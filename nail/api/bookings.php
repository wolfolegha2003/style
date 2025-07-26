<?php
require_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = $_GET['action'] ?? '';

try {
    switch ($method . ':' . $path) {
        case 'GET:my':
            getMyBookings();
            break;
            
        case 'POST:create':
            createBooking();
            break;
            
        case 'GET:details':
            getBookingDetails();
            break;
            
        case 'PUT:update':
            updateBooking();
            break;
            
        case 'DELETE:cancel':
            cancelBooking();
            break;
            
        case 'GET:available-slots':
            getAvailableSlots();
            break;
            
        case 'GET:services':
            getServices();
            break;
            
        case 'GET:masters':
            getMasters();
            break;
            
        default:
            sendError('Метод не найден', 404);
    }
} catch (Exception $e) {
    sendError('Внутренняя ошибка сервера: ' . $e->getMessage(), 500);
}

function getMyBookings() {
    $user = requireAuth();
    
    $status = getParam('status');
    $limit = min(getParam('limit', 50), 100);
    $offset = getParam('offset', 0);
    
    global $pdo;
    
    $sql = "
        SELECT b.*, s.name as service_name, s.price as service_price, 
               s.duration as service_duration, m.name as master_name
        FROM bookings b
        JOIN services s ON b.service_id = s.id
        LEFT JOIN masters m ON b.master_id = m.id
        WHERE b.user_id = ?
    ";
    
    $params = [$user['id']];
    
    if ($status) {
        $sql .= " AND b.status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY b.appointment_date DESC, b.appointment_time DESC";
    $sql .= " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll();
    
    sendJSON([
        'bookings' => $bookings,
        'user_id' => $user['id']
    ]);
}

function createBooking() {
    $user = requireAuth();
    $data = getJSONInput();
    
    if (!$data) {
        sendError('Неверные данные запроса');
    }
    
    $service_id = $data['service_id'] ?? '';
    $master_id = $data['master_id'] ?? null;
    $appointment_date = $data['appointment_date'] ?? '';
    $appointment_time = $data['appointment_time'] ?? '';
    $comment = $data['comment'] ?? '';
    
    // Валидация
    if (!$service_id || !$appointment_date || !$appointment_time) {
        sendError('ID услуги, дата и время обязательны');
    }
    
    // Проверяем, что дата не в прошлом
    $appointmentDateTime = strtotime("$appointment_date $appointment_time");
    if ($appointmentDateTime <= time()) {
        sendError('Нельзя записаться на прошедшее время');
    }
    
    global $pdo;
    
    // Проверяем услугу
    $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ? AND is_active = 1");
    $stmt->execute([$service_id]);
    $service = $stmt->fetch();
    
    if (!$service) {
        sendError('Услуга не найдена', 404);
    }
    
    // Проверяем мастера (если указан)
    if ($master_id) {
        $stmt = $pdo->prepare("SELECT * FROM masters WHERE id = ? AND is_active = 1");
        $stmt->execute([$master_id]);
        $master = $stmt->fetch();
        
        if (!$master) {
            sendError('Мастер не найден', 404);
        }
    }
    
    // Проверяем, нет ли уже записи на это время
    $stmt = $pdo->prepare("
        SELECT id FROM bookings 
        WHERE appointment_date = ? AND appointment_time = ? 
        AND (master_id = ? OR (master_id IS NULL AND ? IS NULL))
        AND status NOT IN ('cancelled')
    ");
    $stmt->execute([$appointment_date, $appointment_time, $master_id, $master_id]);
    
    if ($stmt->fetch()) {
        sendError('На выбранное время уже есть запись', 409);
    }
    
    // Получаем данные пользователя
    $stmt = $pdo->prepare("SELECT name, phone, email FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $userData = $stmt->fetch();
    
    if (!$userData['name']) {
        sendError('Необходимо заполнить имя в профиле');
    }
    
    // Создаем бронирование
    $stmt = $pdo->prepare("
        INSERT INTO bookings 
        (user_id, service_id, master_id, appointment_date, appointment_time, 
         client_name, client_phone, client_email, comment, price, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ");
    
    $stmt->execute([
        $user['id'],
        $service_id,
        $master_id,
        $appointment_date,
        $appointment_time,
        $userData['name'],
        $userData['phone'],
        $userData['email'],
        $comment,
        $service['price']
    ]);
    
    $bookingId = $pdo->lastInsertId();
    
    // Получаем созданное бронирование с деталями
    $stmt = $pdo->prepare("
        SELECT b.*, s.name as service_name, s.price as service_price,
               s.duration as service_duration, m.name as master_name
        FROM bookings b
        JOIN services s ON b.service_id = s.id
        LEFT JOIN masters m ON b.master_id = m.id
        WHERE b.id = ?
    ");
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch();
    
    sendSuccess('Бронирование успешно создано', ['booking' => $booking]);
}

function getBookingDetails() {
    $user = requireAuth();
    $bookingId = getParam('id');
    
    if (!$bookingId) {
        sendError('ID бронирования обязателен');
    }
    
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT b.*, s.name as service_name, s.price as service_price,
               s.duration as service_duration, m.name as master_name,
               u.name as user_name, u.email as user_email
        FROM bookings b
        JOIN services s ON b.service_id = s.id
        LEFT JOIN masters m ON b.master_id = m.id
        JOIN users u ON b.user_id = u.id
        WHERE b.id = ?
    ");
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        sendError('Бронирование не найдено', 404);
    }
    
    // Проверяем права доступа
    if ($user['role'] !== 'admin' && $booking['user_id'] != $user['id']) {
        sendError('Доступ запрещен', 403);
    }
    
    sendJSON(['booking' => $booking]);
}

function updateBooking() {
    $user = requireAuth();
    $data = getJSONInput();
    
    if (!$data) {
        sendError('Неверные данные запроса');
    }
    
    $bookingId = $data['id'] ?? getParam('id');
    $service_id = $data['service_id'] ?? null;
    $master_id = $data['master_id'] ?? null;
    $appointment_date = $data['appointment_date'] ?? null;
    $appointment_time = $data['appointment_time'] ?? null;
    $comment = $data['comment'] ?? null;
    $status = $data['status'] ?? null;
    
    if (!$bookingId) {
        sendError('ID бронирования обязателен');
    }
    
    global $pdo;
    
    // Получаем существующее бронирование
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
    $stmt->execute([$bookingId]);
    $existingBooking = $stmt->fetch();
    
    if (!$existingBooking) {
        sendError('Бронирование не найдено', 404);
    }
    
    // Проверяем права доступа
    if ($user['role'] !== 'admin' && $existingBooking['user_id'] != $user['id']) {
        sendError('Доступ запрещен', 403);
    }
    
    // Проверяем, что бронирование можно изменить
    if ($existingBooking['status'] === 'completed') {
        sendError('Завершенное бронирование нельзя изменить');
    }
    
    // Валидация даты (если изменяется)
    if ($appointment_date && $appointment_time) {
        $appointmentDateTime = strtotime("$appointment_date $appointment_time");
        if ($appointmentDateTime <= time()) {
            sendError('Нельзя перенести на прошедшее время');
        }
    }
    
    // Проверяем услугу (если изменяется)
    if ($service_id && $service_id != $existingBooking['service_id']) {
        $stmt = $pdo->prepare("SELECT price FROM services WHERE id = ? AND is_active = 1");
        $stmt->execute([$service_id]);
        $service = $stmt->fetch();
        
        if (!$service) {
            sendError('Услуга не найдена', 404);
        }
    }
    
    // Обновляем бронирование
    $stmt = $pdo->prepare("
        UPDATE bookings 
        SET service_id = COALESCE(?, service_id),
            master_id = COALESCE(?, master_id),
            appointment_date = COALESCE(?, appointment_date),
            appointment_time = COALESCE(?, appointment_time),
            comment = COALESCE(?, comment),
            status = COALESCE(?, status),
            updated_at = NOW()
        WHERE id = ?
    ");
    
    $stmt->execute([
        $service_id, $master_id, $appointment_date, 
        $appointment_time, $comment, $status, $bookingId
    ]);
    
    // Получаем обновленное бронирование
    $stmt = $pdo->prepare("
        SELECT b.*, s.name as service_name, s.price as service_price,
               s.duration as service_duration, m.name as master_name
        FROM bookings b
        JOIN services s ON b.service_id = s.id
        LEFT JOIN masters m ON b.master_id = m.id
        WHERE b.id = ?
    ");
    $stmt->execute([$bookingId]);
    $updatedBooking = $stmt->fetch();
    
    sendSuccess('Бронирование успешно обновлено', ['booking' => $updatedBooking]);
}

function cancelBooking() {
    $user = requireAuth();
    $bookingId = getParam('id');
    
    if (!$bookingId) {
        sendError('ID бронирования обязателен');
    }
    
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        sendError('Бронирование не найдено', 404);
    }
    
    // Проверяем права доступа
    if ($user['role'] !== 'admin' && $booking['user_id'] != $user['id']) {
        sendError('Доступ запрещен', 403);
    }
    
    // Проверяем, что бронирование можно отменить
    if ($booking['status'] === 'completed') {
        sendError('Завершенное бронирование нельзя отменить');
    }
    
    // Отмечаем как отмененное
    $stmt = $pdo->prepare("UPDATE bookings SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute(['cancelled', $bookingId]);
    
    sendSuccess('Бронирование успешно отменено');
}

function getAvailableSlots() {
    $date = getParam('date');
    $master_id = getParam('master_id');
    
    if (!$date) {
        sendError('Дата обязательна');
    }
    
    if (!strtotime($date)) {
        sendError('Неверный формат даты');
    }
    
    // Проверяем, что дата не в прошлом
    if (strtotime($date) < strtotime(date('Y-m-d'))) {
        sendError('Нельзя выбрать прошедшую дату');
    }
    
    global $pdo;
    
    // Получаем рабочие часы для этого дня недели
    $dayOfWeek = date('w', strtotime($date)); // 0 = Sunday, 1 = Monday, etc.
    
    $stmt = $pdo->prepare("SELECT * FROM working_hours WHERE day_of_week = ? AND is_working = 1");
    $stmt->execute([$dayOfWeek]);
    $workingHours = $stmt->fetch();
    
    if (!$workingHours) {
        sendJSON([
            'available_slots' => [],
            'message' => 'В этот день салон не работает'
        ]);
        return;
    }
    
    // Получаем занятые слоты
    $bookedSlotsQuery = "
        SELECT appointment_time FROM bookings 
        WHERE appointment_date = ? AND status NOT IN ('cancelled')
    ";
    $params = [$date];
    
    if ($master_id) {
        $bookedSlotsQuery .= " AND master_id = ?";
        $params[] = $master_id;
    }
    
    $stmt = $pdo->prepare($bookedSlotsQuery);
    $stmt->execute($params);
    $bookedSlots = $stmt->fetchAll();
    $bookedTimes = array_column($bookedSlots, 'appointment_time');
    
    // Генерируем доступные слоты (каждые 30 минут)
    $startTime = strtotime($workingHours['start_time']);
    $endTime = strtotime($workingHours['end_time']);
    $slots = [];
    
    $current = $startTime;
    while ($current < $endTime) {
        $timeString = date('H:i', $current);
        
        if (!in_array($timeString, $bookedTimes)) {
            // Проверяем, что время еще не прошло (для сегодняшнего дня)
            if ($date > date('Y-m-d') || strtotime("$date $timeString") > time()) {
                $slots[] = $timeString;
            }
        }
        
        $current += 30 * 60; // +30 минут
    }
    
    sendJSON([
        'date' => $date,
        'available_slots' => $slots,
        'working_hours' => [
            'start' => $workingHours['start_time'],
            'end' => $workingHours['end_time']
        ]
    ]);
}

function getServices() {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM services WHERE is_active = 1 ORDER BY category, name");
    $stmt->execute();
    $services = $stmt->fetchAll();
    
    sendJSON(['services' => $services]);
}

function getMasters() {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM masters WHERE is_active = 1 ORDER BY name");
    $stmt->execute();
    $masters = $stmt->fetchAll();
    
    sendJSON(['masters' => $masters]);
}
?>
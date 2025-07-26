<?php
require_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = $_GET['action'] ?? '';

try {
    switch ($method . ':' . $path) {
        case 'GET:bookings':
            getBookings();
            break;
            
        case 'GET:clients':
            getClients();
            break;
            
        case 'GET:statistics':
            getStatistics();
            break;
            
        case 'PUT:booking-status':
            updateBookingStatus();
            break;
            
        case 'POST:service':
            createService();
            break;
            
        case 'PUT:service':
            updateService();
            break;
            
        case 'DELETE:service':
            deleteService();
            break;
            
        case 'POST:master':
            createMaster();
            break;
            
        case 'PUT:master':
            updateMaster();
            break;
            
        case 'DELETE:master':
            deleteMaster();
            break;
            
        default:
            sendError('Метод не найден', 404);
    }
} catch (Exception $e) {
    sendError('Внутренняя ошибка сервера: ' . $e->getMessage(), 500);
}

function getBookings() {
    $user = requireAdmin();
    
    $status = getParam('status');
    $date_from = getParam('date_from');
    $date_to = getParam('date_to');
    $limit = min(getParam('limit', 50), 100);
    $offset = getParam('offset', 0);
    
    global $pdo;
    
    $sql = "
        SELECT b.*, s.name as service_name, s.price as service_price,
               s.duration as service_duration, m.name as master_name,
               u.name as user_name, u.email as user_email
        FROM bookings b
        JOIN services s ON b.service_id = s.id
        LEFT JOIN masters m ON b.master_id = m.id
        JOIN users u ON b.user_id = u.id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($status) {
        $sql .= " AND b.status = ?";
        $params[] = $status;
    }
    
    if ($date_from) {
        $sql .= " AND b.appointment_date >= ?";
        $params[] = $date_from;
    }
    
    if ($date_to) {
        $sql .= " AND b.appointment_date <= ?";
        $params[] = $date_to;
    }
    
    $sql .= " ORDER BY b.appointment_date DESC, b.appointment_time DESC";
    $sql .= " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll();
    
    // Получаем общее количество
    $countSql = "
        SELECT COUNT(*) as total
        FROM bookings b
        WHERE 1=1
    ";
    $countParams = [];
    
    if ($status) {
        $countSql .= " AND b.status = ?";
        $countParams[] = $status;
    }
    
    if ($date_from) {
        $countSql .= " AND b.appointment_date >= ?";
        $countParams[] = $date_from;
    }
    
    if ($date_to) {
        $countSql .= " AND b.appointment_date <= ?";
        $countParams[] = $date_to;
    }
    
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($countParams);
    $total = $countStmt->fetch()['total'];
    
    sendJSON([
        'bookings' => $bookings,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset
    ]);
}

function getClients() {
    $user = requireAdmin();
    
    $search = getParam('search');
    $limit = min(getParam('limit', 50), 100);
    $offset = getParam('offset', 0);
    
    global $pdo;
    
    $sql = "
        SELECT u.*, 
               COUNT(b.id) as total_bookings,
               MAX(b.appointment_date) as last_booking_date
        FROM users u
        LEFT JOIN bookings b ON u.id = b.user_id
        WHERE u.role = 'client'
    ";
    
    $params = [];
    
    if ($search) {
        $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    $sql .= " GROUP BY u.id ORDER BY u.created_at DESC";
    $sql .= " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $clients = $stmt->fetchAll();
    
    sendJSON(['clients' => $clients]);
}

function getStatistics() {
    $user = requireAdmin();
    
    $period = getParam('period', 30); // дней
    $date_from = date('Y-m-d', strtotime("-$period days"));
    $date_to = date('Y-m-d');
    
    global $pdo;
    
    // Общая статистика
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_bookings,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_bookings,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_bookings,
            COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_bookings,
            SUM(CASE WHEN status = 'completed' THEN price ELSE 0 END) as total_revenue
        FROM bookings 
        WHERE appointment_date BETWEEN ? AND ?
    ");
    $stmt->execute([$date_from, $date_to]);
    $stats = $stmt->fetch();
    
    // Статистика по услугам
    $stmt = $pdo->prepare("
        SELECT s.name, s.category, COUNT(b.id) as booking_count,
               SUM(CASE WHEN b.status = 'completed' THEN b.price ELSE 0 END) as revenue
        FROM services s
        LEFT JOIN bookings b ON s.id = b.service_id 
            AND b.appointment_date BETWEEN ? AND ?
        WHERE s.is_active = 1
        GROUP BY s.id
        ORDER BY booking_count DESC
    ");
    $stmt->execute([$date_from, $date_to]);
    $serviceStats = $stmt->fetchAll();
    
    // Статистика по мастерам
    $stmt = $pdo->prepare("
        SELECT m.name, COUNT(b.id) as booking_count,
               SUM(CASE WHEN b.status = 'completed' THEN b.price ELSE 0 END) as revenue
        FROM masters m
        LEFT JOIN bookings b ON m.id = b.master_id 
            AND b.appointment_date BETWEEN ? AND ?
        WHERE m.is_active = 1
        GROUP BY m.id
        ORDER BY booking_count DESC
    ");
    $stmt->execute([$date_from, $date_to]);
    $masterStats = $stmt->fetchAll();
    
    // Статистика по дням
    $stmt = $pdo->prepare("
        SELECT appointment_date as date, 
               COUNT(*) as bookings,
               SUM(CASE WHEN status = 'completed' THEN price ELSE 0 END) as revenue
        FROM bookings 
        WHERE appointment_date BETWEEN ? AND ?
        GROUP BY appointment_date
        ORDER BY appointment_date
    ");
    $stmt->execute([$date_from, $date_to]);
    $dailyStats = $stmt->fetchAll();
    
    sendJSON([
        'period' => $period,
        'date_from' => $date_from,
        'date_to' => $date_to,
        'overview' => $stats,
        'services' => $serviceStats,
        'masters' => $masterStats,
        'daily' => $dailyStats
    ]);
}

function updateBookingStatus() {
    $user = requireAdmin();
    $data = getJSONInput();
    
    if (!$data) {
        sendError('Неверные данные запроса');
    }
    
    $booking_id = $data['booking_id'] ?? '';
    $status = $data['status'] ?? '';
    
    if (!$booking_id || !$status) {
        sendError('ID бронирования и статус обязательны');
    }
    
    $validStatuses = ['pending', 'confirmed', 'completed', 'cancelled'];
    if (!in_array($status, $validStatuses)) {
        sendError('Неверный статус');
    }
    
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        sendError('Бронирование не найдено', 404);
    }
    
    $stmt = $pdo->prepare("UPDATE bookings SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $booking_id]);
    
    sendSuccess('Статус бронирования обновлен');
}

function createService() {
    $user = requireAdmin();
    $data = getJSONInput();
    
    if (!$data) {
        sendError('Неверные данные запроса');
    }
    
    $name = $data['name'] ?? '';
    $description = $data['description'] ?? '';
    $price = $data['price'] ?? 0;
    $duration = $data['duration'] ?? 0;
    $category = $data['category'] ?? '';
    
    if (!$name || !$price || !$duration) {
        sendError('Название, цена и продолжительность обязательны');
    }
    
    global $pdo;
    
    $stmt = $pdo->prepare("
        INSERT INTO services (name, description, price, duration, category)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([$name, $description, $price, $duration, $category]);
    $serviceId = $pdo->lastInsertId();
    
    sendSuccess('Услуга успешно создана', ['service_id' => $serviceId]);
}

function updateService() {
    $user = requireAdmin();
    $data = getJSONInput();
    $serviceId = $data['id'] ?? getParam('id');
    
    if (!$data || !$serviceId) {
        sendError('Неверные данные запроса');
    }
    
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->execute([$serviceId]);
    $service = $stmt->fetch();
    
    if (!$service) {
        sendError('Услуга не найдена', 404);
    }
    
    $name = $data['name'] ?? null;
    $description = $data['description'] ?? null;
    $price = $data['price'] ?? null;
    $duration = $data['duration'] ?? null;
    $category = $data['category'] ?? null;
    $is_active = $data['is_active'] ?? null;
    
    $stmt = $pdo->prepare("
        UPDATE services 
        SET name = COALESCE(?, name),
            description = COALESCE(?, description),
            price = COALESCE(?, price),
            duration = COALESCE(?, duration),
            category = COALESCE(?, category),
            is_active = COALESCE(?, is_active)
        WHERE id = ?
    ");
    
    $stmt->execute([$name, $description, $price, $duration, $category, $is_active, $serviceId]);
    
    sendSuccess('Услуга успешно обновлена');
}

function deleteService() {
    $user = requireAdmin();
    $serviceId = getParam('id');
    
    if (!$serviceId) {
        sendError('ID услуги обязателен');
    }
    
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->execute([$serviceId]);
    $service = $stmt->fetch();
    
    if (!$service) {
        sendError('Услуга не найдена', 404);
    }
    
    // Проверяем, есть ли активные записи на эту услугу
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count FROM bookings 
        WHERE service_id = ? AND status NOT IN ('cancelled', 'completed')
    ");
    $stmt->execute([$serviceId]);
    $activeBookings = $stmt->fetch()['count'];
    
    if ($activeBookings > 0) {
        // Деактивируем вместо удаления
        $stmt = $pdo->prepare("UPDATE services SET is_active = 0 WHERE id = ?");
        $stmt->execute([$serviceId]);
        sendSuccess('Услуга деактивирована (есть активные записи)');
    } else {
        // Удаляем полностью
        $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
        $stmt->execute([$serviceId]);
        sendSuccess('Услуга удалена');
    }
}

function createMaster() {
    $user = requireAdmin();
    $data = getJSONInput();
    
    if (!$data) {
        sendError('Неверные данные запроса');
    }
    
    $name = $data['name'] ?? '';
    $specialization = $data['specialization'] ?? '';
    $experience = $data['experience'] ?? 0;
    $description = $data['description'] ?? '';
    $avatar_url = $data['avatar_url'] ?? '';
    
    if (!$name) {
        sendError('Имя мастера обязательно');
    }
    
    global $pdo;
    
    $stmt = $pdo->prepare("
        INSERT INTO masters (name, specialization, experience, description, avatar_url)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([$name, $specialization, $experience, $description, $avatar_url]);
    $masterId = $pdo->lastInsertId();
    
    sendSuccess('Мастер успешно добавлен', ['master_id' => $masterId]);
}

function updateMaster() {
    $user = requireAdmin();
    $data = getJSONInput();
    $masterId = $data['id'] ?? getParam('id');
    
    if (!$data || !$masterId) {
        sendError('Неверные данные запроса');
    }
    
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM masters WHERE id = ?");
    $stmt->execute([$masterId]);
    $master = $stmt->fetch();
    
    if (!$master) {
        sendError('Мастер не найден', 404);
    }
    
    $name = $data['name'] ?? null;
    $specialization = $data['specialization'] ?? null;
    $experience = $data['experience'] ?? null;
    $description = $data['description'] ?? null;
    $avatar_url = $data['avatar_url'] ?? null;
    $is_active = $data['is_active'] ?? null;
    
    $stmt = $pdo->prepare("
        UPDATE masters 
        SET name = COALESCE(?, name),
            specialization = COALESCE(?, specialization),
            experience = COALESCE(?, experience),
            description = COALESCE(?, description),
            avatar_url = COALESCE(?, avatar_url),
            is_active = COALESCE(?, is_active)
        WHERE id = ?
    ");
    
    $stmt->execute([$name, $specialization, $experience, $description, $avatar_url, $is_active, $masterId]);
    
    sendSuccess('Мастер успешно обновлен');
}

function deleteMaster() {
    $user = requireAdmin();
    $masterId = getParam('id');
    
    if (!$masterId) {
        sendError('ID мастера обязателен');
    }
    
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM masters WHERE id = ?");
    $stmt->execute([$masterId]);
    $master = $stmt->fetch();
    
    if (!$master) {
        sendError('Мастер не найден', 404);
    }
    
    // Проверяем, есть ли активные записи к этому мастеру
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count FROM bookings 
        WHERE master_id = ? AND status NOT IN ('cancelled', 'completed')
    ");
    $stmt->execute([$masterId]);
    $activeBookings = $stmt->fetch()['count'];
    
    if ($activeBookings > 0) {
        // Деактивируем вместо удаления
        $stmt = $pdo->prepare("UPDATE masters SET is_active = 0 WHERE id = ?");
        $stmt->execute([$masterId]);
        sendSuccess('Мастер деактивирован (есть активные записи)');
    } else {
        // Удаляем полностью
        $stmt = $pdo->prepare("DELETE FROM masters WHERE id = ?");
        $stmt->execute([$masterId]);
        sendSuccess('Мастер удален');
    }
}
?>
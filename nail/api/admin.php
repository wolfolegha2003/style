(cd "$(git rev-parse --show-toplevel)" && git apply --3way <<'EOF'
diff --git a/Nail-Style/api/admin.php b/Nail-Style/api/admin.php
--- a/Nail-Style/api/admin.php
+++ b/Nail-Style/api/admin.php
@@ -0,0 +1,611 @@
+<?php
+require_once '../config.php';
+
+$method = $_SERVER['REQUEST_METHOD'];
+$path = $_GET['action'] ?? '';
+
+try {
+    switch ($method . ':' . $path) {
+        case 'GET:bookings':
+            getAllBookings();
+            break;
+            
+        case 'GET:clients':
+            getAllClients();
+            break;
+            
+        case 'GET:statistics':
+            getStatistics();
+            break;
+            
+        case 'PUT:booking-status':
+            updateBookingStatus();
+            break;
+            
+        case 'POST:service':
+            createService();
+            break;
+            
+        case 'PUT:service':
+            updateService();
+            break;
+            
+        case 'DELETE:service':
+            deleteService();
+            break;
+            
+        case 'POST:master':
+            createMaster();
+            break;
+            
+        case 'PUT:master':
+            updateMaster();
+            break;
+            
+        case 'DELETE:master':
+            deleteMaster();
+            break;
+            
+        case 'GET:settings':
+            getSettings();
+            break;
+            
+        case 'PUT:settings':
+            updateSettings();
+            break;
+            
+        default:
+            sendError('Метод не найден', 404);
+    }
+} catch (Exception $e) {
+    sendError('Внутренняя ошибка сервера: ' . $e->getMessage(), 500);
+}
+
+function getAllBookings() {
+    $admin = requireAdmin();
+    
+    $status = getParam('status');
+    $date_from = getParam('date_from');
+    $date_to = getParam('date_to');
+    $limit = min(getParam('limit', 100), 500);
+    $offset = getParam('offset', 0);
+    
+    global $pdo;
+    
+    $sql = "
+        SELECT b.*, s.name as service_name, s.category as service_category,
+               s.price as service_price, s.duration as service_duration,
+               m.name as master_name, u.name as user_name, u.email as user_email
+        FROM bookings b
+        JOIN services s ON b.service_id = s.id
+        LEFT JOIN masters m ON b.master_id = m.id
+        JOIN users u ON b.user_id = u.id
+        WHERE 1=1
+    ";
+    
+    $params = [];
+    
+    if ($status) {
+        $sql .= " AND b.status = ?";
+        $params[] = $status;
+    }
+    
+    if ($date_from) {
+        $sql .= " AND b.appointment_date >= ?";
+        $params[] = $date_from;
+    }
+    
+    if ($date_to) {
+        $sql .= " AND b.appointment_date <= ?";
+        $params[] = $date_to;
+    }
+    
+    $sql .= " ORDER BY b.appointment_date DESC, b.appointment_time DESC";
+    $sql .= " LIMIT ? OFFSET ?";
+    $params[] = $limit;
+    $params[] = $offset;
+    
+    $stmt = $pdo->prepare($sql);
+    $stmt->execute($params);
+    $bookings = $stmt->fetchAll();
+    
+    // Получаем общее количество для пагинации
+    $countSql = "
+        SELECT COUNT(*) as total FROM bookings b
+        JOIN services s ON b.service_id = s.id
+        LEFT JOIN masters m ON b.master_id = m.id
+        JOIN users u ON b.user_id = u.id
+        WHERE 1=1
+    ";
+    
+    $countParams = [];
+    if ($status) {
+        $countSql .= " AND b.status = ?";
+        $countParams[] = $status;
+    }
+    if ($date_from) {
+        $countSql .= " AND b.appointment_date >= ?";
+        $countParams[] = $date_from;
+    }
+    if ($date_to) {
+        $countSql .= " AND b.appointment_date <= ?";
+        $countParams[] = $date_to;
+    }
+    
+    $stmt = $pdo->prepare($countSql);
+    $stmt->execute($countParams);
+    $total = $stmt->fetch()['total'];
+    
+    sendJSON([
+        'bookings' => $bookings,
+        'pagination' => [
+            'total' => (int)$total,
+            'limit' => $limit,
+            'offset' => $offset,
+            'has_more' => ($offset + $limit) < $total
+        ]
+    ]);
+}
+
+function getAllClients() {
+    $admin = requireAdmin();
+    
+    $limit = min(getParam('limit', 100), 500);
+    $offset = getParam('offset', 0);
+    $search = getParam('search');
+    
+    global $pdo;
+    
+    $sql = "
+        SELECT u.id, u.email, u.name, u.phone, u.role, u.birth_date, 
+               u.address, u.city, u.created_at, u.is_active,
+               COUNT(b.id) as total_bookings,
+               COALESCE(SUM(b.price), 0) as total_spent,
+               MAX(b.appointment_date) as last_visit
+        FROM users u
+        LEFT JOIN bookings b ON u.id = b.user_id AND b.status = 'completed'
+        WHERE u.role = 'client'
+    ";
+    
+    $params = [];
+    
+    if ($search) {
+        $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
+        $searchTerm = "%$search%";
+        $params[] = $searchTerm;
+        $params[] = $searchTerm;
+        $params[] = $searchTerm;
+    }
+    
+    $sql .= " GROUP BY u.id ORDER BY u.created_at DESC LIMIT ? OFFSET ?";
+    $params[] = $limit;
+    $params[] = $offset;
+    
+    $stmt = $pdo->prepare($sql);
+    $stmt->execute($params);
+    $clients = $stmt->fetchAll();
+    
+    sendJSON(['clients' => $clients]);
+}
+
+function getStatistics() {
+    $admin = requireAdmin();
+    
+    $period = getParam('period', '30'); // дней
+    $date_from = date('Y-m-d', strtotime("-$period days"));
+    $date_to = date('Y-m-d');
+    
+    global $pdo;
+    
+    // Общая статистика
+    $stats = [];
+    
+    // Общее количество записей за период
+    $stmt = $pdo->prepare("
+        SELECT COUNT(*) as total_bookings,
+               COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_bookings,
+               COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_bookings,
+               COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_bookings,
+               COALESCE(SUM(CASE WHEN status = 'completed' THEN price END), 0) as total_revenue
+        FROM bookings 
+        WHERE appointment_date BETWEEN ? AND ?
+    ");
+    $stmt->execute([$date_from, $date_to]);
+    $stats['bookings'] = $stmt->fetch();
+    
+    // Новые клиенты за период
+    $stmt = $pdo->prepare("
+        SELECT COUNT(*) as new_clients 
+        FROM users 
+        WHERE role = 'client' AND DATE(created_at) BETWEEN ? AND ?
+    ");
+    $stmt->execute([$date_from, $date_to]);
+    $stats['new_clients'] = $stmt->fetch()['new_clients'];
+    
+    // Популярные услуги
+    $stmt = $pdo->prepare("
+        SELECT s.name, s.category, COUNT(b.id) as bookings_count,
+               COALESCE(SUM(CASE WHEN b.status = 'completed' THEN b.price END), 0) as revenue
+        FROM bookings b
+        JOIN services s ON b.service_id = s.id
+        WHERE b.appointment_date BETWEEN ? AND ?
+        GROUP BY s.id, s.name, s.category
+        ORDER BY bookings_count DESC
+        LIMIT 10
+    ");
+    $stmt->execute([$date_from, $date_to]);
+    $stats['popular_services'] = $stmt->fetchAll();
+    
+    // Топ мастера
+    $stmt = $pdo->prepare("
+        SELECT m.name, COUNT(b.id) as bookings_count,
+               COALESCE(SUM(CASE WHEN b.status = 'completed' THEN b.price END), 0) as revenue,
+               ROUND(AVG(CASE WHEN b.status = 'completed' THEN b.price END), 2) as avg_price
+        FROM bookings b
+        JOIN masters m ON b.master_id = m.id
+        WHERE b.appointment_date BETWEEN ? AND ?
+        GROUP BY m.id, m.name
+        ORDER BY bookings_count DESC
+        LIMIT 10
+    ");
+    $stmt->execute([$date_from, $date_to]);
+    $stats['top_masters'] = $stmt->fetchAll();
+    
+    // Доходы по дням
+    $stmt = $pdo->prepare("
+        SELECT DATE(appointment_date) as date,
+               COUNT(*) as bookings_count,
+               COALESCE(SUM(CASE WHEN status = 'completed' THEN price END), 0) as revenue
+        FROM bookings 
+        WHERE appointment_date BETWEEN ? AND ?
+        GROUP BY DATE(appointment_date)
+        ORDER BY date ASC
+    ");
+    $stmt->execute([$date_from, $date_to]);
+    $stats['daily_revenue'] = $stmt->fetchAll();
+    
+    sendJSON([
+        'statistics' => $stats,
+        'period' => [
+            'from' => $date_from,
+            'to' => $date_to,
+            'days' => $period
+        ]
+    ]);
+}
+
+function updateBookingStatus() {
+    $admin = requireAdmin();
+    $data = getJSONInput();
+    
+    if (!$data) {
+        sendError('Неверные данные запроса');
+    }
+    
+    $bookingId = $data['booking_id'] ?? getParam('id');
+    $status = $data['status'] ?? '';
+    
+    if (!$bookingId || !$status) {
+        sendError('ID бронирования и статус обязательны');
+    }
+    
+    $allowedStatuses = ['pending', 'confirmed', 'completed', 'cancelled'];
+    if (!in_array($status, $allowedStatuses)) {
+        sendError('Неверный статус');
+    }
+    
+    global $pdo;
+    
+    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
+    $stmt->execute([$bookingId]);
+    $booking = $stmt->fetch();
+    
+    if (!$booking) {
+        sendError('Бронирование не найдено', 404);
+    }
+    
+    $stmt = $pdo->prepare("UPDATE bookings SET status = ?, updated_at = NOW() WHERE id = ?");
+    $stmt->execute([$status, $bookingId]);
+    
+    sendSuccess('Статус бронирования обновлен');
+}
+
+function createService() {
+    $admin = requireAdmin();
+    $data = getJSONInput();
+    
+    if (!$data) {
+        sendError('Неверные данные запроса');
+    }
+    
+    $name = $data['name'] ?? '';
+    $description = $data['description'] ?? '';
+    $price = $data['price'] ?? '';
+    $duration = $data['duration'] ?? '';
+    $category = $data['category'] ?? '';
+    
+    if (!$name || !$price || !$duration) {
+        sendError('Название, цена и продолжительность обязательны');
+    }
+    
+    if (!is_numeric($price) || $price <= 0) {
+        sendError('Цена должна быть положительным числом');
+    }
+    
+    if (!is_numeric($duration) || $duration <= 0) {
+        sendError('Продолжительность должна быть положительным числом');
+    }
+    
+    global $pdo;
+    
+    $stmt = $pdo->prepare("
+        INSERT INTO services (name, description, price, duration, category)
+        VALUES (?, ?, ?, ?, ?)
+    ");
+    
+    $stmt->execute([$name, $description, (int)$price, (int)$duration, $category]);
+    
+    $serviceId = $pdo->lastInsertId();
+    
+    $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
+    $stmt->execute([$serviceId]);
+    $service = $stmt->fetch();
+    
+    sendSuccess('Услуга успешно создана', ['service' => $service]);
+}
+
+function updateService() {
+    $admin = requireAdmin();
+    $data = getJSONInput();
+    
+    if (!$data) {
+        sendError('Неверные данные запроса');
+    }
+    
+    $serviceId = $data['id'] ?? getParam('id');
+    $name = $data['name'] ?? null;
+    $description = $data['description'] ?? null;
+    $price = $data['price'] ?? null;
+    $duration = $data['duration'] ?? null;
+    $category = $data['category'] ?? null;
+    $is_active = $data['is_active'] ?? null;
+    
+    if (!$serviceId) {
+        sendError('ID услуги обязателен');
+    }
+    
+    if ($price !== null && (!is_numeric($price) || $price <= 0)) {
+        sendError('Цена должна быть положительным числом');
+    }
+    
+    if ($duration !== null && (!is_numeric($duration) || $duration <= 0)) {
+        sendError('Продолжительность должна быть положительным числом');
+    }
+    
+    global $pdo;
+    
+    $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
+    $stmt->execute([$serviceId]);
+    $existingService = $stmt->fetch();
+    
+    if (!$existingService) {
+        sendError('Услуга не найдена', 404);
+    }
+    
+    $stmt = $pdo->prepare("
+        UPDATE services 
+        SET name = COALESCE(?, name),
+            description = COALESCE(?, description),
+            price = COALESCE(?, price),
+            duration = COALESCE(?, duration),
+            category = COALESCE(?, category),
+            is_active = COALESCE(?, is_active)
+        WHERE id = ?
+    ");
+    
+    $stmt->execute([$name, $description, $price, $duration, $category, $is_active, $serviceId]);
+    
+    $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
+    $stmt->execute([$serviceId]);
+    $updatedService = $stmt->fetch();
+    
+    sendSuccess('Услуга успешно обновлена', ['service' => $updatedService]);
+}
+
+function deleteService() {
+    $admin = requireAdmin();
+    $serviceId = getParam('id');
+    
+    if (!$serviceId) {
+        sendError('ID услуги обязателен');
+    }
+    
+    global $pdo;
+    
+    $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
+    $stmt->execute([$serviceId]);
+    $service = $stmt->fetch();
+    
+    if (!$service) {
+        sendError('Услуга не найдена', 404);
+    }
+    
+    // Проверяем, есть ли активные записи
+    $stmt = $pdo->prepare("
+        SELECT COUNT(*) as count FROM bookings 
+        WHERE service_id = ? AND status IN ('pending', 'confirmed')
+    ");
+    $stmt->execute([$serviceId]);
+    $activeBookings = $stmt->fetch()['count'];
+    
+    if ($activeBookings > 0) {
+        sendError('Нельзя удалить услугу с активными записями');
+    }
+    
+    // Деактивируем вместо удаления
+    $stmt = $pdo->prepare("UPDATE services SET is_active = 0 WHERE id = ?");
+    $stmt->execute([$serviceId]);
+    
+    sendSuccess('Услуга успешно деактивирована');
+}
+
+function createMaster() {
+    $admin = requireAdmin();
+    $data = getJSONInput();
+    
+    if (!$data) {
+        sendError('Неверные данные запроса');
+    }
+    
+    $name = $data['name'] ?? '';
+    $specialization = $data['specialization'] ?? '';
+    $experience = $data['experience'] ?? null;
+    $description = $data['description'] ?? '';
+    
+    if (!$name) {
+        sendError('Имя мастера обязательно');
+    }
+    
+    global $pdo;
+    
+    $stmt = $pdo->prepare("
+        INSERT INTO masters (name, specialization, experience, description)
+        VALUES (?, ?, ?, ?)
+    ");
+    
+    $stmt->execute([$name, $specialization, $experience, $description]);
+    
+    $masterId = $pdo->lastInsertId();
+    
+    $stmt = $pdo->prepare("SELECT * FROM masters WHERE id = ?");
+    $stmt->execute([$masterId]);
+    $master = $stmt->fetch();
+    
+    sendSuccess('Мастер успешно добавлен', ['master' => $master]);
+}
+
+function updateMaster() {
+    $admin = requireAdmin();
+    $data = getJSONInput();
+    
+    if (!$data) {
+        sendError('Неверные данные запроса');
+    }
+    
+    $masterId = $data['id'] ?? getParam('id');
+    $name = $data['name'] ?? null;
+    $specialization = $data['specialization'] ?? null;
+    $experience = $data['experience'] ?? null;
+    $description = $data['description'] ?? null;
+    $is_active = $data['is_active'] ?? null;
+    
+    if (!$masterId) {
+        sendError('ID мастера обязателен');
+    }
+    
+    global $pdo;
+    
+    $stmt = $pdo->prepare("SELECT * FROM masters WHERE id = ?");
+    $stmt->execute([$masterId]);
+    $existingMaster = $stmt->fetch();
+    
+    if (!$existingMaster) {
+        sendError('Мастер не найден', 404);
+    }
+    
+    $stmt = $pdo->prepare("
+        UPDATE masters 
+        SET name = COALESCE(?, name),
+            specialization = COALESCE(?, specialization),
+            experience = COALESCE(?, experience),
+            description = COALESCE(?, description),
+            is_active = COALESCE(?, is_active)
+        WHERE id = ?
+    ");
+    
+    $stmt->execute([$name, $specialization, $experience, $description, $is_active, $masterId]);
+    
+    $stmt = $pdo->prepare("SELECT * FROM masters WHERE id = ?");
+    $stmt->execute([$masterId]);
+    $updatedMaster = $stmt->fetch();
+    
+    sendSuccess('Мастер успешно обновлен', ['master' => $updatedMaster]);
+}
+
+function deleteMaster() {
+    $admin = requireAdmin();
+    $masterId = getParam('id');
+    
+    if (!$masterId) {
+        sendError('ID мастера обязателен');
+    }
+    
+    global $pdo;
+    
+    $stmt = $pdo->prepare("SELECT * FROM masters WHERE id = ?");
+    $stmt->execute([$masterId]);
+    $master = $stmt->fetch();
+    
+    if (!$master) {
+        sendError('Мастер не найден', 404);
+    }
+    
+    // Проверяем, есть ли активные записи
+    $stmt = $pdo->prepare("
+        SELECT COUNT(*) as count FROM bookings 
+        WHERE master_id = ? AND status IN ('pending', 'confirmed')
+    ");
+    $stmt->execute([$masterId]);
+    $activeBookings = $stmt->fetch()['count'];
+    
+    if ($activeBookings > 0) {
+        sendError('Нельзя удалить мастера с активными записями');
+    }
+    
+    // Деактивируем вместо удаления
+    $stmt = $pdo->prepare("UPDATE masters SET is_active = 0 WHERE id = ?");
+    $stmt->execute([$masterId]);
+    
+    sendSuccess('Мастер успешно деактивирован');
+}
+
+function getSettings() {
+    $admin = requireAdmin();
+    
+    global $pdo;
+    
+    $stmt = $pdo->prepare("SELECT * FROM salon_settings");
+    $stmt->execute();
+    $settings = $stmt->fetchAll();
+    
+    // Преобразуем в удобный формат
+    $settingsArray = [];
+    foreach ($settings as $setting) {
+        $settingsArray[$setting['setting_key']] = $setting['setting_value'];
+    }
+    
+    sendJSON(['settings' => $settingsArray]);
+}
+
+function updateSettings() {
+    $admin = requireAdmin();
+    $data = getJSONInput();
+    
+    if (!$data || !isset($data['settings'])) {
+        sendError('Неверные данные запроса');
+    }
+    
+    global $pdo;
+    
+    foreach ($data['settings'] as $key => $value) {
+        $stmt = $pdo->prepare("
+            INSERT INTO salon_settings (setting_key, setting_value) 
+            VALUES (?, ?) 
+            ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
+        ");
+        $stmt->execute([$key, $value, $value]);
+    }
+    
+    sendSuccess('Настройки успешно обновлены');
+}
+?>
EOF
)
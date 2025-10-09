<?php
require_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];
$path = $_GET['action'] ?? '';

try {
    switch ($method . ':' . $path) {
        case 'POST:register':
            register();
            break;
            
        case 'POST:login':
            login();
            break;
            
        case 'GET:me':
            getProfile();
            break;
            
        case 'PUT:profile':
            updateProfile();
            break;
            
        case 'PUT:change-password':
            changePassword();
            break;
            
        case 'POST:verify-token':
            verifyUserToken();
            break;
            
        default:
            sendError('Метод не найден', 404);
    }
} catch (Exception $e) {
    sendError('Внутренняя ошибка сервера: ' . $e->getMessage(), 500);
}

function register() {
    $data = getJSONInput();
    
    if (!$data) {
        sendError('Неверные данные запроса');
    }
    
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    $name = $data['name'] ?? '';
    $phone = $data['phone'] ?? '';
    $role = $data['role'] ?? 'client';
    
    // Валидация
    if (!$email || !$password || !$name) {
        sendError('Email, пароль и имя обязательны');
    }
    
    if (!validateEmail($email)) {
        sendError('Неверный формат email');
    }
    
    if (strlen($password) < 6) {
        sendError('Пароль должен содержать минимум 6 символов');
    }
    
    if ($phone && !validatePhone($phone)) {
        sendError('Неверный формат телефона');
    }
    
    global $pdo;
    
    // Проверяем, не существует ли пользователь
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([strtolower($email)]);
    
    if ($stmt->fetch()) {
        sendError('Пользователь с таким email уже существует', 409);
    }
    
    // Создаем пользователя
    $hashedPassword = hashPassword($password);
    
    $stmt = $pdo->prepare("
        INSERT INTO users (email, password, name, phone, role) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        strtolower($email),
        $hashedPassword,
        $name,
        $phone,
        $role
    ]);
    
    $userId = $pdo->lastInsertId();
    
    // Генерируем токен
    $token = generateToken($userId, strtolower($email), $role);
    
    sendSuccess('Пользователь успешно зарегистрирован', [
        'token' => $token,
        'user' => [
            'id' => $userId,
            'email' => strtolower($email),
            'name' => $name,
            'phone' => $phone,
            'role' => $role
        ]
    ]);
}

function login() {
    $data = getJSONInput();
    
    if (!$data) {
        sendError('Неверные данные запроса');
    }
    
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';
    
    if (!$email || !$password) {
        sendError('Email и пароль обязательны');
    }
    
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([strtolower($email)]);
    $user = $stmt->fetch();
    
    if (!$user) {
        sendError('Неверный email или пароль', 401);
    }
    
    if (!$user['is_active']) {
        sendError('Аккаунт заблокирован', 401);
    }
    
    if (!verifyPassword($password, $user['password'])) {
        sendError('Неверный email или пароль', 401);
    }
    
    // Обновляем время последнего входа
    $stmt = $pdo->prepare("UPDATE users SET updated_at = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    // Генерируем токен
    $token = generateToken($user['id'], $user['email'], $user['role']);
    
    sendSuccess('Успешный вход в систему', [
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'phone' => $user['phone'],
            'role' => $user['role'],
            'birth_date' => $user['birth_date'],
            'address' => $user['address'],
            'city' => $user['city'],
            'preferences' => $user['preferences']
        ]
    ]);
}

function getProfile() {
    $user = requireAuth();
    
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT id, email, name, phone, role, birth_date, address, city, 
               preferences, created_at, updated_at 
        FROM users WHERE id = ?
    ");
    $stmt->execute([$user['id']]);
    $userDetails = $stmt->fetch();
    
    if (!$userDetails) {
        sendError('Пользователь не найден', 404);
    }
    
    sendJSON(['user' => $userDetails]);
}

function updateProfile() {
    $user = requireAuth();
    $data = getJSONInput();
    
    if (!$data) {
        sendError('Неверные данные запроса');
    }
    
    $name = $data['name'] ?? null;
    $phone = $data['phone'] ?? null;
    $birth_date = $data['birth_date'] ?? null;
    $address = $data['address'] ?? null;
    $city = $data['city'] ?? null;
    $preferences = $data['preferences'] ?? null;
    
    // Валидация
    if ($name && strlen(trim($name)) < 2) {
        sendError('Имя должно содержать минимум 2 символа');
    }
    
    if ($phone && !validatePhone($phone)) {
        sendError('Неверный формат телефона');
    }
    
    if ($birth_date && !strtotime($birth_date)) {
        sendError('Неверный формат даты рождения');
    }
    
    global $pdo;
    
    // Обновляем данные
    $stmt = $pdo->prepare("
        UPDATE users 
        SET name = COALESCE(?, name),
            phone = COALESCE(?, phone),
            birth_date = COALESCE(?, birth_date),
            address = COALESCE(?, address),
            city = COALESCE(?, city),
            preferences = COALESCE(?, preferences),
            updated_at = NOW()
        WHERE id = ?
    ");
    
    $stmt->execute([
        $name, $phone, $birth_date, $address, $city, $preferences, $user['id']
    ]);
    
    // Получаем обновленные данные
    $stmt = $pdo->prepare("
        SELECT id, email, name, phone, role, birth_date, address, city, 
               preferences, created_at, updated_at 
        FROM users WHERE id = ?
    ");
    $stmt->execute([$user['id']]);
    $updatedUser = $stmt->fetch();
    
    sendSuccess('Профиль успешно обновлен', ['user' => $updatedUser]);
}

function changePassword() {
    $user = requireAuth();
    $data = getJSONInput();
    
    if (!$data) {
        sendError('Неверные данные запроса');
    }
    
    $currentPassword = $data['currentPassword'] ?? '';
    $newPassword = $data['newPassword'] ?? '';
    
    if (!$currentPassword || !$newPassword) {
        sendError('Текущий и новый пароли обязательны');
    }
    
    if (strlen($newPassword) < 6) {
        sendError('Новый пароль должен содержать минимум 6 символов');
    }
    
    global $pdo;
    
    // Получаем текущий пароль
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $userData = $stmt->fetch();
    
    // Проверяем текущий пароль
    if (!verifyPassword($currentPassword, $userData['password'])) {
        sendError('Неверный текущий пароль', 401);
    }
    
    // Обновляем пароль
    $hashedNewPassword = hashPassword($newPassword);
    
    $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$hashedNewPassword, $user['id']]);
    
    sendSuccess('Пароль успешно изменен');
}

function verifyUserToken() {
    $user = requireAuth();
    
    sendJSON([
        'valid' => true,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'role' => $user['role']
        ]
    ]);
}
?>
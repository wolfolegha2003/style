<?php
// Конфигурация для Nail & Style
session_start();

// Настройки базы данных
define('DB_HOST', 'localhost');
define('DB_NAME', 'nail_style');
define('DB_USER', 'root'); // измените на ваш пользователь
define('DB_PASS', '');     // измените на ваш пароль

// Настройки безопасности
define('JWT_SECRET', 'your-super-secret-jwt-key-change-in-production');
define('PASSWORD_SALT', 'nail_style_salt_2024');

// Настройки салона
define('SALON_NAME', 'Nail & Style');
define('SALON_ADDRESS', 'ул. Астана Кесаева, 9, г. Севастополь');
define('SALON_PHONE', '+7 (978) 599-59-83');
define('SALON_EMAIL', 'info@nailstyle-sev.ru');

// Настройки файлов
define('UPLOAD_PATH', './uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB

// Часовой пояс
date_default_timezone_set('Europe/Simferopol');

// Настройки ошибок
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CORS заголовки
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Обработка OPTIONS запросов
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('HTTP/1.0 200 OK');
    exit();
}

// Подключение к базе данных
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    // Если база не существует, создаем её
    try {
        $pdo_create = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo_create->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
    } catch (PDOException $e2) {
        die("Ошибка подключения к базе данных: " . $e2->getMessage());
    }
}

// Функции для работы с JSON
function sendJSON($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

function sendError($message, $status = 400) {
    sendJSON(['error' => $message], $status);
}

function sendSuccess($message, $data = null) {
    $response = ['message' => $message];
    if ($data !== null) {
        $response = array_merge($response, $data);
    }
    sendJSON($response);
}

// Функция для получения JSON из запроса
function getJSONInput() {
    $input = file_get_contents('php://input');
    return json_decode($input, true);
}

// Функция для хеширования пароля
function hashPassword($password) {
    return password_hash($password . PASSWORD_SALT, PASSWORD_DEFAULT);
}

// Функция для проверки пароля
function verifyPassword($password, $hash) {
    return password_verify($password . PASSWORD_SALT, $hash);
}

// Функция для генерации токена
function generateToken($userId, $email, $role) {
    $payload = [
        'user_id' => $userId,
        'email' => $email,
        'role' => $role,
        'exp' => time() + (7 * 24 * 60 * 60) // 7 дней
    ];
    
    return base64_encode(json_encode($payload)) . '.' . hash('sha256', json_encode($payload) . JWT_SECRET);
}

// Функция для проверки токена
function verifyToken($token) {
    if (!$token) return false;
    
    $parts = explode('.', $token);
    if (count($parts) !== 2) return false;
    
    $payload = json_decode(base64_decode($parts[0]), true);
    $signature = $parts[1];
    
    if (!$payload) return false;
    
    // Проверяем подпись
    $expectedSignature = hash('sha256', json_encode($payload) . JWT_SECRET);
    if (!hash_equals($expectedSignature, $signature)) return false;
    
    // Проверяем срок действия
    if ($payload['exp'] < time()) return false;
    
    return $payload;
}

// Функция для получения текущего пользователя
function getCurrentUser() {
    $token = null;
    
    // Поддержка разных способов получения заголовков
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $token = str_replace('Bearer ', '', $headers['Authorization']);
        }
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $token = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
    }
    
    if (!$token) return null;
    
    $payload = verifyToken($token);
    if (!$payload) return null;
    
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT id, email, name, role, is_active FROM users WHERE id = ?");
    $stmt->execute([$payload['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user || !$user['is_active']) return null;
    
    return $user;
}

// Функция для требования аутентификации
function requireAuth() {
    $user = getCurrentUser();
    if (!$user) {
        sendError('Необходима авторизация', 401);
    }
    return $user;
}

// Функция для требования прав администратора
function requireAdmin() {
    $user = requireAuth();
    if ($user['role'] !== 'admin') {
        sendError('Доступ запрещен', 403);
    }
    return $user;
}

// Функция для валидации email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Функция для валидации телефона
function validatePhone($phone) {
    if (empty($phone)) return false;
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    // Поддерживаем российские номера в разных форматах
    return preg_match('/^(\+7|8|7)\d{10}$/', $phone) || preg_match('/^\+\d{10,15}$/', $phone);
}

// Функция для безопасной работы с GET параметрами
function getParam($key, $default = null) {
    return isset($_GET[$key]) ? trim($_GET[$key]) : $default;
}

// Функция для безопасной работы с POST параметрами
function postParam($key, $default = null) {
    return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}
?>
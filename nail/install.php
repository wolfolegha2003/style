<?php
require_once 'config.php';

// Скрипт установки базы данных для Nail & Style
echo "<!DOCTYPE html>
<html lang='ru'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Установка Nail & Style</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 10px 0; }
        h1 { color: #e91e63; }
    </style>
</head>
<body>
    <h1>🎉 Установка Nail & Style</h1>";

try {
    // Создаем таблицы
    
    // Таблица пользователей
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            name VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            role ENUM('client', 'admin') DEFAULT 'client',
            birth_date DATE,
            address TEXT,
            city VARCHAR(100) DEFAULT 'Севастополь',
            preferences TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            is_active BOOLEAN DEFAULT TRUE,
            INDEX idx_email (email),
            INDEX idx_role (role)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<div class='success'>✅ Таблица users создана</div>";
    
    // Таблица услуг
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS services (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            price INT NOT NULL,
            duration INT NOT NULL COMMENT 'Продолжительность в минутах',
            category VARCHAR(100),
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_active (is_active),
            INDEX idx_category (category)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<div class='success'>✅ Таблица services создана</div>";
    
    // Таблица мастеров
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS masters (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            specialization VARCHAR(255),
            experience INT,
            description TEXT,
            avatar_url VARCHAR(500),
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<div class='success'>✅ Таблица masters создана</div>";
    
    // Таблица записей
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS bookings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            service_id INT NOT NULL,
            master_id INT,
            appointment_date DATE NOT NULL,
            appointment_time TIME NOT NULL,
            status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
            client_name VARCHAR(255) NOT NULL,
            client_phone VARCHAR(20) NOT NULL,
            client_email VARCHAR(255),
            comment TEXT,
            price INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
            FOREIGN KEY (master_id) REFERENCES masters(id) ON DELETE SET NULL,
            INDEX idx_user_id (user_id),
            INDEX idx_date (appointment_date),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<div class='success'>✅ Таблица bookings создана</div>";
    
    // Таблица настроек
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS salon_settings (
            setting_key VARCHAR(100) PRIMARY KEY,
            setting_value TEXT,
            description TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<div class='success'>✅ Таблица salon_settings создана</div>";
    
    // Таблица рабочего времени
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS working_hours (
            id INT AUTO_INCREMENT PRIMARY KEY,
            day_of_week INT NOT NULL COMMENT '0=Воскресенье, 1=Понедельник, ..., 6=Суббота',
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            is_working BOOLEAN DEFAULT TRUE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<div class='success'>✅ Таблица working_hours создана</div>";
    
    // Таблица отзывов
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            booking_id INT,
            rating INT CHECK (rating >= 1 AND rating <= 5),
            comment TEXT,
            is_approved BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL,
            INDEX idx_approved (is_approved),
            INDEX idx_rating (rating)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<div class='success'>✅ Таблица reviews создана</div>";
    
    // Проверяем, есть ли уже данные
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch()['count'];
    
    if ($userCount == 0) {
        // Создаем администратора
        $adminPassword = hashPassword('admin123');
        $pdo->exec("
            INSERT INTO users (email, password, name, role, phone, city) VALUES 
            ('admin@nailstyle-sev.ru', '$adminPassword', 'Администратор', 'admin', '+7 (978) 599-59-83', 'Севастополь')
        ");
        echo "<div class='success'>✅ Создан администратор: admin@nailstyle-sev.ru / admin123</div>";
        
        // Добавляем услуги
        $pdo->exec("
            INSERT INTO services (name, description, price, duration, category) VALUES 
            ('Классический маникюр', 'Профессиональный классический маникюр с покрытием', 1500, 60, 'Маникюр'),
            ('Гель-лак', 'Покрытие ногтей стойким гель-лаком', 2000, 90, 'Маникюр'),
            ('Дизайн ногтей', 'Художественный дизайн ногтей любой сложности', 3000, 120, 'Дизайн'),
            ('Наращивание', 'Наращивание ногтей гелем или акрилом', 3500, 150, 'Наращивание'),
            ('Укрепление ногтей', 'Укрепление натуральных ногтей биогелем', 2500, 90, 'Укрепление'),
            ('Педикюр', 'Классический педикюр с покрытием', 2000, 90, 'Педикюр')
        ");
        echo "<div class='success'>✅ Добавлены базовые услуги</div>";
        
        // Добавляем мастеров
        $pdo->exec("
            INSERT INTO masters (name, specialization, experience, description) VALUES 
            ('Анна Иванова', 'Маникюр, дизайн ногтей', 5, 'Опытный мастер маникюра и дизайна'),
            ('Мария Петрова', 'Наращивание, укрепление', 7, 'Специалист по наращиванию и укреплению ногтей'),
            ('Елена Сидорова', 'Педикюр, классический маникюр', 3, 'Мастер педикюра и классического маникюра')
        ");
        echo "<div class='success'>✅ Добавлены мастера</div>";
        
        // Добавляем рабочее время (Пн-Сб: 10:00-20:00)
        $pdo->exec("
            INSERT INTO working_hours (day_of_week, start_time, end_time, is_working) VALUES 
            (1, '10:00', '20:00', 1),
            (2, '10:00', '20:00', 1),
            (3, '10:00', '20:00', 1),
            (4, '10:00', '20:00', 1),
            (5, '10:00', '20:00', 1),
            (6, '10:00', '20:00', 1),
            (0, '10:00', '18:00', 0)
        ");
        echo "<div class='success'>✅ Настроено рабочее время</div>";
        
        // Добавляем настройки
        $pdo->exec("
            INSERT INTO salon_settings (setting_key, setting_value, description) VALUES 
            ('salon_name', '" . SALON_NAME . "', 'Название салона'),
            ('salon_address', '" . SALON_ADDRESS . "', 'Адрес салона'),
            ('salon_phone', '" . SALON_PHONE . "', 'Телефон салона'),
            ('salon_email', '" . SALON_EMAIL . "', 'Email салона'),
            ('booking_advance_days', '30', 'На сколько дней вперед можно записаться'),
            ('min_booking_time', '60', 'Минимальное время до записи (в минутах)')
        ");
        echo "<div class='success'>✅ Добавлены настройки салона</div>";
    } else {
        echo "<div class='info'>ℹ️ Данные уже существуют, пропускаем создание тестовых данных</div>";
    }
    
    echo "<div class='success'>🎉 <strong>Установка завершена успешно!</strong></div>";
    echo "<div class='info'>
        <h3>Доступы:</h3>
        <p><strong>Администратор:</strong> admin@nailstyle-sev.ru / admin123</p>
        <p><strong>Главная страница:</strong> <a href='index.html'>index.html</a></p>
        <p><strong>API:</strong> доступно через папку api/</p>
    </div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Ошибка: " . $e->getMessage() . "</div>";
}

echo "</body></html>";
?>
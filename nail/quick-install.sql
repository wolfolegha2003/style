-- Быстрая установка БД для Nail & Style
CREATE DATABASE IF NOT EXISTS nail_style CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nail_style;

-- Таблица пользователей
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица услуг
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица мастеров
CREATE TABLE IF NOT EXISTS masters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    specialization VARCHAR(255),
    experience INT,
    description TEXT,
    avatar_url VARCHAR(500),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица записей
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица настроек
CREATE TABLE IF NOT EXISTS salon_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица рабочего времени
CREATE TABLE IF NOT EXISTS working_hours (
    id INT AUTO_INCREMENT PRIMARY KEY,
    day_of_week INT NOT NULL COMMENT '0=Воскресенье, 1=Понедельник, ..., 6=Суббота',
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_working BOOLEAN DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица отзывов
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Добавляем администратора
INSERT INTO users (email, password, name, role, phone, city) VALUES 
('admin@nailstyle-sev.ru', '$2y$10$CwTycUXWue0Thq9StjUM0.BaQBDD2tJAGzZ3Y99H8YHdNrPKdwDJG', 'Администратор', 'admin', '+7 (978) 599-59-83', 'Севастополь');

-- Добавляем услуги
INSERT INTO services (name, description, price, duration, category) VALUES 
('Классический маникюр', 'Профессиональный классический маникюр с покрытием', 1500, 60, 'Маникюр'),
('Гель-лак', 'Покрытие ногтей стойким гель-лаком', 2000, 90, 'Маникюр'),
('Дизайн ногтей', 'Художественный дизайн ногтей любой сложности', 3000, 120, 'Дизайн'),
('Наращивание', 'Наращивание ногтей гелем или акрилом', 3500, 150, 'Наращивание'),
('Укрепление ногтей', 'Укрепление натуральных ногтей биогелем', 2500, 90, 'Укрепление'),
('Педикюр', 'Классический педикюр с покрытием', 2000, 90, 'Педикюр');

-- Добавляем мастеров
INSERT INTO masters (name, specialization, experience, description) VALUES 
('Анна Иванова', 'Маникюр, дизайн ногтей', 5, 'Опытный мастер маникюра и дизайна'),
('Мария Петрова', 'Наращивание, укрепление', 7, 'Специалист по наращиванию и укреплению ногтей'),
('Елена Сидорова', 'Педикюр, классический маникюр', 3, 'Мастер педикюра и классического маникюра');

-- Добавляем рабочее время (Пн-Сб: 10:00-20:00)
INSERT INTO working_hours (day_of_week, start_time, end_time, is_working) VALUES 
(1, '10:00', '20:00', 1),
(2, '10:00', '20:00', 1),
(3, '10:00', '20:00', 1),
(4, '10:00', '20:00', 1),
(5, '10:00', '20:00', 1),
(6, '10:00', '20:00', 1),
(0, '10:00', '18:00', 0);

-- Добавляем настройки
INSERT INTO salon_settings (setting_key, setting_value, description) VALUES 
('salon_name', 'Nail & Style', 'Название салона'),
('salon_address', 'ул. Астана Кесаева, 9, г. Севастополь', 'Адрес салона'),
('salon_phone', '+7 (978) 599-59-83', 'Телефон салона'),
('salon_email', 'info@nailstyle-sev.ru', 'Email салона'),
('booking_advance_days', '30', 'На сколько дней вперед можно записаться'),
('min_booking_time', '60', 'Минимальное время до записи (в минутах)');
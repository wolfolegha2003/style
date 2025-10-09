-- =====================================================
-- База данных для сайта "Nail & Style"
-- Полная установка с данными
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Создание базы данных
CREATE DATABASE IF NOT EXISTS `nail_style` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `nail_style`;

-- =====================================================
-- Структура таблиц
-- =====================================================

-- Таблица пользователей
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('client','admin') DEFAULT 'client',
  `birth_date` date DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT 'Севастополь',
  `preferences` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_email` (`email`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица услуг
DROP TABLE IF EXISTS `services`;
CREATE TABLE `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` int(11) NOT NULL,
  `duration` int(11) NOT NULL COMMENT 'Продолжительность в минутах',
  `category` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_active` (`is_active`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица мастеров
DROP TABLE IF EXISTS `masters`;
CREATE TABLE `masters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `specialization` varchar(255) DEFAULT NULL,
  `experience` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `avatar_url` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица записей
DROP TABLE IF EXISTS `bookings`;
CREATE TABLE `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `master_id` int(11) DEFAULT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  `client_name` varchar(255) NOT NULL,
  `client_phone` varchar(20) NOT NULL,
  `client_email` varchar(255) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `price` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_date` (`appointment_date`),
  KEY `idx_status` (`status`),
  KEY `service_id` (`service_id`),
  KEY `master_id` (`master_id`),
  CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bookings_ibfk_3` FOREIGN KEY (`master_id`) REFERENCES `masters` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица настроек салона
DROP TABLE IF EXISTS `salon_settings`;
CREATE TABLE `salon_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица рабочего времени
DROP TABLE IF EXISTS `working_hours`;
CREATE TABLE `working_hours` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `day_of_week` int(11) NOT NULL COMMENT '0=Воскресенье, 1=Понедельник, ..., 6=Суббота',
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_working` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица отзывов
DROP TABLE IF EXISTS `reviews`;
CREATE TABLE `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_approved` (`is_approved`),
  KEY `idx_rating` (`rating`),
  KEY `user_id` (`user_id`),
  KEY `booking_id` (`booking_id`),
  CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Данные для таблиц
-- =====================================================

-- Добавляем администратора (пароль: admin123)
INSERT INTO `users` (`id`, `email`, `password`, `name`, `phone`, `role`, `birth_date`, `address`, `city`, `preferences`, `created_at`, `updated_at`, `is_active`) VALUES
(1, 'admin@nailstyle-sev.ru', '$2y$10$CwTycUXWue0Thq9StjUM0.BaQBDD2tJAGzZ3Y99H8YHdNrPKdwDJG', 'Администратор', '+7 (978) 599-59-83', 'admin', NULL, NULL, 'Севастополь', NULL, current_timestamp(), current_timestamp(), 1);

-- Добавляем тестового пользователя (пароль: 123456)
INSERT INTO `users` (`id`, `email`, `password`, `name`, `phone`, `role`, `birth_date`, `address`, `city`, `preferences`, `created_at`, `updated_at`, `is_active`) VALUES
(2, 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Тестовый Пользователь', '+7 (978) 123-45-67', 'client', '1990-01-01', 'ул. Тестовая, 1', 'Севастополь', NULL, current_timestamp(), current_timestamp(), 1);

-- Добавляем услуги
INSERT INTO `services` (`id`, `name`, `description`, `price`, `duration`, `category`, `is_active`, `created_at`) VALUES
(1, 'Классический маникюр', 'Профессиональный классический маникюр с покрытием', 1500, 60, 'Маникюр', 1, current_timestamp()),
(2, 'Гель-лак', 'Покрытие ногтей стойким гель-лаком', 2000, 90, 'Маникюр', 1, current_timestamp()),
(3, 'Дизайн ногтей', 'Художественный дизайн ногтей любой сложности', 3000, 120, 'Дизайн', 1, current_timestamp()),
(4, 'Наращивание', 'Наращивание ногтей гелем или акрилом', 3500, 150, 'Наращивание', 1, current_timestamp()),
(5, 'Укрепление ногтей', 'Укрепление натуральных ногтей биогелем', 2500, 90, 'Укрепление', 1, current_timestamp()),
(6, 'Педикюр', 'Классический педикюр с покрытием', 2000, 90, 'Педикюр', 1, current_timestamp());

-- Добавляем мастеров
INSERT INTO `masters` (`id`, `name`, `specialization`, `experience`, `description`, `avatar_url`, `is_active`, `created_at`) VALUES
(1, 'Анна Иванова', 'Маникюр, дизайн ногтей', 5, 'Опытный мастер маникюра и дизайна', NULL, 1, current_timestamp()),
(2, 'Мария Петрова', 'Наращивание, укрепление', 7, 'Специалист по наращиванию и укреплению ногтей', NULL, 1, current_timestamp()),
(3, 'Елена Сидорова', 'Педикюр, классический маникюр', 3, 'Мастер педикюра и классического маникюра', NULL, 1, current_timestamp());

-- Добавляем рабочее время (Понедельник-Суббота: 10:00-20:00, Воскресенье: выходной)
INSERT INTO `working_hours` (`id`, `day_of_week`, `start_time`, `end_time`, `is_working`) VALUES
(1, 1, '10:00:00', '20:00:00', 1),
(2, 2, '10:00:00', '20:00:00', 1),
(3, 3, '10:00:00', '20:00:00', 1),
(4, 4, '10:00:00', '20:00:00', 1),
(5, 5, '10:00:00', '20:00:00', 1),
(6, 6, '10:00:00', '20:00:00', 1),
(7, 0, '10:00:00', '18:00:00', 0);

-- Добавляем настройки салона
INSERT INTO `salon_settings` (`setting_key`, `setting_value`, `description`, `updated_at`) VALUES
('salon_name', 'Nail & Style', 'Название салона', current_timestamp()),
('salon_address', 'ул. Астана Кесаева, 9, г. Севастополь', 'Адрес салона', current_timestamp()),
('salon_phone', '+7 (978) 599-59-83', 'Телефон салона', current_timestamp()),
('salon_email', 'info@nailstyle-sev.ru', 'Email салона', current_timestamp()),
('booking_advance_days', '30', 'На сколько дней вперед можно записаться', current_timestamp()),
('min_booking_time', '60', 'Минимальное время до записи (в минутах)', current_timestamp()),
('work_start', '10:00', 'Начало рабочего дня', current_timestamp()),
('work_end', '20:00', 'Конец рабочего дня', current_timestamp()),
('slot_duration', '30', 'Продолжительность слота в минутах', current_timestamp());

-- Добавляем тестовую запись
INSERT INTO `bookings` (`id`, `user_id`, `service_id`, `master_id`, `appointment_date`, `appointment_time`, `status`, `client_name`, `client_phone`, `client_email`, `comment`, `price`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 1, '2024-12-20', '14:00:00', 'confirmed', 'Тестовый Пользователь', '+7 (978) 123-45-67', 'test@example.com', 'Тестовая запись', 1500, current_timestamp(), current_timestamp());

-- Добавляем тестовый отзыв
INSERT INTO `reviews` (`id`, `user_id`, `booking_id`, `rating`, `comment`, `is_approved`, `created_at`) VALUES
(1, 2, 1, 5, 'Отличный сервис! Очень довольна результатом!', 1, current_timestamp());

-- =====================================================
-- Настройки AUTO_INCREMENT
-- =====================================================
ALTER TABLE `users` AUTO_INCREMENT = 3;
ALTER TABLE `services` AUTO_INCREMENT = 7;
ALTER TABLE `masters` AUTO_INCREMENT = 4;
ALTER TABLE `bookings` AUTO_INCREMENT = 2;
ALTER TABLE `working_hours` AUTO_INCREMENT = 8;
ALTER TABLE `reviews` AUTO_INCREMENT = 2;

COMMIT;

-- =====================================================
-- Конец файла
-- Данные для входа:
-- Админ: admin@nailstyle-sev.ru / admin123
-- Пользователь: test@example.com / 123456
-- =====================================================
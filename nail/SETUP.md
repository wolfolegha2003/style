# 🚀 Установка и настройка бэкенда Nail & Style

## 📋 Требования

- **PHP 7.4+** с поддержкой PDO и MySQL
- **MySQL 5.7+** или **MariaDB 10.2+**
- **Apache** или **Nginx** веб-сервер
- **Composer** (опционально)

## ⚙️ Быстрая установка

### 1. Настройка базы данных

Создайте базу данных MySQL:

```sql
CREATE DATABASE nail_style CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. Настройка конфигурации

Отредактируйте файл `config.php` и укажите ваши настройки базы данных:

```php
// Настройки базы данных
define('DB_HOST', 'localhost');
define('DB_NAME', 'nail_style');
define('DB_USER', 'your_username');     // ваш пользователь MySQL
define('DB_PASS', 'your_password');     // ваш пароль MySQL
```

**⚠️ ВАЖНО**: Измените секретный ключ JWT в продакшене:

```php
define('JWT_SECRET', 'your-super-secret-jwt-key-change-in-production');
```

### 3. Установка схемы базы данных

Откройте в браузере: `http://your-domain.com/install.php`

Скрипт автоматически:
- Создаст все необходимые таблицы
- Добавит тестовые данные
- Создаст администратора

### 4. Готово!

✅ **Доступы по умолчанию:**
- **Админ**: `admin@nailstyle-sev.ru` / `admin123`
- **Главная**: `index.html`
- **API**: `/api/`

## 📁 Структура проекта

```
nail/
├── config.php              # Основная конфигурация
├── install.php             # Скрипт установки БД
├── index.html              # Главная страница
├── api-client.js           # JavaScript API клиент
├── .htaccess               # Настройки Apache
├── api/                    # API эндпоинты
│   ├── auth.php           # Аутентификация
│   ├── bookings.php       # Записи
│   └── admin.php          # Админка
└── uploads/               # Загруженные файлы
```

## 🔌 API Эндпоинты

### Аутентификация (`/api/auth.php`)

- `POST ?action=register` - Регистрация
- `POST ?action=login` - Вход
- `GET ?action=me` - Профиль пользователя
- `PUT ?action=profile` - Обновление профиля
- `PUT ?action=change-password` - Смена пароля

### Записи (`/api/bookings.php`)

- `GET ?action=my` - Мои записи
- `POST ?action=create` - Создать запись
- `GET ?action=details&id=X` - Детали записи
- `PUT ?action=update&id=X` - Обновить запись
- `DELETE ?action=cancel&id=X` - Отменить запись
- `GET ?action=available-slots&date=YYYY-MM-DD` - Доступные слоты
- `GET ?action=services` - Список услуг
- `GET ?action=masters` - Список мастеров

### Админка (`/api/admin.php`)

- `GET ?action=bookings` - Все записи
- `GET ?action=clients` - Клиенты
- `GET ?action=statistics&period=30` - Статистика
- `PUT ?action=booking-status` - Изменить статус записи
- `POST ?action=service` - Создать услугу
- `PUT ?action=service&id=X` - Обновить услугу
- `DELETE ?action=service&id=X` - Удалить услугу
- `POST ?action=master` - Добавить мастера
- `PUT ?action=master&id=X` - Обновить мастера
- `DELETE ?action=master&id=X` - Удалить мастера

## 🛠️ Настройка веб-сервера

### Apache

Убедитесь, что включены модули:
```apache
LoadModule rewrite_module modules/mod_rewrite.so
LoadModule headers_module modules/mod_headers.so
```

### Nginx

Добавьте в конфигурацию:
```nginx
location /api/ {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass 127.0.0.1:9000;
    fastcgi_index index.php;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
}
```

## 🔒 Безопасность

### В продакшене обязательно:

1. **Смените JWT секрет**:
   ```php
   define('JWT_SECRET', 'your-unique-secret-key-256-bits');
   ```

2. **Отключите отображение ошибок**:
   ```php
   error_reporting(0);
   ini_set('display_errors', 0);
   ```

3. **Настройте HTTPS**

4. **Ограничьте доступ к admin.php** только для админов

5. **Регулярно обновляйте пароли**

## 🐛 Устранение проблем

### Ошибка подключения к БД
- Проверьте настройки в `config.php`
- Убедитесь, что MySQL запущен
- Проверьте права пользователя БД

### API возвращает ошибки
- Проверьте логи PHP и Apache/Nginx
- Убедитесь, что включены нужные PHP расширения
- Проверьте CORS заголовки

### Файлы не загружаются
- Проверьте права на папку `uploads/`
- Увеличьте лимиты в PHP: `upload_max_filesize`, `post_max_size`

## 📞 Поддержка

При возникновении проблем:

1. Проверьте логи сервера
2. Убедитесь в правильности настроек
3. Проверьте требования к системе

---

**Сделано с ❤️ для Nail & Style** 💅✨
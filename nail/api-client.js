// ======================
// API CLIENT для PHP бэкенда
// ======================

const API_BASE_URL = './api';

// Утилиты для работы с токенами
const TokenManager = {
    get() {
        return localStorage.getItem('auth_token');
    },
    
    set(token) {
        localStorage.setItem('auth_token', token);
    },
    
    remove() {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user_data');
    },
    
    getHeaders() {
        const token = this.get();
        const headers = {
            'Content-Type': 'application/json'
        };
        
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }
        
        return headers;
    }
};

// Основной API клиент
const ApiClient = {
    async request(endpoint, options = {}) {
        const url = `${API_BASE_URL}/${endpoint}`;
        const config = {
            headers: TokenManager.getHeaders(),
            ...options
        };
        
        try {
            const response = await fetch(url, config);
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.error || 'Ошибка сервера');
            }
            
            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    },
    
    // Аутентификация
    auth: {
        async login(email, password) {
            const response = await ApiClient.request('auth.php?action=login', {
                method: 'POST',
                body: JSON.stringify({ email, password })
            });
            
            if (response.token) {
                TokenManager.set(response.token);
                localStorage.setItem('user_data', JSON.stringify(response.user));
            }
            
            return response;
        },
        
        async register(userData) {
            const response = await ApiClient.request('auth.php?action=register', {
                method: 'POST',
                body: JSON.stringify(userData)
            });
            
            if (response.token) {
                TokenManager.set(response.token);
                localStorage.setItem('user_data', JSON.stringify(response.user));
            }
            
            return response;
        },
        
        async getProfile() {
            return await ApiClient.request('auth.php?action=me');
        },
        
        async updateProfile(profileData) {
            return await ApiClient.request('auth.php?action=profile', {
                method: 'PUT',
                body: JSON.stringify(profileData)
            });
        },
        
        async changePassword(currentPassword, newPassword) {
            return await ApiClient.request('auth.php?action=change-password', {
                method: 'PUT',
                body: JSON.stringify({ currentPassword, newPassword })
            });
        },
        
        logout() {
            TokenManager.remove();
            window.location.href = 'index.html';
        }
    },
    
    // Записи
    bookings: {
        async getMy(params = {}) {
            const queryString = new URLSearchParams(params).toString();
            const endpoint = `bookings.php?action=my${queryString ? '&' + queryString : ''}`;
            return await ApiClient.request(endpoint);
        },
        
        async create(bookingData) {
            return await ApiClient.request('bookings.php?action=create', {
                method: 'POST',
                body: JSON.stringify(bookingData)
            });
        },
        
        async getDetails(id) {
            return await ApiClient.request(`bookings.php?action=details&id=${id}`);
        },
        
        async update(id, bookingData) {
            return await ApiClient.request(`bookings.php?action=update&id=${id}`, {
                method: 'PUT',
                body: JSON.stringify({ id, ...bookingData })
            });
        },
        
        async cancel(id) {
            return await ApiClient.request(`bookings.php?action=cancel&id=${id}`, {
                method: 'DELETE'
            });
        },
        
        async getAvailableSlots(date, masterId = null) {
            const params = new URLSearchParams({ date });
            if (masterId) params.append('master_id', masterId);
            return await ApiClient.request(`bookings.php?action=available-slots&${params}`);
        },
        
        async getServices() {
            return await ApiClient.request('bookings.php?action=services');
        },
        
        async getMasters() {
            return await ApiClient.request('bookings.php?action=masters');
        }
    },
    
    // Админка
    admin: {
        async getBookings(params = {}) {
            const queryString = new URLSearchParams(params).toString();
            const endpoint = `admin.php?action=bookings${queryString ? '&' + queryString : ''}`;
            return await ApiClient.request(endpoint);
        },
        
        async getClients(params = {}) {
            const queryString = new URLSearchParams(params).toString();
            const endpoint = `admin.php?action=clients${queryString ? '&' + queryString : ''}`;
            return await ApiClient.request(endpoint);
        },
        
        async getStatistics(period = 30) {
            return await ApiClient.request(`admin.php?action=statistics&period=${period}`);
        },
        
        async updateBookingStatus(bookingId, status) {
            return await ApiClient.request('admin.php?action=booking-status', {
                method: 'PUT',
                body: JSON.stringify({ booking_id: bookingId, status })
            });
        },
        
        async createService(serviceData) {
            return await ApiClient.request('admin.php?action=service', {
                method: 'POST',
                body: JSON.stringify(serviceData)
            });
        },
        
        async updateService(id, serviceData) {
            return await ApiClient.request(`admin.php?action=service&id=${id}`, {
                method: 'PUT',
                body: JSON.stringify({ id, ...serviceData })
            });
        },
        
        async deleteService(id) {
            return await ApiClient.request(`admin.php?action=service&id=${id}`, {
                method: 'DELETE'
            });
        }
    }
};

// Утилиты для работы с пользователем
const UserManager = {
    getCurrentUser() {
        const userData = localStorage.getItem('user_data');
        return userData ? JSON.parse(userData) : null;
    },
    
    isLoggedIn() {
        return !!TokenManager.get() && !!this.getCurrentUser();
    },
    
    isAdmin() {
        const user = this.getCurrentUser();
        return user && user.role === 'admin';
    },
    
    async refreshUserData() {
        if (this.isLoggedIn()) {
            try {
                const response = await ApiClient.auth.getProfile();
                localStorage.setItem('user_data', JSON.stringify(response.user));
                return response.user;
            } catch (error) {
                console.error('Ошибка обновления данных пользователя:', error);
                ApiClient.auth.logout();
                return null;
            }
        }
        return null;
    }
};

// Интеграция с существующими функциями
function updateAuthFunctions() {
    // Обновляем функцию входа
    window.handleLogin = async function(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        const email = formData.get('email');
        const password = formData.get('password');
        
        try {
            const response = await ApiClient.auth.login(email, password);
            
            // Закрываем модальное окно
            const modal = bootstrap.Modal.getInstance(document.getElementById('loginModal'));
            modal.hide();
            
            // Обновляем UI
            updateUIForLoggedInUser(response.user);
            showSuccessToast('Успешный вход в систему!');
            
        } catch (error) {
            showErrorToast(error.message || 'Ошибка входа в систему');
        }
    };
    
    // Обновляем функцию регистрации
    window.handleRegistration = async function(event) {
        event.preventDefault();
        
        const form = event.target;
        const formData = new FormData(form);
        
        const userData = {
            name: formData.get('name'),
            email: formData.get('email'),
            password: formData.get('password'),
            phone: formData.get('phone')
        };
        
        // Проверяем пароли
        const confirmPassword = formData.get('confirmPassword');
        if (userData.password !== confirmPassword) {
            showErrorToast('Пароли не совпадают');
            return;
        }
        
        try {
            const response = await ApiClient.auth.register(userData);
            
            // Закрываем модальное окно
            const modal = bootstrap.Modal.getInstance(document.getElementById('registerModal'));
            modal.hide();
            
            // Обновляем UI
            updateUIForLoggedInUser(response.user);
            showSuccessToast('Регистрация прошла успешно!');
            
        } catch (error) {
            showErrorToast(error.message || 'Ошибка регистрации');
        }
    };
    
    // Обновляем функцию создания записи
    window.handleBookingSubmit = async function(event) {
        event.preventDefault();
        
        if (!UserManager.isLoggedIn()) {
            showErrorToast('Необходимо войти в систему для записи');
            return;
        }
        
        const form = event.target;
        const formData = new FormData(form);
        
        const bookingData = {
            service_id: formData.get('service'),
            master_id: formData.get('master') || null,
            appointment_date: formData.get('date'),
            appointment_time: formData.get('time'),
            comment: formData.get('message')
        };
        
        try {
            const response = await ApiClient.bookings.create(bookingData);
            
            // Закрываем модальное окно
            const modal = bootstrap.Modal.getInstance(document.getElementById('bookingModal'));
            modal.hide();
            
            // Очищаем форму
            form.reset();
            
            showSuccessToast('Запись успешно создана!');
            
        } catch (error) {
            showErrorToast(error.message || 'Ошибка создания записи');
        }
    };
    
    // Функция выхода
    window.logout = function() {
        ApiClient.auth.logout();
    };
}

// Функции для отображения уведомлений
function showSuccessToast(message) {
    showToast(message, 'success');
}

function showErrorToast(message) {
    showToast(message, 'error');
}

function showToast(message, type = 'info') {
    // Создаем контейнер для тостов если его нет
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
    }
    
    // Создаем тост
    const toastId = 'toast_' + Date.now();
    const bgClass = type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : 'bg-primary';
    
    const toastHTML = `
        <div id="${toastId}" class="toast ${bgClass} text-white" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('afterbegin', toastHTML);
    
    // Показываем тост
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, { delay: 5000 });
    toast.show();
    
    // Удаляем элемент после скрытия
    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    updateAuthFunctions();
    
    // Проверяем, авторизован ли пользователь
    if (UserManager.isLoggedIn()) {
        const user = UserManager.getCurrentUser();
        updateUIForLoggedInUser(user);
        
        // Обновляем данные пользователя
        UserManager.refreshUserData();
    }
});
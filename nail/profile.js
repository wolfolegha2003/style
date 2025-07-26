// ======================
// PROFILE PAGE FUNCTIONS
// ======================

// Mock data for profile
let currentUserProfile = null;
let userOrders = [];

// Initialize profile page
document.addEventListener('DOMContentLoaded', function() {
    loadUserProfile();
    setupEventListeners();
    initializeDateInputs();
});

// ======================
// USER PROFILE MANAGEMENT
// ======================
function loadUserProfile() {
    // Get current user from localStorage or sessionStorage
    const savedUser = localStorage.getItem('currentUser') || sessionStorage.getItem('currentUser');
    
    if (!savedUser) {
        // Redirect to login if no user found
        window.location.href = 'index.html';
        return;
    }
    
    currentUserProfile = JSON.parse(savedUser);
    
    // Load profile data
    updateProfileHeader();
    loadUserOrders();
    loadPersonalData();
    
    // Show admin tab if user is admin
    if (currentUserProfile.role === 'admin') {
        document.querySelector('.admin-only').style.display = 'block';
        loadAdminData();
    }
}

function updateProfileHeader() {
    const displayName = currentUserProfile.displayName || 
                       currentUserProfile.name || 
                       currentUserProfile.email.split('@')[0];
    
    document.getElementById('profileName').textContent = displayName;
    document.getElementById('profileRole').textContent = 
        currentUserProfile.role === 'admin' ? 'Администратор' : 'Клиент';
    
    // Update stats
    document.getElementById('totalOrders').textContent = userOrders.length;
    document.getElementById('memberSince').textContent = new Date().getFullYear();
    document.getElementById('profileStatus').textContent = 
        currentUserProfile.role === 'admin' ? 'Персонал' : 'Активен';
}

// ======================
// ORDERS MANAGEMENT
// ======================
function loadUserOrders() {
    // Load orders from mockBookings based on user email
    if (currentUserProfile.role === 'admin') {
        userOrders = [...mockBookings]; // Admin sees all orders
    } else {
        userOrders = mockBookings.filter(order => 
            order.clientEmail === currentUserProfile.email
        );
    }
    
    displayOrders(userOrders);
}

function displayOrders(orders) {
    const container = document.getElementById('ordersContainer');
    
    if (orders.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="bi bi-bag-x"></i>
                <h4>Заказов пока нет</h4>
                <p>Создайте свой первый заказ, нажав кнопку ниже</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = orders.map(order => `
        <div class="order-card" data-status="${order.status}">
            <div class="order-header">
                <div>
                    <h5 class="order-title">${order.service}</h5>
                    <div class="order-id">Заказ #${order.id}</div>
                </div>
                <span class="order-status ${order.status}">${getStatusText(order.status)}</span>
            </div>
            
            <div class="order-details">
                <div class="order-detail">
                    <i class="bi bi-calendar"></i>
                    <span>${formatDate(order.date)}</span>
                </div>
                <div class="order-detail">
                    <i class="bi bi-clock"></i>
                    <span>${order.time}</span>
                </div>
                <div class="order-detail">
                    <i class="bi bi-person"></i>
                    <span>${order.clientName}</span>
                </div>
                <div class="order-detail">
                    <i class="bi bi-telephone"></i>
                    <span>${order.clientPhone}</span>
                </div>
            </div>
            
            ${order.comment ? `
                <div class="order-comment mt-2">
                    <small class="text-muted">
                        <i class="bi bi-chat-dots"></i> ${order.comment}
                    </small>
                </div>
            ` : ''}
            
            <div class="order-actions">
                ${order.status === 'pending' ? `
                    <button class="btn btn-sm btn-outline-primary" onclick="editOrder(${order.id})">
                        <i class="bi bi-pencil"></i> Изменить
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="cancelOrder(${order.id})">
                        <i class="bi bi-x-circle"></i> Отменить
                    </button>
                ` : ''}
                
                ${currentUserProfile.role === 'admin' && order.status === 'pending' ? `
                    <button class="btn btn-sm btn-success" onclick="confirmOrder(${order.id})">
                        <i class="bi bi-check-circle"></i> Подтвердить
                    </button>
                ` : ''}
                
                <button class="btn btn-sm btn-outline-info" onclick="viewOrderDetails(${order.id})">
                    <i class="bi bi-eye"></i> Подробнее
                </button>
            </div>
        </div>
    `).join('');
}

function filterOrders(status) {
    const filtered = status === 'all' ? userOrders : 
                    userOrders.filter(order => order.status === status);
    
    displayOrders(filtered);
    
    // Update active filter button
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
}

// ======================
// PERSONAL DATA MANAGEMENT
// ======================
function loadPersonalData() {
    // Load saved personal data or set defaults
    const personalData = JSON.parse(localStorage.getItem('personalData_' + currentUserProfile.email)) || {};
    
    document.getElementById('firstName').value = personalData.firstName || '';
    document.getElementById('lastName').value = personalData.lastName || '';
    document.getElementById('email').value = currentUserProfile.email;
    document.getElementById('phone').value = personalData.phone || '';
    document.getElementById('birthDate').value = personalData.birthDate || '';
    document.getElementById('gender').value = personalData.gender || '';
    document.getElementById('preferences').value = personalData.preferences || '';
    document.getElementById('address').value = personalData.address || '';
    document.getElementById('city').value = personalData.city || 'Севастополь';
    
    // Update display elements
    updatePersonalDataDisplay(personalData);
}

function updatePersonalDataDisplay(data) {
    document.getElementById('displayBirthDate').textContent = 
        data.birthDate ? formatDate(data.birthDate) : 'Не указан';
    
    document.getElementById('registrationDate').textContent = 
        formatDate(currentUserProfile.loginTime || '2024-01-01');
    
    // Update last visit (mock data)
    document.getElementById('lastVisit').textContent = 
        userOrders.length > 0 ? formatDate(userOrders[userOrders.length - 1].date) : 'Не было';
}

function savePersonalData() {
    const personalData = {
        firstName: document.getElementById('firstName').value,
        lastName: document.getElementById('lastName').value,
        phone: document.getElementById('phone').value,
        birthDate: document.getElementById('birthDate').value,
        gender: document.getElementById('gender').value,
        preferences: document.getElementById('preferences').value,
        address: document.getElementById('address').value,
        city: document.getElementById('city').value
    };
    
    localStorage.setItem('personalData_' + currentUserProfile.email, JSON.stringify(personalData));
    updatePersonalDataDisplay(personalData);
    showToast('Личные данные сохранены!');
}

// ======================
// ADMIN FUNCTIONS
// ======================
function loadAdminData() {
    updateAdminStats();
    loadAdminOrders();
}

function updateAdminStats() {
    const allOrders = mockBookings;
    const pendingOrders = allOrders.filter(order => order.status === 'pending');
    const todayOrders = allOrders.filter(order => 
        new Date(order.date).toDateString() === new Date().toDateString()
    );
    
    document.getElementById('totalClients').textContent = mockClients.length;
    document.getElementById('pendingOrders').textContent = pendingOrders.length;
    document.getElementById('todayOrders').textContent = todayOrders.length;
    document.getElementById('monthRevenue').textContent = '125,000₽'; // Mock data
}

function loadAdminOrders() {
    const tableBody = document.getElementById('adminOrdersTable');
    if (!tableBody) return;
    
    tableBody.innerHTML = mockBookings.map(order => `
        <tr>
            <td>#${order.id}</td>
            <td>${order.clientName}</td>
            <td>${order.service}</td>
            <td>${formatDate(order.date)}</td>
            <td>${order.time}</td>
            <td>
                <span class="badge bg-${getStatusColor(order.status)}">
                    ${getStatusText(order.status)}
                </span>
            </td>
            <td>
                <div class="btn-group btn-group-sm">
                    ${order.status === 'pending' ? `
                        <button class="btn btn-outline-success" onclick="confirmOrder(${order.id})" title="Подтвердить">
                            <i class="bi bi-check"></i>
                        </button>
                        <button class="btn btn-outline-danger" onclick="cancelOrder(${order.id})" title="Отменить">
                            <i class="bi bi-x"></i>
                        </button>
                    ` : ''}
                    <button class="btn btn-outline-primary" onclick="viewOrderDetails(${order.id})" title="Подробнее">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

// ======================
// ORDER ACTIONS
// ======================
function editOrder(orderId) {
    const order = mockBookings.find(o => o.id === orderId);
    if (!order) return;
    
    // Fill new order modal with existing data
    document.getElementById('orderService').value = order.service;
    document.getElementById('orderDate').value = order.date;
    document.getElementById('orderTime').value = order.time;
    document.getElementById('orderComment').value = order.comment || '';
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('newOrderModal'));
    modal.show();
    
    // Store original order ID for update
    document.getElementById('newOrderForm').dataset.editingId = orderId;
}

function cancelOrder(orderId) {
    if (confirm('Вы уверены, что хотите отменить этот заказ?')) {
        const order = mockBookings.find(o => o.id === orderId);
        if (order) {
            order.status = 'cancelled';
            loadUserOrders();
            if (currentUserProfile.role === 'admin') {
                loadAdminOrders();
                updateAdminStats();
            }
            showToast('Заказ отменен');
        }
    }
}

function confirmOrder(orderId) {
    const order = mockBookings.find(o => o.id === orderId);
    if (order) {
        order.status = 'confirmed';
        loadUserOrders();
        if (currentUserProfile.role === 'admin') {
            loadAdminOrders();
            updateAdminStats();
        }
        showToast('Заказ подтвержден');
    }
}

function viewOrderDetails(orderId) {
    const order = mockBookings.find(o => o.id === orderId);
    if (!order) return;
    
    alert(`
Заказ #${order.id}
Услуга: ${order.service}
Клиент: ${order.clientName}
Телефон: ${order.clientPhone}
Дата: ${formatDate(order.date)}
Время: ${order.time}
Статус: ${getStatusText(order.status)}
${order.comment ? `Комментарий: ${order.comment}` : ''}
    `);
}

function createNewOrder() {
    const form = document.getElementById('newOrderForm');
    const editingId = form.dataset.editingId;
    
    const orderData = {
        service: document.getElementById('orderService').value,
        date: document.getElementById('orderDate').value,
        time: document.getElementById('orderTime').value,
        master: document.getElementById('orderMaster').value,
        comment: document.getElementById('orderComment').value
    };
    
    if (editingId) {
        // Update existing order
        const order = mockBookings.find(o => o.id == editingId);
        if (order) {
            Object.assign(order, orderData);
            showToast('Заказ обновлен!');
        }
        delete form.dataset.editingId;
    } else {
        // Create new order
        const newOrder = {
            id: mockBookings.length + 1,
            clientName: currentUserProfile.name || 'Клиент',
            clientPhone: currentUserProfile.phone || '+7 (978) 000-00-00',
            clientEmail: currentUserProfile.email,
            status: 'pending',
            created: new Date().toISOString(),
            ...orderData
        };
        
        mockBookings.push(newOrder);
        showToast('Заказ создан!');
    }
    
    // Close modal and refresh data
    const modal = bootstrap.Modal.getInstance(document.getElementById('newOrderModal'));
    modal.hide();
    form.reset();
    
    loadUserOrders();
    if (currentUserProfile.role === 'admin') {
        loadAdminOrders();
        updateAdminStats();
    }
}

// ======================
// PROFILE EDITING
// ======================
function saveProfileChanges() {
    const displayName = document.getElementById('displayName').value;
    const statusText = document.getElementById('statusText').value;
    
    currentUserProfile.displayName = displayName;
    currentUserProfile.statusText = statusText;
    
    // Save to storage
    if (localStorage.getItem('currentUser')) {
        localStorage.setItem('currentUser', JSON.stringify(currentUserProfile));
    } else {
        sessionStorage.setItem('currentUser', JSON.stringify(currentUserProfile));
    }
    
    updateProfileHeader();
    
    // Close modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('editProfileModal'));
    modal.hide();
    
    showToast('Профиль обновлен!');
}

function changePassword() {
    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    if (newPassword !== confirmPassword) {
        alert('Пароли не совпадают!');
        return;
    }
    
    if (newPassword.length < 6) {
        alert('Пароль должен содержать минимум 6 символов!');
        return;
    }
    
    // In real app, this would make API call
    showToast('Пароль изменен!');
    
    // Close modal and reset form
    const modal = bootstrap.Modal.getInstance(document.getElementById('changePasswordModal'));
    modal.hide();
    document.getElementById('changePasswordForm').reset();
}

// ======================
// EVENT LISTENERS
// ======================
function setupEventListeners() {
    // Personal data form
    const personalForm = document.getElementById('personalDataForm');
    if (personalForm) {
        personalForm.addEventListener('submit', function(e) {
            e.preventDefault();
            savePersonalData();
        });
    }
    
    // Filter buttons
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            filterOrders(this.dataset.filter);
        });
    });
    
    // Settings switches
    document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            localStorage.setItem('setting_' + this.id, this.checked);
        });
    });
    
    // Theme selection
    document.querySelectorAll('input[name="theme"]').forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                const theme = this.value;
                localStorage.setItem('theme', theme);
                applyTheme(theme);
                updateThemeToggleButton(theme);
                showToast(`Тема "${getThemeName(theme)}" применена!`);
            }
        });
    });
    
    // Load saved settings
    loadSavedSettings();
}

function loadSavedSettings() {
    // Load notification settings
    document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
        const saved = localStorage.getItem('setting_' + checkbox.id);
        if (saved !== null) {
            checkbox.checked = saved === 'true';
        }
    });
    
    // Load and apply theme setting
    const savedTheme = localStorage.getItem('theme') || 'dark';
    
    // Update radio buttons to match saved theme
    document.querySelectorAll('input[name="theme"]').forEach(radio => {
        radio.checked = (radio.value === savedTheme);
    });
    
    applyTheme(savedTheme);
}

function initializeDateInputs() {
    // Set minimum date for order date to today
    const orderDateInput = document.getElementById('orderDate');
    if (orderDateInput) {
        const today = new Date().toISOString().split('T')[0];
        orderDateInput.min = today;
    }
}

// ======================
// UTILITY FUNCTIONS
// ======================
function showToast(message) {
    const toastBody = document.querySelector('#successToast .toast-body');
    if (toastBody) {
        toastBody.textContent = message;
        const toast = new bootstrap.Toast(document.getElementById('successToast'));
        toast.show();
    }
}

function logout() {
    localStorage.removeItem('currentUser');
    sessionStorage.removeItem('currentUser');
    window.location.href = 'index.html';
}

// Add profile link to main site
function addProfileLink() {
    const currentUser = localStorage.getItem('currentUser') || sessionStorage.getItem('currentUser');
    if (currentUser) {
        const userObj = JSON.parse(currentUser);
        const loginBtn = document.querySelector('[data-bs-target="#loginModal"], .dropdown .btn');
        if (loginBtn && !loginBtn.parentNode.classList.contains('dropdown')) {
            // Update button to show profile link
            loginBtn.innerHTML = `<i class="bi bi-person-check"></i> ${userObj.name || 'Профиль'}`;
            loginBtn.onclick = () => window.location.href = 'profile.html';
            loginBtn.removeAttribute('data-bs-toggle');
            loginBtn.removeAttribute('data-bs-target');
        }
    }
}

// Auto-call on main page
if (window.location.pathname.includes('index.html') || window.location.pathname === '/') {
    document.addEventListener('DOMContentLoaded', addProfileLink);
}

// ======================
// THEME MANAGEMENT
// ======================
function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    
    // Save to localStorage
    localStorage.setItem('theme', theme);
    
    // Update theme toggle button if exists
    updateThemeToggleButton(theme);
    
    // Trigger custom event for other components
    window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme } }));
}

function updateThemeToggleButton(theme) {
    const switchElement = document.querySelector('.theme-switch');
    const icon = document.querySelector('.theme-icon');
    
    if (switchElement && icon) {
        if (theme === 'light') {
            switchElement.classList.add('light');
            icon.textContent = '☀️';
        } else {
            switchElement.classList.remove('light');
            icon.textContent = '🌙';
        }
    }
}

function getThemeName(theme) {
    const names = {
        'light': 'Светлая',
        'dark': 'Темная'
    };
    return names[theme] || 'Неизвестная';
}

function getThemeIcon(theme) {
    const icons = {
        'light': '☀️',
        'dark': '🌙'
    };
    return icons[theme] || '☀️';
}

function toggleTheme() {
    const current = localStorage.getItem('theme') || 'dark';
    const nextTheme = current === 'light' ? 'dark' : 'light';
    
    applyTheme(nextTheme);
    
    // Update radio buttons if on settings page
    document.querySelectorAll('input[name="theme"]').forEach(radio => {
        radio.checked = (radio.value === nextTheme);
    });
    
    showToast(`Переключено на тему "${getThemeName(nextTheme)}"`);
}

// Initialize theme on page load
document.addEventListener('DOMContentLoaded', function() {
    const savedTheme = localStorage.getItem('theme') || 'dark';
    applyTheme(savedTheme);
});


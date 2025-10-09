// ======================
// DOCUMENT READY
// ======================
document.addEventListener('DOMContentLoaded', function() {
    initializeAOS();
    initializeScrollEffects();
    initializeCounters();
    initializeGallery();
    initializeBookingForm();
    initializeNavbar();
    initializeBackToTop();
    initializeTooltips();
    initializeTypewriter();
    initializeAuthSystem();
    initializeAdminPanel();
});

// ======================
// AOS INITIALIZATION
// ======================
function initializeAOS() {
    AOS.init({
        duration: 1000,
        easing: 'ease-in-out',
        once: true,
        offset: 100
    });
}

// ======================
// SCROLL EFFECTS
// ======================
function initializeScrollEffects() {
    window.addEventListener('scroll', function() {
        const navbar = document.getElementById('mainNavbar');
        const scrolled = window.pageYOffset;
        
        // Navbar background opacity
        if (scrolled > 50) {
            navbar.style.background = 'rgba(255, 255, 255, 0.98)';
            navbar.style.boxShadow = '0 2px 20px rgba(0,0,0,0.15)';
        } else {
            navbar.style.background = 'rgba(255, 255, 255, 0.95)';
            navbar.style.boxShadow = '0 2px 20px rgba(0,0,0,0.1)';
        }
        
        // Parallax effect for hero section
        const hero = document.querySelector('.hero-section');
        if (hero) {
            hero.style.transform = `translateY(${scrolled * 0.5}px)`;
        }
        
        // Fade in animations
        const fadeElements = document.querySelectorAll('.fade-in');
        fadeElements.forEach(element => {
            const elementTop = element.getBoundingClientRect().top;
            const elementVisible = 150;
            
            if (elementTop < window.innerHeight - elementVisible) {
                element.classList.add('visible');
            }
        });
    });
}

// ======================
// ANIMATED COUNTERS
// ======================
function initializeCounters() {
    const counters = document.querySelectorAll('.counter');
    let counterObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const counter = entry.target;
                const target = parseInt(counter.getAttribute('data-target'));
                const duration = 2000; // 2 seconds
                const step = target / (duration / 16); // 60fps
                let current = 0;
                
                const timer = setInterval(() => {
                    current += step;
                    if (current >= target) {
                        counter.textContent = target;
                        clearInterval(timer);
                    } else {
                        counter.textContent = Math.floor(current);
                    }
                }, 16);
                
                observer.unobserve(counter);
            }
        });
    });
    
    counters.forEach(counter => {
        counterObserver.observe(counter);
    });
}

// ======================
// GALLERY FUNCTIONALITY
// ======================
function initializeGallery() {
    // Sample gallery data
    const galleryData = [
        {
            id: 1,
            category: 'manicure',
            image: 'https://images.unsplash.com/photo-1604654894610-df63bc536371?w=400&h=300&fit=crop',
            title: 'Классический маникюр'
        },
        {
            id: 2,
            category: 'design',
            image: 'https://images.unsplash.com/photo-1610992015732-2449b76344bc?w=400&h=300&fit=crop',
            title: 'Художественный дизайн'
        },
        {
            id: 3,
            category: 'extension',
            image: 'https://images.unsplash.com/photo-1596462502278-27bfdc403348?w=400&h=300&fit=crop',
            title: 'Наращивание ногтей'
        },
        {
            id: 4,
            category: 'manicure',
            image: 'https://images.unsplash.com/photo-1607779097040-26e80aa78e66?w=400&h=300&fit=crop',
            title: 'Гель-лак покрытие'
        },
        {
            id: 5,
            category: 'design',
            image: 'https://images.unsplash.com/photo-1608247448319-2f71f0c1b6a0?w=400&h=300&fit=crop',
            title: 'Французский маникюр'
        },
        {
            id: 6,
            category: 'extension',
            image: 'https://images.unsplash.com/photo-1599948180883-96e75b3c9f6e?w=400&h=300&fit=crop',
            title: 'Коррекция формы'
        },
        {
            id: 7,
            category: 'design',
            image: 'https://images.unsplash.com/photo-1584464491033-06628f3a6b7b?w=400&h=300&fit=crop',
            title: 'Градиентный дизайн'
        },
        {
            id: 8,
            category: 'manicure',
            image: 'https://images.unsplash.com/photo-1609204095633-b6c8e4c5e8a7?w=400&h=300&fit=crop',
            title: 'SPA маникюр'
        }
    ];
    
    function renderGallery(filter = 'all') {
        const galleryGrid = document.getElementById('galleryGrid');
        const filteredData = filter === 'all' ? galleryData : galleryData.filter(item => item.category === filter);
        
        galleryGrid.innerHTML = filteredData.map(item => `
            <div class="col-lg-3 col-md-4 col-sm-6 gallery-item" data-aos="zoom-in" data-aos-delay="${item.id * 100}">
                <div class="position-relative">
                    <img src="${item.image}" alt="${item.title}" class="img-fluid">
                    <div class="gallery-overlay">
                        <i class="bi bi-eye"></i>
                    </div>
                </div>
            </div>
        `).join('');
        
        // Reinitialize AOS for new elements
        AOS.refresh();
    }
    
    // Initialize gallery
    renderGallery();
    
    // Filter functionality
    const filterButtons = document.querySelectorAll('.gallery-filter .nav-link');
    filterButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all buttons
            filterButtons.forEach(btn => btn.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Filter gallery
            const filter = this.getAttribute('data-filter');
            renderGallery(filter);
        });
    });
}

// ======================
// BOOKING FORM
// ======================
function initializeBookingForm() {
    const bookingForm = document.getElementById('bookingForm');
    const serviceButtons = document.querySelectorAll('[data-service]');
    const serviceSelect = document.getElementById('serviceSelect');
    
    // Pre-fill service when button is clicked
    serviceButtons.forEach(button => {
        button.addEventListener('click', function() {
            const service = this.getAttribute('data-service');
            if (serviceSelect) {
                serviceSelect.value = service;
            }
        });
    });
    
    // Set minimum date to today
    const dateInput = document.getElementById('appointmentDate');
    if (dateInput) {
        const today = new Date().toISOString().split('T')[0];
        dateInput.min = today;
    }
    
    // Form submission
    if (bookingForm) {
        bookingForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (this.checkValidity()) {
                // Create new booking
                const newBooking = {
                    id: mockBookings.length + 1,
                    clientName: document.getElementById('clientName').value,
                    clientPhone: document.getElementById('clientPhone').value,
                    clientEmail: document.getElementById('clientEmail').value || '',
                    service: document.getElementById('serviceSelect').value,
                    date: document.getElementById('appointmentDate').value,
                    time: document.getElementById('appointmentTime').value,
                    status: 'pending',
                    created: new Date().toISOString(),
                    comment: document.getElementById('clientComment').value || ''
                };
                
                // Add to mock data
                mockBookings.push(newBooking);
                
                // Simulate form submission
                const submitBtn = this.querySelector('[type="submit"]');
                const originalText = submitBtn.textContent;
                
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Отправка...';
                
                setTimeout(() => {
                    // Hide modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('bookingModal'));
                    modal.hide();
                    
                    // Show success toast
                    showSuccessToast('Ваша заявка отправлена! Мы свяжемся с вами в ближайшее время.');
                    
                    // Reset form
                    this.reset();
                    this.classList.remove('was-validated');
                    
                    // Reset button
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                    
                    // Update admin data if panel is open
                    if (document.getElementById('adminModal').classList.contains('show')) {
                        loadBookingsData();
                    }
                }, 2000);
            }
            
            this.classList.add('was-validated');
        });
    }
}

// ======================
// NAVBAR FUNCTIONALITY
// ======================
function initializeNavbar() {
    // Smooth scrolling for navigation links
    const navLinks = document.querySelectorAll('.nav-link[href^="#"]');
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            const targetSection = document.querySelector(targetId);
            
            if (targetSection) {
                const offsetTop = targetSection.offsetTop - 80; // Account for fixed navbar
                
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
                
                // Close mobile menu if open
                const navbarCollapse = document.querySelector('.navbar-collapse');
                if (navbarCollapse.classList.contains('show')) {
                    const bsCollapse = new bootstrap.Collapse(navbarCollapse);
                    bsCollapse.hide();
                }
            }
        });
    });
    
    // Active link highlighting
    window.addEventListener('scroll', function() {
        const sections = document.querySelectorAll('section[id]');
        const scrollPos = window.pageYOffset + 100;
        
        sections.forEach(section => {
            const top = section.offsetTop;
            const height = section.offsetHeight;
            const id = section.getAttribute('id');
            const navLink = document.querySelector(`.nav-link[href="#${id}"]`);
            
            if (scrollPos >= top && scrollPos < top + height) {
                navLinks.forEach(link => link.classList.remove('active'));
                if (navLink) navLink.classList.add('active');
            }
        });
    });
}

// ======================
// BACK TO TOP BUTTON
// ======================
function initializeBackToTop() {
    const backToTopBtn = document.getElementById('backToTop');
    
    if (backToTopBtn) {
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTopBtn.style.display = 'flex';
                backToTopBtn.style.opacity = '1';
            } else {
                backToTopBtn.style.opacity = '0';
                setTimeout(() => {
                    if (window.pageYOffset <= 300) {
                        backToTopBtn.style.display = 'none';
                    }
                }, 300);
            }
        });
        
        backToTopBtn.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
}

// ======================
// TOOLTIPS
// ======================
function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// ======================
// TYPEWRITER EFFECT
// ======================
function initializeTypewriter() {
    const textElement = document.querySelector('.hero-section h1 span');
    if (textElement) {
        const words = ['искусство', 'страсть', 'призвание', 'творчество'];
        let wordIndex = 0;
        let letterIndex = 0;
        let currentWord = '';
        let isDeleting = false;
        
        // Убедимся, что элемент имеет градиентный класс
        textElement.className = 'typewriter-gradient';
        
        function typeWriter() {
            const fullWord = words[wordIndex];
            
            if (isDeleting) {
                currentWord = fullWord.substring(0, letterIndex - 1);
                letterIndex--;
            } else {
                currentWord = fullWord.substring(0, letterIndex + 1);
                letterIndex++;
            }
            
            textElement.textContent = currentWord;
            
            let typeSpeed = 100;
            
            if (isDeleting) {
                typeSpeed /= 2;
            }
            
            if (!isDeleting && letterIndex === fullWord.length) {
                typeSpeed = 2000; // Pause at end
                isDeleting = true;
            } else if (isDeleting && letterIndex === 0) {
                isDeleting = false;
                wordIndex++;
                typeSpeed = 500;
                
                if (wordIndex === words.length) {
                    wordIndex = 0;
                }
            }
            
            setTimeout(typeWriter, typeSpeed);
        }
        
        // Start typewriter effect after a delay
        setTimeout(typeWriter, 2000);
    }
}

// ======================
// CAROUSEL AUTO-PLAY PAUSE ON HOVER
// ======================
document.addEventListener('DOMContentLoaded', function() {
    const carousels = document.querySelectorAll('.carousel');
    carousels.forEach(carousel => {
        carousel.addEventListener('mouseenter', function() {
            const carouselInstance = bootstrap.Carousel.getInstance(this);
            if (carouselInstance) {
                carouselInstance.pause();
            }
        });
        
        carousel.addEventListener('mouseleave', function() {
            const carouselInstance = bootstrap.Carousel.getInstance(this);
            if (carouselInstance) {
                carouselInstance.cycle();
            }
        });
    });
});

// ======================
// LOADING SCREEN (Optional)
// ======================
window.addEventListener('load', function() {
    const loader = document.querySelector('.loader');
    if (loader) {
        loader.style.opacity = '0';
        setTimeout(() => {
            loader.style.display = 'none';
        }, 500);
    }
});

// ======================
// PERFORMANCE OPTIMIZATION
// ======================
// Lazy loading for images
if ('IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                imageObserver.unobserve(img);
            }
        });
    });
    
    const lazyImages = document.querySelectorAll('img[data-src]');
    lazyImages.forEach(img => imageObserver.observe(img));
}

// ======================
// ACCESSIBILITY IMPROVEMENTS
// ======================
document.addEventListener('keydown', function(e) {
    // ESC key to close modals
    if (e.key === 'Escape') {
        const openModals = document.querySelectorAll('.modal.show');
        openModals.forEach(modal => {
            const modalInstance = bootstrap.Modal.getInstance(modal);
            if (modalInstance) {
                modalInstance.hide();
            }
        });
    }
});

// Focus management for modals
document.addEventListener('shown.bs.modal', function(e) {
    const modal = e.target;
    const firstInput = modal.querySelector('input, select, textarea, button');
    if (firstInput) {
        firstInput.focus();
    }
});

// ======================
// AUTHENTICATION SYSTEM
// ======================
let currentUser = null;
let mockBookings = [
    {
        id: 1,
        clientName: 'Анна Петрова',
        clientPhone: '+7 (978) 111-22-33',
        clientEmail: 'anna@email.com',
        service: 'Гель-лак',
        date: '2024-01-25',
        time: '14:00',
        status: 'pending',
        created: '2024-01-20'
    },
    {
        id: 2,
        clientName: 'Мария Иванова',
        clientPhone: '+7 (978) 222-33-44',
        clientEmail: 'maria@email.com',
        service: 'Классический маникюр',
        date: '2024-01-26',
        time: '11:00',
        status: 'confirmed',
        created: '2024-01-21'
    },
    {
        id: 3,
        clientName: 'Елена Сидорова',
        clientPhone: '+7 (978) 333-44-55',
        clientEmail: 'elena@email.com',
        service: 'Дизайн ногтей',
        date: '2024-01-27',
        time: '16:30',
        status: 'pending',
        created: '2024-01-22'
    }
];

let mockClients = [
    {
        id: 1,
        name: 'Анна Петрова',
        email: 'anna@email.com',
        phone: '+7 (978) 111-22-33',
        registrationDate: '2023-12-01',
        visits: 12,
        status: 'VIP'
    },
    {
        id: 2,
        name: 'Мария Иванова',
        email: 'maria@email.com',
        phone: '+7 (978) 222-33-44',
        registrationDate: '2024-01-15',
        visits: 3,
        status: 'Активный'
    }
];

function initializeAuthSystem() {
    // Login form
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleLogin();
        });
    }

    // Register form
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleRegistration();
        });
    }

    // Password toggle
    const togglePassword = document.getElementById('togglePassword');
    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const passwordInput = document.getElementById('loginPassword');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                passwordInput.type = 'password';
                icon.className = 'bi bi-eye';
            }
        });
    }

    // Password confirmation validation
    const regPasswordConfirm = document.getElementById('regPasswordConfirm');
    if (regPasswordConfirm) {
        regPasswordConfirm.addEventListener('input', function() {
            const password = document.getElementById('regPassword').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Пароли не совпадают');
                this.classList.add('is-invalid');
            } else {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
            }
        });
    }

    // Check if user is logged in
    const savedUser = localStorage.getItem('currentUser');
    if (savedUser) {
        currentUser = JSON.parse(savedUser);
        updateUIForLoggedInUser();
    }
}

function handleLogin() {
    const email = document.getElementById('loginEmail').value;
    const password = document.getElementById('loginPassword').value;
    const rememberMe = document.getElementById('rememberMe').checked;

    // Simulate login validation
    if (email && password) {
        // Определяем роль по email (для демо)
        const role = email.includes('admin') || email.includes('сотрудник') ? 'admin' : 'client';
        
        currentUser = {
            email: email,
            role: role,
            name: role === 'admin' ? 'Администратор' : 'Клиент',
            loginTime: new Date().toISOString()
        };

        if (rememberMe) {
            localStorage.setItem('currentUser', JSON.stringify(currentUser));
        } else {
            sessionStorage.setItem('currentUser', JSON.stringify(currentUser));
        }

        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('loginModal'));
        modal.hide();

        // Update UI
        updateUIForLoggedInUser();

        // Show success toast
        showSuccessToast('Вход выполнен успешно!');

        // Open admin panel if admin
        if (role === 'admin') {
            setTimeout(() => {
                const adminModal = new bootstrap.Modal(document.getElementById('adminModal'));
                adminModal.show();
            }, 500);
        }
    }
}

function handleRegistration() {
    const form = document.getElementById('registerForm');
    if (form.checkValidity()) {
        const userData = {
            name: document.getElementById('regName').value,
            surname: document.getElementById('regSurname').value,
            email: document.getElementById('regEmail').value,
            phone: document.getElementById('regPhone').value,
            password: document.getElementById('regPassword').value,
            registrationDate: new Date().toISOString()
        };

        // Simulate registration
        console.log('Регистрация пользователя:', userData);

        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('registerModal'));
        modal.hide();

        // Show success message
        showSuccessToast('Регистрация прошла успешно! Теперь вы можете войти в систему.');

        // Clear form
        form.reset();
        form.classList.remove('was-validated');
    }
    form.classList.add('was-validated');
}

function updateUIForLoggedInUser() {
    const loginBtn = document.querySelector('[data-bs-target="#loginModal"]');
    if (loginBtn && currentUser) {
        loginBtn.innerHTML = `<i class="bi bi-person-check"></i> ${currentUser.name}`;
        loginBtn.setAttribute('data-bs-toggle', 'dropdown');
        loginBtn.setAttribute('data-bs-target', '');
        
        // Create dropdown menu
        const dropdownMenu = document.createElement('ul');
        dropdownMenu.className = 'dropdown-menu';
        dropdownMenu.innerHTML = `
            <li><a class="dropdown-item" href="profile.html"><i class="bi bi-person"></i> Профиль</a></li>
            ${currentUser.role === 'admin' ? '<li><a class="dropdown-item" href="#" onclick="openAdminPanel()"><i class="bi bi-gear"></i> Админ-панель</a></li>' : ''}
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="#" onclick="logout()"><i class="bi bi-box-arrow-right"></i> Выйти</a></li>
        `;
        
        loginBtn.parentNode.appendChild(dropdownMenu);
        loginBtn.parentNode.classList.add('dropdown');
    }
}

function logout() {
    // Используем logout из API клиента если доступен, иначе локальная очистка
    if (window.ApiClient && window.ApiClient.auth && window.ApiClient.auth.logout) {
        window.ApiClient.auth.logout();
        return;
    }
    
    // Fallback - локальная очистка
    currentUser = null;
    localStorage.removeItem('currentUser');
    sessionStorage.removeItem('currentUser');
    localStorage.removeItem('auth_token');
    localStorage.removeItem('user_data');
    
    // Reset UI
    const loginBtn = document.querySelector('[data-bs-target="#loginModal"], .dropdown .btn');
    if (loginBtn) {
        loginBtn.innerHTML = '<i class="bi bi-person-circle"></i> Вход';
        loginBtn.className = 'btn btn-outline-primary btn-lg ms-2';
        loginBtn.setAttribute('data-bs-toggle', 'modal');
        loginBtn.setAttribute('data-bs-target', '#loginModal');
        loginBtn.parentNode.classList.remove('dropdown');
        
        const dropdownMenu = loginBtn.parentNode.querySelector('.dropdown-menu');
        if (dropdownMenu) {
            dropdownMenu.remove();
        }
    }
    
    showSuccessToast('Вы успешно вышли из системы');
}

function openAdminPanel() {
    const adminModal = new bootstrap.Modal(document.getElementById('adminModal'));
    adminModal.show();
    loadBookingsData();
    loadClientsData();
}

// ======================
// ADMIN PANEL FUNCTIONS
// ======================
function initializeAdminPanel() {
    // Load initial data when admin panel is shown
    document.getElementById('adminModal').addEventListener('shown.bs.modal', function() {
        loadBookingsData();
        loadClientsData();
    });
}

function loadBookingsData() {
    const tableBody = document.getElementById('bookingsTable');
    if (!tableBody) return;

    tableBody.innerHTML = mockBookings.map(booking => `
        <tr data-status="${booking.status}">
            <td>${booking.id}</td>
            <td>${booking.clientName}</td>
            <td>${booking.clientPhone}</td>
            <td>${booking.service}</td>
            <td>${formatDate(booking.date)}</td>
            <td>${booking.time}</td>
            <td>
                <span class="badge bg-${getStatusColor(booking.status)}">
                    ${getStatusText(booking.status)}
                </span>
            </td>
            <td>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-success" onclick="updateBookingStatus(${booking.id}, 'confirmed')" title="Подтвердить">
                        <i class="bi bi-check"></i>
                    </button>
                    <button class="btn btn-outline-danger" onclick="updateBookingStatus(${booking.id}, 'cancelled')" title="Отменить">
                        <i class="bi bi-x"></i>
                    </button>
                    <button class="btn btn-outline-primary" onclick="editBooking(${booking.id})" title="Редактировать">
                        <i class="bi bi-pencil"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function loadClientsData() {
    const tableBody = document.getElementById('clientsTable');
    if (!tableBody) return;

    tableBody.innerHTML = mockClients.map(client => `
        <tr>
            <td>${client.id}</td>
            <td>${client.name}</td>
            <td>${client.email}</td>
            <td>${client.phone}</td>
            <td>${formatDate(client.registrationDate)}</td>
            <td>${client.visits}</td>
            <td>
                <span class="badge bg-${client.status === 'VIP' ? 'warning' : 'success'}">
                    ${client.status}
                </span>
            </td>
        </tr>
    `).join('');
}

function filterBookings(status) {
    const rows = document.querySelectorAll('#bookingsTable tr');
    rows.forEach(row => {
        if (status === 'all' || row.dataset.status === status) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
    
    // Update active button
    document.querySelectorAll('.btn-group button').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
}

function updateBookingStatus(bookingId, newStatus) {
    const booking = mockBookings.find(b => b.id === bookingId);
    if (booking) {
        booking.status = newStatus;
        loadBookingsData();
        showSuccessToast(`Статус заявки #${bookingId} обновлен`);
    }
}

function editBooking(bookingId) {
    const booking = mockBookings.find(b => b.id === bookingId);
    if (booking) {
        // Open booking modal with pre-filled data
        document.getElementById('clientName').value = booking.clientName;
        document.getElementById('clientPhone').value = booking.clientPhone;
        document.getElementById('serviceSelect').value = booking.service;
        document.getElementById('appointmentDate').value = booking.date;
        document.getElementById('appointmentTime').value = booking.time;
        
        const bookingModal = new bootstrap.Modal(document.getElementById('bookingModal'));
        bookingModal.show();
    }
}

// ======================
// UTILITY FUNCTIONS
// ======================
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('ru-RU');
}

function getStatusColor(status) {
    switch(status) {
        case 'pending': return 'warning';
        case 'confirmed': return 'success';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
}

function getStatusText(status) {
    switch(status) {
        case 'pending': return 'Ожидает';
        case 'confirmed': return 'Подтверждена';
        case 'cancelled': return 'Отменена';
        default: return 'Неизвестно';
    }
}

function showProfile() {
    alert('Функция профиля в разработке');
}

function showSuccessToast(message) {
    const toastBody = document.querySelector('#successToast .toast-body');
    if (toastBody) {
        toastBody.textContent = message;
        const toast = new bootstrap.Toast(document.getElementById('successToast'));
        toast.show();
    }
}

// ======================
// SERVICE WORKER REGISTRATION
// ======================
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('/sw.js')
            .then(function(registration) {
                console.log('ServiceWorker registration successful');
            })
            .catch(function(err) {
                console.log('ServiceWorker registration failed: ', err);
            });
    });
}

// ======================
// PWA INSTALL PROMPT
// ======================
let deferredPrompt;

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    
    // Show install button if needed
    const installBtn = document.createElement('button');
    installBtn.className = 'btn btn-outline-primary position-fixed';
    installBtn.style.cssText = 'bottom: 80px; right: 20px; z-index: 1050;';
    installBtn.innerHTML = '<i class="bi bi-download"></i> Установить';
    installBtn.onclick = () => {
        deferredPrompt.prompt();
        deferredPrompt.userChoice.then((choiceResult) => {
            if (choiceResult.outcome === 'accepted') {
                console.log('User accepted the install prompt');
            }
            deferredPrompt = null;
            installBtn.remove();
        });
    };
    
    document.body.appendChild(installBtn);
    
    setTimeout(() => {
        if (installBtn.parentNode) {
            installBtn.remove();
        }
    }, 10000); // Remove after 10 seconds
});

// ======================
// THEME MANAGEMENT
// ======================
function initializeTheme() {
    const savedTheme = localStorage.getItem('theme') || 'dark';
    applyTheme(savedTheme);
    
    // Add theme toggle button to navbar if not exists
    addThemeToggleButton();
}

function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);
    
    // Update theme toggle button text
    updateThemeToggleButton(theme);
    
    // Trigger custom event for other components
    window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme } }));
}

function addThemeToggleButton() {
    const navbar = document.querySelector('.navbar .container');
    if (navbar && !document.querySelector('.theme-switch-container')) {
        const themeSwitch = document.createElement('div');
        themeSwitch.className = 'theme-switch-container';
        themeSwitch.innerHTML = `
            <div class="theme-switch" onclick="toggleTheme()">
                <div class="theme-switch-track">
                    <div class="theme-switch-thumb">
                        <span class="theme-icon">🌙</span>
                    </div>
                </div>
                <div class="theme-labels">
                    <span class="theme-label light">Светлая</span>
                    <span class="theme-label dark">Темная</span>
                </div>
            </div>
        `;
        
        // Add as separate element after navbar
        navbar.appendChild(themeSwitch);
    }
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

function toggleTheme() {
    const current = localStorage.getItem('theme') || 'dark';
    const nextTheme = current === 'light' ? 'dark' : 'light';
    
    applyTheme(nextTheme);
    
    // Update radio buttons if on profile settings page
    document.querySelectorAll('input[name="theme"]').forEach(radio => {
        radio.checked = (radio.value === nextTheme);
    });
    
    const themeNames = {
        'light': 'светлую',
        'dark': 'темную'
    };
    
    showSuccessToast(`Переключено на ${themeNames[nextTheme]} тему`);
}

// Initialize theme on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeTheme();
});



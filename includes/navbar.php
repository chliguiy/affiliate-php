<?php
// Vérifier si l'utilisateur est connecté (la session est déjà démarrée dans index.php)
$isLoggedIn = isset($_SESSION['user_id']);
?>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-light fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-handshake"></i> شيك أفلييت
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="#features">المميزات</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#how-it-works">كيف يعمل</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#testimonials">آراء العملاء</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#faq">الأسئلة الشائعة</a>
                </li>
            </ul>
            
            <!-- Barre de recherche -->
            <form class="d-flex mx-3" id="searchForm">
                <div class="input-group">
                    <input type="search" class="form-control" placeholder="ابحث عن منتجات..." aria-label="Search" id="searchInput">
                    <button class="btn btn-outline-primary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>

            <!-- Barre d'icônes réseaux sociaux et notifications -->
            <div class="d-flex align-items-center gap-3 me-3">
                <div class="position-relative">
                    <i class="fas fa-bell fa-lg"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.7rem;">0</span>
                </div>
                <a href="https://youtube.com/" target="_blank" class="text-danger" title="YouTube"><i class="fab fa-youtube fa-lg"></i></a>
                <a href="https://t.me/" target="_blank" class="text-primary" title="Telegram"><i class="fab fa-telegram-plane fa-lg"></i></a>
                <a href="https://wa.me/" target="_blank" class="text-success" title="WhatsApp"><i class="fab fa-whatsapp fa-lg"></i></a>
            </div>

            <!-- Menu utilisateur (uniquement si connecté) -->
            <?php if($isLoggedIn): ?>
                <div class="dropdown">
                    <button class="btn btn-outline-primary dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i> حسابي
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="dashboard.php">لوحة التحكم</a></li>
                        <li><a class="dropdown-item" href="profile.php">الملف الشخصي</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php">تسجيل الخروج</a></li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Modal de connexion -->
<div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="loginModalLabel">تسجيل الدخول</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="loginForm" method="post" action="auth/login.php">
                    <div class="mb-3">
                        <label for="loginEmail" class="form-label">البريد الإلكتروني</label>
                        <input type="email" class="form-control" id="loginEmail" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="loginPassword" class="form-label">كلمة المرور</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="loginPassword" name="password" required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="rememberMe" name="remember">
                        <label class="form-check-label" for="rememberMe">تذكرني</label>
                    </div>
                    <div class="mb-3 text-end">
                        <a href="forgot-password.php" class="text-decoration-none">نسيت كلمة المرور؟</a>
                    </div>
                    <div class="alert alert-danger d-none" id="loginError"></div>
                    <button type="submit" class="btn btn-primary w-100">تسجيل الدخول</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle password visibility
document.getElementById('togglePassword').addEventListener('click', function() {
    const passwordInput = document.getElementById('loginPassword');
    const icon = this.querySelector('i');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});

// Handle login form submission
document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const errorDiv = document.getElementById('loginError');
    
    // Simuler une requête AJAX (à remplacer par votre vraie requête)
    fetch(this.action, {
        method: 'POST',
        body: new FormData(this)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            errorDiv.textContent = data.message || 'خطأ في تسجيل الدخول';
            errorDiv.classList.remove('d-none');
        }
    })
    .catch(error => {
        errorDiv.textContent = 'حدث خطأ. الرجاء المحاولة مرة أخرى';
        errorDiv.classList.remove('d-none');
    });
});

// Navbar scroll effect
window.addEventListener('scroll', function() {
    const navbar = document.querySelector('.navbar');
    if (window.scrollY > 50) {
        navbar.classList.add('scrolled', 'shadow-sm');
    } else {
        navbar.classList.remove('scrolled', 'shadow-sm');
    }
});
</script> 
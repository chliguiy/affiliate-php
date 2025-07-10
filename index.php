<?php
session_start();
require_once 'config/database.php';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="شيك أفلييت - أفضل منصة للتسويق في المغرب">
    <title>شيك أفلييت - منصة التسويق</title>
    
    <!-- Preload des ressources critiques -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" as="style">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" as="style">
    
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --text-color: #333;
            --bg-color: #fff;
            --hero-bg: #1f2937;
            --hero-text: #ffffff;
        }

        [data-theme="dark"] {
            --primary-color: #34495e;
            --secondary-color: #2980b9;
            --accent-color: #c0392b;
            --text-color: #f5f5f5;
            --bg-color: #1a1a1a;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: var(--bg-color);
            transition: background-color 0.3s ease, color 0.3s ease;
            overflow-x: hidden;
        }

        /* Animation de la barre de navigation */
        .navbar {
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9) !important;
            backdrop-filter: blur(10px);
        }

        .navbar.scrolled {
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            padding: 0.5rem 1rem;
        }

        /* Animation des boutons */
        .btn {
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            z-index: 1;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .ripple {
            position: absolute;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            transform: scale(0);
            animation: ripple 0.6s linear;
            pointer-events: none;
        }

        @keyframes ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }

        /* Animation des sections */
        .hero-section {
            position: relative;
            overflow: hidden;
        }

        .hero-section::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 100px;
            background: linear-gradient(to bottom, transparent, rgba(0, 0, 0, 0.1));
            pointer-events: none;
        }

        /* Animation des cartes */
        .feature-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            transform-origin: center;
            will-change: transform;
        }

        .feature-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        .feature-icon {
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .feature-card:hover .feature-icon {
            transform: scale(1.2) rotate(360deg);
            color: var(--accent-color);
        }

        /* Animation des statistiques */
        .stat-number {
            display: inline-block;
            font-weight: bold;
            color: var(--secondary-color);
            transition: all 0.3s ease;
        }

        .stat-card {
            position: relative;
            overflow: hidden;
            padding: 2rem;
            border-radius: 15px;
            background: var(--bg-color);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 0;
            border-radius: 15px;
        }

        .stat-card:hover::before {
            opacity: 0.05;
        }

        /* Animation du CTA */
        .cta-section {
            position: relative;
            overflow: hidden;
        }

        .cta-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.1)" d="M0,96L48,112C96,128,192,160,288,186.7C384,213,480,235,576,213.3C672,192,768,128,864,128C960,128,1056,192,1152,208C1248,224,1344,192,1392,176L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-repeat: no-repeat;
            background-position: bottom;
            background-size: cover;
            opacity: 0.1;
            transform: translateY(50%);
            transition: transform 0.5s ease;
        }

        .cta-section:hover::before {
            transform: translateY(0);
        }

        /* Animation des étapes */
        .rounded-circle {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            z-index: 1;
        }

        .rounded-circle::before {
            content: '';
            position: absolute;
            top: -5px;
            left: -5px;
            right: -5px;
            bottom: -5px;
            background: var(--secondary-color);
            border-radius: 50%;
            z-index: -1;
            opacity: 0;
            transform: scale(0.8);
            transition: all 0.3s ease;
        }

        .rounded-circle:hover::before {
            opacity: 0.2;
            transform: scale(1.1);
        }

        /* Animation du footer */
        footer {
            position: relative;
            overflow: hidden;
        }

        footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(to right, transparent, var(--text-color), transparent);
            opacity: 0.1;
        }

        /* Animation de chargement */
        .loading {
            overflow: hidden;
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .loading .loading-overlay {
            opacity: 1;
            visibility: visible;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 3px solid var(--secondary-color);
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Style spécifique pour la section hero */
        .hero-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            background: var(--hero-bg);
            color: var(--hero-text);
            padding: 0;
            margin: 0;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 200px;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 320'%3E%3Cpath fill='%232d3748' fill-opacity='1' d='M0,96L48,112C96,128,192,160,288,186.7C384,213,480,235,576,213.3C672,192,768,128,864,128C960,128,1056,192,1152,208C1248,224,1344,192,1392,176L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: bottom;
            background-size: cover;
            opacity: 0.1;
            z-index: 1;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 900px;
            padding: 2rem;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--hero-text);
            line-height: 1.2;
            position: relative;
        }

        .hero-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 150px;
            height: 4px;
            background: linear-gradient(90deg, #4CAF50, #2196F3);
            border-radius: 2px;
        }

        .hero-subtitle {
            font-size: 1.4rem;
            margin: 2rem auto;
            opacity: 0.9;
            line-height: 1.8;
            max-width: 800px;
            color: #b0b7c3;
        }

        .hero-cta {
            margin-top: 2.5rem;
        }

        .hero-cta .btn {
            padding: 1rem 2.5rem;
            font-size: 1.2rem;
            font-weight: 500;
            border-radius: 50px;
            background: linear-gradient(45deg, #4CAF50, #2196F3);
            border: none;
            color: white;
            transition: all 0.3s ease;
            text-transform: none;
        }

        .hero-cta .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        /* Éléments flottants 3D */
        .floating-elements {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        .floating-element {
            position: absolute;
            animation: float 6s ease-in-out infinite;
        }

        .floating-element img {
            width: 60px;
            height: 60px;
            object-fit: contain;
        }

        .element-1 { top: 20%; left: 10%; animation-delay: 0s; }
        .element-2 { top: 60%; right: 15%; animation-delay: 1s; }
        .element-3 { top: 30%; right: 25%; animation-delay: 2s; }
        .element-4 { bottom: 20%; left: 20%; animation-delay: 1.5s; }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
        }

        /* Style pour les logos partenaires */
        .partners-logos {
            display: none;
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-subtitle {
                font-size: 1.2rem;
                padding: 0 1rem;
            }

            .floating-element {
                display: none;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="floating-elements">
            <div class="floating-element element-1">
                <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTQ4LjUgMjQuNUMzOC41IDI0LjUgNDEgMTUuNSAzMCAxNS41QzE5IDE1LjUgMjEuNSAyNC41IDExLjUgMjQuNUM1LjUgMjQuNSAwLjUgMjkuNSAwLjUgMzUuNUMwLjUgNDEuNSA1LjUgNDYuNSAxMS41IDQ2LjVINDguNUM1NC41IDQ2LjUgNTkuNSA0MS41IDU5LjUgMzUuNUM1OS41IDI5LjUgNTQuNSAyNC41IDQ4LjUgMjQuNVoiIGZpbGw9IiNGRkZGRkYiIGZpbGwtb3BhY2l0eT0iMC4yIi8+Cjwvc3ZnPg==" alt="Cloud">
            </div>
            <div class="floating-element element-2">
                <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTMwIDEwQzI1IDEwIDIwIDE1IDIwIDIwVjM1QzIwIDQwIDI1IDQ1IDMwIDQ1QzM1IDQ1IDQwIDQwIDQwIDM1VjIwQzQwIDE1IDM1IDEwIDMwIDEwWk0zNSAzNUMzNSAzNy41IDMyLjUgNDAgMzAgNDBDMjcuNSA0MCAyNSAzNy41IDI1IDM1VjIwQzI1IDE3LjUgMjcuNSAxNSAzMCAxNUMzMi41IDE1IDM1IDE3LjUgMzUgMjBWMzVaIiBmaWxsPSIjRkZGRkZGIiBmaWxsLW9wYWNpdHk9IjAuMiIvPgo8L3N2Zz4=" alt="Hand">
            </div>
            <div class="floating-element element-3">
                <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTQ1IDE1SDI1QzIyLjUgMTUgMjAgMTcuNSAyMCAyMFY0MEMyMCA0Mi41IDIyLjUgNDUgMjUgNDVINDVDNDcuNSA0NSA1MCA0Mi41IDUwIDQwVjIwQzUwIDE3LjUgNDcuNSAxNSA0NSAxNVpNNDUgNDBIMjVWMjBINDVWNDBaIiBmaWxsPSIjRkZGRkZGIiBmaWxsLW9wYWNpdHk9IjAuMiIvPgo8Y2lyY2xlIGN4PSIzMCIgY3k9IjMwIiByPSIzIiBmaWxsPSIjRkZGRkZGIiBmaWxsLW9wYWNpdHk9IjAuMiIvPgo8Y2lyY2xlIGN4PSI0MCIgY3k9IjMwIiByPSIzIiBmaWxsPSIjRkZGRkZGIiBmaWxsLW9wYWNpdHk9IjAuMiIvPgo8L3N2Zz4=" alt="Chat">
            </div>
            <div class="floating-element element-4">
                <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMzAiIGN5PSIyMCIgcj0iMTAiIGZpbGw9IiNGRkZGRkYiIGZpbGwtb3BhY2l0eT0iMC4yIi8+CjxwYXRoIGQ9Ik00NSA0MEMzNSA0MCAyNSA0MCAyNSA0MEMyNSA0MCAyMCAzMCAzMCAzMEM0MCAzMCAzNSA0MCA0NSA0MFoiIGZpbGw9IiNGRkZGRkYiIGZpbGwtb3BhY2l0eT0iMC4yIi8+Cjwvc3ZnPg==" alt="Person">
            </div>
        </div>

        <div class="hero-content">
            <h1 class="hero-title">شريكك نحو النجاح في عالم التسويق</h1>
            <p class="hero-subtitle">نوفر لك منصة متكاملة لبدء تجارتك الإلكترونية بكل احترافية، مع تشكيلة واسعة من المنتجات المميزة بأسعار تنافسية تضمن لك عائداً مجزياً</p>
            <div class="hero-cta">
                <a href="login.php" class="btn btn-primary">تسجيل الدخول</a>
                <a href="register.php" class="btn btn-outline-primary">إنشاء حساب</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="container">
        <h2 class="text-center mb-5" data-aos="fade-up">مميزات المنصة</h2>
        <div class="row">
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="0">
                <div class="card feature-card p-4">
                    <div class="text-center">
                        <i class="fas fa-box-open feature-icon"></i>
                        <h3>منتجات حصرية</h3>
                        <p>نقدم لك مجموعة متنوعة من المنتجات الحصرية للترويج</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="card feature-card p-4">
                    <div class="text-center">
                        <i class="fas fa-chart-line feature-icon"></i>
                        <h3>تتبع الأداء</h3>
                        <p>تتبع مبيعاتك وإحصائياتك في الوقت الفعلي مع لوحة تحكم متكاملة</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="400">
                <div class="card feature-card p-4">
                    <div class="text-center">
                        <i class="fas fa-money-bill-wave feature-icon"></i>
                        <h3>مدفوعات آمنة</h3>
                        <p>مدفوعات آمنة وموثوقة مع دعم للبنوك المغربية الرئيسية</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section" data-aos="fade-up">
        <div class="container">
            <div class="row">
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="0">
                    <div class="stat-card">
                        <div class="stat-number" data-count="1000">0</div>
                        <div class="stat-label">بائع نشط</div>
                    </div>
                </div>
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="200">
                    <div class="stat-card">
                        <div class="stat-number" data-count="5000">0</div>
                        <div class="stat-label">مسوق</div>
                    </div>
                </div>
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="400">
                    <div class="stat-card">
                        <div class="stat-number" data-count="10000">0</div>
                        <div class="stat-label">منتج</div>
                    </div>
                </div>
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="600">
                    <div class="stat-card">
                        <div class="stat-number" data-count="1000000">0</div>
                        <div class="stat-label">درهم عمولات</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section id="how-it-works" class="container">
        <h2 class="text-center mb-5" data-aos="fade-up">كيف يعمل</h2>
        <div class="row">
            <div class="col-md-4" data-aos="fade-right" data-aos-delay="0">
                <div class="text-center">
                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <h3 class="mb-0">1</h3>
                    </div>
                    <h4>إنشاء حساب</h4>
                    <p>سجل كمسوق في دقائق</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="text-center">
                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <h3 class="mb-0">2</h3>
                    </div>
                    <h4>اختر المنتجات</h4>
                    <p>اختر من بين مجموعة منتجاتنا الحصرية للترويج</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-left" data-aos-delay="400">
                <div class="text-center">
                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <h3 class="mb-0">3</h3>
                    </div>
                    <h4>ابدأ في الكسب</h4>
                    <p>ابدأ في كسب العمولات من المبيعات</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Why Choose Us Section -->
    <section class="why-choose-us py-5">
        <div class="container">
            <h2 class="text-center mb-5" data-aos="fade-up">لماذا تختار شيك أفلييت؟</h2>
            <div class="row g-4">
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="0">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-percentage text-primary mb-3" style="font-size: 2.5rem;"></i>
                            <h4>عمولات مجزية</h4>
                            <p>نقدم أعلى نسب العمولات في السوق تصل إلى 40%</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="100">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-hand-holding-usd text-primary mb-3" style="font-size: 2.5rem;"></i>
                            <h4>دفع سريع</h4>
                            <p>نضمن تحويل عمولاتك خلال 48 ساعة كحد أقصى</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="200">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-tools text-primary mb-3" style="font-size: 2.5rem;"></i>
                            <h4>أدوات تسويق متطورة</h4>
                            <p>نوفر أحدث أدوات التسويق والتتبع لتحسين أدائك</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3" data-aos="fade-up" data-aos-delay="300">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-headset text-primary mb-3" style="font-size: 2.5rem;"></i>
                            <h4>دعم فني 24/7</h4>
                            <p>فريق دعم متخصص متواجد على مدار الساعة لمساعدتك</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Latest Success Section -->
    <section class="latest-success py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5" data-aos="fade-up">آخر قصص النجاح</h2>
            <div class="row g-4">
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="0">
                    <div class="card success-card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="success-icon bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                                    <i class="fas fa-star"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0">أحمد .م</h5>
                                    <small class="text-muted">مسوق</small>
                                </div>
                            </div>
                            <p>حقق دخلاً شهرياً يتجاوز 15,000 درهم من خلال تسويق المنتجات الرقمية</p>
                            <div class="text-success">
                                <i class="fas fa-chart-line me-2"></i>
                                زيادة المبيعات بنسبة 300%
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="card success-card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="success-icon bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                                    <i class="fas fa-trophy"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0">سارة .ح</h5>
                                    <small class="text-muted">بائعة</small>
                                </div>
                            </div>
                            <p>تمكنت من توسيع نطاق مبيعاتها عبر شبكة المسوقين لدينا</p>
                            <div class="text-success">
                                <i class="fas fa-users me-2"></i>
                                أكثر من 100 مسوق نشط
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="card success-card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="success-icon bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                                    <i class="fas fa-rocket"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0">ياسين .ع</h5>
                                    <small class="text-muted">مسوق</small>
                                </div>
                            </div>
                            <p>بدأ من الصفر وحقق مبيعات تجاوزت 100,000 درهم في 3 أشهر</p>
                            <div class="text-success">
                                <i class="fas fa-arrow-up me-2"></i>
                                نمو مستمر شهرياً
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials py-5">
        <div class="container">
            <h2 class="text-center mb-5" data-aos="fade-up">ماذا يقول عملاؤنا</h2>
            <div class="row g-4">
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="0">
                    <div class="testimonial-card text-center p-4">
                        <img src="https://ui-avatars.com/api/?name=محمد+أمين&background=random" alt="محمد أمين" class="rounded-circle mb-3" width="80">
                        <h5>محمد أمين</h5>
                        <p class="text-muted mb-3">مسوق</p>
                        <div class="text-warning mb-3">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p>"منصة رائعة وسهلة الاستخدام. الدعم الفني ممتاز والعمولات مجزية جداً"</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="testimonial-card text-center p-4">
                        <img src="https://ui-avatars.com/api/?name=فاطمة+الزهراء&background=random" alt="فاطمة الزهراء" class="rounded-circle mb-3" width="80">
                        <h5>فاطمة الزهراء</h5>
                        <p class="text-muted mb-3">بائعة</p>
                        <div class="text-warning mb-3">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p>"ساعدتني المنصة في الوصول إلى مسوقين محترفين وزيادة مبيعاتي بشكل كبير"</p>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="testimonial-card text-center p-4">
                        <img src="https://ui-avatars.com/api/?name=عبد+الله&background=random" alt="عبد الله" class="rounded-circle mb-3" width="80">
                        <h5>عبد الله</h5>
                        <p class="text-muted mb-3">مسوق</p>
                        <div class="text-warning mb-3">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p>"أفضل منصة تسويق بالعمولة في المغرب. الدفعات دائماً في موعدها والمنتجات ممتازة"</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Partners Section -->
    <section class="partners py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5" data-aos="fade-up">شركاؤنا</h2>
            <div class="row align-items-center justify-content-center g-4">
                <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="0">
                    <div class="text-center">
                        <i class="fas fa-building" style="font-size: 3rem; color: var(--primary-color);"></i>
                        <h5 class="mt-3">شركة التجارة الإلكترونية</h5>
                    </div>
                </div>
                <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="100">
                    <div class="text-center">
                        <i class="fas fa-store" style="font-size: 3rem; color: var(--primary-color);"></i>
                        <h5 class="mt-3">متاجر رقمية</h5>
                    </div>
                </div>
                <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="200">
                    <div class="text-center">
                        <i class="fas fa-shipping-fast" style="font-size: 3rem; color: var(--primary-color);"></i>
                        <h5 class="mt-3">شركات الشحن</h5>
                    </div>
                </div>
                <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="300">
                    <div class="text-center">
                        <i class="fas fa-credit-card" style="font-size: 3rem; color: var(--primary-color);"></i>
                        <h5 class="mt-3">بوابات الدفع</h5>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq py-5">
        <div class="container">
            <h2 class="text-center mb-5" data-aos="fade-up">الأسئلة الشائعة</h2>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item" data-aos="fade-up" data-aos-delay="0">
                            <h3 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    كيف يمكنني البدء في التسويق؟
                                </button>
                            </h3>
                            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    يمكنك البدء بسهولة من خلال التسجيل كمسوق، واختيار المنتجات التي تريد تسويقها، والحصول على روابط خاصة بك للترويج.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item" data-aos="fade-up" data-aos-delay="100">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    متى يتم دفع العمولات؟
                                </button>
                            </h3>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    يتم دفع العمولات كل أسبوعين، شرط أن يتجاوز رصيدك 500 درهم. نقوم بالتحويل مباشرة إلى حسابك البنكي.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item" data-aos="fade-up" data-aos-delay="200">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    ما هي نسب العمولات؟
                                </button>
                            </h3>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    تتراوح نسب العمولات بين 10% و 40% حسب نوع المنتج وحجم المبيعات. كلما زادت مبيعاتك، زادت نسبة عمولتك.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item" data-aos="fade-up" data-aos-delay="300">
                            <h3 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                    هل يمكنني التسويق عبر وسائل التواصل الاجتماعي؟
                                </button>
                            </h3>
                            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    نعم، يمكنك التسويق عبر جميع منصات التواصل الاجتماعي. نوفر لك أدوات وموارد تسويقية مخصصة لكل منصة.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section" data-aos="fade-up">
        <div class="container text-center">
            <h2 class="mb-4" data-aos="fade-up">ابدأ رحلتك معنا اليوم</h2>
            <p class="lead mb-4" data-aos="fade-up" data-aos-delay="200">انضم إلى مجتمعنا المتنامي من البائعين والمسوقين بالعمولة</p>
            <div class="d-flex justify-content-center gap-3" data-aos="fade-up" data-aos-delay="400">
                <a href="login.php" class="btn btn-light btn-lg">تسجيل الدخول</a>
                <a href="register.php" class="btn btn-outline-light btn-lg">إنشاء حساب</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>شيك أفلييت</h5>
                    <p>منصة التسويق الأفضل في المغرب</p>
                </div>
                <div class="col-md-4">
                    <h5>روابط سريعة</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white">عن المنصة</a></li>
                        <li><a href="#" class="text-white">الشروط والأحكام</a></li>
                        <li><a href="#" class="text-white">سياسة الخصوصية</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>تواصل معنا</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-envelope me-2"></i> contact@chic-affiliate.com</li>
                        <li><i class="fas fa-phone me-2"></i> +212 5XX-XXXXXX</li>
                    </ul>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p class="mb-0">&copy; 2025 ADNANE أفلييت. جميع الحقوق محفوظة.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script>
        // Initialisation des animations AOS
        AOS.init({
            duration: 800,
            once: true
        });

        // Gestion du thème sombre
        const themeSwitch = document.createElement('button');
        themeSwitch.className = 'theme-switch';
        themeSwitch.innerHTML = '<i class="fas fa-moon"></i>';
        document.body.appendChild(themeSwitch);

        themeSwitch.addEventListener('click', () => {
            document.documentElement.setAttribute('data-theme',
                document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark'
            );
            themeSwitch.innerHTML = document.documentElement.getAttribute('data-theme') === 'dark' 
                ? '<i class="fas fa-sun"></i>' 
                : '<i class="fas fa-moon"></i>';
            
            // Sauvegarder la préférence
            localStorage.setItem('theme', document.documentElement.getAttribute('data-theme'));
        });

        // Restaurer le thème préféré
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            document.documentElement.setAttribute('data-theme', savedTheme);
            themeSwitch.innerHTML = savedTheme === 'dark' 
                ? '<i class="fas fa-sun"></i>' 
                : '<i class="fas fa-moon"></i>';
        }

        // Bouton retour en haut
        const backToTop = document.createElement('button');
        backToTop.className = 'back-to-top';
        backToTop.innerHTML = '<i class="fas fa-arrow-up"></i>';
        document.body.appendChild(backToTop);

        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                backToTop.classList.add('visible');
            } else {
                backToTop.classList.remove('visible');
            }
        });

        backToTop.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Lazy loading des images
        document.addEventListener('DOMContentLoaded', () => {
            const images = document.querySelectorAll('img[data-src]');
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                        observer.unobserve(img);
                    }
                });
            });

            images.forEach(img => imageObserver.observe(img));
        });

        // Animation des cartes au défilement
        document.querySelectorAll('.feature-card').forEach((card, index) => {
            card.setAttribute('data-aos', 'fade-up');
            card.setAttribute('data-aos-delay', index * 100);
        });

        // Gestion du chargement
        window.addEventListener('load', () => {
            document.body.classList.remove('loading');
        });

        // Gestion de la recherche
        const searchForm = document.getElementById('searchForm');
        const searchInput = document.getElementById('searchInput');
        
        searchForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const searchTerm = searchInput.value.trim();
            
            if (searchTerm) {
                // Ajouter une classe loading pendant la recherche
                document.body.classList.add('loading');
                
                // Simuler une recherche (à remplacer par votre vraie API de recherche)
                setTimeout(() => {
                    // Rediriger vers la page de recherche avec le terme
                    window.location.href = `search.php?q=${encodeURIComponent(searchTerm)}`;
                }, 500);
            }
        });

        // Ajout de la suggestion de recherche
        let searchTimeout;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            const searchTerm = e.target.value.trim();
            
            if (searchTerm.length >= 2) {
                searchTimeout = setTimeout(() => {
                    // Ici vous pouvez appeler votre API de suggestions
                    // Pour l'instant, on simule juste un chargement
                    console.log('Searching for:', searchTerm);
                }, 300);
            }
        });

        // Animation des compteurs statistiques
        function animateNumber(element, target) {
            let current = 0;
            const duration = 2000; // 2 secondes
            const step = target / (duration / 16); // 60 FPS
            
            function update() {
                current += step;
                if (current > target) current = target;
                
                element.textContent = Math.floor(current).toLocaleString() + (target >= 1000000 ? '+' : '+');
                
                if (current < target) {
                    requestAnimationFrame(update);
                }
            }
            
            update();
        }

        // Observer pour démarrer l'animation des stats quand elles sont visibles
        const statsObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.querySelectorAll('.stat-number').forEach(stat => {
                        const target = parseInt(stat.getAttribute('data-count'));
                        animateNumber(stat, target);
                    });
                    statsObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        document.querySelector('.stats-section').forEach(section => {
            statsObserver.observe(section);
        });

        // Animation de la barre de navigation au défilement
        window.addEventListener('scroll', () => {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Animation des boutons
        document.querySelectorAll('.btn').forEach(btn => {
            btn.addEventListener('mouseover', function(e) {
                const x = e.pageX - this.offsetLeft;
                const y = e.pageY - this.offsetTop;
                
                const ripple = document.createElement('span');
                ripple.className = 'ripple';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                
                this.appendChild(ripple);
                
                setTimeout(() => ripple.remove(), 1000);
            });
        });
    </script>
</body>
</html> 
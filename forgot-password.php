<?php
session_start();
require_once 'config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $conn = $database->getConnection();
    
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    
    if ($email) {
        // Vérifier si l'email existe
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Générer un token unique
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Sauvegarder le token dans la base de données
            $stmt = $conn->prepare("
                INSERT INTO password_resets (user_id, token, expires_at)
                VALUES (?, ?, ?)
            ");
            
            if ($stmt->execute([$user['id'], $token, $expires])) {
                // Envoyer l'email de réinitialisation
                $reset_link = "https://" . $_SERVER['HTTP_HOST'] . "/reset-password.php?token=" . $token;
                $to = $email;
                $subject = "إعادة تعيين كلمة المرور - شيك أفلييت";
                $message = "
                <html dir='rtl'>
                <head>
                    <title>إعادة تعيين كلمة المرور</title>
                </head>
                <body>
                    <h2>مرحباً،</h2>
                    <p>لقد تلقينا طلباً لإعادة تعيين كلمة المرور الخاصة بك.</p>
                    <p>انقر على الرابط التالي لإعادة تعيين كلمة المرور:</p>
                    <p><a href='$reset_link'>$reset_link</a></p>
                    <p>ينتهي هذا الرابط خلال ساعة واحدة.</p>
                    <p>إذا لم تطلب إعادة تعيين كلمة المرور، يمكنك تجاهل هذا البريد الإلكتروني.</p>
                    <br>
                    <p>مع تحيات،<br>فريق شيك أفلييت</p>
                </body>
                </html>
                ";
                
                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                $headers .= 'From: شيك أفلييت <noreply@chic-affiliate.com>' . "\r\n";
                
                if (mail($to, $subject, $message, $headers)) {
                    $success = 'تم إرسال رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني.';
                } else {
                    $error = 'حدث خطأ أثناء إرسال البريد الإلكتروني. يرجى المحاولة مرة أخرى.';
                }
            } else {
                $error = 'حدث خطأ. يرجى المحاولة مرة أخرى.';
            }
        } else {
            $error = 'البريد الإلكتروني غير مسجل أو الحساب غير مفعل.';
        }
    } else {
        $error = 'يرجى إدخال بريد إلكتروني صحيح.';
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نسيت كلمة المرور - شيك أفلييت</title>
    <!-- Bootstrap 5 RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .forgot-container {
            max-width: 400px;
            margin: 0 auto;
            padding: 20px;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
            text-align: center;
        }

        .form-control {
            border-radius: 10px;
            padding: 12px;
            border: 1px solid #e0e0e0;
        }

        .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
            border-color: var(--secondary-color);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
        }

        .back-to-login {
            color: var(--secondary-color);
            text-decoration: none;
        }

        .back-to-login:hover {
            color: var(--primary-color);
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="forgot-container">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">نسيت كلمة المرور</h3>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <p class="text-muted mb-4">أدخل بريدك الإلكتروني وسنرسل لك رابطاً لإعادة تعيين كلمة المرور.</p>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label">البريد الإلكتروني</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 mb-3">إرسال رابط إعادة التعيين</button>
                    </form>

                    <div class="text-center">
                        <a href="login.php" class="back-to-login">
                            <i class="fas fa-arrow-right me-1"></i>
                            العودة إلى تسجيل الدخول
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
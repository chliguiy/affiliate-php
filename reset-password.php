<?php
session_start();
require_once 'config/database.php';

$error = '';
$success = '';
$token = $_GET['token'] ?? '';

if (!$token) {
    header('Location: login.php');
    exit;
}

// Vérifier si le token est valide
$stmt = $conn->prepare("
    SELECT pr.*, u.email 
    FROM password_resets pr
    INNER JOIN users u ON pr.user_id = u.id
    WHERE pr.token = ? AND pr.expires_at > NOW()
");
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$reset) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($password && $confirm_password) {
        if ($password === $confirm_password) {
            if (strlen($password) >= 8) {
                // Mettre à jour le mot de passe
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                
                if ($stmt->execute([$hashed_password, $reset['user_id']])) {
                    // Supprimer le token de réinitialisation
                    $stmt = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
                    $stmt->execute([$token]);
                    
                    $success = 'تم تغيير كلمة المرور بنجاح. يمكنك الآن تسجيل الدخول باستخدام كلمة المرور الجديدة.';
                } else {
                    $error = 'حدث خطأ أثناء تغيير كلمة المرور. يرجى المحاولة مرة أخرى.';
                }
            } else {
                $error = 'يجب أن تحتوي كلمة المرور على 8 أحرف على الأقل.';
            }
        } else {
            $error = 'كلمات المرور غير متطابقة.';
        }
    } else {
        $error = 'يرجى ملء جميع الحقول.';
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعادة تعيين كلمة المرور - شيك أفلييت</title>
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

        .reset-container {
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

        .password-strength {
            height: 5px;
            margin-top: 5px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .strength-weak { background-color: #dc3545; width: 33%; }
        .strength-medium { background-color: #ffc107; width: 66%; }
        .strength-strong { background-color: #28a745; width: 100%; }
    </style>
</head>
<body>
    <div class="container">
        <div class="reset-container">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">إعادة تعيين كلمة المرور</h3>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <?php echo $success; ?>
                            <div class="mt-3">
                                <a href="login.php" class="btn btn-primary w-100">تسجيل الدخول</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-4">أدخل كلمة المرور الجديدة لحسابك.</p>

                        <form method="POST" action="" id="resetForm">
                            <div class="mb-3">
                                <label for="password" class="form-label">كلمة المرور الجديدة</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <div class="password-strength" id="passwordStrength"></div>
                                <small class="text-muted">يجب أن تحتوي كلمة المرور على 8 أحرف على الأقل.</small>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">تأكيد كلمة المرور</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 mb-3">تغيير كلمة المرور</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const passwordInput = document.getElementById('password');
        const passwordStrength = document.getElementById('passwordStrength');
        const resetForm = document.getElementById('resetForm');

        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/\d/) && password.match(/[^a-zA-Z\d]/)) strength++;
            
            passwordStrength.className = 'password-strength';
            if (strength === 1) {
                passwordStrength.classList.add('strength-weak');
            } else if (strength === 2) {
                passwordStrength.classList.add('strength-medium');
            } else if (strength === 3) {
                passwordStrength.classList.add('strength-strong');
            }
        });

        resetForm.addEventListener('submit', function(e) {
            const password = passwordInput.value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password.length < 8) {
                e.preventDefault();
                alert('يجب أن تحتوي كلمة المرور على 8 أحرف على الأقل.');
            } else if (password !== confirmPassword) {
                e.preventDefault();
                alert('كلمات المرور غير متطابقة.');
            }
        });
    </script>
</body>
</html> 
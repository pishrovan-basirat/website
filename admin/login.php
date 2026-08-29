<?php
include '../includes/config.php';

if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone'] ?? '');

    if (empty($phone)) {
        $error = '❌ لطفاً شماره همراه را وارد کنید.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ? AND role = 'admin'");
        $stmt->execute([$phone]);
        $user = $stmt->fetch();

        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            header('Location: dashboard.php');
            exit();
        } else {
            $error = '❌ شماره همراه معتبر نیست یا دسترسی مدیر ندارید.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود به پنل مدیریت</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "SF Pro Text", "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: transparent;
            padding: 20px;
        }

        /* ===== انیمیشن Fade Up ===== */
        @keyframes fadeUp {
            0% { opacity: 0; transform: translateY(30px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        .admin-login-container {
            max-width: 400px;
            width: 100%;
        }

        .admin-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 8px;
            text-align: center;
            opacity: 0;
            animation: fadeUp 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) 0.1s forwards;
        }
        .admin-subtitle {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 30px;
            text-align: center;
            opacity: 0;
            animation: fadeUp 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) 0.2s forwards;
        }

        .message {
            padding: 12px 16px;
            border-radius: 14px;
            margin-bottom: 20px;
            font-weight: 500;
            text-align: center;
            font-size: 14px;
            opacity: 0;
            animation: fadeUp 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) 0.15s forwards;
        }
        .message.error {
            background: rgba(255, 59, 48, 0.2);
            color: #ff3b30;
            border: 1px solid rgba(255, 59, 48, 0.3);
        }

        .form-group {
            margin-bottom: 18px;
            opacity: 0;
            animation: fadeUp 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) 0.3s forwards;
        }
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 6px;
        }
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border-radius: 14px;
            border: 1px solid var(--glass-border);
            background: var(--card-bg);
            color: var(--text-primary);
            font-size: 16px;
            font-family: inherit;
            transition: all 0.3s ease;
            outline: none;
        }
        .form-group input:focus {
            border-color: #007aff;
            box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.2);
        }

        .login-btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 99px;
            font-family: inherit;
            font-size: 17px;
            font-weight: 600;
            color: var(--icon-active);
            cursor: pointer;
            outline: none;
            background: var(--glass-bg);
            backdrop-filter: blur(50px) saturate(200%);
            -webkit-backdrop-filter: blur(50px) saturate(200%);
            box-shadow: 0 20px 40px -12px var(--glass-shadow), 0 8px 24px -8px rgba(0,0,0,0.1), inset 0 2px 3px -1px var(--glass-highlight), inset 0 -2px 4px -1px var(--glass-caustic), inset 0 0 0 1px var(--glass-border);
            transition: all 0.4s cubic-bezier(0.34, 1.2, 0.64, 1);
            opacity: 0;
            animation: fadeUp 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) 0.4s forwards;
        }
        .login-btn:hover {
            transform: scale(1.02);
        }
        .login-btn:active {
            transform: scale(0.97);
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: var(--text-secondary);
            opacity: 0;
            animation: fadeUp 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) 0.5s forwards;
        }
        .back-link a {
            color: #007aff;
            text-decoration: none;
            font-weight: 600;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="bg-mesh">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
    </div>

    <div class="admin-login-container">
        <div class="auth-card">
            <h1 class="admin-title">🔐 ورود به پنل مدیریت</h1>
            <p class="admin-subtitle">فقط مدیران سایت دسترسی دارند</p>

            <?php if ($error): ?>
                <div class="message error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label>شماره همراه</label>
                    <input type="tel" name="phone" placeholder="09123456789" required>
                </div>
                <button type="submit" class="login-btn">🚀 ورود به پنل مدیریت</button>
            </form>

            <div class="back-link">
                <a href="../login.php">← بازگشت به صفحه ورود کاربران</a>
            </div>
        </div>
    </div>

    <script>
        document.querySelector('.theme-btn')?.addEventListener('click', function() {
            const root = document.documentElement;
            const isDark = root.getAttribute('data-theme') === 'dark';
            root.setAttribute('data-theme', isDark ? 'light' : 'dark');
        });
    </script>

</body>
</html>
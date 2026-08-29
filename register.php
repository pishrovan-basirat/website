<?php
include 'includes/config.php';

if (isLoggedIn()) {
    header('Location: study.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $skill = trim($_POST['skill'] ?? '');
    $location = trim($_POST['location'] ?? '');

    if (empty($full_name) || empty($phone)) {
        $error = '❌ لطفاً نام و شماره همراه را وارد کنید.';
    } elseif (!preg_match('/^09[0-9]{9}$/', $phone)) {
        $error = '❌ شماره همراه معتبر نیست. (مثال: 09123456789)';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
            $stmt->execute([$phone]);
            if ($stmt->rowCount() > 0) {
                $error = '❌ این شماره همراه قبلاً ثبت شده است.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO users (full_name, phone, skill, location, created_at) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$full_name, $phone, $skill, $location, date('Y-m-d H:i:s')]);
                
                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['full_name'] = $full_name;
                header('Location: study.php');
                exit();
            }
        } catch(PDOException $e) {
            $error = '❌ خطا در ثبت‌نام: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fa" data-theme="light" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ثبت‌نام</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        :root {
            --bg-color: #e5e5ea;
            --blob-1: #ff2a5f;
            --blob-2: #007aff;
            --blob-3: #ff9500;
            --blob-opacity: 0.7;
            --glass-bg: rgba(255, 255, 255, 0.15);
            --glass-border: rgba(255, 255, 255, 0.4);
            --glass-shadow: rgba(0, 0, 0, 0.1);
            --glass-highlight: rgba(255, 255, 255, 0.8);
            --glass-caustic: rgba(255, 255, 255, 0.4);
            --reflection-start: rgba(255, 255, 255, 0.6);
            --reflection-end: rgba(255, 255, 255, 0.0);
            --glare-color: rgba(255, 255, 255, 0.5);
            --pill-bg: rgba(255, 255, 255, 0.7);
            --pill-shadow: 0 4px 12px rgba(0,0,0,0.08), 0 1px 2px rgba(0,0,0,0.05), inset 0 1px 1px rgba(255,255,255,0.8);
            --icon-color: rgba(0, 0, 0, 0.5);
            --icon-active: rgba(0, 0, 0, 0.95);
            --card-bg: rgba(255, 255, 255, 0.2);
            --text-primary: #1c1c1e;
            --text-secondary: #3a3a3c;
        }

        [data-theme="dark"] {
            --bg-color: #000000;
            --blob-1: #bf5af2;
            --blob-2: #0a84ff;
            --blob-3: #ff375f;
            --blob-opacity: 0.5;
            --glass-bg: rgba(30, 30, 35, 0.45);
            --glass-border: rgba(255, 255, 255, 0.15);
            --glass-shadow: rgba(0, 0, 0, 0.8);
            --glass-highlight: rgba(255, 255, 255, 0.25);
            --glass-caustic: rgba(255, 255, 255, 0.05);
            --reflection-start: rgba(255, 255, 255, 0.15);
            --reflection-end: rgba(255, 255, 255, 0.0);
            --glare-color: rgba(255, 255, 255, 0.15);
            --pill-bg: rgba(60, 60, 65, 0.8);
            --pill-shadow: 0 4px 12px rgba(0,0,0,0.4), 0 1px 2px rgba(0,0,0,0.2), inset 0 1px 1px rgba(255,255,255,0.2);
            --icon-color: rgba(255, 255, 255, 0.5);
            --icon-active: #ffffff;
            --card-bg: rgba(30, 30, 35, 0.4);
            --text-primary: #f5f5f7;
            --text-secondary: #86868b;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "SF Pro Text", "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: transparent;
            -webkit-font-smoothing: antialiased;
            padding: 20px;
        }

        .bg-mesh {
            position: fixed;
            inset: 0;
            z-index: -1;
            background: var(--bg-color);
            transition: background 0.8s ease;
            overflow: hidden;
        }

        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(90px);
            opacity: var(--blob-opacity);
            animation: float 20s infinite alternate cubic-bezier(0.45, 0.05, 0.55, 0.95);
            will-change: transform;
            transition: background 0.8s ease, opacity 0.8s ease;
        }
        .blob-1 { width: 50vw; height: 50vw; top: -10%; left: -10%; background: #00bcd4; animation-delay: 0s; }
        .blob-2 { width: 45vw; height: 45vw; bottom: -10%; right: -10%; background: #00d4aa; animation-delay: -5s; }
        .blob-3 { width: 35vw; height: 35vw; top: 30%; left: 40%; background: #34c759; animation-delay: -10s; }

        @keyframes float {
            0% { transform: translate(0, 0) scale(1) rotate(0deg); }
            33% { transform: translate(5%, 10%) scale(1.05) rotate(5deg); }
            66% { transform: translate(-5%, 5%) scale(0.95) rotate(-5deg); }
            100% { transform: translate(0, -10%) scale(1.1) rotate(0deg); }
        }

        @keyframes fadeUp {
            0% { opacity: 0; transform: translateY(30px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        .auth-container {
            width: 100%;
            max-width: 480px;
            margin: 0 auto;
            position: relative;
            z-index: 10;
        }

        .auth-card {
            position: relative;
            padding: 40px;
            border-radius: 32px;
            background: var(--glass-bg);
            backdrop-filter: blur(50px) saturate(200%);
            -webkit-backdrop-filter: blur(50px) saturate(200%);
            box-shadow: 0 40px 80px -20px var(--glass-shadow), 0 10px 30px -10px var(--glass-shadow), inset 0 2px 3px -1px var(--glass-highlight), inset 0 -2px 4px -1px var(--glass-caustic), inset 0 0 0 1px var(--glass-border);
            transition: all 0.5s ease;
        }

        .auth-card::before {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            right: 2px;
            height: 40%;
            border-radius: 32px 32px 24px 24px / 32px 32px 12px 12px;
            background: linear-gradient(180deg, var(--reflection-start) 0%, var(--reflection-end) 100%);
            pointer-events: none;
        }

        .auth-title {
            font-size: 38px;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 8px;
            position: relative;
            z-index: 2;
            text-align: center;
            opacity: 0;
            animation: fadeUp 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) 0.1s forwards;
        }

        .auth-subtitle {
            font-size: 15px;
            color: var(--text-secondary);
            margin-bottom: 30px;
            position: relative;
            z-index: 2;
            text-align: center;
            opacity: 0;
            animation: fadeUp 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) 0.2s forwards;
        }

        .form-group {
            margin-bottom: 18px;
            position: relative;
            z-index: 2;
            opacity: 0;
            animation: fadeUp 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }

        .form-group:nth-child(1) { animation-delay: 0.3s; }
        .form-group:nth-child(2) { animation-delay: 0.4s; }
        .form-group:nth-child(3) { animation-delay: 0.5s; }
        .form-group:nth-child(4) { animation-delay: 0.6s; }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 6px;
        }

        .form-group label .required {
            color: #ff3b30;
            font-weight: 700;
            margin-left: 2px;
        }

        .form-group input,
        .form-group textarea {
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

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #007aff;
            box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.2);
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: var(--text-secondary);
            opacity: 0.6;
        }

        .form-group .error-text {
            display: none;
            color: #ff3b30;
            font-size: 13px;
            font-weight: 500;
            margin-top: 6px;
            padding-right: 4px;
        }

        .form-group.error input,
        .form-group.error textarea {
            border-color: #ff3b30 !important;
            box-shadow: 0 0 0 3px rgba(255, 59, 48, 0.15) !important;
        }

        .form-group.error .error-text {
            display: block;
        }

        .form-group .hint {
            color: var(--text-secondary);
            opacity: 0.6;
            font-size: 12px;
            margin-top: 4px;
        }

        .message {
            padding: 12px 16px;
            border-radius: 14px;
            margin-bottom: 20px;
            font-weight: 500;
            position: relative;
            z-index: 2;
            text-align: center;
            font-size: 14px;
            opacity: 0;
            animation: fadeUp 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) 0.15s forwards;
        }
        .message.success {
            background: rgba(52, 199, 89, 0.2);
            color: #34c759;
            border: 1px solid rgba(52, 199, 89, 0.3);
        }
        .message.error {
            background: rgba(255, 59, 48, 0.2);
            color: #ff3b30;
            border: 1px solid rgba(255, 59, 48, 0.3);
        }

        .auth-link {
            text-align: center;
            margin-top: 20px;
            position: relative;
            z-index: 2;
            color: var(--text-secondary);
            font-size: 14px;
            opacity: 0;
            animation: fadeUp 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) 0.8s forwards;
        }
        .auth-link a {
            color: #007aff;
            text-decoration: none;
            font-weight: 600;
        }
        .auth-link a:hover {
            text-decoration: underline;
        }

        /* ============================================ */
        /* دکمه هولوگرام روشن با فونت ExtraBlack */
        /* ============================================ */
        .cta-button-holo-light {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            width: 100%;
            padding: 22px 30px;
            font-size: 1.3rem;
            font-weight: 900;
            font-family: 'Dana', -apple-system, BlinkMacSystemFont, sans-serif;
            color: #006400;
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 2px solid rgba(255, 255, 255, 0.8);
            border-radius: 50px;
            cursor: pointer;
            position: relative;
            transition: all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
            text-shadow: 0 1px 10px rgba(0, 100, 0, 0.1);
            z-index: 2;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
            overflow: hidden;
            margin-top: 10px;
            opacity: 0;
            animation: fadeUp 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) 0.7s forwards;
        }

        .cta-button-holo-light .rainbow-light {
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            border-radius: 52px;
            background: conic-gradient(from 0deg, 
                #a8e063, #56d4b0, #f9d423, #a8e063, #56d4b0, #f9d423, #a8e063);
            background-size: 300% 300%;
            z-index: -1;
            animation: rainbowSpinLight 4s linear infinite;
            opacity: 0.3;
            transition: opacity 0.6s ease;
        }

        .cta-button-holo-light .rainbow-inner-light {
            position: absolute;
            top: 3px;
            left: 3px;
            right: 3px;
            bottom: 3px;
            border-radius: 47px;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            z-index: -1;
            transition: all 0.6s ease;
        }

        .cta-button-holo-light .holo-line-light {
            position: absolute;
            width: 200%;
            height: 60px;
            background: linear-gradient(90deg, 
                transparent, 
                rgba(255, 255, 255, 0.2), 
                rgba(86, 212, 176, 0.15),
                rgba(249, 212, 35, 0.15),
                rgba(168, 224, 99, 0.15),
                rgba(255, 255, 255, 0.2),
                transparent
            );
            transform: rotate(25deg);
            opacity: 0;
            transition: all 0.8s ease;
            pointer-events: none;
            z-index: 0;
            top: -30px;
            left: -50%;
            animation: holoMoveLight 3s linear infinite;
        }

        .cta-button-holo-light .holo-line-light:nth-child(4) {
            top: 20px;
            animation-delay: 0.5s;
        }

        .cta-button-holo-light .holo-line-light:nth-child(5) {
            top: 60px;
            animation-delay: 1s;
        }

        .cta-button-holo-light .icon-light {
            font-size: 1.8rem;
            transition: all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
            flex-shrink: 0;
            margin-left: 0;
        }

        .cta-button-holo-light:hover {
            transform: translateY(-3px) scale(1.03);
            box-shadow: 0 20px 50px -10px rgba(86, 212, 176, 0.3), 
                        0 0 60px rgba(249, 212, 35, 0.1);
            border-color: rgba(86, 212, 176, 0.3);
            color: #004d00;
        }

        .cta-button-holo-light:hover .rainbow-light {
            opacity: 0.7;
        }

        .cta-button-holo-light:active {
            transform: scale(0.95) !important;
            transition: all 0.15s ease !important;
        }

        .cta-button-holo-light:hover .icon-light {
            transform: rotate(-15deg) scale(1.1);
        }

        .cta-button-holo-light.expanded {
            padding: 22px 45px;
            transform: scale(1.03);
            box-shadow: 0 0 60px rgba(86, 212, 176, 0.15), 0 0 80px rgba(249, 212, 35, 0.08);
            color: #004d00;
        }

        .cta-button-holo-light.expanded .rainbow-light {
            opacity: 0.8;
            animation-duration: 2s;
        }

        .cta-button-holo-light.expanded .holo-line-light {
            opacity: 1;
        }

        .cta-button-holo-light.expanded .icon-light {
            animation: holoIconLight 1s ease;
        }

        /* ===== انیمیشن‌ها ===== */
        @keyframes rainbowSpinLight {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes holoMoveLight {
            0% { transform: rotate(25deg) translateX(-100px); opacity: 0; }
            30% { opacity: 0.6; }
            70% { opacity: 0.6; }
            100% { transform: rotate(25deg) translateX(100px); opacity: 0; }
        }

        @keyframes holoIconLight {
            0% { transform: scale(1); }
            25% { transform: scale(1.3) rotate(-15deg); }
            50% { transform: scale(1.1) rotate(10deg); }
            75% { transform: scale(1.2) rotate(-5deg); }
            100% { transform: scale(1) rotate(0deg); }
        }

        @media (max-width: 500px) {
            .auth-card {
                padding: 24px;
            }
            .auth-title {
                font-size: 30px;
            }
            .cta-button-holo-light {
                padding: 16px 20px;
                font-size: 1rem;
                gap: 2px;
            }
            .cta-button-holo-light .icon-light {
                font-size: 1.4rem;
            }
            .cta-button-holo-light.expanded {
                padding: 16px 30px;
            }
            .cta-button-holo-light .holo-line-light {
                display: none;
            }
        }
    </style>
</head>
<body>

    <div class="bg-mesh">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
    </div>

    <div class="auth-container">
        <div class="auth-card">
            <h1 class="auth-title">📝 ثبت‌نام</h1>
            <p class="auth-subtitle">برای شروع، اطلاعات خود را وارد کنید</p>

            <?php if ($error): ?>
                <div class="message error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="" id="registerForm" novalidate>
                <div class="form-group" id="nameGroup">
                    <label>نام و نام خانوادگی <span class="required">*</span></label>
                    <input type="text" name="full_name" id="fullName" placeholder="مثلاً: علی محمدی" required>
                    <div class="error-text">⚠️ لطفاً نام و نام خانوادگی خود را وارد کنید</div>
                </div>

                <div class="form-group" id="phoneGroup">
                    <label>شماره همراه <span class="required">*</span></label>
                    <input type="tel" name="phone" id="phone" placeholder="09123456789" required>
                    <div class="error-text">⚠️ لطفاً شماره همراه معتبر وارد کنید (۰۹۱۲۳۴۵۶۷۸۹)</div>
                    <div class="hint">شماره را بدون فاصله و با ۰۹ وارد کنید</div>
                </div>

                <div class="form-group">
                    <label>توانمندی / مهارت</label>
                    <textarea name="skill" placeholder="مثلاً: برنامه‌نویسی، طراحی، نویسندگی، ..."></textarea>
                </div>

                <div class="form-group">
                    <label>محدوده محل سکونت</label>
                    <input type="text" name="location" placeholder="مثلاً: حصارک، رجایی‌شهر، عظیمیه">
                </div>

                <button type="submit" class="cta-button-holo-light" id="submitBtn">
                    <span class="rainbow-light"></span>
                    <span class="rainbow-inner-light"></span>
                    <span class="holo-line-light"></span>
                    <span class="holo-line-light"></span>
                    <span class="holo-line-light"></span>
                    <span class="icon-light">✨</span>
                    ثبت‌نام و ورود
                </button>
            </form>

            <div class="auth-link">
                قبلاً ثبت‌نام کرده‌اید؟ <a href="login.php">وارد شوید</a>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            let isValid = true;

            const fullName = document.getElementById('fullName');
            const nameGroup = document.getElementById('nameGroup');
            if (fullName.value.trim() === '') {
                nameGroup.classList.add('error');
                isValid = false;
            } else {
                nameGroup.classList.remove('error');
            }

            const phone = document.getElementById('phone');
            const phoneGroup = document.getElementById('phoneGroup');
            const phonePattern = /^09[0-9]{9}$/;
            if (!phonePattern.test(phone.value.trim())) {
                phoneGroup.classList.add('error');
                isValid = false;
            } else {
                phoneGroup.classList.remove('error');
            }

            if (!isValid) {
                e.preventDefault();
                const firstError = document.querySelector('.form-group.error');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });

        document.querySelectorAll('#registerForm input, #registerForm textarea').forEach(function(input) {
            input.addEventListener('input', function() {
                const group = this.closest('.form-group');
                if (group) {
                    group.classList.remove('error');
                }
            });
        });

        document.getElementById('submitBtn').addEventListener('click', function(e) {
            const hasError = document.querySelector('.form-group.error');
            if (!hasError) {
                this.classList.add('expanded');
            }
        });
    </script>

</body>
</html>
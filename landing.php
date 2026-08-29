<?php
include 'includes/config.php';

if (isLoggedIn()) {
    header('Location: study.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>همقدم - عهد روزانه</title>
    <link rel="stylesheet" href="assets/css/style.css" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            min-height: 100vh;
        }

        body {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            font-family: 'Dana', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #e5e5ea;
            padding: 20px;
            direction: rtl;
        }

        .bg-gradient {
            position: fixed;
            inset: 0;
            z-index: -1;
            background: linear-gradient(145deg, #f5f5f7 0%, #e5e5ea 50%, #d1d1d6 100%);
        }

        .blob {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.25;
            z-index: -1;
            animation: floatBlob 15s ease-in-out infinite alternate;
        }
        .blob-1 {
            width: 450px;
            height: 450px;
            top: -150px;
            left: -150px;
            background: #00d4aa;
        }
        .blob-2 {
            width: 400px;
            height: 400px;
            bottom: -150px;
            right: -150px;
            background: #34c759;
            animation-delay: -5s;
        }
        .blob-3 {
            width: 350px;
            height: 350px;
            top: 40%;
            left: 50%;
            background: #00bcd4;
            animation-delay: -10s;
        }

        @keyframes floatBlob {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(40px, -40px) scale(1.1); }
        }

        .landing-container {
            width: 100%;
            max-width: 820px;
            margin: 0 auto;
            position: relative;
            z-index: 10;
            text-align: center;
            flex: 1;
            display: flex;
            align-items: center;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(40px) saturate(180%);
            -webkit-backdrop-filter: blur(40px) saturate(180%);
            border-radius: 48px;
            border: 1px solid rgba(255, 255, 255, 0.35);
            box-shadow: 0 30px 60px -20px rgba(0, 0, 0, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.5);
            padding: 50px 40px 60px;
            transition: all 0.3s ease;
            width: 100%;
        }

        /* ===== انیمیشن Fade Up ===== */
        @keyframes fadeUp {
            0% { opacity: 0; transform: translateY(30px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        .logo {
            width: 280px;
            height: 280px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 24px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
            border: 3px solid rgba(255, 255, 255, 0.4);
            display: block;
            margin-left: auto;
            margin-right: auto;
            opacity: 0;
            animation: fadeUp 0.9s cubic-bezier(0.34, 1.56, 0.64, 1) 0.1s forwards;
        }

        .hero-title {
            font-size: 42px;
            font-weight: 800;
            color: #147241;
            line-height: 1.5;
            margin-bottom: 10px;
            letter-spacing: -0.02em;
            opacity: 0;
            animation: fadeUp 0.9s cubic-bezier(0.34, 1.56, 0.64, 1) 0.3s forwards;
        }

        .hero-subtitle {
            font-size: 18px;
            font-weight: 400;
            color: #3a3a3c;
            margin-bottom: 35px;
            line-height: 1.6;
            opacity: 0;
            animation: fadeUp 0.9s cubic-bezier(0.34, 1.56, 0.64, 1) 0.5s forwards;
        }

        /* ===== دکمه ===== */
        .btn-wrapper {
            position: relative;
            display: inline-block;
            opacity: 0;
            animation: fadeUp 0.9s cubic-bezier(0.34, 1.56, 0.64, 1) 0.7s forwards;
        }

        .cta-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            padding: 22px 60px;
            font-size: 1.5rem;
            font-weight: 900;
            font-family: 'Dana', -apple-system, BlinkMacSystemFont, sans-serif;
            color: #fff;
            background: linear-gradient(135deg, #a8e063 0%, #56d4b0 40%, #f9d423 80%);
            background-size: 300% 300%;
            border: none;
            border-radius: 80px;
            cursor: pointer;
            position: relative;
            transition: padding 0.8s cubic-bezier(0.34, 1.2, 0.64, 1), 
                        background 0.8s cubic-bezier(0.34, 1.2, 0.64, 1),
                        box-shadow 0.8s cubic-bezier(0.34, 1.2, 0.64, 1);
            box-shadow: 0 15px 40px rgba(86, 212, 176, 0.35), 0 0 60px rgba(168, 224, 99, 0.15);
            letter-spacing: 1px;
            text-shadow: 0 2px 15px rgba(0, 0, 0, 0.2);
            z-index: 2;
            animation: float 3.5s ease-in-out infinite, gradientMove 7s ease-in-out infinite;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
            text-decoration: none;
            min-width: 280px;
        }

        .cta-button.expanded {
            padding-left: 95px !important;
            padding-right: 95px !important;
            background: linear-gradient(135deg, #6dd5a0 0%, #37b679 50%, #1e8f5e 100%) !important;
            background-size: 300% 300% !important;
            box-shadow: 0 15px 45px rgba(55, 182, 121, 0.5), 0 0 70px rgba(109, 213, 160, 0.25) !important;
        }

        .cta-button.expanded .icon {
            transform: translateX(-4px) rotate(-5deg) scale(0.98);
        }

        .cta-button .icon {
            font-size: 1.8rem;
            transition: transform 0.3s ease;
        }

        .cta-button:hover .icon {
            transform: translateX(-6px) rotate(-10deg) scale(1.1);
        }

        .cta-button:hover {
            transform: scale(1.05) translateY(-4px);
            box-shadow: 0 25px 55px rgba(86, 212, 176, 0.5), 0 0 80px rgba(168, 224, 99, 0.2);
            animation-play-state: paused;
        }

        .cta-button:active {
            transform: scale(0.96) !important;
            transition: all 0.15s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
        }

        @media (hover: none) {
            .cta-button:hover {
                transform: scale(1);
                animation-play-state: running;
            }
        }

        .cta-button::before {
            content: '';
            position: absolute;
            top: -10px;
            left: -10px;
            right: -10px;
            bottom: -10px;
            background: linear-gradient(135deg, #a8e063, #56d4b0, #f9d423, #a8e063);
            background-size: 400% 400%;
            border-radius: 100px;
            z-index: -1;
            filter: blur(30px);
            opacity: 0.4;
            animation: glowRotate 7s linear infinite;
        }

        .cta-button.expanded::before {
            background: linear-gradient(135deg, #6dd5a0, #37b679, #1e8f5e, #6dd5a0);
            background-size: 400% 400%;
            opacity: 0.6;
        }

        .cta-button::after {
            content: '';
            position: absolute;
            top: -5px;
            left: -5px;
            right: -5px;
            bottom: -5px;
            border-radius: 100px;
            border: 2.5px solid rgba(86, 212, 176, 0.25);
            z-index: -1;
            animation: pulseRing 2.8s ease-in-out infinite;
        }

        .cta-button.expanded::after {
            border-color: rgba(55, 182, 121, 0.4);
        }

        .orb {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
        }
        .orb1 {
            width: 22px;
            height: 22px;
            top: -28px;
            right: -22px;
            background: #a8e063;
            animation: orbFloat 4.5s ease-in-out infinite;
            filter: blur(8px);
            box-shadow: 0 0 30px #a8e063;
        }
        .orb2 {
            width: 32px;
            height: 32px;
            bottom: -32px;
            left: -28px;
            background: #56d4b0;
            animation: orbFloat 5.5s ease-in-out infinite 1.2s;
            filter: blur(10px);
            box-shadow: 0 0 40px #56d4b0;
        }
        .orb3 {
            width: 18px;
            height: 18px;
            top: -12px;
            left: 25%;
            background: #f9d423;
            animation: orbFloat 4s ease-in-out infinite 0.6s;
            filter: blur(6px);
            box-shadow: 0 0 25px #f9d423;
        }
        .orb4 {
            width: 28px;
            height: 28px;
            bottom: -18px;
            right: 18%;
            background: #56d4b0;
            animation: orbFloat 5s ease-in-out infinite 2.2s;
            filter: blur(9px);
            box-shadow: 0 0 35px #56d4b0;
        }

        .cta-button.expanded ~ .orb1 {
            background: #6dd5a0 !important;
            box-shadow: 0 0 30px #6dd5a0 !important;
        }
        .cta-button.expanded ~ .orb2 {
            background: #37b679 !important;
            box-shadow: 0 0 40px #37b679 !important;
        }
        .cta-button.expanded ~ .orb3 {
            background: #1e8f5e !important;
            box-shadow: 0 0 25px #1e8f5e !important;
        }
        .cta-button.expanded ~ .orb4 {
            background: #37b679 !important;
            box-shadow: 0 0 35px #37b679 !important;
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-14px); }
            100% { transform: translateY(0px); }
        }

        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes glowRotate {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes pulseRing {
            0% { transform: scale(1); opacity: 0.7; border-color: rgba(86, 212, 176, 0.25); }
            50% { border-color: rgba(249, 212, 35, 0.3); }
            100% { transform: scale(1.3); opacity: 0; border-color: rgba(168, 224, 99, 0.1); }
        }

        @keyframes orbFloat {
            0% { transform: translate(0, 0) scale(1); opacity: 0.7; }
            33% { transform: translate(18px, -24px) scale(1.5); opacity: 1; }
            66% { transform: translate(-14px, 18px) scale(0.7); opacity: 0.5; }
            100% { transform: translate(0, 0) scale(1); opacity: 0.7; }
        }

        .footer {
            width: 100%;
            text-align: center;
            padding: 24px 20px;
            font-size: 14px;
            color: #8e8e93;
            font-weight: 400;
            position: relative;
            z-index: 10;
            margin-top: 20px;
            opacity: 0;
            animation: fadeUp 0.9s cubic-bezier(0.34, 1.56, 0.64, 1) 0.9s forwards;
        }
        .footer .heart {
            color: #ff3b30;
            display: inline-block;
            animation: heartbeat 1.5s ease-in-out infinite;
        }
        @keyframes heartbeat {
            0%, 100% { transform: scale(1); }
            30% { transform: scale(1.2); }
            60% { transform: scale(1); }
        }

        @media (max-width: 640px) {
            .glass-card {
                padding: 30px 20px 40px;
                border-radius: 32px;
            }
            .logo {
                width: 220px;
                height: 220px;
            }
            .hero-title {
                font-size: 28px;
            }
            .hero-subtitle {
                font-size: 15px;
            }
            .cta-button {
                padding: 16px 36px;
                font-size: 1.15rem;
                min-width: 200px;
            }
            .cta-button .icon {
                font-size: 1.4rem;
            }
            .cta-button.expanded {
                padding-left: 60px !important;
                padding-right: 60px !important;
            }
            .orb1, .orb2, .orb3, .orb4 {
                display: none;
            }
            .cta-button::before {
                filter: blur(20px);
                opacity: 0.3;
            }
            .footer {
                font-size: 12px;
                padding: 16px;
            }
        }

        @media (max-width: 400px) {
            .logo {
                width: 180px !important;
                height: 180px !important;
            }
            .hero-title {
                font-size: 22px;
            }
            .cta-button {
                padding: 14px 28px;
                font-size: 1rem;
                min-width: 160px;
            }
            .cta-button .icon {
                font-size: 1.2rem;
            }
            .cta-button.expanded {
                padding-left: 48px !important;
                padding-right: 48px !important;
            }
        }
    </style>
</head>
<body>

    <div class="bg-gradient"></div>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>

    <div class="landing-container">
        <div class="glass-card">

            <img src="assets/images/pblogo.jpg" alt="لوگو" class="logo" />

            <h1 class="hero-title">
                همقدم با هم، در جهت<br />
                احیای واجبِ فراموش شده
            </h1>

            <p class="hero-subtitle">
                عهد روزانه ۲ دقیقه مطالعه و معرفت‌افزایی
            </p>

            <div class="btn-wrapper">
                <button class="cta-button" id="landingCta"><span class="icon">🌿</span>شروع قدم بـرداشتن</button>
                <span class="orb orb1"></span>
                <span class="orb orb2"></span>
                <span class="orb orb3"></span>
                <span class="orb orb4"></span>
            </div>

        </div>
    </div>

    <div class="footer">
        طراحی شده با <span class="heart">❤️</span> توسط گروه پیشروان بصیرت
    </div>

    <script>
        document.getElementById('landingCta').addEventListener('click', function(e) {
            e.preventDefault();
            this.classList.add('expanded');
            setTimeout(function() {
                window.location.href = 'register.php';
            }, 1000);
        });
    </script>

</body>
</html>
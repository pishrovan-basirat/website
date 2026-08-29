<?php
include 'includes/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user = getUser($pdo);

// ===== گرفتن یا ایجاد رکورد مطالعه امروز =====
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT * FROM user_daily_study WHERE user_id = ? AND study_date = ?");
$stmt->execute([$_SESSION['user_id'], $today]);
$study_record = $stmt->fetch();

if (!$study_record) {
    $stmt = $pdo->prepare("INSERT INTO user_daily_study (user_id, study_date, study_duration, is_completed) VALUES (?, ?, 0, 0)");
    $stmt->execute([$_SESSION['user_id'], $today]);
    $study_record = [
        'id' => $pdo->lastInsertId(),
        'study_duration' => 0,
        'is_completed' => 0
    ];
}

// ===== گرفتن متن مطالعه =====
$stmt = $pdo->query("SELECT * FROM study_content ORDER BY id DESC LIMIT 1");
$content = $stmt->fetch();
$study_text = $content['content'] ?? 'هنوز متنی برای مطالعه تعیین نشده است. لطفاً بعداً مراجعه کنید.';

// ===== تبدیل تاریخ میلادی به شمسی =====
function toJalali($date) {
    $timestamp = strtotime($date);
    $year = date('Y', $timestamp);
    $month = date('m', $timestamp);
    $day = date('d', $timestamp);
    $jalali_year = $year - 621;
    return $jalali_year . '/' . $month . '/' . $day;
}

$jalali_date = toJalali($today);
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مطالعه</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* ===== ریست ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Dana', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #e5e5ea;
            padding: 20px;
            direction: rtl;
        }

        /* ===== پس‌زمینه گرادیان ===== */
        .bg-gradient {
            position: fixed;
            inset: 0;
            z-index: -1;
            background: linear-gradient(135deg, #f5f5f7 0%, #e5e5ea 50%, #d1d1d6 100%);
        }

        /* ===== حباب‌های رنگی ===== */
        .blob {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.3;
            z-index: -1;
            animation: floatBlob 15s ease-in-out infinite alternate;
        }
        .blob-1 {
            width: 400px;
            height: 400px;
            top: -100px;
            left: -100px;
            background: #00bcd4;
        }
        .blob-2 {
            width: 350px;
            height: 350px;
            bottom: -100px;
            right: -100px;
            background: #00d4aa;
            animation-delay: -5s;
        }
        .blob-3 {
            width: 300px;
            height: 300px;
            top: 40%;
            left: 50%;
            background: #34c759;
            animation-delay: -10s;
        }

        @keyframes floatBlob {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(30px, -30px) scale(1.1); }
        }

        /* ===== کانتینر اصلی ===== */
        .study-container {
            width: 100%;
            max-width: 820px;
            margin: 0 auto;
            position: relative;
            z-index: 10;
        }

        /* ===== کارت شیشه‌ای ===== */
        .glass-card {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(40px) saturate(180%);
            -webkit-backdrop-filter: blur(40px) saturate(180%);
            border-radius: 36px;
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 
                0 30px 60px -20px rgba(0, 0, 0, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.6);
            padding: 40px;
            transition: all 0.3s ease;
        }

        /* ===== انیمیشن Fade Up ===== */
        @keyframes fadeUp {
            0% { opacity: 0; transform: translateY(30px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        @keyframes rainbowSpinBlue {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes holoMoveBlue {
            0% { transform: rotate(25deg) translateX(-100px); opacity: 0; }
            30% { opacity: 0.6; }
            70% { opacity: 0.6; }
            100% { transform: rotate(25deg) translateX(100px); opacity: 0; }
        }

        @keyframes holoIconBlue {
            0% { transform: scale(1); }
            25% { transform: scale(1.3) rotate(-15deg); }
            50% { transform: scale(1.1) rotate(10deg); }
            75% { transform: scale(1.2) rotate(-5deg); }
            100% { transform: scale(1) rotate(0deg); }
        }

        /* ===== هدر کاربر ===== */
        .user-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 18px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
            flex-wrap: wrap;
            gap: 12px;
            opacity: 0;
            animation: fadeUp 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) 0.1s forwards;
        }
        .user-info {
            font-size: 16px;
            color: #1c1c1e;
            font-weight: 400;
        }
        .user-info strong {
            font-weight: 700;
            color: #000;
        }
        .logout-btn {
            color: #ff3b30;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            padding: 4px 14px;
            border-radius: 99px;
            background: rgba(255, 255, 255, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
            flex-shrink: 0;
        }
        .logout-btn:hover {
            background: rgba(255, 59, 48, 0.15);
            border-color: #ff3b30;
        }

        /* ===== هدر چسبان با تایمر ===== */
        .sticky-header {
            position: sticky;
            top: 0;
            z-index: 50;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border-radius: 0;
            padding: 10px 24px;
            margin-bottom: 25px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            opacity: 0;
            animation: fadeUp 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) 0.05s forwards;
        }
        .timer-badge {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.4);
            padding: 6px 22px;
            border-radius: 99px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            font-size: 14px;
            transition: all 0.5s ease;
        }
        .timer-badge .icon {
            font-size: 18px;
        }
        .timer-badge .time {
            font-weight: 700;
            font-size: 20px;
            color: #1c1c1e;
            font-variant-numeric: tabular-nums;
            min-width: 60px;
            text-align: center;
            transition: color 0.5s ease;
        }
        .timer-badge .label {
            font-size: 13px;
            color: #3a3a3c;
            font-weight: 400;
        }
        .timer-badge .check-icon {
            display: none;
            font-size: 22px;
            color: #34c759;
            animation: popIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .timer-badge.completed {
            background: rgba(52, 199, 89, 0.15);
            border-color: #34c759;
            box-shadow: 0 0 30px rgba(52, 199, 89, 0.15);
        }
        .timer-badge.completed .time {
            color: #34c759;
        }
        .timer-badge.completed .check-icon {
            display: inline-block;
        }

        @keyframes popIn {
            0% { transform: scale(0) rotate(-20deg); }
            100% { transform: scale(1) rotate(0deg); }
        }

        /* ===== عنوان با تاریخ ===== */
        .study-title {
            font-size: 30px;
            font-weight: 800;
            color: #1c1c1e;
            margin-bottom: 6px;
            letter-spacing: -0.02em;
            opacity: 0;
            animation: fadeUp 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) 0.2s forwards;
        }
        .study-title .date-badge {
            font-size: 18px;
            font-weight: 600;
            color: #007aff;
            background: rgba(0, 122, 255, 0.1);
            padding: 2px 16px;
            border-radius: 99px;
            display: inline-block;
            margin-right: 10px;
        }
        .study-subtitle {
            font-size: 16px;
            color: #3a3a3c;
            margin-bottom: 25px;
            opacity: 0;
            animation: fadeUp 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) 0.3s forwards;
        }

        /* ===== متن مطالعه ===== */
        .study-text {
            font-size: 18px;
            line-height: 2.4;
            color: #1c1c1e;
            font-weight: 500;
            white-space: pre-wrap;
            padding: 0;
            margin: 0;
            background: transparent;
            border: none;
            box-shadow: none;
            border-radius: 0;
            opacity: 0;
            animation: fadeUp 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) 0.4s forwards;
        }

        /* ============================================ */
        /* دکمه شروع خودآزمایی (آبی-فیروزه‌ای) */
        /* ============================================ */
        .cta-exam-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            padding: 18px 40px;
            margin-top: 28px;
            font-size: 1.2rem;
            font-weight: 900;
            font-family: 'Dana', -apple-system, BlinkMacSystemFont, sans-serif;
            color: #005C8A;
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 2px solid rgba(255, 255, 255, 0.8);
            border-radius: 50px;
            cursor: pointer;
            position: relative;
            transition: all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
            text-shadow: 0 1px 10px rgba(0, 92, 138, 0.1);
            z-index: 2;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
            overflow: hidden;
            text-decoration: none;
            min-width: 220px;
            opacity: 0;
            animation: fadeUp 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) 0.5s forwards;
        }

        .cta-exam-btn .rainbow-blue {
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            border-radius: 52px;
            background: conic-gradient(from 0deg, 
                #007aff, #00d4aa, #0a84ff, #5ac8fa, #007aff, #00d4aa, #007aff);
            background-size: 300% 300%;
            z-index: -1;
            animation: rainbowSpinBlue 4s linear infinite;
            opacity: 0.25;
            transition: opacity 0.6s ease;
        }

        .cta-exam-btn .rainbow-inner-blue {
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

        .cta-exam-btn .holo-line-blue {
            position: absolute;
            width: 200%;
            height: 60px;
            background: linear-gradient(90deg, 
                transparent, 
                rgba(255, 255, 255, 0.2), 
                rgba(0, 122, 255, 0.2),
                rgba(0, 212, 170, 0.15),
                rgba(90, 200, 250, 0.2),
                transparent
            );
            transform: rotate(25deg);
            opacity: 0;
            transition: all 0.8s ease;
            pointer-events: none;
            z-index: 0;
            top: -30px;
            left: -50%;
            animation: holoMoveBlue 3s linear infinite;
        }

        .cta-exam-btn .holo-line-blue:nth-child(4) {
            top: 20px;
            animation-delay: 0.5s;
        }

        .cta-exam-btn .holo-line-blue:nth-child(5) {
            top: 60px;
            animation-delay: 1s;
        }

        .cta-exam-btn .icon-blue {
            font-size: 1.6rem;
            transition: all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
            flex-shrink: 0;
            margin-left: 0;
        }

        .cta-exam-btn:hover {
            transform: translateY(-3px) scale(1.03);
            box-shadow: 0 20px 50px -10px rgba(0, 122, 255, 0.3), 
                        0 0 60px rgba(0, 212, 170, 0.1);
            border-color: rgba(0, 122, 255, 0.3);
            color: #004466;
        }

        .cta-exam-btn:hover .rainbow-blue {
            opacity: 0.6;
        }

        .cta-exam-btn:active {
            transform: scale(0.95) !important;
            transition: all 0.15s ease !important;
        }

        .cta-exam-btn:hover .icon-blue {
            transform: rotate(-15deg) scale(1.1);
        }

        .cta-exam-btn.expanded {
            padding: 18px 50px;
            transform: scale(1.03);
            box-shadow: 0 0 60px rgba(0, 122, 255, 0.15), 0 0 80px rgba(0, 212, 170, 0.08);
            color: #004466;
        }

        .cta-exam-btn.expanded .rainbow-blue {
            opacity: 0.8;
            animation-duration: 2s;
        }

        .cta-exam-btn.expanded .holo-line-blue {
            opacity: 1;
        }

        .cta-exam-btn.expanded .icon-blue {
            animation: holoIconBlue 1s ease;
        }

        .btn-wrapper {
            text-align: center;
        }

        /* ===== ریسپانسیو ===== */
        @media (max-width: 640px) {
            .glass-card {
                padding: 24px;
                border-radius: 28px;
            }
            .study-title {
                font-size: 24px;
            }
            .study-title .date-badge {
                font-size: 14px;
                padding: 0 12px;
                display: inline-block;
                margin-right: 6px;
            }
            .study-text {
                font-size: 16px;
                line-height: 2.2;
            }
            .sticky-header {
                padding: 8px 16px;
                border-radius: 0;
                margin-left: -10px;
                margin-right: -10px;
            }
            .timer-badge {
                padding: 4px 16px;
            }
            .timer-badge .time {
                font-size: 17px;
                min-width: 50px;
            }
            .cta-exam-btn {
                padding: 14px 24px;
                font-size: 1rem;
                min-width: 100%;
                gap: 2px;
                justify-content: center;
                width: 100%;
                margin-top: 20px;
            }
            .cta-exam-btn .icon-blue {
                font-size: 1.3rem;
            }
            .cta-exam-btn.expanded {
                padding: 14px 32px;
            }
            .cta-exam-btn .holo-line-blue {
                display: none;
            }
            .user-header {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                gap: 8px;
            }
            .user-info {
                font-size: 14px;
            }
            .logout-btn {
                font-size: 12px;
                padding: 3px 12px;
            }
            .blob-1 { width: 250px; height: 250px; }
            .blob-2 { width: 200px; height: 200px; }
            .blob-3 { width: 180px; height: 180px; }
        }

        @media (max-width: 400px) {
            .study-title .date-badge {
                font-size: 12px;
                padding: 0 8px;
            }
        }
    </style>
</head>
<body>

    <!-- ===== پس‌زمینه ===== -->
    <div class="bg-gradient"></div>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>

    <!-- ===== کانتینر اصلی ===== -->
    <div class="study-container">

        <!-- ===== هدر چسبان با تایمر ===== -->
        <div class="sticky-header">
            <div class="timer-badge" id="timerBadge" data-completed="<?= $study_record['is_completed'] ?>">
                <span class="icon">⏱️</span>
                <span class="time" id="timer">۰۰:۰۰</span>
                <span class="label">حداقل زمان مطالعه امروز</span>
                <span class="check-icon">✅</span>
            </div>
        </div>

        <!-- ===== کارت اصلی ===== -->
        <div class="glass-card">

            <!-- ===== هدر کاربر ===== -->
            <div class="user-header">
                <div class="user-info">
                    👋 خوش آمدی، <strong><?= htmlspecialchars($user['full_name']) ?> عزیز</strong>
                </div>
                <a href="logout.php" class="logout-btn">🚪 خروج</a>
            </div>

            <!-- ===== عنوان با تاریخ ===== -->
            <h1 class="study-title">
                📖 مطالعه امروز
                <span class="date-badge"><?= $jalali_date ?></span>
            </h1>
            <p class="study-subtitle">متن زیر را با دقت مطالعه کنید، سپس وارد خودآزمایی شوید.</p>

            <!-- ===== متن مطالعه ===== -->
            <div class="study-text"><?= nl2br(htmlspecialchars($study_text)) ?></div>

            <!-- ===== دکمه شروع خودآزمایی ===== -->
            <div class="btn-wrapper">
                <a href="exam.php" class="cta-exam-btn" id="examBtn">
                    <span class="rainbow-blue"></span>
                    <span class="rainbow-inner-blue"></span>
                    <span class="holo-line-blue"></span>
                    <span class="holo-line-blue"></span>
                    <span class="holo-line-blue"></span>
                    <span class="icon-blue">🚀</span>
                    شروع خودآزمایی
                </a>
            </div>

        </div>
    </div>

    <script>
        // ===== تایمر =====
        const isCompleted = <?= $study_record['is_completed'] ? 'true' : 'false' ?>;
        const studyId = <?= $study_record['id'] ?>;
        const savedDuration = <?= (int)$study_record['study_duration'] ?>;
        const timerBadge = document.getElementById('timerBadge');
        const timerElement = document.getElementById('timer');

        let seconds = savedDuration;
        let timerInterval = null;

        function updateTimerDisplay() {
            const mins = String(Math.floor(seconds / 60)).padStart(2, '0');
            const secs = String(seconds % 60).padStart(2, '0');
            timerElement.textContent = `${mins}:${secs}`;
            
            if (seconds >= 120) {
                timerBadge.classList.add('completed');
                timerElement.textContent = '۰۲:۰۰';
                if (!isCompleted) {
                    fetch('save_study_time.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ study_id: studyId, duration: 120 })
                    });
                }
                stopTimer();
                return true;
            }
            return false;
        }

        function tick() {
            if (seconds < 120) {
                seconds++;
                updateTimerDisplay();
            } else {
                stopTimer();
            }
        }

        function startTimer() {
            if (timerInterval) return;
            if (seconds >= 120) return;
            timerInterval = setInterval(tick, 1000);
        }

        function stopTimer() {
            if (timerInterval) {
                clearInterval(timerInterval);
                timerInterval = null;
            }
        }

        function saveCurrentTime() {
            if (seconds > 0 && !isCompleted) {
                fetch('save_study_time.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ study_id: studyId, duration: seconds })
                });
            }
        }

        if (isCompleted) {
            seconds = 120;
            timerBadge.classList.add('completed');
            timerElement.textContent = '۰۲:۰۰';
        } else {
            updateTimerDisplay();
            startTimer();
        }

        window.addEventListener('beforeunload', function() {
            stopTimer();
            saveCurrentTime();
        });

        document.getElementById('examBtn').addEventListener('click', function(e) {
            e.preventDefault();
            stopTimer();
            saveCurrentTime();
            window.location.href = this.href;
        });

        document.querySelector('.theme-btn')?.addEventListener('click', function() {
            const root = document.documentElement;
            const isDark = root.getAttribute('data-theme') === 'dark';
            root.setAttribute('data-theme', isDark ? 'light' : 'dark');
        });
    </script>
</body>
</html>
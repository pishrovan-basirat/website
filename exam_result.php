<?php
include 'includes/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user = getUser($pdo);

// ===== گرفتن آخرین تلاش امروز با DATE(attempted_at) =====
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT * FROM user_exam_attempts WHERE user_id = ? AND DATE(attempted_at) = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id'], $today]);
$attempt = $stmt->fetch();

if (!$attempt) {
    header('Location: exam.php');
    exit();
}

$passed = $attempt['passed'];
$score = $attempt['score'];

function optionLabel($num) {
    $labels = ['الف', 'ب', 'پ', 'ت'];
    return $labels[$num - 1] ?? $num;
}

// ===== گرفتن پاسخ‌ها =====
$stmt = $pdo->prepare("
    SELECT uea.*, eq.question_text, eq.correct_answer,
           eq.option_1, eq.option_2, eq.option_3, eq.option_4,
           CASE eq.correct_answer
               WHEN 1 THEN eq.option_1
               WHEN 2 THEN eq.option_2
               WHEN 3 THEN eq.option_3
               WHEN 4 THEN eq.option_4
           END AS correct_answer_text,
           CASE uea.selected_answer
               WHEN 1 THEN eq.option_1
               WHEN 2 THEN eq.option_2
               WHEN 3 THEN eq.option_3
               WHEN 4 THEN eq.option_4
           END AS selected_answer_text
    FROM user_exam_answers uea
    JOIN exam_questions eq ON uea.question_id = eq.id
    WHERE uea.attempt_id = ?
");
$stmt->execute([$attempt['id']]);
$answers = $stmt->fetchAll();

$total = count($answers);
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نتیجه خودآزمایی</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { min-height: 100vh; display: flex; justify-content: center; align-items: center; font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "SF Pro Text", "Segoe UI", Roboto, Helvetica, Arial, sans-serif; padding: 20px; }
        .result-container { max-width: 700px; width: 100%; }
        .user-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid var(--divider); flex-wrap: wrap; gap: 10px; }
        .user-info { color: var(--text-secondary); font-size: 15px; }
        .user-info strong { color: var(--text-primary); }
        .logout-btn { color: #ff3b30; text-decoration: none; font-weight: 600; font-size: 14px; padding: 6px 16px; border-radius: 99px; background: var(--card-bg); border: 1px solid var(--divider); transition: all 0.3s ease; }
        .logout-btn:hover { background: rgba(255, 59, 48, 0.1); border-color: #ff3b30; }

        .success-wrapper { text-align: center; padding: 20px 0 30px 0; }
        .success-check {
            display: inline-block;
            width: 120px; height: 120px; border-radius: 50%;
            background: linear-gradient(135deg, #34c759, #28a745);
            box-shadow: 0 20px 60px -10px rgba(52, 199, 89, 0.5);
            animation: popIn 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
            transform: scale(0);
            position: relative;
        }
        .success-check svg {
            position: absolute; top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            width: 60px; height: 60px;
            stroke: white; stroke-width: 4; fill: none;
            stroke-linecap: round; stroke-linejoin: round;
            stroke-dasharray: 100; stroke-dashoffset: 100;
            animation: drawCheck 0.6s ease 0.4s forwards;
        }
        @keyframes popIn { 0% { transform: scale(0) rotate(-10deg); } 60% { transform: scale(1.15) rotate(2deg); } 100% { transform: scale(1) rotate(0deg); } }
        @keyframes drawCheck { 0% { stroke-dashoffset: 100; } 100% { stroke-dashoffset: 0; } }

        .success-title { font-size: 30px; font-weight: 800; color: #34c759; margin-top: 20px; animation: fadeUp 0.8s ease 0.6s both; }
        .success-subtitle { font-size: 18px; font-weight: 600; color: var(--text-primary); margin-top: 8px; animation: fadeUp 0.8s ease 0.8s both; }
        @keyframes fadeUp { 0% { opacity: 0; transform: translateY(20px); } 100% { opacity: 1; transform: translateY(0); } }

        .fail-wrapper { text-align: center; padding: 40px 20px; }
        .fail-icon { font-size: 80px; animation: shake 0.6s ease; }
        @keyframes shake {
            0%, 100% { transform: rotate(0deg); }
            20% { transform: rotate(-15deg); }
            40% { transform: rotate(15deg); }
            60% { transform: rotate(-10deg); }
            80% { transform: rotate(10deg); }
        }
        .fail-title { font-size: 26px; font-weight: 800; color: #ff3b30; margin-top: 20px; }
        .fail-subtitle { font-size: 16px; color: var(--text-secondary); margin-top: 8px; max-width: 400px; margin-left: auto; margin-right: auto; }

        .btn-group {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            margin-top: 25px;
        }
        .btn {
            display: inline-block;
            padding: 14px 40px;
            border: none;
            border-radius: 99px;
            font-family: inherit;
            font-size: 17px;
            font-weight: 600;
            cursor: pointer;
            outline: none;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.34, 1.2, 0.64, 1);
            min-width: 200px;
            text-align: center;
        }
        .btn:hover { transform: scale(1.04); }
        .btn:active { transform: scale(0.96); }
        .btn-primary {
            color: #ffffff;
            background: linear-gradient(135deg, #007aff, #0055b3);
            box-shadow: 0 10px 30px -10px rgba(0, 122, 255, 0.4);
        }
        .btn-primary:hover { box-shadow: 0 15px 40px -12px rgba(0, 122, 255, 0.5); }
        .btn-orange {
            color: #ffffff;
            background: linear-gradient(135deg, #ff9500, #e68a00);
            box-shadow: 0 10px 30px -10px rgba(255, 149, 0, 0.4);
        }
        .btn-orange:hover { box-shadow: 0 15px 40px -12px rgba(255, 149, 0, 0.5); }

        .score-box {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin: 15px 0;
            flex-wrap: wrap;
        }
        .score-item {
            text-align: center;
            padding: 10px 20px;
            background: var(--card-bg);
            border-radius: 14px;
            border: 1px solid var(--glass-border);
        }
        .score-item .number { font-size: 28px; font-weight: 800; }
        .score-item .number.correct { color: #34c759; }
        .score-item .number.wrong { color: #ff3b30; }
        .score-item .label { font-size: 13px; color: var(--text-secondary); }

        .answers-section { margin-top: 30px; }
        .answers-section h3 { font-size: 18px; font-weight: 700; color: var(--text-primary); margin-bottom: 15px; }
        .answer-item {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 18px 20px;
            margin-bottom: 12px;
            border: 1px solid var(--glass-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .answer-item .q-text { font-size: 15px; font-weight: 600; color: var(--text-primary); flex: 1; min-width: 200px; }
        .answer-item .q-result { display: flex; align-items: center; gap: 15px; flex-wrap: wrap; }
        .badge { padding: 4px 12px; border-radius: 99px; font-size: 13px; font-weight: 600; }
        .badge.correct { background: rgba(52, 199, 89, 0.2); color: #34c759; }
        .badge.wrong { background: rgba(255, 59, 48, 0.2); color: #ff3b30; }
        .badge.correct-answer { background: rgba(0, 122, 255, 0.15); color: #007aff; font-size: 12px; }

        .option-letter {
            display: inline-block;
            width: 22px;
            height: 22px;
            line-height: 22px;
            text-align: center;
            background: rgba(0, 122, 255, 0.1);
            border-radius: 50%;
            font-weight: 700;
            color: #007aff;
            font-size: 12px;
            margin-left: 4px;
        }

        .back-link { color: #007aff; text-decoration: none; font-weight: 600; font-size: 14px; display: inline-flex; align-items: center; gap: 5px; margin-top: 20px; }
        .back-link:hover { text-decoration: underline; }

        @media (max-width: 600px) {
            .answer-item { flex-direction: column; align-items: flex-start; }
            .score-box { gap: 15px; }
            .success-check { width: 90px; height: 90px; }
            .success-check svg { width: 45px; height: 45px; }
            .success-title { font-size: 30px; }
            .success-subtitle { font-size: 15px; }
            .fail-icon { font-size: 60px; }
            .fail-title { font-size: 22px; }
            .btn { min-width: 100%; }
        }
    </style>
</head>
<body>

    <div class="bg-mesh"><div class="blob blob-1"></div><div class="blob blob-2"></div><div class="blob blob-3"></div></div>

    <div class="result-container">
        <div class="auth-card">
            <div class="user-header">
                <div class="user-info">👋 خوش آمدی، <strong><?= htmlspecialchars($user['full_name']) ?> عزیز</strong></div>
                <a href="logout.php" class="logout-btn">🚪 خروج</a>
            </div>

            <?php if ($passed): ?>
                <div class="success-wrapper">
                    <div class="success-check">
                        <svg viewBox="0 0 24 24">
                            <polyline points="4 12 10 18 20 6"></polyline>
                        </svg>
                    </div>
                    <div class="success-title">🎉 خداقوت!</div>
                    <div class="success-subtitle">
                        خودسازی امروز برای سربازی امام زمان(عج) با موفقیت انجام شد✅
                    </div>
                </div>

                <div class="score-box">
                    <div class="score-item">
                        <div class="number correct"><?= $score ?></div>
                        <div class="label">✅ پاسخ درست</div>
                    </div>
                    <div class="score-item">
                        <div class="number wrong"><?= $total - $score ?></div>
                        <div class="label">❌ پاسخ غلط</div>
                    </div>
                    <div class="score-item">
                        <div class="number" style="color: var(--text-primary);"><?= $total ?></div>
                        <div class="label">📋 مجموع سوالات</div>
                    </div>
                </div>

                <div class="answers-section">
                    <h3>📋 پاسخ‌های شما</h3>
                    <?php foreach ($answers as $index => $ans): ?>
                        <div class="answer-item">
                            <div class="q-text"><?= ($index + 1) ?>. <?= htmlspecialchars($ans['question_text']) ?></div>
                            <div class="q-result">
                                <span>
                                    پاسخ شما: 
                                    <span class="option-letter"><?= optionLabel($ans['selected_answer']) ?></span>
                                    <?= htmlspecialchars($ans['selected_answer_text']) ?>
                                </span>
                                <?php if ($ans['is_correct']): ?>
                                    <span class="badge correct">✅ درست</span>
                                <?php else: ?>
                                    <span class="badge wrong">❌ غلط</span>
                                    <span class="badge correct-answer">
                                        ✅ پاسخ صحیح: 
                                        <span class="option-letter"><?= optionLabel($ans['correct_answer']) ?></span>
                                        <?= htmlspecialchars($ans['correct_answer_text']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <a href="study.php" class="back-link">📖 مطالعه مجدد متن</a>

            <?php else: ?>
                <div class="fail-wrapper">
                    <div class="fail-icon">😔</div>
                    <div class="fail-title">متأسفانه قبول نشدید!</div>
                    <div class="fail-subtitle">
                        برای قبولی باید حداقل ۳ سوال از ۵ سوال را درست پاسخ دهید.
                    </div>
                    <div class="btn-group">
                        <a href="exam.php" class="btn btn-primary">خودآزمایی دوباره</a>
                        <a href="study.php" class="btn btn-orange">📖 مطالعه مجدد متن</a>
                    </div>
                </div>
            <?php endif; ?>

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
<?php
include 'includes/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user = getUser($pdo);

// ===== چک کردن قبولی امروز =====
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT * FROM user_exam_attempts WHERE user_id = ? AND passed = 1 AND DATE(attempted_at) = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id'], $today]);
$already_passed_today = $stmt->fetch();

if ($already_passed_today) {
    header('Location: exam_result.php');
    exit();
}

// ===== گرفتن ۵ سوال جدیدترین =====
$stmt = $pdo->query("SELECT * FROM exam_questions ORDER BY id DESC LIMIT 5");
$questions = $stmt->fetchAll();

// ===== مرتب کردن به ترتیب صعودی (۱ تا ۵) =====
$questions = array_reverse($questions);

$error = '';
if (count($questions) < 5) {
    $error = "❌ تعداد سوالات کمتر از ۵ است. لطفاً با مدیر سایت تماس بگیرید.";
}

// ===== پردازش فرم (بدون isset) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $answers = $_POST['answers'] ?? [];
    $correct_count = 0;
    
    foreach ($questions as $q) {
        $selected = (int)($answers[$q['id']] ?? 0);
        if ($selected === (int)$q['correct_answer']) {
            $correct_count++;
        }
    }
    
    $score = $correct_count;
    $passed = ($score >= 3) ? 1 : 0;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO user_exam_attempts (user_id, score, passed) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $score, $passed]);
        $attempt_id = $pdo->lastInsertId();
        
        foreach ($questions as $q) {
            $selected = (int)($answers[$q['id']] ?? 0);
            $is_correct = ($selected === (int)$q['correct_answer']) ? 1 : 0;
            $stmt = $pdo->prepare("INSERT INTO user_exam_answers (attempt_id, question_id, selected_answer, is_correct) VALUES (?, ?, ?, ?)");
            $stmt->execute([$attempt_id, $q['id'], $selected, $is_correct]);
        }
        
        header('Location: exam_result.php');
        exit();
    } catch(PDOException $e) {
        $error = '❌ خطا در ذخیره: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>خودآزمایی</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { min-height: 100vh; display: flex; justify-content: center; align-items: flex-start; padding: 20px; padding-top: 40px; font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "SF Pro Text", "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
        .exam-container { max-width: 750px; width: 100%; }
        .sticky-header {
            position: sticky;
            top: 0;
            z-index: 50;
            background: var(--glass-bg);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border-radius: 20px 20px 0 0;
            padding: 12px 20px;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--glass-border);
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .sticky-link {
            font-size: 13px;
            color: #007aff;
            text-decoration: none;
            font-weight: 600;
            padding: 6px 16px;
            border-radius: 99px;
            background: rgba(0, 122, 255, 0.08);
            border: 1px solid rgba(0, 122, 255, 0.15);
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .sticky-link:hover { background: rgba(0, 122, 255, 0.15); }
        .user-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--divider);
            flex-wrap: wrap;
            gap: 10px;
        }
        .user-info { color: var(--text-secondary); font-size: 15px; }
        .user-info strong { color: var(--text-primary); }
        .logout-btn { color: #ff3b30; text-decoration: none; font-weight: 600; font-size: 14px; padding: 6px 16px; border-radius: 99px; background: var(--card-bg); border: 1px solid var(--divider); transition: all 0.3s ease; }
        .logout-btn:hover { background: rgba(255, 59, 48, 0.1); border-color: #ff3b30; }
        .exam-title { font-size: 28px; font-weight: 700; color: var(--text-primary); margin-bottom: 8px; }
        .exam-subtitle { font-size: 15px; color: var(--text-secondary); margin-bottom: 25px; }
        .exam-subtitle .highlight { color: #007aff; font-weight: 600; }
        .pass-condition { background: var(--card-bg); padding: 10px 16px; border-radius: 12px; margin-top: 10px; border: 1px solid var(--glass-border); font-size: 14px; }
        .question-item { background: var(--card-bg); border-radius: 16px; padding: 20px; margin-bottom: 18px; border: 1px solid var(--glass-border); }
        .question-item .q-number { font-size: 14px; font-weight: 600; color: #007aff; margin-bottom: 6px; }
        .question-item .q-text { font-size: 16px; font-weight: 600; color: var(--text-primary); margin-bottom: 12px; }
        .question-item .options { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .question-item .option-label {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            background: var(--bg-color);
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            font-size: 14px;
            color: var(--text-primary);
            font-weight: 500;
        }
        .question-item .option-label:hover { background: rgba(0, 122, 255, 0.05); border-color: rgba(0, 122, 255, 0.2); }
        .question-item .option-label input[type="radio"] {
            appearance: none;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: 2px solid var(--icon-color);
            position: relative;
            transition: all 0.3s ease;
            flex-shrink: 0;
            cursor: pointer;
        }
        .question-item .option-label input[type="radio"]:checked {
            border-color: #007aff;
            background: #007aff;
        }
        .question-item .option-label input[type="radio"]:checked::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 6px;
            height: 6px;
            background: white;
            border-radius: 50%;
        }
        .question-item .option-label:has(input:checked) {
            border-color: #007aff;
            background: rgba(0, 122, 255, 0.08);
        }
        .question-item .option-label .opt-label {
            font-weight: 700;
            color: #007aff;
            min-width: 20px;
        }
        .option-letter {
            display: inline-block;
            width: 24px;
            height: 24px;
            line-height: 24px;
            text-align: center;
            background: rgba(0, 122, 255, 0.1);
            border-radius: 50%;
            font-weight: 700;
            color: #007aff;
            font-size: 13px;
            flex-shrink: 0;
        }
        .submit-exam-btn {
            width: 100%; padding: 16px; border: none; border-radius: 99px; font-family: inherit; font-size: 18px; font-weight: 600;
            color: #ffffff; cursor: pointer; outline: none; margin-top: 10px;
            background: linear-gradient(135deg, #007aff, #0055b3);
            box-shadow: 0 10px 30px -10px rgba(0, 122, 255, 0.4);
            transition: all 0.3s cubic-bezier(0.34, 1.2, 0.64, 1);
        }
        .submit-exam-btn:hover { transform: scale(1.02); }
        .submit-exam-btn:active { transform: scale(0.97); }
        .submit-exam-btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
        .message { padding: 12px 16px; border-radius: 14px; margin-bottom: 20px; font-weight: 500; text-align: center; font-size: 14px; }
        .message.error { background: rgba(255, 59, 48, 0.2); color: #ff3b30; border: 1px solid rgba(255, 59, 48, 0.3); }
        .message.info { background: rgba(0, 122, 255, 0.1); color: #007aff; border: 1px solid rgba(0, 122, 255, 0.2); }
        @media (max-width: 600px) {
            .question-item .options { grid-template-columns: 1fr; }
            .exam-title { font-size: 22px; }
            .sticky-header { flex-direction: column; align-items: center; }
        }
    </style>
</head>
<body>

    <div class="bg-mesh"><div class="blob blob-1"></div><div class="blob blob-2"></div><div class="blob blob-3"></div></div>

    <div class="exam-container">
        <div class="sticky-header">
            <a href="study.php" class="sticky-link">📖 بازگشت به مطالعه متن</a>
        </div>

        <div class="auth-card">
            <div class="user-header">
                <div class="user-info">👋 خوش آمدی، <strong><?= htmlspecialchars($user['full_name']) ?> عزیز</strong></div>
                <a href="logout.php" class="logout-btn">🚪 خروج</a>
            </div>

            <h1 class="exam-title">📝 خودآزمایی</h1>
            <p class="exam-subtitle">
                به سوالات زیر پاسخ دهید.
                <span class="highlight">شرط قبولی: حداقل ۳ پاسخ درست از ۵ سوال</span>
                <div class="pass-condition">💡 پس از اتمام، پاسخ‌های صحیح و غلط را مشاهده خواهید کرد.</div>
            </p>

            <?php if ($error): ?>
                <div class="message error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (count($questions) < 5): ?>
                <div class="message info">⚠️ تعداد سوالات موجود کمتر از ۵ سوال است. لطفاً از مدیر سایت بخواهید سوالات را کامل کند.</div>
            <?php else: ?>
                <form method="POST" action="" id="examForm">
                    <?php foreach ($questions as $index => $q): ?>
                        <div class="question-item">
                            <div class="q-number">سوال <?= $index + 1 ?> از <?= count($questions) ?></div>
                            <div class="q-text"><?= htmlspecialchars($q['question_text']) ?></div>
                            <div class="options">
                                <label class="option-label">
                                    <input type="radio" name="answers[<?= $q['id'] ?>]" value="1" required>
                                    <span class="option-letter">الف</span>
                                    <?= htmlspecialchars($q['option_1']) ?>
                                </label>
                                <label class="option-label">
                                    <input type="radio" name="answers[<?= $q['id'] ?>]" value="2" required>
                                    <span class="option-letter">ب</span>
                                    <?= htmlspecialchars($q['option_2']) ?>
                                </label>
                                <label class="option-label">
                                    <input type="radio" name="answers[<?= $q['id'] ?>]" value="3" required>
                                    <span class="option-letter">پ</span>
                                    <?= htmlspecialchars($q['option_3']) ?>
                                </label>
                                <label class="option-label">
                                    <input type="radio" name="answers[<?= $q['id'] ?>]" value="4" required>
                                    <span class="option-letter">ت</span>
                                    <?= htmlspecialchars($q['option_4']) ?>
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <button type="submit" name="submit_exam" class="submit-exam-btn" id="submitBtn">📤 ثبت پاسخ‌ها و مشاهده نتیجه</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.querySelector('.theme-btn')?.addEventListener('click', function() {
            const root = document.documentElement;
            const isDark = root.getAttribute('data-theme') === 'dark';
            root.setAttribute('data-theme', isDark ? 'light' : 'dark');
        });
        document.getElementById('examForm')?.addEventListener('submit', function(e) {
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.textContent = '⏳ در حال ثبت...';
        });
    </script>
</body>
</html>
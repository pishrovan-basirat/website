<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'includes/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user = getUser($pdo);

// گرفتن سوالات
$stmt = $pdo->query("SELECT * FROM exam_questions ORDER BY id ASC LIMIT 5");
$questions = $stmt->fetchAll();

if (count($questions) < 5) {
    die("❌ تعداد سوالات کافی نیست (حداقل ۵ عدد)");
}

// پردازش ارسال فرم
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $answers = $_POST['answers'] ?? [];
    $correct = 0;
    
    foreach ($questions as $q) {
        $selected = (int)($answers[$q['id']] ?? 0);
        if ($selected === (int)$q['correct_answer']) {
            $correct++;
        }
    }
    
    $score = $correct;
    $passed = ($score >= 3) ? 1 : 0;
    
    // ذخیره در دیتابیس
    $stmt = $pdo->prepare("INSERT INTO user_exam_attempts (user_id, score, passed) VALUES (?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $score, $passed]);
    $attempt_id = $pdo->lastInsertId();
    
    foreach ($questions as $q) {
        $selected = (int)($answers[$q['id']] ?? 0);
        $is_correct = ($selected === (int)$q['correct_answer']) ? 1 : 0;
        $stmt = $pdo->prepare("INSERT INTO user_exam_answers (attempt_id, question_id, selected_answer, is_correct) VALUES (?, ?, ?, ?)");
        $stmt->execute([$attempt_id, $q['id'], $selected, $is_correct]);
    }
    
    // هدایت به صفحه نتیجه
    header('Location: exam_result_simple.php');
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>خودآزمایی (ساده)</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { padding: 40px; background: #f5f5f7; direction: rtl; font-family: 'Dana', sans-serif; }
        .container { max-width: 700px; margin: auto; background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
        .question { margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 15px; }
        .options label { display: block; margin: 6px 0; }
        button { background: #007aff; color: white; border: none; padding: 12px 30px; border-radius: 99px; font-size: 16px; cursor: pointer; }
        button:hover { background: #0055b3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📝 خودآزمایی</h1>
        <p>به ۵ سوال زیر پاسخ دهید.</p>
        <form method="POST">
            <?php foreach ($questions as $index => $q): ?>
                <div class="question">
                    <p><strong><?= ($index+1) ?>. <?= htmlspecialchars($q['question_text']) ?></strong></p>
                    <div class="options">
                        <label><input type="radio" name="answers[<?= $q['id'] ?>]" value="1" required> <?= htmlspecialchars($q['option_1']) ?></label>
                        <label><input type="radio" name="answers[<?= $q['id'] ?>]" value="2"> <?= htmlspecialchars($q['option_2']) ?></label>
                        <label><input type="radio" name="answers[<?= $q['id'] ?>]" value="3"> <?= htmlspecialchars($q['option_3']) ?></label>
                        <label><input type="radio" name="answers[<?= $q['id'] ?>]" value="4"> <?= htmlspecialchars($q['option_4']) ?></label>
                    </div>
                </div>
            <?php endforeach; ?>
            <button type="submit">ثبت و مشاهده نتیجه</button>
        </form>
    </div>
</body>
</html>
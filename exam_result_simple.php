<?php
include 'includes/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user = getUser($pdo);

// گرفتن آخرین تلاش
$stmt = $pdo->prepare("SELECT * FROM user_exam_attempts WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$attempt = $stmt->fetch();

if (!$attempt) {
    die("❌ هیچ تلاشی پیدا نشد");
}

// گرفتن پاسخ‌ها
$stmt = $pdo->prepare("
    SELECT uea.*, eq.question_text, eq.correct_answer
    FROM user_exam_answers uea
    JOIN exam_questions eq ON uea.question_id = eq.id
    WHERE uea.attempt_id = ?
");
$stmt->execute([$attempt['id']]);
$answers = $stmt->fetchAll();
$total = count($answers);
$score = $attempt['score'];
$passed = $attempt['passed'];
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>نتیجه</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { padding: 40px; background: #f5f5f7; direction: rtl; font-family: 'Dana', sans-serif; }
        .container { max-width: 700px; margin: auto; background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
        .correct { color: #34c759; font-weight: bold; }
        .wrong { color: #ff3b30; font-weight: bold; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 99px; font-size: 14px; }
        .badge.green { background: #d4edda; color: #155724; }
        .badge.red { background: #f8d7da; color: #721c24; }
        .answer-item { border-bottom: 1px solid #eee; padding: 12px 0; }
        .btn { display: inline-block; margin-top: 20px; padding: 10px 25px; background: #007aff; color: white; border-radius: 99px; text-decoration: none; }
        .btn:hover { background: #0055b3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 نتیجه خودآزمایی</h1>

        <?php if ($passed): ?>
            <div style="background: #d4edda; color: #155724; padding: 20px; border-radius: 12px; text-align: center; font-size: 22px;">
                ✅ تبریک! خودسازی امروز با موفقیت انجام شد.
            </div>
        <?php else: ?>
            <div style="background: #f8d7da; color: #721c24; padding: 20px; border-radius: 12px; text-align: center; font-size: 18px;">
                😔 قبول نشدید. برای قبولی باید حداقل ۳ سوال رو درست جواب بدید.
                <br><a href="exam_simple.php" class="btn" style="margin-top:10px; display:inline-block;">تلاش مجدد</a>
            </div>
        <?php endif; ?>

        <div style="display: flex; gap: 30px; justify-content: center; margin: 20px 0;">
            <div><strong>✅ درست:</strong> <?= $score ?></div>
            <div><strong>❌ غلط:</strong> <?= $total - $score ?></div>
        </div>

        <?php foreach ($answers as $index => $ans): ?>
            <div class="answer-item">
                <strong><?= ($index+1) ?>. <?= htmlspecialchars($ans['question_text']) ?></strong><br>
                پاسخ شما: <?= $ans['selected_answer'] ?>
                <?php if ($ans['is_correct']): ?>
                    <span class="badge green">✅ درست</span>
                <?php else: ?>
                    <span class="badge red">❌ غلط</span>
                    <span style="color:#007aff;">(پاسخ صحیح: <?= $ans['correct_answer'] ?>)</span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <div style="margin-top: 20px;">
            <a href="study.php" class="btn">📖 مطالعه مجدد متن</a>
        </div>
    </div>
</body>
</html>
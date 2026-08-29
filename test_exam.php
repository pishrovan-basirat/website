<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'includes/config.php';

echo "✅ مرحله 1: config.php لود شد<br>";

if (!isLoggedIn()) {
    echo "❌ کاربر لاگین نیست";
    exit();
}

echo "✅ مرحله 2: کاربر لاگین است<br>";

$user = getUser($pdo);
echo "✅ مرحله 3: کاربر دریافت شد<br>";

// ===== چک کردن قبولی امروز =====
$today = date('Y-m-d');
echo "✅ تاریخ امروز: " . $today . "<br>";

$stmt = $pdo->prepare("SELECT * FROM user_exam_attempts WHERE user_id = ? AND passed = 1 AND exam_date = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id'], $today]);
$already_passed_today = $stmt->fetch();

echo "✅ مرحله 4: چک قبولی انجام شد<br>";

if ($already_passed_today) {
    echo "✅ کاربر امروز قبول شده، هدایت به exam_result.php";
    // header('Location: exam_result.php');
    // exit();
}

// ===== گرفتن سوالات =====
$stmt = $pdo->query("SELECT * FROM exam_questions ORDER BY id ASC LIMIT 5");
$questions = $stmt->fetchAll();

echo "✅ تعداد سوالات: " . count($questions) . "<br>";

echo "🎉 همه چی درسته!";
?>
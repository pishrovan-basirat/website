<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'includes/config.php';

if (!isLoggedIn()) {
    die("❌ شما وارد نشده‌اید!");
}

echo "<h1>🧪 تست ساده ثبت در دیتابیس</h1>";

// ===== تست ۱: ثبت در جدول user_exam_attempts =====
try {
    $user_id = $_SESSION['user_id'];
    $score = 3;
    $passed = 1;
    
    echo "🟡 در حال ثبت در جدول user_exam_attempts...<br>";
    
    $stmt = $pdo->prepare("INSERT INTO user_exam_attempts (user_id, score, passed) VALUES (?, ?, ?)");
    $result = $stmt->execute([$user_id, $score, $passed]);
    
    if ($result) {
        $attempt_id = $pdo->lastInsertId();
        echo "✅ ثبت موفق! ID: " . $attempt_id . "<br>";
        
        // ===== تست ۲: ثبت در جدول user_exam_answers =====
        try {
            echo "🟡 در حال ثبت در جدول user_exam_answers...<br>";
            
            $stmt = $pdo->prepare("INSERT INTO user_exam_answers (attempt_id, question_id, selected_answer, is_correct) VALUES (?, ?, ?, ?)");
            $stmt->execute([$attempt_id, 1, 2, 1]);
            
            echo "✅ ثبت پاسخ موفق!<br>";
            echo "<br>🎉 همه تست‌ها با موفقیت انجام شد!";
            
        } catch(PDOException $e) {
            echo "❌ خطا در ثبت پاسخ: " . $e->getMessage();
        }
        
    } else {
        echo "❌ ثبت ناموفق بود!";
    }
    
} catch(PDOException $e) {
    echo "❌ خطا: " . $e->getMessage();
}
?>
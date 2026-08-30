<?php
session_start();

// ===== تنظیم منطقه زمانی =====
date_default_timezone_set('Asia/Tehran');

// ===== بارگذاری کتابخانه jalali =====
require_once __DIR__ . '/vendor/morilog/jalali/src/Jalalian.php';
use Morilog\Jalali\Jalalian;

// ===== اطلاعات دیتابیس =====
$host = 'sql123.infinityfree.com';
$dbname = 'if0_12345678_survey_db';
$username = 'if0_12345678';
$password = 'YourPassword123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("خطا در اتصال به دیتابیس: " . $e->getMessage());
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUser($pdo) {
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    }
    return null;
}

// ===== تابع تبدیل تاریخ میلادی به شمسی با کتابخانه =====
function toJalali($date, $format = 'Y/m/d') {
    if (!$date) return '-';
    try {
        return Jalalian::fromDateTime($date)->format($format);
    } catch(Exception $e) {
        return $date;
    }
}
?>

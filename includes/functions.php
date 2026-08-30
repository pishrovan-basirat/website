<?php
// توابع مشترک سایت

function getPageTitle($page) {
    $titles = [
        'index' => 'نظرسنجی شیشه‌ای',
        'login' => 'ورود به سایت',
        'register' => 'ثبت‌نام در سایت',
        'dashboard' => 'داشبورد کاربری',
        'admin' => 'پنل مدیریت'
    ];
    return $titles[$page] ?? 'سایت من';
}

function getPageDescription($page) {
    $descriptions = [
        'index' => 'نظرسنجی با طراحی شیشه‌ای اپل',
        'login' => 'با شماره همراه خود وارد شوید',
        'register' => 'ثبت‌نام در نظرسنجی',
    ];
    return $descriptions[$page] ?? '';
}

// بقیه توابع...
?>

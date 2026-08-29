<?php
include '../includes/config.php';

// چک کردن لاگین و نقش مدیر
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$user = getUser($pdo);
?>

<!DOCTYPE html>
<html lang="fa" data-theme="light" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>داشبورد مدیریت</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "SF Pro Text", "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            padding: 20px;
        }
        .dashboard-container {
            max-width: 800px;
            width: 100%;
        }
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--divider);
            flex-wrap: wrap;
            gap: 10px;
        }
        .admin-header h1 {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
        }
        .admin-header .user-info {
            font-size: 14px;
            color: var(--text-secondary);
        }
        .admin-header .user-info strong {
            color: var(--text-primary);
        }
        .logout-btn {
            color: #ff3b30;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            padding: 6px 16px;
            border-radius: 99px;
            background: var(--card-bg);
            border: 1px solid var(--divider);
            transition: all 0.3s ease;
        }
        .logout-btn:hover {
            background: rgba(255, 59, 48, 0.1);
            border-color: #ff3b30;
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .dashboard-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 24px;
            text-align: center;
            border: 1px solid var(--glass-border);
            transition: all 0.3s ease;
            text-decoration: none;
            color: var(--text-primary);
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px -10px var(--glass-shadow);
        }
        .dashboard-card .icon {
            font-size: 40px;
            display: block;
            margin-bottom: 10px;
        }
        .dashboard-card .title {
            font-size: 16px;
            font-weight: 600;
        }
        .dashboard-card .desc {
            font-size: 13px;
            color: var(--text-secondary);
            margin-top: 4px;
        }
    </style>
</head>
<body>

    <div class="bg-mesh">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
    </div>

    <div class="dashboard-container">
        <div class="auth-card">
            <div class="admin-header">
                <div>
                    <h1>📊 داشبورد مدیریت</h1>
                    <div class="user-info">
                        👋 خوش آمدی، <strong><?= htmlspecialchars($user['full_name']) ?> عزیز</strong>
                    </div>
                </div>
                <a href="../logout.php" class="logout-btn">🚪 خروج</a>
            </div>

            <div class="dashboard-grid">
                <a href="content.php" class="dashboard-card">
                    <span class="icon">📝</span>
                    <div class="title">مدیریت متن مطالعه</div>
                    <div class="desc">تعیین متن مطالعه روزانه</div>
                </a>

                <a href="questions.php" class="dashboard-card">
                    <span class="icon">📋</span>
                    <div class="title">مدیریت سوالات</div>
                    <div class="desc">افزودن و ویرایش سوالات خودآزمایی</div>
                </a>

                <a href="reports.php" class="dashboard-card">
    				<span class="icon">📊</span>
   					<div class="title">گزارشات</div>
   					<div class="desc">مشاهده عملکرد کاربران</div>
				</a>
            </div>
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
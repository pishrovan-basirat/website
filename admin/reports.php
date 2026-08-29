<?php
include '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$user = getUser($pdo);

// ===== گرفتن کاربرانی که امروز مطالعه رو تکمیل کردن =====
$today = date('Y-m-d');
$stmt = $pdo->prepare("
    SELECT u.full_name, u.phone, uds.is_completed, uds.study_duration
    FROM user_daily_study uds
    JOIN users u ON uds.user_id = u.id
    WHERE uds.study_date = ?
    ORDER BY uds.is_completed DESC, u.full_name ASC
");
$stmt->execute([$today]);
$study_reports = $stmt->fetchAll();

// ===== گرفتن کاربرانی که در آزمون قبول شدن و پاسخ‌هاشون =====
$stmt = $pdo->prepare("
    SELECT u.full_name, u.phone, uea.score, uea.attempted_at,
           uea_answers.question_id, uea_answers.selected_answer, uea_answers.is_correct,
           eq.question_text, eq.correct_answer,
           CASE eq.correct_answer
               WHEN 1 THEN eq.option_1
               WHEN 2 THEN eq.option_2
               WHEN 3 THEN eq.option_3
               WHEN 4 THEN eq.option_4
           END AS correct_answer_text
    FROM user_exam_attempts uea
    JOIN users u ON uea.user_id = u.id
    JOIN user_exam_answers uea_answers ON uea.id = uea_answers.attempt_id
    JOIN exam_questions eq ON uea_answers.question_id = eq.id
    WHERE uea.passed = 1
    ORDER BY uea.attempted_at DESC
");
$stmt->execute();
$exam_reports = $stmt->fetchAll();

// گروه‌بندی بر اساس کاربر
$grouped_reports = [];
foreach ($exam_reports as $row) {
    $key = $row['full_name'] . ' (' . $row['phone'] . ')';
    if (!isset($grouped_reports[$key])) {
        $grouped_reports[$key] = [
            'full_name' => $row['full_name'],
            'phone' => $row['phone'],
            'score' => $row['score'],
            'attempted_at' => $row['attempted_at'],
            'answers' => []
        ];
    }
    $grouped_reports[$key]['answers'][] = [
        'question_text' => $row['question_text'],
        'selected_answer' => $row['selected_answer'],
        'is_correct' => $row['is_correct'],
        'correct_answer' => $row['correct_answer'],
        'correct_answer_text' => $row['correct_answer_text']
    ];
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>گزارشات مدیریت</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 20px;
            padding-top: 40px;
            font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "SF Pro Text", "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        .container { max-width: 1100px; width: 100%; }
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
        .admin-header h1 { font-size: 24px; font-weight: 700; color: var(--text-primary); }
        .back-link {
            color: #007aff;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
        }
        .back-link:hover { text-decoration: underline; }
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
        .logout-btn:hover { background: rgba(255, 59, 48, 0.1); border-color: #ff3b30; }

        .section-title { font-size: 20px; font-weight: 700; color: var(--text-primary); margin: 30px 0 15px 0; border-right: 4px solid #007aff; padding-right: 12px; }
        .table-wrap { overflow-x: auto; background: var(--card-bg); border-radius: 16px; border: 1px solid var(--glass-border); padding: 15px; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th { background: var(--bg-color); color: var(--text-primary); padding: 10px 12px; text-align: right; font-weight: 700; border-bottom: 2px solid var(--divider); }
        td { padding: 10px 12px; border-bottom: 1px solid var(--divider); color: var(--text-primary); }
        tr:hover { background: rgba(0, 122, 255, 0.03); }
        .badge {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 99px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge.success { background: rgba(52, 199, 89, 0.2); color: #34c759; }
        .badge.fail { background: rgba(255, 59, 48, 0.2); color: #ff3b30; }
        .badge.info { background: rgba(0, 122, 255, 0.15); color: #007aff; }
        .badge.warning { background: rgba(255, 150, 0, 0.15); color: #ff9500; }
        .answers-detail { margin-top: 6px; font-size: 13px; color: var(--text-secondary); }
        .answers-detail .correct { color: #34c759; font-weight: 600; }
        .answers-detail .wrong { color: #ff3b30; font-weight: 600; }
        .empty-state { text-align: center; padding: 30px; color: var(--text-secondary); }
        .user-row { background: var(--bg-color); border-radius: 12px; margin-bottom: 10px; padding: 12px 16px; }
        .user-row .user-name { font-weight: 700; color: var(--text-primary); }
        @media (max-width: 600px) { th, td { font-size: 12px; padding: 6px 8px; } }
    </style>
</head>
<body>

    <div class="bg-mesh"><div class="blob blob-1"></div><div class="blob blob-2"></div><div class="blob blob-3"></div></div>

    <div class="container">
        <div class="auth-card">
            <div class="admin-header">
                <h1>📊 گزارشات مدیریت</h1>
                <div>
                    <a href="dashboard.php" class="back-link">← بازگشت به داشبورد</a>
                    <a href="../logout.php" class="logout-btn" style="margin-right: 10px;">🚪 خروج</a>
                </div>
            </div>

            <!-- ===== بخش ۱: مطالعه امروز ===== -->
            <h2 class="section-title">📖 وضعیت مطالعه امروز (<?= $today ?>)</h2>
            <div class="table-wrap">
                <?php if (count($study_reports) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>نام کاربر</th>
                                <th>شماره همراه</th>
                                <th>وضعیت</th>
                                <th>مدت مطالعه</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($study_reports as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                                    <td><?= htmlspecialchars($row['phone']) ?></td>
                                    <td>
                                        <?php if ($row['is_completed']): ?>
                                            <span class="badge success">✅ تکمیل شده</span>
                                        <?php else: ?>
                                            <span class="badge warning">⏳ ناقص</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $row['study_duration'] ?> ثانیه</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">📭 امروز هیچ کاربری مطالعه نکرده است.</div>
                <?php endif; ?>
            </div>

            <!-- ===== بخش ۲: کاربران قبول شده ===== -->
            <h2 class="section-title">✅ کاربران قبول شده در خودآزمایی</h2>
            <div class="table-wrap">
                <?php if (count($grouped_reports) > 0): ?>
                    <?php foreach ($grouped_reports as $key => $data): ?>
                        <div class="user-row">
                            <div class="user-name">👤 <?= htmlspecialchars($data['full_name']) ?></div>
                            <div style="font-size:13px; color: var(--text-secondary);">
                                📱 <?= htmlspecialchars($data['phone']) ?> |
                                ✅ امتیاز: <?= $data['score'] ?> از ۵ |
                                🕐 <?= $data['attempted_at'] ?>
                            </div>
                            <div class="answers-detail">
                                <?php foreach ($data['answers'] as $ans): ?>
                                    <div style="margin-top: 4px; padding-right: 10px; border-right: 2px solid <?= $ans['is_correct'] ? '#34c759' : '#ff3b30' ?>;">
                                        <?= htmlspecialchars($ans['question_text']) ?>
                                        <br>
                                        <span class="<?= $ans['is_correct'] ? 'correct' : 'wrong' ?>">
                                            پاسخ شما: <?= $ans['selected_answer'] ?>
                                            <?php if (!$ans['is_correct']): ?>
                                                (پاسخ صحیح: <?= htmlspecialchars($ans['correct_answer_text']) ?>)
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">📭 هنوز هیچ کاربری در آزمون قبول نشده است.</div>
                <?php endif; ?>
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
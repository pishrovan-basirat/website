<?php
include '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$user = getUser($pdo);
$message = '';
$error = '';

// ===== گرفتن لیست سوالات به همراه تاریخ =====
$stmt = $pdo->query("SELECT *, DATE(created_at) as question_date FROM exam_questions ORDER BY created_at DESC, id ASC");
$questions = $stmt->fetchAll();

// ===== حذف سوال =====
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM exam_questions WHERE id = ?");
        $stmt->execute([$id]);
        $message = '✅ سوال با موفقیت حذف شد!';
        // رفرش
        $stmt = $pdo->query("SELECT *, DATE(created_at) as question_date FROM exam_questions ORDER BY created_at DESC, id ASC");
        $questions = $stmt->fetchAll();
    } catch(PDOException $e) {
        $error = '❌ خطا در حذف: ' . $e->getMessage();
    }
}

// ===== ویرایش سوال =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_question'])) {
    $id = (int)$_POST['edit_id'];
    $question_text = trim($_POST['question_text'] ?? '');
    $option_1 = trim($_POST['option_1'] ?? '');
    $option_2 = trim($_POST['option_2'] ?? '');
    $option_3 = trim($_POST['option_3'] ?? '');
    $option_4 = trim($_POST['option_4'] ?? '');
    $correct_answer = (int)$_POST['correct_answer'];

    if (empty($question_text) || empty($option_1) || empty($option_2) || empty($option_3) || empty($option_4)) {
        $error = '❌ لطفاً همه فیلدها را پر کنید.';
    } elseif ($correct_answer < 1 || $correct_answer > 4) {
        $error = '❌ پاسخ صحیح باید بین ۱ تا ۴ باشد.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE exam_questions SET question_text = ?, option_1 = ?, option_2 = ?, option_3 = ?, option_4 = ?, correct_answer = ? WHERE id = ?");
            $stmt->execute([$question_text, $option_1, $option_2, $option_3, $option_4, $correct_answer, $id]);
            $message = '✅ سوال با موفقیت ویرایش شد!';
            // رفرش
            $stmt = $pdo->query("SELECT *, DATE(created_at) as question_date FROM exam_questions ORDER BY created_at DESC, id ASC");
            $questions = $stmt->fetchAll();
        } catch(PDOException $e) {
            $error = '❌ خطا در ویرایش: ' . $e->getMessage();
        }
    }
}

// ===== اضافه کردن سوال جدید =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_question'])) {
    $question_text = trim($_POST['question_text'] ?? '');
    $option_1 = trim($_POST['option_1'] ?? '');
    $option_2 = trim($_POST['option_2'] ?? '');
    $option_3 = trim($_POST['option_3'] ?? '');
    $option_4 = trim($_POST['option_4'] ?? '');
    $correct_answer = (int)$_POST['correct_answer'];

    if (empty($question_text) || empty($option_1) || empty($option_2) || empty($option_3) || empty($option_4)) {
        $error = '❌ لطفاً همه فیلدها را پر کنید.';
    } elseif ($correct_answer < 1 || $correct_answer > 4) {
        $error = '❌ پاسخ صحیح باید بین ۱ تا ۴ باشد.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO exam_questions (question_text, option_1, option_2, option_3, option_4, correct_answer, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$question_text, $option_1, $option_2, $option_3, $option_4, $correct_answer]);
            $message = '✅ سوال جدید با موفقیت اضافه شد!';
            // رفرش
            $stmt = $pdo->query("SELECT *, DATE(created_at) as question_date FROM exam_questions ORDER BY created_at DESC, id ASC");
            $questions = $stmt->fetchAll();
        } catch(PDOException $e) {
            $error = '❌ خطا در ذخیره: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fa" data-theme="light" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت سوالات</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            font-family: -apple-system, BlinkMacSystemFont, "SF Pro Display", "SF Pro Text", "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            padding: 20px;
            padding-top: 40px;
        }
        .admin-container {
            max-width: 1000px;
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
        .admin-header h1 { font-size: 24px; font-weight: 700; color: var(--text-primary); }
        .back-link { color: #007aff; text-decoration: none; font-weight: 600; font-size: 14px; }
        .back-link:hover { text-decoration: underline; }
        .question-form {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 30px;
            border: 1px solid var(--glass-border);
        }
        .question-form .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .question-form .form-group {
            margin-bottom: 15px;
        }
        .question-form .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 6px;
        }
        .question-form .form-group input,
        .question-form .form-group textarea {
            width: 100%;
            padding: 10px 14px;
            border-radius: 12px;
            border: 1px solid var(--glass-border);
            background: var(--bg-color);
            color: var(--text-primary);
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s ease;
            outline: none;
        }
        .question-form .form-group input:focus,
        .question-form .form-group textarea:focus {
            border-color: #007aff;
            box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.2);
        }
        .question-form .form-group textarea {
            min-height: 60px;
            resize: vertical;
        }
        .question-form .form-group select {
            width: 100%;
            padding: 10px 14px;
            border-radius: 12px;
            border: 1px solid var(--glass-border);
            background: var(--bg-color);
            color: var(--text-primary);
            font-size: 14px;
            font-family: inherit;
            outline: none;
        }
        .question-form .form-group select:focus {
            border-color: #007aff;
        }
        .add-btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 99px;
            font-family: inherit;
            font-size: 16px;
            font-weight: 600;
            color: var(--icon-active);
            cursor: pointer;
            outline: none;
            background: var(--glass-bg);
            backdrop-filter: blur(50px) saturate(200%);
            -webkit-backdrop-filter: blur(50px) saturate(200%);
            box-shadow: 0 20px 40px -12px var(--glass-shadow), 0 8px 24px -8px rgba(0,0,0,0.1), inset 0 2px 3px -1px var(--glass-highlight), inset 0 -2px 4px -1px var(--glass-caustic), inset 0 0 0 1px var(--glass-border);
            transition: all 0.4s cubic-bezier(0.34, 1.2, 0.64, 1);
        }
        .add-btn:hover { transform: scale(1.02); }
        .add-btn:active { transform: scale(0.97); }

        /* ===== تاریخ‌بندی و کشویی ===== */
        .date-group {
            margin-bottom: 20px;
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            overflow: hidden;
            background: var(--card-bg);
        }
        .date-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 20px;
            cursor: pointer;
            transition: background 0.3s ease;
            background: var(--card-bg);
            user-select: none;
        }
        .date-header:hover {
            background: rgba(0, 122, 255, 0.05);
        }
        .date-header .date-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-primary);
        }
        .date-header .date-count {
            font-size: 13px;
            color: var(--text-secondary);
            background: var(--bg-color);
            padding: 2px 12px;
            border-radius: 99px;
        }
        .date-header .toggle-icon {
            font-size: 18px;
            transition: transform 0.3s ease;
            color: var(--text-secondary);
        }
        .date-header.open .toggle-icon {
            transform: rotate(180deg);
        }
        .questions-list {
            display: none;
            padding: 0 20px 20px 20px;
        }
        .questions-list.open {
            display: block;
        }
        .question-item {
            background: var(--bg-color);
            border-radius: 12px;
            padding: 16px 18px;
            margin-top: 12px;
            border: 1px solid var(--glass-border);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
        }
        .question-item .q-content {
            flex: 1;
        }
        .question-item .q-content .q-text {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
        }
        .question-item .q-content .q-options {
            font-size: 13px;
            color: var(--text-secondary);
            line-height: 1.8;
        }
        .question-item .q-content .q-options .correct {
            color: #34c759;
            font-weight: 600;
        }
        .question-item .q-actions {
            display: flex;
            gap: 8px;
            flex-shrink: 0;
            flex-wrap: wrap;
        }
        .question-item .q-actions .btn-sm {
            padding: 4px 14px;
            border: none;
            border-radius: 99px;
            font-family: inherit;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-edit {
            background: rgba(0, 122, 255, 0.1);
            color: #007aff;
            border: 1px solid rgba(0, 122, 255, 0.15);
        }
        .btn-edit:hover {
            background: rgba(0, 122, 255, 0.2);
        }
        .btn-delete {
            background: rgba(255, 59, 48, 0.1);
            color: #ff3b30;
            border: 1px solid rgba(255, 59, 48, 0.15);
        }
        .btn-delete:hover {
            background: rgba(255, 59, 48, 0.2);
        }

        .empty-state {
            text-align: center;
            padding: 30px;
            color: var(--text-secondary);
            font-size: 16px;
        }
        .message {
            padding: 12px 16px;
            border-radius: 14px;
            margin-bottom: 20px;
            font-weight: 500;
            text-align: center;
            font-size: 14px;
        }
        .message.success {
            background: rgba(52, 199, 89, 0.2);
            color: #34c759;
            border: 1px solid rgba(52, 199, 89, 0.3);
        }
        .message.error {
            background: rgba(255, 59, 48, 0.2);
            color: #ff3b30;
            border: 1px solid rgba(255, 59, 48, 0.3);
        }

        /* ===== مدال ویرایش ===== */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(10px);
        }
        .modal.active {
            display: flex;
        }
        .modal-content {
            background: var(--card-bg);
            border-radius: 24px;
            padding: 30px;
            max-width: 600px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            border: 1px solid var(--glass-border);
            box-shadow: 0 40px 80px -20px var(--glass-shadow);
        }
        .modal-content h2 {
            font-size: 22px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 20px;
        }
        .modal-content .form-group {
            margin-bottom: 15px;
        }
        .modal-content .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 6px;
        }
        .modal-content .form-group input,
        .modal-content .form-group textarea {
            width: 100%;
            padding: 10px 14px;
            border-radius: 12px;
            border: 1px solid var(--glass-border);
            background: var(--bg-color);
            color: var(--text-primary);
            font-size: 14px;
            font-family: inherit;
            outline: none;
        }
        .modal-content .form-group textarea {
            min-height: 60px;
            resize: vertical;
        }
        .modal-content .form-group select {
            width: 100%;
            padding: 10px 14px;
            border-radius: 12px;
            border: 1px solid var(--glass-border);
            background: var(--bg-color);
            color: var(--text-primary);
            font-size: 14px;
            font-family: inherit;
            outline: none;
        }
        .modal-actions {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }
        .modal-actions .btn {
            padding: 10px 24px;
            border: none;
            border-radius: 99px;
            font-family: inherit;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-save {
            background: linear-gradient(135deg, #007aff, #0055b3);
            color: #fff;
            flex: 1;
        }
        .btn-save:hover {
            transform: scale(1.02);
        }
        .btn-cancel {
            background: var(--card-bg);
            color: var(--text-secondary);
            border: 1px solid var(--glass-border);
        }
        .btn-cancel:hover {
            background: rgba(255, 59, 48, 0.1);
            color: #ff3b30;
        }

        @media (max-width: 600px) {
            .question-form .form-row {
                grid-template-columns: 1fr;
            }
            .question-item {
                flex-direction: column;
                align-items: flex-start;
            }
            .modal-content {
                padding: 20px;
                margin: 10px;
            }
        }
    </style>
</head>
<body>

    <div class="bg-mesh"><div class="blob blob-1"></div><div class="blob blob-2"></div><div class="blob blob-3"></div></div>

    <div class="admin-container">
        <div class="auth-card">
            <div class="admin-header">
                <h1>📋 مدیریت سوالات</h1>
                <a href="dashboard.php" class="back-link">← بازگشت به داشبورد</a>
            </div>

            <?php if ($message): ?>
                <div class="message success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="message error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- ===== فرم اضافه کردن سوال ===== -->
            <div class="question-form">
                <h3 style="margin-bottom: 15px; color: var(--text-primary);">➕ افزودن سوال جدید</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label>متن سوال</label>
                        <textarea name="question_text" placeholder="متن سوال را وارد کنید..." required></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>گزینه ۱</label>
                            <input type="text" name="option_1" placeholder="گزینه اول" required>
                        </div>
                        <div class="form-group">
                            <label>گزینه ۲</label>
                            <input type="text" name="option_2" placeholder="گزینه دوم" required>
                        </div>
                        <div class="form-group">
                            <label>گزینه ۳</label>
                            <input type="text" name="option_3" placeholder="گزینه سوم" required>
                        </div>
                        <div class="form-group">
                            <label>گزینه ۴</label>
                            <input type="text" name="option_4" placeholder="گزینه چهارم" required>
                        </div>
                    </div>
                    <div class="form-group" style="max-width: 200px;">
                        <label>پاسخ صحیح</label>
                        <select name="correct_answer" required>
                            <option value="">انتخاب کنید</option>
                            <option value="1">گزینه ۱</option>
                            <option value="2">گزینه ۲</option>
                            <option value="3">گزینه ۳</option>
                            <option value="4">گزینه ۴</option>
                        </select>
                    </div>
                    <button type="submit" name="add_question" class="add-btn">➕ افزودن سوال</button>
                </form>
            </div>

            <!-- ===== لیست سوالات با تاریخ‌بندی ===== -->
            <h3 style="margin-bottom: 15px; color: var(--text-primary);">📋 لیست سوالات</h3>

            <?php if (count($questions) > 0): ?>
                <?php
                // گروه‌بندی بر اساس تاریخ
                $groups = [];
                foreach ($questions as $q) {
                    $date = $q['question_date'] ?? date('Y-m-d');
                    if (!isset($groups[$date])) {
                        $groups[$date] = [];
                    }
                    $groups[$date][] = $q;
                }
                // مرتب‌سازی تاریخ‌ها (جدیدترین اول)
                krsort($groups);
                ?>

                <?php foreach ($groups as $date => $items): ?>
                    <div class="date-group">
                        <div class="date-header" onclick="toggleGroup(this)">
                            <span class="date-title">📅 <?= jdate($date) ?></span>
                            <span style="display:flex; align-items:center; gap:10px;">
                                <span class="date-count"><?= count($items) ?> سوال</span>
                                <span class="toggle-icon">▼</span>
                            </span>
                        </div>
                        <div class="questions-list open">
                            <?php foreach ($items as $q): ?>
                                <div class="question-item">
                                    <div class="q-content">
                                        <div class="q-text"><?= htmlspecialchars($q['question_text']) ?></div>
                                        <div class="q-options">
                                            ۱. <?= htmlspecialchars($q['option_1']) ?><br>
                                            ۲. <?= htmlspecialchars($q['option_2']) ?><br>
                                            ۳. <?= htmlspecialchars($q['option_3']) ?><br>
                                            ۴. <?= htmlspecialchars($q['option_4']) ?><br>
                                            <span class="correct">✅ پاسخ صحیح: گزینه <?= $q['correct_answer'] ?></span>
                                        </div>
                                    </div>
                                    <div class="q-actions">
                                        <button class="btn-sm btn-edit" onclick="openEditModal(<?= $q['id'] ?>, '<?= addslashes($q['question_text']) ?>', '<?= addslashes($q['option_1']) ?>', '<?= addslashes($q['option_2']) ?>', '<?= addslashes($q['option_3']) ?>', '<?= addslashes($q['option_4']) ?>', <?= $q['correct_answer'] ?>)">✏️ ویرایش</button>
                                        <a href="?delete=<?= $q['id'] ?>" class="btn-sm btn-delete" onclick="return confirm('آیا از حذف این سوال مطمئن هستید؟')">🗑️ حذف</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

            <?php else: ?>
                <div class="empty-state">
                    <p>📭 هنوز سوالی اضافه نشده است.</p>
                    <p style="font-size: 14px; margin-top: 8px;">از فرم بالا برای افزودن سوال استفاده کنید.</p>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- ===== مدال ویرایش ===== -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <h2>✏️ ویرایش سوال</h2>
            <form method="POST" action="">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="form-group">
                    <label>متن سوال</label>
                    <textarea name="question_text" id="edit_question_text" required></textarea>
                </div>
                <div class="form-row" style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                    <div class="form-group">
                        <label>گزینه ۱</label>
                        <input type="text" name="option_1" id="edit_option_1" required>
                    </div>
                    <div class="form-group">
                        <label>گزینه ۲</label>
                        <input type="text" name="option_2" id="edit_option_2" required>
                    </div>
                    <div class="form-group">
                        <label>گزینه ۳</label>
                        <input type="text" name="option_3" id="edit_option_3" required>
                    </div>
                    <div class="form-group">
                        <label>گزینه ۴</label>
                        <input type="text" name="option_4" id="edit_option_4" required>
                    </div>
                </div>
                <div class="form-group" style="max-width:200px;">
                    <label>پاسخ صحیح</label>
                    <select name="correct_answer" id="edit_correct_answer" required>
                        <option value="1">گزینه ۱</option>
                        <option value="2">گزینه ۲</option>
                        <option value="3">گزینه ۳</option>
                        <option value="4">گزینه ۴</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="submit" name="edit_question" class="btn btn-save">💾 ذخیره تغییرات</button>
                    <button type="button" class="btn btn-cancel" onclick="closeEditModal()">❌ انصراف</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // ===== باز و بسته شدن تاریخ‌ها =====
        function toggleGroup(header) {
            const list = header.nextElementSibling;
            header.classList.toggle('open');
            list.classList.toggle('open');
        }

        // ===== مدال ویرایش =====
        function openEditModal(id, text, opt1, opt2, opt3, opt4, correct) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_question_text').value = text;
            document.getElementById('edit_option_1').value = opt1;
            document.getElementById('edit_option_2').value = opt2;
            document.getElementById('edit_option_3').value = opt3;
            document.getElementById('edit_option_4').value = opt4;
            document.getElementById('edit_correct_answer').value = correct;
            document.getElementById('editModal').classList.add('active');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        // ===== بستن مدال با کلیک خارج =====
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });

        document.querySelector('.theme-btn')?.addEventListener('click', function() {
            const root = document.documentElement;
            const isDark = root.getAttribute('data-theme') === 'dark';
            root.setAttribute('data-theme', isDark ? 'light' : 'dark');
        });
    </script>

    <?php
    // ===== تابع تبدیل تاریخ میلادی به شمسی (ساده) =====
    function jdate($date) {
        $timestamp = strtotime($date);
        $year = date('Y', $timestamp);
        $month = date('m', $timestamp);
        $day = date('d', $timestamp);
        
        // تبدیل سال میلادی به شمسی (تقریبی)
        $jalali_year = $year - 621;
        return $jalali_year . '/' . $month . '/' . $day;
    }
    ?>
</body>
</html>
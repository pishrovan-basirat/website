<?php
include '../includes/config.php';

// چک کردن لاگین و نقش مدیر
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$user = getUser($pdo);
$message = '';
$error = '';

// ===== گرفتن تاریخ امروز شمسی =====
function toJalali($date) {
    $timestamp = strtotime($date);
    $year = date('Y', $timestamp);
    $month = date('m', $timestamp);
    $day = date('d', $timestamp);
    $jalali_year = $year - 621;
    return $jalali_year . '/' . $month . '/' . $day;
}

// ===== گرفتن تمام متن‌های مطالعه با تاریخ =====
$stmt = $pdo->query("SELECT *, DATE(updated_at) as update_date FROM study_content ORDER BY updated_at DESC");
$contents = $stmt->fetchAll();

// ===== گرفتن متن فعلی (آخرین نسخه) =====
$stmt = $pdo->query("SELECT * FROM study_content ORDER BY id DESC LIMIT 1");
$current_content = $stmt->fetch();

// ===== ذخیره متن جدید =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_content'])) {
    $content = trim($_POST['content']);
    
    if (empty($content)) {
        $error = '❌ لطفاً متن مطالعه را وارد کنید.';
    } else {
        try {
            // اگه رکوردی وجود نداره، insert کن وگرنه update
            if ($current_content) {
                $stmt = $pdo->prepare("UPDATE study_content SET content = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$content, $current_content['id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO study_content (content, updated_at) VALUES (?, NOW())");
                $stmt->execute([$content]);
            }
            $message = '✅ متن مطالعه با موفقیت ذخیره شد!';
            
            // رفرش کردن
            $stmt = $pdo->query("SELECT * FROM study_content ORDER BY id DESC LIMIT 1");
            $current_content = $stmt->fetch();
            $stmt = $pdo->query("SELECT *, DATE(updated_at) as update_date FROM study_content ORDER BY updated_at DESC");
            $contents = $stmt->fetchAll();
        } catch(PDOException $e) {
            $error = '❌ خطا در ذخیره: ' . $e->getMessage();
        }
    }
}

// ===== ویرایش نسخه قدیمی =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_content'])) {
    $id = (int)$_POST['edit_id'];
    $content = trim($_POST['edit_content_text']);
    
    if (empty($content)) {
        $error = '❌ لطفاً متن مطالعه را وارد کنید.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE study_content SET content = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$content, $id]);
            $message = '✅ متن مطالعه با موفقیت ویرایش شد!';
            
            $stmt = $pdo->query("SELECT * FROM study_content ORDER BY id DESC LIMIT 1");
            $current_content = $stmt->fetch();
            $stmt = $pdo->query("SELECT *, DATE(updated_at) as update_date FROM study_content ORDER BY updated_at DESC");
            $contents = $stmt->fetchAll();
        } catch(PDOException $e) {
            $error = '❌ خطا در ویرایش: ' . $e->getMessage();
        }
    }
}

// ===== حذف نسخه =====
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM study_content WHERE id = ?");
        $stmt->execute([$id]);
        $message = '✅ نسخه با موفقیت حذف شد!';
        
        $stmt = $pdo->query("SELECT * FROM study_content ORDER BY id DESC LIMIT 1");
        $current_content = $stmt->fetch();
        $stmt = $pdo->query("SELECT *, DATE(updated_at) as update_date FROM study_content ORDER BY updated_at DESC");
        $contents = $stmt->fetchAll();
    } catch(PDOException $e) {
        $error = '❌ خطا در حذف: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fa" data-theme="light" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت متن مطالعه</title>
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
        .admin-header h1 {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
        }
        .back-link {
            color: #007aff;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
        }
        .back-link:hover {
            text-decoration: underline;
        }

        .content-form {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 30px;
            border: 1px solid var(--glass-border);
        }
        .content-form textarea {
            width: 100%;
            min-height: 250px;
            padding: 16px;
            border-radius: 16px;
            border: 1px solid var(--glass-border);
            background: var(--bg-color);
            color: var(--text-primary);
            font-size: 16px;
            font-family: inherit;
            line-height: 1.8;
            transition: all 0.3s ease;
            outline: none;
            resize: vertical;
        }
        .content-form textarea:focus {
            border-color: #007aff;
            box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.2);
        }
        .content-form textarea::placeholder {
            color: var(--text-secondary);
            opacity: 0.6;
        }
        .save-btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 99px;
            font-family: inherit;
            font-size: 17px;
            font-weight: 600;
            color: var(--icon-active);
            cursor: pointer;
            outline: none;
            margin-top: 20px;
            background: var(--glass-bg);
            backdrop-filter: blur(50px) saturate(200%);
            -webkit-backdrop-filter: blur(50px) saturate(200%);
            box-shadow: 0 20px 40px -12px var(--glass-shadow), 0 8px 24px -8px rgba(0,0,0,0.1), inset 0 2px 3px -1px var(--glass-highlight), inset 0 -2px 4px -1px var(--glass-caustic), inset 0 0 0 1px var(--glass-border);
            transition: all 0.4s cubic-bezier(0.34, 1.2, 0.64, 1);
        }
        .save-btn:hover {
            transform: scale(1.02);
        }
        .save-btn:active {
            transform: scale(0.97);
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

        .info-box {
            background: var(--card-bg);
            border-radius: 14px;
            padding: 16px;
            margin-bottom: 20px;
            border: 1px solid var(--glass-border);
            font-size: 14px;
            color: var(--text-secondary);
        }
        .info-box strong {
            color: var(--text-primary);
        }

        /* ===== تاریخ‌بندی نسخه‌ها ===== */
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
        .versions-list {
            display: none;
            padding: 0 20px 20px 20px;
        }
        .versions-list.open {
            display: block;
        }

        .version-item {
            background: var(--bg-color);
            border-radius: 12px;
            padding: 16px 18px;
            margin-top: 12px;
            border: 1px solid var(--glass-border);
        }
        .version-item .version-content {
            font-size: 14px;
            line-height: 1.8;
            color: var(--text-primary);
            white-space: pre-wrap;
            max-height: 100px;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .version-item .version-meta {
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .version-item .version-meta .time {
            opacity: 0.7;
        }
        .version-item .version-actions {
            display: flex;
            gap: 8px;
        }
        .version-item .version-actions .btn-sm {
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
        .btn-edit-version {
            background: rgba(0, 122, 255, 0.1);
            color: #007aff;
            border: 1px solid rgba(0, 122, 255, 0.15);
        }
        .btn-edit-version:hover {
            background: rgba(0, 122, 255, 0.2);
        }
        .btn-delete-version {
            background: rgba(255, 59, 48, 0.1);
            color: #ff3b30;
            border: 1px solid rgba(255, 59, 48, 0.15);
        }
        .btn-delete-version:hover {
            background: rgba(255, 59, 48, 0.2);
        }
        .btn-restore-version {
            background: rgba(52, 199, 89, 0.1);
            color: #34c759;
            border: 1px solid rgba(52, 199, 89, 0.15);
        }
        .btn-restore-version:hover {
            background: rgba(52, 199, 89, 0.2);
        }

        .empty-state {
            text-align: center;
            padding: 30px;
            color: var(--text-secondary);
            font-size: 16px;
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
            max-width: 700px;
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
        .modal-content .form-group textarea {
            width: 100%;
            min-height: 250px;
            padding: 12px;
            border-radius: 12px;
            border: 1px solid var(--glass-border);
            background: var(--bg-color);
            color: var(--text-primary);
            font-size: 15px;
            font-family: inherit;
            line-height: 1.8;
            outline: none;
            resize: vertical;
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
            .version-item .version-meta {
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
                <h1>📝 مدیریت متن مطالعه</h1>
                <a href="dashboard.php" class="back-link">← بازگشت به داشبورد</a>
            </div>

            <?php if ($message): ?>
                <div class="message success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="message error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- ===== فرم ذخیره متن جدید ===== -->
            <div class="content-form">
                <div class="info-box">
                    <strong>ℹ️ نکته:</strong> این متنی است که کاربران قبل از شروع خودآزمایی مشاهده می‌کنند. 
                    می‌توانید از HTML برای قالب‌بندی استفاده کنید. هر بار که ذخیره می‌کنید، نسخه جدیدی ساخته میشود.
                </div>
                <form method="POST" action="">
                    <textarea name="content" placeholder="متن مطالعه را اینجا وارد کنید..."><?= htmlspecialchars($current_content['content'] ?? '') ?></textarea>
                    <button type="submit" name="save_content" class="save-btn">💾 ذخیره نسخه جدید</button>
                </form>
            </div>

            <!-- ===== لیست نسخه‌های قبلی با تاریخ‌بندی ===== -->
            <h3 style="margin-bottom: 15px; color: var(--text-primary);">📋 تاریخچه نسخه‌ها</h3>

            <?php if (count($contents) > 0): ?>
                <?php
                // گروه‌بندی بر اساس تاریخ
                $groups = [];
                foreach ($contents as $item) {
                    $date = $item['update_date'] ?? date('Y-m-d');
                    if (!isset($groups[$date])) {
                        $groups[$date] = [];
                    }
                    $groups[$date][] = $item;
                }
                krsort($groups);
                ?>

                <?php foreach ($groups as $date => $items): ?>
                    <div class="date-group">
                        <div class="date-header" onclick="toggleGroup(this)">
                            <span class="date-title">📅 <?= toJalali($date) ?></span>
                            <span style="display:flex; align-items:center; gap:10px;">
                                <span class="date-count"><?= count($items) ?> نسخه</span>
                                <span class="toggle-icon">▼</span>
                            </span>
                        </div>
                        <div class="versions-list <?= $date === array_key_first($groups) ? 'open' : '' ?>">
                            <?php foreach ($items as $item): ?>
                                <div class="version-item">
                                    <div class="version-content"><?= nl2br(htmlspecialchars(substr($item['content'], 0, 300))) ?>...</div>
                                    <div class="version-meta">
                                        <span class="time">🕐 <?= date('H:i', strtotime($item['updated_at'])) ?></span>
                                        <div class="version-actions">
                                            <button class="btn-sm btn-edit-version" onclick="openEditModal(<?= $item['id'] ?>, '<?= addslashes($item['content']) ?>')">✏️ ویرایش</button>
                                            <a href="?delete=<?= $item['id'] ?>" class="btn-sm btn-delete-version" onclick="return confirm('آیا از حذف این نسخه مطمئن هستید؟')">🗑️ حذف</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

            <?php else: ?>
                <div class="empty-state">📭 هنوز نسخه‌ای ذخیره نشده است.</div>
            <?php endif; ?>

        </div>
    </div>

    <!-- ===== مدال ویرایش نسخه ===== -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <h2>✏️ ویرایش نسخه</h2>
            <form method="POST" action="">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="form-group">
                    <label>متن مطالعه</label>
                    <textarea name="edit_content_text" id="edit_content_text" required></textarea>
                </div>
                <div class="modal-actions">
                    <button type="submit" name="edit_content" class="btn btn-save">💾 ذخیره تغییرات</button>
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
        function openEditModal(id, content) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_content_text').value = content;
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

</body>
</html>
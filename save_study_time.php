<?php
include 'includes/config.php';

if (!isLoggedIn()) {
    http_response_code(401);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$study_id = (int)($data['study_id'] ?? 0);
$duration = (int)($data['duration'] ?? 0);

if ($study_id && $duration > 0) {
    $is_completed = ($duration >= 120) ? 1 : 0;
    $stmt = $pdo->prepare("UPDATE user_daily_study SET study_duration = ?, is_completed = ? WHERE id = ?");
    $stmt->execute([$duration, $is_completed, $study_id]);
    echo json_encode(['success' => true, 'duration' => $duration, 'completed' => $is_completed]);
} else {
    echo json_encode(['success' => false]);
}
?>
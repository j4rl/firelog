<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Du måste vara inloggad.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $pdo->prepare(
    'SELECT s.series_number, s.shots_json, s.total_score, s.x_count, s.shot_count, ss.session_date, ss.discipline, ss.distance_meters, w.manufacturer, w.model
     FROM series s
     JOIN shooting_sessions ss ON ss.id = s.session_id
     JOIN weapons w ON w.id = ss.weapon_id
     WHERE ss.user_id = ?
     ORDER BY ss.session_date DESC, s.created_at DESC
     LIMIT 100'
);
$stmt->execute([current_user_id()]);
echo json_encode(['success' => true, 'items' => $stmt->fetchAll()], JSON_UNESCAPED_UNICODE);

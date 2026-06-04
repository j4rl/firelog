<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');
if (!is_logged_in() || current_user_is_admin()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Du måste vara inloggad.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$seriesTable = db_table('series');
$sessionsTable = db_table('shooting_sessions');
$weaponsTable = db_table('weapons');

$stmt = $pdo->prepare(
    'SELECT s.series_number, s.shots_json, s.total_score, s.x_count, s.shot_count, ss.session_date, ss.discipline, ss.distance_meters, ss.shooter_age, w.manufacturer, w.model, w.weapon_class
     FROM ' . $seriesTable . ' s
     JOIN ' . $sessionsTable . ' ss ON ss.id = s.session_id
     JOIN ' . $weaponsTable . ' w ON w.id = ss.weapon_id
     WHERE ss.user_id = ?
     ORDER BY ss.session_date DESC, s.created_at DESC
     LIMIT 100'
);
$stmt->execute([current_user_id()]);
$items = [];
foreach ($stmt->fetchAll() as $row) {
    $shots = json_decode($row['shots_json'], true) ?: [];
    $row['medal'] = series_medal_for_context((string) $row['discipline'], (string) $row['weapon_class'], $row['shooter_age'] !== null ? (int) $row['shooter_age'] : null, $shots);
    $items[] = $row;
}

echo json_encode(['success' => true, 'items' => $items], JSON_UNESCAPED_UNICODE);

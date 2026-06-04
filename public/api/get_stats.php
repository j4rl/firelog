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

$userId = current_user_id();
$seriesTable = db_table('series');
$sessionsTable = db_table('shooting_sessions');

$stmt = $pdo->prepare(
    'SELECT ss.session_date AS label, ROUND(AVG(s.total_score), 2) AS avg_score
     FROM ' . $seriesTable . ' s
     JOIN ' . $sessionsTable . ' ss ON ss.id = s.session_id
     WHERE ss.user_id = ?
     GROUP BY ss.session_date
     ORDER BY ss.session_date'
);
$stmt->execute([$userId]);
$trend = $stmt->fetchAll();

$stmt = $pdo->prepare(
    'SELECT s.shots_json
     FROM ' . $seriesTable . ' s
     JOIN ' . $sessionsTable . ' ss ON ss.id = s.session_id
     WHERE ss.user_id = ?'
);
$stmt->execute([$userId]);
$distribution = array_fill_keys(['X', '10', '9', '8', '7', '6', '5', '4', '3', '2', '1', '0'], 0);
foreach ($stmt->fetchAll() as $row) {
    $shots = json_decode($row['shots_json'], true) ?: [];
    foreach ($shots as $shot) {
        if (array_key_exists((string) $shot, $distribution)) {
            $distribution[(string) $shot]++;
        }
    }
}

echo json_encode([
    'success' => true,
    'trend' => $trend,
    'distribution' => $distribution,
], JSON_UNESCAPED_UNICODE);

<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

function api_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!is_logged_in() || current_user_is_admin()) {
    api_response(['success' => false, 'message' => 'Du måste vara inloggad.'], 401);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    api_response(['success' => false, 'message' => 'Endast POST är tillåtet.'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    api_response(['success' => false, 'message' => 'Ogiltig JSON.'], 400);
}

$sessionId = (int) ($data['session_id'] ?? 0);
$shots = $data['shots'] ?? [];

if (!is_array($shots) || count($shots) === 0) {
    api_response(['success' => false, 'message' => 'Serien måste innehålla minst ett skott.'], 400);
}

$sessionsTable = db_table('shooting_sessions');
$weaponsTable = db_table('weapons');
$seriesTable = db_table('series');

$stmt = $pdo->prepare(
    'SELECT ss.id, ss.discipline, ss.shooter_age, w.weapon_class
     FROM ' . $sessionsTable . ' ss
     JOIN ' . $weaponsTable . ' w ON w.id = ss.weapon_id
     WHERE ss.id = ? AND ss.user_id = ?'
);
$stmt->execute([$sessionId, current_user_id()]);
$session = $stmt->fetch();
if (!$session) {
    api_response(['success' => false, 'message' => 'Skjutpasset hittades inte.'], 404);
}

try {
    $score = score_shots($shots);
} catch (InvalidArgumentException $exception) {
    api_response(['success' => false, 'message' => $exception->getMessage()], 400);
}

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("SELECT COALESCE(MAX(series_number), 0) + 1 AS next_number FROM {$seriesTable} WHERE session_id = ? FOR UPDATE");
    $stmt->execute([$sessionId]);
    $seriesNumber = (int) $stmt->fetch()['next_number'];

    $stmt = $pdo->prepare("INSERT INTO {$seriesTable} (session_id, series_number, shots_json, total_score, x_count, shot_count) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $sessionId,
        $seriesNumber,
        json_encode(array_values($shots), JSON_UNESCAPED_UNICODE),
        $score['total_score'],
        $score['x_count'],
        $score['shot_count'],
    ]);
    $pdo->commit();
} catch (Throwable $exception) {
    $pdo->rollBack();
    api_response(['success' => false, 'message' => 'Kunde inte spara serien.'], 500);
}

api_response([
    'success' => true,
    'series_number' => $seriesNumber,
    'total_score' => $score['total_score'],
    'x_count' => $score['x_count'],
    'shot_count' => $score['shot_count'],
    'medal' => series_medal_for_context(
        (string) $session['discipline'],
        (string) $session['weapon_class'],
        $session['shooter_age'] !== null ? (int) $session['shooter_age'] : null,
        $shots
    ),
]);

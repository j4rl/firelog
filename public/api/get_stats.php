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
$weaponsTable = db_table('weapons');
$selectedDiscipline = trim((string) ($_GET['discipline'] ?? ''));
$where = ['ss.user_id = ?'];
$params = [$userId];
if ($selectedDiscipline !== '') {
    $where[] = 'ss.discipline = ?';
    $params[] = $selectedDiscipline;
}

$stmt = $pdo->prepare(
    'SELECT ss.session_date AS label, w.id AS weapon_id, CONCAT(w.manufacturer, \' \', w.model) AS weapon_label,
            ROUND(AVG(s.total_score), 2) AS avg_score
     FROM ' . $seriesTable . ' s
     JOIN ' . $sessionsTable . ' ss ON ss.id = s.session_id
     JOIN ' . $weaponsTable . ' w ON w.id = ss.weapon_id
     WHERE ' . implode(' AND ', $where) . '
     GROUP BY ss.session_date, w.id, w.manufacturer, w.model
     ORDER BY ss.session_date, w.manufacturer, w.model'
);
$stmt->execute($params);
$trendRows = $stmt->fetchAll();
$trendLabels = [];
$trendByWeapon = [];
foreach ($trendRows as $row) {
    $label = (string) $row['label'];
    $weaponId = (int) $row['weapon_id'];
    if (!in_array($label, $trendLabels, true)) {
        $trendLabels[] = $label;
    }
    if (!isset($trendByWeapon[$weaponId])) {
        $trendByWeapon[$weaponId] = [
            'label' => (string) $row['weapon_label'],
            'points' => [],
        ];
    }
    $trendByWeapon[$weaponId]['points'][$label] = $row['avg_score'] !== null ? (float) $row['avg_score'] : null;
}
$trendDatasets = [];
uasort($trendByWeapon, static fn (array $a, array $b): int => strcmp($a['label'], $b['label']));
foreach ($trendByWeapon as $weapon) {
    $trendDatasets[] = [
        'label' => $weapon['label'],
        'data' => array_map(static fn (string $label): ?float => $weapon['points'][$label] ?? null, $trendLabels),
    ];
}

$stmt = $pdo->prepare(
    'SELECT w.id AS weapon_id, CONCAT(w.manufacturer, \' \', w.model) AS weapon_label, s.shots_json
     FROM ' . $seriesTable . ' s
     JOIN ' . $sessionsTable . ' ss ON ss.id = s.session_id
     JOIN ' . $weaponsTable . ' w ON w.id = ss.weapon_id
     WHERE ' . implode(' AND ', $where) . '
     ORDER BY w.manufacturer, w.model'
);
$stmt->execute($params);
$distributionLabels = ['X', '10', '9', '8', '7', '6', '5', '4', '3', '2', '1', '-'];
$distributionByWeapon = [];
foreach ($stmt->fetchAll() as $row) {
    $weaponId = (int) $row['weapon_id'];
    if (!isset($distributionByWeapon[$weaponId])) {
        $distributionByWeapon[$weaponId] = [
            'label' => (string) $row['weapon_label'],
            'values' => array_fill_keys($distributionLabels, 0),
        ];
    }

    $shots = json_decode($row['shots_json'], true) ?: [];
    foreach ($shots as $shot) {
        $shot = (string) $shot;
        if ($shot === '0') {
            $shot = '-';
        }
        if (array_key_exists($shot, $distributionByWeapon[$weaponId]['values'])) {
            $distributionByWeapon[$weaponId]['values'][$shot]++;
        }
    }
}
$distributionDatasets = [];
uasort($distributionByWeapon, static fn (array $a, array $b): int => strcmp($a['label'], $b['label']));
foreach ($distributionByWeapon as $weapon) {
    $distributionDatasets[] = [
        'label' => $weapon['label'],
        'data' => array_values($weapon['values']),
    ];
}

echo json_encode([
    'success' => true,
    'discipline' => $selectedDiscipline,
    'trend' => [
        'labels' => $trendLabels,
        'datasets' => $trendDatasets,
    ],
    'distribution' => [
        'labels' => $distributionLabels,
        'datasets' => $distributionDatasets,
    ],
], JSON_UNESCAPED_UNICODE);

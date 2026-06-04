<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_shooter();

$seriesTable = db_table('series');
$sessionsTable = db_table('shooting_sessions');
$weaponsTable = db_table('weapons');

$stmt = $pdo->prepare(
    'SELECT ss.session_date, ss.location, ss.discipline, ss.distance_meters, ss.shooter_age,
            w.manufacturer, w.model, w.caliber, w.weapon_class, w.serial_number,
            s.series_number, s.shots_json, s.total_score, s.x_count, s.shot_count
     FROM ' . $seriesTable . ' s
     JOIN ' . $sessionsTable . ' ss ON ss.id = s.session_id
     JOIN ' . $weaponsTable . ' w ON w.id = ss.weapon_id
     WHERE ss.user_id = ?
     ORDER BY ss.session_date, ss.id, s.series_number'
);
$stmt->execute([current_user_id()]);

$filename = 'skjutdagbok-' . date('Y-m-d') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
fputcsv($out, ['datum', 'plats', 'disciplin', 'avstand_m', 'alder', 'vapen', 'kaliber', 'klass', 'serienummer', 'serie_i_pass', 'marke', 'skott', 'totalpoang', 'x', 'antal_skott']);

while ($row = $stmt->fetch()) {
    $shots = json_decode($row['shots_json'], true) ?: [];
    $medal = series_medal_for_context((string) $row['discipline'], (string) $row['weapon_class'], $row['shooter_age'] !== null ? (int) $row['shooter_age'] : null, $shots);
    fputcsv($out, [
        $row['session_date'],
        $row['location'],
        $row['discipline'],
        $row['distance_meters'],
        $row['shooter_age'],
        $row['manufacturer'] . ' ' . $row['model'],
        $row['caliber'],
        $row['weapon_class'],
        $row['serial_number'],
        $row['series_number'],
        $medal !== null ? $medal['label'] : '',
        implode(' - ', $shots),
        $row['total_score'],
        $row['x_count'],
        $row['shot_count'],
    ]);
}
fclose($out);

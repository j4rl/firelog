<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$stmt = $pdo->prepare(
    'SELECT ss.session_date, ss.location, ss.discipline, ss.distance_meters,
            w.manufacturer, w.model, w.caliber, w.weapon_class, w.serial_number,
            s.series_number, s.shots_json, s.total_score, s.x_count, s.shot_count
     FROM series s
     JOIN shooting_sessions ss ON ss.id = s.session_id
     JOIN weapons w ON w.id = ss.weapon_id
     WHERE ss.user_id = ?
     ORDER BY ss.session_date, ss.id, s.series_number'
);
$stmt->execute([current_user_id()]);

$filename = 'skjutdagbok-' . date('Y-m-d') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
fputcsv($out, ['datum', 'plats', 'disciplin', 'avstand_m', 'vapen', 'kaliber', 'klass', 'serienummer', 'serie_i_pass', 'skott', 'totalpoang', 'x', 'antal_skott']);

while ($row = $stmt->fetch()) {
    $shots = json_decode($row['shots_json'], true) ?: [];
    fputcsv($out, [
        $row['session_date'],
        $row['location'],
        $row['discipline'],
        $row['distance_meters'],
        $row['manufacturer'] . ' ' . $row['model'],
        $row['caliber'],
        $row['weapon_class'],
        $row['serial_number'],
        $row['series_number'],
        implode(' - ', $shots),
        $row['total_score'],
        $row['x_count'],
        $row['shot_count'],
    ]);
}
fclose($out);

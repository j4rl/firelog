<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_shooter();

$userId = current_user_id();
$page_title = 'Historik';
$weaponId = (int) ($_GET['weapon_id'] ?? 0);
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$weaponsTable = db_table('weapons');
$sessionsTable = db_table('shooting_sessions');
$seriesTable = db_table('series');

$weaponStmt = $pdo->prepare("SELECT id, manufacturer, model FROM {$weaponsTable} WHERE user_id = ? ORDER BY manufacturer, model");
$weaponStmt->execute([$userId]);
$weapons = $weaponStmt->fetchAll();

$where = ['ss.user_id = ?'];
$params = [$userId];
if ($weaponId > 0) {
    $where[] = 'ss.weapon_id = ?';
    $params[] = $weaponId;
}
if ($dateFrom !== '') {
    $where[] = 'ss.session_date >= ?';
    $params[] = $dateFrom;
}
if ($dateTo !== '') {
    $where[] = 'ss.session_date <= ?';
    $params[] = $dateTo;
}

$sql = 'SELECT s.*, ss.session_date, ss.discipline, ss.distance_meters, ss.shooter_age, w.manufacturer, w.model, w.caliber, w.weapon_class
        FROM ' . $seriesTable . ' s
        JOIN ' . $sessionsTable . ' ss ON ss.id = s.session_id
        JOIN ' . $weaponsTable . ' w ON w.id = ss.weapon_id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY ss.session_date DESC, s.created_at DESC, s.id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<div class="stack">
    <section class="card">
        <form class="form-grid two" method="get">
            <label>Vapen
                <select name="weapon_id">
                    <option value="0">Alla</option>
                    <?php foreach ($weapons as $weapon): ?>
                        <option value="<?= (int) $weapon['id'] ?>" <?= $weaponId === (int) $weapon['id'] ? 'selected' : '' ?>><?= e($weapon['manufacturer']) ?> <?= e($weapon['model']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Från <input type="date" name="date_from" value="<?= e($dateFrom) ?>"></label>
            <label>Till <input type="date" name="date_to" value="<?= e($dateTo) ?>"></label>
            <button type="submit">Filtrera</button>
        </form>
    </section>

    <div class="mobile-list list">
        <?php foreach ($rows as $row): $shots = json_decode($row['shots_json'], true) ?: []; ?>
            <?php $medal = series_medal_for_context((string) $row['discipline'], (string) $row['weapon_class'], $row['shooter_age'] !== null ? (int) $row['shooter_age'] : null, $shots); ?>
            <article class="list-item">
                <strong class="item-title"><?= e($row['session_date']) ?> · Serie <?= (int) $row['series_number'] ?><?= medal_badge_html($medal) ?></strong>
                <span class="meta"><?= e($row['manufacturer']) ?> <?= e($row['model']) ?> · <?= e($row['discipline']) ?> · <?= (int) $row['distance_meters'] ?> m</span>
                <div class="shots"><?php foreach ($shots as $shot): ?><span class="shot-pill"><?= e($shot) ?></span><?php endforeach; ?></div>
                <span><?= (int) $row['total_score'] ?> poäng · <?= (int) $row['x_count'] ?> X · <?= (int) $row['miss_count'] ?> missar</span>
            </article>
        <?php endforeach; ?>
    </div>

    <div class="table-wrap">
        <table>
            <thead><tr><th>Datum</th><th>Vapen</th><th>Disciplin</th><th>Serie</th><th>Märke</th><th>Skott</th><th>Poäng</th><th>X</th><th>Missar</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): $shots = json_decode($row['shots_json'], true) ?: []; ?>
                <?php $medal = series_medal_for_context((string) $row['discipline'], (string) $row['weapon_class'], $row['shooter_age'] !== null ? (int) $row['shooter_age'] : null, $shots); ?>
                <tr>
                    <td><?= e($row['session_date']) ?></td>
                    <td><?= e($row['manufacturer']) ?> <?= e($row['model']) ?></td>
                    <td><?= e($row['discipline']) ?>, <?= (int) $row['distance_meters'] ?> m</td>
                    <td><?= (int) $row['series_number'] ?></td>
                    <td><?= medal_badge_html($medal) ?></td>
                    <td><?= e(implode(' - ', $shots)) ?></td>
                    <td><?= (int) $row['total_score'] ?></td>
                    <td><?= (int) $row['x_count'] ?></td>
                    <td><?= (int) $row['miss_count'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

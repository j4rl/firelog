<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$page_title = 'Serier';
$message = '';
$error = '';
$seriesTable = db_table('series');
$sessionsTable = db_table('shooting_sessions');
$weaponsTable = db_table('weapons');
$usersTable = db_table('users');

if (is_post()) {
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM {$seriesTable} WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'Serien togs bort.';
    }
}

$stmt = $pdo->query(
    'SELECT s.*, ss.session_date, ss.discipline, ss.distance_meters, ss.shooter_age,
            w.manufacturer, w.model, w.weapon_class, u.username
     FROM ' . $seriesTable . ' s
     JOIN ' . $sessionsTable . ' ss ON ss.id = s.session_id
     JOIN ' . $weaponsTable . ' w ON w.id = ss.weapon_id
     JOIN ' . $usersTable . ' u ON u.id = ss.user_id
     ORDER BY ss.session_date DESC, s.created_at DESC, s.id DESC
     LIMIT 500'
);
$rows = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<div class="stack">
    <?php if ($message): ?><div class="message ok"><?= e($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="message error"><?= e($error) ?></div><?php endif; ?>

    <div class="mobile-list list">
        <?php foreach ($rows as $row): $shots = json_decode($row['shots_json'], true) ?: []; ?>
            <?php $medal = series_medal_for_context((string) $row['discipline'], (string) $row['weapon_class'], $row['shooter_age'] !== null ? (int) $row['shooter_age'] : null, $shots); ?>
            <article class="list-item">
                <strong class="item-title"><?= e($row['username']) ?> · <?= e($row['session_date']) ?> · Serie <?= (int) $row['series_number'] ?><?= medal_badge_html($medal) ?></strong>
                <span class="meta"><?= e($row['manufacturer']) ?> <?= e($row['model']) ?> · <?= e($row['discipline']) ?></span>
                <div class="shots"><?php foreach ($shots as $shot): ?><span class="shot-pill"><?= e($shot) ?></span><?php endforeach; ?></div>
                <span><?= (int) $row['total_score'] ?> poäng · <?= (int) $row['x_count'] ?> X · <?= (int) $row['miss_count'] ?> missar</span>
                <div class="actions">
                    <a class="button secondary" href="admin_series_edit.php?id=<?= (int) $row['id'] ?>">Redigera</a>
                    <form class="inline-form" method="post" onsubmit="return confirm('Ta bort serien?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                        <button class="danger" type="submit">Ta bort</button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <div class="table-wrap">
        <table>
            <thead><tr><th>Användare</th><th>Datum</th><th>Vapen</th><th>Disciplin</th><th>Serie</th><th>Märke</th><th>Skott</th><th>Poäng</th><th>Åtgärder</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): $shots = json_decode($row['shots_json'], true) ?: []; ?>
                <?php $medal = series_medal_for_context((string) $row['discipline'], (string) $row['weapon_class'], $row['shooter_age'] !== null ? (int) $row['shooter_age'] : null, $shots); ?>
                <tr>
                    <td><?= e($row['username']) ?></td>
                    <td><?= e($row['session_date']) ?></td>
                    <td><?= e($row['manufacturer']) ?> <?= e($row['model']) ?></td>
                    <td><?= e($row['discipline']) ?></td>
                    <td><?= (int) $row['series_number'] ?></td>
                    <td><?= medal_badge_html($medal) ?></td>
                    <td><?= e(implode(' - ', $shots)) ?></td>
                    <td><?= (int) $row['total_score'] ?> p · <?= (int) $row['x_count'] ?> X · <?= (int) $row['miss_count'] ?> missar</td>
                    <td>
                        <div class="table-actions">
                            <a class="button secondary" href="admin_series_edit.php?id=<?= (int) $row['id'] ?>">Redigera</a>
                            <form class="inline-form" method="post" onsubmit="return confirm('Ta bort serien?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                <button class="danger" type="submit">Ta bort</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

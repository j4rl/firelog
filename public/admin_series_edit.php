<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$seriesTable = db_table('series');
$sessionsTable = db_table('shooting_sessions');
$weaponsTable = db_table('weapons');
$usersTable = db_table('users');
$stmt = $pdo->prepare(
    'SELECT s.*, ss.session_date, ss.discipline, ss.distance_meters,
            w.manufacturer, w.model, u.username
     FROM ' . $seriesTable . ' s
     JOIN ' . $sessionsTable . ' ss ON ss.id = s.session_id
     JOIN ' . $weaponsTable . ' w ON w.id = ss.weapon_id
     JOIN ' . $usersTable . ' u ON u.id = ss.user_id
     WHERE s.id = ?'
);
$stmt->execute([$id]);
$series = $stmt->fetch();
if (!$series) {
    redirect('admin_series.php');
}

$page_title = 'Redigera serie';
$error = '';
$shots = json_decode($series['shots_json'], true) ?: [];

if (is_post()) {
    $shots = parse_shots_input((string) ($_POST['shots'] ?? ''));
    if (!$shots) {
        $error = 'Ange minst ett skottvärde.';
    } else {
        try {
            $score = score_shots($shots);
            $stmt = $pdo->prepare("UPDATE {$seriesTable} SET shots_json = ?, total_score = ?, x_count = ?, miss_count = ?, shot_count = ? WHERE id = ?");
            $stmt->execute([
                json_encode($shots, JSON_UNESCAPED_UNICODE),
                $score['total_score'],
                $score['x_count'],
                $score['miss_count'],
                $score['shot_count'],
                $id,
            ]);
            redirect('admin_series.php');
        } catch (InvalidArgumentException $exception) {
            $error = $exception->getMessage();
        }
    }
}

require __DIR__ . '/../includes/header.php';
?>
<section class="card stack">
    <p class="muted"><?= e($series['username']) ?> · <?= e($series['session_date']) ?> · <?= e($series['manufacturer']) ?> <?= e($series['model']) ?> · <?= e($series['discipline']) ?> · Serie <?= (int) $series['series_number'] ?></p>
    <?php if ($error): ?><div class="message error"><?= e($error) ?></div><?php endif; ?>
    <form class="form-grid" method="post">
        <label>Skott
            <input name="shots" value="<?= e(implode(' ', $shots)) ?>" required>
        </label>
        <p class="muted">Tillåtna värden: X, 10, 9, 8, 7, 6, 5, 4, 3, 2, 1 och - för miss. Separera med mellanslag, komma eller semikolon.</p>
        <div class="actions">
            <button type="submit">Spara serie</button>
            <a class="button secondary" href="admin_series.php">Avbryt</a>
        </div>
    </form>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>

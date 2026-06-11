<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_shooter();

$sessionId = (int) ($_GET['session_id'] ?? 0);
$sessionsTable = db_table('shooting_sessions');
$weaponsTable = db_table('weapons');
$seriesTable = db_table('series');
$stmt = $pdo->prepare(
    'SELECT ss.*, w.manufacturer, w.model, w.caliber, w.weapon_class
     FROM ' . $sessionsTable . ' ss
     JOIN ' . $weaponsTable . ' w ON w.id = ss.weapon_id
     WHERE ss.id = ? AND ss.user_id = ?'
);
$stmt->execute([$sessionId, current_user_id()]);
$session = $stmt->fetch();

if (!$session) {
    redirect('dashboard.php');
}

$stmt = $pdo->prepare("SELECT COALESCE(MAX(series_number), 0) + 1 AS next_number FROM {$seriesTable} WHERE session_id = ?");
$stmt->execute([$sessionId]);
$nextNumber = (int) $stmt->fetch()['next_number'];
$page_title = 'Registrera serie';
$page_class = 'page-shoot';

require __DIR__ . '/../includes/header.php';
?>
<div class="stack shoot-flow" data-shoot data-session-id="<?= (int) $sessionId ?>">
    <section class="card stack shoot-context">
        <strong><?= e($session['discipline']) ?> · <?= (int) $session['distance_meters'] ?> m</strong>
        <span class="meta"><?= e($session['session_date']) ?> · <?= e($session['location']) ?></span>
        <span class="meta"><?= e($session['manufacturer']) ?> <?= e($session['model']) ?>, <?= e($session['caliber']) ?> · Klass <?= e($session['weapon_class']) ?><?= $session['shooter_age'] !== null ? ' · ' . (int) $session['shooter_age'] . ' år' : '' ?></span>
    </section>

    <section class="card stack series-live shoot-live">
        <div class="grid four shoot-metrics">
            <div class="metric"><span class="muted">Serie</span><strong data-series-number><?= $nextNumber ?></strong></div>
            <div class="metric"><span class="muted">Poäng</span><strong data-total>0</strong></div>
            <div class="metric"><span class="muted">X</span><strong data-x-count>0</strong></div>
            <div class="metric"><span class="muted">Missar</span><strong data-miss-count>0</strong></div>
        </div>
        <div class="shots" data-current-shots></div>
        <div class="message" data-shoot-message hidden></div>
    </section>

    <section class="score-grid" aria-label="Poängknappar">
        <?php foreach (['X', '10', '9', '8', '7', '6', '5', '4', '3', '2', '1', '-'] as $shot): ?>
            <button class="score-button" type="button" data-shot="<?= e($shot) ?>"><?= e($shot) ?></button>
        <?php endforeach; ?>
    </section>

    <div class="actions shoot-actions">
        <button class="secondary" type="button" data-undo-shot>Ångra</button>
        <button type="button" data-save-series>Spara serie</button>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

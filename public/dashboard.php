<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_shooter();

$userId = current_user_id();
$page_title = 'Hem';
$weaponsTable = db_table('weapons');
$sessionsTable = db_table('shooting_sessions');
$seriesTable = db_table('series');

$stmt = $pdo->prepare("SELECT COUNT(*) AS count FROM {$weaponsTable} WHERE user_id = ?");
$stmt->execute([$userId]);
$weaponCount = (int) $stmt->fetch()['count'];

$stmt = $pdo->prepare(
    'SELECT ss.*, w.manufacturer, w.model
     FROM ' . $sessionsTable . ' ss
     JOIN ' . $weaponsTable . ' w ON w.id = ss.weapon_id
     WHERE ss.user_id = ?
     ORDER BY ss.session_date DESC, ss.id DESC
     LIMIT 1'
);
$stmt->execute([$userId]);
$latestSession = $stmt->fetch();

$stmt = $pdo->prepare(
    'SELECT s.*, ss.session_date, ss.discipline, ss.shooter_age, w.manufacturer, w.model, w.weapon_class
     FROM ' . $seriesTable . ' s
     JOIN ' . $sessionsTable . ' ss ON ss.id = s.session_id
     JOIN ' . $weaponsTable . ' w ON w.id = ss.weapon_id
     WHERE ss.user_id = ?
     ORDER BY s.created_at DESC, s.id DESC
     LIMIT 1'
);
$stmt->execute([$userId]);
$latestSeries = $stmt->fetch();

$stmt = $pdo->prepare(
    'SELECT MAX(s.total_score) AS best_score, MAX(s.x_count) AS best_x
     FROM ' . $seriesTable . ' s
     JOIN ' . $sessionsTable . ' ss ON ss.id = s.session_id
     WHERE ss.user_id = ?'
);
$stmt->execute([$userId]);
$best = $stmt->fetch();

$stmt = $pdo->prepare(
    'SELECT AVG(total_score) AS avg_score FROM (
        SELECT s.total_score
        FROM ' . $seriesTable . ' s
        JOIN ' . $sessionsTable . ' ss ON ss.id = s.session_id
        WHERE ss.user_id = ?
        ORDER BY s.created_at DESC, s.id DESC
        LIMIT 10
    ) latest'
);
$stmt->execute([$userId]);
$avgLatest = $stmt->fetch()['avg_score'];

require __DIR__ . '/../includes/header.php';
?>
<div class="stack">
    <?php if ($weaponCount === 0): ?>
        <section class="card stack">
            <h2>Kom igång</h2>
            <p class="muted">1. Lägg till vapen. 2. Skapa skjutpass. 3. Registrera serie.</p>
            <a class="button" href="weapon_add.php">Lägg till första vapnet</a>
        </section>
    <?php else: ?>
        <div class="actions">
            <a class="button" href="session_new.php">Nytt skjutpass</a>
            <?php if ($latestSession): ?><a class="button secondary" href="shoot.php?session_id=<?= (int) $latestSession['id'] ?>">Fortsätt senaste</a><?php endif; ?>
        </div>
    <?php endif; ?>

    <section class="grid two">
        <article class="card metric"><span class="muted">Registrerade vapen</span><strong><?= $weaponCount ?></strong></article>
        <article class="card metric"><span class="muted">Snitt senaste 10</span><strong><?= $avgLatest !== null ? e(number_format((float) $avgLatest, 1, ',', '')) : '-' ?></strong></article>
    </section>

    <section class="grid two">
        <article class="card stack">
            <h2>Senaste pass</h2>
            <?php if ($latestSession): ?>
                <p><?= e($latestSession['session_date']) ?> · <?= e($latestSession['discipline']) ?> · <?= (int) $latestSession['distance_meters'] ?> m</p>
                <p class="muted"><?= e($latestSession['manufacturer']) ?> <?= e($latestSession['model']) ?></p>
            <?php else: ?>
                <p class="muted">Inget skjutpass ännu.</p>
            <?php endif; ?>
        </article>

        <article class="card stack">
            <h2>Senaste serie</h2>
            <?php if ($latestSeries): ?>
                <?php $latestShots = json_decode($latestSeries['shots_json'], true) ?: []; ?>
                <?php $latestMedal = series_medal_for_context((string) $latestSeries['discipline'], (string) $latestSeries['weapon_class'], $latestSeries['shooter_age'] !== null ? (int) $latestSeries['shooter_age'] : null, $latestShots); ?>
                <p>Serie <?= (int) $latestSeries['series_number'] ?>: <?= (int) $latestSeries['total_score'] ?> poäng, <?= (int) $latestSeries['x_count'] ?> X <?= medal_badge_html($latestMedal) ?></p>
                <p class="muted"><?= e($latestSeries['session_date']) ?> · <?= e($latestSeries['manufacturer']) ?> <?= e($latestSeries['model']) ?></p>
            <?php else: ?>
                <p class="muted">Ingen serie sparad ännu.</p>
            <?php endif; ?>
        </article>
    </section>

    <section class="card metric">
        <span class="muted">Bästa serie hittills</span>
        <strong><?= $best['best_score'] !== null ? (int) $best['best_score'] . ' p' : '-' ?></strong>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

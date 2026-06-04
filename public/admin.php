<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$page_title = 'Admin';

$usersTable = db_table('users');
$weaponsTable = db_table('weapons');
$seriesTable = db_table('series');

$userCount = (int) $pdo->query("SELECT COUNT(*) AS count FROM {$usersTable}")->fetch()['count'];
$shooterCount = (int) $pdo->query("SELECT COUNT(*) AS count FROM {$usersTable} WHERE is_admin = 0")->fetch()['count'];
$adminCount = (int) $pdo->query("SELECT COUNT(*) AS count FROM {$usersTable} WHERE is_admin = 1")->fetch()['count'];
$weaponCount = (int) $pdo->query("SELECT COUNT(*) AS count FROM {$weaponsTable}")->fetch()['count'];
$seriesCount = (int) $pdo->query("SELECT COUNT(*) AS count FROM {$seriesTable}")->fetch()['count'];

require __DIR__ . '/../includes/header.php';
?>
<div class="stack">
    <section class="grid three">
        <article class="card metric"><span class="muted">Användare</span><strong><?= $userCount ?></strong></article>
        <article class="card metric"><span class="muted">Skyttar</span><strong><?= $shooterCount ?></strong></article>
        <article class="card metric"><span class="muted">Admin</span><strong><?= $adminCount ?></strong></article>
        <article class="card metric"><span class="muted">Vapen</span><strong><?= $weaponCount ?></strong></article>
        <article class="card metric"><span class="muted">Serier</span><strong><?= $seriesCount ?></strong></article>
    </section>

    <section class="grid three">
        <a class="card admin-tile" href="admin_users.php"><strong>Användare</strong><span class="muted">Redigera roller, lösenord och ta bort konton.</span></a>
        <a class="card admin-tile" href="admin_weapons.php"><strong>Vapen</strong><span class="muted">Redigera eller radera registrerade vapen.</span></a>
        <a class="card admin-tile" href="admin_series.php"><strong>Serier</strong><span class="muted">Justera skottvärden eller radera felaktiga serier.</span></a>
    </section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

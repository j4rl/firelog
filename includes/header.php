<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$page_title = $page_title ?? 'firelog';
$active_page = basename($_SERVER['SCRIPT_NAME'] ?? '');
$nav_items = current_user_is_admin()
    ? [
        'admin.php' => 'Admin',
        'admin_users.php' => 'Användare',
        'admin_weapons.php' => 'Vapen',
        'admin_series.php' => 'Serier',
    ]
    : [
        'dashboard.php' => 'Hem',
        'weapons.php' => 'Vapen',
        'session_new.php' => 'Skjut',
        'history.php' => 'Historik',
        'stats.php' => 'Stats',
        'profile.php' => 'Profil',
    ];
$brand_href = current_user_is_admin() ? 'admin.php' : 'dashboard.php';
$eyebrow = current_user_is_admin() ? 'Administration' : 'Personlig firelog';
?>
<!doctype html>
<html lang="sv">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#15191f">
    <title><?= e($page_title) ?> - firelog</title>
    <link rel="manifest" href="manifest.webmanifest">
    <link rel="icon" href="assets/firelog-icon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://ld.j4rl.se/ld-theme-toggle.js" defer></script>
</head>
<body>
<div class="app-shell <?= is_logged_in() ? 'has-sidebar' : '' ?>">
    <?php if (is_logged_in()): ?>
        <aside class="sidebar">
            <div class="sidebar-top">
                <a class="brand" href="<?= e($brand_href) ?>"><img src="assets/firelog-icon.svg" alt="" width="34" height="34">firelog</a>
                <ld-theme-toggle></ld-theme-toggle>
            </div>
            <nav class="nav-list" aria-label="Huvudnavigation">
                <?php foreach ($nav_items as $href => $label): ?>
                    <a class="<?= $active_page === $href ? 'active' : '' ?>" href="<?= e($href) ?>"><?= e($label) ?></a>
                <?php endforeach; ?>
            </nav>
            <a class="logout-link" href="logout.php">Logga ut</a>
        </aside>
    <?php endif; ?>

    <main class="page <?= e($page_class ?? '') ?> <?= is_logged_in() ? '' : 'page-centered' ?>">
        <?php if (is_logged_in()): ?>
            <div class="mobile-theme-bar">
                <ld-theme-toggle></ld-theme-toggle>
            </div>
        <?php endif; ?>
        <header class="page-header">
            <div>
                <p class="eyebrow"><?= e($eyebrow) ?></p>
                <h1><?= e($page_title) ?></h1>
            </div>
            <div class="header-actions">
                <?php if (!is_logged_in()): ?>
                    <ld-theme-toggle></ld-theme-toggle>
                <?php endif; ?>
                <?php if (is_logged_in()): ?>
                    <a class="desktop-action" href="logout.php">Logga ut</a>
                <?php endif; ?>
            </div>
        </header>

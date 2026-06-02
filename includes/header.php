<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$page_title = $page_title ?? 'Skjutdagbok';
$active_page = basename($_SERVER['SCRIPT_NAME'] ?? '');
$nav_items = [
    'dashboard.php' => 'Hem',
    'weapons.php' => 'Vapen',
    'session_new.php' => 'Skjut',
    'history.php' => 'Historik',
    'stats.php' => 'Stats',
];
?>
<!doctype html>
<html lang="sv">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#15191f">
    <title><?= e($page_title) ?> - Skjutdagbok</title>
    <link rel="manifest" href="manifest.webmanifest">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="app-shell">
    <?php if (is_logged_in()): ?>
        <aside class="sidebar">
            <a class="brand" href="dashboard.php">Skjutdagbok</a>
            <nav class="nav-list" aria-label="Huvudnavigation">
                <?php foreach ($nav_items as $href => $label): ?>
                    <a class="<?= $active_page === $href ? 'active' : '' ?>" href="<?= e($href) ?>"><?= e($label) ?></a>
                <?php endforeach; ?>
            </nav>
            <a class="logout-link" href="logout.php">Logga ut</a>
        </aside>
    <?php endif; ?>

    <main class="page <?= is_logged_in() ? '' : 'page-centered' ?>">
        <header class="page-header">
            <div>
                <p class="eyebrow">Personlig skjutdagbok</p>
                <h1><?= e($page_title) ?></h1>
            </div>
            <?php if (is_logged_in()): ?>
                <a class="desktop-action" href="logout.php">Logga ut</a>
            <?php endif; ?>
        </header>

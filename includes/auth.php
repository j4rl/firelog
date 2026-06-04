<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function current_user_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

function current_user_is_admin(): bool
{
    return !empty($_SESSION['is_admin']);
}

function is_logged_in(): bool
{
    return current_user_id() !== null;
}

function require_login(): void
{
    if (!is_logged_in()) {
        redirect('login.php');
    }
}

function require_admin(): void
{
    require_login();
    if (!current_user_is_admin()) {
        redirect('dashboard.php');
    }
}

function require_shooter(): void
{
    require_login();
    if (current_user_is_admin()) {
        redirect('admin.php');
    }
}

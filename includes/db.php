<?php
declare(strict_types=1);

$db_host = '127.0.0.1';
$db_name = 'shooting_log';
$db_user = 'root';
$db_pass = '';
$db_charset = 'utf8mb4';
// Set to e.g. 'firelog_' when several apps share the same database.
$db_table_prefix = '';

$dsn = "mysql:host={$db_host};dbname={$db_name};charset={$db_charset}";

if (!preg_match('/^[A-Za-z0-9_]*$/', $db_table_prefix)) {
    throw new RuntimeException('Databastabellprefix får bara innehålla bokstäver, siffror och understreck.');
}

function db_table(string $name): string
{
    return '`' . str_replace('`', '``', db_table_name($name)) . '`';
}

function db_table_name(string $name): string
{
    global $db_table_prefix;

    $allowed = ['users', 'weapons', 'shooting_sessions', 'series'];
    if (!in_array($name, $allowed, true)) {
        throw new InvalidArgumentException('Okänd databastabell.');
    }

    return $db_table_prefix . $name;
}

$pdo = new PDO($dsn, $db_user, $db_pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

function db_create_missing_tables(PDO $pdo): void
{
    $usersTable = db_table('users');
    $weaponsTable = db_table('weapons');
    $sessionsTable = db_table('shooting_sessions');
    $seriesTable = db_table('series');

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS {$usersTable} (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(80) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            is_admin TINYINT(1) NOT NULL DEFAULT 0,
            birth_date DATE NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS {$weaponsTable} (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            manufacturer VARCHAR(120) NOT NULL,
            model VARCHAR(120) NOT NULL,
            caliber VARCHAR(80) NOT NULL,
            serial_number VARCHAR(120) NOT NULL,
            weapon_class ENUM('A', 'B', 'C', 'R') NOT NULL,
            notes TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_weapons_user (user_id),
            FOREIGN KEY (user_id) REFERENCES {$usersTable}(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS {$sessionsTable} (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            weapon_id INT UNSIGNED NOT NULL,
            session_date DATE NOT NULL,
            location VARCHAR(160) NOT NULL,
            discipline VARCHAR(120) NOT NULL,
            distance_meters INT UNSIGNED NOT NULL,
            shooter_age TINYINT UNSIGNED NULL,
            notes TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_sessions_user_date (user_id, session_date),
            INDEX idx_sessions_weapon (weapon_id),
            FOREIGN KEY (user_id) REFERENCES {$usersTable}(id)
                ON DELETE CASCADE,
            FOREIGN KEY (weapon_id) REFERENCES {$weaponsTable}(id)
                ON DELETE RESTRICT
        ) ENGINE=InnoDB"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS {$seriesTable} (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            session_id INT UNSIGNED NOT NULL,
            series_number INT UNSIGNED NOT NULL,
            shots_json JSON NOT NULL,
            total_score INT UNSIGNED NOT NULL,
            x_count INT UNSIGNED NOT NULL,
            shot_count INT UNSIGNED NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_series_session_number (session_id, series_number),
            INDEX idx_series_session (session_id),
            INDEX idx_series_score (total_score),
            FOREIGN KEY (session_id) REFERENCES {$sessionsTable}(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB"
    );
}

db_create_missing_tables($pdo);

try {
    $sessionsTable = db_table('shooting_sessions');
    $usersTable = db_table('users');

    $column = $pdo->query("SHOW COLUMNS FROM {$sessionsTable} LIKE 'shooter_age'")->fetch();
    if (!$column) {
        $pdo->exec("ALTER TABLE {$sessionsTable} ADD COLUMN shooter_age TINYINT UNSIGNED NULL AFTER distance_meters");
    }

    $column = $pdo->query("SHOW COLUMNS FROM {$usersTable} LIKE 'is_admin'")->fetch();
    if (!$column) {
        $pdo->exec("ALTER TABLE {$usersTable} ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0 AFTER password_hash");
    }

    $column = $pdo->query("SHOW COLUMNS FROM {$usersTable} LIKE 'birth_date'")->fetch();
    if (!$column) {
        $pdo->exec("ALTER TABLE {$usersTable} ADD COLUMN birth_date DATE NULL AFTER is_admin");
    }
} catch (Throwable) {
    // Keep startup resilient; schema.sql documents the required column.
}

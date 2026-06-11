-- Ändra databasnamnet om du vill använda ett annat namn.
-- Om du sätter $db_table_prefix i includes/db.php måste samma prefix användas
-- på tabellnamnen och FOREIGN KEY-referenserna i detta schema innan import.
CREATE DATABASE IF NOT EXISTS shooting_log
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE shooting_log;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(80) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  is_admin TINYINT(1) NOT NULL DEFAULT 0,
  birth_date DATE NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS weapons (
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
  CONSTRAINT fk_weapons_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS shooting_sessions (
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
  CONSTRAINT fk_sessions_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_sessions_weapon
    FOREIGN KEY (weapon_id) REFERENCES weapons(id)
    ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS series (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  session_id INT UNSIGNED NOT NULL,
  series_number INT UNSIGNED NOT NULL,
  shots_json JSON NOT NULL,
  total_score INT UNSIGNED NOT NULL,
  x_count INT UNSIGNED NOT NULL,
  miss_count INT UNSIGNED NOT NULL DEFAULT 0,
  shot_count INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_series_session_number (session_id, series_number),
  INDEX idx_series_session (session_id),
  INDEX idx_series_score (total_score),
  CONSTRAINT fk_series_session
    FOREIGN KEY (session_id) REFERENCES shooting_sessions(id)
    ON DELETE CASCADE
) ENGINE=InnoDB;

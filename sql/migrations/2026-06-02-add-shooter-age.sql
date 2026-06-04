-- Om du använder tabellprefix, lägg samma prefix på tabellnamnet innan körning.
ALTER TABLE shooting_sessions
  ADD COLUMN IF NOT EXISTS shooter_age TINYINT UNSIGNED NULL AFTER distance_meters;

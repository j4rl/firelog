-- Om du använder tabellprefix, lägg samma prefix på tabellnamnet innan körning.
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS is_admin TINYINT(1) NOT NULL DEFAULT 0 AFTER password_hash;

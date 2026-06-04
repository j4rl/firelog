-- Om du använder tabellprefix, lägg samma prefix på tabellnamnet innan körning.
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS birth_date DATE NULL AFTER is_admin;

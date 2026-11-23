-- Files table
CREATE TABLE files (
  id INTEGER PRIMARY KEY,
  path TEXT NOT NULL UNIQUE,
  size INTEGER DEFAULT 0,
  created_at TIMESTAMP
);

SELECT path, size FROM files WHERE size > 1024 ORDER BY size DESC;

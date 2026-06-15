<?php
$db = new PDO('sqlite:' . DB_PATH);
$db->exec("CREATE TABLE IF NOT EXISTS access_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    log_hash TEXT UNIQUE,
    ip TEXT,
    date_time TEXT,
    method TEXT,
    url TEXT,
    status_code INTEGER,
    response_size INTEGER,
    user_agent TEXT,
    response_time_ms REAL,
    log_source TEXT,
    full_log_line TEXT
)");
echo "Database ready.";
?>

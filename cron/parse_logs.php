<?php
// cron/parse_logs.php
require_once dirname(__DIR__) . '/config.php';

$dbPath = DB_PATH;
$logDir = LOG_DIR;

$daysBk = 3;

try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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
} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}

// Use REPLACE INTO instead of INSERT OR IGNORE to update response times for existing entries
// REPLACE INTO deletes and re-inserts the record on conflict (log_hash unique constraint)
$stmt = $db->prepare("REPLACE INTO access_logs (log_hash, ip, date_time, method, url, status_code, response_size, user_agent, response_time_ms, log_source, full_log_line) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$files = glob($logDir . 'drserita_access.log*');
$count = 0;
$threeDaysAgo = time() - ($daysBk * 24 * 60 * 60);

foreach ($files as $file) {
    if (filemtime($file) < $threeDaysAgo) continue;

    $filename = basename($file);
    echo "Processing: $filename ... ";

    $content = '';
    if (pathinfo($file, PATHINFO_EXTENSION) === 'gz') {
        $content = @file_get_contents('compress.zlib://' . $file);
    } else {
        $content = @file_get_contents($file);
    }

    if (!$content) continue;

    $lines = explode("\n", $content);
    foreach ($lines as $line) {
        if (empty($line)) continue;

        // Regex handles both formats: with or without response time at the end
        // Group 8 is optional for response time
        if (preg_match('/^(\S+) \S+ \S+ \[([^\]]+)\] "(\S+) (\S+) \S+" (\d{3}) (\d+) "[^"]*" "(.*?)"(?:\s+(\d+))?$/', $line, $matches)) {
            $ip = $matches[1];
            $date = $matches[2];
            $method = $matches[3];
            $url = $matches[4];
            $status = $matches[5];
            $size = $matches[6];
            $agent = $matches[7];

            // If group 8 exists, it's already in milliseconds (not microseconds)
            // Apache %D should be microseconds, but this server logs in milliseconds
            $time = isset($matches[8]) ? floatval($matches[8]) : 0;

            $hash = md5($line);

            try {
                $stmt->execute([$hash, $ip, $date, $method, $url, $status, $size, $agent, $time, $filename, $line]);
                $count++;
            } catch (PDOException $e) {
                // Ignore duplicates
            }
        }
    }
    echo "Done.\n";
}
echo "Total new records: $count\n";
?>

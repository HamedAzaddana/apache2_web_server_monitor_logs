<?php
/**
 * Truncate Logs Endpoint
 * Requires authentication via session
 */

session_start();
define('ACCESS_ALLOWED', true);

// Check authentication
if (!isset($_SESSION['monitor_auth']) || $_SESSION['monitor_auth'] !== true) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once dirname(__DIR__) . '/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = new PDO('sqlite:' . DB_PATH);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Get count before truncating
        $stmt = $db->query("SELECT COUNT(*) FROM access_logs");
        $countBefore = $stmt->fetchColumn();

        // Truncate the table
        $db->exec("DELETE FROM access_logs");

        // Reset autoincrement
        $db->exec("DELETE FROM sqlite_sequence WHERE name='access_logs'");

        echo json_encode([
            'success' => true,
            'deleted' => $countBefore
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

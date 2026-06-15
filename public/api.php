<?php
// public/api.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once dirname(__DIR__) . '/config.php';

if (!isset($_SESSION['monitor_auth'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

try {
    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['error' => 'DB Error: ' . $e->getMessage()]);
    exit;
}

function getTehranTimeObj($apacheDateStr) {
    $dt = DateTime::createFromFormat('d/M/Y:H:i:s O', $apacheDateStr);
    if (!$dt) return null;
    $dt->setTimezone(new DateTimeZone('Asia/Tehran'));
    return $dt;
}

$action = $_GET['action'] ?? '';
$dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
$dateTo = $_GET['date_to'] ?? date('Y-m-d');
$timeFrom = $_GET['time_from'] ?? '00:00';
$timeTo = $_GET['time_to'] ?? '23:59';
$status = $_GET['status'] ?? '';
$trafficFilter = $_GET['traffic'] ?? 'all';
$searchQuery = $_GET['search'] ?? '';
$responseTimeMin = $_GET['response_time_min'] ?? '';
$responseTimeMax = $_GET['response_time_max'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 50;

// --- Date Logic ---
// IMPORTANT: Apache logs are stored with embedded timezone: [15/Jun/2026:00:05:16 +0330]
// The date part in the log (15/Jun/2026) is the date in the server's timezone (Asia/Tehran)
// We filter directly by Tehran dates since logs are stored with Tehran dates
$dateList = [];
$tz = new DateTimeZone('Asia/Tehran');
$fromDt = new DateTime($dateFrom . ' 00:00:00', $tz);
$toDt = new DateTime($dateTo . ' 23:59:59', $tz);

// Generate SQL patterns directly from Tehran dates
// No UTC conversion needed since logs already contain Tehran dates
$current = clone $fromDt;
while ($current <= $toDt) {
    $dateList[] = $current->format('d/M/Y');
    $current->modify('+1 day');
}

if (!empty($dateList)) {
    $likeClauses = [];
    foreach ($dateList as $index => $d) {
        $paramName = ":date_$index";
        $likeClauses[] = "date_time LIKE $paramName";
        $params[$paramName] = "$d%";
    }
    $whereClauses = ["(" . implode(' OR ', $likeClauses) . ")"];
} else {
    $whereClauses = ["1=0"];
}

if (!empty($status)) {
    $whereClauses[] = "status_code = :status";
    $params[':status'] = $status;
}

if ($trafficFilter === 'google') {
    $whereClauses[] = "(user_agent LIKE '%Googlebot%' OR user_agent LIKE '%Google %')";
} elseif ($trafficFilter === 'non-google') {
    $whereClauses[] = "(user_agent NOT LIKE '%Googlebot%' AND user_agent NOT LIKE '%Google %')";
}

// Exclude URLs containing 'monitor/logs'
$whereClauses[] = "url NOT LIKE '%monitor/logs%'";

// Search query with URL encoding for SQL LIKE
if (!empty($searchQuery)) {
    // Encode the search term to match how URLs are stored in database
    $encodedSearch = rawurlencode($searchQuery);
    // Search in both encoded URL, decoded URL (if stored), and user agent
    // Use LIKE %% for partial matching
    $whereClauses[] = "(url LIKE :search_url OR url LIKE :search_encoded OR user_agent LIKE :search_ua)";
    $searchPattern = "%$searchQuery%";
    $encodedPattern = "%$encodedSearch%";
    $params[':search_url'] = $searchPattern;
    $params[':search_encoded'] = $encodedPattern;
    $params[':search_ua'] = $searchPattern;
}

// Response time filter (in milliseconds)
if (!empty($responseTimeMin)) {
    $whereClauses[] = "response_time_ms >= :response_time_min";
    $params[':response_time_min'] = floatval($responseTimeMin);
}
if (!empty($responseTimeMax)) {
    $whereClauses[] = "response_time_ms <= :response_time_max";
    $params[':response_time_max'] = floatval($responseTimeMax);
}

$sqlWhere = implode(' AND ', $whereClauses);

// --- EXPORT CSV ---
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    // Disable error reporting to prevent warnings in CSV output
    error_reporting(0);
    ini_set('display_errors', 0);

    try {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="logs.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel
        fputcsv($output, ['Date (Tehran)', 'Time (Tehran)', 'IP', 'Method', 'URL', 'Status', 'Response Time (ms)', 'User Agent'], ',', '"', '\\');

        $stmt = $db->prepare("SELECT ip, date_time, method, url, status_code, response_time_ms, user_agent FROM access_logs WHERE $sqlWhere ORDER BY date_time DESC");
        $stmt->execute($params);

        $rowCount = 0;
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $dt = getTehranTimeObj($row['date_time']);
            if ($dt) {
                $currentTime = $dt->format('H:i');
                if ($currentTime >= $timeFrom && $currentTime <= $timeTo) {
                    // URL decode the URL for better readability
                    $decodedUrl = rawurldecode($row['url']);

                    fputcsv($output, [
                        $dt->format('Y-m-d'),
                        $dt->format('H:i:s'),
                        $row['ip'],
                        $row['method'],
                        $decodedUrl,
                        $row['status_code'],
                        $row['response_time_ms'],
                        $row['user_agent']
                    ], ',', '"', '\\');
                    $rowCount++;
                }
            }
        }
        fclose($output);
        exit;

    } catch (Exception $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'CSV export failed: ' . $e->getMessage()]);
        exit;
    }
}

// --- ACTION: TABLE & CHARTS (Shared Data Fetching) ---
try {
    // Fetch all rows for the selected dates to apply Tehran Time Filter accurately
    $dataStmt = $db->prepare("SELECT * FROM access_logs WHERE $sqlWhere ORDER BY id DESC");
    $dataStmt->execute($params);
    $allRows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

    // Process Rows: Convert Time, Filter by Interval, Sort
    $processedRows = [];
    // Parse the user's date range once for efficient comparison
    $userFromDate = new DateTime($dateFrom . ' 00:00:00', new DateTimeZone('Asia/Tehran'));
    $userToDate = new DateTime($dateTo . ' 23:59:59', new DateTimeZone('Asia/Tehran'));

    foreach ($allRows as $row) {
        $dt = getTehranTimeObj($row['date_time']);
        if (!$dt) continue;

        // Decode URL for display
        $row['url_decoded'] = rawurldecode($row['url']);

        // Normalize Tehran date to start of day for comparison
        $tehranDate = new DateTime($dt->format('Y-m-d') . ' 00:00:00', new DateTimeZone('Asia/Tehran'));

        // Check if this log's Tehran date is within the user's selected date range
        if ($tehranDate < $userFromDate || $tehranDate > $userToDate) {
            continue;
        }

        $currentTime = $dt->format('H:i');

        // Apply Time Interval Filter
        if ($currentTime >= $timeFrom && $currentTime <= $timeTo) {
            $row['tehran_date'] = $dt->format('Y-m-d');
            $row['tehran_time'] = $dt->format('H:i:s');
            $row['tehran_timestamp'] = $dt->getTimestamp();
            // Add URL decoded version for display and search
            $row['url_decoded'] = rawurldecode($row['url']);
            $processedRows[] = $row;
        }
    }

    // Sort by Tehran Timestamp Descending
    usort($processedRows, function($a, $b) {
        return $b['tehran_timestamp'] <=> $a['tehran_timestamp'];
    });

    // --- ACTION: TABLE ---
    if ($action === 'table') {
        $totalRecords = count($processedRows);
        $totalPages = ceil($totalRecords / $perPage);
        $offset = ($page - 1) * $perPage;
        $pagedRows = array_slice($processedRows, $offset, $perPage);

        echo json_encode([
            'rows' => $pagedRows,
            'pagination' => [
                'current' => $page,
                'total' => $totalPages,
                'total_records' => $totalRecords
            ]
        ]);
    }

    // --- ACTION: CHARTS ---
    elseif ($action === 'charts') {
        // Use $processedRows which are already filtered by Date, Status, Traffic, and Time
        
        // 1. Response Time Trend
        $hourlyData = [];
        foreach ($processedRows as $row) {
            $hour = date('H:00', $row['tehran_timestamp']);
            if (!isset($hourlyData[$hour])) $hourlyData[$hour] = ['sum' => 0, 'count' => 0];
            $hourlyData[$hour]['sum'] += $row['response_time_ms'];
            $hourlyData[$hour]['count']++;
        }
        ksort($hourlyData); // Sort by hour
        $rtLabels = array_keys($hourlyData);
        $rtValues = [];
        foreach ($hourlyData as $data) $rtValues[] = round($data['sum'] / $data['count'], 2);

        // 2. 404s (Fixed Variable Names)
        $counts404 = [];
        foreach ($processedRows as $row) {
            if ($row['status_code'] == 404) {
                $url = $row['url'];
                if (!isset($counts404[$url])) $counts404[$url] = 0;
                $counts404[$url]++;
            }
        }
        arsort($counts404);
        $top404 = array_slice($counts404, 0, 10, true);
        $data404 = ['labels' => array_keys($top404), 'data' => array_values($top404)];

        // 3. Traffic Dist
        $googleCount = 0;
        $nonGoogleCount = 0;
        foreach ($processedRows as $row) {
            $ua = strtolower($row['user_agent']);
            if (strpos($ua, 'googlebot') !== false || strpos($ua, 'google ') !== false) {
                $googleCount++;
            } else {
                $nonGoogleCount++;
            }
        }

        echo json_encode([
            'response_time' => ['labels' => $rtLabels, 'data' => $rtValues],
            'top_404' => $data404,
            'traffic_dist' => [
                'google' => $googleCount,
                'non_google' => $nonGoogleCount
            ]
        ]);
    }

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
<?php

namespace App\Models;

use PDO;
use PDOException;
use DateTime;
use DateTimeZone;

/**
 * AccessLog Model
 * Handles all database operations for access logs
 */
class AccessLog
{
    private $db;

    public function __construct()
    {
        try {
            $this->db = new PDO('sqlite:' . DB_PATH);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new \Exception('Database connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Get Tehran time object from Apache date string
     */
    private function getTehranTimeObj($apacheDateStr)
    {
        $dt = DateTime::createFromFormat('d/M/Y:H:i:s O', $apacheDateStr);
        if (!$dt) return null;
        $dt->setTimezone(new DateTimeZone('Asia/Tehran'));
        return $dt;
    }

    /**
     * Check if user agent belongs to Google
     */
    private function isGoogleUserAgent($ua)
    {
        $google_bot_patterns = [
            '/Googlebot/i',
            '/Googlebot-Image/i',
            '/Googlebot-Video/i',
            '/Storebot-Google/i',
            '/Google-InspectionTool/i',
            '/GoogleOther/i',
            '/Google-CloudVertexBot/i',
            '/AdsBot-Google/i',
            '/Mediapartners-Google/i',
            '/FeedFetcher-Google/i',
            '/Google-Site-Verification/i',
            '/APIs-Google/i',
            '/DuplexWeb-Google/i',
            '/GoogleReadAloud/i',
            '/Google-Favicon/i',
            '/GoogleProducer/i',
        ];

        foreach ($google_bot_patterns as $pattern) {
            if (preg_match($pattern, $ua)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Build WHERE clauses for filtering
     */
    private function buildFilters($filters)
    {
        $params = [];
        $whereClauses = [];

        // Date filtering
        $dateFrom = $filters['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
        $dateTo = $filters['date_to'] ?? date('Y-m-d');

        $tz = new DateTimeZone('Asia/Tehran');
        $fromDt = new DateTime($dateFrom . ' 00:00:00', $tz);
        $toDt = new DateTime($dateTo . ' 23:59:59', $tz);

        $dateList = [];
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
            $whereClauses[] = "(" . implode(' OR ', $likeClauses) . ")";
        } else {
            $whereClauses[] = "1=0";
        }

        // Status filter
        if (!empty($filters['status'])) {
            $whereClauses[] = "status_code = :status";
            $params[':status'] = $filters['status'];
        }

        // Traffic filter - use comprehensive Google detection
        // Note: For SQL filtering, we use a broad pattern since we can't use the complex regex in SQL
        if (isset($filters['traffic']) && $filters['traffic'] === 'google') {
            $whereClauses[] = "(user_agent LIKE '%google%' AND user_agent NOT LIKE '%google search app%')";
        } elseif (isset($filters['traffic']) && $filters['traffic'] === 'non-google') {
            $whereClauses[] = "(user_agent NOT LIKE '%google%' OR user_agent LIKE '%google search app%')";
        }

        // Exclude monitor logs
        $whereClauses[] = "url NOT LIKE '%monitor/logs%'";

        // Search query
        if (!empty($filters['search'])) {
            $encodedSearch = rawurlencode($filters['search']);
            $whereClauses[] = "(url LIKE :search_url OR url LIKE :search_encoded OR user_agent LIKE :search_ua)";
            $params[':search_url'] = "%{$filters['search']}%";
            $params[':search_encoded'] = "%$encodedSearch%";
            $params[':search_ua'] = "%{$filters['search']}%";
        }

        // Response time filters
        if (!empty($filters['response_time_min'])) {
            $whereClauses[] = "response_time_ms >= :response_time_min";
            $params[':response_time_min'] = floatval($filters['response_time_min']);
        }
        if (!empty($filters['response_time_max'])) {
            $whereClauses[] = "response_time_ms <= :response_time_max";
            $params[':response_time_max'] = floatval($filters['response_time_max']);
        }

        return [
            'where' => implode(' AND ', $whereClauses),
            'params' => $params
        ];
    }

    /**
     * Process rows with Tehran time conversion and filtering
     */
    private function processRows($rows, $filters)
    {
        $processedRows = [];
        $timeFrom = $filters['time_from'] ?? '00:00';
        $timeTo = $filters['time_to'] ?? '23:59';
        $dateFrom = $filters['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
        $dateTo = $filters['date_to'] ?? date('Y-m-d');

        $tz = new DateTimeZone('Asia/Tehran');
        $userFromDate = new DateTime($dateFrom . ' 00:00:00', $tz);
        $userToDate = new DateTime($dateTo . ' 23:59:59', $tz);

        foreach ($rows as $row) {
            $dt = $this->getTehranTimeObj($row['date_time']);
            if (!$dt) continue;

            $row['url_decoded'] = rawurldecode($row['url']);

            $tehranDate = new DateTime($dt->format('Y-m-d') . ' 00:00:00', $tz);

            if ($tehranDate < $userFromDate || $tehranDate > $userToDate) {
                continue;
            }

            $currentTime = $dt->format('H:i');

            if ($currentTime >= $timeFrom && $currentTime <= $timeTo) {
                $row['tehran_date'] = $dt->format('Y-m-d');
                $row['tehran_time'] = $dt->format('H:i:s');
                $row['tehran_timestamp'] = $dt->getTimestamp();
                $processedRows[] = $row;
            }
        }

        // Sort by Tehran timestamp descending
        usort($processedRows, function($a, $b) {
            return $b['tehran_timestamp'] <=> $a['tehran_timestamp'];
        });

        return $processedRows;
    }

    /**
     * Get filtered and paginated logs
     */
    public function getFilteredLogs($filters, $page = 1, $perPage = 50)
    {
        $filterData = $this->buildFilters($filters);

        $stmt = $this->db->prepare("SELECT * FROM access_logs WHERE {$filterData['where']} ORDER BY id DESC");
        $stmt->execute($filterData['params']);
        $allRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $processedRows = $this->processRows($allRows, $filters);

        $totalRecords = count($processedRows);
        $totalPages = ceil($totalRecords / $perPage);
        $offset = ($page - 1) * $perPage;
        $pagedRows = array_slice($processedRows, $offset, $perPage);

        return [
            'rows' => $pagedRows,
            'pagination' => [
                'current' => $page,
                'total' => $totalPages,
                'total_records' => $totalRecords
            ]
        ];
    }

    /**
     * Get response time data for charts
     */
    public function getResponseTimeData($filters)
    {
        $filterData = $this->buildFilters($filters);

        $stmt = $this->db->prepare("SELECT * FROM access_logs WHERE {$filterData['where']} ORDER BY id DESC");
        $stmt->execute($filterData['params']);
        $allRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $processedRows = $this->processRows($allRows, $filters);

        $hourlyData = [];
        foreach ($processedRows as $row) {
            $hour = date('H:00', $row['tehran_timestamp']);
            if (!isset($hourlyData[$hour])) {
                $hourlyData[$hour] = ['sum' => 0, 'count' => 0];
            }
            $hourlyData[$hour]['sum'] += $row['response_time_ms'];
            $hourlyData[$hour]['count']++;
        }

        ksort($hourlyData);

        $rtLabels = array_keys($hourlyData);
        $rtValues = [];
        foreach ($hourlyData as $data) {
            $rtValues[] = round($data['sum'] / $data['count'], 2);
        }

        return [
            'labels' => $rtLabels,
            'data' => $rtValues
        ];
    }

    /**
     * Get top 404 URLs
     */
    public function get404Data($filters)
    {
        $filterData = $this->buildFilters($filters);

        $stmt = $this->db->prepare("SELECT * FROM access_logs WHERE {$filterData['where']} ORDER BY id DESC");
        $stmt->execute($filterData['params']);
        $allRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $processedRows = $this->processRows($allRows, $filters);

        $counts404 = [];
        foreach ($processedRows as $row) {
            if ($row['status_code'] == 404) {
                $url = $row['url'];
                if (!isset($counts404[$url])) {
                    $counts404[$url] = 0;
                }
                $counts404[$url]++;
            }
        }

        arsort($counts404);
        $top404 = array_slice($counts404, 0, 10, true);

        return [
            'labels' => array_keys($top404),
            'data' => array_values($top404)
        ];
    }

    /**
     * Get traffic distribution (Google vs non-Google)
     */
    public function getTrafficDistribution($filters)
    {
        $filterData = $this->buildFilters($filters);

        $stmt = $this->db->prepare("SELECT * FROM access_logs WHERE {$filterData['where']} ORDER BY id DESC");
        $stmt->execute($filterData['params']);
        $allRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $processedRows = $this->processRows($allRows, $filters);

        $googleCount = 0;
        $nonGoogleCount = 0;

        foreach ($processedRows as $row) {
            if ($this->isGoogleUserAgent($row['user_agent'])) {
                $googleCount++;
            } else {
                $nonGoogleCount++;
            }
        }

        return [
            'google' => $googleCount,
            'non_google' => $nonGoogleCount
        ];
    }

    /**
     * Get HTTP status code distribution for pie chart
     */
    public function getStatusDistribution($filters)
    {
        $filterData = $this->buildFilters($filters);

        $stmt = $this->db->prepare("SELECT * FROM access_logs WHERE {$filterData['where']} ORDER BY id DESC");
        $stmt->execute($filterData['params']);
        $allRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $processedRows = $this->processRows($allRows, $filters);

        $distribution = [
            '2xx' => 0,
            '3xx' => 0,
            '4xx' => 0,
            '5xx' => 0
        ];

        foreach ($processedRows as $row) {
            $status = intval($row['status_code']);
            if ($status >= 200 && $status < 300) {
                $distribution['2xx']++;
            } elseif ($status >= 300 && $status < 400) {
                $distribution['3xx']++;
            } elseif ($status >= 400 && $status < 500) {
                $distribution['4xx']++;
            } elseif ($status >= 500 && $status < 600) {
                $distribution['5xx']++;
            }
        }

        return $distribution;
    }

    /**
     * Truncate all logs
     */
    public function truncate()
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM access_logs");
        $countBefore = $stmt->fetchColumn();

        $this->db->exec("DELETE FROM access_logs");
        $this->db->exec("DELETE FROM sqlite_sequence WHERE name='access_logs'");

        return $countBefore;
    }

    /**
     * Export logs as CSV
     */
    public function exportCsv($filters)
    {
        $filterData = $this->buildFilters($filters);
        $timeFrom = $filters['time_from'] ?? '00:00';
        $timeTo = $filters['time_to'] ?? '23:59';

        $stmt = $this->db->prepare("SELECT ip, date_time, method, url, status_code, response_time_ms, user_agent FROM access_logs WHERE {$filterData['where']} ORDER BY date_time DESC");
        $stmt->execute($filterData['params']);

        $rows = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $dt = $this->getTehranTimeObj($row['date_time']);
            if ($dt) {
                $currentTime = $dt->format('H:i');
                if ($currentTime >= $timeFrom && $currentTime <= $timeTo) {
                    $rows[] = [
                        'date' => $dt->format('Y-m-d'),
                        'time' => $dt->format('H:i:s'),
                        'ip' => $row['ip'],
                        'method' => $row['method'],
                        'url' => rawurldecode($row['url']),
                        'status' => $row['status_code'],
                        'response_time' => $row['response_time_ms'],
                        'user_agent' => $row['user_agent']
                    ];
                }
            }
        }

        return $rows;
    }
}

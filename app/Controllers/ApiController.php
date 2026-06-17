<?php

namespace App\Controllers;

use App\Core\Request;
use App\Models\AccessLog;

/**
 * API Controller
 * Handles all AJAX API endpoints
 */
class ApiController
{
    private $logModel;

    public function __construct()
    {
        $this->logModel = new AccessLog();
    }

    /**
     * Return JSON response
     */
    private function jsonResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Get filters from request
     */
    private function getFilters()
    {
        $request = Request::getInstance();

        return [
            'date_from' => $request->get('date_from', date('Y-m-d', strtotime('-7 days'))),
            'date_to' => $request->get('date_to', date('Y-m-d')),
            'time_from' => $request->get('time_from', '00:00'),
            'time_to' => $request->get('time_to', '23:59'),
            'status' => $request->get('status', ''),
            'traffic' => $request->get('traffic', 'all'),
            'search' => $request->get('search', ''),
            'response_time_min' => $request->get('response_time_min', ''),
            'response_time_max' => $request->get('response_time_max', ''),
        ];
    }

    /**
     * Get chart data
     */
    public function charts()
    {
        $filters = $this->getFilters();

        try {
            $responseData = $this->logModel->getResponseTimeData($filters);
            $data404 = $this->logModel->get404Data($filters);
            $trafficDist = $this->logModel->getTrafficDistribution($filters);
            $statusDist = $this->logModel->getStatusDistribution($filters);

            $this->jsonResponse([
                'response_time' => $responseData,
                'top_404' => $data404,
                'traffic_dist' => $trafficDist,
                'status_dist' => $statusDist
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get table data
     */
    public function table()
    {
        $filters = $this->getFilters();
        $page = max(1, intval(Request::getInstance()->get('page', 1)));

        try {
            $result = $this->logModel->getFilteredLogs($filters, $page);
            $this->jsonResponse($result);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Truncate all logs
     */
    public function truncate()
    {
        try {
            $deleted = $this->logModel->truncate();
            $this->jsonResponse([
                'success' => true,
                'deleted' => $deleted
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse([
                'error' => 'Database error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export logs as CSV
     */
    public function exportCsv()
    {
        $filters = $this->getFilters();

        // Disable error reporting for CSV output
        error_reporting(0);
        ini_set('display_errors', 0);

        try {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="logs.csv"');
            header('Pragma: no-cache');
            header('Expires: 0');

            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
            fputcsv($output, ['Date (Tehran)', 'Time (Tehran)', 'IP', 'Method', 'URL', 'Status', 'Response Time (ms)', 'User Agent'], ',', '"', '\\');

            $rows = $this->logModel->exportCsv($filters);

            foreach ($rows as $row) {
                fputcsv($output, $row, ',', '"', '\\');
            }

            fclose($output);
            exit;
        } catch (\Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'CSV export failed: ' . $e->getMessage()]);
            exit;
        }
    }
}

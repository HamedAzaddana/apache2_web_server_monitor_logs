<?php
/**
 * Dashboard View
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= APP_TITLE ?> Monitor</title>
    <link href="<?= url('css/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="<?= url('css/all.min.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('css/flatpickr.min.css') ?>">
    <link rel="stylesheet" href="<?= url('css/sweetalert2.min.css') ?>">
    <script src="<?= url('js/highcharts.js') ?>"></script>
    <script src="<?= url('js/jquery-3.6.0.min.js') ?>"></script>
    <script src="<?= url('js/flatpickr.min.js') ?>"></script>
    <script src="<?= url('js/flatpickr-fa.js') ?>"></script>
    <script src="<?= url('js/sweetalert2.all.min.js') ?>"></script>

    <style>
        :root { --primary: #059669; --bg: #f0fdf4; --card: #ffffff; --text: #334155; }
        body { background-color: var(--bg); color: var(--text); font-family: 'Segoe UI', sans-serif; }
        .card { background: var(--card); border: none; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .btn-primary { background-color: var(--primary); border-color: var(--primary); }
        .btn-primary:hover { background-color: #047857; }
        .table th { color: #64748b; font-weight: 600; font-size: 0.85rem; text-transform: uppercase; }
        .loading-overlay { position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(255,255,255,0.8); z-index:9999; display:none; justify-content:center; align-items:center; }
        .spinner { width: 40px; height: 40px; border: 4px solid #e2e8f0; border-top: 4px solid var(--primary); border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .timezone-badge { background: #e2e8f0; color: #475569; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: 600; }
        .ua-tooltip { cursor: help; border-bottom: 1px dotted #64748b; }
    </style>
    <script>
        // Set base URL for JavaScript
        const BASE_URL = '<?= BASE_URL ?>';
    </script>
</head>
<body>

<div class="loading-overlay" id="loader"><div class="spinner"></div></div>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 style="color: var(--primary)"><i class="fas fa-chart-line me-2"></i><?= APP_TITLE ?> Logs</h3>
            <span class="timezone-badge"><i class="fas fa-clock me-1"></i> Asia/Tehran (UTC+3:30)</span>
        </div>
        <div class="d-flex gap-2">
            <button id="btnTruncateLogs" class="btn btn-warning btn-sm">
                <i class="fas fa-trash-alt me-1"></i>Truncate All Logs
            </button>
            <a href="<?= url('logout') ?>" class="btn btn-outline-danger btn-sm">Logout</a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card p-3">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="small text-muted">Date Range</label>
                <input type="text" id="filterDateRange" class="form-control" placeholder="Select Dates">
            </div>
            <div class="col-md-3">
                <label class="small text-muted">Time Interval</label>
                <div class="input-group">
                    <input type="text" id="filterTimeFrom" class="form-control" placeholder="Start Time">
                    <span class="input-group-text">to</span>
                    <input type="text" id="filterTimeTo" class="form-control" placeholder="End Time">
                </div>
            </div>
            <div class="col-md-2">
                <label class="small text-muted">Status</label>
                <select id="filterStatus" class="form-select">
                    <option value="">All</option>
                    <option value="200">200 OK</option>
                    <option value="300">300 Multiple Choices</option>
                    <option value="301">301 Moved Permanently</option>
                    <option value="302">302 Found</option>
                    <option value="303">303 See Other</option>
                    <option value="304">304 Not Modified</option>
                    <option value="307">307 Temporary Redirect</option>
                    <option value="308">308 Permanent Redirect</option>
                    <option value="404">404 Not Found</option>
                    <option value="500">500 Error</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="small text-muted">Traffic</label>
                <select id="filterTraffic" class="form-select">
                    <option value="all">All Traffic</option>
                    <option value="google">Google Bots</option>
                    <option value="non-google">Non-Google</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-primary w-100" onclick="refreshAll()"><i class="fas fa-sync-alt"></i> Apply</button>
            </div>
        </div>
        <div class="row g-3 mt-2">
            <div class="col-md-4">
                <label class="small text-muted">Search URL or User Agent</label>
                <input type="text" id="filterSearch" class="form-control" placeholder="Search in URL and User Agent...">
            </div>
            <div class="col-md-3">
                <label class="small text-muted">Response Time (ms)</label>
                <div class="input-group">
                    <input type="number" id="responseTimeMin" class="form-control" placeholder="Min ms" min="0">
                    <span class="input-group-text">to</span>
                    <input type="number" id="responseTimeMax" class="form-control" placeholder="Max ms" min="0">
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row">
        <div class="col-md-8">
            <div class="card p-3">
                <h6 class="text-muted">Avg Response Time (ms)</h6>
                <div id="chartRT" style="height: 300px;"></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3">
                <h6 class="text-muted">Traffic Distribution</h6>
                <div id="chartTraffic" style="height: 300px;"></div>
            </div>
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-md-4">
            <div class="card p-3">
                <h6 class="text-muted">HTTP Status Distribution</h6>
                <div id="chartStatus" style="height: 300px;"></div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card p-3">
                <h6 class="text-muted">Top 404 URLs</h6>
                <div id="chart404" style="height: 300px;"></div>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card p-3 mt-3">
        <div class="d-flex justify-content-between mb-3">
            <h6 class="text-muted mb-0">Recent Logs</h6>
            <a href="#" id="csvLink" class="btn btn-sm btn-outline-success" target="_blank"><i class="fas fa-file-csv"></i> Export CSV</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>IP</th>
                        <th>Method</th>
                        <th>URL</th>
                        <th>Status</th>
                        <th>Ms</th>
                        <th>User Agent</th>
                    </tr>
                </thead>
                <tbody id="logTableBody">
                    <tr id="tableLoadingRow">
                        <td colspan="8" class="text-center py-5">
                            <div class="d-flex flex-column align-items-center">
                                <div class="spinner-border text-success mb-3" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <span class="text-muted">Loading logs...</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <nav>
            <ul class="pagination justify-content-center" id="pagination"></ul>
        </nav>
    </div>
</div>

<script>
    // --- Global Configuration & State ---
    let currentPage = 1;

    // Default to Last 7 Days
    let endDate = new Date();
    let startDate = new Date();
    startDate.setDate(startDate.getDate() - 7);

    // Helper function to get local date string (YYYY-MM-DD) without UTC conversion
    function getLocalDateString(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    let dateFrom = getLocalDateString(startDate);
    let dateTo = getLocalDateString(endDate);
    let timeFrom = "00:00";
    let timeTo = "23:59";

    // Helper to build URL with BASE_URL
    function apiUrl(path) {
        return BASE_URL + path;
    }

    // --- Initialize Date & Time Pickers ---
    flatpickr("#filterDateRange", {
        mode: "range",
        dateFormat: "Y-m-d",
        defaultDate: [dateFrom, dateTo],
        onChange: function(selectedDates, dateStr, instance) {
            if (selectedDates.length === 2) {
                dateFrom = getLocalDateString(selectedDates[0]);
                dateTo = getLocalDateString(selectedDates[1]);
                debouncedRefresh();
            }
        }
    });

    flatpickr("#filterTimeFrom", {
        enableTime: true,
        noCalendar: true,
        dateFormat: "H:i",
        defaultDate: timeFrom,
        onChange: function(selectedDates, dateStr, instance) {
            timeFrom = dateStr;
            debouncedRefresh();
        }
    });

    flatpickr("#filterTimeTo", {
        enableTime: true,
        noCalendar: true,
        dateFormat: "H:i",
        defaultDate: timeTo,
        onChange: function(selectedDates, dateStr, instance) {
            timeTo = dateStr;
            debouncedRefresh();
        }
    });

    // --- Debounce Function ---
    let debounceTimer;
    function debouncedRefresh() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(refreshAll, 500);
    }

    // Search input with 1500ms debounce
    let searchDebounceTimer;
    $('#filterSearch').on('input', function() {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(refreshAll, 1500);
    });

    // Response time inputs with 500ms debounce
    $('#responseTimeMin, #responseTimeMax').on('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(refreshAll, 500);
    });

    $('#filterStatus, #filterTraffic').on('change', debouncedRefresh);

    // --- Helper Functions ---
    function showLoader(show) {
        $('#loader').css('display', show ? 'flex' : 'none');
    }

    function getParams() {
        return {
            date_from: dateFrom,
            date_to: dateTo,
            time_from: timeFrom,
            time_to: timeTo,
            status: $('#filterStatus').val(),
            traffic: $('#filterTraffic').val(),
            search: $('#filterSearch').val(),
            response_time_min: $('#responseTimeMin').val(),
            response_time_max: $('#responseTimeMax').val()
        };
    }

    function refreshAll() {
        currentPage = 1;
        loadCharts();
        loadTable();
        updateCsvLink();
    }

    // --- AJAX Loaders ---
    function loadCharts() {
        showLoader(true);
        const params = getParams();

        $.get(apiUrl('/api/charts'), params, function(res) {
            showLoader(false);
            if(!res || res.error) { console.error("Chart Error:", res); return; }

            if(res.response_time && res.response_time.labels.length > 0) {
                Highcharts.chart('chartRT', {
                    chart: { type: 'spline', backgroundColor: 'transparent' },
                    title: { text: null },
                    xAxis: { categories: res.response_time.labels },
                    yAxis: { title: { text: 'ms' } },
                    series: [{ name: 'Avg Time', data: res.response_time.data, color: '#059669' }]
                });
            } else {
                $('#chartRT').html('<p class="text-center text-muted pt-5">No data for this period</p>');
            }

            if(res.traffic_dist) {
                Highcharts.chart('chartTraffic', {
                    chart: { type: 'pie', backgroundColor: 'transparent' },
                    title: { text: null },
                    series: [{
                        name: 'Requests',
                        colorByPoint: true,
                        data: [
                            { name: 'Google Bot', y: res.traffic_dist.google, color: '#4285F4' },
                            { name: 'Others', y: res.traffic_dist.non_google, color: '#94a3b8' }
                        ]
                    }]
                });
            }

            // Status Distribution Pie Chart
            if(res.status_dist) {
                const statusData = [
                    { name: '2xx (Success)', y: res.status_dist['2xx'] || 0, color: '#10b981' },
                    { name: '3xx (Redirect)', y: res.status_dist['3xx'] || 0, color: '#3b82f6' },
                    { name: '4xx (Client Error)', y: res.status_dist['4xx'] || 0, color: '#f59e0b' },
                    { name: '5xx (Server Error)', y: res.status_dist['5xx'] || 0, color: '#ef4444' }
                ].filter(item => item.y > 0);

                if (statusData.length > 0) {
                    Highcharts.chart('chartStatus', {
                        chart: { type: 'pie', backgroundColor: 'transparent' },
                        title: { text: null },
                        plotOptions: {
                            pie: {
                                allowPointSelect: true,
                                cursor: 'pointer',
                                dataLabels: {
                                    enabled: true,
                                    format: '<b>{point.name}</b>: {point.percentage:.1f} %'
                                }
                            }
                        },
                        series: [{
                            name: 'Requests',
                            colorByPoint: true,
                            data: statusData
                        }]
                    });
                } else {
                    $('#chartStatus').html('<p class="text-center text-muted pt-5">No data for this period</p>');
                }
            }

            if(res.top_404 && res.top_404.labels.length > 0) {
                Highcharts.chart('chart404', {
                    chart: { type: 'bar', backgroundColor: 'transparent' },
                    title: { text: null },
                    xAxis: { categories: res.top_404.labels },
                    yAxis: { title: { text: 'Count' } },
                    series: [{ name: '404s', data: res.top_404.data, color: '#ef4444' }]
                });
            } else {
                $('#chart404').html('<p class="text-center text-muted pt-5">No 404 errors found</p>');
            }
        }).fail(function(jq, status, err) {
            showLoader(false);
            console.error("Chart Load Failed:", status, err);
        });
    }

    function loadTable() {
        showLoader(true);
        const params = getParams();
        params.page = currentPage;

        // Show table loading state
        $('#logTableBody').html(`
            <tr id="tableLoadingRow">
                <td colspan="8" class="text-center py-5">
                    <div class="d-flex flex-column align-items-center">
                        <div class="spinner-border text-success mb-3" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <span class="text-muted">Loading logs...</span>
                    </div>
                </td>
            </tr>
        `);

        $.get(apiUrl('/api/table'), params, function(res) {
            showLoader(false);
            if(!res || res.error) { console.error("Table Error:", res); return; }

            let html = '';
            if(res.rows && res.rows.length > 0) {
                res.rows.forEach(row => {
                    let badgeClass = row.status_code == 200 ? 'bg-success' : (row.status_code == 404 ? 'bg-warning' : (row.status_code >= 300 && row.status_code < 400 ? 'bg-info' : 'bg-danger'));
                    let displayUrl = row.url_decoded || row.url;
                    let urlLink = `<a href="${row.url}" target="_blank" style="color: #059669; text-decoration: none;">${displayUrl}</a>`;
                    let uaTitle = row.user_agent ? row.user_agent.replace(/"/g, '&quot;') : 'N/A';

                    html += `<tr>
                        <td>${row.tehran_date}</td>
                        <td>${row.tehran_time}</td>
                        <td>${row.ip}</td>
                        <td><span class="badge bg-secondary">${row.method}</span></td>
                        <td style="max-width:150px;overflow:hidden;text-overflow:ellipsis">${urlLink}</td>
                        <td><span class="badge ${badgeClass}">${row.status_code}</span></td>
                        <td>${row.response_time_ms}</td>
                        <td>
                            <span class="ua-tooltip" data-bs-toggle="tooltip" data-bs-placement="left" title="${uaTitle}">
                                <i class="fas fa-desktop"></i> View UA
                            </span>
                        </td>
                    </tr>`;
                });
            } else {
                html = '<tr><td colspan="8" class="text-center text-muted py-4">No logs found for this filter.</td></tr>';
            }

            $('#logTableBody').html(html);

            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            if(res.pagination) renderSmartPagination(res.pagination);
        }).fail(function(jq, status, err) {
            showLoader(false);
            console.error("Table Load Failed:", status, err);
        });
    }

    // --- Pagination Logic ---
    function renderSmartPagination(p) {
        let html = '';
        let total = p.total;
        let current = p.current;

        if (total <= 1) { $('#pagination').html(''); return; }

        html += `<li class="page-item ${current === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="goToPage(${current - 1})">&laquo;</a>
                 </li>`;

        let start = Math.max(1, current - 4);
        let end = Math.min(total, current + 4);

        if (start > 1) {
            html += `<li class="page-item"><a class="page-link" href="#" onclick="goToPage(1)">1</a></li>`;
            if (start > 2) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }

        for (let i = start; i <= end; i++) {
            let active = i == current ? 'active' : '';
            html += `<li class="page-item ${active}"><a class="page-link" href="#" onclick="goToPage(${i})">${i}</a></li>`;
        }

        if (end < total) {
            if (end < total - 1) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            html += `<li class="page-item"><a class="page-link" href="#" onclick="goToPage(${total})">${total}</a></li>`;
        }

        html += `<li class="page-item ${current === total ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="goToPage(${current + 1})">&raquo;</a>
                 </li>`;

        $('#pagination').html(html);
    }

    function goToPage(page) {
        if (page < 1) return;
        currentPage = page;
        loadTable();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // --- CSV Export Link ---
    function updateCsvLink() {
        const params = getParams();
        const query = new URLSearchParams(params).toString();
        $('#csvLink').attr('href', apiUrl('/api/export') + '?' + query);
    }

    // --- Global AJAX Error Handler for 403 Unauthorized ---
    $(document).ajaxError(function(event, jqxhr, settings, thrownError) {
        if (jqxhr.status === 403) {
            window.location.href = apiUrl('/login');
        }
    });

    // --- Truncate Logs Handler ---
    $('#btnTruncateLogs').on('click', function() {
        Swal.fire({
            title: 'Are you sure?',
            text: 'This will delete ALL log records. This action cannot be undone!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete all logs!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: apiUrl('/api/truncate'),
                    type: 'POST',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Deleted!',
                                text: response.deleted + ' log records have been deleted.',
                                icon: 'success',
                                confirmButtonColor: '#059669'
                            }).then(() => {
                                refreshAll();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: response.error || 'Failed to truncate logs',
                                icon: 'error',
                                confirmButtonColor: '#dc3545'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: 'Error',
                            text: 'Failed to connect to server',
                            icon: 'error',
                            confirmButtonColor: '#dc3545'
                        });
                    }
                });
            }
        });
    });

    // --- Initial Load ---
    $(document).ready(function() {
        refreshAll();
    });
</script>
<script src="<?= url('js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>

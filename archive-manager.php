<?php
// Include database connection
require_once 'conn/conn.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Archive Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .archive-card {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            transition: all 0.3s ease;
        }
        .archive-card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .loading {
            display: none;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .stats-card-secondary {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
    </style>
</head>
<body>

<?php
// Include database connection
require_once 'conn/conn.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Attendance Archive Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    <style>
        .navbar-custom {
            background-color: #343a40;
        }
        .navbar-custom .navbar-brand,
        .navbar-custom .nav-link {
            color: #f8f9fa;
        }
        .navbar-custom .nav-link.active {
            font-weight: bold;
            color: #ffc107;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-custom">
    <a class="navbar-brand ml-4" href="#">QR Code Attendance System</a>
    <button
        class="navbar-toggler"
        type="button"
        data-toggle="collapse"
        data-target="#navbarSupportedContent"
        aria-controls="navbarSupportedContent"
        aria-expanded="false"
        aria-label="Toggle navigation"
    >
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                <a class="nav-link" href="./index.php">Home</a>
            </li>
            <li class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'masterlist.php' ? 'active' : '' ?>">
                <a class="nav-link" href="./masterlist.php">List of Students</a>
            </li>
            <li class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'archive-manager.php' ? 'active' : '' ?>">
                <a class="nav-link" href="./archive-manager.php">Archive</a>
            </li>
        </ul>
        <ul class="navbar-nav ml-auto">
            <li class="nav-item mr-3">
                <a class="nav-link" href="#">Logout</a>
            </li>
        </ul>
    </div>
</nav>
<div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <h1 class="h3 mb-4 text-center">
                    <i class="bi bi-archive-fill"></i> Attendance Archive Manager
                </h1>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="bi bi-archive fs-1"></i>
                        <h3 class="card-title" id="totalArchived">0</h3>
                        <p class="card-text">Total Archived</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card-secondary">
                    <div class="card-body text-center">
                        <i class="bi bi-calendar-check fs-1"></i>
                        <h3 class="card-title" id="activeRecords">0</h3>
                        <p class="card-text">Active Records</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-calendar-range fs-1"></i>
                        <h3 class="card-title" id="uniqueDays">0</h3>
                        <p class="card-text">Unique Days</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-arrow-down-up fs-1"></i>
                        <h3 class="card-title" id="latestDate">-</h3>
                        <p class="card-text">Latest Archive</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Archive Section -->
            <div class="col-md-6">
                <div class="card archive-card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-box-arrow-in-down"></i> Archive Attendance
                        </h5>
                    </div>
                    <div class="card-body">
                        <form id="archiveForm">
                            <div class="mb-3">
                                <label for="archiveDate" class="form-label">Archive records up to:</label>
                                <input type="date" class="form-control" id="archiveDate" name="archive_date" 
                                       value="<?php echo date('Y-m-d', strtotime('-1 day')); ?>" required>
                                <small class="form-text text-muted">
                                    All attendance records on or before this date will be archived
                                </small>
                            </div>
                            <button type="submit" class="btn btn-primary w-100" id="archiveBtn">
                                <span class="loading spinner-border spinner-border-sm me-2" role="status"></span>
                                <i class="bi bi-archive"></i> Archive Records
                            </button>
                        </form>
                        <div id="archiveResult" class="mt-3"></div>
                    </div>
                </div>
            </div>

            <!-- Download Section -->
            <div class="col-md-6">
                <div class="card archive-card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-download"></i> Download Archived Records
                        </h5>
                    </div>
                    <div class="card-body">
                        <form id="downloadForm">
                            <div class="mb-3">
                                <label for="startDate" class="form-label">Start Date:</label>
                                <input type="date" class="form-control" id="startDate" name="start_date" 
                                       value="<?php echo date('Y-m-d', strtotime('-7 days')); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="endDate" class="form-label">End Date:</label>
                                <input type="date" class="form-control" id="endDate" name="end_date" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <button type="button" class="btn btn-success w-100" id="downloadBtn">
                                <i class="bi bi-file-earmark-excel"></i> Download Excel
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daily Breakdown -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-calendar3"></i> Daily Archive Summary
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped" id="dailyTable">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Records Archived</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="dailyBreakdown">
                                    <!-- Data will be loaded via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Load archive summary on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadArchiveSummary();
        });

        // Load archive summary
        function loadArchiveSummary() {
            fetch('endpoint/get-archive-summary.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('totalArchived').textContent = data.summary.total_archived || 0;
                        document.getElementById('activeRecords').textContent = data.summary.active_records || 0;
                        document.getElementById('uniqueDays').textContent = data.summary.unique_days || 0;
                        document.getElementById('latestDate').textContent = data.summary.latest_date || '-';
                        
                        // Load daily breakdown
                        const dailyBreakdown = document.getElementById('dailyBreakdown');
                        dailyBreakdown.innerHTML = '';
                        
                        if (data.summary.daily_breakdown && data.summary.daily_breakdown.length > 0) {
                            data.summary.daily_breakdown.forEach(day => {
                                const row = document.createElement('tr');
                                row.innerHTML = `
                                    <td>${day.attendance_date}</td>
                                    <td>${day.record_count}</td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="downloadSingleDay('${day.attendance_date}')">
                                            <i class="bi bi-download"></i>
                                        </button>
                                    </td>
                                `;
                                dailyBreakdown.appendChild(row);
                            });
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading archive summary:', error);
                });
        }

        // Archive attendance records
        document.getElementById('archiveForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const archiveBtn = document.getElementById('archiveBtn');
            const loading = archiveBtn.querySelector('.loading');
            const archiveDate = document.getElementById('archiveDate').value;
            
            // Show loading
            loading.style.display = 'inline-block';
            archiveBtn.disabled = true;
            
            fetch('endpoint/archive-attendance.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    archive_date: archiveDate
                })
            })
            .then(response => response.json())
            .then(data => {
                const resultDiv = document.getElementById('archiveResult');
                if (data.success) {
                    resultDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                    loadArchiveSummary(); // Refresh summary
                } else {
                    resultDiv.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                }
            })
            .catch(error => {
                document.getElementById('archiveResult').innerHTML = 
                    `<div class="alert alert-danger">Error: ${error.message}</div>`;
            })
            .finally(() => {
                loading.style.display = 'none';
                archiveBtn.disabled = false;
            });
        });

        // Download archived records
        document.getElementById('downloadBtn').addEventListener('click', function() {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            
            if (startDate && endDate) {
                window.location.href = `endpoint/download-archived-attendance.php?start_date=${startDate}&end_date=${endDate}`;
            }
        });

        // Download single day
        function downloadSingleDay(date) {
            window.location.href = `endpoint/download-archived-attendance.php?start_date=${date}&end_date=${date}`;
        }
    </script>
</body>
</html>
<?php
// Close connection
$conn = null;
?>

<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include ('./conn/conn.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Archive Manager - QR Attendance</title>
    
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    
    <style>
        .archive-stats-card {
            border-radius: 10px;
            color: white;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .archive-stats-icon {
            font-size: 2.5rem;
            opacity: 0.9;
            margin-bottom: 10px;
        }
        
        .archive-stats-number {
            font-size: 2rem;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .archive-table {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .loading {
            display: none;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
        </ul>
    </nav>

    <!-- Sidebar -->
    <?php include 'adminlte-sidebar.php'; ?>

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <!-- Content Header -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Archive Manager</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <li class="breadcrumb-item active">Archive</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <section class="content">
            <div class="container-fluid">
                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6">
                        <div class="archive-stats-card bg-gradient-primary">
                            <div class="archive-stats-icon">
                                <i class="fas fa-archive"></i>
                            </div>
                            <div class="archive-stats-number" id="totalArchived">0</div>
                            <div>Total Archived</div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6">
                        <div class="archive-stats-card bg-gradient-success">
                            <div class="archive-stats-icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="archive-stats-number" id="activeRecords">0</div>
                            <div>Active Records</div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6">
                        <div class="archive-stats-card bg-gradient-info">
                            <div class="archive-stats-icon">
                                <i class="fas fa-calendar-range"></i>
                            </div>
                            <div class="archive-stats-number" id="uniqueDays">0</div>
                            <div>Unique Days</div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-md-6">
                        <div class="archive-stats-card bg-gradient-warning">
                            <div class="archive-stats-icon">
                                <i class="fas fa-arrow-down-up"></i>
                            </div>
                            <div class="archive-stats-number" id="latestDate">-</div>
                            <div>Latest Archive</div>
                        </div>
                    </div>
                </div>

                <!-- Archive Actions -->
                <div class="row">
                    <!-- Archive Section -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-gradient-primary text-white">
                                <h3 class="card-title">
                                    <i class="fas fa-box-arrow-in-down mr-2"></i> Archive Attendance
                                </h3>
                            </div>
                            <div class="card-body">
                                <form id="archiveForm">
                                    <div class="form-group">
                                        <label for="archiveDate">Archive records up to:</label>
                                        <input type="date" class="form-control" id="archiveDate" name="archive_date" 
                                               value="<?php echo date('Y-m-d', strtotime('-1 day')); ?>" required>
                                        <small class="form-text text-muted">
                                            All attendance records on or before this date will be archived
                                        </small>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-block" id="archiveBtn">
                                        <span class="loading spinner-border spinner-border-sm me-2" role="status"></span>
                                        <i class="fas fa-archive mr-2"></i> Archive Records
                                    </button>
                                </form>
                                <div id="archiveResult" class="mt-3"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Download Section -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-gradient-success text-white">
                                <h3 class="card-title">
                                    <i class="fas fa-download mr-2"></i> Download Archived Records
                                </h3>
                            </div>
                            <div class="card-body">
                                <form id="downloadForm">
                                    <div class="form-group">
                                        <label for="startDate">Start Date:</label>
                                        <input type="date" class="form-control" id="startDate" name="start_date" 
                                               value="<?php echo date('Y-m-d', strtotime('-7 days')); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="endDate">End Date:</label>
                                        <input type="date" class="form-control" id="endDate" name="end_date" 
                                               value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    <button type="button" class="btn btn-success btn-block" id="downloadBtn">
                                        <i class="fas fa-file-excel mr-2"></i> Download Excel
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
                            <div class="card-header bg-gradient-info text-white">
                                <h3 class="card-title">
                                    <i class="fas fa-calendar3 mr-2"></i> Daily Archive Summary
                                </h3>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive archive-table">
                                    <table class="table table-hover">
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
        </section>
    </div>

    <!-- Footer -->
    <footer class="main-footer">
        <strong>QR Code Attendance System &copy; <?php echo date('Y'); ?></strong>
        All rights reserved.
    </footer>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadArchiveSummary();
});

function loadArchiveSummary() {
    fetch('endpoint/get-archive-summary.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('totalArchived').textContent = data.summary.total_archived || 0;
                document.getElementById('activeRecords').textContent = data.summary.active_records || 0;
                document.getElementById('uniqueDays').textContent = data.summary.unique_days || 0;
                document.getElementById('latestDate').textContent = data.summary.latest_date || '-';
                
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
                                    <i class="fas fa-download"></i> Download
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

document.getElementById('archiveForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const archiveBtn = document.getElementById('archiveBtn');
    const loading = archiveBtn.querySelector('.loading');
    const archiveDate = document.getElementById('archiveDate').value;
    
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
            loadArchiveSummary();
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

document.getElementById('downloadBtn').addEventListener('click', function() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    
    if (startDate && endDate) {
        window.location.href = `endpoint/download-archived-attendance.php?start_date=${startDate}&end_date=${endDate}`;
    }
});

function downloadSingleDay(date) {
    window.location.href = `endpoint/download-archived-attendance.php?start_date=${date}&end_date=${date}`;
}
</script>
</body>
</html>
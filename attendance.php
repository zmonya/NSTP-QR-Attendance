<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

date_default_timezone_set('Asia/Manila');
include ('./conn/conn.php');

// Get current admin info
$admin_id = $_SESSION['user_id'];
$admin_role = $_SESSION['role'] ?? 'admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Attendance Scanner - QR Attendance System</title>
    
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    <!-- Select2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    
    <style>
        .scanner-container {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        #qr-reader {
            width: 100%;
            border-radius: 10px;
            border: 3px solid #007bff;
            overflow: hidden;
            display: none;
            background: #000;
        }
        
        #qr-reader__scan_region {
            background: #000;
            min-height: 350px;
        }
        
        #qr-reader__scan_region video {
            width: 100%;
            height: auto;
            object-fit: cover;
        }
        
        #qr-reader__dashboard {
            background: #f8f9fa;
            padding: 10px;
        }
        
        .scanner-placeholder {
            width: 100%;
            height: 350px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            border: 3px solid #007bff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
        }
        
        .qr-detected-box {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border-radius: 10px;
            padding: 25px;
            animation: fadeIn 0.5s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .camera-controls {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 15px;
        }
        
        .status-badge {
            font-size: 0.85rem;
            padding: 5px 12px;
            border-radius: 20px;
        }
        
        .attendance-card {
            max-height: 600px;
            overflow-y: auto;
        }
        
        .scanner-status {
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 15px;
            display: none;
        }
        
        .qr-content-box {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            padding: 10px;
            margin: 15px 0;
            font-family: 'Courier New', monospace;
            word-break: break-all;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
            width: 100%;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .invalid-qr {
            border-color: #dc3545 !important;
            transition: border-color 0.3s ease;
        }
        
        select#qr-reader__camera_selection {
            border: 1px solid #ced4da;
            border-radius: 4px;
            padding: 5px;
            margin-left: 10px;
        }
        
        button#qr-reader__dashboard_button {
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 6px 12px;
            margin-left: 10px;
            cursor: pointer;
        }
        
        button#qr-reader__dashboard_button:hover {
            background: #0069d9;
        }
        
        .student-info {
            font-size: 1.1em;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .attendance-time {
            font-size: 0.9em;
            opacity: 0.9;
        }
        
        /* DataTables custom styles */
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.5em 1em;
        }
        
        .dataTables_empty {
            display: none;
        }
        
        .no-records-container {
            width: 100%;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                    <i class="fas fa-bars"></i>
                </a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="index.php" class="nav-link">Home</a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="attendance.php" class="nav-link">Attendance</a>
            </li>
        </ul>
        
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <a class="nav-link" href="#" data-toggle="tooltip" title="Manila Time">
                    <i class="far fa-clock"></i>
                    <span id="current-time"><?php echo date('h:i A'); ?></span>
                </a>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="fas fa-user-circle"></i>
                    <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <div class="dropdown-divider"></div>
                    <a href="./endpoint/logout.php" class="dropdown-item">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </a>
                </div>
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
                        <h1 class="m-0">
                            <i class="fas fa-qrcode mr-2"></i>Attendance Scanner
                        </h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <li class="breadcrumb-item active">Attendance Scanner</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <section class="content">
            <div class="container-fluid">
                <!-- Scanner Status Alert -->
                <div class="row">
                    <div class="col-12">
                        <div class="scanner-status alert alert-info" id="scannerStatus">
                            <i class="fas fa-info-circle mr-2"></i>
                            <span id="statusMessage">Scanner ready. Click "Start Scanner" to begin.</span>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Left Column: Scanner -->
                    <div class="col-lg-4">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-camera mr-2"></i>QR Code Scanner
                                </h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <!-- Scanner Section -->
                                <div id="scannerSection">
                                    <div class="text-center mb-3">
                                        <h5>Position QR code within frame</h5>
                                        <p class="text-muted">Ensure good lighting for better scanning</p>
                                    </div>
                                    
                                    <!-- QR Reader Container -->
                                    <div id="qr-reader"></div>
                                    
                                    <!-- Placeholder when scanner is off -->
                                    <div id="scannerPlaceholder" class="scanner-placeholder">
                                        <div>
                                            <i class="fas fa-qrcode fa-4x mb-3" style="opacity: 0.8;"></i>
                                            <h5>Scanner is Off</h5>
                                            <p class="mb-0">Click "Start Scanner" to begin</p>
                                        </div>
                                    </div>
                                    
                                    <!-- Camera Controls -->
                                    <div class="camera-controls mt-3">
                                        <button class="btn btn-success" onclick="startScanner()" id="startBtn">
                                            <i class="fas fa-play mr-2"></i>Start Scanner
                                        </button>
                                        <button class="btn btn-danger" onclick="stopScanner()" id="stopBtn" style="display: none;">
                                            <i class="fas fa-stop mr-2"></i>Stop Scanner
                                        </button>
                                    </div>
                                </div>
                                    
                                <!-- QR Detected Section -->
                                <div id="qrDetectedSection" style="display: none;">
                                    <div class="qr-detected-box text-center">
                                        <i class="fas fa-check-circle fa-3x mb-3"></i>
                                        <h4>QR Code Detected!</h4>
                                        <div class="student-info" id="studentInfo">Student QR code successfully scanned</div>
                                        
                                        <div class="qr-content-box">
                                            <small>QR Code:</small>
                                            <div id="qrContent" class="font-weight-bold"></div>
                                        </div>
                                        
                                        <form action="./endpoint/add-attendance.php" method="POST" id="attendanceForm">
                                            <input type="hidden" id="detectedQrCode" name="qr_code">
                                            <div class="mt-4">
                                                <button type="submit" class="btn btn-light btn-lg mr-2">
                                                    <i class="fas fa-check mr-2"></i>Confirm Attendance
                                                </button>
                                                <button type="button" class="btn btn-outline-light" onclick="resumeScanner()">
                                                    <i class="fas fa-redo mr-2"></i>Scan Again
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                
                                <!-- Attendance Success Section -->
                                <div id="attendanceSuccessSection" style="display: none;">
                                    <div class="qr-detected-box text-center" style="background: linear-gradient(135deg, #28a745 0%, #218838 100%);">
                                        <i class="fas fa-check-circle fa-3x mb-3"></i>
                                        <h4>Attendance Recorded!</h4>
                                        <div class="student-info" id="successStudentInfo"></div>
                                        <div class="attendance-time" id="successTime"></div>
                                        <div class="mt-3">
                                            <span class="badge badge-light" id="successStatus"></span>
                                        </div>
                                        <div class="mt-4">
                                            <button type="button" class="btn btn-light btn-lg" onclick="resumeScanner()">
                                                <i class="fas fa-qrcode mr-2"></i>Scan Next Student
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Manual Entry Button -->
                                <div class="mt-4">
                                    <button class="btn btn-outline-secondary btn-block" data-toggle="modal" data-target="#manualEntryModal">
                                        <i class="fas fa-keyboard mr-2"></i>Manual Entry
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Statistics Cards -->
                        <div class="row">
                            <div class="col-12">
                                <div class="info-box bg-gradient-info">
                                    <span class="info-box-icon">
                                        <i class="fas fa-users"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Total Present Today</span>
                                        <span class="info-box-number">
                                            <?php
                                            if ($admin_role == 'super_admin') {
                                                $stmt = $conn->prepare("SELECT COUNT(*) FROM tbl_attendance WHERE DATE(time_in) = CURDATE()");
                                                $stmt->execute();
                                            } else {
                                                $stmt = $conn->prepare("
                                                    SELECT COUNT(*) 
                                                    FROM tbl_attendance a
                                                    INNER JOIN tbl_student s ON a.tbl_student_id = s.tbl_student_id
                                                    LEFT JOIN tbl_admin_sections ads ON s.course_section = ads.course_section
                                                    WHERE DATE(a.time_in) = CURDATE() 
                                                    AND (s.created_by = ? OR ads.user_id = ?)
                                                ");
                                                $stmt->execute([$admin_id, $admin_id]);
                                            }
                                            echo $stmt->fetchColumn();
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <div class="info-box bg-gradient-success">
                                    <span class="info-box-icon">
                                        <i class="fas fa-clock"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">On Time</span>
                                        <span class="info-box-number">
                                            <?php
                                            if ($admin_role == 'super_admin') {
                                                $stmt = $conn->prepare("
                                                    SELECT COUNT(*) 
                                                    FROM tbl_attendance 
                                                    WHERE DATE(time_in) = CURDATE() 
                                                    AND TIME(time_in) <= '08:00:00'
                                                ");
                                                $stmt->execute();
                                            } else {
                                                $stmt = $conn->prepare("
                                                    SELECT COUNT(*) 
                                                    FROM tbl_attendance a
                                                    INNER JOIN tbl_student s ON a.tbl_student_id = s.tbl_student_id
                                                    LEFT JOIN tbl_admin_sections ads ON s.course_section = ads.course_section
                                                    WHERE DATE(a.time_in) = CURDATE() 
                                                    AND TIME(a.time_in) <= '08:00:00'
                                                    AND (s.created_by = ? OR ads.user_id = ?)
                                                ");
                                                $stmt->execute([$admin_id, $admin_id]);
                                            }
                                            echo $stmt->fetchColumn();
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <div class="info-box bg-gradient-warning">
                                    <span class="info-box-icon">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Late</span>
                                        <span class="info-box-number">
                                            <?php
                                            if ($admin_role == 'super_admin') {
                                                $stmt = $conn->prepare("
                                                    SELECT COUNT(*) 
                                                    FROM tbl_attendance 
                                                    WHERE DATE(time_in) = CURDATE() 
                                                    AND TIME(time_in) > '08:00:00'
                                                ");
                                                $stmt->execute();
                                            } else {
                                                $stmt = $conn->prepare("
                                                    SELECT COUNT(*) 
                                                    FROM tbl_attendance a
                                                    INNER JOIN tbl_student s ON a.tbl_student_id = s.tbl_student_id
                                                    LEFT JOIN tbl_admin_sections ads ON s.course_section = ads.course_section
                                                    WHERE DATE(a.time_in) = CURDATE() 
                                                    AND TIME(a.time_in) > '08:00:00'
                                                    AND (s.created_by = ? OR ads.user_id = ?)
                                                ");
                                                $stmt->execute([$admin_id, $admin_id]);
                                            }
                                            echo $stmt->fetchColumn();
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Attendance List -->
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-list-check mr-2"></i>Today's Attendance Records
                                </h3>
                                <div class="card-tools">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-tool dropdown-toggle" data-toggle="dropdown">
                                            <i class="fas fa-cog"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right" role="menu">
                                            <a href="./endpoint/download-attendance-excel.php" class="dropdown-item">
                                                <i class="fas fa-file-excel mr-2"></i>Export to Excel
                                            </a>
                                            <a href="#" class="dropdown-item" onclick="refreshTable()">
                                                <i class="fas fa-sync-alt mr-2"></i>Refresh
                                            </a>
                                            <div class="dropdown-divider"></div>
                                            <a href="archive-manager.php" class="dropdown-item">
                                                <i class="fas fa-archive mr-2"></i>View Archive
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <?php
                                // Fetch attendance records
                                if ($admin_role == 'super_admin') {
                                    $stmt = $conn->prepare("
                                        SELECT a.*, s.student_name, s.course_section 
                                        FROM tbl_attendance a 
                                        LEFT JOIN tbl_student s ON s.tbl_student_id = a.tbl_student_id 
                                        WHERE DATE(a.time_in) = CURDATE()
                                        ORDER BY a.time_in DESC
                                    ");
                                    $stmt->execute();
                                } else {
                                    $stmt = $conn->prepare("
                                        SELECT a.*, s.student_name, s.course_section 
                                        FROM tbl_attendance a 
                                        INNER JOIN tbl_student s ON s.tbl_student_id = a.tbl_student_id
                                        LEFT JOIN tbl_admin_sections ads ON s.course_section = ads.course_section
                                        WHERE DATE(a.time_in) = CURDATE() 
                                        AND (s.created_by = ? OR ads.user_id = ?)
                                        ORDER BY a.time_in DESC
                                    ");
                                    $stmt->execute([$admin_id, $admin_id]);
                                }
                                $attendanceRecords = $stmt->fetchAll();
                                ?>
                                
                                <div class="table-responsive attendance-card">
                                    <table class="table table-hover" id="attendanceTable">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Student Name</th>
                                                <th>Course & Section</th>
                                                <th>Time In</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($attendanceRecords) > 0): ?>
                                                <?php $counter = 1; ?>
                                                <?php foreach ($attendanceRecords as $record): ?>
                                                    <?php
                                                    $attendanceID = $record["tbl_attendance_id"];
                                                    $studentName = $record["student_name"];
                                                    $studentCourse = $record["course_section"];
                                                    $timeIn = $record["time_in"];
                                                    $status = $record["status"] ?? '';
                                                    
                                                    // FIXED: Properly handle DateTime comparison
                                                    $isLate = false;
                                                    if (!empty($timeIn)) {
                                                        $timeObj = strtotime($timeIn);
                                                        $cutoff = strtotime(date('Y-m-d', $timeObj) . ' 08:00:00');
                                                        $isLate = $timeObj > $cutoff;
                                                    }
                                                    
                                                    $statusClass = (strtolower($status) == 'late' || $isLate) ? 'warning' : 'success';
                                                    $statusText = $status ?: ($isLate ? 'Late' : 'On Time');
                                                    ?>
                                                    <tr>
                                                        <td><?= $counter++ ?></td>
                                                        <td>
                                                            <strong><?= htmlspecialchars($studentName) ?></strong>
                                                        </td>
                                                        <td><?= htmlspecialchars($studentCourse) ?></td>
                                                        <td>
                                                            <div><?= date('h:i A', strtotime($timeIn)) ?></div>
                                                            <small class="text-muted"><?= date('M d, Y', strtotime($timeIn)) ?></small>
                                                        </td>
                                                        <td>
                                                            <span class="badge badge-<?= $statusClass ?> status-badge">
                                                                <?= $statusText ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <button class="btn btn-danger btn-sm" onclick="deleteAttendance(<?= $attendanceID ?>)" title="Delete">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                    
                                    <?php if (count($attendanceRecords) === 0): ?>
                                        <!-- Empty state outside the table to avoid DataTables column count issues -->
                                        <div class="empty-state w-100">
                                            <i class="fas fa-clipboard-list"></i>
                                            <h5>No attendance records for today</h5>
                                            <p class="text-muted">Start scanning QR codes to record attendance</p>
                                            <button class="btn btn-primary mt-2" onclick="startScanner()">
                                                <i class="fas fa-qrcode mr-2"></i>Start Scanner
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="row">
                                    <div class="col-sm-6">
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle mr-1"></i>
                                            Showing <?= count($attendanceRecords) ?> records
                                        </small>
                                    </div>
                                    <div class="col-sm-6 text-right">
                                        <small class="text-muted">
                                            Last updated: <?= date('h:i:s A') ?>
                                        </small>
                                    </div>
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
        <div class="float-right d-none d-sm-inline-block">
            <b>Version</b> 2.0.0
        </div>
    </footer>
</div>

<!-- Manual Entry Modal -->
<div class="modal fade" id="manualEntryModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white">
                    <i class="fas fa-user-plus mr-2"></i>Manual Attendance Entry
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="./endpoint/manual-attendance.php" method="POST" id="manualEntryForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="studentSelect">Select Student:</label>
                        <select class="form-control select2" id="studentSelect" name="student_id" style="width: 100%;" required>
                            <option value="">-- Select a student --</option>
                            <?php
                            if ($admin_role == 'super_admin') {
                                $stmt = $conn->prepare("
                                    SELECT tbl_student_id, student_name, course_section 
                                    FROM tbl_student 
                                    ORDER BY student_name
                                ");
                                $stmt->execute();
                            } else {
                                $stmt = $conn->prepare("
                                    SELECT DISTINCT s.tbl_student_id, s.student_name, s.course_section 
                                    FROM tbl_student s
                                    LEFT JOIN tbl_admin_sections ads ON s.course_section = ads.course_section
                                    WHERE s.created_by = ? 
                                    OR ads.user_id = ?
                                    ORDER BY s.student_name
                                ");
                                $stmt->execute([$admin_id, $admin_id]);
                            }
                            $students = $stmt->fetchAll();
                            
                            if (count($students) > 0):
                                foreach ($students as $student):
                            ?>
                            <option value="<?= $student['tbl_student_id'] ?>">
                                <?= htmlspecialchars($student['student_name']) ?> - <?= htmlspecialchars($student['course_section']) ?>
                            </option>
                            <?php 
                                endforeach;
                            else:
                            ?>
                            <option value="" disabled>No students found in your section</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="manualTime">Time In:</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">
                                    <i class="far fa-clock"></i>
                                </span>
                            </div>
                            <input type="datetime-local" class="form-control" id="manualTime" name="time_in" 
                                   value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                        </div>
                        <small class="form-text text-muted">Current time will be used if not specified</small>
                    </div>
                    <div class="form-group">
                        <label for="notes">Notes (Optional):</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2" 
                                  placeholder="Add any notes about this attendance..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-2"></i>Save Attendance
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- HTML5 QR Code Scanner Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>

<script>
    // Global variables
    let html5QrcodeScanner = null;
    let isScanning = false;
    const adminId = <?= $admin_id ?>;
    const adminRole = '<?= $admin_role ?>';
    
    // Initialize on document ready
    $(document).ready(function() {
        console.log('Document ready - initializing...');
        
        // Initialize Select2
        $('.select2').select2({
            theme: 'bootstrap4',
            placeholder: 'Select an option',
            allowClear: true
        });
        
        // Update time display
        updateTime();
        setInterval(updateTime, 1000);
        
        // Initialize DataTable ONLY if there are records
        <?php if (count($attendanceRecords) > 0): ?>
        try {
            $('#attendanceTable').DataTable({
                "paging": true,
                "lengthChange": false,
                "searching": true,
                "ordering": true,
                "info": true,
                "autoWidth": false,
                "responsive": true,
                "order": [[3, 'desc']],
                "columnDefs": [
                    { "orderable": false, "targets": 5 } // Make Actions column non-sortable
                ],
                "language": {
                    "emptyTable": "No attendance records available"
                }
            });
            console.log('DataTable initialized successfully');
        } catch (e) {
            console.error('DataTable initialization error:', e);
        }
        <?php else: ?>
        console.log('No records found - DataTable not initialized');
        // Add a class to style the empty table
        $('#attendanceTable').addClass('table-empty');
        <?php endif; ?>
        
        // Initialize UI state - scanner OFF by default
        $('#qr-reader').hide();
        $('#scannerPlaceholder').show();
        $('#startBtn').show();
        $('#stopBtn').hide();
        $('#qrDetectedSection').hide();
        $('#attendanceSuccessSection').hide();
        
        // Check if library is loaded
        if (typeof Html5QrcodeScanner === 'undefined') {
            console.error('Html5QrcodeScanner is not defined! Library failed to load.');
            showStatus('danger', 'QR Scanner library failed to load. Please refresh the page.');
        } else {
            console.log('Html5QrcodeScanner library loaded successfully!');
            showStatus('info', 'Scanner ready. Click "Start Scanner" to begin.');
        }
    });
    
    // Update time display
    function updateTime() {
        const now = new Date();
        const hours = now.getHours();
        const minutes = now.getMinutes().toString().padStart(2, '0');
        const ampm = hours >= 12 ? 'PM' : 'AM';
        const formattedHours = (hours % 12 || 12).toString().padStart(2, '0');
        
        $('#current-time').text(`${formattedHours}:${minutes} ${ampm}`);
    }
    
    // Start scanner
    function startScanner() {
        console.log('Start scanner clicked');
        
        if (typeof Html5QrcodeScanner === 'undefined') {
            showStatus('danger', 'QR Scanner library not loaded. Please refresh the page.');
            return;
        }
        
        if (isScanning) {
            showStatus('info', 'Scanner is already running');
            return;
        }
        
        $('#scannerPlaceholder').hide();
        $('#qr-reader').show();
        showStatus('info', 'Initializing camera...');
        
        try {
            if (html5QrcodeScanner) {
                try {
                    html5QrcodeScanner.clear();
                } catch (e) {
                    console.log('Error clearing existing scanner:', e);
                }
                html5QrcodeScanner = null;
            }
            
            html5QrcodeScanner = new Html5QrcodeScanner(
                "qr-reader", 
                { 
                    fps: 10, 
                    qrbox: { width: 250, height: 250 },
                    rememberLastUsedCamera: true,
                    showTorchButtonIfSupported: true,
                    aspectRatio: 1.0,
                    supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA]
                },
                false
            );
            
            html5QrcodeScanner.render(onScanSuccess, onScanError);
            isScanning = true;
            
            $('#startBtn').hide();
            $('#stopBtn').show();
            showStatus('success', 'Scanner active - Position QR code within frame');
            console.log('Scanner started successfully');
            
        } catch (error) {
            console.error('Scanner start error:', error);
            showStatus('danger', 'Failed to start scanner: ' + error.message);
            
            $('#qr-reader').hide();
            $('#scannerPlaceholder').show();
            $('#startBtn').show();
            $('#stopBtn').hide();
            isScanning = false;
        }
    }
    
    // Stop scanner
    function stopScanner() {
        console.log('Stop scanner clicked');
        
        if (html5QrcodeScanner) {
            try {
                html5QrcodeScanner.clear();
                html5QrcodeScanner = null;
                console.log('Scanner stopped successfully');
            } catch (error) {
                console.error('Error clearing scanner:', error);
            }
        }
        
        isScanning = false;
        
        $('#qr-reader').hide();
        $('#scannerPlaceholder').show();
        $('#startBtn').show();
        $('#stopBtn').hide();
        showStatus('info', 'Scanner stopped');
    }
    
    // Handle successful scan
    function onScanSuccess(decodedText, decodedResult) {
        console.log('QR Code scanned:', decodedText);
        
        if (html5QrcodeScanner) {
            try {
                html5QrcodeScanner.pause();
                console.log('Scanner paused');
            } catch (error) {
                console.error('Error pausing scanner:', error);
            }
        }
        
        playSuccessSound();
        showStatus('success', 'QR Code detected! Validating...');
        
        // Validate student
        $.ajax({
            url: './endpoint/validate-student.php',
            method: 'POST',
            data: { 
                qr_code: decodedText,
                admin_id: adminId
            },
            dataType: 'json',
            timeout: 10000,
            success: function(response) {
                console.log('Validation response:', response);
                
                if (response.valid) {
                    // Show QR detected section with student info
                    $('#detectedQrCode').val(decodedText);
                    $('#qrContent').text(decodedText);
                    $('#studentInfo').text(response.student_name + ' - ' + response.course_section);
                    
                    $('#scannerSection').hide();
                    $('#qrDetectedSection').show();
                    $('#attendanceSuccessSection').hide();
                    
                    stopScanner();
                    showStatus('success', 'Student found: ' + response.student_name);
                } else {
                    console.warn('Student validation failed:', response.message);
                    showStatus('warning', response.message || 'Student not found');
                    showToast('error', 'Invalid QR', response.message || 'Student not found');
                    
                    $('#qr-reader').addClass('invalid-qr');
                    setTimeout(() => $('#qr-reader').removeClass('invalid-qr'), 500);
                    
                    setTimeout(() => {
                        resumeScanner();
                    }, 2000);
                }
            },
            error: function(xhr, status, error) {
                console.error('Validation AJAX error:', status, error);
                showStatus('danger', 'Error validating student. Please try again.');
                showToast('error', 'Error', 'Network error. Please try again.');
                
                setTimeout(() => {
                    resumeScanner();
                }, 2000);
            }
        });
    }
    
    function onScanError(errorMessage) {
        // Ignore scan errors
    }
    
    // Handle attendance form submission
    $('#attendanceForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        
        $.ajax({
            url: './endpoint/add-attendance.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                console.log('Attendance response:', response);
                
                if (response.success) {
                    // Show success section
                    $('#qrDetectedSection').hide();
                    $('#attendanceSuccessSection').show();
                    $('#successStudentInfo').text(response.student_name);
                    $('#successTime').text('Time: ' + response.time);
                    $('#successStatus').text(response.status).removeClass('badge-success badge-warning').addClass(
                        response.status === 'On Time' ? 'badge-success' : 'badge-warning'
                    );
                    
                    showToast('success', 'Success!', response.message);
                    
                    // Refresh the table
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    showToast('error', 'Error', response.message);
                    
                    if (response.message.includes('already attended')) {
                        setTimeout(() => {
                            resumeScanner();
                        }, 2000);
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Attendance submission error:', error);
                showToast('error', 'Error', 'Network error. Please try again.');
                
                setTimeout(() => {
                    resumeScanner();
                }, 2000);
            }
        });
    });
    
    // Resume scanning
    function resumeScanner() {
        $('#qrDetectedSection').hide();
        $('#attendanceSuccessSection').hide();
        $('#scannerSection').show();
        $('#detectedQrCode').val('');
        $('#qrContent').text('');
        $('#studentInfo').text('Student QR code successfully scanned');
        
        if (html5QrcodeScanner) {
            try {
                html5QrcodeScanner.resume();
                console.log('Scanner resumed');
            } catch (error) {
                console.error('Error resuming scanner:', error);
                startScanner();
            }
        } else {
            startScanner();
        }
    }
    
    // Delete attendance
    function deleteAttendance(id) {
        if (confirm('Are you sure you want to delete this attendance record?')) {
            $.ajax({
                url: './endpoint/delete-attendance.php',
                method: 'GET',
                data: { attendance: id },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showToast('success', 'Success', 'Attendance record deleted successfully.');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast('error', 'Error', response.message || 'Error deleting record');
                    }
                },
                error: function() {
                    showToast('error', 'Error', 'Network error. Please try again.');
                }
            });
        }
    }
    
    // Refresh table
    function refreshTable() {
        showToast('info', 'Refreshing', 'Updating attendance records...');
        location.reload();
    }
    
    // Show status message
    function showStatus(type, message) {
        const statusDiv = $('#scannerStatus');
        const messageSpan = $('#statusMessage');
        
        statusDiv.removeClass('alert-info alert-success alert-warning alert-danger');
        statusDiv.addClass(`alert-${type}`);
        
        let icon = 'info-circle';
        if (type === 'success') icon = 'check-circle';
        if (type === 'warning') icon = 'exclamation-triangle';
        if (type === 'danger') icon = 'times-circle';
        
        messageSpan.html(`<i class="fas fa-${icon} mr-2"></i>${message}`);
        statusDiv.show();
    }
    
    // Play success sound
    function playSuccessSound() {
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.frequency.value = 800;
            oscillator.type = 'sine';
            
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.2);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.2);
        } catch (e) {
            console.log('Audio context not supported');
        }
    }
    
    // Show toast notification
    function showToast(type, title, message) {
        $('.custom-toast').remove();
        
        const bgColor = type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : 'bg-info';
        
        const toastHtml = `
            <div class="custom-toast toast fade show" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                <div class="toast-header ${bgColor} text-white">
                    <strong class="mr-auto">${title}</strong>
                    <button type="button" class="ml-2 mb-1 close text-white" onclick="$(this).closest('.toast').remove()">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            </div>
        `;
        
        $('body').append(toastHtml);
        
        setTimeout(() => {
            $('.custom-toast').remove();
        }, 3000);
    }
    
    // Handle manual entry form submission
    $('#manualEntryForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        
        $.ajax({
            url: './endpoint/manual-attendance.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showToast('success', 'Success', response.message || 'Attendance recorded successfully');
                    $('#manualEntryModal').modal('hide');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast('error', 'Error', response.message || 'Error recording attendance');
                }
            },
            error: function() {
                showToast('error', 'Error', 'Network error. Please try again.');
            }
        });
    });
    
    // Clean up on page unload
    $(window).on('beforeunload', function() {
        if (html5QrcodeScanner) {
            try {
                html5QrcodeScanner.clear();
            } catch (error) {
                console.error('Error during cleanup:', error);
            }
        }
    });
</script>
</body>
</html>
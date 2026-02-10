<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

date_default_timezone_set('Asia/Manila');
include ('./conn/conn.php');
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
        
        .scanner-video {
            width: 100%;
            height: 350px;
            background: #000;
            border-radius: 10px;
            border: 3px solid #007bff;
            object-fit: cover;
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
        
        .stat-card {
            border-radius: 10px;
            color: white;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        
        .scanner-status {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
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
            padding: 40px 20px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
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
        
        <!-- Right navbar links -->
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
                        <div class="scanner-status" id="scannerStatus">
                            <i class="fas fa-info-circle mr-2"></i>
                            <span id="statusMessage">Scanner ready</span>
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
                                    
                                    <div class="text-center mb-3">
                                        <video id="scannerVideo" class="scanner-video" playsinline></video>
                                    </div>
                                    
                                    <!-- Camera Selection -->
                                    <div class="form-group mb-3" id="cameraSelectGroup" style="display: none;">
                                        <label for="cameraSelect">
                                            <i class="fas fa-video mr-2"></i>Select Camera
                                        </label>
                                        <select class="form-control select2" id="cameraSelect" onchange="changeCamera()">
                                            <option value="">Loading cameras...</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Camera Controls -->
                                    <div class="camera-controls">
                                        <button class="btn btn-success" onclick="startScanner()" id="startBtn">
                                            <i class="fas fa-play mr-2"></i>Start Scanner
                                        </button>
                                        <button class="btn btn-danger" onclick="stopScanner()" id="stopBtn" style="display: none;">
                                            <i class="fas fa-stop mr-2"></i>Stop Scanner
                                        </button>
                                        <button class="btn btn-info" onclick="switchCamera()" id="switchBtn" style="display: none;">
                                            <i class="fas fa-sync-alt mr-2"></i>Switch Camera
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- QR Detected Section -->
                                <div id="qrDetectedSection" style="display: none;">
                                    <div class="qr-detected-box text-center">
                                        <i class="fas fa-check-circle fa-3x mb-3"></i>
                                        <h4>QR Code Detected!</h4>
                                        <p class="mb-0">Student QR code successfully scanned</p>
                                        
                                        <div class="qr-content-box">
                                            <small>Student ID:</small>
                                            <div id="qrContent" class="font-weight-bold"></div>
                                        </div>
                                        
                                        <form action="./endpoint/add-attendance.php" method="POST">
                                            <input type="hidden" id="detectedQrCode" name="qr_code">
                                            <div class="mt-4">
                                                <button type="submit" class="btn btn-light btn-lg mr-2">
                                                    <i class="fas fa-check mr-2"></i>Confirm
                                                </button>
                                                <button type="button" class="btn btn-outline-light" onclick="resumeScanner()">
                                                    <i class="fas fa-redo mr-2"></i>Scan Again
                                                </button>
                                            </div>
                                        </form>
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
                                            $stmt = $conn->prepare("SELECT COUNT(*) FROM tbl_attendance WHERE DATE(time_in) = CURDATE()");
                                            $stmt->execute();
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
                                            $stmt = $conn->prepare("
                                                SELECT COUNT(*) 
                                                FROM tbl_attendance 
                                                WHERE DATE(time_in) = CURDATE() 
                                                AND TIME(time_in) <= '08:00:00'
                                            ");
                                            $stmt->execute();
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
                                            $stmt = $conn->prepare("
                                                SELECT COUNT(*) 
                                                FROM tbl_attendance 
                                                WHERE DATE(time_in) = CURDATE() 
                                                AND TIME(time_in) > '08:00:00'
                                            ");
                                            $stmt->execute();
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
                                            <?php
                                            $stmt = $conn->prepare("
                                                SELECT a.*, s.student_name, s.course_section 
                                                FROM tbl_attendance a 
                                                LEFT JOIN tbl_student s ON s.tbl_student_id = a.tbl_student_id 
                                                WHERE DATE(a.time_in) = CURDATE()
                                                ORDER BY a.time_in DESC
                                            ");
                                            $stmt->execute();
                                            $attendanceRecords = $stmt->fetchAll();
                                            $counter = 1;
                                            
                                            if (count($attendanceRecords) > 0):
                                                foreach ($attendanceRecords as $record):
                                                    $attendanceID = $record["tbl_attendance_id"];
                                                    $studentName = $record["student_name"];
                                                    $studentCourse = $record["course_section"];
                                                    $timeIn = $record["time_in"];
                                                    
                                                    // Determine status
                                                    $checkTime = new DateTime($timeIn);
                                                    $lateTime = new DateTime($checkTime->format('Y-m-d') . ' 08:00:00');
                                                    $isLate = $checkTime > $lateTime;
                                                    $statusClass = $isLate ? 'warning' : 'success';
                                                    $statusText = $isLate ? 'Late' : 'On Time';
                                            ?>
                                            <tr>
                                                <td><?= $counter++; ?></td>
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
                                            <?php else: ?>
                                            <tr>
                                                <td colspan="6">
                                                    <div class="empty-state">
                                                        <i class="fas fa-clipboard-list"></i>
                                                        <h5>No attendance records for today</h5>
                                                        <p class="text-muted">Start scanning QR codes to record attendance</p>
                                                        <button class="btn btn-primary mt-2" onclick="startScanner()">
                                                            <i class="fas fa-qrcode mr-2"></i>Start Scanner
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
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
                            $stmt = $conn->prepare("SELECT tbl_student_id, student_name, course_section FROM tbl_student ORDER BY student_name");
                            $stmt->execute();
                            $students = $stmt->fetchAll();
                            foreach ($students as $student):
                            ?>
                            <option value="<?= $student['tbl_student_id'] ?>">
                                <?= htmlspecialchars($student['student_name']) ?> - <?= htmlspecialchars($student['course_section']) ?>
                            </option>
                            <?php endforeach; ?>
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
<script src="https://rawgit.com/schmich/instascan-builds/master/instascan.min.js"></script>

<script>
    // Global variables
    let scanner = null;
    let isScanning = false;
    let cameras = [];
    let currentCameraIndex = 0;
    
    // Initialize Select2
    $(document).ready(function() {
        $('.select2').select2({
            theme: 'bootstrap4',
            placeholder: 'Select an option',
            allowClear: true
        });
        
        // Initialize time display
        updateTime();
        setInterval(updateTime, 1000);
        
        // Initialize scanner
        initScanner();
        
        // Initialize DataTable
        $('#attendanceTable').DataTable({
            "paging": true,
            "lengthChange": false,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "order": [[3, 'desc']]
        });
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
    
    // Initialize scanner
    function initScanner() {
        showStatus('info', 'Initializing scanner...');
        
        Instascan.Camera.getCameras()
            .then(function(availableCameras) {
                cameras = availableCameras;
                
                if (cameras.length > 0) {
                    // Populate camera select
                    const cameraSelect = $('#cameraSelect');
                    cameraSelect.empty();
                    
                    cameras.forEach((camera, index) => {
                        cameraSelect.append(
                            $('<option></option>').val(index).text(camera.name || `Camera ${index + 1}`)
                        );
                    });
                    
                    $('#cameraSelectGroup').show();
                    cameraSelect.select2({
                        theme: 'bootstrap4',
                        placeholder: 'Select camera',
                        minimumResultsForSearch: -1
                    });
                    
                    // Auto-select back camera if available
                    const backCameraIndex = cameras.findIndex(camera => 
                        camera.name.toLowerCase().includes('back'));
                    if (backCameraIndex !== -1) {
                        currentCameraIndex = backCameraIndex;
                        cameraSelect.val(backCameraIndex).trigger('change');
                    }
                    
                    showStatus('success', 'Scanner ready. Click "Start Scanner" to begin.');
                } else {
                    showStatus('danger', 'No cameras found. Please connect a camera device.');
                }
            })
            .catch(function(error) {
                console.error('Camera initialization error:', error);
                showStatus('danger', `Camera error: ${error.message}`);
            });
    }
    
    // Start scanner
    function startScanner() {
        if (isScanning || cameras.length === 0) return;
        
        if (scanner) {
            scanner.stop();
            scanner = null;
        }
        
        scanner = new Instascan.Scanner({ 
            video: document.getElementById('scannerVideo'),
            mirror: false,
            captureImage: false,
            backgroundScan: true,
            refractoryPeriod: 5000,
            scanPeriod: 1
        });
        
        scanner.addListener('scan', function(content) {
            console.log('QR Code scanned:', content);
            handleScannedQR(content);
        });
        
        scanner.start(cameras[currentCameraIndex])
            .then(() => {
                isScanning = true;
                $('#startBtn').hide();
                $('#stopBtn').show();
                $('#switchBtn').show();
                showStatus('success', 'Scanner active - Ready to scan QR codes');
            })
            .catch(error => {
                console.error('Scanner start error:', error);
                showStatus('danger', `Failed to start scanner: ${error.message}`);
            });
    }
    
    // Stop scanner
    function stopScanner() {
        if (scanner && isScanning) {
            scanner.stop();
            isScanning = false;
            $('#startBtn').show();
            $('#stopBtn').hide();
            showStatus('info', 'Scanner stopped');
        }
    }
    
    // Switch camera
    function switchCamera() {
        if (cameras.length < 2) {
            showStatus('warning', 'Only one camera available');
            return;
        }
        
        currentCameraIndex = (currentCameraIndex + 1) % cameras.length;
        $('#cameraSelect').val(currentCameraIndex).trigger('change');
        
        if (isScanning) {
            scanner.stop().then(() => {
                scanner.start(cameras[currentCameraIndex]);
            });
        }
    }
    
    // Change camera from dropdown
    function changeCamera() {
        const selectedIndex = parseInt($('#cameraSelect').val());
        if (!isNaN(selectedIndex) && selectedIndex >= 0 && selectedIndex < cameras.length) {
            currentCameraIndex = selectedIndex;
            
            if (isScanning) {
                scanner.stop().then(() => {
                    scanner.start(cameras[currentCameraIndex]);
                });
            }
        }
    }
    
    // Handle scanned QR code
    function handleScannedQR(content) {
        if (!content || content.trim() === '') {
            showStatus('warning', 'Invalid QR code content');
            return;
        }
        
        // Basic validation (adjust according to your QR format)
        if (!/^[A-Za-z0-9\-_]+$/.test(content)) {
            showStatus('warning', 'Invalid QR code format');
            return;
        }
        
        // Show success UI
        $('#detectedQrCode').val(content);
        $('#qrContent').text(content);
        
        // Play success sound
        playSuccessSound();
        
        // Show QR detected section
        $('#scannerSection').hide();
        $('#qrDetectedSection').show();
        
        // Show notification
        showToast('success', 'QR Code Detected!', 'Student QR code successfully scanned.');
        
        // Stop scanner
        stopScanner();
    }
    
    // Resume scanning
    function resumeScanner() {
        $('#qrDetectedSection').hide();
        $('#scannerSection').show();
        $('#detectedQrCode').val('');
        $('#qrContent').text('');
        startScanner();
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
        
        // Update classes based on type
        statusDiv.removeClass('alert-info alert-success alert-warning alert-danger');
        
        switch(type) {
            case 'success':
                statusDiv.addClass('alert-success');
                statusDiv.css('background', '#d4edda');
                statusDiv.css('border-color', '#c3e6cb');
                break;
            case 'warning':
                statusDiv.addClass('alert-warning');
                statusDiv.css('background', '#fff3cd');
                statusDiv.css('border-color', '#ffeaa7');
                break;
            case 'danger':
                statusDiv.addClass('alert-danger');
                statusDiv.css('background', '#f8d7da');
                statusDiv.css('border-color', '#f5c6cb');
                break;
            default:
                statusDiv.addClass('alert-info');
                statusDiv.css('background', '#d1ecf1');
                statusDiv.css('border-color', '#bee5eb');
        }
        
        messageSpan.html(`<i class="fas fa-${getStatusIcon(type)} mr-2"></i>${message}`);
        statusDiv.show();
    }
    
    // Get icon based on status type
    function getStatusIcon(type) {
        switch(type) {
            case 'success': return 'check-circle';
            case 'warning': return 'exclamation-triangle';
            case 'danger': return 'times-circle';
            default: return 'info-circle';
        }
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
        // Remove any existing toast
        $('.custom-toast').remove();
        
        // Create toast HTML
        const toastHtml = `
            <div class="custom-toast toast fade show" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                <div class="toast-header bg-${type} text-white">
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
        
        // Append to body
        $('body').append(toastHtml);
        
        // Auto-remove after 3 seconds
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
    
    // Auto-refresh every 60 seconds if not scanning
    setInterval(() => {
        if (!isScanning && document.visibilityState === 'visible') {
            const table = $('#attendanceTable').DataTable();
            table.ajax.reload(null, false); // false means don't reset user paging/search
        }
    }, 60000);
    
    // Clean up on page unload
    $(window).on('beforeunload', function() {
        if (scanner && isScanning) {
            scanner.stop();
        }
    });
    
    // Handle page visibility change
    document.addEventListener('visibilitychange', function() {
        if (document.hidden && scanner && isScanning) {
            scanner.stop();
            isScanning = false;
            $('#startBtn').show();
            $('#stopBtn').hide();
        }
    });
</script>
</body>
</html>
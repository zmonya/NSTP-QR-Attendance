<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

date_default_timezone_set('Asia/Manila');
include ('./conn/conn.php');

$today = date('Y-m-d');
$currentUserID = $_SESSION['user_id'];
$currentUserRole = $_SESSION['role'] ?? 'admin';

// Get statistics with role-based filtering
if ($currentUserRole === 'super_admin') {
    // Super Admin: See all records
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tbl_student");
    $stmt->execute();
    $totalStudents = $stmt->fetchColumn();

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tbl_attendance WHERE DATE(time_in) = :today");
    $stmt->execute(['today' => $today]);
    $todayAttendance = $stmt->fetchColumn();

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tbl_attendance");
    $stmt->execute();
    $totalAttendance = $stmt->fetchColumn();

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tbl_attendance_archive");
    $stmt->execute();
    $totalArchived = $stmt->fetchColumn();

    // Recent attendance - all records
    $stmt = $conn->prepare("
        SELECT a.*, s.student_name, s.course_section, u.full_name as recorded_by 
        FROM tbl_attendance a 
        LEFT JOIN tbl_student s ON s.tbl_student_id = a.tbl_student_id 
        LEFT JOIN tbl_users u ON u.user_id = s.created_by 
        ORDER BY a.time_in DESC 
        LIMIT 8
    ");
    $stmt->execute();
    $recent = $stmt->fetchAll();
} else {
    // Regular Admin: See only records they created
    
    // Count students created by this admin
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT s.tbl_student_id) as total 
        FROM tbl_student s
        WHERE s.created_by = :user_id
    ");
    $stmt->execute(['user_id' => $currentUserID]);
    $totalStudents = $stmt->fetchColumn();

    // Today's attendance for students created by this admin
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM tbl_attendance a
        INNER JOIN tbl_student s ON s.tbl_student_id = a.tbl_student_id
        WHERE DATE(a.time_in) = :today 
        AND s.created_by = :user_id
    ");
    $stmt->execute(['today' => $today, 'user_id' => $currentUserID]);
    $todayAttendance = $stmt->fetchColumn();

    // All attendance for students created by this admin
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM tbl_attendance a
        INNER JOIN tbl_student s ON s.tbl_student_id = a.tbl_student_id
        WHERE s.created_by = :user_id
    ");
    $stmt->execute(['user_id' => $currentUserID]);
    $totalAttendance = $stmt->fetchColumn();

    // Archived records for students created by this admin
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM tbl_attendance_archive a
        INNER JOIN tbl_student s ON s.tbl_student_id = a.tbl_student_id
        WHERE s.created_by = :user_id
    ");
    $stmt->execute(['user_id' => $currentUserID]);
    $totalArchived = $stmt->fetchColumn();

    // Recent attendance - only for students created by this admin
    $stmt = $conn->prepare("
        SELECT a.*, s.student_name, s.course_section 
        FROM tbl_attendance a 
        INNER JOIN tbl_student s ON s.tbl_student_id = a.tbl_student_id 
        WHERE s.created_by = :user_id 
        ORDER BY a.time_in DESC 
        LIMIT 8
    ");
    $stmt->execute(['user_id' => $currentUserID]);
    $recent = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - QR Attendance</title>
    
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    
    <style>
        .small-box { border-radius: 10px; }
        .manila-time-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
        }
        .manila-time {
            font-family: 'Courier New', monospace;
            font-size: 1.8rem;
            font-weight: bold;
        }
        .recent-table {
            max-height: 400px;
            overflow-y: auto;
        }
        .role-badge {
            position: absolute;
            top: 10px;
            right: 10px;
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
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <span class="nav-link">
                    <i class="fas fa-user-tag mr-1"></i>
                    <?php 
                    if ($currentUserRole === 'super_admin') {
                        echo '<span class="badge badge-danger">Super Admin</span>';
                    } else {
                        echo '<span class="badge badge-primary">Admin</span>';
                    }
                    ?>
                </span>
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
                        <h1 class="m-0">Dashboard</h1>
                        <?php if ($currentUserRole === 'super_admin'): ?>
                            <small class="text-danger"><i class="fas fa-shield-alt mr-1"></i> Super Admin View - Showing all records</small>
                        <?php else: ?>
                            <small class="text-primary"><i class="fas fa-user mr-1"></i> Admin View - Showing records for your students only</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <section class="content">
            <div class="container-fluid">
                <!-- Welcome Row -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="callout callout-info">
                            <h5><i class="fas fa-user mr-2"></i> Welcome back, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?>!</h5>
                            <p>
                                <?php if ($currentUserRole === 'super_admin'): ?>
                                    You have <strong>full access</strong> to all system records and functions.
                                <?php else: ?>
                                    You can view and manage <strong>attendance for students you created</strong>. Contact Super Admin for full access.
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Stats Row -->
                <div class="row">
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h3><?php echo $totalStudents; ?></h3>
                                <p>
                                    <?php if ($currentUserRole === 'super_admin'): ?>
                                        Total Students
                                    <?php else: ?>
                                        My Students
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <?php if ($currentUserRole === 'super_admin'): ?>
                                <a href="masterlist.php" class="small-box-footer">View All Students <i class="fas fa-arrow-circle-right"></i></a>
                            <?php else: ?>
                                <a href="masterlist.php?filter=my_students" class="small-box-footer">View My Students <i class="fas fa-arrow-circle-right"></i></a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3><?php echo $todayAttendance; ?></h3>
                                <p>
                                    <?php if ($currentUserRole === 'super_admin'): ?>
                                        Today's Attendance
                                    <?php else: ?>
                                        My Today's Attendance
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <a href="attendance.php" class="small-box-footer">View Scanner <i class="fas fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <h3><?php echo $totalAttendance; ?></h3>
                                <p>
                                    <?php if ($currentUserRole === 'super_admin'): ?>
                                        Active Records
                                    <?php else: ?>
                                        My Active Records
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                            <?php if ($currentUserRole === 'super_admin'): ?>
                                <a href="attendance-list.php" class="small-box-footer">View All Records <i class="fas fa-arrow-circle-right"></i></a>
                            <?php else: ?>
                                <a href="attendance-list.php?filter=my_records" class="small-box-footer">View My Records <i class="fas fa-arrow-circle-right"></i></a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-gradient-secondary">
                            <div class="inner">
                                <h3><?php echo $totalArchived; ?></h3>
                                <p>
                                    <?php if ($currentUserRole === 'super_admin'): ?>
                                        Archived Records
                                    <?php else: ?>
                                        My Archived Records
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-archive"></i>
                            </div>
                            <?php if ($currentUserRole === 'super_admin'): ?>
                                <a href="archive-manager.php" class="small-box-footer">Manage All Archive <i class="fas fa-arrow-circle-right"></i></a>
                            <?php else: ?>
                                <a href="archive-manager.php?filter=my_records" class="small-box-footer">Manage My Archive <i class="fas fa-arrow-circle-right"></i></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity & Quick Actions -->
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-history mr-2"></i> Recent Attendance Activity</h3>
                                <?php if ($currentUserRole === 'super_admin'): ?>
                                    <span class="badge badge-danger float-right">All Admins</span>
                                <?php else: ?>
                                    <span class="badge badge-primary float-right">My Students Only</span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive recent-table">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Student</th>
                                                <th>Course</th>
                                                <th>Time In</th>
                                                <?php if ($currentUserRole === 'super_admin'): ?>
                                                    <th>Created By</th>
                                                <?php endif; ?>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($recent) > 0): ?>
                                                <?php foreach ($recent as $record): ?>
                                                    <?php
                                                    $timeIn = new DateTime($record['time_in'], new DateTimeZone('Asia/Manila'));
                                                    $now = new DateTime('now', new DateTimeZone('Asia/Manila'));
                                                    $diff = $now->diff($timeIn);
                                                    
                                                    if ($diff->h > 0) {
                                                        $timeAgo = $diff->h . ' hours ago';
                                                    } elseif ($diff->i > 0) {
                                                        $timeAgo = $diff->i . ' minutes ago';
                                                    } else {
                                                        $timeAgo = 'Just now';
                                                    }
                                                    
                                                    $checkTime = new DateTime($record['time_in'], new DateTimeZone('Asia/Manila'));
                                                    $lateTime = new DateTime($checkTime->format('Y-m-d') . ' 08:00:00', new DateTimeZone('Asia/Manila'));
                                                    $isLate = $checkTime > $lateTime;
                                                    ?>
                                                    <tr>
                                                        <td><strong><?php echo htmlspecialchars($record['student_name']); ?></strong></td>
                                                        <td><?php echo htmlspecialchars($record['course_section']); ?></td>
                                                        <td>
                                                            <div><?php echo $timeIn->format('h:i A'); ?></div>
                                                            <small class="text-muted"><?php echo $timeAgo; ?></small>
                                                        </td>
                                                        <?php if ($currentUserRole === 'super_admin'): ?>
                                                            <td>
                                                                <?php if (isset($record['recorded_by']) && !empty($record['recorded_by'])): ?>
                                                                    <span class="badge badge-info"><?php echo htmlspecialchars($record['recorded_by']); ?></span>
                                                                <?php else: ?>
                                                                    <span class="badge badge-secondary">System</span>
                                                                <?php endif; ?>
                                                            </td>
                                                        <?php endif; ?>
                                                        <td>
                                                            <?php if ($isLate): ?>
                                                                <span class="badge badge-warning p-2">Late</span>
                                                            <?php else: ?>
                                                                <span class="badge badge-success p-2">On Time</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="<?php echo ($currentUserRole === 'super_admin') ? 5 : 4; ?>" class="text-center text-muted py-4">
                                                        <i class="fas fa-clipboard-list fa-2x mb-3 d-block"></i>
                                                        No recent attendance records
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-bolt mr-2"></i> Quick Actions</h3>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <a href="attendance.php" class="btn btn-app btn-block bg-gradient-primary">
                                            <i class="fas fa-qrcode fa-2x"></i> 
                                            <span>Scan<br>Attendance</span>
                                        </a>
                                    </div>
                                    
                                    <div class="col-12 mb-3">
                                        <?php if ($currentUserRole === 'super_admin'): ?>
                                            <a href="masterlist.php" class="btn btn-app btn-block bg-gradient-info">
                                                <i class="fas fa-user-graduate fa-2x"></i> 
                                                <span>All<br>Students</span>
                                            </a>
                                        <?php else: ?>
                                            <a href="masterlist.php?filter=my_students" class="btn btn-app btn-block bg-gradient-info">
                                                <i class="fas fa-user-graduate fa-2x"></i> 
                                                <span>My<br>Students</span>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="col-12 mb-3">
                                        <?php if ($currentUserRole === 'super_admin'): ?>
                                            <a href="archive-manager.php" class="btn btn-app btn-block bg-gradient-warning">
                                                <i class="fas fa-archive fa-2x"></i> 
                                                <span>All<br>Archive</span>
                                            </a>
                                        <?php else: ?>
                                            <a href="archive-manager.php?filter=my_records" class="btn btn-app btn-block bg-gradient-warning">
                                                <i class="fas fa-archive fa-2x"></i> 
                                                <span>My<br>Archive</span>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="col-12">
                                        <?php if ($currentUserRole === 'super_admin'): ?>
                                            <a href="./endpoint/download-attendance-excel.php" class="btn btn-app btn-block bg-gradient-success">
                                                <i class="fas fa-file-excel fa-2x"></i> 
                                                <span>Export All<br>Data</span>
                                            </a>
                                        <?php else: ?>
                                            <a href="./endpoint/download-attendance-excel.php?filter=my_records" class="btn btn-app btn-block bg-gradient-success">
                                                <i class="fas fa-file-excel fa-2x"></i> 
                                                <span>Export My<br>Data</span>
                                            </a>
                                        <?php endif; ?>
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
    </footer>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

<script>
function updateManilaTime() {
    const now = new Date();
    const manilaOffset = 8 * 60;
    const localOffset = now.getTimezoneOffset();
    const manilaTime = new Date(now.getTime() + (manilaOffset + localOffset) * 60000);
    
    const hours = manilaTime.getHours();
    const minutes = manilaTime.getMinutes().toString().padStart(2, '0');
    const seconds = manilaTime.getSeconds().toString().padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    const formattedHours = (hours % 12 || 12).toString().padStart(2, '0');
    
    document.getElementById('manila-clock-time').textContent = 
        `${formattedHours}:${minutes}:${seconds} ${ampm}`;
}

updateManilaTime();
setInterval(updateManilaTime, 1000);

setTimeout(function() {
    location.reload();
}, 60000);
</script>
</body>
</html>
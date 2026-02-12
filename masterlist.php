<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include('./conn/conn.php');

// Get user info
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'admin';
$user_name = $_SESSION['full_name'] ?? 'User';

// Get admin's assigned section(s)
$stmt = $conn->prepare("
    SELECT a.course_section 
    FROM tbl_admin_sections a 
    WHERE a.user_id = ? 
    ORDER BY a.assigned_at ASC 
    LIMIT 1
");
$stmt->execute([$user_id]);
$assignedSection = $stmt->fetchColumn();

// If no assigned section, check assigned_section field in tbl_users
if (!$assignedSection) {
    $stmt = $conn->prepare("SELECT assigned_section FROM tbl_users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $assignedSection = $stmt->fetchColumn();
}

// Prepare query based on role
if ($user_role === 'super_admin') {
    // Get all admins with their student counts
    $admins_stmt = $conn->prepare("
        SELECT 
            u.user_id,
            u.full_name,
            u.username,
            COUNT(s.tbl_student_id) as student_count
        FROM tbl_users u
        LEFT JOIN tbl_student s ON u.user_id = s.created_by
        WHERE u.role = 'admin'
        GROUP BY u.user_id, u.full_name, u.username
        ORDER BY u.full_name ASC
    ");
    $admins_stmt->execute();
    $admins = $admins_stmt->fetchAll();
    
    // Get students without admin (system added)
    $system_stmt = $conn->prepare("
        SELECT s.*, NULL as admin_name, NULL as admin_username 
        FROM tbl_student s 
        WHERE s.created_by IS NULL 
        ORDER BY s.tbl_student_id DESC
    ");
    $system_stmt->execute();
    $system_students = $system_stmt->fetchAll();
    
    // Also get current user's students separately if they're super admin (they can also add students)
    $my_stmt = $conn->prepare("
        SELECT s.*, u.full_name as admin_name, u.username as admin_username 
        FROM tbl_student s 
        LEFT JOIN tbl_users u ON s.created_by = u.user_id 
        WHERE s.created_by = ?
        ORDER BY s.tbl_student_id DESC
    ");
    $my_stmt->execute([$user_id]);
    $my_students = $my_stmt->fetchAll();
    
} else {
    // Regular admin only sees their own students
    $stmt = $conn->prepare("
        SELECT s.* 
        FROM tbl_student s 
        WHERE s.created_by = ? 
        ORDER BY s.tbl_student_id DESC
    ");
    $stmt->execute([$user_id]);
    $result = $stmt->fetchAll();
}

// Get total counts for stats
if ($user_role === 'super_admin') {
    $total_stmt = $conn->prepare("SELECT COUNT(*) FROM tbl_student");
    $total_stmt->execute();
    $total_students = $total_stmt->fetchColumn();
    
    $my_students_count = $conn->prepare("SELECT COUNT(*) FROM tbl_student WHERE created_by = ?");
    $my_students_count->execute([$user_id]);
    $my_students_count = $my_students_count->fetchColumn();
    
    $total_admins_stmt = $conn->prepare("SELECT COUNT(*) FROM tbl_users WHERE role = 'admin'");
    $total_admins_stmt->execute();
    $total_admins = $total_admins_stmt->fetchColumn();
} else {
    $total_stmt = $conn->prepare("SELECT COUNT(*) FROM tbl_student WHERE created_by = ?");
    $total_stmt->execute([$user_id]);
    $total_students = $total_stmt->fetchColumn();
    $my_students_count = $total_students;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Masterlist - QR Attendance</title>
    
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        .student-table {
            max-height: 600px;
            overflow-y: auto;
        }
        
        .action-buttons .btn {
            margin: 2px;
        }
        
        .qr-modal-img {
            max-width: 300px;
            margin: 0 auto;
            display: block;
        }
        
        .user-badge {
            font-size: 0.85rem;
            padding: 5px 10px;
        }
        
        .permission-badge {
            font-size: 0.75rem;
            padding: 3px 8px;
        }
        
        .section-info {
            font-size: 0.9rem;
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 10px 15px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        
        .section-badge-large {
            font-size: 1rem;
            padding: 8px 15px;
            margin: 0 5px;
        }
        
        .btn-spinner {
            position: relative;
            padding-left: 40px !important;
        }
        .btn-spinner .fa-spinner {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
        }
        
        /* Admin Folder Styles */
        .admin-folder {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 15px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .admin-folder:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .admin-folder-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s ease;
        }
        
        .admin-folder-header.collapsed {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
        }
        
        .admin-folder-header:hover {
            opacity: 0.95;
        }
        
        .admin-folder-header i {
            margin-right: 10px;
        }
        
        .folder-icon {
            font-size: 1.2rem;
            margin-right: 15px;
            color: #ffd700;
        }
        
        .admin-info {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .admin-name {
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .admin-username {
            font-size: 0.9rem;
            opacity: 0.9;
            background: rgba(255,255,255,0.2);
            padding: 3px 10px;
            border-radius: 20px;
        }
        
        .admin-stats {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .stat-badge {
            background: rgba(255,255,255,0.2);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .stat-badge i {
            font-size: 0.9rem;
        }
        
        .expand-collapse-icon {
            font-size: 1.2rem;
            transition: transform 0.3s ease;
        }
        
        .admin-folder-body {
            background: white;
            transition: all 0.3s ease;
        }
        
        .admin-folder-body.collapsed {
            display: none;
        }
        
        .folder-table {
            margin: 0;
        }
        
        .folder-table thead {
            background: #f8f9fa;
        }
        
        .folder-table thead th {
            border-top: none;
            font-weight: 600;
            color: #495057;
            font-size: 0.9rem;
        }
        
        .empty-folder {
            padding: 40px;
            text-align: center;
            color: #6c757d;
        }
        
        .empty-folder i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.3;
        }
        
        .my-folder .admin-folder-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }
        
        .system-folder .admin-folder-header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }
        
        .search-box {
            margin-bottom: 20px;
        }
        
        .search-box input {
            border-radius: 20px;
            padding: 10px 20px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .search-box input:focus {
            border-color: #667eea;
            box-shadow: none;
        }
        
        .admin-filter {
            margin-bottom: 20px;
        }
        
        .admin-filter .btn {
            border-radius: 20px;
            margin-right: 5px;
            margin-bottom: 5px;
        }
        
        .admin-filter .btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        @media (max-width: 768px) {
            .admin-folder-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .admin-info {
                margin-bottom: 10px;
            }
            
            .admin-stats {
                width: 100%;
                justify-content: flex-start;
            }
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
                        <h1 class="m-0">Student Masterlist</h1>
                        <small>
                            Logged in as: 
                            <span class="badge badge-<?php echo ($user_role === 'super_admin') ? 'danger' : 'primary'; ?> user-badge">
                                <?php echo htmlspecialchars($user_name); ?> (<?php echo $user_role; ?>)
                            </span>
                        </small>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <li class="breadcrumb-item active">Students</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <section class="content">
            <div class="container-fluid">
                <!-- Quick Stats -->
                <div class="row mb-3">
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h3><?php echo $total_students; ?></h3>
                                <p>
                                    <?php if ($user_role === 'super_admin'): ?>
                                    Total Students
                                    <?php else: ?>
                                    My Students
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($user_role === 'super_admin'): ?>
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3><?php echo $my_students_count; ?></h3>
                                <p>Students I Added</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-user-plus"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <h3><?php echo $total_admins; ?></h3>
                                <p>Active Admins</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-user-shield"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-gradient-secondary">
                            <div class="inner">
                                <?php
                                $no_creator_stmt = $conn->prepare("SELECT COUNT(*) FROM tbl_student WHERE created_by IS NULL");
                                $no_creator_stmt->execute();
                                $no_creator = $no_creator_stmt->fetchColumn();
                                ?>
                                <h3><?php echo $no_creator; ?></h3>
                                <p>System Added</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-user-cog"></i>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Section Information for Regular Admins -->
                <?php if ($user_role === 'admin'): ?>
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="section-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            <strong>Section Assignment:</strong>
                            <?php if ($assignedSection): ?>
                                You are assigned to section: 
                                <span class="badge badge-primary section-badge-large">
                                    <?php echo htmlspecialchars($assignedSection); ?>
                                </span>
                                <br>
                                <small class="text-muted">
                                    <i class="fas fa-user-check mr-1"></i>
                                    All students you add will automatically be assigned to this section.
                                </small>
                            <?php else: ?>
                                <span class="text-danger">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                    You are not assigned to any section. Please contact super admin.
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <div class="row mb-3">
                    <div class="col-12">
                        <button class="btn btn-success" data-toggle="modal" data-target="#importExcelModal">
                            <i class="fas fa-file-excel mr-2"></i> Import Excel
                        </button>
                        <button class="btn btn-primary float-right" data-toggle="modal" data-target="#addStudentModal" 
                                <?php echo ($user_role === 'admin' && !$assignedSection) ? 'disabled' : ''; ?>>
                            <i class="fas fa-plus mr-2"></i> Add Student
                        </button>
                        <?php if ($user_role === 'admin' && !$assignedSection): ?>
                        <small class="text-danger d-block mt-1">
                            <i class="fas fa-exclamation-circle mr-1"></i>
                            You cannot add students until you are assigned a section.
                        </small>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Super Admin Folder View -->
                <?php if ($user_role === 'super_admin'): ?>
                
                <!-- Search and Filter Controls -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="search-box">
                            <input type="text" id="searchStudent" class="form-control" placeholder="🔍 Search students across all folders...">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="admin-filter text-right">
                            <button class="btn btn-outline-secondary btn-sm" data-filter="all">All Folders</button>
                            <button class="btn btn-outline-secondary btn-sm" data-filter="expanded">Expand All</button>
                            <button class="btn btn-outline-secondary btn-sm active" data-filter="collapsed">Collapse All</button>
                        </div>
                    </div>
                </div>

                <!-- My Students Folder (Current Super Admin) -->
                <?php if (!empty($my_students)): ?>
                <div class="admin-folder my-folder" data-admin-id="<?php echo $user_id; ?>" data-admin-name="<?php echo htmlspecialchars($user_name); ?>">
                    <div class="admin-folder-header collapsed">
                        <div class="admin-info">
                            <i class="fas fa-folder folder-icon"></i>
                            <span class="admin-name">
                                <i class="fas fa-star mr-1" style="color: #ffd700;"></i>
                                My Added Students
                            </span>
                            <span class="admin-username">
                                <i class="fas fa-user mr-1"></i>
                                <?php echo htmlspecialchars($user_name); ?> (You)
                            </span>
                        </div>
                        <div class="admin-stats">
                            <span class="stat-badge">
                                <i class="fas fa-users"></i>
                                <?php echo count($my_students); ?> students
                            </span>
                            <i class="fas fa-chevron-circle-right expand-collapse-icon"></i>
                        </div>
                    </div>
                    <div class="admin-folder-body" style="display: none;">
                        <div class="table-responsive">
                            <table class="table table-hover folder-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Course & Section</th>
                                        <th>QR Code</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($my_students as $row): ?>
                                        <?php
                                        $studentID = $row["tbl_student_id"];
                                        $studentName = $row["student_name"];
                                        $studentCourse = $row["course_section"];
                                        $qrCode = $row["generated_code"];
                                        ?>
                                        <tr class="student-row" data-student-name="<?php echo strtolower(htmlspecialchars($studentName)); ?>" data-student-course="<?php echo strtolower(htmlspecialchars($studentCourse)); ?>">
                                            <td><?= $studentID ?></td>
                                            <td><?= htmlspecialchars($studentName) ?></td>
                                            <td><?= htmlspecialchars($studentCourse) ?></td>
                                            <td>
                                                <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#qrCodeModal<?= $studentID ?>">
                                                    <i class="fas fa-qrcode"></i> View QR
                                                </button>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-warning btn-sm" onclick="updateStudent(<?= $studentID ?>)">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <button class="btn btn-danger btn-sm" onclick="deleteStudent(<?= $studentID ?>)">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- QR Modal -->
                                        <div class="modal fade" id="qrCodeModal<?= $studentID ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title"><?= $studentName ?>'s QR Code</h5>
                                                        <small class="text-muted ml-2">(Added by: You)</small>
                                                        <button type="button" class="close" data-dismiss="modal">
                                                            <span>&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body text-center">
                                                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= $qrCode ?>" 
                                                             alt="QR Code" class="qr-modal-img">
                                                        <p class="mt-3 text-muted">Scan this QR code for attendance</p>
                                                        <p><small>Code: <code><?= $qrCode ?></code></small></p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Other Admins Folders -->
                <?php foreach ($admins as $admin): 
                    // Skip if no students and current user
                    if ($admin['user_id'] == $user_id) continue;
                    
                    // Get students for this admin
                    $admin_students_stmt = $conn->prepare("
                        SELECT s.*, u.full_name as admin_name, u.username as admin_username 
                        FROM tbl_student s 
                        LEFT JOIN tbl_users u ON s.created_by = u.user_id 
                        WHERE s.created_by = ?
                        ORDER BY s.tbl_student_id DESC
                    ");
                    $admin_students_stmt->execute([$admin['user_id']]);
                    $admin_students = $admin_students_stmt->fetchAll();
                    
                    if (empty($admin_students) && $admin['student_count'] == 0) continue;
                ?>
                <div class="admin-folder" data-admin-id="<?php echo $admin['user_id']; ?>" data-admin-name="<?php echo htmlspecialchars($admin['full_name']); ?>">
                    <div class="admin-folder-header collapsed">
                        <div class="admin-info">
                            <i class="fas fa-folder folder-icon"></i>
                            <span class="admin-name">
                                <i class="fas fa-user-shield mr-1"></i>
                                <?php echo htmlspecialchars($admin['full_name']); ?>
                            </span>
                            <span class="admin-username">
                                <i class="fas fa-at mr-1"></i>
                                <?php echo htmlspecialchars($admin['username']); ?>
                            </span>
                        </div>
                        <div class="admin-stats">
                            <span class="stat-badge">
                                <i class="fas fa-users"></i>
                                <?php echo count($admin_students); ?> students
                            </span>
                            <i class="fas fa-chevron-circle-right expand-collapse-icon"></i>
                        </div>
                    </div>
                    <div class="admin-folder-body" style="display: none;">
                        <?php if (!empty($admin_students)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover folder-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Course & Section</th>
                                        <th>QR Code</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($admin_students as $row): ?>
                                        <?php
                                        $studentID = $row["tbl_student_id"];
                                        $studentName = $row["student_name"];
                                        $studentCourse = $row["course_section"];
                                        $qrCode = $row["generated_code"];
                                        ?>
                                        <tr class="student-row" data-student-name="<?php echo strtolower(htmlspecialchars($studentName)); ?>" data-student-course="<?php echo strtolower(htmlspecialchars($studentCourse)); ?>">
                                            <td><?= $studentID ?></td>
                                            <td><?= htmlspecialchars($studentName) ?></td>
                                            <td><?= htmlspecialchars($studentCourse) ?></td>
                                            <td>
                                                <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#qrCodeModal<?= $studentID ?>">
                                                    <i class="fas fa-qrcode"></i> View QR
                                                </button>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <?php if ($admin['user_id'] == $user_id): ?>
                                                    <button class="btn btn-warning btn-sm" onclick="updateStudent(<?= $studentID ?>)">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <button class="btn btn-danger btn-sm" onclick="deleteStudent(<?= $studentID ?>)">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                    <?php else: ?>
                                                    <span class="text-muted permission-badge" data-toggle="tooltip" title="You can only modify students you added">
                                                        <i class="fas fa-lock"></i> Read Only
                                                    </span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- QR Modal -->
                                        <div class="modal fade" id="qrCodeModal<?= $studentID ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title"><?= $studentName ?>'s QR Code</h5>
                                                        <small class="text-muted ml-2">(Added by: <?php echo htmlspecialchars($admin['full_name']); ?>)</small>
                                                        <button type="button" class="close" data-dismiss="modal">
                                                            <span>&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body text-center">
                                                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= $qrCode ?>" 
                                                             alt="QR Code" class="qr-modal-img">
                                                        <p class="mt-3 text-muted">Scan this QR code for attendance</p>
                                                        <p><small>Code: <code><?= $qrCode ?></code></small></p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="empty-folder">
                            <i class="fas fa-folder-open"></i>
                            <h5>No Students Found</h5>
                            <p class="text-muted">This admin hasn't added any students yet.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- System Added Students Folder (No Creator) -->
                <?php if (!empty($system_students)): ?>
                <div class="admin-folder system-folder">
                    <div class="admin-folder-header collapsed">
                        <div class="admin-info">
                            <i class="fas fa-folder folder-icon"></i>
                            <span class="admin-name">
                                <i class="fas fa-cog mr-1"></i>
                                System Added Students
                            </span>
                            <span class="admin-username">
                                <i class="fas fa-robot mr-1"></i>
                                No assigned admin
                            </span>
                        </div>
                        <div class="admin-stats">
                            <span class="stat-badge">
                                <i class="fas fa-users"></i>
                                <?php echo count($system_students); ?> students
                            </span>
                            <i class="fas fa-chevron-circle-right expand-collapse-icon"></i>
                        </div>
                    </div>
                    <div class="admin-folder-body" style="display: none;">
                        <div class="table-responsive">
                            <table class="table table-hover folder-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Course & Section</th>
                                        <th>QR Code</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($system_students as $row): ?>
                                        <?php
                                        $studentID = $row["tbl_student_id"];
                                        $studentName = $row["student_name"];
                                        $studentCourse = $row["course_section"];
                                        $qrCode = $row["generated_code"];
                                        ?>
                                        <tr class="student-row" data-student-name="<?php echo strtolower(htmlspecialchars($studentName)); ?>" data-student-course="<?php echo strtolower(htmlspecialchars($studentCourse)); ?>">
                                            <td><?= $studentID ?></td>
                                            <td><?= htmlspecialchars($studentName) ?></td>
                                            <td><?= htmlspecialchars($studentCourse) ?></td>
                                            <td>
                                                <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#qrCodeModal<?= $studentID ?>">
                                                    <i class="fas fa-qrcode"></i> View QR
                                                </button>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-warning btn-sm" onclick="updateStudent(<?= $studentID ?>)">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <button class="btn btn-danger btn-sm" onclick="deleteStudent(<?= $studentID ?>)">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- QR Modal -->
                                        <div class="modal fade" id="qrCodeModal<?= $studentID ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title"><?= $studentName ?>'s QR Code</h5>
                                                        <small class="text-muted ml-2">(System Added)</small>
                                                        <button type="button" class="close" data-dismiss="modal">
                                                            <span>&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body text-center">
                                                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= $qrCode ?>" 
                                                             alt="QR Code" class="qr-modal-img">
                                                        <p class="mt-3 text-muted">Scan this QR code for attendance</p>
                                                        <p><small>Code: <code><?= $qrCode ?></code></small></p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php else: ?>
                <!-- Regular Admin Table View -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">List of Students</h3>
                        <div class="card-tools">
                            <span class="badge badge-primary">Viewing: My Students Only</span>
                            <?php if ($assignedSection): ?>
                            <span class="badge badge-success ml-2">Section: <?php echo htmlspecialchars($assignedSection); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive student-table">
                            <table class="table table-hover" id="studentTable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Course & Section</th>
                                        <th>QR Code</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($result as $row): ?>
                                        <?php
                                        $studentID = $row["tbl_student_id"];
                                        $studentName = $row["student_name"];
                                        $studentCourse = $row["course_section"];
                                        $qrCode = $row["generated_code"];
                                        ?>
                                        <tr>
                                            <td><?= $studentID ?></td>
                                            <td><?= htmlspecialchars($studentName) ?></td>
                                            <td><?= htmlspecialchars($studentCourse) ?></td>
                                            <td>
                                                <button class="btn btn-info btn-sm" data-toggle="modal" data-target="#qrCodeModal<?= $studentID ?>">
                                                    <i class="fas fa-qrcode"></i> View QR
                                                </button>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-warning btn-sm" onclick="updateStudent(<?= $studentID ?>)">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <button class="btn btn-danger btn-sm" onclick="deleteStudent(<?= $studentID ?>)">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- QR Modal -->
                                        <div class="modal fade" id="qrCodeModal<?= $studentID ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title"><?= $studentName ?>'s QR Code</h5>
                                                        <button type="button" class="close" data-dismiss="modal">
                                                            <span>&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body text-center">
                                                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= $qrCode ?>" 
                                                             alt="QR Code" class="qr-modal-img">
                                                        <p class="mt-3 text-muted">Scan this QR code for attendance</p>
                                                        <p><small>Code: <code><?= $qrCode ?></code></small></p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer class="main-footer">
        <strong>QR Code Attendance System &copy; <?php echo date('Y'); ?></strong>
        All rights reserved.
    </footer>
</div>

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Student</h5>
                <small class="text-muted ml-2">(Will be added under your account)</small>
                <?php if ($user_role === 'admin' && $assignedSection): ?>
                <span class="badge badge-info ml-2">Section: <?php echo htmlspecialchars($assignedSection); ?></span>
                <?php endif; ?>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="./endpoint/add-student.php" method="POST" id="addStudentForm">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        <?php if ($user_role === 'super_admin'): ?>
                        As a super admin, you can add students to any section. Please specify the course section.
                        <?php else: ?>
                        Student will be automatically assigned to your section: <strong><?php echo htmlspecialchars($assignedSection); ?></strong>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="studentName">Full Name:</label>
                        <input type="text" class="form-control" id="studentName" name="student_name" required>
                    </div>
                    
                    <?php if ($user_role === 'super_admin'): ?>
                    <div class="form-group">
                        <label for="studentCourse">Course and Section:</label>
                        <input type="text" class="form-control" id="studentCourse" name="course_section" required>
                    </div>
                    <?php else: ?>
                    <input type="hidden" name="course_section" value="<?php echo htmlspecialchars($assignedSection); ?>">
                    <div class="alert alert-primary">
                        <i class="fas fa-check-circle mr-2"></i>
                        <strong>Auto-assigned Section:</strong> <?php echo htmlspecialchars($assignedSection); ?>
                    </div>
                    <?php endif; ?>
                    
                    <button type="button" class="btn btn-secondary form-control" onclick="generateQrCode()">
                        <i class="fas fa-qrcode mr-2"></i> Generate QR Code
                    </button>

                    <div class="qr-con text-center mt-3" style="display: none;">
                        <input type="hidden" class="form-control" id="generatedCode" name="generated_code">
                        <p class="text-info">QR Code Generated! Take a picture of this QR code.</p>
                        <img class="mb-3" src="" id="qrImg" alt="QR Code" style="max-width: 200px;">
                    </div>
                    <div class="modal-footer" style="display: none;" id="addModalFooter">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="addStudentBtn">
                            Add Student
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Update Student Modal -->
<div class="modal fade" id="updateStudentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Student</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="./endpoint/update-student.php" method="POST" id="updateStudentForm">
                    <input type="hidden" class="form-control" id="updateStudentId" name="tbl_student_id">
                    
                    <div class="form-group">
                        <label for="updateStudentName">Full Name:</label>
                        <input type="text" class="form-control" id="updateStudentName" name="student_name" required>
                    </div>
                    
                    <?php if ($user_role === 'super_admin'): ?>
                    <div class="form-group">
                        <label for="updateStudentCourse">Course and Section:</label>
                        <input type="text" class="form-control" id="updateStudentCourse" name="course_section" required>
                    </div>
                    <?php else: ?>
                    <input type="hidden" id="updateStudentCourse" name="course_section" value="<?php echo htmlspecialchars($assignedSection); ?>">
                    <div class="alert alert-primary">
                        <i class="fas fa-lock mr-2"></i>
                        <strong>Section:</strong> <?php echo htmlspecialchars($assignedSection); ?> (Cannot be changed)
                    </div>
                    <?php endif; ?>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="updateStudentBtn">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Import Excel Modal -->
<div class="modal fade" id="importExcelModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Import Students from Excel</h5>
                <small class="text-muted ml-2">(Will be added under your account)</small>
                <?php if ($user_role === 'admin' && $assignedSection): ?>
                <span class="badge badge-info ml-2">Section: <?php echo htmlspecialchars($assignedSection); ?></span>
                <?php endif; ?>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="importExcelForm">
                    <?php if ($user_role === 'admin' && !$assignedSection): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        You are not assigned to any section. Please contact super admin before importing students.
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="excel_file">Select Excel File:</label>
                        <input type="file" class="form-control-file" id="excel_file" name="excel_file" accept=".xlsx,.xls" 
                               <?php echo ($user_role === 'admin' && !$assignedSection) ? 'disabled' : 'required'; ?>>
                        <small class="form-text text-muted">
                            Supported formats: .xlsx, .xls<br>
                            Expected columns: Student Name (Column A)<?php if ($user_role === 'super_admin'): ?>, Course & Section (Column B)<?php endif; ?>
                        </small>
                    </div>
                    <div class="alert alert-info">
                        <strong>Note:</strong> Make sure your Excel file has the following columns:<br>
                        <strong>Column A:</strong> Student Full Name<br>
                        <?php if ($user_role === 'super_admin'): ?>
                        <strong>Column B:</strong> Course and Section (Required for each student)<br>
                        <?php else: ?>
                        <strong>Column B:</strong> Course and Section (Optional - will use your assigned section if empty)<br>
                        <small class="text-muted">Your assigned section: <strong><?php echo htmlspecialchars($assignedSection); ?></strong></small>
                        <?php endif; ?>
                    </div>
                    <div class="progress" style="display: none;" id="importProgress">
                        <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="importExcel()" 
                        <?php echo ($user_role === 'admin' && !$assignedSection) ? 'disabled' : ''; ?> id="importExcelBtn">
                    <i class="fas fa-file-import mr-2"></i> Import Students
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
    
    <?php if ($user_role === 'super_admin'): ?>
    // All folders are collapsed by default - no need to initialize since HTML already has collapsed classes and display:none
    
    // Folder click handler
    $('.admin-folder-header').on('click', function(e) {
        // Don't toggle if clicking on buttons inside header
        if ($(e.target).closest('button').length) return;
        
        const $header = $(this);
        const $folder = $header.closest('.admin-folder');
        const $body = $folder.find('.admin-folder-body');
        const $icon = $header.find('.expand-collapse-icon');
        
        $body.slideToggle(300);
        $icon.toggleClass('fa-chevron-circle-right fa-chevron-circle-down');
        $header.toggleClass('collapsed');
    });
    
    // Expand All button
    $('[data-filter="expanded"]').on('click', function() {
        $('.admin-folder-body').slideDown(300);
        $('.admin-folder-header').removeClass('collapsed');
        $('.expand-collapse-icon').removeClass('fa-chevron-circle-right').addClass('fa-chevron-circle-down');
        $(this).addClass('active').siblings().removeClass('active');
    });
    
    // Collapse All button
    $('[data-filter="collapsed"]').on('click', function() {
        $('.admin-folder-body').slideUp(300);
        $('.admin-folder-header').addClass('collapsed');
        $('.expand-collapse-icon').removeClass('fa-chevron-circle-down').addClass('fa-chevron-circle-right');
        $(this).addClass('active').siblings().removeClass('active');
    });
    
    // All Folders button (resets to default - collapsed)
    $('[data-filter="all"]').on('click', function() {
        $('.admin-folder-body').slideUp(300);
        $('.admin-folder-header').addClass('collapsed');
        $('.expand-collapse-icon').removeClass('fa-chevron-circle-down').addClass('fa-chevron-circle-right');
        $(this).addClass('active').siblings().removeClass('active');
    });
    
    // Search functionality
    $('#searchStudent').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase().trim();
        
        if (searchTerm === '') {
            // Show all rows
            $('.student-row').show();
            // Show all folders but keep them collapsed
            $('.admin-folder').show();
            // Reset to collapsed state
            $('.admin-folder-body').hide();
            $('.admin-folder-header').addClass('collapsed');
            $('.expand-collapse-icon').removeClass('fa-chevron-circle-down').addClass('fa-chevron-circle-right');
            // Update student counts back to original
            $('.admin-folder').each(function() {
                const $folder = $(this);
                const totalCount = $folder.find('.student-row').length;
                $folder.find('.stat-badge:first').html(`<i class="fas fa-users"></i> ${totalCount} students`);
            });
        } else {
            // Hide all rows first
            $('.student-row').hide();
            
            // Show matching rows
            $(`.student-row[data-student-name*="${searchTerm}"], 
               .student-row[data-student-course*="${searchTerm}"]`).show();
            
            // Show folders that have visible students and expand them
            $('.admin-folder').each(function() {
                const $folder = $(this);
                const $visibleRows = $folder.find('.student-row:visible');
                const totalCount = $folder.find('.student-row').length;
                
                if ($visibleRows.length > 0) {
                    $folder.show();
                    $folder.find('.admin-folder-body').slideDown(300);
                    $folder.find('.admin-folder-header').removeClass('collapsed');
                    $folder.find('.expand-collapse-icon').removeClass('fa-chevron-circle-right').addClass('fa-chevron-circle-down');
                    $folder.find('.stat-badge:first').html(`<i class="fas fa-users"></i> ${$visibleRows.length}/${totalCount} students`);
                } else {
                    $folder.hide();
                }
            });
        }
    });
    
    // Save folder states in localStorage (optional - can be removed if not needed)
    $('.admin-folder-header').on('click', function() {
        const $folder = $(this).closest('.admin-folder');
        const adminId = $folder.data('admin-id') || 'system';
        const isExpanded = $folder.find('.admin-folder-body').is(':visible');
        
        let folderStates = localStorage.getItem('folderStates');
        folderStates = folderStates ? JSON.parse(folderStates) : {};
        folderStates[adminId] = isExpanded;
        localStorage.setItem('folderStates', JSON.stringify(folderStates));
    });
    
    <?php endif; ?>
    
    // Initialize DataTable for regular admin view
    <?php if ($user_role !== 'super_admin'): ?>
    $('#studentTable').DataTable({
        "pageLength": 10,
        "responsive": true
    });
    <?php endif; ?>
});

function updateStudent(id) {
    // Find the row with this student ID
    const button = document.querySelector(`button[onclick="updateStudent(${id})"]`);
    if (!button) return;
    
    const row = button.closest('tr');
    if (!row) return;
    
    // Get student data from the row
    const cells = row.cells;
    const studentName = cells[1].textContent;
    const studentCourse = cells[2].textContent;
    
    // Set values in the modal
    $("#updateStudentId").val(id);
    $("#updateStudentName").val(studentName);
    $("#updateStudentCourse").val(studentCourse);
    
    $("#updateStudentModal").modal("show");
}

function deleteStudent(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "./endpoint/delete-student.php?student=" + id;
        }
    });
}

function generateRandomCode(length) {
    const characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    let randomString = '';
    for (let i = 0; i < length; i++) {
        const randomIndex = Math.floor(Math.random() * characters.length);
        randomString += characters.charAt(randomIndex);
    }
    return randomString;
}

function generateQrCode() {
    const qrImg = document.getElementById('qrImg');
    const studentName = document.getElementById('studentName').value.trim();
    
    if (!studentName) {
        Swal.fire('Error', 'Please enter student name first!', 'error');
        return;
    }
    
    // Check if regular admin has assigned section
    <?php if ($user_role === 'admin' && !$assignedSection): ?>
    Swal.fire('Error', 'You are not assigned to any section. Please contact super admin.', 'error');
    return;
    <?php endif; ?>
    
    // For super admin, check course section
    <?php if ($user_role === 'super_admin'): ?>
    const studentCourse = document.getElementById('studentCourse').value.trim();
    if (!studentCourse) {
        Swal.fire('Error', 'Please enter course section for the student!', 'error');
        return;
    }
    <?php endif; ?>
    
    let text = generateRandomCode(10);
    $("#generatedCode").val(text);

    if (text === "") {
        Swal.fire('Error', 'Failed to generate QR code!', 'error');
        return;
    } else {
        const apiUrl = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(text)}`;
        qrImg.src = apiUrl;
        document.getElementById('studentName').style.pointerEvents = 'none';
        <?php if ($user_role === 'super_admin'): ?>
        document.getElementById('studentCourse').style.pointerEvents = 'none';
        <?php endif; ?>
        document.getElementById('addModalFooter').style.display = 'flex';
        document.querySelector('.qr-con').style.display = 'block';
    }
}

function importExcel() {
    const form = document.getElementById('importExcelForm');
    const formData = new FormData(form);
    const fileInput = document.getElementById('excel_file');
    
    if (!fileInput.files.length) {
        Swal.fire('Error', 'Please select an Excel file to import.', 'error');
        return;
    }

    const file = fileInput.files[0];
    const validTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'];
    
    if (!validTypes.includes(file.type)) {
        Swal.fire('Error', 'Please select a valid Excel file (.xlsx or .xls).', 'error');
        return;
    }

    const submitBtn = document.getElementById('importExcelBtn');
    const originalHtml = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Importing...';

    fetch('./endpoint/import-students-excel.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire('Success', data.message, 'success').then(() => {
                $('#importExcelModal').modal('hide');
                location.reload();
            });
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error', 'An error occurred while importing the file.', 'error');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalHtml;
    });
}

// Handle modal cleanup
$('#addStudentModal').on('hidden.bs.modal', function () {
    // Reset form
    const form = document.getElementById('addStudentForm');
    if (form) {
        form.reset();
        const qrCon = document.querySelector('.qr-con');
        if (qrCon) qrCon.style.display = 'none';
        const footer = document.getElementById('addModalFooter');
        if (footer) footer.style.display = 'none';
        const nameField = document.getElementById('studentName');
        if (nameField) nameField.style.pointerEvents = 'auto';
        <?php if ($user_role === 'super_admin'): ?>
        const courseField = document.getElementById('studentCourse');
        if (courseField) courseField.style.pointerEvents = 'auto';
        <?php endif; ?>
    }
    // Reset submit button
    const submitBtn = document.getElementById('addStudentBtn');
    if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Add Student';
        submitBtn.classList.remove('btn-spinner');
    }
});

$('#importExcelModal').on('hidden.bs.modal', function () {
    document.getElementById('importExcelForm').reset();
});

// Handle form submissions with AJAX
$('#addStudentForm').on('submit', function(e) {
    e.preventDefault();
    
    const form = $(this);
    const submitBtn = document.getElementById('addStudentBtn');
    const originalText = submitBtn.innerHTML;
    
    // Disable button and show spinner
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
    submitBtn.classList.add('btn-spinner');
    
    // Simple validation
    if (!$('#generatedCode').val()) {
        Swal.fire('Error', 'Please generate QR code first!', 'error');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
        submitBtn.classList.remove('btn-spinner');
        return;
    }
    
    // Send AJAX request
    $.ajax({
        url: './endpoint/add-student.php',
        method: 'POST',
        data: form.serialize(),
        dataType: 'json',
        success: function(response) {
            console.log('Response:', response);
            
            if (response.success) {
                // Show success message
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: response.message,
                    showConfirmButton: true,
                    timer: 2000
                }).then(() => {
                    // Close modal
                    $('#addStudentModal').modal('hide');
                    
                    // Reload page after short delay
                    setTimeout(() => {
                        location.reload();
                    }, 500);
                });
            } else {
                // Show error message
                Swal.fire('Error', response.message, 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                submitBtn.classList.remove('btn-spinner');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', xhr.responseText);
            Swal.fire('Error', 'Failed to connect to server. Please try again.', 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
            submitBtn.classList.remove('btn-spinner');
        }
    });
});

$('#updateStudentForm').on('submit', function(e) {
    e.preventDefault();
    
    const form = $(this);
    const submitBtn = document.getElementById('updateStudentBtn');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
    submitBtn.classList.add('btn-spinner');
    
    $.ajax({
        url: './endpoint/update-student.php',
        method: 'POST',
        data: form.serialize(),
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Updated!',
                    text: response.message,
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Error', response.message, 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                submitBtn.classList.remove('btn-spinner');
            }
        },
        error: function() {
            Swal.fire('Error', 'Failed to update student. Please try again.', 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
            submitBtn.classList.remove('btn-spinner');
        }
    });
});

// JavaScript error handler
window.onerror = function(message, source, lineno, colno, error) {
    console.error('JavaScript Error:', message, 'at', source, 'line', lineno);
    return true;
};
</script>
</body>
</html>
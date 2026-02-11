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
    // Super admin sees all students with admin info
    $stmt = $conn->prepare("
        SELECT s.*, u.full_name as admin_name, u.username as admin_username 
        FROM tbl_student s 
        LEFT JOIN tbl_users u ON s.created_by = u.user_id 
        ORDER BY s.tbl_student_id DESC
    ");
    $stmt->execute();
} else {
    // Regular admin only sees their own students
    $stmt = $conn->prepare("
        SELECT s.* 
        FROM tbl_student s 
        WHERE s.created_by = ? 
        ORDER BY s.tbl_student_id DESC
    ");
    $stmt->execute([$user_id]);
}

$result = $stmt->fetchAll();

// Get total counts for stats
if ($user_role === 'super_admin') {
    $total_stmt = $conn->prepare("SELECT COUNT(*) FROM tbl_student");
    $total_stmt->execute();
    $total_students = $total_stmt->fetchColumn();
    
    $my_students_stmt = $conn->prepare("SELECT COUNT(*) FROM tbl_student WHERE created_by = ?");
    $my_students_stmt->execute([$user_id]);
    $my_students = $my_students_stmt->fetchColumn();
} else {
    $total_stmt = $conn->prepare("SELECT COUNT(*) FROM tbl_student WHERE created_by = ?");
    $total_stmt->execute([$user_id]);
    $total_students = $total_stmt->fetchColumn();
    $my_students = $total_students;
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
                                <h3><?php echo $my_students; ?></h3>
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
                                <?php
                                $other_stmt = $conn->prepare("SELECT COUNT(DISTINCT created_by) FROM tbl_student WHERE created_by IS NOT NULL");
                                $other_stmt->execute();
                                $other_admins = $other_stmt->fetchColumn();
                                ?>
                                <h3><?php echo $other_admins; ?></h3>
                                <p>Other Admins</p>
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

                <!-- Student Table -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">List of Students</h3>
                        <?php if ($user_role === 'super_admin'): ?>
                        <div class="card-tools">
                            <span class="badge badge-info">Viewing: All Students</span>
                        </div>
                        <?php else: ?>
                        <div class="card-tools">
                            <span class="badge badge-primary">Viewing: My Students Only</span>
                            <?php if ($assignedSection): ?>
                            <span class="badge badge-success ml-2">Section: <?php echo htmlspecialchars($assignedSection); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
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
                                        <?php if ($user_role === 'super_admin'): ?>
                                        <th>Added By</th>
                                        <?php endif; ?>
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
                                        $createdBy = $row["created_by"] ?? null;
                                        $adminName = $row["admin_name"] ?? null;
                                        $adminUsername = $row["admin_username"] ?? null;
                                        
                                        // Check if current user can edit/delete this student
                                        $canModify = ($user_role === 'super_admin') || ($createdBy == $user_id);
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
                                            <?php if ($user_role === 'super_admin'): ?>
                                            <td>
                                                <?php if ($adminName): ?>
                                                <span class="badge badge-secondary" data-toggle="tooltip" title="Username: <?= htmlspecialchars($adminUsername) ?>">
                                                    <?= htmlspecialchars($adminName) ?>
                                                </span>
                                                <?php elseif ($createdBy): ?>
                                                <span class="badge badge-light">Admin ID: <?= $createdBy ?></span>
                                                <?php else: ?>
                                                <span class="badge badge-dark">System</span>
                                                <?php endif; ?>
                                            </td>
                                            <?php endif; ?>
                                            <td>
                                                <div class="action-buttons">
                                                    <?php if ($canModify): ?>
                                                    <button class="btn btn-warning btn-sm" onclick="updateStudent(<?= $studentID ?>)">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <button class="btn btn-danger btn-sm" onclick="deleteStudent(<?= $studentID ?>)">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                    <?php else: ?>
                                                    <span class="text-muted permission-badge" data-toggle="tooltip" title="You can only modify students you added">
                                                        <i class="fas fa-lock"></i> No Permission
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
                                                        <?php if ($user_role === 'super_admin' && $adminName): ?>
                                                        <small class="text-muted ml-2">(Added by: <?= htmlspecialchars($adminName) ?>)</small>
                                                        <?php endif; ?>
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
    $('#studentTable').DataTable({
        "pageLength": 10,
        "responsive": true
    });
    
    // Enable tooltips
    $('[data-toggle="tooltip"]').tooltip();
});

function updateStudent(id) {
    // Find the row with this student ID
    const row = document.querySelector(`tr:has(button[onclick="updateStudent(${id})"])`);
    if (!row) return;
    
    // Get student data from the row
    const studentName = row.cells[1].textContent;
    const studentCourse = row.cells[2].textContent;
    
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
            window.location = "./endpoint/delete-student.php?student=" + id;
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
    
    // Get form data
    const formData = new FormData();
    formData.append('student_name', $('#studentName').val());
    formData.append('generated_code', $('#generatedCode').val());
    
    <?php if ($user_role === 'super_admin'): ?>
    formData.append('course_section', $('#studentCourse').val());
    <?php else: ?>
    formData.append('course_section', '<?php echo htmlspecialchars($assignedSection); ?>');
    <?php endif; ?>
    
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
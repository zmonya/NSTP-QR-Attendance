<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include ('./conn/conn.php');

$stmt = $conn->prepare("SELECT * FROM tbl_student");
$stmt->execute();
$result = $stmt->fetchAll();
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
                <!-- Action Buttons -->
                <div class="row mb-3">
                    <div class="col-12">
                        <button class="btn btn-success" data-toggle="modal" data-target="#importExcelModal">
                            <i class="fas fa-file-excel mr-2"></i> Import Excel
                        </button>
                        <button class="btn btn-primary float-right" data-toggle="modal" data-target="#addStudentModal">
                            <i class="fas fa-plus mr-2"></i> Add Student
                        </button>
                    </div>
                </div>

                <!-- Student Table -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">List of Students</h3>
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
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="./endpoint/add-student.php" method="POST">
                    <div class="form-group">
                        <label for="studentName">Full Name:</label>
                        <input type="text" class="form-control" id="studentName" name="student_name" required>
                    </div>
                    <div class="form-group">
                        <label for="studentCourse">Course and Section:</label>
                        <input type="text" class="form-control" id="studentCourse" name="course_section" required>
                    </div>
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
                        <button type="submit" class="btn btn-primary">Add Student</button>
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
                <form action="./endpoint/update-student.php" method="POST">
                    <input type="hidden" class="form-control" id="updateStudentId" name="tbl_student_id">
                    <div class="form-group">
                        <label for="updateStudentName">Full Name:</label>
                        <input type="text" class="form-control" id="updateStudentName" name="student_name" required>
                    </div>
                    <div class="form-group">
                        <label for="updateStudentCourse">Course and Section:</label>
                        <input type="text" class="form-control" id="updateStudentCourse" name="course_section" required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update</button>
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
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="importExcelForm">
                    <div class="form-group">
                        <label for="excel_file">Select Excel File:</label>
                        <input type="file" class="form-control-file" id="excel_file" name="excel_file" accept=".xlsx,.xls" required>
                        <small class="form-text text-muted">
                            Supported formats: .xlsx, .xls<br>
                            Expected columns: Student Name (Column A), Course & Section (Column B)
                        </small>
                    </div>
                    <div class="alert alert-info">
                        <strong>Note:</strong> Make sure your Excel file has the following columns:<br>
                        <strong>Column A:</strong> Student Full Name<br>
                        <strong>Column B:</strong> Course and Section
                    </div>
                    <div class="progress" style="display: none;" id="importProgress">
                        <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="importExcel()">Import Students</button>
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

<script>
$(document).ready(function() {
    $('#studentTable').DataTable({
        "pageLength": 10,
        "responsive": true
    });
});

function updateStudent(id) {
    $("#updateStudentModal").modal("show");
    let updateStudentId = $("#studentID-" + id)?.text() || id;
    let updateStudentName = $("#studentName-" + id)?.text() || "";
    let updateStudentCourse = $("#studentCourse-" + id)?.text() || "";
    
    $("#updateStudentId").val(updateStudentId);
    $("#updateStudentName").val(updateStudentName);
    $("#updateStudentCourse").val(updateStudentCourse);
}

function deleteStudent(id) {
    if (confirm("Are you sure you want to delete this student?")) {
        window.location = "./endpoint/delete-student.php?student=" + id;
    }
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
    let text = generateRandomCode(10);
    $("#generatedCode").val(text);

    if (text === "") {
        alert("Please enter text to generate a QR code.");
        return;
    } else {
        const apiUrl = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(text)}`;
        qrImg.src = apiUrl;
        document.getElementById('studentName').style.pointerEvents = 'none';
        document.getElementById('studentCourse').style.pointerEvents = 'none';
        document.getElementById('addModalFooter').style.display = 'flex';
        document.querySelector('.qr-con').style.display = 'block';
    }
}

function importExcel() {
    const form = document.getElementById('importExcelForm');
    const formData = new FormData(form);
    const fileInput = document.getElementById('excel_file');
    
    if (!fileInput.files.length) {
        alert('Please select an Excel file to import.');
        return;
    }

    const file = fileInput.files[0];
    const validTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'];
    
    if (!validTypes.includes(file.type)) {
        alert('Please select a valid Excel file (.xlsx or .xls).');
        return;
    }

    const submitBtn = document.querySelector('[onclick="importExcel()"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Importing...';

    fetch('./endpoint/import-students-excel.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            $('#importExcelModal').modal('hide');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while importing the file.');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Import Students';
    });
}

$('#importExcelModal').on('hidden.bs.modal', function () {
    document.getElementById('importExcelForm').reset();
});
</script>
</body>
</html>
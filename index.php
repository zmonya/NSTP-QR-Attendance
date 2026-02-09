    <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Attendance System</title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@500&display=swap');

        * {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(to bottom, rgba(255,255,255,0.15) 0%, rgba(0,0,0,0.15) 100%), radial-gradient(at top center, rgba(255,255,255,0.40) 0%, rgba(0,0,0,0.40) 120%) #989898;
            background-blend-mode: multiply,multiply;
            background-attachment: fixed;
            background-repeat: no-repeat;
            background-size: cover;
        }

        .main {
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            min-height: 100vh;
            padding: 20px 10px;
            background-color: rgba(255, 255, 255, 0.8);
        }

        .attendance-container {
            width: 100%;
            max-width: 1200px;
            border-radius: 20px;
            padding: 20px;
            background-color: rgba(255, 255, 255, 0.95);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .attendance-container > div {
            box-shadow: rgba(0, 0, 0, 0.24) 0px 3px 8px;
            border-radius: 10px;
            padding: 15px;
            background-color: #fff;
            max-height: 80vh;
            overflow-y: auto;
        }

        .attendance-container > div:last-child {
            width: 100%;
            margin-left: 0;
        }
    </style>
</head>
<body>
<?php include 'navbar_updated.php'; ?>

    <div class="main">
        
            <div class="attendance-container">
                <div class="row">
                    <div class="qr-container col-12 col-md-4 mb-4">
                        <div class="scanner-con text-center">
                            <h5 class="mb-3">Scan your QR Code here for your attendance</h5>
                            <video id="interactive" class="viewport" width="100%" playsinline></video>
                            <div class="mt-3">
                                <button class="btn btn-primary" onclick="startScanner()">
                                    Start Camera
                                </button>
                            </div>
                        </div>

                        <div class="qr-detected-container" style="display: none;">
                            <form action="./endpoint/add-attendance.php" method="POST" class="text-center">
                                <h4 class="text-success mb-3">Student QR Detected!</h4>
                                <input type="hidden" id="detected-qr-code" name="qr_code">
                                <button type="submit" class="btn btn-success btn-lg">Submit Attendance</button>
                            </form>
                        </div>
                    </div>

                    <div class="attendance-list col-12 col-md-8">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4>List of Present Students</h4>
                            <a href="./endpoint/download-attendance-excel.php" class="btn btn-success">
                                <i class="fas fa-file-excel"></i> Download Excel
                            </a>
                        </div>
                        <div class="table-container table-responsive">
                            <table class="table text-center table-sm" id="attendanceTable">
                                <thead class="thead-dark">
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Name</th>
                                        <th scope="col">Course & Section</th>
                                        <th scope="col">Time In</th>
                                        <th scope="col">Action</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    <?php 
                                        include ('./conn/conn.php');

                                        $stmt = $conn->prepare("SELECT * FROM tbl_attendance LEFT JOIN tbl_student ON tbl_student.tbl_student_id = tbl_attendance.tbl_student_id ORDER BY tbl_attendance.tbl_attendance_id DESC");
                                        $stmt->execute();
                        
                                        $result = $stmt->fetchAll();
                        
                                        foreach ($result as $row) {
                                            $attendanceID = $row["tbl_attendance_id"];
                                            $studentName = $row["student_name"];
                                            $studentCourse = $row["course_section"];
                                            $timeIn = $row["time_in"];
                                    ?>

                                    <tr>
                                        <th scope="row"><?= $attendanceID ?></th>
                                        <td><?= htmlspecialchars($studentName) ?></td>
                                        <td><?= htmlspecialchars($studentCourse) ?></td>
                                        <?php date_default_timezone_set('Asia/Manila'); ?>
                                        <td><?= date('M d, Y h:i A', strtotime($timeIn)) ?></td>
                                        <td>
                                            <button class="btn btn-danger btn-sm delete-button" onclick="deleteAttendance(<?= $attendanceID ?>)">
                                                X
                                            </button>
                                        </td>
                                    </tr>

                                    <?php
                                }
                                ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

    </div>
    

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>

    <!-- instascan Js -->
    <script src="https://rawgit.com/schmich/instascan-builds/master/instascan.min.js"></script>

    <script>

        
        let scanner;

        function startScanner() {
            scanner = new Instascan.Scanner({ video: document.getElementById('interactive') });

            scanner.addListener('scan', function (content) {
                $("#detected-qr-code").val(content);
                console.log(content);
                scanner.stop();
                document.querySelector(".qr-detected-container").style.display = '';
                document.querySelector(".scanner-con").style.display = 'none';
            });

            Instascan.Camera.getCameras()
                .then(function (cameras) {
                    if (cameras.length > 0) {
                        scanner.start(cameras[0]);
                    } else {
                        console.error('No cameras found.');
                        alert('No cameras found.');
                    }
                })
                .catch(function (err) {
                    console.error('Camera access error:', err);
                    alert('Camera access error: ' + err);
                });
        }

        document.addEventListener('DOMContentLoaded', startScanner);

        function deleteAttendance(id) {
            if (confirm("Do you want to remove this attendance?")) {
                window.location = "./endpoint/delete-attendance.php?attendance=" + id;
            }
        }
    </script>
</body>
</html>
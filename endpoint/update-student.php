<?php
session_start(); // Add session start
include("../conn/conn.php");

if (!isset($_SESSION['user_id'])) {
    echo "
        <script>
            alert('Unauthorized access!');
            window.location.href = 'http://localhost/qr-code-attendance-system/index.php';
        </script>
    ";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['student_name'], $_POST['course_section'])) {
        $studentId = $_POST['tbl_student_id'];
        $studentName = $_POST['student_name'];
        $studentCourse = $_POST['course_section'];
        $userId = $_SESSION['user_id'];
        $userRole = $_SESSION['role'] ?? 'admin';
        
        try {
            // First, check if student exists and user has permission
            $checkStmt = $conn->prepare("SELECT created_by FROM tbl_student WHERE tbl_student_id = :student_id");
            $checkStmt->bindParam(":student_id", $studentId, PDO::PARAM_INT);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() === 0) {
                echo "
                    <script>
                        alert('Student not found!');
                        window.location.href = 'http://localhost/qr-code-attendance-system/masterlist.php';
                    </script>
                ";
                exit();
            }
            
            $student = $checkStmt->fetch();
            
            // Check permission: super admin OR user who created the student
            if ($userRole !== 'super_admin' && $student['created_by'] != $userId) {
                echo "
                    <script>
                        alert('You do not have permission to edit this student!');
                        window.location.href = 'http://localhost/qr-code-attendance-system/masterlist.php';
                    </script>
                ";
                exit();
            }
            
            // Update student if permission granted
            $stmt = $conn->prepare("UPDATE tbl_student SET student_name = :student_name, course_section = :course_section WHERE tbl_student_id = :tbl_student_id");
            
            $stmt->bindParam(":tbl_student_id", $studentId, PDO::PARAM_INT); 
            $stmt->bindParam(":student_name", $studentName, PDO::PARAM_STR); 
            $stmt->bindParam(":course_section", $studentCourse, PDO::PARAM_STR);

            $stmt->execute();
            
            echo "
                <script>
                    alert('Student updated successfully!');
                    window.location.href = 'http://localhost/qr-code-attendance-system/masterlist.php';
                </script>
            ";
            
            exit();
        } catch (PDOException $e) {
            echo "
                <script>
                    alert('Error: " . addslashes($e->getMessage()) . "');
                    window.location.href = 'http://localhost/qr-code-attendance-system/masterlist.php';
                </script>
            ";
        }

    } else {
        echo "
            <script>
                alert('Please fill in all fields!');
                window.location.href = 'http://localhost/qr-code-attendance-system/masterlist.php';
            </script>
        ";
    }
}
?>
<?php
session_start(); // Add session start
include('../conn/conn.php');

if (!isset($_SESSION['user_id'])) {
    echo "
        <script>
            alert('Unauthorized access!');
            window.location.href = 'http://localhost/qr-code-attendance-system/index.php';
        </script>
    ";
    exit();
}

if (isset($_GET['student'])) {
    $studentId = $_GET['student'];
    $userId = $_SESSION['user_id'];
    $userRole = $_SESSION['role'] ?? 'admin';

    try {
        // First, check if student exists and user has permission
        $checkStmt = $conn->prepare("SELECT created_by FROM tbl_student WHERE tbl_student_id = ?");
        $checkStmt->execute([$studentId]);
        
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
                    alert('You do not have permission to delete this student!');
                    window.location.href = 'http://localhost/qr-code-attendance-system/masterlist.php';
                </script>
            ";
            exit();
        }
        
        // Delete student if permission granted
        $query = "DELETE FROM tbl_student WHERE tbl_student_id = ?";
        $stmt = $conn->prepare($query);
        $query_execute = $stmt->execute([$studentId]);

        if ($query_execute) {
            echo "
                <script>
                    alert('Student deleted successfully!');
                    window.location.href = 'http://localhost/qr-code-attendance-system/masterlist.php';
                </script>
            ";
        } else {
            echo "
                <script>
                    alert('Failed to delete student!');
                    window.location.href = 'http://localhost/qr-code-attendance-system/masterlist.php';
                </script>
            ";
        }

    } catch (PDOException $e) {
        echo "
            <script>
                alert('Error: " . addslashes($e->getMessage()) . "');
                window.location.href = 'http://localhost/qr-code-attendance-system/masterlist.php';
            </script>
        ";
    }
}

?>
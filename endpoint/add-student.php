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
        $studentName = $_POST['student_name'];
        $studentCourse = $_POST['course_section'];
        $generatedCode = $_POST['generated_code'];
        $createdBy = $_SESSION['user_id']; // Get the current user ID
        
        try {
            $stmt = $conn->prepare("INSERT INTO tbl_student (student_name, course_section, generated_code, created_by) VALUES (:student_name, :course_section, :generated_code, :created_by)");
            
            $stmt->bindParam(":student_name", $studentName, PDO::PARAM_STR); 
            $stmt->bindParam(":course_section", $studentCourse, PDO::PARAM_STR);
            $stmt->bindParam(":generated_code", $generatedCode, PDO::PARAM_STR);
            $stmt->bindParam(":created_by", $createdBy, PDO::PARAM_INT);
            
            $stmt->execute();
            
            echo "
                <script>
                    alert('Student added successfully!');
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
<?php
session_start();
include("../conn/conn.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['qr_code']) && !empty(trim($_POST['qr_code']))) {
        $qrCode = trim($_POST['qr_code']);
        $studentID = null;
        $timeIn = date("Y-m-d H:i:s");
        
        // Validate QR code format (adjust regex as needed)
        if (!preg_match('/^[A-Za-z0-9\-_]+$/', $qrCode)) {
            $_SESSION['error'] = "Invalid QR Code format";
            header("Location: http://localhost/qr-code-attendance-system/index.php");
            exit();
        }

        // Get student ID from QR code
        try {
            $selectStmt = $conn->prepare("SELECT tbl_student_id FROM tbl_student WHERE generated_code = :generated_code");
            $selectStmt->bindParam(":generated_code", $qrCode, PDO::PARAM_STR);
            
            if ($selectStmt->execute()) {
                $result = $selectStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result) {
                    $studentID = $result["tbl_student_id"];
                    
                    // Check if attendance already exists for today
                    $checkStmt = $conn->prepare(
                        "SELECT tbl_attendance_id FROM tbl_attendance 
                         WHERE tbl_student_id = :student_id 
                         AND DATE(time_in) = CURDATE() 
                         LIMIT 1"
                    );
                    $checkStmt->bindParam(":student_id", $studentID, PDO::PARAM_INT);
                    
                    if ($checkStmt->execute() && $checkStmt->fetch()) {
                        $_SESSION['error'] = "Attendance already recorded for today";
                        header("Location: http://localhost/qr-code-attendance-system/index.php");
                        exit();
                    }
                    
                    // Insert new attendance record
                    $insertStmt = $conn->prepare(
                        "INSERT INTO tbl_attendance (tbl_student_id, time_in) 
                         VALUES (:tbl_student_id, :time_in)"
                    );
                    
                    $insertStmt->bindParam(":tbl_student_id", $studentID, PDO::PARAM_INT);
                    $insertStmt->bindParam(":time_in", $timeIn, PDO::PARAM_STR);
                    
                    if ($insertStmt->execute()) {
                        $_SESSION['success'] = "Attendance recorded successfully!";
                        header("Location: http://localhost/qr-code-attendance-system/index.php");
                        exit();
                    } else {
                        throw new Exception("Failed to insert attendance record");
                    }
                } else {
                    $_SESSION['error'] = "No student found with this QR Code";
                    header("Location: http://localhost/qr-code-attendance-system/index.php");
                    exit();
                }
            } else {
                throw new Exception("Database query failed");
            }
        } catch (PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
            $_SESSION['error'] = "System error. Please try again.";
            header("Location: http://localhost/qr-code-attendance-system/index.php");
            exit();
        } catch (Exception $e) {
            error_log("Error: " . $e->getMessage());
            $_SESSION['error'] = $e->getMessage();
            header("Location: http://localhost/qr-code-attendance-system/index.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "QR Code is required";
        header("Location: http://localhost/qr-code-attendance-system/index.php");
        exit();
    }
} else {
    $_SESSION['error'] = "Invalid request method";
    header("Location: http://localhost/qr-code-attendance-system/index.php");
    exit();
}
?>
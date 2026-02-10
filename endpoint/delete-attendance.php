<?php
session_start();
include('../conn/conn.php');

if (isset($_GET['attendance'])) {
    $attendanceID = $_GET['attendance'];
    
    // Validate input
    if (!is_numeric($attendanceID) || $attendanceID <= 0) {
        $_SESSION['error'] = "Invalid attendance ID";
        header("Location: http://localhost/qr-code-attendance-system/index.php");
        exit();
    }
    
    // Check if attendance record exists before deleting
    try {
        // First check if record exists
        $checkStmt = $conn->prepare(
            "SELECT tbl_attendance_id FROM tbl_attendance 
             WHERE tbl_attendance_id = :attendance_id 
             LIMIT 1"
        );
        $checkStmt->bindParam(":attendance_id", $attendanceID, PDO::PARAM_INT);
        
        if (!$checkStmt->execute() || !$checkStmt->fetch()) {
            $_SESSION['error'] = "Attendance record not found";
            header("Location: http://localhost/qr-code-attendance-system/index.php");
            exit();
        }
        
        // Proceed with deletion using parameterized query
        $deleteStmt = $conn->prepare(
            "DELETE FROM tbl_attendance 
             WHERE tbl_attendance_id = :attendance_id"
        );
        $deleteStmt->bindParam(":attendance_id", $attendanceID, PDO::PARAM_INT);
        
        if ($deleteStmt->execute()) {
            $rowCount = $deleteStmt->rowCount();
            
            if ($rowCount > 0) {
                $_SESSION['success'] = "Attendance deleted successfully!";
            } else {
                $_SESSION['error'] = "No attendance record was deleted";
            }
        } else {
            throw new Exception("Failed to execute delete statement");
        }
        
    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        $_SESSION['error'] = "Database error occurred";
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        $_SESSION['error'] = $e->getMessage();
    }
} else {
    $_SESSION['error'] = "No attendance ID provided";
}

// Redirect back to index
header("Location: http://localhost/qr-code-attendance-system/index.php");
exit();
?>
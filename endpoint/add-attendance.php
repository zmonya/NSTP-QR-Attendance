<?php
session_start();
include("../conn/conn.php");

// Set timezone
date_default_timezone_set('Asia/Manila');

// Return JSON response for AJAX requests
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Get QR code from POST
    $qrCode = isset($_POST['qr_code']) ? trim($_POST['qr_code']) : '';
    
    if (empty($qrCode)) {
        echo json_encode([
            'success' => false, 
            'message' => 'QR Code is required'
        ]);
        exit();
    }
    
    // Don't restrict QR code format - it can be any string
    // Just remove any dangerous characters
    $qrCode = preg_replace('/[^A-Za-z0-9\-_]/', '', $qrCode);
    
    if (empty($qrCode)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid QR Code format'
        ]);
        exit();
    }
    
    $studentID = null;
    $timeIn = date("Y-m-d H:i:s");
    
    try {
        // Get student ID from QR code
        $selectStmt = $conn->prepare("
            SELECT tbl_student_id, student_name, course_section 
            FROM tbl_student 
            WHERE generated_code = :generated_code OR qr_code = :qr_code
        ");
        $selectStmt->bindParam(":generated_code", $qrCode, PDO::PARAM_STR);
        $selectStmt->bindParam(":qr_code", $qrCode, PDO::PARAM_STR);
        
        if ($selectStmt->execute()) {
            $result = $selectStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $studentID = $result["tbl_student_id"];
                $studentName = $result["student_name"];
                $courseSection = $result["course_section"];
                
                // Check if attendance already exists for today
                $checkStmt = $conn->prepare("
                    SELECT tbl_attendance_id, status 
                    FROM tbl_attendance 
                    WHERE tbl_student_id = :student_id 
                    AND DATE(time_in) = CURDATE() 
                    LIMIT 1
                ");
                $checkStmt->bindParam(":student_id", $studentID, PDO::PARAM_INT);
                $checkStmt->execute();
                $existingAttendance = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existingAttendance) {
                    echo json_encode([
                        'success' => false, 
                        'message' => $studentName . ' already attended today',
                        'status' => $existingAttendance['status']
                    ]);
                    exit();
                }
                
                // Determine status (8:00 AM cutoff)
                $currentHour = (int)date('H');
                $currentMinutes = (int)date('i');
                $status = ($currentHour < 8 || ($currentHour == 8 && $currentMinutes == 0)) ? 'On Time' : 'Late';
                
                // Insert new attendance record
                $insertStmt = $conn->prepare("
                    INSERT INTO tbl_attendance (tbl_student_id, time_in, status) 
                    VALUES (:tbl_student_id, :time_in, :status)
                ");
                
                $insertStmt->bindParam(":tbl_student_id", $studentID, PDO::PARAM_INT);
                $insertStmt->bindParam(":time_in", $timeIn, PDO::PARAM_STR);
                $insertStmt->bindParam(":status", $status, PDO::PARAM_STR);
                
                if ($insertStmt->execute()) {
                    echo json_encode([
                        'success' => true,
                        'message' => $studentName . ' marked as ' . $status,
                        'student_name' => $studentName,
                        'course_section' => $courseSection,
                        'status' => $status,
                        'time' => date('h:i A', strtotime($timeIn))
                    ]);
                    exit();
                } else {
                    throw new Exception("Failed to insert attendance record");
                }
            } else {
                echo json_encode([
                    'success' => false, 
                    'message' => 'No student found with this QR Code: ' . $qrCode
                ]);
                exit();
            }
        } else {
            throw new Exception("Database query failed");
        }
    } catch (PDOException $e) {
        error_log("Database Error in add-attendance: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => 'Database error. Please try again.'
        ]);
        exit();
    } catch (Exception $e) {
        error_log("Error in add-attendance: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => $e->getMessage()
        ]);
        exit();
    }
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid request method'
    ]);
    exit();
}
?>
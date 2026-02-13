<?php
session_start();
require_once '../conn/conn.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['valid' => false, 'message' => 'Unauthorized']);
    exit();
}

$qr_code = $_POST['qr_code'] ?? '';
$admin_id = $_SESSION['user_id'];

if (empty($qr_code)) {
    echo json_encode(['valid' => false, 'message' => 'QR code is required']);
    exit();
}

try {
    // Clean the QR code - remove any whitespace or special characters
    $qr_code = trim($qr_code);
    
    // Debug log
    error_log("Scanning QR Code: " . $qr_code);
    
    // Check if student exists with this generated_code AND belongs to this admin
    $stmt = $conn->prepare("
        SELECT s.tbl_student_id, s.student_name, s.course_section, s.generated_code
        FROM tbl_student s
        LEFT JOIN tbl_admin_sections ads ON s.course_section = ads.course_section
        WHERE s.generated_code = ? 
        AND (s.created_by = ? OR ads.user_id = ? OR 1 = ?) 
        LIMIT 1
    ");
    
    // For super_admin (user_id = 1), they can see all students
    $is_super_admin = ($_SESSION['role'] == 'super_admin' || $admin_id == 1) ? 1 : 0;
    $stmt->execute([$qr_code, $admin_id, $admin_id, $is_super_admin]);
    
    if ($student = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode([
            'valid' => true,
            'student_id' => $student['tbl_student_id'],
            'student_name' => $student['student_name'],
            'course_section' => $student['course_section'],
            'qr_code' => $student['generated_code']
        ]);
    } else {
        // Try searching by different methods
        $stmt = $conn->prepare("
            SELECT s.tbl_student_id, s.student_name, s.course_section, s.generated_code
            FROM tbl_student s
            WHERE s.generated_code LIKE ? 
            OR s.qr_code LIKE ?
            OR s.tbl_student_id = ?
        ");
        $stmt->execute(["%$qr_code%", "%$qr_code%", $qr_code]);
        
        if ($student = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo json_encode([
                'valid' => true,
                'student_id' => $student['tbl_student_id'],
                'student_name' => $student['student_name'],
                'course_section' => $student['course_section'],
                'qr_code' => $student['generated_code']
            ]);
        } else {
            echo json_encode([
                'valid' => false, 
                'message' => 'Student not found. QR Code: ' . $qr_code
            ]);
        }
    }
} catch (PDOException $e) {
    error_log("Database error in validate-student: " . $e->getMessage());
    echo json_encode(['valid' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
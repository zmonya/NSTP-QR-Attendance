<?php
session_start();

// Ensure we're returning JSON
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

include('../conn/conn.php');

$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Log received data for debugging
    error_log("Received POST data: " . print_r($_POST, true));
    
    $studentName = isset($_POST['student_name']) ? trim($_POST['student_name']) : '';
    $courseSection = isset($_POST['course_section']) ? trim($_POST['course_section']) : '';
    $generatedCode = isset($_POST['generated_code']) ? trim($_POST['generated_code']) : '';
    
    if (empty($studentName)) {
        throw new Exception('Student name is required');
    }
    
    if (empty($generatedCode)) {
        throw new Exception('QR code is required. Please generate QR code first.');
    }

    // Determine course section based on user role
    $user_role = $_SESSION['role'] ?? 'admin';
    
    if ($user_role === 'super_admin') {
        // Super admin can specify any section
        if (empty($courseSection)) {
            throw new Exception('Course section is required for super admin');
        }
        $finalCourseSection = $courseSection;
    } else {
        // Regular admin uses their assigned section
        $stmt = $conn->prepare("SELECT assigned_section FROM tbl_users WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        $assignedSection = null;
        if ($admin && !empty($admin['assigned_section'])) {
            $assignedSection = $admin['assigned_section'];
        } else {
            // Try alternative table
            $stmt = $conn->prepare("
                SELECT course_section 
                FROM tbl_admin_sections 
                WHERE user_id = ? 
                ORDER BY assigned_at ASC 
                LIMIT 1
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $assignedSection = $stmt->fetchColumn();
        }
        
        if (!$assignedSection) {
            throw new Exception('You are not assigned to any section. Please contact super admin.');
        }
        
        $finalCourseSection = $assignedSection;
        
        // Use form value if provided and admin has permission
        if (!empty($courseSection)) {
            $finalCourseSection = $courseSection;
        }
    }

    // Check if QR code already exists (should be unique)
    $checkStmt = $conn->prepare("SELECT COUNT(*) FROM tbl_student WHERE generated_code = ?");
    $checkStmt->execute([$generatedCode]);
    if ($checkStmt->fetchColumn() > 0) {
        throw new Exception('QR code already exists. Please generate a new one.');
    }

    // Insert student
    $stmt = $conn->prepare("
        INSERT INTO tbl_student (student_name, course_section, generated_code, created_by) 
        VALUES (?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([$studentName, $finalCourseSection, $generatedCode, $_SESSION['user_id']]);
    
    if (!$result) {
        throw new Exception('Failed to insert student into database');
    }
    
    $studentId = $conn->lastInsertId();

    $response['success'] = true;
    $response['message'] = 'Student added successfully!';
    $response['data'] = [
        'student_id' => $studentId,
        'student_name' => $studentName,
        'course_section' => $finalCourseSection,
        'generated_code' => $generatedCode
    ];

} catch (Exception $e) {
    error_log("Error in add-student.php: " . $e->getMessage());
    $response['message'] = $e->getMessage();
}

// Ensure no extra output
ob_clean();
echo json_encode($response);
exit();
?>
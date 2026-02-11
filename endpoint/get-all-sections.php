<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

include('../conn/conn.php');

try {
    // Get all unique sections from students
    $stmt = $conn->prepare("SELECT DISTINCT course_section FROM tbl_student WHERE course_section IS NOT NULL AND course_section != '' ORDER BY course_section");
    $stmt->execute();
    $studentSections = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get all assigned sections
    $stmt = $conn->prepare("SELECT DISTINCT course_section FROM tbl_admin_sections ORDER BY course_section");
    $stmt->execute();
    $assignedSections = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get all sections from both tables
    $allSections = array_unique(array_merge($studentSections, $assignedSections));
    sort($allSections);
    
    echo json_encode([
        'success' => true,
        'sections' => $allSections,
        'studentSections' => $studentSections,
        'assignedSections' => $assignedSections
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
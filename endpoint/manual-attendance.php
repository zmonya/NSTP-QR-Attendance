<?php
session_start();
require_once '../conn/conn.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$student_id = $_POST['student_id'] ?? '';
$time_in = $_POST['time_in'] ?? '';
$notes = $_POST['notes'] ?? '';

if (empty($student_id)) {
    echo json_encode(['success' => false, 'message' => 'Student is required']);
    exit();
}

try {
    // Validate student exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM tbl_student WHERE tbl_student_id = ?");
    $stmt->execute([$student_id]);
    
    if ($stmt->fetchColumn() == 0) {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        exit();
    }
    
    // Use current time if not provided
    if (empty($time_in)) {
        $time_in = date('Y-m-d H:i:s');
    } else {
        $time_in = date('Y-m-d H:i:s', strtotime($time_in));
    }
    
    // Check if already attended today
    $stmt = $conn->prepare("SELECT COUNT(*) FROM tbl_attendance 
                           WHERE tbl_student_id = ? AND DATE(time_in) = DATE(?)");
    $stmt->execute([$student_id, $time_in]);
    
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Already attended today']);
        exit();
    }
    
    // Determine status (8:00 AM cutoff)
    $attendance_time = new DateTime($time_in);
    $cutoff_time = new DateTime(date('Y-m-d', strtotime($time_in)) . ' 08:00:00');
    $status = $attendance_time > $cutoff_time ? 'Late' : 'On Time';
    
    // Insert record - check if notes column exists
    $columns = "tbl_student_id, time_in, status";
    $placeholders = "?, ?, ?";
    $params = [$student_id, $time_in, $status];
    
    // Add notes if the column exists (you'll add it with the ALTER TABLE above)
    // For now, check if notes is not empty
    if (!empty($notes)) {
        $columns .= ", notes";
        $placeholders .= ", ?";
        $params[] = $notes;
    }
    
    $sql = "INSERT INTO tbl_attendance ($columns) VALUES ($placeholders)";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    
    echo json_encode([
        'success' => true,
        'message' => 'Manual attendance recorded successfully'
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
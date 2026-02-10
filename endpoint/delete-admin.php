<?php
session_start();
require_once './conn/conn.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'] ?? '';
    
    if (empty($user_id)) {
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        exit();
    }
    
    // Prevent deleting yourself
    if ($user_id == $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'You cannot delete your own account']);
        exit();
    }
    
    // Check if user exists and is not a super admin
    $checkStmt = $conn->prepare("SELECT role FROM tbl_users WHERE user_id = ?");
    $checkStmt->execute([$user_id]);
    
    if ($checkStmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }
    
    $user = $checkStmt->fetch();
    if ($user['role'] === 'super_admin') {
        echo json_encode(['success' => false, 'message' => 'Cannot delete super administrator']);
        exit();
    }
    
    // Delete user
    $stmt = $conn->prepare("DELETE FROM tbl_users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Administrator deleted successfully'
    ]);
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
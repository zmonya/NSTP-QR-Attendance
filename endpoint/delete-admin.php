<?php
session_start();
require_once '../conn/conn.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_id = $_POST['admin_id'];
    
    // Prevent deleting yourself
    if ($admin_id == $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'You cannot delete your own account']);
        exit();
    }
    
    // Check if admin exists and is not a super admin
    $checkStmt = $conn->prepare("SELECT role FROM tbl_admin WHERE tbl_admin_id = ?");
    $checkStmt->execute([$admin_id]);
    
    if ($checkStmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Admin not found']);
        exit();
    }
    
    $admin = $checkStmt->fetch();
    if ($admin['role'] === 'super_admin') {
        echo json_encode(['success' => false, 'message' => 'Cannot delete super administrator']);
        exit();
    }
    
    // Delete admin
    $stmt = $conn->prepare("DELETE FROM tbl_admin WHERE tbl_admin_id = ?");
    $stmt->execute([$admin_id]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Administrator deleted successfully'
    ]);
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
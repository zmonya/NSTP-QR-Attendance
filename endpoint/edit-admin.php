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
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'];
    $status = $_POST['status'];
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    if (empty($full_name) || empty($username)) {
        echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
        exit();
    }
    
    // Check if username already exists (excluding current admin)
    $checkStmt = $conn->prepare("SELECT tbl_admin_id FROM tbl_admin WHERE username = ? AND tbl_admin_id != ?");
    $checkStmt->execute([$username, $admin_id]);
    
    if ($checkStmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Username already exists']);
        exit();
    }
    
    // Handle password update if provided
    if (!empty($password)) {
        if ($password !== $confirm_password) {
            echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
            exit();
        }
        
        if (strlen($password) < 8) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long']);
            exit();
        }
        
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Update with password
        $stmt = $conn->prepare("
            UPDATE tbl_admin 
            SET full_name = ?, username = ?, email = ?, role = ?, status = ?, password_hash = ?
            WHERE tbl_admin_id = ?
        ");
        
        $stmt->execute([$full_name, $username, $email, $role, $status, $password_hash, $admin_id]);
    } else {
        // Update without password
        $stmt = $conn->prepare("
            UPDATE tbl_admin 
            SET full_name = ?, username = ?, email = ?, role = ?, status = ?
            WHERE tbl_admin_id = ?
        ");
        
        $stmt->execute([$full_name, $username, $email, $role, $status, $admin_id]);
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Administrator account updated successfully'
    ]);
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
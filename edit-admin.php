<?php
session_start();
require_once './conn/conn.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Enable error reporting for debugging
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    $user_id = $_POST['user_id'] ?? '';
    
    if (empty($user_id)) {
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        exit();
    }
    
    // Check if this is a password-only update (from change password modal)
    $password_only_update = false;
    if (!isset($_POST['full_name']) || (isset($_POST['full_name']) && empty($_POST['full_name']))) {
        $password_only_update = true;
    }
    
    if (!$password_only_update) {
        // Normal update - get all fields
        $full_name = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'admin';
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validate inputs for normal update
        if (empty($full_name) || empty($username) || empty($email)) {
            echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
            exit();
        }
        
        // Check if username already exists (excluding current user)
        $checkStmt = $conn->prepare("SELECT user_id FROM tbl_users WHERE username = ? AND user_id != ?");
        $checkStmt->execute([$username, $user_id]);
        
        if ($checkStmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'Username already exists']);
            exit();
        }
        
        // Check if email already exists (excluding current user)
        $checkStmt = $conn->prepare("SELECT user_id FROM tbl_users WHERE email = ? AND user_id != ?");
        $checkStmt->execute([$email, $user_id]);
        
        if ($checkStmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'Email already exists']);
            exit();
        }
    } else {
        // Password-only update - get only password fields
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Fetch current user info
        $stmt = $conn->prepare("SELECT full_name, username, email, role FROM tbl_users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit();
        }
        
        $full_name = $user['full_name'];
        $username = $user['username'];
        $email = $user['email'];
        $role = $user['role'];
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
            UPDATE tbl_users 
            SET full_name = ?, username = ?, email = ?, role = ?, password_hash = ?, updated_at = CURRENT_TIMESTAMP
            WHERE user_id = ?
        ");
        
        $stmt->execute([$full_name, $username, $email, $role, $password_hash, $user_id]);
    } else {
        // Update without password
        $stmt = $conn->prepare("
            UPDATE tbl_users 
            SET full_name = ?, username = ?, email = ?, role = ?, updated_at = CURRENT_TIMESTAMP
            WHERE user_id = ?
        ");
        
        $stmt->execute([$full_name, $username, $email, $role, $user_id]);
    }
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true, 
            'message' => $password_only_update ? 'Password changed successfully' : 'Administrator account updated successfully'
        ]);
    } else {
        echo json_encode([
            'success' => true, 
            'message' => 'No changes were made'
        ]);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
<?php
session_start();
require_once './conn/conn.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];
    
    // Validate inputs
    if (empty($full_name) || empty($username) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
        exit();
    }
    
    if ($password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
        exit();
    }
    
    if (strlen($password) < 8) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long']);
        exit();
    }
    
    // Check if username already exists
    $checkStmt = $conn->prepare("SELECT user_id FROM tbl_users WHERE username = ?");
    $checkStmt->execute([$username]);
    
    if ($checkStmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Username already exists']);
        exit();
    }
    
    // Check if email already exists
    $checkStmt = $conn->prepare("SELECT user_id FROM tbl_users WHERE email = ?");
    $checkStmt->execute([$email]);
    
    if ($checkStmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit();
    }
    
    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $created_by = $_SESSION['user_id'];
    
    // Insert new admin into tbl_users
    try {
        $stmt = $conn->prepare("
            INSERT INTO tbl_users (full_name, username, email, password_hash, role, created_by) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$full_name, $username, $email, $password_hash, $role, $created_by]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Administrator account created successfully'
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error creating admin: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
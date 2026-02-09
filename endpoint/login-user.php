<?php
session_start();
require_once '../conn/conn.php';

// Set JSON header
header('Content-Type: application/json');

// Initialize response
$response = ['success' => false, 'message' => ''];

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit();
}

// Get and sanitize input
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Validate inputs
if (empty($username) || empty($password)) {
    $response['message'] = 'Username and password are required';
    echo json_encode($response);
    exit();
}

try {
    // Find user by username or email
    $stmt = $conn->prepare("
        SELECT user_id, username, email, password_hash, full_name, role 
        FROM tbl_users 
        WHERE username = :username OR email = :username
    ");
    
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $response['message'] = 'Invalid username or password';
        echo json_encode($response);
        exit();
    }
    
    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        $response['message'] = 'Invalid username or password';
        echo json_encode($response);
        exit();
    }
    
    // Set session variables
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role'] = $user['role'];
    
    $response['success'] = true;
    $response['message'] = 'Login successful';
    $response['redirect'] = 'index.php';
    
} catch (PDOException $e) {
    $response['message'] = 'Login failed: ' . $e->getMessage();
}

echo json_encode($response);
?>

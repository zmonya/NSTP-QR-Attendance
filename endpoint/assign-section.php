<?php
session_start();
header('Content-Type: application/json');

// Only super admin can assign sections
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

include('../conn/conn.php');

$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $courseSection = isset($_POST['course_section']) ? trim($_POST['course_section']) : '';

    if ($userId <= 0) {
        throw new Exception('Invalid admin ID');
    }

    if (empty($courseSection)) {
        throw new Exception('Course section is required');
    }

    // Check if user exists and is admin (not super_admin)
    $stmt = $conn->prepare("SELECT role FROM tbl_users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('Admin not found');
    }

    if ($user['role'] === 'super_admin') {
        throw new Exception('Cannot assign sections to super admins');
    }

    // Check if assignment already exists
    $stmt = $conn->prepare("SELECT * FROM tbl_admin_sections WHERE user_id = ? AND course_section = ?");
    $stmt->execute([$userId, $courseSection]);
    
    if ($stmt->fetch()) {
        throw new Exception('This section is already assigned to this admin');
    }

    // Start transaction
    $conn->beginTransaction();

    // Insert assignment
    $stmt = $conn->prepare("INSERT INTO tbl_admin_sections (user_id, course_section, assigned_by) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $courseSection, $_SESSION['user_id']]);

    // Get the first assigned section for this admin
    $stmt = $conn->prepare("SELECT course_section FROM tbl_admin_sections WHERE user_id = ? ORDER BY assigned_at ASC LIMIT 1");
    $stmt->execute([$userId]);
    $firstSection = $stmt->fetchColumn();
    
    // Update assigned_section field in tbl_users
    $stmt = $conn->prepare("UPDATE tbl_users SET assigned_section = ? WHERE user_id = ?");
    $stmt->execute([$firstSection, $userId]);

    $conn->commit();

    $response['success'] = true;
    $response['message'] = 'Section assigned successfully!';
    $response['data'] = [
        'assignment_id' => $conn->lastInsertId(),
        'admin_id' => $userId,
        'course_section' => $courseSection,
        'assigned_by' => $_SESSION['user_id']
    ];

} catch (Exception $e) {
    $conn->rollBack();
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
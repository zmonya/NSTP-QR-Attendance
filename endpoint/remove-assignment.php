<?php
session_start();
header('Content-Type: application/json');

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

    $assignmentId = isset($_POST['assignment_id']) ? intval($_POST['assignment_id']) : 0;

    if ($assignmentId <= 0) {
        throw new Exception('Invalid assignment ID');
    }

    // Get assignment details before deleting
    $stmt = $conn->prepare("SELECT user_id FROM tbl_admin_sections WHERE admin_section_id = ?");
    $stmt->execute([$assignmentId]);
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$assignment) {
        throw new Exception('Assignment not found');
    }

    $userId = $assignment['user_id'];

    // Start transaction
    $conn->beginTransaction();

    // Delete assignment
    $stmt = $conn->prepare("DELETE FROM tbl_admin_sections WHERE admin_section_id = ?");
    $stmt->execute([$assignmentId]);

    // Update assigned_section if there are still assignments
    $stmt = $conn->prepare("SELECT course_section FROM tbl_admin_sections WHERE user_id = ? ORDER BY assigned_at ASC LIMIT 1");
    $stmt->execute([$userId]);
    $firstSection = $stmt->fetchColumn();

    if ($firstSection) {
        $stmt = $conn->prepare("UPDATE tbl_users SET assigned_section = ? WHERE user_id = ?");
        $stmt->execute([$firstSection, $userId]);
    } else {
        $stmt = $conn->prepare("UPDATE tbl_users SET assigned_section = NULL WHERE user_id = ?");
        $stmt->execute([$userId]);
    }

    $conn->commit();

    $response['success'] = true;
    $response['message'] = 'Assignment removed successfully';

} catch (Exception $e) {
    $conn->rollBack();
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
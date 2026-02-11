<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

include('../conn/conn.php');

$userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid admin ID']);
    exit();
}

try {
    // Get admin's assigned sections
    $stmt = $conn->prepare("
        SELECT a.*, u.username, u.full_name, 
               creator.username as assigned_by_name,
               creator.full_name as assigned_by_fullname
        FROM tbl_admin_sections a
        LEFT JOIN tbl_users u ON a.user_id = u.user_id
        LEFT JOIN tbl_users creator ON a.assigned_by = creator.user_id
        WHERE a.user_id = ?
        ORDER BY a.assigned_at DESC
    ");
    $stmt->execute([$userId]);
    $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'sections' => $sections
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
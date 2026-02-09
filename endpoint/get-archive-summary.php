<?php
// Include database connection
require_once '../conn/conn.php';

// Set JSON header
header('Content-Type: application/json');

try {
    // Create archive table if it doesn't exist
    $createTableSQL = "CREATE TABLE IF NOT EXISTS tbl_attendance_archive (
        tbl_attendance_archive_id INT AUTO_INCREMENT PRIMARY KEY,
        tbl_attendance_id INT NOT NULL,
        tbl_student_id INT NOT NULL,
        time_in TIMESTAMP NOT NULL,
        archived_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_student_id (tbl_student_id),
        INDEX idx_time_in (time_in),
        INDEX idx_archived_date (archived_date)
    )";
    
    $conn->exec($createTableSQL);
    
    // Get summary statistics
    $summary = [];
    
    // Total archived records
    $countSQL = "SELECT COUNT(*) as total_archived FROM tbl_attendance_archive";
    $stmt = $conn->prepare($countSQL);
    $stmt->execute();
    $summary['total_archived'] = $stmt->fetchColumn();
    
    // Date range of archived records
    $rangeSQL = "SELECT 
        MIN(DATE(time_in)) as earliest_date,
        MAX(DATE(time_in)) as latest_date,
        COUNT(DISTINCT DATE(time_in)) as unique_days
    FROM tbl_attendance_archive";
    
    $stmt = $conn->prepare($rangeSQL);
    $stmt->execute();
    $rangeData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $summary['earliest_date'] = $rangeData['earliest_date'];
    $summary['latest_date'] = $rangeData['latest_date'];
    $summary['unique_days'] = $rangeData['unique_days'];
    
    // Records per day
    $dailySQL = "SELECT 
        DATE(time_in) as attendance_date,
        COUNT(*) as record_count
    FROM tbl_attendance_archive
    GROUP BY DATE(time_in)
    ORDER BY attendance_date DESC
    LIMIT 30";
    
    $stmt = $conn->prepare($dailySQL);
    $stmt->execute();
    $summary['daily_breakdown'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Current active attendance records
    $activeSQL = "SELECT COUNT(*) as active_records FROM tbl_attendance";
    $stmt = $conn->prepare($activeSQL);
    $stmt->execute();
    $summary['active_records'] = $stmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'summary' => $summary
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error getting archive summary: ' . $e->getMessage()
    ]);
}
?>

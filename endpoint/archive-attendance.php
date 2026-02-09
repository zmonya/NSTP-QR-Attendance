<?php
// Include database connection
require_once '../conn/conn.php';

// Set JSON header
header('Content-Type: application/json');

// Get input data
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($input['archive_date'])) {
    echo json_encode(['success' => false, 'message' => 'Archive date is required']);
    exit;
}

$archiveDate = $input['archive_date'];

try {
    // Start transaction
    $conn->beginTransaction();
    
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
    
    // Get records to archive (records from the specified date and earlier)
    $selectSQL = "SELECT * FROM tbl_attendance 
                  WHERE DATE(time_in) <= :archive_date 
                  ORDER BY time_in ASC";
    
    $stmt = $conn->prepare($selectSQL);
    $stmt->bindParam(':archive_date', $archiveDate);
    $stmt->execute();
    $recordsToArchive = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $archivedCount = 0;
    
    if (count($recordsToArchive) > 0) {
        // Insert into archive table
        $insertSQL = "INSERT INTO tbl_attendance_archive 
                     (tbl_attendance_id, tbl_student_id, time_in) 
                     VALUES (:attendance_id, :student_id, :time_in)";
        
        $insertStmt = $conn->prepare($insertSQL);
        
        // Delete from main table
        $deleteSQL = "DELETE FROM tbl_attendance 
                     WHERE tbl_attendance_id = :attendance_id";
        
        $deleteStmt = $conn->prepare($deleteSQL);
        
        foreach ($recordsToArchive as $record) {
            // Archive the record
            $insertStmt->bindParam(':attendance_id', $record['tbl_attendance_id']);
            $insertStmt->bindParam(':student_id', $record['tbl_student_id']);
            $insertStmt->bindParam(':time_in', $record['time_in']);
            $insertStmt->execute();
            
            // Delete from main table
            $deleteStmt->bindParam(':attendance_id', $record['tbl_attendance_id']);
            $deleteStmt->execute();
            
            $archivedCount++;
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Successfully archived $archivedCount attendance records",
        'archived_count' => $archivedCount,
        'archive_date' => $archiveDate
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => 'Error archiving attendance: ' . $e->getMessage()
    ]);
}
?>

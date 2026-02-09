<?php
// Include database connection
require_once '../conn/conn.php';

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="attendance_report_' . date('Y-m-d_H-i-s') . '.xls"');
header('Cache-Control: max-age=0');

// Query to get attendance data with student information
$query = "SELECT 
    tbl_attendance.tbl_attendance_id as 'ID',
    tbl_student.student_name as 'Student Name',
    tbl_student.course_section as 'Course & Section',
    tbl_attendance.time_in as 'Time In',
    DATE(tbl_attendance.time_in) as 'Date',
    TIME(tbl_attendance.time_in) as 'Time'
FROM tbl_attendance 
LEFT JOIN tbl_student ON tbl_student.tbl_student_id = tbl_attendance.tbl_student_id
ORDER BY tbl_attendance.time_in DESC";

$stmt = $conn->prepare($query);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Start Excel content
echo '<table border="1">';
echo '<tr>';
echo '<th colspan="6" style="background-color: #4CAF50; color: white; font-size: 16px; font-weight: bold; text-align: center;">';
echo 'ATTENDANCE REPORT - ' . date('F j, Y');
echo '</th>';
echo '</tr>';
echo '<tr></tr>'; // Empty row

// Column headers
echo '<tr style="background-color: #f2f2f2; font-weight: bold;">';
echo '<th style="width: 50px;">ID</th>';
echo '<th style="width: 200px;">Student Name</th>';
echo '<th style="width: 150px;">Course & Section</th>';
echo '<th style="width: 100px;">Date</th>';
echo '<th style="width: 100px;">Time</th>';
echo '<th style="width: 150px;">Time In</th>';
echo '</tr>';

// Data rows
if (count($results) > 0) {
    foreach ($results as $row) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['ID']) . '</td>';
        echo '<td>' . htmlspecialchars($row['Student Name']) . '</td>';
        echo '<td>' . htmlspecialchars($row['Course & Section']) . '</td>';
        echo '<td>' . htmlspecialchars($row['Date']) . '</td>';
        echo '<td>' . htmlspecialchars($row['Time']) . '</td>';
        echo '<td>' . htmlspecialchars($row['Time In']) . '</td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="6" style="text-align: center; font-style: italic;">No attendance records found</td></tr>';
}

echo '</table>';
?>

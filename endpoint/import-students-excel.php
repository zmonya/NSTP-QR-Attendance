<?php
include("../conn/conn.php");
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'imported' => 0];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file'];
    
    // Validate file
    $allowedTypes = ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
    if (!in_array($file['type'], $allowedTypes)) {
        $response['message'] = 'Invalid file type. Please upload an Excel file (.xlsx or .xls)';
        echo json_encode($response);
        exit;
    }
    
    // Check file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        $response['message'] = 'File size too large. Maximum 5MB allowed.';
        echo json_encode($response);
        exit;
    }
    
    try {
        // Load the Excel file
        $spreadsheet = IOFactory::load($file['tmp_name']);
        $worksheet = $spreadsheet->getActiveSheet();
        
        $importedCount = 0;
        $errors = [];
        
        // Start from row 2 (assuming row 1 has headers)
        foreach ($worksheet->getRowIterator(2) as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            
            $data = [];
            foreach ($cellIterator as $cell) {
                $data[] = $cell->getValue();
            }
            
            // Expected columns: A=Name, B=Course&Section
            if (count($data) >= 2) {
                $studentName = trim($data[0]);
                $courseSection = trim($data[1]);
                
                if (!empty($studentName) && !empty($courseSection)) {
                    // Generate unique code
                    $generatedCode = uniqid('STU_', true);
                    
                    // Check if student already exists
                    $checkStmt = $conn->prepare("SELECT COUNT(*) FROM tbl_student WHERE student_name = ? AND course_section = ?");
                    $checkStmt->execute([$studentName, $courseSection]);
                    
                    if ($checkStmt->fetchColumn() == 0) {
                        // Insert new student
                        $stmt = $conn->prepare("INSERT INTO tbl_student (student_name, course_section, generated_code) VALUES (?, ?, ?)");
                        $stmt->execute([$studentName, $courseSection, $generatedCode]);
                        $importedCount++;
                    }
                }
            }
        }
        
        $response['success'] = true;
        $response['message'] = "Successfully imported {$importedCount} students.";
        $response['imported'] = $importedCount;
        
    } catch (Exception $e) {
        $response['message'] = 'Error processing Excel file: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'No file uploaded or invalid request.';
}

echo json_encode($response);
?>

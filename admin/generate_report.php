<?php
ob_start();
session_start();
require_once('../vendor/autoload.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$database = "flexifit_db";

// Verify admin access
if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    header("Location: ../index.php");
    exit();
}

// Create connection
try {
    $conn = new mysqli($servername, $username, $password, $database);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    ob_end_clean();
    die("Database connection error: " . $e->getMessage());
}

// Create new Spreadsheet object
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set document properties
$spreadsheet->getProperties()
    ->setCreator('FlexiFit Gym System')
    ->setTitle('Analytics Report')
    ->setSubject('Gym Analytics');

// Set report title
$sheet->setCellValue('A1', $_POST['reportTitle']);
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->mergeCells('A1:F1');

// Add date if range is specified
$currentRow = 3;
if (!empty($_POST['startDate'])) {
    $dateRange = "Date Range: " . $_POST['startDate'];
    if (!empty($_POST['endDate'])) {
        $dateRange .= " to " . $_POST['endDate'];
    }
    $sheet->setCellValue('A'.$currentRow, $dateRange);
    $currentRow += 2;
}

// Include selected analytics
if (in_array('members', $_POST['analytics'])) {
    $currentRow = include_excel_section($sheet, $conn, 'Membership Overview', get_membership_data($conn, $_POST), $currentRow);
}

if (in_array('status', $_POST['analytics'])) {
    $currentRow = include_excel_section($sheet, $conn, 'Membership Status', get_membership_status_data($conn, $_POST), $currentRow);
}

if (in_array('gender', $_POST['analytics'])) {
    $currentRow = include_excel_section($sheet, $conn, 'Gender Distribution', get_gender_data($conn, $_POST), $currentRow);
}

if (in_array('equipment', $_POST['analytics'])) {
    $currentRow = include_excel_section($sheet, $conn, 'Most Booked Equipment', get_equipment_data($conn, $_POST), $currentRow);
}

if (in_array('trainers', $_POST['analytics'])) {
    $currentRow = include_excel_section($sheet, $conn, 'Highest Rated Trainers', get_trainer_data($conn, $_POST), $currentRow);
}

if (in_array('content', $_POST['analytics'])) {
    $currentRow = include_excel_section($sheet, $conn, 'Highest Rated Content', get_content_data($conn, $_POST), $currentRow);
}

if (in_array('trainer_growth', $_POST['analytics'])) {
    $currentRow = include_excel_section($sheet, $conn, 'Trainer Growth', get_trainer_growth_data($conn, $_POST), $currentRow, true);
}

if (in_array('equipment_growth', $_POST['analytics'])) {
    $currentRow = include_excel_section($sheet, $conn, 'Equipment Additions', get_equipment_growth_data($conn, $_POST), $currentRow, true);
}

if (in_array('content_growth', $_POST['analytics'])) {
    $currentRow = include_excel_section($sheet, $conn, 'Content Additions', get_content_growth_data($conn, $_POST), $currentRow, true);
}

// Add notes if provided
if (!empty($_POST['reportNotes'])) {
    $sheet->setCellValue('A'.$currentRow, 'Notes:');
    $sheet->getStyle('A'.$currentRow)->getFont()->setBold(true);
    $currentRow++;
    $sheet->setCellValue('A'.$currentRow, $_POST['reportNotes']);
    $sheet->mergeCells('A'.$currentRow.':F'.$currentRow);
    $sheet->getStyle('A'.$currentRow)->getAlignment()->setWrapText(true);
}

// Auto-size columns
foreach(range('A','F') as $columnID) {
    $sheet->getColumnDimension($columnID)->setAutoSize(true);
}

// Generate and output Excel file
$filename = 'flexifit_report_'.date('Ymd').'.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="'.$filename.'"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();

// ==================== HELPER FUNCTIONS ====================

function include_excel_section($sheet, $conn, $title, $data, $startRow, $isTabular = false) {
    $currentRow = $startRow;
    
    // Add section title
    $sheet->setCellValue('A'.$currentRow, $title);
    $sheet->getStyle('A'.$currentRow)->getFont()->setBold(true)->setSize(14);
    $currentRow++;
    
    if ($isTabular) {
        // For tabular data (like growth data)
        if (!empty($data)) {
            // Add headers
            $headers = array_keys($data[0]);
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col.$currentRow, ucwords(str_replace('_', ' ', $header)));
                $sheet->getStyle($col.$currentRow)->getFont()->setBold(true);
                $col++;
            }
            $currentRow++;
            
            // Add data rows
            foreach ($data as $row) {
                $col = 'A';
                foreach ($row as $value) {
                    $sheet->setCellValue($col.$currentRow, $value);
                    $col++;
                }
                $currentRow++;
            }
        } else {
            $sheet->setCellValue('A'.$currentRow, 'No data found for this section');
            $currentRow++;
        }
    } else {
        // For key-value data
        foreach ($data as $label => $value) {
            $sheet->setCellValue('A'.$currentRow, $label);
            $sheet->setCellValue('B'.$currentRow, $value);
            $currentRow++;
        }
    }
    
    $currentRow += 2; // Add some space between sections
    return $currentRow;
}

function get_membership_data($conn, $params) {
    $dateCondition = build_date_condition($conn, 'm.start_date', $params);
    
    $data = [];
    
    // Total members
    $query = "SELECT COUNT(*) AS total FROM members m WHERE 1 $dateCondition";
    $result = $conn->query($query);
    $data['Total Members'] = $result->fetch_assoc()['total'];
    
    // Active members
    $query = "SELECT COUNT(*) AS total FROM members m WHERE membership_status = 'active' $dateCondition";
    $result = $conn->query($query);
    $data['Active Members'] = $result->fetch_assoc()['total'];
    
    return $data;
}

function get_membership_status_data($conn, $params) {
    $dateCondition = build_date_condition($conn, 'start_date', $params);
    
    $query = "SELECT membership_status, COUNT(*) AS count 
              FROM members 
              WHERE 1 $dateCondition
              GROUP BY membership_status";
    
    $result = $conn->query($query);
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[$row['membership_status']] = $row['count'];
    }
    return $data;
}

function get_gender_data($conn, $params) {
    $dateCondition = build_date_condition($conn, 'u.created_at', $params);
    
    $query = "SELECT u.gender, COUNT(*) AS count 
              FROM users u
              JOIN members m ON u.user_id = m.user_id
              WHERE 1 $dateCondition
              GROUP BY u.gender";
    
    $result = $conn->query($query);
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[ucfirst($row['gender'])] = $row['count'];
    }
    return $data;
}

function get_equipment_data($conn, $params) {
    $dateCondition = build_date_condition($conn, 's.date', $params);
    
    $query = "SELECT e.name, COUNT(s.inventory_id) AS bookings_count
              FROM schedules s
              JOIN equipment_inventory ei ON s.inventory_id = ei.inventory_id
              JOIN equipment e ON ei.equipment_id = e.equipment_id
              WHERE 1 $dateCondition
              GROUP BY s.inventory_id
              ORDER BY bookings_count DESC
              LIMIT 5";
    
    $result = $conn->query($query);
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[$row['name']] = $row['bookings_count'];
    }
    return $data;
}

function get_trainer_data($conn, $params) {
    $dateCondition = build_date_condition($conn, 'tr.review_date', $params);
    
    $query = "SELECT CONCAT(t.first_name, ' ', t.last_name) AS trainer_name, 
                     AVG(tr.rating) AS avg_rating, 
                     COUNT(tr.trainer_id) AS total_reviews
              FROM trainer_reviews tr
              JOIN trainers t ON tr.trainer_id = t.trainer_id
              WHERE 1 $dateCondition
              GROUP BY tr.trainer_id
              HAVING COUNT(tr.trainer_id) >= 3
              ORDER BY avg_rating DESC
              LIMIT 5";
    
    $result = $conn->query($query);
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[$row['trainer_name']] = "Rating: " . round($row['avg_rating'], 2) . " (Reviews: " . $row['total_reviews'] . ")";
    }
    return $data;
}

function get_content_data($conn, $params) {
    $dateCondition = build_date_condition($conn, 'r.review_date', $params);
    
    $query = "SELECT c.title, 
                     AVG(r.rating) AS avg_rating, 
                     COUNT(r.review_id) AS review_count
              FROM content c
              LEFT JOIN reviews r ON c.content_id = r.content_id
              WHERE 1 $dateCondition
              GROUP BY c.content_id
              HAVING COUNT(r.review_id) >= 3
              ORDER BY avg_rating DESC
              LIMIT 5";
    
    $result = $conn->query($query);
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[$row['title']] = "Rating: " . round($row['avg_rating'], 2) . " (Reviews: " . $row['review_count'] . ")";
    }
    return $data;
}

function get_trainer_growth_data($conn, $params) {
    $dateCondition = build_date_condition($conn, 'created_at', $params);
    
    $query = "SELECT DATE(created_at) AS date, COUNT(*) AS count 
              FROM trainers 
              WHERE 1 $dateCondition
              GROUP BY DATE(created_at)
              ORDER BY date";
    
    $result = $conn->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function get_equipment_growth_data($conn, $params) {
    $dateCondition = build_date_condition($conn, 'added_date', $params);
    
    $query = "SELECT DATE(added_date) AS date, COUNT(*) AS count 
              FROM equipment_inventory 
              WHERE 1 $dateCondition
              GROUP BY DATE(added_date)
              ORDER BY date";
    
    $result = $conn->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function get_content_growth_data($conn, $params) {
    $dateCondition = build_date_condition($conn, 'created_at', $params);
    
    $query = "SELECT DATE(created_at) AS date, COUNT(*) AS count 
              FROM content 
              WHERE 1 $dateCondition
              GROUP BY DATE(created_at)
              ORDER BY date";
    
    $result = $conn->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function build_date_condition($conn, $column, $params) {
    $condition = "";
    if (!empty($params['startDate'])) {
        $startDate = $conn->real_escape_string($params['startDate']);
        $condition = " AND $column >= '$startDate'";
        
        if (!empty($params['endDate'])) {
            $endDate = $conn->real_escape_string($params['endDate']);
            $condition .= " AND $column <= '$endDate'";
        }
    }
    return $condition;
}
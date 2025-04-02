<?php
ob_start();
session_start();
require_once('../vendor/autoload.php');

use TCPDF as TCPDF;

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

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('FlexiFit Gym System');
$pdf->SetAuthor('FlexiFit Admin');
$pdf->SetTitle('Analytics Report');
$pdf->SetSubject('Gym Analytics');

// Set default header data
$pdf->SetHeaderData('', 0, 'FlexiFit Analytics Report', '');

// Set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// Set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', 'B', 16);

// Report title
$pdf->Cell(0, 10, $_POST['reportTitle'], 0, 1, 'C');
$pdf->Ln(10);

// Set smaller font for content
$pdf->SetFont('helvetica', '', 12);

// Add date if range is specified
if (!empty($_POST['startDate'])) {
    $dateRange = "Date Range: " . $_POST['startDate'];
    if (!empty($_POST['endDate'])) {
        $dateRange .= " to " . $_POST['endDate'];
    }
    $pdf->Cell(0, 10, $dateRange, 0, 1);
    $pdf->Ln(5);
}

// Include selected analytics
if (in_array('members', $_POST['analytics'])) {
    include_pdf_section($pdf, $conn, 'Membership Overview', get_membership_data($conn, $_POST));
}

if (in_array('status', $_POST['analytics'])) {
    include_pdf_section($pdf, $conn, 'Membership Status', get_membership_status_data($conn, $_POST));
}

if (in_array('gender', $_POST['analytics'])) {
    include_pdf_section($pdf, $conn, 'Gender Distribution', get_gender_data($conn, $_POST));
}

if (in_array('equipment', $_POST['analytics'])) {
    include_pdf_table($pdf, $conn, 'Most Booked Equipment', get_equipment_data($conn, $_POST), ['Equipment', 'Bookings']);
}

if (in_array('trainers', $_POST['analytics'])) {
    include_pdf_table($pdf, $conn, 'Highest Rated Trainers', get_trainer_data($conn, $_POST), ['Trainer', 'Rating']);
}

if (in_array('content', $_POST['analytics'])) {
    include_pdf_table($pdf, $conn, 'Highest Rated Content', get_content_data($conn, $_POST), ['Content', 'Rating']);
}

if (in_array('trainer_growth', $_POST['analytics'])) {
    include_pdf_table($pdf, $conn, 'Trainer Growth', get_trainer_growth_data($conn, $_POST), ['Date', 'New Trainers']);
}

if (in_array('equipment_growth', $_POST['analytics'])) {
    include_pdf_table($pdf, $conn, 'Equipment Additions', get_equipment_growth_data($conn, $_POST), ['Date', 'New Equipment']);
}

if (in_array('content_growth', $_POST['analytics'])) {
    include_pdf_table($pdf, $conn, 'Content Additions', get_content_growth_data($conn, $_POST), ['Date', 'New Content']);
}

// Add notes if provided
if (!empty($_POST['reportNotes'])) {
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'I', 10);
    $pdf->MultiCell(0, 10, "Notes: " . $_POST['reportNotes']);
}

// Output PDF
$pdf->Output('flexifit_report_'.date('Ymd').'.pdf', 'D');
exit();

// ==================== HELPER FUNCTIONS ====================

function include_pdf_section($pdf, $conn, $title, $data) {
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, $title, 0, 1);
    $pdf->SetFont('helvetica', '', 12);
    
    foreach ($data as $label => $value) {
        $pdf->Cell(0, 10, "$label: $value", 0, 1);
    }
    $pdf->Ln(10);
}

function include_pdf_table($pdf, $conn, $title, $data, $headers) {
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, $title, 0, 1);
    $pdf->SetFont('helvetica', '', 12);
    
    // Calculate column widths
    $colWidths = array(90, 90); // Equal width for two columns
    
    // Table header
    $pdf->SetFillColor(200, 220, 255);
    foreach ($headers as $key => $header) {
        $pdf->Cell($colWidths[$key], 7, $header, 1, 0, 'C', 1);
    }
    $pdf->Ln();
    
    // Table data
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetTextColor(0);
    $fill = false;
    
    foreach ($data as $key => $value) {
        $pdf->Cell($colWidths[0], 6, $key, 'LR', 0, 'L', $fill);
        $pdf->Cell($colWidths[1], 6, $value, 'LR', 0, 'L', $fill);
        $pdf->Ln();
        $fill = !$fill;
    }
    $pdf->Cell(array_sum($colWidths), 0, '', 'T');
    $pdf->Ln(10);
}

// (Keep all your existing get_*_data() functions exactly as they were in the Excel version)
// (Keep build_date_condition() function exactly as it was)

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
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[$row['date']] = $row['count'];
    }
    return $data;
}

function get_equipment_growth_data($conn, $params) {
    $dateCondition = build_date_condition($conn, 'added_date', $params);
    
    $query = "SELECT DATE(added_date) AS date, COUNT(*) AS count 
              FROM equipment_inventory 
              WHERE 1 $dateCondition
              GROUP BY DATE(added_date)
              ORDER BY date";
    
    $result = $conn->query($query);
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[$row['date']] = $row['count'];
    }
    return $data;
}

function get_content_growth_data($conn, $params) {
    $dateCondition = build_date_condition($conn, 'created_at', $params);
    
    $query = "SELECT DATE(created_at) AS date, COUNT(*) AS count 
              FROM content 
              WHERE 1 $dateCondition
              GROUP BY DATE(created_at)
              ORDER BY date";
    
    $result = $conn->query($query);
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[$row['date']] = $row['count'];
    }
    return $data;
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
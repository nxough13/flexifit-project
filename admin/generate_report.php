<?php
// Start output buffering immediately with no whitespace before
ob_start();

session_start();
require_once('../vendor/autoload.php');

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$database = "flexifit_db";

// Verify admin access
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    ob_end_clean();
    header("Location: ../index.php");
    exit();
}

// Create connection with error handling
try {
    $conn = new mysqli($servername, $username, $password, $database);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    ob_end_clean();
    die("Database connection error: " . $e->getMessage());
}

// Include the TCPDF library
use TCPDF as TCPDF;

// Create PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('FlexiFit Gym System');
$pdf->SetAuthor('FlexiFit Admin');
$pdf->SetTitle('Analytics Report');
$pdf->SetSubject('Gym Analytics');

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
    include_report_section($pdf, $conn, 'Membership Overview', get_membership_data($conn, $_POST));
}

if (in_array('status', $_POST['analytics'])) {
    include_report_section($pdf, $conn, 'Membership Status', get_membership_status_data($conn, $_POST));
}

if (in_array('trainer_growth', $_POST['analytics'])) {
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Trainer Growth Over Time', 0, 1);
    $pdf->SetFont('helvetica', '', 12);
    
    $trainerData = get_trainer_growth_data($conn, $_POST);
    if (!empty($trainerData)) {
        $pdf->Ln(5);
        foreach ($trainerData as $row) {
            $pdf->Cell(0, 10, $row['date'] . ': ' . $row['count'] . ' new trainers', 0, 1);
        }
    } else {
        $pdf->Cell(0, 10, 'No trainer data found for selected period', 0, 1);
    }
}

if (in_array('equipment_growth', $_POST['analytics'])) {
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Equipment Additions Over Time', 0, 1);
    $pdf->SetFont('helvetica', '', 12);
    
    $equipmentData = get_equipment_growth_data($conn, $_POST);
    if (!empty($equipmentData)) {
        $pdf->Ln(5);
        foreach ($equipmentData as $row) {
            $pdf->Cell(0, 10, $row['date'] . ': ' . $row['count'] . ' new equipment', 0, 1);
        }
    } else {
        $pdf->Cell(0, 10, 'No equipment data found for selected period', 0, 1);
    }
}

// Add notes if provided
if (!empty($_POST['reportNotes'])) {
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'I', 10);
    $pdf->MultiCell(0, 10, "Notes: " . $_POST['reportNotes']);
}

// Clean buffer and output PDF
ob_end_clean();
$pdf->Output('flexifit_report_'.date('Ymd').'.pdf', 'D');
exit();

// ==================== HELPER FUNCTIONS ====================

function include_report_section($pdf, $conn, $title, $data) {
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, $title, 0, 1);
    $pdf->SetFont('helvetica', '', 12);
    
    foreach ($data as $label => $value) {
        $pdf->Cell(0, 10, "$label: $value", 0, 1);
    }
    $pdf->Ln(10);
}

function get_membership_data($conn, $params) {
    $dateCondition = build_date_condition($conn, 'm.start_date', $params);
    
    $data = [];
    
    // Total members
    $query = "SELECT COUNT(*) AS total FROM members m WHERE 1 $dateCondition";
    $result = $conn->query($query);
    $data['Total Members'] = $result->fetch_assoc()['total'];
    
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

function get_trainer_growth_data($conn, $params) {
    $dateCondition = build_date_condition($conn, 'created_at', $params);
    
    $query = "SELECT DATE(created_at) AS date, COUNT(*) AS count 
              FROM trainers 
              WHERE 1 $dateCondition
              GROUP BY DATE(created_at)";
    
    $result = $conn->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function get_equipment_growth_data($conn, $params) {
    $dateCondition = build_date_condition($conn, 'added_date', $params);
    
    $query = "SELECT DATE(added_date) AS date, COUNT(*) AS count 
              FROM equipment_inventory 
              WHERE 1 $dateCondition
              GROUP BY DATE(added_date)";
    
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

function generate_chart_image($data, $title, $width = 600, $height = 400) {
    $chartUrl = "https://quickchart.io/chart?width=$width&height=$height&chart=";
    $chartConfig = [
        'type' => 'line',
        'data' => [
            'labels' => array_column($data, 'date'),
            'datasets' => [[
                'label' => $title,
                'data' => array_column($data, 'count')
            ]]
        ]
    ];
    return $chartUrl . urlencode(json_encode($chartConfig));
}

?>

<?php ob_end_flush(); // At the end of file ?>
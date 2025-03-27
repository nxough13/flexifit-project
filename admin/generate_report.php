<?php
session_start();
require_once('../vendor/autoload.php');
require_once('../config.php');

// Verify admin access
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Include the TCPDF library
use TCPDF as TCPDF;

// Create new PDF document
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
if (!empty($_POST['startDate']) {
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
    include_report_section($pdf, $conn, 'Membership Status', get_status_data($conn, $_POST));
}

// [Add similar blocks for other analytics sections]

// Add notes if provided
if (!empty($_POST['reportNotes'])) {
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'I', 10);
    $pdf->MultiCell(0, 10, "Notes: " . $_POST['reportNotes']);
}

// Output the PDF
$pdf->Output('flexifit_report_'.date('Ymd').'.pdf', 'D');

// Helper functions
function include_report_section($pdf, $conn, $title, $data) {
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, $title, 0, 1);
    $pdf->SetFont('helvetica', '', 12);
    
    // Add your specific data formatting here
    // Example for membership data:
    foreach ($data as $label => $value) {
        $pdf->Cell(0, 10, "$label: $value", 0, 1);
    }
    
    $pdf->Ln(10);
}

function get_membership_data($conn, $params) {
    // Modify your queries to respect date ranges
    $dateCondition = "";
    if (!empty($params['startDate'])) {
        $startDate = $conn->real_escape_string($params['startDate']);
        $dateCondition = " AND m.start_date >= '$startDate'";
        
        if (!empty($params['endDate'])) {
            $endDate = $conn->real_escape_string($params['endDate']);
            $dateCondition .= " AND m.start_date <= '$endDate'";
        }
    }
    
    $data = [];
    
    // Total members
    $query = "SELECT COUNT(*) AS total FROM members WHERE 1 $dateCondition";
    $result = $conn->query($query);
    $data['Total Members'] = $result->fetch_assoc()['total'];
    
    // [Add other queries as needed]
    
    return $data;
}

// [Add similar functions for other data types]
?>
<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';
require_once('tcpdf/tcpdf.php');
checkLogin();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: unauthorized.php");
    exit();
}

// Get filter parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$employee_id = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0;

// Create new PDF document
$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Krishan Paint Center');
$pdf->SetAuthor('Krishan Paint Center');
$pdf->SetTitle('Attendance Report ' . date('Y-m-d'));

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// Set margins
$pdf->SetMargins(10, 10, 10);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Set font
$pdf->SetFont('helvetica', '', 10);

// Add a page
$pdf->AddPage('L', 'A4');

// Set header content
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'KRISHAN PAINT CENTER', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Attendance Report', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(0, 5, 'Period: ' . date('Y-m-d', strtotime($start_date)) . ' to ' . date('Y-m-d', strtotime($end_date)), 0, 1, 'C');
$pdf->Ln(5);

// Build WHERE clause
$where = "WHERE ea.attendance_date BETWEEN '$start_date' AND '$end_date'";
if ($employee_id) {
    $where .= " AND ea.employee_id = $employee_id";

    // Get employee name
    $query = "SELECT name FROM employees WHERE id = $employee_id";
    $result = Database::search($query);
    if ($emp = $result->fetch_assoc()) {
        $pdf->Cell(0, 5, 'Employee: ' . $emp['name'], 0, 1, 'C');
    }
}
$pdf->Ln(5);

// Get attendance data
$query = "SELECT ea.*, 
          e.name as employee_name,
          e.day_rate,
          e.overtime_rate
          FROM employee_attendance ea 
          LEFT JOIN employees e ON ea.employee_id = e.id 
          $where 
          ORDER BY ea.attendance_date, e.name";
$result = Database::search($query);

// Table header
$header = array('Date', 'Employee', 'Time In', 'Time Out', 'Status', 'OT Hours', 'Day Amount', 'OT Amount', 'Total');
$w = array(25, 45, 20, 20, 25, 20, 30, 30, 30);

// Colors for header
$pdf->SetFillColor(240, 240, 240);
$pdf->SetTextColor(0);
$pdf->SetFont('helvetica', 'B', 10);

// Print header
foreach ($header as $i => $h) {
    $pdf->Cell($w[$i], 7, $h, 1, 0, 'C', true);
}
$pdf->Ln();

// Reset font for data
$pdf->SetFont('helvetica', '', 9);
$pdf->SetFillColor(255, 255, 255);

// Data
$grand_total = 0;
$employee_totals = array();

while ($row = $result->fetch_assoc()) {
    $daily_rate = $row['day_rate'];
    $ot_amount = $row['ot_hours'] * $row['overtime_rate'];

    // Calculate day amount based on status
    $day_amount = 0;
    switch ($row['status']) {
        case 'present':
            $day_amount = $daily_rate;
            break;
        case 'half-day':
            $day_amount = $daily_rate / 2;
            break;
    }

    $total = $day_amount + $ot_amount;
    $grand_total += $total;

    // Track employee totals
    if (!isset($employee_totals[$row['employee_name']])) {
        $employee_totals[$row['employee_name']] = array(
            'days' => 0,
            'ot_hours' => 0,
            'amount' => 0
        );
    }
    if ($row['status'] === 'present') {
        $employee_totals[$row['employee_name']]['days']++;
    } elseif ($row['status'] === 'half-day') {
        $employee_totals[$row['employee_name']]['days'] += 0.5;
    }
    $employee_totals[$row['employee_name']]['ot_hours'] += $row['ot_hours'];
    $employee_totals[$row['employee_name']]['amount'] += $total;

    $pdf->Cell($w[0], 6, date('Y-m-d', strtotime($row['attendance_date'])), 1);
    $pdf->Cell($w[1], 6, $row['employee_name'], 1);
    $pdf->Cell($w[2], 6, $row['time_in'] ? date('H:i', strtotime($row['time_in'])) : '-', 1, 0, 'C');
    $pdf->Cell($w[3], 6, $row['time_out'] ? date('H:i', strtotime($row['time_out'])) : '-', 1, 0, 'C');
    $pdf->Cell($w[4], 6, ucfirst($row['status']), 1, 0, 'C');
    $pdf->Cell($w[5], 6, formatOTHours($row['ot_hours']), 1, 0, 'R');
    $pdf->Cell($w[6], 6, number_format($day_amount, 2), 1, 0, 'R');
    $pdf->Cell($w[7], 6, number_format($ot_amount, 2), 1, 0, 'R');
    $pdf->Cell($w[8], 6, number_format($total, 2), 1, 0, 'R');
    $pdf->Ln();
}

// Summary section
$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(0, 7, 'Summary', 0, 1);
$pdf->SetFont('helvetica', '', 10);

foreach ($employee_totals as $employee => $totals) {
    $pdf->Cell(100, 6, $employee, 0);
    $pdf->Cell(50, 6, 'Days Worked: ' . number_format($totals['days'], 1), 0);
    $pdf->Cell(50, 6, 'OT Hours: ' . number_format($totals['ot_hours'], 2), 0);
    $pdf->Cell(0, 6, 'Total: Rs. ' . number_format($totals['amount'], 2), 0, 0, 'R');
    $pdf->Ln();
}

// Grand total
$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(0, 7, 'Grand Total: Rs. ' . number_format($grand_total, 2), 0, 1, 'R');

// Signature section
$pdf->Ln(20);
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(90, 6, '............................', 0, 0, 'C');
$pdf->Cell(90, 6, '............................', 0, 0, 'C');
$pdf->Cell(90, 6, '............................', 0, 0, 'C');
$pdf->Ln();
$pdf->Cell(90, 6, 'Prepared By', 0, 0, 'C');
$pdf->Cell(90, 6, 'Checked By', 0, 0, 'C');
$pdf->Cell(90, 6, 'Approved By', 0, 0, 'C');

// Output PDF
$pdf->Output('Attendance_Report_' . date('Y-m-d') . '.pdf', 'I');

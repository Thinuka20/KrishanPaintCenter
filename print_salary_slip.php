<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once('tcpdf/tcpdf.php');
require_once 'connection.php';

checkLogin();

$employee_id = (int)$_GET['id'];
$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

// Get employee details
$query = "SELECT * FROM employees WHERE id = $employee_id";
$result = Database::search($query);
$employee = $result->fetch_assoc();

// Calculate salary period
$start_date = date('Y-m-01', strtotime($month));
$end_date = date('Y-m-t', strtotime($month));

// Get attendance records with calculated amounts
$query = "SELECT *, 
          CASE 
            WHEN status = 'present' THEN 
                CASE 
                    WHEN working_hours >= 10 THEN {$employee['day_rate']}
                    ELSE (working_hours / 10) * {$employee['day_rate']}
                END
            WHEN status = 'half-day' THEN {$employee['day_rate']} / 2
            ELSE 0 
          END as day_amount,
          COALESCE(ot_hours * {$employee['overtime_rate']}, 0) as ot_amount
          FROM employee_attendance 
          WHERE employee_id = $employee_id 
          AND attendance_date BETWEEN '$start_date' AND '$end_date'
          ORDER BY attendance_date";
$attendance_result = Database::search($query);

// Calculate totals
$total_days = 0;
$total_ot_hours = 0;
$total_half_days = 0;
$total_absents = 0;
$total_leaves = 0;
$total_working_hours = 0;
$total_day_amount = 0;
$total_ot_amount = 0;

while ($row = $attendance_result->fetch_assoc()) {
    switch ($row['status']) {
        case 'present':
            $total_days++;
            break;
        case 'half-day':
            $total_half_days++;
            break;
        case 'absent':
            $total_absents++;
            break;
        case 'leave':
            $total_leaves++;
            break;
    }
    $total_working_hours += $row['working_hours'];
    $total_ot_hours += $row['ot_hours'];
    $total_day_amount += $row['day_amount'];
    $total_ot_amount += $row['ot_amount'];
}

$total_salary = $total_day_amount + $total_ot_amount;

class MYPDF extends TCPDF {
    public function Header() {
        // Logo
        $image_file = K_PATH_IMAGES.'logo.png';
        if(file_exists($image_file)) {
            $this->Image($image_file, 15, 10, 50, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        }
        
        // Company Details
        $this->SetFont('helvetica', 'B', 15);
        $this->Cell(0, 5, 'KRISHAN PAINT CENTER', 0, 1, 'R');
        $this->SetFont('helvetica', '', 9);
        $this->Cell(0, 5, 'Professional Auto Paint Services', 0, 1, 'R');
        $this->Cell(0, 5, '[Your Address]', 0, 1, 'R');
        $this->Cell(0, 5, 'Tel: [Your Phone] | Email: [Your Email]', 0, 1, 'R');
        
        // Line break
        $this->SetLineWidth(0.5);
        $this->Line(15, 35, 195, 35);
    }
}

// Create new PDF document
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Krishan Paint Center');
$pdf->SetTitle('Salary Slip - ' . $employee['name'] . ' - ' . date('F Y', strtotime($month)));

// Set margins
$pdf->SetMargins(15, 40, 15);
$pdf->SetAutoPageBreak(TRUE, 15);

// Add page
$pdf->AddPage();

// Title
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'SALARY SLIP', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(0, 5, 'For the period: ' . date('F Y', strtotime($month)), 0, 1, 'C');
$pdf->Ln(5);

// Employee Information Box
$pdf->SetFillColor(240, 240, 240);
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(0, 8, 'EMPLOYEE INFORMATION', 1, 1, 'L', true);
$pdf->SetFont('helvetica', '', 10);

// Create two column layout for employee info
$pdf->Cell(95, 7, 'Employee Name: ' . $employee['name'], 'LR', 0, 'L');
$pdf->Cell(85, 7, 'Employee ID: EMP-' . str_pad($employee_id, 4, '0', STR_PAD_LEFT), 'R', 1, 'L');

$pdf->Cell(95, 7, 'Designation: Staff', 'LR', 0, 'L');
$pdf->Cell(85, 7, 'Join Date: ' . date('Y-m-d', strtotime($employee['join_date'])), 'R', 1, 'L');

$pdf->Cell(95, 7, 'Daily Rate: ' . formatCurrency($employee['day_rate']), 'LRB', 0, 'L');
$pdf->Cell(85, 7, 'OT Rate: ' . formatCurrency($employee['overtime_rate']) . ' per hour', 'RB', 1, 'L');

$pdf->Ln(5);

// Attendance Summary Box
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(0, 8, 'ATTENDANCE SUMMARY', 1, 1, 'L', true);
$pdf->SetFont('helvetica', '', 10);

// Create table for attendance
$pdf->SetFillColor(255, 255, 255);
$pdf->Cell(50, 7, 'Full Days', 1, 0, 'L');
$pdf->Cell(40, 7, $total_days . ' days', 1, 0, 'R');
$pdf->Cell(50, 7, 'Half Days', 1, 0, 'L');
$pdf->Cell(40, 7, $total_half_days . ' days', 1, 1, 'R');

$pdf->Cell(50, 7, 'Absents', 1, 0, 'L');
$pdf->Cell(40, 7, $total_absents . ' days', 1, 0, 'R');
$pdf->Cell(50, 7, 'Leaves', 1, 0, 'L');
$pdf->Cell(40, 7, $total_leaves . ' days', 1, 1, 'R');

$pdf->Cell(50, 7, 'Total Working Hours', 1, 0, 'L');
$pdf->Cell(40, 7, number_format($total_working_hours, 2) . ' hrs', 1, 0, 'R');
$pdf->Cell(50, 7, 'Total OT Hours', 1, 0, 'L');
$pdf->Cell(40, 7, formatOTHours($total_ot_hours), 1, 1, 'R');

$pdf->Ln(5);

// Earnings Box
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(0, 8, 'EARNINGS', 1, 1, 'L', true);
$pdf->SetFont('helvetica', '', 10);

$pdf->Cell(140, 7, 'Regular Days Amount (Based on Working Hours)', 1, 0, 'L');
$pdf->Cell(40, 7, formatCurrency($total_day_amount), 1, 1, 'R');

$pdf->Cell(140, 7, 'OT Amount', 1, 0, 'L');
$pdf->Cell(40, 7, formatCurrency($total_ot_amount), 1, 1, 'R');

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(140, 8, 'Total Salary', 1, 0, 'L', true);
$pdf->Cell(40, 8, formatCurrency($total_salary), 1, 1, 'R', true);

$pdf->Ln(2);

function wrapAmountInWords($amount, $pdf) {
    $words = getAmountInWords($amount);
    
    $startX = $pdf->GetX();
    $startY = $pdf->GetY();
    
    $labelWidth = 40;
    $pdf->Cell($labelWidth, 7, 'Amount in words:', 0, 0, 'L');
    
    $remainingWidth = $pdf->GetPageWidth() - $startX - $labelWidth - 15;
    
    $pdf->Cell($remainingWidth, 7, $words, 0, 1, 'L');
    
    return 7;
}

$pdf->SetFont('helvetica', 'B', 10);
$height_used = wrapAmountInWords($total_salary, $pdf);

$pdf->Ln(15);

// Signature section
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(63, 6, '................................', 0, 0, 'C');
$pdf->Cell(63, 6, '................................', 0, 0, 'C');
$pdf->Cell(63, 6, '................................', 0, 1, 'C');

$pdf->Cell(63, 6, 'Prepared By', 0, 0, 'C');
$pdf->Cell(63, 6, 'Checked By', 0, 0, 'C');
$pdf->Cell(63, 6, 'Employee Signature', 0, 1, 'C');

// Add footer note
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->Cell(0, 5, 'This is a computer-generated document and does not require a signature', 0, 1, 'L');
$pdf->Cell(0, 5, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'L');

// Output PDF
$pdf->Output('Salary_Slip_' . $employee['name'] . '_' . $month . '.pdf', 'I');

// Helper function to convert number to words (keep existing functions)
function getAmountInWords($number) {
    $amount = round($number, 2);
    $whole = floor($amount);
    $fraction = round(($amount - $whole) * 100);
    
    // Convert the whole number to words
    $words = numberToWords($whole) . " RUPEES";
    
    // Add cents/fraction if exists
    if ($fraction > 0) {
        $words .= " AND " . numberToWords($fraction) . " CENTS";
    }
    
    return $words . " ONLY";
}

// Helper function to convert numbers to words
function numberToWords($num) {
    $ones = array(
        0 => "", 1 => "ONE", 2 => "TWO", 3 => "THREE", 4 => "FOUR", 
        5 => "FIVE", 6 => "SIX", 7 => "SEVEN", 8 => "EIGHT", 9 => "NINE"
    );
    $tens = array(
        0 => "", 1 => "TEN", 2 => "TWENTY", 3 => "THIRTY", 4 => "FORTY", 
        5 => "FIFTY", 6 => "SIXTY", 7 => "SEVENTY", 8 => "EIGHTY", 9 => "NINETY"
    );
    $teens = array(
        11 => "ELEVEN", 12 => "TWELVE", 13 => "THIRTEEN", 14 => "FOURTEEN", 
        15 => "FIFTEEN", 16 => "SIXTEEN", 17 => "SEVENTEEN", 18 => "EIGHTEEN", 19 => "NINETEEN"
    );
    
    if ($num == 0) {
        return "ZERO";
    }
    
    if ($num < 0) {
        return "NEGATIVE " . numberToWords(abs($num));
    }
    
    $words = "";
    
    if ($num >= 1000000) {
        $words .= numberToWords(floor($num/1000000)) . " MILLION ";
        $num %= 1000000;
    }
    
    if ($num >= 1000) {
        $words .= numberToWords(floor($num/1000)) . " THOUSAND ";
        $num %= 1000;
    }
    
    if ($num >= 100) {
        $words .= numberToWords(floor($num/100)) . " HUNDRED ";
        $num %= 100;
    }
    
    if ($num >= 11 && $num <= 19) {
        $words .= $teens[$num];
    } else {
        if ($num >= 20) {
            $words .= $tens[floor($num/10)];
            $num %= 10;
            if ($num > 0) {
                $words .= " " . $ones[$num];
            }
        } else {
            $words .= $ones[$num];
        }
    }
    
    return trim($words);
}
?>
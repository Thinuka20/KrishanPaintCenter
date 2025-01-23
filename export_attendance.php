<?php
// export_attendance.php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();

// Get filter parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$employee_id = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0;

// Build WHERE clause
$where = "WHERE ea.attendance_date BETWEEN '$start_date' AND '$end_date'";
if ($employee_id) {
    $where .= " AND ea.employee_id = $employee_id";
}

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

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="attendance_report_' . date('Y-m-d') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// Print Excel content
echo "<!DOCTYPE html>";
echo "<html>";
echo "<head>";
echo "<meta charset='utf-8'>";
echo "<style>
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        th { background-color: #f0f0f0; }
        td, th { padding: 5px; }
      </style>";
echo "</head>";
echo "<body>";

echo "<table border='1'>";
echo "<thead>";
echo "<tr>";
echo "<th colspan='9' class='text-center'>Attendance Report</th>";
echo "</tr>";
echo "<tr>";
echo "<th colspan='9' class='text-center'>Period: $start_date to $end_date</th>";
echo "</tr>";
echo "<tr>";
echo "<th>Date</th>";
echo "<th>Employee</th>";
echo "<th>Time In</th>";
echo "<th>Time Out</th>";
echo "<th>Status</th>";
echo "<th>OT Hours</th>";
echo "<th>Day Rate</th>";
echo "<th>OT Rate</th>";
echo "<th>Total</th>";
echo "</tr>";
echo "</thead>";
echo "<tbody>";

$grand_total = 0;
$employee_totals = [];

while ($row = $result->fetch_assoc()) {
    $daily_rate = $row['day_rate'];
    $ot_amount = $row['ot_hours'] * $row['overtime_rate'];
    
    // Calculate day amount based on status
    $day_amount = 0;
    switch($row['status']) {
        case 'present':
            $day_amount = $daily_rate;
            break;
        case 'half-day':
            $day_amount = $daily_rate / 2;
            break;
        case 'absent':
        case 'leave':
            $day_amount = 0;
            break;
    }
    
    $total = $day_amount + $ot_amount;
    $grand_total += $total;
    
    // Track employee totals
    if (!isset($employee_totals[$row['employee_name']])) {
        $employee_totals[$row['employee_name']] = [
            'days' => 0,
            'ot_hours' => 0,
            'amount' => 0
        ];
    }
    if ($row['status'] === 'present') {
        $employee_totals[$row['employee_name']]['days']++;
    } elseif ($row['status'] === 'half-day') {
        $employee_totals[$row['employee_name']]['days'] += 0.5;
    }
    $employee_totals[$row['employee_name']]['ot_hours'] += $row['ot_hours'];
    $employee_totals[$row['employee_name']]['amount'] += $total;
    
    echo "<tr>";
    echo "<td>" . date('Y-m-d', strtotime($row['attendance_date'])) . "</td>";
    echo "<td>{$row['employee_name']}</td>";
    echo "<td>" . ($row['time_in'] ? date('H:i', strtotime($row['time_in'])) : '-') . "</td>";
    echo "<td>" . ($row['time_out'] ? date('H:i', strtotime($row['time_out'])) : '-') . "</td>";
    echo "<td>" . ucfirst($row['status']) . "</td>";
    echo "<td class='text-right'>" . number_format($row['ot_hours'], 2) . "</td>";
    echo "<td class='text-right'>" . number_format($day_amount, 2) . "</td>";
    echo "<td class='text-right'>" . number_format($ot_amount, 2) . "</td>";
    echo "<td class='text-right'>" . number_format($total, 2) . "</td>";
    echo "</tr>";
}

echo "</tbody>";

// Summary section
echo "<tr><td colspan='9'></td></tr>";
echo "<tr><th colspan='9'>Summary</th></tr>";
foreach ($employee_totals as $employee => $totals) {
    echo "<tr>";
    echo "<td colspan='2'><strong>$employee</strong></td>";
    echo "<td colspan='2'>Days Worked: " . number_format($totals['days'], 1) . "</td>";
    echo "<td colspan='2'>OT Hours: " . number_format($totals['ot_hours'], 2) . "</td>";
    echo "<td colspan='3' class='text-right'>Total: " . number_format($totals['amount'], 2) . "</td>";
    echo "</tr>";
}
echo "<tr>";
echo "<th colspan='8' class='text-right'>Grand Total:</th>";
echo "<th class='text-right'>" . number_format($grand_total, 2) . "</th>";
echo "</tr>";

echo "</table>";
echo "</body>";
echo "</html>";
exit;
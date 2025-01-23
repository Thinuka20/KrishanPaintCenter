<?php
// preview_attendance.php
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

include 'header.php';
?>

<div class="container content">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Attendance Report Preview</h2>
            <p>Period: <?php echo date('Y-m-d', strtotime($start_date)); ?> to <?php echo date('Y-m-d', strtotime($end_date)); ?></p>
        </div>
        <div class="col-md-6 text-end">
            <a href="export_attendance_pdf.php?<?php echo $_SERVER['QUERY_STRING']; ?>" class="btn btn-danger" target="_blank">
                <i class="fas fa-file-pdf"></i> Export as PDF
            </a>
            <a href="attendance.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Employee</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Status</th>
                            <th>OT Hours</th>
                            <th>Day Amount</th>
                            <th>OT Amount</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $grand_total = 0;
                        $employee_totals = [];

                        while ($row = $result->fetch_assoc()):
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
                        ?>
                            <tr>
                                <td><?php echo date('Y-m-d', strtotime($row['attendance_date'])); ?></td>
                                <td><?php echo $row['employee_name']; ?></td>
                                <td><?php echo $row['time_in'] ? date('H:i', strtotime($row['time_in'])) : '-'; ?></td>
                                <td><?php echo $row['time_out'] ? date('H:i', strtotime($row['time_out'])) : '-'; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo
                                                            $row['status'] === 'present' ? 'success' : ($row['status'] === 'absent' ? 'danger' : ($row['status'] === 'half-day' ? 'warning' : 'info')); ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                <td class="text-end"><?php echo formatOTHours($row['ot_hours']); ?></td>
                                <td class="text-end"><?php echo formatCurrency($day_amount); ?></td>
                                <td class="text-end"><?php echo formatCurrency($ot_amount); ?></td>
                                <td class="text-end"><?php echo formatCurrency($total); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot>
                        <!-- Summary Section -->
                        <tr>
                            <td colspan="9" class="bg-light"><strong>Summary</strong></td>
                        </tr>
                        <?php foreach ($employee_totals as $employee => $totals): ?>
                            <tr>
                                <td colspan="2"><strong><?php echo $employee; ?></strong></td>
                                <td colspan="3">Days Worked: <?php echo number_format($totals['days'], 1); ?></td>
                                <td>OT Hours: <?php echo formatOTHours($totals['ot_hours']); ?></td>
                                <td colspan="3" class="text-end">
                                    Total: <?php echo formatCurrency($totals['amount']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="table-primary">
                            <th colspan="8" class="text-end">Grand Total:</th>
                            <th class="text-end"><?php echo formatCurrency($grand_total); ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: unauthorized.php");
    exit();
}

$employee_id = (int)$_GET['id'];
$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

// Get employee details
$query = "SELECT * FROM employees WHERE id = $employee_id";
$result = Database::search($query);
$employee = $result->fetch_assoc();

// Calculate salary period
$start_date = date('Y-m-01', strtotime($month));
$end_date = date('Y-m-t', strtotime($month));

// Get attendance records
$query = "SELECT * FROM employee_attendance 
          WHERE employee_id = $employee_id 
          AND attendance_date BETWEEN '$start_date' AND '$end_date'
          ORDER BY attendance_date";
$attendance_result = Database::search($query);

// Initialize totals
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

// Calculate total salary
$total_salary = $total_day_amount + $total_ot_amount;

// Check if payment exists
$query = "SELECT * FROM salary_payments 
          WHERE employee_id = $employee_id 
          AND payment_month = '$start_date'";
$payment_result = Database::search($query);
$payment_exists = $payment_result->num_rows > 0;
$payment_details = $payment_result->fetch_assoc();

include 'header.php';
?>

<div class="container content">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Salary Calculation</h2>
        </div>
        <div class="col-md-6 text-end">
            <?php if ($payment_exists): ?>
                <div class="alert alert-success">
                    <strong>Payment Status:</strong> Paid on <?php echo date('Y-m-d', strtotime($payment_details['payment_date'])); ?>
                    <br>
                    <strong>Amount:</strong> <?php echo formatCurrency($payment_details['total_amount']); ?>
                </div>
            <?php else: ?>
                <a href="record_salary_payment.php?id=<?php echo $employee_id; ?>&month=<?php echo $month; ?>"
                    class="btn btn-success">
                    <i class="fas fa-money-bill"></i> Record Payment
                </a>
            <?php endif; ?>
            <a href="print_salary_slip.php?id=<?php echo $employee_id; ?>&month=<?php echo $month; ?>"
                class="btn btn-danger" target="_blank">
                <i class="fas fa-file-pdf"></i> Print Salary Slip
            </a>
            <button onclick="history.back()" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Employee
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <!-- Month Selection -->
            <form method="GET" class="mb-4">
                <input type="hidden" name="id" value="<?php echo $employee_id; ?>">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Select Month</label>
                            <input type="month" name="month" class="form-control"
                                value="<?php echo $month; ?>"
                                max="<?php echo date('Y-m'); ?>"
                                onchange="this.form.submit()">
                        </div>
                    </div>
                </div>
            </form>

            <!-- Employee Information -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h4>Employee Information</h4>
                    <table class="table table-bordered">
                        <tr>
                            <th width="150">Name</th>
                            <td><?php echo $employee['name']; ?></td>
                        </tr>
                        <tr>
                            <th>Daily Rate</th>
                            <td><?php echo formatCurrency($employee['day_rate']); ?></td>
                        </tr>
                        <tr>
                            <th>OT Rate</th>
                            <td><?php echo formatCurrency($employee['overtime_rate']); ?> per hour</td>
                        </tr>
                    </table>
                    <h4>Salary Calculation</h4>
                            <table class="table table-bordered">
                                <tr>
                                    <th width="200">Working Days Amount</th>
                                    <td class="text-end"><?php echo formatCurrency($total_day_amount); ?></td>
                                </tr>
                                <tr>
                                    <th>OT Amount</th>
                                    <td class="text-end"><?php echo formatCurrency($total_ot_amount); ?></td>
                                </tr>
                                <tr class="table-primary">
                                    <th>Total Salary</th>
                                    <td class="text-end"><strong><?php echo formatCurrency($total_salary); ?></strong></td>
                                </tr>
                            </table>
                </div>
                <div class="col-md-6">
                    <h4>Attendance Summary</h4>
                    <table class="table table-bordered">
                        <tr>
                            <th width="150">Full Days</th>
                            <td><?php echo $total_days; ?> days</td>
                        </tr>
                        <tr>
                            <th>Half Days</th>
                            <td><?php echo $total_half_days; ?> days</td>
                        </tr>
                        <tr>
                            <th>Absents</th>
                            <td><?php echo $total_absents; ?> days</td>
                        </tr>
                        <tr>
                            <th>Leaves</th>
                            <td><?php echo $total_leaves; ?> days</td>
                        </tr>
                        <tr>
                            <th>Working Hours</th>
                            <td><?php echo formatOTHours($total_working_hours); ?> hours</td>
                        </tr>
                        <tr>
                            <th>OT Hours</th>
                            <td><?php echo formatOTHours($total_ot_hours); ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Attendance Details -->
            <h4 class="mt-4">Attendance Details</h4>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Working Hours</th>
                            <th>OT Hours</th>
                            <th>Day Amount</th>
                            <th>OT Amount</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        mysqli_data_seek($attendance_result, 0); // Reset result pointer
                        while ($row = $attendance_result->fetch_assoc()):
                            $status_class = '';
                            switch($row['status']) {
                                case 'present': $status_class = 'success'; break;
                                case 'absent': $status_class = 'danger'; break;
                                case 'half-day': $status_class = 'warning'; break;
                                case 'leave': $status_class = 'info'; break;
                            }
                        ?>
                            <tr>
                                <td><?php echo date('Y-m-d', strtotime($row['attendance_date'])); ?></td>
                                <td><span class="badge bg-<?php echo $status_class; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                                <td><?php echo $row['time_in'] ? date('H:i', strtotime($row['time_in'])) : '-'; ?></td>
                                <td><?php echo $row['time_out'] ? date('H:i', strtotime($row['time_out'])) : '-'; ?></td>
                                <td><?php echo formatOTHours($row['working_hours']); ?></td>
                                <td><?php echo formatOTHours($row['ot_hours']); ?></td>
                                <td class="text-end"><?php echo formatCurrency($row['day_amount']); ?></td>
                                <td class="text-end"><?php echo formatCurrency($row['ot_amount']); ?></td>
                                <td><?php echo $row['notes']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        .no-print { display: none !important; }
        .print-only { display: block !important; }
        .card { border: none !important; }
        .table { width: 100% !important; }
    }
</style>

<?php include 'footer.php'; ?>
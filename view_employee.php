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

// Get employee details
$query = "SELECT * FROM employees WHERE id = $employee_id";
$result = Database::search($query);
$employee = $result->fetch_assoc();

// Get current month attendance summary
$current_month = date('Y-m');
$start_date = date('Y-m-01');
$end_date = date('Y-m-t');

$query = "SELECT 
            COUNT(CASE WHEN status = 'present' THEN 1 END) as present_days,
            COUNT(CASE WHEN status = 'half-day' THEN 1 END) as half_days,
            COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent_days,
            COUNT(CASE WHEN status = 'leave' THEN 1 END) as leave_days,
            SUM(ot_hours) as total_ot
          FROM employee_attendance 
          WHERE employee_id = $employee_id 
          AND attendance_date BETWEEN '$start_date' AND '$end_date'";
$result = Database::search($query);
$month_summary = $result->fetch_assoc();

include 'header.php';
?>

<div class="container content">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Employee Details</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="salary_calculation.php?id=<?php echo $employee_id; ?>" class="btn btn-success">
                <i class="fas fa-calculator"></i> Calculate Salary
            </a>
            <a href="edit_employee.php?id=<?php echo $employee_id; ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit Details
            </a>
            <button onclick="history.back()" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Employee
            </button>

        </div>
    </div>

    <!-- Personal Information -->
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">
                    <h4>Personal Information</h4>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th width="150">Name</th>
                            <td><?php echo $employee['name']; ?></td>
                        </tr>
                        <tr>
                            <th>Phone</th>
                            <td><?php echo $employee['phone']; ?></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><?php echo $employee['email'] ?: '-'; ?></td>
                        </tr>
                        <tr>
                            <th>Address</th>
                            <td><?php echo $employee['address'] ?: '-'; ?></td>
                        </tr>
                        <tr>
                            <th>Join Date</th>
                            <td><?php echo date('Y-m-d', strtotime($employee['join_date'])); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">
                    <h4>Salary Information</h4>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th width="150">Day Rate</th>
                            <td><?php echo formatCurrency($employee['day_rate']); ?></td>
                        </tr>
                        <tr>
                            <th>OT Rate</th>
                            <td><?php echo formatCurrency($employee['overtime_rate']); ?> per hour</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Current Month Summary -->
            <div class="card">
                <div class="card-header">
                    <h4>Current Month Summary (<?php echo date('F Y'); ?>)</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h3><?php echo $month_summary['present_days']; ?></h3>
                                    <p class="mb-0">Present Days</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card bg-warning">
                                <div class="card-body text-center">
                                    <h3><?php echo $month_summary['half_days']; ?></h3>
                                    <p class="mb-0">Half Days</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center">
                                    <h3><?php echo $month_summary['absent_days']; ?></h3>
                                    <p class="mb-0">Absent Days</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h3><?php echo formatOTHours($month_summary['total_ot']); ?></h3>
                                    <p class="mb-0">OT Hours</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Attendance -->
    <div class="card mt-3">
        <div class="card-header">
            <h4>Recent Attendance</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>OT Hours</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT * FROM employee_attendance 
                                 WHERE employee_id = $employee_id 
                                 ORDER BY attendance_date DESC LIMIT 10";
                        $result = Database::search($query);
                        while ($row = $result->fetch_assoc()):
                        ?>
                            <tr>
                                <td><?php echo date('Y-m-d', strtotime($row['attendance_date'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo
                                                            $row['status'] === 'present' ? 'success' : ($row['status'] === 'absent' ? 'danger' : ($row['status'] === 'half-day' ? 'warning' : 'info')); ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $row['time_in'] ? date('H:i', strtotime($row['time_in'])) : '-'; ?></td>
                                <td><?php echo $row['time_out'] ? date('H:i', strtotime($row['time_out'])) : '-'; ?></td>
                                <td><?php echo formatOTHours($row['ot_hours']); ?></td>
                                <td><?php echo $row['notes']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Salary History -->
    <div class="card mt-3">
        <div class="card-header">
            <h4>Recent Salary History</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Working Days</th>
                            <th>OT Hours</th>
                            <th>Regular Amount</th>
                            <th>OT Amount</th>
                            <th>Total Amount</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Query to get salary payments with attendance summary
                        $query = "SELECT 
                        sp.*,
                        COUNT(CASE WHEN ea.status = 'present' THEN 1 END) + 
                        COUNT(CASE WHEN ea.status = 'half-day' THEN 0.5 END) as working_days,
                        SUM(ea.ot_hours) as total_ot_hours
                    FROM salary_payments sp
                    LEFT JOIN employee_attendance ea ON sp.employee_id = ea.employee_id 
                        AND DATE_FORMAT(ea.attendance_date, '%Y-%m') = DATE_FORMAT(sp.payment_month, '%Y-%m')
                    WHERE sp.employee_id = $employee_id
                    GROUP BY sp.id, DATE_FORMAT(sp.payment_month, '%Y-%m')
                    ORDER BY sp.payment_month DESC";

                        $result = Database::search($query);

                        while ($payment = $result->fetch_assoc()) {
                            $month = date('F Y', strtotime($payment['payment_month']));
                        ?>
                            <tr>
                                <td><?php echo $month; ?></td>
                                <td><?php echo number_format($payment['working_days'], 1); ?></td>
                                <td><?php echo number_format($payment['total_ot_hours'], 1); ?></td>
                                <td><?php echo formatCurrency($payment['regular_amount']); ?></td>
                                <td><?php echo formatCurrency($payment['ot_amount']); ?></td>
                                <td><?php echo formatCurrency($payment['total_amount']); ?></td>
                                <td>
                                    <a href="salary_calculation.php?id=<?php echo $employee_id; ?>&month=<?php echo date('Y-m', strtotime($payment['payment_month'])); ?>"
                                        class="btn btn-sm btn-info" style="color: white;">
                                        <i class="fas fa-file-invoice-dollar"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>

                        <?php if ($result->num_rows == 0) { ?>
                            <tr>
                                <td colspan="7" class="text-center">No salary payment records found</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();

$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$start_date = date('Y-m-01', strtotime($month));
$end_date = date('Y-m-t', strtotime($month));

include 'header.php';
?>

<div class="container content">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Salary Payments</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="#" onclick="window.print()" class="btn btn-secondary">
                <i class="fas fa-print"></i> Print Report
            </a>
        </div>
    </div>

    <!-- Month Selection -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Select Month</label>
                        <input type="month" name="month" class="form-control"
                            value="<?php echo $month; ?>"
                            max="<?php echo date('Y-m'); ?>"
                            onchange="this.form.submit()">
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Regular Amount</th>
                            <th>OT Amount</th>
                            <th>Total Amount</th>
                            <th>Payment Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT sp.*, e.name as employee_name 
                                 FROM salary_payments sp 
                                 LEFT JOIN employees e ON sp.employee_id = e.id 
                                 WHERE sp.payment_month = '$start_date'
                                 ORDER BY e.name";
                        $result = Database::search($query);
                        while ($payment = $result->fetch_assoc()):
                        ?>
                            <tr>
                                <td><?php echo $payment['employee_name']; ?></td>
                                <td><?php echo formatCurrency($payment['regular_amount']); ?></td>
                                <td><?php echo formatCurrency($payment['ot_amount']); ?></td>
                                <td><?php echo formatCurrency($payment['total_amount']); ?></td>
                                <td><?php echo date('Y-m-d', strtotime($payment['payment_date'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $payment['payment_status'] === 'paid' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($payment['payment_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="salary_calculation.php?id=<?php echo $payment['employee_id']; ?>&month=<?php echo $month; ?>"
                                        class="btn btn-sm btn-info" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="print_salary_slip.php?id=<?php echo $payment['employee_id']; ?>&month=<?php echo $month; ?>"
                                        class="btn btn-sm btn-secondary" title="Print Slip" target="_blank">
                                        <i class="fas fa-print"></i>
                                    </a>
                                    <a href="#" onclick="confirmDelete(<?php echo $payment['id']; ?>, '<?php echo $month; ?>')"
                                        class="btn btn-sm btn-danger" title="Delete Record">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
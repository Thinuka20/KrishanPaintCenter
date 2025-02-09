<?php
// report_salary_statement.php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';
require_once 'classes/ReportPDF.php';

checkLogin();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: unauthorized.php");
    exit();
}

// Get filter parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Check if PDF export is requested
if (isset($_GET['export_pdf'])) {
    // Initialize arrays for data collection
    $data = array();
    $totals = array(
        'regular_amount' => 0,
        'ot_amount' => 0,
        'total_amount' => 0,
        'payments' => 0
    );

    // Get salary payments data
    $query = "SELECT 
                sp.*,
                e.name as employee_name,
                e.phone as employee_phone
              FROM salary_payments sp
              JOIN employees e ON sp.employee_id = e.id 
              WHERE payment_date BETWEEN '$start_date' AND '$end_date'
              ORDER BY payment_date";
    
    $result = Database::search($query);
    
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
        $totals['regular_amount'] += $row['regular_amount'];
        $totals['ot_amount'] += $row['ot_amount'];
        $totals['total_amount'] += $row['total_amount'];
        $totals['payments']++;
    }

    // Generate PDF
    $pdf = new ReportPDF('L', 'Salary Payments Report');
    $pdf->generateSalaryPaymentsReport(
        $data,
        $totals,
        date('Y-m-d', strtotime($start_date)) . ' to ' . date('Y-m-d', strtotime($end_date))
    );
    $pdf->Output('Salary_Payments_Report_' . date('Y-m-d') . '.pdf', 'I');
    exit;
}

include 'header.php';
?>

<div class="container content">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Salary Payments Report</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&export_pdf=1" 
               class="btn btn-primary" target="_blank">
               <i class="fas fa-print"></i> Print Report
            </a>
            <button onclick="history.back()" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Reports
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <!-- Search Form -->
            <form method="GET" class="row mb-3">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" name="start_date" class="form-control" 
                               value="<?php echo $start_date; ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" name="end_date" class="form-control" 
                               value="<?php echo $end_date; ?>">
                    </div>
                </div>
                <div class="col-md-4">
                        <button type="submit" class="btn btn-primary mt-4">Generate Report</button>
                </div>
            </form>

            <!-- Salary Payments Table -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Payment Date</th>
                            <th>Employee</th>
                            <th>Phone</th>
                            <th class="text-end">Regular Amount</th>
                            <th class="text-end">OT Amount</th>
                            <th class="text-end">Total Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $total_regular = 0;
                        $total_ot = 0;
                        $total_amount = 0;
                        $total_payments = 0;

                        // Get salary payments
                        $query = "SELECT 
                                    sp.*,
                                    e.name as employee_name,
                                    e.phone as employee_phone
                                 FROM salary_payments sp
                                 JOIN employees e ON sp.employee_id = e.id 
                                 WHERE payment_date BETWEEN '$start_date' AND '$end_date'
                                 ORDER BY payment_date";
                        
                        $result = Database::search($query);
                        while ($row = $result->fetch_assoc()) {
                            $total_regular += $row['regular_amount'];
                            $total_ot += $row['ot_amount'];
                            $total_amount += $row['total_amount'];
                            $total_payments++;
                        ?>
                            <tr>
                                <td><?php echo date('Y-m-d', strtotime($row['payment_date'])); ?></td>
                                <td><?php echo htmlspecialchars($row['employee_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['employee_phone']); ?></td>
                                <td class="text-end"><?php echo formatCurrency($row['regular_amount']); ?></td>
                                <td class="text-end"><?php echo formatCurrency($row['ot_amount']); ?></td>
                                <td class="text-end"><?php echo formatCurrency($row['total_amount']); ?></td>
                                <td>
                                    <?php if ($row['payment_status'] == 'paid'): ?>
                                        <span class="badge bg-success">Paid</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Pending</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="3">Total (<?php echo $total_payments; ?> payments)</td>
                            <td class="text-end"><?php echo formatCurrency($total_regular); ?></td>
                            <td class="text-end"><?php echo formatCurrency($total_ot); ?></td>
                            <td class="text-end"><?php echo formatCurrency($total_amount); ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
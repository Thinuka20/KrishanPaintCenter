<?php
// report_supplier_balance.php
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
    $data = array();
    $totals = array(
        'credit' => 0,
        'debit' => 0,
        'balance' => 0
    );

    // Get all suppliers' balances
    $query = "SELECT 
                s.id,
                s.name AS supplier_name,
                s.phone AS supplier_phone,
                s.contact_person,
                COALESCE(SUM(CASE WHEN st.transaction_type = 'debit' THEN st.amount ELSE 0 END), 0) as total_debit,
                COALESCE(SUM(CASE WHEN st.transaction_type = 'credit' THEN st.amount ELSE 0 END), 0) as total_credit,
                COALESCE(SUM(CASE WHEN st.transaction_type = 'debit' THEN st.amount ELSE -st.amount END), 0) as balance,
                COUNT(st.id) as transaction_count
              FROM suppliers s
              LEFT JOIN supplier_transactions st ON s.id = st.supplier_id 
                AND st.transaction_date BETWEEN '$start_date' AND '$end_date'
              GROUP BY s.id, s.name, s.phone, s.contact_person
              ORDER BY s.name";
    
    $result = Database::search($query);
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
        $totals['debit'] += $row['total_debit'];
        $totals['credit'] += $row['total_credit'];
        $totals['balance'] += $row['balance'];
    }

    // Generate PDF
    $pdf = new ReportPDF('L');
    $pdf->generateSupplierBalanceReport(
        $data,
        $totals,
        date('Y-m-d', strtotime($start_date)) . ' to ' . date('Y-m-d', strtotime($end_date))
    );
    $pdf->Output('Supplier_Balance_Report_' . date('Y-m-d') . '.pdf', 'I');
    exit;
}

include 'header.php';
?>

<div class="container content">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Supplier Balance Report</h2>
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
                               value="<?php echo $start_date; ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" name="end_date" class="form-control" 
                               value="<?php echo $end_date; ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary form-control">Generate</button>
                    </div>
                </div>
            </form>

            <!-- Supplier Balances Table -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Supplier Name</th>
                            <th>Contact Person</th>
                            <th>Phone</th>
                            <th class="text-end">Total Debit</th>
                            <th class="text-end">Total Credit</th>
                            <th class="text-end">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $total_debit = 0;
                        $total_credit = 0;
                        $total_balance = 0;

                        // Get all suppliers' balances
                        $query = "SELECT 
                                    s.id,
                                    s.name AS supplier_name,
                                    s.phone AS supplier_phone,
                                    s.contact_person,
                                    COALESCE(SUM(CASE WHEN st.transaction_type = 'debit' THEN st.amount ELSE 0 END), 0) as total_debit,
                                    COALESCE(SUM(CASE WHEN st.transaction_type = 'credit' THEN st.amount ELSE 0 END), 0) as total_credit,
                                    COALESCE(SUM(CASE WHEN st.transaction_type = 'debit' THEN st.amount ELSE -st.amount END), 0) as balance,
                                    COUNT(st.id) as transaction_count
                                FROM suppliers s
                                LEFT JOIN supplier_transactions st ON s.id = st.supplier_id 
                                    AND st.transaction_date BETWEEN '$start_date' AND '$end_date'
                                GROUP BY s.id, s.name, s.phone, s.contact_person
                                ORDER BY s.name";
                        
                        $result = Database::search($query);
                        while ($row = $result->fetch_assoc()) {
                            $total_debit += $row['total_debit'];
                            $total_credit += $row['total_credit'];
                            $total_balance += $row['balance'];
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['contact_person']); ?></td>
                                <td><?php echo htmlspecialchars($row['supplier_phone']); ?></td>
                                <td class="text-end"><?php echo formatCurrency($row['total_debit']); ?></td>
                                <td class="text-end"><?php echo formatCurrency($row['total_credit']); ?></td>
                                <td class="text-end"><?php echo formatCurrency($row['balance']); ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="3">Totals</td>
                            <td class="text-end"><?php echo formatCurrency($total_debit); ?></td>
                            <td class="text-end"><?php echo formatCurrency($total_credit); ?></td>
                            <td class="text-end"><?php echo formatCurrency($total_balance); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
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
$supplier_id = isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : null;

// Check if PDF export is requested
if (isset($_GET['export_pdf'])) {
    // Initialize arrays for data collection
    $data = array();
    $totals = array(
        'credit' => 0,
        'debit' => 0,
        'balance' => 0,
        'transactions' => 0
    );

    if ($supplier_id) {
        // Get supplier transactions
        $query = "SELECT 
                    st.*,
                    s.name as supplier_name,
                    s.phone as supplier_phone,
                    s.contact_person
                  FROM supplier_transactions st
                  JOIN suppliers s ON st.supplier_id = s.id
                  WHERE st.supplier_id = $supplier_id 
                  AND st.transaction_date BETWEEN '$start_date' AND '$end_date'
                  ORDER BY st.transaction_date, st.id";
        
        $result = Database::search($query);
        $running_balance = 0;
        
        while ($row = $result->fetch_assoc()) {
            if ($row['transaction_type'] == 'credit') {
                $running_balance -= $row['amount'];
                $totals['credit'] += $row['amount'];
            } else {
                $running_balance += $row['amount'];
                $totals['debit'] += $row['amount'];
            }
            $row['balance'] = $running_balance;
            $data[] = $row;
            $totals['transactions']++;
        }
        $totals['balance'] = $running_balance;

        // Generate PDF
        $pdf = new ReportPDF('L', 'Supplier Balance Report');
        $pdf->generateSupplierTransactions(
            $data,
            $totals,
            date('Y-m-d', strtotime($start_date)) . ' to ' . date('Y-m-d', strtotime($end_date))
        );
        $pdf->Output('Supplier_Balance_Report_' . date('Y-m-d') . '.pdf', 'I');
        exit;
    }
}

// Get all suppliers for dropdown
$query = "SELECT id, name FROM suppliers ORDER BY name";
$suppliers_result = Database::search($query);

include 'header.php';
?>

<div class="container content">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Supplier Balance Report</h2>
        </div>
        <div class="col-md-6 text-end">
            <?php if ($supplier_id): ?>
            <a href="?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&supplier_id=<?php echo $supplier_id; ?>&export_pdf=1" 
               class="btn btn-primary" target="_blank">
               <i class="fas fa-print"></i> Print Report
            </a>
            <?php endif; ?>
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
                        <label>Supplier</label>
                        <select class="form-control select2" name="supplier_id" required>
                            <option value="">Select Supplier</option>
                            <?php while ($supplier = $suppliers_result->fetch_assoc()): ?>
                                <option value="<?php echo $supplier['id']; ?>" 
                                    <?php echo ($supplier_id == $supplier['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($supplier['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" name="start_date" class="form-control" 
                               value="<?php echo $start_date; ?>" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" name="end_date" class="form-control" 
                               value="<?php echo $end_date; ?>" required>
                    </div>
                </div>
                <div class="col-md-2 mt-4">
                        <button type="submit" class="btn btn-primary">Generate</button>
                </div>
            </form>

            <?php if ($supplier_id): 
                // Get supplier transactions
                $query = "SELECT 
                            st.*,
                            s.name as supplier_name,
                            s.phone as supplier_phone,
                            s.contact_person
                         FROM supplier_transactions st
                         JOIN suppliers s ON st.supplier_id = s.id
                         WHERE st.supplier_id = $supplier_id 
                         AND st.transaction_date BETWEEN '$start_date' AND '$end_date'
                         ORDER BY st.transaction_date, st.id";
                
                $result = Database::search($query);
                $transactions = [];
                $totals = [
                    'credit' => 0,
                    'debit' => 0,
                    'transactions' => 0
                ];
                $running_balance = 0;
                
                while ($row = $result->fetch_assoc()) {
                    if ($row['transaction_type'] == 'credit') {
                        $running_balance -= $row['amount'];
                        $totals['credit'] += $row['amount'];
                    } else {
                        $running_balance += $row['amount'];
                        $totals['debit'] += $row['amount'];
                    }
                    $row['balance'] = $running_balance;
                    $transactions[] = $row;
                    $totals['transactions']++;
                }

                if (!empty($transactions)):
                    $supplier = $transactions[0];
            ?>
                <!-- Balance Overview -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Debit</h5>
                                <h3 class="mb-0"><?php echo formatCurrency($totals['debit']); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Credit</h5>
                                <h3 class="mb-0"><?php echo formatCurrency($totals['credit']); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">Current Balance</h5>
                                <h3 class="mb-0"><?php echo formatCurrency($running_balance); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Transactions</h5>
                                <h3 class="mb-0"><?php echo $totals['transactions']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Balance Chart -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <canvas id="balanceChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transactions Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th class="text-end">Debit</th>
                                <th class="text-end">Credit</th>
                                <th class="text-end">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $trans): ?>
                                <tr>
                                    <td><?php echo date('Y-m-d', strtotime($trans['transaction_date'])); ?></td>
                                    <td><?php echo ucfirst($trans['transaction_type']); ?></td>
                                    <td><?php echo htmlspecialchars($trans['description']); ?></td>
                                    <td class="text-end">
                                        <?php echo $trans['transaction_type'] == 'debit' ? formatCurrency($trans['amount']) : '-'; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php echo $trans['transaction_type'] == 'credit' ? formatCurrency($trans['amount']) : '-'; ?>
                                    </td>
                                    <td class="text-end"><?php echo formatCurrency($trans['balance']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td colspan="3">Totals</td>
                                <td class="text-end"><?php echo formatCurrency($totals['debit']); ?></td>
                                <td class="text-end"><?php echo formatCurrency($totals['credit']); ?></td>
                                <td class="text-end"><?php echo formatCurrency($running_balance); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                <script>
                const dates = <?php echo json_encode(array_map(function($trans) {
                    return date('Y-m-d', strtotime($trans['transaction_date']));
                }, $transactions)); ?>;

                const balances = <?php echo json_encode(array_map(function($trans) {
                    return $trans['balance'];
                }, $transactions)); ?>;

                // Balance Chart
                new Chart(document.getElementById('balanceChart'), {
                    type: 'line',
                    data: {
                        labels: dates,
                        datasets: [{
                            label: 'Balance',
                            data: balances,
                            borderColor: 'rgb(75, 192, 192)',
                            tension: 0.1,
                            fill: false
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Balance Trend'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: false,
                                ticks: {
                                    callback: function(value) {
                                        return 'Rs. ' + value.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });

                // Initialize Select2
                $(document).ready(function() {
                    $('.select2').select2({
                        theme: 'bootstrap4',
                        width: '100%'
                    });
                });
                </script>

            <?php else: ?>
                <div class="alert alert-info">
                    No transactions found for the selected period.
                </div>
            <?php endif; ?>
        <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
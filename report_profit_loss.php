<?php
// report_profit_loss.php
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

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Check if export to PDF is requested
if (isset($_GET['export_pdf'])) {
    // Get all the required data
    
    // Income Data
    $query = "SELECT SUM(total_amount) as total 
              FROM repair_invoices 
              WHERE invoice_date BETWEEN '$start_date' AND '$end_date'";
    $result = Database::search($query);
    $repair_income = $result->fetch_assoc()['total'] ?? 0;
    
    $query = "SELECT SUM(total_amount) as total 
              FROM item_invoices 
              WHERE invoice_date BETWEEN '$start_date' AND '$end_date'";
    $result = Database::search($query);
    $item_income = $result->fetch_assoc()['total'] ?? 0;
    
    $income_data = array(
        'repair_income' => $repair_income,
        'item_income' => $item_income
    );
    
    // Expense Data
    $query = "SELECT category, SUM(amount) as total 
              FROM expenses 
              WHERE expense_date BETWEEN '$start_date' AND '$end_date' 
              GROUP BY category 
              ORDER BY total DESC";
    $result = Database::search($query);
    $general_expenses = array();
    while ($row = $result->fetch_assoc()) {
        $general_expenses[] = $row;
    }
    
    $query = "SELECT SUM(amount) as total 
              FROM supplier_transactions 
              WHERE transaction_type = 'credit' 
              AND transaction_date BETWEEN '$start_date' AND '$end_date'";
    $result = Database::search($query);
    $supplier_payments = $result->fetch_assoc()['total'] ?? 0;
    
    $query = "SELECT SUM(total_amount) as total 
              FROM salary_payments 
              WHERE payment_date BETWEEN '$start_date' AND '$end_date'";
    $result = Database::search($query);
    $salary_payments = $result->fetch_assoc()['total'] ?? 0;
    
    $expense_data = array(
        'general_expenses' => $general_expenses,
        'supplier_payments' => $supplier_payments,
        'salary_payments' => $salary_payments
    );
    
    // Calculate totals
    $total_income = $repair_income + $item_income;
    $total_expenses = $salary_payments + $supplier_payments;
    foreach ($general_expenses as $expense) {
        $total_expenses += $expense['total'];
    }
    
    $totals = array(
        'total_income' => $total_income,
        'total_expenses' => $total_expenses
    );
    
    // Generate PDF
    $date_range = date('F d, Y', strtotime($start_date)) . ' to ' . date('F d, Y', strtotime($end_date));
    $pdf = new ReportPDF('P', 'Profit & Loss Statement');
    $pdf->generateProfitLossReport($income_data, $expense_data, $totals, $date_range);
    $pdf->Output('profit_loss_statement.pdf', 'I');
    exit;
}

include 'header.php';
?>

<div class="container content">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Profit & Loss Statement</h2>
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
                    <button type="submit" class="btn btn-primary mt-4">Generate Statement</button>
                </div>
            </form>

            <!-- Income Section -->
            <h4 class="mb-3">Income</h4>
            <table class="table table-bordered">
                <tbody>
                    <?php
                    // Repair Income
                    $query = "SELECT SUM(total_amount) as total 
                             FROM repair_invoices 
                             WHERE invoice_date BETWEEN '$start_date' AND '$end_date'";
                    $result = Database::search($query);
                    $repair_income = $result->fetch_assoc()['total'] ?? 0;
                    
                    // Item Sales Income
                    $query = "SELECT SUM(total_amount) as total 
                             FROM item_invoices 
                             WHERE invoice_date BETWEEN '$start_date' AND '$end_date'";
                    $result = Database::search($query);
                    $item_income = $result->fetch_assoc()['total'] ?? 0;
                    
                    $total_income = $repair_income + $item_income;
                    ?>
                    <tr>
                        <td>Repair Services Income</td>
                        <td class="text-end"><?php echo formatCurrency($repair_income); ?></td>
                    </tr>
                    <tr>
                        <td>Item Sales Income</td>
                        <td class="text-end"><?php echo formatCurrency($item_income); ?></td>
                    </tr>
                    <tr class="table-success">
                        <th>Total Income</th>
                        <th class="text-end"><?php echo formatCurrency($total_income); ?></th>
                    </tr>
                </tbody>
            </table>

            <!-- Expenses -->
            <h4 class="mb-3 mt-4">Expenses</h4>
            <table class="table table-bordered">
                <tbody>
                    <?php
                    // General Expenses
                    $query = "SELECT category, SUM(amount) as total 
                             FROM expenses 
                             WHERE expense_date BETWEEN '$start_date' AND '$end_date' 
                             GROUP BY category 
                             ORDER BY total DESC";
                    $result = Database::search($query);
                    $total_expenses = 0;
                    while ($row = $result->fetch_assoc()):
                        $total_expenses += $row['total'];
                    ?>
                    <tr>
                        <td><?php echo $row['category']; ?></td>
                        <td class="text-end"><?php echo formatCurrency($row['total']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                    
                    <!-- Supplier Payments -->
                    <?php
                    $query = "SELECT SUM(amount) as total 
                             FROM supplier_transactions 
                             WHERE transaction_type = 'credit' 
                             AND transaction_date BETWEEN '$start_date' AND '$end_date'";
                    $result = Database::search($query);
                    $supplier_payments = $result->fetch_assoc()['total'] ?? 0;
                    $total_expenses += $supplier_payments;
                    ?>
                    <tr>
                        <td>Supplier Payments</td>
                        <td class="text-end"><?php echo formatCurrency($supplier_payments); ?></td>
                    </tr>

                    <!-- Salary Payments -->
                    <?php
                    $query = "SELECT SUM(total_amount) as total 
                             FROM salary_payments 
                             WHERE payment_date BETWEEN '$start_date' AND '$end_date'";
                    $result = Database::search($query);
                    $salary_payments = $result->fetch_assoc()['total'] ?? 0;
                    $total_expenses += $salary_payments;
                    ?>
                    <tr>
                        <td>Salary Payments</td>
                        <td class="text-end"><?php echo formatCurrency($salary_payments); ?></td>
                    </tr>

                    <tr class="table-warning">
                        <th>Total Expenses</th>
                        <th class="text-end"><?php echo formatCurrency($total_expenses); ?></th>
                    </tr>
                </tbody>
            </table>

            <!-- Net Profit/Loss -->
            <?php
            $net_profit = $total_income - $total_expenses;
            $profit_margin = $total_income > 0 ? ($net_profit / $total_income * 100) : 0;
            ?>
            <div class="card <?php echo $net_profit >= 0 ? 'bg-success' : 'bg-danger'; ?> text-white mt-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h4>Net <?php echo $net_profit >= 0 ? 'Profit' : 'Loss'; ?></h4>
                            <h2><?php echo formatCurrency(abs($net_profit)); ?></h2>
                        </div>
                        <div class="col-md-6 text-end">
                            <h4>Profit Margin</h4>
                            <h2><?php echo number_format($profit_margin, 2); ?>%</h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <canvas id="incomeChart"></canvas>
                </div>
                <div class="col-md-6">
                    <canvas id="expenseChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Income Distribution Chart
new Chart(document.getElementById('incomeChart'), {
    type: 'pie',
    data: {
        labels: ['Repair Services', 'Item Sales'],
        datasets: [{
            data: [<?php echo $repair_income; ?>, <?php echo $item_income; ?>],
            backgroundColor: ['rgb(75, 192, 192)', 'rgb(255, 99, 132)']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Income Distribution'
            }
        }
    }
});

// Expense Distribution Chart
<?php
// Get all expense categories including supplier and salary payments
$query = "SELECT category, SUM(amount) as total 
          FROM expenses 
          WHERE expense_date BETWEEN '$start_date' AND '$end_date' 
          GROUP BY category 
          ORDER BY total DESC";
$result = Database::search($query);
$categories = [];
$amounts = [];

// Add regular expenses
while ($row = $result->fetch_assoc()) {
    $categories[] = $row['category'];
    $amounts[] = $row['total'];
}

// Add supplier payments
$categories[] = 'Supplier Payments';
$amounts[] = $supplier_payments;

// Add salary payments
$categories[] = 'Salary Payments';
$amounts[] = $salary_payments;
?>

new Chart(document.getElementById('expenseChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($categories); ?>,
        datasets: [{
            label: 'Amount',
            data: <?php echo json_encode($amounts); ?>,
            backgroundColor: 'rgb(54, 162, 235)'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Expense Distribution'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'Rs. ' + value.toLocaleString();
                    }
                }
            }
        }
    }
});
</script>

<?php include 'footer.php'; ?>
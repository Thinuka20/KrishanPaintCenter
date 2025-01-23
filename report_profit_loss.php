<?php
// report_profit_loss.php - Profit and Loss Statement
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

include 'header.php';
?>

<div class="container content">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Profit & Loss Statement</h2>
        </div>
        <div class="col-md-6 text-end">
            <button onclick="window.print()" class="btn btn-secondary">
                <i class="fas fa-print"></i> Print Report
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

            <!-- Cost of Goods Sold -->
            <h4 class="mb-3 mt-4">Cost of Goods Sold</h4>
            <table class="table table-bordered">
                <tbody>
                    <?php
                    // Calculate COGS
                    $query = "SELECT SUM(iid.quantity * i.unit_price) as total_cost
                             FROM item_invoice_details iid
                             LEFT JOIN items i ON iid.item_id = i.id
                             LEFT JOIN item_invoices ii ON iid.item_invoice_id = ii.id
                             WHERE ii.invoice_date BETWEEN '$start_date' AND '$end_date'";
                    $result = Database::search($query);
                    $cogs = $result->fetch_assoc()['total_cost'] ?? 0;
                    
                    $gross_profit = $total_income - $cogs;
                    ?>
                    <tr>
                        <td>Cost of Goods Sold</td>
                        <td class="text-end"><?php echo formatCurrency($cogs); ?></td>
                    </tr>
                    <tr class="table-info">
                        <th>Gross Profit</th>
                        <th class="text-end"><?php echo formatCurrency($gross_profit); ?></th>
                    </tr>
                </tbody>
            </table>

            <!-- Expenses -->
            <h4 class="mb-3 mt-4">Expenses</h4>
            <table class="table table-bordered">
                <tbody>
                    <?php
                    // Get expense categories and totals
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
                    
                    <!-- Employee Salaries -->
                    <?php
                    $query = "SELECT SUM(
                                (hours_worked * (e.day_rate / 8)) + 
                                (overtime_hours * e.overtime_rate)
                             ) as total_salaries
                             FROM work_records wr
                             LEFT JOIN employees e ON wr.employee_id = e.id
                             WHERE work_date BETWEEN '$start_date' AND '$end_date'";
                    $result = Database::search($query);
                    $salaries = $result->fetch_assoc()['total_salaries'] ?? 0;
                    $total_expenses += $salaries;
                    ?>
                    <tr>
                        <td>Employee Salaries</td>
                        <td class="text-end"><?php echo formatCurrency($salaries); ?></td>
                    </tr>
                    <tr class="table-warning">
                        <th>Total Expenses</th>
                        <th class="text-end"><?php echo formatCurrency($total_expenses); ?></th>
                    </tr>
                </tbody>
            </table>

            <!-- Net Profit/Loss -->
            <?php
            $net_profit = $gross_profit - $total_expenses;
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
$query = "SELECT category, SUM(amount) as total 
          FROM expenses 
          WHERE expense_date BETWEEN '$start_date' AND '$end_date' 
          GROUP BY category 
          ORDER BY total DESC";
$result = Database::search($query);
$categories = [];
$amounts = [];
while ($row = $result->fetch_assoc()) {
    $categories[] = $row['category'];
    $amounts[] = $row['total'];
}
// Add salary to the chart
$categories[] = 'Salaries';
$amounts[] = $salaries;
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
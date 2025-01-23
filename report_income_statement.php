<?php
// report_income_statement.php - Detailed income statement
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
            <h2>Income Statement</h2>
        </div>
        <div class="col-md-6 text-end">
            <button onclick="window.print()" class="btn btn-secondary">
                <i class="fas fa-print"></i> Print Statement
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

            <!-- Detailed Income Section -->
            <h4>Income Details</h4>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Invoice #</th>
                            <th>Customer</th>
                            <th>Type</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Repair Invoices
                        $query = "SELECT ri.*, v.registration_number, c.name as customer_name
                                 FROM repair_invoices ri
                                 LEFT JOIN vehicles v ON ri.vehicle_id = v.id
                                 LEFT JOIN customers c ON v.customer_id = c.id
                                 WHERE ri.invoice_date BETWEEN '$start_date' AND '$end_date'
                                 ORDER BY ri.invoice_date";
                        $result = Database::search($query);
                        while ($row = $result->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo date('Y-m-d', strtotime($row['invoice_date'])); ?></td>
                            <td><?php echo $row['invoice_number']; ?></td>
                            <td><?php echo $row['customer_name'] . ' (' . $row['registration_number'] . ')'; ?></td>
                            <td>Repair Service</td>
                            <td class="text-end"><?php echo formatCurrency($row['total_amount']); ?></td>
                        </tr>
                        <?php endwhile; ?>

                        <?php
                        // Item Invoices
                        $query = "SELECT ii.*, c.name as customer_name
                                 FROM item_invoices ii
                                 LEFT JOIN customers c ON ii.customer_id = c.id
                                 WHERE ii.invoice_date BETWEEN '$start_date' AND '$end_date'
                                 ORDER BY ii.invoice_date";
                        $result = Database::search($query);
                        while ($row = $result->fetch_assoc()):
                            $total_income += $row['total_amount'];
                        ?>
                        <tr>
                            <td><?php echo date('Y-m-d', strtotime($row['invoice_date'])); ?></td>
                            <td><?php echo $row['invoice_number']; ?></td>
                            <td><?php echo $row['customer_name']; ?></td>
                            <td>Item Sale</td>
                            <td class="text-end"><?php echo formatCurrency($row['total_amount']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-success">
                            <th colspan="4">Total Income</th>
                            <th class="text-end"><?php echo formatCurrency($total_income); ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Profit Analysis -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <h4>Daily Profit Analysis</h4>
                    <canvas id="profitChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Daily Profit Chart
<?php
$dates = [];
$daily_income = [];
$daily_expenses = [];
$daily_profit = [];

$current_date = new DateTime($start_date);
$end = new DateTime($end_date);

while ($current_date <= $end) {
    $date = $current_date->format('Y-m-d');
    $dates[] = $date;
    
    // Get daily income
    $query = "SELECT COALESCE(SUM(total_amount), 0) as income FROM (
                SELECT total_amount, invoice_date FROM repair_invoices
                UNION ALL
                SELECT total_amount, invoice_date FROM item_invoices
              ) as income WHERE DATE(invoice_date) = '$date'";
    $result = Database::search($query);
    $income = $result->fetch_assoc()['income'];
    $total_income = $result->fetch_assoc()['income'];
    $daily_income[] = $income;
    
    // Get daily expenses
    $query = "SELECT COALESCE(SUM(amount), 0) as expenses 
              FROM expenses 
              WHERE DATE(expense_date) = '$date'";
    $result = Database::search($query);
    $expenses = $result->fetch_assoc()['expenses'];
    $daily_expenses[] = $expenses;
    
    $daily_profit[] = $income - $expenses;
    
    $current_date->modify('+1 day');
}
?>

new Chart(document.getElementById('profitChart'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode($dates); ?>,
        datasets: [{
            label: 'Income',
            data: <?php echo json_encode($daily_income); ?>,
            borderColor: 'rgb(75, 192, 192)',
            fill: false
        }, {
            label: 'Expenses',
            data: <?php echo json_encode($daily_expenses); ?>,
            borderColor: 'rgb(255, 99, 132)',
            fill: false
        }, {
            label: 'Profit/Loss',
            data: <?php echo json_encode($daily_profit); ?>,
            borderColor: 'rgb(54, 162, 235)',
            fill: false
        }]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Daily Income, Expenses, and Profit Analysis'
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
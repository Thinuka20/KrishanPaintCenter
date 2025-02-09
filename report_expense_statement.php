<?php
// report_financial_statement.php
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

// Get total expenses and group by category
$query = "SELECT 
            category,
            COUNT(*) as transaction_count,
            SUM(amount) as category_total
          FROM expenses 
          WHERE expense_date BETWEEN '$start_date' AND '$end_date'
          GROUP BY category";
$result = Database::search($query);
$expense_categories = [];
$total_expenses = 0;

while ($row = $result->fetch_assoc()) {
    $expense_categories[] = $row;
    $total_expenses += $row['category_total'];
}

// Get supplier credits summary (only credits)
$supplier_query = "SELECT 
                    s.name as supplier_name,
                    COUNT(*) as transaction_count,
                    SUM(st.amount) as total_amount
                  FROM supplier_transactions st
                  JOIN suppliers s ON s.id = st.supplier_id
                  WHERE st.transaction_date BETWEEN '$start_date' AND '$end_date'
                  AND st.transaction_type = 'credit'
                  GROUP BY s.name";
$supplier_result = Database::search($supplier_query);
$supplier_credits = [];
$total_supplier_credit = 0;

while ($row = $supplier_result->fetch_assoc()) {
    $supplier_credits[] = $row;
    $total_supplier_credit += $row['total_amount'];
}

// Get salary payments summary
$salary_query = "SELECT 
                  e.name as employee_name,
                  COUNT(*) as payment_count,
                  SUM(sp.regular_amount) as total_regular,
                  SUM(sp.ot_amount) as total_ot,
                  SUM(sp.total_amount) as total_amount
                FROM salary_payments sp
                JOIN employees e ON e.id = sp.employee_id
                WHERE sp.payment_date BETWEEN '$start_date' AND '$end_date'
                GROUP BY e.name";
$salary_result = Database::search($salary_query);
$salary_payments = [];
$total_salary = 0;
$total_regular = 0;
$total_ot = 0;

$wholeExpenses = $total_expenses + $total_salary + $total_supplier_credit;

while ($row = $salary_result->fetch_assoc()) {
    $salary_payments[] = $row;
    $total_salary += $row['total_amount'];
    $total_regular += $row['total_regular'];
    $total_ot += $row['total_ot'];
}

// Check if PDF export is requested
if (isset($_GET['export_pdf'])) {
    // Create new PDF instance
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Krishan Paint Center');
    $pdf->SetAuthor('Krishan Paint Center');
    $pdf->SetTitle('Financial Statement');

    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // Set margins
    $pdf->SetMargins(10, 10, 10);
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    // Add a page
    $pdf->AddPage();

    // Set header
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'KRISHAN PAINT CENTER', 0, 1, 'C');
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Financial Statement', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'Period: ' . date('Y-m-d', strtotime($start_date)) . ' to ' . date('Y-m-d', strtotime($end_date)), 0, 1, 'C');
    $pdf->Ln(5);

    // Expense Summary section
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 7, 'Expenses By Categories', 0, 1);
    $pdf->SetFont('helvetica', '', 10);

    foreach ($expense_categories as $category) {
        $pdf->Cell(100, 6, $category['category'] . ' (' . $category['transaction_count'] . ' transactions):', 1);
        $pdf->Cell(60, 6, formatCurrency($category['category_total']), 1);
        $pdf->Ln();
    }
    
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(100, 6, 'Total Expenses:', 1);
    $pdf->Cell(60, 6, formatCurrency($total_expenses), 1);
    $pdf->Ln(10);

    // Supplier Credits Summary
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 7, 'Supplier Credits Summary', 0, 1);
    $pdf->SetFont('helvetica', '', 10);

    foreach ($supplier_credits as $credit) {
        $pdf->Cell(100, 6, $credit['supplier_name'] . ' (' . $credit['transaction_count'] . ' transactions):', 1);
        $pdf->Cell(60, 6, formatCurrency($credit['total_amount']), 1);
        $pdf->Ln();
    }

    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(100, 6, 'Total Supplier Credits:', 1);
    $pdf->Cell(60, 6, formatCurrency($total_supplier_credit), 1);
    $pdf->Ln(10);

    // Salary Payments Summary
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 7, 'Salary Payments Summary', 0, 1);
    $pdf->SetFont('helvetica', '', 10);

    foreach ($salary_payments as $payment) {
        $pdf->Cell(100, 6, $payment['employee_name'] . ' (' . $payment['payment_count'] . ' payments):', 1);
        $pdf->Cell(60, 6, formatCurrency($payment['total_amount']), 1);
        $pdf->Ln();
    }

    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(100, 6, 'Total Regular Salary:', 1);
    $pdf->Cell(60, 6, formatCurrency($total_regular), 1);
    $pdf->Ln();
    $pdf->Cell(100, 6, 'Total Overtime:', 1);
    $pdf->Cell(60, 6, formatCurrency($total_ot), 1);
    $pdf->Ln();
    $pdf->Cell(100, 6, 'Total Salary Payments:', 1);
    $pdf->Cell(60, 6, formatCurrency($total_salary), 1);
    $pdf->Ln(10);

    // Expense Summary section
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 7, 'Expenses Summary', 0, 1);
    $pdf->SetFont('helvetica', '', 10);

    foreach ($expense_categories as $category) {
        $pdf->Cell(100, 6, $category['category'] . ' (' . $category['transaction_count'] . ' transactions):', 1);
        $pdf->Cell(60, 6, formatCurrency($category['category_total']), 1);
        $pdf->Ln();
    }
    
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(100, 6, 'Total Expenses:', 1);
    $pdf->Cell(60, 6, formatCurrency($wholeExpenses), 1);
    $pdf->Ln(10);

    // Output PDF
    $pdf->Output('Financial_Statement.pdf', 'I');
    exit;
}

include 'header.php';
?>

<div class="container content">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Financial Statement</h2>
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
            <!-- Date Range Form -->
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

            <!-- Financial Overview Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5>Total Category Expenses</h5>
                            <h3><?php echo formatCurrency($total_expenses); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5>Total Supplier Credits</h5>
                            <h3><?php echo formatCurrency($total_supplier_credit); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h5>Total Salary Payments</h5>
                            <h3><?php echo formatCurrency($total_salary); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <h5>Total Expenses</h5>
                            <h3><?php echo formatCurrency($total_expenses + $total_supplier_credit + $total_salary); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="row mb-4">
                <!-- Daily Transaction Trend -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <h4>Daily Transaction Trend</h4>
                            <div style="height: 400px;">
                                <canvas id="dailyTrendChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Distribution Chart -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h4>Financial Distribution</h4>
                            <div style="height: 400px;">
                                <canvas id="distributionChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transaction Details Table -->
            <h4>Transaction Details</h4>
            <div class="table-responsive">
                <table class="table table-bordered datatable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Category/Name</th>
                            <th>Description</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Combine all transactions
                        $combined_query = "
                            (SELECT 
                                expense_date as transaction_date,
                                'Expense' as type,
                                category as category_name,
                                description,
                                amount
                            FROM expenses 
                            WHERE expense_date BETWEEN '$start_date' AND '$end_date')
                            UNION ALL
                            (SELECT 
                                st.transaction_date,
                                'Supplier Credit' as type,
                                s.name as category_name,
                                st.description,
                                st.amount
                            FROM supplier_transactions st
                            JOIN suppliers s ON s.id = st.supplier_id
                            WHERE st.transaction_date BETWEEN '$start_date' AND '$end_date'
                            AND st.transaction_type = 'credit')
                            UNION ALL
                            (SELECT 
                                sp.payment_date as transaction_date,
                                'Salary Payment' as type,
                                e.name as category_name,
                                CONCAT('Regular: ', sp.regular_amount, ' | OT: ', sp.ot_amount) as description,
                                sp.total_amount as amount
                            FROM salary_payments sp
                            JOIN employees e ON e.id = sp.employee_id
                            WHERE sp.payment_date BETWEEN '$start_date' AND '$end_date')
                            ORDER BY transaction_date DESC";
                        
                        $combined_result = Database::search($combined_query);
                        while ($row = $combined_result->fetch_assoc()):
                            $rowClass = '';
                            switch($row['type']) {
                                case 'Expense':
                                    $rowClass = 'table-danger';
                                    break;
                                case 'Supplier Credit':
                                    $rowClass = 'table-success';
                                    break;
                                case 'Salary Payment':
                                    $rowClass = 'table-info';
                                    break;
                            }
                        ?>
                            <tr class="<?php echo $rowClass; ?>">
                                <td><?php echo date('Y-m-d', strtotime($row['transaction_date'])); ?></td>
                                <td><?php echo $row['type']; ?></td>
                                <td><?php echo $row['category_name']; ?></td>
                                <td><?php echo $row['description']; ?></td>
                                <td class="text-end"><?php echo formatCurrency($row['amount']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-danger">
                            <th colspan="4">Total Transactions</th>
                            <th class="text-end"><?php echo formatCurrency($total_expenses + $total_supplier_credit + $total_salary); ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Prepare data for daily trend chart
    const dailyData = {
        expenses: <?php 
            $expense_trend = [];
            $current_date = new DateTime($start_date);
            $end = new DateTime($end_date);
            while ($current_date <= $end) {
                $date = $current_date->format('Y-m-d');
                $query = "SELECT COALESCE(SUM(amount), 0) as daily_total 
                          FROM expenses 
                          WHERE DATE(expense_date) = '$date'";
                $result = Database::search($query);
                $expense_trend[] = $result->fetch_assoc()['daily_total'];
                $current_date->modify('+1 day');
            }
            echo json_encode($expense_trend);
        ?>,
        supplier_credits: <?php 
            $supplier_credit_trend = [];
            $current_date = new DateTime($start_date);
            while ($current_date <= $end) {
                $date = $current_date->format('Y-m-d');
                $query = "SELECT COALESCE(SUM(amount), 0) as daily_total 
                          FROM supplier_transactions 
                          WHERE DATE(transaction_date) = '$date' 
                          AND transaction_type = 'credit'";
                $result = Database::search($query);
                $supplier_credit_trend[] = $result->fetch_assoc()['daily_total'];
                $current_date->modify('+1 day');
            }
            echo json_encode($supplier_credit_trend);
        ?>,
        salary_payments: <?php 
            $salary_trend = [];
            $current_date = new DateTime($start_date);
            while ($current_date <= $end) {
                $date = $current_date->format('Y-m-d');
                $query = "SELECT COALESCE(SUM(total_amount), 0) as daily_total 
                          FROM salary_payments 
                          WHERE DATE(payment_date) = '$date'";
                $result = Database::search($query);
                $salary_trend[] = $result->fetch_assoc()['daily_total'];
                $current_date->modify('+1 day');
            }
            echo json_encode($salary_trend);
        ?>
    };

    // Create daily trend chart
    const dailyCtx = document.getElementById('dailyTrendChart').getContext('2d');
    new Chart(dailyCtx, {
        type: 'line',
        data: {
            labels: <?php 
                $dates = [];
                $current_date = new DateTime($start_date);
                while ($current_date <= $end) {
                    $dates[] = $current_date->format('Y-m-d');
                    $current_date->modify('+1 day');
                }
                echo json_encode($dates);
            ?>,
            datasets: [{
                label: 'Expenses',
                data: dailyData.expenses,
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.1)',
                fill: true
            }, {
                label: 'Supplier Credits',
                data: dailyData.supplier_credits,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                fill: true
            }, {
                label: 'Salary Payments',
                data: dailyData.salary_payments,
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rs. ' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': Rs. ' + 
                                   context.raw.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // Create distribution chart
    const distributionCtx = document.getElementById('distributionChart').getContext('2d');
    new Chart(distributionCtx, {
        type: 'doughnut',
        data: {
            labels: ['Expenses', 'Supplier Credits', 'Salary Payments'],
            datasets: [{
                data: [
                    <?php echo $total_expenses; ?>,
                    <?php echo $total_supplier_credit; ?>,
                    <?php echo $total_salary; ?>
                ],
                backgroundColor: [
                    'rgb(255, 99, 132)',
                    'rgb(75, 192, 192)',
                    'rgb(54, 162, 235)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.raw;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((value / total) * 100).toFixed(1);
                            return `${context.label}: Rs. ${value.toLocaleString()} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
});
</script>

<?php include 'footer.php'; ?>
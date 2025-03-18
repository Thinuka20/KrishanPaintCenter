<?php
// report_income_statement.php
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

// Calculate total income from repair invoices
$query = "SELECT COALESCE(SUM(pt.amount), 0) as repair_income
FROM payment_transactions pt
WHERE pt.invoice_type = 'repair'
AND pt.payment_date BETWEEN '$start_date' AND '$end_date'";
$result = Database::search($query);
$repair_income = $result->fetch_assoc()['repair_income'];

// Add income from item invoices
$query = "SELECT COALESCE(SUM(pt.amount), 0) as item_income
FROM payment_transactions pt
WHERE pt.invoice_type = 'item'
AND pt.payment_date BETWEEN '$start_date' AND '$end_date'";
$result = Database::search($query);
$item_income = $result->fetch_assoc()['item_income'];

$total_income = $repair_income + $item_income;

// Prepare data for charts
$daily_data = [];
$current_date = new DateTime($start_date);
$end = new DateTime($end_date);

while ($current_date <= $end) {
    $date = $current_date->format('Y-m-d');

    // Get daily repair income
    $query = "SELECT COALESCE(SUM(total_amount), 0) as repair_income 
              FROM repair_invoices 
              WHERE DATE(invoice_date) = '$date'";
    $result = Database::search($query);
    $repair_amount = $result->fetch_assoc()['repair_income'];

    // Get daily item sales income
    $query = "SELECT COALESCE(SUM(total_amount), 0) as item_income 
              FROM item_invoices 
              WHERE DATE(invoice_date) = '$date'";
    $result = Database::search($query);
    $item_amount = $result->fetch_assoc()['item_income'];

    $daily_data[] = [
        'date' => $date,
        'repair' => floatval($repair_amount),
        'items' => floatval($item_amount),
        'total' => floatval($repair_amount + $item_amount)
    ];

    $current_date->modify('+1 day');
}

// Handle PDF Export
if (isset($_GET['export_pdf'])) {
    // Create new PDF instance
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator('Krishan Paint Center');
    $pdf->SetAuthor('Krishan Paint Center');
    $pdf->SetTitle('Income Statement');

    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // Set margins
    $pdf->SetMargins(10, 10, 10);
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    // Add a page
    $pdf->AddPage('P', 'A4');

    // Set header
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'KRISHAN PAINT CENTER', 0, 1, 'C');
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Income Statement', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'Period: ' . date('Y-m-d', strtotime($start_date)) . ' to ' . date('Y-m-d', strtotime($end_date)), 0, 1, 'C');
    $pdf->Ln(5);

    // Summary section
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 7, 'Income Summary', 0, 1);
    $pdf->SetFont('helvetica', '', 10);

    // Summary Cards in PDF
    $pdf->Cell(60, 6, 'Repair Services Income:', 1);
    $pdf->Cell(60, 6, formatCurrency($repair_income), 1);
    $pdf->Ln();
    $pdf->Cell(60, 6, 'Item Sales Income:', 1);
    $pdf->Cell(60, 6, formatCurrency($item_income), 1);
    $pdf->Ln();
    $pdf->Cell(60, 6, 'Total Income:', 1);
    $pdf->Cell(60, 6, formatCurrency($total_income), 1);
    $pdf->Ln(10);

    // Income Details Section
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 7, 'Income Details', 0, 1);
    $pdf->SetFont('helvetica', '', 10);

    // Table header for income
    $pdf->SetFillColor(240, 240, 240);
    $headers = array('Date', 'Invoice #', 'Customer', 'Type', 'Amount');
    $widths = array(30, 35, 60, 30, 35);

    foreach ($headers as $i => $header) {
        $pdf->Cell($widths[$i], 7, $header, 1, 0, 'C', true);
    }
    $pdf->Ln();

    // Reset total for detailed calculation
    $total_income = 0;

    // Get all income transactions
    $query = "SELECT 
        ri.invoice_date,
        ri.invoice_number,
        c.name as customer_name,
        v.registration_number,
        'Repair Service' as invoice_type,
        ri.total_amount
    FROM repair_invoices ri
    LEFT JOIN vehicles v ON ri.vehicle_id = v.id
    LEFT JOIN customers c ON v.customer_id = c.id
    WHERE ri.invoice_date BETWEEN '$start_date' AND '$end_date'

    UNION ALL

    SELECT 
        ii.invoice_date,
        ii.invoice_number,
        c.name as customer_name,
        NULL as registration_number,
        'Item Sale' as invoice_type,
        ii.total_amount
    FROM item_invoices ii
    LEFT JOIN customers c ON ii.customer_id = c.id
    WHERE ii.invoice_date BETWEEN '$start_date' AND '$end_date'
    ORDER BY invoice_date";

    $result = Database::search($query);

    while ($row = $result->fetch_assoc()) {
        $pdf->Cell($widths[0], 6, date('Y-m-d', strtotime($row['invoice_date'])), 1, 0, 'C');
        $pdf->Cell($widths[1], 6, $row['invoice_number'], 1, 0, 'C');

        $customer_info = $row['customer_name'];
        if ($row['registration_number']) {
            $customer_info .= ' (' . $row['registration_number'] . ')';
        }
        $pdf->Cell($widths[2], 6, $customer_info, 1);

        $pdf->Cell($widths[3], 6, $row['invoice_type'], 1, 0, 'C');
        $pdf->Cell($widths[4], 6, formatCurrency($row['total_amount']), 1, 0, 'R');
        $pdf->Ln();
        $total_income += $row['total_amount'];
    }

    // Total Income row
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(array_sum($widths) - $widths[4], 6, 'Total Income', 1, 0, 'R');
    $pdf->Cell($widths[4], 6, formatCurrency($total_income), 1, 0, 'R');

    // Output PDF
    $pdf->Output('Income_Statement.pdf', 'I');
    exit;
    exit;
}

include 'header.php';
?>

<div class="container content">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Income Statement</h2>
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

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5>Repair Services Income</h5>
                            <h3><?php echo formatCurrency($repair_income); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5>Item Sales Income</h5>
                            <h3><?php echo formatCurrency($item_income); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5>Total Income</h5>
                            <h3><?php echo formatCurrency($total_income); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="row mb-4">
                <!-- Daily Income Trend -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <h4>Daily Income Trend</h4>
                            <div style="height: 400px; position: relative;">
                                <canvas id="dailyIncomeChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Income Distribution -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h4>Income Distribution</h4>
                            <div style="height: 400px; position: relative;">
                                <canvas id="incomeDistributionChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Income Details Section -->
            <h4>Income Details</h4>
            <div class="table-responsive">
                <table class="table table-bordered datatable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Invoice #</th>
                            <th>Customer</th>
                            <th>Type</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT 
                            invoice_date,
                            invoice_number,
                            customer_name,
                            registration_number,
                            invoice_type,
                            total_amount
                        FROM (
                            SELECT 
                                ri.invoice_date,
                                ri.invoice_number,
                                c.name as customer_name,
                                v.registration_number,
                                'Repair Service' as invoice_type,
                                ri.total_amount
                            FROM repair_invoices ri
                            LEFT JOIN vehicles v ON ri.vehicle_id = v.id
                            LEFT JOIN customers c ON v.customer_id = c.id
                            WHERE ri.invoice_date BETWEEN '$start_date' AND '$end_date'

                            UNION ALL

                            SELECT 
                                ii.invoice_date,
                                ii.invoice_number,
                                c.name as customer_name,
                                NULL as registration_number,
                                'Item Sale' as invoice_type,
                                ii.total_amount
                            FROM item_invoices ii
                            LEFT JOIN customers c ON ii.customer_id = c.id
                            WHERE ii.invoice_date BETWEEN '$start_date' AND '$end_date'
                        ) combined_invoices
                        ORDER BY invoice_date";

                        $result = Database::search($query);
                        $total_income = 0;

                        while ($row = $result->fetch_assoc()):
                            $total_income += $row['total_amount'];
                            $customer_info = $row['customer_name'];
                            if ($row['registration_number']) {
                                $customer_info .= ' (' . $row['registration_number'] . ')';
                            }
                        ?>
                            <tr>
                                <td><?php echo date('Y-m-d', strtotime($row['invoice_date'])); ?></td>
                                <td><?php echo $row['invoice_number']; ?></td>
                                <td><?php echo $customer_info; ?></td>
                                <td><?php echo $row['invoice_type']; ?></td>
                                <td class="text-end"><?php echo formatCurrency($row['total_amount']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-primary">
                            <th colspan="4">Total Income</th>
                            <th class="text-end"><?php echo formatCurrency($total_income); ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Parse the PHP data for charts
        const dailyData = <?php echo json_encode($daily_data); ?>;

        // Daily Income Trend Chart
        const dailyCtx = document.getElementById('dailyIncomeChart').getContext('2d');
        new Chart(dailyCtx, {
            type: 'line',
            data: {
                labels: dailyData.map(item => item.date),
                datasets: [{
                        label: 'Repair Services',
                        data: dailyData.map(item => item.repair),
                        borderColor: 'rgb(23, 162, 184)',
                        backgroundColor: 'rgba(23, 162, 184, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Item Sales',
                        data: dailyData.map(item => item.items),
                        borderColor: 'rgb(40, 167, 69)',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Total Income',
                        data: dailyData.map(item => item.total),
                        borderColor: 'rgb(0, 123, 255)',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': Rs. ' +
                                    context.raw.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            parser: 'yyyy-MM-dd',
                            unit: 'day',
                            displayFormats: {
                                day: 'MMM d'
                            }
                        },
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    },
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

        // Income Distribution Chart
        const distributionCtx = document.getElementById('incomeDistributionChart').getContext('2d');
        new Chart(distributionCtx, {
            type: 'doughnut',
            data: {
                labels: ['Repair Services', 'Item Sales'],
                datasets: [{
                    data: [<?php echo $repair_income; ?>, <?php echo $item_income; ?>],
                    backgroundColor: [
                        'rgba(23, 162, 184, 0.8)',
                        'rgba(40, 167, 69, 0.8)'
                    ],
                    borderWidth: 1
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
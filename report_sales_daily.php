<?php
// report_sales_daily.php
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
        'repair_sales' => 0,
        'item_sales' => 0,
        'total_sales' => 0,
        'invoices' => 0
    );

    // Get repair sales
    $query = "SELECT 
                DATE(invoice_date) as sale_date,
                SUM(total_amount) as repair_sales,
                COUNT(*) as repair_invoices
             FROM repair_invoices 
             WHERE invoice_date BETWEEN '$start_date' AND '$end_date'
             GROUP BY DATE(invoice_date)";
    
    $repair_result = Database::search($query);
    
    $repair_sales = array();
    while ($row = $repair_result->fetch_assoc()) {
        $repair_sales[$row['sale_date']] = $row;
        $totals['repair_sales'] += $row['repair_sales'];
        $totals['invoices'] += $row['repair_invoices'];
    }

    // Get item sales
    $query = "SELECT 
                DATE(invoice_date) as sale_date,
                SUM(total_amount) as item_sales,
                COUNT(*) as item_invoices
             FROM item_invoices 
             WHERE invoice_date BETWEEN '$start_date' AND '$end_date'
             GROUP BY DATE(invoice_date)";
    
    $item_result = Database::search($query);
    
    $item_sales = array();
    while ($row = $item_result->fetch_assoc()) {
        $item_sales[$row['sale_date']] = $row;
        $totals['item_sales'] += $row['item_sales'];
        $totals['invoices'] += $row['item_invoices'];
    }

    // Combine data
    $dates = array_unique(array_merge(array_keys($repair_sales), array_keys($item_sales)));
    sort($dates);

    foreach ($dates as $date) {
        $repair_amount = isset($repair_sales[$date]) ? $repair_sales[$date]['repair_sales'] : 0;
        $item_amount = isset($item_sales[$date]) ? $item_sales[$date]['item_sales'] : 0;
        $data[] = array(
            'date' => $date,
            'repair_sales' => $repair_amount,
            'item_sales' => $item_amount,
            'total_sales' => $repair_amount + $item_amount,
            'invoices' => (isset($repair_sales[$date]) ? $repair_sales[$date]['repair_invoices'] : 0) +
                         (isset($item_sales[$date]) ? $item_sales[$date]['item_invoices'] : 0)
        );
    }

    $totals['total_sales'] = $totals['repair_sales'] + $totals['item_sales'];

    // Generate PDF
    $pdf = new ReportPDF('L', 'Daily Sales Report');
    $pdf->generateDailySalesReport(
        $data,
        $totals,
        date('Y-m-d', strtotime($start_date)) . ' to ' . date('Y-m-d', strtotime($end_date))
    );
    $pdf->Output('Daily_Sales_Report_' . date('Y-m-d') . '.pdf', 'I');
    exit;
}

include 'header.php';
?>

<div class="container content">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Daily Sales Report</h2>
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

            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Repair Sales</th>
                            <th>Item Sales</th>
                            <th>Total Sales</th>
                            <th>No. of Invoices</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $total_repair_sales = 0;
                        $total_item_sales = 0;
                        $total_invoices = 0;

                        // Get repair sales
                        $query = "SELECT 
                                    DATE(invoice_date) as sale_date,
                                    SUM(total_amount) as repair_sales,
                                    COUNT(*) as repair_invoices
                                 FROM repair_invoices 
                                 WHERE invoice_date BETWEEN '$start_date' AND '$end_date'
                                 GROUP BY DATE(invoice_date)";
                        
                        $repair_result = Database::search($query);
                        $repair_sales = array();
                        while ($row = $repair_result->fetch_assoc()) {
                            $repair_sales[$row['sale_date']] = $row;
                            $total_repair_sales += $row['repair_sales'];
                            $total_invoices += $row['repair_invoices'];
                        }

                        // Get item sales
                        $query = "SELECT 
                                    DATE(invoice_date) as sale_date,
                                    SUM(total_amount) as item_sales,
                                    COUNT(*) as item_invoices
                                 FROM item_invoices 
                                 WHERE invoice_date BETWEEN '$start_date' AND '$end_date'
                                 GROUP BY DATE(invoice_date)";
                        
                        $item_result = Database::search($query);
                        $item_sales = array();
                        while ($row = $item_result->fetch_assoc()) {
                            $item_sales[$row['sale_date']] = $row;
                            $total_item_sales += $row['item_sales'];
                            $total_invoices += $row['item_invoices'];
                        }

                        $dates = array_unique(array_merge(array_keys($repair_sales), array_keys($item_sales)));
                        sort($dates);

                        foreach ($dates as $date) {
                            $repair_amount = isset($repair_sales[$date]) ? $repair_sales[$date]['repair_sales'] : 0;
                            $item_amount = isset($item_sales[$date]) ? $item_sales[$date]['item_sales'] : 0;
                            $daily_total = $repair_amount + $item_amount;
                            $invoice_count = (isset($repair_sales[$date]) ? $repair_sales[$date]['repair_invoices'] : 0) +
                                           (isset($item_sales[$date]) ? $item_sales[$date]['item_invoices'] : 0);
                        ?>
                            <tr>
                                <td><?php echo date('Y-m-d', strtotime($date)); ?></td>
                                <td class="text-end"><?php echo formatCurrency($repair_amount); ?></td>
                                <td class="text-end"><?php echo formatCurrency($item_amount); ?></td>
                                <td class="text-end"><?php echo formatCurrency($daily_total); ?></td>
                                <td class="text-center"><?php echo $invoice_count; ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td>Total</td>
                            <td class="text-end"><?php echo formatCurrency($total_repair_sales); ?></td>
                            <td class="text-end"><?php echo formatCurrency($total_item_sales); ?></td>
                            <td class="text-end"><?php echo formatCurrency($total_repair_sales + $total_item_sales); ?></td>
                            <td class="text-center"><?php echo $total_invoices; ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="row mt-4">
                <div class="col-md-6">
                    <canvas id="salesChart"></canvas>
                </div>
                <div class="col-md-6">
                    <canvas id="invoiceChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const dates = <?php echo json_encode($dates); ?>;
const repairSales = <?php echo json_encode(array_map(function($date) use ($repair_sales) {
    return isset($repair_sales[$date]) ? $repair_sales[$date]['repair_sales'] : 0;
}, $dates)); ?>;
const itemSales = <?php echo json_encode(array_map(function($date) use ($item_sales) {
    return isset($item_sales[$date]) ? $item_sales[$date]['item_sales'] : 0;
}, $dates)); ?>;
const invoiceCounts = <?php echo json_encode(array_map(function($date) use ($repair_sales, $item_sales) {
    return (isset($repair_sales[$date]) ? $repair_sales[$date]['repair_invoices'] : 0) +
           (isset($item_sales[$date]) ? $item_sales[$date]['item_invoices'] : 0);
}, $dates)); ?>;

// Sales Chart
new Chart(document.getElementById('salesChart'), {
    type: 'line',
    data: {
        labels: dates,
        datasets: [{
            label: 'Repair Sales',
            data: repairSales,
            borderColor: 'rgb(75, 192, 192)',
            tension: 0.1
        }, {
            label: 'Item Sales',
            data: itemSales,
            borderColor: 'rgb(255, 99, 132)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Daily Sales Trend'
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Invoice Chart
new Chart(document.getElementById('invoiceChart'), {
    type: 'bar',
    data: {
        labels: dates,
        datasets: [{
            label: 'Number of Invoices',
            data: invoiceCounts,
            backgroundColor: 'rgb(54, 162, 235)'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Daily Invoice Count'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
</script>

<?php include 'footer.php'; ?>
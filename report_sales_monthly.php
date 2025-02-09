<?php
// report_sales_monthly.php - Monthly sales report
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

$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');

// Check if PDF export is requested
if (isset($_GET['export_pdf'])) {
    $start_date = "$year-$month-01";
    $end_date = date('Y-m-t', strtotime($start_date));
    
    // Create new PDF instance
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator('Krishan Paint Center');
    $pdf->SetAuthor('Krishan Paint Center');
    $pdf->SetTitle('Monthly Sales Report - ' . date('F Y', strtotime($start_date)));

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
    $pdf->Cell(0, 10, 'Monthly Sales Report', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(0, 5, 'Month: ' . date('F Y', strtotime($start_date)), 0, 1, 'C');
    $pdf->Ln(5);

    // Summary section
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 7, 'Summary', 0, 1);
    $pdf->SetFont('helvetica', '', 10);

    // Get summary data
    $query = "SELECT SUM(total_amount) as total 
              FROM (
                  SELECT total_amount FROM repair_invoices 
                  WHERE invoice_date BETWEEN '$start_date' AND '$end_date'
                  UNION ALL
                  SELECT total_amount FROM item_invoices 
                  WHERE invoice_date BETWEEN '$start_date' AND '$end_date'
              ) as sales";
    $result = Database::search($query);
    $total_sales = $result->fetch_assoc()['total'] ?? 0;

    $query = "SELECT COUNT(*) as count, SUM(total_amount) as total 
              FROM repair_invoices 
              WHERE invoice_date BETWEEN '$start_date' AND '$end_date'";
    $result = Database::search($query);
    $repairs = $result->fetch_assoc();

    $query = "SELECT 
    COUNT(DISTINCT ii.id) as invoices,
    SUM(iid.quantity) as items,
    SUM(iid.quantity * iid.unit_price) as total
FROM item_invoices ii
INNER JOIN item_invoice_details iid ON ii.id = iid.item_invoice_id
              WHERE ii.invoice_date BETWEEN '$start_date' AND '$end_date'";
    $result = Database::search($query);
    $items = $result->fetch_assoc();

    // Print summary
    $pdf->Cell(90, 6, 'Total Sales: ' . formatCurrency($total_sales), 0, 1);
    $pdf->Cell(90, 6, 'Total Repairs: ' . $repairs['count'] . ' (' . formatCurrency($repairs['total']) . ')', 0, 1);
    $pdf->Cell(90, 6, 'Total Items Sold: ' . $items['items'] . ' (' . formatCurrency($items['total']) . ')', 0, 1);
    $pdf->Ln(5);

    // Daily Sales Table
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 7, 'Daily Sales Breakdown', 0, 1);
    
    // Table header
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(240, 240, 240);
    
    $headers = array('Date', 'Repair Sales', 'Item Sales', 'Total Sales');
    $widths = array(40, 50, 50, 50);
    
    foreach ($headers as $i => $header) {
        $pdf->Cell($widths[$i], 7, $header, 1, 0, 'C', true);
    }
    $pdf->Ln();

    // Table data
    $pdf->SetFont('helvetica', '', 9);
    
    $current_date = new DateTime($start_date);
    $end = new DateTime($end_date);
    
    $query = "SELECT DATE(invoice_date) as date, SUM(total_amount) as total 
              FROM repair_invoices 
              WHERE invoice_date BETWEEN '$start_date' AND '$end_date' 
              GROUP BY DATE(invoice_date)";
    $repair_result = Database::search($query);
    $repair_sales = [];
    while ($row = $repair_result->fetch_assoc()) {
        $repair_sales[$row['date']] = $row['total'];
    }
    
    $query = "SELECT DATE(invoice_date) as date, SUM(total_amount) as total 
              FROM item_invoices 
              WHERE invoice_date BETWEEN '$start_date' AND '$end_date' 
              GROUP BY DATE(invoice_date)";
    $item_result = Database::search($query);
    $item_sales = [];
    while ($row = $item_result->fetch_assoc()) {
        $item_sales[$row['date']] = $row['total'];
    }
    
    while ($current_date <= $end) {
        $date = $current_date->format('Y-m-d');
        $repair_total = $repair_sales[$date] ?? 0;
        $item_total = $item_sales[$date] ?? 0;
        $daily_total = $repair_total + $item_total;
        
        if ($daily_total > 0) {
            $pdf->Cell($widths[0], 6, date('Y-m-d (D)', strtotime($date)), 1, 0, 'C');
            $pdf->Cell($widths[1], 6, formatCurrency($repair_total), 1, 0, 'R');
            $pdf->Cell($widths[2], 6, formatCurrency($item_total), 1, 0, 'R');
            $pdf->Cell($widths[3], 6, formatCurrency($daily_total), 1, 0, 'R');
            $pdf->Ln();
        }
        $current_date->modify('+1 day');
    }

    // Table footer
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell($widths[0], 7, 'Monthly Total', 1, 0, 'R');
    $pdf->Cell($widths[1], 7, formatCurrency($repairs['total'] ?? 0), 1, 0, 'R');
    $pdf->Cell($widths[2], 7, formatCurrency($items['total'] ?? 0), 1, 0, 'R');
    $pdf->Cell($widths[3], 7, formatCurrency($total_sales), 1, 0, 'R');
    $pdf->Ln(15);

    // Output PDF
    $pdf->Output('Monthly_Sales_Report_' . date('Y_m', strtotime($start_date)) . '.pdf', 'I');
    exit;
}

include 'header.php';
?>

<div class="container content">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Monthly Sales Report</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="?year=<?php echo $year; ?>&month=<?php echo $month; ?>&export_pdf=1" 
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
                        <label>Year</label>
                        <select name="year" class="form-control">
                            <?php
                            $current_year = date('Y');
                            for ($y = $current_year; $y >= $current_year - 5; $y--):
                            ?>
                            <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>>
                                <?php echo $y; ?>
                            </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Month</label>
                        <select name="month" class="form-control">
                            <?php
                            for ($m = 1; $m <= 12; $m++):
                            ?>
                            <option value="<?php echo $m; ?>" <?php echo $m == $month ? 'selected' : ''; ?>>
                                <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                            </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary mt-4">Generate Report</button>
                </div>
            </form>

            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5>Total Sales</h5>
                            <?php
                            $start_date = "$year-$month-01";
                            $end_date = date('Y-m-t', strtotime($start_date));
                            
                            $query = "SELECT SUM(total_amount) as total 
                                     FROM (
                                         SELECT total_amount FROM repair_invoices 
                                         WHERE invoice_date BETWEEN '$start_date' AND '$end_date'
                                         UNION ALL
                                         SELECT total_amount FROM item_invoices 
                                         WHERE invoice_date BETWEEN '$start_date' AND '$end_date'
                                     ) as sales";
                            $result = Database::search($query);
                            $total_sales = $result->fetch_assoc()['total'] ?? 0;
                            ?>
                            <h3><?php echo formatCurrency($total_sales); ?></h3>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5>Total Repairs</h5>
                            <?php
                            $query = "SELECT COUNT(*) as count, SUM(total_amount) as total 
                                     FROM repair_invoices 
                                     WHERE invoice_date BETWEEN '$start_date' AND '$end_date'";
                            $result = Database::search($query);
                            $repairs = $result->fetch_assoc();
                            ?>
                            <h3><?php echo $repairs['count']; ?> repairs</h3>
                            <p><?php echo formatCurrency($repairs['total']); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5>Total Items Sold</h5>
                            <?php
                            $query = "SELECT 
                            COUNT(DISTINCT ii.id) as invoices,
                            SUM(iid.quantity) as items,
                            SUM(iid.quantity * iid.unit_price) as total
                          FROM item_invoices ii
                          INNER JOIN item_invoice_details iid ON ii.id = iid.item_invoice_id
                                     WHERE ii.invoice_date BETWEEN '$start_date' AND '$end_date'";
                            $result = Database::search($query);
                            $items = $result->fetch_assoc();
                            ?>
                            <h3><?php echo $items['items']; ?> items</h3>
                            <p><?php echo formatCurrency($items['total']); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <h4>Daily Sales Breakdown</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Repairs</th>
                                    <th>Items</th>
                                    <th>Total Sales</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $current_date = new DateTime($start_date);
                                $end = new DateTime($end_date);
                                
                                $query = "SELECT DATE(invoice_date) as date, SUM(total_amount) as total 
                                         FROM repair_invoices 
                                         WHERE invoice_date BETWEEN '$start_date' AND '$end_date' 
                                         GROUP BY DATE(invoice_date)";
                                $repair_result = Database::search($query);
                                $repair_sales = [];
                                while ($row = $repair_result->fetch_assoc()) {
                                    $repair_sales[$row['date']] = $row['total'];
                                }
                                
                                $query = "SELECT DATE(invoice_date) as date, SUM(total_amount) as total 
                                         FROM item_invoices 
                                         WHERE invoice_date BETWEEN '$start_date' AND '$end_date' 
                                         GROUP BY DATE(invoice_date)";
                                $item_result = Database::search($query);
                                $item_sales = [];
                                while ($row = $item_result->fetch_assoc()) {
                                    $item_sales[$row['date']] = $row['total'];
                                }
                                
                                while ($current_date <= $end) {
                                    $date = $current_date->format('Y-m-d');
                                    $repair_total = $repair_sales[$date] ?? 0;
                                    $item_total = $item_sales[$date] ?? 0;
                                    $daily_total = $repair_total + $item_total;
                                    
                                    if ($daily_total > 0):
                                ?>
                                <tr>
                                    <td><?php echo date('Y-m-d (D)', strtotime($date)); ?></td>
                                    <td><?php echo formatCurrency($repair_total); ?></td>
                                    <td><?php echo formatCurrency($item_total); ?></td>
                                    <td><?php echo formatCurrency($daily_total); ?></td>
                                </tr>
                                <?php
                                    endif;
                                    $current_date->modify('+1 day');
                                }
                                ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>Monthly Total</th>
                                    <th><?php echo formatCurrency($repairs['total'] ?? 0); ?></th>
                                    <th><?php echo formatCurrency($items['total'] ?? 0); ?></th>
                                    <th><?php echo formatCurrency($total_sales); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-6">
                    <h4>Top 10 Selling Items</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Quantity</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query = "SELECT 
                                            i.name,
                                            SUM(iid.quantity) as total_quantity,
                                            SUM(iid.quantity * iid.unit_price) as total_revenue
                                         FROM item_invoice_details iid
                                         LEFT JOIN items i ON iid.item_id = i.id
                                         LEFT JOIN item_invoices ii ON iid.item_invoice_id = ii.id
                                         WHERE ii.invoice_date BETWEEN '$start_date' AND '$end_date'
                                         GROUP BY i.id
                                         ORDER BY total_quantity DESC
                                         LIMIT 10";
                                $result = Database::search($query);
                                while ($row = $result->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><?php echo $row['name']; ?></td>
                                    <td><?php echo $row['total_quantity']; ?></td>
                                    <td><?php echo formatCurrency($row['total_revenue']); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="col-md-6">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Chart data preparation
const repairTotal = <?php echo $repairs['total'] ?? 0; ?>;
const itemTotal = <?php echo $items['total'] ?? 0; ?>;

new Chart(document.getElementById('salesChart'), {
    type: 'pie',
    data: {
        labels: ['Repair Sales', 'Item Sales'],
        datasets: [{
            data: [repairTotal, itemTotal],
            backgroundColor: ['rgb(75, 192, 192)', 'rgb(255, 99, 132)']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Sales Distribution'
            }
        }
    }
});
</script>

<?php include 'footer.php'; ?>
<?php
// report_sales_items.php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';
require_once('tcpdf/tcpdf.php');

checkLogin();

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Check if PDF export is requested
if (isset($_GET['export_pdf'])) {
    // Create new PDF document
    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator('Krishan Paint Center');
    $pdf->SetAuthor('Krishan Paint Center');
    $pdf->SetTitle('Item Sales Report ' . date('Y-m-d'));

    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // Set margins
    $pdf->SetMargins(10, 10, 10);
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    // Add a page
    $pdf->AddPage('L', 'A4');

    // Set header content
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'KRISHAN PAINT CENTER', 0, 1, 'C');
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Item Sales Report', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(0, 5, 'Period: ' . date('Y-m-d', strtotime($start_date)) . ' to ' . date('Y-m-d', strtotime($end_date)), 0, 1, 'C');
    $pdf->Ln(5);

    // Summary Section
    $query = "SELECT 
                SUM(quantity) as total_quantity,
                SUM(subtotal) as total_amount
              FROM (
                  SELECT quantity, subtotal FROM item_invoice_details iid
                  JOIN item_invoices ii ON iid.item_invoice_id = ii.id
                  WHERE ii.invoice_date BETWEEN '$start_date' AND '$end_date'
                  UNION ALL
                  SELECT quantity, subtotal FROM repair_invoice_items rii
                  JOIN repair_invoices ri ON rii.repair_invoice_id = ri.id
                  WHERE ri.invoice_date BETWEEN '$start_date' AND '$end_date'
              ) as combined_sales";
    
    $result = Database::search($query);
    $summary = $result->fetch_assoc();

    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 7, 'Summary', 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(90, 6, 'Total Items Sold: ' . number_format($summary['total_quantity']), 0, 1);
    $pdf->Cell(90, 6, 'Total Sales Amount: ' . formatCurrency($summary['total_amount']), 0, 1);
    $pdf->Ln(5);

    // Detailed Sales Table
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 7, 'Item Sales Details', 0, 1);

    // Table Header
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(240, 240, 240);
    
    $headers = array('Item Code', 'Item Name', 'Quantity Sold', 'Unit Price', 'Total Sales', 'Last Sale Date');
    $widths = array(30, 80, 30, 35, 35, 40);
    
    foreach ($headers as $i => $header) {
        $pdf->Cell($widths[$i], 7, $header, 1, 0, 'C', true);
    }
    $pdf->Ln();

    // Table Data
    $pdf->SetFont('helvetica', '', 9);

    $query = "SELECT 
                i.item_code,
                i.name,
                i.unit_price,
                SUM(COALESCE(ri.quantity, 0) + COALESCE(ii.quantity, 0)) as total_quantity,
                SUM(COALESCE(ri.subtotal, 0) + COALESCE(ii.subtotal, 0)) as total_amount,
                MAX(GREATEST(COALESCE(ri.last_date, '1900-01-01'), COALESCE(ii.last_date, '1900-01-01'))) as last_sale_date
              FROM items i
              LEFT JOIN (
                  SELECT 
                      item_id,
                      SUM(quantity) as quantity,
                      SUM(subtotal) as subtotal,
                      MAX(ri.invoice_date) as last_date
                  FROM repair_invoice_items rii
                  JOIN repair_invoices ri ON rii.repair_invoice_id = ri.id
                  WHERE ri.invoice_date BETWEEN '$start_date' AND '$end_date'
                  GROUP BY item_id
              ) ri ON i.id = ri.item_id
              LEFT JOIN (
                  SELECT 
                      item_id,
                      SUM(quantity) as quantity,
                      SUM(subtotal) as subtotal,
                      MAX(ii.invoice_date) as last_date
                  FROM item_invoice_details iid
                  JOIN item_invoices ii ON iid.item_invoice_id = ii.id
                  WHERE ii.invoice_date BETWEEN '$start_date' AND '$end_date'
                  GROUP BY item_id
              ) ii ON i.id = ii.item_id
              WHERE (ri.quantity IS NOT NULL OR ii.quantity IS NOT NULL)
              GROUP BY i.id, i.item_code, i.name, i.unit_price
              ORDER BY total_quantity DESC";

    $result = Database::search($query);
    
    while ($row = $result->fetch_assoc()) {
        $pdf->Cell($widths[0], 6, $row['item_code'], 1);
        $pdf->Cell($widths[1], 6, $row['name'], 1);
        $pdf->Cell($widths[2], 6, number_format($row['total_quantity']), 1, 0, 'R');
        $pdf->Cell($widths[3], 6, formatCurrency($row['unit_price']), 1, 0, 'R');
        $pdf->Cell($widths[4], 6, formatCurrency($row['total_amount']), 1, 0, 'R');
        $pdf->Cell($widths[5], 6, date('Y-m-d', strtotime($row['last_sale_date'])), 1, 0, 'C');
        $pdf->Ln();
    }

    // Signature section
    $pdf->Ln(20);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(90, 6, '............................', 0, 0, 'C');
    $pdf->Cell(90, 6, '............................', 0, 0, 'C');
    $pdf->Cell(90, 6, '............................', 0, 0, 'C');
    $pdf->Ln();
    $pdf->Cell(90, 6, 'Prepared By', 0, 0, 'C');
    $pdf->Cell(90, 6, 'Checked By', 0, 0, 'C');
    $pdf->Cell(90, 6, 'Approved By', 0, 0, 'C');

    // Output PDF
    $pdf->Output('Item_Sales_Report_' . date('Y-m-d') . '.pdf', 'I');
    exit;
}

include 'header.php';
?>

<div class="container content">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Item Sales Report</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&export_pdf=1" 
               class="btn btn-secondary">
                <i class="fas fa-file-pdf"></i> Export PDF
            </a>
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

            <!-- Summary Cards -->
            <div class="row mb-4">
                <?php
                $query = "SELECT 
                            SUM(quantity) as total_quantity,
                            SUM(subtotal) as total_amount
                          FROM (
                              SELECT quantity, subtotal FROM item_invoice_details iid
                              JOIN item_invoices ii ON iid.item_invoice_id = ii.id
                              WHERE ii.invoice_date BETWEEN '$start_date' AND '$end_date'
                              UNION ALL
                              SELECT quantity, subtotal FROM repair_invoice_items rii
                              JOIN repair_invoices ri ON rii.repair_invoice_id = ri.id
                              WHERE ri.invoice_date BETWEEN '$start_date' AND '$end_date'
                          ) as combined_sales";
                
                $result = Database::search($query);
                $summary = $result->fetch_assoc();
                ?>
                <div class="col-md-6">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5>Total Items Sold</h5>
                            <h3><?php echo number_format($summary['total_quantity']); ?> items</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5>Total Sales Amount</h5>
                            <h3><?php echo formatCurrency($summary['total_amount']); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sales Details Table -->
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Item Code</th>
                            <th>Item Name</th>
                            <th>Quantity Sold</th>
                            <th>Unit Price</th>
                            <th>Total Sales</th>
                            <th>Last Sale Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT 
                                    i.item_code,
                                    i.name,
                                    i.unit_price,
                                    SUM(COALESCE(ri.quantity, 0) + COALESCE(ii.quantity, 0)) as total_quantity,
                                    SUM(COALESCE(ri.subtotal, 0) + COALESCE(ii.subtotal, 0)) as total_amount,
                                    MAX(GREATEST(COALESCE(ri.last_date, '1900-01-01'), COALESCE(ii.last_date, '1900-01-01'))) as last_sale_date
                                FROM items i
                                LEFT JOIN (
                                    SELECT 
                                        item_id,
                                        SUM(quantity) as quantity,
                                        SUM(subtotal) as subtotal,
                                        MAX(ri.invoice_date) as last_date
                                    FROM repair_invoice_items rii
                                    JOIN repair_invoices ri ON rii.repair_invoice_id = ri.id
                                    WHERE ri.invoice_date BETWEEN '$start_date' AND '$end_date'
                                    GROUP BY item_id
                                ) ri ON i.id = ri.item_id
                                LEFT JOIN (
                                    SELECT 
                                        item_id,
                                        SUM(quantity) as quantity,
                                        SUM(subtotal) as subtotal,
                                        MAX(ii.invoice_date) as last_date
                                    FROM item_invoice_details iid
                                    JOIN item_invoices ii ON iid.item_invoice_id = ii.id
                                    WHERE ii.invoice_date BETWEEN '$start_date' AND '$end_date'
                                    GROUP BY item_id
                                ) ii ON i.id = ii.item_id
                                WHERE (ri.quantity IS NOT NULL OR ii.quantity IS NOT NULL)
                                GROUP BY i.id, i.item_code, i.name, i.unit_price
                                ORDER BY total_quantity DESC";

                        $result = Database::search($query);
                        
                        while ($row = $result->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo $row['item_code']; ?></td>
                            <td><?php echo $row['name']; ?></td>
                            <td class="text-end"><?php echo number_format($row['total_quantity']); ?></td>
                            <td class="text-end"><?php echo formatCurrency($row['unit_price']); ?></td>
                            <td class="text-end"><?php echo formatCurrency($row['total_amount']); ?></td>
                            <td class="text-center"><?php echo date('Y-m-d', strtotime($row['last_sale_date'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Sales Chart -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <canvas id="itemSalesChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
<?php
// Prepare data for chart
$result->data_seek(0);
$labels = [];
$quantities = [];
$amounts = [];

while ($row = $result->fetch_assoc()) {
    if($row['total_quantity'] > 0) {
        $labels[] = $row['name'];
        $quantities[] = $row['total_quantity'];
        $amounts[] = $row['total_amount'];
    }
}
?>

// Chart data
const labels = <?php echo json_encode($labels); ?>;
const quantities = <?php echo json_encode($quantities); ?>;
const amounts = <?php echo json_encode($amounts); ?>;

// Create chart
new Chart(document.getElementById('itemSalesChart'), {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [{
            label: 'Quantity Sold',
            data: quantities,
            backgroundColor: 'rgba(75, 192, 192, 0.5)',
            borderColor: 'rgb(75, 192, 192)',
            borderWidth: 1,
            yAxisID: 'y'
        }, {
            label: 'Sales Amount (Rs)',
            data: amounts,
            backgroundColor: 'rgba(255, 99, 132, 0.5)',
            borderColor: 'rgb(255, 99, 132)',
            borderWidth: 1,
            yAxisID: 'y1'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Item Sales Analysis'
            }
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'Quantity'
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'Amount (Rs)'
                },
                grid: {
                    drawOnChartArea: false
                }
            }
        }
    }
});
</script>

<?php include 'footer.php'; ?>
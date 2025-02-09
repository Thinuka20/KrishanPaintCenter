<?php
// report_stock_status.php - Stock status report
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

// Check if PDF export is requested
if (isset($_GET['export_pdf'])) {
    // Create new PDF instance
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator('Krishan Paint Center');
    $pdf->SetAuthor('Krishan Paint Center');
    $pdf->SetTitle('Stock Status Report');

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
    $pdf->Cell(0, 10, 'Stock Status Report', 0, 1, 'C');
    $pdf->Cell(0, 5, 'Date: ' . date('Y-m-d'), 0, 1, 'C');
    $pdf->Ln(5);

    // Summary section
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 7, 'Stock Summary', 0, 1);
    $pdf->SetFont('helvetica', '', 10);

    // Get summary statistics
    $query = "SELECT COUNT(*) as count FROM items WHERE stock_quantity <= minimum_stock";
    $result = Database::search($query);
    $low_stock = $result->fetch_assoc()['count'];

    $query = "SELECT COUNT(*) as count FROM items WHERE stock_quantity > minimum_stock";
    $result = Database::search($query);
    $normal_stock = $result->fetch_assoc()['count'];

    $query = "SELECT COUNT(*) as count, SUM(stock_quantity * unit_price) as value FROM items";
    $result = Database::search($query);
    $total = $result->fetch_assoc();

    // Print summary statistics
    $pdf->Cell(90, 6, 'Low Stock Items: ' . $low_stock, 0, 1);
    $pdf->Cell(90, 6, 'Normal Stock Items: ' . $normal_stock, 0, 1);
    $pdf->Cell(90, 6, 'Total Stock Value: ' . formatCurrency($total['value']), 0, 1);

    if ($low_stock > 0) {

        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 7, 'Low Stock Items', 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        // Table header
        $pdf->SetFillColor(240, 240, 240);
        $headers = array('Code', 'Item Name', 'Current Stock', 'Min Stock', 'Unit Price', 'Stock Value');
        $widths = array(25, 60, 25, 25, 30, 30);

        foreach ($headers as $i => $header) {
            $pdf->Cell($widths[$i], 7, $header, 1, 0, 'C', true);
        }
        $pdf->Ln();

        // Table data
        $query = "SELECT * FROM items WHERE stock_quantity <= minimum_stock ORDER BY stock_quantity ASC";
        $items = Database::search($query);

        $pdf->SetFont('helvetica', '', 9);
        while ($item = $items->fetch_assoc()) {
            $stock_value = $item['stock_quantity'] * $item['unit_price'];
            $pdf->Cell($widths[0], 6, $item['item_code'], 1);
            $pdf->Cell($widths[1], 6, $item['name'], 1);
            $pdf->Cell($widths[2], 6, $item['stock_quantity'], 1, 0, 'R');
            $pdf->Cell($widths[3], 6, $item['minimum_stock'], 1, 0, 'R');
            $pdf->Cell($widths[4], 6, formatCurrency($item['unit_price']), 1, 0, 'R');
            $pdf->Cell($widths[5], 6, formatCurrency($stock_value), 1, 0, 'R');
            $pdf->Ln();
        }
    }
    $pdf->Ln(10);

    // All Items Section
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 7, 'All Items', 0, 1);

    // Table header
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(240, 240, 240);

    $headers = array('Code', 'Item Name', 'Current Stock', 'Min Stock', 'Unit Price', 'Stock Value');
    $widths = array(25, 60, 25, 25, 30, 30);

    foreach ($headers as $i => $header) {
        $pdf->Cell($widths[$i], 7, $header, 1, 0, 'C', true);
    }
    $pdf->Ln();

    // Table data
    $pdf->SetFont('helvetica', '', 9);
    $query = "SELECT * FROM items ORDER BY name ASC";
    $items = Database::search($query);

    while ($item = $items->fetch_assoc()) {
        $stock_value = $item['stock_quantity'] * $item['unit_price'];
        $pdf->Cell($widths[0], 6, $item['item_code'], 1);
        $pdf->Cell($widths[1], 6, $item['name'], 1);
        $pdf->Cell($widths[2], 6, $item['stock_quantity'], 1, 0, 'R');
        $pdf->Cell($widths[3], 6, $item['minimum_stock'], 1, 0, 'R');
        $pdf->Cell($widths[4], 6, formatCurrency($item['unit_price']), 1, 0, 'R');
        $pdf->Cell($widths[5], 6, formatCurrency($stock_value), 1, 0, 'R');
        $pdf->Ln();
    }

    // Output PDF
    $pdf->Output('Stock_Status_Report.pdf', 'I');
    exit;
}

include 'header.php';
?>

<div class="container content">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Stock Status Report</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="?export_pdf=1" class="btn btn-primary" target="_blank">
                <i class="fas fa-print"></i> Print Report
            </a>
            <button onclick="history.back()" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Reports
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-warning text-dark">
                        <div class="card-body">
                            <?php
                            $query = "SELECT COUNT(*) as count FROM items 
                                     WHERE stock_quantity <= minimum_stock";
                            $result = Database::search($query);
                            $low_stock = $result->fetch_assoc()['count'];
                            ?>
                            <h5>Low Stock Items</h5>
                            <h3><?php echo $low_stock; ?> items</h3>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <?php
                            $query = "SELECT COUNT(*) as count FROM items 
                                     WHERE stock_quantity > minimum_stock";
                            $result = Database::search($query);
                            $normal_stock = $result->fetch_assoc()['count'];
                            ?>
                            <h5>Normal Stock Items</h5>
                            <h3><?php echo $normal_stock; ?> items</h3>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <?php
                            $query = "SELECT COUNT(*) as count, 
                                     SUM(stock_quantity * unit_price) as value 
                                     FROM items";
                            $result = Database::search($query);
                            $total = $result->fetch_assoc();
                            ?>
                            <h5>Total Stock Value</h5>
                            <h3><?php echo formatCurrency($total['value']); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Low Stock Items Section -->
            <?php if ($low_stock > 0): ?>
                <div class="mb-4">
                    <h4>Low Stock Items</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Item Name</th>
                                    <th>Current Stock</th>
                                    <th>Minimum Stock</th>
                                    <th>Unit Price</th>
                                    <th>Stock Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query = "SELECT * FROM items 
                                     WHERE stock_quantity <= minimum_stock 
                                     ORDER BY stock_quantity ASC";
                                $items = Database::search($query);
                                while ($item = $items->fetch_assoc()):
                                    $stock_value = $item['stock_quantity'] * $item['unit_price'];
                                ?>
                                    <tr class="bg-warning bg-opacity-25">
                                        <td><?php echo $item['item_code']; ?></td>
                                        <td><?php echo $item['name']; ?></td>
                                        <td class="text-end"><?php echo $item['stock_quantity']; ?></td>
                                        <td class="text-end"><?php echo $item['minimum_stock']; ?></td>
                                        <td class="text-end"><?php echo formatCurrency($item['unit_price']); ?></td>
                                        <td class="text-end"><?php echo formatCurrency($stock_value); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- All Items Section -->
            <div>
                <h4>All Items</h4>
                <div class="table-responsive">
                    <table class="table table-bordered datatable">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Item Name</th>
                                <th>Current Stock</th>
                                <th>Minimum Stock</th>
                                <th>Unit Price</th>
                                <th>Stock Value</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT * FROM items ORDER BY name ASC";
                            $items = Database::search($query);
                            while ($item = $items->fetch_assoc()):
                                $stock_value = $item['stock_quantity'] * $item['unit_price'];
                                $status = $item['stock_quantity'] <= $item['minimum_stock'] ? 'Low' : 'Normal';
                                $status_class = $status === 'Low' ? 'bg-warning bg-opacity-25' : '';
                            ?>
                                <tr class="<?php echo $status_class; ?>">
                                    <td><?php echo $item['item_code']; ?></td>
                                    <td><?php echo $item['name']; ?></td>
                                    <td class="text-end"><?php echo $item['stock_quantity']; ?></td>
                                    <td class="text-end"><?php echo $item['minimum_stock']; ?></td>
                                    <td class="text-end"><?php echo formatCurrency($item['unit_price']); ?></td>
                                    <td class="text-end"><?php echo formatCurrency($stock_value); ?></td>
                                    <td><?php echo $status; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
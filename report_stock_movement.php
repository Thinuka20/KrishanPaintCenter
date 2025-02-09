<?php
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

class StockMovementReport extends ReportPDF {
    public function generateReport($data, $summary, $date_range, $item_info = null) {
        $this->AddPage('L');
        $this->addReportHeader($date_range);

        // Add item filter info if specified
        if ($item_info) {
            $this->SetFont('helvetica', 'B', 11);
            $this->Cell(0, 6, "Item: {$item_info['item_code']} - {$item_info['name']}", 0, 1, 'C');
            $this->Ln(2);
        }

        // Summary section
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 7, 'Movement Summary', 0, 1);
        $this->SetFont('helvetica', '', 10);

        $this->Cell(90, 6, 'Total Stock In: ' . $summary['total_in'], 0, 1);
        $this->Cell(90, 6, 'Total Stock Out: ' . $summary['total_out'], 0, 1);
        $this->Cell(90, 6, 'Number of Transactions: ' . $summary['total_transactions'], 0, 1);
        $this->Cell(90, 6, 'Current Balance: ' . $summary['current_balance'], 0, 1);
        $this->Ln(5);

        // Movement Details Section
        $this->SetFont('helvetica', 'B', 12);
        $this->Cell(0, 7, 'Stock Movements', 0, 1);

        // Table headers
        $headers = array('Date', 'Item Code', 'Item Name', 'Type', 'Quantity', 'Reference', 'Balance');
        $widths = array(30, 20, 75, 20, 20, 90, 20);
        $this->addTableHeader($headers, $widths);

        // Add movement details
        $this->SetFont('helvetica', '', 9);
        foreach ($data as $movement) {
            $type_color = $movement['movement_type'] == 'in' ? array(200, 255, 200) : array(255, 200, 200);
            $this->SetFillColor($type_color[0], $type_color[1], $type_color[2]);
            
            $this->Cell($widths[0], 6, date('Y-m-d H:i', strtotime($movement['movement_date'])), 1, 0, 'C', true);
            $this->Cell($widths[1], 6, $movement['item_code'], 1, 0, 'L', true);
            $this->Cell($widths[2], 6, $movement['name'], 1, 0, 'L', true);
            $this->Cell($widths[3], 6, strtoupper($movement['movement_type']), 1, 0, 'C', true);
            $this->Cell($widths[4], 6, $movement['quantity'], 1, 0, 'R', true);
            $this->Cell($widths[5], 6, $movement['reference'], 1, 0, 'L', true);
            $this->Cell($widths[6], 6, $movement['running_balance'], 1, 0, 'R', true);
            $this->Ln();
        }
    }
}

// Get filters from request
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$item_id = isset($_GET['item_id']) ? $_GET['item_id'] : '';

// Build WHERE clause based on filters
$where_clause = "DATE(m.movement_date) BETWEEN '{$start_date}' AND '{$end_date}'";
if ($item_id) {
    $where_clause .= " AND m.item_id = {$item_id}";
}

// Generate PDF if requested
if (isset($_GET['export_pdf'])) {
    try {
        // Get item info if filtered
        $item_info = null;
        if ($item_id) {
            $query = "SELECT item_code, name FROM items WHERE id = {$item_id}";
            $result = Database::search($query);
            $item_info = $result->fetch_assoc();
        }

        // Get summary statistics
        $query = "SELECT 
                    SUM(CASE WHEN movement_type = 'in' THEN quantity ELSE 0 END) as total_in,
                    SUM(CASE WHEN movement_type = 'out' THEN quantity ELSE 0 END) as total_out,
                    COUNT(*) as total_transactions,
                    SUM(CASE WHEN movement_type = 'in' THEN quantity 
                        WHEN movement_type = 'out' THEN -quantity END) as current_balance
                 FROM stock_movements m
                 WHERE {$where_clause}";
        
        $result = Database::search($query);
        $summary = $result->fetch_assoc();

        // Get movement details with running balance
        $query = "SELECT 
        m.*,
        i.item_code,
        i.name,
        (
            SELECT SUM(CASE 
                WHEN sm.movement_type = 'in' THEN sm.quantity 
                WHEN sm.movement_type = 'out' THEN -sm.quantity 
            END)
            FROM stock_movements sm
            WHERE sm.item_id = m.item_id
            AND (
                sm.movement_date < m.movement_date
                OR (
                    sm.movement_date = m.movement_date
                    AND sm.id <= m.id
                )
            )
        ) as running_balance
    FROM stock_movements m
    JOIN items i ON m.item_id = i.id
    WHERE {$where_clause}
    ORDER BY m.movement_date DESC, m.id DESC";
        
        $result = Database::search($query);
        $movements = array();
        while ($row = $result->fetch_assoc()) {
            $movements[] = $row;
        }

        $date_range = "From: " . date('Y-m-d', strtotime($start_date)) . 
                     " To: " . date('Y-m-d', strtotime($end_date));

        // Generate and output PDF
        $pdf = new StockMovementReport('L', 'Stock Movement Report');
        $pdf->generateReport($movements, $summary, $date_range, $item_info);
        $pdf->Output('Stock_Movements_Report.pdf', 'I');
        exit;
    } catch (Exception $e) {
        error_log("Error generating PDF: " . $e->getMessage());
        die("Error generating report. Please try again later.");
    }
}

// Regular page display
include 'header.php';
?>

<div class="container content">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Stock Movement Report</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="#" onclick="printReport()" class="btn btn-primary" target="_blank">
            <i class="fas fa-print"></i> Print Report
            </a>
            <button onclick="history.back()" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Reports
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <!-- Filters -->
            <form id="reportForm" class="row mb-4">
                <div class="col-md-3">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" 
                           value="<?php echo $start_date; ?>" required>
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" 
                           value="<?php echo $end_date; ?>" required>
                </div>
                <div class="col-md-4">
                    <label for="item_id" class="form-label">Item</label>
                    <select class="form-select" id="item_id" name="item_id">
                        <option value="">All Items</option>
                        <?php
                        $query = "SELECT id, item_code, name FROM items ORDER BY name";
                        $result = Database::search($query);
                        while ($item = $result->fetch_assoc()) {
                            $selected = ($item_id == $item['id']) ? 'selected' : '';
                            echo "<option value='{$item['id']}' {$selected}>{$item['item_code']} - {$item['name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary d-block w-100">Apply Filter</button>
                </div>
            </form>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <?php
                $query = "SELECT 
                            SUM(CASE WHEN movement_type = 'in' THEN quantity ELSE 0 END) as total_in,
                            SUM(CASE WHEN movement_type = 'out' THEN quantity ELSE 0 END) as total_out,
                            COUNT(*) as total_transactions,
                            SUM(CASE WHEN movement_type = 'in' THEN quantity 
                                WHEN movement_type = 'out' THEN -quantity END) as current_balance
                         FROM stock_movements m
                         WHERE {$where_clause}";
                
                $result = Database::search($query);
                $summary = $result->fetch_assoc();
                ?>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5>Total Stock In</h5>
                            <h3><?php echo number_format($summary['total_in']); ?> units</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <h5>Total Stock Out</h5>
                            <h3><?php echo number_format($summary['total_out']); ?> units</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5>Total Transactions</h5>
                            <h3><?php echo number_format($summary['total_transactions']); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5>Current Balance</h5>
                            <h3><?php echo number_format($summary['current_balance']); ?> units</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Movement Details Table -->
            <div class="table-responsive">
                <table class="table table-bordered datatable">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Item Code</th>
                            <th>Item Name</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>Reference</th>
                            <th>Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT 
                        m.*,
                        i.item_code,
                        i.name,
                        (
                            SELECT SUM(CASE 
                                WHEN sm.movement_type = 'in' THEN sm.quantity 
                                WHEN sm.movement_type = 'out' THEN -sm.quantity 
                            END)
                            FROM stock_movements sm
                            WHERE sm.item_id = m.item_id
                            AND (
                                sm.movement_date < m.movement_date
                                OR (
                                    sm.movement_date = m.movement_date
                                    AND sm.id <= m.id
                                )
                            )
                        ) as running_balance
                    FROM stock_movements m
                    JOIN items i ON m.item_id = i.id
                    WHERE {$where_clause}
                    ORDER BY m.movement_date DESC, m.id DESC";
                        
                        $result = Database::search($query);

                        while ($movement = $result->fetch_assoc()):
                            $type_class = $movement['movement_type'] == 'in' ? 
                                        'bg-success bg-opacity-25' : 'bg-danger bg-opacity-25';
                        ?>
                            <tr class="<?php echo $type_class; ?>">
                                <td><?php echo date('Y-m-d H:i', strtotime($movement['movement_date'])); ?></td>
                                <td><?php echo $movement['item_code']; ?></td>
                                <td><?php echo $movement['name']; ?></td>
                                <td class="text-center">
                                    <?php echo strtoupper($movement['movement_type']); ?>
                                </td>
                                <td class="text-end"><?php echo $movement['quantity']; ?></td>
                                <td><?php echo $movement['reference']; ?></td>
                                <td class="text-end"><?php echo $movement['running_balance']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function printReport() {
    let url = '?export_pdf=1';
    if ($('#start_date').val()) {
        url += '&start_date=' + $('#start_date').val();
    }
    if ($('#end_date').val()) {
        url += '&end_date=' + $('#end_date').val();
    }
    if ($('#item_id').val()) {
        url += '&item_id=' + $('#item_id').val();
    }
    window.open(url, '_blank');
}
</script>

<?php include 'footer.php'; ?>
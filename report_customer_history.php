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

// Get filter parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$customer_id = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : null;

// Check if PDF export is requested
if (isset($_GET['export_pdf']) && $customer_id) {
    // Get customer details
    $query = "SELECT * FROM customers WHERE id = $customer_id";
    $result = Database::search($query);
    $customer = $result->fetch_assoc();

    // Get repair history
    $repair_query = "SELECT 
                        ri.*,
                        v.registration_number,
                        v.make,
                        v.model,
                        GROUP_CONCAT(rii.description SEPARATOR '\n') as repair_items
                    FROM repair_invoices ri
                    JOIN vehicles v ON ri.vehicle_id = v.id
                    LEFT JOIN repair_invoice_items rii ON ri.id = rii.repair_invoice_id
                    WHERE v.customer_id = $customer_id 
                    AND ri.invoice_date BETWEEN '$start_date' AND '$end_date'
                    GROUP BY ri.id
                    ORDER BY ri.invoice_date";
    
    $repair_result = Database::search($repair_query);
    $repair_data = [];
    while ($row = $repair_result->fetch_assoc()) {
        $repair_data[] = $row;
    }

    // Get item sales history
    $item_query = "SELECT 
                        ii.*,
                        GROUP_CONCAT(i.name SEPARATOR ', ') as items
                    FROM item_invoices ii
                    LEFT JOIN item_invoice_details iid ON ii.id = iid.item_invoice_id
                    LEFT JOIN items i ON iid.item_id = i.id
                    WHERE ii.customer_id = $customer_id 
                    AND ii.invoice_date BETWEEN '$start_date' AND '$end_date'
                    GROUP BY ii.id
                    ORDER BY ii.invoice_date";
    
    $item_result = Database::search($item_query);
    $item_data = [];
    while ($row = $item_result->fetch_assoc()) {
        $item_data[] = $row;
    }

    // Get pending payments
    $pending_repair_query = "SELECT 
        ri.invoice_date,
        'Repair' as type,
        ri.invoice_number,
        CONCAT(v.registration_number, ' - ', v.make, ' ', v.model) as details,
        ri.total_amount,
        COALESCE(SUM(pt.amount), 0) as paid_amount
    FROM repair_invoices ri
    JOIN vehicles v ON ri.vehicle_id = v.id
    LEFT JOIN payment_transactions pt ON pt.invoice_type = 'repair' AND pt.invoice_id = ri.id
    WHERE v.customer_id = $customer_id 
    AND ri.payment_status != 'paid'
    GROUP BY ri.id
    ORDER BY ri.invoice_date";

    $pending_item_query = "SELECT 
        ii.invoice_date,
        'Item Sale' as type,
        ii.invoice_number,
        GROUP_CONCAT(i.name SEPARATOR ', ') as details,
        ii.total_amount,
        COALESCE(SUM(pt.amount), 0) as paid_amount
    FROM item_invoices ii
    LEFT JOIN item_invoice_details iid ON ii.id = iid.item_invoice_id
    LEFT JOIN items i ON iid.item_id = i.id
    LEFT JOIN payment_transactions pt ON pt.invoice_type = 'item' AND pt.invoice_id = ii.id
    WHERE ii.customer_id = $customer_id 
    AND ii.payment_status != 'paid'
    GROUP BY ii.id
    ORDER BY ii.invoice_date";

    $pending_repair_result = Database::search($pending_repair_query);
    $pending_item_result = Database::search($pending_item_query);

    $pending_data = array();
    while ($row = $pending_repair_result->fetch_assoc()) {
        $pending_data[] = $row;
    }
    while ($row = $pending_item_result->fetch_assoc()) {
        $pending_data[] = $row;
    }

    // Calculate totals
    $totals = [
        'repair_amount' => array_sum(array_column($repair_data, 'total_amount')),
        'item_amount' => array_sum(array_column($item_data, 'total_amount')),
        'pending_amount' => array_sum(array_map(function($row) {
            return $row['total_amount'] - $row['paid_amount'];
        }, $pending_data))
    ];
    $totals['total_amount'] = $totals['repair_amount'] + $totals['item_amount'];

    // Generate PDF
    $pdf = new ReportPDF('L');
    $pdf->generateCustomerHistoryReport(
        $customer,
        $repair_data,
        $item_data,
        $pending_data,
        $totals,
        date('Y-m-d', strtotime($start_date)) . ' to ' . date('Y-m-d', strtotime($end_date))
    );
    $pdf->Output('Customer_History_Report_' . date('Y-m-d') . '.pdf', 'I');
    exit;
}

// Get all customers for dropdown
$query = "SELECT id, name FROM customers ORDER BY name";
$customers_result = Database::search($query);

include 'header.php';
?>

<div class="container content">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Customer History Report</h2>
        </div>
        <div class="col-md-6 text-end">
            <?php if ($customer_id): ?>
            <a href="?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&customer_id=<?php echo $customer_id; ?>&export_pdf=1" 
               class="btn btn-primary" target="_blank">
               <i class="fas fa-print"></i> Print Report
            </a>
            <?php endif; ?>
            <button onclick="history.back()" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Reports
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <!-- Search Form -->
            <form method="GET" class="row mb-3">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Customer</label>
                        <select class="form-control select2" name="customer_id" required>
                            <option value="">Select Customer</option>
                            <?php while ($customer = $customers_result->fetch_assoc()): ?>
                                <option value="<?php echo $customer['id']; ?>" 
                                    <?php echo ($customer_id == $customer['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($customer['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" name="start_date" class="form-control" 
                               value="<?php echo $start_date; ?>" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" name="end_date" class="form-control" 
                               value="<?php echo $end_date; ?>" required>
                    </div>
                </div>
                <div class="col-md-2 mt-4">
                        <button type="submit" class="btn btn-primary">Generate</button>
                </div>
            </form>

            <?php if ($customer_id):
                // Get customer details
                $query = "SELECT * FROM customers WHERE id = $customer_id";
                $result = Database::search($query);
                $customer = $result->fetch_assoc();

                // Get repair history
                $repair_query = "SELECT 
                                ri.*,
                                v.registration_number,
                                v.make,
                                v.model,
                                GROUP_CONCAT(rii.description SEPARATOR '\n') as repair_items
                            FROM repair_invoices ri
                            JOIN vehicles v ON ri.vehicle_id = v.id
                            LEFT JOIN repair_invoice_items rii ON ri.id = rii.repair_invoice_id
                            WHERE v.customer_id = $customer_id 
                            AND ri.invoice_date BETWEEN '$start_date' AND '$end_date'
                            GROUP BY ri.id
                            ORDER BY ri.invoice_date";
                
                $repair_result = Database::search($repair_query);
            ?>
                <!-- Customer Details -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="100">Name:</th>
                                <td><?php echo htmlspecialchars($customer['name']); ?></td>
                            </tr>
                            <tr>
                                <th>Phone:</th>
                                <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                            </tr>
                            <tr>
                                <th>Address:</th>
                                <td><?php echo htmlspecialchars($customer['address']); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Repair History -->
                <h5 class="mb-3">Repair History</h5>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Invoice #</th>
                                <th>Vehicle</th>
                                <th>Repair Items</th>
                                <th class="text-end">Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_repair = 0;
                            while ($row = $repair_result->fetch_assoc()):
                                $total_repair += $row['total_amount'];
                            ?>
                                <tr>
                                    <td><?php echo date('Y-m-d', strtotime($row['invoice_date'])); ?></td>
                                    <td><?php echo $row['invoice_number']; ?></td>
                                    <td><?php echo $row['registration_number'] . ' - ' . $row['make'] . ' ' . $row['model']; ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($row['repair_items'])); ?></td>
                                    <td class="text-end"><?php echo formatCurrency($row['total_amount']); ?></td>
                                    <td><?php echo ucfirst($row['payment_status']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4">Total Repair Amount</th>
                                <th class="text-end"><?php echo formatCurrency($total_repair); ?></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Item Sales History -->
                <?php
                $item_query = "SELECT 
                                ii.*,
                                GROUP_CONCAT(i.name SEPARATOR ', ') as items
                            FROM item_invoices ii
                            LEFT JOIN item_invoice_details iid ON ii.id = iid.item_invoice_id
                            LEFT JOIN items i ON iid.item_id = i.id
                            WHERE ii.customer_id = $customer_id 
                            AND ii.invoice_date BETWEEN '$start_date' AND '$end_date'
                            GROUP BY ii.id
                            ORDER BY ii.invoice_date";
                
                $item_result = Database::search($item_query);
                ?>

                <h5 class="mb-3">Item Sales History</h5>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Invoice #</th>
                                <th>Items</th>
                                <th class="text-end">Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_items = 0;
                            while ($row = $item_result->fetch_assoc()):
                                $total_items += $row['total_amount'];
                            ?>
                                <tr>
                                    <td><?php echo date('Y-m-d', strtotime($row['invoice_date'])); ?></td>
                                    <td><?php echo $row['invoice_number']; ?></td>
                                    <td><?php echo htmlspecialchars($row['items']); ?></td>
                                    <td class="text-end"><?php echo formatCurrency($row['total_amount']); ?></td>
                                    <td><?php echo ucfirst($row['payment_status']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3">Total Item Amount</th>
                                <th class="text-end"><?php echo formatCurrency($total_items); ?></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Pending Payments -->
                <?php
                // Get pending payments
                $pending_repair_query = "SELECT 
                    ri.invoice_date,
                    'Repair' as type,
                    ri.invoice_number,
                    CONCAT(v.registration_number, ' - ', v.make, ' ', v.model) as details,
                    ri.total_amount,
                    COALESCE(SUM(pt.amount), 0) as paid_amount
                FROM repair_invoices ri
                JOIN vehicles v ON ri.vehicle_id = v.id
                LEFT JOIN payment_transactions pt ON pt.invoice_type = 'repair' AND pt.invoice_id = ri.id
                WHERE v.customer_id = $customer_id 
                AND ri.payment_status != 'paid'
                GROUP BY ri.id
                ORDER BY ri.invoice_date";

                $pending_item_query = "SELECT 
                    ii.invoice_date,
                    'Item Sale' as type,
                    ii.invoice_number,
                    GROUP_CONCAT(i.name SEPARATOR ', ') as details,
                    ii.total_amount,
                    COALESCE(SUM(pt.amount), 0) as paid_amount
                FROM item_invoices ii
                LEFT JOIN item_invoice_details iid ON ii.id = iid.item_invoice_id
                LEFT JOIN items i ON iid.item_id = i.id
                LEFT JOIN payment_transactions pt ON pt.invoice_type = 'item' AND pt.invoice_id = ii.id
                WHERE ii.customer_id = $customer_id 
                AND ii.payment_status != 'paid'
                GROUP BY ii.id
                ORDER BY ii.invoice_date";

                $pending_repair_result = Database::search($pending_repair_query);
                $pending_item_result = Database::search($pending_item_query);

                $has_pending = ($pending_repair_result->num_rows > 0 || $pending_item_result->num_rows > 0);

                if ($has_pending):
                ?>
                    <h5 class="mb-3">Pending Payments</h5>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Invoice #</th>
                                    <th>Details</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end">Paid Amount</th>
                                    <th class="text-end">Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_pending = 0;
                                
                                while ($row = $pending_repair_result->fetch_assoc()):
                                    $balance = $row['total_amount'] - $row['paid_amount'];
                                    $total_pending += $balance;
                                ?>
                                    <tr>
                                        <td><?php echo date('Y-m-d', strtotime($row['invoice_date'])); ?></td>
                                        <td><?php echo $row['type']; ?></td>
                                        <td><?php echo $row['invoice_number']; ?></td>
                                        <td><?php echo htmlspecialchars($row['details']); ?></td>
                                        <td class="text-end"><?php echo formatCurrency($row['total_amount']); ?></td>
                                        <td class="text-end"><?php echo formatCurrency($row['paid_amount']); ?></td>
                                        <td class="text-end"><?php echo formatCurrency($balance); ?></td>
                                    </tr>
                                <?php endwhile; ?>

                                <?php while ($row = $pending_item_result->fetch_assoc()):
                                    $balance = $row['total_amount'] - $row['paid_amount'];
                                    $total_pending += $balance;
                                ?>
                                    <tr>
                                        <td><?php echo date('Y-m-d', strtotime($row['invoice_date'])); ?></td>
                                        <td><?php echo $row['type']; ?></td>
                                        <td><?php echo $row['invoice_number']; ?></td>
                                        <td><?php echo htmlspecialchars($row['details']); ?></td>
                                        <td class="text-end"><?php echo formatCurrency($row['total_amount']); ?></td>
                                        <td class="text-end"><?php echo formatCurrency($row['paid_amount']); ?></td>
                                        <td class="text-end"><?php echo formatCurrency($balance); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td colspan="6">Total Pending Amount</td>
                                    <td class="text-end"><?php echo formatCurrency($total_pending); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>

                <!-- Summary -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h5>Summary</h5>
                        <table class="table table-borderless">
                            <tr>
                                <th width="200">Total Repair Amount:</th>
                                <td class="text-end"><?php echo formatCurrency($total_repair); ?></td>
                            </tr>
                            <tr>
                                <th>Total Item Amount:</th>
                                <td class="text-end"><?php echo formatCurrency($total_items); ?></td>
                            </tr>
                            <tr>
                                <th>Total Amount:</th>
                                <td class="text-end"><strong><?php echo formatCurrency($total_repair + $total_items); ?></strong></td>
                            </tr>
                            <?php if ($has_pending): ?>
                            <tr class="text-danger">
                                <th>Total Pending Amount:</th>
                                <td class="text-end"><strong><?php echo formatCurrency($total_pending); ?></strong></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>

            <?php else: ?>
                <div class="alert alert-info">
                    Please select a customer to generate the report.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%'
    });
});
</script>

<?php include 'footer.php'; ?>
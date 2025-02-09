<?php
// item_invoices.php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();

include 'header.php';
?>

<div class="container content">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Item Invoices</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="add_item_invoice.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Item Invoice
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <!-- Search and Filter Form -->
            <form method="GET" class="row mb-3">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Search Invoices</label>
                        <input type="text" name="search" class="form-control"
                            placeholder="Enter invoice number or customer name"
                            value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo isset($_GET['status']) && $_GET['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="partial" <?php echo isset($_GET['status']) && $_GET['status'] === 'partial' ? 'selected' : ''; ?>>Partial</option>
                            <option value="paid" <?php echo isset($_GET['status']) && $_GET['status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary mt-4">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped datatable">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Total Amount</th>
                            <th>Paid Amount</th>
                            <th>Due Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Build WHERE clause for filters
                        $where = "WHERE 1=1";
                        if (isset($_GET['search']) && !empty($_GET['search'])) {
                            $search = validateInput($_GET['search']);
                            $where .= " AND (ii.invoice_number LIKE '%" . $search . "%' 
                                      OR c.name LIKE '%" . $search . "%')";
                        }
                        if (isset($_GET['status']) && !empty($_GET['status'])) {
                            $status = validateInput($_GET['status']);
                            $where .= " AND ii.payment_status = '" . $status . "'";
                        }
                        if (isset($_GET['date_range']) && !empty($_GET['date_range'])) {
                            $dates = explode(' - ', $_GET['date_range']);
                            if (count($dates) == 2) {
                                $start_date = validateInput($dates[0]);
                                $end_date = validateInput($dates[1]);
                                $where .= " AND DATE(ii.invoice_date) BETWEEN '$start_date' AND '$end_date'";
                            }
                        }

                        $query = "SELECT 
                                    ii.*, 
                                    c.name as customer_name,
                                    COALESCE((
                                        SELECT SUM(amount) 
                                        FROM payment_transactions 
                                        WHERE invoice_type = 'item' 
                                        AND invoice_id = ii.id
                                    ), 0) as paid_amount
                                FROM item_invoices ii 
                                LEFT JOIN customers c ON ii.customer_id = c.id 
                                $where 
                                ORDER BY ii.invoice_date DESC";

                        $result = Database::search($query);
                        while ($row = $result->fetch_assoc()):
                            $due_amount = $row['total_amount'] - $row['paid_amount'];
                        ?>
                            <tr>
                                <td><?php echo $row['invoice_number']; ?></td>
                                <td><?php echo $row['customer_name']; ?></td>
                                <td><?php echo date('Y-m-d', strtotime($row['invoice_date'])); ?></td>
                                <td><?php echo formatCurrency($row['total_amount']); ?></td>
                                <td><?php echo formatCurrency($row['paid_amount']); ?></td>
                                <td><?php echo formatCurrency($due_amount); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $row['payment_status'] === 'paid' ? 'success' : ($row['payment_status'] === 'partial' ? 'warning' : 'danger'); ?>">
                                        <?php echo ucfirst($row['payment_status']); ?>
                                    </span>
                                </td>
                                <td class="action-buttons">
                                    <a href="view_item_invoice.php?id=<?php echo $row['id']; ?>"
                                        class="btn btn-sm btn-info">
                                        <i class="fas fa-eye text-light"></i>
                                    </a>
                                    <a href="print_item_invoice.php?id=<?php echo $row['id']; ?>"
                                        class="btn btn-sm btn-secondary" target="_blank">
                                        <i class="fas fa-print text-light"></i>
                                    </a>
                                    <?php if ($row['payment_status'] !== 'paid'): ?>
                                        <a href="edit_item_invoice.php?id=<?php echo $row['id']; ?>"
                                            class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit text-light"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="update_payment_status.php?type=item&id=<?php echo $row['id']; ?>"
                                        class="btn btn-sm btn-success">
                                        <i class="fas fa-dollar-sign text-light"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Summary Section -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <?php
                            $summary_query = "SELECT 
                            COUNT(*) as total_invoices,
                            SUM(total_amount) as total_amount,
                            SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) as pending_invoices,
                            SUM(CASE WHEN payment_status = 'partial' THEN 1 ELSE 0 END) as partial_invoices,
                            SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid_invoices,
                            (
                                SELECT COALESCE(SUM(pt.amount), 0)
                                FROM payment_transactions pt
                                WHERE pt.invoice_type = 'item'
                                AND pt.invoice_id IN (
                                    SELECT ii.id 
                                    FROM item_invoices ii 
                                    LEFT JOIN customers c ON ii.customer_id = c.id
                                    $where
                                )
                            ) as total_paid_amount
                        FROM item_invoices ii 
                        LEFT JOIN customers c ON ii.customer_id = c.id
                        $where";
                            $summary_result = Database::search($summary_query);
                            $summary = $summary_result->fetch_assoc();
                            $total_due_amount = $summary['total_amount'] - $summary['total_paid_amount'];
                            ?>
                            <h5>Summary</h5>
                            <p class="mb-1">Total Item Invoices: <?php echo $summary['total_invoices']; ?></p>
                            <p class="mb-1">Pending Invoices: <?php echo $summary['pending_invoices']; ?></p>
                            <p class="mb-1">Partially Paid: <?php echo $summary['partial_invoices']; ?></p>
                            <p class="mb-1">Fully Paid: <?php echo $summary['paid_invoices']; ?></p>
                            <p class="mb-1">Total Amount: <?php echo formatCurrency($summary['total_amount']); ?></p>
                            <p class="mb-1">Total Paid: <?php echo formatCurrency($summary['total_paid_amount']); ?></p>
                            <p class="mb-1">Total Due: <?php echo formatCurrency($total_due_amount); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
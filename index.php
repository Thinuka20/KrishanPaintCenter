<?php
// index.php - Dashboard
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';
checkLogin();

// Get today's statistics
$today = date('Y-m-d');

// Today's sales
$query = "SELECT COALESCE(SUM(total_amount), 0) as total FROM (
            SELECT total_amount FROM repair_invoices WHERE DATE(invoice_date) = '$today'
            UNION ALL
            SELECT total_amount FROM item_invoices WHERE DATE(invoice_date) = '$today'
          ) as sales";
$result = Database::search($query);
$today_sales = $result->fetch_assoc()['total'];

// Today's expenses
$query = "SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE DATE(expense_date) = '$today'";
$result = Database::search($query);
$today_expenses = $result->fetch_assoc()['total'];

// Low stock items
$query = "SELECT COUNT(*) as count FROM items WHERE stock_quantity <= minimum_stock";
$result = Database::search($query);
$low_stock_count = $result->fetch_assoc()['count'];

// Pending estimates
$query = "SELECT COUNT(*) as count FROM estimates WHERE status = 'pending'";
$result = Database::search($query);
$pending_estimates = $result->fetch_assoc()['count'];

include 'header.php';
?>

<div class="container content">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2>Welcome to Krishan Paint Center Management System</h2>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Today's Sales</h5>
                    <h3><?php echo formatCurrency($today_sales); ?></h3>
                    <a href="report_sales_daily.php" style="text-decoration: none;">View Details →</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Today's Expenses</h5>
                    <h3><?php echo formatCurrency($today_expenses); ?></h3>
                    <a href="expenses.php" style="text-decoration: none;">View Details →</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Low Stock Items</h5>
                    <h3><?php echo $low_stock_count; ?> Items</h3>
                    <a href="report_low_stock.php" style="text-decoration: none;">View Details →</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Pending Estimates</h5>
                    <h3><?php echo $pending_estimates; ?> Estimates</h3>
                    <a href="estimates.php" style="text-decoration: none;">View Details →</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="row">
        <!-- Recent Repairs -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5>Recent Repair Invoices</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Vehicle</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query = "SELECT ri.*, v.registration_number 
                                         FROM repair_invoices ri 
                                         LEFT JOIN vehicles v ON ri.vehicle_id = v.id 
                                         ORDER BY ri.invoice_date DESC LIMIT 5";
                                $result = Database::search($query);
                                while ($row = $result->fetch_assoc()):
                                ?>
                                <tr>
                                    <td>
                                        <a href="view_repair_invoice.php?id=<?php echo $row['id']; ?>">
                                            <?php echo $row['invoice_number']; ?>
                                        </a>
                                    </td>
                                    <td><?php echo $row['registration_number']; ?></td>
                                    <td><?php echo formatCurrency($row['total_amount']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $row['payment_status'] === 'paid' ? 
                                            'success' : ($row['payment_status'] === 'partial' ? 'warning' : 'danger'); ?>">
                                            <?php echo ucfirst($row['payment_status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Item Sales -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5>Recent Item Sales</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query = "SELECT i.*, c.name as customer_name 
                                         FROM item_invoices i 
                                         LEFT JOIN customers c ON i.customer_id = c.id 
                                         ORDER BY i.invoice_date DESC LIMIT 5";
                                $result = Database::search($query);
                                while ($row = $result->fetch_assoc()):
                                ?>
                                <tr>
                                    <td>
                                        <a href="view_item_invoice.php?id=<?php echo $row['id']; ?>">
                                            <?php echo $row['invoice_number']; ?>
                                        </a>
                                    </td>
                                    <td><?php echo $row['customer_name']; ?></td>
                                    <td><?php echo formatCurrency($row['total_amount']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $row['payment_status'] === 'paid' ? 
                                            'success' : ($row['payment_status'] === 'partial' ? 'warning' : 'danger'); ?>">
                                            <?php echo ucfirst($row['payment_status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Low Stock Alert -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5>Low Stock Alert</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Current Stock</th>
                                    <th>Minimum Stock</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query = "SELECT * FROM items 
                                         WHERE stock_quantity <= minimum_stock 
                                         ORDER BY stock_quantity ASC LIMIT 5";
                                $result = Database::search($query);
                                while ($row = $result->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><?php echo $row['name']; ?></td>
                                    <td><?php echo $row['stock_quantity']; ?></td>
                                    <td><?php echo $row['minimum_stock']; ?></td>
                                    <td>
                                        <a href="update_stock.php?id=<?php echo $row['id']; ?>" 
                                           class="btn btn-sm btn-warning">
                                            Update Stock
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Estimates -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5>Pending Estimates</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Estimate #</th>
                                    <th>Vehicle</th>
                                    <th>Amount</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query = "SELECT e.*, v.registration_number 
                                         FROM estimates e 
                                         LEFT JOIN vehicles v ON e.vehicle_id = v.id 
                                         WHERE e.status = 'pending' 
                                         ORDER BY e.estimate_date DESC LIMIT 5";
                                $result = Database::search($query);
                                while ($row = $result->fetch_assoc()):
                                ?>
                                <tr>
                                    <td>
                                        <a href="view_estimate.php?id=<?php echo $row['id']; ?>">
                                            <?php echo $row['estimate_number']; ?>
                                        </a>
                                    </td>
                                    <td><?php echo $row['registration_number']; ?></td>
                                    <td><?php echo formatCurrency($row['total_amount']); ?></td>
                                    <td>
                                        <a href="convert_to_invoice.php?id=<?php echo $row['id']; ?>" 
                                           class="btn btn-sm btn-success"
                                           onclick="return confirm('Convert this estimate to Approve?');">
                                            Approve
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
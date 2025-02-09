<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: unauthorized.php");
    exit();
}

$vehicle_id = (int)$_GET['id'];

// Get vehicle and customer details
$query = "SELECT v.*, c.name as customer_name, c.phone 
          FROM vehicles v 
          LEFT JOIN customers c ON v.customer_id = c.id 
          WHERE v.id = $vehicle_id";
$result = Database::search($query);
$vehicle = $result->fetch_assoc();

// Get vehicle statistics
$query = "SELECT 
            COUNT(ri.id) as total_repairs,
            SUM(ri.total_amount) as total_spent,
            MAX(ri.invoice_date) as last_repair
          FROM repair_invoices ri 
          WHERE ri.vehicle_id = $vehicle_id";
$result = Database::search($query);
$stats = $result->fetch_assoc();

include 'header.php';
?>

<div class="container content">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Vehicle History - <?php echo $vehicle['registration_number']; ?></h2>
            <p>Owner: <strong><?php echo $vehicle['customer_name']; ?></strong> | <?php echo $vehicle['phone']; ?></p>
        </div>
        <div class="col-md-6 text-end">
            <a href="add_repair_invoice.php?vehicle_id=<?php echo $vehicle_id; ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Repair
            </a>
            <a href="add_estimate.php?vehicle_id=<?php echo $vehicle_id; ?>" class="btn btn-success">
                <i class="fas fa-calculator"></i> New Estimate
            </a>
            <button onclick="history.back()" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Customers
            </button>
        </div>
    </div>

    <!-- Vehicle Details & Stats -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5>Vehicle Information</h5>
                    <p><strong>Make/Model:</strong> <?php echo $vehicle['make'] . ' ' . $vehicle['model']; ?></p>
                    <p><strong>Year:</strong> <?php echo $vehicle['year']; ?></p>
                    <p><strong>Registration:</strong> <?php echo $vehicle['registration_number']; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="row">
                <div class="col-md-4">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5>Total Repairs</h5>
                            <h3><?php echo $stats['total_repairs']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5>Total Spent</h5>
                            <h3><?php echo formatCurrency($stats['total_spent']); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5>Last Service</h5>
                            <h3><?php echo $stats['last_repair'] ? date('Y-m-d', strtotime($stats['last_repair'])) : 'Never'; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Repair History -->
    <div class="card mb-4">
        <div class="card-header">
            <h4>Repair History</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped datatable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Invoice #</th>
                            <th>Services/Items</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT ri.*, 
                        GROUP_CONCAT(
                            CONCAT(
                                COALESCE(i.name, rii.description),
                                CASE 
                                    WHEN i.name IS NOT NULL THEN CONCAT(' (', rii.price, ')')
                                    ELSE ''
                                END
                            ) 
                            SEPARATOR ', '
                        ) as items
                        FROM repair_invoices ri
                        LEFT JOIN repair_invoice_items rii ON ri.id = rii.repair_invoice_id
                        LEFT JOIN items i ON rii.item_id = i.id
                        WHERE ri.vehicle_id = $vehicle_id
                        GROUP BY ri.id
                        ORDER BY ri.invoice_date DESC";
                        $repairs = Database::search($query);
                        while ($repair = $repairs->fetch_assoc()):
                        ?>
                            <tr>
                                <td><?php echo date('Y-m-d', strtotime($repair['invoice_date'])); ?></td>
                                <td><?php echo $repair['invoice_number']; ?></td>
                                <td><?php echo $repair['items']; ?></td>
                                <td><?php echo formatCurrency($repair['total_amount']); ?></td>
                                <td>
                                    <span class="badge bg-<?php
                                                            echo $repair['payment_status'] === 'paid' ? 'success' : ($repair['payment_status'] === 'partial' ? 'warning' : 'danger');
                                                            ?>">
                                        <?php echo ucfirst($repair['payment_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="view_repair_invoice.php?id=<?php echo $repair['id']; ?>"
                                        class="btn btn-sm btn-info" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="print_repair_invoice.php?id=<?php echo $repair['id']; ?>"
                                        class="btn btn-sm btn-secondary" title="Print" target="_blank">
                                        <i class="fas fa-print"></i>
                                    </a>
                                    <?php if ($repair['payment_status'] !== 'paid'): ?>
                                        <a href="update_payment_status.php?type=repair&id=<?php echo $repair['id']; ?>"
                                            class="btn btn-sm btn-success" title="Update Payment">
                                            <i class="fas fa-dollar-sign"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Estimate History -->
    <div class="card">
        <div class="card-header">
            <h4>Estimate History</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped datatable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Estimate #</th>
                            <th>Amount</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT e.*, 
                             GROUP_CONCAT(ei.description SEPARATOR ', ') as services
                             FROM estimates e
                             LEFT JOIN estimate_items ei ON e.id = ei.estimate_id
                             WHERE e.vehicle_id = $vehicle_id 
                             GROUP BY e.id
                             ORDER BY e.estimate_date DESC";
                        $estimates = Database::search($query);
                        while ($estimate = $estimates->fetch_assoc()):
                        ?>
                            <tr>
                                <td><?php echo date('Y-m-d', strtotime($estimate['estimate_date'])); ?></td>
                                <td><?php echo htmlspecialchars($estimate['estimate_number']); ?></td>
                                <td><?php echo formatCurrency($estimate['total_amount']); ?></td>
                                <td>
                                    <a href="view_repair_estimate.php?id=<?php echo $estimate['id']; ?>"
                                        class="btn btn-sm btn-info" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="print_estimate.php?id=<?php echo $estimate['id']; ?>"
                                        class="btn btn-sm btn-secondary" title="Print" target="_blank">
                                        <i class="fas fa-print"></i>
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

<?php include 'footer.php'; ?>
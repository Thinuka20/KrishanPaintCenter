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
$customer_id = (int)$_GET['id'];

// Get customer details
$query = "SELECT * FROM customers WHERE id = $customer_id";
$result = Database::search($query);
$customer = $result->fetch_assoc();

include 'header.php';
?>

<div class="container content">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Service History - <?php echo $customer['name']; ?></h2>
        </div>
        <div class="col-md-6 text-end">
            <button onclick="history.back()" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Customer
            </button>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="row mb-4">
        <?php
        $query = "SELECT 
                    COUNT(DISTINCT ri.id) as total_repairs,
                    SUM(ri.total_amount) as total_spent,
                    MAX(ri.invoice_date) as last_visit,
                    COUNT(DISTINCT v.id) as total_vehicles
                 FROM vehicles v
                 LEFT JOIN repair_invoices ri ON v.id = ri.vehicle_id
                 WHERE v.customer_id = $customer_id";
        $result = Database::search($query);
        $stats = $result->fetch_assoc();
        ?>
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5>Total Repairs</h5>
                    <h3><?php echo $stats['total_repairs']; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5>Total Spent</h5>
                    <h3><?php echo formatCurrency($stats['total_spent']); ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5>Vehicles</h5>
                    <h3><?php echo $stats['total_vehicles']; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning">
                <div class="card-body">
                    <h5>Last Visit</h5>
                    <h3><?php echo $stats['last_visit'] ? date('Y-m-d', strtotime($stats['last_visit'])) : 'Never'; ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Service History by Vehicle -->
    <?php
    $query = "SELECT * FROM vehicles WHERE customer_id = $customer_id ORDER BY registration_number";
    $vehicles = Database::search($query);
    while ($vehicle = $vehicles->fetch_assoc()):
    ?>
        <div class="card mb-4">
            <div class="card-header">
                <h4>
                    <?php echo $vehicle['registration_number']; ?> -
                    <?php echo $vehicle['make'] . ' ' . $vehicle['model'] . ' (' . $vehicle['year'] . ')'; ?>
                </h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Invoice #</th>
                                <th>Services</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT ri.*, GROUP_CONCAT(i.name SEPARATOR ', ') as items
                                 FROM repair_invoices ri
                                 LEFT JOIN repair_invoice_items rii ON ri.id = rii.repair_invoice_id
                                 LEFT JOIN items i ON rii.item_id = i.id
                                 WHERE ri.vehicle_id = {$vehicle['id']}
                                 GROUP BY ri.id
                                 ORDER BY ri.invoice_date DESC";
                            $repairs = Database::search($query);
                            if ($repairs->num_rows > 0):
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
                                                class="btn btn-sm btn-info" title="View Invoice">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="print_repair_invoice.php?id=<?php echo $repair['id']; ?>"
                                                class="btn btn-sm btn-secondary" title="Print Invoice" target="_blank">
                                                <i class="fas fa-print"></i>
                                            </a>
                                            <?php if (!empty($repair['notes'])): ?>
                                                <button type="button" class="btn btn-sm btn-warning"
                                                    data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($repair['notes']); ?>">
                                                    <i class="fas fa-sticky-note"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php
                                endwhile;
                            else:
                                ?>
                                <tr>
                                    <td colspan="6" class="text-center">No repair history found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
</div>

<script>
    $(document).ready(function() {
        $('[data-bs-toggle="tooltip"]').tooltip();
    });
</script>

<?php include 'footer.php'; ?>
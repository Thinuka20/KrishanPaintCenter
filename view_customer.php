<?php
// view_customer.php - View customer details
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();

$customer_id = (int)$_GET['id'];

// Get customer details
$query = "SELECT * FROM customers WHERE id = $customer_id";
$result = Database::search($query);
$customer = $result->fetch_assoc();

// Calculate customer statistics
$query = "SELECT 
            COUNT(v.id) as total_vehicles,
            (SELECT COUNT(DISTINCT ri.id) 
             FROM repair_invoices ri 
             JOIN vehicles v2 ON ri.vehicle_id = v2.id 
             WHERE v2.customer_id = $customer_id) as total_repairs,
            (SELECT SUM(ri.total_amount) 
             FROM repair_invoices ri 
             JOIN vehicles v3 ON ri.vehicle_id = v3.id 
             WHERE v3.customer_id = $customer_id) as total_spent,
            (SELECT MAX(ri.invoice_date) 
             FROM repair_invoices ri 
             JOIN vehicles v4 ON ri.vehicle_id = v4.id 
             WHERE v4.customer_id = $customer_id) as last_visit
          FROM vehicles v
          WHERE v.customer_id = $customer_id";
$result = Database::search($query);
$stats = $result->fetch_assoc();

include 'header.php';
?>

<div class="container content">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2><?php echo $customer['name']; ?></h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="edit_customer.php?id=<?php echo $customer_id; ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit Customer
            </a>
            <a href="add_vehicle.php?customer_id=<?php echo $customer_id; ?>" class="btn btn-success">
                <i class="fas fa-car"></i> Add Vehicle
            </a>
            <a href="customers.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Customers
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Customer Information -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h4>Customer Information</h4>
                </div>
                <div class="card-body">
                    <p><strong>Phone:</strong> <?php echo $customer['phone']; ?></p>
                    <p><strong>Email:</strong> <?php echo $customer['email'] ?: 'Not provided'; ?></p>
                    <p><strong>Address:</strong> <?php echo $customer['address'] ?: 'Not provided'; ?></p>
                    <p><strong>Customer Since:</strong> <?php echo date('Y-m-d', strtotime($customer['created_at'])); ?></p>
                </div>
            </div>

            <!-- Customer Statistics -->
            <div class="card mt-3">
                <div class="card-header">
                    <h4>Statistics</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center mb-3">
                                <h5>Vehicles</h5>
                                <h3><?php echo $stats['total_vehicles']; ?></h3>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center mb-3">
                                <h5>Repairs</h5>
                                <h3><?php echo $stats['total_repairs']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <p><strong>Total Spent:</strong> <?php echo formatCurrency($stats['total_spent'] ?: 0); ?></p>
                    <p><strong>Last Visit:</strong> <?php echo $stats['last_visit'] ? date('Y-m-d', strtotime($stats['last_visit'])) : 'Never'; ?></p>
                </div>
            </div>
        </div>

        <!-- Vehicles -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4>Vehicles</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Registration</th>
                                    <th>Make/Model</th>
                                    <th>Year</th>
                                    <th>Last Service</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query = "SELECT v.*, 
                                         (SELECT MAX(invoice_date) 
                                          FROM repair_invoices 
                                          WHERE vehicle_id = v.id) as last_service
                                         FROM vehicles v 
                                         WHERE customer_id = $customer_id 
                                         ORDER BY registration_number";
                                $result = Database::search($query);
                                while ($vehicle = $result->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><?php echo $vehicle['registration_number']; ?></td>
                                    <td><?php echo $vehicle['make'] . ' ' . $vehicle['model']; ?></td>
                                    <td><?php echo $vehicle['year']; ?></td>
                                    <td>
                                        <?php 
                                        echo $vehicle['last_service'] ? 
                                             date('Y-m-d', strtotime($vehicle['last_service'])) : 
                                             'Never';
                                        ?>
                                    </td>
                                    <td>
                                        <a href="vehicle_history.php?id=<?php echo $vehicle['id']; ?>" 
                                           class="btn btn-sm btn-info" title="Service History">
                                            <i class="fas fa-history"></i>
                                        </a>
                                        <a href="add_repair_invoice.php?vehicle_id=<?php echo $vehicle['id']; ?>" 
                                           class="btn btn-sm btn-success" title="New Repair">
                                            <i class="fas fa-tools"></i>
                                        </a>
                                        <a href="edit_vehicle.php?id=<?php echo $vehicle['id']; ?>" 
                                           class="btn btn-sm btn-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent Repairs -->
            <div class="card mt-3">
                <div class="card-header">
                    <h4>Recent Repairs</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Vehicle</th>
                                    <th>Invoice #</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query = "SELECT ri.*, v.registration_number 
                                         FROM repair_invoices ri 
                                         JOIN vehicles v ON ri.vehicle_id = v.id 
                                         WHERE v.customer_id = $customer_id 
                                         ORDER BY ri.invoice_date DESC 
                                         LIMIT 10";
                                $result = Database::search($query);
                                while ($repair = $result->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><?php echo date('Y-m-d', strtotime($repair['invoice_date'])); ?></td>
                                    <td><?php echo $repair['registration_number']; ?></td>
                                    <td><?php echo $repair['invoice_number']; ?></td>
                                    <td><?php echo formatCurrency($repair['total_amount']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo getStatusColor($repair['payment_status']); ?>">
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

<?php
// Helper function for payment status colors
function getStatusColor($status) {
    switch ($status) {
        case 'paid':
            return 'success';
        case 'partial':
            return 'warning';
        default:
            return 'danger';
    }
}

include 'footer.php';
?>
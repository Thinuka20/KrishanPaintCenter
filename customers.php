<?php
// customers.php - Customer management
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
            <h2>Customers</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="add_customer.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Customer
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <!-- Quick Stats -->
            <div class="row mt-4 mb-5">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <?php
                            $query = "SELECT COUNT(*) as count FROM customers";
                            $result = Database::search($query);
                            $total_customers = $result->fetch_assoc()['count'];
                            ?>
                            <h5 class="card-title">Total Customers</h5>
                            <h3><?php echo $total_customers; ?></h3>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <?php
                            $query = "SELECT COUNT(*) as count FROM vehicles";
                            $result = Database::search($query);
                            $total_vehicles = $result->fetch_assoc()['count'];
                            ?>
                            <h5 class="card-title">Registered Vehicles</h5>
                            <h3><?php echo $total_vehicles; ?></h3>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <?php
                            $query = "SELECT COUNT(*) as count FROM repair_invoices 
                                     WHERE invoice_date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)";
                            $result = Database::search($query);
                            $recent_repairs = $result->fetch_assoc()['count'];
                            ?>
                            <h5 class="card-title">Recent Repairs</h5>
                            <h3><?php echo $recent_repairs; ?> <small>in 30 days</small></h3>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card bg-warning">
                        <div class="card-body">
                            <?php
                            $query = "SELECT COUNT(DISTINCT customer_id) as count 
                                     FROM vehicles v 
                                     LEFT JOIN repair_invoices ri ON v.id = ri.vehicle_id 
                                     WHERE ri.invoice_date IS NULL
                                     OR ri.invoice_date < DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)";
                            $result = Database::search($query);
                            $inactive_customers = $result->fetch_assoc()['count'];
                            ?>
                            <h5 class="card-title">Inactive Customers</h5>
                            <h3><?php echo $inactive_customers; ?> <small>6+ months</small></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped datatable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Vehicles</th>
                            <th>Total Repairs</th>
                            <th>Last Visit</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT c.*, 
                                 COUNT(DISTINCT v.id) as vehicle_count,
                                 COUNT(DISTINCT ri.id) as repair_count,
                                 MAX(ri.invoice_date) as last_visit
                                 FROM customers c
                                 LEFT JOIN vehicles v ON c.id = v.customer_id
                                 LEFT JOIN repair_invoices ri ON v.id = ri.vehicle_id
                                 GROUP BY c.id
                                 ORDER BY c.name";
                        $result = Database::search($query);
                        while ($row = $result->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo $row['name']; ?></td>
                            <td><?php echo $row['phone']; ?></td>
                            <td><?php echo $row['email']; ?></td>
                            <td>
                                <span class="badge bg-info">
                                    <?php echo $row['vehicle_count']; ?> vehicles
                                </span>
                            </td>
                            <td><?php echo $row['repair_count']; ?></td>
                            <td>
                                <?php 
                                echo $row['last_visit'] ? 
                                     date('Y-m-d', strtotime($row['last_visit'])) : 
                                     'Never';
                                ?>
                            </td>
                            <td class="action-buttons">
                                <a href="view_customer.php?id=<?php echo $row['id']; ?>" 
                                   class="btn btn-sm btn-info" title="View Details">
                                    <i class="fas fa-eye text-light"></i>
                                </a>
                                <a href="edit_customer.php?id=<?php echo $row['id']; ?>" 
                                   class="btn btn-sm btn-primary" title="Edit">
                                    <i class="fas fa-edit text-light"></i>
                                </a>
                                <a href="customer_history.php?id=<?php echo $row['id']; ?>" 
                                   class="btn btn-sm btn-success" title="Service History">
                                    <i class="fas fa-history text-light"></i>
                                </a>
                                <a href="add_vehicle.php?customer_id=<?php echo $row['id']; ?>" 
                                   class="btn btn-sm btn-warning" title="Add Vehicle">
                                    <i class="fas fa-car text-light"></i>
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

<script>
$(document).ready(function() {
    $('.datatable').DataTable({
        "order": [[0, "asc"]],
        "pageLength": 25,
        "columnDefs": [
            { "targets": -1, "orderable": false }
        ]
    });
});
</script>

<?php include 'footer.php'; ?>
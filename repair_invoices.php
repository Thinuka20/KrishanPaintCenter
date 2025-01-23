<?php
// repair_invoices.php - Repair invoices listing
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
            <h2>Repair Invoices</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="add_repair_invoice.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create New Invoice
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped datatable">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Vehicle</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT ri.*, v.registration_number, c.name as customer_name 
                        FROM repair_invoices ri 
                        LEFT JOIN vehicles v ON ri.vehicle_id = v.id 
                        LEFT JOIN customers c ON v.customer_id = c.id 
                        ORDER BY ri.id DESC";
                        $result = Database::search($query);
                        while ($row = $result->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo $row['invoice_number']; ?></td>
                            <td><?php echo $row['registration_number']; ?></td>
                            <td><?php echo $row['customer_name']; ?></td>
                            <td><?php echo date('Y-m-d', strtotime($row['invoice_date'])); ?></td>
                            <td><?php echo formatCurrency($row['total_amount']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $row['payment_status'] === 'paid' ? 'success' : 
                                    ($row['payment_status'] === 'partial' ? 'warning' : 'danger'); ?>">
                                    <?php echo ucfirst($row['payment_status']); ?>
                                </span>
                            </td>
                            <td class="action-buttons">
                                <a href="view_repair_invoice.php?id=<?php echo $row['id']; ?>" 
                                   class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="print_repair_invoice.php?id=<?php echo $row['id']; ?>" 
                                   class="btn btn-sm btn-secondary" target="_blank">
                                    <i class="fas fa-print"></i>
                                </a>
                                <a href="edit_repair_invoice.php?id=<?php echo $row['id']; ?>" 
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="delete_repair_invoice.php?id=<?php echo $row['id']; ?>" 
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirmDelete();">
                                    <i class="fas fa-trash"></i>
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
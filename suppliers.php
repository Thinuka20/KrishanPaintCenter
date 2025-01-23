<?php
// suppliers.php - List all suppliers
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
            <h2>Suppliers</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="add_supplier.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Supplier
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped datatable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Contact Person</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Balance</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT s.*, 
                                 (SELECT SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE -amount END) 
                                  FROM supplier_transactions 
                                  WHERE supplier_id = s.id) as balance 
                                 FROM suppliers s 
                                 ORDER BY s.name";
                        $result = Database::search($query);
                        while ($row = $result->fetch_assoc()):
                            $balance = $row['balance'] ?? 0;
                        ?>
                        <tr>
                            <td><?php echo $row['name']; ?></td>
                            <td><?php echo $row['contact_person']; ?></td>
                            <td><?php echo $row['phone']; ?></td>
                            <td><?php echo $row['email']; ?></td>
                            <td class="<?php echo $balance < 0 ? 'text-danger' : 'text-success'; ?>">
                                <?php echo formatCurrency(abs($balance)); ?>
                                <?php echo $balance < 0 ? ' (Due)' : ' (Credit)'; ?>
                            </td>
                            <td class="action-buttons">
                                <a href="view_supplier.php?id=<?php echo $row['id']; ?>" 
                                   class="btn btn-sm btn-info">
                                    <i class="fas fa-eye text-light"></i>
                                </a>
                                <a href="edit_supplier.php?id=<?php echo $row['id']; ?>" 
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit text-light"></i>
                                </a>
                                <a href="supplier_transactions.php?id=<?php echo $row['id']; ?>" 
                                   class="btn btn-sm btn-success">
                                    <i class="fas fa-exchange-alt text-light"></i>
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
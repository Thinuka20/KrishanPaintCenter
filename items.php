<?php
// items.php - Items listing and management
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();

include 'header.php';
?>

<div class="container content">
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
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Items Management</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="add_item.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Item
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped datatable">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Stock</th>
                            <th>Unit Price</th>
                            <th>Min. Stock</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT * FROM items ORDER BY id DESC";
                        $result = Database::search($query);
                        while ($row = $result->fetch_assoc()):
                        ?>
                            <tr>
                                <td><?php echo $row['item_code']; ?></td>
                                <td><?php echo $row['name']; ?></td>
                                <td><?php echo $row['stock_quantity']; ?></td>
                                <td><?php echo formatCurrency($row['unit_price']); ?></td>
                                <td><?php echo $row['minimum_stock']; ?></td>
                                <td class="action-buttons">
                                    <a href="edit_item.php?id=<?php echo $row['id']; ?>"
                                        class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit text-light"></i>
                                    </a>
                                    <a href="update_stock.php?id=<?php echo $row['id']; ?>"
                                        class="btn btn-sm btn-success">
                                        <i class="fas fa-box text-light"></i>
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
<?php
// edit_item.php - Edit item details
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();

$item_id = (int)$_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_code = validateInput($_POST['item_code']);
    $name = validateInput($_POST['name']);
    $description = validateInput($_POST['description']);
    $unit_price = (float)$_POST['unit_price'];
    $minimum_stock = (int)$_POST['minimum_stock'];
    
    // Check if item code is unique (excluding current item)
    $query = "SELECT id FROM items WHERE item_code = '$item_code' AND id != $item_id";
    $result = Database::search($query);
    if ($result->num_rows > 0) {
        $error = "Item code already exists. Please choose a different code.";
    } else {
        $query = "UPDATE items 
                  SET item_code = '$item_code', 
                      name = '$name', 
                      description = '$description', 
                      unit_price = $unit_price, 
                      minimum_stock = $minimum_stock 
                  WHERE id = $item_id";
        
        Database::iud($query);
        header("Location: items.php");
        exit();
    }
}

// Get item details
$query = "SELECT * FROM items WHERE id = $item_id";
$result = Database::search($query);
$item = $result->fetch_assoc();

include 'header.php';
?>

<div class="container content">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h3>Edit Item</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" onsubmit="return validateForm('edit-item-form')" id="edit-item-form">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">Item Code</label>
                                    <input type="text" name="item_code" class="form-control" 
                                           required value="<?php echo $item['item_code']; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="required">Name</label>
                                    <input type="text" name="name" class="form-control" 
                                           required value="<?php echo $item['name']; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>Description</label>
                                    <textarea name="description" class="form-control" rows="3"><?php echo $item['description']; ?></textarea>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">Unit Price</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rs.</span>
                                        <input type="number" name="unit_price" class="form-control" 
                                               required step="0.01" min="0" 
                                               value="<?php echo $item['unit_price']; ?>">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Current Stock</label>
                                    <input type="number" class="form-control" readonly 
                                           value="<?php echo $item['stock_quantity']; ?>">
                                    <small class="text-muted">
                                        Use "Update Stock" option to modify stock quantity
                                    </small>
                                </div>
                                
                                <div class="form-group">
                                    <label class="required">Minimum Stock Level</label>
                                    <input type="number" name="minimum_stock" class="form-control" 
                                           required min="0" value="<?php echo $item['minimum_stock']; ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Stock Status Card -->
                        <div class="card mt-4 mb-4">
                            <div class="card-body">
                                <h5>Stock Status</h5>
                                <div class="row">
                                    <div class="col-md-4">
                                        <p class="mb-1">Current Stock: <?php echo $item['stock_quantity']; ?></p>
                                    </div>
                                    <div class="col-md-4">
                                        <p class="mb-1">Minimum Stock: <?php echo $item['minimum_stock']; ?></p>
                                    </div>
                                    <div class="col-md-4">
                                        <p class="mb-1">Stock Value: <?php echo formatCurrency($item['stock_quantity'] * $item['unit_price']); ?></p>
                                    </div>
                                </div>
                                <div class="progress mt-2">
                                    <?php
                                    $stock_percentage = ($item['stock_quantity'] / max($item['minimum_stock'], 1)) * 100;
                                    $progress_class = $stock_percentage <= 100 ? 'bg-danger' : 
                                                    ($stock_percentage <= 150 ? 'bg-warning' : 'bg-success');
                                    ?>
                                    <div class="progress-bar <?php echo $progress_class; ?>" 
                                         role="progressbar" 
                                         style="width: <?php echo min($stock_percentage, 100); ?>%">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Movement History -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5>Recent Stock Movements</h5>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Type</th>
                                                <th>Quantity</th>
                                                <th>Reference</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $query = "SELECT * FROM stock_movements 
                                                     WHERE item_id = $item_id 
                                                     ORDER BY movement_date DESC LIMIT 5";
                                            $result = Database::search($query);
                                            while ($movement = $result->fetch_assoc()):
                                            ?>
                                            <tr>
                                                <td><?php echo date('Y-m-d', strtotime($movement['movement_date'])); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $movement['movement_type'] === 'in' ? 'success' : 'danger'; ?>">
                                                        <?php echo ucfirst($movement['movement_type']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo abs($movement['quantity']); ?></td>
                                                <td><?php echo $movement['reference']; ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Update Item</button>
                            <a href="items.php" class="btn btn-secondary">Cancel</a>
                            <a href="update_stock.php?id=<?php echo $item_id; ?>" class="btn btn-success float-end">
                                <i class="fas fa-box"></i> Update Stock
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add client-side validation for item code (alphanumeric only)
    document.querySelector('input[name="item_code"]').addEventListener('input', function() {
        this.value = this.value.replace(/[^a-zA-Z0-9-]/g, '');
    });

    // Add validation for minimum stock
    document.querySelector('input[name="minimum_stock"]').addEventListener('change', function() {
        if (this.value < 0) {
            alert('Minimum stock cannot be negative.');
            this.value = 0;
        }
    });
});
</script>

<?php include 'footer.php'; ?>
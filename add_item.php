<?php
// add_item.php - Add new item
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_code = validateInput($_POST['item_code']);
    $name = validateInput($_POST['name']);
    $description = validateInput($_POST['description']);
    $unit_price = (float)$_POST['unit_price'];
    $stock_quantity = (int)$_POST['stock_quantity'];
    $minimum_stock = (int)$_POST['minimum_stock'];

    // Check if item code already exists
    $query = "SELECT id FROM items WHERE item_code = '$item_code'";
    $result = Database::search($query);
    if ($result->num_rows > 0) {
        $error = "Item code already exists. Please choose a different code.";
    } else {
        // Start transaction
        Database::connection();
        Database::$connection->begin_transaction();

        try {
            // Insert item
            $query = "INSERT INTO items (item_code, name, description, unit_price, stock_quantity, minimum_stock) 
                      VALUES ('$item_code', '$name', '$description', $unit_price, $stock_quantity, $minimum_stock)";
            Database::iud($query);
            $item_id = Database::$connection->insert_id;

            // Record initial stock if greater than 0
            if ($stock_quantity > 0) {
                $query = "INSERT INTO stock_movements (item_id, movement_type, quantity, reference) 
                          VALUES ($item_id, 'in', $stock_quantity, 'Initial stock')";
                Database::iud($query);
            }

            Database::$connection->commit();
            header("Location: items.php");
            exit();

        } catch (Exception $e) {
            Database::$connection->rollback();
            $error = "Error adding item: " . $e->getMessage();
        }
    }
}

include 'header.php';
?>

<div class="container content">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h3>Add New Item</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" onsubmit="return validateForm('add-item-form')" id="add-item-form">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">Item Code</label>
                                    <input type="text" name="item_code" class="form-control" required 
                                           value="<?php echo isset($_POST['item_code']) ? $_POST['item_code'] : ''; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="required">Name</label>
                                    <input type="text" name="name" class="form-control" required 
                                           value="<?php echo isset($_POST['name']) ? $_POST['name'] : ''; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>Description</label>
                                    <textarea name="description" class="form-control" rows="3"><?php echo isset($_POST['description']) ? $_POST['description'] : ''; ?></textarea>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">Unit Price</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rs.</span>
                                        <input type="number" name="unit_price" class="form-control" required 
                                               step="0.01" min="0" 
                                               value="<?php echo isset($_POST['unit_price']) ? $_POST['unit_price'] : ''; ?>">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="required">Initial Stock</label>
                                    <input type="number" name="stock_quantity" class="form-control" 
                                           required min="0" 
                                           value="<?php echo isset($_POST['stock_quantity']) ? $_POST['stock_quantity'] : '0'; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="required">Minimum Stock Level</label>
                                    <input type="number" name="minimum_stock" class="form-control" 
                                           required min="0" 
                                           value="<?php echo isset($_POST['minimum_stock']) ? $_POST['minimum_stock'] : '0'; ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary">Save Item</button>
                            <a href="items.php" class="btn btn-secondary">Cancel</a>
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

    // Add client-side validation for numbers
    document.querySelectorAll('input[type="number"]').forEach(function(input) {
        input.addEventListener('change', function() {
            if (this.value < 0) {
                alert('Value cannot be negative.');
                this.value = 0;
            }
        });
    });
});
</script>

<?php include 'footer.php'; ?>
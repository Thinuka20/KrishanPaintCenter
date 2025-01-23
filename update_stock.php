<?php
// update_stock.php - Update item stock
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();

$item_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantity = (int)$_POST['quantity'];
    $operation = $_POST['operation'];

    $query = "SELECT stock_quantity FROM items WHERE id = $item_id";
    $result = Database::search($query);
    $row = $result->fetch_assoc();

    $current_stock = (int)$row['stock_quantity'];
    $new_stock = $operation === 'add' ?
        $current_stock + $quantity :
        $current_stock - $quantity;

    $movement_type = $operation === 'add' ? 'in' : 'out';
    $reference = "Manual stock " . ($operation === 'add' ? "addition" : "reduction");

    if ($new_stock >= 0) {
        $query = "UPDATE items SET stock_quantity = $new_stock WHERE id = $item_id";
        Database::iud($query);
        $query2 = "INSERT INTO stock_movements (item_id, movement_type, quantity, reference) 
          VALUES ($item_id, '$movement_type', $quantity, '$reference')";
        Database::iud($query2);
        header("Location: items.php");
        exit();
    }
}

$query = "SELECT * FROM items WHERE id = $item_id";
$result = Database::search($query);
$item = $result->fetch_assoc();

include 'header.php';
?>

<div class="container content">
    <div class="row">
        <div class="col-md-6 offset-md-3">
            <div class="card">
                <div class="card-header">
                    <h3>Update Stock - <?php echo $item['name']; ?></h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label>Current Stock</label>
                            <input type="text" class="form-control" value="<?php echo $item['stock_quantity']; ?>" readonly>
                        </div>

                        <div class="form-group">
                            <label class="required">Quantity</label>
                            <input type="number" name="quantity" class="form-control" required min="1">
                        </div>

                        <div class="form-group">
                            <label class="required">Operation</label>
                            <select name="operation" class="form-control" required>
                                <option value="add">Add Stock</option>
                                <option value="subtract">Remove Stock</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Update Stock</button>
                            <a href="items.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
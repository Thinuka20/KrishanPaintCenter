<?php
// estimates.php - List all estimates
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicle_id = (int)$_POST['vehicle_id'];
    $estimate_date = validateInput($_POST['estimate_date']);
    $valid_until = validateInput($_POST['valid_until']);
    $notes = validateInput($_POST['notes']);
    $total_amount = 0;
    
    // Generate estimate number
    $estimate_number = generateUniqueNumber('EST');
    
    // Start transaction
    Database::connection();
    Database::$connection->begin_transaction();
    
    try {
        // Insert estimate header
        $query = "INSERT INTO estimates (vehicle_id, estimate_number, estimate_date, valid_until, notes, status) 
                  VALUES ($vehicle_id, '$estimate_number', '$estimate_date', '$valid_until', '$notes', 'pending')";
        Database::iud($query);
        $estimate_id = Database::$connection->insert_id;
        
        // Insert estimate items
        foreach ($_POST['item_id'] as $key => $item_id) {
            if (!empty($item_id)) {
                $quantity = (int)$_POST['quantity'][$key];
                $unit_price = (float)$_POST['unit_price'][$key];
                $subtotal = $quantity * $unit_price;
                $total_amount += $subtotal;
                
                $query = "INSERT INTO estimate_items (estimate_id, item_id, quantity, unit_price, subtotal) 
                          VALUES ($estimate_id, $item_id, $quantity, $unit_price, $subtotal)";
                Database::iud($query);
            }
        }
        
        // Update estimate total
        $query = "UPDATE estimates SET total_amount = $total_amount WHERE id = $estimate_id";
        Database::iud($query);
        
        Database::$connection->commit();
        header("Location: view_estimate.php?id=$estimate_id");
        exit();
        
    } catch (Exception $e) {
        Database::$connection->rollback();
        $error = "Error creating estimate: " . $e->getMessage();
    }
}

include 'header.php';
?>

<div class="container content">
    <div class="card">
        <div class="card-header">
            <h3>Create New Estimate</h3>
        </div>
        <div class="card-body">
            <form method="POST" id="estimate-form" onsubmit="return validateForm('estimate-form')">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required">Vehicle</label>
                            <select name="vehicle_id" class="form-control select2" required>
                                <option value="">Select Vehicle</option>
                                <?php
                                $query = "SELECT v.*, c.name as customer_name 
                                         FROM vehicles v 
                                         LEFT JOIN customers c ON v.customer_id = c.id 
                                         ORDER BY v.registration_number";
                                $result = Database::search($query);
                                while ($row = $result->fetch_assoc()):
                                ?>
                                <option value="<?php echo $row['id']; ?>">
                                    <?php echo $row['registration_number'] . ' - ' . $row['customer_name']; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="required">Estimate Date</label>
                            <input type="date" name="estimate_date" class="form-control" 
                                   required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="required">Valid Until</label>
                            <input type="date" name="valid_until" class="form-control" 
                                   required value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control" rows="4"></textarea>
                        </div>
                    </div>
                </div>

                <h4 class="mt-4">Estimate Items</h4>
                <div class="table-responsive">
                    <table class="table table-bordered" id="items-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Quantity</th>
                                <th>Unit Price</th>
                                <th>Subtotal</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="items-container">
                            <tr class="item-row">
                                <td>
                                    <select name="item_id[]" class="form-control select2 item-select" required>
                                        <option value="">Select Item</option>
                                        <?php
                                        $query = "SELECT * FROM items ORDER BY name";
                                        $result = Database::search($query);
                                        while ($row = $result->fetch_assoc()):
                                        ?>
                                        <option value="<?php echo $row['id']; ?>" 
                                                data-price="<?php echo $row['unit_price']; ?>">
                                            <?php echo $row['name']; ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" name="quantity[]" class="form-control item-quantity" 
                                           required min="1" onchange="updateSubtotal(this.closest('tr'))">
                                </td>
                                <td>
                                    <input type="number" name="unit_price[]" class="form-control item-price" 
                                           required step="0.01" onchange="updateSubtotal(this.closest('tr'))">
                                </td>
                                <td>
                                    <input type="number" class="form-control item-subtotal" readonly step="0.01">
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm" 
                                            onclick="this.closest('tr').remove(); calculateTotal();">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-12">
                        <button type="button" class="btn btn-success" onclick="addItemRow()">
                            <i class="fas fa-plus"></i> Add Item
                        </button>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 offset-md-6 text-end">
                        <h4>Total Amount: <span id="total-amount">Rs. 0.00</span></h4>
                    </div>
                </div>

                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary">Save Estimate</button>
                    <a href="estimates.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<template id="item-row-template">
    <tr class="item-row">
        <td>
            <select name="item_id[]" class="form-control select2 item-select" required>
                <option value="">Select Item</option>
                <?php
                $query = "SELECT * FROM items ORDER BY name";
                $result = Database::search($query);
                while ($row = $result->fetch_assoc()):
                ?>
                <option value="<?php echo $row['id']; ?>" 
                        data-price="<?php echo $row['unit_price']; ?>">
                    <?php echo $row['name']; ?>
                </option>
                <?php endwhile; ?>
            </select>
        </td>
        <td>
            <input type="number" name="quantity[]" class="form-control item-quantity" 
                   required min="1" onchange="updateSubtotal(this.closest('tr'))">
        </td>
        <td>
            <input type="number" name="unit_price[]" class="form-control item-price" 
                   required step="0.01" onchange="updateSubtotal(this.closest('tr'))">
        </td>
        <td>
            <input type="number" class="form-control item-subtotal" readonly step="0.01">
        </td>
        <td>
            <button type="button" class="btn btn-danger btn-sm" 
                    onclick="this.closest('tr').remove(); calculateTotal();">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    </tr>
</template>

<?php include 'footer.php'; ?>

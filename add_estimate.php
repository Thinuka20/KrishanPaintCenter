<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();

$vehicle_id = isset($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : 0;

if ($vehicle_id) {
    $query = "SELECT v.*, c.name as customer_name, c.phone 
              FROM vehicles v 
              LEFT JOIN customers c ON v.customer_id = c.id 
              WHERE v.id = $vehicle_id";
    $result = Database::search($query);
    $vehicle = $result->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicle_id = (int)$_POST['vehicle_id'];
    $estimate_date = validateInput($_POST['estimate_date']);
    $valid_until = validateInput($_POST['valid_until']);
    $notes = validateInput($_POST['notes']);
    $total_amount = 0;
    
    // Generate estimate number
    $estimate_number = generateUniqueNumber('EST');
    
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
    <div class="row mb-3">
        <div class="col">
            <h2>Create New Estimate</h2>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" id="estimate-form" onsubmit="return validateForm('estimate-form')">
                <div class="row">
                    <!-- Vehicle Selection -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required">Vehicle</label>
                            <select name="vehicle_id" class="form-control select2" required 
                                    <?php echo $vehicle_id ? 'disabled' : ''; ?>>
                                <option value="">Select Vehicle</option>
                                <?php
                                $query = "SELECT v.*, c.name as customer_name 
                                         FROM vehicles v 
                                         LEFT JOIN customers c ON v.customer_id = c.id 
                                         ORDER BY v.registration_number";
                                $result = Database::search($query);
                                while ($row = $result->fetch_assoc()):
                                ?>
                                <option value="<?php echo $row['id']; ?>" 
                                        <?php echo $vehicle_id == $row['id'] ? 'selected' : ''; ?>>
                                    <?php echo $row['registration_number'] . ' - ' . $row['customer_name']; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                            <?php if ($vehicle_id): ?>
                            <input type="hidden" name="vehicle_id" value="<?php echo $vehicle_id; ?>">
                            <?php endif; ?>
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
                            <textarea name="notes" class="form-control" rows="5"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Items Section -->
                <h4 class="mt-4">Estimate Items</h4>
                <div class="table-responsive">
                    <table class="table table-bordered" id="items-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th width="150">Quantity</th>
                                <th width="200">Unit Price</th>
                                <th width="200">Subtotal</th>
                                <th width="50">Action</th>
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
                                            <?php echo $row['name'] . ' (Rs.' . number_format($row['unit_price'], 2) . ')'; ?>
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

                <div class="row mt-3">
                    <div class="col-md-6">
                        <button type="button" class="btn btn-success" onclick="addItemRow()">
                            <i class="fas fa-plus"></i> Add Item
                        </button>
                    </div>
                    <div class="col-md-6 text-end">
                        <h4>Total Amount: <span id="total-amount">Rs. 0.00</span></h4>
                    </div>
                </div>

                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary">Save Estimate</button>
                    <a href="<?php echo $vehicle_id ? "vehicle_history.php?id=$vehicle_id" : "estimates.php"; ?>" 
                       class="btn btn-secondary">Cancel</a>
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
                    <?php echo $row['name'] . ' (Rs.' . number_format($row['unit_price'], 2) . ')'; ?>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    initializeSelect2();
    setupItemSelectHandlers();
});

function initializeSelect2() {
    $('.select2').select2({
        width: '100%'
    });
}

function setupItemSelectHandlers() {
    $(document).on('select2:select', '.item-select', function(e) {
        const row = $(this).closest('tr');
        const price = $(this).find(':selected').data('price');
        row.find('.item-price').val(price);
        updateSubtotal(row[0]);
    });
}

function addItemRow() {
    const template = document.getElementById('item-row-template');
    const clone = template.content.cloneNode(true);
    document.getElementById('items-container').appendChild(clone);
    initializeSelect2();
}

function updateSubtotal(row) {
    const quantity = parseFloat(row.querySelector('.item-quantity').value) || 0;
    const price = parseFloat(row.querySelector('.item-price').value) || 0;
    const subtotal = quantity * price;
    row.querySelector('.item-subtotal').value = subtotal.toFixed(2);
    calculateTotal();
}

function calculateTotal() {
    let total = 0;
    document.querySelectorAll('.item-subtotal').forEach(function(element) {
        total += parseFloat(element.value) || 0;
    });
    document.getElementById('total-amount').textContent = 'Rs. ' + total.toFixed(2);
}

function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form.checkValidity()) {
        return false;
    }

    const validUntil = new Date(form.valid_until.value);
    const today = new Date();
    if (validUntil < today) {
        alert('Valid until date must be in the future');
        return false;
    }

    return true;
}
</script>

<?php include 'footer.php'; ?>
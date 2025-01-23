<?php
// add_item_invoice.php - Create new item invoice
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = (int)$_POST['customer_id'];
    $invoice_date = validateInput($_POST['invoice_date']);
    $notes = validateInput($_POST['notes']);
    $total_amount = 0;
    
    // Generate invoice number
    $invoice_number = generateUniqueNumber('II');
    
    // Start transaction
    Database::connection();
    Database::$connection->begin_transaction();
    
    try {
        // Insert invoice header
        $query = "INSERT INTO item_invoices (customer_id, invoice_number, invoice_date, notes, payment_status) 
                  VALUES ($customer_id, '$invoice_number', '$invoice_date', '$notes', 'pending')";
        Database::iud($query);
        $invoice_id = Database::$connection->insert_id;
        
        // Insert invoice items
        foreach ($_POST['item_id'] as $key => $item_id) {
            if (!empty($item_id)) {
                $quantity = (int)$_POST['quantity'][$key];
                $unit_price = (float)$_POST['unit_price'][$key];
                $subtotal = $quantity * $unit_price;
                $total_amount += $subtotal;
                
                $query = "INSERT INTO item_invoice_details (item_invoice_id, item_id, quantity, unit_price, subtotal) 
                          VALUES ($invoice_id, $item_id, $quantity, $unit_price, $subtotal)";
                Database::iud($query);
                
                // Update stock
                $query = "UPDATE items SET stock_quantity = stock_quantity - $quantity WHERE id = $item_id";
                Database::iud($query);
            }
        }
        
        // Update invoice total
        $query = "UPDATE item_invoices SET total_amount = $total_amount WHERE id = $invoice_id";
        Database::iud($query);
        
        Database::$connection->commit();
        header("Location: view_item_invoice.php?id=$invoice_id");
        exit();
        
    } catch (Exception $e) {
        Database::$connection->rollback();
        $error = "Error creating invoice: " . $e->getMessage();
    }
}

include 'header.php';
?>

<div class="container content">
    <div class="card">
        <div class="card-header">
            <h3>Create New Item Invoice</h3>
        </div>
        <div class="card-body">
            <form method="POST" id="item-invoice-form" onsubmit="return validateForm('item-invoice-form')">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required">Customer</label>
                            <select name="customer_id" class="form-control select2" required>
                                <option value="">Select Customer</option>
                                <?php
                                $query = "SELECT * FROM customers ORDER BY name";
                                $result = Database::search($query);
                                while ($row = $result->fetch_assoc()):
                                ?>
                                <option value="<?php echo $row['id']; ?>">
                                    <?php echo $row['name'] . ' - ' . $row['phone']; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="required">Invoice Date</label>
                            <input type="date" name="invoice_date" class="form-control" 
                                   required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control" rows="4"></textarea>
                        </div>
                    </div>
                </div>

                <h4 class="mt-4">Invoice Items</h4>
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
                                                data-price="<?php echo $row['unit_price']; ?>"
                                                data-stock="<?php echo $row['stock_quantity']; ?>">
                                            <?php echo $row['name'] . ' (Stock: ' . $row['stock_quantity'] . ')'; ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="number" name="quantity[]" class="form-control item-quantity" 
                                           required min="1" onchange="validateStock(this)">
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
                    <button type="submit" class="btn btn-primary">Save Invoice</button>
                    <a href="invoices.php?type=item" class="btn btn-secondary">Cancel</a>
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
                        data-price="<?php echo $row['unit_price']; ?>"
                        data-stock="<?php echo $row['stock_quantity']; ?>">
                    <?php echo $row['name'] . ' (Stock: ' . $row['stock_quantity'] . ')'; ?>
                </option>
                <?php endwhile; ?>
            </select>
        </td>
        <td>
            <input type="number" name="quantity[]" class="form-control item-quantity" 
                   required min="1" onchange="validateStock(this)">
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
function validateStock(input) {
    const row = input.closest('tr');
    const select = row.querySelector('.item-select');
    const maxStock = parseInt(select.options[select.selectedIndex].dataset.stock);
    const quantity = parseInt(input.value);
    
    if (quantity > maxStock) {
        alert('Quantity cannot exceed available stock (' + maxStock + ')');
        input.value = maxStock;
    }
    updateSubtotal(row);
}

// Other JavaScript functions are in main.js
</script>

<?php include 'footer.php'; ?>
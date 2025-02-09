<?php
// add_item_invoice.php - Create new item invoice
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
            <h2>Create New Item Invoice</h2>
        </div>
        <div class="col-md-6 text-end">
            <button onclick="history.back()" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Invoice
            </button>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <form method="POST" action="process_item_invoice.php" id="item-invoice-form">
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

                <div class="row mb-3">
                    <div class="col-md-12">
                        <h4>Invoice Items</h4>
                        <div class="table-responsive">
                            <table class="table" id="itemsTable">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Quantity</th>
                                        <th>Unit Price</th>
                                        <th>Subtotal</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td>
                                            <select id="newItem" class="form-control select2">
                                                <option value="">Select Item</option>
                                                <?php
                                                $query = "SELECT * FROM items ORDER BY name";
                                                $result = Database::search($query);
                                                while ($row = $result->fetch_assoc()):
                                                ?>
                                                    <option value="<?php echo $row['id']; ?>"
                                                        data-price="<?php echo $row['unit_price']; ?>"
                                                        data-stock="<?php echo $row['stock_quantity']; ?>"
                                                        data-name="<?php echo $row['name']; ?>">
                                                        <?php echo $row['name'] . ' (Stock: ' . $row['stock_quantity'] . ')'; ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" id="newQuantity" class="form-control" min="1">
                                        </td>
                                        <td>
                                            <input type="number" id="newUnitPrice" class="form-control">
                                        </td>
                                        <td>
                                            <input type="number" id="newSubtotal" class="form-control" readonly>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-success" onclick="addToCart()">
                                                <i class="fas fa-plus"></i> Add
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-end"><strong>Total Amount:</strong></td>
                                        <td colspan="2"><span id="totalAmount">0.00</span></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Payment -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label>Payment Type</label>
                        <select class="form-select" name="payment_type" required>
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="cheque">Cheque</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label>Payment Amount</label>
                        <input type="number" class="form-control" name="payment_amount" id="paymentAmount" required>
                    </div>
                </div>

                <input type="hidden" name="cart_items" id="cartItems">
                <input type="hidden" name="total_amount" id="finalAmount">

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
    document.addEventListener('DOMContentLoaded', function() {
        // Basic event listener for select change
        document.getElementById('newItem').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption && selectedOption.value) {
                const price = selectedOption.getAttribute('data-price');
                const unitPriceInput = document.getElementById('newUnitPrice');
                if (price) {
                    unitPriceInput.value = parseFloat(price).toFixed(2);
                    updateNewSubtotal();
                }
            } else {
                const unitPriceInput = document.getElementById('newUnitPrice');
                unitPriceInput.value = '';
                unitPriceInput.removeAttribute('readonly'); // Allow manual entry when no item selected
                document.getElementById('newSubtotal').value = '';
            }
        });

        // Handle quantity changes
        document.getElementById('newQuantity').addEventListener('input', function() {
            updateNewSubtotal();
        });

        // Handle unit price changes
        document.getElementById('newUnitPrice').addEventListener('input', function() {
            updateNewSubtotal();
        });
    });

    function updateNewSubtotal() {
        const quantity = parseInt(document.getElementById('newQuantity').value) || 0;
        const price = parseFloat(document.getElementById('newUnitPrice').value) || 0;
        document.getElementById('newSubtotal').value = (quantity * price).toFixed(2);
    }

    let cart = [];

    function addToCart() {
    const itemSelect = document.getElementById('newItem');
    const selectedOption = itemSelect.options[itemSelect.selectedIndex];
    const quantity = parseInt(document.getElementById('newQuantity').value);
    const unitPrice = parseFloat(document.getElementById('newUnitPrice').value);

    if (!itemSelect.value || !quantity || !unitPrice) {
        alert('Please select an item and enter quantity');
        return;
    }

    const maxStock = parseInt(selectedOption.getAttribute('data-stock'));
    if (quantity > maxStock) {
        alert('Quantity cannot exceed available stock (' + maxStock + ')');
        return;
    }

    // Check if item already exists in cart
    const existingItemIndex = cart.findIndex(item => item.item_id === itemSelect.value);
    
    if (existingItemIndex !== -1) {
        // Calculate total new quantity
        const totalNewQuantity = cart[existingItemIndex].quantity + quantity;
        
        // Check if total quantity exceeds stock
        if (totalNewQuantity > maxStock) {
            alert('Total quantity would exceed available stock (' + maxStock + ')');
            clearInputs();
            return;
        }

        // Show confirmation popup
        if (confirm('This item already exists in the cart. Would you like to update the quantity?')) {
            // Update existing item
            cart[existingItemIndex].quantity = totalNewQuantity;
            cart[existingItemIndex].subtotal = totalNewQuantity * unitPrice;
        } else {
            return; // User canceled the operation
        }
    } else {
        // Add new item to cart
        cart.push({
            item_id: itemSelect.value,
            name: selectedOption.getAttribute('data-name'),
            quantity: quantity,
            unit_price: unitPrice,
            subtotal: quantity * unitPrice
        });
    }

    updateCartDisplay();
    clearInputs();
}

    function removeFromCart(index) {
        cart.splice(index, 1);
        updateCartDisplay();
    }

    function updateCartDisplay() {
        const tbody = document.querySelector('#itemsTable tbody');
        tbody.innerHTML = '';
        let total = 0;

        cart.forEach((item, index) => {
            total += item.subtotal;
            tbody.innerHTML += `
            <tr>
                <td>${item.name}</td>
                <td>${item.quantity}</td>
                <td>${item.unit_price.toFixed(2)}</td>
                <td>${item.subtotal.toFixed(2)}</td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeFromCart(${index})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>`;
        });

        document.getElementById('totalAmount').textContent = total.toFixed(2);
        document.getElementById('cartItems').value = JSON.stringify(cart);
        document.getElementById('finalAmount').value = total;
    }

    function clearInputs() {
        const newItem = document.getElementById('newItem');
        newItem.value = '';
        if (typeof $(newItem).select2 === 'function') {
            $(newItem).select2('val', '');
        }
        document.getElementById('newQuantity').value = '';
        document.getElementById('newUnitPrice').value = '';
        document.getElementById('newSubtotal').value = '';
    }

    // Form submission handler
    document.getElementById('item-invoice-form').onsubmit = function(e) {
        if (!document.getElementById('customerSelect').value) {
            e.preventDefault();
            alert('Please select a customer');
            return false;
        }

        if (cart.length === 0) {
            e.preventDefault();
            alert('Please add at least one item');
            return false;
        }

        const paymentAmount = parseFloat(document.getElementById('paymentAmount').value);
        const totalAmount = parseFloat(document.getElementById('finalAmount').value);

        if (paymentAmount < totalAmount) {
            if (!confirm('Payment amount is less than total amount. Continue?')) {
                e.preventDefault();
                return false;
            }
        }

        return true;
    };
</script>

<?php include 'footer.php'; ?>
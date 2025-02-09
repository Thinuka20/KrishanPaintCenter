<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: unauthorized.php");
    exit();
}

$invoice_id = (int)$_GET['id'];

// Get invoice details
$query = "SELECT ii.*, c.name as customer_name, c.phone, c.email
          FROM item_invoices ii 
          LEFT JOIN customers c ON ii.customer_id = c.id 
          WHERE ii.id = $invoice_id";
$result = Database::search($query);
$invoice = $result->fetch_assoc();

include 'header.php';
?>

<div class="container content">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Edit Item Invoice #<?php echo $invoice['invoice_number']; ?></h2>
        </div>
        <div class="col-md-6 text-end">
            <button onclick="history.back()" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Invoice
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" id="item-invoice-form" action="update_item_invoice.php">
                <input type="hidden" name="invoice_id" value="<?php echo $invoice_id; ?>">
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required">Customer</label>
                            <select name="customer_id" id="customerSelect" class="form-control select2" required>
                                <option value="">Select Customer</option>
                                <?php
                                $query = "SELECT * FROM customers ORDER BY name";
                                $result = Database::search($query);
                                while ($row = $result->fetch_assoc()):
                                    $selected = ($row['id'] == $invoice['customer_id']) ? 'selected' : '';
                                ?>
                                    <option value="<?php echo $row['id']; ?>" <?php echo $selected; ?>>
                                        <?php echo $row['name'] . ' - ' . $row['phone']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="required">Invoice Date</label>
                            <input type="date" name="invoice_date" class="form-control" 
                                   required value="<?php echo $invoice['invoice_date']; ?>">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control" rows="4"><?php echo $invoice['notes']; ?></textarea>
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

                <input type="hidden" name="cart_items" id="cartItems">
                <input type="hidden" name="total_amount" id="finalAmount">

                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary">Update Invoice</button>
                    <a href="view_item_invoice.php?id=<?php echo $invoice_id; ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let cart = [];

document.addEventListener('DOMContentLoaded', function() {
    // Load existing invoice items into cart
    <?php
    $query = "SELECT d.*, i.name as item_name, i.stock_quantity 
              FROM item_invoice_details d
              JOIN items i ON d.item_id = i.id
              WHERE d.item_invoice_id = $invoice_id";
    $items = Database::search($query);
    while ($item = $items->fetch_assoc()):
    ?>
    cart.push({
        item_id: '<?php echo $item['item_id']; ?>',
        name: '<?php echo $item['item_name']; ?>',
        quantity: <?php echo $item['quantity']; ?>,
        unit_price: <?php echo $item['unit_price']; ?>,
        subtotal: <?php echo $item['subtotal']; ?>
    });
    <?php endwhile; ?>

    updateCartDisplay();

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
            unitPriceInput.removeAttribute('readonly');
            document.getElementById('newSubtotal').value = '';
        }
    });

    document.getElementById('newQuantity').addEventListener('input', updateNewSubtotal);
    document.getElementById('newUnitPrice').addEventListener('input', updateNewSubtotal);
});

function updateNewSubtotal() {
    const quantity = parseInt(document.getElementById('newQuantity').value) || 0;
    const price = parseFloat(document.getElementById('newUnitPrice').value) || 0;
    document.getElementById('newSubtotal').value = (quantity * price).toFixed(2);
}

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

    const existingItemIndex = cart.findIndex(item => item.item_id === itemSelect.value);
    
    if (existingItemIndex !== -1) {
        const totalNewQuantity = cart[existingItemIndex].quantity + quantity;
        
        // if (totalNewQuantity > maxStock) {
        //     alert('Total quantity would exceed available stock (' + maxStock + ')');
        //     clearInputs();
        //     return;
        // }

        if (confirm('This item already exists in the cart. Would you like to update the quantity?')) {
            cart[existingItemIndex].quantity = totalNewQuantity;
            cart[existingItemIndex].subtotal = totalNewQuantity * unitPrice;
        } else {
            return;
        }
    } else {
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

    const totalAmount = parseFloat(document.getElementById('finalAmount').value);

    return true;
};
</script>

<?php include 'footer.php'; ?>
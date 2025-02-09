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

// Get invoice details with payment info
$query = "SELECT ri.*, v.registration_number, v.make, v.model, v.year, 
                 c.name as customer_name, c.phone, c.email,
                 pt.payment_type, pt.amount as payment_amount
          FROM repair_invoices ri 
          LEFT JOIN vehicles v ON ri.vehicle_id = v.id 
          LEFT JOIN customers c ON v.customer_id = c.id 
          LEFT JOIN payment_transactions pt ON pt.invoice_type = 'repair' AND pt.invoice_id = ri.id
          WHERE ri.id = $invoice_id";
$result = Database::search($query);
$invoice = $result->fetch_assoc();

include 'header.php';
?>

<div class="container content">
    <?php include 'alerts.php'; ?>

    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Edit Repair Invoice #<?php echo $invoice['invoice_number']; ?></h2>
        </div>
        <div class="col-md-6 text-end">
            <button onclick="history.back()" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Invoice
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form id="editInvoiceForm" method="POST" action="update_repair_invoice.php" enctype="multipart/form-data">
                <input type="hidden" name="invoice_id" value="<?php echo $invoice_id; ?>">
                <input type="hidden" name="cart_items" id="cartItems">
                <input type="hidden" name="total_amount" id="finalAmount">

                <!-- Vehicle Selection Section -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="mb-4">
                            <label>Select Vehicle</label>
                            <select class="form-select select2" name="vehicle_id" id="vehicleSelect" required>
                                <?php
                                $query = "SELECT v.id, v.registration_number, v.make, v.model, c.name as customer_name 
                                         FROM vehicles v
                                         JOIN customers c ON v.customer_id = c.id
                                         ORDER BY v.registration_number";
                                $vehicles = Database::search($query);
                                while ($vehicle = $vehicles->fetch_assoc()) {
                                    $selected = ($vehicle['id'] == $invoice['vehicle_id']) ? 'selected' : '';
                                    echo "<option value='" . $vehicle['id'] . "' 
                                            data-make='" . $vehicle['make'] . "' 
                                            data-model='" . $vehicle['model'] . "' 
                                            data-registration_number='" . $vehicle['registration_number'] . "'
                                            data-customer='" . $vehicle['customer_name'] . "' 
                                            $selected>"
                                        . $vehicle['registration_number'] . " - " . $vehicle['make'] . " " . $vehicle['model']
                                        . "</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div id="vehicleDetails" class="mt-4">
                            <?php if ($invoice['vehicle_id']): ?>
                                <div class="card mt-2">
                                    <div class="card-body">
                                        <p><strong>Make:</strong> <?php echo htmlspecialchars($invoice['make']); ?></p>
                                        <p><strong>Model:</strong> <?php echo htmlspecialchars($invoice['model']); ?></p>
                                        <p><strong>Registration Number:</strong> <?php echo htmlspecialchars($invoice['registration_number']); ?></p>
                                        <p><strong>Customer:</strong> <?php echo htmlspecialchars($invoice['customer_name']); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Common Repairs Section -->
                    <div class="col-md-6">
                        <h6>Common Repair Descriptions</h6>
                        <div style="height: 250px; overflow-y: auto; border: 1px solid #dee2e6;">
                            <table class="table table-bordered mb-0">
                                <tbody>
                                    <?php
                                    $common_repairs = [
                                        'Scanning',
                                        'Repairing',
                                        'Replacing'
                                    ];

                                    foreach ($common_repairs as $repair) {
                                        echo "<tr>
                                                <td class='align-middle'>{$repair}</td>
                                                <td width='100' class='text-center'>
                                                    <button type='button' class='btn btn-sm btn-primary copy-btn' data-text='{$repair}'>
                                                        <i class='fas fa-copy'></i> Copy
                                                    </button>
                                                </td>
                                            </tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Repair Items Section -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <h4>Repair Items</h4>
                        <div class="table-responsive">
                            <table class="table" id="repairItems">
                                <thead>
                                    <tr>
                                        <th>Description</th>
                                        <th>Price</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query = "SELECT * FROM repair_invoice_items WHERE repair_invoice_id = $invoice_id";
                                    $items = Database::search($query);
                                    while ($item = $items->fetch_assoc()):
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['description']); ?></td>
                                            <td><?php echo number_format($item['price'], 2); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-danger btn-sm remove-item">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td>
                                            <input type="text" id="newDescription" class="form-control" placeholder="Enter description">
                                        </td>
                                        <td>
                                            <input type="number" id="newPrice" class="form-control" placeholder="Enter price" step="0.01">
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-success" onclick="addToCart()">
                                                <i class="fas fa-plus"></i> Add
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="1" class="text-end"><strong>Total Price:</strong></td>
                                        <td id="totalPrice">0.00</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Photos Section -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label>Repair Photos</label>
                        <input type="file" class="form-control" id="repairPhotos" name="repair_photos[]" multiple accept="image/*">
                        <div id="imagePreview" class="mt-2 d-flex flex-wrap gap-2">
                            <?php
                            $query = "SELECT * FROM repair_photos WHERE repair_invoice_id = $invoice_id";
                            $photos = Database::search($query);
                            while ($photo = $photos->fetch_assoc()):
                            ?>
                                <div class="position-relative">
                                    <img src="<?php echo htmlspecialchars($photo['photo_path']); ?>"
                                        style="width: 150px; height: 150px; object-fit: cover;">
                                    <a href="delete_repair_photo.php?id=<?php echo $photo['id']; ?>"
                                        class="btn btn-danger btn-sm position-absolute"
                                        style="top: 5px; right: 5px"
                                        onclick="return confirm('Delete this photo?')">×</a>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>

                <!-- Form Buttons -->
                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Update Invoice</button>
                        <button type="button" class="btn btn-secondary" onclick="printInvoice()">Print</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Cart Management
    let cart = [];

    $(document).ready(function() {
        // Initialize cart first
        initializeCartFromExistingItems();

        // Copy button functionality for repair descriptions
        $('.copy-btn').click(function() {
            const text = $(this).data('text');
            $('#newDescription').val(text);
            $(this).removeClass('btn-primary').addClass('btn-success')
                .html('<i class="fas fa-check"></i> Copied');

            setTimeout(() => {
                $(this).removeClass('btn-success').addClass('btn-primary')
                    .html('<i class="fas fa-copy"></i> Copy');
            }, 1000);
        });

        // Initialize select2 after cart is ready
        $('.select2').select2({
            theme: 'bootstrap-5'
        });

        // Show initial vehicle details
        showVehicleDetails($('#vehicleSelect option:selected'));
    });

    function initializeCartFromExistingItems() {
        // Start with empty cart
        cart = [];

        // Get all existing rows from the table
        const existingRows = $('#repairItems tbody tr').get();

        // Process each row
        existingRows.forEach(row => {
            const description = $(row).find('td:first').text().trim();
            const priceText = $(row).find('td:eq(1)').text().trim();
            const price = parseFloat(priceText.replace(/,/g, ''));

            if (description && !isNaN(price)) {
                cart.push({
                    description: description,
                    price: price
                });
            }
        });

        // Update the display after initialization
        updateCartDisplay();
    }

    function addToCart() {
        const description = $('#newDescription').val().trim();
        const price = parseFloat($('#newPrice').val()) || 0;

        if (!description || price <= 0) {
            alert('Please enter both description and a valid price');
            return;
        }

        cart.push({
            description: description,
            price: price
        });

        updateCartDisplay();
        clearInputs();
    }

    function updateCartDisplay() {
        const tbody = $('#repairItems tbody');
        tbody.empty();
        let total = 0;

        cart.forEach((item, index) => {
            total += parseFloat(item.price);

            const row = $('<tr>').append(
                $('<td>').text(item.description),
                $('<td>').text(item.price.toFixed(2)),
                $('<td>').append(
                    $('<button>')
                    .addClass('btn btn-danger btn-sm')
                    .attr('type', 'button')
                    .html('<i class="fas fa-trash"></i>')
                    .click(() => removeFromCart(index))
                )
            );

            tbody.append(row);
        });

        // Update all related fields
        $('#totalPrice').text(total.toFixed(2));
        $('#cartItems').val(JSON.stringify(cart));
        $('#finalAmount').val(total.toFixed(2));
        $('#paymentAmount').val(total.toFixed(2));
    }

    function removeFromCart(index) {
        cart.splice(index, 1);
        updateCartDisplay();
    }

    function clearInputs() {
        $('#newDescription').val('');
        $('#newPrice').val('');
    }

    // Vehicle Selection Change Handler
    $('#vehicleSelect').change(function() {
        showVehicleDetails($(this).find('option:selected'));
    });

    // Print Invoice Function
    function printInvoice() {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'print_repair_invoice.php';
        form.target = '_blank';

        const cartInput = document.createElement('input');
        cartInput.type = 'hidden';
        cartInput.name = 'cart_items';
        cartInput.value = JSON.stringify(cart);

        const invoiceInput = document.createElement('input');
        invoiceInput.type = 'hidden';
        invoiceInput.name = 'invoice_id';
        invoiceInput.value = <?php echo $invoice_id; ?>;

        form.appendChild(cartInput);
        form.appendChild(invoiceInput);
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }

    // Form Validation
    document.getElementById('editInvoiceForm').onsubmit = function(e) {
        if (cart.length === 0) {
            e.preventDefault();
            alert('Please add at least one repair item');
            return false;
        }
        return true;
    };

    // Photo Preview Handler
    document.getElementById('repairPhotos').addEventListener('change', function(e) {
        const preview = document.getElementById('imagePreview');
        const newPhotosDiv = document.createElement('div');
        newPhotosDiv.className = 'd-flex flex-wrap gap-2';

        for (let file of this.files) {
            const reader = new FileReader();
            reader.onload = function(event) {
                const div = document.createElement('div');
                div.style.position = 'relative';
                div.innerHTML = `
                <img src="${event.target.result}" style="width: 150px; height: 150px; object-fit: cover; margin: 5px;">
                <button type="button" class="btn btn-danger btn-sm position-absolute" style="top: 5px; right: 5px;" 
                        onclick="this.parentElement.remove()">×</button>
            `;
                newPhotosDiv.appendChild(div);
            }
            reader.readAsDataURL(file);
        }

        // Add new photos preview after existing photos
        preview.appendChild(newPhotosDiv);
    });

    $(document).ready(function() {
        $('.copy-btn').click(function() {
            const text = $(this).data('text');
            $('#newDescription').val(text);
            $(this).removeClass('btn-primary').addClass('btn-success')
                .html('<i class="fas fa-check"></i> Copied');

            // Reset button after 1 second
            setTimeout(() => {
                $(this).removeClass('btn-success').addClass('btn-primary')
                    .html('<i class="fas fa-copy"></i> Copy');
            }, 1000);
        });
    });
</script>

<?php include 'footer.php'; ?>
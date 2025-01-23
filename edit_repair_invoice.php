<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();

$invoice_id = (int)$_GET['id'];

$query = "SELECT ri.*, v.registration_number, v.make, v.model, v.year, 
                 c.name as customer_name, c.phone, c.email 
          FROM repair_invoices ri 
          LEFT JOIN vehicles v ON ri.vehicle_id = v.id 
          LEFT JOIN customers c ON v.customer_id = c.id 
          WHERE ri.id = $invoice_id";
$result = Database::search($query);
$invoice = $result->fetch_assoc();

$total_amount = $invoice['total_amount'];

include 'header.php';
?>

<div class="container content">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Edit Invoice</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="invoices.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Invoices
            </a>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <h4>Edit Repair Invoice #<?php echo $invoice['invoice_number']; ?></h4>

            <form id="editInvoiceForm" method="POST" action="update_repair_invoice.php" enctype="multipart/form-data">
                <input type="hidden" name="invoice_id" value="<?php echo $invoice_id; ?>">

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label>Vehicle</label>
                        <select class="form-select select2" name="vehicle_id" required>
                            <?php
                            $query = "SELECT v.id, v.registration_number, v.make, v.model 
                                     FROM vehicles v
                                     JOIN customers c ON v.customer_id = c.id
                                     ORDER BY v.registration_number";
                            $vehicles = Database::search($query);
                            while ($vehicle = $vehicles->fetch_assoc()) {
                                $selected = ($vehicle['id'] == $invoice['vehicle_id']) ? 'selected' : '';
                                echo "<option value='" . $vehicle['id'] . "' $selected>"
                                    . $vehicle['registration_number'] . " - " . $vehicle['make'] . " " . $vehicle['model']
                                    . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <h5>Repair Items</h5>
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
                                            <td>
                                                <input type="text" class="form-control description"
                                                    name="items[description][]" value="<?php echo $item['description']; ?>" required>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control price"
                                                    name="items[price][]" value="<?php echo $item['price']; ?>" required>
                                            </td>
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
                                        <td colspan="3">
                                            <button type="button" class="btn btn-success btn-sm" onclick="addNewRow()">
                                                <i class="fas fa-plus"></i> Add Item
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="1" class="text-end"><strong>Total:</strong></td>
                                        <td id="totalAmount">0.00</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label>Repair Photos</label>
                        <input type="file" class="form-control" name="repair_photos[]" multiple accept="image/*">
                        <div id="imagePreview" class="d-flex flex-wrap gap-2 mt-2">
                            <?php
                            $query = "SELECT * FROM repair_photos WHERE repair_invoice_id = $invoice_id";
                            $photos = Database::search($query);
                            while ($photo = $photos->fetch_assoc()):
                            ?>
                                <div class="position-relative">
                                    <img src="<?php echo $photo['photo_path']; ?>"
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

                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Update Invoice</button>
                        <a href="view_repair_invoice.php?id=<?php echo $invoice_id; ?>"
                            class="btn btn-secondary">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function addNewRow() {
        const tbody = document.querySelector('#repairItems tbody');
        const tr = document.createElement('tr');
        tr.innerHTML = `
        <td>
            <input type="text" class="form-control description" 
                   name="items[description][]" required>
        </td>
        <td>
            <input type="number" class="form-control price" 
                   name="items[price][]" required>
        </td>
        <td>
            <button type="button" class="btn btn-danger btn-sm remove-item">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
        tbody.appendChild(tr);
        initializeRow(tr);
    }

    function calculateTotal() {
    let total = 0;
    document.querySelectorAll('.price').forEach(input => {
        const value = parseFloat(input.value) || 0;
        total += value;
    });
    document.getElementById('totalAmount').textContent = total.toFixed(2);
}

    function initializeRow(row) {
        row.querySelector('.price').addEventListener('input', calculateTotal);
        row.querySelector('.remove-item').addEventListener('click', function() {
            if (document.querySelectorAll('#repairItems tbody tr').length > 1) {
                row.remove();
                calculateTotal();
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('#repairItems tbody tr').forEach(initializeRow);
        calculateTotal();
    });

    // Photo preview
    document.querySelector('input[name="repair_photos[]"]').addEventListener('change', function(e) {
        const preview = document.getElementById('imagePreview');

        for (let file of this.files) {
            const reader = new FileReader();
            reader.onload = function(event) {
                const div = document.createElement('div');
                div.className = 'position-relative';
                div.innerHTML = `
                <img src="${event.target.result}" 
                     style="width: 150px; height: 150px; object-fit: cover;">
                <button type="button" class="btn btn-danger btn-sm position-absolute" 
                        style="top: 5px; right: 5px" 
                        onclick="this.parentElement.remove()">×</button>
            `;
                preview.appendChild(div);
            }
            reader.readAsDataURL(file);
        }
    });
</script>

<?php include 'footer.php'; ?>
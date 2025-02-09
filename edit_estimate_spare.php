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

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicle_id = $_POST['vehicle_id'];
    $estimate_date = $_POST['estimate_date'];
    $notes = $_POST['notes'];
    $estimate_items = json_decode($_POST['estimate_items'], true);
    $total_amount = $_POST['total_amount'];

    // Update estimate
    $update_query = "UPDATE estimates_spareparts SET 
        vehicle_id = '" . $vehicle_id . "',
        estimate_date = '" . $estimate_date . "',
        total_amount = '" . $total_amount . "',
        notes = '" . $notes . "'
        WHERE id = '" . $id . "'";

    Database::iud($update_query);

    // Delete existing items
    Database::iud("DELETE FROM estimate_items_spareparts WHERE estimate_id = '" . $id . "'");

    // Insert new items
    foreach ($estimate_items as $item) {
        $price = isset($item['price']) && $item['price'] !== '' ? $item['price'] : 'NULL';
        $item_query = "INSERT INTO estimate_items_spareparts (estimate_id, description, price) 
                  VALUES ('" . $id . "', '" . $item['description'] . "', " . $price . ")";
        Database::iud($item_query);
    }

    header("Location: spare_parts_estimates.php?success=Estimate updated successfully");
    exit();
}

// Fetch estimate data
$estimate_result = Database::search("SELECT e.*, v.registration_number, c.name as customer_name,
                                          v.make, v.model 
                                   FROM estimates_spareparts e 
                                   LEFT JOIN vehicles v ON e.vehicle_id = v.id 
                                   LEFT JOIN customers c ON v.customer_id = c.id 
                                   WHERE e.id = '" . $id . "'");
$estimate = $estimate_result->fetch_assoc();

// Fetch estimate items
$items_result = Database::search("SELECT * FROM estimate_items_spareparts WHERE estimate_id = '" . $id . "'");

include 'header.php';
?>

<div class="container content">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Edit Estimate #<?php echo htmlspecialchars($estimate['estimate_number']); ?></h2>
        </div>
        <div class="col-md-6 text-end">
            <button onclick="history.back()" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Estimates
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form id="estimateForm" method="POST">
                <div class="row mb-3">
                    <!-- Main container with two columns -->
                    <div class="row">
                        <!-- Left Column: Vehicle Selection and Details -->
                        <div class="col-md-6">
                            <!-- Vehicle Selection -->
                            <div class="mb-4">
                                <label>Select Vehicle</label>
                                <select class="form-select select2" id="vehicleSelect" name="vehicle_id" required>
                                    <option value="">Search vehicle...</option>
                                    <?php
                                    $vehicles_query = "SELECT v.id, v.registration_number, v.make, v.model, c.name as customer_name 
                                             FROM vehicles v 
                                             JOIN customers c ON v.customer_id = c.id 
                                             ORDER BY v.registration_number";
                                    $vehicles_result = Database::search($vehicles_query);
                                    while ($vehicle = $vehicles_result->fetch_assoc()) {
                                        $selected = ($vehicle['id'] == $estimate['vehicle_id']) ? 'selected' : '';
                                        echo "<option value='" . $vehicle['id'] . "' 
                                            data-make='" . $vehicle['make'] . "' 
                                            data-model='" . $vehicle['model'] . "' 
                                            data-registration_number='" . $vehicle['registration_number'] . "' 
                                            data-customer='" . $vehicle['customer_name'] . "' 
                                            {$selected}>"
                                            . $vehicle['registration_number'] . " - " . $vehicle['make'] . " " . $vehicle['model']
                                            . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <!-- Vehicle Details -->
                            <div id="vehicleDetails">
                                <?php if ($estimate['vehicle_id']): ?>
                                    <div class="card mt-2">
                                        <div class="card-body">
                                            <p><strong>Make:</strong> <?php echo htmlspecialchars($estimate['make']); ?></p>
                                            <p><strong>Model:</strong> <?php echo htmlspecialchars($estimate['model']); ?></p>
                                            <p><strong>Registration Number:</strong> <?php echo htmlspecialchars($estimate['registration_number']); ?></p>
                                            <p><strong>Customer:</strong> <?php echo htmlspecialchars($estimate['customer_name']); ?></p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Right Column: Common Repair Descriptions -->
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
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <h4>Estimate Items</h4>
                        <div class="table-responsive">
                            <table class="table" id="itemsTable">
                                <thead>
                                    <tr>
                                        <th>Description</th>
                                        <th>Price</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
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
                                            <button type="button" class="btn btn-success" onclick="addToEstimate()">
                                                <i class="fas fa-plus"></i> Add
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="1" class="text-end"><strong>Total Estimate:</strong></td>
                                        <td id="totalEstimate">0.00</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label>Notes</label>
                        <textarea class="form-control" name="notes" rows="3"
                            placeholder="Enter any additional notes"><?php echo htmlspecialchars($estimate['notes']); ?></textarea>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label>Estimate Date</label>
                        <input type="date" class="form-control" name="estimate_date" required
                            value="<?php echo $estimate['estimate_date']; ?>">
                    </div>
                </div>

                <input type="hidden" name="estimate_items" id="estimateItems">
                <input type="hidden" name="total_amount" id="finalAmount">

                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Update Estimate</button>
                        <button type="button" class="btn btn-secondary" onclick="printEstimate()">Print</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let estimateItems = [];

    $(document).ready(function() {
        // Load initial items
        <?php
        mysqli_data_seek($items_result, 0);
        while ($item = $items_result->fetch_assoc()) {
        ?>
            estimateItems.push({
                description: <?php echo json_encode($item['description']); ?>,
                price: <?php echo floatval($item['price']); ?>
            });
        <?php
        }
        ?>

        // Initialize display and components
        updateEstimateDisplay();
        initializeComponents();

        $('.select2').select2({
            theme: 'bootstrap-5'
        });
        // Show initial vehicle details
        showVehicleDetails($('#vehicleSelect option:selected'));

        // Copy button functionality
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
    });

    function showVehicleDetails(selectedOption) {
        if (selectedOption.val()) {
            document.getElementById('vehicleDetails').innerHTML = `
                <div class="card mt-2">
                    <div class="card-body">
                        <p><strong>Make:</strong> ${selectedOption.data('make')}</p>
                        <p><strong>Model:</strong> ${selectedOption.data('model')}</p>
                        <p><strong>Registration Number:</strong> ${selectedOption.data('registration_number')}</p>
                        <p><strong>Customer:</strong> ${selectedOption.data('customer')}</p>
                    </div>
                </div>`;
        }
    }

    $('#vehicleSelect').change(function() {
        showVehicleDetails($(this).find('option:selected'));
    });

    function updateVehicleDetails(selectedOption) {
        const vehicleDetails = `
        <div class="card mt-2">
            <div class="card-body">
                <p><strong>Make:</strong> ${selectedOption.data('make')}</p>
                <p><strong>Model:</strong> ${selectedOption.data('model')}</p>
                <p><strong>Registration Number:</strong> ${selectedOption.data('registration_number')}</p>
                <p><strong>Customer:</strong> ${selectedOption.data('customer')}</p>
            </div>
        </div>`;
        $('#vehicleDetails').html(vehicleDetails);
    }

    function addToEstimate() {
        const description = $('#newDescription').val().trim();
        const priceInput = $('#newPrice').val().trim();
        const price = parseFloat(priceInput);

        if (!description) {
            alert('Please enter the description');
            return;
        }

        estimateItems.push({
            description: description,
            price: price
        });

        updateEstimateDisplay();
        clearInputs();
    }

    function removeFromEstimate(index) {
        estimateItems.splice(index, 1);
        updateEstimateDisplay();
    }

    function updateEstimateDisplay() {
        const tbody = $('#itemsTable tbody');
        tbody.empty();

        let total = 0;
        estimateItems.forEach((item, index) => {
            if (item.price) {
                total += parseFloat(item.price);
            }
            tbody.append(`
            <tr>
                <td>${item.description}</td>
                <td>${item.price ? item.price.toFixed(2) : '--'}</td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeFromEstimate(${index})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `);
        });

        $('#totalEstimate').text(total > 0 ? total.toFixed(2) : '--');
        $('#estimateItems').val(JSON.stringify(estimateItems));
        $('#finalAmount').val(total);
    }

    function clearInputs() {
        $('#newDescription').val('');
        $('#newPrice').val('');
        $('#newDescription').focus();
    }

    function printEstimate() {
        if (!$('#vehicleSelect').val()) {
            alert('Please select a vehicle first');
            return;
        }

        const form = $('<form>', {
            method: 'POST',
            action: 'print_repair_estimate_spare.php?id=<?php echo $id; ?>',
            target: '_blank'
        });

        form.append($('<input>', {
            type: 'hidden',
            name: 'estimate_items',
            value: JSON.stringify(estimateItems)
        }));

        form.append($('<input>', {
            type: 'hidden',
            name: 'vehicle_id',
            value: $('#vehicleSelect').val()
        }));

        form.appendTo('body').submit().remove();
    }

    $('#estimateForm').on('submit', function(e) {
        if (estimateItems.length === 0) {
            e.preventDefault();
            alert('Please add at least one estimate item');
            return false;
        }
        return true;
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
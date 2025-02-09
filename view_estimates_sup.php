<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();
include 'header.php';
?>

<div class="container content">
    <?php include 'alerts.php'; ?>

    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Create Supplimentary Repair Estimates</h2>
        </div>
        <div class="col-md-6 text-end">
            <button onclick="history.back()" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Estimates
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form id="estimateForm" method="POST" action="process_repair_estimate_sup.php">
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
                                    $query = "SELECT v.id, v.registration_number, v.make, v.model, c.name as customer_name 
                        FROM vehicles v 
                        JOIN customers c ON v.customer_id = c.id 
                        ORDER BY v.registration_number";
                                    $result = Database::search($query);
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<option value='" . $row['id'] . "' 
                        data-make='" . $row['make'] . "' 
                        data-model='" . $row['model'] . "' 
                        data-registration_number='" . $row['registration_number'] . "' 
                        data-customer='" . $row['customer_name'] . "'>"
                                            . $row['registration_number'] . " - " . $row['make'] . " " . $row['model']
                                            . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <!-- Vehicle Details -->
                            <div id="vehicleDetails" class="mt-4"></div>
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

                <!-- Repair Items -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <h4>Estimated Repairs</h4>
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
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td>
                                            <input type="text" id="newDescription" class="form-control" placeholder="Enter description">
                                        </td>
                                        <td>
                                            <input type="number" id="newPrice" class="form-control" placeholder="Enter price">
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

                <!-- Notes -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label>Notes</label>
                        <textarea class="form-control" name="notes" rows="3"
                            placeholder="Enter any additional notes"></textarea>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label>Estimate Date</label>
                        <input type="date" class="form-control" name="estimate_date" required
                            value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>

                <input type="hidden" name="estimate_items" id="estimateItems">
                <input type="hidden" name="total_amount" id="finalAmount">

                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Save Estimate</button>
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
        $('.select2').select2({
            theme: 'bootstrap-5'
        });
    });

    $('#vehicleSelect').change(function() {
        const selectedOption = $(this).find('option:selected');
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
    });

    function addToEstimate() {
        const description = document.getElementById('newDescription').value;
        const price = parseFloat(document.getElementById('newPrice').value);

        if (!description || !price) {
            alert('Please enter both description and price');
            return;
        }

        estimateItems.push({
            description,
            price
        });
        updateEstimateDisplay();
        clearInputs();
    }

    function removeFromEstimate(index) {
        estimateItems.splice(index, 1);
        updateEstimateDisplay();
    }

    function updateEstimateDisplay() {
        const tbody = document.querySelector('#repairItems tbody');
        tbody.innerHTML = '';
        let total = 0;

        estimateItems.forEach((item, index) => {
            total += item.price;
            tbody.innerHTML += `
            <tr>
                <td>${item.description}</td>
                <td>${item.price.toFixed(2)}</td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeFromEstimate(${index})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>`;
        });

        document.getElementById('totalEstimate').textContent = total.toFixed(2);
        document.getElementById('estimateItems').value = JSON.stringify(estimateItems);
        document.getElementById('finalAmount').value = total;
    }

    function clearInputs() {
        document.getElementById('newDescription').value = '';
        document.getElementById('newPrice').value = '';
    }

    function printEstimate() {
        if (!document.getElementById('vehicleSelect').value) {
            alert('Please select a vehicle first');
            return;
        }
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'print_repair_estimate.php';
        form.target = '_blank';

        const itemsInput = document.createElement('input');
        itemsInput.type = 'hidden';
        itemsInput.name = 'estimate_items';
        itemsInput.value = JSON.stringify(estimateItems);

        const vehicleInput = document.createElement('input');
        vehicleInput.type = 'hidden';
        vehicleInput.name = 'vehicle_id';
        vehicleInput.value = document.getElementById('vehicleSelect').value;

        form.appendChild(itemsInput);
        form.appendChild(vehicleInput);
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }

    document.getElementById('estimateForm').onsubmit = function(e) {
        if (estimateItems.length === 0) {
            e.preventDefault();
            alert('Please add at least one repair item');
            return false;
        }
        return true;
    };

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
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
    $update_query = "UPDATE estimates_supplimentary SET 
        vehicle_id = '" . $vehicle_id . "',
        estimate_date = '" . $estimate_date . "',
        total_amount = '" . $total_amount . "',
        notes = '" . $notes . "'
        WHERE id = '" . $id . "'";

    Database::iud($update_query);

    // Delete existing items
    Database::iud("DELETE FROM estimate_items_supplimentary WHERE estimate_id = '" . $id . "'");

    // Insert new items
    foreach ($estimate_items as $item) {
        $item_query = "INSERT INTO estimate_items_supplimentary (estimate_id, description, category, price) 
                      VALUES ('" . $id . "', '" . $item['description'] . "', '" . $item['category'] . "', '" . $item['price'] . "')";
        Database::iud($item_query);
    }

    header("Location: supplementary_estimates.php?success=Estimate updated successfully");
    exit();
}

// Fetch estimate data
$estimate_result = Database::search("SELECT e.*, v.registration_number, c.name as customer_name,
                                          v.make, v.model 
                                   FROM estimates_supplimentary e 
                                   LEFT JOIN vehicles v ON e.vehicle_id = v.id 
                                   LEFT JOIN customers c ON v.customer_id = c.id 
                                   WHERE e.id = '" . $id . "'");
$estimate = $estimate_result->fetch_assoc();

// Fetch estimate items
$items_result = Database::search("SELECT * FROM estimate_items_supplimentary WHERE estimate_id = '" . $id . "'");

include 'header.php';
?>

<div class="container content">
    <?php include 'alerts.php'; ?>
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Edit Supplementary Estimate #<?php echo htmlspecialchars($estimate['estimate_number']); ?></h2>
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
                            <div id="vehicleDetails" class="mt-4">
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
                                            'Removing and Refitting' => [
                                                'Scanning',
                                                'Dismantling',
                                                'Assembly',
                                                'Refitting'
                                            ],
                                            'Repairing' => [
                                                'Panel Repair',
                                                'Dent Repair',
                                                'Chassis Repair',
                                                'Buffer Repair'
                                            ],
                                            'Replacing' => [
                                                'Panel Replacement',
                                                'Part Replacement',
                                                'Component Change',
                                                'Unit Replacement'
                                            ],
                                            'Repainting' => [
                                                'Full Body Painting',
                                                'Spot Painting',
                                                'Color Matching',
                                                'Clear Coating'
                                            ],
                                            'Spare Parts' => [
                                                'Buffer',
                                                'Windscreen',
                                                'Lights',
                                                'Panels'
                                            ]
                                        ];

                                        foreach ($common_repairs as $category => $repairs) {
                                            echo "<tr><td colspan='2' class='bg-light'><strong>$category</strong></td></tr>";
                                            foreach ($repairs as $repair) {
                                                echo "<tr>
                                                    <td class='align-middle'>{$repair}</td>
                                                    <td width='100' class='text-center'>
                                                        <button type='button' class='btn btn-sm btn-primary copy-btn' data-text='{$repair}' data-category='" . strtolower(explode(' ', $category)[0]) . "'>
                                                            <i class='fas fa-copy'></i> Copy
                                                        </button>
                                                    </td>
                                                </tr>";
                                            }
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
                                        <th>Category</th>
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
                                            <select id="newCategory" class="form-control">
                                                <option value="removing">Removing and Refitting</option>
                                                <option value="repairing">Repairing</option>
                                                <option value="replacing">Replacing</option>
                                                <option value="repainting">Repainting</option>
                                                <option value="spares">Spare Parts</option>
                                            </select>
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
                                        <td colspan="2" class="text-end"><strong>Total Estimate:</strong></td>
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
            // If the category isn't set in the database, default to 'removing'
            $category = isset($item['category']) ? $item['category'] : 'removing';
        ?>
            estimateItems.push({
                description: <?php echo json_encode($item['description']); ?>,
                category: <?php echo json_encode($category); ?>,
                price: <?php echo floatval($item['price']); ?>
            });
        <?php
        }
        ?>

        // Initialize display and select2
        updateEstimateDisplay();
        
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
        const category = document.getElementById('newCategory').value;
        const price = parseFloat(document.getElementById('newPrice').value);

        if (!description || price === null || price === undefined || price === '' || isNaN(price)) {
            alert('Please enter both description and price');
            return;
        }

        estimateItems.push({
            description,
            category,
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

        // Sort items by category
        const sortedItems = [...estimateItems].sort((a, b) => {
            const categoryOrder = {
                'removing': 1,
                'repairing': 2,
                'replacing': 3,
                'repainting': 4,
                'spares': 5
            };
            return categoryOrder[a.category] - categoryOrder[b.category];
        });

        // Group items by category
        const groupedItems = {};
        sortedItems.forEach(item => {
            if (!groupedItems[item.category]) {
                groupedItems[item.category] = [];
            }
            groupedItems[item.category].push(item);
        });

        // Display grouped items
        for (const category in groupedItems) {
            const items = groupedItems[category];
            let categoryTotal = 0;

            // Category header
            const categoryName = {
                'removing': 'Removing and Refitting',
                'repairing': 'Repairing',
                'replacing': 'Replacing',
                'repainting': 'Repainting',
                'spares': 'Spare Parts'
            } [category];

            tbody.innerHTML += `
                <tr class="bg-light">
                    <td colspan="4"><strong>${categoryName}</strong></td>
                </tr>
            `;

            // Category items
            items.forEach((item, idx) => {
                const originalIndex = estimateItems.findIndex(i => 
                    i.description === item.description && 
                    i.category === item.category && 
                    i.price === item.price
                );
                
                categoryTotal += item.price;
                total += item.price;
                tbody.innerHTML += `
                <tr>
                    <td>${item.description}</td>
                    <td>${categoryName}</td>
                    <td>${item.price == 0 ? '--' : item.price.toFixed(2)}</td>
                    <td>
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeFromEstimate(${originalIndex})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>`;
            });

            // Category subtotal
            tbody.innerHTML += `
                <tr>
                    <td colspan="2" class="text-end"><em>${categoryName} Total:</em></td>
                    <td colspan="2">${categoryTotal == 0 ? '--' : categoryTotal.toFixed(2)}</td>
                </tr>
            `;
        }

        document.getElementById('totalEstimate').textContent = total === 0 ? '--' : total.toFixed(2);
        document.getElementById('estimateItems').value = JSON.stringify(estimateItems);
        document.getElementById('finalAmount').value = total;
    }

    function clearInputs() {
        document.getElementById('newDescription').value = '';
        document.getElementById('newPrice').value = '';
    }

    $(document).ready(function() {
        $('.copy-btn').click(function() {
            const text = $(this).data('text');
            const category = $(this).data('category');
            $('#newDescription').val(text);
            $('#newCategory').val(category);
            $(this).removeClass('btn-primary').addClass('btn-success')
                .html('<i class="fas fa-check"></i> Copied');

            setTimeout(() => {
                $(this).removeClass('btn-success').addClass('btn-primary')
                    .html('<i class="fas fa-copy"></i> Copy');
            }, 1000);
        });
    });

    function printEstimate() {
        if (!document.getElementById('vehicleSelect').value) {
            alert('Please select a vehicle first');
            return;
        }
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'print_repair_estimate_sup.php?id=<?php echo $id; ?>';
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
</script>

<?php include 'footer.php'; ?>
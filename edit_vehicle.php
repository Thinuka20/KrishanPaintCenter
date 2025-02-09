<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();

$vehicle_id = (int)$_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $registration_number = strtoupper(validateInput($_POST['registration_number']));
    $make = validateInput($_POST['make']);
    $model = validateInput($_POST['model']);
    $year = validateInput($_POST['year']);

    try {
        // Check if registration number exists (excluding current vehicle)
        $query = "SELECT id FROM vehicles WHERE registration_number = '$registration_number' AND id != $vehicle_id";
        $result = Database::search($query);
        if ($result->num_rows > 0) {
            throw new Exception("A vehicle with this registration number already exists.");
        }

        $query = "UPDATE vehicles 
                  SET registration_number = '$registration_number',
                      make = '$make',
                      model = '$model',
                      year = '$year'
                  WHERE id = $vehicle_id";
        Database::iud($query);

        $_SESSION['success'] = "Vehicle updated successfully.";
        header("Location: view_customer.php?id=" . $vehicle['customer_id']);
        exit();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get vehicle details
$query = "SELECT v.*, c.name as customer_name 
          FROM vehicles v 
          LEFT JOIN customers c ON v.customer_id = c.id 
          WHERE v.id = $vehicle_id";
$result = Database::search($query);
$vehicle = $result->fetch_assoc();

include 'header.php';
?>

<div class="container content">
    <div class="row align-items-center">
        <div class="col">
            <h3>Edit Vehicle</h3>
            <p class="mb-0">Owner: <?php echo $vehicle['customer_name']; ?></p>
        </div>
        <div class="col text-end">
            <button onclick="history.back()" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Customer
            </button>
        </div>
    </div>
    <div class="row">
        <div class="card">
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" onsubmit="return validateForm('edit-vehicle-form')" id="edit-vehicle-form">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="required">Registration Number</label>
                                <input type="text" name="registration_number" class="form-control text-uppercase"
                                    required maxlength="15" value="<?php echo $vehicle['registration_number']; ?>">
                            </div>

                            <div class="form-group">
                                <label class="required">Make</label>
                                <input type="text" name="make" class="form-control"
                                    required value="<?php echo $vehicle['make']; ?>">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="required">Model</label>
                                <input type="text" name="model" class="form-control"
                                    required value="<?php echo $vehicle['model']; ?>">
                            </div>

                            <div class="form-group">
                                <label class="required">Year</label>
                                <select name="year" class="form-control" required>
                                    <option value="">Select Year</option>
                                    <?php
                                    $current_year = date('Y');
                                    for ($year = $current_year; $year >= 1990; $year--):
                                    ?>
                                        <option value="<?php echo $year; ?>"
                                            <?php echo $vehicle['year'] == $year ? 'selected' : ''; ?>>
                                            <?php echo $year; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Vehicle Summary -->
                    <?php
                    $query = "SELECT COUNT(*) as repair_count, 
                                        MAX(invoice_date) as last_repair,
                                        SUM(total_amount) as total_spent
                                 FROM repair_invoices 
                                 WHERE vehicle_id = $vehicle_id";
                    $result = Database::search($query);
                    $summary = $result->fetch_assoc();
                    ?>
                    <div class="card mt-4">
                        <div class="card-body">
                            <h5>Vehicle Summary</h5>
                            <div class="row">
                                <div class="col-md-4">
                                    <p class="mb-1"><strong>Total Repairs:</strong> <?php echo $summary['repair_count']; ?></p>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-1"><strong>Total Spent:</strong> <?php echo formatCurrency($summary['total_spent']); ?></p>
                                </div>
                                <div class="col-md-4">
                                    <p class="mb-1">
                                        <strong>Last Repair:</strong>
                                        <?php echo $summary['last_repair'] ? date('Y-m-d', strtotime($summary['last_repair'])) : 'Never'; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary">Update Vehicle</button>
                        <a href="view_customer.php?id=<?php echo $vehicle['customer_id']; ?>" class="btn btn-secondary">Cancel</a>

                        <!-- Quick Actions -->
                        <div class="float-end">
                            <a href="add_repair_invoice.php?vehicle_id=<?php echo $vehicle_id; ?>" class="btn btn-success">
                                <i class="fas fa-tools"></i> New Repair
                            </a>
                            <a href="vehicle_history.php?id=<?php echo $vehicle_id; ?>" class="btn btn-info">
                                <i class="fas fa-history"></i> View History
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Registration number formatting
        document.querySelector('input[name="registration_number"]').addEventListener('input', function(e) {
            this.value = this.value.toUpperCase();
            this.value = this.value.replace(/[^A-Z0-9-]/g, '');
        });

        // Form validation
        document.getElementById('edit-vehicle-form').addEventListener('submit', function(e) {
            const regNumber = document.querySelector('input[name="registration_number"]').value;
            if (!/^[A-Z0-9-]+$/.test(regNumber)) {
                e.preventDefault();
                alert('Registration number can only contain letters, numbers, and hyphens.');
                return false;
            }
            return true;
        });
    });
</script>

<?php include 'footer.php'; ?>
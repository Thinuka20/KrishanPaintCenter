<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();

$customer_id = (int)$_GET['customer_id'];

// Get customer details
$query = "SELECT * FROM customers WHERE id = $customer_id";
$result = Database::search($query);
$customer = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $registration_number = strtoupper(validateInput($_POST['registration_number']));
    $make = validateInput($_POST['make']);
    $model = validateInput($_POST['model']);
    $year = validateInput($_POST['year']);
    
    try {
        // Check if registration number exists
        $query = "SELECT id FROM vehicles WHERE registration_number = '$registration_number'";
        $result = Database::search($query);
        if ($result->num_rows > 0) {
            throw new Exception("A vehicle with this registration number already exists.");
        }
        
        $query = "INSERT INTO vehicles (customer_id, registration_number, make, model, year) 
                  VALUES ($customer_id, '$registration_number', '$make', '$model', '$year')";
        Database::iud($query);
        
        $_SESSION['success'] = "Vehicle added successfully.";
        header("Location: view_customer.php?id=$customer_id");
        exit();
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

include 'header.php';
?>

<div class="container content">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h3>Add New Vehicle</h3>
                            <p class="mb-0">Owner: <?php echo $customer['name']; ?></p>
                        </div>
                        <div class="col text-end">
                            <a href="view_customer.php?id=<?php echo $customer_id; ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Customer
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>

                    <form method="POST" onsubmit="return validateForm('add-vehicle-form')" id="add-vehicle-form">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">Registration Number</label>
                                    <input type="text" name="registration_number" class="form-control text-uppercase" 
                                           required maxlength="15" 
                                           value="<?php echo isset($_POST['registration_number']) ? $_POST['registration_number'] : ''; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="required">Make</label>
                                    <input type="text" name="make" class="form-control" required 
                                           value="<?php echo isset($_POST['make']) ? $_POST['make'] : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">Model</label>
                                    <input type="text" name="model" class="form-control" required 
                                           value="<?php echo isset($_POST['model']) ? $_POST['model'] : ''; ?>">
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
                                                <?php echo isset($_POST['year']) && $_POST['year'] == $year ? 'selected' : ''; ?>>
                                            <?php echo $year; ?>
                                        </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary">Save Vehicle</button>
                            <a href="view_customer.php?id=<?php echo $customer_id; ?>" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
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
    document.getElementById('add-vehicle-form').addEventListener('submit', function(e) {
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
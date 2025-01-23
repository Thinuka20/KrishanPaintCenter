<?php
// add_customer.php - Add new customer
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = validateInput($_POST['name']);
    $phone = validateInput($_POST['phone']);
    $email = validateInput($_POST['email']);
    $address = validateInput($_POST['address']);
    
    // Validate phone number
    if (!empty($phone)) {
        // Remove any non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
    }
    
    // Validate email
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        try {
            // Check if phone number already exists
            if (!empty($phone)) {
                $query = "SELECT id FROM customers WHERE phone = '$phone'";
                $result = Database::search($query);
                if ($result->num_rows > 0) {
                    throw new Exception("A customer with this phone number already exists.");
                }
            }

            // Insert customer
            $query = "INSERT INTO customers (name, phone, email, address) 
                      VALUES ('$name', '$phone', '$email', '$address')";
            Database::iud($query);
            $customer_id = Database::$connection->insert_id;

            // If vehicle details are provided
            if (!empty($_POST['registration_number'])) {
                $registration_number = validateInput($_POST['registration_number']);
                $make = validateInput($_POST['make']);
                $model = validateInput($_POST['model']);
                $year = validateInput($_POST['year']);

                // Check if vehicle already exists
                $query = "SELECT id FROM vehicles WHERE registration_number = '$registration_number'";
                $result = Database::search($query);
                if ($result->num_rows > 0) {
                    throw new Exception("A vehicle with this registration number already exists.");
                }

                // Insert vehicle
                $query = "INSERT INTO vehicles (customer_id, registration_number, make, model, year) 
                          VALUES ($customer_id, '$registration_number', '$make', '$model', '$year')";
                Database::iud($query);
            }

            $_SESSION['success'] = "Customer added successfully.";
            header("Location: customers.php");
            exit();

        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

include 'header.php';
?>

<div class="container content">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h3>Add New Customer</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" onsubmit="return validateForm('add-customer-form')" id="add-customer-form">
                        <!-- Customer Information -->
                        <h4 class="mb-3">Customer Information</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">Name</label>
                                    <input type="text" name="name" class="form-control" required 
                                           value="<?php echo isset($_POST['name']) ? $_POST['name'] : ''; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="required">Phone</label>
                                    <input type="tel" name="phone" class="form-control phone-input" required 
                                           value="<?php echo isset($_POST['phone']) ? $_POST['phone'] : ''; ?>"
                                           pattern="[0-9]{10}">
                                    <small class="text-muted">Format: 0777123456</small>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="email" class="form-control" 
                                           value="<?php echo isset($_POST['email']) ? $_POST['email'] : ''; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>Address</label>
                                    <textarea name="address" class="form-control" rows="3"><?php echo isset($_POST['address']) ? $_POST['address'] : ''; ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Vehicle Information (Optional) -->
                        <h4 class="mb-3 mt-4">Vehicle Information (Optional)</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Registration Number</label>
                                    <input type="text" name="registration_number" class="form-control text-uppercase" 
                                           value="<?php echo isset($_POST['registration_number']) ? $_POST['registration_number'] : ''; ?>"
                                           maxlength="15">
                                </div>
                                
                                <div class="form-group">
                                    <label>Make</label>
                                    <input type="text" name="make" class="form-control" 
                                           value="<?php echo isset($_POST['make']) ? $_POST['make'] : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Model</label>
                                    <input type="text" name="model" class="form-control" 
                                           value="<?php echo isset($_POST['model']) ? $_POST['model'] : ''; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label>Year</label>
                                    <select name="year" class="form-control">
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
                            <button type="submit" class="btn btn-primary">Save Customer</button>
                            <a href="customers.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Phone number formatting
    const phoneInput = document.querySelector('.phone-input');
    phoneInput.addEventListener('input', function(e) {
        let numbers = this.value.replace(/\D/g, '');
        if (numbers.length > 10) {
            numbers = numbers.substr(0, 10);
        }
        this.value = numbers;
    });

    // Registration number formatting
    const regInput = document.querySelector('input[name="registration_number"]');
    regInput.addEventListener('input', function(e) {
        this.value = this.value.toUpperCase();
    });

    // Form validation
    document.getElementById('add-customer-form').addEventListener('submit', function(e) {
        const phone = phoneInput.value;
        if (phone.length !== 10) {
            e.preventDefault();
            alert('Phone number must be exactly 10 digits.');
            return false;
        }

        const regNumber = regInput.value;
        if (regNumber && !/^[A-Z0-9-]+$/.test(regNumber)) {
            e.preventDefault();
            alert('Registration number can only contain letters, numbers, and hyphens.');
            return false;
        }

        return true;
    });
});
</script>

<?php include 'footer.php'; ?>
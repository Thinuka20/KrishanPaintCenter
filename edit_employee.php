<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();

$employee_id = (int)$_GET['id'];

// Get employee details
$query = "SELECT * FROM employees WHERE id = $employee_id";
$result = Database::search($query);
$employee = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = validateInput($_POST['name']);
    $phone = validateInput($_POST['phone']);
    $email = validateInput($_POST['email']);
    $address = validateInput($_POST['address']);
    $day_rate = (float)$_POST['day_rate'];
    $overtime_rate = (float)$_POST['overtime_rate'];
    $join_date = validateInput($_POST['join_date']);

    try {
        // Check if phone number already exists (excluding current employee)
        if (!empty($phone)) {
            $query = "SELECT id FROM employees WHERE phone = '$phone' AND id != $employee_id";
            $result = Database::search($query);
            if ($result->num_rows > 0) {
                throw new Exception("Phone number already exists for another employee.");
            }
        }

        $query = "UPDATE employees 
                  SET name = '$name',
                      phone = '$phone',
                      email = '$email',
                      address = '$address',
                      day_rate = $day_rate,
                      overtime_rate = $overtime_rate,
                      join_date = '$join_date'
                  WHERE id = $employee_id";

        Database::iud($query);
        $_SESSION['success'] = "Employee updated successfully.";
        header("Location: view_employee.php?id=$employee_id");
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
                            <h3>Edit Employee</h3>
                        </div>
                        <div class="col text-end">
                            <a href="view_employee.php?id=<?php echo $employee_id; ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="edit-employee-form" onsubmit="return validateForm()">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="required">Name</label>
                                    <input type="text" name="name" class="form-control" required 
                                           value="<?php echo $employee['name']; ?>">
                                </div>

                                <div class="form-group mb-3">
                                    <label class="required">Phone</label>
                                    <input type="tel" name="phone" class="form-control phone-input" required 
                                           value="<?php echo $employee['phone']; ?>"
                                           pattern="[0-9]{10}">
                                    <small class="text-muted">Format: 0777123456</small>
                                </div>

                                <div class="form-group mb-3">
                                    <label>Email</label>
                                    <input type="email" name="email" class="form-control" 
                                           value="<?php echo $employee['email']; ?>">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="required">Day Rate (Rs.)</label>
                                    <input type="number" name="day_rate" class="form-control" required 
                                           min="0" step="0.01" 
                                           value="<?php echo $employee['day_rate']; ?>">
                                </div>

                                <div class="form-group mb-3">
                                    <label class="required">OT Rate (Rs./hour)</label>
                                    <input type="number" name="overtime_rate" class="form-control" required 
                                           min="0" step="0.01" 
                                           value="<?php echo $employee['overtime_rate']; ?>">
                                </div>

                                <div class="form-group mb-3">
                                    <label class="required">Join Date</label>
                                    <input type="date" name="join_date" class="form-control" required 
                                           max="<?php echo date('Y-m-d'); ?>"
                                           value="<?php echo $employee['join_date']; ?>">
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group mb-3">
                                    <label>Address</label>
                                    <textarea name="address" class="form-control" rows="3"><?php echo $employee['address']; ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Update Employee</button>
                            <a href="view_employee.php?id=<?php echo $employee_id; ?>" class="btn btn-secondary">Cancel</a>
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

    // Form validation
    window.validateForm = function() {
        // Phone validation
        const phone = phoneInput.value;
        if (phone.length !== 10) {
            alert('Phone number must be exactly 10 digits.');
            return false;
        }

        // Email validation
        const email = document.querySelector('input[name="email"]').value;
        if (email && !validateEmail(email)) {
            alert('Please enter a valid email address.');
            return false;
        }

        // Rate validation
        const dayRate = parseFloat(document.querySelector('input[name="day_rate"]').value);
        const otRate = parseFloat(document.querySelector('input[name="overtime_rate"]').value);
        
        if (dayRate <= 0) {
            alert('Day rate must be greater than zero.');
            return false;
        }

        if (otRate <= 0) {
            alert('OT rate must be greater than zero.');
            return false;
        }

        return true;
    };

    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
});
</script>

<?php include 'footer.php'; ?>
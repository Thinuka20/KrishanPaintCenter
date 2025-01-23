<?php
// edit_customer.php - Edit customer details
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();

$customer_id = (int)$_GET['id'];

// If form is submitted
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
            // Check if phone number already exists (excluding current customer)
            if (!empty($phone)) {
                $query = "SELECT id FROM customers WHERE phone = '$phone' AND id != $customer_id";
                $result = Database::search($query);
                if ($result->num_rows > 0) {
                    throw new Exception("A customer with this phone number already exists.");
                }
            }

            // Update customer
            $query = "UPDATE customers 
                     SET name = '$name', 
                         phone = '$phone', 
                         email = '$email', 
                         address = '$address' 
                     WHERE id = $customer_id";
            Database::iud($query);

            $_SESSION['success'] = "Customer updated successfully.";
            header("Location: view_customer.php?id=$customer_id");
            exit();
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Get customer details
$query = "SELECT * FROM customers WHERE id = $customer_id";
$result = Database::search($query);
$customer = $result->fetch_assoc();

include 'header.php';
?>

<div class="container content">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h3>Edit Customer</h3>
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

                    <form method="POST" onsubmit="return validateForm('edit-customer-form')" id="edit-customer-form">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="required">Name</label>
                                    <input type="text" name="name" class="form-control" required
                                        value="<?php echo $customer['name']; ?>">
                                </div>

                                <div class="form-group">
                                    <label class="required">Phone</label>
                                    <input type="tel" name="phone" class="form-control phone-input" required
                                        value="<?php echo $customer['phone']; ?>"
                                        pattern="[0-9]{10}">
                                    <small class="text-muted">Format: 0777123456</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="email" class="form-control"
                                        value="<?php echo $customer['email']; ?>">
                                </div>

                                <div class="form-group">
                                    <label>Address</label>
                                    <textarea name="address" class="form-control" rows="3"><?php echo $customer['address']; ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Customer Summary -->
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <?php
                                        $query = "SELECT 
                                        (SELECT COUNT(*) FROM vehicles WHERE customer_id = $customer_id) as vehicle_count,
                                        COUNT(DISTINCT ri.id) as repair_count,
                                        MAX(ri.invoice_date) as last_visit
                                     FROM vehicles v
                                     LEFT JOIN repair_invoices ri ON v.id = ri.vehicle_id
                                     WHERE v.customer_id = $customer_id";
                                        $result = Database::search($query);
                                        $summary = $result->fetch_assoc();
                                        ?>
                                        <h5>Customer Summary</h5>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <p class="mb-1"><strong>Registered Vehicles:</strong> <?php echo $summary['vehicle_count']; ?></p>
                                            </div>
                                            <div class="col-md-4">
                                                <p class="mb-1"><strong>Total Repairs:</strong> <?php echo $summary['repair_count']; ?></p>
                                            </div>
                                            <div class="col-md-4">
                                                <p class="mb-1">
                                                    <strong>Last Visit:</strong>
                                                    <?php echo $summary['last_visit'] ?
                                                        date('Y-m-d', strtotime($summary['last_visit'])) : 'Never'; ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary">Update Customer</button>
                            <a href="view_customer.php?id=<?php echo $customer_id; ?>" class="btn btn-secondary">Cancel</a>

                            <!-- Quick Action Buttons -->
                            <div class="float-end">
                                <a href="add_vehicle.php?customer_id=<?php echo $customer_id; ?>" class="btn btn-success">
                                    <i class="fas fa-car"></i> Add Vehicle
                                </a>
                                <a href="customer_history.php?id=<?php echo $customer_id; ?>" class="btn btn-info">
                                    <i class="fas fa-history"></i> View History
                                </a>
                            </div>
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
        document.getElementById('edit-customer-form').addEventListener('submit', function(e) {
            const phone = phoneInput.value;
            if (phone.length !== 10) {
                e.preventDefault();
                alert('Phone number must be exactly 10 digits.');
                return false;
            }

            const email = document.querySelector('input[name="email"]').value;
            if (email && !validateEmail(email)) {
                e.preventDefault();
                alert('Please enter a valid email address.');
                return false;
            }

            return true;
        });
    });

    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
</script>

<?php include 'footer.php'; ?>
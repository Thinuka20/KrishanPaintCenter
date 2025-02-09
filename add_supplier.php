<?php
// add_supplier.php - Add new supplier
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';
checkLogin();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: unauthorized.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = validateInput($_POST['name']);
    $contact_person = validateInput($_POST['contact_person']);
    $phone = validateInput($_POST['phone']);
    $email = validateInput($_POST['email']);
    $address = validateInput($_POST['address']);

    $query = "INSERT INTO suppliers (name, contact_person, phone, email, address) 
              VALUES ('$name', '$contact_person', '$phone', '$email', '$address')";

    Database::iud($query);
    header("Location: suppliers.php");
    exit();
}

include 'header.php';
?>

<div class="container content">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Register New Supplier</h2>
        </div>
        <div class="col-md-6 text-end">
            <button onclick="history.back()" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Supplier
            </button>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <form method="POST" id="supplier-form" onsubmit="return validateForm('supplier-form')">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required">Company Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label class="required">Contact Person</label>
                            <input type="text" name="contact_person" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label class="required">Phone</label>
                            <input type="tel" name="phone" class="form-control" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>

                        <div class="form-group">
                            <label>Address</label>
                            <textarea name="address" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>

                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary">Save Supplier</button>
                    <a href="suppliers.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>
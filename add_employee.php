<?php
// add_employee.php - Add new employee
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
    $phone = validateInput($_POST['phone']);
    $email = validateInput($_POST['email']);
    $address = validateInput($_POST['address']);
    $day_rate = (float)$_POST['day_rate'];
    $overtime_rate = (float)$_POST['overtime_rate'];
    $join_date = validateInput($_POST['join_date']);

    $query = "INSERT INTO employees (name, phone, email, address, day_rate, overtime_rate, join_date) 
              VALUES ('$name', '$phone', '$email', '$address', $day_rate, $overtime_rate, '$join_date')";

    Database::iud($query);
    header("Location: employees.php");
    exit();
}

include 'header.php';
?>

<div class="container content">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Register New Employee</h2>
        </div>
        <div class="col-md-6 text-end">
            <button onclick="history.back()" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Employee
            </button>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <form method="POST" id="employee-form" onsubmit="return validateForm('employee-form')">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required">Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label class="required">Phone</label>
                            <input type="tel" name="phone" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Address</label>
                            <textarea name="address" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="form-group">
                            <label class="required">Day Rate (Rs.)</label>
                            <input type="number" name="day_rate" class="form-control" required step="0.01" min="0">
                        </div>

                        <div class="form-group">
                            <label class="required">Overtime Rate (Rs./hour)</label>
                            <input type="number" name="overtime_rate" class="form-control"
                                required step="0.01" min="0" value="200.00">
                        </div>

                        <div class="form-group">
                            <label class="required">Join Date</label>
                            <input type="date" name="join_date" class="form-control"
                                required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                </div>

                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary">Save Employee</button>
                    <a href="employees.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <?php include 'footer.php'; ?>
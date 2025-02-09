<?php
// edit_supplier.php - Edit supplier
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: unauthorized.php");
    exit();
}

$supplier_id = (int)$_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = validateInput($_POST['name']);
    $contact_person = validateInput($_POST['contact_person']);
    $phone = validateInput($_POST['phone']);
    $email = validateInput($_POST['email']);
    $address = validateInput($_POST['address']);
    
    $query = "UPDATE suppliers 
              SET name = '$name', 
                  contact_person = '$contact_person', 
                  phone = '$phone', 
                  email = '$email', 
                  address = '$address' 
              WHERE id = $supplier_id";
    
    Database::iud($query);
    header("Location: suppliers.php");
    exit();
}

$query = "SELECT * FROM suppliers WHERE id = $supplier_id";
$result = Database::search($query);
$supplier = $result->fetch_assoc();

include 'header.php';
?>

<div class="container content">
<div class="row mb-3">
        <div class="col-md-6">
            <h2>Edit Supplier</h2>
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
                            <input type="text" name="name" class="form-control" 
                                   required value="<?php echo $supplier['name']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="required">Contact Person</label>
                            <input type="text" name="contact_person" class="form-control" 
                                   required value="<?php echo $supplier['contact_person']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="required">Phone</label>
                            <input type="tel" name="phone" class="form-control" 
                                   required value="<?php echo $supplier['phone']; ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?php echo $supplier['email']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Address</label>
                            <textarea name="address" class="form-control" rows="3"><?php echo $supplier['address']; ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary">Update Supplier</button>
                    <a href="suppliers.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
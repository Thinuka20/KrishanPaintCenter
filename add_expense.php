<?php
// add_expense.php - Add new expense
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';
checkLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = validateInput($_POST['category']);
    $description = validateInput($_POST['description']);
    $amount = (float)$_POST['amount'];
    $expense_date = validateInput($_POST['expense_date']);
    
    $query = "INSERT INTO expenses (category, description, amount, expense_date) 
              VALUES ('$category', '$description', $amount, '$expense_date')";
    
    Database::iud($query);
    header("Location: expenses.php");
    exit();
}

include 'header.php';
?>

<div class="container content">
    <div class="card">
        <div class="card-header">
            <h3>Add New Expense</h3>
        </div>
        <div class="card-body">
            <form method="POST" id="expense-form" onsubmit="return validateForm('expense-form')">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required">Category</label>
                            <select name="category" class="form-control" required>
                                <option value="">Select Category</option>
                                <option value="Utilities">Utilities</option>
                                <option value="Rent">Rent</option>
                                <option value="Supplies">Supplies</option>
                                <option value="Equipment">Equipment</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Insurance">Insurance</option>
                                <option value="Taxes">Taxes</option>
                                <option value="Others">Others</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="required">Amount</label>
                            <input type="number" name="amount" class="form-control" required step="0.01" min="0">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required">Date</label>
                            <input type="date" name="expense_date" class="form-control" 
                                   required value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>

                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary">Save Expense</button>
                    <a href="expenses.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

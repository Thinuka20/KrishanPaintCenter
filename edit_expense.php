<?php
// edit_expense.php - Edit expense
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();

$expense_id = (int)$_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = validateInput($_POST['category']);
    $description = validateInput($_POST['description']);
    $amount = (float)$_POST['amount'];
    $expense_date = validateInput($_POST['expense_date']);
    
    $query = "UPDATE expenses 
              SET category = '$category', 
                  description = '$description', 
                  amount = $amount, 
                  expense_date = '$expense_date' 
              WHERE id = $expense_id";
    
    Database::iud($query);
    header("Location: expenses.php");
    exit();
}

$query = "SELECT * FROM expenses WHERE id = $expense_id";
$result = Database::search($query);
$expense = $result->fetch_assoc();

include 'header.php';
?>

<div class="container content">
    <div class="card">
        <div class="card-header">
            <h3>Edit Expense</h3>
        </div>
        <div class="card-body">
            <form method="POST" id="expense-form" onsubmit="return validateForm('expense-form')">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required">Category</label>
                            <select name="category" class="form-control" required>
                                <option value="">Select Category</option>
                                <?php
                                $categories = ['Utilities', 'Rent', 'Supplies', 'Equipment', 
                                             'Maintenance', 'Insurance', 'Taxes', 'Others'];
                                foreach ($categories as $category):
                                ?>
                                <option value="<?php echo $category; ?>" 
                                        <?php echo $expense['category'] === $category ? 'selected' : ''; ?>>
                                    <?php echo $category; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="required">Amount</label>
                            <input type="number" name="amount" class="form-control" required step="0.01" min="0" 
                                   value="<?php echo $expense['amount']; ?>">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required">Date</label>
                            <input type="date" name="expense_date" class="form-control" required 
                                   value="<?php echo date('Y-m-d', strtotime($expense['expense_date'])); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3"><?php echo $expense['description']; ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary">Update Expense</button>
                    <a href="expenses.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
<?php
// supplier_transactions.php - Manage supplier transactions
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();

$supplier_id = (int)$_GET['id'];

// Get supplier details
$query = "SELECT * FROM suppliers WHERE id = $supplier_id";
$result = Database::search($query);
$supplier = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transaction_type = validateInput($_POST['transaction_type']);
    $amount = (float)$_POST['amount'];
    $description = validateInput($_POST['description']);
    $transaction_date = validateInput($_POST['transaction_date']);
    
    $query = "INSERT INTO supplier_transactions 
              (supplier_id, transaction_type, amount, description, transaction_date) 
              VALUES ($supplier_id, '$transaction_type', $amount, '$description', '$transaction_date')";
    
    Database::iud($query);
    header("Location: supplier_transactions.php?id=$supplier_id");
    exit();
}

include 'header.php';
?>

<div class="container content">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Supplier Transactions - <?php echo $supplier['name']; ?></h2>
        </div>
        <div class="col-md-6 text-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" 
                    data-bs-target="#addTransactionModal">
                <i class="fas fa-plus"></i> Add Transaction
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="alert alert-info">
                        <?php
                        $query = "SELECT SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE -amount END) as balance 
                                 FROM supplier_transactions 
                                 WHERE supplier_id = $supplier_id";
                        $result = Database::search($query);
                        $balance = $result->fetch_assoc()['balance'] ?? 0;
                        ?>
                        <h4>Current Balance:</h4>
                        <h3 class="<?php echo $balance < 0 ? 'text-danger' : 'text-success'; ?>">
                            <?php echo formatCurrency(abs($balance)); ?>
                            <?php echo $balance < 0 ? ' (Due)' : ' (Credit)'; ?>
                        </h3>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped datatable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Description</th>
                            <th>Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT * FROM supplier_transactions 
                                 WHERE supplier_id = $supplier_id 
                                 ORDER BY transaction_date ASC";
                        $result = Database::search($query);
                        $running_balance = 0;
                        while ($row = $result->fetch_assoc()):
                            $amount = $row['transaction_type'] === 'credit' ? 
                                     $row['amount'] : -$row['amount'];
                            $running_balance += $amount;
                        ?>
                        <tr>
                            <td><?php echo date('Y-m-d', strtotime($row['transaction_date'])); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $row['transaction_type'] === 'credit' ? 
                                                           'success' : 'danger'; ?>">
                                    <?php echo ucfirst($row['transaction_type']); ?>
                                </span>
                            </td>
                            <td><?php echo formatCurrency($row['amount']); ?></td>
                            <td><?php echo $row['description']; ?></td>
                            <td class="<?php echo $running_balance < 0 ? 'text-danger' : 'text-success'; ?>">
                                <?php echo formatCurrency(abs($running_balance)); ?>
                                <?php echo $running_balance < 0 ? ' (Due)' : ' (Credit)'; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Transaction Modal -->
<div class="modal fade" id="addTransactionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="transaction-form" onsubmit="return validateForm('transaction-form')">
                <div class="modal-header">
                    <h5 class="modal-title">Add Transaction</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="required">Transaction Type</label>
                        <select name="transaction_type" class="form-control" required>
                            <option value="credit">Credit (Supplier Gives)</option>
                            <option value="debit">Debit (Supplier Receives)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Amount</label>
                        <input type="number" name="amount" class="form-control" required step="0.01" min="0">
                    </div>
                    
                    <div class="form-group">
                        <label class="required">Date</label>
                        <input type="date" name="transaction_date" class="form-control" 
                               required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Transaction</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
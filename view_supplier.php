<?php
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

// Get supplier details
$query = "SELECT * FROM suppliers WHERE id = $supplier_id";
$result = Database::search($query);
$supplier = $result->fetch_assoc();

// Calculate total transactions and balance
$query = "SELECT 
            COUNT(*) as total_transactions,
            SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE -amount END) as balance
          FROM supplier_transactions 
          WHERE supplier_id = $supplier_id";
$result = Database::search($query);
$summary = $result->fetch_assoc();

include 'header.php';
?>

<div class="container content">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Supplier Details</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="supplier_transactions.php?id=<?php echo $supplier_id; ?>" class="btn btn-success">
                <i class="fas fa-exchange-alt"></i> Supplier Transactions
            </a>
            <a href="edit_supplier.php?id=<?php echo $supplier_id; ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit Details
            </a>
            <button onclick="history.back()" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Supplier
            </button>
        </div>
    </div>

    <!-- Supplier Information -->
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">
                    <h4>Supplier Information</h4>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th width="150">Company Name</th>
                            <td><?php echo $supplier['name']; ?></td>
                        </tr>
                        <tr>
                            <th>Contact Person</th>
                            <td><?php echo $supplier['contact_person']; ?></td>
                        </tr>
                        <tr>
                            <th>Phone</th>
                            <td><?php echo $supplier['phone']; ?></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><?php echo $supplier['email'] ?: '-'; ?></td>
                        </tr>
                        <tr>
                            <th>Address</th>
                            <td><?php echo $supplier['address'] ?: '-'; ?></td>
                        </tr>
                        <tr>
                            <th>Since</th>
                            <td><?php echo date('Y-m-d', strtotime($supplier['created_at'])); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">
                    <h4>Account Summary</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h3><?php echo $summary['total_transactions']; ?></h3>
                                    <p class="mb-0">Total Transactions</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card <?php echo $summary['balance'] >= 0 ? 'bg-success' : 'bg-danger'; ?> text-white">
                                <div class="card-body text-center">
                                    <h3><?php echo formatCurrency(abs($summary['balance'])); ?></h3>
                                    <p class="mb-0"><?php echo $summary['balance'] >= 0 ? 'Credit Balance' : 'Due Amount'; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="card">
        <div class="card-header">
            <h4>Recent Transactions</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT * FROM supplier_transactions 
                                 WHERE supplier_id = $supplier_id 
                                 ORDER BY transaction_date DESC, id DESC
                                 LIMIT 10";
                        $result = Database::search($query);
                        $running_balance = 0;
                        while ($transaction = $result->fetch_assoc()):
                            $amount = $transaction['amount'];
                        ?>
                        <tr>
                            <td><?php echo date('Y-m-d', strtotime($transaction['transaction_date'])); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $transaction['transaction_type'] === 'credit' ? 'success' : 'danger'; ?>">
                                    <?php echo ucfirst($transaction['transaction_type']); ?>
                                </span>
                            </td>
                            <td><?php echo formatCurrency($amount); ?></td>
                            <td><?php echo $transaction['description']; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <div class="text-end">
                <a href="supplier_transactions.php?id=<?php echo $supplier_id; ?>" class="btn btn-primary">
                    View All Transactions
                </a>
            </div>
        </div>
    </div>

    <!-- Transaction History Chart -->
    <div class="card mt-3">
        <div class="card-header">
            <h4>Transaction History</h4>
        </div>
        <div class="card-body">
            <canvas id="transactionChart" style="height: 300px;"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Fetch transaction data for the chart
<?php
$query = "SELECT 
            DATE_FORMAT(transaction_date, '%Y-%m') as month,
            SUM(CASE WHEN transaction_type = 'credit' THEN amount ELSE 0 END) as credits,
            SUM(CASE WHEN transaction_type = 'debit' THEN amount ELSE 0 END) as debits
          FROM supplier_transactions 
          WHERE supplier_id = $supplier_id 
          GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
          ORDER BY month ASC
          LIMIT 12";
$result = Database::search($query);
$months = [];
$credits = [];
$debits = [];
while ($row = $result->fetch_assoc()) {
    $months[] = date('M Y', strtotime($row['month'] . '-01'));
    $credits[] = $row['credits'];
    $debits[] = $row['debits'];
}
?>

// Create the chart
new Chart(document.getElementById('transactionChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($months); ?>,
        datasets: [{
            label: 'Credits',
            data: <?php echo json_encode($credits); ?>,
            backgroundColor: 'rgba(40, 167, 69, 0.5)',
            borderColor: 'rgb(40, 167, 69)',
            borderWidth: 1
        }, {
            label: 'Debits',
            data: <?php echo json_encode($debits); ?>,
            backgroundColor: 'rgba(220, 53, 69, 0.5)',
            borderColor: 'rgb(220, 53, 69)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'Rs. ' + value.toLocaleString();
                    }
                }
            }
        },
        plugins: {
            title: {
                display: true,
                text: 'Monthly Transaction History'
            }
        }
    }
});
</script>

<?php include 'footer.php'; ?>
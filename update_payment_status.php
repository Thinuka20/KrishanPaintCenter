<?php
// update_payment_status.php - Update invoice payment status
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: unauthorized.php");
    exit();
}

$invoice_type = $_GET['type'] ?? 'repair';
$invoice_id = (int)$_GET['id'];

// Get current invoice details
$table = $invoice_type . '_invoices';
$query = "SELECT * FROM $table WHERE id = $invoice_id";
$result = Database::search($query);
$invoice = $result->fetch_assoc();

// Calculate total paid amount
$query = "SELECT COALESCE(SUM(amount), 0) as total_paid 
          FROM payment_transactions 
          WHERE invoice_type = '$invoice_type' AND invoice_id = $invoice_id";
$result = Database::search($query);
$total_paid = $result->fetch_assoc()['total_paid'];
$remaining_amount = $invoice['total_amount'] - $total_paid;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_amount = (float)$_POST['payment_amount'];
    $payment_date = validateInput($_POST['payment_date']);
    $payment_notes = validateInput($_POST['payment_notes']);
    $payment_type = validateInput($_POST['payment_type']);
    $check_number = ($payment_type === 'check') ? validateInput($_POST['check_number']) : null;

    Database::connection();
    Database::$connection->begin_transaction();

    try {
        // Validate payment amount against remaining amount
        if ($payment_amount > $remaining_amount) {
            throw new Exception("Payment amount cannot exceed the remaining balance of " . formatCurrency($remaining_amount));
        }

        // Determine payment status based on total payment
        $new_total_paid = $total_paid + $payment_amount;
        if ($new_total_paid >= $invoice['total_amount']) {
            $payment_status = 'paid';
        } elseif ($new_total_paid > 0) {
            $payment_status = 'partial';
        } else {
            $payment_status = 'pending';
        }

        // Update invoice status
        $query = "UPDATE $table SET payment_status = '$payment_status' WHERE id = $invoice_id";
        Database::iud($query);

        // Record payment transaction with payment type and check number
        $query = "INSERT INTO payment_transactions (
                    invoice_type, invoice_id, amount, payment_type, check_number, payment_date, notes
                 ) VALUES (
                    '$invoice_type', $invoice_id, $payment_amount, '$payment_type', " . 
                    ($check_number ? "'$check_number'" : "NULL") . ", '$payment_date', '$payment_notes'
                 )";
        Database::iud($query);

        Database::$connection->commit();
        $_SESSION['success'] = "Payment recorded successfully.";
        header("Location: " . ($invoice_type === 'repair' ? 'view_repair_invoice.php' : 'view_item_invoice.php') . "?id=$invoice_id");
        exit();
    } catch (Exception $e) {
        Database::$connection->rollback();
        $error = "Error updating payment: " . $e->getMessage();
    }
}

include 'header.php';
?>

<div class="container content">
<div class="row mb-3">
        <div class="col-md-6">
            <h2>Record Payment</h2>
        </div>
        <div class="col-md-6 text-end">
            <button onclick="history.back()" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Invoice
            </button>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5>Invoice #<?php echo $invoice['invoice_number']; ?></h5>
                    <p>Current Status:
                        <span class="badge bg-<?php echo $invoice['payment_status'] === 'paid' ?
                                                    'success' : ($invoice['payment_status'] === 'partial' ? 'warning' : 'danger'); ?>">
                            <?php echo ucfirst($invoice['payment_status']); ?>
                        </span>
                    </p>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5>Payment Summary</h5>
                            <div class="row">
                                <div class="col-7"><strong>Total Amount:</strong></div>
                                <div class="col-5 text-end"><?php echo formatCurrency($invoice['total_amount']); ?></div>
                            </div>
                            <div class="row">
                                <div class="col-7"><strong>Amount Paid:</strong></div>
                                <div class="col-5 text-end"><?php echo formatCurrency($total_paid); ?></div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-7"><strong>Remaining Balance:</strong></div>
                                <div class="col-5 text-end">
                                    <h4 class="text-danger mb-0"><?php echo formatCurrency($remaining_amount); ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST" onsubmit="return validateForm('payment-form')" id="payment-form">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="required">Payment Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">Rs.</span>
                                <input type="number" name="payment_amount" class="form-control"
                                    required step="0.01" max="<?php echo $remaining_amount; ?>"
                                    value="<?php echo $remaining_amount; ?>">
                            </div>
                            <small class="text-muted">Maximum allowed: <?php echo formatCurrency($remaining_amount); ?></small>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="required">Payment Date</label>
                            <input type="date" name="payment_date" class="form-control"
                                required value="<?php echo date('Y-m-d'); ?>"
                                max="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="required">Payment Method</label>
                            <select name="payment_type" class="form-control" required onchange="toggleCheckNumber(this.value)">
                                <option value="">Select Payment Method</option>
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="check">Check</option>
                                <option value="credit">Credit</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6" id="checkNumberField" style="display: none;">
                        <div class="form-group mb-3">
                            <label class="required">Check Number</label>
                            <input type="text" name="check_number" class="form-control" id="checkNumber">
                        </div>
                    </div>
                </div>

                <div class="form-group mb-3">
                    <label>Payment Notes</label>
                    <textarea name="payment_notes" class="form-control" rows="3"
                        placeholder="Enter any additional notes about this payment"></textarea>
                </div>

                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Record Payment
                    </button>
                    <a href="<?php echo $invoice_type === 'repair' ? 'view_repair_invoice.php' : 'view_item_invoice.php'; ?>?id=<?php echo $invoice_id; ?>"
                        class="btn btn-secondary">Cancel</a>
                </div>
            </form>

            <!-- Payment History -->
            <div class="mt-4">
                <h4>Payment History</h4>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                                <th>Check Number</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT * FROM payment_transactions 
                                     WHERE invoice_type = '$invoice_type' AND invoice_id = $invoice_id 
                                     ORDER BY payment_date DESC";
                            $result = Database::search($query);
                            if ($result->num_rows > 0):
                                while ($payment = $result->fetch_assoc()):
                            ?>
                                    <tr>
                                        <td><?php echo date('Y-m-d', strtotime($payment['payment_date'])); ?></td>
                                        <td><?php echo formatCurrency($payment['amount']); ?></td>
                                        <td><?php echo ucfirst($payment['payment_type'] ?? 'N/A'); ?></td>
                                        <td><?php echo $payment['check_number'] ?? '-'; ?></td>
                                        <td><?php echo $payment['notes']; ?></td>
                                    </tr>
                                <?php
                                endwhile;
                            else:
                                ?>
                                <tr>
                                    <td colspan="5" class="text-center">No payment records found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th class="text-end">Total Paid:</th>
                                <th><?php echo formatCurrency($total_paid); ?></th>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleCheckNumber(paymentType) {
        const checkNumberField = document.getElementById('checkNumberField');
        const checkNumberInput = document.getElementById('checkNumber');
        
        if (paymentType === 'check') {
            checkNumberField.style.display = 'block';
            checkNumberInput.required = true;
        } else {
            checkNumberField.style.display = 'none';
            checkNumberInput.required = false;
            checkNumberInput.value = '';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const paymentForm = document.getElementById('payment-form');
        const paymentAmount = document.querySelector('input[name="payment_amount"]');
        const paymentDate = document.querySelector('input[name="payment_date"]');
        const paymentType = document.querySelector('select[name="payment_type"]');

        // Payment amount validation
        paymentAmount.addEventListener('change', function() {
            const remainingAmount = <?php echo $remaining_amount; ?>;
            if (this.value > remainingAmount) {
                alert('Payment amount cannot exceed the remaining balance of Rs. ' + remainingAmount.toFixed(2));
                this.value = remainingAmount;
            }
        });

        // Form validation
        paymentForm.addEventListener('submit', function(e) {
            if (!paymentType.value) {
                e.preventDefault();
                alert('Please select a payment method');
                return false;
            }

            // Validate payment date
            const selectedDate = new Date(paymentDate.value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);

            if (selectedDate > tomorrow) {
                e.preventDefault();
                alert('Payment date cannot be in the future');
                return false;
            }
            return true;
        });
    });
</script>

<?php include 'footer.php'; ?>
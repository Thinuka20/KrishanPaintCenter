<?php
// view_item_invoice.php - View item invoice details
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();

$invoice_id = (int)$_GET['id'];

$query = "SELECT ii.*, c.name as customer_name, c.phone, c.email, c.address,
                 (SELECT COALESCE(SUM(amount), 0) 
                  FROM payment_transactions 
                  WHERE invoice_type = 'item' AND invoice_id = ii.id) as paid_amount
          FROM item_invoices ii 
          LEFT JOIN customers c ON ii.customer_id = c.id 
          WHERE ii.id = $invoice_id";
$result = Database::search($query);
$invoice = $result->fetch_assoc();

include 'header.php';
?>

<div class="container content">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Item Invoice #<?php echo $invoice['invoice_number']; ?></h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="print_item_invoice.php?id=<?php echo $invoice_id; ?>"
                class="btn btn-primary" target="_blank">
                <i class="fas fa-print"></i> Print Invoice
            </a>
            <button onclick="history.back()" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Invoices
            </button>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h4>Customer Information</h4>
                    <p><strong>Name:</strong> <?php echo $invoice['customer_name']; ?></p>
                    <p><strong>Phone:</strong> <?php echo $invoice['phone']; ?></p>
                    <p><strong>Email:</strong> <?php echo $invoice['email']; ?></p>
                    <p><strong>Address:</strong> <?php echo $invoice['address']; ?></p>
                </div>
                <div class="col-md-6 text-end">
                    <h4>Invoice Details</h4>
                    <p><strong>Date:</strong> <?php echo date('Y-m-d', strtotime($invoice['invoice_date'])); ?></p>
                    <p>
                        <strong>Status:</strong>
                        <span class="badge bg-<?php echo $invoice['payment_status'] === 'paid' ?
                                                    'success' : ($invoice['payment_status'] === 'partial' ? 'warning' : 'danger'); ?>">
                            <?php echo ucfirst($invoice['payment_status']); ?>
                        </span>
                    </p>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query = "SELECT iid.*, i.name as item_name, i.item_code 
                                         FROM item_invoice_details iid 
                                         LEFT JOIN items i ON iid.item_id = i.id 
                                         WHERE iid.item_invoice_id = $invoice_id";
                                $result = Database::search($query);
                                while ($item = $result->fetch_assoc()):
                                ?>
                                    <tr>
                                        <td>
                                            <?php echo $item['item_name']; ?>
                                        </td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td class="text-end"><?php echo formatCurrency($item['unit_price']); ?></td>
                                        <td class="text-end"><?php echo formatCurrency($item['subtotal']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Total Amount :</strong></td>
                                    <td class="text-end">
                                        <strong><?php echo formatCurrency($invoice['total_amount']); ?></strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Paid Amount :</strong></td>
                                    <td class="text-end">
                                        <strong><?php echo formatCurrency($invoice['paid_amount']); ?></strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Due Payment :</strong></td>
                                    <td class="text-end">
                                        <strong><?php echo formatCurrency($invoice['total_amount'] - $invoice['paid_amount']); ?></strong>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <?php if (!empty($invoice['notes'])): ?>
                <div class="row mt-4">
                    <div class="col-md-12">
                        <h4>Notes</h4>
                        <p><?php echo nl2br($invoice['notes']); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="update_payment_status.php?type=item&id=<?php echo $invoice_id; ?>" method="POST" id="paymentForm">
                <div class="modal-body">
                    <input type="hidden" name="invoice_type" value="item">
                    <input type="hidden" name="invoice_id" value="<?php echo $invoice_id; ?>">
                    <input type="hidden" name="return_url" value="view_item_invoice.php?id=<?php echo $invoice_id; ?>">

                    <div class="form-group mb-3">
                        <label>Outstanding Amount: </label>
                        <h4 class="text-danger">
                            <?php
                            $query = "SELECT SUM(amount) as paid FROM payment_transactions 
                                     WHERE invoice_type = 'item' AND invoice_id = $invoice_id";
                            $result = Database::search($query);
                            $paid = $result->fetch_assoc()['paid'] ?? 0;
                            $outstanding = $invoice['total_amount'] - $paid;
                            echo formatCurrency($outstanding);
                            ?>
                        </h4>
                    </div>

                    <div class="form-group mb-3">
                        <label class="required">Payment Amount</label>
                        <input type="number" name="payment_amount" class="form-control" required
                            step="0.01" max="<?php echo $outstanding; ?>">
                    </div>

                    <div class="form-group mb-3">
                        <label class="required">Payment Date</label>
                        <input type="date" name="payment_date" class="form-control"
                            required value="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="form-group mb-3">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
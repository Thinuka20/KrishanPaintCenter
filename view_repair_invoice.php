<?php
// view_repair_invoice.php - View repair invoice details
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();

$invoice_id = (int)$_GET['id'];

$query = "SELECT ri.*, v.registration_number, v.make, v.model, v.year, 
                 c.name as customer_name, c.phone, c.email, c.address,
                 (SELECT COALESCE(SUM(amount), 0) 
                  FROM payment_transactions 
                  WHERE invoice_type = 'repair' AND invoice_id = ri.id) as paid_amount
          FROM repair_invoices ri 
          LEFT JOIN vehicles v ON ri.vehicle_id = v.id 
          LEFT JOIN customers c ON v.customer_id = c.id 
          WHERE ri.id = $invoice_id";
$result = Database::search($query);
$invoice = $result->fetch_assoc();

include 'header.php';
?>

<div class="container content">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Repair Invoice #<?php echo $invoice['invoice_number']; ?></h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="print_repair_invoice.php?id=<?php echo $invoice_id; ?>"
                class="btn btn-primary" target="_blank">
                <i class="fas fa-print"></i> Print Invoice
            </a>
            <button onclick="history.back()" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Invoice
            </button>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h4>Customer Information</h4>
                    <p><strong>Name:</strong> <?php echo $invoice['customer_name']; ?></p>
                    <p><strong>Phone:</strong> <?php echo $invoice['phone']; ?></p>
                    <p><strong>Email:</strong> <?php echo $invoice['email']; ?></p>
                    <p><strong>Address:</strong> <?php echo $invoice['address']; ?></p>
                </div>
                <div class="col-md-6">
                    <h4>Vehicle Information</h4>
                    <p><strong>Registration:</strong> <?php echo $invoice['registration_number']; ?></p>
                    <p><strong>Make/Model:</strong> <?php echo $invoice['make'] . ' ' . $invoice['model']; ?></p>
                    <p><strong>Year:</strong> <?php echo $invoice['year']; ?></p>
                    <p><strong>Invoice Date:</strong> <?php echo date('Y-m-d', strtotime($invoice['invoice_date'])); ?></p>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-12">
                    <h4>Repair Items</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th>Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query = "SELECT description, price 
                             FROM repair_invoice_items 
                             WHERE repair_invoice_id = $invoice_id";
                                $result = Database::search($query);
                                while ($item = $result->fetch_assoc()):
                                ?>
                                    <tr>
                                        <td><?php echo $item['description']; ?></td>
                                        <td class="text-end"><?php echo formatCurrency($item['price']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td class="text-end"><strong>Total Amount :</strong></td>
                                    <td class="text-end"><strong><?php echo formatCurrency($invoice['total_amount']); ?></strong></td>
                                </tr>
                                <tr>
                                    <td class="text-end"><strong>Paid Amount :</strong></td>
                                    <td class="text-end"><strong><?php echo formatCurrency($invoice['paid_amount']); ?></strong></td>
                                </tr>
                                <tr>
                                    <td class="text-end"><strong>Due Payment :</strong></td>
                                    <td class="text-end"><strong><?php echo formatCurrency($invoice['total_amount'] - $invoice['paid_amount']); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-12">
                    <h4>Repair Photos</h4>
                    <div class="row">
                        <?php
                        $query = "SELECT * FROM repair_photos WHERE repair_invoice_id = $invoice_id";
                        $result = Database::search($query);
                        while ($photo = $result->fetch_assoc()):
                        ?>
                            <div class="col-3 mb-3">
                                <img src="<?php echo $photo['photo_path']; ?>" class="img-fluid preview-image"
                                    alt="Repair Photo">
                            </div>
                        <?php endwhile; ?>
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

<?php include 'footer.php'; ?>
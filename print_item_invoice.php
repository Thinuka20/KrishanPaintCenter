<?php
// print_item_invoice.php - Print item invoice
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();

$invoice_id = (int)$_GET['id'];

$query = "SELECT ii.*, c.name as customer_name, c.phone, c.email, c.address
          FROM item_invoices ii 
          LEFT JOIN customers c ON ii.customer_id = c.id 
          WHERE ii.id = $invoice_id";
$result = Database::search($query);
$invoice = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo $invoice['invoice_number']; ?> - Krishan Paint Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <style>
        @media print {
            body {
                font-size: 10pt;
            }

            .no-print {
                display: none !important;
            }

            .print-only {
                display: block !important;
            }
        }

        .invoice-header {
            border-bottom: 2px solid #dee2e6;
            margin-bottom: 10px;
            padding-bottom: 10px;
        }

        .invoice-footer {
            border-top: 2px solid #dee2e6;
            margin-top: 10px;
            padding-top: 10px;
        }

        .company-logo {
            max-width: 200px;
            height: auto;
        }

        .table th {
            background-color: #f8f9fa;
        }

        @media print {
            .table th {
                background-color: #f8f9fa !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>
    <div class="p-3">
        <div class="invoice-header">
            <div class="row">
                <div class="col-12">
                    <h5 class="text-center">ITEM INVOICE</h5>
                    <div class="row">
                        <div class="col-6 text-start">
                            <p class="mb-1"><strong>Invoice No.:</strong> <?php echo $invoice['invoice_number']; ?></p>
                        </div>
                        <div class="col-6 text-end">
                            <p class="mb-1"><strong>Date:</strong> <?php echo date('Y-m-d', strtotime($invoice['invoice_date'])); ?></p>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="row mb-4">

        </div>

        <div class="table-responsive mb-5">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Item Description</th>
                        <th class="text-center" width="70">Quantity</th>
                        <th class="text-end" width="120">Unit Price</th>
                        <th class="text-end" width="120">Subtotal</th>
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
                            <td class="text-center"><?php echo $item['quantity']; ?></td>
                            <td class="text-end"><?php echo formatCurrency($item['unit_price']); ?></td>
                            <td class="text-end"><?php echo formatCurrency($item['subtotal']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end"><strong>Total Amount:</strong></td>
                        <td class="text-end">
                            <strong><?php echo formatCurrency($invoice['total_amount']); ?></strong>
                        </td>
                    </tr>
                    <?php
                    $query = "SELECT * FROM payment_transactions 
                         WHERE invoice_type = 'item' AND invoice_id = $invoice_id 
                         ORDER BY payment_date";
                    $result = Database::search($query);
                    $total_paid = 0;
                    while ($payment = $result->fetch_assoc()):
                        $total_paid += $payment['amount'];
                    endwhile; ?>
                    <tr>
                        <th colspan="3" class="text-end">Total Paid:</th>
                        <th class="text-end"><?php echo formatCurrency($total_paid); ?></th>
                    </tr>
                    <?php
                    if ($total_paid != $invoice['total_amount']) {
                    ?>
                        <tr>
                            <th colspan="3" class="text-end">Balance Due:</th>
                            <th class="text-end"><?php echo formatCurrency($invoice['total_amount'] - $total_paid); ?></th>
                        </tr>
                    <?php
                    }
                    ?>
                </tfoot>
            </table>
        </div>

        <?php if (!empty($invoice['notes'])): ?>
            <div class="mt-4">
                <h4>Notes</h4>
                <div class="card">
                    <div class="card-body">
                        <?php echo nl2br($invoice['notes']); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Signature Section -->
        <div class="mt-3">
            <p class="mb-0">....................................................</p>
            <p class="mb-0 fw-bold">W.Krishan Shyamal</p>
            <p class="mb-1">Proprietor</p>
        </div>

        <div class="invoice-footer mt-2">
            <div class="row">
                <div class="col-12 text-center">
                    <p class="mb-4">Thank you for your business!</p>
                </div>
            </div>
        </div>

        <div class="row mt-4 no-print">
            <div class="col-12 text-center">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print"></i> Print Invoice
                </button>
                <a href="invoices.php?type=item" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Invoices
                </a>
            </div>
        </div>
    </div>

    <script>
        window.onload = function() {
            if (!window.location.search.includes('noprint')) {
                window.print();
            }
        };
    </script>
</body>

</html>
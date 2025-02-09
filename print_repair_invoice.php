<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();

$invoice_id = (int)$_GET['id'];

$query = "SELECT ri.*, v.registration_number, v.make, v.model, v.year,
          c.name as customer_name, c.phone, c.email, c.address 
          FROM repair_invoices ri
          LEFT JOIN vehicles v ON ri.vehicle_id = v.id
          LEFT JOIN customers c ON v.customer_id = c.id
          WHERE ri.id = $invoice_id";
$result = Database::search($query);
$invoice = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Repair Invoice #<?php echo $invoice['invoice_number']; ?> - Krishan Paint Center</title>
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

            .table th {
                background-color: #f8f9fa !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        .invoice-header,
        .invoice-footer {
            border-bottom: 2px solid #dee2e6;
            margin-bottom: 10px;
            padding-bottom: 10px;
        }

        .invoice-footer {
            border-top: 2px solid #dee2e6;
            margin-top: 10px;
            padding-top: 10px;
        }

        .table th {
            background-color: #f8f9fa;
        }
    </style>
</head>

<body>
    <div class="p-3">
        <div class="invoice-header">
            <div class="row">
                <div class="col-6 text-start">
                    <h6>Vehicle Details:</h6>
                    <p class="mb-1"><strong>Registration:</strong> <?php echo $invoice['registration_number']; ?></p>
                    <p class="mb-1"><strong>Make/Model:</strong> <?php echo $invoice['make'] . ' ' . $invoice['model']; ?></p>
                    <p class="mb-1"><strong>Year:</strong> <?php echo $invoice['year']; ?></p>
                </div>
                <div class="col-6 text-end">
                    <h6>REPAIR INVOICE</h6>
                    <p class="mb-1"><strong>Invoice #:</strong> <?php echo $invoice['invoice_number']; ?></p>
                    <p class="mb-1"><strong>Date:</strong> <?php echo date('Y-m-d', strtotime($invoice['invoice_date'])); ?></p>
                    <p><strong>Status:</strong>
                        <span class="badge bg-<?php echo $invoice['payment_status'] === 'paid' ? 'success' : ($invoice['payment_status'] === 'partial' ? 'warning' : 'danger'); ?>">
                            <?php echo ucfirst($invoice['payment_status']); ?>
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <div class="table-responsive mb-5">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="text-end" width="150">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $items_query = "SELECT description, price as amount 
                                  FROM repair_invoice_items 
                                  WHERE repair_invoice_id = $invoice_id";
                    $items_result = Database::search($items_query);
                    while ($item = $items_result->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?php echo $item['description']; ?></td>
                            <td class="text-end"><?php echo formatCurrency($item['amount']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td class="text-end"><strong>Total Amount:</strong></td>
                        <td class="text-end">
                            <strong><?php echo formatCurrency($invoice['total_amount']); ?></strong>
                        </td>
                    </tr>
                    <?php
                    $payments_query = "SELECT * FROM payment_transactions 
                                         WHERE invoice_type = 'repair' 
                                         AND invoice_id = $invoice_id 
                                         ORDER BY payment_date";
                    $payments_result = Database::search($payments_query);
                    $total_paid = 0;
                    while ($payment = $payments_result->fetch_assoc()):
                        $total_paid += $payment['amount'];
                    endwhile; ?>

                    <tr>
                        <th class="text-end">Total Paid:</th>
                        <th class="text-end"><?php echo formatCurrency($total_paid); ?></th>
                    </tr>
                    <?php
                    if ($invoice['total_amount'] != $total_paid) {
                    ?>
                        <tr>
                            <th class="text-end">Balance Due:</th>
                            <th class="text-end"><?php echo formatCurrency($invoice['total_amount'] - $total_paid); ?></th>
                        </tr>
                    <?php
                    }
                    ?>
                </tfoot>
            </table>
        </div>

        <!-- Signature Section -->
        <div class="mt-3">
            <p class="mb-0">....................................................</p>
            <p class="mb-0 fw-bold">W.Krishan Shyamal</p>
            <p class="mb-1">Proprietor</p>
        </div>

        <div class="invoice-footer">
            <div class="row">
                <div class="col-12 text-center">
                    <p>Thank you for your business!</p>
                </div>
            </div>
        </div>

        <div class="row mt-4 no-print">
            <div class="col-12 text-center">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print"></i> Print Invoice
                </button>
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
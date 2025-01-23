<?php
// print_estimate.php - Print estimate
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();

$estimate_id = (int)$_GET['id'];

$query = "SELECT e.*, v.registration_number, v.make, v.model, v.year, 
                 c.name as customer_name, c.phone, c.email, c.address
          FROM estimates e 
          LEFT JOIN vehicles v ON e.vehicle_id = v.id 
          LEFT JOIN customers c ON v.customer_id = c.id 
          WHERE e.id = $estimate_id";
$result = Database::search($query);
$estimate = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estimate #<?php echo $estimate['estimate_number']; ?> - Krishan Paint Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            body {
                font-size: 12pt;
            }
            .no-print {
                display: none !important;
            }
            .print-only {
                display: block !important;
            }
        }
        .estimate-header {
            border-bottom: 2px solid #dee2e6;
            margin-bottom: 20px;
            padding-bottom: 20px;
        }
        .estimate-footer {
            border-top: 2px solid #dee2e6;
            margin-top: 20px;
            padding-top: 20px;
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
        .validity-note {
            border: 1px solid #dee2e6;
            padding: 10px;
            margin: 20px 0;
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="estimate-header">
            <div class="row">
                <div class="col-6 text-start">
                    <!-- Add your logo here -->
                    <!-- <img src="logo.png" alt="Krishan Paint Center" class="company-logo"> -->
                    <h2>Krishan Paint Center</h2>
                    <p>Professional Auto Paint Services</p>
                </div>
                <div class="col-6 text-end">
                    <h3>ESTIMATE</h3>
                    <p class="mb-1"><strong>Estimate #:</strong> <?php echo $estimate['estimate_number']; ?></p>
                    <p class="mb-1"><strong>Date:</strong> <?php echo date('Y-m-d', strtotime($estimate['estimate_date'])); ?></p>
                    <p class="mb-1"><strong>Valid Until:</strong> <?php echo date('Y-m-d', strtotime($estimate['valid_until'])); ?></p>
                    <p><strong>Status:</strong> 
                        <span class="badge bg-<?php echo $estimate['status'] === 'approved' ? 'success' : 
                            ($estimate['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                            <?php echo ucfirst($estimate['status']); ?>
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h4>Customer Information</h4>
                        <p class="mb-1"><strong>Name:</strong> <?php echo $estimate['customer_name']; ?></p>
                        <p class="mb-1"><strong>Phone:</strong> <?php echo $estimate['phone']; ?></p>
                        <p class="mb-1"><strong>Email:</strong> <?php echo $estimate['email']; ?></p>
                        <p class="mb-1"><strong>Address:</strong> <?php echo $estimate['address']; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h4>Vehicle Information</h4>
                        <p class="mb-1"><strong>Registration:</strong> <?php echo $estimate['registration_number']; ?></p>
                        <p class="mb-1"><strong>Make/Model:</strong> <?php echo $estimate['make'] . ' ' . $estimate['model']; ?></p>
                        <p class="mb-1"><strong>Year:</strong> <?php echo $estimate['year']; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Item Description</th>
                        <th class="text-center" width="100">Quantity</th>
                        <th class="text-end" width="150">Unit Price</th>
                        <th class="text-end" width="150">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT ei.*, i.name as item_name, i.item_code 
                             FROM estimate_items ei 
                             LEFT JOIN items i ON ei.item_id = i.id 
                             WHERE ei.estimate_id = $estimate_id";
                    $result = Database::search($query);
                    while ($item = $result->fetch_assoc()):
                    ?>
                    <tr>
                        <td>
                            <?php echo $item['item_name']; ?>
                            <small class="text-muted d-block">Code: <?php echo $item['item_code']; ?></small>
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
                            <strong><?php echo formatCurrency($estimate['total_amount']); ?></strong>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="validity-note">
            <p class="mb-0"><strong>Note:</strong> This estimate is valid until <?php echo date('Y-m-d', strtotime($estimate['valid_until'])); ?></p>
            <p class="mb-0">Prices and availability are subject to change after the validity period.</p>
        </div>

        <?php if (!empty($estimate['notes'])): ?>
        <div class="mt-4">
            <h4>Notes</h4>
            <div class="card">
                <div class="card-body">
                    <?php echo nl2br($estimate['notes']); ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="estimate-footer mt-5">
            <div class="row">
                <div class="col-12">
                    <p class="mb-4">Thank you for choosing Krishan Paint Center!</p>
                    <div class="row mt-4">
                        <div class="col-6">
                            <div class="border-top pt-2">
                                <p class="mb-0">Customer Signature</p>
                                <p class="text-muted small">Sign above the line</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border-top pt-2">
                                <p class="mb-0">Authorized Signature</p>
                                <p class="text-muted small">Sign above the line</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-12 text-center small">
                    <p class="mb-0">Krishan Paint Center</p>
                    <p class="mb-0">[Your Address]</p>
                    <p class="mb-0">Phone: [Your Phone] | Email: [Your Email]</p>
                </div>
            </div>
        </div>

        <div class="row mt-4 no-print">
            <div class="col-12 text-center">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print"></i> Print Estimate
                </button>
                <a href="estimates.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Estimates
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
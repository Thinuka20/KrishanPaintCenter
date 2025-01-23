<?php
// view_estimate.php - View estimate details
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
                font-size: 10pt;
            }
            .no-print {
                display: none !important;
            }
            .print-only {
                display: block !important;
            }
        }
        .estimate-header, .estimate-footer {
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        .company-info {
            text-align: center;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="company-info">
            <h2>Krishan Paint Center</h2>
            <p>Address: [Your Address]<br>
               Phone: [Your Phone]<br>
               Email: [Your Email]</p>
        </div>

        <div class="estimate-header">
            <div class="row">
                <div class="col-12">
                    <h3 class="text-center">Estimate</h3>
                    <p class="text-end">
                        Estimate #: <?php echo $estimate['estimate_number']; ?><br>
                        Date: <?php echo date('Y-m-d', strtotime($estimate['estimate_date'])); ?><br>
                        Valid Until: <?php echo date('Y-m-d', strtotime($estimate['valid_until'])); ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <h6>Customer Information</h6>
                <p><strong>Name:</strong> <?php echo $estimate['customer_name']; ?><br>
                   <strong>Phone:</strong> <?php echo $estimate['phone']; ?><br>
                   <strong>Email:</strong> <?php echo $estimate['email']; ?><br>
                   <strong>Address:</strong> <?php echo $estimate['address']; ?></p>
            </div>
            <div class="col-md-6">
                <h6>Vehicle Information</h6>
                <p><strong>Registration:</strong> <?php echo $estimate['registration_number']; ?><br>
                   <strong>Make/Model:</strong> <?php echo $estimate['make'] . ' ' . $estimate['model']; ?><br>
                   <strong>Year:</strong> <?php echo $estimate['year']; ?></p>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
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
                        $query = "SELECT ei.*, i.name as item_name 
                                 FROM estimate_items ei 
                                 LEFT JOIN items i ON ei.item_id = i.id 
                                 WHERE ei.estimate_id = $estimate_id";
                        $result = Database::search($query);
                        while ($item = $result->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo $item['item_name']; ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td><?php echo formatCurrency($item['unit_price']); ?></td>
                            <td><?php echo formatCurrency($item['subtotal']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Total Amount:</strong></td>
                            <td><strong><?php echo formatCurrency($estimate['total_amount']); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <?php if (!empty($estimate['notes'])): ?>
        <div class="row mt-4">
            <div class="col-12">
                <h4>Notes</h4>
                <p><?php echo nl2br($estimate['notes']); ?></p>
            </div>
        </div>
        <?php endif; ?>

        <div class="estimate-footer mt-4">
            <div class="row">
                <div class="col-md-12">
                    <p class="mb-4"><strong>Terms and Conditions:</strong></p>
                    <ol>
                        <li>This estimate is valid until <?php echo date('Y-m-d', strtotime($estimate['valid_until'])); ?></li>
                        <li>Prices are subject to change after the validity period</li>
                        <li>Additional repairs or parts may be required after inspection</li>
                        <li>Final invoice may vary based on actual work performed</li>
                    </ol>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-6">
                    <p>Customer Signature: _____________________</p>
                </div>
                <div class="col-6 text-end">
                    <p>Authorized Signature: _____________________</p>
                </div>
            </div>
        </div>

        <div class="row mt-4 no-print">
            <div class="col-12 text-center">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print"></i> Print Estimate
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
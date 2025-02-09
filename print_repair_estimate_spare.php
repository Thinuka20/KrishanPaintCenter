<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();

$id = isset($_GET['id']) ? $_GET['id'] : (isset($_POST['id']) ? $_POST['id'] : null);

if (!$id) {
    exit('Invalid request');
}

$settings_query = "SELECT * FROM settings WHERE id = 1";
$settings_result = Database::search($settings_query);
$settings = $settings_result->fetch_assoc();

$query = "SELECT e.*, v.registration_number, v.make, v.model, v.year, 
          c.name as customer_name, c.phone, c.email, c.address 
          FROM estimates_spareparts e
          JOIN vehicles v ON e.vehicle_id = v.id 
          JOIN customers c ON v.customer_id = c.id
          WHERE e.id = '$id'";
$result = Database::search($query);
$estimate = $result->fetch_assoc();

$items_query = "SELECT * FROM estimate_items_spareparts WHERE estimate_id = '$id'";
$items_result = Database::search($items_query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spare Parts Estimate #<?php echo $estimate['estimate_number']; ?> - <?php echo $settings['business_name']; ?></title>
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
                    <p class="mb-1"><strong>Registration:</strong> <?php echo $estimate['registration_number']; ?></p>
                    <p class="mb-1"><strong>Make/Model:</strong> <?php echo $estimate['make'] . ' ' . $estimate['model']; ?></p>
                    <p class="mb-1"><strong>Year:</strong> <?php echo $estimate['year']; ?></p>
                </div>
                <div class="col-6 text-end">
                    <h6>Spare Parts Estimate</h6>
                    <p class="mb-1"><strong>Estimate #:</strong> <?php echo $estimate['estimate_number']; ?></p>
                    <p class="mb-1"><strong>Date:</strong> <?php echo date('Y-m-d', strtotime($estimate['estimate_date'])); ?></p>
                </div>
            </div>
        </div>

        <div class="table-responsive mb-5">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="text-end" width="120">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total = 0;
                    while ($item = $items_result->fetch_assoc()):
                        $total += $item['price'];
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['description']); ?></td>
                            <td class="text-end"><?php echo (!empty($item['price']) && $item['price'] > 0) ? formatCurrency($item['price']) : '--'; ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td class="text-end"><strong>Total Amount:</strong></td>
                        <td class="text-end">
                            <strong><?php echo (!empty($total) && $total > 0) ? formatCurrency($total) : '--'; ?></strong>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <?php if (!empty($estimate['notes'])): ?>
            <div class="mt-4">
                <h6>Notes</h6>
                <p><?php echo nl2br(htmlspecialchars($estimate['notes'])); ?></p>
            </div>
        <?php endif; ?>

        <!-- Signature Section -->
        <div class="mt-3">
            <p class="mb-0">....................................................</p>
            <p class="mb-0 fw-bold">W.Nimal Thushara Lowe</p>
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
                <a href="#" onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print"></i> Print
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
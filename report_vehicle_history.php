<?php
// report_vehicle_history.php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';
require_once 'classes/ReportPDF.php';

checkLogin();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: unauthorized.php");
    exit();
}

// Get filter parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$vehicle_id = isset($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : null;

// Check if PDF export is requested
if (isset($_GET['export_pdf'])) {
    if ($vehicle_id) {
        // Get vehicle details
        $query = "SELECT v.*, c.name as customer_name, c.phone as customer_phone 
                 FROM vehicles v 
                 JOIN customers c ON v.customer_id = c.id 
                 WHERE v.id = $vehicle_id";
        $result = Database::search($query);
        $vehicle = $result->fetch_assoc();

        // Get repair history
        $repair_query = "SELECT ri.*, 
                              GROUP_CONCAT(rii.description SEPARATOR '\n') as repair_items
                       FROM repair_invoices ri
                       LEFT JOIN repair_invoice_items rii ON ri.id = rii.repair_invoice_id
                       WHERE ri.vehicle_id = $vehicle_id 
                       AND ri.invoice_date BETWEEN '$start_date' AND '$end_date'
                       GROUP BY ri.id
                       ORDER BY ri.invoice_date";
        
        $repair_result = Database::search($repair_query);
        $repair_data = [];
        while ($row = $repair_result->fetch_assoc()) {
            $repair_data[] = $row;
        }

        // Calculate totals
        $totals = [
            'repair_amount' => array_sum(array_column($repair_data, 'total_amount')),
            'repair_count' => count($repair_data)
        ];

        // Generate PDF
        $pdf = new ReportPDF('L');
        $pdf->generateVehicleHistoryReport(
            $vehicle,
            $repair_data,
            $totals,
            date('Y-m-d', strtotime($start_date)) . ' to ' . date('Y-m-d', strtotime($end_date))
        );
        $pdf->Output('Vehicle_History_Report_' . date('Y-m-d') . '.pdf', 'I');
        exit;
    }
}

// Get all vehicles for dropdown
$query = "SELECT v.id, v.registration_number, v.make, v.model, c.name as customer_name 
          FROM vehicles v
          JOIN customers c ON v.customer_id = c.id 
          ORDER BY v.registration_number";
$vehicles_result = Database::search($query);

include 'header.php';
?>

<div class="container content">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Vehicle History Report</h2>
        </div>
        <div class="col-md-6 text-end">
            <?php if ($vehicle_id): ?>
            <a href="?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&vehicle_id=<?php echo $vehicle_id; ?>&export_pdf=1" 
               class="btn btn-primary" target="_blank">
               <i class="fas fa-print"></i> Print Report
            </a>
            <?php endif; ?>
            <button onclick="history.back()" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Reports
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <!-- Search Form -->
            <form method="GET" class="row mb-3">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Vehicle</label>
                        <select class="form-control select2" name="vehicle_id" required>
                            <option value="">Select Vehicle</option>
                            <?php while ($vehicle = $vehicles_result->fetch_assoc()): ?>
                                <option value="<?php echo $vehicle['id']; ?>" 
                                    <?php echo ($vehicle_id == $vehicle['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($vehicle['registration_number'] . ' - ' . $vehicle['make'] . ' ' . $vehicle['model'] . ' (' . $vehicle['customer_name'] . ')'); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" name="start_date" class="form-control" 
                               value="<?php echo $start_date; ?>" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" name="end_date" class="form-control" 
                               value="<?php echo $end_date; ?>" required>
                    </div>
                </div>
                <div class="col-md-2 mt-4">
                        <button type="submit" class="btn btn-primary">Generate</button>
                </div>
            </form>

            <?php if ($vehicle_id): 
                // Get vehicle details
                $query = "SELECT v.*, c.name as customer_name, c.phone as customer_phone 
                         FROM vehicles v 
                         JOIN customers c ON v.customer_id = c.id 
                         WHERE v.id = $vehicle_id";
                $result = Database::search($query);
                $vehicle = $result->fetch_assoc();

                // Get repair history
                $repair_query = "SELECT ri.*, 
                                      GROUP_CONCAT(rii.description SEPARATOR '\n') as repair_items
                               FROM repair_invoices ri
                               LEFT JOIN repair_invoice_items rii ON ri.id = rii.repair_invoice_id
                               WHERE ri.vehicle_id = $vehicle_id 
                               AND ri.invoice_date BETWEEN '$start_date' AND '$end_date'
                               GROUP BY ri.id
                               ORDER BY ri.invoice_date";
                
                $repair_result = Database::search($repair_query);
                $total_amount = 0;
            ?>
                <!-- Vehicle Details -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5>Vehicle Details</h5>
                        <table class="table table-borderless">
                            <tr>
                                <th width="150">Registration:</th>
                                <td><?php echo htmlspecialchars($vehicle['registration_number']); ?></td>
                            </tr>
                            <tr>
                                <th>Make:</th>
                                <td><?php echo htmlspecialchars($vehicle['make']); ?></td>
                            </tr>
                            <tr>
                                <th>Model:</th>
                                <td><?php echo htmlspecialchars($vehicle['model']); ?></td>
                            </tr>
                            <tr>
                                <th>Year:</th>
                                <td><?php echo htmlspecialchars($vehicle['year']); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h5>Owner Details</h5>
                        <table class="table table-borderless">
                            <tr>
                                <th width="150">Name:</th>
                                <td><?php echo htmlspecialchars($vehicle['customer_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Phone:</th>
                                <td><?php echo htmlspecialchars($vehicle['customer_phone']); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Repair History -->
                <h5 class="mb-3">Repair History</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Invoice #</th>
                                <th>Repair Items</th>
                                <th class="text-end">Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $repair_result->fetch_assoc()):
                                $total_amount += $row['total_amount'];
                            ?>
                                <tr>
                                    <td><?php echo date('Y-m-d', strtotime($row['invoice_date'])); ?></td>
                                    <td><?php echo $row['invoice_number']; ?></td>
                                    <td><?php echo nl2br(htmlspecialchars($row['repair_items'])); ?></td>
                                    <td class="text-end"><?php echo formatCurrency($row['total_amount']); ?></td>
                                    <td><?php echo ucfirst($row['payment_status']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3">Total Amount</th>
                                <th class="text-end"><?php echo formatCurrency($total_amount); ?></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

            <?php else: ?>
                <div class="alert alert-info">
                    Please select a vehicle to generate the report.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%'
    });
});
</script>

<?php include 'footer.php'; ?>
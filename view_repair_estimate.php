<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();

$id = $_GET['id'] ?? null;
if (!$id) {
    $_SESSION['error'] = 'Invalid estimate ID';
    header('Location: repair_estimates.php');
    exit();
}

$query = "SELECT e.*, v.registration_number, v.make, v.model, c.name as customer_name, c.phone as customer_phone
          FROM estimates e
          JOIN vehicles v ON e.vehicle_id = v.id 
          JOIN customers c ON v.customer_id = c.id
          WHERE e.id = '$id'";

$result = Database::search($query);
if ($result->num_rows === 0) {
    $_SESSION['error'] = 'Estimate not found';
    header('Location: view_estimates.php');
    exit();
}

$estimate = $result->fetch_assoc();

$items_query = "SELECT * FROM estimate_items WHERE estimate_id = '$id'";
$items_result = Database::search($items_query);

include 'header.php';
?>

<div class="container content">
    <?php include 'alerts.php'; ?>

    <div class="row mb-3">
        <div class="col-md-6">
            <h2>View Initial Estimate #<?php echo $estimate['estimate_number']; ?></h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="#" onclick="printEstimate(<?php echo $id; ?>)" class="btn btn-primary">
                <i class="fas fa-print"></i> Print
            </a>
            <button onclick="history.back()" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Estimates
            </button>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5>Customer Details</h5>
                    <p><strong>Name:</strong> <?php echo $estimate['customer_name']; ?></p>
                    <p><strong>Phone:</strong> <?php echo $estimate['customer_phone']; ?></p>
                </div>
                <div class="col-md-6">
                    <h5>Vehicle Details</h5>
                    <p><strong>Registration:</strong> <?php echo $estimate['registration_number']; ?></p>
                    <p><strong>Make/Model:</strong> <?php echo $estimate['make'] . ' ' . $estimate['model']; ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h5>Estimate Details</h5>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th class="text-end">Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($item = $items_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $item['description']; ?></td>
                                <td class="text-end"><?php echo formatCurrency($item['price']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th class="text-end">Total Amount:</th>
                            <th class="text-end"><?php echo formatCurrency($estimate['total_amount']); ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <?php if (!empty($estimate['notes'])): ?>
                <div class="mt-3">
                    <h5>Notes</h5>
                    <p><?php echo nl2br($estimate['notes']); ?></p>
                </div>
            <?php endif; ?>

            <div class="mt-3">
                <p><strong>Estimate Date:</strong> <?php echo date('Y-m-d', strtotime($estimate['estimate_date'])); ?></p>
                <p><strong>Created At:</strong> <?php echo date('Y-m-d H:i:s', strtotime($estimate['created_at'])); ?></p>
            </div>
        </div>
    </div>
</div>

<script>
    function printEstimate(id) {
        const query = "SELECT e.*, ei.* FROM estimates e JOIN estimate_items ei ON e.id = ei.estimate_id WHERE e.id = '" + id + "'";

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'print_repair_estimate.php';
        form.target = '_blank';

        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id';
        idInput.value = id;

        form.appendChild(idInput);
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }
</script>


<?php include 'footer.php'; ?>
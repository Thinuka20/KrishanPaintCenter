<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();

$employee_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

// Get employee details
$query = "SELECT * FROM employees WHERE id = $employee_id";
$result = Database::search($query);
$employee = $result->fetch_assoc();

// Check if payment already recorded for this month
$start_date = date('Y-m-01', strtotime($month));
$query = "SELECT * FROM salary_payments 
          WHERE employee_id = $employee_id 
          AND payment_month = '$start_date'";
$result = Database::search($query);
$existing_payment = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $regular_amount = (float)$_POST['regular_amount'];
    $ot_amount = (float)$_POST['ot_amount'];
    $total_amount = $regular_amount + $ot_amount;
    $payment_date = validateInput($_POST['payment_date']);
    $notes = validateInput($_POST['notes']);

    try {
        Database::connection();
        Database::$connection->begin_transaction();

        if ($existing_payment) {
            // Update existing payment
            $query = "UPDATE salary_payments 
                     SET regular_amount = $regular_amount,
                         ot_amount = $ot_amount,
                         total_amount = $total_amount,
                         payment_date = '$payment_date',
                         payment_status = 'paid',
                         notes = '$notes'
                     WHERE id = {$existing_payment['id']}";
        } else {
            // Insert new payment
            $query = "INSERT INTO salary_payments (
                        employee_id, payment_month, regular_amount, 
                        ot_amount, total_amount, payment_date, 
                        payment_status, notes
                    ) VALUES (
                        $employee_id, '$start_date', $regular_amount,
                        $ot_amount, $total_amount, '$payment_date',
                        'paid', '$notes'
                    )";
        }
        
        Database::iud($query);
        Database::$connection->commit();
        
        $_SESSION['success'] = "Salary payment recorded successfully.";
        header("Location: view_employee.php?id=$employee_id");
        exit();

    } catch (Exception $e) {
        Database::$connection->rollback();
        $error = "Error recording payment: " . $e->getMessage();
    }
}

// Calculate salary details for the month
$start_date = date('Y-m-01', strtotime($month));
$end_date = date('Y-m-t', strtotime($month));

// Get attendance records with calculated amounts
$query = "SELECT *,
          CASE 
            WHEN status = 'present' THEN 
                CASE 
                    WHEN working_hours >= 10 THEN {$employee['day_rate']}
                    ELSE (working_hours / 10) * {$employee['day_rate']}
                END
            WHEN status = 'half-day' THEN {$employee['day_rate']} / 2
            ELSE 0 
          END as day_amount,
          COALESCE(ot_hours * {$employee['overtime_rate']}, 0) as ot_amount
          FROM employee_attendance 
          WHERE employee_id = $employee_id 
          AND attendance_date BETWEEN '$start_date' AND '$end_date'
          ORDER BY attendance_date";
$attendance_result = Database::search($query);

// Calculate totals
$total_days = 0;
$total_half_days = 0;
$total_absents = 0;
$total_leaves = 0;
$total_working_hours = 0;
$total_ot_hours = 0;
$regular_amount = 0;
$ot_amount = 0;

while ($row = $attendance_result->fetch_assoc()) {
    switch ($row['status']) {
        case 'present':
            $total_days++;
            break;
        case 'half-day':
            $total_half_days++;
            break;
        case 'absent':
            $total_absents++;
            break;
        case 'leave':
            $total_leaves++;
            break;
    }
    $total_working_hours += $row['working_hours'];
    $total_ot_hours += $row['ot_hours'];
    $regular_amount += $row['day_amount'];
    $ot_amount += $row['ot_amount'];
}

$total_amount = $regular_amount + $ot_amount;

include 'header.php';
?>

<div class="container content">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h3>Record Salary Payment</h3>
                            <p class="mb-0">Employee: <?php echo $employee['name']; ?></p>
                            <p class="mb-0">Month: <?php echo date('F Y', strtotime($month)); ?></p>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <h5>Salary Summary</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1">Full Days: <?php echo $total_days; ?></p>
                                    <p class="mb-1">Half Days: <?php echo $total_half_days; ?></p>
                                    <p class="mb-1">Absents: <?php echo $total_absents; ?></p>
                                    <p class="mb-1">Leaves: <?php echo $total_leaves; ?></p>
                                    <p class="mb-1">Total Working Hours: <?php echo number_format($total_working_hours, 2); ?></p>
                                    <p class="mb-1">Total OT Hours: <?php echo formatOTHours($total_ot_hours); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1">Day Rate: <?php echo formatCurrency($employee['day_rate']); ?></p>
                                    <p class="mb-1">OT Rate: <?php echo formatCurrency($employee['overtime_rate']); ?> per hour</p>
                                    <p class="mb-1">Regular Amount: <?php echo formatCurrency($regular_amount); ?></p>
                                    <p class="mb-1">OT Amount: <?php echo formatCurrency($ot_amount); ?></p>
                                    <h5 class="mt-2">Total Amount: <?php echo formatCurrency($total_amount); ?></h5>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form method="POST" id="payment-form">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="required">Regular Amount</label>
                                    <input type="number" name="regular_amount" class="form-control" required 
                                           step="0.01" min="0" value="<?php echo $regular_amount; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="required">OT Amount</label>
                                    <input type="number" name="ot_amount" class="form-control" required 
                                           step="0.01" min="0" value="<?php echo $ot_amount; ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label class="required">Payment Date</label>
                            <input type="date" name="payment_date" class="form-control" required 
                                   value="<?php echo date('Y-m-d'); ?>"
                                   max="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <div class="form-group mb-3">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control" rows="3"><?php echo $existing_payment['notes'] ?? ''; ?></textarea>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Record Payment</button>
                            <a href="view_employee.php?id=<?php echo $employee_id; ?>" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
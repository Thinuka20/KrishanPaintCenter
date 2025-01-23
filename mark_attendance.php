<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();

$today = date('Y-m-d');

// At the beginning of mark_attendance.php after checkLogin();
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$attendance = null;

if ($edit_id) {
    $query = "SELECT ea.*, e.name as employee_name, e.day_rate, e.overtime_rate 
              FROM employee_attendance ea 
              LEFT JOIN employees e ON ea.employee_id = e.id 
              WHERE ea.id = $edit_id";
    $result = Database::search($query);
    $attendance = $result->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = (int)$_POST['employee_id'];
    $attendance_date = validateInput($_POST['attendance_date']);
    $time_in = validateInput($_POST['time_in']);
    $time_out = validateInput($_POST['time_out']);
    $status = validateInput($_POST['status']);
    $notes = validateInput($_POST['notes']);
    $edit_id = isset($_POST['edit_id']) ? (int)$_POST['edit_id'] : 0;

    // Get employee rates
    $query = "SELECT day_rate, overtime_rate FROM employees WHERE id = $employee_id";
    $result = Database::search($query);
    $employee = $result->fetch_assoc();
    $day_rate = $employee['day_rate'];
    $overtime_rate = $employee['overtime_rate'];

    $day_start = strtotime('08:00:00'); // 8:00 AM
    $day_end = strtotime('18:00:00');   // 6:00 PM
    $standard_hours = 10; // Standard working hours

    $working_hours = 0;
    $ot_hours = 0;
    $day_amount = 0;
    $ot_amount = 0;

    if ($time_in && $time_out) {
        $time_in_timestamp = strtotime($time_in);
        $time_out_timestamp = strtotime($time_out);

        // Calculate effective working hours
        $effective_start = max($time_in_timestamp, $day_start);
        $effective_end = min($time_out_timestamp, $day_end);

        if ($effective_start < $effective_end) {
            $daytime_minutes = ($effective_end - $effective_start) / 60;
            $working_hours = round($daytime_minutes / 60, 2);
        }

        // Calculate total worked time
        $total_minutes = ($time_out_timestamp - $time_in_timestamp) / 60;
        $total_hours = $total_minutes / 60;

        // Calculate salary based on status and hours
        switch ($status) {
            case 'present':
            case 'half-day':
                if ($working_hours >= $standard_hours) {
                    $day_amount = $day_rate; // Full day rate
                } else {
                    $day_amount = ($day_rate / $standard_hours) * $working_hours;
                }

                // Calculate OT if total hours exceed standard hours
                if ($total_hours > $standard_hours) {
                    $ot_hours = round($total_hours - $standard_hours, 2);
                    $ot_amount = $ot_hours * $overtime_rate;
                }
                break;

            case 'absent':
            case 'leave':
                $day_amount = 0;
                $ot_hours = 0;
                $ot_amount = 0;
                break;
        }
    }

    try {
        if ($edit_id) {
            $query = "UPDATE employee_attendance 
                     SET time_in = '$time_in',
                         time_out = '$time_out',
                         working_hours = '$working_hours',
                         ot_hours = $ot_hours,
                         day_amount = $day_amount,
                         ot_amount = $ot_amount,
                         status = '$status',
                         notes = '$notes'
                     WHERE id = $edit_id";
        } else {
            $query = "INSERT INTO employee_attendance 
                     (employee_id, attendance_date, time_in, time_out, working_hours, ot_hours, 
                      day_amount, ot_amount, status, notes)
                     VALUES 
                     ($employee_id, '$attendance_date', '$time_in', '$time_out', '$working_hours', 
                      $ot_hours, $day_amount, $ot_amount, '$status', '$notes')";
        }

        Database::iud($query);
        $_SESSION['success'] = $edit_id ? "Attendance updated successfully." : "Attendance marked successfully.";
        header("Location: attendance.php");
        exit();
    } catch (Exception $e) {
        $error = "Error " . ($edit_id ? "updating" : "marking") . " attendance: " . $e->getMessage();
    }
}

include 'header.php';
?>

<div class="container content">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Mark Attendance</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="attendance.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Attendance
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" id="attendance-form" onsubmit="return validateAttendance()">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="required">Employee</label>
                            <select name="employee_id" class="form-control select2" required
                                <?php echo $edit_id ? 'disabled' : ''; ?>>
                                <option value="">Select Employee</option>
                                <?php
                                $query = "SELECT * FROM employees ORDER BY name";
                                $result = Database::search($query);
                                while ($row = $result->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $row['id']; ?>"
                                        <?php echo ($attendance && $attendance['employee_id'] == $row['id']) ? 'selected' : ''; ?>>
                                        <?php echo $row['name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <?php if ($edit_id): ?>
                                <input type="hidden" name="employee_id" value="<?php echo $attendance['employee_id']; ?>">
                            <?php endif; ?>
                        </div>

                        <div class="form-group mb-3">
                            <label class="required">Date</label>
                            <input type="date" name="attendance_date" class="form-control"
                                required max="<?php echo $today; ?>"
                                value="<?php echo $attendance ? $attendance['attendance_date'] : $today; ?>"
                                <?php echo $edit_id ? 'readonly' : ''; ?>>
                        </div>

                        <div class="form-group mb-3">
                            <label class="required">Status</label>
                            <select name="status" class="form-control" required onchange="toggleTimeInputs(this.value)">
                                <option value="present" <?php echo ($attendance && $attendance['status'] == 'present') ? 'selected' : ''; ?>>Present</option>
                                <option value="absent" <?php echo ($attendance && $attendance['status'] == 'absent') ? 'selected' : ''; ?>>Absent</option>
                                <option value="half-day" <?php echo ($attendance && $attendance['status'] == 'half-day') ? 'selected' : ''; ?>>Half Day</option>
                                <option value="leave" <?php echo ($attendance && $attendance['status'] == 'leave') ? 'selected' : ''; ?>>Leave</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="time-inputs">
                            <div class="form-group mb-3">
                                <label class="required">Time In</label>
                                <input type="time" name="time_in" class="form-control"
                                    value="<?php echo $attendance ? date('H:i', strtotime($attendance['time_in'])) : ''; ?>">
                            </div>

                            <div class="form-group mb-3">
                                <label class="required">Time Out</label>
                                <input type="time" name="time_out" class="form-control"
                                    value="<?php echo $attendance ? date('H:i', strtotime($attendance['time_out'])) : ''; ?>">
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control" rows="3"><?php echo $attendance ? $attendance['notes'] : ''; ?></textarea>
                        </div>
                        <?php if ($edit_id): ?>
                            <input type="hidden" name="edit_id" value="<?php echo $edit_id; ?>">
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Save Attendance</button>
                    <button type="reset" class="btn btn-secondary">Reset</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function toggleTimeInputs(status) {
        const timeInputs = document.querySelector('.time-inputs');
        const inputs = timeInputs.querySelectorAll('input');

        if (status === 'present' || status === 'half-day') {
            timeInputs.style.display = 'block';
            inputs.forEach(input => input.required = true);
        } else {
            timeInputs.style.display = 'none';
            inputs.forEach(input => {
                input.required = false;
                input.value = '';
            });
        }
    }

    function validateAttendance() {
        const form = document.getElementById('attendance-form');
        const timeIn = form.time_in.value;
        const timeOut = form.time_out.value;
        const status = form.status.value;

        if ((status === 'present' || status === 'half-day') && timeIn && timeOut) {
            if (timeOut <= timeIn) {
                alert('Time Out must be after Time In');
                return false;
            }
        }

        return true;
    }

    // Initialize Select2
    $(document).ready(function() {
        $('.select2').select2();
    });
</script>

<?php include 'footer.php'; ?>
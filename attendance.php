<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();

$current_date = date('Y-m-d');
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$employee_id = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0;

include 'header.php';
?>

<div class="container content">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Attendance Management</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="mark_attendance.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Mark Attendance
            </a>
            <a href="preview_attendance.php?<?php echo $_SERVER['QUERY_STRING']; ?>" class="btn btn-success">
                <i class="fas fa-file-alt"></i> Preview Report
            </a>
            <a href="employees.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Employee Management
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Employee</label>
                        <select name="employee_id" class="form-control select2">
                            <option value="">All Employees</option>
                            <?php
                            $query = "SELECT * FROM employees ORDER BY name";
                            $result = Database::search($query);
                            while ($row = $result->fetch_assoc()):
                            ?>
                                <option value="<?php echo $row['id']; ?>"
                                    <?php echo $employee_id == $row['id'] ? 'selected' : ''; ?>>
                                    <?php echo $row['name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-7">
                    <div class="form-group">
                        <label>Date Range</label>
                        <div class="input-group">
                            <input type="date" name="start_date" class="form-control"
                                value="<?php echo $start_date; ?>">
                            <span class="input-group-text">to</span>
                            <input type="date" name="end_date" class="form-control"
                                value="<?php echo $end_date; ?>">
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary mt-4">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Attendance Records -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Employee</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>OT Hours</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $where = "WHERE attendance_date BETWEEN '$start_date' AND '$end_date'";
                        if ($employee_id) {
                            $where .= " AND ea.employee_id = $employee_id";
                        }

                        $query = "SELECT ea.*, e.name as employee_name 
                                 FROM employee_attendance ea 
                                 LEFT JOIN employees e ON ea.employee_id = e.id 
                                 $where 
                                 ORDER BY attendance_date DESC, e.name";
                        $result = Database::search($query);
                        while ($row = $result->fetch_assoc()):
                        ?>
                            <tr>
                                <td><?php echo date('Y-m-d', strtotime($row['attendance_date'])); ?></td>
                                <td><?php echo $row['employee_name']; ?></td>
                                <td><?php echo $row['time_in'] ? date('H:i', strtotime($row['time_in'])) : '-'; ?></td>
                                <td><?php echo $row['time_out'] ? date('H:i', strtotime($row['time_out'])) : '-'; ?></td>
                                <td><?php echo $row['ot_hours'] > 0 ? formatOTHours($row['ot_hours']): '-'; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo
                                                            $row['status'] === 'present' ? 'success' : ($row['status'] === 'absent' ? 'danger' : ($row['status'] === 'half-day' ? 'warning' : 'info')); ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($row['attendance_date'] === $current_date): ?>
                                        <a href="mark_attendance.php?edit=<?php echo $row['id']; ?>"
                                            class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($row['notes']): ?>
                                        <button type="button" class="btn btn-sm btn-info"
                                            data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($row['notes']); ?>">
                                            <i class="fas fa-sticky-note"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function exportAttendance() {
        // Create export URL with current filters
        const params = new URLSearchParams(window.location.search);
        const url = 'export_attendance.php?' + params.toString();
        window.location.href = url;
    }

    $(document).ready(function() {
        $('.select2').select2();
        $('[data-bs-toggle="tooltip"]').tooltip();
    });
</script>

<?php include 'footer.php'; ?>
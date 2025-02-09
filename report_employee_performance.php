<?php
// report_employee_performance.php
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
$employee_id = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : null;

// Check if PDF export is requested
if (isset($_GET['export_pdf'])) {
    // Initialize arrays for data collection
    $data = array();
    $totals = array(
        'working_hours' => 0,
        'ot_hours' => 0,
        'regular_amount' => 0,
        'ot_amount' => 0,
        'total_amount' => 0,
        'days_worked' => 0
    );

    if ($employee_id) {
        // Get employee attendance data
        $query = "SELECT 
                    ea.*,
                    e.name as employee_name,
                    e.phone as employee_phone,
                    e.day_rate,
                    e.overtime_rate
                  FROM employee_attendance ea
                  JOIN employees e ON ea.employee_id = e.id 
                  WHERE ea.employee_id = $employee_id 
                  AND ea.attendance_date BETWEEN '$start_date' AND '$end_date'
                  ORDER BY ea.attendance_date";

        $result = Database::search($query);

        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
            $totals['working_hours'] += $row['working_hours'];
            $totals['ot_hours'] += $row['ot_hours'];
            $totals['regular_amount'] += $row['day_amount'];
            $totals['ot_amount'] += $row['ot_amount'];
            if ($row['status'] == 'present') {
                $totals['days_worked']++;
            } elseif ($row['status'] == 'half-day') {
                $totals['days_worked'] += 0.5;
            }
        }

        $totals['total_amount'] = $totals['regular_amount'] + $totals['ot_amount'];

        // Generate PDF
        $pdf = new ReportPDF('L', 'Employee Performance Report');
        $pdf->generateEmployeePerformanceReport(
            $data,
            $totals,
            date('Y-m-d', strtotime($start_date)) . ' to ' . date('Y-m-d', strtotime($end_date))
        );
        $pdf->Output('Employee_Performance_Report_' . date('Y-m-d') . '.pdf', 'I');
        exit;
    }
}

// Get all employees for dropdown
$query = "SELECT id, name FROM employees ORDER BY name";
$employees_result = Database::search($query);

include 'header.php';
?>

<div class="container content">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Employee Performance Report</h2>
        </div>
        <div class="col-md-6 text-end">
            <?php if ($employee_id): ?>
                <a href="?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&employee_id=<?php echo $employee_id; ?>&export_pdf=1"
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
                        <label>Employee</label>
                        <select class="form-control select2" name="employee_id" required>
                            <option value="">Select Employee</option>
                            <?php while ($emp = $employees_result->fetch_assoc()): ?>
                                <option value="<?php echo $emp['id']; ?>"
                                    <?php echo ($employee_id == $emp['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($emp['name']); ?>
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
                    <button type="submit" class="btn btn-primary ">Generate</button>
                </div>
            </form>

            <?php if ($employee_id):
                // Get performance data
                $query = "SELECT 
                            ea.*,
                            e.name as employee_name,
                            e.phone as employee_phone,
                            e.day_rate,
                            e.overtime_rate
                         FROM employee_attendance ea
                         JOIN employees e ON ea.employee_id = e.id 
                         WHERE ea.employee_id = $employee_id 
                         AND ea.attendance_date BETWEEN '$start_date' AND '$end_date'
                         ORDER BY ea.attendance_date";

                $result = Database::search($query);
                $performance_data = [];
                $totals = [
                    'working_hours' => 0,
                    'ot_hours' => 0,
                    'regular_amount' => 0,
                    'ot_amount' => 0,
                    'days_worked' => 0
                ];

                while ($row = $result->fetch_assoc()) {
                    $performance_data[] = $row;
                    $totals['working_hours'] += $row['working_hours'];
                    $totals['ot_hours'] += $row['ot_hours'];
                    $totals['regular_amount'] += $row['day_amount'];
                    $totals['ot_amount'] += $row['ot_amount'];
                    if ($row['status'] == 'present') {
                        $totals['days_worked']++;
                    } elseif ($row['status'] == 'half-day') {
                        $totals['days_worked'] += 0.5;
                    }
                }

                if (!empty($performance_data)):
                    $employee = $performance_data[0];
            ?>
                    <!-- Performance Overview -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Days Worked</h5>
                                    <h3 class="mb-0"><?php echo number_format($totals['days_worked'], 1); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Hours</h5>
                                    <h3 class="mb-0"><?php echo formatOTHours($totals['working_hours'] + $totals['ot_hours'], 2); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h5 class="card-title">OT Hours</h5>
                                    <h3 class="mb-0"><?php echo formatOTHours($totals['ot_hours'], 2); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Earnings</h5>
                                    <h3 class="mb-0"><?php echo formatCurrency($totals['regular_amount'] + $totals['ot_amount']); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Performance Charts -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <canvas id="workingHoursChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <canvas id="earningsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detailed Performance Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Working Hours</th>
                                    <th>OT Hours</th>
                                    <th class="text-end">Regular Amount</th>
                                    <th class="text-end">OT Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($performance_data as $record): ?>
                                    <tr>
                                        <td><?php echo date('Y-m-d', strtotime($record['attendance_date'])); ?></td>
                                        <td><?php echo ucfirst($record['status']); ?></td>
                                        <td><?php echo $record['time_in']; ?></td>
                                        <td><?php echo $record['time_out']; ?></td>
                                        <td><?php echo formatOTHours($record['working_hours'], 2); ?></td>
                                        <td><?php echo formatOTHours($record['ot_hours'], 2); ?></td>
                                        <td class="text-end"><?php echo formatCurrency($record['day_amount']); ?></td>
                                        <td class="text-end"><?php echo formatCurrency($record['ot_amount']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td colspan="4">Totals</td>
                                    <td><?php echo number_format($totals['working_hours'], 2); ?></td>
                                    <td><?php echo number_format($totals['ot_hours'], 2); ?></td>
                                    <td class="text-end"><?php echo formatCurrency($totals['regular_amount']); ?></td>
                                    <td class="text-end"><?php echo formatCurrency($totals['ot_amount']); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                    <script>
                        const dates = <?php echo json_encode(array_map(function ($record) {
                                            return date('Y-m-d', strtotime($record['attendance_date']));
                                        }, $performance_data)); ?>;

                        const workingHours = <?php echo json_encode(array_map(function ($record) {
                                                    return $record['working_hours'];
                                                }, $performance_data)); ?>;

                        const otHours = <?php echo json_encode(array_map(function ($record) {
                                            return $record['ot_hours'];
                                        }, $performance_data)); ?>;

                        const regularAmounts = <?php echo json_encode(array_map(function ($record) {
                                                    return $record['day_amount'];
                                                }, $performance_data)); ?>;

                        const otAmounts = <?php echo json_encode(array_map(function ($record) {
                                                return $record['ot_amount'];
                                            }, $performance_data)); ?>;

                        // Working Hours Chart
                        new Chart(document.getElementById('workingHoursChart'), {
                            type: 'line',
                            data: {
                                labels: dates,
                                datasets: [{
                                    label: 'Regular Hours',
                                    data: workingHours,
                                    borderColor: 'rgb(75, 192, 192)',
                                    tension: 0.1
                                }, {
                                    label: 'OT Hours',
                                    data: otHours,
                                    borderColor: 'rgb(255, 99, 132)',
                                    tension: 0.1
                                }]
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    title: {
                                        display: true,
                                        text: 'Daily Working Hours'
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true
                                    }
                                }
                            }
                        });

                        // Earnings Chart
                        new Chart(document.getElementById('earningsChart'), {
                            type: 'bar',
                            data: {
                                labels: dates,
                                datasets: [{
                                    label: 'Regular Earnings',
                                    data: regularAmounts,
                                    backgroundColor: 'rgb(75, 192, 192)',
                                    stack: 'Stack 0'
                                }, {
                                    label: 'OT Earnings',
                                    data: otAmounts,
                                    backgroundColor: 'rgb(255, 99, 132)',
                                    stack: 'Stack 0'
                                }]
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    title: {
                                        display: true,
                                        text: 'Daily Earnings'
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            callback: function(value) {
                                                return 'Rs. ' + value.toLocaleString();
                                            }
                                        }
                                    }
                                }
                            }
                        });

                        // Initialize Select2
                        $(document).ready(function() {
                            $('.select2').select2({
                                theme: 'bootstrap4',
                                width: '100%'
                            });
                        });
                    </script>

                <?php else: ?>
                    <div class="alert alert-info">
                        No performance data found for the selected period.
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
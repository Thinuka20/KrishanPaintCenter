<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();

include 'header.php';
?>

<div class="container content">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Initial Estimates</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="view_estimates.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Estimate
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
        <form method="GET" class="row mb-3">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Estimate Number</label>
                    <input type="text" name="estimate_number" class="form-control" 
                        placeholder="Search by estimate number"
                        value="<?php echo isset($_GET['estimate_number']) ? htmlspecialchars($_GET['estimate_number']) : ''; ?>">
                </div>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary mt-4">
                    <i class="fas fa-search"></i> Search
                </button>
            </div>
        </form>

            <div class="table-responsive">
                <table class="table table-striped datatable">
                    <thead>
                        <tr>
                            <th>Estimate #</th>
                            <th>Vehicle</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $where = "WHERE 1=1";
                        if (isset($_GET['estimate_number']) && !empty($_GET['estimate_number'])) {
                            $estimate_number = $_GET['estimate_number'];
                            $where .= " AND e.estimate_number LIKE '%$estimate_number%'";
                        }

                        $query = "SELECT e.*, v.registration_number, c.name as customer_name,
                                        DATEDIFF(CURRENT_DATE, e.estimate_date) as days_pending
                                 FROM estimates e 
                                 LEFT JOIN vehicles v ON e.vehicle_id = v.id 
                                 LEFT JOIN customers c ON v.customer_id = c.id 
                                 $where 
                                 ORDER BY e.estimate_date DESC";

                        $result = Database::search($query);
                        while ($row = $result->fetch_assoc()):
                        ?>
                            <tr>
                                <td><?php echo $row['estimate_number']; ?></td>
                                <td><?php echo $row['registration_number']; ?></td>
                                <td><?php echo $row['customer_name']; ?></td>
                                <td><?php echo date('Y-m-d', strtotime($row['estimate_date'])); ?></td>
                                <td><?php echo formatCurrency($row['total_amount']); ?></td>
                                <td class="action-buttons">
                                    <a href="view_repair_estimate.php?id=<?php echo $row['id']; ?>"
                                        class="btn btn-sm btn-info">
                                        <i class="fas fa-eye text-light"></i>
                                    </a>
                                    <a href="print_repair_estimate.php?id=<?php echo $row['id']; ?>"
                                        class="btn btn-sm btn-secondary" target="_blank">
                                        <i class="fas fa-print text-light"></i>
                                    </a>
                                    <a href="edit_estimate.php?id=<?php echo $row['id']; ?>"
                                        class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit text-light"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <?php
                            $summary_query = "SELECT 
                            COUNT(*) as total_estimates,
                            SUM(total_amount) as total_amount
                        FROM estimates";
                            $summary_result = Database::search($summary_query);
                            $summary = $summary_result->fetch_assoc();
                            ?>
                            <h5>Summary</h5>
                            <p class="mb-1">Total Estimates: <?php echo $summary['total_estimates']; ?></p>
                            <p class="mb-1">Total Amount: <?php echo formatCurrency($summary['total_amount']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        $('#dateRangePicker').daterangepicker({
            autoUpdateInput: false,
            opens: 'left',
            showDropdowns: true,
            locale: {
                format: 'YYYY-MM-DD',
                separator: ' - ',
                applyLabel: 'Apply',
                cancelLabel: 'Clear'
            },
            ranges: {
                'Today': [moment(), moment()],
                'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last Month': [moment().subtract(1, 'month').startOf('month'),
                    moment().subtract(1, 'month').endOf('month')
                ]
            }
        });

        $('#dateRangePicker').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
        });

        $('#dateRangePicker').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
        });

        const urlParams = new URLSearchParams(window.location.search);
        const dateRange = urlParams.get('date_range');
        if (dateRange) {
            $('#dateRangePicker').val(dateRange);
            const dates = dateRange.split(' - ');
            if (dates.length === 2) {
                $('#dateRangePicker').data('daterangepicker').setStartDate(dates[0]);
                $('#dateRangePicker').data('daterangepicker').setEndDate(dates[1]);
            }
        }
    });
</script>

<?php include 'footer.php'; ?>
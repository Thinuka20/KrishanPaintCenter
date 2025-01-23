<?php
// invoices.php - Manage all invoices
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();

$invoice_type = isset($_GET['type']) ? $_GET['type'] : 'repair';

include 'header.php';
?>

<div class="container content">
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Invoices</h2>
        </div>
        <div class="col-md-6 text-end">
            <?php if ($invoice_type === 'repair'): ?>
                <a href="add_repair_invoice.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> New Repair Invoice
                </a>
            <?php else: ?>
                <a href="add_item_invoice.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> New Item Invoice
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Invoice Type Selector -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?php echo $invoice_type === 'repair' ? 'active' : ''; ?>"
                href="?type=repair">
                Repair Invoices
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $invoice_type === 'item' ? 'active' : ''; ?>"
                href="?type=item">
                Item Invoices
            </a>
        </li>
    </ul>

    <div class="card">
        <div class="card-body">
            <!-- Date Range Filter -->
            <form method="GET" class="row mb-3">
                <input type="hidden" name="type" value="<?php echo $invoice_type; ?>">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Date Range</label>
                        <input type="text" name="date_range" id="dateRangePicker" class="form-control"
                            placeholder="Select date range"
                            value="<?php echo isset($_GET['date_range']) ? htmlspecialchars($_GET['date_range']) : ''; ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo isset($_GET['status']) && $_GET['status'] === 'pending' ? 'selected' : ''; ?>>
                                Pending
                            </option>
                            <option value="partial" <?php echo isset($_GET['status']) && $_GET['status'] === 'partial' ? 'selected' : ''; ?>>
                                Partial
                            </option>
                            <option value="paid" <?php echo isset($_GET['status']) && $_GET['status'] === 'paid' ? 'selected' : ''; ?>>
                                Paid
                            </option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary mt-4">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped datatable">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <?php if ($invoice_type === 'repair'): ?>
                                <th>Vehicle</th>
                            <?php endif; ?>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Build WHERE clause for filters
                        $where = "WHERE 1=1";
                        if (isset($_GET['date_range']) && !empty($_GET['date_range'])) {
                            $dates = explode(' - ', $_GET['date_range']);
                            $start_date = date('Y-m-d', strtotime($dates[0]));
                            $end_date = date('Y-m-d', strtotime($dates[1]));
                            $where .= " AND invoice_date BETWEEN '$start_date' AND '$end_date'";
                        }
                        if (isset($_GET['status']) && !empty($_GET['status'])) {
                            $status = validateInput($_GET['status']);
                            $where .= " AND payment_status = '$status'";
                        }

                        if ($invoice_type === 'repair') {
                            $query = "SELECT ri.*, v.registration_number, c.name as customer_name 
                                     FROM repair_invoices ri 
                                     LEFT JOIN vehicles v ON ri.vehicle_id = v.id 
                                     LEFT JOIN customers c ON v.customer_id = c.id 
                                     $where 
                                     ORDER BY ri.invoice_date DESC";
                        } else {
                            $query = "SELECT ii.*, c.name as customer_name 
                                     FROM item_invoices ii 
                                     LEFT JOIN customers c ON ii.customer_id = c.id 
                                     $where 
                                     ORDER BY ii.invoice_date DESC";
                        }

                        $result = Database::search($query);
                        while ($row = $result->fetch_assoc()):
                        ?>
                            <tr>
                                <td><?php echo $row['invoice_number']; ?></td>
                                <?php if ($invoice_type === 'repair'): ?>
                                    <td><?php echo $row['registration_number']; ?></td>
                                <?php endif; ?>
                                <td><?php echo $row['customer_name']; ?></td>
                                <td><?php echo date('Y-m-d', strtotime($row['invoice_date'])); ?></td>
                                <td><?php echo formatCurrency($row['total_amount']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $row['payment_status'] === 'paid' ? 'success' : ($row['payment_status'] === 'partial' ? 'warning' : 'danger'); ?>">
                                        <?php echo ucfirst($row['payment_status']); ?>
                                    </span>
                                </td>
                                <td class="action-buttons">
                                    <a href="view_<?php echo $invoice_type; ?>_invoice.php?id=<?php echo $row['id']; ?>"
                                        class="btn btn-sm btn-info">
                                        <i class="fas fa-eye text-light"></i>
                                    </a>
                                    <a href="print_<?php echo $invoice_type; ?>_invoice.php?id=<?php echo $row['id']; ?>"
                                        class="btn btn-sm btn-secondary" target="_blank">
                                        <i class="fas fa-print text-light"></i>
                                    </a>
                                    <?php if ($row['payment_status'] !== 'paid'): ?>
                                        <a href="edit_<?php echo $invoice_type; ?>_invoice.php?id=<?php echo $row['id']; ?>"
                                            class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit text-light"></i>
                                        </a>
                                        <a href="update_payment_status.php?type=<?php echo $invoice_type; ?>&id=<?php echo $row['id']; ?>"
                                            class="btn btn-sm btn-success">
                                            <i class="fas fa-dollar-sign text-light"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="delete_<?php echo $invoice_type; ?>_invoice.php?id=<?php echo $row['id']; ?>"
                                        class="btn btn-sm btn-danger"
                                        onclick="return confirm('Are you sure you want to delete invoice <?php echo $row['invoice_number']; ?>?');">
                                        <i class="fas fa-trash text-light"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Summary Section -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <?php
                            // Calculate summary statistics
                            $query = "SELECT 
                            COUNT(*) as total_invoices,
                            SUM(total_amount) as total_amount,
                            SUM(CASE 
                                WHEN payment_status = 'pending' THEN total_amount 
                                WHEN payment_status = 'partial' THEN (
                                    total_amount - COALESCE((
                                        SELECT SUM(amount) 
                                        FROM payment_transactions 
                                        WHERE invoice_type = '" . $invoice_type . "' 
                                        AND invoice_id = " . ($invoice_type === 'repair' ? 'repair_invoices' : 'item_invoices') . ".id
                                    ), 0)
                                )
                                ELSE 0 
                            END) as pending_amount
                        FROM " . ($invoice_type === 'repair' ? 'repair_invoices' : 'item_invoices') . "
                        $where";
                            $result = Database::search($query);
                            $summary = $result->fetch_assoc();
                            ?>
                            <h5>Summary</h5>
                            <p class="mb-1">Total Invoices: <?php echo $summary['total_invoices']; ?></p>
                            <p class="mb-1">Total Amount: <?php echo formatCurrency($summary['total_amount']); ?></p>
                            <p class="mb-1">Pending Amount: <?php echo formatCurrency($summary['pending_amount']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize daterangepicker with dark theme options
        $('#dateRangePicker').daterangepicker({
            autoUpdateInput: false,
            opens: 'left',
            showDropdowns: true,
            locale: {
                format: 'YYYY-MM-DD',
                separator: ' - ',
                applyLabel: 'Apply',
                cancelLabel: 'Clear',
                fromLabel: 'From',
                toLabel: 'To',
                customRangeLabel: 'Custom Range',
                weekLabel: 'W',
                daysOfWeek: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
                monthNames: ['January', 'February', 'March', 'April', 'May', 'June',
                    'July', 'August', 'September', 'October', 'November', 'December'
                ],
                firstDay: 1
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

        // Handle apply event
        $('#dateRangePicker').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('YYYY-MM-DD') + ' - ' + picker.endDate.format('YYYY-MM-DD'));
        });

        // Handle cancel event
        $('#dateRangePicker').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
        });

        // Set initial value if exists in URL parameters
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
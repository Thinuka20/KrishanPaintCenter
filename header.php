<?php
// Get business settings
$query = "SELECT * FROM settings ORDER BY id DESC LIMIT 1";
$result = Database::search($query);
$business_settings = $result->fetch_assoc();
$current_theme = $business_settings['theme'] ?? 'light';
// Get current page name
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($business_settings['business_name'] ?? 'Krishan Paint Center'); ?></title>

    <?php if (!empty($business_settings['favicon'])): ?>
        <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($business_settings['favicon']); ?>">
    <?php endif; ?>

    <!-- Base CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/theme.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- jQuery and Core Dependencies -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!-- Date Range Picker Dependencies -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>

    <!-- Your custom scripts -->
    <script src="assets/js/main.js"></script>

    <style>
        /* Base Layout */
        body {
            min-height: 100vh;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        /* Mobile Navigation */
        .mobile-nav {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1040;
            padding: 0.75rem 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .mobile-nav .navbar-brand {
            display: flex;
            align-items: center;
        }

        .mobile-nav img {
            max-height: 40px;
            margin-right: 0.5rem;
        }

        .navbar-toggler {
            border: none;
            padding: 0.5rem;
            background: transparent;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 250px;
            display: flex;
            flex-direction: column;
            z-index: 1030;
            transition: transform 0.3s ease;
        }

        .sidebar-header {
            padding: 1rem;
            border-bottom: 1px solid;
        }

        .sidebar-content {
            flex: 1;
            overflow-y: auto;
            padding-top: 1rem;
        }

        /* Navigation Links */
        .nav-link {
            padding: 0.8rem 1rem;
            display: flex;
            align-items: center;
            text-decoration: none;
            transition: all 0.3s;
        }

        .nav-link i {
            width: 24px;
            margin-right: 0.5rem;
        }

        /* Employee Section */
        .employee-section .nav-link {
            padding-left: 2.5rem;
        }

        /* User Info Section */
        .user-info {
            padding: 1rem;
            border-top: 1px solid;
        }

        /* Content Area */
        .content-wrapper {
            margin-left: 250px;
            padding: 1.5rem;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }

        /* Overlay for Mobile */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1025;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        Theme Styles

        /* Light theme */
        .theme-light .sidebar,
        .theme-light .sidebar-header,
        .theme-light .sidebar-content,
        .theme-light .user-info,
        .theme-light .mobile-nav {
            background-color: var(--sidebar-bg);
        }

        .theme-light .nav-link {
            color: var(--sidebar-text);
        }

        .theme-light .nav-link:hover {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .theme-light .nav-link.active {
            background-color: rgba(255, 255, 255, 0.15);
            color: #fff;
        }

        .theme-light .mobile-nav .navbar-brand,
        .theme-light .navbar-toggler {
            color: var(--sidebar-text);
        }

        /* Dark theme */
        .theme-dark .sidebar,
        .theme-dark .sidebar-header,
        .theme-dark .sidebar-content,
        .theme-dark .user-info,
        .theme-dark .mobile-nav {
            background-color: var(--sidebar-bg);
        }

        .theme-dark .nav-link {
            color: var(--sidebar-text);
        }

        .theme-dark .nav-link:hover {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .theme-dark .nav-link.active {
            background-color: rgba(255, 255, 255, 0.15);
            color: #fff;
        }

        .theme-dark .mobile-nav .navbar-brand,
        .theme-dark .navbar-toggler {
            color: var(--sidebar-text);
        }

        /* Blue theme */
        .theme-blue .sidebar,
        .theme-blue .sidebar-header,
        .theme-blue .sidebar-content,
        .theme-blue .user-info,
        .theme-blue .mobile-nav {
            background-color: var(--sidebar-bg);
        }

        .theme-blue .nav-link {
            color: var(--sidebar-text);
        }

        .theme-blue .nav-link:hover {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.15);
        }

        .theme-blue .nav-link.active {
            background-color: rgba(255, 255, 255, 0.2);
            color: #fff;
        }

        .theme-blue .mobile-nav .navbar-brand,
        .theme-blue .navbar-toggler {
            color: var(--sidebar-text);
        }

        /* Green theme */
        .theme-green .sidebar,
        .theme-green .sidebar-header,
        .theme-green .sidebar-content,
        .theme-green .user-info,
        .theme-green .mobile-nav {
            background-color: var(--sidebar-bg);
        }

        .theme-green .nav-link {
            color: var(--sidebar-text);
        }

        .theme-green .nav-link:hover {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.15);
        }

        .theme-green .nav-link.active {
            background-color: rgba(255, 255, 255, 0.2);
            color: #fff;
        }

        .theme-green .mobile-nav .navbar-brand,
        .theme-green .navbar-toggler {
            color: var(--sidebar-text);
        }

        /* Mobile Responsiveness */
        @media (max-width: 991.98px) {
            .mobile-nav {
                display: flex;
                align-items: center;
                justify-content: space-between;
            }

            .sidebar {
                transform: translateX(-100%);
                box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            }

            .sidebar .sidebar-header {
                display: none;
            }

            .sidebar-content {
                padding-top: 75px;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .sidebar-overlay.active {
                display: block;
                opacity: 1;
            }

            .content-wrapper {
                margin-left: 0;
                padding-top: 70px;
            }

            .nav-link {
                padding: 1rem;
            }

            .user-info .nav-link {
                padding: 0.8rem 1rem;
            }
        }

        /* Common theme adjustments */
        .sidebar-header,
        .user-info {
            border-color: var(--border-dark);
        }

        /* Scrollbar adjustments */
        .sidebar-content::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar-content::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar-content::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
        }

        .theme-light .sidebar-content::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.2);
        }
    </style>
    <style>
        .theme-option {
            text-align: center;
        }

        .theme-preview {
            width: 120px;
            padding: 10px;
            cursor: pointer;
        }

        .theme-sample {
            width: 100%;
            height: 80px;
            border: 1px solid #ddd;
            border-radius: 4px;
            position: relative;
            overflow: hidden;
            margin-bottom: 5px;
        }

        .theme-sample-header {
            height: 15px;
            width: 100%;
        }

        .theme-sample-sidebar {
            position: absolute;
            left: 0;
            top: 15px;
            bottom: 0;
            width: 30%;
        }

        /* Theme specific styles */
        .theme-dark {
            background-color: #212529;
            color: #fff;
        }

        .theme-dark .card {
            background-color: #2c3034;
            border-color: #373b3e;
        }

        .theme-dark .form-control {
            background-color: #1a1d20;
            border-color: #373b3e;
            color: #fff;
        }
    </style>
</head>

<body class="theme-<?php echo htmlspecialchars($current_theme); ?>">
    <!-- Mobile Navigation -->
    <nav class="mobile-nav">
        <button class="navbar-toggler" type="button" id="sidebar-toggle">
            <i class="fas fa-bars"></i>
        </button>
        <a class="navbar-brand" href="index.php">
            <?php
            if ($current_theme == 'light') {
                if (!empty($business_settings['logo_light'])): ?>
                    <img src="<?php echo htmlspecialchars($business_settings['logo_light']); ?>"
                        alt="<?php echo htmlspecialchars($business_settings['business_name'] ?? ''); ?>"
                        height="60">
                <?php else: ?>
                    <?php echo htmlspecialchars($business_settings['business_name'] ?? ''); ?>
                <?php endif;
            } else {
                if (!empty($business_settings['logo_dark'])): ?>
                    <img src="<?php echo htmlspecialchars($business_settings['logo_dark']); ?>"
                        alt="<?php echo htmlspecialchars($business_settings['business_name'] ?? ''); ?>"
                        height="60">
                <?php else: ?>
                    <?php echo htmlspecialchars($business_settings['business_name'] ?? ''); ?>
            <?php endif;
            }
            ?>
        </a>
    </nav>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-header">
            <a class="navbar-brand text-white" href="index.php">
                <?php
                if ($current_theme == 'light') {
                    if (!empty($business_settings['logo_light'])): ?>
                        <img src="<?php echo htmlspecialchars($business_settings['logo_light']); ?>"
                            alt="Logo" height="110">
                    <?php else: ?>
                        <?php echo htmlspecialchars($business_settings['business_name'] ?? ''); ?>
                    <?php endif;
                } else {
                    if (!empty($business_settings['logo_dark'])): ?>
                        <img src="<?php echo htmlspecialchars($business_settings['logo_dark']); ?>"
                            alt="Logo" height="110">
                    <?php else: ?>
                        <?php echo htmlspecialchars($business_settings['business_name'] ?? ''); ?>
                <?php endif;
                }
                ?>
            </a>
        </div>

        <div class="sidebar-content">
            <?php
            if ($_SESSION['role'] == 'admin') {
            ?>
                <a class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>" href="index.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            <?php
            }
            ?>

            <a class="nav-link <?php echo $current_page === 'items.php' ? 'active' : ''; ?>" href="items.php">
                <i class="fas fa-box"></i> Items
            </a>

            <a class="nav-link <?php echo $current_page === 'customers.php' ? 'active' : ''; ?>" href="customers.php">
                <i class="fas fa-users"></i> Customers
            </a>

            <a class="nav-link <?php echo $current_page === 'estimates.php' | $current_page === 'initial_estimates.php' | $current_page === 'supplementary_estimates.php' | $current_page === 'spare_parts_estimates.php' ? 'active' : ''; ?>">
                <i class="fas fa-calculator"></i> Estimates
            </a>

            <!-- Estimate section -->
            <div class="employee-section">
                <a class="nav-link <?php echo $current_page === 'initial_estimates.php' ? 'active' : ''; ?>" href="initial_estimates.php">
                    <i class="fas fa-file-contract"></i> Initial Estimates
                </a>
                <a class="nav-link <?php echo $current_page === 'supplementary_estimates.php' ? 'active' : ''; ?>" href="supplementary_estimates.php">
                    <i class="fas fa-file-medical"></i> Supplementary Estimates
                </a>
                <a class="nav-link <?php echo $current_page === 'spare_parts_estimates.php' ? 'active' : ''; ?>" href="spare_parts_estimates.php">
                    <i class="fas fa-cogs"></i> Spare Parts Estimates
                </a>
            </div>

            <a class="nav-link <?php echo $current_page === 'invoices.php' | $current_page === 'invoices-item.php' | $current_page === 'invoices-repair.php' ? 'active' : ''; ?>">
                <i class="fas fa-file-invoice"></i> Invoices
            </a>

            <!-- Invoice section -->
            <div class="invoice-section" style="padding-left: 1.5rem;">
                <a class="nav-link <?php echo $current_page === 'invoices-item.php' ? 'active' : ''; ?>" href="invoices-item.php">
                    <i class="fas fa-box-open"></i> Item Invoices
                </a>
                <a class="nav-link <?php echo $current_page === 'invoices-repair.php' ? 'active' : ''; ?>" href="invoices-repair.php">
                    <i class="fas fa-tools"></i> Repair Invoices
                </a>
            </div>

            <?php
            if ($_SESSION['role'] == 'admin') {
            ?>
                <a class="nav-link <?php echo $current_page === 'employees.php' | $current_page === 'attendance.php' | $current_page === 'mark_attendance.php' | $current_page === 'salary_payments.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-tie"></i> Employees
                </a>

                <!-- Employee section -->
                <div class="employee-section">
                    <a class="nav-link <?php echo $current_page === 'employees.php' ? 'active' : ''; ?>" href="employees.php">
                        <i class="fas fa-users-cog"></i> Manage Employees
                    </a>
                    <a class="nav-link <?php echo $current_page === 'attendance.php' ? 'active' : ''; ?>" href="attendance.php">
                        <i class="fas fa-clipboard-list"></i> Attendance
                    </a>
                    <a class="nav-link <?php echo $current_page === 'mark_attendance.php' ? 'active' : ''; ?>" href="mark_attendance.php">
                        <i class="fas fa-user-check"></i> Mark Attendance
                    </a>
                    <a class="nav-link <?php echo $current_page === 'salary_payments.php' ? 'active' : ''; ?>" href="salary_payments.php">
                        <i class="fas fa-money-bill-wave"></i> Salary Payments
                    </a>
                </div>

                <a class="nav-link <?php echo $current_page === 'suppliers.php' ? 'active' : ''; ?>" href="suppliers.php">
                    <i class="fas fa-truck"></i> Suppliers
                </a>

                <a class="nav-link <?php echo $current_page === 'expenses.php' ? 'active' : ''; ?>" href="expenses.php">
                    <i class="fas fa-receipt"></i> Expenses
                </a>

                <a class="nav-link <?php echo $current_page === 'reports.php' ? 'active' : ''; ?>" href="reports.php">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
                <div class="mt-5"></div>

            <?php
            }
            ?>
        </div>

        <div class="user-info">
            <?php
            if ($_SESSION['role'] == 'admin') {
            ?>
                <a class="nav-link <?php echo $current_page === 'settings.php' ? 'active' : ''; ?>" href="settings.php">
                    <i class="fas fa-cog"></i> Settings
                </a>
            <?php
            }
            ?>
            <a class="nav-link" href="logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </nav>

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <div class="container-fluid py-3">
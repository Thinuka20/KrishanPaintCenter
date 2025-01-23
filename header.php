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

    <!-- Your custom scripts last -->
    <script src="assets/js/main.js"></script>
    <style>
        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 250px;
            background-color: #212529;
            padding-top: 1rem;
            transition: all 0.3s;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.8rem 1rem;
            display: flex;
            align-items: center;
            transition: all 0.3s;
        }

        .sidebar .nav-link:hover {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .sidebar .nav-link i {
            width: 24px;
            margin-right: 0.5rem;
        }

        .sidebar .nav-link.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: #fff;
        }

        .sidebar .dropdown-menu {
            background-color: #343a40;
            border: none;
            margin-left: 2.5rem;
        }

        .sidebar .dropdown-item {
            color: rgba(255, 255, 255, 0.8);
        }

        .sidebar .dropdown-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: #fff;
        }

        /* Main Content Wrapper */
        .content-wrapper {
            margin-left: 250px;
            transition: all 0.3s;
        }

        /* Mobile Navbar */
        .mobile-nav {
            display: none;
            background-color: #212529;
        }

        /* Responsive Design */
        @media (max-width: 991.98px) {
            .sidebar {
                margin-left: -250px;
            }

            .sidebar.active {
                margin-left: 0;
            }

            .content-wrapper {
                margin-left: 0;
            }

            .mobile-nav {
                display: block;
            }
        }

        /* User Info Section */
        .user-info {
            padding: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            position: absolute;
            bottom: 0;
            width: 100%;
            color: rgba(255, 255, 255, 0.8);
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

        .theme-blue .sidebar {
            background-color: #0d6efd;
        }

        .theme-green .sidebar {
            background-color: #198754;
        }
    </style>
</head>

<body class="theme-<?php echo htmlspecialchars($current_theme); ?>">
    <!-- Mobile Navbar -->
    <nav class="navbar navbar-dark bg-dark mobile-nav">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" id="sidebar-toggle">
                <span class="navbar-toggler-icon"></span>
            </button>
            <a class="navbar-brand" href="index.php">
                <?php
                if ($business_settings['theme'] == 'light') {
                    if (!empty($business_settings['logo_light'])): ?>
                        <img src="<?php echo htmlspecialchars($business_settings['logo_light']); ?>"
                            alt="<?php echo htmlspecialchars($business_settings['business_name'] ?? 'Krishan Paint Center'); ?>"
                            height="40" class="me-2">
                    <?php else: ?>
                        <?php echo htmlspecialchars($business_settings['business_name'] ?? 'Krishan Paint Center'); ?>
                    <?php endif;
                } else {
                    if (!empty($business_settings['logo_dark'])): ?>
                        <img src="<?php echo htmlspecialchars($business_settings['logo_dark']); ?>"
                            alt="<?php echo htmlspecialchars($business_settings['business_name'] ?? 'Krishan Paint Center'); ?>"
                            height="40" class="me-2">
                    <?php else: ?>
                        <?php echo htmlspecialchars($business_settings['business_name'] ?? 'Krishan Paint Center'); ?>
                <?php endif;
                }
                ?>
            </a>
        </div>
    </nav>

    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-header">
            <a class="navbar-brand text-white" href="index.php">
                <?php
                if ($business_settings['theme'] == 'light') {
                    if (!empty($business_settings['logo_light'])): ?>
                        <img src="<?php echo htmlspecialchars($business_settings['logo_light']); ?>"
                            alt="Logo" height="40" class="d-inline-block align-text-top">
                    <?php else: ?>
                        <?php echo htmlspecialchars($business_settings['business_name'] ?? 'Krishan Paint Center'); ?>
                    <?php endif;
                } else {
                    if (!empty($business_settings['logo_dark'])): ?>
                        <img src="<?php echo htmlspecialchars($business_settings['logo_dark']); ?>"
                            alt="Logo" height="40" class="d-inline-block align-text-top">
                    <?php else: ?>
                        <?php echo htmlspecialchars($business_settings['business_name'] ?? 'Krishan Paint Center'); ?>
                <?php endif;
                }
                ?>
            </a>
        </div>

        <div class="mt-3">
            <a class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>" href="index.php">
                <i class="fas fa-home"></i> Dashboard
            </a>

            <a class="nav-link <?php echo $current_page === 'items.php' ? 'active' : ''; ?>" href="items.php">
                <i class="fas fa-box"></i> Items
            </a>

            <a class="nav-link <?php echo $current_page === 'customers.php' ? 'active' : ''; ?>" href="customers.php">
                <i class="fas fa-users"></i> Customers
            </a>

            <a class="nav-link <?php echo $current_page === 'estimates.php' ? 'active' : ''; ?>" href="estimates.php">
                <i class="fas fa-calculator"></i> Estimates
            </a>

            <a class="nav-link <?php echo $current_page === 'invoices.php' ? 'active' : ''; ?>" href="invoices.php">
                <i class="fas fa-file-invoice"></i> Invoices
            </a>

            <div class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                    <i class="fas fa-user-tie"></i> Employees
                </a>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="employees.php">
                        <i class="fas fa-users-cog"></i> Manage Employees
                    </a>
                    <a class="dropdown-item" href="attendance.php">
                        <i class="fas fa-clipboard-list"></i> Attendance
                    </a>
                    <a class="dropdown-item" href="mark_attendance.php">
                        <i class="fas fa-user-check"></i> Mark Attendance
                    </a>
                    <a class="dropdown-item" href="salary_payments.php">
                        <i class="fas fa-money-bill-wave"></i> Salary Payments
                    </a>
                </div>
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
        </div>

        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="user-info">
                <?php
                $query = "SELECT role FROM users WHERE id = " . $_SESSION['user_id'];
                $result = Database::search($query);
                $current_user = $result->fetch_assoc();
                if ($current_user['role'] === 'admin'):
                ?>
                    <a class="nav-link <?php echo $current_page === 'settings.php' ? 'active' : ''; ?>" href="settings.php">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                <?php endif; ?>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        <?php endif; ?>
    </nav>

    <!-- Main Content Wrapper -->
    <div class="content-wrapper">
        <div class="container-fluid py-3">
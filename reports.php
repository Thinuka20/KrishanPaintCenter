<?php
// reports.php - Reports dashboard
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: unauthorized.php");
    exit();
}

include 'header.php';
?>

<div class="container content">
    <h2>Reports</h2>
    
    <div class="row mt-4">
        <!-- Sales Reports -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-body">
                    <h4>Sales Reports</h4>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="report_sales_daily.php" class="btn btn-primary w-100">
                                Daily Sales Report
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="report_sales_monthly.php" class="btn btn-primary w-100">
                                Monthly Sales Report
                            </a>
                        </li>
                        <li>
                            <a href="report_sales_items.php" class="btn btn-primary w-100">
                                Item-wise Sales Report
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Financial Reports -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-body">
                    <h4>Financial Reports</h4>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="report_income_statement.php" class="btn btn-success w-100">
                                Income Statement
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="report_expense_statement.php" class="btn btn-success w-100">
                                Expense Statement
                            </a>
                        </li>
                        <li>
                            <a href="report_profit_loss.php" class="btn btn-success w-100">
                                Profit & Loss Statement
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Inventory Reports -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-body">
                    <h4>Inventory Reports</h4>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="report_stock_status.php" class="btn btn-info w-100">
                                Stock Status Report
                            </a>
                        </li>
                        <li>
                            <a href="report_stock_movement.php" class="btn btn-info w-100">
                                Stock Movement History
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Employee Reports -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-body">
                    <h4>Employee Reports</h4>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="report_salary_statement.php" class="btn btn-warning w-100">
                                Salary Statement
                            </a>
                        </li>
                        <li>
                            <a href="report_employee_performance.php" class="btn btn-warning w-100">
                                Employee Performance
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Supplier Reports -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-body">
                    <h4>Supplier Reports</h4>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="report_supplier_balance.php" class="btn btn-secondary w-100">
                                Supplier Balance Statement
                            </a>
                        </li>
                        <li>
                            <a href="report_supplier_transactions.php" class="btn btn-secondary w-100">
                                Supplier Transaction History
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Customer Reports -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-body">
                    <h4>Customer Reports</h4>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="report_customer_history.php" class="btn btn-dark w-100">
                                Customer Service History
                            </a>
                        </li>
                        <li>
                            <a href="report_vehicle_history.php" class="btn btn-dark w-100">
                                Vehicle Service History
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
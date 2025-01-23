<?php
// report_stock_status.php - Stock status report
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
            <h2>Stock Status Report</h2>
        </div>
        <div class="col-md-6 text-end">
            <button onclick="window.print()" class="btn btn-secondary">
                <i class="fas fa-print"></i> Print Report
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-warning text-dark">
                        <div class="card-body">
                            <?php
                            $query = "SELECT COUNT(*) as count FROM items 
                                     WHERE stock_quantity <= minimum_stock";
                            $result = Database::search($query);
                            $low_stock = $result->fetch_assoc()['count'];
                            ?>
                            <h5>Low Stock Items</h5>
                            <h3><?php echo $low_stock; ?> items</h3>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <?php
                            $query = "SELECT COUNT(*) as count FROM items 
                                     WHERE stock_quantity > minimum_stock";
                            $result = Database::search($query);
                            $normal_stock = $result->fetch_assoc()['count'];
                            ?>
                            <h5>Normal Stock Items</h5>
                            <h3><?php echo $normal_stock; ?> items</h3>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <?php
                            $query = "SELECT COUNT(*) as count, 
                                     SUM(stock_quantity * unit_price) as value 
                                     FROM items";
                            $result = Database::search($query);
                            $total = $result->fetch_assoc();
                            ?>
                            <h5>Total Stock Value</h5>
                            <h3><?php echo formatCurrency($total['value']); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <ul class="nav nav-tabs" id="stockTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="all-tab" data-bs-toggle="tab" href="#all">
                                All Items
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="low-tab" data-bs-toggle="tab" href="#low">
                                Low Stock
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content mt-3">
                        <div class="tab-pane fade show active" id="all">
                            <div class="table-responsive">
                                <table class="table table-bordered datatable">
                                    <thead>
                                        <tr>
                                            <th>Code</th>
                                            <th>Item</th>
                                            <th>Current Stock</th>
                                            <th>Minimum Stock</th>
                                            <th>Unit Price</th>
                                            <th>Stock Value</th>
                                            <th>Status</th>
                                        </tr>
                                    


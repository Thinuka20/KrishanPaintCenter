<?php
// expenses.php - List all expenses
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
            <h2>Expenses</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="add_expense.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Expense
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Date Range</label>
                        <input type="text" class="form-control date-range-picker" id="date-range">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Category</label>
                        <select class="form-control" id="category-filter">
                            <option value="">All Categories</option>
                            <?php
                            $query = "SELECT DISTINCT category FROM expenses ORDER BY category";
                            $result = Database::search($query);
                            while ($row = $result->fetch_assoc()):
                            ?>
                            <option value="<?php echo $row['category']; ?>">
                                <?php echo $row['category']; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-primary mt-4" onclick="filterExpenses()">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <button type="button" class="btn btn-secondary mt-4" onclick="resetFilters()">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped datatable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT * FROM expenses ORDER BY expense_date DESC";
                        $result = Database::search($query);
                        while ($row = $result->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo date('Y-m-d', strtotime($row['expense_date'])); ?></td>
                            <td><?php echo $row['category']; ?></td>
                            <td><?php echo $row['description']; ?></td>
                            <td><?php echo formatCurrency($row['amount']); ?></td>
                            <td class="action-buttons">
                                <a href="edit_expense.php?id=<?php echo $row['id']; ?>" 
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="delete_expense.php?id=<?php echo $row['id']; ?>" 
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirmDelete();">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Total:</strong></td>
                            <td colspan="2">
                                <?php
                                $query = "SELECT SUM(amount) as total FROM expenses";
                                $result = Database::search($query);
                                $total = $result->fetch_assoc()['total'];
                                echo formatCurrency($total);
                                ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function filterExpenses() {
    const dateRange = document.getElementById('date-range').value;
    const category = document.getElementById('category-filter').value;
    
    if (!dateRange) {
        alert('Please select a date range');
        return;
    }
    
    const [startDate, endDate] = dateRange.split(' - ');
    window.location.href = `expenses.php?start=${startDate}&end=${endDate}&category=${category}`;
}

function resetFilters() {
    window.location.href = 'expenses.php';
}
</script>

<?php include 'footer.php'; ?>
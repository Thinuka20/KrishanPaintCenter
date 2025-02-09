<?php
// employees.php - List all employees
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
    <div class="row mb-3">
        <div class="col-md-6">
            <h2>Employees</h2>
        </div>
        <div class="col-md-6 text-end">
            <a href="add_employee.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Employee
            </a>
            <a href="attendance.php" class="btn btn-warning">
                Attendance
            </a>
            <a href="salary_payments.php" class="btn btn-success">
                Salary Payments
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped datatable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Day Rate</th>
                            <th>OT Rate</th>
                            <th>Join Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT * FROM employees ORDER BY name";
                        $result = Database::search($query);
                        while ($row = $result->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo $row['name']; ?></td>
                            <td><?php echo $row['phone']; ?></td>
                            <td><?php echo $row['email']; ?></td>
                            <td><?php echo formatCurrency($row['day_rate']); ?></td>
                            <td><?php echo formatCurrency($row['overtime_rate']); ?></td>
                            <td><?php echo date('Y-m-d', strtotime($row['join_date'])); ?></td>
                            <td class="action-buttons">
                                <a href="view_employee.php?id=<?php echo $row['id']; ?>" 
                                   class="btn btn-sm btn-info">
                                    <i class="fas fa-eye text-light"></i>
                                </a>
                                <a href="edit_employee.php?id=<?php echo $row['id']; ?>" 
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit text-light"></i>
                                </a>
                                <a href="salary_calculation.php?id=<?php echo $row['id']; ?>" 
                                   class="btn btn-sm btn-success">
                                    <i class="fas fa-calculator text-light"></i>
                                </a>
                                <a href="delete_employee.php?id=<?php echo $row['id']; ?>" 
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirmDelete();">
                                    <i class="fas fa-trash text-light"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
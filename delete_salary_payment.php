<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';

checkLogin();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: unauthorized.php");
    exit();
}

$payment_id = (int)$_GET['id'];
$month = $_GET['month'] ?? date('Y-m');

try {
    // Get payment details before deletion
    $query = "SELECT * FROM salary_payments WHERE id = $payment_id";
    $result = Database::search($query);
    $payment = $result->fetch_assoc();

    if (!$payment) {
        throw new Exception("Payment record not found.");
    }

    Database::connection();
    Database::$connection->begin_transaction();

    // Delete the payment record
    $query = "DELETE FROM salary_payments WHERE id = $payment_id";
    Database::iud($query);

    Database::$connection->commit();
    $_SESSION['success'] = "Salary payment record deleted successfully.";
} catch (Exception $e) {
    if (isset(Database::$connection)) {
        Database::$connection->rollback();
    }
    $_SESSION['error'] = "Error deleting salary payment: " . $e->getMessage();
}

header("Location: salary_payments.php?month=" . $month);
exit();

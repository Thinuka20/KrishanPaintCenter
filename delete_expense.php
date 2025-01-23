<?php
// delete_expense.php - Delete expense
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';
checkLogin();

$expense_id = (int)$_GET['id'];

$query = "DELETE FROM expenses WHERE id = $expense_id";
Database::iud($query);

header("Location: expenses.php");
exit();
?>
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

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!isset($_POST['attendance_id'])) {
    $response['message'] = 'Attendance ID is required';
    echo json_encode($response);
    exit;
}

$attendance_id = (int)$_POST['attendance_id'];

// Get the attendance record first to check the date
$query = "SELECT attendance_date FROM employee_attendance WHERE id = '" . $attendance_id . "'";
$result = Database::search($query);
$attendance = $result->fetch_assoc();

if (!$attendance) {
    $response['message'] = 'Attendance record not found';
    echo json_encode($response);
    exit;
}

// Delete the record
$delete_query = "DELETE FROM employee_attendance WHERE id = '" . $attendance_id . "'";
Database::iud($delete_query);

// Since iud() doesn't return a value, we'll check if the record still exists
$check_query = "SELECT id FROM employee_attendance WHERE id = '" . $attendance_id . "'";
$check_result = Database::search($check_query);

if ($check_result->num_rows === 0) {
    $response['success'] = true;
    $response['message'] = 'Attendance record deleted successfully';
} else {
    $response['message'] = 'Failed to delete attendance record';
}

echo json_encode($response);
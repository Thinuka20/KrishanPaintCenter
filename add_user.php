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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $username = $_POST['username'];
        $password = $_POST['password'];
        $role = $_POST['role'];
        
        // Check if username exists
        $query = "SELECT id FROM users WHERE username = '$username'";
        $result = Database::search($query);
        if ($result->num_rows > 0) {
            throw new Exception("Username already exists");
        }
        
        $query = "INSERT INTO users (username, password, role) VALUES ('$username', '$password', '$role')";
        Database::iud($query);
        
        echo json_encode(['success' => true, 'message' => 'User added successfully']);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
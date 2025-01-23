<?php
// logout.php - Logout user
session_start();
session_destroy();
header("Location: login.php");
exit();


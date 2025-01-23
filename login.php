<?php
// login.php - Login page
require_once 'config.php';
require_once 'functions.php';
require_once 'connection.php';

// session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = validateInput($_POST['username']);
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE username = '$username'";
    $result = Database::search($query);

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if ($password == $user['password']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            header("Location: index.php");
            exit();
        } else {
            $error = 'Invalid password';
        }
    } else {
        $error = 'User not found';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Krishan Paint Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
    background-image: url('assets/background2.webp');
    background-size: cover;
    background-repeat: no-repeat;
    background-position: center;
    height: 100vh;
    overflow: hidden;
    display: flex;
    justify-content: center;
    align-items: center;
    position: relative;
}

body::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-image: inherit;
    background-size: inherit;
    background-repeat: inherit;
    background-position: inherit;
    filter: blur(5px);  /* Apply blur */
    z-index: -1;  /* Keep the background behind content */
}
        .login-container {
            max-width: 400px;
            margin: 100px auto;
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="login-container">
            <div class="logo" style="text-align: center;">
                <h1 style="color: white; text-shadow: 5px 5px 5px black;">Krishan Paint Center</h1>
                <p style="color: white; text-shadow: 5px 5px 5px black;">Management System</p>
            </div>

            <div class="card">
                <div class="card-body">
                    <h3 class="card-title text-center mb-4">Login</h3>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="form-group mb-3">
                            <label>Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>

                        <div class="form-group mb-3">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Login</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
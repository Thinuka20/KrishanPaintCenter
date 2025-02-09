<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'auth.php';
require_once 'connection.php';
checkLogin();

include 'header.php';
?>

<div class="container content">
    <div class="row justify-content-center mt-5">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="text-danger mb-4">Access Denied</h3>
                    <p class="mb-4">You do not have permission to access this page. Please contact your administrator if you believe this is an error.</p>
                    <div class="row">
                        <div>
                            <a href="logout.php" class="btn btn-primary"><i class="fas fa-sign-in"></i> Return to Login</a>
                            <button onclick="history.back()" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Previous Page
                            </button>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
<?php
// modules/users/login.php

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Access Interceptor: If the user is already logged in, bypass login screen completely
if (isset($_SESSION['role'])) {
    header("Location: ../../modules/reports/index.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Latur District | Login · Meeting & Task Planner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .login-card { max-width: 400px; width: 100%; margin: 80px auto; border: none; border-top: 4px solid #8a151b; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .btn-primary { background-color: #8a151b; border: none; }
        .btn-primary:hover { background-color: #6a1015; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card login-card p-4">
            <div class="text-center mb-4 text-gov-blue">
                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/b/bb/Emblem_of_India.svg/50px-Emblem_of_India.svg.png" height="60" class="mb-2" alt="Emblem of India">
                <h4 class="fw-bold">Official Login</h4>
                <p class="text-muted small">Latur District Administration</p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger small"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <form action="../../controllers/AuthController.php" method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Official Email</label>
                    <input type="email" name="email" class="form-control" placeholder="user@latur.gov.in" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2" style="background-color: var(--gov-blue);">Sign In</button>
            </form>

            <div class="text-center mt-4">
                <p class="small text-muted mb-1">Need assistance? <a href="#" class="text-decoration-none">Contact IT Support</a></p>
                <hr>
                <p class="small text-muted mb-0"><strong>Demo:</strong> organizer@project.local / admin123</p>
            </div>
        </div>
    </div>
</body>
</html>
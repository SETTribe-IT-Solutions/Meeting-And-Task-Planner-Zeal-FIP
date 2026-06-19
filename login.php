<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Auth Portal | Latur Administration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8fafc; font-family: 'Segoe UI', Tahoma, sans-serif; }
        .login-card { 
            background: #ffffff; 
            border-radius: 8px; 
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); 
            border-top: 4px solid #1e293b; 
        }
        .form-control { border-radius: 4px; border: 1px solid #cbd5e1; }
        .btn-primary { background: #1e293b; border: none; font-weight: 600; }
        .btn-primary:hover { background: #334155; }
        .brand-logo { color: #1e293b; font-weight: 800; letter-spacing: -1px; }
    </style>
</head>
<body>

<div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="login-card p-5" style="width: 100%; max-width: 400px;">
        <div class="text-center mb-4">
            <h3 class="brand-logo">LATUR ADMIN SYNC</h3>
            <p class="text-muted small">Official Workspace Entry Portal</p>
        </div>
        <?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger p-2 small text-center" role="alert">
        Invalid credentials provided. Please contact the administrator.
    </div>
<?php endif; ?>
        <form action="controllers/AuthController.php" method="POST">
            <div class="mb-3">
                <label class="form-label small fw-bold">Official Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="mb-4">
                <label class="form-label small fw-bold">Authentication Key</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2">SECURE LOGIN</button>
        </form>
    </div>
</div>

</body>
</html>
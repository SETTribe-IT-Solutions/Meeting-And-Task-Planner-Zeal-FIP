<?php
// modules/users/login.php

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Access Interceptor: If the user is already logged in, bypass login screen completely
if (isset($_SESSION['role'])) {
    header("Location: ../../index.php");
    exit();
}

// Inject standard layout header
include_once '../../includes/header.php';
?>

<div class="row justify-content-center my-5">
    <div class="col-md-5">
        
        <div class="text-center mb-4">
            <h2 class="fw-bold text-dark">Meeting & Task Planner</h2>
            <p class="text-muted small">Zeal Institutional Management Framework</p>
        </div>

        <div class="card shadow-sm border-0 px-3 py-4 bg-white rounded-3">
            <div class="card-body">
                <h4 class="card-title fw-bold mb-4 text-center">Account Sign In</h4>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                        <div class="d-flex align-items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-exclamation-triangle-fill me-2" viewBox="0 0 16 16">
                                <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                            </svg>
                            <div>
                                <?php 
                                    echo htmlspecialchars($_SESSION['error']); 
                                    unset($_SESSION['error']); // Instantly flush key so it won't persist on page reload
                                ?>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form action="../../controllers/AuthController.php" method="POST" novalidate>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label font-monospace small fw-bold text-secondary">EMAIL ADDRESS</label>
                        <input type="email" class="form-control form-control-lg text-lowercase" id="email" name="email" placeholder="username@zealedu.in" required autocomplete="email">
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label font-monospace small fw-bold text-secondary">PASSWORD</label>
                        <input type="password" class="form-control form-control-lg" id="password" name="password" placeholder="••••••••" required>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg fw-semibold shadow-sm text-uppercase">Log In</button>
                    </div>

                </form>

            </div>
        </div>

        <div class="text-center mt-4">
            <div class="p-2 border rounded bg-white shadow-sm d-inline-block px-4">
                <span class="text-muted small"><strong>Organizer Credentials:</strong></span><br>
                <code class="user-select-all small">organizer@project.local</code> <span class="text-muted small">/</span> <code class="user-select-all small">admin123</code>
            </div>
        </div>

    </div>
</div>

<?php 
// Inject standard layout footer
include_once '../../includes/footer.php'; 
?>
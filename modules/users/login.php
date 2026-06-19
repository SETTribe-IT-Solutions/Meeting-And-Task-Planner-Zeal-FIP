<?php
// modules/users/login.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (isset($_SESSION['role'])) {
    header("Location: ../../index.php");
    exit();
}

include_once '../../includes/header.php';
?>

<div class="row justify-content-center my-5 pt-4">
    <div class="col-xl-4 col-lg-5 col-md-7">
        
        <div class="mb-4 text-center">
            <h3 class="fw-bold tracking-tight text-dark" style="font-size: 22px;">Security Access Interceptor</h3>
            <p class="text-muted small">Provide assigned digital credentials to sync platform desks.</p>
        </div>

        <div class="card flat-card p-4 bg-white">
            <div class="card-body p-2">

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="p-3 mb-4 border border-danger-subtle rounded text-danger bg-danger-subtle small font-monospace">
                        <strong>LOGON EXCEPTION:</strong> <?php echo htmlspecialchars($_SESSION['error']); ?>
                        <?php unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <form action="../../controllers/AuthController.php" method="POST" novalidate>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label text-uppercase font-monospace text-secondary small fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">Identity Vector (Email)</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="name@maharashtra.gov.in" required autocomplete="email" style="font-size: 14px; padding: 10px 12px; border-radius: 6px; border: 1px solid #cbd5e1;">
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label text-uppercase font-monospace text-secondary small fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">Secure Secret PIN</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required style="font-size: 14px; padding: 10px 12px; border-radius: 6px; border: 1px solid #cbd5e1;">
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-dark fw-semibold py-2.5" style="font-size: 13px; border-radius: 6px; background-color: var(--slate-800); border: none;">
                            Initialize Connection Layer &rarr;
                        </button>
                    </div>

                </form>

            </div>
        </div>

        <div class="mt-4 text-center">
            <div class="p-3 rounded border" style="background-color: #fafafa; border-style: dashed !important;">
                <span class="text-secondary d-block font-monospace mb-1" style="font-size: 11px;">SANDBOX ACCESS CONFIGS</span>
                <code class="user-select-all small" style="color: var(--slate-800); font-size: 12px;">organizer@project.local</code> 
                <span class="text-muted mx-1">|</span> 
                <code class="user-select-all small" style="color: var(--slate-800); font-size: 12px;">admin123</code>
            </div>
        </div>

    </div>
</div>

<?php include_once '../../includes/footer.php'; ?>
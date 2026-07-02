<?php
// includes/smart-alert.php
// Smart Alert Component - Reusable notification system for all pages

// Include this file in your page to use smart alerts
// Usage: Include this file after session_start() and before HTML output

// Get any alert messages from session
$alertType = '';
$alertTitle = '';
$alertMessage = '';
$alertHasContent = false;

if (!empty($_SESSION['alert'])) {
    $alertType = $_SESSION['alert']['type'] ?? 'info';
    $alertTitle = $_SESSION['alert']['title'] ?? '';
    $alertMessage = $_SESSION['alert']['message'] ?? '';
    $alertHasContent = true;
    
    // Clear the alert after reading it (one-time display)
    unset($_SESSION['alert']);
}

// Alternative: Legacy error/success messages (backward compatibility)
if (empty($alertHasContent)) {
    if (!empty($_SESSION['error'])) {
        $alertType = 'error';
        $alertTitle = 'Error';
        $alertMessage = $_SESSION['error'];
        $alertHasContent = true;
        unset($_SESSION['error']);
    } elseif (!empty($_SESSION['success'])) {
        $alertType = 'success';
        $alertTitle = 'Success';
        $alertMessage = $_SESSION['success'];
        $alertHasContent = true;
        unset($_SESSION['success']);
    }
}
?>

<!-- Smart Alert Component CSS -->
<style>
.smart-alert-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 10000;
    max-width: 450px;
    animation: slideInRight 0.3s ease-out;
}

@keyframes slideInRight {
    from {
        transform: translateX(500px);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideOutRight {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(500px);
        opacity: 0;
    }
}

.smart-alert {
    padding: 16px 20px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 10px;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    animation: smartAlertFadeOut 0.3s ease-out forwards;
    animation-delay: 4.5s;
}

@keyframes smartAlertFadeOut {
    from {
        opacity: 1;
        transform: translateY(0);
    }
    to {
        opacity: 0;
        transform: translateY(-10px);
    }
}

.smart-alert.active {
    animation: none;
}

.smart-alert.closing {
    animation: slideOutRight 0.3s ease-out forwards;
}

.smart-alert-icon {
    flex-shrink: 0;
    font-size: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.smart-alert-content {
    flex: 1;
}

.smart-alert-title {
    font-weight: 600;
    font-size: 14px;
    margin: 0 0 4px 0;
}

.smart-alert-message {
    font-size: 13px;
    margin: 0;
    line-height: 1.4;
    opacity: 0.95;
}

.smart-alert-close {
    flex-shrink: 0;
    background: none;
    border: none;
    cursor: pointer;
    font-size: 18px;
    opacity: 0.7;
    transition: opacity 0.2s;
    padding: 0;
    display: flex;
    align-items: center;
}

.smart-alert-close:hover {
    opacity: 1;
}

/* Alert Type Styles */
.smart-alert.alert-success {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.smart-alert.alert-success .smart-alert-icon {
    color: #28a745;
}

.smart-alert.alert-success .smart-alert-close {
    color: #155724;
}

.smart-alert.alert-error {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.smart-alert.alert-error .smart-alert-icon {
    color: #dc3545;
}

.smart-alert.alert-error .smart-alert-close {
    color: #721c24;
}

.smart-alert.alert-warning {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    color: #856404;
}

.smart-alert.alert-warning .smart-alert-icon {
    color: #ffc107;
}

.smart-alert.alert-warning .smart-alert-close {
    color: #856404;
}

.smart-alert.alert-info {
    background: #d1ecf1;
    border: 1px solid #bee5eb;
    color: #0c5460;
}

.smart-alert.alert-info .smart-alert-icon {
    color: #17a2b8;
}

.smart-alert.alert-info .smart-alert-close {
    color: #0c5460;
}

/* Progress bar animation */
.smart-alert::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    height: 3px;
    background: currentColor;
    opacity: 0.5;
    animation: progressBar 5s linear;
}

@keyframes progressBar {
    from {
        width: 100%;
    }
    to {
        width: 0;
    }
}

.smart-alert.active::after {
    animation: none;
}

/* Mobile responsiveness */
@media (max-width: 600px) {
    .smart-alert-container {
        left: 10px;
        right: 10px;
        max-width: none;
        top: 10px;
    }

    .smart-alert {
        font-size: 12px;
    }

    .smart-alert-icon {
        font-size: 18px;
    }

    .smart-alert-title {
        font-size: 13px;
    }

    .smart-alert-message {
        font-size: 12px;
    }
}
</style>

<!-- Smart Alert HTML -->
<div class="smart-alert-container" id="alertContainer">
    <?php if ($alertHasContent && !empty($alertMessage)): ?>
    <div class="smart-alert alert-<?php echo htmlspecialchars($alertType); ?> active" id="smartAlert">
        <div class="smart-alert-icon" id="alertIcon">
            <?php
            $icons = [
                'success' => '<i class="fas fa-check-circle"></i>',
                'error' => '<i class="fas fa-exclamation-circle"></i>',
                'warning' => '<i class="fas fa-exclamation-triangle"></i>',
                'info' => '<i class="fas fa-info-circle"></i>'
            ];
            echo $icons[$alertType] ?? '<i class="fas fa-info-circle"></i>';
            ?>
        </div>
        <div class="smart-alert-content">
            <?php if (!empty($alertTitle)): ?>
            <div class="smart-alert-title"><?php echo htmlspecialchars($alertTitle); ?></div>
            <?php endif; ?>
            <p class="smart-alert-message"><?php echo htmlspecialchars($alertMessage); ?></p>
        </div>
        <button class="smart-alert-close" onclick="closeAlert()" type="button" title="Close">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <?php endif; ?>
</div>

<!-- Smart Alert JavaScript -->
<script>
// Function to show alert programmatically
function showSmartAlert(message, type = 'info', title = '') {
    const container = document.getElementById('alertContainer');
    if (!container) return;

    // Sanitize input
    message = String(message).replace(/</g, '&lt;').replace(/>/g, '&gt;');
    type = String(type).replace(/[^a-z]/g, '');
    title = String(title).replace(/</g, '&lt;').replace(/>/g, '&gt;');

    // Determine icon based on type
    const icons = {
        'success': '<i class="fas fa-check-circle"></i>',
        'error': '<i class="fas fa-exclamation-circle"></i>',
        'warning': '<i class="fas fa-exclamation-triangle"></i>',
        'info': '<i class="fas fa-info-circle"></i>'
    };

    const iconHtml = icons[type] || icons['info'];
    const typeTitle = title || type.charAt(0).toUpperCase() + type.slice(1);

    const alertHtml = `
        <div class="smart-alert alert-${type} active" id="tempAlert">
            <div class="smart-alert-icon">${iconHtml}</div>
            <div class="smart-alert-content">
                <div class="smart-alert-title">${typeTitle}</div>
                <p class="smart-alert-message">${message}</p>
            </div>
            <button class="smart-alert-close" onclick="this.parentElement.remove()" type="button" title="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;

    container.insertAdjacentHTML('beforeend', alertHtml);

    // Auto-remove after 5 seconds
    const alert = document.getElementById('tempAlert');
    setTimeout(() => {
        if (alert) {
            alert.classList.remove('active');
            alert.classList.add('closing');
            setTimeout(() => alert.remove(), 300);
        }
    }, 5000);
}

// Function to close alert manually
function closeAlert() {
    const alert = document.getElementById('smartAlert');
    if (alert) {
        alert.classList.remove('active');
        alert.classList.add('closing');
        setTimeout(() => {
            alert.remove();
        }, 300);
    }
}

// Auto-close alert after 5 seconds if it's active
document.addEventListener('DOMContentLoaded', function() {
    const alert = document.getElementById('smartAlert');
    if (alert && alert.classList.contains('active')) {
        setTimeout(() => {
            if (alert) {
                alert.classList.remove('active');
                alert.classList.add('closing');
                setTimeout(() => {
                    alert.remove();
                }, 300);
            }
        }, 5000);
    }
});

// Global error handler integration (optional)
window.addEventListener('error', function(event) {
    console.error('JavaScript Error:', event.error);
    // Uncomment below to show errors as alerts
    // showSmartAlert('An error occurred. Please check the console.', 'error', 'Error');
});
</script>

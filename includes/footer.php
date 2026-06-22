<!-- includes/footer.php -->
        </main>
    </div>

    <style>
        .footer {
            background: #0f2b38;
            color: #e0e7ef;
            padding: 1.2rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            font-size: 0.85rem;
            border-top: 2px solid var(--gov-yellow);
        }
        .footer-links a {
            color: #cbd5e1;
            text-decoration: none;
            margin-left: 15px;
        }
        .footer-links a:hover { color: #f9b81b; }
    </style>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JS -->
    <script src="<?php echo defined('APP_URL') ? APP_URL : ''; ?>/assets/js/script.js"></script>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="d-flex align-items-center gap-2">
            <i class="fas fa-map-pin text-warning"></i>
            <span>Latur District Administration Portal</span>
        </div>
        <div class="footer-links">
            <a href="#"><i class="fas fa-envelope"></i> Contact</a>
            <a href="#"><i class="fas fa-shield-alt"></i> Privacy</a>
            <a href="#"><i class="fas fa-info-circle"></i> Help</a>
        </div>
        <div class="mt-2 mt-md-0">
            © <?php echo date('Y'); ?> v1.0.0
        </div>
    </footer>

    <script>
        function updateDate() {
            const now = new Date();
            const options = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
            const el = document.getElementById('liveDate');
            if(el) el.textContent = now.toLocaleDateString('en-IN', options);
        }
        updateDate();
        setInterval(updateDate, 30000);
    </script>
</body>
</html>
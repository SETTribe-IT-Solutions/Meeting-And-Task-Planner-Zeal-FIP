<!-- includes/footer.php -->
        </main>
    </div>

    <style>
        .footer {
            background: linear-gradient(135deg, #0f2b38, #0b1f2a);
            color: #e0e7ef;
            padding: 1.2rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            font-size: 0.85rem;
            border-top: 3px solid #f9b81b;
        }
        .footer-links a {
            color: #cbd5e1;
            text-decoration: none;
            margin-left: 15px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        .footer-links a:hover { 
            color: #f9b81b; 
            transform: translateY(-1px);
        }
    </style>

    <!-- Bootstrap 5.3 JS Bundle (local) -->
    <script src="<?php echo defined('APP_URL') ? APP_URL : ''; ?>/assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
    <!-- jQuery (local) -->
    <script src="<?php echo defined('APP_URL') ? APP_URL : ''; ?>/assets/vendor/jquery/jquery-3.7.1.min.js"></script>
    <!-- Custom App JS (Pagination, Filters, Animations) -->
    <script src="<?php echo defined('APP_URL') ? APP_URL : ''; ?>/assets/js/app.js?v=9"></script>
    
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
    <script>
    (function(){
      var sizes={'-1':'13px','0':'16px','1':'19px'};
      function applySize(level){
        document.documentElement.style.fontSize=sizes[level]||'16px';
        localStorage.setItem('fontSize',level);
        document.querySelectorAll('.font-btn').forEach(function(b){
          b.classList.toggle('util-active', b.dataset.size===String(level));
        });
      }
      applySize(localStorage.getItem('fontSize')||'0');
      document.querySelectorAll('.font-btn').forEach(function(btn){
        btn.addEventListener('click',function(){ applySize(this.dataset.size); });
      });
    }());
    </script>
</body>
</html>
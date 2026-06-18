            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2026 Meeting & Task Planner. All rights reserved.</p>
            <p>Designed for efficient team collaboration and task management.</p>
        </div>
    </footer>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add active state to current page link
        document.addEventListener('DOMContentLoaded', function() {
            const currentPath = window.location.pathname;
            const sidebarItems = document.querySelectorAll('.sidebar-item');
            
            sidebarItems.forEach(item => {
                if (item.href.includes(currentPath.split('/').pop())) {
                    item.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>

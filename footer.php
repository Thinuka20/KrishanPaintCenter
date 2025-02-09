</div> <!-- Close container-fluid -->
    </div> <!-- Close content-wrapper -->
<footer class="footer mt-auto py-3 bg-light">
    <div class="container text-center">
        <span class="text-muted">© <?php echo date('Y'); ?> TC Softwares. All rights reserved.</span>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="assets/js/main.js"></script>
<script>
        // Sidebar toggle functionality
        document.getElementById('sidebar-toggle')?.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const sidebarToggle = document.getElementById('sidebar-toggle');
            
            if (window.innerWidth <= 991.98) {
                if (!sidebar.contains(event.target) && !sidebarToggle.contains(event.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });

        // Initialize tooltips if using Bootstrap
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Handle active states for nested navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            if (link.href === window.location.href) {
                link.classList.add('active');
            }
        });
    </script>

<script>
document.getElementById('themeForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('update_theme.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.documentElement.setAttribute('data-theme', formData.get('theme'));
            showAlert('success', 'Theme updated successfully');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showAlert('danger', data.message || 'Failed to update theme');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('danger', 'An error occurred. Please try again.');
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const currentPage = window.location.pathname.split('/').pop();
    const employeePages = ['employees.php', 'attendance.php', 'mark_attendance.php', 'salary_payments.php'];
    
    if (employeePages.includes(currentPage)) {
        const submenuToggle = document.querySelector('[href="#employeeSubmenu"]');
        const submenu = document.getElementById('employeeSubmenu');
        
        if (submenuToggle && submenu) {
            submenuToggle.classList.remove('collapsed');
            submenuToggle.setAttribute('aria-expanded', 'true');
            submenu.classList.add('show');
        }
        
        const activeLink = document.querySelector(`a[href="${currentPage}"]`);
        if (activeLink) {
            activeLink.classList.add('active');
        }
    }
});
</script>
</body>

</html>
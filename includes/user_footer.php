        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> - <?php echo APP_FULL_NAME; ?></p>
        </div>
    </footer>

    <!-- Bootstrap JS with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Custom JavaScript -->
    <script>
    $(document).ready(function() {
        // Any additional site-wide JavaScript can go here
        
        // Add active class to offcanvas links based on current page
        const currentPage = window.location.pathname.split('/').pop();
        $('.mobile-menu-link').each(function() {
            const href = $(this).attr('href');
            if (href && href.indexOf(currentPage) > -1) {
                $(this).addClass('active');
            }
        });
    });
    </script>
    
    <?php if (isset($page_scripts)): ?>
    <!-- Page-specific scripts -->
    <?php echo $page_scripts; ?>
    <?php endif; ?>
</body>
</html> 
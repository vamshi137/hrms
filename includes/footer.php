<footer class="footer mt-auto py-3 bg-white border-top">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-md-6">
                <span class="text-muted">© <?php echo date('Y'); ?> HRMS System v1.0</span>
            </div>
            <div class="col-md-6 text-right">
                <span class="text-muted">User: <?php echo $current_user['full_name']; ?> 
                (<?php echo $current_user['role_name']; ?>)</span>
            </div>
        </div>
    </div>
</footer>

<!-- jQuery -->
<script src="../assets/js/jquery.min.js"></script>

<!-- Bootstrap Bundle -->
<script src="../assets/js/bootstrap.bundle.min.js"></script>

<!-- DataTables -->
<script src="../assets/js/datatables.min.js"></script>

<!-- Select2 -->
<script src="../assets/js/select2.min.js"></script>

<!-- Charts -->
<script src="../assets/js/chart.min.js"></script>

<!-- Custom JS -->
<script src="../assets/js/app.js"></script>
<script src="../assets/js/validation.js"></script>

<script>
    // Sidebar toggle
    $('#sidebarToggle').click(function() {
        $('.sidebar').toggleClass('d-none d-md-block');
        $('.content-wrapper').toggleClass('col-md-12');
    });

    // Initialize DataTables
    $(document).ready(function() {
        $('.datatable').DataTable({
            "pageLength": 10,
            "responsive": true,
            "language": {
                "search": "Search:",
                "lengthMenu": "Show _MENU_ entries",
                "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                "paginate": {
                    "first": "First",
                    "last": "Last",
                    "next": "Next",
                    "previous": "Previous"
                }
            }
        });

        // Initialize Select2
        $('.select2').select2({
            theme: 'bootstrap4'
        });

        // Auto-dismiss alerts
        $('.alert').delay(5000).fadeOut(400);
    });
</script>
</body>
</html>
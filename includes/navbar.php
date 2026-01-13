<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container-fluid">
        <!-- Sidebar Toggle -->
        <button class="navbar-toggler" type="button" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <!-- Brand -->
        <a class="navbar-brand ml-2" href="../dashboards/<?php echo strtolower(str_replace(' ', '_', $current_user['role_name'])); ?>_dashboard.php">
            <img src="../assets/images/logo.png" height="40" class="d-inline-block align-top" alt="HRMS Logo">
            <span class="ml-2 font-weight-bold">HRMS</span>
        </a>
        
        <!-- User Menu -->
        <div class="navbar-collapse">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" 
                       data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <img src="../assets/images/default_user.png" class="rounded-circle mr-2" 
                             width="32" height="32" alt="User">
                        <span class="d-none d-md-inline"><?php echo $current_user['full_name']; ?></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right shadow" aria-labelledby="userDropdown">
                        <div class="dropdown-header">
                            <h6 class="mb-0"><?php echo $current_user['full_name']; ?></h6>
                            <small class="text-muted"><?php echo $current_user['role_name']; ?></small>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="../modules/employees/employee_view.php?id=<?php echo $current_user['employee_id']; ?>">
                            <i class="fas fa-user mr-2"></i>My Profile
                        </a>
                        <a class="dropdown-item" href="../auth/logout.php">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                        </a>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</nav>
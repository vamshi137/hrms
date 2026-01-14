<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="../index.php">
            <i class="fas fa-chart-line"></i> SSMS HRMS
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars(Session::get('full_name') ?: Session::get('username')); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="../modules/employees/employee_view.php?id=<?php echo Session::get('employee_id'); ?>">
                            <i class="fas fa-user"></i> My Profile
                        </a></li>
                        <li><a class="dropdown-item" href="#">
                            <i class="fas fa-cog"></i> Settings
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../auth/logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
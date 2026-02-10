<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="index.php" class="brand-link">
        <i class="fas fa-qrcode brand-icon"></i>
        <span class="brand-text font-weight-light">QR Attendance</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- User Panel -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <i class="fas fa-user-circle img-circle elevation-2" style="font-size: 2rem; color: #fff;"></i>
            </div>
            <div class="info">
                <a href="#" class="d-block"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'System Administrator'); ?></a>
            </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <li class="nav-item">
                    <a href="index.php" class="nav-link <?= ($currentPage == 'index.php') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="attendance.php" class="nav-link <?= ($currentPage == 'attendance.php') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-qrcode"></i>
                        <p>Attendance Scanner</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="masterlist.php" class="nav-link <?= ($currentPage == 'masterlist.php') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-user-graduate"></i>
                        <p>Student Masterlist</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="archive-manager.php" class="nav-link <?= ($currentPage == 'archive-manager.php') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-archive"></i>
                        <p>Archive Manager</p>
                    </a>
                </li>
                
                <!-- Logout Button - Simple link in sidebar -->
                <li class="nav-item">
                    <a href="endpoint/logout.php" class="nav-link text-danger">
                        <i class="nav-icon fas fa-sign-out-alt"></i>
                        <p>Logout</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>
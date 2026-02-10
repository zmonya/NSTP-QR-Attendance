<?php
// Determine current page for active link highlighting
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="index.php" class="brand-link">
        <span class="brand-image img-circle elevation-3" style="background: white; padding: 8px;">
            <i class="fas fa-qrcode text-primary"></i>
        </span>
        <span class="brand-text font-weight-light">QR Attendance</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel (optional) -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <div class="img-circle elevation-2" style="width: 40px; height: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center;">
                    <?php 
                    $initials = '';
                    if (isset($_SESSION['full_name'])) {
                        $nameParts = explode(' ', $_SESSION['full_name']);
                        $initials = strtoupper(substr($nameParts[0], 0, 1));
                        if (isset($nameParts[1])) {
                            $initials .= strtoupper(substr($nameParts[1], 0, 1));
                        }
                    }
                    echo $initials;
                    ?>
                </div>
            </div>
            <div class="info">
                <a href="#" class="d-block">
                    <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Administrator'); ?>
                    <?php if (isset($_SESSION['role'])): ?>
                        <small class="text-muted d-block">
                            <i class="fas fa-shield-alt mr-1"></i>
                            <?php echo ucfirst(str_replace('_', ' ', $_SESSION['role'])); ?>
                        </small>
                    <?php endif; ?>
                </a>
            </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                <!-- Dashboard -->
                <li class="nav-item">
                    <a href="index.php" class="nav-link <?= ($currentPage == 'index.php') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <!-- Attendance Scanner -->
                <li class="nav-item">
                    <a href="attendance.php" class="nav-link <?= ($currentPage == 'attendance.php') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-qrcode"></i>
                        <p>Attendance Scanner</p>
                    </a>
                </li>

                <!-- Student Management -->
                <li class="nav-item">
                    <a href="masterlist.php" class="nav-link <?= ($currentPage == 'masterlist.php') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-user-graduate"></i>
                        <p>Student Management</p>
                    </a>
                </li>

                <!-- Attendance Records -->
                <li class="nav-item <?= (in_array($currentPage, ['attendance-records.php', 'archive-manager.php'])) ? 'menu-open' : '' ?>">
                    <a href="#" class="nav-link <?= (in_array($currentPage, ['attendance-records.php', 'archive-manager.php'])) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-clipboard-list"></i>
                        <p>
                            Attendance Records
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                      
                        <li class="nav-item">
                            <a href="archive-manager.php" class="nav-link <?= ($currentPage == 'archive-manager.php') ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Archive Manager</p>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Reports -->
               

                <!-- Settings -->
                <li class="nav-item <?= (in_array($currentPage, ['settings.php', 'admin-management.php'])) ? 'menu-open' : '' ?>">
                    <a href="#" class="nav-link <?= (in_array($currentPage, ['settings.php', 'admin-management.php'])) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-cog"></i>
                        <p>
                            Settings
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin'): ?>
                        <li class="nav-item">
                            <a href="admin-management.php" class="nav-link <?= ($currentPage == 'admin-management.php') ? 'active' : '' ?>">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Admin Management</p>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </li>

                <!-- Divider -->
                <li class="nav-header">SYSTEM</li>

                <!-- Help & Support -->
                <li class="nav-item">
                    <a href="help.php" class="nav-link <?= ($currentPage == 'help.php') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-question-circle"></i>
                        <p>Help & Support</p>
                    </a>
                </li>

                <!-- Logout -->
                <li class="nav-item">
                    <a href="./endpoint/logout.php" class="nav-link text-danger">
                        <i class="nav-icon fas fa-sign-out-alt"></i>
                        <p>Logout</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>

<!-- Optional: Add some custom styles -->
<style>
    .brand-link {
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .brand-image {
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    
    .user-panel .image {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .user-panel .info a:hover {
        text-decoration: none;
    }
    
    .nav-sidebar .nav-item > .nav-link.active {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        border-left: 4px solid #ffc107;
    }
    
    .nav-sidebar .nav-treeview .nav-item > .nav-link.active {
        background: rgba(255, 255, 255, 0.1);
        border-left: 4px solid #28a745;
    }
    
    .sidebar-dark-primary .nav-sidebar > .nav-item > .nav-link.active {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    }
    
    .nav-item.menu-open > .nav-link {
        background: rgba(255, 255, 255, 0.1);
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .brand-text {
            display: none;
        }
        
        .user-panel .info {
            display: none;
        }
    }
</style>

<!-- Initialize AdminLTE sidebar -->
<script>
    $(document).ready(function() {
        // Initialize sidebar treeview
        $('[data-widget="treeview"]').Treeview('init');
        
        // Add active state to current page in treeview
        const currentPage = '<?php echo $currentPage; ?>';
        $('.nav-link').each(function() {
            if ($(this).attr('href') === currentPage) {
                $(this).addClass('active');
                $(this).closest('.nav-item').addClass('menu-open');
                $(this).closest('.nav-treeview').closest('.nav-item').addClass('menu-open');
            }
        });
        
        // Smooth sidebar animations
        $('.nav-link').on('click', function() {
            // Remove active class from all links
            $('.nav-link').removeClass('active');
            
            // Add active class to clicked link
            $(this).addClass('active');
            
            // Close other open menus
            $(this).closest('.nav-item').siblings().removeClass('menu-open');
            
            // If this is a treeview parent, toggle it
            if ($(this).siblings('.nav-treeview').length > 0) {
                $(this).closest('.nav-item').toggleClass('menu-open');
            }
        });
        
        // Auto-collapse sidebar on mobile
        if ($(window).width() < 768) {
            $('body').addClass('sidebar-collapse');
        }
        
        // Toggle sidebar on window resize
        $(window).resize(function() {
            if ($(window).width() < 768) {
                $('body').addClass('sidebar-collapse');
            } else {
                $('body').removeClass('sidebar-collapse');
            }
        });
    });
</script>
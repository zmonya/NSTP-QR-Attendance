<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
                <?php 
                // Check if user has profile picture
                if (isset($_SESSION['profile_picture']) && !empty($_SESSION['profile_picture']) && file_exists($_SESSION['profile_picture'])): ?>
                    <img src="<?php echo htmlspecialchars($_SESSION['profile_picture']); ?>?v=<?php echo time(); ?>" 
                         class="img-circle elevation-2" 
                         alt="User Image"
                         style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%; border: 2px solid #fff;">
                <?php else: ?>
                    <div class="img-circle elevation-2" style="width: 40px; height: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; border-radius: 50%; border: 2px solid #fff;">
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
                <?php endif; ?>
            </div>
            <div class="info">
                <a href="profile.php" class="d-block">
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

                <!-- My Profile -->
               

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

                <!-- Archive Manager -->
                <li class="nav-item">
                    <a href="archive-manager.php" class="nav-link <?= ($currentPage == 'archive-manager.php') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-archive"></i>
                        <p>Archive Manager</p>
                    </a>
                </li>

                <!-- Admin Management (Super Admin Only) -->
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin'): ?>
                <li class="nav-item">
                    <a href="admin-management.php" class="nav-link <?= ($currentPage == 'admin-management.php') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-user-shield"></i>
                        <p>Admin Management</p>
                    </a>
                </li>
                <?php endif; ?>

                <!-- Divider -->
                <li class="nav-header">SYSTEM</li>

 <li class="nav-item">
                    <a href="profile.php" class="nav-link <?= ($currentPage == 'profile.php') ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-user-circle"></i>
                        <p>My Profile</p>
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

<!-- Simplified JavaScript - no dropdown functionality needed -->
<script>
    $(document).ready(function() {
        // Add active state to current page
        const currentPage = '<?php echo $currentPage; ?>';
        $('.nav-link').each(function() {
            if ($(this).attr('href') === currentPage) {
                $(this).addClass('active');
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
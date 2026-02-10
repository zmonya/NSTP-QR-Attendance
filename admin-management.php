<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: index.php");
    exit();
}

date_default_timezone_set('Asia/Manila');
include ('./conn/conn.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Management - QR Attendance</title>
    
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <style>
        .admin-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
        }
        .role-badge {
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 20px;
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 20px;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
        </ul>
    </nav>

    <!-- Sidebar -->
    <?php include 'adminlte-sidebar.php'; ?>

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <!-- Content Header -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">
                            <i class="fas fa-users-cog mr-2"></i>Admin Management
                        </h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <li class="breadcrumb-item active">Admin Management</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <section class="content">
            <div class="container-fluid">
                <!-- Quick Stats -->
                <div class="row mb-3">
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-info">
                            <div class="inner">
                                <?php
                                $stmt = $conn->prepare("SELECT COUNT(*) FROM tbl_admin");
                                $stmt->execute();
                                $totalAdmins = $stmt->fetchColumn();
                                ?>
                                <h3><?php echo $totalAdmins; ?></h3>
                                <p>Total Administrators</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-user-shield"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-success">
                            <div class="inner">
                                <?php
                                $stmt = $conn->prepare("SELECT COUNT(*) FROM tbl_admin WHERE status = 'active'");
                                $stmt->execute();
                                $activeAdmins = $stmt->fetchColumn();
                                ?>
                                <h3><?php echo $activeAdmins; ?></h3>
                                <p>Active Administrators</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-user-check"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <?php
                                $stmt = $conn->prepare("SELECT COUNT(*) FROM tbl_admin WHERE role = 'super_admin'");
                                $stmt->execute();
                                $superAdmins = $stmt->fetchColumn();
                                ?>
                                <h3><?php echo $superAdmins; ?></h3>
                                <p>Super Administrators</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-crown"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-gradient-secondary">
                            <div class="inner">
                                <?php
                                $stmt = $conn->prepare("SELECT COUNT(*) FROM tbl_admin WHERE status = 'inactive'");
                                $stmt->execute();
                                $inactiveAdmins = $stmt->fetchColumn();
                                ?>
                                <h3><?php echo $inactiveAdmins; ?></h3>
                                <p>Inactive Administrators</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-user-slash"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Admin Management Section -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Administrator Accounts</h3>
                                <div class="card-tools">
                                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addAdminModal">
                                        <i class="fas fa-plus mr-2"></i>Add New Admin
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <table id="adminsTable" class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Admin</th>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $stmt = $conn->prepare("
                                            SELECT * FROM tbl_admin 
                                            ORDER BY role DESC, created_at DESC
                                        ");
                                        $stmt->execute();
                                        $admins = $stmt->fetchAll();
                                        
                                        foreach ($admins as $admin):
                                            $initials = strtoupper(substr($admin['full_name'], 0, 2));
                                            $roleClass = $admin['role'] === 'super_admin' ? 'danger' : 'primary';
                                            $statusClass = $admin['status'] === 'active' ? 'success' : 'secondary';
                                            $createdDate = new DateTime($admin['created_at']);
                                        ?>
                                        <tr>
                                            <td><?php echo $admin['tbl_admin_id']; ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="admin-avatar mr-3">
                                                        <?php echo $initials; ?>
                                                    </div>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($admin['full_name']); ?></strong>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($admin['username']); ?></td>
                                            <td><?php echo htmlspecialchars($admin['email'] ?? 'N/A'); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $roleClass; ?> role-badge">
                                                    <?php echo ucfirst(str_replace('_', ' ', $admin['role'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php echo $statusClass; ?> status-badge">
                                                    <?php echo ucfirst($admin['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $createdDate->format('M d, Y'); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-sm btn-info edit-admin" 
                                                            data-id="<?php echo $admin['tbl_admin_id']; ?>"
                                                            data-name="<?php echo htmlspecialchars($admin['full_name']); ?>"
                                                            data-username="<?php echo htmlspecialchars($admin['username']); ?>"
                                                            data-email="<?php echo htmlspecialchars($admin['email'] ?? ''); ?>"
                                                            data-role="<?php echo $admin['role']; ?>"
                                                            data-status="<?php echo $admin['status']; ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-warning change-password" 
                                                            data-id="<?php echo $admin['tbl_admin_id']; ?>"
                                                            data-name="<?php echo htmlspecialchars($admin['full_name']); ?>">
                                                        <i class="fas fa-key"></i>
                                                    </button>
                                                    <?php if ($admin['tbl_admin_id'] != $_SESSION['user_id'] && $admin['role'] != 'super_admin'): ?>
                                                    <button class="btn btn-sm btn-danger delete-admin" 
                                                            data-id="<?php echo $admin['tbl_admin_id']; ?>"
                                                            data-name="<?php echo htmlspecialchars($admin['full_name']); ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer class="main-footer">
        <strong>QR Code Attendance System &copy; <?php echo date('Y'); ?></strong>
        All rights reserved.
    </footer>
</div>

<!-- Add Admin Modal -->
<div class="modal fade" id="addAdminModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white">
                    <i class="fas fa-user-plus mr-2"></i>Add New Administrator
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="addAdminForm" method="POST">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="full_name">Full Name *</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="username">Username *</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                                <small class="form-text text-muted">Must be unique</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="role">Role *</label>
                                <select class="form-control" id="role" name="role" required>
                                    <option value="admin">Administrator</option>
                                    <option value="super_admin">Super Administrator</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password">Password *</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <small class="form-text text-muted">Minimum 8 characters</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="confirm_password">Confirm Password *</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select class="form-control" id="status" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-2"></i>Create Administrator
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Admin Modal -->
<div class="modal fade" id="editAdminModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title text-white">
                    <i class="fas fa-edit mr-2"></i>Edit Administrator
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="editAdminForm" method="POST">
                <input type="hidden" id="edit_admin_id" name="admin_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_full_name">Full Name *</label>
                                <input type="text" class="form-control" id="edit_full_name" name="full_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_username">Username *</label>
                                <input type="text" class="form-control" id="edit_username" name="username" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_email">Email Address</label>
                                <input type="email" class="form-control" id="edit_email" name="email">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_role">Role *</label>
                                <select class="form-control" id="edit_role" name="role" required>
                                    <option value="admin">Administrator</option>
                                    <option value="super_admin">Super Administrator</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_status">Status</label>
                        <select class="form-control" id="edit_status" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        Leave password fields blank to keep current password
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_password">New Password</label>
                                <input type="password" class="form-control" id="edit_password" name="password">
                                <small class="form-text text-muted">Minimum 8 characters</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_confirm_password">Confirm New Password</label>
                                <input type="password" class="form-control" id="edit_confirm_password" name="confirm_password">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-save mr-2"></i>Update Administrator
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title text-white">
                    <i class="fas fa-key mr-2"></i>Change Password
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="changePasswordForm" method="POST">
                <input type="hidden" id="password_admin_id" name="admin_id">
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <h5 id="passwordAdminName"></h5>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password *</label>
                        <input type="password" class="form-control" id="new_password" name="password" required>
                        <small class="form-text text-muted">Minimum 8 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_new_password">Confirm New Password *</label>
                        <input type="password" class="form-control" id="confirm_new_password" name="confirm_password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-key mr-2"></i>Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('#adminsTable').DataTable({
        "paging": true,
        "lengthChange": true,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true,
        "order": [[0, 'desc']]
    });
    
    // Handle add admin form submission
    $('#addAdminForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        
        // Validate passwords match
        const password = $('#password').val();
        const confirmPassword = $('#confirm_password').val();
        
        if (password !== confirmPassword) {
            Swal.fire('Error', 'Passwords do not match!', 'error');
            return;
        }
        
        if (password.length < 8) {
            Swal.fire('Error', 'Password must be at least 8 characters long!', 'error');
            return;
        }
        
        $.ajax({
            url: './endpoint/add-admin.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire('Success', response.message, 'success');
                    $('#addAdminModal').modal('hide');
                    $('#addAdminForm')[0].reset();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Network error. Please try again.', 'error');
            }
        });
    });
    
    // Handle edit button click
    $('.edit-admin').on('click', function() {
        const adminId = $(this).data('id');
        const adminName = $(this).data('name');
        const username = $(this).data('username');
        const email = $(this).data('email');
        const role = $(this).data('role');
        const status = $(this).data('status');
        
        $('#edit_admin_id').val(adminId);
        $('#edit_full_name').val(adminName);
        $('#edit_username').val(username);
        $('#edit_email').val(email);
        $('#edit_role').val(role);
        $('#edit_status').val(status);
        
        $('#editAdminModal').modal('show');
    });
    
    // Handle edit form submission
    $('#editAdminForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        
        // Validate passwords if provided
        const password = $('#edit_password').val();
        const confirmPassword = $('#edit_confirm_password').val();
        
        if (password || confirmPassword) {
            if (password !== confirmPassword) {
                Swal.fire('Error', 'Passwords do not match!', 'error');
                return;
            }
            
            if (password.length < 8) {
                Swal.fire('Error', 'Password must be at least 8 characters long!', 'error');
                return;
            }
        }
        
        $.ajax({
            url: './endpoint/edit-admin.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire('Success', response.message, 'success');
                    $('#editAdminModal').modal('hide');
                    $('#editAdminForm')[0].reset();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Network error. Please try again.', 'error');
            }
        });
    });
    
    // Handle change password button click
    $('.change-password').on('click', function() {
        const adminId = $(this).data('id');
        const adminName = $(this).data('name');
        
        $('#password_admin_id').val(adminId);
        $('#passwordAdminName').text(adminName);
        
        $('#changePasswordModal').modal('show');
    });
    
    // Handle change password form submission
    $('#changePasswordForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        
        // Validate passwords
        const password = $('#new_password').val();
        const confirmPassword = $('#confirm_new_password').val();
        
        if (password !== confirmPassword) {
            Swal.fire('Error', 'Passwords do not match!', 'error');
            return;
        }
        
        if (password.length < 8) {
            Swal.fire('Error', 'Password must be at least 8 characters long!', 'error');
            return;
        }
        
        $.ajax({
            url: './endpoint/change-admin-password.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire('Success', response.message, 'success');
                    $('#changePasswordModal').modal('hide');
                    $('#changePasswordForm')[0].reset();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Network error. Please try again.', 'error');
            }
        });
    });
    
    // Handle delete button click
    $('.delete-admin').on('click', function() {
        const adminId = $(this).data('id');
        const adminName = $(this).data('name');
        
        Swal.fire({
            title: 'Delete Administrator?',
            html: `Are you sure you want to delete <strong>${adminName}</strong>?<br>This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: './endpoint/delete-admin.php',
                    method: 'POST',
                    data: { admin_id: adminId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Deleted!', response.message, 'success');
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Network error. Please try again.', 'error');
                    }
                });
            }
        });
    });
});
</script>
</body>
</html>
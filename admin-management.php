<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: index.php");
    exit();
}

date_default_timezone_set('Asia/Manila');
include('./conn/conn.php');
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
        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        .section-badge {
            font-size: 0.75rem;
            padding: 3px 8px;
            border-radius: 10px;
            margin: 2px;
        }
        .section-list {
            max-height: 100px;
            overflow-y: auto;
        }
        .assigned-section-col {
            min-width: 150px;
        }
        .card-header .card-tools {
            position: absolute;
            right: 1rem;
            top: 1rem;
        }
        .modal-header .close {
            padding: 1rem;
            margin: -1rem -1rem -1rem auto;
        }
        .btn-xs {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            line-height: 1.5;
            border-radius: 0.2rem;
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
                                $stmt = $conn->prepare("SELECT COUNT(*) FROM tbl_users WHERE role != 'super_admin'");
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
                                $stmt = $conn->prepare("SELECT COUNT(DISTINCT user_id) FROM tbl_admin_sections");
                                $stmt->execute();
                                $adminsWithSections = $stmt->fetchColumn();
                                ?>
                                <h3><?php echo $adminsWithSections; ?></h3>
                                <p>Admins with Assigned Sections</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-tasks"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <?php
                                $stmt = $conn->prepare("SELECT COUNT(*) FROM tbl_admin_sections");
                                $stmt->execute();
                                $totalAssignments = $stmt->fetchColumn();
                                ?>
                                <h3><?php echo $totalAssignments; ?></h3>
                                <p>Total Section Assignments</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-list-check"></i>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-3 col-6">
                        <div class="small-box bg-gradient-secondary">
                            <div class="inner">
                                <?php
                                $stmt = $conn->prepare("SELECT COUNT(DISTINCT course_section) FROM tbl_admin_sections");
                                $stmt->execute();
                                $uniqueSections = $stmt->fetchColumn();
                                ?>
                                <h3><?php echo $uniqueSections; ?></h3>
                                <p>Unique Sections Assigned</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-layer-group"></i>
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
                                            <th class="assigned-section-col">Assigned Section(s)</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $stmt = $conn->prepare("
                                            SELECT u.*, 
                                                   GROUP_CONCAT(DISTINCT a.course_section ORDER BY a.assigned_at) as assigned_sections_list
                                            FROM tbl_users u
                                            LEFT JOIN tbl_admin_sections a ON u.user_id = a.user_id
                                            GROUP BY u.user_id
                                            ORDER BY u.role DESC, u.created_at DESC
                                        ");
                                        $stmt->execute();
                                        $admins = $stmt->fetchAll();
                                        
                                        foreach ($admins as $admin):
                                            $initials = strtoupper(substr($admin['full_name'], 0, 2));
                                            $roleClass = $admin['role'] === 'super_admin' ? 'danger' : 'primary';
                                            $createdDate = new DateTime($admin['created_at']);
                                            $assignedSections = $admin['assigned_sections_list'] ? explode(',', $admin['assigned_sections_list']) : [];
                                        ?>
                                        <tr>
                                            <td><?php echo $admin['user_id']; ?></td>
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
                                            <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $roleClass; ?> role-badge">
                                                    <?php echo ucfirst(str_replace('_', ' ', $admin['role'])); ?>
                                                </span>
                                            </td>
                                            <td class="assigned-section-col">
                                                <?php if (!empty($assignedSections)): ?>
                                                    <div class="section-list">
                                                        <?php foreach ($assignedSections as $section): ?>
                                                            <span class="badge badge-info section-badge" title="Assigned Section">
                                                                <?php echo htmlspecialchars($section); ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php elseif ($admin['role'] === 'admin'): ?>
                                                    <span class="badge badge-secondary">Not Assigned</span>
                                                <?php else: ?>
                                                    <span class="text-muted">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $createdDate->format('M d, Y'); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-sm btn-info edit-admin" 
                                                            data-id="<?php echo $admin['user_id']; ?>"
                                                            data-name="<?php echo htmlspecialchars($admin['full_name']); ?>"
                                                            data-username="<?php echo htmlspecialchars($admin['username']); ?>"
                                                            data-email="<?php echo htmlspecialchars($admin['email']); ?>"
                                                            data-role="<?php echo $admin['role']; ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-warning change-password" 
                                                            data-id="<?php echo $admin['user_id']; ?>"
                                                            data-name="<?php echo htmlspecialchars($admin['full_name']); ?>">
                                                        <i class="fas fa-key"></i>
                                                    </button>
                                                    <?php if ($admin['role'] === 'admin'): ?>
                                                    <button class="btn btn-sm btn-success assign-section" 
                                                            data-id="<?php echo $admin['user_id']; ?>"
                                                            data-name="<?php echo htmlspecialchars($admin['full_name']); ?>">
                                                        <i class="fas fa-tasks"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                    <?php if ($admin['user_id'] != $_SESSION['user_id'] && $admin['role'] != 'super_admin'): ?>
                                                    <button class="btn btn-sm btn-danger delete-admin" 
                                                            data-id="<?php echo $admin['user_id']; ?>"
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
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
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
                                <label for="email">Email Address *</label>
                                <input type="email" class="form-control" id="email" name="email" required>
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
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editAdminForm" method="POST">
                <input type="hidden" id="edit_user_id" name="user_id">
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
                                <label for="edit_email">Email Address *</label>
                                <input type="email" class="form-control" id="edit_email" name="email" required>
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
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="changePasswordForm" method="POST">
                <input type="hidden" id="password_user_id" name="user_id">
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

<!-- Assign Section Modal -->
<div class="modal fade" id="assignSectionModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title text-white">
                    <i class="fas fa-tasks mr-2"></i>Assign Section to Admin
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="assignSectionForm">
                <input type="hidden" id="assign_user_id" name="user_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="admin_name">Admin Name</label>
                                <input type="text" class="form-control" id="admin_name" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="course_section">Course Section *</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="course_section" name="course_section" 
                                           list="sectionSuggestions" required>
                                    <datalist id="sectionSuggestions"></datalist>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="button" id="refreshSections">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </div>
                                </div>
                                
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Currently Assigned Sections:</label>
                                <div id="currentSections" class="mt-2">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle mr-2"></i>
                                        Loading assigned sections...
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check mr-2"></i>Assign Section
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
        "order": [[0, 'desc']],
        "columnDefs": [
            { "orderable": false, "targets": [1, 7] } // Disable sorting on Admin and Actions columns
        ]
    });
    
    // Load available sections for datalist
    function loadAvailableSections() {
        $.ajax({
            url: 'endpoint/get-all-sections.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.sections.length > 0) {
                    let datalist = $('#sectionSuggestions');
                    datalist.empty();
                    response.sections.forEach(function(section) {
                        datalist.append(`<option value="${section}">`);
                    });
                }
            },
            error: function() {
                console.log('Failed to load sections list');
            }
        });
    }
    
    // Handle assign section button click
    $(document).on('click', '.assign-section', function() {
        const userId = $(this).data('id');
        const userName = $(this).data('name');
        
        $('#assign_user_id').val(userId);
        $('#admin_name').val(userName);
        
        // Load available sections and current assignments
        loadAvailableSections();
        loadAdminSections(userId);
        
        $('#assignSectionModal').modal('show');
    });
    
    // Refresh sections button
    $('#refreshSections').on('click', function() {
        loadAvailableSections();
        Swal.fire({
            icon: 'success',
            title: 'Refreshed!',
            text: 'Section list has been refreshed',
            timer: 1500,
            showConfirmButton: false
        });
    });
    
    // Load admin's assigned sections
    function loadAdminSections(userId) {
        $('#currentSections').html(`
            <div class="text-center py-3">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Loading assigned sections...</p>
            </div>
        `);
        
        $.ajax({
            url: 'endpoint/get-admin-sections.php',
            method: 'GET',
            data: { user_id: userId },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.sections.length > 0) {
                    let html = '<div class="list-group">';
                    response.sections.forEach(function(section, index) {
                        const assignedDate = new Date(section.assigned_at);
                        const formattedDate = assignedDate.toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                        
                        html += `
                            <div class="list-group-item ${index === 0 ? 'list-group-item-primary' : ''}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center">
                                            <span class="badge badge-info mr-2">${section.course_section}</span>
                                            ${index === 0 ? '<span class="badge badge-success ml-2">Primary</span>' : ''}
                                        </div>
                                        <small class="text-muted d-block mt-1">
                                            <i class="fas fa-user mr-1"></i> Assigned by: ${section.assigned_by_fullname || section.assigned_by_name || 'System'}
                                            <br>
                                            <i class="fas fa-clock mr-1"></i> ${formattedDate}
                                        </small>
                                    </div>
                                    <button class="btn btn-sm btn-danger remove-assignment" 
                                            data-id="${section.admin_section_id}"
                                            data-section="${section.course_section}"
                                            title="Remove this section assignment">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                    $('#currentSections').html(html);
                } else {
                    $('#currentSections').html(`
                        <div class="alert alert-warning">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-circle fa-2x mr-3"></i>
                                <div>
                                    <h6 class="mb-1">No Sections Assigned</h6>
                                    <p class="mb-0">This admin doesn't have any sections assigned yet.</p>
                                </div>
                            </div>
                        </div>
                    `);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading sections:', error);
                $('#currentSections').html(`
                    <div class="alert alert-danger">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-triangle fa-2x mr-3"></i>
                            <div>
                                <h6 class="mb-1">Failed to Load</h6>
                                <p class="mb-0">Could not load assigned sections. Please try again.</p>
                                <small class="text-muted">Error: ${error}</small>
                            </div>
                        </div>
                    </div>
                `);
            }
        });
    }
    
    // Handle assign section form submission
    $('#assignSectionForm').on('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        submitBtn.html('<i class="fas fa-spinner fa-spin mr-2"></i>Assigning...').prop('disabled', true);
        
        const formData = $(this).serialize();
        const userId = $('#assign_user_id').val();
        const sectionName = $('#course_section').val();
        
        $.ajax({
            url: 'endpoint/assign-section.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                submitBtn.html(originalText).prop('disabled', false);
                
                if (response.success) {
                    // Show success notification with SweetAlert
                    Swal.fire({
                        icon: 'success',
                        title: 'Section Assigned!',
                        html: `
                            <div class="text-center">
                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                <h5>${response.message}</h5>
                                <p class="text-muted">Section: <strong>${sectionName}</strong></p>
                                <div class="alert alert-success small mt-3">
                                    <i class="fas fa-info-circle"></i>
                                    Admin can now enroll students in this section.
                                </div>
                            </div>
                        `,
                        showConfirmButton: true,
                        confirmButtonText: 'OK',
                        timer: 4000
                    });
                    
                    // Clear the form
                    $('#course_section').val('');
                    
                    // Refresh the sections list
                    loadAdminSections(userId);
                    
                    // Reload the page after 2 seconds
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    // Show error notification
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        html: `
                            <div class="text-center">
                                <i class="fas fa-times-circle fa-3x text-danger mb-3"></i>
                                <h5>${response.message}</h5>
                            </div>
                        `,
                        showConfirmButton: true,
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function(xhr, status, error) {
                submitBtn.html(originalText).prop('disabled', false);
                
                // Show detailed error notification
                Swal.fire({
                    icon: 'error',
                    title: 'Request Failed!',
                    html: `
                        <div class="text-center">
                            <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                            <h5>Failed to assign section</h5>
                            <p>Please try again.</p>
                            <small class="text-muted">Error: ${error}</small>
                        </div>
                    `,
                    showConfirmButton: true,
                    confirmButtonText: 'OK'
                });
                
                console.error('Assign section error:', error);
            }
        });
    });
    
    // Handle remove assignment
    $(document).on('click', '.remove-assignment', function() {
        const assignmentId = $(this).data('id');
        const sectionName = $(this).data('section');
        const userId = $('#assign_user_id').val();
        
        Swal.fire({
            title: 'Remove Section Assignment?',
            html: `
                <div class="text-center">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                    <h5>Are you sure?</h5>
                    <p>This will remove <strong>${sectionName}</strong> from this admin's assigned sections.</p>
                    <div class="alert alert-warning small">
                        <i class="fas fa-info-circle"></i>
                        Students already enrolled in this section will keep their current section.
                    </div>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: '<i class="fas fa-trash mr-2"></i> Yes, remove it',
            cancelButtonText: '<i class="fas fa-times mr-2"></i> Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Removing Assignment...',
                    html: `
                        <div class="text-center">
                            <div class="spinner-border text-primary mb-3" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                            <p>Please wait while we remove the section assignment...</p>
                        </div>
                    `,
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => { Swal.showLoading(); }
                });
                
                $.ajax({
                    url: 'endpoint/remove-assignment.php',
                    method: 'POST',
                    data: { assignment_id: assignmentId },
                    dataType: 'json',
                    success: function(response) {
                        Swal.close();
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Assignment Removed!',
                                html: `
                                    <div class="text-center">
                                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                        <h5>${response.message}</h5>
                                        <p class="text-muted">Section: <strong>${sectionName}</strong></p>
                                    </div>
                                `,
                                showConfirmButton: true,
                                confirmButtonText: 'OK',
                                timer: 3000
                            });
                            loadAdminSections(userId);
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: response.message,
                                showConfirmButton: true,
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.close();
                        Swal.fire({
                            icon: 'error',
                            title: 'Failed to Remove',
                            html: `
                                <div class="text-center">
                                    <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                                    <h5>Could not remove the assignment</h5>
                                    <p>Please try again.</p>
                                    <small class="text-muted">Error: ${error}</small>
                                </div>
                            `,
                            showConfirmButton: true,
                            confirmButtonText: 'OK'
                        });
                    }
                });
            }
        });
    });
    
    // Handle add admin form submission
    $('#addAdminForm').on('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        submitBtn.html('<i class="fas fa-spinner fa-spin mr-2"></i>Creating...').prop('disabled', true);
        
        const formData = $(this).serialize();
        
        // Validate passwords match
        const password = $('#password').val();
        const confirmPassword = $('#confirm_password').val();
        
        if (password !== confirmPassword) {
            Swal.fire('Error', 'Passwords do not match!', 'error');
            submitBtn.html(originalText).prop('disabled', false);
            return;
        }
        
        if (password.length < 8) {
            Swal.fire('Error', 'Password must be at least 8 characters long!', 'error');
            submitBtn.html(originalText).prop('disabled', false);
            return;
        }
        
        $.ajax({
            url: 'endpoint/add-admin.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                submitBtn.html(originalText).prop('disabled', false);
                
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
                submitBtn.html(originalText).prop('disabled', false);
                Swal.fire('Error', 'Failed to create admin. Please try again.', 'error');
            }
        });
    });
    
    // Handle edit button click
    $('.edit-admin').on('click', function() {
        const userId = $(this).data('id');
        const userName = $(this).data('name');
        const username = $(this).data('username');
        const email = $(this).data('email');
        const role = $(this).data('role');
        
        $('#edit_user_id').val(userId);
        $('#edit_full_name').val(userName);
        $('#edit_username').val(username);
        $('#edit_email').val(email);
        $('#edit_role').val(role);
        
        // Clear password fields
        $('#edit_password').val('');
        $('#edit_confirm_password').val('');
        
        $('#editAdminModal').modal('show');
    });
    
    // Handle edit form submission
    $('#editAdminForm').on('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        submitBtn.html('<i class="fas fa-spinner fa-spin mr-2"></i>Updating...').prop('disabled', true);
        
        const formData = $(this).serialize();
        
        // Validate passwords if provided
        const password = $('#edit_password').val();
        const confirmPassword = $('#edit_confirm_password').val();
        
        if (password || confirmPassword) {
            if (password !== confirmPassword) {
                Swal.fire('Error', 'Passwords do not match!', 'error');
                submitBtn.html(originalText).prop('disabled', false);
                return;
            }
            
            if (password.length < 8) {
                Swal.fire('Error', 'Password must be at least 8 characters long!', 'error');
                submitBtn.html(originalText).prop('disabled', false);
                return;
            }
        }
        
        $.ajax({
            url: 'endpoint/edit-admin.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                submitBtn.html(originalText).prop('disabled', false);
                
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
                submitBtn.html(originalText).prop('disabled', false);
                Swal.fire('Error', 'Failed to update admin. Please try again.', 'error');
            }
        });
    });
    
    // Handle change password button click
    $('.change-password').on('click', function() {
        const userId = $(this).data('id');
        const userName = $(this).data('name');
        
        $('#password_user_id').val(userId);
        $('#passwordAdminName').text(userName);
        
        // Clear password fields
        $('#new_password').val('');
        $('#confirm_new_password').val('');
        
        $('#changePasswordModal').modal('show');
    });
    
    // Handle change password form submission
    $('#changePasswordForm').on('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        submitBtn.html('<i class="fas fa-spinner fa-spin mr-2"></i>Changing...').prop('disabled', true);
        
        const userId = $('#password_user_id').val();
        const password = $('#new_password').val();
        const confirmPassword = $('#confirm_new_password').val();
        
        // Validate passwords
        if (password !== confirmPassword) {
            Swal.fire('Error', 'Passwords do not match!', 'error');
            submitBtn.html(originalText).prop('disabled', false);
            return;
        }
        
        if (password.length < 8) {
            Swal.fire('Error', 'Password must be at least 8 characters long!', 'error');
            submitBtn.html(originalText).prop('disabled', false);
            return;
        }
        
        const formData = {
            user_id: userId,
            password: password,
            confirm_password: confirmPassword
        };
        
        $.ajax({
            url: 'endpoint/edit-admin.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                submitBtn.html(originalText).prop('disabled', false);
                
                if (response.success) {
                    Swal.fire('Success', 'Password changed successfully!', 'success');
                    $('#changePasswordModal').modal('hide');
                    $('#changePasswordForm')[0].reset();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function() {
                submitBtn.html(originalText).prop('disabled', false);
                Swal.fire('Error', 'Failed to change password. Please try again.', 'error');
            }
        });
    });
    
    // Handle delete button click
    $('.delete-admin').on('click', function() {
        const userId = $(this).data('id');
        const userName = $(this).data('name');
        
        Swal.fire({
            title: 'Delete Administrator?',
            html: `
                <div class="text-center">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                    <h5>Are you sure?</h5>
                    <p>This will permanently delete <strong>${userName}</strong>.</p>
                    <div class="alert alert-danger small">
                        <i class="fas fa-exclamation-circle"></i>
                        This action cannot be undone!
                    </div>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: '<i class="fas fa-trash mr-2"></i> Yes, delete it',
            cancelButtonText: '<i class="fas fa-times mr-2"></i> Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Deleting...',
                    html: `
                        <div class="text-center">
                            <div class="spinner-border text-danger mb-3" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                            <p>Please wait while we delete the administrator...</p>
                        </div>
                    `,
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => { Swal.showLoading(); }
                });
                
                $.ajax({
                    url: 'endpoint/delete-admin.php',
                    method: 'POST',
                    data: { user_id: userId },
                    dataType: 'json',
                    success: function(response) {
                        Swal.close();
                        
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                html: `
                                    <div class="text-center">
                                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                        <h5>${response.message}</h5>
                                    </div>
                                `,
                                showConfirmButton: true,
                                confirmButtonText: 'OK',
                                timer: 3000
                            });
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: response.message,
                                showConfirmButton: true,
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    error: function() {
                        Swal.close();
                        Swal.fire({
                            icon: 'error',
                            title: 'Failed to Delete',
                            text: 'Failed to delete admin. Please try again.',
                            showConfirmButton: true,
                            confirmButtonText: 'OK'
                        });
                    }
                });
            }
        });
    });
});
</script>
</body>
</html>
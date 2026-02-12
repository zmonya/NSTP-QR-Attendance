<?php
session_start();
require_once 'conn/conn.php'; // Updated path to conn.php inside conn folder

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Create upload directory if it doesn't exist
$upload_dir = 'uploads/profile_pictures/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Handle profile picture upload
if (isset($_POST['upload_picture'])) {
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_picture']['name'];
        $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($file_ext, $allowed)) {
            $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_ext;
            $target_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_path)) {
                // Get old profile picture to delete
                $stmt = $conn->prepare("SELECT profile_picture FROM tbl_users WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $old_picture = $stmt->fetchColumn();
                
                if ($old_picture && file_exists($old_picture)) {
                    unlink($old_picture);
                }
                
                // Update database
                $stmt = $conn->prepare("UPDATE tbl_users SET profile_picture = ? WHERE user_id = ?");
                
                if ($stmt->execute([$target_path, $user_id])) {
                    // Update session with new profile picture
                    $_SESSION['profile_picture'] = $target_path;
                    $message = "Profile picture updated successfully!";
                } else {
                    $error = "Error updating database.";
                }
            } else {
                $error = "Error uploading file.";
            }
        } else {
            $error = "Only JPG, JPEG, PNG & GIF files are allowed.";
        }
    } else {
        $error = "Please select an image file.";
    }
}

// Handle remove profile picture
if (isset($_POST['remove_picture'])) {
    $stmt = $conn->prepare("SELECT profile_picture FROM tbl_users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $old_picture = $stmt->fetchColumn();
    
    if ($old_picture && file_exists($old_picture)) {
        unlink($old_picture);
    }
    
    $stmt = $conn->prepare("UPDATE tbl_users SET profile_picture = NULL WHERE user_id = ?");
    
    if ($stmt->execute([$user_id])) {
        // Remove from session
        $_SESSION['profile_picture'] = null;
        unset($_SESSION['profile_picture']);
        $message = "Profile picture removed successfully!";
    } else {
        $error = "Error removing profile picture.";
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Get current password hash
    $stmt = $conn->prepare("SELECT password_hash FROM tbl_users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch();
    
    // Verify current password
    if ($user_data && password_verify($current_password, $user_data['password_hash'])) {
        if ($new_password === $confirm_password) {
            if (strlen($new_password) >= 8) {
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE tbl_users SET password_hash = ?, last_password_change = NOW() WHERE user_id = ?");
                
                if ($stmt->execute([$new_password_hash, $user_id])) {
                    $message = "Password changed successfully!";
                } else {
                    $error = "Error changing password.";
                }
            } else {
                $error = "Password must be at least 8 characters long.";
            }
        } else {
            $error = "New passwords do not match.";
        }
    } else {
        $error = "Current password is incorrect.";
    }
}

// Handle profile info update
if (isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    
    // Check if username/email already exists (except current user)
    $check_sql = "SELECT user_id FROM tbl_users WHERE (username = ? OR email = ?) AND user_id != ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->execute([$username, $email, $user_id]);
    
    if ($check_stmt->rowCount() == 0) {
        $update_sql = "UPDATE tbl_users SET full_name = ?, email = ?, username = ? WHERE user_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        
        if ($update_stmt->execute([$full_name, $email, $username, $user_id])) {
            $_SESSION['full_name'] = $full_name;
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            $message = "Profile updated successfully!";
        } else {
            $error = "Error updating profile.";
        }
    } else {
        $error = "Username or email already exists.";
    }
}

// Get user data
$stmt = $conn->prepare("SELECT * FROM tbl_users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get user's assigned sections from tbl_admin_sections
$sections = [];
if ($user && $user['role'] == 'admin') {
    $section_sql = "SELECT course_section FROM tbl_admin_sections WHERE user_id = ?";
    $section_stmt = $conn->prepare($section_sql);
    $section_stmt->execute([$user_id]);
    $sections = $section_stmt->fetchAll();
}

// Get total students managed by this user
$student_count = 0;
$student_sql = "SELECT COUNT(*) as total FROM tbl_student WHERE created_by = ?";
$student_stmt = $conn->prepare($student_sql);
$student_stmt->execute([$user_id]);
$student_count = $student_stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - QR Attendance System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            border-radius: 10px 10px 0 0;
            color: white;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            object-fit: cover;
            margin: 0 auto;
        }
        
        .profile-avatar-initials {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid white;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: bold;
            color: white;
            margin: 0 auto;
        }
        
        .upload-overlay {
            position: relative;
            display: inline-block;
        }
        
        .upload-btn {
            position: absolute;
            bottom: 0;
            right: 0;
            background: #007bff;
            color: white;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 2px solid white;
            transition: all 0.3s;
        }
        
        .upload-btn:hover {
            background: #0056b3;
            transform: scale(1.1);
        }
        
        .remove-btn {
            position: absolute;
            bottom: 0;
            left: 0;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 2px solid white;
            transition: all 0.3s;
        }
        
        .remove-btn:hover {
            background: #c82333;
            transform: scale(1.1);
        }
        
        .password-strength {
            height: 5px;
            margin-top: 5px;
            transition: all 0.3s;
        }
        
        .nav-tabs .nav-link.active {
            border-bottom: 3px solid #007bff;
            font-weight: bold;
            color: #007bff;
        }
        
        .card {
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: none;
        }
        
        .info-item {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #555;
            font-size: 0.9rem;
        }
        
        .info-value {
            color: #333;
            font-size: 1rem;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .stat-icon {
            font-size: 40px;
            opacity: 0.8;
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: bold;
        }
        
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
        
        @media (max-width: 768px) {
            .profile-header {
                padding: 20px;
            }
            
            .profile-avatar, .profile-avatar-initials {
                width: 100px;
                height: 100px;
                font-size: 40px;
            }
            
            .stat-card {
                margin-bottom: 15px;
            }
        }
        
        .timeline {
            position: relative;
            padding: 20px 0;
        }
        
        .timeline-item {
            position: relative;
            padding-left: 50px;
            margin-bottom: 20px;
        }
        
        .timeline-badge {
            position: absolute;
            left: 0;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #007bff;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .time-label {
            margin-bottom: 20px;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #6c757d;
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        .badge {
            padding: 5px 10px;
            border-radius: 5px;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <!-- Include your sidebar -->
        <?php include 'adminlte-sidebar.php'; ?>
        
        <div class="content-wrapper">
            <!-- Content Header -->
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1><i class="fas fa-user-circle mr-2"></i>My Profile</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-right">
                                <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                                <li class="breadcrumb-item active">My Profile</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <?php if ($message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($message); ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <!-- Profile Card -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="profile-header text-center">
                                    <div class="upload-overlay">
                                        <?php if (!empty($user['profile_picture']) && file_exists($user['profile_picture'])): ?>
                                            <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>?v=<?php echo time(); ?>" 
                                                 alt="Profile Picture" 
                                                 class="profile-avatar"
                                                 id="profileImage">
                                        <?php else: ?>
                                            <?php 
                                            $initials = '';
                                            if (!empty($user['full_name'])) {
                                                $nameParts = explode(' ', $user['full_name']);
                                                $initials = strtoupper(substr($nameParts[0], 0, 1));
                                                if (isset($nameParts[1])) {
                                                    $initials .= strtoupper(substr($nameParts[1], 0, 1));
                                                }
                                            }
                                            ?>
                                            <div class="profile-avatar-initials" id="profileInitials">
                                                <?php echo $initials; ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <label for="profilePictureInput" class="upload-btn" title="Change Profile Picture">
                                            <i class="fas fa-camera"></i>
                                        </label>
                                        
                                        <?php if (!empty($user['profile_picture'])): ?>
                                            <form method="POST" style="display: inline;" id="removePictureForm">
                                                <button type="submit" name="remove_picture" class="remove-btn" title="Remove Profile Picture" onclick="return confirm('Are you sure you want to remove your profile picture?');">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <h4 class="mt-3"><?php echo htmlspecialchars($user['full_name'] ?? 'User'); ?></h4>
                                    <p class="mb-0">
                                        <span class="badge badge-light">
                                            <i class="fas fa-shield-alt mr-1"></i>
                                            <?php echo ucfirst(str_replace('_', ' ', $user['role'] ?? 'admin')); ?>
                                        </span>
                                    </p>
                                </div>
                                
                                <div class="card-body">
                                    <form id="uploadForm" action="" method="POST" enctype="multipart/form-data" style="display: none;">
                                        <input type="file" id="profilePictureInput" name="profile_picture" accept="image/*">
                                        <input type="hidden" name="upload_picture" value="1">
                                    </form>
                                    
                                    <div class="row mb-4">
                                        <div class="col-6">
                                            <div class="stat-card text-center">
                                                <i class="fas fa-users stat-icon mb-2"></i>
                                                <div class="stat-number"><?php echo $student_count; ?></div>
                                                <div class="stat-label">Students Managed</div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="stat-card" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                                                <i class="fas fa-calendar-check stat-icon mb-2"></i>
                                                <div class="stat-number"><?php echo date('Y'); ?></div>
                                                <div class="stat-label">Current Year</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <h6 class="text-primary"><i class="fas fa-info-circle mr-2"></i>Account Information</h6>
                                    
                                    <div class="info-item">
                                        <div class="info-label">
                                            <i class="fas fa-user mr-1"></i> Username
                                        </div>
                                        <div class="info-value"><?php echo htmlspecialchars($user['username'] ?? ''); ?></div>
                                    </div>
                                    
                                    <div class="info-item">
                                        <div class="info-label">
                                            <i class="fas fa-envelope mr-1"></i> Email
                                        </div>
                                        <div class="info-value"><?php echo htmlspecialchars($user['email'] ?? ''); ?></div>
                                    </div>
                                    
                                    <?php if ($user && $user['role'] == 'admin' && !empty($sections)): ?>
                                    <div class="info-item">
                                        <div class="info-label">
                                            <i class="fas fa-book-open mr-1"></i> Assigned Sections
                                        </div>
                                        <div class="info-value">
                                            <?php foreach ($sections as $section): ?>
                                                <span class="badge badge-info mr-1">
                                                    <?php echo htmlspecialchars($section['course_section']); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="info-item">
                                        <div class="info-label">
                                            <i class="fas fa-calendar-alt mr-1"></i> Member Since
                                        </div>
                                        <div class="info-value">
                                            <?php echo date('F d, Y', strtotime($user['created_at'] ?? date('Y-m-d'))); ?>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($user['last_password_change'])): ?>
                                    <div class="info-item">
                                        <div class="info-label">
                                            <i class="fas fa-clock mr-1"></i> Last Password Change
                                        </div>
                                        <div class="info-value">
                                            <?php echo date('F d, Y', strtotime($user['last_password_change'])); ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Edit Profile Tabs -->
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header bg-white">
                                    <ul class="nav nav-tabs card-header-tabs" id="profileTabs" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active" id="profile-tab" data-toggle="tab" href="#profile" role="tab">
                                                <i class="fas fa-user-edit mr-2"></i>Edit Profile
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" id="password-tab" data-toggle="tab" href="#password" role="tab">
                                                <i class="fas fa-lock mr-2"></i>Change Password
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" id="activity-tab" data-toggle="tab" href="#activity" role="tab">
                                                <i class="fas fa-history mr-2"></i>Recent Activity
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                                
                                <div class="card-body">
                                    <div class="tab-content">
                                        <!-- Edit Profile Tab -->
                                        <div class="tab-pane fade show active" id="profile" role="tabpanel">
                                            <form action="" method="POST" id="profileForm">
                                                <div class="form-group">
                                                    <label for="full_name">Full Name</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text">
                                                                <i class="fas fa-user"></i>
                                                            </span>
                                                        </div>
                                                        <input type="text" class="form-control" id="full_name" name="full_name" 
                                                               value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                                                    </div>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="username">Username</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text">
                                                                <i class="fas fa-at"></i>
                                                            </span>
                                                        </div>
                                                        <input type="text" class="form-control" id="username" name="username" 
                                                               value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
                                                    </div>
                                                    <small class="text-muted">Username must be unique</small>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="email">Email Address</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text">
                                                                <i class="fas fa-envelope"></i>
                                                            </span>
                                                        </div>
                                                        <input type="email" class="form-control" id="email" name="email" 
                                                               value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                                                    </div>
                                                </div>
                                                
                                                <button type="submit" name="update_profile" class="btn btn-primary">
                                                    <i class="fas fa-save mr-2"></i>Save Changes
                                                </button>
                                                <button type="reset" class="btn btn-secondary">
                                                    <i class="fas fa-undo mr-2"></i>Reset
                                                </button>
                                            </form>
                                        </div>
                                        
                                        <!-- Change Password Tab -->
                                        <div class="tab-pane fade" id="password" role="tabpanel">
                                            <form action="" method="POST" id="passwordForm">
                                                <div class="form-group">
                                                    <label for="current_password">Current Password</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text">
                                                                <i class="fas fa-lock"></i>
                                                            </span>
                                                        </div>
                                                        <input type="password" class="form-control" id="current_password" 
                                                               name="current_password" required>
                                                        <div class="input-group-append">
                                                            <span class="input-group-text toggle-password" style="cursor: pointer;">
                                                                <i class="fas fa-eye"></i>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="new_password">New Password</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text">
                                                                <i class="fas fa-key"></i>
                                                            </span>
                                                        </div>
                                                        <input type="password" class="form-control" id="new_password" 
                                                               name="new_password" required>
                                                        <div class="input-group-append">
                                                            <span class="input-group-text toggle-password" style="cursor: pointer;">
                                                                <i class="fas fa-eye"></i>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="password-strength mt-2">
                                                        <div class="progress" style="height: 5px;">
                                                            <div class="progress-bar" id="passwordStrengthBar" 
                                                                 role="progressbar" style="width: 0%;" 
                                                                 aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                    </div>
                                                    <small class="text-muted">
                                                        Password must be at least 8 characters long.
                                                    </small>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="confirm_password">Confirm New Password</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text">
                                                                <i class="fas fa-check-circle"></i>
                                                            </span>
                                                        </div>
                                                        <input type="password" class="form-control" id="confirm_password" 
                                                               name="confirm_password" required>
                                                        <div class="input-group-append">
                                                            <span class="input-group-text toggle-password" style="cursor: pointer;">
                                                                <i class="fas fa-eye"></i>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <small id="passwordMatchMsg" class="form-text"></small>
                                                </div>
                                                
                                                <button type="submit" name="change_password" class="btn btn-primary" id="changePasswordBtn">
                                                    <i class="fas fa-key mr-2"></i>Change Password
                                                </button>
                                            </form>
                                        </div>
                                        
                                        <!-- Recent Activity Tab -->
                                        <div class="tab-pane fade" id="activity" role="tabpanel">
                                            <?php
                                            // Get recent attendance activity
                                            $activity_sql = "
                                                SELECT a.*, s.student_name, s.course_section 
                                                FROM tbl_attendance a 
                                                JOIN tbl_student s ON a.tbl_student_id = s.tbl_student_id 
                                                WHERE s.created_by = ? 
                                                ORDER BY a.time_in DESC 
                                                LIMIT 10
                                            ";
                                            $activity_stmt = $conn->prepare($activity_sql);
                                            $activity_stmt->execute([$user_id]);
                                            $activities = $activity_stmt->fetchAll();
                                            ?>
                                            
                                            <?php if (count($activities) > 0): ?>
                                                <div class="timeline">
                                                    <?php 
                                                    $current_date = '';
                                                    foreach ($activities as $activity): 
                                                        $activity_date = date('Y-m-d', strtotime($activity['time_in']));
                                                    ?>
                                                        <?php if ($activity_date != $current_date): ?>
                                                            <?php $current_date = $activity_date; ?>
                                                            <div class="time-label">
                                                                <span class="bg-primary px-3 py-1 rounded text-white">
                                                                    <i class="fas fa-calendar mr-1"></i>
                                                                    <?php echo date('F d, Y', strtotime($activity_date)); ?>
                                                                </span>
                                                            </div>
                                                        <?php endif; ?>
                                                        
                                                        <div class="timeline-item">
                                                            <div class="timeline-badge">
                                                                <i class="fas fa-clock"></i>
                                                            </div>
                                                            <div class="card card-widget">
                                                                <div class="card-header">
                                                                    <div class="user-block">
                                                                        <span class="username">
                                                                            <strong><?php echo htmlspecialchars($activity['student_name']); ?></strong>
                                                                        </span>
                                                                        <span class="description">
                                                                            <i class="fas fa-clock mr-1"></i>
                                                                            <?php echo date('h:i A', strtotime($activity['time_in'])); ?>
                                                                        </span>
                                                                    </div>
                                                                    <div class="card-tools">
                                                                        <span class="badge badge-<?php echo $activity['status'] == 'On Time' ? 'success' : 'warning'; ?>">
                                                                            <?php echo $activity['status']; ?>
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                                <div class="card-body">
                                                                    <p>
                                                                        <i class="fas fa-book-open mr-1"></i>
                                                                        <?php echo htmlspecialchars($activity['course_section']); ?>
                                                                    </p>
                                                                    <?php if (!empty($activity['notes'])): ?>
                                                                        <p class="text-muted mb-0">
                                                                            <i class="fas fa-sticky-note mr-1"></i>
                                                                            <?php echo htmlspecialchars($activity['notes']); ?>
                                                                        </p>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="text-center py-5">
                                                    <i class="fas fa-history fa-4x text-muted mb-3"></i>
                                                    <h5 class="text-muted">No recent activity found</h5>
                                                    <p class="text-muted">Your attendance records will appear here</p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        
        <!-- Footer -->
        <footer class="main-footer">
            <div class="float-right d-none d-sm-block">
                <b>Version</b> 1.0.0
            </div>
            <strong>Copyright &copy; <?php echo date('Y'); ?> QR Attendance System.</strong> All rights reserved.
        </footer>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Toggle password visibility
            $('.toggle-password').click(function() {
                const input = $(this).closest('.input-group').find('input');
                const icon = $(this).find('i');
                
                if (input.attr('type') === 'password') {
                    input.attr('type', 'text');
                    icon.removeClass('fa-eye').addClass('fa-eye-slash');
                } else {
                    input.attr('type', 'password');
                    icon.removeClass('fa-eye-slash').addClass('fa-eye');
                }
            });
            
            // Auto upload when file is selected
            $('#profilePictureInput').change(function() {
                $('#uploadForm').submit();
            });
            
            // Password strength checker
            $('#new_password').on('keyup', function() {
                const password = $(this).val();
                const strengthBar = $('#passwordStrengthBar');
                let strength = 0;
                
                if (password.length >= 8) strength += 40;
                if (password.match(/[A-Z]/)) strength += 15;
                if (password.match(/[a-z]/)) strength += 15;
                if (password.match(/[0-9]/)) strength += 15;
                if (password.match(/[^A-Za-z0-9]/)) strength += 15;
                
                strength = Math.min(strength, 100);
                strengthBar.css('width', strength + '%').attr('aria-valuenow', strength);
                
                if (strength < 40) {
                    strengthBar.removeClass('bg-success bg-warning').addClass('bg-danger');
                } else if (strength < 70) {
                    strengthBar.removeClass('bg-success bg-danger').addClass('bg-warning');
                } else {
                    strengthBar.removeClass('bg-danger bg-warning').addClass('bg-success');
                }
            });
            
            // Password match checker
            $('#confirm_password').on('keyup', function() {
                const password = $('#new_password').val();
                const confirm = $(this).val();
                const msg = $('#passwordMatchMsg');
                
                if (password === confirm) {
                    msg.html('<span class="text-success"><i class="fas fa-check mr-1"></i>Passwords match</span>');
                    $('#changePasswordBtn').prop('disabled', false);
                } else {
                    msg.html('<span class="text-danger"><i class="fas fa-times mr-1"></i>Passwords do not match</span>');
                    $('#changePasswordBtn').prop('disabled', true);
                }
            });
            
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
            
            // Form validation
            $('#profileForm').submit(function(e) {
                const email = $('#email').val();
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                
                if (!emailRegex.test(email)) {
                    e.preventDefault();
                    alert('Please enter a valid email address.');
                }
            });
            
            $('#passwordForm').submit(function(e) {
                if ($('#changePasswordBtn').prop('disabled')) {
                    e.preventDefault();
                    alert('Please make sure your passwords match.');
                }
            });
        });
        
        // Force sidebar to update after profile picture upload
        <?php if ($message && (strpos($message, 'Profile picture updated') !== false || strpos($message, 'Profile picture removed') !== false)): ?>
        setTimeout(function() {
            // Try to update sidebar image without page reload
            $('.user-panel .image img').each(function() {
                var src = $(this).attr('src').split('?')[0];
                $(this).attr('src', src + '?v=' + new Date().getTime());
            });
            
            // If it's showing initials, reload the page to show the image
            if ($('.user-panel .image div').length > 0) {
                location.reload();
            }
        }, 1500);
        <?php endif; ?>
    </script>
</body>
</html>
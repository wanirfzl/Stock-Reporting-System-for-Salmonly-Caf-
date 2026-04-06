<?php
// profile.php
define('ACCESS_ALLOWED', true);
require_once 'config/database.php';
requireLogin();

$page_title = 'My Profile';
$success_message = '';
$error_message = '';

// Get current user data from database
$conn = getConnection();
$user_id = $_SESSION['user_id'];
$result = mysqli_query($conn, "SELECT * FROM users WHERE user_id = $user_id");
$user = mysqli_fetch_assoc($result);
mysqli_close($conn);

// Format for display and set default values
$user['name'] = $user['full_name'];
$user['join_date'] = date('F j, Y', strtotime($user['join_date']));
$user['phone'] = $user['phone'] ?? '-';
$user['email'] = $user['email'] ?? $_SESSION['user_email'] ?? '';
$user['role'] = $user['role'] ?? $_SESSION['user_role'] ?? 'Staff';
$user['profile_picture'] = $user['profile_picture'] ?? 'default-avatar.png';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getConnection();
    $user_id = $_SESSION['user_id'];
    
    if (isset($_POST['update_profile'])) {
        $new_name = mysqli_real_escape_string($conn, $_POST['name'] ?? '');
        $new_phone = mysqli_real_escape_string($conn, $_POST['phone'] ?? '');
        
        if (empty($new_name)) {
            $error_message = 'Name is required';
        } else {
            $update = "UPDATE users SET full_name = '$new_name', phone = '$new_phone' WHERE user_id = $user_id";
            if (mysqli_query($conn, $update)) {
                $_SESSION['user_name'] = $new_name;
                $success_message = 'Profile updated successfully!';
                // Refresh user data
                $result = mysqli_query($conn, "SELECT * FROM users WHERE user_id = $user_id");
                $user = mysqli_fetch_assoc($result);
                $user['name'] = $user['full_name'];
                $user['join_date'] = date('F j, Y', strtotime($user['join_date']));
                $user['phone'] = $user['phone'] ?? '-';
                $user['email'] = $user['email'] ?? $_SESSION['user_email'] ?? '';
                $user['role'] = $user['role'] ?? $_SESSION['user_role'] ?? 'Staff';
                $user['profile_picture'] = $user['profile_picture'] ?? 'default-avatar.png';
            } else {
                $error_message = 'Update failed: ' . mysqli_error($conn);
            }
        }
    }
    
    if (isset($_POST['change_password'])) {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        
        $result = mysqli_query($conn, "SELECT password FROM users WHERE user_id = $user_id");
        $row = mysqli_fetch_assoc($result);
        
        if (empty($current) || empty($new) || empty($confirm)) {
            $error_message = 'All password fields are required';
        } elseif (!password_verify($current, $row['password'])) {
            $error_message = 'Current password is incorrect';
        } elseif ($new !== $confirm) {
            $error_message = 'New passwords do not match';
        } elseif (strlen($new) < 6) {
            $error_message = 'Password must be at least 6 characters';
        } else {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $update = "UPDATE users SET password = '$hashed' WHERE user_id = $user_id";
            if (mysqli_query($conn, $update)) {
                $success_message = 'Password changed successfully!';
            } else {
                $error_message = 'Update failed';
            }
        }
    }
    
    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $target_dir = "uploads/profiles/";
        
        // Create directory if not exists
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_extension, $allowed_types)) {
            $new_filename = "user_" . $user_id . "_" . time() . "." . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
                // Delete old profile picture if not default and if it exists as a file
                if ($user['profile_picture'] != 'default-avatar.png' && !empty($user['profile_picture'])) {
                    $old_file = $target_dir . $user['profile_picture'];
                    if (file_exists($old_file) && is_file($old_file)) {
                        unlink($old_file);
                    }
                }
                
                $update = "UPDATE users SET profile_picture = '$new_filename' WHERE user_id = $user_id";
                if (mysqli_query($conn, $update)) {
                    $success_message = 'Profile picture updated successfully!';
                    $user['profile_picture'] = $new_filename;
                } else {
                    $error_message = 'Failed to save profile picture';
                }
            } else {
                $error_message = 'Failed to upload image';
            }
        } else {
            $error_message = 'Only JPG, JPEG, PNG & GIF files are allowed';
        }
    }
    
    mysqli_close($conn);
}

// Include correct header based on user role
if ($_SESSION['user_role'] === 'Administrator') {
    include 'includes/header-admin.php';
} else {
    include 'includes/header.php';
}
?>

<main class="px-6 md:px-10 py-8 max-w-4xl mx-auto w-full">
    <!-- Page Title -->
    <div class="mb-8">
        <h1 class="text-3xl md:text-4xl font-black text-[#3C2A21]">My Profile</h1>
        <p class="text-[#3C2A21]/60 mt-2">Manage your personal information</p>
    </div>

    <!-- Success/Error Messages -->
    <?php if ($success_message): ?>
    <div class="alert alert-success mb-6">
        <?php echo $success_message; ?>
    </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
    <div class="alert alert-error mb-6">
        <?php echo $error_message; ?>
    </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Profile Summary Card -->
        <div class="md:col-span-1">
            <div class="bg-[#FAF9F6] rounded-2xl border border-[#3C2A21]/5 card-shadow p-6 text-center">
                <!-- Profile Picture with Upload -->
                <div class="relative inline-block">
                    <div class="size-24 rounded-full border-4 border-[#8B5A2B] overflow-hidden mx-auto mb-4">
                        <?php 
                        $profile_pic_path = "uploads/profiles/" . $user['profile_picture'];
                        if ($user['profile_picture'] != 'default-avatar.png' && file_exists($profile_pic_path) && is_file($profile_pic_path)) {
                            echo '<img src="' . $profile_pic_path . '?t=' . time() . '" alt="Profile" class="w-full h-full object-cover">';
                        } else {
                            echo '<img src="https://ui-avatars.com/api/?name=' . urlencode($user['name']) . '&background=8B5A2B&color=fff&size=128" alt="Profile" class="w-full h-full object-cover">';
                        }
                        ?>
                    </div>
                    <form method="POST" action="" enctype="multipart/form-data" class="mt-2">
                        <label class="cursor-pointer bg-[#F2E8DF] hover:bg-[#E6B17E] text-[#3C2A21] text-xs font-bold px-3 py-1 rounded-full transition-colors inline-flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">upload</span>
                            Change Photo
                            <input type="file" name="profile_picture" accept="image/*" class="hidden" onchange="this.form.submit()">
                        </label>
                    </form>
                </div>
                
                <h2 class="text-xl font-bold text-[#3C2A21] mt-2"><?php echo htmlspecialchars($user['name']); ?></h2>
                <p class="text-[#8B5A2B] font-medium text-sm mt-1"><?php echo htmlspecialchars($user['role']); ?></p>
                
                <div class="mt-6 pt-6 border-t border-[#3C2A21]/5">
                    <div class="flex items-center justify-between text-sm mb-3">
                        <span class="text-[#3C2A21]/60">Member since</span>
                        <span class="font-medium"><?php echo htmlspecialchars($user['join_date']); ?></span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-[#3C2A21]/60">Email</span>
                        <span class="font-medium"><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Profile Form -->
        <div class="md:col-span-2 space-y-6">
            <!-- Edit Profile -->
            <div class="bg-[#FAF9F6] rounded-2xl border border-[#3C2A21]/5 card-shadow p-6">
                <h3 class="text-lg font-bold text-[#3C2A21] mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#8B5A2B]">edit</span>
                    Edit Profile
                </h3>
                
                <form method="POST" action="" class="space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-[#3C2A21]/60 mb-2">Full Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" 
                               class="form-input" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold text-[#3C2A21]/60 mb-2">Email (cannot be changed)</label>
                        <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" 
                               class="form-input bg-[#F2E8DF]/50" disabled>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold text-[#3C2A21]/60 mb-2">Phone Number</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] !== '-' ? $user['phone'] : ''); ?>" 
                               class="form-input" placeholder="e.g., +60 12-345 6789">
                        <p class="text-xs text-[#3C2A21]/40 mt-1">Optional</p>
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn-primary">
                        Update Profile
                    </button>
                </form>
            </div>

            <!-- Change Password -->
            <div class="bg-[#FAF9F6] rounded-2xl border border-[#3C2A21]/5 card-shadow p-6">
                <h3 class="text-lg font-bold text-[#3C2A21] mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-[#8B5A2B]">lock</span>
                    Change Password
                </h3>
                
                <form method="POST" action="" class="space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-[#3C2A21]/60 mb-2">Current Password</label>
                        <input type="password" name="current_password" class="form-input" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold text-[#3C2A21]/60 mb-2">New Password</label>
                        <input type="password" name="new_password" class="form-input" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold text-[#3C2A21]/60 mb-2">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-input" required>
                    </div>
                    
                    <button type="submit" name="change_password" class="btn-secondary">
                        Change Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

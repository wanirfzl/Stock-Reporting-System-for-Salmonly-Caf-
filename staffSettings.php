<?php
// staffSettings.php
define('ACCESS_ALLOWED', true);
require_once 'config/database.php';
requireLogin();

$page_title = 'Settings';

// Get current user data
$conn = getConnection();
$user_id = $_SESSION['user_id'];

// Get user data
$user_result = mysqli_query($conn, "SELECT * FROM users WHERE user_id = $user_id");
$current_user = mysqli_fetch_assoc($user_result);

// Check if user has settings
$settings_check = mysqli_query($conn, "SELECT * FROM user_settings WHERE user_id = $user_id");
$has_settings = mysqli_num_rows($settings_check) > 0;
$user_settings = mysqli_fetch_assoc($settings_check);

// Handle form submissions
$success_message = '';
$error_message = '';

// Handle notification settings update (only low stock alerts)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_notifications'])) {
    $low_stock_alerts = isset($_POST['low_stock_alerts']) ? 1 : 0;
    
    if ($has_settings) {
        $update = "UPDATE user_settings SET low_stock_alerts = '$low_stock_alerts' WHERE user_id = $user_id";
    } else {
        $update = "INSERT INTO user_settings (user_id, low_stock_alerts) VALUES ($user_id, $low_stock_alerts)";
    }
    
    if (mysqli_query($conn, $update)) {
        $success_message = 'Notification settings updated successfully!';
        $has_settings = true;
        $settings_result = mysqli_query($conn, "SELECT * FROM user_settings WHERE user_id = $user_id");
        $user_settings = mysqli_fetch_assoc($settings_result);
    } else {
        $error_message = 'Update failed: ' . mysqli_error($conn);
    }
}

// Handle appearance settings update (only theme)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_appearance'])) {
    $theme = mysqli_real_escape_string($conn, $_POST['theme'] ?? 'light');
    
    if ($has_settings) {
        $update = "UPDATE user_settings SET theme='$theme' WHERE user_id=$user_id";
    } else {
        $update = "INSERT INTO user_settings (user_id, theme) VALUES ($user_id, '$theme')";
    }
    
    if (mysqli_query($conn, $update)) {
        $success_message = 'Appearance settings updated successfully!';
        $has_settings = true;
        $settings_result = mysqli_query($conn, "SELECT * FROM user_settings WHERE user_id = $user_id");
        $user_settings = mysqli_fetch_assoc($settings_result);
    } else {
        $error_message = 'Update failed';
    }
}

// Handle profile update from settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile_settings'])) {
    $new_name = mysqli_real_escape_string($conn, $_POST['name'] ?? '');
    $new_phone = mysqli_real_escape_string($conn, $_POST['phone'] ?? '');
    
    if (empty($new_name)) {
        $error_message = 'Name is required';
    } else {
        $update = "UPDATE users SET full_name = '$new_name', phone = '$new_phone' WHERE user_id = $user_id";
        if (mysqli_query($conn, $update)) {
            $_SESSION['user_name'] = $new_name;
            $success_message = 'Profile updated successfully!';
            $user_result = mysqli_query($conn, "SELECT * FROM users WHERE user_id = $user_id");
            $current_user = mysqli_fetch_assoc($user_result);
        } else {
            $error_message = 'Update failed';
        }
    }
}

// Handle reset settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_settings'])) {
    $reset = "UPDATE user_settings SET theme='light', low_stock_alerts=1 WHERE user_id=$user_id";
    if (mysqli_query($conn, $reset)) {
        $success_message = 'Settings reset to default!';
        $theme = 'light';
        $low_stock_alert = 1;
        // Refresh user settings
        $settings_result = mysqli_query($conn, "SELECT * FROM user_settings WHERE user_id = $user_id");
        $user_settings = mysqli_fetch_assoc($settings_result);
    } else {
        $error_message = 'Reset failed';
    }
}

// Refresh settings after update
if ($has_settings) {
    $settings_result = mysqli_query($conn, "SELECT * FROM user_settings WHERE user_id = $user_id");
    $user_settings = mysqli_fetch_assoc($settings_result);
}

// Get low stock count for notification
$low_stock_count_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM products WHERE current_stock <= reorder_level");
$low_stock_count = mysqli_fetch_assoc($low_stock_count_result)['count'];
$critical_stock_count_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM products WHERE current_stock <= reorder_level/2");
$critical_stock_count = mysqli_fetch_assoc($critical_stock_count_result)['count'];

// Get database last updated info
$db_update_result = mysqli_query($conn, "SELECT MAX(updated_at) as last_update FROM products");
$last_db_update = mysqli_fetch_assoc($db_update_result)['last_update'];
$last_updated = $last_db_update ? date('d/m/Y H:i:s', strtotime($last_db_update)) : 'Never';

// Get total records
$total_products = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM products"))['total'];
$total_transactions = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM stock_transactions"))['total'];
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role != 'Administrator'"))['total'];

mysqli_close($conn);

// Set default values
$low_stock_alert = isset($user_settings['low_stock_alerts']) ? $user_settings['low_stock_alerts'] : 1;
$theme = isset($user_settings['theme']) ? $user_settings['theme'] : 'light';

include 'includes/header.php';
?>

<main class="px-6 md:px-10 py-8 max-w-4xl mx-auto w-full">
    <!-- Page Title -->
    <div class="mb-8">
        <h1 class="text-3xl md:text-4xl font-black text-[#3C2A21]">Settings</h1>
        <p class="text-[#3C2A21]/60 mt-2">Customize your application preferences</p>
    </div>

    <!-- Notification Bar - Low Stock Alert -->
    <?php if ($low_stock_count > 0 && $low_stock_alert == 1): ?>
    <div class="bg-[#B85C38]/10 border border-[#B85C38] text-[#B85C38] px-6 py-4 rounded-2xl mb-6 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="material-symbols-outlined text-[#B85C38]">warning</span>
            <div>
                <span class="font-bold">Stock Alert!</span>
                <span class="ml-2"><?php echo $low_stock_count; ?> item(s) are running low</span>
                <?php if ($critical_stock_count > 0): ?>
                <span class="ml-2 text-[#B85C38] font-bold">(<?php echo $critical_stock_count; ?> critical)</span>
                <?php endif; ?>
            </div>
        </div>
        <a href="staffViewStock.php" class="text-[#B85C38] hover:underline text-sm font-bold">View Stock →</a>
    </div>
    <?php endif; ?>

    <!-- Success Message -->
    <?php if ($success_message): ?>
    <div class="bg-[#7A8C71] text-white px-6 py-4 rounded-2xl mb-6 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="material-symbols-outlined">check_circle</span>
            <span class="font-medium"><?php echo $success_message; ?></span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-white/80 hover:text-white">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
    <div class="bg-[#B85C38] text-white px-6 py-4 rounded-2xl mb-6 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="material-symbols-outlined">error</span>
            <span class="font-medium"><?php echo $error_message; ?></span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-white/80 hover:text-white">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>
    <?php endif; ?>

    <!-- Settings Tabs -->
    <div class="flex gap-2 mb-6 overflow-x-auto pb-2">
        <button onclick="showTab('profile')" class="tab-btn active px-6 py-3 rounded-full text-sm font-bold uppercase tracking-wider transition-colors" style="background: #8B5A2B; color: white;">
            Profile
        </button>
        <button onclick="showTab('notifications')" class="tab-btn px-6 py-3 rounded-full text-sm font-bold uppercase tracking-wider transition-colors bg-white border border-[#3C2A21]/10 hover:bg-[#F2E8DF]">
            Notifications
        </button>
        <button onclick="showTab('appearance')" class="tab-btn px-6 py-3 rounded-full text-sm font-bold uppercase tracking-wider transition-colors bg-white border border-[#3C2A21]/10 hover:bg-[#F2E8DF]">
            Appearance
        </button>
        <button onclick="showTab('system')" class="tab-btn px-6 py-3 rounded-full text-sm font-bold uppercase tracking-wider transition-colors bg-white border border-[#3C2A21]/10 hover:bg-[#F2E8DF]">
            System
        </button>
    </div>

    <!-- Profile Settings Tab -->
    <div id="profile-tab" class="tab-content">
        <div class="bg-[#FAF9F6] rounded-2xl border border-[#3C2A21]/5 card-shadow p-8">
            <h2 class="text-xl font-bold text-[#3C2A21] mb-6 flex items-center gap-2">
                <span class="material-symbols-outlined text-[#8B5A2B]">person</span>
                Profile Information
            </h2>
            
            <form method="POST" action="" class="space-y-6">
                <div>
                    <label class="block text-sm font-bold text-[#3C2A21]/60 mb-2 uppercase tracking-wider">Full Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($current_user['full_name'] ?? ''); ?>" 
                           class="w-full px-4 py-3 rounded-xl border border-[#3C2A21]/10 focus:border-[#8B5A2B] focus:ring-2 focus:ring-[#8B5A2B]/20 outline-none transition">
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-[#3C2A21]/60 mb-2 uppercase tracking-wider">Email Address</label>
                    <input type="email" value="<?php echo htmlspecialchars($current_user['email'] ?? ''); ?>" 
                           class="w-full px-4 py-3 rounded-xl border border-[#3C2A21]/10 bg-[#F2E8DF]/50" readonly disabled>
                    <p class="text-xs text-[#3C2A21]/40 mt-1">Email cannot be changed</p>
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-[#3C2A21]/60 mb-2 uppercase tracking-wider">Phone Number</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($current_user['phone'] ?? ''); ?>" 
                           class="w-full px-4 py-3 rounded-xl border border-[#3C2A21]/10 focus:border-[#8B5A2B] focus:ring-2 focus:ring-[#8B5A2B]/20 outline-none transition">
                    <p class="text-xs text-[#3C2A21]/40 mt-1">Optional</p>
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-[#3C2A21]/60 mb-2 uppercase tracking-wider">Role</label>
                    <input type="text" value="<?php echo htmlspecialchars($current_user['role'] ?? 'Staff'); ?>" 
                           class="w-full px-4 py-3 rounded-xl border border-[#3C2A21]/10 bg-[#F2E8DF]/50" readonly disabled>
                </div>
                
                <button type="submit" name="update_profile_settings" class="bg-[#8B5A2B] text-white px-8 py-3 rounded-full text-sm font-bold uppercase tracking-wider hover:bg-[#B07A4A] transition-colors">
                    Update Profile
                </button>
            </form>
        </div>
    </div>

    <!-- Notifications Settings Tab - ONLY LOW STOCK ALERTS -->
    <div id="notifications-tab" class="tab-content hidden">
        <div class="bg-[#FAF9F6] rounded-2xl border border-[#3C2A21]/5 card-shadow p-8">
            <h2 class="text-xl font-bold text-[#3C2A21] mb-6 flex items-center gap-2">
                <span class="material-symbols-outlined text-[#8B5A2B]">notifications</span>
                Notification Preferences
            </h2>
            
            <form method="POST" action="" class="space-y-6">
                <div class="space-y-4">
                    <label class="flex items-center justify-between p-4 bg-[#F2E8DF]/30 rounded-xl">
                        <div>
                            <span class="font-bold text-[#3C2A21]">Low Stock Alerts</span>
                            <p class="text-xs text-[#3C2A21]/40 mt-1">Show alert when items are running low</p>
                        </div>
                        <input type="checkbox" name="low_stock_alerts" class="w-5 h-5 rounded border-[#3C2A21]/20 text-[#8B5A2B] focus:ring-[#8B5A2B]" <?= $low_stock_alert ? 'checked' : '' ?>>
                    </label>
                </div>
                
                <div class="bg-[#F2E8DF] p-4 rounded-xl">
                    <p class="text-sm font-bold text-[#3C2A21] mb-2">Current Stock Status</p>
                    <div class="flex justify-between items-center">
                        <span class="text-[#3C2A21]/60">Low Stock Items:</span>
                        <span class="font-bold text-[#B85C38]"><?php echo $low_stock_count; ?></span>
                    </div>
                    <div class="flex justify-between items-center mt-1">
                        <span class="text-[#3C2A21]/60">Critical Stock Items:</span>
                        <span class="font-bold text-[#B85C38]"><?php echo $critical_stock_count; ?></span>
                    </div>
                </div>
                
                <button type="submit" name="update_notifications" class="bg-[#8B5A2B] text-white px-8 py-3 rounded-full text-sm font-bold uppercase tracking-wider hover:bg-[#B07A4A] transition-colors">
                    Save Notification Settings
                </button>
            </form>
        </div>
    </div>

    <!-- Appearance Settings Tab - ONLY THEME (Light, Dark, System) -->
    <div id="appearance-tab" class="tab-content hidden">
        <div class="bg-[#FAF9F6] rounded-2xl border border-[#3C2A21]/5 card-shadow p-8">
            <h2 class="text-xl font-bold text-[#3C2A21] mb-6 flex items-center gap-2">
                <span class="material-symbols-outlined text-[#8B5A2B]">palette</span>
                Appearance Settings
            </h2>
            
            <form method="POST" action="" class="space-y-6">
                <div>
                    <label class="block text-sm font-bold text-[#3C2A21]/60 mb-3 uppercase tracking-wider">Theme</label>
                    <div class="grid grid-cols-3 gap-4">
                        <label class="cursor-pointer">
                            <input type="radio" name="theme" value="light" class="hidden peer" <?= $theme == 'light' ? 'checked' : '' ?>>
                            <div class="p-4 bg-white border-2 border-[#3C2A21]/10 rounded-xl text-center peer-checked:border-[#8B5A2B] peer-checked:bg-[#8B5A2B]/5">
                                <span class="material-symbols-outlined text-3xl text-[#8B5A2B]">light_mode</span>
                                <p class="text-sm font-bold mt-2">Light</p>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="theme" value="dark" class="hidden peer" <?= $theme == 'dark' ? 'checked' : '' ?>>
                            <div class="p-4 bg-white border-2 border-[#3C2A21]/10 rounded-xl text-center peer-checked:border-[#8B5A2B] peer-checked:bg-[#8B5A2B]/5">
                                <span class="material-symbols-outlined text-3xl text-[#3C2A21]">dark_mode</span>
                                <p class="text-sm font-bold mt-2">Dark</p>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="theme" value="system" class="hidden peer" <?= $theme == 'system' ? 'checked' : '' ?>>
                            <div class="p-4 bg-white border-2 border-[#3C2A21]/10 rounded-xl text-center peer-checked:border-[#8B5A2B] peer-checked:bg-[#8B5A2B]/5">
                                <span class="material-symbols-outlined text-3xl text-[#8B5A2B]">settings_suggest</span>
                                <p class="text-sm font-bold mt-2">System</p>
                            </div>
                        </label>
                    </div>
                    <p class="text-xs text-[#3C2A21]/40 mt-3">System will follow your device's theme preference</p>
                </div>
                
                <button type="submit" name="update_appearance" class="bg-[#8B5A2B] text-white px-8 py-3 rounded-full text-sm font-bold uppercase tracking-wider hover:bg-[#B07A4A] transition-colors">
                    Save Appearance Settings
                </button>
            </form>
        </div>
    </div>

    <!-- System Settings Tab - ONLY SYSTEM INFORMATION (READ ONLY) -->
    <div id="system-tab" class="tab-content hidden">
        <div class="bg-[#FAF9F6] rounded-2xl border border-[#3C2A21]/5 card-shadow p-8">
            <h2 class="text-xl font-bold text-[#3C2A21] mb-6 flex items-center gap-2">
                <span class="material-symbols-outlined text-[#8B5A2B]">settings</span>
                System Information
            </h2>
            
            <div class="space-y-6">
                <div class="pt-4">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-[#3C2A21]/40">System Version</p>
                            <p class="font-medium">Stock Reporting System v1.0</p>
                        </div>
                        <div>
                            <p class="text-[#3C2A21]/40">Last Database Update</p>
                            <p class="font-medium"><?php echo $last_updated; ?></p>
                        </div>
                        <div>
                            <p class="text-[#3C2A21]/40">Total Products</p>
                            <p class="font-medium"><?php echo $total_products; ?> items</p>
                        </div>
                        <div>
                            <p class="text-[#3C2A21]/40">Total Transactions</p>
                            <p class="font-medium"><?php echo $total_transactions; ?> records</p>
                        </div>
                        <div>
                            <p class="text-[#3C2A21]/40">Total Users</p>
                            <p class="font-medium"><?php echo $total_users; ?> registered</p>
                        </div>
                        <div>
                            <p class="text-[#3C2A21]/40">Database Type</p>
                            <p class="font-medium">MySQL</p>
                        </div>
                        <div>
                            <p class="text-[#3C2A21]/40">Server</p>
                            <p class="font-medium">XAMPP / Apache</p>
                        </div>
                        <div>
                            <p class="text-[#3C2A21]/40">PHP Version</p>
                            <p class="font-medium"><?php echo phpversion(); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Danger Zone -->
    <div class="mt-8 bg-[#FAF9F6] rounded-2xl border border-[#B85C38]/20 card-shadow p-8">
        <h2 class="text-xl font-bold text-[#B85C38] mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined">warning</span>
            Danger Zone
        </h2>
        
        <div class="space-y-4">
            <div class="flex items-center justify-between p-4 bg-[#F2E8DF]/30 rounded-xl">
                <div>
                    <p class="font-bold text-[#3C2A21]">Reset All Settings</p>
                    <p class="text-xs text-[#3C2A21]/40">Restore default configuration</p>
                </div>
                <form method="POST" action="" style="margin: 0;">
                    <input type="hidden" name="reset_settings" value="1">
                    <button type="submit" onclick="return confirm('Are you sure you want to reset all settings to default?')" 
                            class="bg-white border border-[#B85C38] text-[#B85C38] px-6 py-2 rounded-full text-xs font-bold uppercase tracking-wider hover:bg-[#B85C38] hover:text-white transition-colors">
                        Reset
                    </button>
                </form>
            </div>
        </div>
    </div>
</main>

<script>
// Tab switching functionality
function showTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.add('hidden');
    });
    
    document.getElementById(tabName + '-tab').classList.remove('hidden');
    
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
        btn.style.background = 'white';
        btn.style.color = '#3C2A21';
    });
    
    event.target.style.background = '#8B5A2B';
    event.target.style.color = 'white';
}

// Live preview theme when radio button is clicked
document.querySelectorAll('input[name="theme"]').forEach(radio => {
    radio.addEventListener('change', function() {
        if (this.value === 'dark') {
            document.body.classList.add('dark-mode');
        } else if (this.value === 'light') {
            document.body.classList.remove('dark-mode');
        } else if (this.value === 'system') {
            // Check user's system preference
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                document.body.classList.add('dark-mode');
            } else {
                document.body.classList.remove('dark-mode');
            }
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>

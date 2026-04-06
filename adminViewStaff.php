<?php
// adminViewStaff.php
define('ACCESS_ALLOWED', true);
require_once 'config/database.php';
requireLogin();

if ($_SESSION['user_role'] !== 'Administrator') {
    header('Location: staffDashboard.php');
    exit();
}

$page_title = 'Staff Management';

// Handle Edit Staff
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_staff'])) {
    $conn = getConnection();
    $user_id = intval($_POST['user_id']);
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // JANGAN update last_stock_activity
    $update = "UPDATE users SET full_name='$full_name', email='$email', phone='$phone', role='$role', status='$status' WHERE user_id=$user_id";
    
    if (mysqli_query($conn, $update)) {
        $success_message = 'Staff updated successfully!';
    } else {
        $error_message = 'Failed to update staff: ' . mysqli_error($conn);
    }
    mysqli_close($conn);
    header("Location: adminViewStaff.php");
    exit();
}

// Handle Delete Staff
if (isset($_GET['delete_staff'])) {
    $conn = getConnection();
    $user_id = intval($_GET['delete_staff']);
    
    // Delete staff
    $delete = "DELETE FROM users WHERE user_id = $user_id AND role != 'Administrator'";
    if (mysqli_query($conn, $delete)) {
        $success_message = 'Staff deleted successfully!';
    } else {
        $error_message = 'Failed to delete staff';
    }
    mysqli_close($conn);
    header("Location: adminViewStaff.php");
    exit();
}

// Get staff list from database
$conn = getConnection();
$staff_list = [];
$result = mysqli_query($conn, "SELECT * FROM users WHERE role != 'Administrator' ORDER BY user_id");

while ($row = mysqli_fetch_assoc($result)) {
    // Get profile picture
    $profile_pic = $row['profile_picture'] ?? 'default-avatar.png';
    $profile_pic_path = "uploads/profiles/" . $profile_pic;
    
    // Convert role to display format
    $role_display = $row['role'];
    $role_class = '';
    if (stripos($row['role'], 'senior') !== false) {
        $role_class = 'bg-[#8B5A2B]/10 text-[#8B5A2B]';
        $role_display = 'SENIOR STAFF';
    } elseif (stripos($row['role'], 'junior') !== false) {
        $role_class = 'bg-[#7A8C71]/10 text-[#7A8C71]';
        $role_display = 'JUNIOR STAFF';
    } else {
        $role_class = 'bg-[#E6B17E]/10 text-[#E6B17E]';
        $role_display = 'TRAINEE';
    }
    
    $staff_list[] = [
        'id' => $row['user_id'],
        'name' => $row['full_name'],
        'role' => $role_display,
        'role_raw' => $row['role'],
        'role_class' => $role_class,
        'last_stock' => $row['last_stock_activity'] ? date('h:i A', strtotime($row['last_stock_activity'])) . ' ago' : 'Never',
        'initial' => strtoupper(substr($row['full_name'], 0, 1) . (isset(explode(' ', $row['full_name'])[1]) ? substr(explode(' ', $row['full_name'])[1], 0, 1) : '')),
        'color' => ['#8B5A2B', '#7A8C71', '#B85C38', '#E6B17E'][$row['user_id'] % 4],
        'email' => $row['email'],
        'phone' => $row['phone'] ?: '-',
        'status' => $row['status'],
        'join_date' => $row['join_date'],
        'profile_picture' => $profile_pic,
        'profile_picture_path' => (file_exists($profile_pic_path) && is_file($profile_pic_path)) ? $profile_pic_path : null
    ];
}

$total_staff = count($staff_list);
$active_staff = 0;
$offline_staff = 0;
foreach ($staff_list as $staff) {
    if ($staff['status'] == 'active') $active_staff++;
    else $offline_staff++;
}

mysqli_close($conn);

include 'includes/header-admin.php';
?>

<style>
    .staff-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
        margin: 0 auto;
        border: 4px solid #8B5A2B;
    }
    
    .staff-avatar-small {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
    }
    
    .modal-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
    }
</style>

<main class="px-6 md:px-10 py-8 max-w-7xl mx-auto w-full">
    <!-- Page Title -->
    <div class="mb-6">
        <h1 class="text-3xl md:text-4xl font-black text-[#3C2A21]">Staff Management</h1>
        <p class="text-[#3C2A21]/60 mt-1">Review performance, manage roles, and monitor recent inventory activity.</p>
    </div>
    
    <!-- Success/Error Messages -->
    <?php if (isset($success_message)): ?>
    <div class="bg-[#7A8C71] text-white px-6 py-4 rounded-2xl mb-6 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="material-symbols-outlined">check_circle</span>
            <span class="font-medium"><?php echo $success_message; ?></span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-white/80 hover:text-white">×</button>
    </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
    <div class="bg-[#B85C38] text-white px-6 py-4 rounded-2xl mb-6 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="material-symbols-outlined">error</span>
            <span class="font-medium"><?php echo $error_message; ?></span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-white/80 hover:text-white">×</button>
    </div>
    <?php endif; ?>

    <!-- Search Box -->
    <div class="bg-[#FAF9F6] rounded-2xl border border-[#3C2A21]/5 p-6 mb-8">
        <div class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[300px]">
                <input type="text" id="searchInput" placeholder="Search staff by name, ID or role.." 
                       class="w-full px-4 py-3 rounded-xl border border-[#3C2A21]/10 focus:border-[#8B5A2B] outline-none">
            </div>
            <button onclick="searchStaff()" class="bg-[#8B5A2B] text-white px-6 py-3 rounded-full text-sm font-bold uppercase tracking-wider hover:bg-[#B07A4A] transition-colors">
                SEARCH
            </button>
            <a href="adminViewStaff.php" class="bg-white border border-[#3C2A21]/10 px-6 py-3 rounded-full text-sm font-bold uppercase tracking-wider hover:bg-[#F2E8DF] transition-colors">
                RESET
            </a>
        </div>
    </div>

    <!-- Staff Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8" id="staffCardsContainer">
        <?php foreach ($staff_list as $index => $staff): ?>
        <div class="staff-card bg-[#FAF9F6] rounded-2xl border border-[#3C2A21]/5 p-6 text-center shadow-sm hover:shadow-md transition-shadow" data-name="<?php echo strtolower($staff['name']); ?>" data-role="<?php echo strtolower($staff['role']); ?>">
            <!-- Profile Picture -->
            <div class="w-24 h-24 rounded-full mx-auto mb-4 border-4 border-[#8B5A2B] overflow-hidden bg-[#F2E8DF]">
                <?php if ($staff['profile_picture_path'] && file_exists($staff['profile_picture_path'])): ?>
                <img src="<?php echo $staff['profile_picture_path']; ?>?t=<?php echo time(); ?>" alt="<?php echo $staff['name']; ?>" class="w-full h-full object-cover">
                <?php else: ?>
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($staff['name']); ?>&background=8B5A2B&color=fff&size=128" alt="<?php echo $staff['name']; ?>" class="w-full h-full object-cover">
                <?php endif; ?>
            </div>
            
            <h3 class="font-bold text-xl text-[#3C2A21] mb-2"><?php echo $staff['name']; ?></h3>
            
            <span class="inline-block text-xs font-bold px-4 py-1.5 rounded-full mb-3 <?php echo $staff['role_class']; ?>">
                <?php echo $staff['role']; ?>
            </span>
            
            
            <div class="flex gap-3 pt-2 border-t border-[#3C2A21]/10">
                <button onclick="openEditModal(<?php echo $index; ?>)" 
                        class="flex-1 py-2.5 bg-[#8B5A2B]/10 text-[#8B5A2B] rounded-lg text-sm font-medium hover:bg-[#8B5A2B] hover:text-white transition-colors">
                    View / Edit
                </button>
                <button onclick="if(confirm('Delete <?php echo $staff['name']; ?>?')) window.location.href='adminViewStaff.php?delete_staff=<?php echo $staff['id']; ?>'" 
                        class="flex-1 py-2.5 bg-[#F2E8DF] text-[#B85C38] rounded-lg text-sm font-medium hover:bg-[#B85C38] hover:text-white transition-colors">
                    Delete
                </button>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Add Staff Card -->
        <div class="bg-[#E8D5C4] rounded-2xl border-2 border-dashed border-[#8B5A2B]/50 p-6 flex flex-col items-center justify-center text-center hover:bg-[#DCC8B5] transition-colors cursor-pointer shadow-sm" 
             onclick="window.location.href='register.php'">
            <div class="w-24 h-24 bg-[#8B5A2B]/20 rounded-full flex items-center justify-center mb-4">
                <span class="material-symbols-outlined text-4xl text-[#8B5A2B]">person_add</span>
            </div>
            <h3 class="font-bold text-xl text-[#3C2A21] mb-2">Add Staff Member</h3>
            <p class="text-sm text-[#3C2A21]/60">Set up permissions and onboarding.</p>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-[#FAF9F6] p-5 rounded-2xl border border-[#3C2A21]/5">
            <p class="text-[#3C2A21]/40 text-xs font-bold uppercase mb-1">TOTAL STAFF</p>
            <p class="text-2xl font-black text-[#3C2A21]"><?php echo $total_staff; ?></p>
        </div>
        <div class="bg-[#FAF9F6] p-5 rounded-2xl border border-[#3C2A21]/5">
            <p class="text-[#3C2A21]/40 text-xs font-bold uppercase mb-1">ACTIVE NOW</p>
            <p class="text-2xl font-black text-[#7A8C71]"><?php echo $active_staff; ?></p>
        </div>
        <div class="bg-[#FAF9F6] p-5 rounded-2xl border border-[#3C2A21]/5">
            <p class="text-[#3C2A21]/40 text-xs font-bold uppercase mb-1">OFFLINE</p>
            <p class="text-2xl font-black text-[#B85C38]"><?php echo $offline_staff; ?></p>
        </div>
    </div>

    <!-- Staff Table -->
    <div class="bg-[#FAF9F6] rounded-3xl border border-[#3C2A21]/5 overflow-hidden mb-6">
        <div class="bg-[#3C2A21] px-6 py-4">
            <h2 class="text-white text-sm font-bold uppercase">STAFF DETAILS</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b">
                        <th class="text-left p-4 text-xs font-bold text-[#3C2A21]/40 uppercase">STAFF</th>
                        <th class="text-left p-4 text-xs font-bold text-[#3C2A21]/40 uppercase">ID</th>
                        <th class="text-left p-4 text-xs font-bold text-[#3C2A21]/40 uppercase">ROLE</th>
                        <th class="text-left p-4 text-xs font-bold text-[#3C2A21]/40 uppercase">CONTACT</th>
                        <th class="text-left p-4 text-xs font-bold text-[#3C2A21]/40 uppercase">STATUS</th>
                        <th class="text-left p-4 text-xs font-bold text-[#3C2A21]/40 uppercase">ACTIONS</th>
                     </tr>
                </thead>
                <tbody>
                    <?php foreach ($staff_list as $index => $staff): ?>
                    <tr class="border-b hover:bg-[#F2E8DF]/30">
                        <td class="p-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full overflow-hidden bg-[#F2E8DF]">
                                    <?php if ($staff['profile_picture_path'] && file_exists($staff['profile_picture_path'])): ?>
                                    <img src="<?php echo $staff['profile_picture_path']; ?>?t=<?php echo time(); ?>" alt="<?php echo $staff['name']; ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($staff['name']); ?>&background=8B5A2B&color=fff&size=64" alt="<?php echo $staff['name']; ?>" class="w-full h-full object-cover">
                                    <?php endif; ?>
                                </div>
                                <span class="font-medium"><?php echo $staff['name']; ?></span>
                            </div>
                         </td>
                        <td class="p-4 font-mono text-sm"><?php echo $staff['id']; ?></td>
                        <td class="p-4 text-sm"><?php echo $staff['role']; ?></td>
                        <td class="p-4 text-sm"><?php echo $staff['phone']; ?></td>
                        <td class="p-4">
                            <span class="inline-block w-2 h-2 rounded-full <?php echo $staff['status'] == 'active' ? 'bg-[#7A8C71]' : 'bg-[#B85C38]'; ?> mr-2"></span>
                            <?php echo $staff['status']; ?>
                         </td>
                        <td class="p-4">
                            <button onclick="openEditModal(<?php echo $index; ?>)" class="text-[#8B5A2B] hover:bg-[#F2E8DF] p-1.5 rounded" title="Edit">✏️</button>
                            <button onclick="if(confirm('Delete <?php echo $staff['name']; ?>?')) window.location.href='adminViewStaff.php?delete_staff=<?php echo $staff['id']; ?>'" class="text-[#B85C38] hover:bg-[#F2E8DF] p-1.5 rounded" title="Delete">🗑️</button>
                         </td>
                     </tr>
                    <?php endforeach; ?>
                </tbody>
             </table>
        </div>
    </div>
</main>

<!-- MODAL EDIT STAFF -->
<div id="editStaffModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000;">
    <div style="background: #FAF9F6; border-radius: 24px; max-width: 500px; width: 90%; margin: 0 auto; padding: 32px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h3 style="font-size: 24px; font-weight: 900; color: #3C2A21;">Edit Staff</h3>
            <button onclick="closeEditModal()" style="font-size: 28px; color: #3C2A21/40; background: none; border: none; cursor: pointer;">×</button>
        </div>
        
        <form method="POST" action="" id="editStaffForm">
            <input type="hidden" name="edit_staff" value="1">
            <input type="hidden" name="user_id" id="edit_user_id">
            
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">Full Name</label>
                <input type="text" name="full_name" id="edit_full_name" required style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid rgba(60,42,33,0.1);">
            </div>
            
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">Email</label>
                <input type="email" name="email" id="edit_email" required style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid rgba(60,42,33,0.1);">
            </div>
            
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">Phone</label>
                <input type="text" name="phone" id="edit_phone" style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid rgba(60,42,33,0.1);">
            </div>
            
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">Role</label>
                <select name="role" id="edit_role" required style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid rgba(60,42,33,0.1);">
                    <option value="Trainee">Trainee</option>
                    <option value="Junior Staff">Junior Staff</option>
                    <option value="Senior Staff">Senior Staff</option>
                </select>
            </div>
            
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">Status</label>
                <select name="status" id="edit_status" required style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid rgba(60,42,33,0.1);">
                    <option value="active">Active</option>
                    <option value="offline">Offline</option>
                </select>
            </div>
            
            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" style="flex: 1; background: #8B5A2B; color: white; padding: 12px; border-radius: 9999px; font-size: 14px; font-weight: bold; text-transform: uppercase; border: none; cursor: pointer;">
                    Update Staff
                </button>
                <button type="button" onclick="closeEditModal()" style="flex: 1; background: white; border: 1px solid rgba(60,42,33,0.1); padding: 12px; border-radius: 9999px; font-size: 14px; font-weight: bold; text-transform: uppercase; cursor: pointer;">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const staffData = <?= json_encode($staff_list) ?>;

function openEditModal(index) {
    const staff = staffData[index];
    
    document.getElementById('edit_user_id').value = staff.id;
    document.getElementById('edit_full_name').value = staff.name;
    document.getElementById('edit_email').value = staff.email;
    document.getElementById('edit_phone').value = staff.phone !== '-' ? staff.phone : '';
    
    // Set role based on display role
    let roleValue = 'Trainee';
    if (staff.role === 'SENIOR STAFF') {
        roleValue = 'Senior Staff';
    } else if (staff.role === 'JUNIOR STAFF') {
        roleValue = 'Junior Staff';
    } else {
        roleValue = 'Trainee';
    }
    document.getElementById('edit_role').value = roleValue;
    document.getElementById('edit_status').value = staff.status;
    
    document.getElementById('editStaffModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editStaffModal').style.display = 'none';
}

function searchStaff() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const cards = document.querySelectorAll('.staff-card');
    
    cards.forEach(card => {
        const name = card.getAttribute('data-name');
        const role = card.getAttribute('data-role');
        
        if (name.includes(searchTerm) || role.includes(searchTerm)) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}

window.onclick = function(event) {
    const modal = document.getElementById('editStaffModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}
</script>

<?php include 'includes/footer.php'; ?>

<?php
// adminDashboard.php
define('ACCESS_ALLOWED', true);
require_once 'config/database.php';
requireLogin();

// Check if user is admin
if ($_SESSION['user_role'] !== 'Administrator') {
    header('Location: staffDashboard.php');
    exit();
}

$page_title = 'Admin Dashboard';

// Get current user
$admin_name = $_SESSION['user_name'] ?? 'Admin User';
$admin_role = $_SESSION['user_role'] ?? 'Administrator';
$current_time = date('h:i A');
$current_date = date('F j, Y');

// Connect to database
$conn = getConnection();

// Quick Stats - sync dengan status inventory guna all_products
$total_items_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM products");
$total_items = mysqli_fetch_assoc($total_items_result)['total'];

$total_staff_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role != 'Administrator'");
$total_staff = mysqli_fetch_assoc($total_staff_result)['total'];

// Get active and offline staff count
$active_staff_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role != 'Administrator' AND status = 'active'");
$active_staff = mysqli_fetch_assoc($active_staff_result)['total'];

$offline_staff_result = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role != 'Administrator' AND status = 'offline'");
$offline_staff = mysqli_fetch_assoc($offline_staff_result)['total'];

// Get all products for accurate counting of Low Stock & Critical (sync dengan staffViewStock)
$all_products_query = "SELECT current_stock, reorder_level, max_stock FROM products";
$all_products_result = mysqli_query($conn, $all_products_query);

$low_stock_count = 0;
$critical_count = 0;

while ($row = mysqli_fetch_assoc($all_products_result)) {
    // FIX: Check if max_stock is zero to avoid division by zero
    $percentage = 0;
    if ($row['max_stock'] > 0) {
        $percentage = ($row['current_stock'] / $row['max_stock']) * 100;
        $percentage = min(100, max(0, $percentage));
    }
    
    // Formula yang sama dengan staffViewStock.php
    if ($row['current_stock'] <= $row['reorder_level']/2) {
        $critical_count++;
    } elseif ($row['current_stock'] <= $row['reorder_level']) {
        // REORDER - tidak dikira dalam low atau critical
    } elseif ($percentage <= 50) {
        $low_stock_count++;
    }
}

// Get Critical & Low Stock Items (only CRITICAL and LOW)
$critical_low_items = [];
$critical_low_query = "SELECT p.*, c.category_name, c.category_color,
                       (p.current_stock / p.max_stock) * 100 as percentage
                       FROM products p 
                       LEFT JOIN categories c ON p.category_id = c.category_id 
                       WHERE p.current_stock <= p.reorder_level
                       ORDER BY (p.current_stock / p.max_stock) ASC LIMIT 10";
$result = mysqli_query($conn, $critical_low_query);
while ($row = mysqli_fetch_assoc($result)) {
    // FIX: Check if max_stock is zero to avoid division by zero
    $percentage = 0;
    if ($row['max_stock'] > 0) {
        $percentage = ($row['current_stock'] / $row['max_stock']) * 100;
        $percentage = min(100, max(0, $percentage));
    }
    
    // Only CRITICAL and LOW (exclude REORDER)
    if ($row['current_stock'] <= $row['reorder_level']/2) {
        $status = 'critical';
        $status_text = 'CRITICAL';
        $status_color = 'bg-red-100 text-red-700 border-red-300';
        $icon = 'error';
    } elseif ($percentage <= 50) {
        $status = 'low';
        $status_text = 'LOW';
        $status_color = 'bg-yellow-100 text-yellow-700 border-yellow-300';
        $icon = 'priority_high';
    } else {
        continue;
    }
    
    $critical_low_items[] = [
        'id' => $row['product_id'],
        'name' => $row['product_name'],
        'current_stock' => $row['current_stock'],
        'max_stock' => $row['max_stock'],
        'unit' => $row['unit'],
        'status' => $status,
        'status_text' => $status_text,
        'status_color' => $status_color,
        'icon' => $icon,
        'percentage' => round($percentage)
    ];
}

// Recent activities
$recent_activities = [];
$activity_query = "SELECT st.notes as action, DATE_FORMAT(st.transaction_date, '%h:%i %p') as time,
                   p.location, u.full_name as user,
                   CASE 
                       WHEN st.notes LIKE '%restock%' THEN 'add_circle'
                       WHEN st.notes LIKE '%waste%' THEN 'delete_sweep'
                       WHEN st.notes LIKE '%audit%' THEN 'fact_check'
                       ELSE 'inventory'
                   END as icon,
                   CASE 
                       WHEN st.notes LIKE '%restock%' THEN '#8B5A2B'
                       WHEN st.notes LIKE '%waste%' THEN '#B85C38'
                       WHEN st.notes LIKE '%audit%' THEN '#E6B17E'
                       ELSE '#7A8C71'
                   END as color
                   FROM stock_transactions st
                   JOIN products p ON st.product_id = p.product_id
                   JOIN users u ON st.performed_by = u.user_id
                   ORDER BY st.transaction_date DESC LIMIT 5";
$result = mysqli_query($conn, $activity_query);
while ($row = mysqli_fetch_assoc($result)) {
    $row['action'] = $row['action'] ?: ($row['icon'] == 'add_circle' ? 'Stock added' : 'Stock updated');
    $recent_activities[] = $row;
}

mysqli_close($conn);

include 'includes/header-admin.php';
?>

<style>
    /* Warna kotak statistik */
    .stat-card-total {
        background: linear-gradient(135deg, #2C3E50 0%, #1A2A3A 100%);
        transition: all 0.3s ease;
    }
    .stat-card-total:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px -12px rgba(0, 0, 0, 0.3);
    }
    
    .stat-card-staff {
        background: linear-gradient(135deg, #2E7D32 0%, #1B5E20 100%);
        transition: all 0.3s ease;
    }
    .stat-card-staff:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px -12px rgba(46, 125, 50, 0.3);
    }
    
    .stat-card-low {
        background: linear-gradient(135deg, #F39C12 0%, #E67E22 100%);
        transition: all 0.3s ease;
    }
    .stat-card-low:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px -12px rgba(243, 156, 18, 0.3);
    }
    
    .stat-card-critical {
        background: linear-gradient(135deg, #C0392B 0%, #A93226 100%);
        transition: all 0.3s ease;
    }
    .stat-card-critical:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px -12px rgba(192, 57, 43, 0.3);
    }
    
    /* Critical & Low Stock Table */
    .critical-low-table th {
        background: #3C2A21;
        color: white;
    }
    
    /* Recent Activity Card */
    .recent-activity-header {
        background: linear-gradient(135deg, #3C2A21 0%, #5D3A1A 100%);
    }
    
    /* Quick Actions Card */
    .quick-actions-card {
        background: linear-gradient(135deg, #FAF9F6 0%, #F2E8DF 100%);
    }
    
    .quick-action-btn {
        transition: all 0.3s ease;
        background: white;
        border: 1px solid rgba(139, 90, 43, 0.1);
    }
    
    .quick-action-btn:hover {
        background: linear-gradient(135deg, #8B5A2B 0%, #B07A4A 100%);
        color: white;
        transform: translateX(5px);
        border-color: transparent;
    }
    
    .quick-action-btn:hover span {
        color: white !important;
    }
    
    /* Staff Summary Card */
    .staff-summary-card {
        background: linear-gradient(135deg, #FAF9F6 0%, #F2E8DF 100%);
    }
    
    .staff-stat {
        background: white;
        transition: all 0.3s ease;
    }
    
    .staff-stat:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px -8px rgba(0, 0, 0, 0.1);
    }
</style>

<main class="px-6 md:px-10 py-8 max-w-7xl mx-auto w-full flex flex-col gap-8">
    <!-- Welcome Section -->
    <section class="flex flex-col md:flex-row justify-between items-center gap-6 bg-[#FAF9F6] p-8 rounded-3xl border border-[#3C2A21]/5 card-shadow">
        <div class="flex flex-col gap-2">
            <div class="inline-flex items-center gap-2 text-[#8B5A2B] font-black text-xs uppercase tracking-[0.2em]">
                <span class="size-2 bg-[#8B5A2B] rounded-full animate-pulse"></span>
                Admin Dashboard
            </div>
            <h1 class="text-3xl md:text-4xl font-black leading-tight text-[#3C2A21]">Welcome, <?php echo htmlspecialchars($admin_name); ?>! 👋</h1>
            <p class="text-[#3C2A21]/60 font-medium max-w-lg">Stock Control System - Overview of cafe inventory and staff management.</p>
        </div>
        <div class="flex flex-col gap-3 shrink-0">
            <div class="bg-[#F2E8DF] px-6 py-4 rounded-2xl border border-[#3C2A21]/5 flex items-center gap-4">
                <span class="material-symbols-outlined text-[#8B5A2B] text-3xl">schedule</span>
                <div class="flex flex-col">
                    <p id="currentDate" class="text-[10px] font-bold uppercase text-[#3C2A21]/40"><?php echo $current_date; ?></p>
                    <p id="currentTime" class="text-xl font-black text-[#3C2A21]"><?php echo $current_time; ?></p>
                </div>
            </div>
        </div>
    </section>

    <!-- Quick Stats Cards - WARNA BERBEZA -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="stat-card-total p-5 rounded-2xl flex items-center gap-4 shadow-lg">
            <div class="size-12 bg-white/20 rounded-xl flex items-center justify-center text-white">
                <span class="material-symbols-outlined text-2xl">inventory</span>
            </div>
            <div>
                <p class="text-white/70 text-xs font-bold uppercase tracking-widest">Total Items</p>
                <p class="text-3xl font-black text-white"><?php echo $total_items; ?></p>
            </div>
        </div>
        <div class="stat-card-staff p-5 rounded-2xl flex items-center gap-4 shadow-lg">
            <div class="size-12 bg-white/20 rounded-xl flex items-center justify-center text-white">
                <span class="material-symbols-outlined text-2xl">group</span>
            </div>
            <div>
                <p class="text-white/70 text-xs font-bold uppercase tracking-widest">Total Staff</p>
                <p class="text-3xl font-black text-white"><?php echo $total_staff; ?></p>
            </div>
        </div>
        <div class="stat-card-low p-5 rounded-2xl flex items-center gap-4 shadow-lg">
            <div class="size-12 bg-white/20 rounded-xl flex items-center justify-center text-white">
                <span class="material-symbols-outlined text-2xl">warning</span>
            </div>
            <div>
                <p class="text-white/70 text-xs font-bold uppercase tracking-widest">Low Stock</p>
                <p class="text-3xl font-black text-white"><?php echo $low_stock_count; ?></p>
            </div>
        </div>
        <div class="stat-card-critical p-5 rounded-2xl flex items-center gap-4 shadow-lg">
            <div class="size-12 bg-white/20 rounded-xl flex items-center justify-center text-white">
                <span class="material-symbols-outlined text-2xl">priority_high</span>
            </div>
            <div>
                <p class="text-white/70 text-xs font-bold uppercase tracking-widest">Critical</p>
                <p class="text-3xl font-black text-white"><?php echo $critical_count; ?></p>
            </div>
        </div>
    </div>

    <!-- Critical & Low Stock Items (Simple Table - Full Width) -->
    <section class="bg-[#FAF9F6] rounded-3xl border border-[#3C2A21]/5 card-shadow overflow-hidden">
        <div class="critical-low-table bg-[#3C2A21] px-6 py-3 flex items-center justify-between">
            <h2 class="text-white text-sm font-bold uppercase tracking-widest flex items-center gap-2">
                <span class="material-symbols-outlined text-[#E6B17E]">warning</span>
                Critical & Low Stock Items
            </h2>
            <a href="adminInventory.php" class="text-white/80 text-xs font-bold uppercase tracking-wider hover:text-white">View All →</a>
        </div>
        
        <div class="overflow-x-auto">
            <?php 
            $filtered_items = array_filter($critical_low_items, function($item) {
                return $item['status'] === 'critical' || $item['status'] === 'low';
            });
            ?>
            <?php if (empty($filtered_items)): ?>
            <div class="text-center py-6 text-[#3C2A21]/40">
                <span class="material-symbols-outlined text-3xl mb-1">check_circle</span>
                <p class="text-sm">No critical or low stock items! 🎉</p>
            </div>
            <?php else: ?>
            <table class="w-full">
                <thead><tr class="border-b border-[#3C2A21]/5 bg-[#F2E8DF]/30"><th class="text-left px-4 py-2 text-xs font-bold text-[#3C2A21]/40 uppercase">Item</th><th class="text-left px-4 py-2 text-xs font-bold text-[#3C2A21]/40 uppercase">Stock Level</th><th class="text-left px-4 py-2 text-xs font-bold text-[#3C2A21]/40 uppercase">Status</th> </thead>
                <tbody>
                    <?php foreach ($filtered_items as $item): ?>
                    <tr class="border-b border-[#3C2A21]/5 hover:bg-[#F2E8DF]/30 transition-colors">
                        <td class="px-4 py-2"><div class="flex items-center gap-2"><span class="material-symbols-outlined text-sm" style="color: <?php echo $item['status'] === 'critical' ? '#B85C38' : '#EAB308'; ?>"><?php echo $item['icon']; ?></span><span class="font-medium text-[#3C2A21] text-sm"><?php echo $item['name']; ?></span></div> </td>
                        <td class="px-4 py-2"><div><p class="font-medium text-[#3C2A21] text-sm"><?php echo $item['current_stock']; ?> / <?php echo $item['max_stock']; ?> <?php echo $item['unit']; ?></p><div class="w-full bg-gray-200 rounded-full h-1 mt-1 max-w-[80px]"><div class="h-1 rounded-full <?php echo $item['status'] === 'critical' ? 'bg-red-500' : 'bg-yellow-500'; ?>" style="width: <?php echo $item['percentage']; ?>%"></div></div></div> </td>
                        <td class="px-4 py-2"><span class="text-xs font-bold px-2 py-0.5 rounded-full <?php echo $item['status_color']; ?>"><?php echo $item['status_text']; ?></span> </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
             </table>
            <?php endif; ?>
        </div>
        <?php if (!empty($filtered_items)): ?><div class="px-4 py-2 bg-[#F2E8DF]/30 border-t border-[#3C2A21]/5"><p class="text-xs text-[#3C2A21]/40">Showing <?php echo count($filtered_items); ?> items that need immediate attention</p></div><?php endif; ?>
    </section>

    <!-- Recent Activity & Quick Actions -->
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Activity -->
        <div class="lg:col-span-2 bg-[#FAF9F6] rounded-3xl border border-[#3C2A21]/5 card-shadow overflow-hidden">
            <div class="recent-activity-header px-6 py-4 flex items-center justify-between">
                <h2 class="text-white text-sm font-bold uppercase tracking-widest">Recent Activity</h2>
                <span class="size-2 bg-[#7A8C71] rounded-full"></span>
            </div>
            <div class="flex flex-col divide-y divide-[#3C2A21]/5">
                <?php foreach ($recent_activities as $activity): ?>
                <div class="p-4 flex gap-4 hover:bg-[#F2E8DF]/30 transition-colors cursor-pointer" onclick="showSuccess('Viewing: <?php echo $activity['action']; ?>')">
                    <div class="size-10 rounded-xl flex items-center justify-center shrink-0" style="background: <?php echo $activity['color']; ?>10; color: <?php echo $activity['color']; ?>;">
                        <span class="material-symbols-outlined"><?php echo $activity['icon']; ?></span>
                    </div>
                    <div class="flex flex-col flex-1">
                        <p class="text-[#3C2A21] font-bold"><?php echo $activity['action']; ?></p>
                        <p class="text-[#3C2A21]/40 text-xs font-bold uppercase tracking-widest mt-0.5"><?php echo $activity['time']; ?> • <?php echo $activity['location']; ?> • by <?php echo $activity['user']; ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button class="w-full py-3 bg-[#F2E8DF]/20 text-[#3C2A21]/40 text-xs font-black uppercase tracking-[0.2em] text-center hover:bg-[#F2E8DF]/50 transition-colors" onclick="window.location.href='adminReports.php'">View Full Activity Log</button>
        </div>

        <!-- Quick Actions -->
        <div class="rounded-3xl border border-[#3C2A21]/5 card-shadow p-6" style="background: linear-gradient(135deg, #F5E6D3 0%, #E8D5C4 100%);">
            <h2 class="text-lg font-bold text-[#8B5A2B] mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-[#8B5A2B]">bolt</span>
                Quick Actions
            </h2>
            <div class="space-y-3">
                <button class="w-full p-4 bg-white/80 rounded-xl flex items-center gap-3 hover:bg-[#8B5A2B] hover:text-white transition-all duration-300 group shadow-sm" onclick="window.location.href='adminInventory.php?action=add'">
                    <span class="material-symbols-outlined text-[#8B5A2B] group-hover:text-white">add_circle</span>
                    <span class="font-medium">Add New Inventory Item</span>
                </button>
                <button class="w-full p-4 bg-white/80 rounded-xl flex items-center gap-3 hover:bg-[#8B5A2B] hover:text-white transition-all duration-300 group shadow-sm" onclick="window.location.href='adminViewStaff.php'">
                    <span class="material-symbols-outlined text-[#8B5A2B] group-hover:text-white">person_add</span>
                    <span class="font-medium">Register New Staff</span>
                </button>
                <button class="w-full p-4 bg-white/80 rounded-xl flex items-center gap-3 hover:bg-[#8B5A2B] hover:text-white transition-all duration-300 group shadow-sm" onclick="window.location.href='adminReports.php'">
                    <span class="material-symbols-outlined text-[#8B5A2B] group-hover:text-white">analytics</span>
                    <span class="font-medium">Generate Report</span>
                </button>
                <button class="w-full p-4 bg-white/80 rounded-xl flex items-center gap-3 hover:bg-[#8B5A2B] hover:text-white transition-all duration-300 group shadow-sm" onclick="window.location.href='adminSettings.php'">
                    <span class="material-symbols-outlined text-[#8B5A2B] group-hover:text-white">settings</span>
                    <span class="font-medium">System Settings</span>
                </button>
            </div>
        </div>
    </section>

    <!-- Staff Summary Section -->
    <div class="rounded-3xl border border-[#3C2A21]/5 card-shadow p-6" style="background: linear-gradient(135deg, #E0F2E9 0%, #D0E8DD 100%);">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-[#2E7D32] flex items-center gap-2">
                <span class="material-symbols-outlined text-[#2E7D32]">group</span>
                Staff Summary
            </h2>
            <a href="adminViewStaff.php" class="text-[#2E7D32] text-sm font-bold uppercase tracking-wider hover:underline">Manage Staff →</a>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white/80 p-4 rounded-xl shadow-sm text-center hover:bg-white transition-all duration-300">
                <p class="text-[#3C2A21]/60 text-xs font-bold uppercase tracking-widest mb-1">Total Staff</p>
                <p class="text-2xl font-black text-[#3C2A21]"><?php echo $total_staff; ?></p>
            </div>
            <div class="bg-white/80 p-4 rounded-xl shadow-sm text-center hover:bg-white transition-all duration-300">
                <p class="text-[#3C2A21]/60 text-xs font-bold uppercase tracking-widest mb-1">Active Now</p>
                <p class="text-2xl font-black text-[#7A8C71]"><?php echo $active_staff; ?></p>
            </div>
            <div class="bg-white/80 p-4 rounded-xl shadow-sm text-center hover:bg-white transition-all duration-300">
                <p class="text-[#3C2A21]/60 text-xs font-bold uppercase tracking-widest mb-1">Offline</p>
                <p class="text-2xl font-black text-[#B85C38]"><?php echo $offline_staff; ?></p>
            </div>
        </div>
    </div>
</main>

<script>
function showSuccess(message) {
    alert('✅ ' + message);
}

// Real-time clock update
function updateDateTime() {
    const now = new Date();
    let hours = now.getHours();
    let minutes = now.getMinutes();
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12;
    hours = hours ? hours : 12;
    minutes = minutes < 10 ? '0' + minutes : minutes;
    const timeString = hours + ':' + minutes + ' ' + ampm;
    
    const months = ['January', 'February', 'March', 'April', 'May', 'June', 
                    'July', 'August', 'September', 'October', 'November', 'December'];
    const date = now.getDate();
    const month = months[now.getMonth()];
    const year = now.getFullYear();
    const dateString = month + ' ' + date + ', ' + year;
    
    const timeElement = document.getElementById('currentTime');
    const dateElement = document.getElementById('currentDate');
    
    if (timeElement) timeElement.textContent = timeString;
    if (dateElement) dateElement.textContent = dateString;
}

updateDateTime();
setInterval(updateDateTime, 1000);
</script>

<?php include 'includes/footer.php'; ?>

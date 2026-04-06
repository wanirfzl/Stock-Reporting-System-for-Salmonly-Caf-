<?php
// staffDashboard.php
// Staff Dashboard - Main Overview Page

define('ACCESS_ALLOWED', true);
require_once 'config/database.php';
requireLogin();

$page_title = 'Dashboard';

$current_time = date('h:i A');
$current_date = date('F j, Y');

// Staff info (dari session)
$staff_name = $_SESSION['user_name'] ?? 'Staff';
$staff_role = $_SESSION['user_role'] ?? 'Staff';
$staff_id = $_SESSION['user_id'] ?? 0;

// Connect to database
$conn = getConnection();

// Handle AJAX request for shifts
if (isset($_GET['get_shifts'])) {
    $current_month = date('m');
    $current_year = date('Y');
    
    $shift_query = "SELECT s.*, DATE_FORMAT(s.shift_date, '%d/%m/%Y') as formatted_date,
                           DATE_FORMAT(s.start_time, '%h:%i %p') as start_time_formatted,
                           DATE_FORMAT(s.end_time, '%h:%i %p') as end_time_formatted,
                           CASE 
                               WHEN s.shift_date = CURDATE() THEN 'Today'
                               WHEN s.shift_date < CURDATE() THEN 'Past'
                               ELSE 'Upcoming'
                           END as status
                    FROM shifts s 
                    WHERE s.user_id = $staff_id 
                    AND MONTH(s.shift_date) = $current_month 
                    AND YEAR(s.shift_date) = $current_year
                    ORDER BY s.shift_date ASC";
    
    $result = mysqli_query($conn, $shift_query);
    $shifts = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $shifts[] = $row;
    }
    header('Content-Type: application/json');
    echo json_encode($shifts);
    exit();
}

// Get stock items for dashboard (top 4 most critical)
$stock_items = [];
$stock_query = "SELECT product_name, current_stock, unit, reorder_level, max_stock, category_id,
                CASE 
                    WHEN current_stock <= reorder_level/2 THEN 'critical'
                    WHEN current_stock <= reorder_level THEN 'low'
                    WHEN current_stock > reorder_level*2 THEN 'stable'
                    ELSE 'healthy'
                END as status,
                CASE 
                    WHEN current_stock <= reorder_level/2 THEN 'CRITICAL'
                    WHEN current_stock <= reorder_level THEN 'LOW'
                    WHEN current_stock > reorder_level*2 THEN 'STABLE'
                    ELSE 'HEALTHY'
                END as status_text,
                CASE 
                    WHEN category_id = 1 THEN 'error'
                    WHEN category_id = 2 THEN 'check_circle'
                    WHEN category_id = 3 THEN 'egg'
                    ELSE 'bakery_dining'
                END as icon
                FROM products 
                ORDER BY (current_stock / reorder_level) ASC LIMIT 4";
$result = mysqli_query($conn, $stock_query);
while ($row = mysqli_fetch_assoc($result)) {
    $stock_items[] = $row;
}

// Get recent activities
$recent_activities = [];
$activity_query = "SELECT st.notes as action, DATE_FORMAT(st.transaction_date, '%h:%i %p') as time, 
                   p.location,
                   CASE 
                       WHEN st.notes LIKE '%restock%' THEN 'add_circle'
                       WHEN st.notes LIKE '%waste%' THEN 'delete_sweep'
                       WHEN st.notes LIKE '%audit%' THEN 'verified'
                       ELSE 'local_shipping'
                   END as icon,
                   CASE 
                       WHEN st.notes LIKE '%restock%' THEN '#8B5A2B'
                       WHEN st.notes LIKE '%waste%' THEN '#B85C38'
                       WHEN st.notes LIKE '%audit%' THEN '#E6B17E'
                       ELSE '#3C2A21'
                   END as color
                   FROM stock_transactions st
                   JOIN products p ON st.product_id = p.product_id
                   ORDER BY st.transaction_date DESC LIMIT 4";
$result = mysqli_query($conn, $activity_query);
while ($row = mysqli_fetch_assoc($result)) {
    $row['action'] = $row['action'] ?: ($row['icon'] == 'add_circle' ? 'Stock added' : 'Stock updated');
    $recent_activities[] = $row;
}

// Get counts
// Get all products for accurate counting (sync dengan status dalam table)
$all_products_count = [];
$all_products_query = "SELECT p.current_stock, p.reorder_level, p.max_stock 
                       FROM products p";
$all_products_result = mysqli_query($conn, $all_products_query);

$critical_count = 0;
$reorder_count = 0;
$healthy_count = 0;
$low_count = 0;

while ($row = mysqli_fetch_assoc($all_products_result)) {
    $percentage = ($row['current_stock'] / $row['max_stock']) * 100;
    $percentage = min(100, max(0, $percentage));
    
    // Determine status (sama dengan formula dalam staffViewStock.php)
    if ($row['current_stock'] <= $row['reorder_level']/2) {
        $critical_count++;
    } elseif ($row['current_stock'] <= $row['reorder_level']) {
        $reorder_count++;
    } elseif ($percentage <= 50) {
        $low_count++;
    } else {
        $healthy_count++;
    }
}

// Hantar low_count ke low? Tapi dalam card kita guna critical, healthy, reorder
// Low tak ditunjuk dalam card, jadi kita kumpul dengan reorder atau biarkan
// Untuk card, kita guna critical, healthy, reorder

$prediction_message = "All stock levels are healthy. No immediate action needed.";
$prediction_suggestion = "";
$prediction_icon = "check_circle";

if (!empty($prediction_items)) {
    $critical_item = $prediction_items[0];
    if ($critical_item['current_stock'] <= $critical_item['reorder_level']/2) {
        $prediction_icon = "warning";
        $prediction_message = "⚠️ URGENT: " . $critical_item['product_name'] . " is at CRITICAL level!";
        $prediction_suggestion = "Suggested: Order " . ($critical_item['reorder_level'] * 2) . " " . $critical_item['unit'] . " immediately.";
    } elseif ($critical_item['current_stock'] <= $critical_item['reorder_level']) {
        $prediction_icon = "priority_high";
        $prediction_message = "📉 Low Stock Alert: " . $critical_item['product_name'] . " needs attention.";
        $prediction_suggestion = "Suggested: Restock " . $critical_item['reorder_level'] . " " . $critical_item['unit'] . " within 24 hours.";
    }
    if (count($prediction_items) > 1) {
        $prediction_message .= " Also check " . $prediction_items[1]['product_name'] . ".";
    }
}

mysqli_close($conn);

include 'includes/header.php';
?>

<style>
    /* Warna baru - lebih menarik */
    .welcome-gradient {
        background: linear-gradient(135deg, #FAF9F6 0%, #F2E8DF 100%);
    }
    
    .card-hover {
        transition: all 0.3s ease;
        border: 1px solid rgba(139, 90, 43, 0.1);
    }
    
    .card-hover:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 30px -12px rgba(139, 90, 43, 0.2);
        border-color: rgba(139, 90, 43, 0.3);
    }
    
    .stat-card {
        background: linear-gradient(135deg, #FFFFFF 0%, #FAF9F6 100%);
        border: 1px solid rgba(139, 90, 43, 0.08);
        transition: all 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 30px -12px rgba(0, 0, 0, 0.1);
    }
    
    .inventory-card {
        background: white;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .inventory-card:hover {
        transform: translateY(-5px) scale(1.02);
        box-shadow: 0 15px 30px -12px rgba(0, 0, 0, 0.15);
    }
    
    .prediction-card {
        background: linear-gradient(135deg, #8B5A2B 0%, #6B421F 100%);
        position: relative;
        overflow: hidden;
    }
    
    .prediction-card::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        pointer-events: none;
    }
    
    .recent-activity-card {
        background: white;
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
    
    .summary-card {
        background: linear-gradient(135deg, #F2E8DF 0%, #FAF9F6 100%);
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .summary-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px -8px rgba(0, 0, 0, 0.1);
    }
    
    /* Action buttons - kekalkan design asal */
    .action-in-gradient {
        background: linear-gradient(135deg, #8B5A2B 0%, #B07A4A 100%);
    }
    
    .action-out-gradient {
        background: linear-gradient(135deg, #B85C38 0%, #D1734F 100%);
    }
    
    /* Shift Modal Styles */
    .shift-modal {
        max-width: 600px;
        width: 90%;
    }
    
    .shift-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .shift-table th {
        text-align: left;
        padding: 10px;
        background: #F2E8DF;
        font-weight: bold;
        font-size: 0.75rem;
        text-transform: uppercase;
        color: #3C2A21/60;
    }
    
    .shift-table td {
        padding: 10px;
        border-bottom: 1px solid rgba(60, 42, 33, 0.1);
    }
    
    .shift-badge-today {
        background: #8B5A2B;
        color: white;
        padding: 2px 8px;
        border-radius: 9999px;
        font-size: 0.7rem;
        font-weight: bold;
    }
    
    .shift-badge-upcoming {
        background: #7A8C71;
        color: white;
        padding: 2px 8px;
        border-radius: 9999px;
        font-size: 0.7rem;
        font-weight: bold;
    }
    
    .shift-badge-past {
        background: #B85C38;
        color: white;
        padding: 2px 8px;
        border-radius: 9999px;
        font-size: 0.7rem;
        font-weight: bold;
    }
</style>

<main class="px-6 md:px-10 py-8 max-w-7xl mx-auto w-full flex flex-col gap-10">
    <!-- Store Status Section - Warna baru -->
    <section class="welcome-gradient flex flex-col md:flex-row justify-between items-center gap-8 p-10 rounded-3xl border border-[#8B5A2B]/10 card-hover">
        <div class="flex flex-col gap-3">
            <div class="inline-flex items-center gap-2 text-[#B85C38] font-black text-xs uppercase tracking-[0.2em]">
                <span class="size-2 bg-[#B85C38] rounded-full animate-pulse"></span>
                Store Live Status
            </div>
            <h1 class="text-4xl md:text-5xl font-black leading-tight text-[#3C2A21]">Welcome, <?php echo htmlspecialchars($staff_name); ?>! ☕️</h1>
            <p class="text-[#3C2A21]/60 font-medium max-w-lg text-lg">Let's make today a great day! Don't forget to update stock before closing time, team!</p>
        </div>
        <div class="flex flex-col gap-4 shrink-0">
            <div class="bg-white/80 backdrop-blur-sm px-6 py-4 rounded-2xl border border-[#8B5A2B]/10 flex items-center gap-4 shadow-sm">
                <span class="material-symbols-outlined text-[#8B5A2B] text-3xl">schedule</span>
                <div class="flex flex-col">
                    <p id="currentDate" class="text-[10px] font-bold uppercase text-[#3C2A21]/40"><?php echo $current_date; ?></p>
                    <p id="currentTime" class="text-xl font-black text-[#3C2A21]"><?php echo $current_time; ?></p>
                </div>
            </div>
            <button class="bg-gradient-to-r from-[#3C2A21] to-[#5D3A1A] text-white px-8 py-4 rounded-full text-xs font-bold uppercase tracking-[0.15em] hover:from-[#8B5A2B] hover:to-[#B07A4A] transition-all duration-300 shadow-lg hover:shadow-xl" onclick="openShiftModal()">
                View My Shift
            </button>
        </div>
    </section>

    <!-- Action Buttons - KEKALKAN DESIGN ASAL -->
    <section class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Record Stock-In -->
        <button class="action-in-gradient group relative flex flex-col items-start justify-between p-10 rounded-3xl text-white h-72 shadow-xl shadow-[#8B5A2B]/20 hover:scale-[1.02] transition-all overflow-hidden text-left border border-white/10" onclick="window.location.href='staffViewStock.php?action=stockin'">
            <div class="absolute right-[-20px] top-[-20px] opacity-10 scale-[1.8] rotate-12 transition-transform group-hover:rotate-0 duration-700">
                <span class="material-symbols-outlined text-[160px]">inventory</span>
            </div>
            <div class="size-20 bg-white/20 backdrop-blur-lg rounded-2xl flex items-center justify-center mb-4 border border-white/30 shadow-inner">
                <span class="material-symbols-outlined text-5xl">input</span>
            </div>
            <div>
                <h3 class="text-4xl font-black mb-2 tracking-tight">Record Stock-In</h3>
                <p class="text-white/80 font-medium text-lg">Log deliveries & kitchen supplies</p>
            </div>
        </button>

        <!-- Record Stock-Out -->
        <button class="action-out-gradient group relative flex flex-col items-start justify-between p-10 rounded-3xl text-white h-72 shadow-xl shadow-[#B85C38]/20 hover:scale-[1.02] transition-all overflow-hidden text-left border border-white/10" onclick="window.location.href='staffViewStock.php?action=stockout'">
            <div class="absolute right-[-20px] top-[-20px] opacity-10 scale-[1.8] -rotate-12 transition-transform group-hover:rotate-0 duration-700">
                <span class="material-symbols-outlined text-[160px]">list_alt</span>
            </div>
            <div class="size-20 bg-white/20 backdrop-blur-lg rounded-2xl flex items-center justify-center mb-4 border border-white/30 shadow-inner">
                <span class="material-symbols-outlined text-5xl">output</span>
            </div>
            <div>
                <h3 class="text-4xl font-black mb-2 tracking-tight">Record Stock-Out</h3>
                <p class="text-white/80 font-medium text-lg">Wastage, sales & daily usage</p>
            </div>
        </button>
    </section>

    <!-- Inventory Dashboard and Recent Activity -->
    <section class="grid grid-cols-1 lg:grid-cols-3 gap-10">
        <!-- Left Column - Inventory Dashboard -->
        <div class="lg:col-span-2 flex flex-col gap-6">
            <div class="flex items-center justify-between px-2">
                <h2 class="text-[#3C2A21]/40 text-sm font-black uppercase tracking-widest">Inventory Dashboard</h2>
                <a href="staffViewStock.php" class="text-[#B85C38] text-sm font-black uppercase tracking-wider hover:underline underline-offset-4">Full View →</a>
            </div>
            
            <!-- Inventory Cards - Warna baru -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <?php foreach ($stock_items as $item): 
                    $gradient = ($item['status'] == 'critical') ? 'linear-gradient(135deg, #B85C38 0%, #9B3E1F 100%)' : (($item['status'] == 'low') ? 'linear-gradient(135deg, #E6B17E 0%, #C4925A 100%)' : 'linear-gradient(135deg, #7A8C71 0%, #5A6B51 100%)');
                ?>
                <div class="inventory-card flex flex-col gap-4 rounded-3xl p-6 shadow-sm hover:shadow-xl" style="background: <?php echo $gradient; ?>; border: none;" onclick="window.location.href='staffViewStock.php'">
                    <div class="size-10 rounded-xl flex items-center justify-center shadow-lg bg-white/20 backdrop-blur-sm">
                        <span class="material-symbols-outlined text-2xl text-white"><?php echo $item['icon']; ?></span>
                    </div>
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest mb-1 text-white/80"><?php echo $item['product_name']; ?></p>
                        <p class="text-3xl font-black text-white"><?php echo $item['current_stock'] . $item['unit']; ?></p>
                    </div>
                    <div class="text-white text-[10px] font-bold px-3 py-1 rounded-full w-fit bg-white/20 backdrop-blur-sm"><?php echo $item['status_text']; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Smart Prediction - Warna baru -->
            <div class="prediction-card text-white rounded-[2rem] p-8 flex items-center justify-between shadow-2xl relative overflow-hidden">
                <div class="absolute right-0 bottom-0 opacity-10">
                    <span class="material-symbols-outlined text-[140px] translate-y-1/4 translate-x-1/4">psychology</span>
                </div>
                <div class="flex items-center gap-6 relative z-10">
                    <div class="size-16 bg-white/20 rounded-2xl flex items-center justify-center border border-white/20 backdrop-blur-sm shrink-0">
                        <span class="material-symbols-outlined text-4xl text-[#E6B17E]"><?php echo $prediction_icon; ?></span>
                    </div>
                    <div class="max-w-md">
                        <p class="text-[#E6B17E] font-black text-xl mb-1">Smart Prediction</p>
                        <p class="text-white/90 text-sm leading-relaxed font-medium"><?php echo $prediction_message; ?></p>
                        <?php if ($prediction_suggestion): ?>
                        <p class="text-white/70 text-xs mt-2"><?php echo $prediction_suggestion; ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <button class="bg-white/10 hover:bg-white/20 p-2 rounded-full transition-colors relative z-10 border border-white/20" onclick="this.parentElement.style.display='none'">
                    <span class="material-symbols-outlined text-sm">close</span>
                </button>
            </div>
        </div>

        <!-- Right Column - Recent Activity -->
        <div class="flex flex-col gap-6">
            <div class="flex items-center px-2">
                <h2 class="text-[#3C2A21]/40 text-sm font-black uppercase tracking-widest">Recent Activity</h2>
            </div>
            
            <!-- Activity List - Warna baru -->
            <div class="recent-activity-card rounded-[2rem] border border-[#8B5A2B]/5 overflow-hidden shadow-lg">
                <div class="bg-gradient-to-r from-[#3C2A21] to-[#5D3A1A] px-6 py-4 flex items-center justify-between">
                    <p class="text-white text-xs font-bold uppercase tracking-widest">Live Updates</p>
                    <span class="size-2 bg-[#7A8C71] rounded-full animate-pulse"></span>
                </div>
                
                <div class="flex flex-col divide-y divide-[#3C2A21]/5">
                    <?php foreach ($recent_activities as $activity): ?>
                    <div class="p-5 flex gap-4 hover:bg-[#F2E8DF]/50 transition-all duration-300 cursor-pointer group" onclick="window.location.href='staffReports.php'">
                        <div class="size-12 rounded-xl flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform" style="background: <?php echo $activity['color']; ?>15; color: <?php echo $activity['color']; ?>;">
                            <span class="material-symbols-outlined"><?php echo $activity['icon']; ?></span>
                        </div>
                        <div class="flex flex-col">
                            <p class="text-[#3C2A21] text-sm font-bold"><?php echo $activity['action']; ?></p>
                            <p class="text-[#3C2A21]/40 text-[10px] font-bold uppercase tracking-widest mt-0.5"><?php echo $activity['time']; ?> • <?php echo $activity['location']; ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <button class="w-full py-5 bg-[#F2E8DF]/30 text-[#3C2A21]/50 text-[10px] font-black uppercase tracking-[0.2em] text-center hover:bg-[#F2E8DF]/70 transition-all duration-300" onclick="window.location.href='staffReports.php'">
                    View Full Activity Log →
                </button>
            </div>

            <!-- Stock Summary Cards - Warna baru -->
            <div class="grid grid-cols-3 gap-3">
                <div class="summary-card p-4 rounded-xl text-center shadow-sm" onclick="showSuccess('Critical items: <?php echo $critical_count; ?>')">
                    <p class="text-[#B85C38] text-2xl font-black"><?php echo $critical_count; ?></p>
                    <p class="text-[#3C2A21]/50 text-[8px] font-bold uppercase tracking-wider mt-1">Critical</p>
                </div>
                <div class="summary-card p-4 rounded-xl text-center shadow-sm" onclick="showSuccess('Healthy items: <?php echo $healthy_count; ?>')">
                    <p class="text-[#7A8C71] text-2xl font-black"><?php echo $healthy_count; ?></p>
                    <p class="text-[#3C2A21]/50 text-[8px] font-bold uppercase tracking-wider mt-1">Healthy</p>
                </div>
                <div class="summary-card p-4 rounded-xl text-center shadow-sm" onclick="showSuccess('Reorder items: <?php echo $reorder_count; ?>')">
                    <p class="text-[#3C2A21] text-2xl font-black"><?php echo $reorder_count; ?></p>
                    <p class="text-[#3C2A21]/50 text-[8px] font-bold uppercase tracking-wider mt-1">Reorder</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Quick Actions Bar - Warna baru -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
        <button class="quick-action-btn p-4 rounded-xl flex items-center gap-3 transition-all duration-300 shadow-sm" onclick="window.location.href='staffReports.php'">
            <span class="material-symbols-outlined text-[#8B5A2B]">assessment</span>
            <span class="font-bold text-sm">View Daily Report</span>
        </button>
        <button class="quick-action-btn p-4 rounded-xl flex items-center gap-3 transition-all duration-300 shadow-sm" onclick="window.location.href='staffViewStock.php'">
            <span class="material-symbols-outlined text-[#8B5A2B]">fact_check</span>
            <span class="font-bold text-sm">Start Stock Take</span>
        </button>
        <button class="quick-action-btn p-4 rounded-xl flex items-center gap-3 transition-all duration-300 shadow-sm" onclick="window.location.href='staffSettings.php'">
            <span class="material-symbols-outlined text-[#8B5A2B]">settings</span>
            <span class="font-bold text-sm">Settings</span>
        </button>
    </div>
</main>

<!-- Modal View My Shift -->
<dialog id="shiftModal" class="shift-modal" style="background: #FAF9F6; border-radius: 24px; padding: 32px; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.2); position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); margin: 0;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h3 style="font-size: 24px; font-weight: 900; color: #3C2A21;">My Shift Schedule</h3>
            <p style="color: #3C2A21/60; font-size: 14px; margin-top: 4px;"><?php echo date('F Y'); ?></p>
        </div>
        <button onclick="document.getElementById('shiftModal').close()" style="font-size: 28px; color: #3C2A21/40; background: none; border: none; cursor: pointer;">×</button>
    </div>
    
    <div id="shiftModalContent" style="max-height: 60vh; overflow-y: auto;">
        <div class="text-center py-8 text-[#3C2A21]/40">
            <span class="material-symbols-outlined text-4xl">calendar_month</span>
            <p class="mt-2">Loading...</p>
        </div>
    </div>
    
    <div class="flex justify-end mt-6 pt-4 border-t border-[#3C2A21]/10">
        <button onclick="document.getElementById('shiftModal').close()" class="px-6 py-2 bg-[#8B5A2B] text-white rounded-full text-sm font-bold uppercase tracking-wider hover:bg-[#B07A4A] transition-colors">
            Close
        </button>
    </div>
</dialog>

<script>
function showSuccess(message) {
    alert('✅ ' + message);
}

function openShiftModal() {
    const modal = document.getElementById('shiftModal');
    const content = document.getElementById('shiftModalContent');
    
    content.innerHTML = '<div class="text-center py-8 text-[#3C2A21]/40"><span class="material-symbols-outlined text-4xl">calendar_month</span><p class="mt-2">Loading...</p></div>';
    
    modal.showModal();
    
    fetch('staffDashboard.php?get_shifts=1')
        .then(response => response.json())
        .then(data => {
            if (data.length === 0) {
                content.innerHTML = `
                    <div class="text-center py-8 text-[#3C2A21]/40">
                        <span class="material-symbols-outlined text-5xl">calendar_month</span>
                        <p class="mt-3">No shifts scheduled for this month.</p>
                        <p class="text-sm text-[#3C2A21]/40 mt-1">Please check with your manager for schedule.</p>
                    </div>
                `;
                return;
            }
            
            let html = `<table class="shift-table" style="width:100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid rgba(60,42,33,0.1);">
                        <th style="text-align: left; padding: 10px; font-size: 0.75rem; font-weight: bold; color: #3C2A21/60;">Date</th>
                        <th style="text-align: left; padding: 10px; font-size: 0.75rem; font-weight: bold; color: #3C2A21/60;">Shift Type</th>
                        <th style="text-align: left; padding: 10px; font-size: 0.75rem; font-weight: bold; color: #3C2A21/60;">Time</th>
                        <th style="text-align: left; padding: 10px; font-size: 0.75rem; font-weight: bold; color: #3C2A21/60;">Status</th>
                    </tr>
                </thead>
                <tbody>`;
            
            data.forEach(shift => {
                let statusClass = '';
                let statusText = shift.status;
                if (shift.status === 'Today') {
                    statusClass = 'style="background: #8B5A2B; color: white; padding: 2px 8px; border-radius: 9999px; font-size: 0.7rem; display: inline-block;"';
                } else if (shift.status === 'Upcoming') {
                    statusClass = 'style="background: #7A8C71; color: white; padding: 2px 8px; border-radius: 9999px; font-size: 0.7rem; display: inline-block;"';
                } else {
                    statusClass = 'style="background: #B85C38; color: white; padding: 2px 8px; border-radius: 9999px; font-size: 0.7rem; display: inline-block;"';
                }
                
                html += `<tr style="border-bottom: 1px solid rgba(60,42,33,0.05);">
                    <td style="padding: 10px; font-weight: 500;">${shift.formatted_date}</td>
                    <td style="padding: 10px;">${shift.shift_type.charAt(0).toUpperCase() + shift.shift_type.slice(1)}</td>
                    <td style="padding: 10px;">${shift.start_time_formatted} - ${shift.end_time_formatted}</td>
                    <td style="padding: 10px;"><span ${statusClass}>${statusText}</span></td>
                </tr>`;
            });
            
            html += `</tbody></table>`;
            content.innerHTML = html;
        })
        .catch(error => {
            content.innerHTML = '<div class="text-center py-8 text-red-500">Failed to load shift data.</div>';
        });
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

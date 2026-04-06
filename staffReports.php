<?php
// staffReports.php
define('ACCESS_ALLOWED', true);
require_once 'config/database.php';
requireLogin();

$page_title = 'Reports';

// Connect to database
$conn = getConnection();

// Handle date filter for main reports
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-7 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$report_type = isset($_GET['type']) ? $_GET['type'] : 'all';

// Get daily stock report data for today
$daily_report_date = isset($_GET['daily_date']) ? $_GET['daily_date'] : date('Y-m-d');
$daily_products = [];
$daily_total_items = 0;
$daily_critical_count = 0;
$daily_low_count = 0;
$daily_healthy_count = 0;

$daily_query = "SELECT p.*, c.category_name, c.category_color 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.category_id 
                ORDER BY c.category_name, p.product_name";
$daily_result = mysqli_query($conn, $daily_query);
while ($row = mysqli_fetch_assoc($daily_result)) {
    $category = $row['category_name'] ?? 'Uncategorized';
    if (!isset($daily_products[$category])) {
        $daily_products[$category] = [];
    }
    
    $percentage = ($row['current_stock'] / $row['max_stock']) * 100;
    $percentage = min(100, max(0, $percentage));
    
    // Determine status - SAMA DENGAN staffViewStock.php
    if ($row['current_stock'] <= $row['reorder_level']/2) {
        $status_text = 'CRITICAL';
        $status_color = '#B85C38';
        $status_bg = '#B85C3810';
        $daily_critical_count++;
    } elseif ($row['current_stock'] <= $row['reorder_level']) {
        // REORDER - tidak dikira dalam low atau critical
        $status_text = 'REORDER';
        $status_color = '#3B82F6';
        $status_bg = '#3B82F610';
        // REORDER tidak ditambah ke mana-mana count
    } elseif ($percentage <= 50) {
        $status_text = 'LOW';
        $status_color = '#E6B17E';
        $status_bg = '#E6B17E10';
        $daily_low_count++;
    } else {
        $status_text = 'HEALTHY';
        $status_color = '#7A8C71';
        $status_bg = '#7A8C7110';
        $daily_healthy_count++;
    }
    
    $daily_products[$category][] = [
        'id' => $row['product_id'],
        'name' => $row['product_name'],
        'current_stock' => $row['current_stock'],
        'unit' => $row['unit'],
        'max_stock' => $row['max_stock'],
        'status_text' => $status_text,
        'status_color' => $status_color,
        'status_bg' => $status_bg,
        'percentage' => round($percentage)
    ];
    $daily_total_items++;
}

// Get last update info for daily report - ambil yang paling akhir
$last_update_query = "SELECT u.full_name, st.transaction_date as last_update
                      FROM stock_transactions st
                      JOIN users u ON st.performed_by = u.user_id
                      WHERE DATE(st.transaction_date) = '$daily_report_date'
                      ORDER BY st.transaction_date DESC
                      LIMIT 1";
$last_update_result = mysqli_query($conn, $last_update_query);
$last_update = mysqli_fetch_assoc($last_update_result);

$updated_by = $last_update['full_name'] ?? $_SESSION['user_name'] ?? 'System';
$last_update_time = $last_update['last_update'] ? date('h:i A', strtotime($last_update['last_update'])) : 'No updates today';
$daily_day_name = date('l', strtotime($daily_report_date));
$daily_formatted_date = date('F j, Y', strtotime($daily_report_date));

// Get reports data from stock_transactions
$reports_data = [];
$query = "SELECT 
            DATE(st.transaction_date) as date,
            p.product_name as item,
            c.category_name as category,
            SUM(CASE WHEN st.transaction_type = 'IN' THEN st.quantity ELSE 0 END) as stock_in,
            SUM(CASE WHEN st.transaction_type = 'OUT' THEN st.quantity ELSE 0 END) as stock_out,
            SUM(CASE WHEN st.transaction_type = 'OUT' AND st.notes LIKE '%waste%' THEN st.quantity ELSE 0 END) as waste,
            (SELECT current_stock FROM products WHERE product_id = p.product_id) as closing_stock,
            (SELECT current_stock * price_per_unit FROM products WHERE product_id = p.product_id) as value
          FROM stock_transactions st
          JOIN products p ON st.product_id = p.product_id
          LEFT JOIN categories c ON p.category_id = c.category_id
          WHERE DATE(st.transaction_date) BETWEEN '$start_date' AND '$end_date'
          GROUP BY DATE(st.transaction_date), p.product_id
          ORDER BY st.transaction_date DESC, p.product_name";

$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    $reports_data[] = $row;
}

// Filter data based on date range AND report type
$filtered_data = array_filter($reports_data, function($row) use ($start_date, $end_date, $report_type) {
    if ($row['date'] < $start_date || $row['date'] > $end_date) {
        return false;
    }
    if ($report_type == 'waste') {
        return $row['waste'] > 0;
    } elseif ($report_type == 'value') {
        return $row['value'] > 0;
    }
    return true;
});

// Calculate summary for today based on filtered data
$today = date('Y-m-d');
$total_value_today = 0;
$total_waste_today = 0;
$total_stock_in_today = 0;
$total_stock_out_today = 0;
$unique_items_today = [];

foreach ($filtered_data as $row) {
    if ($row['date'] == $today) {
        $total_value_today += $row['value'];
        $total_waste_today += $row['waste'];
        $total_stock_in_today += $row['stock_in'];
        $total_stock_out_today += $row['stock_out'];
        $unique_items_today[$row['item']] = true;
    }
}

$summary = [
    'total_items' => count($unique_items_today),
    'total_value' => $total_value_today,
    'total_waste' => $total_waste_today,
    'total_stock_in' => $total_stock_in_today,
    'total_stock_out' => $total_stock_out_today,
    'avg_daily_usage' => $total_stock_out_today > 0 ? ($total_stock_out_today / 7) : 0
];

// Get top moving items
$top_items_query = "SELECT p.product_name, SUM(st.quantity) as total_used
                    FROM stock_transactions st
                    JOIN products p ON st.product_id = p.product_id
                    WHERE st.transaction_type = 'OUT'
                    AND DATE(st.transaction_date) BETWEEN '$start_date' AND '$end_date'
                    GROUP BY p.product_id ORDER BY total_used DESC LIMIT 3";
$top_items_result = mysqli_query($conn, $top_items_query);
$top_items = [];
while ($row = mysqli_fetch_assoc($top_items_result)) {
    $top_items[] = $row;
}

// Get waste analysis
$waste_query = "SELECT p.product_name, SUM(st.quantity) as waste_total
                FROM stock_transactions st
                JOIN products p ON st.product_id = p.product_id
                WHERE st.transaction_type = 'OUT' AND st.notes LIKE '%waste%'
                AND DATE(st.transaction_date) BETWEEN '$start_date' AND '$end_date'
                GROUP BY p.product_id ORDER BY waste_total DESC LIMIT 3";
$waste_result = mysqli_query($conn, $waste_query);
$waste_items = [];
while ($row = mysqli_fetch_assoc($waste_result)) {
    $waste_items[] = $row;
}

// Calculate waste rate
$usage_query = "SELECT SUM(CASE WHEN transaction_type = 'OUT' THEN quantity ELSE 0 END) as used,
                SUM(CASE WHEN transaction_type = 'OUT' AND notes LIKE '%waste%' THEN quantity ELSE 0 END) as wasted
                FROM stock_transactions
                WHERE DATE(transaction_date) BETWEEN '$start_date' AND '$end_date'";
$usage_result = mysqli_query($conn, $usage_query);
$usage = mysqli_fetch_assoc($usage_result);
$waste_rate = ($usage['used'] > 0) ? round(($usage['wasted'] / $usage['used']) * 100, 1) : 0;

mysqli_close($conn);

// Handle export
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="stock_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date', 'Item', 'Category', 'Stock In', 'Stock Out', 'Waste', 'Closing Stock', 'Value (RM)']);
    
    foreach ($filtered_data as $row) {
        fputcsv($output, [
            $row['date'],
            $row['item'],
            $row['category'],
            $row['stock_in'],
            $row['stock_out'],
            $row['waste'],
            $row['closing_stock'],
            $row['value']
        ]);
    }
    
    fclose($output);
    exit();
}

include 'includes/header.php';
?>

<style>
    /* Warna baru untuk cards */
    .stat-card {
        background: linear-gradient(135deg, #FFFFFF 0%, #FAF9F6 100%);
        border: 1px solid rgba(139, 90, 43, 0.08);
        transition: all 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px -12px rgba(0, 0, 0, 0.1);
    }
    
    .filter-section {
        background: linear-gradient(135deg, #FAF9F6 0%, #F2E8DF 100%);
        border: 1px solid rgba(139, 90, 43, 0.1);
    }
    
    .report-table-header {
        background: linear-gradient(135deg, #3C2A21 0%, #5D3A1A 100%);
    }
    
    .report-table tbody tr {
        transition: all 0.2s ease;
    }
    
    .report-table tbody tr:hover {
        background: rgba(139, 90, 43, 0.05);
        transform: scale(1.01);
    }
    
    .insight-card {
        background: linear-gradient(135deg, #FFFFFF 0%, #FAF9F6 100%);
        border: 1px solid rgba(139, 90, 43, 0.08);
        transition: all 0.3s ease;
    }
    
    .insight-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px -12px rgba(139, 90, 43, 0.15);
    }
    
    .btn-apply {
        background: linear-gradient(135deg, #8B5A2B 0%, #B07A4A 100%);
        transition: all 0.3s ease;
    }
    
    .btn-apply:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px -8px rgba(139, 90, 43, 0.4);
    }
    
    .btn-reset {
        background: white;
        border: 1px solid rgba(139, 90, 43, 0.2);
        transition: all 0.3s ease;
    }
    
    .btn-reset:hover {
        background: #F2E8DF;
        transform: translateY(-2px);
    }
    
    /* Daily Report Card */
    .daily-report-card {
        background: #FAF9F6;
        border-radius: 1.5rem;
        border: 1px solid rgba(60, 42, 33, 0.1);
        box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        margin-bottom: 2rem;
    }
    
    .daily-report-header {
        background: #3C2A21;
        padding: 1rem 1.5rem;
        color: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .daily-stats {
        display: flex;
        gap: 1rem;
        margin-top: 1rem;
        margin-bottom: 1rem;
    }
    
    .daily-stat-card {
        flex: 1;
        background: #F2E8DF;
        padding: 0.75rem;
        border-radius: 0.75rem;
        text-align: center;
    }
    
    .status-badge {
        display: inline-block;
        padding: 0.2rem 0.6rem;
        border-radius: 9999px;
        font-size: 0.7rem;
        font-weight: bold;
    }
    
    .daily-category-title {
        font-size: 1rem;
        font-weight: bold;
        color: #8B5A2B;
        margin: 1rem 0 0.5rem 0;
        padding-left: 0.5rem;
        border-left: 3px solid #8B5A2B;
    }
    
    .daily-stock-table {
        width: 100%;
        font-size: 0.875rem;
    }
    
    .daily-stock-table th {
        text-align: left;
        padding: 0.5rem;
        background: #F2E8DF;
        font-weight: bold;
        font-size: 0.7rem;
        text-transform: uppercase;
        color: #3C2A21/60;
    }
    
    .daily-stock-table td {
        padding: 0.5rem;
        border-bottom: 1px solid rgba(60, 42, 33, 0.05);
    }
    
    @media print {
        /* Hide everything by default */
        body * { visibility: hidden; }
        
        /* Show only the main stock report section when printing */
        .print-stock-area, .print-stock-area * { visibility: visible; }
        .print-stock-area { 
            position: absolute; 
            top: 0; 
            left: 0; 
            width: 100%; 
            margin: 0; 
            padding: 20px; 
        }
        
        /* Hide daily report card and other elements when printing stock report */
        .daily-report-card,
        .no-print {
            display: none !important;
        }
    }
</style>

<main class="px-6 md:px-10 py-8 max-w-7xl mx-auto w-full">
    
    <!-- =============================================== -->
    <!-- DAILY STOCK REPORT -->
    <!-- =============================================== -->
    <div class="daily-report-card no-print">
        <div class="daily-report-header">
            <h2 class="text-white text-sm font-bold uppercase tracking-widest flex items-center gap-2">
                <span class="material-symbols-outlined text-[#E6B17E]">calendar_today</span>
                DAILY STOCK REPORT
            </h2>
            <div class="flex gap-2">
                <input type="date" id="dailyDatePicker" value="<?php echo $daily_report_date; ?>" class="px-3 py-1 rounded-lg text-sm bg-white/20 text-white border border-white/30">
                <button onclick="changeDailyDate()" class="px-3 py-1 bg-white/20 rounded-lg text-xs font-bold hover:bg-white/30 transition-colors">View</button>
                <button onclick="printDailyReport()" class="px-3 py-1 bg-[#8B5A2B] rounded-lg text-xs font-bold hover:bg-[#B07A4A] transition-colors flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">print</span>
                    Print
                </button>
            </div>
        </div>
        
        <div class="p-6">
            <div class="daily-stats">
                <div class="daily-stat-card"><p class="text-xs text-[#3C2A21]/60">Total Items</p><p class="text-2xl font-black text-[#3C2A21]"><?php echo $daily_total_items; ?></p></div>
                <div class="daily-stat-card"><p class="text-xs text-[#3C2A21]/60">Critical / Out of Stock</p><p class="text-2xl font-black text-[#B85C38]"><?php echo $daily_critical_count; ?></p></div>
                <div class="daily-stat-card"><p class="text-xs text-[#3C2A21]/60">Low Stock</p><p class="text-2xl font-black text-[#E6B17E]"><?php echo $daily_low_count; ?></p></div>
                <div class="daily-stat-card"><p class="text-xs text-[#3C2A21]/60">Healthy</p><p class="text-2xl font-black text-[#7A8C71]"><?php echo $daily_healthy_count; ?></p></div>
            </div>
            <div class="bg-[#F2E8DF] p-3 rounded-xl mb-4 flex justify-between text-sm">
                <div><span class="font-bold">Day:</span> <?php echo $daily_day_name; ?><br><span class="font-bold">Date:</span> <?php echo $daily_formatted_date; ?></div>
                <div><span class="font-bold">Updated By:</span> <?php echo htmlspecialchars($updated_by); ?><br><span class="font-bold">Last Update:</span> <?php echo $last_update_time; ?></div>
            </div>
            <div style="max-height: 400px; overflow-y: auto;">
                <?php foreach ($daily_products as $category => $products): ?>
                <div>
                    <div class="daily-category-title"><?php echo $category; ?></div>
                    <table class="daily-stock-table">
                        <thead>
                            <tr>
                                <th class="w-2/5">Item</th>
                                <th class="w-1/5">Quantity</th>
                                <th class="w-1/5">Unit</th>
                                <th class="w-1/5">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo $product['current_stock']; ?><?php if ($product['max_stock'] > 0): ?> / <?php echo $product['max_stock']; ?><?php endif; ?></td>
                                <td><?php echo $product['unit']; ?></td>
                                <td><span class="status-badge" style="background: <?php echo $product['status_bg']; ?>; color: <?php echo $product['status_color']; ?>;"><?php echo $product['status_text']; ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="mt-4 pt-3 border-t border-[#3C2A21]/5 text-center text-xs text-[#3C2A21]/40">
                <p>This report shows current stock levels as of closing. Any adjustments made after this time will be reflected in tomorrow's report.</p>
            </div>
        </div>
    </div>

    <!-- PRINT VERSION (Hidden normally, visible when printing) -->
    <div id="dailyPrintArea" style="display: none;">
        <div class="print-daily-area">
            <div style="text-align: center; margin-bottom: 20px;">
                <?php 
                $logo_path = "images/logo.png";
                if (file_exists($logo_path)) {
                    echo '<img src="' . $logo_path . '" alt="Salmonly Café Logo" style="width: 80px; height: 80px; object-fit: cover; margin-bottom: 10px; display: block; margin-left: auto; margin-right: auto;">';
                }
                ?>
                <h1 style="font-size: 24px; font-weight: bold; color: #8B5A2B; margin: 0;">SALMONLY CAFÉ</h1>
                <h2 style="font-size: 18px; font-weight: bold;">Daily Stock Report</h2>
                <p style="font-size: 12px;">End of Day Inventory Summary</p>
            </div>
            
            <div style="display: flex; justify-content: space-between; margin-bottom: 20px; padding: 10px; border: 1px solid #ddd;">
                <div><strong>Day:</strong> <?php echo $daily_day_name; ?><br><strong>Date:</strong> <?php echo $daily_formatted_date; ?></div>
                <div><strong>Updated By:</strong> <?php echo htmlspecialchars($updated_by); ?><br><strong>Last Update:</strong> <?php echo $last_update_time; ?></div>
                <div><strong>Total Items:</strong> <?php echo $daily_total_items; ?><br><strong>Critical:</strong> <?php echo $daily_critical_count; ?></div>
            </div>
            
            <?php foreach ($daily_products as $category => $products): ?>
            <div>
                <div style="font-size: 14px; font-weight: bold; color: #8B5A2B; margin: 15px 0 10px 0; padding-left: 5px; border-left: 3px solid #8B5A2B;"><?php echo $category; ?></div>
                <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                    <thead>
                        <tr style="background: #f2f2f2;">
                            <th style="text-align: left; padding: 8px; border: 1px solid #ddd; width: 40%;">Item</th>
                            <th style="text-align: left; padding: 8px; border: 1px solid #ddd; width: 20%;">Quantity</th>
                            <th style="text-align: left; padding: 8px; border: 1px solid #ddd; width: 20%;">Unit</th>
                            <th style="text-align: left; padding: 8px; border: 1px solid #ddd; width: 20%;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td style="padding: 6px; border: 1px solid #ddd; word-wrap: break-word;"><?php echo htmlspecialchars($product['name']); ?></td>
                            <td style="padding: 6px; border: 1px solid #ddd;"><?php echo $product['current_stock']; ?><?php if ($product['max_stock'] > 0): ?> / <?php echo $product['max_stock']; ?><?php endif; ?></td>
                            <td style="padding: 6px; border: 1px solid #ddd;"><?php echo $product['unit']; ?></td>
                            <td style="padding: 6px; border: 1px solid #ddd;"><?php echo $product['status_text']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endforeach; ?>
            
            <div style="margin-top: 20px; padding-top: 10px; text-align: center; font-size: 10px; border-top: 1px solid #ddd;">
                <p>This is a computer-generated report. No signature required.</p>
                <p>Salmonly Café - Stock Reporting System</p>
                <p>Generated on: <?php echo date('d/m/Y h:i A'); ?></p>
            </div>
        </div>
    </div>

    <!-- =============================================== -->
    <!-- MAIN REPORTS SECTION -->
    <!-- =============================================== -->
    <div class="print-stock-area">
        <div class="flex items-center justify-between mb-6">
            <div><h1 class="text-3xl md:text-4xl font-black text-[#3C2A21]">Stock Reports</h1><p class="text-[#3C2A21]/60 mt-1">View and analyze inventory movement</p></div>
            <div class="flex gap-3">
                <a href="?export=csv&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&type=<?php echo $report_type; ?>" class="bg-white border border-[#3C2A21]/10 px-4 py-2 rounded-full text-xs font-bold uppercase tracking-wider hover:bg-[#F2E8DF] transition-colors flex items-center gap-2"><span class="material-symbols-outlined text-lg">download</span>Export CSV</a>
                <button onclick="window.print()" class="bg-white border border-[#3C2A21]/10 px-4 py-2 rounded-full text-xs font-bold uppercase tracking-wider hover:bg-[#F2E8DF] transition-colors flex items-center gap-2"><span class="material-symbols-outlined text-lg">print</span>Print</button>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="stat-card p-5 rounded-2xl shadow-sm"><p class="text-[#3C2A21]/40 text-xs font-bold uppercase tracking-widest mb-1">Active Items</p><p class="text-3xl font-black text-[#3C2A21]"><?php echo $summary['total_items']; ?></p></div>
            <div class="stat-card p-5 rounded-2xl shadow-sm"><p class="text-[#3C2A21]/40 text-xs font-bold uppercase tracking-widest mb-1">Stock Value</p><p class="text-3xl font-black text-[#3C2A21]">RM <?php echo number_format($summary['total_value'], 2); ?></p></div>
            <div class="stat-card p-5 rounded-2xl shadow-sm"><p class="text-[#3C2A21]/40 text-xs font-bold uppercase tracking-widest mb-1">Total Waste</p><p class="text-3xl font-black text-[#3C2A21]"><?php echo number_format($summary['total_waste'], 1); ?> units</p></div>
            <div class="stat-card p-5 rounded-2xl shadow-sm"><p class="text-[#3C2A21]/40 text-xs font-bold uppercase tracking-widest mb-1">Avg Daily Usage</p><p class="text-3xl font-black text-[#3C2A21]">RM <?php echo number_format($summary['avg_daily_usage'], 2); ?></p></div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section rounded-2xl p-6 mb-6">
            <form method="GET" action="" class="flex flex-wrap items-end gap-4">
                <div class="flex-1 min-w-[180px]"><label class="block text-xs font-bold text-[#3C2A21]/40 uppercase tracking-widest mb-2">Start Date</label><input type="date" name="start_date" value="<?php echo $start_date; ?>" class="form-input w-full"></div>
                <div class="flex-1 min-w-[180px]"><label class="block text-xs font-bold text-[#3C2A21]/40 uppercase tracking-widest mb-2">End Date</label><input type="date" name="end_date" value="<?php echo $end_date; ?>" class="form-input w-full"></div>
                <div class="flex-1 min-w-[180px]"><label class="block text-xs font-bold text-[#3C2A21]/40 uppercase tracking-widest mb-2">Report Type</label><select name="type" class="form-input w-full"><option value="all" <?php echo $report_type == 'all' ? 'selected' : ''; ?>>All Reports</option><option value="waste" <?php echo $report_type == 'waste' ? 'selected' : ''; ?>>Waste Report</option><option value="value" <?php echo $report_type == 'value' ? 'selected' : ''; ?>>Value Report</option></select></div>
                <div class="flex gap-2"><button type="submit" class="btn-apply text-white px-6 py-2 rounded-full text-sm font-bold uppercase tracking-wider">Apply Filters</button><a href="staffReports.php" class="btn-reset px-6 py-2 rounded-full text-sm font-bold uppercase tracking-wider">Reset</a></div>
            </form>
        </div>

        <!-- Reports Table -->
        <div class="bg-white rounded-2xl border border-[#3C2A21]/5 overflow-hidden shadow-md">
            <div class="report-table-header px-6 py-3"><h2 class="text-white text-sm font-bold uppercase tracking-widest">STOCK MOVEMENT REPORT (<?php echo count($filtered_data); ?> entries)</h2></div>
            <div class="overflow-x-auto">
                <table class="report-table w-full">
                    <thead><tr class="border-b border-[#3C2A21]/5"><th class="text-left p-3 text-xs font-bold text-[#3C2A21]/40 uppercase">DATE</th><th class="text-left p-3 text-xs font-bold text-[#3C2A21]/40 uppercase">ITEM</th><th class="text-left p-3 text-xs font-bold text-[#3C2A21]/40 uppercase">CATEGORY</th><th class="text-left p-3 text-xs font-bold text-[#3C2A21]/40 uppercase">STOCK IN</th><th class="text-left p-3 text-xs font-bold text-[#3C2A21]/40 uppercase">STOCK OUT</th><th class="text-left p-3 text-xs font-bold text-[#3C2A21]/40 uppercase">WASTE</th><th class="text-left p-3 text-xs font-bold text-[#3C2A21]/40 uppercase">CLOSING</th><th class="text-left p-3 text-xs font-bold text-[#3C2A21]/40 uppercase">VALUE (RM)</th> </thead>
                    <tbody>
                        <?php if (empty($filtered_data)): ?>
                        <tr><td colspan="8" class="text-center py-8 text-[#3C2A21]/40">No reports found</td></tr>
                        <?php else: $total_value = 0; foreach ($filtered_data as $row): $total_value += $row['value']; ?>
                        <tr class="border-b border-[#3C2A21]/5 hover:bg-[#F2E8DF]/30 transition-all"><td class="p-3 text-sm"><?= date('d/m/Y', strtotime($row['date'])) ?></td><td class="p-3 font-medium"><?= $row['item'] ?></td><td class="p-3"><span class="text-xs px-2 py-1 rounded-full" style="background: <?= $row['category'] == 'Seafood' ? '#B85C3810' : '#8B5A2B10' ?>;"><?= $row['category'] ?? 'Uncategorized' ?></span></td><td class="p-3 text-green-600 font-medium">+<?= $row['stock_in'] ?></td><td class="p-3 text-blue-600 font-medium">-<?= $row['stock_out'] ?></td><td class="p-3 text-[#B85C38] font-medium"><?= $row['waste'] > 0 ? $row['waste'] : '-' ?></td><td class="p-3 font-bold"><?= $row['closing_stock'] ?></td><td class="p-3 font-bold">RM <?= number_format($row['value'], 2) ?></td></tr>
                        <?php endforeach; ?><tr class="bg-[#F2E8DF]/30"><td colspan="7" class="text-right font-bold p-3">TOTAL VALUE:</td><td class="font-black text-[#8B5A2B] p-3">RM <?= number_format($total_value, 2) ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Reports Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            <!-- Top Moving Items Card -->
            <div class="rounded-2xl p-5 shadow-md transition-all duration-300 hover:shadow-xl hover:translate-y-[-5px]" style="background: linear-gradient(135deg, #8B5A2B 0%, #B07A4A 100%);">
                <h3 class="text-lg font-bold text-white mb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined text-white">trending_up</span>
                    TOP MOVING ITEMS
                </h3>
                <?php if (empty($top_items)): ?>
                <p class="text-center text-white/70 py-4">No data available</p>
                <?php else: ?>
                <?php foreach ($top_items as $item): ?>
                <div class="flex justify-between items-center py-2 border-b border-white/20">
                    <span class="font-medium text-white"><?= $item['product_name'] ?></span>
                    <span class="text-sm bg-white/20 text-white px-3 py-1 rounded-full"><?= $item['total_used'] ?> units used</span>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Waste Analysis Card -->
            <div class="rounded-2xl p-5 shadow-md transition-all duration-300 hover:shadow-xl hover:translate-y-[-5px]" style="background: linear-gradient(135deg, #B85C38 0%, #D1734F 100%);">
                <h3 class="text-lg font-bold text-white mb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined text-white">delete_sweep</span>
                    WASTE ANALYSIS
                </h3>
                <?php if (empty($waste_items)): ?>
                <p class="text-center text-white/70 py-4">No waste recorded</p>
                <?php else: ?>
                <?php foreach ($waste_items as $item): ?>
                <div class="flex justify-between items-center py-2 border-b border-white/20">
                    <span class="font-medium text-white"><?= $item['product_name'] ?></span>
                    <span class="text-sm bg-white/20 text-white px-3 py-1 rounded-full"><?= $item['waste_total'] ?> units wasted</span>
                </div>
                <?php endforeach; ?>
                <div class="flex justify-between items-center pt-2 mt-2 border-t border-white/20">
                    <span class="font-bold text-white">Waste Rate</span>
                    <span class="text-sm font-bold text-white"><?= $waste_rate ?>% of total usage</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script>
function changeDailyDate() { 
    const date = document.getElementById('dailyDatePicker').value; 
    window.location.href = 'staffReports.php?daily_date=' + date; 
}

function printDailyReport() { 
    // Get the print area content
    const printContents = document.getElementById('dailyPrintArea').innerHTML;
    
    // Check if print area is empty
    if (!printContents || printContents.trim() === '') {
        alert('No content to print. Please try again.');
        return;
    }
    
    // Create a new window for printing
    const printWindow = window.open('', '_blank');
    
    // Write the print content with styles
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Salmonly Café - Daily Stock Report</title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                body {
                    font-family: Arial, sans-serif;
                    padding: 20px;
                    background: white;
                }
                .print-daily-area {
                    max-width: 1200px;
                    margin: 0 auto;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                }
                th, td {
                    border: 1px solid #ddd;
                    padding: 8px;
                    text-align: left;
                }
                th {
                    background-color: #f2f2f2;
                }
                @media print {
                    body {
                        padding: 0;
                        margin: 0;
                    }
                    .no-print {
                        display: none;
                    }
                }
            </style>
        </head>
        <body>
            ${printContents}
        </body>
        </html>
    `);
    
    printWindow.document.close();
    printWindow.print();
    printWindow.close();
}
</script>

<?php include 'includes/footer.php'; ?>

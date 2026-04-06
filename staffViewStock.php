<?php
// staffViewStock.php
// Page: View Stock Inventory

define('ACCESS_ALLOWED', true);
require_once 'config/database.php';
requireLogin();

$page_title = 'View Stock';

// Staff info dari session
$staff_name = $_SESSION['user_name'] ?? 'Staff';
$staff_role = $_SESSION['user_role'] ?? 'Staff';
$current_time = date('h:i A');
$current_date = date('F j, Y');

// Get user theme preference from database
$user_theme = 'light';
if (isset($_SESSION['user_id'])) {
    $conn_theme = getConnection();
    $user_id = $_SESSION['user_id'];
    $theme_result = mysqli_query($conn_theme, "SELECT theme FROM user_settings WHERE user_id = $user_id");
    if ($row = mysqli_fetch_assoc($theme_result)) {
        $user_theme = $row['theme'];
    }
    mysqli_close($conn_theme);
}

// If theme is system, check system preference
if ($user_theme === 'system') {
    $user_theme = 'light';
}

// Connect to database
$conn = getConnection();

// Handle AJAX request for transaction history
if (isset($_GET['get_history'])) {
    $product_id = intval($_GET['get_history']);
    $query = "SELECT st.*, u.full_name as performed_by_name 
              FROM stock_transactions st 
              LEFT JOIN users u ON st.performed_by = u.user_id 
              WHERE st.product_id = $product_id 
              ORDER BY st.transaction_date DESC LIMIT 50";
    $result = mysqli_query($conn, $query);
    $history = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $history[] = $row;
    }
    header('Content-Type: application/json');
    echo json_encode($history);
    exit();
}

// Handle stock update from modal (Daily Stock Take)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_daily_stock'])) {
    $performed_by = $_SESSION['user_id'];
    $changes_made = false;
    $success_count = 0;
    $error_count = 0;
    
    foreach ($_POST['stock'] as $product_id => $new_quantity) {
        $product_id = intval($product_id);
        $new_quantity = floatval($new_quantity);
        
        $result = mysqli_query($conn, "SELECT current_stock FROM products WHERE product_id = $product_id");
        if ($row = mysqli_fetch_assoc($result)) {
            $old_quantity = $row['current_stock'];
            
            if ($new_quantity != $old_quantity) {
                $changes_made = true;
                $difference = $new_quantity - $old_quantity;
                
                $update = "UPDATE products SET current_stock = $new_quantity, updated_at = NOW() WHERE product_id = $product_id";
                if (mysqli_query($conn, $update)) {
                    $success_count++;
                    $transaction_type = ($difference > 0) ? 'IN' : 'OUT';
                    $notes = 'Daily stock adjustment - ' . ($difference > 0 ? 'Increase' : 'Decrease');
                    $insert = "INSERT INTO stock_transactions (product_id, transaction_type, quantity, notes, performed_by) 
                               VALUES ($product_id, '$transaction_type', " . abs($difference) . ", '$notes', $performed_by)";
                    mysqli_query($conn, $insert);
                } else {
                    $error_count++;
                }
            }
        }
    }
    
    if ($changes_made) {
        $success_message = "Stock updated! $success_count item(s) changed.";
        if ($error_count > 0) {
            $error_message = "$error_count item(s) failed to update.";
        }
    } else {
        $success_message = 'No changes were made.';
    }
    
    header("Location: staffViewStock.php");
    exit();
}

// Handle Stock-In from dropdown modal (General)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['stock_in_general'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = floatval($_POST['quantity']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? 'Stock received');
    $performed_by = $_SESSION['user_id'];
    
    if ($quantity <= 0) {
        $error_message = 'Quantity must be greater than 0';
    } else {
        $result = mysqli_query($conn, "SELECT current_stock, unit FROM products WHERE product_id = $product_id");
        $product = mysqli_fetch_assoc($result);
        $new_stock = $product['current_stock'] + $quantity;
        
        $update = "UPDATE products SET current_stock = $new_stock, updated_at = NOW() WHERE product_id = $product_id";
        $insert = "INSERT INTO stock_transactions (product_id, transaction_type, quantity, notes, performed_by) 
                   VALUES ($product_id, 'IN', $quantity, '$notes', $performed_by)";
        
        if (mysqli_query($conn, $update) && mysqli_query($conn, $insert)) {
            $success_message = 'Stock added successfully!';
            header("Location: staffViewStock.php");
            exit();
        } else {
            $error_message = 'Failed to add stock: ' . mysqli_error($conn);
        }
    }
}

// Handle Stock-Out from dropdown modal (General)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['stock_out_general'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = floatval($_POST['quantity']);
    $reason = mysqli_real_escape_string($conn, $_POST['reason'] ?? 'Usage');
    $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
    $performed_by = $_SESSION['user_id'];
    
    if ($quantity <= 0) {
        $error_message = 'Quantity must be greater than 0';
    } else {
        $result = mysqli_query($conn, "SELECT current_stock, unit FROM products WHERE product_id = $product_id");
        $product = mysqli_fetch_assoc($result);
        
        if ($product['current_stock'] < $quantity) {
            $error_message = 'Insufficient stock! Current stock: ' . $product['current_stock'] . ' ' . $product['unit'];
        } else {
            $new_stock = $product['current_stock'] - $quantity;
            $full_notes = $reason . ($notes ? ' - ' . $notes : '');
            
            $update = "UPDATE products SET current_stock = $new_stock, updated_at = NOW() WHERE product_id = $product_id";
            $insert = "INSERT INTO stock_transactions (product_id, transaction_type, quantity, notes, performed_by) 
                       VALUES ($product_id, 'OUT', $quantity, '$full_notes', $performed_by)";
            
            if (mysqli_query($conn, $update) && mysqli_query($conn, $insert)) {
                $success_message = 'Stock removed successfully!';
                header("Location: staffViewStock.php");
                exit();
            } else {
                $error_message = 'Failed to remove stock: ' . mysqli_error($conn);
            }
        }
    }
}

// Handle Stock-In from table button (Specific Product)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['stock_in'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = floatval($_POST['quantity']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? 'Stock received');
    $performed_by = $_SESSION['user_id'];
    
    if ($quantity <= 0) {
        $error_message = 'Quantity must be greater than 0';
    } else {
        $result = mysqli_query($conn, "SELECT current_stock, unit FROM products WHERE product_id = $product_id");
        $product = mysqli_fetch_assoc($result);
        $new_stock = $product['current_stock'] + $quantity;
        
        $update = "UPDATE products SET current_stock = $new_stock, updated_at = NOW() WHERE product_id = $product_id";
        $insert = "INSERT INTO stock_transactions (product_id, transaction_type, quantity, notes, performed_by) 
                   VALUES ($product_id, 'IN', $quantity, '$notes', $performed_by)";
        
        if (mysqli_query($conn, $update) && mysqli_query($conn, $insert)) {
            $success_message = 'Stock added successfully!';
            header("Location: staffViewStock.php");
            exit();
        } else {
            $error_message = 'Failed to add stock: ' . mysqli_error($conn);
        }
    }
}

// Handle Stock-Out from table button (Specific Product)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['stock_out'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = floatval($_POST['quantity']);
    $reason = mysqli_real_escape_string($conn, $_POST['reason'] ?? 'Usage');
    $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
    $performed_by = $_SESSION['user_id'];
    
    if ($quantity <= 0) {
        $error_message = 'Quantity must be greater than 0';
    } else {
        $result = mysqli_query($conn, "SELECT current_stock, unit FROM products WHERE product_id = $product_id");
        $product = mysqli_fetch_assoc($result);
        
        if ($product['current_stock'] < $quantity) {
            $error_message = 'Insufficient stock! Current stock: ' . $product['current_stock'] . ' ' . $product['unit'];
        } else {
            $new_stock = $product['current_stock'] - $quantity;
            $full_notes = $reason . ($notes ? ' - ' . $notes : '');
            
            $update = "UPDATE products SET current_stock = $new_stock, updated_at = NOW() WHERE product_id = $product_id";
            $insert = "INSERT INTO stock_transactions (product_id, transaction_type, quantity, notes, performed_by) 
                       VALUES ($product_id, 'OUT', $quantity, '$full_notes', $performed_by)";
            
            if (mysqli_query($conn, $update) && mysqli_query($conn, $insert)) {
                $success_message = 'Stock removed successfully!';
                header("Location: staffViewStock.php");
                exit();
            } else {
                $error_message = 'Failed to remove stock: ' . mysqli_error($conn);
            }
        }
    }
}

// Get categories from database
$categories = ['All Items'];
$cat_result = mysqli_query($conn, "SELECT category_name FROM categories");
while ($cat = mysqli_fetch_assoc($cat_result)) {
    $categories[] = $cat['category_name'];
}

// Get all products for dropdown
$all_products_dropdown = [];
$product_result = mysqli_query($conn, "SELECT product_id, product_name, current_stock, unit FROM products ORDER BY product_name");
while ($row = mysqli_fetch_assoc($product_result)) {
    $all_products_dropdown[] = $row;
}

// Get stock items from database (grouped for modal)
$products_by_category = [];
$all_products = [];

$result = mysqli_query($conn, "SELECT p.*, c.category_name, c.category_color 
                               FROM products p 
                               LEFT JOIN categories c ON p.category_id = c.category_id 
                               ORDER BY c.category_name, p.product_name");

while ($row = mysqli_fetch_assoc($result)) {
    $category = $row['category_name'] ?? 'Uncategorized';
    if (!isset($products_by_category[$category])) {
        $products_by_category[$category] = [];
    }
    
    $percentage = ($row['current_stock'] / $row['max_stock']) * 100;
    $percentage = min(100, max(0, $percentage));
    
    // Determine status
    if ($row['current_stock'] <= $row['reorder_level']/2) {
        $status = 'critical';
        $status_text = 'CRITICAL';
        $status_color = 'bg-red-100 text-red-700 border-red-300';
    } elseif ($row['current_stock'] <= $row['reorder_level']) {
        $status = 'reorder';
        $status_text = 'REORDER';
        $status_color = 'bg-blue-100 text-blue-700 border-blue-300';
    } elseif ($percentage <= 50) {
        $status = 'low';
        $status_text = 'LOW';
        $status_color = 'bg-yellow-100 text-yellow-700 border-yellow-300';
    } else {
        $status = 'healthy';
        $status_text = 'HEALTHY';
        $status_color = 'bg-green-100 text-green-700 border-green-300';
    }
    
    $product_data = [
        'id' => $row['product_id'],
        'name' => $row['product_name'],
        'ref' => 'PRO-' . str_pad($row['product_id'], 3, '0', STR_PAD_LEFT),
        'category' => $category,
        'category_color' => $row['category_color'] ?: '#8B5A2B',
        'current_stock' => $row['current_stock'],
        'max_stock' => $row['max_stock'],
        'unit' => $row['unit'],
        'percentage' => round($percentage),
        'status' => $status,
        'status_text' => $status_text,
        'status_color' => $status_color,
        'reorder_level' => $row['reorder_level']
    ];
    
    $products_by_category[$category][] = $product_data;
    $all_products[] = $product_data;
}

// Get summary stats - menggunakan $all_products untuk sync dengan status
$stock_value_result = mysqli_query($conn, "SELECT SUM(current_stock * price_per_unit) as total FROM products");
$stock_value = mysqli_fetch_assoc($stock_value_result)['total'] ?: 0;

// Count Critical & Low from $all_products array (sama dengan status dalam table)
$critical_low_count = 0;
foreach ($all_products as $item) {
    if ($item['status'] === 'critical' || $item['status'] === 'low') {
        $critical_low_count++;
    }
}
$alert_count = $critical_low_count;

$total_items = count($all_products);

// Get last audit info - ambil yang paling akhir
$audit_query = "SELECT u.full_name, st.transaction_date as last_audit
                FROM stock_transactions st
                JOIN users u ON st.performed_by = u.user_id
                ORDER BY st.transaction_date DESC
                LIMIT 1";
$audit_result = mysqli_query($conn, $audit_query);
$audit = mysqli_fetch_assoc($audit_result);

$last_audit_time = $audit['last_audit'] ? date('h:i A', strtotime($audit['last_audit'])) : 'Never';
$last_audit_by = $audit['full_name'] ?? 'System';

mysqli_close($conn);

$current_page = 1;
$items_per_page = 5;
$start_item = (($current_page - 1) * $items_per_page) + 1;
$end_item = min($current_page * $items_per_page, count($all_products));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salmonly Café - View Stock</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@300;400;500;600;700;800&family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Instrument Sans', sans-serif; 
            background-color: #F2E8DF; 
            color: #3C2A21; 
            position: relative; 
            min-height: 100vh;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        /* Dark Mode Styles */
        body.dark-mode {
            background-color: #1a1a1a;
            color: #e0e0e0;
        }
        
        body.dark-mode .bg-[#FAF9F6] {
            background-color: #2d2d2d;
        }
        
        body.dark-mode .bg-[#F2E8DF] {
            background-color: #252525;
        }
        
        body.dark-mode .bg-[#F2E8DF]/60 {
            background-color: #252525;
        }
        
        body.dark-mode .bg-white {
            background-color: #2d2d2d;
        }
        
        body.dark-mode .border {
            border-color: #404040;
        }
        
        body.dark-mode .border-[#3C2A21]/5 {
            border-color: #404040;
        }
        
        body.dark-mode .border-[#3C2A21]/10 {
            border-color: #505050;
        }
        
        body.dark-mode .text-[#3C2A21] {
            color: #e0e0e0;
        }
        
        body.dark-mode .text-[#3C2A21]/60 {
            color: #a0a0a0;
        }
        
        body.dark-mode .text-[#3C2A21]/40 {
            color: #808080;
        }
        
        body.dark-mode .text-[#3C2A21]/30 {
            color: #707070;
        }
        
        body.dark-mode .bg-[#8B5A2B]/10 {
            background-color: rgba(139, 90, 43, 0.2);
        }
        
        body.dark-mode .bg-[#7A8C71]/10 {
            background-color: rgba(122, 140, 113, 0.2);
        }
        
        body.dark-mode .bg-[#B85C38]/10 {
            background-color: rgba(184, 92, 56, 0.2);
        }
        
        body.dark-mode .bg-[#E6B17E]/10 {
            background-color: rgba(230, 177, 126, 0.2);
        }
        
        body.dark-mode .hover\:bg-[#F2E8DF]:hover {
            background-color: #3a3a3a;
        }
        
        body.dark-mode .hover\:bg-[#F2E8DF]/30:hover {
            background-color: rgba(58, 58, 58, 0.3);
        }
        
        body.dark-mode .form-input {
            background-color: #3a3a3a;
            border-color: #505050;
            color: #e0e0e0;
        }
        
        body.dark-mode .form-input:focus {
            border-color: #8B5A2B;
        }
        
        body.dark-mode .bg-white\/80 {
            background-color: rgba(45, 45, 45, 0.8);
        }
        
        body.dark-mode .bg-white\/20 {
            background-color: rgba(45, 45, 45, 0.2);
        }
        
        body.dark-mode .bg-white\/10 {
            background-color: rgba(45, 45, 45, 0.1);
        }
        
        body.dark-mode .btn-stock-in {
            background: linear-gradient(135deg, #1B5E20 0%, #0A3B0E 100%);
        }
        
        body.dark-mode .btn-stock-out {
            background: linear-gradient(135deg, #B71C1C 0%, #8B0000 100%);
        }
        
        body.dark-mode .bg-[#8B5A2B] {
            background: linear-gradient(135deg, #6B421F 0%, #4A2E15 100%);
        }
        
        body.dark-mode .bg-[#3C2A21] {
            background-color: #2a2a2a;
        }
        
        body.dark-mode .bg-gradient-to-r {
            background: linear-gradient(135deg, #2a2a2a 0%, #1a1a1a 100%);
        }
        
        .grain-bg::before {
            content: ""; position: fixed; inset: 0; width: 100%; height: 100%;
            background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIzMDAiIGhlaWdodD0iMzAwIj48ZmlsdGVyIGlkPSJmIj48ZmVUdXJidWxlbmNlIHR5cGU9ImZyYWN0YWxOb2lzZSIgYmFzZUZyZXF1ZW5jeT0iLjc0IiBudW1PY3RhdmVzPSIzIiAvPjwvZmlsdGVyPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbHRlcj0idXJsKCNmKSIgb3BhY2l0eT0iMC4xNSIgLz48L3N2Zz4=');
            opacity: 0.1; pointer-events: none; z-index: 1;
        }
        
        body.dark-mode .grain-bg::before {
            opacity: 0.05;
        }
        
        .sidebar-gradient { background: linear-gradient(180deg, #8B5A2B 0%, #5D3A1A 100%); }
        body.dark-mode .sidebar-gradient { background: linear-gradient(180deg, #5d3a1a 0%, #3a2410 100%); }
        
        .card-shadow { box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.05); }
        body.dark-mode .card-shadow { box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.3); }
        
        .profile-section { cursor: pointer; transition: all 0.2s ease; padding: 1rem; background: rgba(255, 255, 255, 0.1); border-radius: 1rem; border: 1px solid rgba(255, 255, 255, 0.05); }
        .profile-section:hover { background: rgba(255, 255, 255, 0.15); transform: translateY(-2px); }
        .logout-btn { width: 100%; padding: 0.5rem; background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 0.5rem; color: white; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; letter-spacing: 0.1em; transition: all 0.2s ease; cursor: pointer; margin-top: 1rem; }
        .logout-btn:hover { background: rgba(255, 255, 255, 0.2); }
        .nav-link { display: flex; align-items: center; gap: 1rem; padding: 0.75rem 1rem; border-radius: 0.75rem; transition: all 0.2s ease; color: rgba(255, 255, 255, 0.7); text-decoration: none; }
        .nav-link:hover { background: rgba(255, 255, 255, 0.1); color: white; }
        .nav-link.active { background: rgba(255, 255, 255, 0.1); color: white; font-weight: bold; border: 1px solid rgba(255, 255, 255, 0.05); }
        .category-filter { padding: 0.5rem 1.25rem; border-radius: 9999px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; letter-spacing: 0.05em; transition: all 0.2s ease; cursor: pointer; background: white; border: 1px solid rgba(60, 42, 33, 0.1); color: #3C2A21; }
        body.dark-mode .category-filter { background: #3a3a3a; border-color: #505050; color: #e0e0e0; }
        .category-filter:hover { background: #8B5A2B; color: white; border-color: #8B5A2B; }
        .category-filter.active { background: #8B5A2B; color: white; border-color: #8B5A2B; }
        .stock-table { width: 100%; border-collapse: collapse; }
        .stock-table th { text-align: left; padding: 1rem 1rem; font-size: 0.7rem; font-weight: bold; text-transform: uppercase; letter-spacing: 0.1em; color: rgba(60, 42, 33, 0.4); border-bottom: 1px solid rgba(60, 42, 33, 0.1); }
        body.dark-mode .stock-table th { color: rgba(224, 224, 224, 0.5); border-bottom-color: #404040; }
        .stock-table td { padding: 1rem 1rem; border-bottom: 1px solid rgba(60, 42, 33, 0.05); }
        body.dark-mode .stock-table td { border-bottom-color: #404040; }
        .stock-table tr:hover { background: rgba(242, 232, 223, 0.5); }
        body.dark-mode .stock-table tr:hover { background: rgba(58, 58, 58, 0.5); }
        .action-btn { padding: 0.4rem; border-radius: 0.5rem; background: rgba(60, 42, 33, 0.05); color: #3C2A21; transition: all 0.2s ease; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; }
        body.dark-mode .action-btn { background: rgba(224, 224, 224, 0.1); color: #e0e0e0; }
        .action-btn:hover { background: #8B5A2B; color: white; }
        .progress-bar { width: 80px; height: 6px; background: rgba(60, 42, 33, 0.1); border-radius: 9999px; overflow: hidden; margin-top: 4px; }
        body.dark-mode .progress-bar { background: rgba(224, 224, 224, 0.2); }
        .progress-fill { height: 100%; border-radius: 9999px; }
        .progress-fill.critical { background: #B85C38; }
        .progress-fill.low { background: #E6B17E; }
        .progress-fill.healthy { background: #7A8C71; }
        .pagination-btn { width: 2rem; height: 2rem; display: flex; align-items: center; justify-content: center; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 500; transition: all 0.2s ease; cursor: pointer; background: white; border: 1px solid rgba(60, 42, 33, 0.1); }
        body.dark-mode .pagination-btn { background: #3a3a3a; border-color: #505050; color: #e0e0e0; }
        .pagination-btn:hover { background: #8B5A2B; color: white; border-color: #8B5A2B; }
        .pagination-btn.active { background: #8B5A2B; color: white; border-color: #8B5A2B; }
        .content-wrapper { position: relative; z-index: 2; }
        .form-input { width: 100%; padding: 0.75rem 1rem; border: 1px solid rgba(60, 42, 33, 0.1); border-radius: 0.75rem; background: white; font-size: 0.95rem; transition: all 0.2s ease; }
        body.dark-mode .form-input { background-color: #3a3a3a; border-color: #505050; color: #e0e0e0; }
        .btn-primary { background: #8B5A2B; color: white; padding: 0.75rem 1.5rem; border-radius: 9999px; font-weight: bold; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; transition: all 0.2s ease; border: none; cursor: pointer; }
        .btn-primary:hover { background: #B07A4A; transform: translateY(-2px); box-shadow: 0 10px 20px -5px rgba(139, 90, 43, 0.3); }
        
        .creative-btn { position: relative; overflow: hidden; transition: all 0.3s ease; }
        .creative-btn::before { content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent); transition: left 0.5s ease; }
        .creative-btn:hover::before { left: 100%; }
        .btn-stock-in { background: linear-gradient(135deg, #2E7D32 0%, #1B5E20 100%); box-shadow: 0 4px 15px rgba(46,125,50,0.3); }
        .btn-stock-in:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(46,125,50,0.4); }
        .btn-stock-out { background: linear-gradient(135deg, #C62828 0%, #B71C1C 100%); box-shadow: 0 4px 15px rgba(198,40,40,0.3); }
        .btn-stock-out:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(198,40,40,0.4); }
        
        .stock-modal { max-width: 500px; width: 90%; }
        .update-stock-modal { max-width: 90vw; width: 1200px; max-height: 90vh; overflow-y: auto; }
        .category-section { margin-bottom: 2rem; border-bottom: 1px solid rgba(60, 42, 33, 0.1); padding-bottom: 1rem; }
        .category-title { font-size: 1.25rem; font-weight: bold; color: #8B5A2B; margin-bottom: 1rem; padding-left: 0.5rem; border-left: 4px solid #8B5A2B; }
        .stock-input { width: 100px; padding: 0.5rem; border: 1px solid rgba(60, 42, 33, 0.2); border-radius: 0.5rem; text-align: center; }
        .stock-input:focus { outline: none; border-color: #8B5A2B; box-shadow: 0 0 0 2px rgba(139, 90, 43, 0.2); }
        .original-stock { font-size: 0.875rem; color: #3C2A21/60; }
        .difference-badge { font-size: 0.75rem; padding: 0.2rem 0.5rem; border-radius: 9999px; font-weight: bold; }
        .difference-positive { background: #7A8C71/20; color: #7A8C71; }
        .difference-negative { background: #B85C38/20; color: #B85C38; }
        .difference-zero { background: #E6B17E/20; color: #E6B17E; }
        .history-modal { max-width: 800px; width: 90%; }
        .history-table { width: 100%; font-size: 0.875rem; }
        .history-table th { text-align: left; padding: 0.75rem; background: #F2E8DF; font-weight: bold; color: #3C2A21/60; font-size: 0.7rem; text-transform: uppercase; }
        .history-table td { padding: 0.75rem; border-bottom: 1px solid rgba(60, 42, 33, 0.1); }
        .badge-in { background: #7A8C71/20; color: #7A8C71; padding: 0.2rem 0.5rem; border-radius: 9999px; font-size: 0.7rem; font-weight: bold; }
        .badge-out { background: #B85C38/20; color: #B85C38; padding: 0.2rem 0.5rem; border-radius: 9999px; font-size: 0.7rem; font-weight: bold; }
        .custom-select { appearance: none; background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%238B5A2B' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e"); background-repeat: no-repeat; background-position: right 1rem center; background-size: 1rem; }
        body.dark-mode .custom-select { background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23E6B17E' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e"); }
    </style>
</head>
<body class="grain-bg <?php echo $user_theme === 'dark' ? 'dark-mode' : ''; ?>">
    <div class="flex min-h-screen w-full">
        <!-- Sidebar - Staff View -->
        <aside class="hidden lg:flex flex-col w-72 sidebar-gradient text-white sticky top-0 h-screen p-8 z-40">
            <div class="flex items-center gap-3 mb-12">
                <div class="size-10 rounded-xl overflow-hidden border border-white/20" style="background: white;">
                    <img src="images/logo.png" alt="Salmonly Café Logo" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <h2 class="text-xl font-extrabold tracking-tight">Salmonly <span class="text-[#E6B17E]">Café</span></h2>
            </div>
            <nav class="flex flex-col gap-2 flex-1">
                <a href="staffDashboard.php" class="nav-link"><span class="material-symbols-outlined">dashboard</span> Dashboard</a>
                <a href="staffViewStock.php" class="nav-link active"><span class="material-symbols-outlined">inventory_2</span> View Stock</a>
                <a href="staffReports.php" class="nav-link"><span class="material-symbols-outlined">analytics</span> Reports</a>
                <a href="staffSettings.php" class="nav-link"><span class="material-symbols-outlined">settings</span> Settings</a>
            </nav>
            <div class="profile-section" onclick="window.location.href='profile.php'">
                <div class="flex items-center gap-3">
                    <div class="size-10 rounded-full border-2 border-[#E6B17E] overflow-hidden">
                        <?php 
                        $profile_pic = 'default-avatar.png';
                        if (isset($_SESSION['user_id'])) {
                            $conn_pic = getConnection();
                            $user_id_pic = $_SESSION['user_id'];
                            $pic_result = mysqli_query($conn_pic, "SELECT profile_picture FROM users WHERE user_id = $user_id_pic");
                            if ($row_pic = mysqli_fetch_assoc($pic_result)) {
                                $profile_pic = $row_pic['profile_picture'] ?? 'default-avatar.png';
                            }
                            mysqli_close($conn_pic);
                        }
                        $profile_pic_path = "uploads/profiles/" . $profile_pic;
                        if ($profile_pic != 'default-avatar.png' && file_exists($profile_pic_path) && is_file($profile_pic_path)) {
                            echo '<img src="' . $profile_pic_path . '?t=' . time() . '" alt="Profile" class="w-full h-full object-cover">';
                        } else {
                            echo '<img src="https://ui-avatars.com/api/?name=' . urlencode($staff_name) . '&background=8B5A2B&color=fff&size=100" alt="Profile" class="w-full h-full object-cover">';
                        }
                        ?>
                    </div>
                    <div class="flex flex-col">
                        <p class="text-xs font-bold leading-none"><?php echo htmlspecialchars($staff_name); ?></p>
                        <p class="text-[10px] text-white/50"><?php echo htmlspecialchars($staff_role); ?></p>
                    </div>
                </div>
            </div>
            <button class="logout-btn" onclick="window.location.href='logout.php'">Logout</button>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0 content-wrapper">
                        <!-- Header - Tanpa Search dan Notifications -->
            <header class="flex items-center justify-end px-6 md:px-10 py-5 bg-[#F2E8DF]/60 backdrop-blur-md sticky top-0 z-30">
                <div class="lg:hidden flex items-center gap-3">
                    <span class="material-symbols-outlined text-[#8B5A2B]">menu</span>
                    <h2 class="text-[#3C2A21] font-extrabold">Salmonly Café</h2>
                </div>
                
                <div class="flex flex-1 justify-end gap-5 items-center">
                    <!-- Kosong - Search dan Notification telah dibuang -->
                </div>
            </header>

            <main class="px-6 md:px-10 py-8 max-w-7xl mx-auto w-full flex flex-col gap-8">
                <!-- Page Title and Action Buttons -->
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl md:text-4xl font-black text-[#3C2A21]">View Stock</h1>
                        <p class="text-[#3C2A21]/60 mt-1">Monitor and update inventory levels</p>
                    </div>
                    <div class="flex gap-4">
                        <button onclick="openGeneralStockInModal()" class="btn-stock-in creative-btn relative px-6 py-3 rounded-2xl text-white font-bold text-sm uppercase tracking-wider flex items-center gap-2 transition-all duration-300 shadow-lg">
                            <span class="material-symbols-outlined text-2xl animate-pulse">add_circle</span>
                            <span class="hidden sm:inline">Stock In</span>
                            <span class="absolute -top-1 -right-1 w-3 h-3 bg-green-300 rounded-full animate-ping"></span>
                        </button>
                        <button onclick="openGeneralStockOutModal()" class="btn-stock-out creative-btn relative px-6 py-3 rounded-2xl text-white font-bold text-sm uppercase tracking-wider flex items-center gap-2 transition-all duration-300 shadow-lg">
                            <span class="material-symbols-outlined text-2xl">remove_circle</span>
                            <span class="hidden sm:inline">Stock Out</span>
                        </button>
                        <button onclick="openUpdateStockModal()" class="bg-[#8B5A2B] text-white px-5 py-3 rounded-full text-sm font-bold uppercase tracking-wider hover:bg-[#B07A4A] transition-colors flex items-center gap-2">
                            <span class="material-symbols-outlined text-lg">edit_calendar</span>
                            Update Stock
                        </button>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-[#FAF9F6] p-6 rounded-2xl border border-[#3C2A21]/5 card-shadow flex items-center justify-between">
                        <div><p class="text-[#3C2A21]/40 text-xs font-bold uppercase tracking-widest mb-1">Stock Value</p><p class="text-3xl font-black text-[#3C2A21]">RM <?php echo number_format($stock_value, 2); ?></p><p class="text-xs text-[#7A8C71] mt-1">Total inventory value</p></div>
                        <div class="size-12 bg-[#8B5A2B]/10 rounded-xl flex items-center justify-center text-[#8B5A2B]"><span class="material-symbols-outlined text-3xl">payments</span></div>
                    </div>
                    <div class="bg-[#FAF9F6] p-6 rounded-2xl border border-[#3C2A21]/5 card-shadow flex items-center justify-between">
                        <div><p class="text-[#3C2A21]/40 text-xs font-bold uppercase tracking-widest mb-1">Stock Alerts</p><p class="text-3xl font-black text-[#3C2A21]"><?php echo $alert_count; ?> Items</p><p class="text-xs text-[#B85C38] mt-1">Critical & Low stock</p></div>
                        <div class="size-12 bg-[#B85C38]/10 rounded-xl flex items-center justify-center text-[#B85C38]"><span class="material-symbols-outlined text-3xl">warning</span></div>
                    </div>
                    <div class="bg-[#FAF9F6] p-6 rounded-2xl border border-[#3C2A21]/5 card-shadow flex items-center justify-between">
                        <div><p class="text-[#3C2A21]/40 text-xs font-bold uppercase tracking-widest mb-1">Last Audit</p><p class="text-3xl font-black text-[#3C2A21]"><?php echo $last_audit_time; ?></p><p class="text-xs text-[#7A8C71] mt-1">By <?php echo $last_audit_by; ?></p></div>
                        <div class="size-12 bg-[#E6B17E]/10 rounded-xl flex items-center justify-center text-[#E6B17E]"><span class="material-symbols-outlined text-3xl">checklist</span></div>
                    </div>
                </div>

                <!-- Category Filters -->
                <div class="flex flex-wrap gap-2 items-center">
                    <span class="text-[#3C2A21]/40 text-xs font-bold uppercase tracking-widest mr-2">Filter:</span>
                    <?php foreach ($categories as $index => $category): ?>
                    <button class="category-filter <?php echo $index === 0 ? 'active' : ''; ?>" onclick="filterByCategory('<?php echo $category; ?>', this)"><?php echo $category; ?></button>
                    <?php endforeach; ?>
                </div>

                <!-- Stock Table -->
                <div class="bg-[#FAF9F6] rounded-2xl border border-[#3C2A21]/5 overflow-hidden card-shadow">
                    <div class="overflow-x-auto">
                        <table class="stock-table" id="stockTable">
                            <thead>
                                <tr>
                                    <th>Ingredient</th><th>Category</th><th>Stock Level</th><th>Unit</th><th>Status</th><th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_products as $item): ?>
                                <?php $progress_class = ($item['status'] === 'critical' || $item['status'] === 'reorder') ? 'critical' : (($item['status'] === 'low') ? 'low' : 'healthy'); ?>
                                <tr data-category="<?php echo $item['category']; ?>" data-name="<?php echo strtolower($item['name']); ?>">
                                    <td class="p-4"><div><p class="font-bold text-[#3C2A21]"><?php echo $item['name']; ?></p><p class="text-[10px] text-[#3C2A21]/40 font-bold uppercase tracking-wider mt-0.5">REF: <?php echo $item['ref']; ?></p></div></td>
                                    <td class="p-4"><span class="text-xs font-medium" style="color: <?php echo $item['category_color']; ?>; background: <?php echo $item['category_color']; ?>10; padding: 0.25rem 0.75rem; border-radius: 9999px;"><?php echo $item['category']; ?></span></td>
                                    <td class="p-4"><div><p class="text-sm font-bold text-[#3C2A21]"><?php echo $item['current_stock']; ?> / <?php echo $item['max_stock']; ?> <?php echo $item['unit']; ?></p><p class="text-[10px] text-[#3C2A21]/40"><?php echo $item['percentage']; ?>% remaining</p><div class="progress-bar"><div class="progress-fill <?php echo $progress_class; ?>" style="width: <?php echo $item['percentage']; ?>%"></div></div></div></td>
                                    <td class="p-4"><span class="text-sm font-medium"><?php echo $item['unit']; ?></span></td>
                                    <td class="p-4"><span class="text-xs font-bold px-3 py-1 rounded-full <?php echo $item['status_color']; ?>"><?php echo $item['status_text']; ?></span></td>
                                    <td class="p-4"><div class="flex gap-2">
                                        <button onclick="openStockInModal(<?php echo $item['id']; ?>, '<?php echo addslashes($item['name']); ?>')" class="action-btn" title="Stock In"><span class="material-symbols-outlined text-lg text-[#7A8C71]">add_circle</span></button>
                                        <button onclick="openStockOutModal(<?php echo $item['id']; ?>, '<?php echo addslashes($item['name']); ?>', <?php echo $item['current_stock']; ?>)" class="action-btn" title="Stock Out"><span class="material-symbols-outlined text-lg text-[#B85C38]">remove_circle</span></button>
                                        <button onclick="openHistoryModal(<?php echo $item['id']; ?>, '<?php echo addslashes($item['name']); ?>')" class="action-btn" title="View History"><span class="material-symbols-outlined text-lg text-[#8B5A2B]">history</span></button>
                                    </div></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="flex items-center justify-between px-6 py-4 border-t border-[#3C2A21]/5">
                        <p class="text-xs text-[#3C2A21]/40 font-bold uppercase tracking-wider">Showing <?php echo $start_item; ?> to <?php echo $end_item; ?> of <?php echo $total_items; ?> items</p>
                        <div class="flex gap-2"><button class="pagination-btn active">1</button><button class="pagination-btn">2</button><button class="pagination-btn">3</button></div>
                    </div>
                </div>
            </main>

            <footer class="mt-auto px-10 py-10 text-center"><div class="w-24 h-px bg-[#3C2A21]/10 mx-auto mb-6"></div><p class="text-[#3C2A21]/30 text-[10px] font-black uppercase tracking-[0.4em]">© <?php echo date('Y'); ?> Salmonly Café • Centralized Stock System</p></footer>
        </div>
    </div>

        <!-- Modal Stock-In (Dropdown) -->
    <dialog id="generalStockInModal" class="stock-modal" style="background: #FAF9F6; border-radius: 24px; padding: 32px; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.2); position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); margin: 0;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center"><span class="material-symbols-outlined text-green-600">add_circle</span></div>
                <h3 style="font-size: 24px; font-weight: 900; color: #3C2A21;">Add Stock</h3>
            </div>
            <button onclick="document.getElementById('generalStockInModal').close()" style="font-size: 28px; color: #3C2A21/40; background: none; border: none; cursor: pointer;">×</button>
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="stock_in_general" value="1">
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">Select Product</label>
                <select name="product_id" required class="custom-select" style="width: 100%; padding: 14px; border-radius: 16px; border: 2px solid rgba(60,42,33,0.1); background: white;">
                    <option value="">-- Choose a product --</option>
                    <?php foreach ($all_products_dropdown as $product): ?>
                    <option value="<?php echo $product['product_id']; ?>"><?php echo $product['product_name']; ?> (Current: <?php echo $product['current_stock']; ?> <?php echo $product['unit']; ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">Quantity *</label>
                <input type="number" name="quantity" step="0.01" required min="0.01" placeholder="Enter quantity" style="width: 100%; padding: 14px; border-radius: 16px; border: 2px solid rgba(60,42,33,0.1);">
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">Notes (Optional)</label>
                <input type="text" name="notes" placeholder="e.g., Supplier delivery" style="width: 100%; padding: 14px; border-radius: 16px; border: 2px solid rgba(60,42,33,0.1);">
            </div>
            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" style="flex: 1; background: linear-gradient(135deg, #2E7D32, #1B5E20); color: white; padding: 14px; border-radius: 9999px; font-size: 14px; font-weight: bold; text-transform: uppercase; border: none; cursor: pointer;">Add Stock</button>
                <button type="button" onclick="document.getElementById('generalStockInModal').close()" style="flex: 1; background: white; border: 2px solid rgba(60,42,33,0.1); padding: 14px; border-radius: 9999px; font-size: 14px; font-weight: bold; text-transform: uppercase; cursor: pointer;">Cancel</button>
            </div>
        </form>
    </dialog>

        <!-- Modal Stock-Out (Dropdown) -->
    <dialog id="generalStockOutModal" class="stock-modal" style="background: #FAF9F6; border-radius: 24px; padding: 32px; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.2); position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); margin: 0;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center"><span class="material-symbols-outlined text-red-600">remove_circle</span></div>
                <h3 style="font-size: 24px; font-weight: 900; color: #3C2A21;">Remove Stock</h3>
            </div>
            <button onclick="document.getElementById('generalStockOutModal').close()" style="font-size: 28px; color: #3C2A21/40; background: none; border: none; cursor: pointer;">×</button>
        </div>
        
        <form method="POST" action="" id="generalStockOutForm">
            <input type="hidden" name="stock_out_general" value="1">
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">Select Product</label>
                <select name="product_id" id="generalStockOutProductSelect" required class="custom-select" style="width: 100%; padding: 14px; border-radius: 16px; border: 2px solid rgba(60,42,33,0.1); background: white;">
                    <option value="">-- Choose a product --</option>
                    <?php foreach ($all_products_dropdown as $product): ?>
                    <option value="<?php echo $product['product_id']; ?>" data-stock="<?php echo $product['current_stock']; ?>" data-unit="<?php echo $product['unit']; ?>"><?php echo $product['product_name']; ?> (Current: <?php echo $product['current_stock']; ?> <?php echo $product['unit']; ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">Current Stock</label>
                <div id="generalCurrentStockDisplay" style="background: #F2E8DF; padding: 12px; border-radius: 16px; font-weight: bold;">--</div>
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">Quantity *</label>
                <input type="number" name="quantity" id="generalStockOutQuantity" step="0.01" required min="0.01" placeholder="Enter quantity" style="width: 100%; padding: 14px; border-radius: 16px; border: 2px solid rgba(60,42,33,0.1);">
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">Reason *</label>
                <select name="reason" required class="custom-select" style="width: 100%; padding: 14px; border-radius: 16px; border: 2px solid rgba(60,42,33,0.1);">
                    <option value="Usage">📦 Usage (Kitchen / Production)</option>
                    <option value="Sales">💰 Sales (Customer)</option>
                    <option value="Waste">🗑️ Waste (Expired / Damaged)</option>
                </select>
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">Additional Notes</label>
                <input type="text" name="notes" placeholder="Optional notes" style="width: 100%; padding: 14px; border-radius: 16px; border: 2px solid rgba(60,42,33,0.1);">
            </div>
            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" style="flex: 1; background: linear-gradient(135deg, #C62828, #B71C1C); color: white; padding: 14px; border-radius: 9999px; font-size: 14px; font-weight: bold; text-transform: uppercase; border: none; cursor: pointer;">Remove Stock</button>
                <button type="button" onclick="document.getElementById('generalStockOutModal').close()" style="flex: 1; background: white; border: 2px solid rgba(60,42,33,0.1); padding: 14px; border-radius: 9999px; font-size: 14px; font-weight: bold; text-transform: uppercase; cursor: pointer;">Cancel</button>
            </div>
        </form>
    </dialog>

        <!-- Modal Stock-In (Specific Product) -->
    <dialog id="stockInModal" class="stock-modal" style="background: #FAF9F6; border-radius: 24px; padding: 32px; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.2); position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); margin: 0;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h3 style="font-size: 24px; font-weight: 900; color: #3C2A21;">Record Stock-In</h3>
            <button onclick="document.getElementById('stockInModal').close()" style="font-size: 28px; color: #3C2A21/40; background: none; border: none; cursor: pointer;">×</button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="stock_in" value="1">
            <input type="hidden" name="product_id" id="stockInProductId">
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">Product</label>
                <input type="text" id="stockInProductName" readonly style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid rgba(60,42,33,0.1); background: #F2E8DF;">
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">Quantity *</label>
                <input type="number" name="quantity" step="0.01" required min="0.01" style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid rgba(60,42,33,0.1);">
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">Notes</label>
                <input type="text" name="notes" placeholder="e.g., Supplier delivery" style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid rgba(60,42,33,0.1);">
            </div>
            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" style="flex: 1; background: #8B5A2B; color: white; padding: 12px; border-radius: 9999px; font-size: 14px; font-weight: bold; text-transform: uppercase; border: none; cursor: pointer;">Add Stock</button>
                <button type="button" onclick="document.getElementById('stockInModal').close()" style="flex: 1; background: white; border: 1px solid rgba(60,42,33,0.1); padding: 12px; border-radius: 9999px; font-size: 14px; font-weight: bold; text-transform: uppercase; cursor: pointer;">Cancel</button>
            </div>
        </form>
    </dialog>

        <!-- Modal Stock-Out (Specific Product) -->
    <dialog id="stockOutModal" class="stock-modal" style="background: #FAF9F6; border-radius: 24px; padding: 32px; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.2); position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); margin: 0;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h3 style="font-size: 24px; font-weight: 900; color: #3C2A21;">Record Stock-Out</h3>
            <button onclick="document.getElementById('stockOutModal').close()" style="font-size: 28px; color: #3C2A21/40; background: none; border: none; cursor: pointer;">×</button>
        </div>
        <form method="POST" action="" id="stockOutForm">
            <input type="hidden" name="stock_out" value="1">
            <input type="hidden" name="product_id" id="stockOutProductId">
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">Product</label>
                <input type="text" id="stockOutProductName" readonly style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid rgba(60,42,33,0.1); background: #F2E8DF;">
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">Current Stock</label>
                <input type="text" id="stockOutCurrentStock" readonly style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid rgba(60,42,33,0.1); background: #F2E8DF;">
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">Quantity *</label>
                <input type="number" name="quantity" id="stockOutQuantity" step="0.01" required min="0.01" style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid rgba(60,42,33,0.1);">
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">Reason *</label>
                <select name="reason" required style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid rgba(60,42,33,0.1);">
                    <option value="Usage">Usage (Kitchen / Production)</option>
                    <option value="Sales">Sales (Customer)</option>
                    <option value="Waste">Waste (Expired / Damaged)</option>
                </select>
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">Additional Notes</label>
                <input type="text" name="notes" placeholder="Optional notes" style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid rgba(60,42,33,0.1);">
            </div>
            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" style="flex: 1; background: #8B5A2B; color: white; padding: 12px; border-radius: 9999px; font-size: 14px; font-weight: bold; text-transform: uppercase; border: none; cursor: pointer;">Remove Stock</button>
                <button type="button" onclick="document.getElementById('stockOutModal').close()" style="flex: 1; background: white; border: 1px solid rgba(60,42,33,0.1); padding: 12px; border-radius: 9999px; font-size: 14px; font-weight: bold; text-transform: uppercase; cursor: pointer;">Cancel</button>
            </div>
        </form>
    </dialog>

        <!-- Modal Update Stock (Daily Stock Take) -->
    <dialog id="updateStockModal" class="update-stock-modal" style="background: #FAF9F6; border-radius: 24px; padding: 32px; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.2); position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); margin: 0;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h3 style="font-size: 24px; font-weight: 900; color: #3C2A21;">Update Daily Stock</h3>
            <button onclick="document.getElementById('updateStockModal').close()" style="font-size: 28px; color: #3C2A21/40; background: none; border: none; cursor: pointer;">×</button>
        </div>
        
        <p class="text-[#3C2A21]/60 mb-4 text-sm">Enter the actual stock quantity for each item. Changes will be recorded in transaction history.</p>
        
        <form method="POST" action="" id="updateStockForm">
            <input type="hidden" name="update_daily_stock" value="1">
            
            <div class="max-h-[60vh] overflow-y-auto pr-2">
                <?php foreach ($products_by_category as $category => $products): ?>
                <div class="category-section">
                    <div class="category-title"><?php echo $category; ?></div>
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-[#3C2A21]/10">
                                <th class="text-left py-2 text-xs font-bold text-[#3C2A21]/40">Product</th>
                                <th class="text-left py-2 text-xs font-bold text-[#3C2A21]/40">Current Stock</th>
                                <th class="text-left py-2 text-xs font-bold text-[#3C2A21]/40">New Stock</th>
                                <th class="text-left py-2 text-xs font-bold text-[#3C2A21]/40">Unit</th>
                                <th class="text-left py-2 text-xs font-bold text-[#3C2A21]/40">Difference</th>
                             </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                            <tr class="border-b border-[#3C2A21]/5 hover:bg-[#F2E8DF]/30 transition-colors" data-original-stock="<?php echo $product['current_stock']; ?>">
                                <td class="py-2">
                                    <span class="font-medium text-[#3C2A21]"><?php echo $product['name']; ?></span>
                                 </td>
                                <td class="py-2">
                                    <span class="original-stock"><?php echo $product['current_stock']; ?></span>
                                 </td>
                                <td class="py-2">
                                    <input type="number" name="stock[<?php echo $product['id']; ?>]" 
                                           value="<?php echo $product['current_stock']; ?>" 
                                           step="0.01" 
                                           class="stock-input new-stock-input"
                                           data-product-id="<?php echo $product['id']; ?>">
                                 </td>
                                <td class="py-2 text-sm text-[#3C2A21]/60"><?php echo $product['unit']; ?></td>
                                <td class="py-2">
                                    <span class="difference-badge difference-zero" id="diff-<?php echo $product['id']; ?>">0</span>
                                 </td>
                             </tr>
                            <?php endforeach; ?>
                        </tbody>
                     </table>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-[#3C2A21]/10">
                <button type="button" onclick="document.getElementById('updateStockModal').close()" class="px-6 py-2 bg-white border border-[#3C2A21]/10 rounded-full text-sm font-bold uppercase tracking-wider hover:bg-[#F2E8DF] transition-colors">
                    Cancel
                </button>
                <button type="submit" class="px-6 py-2 bg-[#8B5A2B] text-white rounded-full text-sm font-bold uppercase tracking-wider hover:bg-[#B07A4A] transition-colors">
                    Save All Changes
                </button>
            </div>
        </form>
    </dialog>

    <!-- Modal History -->
    <dialog id="historyModal" class="history-modal" style="background: #FAF9F6; border-radius: 24px; padding: 32px; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.2); position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); margin: 0;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;"><h3 style="font-size: 24px; font-weight: 900; color: #3C2A21;">Transaction History</h3><button onclick="document.getElementById('historyModal').close()" style="font-size: 28px; color: #3C2A21/40; background: none; border: none; cursor: pointer;">×</button></div>
        <div id="historyContent" style="max-height: 60vh; overflow-y: auto;"><div class="text-center py-8 text-[#3C2A21]/40">Loading...</div></div>
        <div class="flex justify-end mt-4"><button onclick="document.getElementById('historyModal').close()" class="px-6 py-2 bg-[#8B5A2B] text-white rounded-full text-sm font-bold uppercase tracking-wider hover:bg-[#B07A4A] transition-colors">Close</button></div>
    </dialog>

    <script>
        function showNotification() { alert('🔔 Notifications\n\n• Salmon restocked (08:30 AM)\n• Counter Audit (07:15 AM)\n• Waste Recorded (06:45 AM)\n• Supplier Arrival (06:00 AM)'); }
        
        function searchStock() {
            let input = document.getElementById('searchInput');
            let filter = input.value.toLowerCase();
            let table = document.getElementById('stockTable');
            let rows = table.getElementsByTagName('tr');
            for (let i = 1; i < rows.length; i++) {
                let nameCell = rows[i].getElementsByTagName('td')[0];
                if (nameCell) {
                    let name = nameCell.textContent || nameCell.innerText;
                    rows[i].style.display = name.toLowerCase().indexOf(filter) > -1 ? '' : 'none';
                }
            }
        }
        
        function filterByCategory(category, element) {
            let buttons = document.querySelectorAll('.category-filter');
            buttons.forEach(btn => { btn.classList.remove('active'); btn.style.background = 'white'; btn.style.color = '#3C2A21'; });
            element.classList.add('active'); element.style.background = '#8B5A2B'; element.style.color = 'white';
            let table = document.getElementById('stockTable');
            let rows = table.getElementsByTagName('tr');
            for (let i = 1; i < rows.length; i++) {
                let categoryCell = rows[i].getAttribute('data-category');
                rows[i].style.display = (category === 'All Items' || categoryCell === category) ? '' : 'none';
            }
        }
        
        function openGeneralStockInModal() { document.getElementById('generalStockInModal').showModal(); }
        
        function openGeneralStockOutModal() {
            document.getElementById('generalStockOutModal').showModal();
            document.getElementById('generalStockOutProductSelect').addEventListener('change', function() {
                let selected = this.options[this.selectedIndex];
                let stock = selected.getAttribute('data-stock');
                let unit = selected.getAttribute('data-unit');
                document.getElementById('generalCurrentStockDisplay').innerHTML = stock + ' ' + unit;
                document.getElementById('generalStockOutQuantity').max = stock;
            });
        }
        
        function openStockInModal(productId, productName) {
            document.getElementById('stockInProductId').value = productId;
            document.getElementById('stockInProductName').value = productName;
            document.getElementById('stockInModal').showModal();
        }
        
        function openStockOutModal(productId, productName, currentStock) {
            document.getElementById('stockOutProductId').value = productId;
            document.getElementById('stockOutProductName').value = productName;
            document.getElementById('stockOutCurrentStock').value = currentStock;
            document.getElementById('stockOutQuantity').max = currentStock;
            document.getElementById('stockOutModal').showModal();
        }
        
        document.getElementById('stockOutForm')?.addEventListener('submit', function(e) {
            let quantity = parseFloat(document.getElementById('stockOutQuantity').value);
            let currentStock = parseFloat(document.getElementById('stockOutCurrentStock').value);
            if (quantity > currentStock) { e.preventDefault(); alert('Cannot remove more than current stock! Current stock: ' + currentStock); }
        });
        
        document.getElementById('generalStockOutForm')?.addEventListener('submit', function(e) {
            let quantity = parseFloat(document.getElementById('generalStockOutQuantity').value);
            let currentStockText = document.getElementById('generalCurrentStockDisplay').innerHTML;
            let currentStock = parseFloat(currentStockText.split(' ')[0]);
            if (quantity > currentStock) { e.preventDefault(); alert('Cannot remove more than current stock! Current stock: ' + currentStock); }
        });
        
        function openUpdateStockModal() {
            document.querySelectorAll('.new-stock-input').forEach(input => {
                let row = input.closest('tr');
                let originalStock = parseFloat(row.getAttribute('data-original-stock'));
                input.value = originalStock;
                updateDifference(input, originalStock);
            });
            document.getElementById('updateStockModal').showModal();
        }
        
        function updateDifference(input, originalStock) {
            let newStock = parseFloat(input.value) || 0;
            let difference = newStock - originalStock;
            let diffSpan = document.getElementById('diff-' + input.getAttribute('data-product-id'));
            if (difference > 0) { diffSpan.innerHTML = '+' + difference.toFixed(2); diffSpan.className = 'difference-badge difference-positive'; }
            else if (difference < 0) { diffSpan.innerHTML = difference.toFixed(2); diffSpan.className = 'difference-badge difference-negative'; }
            else { diffSpan.innerHTML = '0'; diffSpan.className = 'difference-badge difference-zero'; }
        }
        
        function openHistoryModal(productId, productName) {
            document.getElementById('historyModal').showModal();
            document.getElementById('historyContent').innerHTML = '<div class="text-center py-8"><span class="material-symbols-outlined text-4xl">history</span><p class="mt-2">Loading...</p></div>';
            fetch('staffViewStock.php?get_history=' + productId)
                .then(response => response.json())
                .then(data => {
                    if (data.length === 0) { document.getElementById('historyContent').innerHTML = '<div class="text-center py-8"><span class="material-symbols-outlined text-4xl">inbox</span><p>No transaction history found.</p></div>'; return; }
                    let html = `<div style="margin-bottom:16px;"><h4 style="font-size:18px; font-weight:bold; color:#8B5A2B;">${productName}</h4></div><table class="history-table"><thead><th>Date</th><th>Type</th><th>Quantity</th><th>Notes</th><th>By</th> </thead><tbody>`;
                    data.forEach(record => {
                        let badgeClass = record.transaction_type === 'IN' ? 'badge-in' : 'badge-out';
                        let typeText = record.transaction_type === 'IN' ? 'Stock In' : 'Stock Out';
                        let quantity = record.transaction_type === 'IN' ? '+' + record.quantity : '-' + record.quantity;
                        html += `<tr><td>${new Date(record.transaction_date).toLocaleString()}</td><td><span class="${badgeClass}">${typeText}</span></td><td>${quantity}</td><td>${record.notes || '-'}</td><td>${record.performed_by_name || 'System'}</td></tr>`;
                    });
                    html += `</tbody></table>`;
                    document.getElementById('historyContent').innerHTML = html;
                })
                .catch(() => { document.getElementById('historyContent').innerHTML = '<div class="text-center py-8 text-red-500">Failed to load history.</div>'; });
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.new-stock-input').forEach(input => {
                let row = input.closest('tr');
                let originalStock = parseFloat(row.getAttribute('data-original-stock'));
                input.addEventListener('change', function() { updateDifference(this, originalStock); });
            });
        });
    </script>
</body>
</html>

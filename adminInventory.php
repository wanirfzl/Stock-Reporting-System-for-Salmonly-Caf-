<?php
// adminInventory.php
define('ACCESS_ALLOWED', true);
require_once 'config/database.php';
requireLogin();

if ($_SESSION['user_role'] !== 'Administrator') {
    header('Location: staffDashboard.php');
    exit();
}

$page_title = 'Inventory Management';
$success_message = '';
$error_message = '';

// Connect to database
$conn = getConnection();

// Handle Add New Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $category_name = mysqli_real_escape_string($conn, $_POST['category_name'] ?? '');
    $description = mysqli_real_escape_string($conn, $_POST['description'] ?? '');
    $category_color = mysqli_real_escape_string($conn, $_POST['category_color'] ?? '#8B5A2B');
    
    if (empty($category_name)) {
        $error_message = 'Category name is required';
    } else {
        $check_query = "SELECT category_id FROM categories WHERE category_name = '$category_name'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error_message = 'Category "' . $category_name . '" already exists!';
        } else {
            $query = "INSERT INTO categories (category_name, description, category_color) 
                      VALUES ('$category_name', '$description', '$category_color')";
            
            if (mysqli_query($conn, $query)) {
                $success_message = 'Category added successfully!';
            } else {
                $error_message = 'Failed to add category: ' . mysqli_error($conn);
            }
        }
    }
}

// Handle Add New Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $product_name = mysqli_real_escape_string($conn, $_POST['product_name'] ?? '');
    $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : 'NULL';
    $unit = mysqli_real_escape_string($conn, $_POST['unit'] ?? '');
    $current_stock = floatval($_POST['current_stock'] ?? 0);
    $max_stock = floatval($_POST['max_stock'] ?? 0);
    $reorder_level = floatval($_POST['reorder_level'] ?? 10);
    $price_per_unit = floatval($_POST['price_per_unit'] ?? 0);
    $supplier = mysqli_real_escape_string($conn, $_POST['supplier'] ?? '');
    $location = mysqli_real_escape_string($conn, $_POST['location'] ?? '');
    $created_by = $_SESSION['user_id'];
    
    if (empty($product_name) || empty($unit)) {
        $error_message = 'Product Name and Unit are required';
    } else {
        $query = "INSERT INTO products (product_name, category_id, unit, current_stock, max_stock, reorder_level, 
                  price_per_unit, supplier, location, created_by) 
                  VALUES ('$product_name', $category_id, '$unit', $current_stock, $max_stock, $reorder_level, 
                  $price_per_unit, '$supplier', '$location', $created_by)";
        
        if (mysqli_query($conn, $query)) {
            $success_message = 'Product added successfully!';
            header("Location: adminInventory.php");
            exit();
        } else {
            $error_message = 'Failed to add product: ' . mysqli_error($conn);
        }
    }
}

// Handle Edit Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product'])) {
    $product_id = intval($_POST['product_id']);
    $product_name = mysqli_real_escape_string($conn, $_POST['product_name'] ?? '');
    $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : 'NULL';
    $unit = mysqli_real_escape_string($conn, $_POST['unit'] ?? '');
    $current_stock = floatval($_POST['current_stock'] ?? 0);
    $max_stock = floatval($_POST['max_stock'] ?? 0);
    $reorder_level = floatval($_POST['reorder_level'] ?? 10);
    $price_per_unit = floatval($_POST['price_per_unit'] ?? 0);
    $supplier = mysqli_real_escape_string($conn, $_POST['supplier'] ?? '');
    $location = mysqli_real_escape_string($conn, $_POST['location'] ?? '');
    
    if (empty($product_name) || empty($unit)) {
        $error_message = 'Product Name and Unit are required';
    } else {
        $query = "UPDATE products SET 
                  product_name = '$product_name',
                  category_id = $category_id,
                  unit = '$unit',
                  current_stock = $current_stock,
                  max_stock = $max_stock,
                  reorder_level = $reorder_level,
                  price_per_unit = $price_per_unit,
                  supplier = '$supplier',
                  location = '$location',
                  updated_at = NOW()
                  WHERE product_id = $product_id";
        
        if (mysqli_query($conn, $query)) {
            $success_message = 'Product updated successfully!';
            header("Location: adminInventory.php");
            exit();
        } else {
            $error_message = 'Failed to update product: ' . mysqli_error($conn);
        }
    }
}

// Handle Delete Product
if (isset($_GET['delete_product'])) {
    $product_id = intval($_GET['delete_product']);
    $query = "DELETE FROM products WHERE product_id = $product_id";
    if (mysqli_query($conn, $query)) {
        $success_message = 'Product deleted successfully!';
        header("Location: adminInventory.php");
        exit();
    } else {
        $error_message = 'Failed to delete product: ' . mysqli_error($conn);
    }
}

// Handle Stock-In from dropdown modal
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
            header("Location: adminInventory.php");
            exit();
        } else {
            $error_message = 'Failed to add stock: ' . mysqli_error($conn);
        }
    }
}

// Handle Stock-Out from dropdown modal
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
                header("Location: adminInventory.php");
                exit();
            } else {
                $error_message = 'Failed to remove stock: ' . mysqli_error($conn);
            }
        }
    }
}

// Handle Stock-In from table button
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
            header("Location: adminInventory.php");
            exit();
        } else {
            $error_message = 'Failed to add stock: ' . mysqli_error($conn);
        }
    }
}

// Handle Stock-Out from table button
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
                header("Location: adminInventory.php");
                exit();
            } else {
                $error_message = 'Failed to remove stock: ' . mysqli_error($conn);
            }
        }
    }
}

// Handle AJAX request to get product data for edit
if (isset($_GET['get_product'])) {
    $product_id = intval($_GET['get_product']);
    $result = mysqli_query($conn, "SELECT * FROM products WHERE product_id = $product_id");
    $product = mysqli_fetch_assoc($result);
    header('Content-Type: application/json');
    echo json_encode($product);
    exit();
}

// Handle AJAX request to get categories
if (isset($_GET['get_categories'])) {
    $cat_result = mysqli_query($conn, "SELECT category_id as id, category_name as name FROM categories ORDER BY category_name");
    $categories = [];
    while ($cat = mysqli_fetch_assoc($cat_result)) {
        $categories[] = $cat;
    }
    header('Content-Type: application/json');
    echo json_encode($categories);
    exit();
}

// Handle AJAX request to get products for dropdown
if (isset($_GET['get_products'])) {
    $product_result = mysqli_query($conn, "SELECT product_id, product_name, current_stock, unit FROM products ORDER BY product_name");
    $products = [];
    while ($row = mysqli_fetch_assoc($product_result)) {
        $products[] = $row;
    }
    header('Content-Type: application/json');
    echo json_encode($products);
    exit();
}

// Get categories for dropdown
$categories_list = ['All Items'];
$categories_result = mysqli_query($conn, "SELECT category_name FROM categories");
while ($cat = mysqli_fetch_assoc($categories_result)) {
    $categories_list[] = $cat['category_name'];
}

// Get all products for dropdown
$all_products_dropdown = [];
$product_result = mysqli_query($conn, "SELECT product_id, product_name, current_stock, unit FROM products ORDER BY product_name");
while ($row = mysqli_fetch_assoc($product_result)) {
    $all_products_dropdown[] = $row;
}

// Get inventory items from database
$inventory_items = [];
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$category_filter = isset($_GET['category']) && $_GET['category'] != 'All Items' ? $_GET['category'] : '';

$query = "SELECT p.*, c.category_name, c.category_color 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.category_id 
          WHERE 1=1";
if ($search) {
    $query .= " AND p.product_name LIKE '%$search%'";
}
if ($category_filter) {
    $query .= " AND c.category_name = '$category_filter'";
}
$query .= " ORDER BY p.product_id";

$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    // FIX: Division by zero check
    $percentage = 0;
    if ($row['max_stock'] > 0) {
        $percentage = ($row['current_stock'] / $row['max_stock']) * 100;
        $percentage = min(100, max(0, $percentage));
    }
    
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
    
    $inventory_items[] = [
        'id' => $row['product_id'],
        'name' => $row['product_name'],
        'category' => $row['category_name'] ?? 'Uncategorized',
        'category_color' => $row['category_color'] ?? '#8B5A2B',
        'current_stock' => $row['current_stock'],
        'max_stock' => $row['max_stock'],
        'unit' => $row['unit'],
        'status' => $status,
        'status_text' => $status_text,
        'status_color' => $status_color,
        'reorder_level' => $row['reorder_level'],
        'supplier' => $row['supplier'] ?? '-',
        'location' => $row['location'] ?? '-',
        'price_per_unit' => $row['price_per_unit'] ?? 0,
        'last_updated' => date('d/m/Y h:i A', strtotime($row['updated_at']))
    ];
}

// Calculate total stock value
$value_result = mysqli_query($conn, "SELECT SUM(current_stock * price_per_unit) as total FROM products");
$stock_value = mysqli_fetch_assoc($value_result)['total'] ?? 0;

$total_items = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM products"));

// Get categories for dropdown in modal
$modal_categories = [];
$cat_modal_result = mysqli_query($conn, "SELECT category_id, category_name FROM categories ORDER BY category_name");
while ($cat = mysqli_fetch_assoc($cat_modal_result)) {
    $modal_categories[] = $cat;
}

mysqli_close($conn);

include 'includes/header-admin.php';
?>

<style>
    /* Modal Styles */
    .stock-modal {
        max-width: 500px;
        width: 90%;
    }
    .custom-select {
        appearance: none;
        background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%238B5A2B' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 1rem center;
        background-size: 1rem;
    }
    .btn-stock-in { background: linear-gradient(135deg, #2E7D32 0%, #1B5E20 100%); }
    .btn-stock-out { background: linear-gradient(135deg, #C62828 0%, #B71C1C 100%); }
</style>

<main class="px-6 md:px-10 py-8 max-w-7xl mx-auto w-full">
    <!-- Page Title -->
    <div class="mb-6">
        <h1 class="text-3xl md:text-4xl font-black text-[#3C2A21]">Inventory Management</h1>
        <p class="text-[#3C2A21]/60 mt-1">Manage all items, stock levels, and transactions</p>
    </div>

    <!-- Success Message -->
    <?php if ($success_message): ?>
    <div class="bg-[#7A8C71] text-white px-6 py-4 rounded-2xl mb-6 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="material-symbols-outlined">check_circle</span>
            <span class="font-medium"><?php echo $success_message; ?></span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-white/80 hover:text-white">×</button>
    </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
    <div class="bg-[#B85C38] text-white px-6 py-4 rounded-2xl mb-6 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="material-symbols-outlined">error</span>
            <span class="font-medium"><?php echo $error_message; ?></span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-white/80 hover:text-white">×</button>
    </div>
    <?php endif; ?>

    <!-- Search and Filter Section -->
    <div class="bg-[#FAF9F6] rounded-2xl border border-[#3C2A21]/5 card-shadow p-6 mb-6">
        <form method="GET" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-[#3C2A21]/40 uppercase tracking-widest mb-2">SEARCH ITEMS</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search by item name..." 
                           class="w-full px-4 py-3 rounded-xl border border-[#3C2A21]/10 focus:border-[#8B5A2B] focus:ring-2 focus:ring-[#8B5A2B]/20 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-[#3C2A21]/40 uppercase tracking-widest mb-2">CATEGORY</label>
                    <select name="category" class="w-full px-4 py-3 rounded-xl border border-[#3C2A21]/10 focus:border-[#8B5A2B] focus:ring-2 focus:ring-[#8B5A2B]/20 outline-none">
                        <?php foreach ($categories_list as $cat): ?>
                        <option value="<?php echo $cat; ?>" <?php echo (isset($_GET['category']) && $_GET['category'] == $cat) ? 'selected' : ''; ?>><?php echo $cat; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="flex flex-wrap gap-3">
                <button type="submit" class="bg-[#8B5A2B] text-white px-6 py-3 rounded-full text-sm font-bold uppercase tracking-wider hover:bg-[#B07A4A] transition-colors">
                    APPLY FILTERS
                </button>
                <a href="adminInventory.php" class="bg-white border border-[#3C2A21]/10 px-6 py-3 rounded-full text-sm font-bold uppercase tracking-wider hover:bg-[#F2E8DF] transition-colors">
                    RESET
                </a>
            </div>
        </form>
        
        <div class="flex justify-end mt-4">
            <button onclick="document.getElementById('addDialog').showModal()" class="bg-[#8B5A2B] text-white px-6 py-3 rounded-full text-sm font-bold uppercase tracking-wider hover:bg-[#B07A4A] transition-colors flex items-center gap-2">
                <span class="material-symbols-outlined">add</span>
                ADD NEW ITEM
            </button>
        </div>
    </div>

    <!-- Stock Actions - Record Stock In & Stock Out Buttons -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <button onclick="openGeneralStockInModal()" class="btn-stock-in text-white p-6 rounded-2xl flex items-center justify-between cursor-pointer hover:opacity-90 transition-all shadow-lg">
            <div>
                <h3 class="text-xl font-bold">Record Stock-In</h3>
                <p class="text-white/80 text-sm">Add new stock to inventory</p>
            </div>
            <span class="material-symbols-outlined text-3xl">arrow_forward</span>
        </button>
        <button onclick="openGeneralStockOutModal()" class="btn-stock-out text-white p-6 rounded-2xl flex items-center justify-between cursor-pointer hover:opacity-90 transition-all shadow-lg">
            <div>
                <h3 class="text-xl font-bold">Record Stock-Out</h3>
                <p class="text-white/80 text-sm">Record usage, sales, or waste</p>
            </div>
            <span class="material-symbols-outlined text-3xl">arrow_forward</span>
        </button>
    </div>

    <!-- Inventory Table -->
    <div class="bg-[#FAF9F6] rounded-3xl border border-[#3C2A21]/5 card-shadow overflow-hidden">
        <div class="bg-[#3C2A21] px-6 py-4 flex items-center justify-between">
            <h2 class="text-white text-sm font-bold uppercase tracking-widest flex items-center gap-2">
                <span class="material-symbols-outlined text-[#E6B17E]">inventory</span>
                INVENTORY ITEMS (<?php echo count($inventory_items); ?>)
            </h2>
            <span class="text-white/60 text-xs">Stock Value: RM <?php echo number_format($stock_value, 2); ?></span>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-[#3C2A21]/5">
                        <th class="text-left p-4 text-xs font-bold text-[#3C2A21]/40 uppercase">ITEM</th>
                        <th class="text-left p-4 text-xs font-bold text-[#3C2A21]/40 uppercase">CATEGORY</th>
                        <th class="text-left p-4 text-xs font-bold text-[#3C2A21]/40 uppercase">STOCK LEVEL</th>
                        <th class="text-left p-4 text-xs font-bold text-[#3C2A21]/40 uppercase">STATUS</th>
                        <th class="text-left p-4 text-xs font-bold text-[#3C2A21]/40 uppercase">SUPPLIER</th>
                        <th class="text-left p-4 text-xs font-bold text-[#3C2A21]/40 uppercase">LOCATION</th>
                        <th class="text-left p-4 text-xs font-bold text-[#3C2A21]/40 uppercase">LAST UPDATED</th>
                        <th class="text-left p-4 text-xs font-bold text-[#3C2A21]/40 uppercase">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($inventory_items)): ?>
                    <tr><td colspan="8" class="text-center py-8 text-[#3C2A21]/40">No items found</td></tr>
                    <?php else: ?>
                    <?php foreach ($inventory_items as $item): ?>
                    <tr class="border-b border-[#3C2A21]/5 hover:bg-[#F2E8DF]/30">
                        <td class="p-4">
                            <div>
                                <p class="font-bold text-[#3C2A21]"><?php echo $item['name']; ?></p>
                                <p class="text-[10px] text-[#3C2A21]/40 font-bold uppercase mt-0.5">ID: INV-<?php echo str_pad($item['id'], 4, '0', STR_PAD_LEFT); ?></p>
                            </div>
                         </td>
                        <td class="p-4">
                            <span class="text-xs px-3 py-1 rounded-full" style="background: <?php echo $item['category_color']; ?>10; color: <?php echo $item['category_color']; ?>">
                                <?php echo $item['category']; ?>
                            </span>
                         </td>
                        <td class="p-4">
                            <div>
                                <p class="font-bold"><?php echo $item['current_stock']; ?> / <?php echo $item['max_stock']; ?> <?php echo $item['unit']; ?></p>
                                <p class="text-[10px] text-[#3C2A21]/40">Reorder: <?php echo $item['reorder_level']; ?> <?php echo $item['unit']; ?></p>
                            </div>
                         </td>
                        <td class="p-4">
                            <span class="text-xs font-bold px-3 py-1 rounded-full <?php echo $item['status_color']; ?>">
                                <?php echo $item['status_text']; ?>
                            </span>
                         </td>
                        <td class="p-4 text-sm"><?php echo $item['supplier']; ?></td>
                        <td class="p-4 text-sm"><?php echo $item['location']; ?></td>
                        <td class="p-4 text-sm text-[#3C2A21]/60"><?php echo $item['last_updated']; ?></td>
                        <td class="p-4">
                            <div class="flex gap-2">
                                <button onclick="openStockInModal(<?php echo $item['id']; ?>, '<?php echo addslashes($item['name']); ?>')" class="p-2 hover:bg-[#F2E8DF] rounded-lg" title="Stock In">
                                    <span class="material-symbols-outlined text-lg text-[#7A8C71]">add_circle</span>
                                </button>
                                <button onclick="openStockOutModal(<?php echo $item['id']; ?>, '<?php echo addslashes($item['name']); ?>', <?php echo $item['current_stock']; ?>)" class="p-2 hover:bg-[#F2E8DF] rounded-lg" title="Stock Out">
                                    <span class="material-symbols-outlined text-lg text-[#B85C38]">remove_circle</span>
                                </button>
                                <button onclick="openEditModal(<?php echo $item['id']; ?>)" class="p-2 hover:bg-[#F2E8DF] rounded-lg" title="Edit">
                                    <span class="material-symbols-outlined text-lg text-[#8B5A2B]">edit</span>
                                </button>
                                <button onclick="if(confirm('Delete <?php echo $item['name']; ?>?')) window.location.href='adminInventory.php?delete_product=<?php echo $item['id']; ?>'" class="p-2 hover:bg-[#F2E8DF] rounded-lg" title="Delete">
                                    <span class="material-symbols-outlined text-lg text-[#B85C38]">delete</span>
                                </button>
                            </div>
                         </td>
                     </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
             </table>
        </div>
        
        <div class="flex items-center justify-between px-6 py-4 border-t border-[#3C2A21]/5">
            <p class="text-xs text-[#3C2A21]/40 font-bold uppercase tracking-wider">
                Showing <?php echo count($inventory_items); ?> of <?php echo $total_items; ?> items
            </p>
            <div class="flex gap-2">
                <button class="w-8 h-8 flex items-center justify-center rounded-lg bg-[#8B5A2B] text-white">1</button>
                <button class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-[#3C2A21]/10 hover:bg-[#F2E8DF]">2</button>
                <button class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-[#3C2A21]/10 hover:bg-[#F2E8DF]">3</button>
            </div>
        </div>
    </div>
</main>

<!-- Dialog Add New Category -->
<dialog id="addCategoryDialog" style="background: #FAF9F6; border-radius: 24px; padding: 32px; max-width: 400px; width: 90%; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.2); position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); margin: 0;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h3 style="font-size: 24px; font-weight: 900; color: #3C2A21;">Add New Category</h3>
        <button onclick="document.getElementById('addCategoryDialog').close()" style="font-size: 28px; color: #3C2A21/40; background: none; border: none; cursor: pointer;">×</button>
    </div>
    <form method="POST" action="" id="addCategoryForm">
        <input type="hidden" name="add_category" value="1">
        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">Category Name *</label>
            <input type="text" name="category_name" required style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid rgba(60,42,33,0.1);">
        </div>
        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">Description</label>
            <textarea name="description" rows="2" style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid rgba(60,42,33,0.1);"></textarea>
        </div>
        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">Color</label>
            <input type="color" name="category_color" value="#8B5A2B" style="width: 100%; padding: 8px; border-radius: 12px; border: 1px solid rgba(60,42,33,0.1);">
        </div>
        <div style="display: flex; gap: 12px; margin-top: 24px;">
            <button type="submit" style="flex: 1; background: #8B5A2B; color: white; padding: 12px; border-radius: 9999px; font-size: 14px; font-weight: bold; text-transform: uppercase; border: none; cursor: pointer;">Add Category</button>
            <button type="button" onclick="document.getElementById('addCategoryDialog').close()" style="flex: 1; background: white; border: 1px solid rgba(60,42,33,0.1); padding: 12px; border-radius: 9999px; font-size: 14px; font-weight: bold; text-transform: uppercase; cursor: pointer;">Cancel</button>
        </div>
    </form>
</dialog>

<!-- Dialog Add New Item -->
<dialog id="addDialog" style="background: #FAF9F6; border-radius: 24px; padding: 32px; max-width: 550px; width: 90%; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.2); position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); margin: 0;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h3 style="font-size: 24px; font-weight: 900; color: #3C2A21;">Add New Item</h3>
        <button onclick="document.getElementById('addDialog').close()" style="font-size: 28px; color: #3C2A21/40; background: none; border: none; cursor: pointer;">×</button>
    </div>
    <form method="POST" action="">
        <input type="hidden" name="add_product" value="1">
        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">Product Name *</label>
            <input type="text" name="product_name" required style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid rgba(60,42,33,0.1);">
        </div>
        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">Category</label>
            <div style="display: flex; gap: 10px;">
                <select name="category_id" id="categorySelect" style="flex: 1; padding: 12px; border-radius: 12px; border: 1px solid rgba(60,42,33,0.1);">
                    <option value="">Select Category</option>
                    <?php foreach ($modal_categories as $cat): ?>
                    <option value="<?php echo $cat['category_id']; ?>"><?php echo $cat['category_name']; ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" onclick="document.getElementById('addCategoryDialog').showModal()" style="background: #8B5A2B; color: white; padding: 12px 20px; border-radius: 12px; border: none; cursor: pointer;">Add New</button>
            </div>
        </div>
        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">Unit * (kg, liter, pcs, etc)</label>
            <input type="text" name="unit" required style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid rgba(60,42,33,0.1);">
        </div>
        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">Current Stock</label>
            <input type="number" name="current_stock" step="0.01" value="" style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid rgba(60,42,33,0.1);">
        </div>
        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">Max Stock</label>
            <input type="number" name="max_stock" step="0.01" value="" style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid rgba(60,42,33,0.1);">
        </div>
        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">Reorder Level</label>
            <input type="number" name="reorder_level" step="0.01" value="" style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid rgba(60,42,33,0.1);">
        </div>
        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">Price per Unit (RM)</label>
            <input type="number" name="price_per_unit" step="0.01" value="" style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid rgba(60,42,33,0.1);">
        </div>
        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">Supplier</label>
            <input type="text" name="supplier" style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid rgba(60,42,33,0.1);">
        </div>
        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">Location</label>
            <input type="text" name="location" style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid rgba(60,42,33,0.1);">
        </div>
        <div style="display: flex; gap: 12px; margin-top: 24px;">
            <button type="submit" style="flex: 1; background: #8B5A2B; color: white; padding: 12px; border-radius: 9999px; font-size: 14px; font-weight: bold; text-transform: uppercase; border: none; cursor: pointer;">Add Item</button>
            <button type="button" onclick="document.getElementById('addDialog').close()" style="flex: 1; background: white; border: 1px solid rgba(60,42,33,0.1); padding: 12px; border-radius: 9999px; font-size: 14px; font-weight: bold; text-transform: uppercase; cursor: pointer;">Cancel</button>
        </div>
    </form>
</dialog>

<!-- Dialog Edit Item -->
<dialog id="editDialog" style="background: #FAF9F6; border-radius: 24px; padding: 32px; max-width: 550px; width: 90%; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.2); position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); margin: 0;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h3 style="font-size: 24px; font-weight: 900; color: #3C2A21;">Edit Item</h3>
        <button onclick="document.getElementById('editDialog').close()" style="font-size: 28px; color: #3C2A21/40; background: none; border: none; cursor: pointer;">×</button>
    </div>
    <form method="POST" action="" id="editForm">
        <input type="hidden" name="edit_product" value="1">
        <input type="hidden" name="product_id" id="edit_product_id">
        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">Product Name *</label>
            <input type="text" name="product_name" id="edit_product_name" required style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid rgba(60,42,33,0.1);">
        </div>
        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">Category</label>
            <select name="category_id" id="edit_category_id" style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid rgba(60,42,33,0.1);">
                <option value="">Select Category</option>
                <?php foreach ($modal_categories as $cat): ?>
                <option value="<?php echo $cat['category_id']; ?>"><?php echo $cat['category_name']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">Unit *</label>
            <input type="text" name="unit" id="edit_unit" required style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid rgba(60,42,33,0.1);">
        </div>
        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">Current Stock</label>
            <input type="number" name="current_stock" id="edit_current_stock" step="0.01" style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid rgba(60,42,33,0.1);">
        </div>
        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">Max Stock</label>
            <input type="number" name="max_stock" id="edit_max_stock" step="0.01" style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid rgba(60,42,33,0.1);">
        </div>
        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">Reorder Level</label>
            <input type="number" name="reorder_level" id="edit_reorder_level" step="0.01" style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid rgba(60,42,33,0.1);">
        </div>
        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">Price per Unit (RM)</label>
            <input type="number" name="price_per_unit" id="edit_price_per_unit" step="0.01" style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid rgba(60,42,33,0.1);">
        </div>
        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">Supplier</label>
            <input type="text" name="supplier" id="edit_supplier" style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid rgba(60,42,33,0.1);">
        </div>
        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 12px; font-weight: bold; color: #3C2A21/40; text-transform: uppercase; margin-bottom: 8px;">Location</label>
            <input type="text" name="location" id="edit_location" style="width: 100%; padding: 12px; border-radius: 12px; border: 1px solid rgba(60,42,33,0.1);">
        </div>
        <div style="display: flex; gap: 12px; margin-top: 24px;">
            <button type="submit" style="flex: 1; background: #8B5A2B; color: white; padding: 12px; border-radius: 9999px; font-size: 14px; font-weight: bold; text-transform: uppercase; border: none; cursor: pointer;">Update Item</button>
            <button type="button" onclick="document.getElementById('editDialog').close()" style="flex: 1; background: white; border: 1px solid rgba(60,42,33,0.1); padding: 12px; border-radius: 9999px; font-size: 14px; font-weight: bold; text-transform: uppercase; cursor: pointer;">Cancel</button>
        </div>
    </form>
</dialog>

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

<script>
// General Stock In Modal
function openGeneralStockInModal() {
    document.getElementById('generalStockInModal').showModal();
}

// General Stock Out Modal
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

// Specific Stock In Modal
function openStockInModal(productId, productName) {
    document.getElementById('stockInProductId').value = productId;
    document.getElementById('stockInProductName').value = productName;
    document.getElementById('stockInModal').showModal();
}

// Specific Stock Out Modal
function openStockOutModal(productId, productName, currentStock) {
    document.getElementById('stockOutProductId').value = productId;
    document.getElementById('stockOutProductName').value = productName;
    document.getElementById('stockOutCurrentStock').value = currentStock;
    document.getElementById('stockOutQuantity').max = currentStock;
    document.getElementById('stockOutModal').showModal();
}

// Validate Stock-Out quantity
document.getElementById('stockOutForm')?.addEventListener('submit', function(e) {
    let quantity = parseFloat(document.getElementById('stockOutQuantity').value);
    let currentStock = parseFloat(document.getElementById('stockOutCurrentStock').value);
    if (quantity > currentStock) {
        e.preventDefault();
        alert('Cannot remove more than current stock! Current stock: ' + currentStock);
    }
});

document.getElementById('generalStockOutForm')?.addEventListener('submit', function(e) {
    let quantity = parseFloat(document.getElementById('generalStockOutQuantity').value);
    let currentStock = parseFloat(document.getElementById('generalCurrentStockDisplay').innerHTML.split(' ')[0]);
    if (quantity > currentStock) {
        e.preventDefault();
        alert('Cannot remove more than current stock! Current stock: ' + currentStock);
    }
});

function openAddCategoryModal() {
    document.getElementById('addCategoryDialog').showModal();
}

function openEditModal(productId) {
    fetch('adminInventory.php?get_product=' + productId)
        .then(response => response.json())
        .then(data => {
            document.getElementById('edit_product_id').value = data.product_id;
            document.getElementById('edit_product_name').value = data.product_name;
            document.getElementById('edit_category_id').value = data.category_id || '';
            document.getElementById('edit_unit').value = data.unit;
            document.getElementById('edit_current_stock').value = data.current_stock;
            document.getElementById('edit_max_stock').value = data.max_stock;
            document.getElementById('edit_reorder_level').value = data.reorder_level;
            document.getElementById('edit_price_per_unit').value = data.price_per_unit;
            document.getElementById('edit_supplier').value = data.supplier || '';
            document.getElementById('edit_location').value = data.location || '';
            document.getElementById('editDialog').showModal();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to load product data');
        });
}

function closeCategoryDialogAndRefresh() {
    document.getElementById('addCategoryDialog').close();
    fetch('adminInventory.php?get_categories=1')
        .then(response => response.json())
        .then(data => {
            const select = document.getElementById('categorySelect');
            select.innerHTML = '<option value="">Select Category</option>';
            data.forEach(cat => {
                select.innerHTML += '<option value="' + cat.id + '">' + cat.name + '</option>';
            });
        });
}

document.getElementById('addCategoryForm')?.addEventListener('submit', function(e) {
    setTimeout(() => {
        closeCategoryDialogAndRefresh();
    }, 500);
});
</script>

<?php include 'includes/footer.php'; ?>

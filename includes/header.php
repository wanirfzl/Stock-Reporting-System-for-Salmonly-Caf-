<?php
// includes/header.php
// Prevent direct access
if (!defined('ACCESS_ALLOWED')) {
    exit('Direct access not allowed');
}

// Get current user data
$current_user = getCurrentUser();
$current_time = date('h:i A');
$current_date = date('F j, Y');

// Ensure name exists
$user_name = $current_user['name'] ?? $current_user['full_name'] ?? $_SESSION['user_name'] ?? 'User';
$user_role = $current_user['role'] ?? $_SESSION['user_role'] ?? 'Staff';

// Get user theme preference from database
$user_theme = 'light';
if (isset($_SESSION['user_id'])) {
    $conn = getConnection();
    $user_id = $_SESSION['user_id'];
    $theme_result = mysqli_query($conn, "SELECT theme FROM user_settings WHERE user_id = $user_id");
    if ($row = mysqli_fetch_assoc($theme_result)) {
        $user_theme = $row['theme'];
    }
    mysqli_close($conn);
}

// If theme is system, check system preference
if ($user_theme === 'system') {
    // Default to light, JavaScript will handle the actual detection
    $user_theme = 'light';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salmonly Café - <?php echo $page_title ?? 'Dashboard'; ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@300;400;500;600;700;800&family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

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

        body.dark-mode .bg-[#F2E8DF]/30 {
            background-color: #2a2a2a;
        }

        body.dark-mode .bg-[#F2E8DF]/50 {
            background-color: #2a2a2a;
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

        body.dark-mode .hover\:bg-[#F2E8DF]/50:hover {
            background-color: #3a3a3a;
        }

        body.dark-mode .form-input {
            background-color: #3a3a3a;
            border-color: #505050;
            color: #e0e0e0;
        }

        body.dark-mode .form-input:focus {
            border-color: #8B5A2B;
        }

        body.dark-mode .btn-secondary {
            background-color: #3a3a3a;
            border-color: #505050;
            color: #e0e0e0;
        }

        body.dark-mode .btn-secondary:hover {
            background-color: #4a4a4a;
        }

        :root {
            --primary-brown: #8B5A2B;
            --secondary-brown: #B07A4A;
            --light-brown: #E6B17E;
            --espresso: #3C2A21;
            --terracotta: #B85C38;
            --warm-beige: #F2E8DF;
            --paper-white: #FAF9F6;
            --sage-green: #7A8C71;
        }

        .grain-bg::before {
            content: "";
            position: fixed;
            inset: 0;
            width: 100%;
            height: 100%;
            background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIzMDAiIGhlaWdodD0iMzAwIj48ZmlsdGVyIGlkPSJmIj48ZmVUdXJidWxlbmNlIHR5cGU9ImZyYWN0YWxOb2lzZSIgYmFzZUZyZXF1ZW5jeT0iLjc0IiBudW1PY3RhdmVzPSIzIiAvPjwvZmlsdGVyPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbHRlcj0idXJsKCNmKSIgb3BhY2l0eT0iMC4xNSIgLz48L3N2Zz4=');
            opacity: 0.1;
            pointer-events: none;
            z-index: 1;
        }

        body.dark-mode .grain-bg::before {
            opacity: 0.05;
        }

        .sidebar-gradient {
            background: linear-gradient(180deg, #8B5A2B 0%, #5D3A1A 100%);
        }

        body.dark-mode .sidebar-gradient {
            background: linear-gradient(180deg, #5d3a1a 0%, #3a2410 100%);
        }

        .action-in-gradient {
            background: linear-gradient(135deg, #8B5A2B 0%, #B07A4A 100%);
        }

        .action-out-gradient {
            background: linear-gradient(135deg, #B85C38 0%, #D1734F 100%);
        }

        .card-shadow {
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.05);
        }

        body.dark-mode .card-shadow {
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.3);
        }

        .profile-section {
            cursor: pointer;
            transition: all 0.2s ease;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .profile-section:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }

        .logout-btn {
            width: 100%;
            padding: 0.5rem;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 0.5rem;
            color: white;
            font-size: 0.75rem;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            transition: all 0.2s ease;
            cursor: pointer;
            margin-top: 1rem;
        }

        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            transition: all 0.2s ease;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .nav-link.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-weight: bold;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .content-wrapper {
            position: relative;
            z-index: 2;
        }

        .category-filter {
            padding: 0.5rem 1.25rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: all 0.2s ease;
            cursor: pointer;
            background: white;
            border: 1px solid rgba(60, 42, 33, 0.1);
            color: #3C2A21;
        }

        body.dark-mode .category-filter {
            background: #3a3a3a;
            border-color: #505050;
            color: #e0e0e0;
        }

        .category-filter:hover {
            background: #8B5A2B;
            color: white;
            border-color: #8B5A2B;
        }

        .category-filter.active {
            background: #8B5A2B;
            color: white;
            border-color: #8B5A2B;
        }

        .status-critical {
            background: #B85C38;
            color: white;
            font-size: 0.7rem;
            font-weight: bold;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            width: fit-content;
        }

        .status-healthy {
            background: #7A8C71;
            color: white;
            font-size: 0.7rem;
            font-weight: bold;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            width: fit-content;
        }

        .status-low {
            background: #E6B17E;
            color: #3C2A21;
            font-size: 0.7rem;
            font-weight: bold;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            width: fit-content;
        }

        .status-reorder {
            background: #B85C38;
            color: white;
            font-size: 0.7rem;
            font-weight: bold;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            width: fit-content;
        }

        .stock-table {
            width: 100%;
            border-collapse: collapse;
        }

        .stock-table th {
            text-align: left;
            padding: 1rem 1rem;
            font-size: 0.7rem;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: rgba(60, 42, 33, 0.4);
            border-bottom: 1px solid rgba(60, 42, 33, 0.1);
        }

        body.dark-mode .stock-table th {
            color: rgba(224, 224, 224, 0.5);
            border-bottom-color: #404040;
        }

        .stock-table td {
            padding: 1rem 1rem;
            border-bottom: 1px solid rgba(60, 42, 33, 0.05);
        }

        body.dark-mode .stock-table td {
            border-bottom-color: #404040;
        }

        .stock-table tr:hover {
            background: rgba(242, 232, 223, 0.5);
        }

        body.dark-mode .stock-table tr:hover {
            background: rgba(58, 58, 58, 0.5);
        }

        .action-btn {
            padding: 0.4rem;
            border-radius: 0.5rem;
            background: rgba(60, 42, 33, 0.05);
            color: #3C2A21;
            transition: all 0.2s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
        }

        body.dark-mode .action-btn {
            background: rgba(224, 224, 224, 0.1);
            color: #e0e0e0;
        }

        .action-btn:hover {
            background: #8B5A2B;
            color: white;
        }

        .progress-bar {
            width: 80px;
            height: 6px;
            background: rgba(60, 42, 33, 0.1);
            border-radius: 9999px;
            overflow: hidden;
            margin-top: 4px;
        }

        body.dark-mode .progress-bar {
            background: rgba(224, 224, 224, 0.2);
        }

        .progress-fill {
            height: 100%;
            border-radius: 9999px;
        }

        .progress-fill.critical { background: #B85C38; }
        .progress-fill.low { background: #E6B17E; }
        .progress-fill.healthy { background: #7A8C71; }

        .pagination-btn {
            width: 2rem;
            height: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s ease;
            cursor: pointer;
            background: white;
            border: 1px solid rgba(60, 42, 33, 0.1);
        }

        body.dark-mode .pagination-btn {
            background: #3a3a3a;
            border-color: #505050;
            color: #e0e0e0;
        }

        .pagination-btn:hover {
            background: #8B5A2B;
            color: white;
            border-color: #8B5A2B;
        }

        .pagination-btn.active {
            background: #8B5A2B;
            color: white;
            border-color: #8B5A2B;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid rgba(60, 42, 33, 0.1);
            border-radius: 0.75rem;
            background: white;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #8B5A2B;
            box-shadow: 0 0 0 3px rgba(139, 90, 43, 0.1);
        }

        .btn-primary {
            background: #8B5A2B;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 9999px;
            font-weight: bold;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
        }

        .btn-primary:hover {
            background: #B07A4A;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(139, 90, 43, 0.3);
        }

        .btn-secondary {
            background: white;
            color: #3C2A21;
            padding: 0.75rem 1.5rem;
            border-radius: 9999px;
            font-weight: bold;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: all 0.2s ease;
            border: 1px solid rgba(60, 42, 33, 0.1);
            cursor: pointer;
        }

        .btn-secondary:hover {
            background: #F2E8DF;
        }

        .alert {
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #7A8C71;
            color: white;
        }

        .alert-error {
            background: #B85C38;
            color: white;
        }
    </style>
</head>
<body class="grain-bg <?php echo $user_theme === 'dark' ? 'dark-mode' : ''; ?>">
    <div class="flex min-h-screen w-full">
        <!-- Sidebar -->
        <aside class="hidden lg:flex flex-col w-72 sidebar-gradient text-white sticky top-0 h-screen p-8 z-40">
            <!-- Logo -->
            <div class="flex items-center gap-3 mb-12">
    <div class="size-10 rounded-xl overflow-hidden border border-white/20" style="background: white;">
        <img src="images/logo.png" alt="Salmonly Café Logo" style="width: 100%; height: 100%; object-fit: cover;">
    </div>
    <h2 class="text-xl font-extrabold tracking-tight">Salmonly <span class="text-[#E6B17E]">Café</span></h2>
</div>
            
            <!-- Navigation -->
            <nav class="flex flex-col gap-2 flex-1">
                <a href="staffDashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'staffDashboard.php' ? 'active' : ''; ?>">
                    <span class="material-symbols-outlined">dashboard</span> Dashboard
                </a>
                <a href="staffViewStock.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'staffViewStock.php' ? 'active' : ''; ?>">
                    <span class="material-symbols-outlined">inventory_2</span> View Stock
                </a>
                <a href="staffReports.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'staffReports.php' ? 'active' : ''; ?>">
                    <span class="material-symbols-outlined">analytics</span> Reports
                </a>
                <a href="staffSettings.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'staffSettings.php' ? 'active' : ''; ?>">
                    <span class="material-symbols-outlined">settings</span> Settings
                </a>
            </nav>
            
            <!-- Profile Section -->
            <!-- Profile Section -->
<div class="profile-section" onclick="window.location.href='profile.php'">
    <div class="flex items-center gap-3">
        <div class="size-10 rounded-full border-2 border-[#E6B17E] overflow-hidden">
            <?php 
            // Get user profile picture from database
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
                echo '<img src="https://ui-avatars.com/api/?name=' . urlencode($user_name) . '&background=8B5A2B&color=fff&size=100" alt="Profile" class="w-full h-full object-cover">';
            }
            ?>
        </div>
        <div class="flex flex-col">
            <p class="text-xs font-bold leading-none"><?php echo htmlspecialchars($user_name); ?></p>
            <p class="text-[10px] text-white/50"><?php echo htmlspecialchars($user_role); ?></p>
        </div>
    </div>
</div>
            
            <!-- Logout Button -->
            <form action="logout.php" method="POST">
                <button type="submit" class="logout-btn">
                    Logout
                </button>
            </form>
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

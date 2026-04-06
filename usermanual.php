<?php
// usermanual.php
define('ACCESS_ALLOWED', true);
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salmonly Café - User Manual</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
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
            min-height: 100vh;
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

        .hero-section {
            background: linear-gradient(135deg, #8B5A2B 0%, #5D3A1A 100%);
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            pointer-events: none;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 9999px;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            border: 1px solid rgba(139, 90, 43, 0.2);
            background: white;
            color: #3C2A21;
        }

        .nav-link:hover {
            background: #8B5A2B;
            color: white;
            border-color: #8B5A2B;
            transform: translateY(-2px);
        }

        .nav-link.login-btn {
            background: #8B5A2B;
            color: white;
            border: none;
        }

        .nav-link.login-btn:hover {
            background: #B07A4A;
        }

        .step-card {
            transition: all 0.3s ease;
            background: white;
            border-radius: 1.5rem;
            padding: 2rem;
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.05);
        }

        .step-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px -10px rgba(139, 90, 43, 0.2);
        }

        .step-number {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #8B5A2B 0%, #B07A4A 100%);
            color: white;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
        }

        .feature-card {
            transition: all 0.3s ease;
            background: white;
            border-radius: 1.5rem;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.05);
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px -10px rgba(139, 90, 43, 0.2);
        }

        .faq-item {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .faq-item:hover {
            background: #F2E8DF;
        }

        .faq-question {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: bold;
        }

        .faq-answer {
            display: none;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(60, 42, 33, 0.1);
            color: #3C2A21/70;
        }

        .faq-item.active .faq-answer {
            display: block;
        }
    </style>
</head>
<body class="grain-bg">
    
    <!-- Navigation -->
    <nav class="flex justify-end items-center gap-4 px-6 md:px-10 py-5 max-w-7xl mx-auto">
        <a href="about.php" class="nav-link">
            <span class="material-symbols-outlined">people</span>
            About Us
        </a>
        <a href="login.php" class="nav-link login-btn">
            <span class="material-symbols-outlined">login</span>
            Login
        </a>
        <a href="register.php" class="nav-link">
            <span class="material-symbols-outlined">app_registration</span>
            Register
        </a>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section text-white py-16 md:py-20 relative">
        <div class="max-w-7xl mx-auto px-6 md:px-10 text-center relative z-10">
            <div class="mb-6">
                <span class="material-symbols-outlined text-6xl">menu_book</span>
            </div>
            <h1 class="text-4xl md:text-6xl font-black mb-4">User Manual</h1>
            <p class="text-xl text-white/90 max-w-2xl mx-auto">Learn how to use the Stock Reporting System for Salmonly Café</p>
        </div>
    </section>

    <!-- Quick Guide -->
    <section class="py-16 md:py-20 max-w-7xl mx-auto px-6 md:px-10">
        <div class="text-center mb-12">
            <span class="text-[#8B5A2B] text-sm font-bold uppercase tracking-wider">Quick Guide</span>
            <h2 class="text-3xl md:text-4xl font-black text-[#3C2A21] mt-2">Getting Started</h2>
            <p class="text-[#3C2A21]/60 mt-4 max-w-2xl mx-auto">Follow these simple steps to start using the system</p>
        </div>
        <div class="grid md:grid-cols-3 gap-8">
            <div class="step-card">
                <div class="step-number">1</div>
                <h3 class="text-xl font-bold text-[#3C2A21] mb-2">Register an Account</h3>
                <p class="text-[#3C2A21]/60">Click "Register" and fill in your details. Choose your role (Staff or Administrator).</p>
            </div>
            <div class="step-card">
                <div class="step-number">2</div>
                <h3 class="text-xl font-bold text-[#3C2A21] mb-2">Login to System</h3>
                <p class="text-[#3C2A21]/60">Use your email and password to access the dashboard. Staff and admin have different views.</p>
            </div>
            <div class="step-card">
                <div class="step-number">3</div>
                <h3 class="text-xl font-bold text-[#3C2A21] mb-2">Start Managing Stock</h3>
                <p class="text-[#3C2A21]/60">Record stock-in, stock-out, and generate reports. Keep inventory up to date.</p>
            </div>
        </div>
    </section>

    <!-- Features Guide -->
    <section class="bg-[#FAF9F6] py-16 md:py-20">
        <div class="max-w-7xl mx-auto px-6 md:px-10">
            <div class="text-center mb-12">
                <span class="text-[#8B5A2B] text-sm font-bold uppercase tracking-wider">Features</span>
                <h2 class="text-3xl md:text-4xl font-black text-[#3C2A21] mt-2">System Features</h2>
                <p class="text-[#3C2A21]/60 mt-4 max-w-2xl mx-auto">What you can do with the Stock Reporting System</p>
            </div>
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="feature-card">
                    <span class="material-symbols-outlined text-4xl text-[#8B5A2B]">add_circle</span>
                    <h3 class="text-lg font-bold text-[#3C2A21] mt-3 mb-2">Record Stock-In</h3>
                    <p class="text-sm text-[#3C2A21]/60">Add new stock when deliveries arrive. Update inventory quantities.</p>
                </div>
                <div class="feature-card">
                    <span class="material-symbols-outlined text-4xl text-[#8B5A2B]">remove_circle</span>
                    <h3 class="text-lg font-bold text-[#3C2A21] mt-3 mb-2">Record Stock-Out</h3>
                    <p class="text-sm text-[#3C2A21]/60">Track usage, sales, and waste. Keep accurate stock levels.</p>
                </div>
                <div class="feature-card">
                    <span class="material-symbols-outlined text-4xl text-[#8B5A2B]">inventory</span>
                    <h3 class="text-lg font-bold text-[#3C2A21] mt-3 mb-2">View Stock</h3>
                    <p class="text-sm text-[#3C2A21]/60">Monitor current stock levels with real-time updates and status indicators.</p>
                </div>
                <div class="feature-card">
                    <span class="material-symbols-outlined text-4xl text-[#8B5A2B]">analytics</span>
                    <h3 class="text-lg font-bold text-[#3C2A21] mt-3 mb-2">Generate Reports</h3>
                    <p class="text-sm text-[#3C2A21]/60">View daily stock reports and analyze inventory movement.</p>
                </div>
                <div class="feature-card">
                    <span class="material-symbols-outlined text-4xl text-[#8B5A2B]">calendar_month</span>
                    <h3 class="text-lg font-bold text-[#3C2A21] mt-3 mb-2">Staff Schedule</h3>
                    <p class="text-sm text-[#3C2A21]/60">Manage staff shifts and view your personal schedule.</p>
                </div>
                <div class="feature-card">
                    <span class="material-symbols-outlined text-4xl text-[#8B5A2B]">dark_mode</span>
                    <h3 class="text-lg font-bold text-[#3C2A21] mt-3 mb-2">Dark Mode</h3>
                    <p class="text-sm text-[#3C2A21]/60">Switch between light and dark theme for comfortable viewing.</p>
                </div>
                <div class="feature-card">
                    <span class="material-symbols-outlined text-4xl text-[#8B5A2B]">group</span>
                    <h3 class="text-lg font-bold text-[#3C2A21] mt-3 mb-2">Staff Management</h3>
                    <p class="text-sm text-[#3C2A21]/60">Admin can add, edit, and manage staff accounts.</p>
                </div>
                <div class="feature-card">
                    <span class="material-symbols-outlined text-4xl text-[#8B5A2B]">print</span>
                    <h3 class="text-lg font-bold text-[#3C2A21] mt-3 mb-2">Print Reports</h3>
                    <p class="text-sm text-[#3C2A21]/60">Generate and print daily stock reports with one click.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Staff Guide -->
    <section class="py-16 md:py-20 max-w-7xl mx-auto px-6 md:px-10">
        <div class="text-center mb-12">
            <span class="text-[#8B5A2B] text-sm font-bold uppercase tracking-wider">For Staff</span>
            <h2 class="text-3xl md:text-4xl font-black text-[#3C2A21] mt-2">Staff Guide</h2>
            <p class="text-[#3C2A21]/60 mt-4 max-w-2xl mx-auto">What staff can do in the system</p>
        </div>
        <div class="grid md:grid-cols-2 gap-8">
            <div class="bg-white rounded-2xl p-6 shadow-lg">
                <div class="flex items-center gap-3 mb-4">
                    <span class="material-symbols-outlined text-3xl text-[#8B5A2B]">check_circle</span>
                    <h3 class="text-xl font-bold text-[#3C2A21]">Daily Tasks</h3>
                </div>
                <ul class="space-y-3 text-[#3C2A21]/70">
                    <li class="flex gap-2"><span class="text-[#8B5A2B]">•</span> Record stock-in when deliveries arrive</li>
                    <li class="flex gap-2"><span class="text-[#8B5A2B]">•</span> Record stock-out for daily usage and sales</li>
                    <li class="flex gap-2"><span class="text-[#8B5A2B]">•</span> Update stock during daily stock take before closing</li>
                    <li class="flex gap-2"><span class="text-[#8B5A2B]">•</span> View current stock levels and status alerts</li>
                    <li class="flex gap-2"><span class="text-[#8B5A2B]">•</span> Generate daily stock reports</li>
                    <li class="flex gap-2"><span class="text-[#8B5A2B]">•</span> View personal shift schedule</li>
                </ul>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow-lg">
                <div class="flex items-center gap-3 mb-4">
                    <span class="material-symbols-outlined text-3xl text-[#8B5A2B]">warning</span>
                    <h3 class="text-xl font-bold text-[#3C2A21]">Important Notes</h3>
                </div>
                <ul class="space-y-3 text-[#3C2A21]/70">
                    <li class="flex gap-2"><span class="text-[#8B5A2B]">•</span> Always update stock before closing time</li>
                    <li class="flex gap-2"><span class="text-[#8B5A2B]">•</span> Check low stock alerts to avoid shortages</li>
                    <li class="flex gap-2"><span class="text-[#8B5A2B]">•</span> Record waste accurately to track losses</li>
                    <li class="flex gap-2"><span class="text-[#8B5A2B]">•</span> Use "Update Stock" button for daily stock take</li>
                    <li class="flex gap-2"><span class="text-[#8B5A2B]">•</span> Contact admin if you encounter issues</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- Admin Guide -->
    <section class="bg-[#FAF9F6] py-16 md:py-20">
        <div class="max-w-7xl mx-auto px-6 md:px-10">
            <div class="text-center mb-12">
                <span class="text-[#8B5A2B] text-sm font-bold uppercase tracking-wider">For Admin</span>
                <h2 class="text-3xl md:text-4xl font-black text-[#3C2A21] mt-2">Admin Guide</h2>
                <p class="text-[#3C2A21]/60 mt-4 max-w-2xl mx-auto">Additional features available for administrators</p>
            </div>
            <div class="grid md:grid-cols-2 gap-8">
                <div class="bg-white rounded-2xl p-6 shadow-lg">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="material-symbols-outlined text-3xl text-[#8B5A2B]">admin_panel_settings</span>
                        <h3 class="text-xl font-bold text-[#3C2A21]">Admin Controls</h3>
                    </div>
                    <ul class="space-y-3 text-[#3C2A21]/70">
                        <li class="flex gap-2"><span class="text-[#8B5A2B]">•</span> Add, edit, and delete inventory items</li>
                        <li class="flex gap-2"><span class="text-[#8B5A2B]">•</span> Manage staff accounts and roles</li>
                        <li class="flex gap-2"><span class="text-[#8B5A2B]">•</span> Create and edit staff schedules</li>
                        <li class="flex gap-2"><span class="text-[#8B5A2B]">•</span> View complete stock movement reports</li>
                        <li class="flex gap-2"><span class="text-[#8B5A2B]">•</span> Add new product categories</li>
                        <li class="flex gap-2"><span class="text-[#8B5A2B]">•</span> Manage system settings</li>
                    </ul>
                </div>
                <div class="bg-white rounded-2xl p-6 shadow-lg">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="material-symbols-outlined text-3xl text-[#8B5A2B]">settings</span>
                        <h3 class="text-xl font-bold text-[#3C2A21]">System Settings</h3>
                    </div>
                    <ul class="space-y-3 text-[#3C2A21]/70">
                        <li class="flex gap-2"><span class="text-[#8B5A2B]">•</span> Configure low stock alerts</li>
                        <li class="flex gap-2"><span class="text-[#8B5A2B]">•</span> Set reorder levels for products</li>
                        <li class="flex gap-2"><span class="text-[#8B5A2B]">•</span> Export all data to CSV</li>
                        <li class="flex gap-2"><span class="text-[#8B5A2B]">•</span> View system information</li>
                        <li class="flex gap-2"><span class="text-[#8B5A2B]">•</span> Customize theme preferences</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-16 md:py-20 max-w-7xl mx-auto px-6 md:px-10">
        <div class="text-center mb-12">
            <span class="text-[#8B5A2B] text-sm font-bold uppercase tracking-wider">FAQ</span>
            <h2 class="text-3xl md:text-4xl font-black text-[#3C2A21] mt-2">Frequently Asked Questions</h2>
            <p class="text-[#3C2A21]/60 mt-4 max-w-2xl mx-auto">Common questions about the system</p>
        </div>
        <div class="max-w-3xl mx-auto">
            <div class="faq-item" onclick="toggleFaq(this)">
                <div class="faq-question">
                    <span class="font-bold">How do I record stock-in?</span>
                    <span class="material-symbols-outlined text-[#8B5A2B]">expand_more</span>
                </div>
                <div class="faq-answer">
                    Go to View Stock page, click the "Stock In" button (green). Select the product, enter quantity and notes, then click "Add Stock".
                </div>
            </div>
            <div class="faq-item" onclick="toggleFaq(this)">
                <div class="faq-question">
                    <span class="font-bold">How do I record stock-out?</span>
                    <span class="material-symbols-outlined text-[#8B5A2B]">expand_more</span>
                </div>
                <div class="faq-answer">
                    Go to View Stock page, click the "Stock Out" button (red). Select the product, enter quantity, choose reason (Usage/Sales/Waste), then click "Remove Stock".
                </div>
            </div>
            <div class="faq-item" onclick="toggleFaq(this)">
                <div class="faq-question">
                    <span class="font-bold">What is Daily Stock Take?</span>
                    <span class="material-symbols-outlined text-[#8B5A2B]">expand_more</span>
                </div>
                <div class="faq-answer">
                    Daily Stock Take is a feature to update stock levels at the end of each day. Click "Update Stock" button and enter the actual stock quantity for each item.
                </div>
            </div>
            <div class="faq-item" onclick="toggleFaq(this)">
                <div class="faq-question">
                    <span class="font-bold">How do I generate a daily report?</span>
                    <span class="material-symbols-outlined text-[#8B5A2B]">expand_more</span>
                </div>
                <div class="faq-answer">
                    Go to Reports page. You can view the Daily Stock Report section, select a date, and click "Print" to generate a PDF report.
                </div>
            </div>
            <div class="faq-item" onclick="toggleFaq(this)">
                <div class="faq-question">
                    <span class="font-bold">What do the stock status colors mean?</span>
                    <span class="material-symbols-outlined text-[#8B5A2B]">expand_more</span>
                </div>
                <div class="faq-answer">
                    <strong>Red (Critical)</strong> - Stock is critically low, needs immediate restock.<br>
                    <strong>Yellow (Low)</strong> - Stock is running low, should be restocked soon.<br>
                    <strong>Blue (Reorder)</strong> - Stock has reached reorder level.<br>
                    <strong>Green (Healthy)</strong> - Stock level is sufficient.
                </div>
            </div>
            <div class="faq-item" onclick="toggleFaq(this)">
                <div class="faq-question">
                    <span class="font-bold">How do I change my profile picture?</span>
                    <span class="material-symbols-outlined text-[#8B5A2B]">expand_more</span>
                </div>
                <div class="faq-answer">
                    Go to Profile page, click "Change Photo" button under your avatar. Select an image file (JPG, PNG, GIF) and it will be uploaded automatically.
                </div>
            </div>
            <div class="faq-item" onclick="toggleFaq(this)">
                <div class="faq-question">
                    <span class="font-bold">How do I enable dark mode?</span>
                    <span class="material-symbols-outlined text-[#8B5A2B]">expand_more</span>
                </div>
                <div class="faq-answer">
                    Go to Settings → Appearance tab. Select "Dark" theme and click "Save Appearance Settings".
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-[#3C2A21] text-white py-12">
        <div class="max-w-7xl mx-auto px-6 md:px-10 text-center">
            <img src="images/logo.png" alt="Salmonly Café Logo" class="w-16 h-16 mx-auto rounded-xl mb-4" style="background: white; object-fit: cover;">
            <p class="text-white/70 text-sm">© <?php echo date('Y'); ?> Salmonly Café. All rights reserved.</p>
            <p class="text-white/50 text-xs mt-2">Stock Reporting System v1.0</p>
        </div>
    </footer>

    <script>
        function toggleFaq(element) {
            element.classList.toggle('active');
            const icon = element.querySelector('.faq-question span:last-child');
            if (element.classList.contains('active')) {
                icon.textContent = 'expand_less';
            } else {
                icon.textContent = 'expand_more';
            }
        }
    </script>
</body>
</html>

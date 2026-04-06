<?php
// register.php
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'Administrator') {
        header('Location: adminDashboard.php');
    } else {
        header('Location: staffDashboard.php');
    }
    exit();
}

$error = '';
$success = '';

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = $_POST['fullname'] ?? '';
    $email = $_POST['email'] ?? '';
    $role = $_POST['role'] ?? '';
    $staff_id = $_POST['staff_id'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $terms = isset($_POST['terms']) ? true : false;
    
    // Validation
    if (empty($fullname) || empty($email) || empty($role) || empty($staff_id) || empty($password)) {
        $error = 'All fields are required';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif (!$terms) {
        $error = 'You must agree to the privacy policy';
    } else {
        require_once 'config/database.php';
        $conn = getConnection();
        
        $email = mysqli_real_escape_string($conn, $email);
        $check = mysqli_query($conn, "SELECT * FROM users WHERE email = '$email'");
        
        if (mysqli_num_rows($check) > 0) {
            $error = 'Email already registered';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $join_date = date('Y-m-d');
            $fullname = mysqli_real_escape_string($conn, $fullname);
            $role = mysqli_real_escape_string($conn, $role);
            $staff_id = mysqli_real_escape_string($conn, $staff_id);
            
            $query = "INSERT INTO users (full_name, email, password, role, staff_id, join_date, status) 
                      VALUES ('$fullname', '$email', '$hashed_password', '$role', '$staff_id', '$join_date', 'active')";
            
            if (mysqli_query($conn, $query)) {
                $success = 'Registration successful! Please login with your credentials.';
                $_POST = array();
            } else {
                $error = 'Registration failed: ' . mysqli_error($conn);
            }
        }
        mysqli_close($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salmonly Café - Register</title>
    
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
            min-height: 100vh;
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

        .register-container {
            display: flex;
            min-height: 100vh;
        }

        .left-panel {
            flex: 1;
            background: linear-gradient(135deg, #8B5A2B 0%, #5D3A1A 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 2rem;
            position: relative;
            overflow: hidden;
            height: 100vh;
        }

        .left-panel::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI4MCIgaGVpZ2h0PSI4MCIgdmlld0JveD0iMCAwIDgwIDgwIj48cGF0aCBkPSJNMjAgMjBoMTB2MTBIMjB6TTUwIDUwaDEwdjEwSDUweiIgZmlsbD0iI2ZmZmZmZiIgb3BhY2l0eT0iMC4wNSIvPjwvc3ZnPg==');
            opacity: 0.1;
        }

        .cafe-image-vertical {
            width: 100%;
            height: 85%;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            border-radius: 2rem;
            overflow: hidden;
            margin-bottom: 1.5rem;
            position: relative;
            z-index: 2;
        }

        .vertical-item {
            position: relative;
            width: 100%;
            height: 50%;
            overflow: hidden;
            border-radius: 1.5rem;
            border: 3px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
        }

        .vertical-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            transition: transform 0.5s ease;
            display: block;
        }

        .vertical-item:hover {
            transform: scale(1.02);
            border-color: rgba(255, 255, 255, 0.4);
            box-shadow: 0 20px 30px -8px rgba(0, 0, 0, 0.4);
            z-index: 5;
        }

        .vertical-item:hover img {
            transform: scale(1.1);
        }

        .image-overlay-vertical {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
            padding: 1rem;
            color: white;
            transform: translateY(100%);
            transition: transform 0.3s ease;
        }

        .vertical-item:hover .image-overlay-vertical {
            transform: translateY(0);
        }

        .image-overlay-vertical p {
            font-size: 0.9rem;
            font-weight: 600;
            margin: 0;
            text-align: center;
        }

        .top-item:hover {
            transform: scale(1.02) translateY(-5px);
        }

        .bottom-item:hover {
            transform: scale(1.02) translateY(5px);
        }

        .image-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(139, 90, 43, 0.9);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.8rem;
            font-weight: 600;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            z-index: 3;
        }

        .right-panel {
            flex: 1;
            background: #FAF9F6;
            display: flex;
            flex-direction: column;
            padding: 2rem;
            position: relative;
            overflow-y: auto;
            max-height: 100vh;
        }

        .nav-bar {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 1rem;
            padding: 1rem 0;
            margin-bottom: 1.5rem;
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
            box-shadow: 0 5px 15px -5px rgba(139, 90, 43, 0.3);
        }

        .nav-link.login-btn {
            background: #8B5A2B;
            color: white;
            border: none;
            font-weight: 700;
        }

        .nav-link.login-btn:hover {
            background: #B07A4A;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(139, 90, 43, 0.4);
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .logo-icon {
            width: 60px;
            height: 60px;
            background: #8B5A2B;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 30px -5px rgba(139, 90, 43, 0.3);
        }

        .logo-icon span {
            font-size: 2rem;
            color: white;
        }

        .welcome-text {
            color: white;
            text-align: center;
            max-width: 400px;
            position: relative;
            z-index: 2;
        }

        .welcome-text h1 {
            font-size: 2.2rem;
            font-weight: 900;
            margin-bottom: 0.5rem;
            color: white;
        }

        .welcome-text p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1rem;
            line-height: 1.5;
        }

        .form-container {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
        }

        .form-container h2 {
            font-size: 2rem;
            font-weight: 900;
            color: #3C2A21;
            margin-bottom: 0.25rem;
        }

        .form-container .subtitle {
            color: #8B5A2B;
            font-weight: 700;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 1rem;
        }

        .input-group {
            margin-bottom: 1.2rem;
        }

        .input-group label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #3C2A21/60;
            margin-bottom: 0.3rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper span {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #8B5A2B;
            font-size: 1.25rem;
        }

        .input-wrapper input,
        .input-wrapper select {
            width: 100%;
            padding: 0.8rem 1rem 0.8rem 3rem;
            border: 1px solid rgba(60, 42, 33, 0.1);
            border-radius: 1rem;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            background: white;
            appearance: none;
        }

        .input-wrapper select {
            cursor: pointer;
        }

        .input-wrapper input:focus,
        .input-wrapper select:focus {
            outline: none;
            border-color: #8B5A2B;
            box-shadow: 0 0 0 4px rgba(139, 90, 43, 0.1);
        }

        .checkbox-group {
            margin-bottom: 1rem;
        }

        .checkbox-label {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.5rem;
            transition: background 0.2s ease;
        }

        .checkbox-label:hover {
            background: rgba(139, 90, 43, 0.05);
        }

        .checkbox-label input[type="checkbox"] {
            width: 1.2rem;
            height: 1.2rem;
            margin-top: 0.1rem;
            border: 2px solid rgba(60, 42, 33, 0.2);
            border-radius: 0.3rem;
            cursor: pointer;
            accent-color: #8B5A2B;
        }

        .checkbox-label span {
            font-size: 0.85rem;
            color: #3C2A21;
            line-height: 1.4;
        }

        .checkbox-label a {
            color: #8B5A2B;
            font-weight: 700;
            text-decoration: none;
        }

        .checkbox-label a:hover {
            text-decoration: underline;
        }

        .register-btn {
            width: 100%;
            background: #8B5A2B;
            color: white;
            padding: 0.8rem;
            border: none;
            border-radius: 9999px;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            cursor: pointer;
            transition: all 0.2s ease;
            margin: 0.8rem 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .register-btn:hover {
            background: #B07A4A;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(139, 90, 43, 0.4);
        }

        .register-btn span {
            font-size: 1.2rem;
        }

        .login-link {
            text-align: center;
            color: #3C2A21/60;
            font-size: 0.85rem;
        }

        .login-link a {
            color: #8B5A2B;
            font-weight: 700;
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .error-message {
            background: #B85C38;
            color: white;
            padding: 0.8rem;
            border-radius: 1rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .success-message {
            background: #7A8C71;
            color: white;
            padding: 0.8rem;
            border-radius: 1rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .stats-badge {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 2rem;
            padding: 0.6rem 1.2rem;
            margin-top: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(5px);
        }

        .tagline {
            font-style: italic;
            color: rgba(255, 255, 255, 0.9);
            margin-top: 0.8rem;
            text-align: center;
            font-size: 1rem;
            position: relative;
            z-index: 2;
        }

        @media (max-width: 768px) {
            .register-container {
                flex-direction: column;
            }
            
            .left-panel {
                padding: 1.5rem 1rem;
            }
            
            .cafe-image-vertical {
                height: 300px;
            }
            
            .nav-bar {
                flex-wrap: wrap;
                justify-content: center;
            }
        }
    </style>
</head>
<body class="grain-bg">
    <div class="register-container">
        <!-- Left Panel - Cafe Interior Images Vertical Stack -->
        <div class="left-panel">
            <div class="cafe-image-vertical" style="height: 600px; margin-bottom: 20px;">
    <div class="vertical-item top-item" style="background-image: url('images/cafe1.jpg');">
        <div class="image-overlay-vertical">
            <p>Our Best Seller</p>
        </div>
    </div>
    <div class="vertical-item bottom-item" style="background-image: url('images/cafe2.jpg');">
        <div class="image-overlay-vertical">
            <p>Freshly made</p>
        </div>
    </div>
</div>
            
            <div class="stats-badge">
                <span class="material-symbols-outlined" style="color: #E6B17E;">groups</span>
                <span style="color: white; font-weight: 600;">Join Our Team</span>
            </div>
            
            <div class="welcome-text">
                <h1>Join the Salmonly Team</h1>
                <p>Become part of our family and help us serve the best Mentai in town.</p>
            </div>
            
            <div class="tagline">
                "Smart stock, smooth operations"
            </div>
            
            <div style="position: absolute; bottom: 2rem; left: 2rem; color: rgba(255,255,255,0.1); font-size: 6rem; transform: rotate(-15deg);">
                <span class="material-symbols-outlined" style="font-size: 6rem;">coffee</span>
            </div>
            <div style="position: absolute; top: 2rem; right: 2rem; color: rgba(255,255,255,0.1); font-size: 5rem; transform: rotate(15deg);">
                <span class="material-symbols-outlined" style="font-size: 5rem;">bakery_dining</span>
            </div>
        </div>
        
        <!-- Right Panel - Registration Form with Navigation -->
        <div class="right-panel">
            <div class="nav-bar">
    <a href="about.php" class="nav-link">
        <span class="material-symbols-outlined">people</span>
        About Us
    </a>
    
<a href="usermanual.php" class="nav-link">
    <span class="material-symbols-outlined">menu_book</span>
    User Manual
</a>

    <a href="login.php" class="nav-link login-btn">
        <span class="material-symbols-outlined">login</span>
        Login
    </a>
</div>
            
            <div class="form-container">
                <div class="logo-container">
    <div class="logo-icon" style="background: white;">
        <img src="images/logo.png" alt="Salmonly Café Logo" style="width: 100%; height: 100%; object-fit: cover; border-radius: 1rem;">
    </div>
    <div>
        <h2 style="font-size: 1.5rem; margin: 0;">Salmonly Café</h2>
        <p style="color: #3C2A21/60; font-size: 0.85rem;">Stock Reporting System</p>
    </div>
</div>
                
                <h2>NEW ACCOUNT REGISTRATION</h2>
                <p class="subtitle">Create Account</p>
                <p style="color: #3C2A21/60; margin-bottom: 1rem; font-size: 0.9rem;">Enter your details to access the stock portal.</p>
                
                <?php if ($error): ?>
                <div class="error-message">
                    <span class="material-symbols-outlined">error</span>
                    <span><?php echo $error; ?></span>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="success-message">
                    <span class="material-symbols-outlined">check_circle</span>
                    <span><?php echo $success; ?></span>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="input-group">
                        <label>Full Name</label>
                        <div class="input-wrapper">
                            <span class="material-symbols-outlined">person</span>
                            <input type="text" name="fullname" placeholder="John Doe" value="<?php echo $_POST['fullname'] ?? ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <label>Email Address</label>
                        <div class="input-wrapper">
                            <span class="material-symbols-outlined">mail</span>
                            <input type="email" name="email" placeholder="john@salmonlycafe.com" value="<?php echo $_POST['email'] ?? ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <label>Role Selection</label>
                        <div class="input-wrapper">
                            <span class="material-symbols-outlined">badge</span>
                            <select name="role" required>
                                <option value="">Choose Role</option>
                                <option value="Staff" <?php echo (isset($_POST['role']) && $_POST['role'] == 'Staff') ? 'selected' : ''; ?>>Staff</option>
                                <option value="Administrator" <?php echo (isset($_POST['role']) && $_POST['role'] == 'Administrator') ? 'selected' : ''; ?>>Administrator</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <label>Staff ID</label>
                        <div class="input-wrapper">
                            <span class="material-symbols-outlined">qr_code_scanner</span>
                            <input type="text" name="staff_id" placeholder="STF-XXXX" value="<?php echo $_POST['staff_id'] ?? ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <label>Password</label>
                        <div class="input-wrapper">
                            <span class="material-symbols-outlined">lock</span>
                            <input type="password" name="password" placeholder="••••••" required>
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <label>Confirm Password</label>
                        <div class="input-wrapper">
                            <span class="material-symbols-outlined">lock</span>
                            <input type="password" name="confirm_password" placeholder="••••••" required>
                        </div>
                    </div>
                    
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="terms" <?php echo isset($_POST['terms']) ? 'checked' : ''; ?> required>
                            <span>I agree to Salmonly Cafe's internal <a href="#">staff data & privacy policy</a> regarding inventory management.</span>
                        </label>
                    </div>
                    
                    <button type="submit" class="register-btn">
                        Create Account
                        <span class="material-symbols-outlined">arrow_forward</span>
                    </button>
                </form>
                
                <div class="login-link">
                    Already have an account? <a href="login.php">Sign in here</a>
                </div>
                
                <div style="margin-top: 1.5rem; text-align: center; color: #3C2A21/60; font-size: 0.85rem; border-top: 1px solid rgba(60,42,33,0.1); padding-top: 1rem;">
                    <p>Empowering our team with smart reporting.</p>
                    <p style="margin-top: 0.3rem; font-weight: 600; color: #8B5A2B;">Join our team managing the freshest Salmonly Café stock.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

<?php
// login.php
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

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    require_once 'config/database.php';
    $conn = getConnection();
    
    $email = mysqli_real_escape_string($conn, $email);
    $result = mysqli_query($conn, "SELECT * FROM users WHERE email = '$email'");
    
    if ($row = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['user_name'] = $row['full_name'];
            $_SESSION['user_role'] = $row['role'];
            $_SESSION['user_email'] = $row['email'];
            
            // Update last activity
            mysqli_query($conn, "UPDATE users SET last_stock_activity = NOW() WHERE user_id = " . $row['user_id']);
            
            mysqli_close($conn);
            
            if ($row['role'] === 'Administrator') {
                header('Location: adminDashboard.php');
            } else {
                header('Location: staffDashboard.php');
            }
            exit();
        } else {
            $error = 'Invalid email or password';
        }
    } else {
        $error = 'Invalid email or password';
    }
    mysqli_close($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salmonly Café - Login</title>
    
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

        .login-container {
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

        .cafe-image-grid {
            width: 100%;
            height: 60%;
            display: flex;
            flex-direction: row;
            gap: 1rem;
            border-radius: 2rem;
            overflow: hidden;
            margin-bottom: 2rem;
            position: relative;
            z-index: 2;
        }

        .grid-item {
            position: relative;
            width: 50%;
            height: 100%;
            overflow: hidden;
            border-radius: 1.5rem;
            border: 3px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .grid-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            transition: transform 0.5s ease;
            display: block;
        }

        .grid-item:hover {
            transform: scale(1.02);
            border-color: rgba(255, 255, 255, 0.4);
            box-shadow: 0 20px 30px -8px rgba(0, 0, 0, 0.4);
            z-index: 5;
        }

        .grid-item:hover img {
            transform: scale(1.1);
        }

        .image-overlay-grid {
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

        .grid-item:hover .image-overlay-grid {
            transform: translateY(0);
        }

        .image-overlay-grid p {
            font-size: 0.9rem;
            font-weight: 600;
            margin: 0;
            text-align: center;
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
        }

        .nav-bar {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 1rem;
            padding: 1rem 0;
            margin-bottom: 2rem;
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

        .nav-link.register-btn {
            background: #8B5A2B;
            color: white;
            border: none;
            font-weight: 700;
        }

        .nav-link.register-btn:hover {
            background: #B07A4A;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(139, 90, 43, 0.4);
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
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
            font-size: 2.5rem;
            font-weight: 900;
            margin-bottom: 1rem;
            color: white;
        }

        .welcome-text p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.1rem;
            line-height: 1.6;
        }

        .form-container {
            width: 100%;
            max-width: 450px;
            margin: 0 auto;
        }

        .form-container h2 {
            font-size: 2rem;
            font-weight: 900;
            color: #3C2A21;
            margin-bottom: 0.5rem;
        }

        .form-container .subtitle {
            color: #3C2A21/60;
            margin-bottom: 2rem;
        }

        .input-group {
            margin-bottom: 1.5rem;
        }

        .input-group label {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #3C2A21/60;
            margin-bottom: 0.5rem;
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

        .input-wrapper input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 1px solid rgba(60, 42, 33, 0.1);
            border-radius: 1rem;
            font-size: 1rem;
            transition: all 0.2s ease;
            background: white;
        }

        .input-wrapper input:focus {
            outline: none;
            border-color: #8B5A2B;
            box-shadow: 0 0 0 4px rgba(139, 90, 43, 0.1);
        }

        .sign-in-btn {
            width: 100%;
            background: #8B5A2B;
            color: white;
            padding: 1rem;
            border: none;
            border-radius: 9999px;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 1rem;
        }

        .sign-in-btn:hover {
            background: #B07A4A;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(139, 90, 43, 0.3);
        }

        .register-link {
            text-align: center;
            margin-top: 2rem;
            color: #3C2A21/60;
            font-size: 0.9rem;
        }

        .register-link a {
            color: #8B5A2B;
            font-weight: 700;
            text-decoration: none;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .error-message {
            background: #B85C38;
            color: white;
            padding: 1rem;
            border-radius: 1rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .tagline {
            font-style: italic;
            color: rgba(255, 255, 255, 0.9);
            margin-top: 1rem;
            text-align: center;
            font-size: 1.2rem;
            position: relative;
            z-index: 2;
        }

        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
            }
            
            .left-panel {
                padding: 2rem 1rem;
            }
            
            .cafe-image-grid {
                flex-direction: column;
                height: 400px;
            }
            
            .grid-item {
                width: 100%;
                height: 50%;
            }
            
            .nav-bar {
                flex-wrap: wrap;
                justify-content: center;
            }
        }
    </style>
</head>
<body class="grain-bg">
    <div class="login-container">
        <!-- Left Panel - Cafe Interior Image Grid -->
        <div class="left-panel">
            <div class="cafe-image-grid">
                <div class="grid-item left-image">
                    <img src="images/interior1.jpeg" 
                         alt="Salmonly Café Interior">
                    <div class="image-overlay-grid">
                        <p>Cozy Corner</p>
                    </div>
                </div>
                <div class="grid-item right-image">
                    <img src="images/interior2.jpeg" 
                         alt="Salmonly Café Counter">
                    <div class="image-overlay-grid">
                        <p>Our Brew Bar</p>
                    </div>
                </div>
            </div>
            
            <div class="image-badge">
                <span class="material-symbols-outlined">stars</span>
                <span>Est. 2020</span>
            </div>
            
            <div class="welcome-text">
                <h1>Yummy in Tummy!</h1>
                <p>Centralized Stock Reporting System for Salmonly Café.</p>
            </div>
            
            <div class="tagline">
                "Quality in every dish, precision in every stock"
            </div>
            
            <div style="position: absolute; bottom: 2rem; left: 2rem; color: rgba(255,255,255,0.1); font-size: 8rem; transform: rotate(-15deg);">
                <span class="material-symbols-outlined" style="font-size: 8rem;">restaurant</span>
            </div>
            <div style="position: absolute; top: 2rem; right: 2rem; color: rgba(255,255,255,0.1); font-size: 6rem; transform: rotate(15deg);">
                <span class="material-symbols-outlined" style="font-size: 6rem;">bakery_dining</span>
            </div>
        </div>
        
        <!-- Right Panel - Login Form -->
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

    <a href="register.php" class="nav-link register-btn">
        <span class="material-symbols-outlined">app_registration</span>
        Register
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
                
                <h2>Welcome back</h2>
                <p class="subtitle">Enter your credentials to access the reporting dashboard.</p>
                
                <?php if ($error): ?>
                <div class="error-message">
                    <span class="material-symbols-outlined">error</span>
                    <span><?php echo $error; ?></span>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="input-group">
                        <label>Email address</label>
                        <div class="input-wrapper">
                            <span class="material-symbols-outlined">mail</span>
                            <input type="email" name="email" placeholder="name@salmonly.com" required>
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <label>Password</label>
                        <div class="input-wrapper">
                            <span class="material-symbols-outlined">lock</span>
                            <input type="password" name="password" placeholder="••••••" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="sign-in-btn">
                        Sign In
                    </button>
                </form>
                
                <div class="register-link">
                    New member? <a href="register.php">Create an account</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

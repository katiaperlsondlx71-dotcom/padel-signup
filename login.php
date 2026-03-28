<?php
require_once 'includes/functions.php';

$pageTitle = 'Login';

// Redirect if already logged in
if (isLoggedIn()) {
    redirectTo('index.php');
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    require_csrf_token();
    
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    }
    
    if (empty($password)) {
        $errors[] = 'Password is required';
    }
    
    if (empty($errors)) {
        if (login($email, $password)) {
            // Handle remember me functionality
            if (isset($_POST['remember_me']) && $_POST['remember_me'] == '1') {
                createRememberToken($_SESSION['user_id']);
            }
            
            // Redirect to intended page or home
            $redirect = $_GET['redirect'] ?? 'index.php';
            redirectTo($redirect);
        } else {
            $errors[] = 'Invalid email or password';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Padel Tournament Registration</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #F3F4F6;
            min-height: 100vh;
        }
        
        /* Navigation */
        .nav-bar {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 16px 0;
        }
        
        .nav-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 18px;
            font-weight: 700;
            color: #374151;
            text-decoration: none;
            transition: color 0.2s;
        }
        
        .logo:hover {
            color: #5a9fd4;
        }
        
        .nav-links {
            display: flex;
            gap: 32px;
        }
        
        .nav-link {
            color: #9ca3af;
            text-decoration: none;
            font-size: 15px;
            font-weight: 400;
            transition: color 0.2s;
        }
        
        .nav-link:hover, .nav-link.active {
            color: #374151;
        }
        
        .user-menu {
            position: relative;
        }
        
        .user-button {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #5a9fd4;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .dropdown-menu {
            display: none;
            position: absolute;
            top: 48px;
            right: 0;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            min-width: 180px;
            z-index: 1000;
        }
        
        .dropdown-menu a {
            display: block;
            padding: 12px 16px;
            color: #374151;
            text-decoration: none;
            font-size: 14px;
            white-space: nowrap;
        }
        
        .dropdown-menu a:hover {
            background: #f9fafb;
        }
        
        .dropdown-menu .logout {
            color: #dc2626;
            border-top: 1px solid #e5e7eb;
        }
        
        /* Mobile Menu */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 20px;
            color: #4a5568;
            cursor: pointer;
        }
        
        .mobile-menu-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }
        
        .mobile-menu {
            position: fixed;
            top: 0;
            right: 0;
            width: 280px;
            height: 100%;
            background: white;
            padding: 20px;
            box-shadow: -2px 0 10px rgba(0,0,0,0.1);
        }
        
        .mobile-menu h3 {
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e5e7eb;
            color: #374151;
        }
        
        .mobile-menu a {
            display: block;
            padding: 12px 0;
            color: #374151;
            text-decoration: none;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .mobile-menu a.logout {
            color: #dc2626;
            margin-top: 16px;
            border-bottom: none;
        }
        
        .mobile-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: none;
            border: none;
            font-size: 24px;
            color: #6b7280;
            cursor: pointer;
        }
        
        /* Title Section */
        .title-section {
            background: #FCFCFD;
            padding: 2rem 0 1rem 0;
        }
        
        .title-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .title-container h1 {
            color: #2d3748;
            font-size: 48px;
            font-weight: 400;
            font-family: Georgia, serif;
            line-height: 1.1;
        }
        
        /* Content Area */
        .content-area {
            background: #F3F4F6;
            min-height: 60vh;
            padding: 20px 0;
        }
        
        .content-container {
            max-width: 500px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Login Form */
        .login-form {
            background: white;
            border-radius: 8px;
            padding: 32px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .form-field {
            margin-bottom: 16px;
        }
        
        .form-field label {
            display: block;
            color: #374151;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 6px;
        }
        
        .form-field input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            background: white;
            transition: border-color 0.2s ease;
        }
        
        .form-field input:focus {
            outline: none;
            border-color: #5a9fd4;
            box-shadow: 0 0 0 3px rgba(90, 159, 212, 0.1);
        }
        
        .btn-primary {
            background: #5a9fd4;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        
        .btn-primary:hover {
            background: #4a8bc2;
        }
        
        .login-form .btn-primary {
            width: 100%;
            padding: 12px 24px;
            font-size: 16px;
            font-weight: 600;
        }
        
        .error-message {
            background: #fed7d7;
            color: #742a2a;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #feb2b2;
        }
        
        .signup-link {
            text-align: center;
            margin-top: 24px;
            color: #6b7280;
            font-size: 14px;
        }
        
        .signup-link a {
            color: #5a9fd4;
            text-decoration: none;
            font-weight: 500;
        }
        
        /* Checkbox styling */
        .checkbox-container {
            display: flex !important;
            align-items: center;
            cursor: pointer;
            font-size: 14px;
            color: #374151;
            font-weight: 400 !important;
        }
        
        .checkbox-container input[type="checkbox"] {
            width: auto !important;
            margin-right: 8px;
            cursor: pointer;
        }
        
        /* Mobile Responsive */
        @media (max-width: 767px) {
            .nav-links, .user-menu {
                display: none;
            }
            
            .mobile-menu-btn {
                display: block;
            }
            
            .nav-container .btn-primary {
                padding: 6px 12px;
                font-size: 13px;
            }
            
            .login-form .btn-primary {
                padding: 12px 24px;
                font-size: 16px;
            }
            
            .title-container {
                text-align: center;
                padding: 0 20px;
            }
            
            .title-container h1 {
                font-size: 28px;
                margin: 0;
            }
            
            .nav-container {
                justify-content: space-between;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <div class="nav-bar">
        <div class="nav-container">
            <a href="index.php" class="logo">🎾 Play Padel with Us</a>
            
            <div class="nav-links">
                <a href="player-levels.php" class="nav-link">Player Levels</a>
            </div>
            
            <?php if (isLoggedIn()): ?>
                <div class="user-menu">
                    <div class="dropdown">
                        <button class="user-button" onclick="toggleDropdown()">
                            <?php echo getUserInitials($_SESSION['user_name'] ?? 'User'); ?>
                        </button>
                        <div class="dropdown-menu" id="dropdown">
                            <a href="create-tournament.php">Create Tournament</a>
                            <a href="index.php">My Upcoming Games</a>
                            <a href="previous-games.php">My Previous Games</a>
                            <a href="edit-account.php">My Account</a>
                            <a href="logout.php" class="logout">Logout</a>
                        </div>
                    </div>
                </div>
                
                <button class="mobile-menu-btn" onclick="openMobileMenu()">≡</button>
            <?php else: ?>
                <a href="login.php" class="btn-primary">Login</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Mobile Menu -->
    <?php if (isLoggedIn()): ?>
        <div class="mobile-menu-overlay" id="mobile-overlay" onclick="closeMobileMenu()">
            <div class="mobile-menu" onclick="event.stopPropagation()">
                <button class="mobile-close" onclick="closeMobileMenu()">×</button>
                <h3>Menu</h3>
                <a href="player-levels.php">Player Levels</a>
                <a href="create-tournament.php">Create Tournament</a>
                <a href="index.php">My Upcoming Games</a>
                <a href="previous-games.php">My Previous Games</a>
                <a href="create-tournament.php">Create Tournament</a>
                <a href="edit-account.php">My Account</a>
                <a href="logout.php" class="logout">Logout</a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Title Section -->
    <div class="title-section">
        <div class="title-container">
            <h1>Log In</h1>
        </div>
    </div>

    <!-- Content Area -->
    <div class="content-area">
        <div class="content-container">
            <div class="login-form">
                
                <?php if (!empty($errors)): ?>
                    <div class="error-message">
                        <?php foreach ($errors as $error): ?>
                            <div><?php echo $error; ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="loginForm">
                    <?php echo csrf_field(); ?>
                    <div class="form-field">
                        <label for="email">Email Address</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            required 
                            value="<?php echo htmlspecialchars($email); ?>"
                            placeholder="Enter your email"
                            autocomplete="email"
                        >
                    </div>
                    
                    <div class="form-field">
                        <label for="password">Password</label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required 
                            placeholder="Enter your password"
                            autocomplete="current-password"
                        >
                    </div>
                    
                    <div class="form-field">
                        <label class="checkbox-container">
                            <input type="checkbox" name="remember_me" value="1" checked>
                            <span class="checkmark"></span>
                            Remember me for 30 days
                        </label>
                    </div>
                    
                    <button type="submit" class="btn-primary">
                        Login
                    </button>
                </form>
                
                <div class="signup-link">
                    <a href="forgot-password.php">Forgot your password?</a>
                </div>
                
                <div class="signup-link">
                    Don't have an account? 
                    <a href="register.php">Sign up here</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleDropdown() {
            const dropdown = document.getElementById('dropdown');
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }
        
        function openMobileMenu() {
            document.getElementById('mobile-overlay').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        function closeMobileMenu() {
            document.getElementById('mobile-overlay').style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('dropdown');
            if (dropdown && !e.target.closest('.dropdown')) {
                dropdown.style.display = 'none';
            }
        });
    </script>
</body>
</html>
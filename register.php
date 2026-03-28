<?php
require_once 'includes/functions.php';

$pageTitle = 'Sign Up';

// Redirect if already logged in
if (isLoggedIn()) {
    redirectTo('index.php');
}

$errors = [];
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    require_csrf_token();
    
    $formData = [
        'name' => sanitizeInput($_POST['name'] ?? ''),
        'nickname' => sanitizeInput($_POST['nickname'] ?? ''),
        'email' => sanitizeInput($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? '',
        'country_code' => sanitizeInput($_POST['country_code'] ?? 'XX')
    ];
    
    // Validation
    if (empty($formData['name'])) {
        $errors[] = 'Name is required';
    }
    
    if (empty($formData['email'])) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    if (empty($formData['password'])) {
        $errors[] = 'Password is required';
    } elseif (strlen($formData['password']) < PASSWORD_MIN_LENGTH) {
        $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long';
    }
    
    if ($formData['password'] !== $formData['confirm_password']) {
        $errors[] = 'Passwords do not match';
    }
    
    // Attempt registration if no errors
    if (empty($errors)) {
        $userId = registerUser(
            $formData['name'],
            $formData['nickname'],
            $formData['email'],
            $formData['password'],
            $formData['country_code']
        );
        
        if ($userId && $userId !== 'emoji_error') {
            // Send welcome email
            $emailSent = sendWelcomeEmail($formData['email'], $formData['name']);
            if (!$emailSent) {
                error_log("Failed to send welcome email to {$formData['email']} for user ID {$userId}");
            }
            
            // Auto-login after registration
            if (login($formData['email'], $formData['password'])) {
                $welcomeMessage = 'Welcome! Your account has been created successfully.';
                if ($emailSent) {
                    $welcomeMessage .= ' A welcome email has been sent to your email address.';
                }
                showMessage($welcomeMessage, 'success');
                redirectTo('index.php');
            }
        } elseif ($userId === 'emoji_error') {
            $errors[] = 'Please avoid using emojis in your name or nickname. Special characters may not be supported.';
        } else {
            $errors[] = 'Email address is already registered';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Padel Tournament Registration</title>
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
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .user-button {
            width: 36px;
            height: 36px;
            background: #f3f4f6;
            border: none;
            border-radius: 8px;
            color: #374151;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }
        
        .user-button:hover {
            background: #e5e7eb;
        }
        
        .dropdown {
            position: relative;
        }
        
        .dropdown-menu {
            display: none;
            position: absolute;
            top: 45px;
            right: 0;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            min-width: 180px;
            z-index: 1000;
            overflow: hidden;
        }
        
        .dropdown-menu a {
            display: block;
            padding: 12px 16px;
            color: #374151;
            text-decoration: none;
            font-size: 14px;
            white-space: nowrap;
            line-height: 1.4;
            min-height: auto;
            box-sizing: border-box;
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
        
        .nav-container .btn-primary {
            background: #5a9fd4;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: background 0.2s;
            width: auto;
        }

        .nav-container .btn-primary:hover {
            background: #4a8bc2;
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
        
        /* Form Styling */
        .register-form {
            background: white;
            border-radius: 8px;
            padding: 32px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .form-title {
            text-align: center;
            margin-bottom: 24px;
            color: #374151;
            font-size: 24px;
            font-weight: 600;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 16px;
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
        
        .form-field input, 
        .form-field select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            background: white;
            transition: border-color 0.2s ease;
            line-height: 1.4;
            min-height: 40px;
        }
        
        .form-field select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6,9 12,15 18,9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 16px;
            padding-right: 40px;
        }
        
        .form-field select option {
            padding: 8px 12px;
            line-height: 1.4;
        }
        
        .form-field input:focus, 
        .form-field select:focus {
            outline: none;
            border-color: #5a9fd4;
            box-shadow: 0 0 0 3px rgba(90, 159, 212, 0.1);
        }
        
        
        .btn-primary {
            background: #5a9fd4;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.2s ease;
        }
        
        .btn-primary:hover {
            background: #4a8bc2;
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
                white-space: nowrap;
                width: auto;
            }
            
            .title-container {
                text-align: center;
                padding: 0 20px;
            }
            
            .title-container h1 {
                font-size: 28px;
                margin: 0;
            }
            
            .form-row {
                grid-template-columns: 1fr;
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
                <a href="edit-account.php">My Account</a>
                <a href="logout.php" class="logout">Logout</a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Title Section -->
    <div class="title-section">
        <div class="title-container">
            <h1>Join the Padel Community</h1>
        </div>
    </div>

    <!-- Content Area -->
    <div class="content-area">
        <div class="content-container">
            <div class="register-form">
                
                <?php if (!empty($errors)): ?>
                    <div class="error-message">
                        <?php foreach ($errors as $error): ?>
                            <div><?php echo $error; ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <?php echo csrf_field(); ?>
                    <div class="form-row">
                        <div class="form-field">
                            <label for="name">Full Name</label>
                            <input 
                                type="text" 
                                id="name" 
                                name="name" 
                                required 
                                value="<?php echo htmlspecialchars($formData['name'] ?? ''); ?>"
                                placeholder="Enter your full name"
                            >
                        </div>
                        <div class="form-field">
                            <label for="nickname">Nickname (Optional)</label>
                            <input 
                                type="text" 
                                id="nickname" 
                                name="nickname" 
                                value="<?php echo htmlspecialchars($formData['nickname'] ?? ''); ?>"
                                placeholder="Display name"
                            >
                        </div>
                    </div>
                    
                    <div class="form-field">
                        <label for="email">Email Address</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            required 
                            value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>"
                            placeholder="Enter your email"
                        >
                    </div>
                    
                    <div class="form-field">
                        <label for="country_code">Country</label>
                        <select id="country_code" name="country_code">
                            <option value="XX" <?php echo ($formData['country_code'] ?? 'XX') === 'XX' ? 'selected' : ''; ?>>Select Country</option>
                            <?php
                            global $countries;
                            
                            // Create array without 'XX' and sort by country name
                            $sortedCountries = $countries;
                            unset($sortedCountries['XX']); // Remove "Other" from sorting
                            
                            // Sort by country name
                            uasort($sortedCountries, function($a, $b) {
                                return strcmp($a['name'], $b['name']);
                            });
                            
                            foreach ($sortedCountries as $code => $country) {
                                $selected = ($formData['country_code'] ?? 'XX') === $code ? 'selected' : '';
                                echo "<option value=\"{$code}\" {$selected}>{$country['flag']} {$country['name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    
                    <div class="form-row">
                        <div class="form-field">
                            <label for="password">Password</label>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                required 
                                placeholder="Choose a secure password"
                            >
                        </div>
                        <div class="form-field">
                            <label for="confirm_password">Confirm Password</label>
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                required 
                                placeholder="Confirm your password"
                            >
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-primary">
                        Create Account
                    </button>
                </form>
                
                <div class="signup-link">
                    Already have an account? 
                    <a href="login.php">Sign in here</a>
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
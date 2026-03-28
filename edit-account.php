<?php
require_once 'includes/functions.php';

$pageTitle = 'Edit Account';

// Require login
requireLogin();

$userId = $_SESSION['user_id'];

// Get current user data
$user = getCurrentUser();
if (!$user) {
    showMessage('User not found.', 'error');
    redirectTo('index.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $nickname = trim($_POST['nickname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $country_code = trim($_POST['country_code'] ?? '');
    
    // Simple validation
    if (empty($name) || empty($email)) {
        showMessage('Name and email are required.', 'error');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        showMessage('Please enter a valid email address.', 'error');
    } else {
        // Update user data
        $updateData = [
            'name' => $name,
            'nickname' => $nickname ?: null,
            'email' => $email,
            'country_code' => $country_code
        ];
        
        try {
            $updated = db()->update('users', $updateData, 'id = ?', [$userId]);
            
            if ($updated !== false) {
                // Update session data
                $_SESSION['user_name'] = $name;
                $_SESSION['user_country'] = $country_code;
                
                showMessage('Account updated successfully!', 'success');
                redirectTo('edit-account.php');
            } else {
                showMessage('Failed to update account.', 'error');
            }
        } catch (Exception $e) {
            showMessage('Database error: ' . $e->getMessage(), 'error');
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - Padel Tournament Registration</title>
    <style>
        /* Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        /* Base layout - same as working template */
        html {
            background: #F3F4F6;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #F3F4F6;
            min-height: 100vh;
        }
        
        /* Navigation Bar */
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
        
        .share-btn {
            color: #6b7280;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            padding: 8px 16px;
            background: #f3f4f6;
            border-radius: 8px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .share-btn:hover {
            background: #e5e7eb;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .user-button {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 50%;
            color: #4a5568;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }
        
        .user-button:hover {
            background: #edf2f7;
            border-color: #cbd5e0;
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
            padding: 8px;
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
            overflow-y: auto;
        }
        
        .mobile-menu-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .mobile-menu-title {
            margin: 0;
            color: #374151;
            font-size: 18px;
            font-weight: 600;
        }
        
        .mobile-menu-close {
            background: none;
            border: none;
            font-size: 24px;
            color: #6b7280;
            cursor: pointer;
        }
        
        .mobile-menu-items a {
            display: block;
            padding: 12px 0;
            color: #374151;
            text-decoration: none;
            font-size: 16px;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .mobile-menu-items a:hover {
            color: #5a9fd4;
        }
        
        .mobile-menu-items a.logout {
            color: #dc2626;
            margin-top: 16px;
            border-bottom: none;
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
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        
        .title-container h1 {
            color: #2d3748;
            font-size: 48px;
            font-weight: 400;
            font-family: 'Georgia', 'Times New Roman', serif;
            line-height: 1.1;
        }
        
        .create-btn {
            background: #5a9fd4;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            white-space: nowrap;
            margin-top: 8px;
            transition: background 0.2s;
        }
        
        .create-btn:hover {
            background: #4a8bc2;
        }
        
        /* Content Area */
        .content-area {
            background: #F3F4F6;
            min-height: 60vh;
            padding: 20px 0;
        }
        
        .content-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Form Styling */
        .form-card {
            background: white;
            border-radius: 8px;
            padding: 32px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .form-section {
            margin-bottom: 32px;
        }
        
        .form-section h3 {
            color: #374151;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e5e7eb;
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
        
        .password-note {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 16px;
            font-style: italic;
        }
        
        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            padding-top: 16px;
            border-top: 1px solid #e5e7eb;
            margin-top: 24px;
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
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary:hover {
            background: #4a8bc2;
        }
        
        .btn-secondary {
            background: white;
            color: #6b7280;
            padding: 12px 24px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-secondary:hover {
            background: #f9fafb;
        }
        
        
        /* Mobile Responsive */
        @media (max-width: 767px) {
            .nav-links, .user-menu {
                display: none;
            }
            
            .mobile-menu-btn {
                display: block;
            }
            
            .title-container {
                flex-direction: column;
                align-items: center;
                text-align: center;
                gap: 16px;
                padding: 0 20px;
            }
            
            .title-container h1 {
                font-size: 28px;
                margin: 0;
            }
            
            .create-btn {
                margin-top: 0;
                width: 100%;
                max-width: 280px;
                text-align: center;
                padding: 12px 24px;
                font-size: 16px;
            }
            
            .nav-container {
                justify-content: space-between;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn-primary, .btn-secondary {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <div class="nav-bar">
        <div class="nav-container">
            <a href="index.php" class="logo">🎾 Play Padel with Us</a>
            
            <div class="nav-links">
                <a href="player-levels.php" class="nav-link">Player Levels</a>
            </div>
            
            <!-- Desktop Menu -->
            <div class="user-menu">
                <div class="dropdown">
                    <button class="user-button" onclick="toggleDropdown()">
                        <?php echo getUserInitials($user['name']); ?>
                    </button>
                    <div class="dropdown-menu" id="dropdown">
                        <a href="create-tournament.php">Create Tournament</a>
                        <a href="index.php">My Upcoming Games</a>
                        <a href="previous-games.php">My Previous Games</a>
                        <?php if (isAdmin()): ?>
                            <a href="admin/dashboard.php">Admin Dashboard</a>
                        <?php endif; ?>
                        <a href="edit-account.php">My Account</a>
                        <a href="logout.php" class="logout">Logout</a>
                    </div>
                </div>
            </div>
            
            <!-- Mobile Menu Button -->
            <button class="mobile-menu-btn" id="mobile-menu-btn" onclick="openMobileMenu()">
                ≡
            </button>
            
            <!-- Mobile Menu Overlay -->
            <div class="mobile-menu-overlay" id="mobile-menu-overlay" onclick="closeMobileMenu()">
                <div class="mobile-menu" onclick="event.stopPropagation()">
                    <div class="mobile-menu-header">
                        <h3 class="mobile-menu-title">Menu</h3>
                        <button class="mobile-menu-close" id="mobile-menu-close" onclick="closeMobileMenu()">×</button>
                    </div>
                    
                    <div class="mobile-menu-items">
                        <a href="player-levels.php">Player Levels</a>
                        <a href="create-tournament.php">Create Tournament</a>
                        <a href="index.php">My Upcoming Games</a>
                        <a href="previous-games.php">My Previous Games</a>
                        <?php if (isAdmin()): ?>
                            <a href="admin/dashboard.php">Admin Dashboard</a>
                        <?php endif; ?>
                        <a href="edit-account.php">My Account</a>
                        <a href="logout.php" class="logout">Sign Out</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Title Section -->
    <div class="title-section">
        <div class="title-container">
            <h1>Account Settings</h1>
            <a href="create-tournament.php" class="create-btn">+ Create Tournament</a>
        </div>
    </div>

    <!-- Content Area -->
    <div class="content-area">
        <div class="content-container">
            <div class="form-card">
                <form method="POST">
                    <!-- Personal Information -->
                    <div class="form-section">
                        <h3>Personal Information</h3>
                        <div class="form-row">
                            <div class="form-field">
                                <label for="name">Full Name *</label>
                                <input type="text" id="name" name="name" value="<?php echo sanitizeInput($user['name']); ?>" required>
                            </div>
                            <div class="form-field">
                                <label for="nickname">Nickname (Optional)</label>
                                <input type="text" id="nickname" name="nickname" value="<?php echo sanitizeInput($user['nickname']); ?>" placeholder="Display name for tournaments">
                            </div>
                        </div>
                        <div class="form-field">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" value="<?php echo sanitizeInput($user['email']); ?>" required>
                        </div>
                        <div class="form-field">
                            <label for="country_code">Country</label>
                            <select id="country_code" name="country_code">
                                <option value="XX" <?php echo $user['country_code'] === 'XX' ? 'selected' : ''; ?>>Select Country</option>
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
                                    $selected = $user['country_code'] === $code ? 'selected' : '';
                                    echo "<option value=\"{$code}\" {$selected}>{$country['flag']} {$country['name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Change Password -->
                    <div class="form-section">
                        <h3>Change Password</h3>
                        <div class="password-note">Leave blank to keep current password</div>
                        <div class="form-field">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password">
                        </div>
                        <div class="form-row">
                            <div class="form-field">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password">
                            </div>
                            <div class="form-field">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="form-actions">
                        <a href="index.php" class="btn-secondary">Cancel</a>
                        <button type="submit" class="btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleDropdown() {
            const dropdown = document.getElementById('dropdown');
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }
        
        function openMobileMenu() {
            document.getElementById('mobile-menu-overlay').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        function closeMobileMenu() {
            document.getElementById('mobile-menu-overlay').style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.dropdown')) {
                document.getElementById('dropdown').style.display = 'none';
            }
        });
        
        function copyCurrentPageLink() {
            const url = window.location.href;
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(() => {
                    const button = event.target.closest('a');
                    const originalText = button.innerHTML;
                    button.innerHTML = '✅ Copied!';
                    button.style.background = '#c6f6d5';
                    button.style.color = '#22543d';
                    
                    setTimeout(() => {
                        button.innerHTML = originalText;
                        button.style.background = '';
                        button.style.color = '';
                    }, 2000);
                });
            } else {
                alert('Copy this link:\n' + url);
            }
        }
    </script>
</body>
</html>
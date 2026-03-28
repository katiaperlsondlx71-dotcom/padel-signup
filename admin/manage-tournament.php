<?php
require_once '../includes/functions.php';

// Check if user is admin
if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

if (!isAdmin()) {
    header('Location: unauthorized.php');
    exit;
}

// Get tournament slug from URL
$tournamentSlug = sanitizeInput($_GET['t'] ?? '');

if (!$tournamentSlug) {
    showMessage('Tournament not found.', 'error');
    redirectTo('dashboard.php');
}

// Get tournament details
$tournament = getTournamentBySlug($tournamentSlug);
if (!$tournament) {
    showMessage('Tournament not found.', 'error');
    redirectTo('dashboard.php');
}

$tournamentId = $tournament['id'];

$message = '';
$messageType = '';

// Handle admin actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'add_user':
                $userId = intval($_POST['user_id'] ?? 0);
                $status = $_POST['status'] ?? 'registered';
                
                if (!$userId) {
                    $message = "Please select a user.";
                    $messageType = 'error';
                    break;
                }
                
                // Check if user is already registered
                $existingReg = db()->fetch(
                    "SELECT * FROM registrations WHERE tournament_id = ? AND user_id = ?", 
                    [$tournamentId, $userId]
                );
                
                if ($existingReg) {
                    $message = "User is already registered for this tournament.";
                    $messageType = 'error';
                } else {
                    // Add user to tournament
                    db()->insert('registrations', [
                        'tournament_id' => $tournamentId,
                        'user_id' => $userId,
                        'status' => $status,
                        'registration_time' => date('Y-m-d H:i:s')
                    ]);
                    
                    $message = "User added to tournament successfully!";
                    $messageType = 'success';
                }
                break;
                
            case 'remove_user':
                $userId = intval($_POST['user_id'] ?? 0);
                
                if (!$userId) {
                    $message = "Invalid user ID.";
                    $messageType = 'error';
                    break;
                }
                
                $deleted = db()->query(
                    "DELETE FROM registrations WHERE tournament_id = ? AND user_id = ?", 
                    [$tournamentId, $userId]
                );
                
                if ($deleted->rowCount() > 0) {
                    $message = "User removed from tournament successfully!";
                    $messageType = 'success';
                } else {
                    $message = "Failed to remove user or user not found.";
                    $messageType = 'error';
                }
                break;
                
            case 'change_status':
                $userId = intval($_POST['user_id'] ?? 0);
                $newStatus = $_POST['new_status'] ?? '';
                
                if (!$userId || !in_array($newStatus, ['registered', 'waitlist'])) {
                    $message = "Invalid parameters.";
                    $messageType = 'error';
                    break;
                }
                
                $updated = db()->update(
                    'registrations',
                    ['status' => $newStatus],
                    'tournament_id = ? AND user_id = ?',
                    [$tournamentId, $userId]
                );
                
                if ($updated > 0) {
                    $message = "User status updated successfully!";
                    $messageType = 'success';
                } else {
                    $message = "Failed to update user status.";
                    $messageType = 'error';
                }
                break;
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = 'error';
    }
}

// Get tournament registrations
$registrations = getTournamentRegistrations($tournamentId);
$registeredUsers = array_values(array_filter($registrations, function($r) { return $r['status'] === 'registered'; }));
$waitlistUsers = array_values(array_filter($registrations, function($r) { return $r['status'] === 'waitlist'; }));

// Get all users for adding
$allUsers = db()->fetchAll("
    SELECT u.id, u.name, u.nickname, u.email, u.country_code,
           r.id as registration_id
    FROM users u 
    LEFT JOIN registrations r ON u.id = r.user_id AND r.tournament_id = ?
    ORDER BY u.name ASC
", [$tournamentId]);

// Separate registered and available users
$availableUsers = array_filter($allUsers, function($u) { return !$u['registration_id']; });
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Tournament - Admin</title>
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
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .header h1 {
            color: #1a202c;
            margin-bottom: 8px;
        }
        
        .header .tournament-info {
            color: #6b7280;
            font-size: 14px;
        }
        
        .back-link {
            display: inline-block;
            color: #5a9fd4;
            text-decoration: none;
            margin-bottom: 16px;
            font-weight: 500;
        }
        
        .back-link:hover {
            color: #4a8bc2;
        }
        
        .message {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .message.success {
            background: #c6f6d5;
            color: #22543d;
        }
        
        .message.error {
            background: #fed7d7;
            color: #742a2a;
        }
        
        .section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .section h2 {
            color: #374151;
            margin-bottom: 16px;
            font-size: 18px;
        }
        
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: #374151;
        }
        
        .form-group select,
        .form-group input {
            width: 100%;
            max-width: 300px;
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .btn {
            padding: 6px 12px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-right: 6px;
            margin-bottom: 4px;
            transition: all 0.2s ease;
            background: white;
            color: #374151;
        }
        
        .btn:hover {
            border-color: #d1d5db;
            background: #f9fafb;
        }
        
        .btn-primary {
            color: #5a9fd4;
            border-color: #5a9fd4;
        }
        
        .btn-primary:hover {
            background: #f0f9ff;
            border-color: #4a8bc2;
            color: #4a8bc2;
        }
        
        .btn-secondary {
            color: #6b7280;
            border-color: #6b7280;
        }
        
        .btn-secondary:hover {
            background: #f9fafb;
            border-color: #4b5563;
            color: #4b5563;
        }
        
        .btn-danger {
            color: #dc2626;
            border-color: #dc2626;
        }
        
        .btn-danger:hover {
            background: #fef2f2;
            border-color: #b91c1c;
            color: #b91c1c;
        }
        
        .btn-small {
            padding: 4px 8px;
            font-size: 11px;
        }
        
        .users-list {
            list-style: none;
            padding: 0;
        }
        
        .user-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .user-item:last-child {
            border-bottom: none;
        }
        
        .user-info {
            flex: 1;
        }
        
        .user-name {
            font-weight: 600;
            color: #374151;
        }
        
        .user-email {
            font-size: 12px;
            color: #6b7280;
            margin-top: 2px;
        }
        
        .user-actions {
            display: flex;
            gap: 4px;
        }
        
        .status-badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-registered {
            background: #c6f6d5;
            color: #22543d;
        }
        
        .status-waitlist {
            background: #fef3c7;
            color: #92400e;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }
        
        .stat-item {
            text-align: center;
            padding: 12px;
            background: #f9fafb;
            border-radius: 8px;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #374151;
        }
        
        .stat-label {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
        }
        
        /* Mobile Responsive */
        @media (max-width: 767px) {
            .nav-links, .user-menu {
                display: none;
            }
            
            .mobile-menu-btn {
                display: block;
            }
            
            .container {
                padding: 10px;
            }
            
            .header {
                padding: 16px;
            }
            
            .header h1 {
                font-size: 20px;
            }
            
            .section {
                padding: 16px;
            }
            
            .form-group select,
            .form-group input {
                max-width: 100%;
            }
            
            .stats {
                grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
                gap: 8px;
            }
            
            .stat-item {
                padding: 8px;
            }
            
            .stat-number {
                font-size: 18px;
            }
            
            .user-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
            
            .user-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 4px;
            }
            
            .btn {
                font-size: 11px;
                padding: 4px 8px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <div class="nav-bar">
        <div class="nav-container">
            <a href="../index.php" class="logo">🎾 Play Padel with Us</a>
            
            <div class="nav-links">
                <a href="../player-levels.php" class="nav-link">Player Levels</a>
            </div>
            
            <div class="user-menu">
                <div class="dropdown">
                    <button class="user-button" onclick="toggleDropdown()">
                        <?php echo getUserInitials($_SESSION['user_name'] ?? 'User'); ?>
                    </button>
                    <div class="dropdown-menu" id="dropdown">
                        <a href="../create-tournament.php">Create Tournament</a>
                        <a href="../index.php">My Upcoming Games</a>
                        <a href="../previous-games.php">My Previous Games</a>
                        <a href="dashboard.php">Admin Dashboard</a>
                        <a href="../edit-account.php">My Account</a>
                        <a href="../logout.php" class="logout">Logout</a>
                    </div>
                </div>
            </div>
            
            <button class="mobile-menu-btn" onclick="openMobileMenu()">≡</button>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div class="mobile-menu-overlay" id="mobile-overlay" onclick="closeMobileMenu()">
        <div class="mobile-menu" onclick="event.stopPropagation()">
            <button class="mobile-close" onclick="closeMobileMenu()">×</button>
            <h3>Menu</h3>
            <a href="../player-levels.php">Player Levels</a>
            <a href="../create-tournament.php">Create Tournament</a>
            <a href="../index.php">My Upcoming Games</a>
            <a href="../previous-games.php">My Previous Games</a>
            <a href="dashboard.php">Admin Dashboard</a>
            <a href="../edit-account.php">My Account</a>
            <a href="../logout.php" class="logout">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <a href="dashboard.php" class="back-link">← Back to Admin Dashboard</a>
        
        <div class="header">
            <h1>Manage Tournament</h1>
            <div class="tournament-info">
                <strong><?php echo htmlspecialchars($tournament['name']); ?></strong><br>
                <?php echo formatDate($tournament['date']); ?> at <?php echo formatTime($tournament['start_time']); ?> | 
                Max <?php echo $tournament['max_participants']; ?> players
            </div>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Tournament Statistics -->
        <div class="section">
            <h2>Tournament Statistics</h2>
            <div class="stats">
                <div class="stat-item">
                    <div class="stat-number"><?php echo count($registeredUsers); ?></div>
                    <div class="stat-label">Registered</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo count($waitlistUsers); ?></div>
                    <div class="stat-label">Waitlist</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $tournament['max_participants'] - count($registeredUsers); ?></div>
                    <div class="stat-label">Available Spots</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo count($availableUsers); ?></div>
                    <div class="stat-label">Available Users</div>
                </div>
            </div>
        </div>

        <!-- Add User -->
        <div class="section">
            <h2>Add User to Tournament</h2>
            <?php if (!empty($availableUsers)): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="add_user">
                    <div class="form-group">
                        <label for="user_id">Select User:</label>
                        <select name="user_id" id="user_id" required>
                            <option value="">Choose a user...</option>
                            <?php foreach ($availableUsers as $user): ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo htmlspecialchars(getDisplayName($user)); ?> 
                                    (<?php echo htmlspecialchars($user['email']); ?>)
                                    <?php echo getCountryFlag($user['country_code']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="status">Status:</label>
                        <select name="status" id="status">
                            <option value="registered">Registered</option>
                            <option value="waitlist">Waitlist</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </form>
            <?php else: ?>
                <p style="color: #6b7280; font-style: italic;">All users are already registered for this tournament.</p>
            <?php endif; ?>
        </div>

        <!-- Registered Players -->
        <?php if (!empty($registeredUsers)): ?>
            <div class="section">
                <h2>Registered Players (<?php echo count($registeredUsers); ?>/<?php echo $tournament['max_participants']; ?>)</h2>
                <ul class="users-list">
                    <?php foreach ($registeredUsers as $index => $user): ?>
                        <li class="user-item">
                            <div class="user-info">
                                <div class="user-name">
                                    <?php echo ($index + 1); ?>. <?php echo htmlspecialchars(getDisplayName($user)); ?>
                                    <?php echo getCountryFlag($user['country_code']); ?>
                                    <?php if ($user['user_id'] == $tournament['host_id']): ?>
                                        <span style="color: #10b981; font-weight: bold;">👑 HOST</span>
                                    <?php endif; ?>
                                </div>
                                <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                            </div>
                            <div class="user-actions">
                                <span class="status-badge status-registered">Registered</span>
                                
                                <!-- Move to waitlist -->
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="change_status">
                                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                    <input type="hidden" name="new_status" value="waitlist">
                                    <button type="submit" class="btn btn-secondary btn-small"
                                            onclick="return confirm('Move this user to waitlist?')">
                                        Move to Waitlist
                                    </button>
                                </form>
                                
                                <!-- Remove user -->
                                <?php if ($user['user_id'] != $tournament['host_id']): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="remove_user">
                                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-small"
                                                onclick="return confirm('Remove <?php echo addslashes(htmlspecialchars(getDisplayName($user))); ?> from this tournament?')">
                                            Remove
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Waitlist -->
        <?php if (!empty($waitlistUsers)): ?>
            <div class="section">
                <h2>Waitlist (<?php echo count($waitlistUsers); ?>)</h2>
                <ul class="users-list">
                    <?php foreach ($waitlistUsers as $index => $user): ?>
                        <li class="user-item">
                            <div class="user-info">
                                <div class="user-name">
                                    <?php echo (count($registeredUsers) + $index + 1); ?>. <?php echo htmlspecialchars(getDisplayName($user)); ?>
                                    <?php echo getCountryFlag($user['country_code']); ?>
                                </div>
                                <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                            </div>
                            <div class="user-actions">
                                <span class="status-badge status-waitlist">Waitlist</span>
                                
                                <!-- Move to registered -->
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="change_status">
                                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                    <input type="hidden" name="new_status" value="registered">
                                    <button type="submit" class="btn btn-primary btn-small"
                                            onclick="return confirm('Move this user to registered players?')">
                                        Move to Registered
                                    </button>
                                </form>
                                
                                <!-- Remove user -->
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="remove_user">
                                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-small"
                                            onclick="return confirm('Remove <?php echo addslashes(htmlspecialchars(getDisplayName($user))); ?> from waitlist?')">
                                        Remove
                                    </button>
                                </form>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
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
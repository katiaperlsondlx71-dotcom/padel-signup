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

$message = '';
$messageType = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = intval($_POST['user_id'] ?? 0);
    
    try {
        switch ($action) {
            case 'toggle_admin':
                if ($userId === $_SESSION['user_id']) {
                    $message = "You cannot modify your own admin status.";
                    $messageType = 'error';
                } else {
                    $user = db()->fetch("SELECT is_admin FROM users WHERE id = ?", [$userId]);
                    if ($user) {
                        $newAdminStatus = $user['is_admin'] ? 0 : 1;
                        db()->update('users', ['is_admin' => $newAdminStatus], 'id = ?', [$userId]);
                        $message = $newAdminStatus ? "User granted admin access." : "User admin access revoked.";
                        $messageType = 'success';
                    }
                }
                break;

            case 'toggle_ban':
                if ($userId === $_SESSION['user_id']) {
                    $message = "You cannot ban yourself.";
                    $messageType = 'error';
                } else {
                    $user = db()->fetch("SELECT is_banned FROM users WHERE id = ?", [$userId]);
                    if ($user) {
                        $newBanStatus = $user['is_banned'] ? 0 : 1;
                        db()->update('users', ['is_banned' => $newBanStatus], 'id = ?', [$userId]);
                        $message = $newBanStatus ? "User has been banned." : "User ban has been lifted.";
                        $messageType = 'success';
                    }
                }
                break;
                
            case 'delete_user':
                if ($userId === $_SESSION['user_id']) {
                    $message = "You cannot delete your own account.";
                    $messageType = 'error';
                } else {
                    // First check if user has any tournaments or registrations
                    $tournaments = db()->fetch("SELECT COUNT(*) as count FROM tournaments WHERE host_id = ?", [$userId])['count'];
                    $registrations = db()->fetch("SELECT COUNT(*) as count FROM registrations WHERE user_id = ?", [$userId])['count'];
                    
                    if ($tournaments > 0) {
                        $message = "Cannot delete user: they have hosted {$tournaments} tournament(s). Transfer or delete tournaments first.";
                        $messageType = 'error';
                    } else {
                        // Delete registrations first (foreign key constraint)
                        if ($registrations > 0) {
                            db()->query("DELETE FROM registrations WHERE user_id = ?", [$userId]);
                        }
                        
                        // Delete user
                        $deleted = db()->query("DELETE FROM users WHERE id = ?", [$userId]);
                        if ($deleted) {
                            $message = "User deleted successfully.";
                            $messageType = 'success';
                        } else {
                            $message = "Failed to delete user.";
                            $messageType = 'error';
                        }
                    }
                }
                break;
                
            case 'reset_password':
                $newPassword = $_POST['new_password'] ?? '';
                if (strlen($newPassword) < 6) {
                    $message = "Password must be at least 6 characters long.";
                    $messageType = 'error';
                } else {
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    db()->update('users', ['password_hash' => $hashedPassword], 'id = ?', [$userId]);
                    $message = "Password reset successfully.";
                    $messageType = 'success';
                }
                break;
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = 'error';
    }
}

// Get all users with their statistics
try {
    $users = db()->fetchAll("
        SELECT u.*, 
               COUNT(DISTINCT t.id) as tournaments_hosted,
               COUNT(DISTINCT r.id) as total_registrations,
               MAX(r.registration_time) as last_registration
        FROM users u
        LEFT JOIN tournaments t ON u.id = t.host_id
        LEFT JOIN registrations r ON u.id = r.user_id AND r.status = 'registered'
        GROUP BY u.id
        ORDER BY u.created_at DESC
    ");
} catch (Exception $e) {
    $users = [];
    $message = "Database error: " . $e->getMessage();
    $messageType = 'error';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f7fafc;
            color: #1a202c;
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
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .admin-badge {
            background: #e6fffa;
            color: #234e52;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
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
        .users-table {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        th {
            background: #f7fafc;
            font-weight: 600;
            color: #4a5568;
        }
        .user-row:hover {
            background: #f7fafc;
        }
        .admin-user {
            background: #fef5e7;
        }
        .current-user {
            background: #e6fffa;
        }
        .user-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .admin-badge-user {
            background: #fed7d7;
            color: #742a2a;
        }
        .user-badge-normal {
            background: #e2e8f0;
            color: #4a5568;
        }
        .banned-badge {
            background: #fed7d7;
            color: #742a2a;
        }
        .banned-user {
            background: #fef2f2;
        }
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            margin: 2px;
            display: inline-block;
        }
        .btn-primary {
            background: #5a9fd4;
            color: white;
        }
        .btn-warning {
            background: #f6ad55;
            color: white;
        }
        .btn-danger {
            background: #fc8181;
            color: white;
        }
        .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }
        .btn:hover {
            opacity: 0.8;
        }
        .stats {
            font-size: 11px;
            color: #718096;
        }
        .actions {
            white-space: nowrap;
        }
        /* Responsive Table */
        .table-container {
            overflow-x: auto;
            margin: 0 -12px;
        }
        
        @media (max-width: 768px) {
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
                flex-direction: column;
                gap: 12px;
                text-align: center;
                padding: 16px;
            }
            
            .table-container {
                margin: 0;
            }
            
            table {
                font-size: 12px;
            }
            
            th, td {
                padding: 6px;
            }
            
            .btn {
                font-size: 12px;
                padding: 8px 12px;
                margin: 2px 0;
                min-height: 40px;
                min-width: 80px;
            }
            
            .actions {
                white-space: normal;
            }
            
            .actions form {
                display: block;
                margin-bottom: 4px;
            }
            
            .stats {
                font-size: 10px;
            }
        }
        
        @media (max-width: 600px) {
            /* Stack table for very small screens */
            table, thead, tbody, th, td, tr {
                display: block;
            }
            
            thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }
            
            tr {
                border: 1px solid #e2e8f0;
                margin-bottom: 12px;
                border-radius: 8px;
                background: white;
                padding: 16px;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            }
            
            td {
                border: none;
                border-bottom: 1px solid #e2e8f0;
                position: relative;
                padding: 8px 0;
                padding-left: 100px;
            }
            
            td:last-child {
                border-bottom: none;
            }
            
            td:before {
                content: attr(data-label) ": ";
                position: absolute;
                left: 0;
                top: 8px;
                width: 90px;
                font-weight: 600;
                color: #4a5568;
                font-size: 12px;
            }
            
            .actions {
                padding-left: 0 !important;
                padding-top: 8px;
                padding-bottom: 8px;
            }
            
            .actions:before {
                display: none;
            }
            
            .btn {
                display: block;
                margin: 6px 0;
                text-align: center;
                font-size: 14px;
                padding: 12px 16px;
                min-height: 44px;
                border-radius: 6px;
                font-weight: 500;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            
            .btn:active {
                transform: translateY(1px);
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
    <div class="header">
        <div>
            <h1 style="margin: 0;">👥 Manage Users</h1>
            <p style="margin: 0; color: #718096;">View, edit, and manage user accounts</p>
        </div>
        <div style="display: flex; align-items: center; gap: 12px;">
            <span class="admin-badge">👑 ADMIN</span>
            <a href="dashboard.php" style="color: #5a9fd4; text-decoration: none;">← Back to Dashboard</a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="users-table">
        <div class="table-container">
            <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Country</th>
                    <th>Status</th>
                    <th>Statistics</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <?php 
                    $isCurrentUser = $user['id'] == $_SESSION['user_id'];
                    $rowClass = $isCurrentUser ? 'current-user' : ($user['is_admin'] ? 'admin-user' : '');
                    ?>
                    <tr class="user-row <?php echo $rowClass; ?> <?php echo $user['is_banned'] ? 'banned-user' : ''; ?>">
                        <td data-label="User">
                            <div>
                                <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                <?php if ($user['nickname']): ?>
                                    <div style="font-size: 12px; color: #718096;">
                                        "<?php echo htmlspecialchars($user['nickname']); ?>"
                                    </div>
                                <?php endif; ?>
                                <?php if ($isCurrentUser): ?>
                                    <span style="font-size: 10px; color: #0891b2; font-weight: 600;">(YOU)</span>
                                <?php endif; ?>
                                <?php if ($user['is_banned']): ?>
                                    <div style="font-size: 10px; color: #dc2626; font-weight: 600;">🚫 BANNED</div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td data-label="Email">
                            <?php echo htmlspecialchars($user['email']); ?>
                            <?php if ($user['phone']): ?>
                                <div style="font-size: 11px; color: #718096;">
                                    📞 <?php echo htmlspecialchars($user['phone']); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td data-label="Country">
                            <?php echo getCountryFlag($user['country_code']); ?>
                            <?php echo $user['country_code']; ?>
                        </td>
                        <td data-label="Status">
                            <span class="user-badge <?php echo $user['is_admin'] ? 'admin-badge-user' : 'user-badge-normal'; ?>">
                                <?php echo $user['is_admin'] ? '👑 Admin' : 'User'; ?>
                            </span>
                            <?php if ($user['is_banned']): ?>
                                <br><span class="user-badge banned-badge">🚫 Banned</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Statistics" class="stats">
                            🏆 <?php echo $user['tournaments_hosted']; ?> tournaments<br>
                            📝 <?php echo $user['total_registrations']; ?> registrations
                            <?php if ($user['last_registration']): ?>
                                <br>Last: <?php echo date('M j', strtotime($user['last_registration'])); ?>
                            <?php endif; ?>
                        </td>
                        <td data-label="Joined" style="font-size: 12px; color: #718096;">
                            <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                        </td>
                        <td data-label="Actions" class="actions">
                            <?php if (!$isCurrentUser): ?>
                                <!-- Toggle Admin -->
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle_admin">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="btn <?php echo $user['is_admin'] ? 'btn-warning' : 'btn-primary'; ?>"
                                            onclick="return confirm('<?php echo $user['is_admin'] ? 'Remove admin access?' : 'Grant admin access?'; ?>')">
                                        <?php echo $user['is_admin'] ? '⬇️ Revoke' : '⬆️ Promote'; ?>
                                    </button>
                                </form>
                                
                                <!-- Ban/Unban User -->
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle_ban">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="btn <?php echo $user['is_banned'] ? 'btn-primary' : 'btn-danger'; ?>"
                                            onclick="return confirm('<?php echo $user['is_banned'] ? 'Lift ban for this user?' : 'Ban this user from joining tournaments?'; ?>')">
                                        <?php echo $user['is_banned'] ? '✅ Unban' : '🚫 Ban'; ?>
                                    </button>
                                </form>

                                <!-- Reset Password -->
                                <button class="btn btn-secondary" onclick="resetPassword(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['name']); ?>')">
                                    🔑 Reset
                                </button>
                                
                                <!-- Delete User -->
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="btn btn-danger"
                                            onclick="return confirm('Are you sure you want to delete <?php echo htmlspecialchars($user['name']); ?>? This action cannot be undone.')">
                                        🗑️ Delete
                                    </button>
                                </form>
                            <?php else: ?>
                                <span style="color: #718096; font-size: 11px;">Current User</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: #718096; padding: 40px;">
                            No users found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top: 24px; text-align: center; color: #718096; font-size: 14px;">
        Total Users: <?php echo count($users); ?>
    </div>
</div>

<!-- Password Reset Modal -->
<div id="passwordModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 24px; border-radius: 8px; min-width: 300px;">
        <h3 style="margin-top: 0;">Reset Password</h3>
        <form method="POST">
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="user_id" id="resetUserId">
            
            <p>Reset password for: <strong id="resetUserName"></strong></p>
            
            <div style="margin: 16px 0;">
                <label style="display: block; margin-bottom: 6px; font-weight: 500;">New Password:</label>
                <input type="password" name="new_password" required minlength="6" 
                       style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
            </div>
            
            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px;">
                <button type="button" onclick="closePasswordModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Reset Password</button>
            </div>
        </form>
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

function resetPassword(userId, userName) {
    document.getElementById('resetUserId').value = userId;
    document.getElementById('resetUserName').textContent = userName;
    document.getElementById('passwordModal').style.display = 'block';
}

function closePasswordModal() {
    document.getElementById('passwordModal').style.display = 'none';
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('dropdown');
    if (dropdown && !e.target.closest('.dropdown')) {
        dropdown.style.display = 'none';
    }
});

// Close modal when clicking outside
document.getElementById('passwordModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closePasswordModal();
    }
});
</script>

</body>
</html>
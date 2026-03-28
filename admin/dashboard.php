<?php
require_once '../includes/functions.php';

// Function to render tournament row
function renderTournamentRow($tournament) {
    $timeInfo = formatTournamentTimeForUser($tournament, true);
    
    // Format date specifically for admin dashboard (without day, with year)
    $converted = convertTournamentTimeForUser($tournament);
    $adminDate = date('M j, Y', strtotime($converted['user_date']));
    
    // Determine status styling
    $statusClass = '';
    $statusText = '';
    
    // Check for cancelled status first (overrides time-based status)
    if ($tournament['status'] === 'cancelled') {
        $statusClass = 'background: #f3f4f6; color: #6b7280; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; text-decoration: line-through;';
        $statusText = '❌ Cancelled';
    } elseif ($tournament['time_status'] === 'past') {
        $statusClass = 'background: #fee2e2; color: #991b1b; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;';
        $statusText = '📋 Past';
    } elseif ($tournament['time_status'] === 'today') {
        $statusClass = 'background: #fef3c7; color: #92400e; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;';
        $statusText = '🔥 Today';
    } else {
        $statusClass = 'background: #dcfce7; color: #166534; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;';
        $statusText = '📅 Upcoming';
    }
    
    // Game state indicator
    $gameStateIcon = '';
    if ($tournament['game_state'] === 'in_progress') {
        $gameStateIcon = ' <span style="color: #10b981; font-weight: bold;">🎯</span>';
    } elseif ($tournament['game_state'] === 'completed') {
        $gameStateIcon = ' <span style="color: #6b7280; font-weight: bold;">✅</span>';
    }
    
    // Participation status
    $participantColor = '#374151';
    if ($tournament['registered_count'] >= $tournament['max_participants']) {
        $participantColor = '#dc2626'; // Red if full
    } elseif ($tournament['registered_count'] >= $tournament['max_participants'] * 0.8) {
        $participantColor = '#f59e0b'; // Orange if nearly full
    }
    
    echo '<tr style="' . ($tournament['time_status'] === 'today' ? 'background: #fffbeb;' : '') . '">';
    echo '<td data-label="Tournament">';
    echo '<div style="font-weight: 600; color: #374151;">' . htmlspecialchars($tournament['name']) . $gameStateIcon . '</div>';
    if (!empty($tournament['location'])) {
        echo '<div style="font-size: 12px; color: #6b7280; margin-top: 2px;">📍 ' . htmlspecialchars($tournament['location']) . '</div>';
    }
    echo '</td>';
    
    echo '<td data-label="Host">';
    $hostData = ['name' => $tournament['host_name'], 'nickname' => $tournament['host_nickname']];
    echo htmlspecialchars(getDisplayName($hostData) ?: 'Unknown');
    echo '</td>';
    
    echo '<td data-label="Date & Time">';
    echo '<div>' . $adminDate . '</div>';
    echo '<div style="font-size: 12px; color: #6b7280;">' . $timeInfo['start_time'] . ' - ' . $timeInfo['end_time'] . '</div>';
    echo '</td>';
    
    echo '<td data-label="Level">';
    echo '<span style="text-transform: capitalize; padding: 2px 8px; background: #f3f4f6; border-radius: 12px; font-size: 11px; font-weight: 600;">';
    echo htmlspecialchars($tournament['level']);
    echo '</span>';
    echo '</td>';
    
    echo '<td data-label="Participants">';
    echo '<div style="font-weight: 600; color: ' . $participantColor . ';">';
    echo $tournament['registered_count'] . '/' . $tournament['max_participants'];
    echo '</div>';
    if ($tournament['waitlist_count'] > 0) {
        echo '<div style="font-size: 11px; color: #f59e0b;">+' . $tournament['waitlist_count'] . ' waitlist</div>';
    }
    echo '</td>';
    
    echo '<td data-label="Status">';
    echo '<span style="' . $statusClass . '">' . $statusText . '</span>';
    echo '</td>';
    
    echo '<td data-label="Actions">';
    echo '<a href="../tournament.php?t=' . $tournament['slug'] . '" style="color: #5a9fd4; text-decoration: none; margin-right: 12px; font-size: 13px; font-weight: 500;">View</a>';
    echo '<a href="manage-tournament.php?t=' . $tournament['slug'] . '" style="color: #10b981; text-decoration: none; margin-right: 12px; font-size: 13px; font-weight: 500;">Manage</a>';
    echo '<form method="POST" style="display: inline;">';
    echo '<input type="hidden" name="action" value="delete_tournament">';
    echo '<input type="hidden" name="tournament_id" value="' . intval($tournament['id']) . '">';
    echo '<button type="submit" onclick="return confirmDelete(\'' . addslashes(htmlspecialchars($tournament['name'])) . '\')" style="background: #ef4444; color: white; border: none; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; cursor: pointer; transition: background 0.2s;" onmouseover="this.style.background=\'#dc2626\'" onmouseout="this.style.background=\'#ef4444\'">Delete</button>';
    echo '</form>';
    echo '</td>';
    
    echo '</tr>';
}

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

// Handle settings form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'toggle_scoring') {
        $newValue = isset($_POST['scoring_enabled']) ? '1' : '0';
        if (setSetting('scoring_system_enabled', $newValue, 'Enable/disable the scoring system feature')) {
            $message = $newValue === '1' ? 'Scoring system enabled!' : 'Scoring system disabled!';
            $messageType = 'success';
        } else {
            $message = 'Failed to update scoring system setting.';
            $messageType = 'error';
        }
    } elseif ($_POST['action'] === 'delete_tournament') {
        $tournamentId = intval($_POST['tournament_id'] ?? 0);
        
        if (!$tournamentId) {
            $message = 'Invalid tournament ID.';
            $messageType = 'error';
        } else {
            // Get tournament name for confirmation message
            $tournament = getTournament($tournamentId);
            $tournamentName = $tournament ? $tournament['name'] : 'Unknown Tournament';
            
            if (deleteTournament($tournamentId)) {
                $message = "Tournament '{$tournamentName}' has been deleted successfully.";
                $messageType = 'success';
            } else {
                $message = "Failed to delete tournament '{$tournamentName}'.";
                $messageType = 'error';
            }
        }
    }
}

// Get basic statistics with error handling
try {
    $totalTournaments = db()->fetch("SELECT COUNT(*) as count FROM tournaments")['count'] ?? 0;
    $upcomingTournaments = db()->fetch("SELECT COUNT(*) as count FROM tournaments WHERE status = 'upcoming'")['count'] ?? 0;
    $totalUsers = db()->fetch("SELECT COUNT(*) as count FROM users WHERE is_admin = FALSE")['count'] ?? 0;
    $totalRegistrations = db()->fetch("SELECT COUNT(*) as count FROM registrations")['count'] ?? 0;
} catch (Exception $e) {
    $totalTournaments = 0;
    $upcomingTournaments = 0;
    $totalUsers = 0;
    $totalRegistrations = 0;
    $dbError = "Database error: " . $e->getMessage();
}

// Get all tournaments with detailed information
try {
    $allTournaments = db()->fetchAll("
        SELECT t.*, 
               u.name as host_name, u.nickname as host_nickname,
               COUNT(r.id) as registration_count,
               COUNT(CASE WHEN r.status = 'registered' THEN 1 END) as registered_count,
               COUNT(CASE WHEN r.status = 'waitlist' THEN 1 END) as waitlist_count,
               CASE 
                   WHEN t.date < CURDATE() THEN 'past'
                   WHEN t.date = CURDATE() THEN 'today'
                   ELSE 'future'
               END as time_status
        FROM tournaments t
        LEFT JOIN users u ON t.host_id = u.id
        LEFT JOIN registrations r ON t.id = r.tournament_id
        GROUP BY t.id
        ORDER BY t.date DESC, t.start_time DESC
    ") ?? [];
    
    // Separate tournaments by status for better organization
    $upcomingTournamentsArray = array_filter($allTournaments, function($t) { 
        return ($t['time_status'] === 'future' || $t['time_status'] === 'today') && $t['status'] !== 'cancelled'; 
    });
    $pastTournamentsArray = array_filter($allTournaments, function($t) { 
        return $t['time_status'] === 'past' || $t['status'] === 'cancelled'; 
    });
    
} catch (Exception $e) {
    $allTournaments = [];
    $upcomingTournamentsArray = [];
    $pastTournamentsArray = [];
    $dbError = ($dbError ?? '') . " Tournament fetch error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Padel Tournament Registration</title>
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Admin Dashboard Specific Styles */
        .admin-badge {
            background: #e6fffa;
            color: #234e52;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        .stat-card {
            background: white;
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .stat-number {
            font-size: 36px;
            font-weight: 700;
            color: #1a202c;
            margin: 8px 0;
        }
        .section {
            background: white;
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 24px;
        }
        .btn {
            background: #5a9fd4;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            margin-right: 12px;
            font-weight: 500;
        }
        .btn:hover {
            background: #4a8bc2;
        }
        .error {
            background: #fed7d7;
            color: #742a2a;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
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
        }
        
        /* Responsive Table */
        .table-container {
            overflow-x: auto;
            margin: 0 -12px;
        }
        
        @media (max-width: 768px) {
            .table-container {
                margin: 0;
            }
            
            table {
                font-size: 14px;
            }
            
            th, td {
                padding: 8px;
            }
            
            /* Stack table for very small screens */
            @media (max-width: 600px) {
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
                    padding-left: 120px;
                }
                
                td:last-child {
                    border-bottom: none;
                }
                
                td:before {
                    content: attr(data-label) ": ";
                    position: absolute;
                    left: 0;
                    top: 8px;
                    width: 110px;
                    font-weight: 600;
                    color: #4a5568;
                }
            }
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
                text-align: center;
                padding: 0 20px;
            }
            
            .title-container h1 {
                font-size: 28px;
                margin: 0;
            }
            
            .stats {
                grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
                gap: 12px;
            }
            
            .stat-card {
                padding: 16px;
            }
            
            .stat-number {
                font-size: 24px;
            }
            
            .section {
                padding: 16px;
            }
            
            .btn {
                display: block;
                margin: 8px 0;
                text-align: center;
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

    <!-- Title Section -->
    <div class="title-section">
        <div class="title-container">
            <h1>Admin Dashboard</h1>
        </div>
    </div>

    <!-- Content Area -->
    <div class="content-area">
        <div class="content-container">

    <?php if (isset($dbError)): ?>
        <div class="error">
            <?php echo htmlspecialchars($dbError); ?>
        </div>
    <?php endif; ?>

    <!-- Statistics -->
    <div class="stats">
        <div class="stat-card">
            <div style="font-size: 32px;">🏆</div>
            <div class="stat-number"><?php echo $totalTournaments; ?></div>
            <div style="color: #718096;">Total Tournaments</div>
        </div>
        
        <div class="stat-card">
            <div style="font-size: 32px;">📅</div>
            <div class="stat-number"><?php echo $upcomingTournaments; ?></div>
            <div style="color: #718096;">Upcoming</div>
        </div>
        
        <div class="stat-card">
            <div style="font-size: 32px;">👥</div>
            <div class="stat-number"><?php echo $totalUsers; ?></div>
            <div style="color: #718096;">Users</div>
        </div>
        
        <div class="stat-card">
            <div style="font-size: 32px;">📝</div>
            <div class="stat-number"><?php echo $totalRegistrations; ?></div>
            <div style="color: #718096;">Registrations</div>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- System Settings -->
    <div class="section">
        <h2>System Settings</h2>
        <form method="POST" style="margin-bottom: 20px;">
            <input type="hidden" name="action" value="toggle_scoring">
            <div style="display: flex; align-items: center; gap: 12px; padding: 16px; background: #f8fafc; border-radius: 6px; border: 1px solid #e2e8f0;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; user-select: none;">
                    <input type="checkbox" name="scoring_enabled" 
                           <?php echo isScoringSystemEnabled() ? 'checked' : ''; ?>
                           onchange="this.form.submit()"
                           style="margin: 0;">
                    <span style="font-weight: 500; color: #374151;">🎯 Enable Scoring System</span>
                </label>
                <span style="color: #6b7280; font-size: 14px; margin-left: auto;">
                    <?php echo isScoringSystemEnabled() ? 'Active - Start Game buttons visible to admins' : 'Disabled - Traditional tournament view only'; ?>
                </span>
            </div>
        </form>
    </div>

    <!-- Quick Actions -->
    <div class="section">
        <h2>Quick Actions</h2>
        <a href="../create-tournament.php" class="btn">➕ Create Tournament</a>
        <a href="manage-users.php" class="btn" style="background: #10b981;">👥 Manage Users</a>
        <a href="../admin-test.php" class="btn" style="background: #e2e8f0; color: #4a5568;">🔧 Debug Info</a>
    </div>

    <!-- All Tournaments Management -->
    <div class="section">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2>Tournament Management</h2>
            <div style="display: flex; gap: 8px;">
                <button onclick="showUpcoming()" id="upcomingBtn" class="btn" style="background: #5a9fd4; color: white;">
                    Upcoming (<?php echo count($upcomingTournamentsArray); ?>)
                </button>
                <button onclick="showPast()" id="pastBtn" class="btn" style="background: #e2e8f0; color: #4a5568;">
                    Past & Cancelled (<?php echo count($pastTournamentsArray); ?>)
                </button>
                <button onclick="showAll()" id="allBtn" class="btn" style="background: #e2e8f0; color: #4a5568;">
                    All (<?php echo count($allTournaments); ?>)
                </button>
            </div>
        </div>
        
        <?php if (empty($allTournaments)): ?>
            <p style="color: #718096; font-style: italic; text-align: center; padding: 40px;">No tournaments found.</p>
        <?php else: ?>
            <!-- Upcoming Tournaments -->
            <div id="upcomingSection" style="display: block;">
                <h3 style="color: #374151; margin-bottom: 16px; font-size: 16px; font-weight: 600;">
                    📅 Upcoming Tournaments (<?php echo count($upcomingTournamentsArray); ?>)
                </h3>
                <?php if (empty($upcomingTournamentsArray)): ?>
                    <p style="color: #718096; font-style: italic; padding: 20px; text-align: center;">No upcoming tournaments.</p>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Tournament</th>
                                    <th>Host</th>
                                    <th>Date & Time</th>
                                    <th>Level</th>
                                    <th>Participants</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcomingTournamentsArray as $tournament): ?>
                                    <?php renderTournamentRow($tournament); ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Past Tournaments -->
            <div id="pastSection" style="display: none;">
                <h3 style="color: #374151; margin-bottom: 16px; font-size: 16px; font-weight: 600;">
                    📋 Past & Cancelled Tournaments (<?php echo count($pastTournamentsArray); ?>)
                </h3>
                <?php if (empty($pastTournamentsArray)): ?>
                    <p style="color: #718096; font-style: italic; padding: 20px; text-align: center;">No past or cancelled tournaments.</p>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Tournament</th>
                                    <th>Host</th>
                                    <th>Date & Time</th>
                                    <th>Level</th>
                                    <th>Participants</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pastTournamentsArray as $tournament): ?>
                                    <?php renderTournamentRow($tournament); ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- All Tournaments -->
            <div id="allSection" style="display: none;">
                <h3 style="color: #374151; margin-bottom: 16px; font-size: 16px; font-weight: 600;">
                    🗂️ All Tournaments (<?php echo count($allTournaments); ?>)
                </h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Tournament</th>
                                <th>Host</th>
                                <th>Date & Time</th>
                                <th>Level</th>
                                <th>Participants</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allTournaments as $tournament): ?>
                                <?php renderTournamentRow($tournament); ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Debug Information -->
    <div class="section">
        <h2>Debug Information</h2>
        <p><strong>Current User:</strong> <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Not set'); ?></p>
        <p><strong>User ID:</strong> <?php echo $_SESSION['user_id'] ?? 'Not set'; ?></p>
        <p><strong>Admin Status:</strong> <?php echo isAdmin() ? 'TRUE' : 'FALSE'; ?></p>
        <p><strong>Session Admin:</strong> <?php echo isset($_SESSION['is_admin']) ? ($_SESSION['is_admin'] ? 'TRUE' : 'FALSE') : 'Not set'; ?></p>
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
        
        // Tournament view switching functions
        function showUpcoming() {
            document.getElementById('upcomingSection').style.display = 'block';
            document.getElementById('pastSection').style.display = 'none';
            document.getElementById('allSection').style.display = 'none';
            
            // Update button styles
            document.getElementById('upcomingBtn').style.background = '#5a9fd4';
            document.getElementById('upcomingBtn').style.color = 'white';
            document.getElementById('pastBtn').style.background = '#e2e8f0';
            document.getElementById('pastBtn').style.color = '#4a5568';
            document.getElementById('allBtn').style.background = '#e2e8f0';
            document.getElementById('allBtn').style.color = '#4a5568';
        }
        
        function showPast() {
            document.getElementById('upcomingSection').style.display = 'none';
            document.getElementById('pastSection').style.display = 'block';
            document.getElementById('allSection').style.display = 'none';
            
            // Update button styles
            document.getElementById('upcomingBtn').style.background = '#e2e8f0';
            document.getElementById('upcomingBtn').style.color = '#4a5568';
            document.getElementById('pastBtn').style.background = '#5a9fd4';
            document.getElementById('pastBtn').style.color = 'white';
            document.getElementById('allBtn').style.background = '#e2e8f0';
            document.getElementById('allBtn').style.color = '#4a5568';
        }
        
        function showAll() {
            document.getElementById('upcomingSection').style.display = 'none';
            document.getElementById('pastSection').style.display = 'none';
            document.getElementById('allSection').style.display = 'block';
            
            // Update button styles
            document.getElementById('upcomingBtn').style.background = '#e2e8f0';
            document.getElementById('upcomingBtn').style.color = '#4a5568';
            document.getElementById('pastBtn').style.background = '#e2e8f0';
            document.getElementById('pastBtn').style.color = '#4a5568';
            document.getElementById('allBtn').style.background = '#5a9fd4';
            document.getElementById('allBtn').style.color = 'white';
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('dropdown');
            if (dropdown && !e.target.closest('.dropdown')) {
                dropdown.style.display = 'none';
            }
        });
        
        // Tournament deletion confirmation
        function confirmDelete(tournamentName) {
            return confirm('Are you sure you want to delete "' + tournamentName + '"?\n\nThis will permanently remove the tournament and all its registrations. This action cannot be undone.');
        }
    </script>

</body>
</html>
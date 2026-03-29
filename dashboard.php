<?php
require_once 'includes/functions.php';

$pageTitle = 'My Upcoming Games';

// Auto-mark completed tournaments
autoMarkCompletedTournaments();

// Helper functions
function getTournamentStatus($registeredCount, $maxParticipants) {
    $percentage = ($registeredCount / $maxParticipants) * 100;
    
    if ($percentage >= 100) {
        return ['status' => 'full', 'text' => 'Tournament Full', 'class' => 'full'];
    } elseif ($percentage >= 70) {
        return ['status' => 'filling-fast', 'text' => 'Filling Fast', 'class' => 'filling-fast'];
    } else {
        return ['status' => 'open', 'text' => 'Open for Registration', 'class' => 'active'];
    }
}

function getSkillLevel($level) {
    $level = strtolower($level);
    if (strpos($level, 'mixed') !== false) return 'mixed';
    if (strpos($level, 'beginner') !== false) return 'beginner';
    if (strpos($level, 'intermediate') !== false) return 'intermediate';
    if (strpos($level, 'advanced') !== false) return 'advanced';
    return 'mixed';
}

// Initialize variables
$uniqueTournaments = [];

// If user is logged in, get their tournaments
if (isLoggedIn()) {
    $userId = $_SESSION['user_id'];
    
    // Get tournaments the user is registered for (both registered and waitlist)
    // Only show tournaments that haven't ended yet (current date/time is before tournament end)
    $myRegistrations = db()->fetchAll("
        SELECT t.*, r.status as registration_status, r.registration_time,
               u.name as host_name, u.nickname as host_nickname, u.country_code as host_country,
               COUNT(r2.id) as registered_count
        FROM tournaments t
        JOIN registrations r ON t.id = r.tournament_id
        LEFT JOIN users u ON t.host_id = u.id
        LEFT JOIN registrations r2 ON t.id = r2.tournament_id AND r2.status = 'registered'
        WHERE r.user_id = ? AND t.status = 'upcoming' 
              AND CONCAT(t.date, ' ', t.end_time) > NOW()
        GROUP BY t.id
        ORDER BY t.date ASC, t.start_time ASC
    ", [$userId]);

    // Get tournaments the user is hosting
    // Only show tournaments that haven't ended yet (current date/time is before tournament end)
    $myHostedTournaments = db()->fetchAll("
        SELECT t.*, 'host' as registration_status, t.created_at as registration_time,
               u.name as host_name, u.nickname as host_nickname, u.country_code as host_country,
               COUNT(r.id) as registered_count
        FROM tournaments t
        LEFT JOIN users u ON t.host_id = u.id
        LEFT JOIN registrations r ON t.id = r.tournament_id AND r.status = 'registered'
        WHERE t.host_id = ? AND t.status = 'upcoming' 
              AND CONCAT(t.date, ' ', t.end_time) > NOW()
        GROUP BY t.id
        ORDER BY t.date ASC, t.start_time ASC
    ", [$userId]);

    // Combine and sort all tournaments
    $allMyTournaments = array_merge($myRegistrations, $myHostedTournaments);

    // Remove duplicates (in case user is both host and registered)
    foreach ($allMyTournaments as $tournament) {
        $id = $tournament['id'];
        if (!isset($uniqueTournaments[$id])) {
            $uniqueTournaments[$id] = $tournament;
        } else {
            // If user is both host and registered, prioritize host status
            if ($tournament['registration_status'] === 'host') {
                $uniqueTournaments[$id] = $tournament;
            }
        }
    }

    // Sort by date
    usort($uniqueTournaments, function($a, $b) {
        return strtotime($a['date'] . ' ' . $a['start_time']) - strtotime($b['date'] . ' ' . $b['start_time']);
    });
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Upcoming Games - Padel Tournament Registration</title>
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
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        
        .title-container h1 {
            color: #2d3748;
            font-size: 48px;
            font-weight: 400;
            font-family: Georgia, serif;
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
        
        /* Tournament Cards */
        .tournament-card {
            background: white;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .tournament-description {
            margin: 16px 0;
            padding: 12px;
            background: #f9fafb;
            border-radius: 6px;
            color: #374151;
            font-size: 14px;
            font-family: Georgia, serif;
            line-height: 1.5;
            border-left: 3px solid #5a9fd4;
        }
        
        .tournament-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        
        .tournament-title {
            font-size: 22px;
            font-weight: 400;
            font-family: Georgia, serif;
        }
        
        .tournament-title a {
            color: #1a202c;
            text-decoration: none;
        }
        
        .card-share-btn {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 12px;
            color: #6b7280;
            cursor: pointer;
        }
        
        .status-badges {
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-host {
            background: #bee3f8;
            color: #2a69ac;
        }
        
        .status-registered {
            background: #c6f6d5;
            color: #22543d;
        }
        
        .status-waitlist {
            background: #fef3c7;
            color: #92400e;
        }
        
        .tournament-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #6b7280;
        }
        
        .progress-section {
            margin-bottom: 16px;
        }
        
        .progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            transition: width 0.3s ease;
        }
        
        .progress-fill.active {
            background: #5a9fd4;
        }
        
        .progress-fill.filling-fast {
            background: #f59e0b;
        }
        
        .progress-fill.full {
            background: #ef4444;
        }
        
        .view-btn {
            background: #f3f4f6;
            color: #374151;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            display: inline-block;
        }
        
        .view-btn:hover {
            background: #e5e7eb;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            margin: 4rem 0;
        }
        
        .empty-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .empty-state h2 {
            color: #4a5568;
            margin-bottom: 1rem;
            font-size: 18px;
        }
        
        .empty-state p {
            color: #718096;
            font-size: 14px;
            margin-bottom: 2rem;
        }
        
        .empty-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
        }
        
        .empty-actions .btn-primary,
        .empty-actions .btn-secondary {
            padding: 14px 28px;
            font-size: 16px;
            font-weight: 600;
            min-width: 140px;
        }
        
        .btn-primary {
            background: #5a9fd4;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }
        
        .btn-secondary {
            background: white;
            color: #5a9fd4;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            border: 1px solid #5a9fd4;
        }
        
        .share-section {
            margin: 24px 0;
            padding: 16px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        
        .share-label {
            color: #4a5568;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        .share-input-group {
            display: flex;
            gap: 8px;
        }
        
        .share-input {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            color: #4a5568;
            background: white;
            cursor: text;
        }
        
        .share-input:focus {
            outline: none;
            border-color: #5a9fd4;
            box-shadow: 0 0 0 3px rgba(90, 159, 212, 0.1);
        }
        
        .copy-btn {
            background: #111827;
            border: 1px solid #111827;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
            min-width: 100px;
            justify-content: center;
        }
        
        .copy-btn:hover {
            background: #1f2937;
            border-color: #1f2937;
        }
        
        .copy-btn.copied {
            background: #10b981;
            border-color: #10b981;
            color: white;
        }
        
        .copy-btn.copied:hover {
            background: #059669;
            border-color: #059669;
        }
        
        /* Mobile Responsive */
        @media (max-width: 767px) {
            .nav-links, .user-menu {
                display: none;
            }
            
            .mobile-menu-btn {
                display: block;
            }
            
            .btn-primary {
                padding: 6px 12px;
                font-size: 13px;
            }
            
            .title-container {
                flex-direction: column;
                align-items: center;
                text-align: center;
                gap: 16px;
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
            
            .tournament-header {
                flex-direction: column;
                gap: 12px;
            }
            
            .tournament-meta {
                grid-template-columns: 1fr;
                gap: 8px;
            }
            
            .empty-actions {
                flex-direction: column;
                align-items: center;
            }
            
            .empty-actions .btn-primary, 
            .empty-actions .btn-secondary {
                width: 100%;
                max-width: 280px;
                padding: 12px 24px;
                font-size: 16px;
            }
            
            .nav-container .btn-primary {
                width: auto;
            }
            
            .share-input-group {
                flex-direction: column;
                gap: 8px;
            }
            
            .share-input {
                width: 100%;
            }
            
            .copy-btn {
                width: 100%;
                min-width: auto;
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
                            <?php if (isAdmin()): ?>
                                <a href="admin/dashboard.php">Admin Dashboard</a>
                            <?php endif; ?>
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
                <?php if (isAdmin()): ?>
                    <a href="admin/dashboard.php">Admin Dashboard</a>
                <?php endif; ?>
                <a href="edit-account.php">My Account</a>
                <a href="logout.php" class="logout">Logout</a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Title Section -->
    <div class="title-section">
        <div class="title-container">
            <h1>My Upcoming Games</h1>
            <?php if (isLoggedIn()): ?>
                <a href="create-tournament.php" class="create-btn">+ Create Tournament</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Content Area -->
    <div class="content-area">
        <div class="content-container">
            <?php if (!isLoggedIn()):
                // Redirect to landing page for non-logged in users
                header('Location: index.php');
                exit;
            endif; ?>
            
            <?php if (empty($uniqueTournaments)): ?>
                <div class="empty-state">
                    <div class="empty-icon">🎾</div>
                    <h2>No upcoming games</h2>
                    <p>You're not registered for any tournaments yet.</p>
                    <div class="empty-actions">
                        <a href="create-tournament.php" class="btn-primary">Create Tournament</a>
                        <a href="previous-games.php" class="btn-secondary">View My Previous Games</a>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($uniqueTournaments as $tournament): ?>
                    <?php
                    $registrations = getTournamentRegistrations($tournament['id']);
                    $registeredUsers = array_values(array_filter($registrations, function($r) { return $r['status'] === 'registered'; }));
                    $registeredCount = count($registeredUsers);
                    $isUserRegistered = isUserRegistered($tournament['id'], $userId);
                    $isHost = isUserTournamentHost($tournament['id'], $userId);
                    $status = getTournamentStatus($registeredCount, $tournament['max_participants']);
                    $skillLevel = getSkillLevel($tournament['level']);
                    $progressPercentage = ($registeredCount / $tournament['max_participants']) * 100;
                    ?>
                    
                    <div class="tournament-card">
                        <div class="tournament-title">
                            <a href="tournament.php?t=<?php echo $tournament['slug']; ?>">
                                <?php echo sanitizeInput($tournament['name']); ?>
                            </a>
                        </div>
                        
                        <!-- Share Link -->
                        <div class="share-section">
                            <div class="share-label">Copy link and share with your friends</div>
                            <div class="share-input-group">
                                <input type="text" class="share-input" value="<?php echo APP_URL . '/tournament.php?t=' . $tournament['slug']; ?>" readonly id="shareUrl-<?php echo $tournament['id']; ?>">
                                <button onclick="copyTournamentShareUrl('<?php echo $tournament['id']; ?>')" class="copy-btn" id="copyBtn-<?php echo $tournament['id']; ?>">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                    </svg>
                                    <span>Copy Link</span>
                                </button>
                            </div>
                        </div>
                        
                        <?php
                        $userStatuses = [];
                        if ($isHost) $userStatuses[] = ['label' => '👑 Host', 'class' => 'status-host'];
                        if ($isUserRegistered === 'registered') $userStatuses[] = ['label' => '✅ Confirmed', 'class' => 'status-registered'];
                        if ($isUserRegistered === 'waitlist') $userStatuses[] = ['label' => '⏳ Waitlist', 'class' => 'status-waitlist'];
                        ?>
                        
                        <?php if (!empty($userStatuses)): ?>
                            <div class="status-badges">
                                <?php foreach ($userStatuses as $statusInfo): ?>
                                    <span class="status-badge <?php echo $statusInfo['class']; ?>">
                                        <?php echo $statusInfo['label']; ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($tournament['registration_status'] !== 'host'): ?>
                            <div style="margin-bottom: 16px; color: #6b7280; font-size: 14px;">
                                Hosted by <?php echo sanitizeInput(getDisplayName(['name' => $tournament['host_name'], 'nickname' => $tournament['host_nickname']])); ?> <?php echo getCountryFlag($tournament['host_country']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="tournament-meta">
                            <?php $timeInfo = formatTournamentTimeForUser($tournament, true); ?>
                            <div class="meta-item">
                                <span>📅</span>
                                <span><?php echo $timeInfo['date']; ?></span>
                            </div>
                            <div class="meta-item">
                                <span>🕐</span>
                                <span><?php echo $timeInfo['start_time']; ?> - <?php echo $timeInfo['end_time']; ?></span>
                            </div>
                            <?php if ($tournament['location']): ?>
                                <div class="meta-item">
                                    <span>📍</span>
                                    <span><?php echo sanitizeInput($tournament['location']); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="meta-item">
                                <span>⭐</span>
                                <span><?php echo ucfirst($skillLevel === 'mixed' ? 'Mixed Level' : $tournament['level']); ?></span>
                            </div>
                        </div>
                        
                        <?php if (!empty($tournament['description'])): ?>
                            <div class="tournament-description">
                                <?php echo formatDescription($tournament['description']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="progress-section">
                            <div class="progress-header">
                                <span><?php echo $status['text']; ?></span>
                                <span><?php echo $registeredCount; ?> of <?php echo $tournament['max_participants']; ?> players</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill <?php echo $status['class']; ?>" style="width: <?php echo min(100, $progressPercentage); ?>%"></div>
                            </div>
                        </div>
                        
                        <a href="tournament.php?t=<?php echo $tournament['slug']; ?>" class="view-btn">
                            View Details
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
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
            if (!e.target.closest('.dropdown')) {
                document.getElementById('dropdown').style.display = 'none';
            }
        });
        
        function copyTournamentShareUrl(tournamentId) {
            const input = document.getElementById('shareUrl-' + tournamentId);
            const button = document.getElementById('copyBtn-' + tournamentId);
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(input.value).then(() => {
                    // Change to copied state
                    button.classList.add('copied');
                    button.innerHTML = `
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20,6 9,17 4,12"></polyline>
                        </svg>
                        <span>Copied</span>
                    `;
                    
                    setTimeout(() => {
                        // Revert to original state
                        button.classList.remove('copied');
                        button.innerHTML = `
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                            </svg>
                            <span>Copy Link</span>
                        `;
                    }, 2000);
                });
            } else {
                input.select();
                input.setSelectionRange(0, 99999);
                document.execCommand('copy');
                
                // Change to copied state
                button.classList.add('copied');
                button.innerHTML = `
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20,6 9,17 4,12"></polyline>
                    </svg>
                    <span>Copied</span>
                `;
                
                setTimeout(() => {
                    // Revert to original state
                    button.classList.remove('copied');
                    button.innerHTML = `
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                        </svg>
                        <span>Copy Link</span>
                    `;
                }, 2000);
            }
        }
        
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
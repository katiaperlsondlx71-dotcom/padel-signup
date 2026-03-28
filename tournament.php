<?php
require_once 'includes/functions.php';

$pageTitle = 'Tournament Details';

// Get tournament slug from URL (fallback to ID for backward compatibility)
$tournamentSlug = sanitizeInput($_GET['t'] ?? '');
$tournamentId = intval($_GET['id'] ?? 0);

if (!$tournamentSlug && !$tournamentId) {
    showMessage('Tournament not found.', 'error');
    redirectTo('index.php');
}

// Get tournament details - try by slug first, then by ID
if ($tournamentSlug) {
    $tournament = getTournamentBySlug($tournamentSlug);
} else {
    $tournament = getTournament($tournamentId);
}

if (!$tournament) {
    showMessage('Tournament not found.', 'error');
    redirectTo('index.php');
}

// Set the tournament ID for use throughout the rest of the page
$tournamentId = $tournament['id'];

// Get registrations
$registrations = getTournamentRegistrations($tournamentId);
$registeredUsers = array_values(array_filter($registrations, function($r) { return $r['status'] === 'registered'; }));
$waitlistUsers = array_values(array_filter($registrations, function($r) { return $r['status'] === 'waitlist'; }));
$registeredCount = count($registeredUsers);
$waitlistCount = count($waitlistUsers);

$isUserRegistered = isLoggedIn() ? isUserRegistered($tournamentId, $_SESSION['user_id']) : false;
$isFull = $registeredCount >= $tournament['max_participants'];
$isHost = isLoggedIn() ? isUserTournamentHost($tournamentId, $_SESSION['user_id']) : false;
$currentUser = isLoggedIn() ? getCurrentUser() : null;
$isUserBanned = $currentUser ? $currentUser['is_banned'] : false;

// Generate shareable URL using slug
$shareUrl = APP_URL . '/tournament.php?t=' . $tournament['slug'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tournament['name']); ?> - Padel Tournament</title>
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
        }
        
        .nav-links {
            display: flex;
            gap: 32px;
        }
        
        .nav-link {
            color: #9ca3af;
            text-decoration: none;
            font-size: 15px;
            transition: color 0.2s;
        }
        
        .nav-link:hover {
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
            min-width: 150px;
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
        
        .btn-primary {
            background: #5a9fd4;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            display: inline-block;
            transition: background 0.2s;
        }
        
        .btn-primary:hover {
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
        
        .card {
            background: white;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .tournament-header {
            margin-bottom: 20px;
        }
        
        .tournament-title {
            font-size: 22px;
            font-weight: 400;
            font-family: Georgia, serif;
            color: #1a202c;
            margin-bottom: 12px;
        }
        
        .share-section {
            margin-bottom: 20px;
            padding: 16px;
            background: #f9fafb;
            border-radius: 8px;
        }
        
        .share-input {
            display: flex;
            gap: 12px;
            align-items: center;
        }
        
        .share-input input {
            flex: 1;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            background: white;
            color: #374151;
        }
        
        .share-input input:focus {
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
        
        .whatsapp-btn {
            background: #25D366;
            border-color: #25D366;
        }
        
        .whatsapp-btn:hover {
            background: #128C7E;
            border-color: #128C7E;
        }
        
        .whatsapp-btn.copied {
            background: #10b981;
            border-color: #10b981;
        }
        
        .whatsapp-btn.copied:hover {
            background: #059669;
            border-color: #059669;
        }
        
        .meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        
        .tournament-description {
            margin: 24px 0;
            padding: 20px;
            background: #f9fafb;
            border-radius: 8px;
            border-left: 4px solid #5a9fd4;
        }
        
        .description-content {
            color: #374151;
            font-size: 16px;
            font-family: Georgia, serif;
        }
        
        .players-section {
            margin: 20px 0;
        }
        
        .players-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
        }
        
        .player-tag {
            background: #f3f4f6;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 14px;
            border: 1px solid #e5e7eb;
        }
        
        .player-tag.host {
            background: #dcfce7;
            border-color: #bbf7d0;
            color: #166534;
        }
        
        .player-tag.waitlist {
            background: #fef3c7;
            border-color: #fde68a;
            color: #92400e;
        }
        
        .action-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 20px;
        }
        
        .host-actions {
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-join {
            background: #10b981;
            color: white;
        }
        
        .btn-join:hover {
            background: #059669;
        }
        
        .btn-leave {
            background: #ef4444;
            color: white;
        }
        
        .btn-leave:hover {
            background: #dc2626;
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .btn-danger:hover {
            background: #dc2626;
        }
        
        .btn-waitlist {
            background: #f59e0b;
            color: white;
        }
        
        .btn-waitlist:hover {
            background: #d97706;
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
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
            margin: 0;
        }
        
        /* Mobile Responsive */
        @media (max-width: 767px) {
            .nav-links, .user-menu {
                display: none;
            }
            
            .mobile-menu-btn {
                display: block;
            }
            
            .title-container h1 {
                font-size: 28px;
            }
            
            .meta-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
            
            .share-input {
                flex-direction: column;
                gap: 8px;
            }
            
            .share-input input {
                margin-bottom: 0;
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
            <h1><?php echo htmlspecialchars($tournament['name']); ?></h1>
        </div>
    </div>

    <!-- Content Area -->
    <div class="content-area">
        <div class="content-container">
            <!-- Main Tournament Card -->
            <div class="card">
                <!-- Tournament Header -->
                <div class="tournament-header">
                    <?php $hostData = ['name' => $tournament['host_name'], 'nickname' => $tournament['host_nickname']]; ?>
                    <div class="tournament-title">
                        Hosted by <?php echo htmlspecialchars(getDisplayName($hostData)); ?> <?php echo getCountryFlag($tournament['host_country'] ?? 'XX'); ?>
                    </div>
                </div>

                <!-- Share Section -->
                <div class="share-section">
                    <div style="font-size: 14px; color: #6b7280; margin-bottom: 8px;">Share this tournament</div>
                    <div class="share-input">
                        <input type="text" id="shareUrl" value="<?php echo $shareUrl; ?>" readonly>
                        <button onclick="copyShareUrl()" class="copy-btn" id="copyBtn">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                            </svg>
                            <span id="copyText">Copy Link</span>
                        </button>
                    </div>
                    <?php if ($isHost || isAdmin()): ?>
                        <div style="margin-top: 12px;">
                            <button onclick="copyWhatsAppFormat()" class="copy-btn whatsapp-btn" id="whatsappCopyBtn">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="white">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.485 3.515"/>
                                </svg>
                                <span id="whatsappCopyText">Copy for WhatsApp</span>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Tournament Details -->
                <div class="meta-grid">
                    <?php 
                    $timeInfo = formatTournamentTimeForUser($tournament, true);
                    // Format date with year for tournament page
                    $converted = convertTournamentTimeForUser($tournament);
                    $tournamentDate = date('M j, Y', strtotime($converted['user_date']));
                    ?>
                    <div class="meta-item">
                        <span>📅</span>
                        <span><?php echo $tournamentDate; ?></span>
                    </div>
                    <div class="meta-item">
                        <span>🕐</span>
                        <span><?php echo $timeInfo['start_time']; ?> - <?php echo $timeInfo['end_time']; ?></span>
                    </div>
                    <?php if ($tournament['location']): ?>
                        <div class="meta-item">
                            <span>📍</span>
                            <span><?php echo htmlspecialchars($tournament['location']); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="meta-item">
                        <span>⭐</span>
                        <span><?php echo htmlspecialchars($tournament['level']); ?></span>
                    </div>
                    <div class="meta-item">
                        <span>👥</span>
                        <span><?php echo $registeredCount; ?>/<?php echo $tournament['max_participants']; ?> players</span>
                    </div>
                </div>

                <!-- Tournament Description -->
                <?php if (!empty($tournament['description'])): ?>
                    <div class="tournament-description">
                        <div class="description-content">
                            <?php echo formatDescription($tournament['description']); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Players List -->
                <?php if (!empty($registeredUsers)): ?>
                    <div class="players-section">
                        <h3 style="margin-bottom: 12px;">Registered Players (<?php echo count($registeredUsers); ?>)</h3>
                        <div class="players-list">
                            <?php foreach ($registeredUsers as $index => $player): 
                                $isPlayerHost = $player['user_id'] == $tournament['host_id'];
                                $canRemovePlayer = ($isHost || isAdmin()) && !$isPlayerHost;
                            ?>
                                <div class="player-tag <?php echo $isPlayerHost ? 'host' : ''; ?>" style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                                    <span>
                                        <?php echo ($index + 1); ?>. <?php echo htmlspecialchars(getDisplayName($player)); ?>
                                        <?php if ($isPlayerHost): ?> 👑<?php endif; ?>
                                        <?php echo getCountryFlag($player['country_code']); ?>
                                    </span>
                                    <?php if ($canRemovePlayer): ?>
                                        <form method="POST" action="tournament-action.php" style="display: inline; margin: 0;">
                                            <input type="hidden" name="tournament_id" value="<?php echo $tournament['id']; ?>">
                                            <input type="hidden" name="action" value="remove_player">
                                            <input type="hidden" name="target_user_id" value="<?php echo $player['user_id']; ?>">
                                            <input type="hidden" name="return_url" value="<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">
                                            <button type="submit" 
                                                    onclick="return confirm('Remove <?php echo addslashes(htmlspecialchars(getDisplayName($player))); ?> from this tournament?')"
                                                    style="background: none; color: #6b7280; border: none; padding: 2px 4px; font-size: 16px; cursor: pointer; line-height: 1; transition: color 0.2s; font-weight: bold;"
                                                    onmouseover="this.style.color='#dc2626'" 
                                                    onmouseout="this.style.color='#6b7280'"
                                                    title="Remove player from tournament">
                                                ×
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Waitlist -->
                <?php if (!empty($waitlistUsers)): ?>
                    <div class="players-section">
                        <h3 style="margin-bottom: 12px;">Waiting List (<?php echo count($waitlistUsers); ?>)</h3>
                        <div class="players-list">
                            <?php foreach ($waitlistUsers as $index => $player): 
                                $canRemovePlayer = ($isHost || isAdmin());
                            ?>
                                <div class="player-tag waitlist" style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
                                    <span>
                                        <?php echo ($registeredCount + $index + 1); ?>. <?php echo htmlspecialchars(getDisplayName($player)); ?>
                                        <?php echo getCountryFlag($player['country_code']); ?>
                                    </span>
                                    <?php if ($canRemovePlayer): ?>
                                        <form method="POST" action="tournament-action.php" style="display: inline; margin: 0;">
                                            <input type="hidden" name="tournament_id" value="<?php echo $tournament['id']; ?>">
                                            <input type="hidden" name="action" value="remove_player">
                                            <input type="hidden" name="target_user_id" value="<?php echo $player['user_id']; ?>">
                                            <input type="hidden" name="return_url" value="<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">
                                            <button type="submit" 
                                                    onclick="return confirm('Remove <?php echo addslashes(htmlspecialchars(getDisplayName($player))); ?> from waitlist?')"
                                                    style="background: none; color: #6b7280; border: none; padding: 2px 4px; font-size: 16px; cursor: pointer; line-height: 1; transition: color 0.2s; font-weight: bold;"
                                                    onmouseover="this.style.color='#dc2626'" 
                                                    onmouseout="this.style.color='#6b7280'"
                                                    title="Remove player from waitlist">
                                                ×
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Host Actions -->
                <?php if ($isHost): ?>
                    <div class="action-buttons host-actions">
                        <a href="edit-tournament.php?id=<?php echo $tournament['id']; ?>" class="btn btn-secondary">
                            ✏️ Edit Tournament
                        </a>
                        <form method="POST" action="tournament-action.php" style="display: inline;">
                            <input type="hidden" name="action" value="cancel_tournament">
                            <input type="hidden" name="tournament_id" value="<?php echo $tournament['id']; ?>">
                            <input type="hidden" name="redirect_url" value="<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to cancel this tournament? This will notify all registered players.')">
                                ❌ Cancel Tournament
                            </button>
                        </form>
                    </div>
                <?php endif; ?>

                <!-- Registration Actions -->
                <?php if (isLoggedIn()): ?>
                    <?php if (!$isUserBanned): ?>
                        <div class="action-buttons">
                            <?php if ($isUserRegistered === 'registered'): ?>
                                <form method="POST" action="tournament-action.php">
                                    <input type="hidden" name="action" value="cancel">
                                    <input type="hidden" name="tournament_id" value="<?php echo $tournament['id']; ?>">
                                    <input type="hidden" name="redirect_url" value="<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">
                                    <button type="submit" class="btn btn-leave" onclick="return confirm('Leave this tournament?')">
                                        Leave Tournament
                                    </button>
                                </form>
                            <?php elseif ($isUserRegistered === 'waitlist'): ?>
                                <form method="POST" action="tournament-action.php">
                                    <input type="hidden" name="action" value="cancel">
                                    <input type="hidden" name="tournament_id" value="<?php echo $tournament['id']; ?>">
                                    <input type="hidden" name="redirect_url" value="<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">
                                    <button type="submit" class="btn btn-secondary" onclick="return confirm('Leave waiting list?')">
                                        Leave Waiting List
                                    </button>
                                </form>
                            <?php elseif ($isFull): ?>
                                <form method="POST" action="tournament-action.php">
                                    <input type="hidden" name="action" value="register">
                                    <input type="hidden" name="tournament_id" value="<?php echo $tournament['id']; ?>">
                                    <input type="hidden" name="redirect_url" value="<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">
                                    <button type="submit" class="btn btn-waitlist">
                                        Join Waiting List
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="POST" action="tournament-action.php">
                                    <input type="hidden" name="action" value="register">
                                    <input type="hidden" name="tournament_id" value="<?php echo $tournament['id']; ?>">
                                    <input type="hidden" name="redirect_url" value="<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">
                                    <button type="submit" class="btn btn-join">
                                        Join Tournament
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="action-buttons">
                        <a href="login.php" class="btn btn-join">Login to Join</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Scoring Section Card (separate from main card) - Admin Only -->
            <?php if (isScoringSystemEnabled() && isAdmin()): ?>
                <div class="card">
                    <?php if ($tournament['game_state'] === 'not_started'): ?>
                        <!-- Player Count and Start Game -->
                        <div style="text-align: center;">
                            <h2 style="color: #374151; margin-bottom: 20px;">👥 Number of Players</h2>
                            <div style="font-size: 48px; font-weight: bold; color: #5a9fd4; margin-bottom: 20px;">
                                <?php echo count($registeredUsers); ?>
                            </div>
                            
                            <?php if (isAdmin()): ?>
                                <form method="POST" action="game-action.php">
                                    <input type="hidden" name="action" value="start_game">
                                    <input type="hidden" name="tournament_id" value="<?php echo $tournament['id']; ?>">
                                    <input type="hidden" name="redirect_url" value="<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">
                                    <button type="submit" class="btn btn-join" style="font-size: 18px; padding: 16px 32px;" onclick="return confirm('Start the scoring system?')">
                                        🎯 Start Game
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                        
                    <?php elseif ($tournament['game_state'] === 'in_progress'): ?>
                        <!-- Live Scoring Interface -->
                        <div style="text-align: center; margin-bottom: 20px;">
                            <h2 style="color: #047857; margin-bottom: 8px;">🎯 Live Scoring</h2>
                            <div style="color: #059669; font-weight: 500;">Game in Progress</div>
                        </div>
                        
                        <!-- Score Display -->
                        <div style="background: #f9fafb; border-radius: 8px; padding: 20px; margin-bottom: 16px;">
                            <div style="display: grid; grid-template-columns: 1fr auto 1fr; gap: 16px; align-items: center; margin-bottom: 20px;">
                                <div style="text-align: center;">
                                    <div style="font-size: 18px; font-weight: 600; color: #374151; margin-bottom: 4px;">Team A</div>
                                    <div style="font-size: 32px; font-weight: bold; color: #10b981;">0</div>
                                </div>
                                <div style="text-align: center; color: #6b7280; font-weight: 500;">VS</div>
                                <div style="text-align: center;">
                                    <div style="font-size: 18px; font-weight: 600; color: #374151; margin-bottom: 4px;">Team B</div>
                                    <div style="font-size: 32px; font-weight: bold; color: #10b981;">0</div>
                                </div>
                            </div>
                            
                            <div style="text-align: center; margin-bottom: 16px;">
                                <div style="color: #6b7280; font-size: 14px; margin-bottom: 8px;">Current Set</div>
                                <div style="font-size: 20px; font-weight: 600; color: #374151;">Set 1</div>
                            </div>
                            
                            <div style="display: flex; gap: 8px; justify-content: center;">
                                <button class="btn btn-join">+1 Team A</button>
                                <button class="btn btn-join">+1 Team B</button>
                            </div>
                        </div>
                        
                        <div style="text-align: center; color: #6b7280; font-size: 12px;">
                            💡 Full scoring functionality coming soon
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function copyShareUrl() {
            const input = document.getElementById('shareUrl');
            const button = document.getElementById('copyBtn');
            const copyText = document.getElementById('copyText');
            
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
                            <span id="copyText">Copy Link</span>
                        `;
                    }, 2000);
                });
            } else {
                input.select();
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
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2 2v1"></path>
                        </svg>
                        <span id="copyText">Copy Link</span>
                    `;
                }, 2000);
            }
        }
        
        function copyWhatsAppFormat() {
            const button = document.getElementById('whatsappCopyBtn');
            const copyText = document.getElementById('whatsappCopyText');
            
            // Generate WhatsApp format
            const whatsappText = generateWhatsAppFormat();
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(whatsappText).then(() => {
                    showCopiedState(button, 'whatsappCopyText', 'Copied!');
                });
            } else {
                // Fallback for older browsers
                const textarea = document.createElement('textarea');
                textarea.value = whatsappText;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                showCopiedState(button, 'whatsappCopyText', 'Copied!');
            }
        }
        
        function generateWhatsAppFormat() {
            const tournamentData = <?php echo json_encode([
                'name' => $tournament['name'],
                'date' => date('l, F jS', strtotime($converted['user_date'])),
                'time' => $timeInfo['start_time'] . ' to ' . $timeInfo['end_time'],
                'level' => $tournament['level'],
                'fee' => $tournament['fee'] . ' THB',
                'location' => $tournament['location'],
                'description' => $tournament['description'],
                'max_participants' => $tournament['max_participants'],
                'host_name' => getDisplayName(['name' => $tournament['host_name'], 'nickname' => $tournament['host_nickname']]),
                'host_country' => getCountryFlag($tournament['host_country'] ?? 'XX'),
                'registered_users' => array_map(function($user) {
                    return [
                        'name' => getDisplayName($user),
                        'country' => getCountryFlag($user['country_code'])
                    ];
                }, $registeredUsers),
                'waitlist_users' => array_map(function($user) {
                    return [
                        'name' => getDisplayName($user),
                        'country' => getCountryFlag($user['country_code'])
                    ];
                }, $waitlistUsers)
            ]); ?>;
            
            let text = `Please don't add or remove names here use the official sign-up link for any changes. 🙏\n\n*${tournamentData.name.toUpperCase()}*\n\n`;
            text += `📅 ${tournamentData.date}, ${tournamentData.time}\n`;
            text += `🏆 ${tournamentData.description || 'Warm up 30 mins before'}\n\n`;
            text += `🎾 Level : ${tournamentData.level}\n`;
            if (tournamentData.location) {
                text += `📍 Location : ${tournamentData.location}\n`;
            }
            text += `🤷‍♂ How to register ? ${window.location.href}\n`;
            
            // Add registered players
            tournamentData.registered_users.forEach((player, index) => {
                text += `${index + 1}/ ${player.name} ${player.country}\n`;
            });
            
            // Add empty slots
            const remainingSlots = tournamentData.max_participants - tournamentData.registered_users.length;
            for (let i = 0; i < remainingSlots; i++) {
                text += `${tournamentData.registered_users.length + i + 1}/ \n`;
            }
            
            // Add waitlist if exists
            if (tournamentData.waitlist_users.length > 0) {
                text += `\nWaiting list :\n`;
                tournamentData.waitlist_users.forEach((player, index) => {
                    text += `${index + 1}/ ${player.name} ${player.country}\n`;
                });
                text += `${tournamentData.waitlist_users.length + 1}/ \n`;
            }
            
            text += `\n🔔Host : ${tournamentData.host_name} ${tournamentData.host_country}`;
            
            return text;
        }
        
        function showCopiedState(button, textElementId, message) {
            button.classList.add('copied');
            const textElement = document.getElementById(textElementId);
            const originalText = textElement.textContent;
            textElement.textContent = message;
            
            setTimeout(() => {
                button.classList.remove('copied');
                textElement.textContent = originalText;
            }, 2000);
        }
        
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
    </script>
</body>
</html>
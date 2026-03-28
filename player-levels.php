<?php
require_once 'includes/functions.php';

$pageTitle = 'Player Levels';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Padel Tournament Registration</title>
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
            padding: 32px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .levels-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .levels-table th {
            background: #f9fafb;
            padding: 16px 20px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .levels-table th:last-child {
            text-align: center;
            width: 140px;
        }
        
        .levels-table td {
            padding: 16px 20px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: top;
        }
        
        .level-number {
            font-size: 24px;
            font-weight: 700;
            color: #374151;
            width: 60px;
        }
        
        .level-description {
            color: #4b5563;
            line-height: 1.5;
            font-size: 14px;
        }
        
        .level-category {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: center;
        }
        
        .category-initiation {
            background: #dbeafe;
            color: #1d4ed8;
        }
        
        .category-initiation-intermediate {
            background: #e0e7ff;
            color: #3730a3;
        }
        
        .category-intermediate {
            background: #fef3c7;
            color: #92400e;
        }
        
        .category-intermediate-high {
            background: #fed7aa;
            color: #c2410c;
        }
        
        .category-intermediate-advanced {
            background: #fecaca;
            color: #dc2626;
        }
        
        .category-advanced {
            background: #dcfce7;
            color: #166534;
        }
        
        .category-elite {
            background: #f3e8ff;
            color: #7c3aed;
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
            
            .levels-table {
                font-size: 13px;
            }
            
            .levels-table th,
            .levels-table td {
                padding: 12px 8px;
            }
            
            .level-number {
                font-size: 20px;
                width: 40px;
            }
            
            .level-description {
                font-size: 13px;
            }
            
            .level-category {
                font-size: 10px;
                padding: 3px 8px;
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
                <a href="player-levels.php" class="nav-link active">Player Levels</a>
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
            <h1>Player Levels</h1>
        </div>
    </div>

    <!-- Content Area -->
    <div class="content-area">
        <div class="content-container">
            <div class="card">
                <table class="levels-table">
                    <thead>
                        <tr>
                            <th>Level</th>
                            <th>Description</th>
                            <th>Category</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="level-number">0</td>
                            <td class="level-description">Has never played any racket sports.</td>
                            <td><span class="level-category category-initiation">Initiation</span></td>
                        </tr>
                        <tr>
                            <td class="level-number">0.5</td>
                            <td class="level-description">No classes. Less than 6 months playing. No technique or tactics.</td>
                            <td><span class="level-category category-initiation">Initiation</span></td>
                        </tr>
                        <tr>
                            <td class="level-number">1.0</td>
                            <td class="level-description">No classes or only few. Less than 12 months playing. No technique or tactics.</td>
                            <td><span class="level-category category-initiation">Initiation</span></td>
                        </tr>
                        <tr>
                            <td class="level-number">1.5</td>
                            <td class="level-description">Few classes. A couple of games a month. Rally and return at low speed.</td>
                            <td><span class="level-category category-initiation-intermediate">Initiation Intermediate</span></td>
                        </tr>
                        <tr>
                            <td class="level-number">2.0</td>
                            <td class="level-description">Few classes. At least 1 year of play. A couple of games a month. Rally and return at low speed.</td>
                            <td><span class="level-category category-initiation-intermediate">Initiation Intermediate</span></td>
                        </tr>
                        <tr>
                            <td class="level-number">2.5</td>
                            <td class="level-description">Has almost mastered most of the strokes and controls the directions at a normal pace.</td>
                            <td><span class="level-category category-intermediate">Intermediate</span></td>
                        </tr>
                        <tr>
                            <td class="level-number">3.0</td>
                            <td class="level-description">Dominates most strokes, plays flat and drives the ball. Makes many unforced errors.</td>
                            <td><span class="level-category category-intermediate">Intermediate</span></td>
                        </tr>
                        <tr>
                            <td class="level-number">3.5</td>
                            <td class="level-description">Dominates most strokes. Can play slice forehand, slice backhand and flat. Can direct the ball correctly. Lot of unforced errors.</td>
                            <td><span class="level-category category-intermediate">Intermediate</span></td>
                        </tr>
                        <tr>
                            <td class="level-number">4.0</td>
                            <td class="level-description">Masters most strokes. Controls the directions. Is able to play slice forehand, slice backhand or flat and direct the ball. Makes a few unforced errors.</td>
                            <td><span class="level-category category-intermediate-high">Intermediate High</span></td>
                        </tr>
                        <tr>
                            <td class="level-number">4.5</td>
                            <td class="level-description">Masters the stroke. Controls the directions. Is able to play slice forehand, slice backhand or flat and direct the ball. Puts the ball at high speed but has difficulties finishing points.</td>
                            <td><span class="level-category category-intermediate-high">Intermediate High</span></td>
                        </tr>
                        <tr>
                            <td class="level-number">5.0</td>
                            <td class="level-description">Medium technique and high tactical mindset. Is ready to play matches with good pace.</td>
                            <td><span class="level-category category-intermediate-advanced">Intermediate Advanced</span></td>
                        </tr>
                        <tr>
                            <td class="level-number">5.5</td>
                            <td class="level-description">Dominates technical and tactical skills. Prepared to play matches at high pace.<br><br><strong>FOREHAND and BACKHAND:</strong> Hard hitting with control, depth and variety of shots, effective on deep lobs, use the forehand to set up the drive to the net. He can forehand and backhand on aggressive shots with fairly good consistency, good control in direction and depth on most shots. Can return difficult serves with control.<br><br><strong>ATTACKING NEAR THE NET:</strong> Can execute difficult serves with control, looking for the opponent's weak point. Keeps the pace high when the opponent plays with high speed.<br><br><strong>WALLS:</strong> Good rebounding defense, even on strong balls. Fast "bajada" lob forehand and backhand. Varies the game depending on the opponent, solid teamwork, manages the game and finds the opponent's weak point, is less mentally and physically consistent than an elite or professional player.</td>
                            <td><span class="level-category category-advanced">Advanced</span></td>
                        </tr>
                        <tr>
                            <td class="level-number">6.0</td>
                            <td class="level-description">Has all the shots of the previous levels in an exceptional way, with increased power. Is capable of playing with power and precision, looking for the opponent's weak point. Keeps the pace high when the opponent plays with high speed.</td>
                            <td><span class="level-category category-advanced">Advanced</span></td>
                        </tr>
                        <tr>
                            <td class="level-number">7.0</td>
                            <td class="level-description">Professional player. Top 30 WPT.</td>
                            <td><span class="level-category category-elite">Elite</span></td>
                        </tr>
                    </tbody>
                </table>
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
            if (!e.target.closest('.dropdown')) {
                document.getElementById('dropdown').style.display = 'none';
            }
        });
    </script>
</body>
</html>
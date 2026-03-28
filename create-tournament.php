<?php
require_once 'includes/functions.php';

requireLogin();

$pageTitle = 'Create Tournament';

$errors = [];
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle time fields - either from hidden fields or construct from hour/minute
    $startTime = sanitizeInput($_POST['start_time'] ?? '');
    $endTime = sanitizeInput($_POST['end_time'] ?? '');
    
    // Fallback: construct time from separate hour/minute fields if hidden field is empty
    if (empty($startTime)) {
        $startHour = sanitizeInput($_POST['start_hour'] ?? '');
        $startMinute = sanitizeInput($_POST['start_minute'] ?? '');
        if ($startHour !== '' && $startMinute !== '') {
            $startTime = sprintf('%02d:%02d', intval($startHour), intval($startMinute));
        }
    }
    
    if (empty($endTime)) {
        $endHour = sanitizeInput($_POST['end_hour'] ?? '');
        $endMinute = sanitizeInput($_POST['end_minute'] ?? '');
        if ($endHour !== '' && $endMinute !== '') {
            $endTime = sprintf('%02d:%02d', intval($endHour), intval($endMinute));
        }
    }
    
    $formData = [
        'name' => sanitizeInput($_POST['name'] ?? ''),
        'date' => sanitizeInput($_POST['date'] ?? ''),
        'start_time' => $startTime,
        'end_time' => $endTime,
        'level' => sanitizeInput($_POST['level'] ?? ''),
        'max_participants' => intval($_POST['max_participants'] ?? 12),
        'location' => sanitizeInput($_POST['location'] ?? ''),
        'description' => sanitizeInput($_POST['description'] ?? ''),
        'host_id' => $_SESSION['user_id']
    ];
    
    // Validation
    if (empty($formData['name'])) {
        $errors[] = 'Tournament name is required';
    }
    
    if (empty($formData['date'])) {
        $errors[] = 'Date is required';
    } elseif (strtotime($formData['date']) < strtotime('today')) {
        $errors[] = 'Date must be in the future';
    }
    
    if (empty($formData['start_time'])) {
        $errors[] = 'Start time is required';
    }
    
    if (empty($formData['end_time'])) {
        $errors[] = 'End time is required';
    } elseif (!empty($formData['start_time']) && $formData['end_time'] <= $formData['start_time']) {
        $errors[] = 'End time must be after start time';
    }
    
    if (empty($formData['level'])) {
        $errors[] = 'Level is required';
    }
    
    if ($formData['max_participants'] < 4) {
        $errors[] = 'Minimum 4 participants required';
    } elseif ($formData['max_participants'] > 50) {
        $errors[] = 'Maximum 50 participants allowed';
    }
    
    // Create tournament if no errors
    if (empty($errors)) {
        try {
            // Generate a unique slug for the tournament
            $formData['slug'] = generateTournamentSlug();
            
            $tournamentId = db()->insert('tournaments', $formData);
            
            if ($tournamentId) {
                // Send confirmation email to the host
                $currentUser = getCurrentUser();
                if ($currentUser) {
                    $emailSent = sendTournamentCreationEmail($tournamentId, $currentUser['email'], $currentUser['name']);
                    if (!$emailSent) {
                        error_log("Failed to send tournament creation email to {$currentUser['email']} for tournament {$tournamentId}");
                    }
                }
                
                showMessage('Tournament created successfully!', 'success');
                // Get the slug for redirect
                $createdTournament = getTournament($tournamentId);
                redirectTo('tournament.php?t=' . $createdTournament['slug']);
            } else {
                $errors[] = 'Failed to create tournament: No ID returned from database';
            }
        } catch (Exception $e) {
            $errors[] = 'Failed to create tournament: ' . $e->getMessage();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Tournament - Padel Tournament Registration</title>
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
            color: #374151;
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
            z-index: 2000;
        }

        .mobile-menu {
            position: absolute;
            top: 0;
            right: 0;
            width: 280px;
            height: 100%;
            background: white;
            padding: 20px;
            transform: translateX(100%);
            transition: transform 0.3s ease;
        }

        .mobile-menu.open {
            transform: translateX(0);
        }

        .mobile-close {
            position: absolute;
            top: 15px;
            right: 20px;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #6b7280;
        }

        .mobile-menu h3 {
            margin-bottom: 20px;
            color: #374151;
        }

        .mobile-menu a {
            display: block;
            padding: 12px 0;
            color: #374151;
            text-decoration: none;
            border-bottom: 1px solid #f3f4f6;
        }

        .mobile-menu a:hover {
            color: #5a9fd4;
        }

        .mobile-menu .logout {
            color: #dc2626;
            border-top: 2px solid #f3f4f6;
            margin-top: 8px;
        }
        
        .logo {
            font-size: 18px;
            font-weight: 700;
            color: #374151;
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
            font-family: 'Georgia', 'Times New Roman', serif;
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
        
        /* Form Styling */
        .form-card {
            background: white;
            border-radius: 8px;
            padding: 32px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .error-message {
            background: #fed7d7;
            color: #742a2a;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #feb2b2;
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
        .form-field select, 
        .form-field textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            background: white;
            transition: border-color 0.2s ease;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }
        
        .form-field input[type="date"] {
            -webkit-appearance: auto;
            -moz-appearance: auto;
            appearance: auto;
        }
        
        .form-field select {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 12px center;
            background-repeat: no-repeat;
            background-size: 16px;
            padding-right: 40px;
        }
        
        .form-field textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-field input:focus, 
        .form-field select:focus, 
        .form-field textarea:focus {
            outline: none;
            border-color: #5a9fd4;
            box-shadow: 0 0 0 3px rgba(90, 159, 212, 0.1);
        }
        
        .time-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
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
            
            .nav-container {
                justify-content: space-between;
            }
            .title-container {
                text-align: center;
                padding: 0 20px;
            }
            
            .title-container h1 {
                font-size: 28px;
            }
            
            .nav-container {
                justify-content: center;
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
            <div class="logo">🎾 Play Padel with Us</div>
            
            <div class="nav-links">
                <a href="player-levels.php" class="nav-link">Player Levels</a>
            </div>
            
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
        </div>
    </div>

    <!-- Mobile Menu -->
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

    <!-- Title Section -->
    <div class="title-section">
        <div class="title-container">
            <h1>Create Tournament</h1>
        </div>
    </div>

    <!-- Content Area -->
    <div class="content-area">
        <div class="content-container">
            <div class="form-card">
                <?php if (!empty($errors)): ?>
                    <div class="error-message">
                        <?php foreach ($errors as $error): ?>
                            <div><?php echo $error; ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-field">
                        <label for="name">Tournament Name</label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            required 
                            value="<?php echo htmlspecialchars($formData['name'] ?? ''); ?>"
                            placeholder="e.g., Silver Americano Registration / Early birds session"
                        >
                    </div>
                
                    <div class="form-row">
                        <div class="form-field">
                            <label for="date">Date</label>
                            <input 
                                type="date" 
                                id="date" 
                                name="date" 
                                required 
                                value="<?php echo htmlspecialchars($formData['date'] ?? ''); ?>"
                                min="<?php echo date('Y-m-d'); ?>"
                            >
                        </div>
                        
                        <div class="form-field">
                            <label for="level">Level</label>
                            <select id="level" name="level" required>
                                <option value="">Select Level</option>
                                <option value="Beginner" <?php echo ($formData['level'] ?? '') === 'Beginner' ? 'selected' : ''; ?>>Beginner</option>
                                <option value="Intermediate" <?php echo ($formData['level'] ?? '') === 'Intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                                <option value="Advanced" <?php echo ($formData['level'] ?? '') === 'Advanced' ? 'selected' : ''; ?>>Advanced</option>
                                <option value="Mixed Level" <?php echo ($formData['level'] ?? '') === 'Mixed Level' ? 'selected' : ''; ?>>Mixed Level</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-field">
                            <label for="start_time">Start Time</label>
                            <div class="time-row">
                                <select id="start_hour" name="start_hour" required>
                                    <option value="">Hour</option>
                                    <?php
                                    $currentStartTime = $formData['start_time'] ?? '';
                                    $currentStartHour = $currentStartTime ? explode(':', $currentStartTime)[0] : '';
                                    for ($h = 0; $h <= 23; $h++) {
                                        $hour = sprintf('%02d', $h);
                                        $selected = $currentStartHour === $hour ? 'selected' : '';
                                        echo "<option value=\"$hour\" $selected>$hour</option>";
                                    }
                                    ?>
                                </select>
                                <select id="start_minute" name="start_minute" required>
                                    <option value="">Min</option>
                                    <?php
                                    $currentStartMinute = $currentStartTime ? explode(':', $currentStartTime)[1] : '';
                                    for ($m = 0; $m <= 59; $m += 5) {
                                        $minute = sprintf('%02d', $m);
                                        $selected = $currentStartMinute === $minute ? 'selected' : '';
                                        echo "<option value=\"$minute\" $selected>$minute</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <input type="hidden" id="start_time" name="start_time" value="<?php echo htmlspecialchars($formData['start_time'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-field">
                            <label for="end_time">End Time</label>
                            <div class="time-row">
                                <select id="end_hour" name="end_hour" required>
                                    <option value="">Hour</option>
                                    <?php
                                    $currentEndTime = $formData['end_time'] ?? '';
                                    $currentEndHour = $currentEndTime ? explode(':', $currentEndTime)[0] : '';
                                    for ($h = 0; $h <= 23; $h++) {
                                        $hour = sprintf('%02d', $h);
                                        $selected = $currentEndHour === $hour ? 'selected' : '';
                                        echo "<option value=\"$hour\" $selected>$hour</option>";
                                    }
                                    ?>
                                </select>
                                <select id="end_minute" name="end_minute" required>
                                    <option value="">Min</option>
                                    <?php
                                    $currentEndMinute = $currentEndTime ? explode(':', $currentEndTime)[1] : '';
                                    for ($m = 0; $m <= 59; $m += 5) {
                                        $minute = sprintf('%02d', $m);
                                        $selected = $currentEndMinute === $minute ? 'selected' : '';
                                        echo "<option value=\"$minute\" $selected>$minute</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <input type="hidden" id="end_time" name="end_time" value="<?php echo htmlspecialchars($formData['end_time'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-field">
                        <label for="max_participants">Max Participants</label>
                        <input 
                            type="number" 
                            id="max_participants" 
                            name="max_participants" 
                            required 
                            min="4" 
                            max="50" 
                            value="<?php echo htmlspecialchars($formData['max_participants'] ?? 12); ?>"
                            style="max-width: 200px;"
                        >
                    </div>
                    
                    <div class="form-field">
                        <label for="location">Location (Optional)</label>
                        <input 
                            type="text" 
                            id="location" 
                            name="location" 
                            value="<?php echo htmlspecialchars($formData['location'] ?? ''); ?>"
                            placeholder="e.g., Central Padel Club Bangkok"
                        >
                    </div>
                    
                    <div class="form-field">
                        <label for="description">Description (Optional)</label>
                        <textarea 
                            id="description" 
                            name="description" 
                            placeholder="Additional information about the tournament..."
                        ><?php echo htmlspecialchars($formData['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <a href="index.php" class="btn-secondary">Cancel</a>
                        <button type="submit" class="btn-primary">Create Tournament</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Navigation functions
        function toggleDropdown() {
            const dropdown = document.getElementById('dropdown');
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }

        function openMobileMenu() {
            const overlay = document.getElementById('mobile-overlay');
            const menu = overlay.querySelector('.mobile-menu');
            overlay.style.display = 'block';
            setTimeout(() => menu.classList.add('open'), 10);
        }

        function closeMobileMenu() {
            const overlay = document.getElementById('mobile-overlay');
            const menu = overlay.querySelector('.mobile-menu');
            menu.classList.remove('open');
            setTimeout(() => overlay.style.display = 'none', 300);
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('dropdown');
            const userButton = event.target.closest('.user-button');
            
            if (!userButton && dropdown) {
                dropdown.style.display = 'none';
            }
        });

        // Function to combine hour and minute into time format
        function updateTimeField(hourId, minuteId, timeId) {
            const hour = document.getElementById(hourId).value;
            const minute = document.getElementById(minuteId).value;
            const timeField = document.getElementById(timeId);
            
            if (hour && minute) {
                timeField.value = hour + ':' + minute;
            } else {
                timeField.value = '';
            }
        }

        // Add event listeners to update hidden time fields
        document.getElementById('start_hour').addEventListener('change', function() {
            updateTimeField('start_hour', 'start_minute', 'start_time');
        });

        document.getElementById('start_minute').addEventListener('change', function() {
            updateTimeField('start_hour', 'start_minute', 'start_time');
        });

        document.getElementById('end_hour').addEventListener('change', function() {
            updateTimeField('end_hour', 'end_minute', 'end_time');
        });

        document.getElementById('end_minute').addEventListener('change', function() {
            updateTimeField('end_hour', 'end_minute', 'end_time');
        });

        // Initialize time fields on page load if values exist
        document.addEventListener('DOMContentLoaded', function() {
            updateTimeField('start_hour', 'start_minute', 'start_time');
            updateTimeField('end_hour', 'end_minute', 'end_time');
        });
    </script>
</body>
</html>
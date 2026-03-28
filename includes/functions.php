<?php
require_once 'config.php';
require_once 'database.php';

// Refresh session expiration on every page load for logged-in users
// This will be called after all functions are defined at the end of this file

// Authentication functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        // Check if we're in admin directory to use relative path
        $currentPath = $_SERVER['PHP_SELF'];
        if (strpos($currentPath, '/admin/') !== false) {
            header('Location: unauthorized.php');
        } else {
            header('Location: admin/unauthorized.php');
        }
        exit;
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $user = db()->fetch("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
    if ($user) {
        $_SESSION['user_country'] = $user['country_code']; // Store for header display
    }
    return $user;
}

function login($email, $password) {
    $user = db()->fetch("SELECT * FROM users WHERE email = ?", [$email]);
    
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['is_admin'] = $user['is_admin'];
        
        // Create session record
        $sessionId = session_id();
        $expiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
        
        db()->query("INSERT INTO sessions (id, user_id, expires_at) VALUES (?, ?, ?) 
                     ON DUPLICATE KEY UPDATE expires_at = ?", 
                    [$sessionId, $user['id'], $expiresAt, $expiresAt]);
        
        return true;
    }
    
    return false;
}

function logout() {
    $sessionId = session_id();
    db()->query("DELETE FROM sessions WHERE id = ?", [$sessionId]);
    
    // Clear remember token if it exists
    clearRememberToken();
    
    session_destroy();
    header('Location: index.php');
    exit;
}

function registerUser($name, $nickname, $email, $password, $countryCode = 'XX', $phone = '', $timezone = null) {
    try {
        // Check if email already exists
        $existingUser = db()->fetch("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existingUser) {
            return false;
        }
        
        $passwordHash = password_hash($password, HASH_ALGORITHM);
        
        $userId = db()->insert('users', [
            'name' => $name,
            'nickname' => $nickname ?: null,
            'email' => $email,
            'password_hash' => $passwordHash,
            'country_code' => $countryCode,
            'phone' => $phone,
            'timezone' => $timezone ?: DEFAULT_TIMEZONE
        ]);
        
        return $userId;
        
    } catch (PDOException $e) {
        error_log("Registration error for email {$email}: " . $e->getMessage());
        
        // Check if it's an emoji/encoding issue
        if (strpos($e->getMessage(), 'Incorrect string value') !== false) {
            error_log("Emoji encoding error detected for user registration: {$name}, nickname: {$nickname}");
            // Return a specific error code for emoji issues
            return 'emoji_error';
        }
        
        // Return false for other database errors
        return false;
    }
}

// Helper function to get display name (nickname or full name)
function getDisplayName($user) {
    if (is_array($user)) {
        return !empty($user['nickname']) ? $user['nickname'] : $user['name'];
    }
    return $user; // fallback for string input
}

// Remember me token functions
function generateRememberToken() {
    return bin2hex(random_bytes(64)); // 128 character token
}

function createRememberToken($userId) {
    try {
        // Generate secure token
        $token = generateRememberToken();
        $expiresAt = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60)); // 30 days
        
        // Clear any existing tokens for this user
        db()->query("DELETE FROM remember_tokens WHERE user_id = ?", [$userId]);
        
        // Insert new token
        db()->insert('remember_tokens', [
            'user_id' => $userId,
            'token' => $token,
            'expires_at' => $expiresAt
        ]);
        
        // Set cookie
        setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', true, true);
        
        return $token;
    } catch (Exception $e) {
        error_log("Failed to create remember token: " . $e->getMessage());
        return false;
    }
}

function validateRememberToken($token) {
    if (empty($token)) {
        return null;
    }
    
    try {
        $result = db()->fetch("
            SELECT rt.user_id, u.name, u.email, u.is_admin 
            FROM remember_tokens rt
            JOIN users u ON rt.user_id = u.id
            WHERE rt.token = ? AND rt.expires_at > NOW()
        ", [$token]);
        
        return $result;
    } catch (Exception $e) {
        error_log("Failed to validate remember token: " . $e->getMessage());
        return null;
    }
}

function loginWithRememberToken($token) {
    $tokenData = validateRememberToken($token);
    
    if (!$tokenData) {
        return false;
    }
    
    // Set session data
    $_SESSION['user_id'] = $tokenData['user_id'];
    $_SESSION['user_name'] = $tokenData['name'];
    $_SESSION['is_admin'] = $tokenData['is_admin'];
    
    // Create session record
    $sessionId = session_id();
    $expiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
    
    db()->query("INSERT INTO sessions (id, user_id, expires_at) VALUES (?, ?, ?) 
                 ON DUPLICATE KEY UPDATE expires_at = ?", 
                [$sessionId, $tokenData['user_id'], $expiresAt, $expiresAt]);
    
    // Refresh the remember token with new expiry
    createRememberToken($tokenData['user_id']);
    
    return true;
}

function clearRememberToken() {
    if (isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        
        // Delete from database
        db()->query("DELETE FROM remember_tokens WHERE token = ?", [$token]);
        
        // Clear cookie
        setcookie('remember_token', '', time() - 3600, '/', '', true, true);
    }
}

function cleanupExpiredRememberTokens() {
    try {
        $deleted = db()->query("DELETE FROM remember_tokens WHERE expires_at < NOW()");
        return $deleted->rowCount();
    } catch (Exception $e) {
        error_log("Failed to cleanup expired remember tokens: " . $e->getMessage());
        return 0;
    }
}

// Tournament functions
function generateTournamentSlug() {
    $attempts = 0;
    $maxAttempts = 10;
    
    do {
        // Generate a random 7-character slug: Letter + 4 digits + 2 letters
        $slug = chr(65 + rand(0, 25)) . // Random uppercase letter (A-Z)
                str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT) . // 4 digits with leading zeros
                chr(65 + rand(0, 25)) . // Random uppercase letter (A-Z)
                chr(97 + rand(0, 25));  // Random lowercase letter (a-z)
        
        // Check if slug already exists
        $existing = db()->fetch("SELECT id FROM tournaments WHERE slug = ?", [$slug]);
        $attempts++;
        
        if (!$existing || $attempts >= $maxAttempts) {
            break;
        }
    } while ($attempts < $maxAttempts);
    
    return $slug;
}

function getTournaments($status = 'upcoming') {
    return db()->fetchAll("
        SELECT t.*, u.name as host_name, u.nickname as host_nickname, u.country_code as host_country,
               COUNT(r.id) as registered_count
        FROM tournaments t
        LEFT JOIN users u ON t.host_id = u.id
        LEFT JOIN registrations r ON t.id = r.tournament_id AND r.status = 'registered'
        WHERE t.status = ?
        GROUP BY t.id
        ORDER BY t.date ASC, t.start_time ASC
    ", [$status]);
}

function getTournament($id) {
    return db()->fetch("
        SELECT t.*, u.name as host_name, u.nickname as host_nickname, u.country_code as host_country
        FROM tournaments t
        LEFT JOIN users u ON t.host_id = u.id
        WHERE t.id = ?
    ", [$id]);
}

function getTournamentBySlug($slug) {
    return db()->fetch("
        SELECT t.*, u.name as host_name, u.nickname as host_nickname, u.country_code as host_country
        FROM tournaments t
        LEFT JOIN users u ON t.host_id = u.id
        WHERE t.slug = ?
    ", [$slug]);
}

function getTournamentRegistrations($tournamentId) {
    return db()->fetchAll("
        SELECT r.*, u.name, u.nickname, u.country_code, u.email
        FROM registrations r
        JOIN users u ON r.user_id = u.id
        WHERE r.tournament_id = ?
        ORDER BY 
            CASE WHEN r.status = 'registered' THEN 0 ELSE 1 END,
            r.registration_time ASC
    ", [$tournamentId]);
}

function isUserRegistered($tournamentId, $userId) {
    $registration = db()->fetch("
        SELECT status FROM registrations 
        WHERE tournament_id = ? AND user_id = ?
    ", [$tournamentId, $userId]);
    
    return $registration ? $registration['status'] : false;
}

function registerForTournament($tournamentId, $userId) {
    $tournament = getTournament($tournamentId);
    if (!$tournament) {
        return false;
    }
    
    // Check if already registered
    if (isUserRegistered($tournamentId, $userId)) {
        return false;
    }
    
    // Count current registrations (with better locking to prevent race conditions)
    $registeredCount = db()->fetch("
        SELECT COUNT(*) as count 
        FROM registrations 
        WHERE tournament_id = ? AND status = 'registered'
    ", [$tournamentId])['count'];
    
    $status = ($registeredCount >= $tournament['max_participants']) ? 'waitlist' : 'registered';
    
    // Log the registration attempt for debugging
    error_log("Registration attempt - Tournament: {$tournamentId}, User: {$userId}, Current count: {$registeredCount}, Max: {$tournament['max_participants']}, Status: {$status}");
    
    db()->insert('registrations', [
        'tournament_id' => $tournamentId,
        'user_id' => $userId,
        'status' => $status
    ]);
    
    // After registration, fix any inconsistencies
    fixTournamentRegistrations($tournamentId);
    
    return $status;
}

function fixTournamentRegistrations($tournamentId) {
    $tournament = getTournament($tournamentId);
    if (!$tournament) {
        return false;
    }
    
    // Get all registrations with user details ordered by registration time
    $allRegistrations = db()->fetchAll("
        SELECT r.user_id, r.status, r.registration_time, u.email, u.name
        FROM registrations r
        JOIN users u ON r.user_id = u.id
        WHERE r.tournament_id = ?
        ORDER BY r.registration_time ASC
    ", [$tournamentId]);
    
    $maxParticipants = $tournament['max_participants'];
    $registeredCount = 0;
    
    // Fix status for each registration
    foreach ($allRegistrations as $registration) {
        $correctStatus = ($registeredCount < $maxParticipants) ? 'registered' : 'waitlist';
        
        // Update if status is incorrect
        if ($registration['status'] !== $correctStatus) {
            db()->update('registrations',
                ['status' => $correctStatus],
                'tournament_id = ? AND user_id = ?',
                [$tournamentId, $registration['user_id']]
            );
            error_log("Fixed registration status for user {$registration['user_id']} in tournament {$tournamentId}: {$registration['status']} -> {$correctStatus}");
            
            // Send promotion email if user was moved from waitlist to registered
            if ($registration['status'] === 'waitlist' && $correctStatus === 'registered') {
                $emailSent = sendWaitlistPromotionEmail($tournamentId, $registration['email'], $registration['name']);
                if ($emailSent) {
                    error_log("SUCCESS: Sent promotion email to {$registration['email']} for tournament {$tournamentId}");
                } else {
                    error_log("ERROR: Failed to send promotion email to {$registration['email']} for tournament {$tournamentId}");
                }
            }
        }
        
        if ($correctStatus === 'registered') {
            $registeredCount++;
        }
    }
    
    return true;
}

function cancelRegistration($tournamentId, $userId) {
    // Check the user's current status before deletion
    $userRegistration = db()->fetch("
        SELECT status FROM registrations 
        WHERE tournament_id = ? AND user_id = ?
    ", [$tournamentId, $userId]);
    
    $deleted = db()->delete('registrations', 'tournament_id = ? AND user_id = ?', [$tournamentId, $userId]);
    
    if ($deleted) {
        // Only run fix if a registered player left (not waitlist)
        // If a waitlist player left, no reordering is needed
        if ($userRegistration && $userRegistration['status'] === 'registered') {
            fixTournamentRegistrations($tournamentId);
        }
    }
    
    return $deleted > 0;
}

function cancelTournament($tournamentId, $userId) {
    try {
        // Verify user is the host of this tournament and get host details
        $tournament = db()->fetch("SELECT host_id, status FROM tournaments WHERE id = ?", [$tournamentId]);
        
        if (!$tournament) {
            return false; // Tournament not found
        }
        
        if ($tournament['host_id'] != $userId) {
            return false; // User is not the host
        }
        
        // Check if already cancelled
        if ($tournament['status'] === 'cancelled') {
            return true; // Already cancelled
        }
        
        // Get all registered players before cancelling
        $registrations = getTournamentRegistrations($tournamentId);
        
        // Get host details for sending confirmation email
        $host = db()->fetch("SELECT name, email FROM users WHERE id = ?", [$tournament['host_id']]);
        
        // Update tournament status to cancelled
        $updated = db()->update('tournaments', 
            ['status' => 'cancelled'], 
            'id = ?', 
            [$tournamentId]
        );
        
        if ($updated > 0) {
            $emailCount = 0;
            
            // Send cancellation emails to all registered players (both registered and waitlist)
            foreach ($registrations as $registration) {
                $emailSent = sendTournamentCancellationEmail($tournamentId, $registration['email'], $registration['name']);
                if (!$emailSent) {
                    error_log("Failed to send cancellation email to {$registration['email']} for tournament {$tournamentId}");
                } else {
                    $emailCount++;
                }
            }
            
            // Send confirmation email to the host
            if ($host && $host['email']) {
                $hostEmailSent = sendTournamentCancellationConfirmationEmail($tournamentId, $host['email'], $host['name']);
                if (!$hostEmailSent) {
                    error_log("Failed to send cancellation confirmation email to host {$host['email']} for tournament {$tournamentId}");
                } else {
                    $emailCount++;
                }
            }
            
            error_log("Tournament {$tournamentId} cancelled, sent {$emailCount} emails (" . count($registrations) . " participants + host)");
        }
        
        return $updated > 0;
    } catch (Exception $e) {
        error_log("Error cancelling tournament {$tournamentId}: " . $e->getMessage());
        return false;
    }
}

function isUserTournamentHost($tournamentId, $userId) {
    if (!$userId) return false;
    
    $tournament = db()->fetch("SELECT host_id FROM tournaments WHERE id = ?", [$tournamentId]);
    return $tournament && $tournament['host_id'] == $userId;
}

function autoMarkCompletedTournaments() {
    try {
        // Mark tournaments as completed if their end time has passed
        $now = date('Y-m-d H:i:s');
        
        $updated = db()->update('tournaments',
            ['status' => 'completed'],
            "status = 'upcoming' AND CONCAT(date, ' ', end_time) < :now",
            ['now' => $now]
        );
        
        if ($updated > 0) {
            error_log("Auto-marked {$updated} tournaments as completed");
        }
        
        return $updated;
    } catch (Exception $e) {
        error_log("Error auto-marking completed tournaments: " . $e->getMessage());
        return 0;
    }
}

// Utility functions (timezone-aware)
function formatDate($date, $timezone = null) {
    if ($timezone) {
        return formatDateTimeInUserTimezone($date . ' 00:00:00', $timezone, 'l, F jS');
    }
    return date('l, F jS', strtotime($date));
}

function formatTime($time, $timezone = null, $showTimezone = false) {
    if ($timezone) {
        $formatted = formatDateTimeInUserTimezone('2000-01-01 ' . $time, $timezone, 'H:i');
        if ($showTimezone) {
            $offset = getTimezoneOffset($timezone);
            $formatted .= " ({$offset})";
        }
        return $formatted;
    }
    return date('H:i', strtotime($time));
}

function formatDateTime($datetime, $timezone = null) {
    if ($timezone) {
        return formatDateTimeInUserTimezone($datetime, $timezone, 'F j, Y \a\t g:i A');
    }
    return date('F j, Y \a\t g:i A', strtotime($datetime));
}

function formatTournamentTimeForUser($tournament, $showOriginal = false) {
    $converted = convertTournamentTimeForUser($tournament);
    $userTimezone = getUserTimezone();
    
    $formatted = [
        'date' => formatDate($converted['user_date']),
        'start_time' => formatTime($converted['user_start_time']),
        'end_time' => formatTime($converted['user_end_time']),
        'timezone' => $userTimezone,
        'timezone_offset' => getTimezoneOffset($userTimezone)
    ];
    
    if ($showOriginal && $converted['date_changed']) {
        $formatted['original_date'] = formatDate($tournament['date']);
        $formatted['original_time'] = formatTime($tournament['start_time']) . ' - ' . formatTime($tournament['end_time']);
        $formatted['original_timezone'] = $tournament['timezone'] ?? DEFAULT_TIMEZONE;
    }
    
    return $formatted;
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function getUserInitials($name) {
    $words = explode(' ', trim($name));
    if (count($words) >= 2) {
        return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    }
    return strtoupper(substr($name, 0, 2));
}

function redirectTo($url) {
    header("Location: $url");
    exit;
}

function showMessage($message, $type = 'info') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

function getAndClearMessage() {
    $message = isset($_SESSION['message']) ? $_SESSION['message'] : null;
    $type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : 'info';
    
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
    
    return $message ? ['text' => $message, 'type' => $type] : null;
}

// Helper function to check if current page is in admin area
function isAdminPage() {
    $currentPath = $_SERVER['PHP_SELF'];
    return strpos($currentPath, '/admin/') !== false;
}

// Helper function to get current page title based on filename
function getCurrentPageTitle() {
    $filename = basename($_SERVER['PHP_SELF'], '.php');
    
    $titles = [
        'dashboard' => 'Admin Dashboard',
        'create-tournament' => 'Create Tournament',
        'manage-tournaments' => 'Manage Tournaments',
        'manage-users' => 'Manage Users',
        'index' => 'Tournaments',
        'login' => 'Login',
        'register' => 'Register'
    ];
    
    return $titles[$filename] ?? ucfirst(str_replace('-', ' ', $filename));
}

// Email functions
function sendTournamentCreationEmail($tournamentId, $hostEmail, $hostName) {
    try {
        // Get tournament details
        $tournament = getTournament($tournamentId);
        if (!$tournament) {
            return false;
        }
        
        // Get site URL
        $siteUrl = defined('APP_URL') ? APP_URL : 'http://' . $_SERVER['HTTP_HOST'];
        $tournamentUrl = $siteUrl . '/tournament.php?t=' . $tournament['slug'];
        
        // Email subject
        $subject = "Tournament Created: " . $tournament['name'];
        
        // Email body
        $body = "
Hi {$hostName},

Your tournament has been successfully created! Here are the details:

🏆 Tournament: {$tournament['name']}
📅 Date: " . formatDate($tournament['date']) . "
🕐 Time: " . formatTime($tournament['start_time']) . " - " . formatTime($tournament['end_time']) . "
👥 Max Players: {$tournament['max_participants']}
⭐ Level: " . ucfirst($tournament['level']);

        if ($tournament['location']) {
            $body .= "\n📍 Location: {$tournament['location']}";
        }
        
        if ($tournament['description']) {
            $body .= "\n\n📝 Description:\n{$tournament['description']}";
        }
        
        $body .= "

🔗 Tournament Link: {$tournamentUrl}

As the tournament host, you can:
• Share the tournament link with players
• Monitor registrations and waitlist
• Manage participants
• Join as a player yourself

Players can register by visiting the tournament link above.

Best regards,
🎾 Play Padel with Us Team
";

        // Email headers
        $headers = [
            'From: noreply@' . $_SERVER['HTTP_HOST'],
            'Reply-To: noreply@' . $_SERVER['HTTP_HOST'],
            'Content-Type: text/plain; charset=UTF-8',
            'X-Mailer: PHP/' . phpversion()
        ];
        
        // Send email
        return mail($hostEmail, $subject, $body, implode("\r\n", $headers));
        
    } catch (Exception $e) {
        error_log("Failed to send tournament creation email: " . $e->getMessage());
        return false;
    }
}

function sendTournamentRegistrationEmail($tournamentId, $userEmail, $userName, $registrationStatus) {
    try {
        error_log("DEBUG: sendTournamentRegistrationEmail called - Tournament: {$tournamentId}, Email: {$userEmail}, User: {$userName}, Status: {$registrationStatus}");
        
        // Get tournament details
        $tournament = getTournament($tournamentId);
        if (!$tournament) {
            error_log("ERROR: Tournament {$tournamentId} not found");
            return false;
        }
        
        error_log("DEBUG: Tournament found - Name: {$tournament['name']}");
        
        // Get site URL
        $siteUrl = defined('APP_URL') ? APP_URL : 'http://' . $_SERVER['HTTP_HOST'];
        $tournamentUrl = $siteUrl . '/tournament.php?t=' . $tournament['slug'];
        
        error_log("DEBUG: Site URL: {$siteUrl}, Tournament URL: {$tournamentUrl}");
        
        // Email subject and content based on registration status
        if ($registrationStatus === 'registered') {
            $subject = "✅ Registration Confirmed: " . $tournament['name'];
            $statusMessage = "You're confirmed for the tournament!";
            $emoji = "🎉";
        } else {
            $subject = "⏳ Added to Waiting List: " . $tournament['name'];
            $statusMessage = "You've been added to the waiting list. We'll notify you if a spot opens up.";
            $emoji = "⏳";
        }
        
        // Generate clean, short calendar links
        $token = md5($tournament['id'] . $tournament['slug']);
        $googleCalendarUrl = $siteUrl . '/add-to-calendar.php?t=' . $tournament['slug'] . '&token=' . $token . '&provider=google';
        $icsUrl = $siteUrl . '/add-to-calendar.php?t=' . $tournament['slug'] . '&token=' . $token . '&provider=ics';
        
        // Email body
        $body = "
Hi {$userName},

{$emoji} {$statusMessage}

🏆 Tournament: {$tournament['name']}
📅 Date: " . formatDate($tournament['date']) . "
🕐 Time: " . formatTime($tournament['start_time']) . " - " . formatTime($tournament['end_time']) . "
⭐ Level: " . ucfirst($tournament['level']);

        if ($tournament['location']) {
            $body .= "\n📍 Location: {$tournament['location']}";
        }
        
        if ($tournament['description']) {
            $body .= "\n\n📝 About this tournament:\n{$tournament['description']}";
        }
        
        $body .= "

📅 ADD TO YOUR CALENDAR:
• Google Calendar: {$googleCalendarUrl}
• Apple Calendar/Outlook: {$icsUrl}

🔗 Tournament Details: {$tournamentUrl}

You can visit the tournament page to:
• View other registered players
• Cancel your registration if needed
• Get updates about the tournament

See you on the court!

Best regards,
🎾 Play Padel with Us Team
";

        // Email headers
        $headers = [
            'From: noreply@' . $_SERVER['HTTP_HOST'],
            'Reply-To: noreply@' . $_SERVER['HTTP_HOST'],
            'Content-Type: text/plain; charset=UTF-8',
            'X-Mailer: PHP/' . phpversion()
        ];
        
        error_log("DEBUG: About to send email - To: {$userEmail}, Subject: {$subject}");
        error_log("DEBUG: Email headers: " . implode(" | ", $headers));
        
        // Send email
        $result = mail($userEmail, $subject, $body, implode("\r\n", $headers));
        
        error_log("DEBUG: mail() function result: " . ($result ? 'TRUE' : 'FALSE'));
        
        return $result;
        
    } catch (Exception $e) {
        error_log("ERROR: Exception in sendTournamentRegistrationEmail: " . $e->getMessage());
        return false;
    }
}

function sendTournamentCancellationEmail($tournamentId, $userEmail, $userName) {
    try {
        error_log("DEBUG: sendTournamentCancellationEmail called - Tournament: {$tournamentId}, Email: {$userEmail}, User: {$userName}");
        
        // Get tournament details
        $tournament = getTournament($tournamentId);
        if (!$tournament) {
            error_log("ERROR: Tournament {$tournamentId} not found for cancellation email");
            return false;
        }
        
        error_log("DEBUG: Tournament found for cancellation email - Name: {$tournament['name']}");
        
        // Get site URL
        $siteUrl = defined('APP_URL') ? APP_URL : 'http://' . $_SERVER['HTTP_HOST'];
        
        // Email subject
        $subject = "❌ Tournament Cancelled: " . $tournament['name'];
        
        // Email body
        $body = "
Hi {$userName},

We're sorry to inform you that the following tournament has been cancelled:

🏆 Tournament: {$tournament['name']}
📅 Date: " . formatDate($tournament['date']) . "
🕐 Time: " . formatTime($tournament['start_time']) . " - " . formatTime($tournament['end_time']) . "
⭐ Level: " . ucfirst($tournament['level']);

        if ($tournament['location']) {
            $body .= "\n📍 Location: {$tournament['location']}";
        }
        
        $body .= "

The tournament host has cancelled this event. If you have any questions about the cancellation, please contact the tournament organizer.

We apologize for any inconvenience this may cause.

Thank you for your understanding.

Best regards,
🎾 Play Padel with Us Team
";

        // Email headers
        $headers = [
            'From: noreply@' . $_SERVER['HTTP_HOST'],
            'Reply-To: noreply@' . $_SERVER['HTTP_HOST'],
            'Content-Type: text/plain; charset=UTF-8',
            'X-Mailer: PHP/' . phpversion()
        ];
        
        error_log("DEBUG: About to send cancellation email - To: {$userEmail}, Subject: {$subject}");
        
        // Send email
        $result = mail($userEmail, $subject, $body, implode("\r\n", $headers));
        
        error_log("DEBUG: Cancellation email mail() function result: " . ($result ? 'TRUE' : 'FALSE'));
        
        return $result;
        
    } catch (Exception $e) {
        error_log("ERROR: Exception in sendTournamentCancellationEmail: " . $e->getMessage());
        return false;
    }
}

function sendTournamentCancellationConfirmationEmail($tournamentId, $hostEmail, $hostName) {
    try {
        error_log("DEBUG: sendTournamentCancellationConfirmationEmail called - Tournament: {$tournamentId}, Email: {$hostEmail}, User: {$hostName}");
        
        // Get tournament details
        $tournament = getTournament($tournamentId);
        if (!$tournament) {
            error_log("ERROR: Tournament {$tournamentId} not found for host cancellation confirmation email");
            return false;
        }
        
        error_log("DEBUG: Tournament found for host cancellation confirmation email - Name: {$tournament['name']}");
        
        // Get site URL
        $siteUrl = defined('APP_URL') ? APP_URL : 'http://' . $_SERVER['HTTP_HOST'];
        
        // Email subject
        $subject = "✅ Tournament Cancellation Confirmed: " . $tournament['name'];
        
        // Email body
        $body = "
Hi {$hostName},

This email confirms that you have successfully cancelled your tournament:

🏆 Tournament: {$tournament['name']}
📅 Date: " . formatDate($tournament['date']) . "
🕐 Time: " . formatTime($tournament['start_time']) . " - " . formatTime($tournament['end_time']) . "
⭐ Level: " . ucfirst($tournament['level']);

        if ($tournament['location']) {
            $body .= "\n📍 Location: {$tournament['location']}";
        }
        
        $body .= "

✅ Cancellation Status: CONFIRMED

All registered participants have been automatically notified of the cancellation via email.

If you need to create a new tournament in the future, you can do so anytime through your account:

🔗 Create New Tournament: {$siteUrl}/create-tournament.php

Thank you for using our platform to organize padel tournaments.

Best regards,
🎾 Play Padel with Us Team
";

        // Email headers
        $headers = [
            'From: noreply@' . $_SERVER['HTTP_HOST'],
            'Reply-To: noreply@' . $_SERVER['HTTP_HOST'],
            'Content-Type: text/plain; charset=UTF-8',
            'X-Mailer: PHP/' . phpversion()
        ];
        
        error_log("DEBUG: About to send host cancellation confirmation email - To: {$hostEmail}, Subject: {$subject}");
        
        // Send email
        $result = mail($hostEmail, $subject, $body, implode("\r\n", $headers));
        
        error_log("DEBUG: Host cancellation confirmation email mail() function result: " . ($result ? 'TRUE' : 'FALSE'));
        
        return $result;
        
    } catch (Exception $e) {
        error_log("ERROR: Exception in sendTournamentCancellationConfirmationEmail: " . $e->getMessage());
        return false;
    }
}

function sendWaitlistPromotionEmail($tournamentId, $userEmail, $userName) {
    try {
        error_log("DEBUG: sendWaitlistPromotionEmail called - Tournament: {$tournamentId}, Email: {$userEmail}, User: {$userName}");
        
        // Get tournament details
        $tournament = getTournament($tournamentId);
        if (!$tournament) {
            error_log("ERROR: Tournament {$tournamentId} not found for promotion email");
            return false;
        }
        
        error_log("DEBUG: Tournament found for promotion email - Name: {$tournament['name']}");
        
        // Get site URL
        $siteUrl = defined('APP_URL') ? APP_URL : 'http://' . $_SERVER['HTTP_HOST'];
        $tournamentUrl = $siteUrl . '/tournament.php?t=' . $tournament['slug'];
        
        // Email subject
        $subject = "🎉 You're In! Promoted from Waiting List: " . $tournament['name'];
        
        // Generate clean, short calendar links
        $token = md5($tournament['id'] . $tournament['slug']);
        $googleCalendarUrl = $siteUrl . '/add-to-calendar.php?t=' . $tournament['slug'] . '&token=' . $token . '&provider=google';
        $icsUrl = $siteUrl . '/add-to-calendar.php?t=' . $tournament['slug'] . '&token=' . $token . '&provider=ics';
        
        // Email body
        $body = "
Hi {$userName},

Great news! A spot has opened up and you've been promoted from the waiting list:

🎉 You're now CONFIRMED for this tournament!

🏆 Tournament: {$tournament['name']}
📅 Date: " . formatDate($tournament['date']) . "
🕐 Time: " . formatTime($tournament['start_time']) . " - " . formatTime($tournament['end_time']) . "
⭐ Level: " . ucfirst($tournament['level']);

        if ($tournament['location']) {
            $body .= "\n📍 Location: {$tournament['location']}";
        }
        
        if ($tournament['description']) {
            $body .= "\n\n📝 About this tournament:\n{$tournament['description']}";
        }
        
        $body .= "

📅 ADD TO YOUR CALENDAR:
• Google Calendar: {$googleCalendarUrl}
• Apple Calendar/Outlook: {$icsUrl}

🔗 Tournament Details: {$tournamentUrl}

You can visit the tournament page to:
• View other confirmed players
• Get updates about the tournament
• Cancel if you can no longer attend (please do this ASAP so we can offer the spot to someone else)

See you on the court!

Best regards,
🎾 Play Padel with Us Team
";

        // Email headers
        $headers = [
            'From: noreply@' . $_SERVER['HTTP_HOST'],
            'Reply-To: noreply@' . $_SERVER['HTTP_HOST'],
            'Content-Type: text/plain; charset=UTF-8',
            'X-Mailer: PHP/' . phpversion()
        ];
        
        error_log("DEBUG: About to send promotion email - To: {$userEmail}, Subject: {$subject}");
        
        // Send email
        $result = mail($userEmail, $subject, $body, implode("\r\n", $headers));
        
        error_log("DEBUG: Promotion email mail() function result: " . ($result ? 'TRUE' : 'FALSE'));
        
        return $result;
        
    } catch (Exception $e) {
        error_log("ERROR: Exception in sendWaitlistPromotionEmail: " . $e->getMessage());
        return false;
    }
}

// Timezone Management Functions
function getCommonTimezones() {
    return [
        // Americas
        'America/New_York' => 'Eastern Time (US)',
        'America/Chicago' => 'Central Time (US)',
        'America/Denver' => 'Mountain Time (US)',
        'America/Los_Angeles' => 'Pacific Time (US)',
        'America/Toronto' => 'Toronto',
        'America/Vancouver' => 'Vancouver',
        'America/Mexico_City' => 'Mexico City',
        'America/Sao_Paulo' => 'São Paulo',
        'America/Buenos_Aires' => 'Buenos Aires',
        
        // Europe
        'Europe/London' => 'London',
        'Europe/Paris' => 'Paris',
        'Europe/Berlin' => 'Berlin',
        'Europe/Rome' => 'Rome',
        'Europe/Madrid' => 'Madrid',
        'Europe/Amsterdam' => 'Amsterdam',
        'Europe/Stockholm' => 'Stockholm',
        'Europe/Warsaw' => 'Warsaw',
        'Europe/Moscow' => 'Moscow',
        
        // Asia Pacific
        'Asia/Tokyo' => 'Tokyo',
        'Asia/Shanghai' => 'Shanghai',
        'Asia/Hong_Kong' => 'Hong Kong',
        'Asia/Singapore' => 'Singapore',
        'Asia/Bangkok' => 'Bangkok',
        'Asia/Jakarta' => 'Jakarta',
        'Asia/Manila' => 'Manila',
        'Asia/Dubai' => 'Dubai',
        'Asia/Kolkata' => 'Mumbai/Delhi',
        'Australia/Sydney' => 'Sydney',
        'Australia/Melbourne' => 'Melbourne',
        'Pacific/Auckland' => 'Auckland',
        
        // Others
        'Africa/Cairo' => 'Cairo',
        'Africa/Lagos' => 'Lagos',
        'Africa/Johannesburg' => 'Johannesburg'
    ];
}

function getUserTimezone($userId = null) {
    if ($userId === null && !isLoggedIn()) {
        return DEFAULT_TIMEZONE;
    }
    
    $userId = $userId ?? $_SESSION['user_id'];
    $user = db()->fetch("SELECT timezone FROM users WHERE id = ?", [$userId]);
    
    return $user ? ($user['timezone'] ?? DEFAULT_TIMEZONE) : DEFAULT_TIMEZONE;
}

function convertTimeToUserTimezone($datetime, $fromTimezone, $toTimezone = null) {
    if ($toTimezone === null) {
        $toTimezone = getUserTimezone();
    }
    
    try {
        $dt = new DateTime($datetime, new DateTimeZone($fromTimezone));
        $dt->setTimezone(new DateTimeZone($toTimezone));
        return $dt;
    } catch (Exception $e) {
        error_log("Timezone conversion error: " . $e->getMessage());
        return new DateTime($datetime);
    }
}

function formatDateTimeInUserTimezone($datetime, $timezone = null, $format = 'Y-m-d H:i') {
    $userTimezone = $timezone ?? getUserTimezone();
    
    try {
        $dt = new DateTime($datetime, new DateTimeZone(DEFAULT_TIMEZONE));
        $dt->setTimezone(new DateTimeZone($userTimezone));
        return $dt->format($format);
    } catch (Exception $e) {
        error_log("Date formatting error: " . $e->getMessage());
        return date($format, strtotime($datetime));
    }
}

function convertTournamentTimeForUser($tournament, $userTimezone = null) {
    $userTimezone = $userTimezone ?? getUserTimezone();
    $tournamentTimezone = $tournament['timezone'] ?? DEFAULT_TIMEZONE;
    
    if ($userTimezone === $tournamentTimezone) {
        // No conversion needed, but still set user fields
        $tournament['user_date'] = $tournament['date'];
        $tournament['user_start_time'] = $tournament['start_time'];
        $tournament['user_end_time'] = $tournament['end_time'];
        $tournament['user_timezone'] = $userTimezone;
        $tournament['original_timezone'] = $tournamentTimezone;
        $tournament['date_changed'] = false;
        return $tournament;
    }
    
    try {
        // Convert date and times
        $dateTime = $tournament['date'] . ' ' . $tournament['start_time'];
        $endDateTime = $tournament['date'] . ' ' . $tournament['end_time'];
        
        $startDt = convertTimeToUserTimezone($dateTime, $tournamentTimezone, $userTimezone);
        $endDt = convertTimeToUserTimezone($endDateTime, $tournamentTimezone, $userTimezone);
        
        $tournament['user_date'] = $startDt->format('Y-m-d');
        $tournament['user_start_time'] = $startDt->format('H:i');
        $tournament['user_end_time'] = $endDt->format('H:i');
        $tournament['user_timezone'] = $userTimezone;
        $tournament['original_timezone'] = $tournamentTimezone;
        
        // Check if date changed due to timezone conversion
        $tournament['date_changed'] = ($tournament['date'] !== $tournament['user_date']);
        
        return $tournament;
    } catch (Exception $e) {
        error_log("Tournament timezone conversion error: " . $e->getMessage());
        // Fallback: set user fields to original values
        $tournament['user_date'] = $tournament['date'];
        $tournament['user_start_time'] = $tournament['start_time'];
        $tournament['user_end_time'] = $tournament['end_time'];
        $tournament['user_timezone'] = $userTimezone;
        $tournament['original_timezone'] = $tournamentTimezone;
        $tournament['date_changed'] = false;
        return $tournament;
    }
}

function getTimezoneOffset($timezone) {
    try {
        $dt = new DateTime('now', new DateTimeZone($timezone));
        return $dt->format('P'); // Returns offset like +07:00
    } catch (Exception $e) {
        return '+00:00';
    }
}

// Improved email delivery function
function sendImprovedEmail($to, $subject, $body, $isHtml = false) {
    try {
        // Create proper email headers for better deliverability
        $headers = [
            'From: ' . EMAIL_FROM_NAME . ' <' . EMAIL_NOREPLY . '>',
            'Reply-To: ' . EMAIL_REPLY_TO,
            'Return-Path: ' . EMAIL_NOREPLY,
            'Content-Type: ' . ($isHtml ? 'text/html' : 'text/plain') . '; charset=UTF-8',
            'X-Mailer: ' . str_replace(' ', '', EMAIL_FROM_NAME) . '/1.0',
            'Message-ID: <' . uniqid() . '.' . time() . '@' . EMAIL_FROM_DOMAIN . '>',
            'Date: ' . date('r'),
            'X-Priority: 3',
            'MIME-Version: 1.0',
            'X-Originating-IP: [' . ($_SERVER['SERVER_ADDR'] ?? 'unknown') . ']',
            'List-Unsubscribe-Post: List-Unsubscribe=One-Click',
            'List-Unsubscribe: <mailto:unsubscribe@' . EMAIL_FROM_DOMAIN . '>'
        ];
        
        // Add proper text encoding
        $subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        
        error_log("Sending email to: {$to} with subject: {$subject}");
        
        return mail($to, $subject, $body, implode("\r\n", $headers));
        
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return false;
    }
}

// Welcome email function
function sendWelcomeEmail($email, $name) {
    try {
        $subject = 'Welcome to ' . APP_NAME . ' - Account Created Successfully!';
        
        $siteUrl = defined('APP_URL') ? APP_URL : 'http://' . $_SERVER['HTTP_HOST'];
        
        $body = "Hi {$name},

🎾 Welcome to the Play Padel with Us community!

Your account has been created successfully and you're now ready to join padel tournaments in your area.

Here's what you can do next:
• Register for tournaments that match your skill level
• Accept invitations from other members to play a tournament

Visit your dashboard: {$siteUrl}

If you have any questions, feel free to reach out to us at " . EMAIL_REPLY_TO . "

See you on the court!

Best regards,
The " . APP_NAME . " Team

P.S. Don't forget to check your account settings to ensure your timezone and country are correct for the best tournament experience.";

        error_log("DEBUG: Sending welcome email to {$email}");
        
        return sendImprovedEmail($email, $subject, $body);
        
    } catch (Exception $e) {
        error_log("Failed to send welcome email: " . $e->getMessage());
        return false;
    }
}

// Password reset functions
function sendPasswordResetEmail($email, $name, $resetUrl) {
    try {
        $subject = 'Password Reset Request - ' . APP_NAME;
        
        $body = "Hi {$name},

You requested a password reset for your " . APP_NAME . " account.

Click the link below to reset your password:
{$resetUrl}

This link will expire in 1 hour for security reasons.

If you didn't request this password reset, please ignore this email - your account is still secure.

Best regards,
The " . APP_NAME . " Team";

        error_log("DEBUG: Sending password reset email to {$email}");
        
        return sendImprovedEmail($email, $subject, $body);
        
    } catch (Exception $e) {
        error_log("Failed to send password reset email: " . $e->getMessage());
        return false;
    }
}

function validateResetToken($token) {
    if (empty($token)) {
        return null;
    }
    
    // Find valid token that hasn't expired or been used
    $reset = db()->fetch("
        SELECT prt.*, u.email, u.name 
        FROM password_reset_tokens prt
        JOIN users u ON prt.user_id = u.id
        WHERE prt.token = ? 
        AND prt.expires_at > NOW() 
        AND prt.used_at IS NULL
    ", [$token]);
    
    return $reset;
}

function useResetToken($token, $newPassword) {
    try {
        db()->query("BEGIN");
        
        // Validate token
        $reset = validateResetToken($token);
        if (!$reset) {
            db()->query("ROLLBACK");
            return false;
        }
        
        // Hash new password
        $passwordHash = password_hash($newPassword, HASH_ALGORITHM);
        
        // Update user password
        $updated = db()->update('users', 
            ['password_hash' => $passwordHash], 
            'id = ?', 
            [$reset['user_id']]
        );
        
        if ($updated === 0) {
            db()->query("ROLLBACK");
            return false;
        }
        
        // Mark token as used
        db()->update('password_reset_tokens',
            ['used_at' => date('Y-m-d H:i:s')],
            'token = ?',
            [$token]
        );
        
        db()->query("COMMIT");
        return true;
        
    } catch (Exception $e) {
        db()->query("ROLLBACK");
        error_log("Password reset error: " . $e->getMessage());
        return false;
    }
}

function cleanupExpiredResetTokens() {
    try {
        // Delete tokens older than 24 hours
        $deleted = db()->query("DELETE FROM password_reset_tokens WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        return $deleted->rowCount();
    } catch (Exception $e) {
        error_log("Cleanup expired tokens error: " . $e->getMessage());
        return 0;
    }
}

function formatDescription($description) {
    if (empty($description)) {
        return '';
    }
    
    // Data is already sanitized when stored, just format line breaks
    $formatted = nl2br(trim($description));
    
    return $formatted;
}

function refreshSessionExpiration() {
    if (!isLoggedIn()) {
        return false;
    }
    
    try {
        $sessionId = session_id();
        $newExpiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
        
        // Update session expiration in database
        $updated = db()->update('sessions', 
            ['expires_at' => $newExpiresAt],
            'id = ?',
            [$sessionId]
        );
        
        return $updated > 0;
    } catch (Exception $e) {
        error_log("Failed to refresh session expiration: " . $e->getMessage());
        return false;
    }
}

// Settings management functions
function getSetting($key, $default = null) {
    try {
        $result = db()->fetch("SELECT setting_value FROM settings WHERE setting_key = ?", [$key]);
        return $result ? $result['setting_value'] : $default;
    } catch (Exception $e) {
        error_log("Failed to get setting $key: " . $e->getMessage());
        return $default;
    }
}

function setSetting($key, $value, $description = null) {
    try {
        // Try to update first
        $updated = db()->query("UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?", [$value, $key])->rowCount();
        
        // If no row was updated, insert new setting
        if ($updated === 0) {
            db()->query("INSERT INTO settings (setting_key, setting_value, description) VALUES (?, ?, ?)", [$key, $value, $description]);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Failed to set setting $key: " . $e->getMessage());
        return false;
    }
}

function isScoringSystemEnabled() {
    return getSetting('scoring_system_enabled', '0') === '1';
}

// Game state management functions
function startTournamentGame($tournamentId) {
    try {
        $updated = db()->query("UPDATE tournaments SET game_state = 'in_progress' WHERE id = ?", [$tournamentId]);
        return $updated->rowCount() > 0;
    } catch (Exception $e) {
        error_log("Failed to start tournament game: " . $e->getMessage());
        return false;
    }
}

function endTournamentGame($tournamentId) {
    try {
        $updated = db()->query("UPDATE tournaments SET game_state = 'completed' WHERE id = ?", [$tournamentId]);
        return $updated->rowCount() > 0;
    } catch (Exception $e) {
        error_log("Failed to end tournament game: " . $e->getMessage());
        return false;
    }
}

function resetTournamentGame($tournamentId) {
    try {
        $updated = db()->query("UPDATE tournaments SET game_state = 'not_started' WHERE id = ?", [$tournamentId]);
        return $updated->rowCount() > 0;
    } catch (Exception $e) {
        error_log("Failed to reset tournament game: " . $e->getMessage());
        return false;
    }
}

function updateTournament($tournamentId, $data, $hostId) {
    try {
        // Verify user is the host of this tournament
        $tournament = db()->fetch("SELECT host_id, max_participants FROM tournaments WHERE id = ?", [$tournamentId]);
        
        if (!$tournament) {
            return ['success' => false, 'error' => 'Tournament not found'];
        }
        
        if ($tournament['host_id'] != $hostId) {
            return ['success' => false, 'error' => 'You are not authorized to edit this tournament'];
        }
        
        // Check if players are registered
        $registeredCount = db()->fetch("
            SELECT COUNT(*) as count 
            FROM registrations 
            WHERE tournament_id = ? AND status = 'registered'
        ", [$tournamentId])['count'];
        
        // If players are registered, prevent changing max_participants
        if ($registeredCount > 0 && isset($data['max_participants']) && $data['max_participants'] != $tournament['max_participants']) {
            return ['success' => false, 'error' => 'Cannot change maximum participants after players have registered'];
        }
        
        // Build update query dynamically
        $allowedFields = ['name', 'date', 'start_time', 'end_time', 'level', 'max_participants', 'location', 'description'];
        $updateData = [];
        $placeholders = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
                $placeholders[] = "$field = ?";
            }
        }
        
        if (empty($updateData)) {
            return ['success' => false, 'error' => 'No valid fields to update'];
        }
        
        // Add updated_at timestamp
        $updateData['updated_at'] = date('Y-m-d H:i:s');
        $placeholders[] = "updated_at = ?";
        
        $sql = "UPDATE tournaments SET " . implode(', ', $placeholders) . " WHERE id = ?";
        $values = array_values($updateData);
        $values[] = $tournamentId;
        
        $updated = db()->query($sql, $values);
        
        if ($updated->rowCount() > 0) {
            return ['success' => true, 'message' => 'Tournament updated successfully'];
        } else {
            return ['success' => false, 'error' => 'No changes were made'];
        }
        
    } catch (Exception $e) {
        error_log("Failed to update tournament: " . $e->getMessage());
        return ['success' => false, 'error' => 'An error occurred while updating the tournament'];
    }
}

function deleteTournament($tournamentId) {
    try {
        // Validate tournament ID
        if (!$tournamentId || !is_numeric($tournamentId)) {
            error_log("Invalid tournament ID provided for deletion: " . var_export($tournamentId, true));
            return false;
        }
        
        // Check if tournament exists before attempting to delete
        $tournament = db()->fetch("SELECT id, name FROM tournaments WHERE id = ?", [$tournamentId]);
        if (!$tournament) {
            error_log("Attempted to delete non-existent tournament with ID: " . $tournamentId);
            return false;
        }
        
        // Start transaction to ensure data consistency
        db()->getConnection()->beginTransaction();
        
        // Delete the tournament (CASCADE will handle registrations automatically)
        $deleted = db()->query("DELETE FROM tournaments WHERE id = ?", [$tournamentId]);
        
        if ($deleted->rowCount() > 0) {
            db()->getConnection()->commit();
            error_log("Successfully deleted tournament: " . $tournament['name'] . " (ID: " . $tournamentId . ")");
            return true;
        } else {
            db()->getConnection()->rollback();
            error_log("No rows affected when deleting tournament ID: " . $tournamentId);
            return false;
        }
    } catch (Exception $e) {
        // Rollback transaction if active
        try {
            db()->getConnection()->rollback();
        } catch (Exception $rollbackEx) {
            error_log("Failed to rollback transaction: " . $rollbackEx->getMessage());
        }
        
        error_log("Failed to delete tournament (ID: " . $tournamentId . "): " . $e->getMessage());
        return false;
    }
}

// Calendar integration functions
function generateICSFile($tournament, $userTimezone = null) {
    try {
        $userTimezone = $userTimezone ?? getUserTimezone();
        $convertedTournament = convertTournamentTimeForUser($tournament, $userTimezone);
        
        // Create DateTime objects for ICS formatting
        $startDateTime = new DateTime($convertedTournament['user_date'] . ' ' . $convertedTournament['user_start_time'], new DateTimeZone($userTimezone));
        $endDateTime = new DateTime($convertedTournament['user_date'] . ' ' . $convertedTournament['user_end_time'], new DateTimeZone($userTimezone));
        
        // Format for ICS (UTC format)
        $startDateTime->setTimezone(new DateTimeZone('UTC'));
        $endDateTime->setTimezone(new DateTimeZone('UTC'));
        
        $startDateISO = $startDateTime->format('Ymd\THis\Z');
        $endDateISO = $endDateTime->format('Ymd\THis\Z');
        $createdDate = date('Ymd\THis\Z');
        
        // Create unique identifier
        $uid = 'tournament-' . $tournament['id'] . '-' . $tournament['slug'] . '@' . $_SERVER['HTTP_HOST'];
        
        // Build location string
        $location = '';
        if (!empty($tournament['location'])) {
            $location = str_replace([',', ';', '\n'], ['\,', '\;', '\\n'], $tournament['location']);
        }
        
        // Build description
        $description = "Tournament: " . $tournament['name'] . "\\n";
        $description .= "Level: " . ucfirst($tournament['level']) . "\\n";
        $description .= "Max Participants: " . $tournament['max_participants'] . "\\n";
        if (!empty($tournament['description'])) {
            $description .= "\\nDescription: " . str_replace(['\n', '\r'], '\\n', strip_tags($tournament['description']));
        }
        
        // Host information
        $hostName = getDisplayName($tournament);
        if (isset($tournament['host_name'])) {
            $hostName = getDisplayName(['name' => $tournament['host_name'], 'nickname' => $tournament['host_nickname'] ?? '']);
        }
        $description .= "\\nHost: " . $hostName;
        
        // Tournament URL
        $siteUrl = defined('APP_URL') ? APP_URL : 'http://' . $_SERVER['HTTP_HOST'];
        $tournamentUrl = $siteUrl . '/tournament.php?t=' . $tournament['slug'];
        $description .= "\\nTournament Details: " . $tournamentUrl;
        
        // Create ICS content
        $icsContent = "BEGIN:VCALENDAR\r\n";
        $icsContent .= "VERSION:2.0\r\n";
        $icsContent .= "PRODID:-//Play Padel with Us//Tournament Registration//EN\r\n";
        $icsContent .= "CALSCALE:GREGORIAN\r\n";
        $icsContent .= "METHOD:PUBLISH\r\n";
        $icsContent .= "BEGIN:VEVENT\r\n";
        $icsContent .= "UID:" . $uid . "\r\n";
        $icsContent .= "DTSTAMP:" . $createdDate . "\r\n";
        $icsContent .= "DTSTART:" . $startDateISO . "\r\n";
        $icsContent .= "DTEND:" . $endDateISO . "\r\n";
        $icsContent .= "SUMMARY:" . str_replace([',', ';', '\n'], ['\,', '\;', '\\n'], $tournament['name']) . "\r\n";
        if ($location) {
            $icsContent .= "LOCATION:" . $location . "\r\n";
        }
        $icsContent .= "DESCRIPTION:" . $description . "\r\n";
        $icsContent .= "STATUS:CONFIRMED\r\n";
        $icsContent .= "CATEGORIES:Sports,Padel,Tournament\r\n";
        $icsContent .= "END:VEVENT\r\n";
        $icsContent .= "END:VCALENDAR\r\n";
        
        return $icsContent;
        
    } catch (Exception $e) {
        error_log("Failed to generate ICS file: " . $e->getMessage());
        return false;
    }
}

function generateGoogleCalendarLink($tournament, $userTimezone = null) {
    try {
        $userTimezone = $userTimezone ?? getUserTimezone();
        $convertedTournament = convertTournamentTimeForUser($tournament, $userTimezone);
        
        // Create DateTime objects for Google Calendar formatting
        $startDateTime = new DateTime($convertedTournament['user_date'] . ' ' . $convertedTournament['user_start_time'], new DateTimeZone($userTimezone));
        $endDateTime = new DateTime($convertedTournament['user_date'] . ' ' . $convertedTournament['user_end_time'], new DateTimeZone($userTimezone));
        
        // Format for Google Calendar (YYYYmmddTHHMMSS/YYYYmmddTHHMMSS in the event timezone)
        $startDateGoogle = $startDateTime->format('Ymd\THis');
        $endDateGoogle = $endDateTime->format('Ymd\THis');
        
        // Build event details
        $title = $tournament['name'];
        
        // Build description for Google Calendar
        $details = "Tournament: " . $tournament['name'] . "\n";
        $details .= "Level: " . ucfirst($tournament['level']) . "\n";
        $details .= "Max Participants: " . $tournament['max_participants'] . "\n";
        if (!empty($tournament['description'])) {
            $details .= "\nDescription: " . strip_tags($tournament['description']) . "\n";
        }
        
        // Host information
        $hostName = getDisplayName($tournament);
        if (isset($tournament['host_name'])) {
            $hostName = getDisplayName(['name' => $tournament['host_name'], 'nickname' => $tournament['host_nickname'] ?? '']);
        }
        $details .= "\nHost: " . $hostName;
        
        // Tournament URL
        $siteUrl = defined('APP_URL') ? APP_URL : 'http://' . $_SERVER['HTTP_HOST'];
        $tournamentUrl = $siteUrl . '/tournament.php?t=' . $tournament['slug'];
        $details .= "\nTournament Details: " . $tournamentUrl;
        
        // Location
        $location = $tournament['location'] ?? '';
        
        // Build Google Calendar URL
        $googleUrl = 'https://calendar.google.com/calendar/render?action=TEMPLATE';
        $googleUrl .= '&text=' . urlencode($title);
        $googleUrl .= '&dates=' . $startDateGoogle . '/' . $endDateGoogle;
        if ($location) {
            $googleUrl .= '&location=' . urlencode($location);
        }
        $googleUrl .= '&details=' . urlencode($details);
        $googleUrl .= '&ctz=' . urlencode($userTimezone);
        
        return $googleUrl;
        
    } catch (Exception $e) {
        error_log("Failed to generate Google Calendar link: " . $e->getMessage());
        return false;
    }
}

function createICSDownloadLink($tournament, $userTimezone = null) {
    try {
        // Create a clean filename
        $filename = 'tournament-' . $tournament['slug'] . '.ics';
        
        // Generate the ICS content
        $icsContent = generateICSFile($tournament, $userTimezone);
        if (!$icsContent) {
            return false;
        }
        
        // Create base64 encoded data URI
        $base64Data = base64_encode($icsContent);
        $dataUri = 'data:text/calendar;base64,' . $base64Data;
        
        return [
            'url' => $dataUri,
            'filename' => $filename,
            'content' => $icsContent
        ];
        
    } catch (Exception $e) {
        error_log("Failed to create ICS download link: " . $e->getMessage());
        return false;
    }
}

function sendEmailWithICSAttachment($to, $subject, $htmlBody, $tournament, $userTimezone = null) {
    try {
        // Generate ICS file content
        $icsContent = generateICSFile($tournament, $userTimezone);
        if (!$icsContent) {
            error_log("Failed to generate ICS content for email attachment");
            return false;
        }
        
        // Create filename
        $icsFilename = 'tournament-' . $tournament['slug'] . '.ics';
        
        // Create boundary for multipart email
        $boundary = uniqid('boundary_');
        
        // Create multipart email headers
        $headers = [
            'From: noreply@' . $_SERVER['HTTP_HOST'],
            'Reply-To: noreply@' . $_SERVER['HTTP_HOST'],
            'MIME-Version: 1.0',
            'Content-Type: multipart/mixed; boundary="' . $boundary . '"',
            'X-Mailer: PHP/' . phpversion()
        ];
        
        // Create email body with HTML content and ICS attachment
        $emailBody = "--{$boundary}\r\n";
        $emailBody .= "Content-Type: text/html; charset=UTF-8\r\n";
        $emailBody .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $emailBody .= $htmlBody . "\r\n\r\n";
        
        // Add ICS attachment
        $emailBody .= "--{$boundary}\r\n";
        $emailBody .= "Content-Type: text/calendar; charset=UTF-8; name=\"{$icsFilename}\"\r\n";
        $emailBody .= "Content-Transfer-Encoding: base64\r\n";
        $emailBody .= "Content-Disposition: attachment; filename=\"{$icsFilename}\"\r\n\r\n";
        $emailBody .= chunk_split(base64_encode($icsContent)) . "\r\n";
        $emailBody .= "--{$boundary}--\r\n";
        
        // Send email
        $result = mail($to, $subject, $emailBody, implode("\r\n", $headers));
        
        if ($result) {
            error_log("Email with ICS attachment sent successfully to {$to}");
        } else {
            error_log("Failed to send email with ICS attachment to {$to}");
        }
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Error sending email with ICS attachment: " . $e->getMessage());
        return false;
    }
}

// Check for remember me token if user is not logged in
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    loginWithRememberToken($_COOKIE['remember_token']);
}

// Automatically refresh session expiration for logged-in users on every page load
if (isset($_SESSION['user_id'])) {
    refreshSessionExpiration();
    
    // Occasionally cleanup expired tokens (1% chance per page load)
    if (rand(1, 100) === 1) {
        cleanupExpiredRememberTokens();
    }
}
?>
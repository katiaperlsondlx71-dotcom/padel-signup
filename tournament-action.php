<?php
require_once 'includes/functions.php';

// Require login
requireLogin();

$action = $_POST['action'] ?? '';
$tournamentId = intval($_POST['tournament_id'] ?? 0);
$userId = $_SESSION['user_id'];
$redirectUrl = urldecode($_POST['redirect_url'] ?? 'index.php');

// Debug the redirect URL
error_log("Tournament Action - Action: {$action}, Original: {$_POST['redirect_url']}, Decoded: {$redirectUrl}");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo($redirectUrl);
}

if (!$tournamentId) {
    showMessage('Invalid tournament specified.', 'error');
    redirectTo($redirectUrl);
}

// Verify tournament exists
$tournament = getTournament($tournamentId);
if (!$tournament) {
    showMessage('Tournament not found.', 'error');
    redirectTo($redirectUrl);
}

switch ($action) {
    case 'register':
        // Debug info
        error_log("Register request - Tournament ID: {$tournamentId}, User ID: {$userId}");
        
        // Check if user is banned
        $currentUser = getCurrentUser();
        if ($currentUser && $currentUser['is_banned']) {
            showMessage('You are not able to join tournaments at this time.', 'error');
            break;
        }
        
        $status = registerForTournament($tournamentId, $userId);
        error_log("Registration result: " . ($status ? $status : 'FALSE'));
        
        if ($status === 'registered') {
            showMessage('🎉 Registration successful! You\'re confirmed for the tournament.', 'success');
            
            // Send registration confirmation email
            $currentUser = getCurrentUser();
            error_log("DEBUG: Registration successful for user ID {$userId}, attempting to send email");
            
            if ($currentUser) {
                error_log("DEBUG: Current user found - Email: {$currentUser['email']}, Name: {$currentUser['name']}");
                
                $emailSent = sendTournamentRegistrationEmail($tournamentId, $currentUser['email'], $currentUser['name'], 'registered');
                
                if ($emailSent) {
                    error_log("SUCCESS: Registration confirmation email sent to {$currentUser['email']} for tournament {$tournamentId}");
                } else {
                    error_log("ERROR: Failed to send registration confirmation email to {$currentUser['email']} for tournament {$tournamentId}");
                }
            } else {
                error_log("ERROR: getCurrentUser() returned null for user ID {$userId}");
            }
        } elseif ($status === 'waitlist') {
            showMessage('⏳ Tournament is full, but you\'ve been added to the waiting list.', 'warning');
            
            // Send waitlist confirmation email
            $currentUser = getCurrentUser();
            error_log("DEBUG: Waitlist registration for user ID {$userId}, attempting to send email");
            
            if ($currentUser) {
                error_log("DEBUG: Current user found - Email: {$currentUser['email']}, Name: {$currentUser['name']}");
                
                $emailSent = sendTournamentRegistrationEmail($tournamentId, $currentUser['email'], $currentUser['name'], 'waitlist');
                
                if ($emailSent) {
                    error_log("SUCCESS: Waitlist confirmation email sent to {$currentUser['email']} for tournament {$tournamentId}");
                } else {
                    error_log("ERROR: Failed to send waitlist confirmation email to {$currentUser['email']} for tournament {$tournamentId}");
                }
            } else {
                error_log("ERROR: getCurrentUser() returned null for user ID {$userId}");
            }
        } else {
            // More specific error handling
            $existingRegistration = isUserRegistered($tournamentId, $userId);
            if ($existingRegistration) {
                showMessage('You are already registered for this tournament.', 'error');
            } else {
                showMessage('Registration failed. Please try again or contact support.', 'error');
            }
        }
        break;
        
    case 'cancel':
        if (cancelRegistration($tournamentId, $userId)) {
            showMessage('Your registration has been cancelled.', 'info');
        } else {
            showMessage('Failed to cancel registration.', 'error');
        }
        break;
        
    case 'cancel_tournament':
        // Debug info
        error_log("Cancel tournament request - Tournament ID: {$tournamentId}, User ID: {$userId}");
        
        $result = cancelTournament($tournamentId, $userId);
        error_log("Cancel tournament result: " . ($result ? 'SUCCESS' : 'FAILED'));
        
        if ($result) {
            showMessage('Tournament has been cancelled successfully.', 'success');
            redirectTo('index.php'); // Always redirect to home after cancelling tournament
        } else {
            // Check if user is the host
            $tournament = getTournament($tournamentId);
            if ($tournament && $tournament['host_id'] != $userId) {
                showMessage('Failed to cancel tournament. You can only cancel tournaments you created.', 'error');
            } else if (!$tournament) {
                showMessage('Tournament not found.', 'error');
            } else {
                showMessage('Failed to cancel tournament. Database error occurred.', 'error');
            }
        }
        break;
        
    case 'remove_player':
        $targetUserId = intval($_POST['target_user_id'] ?? 0);
        
        if (!$targetUserId) {
            showMessage('Invalid player specified.', 'error');
            break;
        }
        
        // Check if current user is admin or tournament host
        $isHost = isUserTournamentHost($tournamentId, $userId);
        $isAdmin = isAdmin();
        
        if (!$isHost && !$isAdmin) {
            showMessage('You do not have permission to remove players from this tournament.', 'error');
            break;
        }
        
        // Cannot remove the tournament host
        if ($targetUserId == $tournament['host_id']) {
            showMessage('Cannot remove the tournament host.', 'error');
            break;
        }
        
        // Get player name for confirmation message
        $targetUser = db()->fetch("SELECT name, nickname FROM users WHERE id = ?", [$targetUserId]);
        $playerName = $targetUser ? getDisplayName($targetUser) : 'Player';
        
        // Remove the player
        if (cancelRegistration($tournamentId, $targetUserId)) {
            showMessage("{$playerName} has been removed from the tournament.", 'success');
        } else {
            showMessage("Failed to remove {$playerName} from the tournament.", 'error');
        }
        break;
        
    default:
        showMessage('Invalid action specified.', 'error');
        break;
}

redirectTo($redirectUrl);
?>
<?php
require_once 'includes/functions.php';

// Require admin access for game actions
if (!isLoggedIn() || !isAdmin()) {
    showMessage('Unauthorized access.', 'error');
    redirectTo('index.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('index.php');
}

$action = $_POST['action'] ?? '';
$tournamentId = intval($_POST['tournament_id'] ?? 0);
$redirectUrl = urldecode($_POST['redirect_url'] ?? 'index.php');

if (!$tournamentId) {
    showMessage('Invalid tournament ID.', 'error');
    redirectTo($redirectUrl);
}

// Verify tournament exists
$tournament = getTournament($tournamentId);
if (!$tournament) {
    showMessage('Tournament not found.', 'error');
    redirectTo($redirectUrl);
}

switch ($action) {
    case 'start_game':
        if (startTournamentGame($tournamentId)) {
            showMessage('Game started successfully!', 'success');
        } else {
            showMessage('Failed to start game.', 'error');
        }
        break;
        
    case 'end_game':
        if (endTournamentGame($tournamentId)) {
            showMessage('Game ended successfully!', 'success');
        } else {
            showMessage('Failed to end game.', 'error');
        }
        break;
        
    case 'reset_game':
        if (resetTournamentGame($tournamentId)) {
            showMessage('Game reset successfully!', 'success');
        } else {
            showMessage('Failed to reset game.', 'error');
        }
        break;
        
    default:
        showMessage('Invalid action.', 'error');
        break;
}

redirectTo($redirectUrl);
?>
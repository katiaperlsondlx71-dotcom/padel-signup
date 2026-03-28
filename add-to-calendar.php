<?php
require_once 'includes/functions.php';

// Get tournament slug and token from URL parameters
$tournamentSlug = $_GET['t'] ?? '';
$token = $_GET['token'] ?? '';
$provider = $_GET['provider'] ?? 'google';

if (empty($tournamentSlug) || empty($token)) {
    http_response_code(400);
    die('Invalid request parameters.');
}

// Get tournament by slug
$tournament = getTournamentBySlug($tournamentSlug);
if (!$tournament) {
    http_response_code(404);
    die('Tournament not found.');
}

// Verify token (simple security check)
$expectedToken = md5($tournament['id'] . $tournament['slug']);
if ($token !== $expectedToken) {
    http_response_code(403);
    die('Invalid access token.');
}

// Get user timezone (default if user not logged in)
$userTimezone = isLoggedIn() ? getUserTimezone() : DEFAULT_TIMEZONE;

// Handle different calendar providers
switch ($provider) {
    case 'google':
        $calendarUrl = generateGoogleCalendarLink($tournament, $userTimezone);
        if ($calendarUrl) {
            header('Location: ' . $calendarUrl);
            exit;
        }
        break;
        
    case 'ics':
        // Redirect to ICS download
        $icsUrl = 'download-ics.php?t=' . urlencode($tournamentSlug) . '&token=' . urlencode($token);
        header('Location: ' . $icsUrl);
        exit;
        break;
        
    default:
        http_response_code(400);
        die('Unsupported calendar provider.');
}

http_response_code(500);
die('Failed to generate calendar link.');
?>
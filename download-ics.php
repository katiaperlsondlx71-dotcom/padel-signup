<?php
require_once 'includes/functions.php';

// Get tournament slug and token from URL parameters
$tournamentSlug = $_GET['t'] ?? '';
$token = $_GET['token'] ?? '';

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

// Generate ICS content
$icsContent = generateICSFile($tournament, $userTimezone);
if (!$icsContent) {
    http_response_code(500);
    die('Failed to generate calendar file.');
}

// Create filename
$filename = 'tournament-' . $tournament['slug'] . '.ics';

// Set headers for file download
header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($icsContent));
header('Cache-Control: no-cache, must-revalidate');
header('Expires: 0');
header('Pragma: no-cache');

// Output the ICS content
echo $icsContent;
exit;
?>
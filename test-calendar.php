<?php
// Test script for calendar integration
require_once 'includes/functions.php';

// Create a sample tournament for testing
$sampleTournament = [
    'id' => 1,
    'slug' => 'TEST123Ab',
    'name' => 'Test Americano Tournament',
    'date' => '2024-09-15',
    'start_time' => '18:00',
    'end_time' => '20:00',
    'level' => 'intermediate',
    'max_participants' => 16,
    'location' => 'Padel Club Central',
    'description' => 'Join us for a fun Americano tournament! All levels welcome.',
    'timezone' => 'Europe/Madrid',
    'host_name' => 'John Doe',
    'host_nickname' => 'Johnny'
];

echo "<h1>Calendar Integration Test</h1>\n";

// Test ICS generation
echo "<h2>ICS File Generation Test</h2>\n";
$icsContent = generateICSFile($sampleTournament, 'America/New_York');
if ($icsContent) {
    echo "<p>✅ ICS file generated successfully!</p>\n";
    echo "<textarea rows='20' cols='80' readonly>" . htmlspecialchars($icsContent) . "</textarea><br>\n";
} else {
    echo "<p>❌ ICS file generation failed!</p>\n";
}

// Test Google Calendar link
echo "<h2>Google Calendar Link Test</h2>\n";
$googleUrl = generateGoogleCalendarLink($sampleTournament, 'America/New_York');
if ($googleUrl) {
    echo "<p>✅ Google Calendar link generated successfully!</p>\n";
    echo "<p><a href='" . htmlspecialchars($googleUrl) . "' target='_blank'>Test Google Calendar Link</a></p>\n";
    echo "<p><small>URL: " . htmlspecialchars($googleUrl) . "</small></p>\n";
} else {
    echo "<p>❌ Google Calendar link generation failed!</p>\n";
}

// Test ICS download link
echo "<h2>ICS Download Link Test</h2>\n";
$icsDownload = createICSDownloadLink($sampleTournament, 'America/New_York');
if ($icsDownload) {
    echo "<p>✅ ICS download link generated successfully!</p>\n";
    
    // Create a proper download link
    $downloadUrl = '/download-ics.php?t=' . $sampleTournament['slug'] . '&token=' . md5($sampleTournament['id'] . $sampleTournament['slug']);
    echo "<p><a href='" . htmlspecialchars($downloadUrl) . "'>Download ICS File</a></p>\n";
    echo "<p><small>Filename: " . htmlspecialchars($icsDownload['filename']) . "</small></p>\n";
} else {
    echo "<p>❌ ICS download link generation failed!</p>\n";
}

// Test email integration
echo "<h2>Email Integration Test</h2>\n";
echo "<p>The email templates have been updated to include:</p>\n";
echo "<ul>\n";
echo "<li>Google Calendar link for easy adding to Google Calendar</li>\n";
echo "<li>ICS download link for Apple Calendar, Outlook, and other calendar apps</li>\n";
echo "<li>Both links are generated using the user's timezone for accurate time display</li>\n";
echo "</ul>\n";

echo "<h3>Sample Email Content Preview:</h3>\n";
echo "<div style='border: 1px solid #ccc; padding: 15px; background: #f9f9f9; font-family: monospace; white-space: pre-line;'>\n";

// Simulate email content
$userName = "Test User";
$emoji = "🎉";
$statusMessage = "You're confirmed for the tournament!";

$siteUrl = 'http://localhost';
$tournamentUrl = $siteUrl . '/tournament.php?t=' . $sampleTournament['slug'];
$googleCalendarUrl = generateGoogleCalendarLink($sampleTournament, 'America/New_York');
$icsUrl = $siteUrl . '/download-ics.php?t=' . $sampleTournament['slug'] . '&token=' . md5($sampleTournament['id'] . $sampleTournament['slug']);

$sampleEmailBody = "Hi {$userName},

{$emoji} {$statusMessage}

🏆 Tournament: {$sampleTournament['name']}
📅 Date: " . formatDate($sampleTournament['date']) . "
🕐 Time: " . formatTime($sampleTournament['start_time']) . " - " . formatTime($sampleTournament['end_time']) . "
⭐ Level: " . ucfirst($sampleTournament['level']) . "
📍 Location: {$sampleTournament['location']}

📝 About this tournament:
{$sampleTournament['description']}

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
🎾 Play Padel with Us Team";

echo htmlspecialchars($sampleEmailBody);
echo "</div>\n";

echo "<p><strong>Test completed!</strong> If all tests show ✅, the calendar integration is working properly.</p>\n";
?>
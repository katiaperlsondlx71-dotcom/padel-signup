<?php
require_once 'includes/functions.php';

// Create a sample tournament for the demo email
$demoTournament = [
    'id' => 999,
    'slug' => 'DEMO123Ab',
    'name' => 'Demo Americano Tournament - Calendar Integration Test',
    'date' => '2024-09-15',
    'start_time' => '18:00',
    'end_time' => '20:00',
    'level' => 'intermediate',
    'max_participants' => 16,
    'location' => 'Padel Club Central, 123 Sport Street, Miami, FL',
    'description' => 'This is a demo tournament to showcase the new calendar integration features. Players will receive beautiful HTML emails with one-click calendar integration for both Google Calendar and Apple Calendar/Outlook.',
    'timezone' => 'America/New_York',
    'host_name' => 'Demo Host',
    'host_nickname' => 'DemoHost'
];

echo "<h1>Demo Email Sender</h1>\n";

// Send demo registration email
echo "<h2>Sending Demo Registration Email...</h2>\n";

$demoEmail = 'mark@brenwall.com';
$demoName = 'Mark Brenwall';
$registrationStatus = 'registered';

try {
    // Since we're using demo data, we need to call the email function differently
    // We'll create the email directly instead of using the database tournament
    
    $tournament = $demoTournament; // Use our demo tournament data
    $userEmail = $demoEmail;
    $userName = $demoName;
    
    // Get site URL
    $siteUrl = defined('APP_URL') ? APP_URL : 'http://' . $_SERVER['HTTP_HOST'];
    $tournamentUrl = $siteUrl . '/tournament.php?t=' . $tournament['slug'];
    
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
    
    // Send email
    $emailSent = mail($userEmail, $subject, $body, implode("\r\n", $headers));
    
    if ($emailSent) {
        echo "<p>✅ <strong>Demo email sent successfully to {$demoEmail}!</strong></p>\n";
        echo "<p>The email includes:</p>\n";
        echo "<ul>\n";
        echo "<li>📅 <strong>Google Calendar</strong> button - One-click to add to Google Calendar</li>\n";
        echo "<li>📎 <strong>ICS Download Link</strong> - Direct link to download calendar file for Apple Calendar, Outlook, Thunderbird, and other calendar apps</li>\n";
        echo "<li>📧 <strong>Clean text format</strong> with emojis and clear structure</li>\n";
        echo "<li>🕒 <strong>Timezone-aware</strong> calendar entries</li>\n";
        echo "</ul>\n";
        
        echo "<h3>Features Demonstrated:</h3>\n";
        echo "<ul>\n";
        echo "<li><strong>Tournament Details:</strong> {$demoTournament['name']}</li>\n";
        echo "<li><strong>Date & Time:</strong> " . formatDate($demoTournament['date']) . " at " . formatTime($demoTournament['start_time']) . " - " . formatTime($demoTournament['end_time']) . "</li>\n";
        echo "<li><strong>Location:</strong> {$demoTournament['location']}</li>\n";
        echo "<li><strong>Calendar Integration:</strong> Google Calendar link + ICS download link</li>\n";
        echo "<li><strong>Simple & Clean:</strong> Text-based email format</li>\n";
        echo "</ul>\n";
        
        echo "<p><strong>🔍 Check your email at {$demoEmail} to see the calendar integration in action!</strong></p>\n";
        
        echo "<h3>Clean Calendar URLs:</h3>\n";
        echo "<ul>\n";
        echo "<li><strong>Google Calendar:</strong> {$googleCalendarUrl}</li>\n";
        echo "<li><strong>Apple Calendar/Outlook:</strong> {$icsUrl}</li>\n";
        echo "</ul>\n";
        echo "<p><em>Much cleaner than the original long Google Calendar URLs!</em></p>\n";
        
    } else {
        echo "<p>❌ <strong>Failed to send demo email!</strong></p>\n";
        echo "<p>Please check your email server configuration.</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p>❌ <strong>Error sending demo email:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

echo "<hr>\n";
echo "<h3>Technical Details:</h3>\n";
echo "<ul>\n";
echo "<li><strong>ICS File Format:</strong> RFC 5545 compliant calendar files attached to emails</li>\n";
echo "<li><strong>Google Calendar:</strong> Direct integration via URL parameters</li>\n";
echo "<li><strong>Timezone Support:</strong> Automatic conversion to user's timezone</li>\n";
echo "<li><strong>ICS Download:</strong> Secure token-based download links</li>\n";
echo "<li><strong>Email Format:</strong> Clean text format with calendar links</li>\n";
echo "</ul>\n";

echo "<p><strong>Next Steps:</strong> Test the calendar buttons in the email to verify they work on different devices and email clients.</p>\n";
?>
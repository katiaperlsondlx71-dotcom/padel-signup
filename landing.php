<?php
require_once 'includes/functions.php';
$pageTitle = 'Play Padel with Us - Tournament Management Made Simple';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/modern-style.css">
    <style>
        .landing-hero {
            background: linear-gradient(135deg, #4299e1 0%, #2b6cb0 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
        }
        .landing-hero h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 20px;
            line-height: 1.2;
        }
        .landing-hero p {
            font-size: 1.25rem;
            opacity: 0.95;
            max-width: 600px;
            margin: 0 auto 30px;
            line-height: 1.6;
        }
        .landing-cta {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .landing-cta .btn {
            padding: 14px 32px;
            font-size: 1.1rem;
            font-weight: 600;
        }
        .landing-section {
            padding: 60px 0;
            background: #f8fafc;
        }
        .landing-section:nth-child(even) {
            background: white;
        }
        .landing-section h2 {
            font-size: 2rem;
            text-align: center;
            margin-bottom: 40px;
            color: #1a202c;
        }
        .landing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .landing-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.07);
            border: 1px solid #e2e8f0;
            transition: transform 0.2s;
        }
        .landing-card:hover {
            transform: translateY(-4px);
        }
        .landing-card h3 {
            font-size: 1.25rem;
            margin-bottom: 12px;
            color: #2b6cb0;
        }
        .landing-card p {
            color: #4a5568;
            line-height: 1.6;
        }
        .landing-card-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        .landing-testimonial {
            background: linear-gradient(135deg, #2b6cb0 0%, #1a202c 100%);
            color: white;
            padding: 60px 0;
            text-align: center;
        }
        .landing-testimonial blockquote {
            font-size: 1.5rem;
            font-style: italic;
            max-width: 700px;
            margin: 0 auto;
            line-height: 1.6;
        }
        .landing-testimonial cite {
            display: block;
            margin-top: 20px;
            font-size: 1rem;
            opacity: 0.9;
        }
        .landing-cta-secondary {
            text-align: center;
            padding: 60px 0;
            background: #f8fafc;
        }
        .landing-cta-secondary h2 {
            margin-bottom: 15px;
        }
        .landing-cta-secondary p {
            color: #4a5568;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <header class="header" style="background: white; border-bottom: 1px solid #e2e8f0;">
        <div class="container">
            <div class="header-content">
                <a href="landing.php" class="logo">🎾 Play Padel with Us</a>
                <nav>
                    <ul class="nav-menu">
                        <li><a href="#features">Features</a></li>
                        <li><a href="#how-it-works">How It Works</a></li>
                        <li><a href="#testimonials">Testimonials</a></li>
                    </ul>
                </nav>
                <div class="user-menu">
                    <a href="login.php" class="btn btn-secondary btn-sm" style="color: #2d3748;">Login</a>
                    <a href="register.php" class="btn btn-primary btn-sm">Get Started</a>
                </div>
            </div>
        </div>
    </header>

    <div class="landing-hero">
        <div class="container">
            <h1>Run Your Padel Tournaments Without the Headache</h1>
            <p>The easiest way for club owners to create tournaments, manage players, and keep everyone informed. Save hours every week and never miss a no-show again.</p>
            <div class="landing-cta">
                <a href="register.php" class="btn btn-primary">Start Free</a>
                <a href="#how-it-works" class="btn btn-secondary" style="background: rgba(255,255,255,0.2); border: 2px solid white; color: white;">See How It Works</a>
            </div>
        </div>
    </div>

    <div class="landing-section" id="features">
        <div class="container">
            <h2>Everything You Need to Run Successful Tournaments</h2>
            <div class="landing-grid">
                <div class="landing-card">
                    <div class="landing-card-icon">📅</div>
                    <h3>Easy Scheduling</h3>
                    <p>Create tournaments in seconds. Set date, time, location, max players, and skill level — done.</p>
                </div>
                <div class="landing-card">
                    <div class="landing-card-icon">👥</div>
                    <h3>Automatic Registrations</h3>
                    <p>Players register themselves. No more manual sign-ups or chasing payments.</p>
                </div>
                <div class="landing-card">
                    <div class="landing-card-icon">📧</div>
                    <h3>Never Forget a Reminder</h3>
                    <p>Players get automatic email reminders before each tournament. No-shows become a thing of the past.</p>
                </div>
                <div class="landing-card">
                    <div class="landing-card-icon">⚠️</div>
                    <h3>Handle No-Shows</h3>
                    <p>Automatically move waitlisted players into the tournament when someone drops out.</p>
                </div>
                <div class="landing-card">
                    <div class="landing-card-icon">📱</div>
                    <h3>Mobile Ready</h3>
                    <p>Tournament pages work perfectly on phones. Players can register from anywhere.</p>
                </div>
                <div class="landing-card">
                    <div class="landing-card-icon">🎉</div>
                    <h3>Build Your Community</h3>
                    <p>Players see upcoming tournaments, register instantly, and keep coming back to your club.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="landing-section" id="how-it-works">
        <div class="container">
            <h2>How It Works</h2>
            <div class="landing-grid">
                <div class="landing-card">
                    <div class="landing-card-icon">1️⃣</div>
                    <h3>Create Your Tournament</h3>
                    <p>Fill in the details — name, date, time, location, and max players. Takes less than a minute.</p>
                </div>
                <div class="landing-card">
                    <div class="landing-card-icon">2️⃣</div>
                    <h3>Share the Link</h3>
                    <p>Share your tournament link with players via WhatsApp, email, or social media. It's that simple.</p>
                </div>
                <div class="landing-card">
                    <div class="landing-card-icon">3️⃣</div>
                    <h3>Watch Players Register</h3>
                    <p>Players see the tournament, register themselves, and get confirmation. No work required from you.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="landing-testimonial" id="testimonials">
        <div class="container">
            <blockquote>"Managing our padel tournaments used to be a nightmare — chasing players for replies, manually tracking who was registered, and sending reminders. Now everything happens automatically. It saves me hours every tournament and players actually show up because they get reminders."</blockquote>
            <cite>— Club Manager, Thailand</cite>
        </div>
    </div>

    <div class="landing-cta-secondary">
        <div class="container">
            <h2>Ready to Simplify Your Tournaments?</h2>
            <p>Join hundreds of padel clubs already using our platform. Free to start, no credit card required.</p>
            <a href="register.php" class="btn btn-primary">Create Your First Tournament</a>
        </div>
    </div>

    <footer style="background: #1a202c; color: white; padding: 40px 0;">
        <div class="container" style="text-align: center;">
            <p style="opacity: 0.7;">&copy; <?php echo date('Y'); ?> Play Padel with Us. Built for the padel community.</p>
        </div>
    </footer>
</body>
</html>
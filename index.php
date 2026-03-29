<?php
require_once 'includes/functions.php';
$pageTitle = 'Play Padel with Us - Simple Tournament Management';
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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #1a202c;
            line-height: 1.6;
            background: #fff;
        }
        
        .hero {
            background: linear-gradient(135deg, #059669 0%, #0891b2 100%);
            color: white;
            padding: 80px 20px;
            text-align: center;
            min-height: 70vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .hero-content {
            max-width: 700px;
        }
        
        .hero h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 20px;
            line-height: 1.2;
        }
        
        .hero p {
            font-size: 1.25rem;
            opacity: 0.95;
            margin-bottom: 30px;
            max-width: 550px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .hero-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .hero .btn {
            padding: 14px 28px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .hero .btn-primary {
            background: white;
            color: #059669;
        }
        
        .hero .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .hero .btn-secondary {
            background: transparent;
            border: 2px solid white;
            color: white;
        }
        
        .hero .btn-secondary:hover {
            background: rgba(255,255,255,0.1);
        }
        
        .section {
            padding: 80px 20px;
            max-width: 1100px;
            margin: 0 auto;
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 50px;
        }
        
        .section-header h2 {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: #1a202c;
        }
        
        .section-header p {
            font-size: 1.1rem;
            color: #64748b;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
        }
        
        .feature-card {
            background: #f8fafc;
            padding: 30px;
            border-radius: 12px;
            transition: all 0.2s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        }
        
        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        
        .feature-card h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: #1a202c;
        }
        
        .feature-card p {
            color: #64748b;
            font-size: 0.95rem;
        }
        
        .how-it-works {
            background: #f8fafc;
        }
        
        .steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            text-align: center;
        }
        
        .step-number {
            width: 50px;
            height: 50px;
            background: #059669;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            font-weight: 700;
            margin: 0 auto 20px;
        }
        
        .step h3 {
            font-size: 1.1rem;
            margin-bottom: 10px;
        }
        
        .step p {
            color: #64748b;
            font-size: 0.95rem;
        }
        
        .cta {
            background: linear-gradient(135deg, #059669 0%, #0891b2 100%);
            color: white;
            text-align: center;
            padding: 80px 20px;
        }
        
        .cta h2 {
            font-size: 2rem;
            margin-bottom: 15px;
        }
        
        .cta p {
            font-size: 1.1rem;
            opacity: 0.95;
            margin-bottom: 30px;
        }
        
        .cta-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .cta .btn {
            padding: 14px 28px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .cta .btn-primary {
            background: white;
            color: #059669;
        }
        
        .cta .btn-primary:hover {
            transform: translateY(-2px);
        }
        
        .cta .btn-secondary {
            background: transparent;
            border: 2px solid white;
            color: white;
        }
        
        footer {
            background: #1a202c;
            color: white;
            padding: 40px 20px;
            text-align: center;
        }
        
        footer p {
            opacity: 0.7;
            font-size: 0.9rem;
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .hero {
                padding: 60px 20px 40px;
                min-height: auto;
            }
            
            .hero h1 {
                font-size: 1.8rem;
            }
            
            .hero p {
                font-size: 1rem;
            }
            
            .hero-buttons {
                flex-direction: column;
                width: 100%;
            }
            
            .hero .btn {
                width: 100%;
                text-align: center;
            }
            
            .section {
                padding: 50px 20px;
            }
            
            .section-header h2 {
                font-size: 1.5rem;
            }
            
            .features {
                grid-template-columns: 1fr;
            }
            
            .steps {
                gap: 30px;
            }
            
            .cta h2 {
                font-size: 1.5rem;
            }
            
            .cta-buttons {
                flex-direction: column;
                width: 100%;
            }
            
            .cta .btn {
                width: 100%;
                text-align: center;
            }
        }
        
        @media (max-width: 480px) {
            .hero h1 {
                font-size: 1.5rem;
            }
            
            .hero p {
                font-size: 0.95rem;
            }
            
            .feature-card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="hero">
        <div class="hero-content">
            <h1>Stop Scheduling Your Padel Tournaments Manually</h1>
            <p>The simple way for club managers to create tournaments, manage players, and keep everyone informed. Save hours every week.</p>
            <div class="hero-buttons">
                <a href="register.php" class="btn btn-primary">Start Free</a>
                <a href="#how-it-works" class="btn btn-secondary">See How It Works</a>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-header">
            <h2>Everything You Need</h2>
            <p>Run your Americano and Mexicano tournaments without the headache.</p>
        </div>
        <div class="features">
            <div class="feature-card">
                <div class="feature-icon">📅</div>
                <h3>Easy Scheduling</h3>
                <p>Create tournaments in seconds. Set date, time, location, max players, and skill level.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">👥</div>
                <h3>Automatic Registrations</h3>
                <p>Players register themselves. No more manual sign-ups or chasing payments.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📧</div>
                <h3>Automatic Reminders</h3>
                <p>Players get email reminders before each tournament. Less no-shows.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📱</div>
                <h3>Mobile Friendly</h3>
                <p>Works perfectly on phones. Players can register from anywhere.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔗</div>
                <h3>Easy Sharing</h3>
                <p>Share tournament links anywhere — WhatsApp, email, social media.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🎉</div>
                <h3>Build Your Community</h3>
                <p>Players see upcoming tournaments and keep coming back to your club.</p>
            </div>
        </div>
    </div>

    <div class="section how-it-works">
        <div class="section-header">
            <h2>How It Works</h2>
            <p>Get started in minutes.</p>
        </div>
        <div class="steps">
            <div class="step">
                <div class="step-number">1</div>
                <h3>Create Your Tournament</h3>
                <p>Fill in the details — name, date, time, location, and max players.</p>
            </div>
            <div class="step">
                <div class="step-number">2</div>
                <h3>Share the Link</h3>
                <p>Share your tournament link with players via WhatsApp or email.</p>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <h3>Watch Players Register</h3>
                <p>Players register themselves and get automatic confirmations.</p>
            </div>
        </div>
    </div>

    <div class="cta">
        <h2>Ready to Simplify Your Tournaments?</h2>
        <p>Join padel clubs already using our platform. Free to start, no credit card required.</p>
        <div class="cta-buttons">
            <a href="login.php" class="btn btn-secondary">Login</a>
            <a href="register.php" class="btn btn-primary">Get Started</a>
        </div>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Play Padel with Us. Built for the padel community.</p>
    </footer>
</body>
</html>

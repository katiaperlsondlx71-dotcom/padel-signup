<?php
require_once 'includes/functions.php';

$pageTitle = 'Reset Password';

// Redirect if already logged in
if (isLoggedIn()) {
    redirectTo('index.php');
}

$token = sanitizeInput($_GET['token'] ?? '');
$errors = [];
$message = '';
$tokenValid = false;

// Validate token first
if ($token) {
    $resetData = validateResetToken($token);
    if ($resetData) {
        $tokenValid = true;
    } else {
        $errors[] = 'Invalid or expired reset token. Please request a new password reset.';
    }
} else {
    $errors[] = 'No reset token provided.';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid) {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors[] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long';
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match';
    }
    
    // Reset password if no errors
    if (empty($errors)) {
        if (useResetToken($token, $password)) {
            $message = 'Your password has been successfully reset. You can now log in with your new password.';
            $tokenValid = false; // Prevent further submissions
            
            // Clean up old tokens
            cleanupExpiredResetTokens();
        } else {
            $errors[] = 'Failed to reset password. Please try again.';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Padel Tournament Registration</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

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

        .logo {
            font-size: 18px;
            font-weight: 700;
            color: #374151;
            text-decoration: none;
            transition: color 0.2s;
        }
        
        .logo:hover {
            color: #5a9fd4;
        }

        .btn-primary {
            background: #5a9fd4;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: background 0.2s;
        }

        .btn-primary:hover {
            background: #4a8bc2;
        }

        /* Title Section */
        .title-section {
            background: white;
            padding: 40px 0;
            border-bottom: 1px solid #e5e7eb;
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
            font-family: Georgia, serif;
        }

        /* Content Area */
        .content-area {
            background: #F3F4F6;
            padding: 40px 0;
            min-height: 60vh;
        }

        .content-container {
            max-width: 500px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Reset Form */
        .reset-form {
            background: white;
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .form-field {
            margin-bottom: 24px;
        }

        .form-field label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-weight: 500;
        }

        .form-field input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-field input:focus {
            outline: none;
            border-color: #5a9fd4;
            box-shadow: 0 0 0 3px rgba(90, 159, 212, 0.1);
        }

        .btn-primary.submit {
            width: 100%;
            padding: 14px;
            margin-top: 8px;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }

        .error-messages {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 24px;
        }

        .success-message {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 24px;
        }

        .login-link {
            text-align: center;
            margin-top: 24px;
            color: #6b7280;
            font-size: 14px;
        }

        .login-link a {
            color: #5a9fd4;
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .password-requirements {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px;
            margin-top: 8px;
            font-size: 14px;
            color: #64748b;
        }

        /* Mobile Responsive */
        @media (max-width: 767px) {
            .nav-links {
                display: none;
            }
            
            .btn-primary {
                padding: 6px 12px;
                font-size: 13px;
            }
            
            .nav-container {
                justify-content: center;
            }
            
            .title-container h1 {
                font-size: 28px;
                text-align: center;
            }

            .reset-form {
                padding: 24px 20px;
                margin: 0 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <div class="nav-bar">
        <div class="nav-container">
            <a href="index.php" class="logo">🎾 Play Padel with Us</a>
            
            <div class="nav-links">
                <a href="player-levels.php" class="nav-link">Player Levels</a>
            </div>
            
            <a href="login.php" class="btn-primary">Login</a>
        </div>
    </div>

    <!-- Title Section -->
    <div class="title-section">
        <div class="title-container">
            <h1>Reset Password</h1>
        </div>
    </div>

    <!-- Content Area -->
    <div class="content-area">
        <div class="content-container">
            <div class="reset-form">
                <?php if (!empty($errors)): ?>
                    <div class="error-messages">
                        <?php foreach ($errors as $error): ?>
                            <?php echo htmlspecialchars($error); ?><br>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($message): ?>
                    <div class="success-message">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                    <div class="login-link">
                        <a href="login.php">Click here to log in</a>
                    </div>
                <?php elseif ($tokenValid): ?>
                    <form method="POST" action="">
                        <div class="form-field">
                            <label for="password">New Password</label>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                required 
                                placeholder="Enter your new password"
                                minlength="<?php echo PASSWORD_MIN_LENGTH; ?>"
                            >
                            <div class="password-requirements">
                                Password must be at least <?php echo PASSWORD_MIN_LENGTH; ?> characters long.
                            </div>
                        </div>

                        <div class="form-field">
                            <label for="confirm_password">Confirm Password</label>
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                required 
                                placeholder="Confirm your new password"
                            >
                        </div>
                        
                        <button type="submit" class="btn-primary submit">
                            Update Password
                        </button>
                    </form>
                <?php else: ?>
                    <div class="login-link">
                        <a href="forgot-password.php">Request a new password reset</a> or 
                        <a href="login.php">return to login</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
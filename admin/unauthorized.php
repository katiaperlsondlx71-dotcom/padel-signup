<?php
require_once '../includes/functions.php';

// Get user status for better messaging
$isLoggedIn = isLoggedIn();
$userName = $isLoggedIn ? ($_SESSION['user_name'] ?? 'User') : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - Padel Tournament Registration</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            padding: 0;
            background: #f7fafc;
            color: #1a202c;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            max-width: 500px;
            width: 100%;
            margin: 20px;
        }
        .error-card {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .error-icon {
            font-size: 64px;
            margin-bottom: 24px;
        }
        .error-title {
            font-size: 28px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 16px;
        }
        .error-message {
            font-size: 16px;
            color: #718096;
            line-height: 1.6;
            margin-bottom: 32px;
        }
        .user-info {
            background: #f7fafc;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
            color: #4a5568;
        }
        .btn {
            background: #5a9fd4;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            font-weight: 500;
            margin: 8px;
            transition: background-color 0.2s ease;
        }
        .btn:hover {
            background: #4a8bc2;
        }
        .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }
        .btn-secondary:hover {
            background: #cbd5e0;
        }
        @media (max-width: 480px) {
            .error-card {
                padding: 24px;
            }
            .error-title {
                font-size: 24px;
            }
            .error-icon {
                font-size: 48px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="error-card">
        <div class="error-icon">🚫</div>
        
        <h1 class="error-title">Access Denied</h1>
        
        <div class="error-message">
            You don't have permission to access the admin area. Only administrators can view this section of the site.
        </div>
        
        <?php if ($isLoggedIn): ?>
            <div class="user-info">
                <strong>Logged in as:</strong> <?php echo htmlspecialchars($userName); ?><br>
                <strong>Account Type:</strong> Standard User
            </div>
            
            <p style="color: #718096; font-size: 14px; margin-bottom: 32px;">
                If you believe you should have admin access, please contact the site administrator.
            </p>
        <?php else: ?>
            <div class="user-info">
                You are not logged in. Please log in to access your account.
            </div>
        <?php endif; ?>
        
        <div>
            <a href="../index.php" class="btn">
                🏠 Back to Tournaments
            </a>
            
            <?php if (!$isLoggedIn): ?>
                <a href="../login.php" class="btn btn-secondary">
                    🔐 Login
                </a>
            <?php else: ?>
                <a href="../edit-account.php" class="btn btn-secondary">
                    👤 My Account
                </a>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid #e2e8f0; font-size: 12px; color: #a0aec0;">
            Error Code: 403 - Forbidden
        </div>
    </div>
</div>

</body>
</html>
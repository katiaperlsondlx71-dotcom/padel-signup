<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Padel Tournament Registration</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/modern-style.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="index.php" class="logo">🏓 Padel Tournaments</a>
                
                <nav>
                    <ul class="nav-menu">
                        <li><a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">Tournaments</a></li>
                        <?php if (isLoggedIn()): ?>
                            <li><a href="create-tournament.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'create-tournament.php' ? 'active' : ''; ?>">Create</a></li>
                        <?php endif; ?>
                        <?php if (isAdmin()): ?>
                            <li><a href="admin/dashboard.php" class="<?php echo strpos($_SERVER['PHP_SELF'], 'admin') !== false ? 'active' : ''; ?>">Admin</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
                
                <div class="user-menu">
                    <?php if (isLoggedIn()): ?>
                        <a href="../edit-account.php" style="display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; background: #f7fafc; border: 1px solid #e2e8f0; border-radius: 50%; color: #4a5568; font-size: 14px; font-weight: 600; text-decoration: none; transition: all 0.2s ease; margin-right: 12px;" onmouseover="this.style.background='#edf2f7'; this.style.borderColor='#cbd5e0'" onmouseout="this.style.background='#f7fafc'; this.style.borderColor='#e2e8f0'">
                            <?php echo getUserInitials($_SESSION['user_name']); ?>
                        </a>
                        <a href="../logout.php" class="btn btn-secondary btn-sm">Logout</a>
                    <?php else: ?>
                        <a href="../login.php" class="btn btn-secondary btn-sm">Login</a>
                        <a href="../register.php" class="btn btn-primary btn-sm">Sign Up</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Admin Page Indicator -->
    <?php if (isAdminPage() && isAdmin()): ?>
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 0.75rem 0; text-align: center; border-bottom: 1px solid #e2e8f0;">
            <div class="container" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                <span style="font-size: 1.2rem;">👑</span>
                <span style="font-weight: 600;">Admin Panel</span>
                <span style="opacity: 0.8;">•</span>
                <span style="opacity: 0.9;"><?php echo getCurrentPageTitle(); ?></span>
            </div>
        </div>
    <?php endif; ?>
    
    <main class="container">
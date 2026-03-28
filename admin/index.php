<?php
require_once '../includes/functions.php';

// Check if user is logged in and is admin
if (!isLoggedIn()) {
    header('Location: ../login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

if (!isAdmin()) {
    header('Location: unauthorized.php');
    exit;
}

// If they are admin, redirect to dashboard
header('Location: dashboard.php');
exit;
-- Migration: add login_attempts table for IP-based rate limiting on
-- /login.php and /forgot-password.php. Safe to re-run.

CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    email VARCHAR(255),
    action ENUM('login', 'reset') NOT NULL DEFAULT 'login',
    success BOOLEAN DEFAULT FALSE,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_action_time (ip_address, action, attempted_at)
);

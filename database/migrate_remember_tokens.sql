-- Migration: Add remember_tokens table for "Remember Me" functionality
-- Run this script to add the new table without affecting existing data

-- Create remember tokens table
CREATE TABLE IF NOT EXISTS remember_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(128) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Add indexes for performance
CREATE INDEX IF NOT EXISTS idx_remember_tokens_expires ON remember_tokens(expires_at);
CREATE INDEX IF NOT EXISTS idx_remember_tokens_user ON remember_tokens(user_id);
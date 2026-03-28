-- Migration to add banned column to users table
-- Run this script on existing databases to add the banned functionality

ALTER TABLE users ADD COLUMN is_banned BOOLEAN DEFAULT FALSE AFTER is_admin;
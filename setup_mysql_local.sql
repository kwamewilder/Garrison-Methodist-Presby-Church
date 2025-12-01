-- GMPC CMS - Local MySQL Setup
-- Run this in MySQL to create database and user

-- Create database
CREATE DATABASE IF NOT EXISTS gmpc_cms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user (change password if needed)
CREATE USER IF NOT EXISTS 'gmpc_user'@'localhost' IDENTIFIED BY 'gmpc_local_pass';

-- Grant all privileges
GRANT ALL PRIVILEGES ON gmpc_cms.* TO 'gmpc_user'@'localhost';

-- Apply changes
FLUSH PRIVILEGES;

-- Show databases to verify
SHOW DATABASES;

-- Success message
SELECT 'Database and user created successfully!' AS Status;

#!/bin/bash

# GMPC Local Setup Script for Linux Mint
# This script automates the local development setup

set -e  # Exit on error

echo "╔════════════════════════════════════════════════════════════╗"
echo "║   GMPC CMS - Local Development Setup                      ║"
echo "║   This will install and configure everything needed       ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_success() { echo -e "${GREEN}✓ $1${NC}"; }
print_error() { echo -e "${RED}✗ $1${NC}"; }
print_info() { echo -e "${BLUE}ℹ $1${NC}"; }
print_warning() { echo -e "${YELLOW}⚠ $1${NC}"; }

# Check if running as root
if [ "$EUID" -eq 0 ]; then
    print_error "Please run this script as a normal user (not root)"
    print_info "The script will ask for your password when needed"
    exit 1
fi

# Project directory
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
print_info "Project directory: $PROJECT_DIR"
echo ""

# Step 1: Update system
print_info "Step 1/8: Updating system packages..."
sudo apt update -qq
print_success "System updated"
echo ""

# Step 2: Check and install Apache
print_info "Step 2/8: Installing Apache web server..."
if ! command -v apache2 &> /dev/null; then
    sudo apt install -y apache2 > /dev/null 2>&1
    print_success "Apache installed"
else
    print_warning "Apache already installed"
fi

# Start and enable Apache
sudo systemctl start apache2
sudo systemctl enable apache2 > /dev/null 2>&1
print_success "Apache started"
echo ""

# Step 3: Check and install MySQL
print_info "Step 3/8: Installing MySQL database..."
if ! command -v mysql &> /dev/null; then
    sudo apt install -y mysql-server > /dev/null 2>&1
    print_success "MySQL installed"
else
    print_warning "MySQL already installed"
fi

# Start and enable MySQL
sudo systemctl start mysql
sudo systemctl enable mysql > /dev/null 2>&1
print_success "MySQL started"
echo ""

# Step 4: Install PHP
print_info "Step 4/8: Installing PHP and extensions..."
if ! command -v php &> /dev/null; then
    sudo apt install -y php libapache2-mod-php php-mysql php-cli php-mbstring php-xml php-curl php-gd > /dev/null 2>&1
    print_success "PHP installed"
else
    print_warning "PHP already installed"
fi

# Enable PHP module
sudo a2enmod php8.3 > /dev/null 2>&1 || sudo a2enmod php8.2 > /dev/null 2>&1 || sudo a2enmod php8.1 > /dev/null 2>&1
print_success "PHP module enabled"
echo ""

# Step 5: Enable required Apache modules
print_info "Step 5/8: Configuring Apache..."
sudo a2enmod rewrite > /dev/null 2>&1
print_success "Rewrite module enabled"

# Update Apache config to allow .htaccess
sudo sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf
print_success "Apache configured for .htaccess"

sudo systemctl restart apache2
print_success "Apache restarted"
echo ""

# Step 6: Create symbolic link
print_info "Step 6/8: Setting up project in web root..."
if [ ! -L "/var/www/html/gmpc" ]; then
    sudo rm -f /var/www/html/index.html
    sudo ln -s "$PROJECT_DIR" /var/www/html/gmpc
    print_success "Symbolic link created: /var/www/html/gmpc -> $PROJECT_DIR"
else
    print_warning "Symbolic link already exists"
fi

# Set permissions
sudo chown -R $USER:www-data "$PROJECT_DIR/uploads" 2>/dev/null || true
sudo chmod -R 775 "$PROJECT_DIR/uploads" 2>/dev/null || true
print_success "Permissions set"
echo ""

# Step 7: Setup MySQL database
print_info "Step 7/8: Setting up MySQL database..."

# Create database and user
sudo mysql -e "CREATE DATABASE IF NOT EXISTS gmpc_cms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null || true
sudo mysql -e "CREATE USER IF NOT EXISTS 'gmpc_user'@'localhost' IDENTIFIED BY 'gmpc_password';" 2>/dev/null || true
sudo mysql -e "GRANT ALL PRIVILEGES ON gmpc_cms.* TO 'gmpc_user'@'localhost';" 2>/dev/null || true
sudo mysql -e "FLUSH PRIVILEGES;" 2>/dev/null || true

print_success "Database 'gmpc_cms' created"
print_success "User 'gmpc_user' created with password 'gmpc_password'"
echo ""

# Step 8: Update config.php
print_info "Step 8/8: Configuring database connection..."

# Backup original config
if [ ! -f "$PROJECT_DIR/config.php.backup" ]; then
    cp "$PROJECT_DIR/config.php" "$PROJECT_DIR/config.php.backup"
fi

# Update config.php with local settings
cat > "$PROJECT_DIR/config.php" << 'CONFIGEOF'
<?php
/**
 * Database Configuration - LOCAL DEVELOPMENT
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'gmpc_user');
define('DB_PASS', 'gmpc_password');
define('DB_NAME', 'gmpc_cms');

// Site Configuration - LOCAL
define('SITE_URL', 'http://localhost/gmpc');
define('ADMIN_URL', 'http://localhost/gmpc/admin');
define('UPLOAD_PATH', __DIR__ . '/uploads/');
define('UPLOAD_URL', 'http://localhost/gmpc/uploads/');

// Security
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('PASSWORD_MIN_LENGTH', 8);

// Create database connection
function getDBConnection() {
    static $conn = null;

    if ($conn === null) {
        try {
            $conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection failed. Please contact the administrator.");
        }
    }

    return $conn;
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timezone
date_default_timezone_set('Africa/Accra');
?>
CONFIGEOF

print_success "config.php updated for local development"
echo ""

# Final checks
echo "╔════════════════════════════════════════════════════════════╗"
echo "║                    INSTALLATION COMPLETE!                  ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""

print_success "All components installed and configured!"
echo ""

print_info "═══════════════════ NEXT STEPS ═══════════════════"
echo ""
print_info "1. Run database setup:"
echo "   Open browser: ${BLUE}http://localhost/gmpc/setup_database.php${NC}"
echo ""
print_info "2. Login to admin panel:"
echo "   URL: ${BLUE}http://localhost/gmpc/admin/${NC}"
echo "   Username: ${GREEN}admin${NC}"
echo "   Password: ${GREEN}admin123${NC}"
echo ""
print_info "3. View your website:"
echo "   ${BLUE}http://localhost/gmpc/${NC}"
echo ""

print_warning "IMPORTANT SECURITY STEPS:"
echo "  • Change admin password after first login"
echo "  • Delete setup_database.php after running it"
echo ""

print_info "═══════════════════ SYSTEM INFO ═══════════════════"
echo "  PHP Version: $(php -v | head -n 1 | cut -d ' ' -f2)"
echo "  MySQL Version: $(mysql --version | cut -d ' ' -f6 | cut -d ',' -f1)"
echo "  Apache Version: $(apache2 -v | head -n 1 | cut -d ' ' -f3 | cut -d '/' -f2)"
echo ""
echo "  Database: gmpc_cms"
echo "  DB User: gmpc_user"
echo "  DB Pass: gmpc_password"
echo ""

print_info "═══════════════════ USEFUL COMMANDS ═══════════════"
echo "  View website: ${BLUE}firefox http://localhost/gmpc/${NC}"
echo "  View admin: ${BLUE}firefox http://localhost/gmpc/admin/${NC}"
echo "  Stop Apache: ${YELLOW}sudo systemctl stop apache2${NC}"
echo "  Start Apache: ${YELLOW}sudo systemctl start apache2${NC}"
echo ""

print_success "Setup complete! Open http://localhost/gmpc/setup_database.php to continue"

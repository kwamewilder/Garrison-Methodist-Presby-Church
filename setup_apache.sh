#!/bin/bash

# Setup Apache for GMPC CMS
# This script creates a symbolic link and sets permissions

set -e

echo "╔════════════════════════════════════════════════════════╗"
echo "║   Setting up Apache for GMPC CMS                      ║"
echo "╚════════════════════════════════════════════════════════╝"
echo ""

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m'

PROJECT_DIR="/home/oheneba/Garrison-Methodist-Presby-Church"

echo -e "${BLUE}Step 1: Creating symbolic link...${NC}"
# Remove old link if exists
sudo rm -f /var/www/html/gmpc

# Create new symbolic link
sudo ln -s "$PROJECT_DIR" /var/www/html/gmpc

echo -e "${GREEN}✓ Symbolic link created: /var/www/html/gmpc -> $PROJECT_DIR${NC}"
echo ""

echo -e "${BLUE}Step 2: Setting permissions...${NC}"
# Set ownership
sudo chown -R $USER:www-data "$PROJECT_DIR"

# Set directory permissions
sudo find "$PROJECT_DIR" -type d -exec chmod 755 {} \;

# Set file permissions
sudo find "$PROJECT_DIR" -type f -exec chmod 644 {} \;

# Make uploads writable
sudo chmod -R 775 "$PROJECT_DIR/uploads"
sudo chown -R www-data:www-data "$PROJECT_DIR/uploads"

echo -e "${GREEN}✓ Permissions set${NC}"
echo ""

echo -e "${BLUE}Step 3: Enabling Apache modules...${NC}"
sudo a2enmod rewrite > /dev/null 2>&1 || true
sudo a2enmod php8.3 > /dev/null 2>&1 || sudo a2enmod php8.2 > /dev/null 2>&1 || sudo a2enmod php8.1 > /dev/null 2>&1 || true

echo -e "${GREEN}✓ Modules enabled${NC}"
echo ""

echo -e "${BLUE}Step 4: Configuring Apache for .htaccess...${NC}"
# Backup original config
sudo cp /etc/apache2/apache2.conf /etc/apache2/apache2.conf.backup 2>/dev/null || true

# Update AllowOverride
sudo sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

echo -e "${GREEN}✓ Apache configured${NC}"
echo ""

echo -e "${BLUE}Step 5: Restarting Apache...${NC}"
sudo systemctl restart apache2

echo -e "${GREEN}✓ Apache restarted${NC}"
echo ""

echo "╔════════════════════════════════════════════════════════╗"
echo "║              APACHE SETUP COMPLETE!                   ║"
echo "╚════════════════════════════════════════════════════════╝"
echo ""
echo -e "${GREEN}✓ Your site is now available at:${NC}"
echo ""
echo -e "  ${BLUE}http://localhost/gmpc/${NC}"
echo ""
echo -e "${BLUE}Next steps:${NC}"
echo "  1. Setup MySQL database (see instructions below)"
echo "  2. Visit: http://localhost/gmpc/test_db_connection.php"
echo "  3. Visit: http://localhost/gmpc/setup_database.php"
echo "  4. Login: http://localhost/gmpc/admin/"
echo ""

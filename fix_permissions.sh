#!/bin/bash

# Fix Apache Permissions for GMPC
# This fixes the "Forbidden" error

echo "╔════════════════════════════════════════════════════════╗"
echo "║   Fixing Apache Permissions - GMPC CMS               ║"
echo "╚════════════════════════════════════════════════════════╝"
echo ""

PROJECT_DIR="/home/oheneba/Garrison-Methodist-Presby-Church"

echo "Step 1: Setting correct ownership..."
sudo chown -R $USER:www-data "$PROJECT_DIR"
echo "✓ Ownership set to $USER:www-data"
echo ""

echo "Step 2: Setting directory permissions (755)..."
sudo find "$PROJECT_DIR" -type d -exec chmod 755 {} \;
echo "✓ Directory permissions set"
echo ""

echo "Step 3: Setting file permissions (644)..."
sudo find "$PROJECT_DIR" -type f -exec chmod 644 {} \;
echo "✓ File permissions set"
echo ""

echo "Step 4: Making uploads writable..."
sudo chmod -R 775 "$PROJECT_DIR/uploads"
sudo chown -R www-data:www-data "$PROJECT_DIR/uploads"
echo "✓ Uploads directory configured"
echo ""

echo "Step 5: Setting home directory permissions..."
sudo chmod 755 /home/oheneba
echo "✓ Home directory accessible"
echo ""

echo "Step 6: Updating Apache configuration..."
# Create a specific site configuration for better permissions
sudo tee /etc/apache2/sites-available/gmpc.conf > /dev/null << 'EOF'
<Directory /home/oheneba/Garrison-Methodist-Presby-Church>
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>

Alias /gmpc /home/oheneba/Garrison-Methodist-Presby-Church
EOF

# Enable the site
sudo a2ensite gmpc.conf > /dev/null 2>&1
echo "✓ Apache configuration updated"
echo ""

echo "Step 7: Restarting Apache..."
sudo systemctl restart apache2
echo "✓ Apache restarted"
echo ""

echo "╔════════════════════════════════════════════════════════╗"
echo "║              PERMISSIONS FIXED!                       ║"
echo "╚════════════════════════════════════════════════════════╝"
echo ""
echo "Try accessing your site now:"
echo "  http://localhost/gmpc/"
echo ""

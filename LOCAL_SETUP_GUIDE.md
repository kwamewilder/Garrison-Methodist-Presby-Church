# 🚀 Local Development Setup Guide
## Running GMPC CMS on Your Linux Mint Computer

---

## Step 1️⃣: Install LAMP Stack (5-10 minutes)

Open **Terminal** (Ctrl+Alt+T) and run these commands:

### 1.1 Update System
```bash
sudo apt update
```

### 1.2 Install Apache, MySQL, PHP
```bash
sudo apt install -y apache2 mysql-server php libapache2-mod-php php-mysql php-cli php-mbstring php-xml php-curl php-gd
```
⏱️ This takes 2-5 minutes

### 1.3 Install phpMyAdmin (Database Manager)
```bash
sudo apt install -y phpmyadmin
```

**Important Prompts:**
- Web server: Press **SPACE** to select `apache2`, then **ENTER**
- Configure database: Select **Yes**
- Password: Enter a password (remember this!)

### 1.4 Enable Required Modules
```bash
sudo a2enmod php8.3
sudo a2enmod rewrite
sudo systemctl restart apache2
```

---

## Step 2️⃣: Verify Installation

Run this command to check everything:
```bash
php -v && mysql --version && apache2 -v
```

You should see version numbers for all three.

**Check Services are Running:**
```bash
sudo systemctl status apache2
sudo systemctl status mysql
```

Both should show **"active (running)"** in green.

**Test Apache in Browser:**
- Open browser: http://localhost
- You should see "Apache2 Ubuntu Default Page"

**Test phpMyAdmin:**
- Open browser: http://localhost/phpmyadmin
- Login with username: `phpmyadmin` and your password

---

## Step 3️⃣: Setup Your Project

### 3.1 Create Symbolic Link to Apache
Your project is in: `/home/oheneba/Garrison-Methodist-Presby-Church`
Apache serves files from: `/var/www/html`

**Option A: Create Symbolic Link (Recommended)**
```bash
# Remove default Apache index
sudo rm /var/www/html/index.html

# Create symbolic link
sudo ln -s /home/oheneba/Garrison-Methodist-Presby-Church /var/www/html/gmpc

# Set permissions
sudo chown -R www-data:www-data /home/oheneba/Garrison-Methodist-Presby-Church/uploads
sudo chmod -R 755 /home/oheneba/Garrison-Methodist-Presby-Church/uploads
```

**Your website will be at:** http://localhost/gmpc/

**Option B: Copy Project to Apache Directory**
```bash
sudo cp -r /home/oheneba/Garrison-Methodist-Presby-Church /var/www/html/gmpc
sudo chown -R www-data:www-data /var/www/html/gmpc
sudo chmod -R 755 /var/www/html/gmpc/uploads
```

### 3.2 Enable .htaccess Support
```bash
sudo nano /etc/apache2/apache2.conf
```

Find this section:
```
<Directory /var/www/>
    Options Indexes FollowSymLinks
    AllowOverride None
    Require all granted
</Directory>
```

Change `AllowOverride None` to `AllowOverride All`:
```
<Directory /var/www/>
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

Save: **Ctrl+O**, **Enter**, **Ctrl+X**

Restart Apache:
```bash
sudo systemctl restart apache2
```

---

## Step 4️⃣: Setup MySQL Database

### 4.1 Secure MySQL (First Time Only)
```bash
sudo mysql_secure_installation
```

Answer prompts:
- Set root password? **Y** → Enter a password
- Remove anonymous users? **Y**
- Disallow root login remotely? **Y**
- Remove test database? **Y**
- Reload privilege tables? **Y**

### 4.2 Create Database
```bash
sudo mysql
```

In MySQL prompt, run:
```sql
CREATE DATABASE gmpc_cms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'gmpc_user'@'localhost' IDENTIFIED BY 'gmpc_password';
GRANT ALL PRIVILEGES ON gmpc_cms.* TO 'gmpc_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

✅ Database created!

**Or Use phpMyAdmin:**
1. Go to: http://localhost/phpmyadmin
2. Click "Databases" tab
3. Create database: `gmpc_cms`
4. Collation: `utf8mb4_unicode_ci`

---

## Step 5️⃣: Configure config.php

Edit your config file:
```bash
cd /home/oheneba/Garrison-Methodist-Presby-Church
nano config.php
```

Update these lines:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'gmpc_user');
define('DB_PASS', 'gmpc_password');
define('DB_NAME', 'gmpc_cms');
```

Also update the site URL:
```php
define('SITE_URL', 'http://localhost/gmpc');
define('ADMIN_URL', 'http://localhost/gmpc/admin');
```

Save: **Ctrl+O**, **Enter**, **Ctrl+X**

---

## Step 6️⃣: Run Database Setup

### 6.1 Open Browser
Go to: **http://localhost/gmpc/setup_database.php**

### 6.2 You Should See
✅ "Database Setup Complete!" message
✅ Default admin credentials displayed
✅ List of 8 tables created

### 6.3 If You See Errors

**Error: "Database connection failed"**
- Check config.php credentials
- Make sure MySQL is running: `sudo systemctl status mysql`

**Error: "Access denied"**
- Wrong username/password in config.php
- Recreate MySQL user (see Step 4.2)

**Error: "Can't connect to MySQL server"**
- Start MySQL: `sudo systemctl start mysql`

---

## Step 7️⃣: Login to Admin Panel

### 7.1 Open Admin Login
Go to: **http://localhost/gmpc/admin/**

### 7.2 Login with Default Credentials
- Username: `admin`
- Password: `admin123`

### 7.3 You Should See
✅ Dashboard with statistics
✅ Sidebar navigation
✅ 4 stat cards showing numbers

---

## Step 8️⃣: Test the CMS

### Test Communities Management
1. Click **"Communities"** in sidebar
2. Click **"Edit"** on "Choir"
3. Change meeting time to "7:00 PM - 9:00 PM"
4. Click **"Save Changes"**
5. Go to: http://localhost/gmpc/communities/choir.html
6. Verify time hasn't changed (because pages are static HTML)

**Note:** The HTML pages are static. To make them dynamic, we'd need to update them to fetch from database using PHP.

### Test Settings
1. Click **"Settings"** in sidebar
2. Change church phone to "(+233) 999-8888"
3. Click **"Save Settings"**
4. Data is saved in database!

---

## Step 9️⃣: Secure Your Installation

### 9.1 Delete Setup File
```bash
rm /home/oheneba/Garrison-Methodist-Presby-Church/setup_database.php
```

### 9.2 Change Admin Password
For now, via phpMyAdmin:
1. Go to: http://localhost/phpmyadmin
2. Database: `gmpc_cms` → Table: `admins`
3. Edit the admin user
4. Generate new password hash:

Create file `hash.php` in project root:
```php
<?php
echo password_hash('YourNewPassword123!', PASSWORD_DEFAULT);
?>
```

Visit: http://localhost/gmpc/hash.php
Copy the hash, paste in password field
Delete hash.php

---

## 🎯 Quick Reference

### URLs
- **Website:** http://localhost/gmpc/
- **Admin Panel:** http://localhost/gmpc/admin/
- **phpMyAdmin:** http://localhost/phpmyadmin

### Default Credentials
- **Admin User:** admin
- **Admin Pass:** admin123 (change this!)

### Useful Commands
```bash
# Start services
sudo systemctl start apache2
sudo systemctl start mysql

# Stop services
sudo systemctl stop apache2
sudo systemctl stop mysql

# Restart services
sudo systemctl restart apache2
sudo systemctl restart mysql

# Check status
sudo systemctl status apache2
sudo systemctl status mysql

# View Apache error logs
sudo tail -f /var/log/apache2/error.log

# View PHP errors
sudo tail -f /var/log/apache2/error.log
```

### File Locations
- **Apache config:** /etc/apache2/
- **Web root:** /var/www/html/
- **PHP config:** /etc/php/8.3/
- **MySQL config:** /etc/mysql/
- **Your project:** /home/oheneba/Garrison-Methodist-Presby-Church/
- **Logs:** /var/log/apache2/

---

## 🚨 Troubleshooting

### Apache won't start
```bash
# Check what's wrong
sudo systemctl status apache2
sudo apache2ctl configtest

# Check if port 80 is in use
sudo netstat -tulpn | grep :80
```

### MySQL won't start
```bash
sudo systemctl status mysql
sudo journalctl -u mysql -n 50
```

### Permission errors
```bash
# Fix ownership
sudo chown -R www-data:www-data /var/www/html/gmpc

# Fix permissions
sudo chmod -R 755 /var/www/html/gmpc
sudo chmod -R 755 /var/www/html/gmpc/uploads
```

### Can't access phpMyAdmin
```bash
# Reinstall phpMyAdmin
sudo apt install --reinstall phpmyadmin

# Create symbolic link
sudo ln -s /usr/share/phpmyadmin /var/www/html/phpmyadmin
```

### PHP not working
```bash
# Make sure PHP module is enabled
sudo a2enmod php8.3
sudo systemctl restart apache2

# Check PHP is installed
php -v
```

---

## 🎉 Success Checklist

After setup, you should be able to:

- [ ] Open http://localhost/gmpc/ and see your website
- [ ] Open http://localhost/gmpc/admin/ and login
- [ ] See dashboard with statistics
- [ ] Click Communities and see 10 communities
- [ ] Edit a community and save
- [ ] Click Settings and update information
- [ ] Access phpMyAdmin and see database tables

---

## 📞 Need Help?

Common issues and solutions in troubleshooting section above.

If stuck, check:
1. Apache error log: `sudo tail -f /var/log/apache2/error.log`
2. MySQL is running: `sudo systemctl status mysql`
3. PHP is working: Create test.php with `<?php phpinfo(); ?>`

---

**You're all set for local development!** 🚀

Once everything works locally, you can deploy to a live server.

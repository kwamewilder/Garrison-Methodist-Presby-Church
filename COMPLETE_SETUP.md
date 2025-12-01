# 🚀 Complete Local Setup - Quick Guide

You need to fix two things:
1. **MySQL password** - Reset it
2. **Apache access** - Create link to your project

---

## Step 1: Setup Apache (1 minute)

Run this in Terminal:

```bash
cd /home/oheneba/Garrison-Methodist-Presby-Church
./setup_apache.sh
```

This will:
- Create link: /var/www/html/gmpc → your project
- Set correct permissions
- Enable required modules
- Restart Apache

**After this, you should be able to access:**
```
http://localhost/gmpc/
```

---

## Step 2: Setup MySQL Database (2 minutes)

### A. Access MySQL (no password needed with sudo)

```bash
sudo mysql
```

You should see `mysql>` prompt.

### B. Run these SQL commands:

```sql
-- Create database
CREATE DATABASE gmpc_cms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user
CREATE USER 'gmpc_user'@'localhost' IDENTIFIED BY 'gmpc_local_pass';

-- Grant privileges
GRANT ALL PRIVILEGES ON gmpc_cms.* TO 'gmpc_user'@'localhost';

-- Apply changes
FLUSH PRIVILEGES;

-- Verify (should show gmpc_cms in list)
SHOW DATABASES;

-- Exit
EXIT;
```

**Copy and paste all at once!**

---

## Step 3: Test Database Connection

Open browser:
```
http://localhost/gmpc/test_db_connection.php
```

You should see:
- ✓ Connection successful
- ✓ Database exists
- ⚠ No tables yet (this is normal)

---

## Step 4: Run Database Setup

Open browser:
```
http://localhost/gmpc/setup_database.php
```

You should see:
- ✓ "Database Setup Complete!"
- ✓ 8 tables created
- ✓ Default admin credentials shown

---

## Step 5: Login to Admin

Open browser:
```
http://localhost/gmpc/admin/
```

Login:
- Username: `admin`
- Password: `admin123`

You should see the dashboard!

---

## Quick Summary (Copy & Paste)

```bash
# 1. Setup Apache
cd /home/oheneba/Garrison-Methodist-Presby-Church
./setup_apache.sh

# 2. Setup MySQL
sudo mysql
```

Then in MySQL (`mysql>` prompt):
```sql
CREATE DATABASE gmpc_cms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'gmpc_user'@'localhost' IDENTIFIED BY 'gmpc_local_pass';
GRANT ALL PRIVILEGES ON gmpc_cms.* TO 'gmpc_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Then in browser:
1. http://localhost/gmpc/test_db_connection.php (test)
2. http://localhost/gmpc/setup_database.php (setup)
3. http://localhost/gmpc/admin/ (login)

---

## Troubleshooting

### "Not Found" error?
- Run `./setup_apache.sh` again
- Check: `ls -la /var/www/html/gmpc`

### "Database connection failed"?
- Make sure you ran the MySQL commands
- Check: `sudo mysql -e "SHOW DATABASES;"`

### Can't access MySQL?
- Use: `sudo mysql` (not `mysql -u root -p`)
- sudo bypasses password requirement

---

**That's it! Should take less than 5 minutes total.**

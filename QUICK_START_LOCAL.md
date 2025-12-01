# 🚀 Quick Start - Local Development

## Option 1: Automated Setup (Recommended - 2 minutes)

Just run this one command in Terminal (Ctrl+Alt+T):

```bash
cd /home/oheneba/Garrison-Methodist-Presby-Church
./setup_local.sh
```

The script will:
✅ Install Apache, MySQL, PHP
✅ Configure web server
✅ Create database and user
✅ Set up your project
✅ Configure everything automatically

**After the script finishes:**

1. **Open browser**: http://localhost/gmpc/setup_database.php
2. **Login to admin**: http://localhost/gmpc/admin/
   - Username: `admin`
   - Password: `admin123`

---

## Option 2: Manual Setup (15 minutes)

Follow the detailed guide: [LOCAL_SETUP_GUIDE.md](LOCAL_SETUP_GUIDE.md)

---

## What You Get

Once setup is complete:

- ✅ **Your Website**: http://localhost/gmpc/
- ✅ **Admin Panel**: http://localhost/gmpc/admin/
- ✅ **phpMyAdmin**: http://localhost/phpmyadmin (if installed)

---

## Default Credentials

**Admin Panel:**
- Username: `admin`
- Password: `admin123` ⚠️ Change this!

**Database:**
- Host: `localhost`
- Database: `gmpc_cms`
- Username: `gmpc_user`
- Password: `gmpc_password`

---

## Troubleshooting

### Script fails?
Run commands manually from [LOCAL_SETUP_GUIDE.md](LOCAL_SETUP_GUIDE.md)

### Can't access http://localhost/gmpc/?
```bash
# Check Apache is running
sudo systemctl status apache2

# Restart Apache
sudo systemctl restart apache2
```

### Database connection error?
```bash
# Check MySQL is running
sudo systemctl status mysql

# Restart MySQL
sudo systemctl start mysql
```

---

## Next Steps After Setup

1. ✅ Run http://localhost/gmpc/setup_database.php
2. ✅ Login to admin panel
3. ✅ Test editing a community
4. ✅ Test updating settings
5. ⚠️ Change admin password
6. ⚠️ Delete setup_database.php

---

**Need help?** Check [LOCAL_SETUP_GUIDE.md](LOCAL_SETUP_GUIDE.md) for detailed instructions.

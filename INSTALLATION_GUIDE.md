# Quick Installation Guide

## 🚀 Get Your CMS Running in 5 Minutes

### Step 1: Database Setup (2 minutes)

1. **Create a database** (using phpMyAdmin, MySQL Workbench, or command line):
   ```sql
   CREATE DATABASE gmpc_cms;
   ```

2. **Update config.php** with your database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   define('DB_NAME', 'gmpc_cms');
   ```

### Step 2: Run Setup (1 minute)

1. **Open your browser** and navigate to:
   ```
   http://your-website.com/setup_database.php
   ```

2. **You should see**: "Database Setup Complete!" message

3. **Delete the setup file** for security:
   ```bash
   rm setup_database.php
   ```

### Step 3: Login (30 seconds)

1. **Go to admin panel**:
   ```
   http://your-website.com/admin/
   ```

2. **Login with default credentials**:
   - Username: `admin`
   - Password: `admin123`

### Step 4: Secure Your Site (1 minute)

1. **Change your password immediately!**
   - Go to Admin Users (Super Admin only)
   - Click Edit on admin user
   - Set a strong password

2. **Set upload permissions**:
   ```bash
   chmod 755 uploads
   chmod 755 uploads/images
   chmod 755 uploads/documents
   ```

### Step 5: Start Managing Content (30 seconds)

You're all set! Now you can:

✅ **Manage Communities**: Click "Communities" in the sidebar
✅ **Update Settings**: Click "Settings" to update church info
✅ **Add Events**: Click "Events" to add upcoming events
✅ **Upload Sermons**: Click "Sermons" to add messages

---

## 🎯 What You Can Do Now

### Manage Communities
1. Go to **Communities**
2. Click **Edit** on any community
3. Update name, description, meeting times
4. Save changes - updates instantly on website!

### Update Church Information
1. Go to **Settings**
2. Update:
   - Church name & address
   - Phone & email
   - Service times
   - Social media links
3. Save - changes reflect everywhere!

### Add Events (Coming Soon)
1. Go to **Events**
2. Click **Add New Event**
3. Fill in details
4. Publish!

---

## 🔒 Security Checklist

- [ ] Changed default admin password
- [ ] Deleted setup_database.php
- [ ] Set correct file permissions (755)
- [ ] Enabled HTTPS on your server
- [ ] Backed up database

---

## ❗ Common Issues

### Can't login?
- Check database credentials in config.php
- Make sure database exists
- Try clearing browser cache

### Database connection error?
- Verify MySQL is running
- Check username/password
- Ensure database name is correct

### Page not found?
- Check .htaccess file exists
- Verify mod_rewrite is enabled
- Check file paths in config.php

---

## 📞 Need Help?

1. Check **README_CMS.md** for detailed documentation
2. Review server error logs
3. Contact your web developer

---

## 🎉 You're Ready!

Your CMS is now installed and ready to use. Start managing your church website content with ease!

**Admin URL**: http://your-website.com/admin/

Happy content managing! 🙏

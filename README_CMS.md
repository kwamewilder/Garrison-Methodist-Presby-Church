# GMPC Content Management System (CMS)

## Overview

This is a custom-built Content Management System (CMS) for the Garrison Methodist Presbyterian Church website. It allows administrators to manage website content, images, events, sermons, and community information without editing HTML files directly.

## Features

- ✅ **Secure Admin Dashboard** - Role-based access control
- ✅ **Community Management** - Edit community details, meeting times, and descriptions
- ✅ **Site Settings** - Update church information, contact details, and service times
- ✅ **User Management** - Manage admin users (Super Admin only)
- ✅ **Media Library** - Upload and manage images
- ✅ **Responsive Design** - Works on desktop, tablet, and mobile devices

## Installation

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- mod_rewrite enabled (for Apache)

### Step 1: Database Configuration

1. Create a new MySQL database:
```sql
CREATE DATABASE gmpc_cms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Create a database user:
```sql
CREATE USER 'gmpc_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON gmpc_cms.* TO 'gmpc_user'@'localhost';
FLUSH PRIVILEGES;
```

3. Update database credentials in `config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'gmpc_user');
define('DB_PASS', 'your_secure_password');
define('DB_NAME', 'gmpc_cms');
```

### Step 2: Run Database Setup

1. Open your browser and navigate to:
```
http://your-domain.com/setup_database.php
```

2. The setup script will create all necessary tables and insert default data.

3. **IMPORTANT**: After setup completes, delete or rename `setup_database.php` for security:
```bash
rm setup_database.php
# OR
mv setup_database.php setup_database.php.bak
```

### Step 3: Set File Permissions

Make sure the uploads directory is writable:
```bash
chmod 755 uploads
chmod 755 uploads/images
chmod 755 uploads/documents
```

### Step 4: Security (IMPORTANT!)

1. **Change the default admin password immediately!**
   - Default username: `admin`
   - Default password: `admin123`

2. Add `.htaccess` file to protect admin directory (if using Apache):
```bash
# The .htaccess file is already created in the admin directory
```

3. Enable HTTPS on your server for secure connections

4. Keep your PHP and MySQL versions updated

## Default Login Credentials

**⚠️ CHANGE THESE IMMEDIATELY AFTER INSTALLATION!**

- **Username**: admin
- **Password**: admin123
- **Admin URL**: http://your-domain.com/admin/

## Directory Structure

```
Garrison-Methodist-Presby-Church/
├── admin/                      # Admin panel
│   ├── css/                    # Admin CSS files
│   ├── js/                     # Admin JavaScript files
│   ├── includes/               # PHP includes
│   │   ├── auth.php           # Authentication functions
│   │   ├── header.php         # Admin header
│   │   └── footer.php         # Admin footer
│   ├── index.php              # Dashboard
│   ├── login.php              # Login page
│   ├── logout.php             # Logout handler
│   ├── communities.php        # Community management
│   ├── settings.php           # Site settings
│   ├── events.php             # Events management
│   ├── sermons.php            # Sermons management
│   ├── pages.php              # Pages management
│   ├── media.php              # Media library
│   └── admins.php             # Admin users management
├── api/                        # API endpoints (for future use)
├── uploads/                    # Uploaded files
│   ├── images/                # Images
│   └── documents/             # Documents
├── communities/               # Community pages
├── css/                       # Frontend CSS
├── js/                        # Frontend JavaScript
├── images/                    # Static images
├── config.php                 # Database configuration
├── setup_database.php         # Database setup script
└── README_CMS.md             # This file
```

## User Roles

### Super Admin
- Full access to all features
- Can manage other admin users
- Can delete critical content

### Admin
- Can manage most content
- Cannot delete other admins
- Can create and edit pages

### Editor
- Can edit existing content
- Cannot delete content
- Cannot access user management

## Managing Content

### Communities

1. Go to **Communities** in the admin sidebar
2. Click **Edit** on any community
3. Update the information:
   - Name
   - Description
   - Full content (supports HTML)
   - Meeting times and location
4. Click **Save Changes**
5. Changes are reflected immediately on the website

### Site Settings

1. Go to **Settings** in the admin sidebar
2. Update church information:
   - Church name and address
   - Contact details
   - Service times
   - Social media links
3. Click **Save Settings**
4. Changes apply across the entire website

### Events (Coming Soon)

Add and manage church events with dates, times, and descriptions.

### Sermons (Coming Soon)

Upload sermon recordings, add titles, descriptions, and scripture references.

### Media Library (Coming Soon)

Upload and organize images and documents for use throughout the site.

## Database Schema

### Key Tables

- **admins**: Administrator users
- **pages**: Custom pages
- **sections**: Homepage sections
- **events**: Church events
- **sermons**: Sermon recordings
- **communities**: Community groups
- **settings**: Site settings
- **media**: Uploaded files

For detailed schema, see `setup_database.php`.

## Security Features

- ✅ Password hashing with bcrypt
- ✅ CSRF token protection
- ✅ SQL injection prevention (PDO prepared statements)
- ✅ XSS protection (input sanitization)
- ✅ Session timeout
- ✅ Role-based access control
- ✅ Secure file uploads (future feature)

## Troubleshooting

### Cannot login

1. Check database credentials in `config.php`
2. Verify the database exists and tables are created
3. Try resetting admin password in database

### Database connection errors

1. Check MySQL service is running
2. Verify database credentials
3. Check PHP PDO MySQL extension is enabled

### Upload errors

1. Check directory permissions (755 for uploads)
2. Verify PHP upload_max_filesize setting
3. Check disk space

### Session timeout issues

1. Adjust SESSION_TIMEOUT in `config.php`
2. Check PHP session.gc_maxlifetime setting

## Backup Recommendations

### Database Backup

Run daily backups of your database:
```bash
mysqldump -u gmpc_user -p gmpc_cms > backup_$(date +%Y%m%d).sql
```

### File Backup

Backup the uploads directory regularly:
```bash
tar -czf uploads_backup_$(date +%Y%m%d).tar.gz uploads/
```

## Support

For issues or questions:
- Check this README first
- Review error logs in your server
- Contact your web developer

## Future Enhancements

Planned features:
- [ ] Rich text editor for content
- [ ] Image upload and management
- [ ] Event calendar view
- [ ] Sermon audio/video upload
- [ ] Email notifications
- [ ] Activity logs
- [ ] Advanced user permissions
- [ ] Frontend page builder
- [ ] API for mobile app

## Version History

### Version 1.0.0 (Current)
- Initial release
- Basic admin dashboard
- Community management
- Site settings management
- User authentication
- Database structure

## License

Copyright © 2025 Garrison Methodist Presbyterian Church. All rights reserved.

---

**Built with ❤️ for GMPC**

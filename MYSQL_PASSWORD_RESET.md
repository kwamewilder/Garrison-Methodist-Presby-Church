# Reset MySQL Root Password

## Easy Method (Using sudo)

On Ubuntu/Linux Mint, you can access MySQL as root using sudo:

```bash
sudo mysql
```

This will log you in WITHOUT needing a password!

Once you're in the `mysql>` prompt, run these commands:

```sql
-- Set a new root password
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'your_new_password';

-- Or if you prefer no password (local development only!)
ALTER USER 'root'@'localhost' IDENTIFIED BY '';

-- Apply changes
FLUSH PRIVILEGES;

-- Now create the database and user
CREATE DATABASE IF NOT EXISTS gmpc_cms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'gmpc_user'@'localhost' IDENTIFIED BY 'gmpc_local_pass';
GRANT ALL PRIVILEGES ON gmpc_cms.* TO 'gmpc_user'@'localhost';
FLUSH PRIVILEGES;

-- Verify database exists
SHOW DATABASES;

-- Exit
EXIT;
```

## After Setting Password

Test the connection:
```bash
mysql -u root -p
# Enter the password you just set
```

You should now be able to login!

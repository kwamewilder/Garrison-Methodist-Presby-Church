<?php
/**
 * Database Setup Script
 * Run this file once to create all necessary tables
 * Access: http://your-domain.com/setup_database.php
 */

require_once 'config.php';

try {
    $conn = getDBConnection();

    // Create admins table
    $conn->exec("CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        role ENUM('super_admin', 'admin', 'editor') DEFAULT 'editor',
        status ENUM('active', 'inactive') DEFAULT 'active',
        last_login DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Create pages table
    $conn->exec("CREATE TABLE IF NOT EXISTS pages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        page_name VARCHAR(100) NOT NULL,
        page_slug VARCHAR(100) UNIQUE NOT NULL,
        title VARCHAR(200) NOT NULL,
        meta_description TEXT,
        content LONGTEXT,
        status ENUM('published', 'draft') DEFAULT 'draft',
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Create sections table (for homepage sections, etc.)
    $conn->exec("CREATE TABLE IF NOT EXISTS sections (
        id INT AUTO_INCREMENT PRIMARY KEY,
        section_name VARCHAR(100) NOT NULL,
        section_key VARCHAR(100) UNIQUE NOT NULL,
        heading VARCHAR(200),
        subheading VARCHAR(300),
        content LONGTEXT,
        button_text VARCHAR(50),
        button_link VARCHAR(255),
        image_url VARCHAR(255),
        display_order INT DEFAULT 0,
        status ENUM('active', 'inactive') DEFAULT 'active',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Create events table
    $conn->exec("CREATE TABLE IF NOT EXISTS events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        description TEXT,
        event_date DATE NOT NULL,
        event_time TIME,
        location VARCHAR(200),
        image_url VARCHAR(255),
        status ENUM('published', 'draft') DEFAULT 'draft',
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Create sermons table
    $conn->exec("CREATE TABLE IF NOT EXISTS sermons (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        preacher VARCHAR(100),
        sermon_date DATE NOT NULL,
        description TEXT,
        video_url VARCHAR(255),
        audio_url VARCHAR(255),
        thumbnail_url VARCHAR(255),
        scripture_reference VARCHAR(100),
        series VARCHAR(100),
        status ENUM('published', 'draft') DEFAULT 'draft',
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Create communities table
    $conn->exec("CREATE TABLE IF NOT EXISTS communities (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(100) UNIQUE NOT NULL,
        description TEXT,
        full_content LONGTEXT,
        meeting_day VARCHAR(50),
        meeting_time VARCHAR(50),
        meeting_location VARCHAR(100),
        image_url VARCHAR(255),
        contact_person VARCHAR(100),
        contact_email VARCHAR(100),
        status ENUM('active', 'inactive') DEFAULT 'active',
        display_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Create settings table
    $conn->exec("CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        setting_type VARCHAR(50) DEFAULT 'text',
        description VARCHAR(255),
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Create media table
    $conn->exec("CREATE TABLE IF NOT EXISTS media (
        id INT AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(255) NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        file_type VARCHAR(50),
        file_size INT,
        uploaded_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (uploaded_by) REFERENCES admins(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Insert default admin (username: admin, password: admin123)
    $defaultPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $conn->exec("INSERT IGNORE INTO admins (username, email, password, full_name, role)
                 VALUES ('admin', 'admin@garrisonmpc.org', '$defaultPassword', 'System Administrator', 'super_admin')");

    // Insert default settings
    $defaultSettings = [
        ['church_name', 'Garrison Methodist Presbyterian Church', 'text', 'Church Name'],
        ['church_address', 'Burma Camp, Accra', 'text', 'Church Address'],
        ['church_phone', '(+233) 243-3689', 'text', 'Church Phone'],
        ['church_email', 'info@garrisonmpc.org', 'email', 'Church Email'],
        ['sunday_service_time', '09:00 AM', 'text', 'Sunday Service Time'],
        ['tuesday_service_time', '9:00 AM', 'text', 'Tuesday Morning Service Time'],
        ['counselling_time', '14:00 AM', 'text', 'Counselling Session Time'],
        ['facebook_url', 'https://www.facebook.com/gmpcburmacamp', 'url', 'Facebook URL'],
        ['youtube_url', 'https://www.youtube.com/@gmpcburmacamp3405', 'url', 'YouTube URL'],
        ['tiktok_url', 'https://www.tiktok.com/@gmpcburmacamp', 'url', 'TikTok URL']
    ];

    $stmt = $conn->prepare("INSERT IGNORE INTO settings (setting_key, setting_value, setting_type, description) VALUES (?, ?, ?, ?)");
    foreach ($defaultSettings as $setting) {
        $stmt->execute($setting);
    }

    // Insert default communities
    $communities = [
        ['Audio Visual Team', 'audio-visual-team', 'Supporting worship through technology and media excellence', 'Monthly', 'Various', 'Church', 1],
        ['Choir', 'choir', 'Make a joyful noise unto the Lord through song', 'Thursday', '6:00 PM - 8:00 PM', 'Church Sanctuary', 2],
        ['Israel', 'israel', 'A special community within our church family', 'Contact us', 'Various', 'Church', 3],
        ['Junior Youth', 'junior-youth', 'Building young disciples for Christ', 'Sunday', 'After main service', 'Youth Hall', 4],
        ['Youth Fellowship', 'youth-fellowship', 'Empowering the next generation for Christ', 'Sunday', '4:00 PM - 6:00 PM', 'Youth Center', 5],
        ['Women\'s Fellowship', 'womens-fellowship', 'United in faith, love, and service', 'Tuesday', '10:00 AM - 12:00 PM', 'Fellowship Hall', 6],
        ['Men\'s Fellowship', 'mens-fellowship', 'Iron sharpening iron in faith and brotherhood', 'First Saturday', '7:00 AM - 9:00 AM', 'Men\'s Hall', 7],
        ['Children Service', 'children-service', 'Nurturing young hearts for Jesus', 'Sunday', '9:00 AM - 11:00 AM', 'Children\'s Wing', 8],
        ['Singing Band', 'singing-band', 'Making melody to the Lord with instruments and voices', 'Saturday', '3:00 PM - 5:00 PM', 'Music Room', 9],
        ['Ark of Praise', 'ark-of-praise', 'Lifting high the name of Jesus through worship', 'Wednesday', '6:30 PM - 8:30 PM', 'Worship Center', 10]
    ];

    $stmt = $conn->prepare("INSERT IGNORE INTO communities (name, slug, description, meeting_day, meeting_time, meeting_location, display_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($communities as $community) {
        $stmt->execute($community);
    }

    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Database Setup Complete</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
            .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 20px; border-radius: 5px; }
            .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 20px; border-radius: 5px; margin-top: 20px; }
            h1 { color: #333; }
            code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
            .credentials { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class='success'>
            <h1>✓ Database Setup Complete!</h1>
            <p>All tables have been created successfully.</p>
        </div>

        <div class='credentials'>
            <h2>Default Admin Credentials</h2>
            <p><strong>Username:</strong> admin</p>
            <p><strong>Password:</strong> admin123</p>
            <p><strong>Admin URL:</strong> <a href='admin/login.php'>admin/login.php</a></p>
        </div>

        <div class='warning'>
            <h2>⚠ Important Security Steps</h2>
            <ol>
                <li>Update your database credentials in <code>config.php</code></li>
                <li><strong>Change the default admin password immediately!</strong></li>
                <li>Delete or rename this <code>setup_database.php</code> file</li>
                <li>Make sure the <code>uploads</code> directory is writable (chmod 755)</li>
                <li>Add <code>.htaccess</code> file to protect your admin directory</li>
            </ol>
        </div>
    </body>
    </html>";

} catch(PDOException $e) {
    die("Setup failed: " . $e->getMessage());
}
?>

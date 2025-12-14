<?php
// Load site-wide settings and helper accessors

if (!function_exists('getSiteSettings')) {
    function getSiteSettings() {
        static $settings = null;
        if ($settings !== null) {
            return $settings;
        }

        $settings = [];
        try {
            $conn = getDBConnection();
            $stmt = $conn->query("SELECT setting_key, setting_value FROM settings");
            foreach ($stmt->fetchAll() as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (PDOException $e) {
            error_log('Settings load error: ' . $e->getMessage());
        }

        return $settings;
    }
}

if (!function_exists('getSetting')) {
    function getSetting($key, $default = '') {
        $settings = getSiteSettings();
        return $settings[$key] ?? $default;
    }
}

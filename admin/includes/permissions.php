<?php
/**
 * Permission and audit helpers.
 */
require_once __DIR__ . '/auth.php';

function canEditResource($type, $id) {
    if (!isLoggedIn()) {
        return false;
    }

    // Super admins can edit anything
    if (hasRole('super_admin')) {
        return true;
    }

    $allowedTypes = ['page','event','sermon','community','banner'];
    if (!in_array($type, $allowedTypes, true)) {
        return false;
    }

    // Admins/editors: must have explicit permission
    $adminId = $_SESSION['admin_id'];
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT can_edit FROM admin_permissions WHERE admin_id = ? AND resource_type = ? AND resource_id = ?");
        $stmt->execute([$adminId, $type, (int)$id]);
        $row = $stmt->fetch();
        return $row ? (bool)$row['can_edit'] : false;
    } catch (PDOException $e) {
        error_log("Permission check failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if the current admin can edit a specific page.
 */
function canEditPage($pageId) {
    return canEditResource('page', $pageId);
}

/**
 * Require page permission or stop.
 */
function requirePagePermission($pageId) {
    if (!canEditPage($pageId)) {
        header('HTTP/1.1 403 Forbidden');
        exit('You do not have permission to edit this page.');
    }
}

/**
 * Log an admin action to the audit trail.
 */
function log_audit($action, $entityType, $entityId = null, $description = '') {
    if (!isLoggedIn()) {
        return;
    }
    $adminId = $_SESSION['admin_id'];
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("INSERT INTO audit_logs (admin_id, action, entity_type, entity_id, description, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$adminId, $action, $entityType, $entityId, $description, $ip]);
    } catch (PDOException $e) {
        error_log("Audit log failed: " . $e->getMessage());
    }
}
?>

<?php
require_once __DIR__ . '/../includes/auth.php';

// Helper to convert shorthand ini sizes (e.g., 8M) to bytes
function ini_size_bytes($value) {
    $value = trim($value);
    $last = strtolower(substr($value, -1));
    $num = (int)$value;
    switch ($last) {
        case 'g': $num *= 1024;
        case 'm': $num *= 1024;
        case 'k': $num *= 1024;
    }
    return $num;
}

if (!isLoggedIn()) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => 0, 'error' => 'Authentication required. Please log in again.']);
    exit;
}

header('Content-Type: application/json');

// Basic validation
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['success' => 0, 'error' => 'Invalid request.']);
    exit;
}

// Detect cases where PHP discarded the upload because it exceeded limits
if (empty($_FILES) && !empty($_SERVER['CONTENT_LENGTH'])) {
    $postMax = ini_get('post_max_size') ?: '8M';
    http_response_code(400);
    echo json_encode(['success' => 0, 'error' => "Upload too large. Server limit: {$postMax}."]);
    exit;
}

if (!isset($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['success' => 0, 'error' => 'No file uploaded.']);
    exit;
}

$file = $_FILES['file'];
$uploadLimit = ini_size_bytes(ini_get('upload_max_filesize'));
$postLimit = ini_size_bytes(ini_get('post_max_size'));
$serverLimit = min($uploadLimit ?: PHP_INT_MAX, $postLimit ?: PHP_INT_MAX);

if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    $errMap = [
        UPLOAD_ERR_INI_SIZE => "File exceeds server limit (" . ini_get('upload_max_filesize') . ").",
        UPLOAD_ERR_FORM_SIZE => "File exceeds form limit.",
        UPLOAD_ERR_PARTIAL => "File upload was incomplete.",
        UPLOAD_ERR_NO_FILE => "No file was uploaded.",
        UPLOAD_ERR_NO_TMP_DIR => "Missing temp directory on server.",
        UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk.",
        UPLOAD_ERR_EXTENSION => "Upload blocked by a PHP extension.",
    ];
    $msg = $errMap[$file['error']] ?? 'Upload error.';
    echo json_encode(['success' => 0, 'error' => $msg]);
    exit;
}

$allowedTypes = [
    'image/jpeg' => ['ext' => '.jpg', 'dir' => 'images', 'max' => 5 * 1024 * 1024],
    'image/png' => ['ext' => '.png', 'dir' => 'images', 'max' => 5 * 1024 * 1024],
    'image/gif' => ['ext' => '.gif', 'dir' => 'images', 'max' => 5 * 1024 * 1024],
    'image/webp' => ['ext' => '.webp', 'dir' => 'images', 'max' => 5 * 1024 * 1024],
    'video/mp4' => ['ext' => '.mp4', 'dir' => 'videos', 'max' => 80 * 1024 * 1024],
    'video/webm' => ['ext' => '.webm', 'dir' => 'videos', 'max' => 80 * 1024 * 1024],
    'video/ogg' => ['ext' => '.ogv', 'dir' => 'videos', 'max' => 80 * 1024 * 1024],
    'audio/mpeg' => ['ext' => '.mp3', 'dir' => 'audio', 'max' => 30 * 1024 * 1024],
    'audio/mp3' => ['ext' => '.mp3', 'dir' => 'audio', 'max' => 30 * 1024 * 1024],
    'audio/mp4' => ['ext' => '.m4a', 'dir' => 'audio', 'max' => 30 * 1024 * 1024],
    'audio/x-m4a' => ['ext' => '.m4a', 'dir' => 'audio', 'max' => 30 * 1024 * 1024],
    'audio/wav' => ['ext' => '.wav', 'dir' => 'audio', 'max' => 40 * 1024 * 1024],
    'audio/x-wav' => ['ext' => '.wav', 'dir' => 'audio', 'max' => 40 * 1024 * 1024],
    'audio/ogg' => ['ext' => '.ogg', 'dir' => 'audio', 'max' => 30 * 1024 * 1024],
    'application/pdf' => ['ext' => '.pdf', 'dir' => 'documents', 'max' => 20 * 1024 * 1024],
    'application/msword' => ['ext' => '.doc', 'dir' => 'documents', 'max' => 20 * 1024 * 1024],
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['ext' => '.docx', 'dir' => 'documents', 'max' => 20 * 1024 * 1024],
];

$mime = mime_content_type($file['tmp_name']);
$extGuess = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$fallbackByExt = [
    'mp3' => 'audio/mpeg',
    'm4a' => 'audio/mp4',
    'wav' => 'audio/wav',
    'ogg' => 'audio/ogg',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'pdf' => 'application/pdf',
];

if (!isset($allowedTypes[$mime])) {
    if (isset($fallbackByExt[$extGuess]) && isset($allowedTypes[$fallbackByExt[$extGuess]])) {
        $mime = $fallbackByExt[$extGuess];
    } else {
        http_response_code(400);
        echo json_encode(['success' => 0, 'error' => 'Invalid file type. Supported: images, video (mp4/webm/ogg), audio (mp3/m4a/wav/ogg), documents (pdf/doc/docx).']);
        exit;
    }
}

$typeConfig = $allowedTypes[$mime];
$typeMax = $typeConfig['max'] ?? (5 * 1024 * 1024);
$maxSize = $serverLimit ? min($typeMax, $serverLimit) : $typeMax;
if ($file['size'] > $maxSize) {
    http_response_code(400);
    $limitMb = round($maxSize / (1024 * 1024));
    echo json_encode(['success' => 0, 'error' => "File too large (max {$limitMb}MB)."]);
    exit;
}

$ext = $typeConfig['ext'];
$folder = $typeConfig['dir'];
$safeName = bin2hex(random_bytes(8)) . $ext;
$targetDir = __DIR__ . '/../../uploads/' . $folder;

if (!is_dir($targetDir)) {
    mkdir($targetDir, 0775, true);
}

$uploadDir = realpath($targetDir);

if (!$uploadDir || !is_dir($uploadDir)) {
    http_response_code(500);
    echo json_encode(['success' => 0, 'error' => 'Upload directory missing.']);
    exit;
}

$targetPath = $uploadDir . DIRECTORY_SEPARATOR . $safeName;
if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    http_response_code(500);
    echo json_encode(['success' => 0, 'error' => 'Failed to save file.']);
    exit;
}

$publicUrlBase = defined('UPLOAD_URL') ? rtrim(UPLOAD_URL, '/') : '';
if ($publicUrlBase) {
    $publicUrl = $publicUrlBase . '/' . $folder . '/' . $safeName;
} else {
    $publicUrl = '/uploads/' . $folder . '/' . $safeName;
}

echo json_encode([
    'success' => 1,
    'file' => ['url' => $publicUrl]
]);

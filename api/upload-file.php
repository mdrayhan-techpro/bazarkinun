<?php
// api/upload-file.php
// Dedicated file upload endpoint for grocery list photos & product images
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['status' => 'error', 'message' => 'Method Not Allowed'], 405);
}

$type = $_GET['type'] ?? $_POST['type'] ?? 'lists'; // 'lists', 'products', or 'categories'
if ($type === 'products' || $type === 'categories') {
    // Admin / Manager authentication required for product & category media
    requirePortalAuth(['admin', 'author', 'manager', 'management']);
    $targetDir = __DIR__ . '/../uploads/' . $type . '/';
    $webPrefix = 'uploads/' . $type . '/';
} else {
    $type = 'lists';
    $targetDir = __DIR__ . '/../uploads/lists/';
    $webPrefix = 'uploads/lists/';
}

$allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

// 1. Multipart Form Upload
if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $fileTmp = $_FILES['file']['tmp_name'];
    $fileOrig = $_FILES['file']['name'];
    $fileSize = $_FILES['file']['size'];

    // Check size max 8MB
    if ($fileSize > 8 * 1024 * 1024) {
        jsonResponse(['status' => 'error', 'message' => 'File size exceeds 8MB limit.'], 400);
    }

    $ext = strtolower(pathinfo($fileOrig, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    if (!in_array($ext, $allowed)) {
        jsonResponse(['status' => 'error', 'message' => 'Only JPG, PNG, WEBP and GIF images are allowed.'], 400);
    }

    $newFilename = uniqid("{$type}_", true) . '.' . $ext;
    $destPath = $targetDir . $newFilename;

    if (move_uploaded_file($fileTmp, $destPath)) {
        jsonResponse([
            'status' => 'success',
            'message' => 'File uploaded successfully!',
            'file_url' => $webPrefix . $newFilename,
            'file_name' => $newFilename
        ]);
    } else {
        jsonResponse(['status' => 'error', 'message' => 'Failed to save uploaded file.'], 500);
    }
}

// 2. Base64 Upload in JSON or POST body
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
if (!empty($input['base64'])) {
    $base64Data = $input['base64'];
    if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $matches)) {
        $ext = strtolower($matches[1]);
        if ($ext === 'jpeg') $ext = 'jpg';

        if (!in_array($ext, $allowedExtensions)) {
            jsonResponse(['status' => 'error', 'message' => 'Only JPG, PNG, WEBP and GIF images are allowed.'], 400);
        }

        $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
        $decoded = base64_decode($base64Data);

        if ($decoded === false) {
            jsonResponse(['status' => 'error', 'message' => 'Invalid Base64 payload'], 400);
        }

        $newFilename = uniqid("{$type}_", true) . '.' . $ext;
        $destPath = $targetDir . $newFilename;

        if (file_put_contents($destPath, $decoded)) {
            jsonResponse([
                'status' => 'success',
                'message' => 'Base64 image saved successfully!',
                'file_url' => $webPrefix . $newFilename,
                'file_name' => $newFilename
            ]);
        } else {
            jsonResponse(['status' => 'error', 'message' => 'Could not write image to disk.'], 500);
        }
    } else {
        jsonResponse(['status' => 'error', 'message' => 'Invalid data URL format.'], 400);
    }
}

jsonResponse(['status' => 'error', 'message' => 'No file or image payload received.'], 400);

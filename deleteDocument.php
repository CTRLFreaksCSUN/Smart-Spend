<?php
header('Content-Type: application/json');

// Directory where files are uploaded
$uploadDir = 'uploads/';

// Get filename from POST
$fileName = $_POST['file'] ?? null;

if (!$fileName) {
    echo json_encode(['success' => false, 'message' => 'No filename provided']);
    exit;
}

// Sanitize the filename to prevent directory traversal
$fileName = basename($fileName);
$filePath = $uploadDir . $fileName;

if (!file_exists($filePath)) {
    echo json_encode(['success' => false, 'message' => 'File not found']);
    exit;
}

// Try to delete the file
if (unlink($filePath)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Could not delete file']);
}
<?php
/**
 * Get File Path Script
 * 
 * This script looks up the full path of an uploaded file by filename
 * Used by the browser-side OCR to process images
 */

// Make sure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get the filename from query parameter
$fileName = $_GET['filename'] ?? '';

if (empty($fileName)) {
    http_response_code(400);
    echo "Error: No filename provided";
    exit;
}

// Upload directory
$uploadDir = 'uploads/';

// Find the full path for this file
$filePath = '';
foreach (glob($uploadDir . '*_' . $fileName) as $path) {
    $filePath = $path;
    break;
}

if (!empty($filePath)) {
    // Return the file path
    echo $filePath;
} else {
    http_response_code(404);
    echo "Error: File not found";
}
?>
<?php
/**
 * Update Document Script
 * 
 * This script handles document processing for Smart Spend:
 * 1. Processes file uploads
 * 2. Returns file paths for browser-side OCR processing
 */

// Turn off error display, but log errors
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Make sure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get customer ID from session if available
$customerId = $_SESSION['c_id'] ?? uniqid('cust_');

// Store the customer ID in session if it's new
if (!isset($_SESSION['c_id'])) {
    $_SESSION['c_id'] = $customerId;
}

// Log the request type
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['documents'])) {
    handleFileUpload($customerId);
} else {
    sendJsonResponse([
        'status' => 'error',
        'message' => 'Invalid request'
    ]);
}

/**
 * Handle file upload processing
 * 
 * @param string $customerId Customer ID
 */
function handleFileUpload($customerId) {
    // Upload directory
    $uploadDir = 'uploads/';
    
    // Make sure upload directory exists
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            error_log("Failed to create upload directory: $uploadDir");
            sendJsonResponse([
                'status' => 'error',
                'message' => 'Could not create upload directory'
            ]);
        }
    }
    
    // Check if directory is writable
    if (!is_writable($uploadDir)) {
        error_log("Upload directory is not writable: $uploadDir");
        chmod($uploadDir, 0777);
        if (!is_writable($uploadDir)) {
            sendJsonResponse([
                'status' => 'error',
                'message' => 'Upload directory is not writable'
            ]);
        }
    }
    
    // Allowed file types
    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'application/pdf' => 'pdf',
        'text/plain' => 'txt',
        'text/csv' => 'csv'
    ];
    
    // Process uploaded files
    $uploadedFiles = [];
    $filePaths = [];
    
    foreach ($_FILES['documents']['tmp_name'] as $key => $tmpName) {
        // Skip empty uploads
        if (empty($tmpName) || !is_uploaded_file($tmpName)) {
            continue;
        }
        
        // Verify file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tmpName);
        $fileExt = strtolower(pathinfo($_FILES['documents']['name'][$key], PATHINFO_EXTENSION));
        
        // Log file info
        error_log("Processing file: " . $_FILES['documents']['name'][$key] . ", MIME: $mime, Extension: $fileExt");
        
        if (!in_array($mime, array_keys($allowedTypes)) || $allowedTypes[$mime] !== $fileExt) {
            error_log("Invalid file type: $mime / $fileExt");
            continue; // Skip invalid files
        }
        
        $fileName = basename($_FILES['documents']['name'][$key]);
        $uniqueId = uniqid();
        $targetPath = $uploadDir . $uniqueId . '_' . $fileName;
        
        // Try to move the uploaded file
        if (move_uploaded_file($tmpName, $targetPath)) {
            error_log("File uploaded successfully: $targetPath");
            $uploadedFiles[] = $fileName;
            $filePaths[] = $targetPath;
        } else {
            error_log("Failed to move uploaded file from $tmpName to $targetPath");
        }
    }
    
    // Send response back
    if (!empty($uploadedFiles)) {
        $uploadMessage = "Successfully uploaded: " . implode(", ", $uploadedFiles);
        
        sendJsonResponse([
            'status' => 'success',
            'message' => $uploadMessage,
            'files' => $uploadedFiles,
            'paths' => $filePaths,
            'c_id' => $customerId
        ]);
    } else {
        sendJsonResponse([
            'status' => 'error',
            'message' => 'No valid files were uploaded.'
        ]);
    }
}

/**
 * Send JSON response
 * 
 * @param array $data Response data
 */
function sendJsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>
<?php
/**
 * Receipt Extractor with Enhanced PDF Support
 * 
 * Extracts business name and total amount from receipt images and documents
 * and stores the data in AWS DynamoDB
 */

// Increase memory and execution time limits for processing
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 300); // 5 minutes
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\Exception\AwsException;
use Dotenv\Dotenv;

require __DIR__.'/vendor/autoload.php';

// Create debug log function
function debug_log($message) {
    error_log($message);
}

// Load environment variables
try {
    if (file_exists(__DIR__ . '/.env')) {
        $env = Dotenv::createImmutable(__DIR__);
        $env->load();
        
        // AWS Configuration
        $key = $_ENV["KEY"] ?? null;
        $secret = $_ENV["SECRET"] ?? null;
        $region = $_ENV["REGION"] ?? null;
    }
} catch (Exception $e) {
    debug_log("Error loading .env file: " . $e->getMessage());
}

/**
 * Extract business name and total amount from document text
 * 
 * @param string $text OCR text from receipt
 * @return array Array with business name and amount
 */
function extractReceiptData($text) {
    debug_log("Starting receipt extraction");
    
    $data = [
        'business' => 'Unknown Business',
        'amount' => 0
    ];
    
    // Skip empty text
    if (empty($text)) {
        debug_log("Text is empty, returning default values");
        return $data;
    }
    
    // Clean the text
    $text = str_replace(["\r"], "", $text);
    $originalText = $text; // Keep a copy of the original for fallback
    
    // Split into lines and clean
    $lines = explode("\n", $text);
    $cleanLines = [];
    
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (!empty($trimmed)) {
            $cleanLines[] = $trimmed;
        }
    }
    
    debug_log("Processing receipt with " . count($cleanLines) . " lines");
    debug_log("First 10 lines: " . implode(" | ", array_slice($cleanLines, 0, 10)));
    
    // Try to identify receipt type
    $isPayPal = preg_match('/(?:PayPal|Spotify\s+USA)/i', $text);
    
    if ($isPayPal) {
        debug_log("PayPal receipt format detected");
        
        // PAYPAL RECEIPT PROCESSING
        
        // Extract business name - very specific for PayPal
        if (preg_match('/Spotify\s+USA/i', $text)) {
            $data['business'] = 'Spotify';
            debug_log("Found business: Spotify");
        } elseif (preg_match('/Seller\s+info.+?([A-Za-z0-9\s]+)(?=support|Invoice|\$)/is', $text, $matches)) {
            $data['business'] = trim($matches[1]);
            debug_log("Found business from seller info: " . $data['business']);
        } elseif (preg_match('/([A-Za-z0-9\s]+(?:Inc|LLC|Ltd))\s/i', $text, $matches)) {
            $data['business'] = trim($matches[1]);
            debug_log("Found business with corporate suffix: " . $data['business']);
        }
        
        // Extract amount - try multiple patterns for PayPal
        
        // First try: Look for negative amount pattern (common in PayPal)
        if (preg_match('/[−\-]\$(\d+\.\d{2})/i', $text, $matches)) {
            $data['amount'] = floatval($matches[1]);
            debug_log("Found amount from negative pattern: " . $data['amount']);
        } 
        // Second try: Look for "Total $X.XX" format
        elseif (preg_match('/Total\s+\$(\d+\.\d{2})/i', $text, $matches)) {
            $data['amount'] = floatval($matches[1]);
            debug_log("Found amount from Total: " . $data['amount']);
        }
        // Third try: Look for "Purchase amount $X.XX" format
        elseif (preg_match('/Purchase\s+amount\s+\$(\d+\.\d{2})/i', $text, $matches)) {
            $data['amount'] = floatval($matches[1]);
            debug_log("Found amount from Purchase amount: " . $data['amount']);
        }
        // Fourth try: Look for two consecutive lines with "Total" and a dollar amount
        elseif (preg_match('/Total\s*\n\s*\$(\d+\.\d{2})/im', $text, $matches)) {
            $data['amount'] = floatval($matches[1]);
            debug_log("Found amount from Total with newline: " . $data['amount']);
        }
        // Final fallback: Just look for any dollar amount
        elseif (preg_match('/\$(\d+\.\d{2})/', $text, $matches)) {
            $data['amount'] = floatval($matches[1]);
            debug_log("Found amount from any dollar sign: " . $data['amount']);
        }
    } else {
        debug_log("Standard receipt format detected");
        
        // STANDARD RECEIPT PROCESSING
        
        // Look for "Welcome to" pattern (common in retail receipts)
        foreach ($cleanLines as $line) {
            if (preg_match('/welcome\s+to\s+(.*)/i', $line, $matches)) {
                // Extract just the main store name without store number
                $fullName = trim($matches[1]);
                // Remove the store number if it exists
                $businessName = preg_replace('/\s+#\d+$/', '', $fullName);
                $data['business'] = $businessName;
                debug_log("Found business name using 'Welcome to' pattern: " . $data['business']);
                break;
            }
        }
        
        // Second attempt: Check first few lines
        if ($data['business'] == 'Unknown Business' && !empty($cleanLines)) {
            $lineIndex = 0;
            $maxLinesToCheck = min(7, count($cleanLines));
            
            while ($lineIndex < $maxLinesToCheck) {
                $line = $cleanLines[$lineIndex];
                if (!preg_match('/^\d+\/\d+\/\d+|\d+:\d+|receipt|invoice|order|transaction|#\d+/i', $line)) {
                    // Extract just the main store name without store number
                    $fullName = $line;
                    // Remove the store number if it exists
                    $businessName = preg_replace('/\s+#\d+$/', '', $fullName);
                    $data['business'] = $businessName;
                    debug_log("Found business name using first lines strategy: " . $data['business']);
                    break;
                }
                $lineIndex++;
            }
        }
        
        // AMOUNT EXTRACTION
        
        // First priority: Look for total (not subtotal)
        $totalPattern = '/(?<!Sub)total\s*[\$\€\£]?\s*(\d+[\.,]\d{2})/i';
        if (preg_match($totalPattern, $text, $matches)) {
            $data['amount'] = floatval(str_replace(',', '.', $matches[1]));
            debug_log("Found total amount: " . $data['amount']);
        }
        // Also try "Total" at start of line
        elseif (preg_match('/^Total\s+[\$\€\£]?\s*(\d+[\.,]\d{2})/im', $text, $matches)) {
            $data['amount'] = floatval(str_replace(',', '.', $matches[1]));
            debug_log("Found total at line start: " . $data['amount']);
        }
        // Try for USD$ format
        elseif (preg_match('/USD\$\s*(\d+[\.,]\d{2})/i', $text, $matches)) {
            $data['amount'] = floatval(str_replace(',', '.', $matches[1]));
            debug_log("Found USD amount: " . $data['amount']);
        }
        // Fallback to Subtotal if needed
        elseif (preg_match('/Subtotal\s*[\$\€\£]?\s*(\d+[\.,]\d{2})/i', $text, $matches)) {
            $data['amount'] = floatval(str_replace(',', '.', $matches[1]));
            debug_log("Found subtotal amount: " . $data['amount']);
        }
        // Last resort: Look for any dollar amount near the bottom
        elseif (preg_match_all('/[\$\€\£]\s*(\d+[\.,]\d{2})/', $text, $matches)) {
            // Get all dollar amounts
            $amounts = [];
            foreach ($matches[1] as $match) {
                $amounts[] = floatval(str_replace(',', '.', $match));
            }
            if (!empty($amounts)) {
                // Use the largest as it's likely the total
                $data['amount'] = max($amounts);
                debug_log("Found amount using largest value: " . $data['amount']);
            }
        }
    }
    
    // Clean up business name
    if ($data['business'] != 'Unknown Business') {
        // Remove Inc, LLC, etc.
        $data['business'] = preg_replace('/(,?\s+Inc\.?|,?\s+LLC\.?|,?\s+Ltd\.?)$/i', '', $data['business']);
        // Trim and clean
        $data['business'] = trim($data['business']);
    }
    
    debug_log("Extraction complete - Business: " . $data['business'] . ", Amount: " . $data['amount']);
    return $data;
}

/**
 * Connect to AWS DynamoDB
 */
function connectToDatabase($key, $secret, $region) {
    debug_log("Connecting to AWS DynamoDB");
    
    try {
        $config = [
            'credentials' => [
                'key' => $key,
                'secret' => $secret
            ],
            'region' => $region,
            'version' => 'latest'
        ];
        
        $dbClient = new DynamoDbClient($config);
        return $dbClient;
    }
    catch (Exception $e) {
        debug_log('Failed to connect to AWS: ' . $e->getMessage());
        return null;
    }
}

/**
 * Store receipt data in AWS DynamoDB
 */
function storeReceiptData($businessName, $amount, $filePath, $customerId = null) {
    global $key, $secret, $region;
    
    debug_log("Storing receipt data:");
    debug_log("Business: " . $businessName);
    debug_log("Amount: " . $amount);
    debug_log("FilePath: " . $filePath);
    
    // Connect to AWS DynamoDB
    $dbClient = connectToDatabase($key, $secret, $region);
    
    if ($dbClient === null) {
        debug_log("Database connection failed");
        return [
            'status' => 'error',
            'message' => 'Could not connect to database'
        ];
    }
    
    try {
        // Generate IDs
        $docId = uniqid('doc_');
        $cId = $customerId ?? uniqid('cust_');
        
        // Create a marshaler to convert PHP arrays to DynamoDB format
        $marshaler = new Marshaler();
        
        // Prepare item data
        $item = [
            'doc_id' => $docId,
            'c_id' => $cId,
            'business_name' => $businessName,
            'amount' => $amount,
            'file_path' => $filePath,
            'upload_date' => date('Y-m-d H:i:s')
        ];
        
        // Convert to DynamoDB format
        $marshaledItem = $marshaler->marshalItem($item);
        
        // Add item to table
        $result = $dbClient->putItem([
            'TableName' => 'Documents',
            'Item' => $marshaledItem
        ]);
        
        return [
            'status' => 'success',
            'message' => 'Receipt data stored successfully',
            'doc_id' => $docId,
            'business' => $businessName,
            'amount' => $amount
        ];
    } catch (Exception $e) {
        debug_log('Error storing receipt data: ' . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Database error: ' . $e->getMessage(),
            'business' => $businessName,
            'amount' => $amount
        ];
    }
}

/**
 * Process a receipt document
 */
function processReceipt($filePath, $customerId = null, $extractedText = null) {
    debug_log("Processing receipt: " . $filePath);
    
    // If text wasn't provided, try to extract it
    $text = $extractedText;
    
    if (empty($text)) {
        $fileExt = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        // For text files, read directly
        if (in_array($fileExt, ['txt'])) {
            $text = file_get_contents($filePath);
            debug_log("Text file read directly with " . strlen($text) . " characters");
        }
        // For PDF files, use PDF Parser
        elseif ($fileExt === 'pdf') {
            debug_log("PDF detected, attempting to extract text");
            
            // Check if Smalot PDF Parser is available
            if (class_exists('Smalot\PdfParser\Parser')) {
                try {
                    debug_log("Using Smalot PDF Parser");
                    $parser = new \Smalot\PdfParser\Parser();
                    $pdf = $parser->parseFile($filePath);
                    $text = $pdf->getText();
                    debug_log("Extracted " . strlen($text) . " characters from PDF");
                } catch (Exception $e) {
                    debug_log("Error extracting PDF text: " . $e->getMessage());
                }
            } else {
                debug_log("Smalot PDF Parser not available, trying alternative methods");
                
                // Try pdftotext if available (Linux/Mac with poppler-utils)
                if (function_exists('exec')) {
                    debug_log("Trying pdftotext command line tool");
                    $output = [];
                    $returnVar = 0;
                    exec("pdftotext \"$filePath\" - 2>&1", $output, $returnVar);
                    
                    if ($returnVar === 0 && !empty($output)) {
                        $text = implode("\n", $output);
                        debug_log("Extracted " . strlen($text) . " characters using pdftotext");
                    } else {
                        debug_log("pdftotext failed or not installed: " . implode("\n", $output));
                    }
                }
                
                // Last resort: try to extract text from PDF using PHP only
                if (empty($text)) {
                    debug_log("Attempting basic text extraction");
                    $content = file_get_contents($filePath);
                    
                    // Extract text strings from binary PDF
                    if (preg_match_all('/[\w\s\.,:;\-\$]{10,}/', $content, $matches)) {
                        $text = implode("\n", $matches[0]);
                        debug_log("Extracted " . strlen($text) . " characters with basic extraction");
                    } else {
                        debug_log("Could not extract text from PDF with basic method");
                    }
                }
            }
        }
    } else {
        debug_log("Using provided OCR text with " . strlen($text) . " characters");
    }
    
    // Extract data from the text
    $data = extractReceiptData($text);
    
    // Store in database
    $result = storeReceiptData(
        $data['business'],
        $data['amount'],
        $filePath,
        $customerId
    );
    
    return $result;
}

// API endpoint for processing OCR text
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_receipt') {
    debug_log("Received process_receipt request");
    
    // Get parameters
    $filePath = $_POST['file_path'] ?? '';
    $extractedText = $_POST['text'] ?? '';
    $customerId = $_POST['c_id'] ?? null;
    
    debug_log("Parameters - File: $filePath, Text length: " . strlen($extractedText));
    
    // Basic validation
    if (empty($filePath)) {
        debug_log("Missing file path");
        sendJsonResponse([
            'status' => 'error',
            'message' => 'Missing file path'
        ]);
    }
    
    // Process the receipt
    $result = processReceipt($filePath, $customerId, $extractedText);
    
    // Return JSON response
    sendJsonResponse($result);
}

/**
 * Send JSON response
 */
function sendJsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>
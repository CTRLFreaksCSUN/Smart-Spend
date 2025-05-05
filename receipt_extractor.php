<?php
/**
 * Receipt Extractor
 * 
 * Extracts business name and total amount from receipt images and documents
 * and stores the data in AWS DynamoDB
 */

// Increase memory and execution time limits for processing
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 300); // 5 minutes
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Marshaler;
use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\Exception\UnrecognizedClientException;
use Aws\Exception\ValidationException;

use Dotenv\Dotenv;
require __DIR__.'/vendor/autoload.php';

// Load environment variables
try {
    $env = Dotenv::createImmutable(__DIR__);
    $env->load();
    
    // AWS Configuration from your .env file
    $key = $_ENV["KEY"];
    $secret = $_ENV["SECRET"];
    $region = $_ENV["REGION"];
    $endPt = $_ENV["ENDPOINT"];
    
    // Log successful env loading
    error_log("Environment variables loaded successfully");
} catch (Exception $e) {
    error_log("Error loading .env file: " . $e->getMessage());
    // Set defaults if .env fails
    $key = '';
    $secret = '';
    $region = 'us-east-1';
    $endPt = null;
}

/**
 * Extract business name and total amount from document text
 * 
 * @param string $text OCR text from receipt
 * @return array Array with business name and amount
 */
function extractReceiptData($text) {
    // For debugging
    error_log("Extracting receipt data from text (" . strlen($text) . " chars)");
    
    $data = [
        'business' => 'Unknown Business',
        'amount' => 0
    ];
    
    // Skip empty text
    if (empty($text)) {
        error_log("Text is empty, returning default values");
        return $data;
    }
    
    // Clean the text
    $text = str_replace(["\r"], "", $text);
    $lines = explode("\n", $text);
    $cleanLines = [];
    
    // Clean up lines and remove empty ones
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (!empty($trimmed)) {
            $cleanLines[] = $trimmed;
        }
    }
    
    error_log("Cleaned text has " . count($cleanLines) . " non-empty lines");
    
    // BUSINESS NAME EXTRACTION
    error_log("Attempting to extract business name");
    
    // Strategy 1: Look for "Welcome to" pattern (common in retail receipts)
    foreach ($cleanLines as $line) {
        if (preg_match('/welcome\s+to\s+(.*)/i', $line, $matches)) {
            // Extract just the main store name without store number
            $fullName = trim($matches[1]);
            // Remove the store number if it exists
            $businessName = preg_replace('/\s+#\d+$/', '', $fullName);
            $data['business'] = $businessName;
            error_log("Found business name using 'Welcome to' pattern: " . $data['business']);
            break;
        }
    }
    
    // Strategy 2: Check first few lines (often contains business name)
    if ($data['business'] == 'Unknown Business' && !empty($cleanLines)) {
        $lineIndex = 0;
        $maxLinesToCheck = min(5, count($cleanLines));
        
        while ($lineIndex < $maxLinesToCheck) {
            $line = $cleanLines[$lineIndex];
            if (!preg_match('/^\d+\/\d+\/\d+|\d+:\d+|receipt|invoice|order|transaction|#\d+/i', $line)) {
                // Extract just the main store name without store number
                $fullName = $line;
                // Remove the store number if it exists
                $businessName = preg_replace('/\s+#\d+$/', '', $fullName);
                $data['business'] = $businessName;
                error_log("Found business name using first lines strategy: " . $data['business']);
                break;
            }
            $lineIndex++;
        }
    }
    
    // TOTAL AMOUNT EXTRACTION - PRIORITIZE TOTAL OVER SUBTOTAL
    error_log("Attempting to extract total amount");
    
    // First priority: Look for "Total" specifically (for final amount, not subtotal)
    $totalPatterns = [
        // Match lines with "Total" but not "Subtotal"
        '/(?<!Sub)total\s*[\$\€\£]?\s*(\d+[\.,]\d{2})/i',  
        '/(?<!Sub)total\s*:?\s*[\$\€\£]?\s*(\d+[\.,]\d{2})/i',
        // Specific patterns for common receipt formats
        '/total\s*amount\s*:?\s*[\$\€\£]?\s*(\d+[\.,]\d{2})/i',
        '/grand\s*total\s*:?\s*[\$\€\£]?\s*(\d+[\.,]\d{2})/i',
        // Look for lines that start with Total and have a currency amount
        '/^total\s*[\$\€\£]?\s*(\d+[\.,]\d{2})/im',
        // Last attempt - find USD$ near the amount
        '/USD\$\s*(\d+[\.,]\d{2})/i'
    ];
    
    // Try each total pattern first
    foreach ($totalPatterns as $pattern) {
        if (preg_match($pattern, $text, $matches)) {
            $data['amount'] = floatval(str_replace(',', '.', $matches[1]));
            error_log("Found total amount using pattern '" . $pattern . "': " . $data['amount']);
            break;
        }
    }
    
    // Second priority: Use subtotal if total wasn't found
    if ($data['amount'] == 0) {
        if (preg_match('/subtotal\s*[\$\€\£]?\s*(\d+[\.,]\d{2})/i', $text, $matches)) {
            $data['amount'] = floatval(str_replace(',', '.', $matches[1]));
            error_log("Found subtotal amount as fallback: " . $data['amount']);
        }
    }
    
    // Third priority: Look for currency symbols in the last part of the receipt
    if ($data['amount'] == 0) {
        error_log("No amount found with patterns, trying currency symbol strategy");
        $lastLines = array_slice($cleanLines, -intval(count($cleanLines) / 3));
        $currencyPattern = '/[\$\€\£]\s*(\d+[\.,]\d{2})/';
        
        $amounts = [];
        foreach ($lastLines as $line) {
            if (preg_match_all($currencyPattern, $line, $matches)) {
                foreach ($matches[1] as $match) {
                    $amounts[] = floatval(str_replace(',', '.', $match));
                }
            }
        }
        
        if (!empty($amounts)) {
            $data['amount'] = max($amounts);
            error_log("Found amount using currency symbol: " . $data['amount']);
        }
    }
    
    error_log("Final extraction result - Business: " . $data['business'] . ", Amount: " . $data['amount']);
    return $data;
}

/**
 * Connect to AWS DynamoDB
 * 
 * @param string $key AWS access key
 * @param string $secret AWS secret key
 * @param string $region AWS region
 * @param string $endPt AWS endpoint
 * @return DynamoDbClient|null DynamoDB client or null on failure
 */
function connectToDatabase($key, $secret, $region, $endPt = null) {
    // Skip if credentials are missing
    if (empty($key) || empty($secret)) {
        error_log("Missing AWS credentials");
        return null;
    }
    
    try {
        $config = [
            'credentials' => [
                'key' => $key,
                'secret' => $secret
            ],
            'region' => $region,
            'version' => 'latest',
            'scheme' => 'http'
        ];
        
        // If endpoint is provided, use it
        if ($endPt !== null && !empty($endPt)) {
            $config['endpoint'] = $endPt;
        }
        
        $dbClient = DynamoDbClient::factory($config);
        
        // Create Documents table if it doesn't exist
        createDocumentTable($dbClient);
        
        return $dbClient;
    }
    catch(Exception $e) {
        error_log('Failed to connect to AWS: ' . $e->getMessage());
        return null;
    }
}

/**
 * Create document table in DynamoDB if it doesn't exist
 * 
 * @param DynamoDbClient $dbClient DynamoDB client
 * @return bool Success status
 */
function createDocumentTable($dbClient) {
    if ($dbClient === null) {
        return false;
    }
    
    try {
        // Check if table already exists
        try {
            $dbClient->describeTable(['TableName' => 'Documents']);
            // Table exists, no need to create
            return true;
        } catch (Exception $e) {
            // Table doesn't exist, create it
            error_log("Documents table doesn't exist, creating...");
        }
        
        $result = $dbClient->createTable([
            'AttributeDefinitions' => [
                [
                    'AttributeName' => 'doc_id',
                    'AttributeType' => 'S'
                ],
                [
                    'AttributeName' => 'c_id',
                    'AttributeType' => 'S'
                ]
            ],
            'BillingMode' => 'PAY_PER_REQUEST',
            'DeletionProtectionEnabled' => false,
            'KeySchema' => [
                [
                    'AttributeName' => 'doc_id',
                    'KeyType' => 'HASH'  // Partition key
                ],
                [
                    'AttributeName' => 'c_id',
                    'KeyType' => 'RANGE'  // Sort key
                ]
            ],
            'TableName' => 'Documents'
        ]);
        
        error_log("Documents table created successfully");
        return true;
    }
    catch (DynamoDbException $dbErr) {
        // Table might already exist
        if (strpos($dbErr->getMessage(), 'ResourceInUseException') !== false) {
            // Table exists, no problem
            error_log("Documents table already exists");
            return true;
        }
        error_log('Error creating documents table: ' . $dbErr->getMessage());
        return false;
    }
    catch (Exception $e) {
        error_log('Error creating documents table: ' . $e->getMessage());
        return false;
    }
}

/**
 * Store receipt data in AWS DynamoDB
 * 
 * @param string $businessName Business name
 * @param float $amount Total amount
 * @param string $filePath Path to the receipt file
 * @param string $customerId Customer ID
 * @return array Result status
 */
function storeReceiptData($businessName, $amount, $filePath, $customerId = null) {
    global $key, $secret, $region, $endPt;
    
    error_log("Starting to store receipt data - Business: $businessName, Amount: $amount");
    error_log("AWS Credentials - Key: " . (empty($key) ? "EMPTY" : "PROVIDED") . 
              ", Secret: " . (empty($secret) ? "EMPTY" : "PROVIDED") . 
              ", Region: $region, Endpoint: " . ($endPt ?? "NULL"));
    
    // Connect to AWS DynamoDB
    $dbClient = connectToDatabase($key, $secret, $region, $endPt);
    
    if ($dbClient === null) {
        error_log("Failed to connect to database - returned null");
        return [
            'status' => 'error',
            'message' => 'Could not connect to database'
        ];
    }
    
    error_log("Successfully connected to database");
    
    try {
        // Generate IDs
        $docId = uniqid('doc_');
        $cId = $customerId ?? uniqid('cust_');
        
        error_log("Generated IDs - DocID: $docId, CustomerID: $cId");
        
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
        
        error_log("Prepared item data: " . json_encode($item));
        
        // Convert to DynamoDB format
        $marshaledItem = $marshaler->marshalItem($item);
        
        error_log("Marshaled item for DynamoDB, about to execute putItem");
        
        // Add item to table
        $result = $dbClient->putItem([
            'TableName' => 'Documents',
            'Item' => $marshaledItem
        ]);
        
        error_log("Successfully stored data in DynamoDB");
        
        return [
            'status' => 'success',
            'message' => 'Receipt data stored successfully',
            'doc_id' => $docId
        ];
    } catch (Exception $e) {
        error_log('Error storing receipt data: ' . $e->getMessage());
        error_log('Error trace: ' . $e->getTraceAsString());
        return [
            'status' => 'error',
            'message' => 'Database error: ' . $e->getMessage()
        ];
    }
}

/**
 * Process a receipt document and extract/store business name and amount
 * 
 * @param string $filePath Path to the document file
 * @param string $customerId Customer ID (optional)
 * @param string $extractedText Optional OCR text
 * @return array Processing result
 */
function processReceipt($filePath, $customerId = null, $extractedText = null) {
    // If text wasn't provided, try to extract it
    $text = $extractedText;
    
    if (empty($text)) {
        $fileExt = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        // For text files, read directly
        if (in_array($fileExt, ['txt'])) {
            $text = file_get_contents($filePath);
        }
        // For other file types, we rely on client-side OCR
    }
    
    error_log("Processing receipt with text length: " . strlen($text));
    
    // Extract data from the text
    $data = extractReceiptData($text);
    
    error_log("Extracted business: " . $data['business']);
    error_log("Extracted amount: " . $data['amount']);
    
    // Store in database
    $result = storeReceiptData(
        $data['business'],
        $data['amount'],
        $filePath,
        $customerId
    );
    
    // Include extracted data in result
    $result['business'] = $data['business'];
    $result['amount'] = $data['amount'];
    
    return $result;
}

// API endpoint for processing OCR text
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'process_receipt') {
    // Get parameters
    $filePath = $_POST['file_path'] ?? '';
    $extractedText = $_POST['text'] ?? '';
    $customerId = $_POST['c_id'] ?? null;
    
    // Basic validation
    if (empty($filePath)) {
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
 * 
 * @param array $data Response data
 */
function sendJsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>
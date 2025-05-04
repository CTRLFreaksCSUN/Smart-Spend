<?php
session_start();
use Aws\DynamoDb\Marshaler;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;

// Load environment variables
use Dotenv\Dotenv;
require __DIR__.'/vendor/autoload.php';
$env = Dotenv::createImmutable(__DIR__);
$env->load();

// Check if verification is pending
if (!isset($_SESSION['pending_verification_email']) || !isset($_SESSION['verification_code'])) {
    header("Location: RegisterPage.php");
    exit();
}

// Process verification code submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verification_code'])) {
    $submittedCode = trim($_POST['verification_code']);
    $storedCode = $_SESSION['verification_code'];
    $email = $_SESSION['pending_verification_email'];
    
    // Connect to DynamoDB
    $key = $_ENV["KEY"];
    $secret = $_ENV["SECRET"];
    $region = $_ENV["REGION"];
    
    try {
        $dynamoDb = new DynamoDbClient([
            'region' => $region,
            'version' => 'latest',
            'scheme' => 'http',
            'credentials' => [
                'key' => $key,
                'secret' => $secret,
            ]
        ]);
        
        $marshaler = new Marshaler();
        
        // Get the user record to check the stored verification code and expiration time
        $result = $dynamoDb->getItem([
            'TableName' => 'Customer',
            'Key' => $marshaler->marshalItem([
                'c_id' => hash("sha256", $email)
            ])
        ]);
        
        if (!isset($result['Item'])) {
            $_SESSION['verification_error'] = "User not found. Please register again.";
            header("Location: RegisterPage.php");
            exit();
        }
        
        $userData = $marshaler->unmarshalItem($result['Item']);
        
        // Check if code has expired
        $currentTime = time();
        $expirationTime = isset($userData['code_expiration']) ? intval($userData['code_expiration']) : 0;
        
        if ($currentTime > $expirationTime) {
            $_SESSION['verification_error'] = "Verification code has expired. Please register again.";
            // Clear verification session data
            unset($_SESSION['pending_verification_email']);
            unset($_SESSION['verification_code']);
            unset($_SESSION['user_fname']);
            header("Location: RegisterPage.php");
            exit();
        }
        
        // Verify the code matches the one in the database
        $dbVerificationCode = $userData['verification_code'] ?? '';
        
        if ($submittedCode === $dbVerificationCode) {
            // Update user as verified - THIS IS THE CRITICAL PART
            $updateResult = $dynamoDb->updateItem([
                'TableName' => 'Customer',
                'Key' => $marshaler->marshalItem([
                    'c_id' => hash("sha256", $email)
                ]),
                'UpdateExpression' => 'SET is_verified = :verified',
                'ExpressionAttributeValues' => $marshaler->marshalItem([
                    ':verified' => true
                ]),
                'ReturnValues' => 'UPDATED_NEW'
            ]);
            
            // For debugging, log the update result
            error_log("DynamoDB update result: " . print_r($updateResult, true));
            
            // Clear verification session data
            unset($_SESSION['pending_verification_email']);
            unset($_SESSION['verification_code']);
            
            // Set success message and create login session
            $_SESSION['verification_success'] = true;
            $_SESSION['login'] = true;
            $_SESSION['email'] = $email;
            
            // Redirect to ContinuedRegPage or Dashboard
            header("Location: ContinuedRegPage.php");
            exit();
            
        } else {
            // Invalid code
            $_SESSION['verification_error'] = "Invalid verification code. Please try again.";
            header("Location: RegisterPage.php");
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['verification_error'] = "Database error: " . $e->getMessage();
        error_log("DynamoDB Error in verification: " . $e->getMessage());
        header("Location: RegisterPage.php");
        exit();
    }
} else {
    // Invalid request
    header("Location: RegisterPage.php");
    exit();
}
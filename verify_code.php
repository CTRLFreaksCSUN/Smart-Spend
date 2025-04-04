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
    $expirationTime = $_SESSION['code_expiration'] ?? 0;

     // Check if code has expired
    if (time() > $expirationTime) {
        $_SESSION['verification_error'] = "Verification code has expired. Please register again.";
        // Clear verification session data
        unset($_SESSION['pending_verification_email']);
        unset($_SESSION['verification_code']);
        unset($_SESSION['user_fname']);
        unset($_SESSION['code_expiration']);
        header("Location: RegisterPage.php");
        exit();
    }
    
    // Verify the code
    if ($submittedCode === $storedCode) {
        // Connect to DynamoDB
        $key = $_ENV["KEY"];
        $secret = $_ENV["SECRET"];
        $region = $_ENV["REGION"];
        
        try {
            $dynamoDb = new DynamoDbClient([
                'region' => $region,
                'version' => 'latest',
                'credentials' => [
                    'key' => $key,
                    'secret' => $secret,
                ]
            ]);
            
            // Update user as verified
            $dynamoDb->updateItem([
                'TableName' => 'Customer',
                'Key' => [
                    'c_id' => [
                        'S' => hash("sha256", $email)
                    ]
                ],
                'UpdateExpression' => 'SET is_verified = :verified',
                'ExpressionAttributeValues' => [
                    ':verified' => ['BOOL' => true]
                ]
            ]);
            
            // Clear verification session data
            unset($_SESSION['pending_verification_email']);
            unset($_SESSION['verification_code']);
            unset($_SESSION['user_fname']);
            
            // Set success message
            $_SESSION['verification_success'] = true;
            
            // Redirect to ContinuedRegPage
            header("Location: ContinuedRegPage.php");
            exit();
            
        } catch (Exception $e) {
            $_SESSION['verification_error'] = "Database error: " . $e->getMessage();
            header("Location: RegisterPage.php");
            exit();
        }
    } else {
        // Invalid code
        $_SESSION['verification_error'] = "Invalid verification code. Please try again.";
        header("Location: RegisterPage.php");
        exit();
    }
} else {
    // Invalid request
    header("Location: RegisterPage.php");
    exit();
}
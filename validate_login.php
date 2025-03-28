<?php
use Aws\DynamoDb\Marshaler;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use DynamoDb\DynamoDBAttribute;
use DynamoDb\DynamoDBService;

use Dotenv\Dotenv;
require __DIR__.'/vendor/autoload.php';

$env = Dotenv::createImmutable(__DIR__);
$env->load();

$email = $password = "";
$errorMsg = [];
$showBothErrors = false;

function scan_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    if (empty($_POST["email"])) {
        $errorMsg['email'] = "Please enter your email";
    } else {
        $email = scan_input($_POST["email"]);
    }
    
    if (empty($_POST["password"])) {
        $errorMsg['password'] = "Please enter your password";
    } else {
        $password = scan_input($_POST["password"]);
    }
    
    if (empty($errorMsg)) {
        try {
            // Initialize DynamoDB client
            $dynamoDb = new DynamoDbClient([
                'region'  => $_ENV['AWS_REGION'],
                'version' => 'latest',
                'credentials' => [
                    'key'    => $_ENV['AWS_ACCESS_KEY_ID'],
                    'secret' => $_ENV['AWS_SECRET_ACCESS_KEY'],
                ]
            ]);
            
            $marshaler = new Marshaler();
            
            // Query DynamoDB for user
            $result = $dynamoDb->getItem([
                'TableName' => 'Users',
                'Key' => $marshaler->marshalItem([
                    'email' => $email
                ])
            ]);
            
            if (!empty($result['Item'])) {
                $storedPassword = $marshaler->unmarshalValue($result['Item']['password']);
                
                if (password_verify($password, $storedPassword)) {
                    session_start();
                    $_SESSION['loggedin'] = true;
                    $_SESSION['email'] = $email;
                    header('Location: DashboardPage.php');
                    exit;
                } else {
                    $errorMsg['auth'] = "Invalid email or password";
                }
            } else {
                $errorMsg['auth'] = "Invalid email or password";
            }
        } catch (DynamoDbException $e) {
            $errorMsg['system'] = "Login service unavailable. Please try again later.";
            error_log("DynamoDB Error: " . $e->getMessage());
        }
    }
    
    $showBothErrors = true;
}
?>

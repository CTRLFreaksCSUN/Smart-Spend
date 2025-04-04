<?php
session_start();
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

//If request is to sign in, verify valid fields
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
    //If fields are filled, search for credentials in database
    if (empty($errorMsg)) {
        try {
            // Initialize DynamoDB client
            $dynamoDb = new DynamoDbClient([
                'region' => $_ENV['REGION'],
                'version' => 'latest',
                'credentials' => [
                    'key' => $_ENV['KEY'],
                    'secret' => $_ENV['SECRET'],
                ]
            ]);

            $marshaler = new Marshaler();
            
            // Query DynamoDB for user
            $result = $dynamoDb->getItem([
                'TableName' => 'Customer',
                'Key' => $marshaler->marshalItem([
                    'c_id' => hash("sha256", $email) //hash email as primary key see if key is found in db
                ])
            ]);
            //Search for password hash value
            if (!empty($result['Item'])) {
                $storedPassword = $marshaler->unmarshalValue($result['Item']['passwd']);
                //Match password with hash
                //If matching, user is authenticated and session starts
                if (password_verify($password, $storedPassword)) {
                    // Check if email is verified
                    $isVerified = isset($result['Item']['is_verified']) ? 
                                 $marshaler->unmarshalValue($result['Item']['is_verified']) : 
                                 false;
                    
                    if ($isVerified) {
                        // Email is verified, allow login
                        $_SESSION['login'] = true;
                        $_SESSION['email'] = $email;
                        header('Location: DashboardPage.php');
                        exit;
                    } 
                    else {
                        // Email not verified, show error
                        $errorMsg['auth'] = "Please verify your email before logging in. Check your inbox or register again.";
                    }
                } else {
                    $errorMsg['auth'] = "Invalid email or password";
                }
            }

            else {
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

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
                'scheme' => 'http',
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
                        $_SESSION['user_fname'] = $marshaler->unmarshalValue($result['Item']['Fname']);
                        $_SESSION['user_lastname'] = $marshaler->unmarshalValue($result['Item']['Lname']);
                        $_SESSION['user_middlename'] = $marshaler->unmarshalValue($result['Item']['Mname']);
                        header('Location: DashboardPage.php');
                        exit;
                    } 
                    else {
                        // Email not verified, set up verification page redirect
                        $userData = $marshaler->unmarshalItem($result['Item']);
                        
                        // Generate a new verification code if needed
                        $verificationCode = sprintf("%06d", mt_rand(100000, 999999));
                        $expirationTime = time() + (24 * 60 * 60); // 24 hours from now
                        
                        // Update the verification code in the database
                        $dynamoDb->updateItem([
                            'TableName' => 'Customer',
                            'Key' => $marshaler->marshalItem([
                                'c_id' => hash("sha256", $email)
                            ]),
                            'UpdateExpression' => 'SET verification_code = :code, code_expiration = :expiry',
                            'ExpressionAttributeValues' => $marshaler->marshalItem([
                                ':code' => $verificationCode,
                                ':expiry' => $expirationTime
                            ])
                        ]);
                        
                        // Set up session for verification page
                        $_SESSION['pending_verification_email'] = $email;
                        $_SESSION['verification_code'] = $verificationCode;
                        $_SESSION['user_fname'] = $marshaler->unmarshalValue($result['Item']['Fname']);
                        
                        // Redirect to verify page - using RegisterPage.php since it has the verification UI
                        header('Location: RegisterPage.php');
                        exit;
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
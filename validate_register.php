<?php
// Add session_start() if not already at the top of the file
if (session_status() === PHP_SESSION_NONE) {
   session_start();
}
use Aws\DynamoDb\Marshaler;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use DynamoDb\DynamoDBAttribute;
use DynamoDb\DynamoDBService;

//Declare library for accessing secret storage
use Dotenv\Dotenv;
require __DIR__.'/vendor/autoload.php';
$env = Dotenv::createImmutable(__DIR__);
$env->load();

//Initilialize variables
$email = $password = $fName = $mName = $lName = $reEnPassw = "";
$errorMsg = "";
$nameErr = "";
$passwErr = "";
$dbClient = null;

//Check for empty credentials
$validated = checkEmptyCreds($email, $password, $fName, $lName, $reEnPassw);
//Access stored secrets to retrieve EC2 instance credentials
$key = $_ENV["KEY"];
$endPt = $_ENV["ENDPOINT"];
$region =  $_ENV["REGION"];
$secret = $_ENV["SECRET"];

//Connect to EC2 instance for managing DynamoDB 
if ($validated && empty($errorMsg)) {
   $dbClient = connectToDatabase($key, $secret, $region);
   $newAcc = createAccount($dbClient, $fName, $mName, $lName, $email, $password);
   
    //Verify that there are no field errors before signing up
    if ($newAcc && empty($errorMsg)) {
      // Instead of redirecting to ContinuedRegPage.php, let the page reload to show verification UI
      // The session vars set in createAccount will trigger the verification UI
      return;
   }
}



function checkEmptyCreds(&$email, &$password, &$fName, &$lName, &$reEnPassw) {
   //Analyze each field
   $creds = array('firstname', 'lastname', 'email', 'password', 're-password');
   $isValid = false;
   foreach ($creds as $cr) {
      if ($_SERVER["REQUEST_METHOD"] == "POST") {
         //Verify that all fields are not empty
         if (empty($_POST[$cr])) {
            $GLOBALS["errorMsg"] = "*Please fill in empty fields.*";
            return false;
         }

         switch($cr) {
            case 'firstname':
               $fName = scan_input($_POST[$cr]);
               break;
            case 'lastname':
               $lName = scan_input($_POST[$cr]);
               break;
            case 'email':
               $email = scan_input($_POST[$cr]);
               break;
            case 'password':
               $password = scan_input($_POST[$cr]);
               break;
            case 're-password':
               $reEnPassw = scan_input($_POST[$cr]);
               break;
         }
         //Verify that user input is valid
         $isValid = checkValidCredentials($cr);
      }
   }

   return $isValid;
}

function checkValidCredentials($cr) {
   switch($cr) {
      case 'firstname':
         return nameisValid($cr);

      case 'middlename':
         return nameisValid($cr);

      case 'lastname':
         return nameisValid($cr);

      case 'password':
         return PasswordisValid($cr);

      case 're-password':
         if ($_POST[$cr] == $_POST['password']) {
            return true;
         }

         $GLOBALS['errorMsg'] = "Please re-enter the same password.";
         return false;
   }
}

//Check if name is only alphabetical
function nameIsValid($cr) {
   $containsLetters = "/[a-z]{3,16}/i";
   if (preg_match($containsLetters, $_POST[$cr]) < 1) {
      $GLOBALS["errorMsg"] = $cr . ' must contain 3-16 alphabetic characters.';
      return false;
   }
   return true;
}

//Check that password contains all required characters
function PasswordIsValid($cr) {
   $letters = array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z');
   $numbers = array('1','2','3','4','5','6','7','8','9');
   $symbols = array('!','@','#','$','%','^','&','*','(',')','[',']','{','}','?','.',',','<','>',';',':','~','`','-','=','+','_');
   $containsAlpha = false;
   $containsNum = false;
   $containsSpecialChars = false;
   $password = $_POST[$cr];
   $passwordLen = strlen($password);
   $isPasswordLength = ($passwordLen >= 8) && ($passwordLen <= 32);
   //Check for any missing alphabetic, numerical, or special characters
   //And that the password is the correct length
   for ($i=0; $i < $passwordLen; $i++) {
      if (in_array($password[$i], $letters)) {
         $containsAlpha = true;
      }

      else if (in_array($password[$i], $numbers)) {
         $containsNum = true;
      }

      else if (in_array($password[$i], $symbols)) {
         $containsSpecialChars = true;
      }
   }
   if (!($containsAlpha) || !($containsNum) || !($containsSpecialChars) || !($isPasswordLength)) {
      $GLOBALS["errorMsg"] = $cr . ' must contain 8-32 characters, at least one special character($!@&^*#...) and digit.';
      return false;
   }
   return true;
}

//Filter user input for POST request
function scan_input($input) {
  $input = trim($input);
  $input = stripslashes($input);
  $input = htmlspecialchars($input);
  return $input;
}


function connectToDatabase($key, $secret, $region) {
try {
   $dbClient = DynamoDbClient::factory(array(
      'credentials' => array(
         'key' => $key,
         'secret' => $secret
         ),
      'region' => $region,
      'version' => 'latest',
      'scheme' => 'http'
   ));
   //Create collection (if does not exist)
   $db = createCollection($dbClient);
   return $dbClient;
}

catch(UnrecognizedClientException $ucerr) {
   echo 'Failed to connect to AWS: ' . $ucerr->getMessage();
}

catch(ValidationException $verr) {
   echo 'Failed to validate user: ' . $verr->getMessage();
}

catch(InvalidArgumentException $iaerr) {
   echo 'Invalid argument detected: ' . $iaerr->getMessage();
}

catch(ResourceNotFoundException $rerr) {
   echo 'Could not find requested resource: ' . $rerr->getMessage();
}
}


function createAccount($dbClient, $fName, $mName, $lName, $email, $password) {
   $item = $dbClient->getItem([
      'Key' => [
         'c_id' => [
           'S' => hash("sha256", $email)
         ]
      ],
      'TableName' => "Customer"
   ]);
   if (empty($item["Item"])) {
      // Generate a verification code
      $verificationCode = sprintf("%06d", mt_rand(100000, 999999));
       // Set expiration time (24 hours from now)
      $expirationTime = time() + (24 * 60 * 60);
      $dbClient->putItem([
         'Item' => [
            'Fname' => [
               'S' => $fName
            ], 
            'Mname' => [
               'S' => $mName
            ],
            'Lname' => [
               'S' => $lName
            ],
            'email' => [
               'S' => $email
            ],
            'passwd' => [
               'S' => password_hash($password, PASSWORD_BCRYPT)
            ],
            'c_id' => [
               'S' => hash('sha256', $email)
            ],
            'verification_code' => [
               'S' => $verificationCode
            ],
            'is_verified' => [
               'BOOL' => false
            ],
            'code_expiration' => [
            'N' => (string)$expirationTime
         ]
         ],
         'TableName' => "Customer"
      ]);
      // Store email and verification code in session
      $_SESSION['pending_verification_email'] = $email;
      $_SESSION['verification_code'] = $verificationCode;
      $_SESSION['user_fname'] = $fName;
      return true;
   } 
   $GLOBALS["errorMsg"] = "User already exists!";
   return false;
}


function createCollection($dbClient) {
   //Create table for user data
   $tablename = "Customer";
   $collec = null;

   //Check if table is already instantiated
   try {
   $collec = $dbClient->createTable([
      'AttributeDefinitions' => [
      [
         'AttributeName' => 'Fname',
         'AttributeType' => 'S'
      ],
      [
         'AttributeName' => 'Mname',
         'AttributeType' => 'S'
      ],
      [
         'AttributeName' => 'Lname',
         'AttributeType' => 'S'
      ],
      [
         'AttributeName' => 'email',
         'AttributeType' => 'S'
      ],
      [
         'AttributeName' => 'passwd',
         'AttributeType' => 'S'
      ],
      [
         'AttributeName' => 'address',
         'AttributeType' => 'S'
      ]
      ],
   'BillingMode' => 'PAY_PER_REQUEST',
   'DeletionProtectionEnabled' => false,
   'KeySchema' => [
      [
         'AttributeName' => 'c_id',
         'KeyType' => 'HASH'
      ],
   ],
   'AttributeDefinitions' => [
      [
         'AttributeName' => 'c_id',
         'AttributeType' => 'S'
      ], 
   ],
   'OnDemandThroughput' => [
      'MaxReadRequestUnits' => 25,
      'MaxWriteRequestUnits' => 25
   ],
   'SSESpecification' => [
      'Enabled' => false
   ],
   'StreamSpecification' => [
      'StreamEnabled' => true,
      'StreamViewType' => 'NEW_AND_OLD_IMAGES'
   ],
   'TableClass' => 'STANDARD',
   'TableName' => $tablename,
   'Tags' => [
      [
         'Key' => 'First Name',
         'Value' => 'Fname'
      ],
      [
         'Key' => 'Last Name',
         'Value' => 'Lname'
      ],
      [
         'Key' => 'Email',
         'Value' => 'email'
      ]
   ]
]
   );
}

catch (DynamoDbException $de) {
   error_log($de->getMessage(), 0);
}

finally {
   return $collec;
}
}
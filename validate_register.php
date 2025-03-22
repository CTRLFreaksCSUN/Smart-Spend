<?php
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
$email = $password = "";
$errorMsg = "";
$db = null;

//Access stored secrets to retrieve EC2 instance credentials
$key = $_ENV["KEY"];
$endPt = $_ENV["ENDPOINT"];
$region =  $_ENV["REGION"];

//Check for empty fields
$errorMsg = checkEmptyCreds($errorMsg);

//Connect to EC2 instance for managing DynamoDB 
try {
   $dbClient = DynamoDbClient::factory([
      'priv_key' => $key,
      'endpoint' => $endPt,
      'region' => $region
   ]);
   //Create collection (if does not exist)
   $db = createCollection();
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



function checkEmptyCreds($errMsg) {
   //Read each field from POST request
   //If field is empty, return error message
   if ($_SERVER["REQUEST_METHOD"] == "POST") { //
      if (empty($_POST["email"])) {
         $errMsg = "*Please enter your email.*";
      }

      else {
            //Remove unnecessary characters and whitespace from email
            $email = scan_input($_POST["email"]);
            if (empty($_POST["password"])) {
               $errMsg = "*Please enter your password.*";
            }

            else {
               //Remove unnecessary characters and whitespace from password
               $password = scan_input($_POST["password"]);
            }
      }
   }
   return $errMsg;
}

function scan_input($input) {
  $input = trim($input);
  $input = stripslashes($input);
  $input = htmlspecialchars($input);
  return $input;
}


function createCollection() {
   //Create table for user data
   $tablename = "User";
   $collec = null;

   //Check if table is already instantiated
   try {
   $collec = dbClient->createTable([
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
      ],
      [
         'AttributeName' => 'docs',
         'AttributeType' => 'BS'
      ],
      [
         'AttributeName' => 'u_id',
         'AttributeType' => 'N'
      ]
      ],
   'BillingMode' => 'PAY_PER_REQUEST',
   'DeletionProtectionEnabled' => true,
   'KeySchema' => [
      [
         'AttributeName' => 'u_id',
         'KeyType' => 'HASH'
      ]
   ],
   'OnDemandThroughput' => [
      'MaxReadRequestUnits' => 25,
      'MaxWriteRequestUnits' => 25
   ],
   'SSESpecification' => [
      'Enabled' => false,
      'SSEType' => 'AES256'
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
      ],
      [
         'Key' => 'Documents',
         'Value' => 'docs'
      ],
   ]
]
   );
}

catch (ResourceInUseException $e) {
   error_log("Table already exists!\n", 0);
}

finally {
   return $collec;
}
}
?>
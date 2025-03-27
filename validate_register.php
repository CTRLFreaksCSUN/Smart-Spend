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
$validated = checkEmptyCreds($email, $password, $errorMsg);

//Access stored secrets to retrieve EC2 instance credentials
$key = $_ENV["KEY"];
$endPt = $_ENV["ENDPOINT"];
$region =  $_ENV["REGION"];
$secret = $_ENV["SECRET"];

if ($validated && empty($errorMsg)) {
   // TODO: Save email and password into DynamoDB

   header("Location: ContinuedRegPage.php");
   exit(); 
}

//Connect to EC2 instance for managing DynamoDB 
try {
   $dbClient = DynamoDbClient::factory(array(
      'credentials' => array(
         'key' => $key,
         'secret' => $secret
         ),
      'region' => $region,
      'version' => 'latest'
   ));
   //Create collection (if does not exist)
   $db = createCollection($dbClient);
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



function checkEmptyCreds(&$email, &$password, &$errMsg) {
   if ($_SERVER["REQUEST_METHOD"] == "POST") {
      if (empty($_POST["email"])) {
         $errMsg = "*Please enter your email.*";
         return false;
      }

      $email = scan_input($_POST["email"]);

      if (empty($_POST["password"])) {
         $errMsg = "*Please enter your password.*";
         return false;
      }

      $password = scan_input($_POST["password"]);
      return true;
   }
   return false;
}

function scan_input($input) {
  $input = trim($input);
  $input = stripslashes($input);
  $input = htmlspecialchars($input);
  return $input;
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
   'BillingMode' => 'PROVISIONED',
   'DeletionProtectionEnabled' => false,
   'KeySchema' => [
      [
         'AttributeName' => 'c_id',
         'KeyType' => 'HASH'
      ],
      [
         'AttributeName' => 'f_id',
         'KeyType' => 'RANGE'
      ]
   ],
   'AttributeDefinitions' => [
      [
         'AttributeName' => 'c_id',
         'AttributeType' => 'N'
      ], 
      [
         'AttributeName' => 'f_id',
         'AttributeType' => 'N'
      ]
   ],
   'ProvisionedThroughput' => [
      'ReadCapacityUnits' => 1,
      'WriteCapacityUnits' => 1
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
      ],
      [
         'Key' => 'Documents',
         'Value' => 'docs'
      ],
   ]
]
   );
}

catch (ResourceInUseException $re) {
   error_log("Table already exists!\n", 0);
}

catch (DynamoDbException $de) {
   echo $de->getMessage();
}

finally {
   return $collec;
}
}


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
$errorMsg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (empty($_POST["email"])) {
     $errorMsg = "*Please enter your email.*";
  }

  else {
      $email = scan_input($_POST["email"]);
      if (empty($_POST["password"])) {
      $errorMsg = "*Please enter your password.*";
      }

      else {
         $password = scan_input($_POST["password"]);
      }
   }
}
?>
<?php
use Dotenv\Dotenv;
require __DIR__.'/vendor/autoload.php';
$env = Dotenv::createImmutable(__DIR__);
$env->load();

$email = $password = "";
$emailError = $passwordError = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if (empty($_POST["email"])) {
     $emailError = "Please enter your email.";
  }

  else {
     $email = scan_input($_POST["email"]);
  }
  
  if (empty($_POST["password"])) {
     $passwordError = "Please enter your password.";
  }

  else {
     $password = scan_input($_POST["password"]);
  }
}

$host = $_ENV["HOST"];
$dUser = $_ENV["USER"];
$dPassw =  $_ENV["PASSWORD"];

$connect = new mysqli($host, $dUser, $dPassw);
if ($connect->connect_error) {
   die("Failed to connect to database" . $connect->connect_error);
}

echo "Connected to database";
mysqli_close($connect);

function scan_input($input) {
  $input = trim($input);
  $input = stripslashes($input);
  $input = htmlspecialchars($input);
  return $input;
}
?>
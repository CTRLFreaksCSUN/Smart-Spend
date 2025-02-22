<?php
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
     $password = scan_input(POST["password"]);
  }
}


function scan_input($input) {
  $input = trim($input);
  $input = stripslashes($input);
  $input = htmlspecialchars($input);
  return $input;
}
?>
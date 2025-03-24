<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
    <meta name="author" content="CTRL_Freaks">
     <meta name="description" content="Proof of concept for Smart Spend register.">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="refresh" content="60">
    <title>Smart Spend(POC)-Register</title>
      <link rel="stylesheet" href="Registerstyle.css?v=<?php echo time(); ?>">
</head>
<body>
  <header>
  <h1> <img src="images\SmartSpendLogo.png"
        alt="Smart Spend" style="width:90px; height:80px;"> Smart Spend</h1>
  </header>
  <br>

  <?php 
  $path = __DIR__; 
  include $path . '\validate_register.php';?>

 <div class="register">
     <form method="POST"> 
  <h2>Sign Up</h2>
  <span class="error"><?php echo $errorMsg ?></span>
    <br>
   <br>
  <label for="firstname" style="padding-right:18px;">Enter your First Name: </label>
      <input type="text" id="firstname" name="firstname" placeholder="John" required> <br>
  <label for="middlename" style="padding-right:0px;">Enter your Middle Name: </label>
      <input type="text" id="middlename" name="middlename" placeholder="[ Optional ]"> <br>
  <label for="lastname" style="padding-right:20px;">Enter your Last Name: </label>
      <input type="text" id="lastname" name="lastname" placeholder="Doe" required> <br>
        <label for="email" style="padding-right:60px;">Enter your Email: </label>
     <input type="text" id="email" name="email" placeholder="newuser123@gmail.com" required> <br>
  <label for="password" style="padding-right:34px;">Enter new Password: </label>
     <input type="password" id="password" name="password" placeholder="$Password1234" required> <br>
  <label for="password" style="padding-right:9px;">Re-enter new Password: </label>
     <input type="password" id="re-password" name="re-password" placeholder="$Password1234" required> <br>
    <br>
  <br>
     <input type="submit" id="register" name="register" value="Sign Up">
     </input>
     </form>
  <form action="LoginPage.php" target="_self">
     <input type="submit" id="login" name="login" value="Sign In"></input>
 </form>
 </div>
  <footer>
      &copy; CTRL_Freaks - 2025
  </footer>
</body>

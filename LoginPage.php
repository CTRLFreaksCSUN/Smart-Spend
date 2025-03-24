<!DOCTYPE html> 
<html lang = "en">
<head>
  <meta charset="UTF-8">
  <meta name="author" content="CTRL_Freaks">
  <meta name="description" content="Proof of concept for Smart Spend login.">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="refresh" content="60"> 
    <title>Smart Spend(POC)-Login</title>
      <link rel="stylesheet" href="Loginstyle.css?v=<?php echo time(); ?>">
      <link rel="preload" href="https://img.freepik.com/premium-vector/abstract-water-background-wave-illustration-art-concept_114588-20.jpg" as="image">
</head>
<body>
  <header>
  <h1> <img src="images/SmartSpendLogo.png"
        alt="Smart Spend" style="width:90px; height:80px;"> Smart Spend</h1>
  </header>

  <?php 
  $path = __DIR__;
  include $path . '/validate_login.php'?>

 <div class="log">
  <h2>Sign In</h2>
  <form method="POST">
    <span class="error"><?php echo $errorMsg?></span><br>
    <label for="email">Email:&emsp;&emsp;</label>
  <input type="text" id="email" name="email" placeholder="johndoe01@hotmail.com">
         <br>
    <label for="password" style="text-align:center">Password:&nbsp;&nbsp;</label>
  <input type="password" id="password" name="password" placeholder="@Password1234">
  <br>
  <button type="button" style="background:none; border:none; margin-left:162px">Forgot password?</button>
   <br>
        <input type="submit" id="signInBtn" name="login" value="Sign In" style="margin-top:100px">
 </form>
</div>
<form action="RegisterPage.php" target="_self">
 <div class="register">
    <p style="font-size:30px; color: rgb(0, 120, 220); margin-top:120px">Your money, your future&mdash;
    <br>spend smarter and save more with<br>AI-powered insights. Get started today!</p><br>
    <input type="submit" id="registerBtn" name="register" value="Sign Up" style="float:center; margin-top:185px; background-color: rgb(0, 90, 250); color: white;">
 </div>
 </form>
  <footer>
      &copy; CTRL_Freaks - 2025
  </footer>
</body>
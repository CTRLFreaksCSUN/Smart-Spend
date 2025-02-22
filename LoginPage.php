<!DOCTYPE html> 
<html lang = "en">
<head>
  <meta charset="UTF-8">
  <meta name="author" content="CTRL_Freaks">
  <meta name="description" content="Proof of concept for Smart Spend login.">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="refresh" content="60"> 
    <title>Smart Spend(POC)-Login</title>
      <link rel="stylesheet" href="Loginstyle.css">
      <link rel="preload" href="https://img.freepik.com/premium-vector/abstract-water-background-wave-illustration-art-concept_114588-20.jpg" as="image">
</head>
<body>
  <header>
	<h1> <img src="C:\Users\Owner\OneDrive\Pictures\SmartSpendLogo.png"
	      alt="Smart Spend" style="width:90px; height:80px;"> Smart Spend</h1>
  </header>
 <div class="log">
  <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
    <label for="email">Email:&emsp;&emsp;</label>
	<input type="text" id="email" name="email" placeholder="johndoe01@hotmail.com">
	<span style="color:red;">*<?php echo $emailError?>*</span>
         <br>
    <label for="password" style="text-align:center">Password:&nbsp;&nbsp;</label>
	<input type="password" id="password" name="password" placeholder="@FooBar5115">
	<br>
	<button type="button" style="background:none; border:none; margin-left:162px">Forgot password?</button>
	 <br>
        <input type="submit" id="signInBtn" name="login" value="Sign In" style="margin-top:100px">
 </form>
</div>
 <div class="register">
    <p style="font-size:30px; color: rgb(0, 120, 220); margin-top:120px">Your money, your future&mdash;
    <br>spend smarter and save more with<br>AI-powered insights. Get started today!</p><br>
    <input type="submit" id="registerBtn" name="register" value="Sign Up" style="float:center; margin-top:185px">
 </div>
  <footer>
      &copy; CTRL_Freaks - 2025
  </footer>
</body>
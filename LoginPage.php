<!DOCTYPE html> 
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="author" content="CTRL_Freaks">
  <meta name="description" content="Proof of concept for Smart Spend login.">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Smart Spend - Login</title>
  <link rel="stylesheet" href="Loginstyle.css?v=<?php echo time(); ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
  <div class="background-overlay"></div>
  
  <div class="container">
  <aside>
    <div class="logo-title">
      <img src="images/SmartSpendLogo.png" alt="Smart Spend Logo">
      <h2>Smart Spend</h2>
    </div>
    <nav>
      <a href="SplashPage.php#how"><i class="fas fa-lightbulb"></i> How to Use</a>
      <a href="SplashPage.php#about"><i class="fas fa-users"></i> About the Creators</a>
      <a href="LoginPage.php"><i class="fas fa-sign-in-alt"></i> Sign In</a>
    </nav>
  </aside>

    <?php 
    $path = __DIR__; //get current directory
    include $path . '/validate_login.php';// Include login validation script
    ?>

    <main class="content-wrapper">
      <div class="login-section">
        <div class="login-card glass-card center-content">
          <h2>Welcome Back</h2>
          <p class="subtitle">Sign in to your financial dashboard</p>
          
          <!-- Process form and return any validation errors or success messages -->
           <!-- action: form submits to itself for handling -->
          <form method="POST" class="login-form" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
    <!-- Display all error messages -->
    <?php if (!empty($errorMsg)): ?> <!-- if error message isn't empty-->
        <div class="error-messages">
            <?php if ($showBothErrors): ?> <!-- if multiple errors display them-->
                <?php if (isset($errorMsg['email'])): ?> <!-- check if email error exists -->
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $errorMsg['email']; ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($errorMsg['password'])): ?> <!-- check if password error exists -->
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $errorMsg['password']; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            <?php if (isset($errorMsg['auth'])): ?> <!-- check if authentication error exists -->
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $errorMsg['auth']; ?> 
                </div>
            <?php endif; ?>
            <?php if (isset($errorMsg['system'])): ?> <!-- checking system error -->
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $errorMsg['system']; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <div class="form-group">
        <label for="email">Email</label>
        <div class="input-with-icon">
            <i class="fas fa-envelope"></i>
            <input type="text" id="email" name="email" placeholder="johndoe@example.com" 
                   value="<?php echo htmlspecialchars($email ?? ''); ?>">
        </div>
    </div>
    
    <div class="form-group">
        <label for="password">Password</label>
        <div class="input-with-icon">
            <i class="fas fa-lock"></i>
            <input type="password" id="password" name="password" placeholder="••••••••">
        </div>
        <a href="#" class="forgot-password">Forgot password?</a>
    </div>
    
    <button type="submit" name="login" class="btn btn-primary">
        <i class="fas fa-sign-in-alt"></i> Sign In
    </button>
</form>


        </div>
      </div>
      
      <div class="register-section">
        <div class="register-content glass-card">
          <h2>Start Your Journey</h2>
          <p class="register-text">Your money, your future — spend smarter and save more with AI-powered insights.</p>
          <ul class="benefits-list">
            <li><i class="fas fa-chart-line"></i> Real-time spending analytics</li>
            <li><i class="fas fa-robot"></i> AI-powered recommendations</li>
            <li><i class="fas fa-piggy-bank"></i> Smart savings tools</li>
          </ul>
          <form action="RegisterPage.php" target="_self">
            <button type="submit" name="register" class="btn btn-secondary">
              <i class="fas fa-user-plus"></i> Create Account
            </button>
          </form>
        </div>
      </div>
    </main>
    
    <footer>
      &copy; CTRL_Freaks - 2025 | Smart Spend Financial Solutions
    </footer>
  </div>
</body>
</html>

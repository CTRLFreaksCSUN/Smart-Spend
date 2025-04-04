<?php
session_start(); // Add this at the top to enable sessions
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="author" content="CTRL_Freaks">
  <meta name="description" content="Proof of concept for Smart Spend register.">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Smart Spend - Register</title>
  <link rel="stylesheet" href="Registerstyle.css?v=<?php echo time(); ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
  <div class="background-overlay"></div>
  
  <div class="container">
    <header>
      <div class="logo-container">
        <img src="images/SmartSpendLogo.png" alt="Smart Spend" class="logo">
        <h1>Smart Spend</h1>
      </div>
    </header>

    <?php 
    $path = __DIR__; //get current directory
    include $path . '/validate_register.php'; // Include login validation script
    ?>

    <main class="register-container">
    <?php if(isset($_SESSION['pending_verification_email'])): ?>
        <!-- Verification UI -->
        <div class="register-card verification-container">
          <h2>Verify Your Email</h2>
          <p class="subtitle">We've sent a verification code to <strong><?php echo htmlspecialchars($_SESSION['pending_verification_email']); ?></strong></p>
          <p>Please check your inbox and enter the code below to complete your registration.</p>
          
          <?php if(isset($_SESSION['verification_error'])): ?>
            <div class="error-message">
              <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['verification_error']; ?>
            </div>
            <?php unset($_SESSION['verification_error']); ?>
          <?php endif; ?>
          
          <form method="POST" class="verification-form" action="verify_code.php">
            <div class="form-group">
              <div class="input-group verification-code-input">
                <input type="text" id="verification-code" name="verification_code" placeholder=" " maxlength="6">
                <label for="verification-code">Verification Code</label>
              </div>
            </div>
            
            <div class="form-actions">
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-check-circle"></i> Verify Email
              </button>
              
              <button type="button" id="resend-code" class="btn btn-secondary">
                <i class="fas fa-redo"></i> Resend Code
              </button>
            </div>

            <div class="login-link">
              <a href="RegisterPage.php?cancel=true">Cancel registration</a>
            </div>
          </form>
        </div>
        
        <!-- Create hidden iframe to run the email verification script -->
        <iframe id="email-frame" src="verification_email.php" style="display:none;"></iframe>
        
        <script>
          // Handle resend button
          document.getElementById('resend-code').addEventListener('click', function() {
            // Reload the iframe to trigger email sending again
            document.getElementById('email-frame').src = 'verification_email.php';
            alert('Verification code resent. Please check your email.');
          });
          
          // Listen for messages from the iframe
          window.addEventListener('message', function(event) {
            if (event.data === 'email_sent_success') {
              console.log('Email sent successfully');
            } else if (event.data === 'email_sent_error') {
              console.log('Failed to send email');
              alert('There was a problem sending the verification email. Please try again.');
            }
          });
        </script>
        <?php else : ?>
    <!-- Regular registration form -->
      <div class="register-card">
        <h2>Create Your Account</h2>
        <p class="subtitle">Join Smart Spend and take control of your finances</p>
        
        <?php if (!empty($errorMsg)): ?> <!-- if error message isn't empty -->
          <div class="error-message">
            <i class="fas fa-exclamation-circle"></i> <?php echo $errorMsg; ?>
          </div>
        <?php endif; ?>
        
        <form method="POST" class="register-form" target="_parent" onsubmit="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">

          <div class="form-group">
            <div class="input-group">
              <input type="text" id="firstname" name="firstname" placeholder=" " value="<?php echo htmlspecialchars($_POST['firstname'] ?? ''); ?>">
              <label for="firstname">First Name</label>
            </div>
          </div>
          
          <div class="form-group">
            <div class="input-group">
              <input type="text" id="middlename" name="middlename" placeholder=" " value="<?php echo htmlspecialchars($_POST['middlename'] ?? ''); ?>">
              <label for="middlename">Middle Name <span class="optional">(Optional)</span></label>
            </div>
          </div>
          
          <div class="form-group">
            <div class="input-group">
              <input type="text" id="lastname" name="lastname" placeholder=" " value="<?php echo htmlspecialchars($_POST['lastname'] ?? ''); ?>">
              <label for="lastname">Last Name</label>
            </div>
          </div>
          
          <div class="form-group">
            <div class="input-group">
              <input type="email" id="email" name="email" placeholder=" " value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
              <label for="email">Email Address</label>
            </div>
          </div>
          
          <div class="form-group">
            <div class="input-group">
              <input type="password" id="password" name="password" placeholder=" ">
              <label for="password">Password</label>
              <i class="fas fa-eye toggle-password"></i>
            </div>
          </div>
          
          <div class="form-group">
            <div class="input-group">
              <input type="password" id="re-password" name="re-password" placeholder=" ">
              <label for="re-password">Confirm Password</label>
              <i class="fas fa-eye toggle-password"></i>
            </div>
          </div>
          
          <div class="form-actions">
            <button type="submit" name="register" class="btn btn-primary">
              <i class="fas fa-user-plus"></i> Create Account
            </button>
            
            <div class="login-link">
              Already have an account? <a href="LoginPage.php">Sign In</a>
            </div>
          </div>
        </form>
      </div>
      <?php endif; ?>
    </main>
    
    <footer>
      &copy; CTRL_Freaks - 2025 | Smart Spend Financial Solutions
    </footer>
  </div>
  
  <?php
  // Handle registration cancellation
  if (isset($_GET['cancel']) && $_GET['cancel'] == 'true') {
    // Clear verification session data
    unset($_SESSION['pending_verification_email']);
    unset($_SESSION['verification_code']);
    unset($_SESSION['user_fname']);
    
    // Redirect to clear URL parameters
    header("Location: RegisterPage.php");
    exit();
  }
  ?>
  
  <script>
    // Toggle password visibility
    document.querySelectorAll('.toggle-password').forEach(icon => {
      icon.addEventListener('click', function() {
        const input = this.parentElement.querySelector('input');
        const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
        input.setAttribute('type', type);
        this.classList.toggle('fa-eye-slash');
      });
    });
  </script>
</body>
</html>

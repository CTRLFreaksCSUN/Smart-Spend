<?php
// verification_email.php - Handles email verification with EmailJS
session_start();

// Load environment variables
use Dotenv\Dotenv;
require __DIR__.'/vendor/autoload.php';
$env = Dotenv::createImmutable(__DIR__);
$env->load();

// Only allow access if there's a pending verification
if (!isset($_SESSION['pending_verification_email']) || !isset($_SESSION['verification_code'])) {
    echo "Unauthorized access";
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Email Verification</title>
</head>
<body>
    <!-- EmailJS Library Import -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/@emailjs/browser@4/dist/email.min.js"></script>
    
    <!-- Initialize EmailJS -->
    <script type="text/javascript">
        (function(){
            emailjs.init({
                publicKey: "bjjZtXvvqYNvMLCO4",
                blockHeadless: true,
                limitRate: {
                    id: 'app',
                    throttle: 10000
                }
            });
        })();
    </script>
    
    <!-- Send Email -->
    <script type="text/javascript">
        var templateArgs = {
            to_name: "<?php echo htmlspecialchars($_SESSION['user_fname']); ?>",
            to_email: "<?php echo htmlspecialchars($_SESSION['pending_verification_email']); ?>",
            verification_code: "<?php echo $_SESSION['verification_code']; ?>",
            from_name: "Smart Spend"
        };
        
        function sendVerificationEmail() {
            return emailjs.send("<?php echo $_ENV['SERVICE_ID']; ?>", "<?php echo $_ENV['TEMPLATE_ID'] ?? 'template_1zknteh'; ?>", templateArgs)
                .then(
                    function(response) {
                        console.log("Successfully sent email.", response);
                        window.parent.postMessage('email_sent_success', '*');
                        return true;
                    },
                    function(error) {
                        console.log("Unable to send email.", error);
                        window.parent.postMessage('email_sent_error', '*');
                        return false;
                    }
                );
        }
        
        // Send email immediately when page loads
        window.onload = function() {
            sendVerificationEmail();
        };
    </script>
</body>
</html>
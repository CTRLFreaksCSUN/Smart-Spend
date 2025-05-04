<?php
session_start();

use Aws\DynamoDb\Marshaler;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Dotenv\Dotenv;

require __DIR__.'/vendor/autoload.php';
$env = Dotenv::createImmutable(__DIR__);
$env->load();

// Sample user data
$user = [
    'name' => $_SESSION['user_fname'] . " " . $_SESSION['user_middlename'] . " " . $_SESSION['user_lastname'],
    'email' => $_SESSION['email'],
    'title' => '',
    'avatar' => 'images/ProfilePic.png',
    'join_date' => 'Joined January 2024',
    'stats' => [
        'transactions' => 142,
        'savings' => '$8,240',
        'budgets' => 7
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Smart Spend - Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="DashboardStyle.css">
    <link rel="stylesheet" href="ProfileStyle.css">
    <script>
        document.addEventListener('DOMContentLoaded', () => {
    const hash = window.location.hash.substring(1);
    if (hash) {
        switchView(null, hash);
    }
});
    </script>
</head>
<body>

<div class="profile-layout">
    <!-- Creative Side Navigation -->
    <aside class="profile-sidebar">
        <div class="sidebar-brand">
            <img src="images/SmartSpendLogo.png" alt="Smart Spend Logo" class="logo">
            <span>Smart Spend</span>
        </div>
        
        <nav class="sidebar-nav">
            <ul>
                <li class="active">
                    <a href="#">
                        <div class="nav-icon"><i class="fas fa-user-circle"></i></div>
                        <span>Profile</span>
                        <div class="nav-dot"></div>
                    </a>
                </li>
                <li>
                    <a href="DashboardPage.php">
                        <div class="nav-icon"><i class="fas fa-chart-pie"></i></div>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="#">
                        <div class="nav-icon"><i class="fas fa-bell"></i></div>
                        <span>Notifications</span>
                        <span class="nav-badge">3</span>
                    </a>
                </li>
                <li>
                    <a href="#">
                        <div class="nav-icon"><i class="fas fa-question-circle"></i></div>
                        <span>Help</span>
                    </a>
                </li>
            </ul>
        </nav>
        
        <form method="POST" target="_top">
        <div class="sidebar-footer">
            <div class="user-card">
                <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="User" class="user-avatar">
                <div>
                    <span class="user-name"><?php echo htmlspecialchars($user['name']); ?></span>
                    <span class="user-email"><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                <button type='submit' class="logout-btn" name='logout' style="cursor:pointer; background-color: #0d47a1;"><i class="fas fa-sign-out-alt"></i></button>
            </div>
        </div>
        </form>
        
    </aside>

    <!-- Display prompt to confirm log out -->
    <?php if (($_SERVER['REQUEST_METHOD'] == 'POST') && ((isset($_POST['logout'])))):?>
    <div class="confirm-box">
        <p>Do you want to log out?</p>
        <form method="POST">
        <section>
            <button type='submit' name='confirm-logout'>Yes</button>
            <button type='submit' name='cancel-logout'>No</button>
        </section>
    </form>
    </div>
    <?php endif;?>

    <?php include __DIR__ . '/validate_register.php';?>
    <!-- Confirm logout and close user session -->
    <?php
        if (isset($_POST['confirm-logout'])) {
            session_unset();
            header('Location: LoginPage.php');
            exit();
        }
    ?>

<?php 
    // Respond to user submission of new password
    if(isset($_POST['update-password'])) {
        $_SESSION['error'] = "";
        if ($_POST['new-password'] != $_POST['reEn-password']) {
            $_SESSION['error'] = "Please re-enter the same password.";
        }

        // Make sure that the user does not copy the same password
        else if ($_POST['new-password'] == $_POST['old-password']) {
            $_SESSION['error'] = "Please enter a new password.";
        }

        // Check if password meets strong password criteria
        else if (PasswordIsValid('new-password')) {
            try {
                $new_password = $_POST['new-password'];
                $client = new DynamoDbClient([
                    'region' => $_ENV['REGION'],
                    'version' => 'latest',
                    'credentials' => [
                        'key' => $_ENV['KEY'],
                        'secret' => $_ENV['SECRET']
                    ],
                    'scheme' => 'http'
                ]);

                $marshaler = new Marshaler();
                $items = $client->getItem([
                    'TableName' => 'Customer', 
                    'Key' => $marshaler->marshalItem([
                        'c_id' => hash("sha256", $user['email'])
                    ])
                    ]);

                // Check if password already exists in user's password history
                if (!empty($items['Item'])) {
                    if (isset($items['Item']['password_history'])) {
                        $existing = $marshaler->unmarshalValue($items['Item']['password_history']);
                        foreach($existing as $e) {
                            if (password_verify($new_password, $e)) {
                                $_SESSION['error'] = "You have already used this password.";
                                break;
                            }
                        }
                    }

                    // update password and add outdated password into user's password history
                    if (empty($_SESSION['error'])) {
                    $savedPassword = $marshaler->unmarshalValue($items['Item']['passwd']);
                    if (password_verify($_POST['old-password'], $savedPassword)) {
                        $client->UpdateItem([
                            'TableName' => 'Customer',
                            'Key' => $marshaler->marshalItem([
                                'c_id' => hash("sha256", $user['email'])
                            ]),
                            'UpdateExpression' => "SET passwd = :updated_passwd, password_history = list_append(if_not_exists(password_history, :empty_list), :prev_passwd)",
                            'ExpressionAttributeValues' => $marshaler->marshalItem([
                                ':updated_passwd' => password_hash($new_password, PASSWORD_BCRYPT),
                                ':prev_passwd' => [$savedPassword],
                                ':empty_list' => []
                            ])
                        ]);

                        unset($_SESSION['error']);
                        echo '<script>
                                alert("Successfully changed password.");
                                window.location.href = "ProfilePage.php";
                              </script>';
                    }
                    else {
                        $_SESSION['error'] = "Please enter your old password.";   
                    }
                }
                }
            }
            catch(DynamoDbException $err) {
                echo "Unable to update password: " . $err;
            }
        } 

        else 
            $_SESSION['error'] = "Password must contain 8-32 characters, at least one special character($!@&^*#...) and digit.'";
    }
    ?>

    <!-- Main Content Area -->
    <main class="profile-main">
        <div class="profile-header">
            <h1>My Profile</h1>
            <div class="header-actions">
                <button class="edit-btn"><i class="fas fa-pencil-alt"></i> Edit Profile</button>
            </div>
        </div>

        <div class="profile-content">
            <!-- User Card -->
            <div class="profile-card">
                <div class="profile-banner"></div>
                <div class="profile-info">
                    <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="Profile" class="profile-avatar">
                    <div class="profile-meta">
                        <h2><?php echo htmlspecialchars($user['name']); ?></h2>
                        <p class="profile-title"><?php echo htmlspecialchars($user['title']); ?></p>
                        <p class="profile-email"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                        <p class="profile-join"><i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($user['join_date']); ?></p>
                    </div>
                </div>
                
                <div class="profile-stats">
                    <div class="stat-item">
                        <div class="stat-icon" style="background-color: #e3f2fd;">
                            <i class="fas fa-exchange-alt" style="color: #1e88e5;"></i>
                        </div>
                        <div>
                            <span class="stat-value"><?php echo $user['stats']['transactions']; ?></span>
                            <span class="stat-label">Transactions</span>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-icon" style="background-color: #e8f5e9;">
                            <i class="fas fa-piggy-bank" style="color: #4caf50;"></i>
                        </div>
                        <div>
                            <span class="stat-value"><?php echo $user['stats']['savings']; ?></span>
                            <span class="stat-label">Total Savings</span>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-icon" style="background-color: #fff3e0;">
                            <i class="fas fa-chart-line" style="color: #ff9800;"></i>
                        </div>
                        <div>
                            <span class="stat-value"><?php echo $user['stats']['budgets']; ?></span>
                            <span class="stat-label">Active Budgets</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Sections -->
            <div class="profile-sections">
                <section class="profile-section">
                    <div class="section-header">
                        <h3><i class="fas fa-user-tag"></i> Personal Information</h3>
                        <button class="section-edit"><i class="fas fa-pencil-alt"></i></button>
                    </div>
                    <div class="section-content">
                        <div class="info-row">
                            <span class="info-label">Full Name</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['name']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Email</span>
                            <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Location</span>
                            <span class="info-value" id="location"></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Timezone</span>
                            <span class="info-value"><?php 
                            date_default_timezone_set('America/Los_Angeles');
                            echo date('T');?></span>
                        </div>
                    </div>
                </section>

                <form class="security">
                <section class="profile-section">
                    <div class="section-header">
                        <h3><i class="fas fa-lock"></i> Security</h3>
                        <button class="section-edit"><i class="fas fa-pencil-alt"></i></button>
                    </div>
                    <div class="section-content">
                        <div class="security-item">
                            <i class="fas fa-shield-alt security-icon"></i>
                            <div>
                                <h4>Password</h4>
                                <p>Last changed 3 months ago</p>
                            </div>
                            <button onclick="switchView(event, 'change-password')" class="security-action">Change</button>
                        </div>
                        <div class="security-item">
                            <i class="fas fa-mobile-alt security-icon"></i>
                            <div>
                                <h4>Two-Factor Authentication</h4>
                                <p>Not enabled</p>
                            </div>
                            <button class="security-action">Enable</button>
                        </div>
                    </div>
                </section>
            </form>
            </div>
        </div>

        <!-- Display as new view when user requests to change password -->
        <form class="profile-content change-password" method="POST" onsubmit='<?php echo htmlspecialchars($_SERVER['PHP_SELF']);?>'>
                <div>
                <h1>Change Password</h1>
                    <?php if (!empty($_SESSION['error'])): ?>
                        <span class='error'><?php echo $_SESSION['error'];?></span>
                    <?php endif;?>
                    <label for='old-password'>Old Password</label>
                    <input type='password' id='old-password' name='old-password'
                    value="<?php echo htmlspecialchars($_POST['old-password'] ?? '');?>" required/>
                    <label for='new-password'>New Password</label>
                    <input type='password' id='new-password' name='new-password' 
                    value="<?php echo htmlspecialchars($_POST['new-password'] ?? '');?>" required/>
                    <label for='reEn-password'>Re-Enter New Password</label>
                    <input type='password' id='reEn-password' name='reEn-password'
                    value="<?php echo htmlspecialchars($_POST['reEn-password'] ?? '');?>" required/>
                <section>
                    <a class='security-action' href='ProfilePage.php' onclick=<?php unset($_SESSION['error']);?>>Cancel</a>
                    <input class='security-action' type='submit' id='update-password' name='update-password'/>
                </section>   
            </div>
            </form>
    </main>
</div>

<script>
// Toggle active state for nav items
document.querySelectorAll('.sidebar-nav a').forEach(link => {
    link.addEventListener('click', function(e) {
        document.querySelectorAll('.sidebar-nav li').forEach(item => {
            item.classList.remove('active');
        });
        this.parentElement.classList.add('active');
    });
});

// Simple animation for stats
document.querySelectorAll('.stat-item').forEach((item, index) => {
    item.style.animationDelay = `${index * 0.1}s`;
});

// Get user's current Location
/**document.addEventListener('DOMContentLoaded', function() {
const e = document.getElementById('location');
if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                let lat = position.coords.latitude;
                let long = position.coords.longitude;
            },
            (error) => {
                alert("Unable to find location.");
            }
        );
    }

    else {
        console.error("Geolocation is not supported by this browser.");
    }
});**/

// changes the view of the profile page
function switchView(e, name) {
    if (e) e.preventDefault();
    const views = document.getElementsByClassName('profile-content');
    Array.from(views).forEach(view => view.style.display = 'none');

    const selectedView = document.getElementsByClassName(name);
    if (selectedView.length > 0) {
        selectedView[0].style.display = 'block';
    }

    window.location.hash = name;
}
</script>

</body>
</html>
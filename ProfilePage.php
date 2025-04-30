<?php
session_start();
// Sample user data
$user = [
    'name' => 'Shadi Zgheib',
    'email' => 'sha.zghe@gmail.com',
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
                    <a href="#">
                        <div class="nav-icon"><i class="fas fa-chart-pie"></i></div>
                        <span>Analytics</span>
                    </a>
                </li>
                <li>
                    <a href="#">
                        <div class="nav-icon"><i class="fas fa-wallet"></i></div>
                        <span>Accounts</span>
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
                        <div class="nav-icon"><i class="fas fa-cog"></i></div>
                        <span>Settings</span>
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
        
        <div class="sidebar-footer">
            <div class="user-card">
                <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="User" class="user-avatar">
                <div>
                    <span class="user-name"><?php echo htmlspecialchars($user['name']); ?></span>
                    <span class="user-email"><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                <a href="#" class="logout-btn"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </div>
    </aside>

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
                            <span class="info-value">San Francisco, CA</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Timezone</span>
                            <span class="info-value">(GMT-07:00) Pacific Time</span>
                        </div>
                    </div>
                </section>

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
                            <button class="security-action">Change</button>
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
            </div>
        </div>
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
</script>

</body>
</html>
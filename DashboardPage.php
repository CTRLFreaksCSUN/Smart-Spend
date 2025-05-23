<?php
session_start();

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\DynamoDb\Marshaler;
use Dotenv\Dotenv;

require __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/helpers.php';
$env = Dotenv::createImmutable(__DIR__);
$env->load();

$financeClient    = new DynamoDbClient([
    'region'      => $_ENV['REGION'],
    'version'     => 'latest',
    'credentials' => [
        'key'    => $_ENV['KEY'],
        'secret' => $_ENV['SECRET'],
    ],
    'scheme'      => 'http',
]);
$marshaler = new Marshaler();

list($trendLabels, $trendData) = fetchSpendingTrend(
    $financeClient, $marshaler, $_SESSION['email'], 6
);

// 2) Income trend:
list($incLabels, $incData) = fetchIncomeTrend(
    $financeClient, $marshaler, $_SESSION['email'], 6
);

// Bind the two helper results directly:
$incomeData  = $incData;
$expenseData = $trendData;

// Latest expenses & budgets
$latest      = getLatestFinance($financeClient,$marshaler,$_SESSION['email']);
$budgets     = $latest['budgets']       ?? [];
$expenses    = $latest['expenses']      ?? [];
$savingsGoals= $latest['savings_goals'] ?? [];
$currentSavings= $latest['current_savings'] ?? [];

// Seven categories in lock‑step
$keys       = ['rent','utilities','medical','food','shopping','transport','entertainment'];
$categoryLabels = ['Rent','Utilities','Medical','Food','Shopping','Transport','Entertainment'];
$actualData = array_map(fn($k)=> floatval($expenses[$k] ?? 0), $keys);
$budgetData = array_map(fn($k)=> floatval($budgets[$k] ?? 0), $keys);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Smart Spend - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="DashboardStyle.css">
    <link rel="stylesheet" href="bubbleChatStyle.css"> 
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<header>
    <div class="header-container">
        <div class="logo-title">
            <img src="images/SmartSpendLogo.png" alt="Smart Spend Logo" class="logo">
            <h1>Smart Spend</h1>
        </div>
        
        <nav class="main-nav">
            <ul class="nav-list">
                <li class="nav-item"><a href="DashboardPage.php" class="nav-link active"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li class="nav-item"><a href="documents.php" class="nav-link"><i class="fas fa-file-alt"></i> Documents</a></li>
                <li class="nav-item"><a href="uploadDocs.php" class="nav-link"><i class="fas fa-upload"></i> Upload</a></li>
                <li class="nav-item"><a href="SpendTrend.php" class="nav-link"><i class="fas fa-credit-card"></i> Spend Trend</a></li>
                <li class="nav-item"><a class="nav-link" href="history.php"><i class="fas fa-history me-1"></i> History</a></li>            
            </ul>
        </nav>
        
        <div class="profile-dropdown" id="profileDropdown">
            <img src="images/ProfilePic.png" alt="User" class="profile-avatar">
            <span class="profile-name">
                <?php echo isset($_SESSION['user_fname']) ? htmlspecialchars($_SESSION['user_fname']) : 'User'; ?>
            </span>
            <form method="POST">
            <i class="fas fa-chevron-down dropdown-icon"></i>
            <div class="dropdown-menu" id="dropdownMenu">
                <a href="ProfilePage.php" class="dropdown-item">View Profile</a>
                <a class="dropdown-item"><button type='submit' style="background-color:rgba(0,0,0,0); border-style:none; width:10rem; height:2rem; text-align:left; cursor:pointer;" name="logout" >Sign Out</button></a>
            </div>
           </form>
        </div>
    </div>
</header>

<?php 
    if (isset($_POST['logout'])) {
        session_unset();
        header('Location: LoginPage.php');
        exit();
    }
?>

<div class="background"></div>

<main class="card-grid">

    <div class="card" onclick="window.location.href='SpendTrend.php';" style="cursor: pointer;">
        <h2>Spending Trends</h2>
        <canvas id="spendingChart"></canvas>
    </div>

    <div class="card">
        <h2>Budget vs Actual Spending</h2>
        <canvas id="budgetChart"></canvas>
    </div>    

    <div class="card">
        <h2>Income vs Spending</h2>
         <canvas id="incomeChart"></canvas>
    </div>

    <div class="card">
        <h2>Top Spending Categories</h2>
        <canvas id="categoryChart"></canvas>
    </div>

    <div class="card">
        <h2>Savings Goals</h2>
        <canvas id="savingsGoalsChart"></canvas>
    </div>

</main>

<div class="chat-bubble-container" id="chatContainer">
    <div class="chat-bubble-button" id="chatBubble">?</div>
    <div class="chat-popup" id="chatPopup">
        <div class="chat-header">
            <span>AI Financial Assistant</span>
            <button id="closeChat">&times;</button>
        </div>
        <div class="chat-content">
            <iframe src="chatbox.php"></iframe>
        </div>
    </div>
</div>

<script>
    new Chart(document.getElementById('budgetChart'), {
        type: 'bar',
        data: {
        labels: <?= json_encode($categoryLabels) ?>,
        datasets: [
            {
            label: 'Actual Spending',
            data: <?= json_encode($actualData) ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.6)'
            },
            {
            label: 'Budget',
            data: <?= json_encode($budgetData) ?>,
            backgroundColor: 'rgba(255, 99, 132, 0.6)'
            }
        ]
        },
        options: {
        responsive: true,
        scales: {
            y: {
            beginAtZero: true,
            title: { display: true, text: 'Amount ($)' }
            },
            x: {
            title: { display: true, text: 'Category' }
            }
        }
        }
    });

    new Chart(document.getElementById('incomeChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode($incLabels) ?>,
            datasets: [
            {
                label: 'Income ($)',
                data: <?= json_encode($incomeData) ?>,
                borderColor: '#4caf50',
                backgroundColor: 'rgba(76,175,80,0.2)',
                borderWidth: 2
            },
            {
                label: 'Spending ($)',
                data: <?= json_encode($expenseData) ?>,
                borderColor: '#f44336',
                backgroundColor: 'rgba(244,67,54,0.2)',
                borderWidth: 2
            }
            ]
        },
        options: {
            responsive: true,
            scales: {
            y: {
                beginAtZero: true
            }
            }
        }
        });

    new Chart(document.getElementById('categoryChart'), {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($categoryLabels); ?>,
            datasets: [{
                label: 'Categories',
                data: <?php echo json_encode($actualData); ?>,
                backgroundColor: ['#ff6384', '#4caf50', '#FFC0CB', '#ffce56', '#36a2eb', '#800080', '#ff9800']
            }]
        },
        options: { responsive: true }
    });

    new Chart(document.getElementById('savingsGoalsChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_map(
            fn($k)=> ucwords(str_replace('_',' ',$k)), 
            array_keys($savingsGoals)
            )) ?>,
            datasets: [
            {
                label: 'Target Goal',
                data: <?= json_encode(array_map('floatval', array_values($savingsGoals))) ?>,
                backgroundColor: 'rgba(75,192,192,0.6)'
            },
            {
                label: 'Current Saved',
                data: <?= json_encode(array_map('floatval', array_map(
                fn($k)=> $currentSavings[$k] ?? 0, 
                array_keys($savingsGoals)
                ))) ?>,
                backgroundColor: 'rgba(153,102,255,0.6)'
            }
            ]
        },
        options: {
            responsive: true,
            scales: {
            y: { beginAtZero: true, title: { display: true, text: 'Amount ($)' } }
            }
        }
    });
</script>

<script>
    const ctx = document.getElementById('spendingChart').getContext('2d');
new Chart(ctx, {
  type: 'line',
  data: {
    labels: <?php echo json_encode($trendLabels); ?>,
    datasets: [{
      label: 'Spending ($)',
      data: <?php echo json_encode($trendData); ?>,
      fill: true,
      tension: 0.3,
      borderWidth: 2
    }]
  },
  options: {
    responsive: true,
    scales: { y: { beginAtZero: true } }
  }
});
  </script>

<!-- Chat Bubble JavaScript -->
<script src="bubbleChat.js"></script>

<script>
document.getElementById('profileDropdown').addEventListener('click', function (e) {
    e.stopPropagation(); 
    this.classList.toggle('show');
});

window.addEventListener('click', function () {
    const dropdown = document.getElementById('profileDropdown');
    dropdown.classList.remove('show');
});
</script>

</body>
</html>

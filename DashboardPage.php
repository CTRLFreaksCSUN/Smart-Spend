<?php
// DashboardPage.php
// Dummy data for charts
$labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
$spendingData = [200, 300, 250, 400, 350, 450];

// Budget vs Actual Spending
$budgetData = [500, 550, 580, 620, 600, 650];
$actualData = [480, 530, 560, 610, 590, 640];

// Income vs Spending
$incomeData = [700, 720, 750, 780, 760, 800];
$expenseData = [500, 540, 580, 600, 590, 620];

// Top Spending Categories (Pie Chart)
$categoryLabels = ['Rent', 'Groceries', 'Shopping', 'Transport', 'Entertainment'];
$categoryData = [40, 25, 15, 10, 10];

// Savings Progress (Bar Chart)
$savingsData = [150, 180, 200, 220, 250, 300];

// Predicted Spending (Line Chart)
$predictedData = [300, 350, 400, 450, 500, 550];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Smart Spend - Dashboard</title>
    <link rel="stylesheet" href="DashboardStyle.css">
    <link rel="stylesheet" href="bubbleChatStyle.css"> <!-- Added chat bubble CSS -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<header>
    <div class="logo-title">
        <img src="images/SmartSpendLogo.png" alt="Smart Spend Logo" class="logo" style="width:90px; height:80px;">
        <h1>Smart Spend</h1>
    </div>
    
    <div class="nav-container">
    <nav>
        <a href="DashboardPage.php">Dashboard</a>
        <a href="#">Documents</a>
        <a href="uploadDocs.php">Upload Documents</a> <!-- Updated link -->
    </nav>
    </div>
    
    <div class="profile-icon">
        <img src="images/ProfilePic.png" alt="User" class="avatar">
    </div>
</header>

<!-- Background wave image -->
<div class="background"></div>

<!-- Graph cards -->
<main class="card-grid">

    <!-- Spending Trends -->
    <div class="card">
        <h2>Spending Trends</h2>
        <canvas id="spendingChart"></canvas>
    </div>

    <!-- Budget vs Actual Spending -->
    <div class="card">
        <h2>Budget vs Actual Spending</h2>
        <canvas id="budgetChart"></canvas>
    </div>

    <!-- Income vs Spending -->
    <div class="card">
        <h2>Income vs Spending</h2>
        <canvas id="incomeChart"></canvas>
    </div>

    <!-- Top Spending Categories -->
    <div class="card">
        <h2>Top Spending Categories</h2>
        <canvas id="categoryChart"></canvas>
    </div>

    <!-- Savings Progress -->
    <div class="card">
        <h2>Savings Progress</h2>
        <canvas id="savingsChart"></canvas>
    </div>

    <!-- Predicted Spending -->
    <div class="card">
        <h2>Predicted Spending</h2>
        <canvas id="predictedChart"></canvas>
    </div>

</main>

<!-- Floating Chat Bubble & Popup Container -->
<div class="chat-bubble-container" id="chatContainer">
    <!-- Bubble Button -->
    <div class="chat-bubble-button" id="chatBubble">?</div>
    <!-- Chat Popup (hidden by default) -->
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

<!-- Graph Logic -->
<script>
    const ctx = document.getElementById('spendingChart').getContext('2d');
    const spendingChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{
                label: 'Spending ($)',
                data: <?php echo json_encode($spendingData); ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // Budget vs Actual Spending - Bar Chart
    new Chart(document.getElementById('budgetChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [
                {
                    label: 'Budget ($)',
                    data: <?php echo json_encode($budgetData); ?>,
                    backgroundColor: '#4caf50'
                },
                {
                    label: 'Actual ($)',
                    data: <?php echo json_encode($actualData); ?>,
                    backgroundColor: '#ff9800'
                }
            ]
        },
        options: { responsive: true }
    });

    // Income vs Spending - Line Chart
    new Chart(document.getElementById('incomeChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [
                {
                    label: 'Income ($)',
                    data: <?php echo json_encode($incomeData); ?>,
                    borderColor: '#4caf50',
                    backgroundColor: 'rgba(76, 175, 80, 0.2)',
                    borderWidth: 2
                },
                {
                    label: 'Spending ($)',
                    data: <?php echo json_encode($expenseData); ?>,
                    borderColor: '#f44336',
                    backgroundColor: 'rgba(244, 67, 54, 0.2)',
                    borderWidth: 2
                }
            ]
        },
        options: { responsive: true }
    });

    // Top Spending Categories - Pie Chart
    new Chart(document.getElementById('categoryChart'), {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($categoryLabels); ?>,
            datasets: [{
                label: 'Categories',
                data: <?php echo json_encode($categoryData); ?>,
                backgroundColor: ['#ff6384', '#36a2eb', '#ffce56', '#4caf50', '#ff9800']
            }]
        },
        options: { responsive: true }
    });

    // Savings Progress - Bar Chart
    new Chart(document.getElementById('savingsChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{
                label: 'Savings ($)',
                data: <?php echo json_encode($savingsData); ?>,
                backgroundColor: '#3f51b5'
            }]
        },
        options: { responsive: true }
    });

    // Predicted Spending - Line Chart
    new Chart(document.getElementById('predictedChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{
                label: 'Predicted Spending ($)',
                data: <?php echo json_encode($predictedData); ?>,
                borderColor: '#ff6384',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                borderWidth: 2,
                tension: 0.3
            }]
        },
        options: { responsive: true }
    });
</script>

<!-- Chat Bubble JavaScript -->
<script src="bubbleChat.js"></script>

</body>
</html>

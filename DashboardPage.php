<?php
// Dummy data for the Spending Trends chart
$labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
$data = [200, 300, 250, 400, 350, 450];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Smart Spend - Dashboard</title>
    <link rel="stylesheet" href="DashboardStyle.css">
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
            <a href="#">Dashboard</a>
            <a href="#">Documents</a>
            <a href="#">Upload Documents</a>
        </nav>
    </div>
    
    <div class="profile-icon">
        <img src="images/ProfilePic.png" alt="User" class="avatar">
    </div>
</header>

<!-- Graph cards -->
<main class="card-grid">
    <div class="card">
        <h2>Graphs</h2>
        <p>Spending Trends</p>
        <canvas id="spendingChart"></canvas>
    </div>

    <div class="card">
        <h2>Graphs</h2>
        <p>Budget vs actual spending</p>
    </div>
    <div class="card">
        <h2>Graphs</h2>
        <p>Income vs spending</p>
    </div>
    <div class="card">
        <h2>Graphs</h2>
        <p>Top Spending categories</p>
    </div>
    <div class="card">
        <h2>Graphs</h2>
        <p>Savings Progress</p>
    </div>
    <div class="card">
        <h2>Graphs</h2>
        <p>Predicted Spending</p>
    </div>
</main>

<!-- Graph Logic -->
<script>
    const ctx = document.getElementById('spendingChart').getContext('2d');
    const spendingChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{
                label: 'Spending ($)',
                data: <?php echo json_encode($data); ?>,
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
</script>

</body>
</html>

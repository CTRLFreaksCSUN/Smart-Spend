<?php
// SpendTrend.php
$current_page = basename($_SERVER['PHP_SELF']);
$categories = [
    'gas' => ['icon' => 'fas fa-gas-pump', 'color' => '#FF9F43'],
    'groceries' => ['icon' => 'fas fa-shopping-basket', 'color' => '#4BC0C0'],
    'subscriptions' => ['icon' => 'fas fa-newspaper', 'color' => '#9966FF'],
    'dining' => ['icon' => 'fas fa-utensils', 'color' => '#FF6384'],
    'entertainment' => ['icon' => 'fas fa-film', 'color' => '#36A2EB'],
    'utilities' => ['icon' => 'fas fa-bolt', 'color' => '#FFCD56']
];

$analytics = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $expenses = [];
    $budgets = [];
    
    foreach ($categories as $cat => $data) {
        if (isset($_POST['expenses'][$cat])) {
            $expenseValues = array_filter(array_map('floatval', explode(',', $_POST['expenses'][$cat])));
            $expenses[$cat] = !empty($expenseValues) ? $expenseValues : [0];
        }
        $budgets[$cat] = floatval($_POST['budgets'][$cat] ?? 0);
    }
    
    $apiData = [
        'expenses' => $expenses,
        'budgets' => $budgets,
        'timeframe' => $_POST['timeframe'] ?? '6m'
    ];
    
    $ch = curl_init('http://localhost:5000/calc_spending');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($apiData)
    ]);
    $response = curl_exec($ch);
    $analytics = json_decode($response, true);
    curl_close($ch);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Spend Tracker</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="spendtrend.css">
    <style>
        :root {
            --primary-blue: #1e88e5;
            --dark-blue: #0d47a1;
            --light-blue: #e3f2fd;
            --white: #ffffff;
            --light-gray: #f5f5f5;
        }
        
        .background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('../images/Background.jpg');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center center;
            background-attachment: fixed;
            z-index: -1;
            opacity: 0.1;
        }
        
        .content-wrapper {
            position: relative;
            z-index: 1;
        }
        
        .navbar {
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary-blue), var(--dark-blue));
            color: white;
            padding: 2.5rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .card {
            background-color: white;
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        
        .card-header {
            background-color: var(--primary-blue);
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        
        @media (max-width: 768px) {
            .background {
                background-attachment: scroll;
                opacity: 0.15;
            }
            
            .header {
                padding: 1.5rem 0;
            }
        }
    </style>
</head>
<body>
    <div class="background"></div>
    
    <div class="content-wrapper">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="DashboardPage.php">
            <i class="fas fa-wallet me-2"></i> Smart Spend
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'DashboardPage.php') ? 'active' : '' ?>" href="DashboardPage.php">
                        <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page == 'SpendTrend.php') ? 'active' : '' ?>" href="#">
                        <i class="fas fa-chart-pie me-1"></i> Spending Trends
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="fas fa-history me-1"></i> History
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="fas fa-cog me-1"></i> Settings
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
        <div class="header text-center">
            <div class="container">
                <h1><i class="fas fa-wallet me-2"></i> Smart Spend Tracker</h1>
                <p class="lead mb-0">Visualize your spending habits and optimize your budget</p>
            </div>
        </div>

        <div class="container pb-5">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0"><i class="fas fa-edit me-2"></i> Enter Your Expenses</h3>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="row">
                            <?php foreach ($categories as $cat => $data): ?>
                            <div class="col-md-6 mb-4">
                                <div class="category-header">
                                    <i class="<?= $data['icon'] ?>"></i>
                                    <h5 class="mb-0"><?= ucfirst($cat) ?></h5>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Expenses (comma separated)</label>
                                    <input type="text" class="form-control" 
                                           name="expenses[<?= $cat ?>]" 
                                           placeholder="e.g., 25.50, 12.75, 30.00">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Monthly Budget</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" step="0.01" class="form-control" 
                                               name="budgets[<?= $cat ?>]" 
                                               placeholder="Budget amount">
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <select class="form-select w-auto" name="timeframe">
                                <option value="6m">Last 6 Months</option>
                                <option value="1y">Last 12 Months</option>
                            </select>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-chart-bar me-2"></i> Analyze Spending
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php if ($analytics): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0"><i class="fas fa-chart-pie me-2"></i> Spending Analysis</h3>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="stat-card">
                                <h6 class="text-muted">Total Spent</h6>
                                <div class="stat-value">$<?= number_format($analytics['total_spent'], 2) ?></div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="stat-card">
                                <h6 class="text-muted">Total Budget</h6>
                                <div class="stat-value">$<?= number_format($analytics['total_budget'], 2) ?></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card">
                                <h6 class="text-muted">Savings</h6>
                                <div class="stat-value <?= $analytics['savings'] >= 0 ? 'positive' : 'negative' ?>">
                                    $<?= number_format(abs($analytics['savings']), 2) ?>
                                    <i class="fas fa-arrow-<?= $analytics['savings'] >= 0 ? 'down' : 'up' ?> ms-2"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-5">
                        <h4 class="mb-4"><i class="fas fa-chart-bar text-primary me-2"></i> Spending Distribution</h4>
                        <?php foreach ($analytics['spending_distribution'] as $cat => $perc): ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <div class="d-flex align-items-center">
                                    <i class="<?= $categories[$cat]['icon'] ?> me-2" style="color: <?= $categories[$cat]['color'] ?>"></i>
                                    <span><?= ucfirst($cat) ?></span>
                                </div>
                                <span><?= number_format($perc, 1) ?>%</span>
                            </div>
                            <div class="distribution-bar">
                                <div class="distribution-fill" style="width: <?= $perc ?>%; background: <?= $categories[$cat]['color'] ?>"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Chart -->
                    <div>
                        <h4 class="mb-4"><i class="fas fa-chart-line text-primary me-2"></i> Spending Trends</h4>
                        <div class="chart-container">
                            <canvas id="spendingChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($analytics && isset($analytics['historical'])): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        try {
            const ctx = document.getElementById('spendingChart');
            if (!ctx) {
                console.error('Canvas element not found');
                return;
            }
            
            console.log('Chart data:', <?= json_encode($analytics['historical']) ?>);
            
            const datasets = [];
            
            <?php foreach ($analytics['historical']['data'] as $cat => $values): ?>
                datasets.push({
                    label: '<?= addslashes(ucfirst($cat)) ?>',
                    data: <?= json_encode(array_map('floatval', $values)) ?>,
                    backgroundColor: '<?= $categories[$cat]['color'] ?>33',
                    borderColor: '<?= $categories[$cat]['color'] ?>',
                    borderWidth: 2,
                    tension: 0.1
                });
            <?php endforeach; ?>
            
            <?php foreach ($analytics['regression'] as $cat => $reg): ?>
                datasets.push({
                    label: '<?= addslashes(ucfirst($cat)) ?> Trend',
                    data: <?= json_encode(array_map('floatval', $reg['trend_line'])) ?>,
                    borderColor: '<?= $categories[$cat]['color'] ?>',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    pointRadius: 0
                });
            <?php endforeach; ?>
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?= json_encode($analytics['historical']['dates']) ?>,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
            
        } catch (error) {
            console.error('Chart initialization failed:', error);
        }
    });
    </script>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
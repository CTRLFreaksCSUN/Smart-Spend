<?php
session_start();
require_once __DIR__ . '/AIAnalytics.php';

// Get data from session or use defaults
$trendData = $_SESSION['spending_trend_data'] ?? null;
$labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
$budgetData = [500, 550, 580, 620, 600, 650];
$actualData = [480, 530, 560, 610, 590, 640];

if ($trendData) {
    $labels = $trendData['historical']['dates'] ?? $labels;
    $budgetData = $trendData['budgets'] ?? $budgetData;
    $actualData = $trendData['actuals'] ?? $actualData;
}

// Calculate key metrics
$totalBudget = array_sum($budgetData);
$totalActual = array_sum($actualData);
$savings = $totalBudget - $totalActual;
$performancePercent = ($totalBudget > 0) ? ($totalActual / $totalBudget) * 100 : 0;

// Handle AI recommendations
$aiRecommendations = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_ai'])) {
    $aiRecommendations = generate_ai_recommendations($budgetData, $actualData);
} elseif ($trendData) {
    // Default placeholder recommendations
    $aiRecommendations = [
        ['icon' => 'chart-pie', 'text' => 'Analyze your spending patterns for personalized advice'],
        ['icon' => 'lightbulb', 'text' => 'Try our spending forecast tool for future planning']
    ];
}

// Reset functionality
if (isset($_GET['reset'])) {
    unset($_SESSION['spending_trend_data']);
    header("Location: BudgetVsActual.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Spend - Budget vs Actual</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="bubbleChatStyle.css">
    <style>
        :root {
            --primary-blue: #1e88e5;
            --dark-blue: #0d47a1;
            --light-blue: #e3f2fd;
            --white: #ffffff;
            --light-gray: #f5f5f5;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background-color: var(--white);
            position: relative;
            min-height: 100vh;
            color: #333;
        }
        
        .background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('assets/images/background.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            opacity: 0.1;
            z-index: -1;
        }
        
        .navbar {
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            background-color: var(--primary-blue) !important;
            padding: 0.5rem 1rem;
        }
        
        .navbar-brand {
            font-weight: 600;
            font-size: 1.5rem;
        }
        
        .navbar-brand img {
            transition: transform 0.3s ease;
        }
        
        .navbar-brand:hover img {
            transform: rotate(-10deg);
        }
        
        .nav-link {
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover, .nav-link.active {
            background-color: var(--dark-blue);
        }
        
        .budget-container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .chart-card {
            background-color: var(--white);
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
            border: 1px solid rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .chart-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }
        
        .profile-dropdown {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            transition: all 0.3s ease;
        }
        
        .profile-dropdown:hover {
            background-color: rgba(255,255,255,0.15);
        }
        
        .profile-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid rgba(255,255,255,0.3);
            object-fit: cover;
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            border-radius: 8px;
            padding: 0.5rem;
        }
        
        .dropdown-item {
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: all 0.2s ease;
        }
        
        .dropdown-item:hover {
            background-color: var(--light-blue);
            color: var(--dark-blue);
        }
        
        .insight-item {
            padding: 1.25rem;
            border-radius: 8px;
            background-color: var(--light-gray);
            margin-bottom: 1.25rem;
        }
        
        .recommendation-item {
            display: flex;
            gap: 1rem;
            align-items: flex-start;
            padding: 1rem;
            background-color: var(--light-gray);
            border-radius: 8px;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .recommendation-item:hover {
            background-color: #e9ecef;
            transform: translateX(5px);
        }
        
        .recommendation-item i {
            font-size: 1.1rem;
            color: var(--primary-blue);
            margin-top: 0.2rem;
        }
        
        .recommendation-scroll {
            max-height: 300px;
            overflow-y: auto;
            padding-right: 10px;
        }
        
        .performance-meter {
            height: 8px;
            background-color: #f0f0f0;
            border-radius: 4px;
            margin-top: 1rem;
            overflow: hidden;
        }
        
        .meter-bar {
            height: 100%;
            background: linear-gradient(90deg, #4CAF50, #8BC34A);
            transition: width 0.5s ease;
        }
        
        .trend-indicator {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .trend-up {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        
        .trend-down {
            background-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }
        
        @media (max-width: 768px) {
            .budget-container {
                padding: 0 1rem;
            }
            
            .chart-card {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="background"></div>
    
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="DashboardPage.php">
                <img src="images/SmartSpendLogo.png" alt="Smart Spend Logo" class="me-2" width="40">
                Smart Spend
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="DashboardPage.php">
                            <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="SpendTrend.php">
                            <i class="fas fa-chart-pie me-1"></i> Trends
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="#">
                            <i class="fas fa-exchange-alt me-1"></i> Budget vs Actual
                        </a>
                    </li>
                </ul>
                
                <div class="dropdown">
                    <button 
                        class="btn btn-transparent dropdown-toggle d-flex align-items-center" 
                        type="button" 
                        id="profileDropdown" 
                        data-bs-toggle="dropdown" 
                        aria-expanded="false"
                    >
                        <img src="images/ProfilePic.png" alt="User Profile" class="profile-avatar me-2">
                        <span class="text-white"><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                        <li><a class="dropdown-item" href="ProfilePage.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="LoginPage.php"><i class="fas fa-sign-out-alt me-2"></i> Sign Out</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="budget-container">
        <div class="text-center my-5 py-3">
            <h1 class="display-5 fw-bold mb-3">Budget vs Actual Spending</h1>
            <?php if ($trendData): ?>
            <div class="alert alert-info d-flex flex-column flex-md-row justify-content-between align-items-center">
                <div class="d-flex align-items-center mb-2 mb-md-0">
                    <i class="fas fa-info-circle me-2"></i>
                    <span>Currently viewing your personalized spending analysis</span>
                </div>
                <a href="?reset=1" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-sync-alt me-1"></i> Reset to Sample Data
                </a>
            </div>
            <?php endif; ?>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="chart-card">
                    <h3 class="mb-4 d-flex align-items-center">
                        <i class="fas fa-chart-bar text-primary me-3"></i>
                        <span>Budget Comparison</span>
                    </h3>
                    <canvas id="budgetChart" height="250"></canvas>
                    <div class="d-flex justify-content-center gap-4 mt-4">
                        <span class="d-flex align-items-center">
                            <i class="fas fa-square me-2" style="color: rgba(54, 162, 235, 0.7);"></i>
                            <span>Budget</span>
                        </span>
                        <span class="d-flex align-items-center">
                            <i class="fas fa-square me-2" style="color: rgba(255, 99, 132, 0.7);"></i>
                            <span>Actual</span>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="chart-card h-100" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                    <h3 class="mb-4 d-flex align-items-center">
                        <i class="fas fa-tachometer-alt text-primary me-3"></i>
                        <span>Financial Snapshot</span>
                    </h3>
                    
                    <!-- Budget Card -->
                    <div class="insight-item shadow-sm" style="border-left: 4px solid #1e88e5;">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="fw-semibold mb-0">Total Budget</h5>
                            <i class="fas fa-wallet text-primary"></i>
                        </div>
                        <p class="fs-3 fw-bold text-primary mt-2">$<?= number_format($totalBudget, 2) ?></p>
                        <small class="text-muted">Planned spending</small>
                    </div>

                    <!-- Actual Card -->
                    <div class="insight-item shadow-sm mt-3" style="border-left: 4px solid #ff6384;">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="fw-semibold mb-0">Actual Spending</h5>
                            <i class="fas fa-receipt" style="color: #ff6384;"></i>
                        </div>
                        <p class="fs-3 fw-bold mt-2" style="color: #ff6384;">$<?= number_format($totalActual, 2) ?></p>
                        <small class="text-muted">Real expenses</small>
                    </div>

                    <!-- Difference Card -->
                    <div class="insight-item shadow-sm mt-3" style="border-left: 4px solid <?= $savings >= 0 ? '#4CAF50' : '#F44336' ?>;">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="fw-semibold mb-0">Difference</h5>
                            <i class="fas fa-balance-scale" style="color: <?= $savings >= 0 ? '#4CAF50' : '#F44336' ?>;"></i>
                        </div>
                        <div class="d-flex align-items-end justify-content-between mt-2">
                            <p class="fs-3 fw-bold mb-0" style="color: <?= $savings >= 0 ? '#4CAF50' : '#F44336' ?>;">
                                $<?= number_format(abs($savings), 2) ?>
                            </p>
                            <span class="badge bg-<?= $savings >= 0 ? 'success' : 'danger' ?>">
                                <?= $savings >= 0 ? 'Under' : 'Over' ?> Budget
                            </span>
                        </div>
                        <div class="progress mt-3" style="height: 10px;">
                            <div class="progress-bar bg-<?= $performancePercent <= 100 ? 'success' : 'danger' ?>" 
                                 role="progressbar" 
                                 style="width: <?= min($performancePercent, 100) ?>%;" 
                                 aria-valuenow="<?= $performancePercent ?>" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                            </div>
                        </div>
                        <small class="text-muted d-block mt-1">
                            <?= number_format($performancePercent, 1) ?>% of budget used
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <div class="chart-card mt-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
                <h3 class="mb-3 mb-md-0 d-flex align-items-center">
                    <i class="fas fa-robot text-primary me-3"></i>
                    <span>AI Recommendations</span>
                </h3>
                <form method="post" class="d-flex">
                    <button type="submit" name="generate_ai" class="btn btn-primary px-4">
                        <i class="fas fa-magic me-2"></i> Generate Suggestions
                    </button>
                </form>
            </div>
            
            <div class="recommendation-scroll">
                <?php if (!empty($aiRecommendations)): ?>
                    <?php foreach ($aiRecommendations as $rec): ?>
                    <div class="recommendation-item">
                        <i class="fas fa-<?= $rec['icon'] ?>"></i>
                        <p class="mb-0"><?= $rec['text'] ?></p>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-robot fa-3x mb-3" style="opacity: 0.3;"></i>
                        <h5>No recommendations yet</h5>
                        <p class="mb-0">Analyze your spending to get personalized advice</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Chat Bubble Integration -->
    <div id="chatContainer" class="chat-bubble-container">
        <button id="chatBubble" class="chat-bubble-button">?</button>
        <div id="chatPopup" class="chat-popup">
            <div class="chat-header">
                <div class="chat-title">Smart Spend Assistant</div>
                <button id="closeChat"><i class="fas fa-times"></i></button>
            </div>
            <div class="chat-content">
                <iframe src="chatbox.php" frameborder="0"></iframe>
            </div>
            <div class="resize-handle"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="bubbleChat.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize chart
        const ctx = document.getElementById('budgetChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [
                    {
                        label: 'Budget',
                        data: <?= json_encode($budgetData) ?>,
                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                        borderRadius: 4
                    },
                    {
                        label: 'Actual',
                        data: <?= json_encode($actualData) ?>,
                        backgroundColor: 'rgba(255, 99, 132, 0.7)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    tooltip: {
                        callbacks: {
                            afterBody: function(context) {
                                const index = context[0].dataIndex;
                                const diff = <?= json_encode($budgetData) ?>[index] - <?= json_encode($actualData) ?>[index];
                                const variance = (<?= json_encode($actualData) ?>[index] / <?= json_encode($budgetData) ?>[index] * 100).toFixed(1);
                                return [
                                    `Difference: $${Math.abs(diff).toFixed(2)} (${diff >= 0 ? 'Under' : 'Over'})`,
                                    `Variance: ${variance}% of budget`
                                ];
                            }
                        }
                    },
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            drawBorder: false
                        },
                        ticks: {
                            callback: function(value) {
                                return '$' + value;
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    });
    </script>
</body>
</html>
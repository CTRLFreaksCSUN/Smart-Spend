<?php
// smartspend.php
session_start();

function isServerRunning() {
    $ch = curl_init('http://localhost:5000/calc_spending');
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response !== false;
}

function startPythonServer() {
    $pythonScript = __DIR__ . '/spend_trend.py';
    
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows - we'll store the process ID
        $command = 'wmic process call create "python \"' . $pythonScript . '\"" | find "ProcessId"';
        $output = shell_exec($command);
        preg_match('/\d+/', $output, $matches);
        if (!empty($matches)) {
            $_SESSION['python_server_pid'] = (int)$matches[0];
            return true;
        }
    } else {
        // Linux/Mac - store the PID
        $command = 'python3 "' . $pythonScript . '" > /dev/null 2>&1 & echo $!';
        $pid = (int)shell_exec($command);
        if ($pid > 0) {
            $_SESSION['python_server_pid'] = $pid;
            return true;
        }
    }
    
    return false;
}

function stopPythonServer() {
    if (!empty($_SESSION['python_server_pid'])) {
        $pid = $_SESSION['python_server_pid'];
        
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows
            shell_exec("taskkill /PID $pid /F");
        } else {
            // Linux/Mac
            shell_exec("kill $pid");
        }
        
        unset($_SESSION['python_server_pid']);
    }
}

// Register shutdown function
register_shutdown_function('stopPythonServer');

if (!isServerRunning()) {
    if (startPythonServer()) {
        sleep(2); // Give it time to start
    } else {
        // Handle error - couldn't start server
        error_log("Failed to start Python server");
    }
}


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
    
    // Add machine learning predictions and anomaly detection
    if ($analytics) {
        foreach ($analytics['historical']['data'] as $cat => $values) {
            // Generate forecasts for each category
            $forecast = generate_forecast($values);
            $analytics['forecasts'][$cat] = $forecast;
            
            // Detect anomalies for each category
            $anomalies = detect_anomalies($values);
            $analytics['anomalies'][$cat] = $anomalies;
        }
        
        // Save to history
        if (!isset($_SESSION['spending_history'])) {
            $_SESSION['spending_history'] = [];
        }
        
        $entry = $analytics;
        $entry['timestamp'] = date('Y-m-d H:i:s');
        array_unshift($_SESSION['spending_history'], $entry);
        $_SESSION['spending_history'] = array_slice($_SESSION['spending_history'], 0, 50);
    }
}

function generate_forecast($historical_data, $periods = 3) {
    // Simple forecasting using linear regression
    $n = count($historical_data);
    if ($n < 2) return array_fill(0, $periods, end($historical_data));
    
    $sumX = $sumY = $sumXY = $sumX2 = 0;
    
    foreach ($historical_data as $i => $value) {
        $x = $i + 1;
        $sumX += $x;
        $sumY += $value;
        $sumXY += $x * $value;
        $sumX2 += $x * $x;
    }
    
    $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
    $intercept = ($sumY - $slope * $sumX) / $n;
    
    $forecast = [];
    for ($i = 1; $i <= $periods; $i++) {
        $forecast[] = $intercept + $slope * ($n + $i);
    }
    
    return $forecast;
}

function detect_anomalies($data) {
    // Simple anomaly detection using IQR method
    if (count($data) < 3) return [];
    
    sort($data);
    $q1 = $data[floor(count($data) * 0.25)];
    $q3 = $data[floor(count($data) * 0.75)];
    $iqr = $q3 - $q1;
    
    $lower_bound = $q1 - 1.5 * $iqr;
    $upper_bound = $q3 + 1.5 * $iqr;
    
    $anomalies = [];
    foreach ($data as $value) {
        if ($value < $lower_bound || $value > $upper_bound) {
            $anomalies[] = $value;
        }
    }
    
    return $anomalies;
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
    <!-- Add bubble chat CSS -->
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
        
        .anomaly-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: var(--danger);
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }
        
        .forecast-card {
            position: relative;
            transition: all 0.3s ease;
        }
        
        .forecast-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        @media (max-width: 768px) {
            .form-footer-buttons {
                flex-direction: column;
                gap: 1rem;
            }
            
            .form-footer-buttons .btn {
                width: 100%;
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
                            <a class="nav-link <?= ($current_page == 'smartspend.php') ? 'active' : '' ?>" href="smartspend.php">
                                <i class="fas fa-chart-pie me-1"></i> Spending Trends
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= ($current_page == 'history.php') ? 'active' : '' ?>" href="history.php">
                                <i class="fas fa-history me-1"></i> History
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= ($current_page == 'settings.php') ? 'active' : '' ?>" href="settings.php">
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
            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($analytics)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> Analysis completed and saved to history!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <div class="mt-2">
                    <a href="history.php" class="alert-link">View full history</a>
                </div>
            </div>
            <?php endif; ?>
            
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
                                           placeholder="e.g., 25.50, 12.75, 30.00"
                                           value="<?= isset($_POST['expenses'][$cat]) ? htmlspecialchars($_POST['expenses'][$cat]) : '' ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Monthly Budget</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" step="0.01" class="form-control" 
                                               name="budgets[<?= $cat ?>]" 
                                               placeholder="Budget amount"
                                               value="<?= isset($_POST['budgets'][$cat]) ? htmlspecialchars($_POST['budgets'][$cat]) : '' ?>">
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mt-4 form-footer-buttons">
                            <select class="form-select" name="timeframe" style="width: auto;">
                                <option value="6m">Last 6 Months</option>
                                <option value="1y">Last 12 Months</option>
                            </select>
                            <div class="d-flex gap-2">
                                <a href="history.php" class="btn btn-outline-primary">
                                    <i class="fas fa-history me-2"></i> View History
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-chart-bar me-2"></i> Analyze Spending
                                </button>
                            </div>
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
                                <div class="stat-value <?= $analytics['savings'] >= 0 ? 'text-success' : 'text-danger' ?>">
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
                    
                    <!-- Forecast Section -->
                    <div class="mb-5">
                        <h4 class="mb-4"><i class="fas fa-chart-line text-primary me-2"></i> Spending Forecast & Anomalies</h4>
                        <div class="row">
                            <?php foreach ($analytics['forecasts'] as $cat => $forecast): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card h-100 forecast-card">
                                    <div class="card-body position-relative">
                                        <?php if (!empty($analytics['anomalies'][$cat])): ?>
                                            <span class="anomaly-badge" title="Anomalies detected">
                                                <i class="fas fa-exclamation"></i>
                                            </span>
                                        <?php endif; ?>
                                        <h5 class="card-title">
                                            <i class="<?= $categories[$cat]['icon'] ?> me-2" style="color: <?= $categories[$cat]['color'] ?>"></i>
                                            <?= ucfirst($cat) ?>
                                        </h5>
                                        <div class="mb-2">
                                            <small class="text-muted">Next 3 Periods Forecast:</small>
                                            <div class="d-flex justify-content-between">
                                                <?php foreach ($forecast as $i => $value): ?>
                                                <div>
                                                    <small>Period <?= $i+1 ?></small>
                                                    <div class="fw-bold">$<?= number_format($value, 2) ?></div>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <?php if (!empty($analytics['anomalies'][$cat])): ?>
                                            <div class="alert alert-warning p-2 mt-2 mb-0">
                                                <small>
                                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                                    Anomalies detected: <?= implode(', ', array_map(function($v) { return '$' . number_format($v, 2); }, $analytics['anomalies'][$cat])) ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
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

    <!-- Bubble Chat Container -->
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
            
            // Add forecast data if available
            <?php if (isset($analytics['forecasts'])): ?>
                <?php foreach ($analytics['forecasts'] as $cat => $forecast): ?>
                    datasets.push({
                        label: '<?= addslashes(ucfirst($cat)) ?> Forecast',
                        data: [
                            <?php 
                            $last_historical = end($analytics['historical']['data'][$cat]);
                            echo json_encode($last_historical) . ', ' . json_encode($forecast);
                            ?>
                        ],
                        borderColor: '<?= $categories[$cat]['color'] ?>',
                        borderWidth: 2,
                        borderDash: [3, 3],
                        pointRadius: 0
                    });
                <?php endforeach; ?>
            <?php endif; ?>
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?= json_encode($analytics['historical']['dates']) ?>,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Amount ($)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Time Period'
                            }
                        }
                    }
                }
            });
            
        } catch (error) {
            console.error('Chart initialization failed:', error);
        }
    });
    </script>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Add bubble chat JS -->
    <script src="bubbleChat.js"></script>
</body>
</html>

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

$financeClient = new DynamoDbClient([
  'region'      => $_ENV['REGION'],
  'version'     => 'latest',
  'credentials' => [
    'key'    => $_ENV['KEY'],
    'secret' => $_ENV['SECRET'],
  ],
  'scheme'      => 'http',
]);
$m = new Marshaler();

try {
  $latest = getLatestExpenses($financeClient, $m, $_SESSION['email']);
} catch (DynamoDbException $e) {
  // handle (or default to zeros)
  $latest = [];
}

// Now turn that into your seven category variables:
$_SESSION['rent']       = $latest['rent']      ?? 0;
$_SESSION['medical']    = $latest['medical']       ?? 0;
$_SESSION['food']       = $latest['food']          ?? 0;
$_SESSION['utilities']       = $latest['utilities']     ?? 0;
$_SESSION['shopping']   = $latest['shopping']      ?? 0;
$_SESSION['transport']  = $latest['transport']     ?? 0;
$_SESSION['entertain']  = $latest['entertainment'] ?? 0;

function isServerRunning() {
    $ch = curl_init('http://localhost:5000/calc_spending');
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    return curl_exec($ch) !== false;
}

if (!isServerRunning()) {
    // Start the server from its correct directory
    $command = 'cd /d C:\xampp\htdocs\Smart-Spend && start /B python spend_trend.py';
    pclose(popen($command, 'r'));
    sleep(2);
}

$current_page = basename($_SERVER['PHP_SELF']);
$categories = [
  'rent'          => ['icon'=>'fas fa-home',        'color'=>'#FF9F43'],
  'medical'       => ['icon'=>'fas fa-notes-medical','color'=>'#4BC0C0'],
  'food'          => ['icon'=>'fas fa-utensils',    'color'=>'#9966FF'],
  'utilities'     => ['icon'=>'fas fa-bolt',        'color'=>'#FFCD56'],
  'shopping'      => ['icon'=>'fas fa-shopping-bag','color'=>'#36A2EB'],
  'transport'     => ['icon'=>'fas fa-car',         'color'=>'#FF6384'],
  'entertainment' => ['icon'=>'fas fa-film',        'color'=>'#36A2EB'],
];

$analytics = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1) get both previous maps
    $prevRec      = getLatestExpenses($financeClient, $m, $_SESSION['email']);
    $prevExpenses = $prevRec['expenses'] ?? [];
    $prevBudgets  = $prevRec['budgets']  ?? [];

    $expenseSums = [];
    $budgets     = [];

    // 2) loop all 7 categories
    foreach ($categories as $cat => $data) {
        // --- EXPENSES: parse, sum, add to old ---
        $vals = [];
        if (!empty($_POST['expenses'][$cat])) {
            $vals = array_filter(
                array_map('floatval', explode(',', $_POST['expenses'][$cat]))
            );
        }
        $sum               = array_sum($vals);
        $expenseSums[$cat] = ($prevExpenses[$cat] ?? 0) + $sum;

    if (isset($_POST['budgets'][$cat]) && $_POST['budgets'][$cat] !== '') {
        $budgets[$cat] = floatval($_POST['budgets'][$cat]);
    } else {
        $budgets[$cat] = $prevBudgets[$cat] ?? 0;
    }}

    $apiData = [
        'expenses' => $expenseSums,
        'budgets' => $budgets,
        'timeframe' => $_POST['timeframe'] ?? '6m'
    ];
    
    try {
        $now = (new DateTime())->format(DateTime::ATOM);
        $financeClient->putItem([
            'TableName' => 'Finance',
            'Item'      => $m->marshalItem([
                'c_id'       => hash('sha256', $_SESSION['email']),
                'created_at' => $now,
                'expenses'   => $expenseSums,
                'budgets'    => $budgets,
            ]),
        ]);
    } catch (DynamoDbException $e) {
        error_log("Failed to save raw expenses/budgets: " . $e->getMessage());
    }

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

        try {
            $conn = new DynamoDbClient([
                'credentials' => [
                    'key' => $_ENV['KEY'],
                    'secret' => $_ENV['SECRET']
                ],
                'region' => $_ENV['REGION'],
                'version' => 'latest',
                'scheme' => 'http'
            ]);

            // creates collection if it does not exist
            createHistoryCollection($conn);

            $catDetails = [];
            $spendingDist = [];
            $data = [];
            $types = ['gas', 'groceries', 'subscriptions', 'dining', 'entertainment', 'utilities'];
            $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
            $regress = [];
            $forecasts = [];
            $anomalies = [];

            foreach($types as $t) {
                $catDetails[$t]['total'] = $entry['by_category'][$t]['total'];
                $catDetails[$t]['budget'] = $entry['by_category'][$t]['budget'];
                $catDetails[$t]['percent'] = $entry['by_category'][$t]['percentage'];
                $catDetails[$t]['average'] = $entry['by_category'][$t]['avg'];
                $catDetails[$t]['trend'] = $entry['by_category'][$t]['trend'];

                $spendingDist[$t] = $entry['spending_distribution'][$t];
                
                $regress[$t]['slope'] = $entry['regression'][$t]['slope'];
                $regress[$t]['intercept'] = $entry['regression'][$t]['intercept'];
                $regress[$t]['r_value'] = $entry['regression'][$t]['r_value'];
                $regress[$t]['p_value'] = $entry['regression'][$t]['p_value'];
                $regress[$t]['std_err'] = $entry['regression'][$t]['std_err'];
                $regress[$t]['trend_line'] = $entry['regression'][$t]['trend_line'];

                $forecasts[$t] = $entry['forecasts'][$t];
                $anomalies[$t] = $entry['anomalies'][$t];

                foreach($months as $ind => $month) {
                    // Safely assign category value for the month
                    if (isset($entry['historical']['data'][$t]) && is_array($entry['historical']['data'][$t])) {
                        $data[$month][$t] = $entry['historical']['data'][$t][$ind] ?? null;
                    } else {
                        $data[$month][$t] = null;
                    }
                }
            }

            $history = [
                'c_id' => hash('sha256', $_SESSION['email']),
                'h_id' => uniqid(),
                'categories' => $catDetails,
                'total_spendings' => $entry['total_spent'],
                'total_budget' => $entry['total_budget'],
                'savings' => $entry['savings'],
                'spending_distribution' => $spendingDist,
                'monthly_data' => $data,
                'regression' => $regress,
                'recommendations' => $entry['recommendations'],
                'forecasts' => $forecasts,
                'anomalies' => $anomalies,
                'timestamp' => $entry['timestamp']
            ];
            $marshaledEntity = $m->marshalItem($history);
            $action = $conn->putItem([
                'TableName' => 'History',
                'Item' => $marshaledEntity
            ]);
        }
        catch (Exception $err) {
            echo "Database Error: " . $err;
        }
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

function createHistoryCollection($client) {
    try {    
        try {
         $client->describeTable(['TableName' => 'History']);
         return true;
        } catch(Exception $e) {
            error_log($e, 0);
        }
        
        $client->createTable([
            'AttributeDefinitions' => [
                [
                    'AttributeName' => 'c_id',
                    'AttributeType' => 'S'
                ],
                [
                    'AttributeName' => 'h_id',
                    'AttributeType' => 'S'
                ]
            ],
            'BillingMode' => 'PAY_PER_REQUEST',
            'DeletionProtectionEnabled' => false,
            'KeySchema' => [
                [
                    'AttributeName' => 'c_id',
                    'KeyType' => 'HASH'
                ],
                [
                    'AttributeName' => 'h_id',
                    'KeyType' => 'RANGE'
                ]
            ],
            'OnDemandThroughput' => [
                'MaxReadRequestUnits' => 25,
                'MaxWriteRequestUnits' => 25
            ],
            'SSESpecification' => [
                'Enabled' => false
            ],
            'StreamSpecification' => [
                'StreamEnabled' => true,
                'StreamViewType' => 'NEW_AND_OLD_IMAGES'
            ],
            'TableClass' => 'STANDARD',
            'TableName' => 'History'
        ]);
    }
    catch(Exception $dbErr) {
        echo "Database error: " . $dbErr->getMessage();
    }
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
    <link rel="stylesheet" href="DashboardStyle.css">
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
<header>
    <div class="header-container">
        <div class="logo-title">
            <img src="images/SmartSpendLogo.png" alt="Smart Spend Logo" class="logo">
            <h1>Smart Spend</h1>
        </div>
        
        <nav class="main-nav">
            <ul class="nav-list">
                <li class="nav-item"><a href="DashboardPage.php" class="nav-link"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-file-alt"></i> Documents</a></li>
                <li class="nav-item"><a href="uploadDocs.php" class="nav-link"><i class="fas fa-upload"></i> Upload</a></li>
                <li class="nav-item"><a href="SpendTrend.php" class="nav-link active"><i class="fas fa-credit-card"></i> Spend Trend</a></li>
            </ul>
        </nav>
        
        <div class="profile-dropdown" id="profileDropdown">
            <div class="profile-toggle">
                <img src="images/ProfilePic.png" alt="User" class="profile-avatar">
                <span class="profile-name"><?php echo htmlspecialchars($_SESSION['user_fname'] ?? 'User'); ?></span>
                <i class="fas fa-chevron-down dropdown-icon"></i>
            </div>
            <div class="dropdown-menu" id="dropdownMenu">
                <a href="ProfilePage.php" class="dropdown-item">View Profile</a>
                <form method="POST">
                    <button type="submit" name="logout" class="dropdown-item" style="background:none; border:none; width:100%; text-align:left; padding:0;">Sign Out</button>
                </form>
            </div>
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
    
    <div class="content-wrapper">
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

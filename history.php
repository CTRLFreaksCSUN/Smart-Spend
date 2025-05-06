<?php
// history.php
$current_page = basename($_SERVER['PHP_SELF']);
$categories = [
    'gas' => ['icon' => 'fas fa-gas-pump', 'color' => '#FF9F43'],
    'groceries' => ['icon' => 'fas fa-shopping-basket', 'color' => '#4BC0C0'],
    'subscriptions' => ['icon' => 'fas fa-newspaper', 'color' => '#9966FF'],
    'dining' => ['icon' => 'fas fa-utensils', 'color' => '#FF6384'],
    'entertainment' => ['icon' => 'fas fa-film', 'color' => '#36A2EB'],
    'utilities' => ['icon' => 'fas fa-bolt', 'color' => '#FFCD56']
];

if (isset($_POST['logout'])) {
    session_unset();
    header('Location: LoginPage.php');
    exit();
}

// Initialize session storage for history if it doesn't exist
session_start();
if (!isset($_SESSION['spending_history'])) {
    $_SESSION['spending_history'] = [];
}

// Add new entry if coming from smartspend.php with data
if (isset($_GET['from_analysis']) && isset($_GET['data'])) {
    $entry = json_decode(base64_decode($_GET['data']), true);
    $entry['timestamp'] = date('Y-m-d H:i:s');
    array_unshift($_SESSION['spending_history'], $entry);
    
    // Keep only the last 50 entries
    $_SESSION['spending_history'] = array_slice($_SESSION['spending_history'], 0, 50);
}

// Function to generate mini chart data
function generate_mini_chart_data($historical_data) {
    $labels = array_keys($historical_data['dates']);
    $datasets = [];
    
    foreach ($historical_data['data'] as $cat => $values) {
        $datasets[] = [
            'label' => ucfirst($cat),
            'data' => array_values($values),
            'borderColor' => $GLOBALS['categories'][$cat]['color'],
            'borderWidth' => 1,
            'tension' => 0.1,
            'fill' => false
        ];
    }
    
    return [
        'labels' => $labels,
        'datasets' => $datasets
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Spend - History</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="history.css">
    <link rel="stylesheet" href="DashboardStyle.css">
</head>
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
                <li class="nav-item"><a href="SpendTrend.php" class="nav-link"><i class="fas fa-credit-card"></i> Spend Trend</a></li>
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
<body>
    <div class="background"></div>
    
    <div class="content-wrapper">
        <div class="header text-center">
            <div class="container">
                <h1><i class="fas fa-history me-2"></i> Spending History</h1>
                <p class="lead mb-0">Review your past spending analyses and trends</p>
            </div>
        </div>

        <div class="container pb-5">
            <?php if (empty($_SESSION['spending_history'])): ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-clipboard-list fa-4x text-muted mb-4"></i>
                        <h3>No History Yet</h3>
                        <p class="text-muted">Your spending analyses will appear here after you use Smart Spend</p>
                        <a href="smartspend.php" class="btn btn-primary mt-3">
                            <i class="fas fa-chart-bar me-2"></i> Analyze Spending
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-filter me-2"></i> Filters</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Time Period</label>
                                    <select class="form-select" id="timeFilter">
                                        <option value="all">All Time</option>
                                        <option value="7d">Last 7 Days</option>
                                        <option value="30d">Last 30 Days</option>
                                        <option value="90d">Last 90 Days</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Category</label>
                                    <select class="form-select" id="categoryFilter">
                                        <option value="all">All Categories</option>
                                        <?php foreach ($categories as $cat => $data): ?>
                                            <option value="<?= $cat ?>"><?= ucfirst($cat) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button class="btn btn-primary w-100" id="applyFilters">
                                    <i class="fas fa-filter me-2"></i> Apply Filters
                                </button>
                                <button class="btn btn-outline-danger w-100 mt-2" id="clearHistory">
                                    <i class="fas fa-trash me-2"></i> Clear History
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-list me-2"></i> Analysis History</h5>
                                <span class="badge bg-primary rounded-pill">
                                    <?= count($_SESSION['spending_history']) ?> records
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="history-list" id="historyList">
                                    <?php foreach ($_SESSION['spending_history'] as $index => $entry): ?>
                                        <div class="history-item" data-timestamp="<?= strtotime($entry['timestamp']) ?>" data-categories="<?= implode(',', array_keys($entry['by_category'])) ?>">
                                            <div class="history-item-header">
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-calendar-day me-2"></i>
                                                    <strong><?= date('M j, Y g:i a', strtotime($entry['timestamp'])) ?></strong>
                                                </div>
                                                <div class="history-actions">
                                                    <button class="btn btn-sm btn-outline-primary view-details" data-index="<?= $index ?>">
                                                        <i class="fas fa-expand"></i> Details
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="history-item-summary">
                                                <div class="row">
                                                    <div class="col-4">
                                                        <div class="summary-stat">
                                                            <small>Total Spent</small>
                                                            <div class="stat-value">$<?= number_format($entry['total_spent'], 2) ?></div>
                                                        </div>
                                                    </div>
                                                    <div class="col-4">
                                                        <div class="summary-stat">
                                                            <small>Budget</small>
                                                            <div class="stat-value">$<?= number_format($entry['total_budget'], 2) ?></div>
                                                        </div>
                                                    </div>
                                                    <div class="col-4">
                                                        <div class="summary-stat">
                                                            <small>Savings</small>
                                                            <div class="stat-value <?= $entry['savings'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                                                $<?= number_format(abs($entry['savings']), 2) ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="history-item-chart">
                                                <canvas class="mini-chart" id="miniChart<?= $index ?>"></canvas>
                                            </div>
                                            <div class="history-item-categories">
                                                <?php foreach ($entry['by_category'] as $cat => $data): ?>
                                                    <span class="category-badge" style="background: <?= $categories[$cat]['color'] ?>20; border-left: 3px solid <?= $categories[$cat]['color'] ?>">
                                                        <i class="<?= $categories[$cat]['icon'] ?> me-1"></i>
                                                        <?= ucfirst($cat) ?>: 
                                                        <span class="<?= $data['percentage'] > 100 ? 'text-danger' : 'text-success' ?>">
                                                            <?= number_format($data['percentage'], 1) ?>%
                                                        </span>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

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

    <div class="modal fade" id="historyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Analysis Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modalBodyContent">
                    <!-- Content will be loaded via JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="bubbleChat.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize mini charts
        <?php foreach ($_SESSION['spending_history'] as $index => $entry): ?>
            <?php $chartData = generate_mini_chart_data($entry['historical']); ?>
            const ctx<?= $index ?> = document.getElementById('miniChart<?= $index ?>');
            if (ctx<?= $index ?>) {
                new Chart(ctx<?= $index ?>, {
                    type: 'line',
                    data: {
                        labels: <?= json_encode($chartData['labels']) ?>,
                        datasets: <?= json_encode($chartData['datasets']) ?>
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: { enabled: false }
                        },
                        scales: {
                            x: { display: false },
                            y: { display: false }
                        },
                        elements: {
                            point: { radius: 0 }
                        }
                    }
                });
            }
        <?php endforeach; ?>

        // View details button handler
        document.querySelectorAll('.view-details').forEach(button => {
            button.addEventListener('click', function() {
                const index = this.getAttribute('data-index');
                fetchAnalysisDetails(index);
            });
        });

        // Apply filters
        document.getElementById('applyFilters').addEventListener('click', function() {
            const timeFilter = document.getElementById('timeFilter').value;
            const categoryFilter = document.getElementById('categoryFilter').value;
            const now = new Date().getTime();
            
            document.querySelectorAll('.history-item').forEach(item => {
                const timestamp = parseInt(item.getAttribute('data-timestamp')) * 1000;
                const itemCategories = item.getAttribute('data-categories').split(',');
                
                let timeMatch = true;
                if (timeFilter !== 'all') {
                    const days = parseInt(timeFilter);
                    timeMatch = (now - timestamp) <= (days * 24 * 60 * 60 * 1000);
                }
                
                let categoryMatch = true;
                if (categoryFilter !== 'all') {
                    categoryMatch = itemCategories.includes(categoryFilter);
                }
                
                item.style.display = (timeMatch && categoryMatch) ? 'block' : 'none';
            });
        });

        // Clear history
        document.getElementById('clearHistory').addEventListener('click', function() {
            if (confirm('Are you sure you want to clear all history?')) {
                fetch('clear_history.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                }).then(response => {
                    if (response.ok) {
                        window.location.reload();
                    }
                });
            }
        });

        // Function to fetch analysis details
        function fetchAnalysisDetails(index) {
            const entry = <?= json_encode($_SESSION['spending_history']) ?>[index];
            const modalBody = document.getElementById('modalBodyContent');
            
            // Create detailed view HTML
            let html = `
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="stat-card">
                            <h6>Total Spent</h6>
                            <div class="stat-value">$${entry.total_spent.toFixed(2)}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <h6>Total Budget</h6>
                            <div class="stat-value">$${entry.total_budget.toFixed(2)}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <h6>Savings</h6>
                            <div class="stat-value ${entry.savings >= 0 ? 'text-success' : 'text-danger'}">
                                $${Math.abs(entry.savings).toFixed(2)}
                            </div>
                        </div>
                    </div>
                </div>
                
                <h5 class="mt-4 mb-3"><i class="fas fa-chart-pie me-2"></i> Spending Distribution</h5>
                <div class="mb-4">
            `;
            
            // Add distribution bars
            for (const [cat, perc] of Object.entries(entry.spending_distribution)) {
                html += `
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="d-flex align-items-center">
                                <i class="${categories[cat].icon} me-2" style="color: ${categories[cat].color}"></i>
                                <span>${cat.charAt(0).toUpperCase() + cat.slice(1)}</span>
                            </div>
                            <span>${perc.toFixed(1)}%</span>
                        </div>
                        <div class="distribution-bar">
                            <div class="distribution-fill" style="width: ${perc}%; background: ${categories[cat].color}"></div>
                        </div>
                    </div>
                `;
            }
            
            html += `
                </div>
                
                <h5 class="mt-4 mb-3"><i class="fas fa-chart-line me-2"></i> Historical Trend</h5>
                <div class="chart-container">
                    <canvas id="detailChart"></canvas>
                </div>
                
                <h5 class="mt-4 mb-3"><i class="fas fa-lightbulb me-2"></i> Recommendations</h5>
                <div class="recommendations">
                    <ul class="list-group">
            `;
            
            // Add recommendations
            if (entry.recommendations && entry.recommendations.length > 0) {
                entry.recommendations.forEach(rec => {
                    html += `<li class="list-group-item">${rec}</li>`;
                });
            } else {
                html += `<li class="list-group-item text-muted">No specific recommendations for this analysis</li>`;
            }
            
            html += `
                    </ul>
                </div>
            `;
            
            modalBody.innerHTML = html;
            
            // Initialize the detailed chart
            const detailCtx = document.getElementById('detailChart');
            if (detailCtx) {
                const detailDatasets = [];
                
                for (const [cat, values] of Object.entries(entry.historical.data)) {
                    detailDatasets.push({
                        label: cat.charAt(0).toUpperCase() + cat.slice(1),
                        data: values,
                        backgroundColor: categories[cat].color + '33',
                        borderColor: categories[cat].color,
                        borderWidth: 2,
                        tension: 0.1
                    });
                }
                
                new Chart(detailCtx, {
                    type: 'line',
                    data: {
                        labels: entry.historical.dates,
                        datasets: detailDatasets
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
            }
            
            // Show the modal
            const modal = new bootstrap.Modal(document.getElementById('historyModal'));
            modal.show();
        }
        const dropdown = document.getElementById('profileDropdown');

        // Toggle dropdown on click
        dropdown.addEventListener('click', function (e) {
            e.stopPropagation();
            dropdown.classList.toggle('show');
        });

        // Close dropdown if clicking outside
        window.addEventListener('click', function (e) {
            if (!dropdown.contains(e.target)) {
                dropdown.classList.remove('show');
            }
        });
    });
    </script>
</body>
</html>
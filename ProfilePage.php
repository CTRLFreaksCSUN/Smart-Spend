<?php
session_start();

use Aws\DynamoDb\Marshaler;
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Dotenv\Dotenv;

require __DIR__ . '/vendor/autoload.php';
Dotenv::createImmutable(__DIR__)->load();

$user = [
    'name'      => $_SESSION['user_fname'].' '.$_SESSION['user_middlename'].' '.$_SESSION['user_lastname'],
    'email'     => $_SESSION['email'],
    'avatar'    => 'images/ProfilePic.png',
    'join_date' => 'Joined January 2024',
];

$allKeys = ['property','utilities','medical','food','shopping','transport','entertainment'];
$savingsKeys = ['car','emergency_fund','general_savings','vacation'];

try {
    $client    = new DynamoDbClient([
        'region'      => $_ENV['REGION'],
        'version'     => 'latest',
        'credentials' => [
            'key'    => $_ENV['KEY'],
            'secret' => $_ENV['SECRET'],
        ],
        'scheme'      => 'http',
    ]);
    $marshaler = new Marshaler();
    $customerId = hash('sha256', $user['email']);

    $resp = $client->getItem([
        'TableName' => 'Finance',
        'Key'       => $marshaler->marshalItem([
            'c_id' => hash('sha256', $user['email'])
        ]),
    ]);

    $rawExpenses = [];
    if (! empty($resp['Item']['expenses'])) {
        $rawExpenses = $marshaler->unmarshalValue($resp['Item']['expenses']);
    }
    // NEW: pull income (defaults to 0)
    $income = isset($resp['Item']['income'])
            ? (float)$marshaler->unmarshalValue($resp['Item']['income'])
            : 0.0;

    if (isset($_POST['update-income'])) {
        $newIncome = floatval($_POST['income']);
        try {
            $client->updateItem([
                'TableName'                 => 'Finance',
                'Key'                       => $marshaler->marshalItem([
                    'c_id' => hash('sha256', $user['email'])
                ]),
                'UpdateExpression'          => 'SET income = :inc',
                'ExpressionAttributeValues' => [
                    ':inc' => $marshaler->marshalValue($newIncome),
                ],
            ]);
            // Redirect so the refreshed page shows the new value
            header('Location: ProfilePage.php');
            exit;
        } catch (DynamoDbException $e) {
            echo '<div class="error">Failed to update income: '
                .htmlspecialchars($e->getMessage()).'</div>';
        }
    }

    $categoryLabels = [];
    $categoryData   = [];
    foreach ($allKeys as $key) {
        $categoryLabels[] = ucfirst($key);
        $categoryData[]   = isset($rawExpenses[$key]) ? (float)$rawExpenses[$key] : 0.0;
    }
} catch (DynamoDbException $e) {
    error_log("DynamoDB error: ".$e->getMessage());
    $categoryLabels = array_map('ucfirst', $allKeys);
    $categoryData   = array_fill(0, count($allKeys), 0.0);
}

if (isset($_POST['save_categories']) && is_array($_POST['category_data'])) {
    $newValues   = array_map('floatval', $_POST['category_data']);
    $expensesMap = [];
    foreach ($allKeys as $i => $key) {
        $expensesMap[$key] = $newValues[$i];
    }
    try {
        $client->updateItem([
            'TableName'                 => 'Finance',
            'Key'                       => $marshaler->marshalItem(['c_id'=>hash('sha256',$user['email'])]),
            'UpdateExpression'          => 'SET expenses = :e',
            'ExpressionAttributeValues' => $marshaler->marshalItem([':e'=>$expensesMap]),
        ]);
        echo '<script>alert("Categories saved.");window.location="ProfilePage.php";</script>';
        exit;
    } catch (DynamoDbException $e) {
        echo '<div class="error">Failed to save: '.htmlspecialchars($e->getMessage()).'</div>';
    }
}

if (isset($_POST['reset_goal'])) {
    $cat = $_POST['reset_goal'];
    $client->updateItem([
        'TableName'                 => 'Finance',
        'Key'                       => $marshaler->marshalItem(['c_id'=> $customerId]),
        'UpdateExpression'          => "SET savings_goals.$cat = :zero",
        'ExpressionAttributeValues' => [
            ':zero' => $marshaler->marshalValue(0.0),
        ],
    ]);
    header('Location: ProfilePage.php');
    exit;
}

// Reset a single current savings to 0
if (isset($_POST['reset_current'])) {
    $cat = $_POST['reset_current'];
    $client->updateItem([
        'TableName'                 => 'Finance',
        'Key'                       => $marshaler->marshalItem(['c_id'=> $customerId]),
        'UpdateExpression'          => "SET current_savings.$cat = :zero",
        'ExpressionAttributeValues' => [
            ':zero' => $marshaler->marshalValue(0.0),
        ],
    ]);
    header('Location: ProfilePage.php');
    exit;
}

if (isset($_POST['add_savings']) && is_array($_POST['new_savings'])) {
      // 1) Unmarshal the existing map (default to zeros)
      $existing = [];
      if (! empty($resp['Item']['current_savings'])) {
          $existing = $marshaler->unmarshalValue($resp['Item']['current_savings']);
      }
      // 2) Build the new map by summing existing + new
      $updated = [];
      foreach (['vacation','car','general_savings','emergency_fund'] as $cat) {
        $old = isset($existing[$cat]) ? (float)$existing[$cat] : 0.0;
        $inc = isset($_POST['new_savings'][$cat]) ? (float)$_POST['new_savings'][$cat] : 0.0;
        $updated[$cat] = $old + $inc;
    }
      // 3) Push back to DynamoDB
      try {
          $client->updateItem([
              'TableName'                 => 'Finance',
              'Key'                       => $marshaler->marshalItem(['c_id' => hash('sha256',$user['email'])]),
              'UpdateExpression'          => 'SET current_savings = :cs',
              'ExpressionAttributeValues' => $marshaler->marshalItem([':cs' => $updated]),
          ]);
          // refresh so you see the new totals
          header('Location: ProfilePage.php');
          exit;
      } catch (DynamoDbException $e) {
          echo "<div class='error'>Could not update savings: ".htmlspecialchars($e->getMessage())."</div>";
      }
  }
  $rawSavings = [];
  if (isset($resp['Item']['current_savings'])) {
      $rawSavings = $marshaler->unmarshalValue(
        $resp['Item']['current_savings']
      );
  }
  foreach ($savingsKeys as $k) {
      $displaySavings[$k] = isset($rawSavings[$k]) ? (float)$rawSavings[$k] : 0.0;
  }

  $rawGoals = [];
  if (! empty($resp['Item']['savings_goals'])) {
      $rawGoals = $marshaler->unmarshalValue(
        $resp['Item']['savings_goals']
      );
  }
  foreach ($savingsKeys as $k) {
      // if the user has set a goal in Dynamo, show it; otherwise default 0.0
      $displayGoals[$k] = isset($rawGoals[$k]) 
         ? (float)$rawGoals[$k] 
         : 0.0;
  }

if (isset($_POST['update_goals']) && is_array($_POST['savings_goals'])) {
  $newGoals = array_map('floatval', $_POST['savings_goals']);
  $client->updateItem([
    'TableName'=>'Finance',
    'Key'=>$marshaler->marshalItem(['c_id'=>$customerId]),
    'UpdateExpression'=>'SET savings_goals = :sg',
    'ExpressionAttributeValues'=>[
      ':sg'=>$marshaler->marshalValue($newGoals)
    ],
  ]);
  header('Location: ProfilePage.php');
  exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Smart Spend – Profile</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="DashboardStyle.css">
  <link rel="stylesheet" href="ProfileStyle.css">
  <script>
  function switchView(e, name) {
    if (e) e.preventDefault();
    // Grab the inline form
    const form = document.querySelector('.personal-section .change-password');
    // Toggle “active” only when name matches
    form.classList.toggle('active', name === 'change-password');
  }
</script>
</head>
<body>
  <div class="profile-layout">
    <!-- SIDEBAR -->
    <aside class="profile-sidebar">
      <div class="sidebar-brand">
        <img src="images/SmartSpendLogo.png" alt="Logo" class="logo">
        <span>Smart Spend</span>
      </div>
      <nav class="sidebar-nav">
        <ul>
          <li class="active"><a href="#"><i class="fas fa-user-circle"></i><span>Profile</span><div class="nav-dot"></div></a></li>
          <li><a href="DashboardPage.php"><i class="fas fa-chart-pie"></i><span>Dashboard</span></a></li>
        </ul>
      </nav>
      <form method="POST" target="_top">
        <div class="sidebar-footer">
          <div class="user-card">
            <img src="<?=htmlspecialchars($user['avatar'])?>" class="user-avatar">
            <div>
              <span class="user-name"><?=htmlspecialchars($user['name'])?></span>
              <span class="user-email"><?=htmlspecialchars($user['email'])?></span>
            </div>
            <button type="submit" name="logout" class="logout-btn"><i class="fas fa-sign-out-alt"></i></button>
          </div>
        </div>
      </form>
    </aside>

    <!-- MAIN -->
    <main class="profile-main">
      <div class="profile-header">
        <h1>My Profile</h1>
      </div>

      <!-- PROFILE CARD -->
      <div class="profile-card">
        <div class="profile-banner"></div>
        <div class="profile-info">
          <img src="<?=htmlspecialchars($user['avatar'])?>" class="profile-avatar">
          <div class="profile-meta">
            <h2><?=htmlspecialchars($user['name'])?></h2>
            <p class="profile-join"><i class="fas fa-calendar-alt"></i> <?=htmlspecialchars($user['join_date'])?></p>
          </div>
        </div>
      </div>

      <!-- SECTIONS -->
      <div class="profile-sections">
        <section class="profile-section personal-section">
          <div class="section-content">
            <div class="section-header section-header--full">
            <h3><i class="fas fa-user-tag"></i> Personal Information</h3>
          </div>
            <!-- Full Name -->
            <div class="info-row">
              <span class="info-label">Full Name</span>
              <span class="info-value"><?=htmlspecialchars($user['name'])?></span>
            </div>

            <!-- Email -->
            <div class="info-row">
              <span class="info-label">Email</span>
              <span class="info-value"><?=htmlspecialchars($user['email'])?></span>
            </div>

            <!-- Monthly Income -->
            <div class="info-row income-row">
              <span class="info-label">Monthly Income</span>
              <div class="input-group-inline">
                <span class="input-prefix">$</span>
                <input id="income" name="income" type="number" step="0.01"
                      value="<?=htmlspecialchars(number_format($income,2,'.',''))?>">
                <button form="income-form" type="submit" class="btn btn-small">Update</button>
              </div>
            </div>

            <!-- Security Sub‑Heading spans both columns -->
            <div class="section-subheader" style="grid-column:1/-1;">
              <i class="fas fa-lock"></i>
              <h4>Security</h4>
            </div>

            <!-- Password row -->
            <div class="info-row security-row">
              <span class="info-label">Password</span>
              <button type="button"
                      onclick="switchView(event,'change-password')"
                      class="btn btn-primary small"
              >Change Password</button>
            </div>
            <!-- INLINE CHANGE‐PASSWORD FORM -->
            <form class="change-password">
              <h4>Change Password</h4>
              <div class="cp-row">
                <label for="old-password">Old Password</label>
                <input id="old-password" type="password" name="old-password" required>
              </div>
              <div class="cp-row">
                <label for="new-password">New Password</label>
                <input id="new-password" type="password" name="new-password" required>
              </div>
              <div class="cp-row">
                <label for="re-new-password">Re‑enter New Password</label>
                <input id="re-new-password" type="password" name="reEn-password" required>
              </div>
              <div class="cp-actions">
                <button type="button" onclick="switchView(null,'')" class="btn-link">Cancel</button>
                <button type="submit" name="update-password" class="btn-primary">Update</button>
              </div>
            </form>
          </div>
        </section>

        <section class="profile-section savings-section">
          <div class="section-header">
            <h3><i class="fas fa-piggy-bank"></i> Savings Goals & Deposits</h3>
          </div>
          <div class="section-content">
            <form method="POST" class="section-content savings-grid">
              <h4>Edit Goals</h4>
              <?php foreach($savingsKeys as $key): 
                $label       = ucwords(str_replace('_',' ',$key));
                $goalValue   = $displayGoals[$key]    ?? 0.0;
                $currentValue= $displaySavings[$key] ?? 0.0;
              ?>
                <div class="grid-row">
                  <label for="goal_<?= $key ?>"><?= $label ?> Goal</label>
                  <div class="input-group">
                    <span class="input-prefix">$</span>
                    <input
                      id="goal_<?= $key ?>"
                      name="savings_goals[<?= $key ?>]"
                      type="number"
                      step="0.01"
                      value="<?= number_format($goalValue,2,'.','') ?>"
                    >
                    <button 
                      type="submit" 
                      name="reset_goal" 
                      value="<?= $key ?>"
                      class="btn-small"
                      onclick="return confirm('Zero out <?= $label ?> goal?')"
                    >Reset</button>
                  </div>

                  <label><?= $label ?> Saved</label>
                  <div class="input-group">
                    <span class="static-value">$<?= number_format($currentValue,2) ?></span>
                    <button 
                      type="submit" 
                      name="reset_current" 
                      value="<?= $key ?>"
                      class="btn-small"
                      onclick="return confirm('Zero out <?= $label ?> saved?')"
                    >Reset</button>
                  </div>
                </div>
              <?php endforeach; ?>

              <h4>Make a Deposit</h4>
              <?php foreach($savingsKeys as $key): 
                $label = ucwords(str_replace('_',' ',$key));
              ?>
                <div class="grid-row">
                  <label for="dep_<?= $key ?>"><?= $label ?> Deposit</label>
                  <div class="input-group">
                    <input
                      id="dep_<?= $key ?>"
                      name="new_savings[<?= $key ?>]"
                      type="number"
                      step="0.01"
                      placeholder="0.00"
                    >
                  </div>
                </div>
              <?php endforeach; ?>

              <div class="grid-row actions-row">
                <button name="add_savings"  type="submit" class="btn-primary">Save Deposits</button>
                <button name="update_goals" type="submit" class="btn-primary">Update Goals</button>
              </div>
            </form>
          </div>
        </section>
    </main>
  </div>
</body>
</html>
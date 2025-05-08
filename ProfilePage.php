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
    function switchView(e,name){
      if(e) e.preventDefault();
      document.querySelectorAll('.profile-content').forEach(v=>v.style.display='none');
      const sel=document.querySelector('.'+name);
      if(sel) sel.style.display='block';
      window.location.hash=name;
    }
    document.addEventListener('DOMContentLoaded',()=>{
      const h=window.location.hash.slice(1);
      if(h) switchView(null,h);
    });
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
        <button class="edit-btn"><i class="fas fa-pencil-alt"></i> Edit Profile</button>
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
        <section class="profile-section">
          <div class="section-header">
            <h3><i class="fas fa-user-tag"></i> Personal Information</h3>
            <button class="section-edit"><i class="fas fa-pencil-alt"></i></button>
          </div>
          <div class="section-content">
            <div class="info-row"><span class="info-label">Full Name</span><span class="info-value"><?=htmlspecialchars($user['name'])?></span></div>
            <div class="info-row"><span class="info-label">Email</span><span class="info-value"><?=htmlspecialchars($user['email'])?></span></div>
          </div>
        </section>

        <!-- SECURITY -->
        <section class="profile-section security-section">
          <div class="section-header">
            <h3><i class="fas fa-lock"></i> Security</h3>
            <button class="section-edit"><i class="fas fa-pencil-alt"></i></button>
          </div>
          <div class="section-content">
            <i class="fas fa-key security-icon"></i>
            <span class="security-label">Password</span>
            <button type="button" onclick="switchView(event,'change-password')" class="security-action">Change Password</button>
          </div>
        </section>

        <!-- EDIT CATEGORIES -->
        <section class="profile-section category-edit-section">
          <div class="section-header">
            <h3><i class="fas fa-chart-pie"></i> Edit Categories</h3>
          </div>
          <form method="POST" class="category-form">
            <div class="category-list">
              <?php foreach ($categoryLabels as $i => $label): ?>
                <div class="category-item">
                  <label for="cat-<?=$i?>"><?=htmlspecialchars($label)?></label>
                  <input
                    type="number"
                    id="cat-<?=$i?>"
                    name="category_data[]"
                    step="0.01"
                    value="<?=htmlspecialchars($categoryData[$i])?>"
                    required
                  />
                </div>
              <?php endforeach; ?>
            </div>
            <button type="submit" name="save_categories" class="btn-save">Save Changes</button>
          </form>
        </section>
      </div>

      <!-- CHANGE PASSWORD FORM -->
      <form class="profile-content change-password" method="POST" action="">
        <h1>Change Password</h1>
        <?php if (!empty($_SESSION['error'])): ?>
          <div class="error"><?=htmlspecialchars($_SESSION['error'])?></div>
        <?php endif; ?>
        <label>Old Password</label>
        <input type="password" name="old-password" required>
        <label>New Password</label>
        <input type="password" name="new-password" required>
        <label>Re-enter New Password</label>
        <input type="password" name="reEn-password" required>
        <div class="actions">
          <a href="ProfilePage.php" class="security-action">Cancel</a>
          <button type="submit" name="update-password" class="security-action">Update</button>
        </div>
      </form>
    </main>
  </div>
</body>
</html>
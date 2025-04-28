<?php  
  use Aws\DynamoDb\DynamoDbClient;
  use Aws\DynamoDb\Marshaler;
  use Aws\DynamoDb\Exception\DynamoDbException;
  use Aws\Exception\UnrecognizedClientException;
  use Aws\Exception\ValidationException;
  
  use Dotenv\Dotenv;
  require __DIR__.'/vendor/autoload.php';
  $env = Dotenv::createImmutable(__DIR__);
  $env->load();
  
  $key = $_ENV["KEY"];
  $endPt = $_ENV["ENDPOINT"];
  $region = $_ENV["REGION"];
  $secret = $_ENV["SECRET"];
  
  // Connect to the database first
  $client = connectToDatabase($key, $secret, $region);
  
  // Process form submission if the form was submitted
  if (isset($_POST['finish'])) {
    processFormData($client);
  }
  
  // Connect to database function
  function connectToDatabase($key, $secret, $region) {
    try {
      $dbClient = DynamoDbClient::factory(array(
        'credentials' => array(
          'key' => $key,
          'secret' => $secret
        ),
        'region' => $region,
        'version' => 'latest',
        'scheme' => 'http'
      ));
      
      // Create collection (if does not exist)
      $db = createFinanceCollection($dbClient);
      return $dbClient;
    }
    catch(UnrecognizedClientException $ucerr) {
      echo 'Failed to connect to AWS: ' . $ucerr->getMessage();
    }
    catch(ValidationException $verr) {
      echo 'Failed to validate user: ' . $verr->getMessage();
    }
    catch(InvalidArgumentException $iaerr) {
      echo 'Invalid argument detected: ' . $iaerr->getMessage();
    }
    catch(ResourceNotFoundException $rerr) {
      echo 'Could not find requested resource: ' . $rerr->getMessage();
    }
  }
  
  // Modified table creation function (fixed the key schema and attribute definitions)
  function createFinanceCollection($dbClient) {
    $new_collec = null;
    try {
      // Check if table already exists
      try {
        $dbClient->describeTable(['TableName' => 'Finance']);
        // Table exists, no need to create
        return true;
      } catch (Exception $e) {
        // Table doesn't exist, create it
      }
      
      $new_collec = $dbClient->createTable([
        'AttributeDefinitions' => [
          [
             'AttributeName' => 'f_id',
             'AttributeType' => 'S'
          ],
          [
             'AttributeName' => 'c_id',
             'AttributeType' => 'S'
          ]
        ],
        'BillingMode' => 'PAY_PER_REQUEST',
        'DeletionProtectionEnabled' => false,
        'KeySchema' => [
          [
             'AttributeName' => 'f_id',
             'KeyType' => 'HASH'  // Partition key
          ],
          [
             'AttributeName' => 'c_id',
             'KeyType' => 'RANGE'  // Sort key (changed from HASH to RANGE)
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
        'TableName' => 'Finance',
        'Tags' => [
          [
             'Key' => 'Monthly Income',
             'Value' => 'income'
          ],
          [
             'Key' => 'Total Savings',
             'Value' => 'savings'
          ],
          [
             'Key' => 'Monthly Budget',
             'Value' => 'budget'
          ],
          [
             'Key' => 'Dining',
             'Value' => 'dining'
          ],
          [
             'Key' => 'Groceries',
             'Value' => 'groceries'
          ],
          [
             'Key' => 'Housing',
             'Value' => 'housing'
          ],
          [
             'Key' => 'Transportation',
             'Value' => 'transportation'
          ],
          [
             'Key' => 'Utilities',
             'Value' => 'utilities'
          ],
          [
             'Key' => 'Shopping',
             'Value' => 'shopping'
          ],
          [
             'Key' => 'Entertainment',
             'Value' => 'entertainment'
          ]
        ]
      ]);
    }
    catch (DynamoDbException $dbErr) {
      // Table might already exist
      if (strpos($dbErr->getMessage(), 'ResourceInUseException') !== false) {
        // Table exists, no problem
        return true;
      }
      error_log($dbErr->getMessage(), 0);
    }
    finally {
      return $new_collec;
    }
  }
  
  // Process form data and save to DynamoDB
  function processFormData($client) {
    try {
      // Get form data
      $income = $_POST['income'];
      $savingsGoals = isset($_POST['savings_goals']) ? $_POST['savings_goals'] : [];
      $budget = isset($_POST['budget']) ? $_POST['budget'] : 'Flexible';
      $expenses = isset($_POST['expenses']) ? $_POST['expenses'] : [];
      
      // Generate unique IDs
      $financeId = uniqid('fin_');
      $customerId = uniqid('cust_');
      
      // Create a marshaler to convert PHP arrays to DynamoDB format
      $marshaler = new Marshaler();
      
      // Prepare item data
      $item = [
        'f_id' => $financeId,
        'c_id' => $customerId,
        'income' => (int)$income,
        'savings_goals' => $savingsGoals,
        'budget_type' => $budget,
        'expense_categories' => $expenses,
        'created_at' => date('Y-m-d H:i:s')
      ];
      
      // Convert to DynamoDB format
      $marshaledItem = $marshaler->marshalItem($item);
      
      // Add item to table
      $result = $client->putItem([
        'TableName' => 'Finance',
        'Item' => $marshaledItem
      ]);
      
      // Redirect to dashboard on success
      header('Location: DashboardPage.php');
      exit;
    }
    catch (Exception $e) {
      // Log error and display message
      error_log($e->getMessage(), 0);
      $error = "There was an error saving your information. Please try again.";
    }
  }
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="author" content="CTRL_Freaks">
  <meta name="description" content="Smart Spend registration page">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Smart Spend - Setup</title>
  <link rel="stylesheet" href="ContinuedRegStyle.css">
  <link rel="stylesheet" href="bubbleChatStyle.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>
  <div class="container">
    <header>
      <h1> 
        <img src="images/SmartSpendLogo.png" alt="Smart Spend" style="width:70px; height:60px;"> 
        Smart Spend
      </h1>
    </header>

    <main>
      <div class="register">
        <h2>Getting Started</h2>
        <p class="intro-text">Complete your profile to personalize your financial experience</p>

        <!-- Add form tag with POST method -->
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
          
          <!-- Monthly Income -->
          <div class="form-group">
            <span class="section-title">Monthly Income</span>
            <input type="number" id="income" name="income" placeholder="Enter your monthly income" required class="styled-input">
          </div>

          <!-- Savings Goals -->
          <span class="section-title">Savings Goals</span>
          <div class="checkbox-group" id="default-savings-goals">
            <label><input type="checkbox" name="savings_goals[]" value="Vacation"> Vacation</label>
            <label><input type="checkbox" name="savings_goals[]" value="Car"> New Car</label>
            <label><input type="checkbox" name="savings_goals[]" value="Savings"> General Savings</label>
            <label><input type="checkbox" name="savings_goals[]" value="Emergency"> Emergency Fund</label>
          </div>

          <div id="custom-savings-goals" class="custom-input-wrapper">
            <input type="text" name="savings_goals[]" class="custom-input" placeholder="Custom goal name">
          </div>

          <button type="button" class="rounded-button small-add-button" onclick="addSavingsGoal()">
            <span>+ Add Custom Goal</span>
          </button>

          <!-- Preferred Budget -->
          <span class="section-title">Budget Preference</span>
          <div class="radio-group">
            <label><input type="radio" name="budget" value="Fixed" id="budget-fixed"> Fixed Budget</label>
            <label><input type="radio" name="budget" value="Flexible" id="budget-flexible"> Flexible Budget</label>
          </div>

          <!-- Expense Categories -->
          <span class="section-title">Expense Categories</span>
          <div class="checkbox-group">
            <label><input type="checkbox" name="expenses[]" value="Food"> Food & Dining</label>
            <label><input type="checkbox" name="expenses[]" value="Rent"> Housing</label>
            <label><input type="checkbox" name="expenses[]" value="Transportation"> Transportation</label>
            <label><input type="checkbox" name="expenses[]" value="Utilities"> Utilities</label>
            <label><input type="checkbox" name="expenses[]" value="Shopping"> Shopping</label>
            <label><input type="checkbox" name="expenses[]" value="Entertainment"> Entertainment</label>
          </div>

          <!-- Custom Categories -->
          <div id="custom-expenses" class="custom-input-wrapper">
            <input type="text" name="expenses[]" class="custom-input" placeholder="Custom category name">
          </div>

          <button type="button" class="rounded-button small-add-button" onclick="addExpenseField()">
            <span>+ Add Category</span>
          </button>

          <!-- Form Actions -->
          <div style="display: flex; justify-content: center; gap: 20px; margin-top: 40px;">
            <input type="submit" name="cancel" value="Cancel" class="register-button">
            <input type="submit" name="finish" value="Complete Setup" class="register-button">
          </div>
        </form>
      </div>
    </main>

    <footer>
      &copy; CTRL_Freaks - 2025 | Smart Spend Financial Solutions
    </footer>
  </div>

  <!-- Floating Chat Bubble -->
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
    function addExpenseField() {
      const container = document.getElementById("custom-expenses");
      const input = document.createElement("input");
      input.type = "text";
      input.name = "expenses[]";
      input.placeholder = "Custom category name";
      input.className = "custom-input";
      container.appendChild(input);
    }

    function addSavingsGoal() {
      const container = document.getElementById("custom-savings-goals");
      const input = document.createElement("input");
      input.type = "text";
      input.name = "savings_goals[]";
      input.placeholder = "Custom goal name";
      input.className = "custom-input";
      container.appendChild(input);
    }
  </script>

  <script src="bubbleChat.js"></script>
</body>
</html>
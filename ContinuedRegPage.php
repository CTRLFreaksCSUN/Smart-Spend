<?php  
  session_start();
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
    catch(InvalidArgumentException $iaerr) {
      echo 'Invalid argument detected: ' . $iaerr->getMessage();
    }
  }

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
             'AttributeName' => 'c_id',
             'AttributeType' => 'S'
          ]
        ],
        'BillingMode' => 'PAY_PER_REQUEST',
        'DeletionProtectionEnabled' => false,
        'KeySchema' => [
          [
             'AttributeName' => 'c_id',
             'KeyType' => 'HASH' 
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
             'Key' => 'Rent',
             'Value' => 'rent'
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
  function processFormData(DynamoDbClient $client) {
    $marshaler  = new Marshaler();
    $customerId = hash('sha256', $_SESSION['email']);

    // Collect income, budget and budgets
    $income  = (int)($_POST['income'] ?? 0);
    $budget  = $_POST['budget'] ?? 'Flexible';
    $budgets = array_map('floatval', [
        'entertainment' => $_POST['entertain'] ?? 0,
        'rent'          => $_POST['rent']      ?? 0,
        'food'          => $_POST['food']      ?? 0,
        'medical'       => $_POST['medical']   ?? 0,
        'shopping'      => $_POST['shopping']  ?? 0,
        'transport'     => $_POST['transport'] ?? 0,
        'utilities'     => $_POST['util']      ?? 0,
    ]);

    // Build savings goals map
    $savingsGoals = [];
    foreach (['vacation','car','general_savings','emergency_fund'] as $cat) {
        if (!empty($_POST['savings_goals']) && in_array($cat, $_POST['savings_goals'], true)) {
            $savingsGoals[$cat] = (float)($_POST['savings_amounts'][$cat] ?? 0);
        }
    }

    // Update DynamoDB
    try {
        $client->updateItem([
            'TableName'                 => 'Finance',
            'Key'                       => $marshaler->marshalItem(['c_id' => $customerId]),
            'UpdateExpression'          => 'SET 
                income        = :inc,
                budget_type   = :bt,
                budgets       = :b,
                savings_goals = :sg',
            'ExpressionAttributeValues' => [
                ':inc' => $marshaler->marshalValue($income),
                ':bt'  => $marshaler->marshalValue($budget),
                ':b'   => $marshaler->marshalValue($budgets),
                ':sg'  => $marshaler->marshalValue($savingsGoals),
            ],
        ]);

        header('Location: DashboardPage.php');
        exit;
    } catch (DynamoDbException $e) {
        error_log("DynamoDB update error: " . $e->getMessage());
        // Optionally set a flash message or display an error above the form
        $_SESSION['error'] = "Sorry, we couldn't save your settings—please try again.";
    }
}
?>

<?php
  if (isset($_POST['cancel'])) {
    session_unset();
    header('Location: LoginPage.php');
    exit();
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
            <input type="number" id="income" name="income" placeholder="Enter your monthly income" class="styled-input">
          </div>

          <!-- Savings Goals -->
          <div id="savings-goals">
            <div class="section-title">Savings Goals</div>
            <?php foreach (['vacation','car','general_savings','emergency_fund'] as $cat): ?>
              <div class="form-group">
                <label>
                  <input
                    type="checkbox"
                    name="savings_goals[]"
                    value="<?= $cat ?>"
                    id="cb-<?= $cat ?>"
                    onchange="toggleSavingsInputs('<?= $cat ?>')"
                  >
                  <?= ucwords(str_replace('_',' ',$cat)) ?>
                </label>

                <div id="inputs-<?= $cat ?>" class="savings_inputs">
                  <label for="amt-<?= $cat ?>">Target amount</label>
                  <input
                    type="number"
                    id="amt-<?= $cat ?>"
                    name="savings_amounts[<?= $cat ?>]"
                    placeholder="0.00"
                    min="0"
                    step="0.01"
                  />
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Preferred Budget -->
          <span class="section-title">Budget Preference</span>
          <div class="radio-group">
            <label><input type="radio" name="budget" value="Fixed" id="budget-fixed"> Fixed Budget</label>
            <label><input type="radio" name="budget" value="Flexible" id="budget-flexible"> Flexible Budget</label>
          </div>

          <!-- Budget Categories -->
          <span class="section-title">Expense Categories</span>
            <label>
              <div class='checkbox-inputbox'>
              <input type="checkbox" name="expenses[]" value="Food"> Food & Dining<input type='number' id='food'
               name='food' value='<?php htmlspecialchars($_POST['food'] ?? 0);?>'/></label>
            <label><input type="checkbox" name="expenses[]" value="Rent"> Rent<input type='number' id='rent'
             name='rent' value='<?php htmlspecialchars($_POST['rent'] ?? 0);?>'/></label>
            <label><input type="checkbox" name="expenses[]" value="Medical"> Medical<input type='number' id='medical' 
            name='medical' value='<?php htmlspecialchars($_POST['medical'] ?? 0);?>'/></label>
            <label><input type="checkbox" name="expenses[]" value="Transportation"> Transportation<input type='number' id='transport' 
            name='transport' value='<?php htmlspecialchars($_POST['transport'] ?? 0);?>'/></label>
            <label><input type="checkbox" name="expenses[]" value="Utilities"> Utilities<input type='number' id='util' 
            name='util' value='<?php htmlspecialchars($_POST['util'] ?? 0);?>'/></label>
            <label><input type="checkbox" name="expenses[]" value="Shopping"> Shopping<input type='number' id='shopping' 
            name='shopping' value='<?php htmlspecialchars($_POST['shopping'] ?? 0);?>'/></label>
            <label><input type="checkbox" name="expenses[]" value="Entertainment"> Entertainment<input type='number' id='entertain' 
            name='entertain' value='<?php htmlspecialchars($_POST['entertain'] ?? 0);?>'/></label>
          </div>
          <div style="display: flex; justify-content: center; gap: 20px; margin-top: 40px;">
            <input type="submit" name="cancel" value="Cancel" class="register-button">
            <input type="submit" name="finish" value="Complete Setup" class="register-button">
          </div>
        </form>
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

  <script src="bubbleChat.js"></script>
  <script>
  // On load, hide all the wrappers
  document.querySelectorAll('.savings_inputs, .expense-inputs')
          .forEach(div => div.style.display = 'none');

  function toggleSavingsInputs(key) {
    const wrapper = document.getElementById(`inputs-${key}`);
    const checked = document.getElementById(`cb-${key}`).checked;
    wrapper.style.display = checked ? 'block' : 'none';
    if (!checked && wrapper.querySelector('input')) {
      wrapper.querySelector('input').value = '';
    } else if (checked && wrapper.querySelector('input')) {
      wrapper.querySelector('input').focus();
    }
  }

  function toggleExpenseInputs(key) {
    const wrapper = document.getElementById(`inputs-${key}`);
    const checked = document.getElementById(`cb-${key}`).checked;
    wrapper.style.display = checked ? 'block' : 'none';
    if (!checked && wrapper.querySelector('input')) {
      wrapper.querySelector('input').value = '';
    } else if (checked && wrapper.querySelector('input')) {
      wrapper.querySelector('input').focus();
    }
  }
</script>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.checkbox-inputbox input[type=checkbox]').forEach(cb => {
      cb.addEventListener('change', e => {
        // derive the key from the checkbox id: "cb-food" → "food"
        const key = cb.id.replace(/^cb-/, '');
        const wrapper = document.getElementById(`inputs-${key}`);
        if (!wrapper) return;
        wrapper.style.display = cb.checked ? 'block' : 'none';
        if (cb.checked) wrapper.querySelector('input')?.focus();
      });
    });
  });
</script>
</body>
</html>
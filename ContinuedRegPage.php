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
          <input type="submit" name="cancel" value="Cancel" class="register-button" onclick="window.location.href='LoginPage.php';">
          <input type="submit" name="finish" value="Complete Setup" class="register-button">
        </div>
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

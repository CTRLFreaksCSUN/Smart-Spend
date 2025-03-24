<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
    <meta name="author" content="CTRL_Freaks">
     <meta name="description" content="Proof of concept for Smart Spend register.">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="refresh" content="60">
    <title>Smart Spend - Setup</title>
    <link rel="stylesheet" href="ContinuedRegStyle.css"> 
</head>
<body>
  <div class="container">

    <header>
      <h1> 
        <img src="images/SmartSpendLogo.png" 
        alt="Smart Spend" style="width:90px; height:80px;"> Smart Spend</h1>
    </header>

    <main>
      <div class="register">
        <h2>Getting Started</h2>
        <p>Fill in the details below to personalize your experience.</p>

        <!-- Monthly Income -->
        <div class="custom-input-block">
            <span class="section-title">Monthly Income:</span>
            <input type="number" id="income" name="income" placeholder="Enter monthly income" required class="custom-input">
        </div>
        <br>

        <!-- Savings Goals -->
        <span class="section-title">Savings Goals:</span>
        <div class="checkbox-group" id="default-savings-goals">
            <label><input type="checkbox" name="savings_goals[]" value="Vacation"> Vacation</label>
            <label><input type="checkbox" name="savings_goals[]" value="Car"> Car</label>
            <label><input type="checkbox" name="savings_goals[]" value="Savings"> Savings</label>
        </div>

        <div id="custom-savings-goals" class="custom-input-wrapper">
            <input type="text" name="savings_goals[]" class="custom-input" placeholder="Enter custom goal">
        </div>

        <button type="button" class="rounded-button" onclick="addSavingsGoal()">+ Add Custom Goal</button>
        <br><br>

        <!-- Preferred Budget -->
        <span class="section-title">Preferred Budget:</span><br>
        <input type="radio" name="budget" value="Fixed" id="budget-fixed">
        <label for="budget-fixed">Fixed</label>

        <input type="radio" name="budget" value="Flexible" id="budget-flexible">
        <label for="budget-flexible">Flexible</label>
        <br><br>

        <!-- Expense Categories -->
        <span class="section-title">Expense Categories:</span><br>
        <input type="checkbox" name="expenses[]" value="Food"> Food
        <input type="checkbox" name="expenses[]" value="Rent"> Rent
        <input type="checkbox" name="expenses[]" value="Shopping"> Shopping
        <br><br>

        <!-- Custom Categories -->
        <label>Other Categories:</label>
        <div id="custom-expenses" class="custom-input-wrapper">
            <input type="text" name="expenses[]" class="custom-input" placeholder="Enter custom category">
        </div>
        <button type="button" class="rounded-button" onclick="addExpenseField()">+ Add More</button>

        <!-- Buttons -->
        <br><br>
        <input type="submit" name="cancel" value="Cancel" class="register-button" onclick="window.location.href='LoginPage.php';">
        <input type="submit" name="finish" value="Finish" class="register-button">
      </div>
    </main>

    <footer>
      &copy; CTRL_Freaks - 2025
    </footer>
  </div>

  <script>
    function addExpenseField() {
        const container = document.getElementById("custom-expenses");
        const input = document.createElement("input");
        input.type = "text";
        input.name = "expenses[]";
        input.placeholder = "Enter custom category";
        input.className = "custom-input";
        container.appendChild(document.createElement("br"));
        container.appendChild(input);
    }
  </script>

<script>
    function addSavingsGoal() {
        const container = document.getElementById("custom-savings-goals");
        const input = document.createElement("input");
        input.type = "text";
        input.name = "savings_goals[]";
        input.placeholder = "Enter custom goal";
        input.className = "custom-input";
        container.appendChild(input);
    }
</script>

</body>
</html>

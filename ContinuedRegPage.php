<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Spend - Setup</title>
    <link rel="stylesheet" href="Registerstyle.css"> <!-- Use the same CSS as registerPage -->
</head>
<body>
    <header>
        <h1> 
            <img src="images/SmartSpendLogo.png" alt="Smart Spend" style="width:90px; height:80px;">
            Smart Spend
        </h1>
    </header>
    <br>

    <div class="register">
        <br>
        <h2>Getting Started</h2>
        <p>Fill in the details below to personalize your experience.</p>

            <!-- Monthly Income -->
            <label for="income">Monthly Income:</label>
            <input type="number" id="income" name="income" min="0" required>
            <br>
            <br>
           
            <!-- Savings Goals -->
            <label>Savings Goals:</label><br>
            <div class="checkbox-group">
                <label><input type="checkbox" name="savings_goals[]" value="Vacation"> Vacation</label>
                <label><input type="checkbox" name="savings_goals[]" value="Car"> Car</label>
                <label><input type="checkbox" name="savings_goals[]" value="Savings"> Savings</label>
            </div>
            <br>
            <br>

            <!-- Preferred Budget -->
            <label>Preferred Budget:</label><br>
            <input type="checkbox" name="budget[]" value="Fixed" > Fixed
            <input type="checkbox" name="budget[]" value="Flexible" > Flexible
            <br>
            <br>
            <!-- Expense Categories -->
            <label>Select Expense Categories:</label><br>
            <input type="checkbox" name="expenses[]" value="Food" > Food
            <input type="checkbox" name="expenses[]" value="Rent" > Rent
            <input type="checkbox" name="expenses[]" value="Shopping" > Shopping
            <input type="checkbox" name="expenses[]" value="Other"> Other
            <br>

            <!-- Buttons -->
            <br>
            <br>
            <input type="submit" name="cancel" value="Cancel" class="register-button" onclick="window.location.href='LoginPage.php';">
            <input type="submit" name="finish" value="Finish" class="register-button">
        </form>
    </div>

    <footer>
        &copy; CTRL_Freaks - 2025
    </footer>

</body>
</html>

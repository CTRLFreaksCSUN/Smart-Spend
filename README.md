# Smart Spend
Smart Spend is an AI intelligent expense tracking website that will use AI to help users manage their finances. 
Users will be able track daily spending, set budgets, and analyze financial trends. Smart Spend will be able to 
provide automated insights, predictive analytics, and personalized recommendations to help users with their financial needs. 

## Purpose
Those that will use Smart Spend will reduce financial stress with AI insights, enhance financial literacy, and help 
users achieve financial goals with smart budgeting.

## Required files


# Setup


# User Manual
1. Open the Smart Spend website on your browser.
2. If you do not have an account, click Sign Up and input your information in the following fields. Once completed, click Create Account. You will be sent a verification email with a verification code needed to complete registration.
3. If you have already created an account, simply input your username and password in the fields and then click Sign In. Afterward, you will be redirected to the Dashboard.

## Dashboard
From the Dashboard, users can enter their expenses and view visual representations of their spending habits. This includes graphs and spending analysis that show how much the user has spent in comparison to their total budget.

### How to Enter an Expense:
1. Navigate to the **Dashboard**.
2. Click the **"Add Expense"** button.
3. Fill in the required details: amount, category, date, and description.
4. Click **Submit** to save the expense.

## Upload
The upload tab allows users to upload receipts or other documents related to their expenses for easier tracking. This allows users to input their expenses without having to manually input the amount they spent each time.

### How to Upload a Document:
1. To upload a document, click **Upload** to be brought to the upload tab.
2. Either drag a document into the box labeled "Drag & drop receipt or click to browse" or click that same box to open your file explorer. Then find the document on your computer that you wish to upload.
3. Once a document is uploaded, click **Upload**.

## Documents
From the Documents tab, users can see all the documents that they've uploaded.

### How to View Uploaded Documents:
1. Click on the **Documents** tab.
2. Browse through the list or use the search/filter bar to find specific files.
3. Click on a document to view its details and the associated expense entry.

## Dataset

# Testcases 
## Registration test cases
1. Password: rEt6$ => Expected Result: Invalid
2. Password: re8ch4the$tar$ => Expected Result: Invalid
3. Password: @succE$$55 => Expected Result: Valid
4. First/Middle/Last name: 99904 => Expected Result: Invalid

# Spend Trend test cases
1.
![Screenshot (408)](https://github.com/user-attachments/assets/206af79e-3b9f-4f67-8b89-0d45d6c1fc7f)

2. 
![Screenshot (410)](https://github.com/user-attachments/assets/96263597-4009-4388-8408-0a9b5be8eb95)


## Unit Tests
1. To get started with running the unit test cases, first install the Composer package manager.

https://getcomposer.org/download/

Install by clicking Composer-Setup.exe

2. After installing, download the "unit_tests" folder from this repository.
3. Create a new folder and move unit_tests folder into it.
4. Install "composer.lock" and "composer.json" from this repository and place them in the same directory as the unit_tests folder.
5. In the command line terminal, navigate to that same directory and run the command "composer install" to install all dependencies.
6. After that, enter "./vendor/bin/phpunit unit_tests/test_file_name" to run test cases (replace test_file_name with actual test file name).

# Authors
Jessica Babos

Andrew Hua

Kenneth Riles

Zachary Mclaughlin

Shadi Zgheib

# External Requirements:

 ## Presentation
 Slides Link - https://docs.google.com/presentation/d/1LdT1w3d4i-lalkJ0UfNcnPZukd9PQlQBmQofYlxoK_M/edit?usp=drive_link
 Video Link - 

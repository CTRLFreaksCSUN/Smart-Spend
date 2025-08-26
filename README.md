# Smart Spend
Smart Spend is an AI intelligent expense tracking website that will use AI to help users manage their finances. 
Users will be able track daily spending, set budgets, and analyze financial trends. Smart Spend will be able to 
provide automated insights, predictive analytics, and personalized recommendations to help users with their financial needs. 

## Purpose
Those that will use Smart Spend will reduce financial stress with AI insights, enhance financial literacy, and help 
users achieve financial goals with smart budgeting.

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

# Screenshots
<img width="909" height="436" alt="Screenshot (448)" src="https://github.com/user-attachments/assets/4be53c93-8b29-4d03-a6ed-a71c11eb463f" />

<img width="888" height="442" alt="Screenshot (449)" src="https://github.com/user-attachments/assets/7a062a06-0e6f-4599-8245-343a82715f98" />

<img width="942" height="459" alt="Screenshot (450)" src="https://github.com/user-attachments/assets/21947c5c-e121-4a9a-9812-e3cc050a7ad1" />

<img width="962" height="460" alt="Screenshot (451)" src="https://github.com/user-attachments/assets/4310e710-2cac-42b9-b01c-12a515cc9615" />

<img width="955" height="466" alt="Screenshot (452)" src="https://github.com/user-attachments/assets/e60b55f6-aa74-46fb-86c5-66b2ac5bf6bd" />

<img width="977" height="479" alt="Screenshot (453)" src="https://github.com/user-attachments/assets/edbe83cf-80a6-41d6-b3a9-4c5edfa65791" />

<img width="930" height="455" alt="Screenshot (454)" src="https://github.com/user-attachments/assets/cd2c8a3f-fdfb-415e-9ab5-4d6549979e46" />

<img width="500" height="450" alt="Screenshot (455)" src="https://github.com/user-attachments/assets/bcdabc53-cb59-401a-99b1-712098d778b3" />


# Testcases 

### Registration test cases
1. Enter Password: rEt6$ => Expected Result: Invalid
2. Enter Password: re8ch4the$tar$ => Expected Result: Invalid
3. Enter Password: @succE$$55 => Expected Result: Valid
4. Enter First/Middle/Last name: 99904 => Expected Result: Invalid

### Login test cases
1. After creating new account, attempt to login will fail if user is not verified.
2. User clicks on sign in button while unverified => Expected Result: Verification email is resent.

### Dashboard test cases
1. After adding financial information from registration, the dashboard will be repopulated with data represented in the charts/diagrams.
2. The charts will be updated after starting budget and expense analysis.
3. When user clicks sign out, their session is successfully terminated.

### Spend trend test cases
1. Inputting comma, separated expenses will be successfully fed into analysis.
![Screenshot (408)](https://github.com/user-attachments/assets/206af79e-3b9f-4f67-8b89-0d45d6c1fc7f)

2. Inputting numbers with random decimal places is accepted.
![Screenshot (410)](https://github.com/user-attachments/assets/96263597-4009-4388-8408-0a9b5be8eb95)

3. Inputting negative numbers will be accepted.
![Screenshot (411)](https://github.com/user-attachments/assets/76a017cd-439d-4067-a705-21420bf64ba1)

### History test cases
1. History is repopulated after spend trend analysis is completed.
2. History is saved next time the user logs in.
3. Applying time filters shows the history within the selected time frame.
4. Applying expense type filters shows only the history of that category.
5. Clearing history removes all history permanently.

### Profile test cases
1. User's monthly income, savings goals and savings will be displayed on profile and saved the next time they login.
2. When the user makes a deposit, the user's savings will be updated.
3. When the user changes their password, they are required to enter their old password first.
4. Attempting to change the password to an old password will fail.

### Upload documents test cases
1. User receives results from uploading images and pdfs.
2. Documents tab is repopulated with files that the user uploads.
3. When user removes document, it is permanently deleted.

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

# Presentation and Demo Video
- Slides Link - https://docs.google.com/presentation/d/1LdT1w3d4i-lalkJ0UfNcnPZukd9PQlQBmQofYlxoK_M/edit?usp=drive_link
- Video Link - https://drive.google.com/file/d/1HaIrtAvYVxvvvtrQVWKfIe1CwBGTXzsF/view?usp=sharing

<?php

//composer require orhanerday/open-ai       -- to install it 

// To Disable SSL verification
// 1. Navigate to the file: C:\xampp\htdocs\Smart-Spend-main\vendor\orhanerday\open-ai\src\OpenAi.php
// 2. Find the function sendRequest() near line 967 where the cURL request is configured.
// 3. Find $curl = curl_init(); (most likely will be on line 1002 on your end)
// 4. Immediately after curl_init(); copy and paste this below 
// curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
// curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);




ini_set('display_errors', 1);
error_reporting(E_ALL);
require __DIR__ . '/vendor/autoload.php';

// Load environment variables from .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use Orhanerday\OpenAi\OpenAi;

$open_ai_key = $_ENV['OPENAI_API_KEY'] ?? null;
if (!$open_ai_key) {
    die("OpenAI API key not found. Please set OPENAI_API_KEY in .env.");
}

// Create an instance of the OpenAi client
$open_ai = new OpenAi($open_ai_key);

// Variable to hold the AI response
$aiResponse = "";

// Process the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userMessage = trim($_POST['userMessage'] ?? '');
    
    if (!empty($userMessage)) {
        // Make a request to ChatGPT
        $chat = $open_ai->chat([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role'    => 'system',
                    'content' => 'You are a helpful financial assistant. Provide concise and clear advice.'
                ],
                [
                    'role'    => 'user',
                    'content' => $userMessage
                ]
            ],
        ]);
        
        // Log the raw response for debugging
        error_log("ChatGPT raw response: " . $chat);
        
        // Convert JSON response to array
        $responseArr = json_decode($chat, true);
        
        if (isset($responseArr['error'])) {
            $aiResponse = "API Error: " . $responseArr['error']['message'];
        } elseif (isset($responseArr['choices'][0]['message']['content'])) {
            $aiResponse = $responseArr['choices'][0]['message']['content'];
        } else {
            $aiResponse = "No response from ChatGPT. Check logs or API usage.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Spend - Financial AI Assistant</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4cc9f0;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #4bb543;
            --error-color: #ff3333;
            --border-radius: 12px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: var(--dark-color);
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px 0;
        }
        
        header h1 {
            color: var(--primary-color);
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        header p.subtitle {
            color: var(--secondary-color);
            font-size: 1.1rem;
        }
        
        .chat-container {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .chat-header {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 15px 20px;
            font-size: 1.2rem;
            font-weight: 500;
        }
        
        .chat-body {
            padding: 20px;
            min-height: 300px;
            max-height: 500px;
            overflow-y: auto;
        }
        
        .message {
            margin-bottom: 15px;
            display: flex;
        }
        
        .user-message {
            justify-content: flex-end;
        }
        
        .ai-message {
            justify-content: flex-start;
        }
        
        .message-content {
            max-width: 80%;
            padding: 12px 16px;
            border-radius: var(--border-radius);
            position: relative;
            line-height: 1.5;
        }
        
        .user-message .message-content {
            background-color: var(--primary-color);
            color: white;
            border-bottom-right-radius: 5px;
        }
        
        .ai-message .message-content {
            background-color: var(--light-color);
            border: 1px solid #e9ecef;
            border-bottom-left-radius: 5px;
        }
        
        .chat-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
            padding: 20px;
            background-color: #f8f9fa;
            border-top: 1px solid #e9ecef;
        }
        
        .chat-input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e9ecef;
            border-radius: var(--border-radius);
            font-family: inherit;
            font-size: 1rem;
            resize: none;
            transition: var(--transition);
        }
        
        .chat-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }
        
        .submit-btn {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            align-self: flex-end;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .submit-btn:active {
            transform: translateY(0);
        }
        
        footer {
            text-align: center;
            margin-top: 40px;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .typing-indicator {
            display: none;
            padding: 10px;
            color: #6c757d;
            font-style: italic;
        }
        
        @media (max-width: 768px) {
            header h1 {
                font-size: 2rem;
            }
            
            .message-content {
                max-width: 90%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Smart Spend AI</h1>
            <p class="subtitle">Your personal financial assistant</p>
        </header>
        
        <div class="chat-container">
            <div class="chat-header">
                Financial Advice Chat
            </div>
            
            <div class="chat-body" id="chatBody">
                <?php if (!empty($aiResponse)): ?>
                    <div class="message user-message">
                        <div class="message-content">
                            <?php echo htmlspecialchars($_POST['userMessage'] ?? ''); ?>
                        </div>
                    </div>
                    
                    <div class="message ai-message">
                        <div class="message-content">
                            <?php echo htmlspecialchars($aiResponse); ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="welcome-message" style="text-align: center; padding: 20px; color: #6c757d;">
                        <p>Hello! I'm your Smart Spend AI assistant. Ask me anything about budgeting, saving, or personal finance.</p>
                        <p>Try questions like:<br>
                        "How can I save more money?"<br>
                        "What's the 50/30/20 budget rule?"<br>
                        "Tips for reducing monthly expenses?"</p>
                    </div>
                <?php endif; ?>
                
                <div class="typing-indicator" id="typingIndicator">
                    Smart Spend is typing...
                </div>
            </div>
            
            <form method="POST" action="chatbox.php" class="chat-form" id="chatForm">
                <textarea name="userMessage" class="chat-input" rows="3" placeholder="Type your financial question here..." required></textarea>
                <button type="submit" class="submit-btn">Send Message</button>
            </form>
        </div>
        
        <footer>
            <p>&copy; <?php echo date('Y'); ?> Smart Spend AI. All rights reserved.</p>
        </footer>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chatBody = document.getElementById('chatBody');
            chatBody.scrollTop = chatBody.scrollHeight;
            
            const chatForm = document.getElementById('chatForm');
            const typingIndicator = document.getElementById('typingIndicator');
            
            chatForm.addEventListener('submit', function() {
                typingIndicator.style.display = 'block';
                chatBody.scrollTop = chatBody.scrollHeight;
            });
        });
    </script>
</body>
</html>
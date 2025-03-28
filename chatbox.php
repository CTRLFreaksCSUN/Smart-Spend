<?php
//composer require orhanerday/open-ai       -- to install it 

// To Disable SSL verification
// 1. Navigate to the file: C:\xampp\htdocs\Smart-Spend-main\vendor\orhanerday\open-ai\src\OpenAi.php
// 2. Find the function sendRequest() near line 967 where the cURL request is configured.
// 3. Find $curl = curl_init(); (most likely will be on line 1002 on your end)
// 4. Immediately after curl_init(); copy and paste this below 
// curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
// curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use Orhanerday\OpenAi\OpenAi;

$open_ai_key = $_ENV['OPENAI_API_KEY'] ?? die("OpenAI API key not configured in .env");
$open_ai = new OpenAi($open_ai_key);

$aiResponse = "";
$debugInfo = "";

/**
 * Validates image file and returns MIME type
 */
function validateImageFile($filePath) {
    if (!file_exists($filePath)) {
        throw new Exception("File doesn't exist or cannot be accessed");
    }

    // Method 1: Try finfo first (most reliable)
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $filePath);
        finfo_close($finfo);
        
        if (in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
            return $mime;
        }
    }

    // Method 2: Try getimagesize
    $imageInfo = @getimagesize($filePath);
    if ($imageInfo !== false) {
        $mime = $imageInfo['mime'] ?? '';
        if (in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
            return $mime;
        }
    }

    // Method 3: Check file signature (magic numbers)
    $fileHandle = fopen($filePath, 'rb');
    if ($fileHandle) {
        $firstBytes = fread($fileHandle, 8);
        fclose($fileHandle);
        
        if (strpos($firstBytes, "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A") === 0) return 'image/png';
        if (strpos($firstBytes, "\xFF\xD8\xFF") === 0) return 'image/jpeg';
        if (strpos($firstBytes, "GIF") === 0) return 'image/gif';
        if (strpos($firstBytes, "RIFF") === 0) return 'image/webp';
    }

    throw new Exception("Unsupported file type. Please upload a valid JPEG, PNG, GIF, or WebP image.");
}

/**
 * Prepares image data for API upload
 */
function prepareImageData($filePath) {
    $mime = validateImageFile($filePath);
    $imageData = base64_encode(file_get_contents($filePath));
    return [
        'mime' => $mime,
        'data' => $imageData
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Handle receipt upload
        if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
            $tempPath = $_FILES['receipt']['tmp_name'];
            $imageInfo = prepareImageData($tempPath);
            
            // Call OpenAI Vision API with GPT-4o
            $response = $open_ai->chat([
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => "Extract merchant, total, date, and items from this receipt. Format as markdown table with columns: Item | Price | Category. Include currency symbols."],
                            ['type' => 'image_url', 'image_url' => [
                                "url" => "data:{$imageInfo['mime']};base64,{$imageInfo['data']}"
                            ]]
                        ]
                    ]
                ],
                'max_tokens' => 1000
            ]);
            
            $result = json_decode($response, true);
            $aiResponse = $result['choices'][0]['message']['content'] ?? "⚠️ Failed to analyze receipt";
            
        } 
        // Handle text message
        elseif (!empty($_POST['userMessage'])) {
            $response = $open_ai->chat([
                'model' => 'gpt-4o',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a helpful financial assistant.'],
                    ['role' => 'user', 'content' => $_POST['userMessage']]
                ]
            ]);
            $aiResponse = json_decode($response)->choices[0]->message->content;
        }
        
    } catch (Exception $e) {
        $aiResponse = "⚠️ Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Spend - AI Receipt Scanner</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --accent: #4cc9f0;
            --light: #f8f9fa;
            --dark: #212529;
            --error: #ff3333;
            --radius: 12px;
            --shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f7fa;
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .chat-container {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        .chat-header {
            background: linear-gradient(to right, var(--primary), var(--secondary));
            color: white;
            padding: 15px 20px;
            font-size: 1.2rem;
        }
        .chat-body {
            padding: 20px;
            max-height: 500px;
            overflow-y: auto;
        }
        .message {
            margin-bottom: 15px;
        }
        .user-message { text-align: right; }
        .ai-message { text-align: left; }
        .message-content {
            display: inline-block;
            padding: 12px 16px;
            border-radius: var(--radius);
            max-width: 80%;
        }
        .user-message .message-content {
            background: var(--primary);
            color: white;
        }
        .ai-message .message-content {
            background: var(--light);
            border: 1px solid #eee;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin: 10px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .upload-area {
            border: 2px dashed #ccc;
            border-radius: var(--radius);
            padding: 20px;
            text-align: center;
            margin: 15px 0;
            cursor: pointer;
            transition: all 0.3s;
        }
        .upload-area:hover {
            border-color: var(--primary);
            background: rgba(67, 97, 238, 0.05);
        }
        #file-input {
            display: none;
        }
        #preview-container {
            margin-top: 15px;
        }
        #preview-image {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            display: none;
        }
        .chat-form {
            padding: 20px;
            background: #f8f9fa;
            border-top: 1px solid #eee;
        }
        .submit-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: var(--radius);
            cursor: pointer;
            float: right;
            transition: all 0.3s;
        }
        .submit-btn:hover {
            background: var(--secondary);
            transform: translateY(-2px);
        }
        .error-message {
            color: var(--error);
            background: rgba(255, 51, 51, 0.1);
            padding: 10px;
            border-radius: var(--radius);
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="chat-container">
            <div class="chat-header">
                <i class="fas fa-receipt"></i> Smart Spend Financial Advisor
            </div>

            <div class="chat-body" id="chatBody">
                <?php if (!empty($aiResponse)): ?>
                    <?php if (isset($_FILES['receipt'])): ?>
                        <div class="message user-message">
                            <div class="message-content">
                                <i class="fas fa-receipt"></i> Uploaded Receipt
                                <?php if (isset($_FILES['receipt']['name'])): ?>
                                    <div style="font-size: 0.8em; margin-top: 5px;">
                                        <?= htmlspecialchars($_FILES['receipt']['name']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="message user-message">
                            <div class="message-content">
                                <?= htmlspecialchars($_POST['userMessage'] ?? '') ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="message ai-message">
                        <div class="message-content">
                            <?= nl2br(htmlspecialchars($aiResponse)) ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px 20px; color: #666;">
                        <i class="fas fa-receipt" style="font-size: 3rem; color: var(--primary);"></i>
                        <h3>Upload a Receipt</h3>
                        <div style="margin-top: 30px; font-family: 'Poppins', sans-serif; line-height: 1.4;">
        <p style="font-weight: bold; font-size: 1.1rem; margin-bottom: 8px; color: var(--dark);">
            Hello! I'm your Smart Spend AI assistant. Ask me anything about budgeting, saving, or personal finance.
        </p>
        <p style="font-weight: 500; margin: 5px 0; color: var(--primary);">Try something like this:</p>
        <p style="margin: 5px 0; color: var(--dark);">"How can I save more money?"</p>
        <p style="margin: 5px 0; color: var(--dark);">"Help me with my taxes"</p>
    </div>
                           
                        </p>
                    </div>
                <?php endif; ?>
            </div>

            <form method="POST" action="" class="chat-form" enctype="multipart/form-data" id="chatForm">
                <div class="upload-area" id="uploadArea">
                    <i class="fas fa-cloud-upload-alt" style="font-size: 2rem;"></i>
                    <p>Drag & drop receipt or click to browse</p>
                    <p style="font-size: 0.8em; color: #666;">(Supports JPEG, PNG, GIF, WebP up to 20MB)</p>
                    <input type="file" id="file-input" name="receipt" accept="image/jpeg,image/png,image/gif,image/webp">
                    <div id="preview-container">
                        <img id="preview-image" alt="Receipt preview">
                    </div>
                </div>

                <textarea name="userMessage" placeholder="Or ask a financial question..." style="width: 100%; padding: 12px; border-radius: var(--radius); border: 1px solid #ddd; margin-top: 10px; min-height: 80px;"></textarea>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-paper-plane"></i> Analyze
                </button>
                <div style="clear: both;"></div>
            </form>
        </div>
    </div>

    <script>
        // File upload handling
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('file-input');
        const previewImage = document.getElementById('preview-image');

        uploadArea.addEventListener('click', () => fileInput.click());
        
        fileInput.addEventListener('change', function(e) {
            if (e.target.files.length) {
                const file = e.target.files[0];
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        previewImage.src = event.target.result;
                        previewImage.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                }
            }
        });

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            uploadArea.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, unhighlight, false);
        });

        function highlight() {
            uploadArea.style.borderColor = 'var(--primary)';
            uploadArea.style.backgroundColor = 'rgba(67, 97, 238, 0.1)';
        }

        function unhighlight() {
            uploadArea.style.borderColor = '#ccc';
            uploadArea.style.backgroundColor = 'transparent';
        }

        uploadArea.addEventListener('drop', function(e) {
            const files = e.dataTransfer.files;
            if (files.length) {
                fileInput.files = files;
                fileInput.dispatchEvent(new Event('change'));
            }
        });
    </script>
</body>
</html>

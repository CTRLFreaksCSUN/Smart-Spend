<?php
// Handle file upload logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['documents'])) {
    $uploadDir = 'uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'application/pdf' => 'pdf',
        'text/plain' => 'txt',
        'text/csv' => 'csv'
    ];
    
    $uploadedFiles = [];
    foreach ($_FILES['documents']['tmp_name'] as $key => $tmpName) {
        // Verify file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tmpName);
        $fileExt = strtolower(pathinfo($_FILES['documents']['name'][$key], PATHINFO_EXTENSION));
        
        if (!in_array($mime, array_keys($allowedTypes)) || $allowedTypes[$mime] !== $fileExt) {
            continue; // Skip invalid files
        }
        
        $fileName = basename($_FILES['documents']['name'][$key]);
        $targetPath = $uploadDir . uniqid() . '_' . $fileName;
        
        if (move_uploaded_file($tmpName, $targetPath)) {
            $uploadedFiles[] = $fileName;
        }
    }
    
    if (!empty($uploadedFiles)) {
        $uploadMessage = "Successfully uploaded: " . implode(", ", $uploadedFiles);
        $messageClass = "success";
    } else {
        $uploadMessage = "No valid files were uploaded.";
        $messageClass = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Spend - Upload Documents</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="uploadDocsStyle.css">
    <link rel="stylesheet" href="bubbleChatStyle.css">
</head>
<body>
    <div class="upload-container">
        <header class="upload-header">
            <div class="logo-title">
                <img src="images/SmartSpendLogo.png" alt="Smart Spend Logo" class="logo">
                <h1>Smart Spend</h1>
            </div>
            <nav>
                <a href="DashboardPage.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="#"><i class="fas fa-file-alt"></i> Documents</a>
                <a href="#" class="active"><i class="fas fa-cloud-upload-alt"></i> Upload</a>
            </nav>
            <div class="profile-icon">
                <img src="images/ProfilePic.png" alt="User" class="avatar">
            </div>
        </header>

        <main class="upload-main">
            <div class="upload-card animate__animated animate__fadeIn">
                <div class="upload-header">
                    <h2><i class="fas fa-cloud-upload-alt"></i> Upload Financial Documents</h2>
                    <p>Upload receipts, invoices, or bank statements for analysis</p>
                </div>
                
                <form action="uploadDocs.php" method="POST" enctype="multipart/form-data" class="upload-form">
                    <div class="file-filter">
                        <span>Filter by:</span>
                        <button type="button" class="filter-btn active" data-filter="all">All</button>
                        <button type="button" class="filter-btn" data-filter="image">Images</button>
                        <button type="button" class="filter-btn" data-filter="pdf">PDFs</button>
                    </div>
                    
                    <div class="file-upload-area" id="dropZone">
                        <i class="fas fa-file-upload"></i>
                        <p>Drag & drop files here or click to browse</p>
                        <input type="file" name="documents[]" id="fileInput" multiple 
                               accept=".pdf,.jpg,.jpeg,.png,.txt,.csv">
                        <label for="fileInput" class="browse-btn">Select Files</label>
                    </div>
                    
                    <div class="file-preview" id="filePreview">
                        <h3>Selected Files: <span id="fileCount">0</span></h3>
                        <ul id="fileList"></ul>
                    </div>
                    
                    <div class="bulk-actions">
                        <button type="button" class="bulk-btn" id="setAllInvoices">
                            <i class="fas fa-tag"></i> Mark All as Invoices
                        </button>
                        <button type="button" class="bulk-btn" id="setAllReceipts">
                            <i class="fas fa-receipt"></i> Mark All as Receipts
                        </button>
                    </div>
                    
                    <div class="upload-options">
                        <div class="form-group">
                            <label for="documentType"><i class="fas fa-tag"></i> Document Type:</label>
                            <select name="documentType" id="documentType">
                                <option value="receipt">Receipt</option>
                                <option value="invoice">Invoice</option>
                                <option value="statement">Bank Statement</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="date"><i class="fas fa-calendar-alt"></i> Date:</label>
                            <input type="date" name="date" id="date">
                        </div>
                    </div>
                    
                    
                    
                    <div class="form-group version-control">
                        <label>
                            <input type="checkbox" name="versionControl" checked>
                            Enable Version Control
                        </label>
                        <small>Keep previous versions of updated documents</small>
                    </div>
                    
                    <button type="submit" class="upload-btn">
                        <i class="fas fa-upload"></i> Upload Documents
                    </button>
                    
                    <?php if (isset($uploadMessage)): ?>
                        <div class="upload-message <?php echo $messageClass; ?>">
                            <?php echo $uploadMessage; ?>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </main>
    </div>

    <!-- Floating Chat Bubble -->
    <div class="chat-bubble-container" id="chatContainer">
        <div class="chat-bubble-button" id="chatBubble">?</div>
        <div class="chat-popup" id="chatPopup">
            <div class="chat-header">
                <span>Financial Assistant</span>
                <button id="closeChat">&times;</button>
            </div>
            <div class="chat-content">
                <iframe src="chatbox.php"></iframe>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.14.0/Sortable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@4/dist/tesseract.min.js"></script>
    <script src="bubbleChat.js"></script>
    
    <script>
        // DOM Elements
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const fileList = document.getElementById('fileList');
        const fileCount = document.getElementById('fileCount');
        const documentType = document.getElementById('documentType');
        const dateInput = document.getElementById('date');
        const uploadForm = document.querySelector('.upload-form');
        const filterButtons = document.querySelectorAll('.filter-btn');
        
        // Initialize SortableJS for file reordering
        new Sortable(fileList, {
            animation: 150,
            handle: '.file-preview-container',
            ghostClass: 'sortable-ghost'
        });
        
        // File upload handling
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight() {
            dropZone.classList.add('highlight');
            dropZone.querySelector('i').classList.add('animate__animated', 'animate__pulse');
        }
        
        function unhighlight() {
            dropZone.classList.remove('highlight');
            dropZone.querySelector('i').classList.remove('animate__animated', 'animate__pulse');
        }
        
        dropZone.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            handleFiles(files);
        }
        
        fileInput.addEventListener('change', function() {
            handleFiles(this.files);
        });
        
        function handleFiles(files) {
            const MAX_SIZE = 10 * 1024 * 1024; // 10MB
            const validFiles = Array.from(files).filter(file => file.size <= MAX_SIZE);
            
            if (validFiles.length !== files.length) {
                alert('Some files exceeded the 10MB limit and were removed');
            }
            
            fileInput.files = createFileList(validFiles);
            updateFileList();
            
            // Auto-detect document types and dates
            autoDetectInfo(validFiles);
        }
        
        function createFileList(files) {
            const dt = new DataTransfer();
            files.forEach(file => dt.items.add(file));
            return dt.files;
        }
        
        function autoDetectInfo(files) {
            files.forEach(file => {
                // Detect document type from filename
                if (file.name.match(/invoice/i)) {
                    documentType.value = 'invoice';
                } else if (file.name.match(/receipt/i)) {
                    documentType.value = 'receipt';
                } else if (file.name.match(/statement/i)) {
                    documentType.value = 'statement';
                }
                
                // Detect date from filename
                const dateMatch = file.name.match(/(\d{4}-\d{2}-\d{2})|(\d{2}\.\d{2}\.\d{4})/);
                if (dateMatch) {
                    dateInput.value = dateMatch[0].replace(/\./g, '-');
                }
            });
        }
        
        function updateFileList() {
            fileList.innerHTML = '';
            const files = fileInput.files;
            fileCount.textContent = files.length;
            
            if (files.length > 0) {
                Array.from(files).forEach((file, index) => {
                    const listItem = document.createElement('li');
                    const preview = file.type.startsWith('image/') ? 
                        `<img src="${URL.createObjectURL(file)}" class="file-thumbnail">` : 
                        `<div class="file-icon"><i class="fas fa-file"></i></div>`;
                    
                    listItem.innerHTML = `
                        <div class="file-preview-container">
                            ${preview}
                            <div class="file-info">
                                <span class="file-name">${file.name}</span>
                                <span class="file-size">(${(file.size / 1024 / 1024).toFixed(2)} MB)</span>
                                <button class="remove-file" data-index="${index}">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        ${file.type === 'application/pdf' ? `
                        <button class="ocr-preview-btn" data-file="${file.name}">
                            <i class="fas fa-eye"></i> Preview Text
                        </button>
                        <div class="ocr-preview"></div>
                        ` : ''}
                    `;
                    fileList.appendChild(listItem);
                });
                
                // Add event listeners for remove buttons
                document.querySelectorAll('.remove-file').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const index = this.getAttribute('data-index');
                        const files = Array.from(fileInput.files);
                        files.splice(index, 1);
                        fileInput.files = createFileList(files);
                        updateFileList();
                    });
                });
                
                // Add event listeners for OCR preview
                document.querySelectorAll('.ocr-preview-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const fileIndex = this.getAttribute('data-index');
                        const file = fileInput.files[fileIndex];
                        const previewDiv = this.nextElementSibling;
                        
                        previewDiv.innerHTML = '<div class="ocr-loading">Extracting text...</div>';
                        
                        Tesseract.recognize(
                            file,
                            'eng',
                            { logger: m => console.log(m) }
                        ).then(({ data: { text } }) => {
                            previewDiv.innerHTML = `
                                <div class="ocr-result">
                                    <h4>Extracted Text:</h4>
                                    <textarea readonly>${text}</textarea>
                                </div>
                            `;
                        });
                    });
                });
                
                document.getElementById('filePreview').style.display = 'block';
            } else {
                document.getElementById('filePreview').style.display = 'none';
            }
        }
        
        // Bulk actions
        document.getElementById('setAllInvoices').addEventListener('click', () => {
            documentType.value = 'invoice';
        });
        
        document.getElementById('setAllReceipts').addEventListener('click', () => {
            documentType.value = 'receipt';
        });
        
        // File filtering
        filterButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                filterButtons.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                const filter = this.getAttribute('data-filter');
                filterFiles(filter);
            });
        });
        
        function filterFiles(filter) {
            const items = fileList.querySelectorAll('li');
            items.forEach(item => {
                const fileType = item.querySelector('.file-name').textContent.split('.').pop();
                if (filter === 'all' || 
                    (filter === 'image' && ['jpg', 'jpeg', 'png'].includes(fileType)) || 
                    (filter === 'pdf' && fileType === 'pdf')) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        }
        
        // Form submission with progress
        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (fileInput.files.length === 0) {
                alert('Please select at least one file to upload');
                return;
            }
            
            if (fileInput.files.length > 10) {
                if (!confirm(`You're about to upload ${fileInput.files.length} files. Continue?`)) {
                    return;
                }
            }
            
            const progressBar = document.createElement('div');
            progressBar.className = 'upload-progress';
            progressBar.innerHTML = `
                <div class="progress-bar"></div>
                <span class="progress-text">0%</span>
            `;
            this.appendChild(progressBar);
            
            const formData = new FormData(this);
            const xhr = new XMLHttpRequest();
            
            xhr.upload.onprogress = function(e) {
                const percent = Math.round((e.loaded / e.total) * 100);
                progressBar.querySelector('.progress-bar').style.width = `${percent}%`;
                progressBar.querySelector('.progress-text').textContent = `${percent}%`;
            };
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    progressBar.innerHTML = `
                        <div class="upload-complete">
                            <i class="fas fa-check-circle"></i> Upload Complete!
                        </div>
                    `;
                    // Refresh file list
                    fileInput.value = '';
                    updateFileList();
                }
            };
            
            xhr.open('POST', 'uploadDocs.php', true);
            xhr.send(formData);
        });
    </script>
</body>
</html>
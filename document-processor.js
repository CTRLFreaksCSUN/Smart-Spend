/**
 * Document Processor
 * 
 * Uses Tesseract.js to extract text from receipt images and PDFs
 * and sends to server for business name and amount extraction
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Document processor initialized');
    
    // Verify Tesseract.js is available
    if (typeof Tesseract === 'undefined') {
        console.error('Tesseract.js not found. Please include the library.');
        return;
    }
    
    // Find the upload form
    const uploadForm = document.querySelector('.upload-form');
    if (!uploadForm) {
        console.error('Upload form not found');
        return;
    }
    
    // Store the original form submission handler
    uploadForm.addEventListener('submit', function(e) {
        e.preventDefault();
        console.log('Form submission intercepted');
        
        // Get selected files
        const fileInput = document.getElementById('fileInput');
        if (!fileInput || fileInput.files.length === 0) {
            alert('Please select at least one file to upload');
            return;
        }
        
        // Show loading indicator
        let loadingIndicator = document.querySelector('.loading-indicator');
        if (!loadingIndicator) {
            loadingIndicator = document.createElement('div');
            loadingIndicator.className = 'loading-indicator';
            loadingIndicator.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing files...';
            uploadForm.appendChild(loadingIndicator);
        }
        
        // Create FormData from the form
        const formData = new FormData(this);
        
        // Upload the files first
        fetch('update-document.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log('Upload response:', data);
            if (data.status === 'success' || data.files && data.files.length > 0) {
                loadingIndicator.innerHTML = '<i class="fas fa-check-circle"></i> Files uploaded. Processing receipts...';
                
                // Process each uploaded file to extract business name and amount
                processUploadedFiles(data.files || [], data.paths || [], loadingIndicator);
            } else {
                loadingIndicator.innerHTML = `<i class="fas fa-times-circle"></i> Error: ${data.message}`;
                setTimeout(() => {
                    loadingIndicator.remove();
                }, 3000);
            }
        })
        .catch(error => {
            console.error('Upload error:', error);
            loadingIndicator.innerHTML = `<i class="fas fa-times-circle"></i> Error: ${error.message}`;
            setTimeout(() => {
                loadingIndicator.remove();
            }, 3000);
        });
    });
    
    /**
     * Process multiple files
     */
    function processUploadedFiles(files, paths, loadingIndicator) {
        // Skip if no files
        if (!files || files.length === 0) {
            loadingIndicator.innerHTML = '<i class="fas fa-info-circle"></i> No files to process';
            setTimeout(() => {
                loadingIndicator.remove();
            }, 3000);
            return;
        }
        
        // Create results container
        const resultsContainer = document.createElement('div');
        resultsContainer.className = 'extraction-results';
        resultsContainer.innerHTML = '<h3>Extracted Receipt Data</h3>';
        
        // Process one file at a time
        let fileIndex = 0;
        
        function processNextFile() {
            if (fileIndex >= files.length) {
                // All files processed
                loadingIndicator.remove();
                return;
            }
            
            const fileName = files[fileIndex];
            const filePath = paths[fileIndex] || `uploads/${fileName}`; // Fallback if path not provided
            
            loadingIndicator.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Processing ${fileName} (${fileIndex + 1}/${files.length})...`;
            
            // Check file type
            const fileExt = fileName.split('.').pop().toLowerCase();
            
            // For images and PDFs, use OCR
            if (['jpg', 'jpeg', 'png', 'pdf'].includes(fileExt)) {
                // Get the full URL to the file
                const fileUrl = getAbsolutePath(filePath);
                console.log(`Processing file with OCR: ${fileName} (${fileUrl})`);
                
                Tesseract.recognize(
                    fileUrl,
                    'eng',
                    { 
                        logger: m => {
                            if (m.status === 'recognizing text') {
                                loadingIndicator.innerHTML = `
                                    <i class="fas fa-spinner fa-spin"></i> 
                                    OCR Processing ${fileName}: ${Math.round(m.progress * 100)}%
                                `;
                            }
                        }
                    }
                ).then(({ data: { text } }) => {
                    console.log(`OCR complete for ${fileName}. Text length: ${text.length}`);
                    
                    // Extract business name and amount
                    sendExtractRequest(fileName, filePath, text, resultsContainer);
                    
                    // Process next file
                    fileIndex++;
                    processNextFile();
                }).catch(error => {
                    console.error(`OCR error for ${fileName}:`, error);
                    
                    // For PDFs, try server-side extraction as fallback if OCR fails
                    if (fileExt === 'pdf') {
                        console.log(`Falling back to server-side extraction for PDF: ${fileName}`);
                        sendExtractRequest(fileName, filePath, "", resultsContainer);
                    } else {
                        addResultRow(fileName, 'Error', 'Error', error.message, resultsContainer);
                    }
                    
                    // Process next file
                    fileIndex++;
                    processNextFile();
                });
            } else {
                // For non-images, just extract directly
                console.log(`Processing non-image file: ${fileName}`);
                sendExtractRequest(fileName, filePath, "", resultsContainer);
                
                // Process next file
                fileIndex++;
                processNextFile();
            }
        }
        
        // Start processing files
        processNextFile();
        
        // Insert results container after the form
        uploadForm.insertAdjacentElement('afterend', resultsContainer);
    }
    
    /**
     * Send extraction request to server
     */
    function sendExtractRequest(fileName, filePath, text, resultsContainer) {
        // Get customer ID if available
        const customerId = getCustomerId();
        
        const formData = new FormData();
        formData.append('action', 'process_receipt');
        formData.append('file_path', filePath);
        formData.append('text', text);
        if (customerId) {
            formData.append('c_id', customerId);
        }
        
        fetch('receipt_extractor.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Server response status:', response.status);
            return response.text().then(text => {
                try {
                    console.log('Raw server response:', text);
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Error parsing JSON response:', e);
                    console.log('Raw response was:', text);
                    throw new Error('Invalid server response: ' + e.message);
                }
            });
        })
        .then(data => {
            console.log(`Extraction result for ${fileName}:`, data);
            
            if (data.status === 'success') {
                addResultRow(fileName, data.business, data.amount, 'Processed', resultsContainer);
            } else {
                addResultRow(fileName, data.business || 'Unknown', data.amount || 0, data.message || 'Error', resultsContainer);
            }
        })
        .catch(error => {
            console.error(`Extraction error for ${fileName}:`, error);
            addResultRow(fileName, 'Error', 'Error', error.message, resultsContainer);
        });
    }
    
    /**
     * Add a result row to the results container
     */
    function addResultRow(fileName, business, amount, status, container) {
        // Create table if it doesn't exist
        let table = container.querySelector('table');
        if (!table) {
            table = document.createElement('table');
            table.className = 'extraction-table';
            table.innerHTML = `
                <thead>
                    <tr>
                        <th>File</th>
                        <th>Business</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody></tbody>
            `;
            container.appendChild(table);
        }
        
        // Add row to table
        const tbody = table.querySelector('tbody');
        const row = document.createElement('tr');
        
        // Format amount if it's a number
        let formattedAmount = amount;
        if (typeof amount === 'number') {
            formattedAmount = '$' + amount.toFixed(2);
        }
        
        // Determine status class
        let statusClass = 'status-info';
        if (status === 'Processed') {
            statusClass = 'status-success';
        } else if (status.includes('Error')) {
            statusClass = 'status-error';
        }
        
        row.innerHTML = `
            <td>${fileName}</td>
            <td>${business}</td>
            <td>${formattedAmount}</td>
            <td><span class="${statusClass}">${status}</span></td>
        `;
        
        tbody.appendChild(row);
    }
    
    /**
     * Get absolute path to a file
     */
    function getAbsolutePath(relativePath) {
        // If it's already an absolute URL, return it
        if (relativePath.startsWith('http')) {
            return relativePath;
        }
        
        // Otherwise, construct the absolute URL
        const baseUrl = window.location.href.split('/').slice(0, -1).join('/');
        return `${baseUrl}/${relativePath}`;
    }
    
    /**
     * Get customer ID from localStorage or generate a new one
     */
    function getCustomerId() {
        // Try to get from localStorage
        let customerId = localStorage.getItem('c_id');
        
        // If not found, try to get from session
        if (!customerId && typeof sessionStorage !== 'undefined') {
            customerId = sessionStorage.getItem('c_id');
        }
        
        // If still not found, generate a new one
        if (!customerId) {
            customerId = 'cust_' + Date.now();
            
            // Try to save in localStorage if available
            if (typeof localStorage !== 'undefined') {
                try {
                    localStorage.setItem('c_id', customerId);
                } catch (e) {
                    console.warn('Could not save customer ID to localStorage');
                }
            }
            
            // Also try to save in sessionStorage
            if (typeof sessionStorage !== 'undefined') {
                try {
                    sessionStorage.setItem('c_id', customerId);
                } catch (e) {
                    console.warn('Could not save customer ID to sessionStorage');
                }
            }
        }
        
        return customerId;
    }
});
<?php
// Directory where files are uploaded
$uploadDir = 'uploads/';

// Get all uploaded files
$files = [];
if (file_exists($uploadDir)) {
    $fileList = scandir($uploadDir);
    foreach ($fileList as $file) {
        if ($file !== '.' && $file !== '..') {
            $filePath = $uploadDir . $file;
            $files[] = [
                'name' => $file,
                'path' => $filePath,
                'size' => filesize($filePath),
                'type' => mime_content_type($filePath),
                'date' => filemtime($filePath)
            ];
        }
    }
    
    // Sort by upload date (newest first)
    usort($files, function($a, $b) {
        return $b['date'] - $a['date'];
    });
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Spend - My Documents</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="documentsStyle.css">
</head>
<body>
    <div class="documents-container">
        <header class="documents-header">
            <div class="logo-title">
                <img src="images/SmartSpendLogo.png" alt="Smart Spend Logo" class="logo">
                <h1>Smart Spend</h1>
            </div>
            <nav>
                <a href="DashboardPage.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="#" class="active"><i class="fas fa-file-alt"></i> Documents</a>
                <a href="uploadDocs.php"><i class="fas fa-cloud-upload-alt"></i> Upload</a>
            </nav>
            <div class="profile-icon">
                <img src="images/ProfilePic.png" alt="User" class="avatar">
            </div>
        </header>

        <main class="documents-main">
            <div class="documents-header-section">
                <h2><i class="fas fa-file-alt"></i> My Documents</h2>
                <div class="search-filter">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Search documents...">
                    </div>
                </div>
            </div>

            <div class="documents-grid" id="documentsGrid">
                <?php if (!empty($files)): ?>
                    <?php foreach ($files as $file): ?>
                        <div class="document-card">
                            <div class="document-preview">
                                <?php if (strpos($file['type'], 'image/') === 0): ?>
                                    <img src="<?= htmlspecialchars($file['path']) ?>" alt="Document preview">
                                <?php elseif ($file['type'] === 'application/pdf'): ?>
                                    <i class="fas fa-file-pdf"></i>
                                <?php elseif (strpos($file['type'], 'text/') === 0): ?>
                                    <i class="fas fa-file-alt"></i>
                                <?php else: ?>
                                    <i class="fas fa-file"></i>
                                <?php endif; ?>
                            </div>
                            <div class="document-info">
                                <h3><?= htmlspecialchars($file['name']) ?></h3>
                                <div class="document-meta">
                                    <span><i class="fas fa-file"></i> <?= htmlspecialchars($file['type']) ?></span>
                                    <span><i class="fas fa-calendar-alt"></i> <?= date('M d, Y', $file['date']) ?></span>
                                    <span><i class="fas fa-weight-hanging"></i> <?= formatFileSize($file['size']) ?></span>
                                </div>
                                <div class="document-actions">
                                    <a href="<?= htmlspecialchars($file['path']) ?>" download class="download-btn">
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                    <a href="<?= htmlspecialchars($file['path']) ?>" target="_blank" class="view-btn">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <button class="delete-btn" data-file="<?= htmlspecialchars($file['name']) ?>">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        <h3>No documents found</h3>
                        <p>Upload your first document to get started</p>
                        <a href="uploadDocs.php" class="upload-btn">
                            <i class="fas fa-cloud-upload-alt"></i> Upload Documents
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Document filtering and search
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const documents = document.querySelectorAll('.document-card');
            
            documents.forEach(doc => {
                const docName = doc.querySelector('h3').textContent.toLowerCase();
                if (docName.includes(searchTerm)) {
                    doc.style.display = 'flex';
                } else {
                    doc.style.display = 'none';
                }
            });
        });

        // Delete document
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                if (confirm('Are you sure you want to delete this document?')) {
                    const fileName = this.getAttribute('data-file');
                    fetch('deleteDocument.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `file=${encodeURIComponent(fileName)}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.closest('.document-card').remove();
                            
                            // If no documents left, show empty state
                            if (document.querySelectorAll('.document-card').length === 0) {
                                document.getElementById('documentsGrid').innerHTML = `
                                    <div class="empty-state">
                                        <i class="fas fa-folder-open"></i>
                                        <h3>No documents found</h3>
                                        <p>Upload your first document to get started</p>
                                        <a href="uploadDocs.php" class="upload-btn">
                                            <i class="fas fa-cloud-upload-alt"></i> Upload Documents
                                        </a>
                                    </div>
                                `;
                            }
                        } else {
                            alert('Error deleting document: ' + data.message);
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>

<?php
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        return $bytes . ' bytes';
    } elseif ($bytes == 1) {
        return '1 byte';
    } else {
        return '0 bytes';
    }
}
?>
<?php
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../config/constants.php';

requireJobSeeker();

$userId = getCurrentUserId();
$cvId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$cvId) {
    header('Location: cv-manager.php');
    exit();
}

// Fetch CV details
$stmt = $pdo->prepare("
    SELECT * FROM cvs 
    WHERE id = ? AND user_id = ?
");
$stmt->execute([$cvId, $userId]);
$cv = $stmt->fetch();

if (!$cv) {
    header('Location: cv-manager.php');
    exit();
}

$cvPath = '../../uploads/cvs/' . $cv['file_path'];
$fileExtension = strtolower(pathinfo($cv['file_path'], PATHINFO_EXTENSION));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CV Preview - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .preview-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .preview-header {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .cv-info h1 {
            margin: 0 0 0.5rem 0;
            font-size: 1.5rem;
            color: #111827;
        }

        .cv-meta {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            font-size: 0.9rem;
            color: #6b7280;
        }

        .cv-meta span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .preview-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
            font-family: inherit;
        }

        .btn-primary {
            background: #dc2626;
            color: white;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        }

        .btn-primary:hover {
            background: #b91c1c;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(220, 38, 38, 0.4);
        }

        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
        }

        .btn-secondary:hover {
            background: #e5e7eb;
            border-color: #9ca3af;
        }

        .preview-frame {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
            position: relative;
        }

        .pdf-viewer {
            width: 100%;
            height: 100vh;
            min-height: 1200px;
            border: none;
        }

        .doc-preview {
            padding: 2rem;
            background: white;
            min-height: 600px;
        }

        .doc-message {
            text-align: center;
            padding: 3rem 2rem;
            color: #6b7280;
        }

        .doc-message i {
            font-size: 4rem;
            color: #dc2626;
            margin-bottom: 1rem;
        }

        .doc-message h3 {
            margin: 0 0 1rem 0;
            color: #111827;
        }

        .loading {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
        }

        .loading i {
            font-size: 3rem;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .preview-header {
                flex-direction: column;
                align-items: stretch;
            }

            .preview-actions {
                justify-content: stretch;
            }

            .btn {
                flex: 1;
                justify-content: center;
            }

            .pdf-viewer {
                height: 100vh;
                min-height: 900px;
            }
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>

    <div class="preview-container">
        <div class="preview-header">
            <div class="cv-info">
                <h1>
                    <i class="fas fa-file-<?php echo ($fileExtension === 'pdf') ? 'pdf' : (($fileExtension === 'html' || $fileExtension === 'htm') ? 'code' : 'alt'); ?>"></i> 
                    <?php echo htmlspecialchars($cv['title']); ?>
                </h1>
                <div class="cv-meta">
                    <span>
                        <i class="fas fa-calendar"></i>
                        Uploaded: <?php echo date('M d, Y', strtotime($cv['created_at'])); ?>
                    </span>
                    <span>
                        <i class="fas fa-file"></i>
                        <?php echo strtoupper($fileExtension); ?> File
                    </span>
                    <span>
                        <i class="fas fa-download"></i>
                        <?php echo $cv['download_count'] ?? 0; ?> downloads
                    </span>
                </div>
            </div>

            <div class="preview-actions">
                <?php if ($fileExtension === 'html' || $fileExtension === 'htm'): ?>
                    <button onclick="downloadAsPDF()" class="btn btn-primary">
                        <i class="fas fa-download"></i> Download as PDF
                    </button>
                    <a href="<?php echo $cvPath; ?>" download="<?php echo htmlspecialchars($cv['title']); ?>.html" class="btn btn-secondary">
                        <i class="fas fa-code"></i> Download HTML
                    </a>
                <?php else: ?>
                    <a href="<?php echo $cvPath; ?>" download="<?php echo htmlspecialchars($cv['title']); ?>.<?php echo $fileExtension; ?>" class="btn btn-primary" onclick="trackDownload()">
                        <i class="fas fa-download"></i> Download <?php echo strtoupper($fileExtension); ?>
                    </a>
                <?php endif; ?>
                <a href="cv-manager.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Manager
                </a>
            </div>
        </div>

        <?php if ($fileExtension === 'html' || $fileExtension === 'htm'): ?>
            <div style="background: #fef3c7; border: 1px solid #fbbf24; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; text-align: center;">
                <i class="fas fa-info-circle" style="color: #f59e0b;"></i>
                <strong>How to download as PDF:</strong> Click "Download as PDF" button, then select "Save as PDF" as the destination in the print dialog, and click Save.
            </div>
        <?php endif; ?>

        <div class="preview-frame">
            <?php if ($fileExtension === 'pdf'): ?>
                <iframe 
                    src="<?php echo $cvPath; ?>#toolbar=1&navpanes=0" 
                    class="pdf-viewer"
                    id="pdf-viewer"
                    onload="document.getElementById('loading').style.display='none';">
                </iframe>
                <div id="loading" class="loading">
                    <i class="fas fa-spinner"></i>
                    <p>Loading PDF...</p>
                </div>
            <?php elseif ($fileExtension === 'html' || $fileExtension === 'htm'): ?>
                <iframe 
                    src="<?php echo $cvPath; ?>" 
                    class="pdf-viewer"
                    id="html-viewer"
                    onload="document.getElementById('loading').style.display='none';">
                </iframe>
                <div id="loading" class="loading">
                    <i class="fas fa-spinner"></i>
                    <p>Loading CV...</p>
                </div>
            <?php elseif (in_array($fileExtension, ['doc', 'docx'])): ?>
                <div class="doc-message">
                    <i class="fas fa-file-word"></i>
                    <h3>Word Document Preview</h3>
                    <p>Word documents cannot be previewed directly in the browser.</p>
                    <p>Please download the file to view it in Microsoft Word or a compatible application.</p>
                    <br>
                    <a href="<?php echo $cvPath; ?>" download class="btn btn-primary">
                        <i class="fas fa-download"></i> Download <?php echo strtoupper($fileExtension); ?> File
                    </a>
                </div>
            <?php else: ?>
                <div class="doc-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Unsupported File Type</h3>
                    <p>This file type cannot be previewed in the browser.</p>
                    <p>Please download the file to view it.</p>
                    <br>
                    <a href="<?php echo $cvPath; ?>" download class="btn btn-primary">
                        <i class="fas fa-download"></i> Download File
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Track CV views
        fetch('../../api/cv-analytics.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'view',
                cv_id: <?php echo $cvId; ?>
            })
        });

        // Track CV downloads
        function trackDownload() {
            fetch('../../api/cv-analytics.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'download',
                    cv_id: <?php echo $cvId; ?>
                })
            });
        }

        // Download CV as PDF - Opens print dialog
        function downloadAsPDF() {
            // Track the download
            trackDownload();
            
            // Open CV in new window and trigger print
            const cvUrl = '<?php echo $cvPath; ?>';
            const printWindow = window.open(cvUrl, '_blank');
            
            if (printWindow) {
                printWindow.onload = function() {
                    setTimeout(function() {
                        printWindow.print();
                    }, 500); // Small delay to ensure content is loaded
                };
            } else {
                alert('Please allow pop-ups to download as PDF.\n\nAlternatively:\n1. Right-click on the CV preview below\n2. Select "Print" from the menu\n3. Choose "Save as PDF"');
            }
        }

        // Handle PDF/HTML loading errors
        const viewer = document.getElementById('pdf-viewer') || document.getElementById('html-viewer');
        viewer?.addEventListener('error', function() {
            this.style.display = 'none';
            const loadingDiv = document.getElementById('loading');
            if (loadingDiv) {
                loadingDiv.innerHTML = `
                    <i class="fas fa-exclamation-circle" style="color: #dc2626;"></i>
                    <p>Unable to load CV preview. Please download the file instead.</p>
                    <a href="<?php echo $cvPath; ?>" download class="btn btn-primary" style="margin-top: 1rem;">
                        <i class="fas fa-download"></i> Download File
                    </a>
                `;
                loadingDiv.style.display = 'block';
            }
        });
    </script>
</body>
</html>

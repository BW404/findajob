<?php
/**
 * FindAJob Nigeria - Development Email Viewer
 * View emails sent during development instead of actually sending them
 * Access: http://your-ip/findaj    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Development Email Viewer - FindAJob Nigeria</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="assets/images/icons/icon-192x192.svg">
    <link rel="alternate icon" href="assets/images/icons/icon-192x192.svg">
    <link rel="shortcut icon" href="assets/images/icons/icon-192x192.svg">temp_mail.php (works from any IP/domain when DEV_MODE=true)
 */

require_once 'config/constants.php';
require_once 'includes/functions.php';

// Only accessible in development mode
if (!isDevelopmentMode() && (!defined('DEV_MODE') || !DEV_MODE)) {
    die('This page is only available in development mode.');
}

$emailsFile = __DIR__ . '/temp_emails.json';

// Function to store email
function storeEmail($to, $subject, $message, $type = 'general') {
    global $emailsFile;
    
    $email = [
        'id' => uniqid(),
        'timestamp' => date('Y-m-d H:i:s'),
        'to' => $to,
        'subject' => $subject,
        'message' => $message,
        'type' => $type,
        'read' => false
    ];
    
    // Read existing emails
    $emails = [];
    if (file_exists($emailsFile)) {
        $existingData = file_get_contents($emailsFile);
        $emails = json_decode($existingData, true) ?: [];
    }
    
    // Add new email to the beginning
    array_unshift($emails, $email);
    
    // Keep only last 50 emails
    $emails = array_slice($emails, 0, 50);
    
    // Save back to file
    file_put_contents($emailsFile, json_encode($emails, JSON_PRETTY_PRINT));
    
    return true;
}

// Function to get all emails
function getEmails() {
    global $emailsFile;
    
    if (!file_exists($emailsFile)) {
        return [];
    }
    
    $data = file_get_contents($emailsFile);
    return json_decode($data, true) ?: [];
}

// Function to mark email as read
function markEmailAsRead($emailId) {
    global $emailsFile;
    
    $emails = getEmails();
    foreach ($emails as &$email) {
        if ($email['id'] === $emailId) {
            $email['read'] = true;
            break;
        }
    }
    
    file_put_contents($emailsFile, json_encode($emails, JSON_PRETTY_PRINT));
}

// Function to clear all emails
function clearEmails() {
    global $emailsFile;
    
    if (file_exists($emailsFile)) {
        unlink($emailsFile);
    }
    
    return true;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'mark_read':
            $emailId = $_POST['email_id'] ?? '';
            markEmailAsRead($emailId);
            echo json_encode(['success' => true]);
            break;
            
        case 'clear_all':
            clearEmails();
            echo json_encode(['success' => true]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    exit();
}

// Handle GET requests for viewing emails
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['view'])) {
    $emailId = $_GET['view'];
    $emails = getEmails();
    
    foreach ($emails as $email) {
        if ($email['id'] === $emailId) {
            markEmailAsRead($emailId);
            
            echo "<!DOCTYPE html>
            <html>
            <head>
                <title>Email View - {$email['subject']}</title>
                <style>
                    body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
                    .email-header { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
                    .email-content { border: 1px solid #dee2e6; padding: 20px; border-radius: 5px; }
                    .back-btn { background: #dc2626; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
                </style>
            </head>
            <body>
                <a href='temp_mail.php' class='back-btn'>← Back to Inbox</a>
                <div class='email-header'>
                    <h2>{$email['subject']}</h2>
                    <p><strong>To:</strong> {$email['to']}</p>
                    <p><strong>Date:</strong> {$email['timestamp']}</p>
                    <p><strong>Type:</strong> " . ucfirst($email['type']) . "</p>
                </div>
                <div class='email-content'>
                    <iframe srcdoc=\"" . htmlspecialchars($email['message']) . "\" 
                            style='width: 100%; min-height: 600px; border: none; border-radius: 8px;'
                            onload='this.style.height = this.contentDocument.body.scrollHeight + \"px\"'>
                    </iframe>
                    <details style='margin-top: 20px;'>
                        <summary style='cursor: pointer; color: #666; font-size: 14px;'>View Raw HTML</summary>
                        <pre style='background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; margin-top: 10px;'><code>" . htmlspecialchars($email['message']) . "</code></pre>
                    </details>
                </div>
            </body>
            </html>";
            exit();
        }
    }
    
    echo "Email not found.";
    exit();
}

$emails = getEmails();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Development Email Inbox - FindAJob Nigeria</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f8fafc;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .header {
            background: #dc2626;
            color: white;
            padding: 2rem;
            border-radius: 12px 12px 0 0;
        }
        
        .header h1 {
            margin: 0;
            font-size: 1.75rem;
        }
        
        .header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
        }
        
        .toolbar {
            padding: 1rem 2rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .btn {
            background: #dc2626;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 0.875rem;
        }
        
        .btn:hover {
            background: #991b1b;
        }
        
        .btn-secondary {
            background: #64748b;
        }
        
        .btn-secondary:hover {
            background: #475569;
        }
        
        .email-list {
            padding: 0;
        }
        
        .email-item {
            border-bottom: 1px solid #e2e8f0;
            padding: 1rem 2rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .email-item:hover {
            background-color: #f8fafc;
        }
        
        .email-item.unread {
            background-color: #fef2f2;
            border-left: 4px solid #dc2626;
        }
        
        .email-header-info {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.5rem;
        }
        
        .email-subject {
            font-weight: 600;
            color: #1e293b;
            margin: 0;
        }
        
        .email-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            font-size: 0.875rem;
            color: #64748b;
        }
        
        .email-type {
            background: #dc2626;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            text-transform: uppercase;
        }
        
        .email-to {
            color: #64748b;
            font-size: 0.875rem;
        }
        
        .email-preview {
            color: #64748b;
            font-size: 0.875rem;
            margin-top: 0.5rem;
            line-height: 1.4;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #64748b;
        }
        
        .stats {
            display: flex;
            gap: 2rem;
            font-size: 0.875rem;
        }
        
        .stat {
            color: #64748b;
        }
        
        .unread-count {
            background: #dc2626;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 10px;
            font-size: 0.75rem;
        }
        
        @media (max-width: 768px) {
            .toolbar {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }
            
            .email-header-info {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .stats {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📧 Development Email Inbox</h1>
            <p>View emails sent during development (Registration, Password Reset, etc.)</p>
        </div>
        
        <div class="toolbar">
            <div class="stats">
                <span class="stat">Total: <?php echo count($emails); ?> emails</span>
                <span class="stat">Unread: <span class="unread-count"><?php echo count(array_filter($emails, fn($e) => !$e['read'])); ?></span></span>
                <span class="stat">Last updated: <?php echo date('Y-m-d H:i:s'); ?></span>
            </div>
            <div>
                <button onclick="clearAllEmails()" class="btn btn-secondary">Clear All</button>
                <button onclick="location.reload()" class="btn">Refresh</button>
            </div>
        </div>
        
        <div class="email-list">
            <?php if (empty($emails)): ?>
                <div class="empty-state">
                    <h3>📭 No emails yet</h3>
                    <p>Emails sent by the application will appear here.</p>
                    <p>Try registering a new account or requesting a password reset to see emails.</p>
                </div>
            <?php else: ?>
                <?php foreach ($emails as $email): ?>
                    <div class="email-item <?php echo !$email['read'] ? 'unread' : ''; ?>" 
                         onclick="viewEmail('<?php echo $email['id']; ?>')">
                        <div class="email-header-info">
                            <h3 class="email-subject"><?php echo htmlspecialchars($email['subject']); ?></h3>
                            <div class="email-meta">
                                <span class="email-type"><?php echo $email['type']; ?></span>
                                <span><?php echo $email['timestamp']; ?></span>
                            </div>
                        </div>
                        <div class="email-to">To: <?php echo htmlspecialchars($email['to']); ?></div>
                        <div class="email-preview">
                            <?php 
                            $preview = strip_tags($email['message']);
                            $preview = substr($preview, 0, 150);
                            echo htmlspecialchars($preview) . (strlen($preview) >= 150 ? '...' : '');
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function viewEmail(emailId) {
            window.open('temp_mail.php?view=' + emailId, '_blank');
        }
        
        async function clearAllEmails() {
            if (!confirm('Are you sure you want to clear all emails?')) {
                return;
            }
            
            try {
                const response = await fetch('temp_mail.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=clear_all'
                });
                
                const result = await response.json();
                if (result.success) {
                    location.reload();
                } else {
                    alert('Failed to clear emails');
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        }
        
        // Auto-refresh every 30 seconds
        setInterval(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
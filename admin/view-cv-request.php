<?php
require_once '../config/database.php';
require_once '../config/session.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: login.php');
    exit();
}

$request_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$request_id) {
    header('Location: premium-cv-manager.php');
    exit();
}

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'update_status') {
            $status = $_POST['status'];
            $admin_notes = trim($_POST['admin_notes'] ?? '');
            
            $stmt = $pdo->prepare("UPDATE premium_cv_requests SET status = ?, admin_notes = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $admin_notes, $request_id]);
            $success_message = 'Status updated successfully';
            
        } elseif ($action === 'schedule_consultation') {
            $consultation_date = $_POST['consultation_date'];
            
            $stmt = $pdo->prepare("UPDATE premium_cv_requests SET consultation_scheduled = ?, status = 'in_progress', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$consultation_date, $request_id]);
            $success_message = 'Consultation scheduled successfully';
            
        } elseif ($action === 'set_delivery') {
            $delivery_date = $_POST['delivery_date'];
            
            $stmt = $pdo->prepare("UPDATE premium_cv_requests SET delivery_date = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$delivery_date, $request_id]);
            $success_message = 'Delivery date set successfully';
            
        } elseif ($action === 'mark_completed') {
            $stmt = $pdo->prepare("UPDATE premium_cv_requests SET status = 'completed', delivery_date = NOW(), updated_at = NOW() WHERE id = ?");
            $stmt->execute([$request_id]);
            $success_message = 'Request marked as completed';
        }
    } catch (Exception $e) {
        $error_message = 'Error: ' . $e->getMessage();
    }
}

// Get request details with user info
$stmt = $pdo->prepare("
    SELECT 
        pcr.*,
        u.first_name,
        u.last_name,
        u.email,
        u.phone
    FROM premium_cv_requests pcr
    LEFT JOIN users u ON pcr.user_id = u.id
    WHERE pcr.id = ?
");
$stmt->execute([$request_id]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    header('Location: premium-cv-manager.php');
    exit();
}

// Get plan details
require_once '../config/flutterwave.php';
$plan_details = PRICING_PLANS[$request['plan_type']] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View CV Request #<?php echo $request_id; ?> - Admin</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f7fa; }
        .admin-layout { display: flex; min-height: 100vh; }
        
        /* Sidebar Styles */
        .admin-sidebar {
            width: 260px;
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        .sidebar-header { padding: 24px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header h1 { font-size: 20px; font-weight: 700; color: #fff; margin-bottom: 4px; }
        .sidebar-header p { font-size: 13px; color: rgba(255,255,255,0.6); }
        .sidebar-nav { padding: 20px 0; }
        .nav-section { margin-bottom: 24px; }
        .nav-section-title { padding: 0 20px 8px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: rgba(255,255,255,0.5); }
        .nav-link { display: flex; align-items: center; padding: 12px 20px; color: rgba(255,255,255,0.8); text-decoration: none; transition: all 0.2s; }
        .nav-link:hover { background: rgba(255,255,255,0.1); color: white; }
        .nav-link.active { background: rgba(220, 38, 38, 0.2); color: white; border-left: 3px solid #dc2626; }
        .nav-link i { width: 20px; margin-right: 12px; font-size: 16px; }
        
        .admin-main { 
            margin-left: 260px; 
            flex: 1; 
            padding: 24px; 
            width: calc(100% - 260px); 
        }
        
        .info-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .info-card h3 {
            font-size: 1.25rem;
            color: #111827;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #f3f4f6;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        
        .info-item {
            padding: 0.75rem 0;
        }
        
        .info-label {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 0.25rem;
        }
        
        .info-value {
            font-size: 1rem;
            color: #111827;
            font-weight: 500;
        }
        
        .badge {
            display: inline-block;
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-in_progress { background: #dbeafe; color: #1e40af; }
        .badge-completed { background: #d1fae5; color: #065f46; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }
        .badge-paid { background: #d1fae5; color: #065f46; }
        .badge-unpaid { background: #fee2e2; color: #991b1b; }
        
        .action-section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            border: none;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #dc2626;
            color: white;
        }
        
        .btn-primary:hover {
            background: #b91c1c;
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        .btn-success {
            background: #10b981;
            color: white;
        }
        
        .btn-success:hover {
            background: #059669;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #10b981;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #dc2626;
        }
        
        .file-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: #f3f4f6;
            border-radius: 6px;
            color: #374151;
            text-decoration: none;
            font-size: 0.875rem;
        }
        
        .file-link:hover {
            background: #e5e7eb;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="admin-main">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h1><i class="fas fa-file-invoice"></i> CV Request #<?php echo $request_id; ?></h1>
                <a href="premium-cv-manager.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Requests
                </a>
            </div>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Client Information -->
            <div class="info-card">
                <h3><i class="fas fa-user"></i> Client Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Name</div>
                        <div class="info-value"><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?php echo htmlspecialchars($request['email']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Contact Phone</div>
                        <div class="info-value"><?php echo htmlspecialchars($request['contact_phone']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Contact Email</div>
                        <div class="info-value"><?php echo htmlspecialchars($request['contact_email']); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Request Details -->
            <div class="info-card">
                <h3><i class="fas fa-info-circle"></i> Request Details</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Plan Type</div>
                        <div class="info-value"><?php echo htmlspecialchars($plan_details['name'] ?? $request['plan_type']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Amount</div>
                        <div class="info-value">₦<?php echo number_format($request['amount']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Status</div>
                        <div class="info-value">
                            <span class="badge badge-<?php echo $request['status']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                            </span>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Payment Status</div>
                        <div class="info-value">
                            <span class="badge badge-<?php echo $request['payment_status'] === 'paid' ? 'paid' : 'unpaid'; ?>">
                                <?php echo ucfirst($request['payment_status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Submitted On</div>
                        <div class="info-value"><?php echo date('F j, Y \a\t g:i A', strtotime($request['created_at'])); ?></div>
                    </div>
                    <?php if ($request['consultation_scheduled']): ?>
                    <div class="info-item">
                        <div class="info-label">Consultation Scheduled</div>
                        <div class="info-value"><?php echo date('F j, Y \a\t g:i A', strtotime($request['consultation_scheduled'])); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($request['delivery_date']): ?>
                    <div class="info-item">
                        <div class="info-label">Delivery Date</div>
                        <div class="info-value"><?php echo date('F j, Y', strtotime($request['delivery_date'])); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($request['cv_file'])): ?>
                <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb;">
                    <div class="info-label" style="margin-bottom: 0.75rem;">Attached CV</div>
                    <a href="../<?php echo htmlspecialchars($request['cv_file']); ?>" target="_blank" class="file-link">
                        <i class="fas fa-file-pdf"></i> Download CV
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($request['additional_notes'])): ?>
                <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb;">
                    <div class="info-label" style="margin-bottom: 0.5rem;">Additional Notes</div>
                    <div class="info-value" style="white-space: pre-wrap;"><?php echo htmlspecialchars($request['additional_notes']); ?></div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Admin Actions -->
            <div class="action-section">
                <h3><i class="fas fa-cog"></i> Admin Actions</h3>
                
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; margin-top: 1.5rem;">
                    <!-- Update Status -->
                    <form method="POST" style="background: #f9fafb; padding: 1.5rem; border-radius: 8px;">
                        <input type="hidden" name="action" value="update_status">
                        <h4 style="margin-bottom: 1rem; color: #374151;">Update Status</h4>
                        
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" required>
                                <option value="pending" <?php echo $request['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="in_progress" <?php echo $request['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="completed" <?php echo $request['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $request['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Admin Notes</label>
                            <textarea name="admin_notes" placeholder="Add internal notes..."><?php echo htmlspecialchars($request['admin_notes'] ?? ''); ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-save"></i> Update Status
                        </button>
                    </form>
                    
                    <!-- Schedule Consultation -->
                    <form method="POST" style="background: #f9fafb; padding: 1.5rem; border-radius: 8px;">
                        <input type="hidden" name="action" value="schedule_consultation">
                        <h4 style="margin-bottom: 1rem; color: #374151;">Schedule Consultation</h4>
                        
                        <div class="form-group">
                            <label>Consultation Date & Time</label>
                            <input type="datetime-local" name="consultation_date" value="<?php echo $request['consultation_scheduled'] ? date('Y-m-d\TH:i', strtotime($request['consultation_scheduled'])) : ''; ?>" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-calendar"></i> Schedule Consultation
                        </button>
                    </form>
                    
                    <!-- Set Delivery Date -->
                    <form method="POST" style="background: #f9fafb; padding: 1.5rem; border-radius: 8px;">
                        <input type="hidden" name="action" value="set_delivery">
                        <h4 style="margin-bottom: 1rem; color: #374151;">Set Delivery Date</h4>
                        
                        <div class="form-group">
                            <label>Expected Delivery Date</label>
                            <input type="date" name="delivery_date" value="<?php echo $request['delivery_date'] ? date('Y-m-d', strtotime($request['delivery_date'])) : ''; ?>" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-truck"></i> Set Delivery Date
                        </button>
                    </form>
                    
                    <!-- Quick Complete -->
                    <form method="POST" style="background: #f9fafb; padding: 1.5rem; border-radius: 8px;">
                        <input type="hidden" name="action" value="mark_completed">
                        <h4 style="margin-bottom: 1rem; color: #374151;">Mark as Completed</h4>
                        
                        <p style="color: #6b7280; font-size: 0.875rem; margin-bottom: 1rem;">
                            This will mark the request as completed and set the delivery date to today.
                        </p>
                        
                        <button type="submit" class="btn btn-success" style="width: 100%;" onclick="return confirm('Are you sure you want to mark this request as completed?')">
                            <i class="fas fa-check-circle"></i> Mark Completed
                        </button>
                    </form>
                </div>
            </div>
            
            <?php if ($plan_details): ?>
            <!-- Plan Features -->
            <div class="info-card">
                <h3><i class="fas fa-list"></i> Plan Features</h3>
                <ul style="list-style: none; padding: 0;">
                    <?php foreach ($plan_details['features'] as $feature): ?>
                        <li style="padding: 0.5rem 0; color: #374151;">
                            <i class="fas fa-check-circle" style="color: #10b981; margin-right: 0.5rem;"></i>
                            <?php echo htmlspecialchars($feature); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

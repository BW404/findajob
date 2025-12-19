<?php
require_once '../../config/database.php';
require_once '../../config/session.php';

// Check if user is logged in and is an employer
if (!isLoggedIn() || !isEmployer()) {
    header('Location: ../auth/login.php');
    exit;
}

$userId = getCurrentUserId();

// Check if employer has Pro subscription
$stmt = $pdo->prepare("SELECT subscription_type, subscription_end FROM users WHERE id = ?");
$stmt->execute([$userId]);
$subscription = $stmt->fetch(PDO::FETCH_ASSOC);

$isPro = ($subscription['subscription_type'] === 'pro' && 
          (!$subscription['subscription_end'] || strtotime($subscription['subscription_end']) > time()));

if (!$isPro) {
    $_SESSION['upgrade_message'] = 'Private Job Offers are a Pro feature. Upgrade to access your private offers!';
    header('Location: ../payment/plans.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Private Job Offers - FindAJob</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .offers-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .filters {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filter-tabs {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .filter-tab {
            padding: 0.5rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .filter-tab.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .offers-grid {
            display: grid;
            gap: 1rem;
        }
        
        .offer-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .offer-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .offer-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }
        
        .offer-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0 0 0.5rem 0;
        }
        
        .candidate-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .candidate-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .status-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-viewed { background: #dbeafe; color: #1e40af; }
        .status-accepted { background: #d1fae5; color: #065f46; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        .status-expired { background: #e5e7eb; color: #374151; }
        .status-withdrawn { background: #e5e7eb; color: #374151; }
        
        .offer-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .offer-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #d1d5db;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <?php include '../../includes/employer-header.php'; ?>
    
    <div class="offers-container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <div>
                <h1 style="margin: 0 0 0.5rem 0;">📧 Private Job Offers</h1>
                <p style="margin: 0; color: var(--text-secondary);">
                    Manage exclusive job offers sent directly to candidates
                </p>
            </div>
            <a href="send-private-offer.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Send New Offer
            </a>
        </div>
        
        <div class="filters">
            <div class="filter-tabs">
                <button class="filter-tab active" data-status="all">
                    All <span class="count" id="count-all">0</span>
                </button>
                <button class="filter-tab" data-status="pending">
                    Pending <span class="count" id="count-pending">0</span>
                </button>
                <button class="filter-tab" data-status="viewed">
                    Viewed <span class="count" id="count-viewed">0</span>
                </button>
                <button class="filter-tab" data-status="accepted">
                    Accepted <span class="count" id="count-accepted">0</span>
                </button>
                <button class="filter-tab" data-status="rejected">
                    Rejected <span class="count" id="count-rejected">0</span>
                </button>
            </div>
        </div>
        
        <div class="offers-grid" id="offersGrid">
            <div style="text-align: center; padding: 2rem;">
                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--primary);"></i>
                <p>Loading offers...</p>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
    
    <script>
    let currentStatus = 'all';
    let allOffers = [];
    
    // Load offers
    async function loadOffers() {
        try {
            const response = await fetch(`../../api/private-job-offers.php?action=get_offers&status=${currentStatus}`);
            const data = await response.json();
            
            if (data.success) {
                allOffers = data.offers;
                renderOffers(data.offers);
                updateCounts(data.offers);
            }
        } catch (error) {
            console.error('Error loading offers:', error);
        }
    }
    
    // Render offers
    function renderOffers(offers) {
        const grid = document.getElementById('offersGrid');
        
        if (offers.length === 0) {
            grid.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-envelope-open"></i>
                    <h3>No offers yet</h3>
                    <p>Start sending private job offers to talented candidates</p>
                    <a href="send-private-offer.php" class="btn btn-primary" style="margin-top: 1rem;">
                        <i class="fas fa-plus"></i> Send Your First Offer
                    </a>
                </div>
            `;
            return;
        }
        
        grid.innerHTML = offers.map(offer => {
            const salaryRange = offer.salary_min && offer.salary_max 
                ? `₦${parseInt(offer.salary_min).toLocaleString()} - ₦${parseInt(offer.salary_max).toLocaleString()}`
                : 'Negotiable';
                
            const initials = offer.first_name ? offer.first_name.charAt(0).toUpperCase() : 'U';
            const fullName = `${offer.first_name || ''} ${offer.last_name || ''}`.trim() || 'Unknown';
            
            const deadline = new Date(offer.deadline);
            const now = new Date();
            const daysLeft = Math.ceil((deadline - now) / (1000 * 60 * 60 * 24));
            
            return `
                <div class="offer-card">
                    <div class="offer-header">
                        <div style="flex: 1;">
                            <h3 class="offer-title">${offer.job_title}</h3>
                            <div class="candidate-info">
                                <div class="candidate-avatar">
                                    ${offer.profile_picture 
                                        ? `<img src="../../uploads/profile-pictures/${offer.profile_picture}" style="width:100%; height:100%; object-fit:cover; border-radius:50%;">` 
                                        : initials}
                                </div>
                                <div>
                                    <strong>${fullName}</strong>
                                    ${offer.email ? `<p style="margin: 0.25rem 0 0 0; color: var(--text-secondary); font-size: 0.9rem;">${offer.email}</p>` : ''}
                                </div>
                            </div>
                        </div>
                        <span class="status-badge status-${offer.status}">${offer.status.charAt(0).toUpperCase() + offer.status.slice(1)}</span>
                    </div>
                    
                    <div class="offer-details">
                        <div class="detail-item">
                            <i class="fas fa-briefcase"></i>
                            <span>${offer.job_type.replace('-', ' ').replace(/\b\w/g, l => l.toUpperCase())}</span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-money-bill-wave"></i>
                            <span>${salaryRange}</span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>${offer.location_type.charAt(0).toUpperCase() + offer.location_type.slice(1)}</span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-calendar"></i>
                            <span>${daysLeft > 0 ? `${daysLeft} days left` : 'Expired'}</span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-clock"></i>
                            <span>Sent ${timeAgo(offer.created_at)}</span>
                        </div>
                        ${offer.viewed_at ? `
                        <div class="detail-item">
                            <i class="fas fa-eye"></i>
                            <span>Viewed ${timeAgo(offer.viewed_at)}</span>
                        </div>` : ''}
                    </div>
                    
                    ${offer.response_message && (offer.status === 'accepted' || offer.status === 'rejected') ? `
                    <div style="margin-top: 1rem; padding: 1rem; background: #f9fafb; border-radius: 8px;">
                        <strong style="color: var(--text-primary); display: block; margin-bottom: 0.5rem;">Response:</strong>
                        <p style="margin: 0; color: var(--text-secondary);">${offer.response_message}</p>
                    </div>
                    ` : ''}
                    
                    <div class="offer-actions">
                        <a href="view-private-offer.php?id=${offer.id}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                        ${offer.status === 'pending' || offer.status === 'viewed' ? `
                        <button onclick="withdrawOffer(${offer.id})" class="btn btn-danger btn-sm">
                            <i class="fas fa-times"></i> Withdraw
                        </button>
                        ` : ''}
                    </div>
                </div>
            `;
        }).join('');
    }
    
    // Update counts
    function updateCounts(offers) {
        const counts = {
            all: offers.length,
            pending: offers.filter(o => o.status === 'pending').length,
            viewed: offers.filter(o => o.status === 'viewed').length,
            accepted: offers.filter(o => o.status === 'accepted').length,
            rejected: offers.filter(o => o.status === 'rejected').length
        };
        
        Object.keys(counts).forEach(status => {
            const el = document.getElementById(`count-${status}`);
            if (el) el.textContent = `(${counts[status]})`;
        });
    }
    
    // Filter tabs
    document.querySelectorAll('.filter-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            currentStatus = this.dataset.status;
            loadOffers();
        });
    });
    
    // Withdraw offer
    async function withdrawOffer(offerId) {
        if (!confirm('Are you sure you want to withdraw this offer? This action cannot be undone.')) {
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('action', 'withdraw_offer');
            formData.append('offer_id', offerId);
            
            const response = await fetch('../../api/private-job-offers.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert('Offer withdrawn successfully');
                loadOffers();
            } else {
                alert('Error: ' + data.error);
            }
        } catch (error) {
            alert('An error occurred. Please try again.');
        }
    }
    
    // Time ago helper
    function timeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);
        
        const intervals = {
            year: 31536000,
            month: 2592000,
            week: 604800,
            day: 86400,
            hour: 3600,
            minute: 60
        };
        
        for (const [unit, secondsInUnit] of Object.entries(intervals)) {
            const interval = Math.floor(seconds / secondsInUnit);
            if (interval >= 1) {
                return interval === 1 ? `1 ${unit} ago` : `${interval} ${unit}s ago`;
            }
        }
        
        return 'just now';
    }
    
    // Initial load
    loadOffers();
    
    // Auto-refresh every 30 seconds
    setInterval(loadOffers, 30000);
    </script>
</body>
</html>

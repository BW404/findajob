<?php
require_once '../../config/database.php';
require_once '../../config/session.php';

// Check if user is logged in and is a job seeker
if (!isLoggedIn() || !isJobSeeker()) {
    header('Location: ../auth/login.php');
    exit;
}

$userId = getCurrentUserId();
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
            max-width: 1000px;
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
            position: relative;
        }
        
        .offer-card.unread {
            border-left: 4px solid var(--primary);
        }
        
        .offer-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .new-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: var(--primary);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .company-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .company-logo {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.5rem;
        }
        
        .offer-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0 0 0.5rem 0;
        }
        
        .offer-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
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
        
        .offer-message {
            background: #f9fafb;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }
        
        .offer-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
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
        
        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="offers-container">
        <div style="margin-bottom: 1.5rem;">
            <h1 style="margin: 0 0 0.5rem 0;">💌 Private Job Offers</h1>
            <p style="margin: 0; color: var(--text-secondary);">
                Exclusive job offers sent directly to you by employers
            </p>
        </div>
        
        <div class="alert-info">
            <i class="fas fa-info-circle"></i>
            <strong>Private Offers:</strong> These are exclusive opportunities where employers have personally selected you for consideration. 
            Review each offer carefully and respond by the deadline.
        </div>
        
        <div class="filters">
            <div class="filter-tabs">
                <button class="filter-tab active" data-status="all">
                    All <span class="count" id="count-all">0</span>
                </button>
                <button class="filter-tab" data-status="pending">
                    New <span class="count" id="count-pending">0</span>
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
                    <h3>No private offers yet</h3>
                    <p>When employers send you exclusive job offers, they'll appear here</p>
                </div>
            `;
            return;
        }
        
        grid.innerHTML = offers.map(offer => {
            const salaryRange = offer.salary_min && offer.salary_max 
                ? `₦${parseInt(offer.salary_min).toLocaleString()} - ₦${parseInt(offer.salary_max).toLocaleString()} ${offer.salary_period}`
                : 'Negotiable';
                
            const companyInitial = offer.company_name ? offer.company_name.charAt(0).toUpperCase() : 'C';
            
            const deadline = new Date(offer.deadline);
            const now = new Date();
            const daysLeft = Math.ceil((deadline - now) / (1000 * 60 * 60 * 24));
            const isExpiring = daysLeft <= 3 && daysLeft > 0;
            const isExpired = daysLeft <= 0;
            
            return `
                <div class="offer-card ${offer.status === 'pending' ? 'unread' : ''}">
                    ${offer.status === 'pending' ? '<span class="new-badge">NEW</span>' : ''}
                    
                    <div class="company-info">
                        <div class="company-logo">
                            ${offer.company_logo 
                                ? `<img src="../../uploads/profile-pictures/${offer.company_logo}" style="width:100%; height:100%; object-fit:cover; border-radius:12px;">` 
                                : companyInitial}
                        </div>
                        <div>
                            <strong style="font-size: 1.1rem;">${offer.company_name || 'Company'}</strong>
                            ${offer.industry ? `<p style="margin: 0.25rem 0 0 0; color: var(--text-secondary); font-size: 0.9rem;">${offer.industry}</p>` : ''}
                        </div>
                    </div>
                    
                    <h3 class="offer-title">${offer.job_title}</h3>
                    
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
                            <span>${offer.location_type.charAt(0).toUpperCase() + offer.location_type.slice(1)}${offer.city ? ` - ${offer.city}` : ''}</span>
                        </div>
                        <div class="detail-item ${isExpiring ? 'text-warning' : ''} ${isExpired ? 'text-danger' : ''}">
                            <i class="fas fa-clock"></i>
                            <span>${isExpired ? 'Expired' : isExpiring ? `⚠️ ${daysLeft} days left` : `${daysLeft} days left`}</span>
                        </div>
                    </div>
                    
                    ${offer.offer_message ? `
                    <div class="offer-message">
                        <strong style="display: block; margin-bottom: 0.5rem; color: var(--text-primary);">
                            <i class="fas fa-quote-left"></i> Personal Message:
                        </strong>
                        <p style="margin: 0; color: var(--text-secondary); white-space: pre-wrap;">${offer.offer_message}</p>
                    </div>
                    ` : ''}
                    
                    <div class="offer-actions">
                        <a href="view-private-offer.php?id=${offer.id}" class="btn btn-primary">
                            <i class="fas fa-eye"></i> View Full Offer
                        </a>
                        ${offer.status === 'pending' || offer.status === 'viewed' ? `
                        <button onclick="quickRespond(${offer.id}, 'accepted')" class="btn btn-success">
                            <i class="fas fa-check"></i> Accept
                        </button>
                        <button onclick="quickRespond(${offer.id}, 'rejected')" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Decline
                        </button>
                        ` : `
                        <span class="status-badge status-${offer.status}">
                            ${offer.status.charAt(0).toUpperCase() + offer.status.slice(1)}
                        </span>
                        `}
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
    
    // Quick respond
    async function quickRespond(offerId, response) {
        const message = response === 'accepted' 
            ? 'Are you sure you want to accept this offer? You can add a message on the details page.' 
            : 'Are you sure you want to decline this offer?';
            
        if (!confirm(message)) return;
        
        try {
            const formData = new FormData();
            formData.append('action', 'respond_to_offer');
            formData.append('offer_id', offerId);
            formData.append('response', response);
            formData.append('response_message', '');
            
            const resp = await fetch('../../api/private-job-offers.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await resp.json();
            
            if (data.success) {
                alert(response === 'accepted' ? 'Offer accepted! The employer will be notified.' : 'Offer declined.');
                loadOffers();
            } else {
                alert('Error: ' + data.error);
            }
        } catch (error) {
            alert('An error occurred. Please try again.');
        }
    }
    
    // Initial load
    loadOffers();
    
    // Auto-refresh every 30 seconds
    setInterval(loadOffers, 30000);
    </script>
</body>
</html>

<?php
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

// Check if user is logged in and is a job seeker
if (!isLoggedIn()) {
    header('Location: ../auth/login.php?return=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

if (!isJobSeeker()) {
    header('Location: ../../index.php');
    exit;
}

$centre_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$centre_id) {
    header('Location: job-centres.php');
    exit;
}

$user_id = getCurrentUserId();

// Get job centre details
$stmt = $pdo->prepare("
    SELECT jc.*,
           (SELECT COUNT(*) FROM job_centre_bookmarks WHERE job_centre_id = jc.id) as bookmark_count,
           (SELECT COUNT(*) FROM job_centre_bookmarks WHERE job_centre_id = jc.id AND user_id = ?) as is_bookmarked
    FROM job_centres jc
    WHERE jc.id = ? AND jc.is_active = 1
");
$stmt->execute([$user_id, $centre_id]);
$centre = $stmt->fetch();

if (!$centre) {
    header('Location: job-centres.php');
    exit;
}

// Parse services
$centre['services'] = json_decode($centre['services'], true) ?: [];

// Increment view count
$stmt = $pdo->prepare("UPDATE job_centres SET views_count = views_count + 1 WHERE id = ?");
$stmt->execute([$centre_id]);

$page_title = $centre['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - FindAJob Nigeria</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="manifest" href="../../manifest.json">
    <meta name="theme-color" content="#dc2626">
    
    <style>
        .centre-detail-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary);
            text-decoration: none;
            margin-bottom: 1.5rem;
            font-weight: 600;
            transition: all 0.3s;
        }

        .back-link:hover {
            gap: 0.75rem;
        }

        .centre-header {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }

        .header-top {
            display: flex;
            gap: 2rem;
            margin-bottom: 1.5rem;
        }

        .centre-logo-large {
            width: 120px;
            height: 120px;
            border-radius: 12px;
            object-fit: cover;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: 700;
            color: var(--primary);
        }

        .header-content {
            flex: 1;
        }

        .centre-name-large {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #111827;
        }

        .centre-badges-large {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge-verified { background: #dbeafe; color: #1e40af; }
        .badge-government { background: #dcfce7; color: #166534; }
        .badge-private { background: #fef3c7; color: #92400e; }
        .badge-online { background: #e0e7ff; color: #4338ca; }

        .rating-large {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .stars-large {
            font-size: 1.5rem;
        }

        .rating-text-large {
            font-size: 1.1rem;
            color: #6b7280;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .btn-large {
            padding: 0.875rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-secondary {
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-secondary:hover {
            background: #fef2f2;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        .detail-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-grid {
            display: grid;
            gap: 1.5rem;
        }

        .info-item {
            display: flex;
            gap: 1rem;
        }

        .info-icon {
            font-size: 1.5rem;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fef2f2;
            border-radius: 8px;
            flex-shrink: 0;
        }

        .info-content h4 {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 0.25rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .info-content p {
            font-size: 1rem;
            color: #111827;
            margin: 0;
        }

        .info-content a {
            color: var(--primary);
            text-decoration: none;
        }

        .info-content a:hover {
            text-decoration: underline;
        }

        .services-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }

        .service-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: #fef2f2;
            border-radius: 8px;
            color: var(--primary);
            font-weight: 500;
        }

        .review-form {
            background: #f9fafb;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }

        .star-rating {
            display: flex;
            gap: 0.5rem;
            font-size: 2rem;
            margin: 1rem 0;
        }

        .star {
            cursor: pointer;
            transition: all 0.2s;
        }

        .star:hover,
        .star.active {
            transform: scale(1.2);
        }

        .review-textarea {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-family: inherit;
            font-size: 1rem;
            resize: vertical;
            min-height: 100px;
        }

        .review-textarea:focus {
            outline: none;
            border-color: var(--primary);
        }

        .reviews-list {
            display: grid;
            gap: 1.5rem;
        }

        .review-card {
            padding: 1.5rem;
            background: #f9fafb;
            border-radius: 8px;
            border-left: 4px solid var(--primary-light);
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .reviewer-info {
            display: flex;
            gap: 1rem;
        }

        .reviewer-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: var(--primary);
        }

        .reviewer-details h4 {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .reviewer-details p {
            font-size: 0.875rem;
            color: #6b7280;
            margin: 0;
        }

        .sidebar-widget {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
        }

        .widget-title {
            font-size: 1.125rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .stat-item:last-child {
            border-bottom: none;
        }

        @media (max-width: 768px) {
            .detail-grid {
                grid-template-columns: 1fr;
            }

            .header-top {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .centre-name-large {
                font-size: 1.5rem;
            }

            .header-actions {
                flex-direction: column;
            }

            .services-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>

    <div class="centre-detail-container">
        <a href="job-centres.php" class="back-link">← Back to Job Centres</a>

        <!-- Header -->
        <div class="centre-header">
            <div class="header-top">
                <div class="centre-logo-large">
                    <?php if ($centre['logo']): ?>
                        <img src="../../uploads/job-centres/<?php echo htmlspecialchars($centre['logo']); ?>" alt="<?php echo htmlspecialchars($centre['name']); ?>">
                    <?php else: ?>
                        <?php echo htmlspecialchars(substr($centre['name'], 0, 1)); ?>
                    <?php endif; ?>
                </div>

                <div class="header-content">
                    <h1 class="centre-name-large"><?php echo htmlspecialchars($centre['name']); ?></h1>
                    
                    <div class="centre-badges-large">
                        <?php if ($centre['is_verified']): ?>
                            <span class="badge badge-verified">✓ Verified</span>
                        <?php endif; ?>
                        <span class="badge <?php echo $centre['is_government'] ? 'badge-government' : 'badge-private'; ?>">
                            <?php echo $centre['is_government'] ? '🏛️ Government' : '🏢 Private'; ?>
                        </span>
                        <span class="badge badge-online">
                            <?php 
                            echo $centre['category'] === 'online' ? '💻 Online' : 
                                 ($centre['category'] === 'offline' ? '📍 Offline' : '📍💻 Both'); 
                            ?>
                        </span>
                    </div>

                    <div class="rating-large">
                        <span class="stars-large" id="centreStars"></span>
                        <span class="rating-text-large">
                            <?php echo number_format($centre['rating_avg'], 1); ?> 
                            (<?php echo $centre['rating_count']; ?> reviews)
                        </span>
                    </div>

                    <?php if ($centre['description']): ?>
                        <p style="color: #6b7280; line-height: 1.6; margin-top: 1rem;">
                            <?php echo nl2br(htmlspecialchars($centre['description'])); ?>
                        </p>
                    <?php endif; ?>

                    <div class="header-actions">
                        <button class="btn-large btn-secondary" id="bookmarkBtn" onclick="toggleBookmark()">
                            <?php echo $centre['is_bookmarked'] ? '❤️ Bookmarked' : '🤍 Bookmark'; ?>
                        </button>
                        <?php if ($centre['website']): ?>
                            <a href="<?php echo htmlspecialchars($centre['website']); ?>" target="_blank" class="btn-large btn-primary">
                                🌐 Visit Website
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detail Grid -->
        <div class="detail-grid">
            <!-- Main Content -->
            <div>
                <!-- Contact Information -->
                <div class="detail-section">
                    <h2 class="section-title">📞 Contact Information</h2>
                    <div class="info-grid">
                        <?php if ($centre['address']): ?>
                            <div class="info-item">
                                <div class="info-icon">📍</div>
                                <div class="info-content">
                                    <h4>Address</h4>
                                    <p><?php echo nl2br(htmlspecialchars($centre['address'])); ?></p>
                                    <p style="color: #6b7280; margin-top: 0.25rem;">
                                        <?php echo htmlspecialchars($centre['city'] . ', ' . $centre['state']); ?>
                                    </p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($centre['contact_number']): ?>
                            <div class="info-item">
                                <div class="info-icon">📱</div>
                                <div class="info-content">
                                    <h4>Phone</h4>
                                    <p><a href="tel:<?php echo htmlspecialchars($centre['contact_number']); ?>">
                                        <?php echo htmlspecialchars($centre['contact_number']); ?>
                                    </a></p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($centre['email']): ?>
                            <div class="info-item">
                                <div class="info-icon">✉️</div>
                                <div class="info-content">
                                    <h4>Email</h4>
                                    <p><a href="mailto:<?php echo htmlspecialchars($centre['email']); ?>">
                                        <?php echo htmlspecialchars($centre['email']); ?>
                                    </a></p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($centre['operating_hours']): ?>
                            <div class="info-item">
                                <div class="info-icon">🕐</div>
                                <div class="info-content">
                                    <h4>Operating Hours</h4>
                                    <p><?php echo htmlspecialchars($centre['operating_hours']); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Services -->
                <?php if (!empty($centre['services'])): ?>
                    <div class="detail-section">
                        <h2 class="section-title">🛠️ Services Offered</h2>
                        <div class="services-list">
                            <?php foreach ($centre['services'] as $service): ?>
                                <div class="service-item">
                                    <span>✓</span>
                                    <span><?php echo htmlspecialchars($service); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Reviews Section -->
                <div class="detail-section">
                    <h2 class="section-title">⭐ Reviews & Ratings</h2>
                    
                    <!-- Review Form -->
                    <div class="review-form">
                        <h3 style="margin-bottom: 1rem;">Share Your Experience</h3>
                        <div class="star-rating" id="starRating">
                            <span class="star" data-rating="1">☆</span>
                            <span class="star" data-rating="2">☆</span>
                            <span class="star" data-rating="3">☆</span>
                            <span class="star" data-rating="4">☆</span>
                            <span class="star" data-rating="5">☆</span>
                        </div>
                        <textarea class="review-textarea" id="reviewText" placeholder="Write your review here... (optional)"></textarea>
                        <button class="btn-large btn-primary" style="margin-top: 1rem;" onclick="submitReview()">
                            Submit Review
                        </button>
                    </div>

                    <!-- Reviews List -->
                    <div class="reviews-list" id="reviewsList">
                        <div style="text-align: center; padding: 2rem; color: #6b7280;">
                            Loading reviews...
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div>
                <!-- Quick Stats -->
                <div class="sidebar-widget">
                    <h3 class="widget-title">📊 Statistics</h3>
                    <div class="stat-item">
                        <span>👁️ Total Views</span>
                        <strong><?php echo number_format($centre['views_count']); ?></strong>
                    </div>
                    <div class="stat-item">
                        <span>❤️ Bookmarks</span>
                        <strong><?php echo number_format($centre['bookmark_count']); ?></strong>
                    </div>
                    <div class="stat-item">
                        <span>⭐ Rating</span>
                        <strong><?php echo number_format($centre['rating_avg'], 1); ?>/5.0</strong>
                    </div>
                    <div class="stat-item">
                        <span>📝 Reviews</span>
                        <strong><?php echo number_format($centre['rating_count']); ?></strong>
                    </div>
                </div>

                <!-- Location Map Placeholder -->
                <?php if ($centre['category'] !== 'online'): ?>
                    <div class="sidebar-widget">
                        <h3 class="widget-title">📍 Location</h3>
                        <div style="background: #f3f4f6; height: 200px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #6b7280;">
                            <div style="text-align: center;">
                                <div style="font-size: 3rem; margin-bottom: 0.5rem;">🗺️</div>
                                <p style="margin: 0;"><?php echo htmlspecialchars($centre['city']); ?></p>
                                <p style="margin: 0.25rem 0 0; font-size: 0.875rem;">
                                    <?php echo htmlspecialchars($centre['state']); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>

    <script>
        const centreId = <?php echo $centre_id; ?>;
        let userRating = 0;
        let isBookmarked = <?php echo $centre['is_bookmarked'] ? 'true' : 'false'; ?>;

        // Display star rating
        function displayStars(rating, container) {
            const fullStars = Math.floor(rating);
            const hasHalfStar = rating % 1 >= 0.5;
            let stars = '';
            
            for (let i = 0; i < fullStars; i++) stars += '⭐';
            if (hasHalfStar) stars += '⭐';
            for (let i = fullStars + (hasHalfStar ? 1 : 0); i < 5; i++) stars += '☆';
            
            document.getElementById(container).textContent = stars;
        }

        // Star rating interaction
        document.querySelectorAll('.star').forEach(star => {
            star.addEventListener('click', function() {
                userRating = parseInt(this.dataset.rating);
                updateStarDisplay();
            });

            star.addEventListener('mouseenter', function() {
                const rating = parseInt(this.dataset.rating);
                document.querySelectorAll('.star').forEach((s, index) => {
                    s.textContent = index < rating ? '★' : '☆';
                });
            });
        });

        document.getElementById('starRating').addEventListener('mouseleave', updateStarDisplay);

        function updateStarDisplay() {
            document.querySelectorAll('.star').forEach((star, index) => {
                star.textContent = index < userRating ? '★' : '☆';
                star.classList.toggle('active', index < userRating);
            });
        }

        // Submit review
        async function submitReview() {
            if (userRating === 0) {
                alert('Please select a rating');
                return;
            }

            const review = document.getElementById('reviewText').value.trim();

            try {
                const response = await fetch('../../api/job-centres.php?action=add_review', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        job_centre_id: centreId,
                        rating: userRating,
                        review: review
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert('Review submitted successfully!');
                    userRating = 0;
                    document.getElementById('reviewText').value = '';
                    updateStarDisplay();
                    loadReviews();
                    location.reload(); // Refresh to show updated rating
                } else {
                    alert(data.error || 'Failed to submit review');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred');
            }
        }

        // Load reviews
        async function loadReviews() {
            try {
                const response = await fetch(`../../api/job-centres.php?action=get_reviews&job_centre_id=${centreId}`);
                const data = await response.json();

                if (data.success) {
                    displayReviews(data.reviews);
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }

        // Display reviews
        function displayReviews(reviews) {
            const container = document.getElementById('reviewsList');

            if (reviews.length === 0) {
                container.innerHTML = `
                    <div style="text-align: center; padding: 2rem; color: #6b7280;">
                        <p>No reviews yet. Be the first to review this job centre!</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = reviews.map(review => {
                const stars = '★'.repeat(review.rating) + '☆'.repeat(5 - review.rating);
                const initials = review.reviewer_name.split(' ').map(n => n[0]).join('').toUpperCase();
                const timeAgo = new Date(review.created_at).toLocaleDateString();

                return `
                    <div class="review-card">
                        <div class="review-header">
                            <div class="reviewer-info">
                                <div class="reviewer-avatar">${initials}</div>
                                <div class="reviewer-details">
                                    <h4>${review.reviewer_name}</h4>
                                    <p>${timeAgo}</p>
                                </div>
                            </div>
                            <div style="color: #fbbf24; font-size: 1.125rem;">${stars}</div>
                        </div>
                        ${review.review ? `<p style="color: #374151; line-height: 1.6;">${review.review}</p>` : ''}
                    </div>
                `;
            }).join('');
        }

        // Toggle bookmark
        async function toggleBookmark() {
            try {
                const action = isBookmarked ? 'remove_bookmark' : 'bookmark';
                const response = await fetch(`../../api/job-centres.php?action=${action}`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ job_centre_id: centreId })
                });

                const data = await response.json();

                if (data.success) {
                    isBookmarked = !isBookmarked;
                    const btn = document.getElementById('bookmarkBtn');
                    btn.textContent = isBookmarked ? '❤️ Bookmarked' : '🤍 Bookmark';
                } else {
                    alert(data.error || 'Failed to update bookmark');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred');
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            displayStars(<?php echo $centre['rating_avg']; ?>, 'centreStars');
            loadReviews();
        });
    </script>
</body>
</html>

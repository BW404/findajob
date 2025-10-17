<?php
// Job details page
// Enable debug output when ?debug=1 is present
if (isset($_GET['debug']) && $_GET['debug']) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    echo "<!-- DEBUG MODE ON -->\n";
}

// Includes
$dbPath = __DIR__ . '/../../config/database.php';
$sessionPath = __DIR__ . '/../../config/session.php';
$funcPath = __DIR__ . '/../../includes/functions.php';
$constPath = __DIR__ . '/../../config/constants.php';

if (!file_exists($dbPath)) {
    if (isset($_GET['debug']) && $_GET['debug']) echo "Missing database.php at $dbPath";
    http_response_code(500);
    exit;
}
require_once $dbPath;

if (!file_exists($sessionPath)) {
    if (isset($_GET['debug']) && $_GET['debug']) echo "Missing session.php at $sessionPath";
    http_response_code(500);
    exit;
}
require_once $sessionPath;

if (!file_exists($funcPath)) {
    if (isset($_GET['debug']) && $_GET['debug']) echo "Missing functions.php at $funcPath";
    http_response_code(500);
    exit;
}
require_once $funcPath;

// Load constants (SITE_NAME, SITE_URL, etc.) if available
if (file_exists($constPath)) {
    require_once $constPath;
}

// Quick DB sanity when debugging — produce visible output
if (isset($_GET['debug']) && $_GET['debug']) {
    echo "<div style='padding:12px;background:#fff4e5;border:1px solid #ffd59e;color:#3a2b00;font-family:system-ui,Segoe UI,Arial;margin:12px;'>";
    echo "<strong>DEBUG MODE ON</strong><br>";
    echo 'Includes:<pre style="white-space:pre-wrap;">' . htmlspecialchars("db: $dbPath\nsession: $sessionPath\nfunctions: $funcPath") . '</pre>';
    try {
        if (empty($pdo)) throw new Exception('PDO not set');
        $pdo->query('SELECT 1');
        echo "<div style='color:green;'>Database connection: OK</div>";
    } catch (Exception $e) {
        echo "<div style='color:red;'>Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    // SITE_NAME check
    echo "<div style='margin-top:6px;'>SITE_NAME: " . htmlspecialchars(defined('SITE_NAME') ? SITE_NAME : 'NOT_DEFINED') . "</div>";
    echo "<div style='margin-top:8px; font-size:0.9rem; color:#333;'>Request: " . htmlspecialchars($_SERVER['REQUEST_URI'] ?? '') . "</div>";
    echo "</div>";
}

// Get job id from query
$jobId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($jobId <= 0) {
    http_response_code(404);
    echo "<h1>404 - Job not found</h1>";
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT j.*, jc.name as category_name FROM jobs j LEFT JOIN job_categories jc ON j.category_id = jc.id WHERE j.id = ? AND j.STATUS = 'active' LIMIT 1");
    $stmt->execute([$jobId]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Job fetch error: ' . $e->getMessage());
    http_response_code(500);
    echo "<h1>500 - Server error</h1>";
    exit;
}

if (!$job) {
    http_response_code(404);
    echo "<h1>404 - Job not found</h1>";
    if (isset($_GET['debug']) && $_GET['debug']) {
        echo "<div style='background:#fee;border:1px solid #fbb;padding:12px;margin:12px;'>";
        echo "<strong>DEBUG:</strong> No job returned for ID: " . htmlspecialchars($jobId);
        // show a simple attempt to query ID existence in DB
        try {
            $check = $pdo->prepare("SELECT id, STATUS FROM jobs WHERE id = ? LIMIT 1");
            $check->execute([$jobId]);
            $row = $check->fetch(PDO::FETCH_ASSOC);
            echo '<pre style="white-space:pre-wrap;margin-top:8px;">' . htmlspecialchars(print_r($row, true)) . '</pre>';
        } catch (Exception $e) {
            echo '<div style="color:red;margin-top:8px;">DB check error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        echo "</div>";
    }
    exit;
}

// Increase view count (best-effort)
try {
    $update = $pdo->prepare("UPDATE jobs SET views_count = COALESCE(views_count,0) + 1 WHERE id = ?");
    $update->execute([$jobId]);
} catch (Exception $e) {
    // ignore
}

// Render simple page using existing assets
if (isset($_GET['debug']) && $_GET['debug']) {
    echo "<div style='padding:12px;background:#eef; border:1px solid #cce; margin:12px; font-family:monospace; white-space:pre-wrap;'>Fetched job:\n" . htmlspecialchars(print_r($job, true)) . "</div>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($job['title']); ?> - <?php echo SITE_NAME; ?></title>
    <link rel="manifest" href="../../manifest.json">
    <link rel="icon" type="image/svg+xml" href="../../assets/images/icons/icon-192x192.svg">
    <link rel="apple-touch-icon" href="../../assets/images/icons/icon-192x192.svg">
    <link rel="stylesheet" href="../../assets/css/main.css">
    <style>
        /* Responsive adjustments for job details */
        .job-detail { display: grid; grid-template-columns: 1fr 320px; gap: 2rem; }
        @media (max-width: 900px) {
            .job-detail { grid-template-columns: 1fr !important; display: block !important; }
            .job-sidebar { order: 2 !important; width: 100% !important; max-width: none !important; }
            .job-main { order: 1 !important; width: 100% !important; }
            .card { padding: 1rem !important; }
        }
        @media (max-width: 480px) {
            .job-title { font-size: 1.25rem !important; }
            .btn-block { width: 100% !important; display: inline-block !important; }
        }
        /* Ensure bottom nav doesn't overlay content on large viewports */
        body.has-bottom-nav { padding-bottom: 92px !important; }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>

    <div class="container">
        <main class="main-content" style="padding:2rem 0;">
            <div class="job-detail">
                <div class="job-main">
                    <div class="card" style="padding:1.5rem; border-radius:10px; background:var(--surface);">
                        <h1 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h1>
                        <div class="job-meta" style="color:var(--text-secondary); font-size:0.95rem; margin-top:6px;">
                            <span><strong>Company:</strong> <?php echo htmlspecialchars($job['company_name']); ?></span>
                            &nbsp;&middot;&nbsp;
                            <span><strong>Location:</strong> <?php echo htmlspecialchars(trim(($job['city'] ?? '') . ', ' . ($job['state'] ?? ' '), ', ')); ?></span>
                            &nbsp;&middot;&nbsp;
                            <span><strong>Type:</strong> <?php echo htmlspecialchars($job['job_type'] ?? ''); ?></span>
                        </div>

                        <hr style="margin:12px 0 16px; border-color:var(--border);">

                        <section class="job-section">
                            <h3 style="margin-bottom:8px;">Job description</h3>
                            <div class="job-description" style="line-height:1.6; color:var(--text-primary);"><?php echo nl2br(htmlspecialchars($job['description'] ?? '')); ?></div>
                        </section>

                        <?php if (!empty($job['requirements'])): ?>
                        <section class="job-section" style="margin-top:1rem;">
                            <h3 style="margin-bottom:8px;">Requirements</h3>
                            <div class="job-requirements">
                                <?php echo nl2br(htmlspecialchars($job['requirements'])); ?>
                            </div>
                        </section>
                        <?php endif; ?>

                        <?php if (!empty($job['responsibilities'])): ?>
                        <section class="job-section" style="margin-top:1rem;">
                            <h3 style="margin-bottom:8px;">Responsibilities</h3>
                            <div class="job-responsibilities">
                                <?php echo nl2br(htmlspecialchars($job['responsibilities'])); ?>
                            </div>
                        </section>
                        <?php endif; ?>

                        <div style="margin-top:1.5rem; color:var(--text-secondary); font-size:0.95rem;">
                            <strong>Posted:</strong> <?php echo date('M j, Y', strtotime($job['created_at'])); ?> &nbsp;•&nbsp;
                            <strong>Views:</strong> <?php echo (int)($job['views_count'] ?? 0) + 1; ?> &nbsp;•&nbsp;
                            <strong>Applications:</strong> <?php echo (int)($job['applications_count'] ?? 0); ?>
                        </div>
                    </div>
                </div>

                <aside class="job-sidebar">
                    <div class="card" style="padding:1.5rem; border-radius:10px; background:var(--surface); box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                        <div style="text-align:center; margin-bottom:16px;">
                            <img src="/findajob/assets/images/placeholders/company-placeholder.png" alt="Company logo" style="max-width:100%; height:80px; object-fit:contain;">
                        </div>

                        <div style="font-size:0.9rem; color:var(--text-secondary); line-height:1.8; margin-bottom:16px;">
                            <div style="margin-bottom:8px;">
                                <strong style="color:var(--text-primary);">Company:</strong> <?php echo htmlspecialchars($job['company_name']); ?>
                            </div>
                            <div style="margin-bottom:8px;">
                                <strong style="color:var(--text-primary);">Category:</strong> <?php echo htmlspecialchars($job['category_name'] ?? ''); ?>
                            </div>
                            <div style="margin-bottom:8px;">
                                <strong style="color:var(--text-primary);">Salary:</strong> <?php echo (!empty($job['salary_min']) && !empty($job['salary_max'])) ? '₦' . number_format($job['salary_min']) . ' - ₦' . number_format($job['salary_max']) . ' / ' . htmlspecialchars($job['salary_period']) : 'Not disclosed'; ?>
                            </div>
                            <div style="margin-bottom:8px;">
                                <strong style="color:var(--text-primary);">Location type:</strong> <?php echo htmlspecialchars(ucfirst($job['location_type'] ?? '')); ?>
                            </div>
                        </div>

                        <div style="display:flex; flex-direction:column; gap:8px;">
                            <?php if (!empty($job['application_email'])): ?>
                                <a class="btn btn-primary btn-block" style="text-align:center; padding:0.85rem 1rem; font-weight:600;" href="mailto:<?php echo htmlspecialchars($job['application_email']); ?>">Apply via Email</a>
                            <?php elseif (!empty($job['application_url'])): ?>
                                <a class="btn btn-primary btn-block" style="text-align:center; padding:0.85rem 1rem; font-weight:600;" href="<?php echo htmlspecialchars($job['application_url']); ?>" target="_blank">Apply on Company Site</a>
                            <?php else: ?>
                                <form method="post" action="/findajob/pages/jobs/apply.php" style="width:100%;">
                                    <input type="hidden" name="job_id" value="<?php echo (int)$jobId; ?>">
                                    <button class="btn btn-primary btn-block" style="text-align:center; padding:0.85rem 1rem; font-weight:600; width:100%;" type="submit">Apply Now</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </aside>
            </div>
        </main>
    </div>

    <?php include '../../includes/footer.php'; ?>

    <!-- Bottom Navigation for Mobile (PWA style) -->
    <nav class="app-bottom-nav">
        <a href="../../index.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">🏠</div>
            <div class="app-bottom-nav-label">Home</div>
        </a>
        <a href="browse.php" class="app-bottom-nav-item active">
            <div class="app-bottom-nav-icon">🔍</div>
            <div class="app-bottom-nav-label">Jobs</div>
        </a>
        <a href="../services/cv-creator.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">📄</div>
            <div class="app-bottom-nav-label">CV</div>
        </a>
        <a href="../user/dashboard.php" class="app-bottom-nav-item">
            <div class="app-bottom-nav-icon">👤</div>
            <div class="app-bottom-nav-label">Profile</div>
        </a>
    </nav>

    <!-- PWA Scripts -->
    <script src="../../assets/js/pwa.js"></script>
    <script>
        // Initialize PWA features if available
        if (typeof PWAManager !== 'undefined') {
            try { const pwa = new PWAManager(); pwa.init(); } catch(e) { console.warn('PWA init failed', e); }
        }
        // Add class for bottom nav styling
        document.body.classList.add('has-bottom-nav');
    </script>
</body>
</html>

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'includes/ad-display.php';

// Test database connection
if (!isset($pdo)) {
    die("Database connection failed!");
}

echo "<h1>Ad Display Test</h1>";
echo "<style>body { font-family: Arial; padding: 20px; background: #f5f7fa; } .debug { background: #fef3c7; padding: 10px; margin: 10px 0; border-radius: 5px; }</style>";

// Debug: Check total ads in database
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM advertisements WHERE is_active = 1");
    $total = $stmt->fetchColumn();
    echo "<div class='debug'>✅ Database connected. Total active ads: $total</div>";
    
    $stmt = $pdo->query("SELECT id, title, ad_type, placement, priority, start_date FROM advertisements WHERE is_active = 1 ORDER BY placement, priority DESC LIMIT 10");
    $all_ads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<div class='debug'><strong>Active Ads in Database:</strong><br>";
    foreach ($all_ads as $ad) {
        echo "- ID: {$ad['id']}, {$ad['title']} ({$ad['ad_type']}) - {$ad['placement']} - Priority: {$ad['priority']}<br>";
    }
    echo "</div>";
} catch (Exception $e) {
    echo "<div class='debug'>❌ Database error: " . $e->getMessage() . "</div>";
}

echo "<h2>Homepage Banner Ads</h2>";
$homepage_banner = getActiveAds('homepage', 'banner', 3);
echo "<p>Found: " . count($homepage_banner) . " banner ads for homepage</p>";
if (count($homepage_banner) > 0) {
    foreach ($homepage_banner as $ad) {
        echo "<p>- {$ad['title']} (Priority: {$ad['priority']})</p>";
        displayBannerAd($ad, 'medium');
    }
} else {
    echo "<div class='debug'>⚠️ No banner ads found for homepage. Check database.</div>";
}

echo "<h2>Jobs Page Sidebar Ads</h2>";
$jobs_sidebar = getActiveAds('jobs_page', 'sidebar', 3);
echo "<p>Found: " . count($jobs_sidebar) . " sidebar ads for jobs_page</p>";
if (count($jobs_sidebar) > 0) {
    foreach ($jobs_sidebar as $ad) {
        echo "<p>- {$ad['title']} (Priority: {$ad['priority']})</p>";
        displaySidebarAd($ad);
    }
} else {
    echo "<div class='debug'>⚠️ No sidebar ads found for jobs_page. Check database.</div>";
}

echo "<h2>Jobs Page Inline Ads</h2>";
$jobs_inline = getActiveAds('jobs_page', 'inline', 2);
echo "<p>Found: " . count($jobs_inline) . " inline ads for jobs_page</p>";
if (count($jobs_inline) > 0) {
    foreach ($jobs_inline as $ad) {
        echo "<p>- {$ad['title']}</p>";
        displayInlineAd($ad);
    }
} else {
    echo "<div class='debug'>⚠️ No inline ads found for jobs_page. Check database.</div>";
}

includeAdTrackingScript();
?>

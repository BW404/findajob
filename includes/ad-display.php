<?php
/**
 * Advertisement Display Helper Functions
 * Handles fetching and displaying ads across the platform
 */

// Don't require database here - it should already be loaded by the calling page
// The calling page must include database.php before ad-display.php

/**
 * Get active advertisements for a specific placement
 * @param string $placement The placement location (homepage, jobs_page, etc.)
 * @param string $ad_type Optional filter by ad type (banner, sidebar, inline, etc.)
 * @param int $limit Maximum number of ads to return
 * @return array Array of active advertisements
 */
function getActiveAds($placement, $ad_type = null, $limit = 5) {
    global $pdo;
    
    // Check if PDO connection exists
    if (!isset($pdo)) {
        error_log("PDO not initialized in getActiveAds");
        return [];
    }
    
    try {
        $sql = "
            SELECT * FROM advertisements 
            WHERE placement = ? 
            AND is_active = 1 
            AND start_date <= CURDATE() 
            AND (end_date IS NULL OR end_date >= CURDATE())
        ";
        
        $params = [$placement];
        
        if ($ad_type) {
            $sql .= " AND ad_type = ?";
            $params[] = $ad_type;
        }
        
        // Order by priority (highest first), then random for equal priorities
        // Note: LIMIT must be added directly to SQL, not as bound parameter
        $limit = (int)$limit; // Sanitize as integer
        $sql .= " ORDER BY priority DESC, RAND() LIMIT $limit";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Debug log
        error_log("getActiveAds: placement=$placement, type=$ad_type, found=" . count($results));
        
        return $results;
    } catch (Exception $e) {
        error_log("Get active ads error: " . $e->getMessage());
        return [];
    }
}

/**
 * Record ad impression
 * @param int $ad_id Advertisement ID
 */
function recordAdImpression($ad_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE advertisements SET impression_count = impression_count + 1 WHERE id = ?");
        $stmt->execute([$ad_id]);
    } catch (Exception $e) {
        error_log("Record impression error: " . $e->getMessage());
    }
}

/**
 * Record ad click
 * @param int $ad_id Advertisement ID
 */
function recordAdClick($ad_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("UPDATE advertisements SET click_count = click_count + 1 WHERE id = ?");
        $stmt->execute([$ad_id]);
    } catch (Exception $e) {
        error_log("Record click error: " . $e->getMessage());
    }
}

/**
 * Display banner advertisement
 * @param array $ad Advertisement data
 * @param string $size Size variant (large, medium, small)
 */
function displayBannerAd($ad, $size = 'large') {
    if (!$ad) return;
    
    recordAdImpression($ad['id']);
    
    $sizes = [
        'large' => ['width' => '100%', 'height' => '250px', 'text_size' => '28px'],
        'medium' => ['width' => '100%', 'height' => '150px', 'text_size' => '20px'],
        'small' => ['width' => '100%', 'height' => '90px', 'text_size' => '16px']
    ];
    
    $dimensions = $sizes[$size] ?? $sizes['medium'];
    
    // Random gradient colors for visual variety
    $gradients = [
        'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
        'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
        'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
        'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
        'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
        'linear-gradient(135deg, #30cfd0 0%, #330867 100%)',
        'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
        'linear-gradient(135deg, #ff9a56 0%, #ff6a88 100%)'
    ];
    $gradient = $gradients[($ad['id'] ?? 0) % count($gradients)];
    
    ?>
    <div class="ad-banner ad-banner-<?= $size ?>" style="margin: 20px 0; animation: slideInUp 0.6s ease-out;">
        <a href="<?= htmlspecialchars($ad['target_url'] ?? '#') ?>" 
           onclick="trackAdClick(<?= $ad['id'] ?>)"
           target="_blank" 
           rel="noopener noreferrer"
           style="display: block; text-decoration: none; overflow: hidden; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); transition: transform 0.3s ease, box-shadow 0.3s ease;"
           onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.2)'"
           onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'">
            <?php if ($ad['image_path']): ?>
                <img src="/findajob/<?= htmlspecialchars($ad['image_path']) ?>" 
                     alt="<?= htmlspecialchars($ad['title']) ?>"
                     style="width: <?= $dimensions['width'] ?>; height: <?= $dimensions['height'] ?>; object-fit: cover; display: block;">
            <?php else: ?>
                <div style="width: <?= $dimensions['width'] ?>; height: <?= $dimensions['height'] ?>; 
                            background: <?= $gradient ?>; 
                            display: flex; flex-direction: column; align-items: center; justify-content: center; 
                            padding: 30px; color: white; text-align: center; position: relative; overflow: hidden;">
                    <div style="position: absolute; top: -50%; right: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%); animation: pulse 3s ease-in-out infinite;"></div>
                    <h3 style="margin: 0 0 12px 0; font-size: <?= $dimensions['text_size'] ?>; font-weight: 700; position: relative; z-index: 1; text-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                        <?= htmlspecialchars($ad['title']) ?>
                    </h3>
                    <?php if ($ad['description']): ?>
                        <p style="margin: 0; font-size: 16px; opacity: 0.95; position: relative; z-index: 1; max-width: 600px; line-height: 1.5;">
                            <?= htmlspecialchars(substr($ad['description'], 0, 150)) ?><?= strlen($ad['description']) > 150 ? '...' : '' ?>
                        </p>
                    <?php endif; ?>
                    <span style="display: inline-block; margin-top: 15px; padding: 10px 24px; background: rgba(255,255,255,0.2); border-radius: 25px; font-weight: 600; font-size: 14px; position: relative; z-index: 1; backdrop-filter: blur(10px);">
                        Learn More →
                    </span>
                </div>
            <?php endif; ?>
        </a>
        <div style="font-size: 10px; color: #9ca3af; text-align: center; margin-top: 6px; font-weight: 500;">ADVERTISEMENT</div>
    </div>
    <style>
        @keyframes slideInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.3; }
            50% { transform: scale(1.1); opacity: 0.5; }
        }
    </style>
    <?php
}

/**
 * Display sidebar advertisement
 * @param array $ad Advertisement data
 */
function displaySidebarAd($ad) {
    if (!$ad) return;
    
    recordAdImpression($ad['id']);
    
    $icons = ['🎯', '💼', '📝', '🚀', '⭐', '💡', '🎓', '👔'];
    $icon = $icons[($ad['id'] ?? 0) % count($icons)];
    
    ?>
    <div class="ad-sidebar" style="margin-bottom: 20px; animation: fadeIn 0.6s ease-out;">
        <div style="font-size: 10px; color: #9ca3af; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">
            ⭐ SPONSORED
        </div>
        <a href="<?= htmlspecialchars($ad['target_url'] ?? '#') ?>" 
           onclick="trackAdClick(<?= $ad['id'] ?>)"
           target="_blank" 
           rel="noopener noreferrer"
           style="display: block; text-decoration: none; background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%); border-radius: 12px; 
                  padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); transition: all 0.3s ease; border: 2px solid #e5e7eb;"
           onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 16px rgba(0,0,0,0.12)'; this.style.borderColor='#dc2626'"
           onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.08)'; this.style.borderColor='#e5e7eb'">
            <?php if ($ad['image_path']): ?>
                <img src="/findajob/<?= htmlspecialchars($ad['image_path']) ?>" 
                     alt="<?= htmlspecialchars($ad['title']) ?>"
                     style="width: 100%; height: 180px; object-fit: cover; border-radius: 8px; margin-bottom: 14px;">
            <?php else: ?>
                <div style="width: 100%; height: 120px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-radius: 8px; margin-bottom: 14px; display: flex; align-items: center; justify-content: center; font-size: 48px;">
                    <?= $icon ?>
                </div>
            <?php endif; ?>
            <h4 style="margin: 0 0 10px 0; font-size: 17px; font-weight: 700; color: #1f2937; line-height: 1.3;">
                <?= htmlspecialchars($ad['title']) ?>
            </h4>
            <?php if ($ad['description']): ?>
                <p style="margin: 0; font-size: 14px; color: #6b7280; line-height: 1.6;">
                    <?= htmlspecialchars(substr($ad['description'], 0, 90)) ?><?= strlen($ad['description']) > 90 ? '...' : '' ?>
                </p>
            <?php endif; ?>
            <span style="display: inline-block; margin-top: 12px; padding: 8px 16px; background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); color: white; border-radius: 6px; font-size: 13px; font-weight: 600; box-shadow: 0 2px 4px rgba(220,38,38,0.3);">
                Click Here →
            </span>
        </a>
    </div>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
    <?php
}

/**
 * Display inline advertisement (within content)
 * @param array $ad Advertisement data
 */
function displayInlineAd($ad) {
    if (!$ad) return;
    
    recordAdImpression($ad['id']);
    
    ?>
    <div class="ad-inline" style="margin: 30px 0; padding: 20px; background: #f9fafb; border-left: 4px solid #dc2626; border-radius: 8px;">
        <div style="font-size: 10px; color: #9ca3af; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">
            Sponsored Content
        </div>
        <a href="<?= htmlspecialchars($ad['target_url'] ?? '#') ?>" 
           onclick="trackAdClick(<?= $ad['id'] ?>)"
           target="_blank" 
           rel="noopener noreferrer"
           style="display: flex; gap: 16px; text-decoration: none; align-items: center;">
            <?php if ($ad['image_path']): ?>
                <img src="<?= htmlspecialchars($ad['image_path']) ?>" 
                     alt="<?= htmlspecialchars($ad['title']) ?>"
                     style="width: 120px; height: 120px; object-fit: cover; border-radius: 6px; flex-shrink: 0;">
            <?php endif; ?>
            <div style="flex: 1;">
                <h4 style="margin: 0 0 8px 0; font-size: 18px; font-weight: 600; color: #1f2937;">
                    <?= htmlspecialchars($ad['title']) ?>
                </h4>
                <?php if ($ad['description']): ?>
                    <p style="margin: 0; font-size: 14px; color: #6b7280; line-height: 1.6;">
                        <?= htmlspecialchars($ad['description']) ?>
                    </p>
                <?php endif; ?>
                <span style="display: inline-block; margin-top: 12px; color: #dc2626; font-size: 14px; font-weight: 500;">
                    Learn More →
                </span>
            </div>
        </a>
    </div>
    <?php
}

/**
 * Display Google AdSense ad slot
 * @param string $slot Ad slot ID
 * @param string $format Ad format (auto, rectangle, horizontal, vertical)
 */
function displayGoogleAd($slot = '', $format = 'auto') {
    // Check if Google AdSense is enabled in settings
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'google_adsense_enabled'");
        $enabled = $stmt->fetchColumn();
        
        if (!$enabled) {
            return;
        }
        
        $stmt = $pdo->query("SELECT setting_value FROM site_settings WHERE setting_key = 'google_adsense_client_id'");
        $client_id = $stmt->fetchColumn();
        
        if (!$client_id) {
            return;
        }
        
    } catch (Exception $e) {
        error_log("Google Ad settings error: " . $e->getMessage());
        return;
    }
    
    ?>
    <div class="google-ad-container" style="margin: 20px 0; text-align: center;">
        <ins class="adsbygoogle"
             style="display:block"
             data-ad-client="<?= htmlspecialchars($client_id) ?>"
             <?php if ($slot): ?>data-ad-slot="<?= htmlspecialchars($slot) ?>"<?php endif; ?>
             data-ad-format="<?= htmlspecialchars($format) ?>"
             data-full-width-responsive="true"></ins>
        <script>
            (adsbygoogle = window.adsbygoogle || []).push({});
        </script>
    </div>
    <?php
}

/**
 * Display ads for a specific placement
 * @param string $placement Placement location
 * @param string $ad_type Optional ad type filter
 * @param int $count Number of ads to display
 */
function displayAds($placement, $ad_type = null, $count = 1) {
    $ads = getActiveAds($placement, $ad_type, $count);
    
    foreach ($ads as $ad) {
        // Handle custom code and Google AdSense
        if ($ad['ad_type'] === 'google_adsense' || $ad['ad_type'] === 'custom_code') {
            if (!empty($ad['custom_code'])) {
                recordAdImpression($ad['id']);
                echo '<div class="ad-custom-code" style="margin: 20px 0;">';
                echo '<div style="font-size: 10px; color: #9ca3af; text-align: center; margin-bottom: 4px;">Advertisement</div>';
                echo $ad['custom_code']; // Output the custom code directly
                echo '</div>';
            }
            continue;
        }
        
        // Handle standard ad types
        switch ($ad['ad_type']) {
            case 'banner':
                displayBannerAd($ad);
                break;
            case 'sidebar':
                displaySidebarAd($ad);
                break;
            case 'inline':
                displayInlineAd($ad);
                break;
        }
    }
}

/**
 * JavaScript for tracking ad clicks
 */
function includeAdTrackingScript() {
    ?>
    <script>
    function trackAdClick(adId) {
        // Send click tracking request
        fetch('/findajob/api/track-ad.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'click',
                ad_id: adId
            })
        }).catch(err => console.error('Ad tracking error:', err));
    }
    </script>
    <?php
}

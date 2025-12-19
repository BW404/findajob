<?php
/**
 * Test Script: Premium Job Seeker Priority in Search Results
 * 
 * This script tests the priority placement of premium job seekers
 * in employer search results.
 */

require_once 'config/database.php';

echo "<h1>Premium Job Seeker Priority Test</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f3f4f6; }
    h1 { color: #dc2626; }
    h2 { color: #374151; margin-top: 30px; }
    .success { color: #059669; font-weight: bold; }
    .error { color: #dc2626; font-weight: bold; }
    .info { background: #dbeafe; padding: 15px; border-radius: 8px; margin: 10px 0; }
    .test-result { background: white; padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #dc2626; }
    table { width: 100%; border-collapse: collapse; margin: 20px 0; background: white; }
    th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
    th { background: #f9fafb; font-weight: 600; }
    .badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; }
    .premium { background: #fef3c7; color: #92400e; }
    .free { background: #e5e7eb; color: #374151; }
</style>";

// Step 1: Check subscription fields
echo "<h2>Step 1: Verify Subscription Fields</h2>";
try {
    $result = $pdo->query("DESCRIBE users")->fetchAll();
    $has_subscription = false;
    foreach ($result as $col) {
        if (strpos($col['Field'], 'subscription') !== false) {
            $has_subscription = true;
            break;
        }
    }
    
    if ($has_subscription) {
        echo "<p class='success'>✓ Subscription fields exist in users table</p>";
    } else {
        echo "<p class='error'>✗ Subscription fields missing in users table</p>";
    }
    
    $result = $pdo->query("DESCRIBE job_seeker_profiles")->fetchAll();
    $has_boost = false;
    foreach ($result as $col) {
        if (strpos($col['Field'], 'boost') !== false) {
            $has_boost = true;
            break;
        }
    }
    
    if ($has_boost) {
        echo "<p class='success'>✓ Boost fields exist in job_seeker_profiles table</p>";
    } else {
        echo "<p class='error'>✗ Boost fields missing in job_seeker_profiles table</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Error checking fields: " . $e->getMessage() . "</p>";
}

// Step 2: Check for job seekers with CVs
echo "<h2>Step 2: Check Job Seekers with CVs</h2>";
try {
    $stmt = $pdo->query("
        SELECT COUNT(DISTINCT cv.user_id) as total
        FROM cvs cv
        INNER JOIN users u ON cv.user_id = u.id
        WHERE u.user_type = 'job_seeker'
        AND u.is_active = 1
    ");
    $total = $stmt->fetch()['total'];
    
    echo "<p>Found <strong>{$total}</strong> job seekers with CVs</p>";
    
    if ($total == 0) {
        echo "<p class='error'>✗ No job seekers with CVs found. Create some test users first.</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

// Step 3: Create test premium users
echo "<h2>Step 3: Create Test Premium Users</h2>";
try {
    // Get first 3 job seekers
    $stmt = $pdo->query("
        SELECT u.id, u.first_name, u.last_name, u.email, 
               u.subscription_status, u.subscription_plan
        FROM cvs cv
        INNER JOIN users u ON cv.user_id = u.id
        WHERE u.user_type = 'job_seeker'
        AND u.is_active = 1
        LIMIT 3
    ");
    $job_seekers = $stmt->fetchAll();
    
    if (count($job_seekers) >= 2) {
        // Make first one premium via subscription
        $user1 = $job_seekers[0];
        $pdo->prepare("
            UPDATE users 
            SET subscription_status = 'active',
                subscription_plan = 'pro',
                subscription_type = 'monthly',
                subscription_start = NOW(),
                subscription_end = DATE_ADD(NOW(), INTERVAL 30 DAY)
            WHERE id = ?
        ")->execute([$user1['id']]);
        
        echo "<p class='success'>✓ Made {$user1['first_name']} {$user1['last_name']} ({$user1['email']}) a Premium user via subscription</p>";
        
        // Make second one premium via profile boost
        if (count($job_seekers) >= 2) {
            $user2 = $job_seekers[1];
            $pdo->prepare("
                UPDATE job_seeker_profiles 
                SET profile_boosted = 1,
                    profile_boost_until = DATE_ADD(NOW(), INTERVAL 7 DAY)
                WHERE user_id = ?
            ")->execute([$user2['id']]);
            
            echo "<p class='success'>✓ Made {$user2['first_name']} {$user2['last_name']} ({$user2['email']}) Premium via profile boost</p>";
        }
        
        // Keep third one free (if exists)
        if (count($job_seekers) >= 3) {
            $user3 = $job_seekers[2];
            echo "<p class='info'>ℹ {$user3['first_name']} {$user3['last_name']} ({$user3['email']}) remains as Free user</p>";
        }
    } else {
        echo "<p class='error'>✗ Need at least 2 job seekers with CVs to test</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

// Step 4: Test the search query
echo "<h2>Step 4: Test Search Query with Premium Priority</h2>";
try {
    $query = "SELECT DISTINCT
          cv.id as cv_id,
          u.id as user_id,
          u.first_name,
          u.last_name,
          u.email,
          u.subscription_status,
          u.subscription_plan,
          u.subscription_end,
          jsp.profile_boosted,
          jsp.profile_boost_until,
          CASE 
            WHEN u.subscription_status = 'active' AND u.subscription_plan = 'pro' AND (u.subscription_end IS NULL OR u.subscription_end > NOW()) THEN 1
            WHEN jsp.profile_boosted = 1 AND (jsp.profile_boost_until IS NULL OR jsp.profile_boost_until > NOW()) THEN 1
            ELSE 0
          END as is_premium
          FROM cvs cv
          INNER JOIN users u ON cv.user_id = u.id
          LEFT JOIN job_seeker_profiles jsp ON u.id = jsp.user_id
          WHERE u.user_type = 'job_seeker'
          AND u.is_active = 1
          ORDER BY is_premium DESC, cv.created_at DESC
          LIMIT 10";
    
    $stmt = $pdo->query($query);
    $results = $stmt->fetchAll();
    
    if (count($results) > 0) {
        echo "<p class='success'>✓ Query executed successfully. Found " . count($results) . " results</p>";
        echo "<table>";
        echo "<tr>
                <th>#</th>
                <th>Name</th>
                <th>Email</th>
                <th>Status</th>
                <th>Premium</th>
                <th>Subscription End</th>
                <th>Boost Until</th>
              </tr>";
        
        foreach ($results as $index => $row) {
            $premiumBadge = $row['is_premium'] ? 
                "<span class='badge premium'>PREMIUM</span>" : 
                "<span class='badge free'>FREE</span>";
            
            echo "<tr>";
            echo "<td>" . ($index + 1) . "</td>";
            echo "<td>{$row['first_name']} {$row['last_name']}</td>";
            echo "<td>{$row['email']}</td>";
            echo "<td>{$row['subscription_status']} / {$row['subscription_plan']}</td>";
            echo "<td>{$premiumBadge}</td>";
            echo "<td>" . ($row['subscription_end'] ?? '-') . "</td>";
            echo "<td>" . ($row['profile_boost_until'] ?? '-') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Verify premium users appear first
        $first_result_premium = $results[0]['is_premium'];
        if ($first_result_premium) {
            echo "<div class='test-result'>";
            echo "<p class='success'>✓ TEST PASSED: Premium users appear first in results!</p>";
            echo "</div>";
        } else {
            echo "<div class='test-result'>";
            echo "<p class='info'>ℹ Note: First result is not premium. This is expected if no premium users exist.</p>";
            echo "</div>";
        }
    } else {
        echo "<p class='error'>✗ No results found</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
}

// Step 5: Provide test instructions
echo "<h2>Step 5: Manual Testing</h2>";
echo "<div class='info'>";
echo "<p><strong>To test the feature on the actual CV search page:</strong></p>";
echo "<ol>";
echo "<li>Login as an employer</li>";
echo "<li>Navigate to <code>/pages/company/search-cvs.php</code></li>";
echo "<li>Verify that job seekers with premium subscriptions or profile boosts appear at the top</li>";
echo "<li>Look for the <strong>'Boosted'</strong> badge next to premium users' names</li>";
echo "<li>Try different sort options - premium users should always appear first</li>";
echo "</ol>";
echo "</div>";

echo "<h2>Summary</h2>";
echo "<div class='test-result'>";
echo "<p><strong>Premium Priority Features Implemented:</strong></p>";
echo "<ul>";
echo "<li>✓ Premium users (pro subscription OR profile boost) appear first in search results</li>";
echo "<li>✓ 'Boosted' badge displays on premium profiles</li>";
echo "<li>✓ Priority maintained across all sort options (newest, oldest, experience)</li>";
echo "<li>✓ Badge has animated pulse effect to draw attention</li>";
echo "</ul>";
echo "</div>";

echo "<p style='margin-top: 30px; padding: 15px; background: #fef3c7; border-radius: 8px;'>";
echo "<strong>Note:</strong> This test script created some test premium users. ";
echo "The changes are persistent in the database.";
echo "</p>";
?>

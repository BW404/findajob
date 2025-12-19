<?php
/**
 * Test pricing update regex pattern
 */

// Sample content from flutterwave.php
$config_content = <<<'EOF'
    'job_seeker_pro_monthly' => [
        'name' => 'Job Seeker Pro (Monthly)',
        'price' => 6000,
        'duration' => '30 days',
        'type' => 'subscription',
        'user_type' => 'job_seeker'
    ],
    'job_seeker_pro_yearly' => [
        'name' => 'Job Seeker Pro (Yearly)',
        'price' => 60000,
        'duration' => '365 days',
        'type' => 'subscription',
        'user_type' => 'job_seeker',
        'savings' => '12,000 savings!'
    ],
EOF;

// Test updating job_seeker_pro_monthly from 6000 to 7500
$plan_key = 'job_seeker_pro_monthly';
$new_price = 7500;

$pattern = "/('" . preg_quote($plan_key, '/') . "'\\s*=>\\s*\\[[^\\]]*'price'\\s*=>\\s*)(\\d+)/s";

echo "Testing pricing update...\n\n";
echo "Plan Key: $plan_key\n";
echo "New Price: $new_price\n";
echo "Pattern: $pattern\n\n";

$replacement = '${1}' . intval($new_price);
$updated_content = preg_replace($pattern, $replacement, $config_content, 1, $count);

echo "Matches found: $count\n\n";
echo "Original:\n";
echo $config_content . "\n\n";
echo "Updated:\n";
echo $updated_content . "\n\n";

// Test with job_seeker_pro_yearly
$plan_key = 'job_seeker_pro_yearly';
$new_price = 65000;

$pattern = "/('" . preg_quote($plan_key, '/') . "'\\s*=>\\s*\\[[^\\]]*'price'\\s*=>\\s*)(\\d+)/s";
$replacement = '${1}' . intval($new_price);
$updated_content = preg_replace($pattern, $replacement, $config_content, 1, $count);

echo "Testing second plan...\n";
echo "Plan Key: $plan_key\n";
echo "New Price: $new_price\n";
echo "Matches found: $count\n\n";
echo "Updated:\n";
echo $updated_content . "\n";

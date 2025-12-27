<?php
/**
 * Test Interview API
 * Quick test to verify the interview scheduling API works
 */

require_once 'config/database.php';
require_once 'config/session.php';

echo "<h1>Interview API Test</h1>";

// Check database connection
if (isset($pdo)) {
    echo "<p style='color: green;'>✓ Database connection OK</p>";
} else {
    echo "<p style='color: red;'>✗ Database connection FAILED</p>";
}

// Check if user is logged in
if (isLoggedIn()) {
    echo "<p style='color: green;'>✓ User logged in: " . getCurrentUserId() . " (Type: " . $_SESSION['user_type'] . ")</p>";
} else {
    echo "<p style='color: red;'>✗ User not logged in</p>";
}

// Check if interview.php exists
if (file_exists('api/interview.php')) {
    echo "<p style='color: green;'>✓ interview.php file exists</p>";
} else {
    echo "<p style='color: red;'>✗ interview.php file missing</p>";
}

// Check if email function exists
if (function_exists('sendInterviewScheduledEmail')) {
    echo "<p style='color: green;'>✓ Email function loaded</p>";
} else {
    echo "<p style='color: red;'>✗ Email function not found</p>";
}

// Test API endpoint
echo "<hr><h2>Test API Call</h2>";

if (isLoggedIn() && $_SESSION['user_type'] === 'employer') {
    // Get a sample application
    $stmt = $pdo->prepare("
        SELECT ja.id, ja.job_id, j.title, u.first_name, u.last_name
        FROM job_applications ja
        JOIN jobs j ON ja.job_id = j.id
        JOIN users u ON ja.job_seeker_id = u.id
        WHERE j.employer_id = ?
        LIMIT 1
    ");
    $stmt->execute([getCurrentUserId()]);
    $app = $stmt->fetch();
    
    if ($app) {
        echo "<p>Sample Application Found: ID {$app['id']} - {$app['first_name']} {$app['last_name']} for {$app['title']}</p>";
        
        echo "<form method='POST' action='../api/interview.php' style='border: 1px solid #ccc; padding: 20px; background: #f9f9f9;'>
            <h3>Schedule Test Interview</h3>
            <input type='hidden' name='action' value='schedule_interview'>
            <input type='hidden' name='application_id' value='{$app['id']}'>
            
            <div style='margin: 10px 0;'>
                <label>Date: <input type='date' name='interview_date' value='" . date('Y-m-d', strtotime('+1 day')) . "' required></label>
            </div>
            
            <div style='margin: 10px 0;'>
                <label>Time: <input type='time' name='interview_time' value='14:00' required></label>
            </div>
            
            <div style='margin: 10px 0;'>
                <label>Type: 
                    <select name='interview_type' required>
                        <option value='phone'>Phone</option>
                        <option value='video'>Video</option>
                        <option value='in_person'>In-Person</option>
                        <option value='online'>Online</option>
                    </select>
                </label>
            </div>
            
            <div style='margin: 10px 0;'>
                <label>Link: <input type='url' name='interview_link' value='https://meet.google.com/test-test-test' placeholder='Optional'></label>
            </div>
            
            <div style='margin: 10px 0;'>
                <label>Notes: <textarea name='interview_notes' rows='3'>Please be available 5 minutes before the scheduled time.</textarea></label>
            </div>
            
            <button type='submit' style='padding: 10px 20px; background: #10b981; color: white; border: none; border-radius: 5px; cursor: pointer;'>
                Schedule Interview
            </button>
        </form>";
    } else {
        echo "<p style='color: orange;'>No applications found for testing</p>";
    }
} else {
    echo "<p style='color: orange;'>You must be logged in as an employer to test</p>";
}

echo "<hr>";
echo "<p><a href='../pages/company/applicants.php'>← Back to Applicants</a></p>";
?>

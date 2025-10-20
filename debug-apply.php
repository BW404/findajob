<?php
// Debug apply button issue
require_once 'config/database.php';
require_once 'config/session.php';

echo "<h2>Apply Button Debug</h2>\n\n";
echo "<strong>Session Status:</strong>\n";
echo "- Session started: " . (session_status() === PHP_SESSION_ACTIVE ? 'YES' : 'NO') . "\n";
echo "- Logged in: " . (isLoggedIn() ? 'YES' : 'NO') . "\n";

if (isLoggedIn()) {
    echo "- User ID: " . getCurrentUserId() . "\n";
    echo "- User type: " . $_SESSION['user_type'] . "\n";
    echo "- Is job seeker: " . (isJobSeeker() ? 'YES' : 'NO') . "\n";
    
    // Get user details
    $userId = getCurrentUserId();
    $stmt = $pdo->prepare("SELECT id, email, user_type, first_name FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\n<strong>User Details:</strong>\n";
    print_r($user);
} else {
    echo "\n⚠️ NOT LOGGED IN\n";
    echo "\nSession data:\n";
    print_r($_SESSION);
}

echo "\n\n<strong>Test URLs:</strong>\n";
echo "1. Login as job seeker: <a href='/findajob/pages/auth/login-jobseeker.php'>Login</a>\n";
echo "2. View job details: <a href='/findajob/pages/jobs/details.php?id=1'>Job Details</a>\n";
echo "3. Apply directly: <a href='/findajob/pages/jobs/apply.php'>Apply (will redirect)</a>\n";

echo "\n\n<strong>Form Test:</strong>\n";
echo '<form method="POST" action="/findajob/pages/jobs/apply.php" style="margin-top:1rem;">
    <input type="hidden" name="job_id" value="1">
    <button type="submit" style="padding:0.5rem 1rem; background:#3b82f6; color:white; border:none; border-radius:4px; cursor:pointer;">
        Test Apply Now
    </button>
</form>';

echo "\n\n<strong>JavaScript Check:</strong>\n";
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Page loaded');
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            console.log('Form submitted!');
            console.log('Action:', this.action);
            console.log('Method:', this.method);
            console.log('Job ID:', this.querySelector('[name="job_id"]').value);
        });
    }
});
</script>

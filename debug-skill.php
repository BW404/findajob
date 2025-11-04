<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    die('Please log in to test.');
}

$userId = getCurrentUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_skills'])) {
    $val = trim($_POST['test_skills']);
    $stmt = $pdo->prepare("UPDATE job_seeker_profiles SET skills = ? WHERE user_id = ?");
    $stmt->execute([$val, $userId]);
    echo "<p>Updated skills to: " . htmlspecialchars($val) . "</p>";
}

$stmt = $pdo->prepare("SELECT skills FROM job_seeker_profiles WHERE user_id = ?");
$stmt->execute([$userId]);
$row = $stmt->fetch();
$skills = $row['skills'] ?? null;
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Debug Skills</title></head>
<body>
<h2>Current stored skills</h2>
<pre><?php echo htmlspecialchars(var_export($skills, true)); ?></pre>

<form method="POST">
    <label>Set test skills (comma separated):</label><br>
    <input type="text" name="test_skills" style="width:400px" value="<?php echo htmlspecialchars($skills); ?>" />
    <button type="submit">Update</button>
</form>

<p>After updating, reload your dashboard to see if the Profile Summary reflects the change.</p>
</body>
</html>
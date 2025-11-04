<?php
require_once 'config/database.php';
require_once 'config/session.php';

if (!isLoggedIn()) {
    die('Please log in.');
}

$userId = getCurrentUserId();

$stmt = $pdo->prepare("SELECT nin_verification_data FROM job_seeker_profiles WHERE user_id = ?");
$stmt->execute([$userId]);
$row = $stmt->fetch();

if (empty($row['nin_verification_data'])) {
    die('No NIN verification data found for this user.');
}

$data = json_decode($row['nin_verification_data'], true);
if (!$data) {
    die('Invalid NIN data JSON');
}

// Update users
$userUpdates = [];
$params = [];
if (!empty($data['first_name'])) {
    $userUpdates[] = 'first_name = ?';
    $params[] = $data['first_name'];
}
if (!empty($data['last_name'])) {
    $userUpdates[] = 'last_name = ?';
    $params[] = $data['last_name'];
}
if (!empty($userUpdates)) {
    $params[] = $userId;
    $sql = 'UPDATE users SET ' . implode(', ', $userUpdates) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo "Updated users table\n";
}

// Update profile
$profileUpdates = [];
$p = [];
if (!empty($data['date_of_birth'])) {
    $profileUpdates[] = 'date_of_birth = ?';
    $p[] = $data['date_of_birth'];
}

// State of origin
if (!empty($data['origin_state'])) {
    // Check if column exists
    $chk = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'job_seeker_profiles' AND COLUMN_NAME = 'state_of_origin'");
    $chk->execute();
    if ($chk->fetchColumn() > 0) {
        $profileUpdates[] = 'state_of_origin = ?';
        $p[] = $data['origin_state'];
    }
} elseif (!empty($data['residence_state'])) {
    $chk = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'job_seeker_profiles' AND COLUMN_NAME = 'state_of_origin'");
    $chk->execute();
    if ($chk->fetchColumn() > 0) {
        $profileUpdates[] = 'state_of_origin = ?';
        $p[] = $data['residence_state'];
    }
}

// LGA of origin
if (!empty($data['origin_lga'])) {
    $chk = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'job_seeker_profiles' AND COLUMN_NAME = 'lga_of_origin'");
    $chk->execute();
    if ($chk->fetchColumn() > 0) {
        $profileUpdates[] = 'lga_of_origin = ?';
        $p[] = $data['origin_lga'];
    }
} elseif (!empty($data['residence_lga'])) {
    $chk = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'job_seeker_profiles' AND COLUMN_NAME = 'lga_of_origin'");
    $chk->execute();
    if ($chk->fetchColumn() > 0) {
        $profileUpdates[] = 'lga_of_origin = ?';
        $p[] = $data['residence_lga'];
    }
}

// City of birth
$cityOfBirth = $data['birth_lga'] ?? $data['birthlga'] ?? $data['origin_lga'] ?? $data['place_of_birth'] ?? null;
if (!empty($cityOfBirth)) {
    $chk = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'job_seeker_profiles' AND COLUMN_NAME = 'city_of_birth'");
    $chk->execute();
    if ($chk->fetchColumn() > 0) {
        $profileUpdates[] = 'city_of_birth = ?';
        $p[] = $cityOfBirth;
    } else {
        echo "City of birth column not present, skipping\n";
    }
}

// Religion - check multiple field names
$religion = $data['religion'] ?? $data['Religion'] ?? null;
if (!empty($religion)) {
    $chk = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'job_seeker_profiles' AND COLUMN_NAME = 'religion'");
    $chk->execute();
    if ($chk->fetchColumn() > 0) {
        $profileUpdates[] = 'religion = ?';
        $p[] = $religion;
    } else {
        echo "Religion column not present, skipping\n";
    }
}

if (!empty($profileUpdates)) {
    $p[] = $userId;
    $sql = 'UPDATE job_seeker_profiles SET ' . implode(', ', $profileUpdates) . ' WHERE user_id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($p);
    echo "Updated job_seeker_profiles\n";
}

echo "Done.\n";

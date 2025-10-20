<?php
require_once 'config/database.php';

try {
    $stmt = $pdo->query('DESCRIBE job_applications');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Current job_applications table structure:\n\n";
    foreach ($columns as $col) {
        echo $col['Field'] . " - " . $col['Type'] . " - Default: " . ($col['Default'] ?? 'NULL') . "\n";
    }
    
    echo "\n\nSample data:\n";
    $stmt = $pdo->query('SELECT * FROM job_applications LIMIT 3');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($rows);
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}

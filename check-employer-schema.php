<?php
require_once 'config/database.php';

echo "<h2>Employer Profiles Table Structure</h2>";
echo "<pre>";

try {
    $stmt = $pdo->query("DESCRIBE employer_profiles");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Column Name          | Type                | Null | Key | Default\n";
    echo "-------------------- | ------------------- | ---- | --- | -------\n";
    
    foreach ($columns as $col) {
        printf("%-20s | %-19s | %-4s | %-3s | %s\n", 
            $col['Field'], 
            $col['Type'], 
            $col['Null'], 
            $col['Key'], 
            $col['Default'] ?? 'NULL'
        );
    }
    
    echo "\n\n";
    echo "Sample employer profile data:\n";
    echo "-----------------------------\n";
    
    $stmt = $pdo->query("SELECT * FROM employer_profiles LIMIT 1");
    $sample = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($sample) {
        foreach ($sample as $key => $value) {
            echo "$key: " . ($value ?? 'NULL') . "\n";
        }
    } else {
        echo "No employer profiles found.\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

echo "</pre>";

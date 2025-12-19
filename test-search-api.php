<?php
// Test the search API
require_once '../config/database.php';

$query = isset($_GET['q']) ? $_GET['q'] : 'OLUFEMI';
$type = isset($_GET['type']) ? $_GET['type'] : 'job_seekers';

echo "Testing search with query: $query, type: $type\n\n";

// Include the search.php file
$_GET['q'] = $query;
$_GET['type'] = $type;

ob_start();
include 'api/search.php';
$output = ob_get_clean();

echo "API Response:\n";
echo $output;
?>

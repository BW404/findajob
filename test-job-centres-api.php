<?php
/**
 * Test Job Centres API
 * Quick test to see what the API returns
 */

require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/functions.php';

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Job Centres API Test</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; }
        h2 { color: #dc2626; }
        pre { background: #f9fafb; padding: 15px; border-radius: 4px; overflow-x: auto; }
        .success { color: #059669; }
        .error { color: #dc2626; }
    </style>
</head>
<body>
    <h1>🧪 Job Centres API Test</h1>

    <div class="section">
        <h2>1. Database Check</h2>
        <?php
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM job_centres WHERE is_active = 1");
            $total = $stmt->fetch()['total'];
            echo "<p class='success'>✓ Total active centres in database: <strong>$total</strong></p>";
            
            $stmt = $pdo->query("SELECT id, name, is_active, state, city FROM job_centres ORDER BY id");
            $centres = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<pre>" . print_r($centres, true) . "</pre>";
        } catch (Exception $e) {
            echo "<p class='error'>✗ Database error: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>

    <div class="section">
        <h2>2. API Response (action=list)</h2>
        <?php
        // Simulate API call
        $_GET['action'] = 'list';
        $_GET['page'] = 1;
        
        ob_start();
        include 'api/job-centres.php';
        $response = ob_get_clean();
        
        $data = json_decode($response, true);
        
        if ($data) {
            echo "<p class='success'>✓ API returned valid JSON</p>";
            echo "<p><strong>Success:</strong> " . ($data['success'] ? 'true' : 'false') . "</p>";
            echo "<p><strong>Centres count:</strong> " . (isset($data['centres']) ? count($data['centres']) : 0) . "</p>";
            
            if (isset($data['pagination'])) {
                echo "<p><strong>Pagination:</strong></p>";
                echo "<pre>" . print_r($data['pagination'], true) . "</pre>";
            }
            
            echo "<p><strong>Full Response:</strong></p>";
            echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
        } else {
            echo "<p class='error'>✗ API returned invalid JSON</p>";
            echo "<pre>$response</pre>";
        }
        ?>
    </div>

    <div class="section">
        <h2>3. JavaScript Fetch Test</h2>
        <button onclick="testAPI()">Run Fetch Test</button>
        <div id="fetchResult"></div>
        
        <script>
        async function testAPI() {
            const resultDiv = document.getElementById('fetchResult');
            resultDiv.innerHTML = '<p>Loading...</p>';
            
            try {
                const response = await fetch('/findajob/api/job-centres.php?action=list&page=1');
                const data = await response.json();
                
                resultDiv.innerHTML = `
                    <p class="success">✓ Fetch successful</p>
                    <p><strong>Success:</strong> ${data.success}</p>
                    <p><strong>Centres count:</strong> ${data.centres ? data.centres.length : 0}</p>
                    <p><strong>Total in DB:</strong> ${data.pagination ? data.pagination.total : 'N/A'}</p>
                    <p><strong>Full Response:</strong></p>
                    <pre>${JSON.stringify(data, null, 2)}</pre>
                `;
            } catch (error) {
                resultDiv.innerHTML = `<p class="error">✗ Error: ${error.message}</p>`;
            }
        }
        </script>
    </div>

    <div class="section">
        <h2>4. SQL Query Test</h2>
        <?php
        $page = 1;
        $per_page = 12;
        $offset = 0;
        
        $sql = "
            SELECT 
                jc.*,
                (SELECT COUNT(*) FROM job_centre_bookmarks WHERE job_centre_id = jc.id) as bookmark_count
            FROM job_centres jc
            WHERE is_active = 1
            ORDER BY rating_avg DESC, rating_count DESC
            LIMIT $per_page OFFSET $offset
        ";
        
        echo "<p><strong>SQL Query:</strong></p>";
        echo "<pre>$sql</pre>";
        
        try {
            $stmt = $pdo->query($sql);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<p class='success'>✓ Query executed successfully</p>";
            echo "<p><strong>Rows returned:</strong> " . count($results) . "</p>";
            echo "<pre>" . print_r($results, true) . "</pre>";
        } catch (Exception $e) {
            echo "<p class='error'>✗ Query error: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>

</body>
</html>

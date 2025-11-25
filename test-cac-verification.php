<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test CAC Verification - FindAJob</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: #f3f4f6;
        }
        .container {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1f2937;
            margin-bottom: 1rem;
        }
        .test-section {
            margin: 2rem 0;
            padding: 1.5rem;
            background: #f9fafb;
            border-radius: 8px;
            border-left: 4px solid #dc2626;
        }
        .test-input {
            width: 100%;
            padding: 0.75rem;
            margin: 0.5rem 0;
            border: 2px solid #d1d5db;
            border-radius: 6px;
            font-size: 1rem;
        }
        .test-button {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: white;
            padding: 0.875rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 1rem;
            margin-top: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .test-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(220, 38, 38, 0.3);
        }
        .result {
            margin-top: 1.5rem;
            padding: 1rem;
            border-radius: 6px;
            font-family: monospace;
            font-size: 0.875rem;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .success {
            background: #d1fae5;
            border: 1px solid #a7f3d0;
            color: #059669;
        }
        .error {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #dc2626;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
        }
        select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #d1d5db;
            border-radius: 6px;
            font-size: 1rem;
            margin: 0.5rem 0;
        }
        .info-box {
            background: #dbeafe;
            border: 1px solid #93c5fd;
            color: #1e40af;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-building"></i> CAC Verification Test</h1>
        <p style="color: #6b7280; margin-bottom: 2rem;">
            Test the Dojah CAC verification API integration
        </p>

        <div class="info-box">
            <strong><i class="fas fa-info-circle"></i> Test Data (Sandbox):</strong><br>
            <strong>Company Name:</strong> JOHN DOE LIMITED<br>
            <strong>RC Number:</strong> 1234567<br>
            <strong>Company Type:</strong> BUSINESS_NAME
        </div>

        <div class="test-section">
            <h3 style="margin-top: 0;">Test CAC Verification</h3>
            
            <label for="companyName">Company Name *</label>
            <input type="text" id="companyName" class="test-input" 
                   placeholder="Enter company name" 
                   value="JOHN DOE LIMITED">
            
            <label for="rcNumber">RC Number *</label>
            <input type="text" id="rcNumber" class="test-input" 
                   placeholder="e.g., RC1234567" 
                   value="1234567">
            
            <label for="companyType">Company Type *</label>
            <select id="companyType" class="test-input">
                <option value="">Select company type</option>
                <option value="BUSINESS_NAME" selected>Business Name</option>
                <option value="COMPANY">Company (Limited)</option>
                <option value="INCORPORATED_TRUSTEES">Incorporated Trustees</option>
                <option value="LIMITED_PARTNERSHIP">Limited Partnership</option>
                <option value="LIMITED_LIABILITY_PARTNERSHIP">Limited Liability Partnership</option>
            </select>
            
            <button class="test-button" onclick="testCACVerification()">
                <i class="fas fa-check-circle"></i> Test Verification
            </button>
            
            <div id="result"></div>
        </div>

        <div class="test-section">
            <h3 style="margin-top: 0;">Direct API Test</h3>
            <p style="color: #6b7280; font-size: 0.875rem; margin-bottom: 1rem;">
                Test the Dojah API directly without name matching
            </p>
            
            <label for="directRcNumber">RC Number *</label>
            <input type="text" id="directRcNumber" class="test-input" 
                   placeholder="e.g., 1234567" 
                   value="1234567">
            
            <label for="directCompanyType">Company Type *</label>
            <select id="directCompanyType" class="test-input">
                <option value="">Select company type</option>
                <option value="BUSINESS_NAME" selected>Business Name</option>
                <option value="COMPANY">Company (Limited)</option>
                <option value="INCORPORATED_TRUSTEES">Incorporated Trustees</option>
                <option value="LIMITED_PARTNERSHIP">Limited Partnership</option>
                <option value="LIMITED_LIABILITY_PARTNERSHIP">Limited Liability Partnership</option>
            </select>
            
            <button class="test-button" onclick="testDirectAPI()">
                <i class="fas fa-code"></i> Test Direct API
            </button>
            
            <div id="directResult"></div>
        </div>
    </div>

    <script>
        async function testCACVerification() {
            const companyName = document.getElementById('companyName').value.trim();
            const rcNumber = document.getElementById('rcNumber').value.trim();
            const companyType = document.getElementById('companyType').value;
            const resultDiv = document.getElementById('result');

            if (!companyName || !rcNumber || !companyType) {
                resultDiv.innerHTML = '<div class="result error">Please fill in all fields</div>';
                return;
            }

            resultDiv.innerHTML = '<div class="result" style="background: #fef3c7; border: 1px solid #fcd34d; color: #92400e;"><i class="fas fa-spinner fa-spin"></i> Verifying CAC details...</div>';

            try {
                const response = await fetch('/findajob/api/verify-cac.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'verify_cac',
                        company_name: companyName,
                        rc_number: rcNumber,
                        company_type: companyType
                    })
                });

                const data = await response.json();

                if (data.success) {
                    resultDiv.innerHTML = `
                        <div class="result success">
                            <strong>✓ Verification Successful!</strong><br><br>
                            ${JSON.stringify(data, null, 2)}
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="result error">
                            <strong>✗ Verification Failed</strong><br><br>
                            ${JSON.stringify(data, null, 2)}
                        </div>
                    `;
                }
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="result error">
                        <strong>✗ Network Error</strong><br><br>
                        ${error.message}
                    </div>
                `;
            }
        }

        async function testDirectAPI() {
            const rcNumber = document.getElementById('directRcNumber').value.trim();
            const companyType = document.getElementById('directCompanyType').value;
            const resultDiv = document.getElementById('directResult');

            if (!rcNumber || !companyType) {
                resultDiv.innerHTML = '<div class="result error">Please fill in all fields</div>';
                return;
            }

            resultDiv.innerHTML = '<div class="result" style="background: #fef3c7; border: 1px solid #fcd34d; color: #92400e;"><i class="fas fa-spinner fa-spin"></i> Calling Dojah API...</div>';

            try {
                const url = `https://sandbox.dojah.io/api/v1/kyc/cac/basic?rc_number=${rcNumber}&company_type=${companyType}`;
                
                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'AppId': '68e170175ab6888b2b8c0f71',
                        'Authorization': 'test_sk_3i97kuOmiq11lP3XA5FlkVZkG',
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();

                if (response.ok) {
                    resultDiv.innerHTML = `
                        <div class="result success">
                            <strong>✓ API Response (HTTP ${response.status})</strong><br><br>
                            ${JSON.stringify(data, null, 2)}
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="result error">
                            <strong>✗ API Error (HTTP ${response.status})</strong><br><br>
                            ${JSON.stringify(data, null, 2)}
                        </div>
                    `;
                }
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="result error">
                        <strong>✗ Network Error</strong><br><br>
                        ${error.message}<br><br>
                        Note: CORS may block direct browser requests. Use the PHP API test above instead.
                    </div>
                `;
            }
        }
    </script>
</body>
</html>

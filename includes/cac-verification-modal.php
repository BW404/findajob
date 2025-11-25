<!-- CAC Verification Modal -->
<div id="cacVerificationModal" class="cac-modal">
    <div class="cac-modal-content">
        <div class="cac-modal-header">
            <h3><i class="fas fa-building"></i> Verify Company CAC</h3>
            <button class="cac-modal-close" onclick="closeCACModal()">&times;</button>
        </div>
        <div class="cac-modal-body">
            <div id="cacAlert" class="cac-alert"></div>
            
            <!-- Step 1: Enter CAC Details -->
            <div id="cacStep1" class="cac-step active">
                <p style="margin-bottom: 1.5rem; color: #6b7280;">
                    Verify your company registration with the Corporate Affairs Commission (CAC).
                </p>
                
                <div class="cac-form-group">
                    <label for="cacCompanyName">Company Name *</label>
                    <input type="text" id="cacCompanyName" class="cac-input" 
                           placeholder="Enter your company name"
                           value="<?php echo htmlspecialchars($user['company_name'] ?? ''); ?>">
                    <small style="color: #6b7280;">As registered with CAC</small>
                </div>
                
                <div class="cac-form-group">
                    <label for="cacRcNumber">RC Number *</label>
                    <input type="text" id="cacRcNumber" class="cac-input" 
                           placeholder="e.g., RC1234567"
                           value="<?php echo htmlspecialchars($user['company_cac_number'] ?? ''); ?>">
                    <small style="color: #6b7280;">Registration/Business Number</small>
                </div>
                
                <div class="cac-form-group">
                    <label for="cacCompanyType">Company Type *</label>
                    <select id="cacCompanyType" class="cac-input">
                        <option value="">Select company type</option>
                        <option value="BUSINESS_NAME">Business Name</option>
                        <option value="COMPANY">Company (Limited)</option>
                        <option value="INCORPORATED_TRUSTEES">Incorporated Trustees</option>
                        <option value="LIMITED_PARTNERSHIP">Limited Partnership</option>
                        <option value="LIMITED_LIABILITY_PARTNERSHIP">Limited Liability Partnership</option>
                    </select>
                    <small style="color: #6b7280;">Select your business structure</small>
                </div>
                
                <div class="cac-fee-notice">
                    <i class="fas fa-info-circle"></i>
                    <span>CAC verification is <strong>FREE</strong></span>
                </div>
                
                <button onclick="verifyCACDetails()" class="btn-cac-verify" id="verifyCACBtn">
                    <i class="fas fa-check-circle"></i> Verify CAC Details
                </button>
            </div>
            
            <!-- Step 2: Verification Success -->
            <div id="cacStep2" class="cac-step">
                <div style="text-align: center; padding: 2rem 0;">
                    <i class="fas fa-check-circle" style="font-size: 4rem; color: #10b981; margin-bottom: 1rem;"></i>
                    <h3 style="color: #10b981; margin-bottom: 1rem;">CAC Verified!</h3>
                    <p style="color: #6b7280; margin-bottom: 1.5rem;">Your company details have been successfully verified.</p>
                    
                    <div id="cacVerifiedDetails" style="background: #f9fafb; padding: 1.5rem; border-radius: 8px; text-align: left; margin-bottom: 1.5rem;">
                        <!-- Verification details will be populated here -->
                    </div>
                </div>
                
                <button onclick="closeCACModal(); location.reload();" class="btn-cac-verify">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    /* CAC Verification Modal */
    .cac-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        z-index: 10000;
        align-items: center;
        justify-content: center;
        animation: fadeIn 0.2s ease;
    }
    
    .cac-modal.active {
        display: flex;
    }
    
    .cac-modal-content {
        background: white;
        border-radius: 16px;
        width: 90%;
        max-width: 550px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
        animation: slideInUp 0.3s ease;
    }
    
    .cac-modal-header {
        padding: 1.5rem;
        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        color: white;
        border-radius: 16px 16px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .cac-modal-header h3 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .cac-modal-close {
        background: none;
        border: none;
        color: white;
        font-size: 1.75rem;
        cursor: pointer;
        line-height: 1;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: background 0.2s;
    }
    
    .cac-modal-close:hover {
        background: rgba(255, 255, 255, 0.2);
    }
    
    .cac-modal-body {
        padding: 2rem;
    }
    
    .cac-step {
        display: none;
    }
    
    .cac-step.active {
        display: block;
    }
    
    .cac-alert {
        padding: 0.875rem 1rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        display: none;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.875rem;
    }
    
    .cac-alert.active {
        display: flex;
    }
    
    .cac-alert-error {
        background: #fee2e2;
        color: #dc2626;
        border: 1px solid #fecaca;
    }
    
    .cac-alert-success {
        background: #d1fae5;
        color: #059669;
        border: 1px solid #a7f3d0;
    }
    
    .cac-form-group {
        margin-bottom: 1.5rem;
    }
    
    .cac-form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: #374151;
        font-size: 0.875rem;
    }
    
    .cac-input {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid #d1d5db;
        border-radius: 8px;
        font-size: 1rem;
        transition: all 0.2s;
    }
    
    .cac-input:focus {
        outline: none;
        border-color: #dc2626;
        box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
    }
    
    .cac-fee-notice {
        background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #059669;
        font-size: 0.875rem;
    }
    
    .cac-fee-notice i {
        font-size: 1.25rem;
    }
    
    .btn-cac-verify {
        width: 100%;
        padding: 0.875rem 1.5rem;
        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }
    
    .btn-cac-verify:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(220, 38, 38, 0.3);
    }
    
    .btn-cac-verify:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes slideInUp {
        from {
            transform: translateY(100px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    @media (max-width: 640px) {
        .cac-modal-content {
            width: 95%;
            max-height: 95vh;
        }
        
        .cac-modal-body {
            padding: 1.5rem;
        }
    }
</style>

<script>
    function openCACModal() {
        document.getElementById('cacVerificationModal').classList.add('active');
        showCACStep(1);
    }
    
    function closeCACModal() {
        document.getElementById('cacVerificationModal').classList.remove('active');
    }
    
    function showCACStep(step) {
        document.querySelectorAll('.cac-step').forEach(el => el.classList.remove('active'));
        document.getElementById('cacStep' + step).classList.add('active');
        hideCACAlert();
    }
    
    function showCACAlert(message, type = 'error') {
        const alert = document.getElementById('cacAlert');
        alert.textContent = message;
        alert.className = 'cac-alert active cac-alert-' + type;
    }
    
    function hideCACAlert() {
        document.getElementById('cacAlert').classList.remove('active');
    }
    
    async function verifyCACDetails() {
        const companyName = document.getElementById('cacCompanyName').value.trim();
        const rcNumber = document.getElementById('cacRcNumber').value.trim();
        const companyType = document.getElementById('cacCompanyType').value;
        
        // Validation
        if (!companyName) {
            showCACAlert('Please enter your company name');
            return;
        }
        
        if (!rcNumber) {
            showCACAlert('Please enter your RC Number');
            return;
        }
        
        if (!companyType) {
            showCACAlert('Please select your company type');
            return;
        }
        
        const btn = document.getElementById('verifyCACBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
        
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
                // Display verification details
                const details = data.data;
                const detailsHTML = `
                    <div style="display: grid; gap: 0.75rem;">
                        <div>
                            <strong style="color: #6b7280; font-size: 0.875rem;">Company Name:</strong>
                            <div style="font-weight: 600; color: #1f2937;">${details.company_name}</div>
                        </div>
                        <div>
                            <strong style="color: #6b7280; font-size: 0.875rem;">RC Number:</strong>
                            <div style="font-weight: 600; color: #1f2937;">${details.rc_number}</div>
                        </div>
                        <div>
                            <strong style="color: #6b7280; font-size: 0.875rem;">Company Type:</strong>
                            <div style="font-weight: 600; color: #1f2937;">${details.type_of_company}</div>
                        </div>
                        <div>
                            <strong style="color: #6b7280; font-size: 0.875rem;">Status:</strong>
                            <div style="font-weight: 600; color: ${details.status === 'Active' ? '#059669' : '#dc2626'};">${details.status}</div>
                        </div>
                        ${details.address ? `
                        <div>
                            <strong style="color: #6b7280; font-size: 0.875rem;">Address:</strong>
                            <div style="color: #1f2937;">${details.address}</div>
                        </div>
                        ` : ''}
                        ${details.date_of_registration ? `
                        <div>
                            <strong style="color: #6b7280; font-size: 0.875rem;">Registration Date:</strong>
                            <div style="color: #1f2937;">${new Date(details.date_of_registration).toLocaleDateString()}</div>
                        </div>
                        ` : ''}
                    </div>
                `;
                
                document.getElementById('cacVerifiedDetails').innerHTML = detailsHTML;
                showCACStep(2);
            } else {
                showCACAlert(data.error || 'CAC verification failed');
            }
        } catch (error) {
            console.error('CAC verification error:', error);
            showCACAlert('Network error. Please try again.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-circle"></i> Verify CAC Details';
        }
    }
    
    // Allow Enter key to submit
    document.addEventListener('DOMContentLoaded', () => {
        const inputs = ['cacCompanyName', 'cacRcNumber'];
        inputs.forEach(id => {
            document.getElementById(id)?.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') verifyCACDetails();
            });
        });
    });
</script>

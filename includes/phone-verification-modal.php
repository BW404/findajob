<!-- Phone Verification Modal -->
<div id="phoneVerificationModal" class="phone-modal">
    <div class="phone-modal-content">
        <div class="phone-modal-header">
            <h3><i class="fas fa-mobile-alt"></i> Verify Phone Number</h3>
            <button class="phone-modal-close" onclick="closePhoneModal()">&times;</button>
        </div>
        <div class="phone-modal-body">
            <div id="phoneAlert" class="phone-alert"></div>
            
            <!-- Step 1: Enter Phone Number -->
            <div id="phoneStep1" class="phone-step active">
                <p style="margin-bottom: 1.5rem; color: #6b7280;">
                    We'll send a 6-digit code to your phone number via SMS.
                </p>
                
                <div class="phone-form-group">
                    <label for="phoneNumber">Phone Number</label>
                    <input type="tel" id="phoneNumber" class="phone-input" 
                           placeholder="e.g., 08012345678 or 2348012345678"
                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    <small style="color: #6b7280;">Nigerian mobile number only</small>
                </div>
                
                <button onclick="sendPhoneOTP()" class="btn-phone-verify">
                    <i class="fas fa-paper-plane"></i> Send Verification Code
                </button>
            </div>
            
            <!-- Step 2: Enter OTP -->
            <div id="phoneStep2" class="phone-step">
                <p style="margin-bottom: 1.5rem; color: #6b7280;">
                    Enter the 6-digit code sent to <strong id="phoneDisplay"></strong>
                </p>
                
                <div class="phone-form-group">
                    <label for="otpCode">Verification Code</label>
                    <input type="text" id="otpCode" class="phone-input" 
                           placeholder="Enter 6-digit code" maxlength="6"
                           style="font-size: 1.5rem; text-align: center; letter-spacing: 0.5rem;">
                </div>
                
                <button onclick="verifyPhoneOTP()" class="btn-phone-verify">
                    <i class="fas fa-check-circle"></i> Verify Code
                </button>
                
                <div style="margin-top: 1rem; text-align: center;">
                    <button onclick="showResendOptions()" class="btn-resend" id="resendBtn" disabled>
                        <i class="fas fa-redo"></i> Resend Code <span id="resendTimer">(60s)</span>
                    </button>
                    
                    <!-- Resend Options Dropdown -->
                    <div id="resendOptions" style="display: none; margin-top: 0.5rem; padding: 0.75rem; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb;">
                        <p style="font-size: 0.875rem; color: #6b7280; margin-bottom: 0.5rem; font-weight: 500;">Choose delivery method:</p>
                        <div style="display: flex; gap: 0.5rem; justify-content: center;">
                            <button onclick="resendPhoneOTP('sms')" class="btn-channel-option">
                                <i class="fas fa-comment"></i> SMS
                            </button>
                            <button onclick="resendPhoneOTP('voice')" class="btn-channel-option">
                                <i class="fas fa-phone"></i> Voice Call
                            </button>
                        </div>
                    </div>
                    
                    <button onclick="changePhoneNumber()" class="btn-change-phone">
                        <i class="fas fa-edit"></i> Change Phone Number
                    </button>
                </div>
            </div>
            
            <!-- Step 3: Success -->
            <div id="phoneStep3" class="phone-step">
                <div style="text-align: center; padding: 2rem 0;">
                    <i class="fas fa-check-circle" style="font-size: 4rem; color: #10b981; margin-bottom: 1rem;"></i>
                    <h3 style="color: #10b981; margin-bottom: 0.5rem;">Phone Verified!</h3>
                    <p style="color: #6b7280;">Your phone number has been successfully verified.</p>
                </div>
                
                <button onclick="closePhoneModal(); location.reload();" class="btn-phone-verify">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Phone Verification Modal */
    .phone-modal {
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
    
    .phone-modal.active {
        display: flex;
    }
    
    .phone-modal-content {
        background: white;
        border-radius: 16px;
        width: 90%;
        max-width: 500px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
        animation: slideInUp 0.3s ease;
    }
    
    .phone-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1.5rem;
        border-bottom: 2px solid #e5e7eb;
        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        color: white;
        border-radius: 16px 16px 0 0;
    }
    
    .phone-modal-header h3 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .phone-modal-close {
        background: none;
        border: none;
        color: white;
        font-size: 2rem;
        cursor: pointer;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: background 0.2s;
    }
    
    .phone-modal-close:hover {
        background: rgba(255, 255, 255, 0.2);
    }
    
    .phone-modal-body {
        padding: 2rem;
    }
    
    .phone-step {
        display: none;
    }
    
    .phone-step.active {
        display: block;
    }
    
    .phone-alert {
        display: none;
        padding: 0.875rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        font-size: 0.875rem;
    }
    
    .phone-alert.active {
        display: block;
    }
    
    .phone-alert-success {
        background: #d1fae5;
        border: 1px solid #10b981;
        color: #065f46;
    }
    
    .phone-alert-error {
        background: #fee2e2;
        border: 1px solid #dc2626;
        color: #991b1b;
    }
    
    .phone-form-group {
        margin-bottom: 1.5rem;
    }
    
    .phone-form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: #374151;
        font-size: 0.875rem;
    }
    
    .phone-input {
        width: 100%;
        padding: 0.75rem;
        border: 2px solid #d1d5db;
        border-radius: 8px;
        font-size: 1rem;
        transition: all 0.2s;
    }
    
    .phone-input:focus {
        outline: none;
        border-color: #dc2626;
        box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
    }
    
    .btn-phone-verify {
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
    
    .btn-phone-verify:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(220, 38, 38, 0.3);
    }
    
    .btn-phone-verify:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }
    
    .btn-resend, .btn-change-phone {
        background: none;
        border: none;
        color: #dc2626;
        font-weight: 500;
        cursor: pointer;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        transition: background 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.875rem;
    }
    
    .btn-resend:hover:not(:disabled), .btn-change-phone:hover {
        background: #fee2e2;
    }
    
    .btn-resend:disabled {
        color: #9ca3af;
        cursor: not-allowed;
    }
    
    .btn-channel-option {
        flex: 1;
        padding: 0.625rem 1rem;
        background: white;
        border: 2px solid #dc2626;
        color: #dc2626;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        font-size: 0.875rem;
    }
    
    .btn-channel-option:hover {
        background: #dc2626;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2);
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
</style>

<script>
    let phoneReferenceId = '';
    let phoneNumberToVerify = '';
    let resendTimer = null;
    
    function openPhoneModal() {
        document.getElementById('phoneVerificationModal').classList.add('active');
        showPhoneStep(1);
    }
    
    function closePhoneModal() {
        document.getElementById('phoneVerificationModal').classList.remove('active');
        clearInterval(resendTimer);
    }
    
    function showPhoneStep(step) {
        document.querySelectorAll('.phone-step').forEach(el => el.classList.remove('active'));
        document.getElementById('phoneStep' + step).classList.add('active');
        hidePhoneAlert();
    }
    
    function showPhoneAlert(message, type = 'error') {
        const alert = document.getElementById('phoneAlert');
        alert.textContent = message;
        alert.className = 'phone-alert active phone-alert-' + type;
    }
    
    function hidePhoneAlert() {
        document.getElementById('phoneAlert').classList.remove('active');
    }
    
    async function sendPhoneOTP(channel = 'sms') {
        const phoneInput = document.getElementById('phoneNumber');
        const phone = phoneInput.value.trim();
        
        if (!phone) {
            showPhoneAlert('Please enter your phone number');
            return;
        }
        
        const btn = event.target;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
        
        try {
            const response = await fetch('/findajob/api/verify-phone.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'send_otp',
                    phone_number: phone,
                    channel: channel
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                phoneReferenceId = data.reference_id;
                phoneNumberToVerify = phone;
                document.getElementById('phoneDisplay').textContent = data.phone;
                document.getElementById('resendOptions').style.display = 'none'; // Hide options after send
                showPhoneStep(2);
                startResendTimer();
                const channelText = channel === 'voice' ? 'voice call' : 'SMS';
                showPhoneAlert(data.message || `OTP sent via ${channelText}`, 'success');
            } else {
                showPhoneAlert(data.error || 'Failed to send OTP');
            }
        } catch (error) {
            console.error('Send OTP error:', error);
            showPhoneAlert('Network error. Please try again.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Verification Code';
        }
    }
    
    async function verifyPhoneOTP() {
        const code = document.getElementById('otpCode').value.trim();
        
        if (code.length !== 6) {
            showPhoneAlert('Please enter the 6-digit code');
            return;
        }
        
        const btn = event.target;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
        
        try {
            const response = await fetch('/findajob/api/verify-phone.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'verify_otp',
                    code: code,
                    reference_id: phoneReferenceId,
                    phone_number: phoneNumberToVerify
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                showPhoneStep(3);
                clearInterval(resendTimer);
            } else {
                showPhoneAlert(data.error || 'Invalid verification code');
            }
        } catch (error) {
            console.error('Verify OTP error:', error);
            showPhoneAlert('Network error. Please try again.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-circle"></i> Verify Code';
        }
    }
    
    function resendPhoneOTP(channel) {
        // Hide the options dropdown after selection
        document.getElementById('resendOptions').style.display = 'none';
        
        // Use the selected channel (sms or voice)
        const phoneInput = document.getElementById('phoneNumber');
        phoneInput.value = phoneNumberToVerify; // Ensure we use the same number
        sendPhoneOTP(channel);
    }
    
    function showResendOptions() {
        const optionsDiv = document.getElementById('resendOptions');
        if (optionsDiv.style.display === 'none') {
            optionsDiv.style.display = 'block';
        } else {
            optionsDiv.style.display = 'none';
        }
    }
    
    function changePhoneNumber() {
        showPhoneStep(1);
        clearInterval(resendTimer);
        document.getElementById('otpCode').value = '';
        document.getElementById('resendOptions').style.display = 'none';
    }
    
    function startResendTimer() {
        let seconds = 60;
        const resendBtn = document.getElementById('resendBtn');
        const timerDisplay = document.getElementById('resendTimer');
        
        resendBtn.disabled = true;
        
        resendTimer = setInterval(() => {
            seconds--;
            timerDisplay.textContent = `(${seconds}s)`;
            
            if (seconds <= 0) {
                clearInterval(resendTimer);
                resendBtn.disabled = false;
                timerDisplay.textContent = '';
            }
        }, 1000);
    }
    
    // Allow Enter key to submit
    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('phoneNumber')?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') sendPhoneOTP();
        });
        
        document.getElementById('otpCode')?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') verifyPhoneOTP();
        });
        
        // Auto-focus OTP input and format
        document.getElementById('otpCode')?.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
        });
    });
</script>

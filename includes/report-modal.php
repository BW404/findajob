<!-- Report Modal - Reusable component for reporting issues to admin -->
<style>
.report-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    z-index: 10000;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    backdrop-filter: blur(4px);
}

.report-modal.active {
    display: flex;
}

.report-modal-content {
    background: white;
    border-radius: 16px;
    max-width: 600px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.report-modal-header {
    background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
    color: white;
    padding: 1.5rem 2rem;
    border-radius: 16px 16px 0 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.report-modal-header h3 {
    margin: 0;
    font-size: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.report-modal-close {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    transition: all 0.3s;
}

.report-modal-close:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: rotate(90deg);
}

.report-modal-body {
    padding: 2rem;
}

.report-info-box {
    background: #fef3c7;
    border-left: 4px solid #f59e0b;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    font-size: 0.95rem;
    line-height: 1.6;
}

.report-form-group {
    margin-bottom: 1.5rem;
}

.report-form-group label {
    display: block;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.5rem;
}

.report-form-group .required {
    color: #dc2626;
}

.report-form-group select,
.report-form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 1rem;
    font-family: inherit;
    transition: all 0.3s;
}

.report-form-group select:focus,
.report-form-group textarea:focus {
    outline: none;
    border-color: #dc2626;
    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
}

.report-form-group textarea {
    resize: vertical;
    min-height: 120px;
}

.report-char-count {
    text-align: right;
    font-size: 0.85rem;
    color: #6b7280;
    margin-top: 0.25rem;
}

.report-reason-help {
    font-size: 0.9rem;
    color: #6b7280;
    margin-top: 0.5rem;
    font-style: italic;
}

.report-modal-footer {
    padding: 1.5rem 2rem;
    background: #f9fafb;
    border-radius: 0 0 16px 16px;
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
}

.report-btn {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.report-btn-cancel {
    background: white;
    color: #374151;
    border: 2px solid #e5e7eb;
}

.report-btn-cancel:hover {
    background: #f9fafb;
}

.report-btn-submit {
    background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
    color: white;
}

.report-btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.4);
}

.report-btn-submit:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.report-success-message {
    display: none;
    background: #d1fae5;
    border-left: 4px solid #059669;
    padding: 1.5rem;
    border-radius: 8px;
    color: #065f46;
}

.report-success-message.show {
    display: block;
}

.report-success-message i {
    color: #059669;
    font-size: 1.5rem;
    margin-right: 0.75rem;
}

@media (max-width: 640px) {
    .report-modal-content {
        max-height: 95vh;
    }
    
    .report-modal-header {
        padding: 1rem 1.5rem;
    }
    
    .report-modal-header h3 {
        font-size: 1.25rem;
    }
    
    .report-modal-body {
        padding: 1.5rem;
    }
    
    .report-modal-footer {
        flex-direction: column;
        padding: 1rem 1.5rem;
    }
    
    .report-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>

<div id="reportModal" class="report-modal">
    <div class="report-modal-content">
        <div class="report-modal-header">
            <h3>
                <i class="fas fa-flag"></i>
                Report to Admin
            </h3>
            <button type="button" class="report-modal-close" onclick="closeReportModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="report-modal-body">
            <div class="report-info-box">
                <i class="fas fa-info-circle"></i>
                Your report will be reviewed by our admin team. Please provide detailed information to help us investigate the issue effectively.
            </div>
            
            <div id="reportSuccessMessage" class="report-success-message">
                <i class="fas fa-check-circle"></i>
                <strong>Report submitted successfully!</strong> Our admin team will review it shortly.
            </div>
            
            <form id="reportForm">
                <input type="hidden" id="reportEntityType" name="entity_type">
                <input type="hidden" id="reportEntityId" name="entity_id">
                
                <div class="report-form-group">
                    <label for="reportReason">
                        Reason for Report <span class="required">*</span>
                    </label>
                    <select id="reportReason" name="reason" required>
                        <option value="">-- Select a Reason --</option>
                        <option value="fake_profile">Fake Profile</option>
                        <option value="fake_job">Fake Job Posting</option>
                        <option value="inappropriate_content">Inappropriate Content</option>
                        <option value="harassment">Harassment or Bullying</option>
                        <option value="spam">Spam</option>
                        <option value="scam">Scam or Fraudulent Activity</option>
                        <option value="misleading_information">Misleading Information</option>
                        <option value="copyright_violation">Copyright Violation</option>
                        <option value="discrimination">Discrimination</option>
                        <option value="offensive_language">Offensive Language</option>
                        <option value="duplicate_posting">Duplicate Posting</option>
                        <option value="privacy_violation">Privacy Violation</option>
                        <option value="payment_issues">Payment Issues</option>
                        <option value="other">Other</option>
                    </select>
                    <div class="report-reason-help" id="reasonHelp"></div>
                </div>
                
                <div class="report-form-group">
                    <label for="reportDescription">
                        Detailed Description <span class="required">*</span>
                    </label>
                    <textarea 
                        id="reportDescription" 
                        name="description" 
                        required
                        minlength="10"
                        maxlength="2000"
                        placeholder="Please provide specific details about the issue you're reporting. Include dates, usernames, or any other relevant information that will help us investigate."
                    ></textarea>
                    <div class="report-char-count">
                        <span id="charCount">0</span> / 2000 characters (minimum 10)
                    </div>
                </div>
            </form>
        </div>
        
        <div class="report-modal-footer">
            <button type="button" class="report-btn report-btn-cancel" onclick="closeReportModal()">
                <i class="fas fa-times"></i>
                Cancel
            </button>
            <button type="button" class="report-btn report-btn-submit" id="submitReportBtn" onclick="submitReport()">
                <i class="fas fa-flag"></i>
                Submit Report
            </button>
        </div>
    </div>
</div>

<script>
let currentReportData = {};

// Reason help text
const reasonHelpTexts = {
    'fake_profile': 'Report profiles that appear to be fake or impersonating someone else',
    'fake_job': 'Report job postings that seem fraudulent or don\'t exist',
    'inappropriate_content': 'Report content that is offensive, explicit, or violates community standards',
    'harassment': 'Report bullying, threats, or harassment',
    'spam': 'Report unsolicited or repetitive messages',
    'scam': 'Report fraudulent activities or attempts to deceive users',
    'misleading_information': 'Report false or misleading claims',
    'copyright_violation': 'Report unauthorized use of copyrighted material',
    'discrimination': 'Report discriminatory behavior or content',
    'offensive_language': 'Report use of profanity or hate speech',
    'duplicate_posting': 'Report duplicate job postings or profiles',
    'privacy_violation': 'Report unauthorized sharing of private information',
    'payment_issues': 'Report payment-related problems or fraud',
    'other': 'Report any other issue not covered by the categories above'
};

// Open report modal
function openReportModal(entityType, entityId, entityName = '') {
    currentReportData = { entityType, entityId, entityName };
    
    document.getElementById('reportEntityType').value = entityType;
    document.getElementById('reportEntityId').value = entityId || '';
    document.getElementById('reportForm').reset();
    document.getElementById('reportSuccessMessage').classList.remove('show');
    document.getElementById('charCount').textContent = '0';
    document.getElementById('reasonHelp').textContent = '';
    
    const modal = document.getElementById('reportModal');
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

// Close report modal
function closeReportModal() {
    const modal = document.getElementById('reportModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
    currentReportData = {};
}

// Update character count
document.getElementById('reportDescription')?.addEventListener('input', function() {
    const count = this.value.length;
    document.getElementById('charCount').textContent = count;
    
    const charCountEl = document.getElementById('charCount').parentElement;
    if (count < 10) {
        charCountEl.style.color = '#dc2626';
    } else if (count > 1900) {
        charCountEl.style.color = '#f59e0b';
    } else {
        charCountEl.style.color = '#6b7280';
    }
});

// Update reason help text
document.getElementById('reportReason')?.addEventListener('change', function() {
    const helpText = reasonHelpTexts[this.value] || '';
    document.getElementById('reasonHelp').textContent = helpText;
});

// Submit report
async function submitReport() {
    const form = document.getElementById('reportForm');
    const submitBtn = document.getElementById('submitReportBtn');
    
    // Validate form
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const description = document.getElementById('reportDescription').value.trim();
    if (description.length < 10) {
        alert('Please provide a detailed description (at least 10 characters)');
        return;
    }
    
    // Disable submit button
    submitBtn.disabled = true;
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
    
    try {
        const formData = new FormData(form);
        formData.append('action', 'submit');
        
        const response = await fetch('/findajob/api/reports.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Show success message
            document.getElementById('reportSuccessMessage').classList.add('show');
            form.style.display = 'none';
            
            // Close modal after 3 seconds
            setTimeout(() => {
                closeReportModal();
                form.style.display = 'block';
            }, 3000);
        } else {
            alert('Error: ' + (data.error || 'Failed to submit report'));
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    } catch (error) {
        console.error('Report submission error:', error);
        alert('An error occurred. Please try again.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
}

// Close modal when clicking outside
document.getElementById('reportModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeReportModal();
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('reportModal').classList.contains('active')) {
        closeReportModal();
    }
});

// Handle report buttons with data attributes (event delegation)
document.addEventListener('click', function(e) {
    const reportBtn = e.target.closest('.report-trigger');
    if (reportBtn) {
        e.preventDefault();
        e.stopPropagation();
        const entityType = reportBtn.dataset.entityType;
        const entityId = reportBtn.dataset.entityId;
        const entityName = reportBtn.dataset.entityName || '';
        openReportModal(entityType, entityId, entityName);
    }
});
</script>

<?php
require_once '../../config/database.php';
require_once '../../config/session.php';

// Check if user is logged in and is an employer
if (!isLoggedIn() || !isEmployer()) {
    header('Location: ../auth/login.php');
    exit;
}

$userId = getCurrentUserId();

// Check if employer has Pro subscription
$stmt = $pdo->prepare("SELECT subscription_type, subscription_end FROM users WHERE id = ?");
$stmt->execute([$userId]);
$subscription = $stmt->fetch(PDO::FETCH_ASSOC);

$isPro = ($subscription['subscription_type'] === 'pro' && 
          (!$subscription['subscription_end'] || strtotime($subscription['subscription_end']) > time()));

if (!$isPro) {
    $_SESSION['upgrade_message'] = 'Private Job Offers are a Pro feature. Upgrade to send exclusive offers to candidates!';
    header('Location: ../payment/plans.php');
    exit;
}

// Get employer profile
$stmt = $pdo->prepare("
    SELECT u.*, ep.company_name 
    FROM users u
    LEFT JOIN employer_profiles ep ON u.id = ep.user_id
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Get job seeker ID if passed
$jobSeekerId = $_GET['job_seeker_id'] ?? null;
$jobSeekerData = null;

if ($jobSeekerId) {
    $stmt = $pdo->prepare("
        SELECT u.*, jsp.*
        FROM users u
        LEFT JOIN job_seeker_profiles jsp ON u.id = jsp.user_id
        WHERE u.id = ? AND u.user_type = 'job_seeker'
    ");
    $stmt->execute([$jobSeekerId]);
    $jobSeekerData = $stmt->fetch();
}

// Get Nigerian states
$stmt = $pdo->query("SELECT * FROM nigeria_states ORDER BY name");
$states = $stmt->fetchAll();

// Get categories
$categories = [
    'Technology', 'Healthcare', 'Finance', 'Education', 'Engineering',
    'Sales & Marketing', 'Customer Service', 'Administration', 'Manufacturing',
    'Construction', 'Transportation', 'Hospitality', 'Retail', 'Media', 'Other'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Private Job Offer - FindAJob</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .offer-form-container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .form-section {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        
        .form-section h2 {
            margin: 0 0 1.5rem 0;
            color: var(--text-primary);
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-primary);
        }
        
        .form-group label .required {
            color: var(--primary);
        }
        
        .candidate-card {
            background: #f9fafb;
            padding: 1.5rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .candidate-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .candidate-info h3 {
            margin: 0 0 0.25rem 0;
            color: var(--text-primary);
        }
        
        .candidate-info p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <?php include '../../includes/employer-header.php'; ?>
    
    <div class="offer-form-container">
        <h1 style="margin-bottom: 0.5rem;">📧 Send Private Job Offer</h1>
        <p style="color: var(--text-secondary); margin-bottom: 2rem;">
            Send an exclusive job offer directly to a specific candidate. This offer will be private and only visible to the selected job seeker.
        </p>
        
        <?php if ($jobSeekerData): ?>
            <div class="candidate-card">
                <div class="candidate-avatar">
                    <?php if (!empty($jobSeekerData['profile_picture'])): ?>
                        <img src="../../uploads/profile-pictures/<?php echo htmlspecialchars($jobSeekerData['profile_picture']); ?>" 
                             style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                    <?php else: ?>
                        <?php echo strtoupper(substr($jobSeekerData['first_name'], 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <div class="candidate-info">
                    <h3><?php echo htmlspecialchars($jobSeekerData['first_name'] . ' ' . $jobSeekerData['last_name']); ?></h3>
                    <p><?php echo htmlspecialchars($jobSeekerData['current_job_title'] ?? 'Job Seeker'); ?></p>
                    <?php if ($jobSeekerData['years_of_experience']): ?>
                        <p><?php echo $jobSeekerData['years_of_experience']; ?> years of experience</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <form id="offerForm">
            <?php if ($jobSeekerId): ?>
                <input type="hidden" name="job_seeker_id" value="<?php echo htmlspecialchars($jobSeekerId); ?>" required>
            <?php else: ?>
                <div class="form-section">
                    <h2><i class="fas fa-user-search"></i> Select Job Seeker</h2>
                    <p style="color: var(--text-secondary); margin-bottom: 1rem;">
                        Search for a job seeker by name or email to send them a private offer.
                    </p>
                    
                    <div class="form-group">
                        <label>Search Job Seeker <span class="required">*</span></label>
                        <input type="text" id="jobSeekerSearch" class="form-control" 
                               placeholder="Type name or email to search..." 
                               autocomplete="off">
                        <div id="searchResults" style="position: relative;"></div>
                    </div>
                    
                    <input type="hidden" name="job_seeker_id" id="selectedJobSeekerId" required>
                    <div id="selectedCandidate" style="display: none; margin-top: 1rem;"></div>
                </div>
            <?php endif; ?>
            
            <!-- Job Details -->
            <div class="form-section">
                <h2><i class="fas fa-briefcase"></i> Job Details</h2>
                
                <div class="form-group">
                    <label>Job Title <span class="required">*</span></label>
                    <input type="text" name="job_title" class="form-control" required 
                           placeholder="e.g., Senior Software Engineer">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Job Type <span class="required">*</span></label>
                        <select name="job_type" class="form-control" required>
                            <option value="full-time">Full-Time</option>
                            <option value="part-time">Part-Time</option>
                            <option value="contract">Contract</option>
                            <option value="internship">Internship</option>
                            <option value="temporary">Temporary</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" class="form-control">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Job Description <span class="required">*</span></label>
                    <textarea name="job_description" class="form-control" rows="8" required 
                              placeholder="Describe the role, responsibilities, and what makes this opportunity special..."></textarea>
                </div>
            </div>
            
            <!-- Location -->
            <div class="form-section">
                <h2><i class="fas fa-map-marker-alt"></i> Location</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Location Type</label>
                        <select name="location_type" class="form-control">
                            <option value="onsite">On-site</option>
                            <option value="remote">Remote</option>
                            <option value="hybrid">Hybrid</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>State</label>
                        <select name="state" class="form-control">
                            <option value="">Select State</option>
                            <?php foreach ($states as $state): ?>
                                <option value="<?php echo htmlspecialchars($state['name']); ?>">
                                    <?php echo htmlspecialchars($state['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>City</label>
                    <input type="text" name="city" class="form-control" placeholder="e.g., Lagos">
                </div>
            </div>
            
            <!-- Compensation -->
            <div class="form-section">
                <h2><i class="fas fa-money-bill-wave"></i> Compensation</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Minimum Salary (₦)</label>
                        <input type="number" name="salary_min" class="form-control" min="0" step="1000" 
                               placeholder="e.g., 150000">
                    </div>
                    
                    <div class="form-group">
                        <label>Maximum Salary (₦)</label>
                        <input type="number" name="salary_max" class="form-control" min="0" step="1000" 
                               placeholder="e.g., 250000">
                    </div>
                    
                    <div class="form-group">
                        <label>Salary Period</label>
                        <select name="salary_period" class="form-control">
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
                            <option value="weekly">Weekly</option>
                            <option value="daily">Daily</option>
                            <option value="hourly">Hourly</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Requirements -->
            <div class="form-section">
                <h2><i class="fas fa-clipboard-check"></i> Requirements</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Experience Level</label>
                        <select name="experience_level" class="form-control">
                            <option value="entry">Entry Level</option>
                            <option value="intermediate" selected>Intermediate</option>
                            <option value="senior">Senior</option>
                            <option value="expert">Expert</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Education Level</label>
                        <input type="text" name="education_level" class="form-control" 
                               placeholder="e.g., Bachelor's Degree">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Required Skills</label>
                    <textarea name="required_skills" class="form-control" rows="3" 
                              placeholder="List required skills, one per line or comma-separated"></textarea>
                </div>
            </div>
            
            <!-- Offer Message -->
            <div class="form-section">
                <h2><i class="fas fa-envelope"></i> Personal Message</h2>
                
                <div class="form-group">
                    <label>Offer Message</label>
                    <textarea name="offer_message" class="form-control" rows="5" 
                              placeholder="Add a personal message explaining why you're reaching out to this candidate specifically..."></textarea>
                    <small style="color: var(--text-secondary);">This helps the candidate understand why they were selected for this opportunity.</small>
                </div>
                
                <div class="form-group">
                    <label>Benefits & Perks</label>
                    <textarea name="benefits" class="form-control" rows="4" 
                              placeholder="List benefits like health insurance, flexible hours, professional development, etc."></textarea>
                </div>
            </div>
            
            <!-- Timeline -->
            <div class="form-section">
                <h2><i class="fas fa-calendar"></i> Timeline</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Proposed Start Date</label>
                        <input type="date" name="start_date" class="form-control" 
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Response Deadline <span class="required">*</span></label>
                        <input type="date" name="deadline" class="form-control" required
                               min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                               value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                        <small style="color: var(--text-secondary);">Candidate must respond by this date</small>
                    </div>
                </div>
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <a href="applicants.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <i class="fas fa-paper-plane"></i> Send Private Offer
                </button>
            </div>
        </form>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
    
    <script>
    // Job seeker search functionality
    <?php if (!$jobSeekerId): ?>
    const searchInput = document.getElementById('jobSeekerSearch');
    const searchResults = document.getElementById('searchResults');
    const selectedJobSeekerInput = document.getElementById('selectedJobSeekerId');
    const selectedCandidateDiv = document.getElementById('selectedCandidate');
    let searchTimeout;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        
        if (query.length < 2) {
            searchResults.innerHTML = '';
            return;
        }
        
        searchTimeout = setTimeout(async () => {
            try {
                const url = `../../api/search.php?q=${encodeURIComponent(query)}&type=job_seekers`;
                console.log('Searching with URL:', url);
                
                const response = await fetch(url);
                const text = await response.text();
                console.log('Raw response:', text);
                
                const data = JSON.parse(text);
                console.log('Parsed data:', data);
                
                if (data.success && data.results && data.results.length > 0) {
                    searchResults.innerHTML = `
                        <div style="position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); max-height: 300px; overflow-y: auto; z-index: 1000; margin-top: 0.5rem;">
                            ${data.results.map(user => `
                                <div onclick="selectJobSeeker(${user.id}, '${user.first_name}', '${user.last_name}', '${user.email}')" 
                                     style="padding: 1rem; cursor: pointer; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; gap: 1rem;"
                                     onmouseover="this.style.background='#f9fafb'" 
                                     onmouseout="this.style.background='white'">
                                    <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                        ${user.first_name.charAt(0).toUpperCase()}
                                    </div>
                                    <div style="flex: 1;">
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <strong>${user.first_name} ${user.last_name}</strong>
                                            ${user.is_premium ? '<span style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; padding: 2px 8px; border-radius: 12px; font-size: 10px; font-weight: 600; text-transform: uppercase;">⚡ BOOSTED</span>' : ''}
                                        </div>
                                        <p style="margin: 0.25rem 0 0 0; color: var(--text-secondary); font-size: 0.9rem;">${user.email}</p>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    `;
                } else {
                    searchResults.innerHTML = `
                        <div style="position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1rem; margin-top: 0.5rem;">
                            <p style="margin: 0; color: var(--text-secondary);">No job seekers found</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Search error:', error);
            }
        }, 300);
    });
    
    window.selectJobSeeker = function(id, firstName, lastName, email) {
        selectedJobSeekerInput.value = id;
        searchInput.value = `${firstName} ${lastName} (${email})`;
        searchResults.innerHTML = '';
        
        selectedCandidateDiv.innerHTML = `
            <div class="candidate-card">
                <div class="candidate-avatar">
                    ${firstName.charAt(0).toUpperCase()}
                </div>
                <div class="candidate-info">
                    <h3>${firstName} ${lastName}</h3>
                    <p>${email}</p>
                </div>
            </div>
        `;
        selectedCandidateDiv.style.display = 'block';
    };
    
    // Close search results when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.innerHTML = '';
        }
    });
    <?php endif; ?>
    
    // Form submission
    document.getElementById('offerForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        <?php if (!$jobSeekerId): ?>
        // Validate job seeker is selected
        const jobSeekerId = document.getElementById('selectedJobSeekerId').value;
        if (!jobSeekerId) {
            alert('Please select a job seeker first');
            return;
        }
        <?php endif; ?>
        
        const submitBtn = document.getElementById('submitBtn');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
        
        try {
            const formData = new FormData(this);
            <?php if (!$jobSeekerId): ?>
            formData.append('job_seeker_id', document.getElementById('selectedJobSeekerId').value);
            <?php endif; ?>
            formData.append('action', 'create_offer');
            
            const response = await fetch('../../api/private-job-offers.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert('Private job offer sent successfully! The candidate will be notified.');
                window.location.href = 'private-offers.php';
            } else {
                alert('Error: ' + data.error);
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        } catch (error) {
            alert('An error occurred. Please try again.');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
    </script>
</body>
</html>

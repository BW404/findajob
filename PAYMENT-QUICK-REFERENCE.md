# Payment Integration Quick Reference

## 🎯 Where to Find Payment Options

### For Job Seekers

| Location | Payment Option | Price | What It Does |
|----------|---------------|-------|--------------|
| **Dashboard** | Pro Plan Upgrade | ₦6k/month or ₦60k/year | Priority alerts, featured profile, unlimited CVs |
| **Dashboard** | Profile Booster | ₦500 | 30-day featured placement |
| **Profile Page** | Profile Booster | ₦500 | Same as above (if 70%+ complete) |
| **Profile Page** | Verification Badge | ₦1,000 | Show verified checkmark on profile |
| **CV Manager** | Professional CV Service | ₦15,500+ | Expert-written CV with consultation |
| **CV Creator** | Professional CV Service | ₦15,500+ | Same as above |

### For Employers

| Location | Payment Option | Price | What It Does |
|----------|---------------|-------|--------------|
| **Dashboard** | Pro Plan Upgrade | ₦30k/month or ₦300k/year | Unlimited posts, advanced analytics, CV search |
| **Dashboard** | Buy Boost Credits | ₦5k - ₦15k | Credits for featuring job posts |
| **Profile Page** | Verification Badge | ₦1,000 | Show verified checkmark on company |
| **Post Job Page** | 1 Job Boost | ₦5,000 | Feature one job for 30 days |
| **Post Job Page** | 3 Job Boosts | ₦10,000 | Save ₦5,000 (₦3,333 each) |
| **Post Job Page** | 5 Job Boosts | ₦15,000 | Save ₦10,000 (₦3,000 each) |

---

## 💻 Code Examples

### Display Subscription Status (Any Page)
```php
// Get subscription data (add to SQL query)
$stmt = $pdo->prepare("
    SELECT u.*, 
           u.subscription_status, u.subscription_plan, 
           u.subscription_type, u.subscription_end
    FROM users u WHERE u.id = ?
");

// Check if Pro
$isPro = ($user['subscription_plan'] === 'pro' && 
          $user['subscription_status'] === 'active');

// Check if expiring soon
$isExpiringSoon = false;
if ($user['subscription_end']) {
    $now = new DateTime();
    $expiry = new DateTime($user['subscription_end']);
    $days = $now->diff($expiry)->days;
    $isExpiringSoon = ($days <= 7 && $expiry > $now);
}
```

### Add Payment Button
```html
<button onclick="initializePayment('job_seeker_pro_monthly', 6000, 'Job Seeker Pro Monthly')" 
        class="btn btn-primary">
    🚀 Upgrade to Pro (₦6,000)
</button>

<script>
function initializePayment(serviceType, amount, description) {
    const button = event.target;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    
    const formData = new FormData();
    formData.append('action', 'initialize_payment');
    formData.append('amount', amount);
    formData.append('service_type', serviceType);
    formData.append('description', description);
    
    fetch('/findajob/api/payment.php', {method: 'POST', body: formData})
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.location.href = data.data.payment_link;
        } else {
            alert('Error: ' + (data.error || 'Payment failed'));
            button.disabled = false;
            button.innerHTML = originalText;
        }
    });
}
</script>
```

### Check Boost Credits (Employer)
```php
// Add to SQL query
$stmt = $pdo->prepare("
    SELECT ep.job_boost_credits, ep.verification_boosted
    FROM employer_profiles ep WHERE ep.user_id = ?
");

$credits = $user['job_boost_credits'] ?? 0;

// Display credits badge
<?php if ($credits > 0): ?>
<span class="badge">
    🚀 <?php echo $credits; ?> Boost Credit<?php echo $credits !== 1 ? 's' : ''; ?>
</span>
<?php endif; ?>
```

### Check Profile Boost (Job Seeker)
```php
// Add to SQL query
$stmt = $pdo->prepare("
    SELECT jsp.profile_boosted, jsp.profile_boost_until
    FROM job_seeker_profiles jsp WHERE jsp.user_id = ?
");

$profileBoostActive = false;
if ($user['profile_boost_until']) {
    $boostDate = new DateTime($user['profile_boost_until']);
    $profileBoostActive = $boostDate > new DateTime();
}

// Display boost status
<?php if ($profileBoostActive): ?>
<div class="alert alert-success">
    🚀 Profile Boost Active until <?php echo date('M d, Y', strtotime($user['profile_boost_until'])); ?>
</div>
<?php endif; ?>
```

---

## 🎨 Banner Templates

### Upgrade Banner (Yellow)
```html
<div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); 
            border-left: 4px solid #f59e0b; padding: 1.25rem; 
            border-radius: 8px; margin-bottom: 1.5rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem;">
        <div style="flex: 1;">
            <h3 style="margin: 0 0 0.5rem 0; color: #92400e;">
                <span style="font-size: 1.5rem;">👑</span> Upgrade to Pro Plan
            </h3>
            <p style="margin: 0; color: #78350f; font-size: 0.875rem;">
                Get premium features starting from ₦6,000/month.
            </p>
        </div>
        <a href="../payment/plans.php" class="btn btn-primary">🚀 View Plans</a>
    </div>
</div>
```

### Active Status (Green)
```html
<div style="background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); 
            border-left: 4px solid #059669; padding: 1.25rem; 
            border-radius: 8px; margin-bottom: 1.5rem;">
    <h3 style="margin: 0 0 0.5rem 0; color: #065f46;">
        <span style="font-size: 1.5rem;">👑</span> Pro Plan Active
    </h3>
    <p style="margin: 0; color: #047857; font-size: 0.875rem;">
        Your subscription expires on <?php echo date('M d, Y', strtotime($expiry)); ?>.
    </p>
</div>
```

### Boosted Status (Purple)
```html
<div style="background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%); 
            border-left: 4px solid #7c3aed; padding: 1.25rem; 
            border-radius: 8px; margin-bottom: 1.5rem;">
    <h3 style="margin: 0 0 0.5rem 0; color: #5b21b6;">
        <span style="font-size: 1.5rem;">🚀</span> Profile Boost Active
    </h3>
    <p style="margin: 0; color: #6b21a8; font-size: 0.875rem;">
        Your profile is featured until <?php echo date('M d, Y', strtotime($until)); ?>.
    </p>
</div>
```

### Verification Badge (Blue)
```html
<div style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); 
            border-left: 4px solid #1e40af; padding: 1.25rem; 
            border-radius: 8px; margin-bottom: 1.5rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem;">
        <div style="flex: 1;">
            <h3 style="margin: 0 0 0.5rem 0; color: #1e3a8a;">
                <span style="font-size: 1.5rem;">✅</span> Get Verified Badge
            </h3>
            <p style="margin: 0; color: #1e40af; font-size: 0.875rem;">
                Show employers you're verified for only ₦1,000.
            </p>
        </div>
        <button onclick="initializePayment('job_seeker_verification_booster', 1000, 'Verification Badge')" 
                class="btn btn-primary">✅ Get Verified</button>
    </div>
</div>
```

---

## 📋 Service Types Reference

### Complete List
```javascript
const SERVICE_TYPES = {
    // Job Seeker
    'job_seeker_pro_monthly': 6000,
    'job_seeker_pro_yearly': 60000,
    'job_seeker_verification_booster': 1000,
    'job_seeker_profile_booster': 500,
    
    // Employer
    'employer_pro_monthly': 30000,
    'employer_pro_yearly': 300000,
    'employer_verification_booster': 1000,
    'employer_job_booster_1': 5000,
    'employer_job_booster_3': 10000,
    'employer_job_booster_5': 15000,
    
    // Other
    'nin_verification': 1000,
    'cv_service': 15500 // Starting price
};
```

---

## 🔗 Important Links

- **Main Pricing Page**: `/pages/payment/plans.php`
- **Payment API**: `/api/payment.php`
- **Verification Page**: `/pages/payment/verify.php`
- **Flutterwave Config**: `/config/flutterwave.php`
- **Documentation**: 
  - `PRICING-PLANS.md` - Complete pricing documentation
  - `FLUTTERWAVE-INTEGRATION.md` - Flutterwave setup guide
  - `PAYMENT-INTEGRATION-COMPLETE.md` - This implementation summary

---

## ⚙️ Configuration

### Flutterwave API Keys
```php
// In config/flutterwave.php
define('FLUTTERWAVE_PUBLIC_KEY', 'your_public_key_here');
define('FLUTTERWAVE_SECRET_KEY', 'your_secret_key_here');
define('FLUTTERWAVE_ENCRYPTION_KEY', 'your_encryption_key_here');
define('FLUTTERWAVE_ENVIRONMENT', 'test'); // or 'live'
```

### Test Card Details
```
Card Number: 5531 8866 5214 2950
CVV: 564
PIN: 3310
OTP: 12345
Expiry: Any future date
```

---

## 🐛 Troubleshooting

### Payment not initializing?
1. Check browser console for JavaScript errors
2. Verify `/api/payment.php` is accessible
3. Ensure user is logged in (`$_SESSION['user_id']`)
4. Check Flutterwave API keys are set

### Database fields missing?
```sql
-- Run these migrations if needed
SOURCE database/add-flutterwave-fields.sql;
SOURCE database/add-subscription-fields.sql;
```

### Subscription not showing?
```php
// Verify data in database
SELECT id, email, subscription_status, subscription_plan, subscription_end 
FROM users WHERE id = ?;

SELECT user_id, profile_boosted, profile_boost_until 
FROM job_seeker_profiles WHERE user_id = ?;

SELECT user_id, job_boost_credits, verification_boosted 
FROM employer_profiles WHERE user_id = ?;
```

---

**Quick Start**: Copy any banner template above, update the service type and price, add the payment initialization JavaScript, and you're done! 🚀

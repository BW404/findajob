# Report to Admin - Quick Integration Guide

## 🚀 Quick Start (2 Steps)

### Step 1: Include the Modal
Add this line before `</body>` tag:
```php
<?php if (isLoggedIn()): ?>
    <?php include '../../includes/report-modal.php'; ?>
<?php endif; ?>
```

### Step 2: Add Report Button
```html
<button onclick="openReportModal('job', <?php echo $jobId; ?>)" class="btn">
    <i class="fas fa-flag"></i> Report
</button>
```

**That's it!** The modal handles everything else.

---

## 📋 Entity Types Reference

| Entity Type | Use Case | Example |
|------------|----------|---------|
| `'job'` | Report job posting | Fake jobs, scams, misleading info |
| `'user'` | Report job seeker | Fake profiles, inappropriate behavior |
| `'company'` | Report employer | Fake companies, scam operations |
| `'application'` | Report application | Spam applications, fake CVs |
| `'other'` | Report anything else | General platform issues |

---

## 🎨 Button Style Examples

### Primary Button
```html
<button onclick="openReportModal('job', 123)" 
        class="btn btn-primary">
    <i class="fas fa-flag"></i> Report Job
</button>
```

### Outline Button (Subtle)
```html
<button onclick="openReportModal('user', 456)" 
        class="btn btn-outline"
        style="color: #6b7280; border: 2px solid #e5e7eb;">
    <i class="fas fa-flag"></i> Report Profile
</button>
```

### Icon Only (Compact)
```html
<button onclick="openReportModal('company', 789)" 
        class="btn btn-icon"
        title="Report this company">
    <i class="fas fa-flag"></i>
</button>
```

### Link Style
```html
<a href="#" onclick="event.preventDefault(); openReportModal('job', 123);" 
   style="color: #6b7280; text-decoration: none;">
    <i class="fas fa-flag"></i> Report
</a>
```

---

## 🔧 Modal Functions

### Open Modal
```javascript
openReportModal(entityType, entityId, entityName)
```
- **entityType**: 'job', 'user', 'company', 'application', 'other'
- **entityId**: Database ID (optional for 'other')
- **entityName**: Display name (optional)

**Examples:**
```javascript
// Simple
openReportModal('job', 123)

// With entity name
openReportModal('job', 123, 'Senior Developer Position')

// Other issue (no ID needed)
openReportModal('other', null, 'Platform Issue')
```

### Close Modal
```javascript
closeReportModal()
```

---

## 📱 Where to Add Report Buttons

### ✅ Job Listings
```php
<!-- In pages/jobs/browse.php or details.php -->
<button onclick="openReportModal('job', <?php echo $job['id']; ?>)">
    Report
</button>
```

### ✅ User Profiles
```php
<!-- In pages/user/profile.php (viewing other user) -->
<button onclick="openReportModal('user', <?php echo $user['id']; ?>)">
    Report Profile
</button>
```

### ✅ Company Pages
```php
<!-- In pages/company/profile.php (viewing as job seeker) -->
<button onclick="openReportModal('company', <?php echo $company_id; ?>)">
    Report Company
</button>
```

### ✅ Applications List
```php
<!-- In pages/company/applications.php -->
<button onclick="openReportModal('application', <?php echo $application['id']; ?>)">
    Report Application
</button>
```

### ✅ CV Search Results
```php
<!-- In pages/company/search-cvs.php -->
<button onclick="openReportModal('user', <?php echo $cv['user_id']; ?>)">
    Report
</button>
```

---

## 🎯 Real-World Examples

### Example 1: Job Card with Report
```php
<div class="job-card">
    <h3><?php echo $job['title']; ?></h3>
    <p><?php echo $job['company_name']; ?></p>
    
    <div class="job-actions">
        <a href="apply.php?id=<?php echo $job['id']; ?>" class="btn btn-primary">
            Apply Now
        </a>
        <button onclick="openReportModal('job', <?php echo $job['id']; ?>, '<?php echo htmlspecialchars(addslashes($job['title'])); ?>')" 
                class="btn btn-outline btn-sm">
            <i class="fas fa-flag"></i>
        </button>
    </div>
</div>
```

### Example 2: Profile Header with Report
```php
<div class="profile-header">
    <img src="<?php echo $user['profile_picture']; ?>" alt="Profile">
    <h2><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></h2>
    
    <?php if (isLoggedIn() && getCurrentUserId() != $user['id']): ?>
        <button onclick="openReportModal('user', <?php echo $user['id']; ?>)" 
                class="btn-report">
            <i class="fas fa-exclamation-triangle"></i>
            Report User
        </button>
    <?php endif; ?>
</div>
```

### Example 3: Dropdown Menu with Report
```php
<div class="dropdown">
    <button class="dropdown-toggle">⋮</button>
    <div class="dropdown-menu">
        <a href="#">Save Job</a>
        <a href="#">Share</a>
        <hr>
        <a href="#" onclick="openReportModal('job', <?php echo $job['id']; ?>); return false;">
            <i class="fas fa-flag"></i> Report Job
        </a>
    </div>
</div>
```

---

## 🛡️ Security Checklist

✅ **Only show to logged-in users**
```php
<?php if (isLoggedIn()): ?>
    <button onclick="openReportModal('job', 123)">Report</button>
<?php endif; ?>
```

✅ **Escape entity names**
```php
openReportModal('job', 123, '<?php echo htmlspecialchars(addslashes($title)); ?>')
```

✅ **Validate entity IDs**
```php
$jobId = (int)$_GET['id']; // Cast to int
openReportModal('job', <?php echo $jobId; ?>)
```

✅ **Don't allow self-reporting** (optional)
```php
<?php if (getCurrentUserId() != $entity_owner_id): ?>
    <button onclick="openReportModal(...)">Report</button>
<?php endif; ?>
```

---

## 📊 Admin Panel Access

**URL**: `/admin/reports.php`

**Features**:
- View all reports
- Filter by status/type/reason
- Review and take action
- Add admin notes
- Track resolution

**Admin Actions**:
1. **Mark as Under Review** - Investigation in progress
2. **Resolve** - Issue fixed, add notes
3. **Dismiss** - Invalid report, add notes

---

## 🎨 UI Customization

### Custom Button Styles
```html
<style>
.btn-report {
    background: transparent;
    border: 2px solid #e5e7eb;
    color: #6b7280;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-report:hover {
    border-color: #dc2626;
    color: #dc2626;
    background: #fef2f2;
}
</style>

<button class="btn-report" onclick="openReportModal('job', 123)">
    <i class="fas fa-flag"></i> Report
</button>
```

### Mobile-Friendly
```html
<button onclick="openReportModal('job', 123)" 
        class="btn"
        style="width: 100%; padding: 0.75rem; font-size: 1rem;">
    <i class="fas fa-flag"></i> Report Issue
</button>
```

---

## 🐛 Troubleshooting

### Modal doesn't open?
1. Check if `report-modal.php` is included
2. Check browser console for errors
3. Verify Font Awesome is loaded

### Submit button disabled?
1. Check description length (min 10 chars)
2. Ensure reason is selected
3. Check browser console for validation errors

### "Unauthorized" error?
1. Verify user is logged in: `isLoggedIn()`
2. Check session is active
3. Try logging out and back in

---

## ✅ Testing Checklist

Before deploying:

- [ ] Report modal opens correctly
- [ ] All 14 reasons are selectable
- [ ] Description validates (10-2000 chars)
- [ ] Character counter works
- [ ] Success message shows after submit
- [ ] Modal auto-closes after success
- [ ] Can't submit duplicate report within 24h
- [ ] Admin can see report in panel
- [ ] Admin can take actions
- [ ] Badge count updates

---

## 📞 Support

**Test Page**: `/test-report-system.php`
**API Endpoint**: `/api/reports.php`
**Admin Panel**: `/admin/reports.php`
**Documentation**: `/REPORT-SYSTEM-COMPLETE.md`

---

## 💡 Pro Tips

1. **Place report buttons in accessible locations** - Users should easily find them when needed

2. **Use subtle styling for report buttons** - Don't make them more prominent than primary actions

3. **Provide context** - Show what they're reporting (job title, user name, etc.)

4. **Monitor reports regularly** - Set up admin routine to review pending reports daily

5. **Add report stats to dashboard** - Track trends and respond to emerging issues

6. **Train admins on handling reports** - Ensure consistent, fair treatment of all reports

---

## 🚀 Common Patterns

### Pattern 1: Three-Dot Menu
```php
<div class="action-menu">
    <button class="menu-trigger">⋮</button>
    <div class="menu-items">
        <a href="#">View</a>
        <a href="#">Save</a>
        <a href="#" onclick="openReportModal('job', 123)">Report</a>
    </div>
</div>
```

### Pattern 2: Sidebar Action
```php
<aside class="sidebar">
    <!-- Main actions -->
    <button class="btn-primary">Apply</button>
    <button class="btn-secondary">Save</button>
    
    <!-- Secondary actions -->
    <hr>
    <button class="btn-text" onclick="openReportModal('job', 123)">
        <i class="fas fa-flag"></i> Report Issue
    </button>
</aside>
```

### Pattern 3: Footer Link
```php
<footer class="content-footer">
    <small>Posted 2 days ago</small>
    <a href="#" onclick="openReportModal('job', 123); return false;">
        Report
    </a>
</footer>
```

---

**Remember**: The report system is designed to be simple to integrate. Just include the modal and add buttons where needed. The system handles all validation, submission, and admin workflow automatically!

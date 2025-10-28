# Quick Fix: Update All Form Fields in post-job.php

## Issue
When editing a job, the form doesn't pre-fill with existing job data.

## Solution Implemented

### 1. Added Edit Detection (Line ~40)
```php
// Check if we're editing an existing job
$isEditing = false;
$editJobId = isset($_GET['edit']) ? (int)$_GET['edit'] : null;
$existingJob = null;

if ($editJobId) {
    // Fetch the job to edit
    $stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ? AND employer_id = ?");
    $stmt->execute([$editJobId, $userId]);
    $existingJob = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingJob) {
        $isEditing = true;
    }
}
```

### 2. Added Helper Function (After edit detection)
```php
// Helper function to get field value: POST > existing job > default
function getFieldValue($fieldName, $default = '') {
    global $existingJob;
    if (isset($_POST[$fieldName])) {
        return $_POST[$fieldName];
    }
    if ($existingJob && isset($existingJob[$fieldName])) {
        return $existingJob[$fieldName];
    }
    return $default;
}
```

### 3. Updated INSERT to handle UPDATE
The SQL now checks if we're editing and uses UPDATE instead of INSERT.

## Manual Steps to Complete

Update ALL form fields to use `getFieldValue()`. Search and replace these patterns:

### Pattern 1: Text Inputs
**Find:**
```php
value="<?php echo htmlspecialchars($_POST['field_name'] ?? ''); ?>"
```

**Replace with:**
```php
value="<?php echo htmlspecialchars(getFieldValue('field_name', '')); ?>"
```

### Pattern 2: Textareas
**Find:**
```php
><?php echo htmlspecialchars($_POST['field_name'] ?? ''); ?></textarea>
```

**Replace with:**
```php
><?php echo htmlspecialchars(getFieldValue('field_name', '')); ?></textarea>
```

### Pattern 3: Selects (requires custom logic for each)
**Current:**
```php
<?php echo ($_POST['field_name'] ?? '') === 'value' ? 'selected' : ''; ?>
```

**Replace with:**
```php
<?php echo getFieldValue('field_name', '') === 'value' ? 'selected' : ''; ?>
```

### Pattern 4: Checkboxes
**Current:**
```php
<?php echo isset($_POST['field_name']) ? 'checked' : ''; ?>
```

**Replace with:**
```php
<?php echo getFieldValue('field_name', '') ? 'checked' : ''; ?>
```

## Fields to Update

### Step 1 Fields (Lines ~975-1105):
- ✅ job_title - DONE
- ✅ job_type - DONE  
- ✅ category - DONE
- location
- remote_friendly
- salary_min
- salary_max
- salary_period
- benefits

### Step 2 Fields (Lines ~1120-1270):
- description
- requirements
- responsibilities
- experience
- education
- application_deadline
- application_type (radio buttons)
- application_email
- application_url
- application_instructions

### Step 3 Fields (Lines ~1280-1340):
- boost_type (radio buttons)
- is_urgent

## Quick Regex Replacements (Use with caution!)

### 1. Simple Text Inputs
Search: `value="\<\?php echo htmlspecialchars\(\$_POST\['([^']+)'\] \?\? ''\); \?>"`
Replace: `value="<?php echo htmlspecialchars(getFieldValue('$1', '')); ?>"`

### 2. Textareas  
Search: `>\<\?php echo htmlspecialchars\(\$_POST\['([^']+)'\] \?\? ''\); \?></textarea>`
Replace: `><?php echo htmlspecialchars(getFieldValue('$1', '')); ?></textarea>`

### 3. Select Options
Search: `\<\?php echo \(\$_POST\['([^']+)'\] \?\? '([^']*)'\) === '([^']+)' \? 'selected' : ''; \?>`
Replace: `<?php echo getFieldValue('$1', '$2') === '$3' ? 'selected' : ''; ?>`

## Testing

1. **Create a new job** - Should work as before
2. **Edit existing job** - Form should pre-fill with job data  
3. **Submit edits** - Should UPDATE the job, not create new one
4. **Check all fields** - Verify all fields retain their values

## Current Status

✅ Edit detection added
✅ Helper function added
✅ UPDATE SQL logic added
✅ Page title/header updated
✅ 3 fields updated (job_title, job_type, category)
⏳ Remaining ~15 fields need updating

Use the patterns above to quickly update remaining fields!

# Job Centres Admin Panel - User Guide

## Overview
The Job Centres management feature allows you to add, edit, and manage job centres (both government and private) that job seekers can discover and use to find employment opportunities.

## Accessing the Feature
1. Log in to the Admin Panel
2. Navigate to **Content → Job Centres** in the sidebar

## Features

### 1. Dashboard Statistics
View at-a-glance statistics:
- **Total Job Centres**: Count of all job centres in the system
- **Verified Centres**: Number of verified/trusted job centres
- **Government Centres**: Count of government organizations
- **Active Centres**: Currently active and visible centres

### 2. Adding a Job Centre Manually

Click the **"Add Job Centre"** button to open the form.

**Required Fields:**
- **Name**: Full name of the job centre (e.g., "National Directorate of Employment")
- **Category**: 
  - `Offline` - Physical location only
  - `Online` - Virtual platform only
  - `Both` - Hybrid (both online and offline services)
- **State**: Nigerian state where the centre is located
- **City**: City or LGA within the state

**Optional Fields:**
- **Description**: Detailed description of services and mission
- **Address**: Full physical address
- **Contact Number**: Phone number (Nigerian format)
- **Email**: Contact email address
- **Website**: Full website URL (https://example.com)
- **Services Offered**: Comma-separated list of services
  - Example: `Job Placement, Career Counseling, Skills Training, CV Writing`
- **Operating Hours**: Business hours
  - Example: `Mon-Fri: 9AM-5PM` or `24/7 Online`
- **Verified Job Centre**: Check if the centre is verified/trusted
- **Government Organization**: Check if it's a government entity
- **Active Status**: Check to make the centre visible to users

### 3. Bulk Upload via CSV

For adding multiple job centres at once:

#### Step 1: Download Template
1. Click **"Bulk Upload"** button
2. In the modal, click **"Download CSV Template"** (or click the button in the action bar)
3. A sample CSV file will be downloaded

#### Step 2: Prepare Your CSV File

**CSV Format:**
```csv
name,category,description,address,state,city,contact_number,email,website,services,operating_hours,is_verified,is_government,is_active
```

**Column Descriptions:**
- `name` - Job centre name (REQUIRED)
- `category` - One of: online, offline, both (REQUIRED)
- `description` - Full description of services
- `address` - Physical address
- `state` - Nigerian state (REQUIRED)
- `city` - City/LGA (REQUIRED)
- `contact_number` - Phone number
- `email` - Email address
- `website` - Website URL
- `services` - Comma-separated services (use semicolons within CSV)
- `operating_hours` - Business hours
- `is_verified` - 1 for verified, 0 for not verified
- `is_government` - 1 for government, 0 for private
- `is_active` - 1 for active, 0 for inactive

**Example CSV Data:**
```csv
name,category,description,address,state,city,contact_number,email,website,services,operating_hours,is_verified,is_government,is_active
National Directorate of Employment,offline,"Government employment agency","Plot 1, NDE House",Lagos,Ikeja,08012345678,info@nde.gov.ng,https://nde.gov.ng,"Job Placement; Training; Counseling",Mon-Fri: 8AM-4PM,1,1,1
Career Services Ltd,both,"Private recruitment firm","15 Victoria Street",Lagos,Victoria Island,08098765432,contact@career.ng,https://career.ng,"Recruitment; CV Writing",Mon-Fri: 9AM-5PM,1,0,1
```

**Important Notes:**
- For services column, separate items with semicolons (;) inside the CSV
- Use proper quotes around text with commas
- Ensure all required fields have values
- Valid categories: online, offline, both
- Boolean fields (is_verified, is_government, is_active): use 1 or 0

#### Step 3: Upload CSV
1. Click **"Bulk Upload"** button
2. Select your prepared CSV file
3. Choose whether to **"Skip duplicate entries"** (recommended)
   - Duplicates are checked based on name + state combination
4. Click **"Upload CSV"**

#### Step 4: Review Results
After upload, you'll see:
- **Imported**: Number of successfully imported centres
- **Skipped**: Number of duplicates skipped
- **Errors**: Number of failed imports
- Error details will be displayed if any issues occurred

### 4. Filtering & Search

Use the filter bar to find specific job centres:
- **Search**: Search by name, city, or state
- **State**: Filter by specific Nigerian state
- **Category**: Filter by online/offline/both
- **Verified**: Filter by verification status
- **Type**: Filter by government/private

Click **"Filter"** to apply or **"Clear"** to reset.

### 5. Managing Existing Job Centres

Each job centre row has action buttons:

- **Edit** (Blue pencil icon): Open the edit form
- **Toggle Verification** (Yellow shield icon): Mark as verified/unverified
- **Toggle Status** (Gray eye icon): Activate/deactivate the centre
- **Delete** (Red trash icon): Permanently delete the job centre
  - ⚠️ This action cannot be undone!

### 6. Editing a Job Centre
1. Click the edit icon (blue pencil)
2. Modify any fields as needed
3. Click **"Save Job Centre"**

## Best Practices

### For Government Centres
- Always mark as **Government Organization** ✓
- Include official contact information
- Add comprehensive service descriptions
- Mark as **Verified** after validation ✓

### For Private Centres
- Verify legitimacy before marking as verified
- Ensure contact details are accurate
- Include website for credibility
- Monitor reviews and ratings

### Service Categories
Common services to include:
- Job Placement
- Career Counseling
- Skills Training
- CV Writing/Review
- Interview Preparation
- Job Search Assistance
- Vocational Training
- Entrepreneurship Training
- Resume Building
- Career Development

### Data Quality
- Use consistent naming (e.g., "NDE Lagos" vs "Lagos NDE")
- Verify phone numbers are in Nigerian format
- Ensure websites include https://
- Keep descriptions informative but concise
- Update operating hours regularly

## Troubleshooting

### Bulk Upload Issues

**"CSV file is empty or invalid"**
- Ensure the file has a header row
- Check that the file is saved as CSV (not Excel)

**"Missing required columns"**
- Verify your CSV has: name, category, state, city

**"Row has insufficient columns"**
- Check for missing commas in your CSV
- Ensure all rows have the same number of columns

**"Error inserting [name]"**
- Check for duplicate entries
- Verify data types (e.g., category must be online/offline/both)
- Ensure required fields are not empty

### General Issues

**Job centre not appearing on frontend**
- Check if **Active Status** is enabled
- Verify the state and city are spelled correctly
- Ensure the centre is not marked as inactive

**Services not displaying correctly**
- Use commas to separate services in manual entry
- Use semicolons in CSV files
- Avoid special characters

## Security Notes

- Only Super Admins and authorized administrators can access this feature
- All changes are logged
- Bulk uploads are validated before insertion
- Duplicate detection prevents data redundancy

## Need Help?

For technical support or questions:
- Email: admin@findajob.ng
- Check system logs for detailed error messages
- Review the sample data in the CSV template

---

**Last Updated**: January 6, 2026
**Version**: 1.0.0

# FindAJob Nigeria - Database & API Reference Map

**Generated:** December 21, 2025  
**Database:** findajob_ng (MySQL/MariaDB)  
**Charset:** utf8mb4_unicode_ci

---

## Database Tables (35 Tables)

### Authentication & User Management
| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `users` | Main user accounts (job_seekers, employers, admins) | id, email, user_type, is_suspended, email_verified, phone_verified, subscription_status |
| `job_seeker_profiles` | Job seeker profile details | user_id, bio, skills, expected_salary, availability |
| `employer_profiles` | Employer/company profile details | user_id, company_name, company_cac_number, company_cac_verified, provider_nin, provider_nin_verified |
| `email_verifications` | Email verification tokens | user_id, token, expires_at |
| `password_resets` | Password reset tokens | email, token, created_at |
| `login_attempts` | Track failed login attempts | email, ip_address, attempted_at |
| `phone_verification_attempts` | Phone OTP verification tracking | phone_number, otp_code, expires_at |

### Admin System
| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `admin_users` | Admin user accounts (deprecated - now in users) | - |
| `admin_roles` | Admin role definitions | id, role_name, description |
| `admin_permissions` | Permission definitions | id, permission_name, category |
| `admin_role_permissions` | Role-permission mapping | role_id, permission_id |

### Jobs & Applications
| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `jobs` | Job postings | id, employer_id, title, status, job_type, salary_min, salary_max, easy_apply, external_url, is_boosted |
| `job_applications` | Job applications | id, job_id, job_seeker_id, cv_id, application_status, applied_at |
| `job_categories` | Job category taxonomy | id, name, slug, icon |
| `saved_jobs` | User saved jobs | user_id, job_id (unique) |
| `internships` | Internship postings | id, employer_id, title, status, stipend_amount, duration_months |
| `internship_badges` | Internship completion badges | user_id, internship_id, issued_at |

### CV Management
| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `cvs` | Uploaded/generated CVs | user_id, cv_type (uploaded/generated), file_path, cv_data (JSON), is_primary |
| `cv_analytics` | CV view/download tracking | cv_id, viewer_id, action_type, ip_address |
| `premium_cv_requests` | Premium CV generation requests | user_id, cv_id, status, payment_status |

### Location Data
| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `nigeria_states` | 37 Nigerian states | id, name, code |
| `nigeria_lgas` | 774 Local Government Areas | id, state_id, name |

### Reports & Moderation
| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `reports` | User-submitted reports | id, reporter_id, reported_entity_type (user/job/application), reported_entity_id, reason, status (pending/under_review/resolved/dismissed/suspended) |

### Payments & Subscriptions
| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `payment_transactions` | Payment records (Flutterwave) | id, user_id, amount, status, transaction_reference, flutterwave_tx_ref |
| `transactions` | General transaction log | user_id, type, amount, status |
| `user_subscriptions` | User subscription records | user_id, plan, status, start_date, end_date |
| `verification_transactions` | Verification payment tracking | user_id, verification_type, amount, status |
| `verification_audit_log` | Verification attempt audit trail | user_id, verification_type, status, verified_by |

### Private Job Offers
| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `private_job_offers` | Direct job offers to specific users | employer_id, job_seeker_id, job_id, status, expires_at |
| `private_offer_notifications` | Notification tracking for offers | offer_id, sent_at, read_at |

### Work History & Education
| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `user_work_experience` | Job seeker work history | user_id, company_name, job_title, start_date, end_date, is_current |
| `user_education` | Job seeker education records | user_id, institution, degree, field_of_study, graduation_year |

### Marketing & Ads
| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `advertisements` | Platform advertisements | title, ad_type, placement, status, impressions, clicks |
| `companies` | Company directory (legacy) | name, industry, website |

### Configuration
| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `site_settings` | Platform configuration | setting_key, setting_value, updated_at |

---

## API Endpoints (20 Files)

### Authentication & User Management
**File:** `api/auth.php`  
**Class:** AuthAPI  
**Endpoints:**
- `register` - User registration (job_seeker/employer)
- `login` - User login with suspension check
- `logout` - User logout
- `verify_email` - Email verification via token
- `resend_verification` - Resend verification email
- `request_password_reset` - Send password reset email
- `reset_password` - Reset password with token
- `verify_phone_otp` - Verify phone OTP code

**File:** `api/verify-phone.php`  
**Function:** Phone OTP generation and verification  
**Methods:**
- `POST send_otp` - Send OTP to phone number
- `POST verify_otp` - Verify OTP code

**File:** `api/verify-nin.php`  
**Function:** Job seeker NIN verification via Dojah API  
**Methods:**
- `POST` - Verify NIN against user details

**File:** `api/verify-employer-nin.php`  
**Function:** Employer provider NIN verification  
**Methods:**
- `POST` - Verify employer representative NIN

**File:** `api/verify-cac.php`  
**Function:** Company CAC number verification via Dojah API  
**Methods:**
- `POST` - Verify company CAC registration

**File:** `api/upload-profile-picture.php`  
**Function:** Profile/company logo upload  
**Methods:**
- `POST` - Upload and process image

### Jobs & Applications
**File:** `api/jobs.php`  
**Functions:**
- `save_job` - Save job to favorites
- `unsave_job` - Remove job from favorites
- `apply_job` - Submit job application
- `get_job_details` - Fetch job information
- `delete_job` - Delete job posting (employer)
- `update_job_status` - Change job status (active/closed)

**File:** `api/ai-job-recommendations.php`  
**Function:** ML-based job matching algorithm  
**Methods:**
- `GET` - Get personalized job recommendations

**File:** `api/search.php`  
**Functions:**
- `autocomplete_jobs` - Job title autocomplete
- `autocomplete_companies` - Company name autocomplete
- `autocomplete_locations` - Location autocomplete
- `autocomplete_categories` - Category autocomplete
- `search_jobs` - Full job search with filters

**File:** `api/locations.php`  
**Functions:**
- `get_states` - List all Nigerian states
- `get_lgas` - Get LGAs by state_id

**File:** `api/salary-insights.php`  
**Function:** Salary statistics and insights  
**Methods:**
- `GET` - Get salary ranges by job title, category, location

### CV Management
**File:** `api/generate-cv.php`  
**Functions:**
- `save_cv_data` - Save CV generation progress
- `generate_pdf` - Generate PDF from CV data
- `get_templates` - List available CV templates
- `preview_cv` - Preview CV before download

**File:** `api/cv-analytics.php`  
**Functions:**
- `track_view` - Record CV view
- `track_download` - Record CV download
- `get_cv_stats` - Get CV analytics for user

### Payment System
**File:** `api/payment.php`  
**Functions:**
- `initialize_payment` - Start Flutterwave payment
- `verify_payment` - Verify transaction status
- `get_transaction_history` - User transaction list

**File:** `api/payment-callback.php`  
**Function:** Flutterwave payment redirect handler  
**Methods:**
- `GET` - Process payment callback

**File:** `api/flutterwave-webhook.php`  
**Function:** Flutterwave webhook receiver  
**Methods:**
- `POST` - Process payment notifications

### Private Job Offers
**File:** `api/private-job-offers.php`  
**Functions:**
- `create_offer` - Send private job offer
- `get_offers` - List offers (employer/job_seeker)
- `update_offer_status` - Accept/decline offer
- `get_offer_details` - View offer details

### Reports & Moderation
**File:** `api/reports.php`  
**Functions:**
- `submit_report` - Create new report
- `get_user_reports` - List user's submitted reports

### Notifications
**File:** `api/notifications.php`  
**Functions:**
- `get_notifications` - Fetch user notifications
- `mark_read` - Mark notification as read
- `mark_all_read` - Mark all as read

### Admin Actions
**File:** `api/admin-actions.php`  
**Functions:**
- `get_report` - Get detailed report information
- `unsuspend_account` - Unsuspend user account
- `suspend_user` - Toggle user active status
- `verify_user` - Verify email/phone/nin manually
- `get_dashboard_stats` - Admin dashboard statistics
- `get_role_permissions` - Get permissions for role
- `update_role_permissions` - Update role permissions
- `create_role` - Create new admin role
- `update_role` - Update role details
- `delete_role` - Delete admin role

### Interview Scheduling
**File:** `api/interview.php`  
**Functions:**
- `schedule_interview` - Schedule interview for job application (employer)
- `update_interview` - Update interview details (employer)
- `cancel_interview` - Cancel scheduled interview (employer/job seeker)
- `get_interview` - Get interview details
- `get_my_interviews` - List user's scheduled interviews

---

## Database Relationships

### Key Foreign Keys
```
users.id → job_seeker_profiles.user_id
users.id → employer_profiles.user_id
users.id → jobs.employer_id
users.id → job_applications.job_seeker_id
users.id → cvs.user_id
users.id → saved_jobs.user_id
users.id → reports.reporter_id
users.id → private_job_offers.employer_id
users.id → private_job_offers.job_seeker_id

jobs.id → job_applications.job_id
jobs.id → saved_jobs.job_id
jobs.id → private_job_offers.job_id

cvs.id → job_applications.cv_id
cvs.id → cv_analytics.cv_id

nigeria_states.id → nigeria_lgas.state_id

admin_roles.id → admin_role_permissions.role_id
admin_permissions.id → admin_role_permissions.permission_id
```

---

## Important Notes

### Suspension System
- **Fields:** `is_suspended`, `suspension_reason`, `suspended_at`, `suspended_by`, `suspension_expires`
- **Auto-unsuspend:** Checked on login if `suspension_expires < NOW()`
- **Session check:** `checkSuspensionStatus()` forces logout if suspended
- **Report status:** Changes to 'suspended' when user suspended from report

### Verification System
- **Email:** `email_verified` (boolean)
- **Phone:** `phone_verified`, `phone_verified_at` (OTP-based)
- **NIN:** Stored in profiles, verified via Dojah API
- **CAC:** `company_cac_verified`, `company_cac_verified_at` (Dojah API)

### Payment Integration
- **Provider:** Flutterwave
- **Webhook:** `api/flutterwave-webhook.php`
- **Callback:** `api/payment-callback.php`
- **Status tracking:** `payment_transactions` table

### Job Application Types
- **Easy Apply:** One-click with uploaded CV (`easy_apply = 1`)
- **External Apply:** Redirect to company ATS (`external_url`)
- **Both:** Dual application methods enabled

### Report System
- **Entity Types:** user, job, application, company, other
- **Reasons:** 14 predefined reasons (fake_profile, fake_job, harassment, etc.)
- **Statuses:** pending, under_review, resolved, dismissed, suspended
- **Actions:** Suspend account, unsuspend account, dismiss, resolve

---

## Development Tips

### Database Access
```bash
# PowerShell
cd E:\XAMPP\mysql\bin
.\mysql.exe -u root
USE findajob_ng;
```

### API Authentication
Most APIs require session authentication. Use `credentials: 'same-origin'` in fetch:
```javascript
fetch('../api/endpoint.php', {
    method: 'POST',
    credentials: 'same-origin',
    body: formData
});
```

### Error Handling
APIs return JSON with `success` boolean and `message` field:
```json
{"success": true, "data": {...}}
{"success": false, "message": "Error description"}
```

---

**Last Updated:** December 21, 2025  
**Maintained By:** FindAJob Development Team

# FindAJob Nigeria - AI Coding Assistant Instructions

## Project Overview
FindAJob (FAJ) is a comprehensive job platform for Nigeria that goes beyond traditional job boards. It's a platform where job seekers can search for jobs, discover government and private sector opportunities, and get guidance on self-employment. Built as a progressive web app with Android APK compilation capability.

### Target Demographics
- Nigerian males and females aged 18-35
- Education levels: School certificate to graduate holders

### Key Differentiators
- **Secure Environment**: Verified candidates and employers via NIN/BVN
- **Multiple Job Types**: Permanent, contract, temp, internship, NYSC placement
- **AI Features**: Job matching, candidate suggestions, CV generation
- **Multiple Profiles**: Job seekers can manage multiple CVs for different roles
- **Employer Mini-Sites**: Branded sub-domains for corporate clients
- **Training & Guidance**: Self-employment guides and training videos
- **Professional Services**: CV creation, recruitment services

### Revenue Streams
- ID verification services (₦1,000 one-off)
- Google Ads and private advertising
- Pro subscriptions (Job seekers: ₦6,000/month, Employers: ₦30,000/month)
- Employer mini-site subscriptions with custom URLs
- CV creation services (₦15,500 - ₦33,500)
- Job posting boosters (₦5,000 - ₦15,000)

## Technology Stack
- **Frontend**: HTML5, CSS3 (modern), JavaScript (ES6+)
- **Backend**: PHP 8.x with XAMPP
- **Database**: MySQL 8.x
- **UI Framework**: Modern CSS Grid/Flexbox, CSS Variables
- **PWA**: Service Workers, Web App Manifest
- **Mobile**: Progressive Web App (PWA) → Android APK via TWA/Cordova

## Development Environment
- **Platform**: XAMPP (Apache, MySQL, PHP)
- **Location**: `e:\XAMPP\htdocs\findajob`
- **Local Access**: `http://localhost/findajob`
- **Mobile Testing**: Use browser dev tools mobile view or local network access

## Architecture & Directory Structure
```
/findajob
├── index.php                 # Landing page with job search
├── manifest.json            # PWA manifest for app-like experience
├── sw.js                   # Service worker for offline capability
├── config/
│   ├── database.php        # MySQL connection settings
│   ├── constants.php       # App-wide constants (colors, API keys)
│   ├── session.php         # Session management utilities
│   └── pricing.php         # Subscription plans and pricing
├── assets/
│   ├── css/
│   │   ├── main.css        # Core styles with CSS variables
│   │   ├── components.css  # Reusable UI components
│   │   ├── mobile.css      # Mobile-specific responsive styles
│   │   └── admin.css       # Admin panel specific styles
│   ├── js/
│   │   ├── app.js          # Main application logic
│   │   ├── search.js       # Job search functionality
│   │   ├── auth.js         # Authentication handling
│   │   ├── pwa.js          # Progressive Web App features
│   │   ├── ai-matching.js  # AI job matching algorithms
│   │   └── verification.js # ID verification handling
│   ├── images/
│   │   ├── icons/          # App icons for PWA (various sizes)
│   │   ├── logos/          # Branding assets
│   │   ├── placeholders/   # Job/company placeholder images
│   │   └── badges/         # Verification badges, internship badges
│   └── fonts/              # Custom fonts for Nigerian audience
├── api/
│   ├── jobs.php            # Job CRUD operations
│   ├── users.php           # User management
│   ├── auth.php            # Login/register endpoints
│   ├── applications.php    # Job application handling
│   ├── search.php          # Advanced search functionality
│   ├── verification.php    # NIN/BVN verification endpoints
│   ├── ai-matching.php     # AI job matching algorithms
│   ├── payments.php        # Subscription and payment handling
│   └── raffle.php          # Raffle system management
├── pages/
│   ├── jobs/
│   │   ├── browse.php      # Job listings with filters
│   │   ├── details.php     # Individual job details
│   │   ├── apply.php       # Job application form
│   │   └── internships.php # Internship listings
│   ├── user/
│   │   ├── profile.php     # Job seeker profile (basic/advanced)
│   │   ├── dashboard.php   # User dashboard with notifications
│   │   ├── applications.php # Track applications
│   │   ├── cv-manager.php  # Multiple CV management
│   │   ├── verification.php # ID verification process
│   │   ├── subscription.php # Upgrade to pro plans
│   │   └── raffle.php      # Raffle tickets and status
│   ├── company/
│   │   ├── register.php    # Company registration
│   │   ├── dashboard.php   # Company dashboard
│   │   ├── post-job.php    # Job posting form
│   │   ├── applicants.php  # Manage job applicants
│   │   ├── resume-search.php # Search CVs (paid feature)
│   │   ├── mini-site.php   # Mini website management
│   │   └── interviews.php  # Schedule interviews
│   ├── services/
│   │   ├── cv-creator.php  # AI-powered CV generator
│   │   ├── cv-professional.php # Professional CV service
│   │   ├── job-centre.php  # Job center locations/services
│   │   └── training.php    # Training videos and guides
│   └── auth/
│       ├── login.php       # Login page
│       ├── register.php    # User registration
│       └── reset.php       # Password reset
├── admin/
│   ├── index.php           # Super admin dashboard
│   ├── users.php           # Job seeker management
│   ├── employers.php       # Employer management
│   ├── jobs.php            # Job management
│   ├── transactions.php    # Payment/subscription tracking
│   ├── ads.php             # Advertisement management
│   ├── cv-manager.php      # CV management system
│   ├── data-scraper.php    # External data management
│   ├── social-media.php    # Social media management
│   ├── api-manager.php     # API key management
│   └── reports.php         # Analytics and reports
├── mini-sites/             # Employer mini-site templates
│   ├── templates/          # Mini-site templates
│   └── custom/             # Custom branded sites
├── includes/
│   ├── header.php          # Common header with navigation
│   ├── footer.php          # Footer with PWA install prompt
│   ├── functions.php       # Utility functions
│   ├── validators.php      # Form validation helpers
│   ├── payment-handler.php # Payment processing
│   └── ai-helpers.php      # AI matching algorithms
└── database/
    ├── schema.sql          # Database structure
    ├── seed.sql            # Sample data for development
    ├── migrations/         # Database version control
    └── nigeria-data.sql    # Nigerian states/LGAs data
```

## Design System & UI Conventions

### Color Palette (CSS Variables)
```css
:root {
  --primary: #dc2626;         /* Red primary */
  --primary-light: #fecaca;   /* Light red backgrounds */
  --primary-dark: #991b1b;    /* Dark red accents */
  --secondary: #64748b;       /* Neutral gray */
  --accent: #059669;          /* Success green */
  --warning: #d97706;         /* Warning orange */
  --background: #f8fafc;      /* Light background */
  --surface: #ffffff;         /* Card backgrounds */
  --text-primary: #1e293b;    /* Main text */
  --text-secondary: #64748b;  /* Secondary text */
}
```

### Component Patterns
- **Cards**: Modern shadowed cards for jobs, companies
- **Buttons**: Red primary with proper hover/focus states
- **Forms**: Clean inputs with validation feedback
- **Navigation**: Sticky header with mobile hamburger menu
- **Search**: Prominent search bar with filters
- **Responsive**: Mobile-first approach with breakpoints

## Database Schema Patterns

### Core Tables
- `users` (job_seekers, employers, admins with role-based permissions)
- `user_profiles` (basic and advanced profile data from BVN/NIN)
- `user_subscriptions` (pro plan tracking and billing)
- `user_verification` (NIN/BVN verification status)
- `companies` (employer profiles with verification status)
- `jobs` (job postings: permanent, contract, temp, internship, NYSC)
- `job_applications` (application tracking with status)
- `cvs` (multiple CV management per user)
- `cover_letters` (AI-generated cover letters)
- `categories` (Nigerian-specific job categories)
- `locations` (Nigerian states/LGAs with full hierarchy)
- `mini_sites` (employer branded mini-websites)
- `transactions` (payment tracking for subscriptions/boosters)
- `raffle_tickets` (raffle system for marketing)
- `internship_badges` (internship completion tracking)
- `interviews` (scheduled interview management)
- `job_centre_locations` (offline/online job centers)

### Key Features Schema
- **Multiple CVs**: Users can have different CVs for different job types
- **Verification System**: NIN/BVN integration for identity verification
- **AI Matching**: Store job-candidate matching scores and preferences
- **Subscription Tiers**: Free vs Pro features with usage limits
- **Mini-Sites**: Employer branded pages with custom domains
- **Raffle System**: Marketing tool with automated prize distribution
- **Interview Scheduling**: Google Meet integration for online interviews

### Nigerian-Specific Considerations
- Store locations as Nigerian states/LGAs with full hierarchy
- Support for salary in Naira (₦) with range formatting
- Job categories popular in Nigerian market (Tech, Oil & Gas, Banking, Government)
- Support for both English and local language job titles
- Integration with Nigerian APIs (NIN/BVN verification)
- Internship duration tracking and completion badges

## Development Workflow

### XAMPP Setup
1. Start Apache and MySQL services
2. Create database: `findajob_ng`
3. Import `database/schema.sql`
4. Configure `config/database.php` with local credentials

### PWA Development
1. **Manifest**: Configure `manifest.json` with app details
2. **Service Worker**: Implement caching for offline job browsing
3. **Icons**: Generate icon set (192x192, 512x512, etc.)
4. **Install Prompt**: Add "Add to Home Screen" functionality

### Mobile-First Development
- Test on actual devices via local network (192.168.x.x:80/findajob)
- Use CSS Grid/Flexbox for responsive layouts
- Implement touch-friendly UI elements (44px minimum touch targets)
- Optimize images for mobile bandwidth

### Android APK Compilation
- **Option 1**: Trusted Web Activity (TWA) via Bubblewrap
- **Option 2**: Apache Cordova with WebView
- **Requirements**: HTTPS for production, proper PWA implementation

## API Design Patterns
- RESTful endpoints in `/api/` directory
- JSON responses with consistent error handling
- Authentication via PHP sessions + JWT for mobile
- Rate limiting for search endpoints
- Pagination for job listings

## Security Considerations
- CSRF protection on all forms
- SQL injection prevention (prepared statements)
- XSS protection (htmlspecialchars)
- File upload validation for resumes/logos
- Secure session handling

## Key Feature Implementation

### Job Seeker Features
- **Free Plan**: Basic search, apply, single CV, basic profile
- **Pro Plan (₦6,000/month)**: Multiple CVs, cover letter generator, advanced profile, verified status, priority in searches, daily job recommendations
- **Verification Booster (₦1,000)**: NIN/BVN verification for green tick status
- **Profile Booster (₦500/30 days)**: Enhanced visibility in employer searches

### Employer Features
- **Free Plan**: Limited job posts, basic profile
- **Pro Plan (₦30,000/month)**: Unlimited jobs, resume search, private jobs, mini-site, interview scheduling, application analytics
- **Job Boosters**: Enhanced job visibility (₦5K-₦15K depending on package)
- **Mini-Sites**: Branded sub-domains with company branding

### AI & Matching Features
- **Job Matching**: AI-powered job suggestions based on CV analysis
- **CV Generator**: Free basic AI CV creation, paid professional service
- **Search Intelligence**: Smart job search with location and skill matching
- **Application Tracking**: Detailed analytics for both job seekers and employers

### Unique Platform Features
- **Multiple Job Types**: Permanent, contract, temporary, internship, NYSC placements
- **Internship Badges**: Completion certificates that enhance profiles
- **Job Centre Integration**: Physical and online job center directory
- **Self-Employment Guides**: Training videos and entrepreneurship resources
- **Raffle System**: Marketing tool giving free prizes for engagement
- **Interview Scheduling**: Integrated Google Meet scheduling system

## Nigeria-Specific Features
- **Currency**: Naira (₦) formatting with salary ranges
- **Locations**: Complete Nigerian state/LGA database with proper hierarchy
- **Job Categories**: Tech, Oil & Gas, Banking, Government, Manufacturing, Agriculture
- **Education**: Nigerian qualification levels (SSCE, OND, HND, B.Sc, etc.)
- **Languages**: Support for major Nigerian languages in job descriptions
- **ID Verification**: Integration with NIN/BVN APIs for identity verification
- **Mobile Money**: Support for Nigerian payment methods
- **Cultural Considerations**: Appropriate job categories and cultural sensitivity

## Performance Optimization
- Image optimization and lazy loading
- CSS/JS minification for production
- Database indexing on search fields
- Caching for popular job searches
- Progressive loading for mobile

## Testing Strategy
- Cross-browser testing (Chrome, Firefox, Safari, Edge)
- Mobile device testing (Android, iOS)
- PWA functionality testing
- Database performance testing with sample Nigerian job data
- Network simulation for slow connections

---
*This file should be updated as the project evolves and new patterns emerge.*
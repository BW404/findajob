# AI-Powered CV Generator System

## Overview
The AI-Powered CV Generator is a comprehensive wizard-based system that helps job seekers create professional CVs in minutes.

## Components

### 1. CV Generator Wizard (`pages/services/cv-generator.php`)
- **6-Step Process**: Personal Info → Summary → Experience → Education → Skills → Template Selection
- **Features**:
  - Multi-step form with progress tracking
  - Form validation on each step
  - AI-powered suggestions for summaries and skills
  - Repeatable sections for experience and education
  - Live template preview and selection
  - Mobile-responsive design

### 2. CV Generation API (`api/generate-cv.php`)
- Processes form data and generates PDF CVs
- Supports multiple template rendering
- Stores CV data in JSON format for future editing
- Handles PDF generation (supports DomPDF, wkhtmltopdf, or HTML fallback)

### 3. Professional CV Templates (`templates/cv/`)

#### Modern Professional (`modern.php`)
- **Best For**: Tech professionals, corporate roles
- **Style**: Clean, minimal with gradient header
- **Colors**: Blue/Purple gradient (#667eea to #764ba2)
- **Features**: 
  - Full-width colored header
  - Skill tags with gradient backgrounds
  - Clear section hierarchy
  - PDF-optimized layout

#### Creative (`creative.php`)
- **Best For**: Designers, creative professionals, marketers
- **Style**: Two-column layout with sidebar
- **Colors**: Purple gradient (#8b5cf6 to #6d28d9)
- **Features**:
  - Colored sidebar with contact info
  - Skill bars with visual progress
  - Timeline-style experience section
  - Eye-catching design

#### Technical (`technical.php`)
- **Best For**: Developers, engineers, IT professionals
- **Style**: Code-themed with monospace fonts
- **Colors**: Red/dark theme (#ef4444, #1f2937)
- **Features**:
  - Code block styling for experience
  - Terminal-like design elements
  - Syntax highlighting colors
  - Tech stack emphasis

#### Executive (`executive.php`)
- **Best For**: Senior management, executives, directors
- **Style**: Elegant serif fonts, formal layout
- **Colors**: Gold accents (#d4af37) on dark blue (#2c3e50)
- **Features**:
  - Sophisticated typography
  - Double-line borders
  - Centered header
  - Professional appearance

#### Academic (`academic.php`)
- **Best For**: Researchers, professors, academics
- **Style**: Traditional academic format
- **Based On**: Modern template with academic styling
- **Features**:
  - Publications-friendly layout
  - Research emphasis
  - Detailed education section

#### Minimalist (`minimalist.php`)
- **Best For**: Any profession, maximum readability
- **Style**: Ultra-clean, content-focused
- **Based On**: Modern template with minimal styling
- **Features**:
  - Maximum white space
  - Simple typography
  - Content over design
  - ATS-friendly

## AI Features (Mock Implementation)

### 1. Professional Summary Generator
- Takes: Industry, experience level, job title
- Generates: 3-5 sentence professional summary
- **Examples**:
  - Technology: Emphasizes innovation, cross-functional teams, digital transformation
  - Healthcare: Focuses on patient care, clinical operations, quality improvement
  - Finance: Highlights financial planning, risk assessment, data-driven insights

### 2. Job Description Enhancer
- Improves bullet points with action verbs
- Adds quantifiable achievements
- **Note**: Currently shows alert, ready for API integration

### 3. Skills Suggester
- Provides role-specific skill recommendations
- **Pre-loaded for**:
  - Data Analyst: Python, SQL, Excel, Tableau, etc.
  - Frontend Developer: JavaScript, React, HTML5, CSS3, etc.
  - Project Manager: Agile/Scrum, JIRA, stakeholder management, etc.

## Database Schema

### CVs Table Updates
```sql
ALTER TABLE cvs ADD COLUMN cv_data TEXT; -- JSON storage for CV structure
```

### Analytics Integration
Uses the same analytics system as CV preview:
- `view_count`, `download_count`
- `cv_analytics` table for detailed tracking

## Usage Flow

1. **User clicks "Create CV with AI"** from CV Manager
2. **Step 1-2**: Personal info and professional summary
   - Pre-fills from user profile
   - AI suggestion button for summary
3. **Step 3-4**: Work experience and education
   - Add multiple entries
   - AI enhancement for job descriptions
4. **Step 5**: Skills and certifications
   - AI role-based skill suggestions
   - Categorized input (technical, soft, languages, certs)
5. **Step 6**: Template selection and generation
   - Choose from 6 templates
   - Name the CV
   - Click "Generate CV"
6. **Backend Processing**:
   - Collects all form data
   - Generates HTML from selected template
   - Converts to PDF
   - Saves to uploads/cvs/
   - Stores metadata in database
7. **Redirect to CV Preview** with analytics tracking

## PDF Generation Options

### Option 1: DomPDF (Recommended)
```bash
composer require dompdf/dompdf
```
- Pure PHP solution
- Good HTML/CSS support
- Easy to deploy

### Option 2: wkhtmltopdf
```bash
# Ubuntu/Debian
sudo apt-get install wkhtmltopdf

# Windows: Download from https://wkhtmltopdf.org/
```
- Better CSS support
- Faster rendering
- Requires system binary

### Option 3: HTML Fallback
- Browser-based PDF printing
- No dependencies
- User controls output

## File Structure
```
pages/services/cv-generator.php      # Main wizard interface
api/generate-cv.php                  # PDF generation backend
templates/cv/
  ├── modern.php                     # Modern Professional template
  ├── creative.php                   # Creative template
  ├── technical.php                  # Technical/Developer template
  ├── executive.php                  # Executive template
  ├── academic.php                   # Academic template
  └── minimalist.php                 # Minimalist template
```

## Future Enhancements

### Real AI Integration
- Integrate OpenAI GPT-4 API for:
  - Professional summary generation
  - Job description enhancement
  - Achievement quantification
  - Grammar and tone improvement

### Advanced Features
- CV editing (load from `cv_data` JSON)
- A/B testing different CV versions
- CV scoring and optimization tips
- Industry-specific templates
- Multi-language support
- Export to Word/LinkedIn/JSON

### Analytics
- Which templates are most popular
- Average completion time
- Drop-off points in wizard
- Success rates (applications per CV)

## Integration Points

### From CV Manager
```php
<a href="../services/cv-generator.php">
    <i class="fas fa-magic"></i> Start Creating
</a>
```

### To CV Preview
```javascript
window.location.href = '../user/cv-preview.php?id=' + data.cv_id;
```

### With Analytics
- Automatic tracking on view/download
- Dashboard integration
- Charts and statistics

## Security Considerations

1. **File Upload Security**:
   - Generated PDFs stored in uploads/cvs/
   - Protected by .htaccess (only PDF downloads)
   - User ownership verification

2. **Data Validation**:
   - Required field validation on each step
   - XSS prevention with htmlspecialchars()
   - SQL injection prevention with prepared statements

3. **Access Control**:
   - Only logged-in job seekers can access
   - Users can only generate for themselves
   - API endpoints verify user sessions

---

**Status**: ✅ Fully Implemented and Ready for Use
**Last Updated**: October 28, 2025

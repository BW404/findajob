/**
 * Job Search JavaScript
 * Handles search functionality, filtering, and job display
 */

class JobSearch {
    constructor() {
        this.currentPage = 1;
        this.currentFilters = this.getFiltersFromURL();
        this.jobs = [];
        this.totalJobs = 0;
        this.totalPages = 0;
        this.isLoading = false;
        this.currentView = 'list';
        
        // DOM elements
        this.jobsContainer = null;
        this.loadingSpinner = null;
        this.resultsCount = null;
        this.paginationContainer = null;
        this.searchForm = null;
        this.activeFilters = null;
    }
    
    init() {
        this.initializeElements();
        this.bindEvents();
        this.loadInitialResults();
        this.setupAutoComplete();
        
        // Setup cleanup on page unload to prevent memory leaks
        window.addEventListener('beforeunload', () => {
            this.cleanup();
        });
    }
    
    initializeElements() {
        this.jobsContainer = document.getElementById('jobsContainer');
        this.loadingSpinner = document.getElementById('loadingSpinner');
        this.resultsCount = document.getElementById('resultsCount');
        this.paginationContainer = document.getElementById('paginationContainer');
        this.searchForm = document.getElementById('jobSearchForm');
        this.activeFilters = document.getElementById('activeFilters');
        
        // Get current page from URL
        const urlParams = new URLSearchParams(window.location.search);
        this.currentPage = parseInt(urlParams.get('page')) || 1;
    }
    
    bindEvents() {
        // Search form submission
        if (this.searchForm) {
            this.searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleSearch();
            });
        }
        
        // Sort change
        const sortSelect = document.getElementById('sortBy');
        if (sortSelect) {
            sortSelect.addEventListener('change', () => {
                this.currentFilters.sort = sortSelect.value;
                this.currentPage = 1;
                this.loadJobs();
            });
        }
        
        // View toggle
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                this.toggleView(e.target.dataset.view);
            });
        });
        
        // Filter changes
        this.bindFilterEvents();
    }
    
    bindFilterEvents() {
        const filterSelects = document.querySelectorAll('.filter-select, .salary-input');
        filterSelects.forEach(select => {
            select.addEventListener('change', () => {
                this.debounce(() => {
                    this.updateFiltersFromForm();
                    this.currentPage = 1;
                    this.loadJobs();
                }, 300);
            });
        });
        
        // Keywords input with debounce
        const keywordsInput = document.getElementById('keywords');
        if (keywordsInput) {
            keywordsInput.addEventListener('input', () => {
                this.debounce(() => {
                    this.updateFiltersFromForm();
                    this.currentPage = 1;
                    this.loadJobs();
                }, 500);
            });
        }
        
        // Location input with debounce
        const locationInput = document.getElementById('location');
        if (locationInput) {
            locationInput.addEventListener('input', () => {
                this.debounce(() => {
                    this.updateFiltersFromForm();
                    this.currentPage = 1;
                    this.loadJobs();
                }, 500);
            });
        }
    }
    
    debounce(func, wait) {
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(func, wait);
    }
    
    getFiltersFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        return {
            keywords: urlParams.get('keywords') || '',
            location: urlParams.get('location') || '',
            category: urlParams.get('category') || '',
            job_type: urlParams.get('job_type') || '',
            experience_level: urlParams.get('experience_level') || '',
            salary_min: urlParams.get('salary_min') || '',
            salary_max: urlParams.get('salary_max') || '',
            sort: urlParams.get('sort') || 'newest'
        };
    }
    
    updateFiltersFromForm() {
        const formData = new FormData(this.searchForm);
        this.currentFilters = {
            keywords: formData.get('keywords') || '',
            location: formData.get('location') || '',
            category: formData.get('category') || '',
            job_type: formData.get('job_type') || '',
            experience_level: formData.get('experience_level') || '',
            salary_min: formData.get('salary_min') || '',
            salary_max: formData.get('salary_max') || '',
            sort: this.currentFilters.sort || 'newest'
        };
    }
    
    handleSearch() {
        this.updateFiltersFromForm();
        this.currentPage = 1;
        this.loadJobs();
        this.updateURL();
    }
    
    loadInitialResults() {
        this.loadJobs();
    }
    
    async loadJobs() {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.showLoading();
        
        try {
            const params = new URLSearchParams({
                ...this.currentFilters,
                page: this.currentPage,
                limit: 20
            });
            
            // Remove empty parameters
            for (const [key, value] of [...params.entries()]) {
                if (!value) {
                    params.delete(key);
                }
            }
            
            const response = await fetch(`../../api/jobs.php?${params}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.jobs = data.jobs;
                this.totalJobs = data.pagination.total_items;
                this.totalPages = data.pagination.total_pages;
                
                this.displayJobs();
                this.displayPagination();
                this.updateResultsCount();
                this.displayActiveFilters();
            } else {
                throw new Error(data.error || 'Failed to load jobs');
            }
            
        } catch (error) {
            console.error('Error loading jobs:', error);
            this.showError('Failed to load jobs. Please try again.');
        } finally {
            this.isLoading = false;
            this.hideLoading();
        }
    }
    
    displayJobs() {
        if (!this.jobsContainer) return;
        
        if (this.jobs.length === 0) {
            this.jobsContainer.innerHTML = `
                <div class="no-results">
                    <div class="no-results-icon">🔍</div>
                    <h3>No jobs found</h3>
                    <p>Try adjusting your search criteria or browse all available positions.</p>
                    <button class="btn-primary" onclick="window.location.href='browse.php'">
                        Browse All Jobs
                    </button>
                </div>
            `;
            return;
        }
        
        const jobsHTML = this.jobs.map(job => this.renderJobCard(job)).join('');
        this.jobsContainer.innerHTML = jobsHTML;
        
        // Add event listeners to job cards
        this.bindJobCardEvents();
        
        // Setup proper image loading to prevent memory issues
        this.setupImageLoading();
    }
    
    renderJobCard(job) {
        const badges = [];
        
        if (job.is_featured) {
            badges.push('<span class="job-badge featured">⭐ Featured</span>');
        }
        
        if (job.is_urgent) {
            badges.push('<span class="job-badge urgent">🔥 Urgent</span>');
        }
        
        if (job.is_remote_friendly) {
            badges.push('<span class="job-badge">🏠 Remote Friendly</span>');
        }
        
        badges.push(`<span class="job-badge">${job.job_type_formatted}</span>`);
        
        if (job.salary_formatted) {
            badges.push(`<span class="job-badge">💰 ${job.salary_formatted}</span>`);
        }
        
        // Company logo with fallback - prevent infinite loading
        let logoSrc = '../../assets/images/placeholders/company.png'; // Default fallback
        if (job.company_logo && job.company_logo.trim() !== '') {
            logoSrc = `../../uploads/logos/${job.company_logo}`;
        }
        
        // Check if job is saved (will be populated from server or localStorage)
        const isSaved = job.is_saved || false;
        const saveIcon = isSaved ? '❤️' : '🤍';
        const savedClass = isSaved ? 'saved' : '';
        
        return `
            <article class="job-card" data-job-id="${job.id}" onclick="window.location.href='details.php?id=${job.id}'">
                <div class="job-card-header">
                    <div class="company-logo">
                        <img src="${logoSrc}" alt="${job.company_name}" onerror="this.onerror=null; this.src='../../assets/images/placeholders/company.png';">
                    </div>
                    <div class="job-info">
                        <h3 class="job-title">${this.escapeHtml(job.title)}</h3>
                        <p class="company-name">
                            ${this.escapeHtml(job.company_name)}
                            ${job.employer_verified ? '<span class="verified-badge">✓</span>' : ''}
                        </p>
                        <p class="job-location">📍 ${job.location_formatted}</p>
                    </div>
                    <div class="job-actions">
                        <button class="save-job-btn ${savedClass}" data-job-id="${job.id}" title="${isSaved ? 'Unsave job' : 'Save job'}" onclick="event.stopPropagation();">
                            <span class="save-icon">${saveIcon}</span>
                        </button>
                    </div>
                </div>
                
                <div class="job-card-body">
                    <div class="job-badges">
                        ${badges.join('')}
                    </div>
                    <p class="job-description">${job.description_preview}</p>
                    
                    ${job.category_name ? `
                        <div class="job-category">
                            <span class="category-tag">
                                ${job.category_icon} ${job.category_name}
                            </span>
                        </div>
                    ` : ''}
                </div>
                
                <div class="job-footer">
                    <div class="job-meta">
                        <span class="posted-time">📅 ${job.created_at_formatted}</span>
                        <span class="view-count">👁️ ${job.views_count} views</span>
                        ${job.application_deadline ? `
                            <span class="deadline ${job.days_until_deadline < 7 ? 'urgent' : ''}">
                                ⏰ ${job.deadline_formatted}
                            </span>
                        ` : ''}
                    </div>
                    <button class="apply-btn" onclick="event.stopPropagation(); window.location.href='details.php?id=${job.id}'">
                        Read More
                    </button>
                </div>
            </article>
        `;
    }
    
    bindJobCardEvents() {
        // Save job buttons
        const saveButtons = document.querySelectorAll('.save-job-btn');
        console.log('Binding events to', saveButtons.length, 'save buttons');
        
        saveButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                e.preventDefault();
                console.log('Save button clicked for job ID:', btn.dataset.jobId);
                
                const jobId = btn.dataset.jobId;
                const isSaved = btn.classList.contains('saved');
                this.toggleSaveJob(jobId, isSaved, btn);
            });
        });
    }
    
    async toggleSaveJob(jobId, isSaved, buttonElement) {
        const action = isSaved ? 'unsave' : 'save';
        console.log('Toggle save job:', jobId, 'Action:', action);
        
        try {
            // Optimistic UI update
            const icon = buttonElement.querySelector('.save-icon');
            const originalIcon = icon.textContent;
            icon.textContent = isSaved ? '🤍' : '❤️';
            buttonElement.classList.toggle('saved');
            
            console.log('Making API call to:', '../../api/jobs.php');
            const response = await fetch('../../api/jobs.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=${action}&job_id=${jobId}`
            });
            
            console.log('API response status:', response.status);
            const data = await response.json();
            console.log('API response data:', data);
            
            if (!data.success) {
                // Revert on error
                icon.textContent = originalIcon;
                buttonElement.classList.toggle('saved');
                alert(data.message || 'Failed to ' + action + ' job');
            } else {
                // Update title
                buttonElement.title = isSaved ? 'Save job' : 'Unsave job';
            }
        } catch (error) {
            // Revert on error
            const icon = buttonElement.querySelector('.save-icon');
            icon.textContent = isSaved ? '❤️' : '🤍';
            buttonElement.classList.toggle('saved');
            console.error('Error toggling save job:', error);
            alert('An error occurred. Please try again.');
        }
    }
    
    displayPagination() {
        if (!this.paginationContainer || this.totalPages <= 1) {
            this.paginationContainer.innerHTML = '';
            return;
        }
        
        const pagination = this.createPagination();
        this.paginationContainer.innerHTML = pagination;
        
        // Bind pagination events
        this.bindPaginationEvents();
    }
    
    createPagination() {
        const pages = [];
        const maxVisiblePages = 5;
        
        // Previous button
        pages.push(`
            <button class="page-btn" 
                    ${this.currentPage === 1 ? 'disabled' : ''} 
                    data-page="${this.currentPage - 1}">
                ← Previous
            </button>
        `);
        
        // Page numbers
        let startPage = Math.max(1, this.currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(this.totalPages, startPage + maxVisiblePages - 1);
        
        if (endPage - startPage < maxVisiblePages - 1) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }
        
        // First page and ellipsis
        if (startPage > 1) {
            pages.push(`<button class="page-btn" data-page="1">1</button>`);
            if (startPage > 2) {
                pages.push(`<span class="pagination-ellipsis">...</span>`);
            }
        }
        
        // Page number buttons
        for (let i = startPage; i <= endPage; i++) {
            pages.push(`
                <button class="page-btn ${i === this.currentPage ? 'active' : ''}" 
                        data-page="${i}">
                    ${i}
                </button>
            `);
        }
        
        // Last page and ellipsis
        if (endPage < this.totalPages) {
            if (endPage < this.totalPages - 1) {
                pages.push(`<span class="pagination-ellipsis">...</span>`);
            }
            pages.push(`<button class="page-btn" data-page="${this.totalPages}">${this.totalPages}</button>`);
        }
        
        // Next button
        pages.push(`
            <button class="page-btn" 
                    ${this.currentPage === this.totalPages ? 'disabled' : ''} 
                    data-page="${this.currentPage + 1}">
                Next →
            </button>
        `);
        
        return `<div class="pagination">${pages.join('')}</div>`;
    }
    
    bindPaginationEvents() {
        document.querySelectorAll('.page-btn:not([disabled])').forEach(btn => {
            btn.addEventListener('click', () => {
                const page = parseInt(btn.dataset.page);
                if (page && page !== this.currentPage) {
                    this.currentPage = page;
                    this.loadJobs();
                    this.updateURL();
                    this.scrollToTop();
                }
            });
        });
    }
    
    updateResultsCount() {
        if (!this.resultsCount) return;
        
        if (this.totalJobs === 0) {
            this.resultsCount.textContent = 'No jobs found';
        } else {
            const start = (this.currentPage - 1) * 20 + 1;
            const end = Math.min(this.currentPage * 20, this.totalJobs);
            this.resultsCount.textContent = `Showing ${start}-${end} of ${this.totalJobs.toLocaleString()} jobs`;
        }
    }
    
    displayActiveFilters() {
        if (!this.activeFilters) return;
        
        const filters = [];
        
        if (this.currentFilters.keywords) {
            filters.push({
                label: `"${this.currentFilters.keywords}"`,
                key: 'keywords'
            });
        }
        
        if (this.currentFilters.location) {
            filters.push({
                label: `📍 ${this.currentFilters.location}`,
                key: 'location'
            });
        }
        
        if (this.currentFilters.category) {
            filters.push({
                label: `Category: ${this.currentFilters.category}`,
                key: 'category'
            });
        }
        
        if (this.currentFilters.job_type) {
            filters.push({
                label: `Type: ${this.currentFilters.job_type}`,
                key: 'job_type'
            });
        }
        
        if (this.currentFilters.experience_level) {
            filters.push({
                label: `Experience: ${this.currentFilters.experience_level}`,
                key: 'experience_level'
            });
        }
        
        if (this.currentFilters.salary_min || this.currentFilters.salary_max) {
            const min = this.currentFilters.salary_min ? `₦${parseInt(this.currentFilters.salary_min).toLocaleString()}` : '';
            const max = this.currentFilters.salary_max ? `₦${parseInt(this.currentFilters.salary_max).toLocaleString()}` : '';
            filters.push({
                label: `Salary: ${min || 'Any'} - ${max || 'Any'}`,
                key: 'salary'
            });
        }
        
        if (filters.length === 0) {
            this.activeFilters.innerHTML = '';
            return;
        }
        
        const filtersHTML = filters.map(filter => `
            <span class="filter-tag">
                ${filter.label}
                <button class="remove-filter" data-filter="${filter.key}">×</button>
            </span>
        `).join('');
        
        this.activeFilters.innerHTML = filtersHTML;
        
        // Bind remove filter events
        document.querySelectorAll('.remove-filter').forEach(btn => {
            btn.addEventListener('click', () => {
                this.removeFilter(btn.dataset.filter);
            });
        });
    }
    
    removeFilter(filterKey) {
        if (filterKey === 'salary') {
            this.currentFilters.salary_min = '';
            this.currentFilters.salary_max = '';
        } else {
            this.currentFilters[filterKey] = '';
        }
        
        this.updateFormFromFilters();
        this.currentPage = 1;
        this.loadJobs();
        this.updateURL();
    }
    
    updateFormFromFilters() {
        if (!this.searchForm) return;
        
        Object.keys(this.currentFilters).forEach(key => {
            const input = this.searchForm.querySelector(`[name="${key}"]`);
            if (input) {
                input.value = this.currentFilters[key];
            }
        });
    }
    
    toggleView(view) {
        this.currentView = view;
        
        // Update active button
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.view === view);
        });
        
        // Update jobs container class
        this.jobsContainer.className = `jobs-container view-${view}`;
        
        // Re-render jobs if needed
        this.displayJobs();
    }
    
    updateURL() {
        const params = new URLSearchParams();
        
        Object.keys(this.currentFilters).forEach(key => {
            if (this.currentFilters[key]) {
                params.set(key, this.currentFilters[key]);
            }
        });
        
        if (this.currentPage > 1) {
            params.set('page', this.currentPage);
        }
        
        const newURL = `${window.location.pathname}${params.toString() ? '?' + params.toString() : ''}`;
        window.history.pushState({}, '', newURL);
    }
    
    scrollToTop() {
        document.querySelector('.results-section').scrollIntoView({ 
            behavior: 'smooth' 
        });
    }
    
    showLoading() {
        if (this.loadingSpinner) {
            this.loadingSpinner.style.display = 'block';
        }
        if (this.jobsContainer) {
            this.jobsContainer.style.opacity = '0.6';
        }
    }
    
    hideLoading() {
        if (this.loadingSpinner) {
            this.loadingSpinner.style.display = 'none';
        }
        if (this.jobsContainer) {
            this.jobsContainer.style.opacity = '1';
        }
    }
    
    showError(message) {
        if (this.jobsContainer) {
            this.jobsContainer.innerHTML = `
                <div class="error-message">
                    <div class="error-icon">⚠️</div>
                    <h3>Oops! Something went wrong</h3>
                    <p>${message}</p>
                    <button class="btn-primary" onclick="location.reload()">
                        Try Again
                    </button>
                </div>
            `;
        }
    }
    
    setupAutoComplete() {
        // Basic autocomplete for keywords and location
        this.setupKeywordsAutoComplete();
        this.setupLocationAutoComplete();
    }
    
    setupKeywordsAutoComplete() {
        const keywordsInput = document.getElementById('keywords');
        if (!keywordsInput) return;
        
        keywordsInput.addEventListener('input', () => {
            const query = keywordsInput.value.trim();
            if (query.length < 2) return;
            
            this.debounce(() => {
                this.fetchAutoCompleteData('jobs', query).then(suggestions => {
                    this.showAutoComplete(keywordsInput, suggestions);
                });
            }, 300);
        });
    }
    
    setupLocationAutoComplete() {
        const locationInput = document.getElementById('location');
        if (!locationInput) return;
        
        locationInput.addEventListener('input', () => {
            const query = locationInput.value.trim();
            if (query.length < 2) return;
            
            this.debounce(() => {
                this.fetchAutoCompleteData('locations', query).then(suggestions => {
                    this.showAutoComplete(locationInput, suggestions);
                });
            }, 300);
        });
    }
    
    async fetchAutoCompleteData(type, query) {
        try {
            const response = await fetch(`../../api/search.php?type=${type}&q=${encodeURIComponent(query)}&limit=5`);
            const data = await response.json();
            return data.results[type] || [];
        } catch (error) {
            console.error('Autocomplete error:', error);
            return [];
        }
    }
    
    showAutoComplete(input, suggestions) {
        // Remove existing dropdown
        const existingDropdown = input.parentNode.querySelector('.autocomplete-dropdown');
        if (existingDropdown) {
            existingDropdown.remove();
        }
        
        if (suggestions.length === 0) return;
        
        const dropdown = document.createElement('div');
        dropdown.className = 'autocomplete-dropdown';
        
        suggestions.forEach(suggestion => {
            const item = document.createElement('div');
            item.className = 'autocomplete-item';
            item.innerHTML = `
                <span class="suggestion-text">${this.escapeHtml(suggestion.name || suggestion.title)}</span>
                ${suggestion.type === 'location' && suggestion.job_count ? 
                    `<span class="suggestion-meta">${suggestion.job_count} jobs</span>` : ''}
            `;
            
            item.addEventListener('click', () => {
                input.value = suggestion.name || suggestion.title;
                dropdown.remove();
                this.handleSearch();
            });
            
            dropdown.appendChild(item);
        });
        
        input.parentNode.appendChild(dropdown);
        
        // Close dropdown when clicking outside
        setTimeout(() => {
            document.addEventListener('click', (e) => {
                if (!input.parentNode.contains(e.target)) {
                    dropdown.remove();
                }
            }, { once: true });
        }, 100);
    }
    
    // Handle image loading with proper error handling to prevent memory leaks
    setupImageLoading() {
        const images = this.jobsContainer.querySelectorAll('img');
        images.forEach(img => {
            // Remove any existing error handlers to prevent duplicates
            img.onerror = null;
            
            // Set up proper error handling with single retry
            img.onerror = function() {
                // Prevent infinite loops by removing the error handler
                this.onerror = null;
                
                // Set to placeholder if not already
                if (this.src !== '../../assets/images/placeholders/company.png') {
                    this.src = '../../assets/images/placeholders/company.png';
                }
            };
            
            // Add loading optimization
            img.loading = 'lazy';
        });
    }
    
    // Clean up resources to prevent memory leaks
    cleanup() {
        // Remove event listeners and clear references
        const images = document.querySelectorAll('.job-card img');
        images.forEach(img => {
            img.onerror = null;
            img.onload = null;
        });
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Export for use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = JobSearch;
}

// Additional styles for autocomplete and enhancements
const additionalStyles = `
    .autocomplete-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #d1d5db;
        border-top: none;
        border-radius: 0 0 8px 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        z-index: 1000;
        max-height: 200px;
        overflow-y: auto;
    }
    
    .autocomplete-item {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #f3f4f6;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: background 0.2s ease;
    }
    
    .autocomplete-item:hover {
        background: var(--background);
    }
    
    .autocomplete-item:last-child {
        border-bottom: none;
    }
    
    .suggestion-text {
        color: var(--text-primary);
    }
    
    .suggestion-meta {
        font-size: 0.85rem;
        color: var(--text-secondary);
    }
    
    .no-results, .error-message {
        text-align: center;
        padding: 3rem;
        color: var(--text-secondary);
    }
    
    .no-results-icon, .error-icon {
        font-size: 4rem;
        margin-bottom: 1rem;
    }
    
    .no-results h3, .error-message h3 {
        color: var(--text-primary);
        margin-bottom: 1rem;
    }
    
    .verified-badge {
        background: #10b981;
        color: white;
        padding: 0.125rem 0.375rem;
        border-radius: 10px;
        font-size: 0.7rem;
        margin-left: 0.5rem;
    }
    
    .save-job-btn {
        background: none;
        border: none;
        padding: 0.5rem;
        cursor: pointer;
        border-radius: 50%;
        transition: background 0.2s ease;
    }
    
    .save-job-btn:hover {
        background: var(--background);
    }
    
    .save-icon {
        font-size: 1.2rem;
    }
    
    .deadline.urgent {
        color: #ef4444;
        font-weight: 600;
    }
    
    .pagination-ellipsis {
        padding: 0.5rem;
        color: var(--text-secondary);
    }
    
    .input-with-icon {
        position: relative;
    }
    
    .view-${this.currentView === 'grid' ? 'grid' : 'list'} .job-card {
        ${this.currentView === 'grid' ? `
            display: flex;
            flex-direction: column;
            height: 100%;
        ` : ''}
    }
    
    @media (max-width: 768px) {
        .autocomplete-dropdown {
            position: fixed;
            left: 1rem;
            right: 1rem;
            top: auto;
        }
    }
`;

// Inject additional styles
if (typeof document !== 'undefined') {
    const styleSheet = document.createElement('style');
    styleSheet.textContent = additionalStyles;
    document.head.appendChild(styleSheet);
}
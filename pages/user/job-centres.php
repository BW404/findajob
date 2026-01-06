<?php
require_once '../../config/database.php';
require_once '../../config/session.php';
require_once '../../includes/functions.php';

// Check if user is logged in and is a job seeker
if (!isLoggedIn()) {
    header('Location: ../auth/login.php?return=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

if (!isJobSeeker()) {
    header('Location: ../../index.php');
    exit;
}

$user_id = getCurrentUserId();
$page_title = 'Job Centres - Find Employment Assistance';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - FindAJob Nigeria</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="manifest" href="../../manifest.json">
    <meta name="theme-color" content="#dc2626">
    
    <style>
        .job-centres-hero {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }

        .hero-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        .hero-content h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .hero-content p {
            font-size: 1.1rem;
            opacity: 0.95;
            max-width: 800px;
            line-height: 1.6;
        }

        .search-section {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin: -3rem auto 2rem;
            max-width: 1200px;
            position: relative;
            z-index: 10;
        }

        .search-filters {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .search-input-wrapper {
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 0.875rem 3rem 0.875rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
        }

        .search-btn {
            position: absolute;
            right: 0.5rem;
            top: 50%;
            transform: translateY(-50%);
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .search-btn:hover {
            background: var(--primary-dark);
        }

        .filter-tags {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .filter-tag {
            padding: 0.5rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 20px;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-tag:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .filter-tag.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .filter-dropdown {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .filter-select {
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s;
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--primary);
        }

        .centres-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        .centre-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
            position: relative;
            border: 2px solid transparent;
        }

        .centre-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
            border-color: var(--primary-light);
        }

        .centre-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .centre-logo {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }

        .centre-badges {
            display: flex;
            gap: 0.5rem;
            flex-direction: column;
            align-items: flex-end;
        }

        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .badge-verified {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-government {
            background: #dcfce7;
            color: #166534;
        }

        .badge-private {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-online {
            background: #e0e7ff;
            color: #4338ca;
        }

        .badge-offline {
            background: #f3e8ff;
            color: #6b21a8;
        }

        .centre-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: #111827;
            margin: 0.75rem 0 0.5rem;
        }

        .centre-location {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 0.75rem;
        }

        .centre-rating {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .stars {
            color: #fbbf24;
            font-size: 1rem;
        }

        .rating-text {
            color: #6b7280;
            font-size: 0.85rem;
        }

        .centre-services {
            margin: 1rem 0;
        }

        .service-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .service-tag {
            padding: 0.375rem 0.75rem;
            background: #fef2f2;
            color: var(--primary);
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .centre-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
        }

        .btn-view {
            flex: 1;
            padding: 0.75rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-view:hover {
            background: var(--primary-dark);
        }

        .btn-bookmark {
            padding: 0.75rem 1rem;
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-bookmark:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .btn-bookmark.bookmarked {
            background: #fef2f2;
            border-color: var(--primary);
            color: var(--primary);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6b7280;
        }

        .empty-state svg {
            width: 120px;
            height: 120px;
            margin: 0 auto 1.5rem;
            opacity: 0.5;
        }

        .loading {
            text-align: center;
            padding: 3rem;
        }

        .spinner {
            border: 3px solid #f3f4f6;
            border-top: 3px solid var(--primary);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin: 3rem 0 2rem;
        }

        .pagination button {
            padding: 0.5rem 1rem;
            border: 2px solid #e5e7eb;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .pagination button:hover:not(:disabled) {
            border-color: var(--primary);
            color: var(--primary);
        }

        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .pagination button.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        /* View Toggle Styles */
        .view-toggle {
            display: flex;
            gap: 0.5rem;
            background: #f3f4f6;
            padding: 0.25rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .view-toggle button {
            padding: 0.5rem 1rem;
            border: none;
            background: transparent;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: #6b7280;
        }

        .view-toggle button:hover {
            color: var(--primary);
        }

        .view-toggle button.active {
            background: white;
            color: var(--primary);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        /* List View Styles */
        .centres-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        .centre-card.list-view {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 1.5rem;
            align-items: start;
        }

        .centre-card.list-view .centre-header {
            margin-bottom: 0;
        }

        .centre-card.list-view .centre-content {
            flex: 1;
        }

        .centre-card.list-view .centre-badges {
            flex-direction: row;
            align-items: center;
            gap: 0.5rem;
        }

        .centre-card.list-view .centre-actions {
            border-top: none;
            padding-top: 0;
            margin-top: 0;
            flex-direction: column;
            min-width: 150px;
        }

        .centre-card.list-view .centre-location,
        .centre-card.list-view .centre-rating {
            display: inline-flex;
            margin-right: 1.5rem;
        }

        .centre-card.list-view .service-tags {
            margin-top: 0.5rem;
        }

        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 1.75rem;
            }

            .search-section {
                margin-top: -2rem;
                padding: 1.5rem;
            }

            .search-filters {
                grid-template-columns: 1fr;
            }

            .filter-dropdown {
                grid-template-columns: 1fr;
            }

            .centres-grid {
                grid-template-columns: 1fr;
                padding: 0 1rem;
            }

            .centre-card {
                padding: 1.25rem;
            }

            .centre-card.list-view {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .centre-card.list-view .centre-actions {
                flex-direction: row;
                width: 100%;
            }

            .view-toggle {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>

    <!-- Hero Section -->
    <section class="job-centres-hero">
        <div class="hero-content">
            <h1>🏢 Job Centres Directory</h1>
            <p>Find trusted employment centres across Nigeria. Get professional guidance, skills training, and job placement assistance from government and private job centres.</p>
        </div>
    </section>

    <!-- Search Section -->
    <div class="search-section" style="max-width: 1200px; margin-left: auto; margin-right: auto;">
        <div class="search-input-wrapper">
            <input 
                type="text" 
                id="searchInput" 
                class="search-input" 
                placeholder="Search by name, location, or services..."
            >
            <button class="search-btn" onclick="searchCentres()">
                <span>🔍</span> Search
            </button>
        </div>

        <div class="filter-tags">
            <div class="filter-tag active" data-filter="all" onclick="setFilter('type', 'all')">
                <span>📍</span> All Centres
            </div>
            <div class="filter-tag" data-filter="government" onclick="setFilter('type', 'government')">
                <span>🏛️</span> Government
            </div>
            <div class="filter-tag" data-filter="private" onclick="setFilter('type', 'private')">
                <span>🏢</span> Private
            </div>
            <div class="filter-tag" data-filter="online" onclick="setFilter('category', 'online')">
                <span>💻</span> Online
            </div>
            <div class="filter-tag" data-filter="offline" onclick="setFilter('category', 'offline')">
                <span>📍</span> Offline
            </div>
        </div>

        <div class="filter-dropdown">
            <select id="stateFilter" class="filter-select">
                <option value="">All States</option>
            </select>
            
            <select id="sortFilter" class="filter-select">
                <option value="rating">Highest Rated</option>
                <option value="name">Name (A-Z)</option>
                <option value="newest">Newest First</option>
                <option value="views">Most Viewed</option>
            </select>
        </div>
    </div>

    <!-- View Toggle & Centres Container -->
    <div style="max-width: 1200px; margin: 0 auto; padding: 0 1.5rem;">
        <div class="view-toggle">
            <button class="active" onclick="setView('grid')" id="gridViewBtn">
                <span>▦</span> Grid View
            </button>
            <button onclick="setView('list')" id="listViewBtn">
                <span>☰</span> List View
            </button>
        </div>
    </div>

    <!-- Centres Grid/List -->
    <div class="centres-grid" id="centresGrid">
        <div class="loading">
            <div class="spinner"></div>
            <p>Loading job centres...</p>
        </div>
    </div>

    <!-- Pagination -->
    <div class="pagination" id="pagination" style="display: none;"></div>

    <?php include '../../includes/footer.php'; ?>

    <script>
        let currentPage = 1;
        let currentView = 'grid'; // 'grid' or 'list'
        let filters = {
            state: '',
            category: '',
            is_government: null,
            sort: 'rating',
            search: ''
        };

        // Load states
        async function loadStates() {
            try {
                const response = await fetch('../../api/locations.php?action=states');
                const data = await response.json();
                
                if (data.success) {
                    const select = document.getElementById('stateFilter');
                    data.states.forEach(state => {
                        const option = document.createElement('option');
                        option.value = state.name;
                        option.textContent = state.name;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading states:', error);
            }
        }

        // Load job centres
        async function loadCentres(page = 1) {
            currentPage = page;
            console.log('=== Loading Centres ===');
            console.log('Page:', page);
            console.log('Filters:', JSON.stringify(filters, null, 2));
            
            const grid = document.getElementById('centresGrid');
            grid.innerHTML = '<div class="loading"><div class="spinner"></div><p>Loading job centres...</p></div>';
            
            // Scroll to top of results when changing pages
            if (page !== 1) {
                window.scrollTo({ top: 200, behavior: 'smooth' });
            }
            
            try {
                // Build params object, excluding null and empty values
                const paramsObj = {
                    page: currentPage
                };
                
                // Only add filter values that are not null or empty
                if (filters.state && filters.state !== '') paramsObj.state = filters.state;
                if (filters.category && filters.category !== '') paramsObj.category = filters.category;
                if (filters.is_government !== null && filters.is_government !== '') paramsObj.is_government = filters.is_government;
                if (filters.sort && filters.sort !== '') paramsObj.sort = filters.sort;
                if (filters.search && filters.search !== '') paramsObj.search = filters.search;
                
                const params = new URLSearchParams(paramsObj);
                
                const apiUrl = `../../api/job-centres.php?action=list&${params}`;
                console.log('API URL:', apiUrl);

                const response = await fetch(apiUrl);
                const data = await response.json();
                
                console.log('API Response:', data);
                console.log('Centres count:', data.centres ? data.centres.length : 0);
                console.log('Pagination:', data.pagination);
                console.log('======================');
                
                if (data.success) {
                    displayCentres(data.centres);
                    displayPagination(data.pagination);
                } else {
                    showError(data.error || 'Failed to load job centres');
                }
            } catch (error) {
                console.error('Error:', error);
                showError('An error occurred while loading job centres');
            }
        }

        // Display centres
        function displayCentres(centres) {
            const grid = document.getElementById('centresGrid');
            
            console.log('Displaying centres:', centres.length, centres);
            
            if (centres.length === 0) {
                grid.innerHTML = `
                    <div class="empty-state" style="grid-column: 1/-1;">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <h3>No job centres found</h3>
                        <p>Try adjusting your filters or search criteria</p>
                    </div>
                `;
                return;
            }

            // Apply current view class to grid container
            if (currentView === 'list') {
                grid.className = 'centres-list';
            } else {
                grid.className = 'centres-grid';
            }

            grid.innerHTML = centres.map(centre => createCentreCard(centre)).join('');
        }

        // Create centre card HTML
        function createCentreCard(centre) {
            try {
                // Ensure services is an array
                const servicesArray = Array.isArray(centre.services) ? centre.services : 
                                     (typeof centre.services === 'string' ? JSON.parse(centre.services) : []);
                const services = servicesArray.slice(0, 3);
                const rating = parseFloat(centre.rating_avg) || 0;
                const stars = generateStars(rating);
                
                // Different layouts for grid vs list view
                if (currentView === 'list') {
                    return createListCard(centre, servicesArray, services, rating, stars);
                } else {
                    return createGridCard(centre, servicesArray, services, rating, stars);
                }
            } catch (error) {
                console.error('Error creating card for centre:', centre, error);
                return ''; // Return empty string if card creation fails
            }
        }

        // Create grid view card
        function createGridCard(centre, servicesArray, services, rating, stars) {
            return `
                <div class="centre-card" data-id="${centre.id}">
                    <div class="centre-header">
                        <div class="centre-logo">
                            ${centre.logo ? `<img src="../../uploads/job-centres/${centre.logo}" alt="${centre.name}">` : centre.name.charAt(0)}
                        </div>
                        <div class="centre-badges">
                            ${centre.is_verified ? '<span class="badge badge-verified">✓ Verified</span>' : ''}
                            <span class="badge ${centre.is_government ? 'badge-government' : 'badge-private'}">
                                ${centre.is_government ? '🏛️ Government' : '🏢 Private'}
                            </span>
                            <span class="badge ${centre.category === 'online' ? 'badge-online' : 'badge-offline'}">
                                ${centre.category === 'online' ? '💻 Online' : centre.category === 'offline' ? '📍 Offline' : '📍💻 Both'}
                            </span>
                        </div>
                    </div>

                    <h3 class="centre-name">${centre.name}</h3>

                    <div class="centre-location">
                        <span>📍</span>
                        <span>${centre.city}, ${centre.state}</span>
                    </div>

                    <div class="centre-rating">
                        <span class="stars">${stars}</span>
                        <span class="rating-text">${rating.toFixed(1)} (${centre.rating_count} reviews)</span>
                    </div>

                    ${services.length > 0 ? `
                        <div class="centre-services">
                            <div class="service-tags">
                                ${services.map(service => `
                                    <span class="service-tag">${service}</span>
                                `).join('')}
                                ${servicesArray.length > 3 ? `<span class="service-tag">+${servicesArray.length - 3} more</span>` : ''}
                            </div>
                        </div>
                    ` : ''}

                    <div class="centre-actions">
                        <button class="btn-view" onclick="viewCentre(${centre.id})">
                            View Details
                        </button>
                        <button class="btn-bookmark ${centre.is_bookmarked ? 'bookmarked' : ''}" 
                                onclick="toggleBookmark(${centre.id}, this)" 
                                title="${centre.is_bookmarked ? 'Remove bookmark' : 'Bookmark this centre'}">
                            ${centre.is_bookmarked ? '❤️' : '🤍'}
                        </button>
                    </div>
                </div>
            `;
        }

        // Create list view card
        function createListCard(centre, servicesArray, services, rating, stars) {
            return `
                <div class="centre-card list-view" data-id="${centre.id}">
                    <div class="centre-header">
                        <div class="centre-logo">
                            ${centre.logo ? `<img src="../../uploads/job-centres/${centre.logo}" alt="${centre.name}">` : centre.name.charAt(0)}
                        </div>
                    </div>

                    <div class="centre-content">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                            <h3 class="centre-name" style="margin: 0;">${centre.name}</h3>
                            <div class="centre-badges">
                                ${centre.is_verified ? '<span class="badge badge-verified">✓ Verified</span>' : ''}
                                <span class="badge ${centre.is_government ? 'badge-government' : 'badge-private'}">
                                    ${centre.is_government ? '🏛️ Government' : '🏢 Private'}
                                </span>
                                <span class="badge ${centre.category === 'online' ? 'badge-online' : 'badge-offline'}">
                                    ${centre.category === 'online' ? '💻 Online' : centre.category === 'offline' ? '📍 Offline' : '📍💻 Both'}
                                </span>
                            </div>
                        </div>

                        <div style="display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: 0.75rem;">
                            <div class="centre-location" style="margin: 0;">
                                <span>📍</span>
                                <span>${centre.city}, ${centre.state}</span>
                            </div>

                            <div class="centre-rating" style="margin: 0;">
                                <span class="stars">${stars}</span>
                                <span class="rating-text">${rating.toFixed(1)} (${centre.rating_count} reviews)</span>
                            </div>
                        </div>

                        ${services.length > 0 ? `
                            <div class="centre-services">
                                <div class="service-tags">
                                    ${services.map(service => `
                                        <span class="service-tag">${service}</span>
                                    `).join('')}
                                    ${servicesArray.length > 3 ? `<span class="service-tag">+${servicesArray.length - 3} more</span>` : ''}
                                </div>
                            </div>
                        ` : ''}
                    </div>

                    <div class="centre-actions">
                        <button class="btn-view" onclick="viewCentre(${centre.id})">
                            View Details
                        </button>
                        <button class="btn-bookmark ${centre.is_bookmarked ? 'bookmarked' : ''}" 
                                onclick="toggleBookmark(${centre.id}, this)" 
                                title="${centre.is_bookmarked ? 'Remove bookmark' : 'Bookmark this centre'}">
                            ${centre.is_bookmarked ? '❤️' : '🤍'}
                        </button>
                    </div>
                </div>
            `;
        }

        // Generate star rating HTML
        function generateStars(rating) {
            const fullStars = Math.floor(rating);
            const hasHalfStar = rating % 1 >= 0.5;
            let stars = '';
            
            for (let i = 0; i < fullStars; i++) {
                stars += '⭐';
            }
            if (hasHalfStar) {
                stars += '⭐';
            }
            for (let i = fullStars + (hasHalfStar ? 1 : 0); i < 5; i++) {
                stars += '☆';
            }
            
            return stars;
        }

        // Display pagination
        function displayPagination(pagination) {
            const container = document.getElementById('pagination');
            
            if (pagination.total_pages <= 1) {
                container.style.display = 'none';
                return;
            }

            container.style.display = 'flex';
            
            const currentPage = pagination.page;
            const totalPages = pagination.total_pages;
            let pages = [];
            
            // Always show first page
            pages.push(1);
            
            // Calculate range around current page
            let rangeStart = Math.max(2, currentPage - 2);
            let rangeEnd = Math.min(totalPages - 1, currentPage + 2);
            
            // Add ellipsis after first page if needed
            if (rangeStart > 2) {
                pages.push('...');
            }
            
            // Add pages around current page
            for (let i = rangeStart; i <= rangeEnd; i++) {
                pages.push(i);
            }
            
            // Add ellipsis before last page if needed
            if (rangeEnd < totalPages - 1) {
                pages.push('...');
            }
            
            // Always show last page (if more than 1 page)
            if (totalPages > 1) {
                pages.push(totalPages);
            }
            
            // Remove duplicates
            pages = [...new Set(pages)];
            
            container.innerHTML = `
                <button ${currentPage === 1 ? 'disabled' : ''} 
                        onclick="loadCentres(${currentPage - 1})"
                        title="Previous page">
                    ← Previous
                </button>
                
                ${pages.map(page => {
                    if (page === '...') {
                        return '<span style="padding: 0 0.5rem; color: #6b7280;">...</span>';
                    }
                    return `
                        <button class="${page === currentPage ? 'active' : ''}" 
                                onclick="loadCentres(${page})"
                                title="Page ${page}">
                            ${page}
                        </button>
                    `;
                }).join('')}
                
                <button ${currentPage === totalPages ? 'disabled' : ''} 
                        onclick="loadCentres(${currentPage + 1})"
                        title="Next page">
                    Next →
                </button>
                
                <span style="margin-left: 1rem; color: #6b7280; font-size: 0.875rem; display: flex; align-items: center;">
                    Page ${currentPage} of ${totalPages} (${pagination.total} centres)
                </span>
            `;
        }

        // Set filter
        function setFilter(type, value) {
            const tags = document.querySelectorAll('.filter-tag');
            tags.forEach(tag => tag.classList.remove('active'));
            event.currentTarget.classList.add('active');

            if (type === 'type') {
                if (value === 'all') {
                    filters.is_government = null;
                    filters.category = ''; // Clear category filter too
                } else if (value === 'government') {
                    filters.is_government = 1;
                    filters.category = ''; // Clear category filter
                } else if (value === 'private') {
                    filters.is_government = 0;
                    filters.category = ''; // Clear category filter
                }
            } else if (type === 'category') {
                filters.category = value;
                filters.is_government = null;
            }

            console.log('Filter changed:', type, value, 'Current filters:', filters);
            loadCentres(1); // Reset to page 1
        }

        // Set view (grid or list)
        function setView(view) {
            currentView = view;
            
            // Update button states
            document.getElementById('gridViewBtn').classList.toggle('active', view === 'grid');
            document.getElementById('listViewBtn').classList.toggle('active', view === 'list');
            
            // Re-render current centres with new view
            const grid = document.getElementById('centresGrid');
            if (grid.children.length > 0 && !grid.querySelector('.loading') && !grid.querySelector('.empty-state')) {
                // Get current centres data from DOM
                loadCentres(currentPage); // Reload to apply new view
            }
            
            // Save preference to localStorage
            localStorage.setItem('jobCentresView', view);
        }

        // Search centres
        function searchCentres() {
            filters.search = document.getElementById('searchInput').value.trim();
            console.log('Search triggered:', filters.search);
            loadCentres(1); // Reset to page 1
        }

        // View centre details
        function viewCentre(id) {
            window.location.href = `job-centre-details.php?id=${id}`;
        }

        // Toggle bookmark
        async function toggleBookmark(id, button) {
            try {
                const isBookmarked = button.classList.contains('bookmarked');
                const action = isBookmarked ? 'remove_bookmark' : 'bookmark';
                
                const response = await fetch(`../../api/job-centres.php?action=${action}`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ job_centre_id: id })
                });

                const data = await response.json();
                
                if (data.success) {
                    button.classList.toggle('bookmarked');
                    button.textContent = isBookmarked ? '🤍' : '❤️';
                    button.title = isBookmarked ? 'Bookmark this centre' : 'Remove bookmark';
                } else {
                    alert(data.error || 'Failed to update bookmark');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred');
            }
        }

        // Show error
        function showError(message) {
            const grid = document.getElementById('centresGrid');
            grid.innerHTML = `
                <div class="empty-state" style="grid-column: 1/-1; color: var(--primary);">
                    <h3>⚠️ Error</h3>
                    <p>${message}</p>
                </div>
            `;
        }

        // Update filters from dropdown
        document.getElementById('stateFilter').addEventListener('change', function() {
            filters.state = this.value;
            console.log('State filter changed to:', this.value);
            loadCentres(1); // Reset to page 1
        });

        document.getElementById('sortFilter').addEventListener('change', function() {
            filters.sort = this.value;
            console.log('Sort changed to:', this.value);
            loadCentres(1); // Reset to page 1
        });

        // Search on Enter key
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchCentres();
            }
        });

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Restore saved view preference
            const savedView = localStorage.getItem('jobCentresView');
            if (savedView) {
                currentView = savedView;
                document.getElementById('gridViewBtn').classList.toggle('active', savedView === 'grid');
                document.getElementById('listViewBtn').classList.toggle('active', savedView === 'list');
            }
            
            loadStates();
            loadCentres();
        });
    </script>
</body>
</html>

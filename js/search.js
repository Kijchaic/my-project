/**
 * Search Template JavaScript
 * Handles search functionality for different product types
 */

// Global variables
let selectedCountries = [];
let maxPrice = 50;
let maxData = 25;
let maxDays = 35;
let searchTerm = '';
let searchTimeout;

// Initialize search functionality
function initializeSearch() {
    setupEventListeners();
    performSearch();
}

// Setup all event listeners
function setupEventListeners() {
    // Search input with debouncing
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                searchTerm = e.target.value;
                performSearch();
            }, 300);
        });
    }

    // Country checkboxes
    document.addEventListener('change', (e) => {
        if (e.target.classList.contains('country-checkbox')) {
            const country = e.target.value;
            if (e.target.checked) {
                selectedCountries.push(country);
            } else {
                selectedCountries = selectedCountries.filter(c => c !== country);
            }
            renderSelectedCountries();
            performSearch();
        }
    });

    // Price range
    const priceRange = document.getElementById('priceRange');
    if (priceRange) {
        priceRange.addEventListener('input', (e) => {
            maxPrice = parseInt(e.target.value);
            document.getElementById('priceDisplay').textContent = `$0 - $${maxPrice}`;
            performSearch();
        });
    }

    // Data range (for eSIM)
    const dataRange = document.getElementById('dataRange');
    if (dataRange) {
        dataRange.addEventListener('input', (e) => {
            maxData = parseInt(e.target.value);
            document.getElementById('dataDisplay').textContent = `0GB - ${maxData}GB`;
            performSearch();
        });
    }

    // Days range (for eSIM)
    const daysRange = document.getElementById('daysRange');
    if (daysRange) {
        daysRange.addEventListener('input', (e) => {
            maxDays = parseInt(e.target.value);
            document.getElementById('daysDisplay').textContent = `0 - ${maxDays} days`;
            performSearch();
        });
    }

    // Clear filters buttons
    const clearFiltersBtn = document.getElementById('clearFilters');
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', clearFilters);
    }

    const clearFiltersFromNoResultsBtn = document.getElementById('clearFiltersFromNoResults');
    if (clearFiltersFromNoResultsBtn) {
        clearFiltersFromNoResultsBtn.addEventListener('click', clearFilters);
    }
}

// Perform search via AJAX
function performSearch() {
    const searchData = {
        search_term: searchTerm,
        filters: {
            max_price: maxPrice,
            countries: selectedCountries
        }
    };

    // Add eSIM specific filters
    if (productType === 'esim') {
        searchData.filters.max_data = maxData;
        searchData.filters.max_days = maxDays;
    }

    // Show loading state
    const resultsContainer = document.getElementById('resultsContainer');
    const resultsCount = document.getElementById('resultsCount');
    
    if (resultsContainer) {
        resultsContainer.innerHTML = '<div class="text-center py-8"><div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600 mx-auto"></div></div>';
    }
    
    if (resultsCount) {
        resultsCount.textContent = 'Searching...';
    }

    // Make AJAX request
    fetch('api/search.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(searchData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            renderResults(data.results);
            updateResultsCount(data.count);
        } else {
            showError('Search failed. Please try again.');
        }
    })
    .catch(error => {
        console.error('Search error:', error);
        showError('Search failed. Please try again.');
    });
}

// Render search results
function renderResults(results) {
    const container = document.getElementById('resultsContainer');
    const noResults = document.getElementById('noResults');

    if (!container) return;

    if (results.length === 0) {
        container.innerHTML = '';
        if (noResults) {
            noResults.classList.remove('hidden');
        }
    } else {
        if (noResults) {
            noResults.classList.add('hidden');
        }
        
        container.innerHTML = results.map((item, index) => {
            return createResultCard(item, index);
        }).join('');
    }
}

// Create result card based on product type
function createResultCard(item, index) {
    const baseCard = `
        <div class="glass-effect rounded-3xl shadow-lg p-6 card-hover animate-fade-in" style="animation-delay: ${index * 100}ms">
            <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-6">
                <div class="flex-1">
                    <div class="flex items-start justify-between mb-4">
                        <h4 class="text-2xl font-bold text-gray-800">${escapeHtml(item.name)}</h4>
                        <div class="text-right xl:hidden">
                            <div class="text-3xl font-bold gradient-text">$${item.price}</div>
                            ${item.days ? `<div class="text-sm text-gray-500">$${(item.price / item.days).toFixed(2)}/day</div>` : ''}
                        </div>
                    </div>
                    
                    ${item.description ? `<p class="text-gray-600 mb-4">${escapeHtml(item.description)}</p>` : ''}
                    
                    ${createProductSpecificContent(item)}
                </div>
                
                <div class="flex items-center space-x-6">
                    <div class="text-right hidden xl:block">
                        <div class="text-4xl font-bold gradient-text mb-1">$${item.price}</div>
                        ${item.days ? `<div class="text-sm text-gray-500 bg-gray-100 px-3 py-1 rounded-full">$${(item.price / item.days).toFixed(2)}/day</div>` : ''}
                    </div>
                    <button class="btn-primary px-8 py-4 text-white font-bold rounded-2xl text-lg whitespace-nowrap" onclick="selectProduct(${item.id})">
                        Select ${productType === 'esim' ? 'Plan' : productType === 'hotel' ? 'Hotel' : productType === 'flight' ? 'Flight' : 'Car'}
                    </button>
                </div>
            </div>
        </div>
    `;

    return baseCard;
}

// Create product-specific content
function createProductSpecificContent(item) {
    if (productType === 'esim') {
        const countries = JSON.parse(item.countries || '[]');
        return `
            <div class="flex flex-wrap gap-2 mb-4">
                ${countries.map(country => `
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gradient-to-r from-purple-100 to-blue-100 text-purple-700 border border-purple-200">
                        ${escapeHtml(country)}
                    </span>
                `).join('')}
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div class="flex items-center space-x-3 bg-blue-50/80 rounded-xl p-3">
                    <div class="bg-blue-500 rounded-lg p-2">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
                        </svg>
                    </div>
                    <div>
                        <div class="font-bold text-gray-800">${item.data_gb}GB</div>
                        <div class="text-sm text-gray-600">Data</div>
                    </div>
                </div>
                <div class="flex items-center space-x-3 bg-green-50/80 rounded-xl p-3">
                    <div class="bg-green-500 rounded-lg p-2">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <div class="font-bold text-gray-800">${item.days}</div>
                        <div class="text-sm text-gray-600">Days</div>
                    </div>
                </div>
            </div>
        `;
    }
    
    return '';
}

// Render selected countries tags
function renderSelectedCountries() {
    const container = document.getElementById('selectedCountries');
    if (!container) return;

    if (selectedCountries.length > 0) {
        container.innerHTML = selectedCountries.map(country => `
            <span class="inline-flex items-center px-3 py-2 rounded-xl text-sm font-medium bg-gradient-to-r from-purple-500 to-blue-500 text-white shadow-lg">
                ${escapeHtml(country)}
                <svg class="w-4 h-4 ml-2 cursor-pointer hover:bg-white/20 rounded-full" onclick="removeCountry('${escapeHtml(country)}')" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </span>
        `).join('');
    } else {
        container.innerHTML = '';
    }
}

// Remove country from selection
function removeCountry(country) {
    selectedCountries = selectedCountries.filter(c => c !== country);
    const checkbox = document.querySelector(`input[value="${escapeHtml(country)}"]`);
    if (checkbox) {
        checkbox.checked = false;
    }
    renderSelectedCountries();
    performSearch();
}

// Clear all filters
function clearFilters() {
    selectedCountries = [];
    maxPrice = productConfig.price_range ? productConfig.price_range[1] : 50;
    maxData = productConfig.data_range ? productConfig.data_range[1] : 25;
    maxDays = productConfig.days_range ? productConfig.days_range[1] : 35;
    searchTerm = '';

    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.value = '';
    }

    const priceRange = document.getElementById('priceRange');
    if (priceRange) {
        priceRange.value = maxPrice;
        document.getElementById('priceDisplay').textContent = `$0 - $${maxPrice}`;
    }

    const dataRange = document.getElementById('dataRange');
    if (dataRange) {
        dataRange.value = maxData;
        document.getElementById('dataDisplay').textContent = `0GB - ${maxData}GB`;
    }

    const daysRange = document.getElementById('daysRange');
    if (daysRange) {
        daysRange.value = maxDays;
        document.getElementById('daysDisplay').textContent = `0 - ${maxDays} days`;
    }

    document.querySelectorAll('.country-checkbox').forEach(cb => cb.checked = false);
    
    renderSelectedCountries();
    performSearch();
}

// Update results count
function updateResultsCount(count) {
    const resultsCount = document.getElementById('resultsCount');
    if (resultsCount) {
        const productName = productType === 'esim' ? 'eSIM plans' : 
                           productType === 'hotel' ? 'hotels' : 
                           productType === 'flight' ? 'flights' : 'cars';
        resultsCount.textContent = `${count} ${productName} found`;
    }
}

// Show error message
function showError(message) {
    const container = document.getElementById('resultsContainer');
    if (container) {
        container.innerHTML = `
            <div class="text-center py-8">
                <div class="text-red-500 mb-4">
                    <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <p class="text-gray-600">${escapeHtml(message)}</p>
            </div>
        `;
    }
}

// Select product (placeholder function)
function selectProduct(productId) {
    // This function can be customized based on requirements
    console.log(`Selected ${productType} with ID:`, productId);
    alert(`Selected ${productType} with ID: ${productId}`);
}

// Utility function to escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
} 
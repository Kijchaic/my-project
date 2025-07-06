<?php
/**
 * Reusable Search Template Class
 * This class provides a template for creating search functionality for different product types
 */

require_once __DIR__ . '/../config/database.php';

class SearchTemplate {
    private $db;
    private $productType;
    private $config;
    private $filters;
    private $searchTerm;

    public function __construct($productType = 'esim') {
        $this->db = Database::getInstance();
        $this->productType = $productType;
        $this->config = $this->getProductConfig($productType);
        $this->filters = [];
        $this->searchTerm = '';
    }

    /**
     * Get product-specific configuration
     */
    private function getProductConfig($type) {
        $configs = [
            'esim' => [
                'title' => 'eSIM Search & Compare',
                'subtitle' => 'Find the perfect eSIM plan for your travel needs',
                'filters' => ['countries', 'price', 'data', 'days'],
                'price_range' => [0, 50],
                'data_range' => [0, 25],
                'days_range' => [0, 35],
                'icon' => 'wifi',
                'theme' => 'theme-esim'
            ],
            'hotel' => [
                'title' => 'Hotel Search & Compare',
                'subtitle' => 'Find the perfect hotel for your stay',
                'filters' => ['location', 'price', 'stars', 'amenities'],
                'price_range' => [0, 500],
                'stars_range' => [1, 5],
                'amenities' => ['wifi', 'pool', 'gym', 'restaurant', 'spa'],
                'icon' => 'building',
                'theme' => 'theme-hotel'
            ],
            'flight' => [
                'title' => 'Flight Search & Compare',
                'subtitle' => 'Find the best flight deals for your journey',
                'filters' => ['origin', 'destination', 'price', 'airline', 'stops'],
                'price_range' => [0, 2000],
                'airlines' => ['Thai Airways', 'Singapore Airlines', 'Emirates', 'Qatar Airways'],
                'icon' => 'plane',
                'theme' => 'theme-flight'
            ],
            'car' => [
                'title' => 'Car Rental Search & Compare',
                'subtitle' => 'Find the perfect rental car for your trip',
                'filters' => ['location', 'price', 'type', 'transmission'],
                'price_range' => [0, 200],
                'car_types' => ['Economy', 'Compact', 'Midsize', 'SUV', 'Luxury'],
                'transmissions' => ['Automatic', 'Manual'],
                'icon' => 'car',
                'theme' => 'theme-car'
            ]
        ];

        return $configs[$type] ?? $configs['esim'];
    }

    /**
     * Set search filters
     */
    public function setFilters($filters) {
        $this->filters = array_merge($this->filters, $filters);
        return $this;
    }

    /**
     * Set search term
     */
    public function setSearchTerm($term) {
        $this->searchTerm = trim($term);
        return $this;
    }

    /**
     * Search products from database
     */
    public function searchProducts() {
        try {
            $sql = "SELECT * FROM products WHERE type = ? AND is_active = 1";
            $params = [$this->productType];

            // Add search term filter
            if (!empty($this->searchTerm)) {
                $sql .= " AND (name LIKE ? OR description LIKE ?)";
                $searchPattern = "%{$this->searchTerm}%";
                $params[] = $searchPattern;
                $params[] = $searchPattern;
            }

            // Add price filter
            if (isset($this->filters['max_price'])) {
                $sql .= " AND price <= ?";
                $params[] = $this->filters['max_price'];
            }

            // Add data filter (for eSIM)
            if ($this->productType === 'esim' && isset($this->filters['max_data'])) {
                $sql .= " AND data_gb <= ?";
                $params[] = $this->filters['max_data'];
            }

            // Add days filter (for eSIM)
            if ($this->productType === 'esim' && isset($this->filters['max_days'])) {
                $sql .= " AND days <= ?";
                $params[] = $this->filters['max_days'];
            }

            // Add countries filter (for eSIM)
            if ($this->productType === 'esim' && !empty($this->filters['countries'])) {
                $placeholders = str_repeat('?,', count($this->filters['countries']) - 1) . '?';
                $sql .= " AND JSON_OVERLAPS(countries, JSON_ARRAY($placeholders))";
                $params = array_merge($params, $this->filters['countries']);
            }

            $sql .= " ORDER BY price ASC";

            $results = $this->db->fetchAll($sql, $params);

            // Log search for analytics
            $this->logSearch($results);

            return $results;

        } catch (Exception $e) {
            error_log("Search failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Log search for analytics
     */
    private function logSearch($results) {
        try {
            $logData = [
                $this->searchTerm,
                json_encode($this->filters),
                $this->productType,
                count($results),
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ];

            $sql = "INSERT INTO search_logs (search_term, filters, product_type, results_count, user_ip, user_agent) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $this->db->executeQuery($sql, $logData);

        } catch (Exception $e) {
            error_log("Search logging failed: " . $e->getMessage());
        }
    }

    /**
     * Get available countries for eSIM
     */
    public function getAvailableCountries() {
        if ($this->productType !== 'esim') {
            return [];
        }

        try {
            $sql = "SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(countries, '$[*]')) as country 
                    FROM products 
                    WHERE type = 'esim' AND is_active = 1";
            
            $results = $this->db->fetchAll($sql);
            $countries = [];
            
            foreach ($results as $row) {
                $countryArray = json_decode($row['country'], true);
                if (is_array($countryArray)) {
                    $countries = array_merge($countries, $countryArray);
                }
            }
            
            return array_unique($countries);

        } catch (Exception $e) {
            error_log("Failed to get countries: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Render the search interface HTML
     */
    public function renderSearchInterface() {
        $config = $this->config;
        $countries = $this->getAvailableCountries();
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?= htmlspecialchars($config['title']) ?></title>
            <script src="https://cdn.tailwindcss.com"></script>
            <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
            <link rel="stylesheet" href="styles/main.css">
        </head>
        <body class="bg-gradient-to-br from-purple-50 via-blue-50 to-indigo-50 min-h-screen <?= $config['theme'] ?>">
            <div class="container mx-auto px-4 py-8 max-w-7xl">
                <!-- Header -->
                <div class="text-center mb-12">
                    <h1 class="text-5xl font-bold gradient-text mb-4">
                        <?= htmlspecialchars($config['title']) ?>
                    </h1>
                    <p class="text-gray-600 text-lg max-w-2xl mx-auto">
                        <?= htmlspecialchars($config['subtitle']) ?>
                    </p>
                </div>

                <!-- Search Bar -->
                <div class="relative mb-8 max-w-2xl mx-auto">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input
                        type="text"
                        id="searchInput"
                        placeholder="Search by name or description..."
                        class="search-input block w-full pl-12 pr-4 py-4 text-lg border-2 border-purple-200 rounded-2xl focus:border-purple-500 focus:outline-none transition-all duration-300 glass-effect"
                    />
                </div>

                <div class="grid lg:grid-cols-4 gap-8">
                    <!-- Filters Sidebar -->
                    <div class="lg:col-span-1">
                        <div class="glass-effect rounded-3xl shadow-xl p-6 sticky top-8">
                            <div class="flex items-center justify-between mb-6">
                                <h2 class="text-xl font-bold text-gray-800 flex items-center">
                                    <svg class="w-5 h-5 mr-3 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.707A1 1 0 013 7V4z"></path>
                                    </svg>
                                    Filters
                                </h2>
                                <button id="clearFilters" class="text-sm text-purple-600 hover:text-purple-800 font-semibold transition-colors">
                                    Clear All
                                </button>
                            </div>

                            <div class="space-y-8">
                                <?php if ($this->productType === 'esim' && !empty($countries)): ?>
                                <!-- Countries Filter -->
                                <div>
                                    <div class="flex items-center space-x-3 mb-4">
                                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <span class="font-semibold text-gray-700">Countries</span>
                                    </div>
                                    <div id="countriesContainer" class="max-h-48 overflow-y-auto space-y-3 bg-gray-50/80 rounded-xl p-4 border border-gray-200">
                                        <?php foreach ($countries as $country): ?>
                                        <label class="flex items-center space-x-3 cursor-pointer hover:bg-white/60 rounded-lg p-2 transition-colors group">
                                            <input type="checkbox" value="<?= htmlspecialchars($country) ?>" class="custom-checkbox country-checkbox" />
                                            <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900"><?= htmlspecialchars($country) ?></span>
                                        </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <div id="selectedCountries" class="flex flex-wrap gap-2 mt-4">
                                        <!-- Selected countries tags will appear here -->
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Price Range -->
                                <div>
                                    <div class="flex items-center space-x-3 mb-4">
                                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                        </svg>
                                        <span class="font-semibold text-gray-700">Price Range</span>
                                    </div>
                                    <div class="px-2">
                                        <input type="range" id="priceRange" min="<?= $config['price_range'][0] ?>" max="<?= $config['price_range'][1] ?>" value="<?= $config['price_range'][1] ?>" class="range-slider w-full" />
                                        <div class="flex justify-between text-sm text-gray-500 mt-3">
                                            <span class="font-medium">$<?= $config['price_range'][0] ?></span>
                                            <span id="priceDisplay" class="font-bold text-purple-600 bg-purple-50 px-3 py-1 rounded-full">$<?= $config['price_range'][0] ?> - $<?= $config['price_range'][1] ?></span>
                                            <span class="font-medium">$<?= $config['price_range'][1] ?></span>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($this->productType === 'esim'): ?>
                                <!-- Data Range -->
                                <div>
                                    <div class="flex items-center space-x-3 mb-4">
                                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
                                        </svg>
                                        <span class="font-semibold text-gray-700">Data Allowance</span>
                                    </div>
                                    <div class="px-2">
                                        <input type="range" id="dataRange" min="<?= $config['data_range'][0] ?>" max="<?= $config['data_range'][1] ?>" value="<?= $config['data_range'][1] ?>" class="range-slider w-full" />
                                        <div class="flex justify-between text-sm text-gray-500 mt-3">
                                            <span class="font-medium"><?= $config['data_range'][0] ?>GB</span>
                                            <span id="dataDisplay" class="font-bold text-purple-600 bg-purple-50 px-3 py-1 rounded-full"><?= $config['data_range'][0] ?>GB - <?= $config['data_range'][1] ?>GB</span>
                                            <span class="font-medium"><?= $config['data_range'][1] ?>GB</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Days Range -->
                                <div>
                                    <div class="flex items-center space-x-3 mb-4">
                                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        <span class="font-semibold text-gray-700">Duration</span>
                                    </div>
                                    <div class="px-2">
                                        <input type="range" id="daysRange" min="<?= $config['days_range'][0] ?>" max="<?= $config['days_range'][1] ?>" value="<?= $config['days_range'][1] ?>" class="range-slider w-full" />
                                        <div class="flex justify-between text-sm text-gray-500 mt-3">
                                            <span class="font-medium"><?= $config['days_range'][0] ?> days</span>
                                            <span id="daysDisplay" class="font-bold text-purple-600 bg-purple-50 px-3 py-1 rounded-full"><?= $config['days_range'][0] ?> - <?= $config['days_range'][1] ?> days</span>
                                            <span class="font-medium"><?= $config['days_range'][1] ?> days</span>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Results -->
                    <div class="lg:col-span-3">
                        <div class="mb-6 flex items-center justify-between">
                            <h3 id="resultsCount" class="text-2xl font-bold text-gray-800">
                                Loading...
                            </h3>
                            <div class="text-sm text-gray-500 bg-white/60 px-4 py-2 rounded-full">
                                Sorted by price
                            </div>
                        </div>

                        <div id="resultsContainer" class="space-y-6">
                            <!-- Results will be populated by JavaScript -->
                        </div>

                        <!-- No Results Message -->
                        <div id="noResults" class="text-center py-16 hidden">
                            <div class="glass-effect rounded-3xl p-12 max-w-md mx-auto">
                                <div class="text-gray-400 mb-6">
                                    <svg class="w-20 h-20 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-2xl font-bold text-gray-700 mb-3">No <?= $this->productType ?>s found</h3>
                                <p class="text-gray-500 mb-6">Try adjusting your filters or search criteria to find more options</p>
                                <button id="clearFiltersFromNoResults" class="btn-primary px-8 py-3 text-white font-semibold rounded-xl">
                                    Clear All Filters
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                // Product type configuration
                const productConfig = <?= json_encode($config) ?>;
                const productType = '<?= $this->productType ?>';
                
                // Initialize search functionality
                document.addEventListener('DOMContentLoaded', function() {
                    initializeSearch();
                });
            </script>
            <script src="js/search.js"></script>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Get search results as JSON for AJAX requests
     */
    public function getSearchResultsJson() {
        $results = $this->searchProducts();
        return json_encode([
            'success' => true,
            'results' => $results,
            'count' => count($results)
        ]);
    }
}
?> 
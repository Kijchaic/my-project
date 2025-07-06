<?php
/**
 * Search API Endpoint
 * Handles AJAX search requests from the frontend
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

require_once __DIR__ . '/../includes/SearchTemplate.php';

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }

    // Determine product type from URL or input
    $productType = $_GET['type'] ?? $input['product_type'] ?? 'esim';
    
    // Validate product type
    $validTypes = ['esim', 'hotel', 'flight', 'car'];
    if (!in_array($productType, $validTypes)) {
        throw new Exception('Invalid product type');
    }

    // Create search template instance
    $search = new SearchTemplate($productType);

    // Set search parameters
    if (isset($input['search_term'])) {
        $search->setSearchTerm($input['search_term']);
    }

    if (isset($input['filters'])) {
        $search->setFilters($input['filters']);
    }

    // Perform search
    $results = $search->searchProducts();

    // Return results
    echo json_encode([
        'success' => true,
        'results' => $results,
        'count' => count($results),
        'product_type' => $productType
    ]);

} catch (Exception $e) {
    error_log("Search API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Search failed. Please try again later.'
    ]);
}
?> 
<?php
/**
 * Hotel Search Page Example
 * Demonstrates how to use the SearchTemplate for hotel products
 */

require_once 'includes/SearchTemplate.php';

// Create hotel search instance
$search = new SearchTemplate('hotel');

// Handle search requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Set search parameters from form
    if (isset($_POST['search_term'])) {
        $search->setSearchTerm($_POST['search_term']);
    }
    
    if (isset($_POST['filters'])) {
        $search->setFilters($_POST['filters']);
    }
}

// Render the search interface
echo $search->renderSearchInterface();
?> 
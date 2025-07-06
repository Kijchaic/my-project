<?php
/**
 * Setup Script
 * Initializes the database and inserts sample data
 */

require_once 'config/database.php';

echo "<h1>Database Setup</h1>";

try {
    // Create database schema
    echo "<h2>Creating database schema...</h2>";
    if (createDatabaseSchema()) {
        echo "<p style='color: green;'>✓ Database schema created successfully!</p>";
    } else {
        echo "<p style='color: red;'>✗ Failed to create database schema.</p>";
        exit(1);
    }

    // Insert sample data
    echo "<h2>Inserting sample data...</h2>";
    if (insertSampleData()) {
        echo "<p style='color: green;'>✓ Sample data inserted successfully!</p>";
    } else {
        echo "<p style='color: red;'>✗ Failed to insert sample data.</p>";
        exit(1);
    }

    echo "<h2>Setup Complete!</h2>";
    echo "<p>Your search system is now ready to use.</p>";
    echo "<p><a href='esim-search.php'>View eSIM Search Page</a></p>";
    echo "<p><a href='hotel-search.php'>View Hotel Search Page</a></p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>Setup failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check your database configuration in config/database.php</p>";
}
?> 
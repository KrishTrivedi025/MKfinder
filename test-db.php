<?php
/**
 * Database Connection Test Page
 * Test the PostgreSQL database connection and basic operations
 */

require_once 'config.php';
require_once 'database.php';

// Set headers
header('Content-Type: application/json');

try {
    // Test database connection
    $connectionTest = testDatabaseConnection();
    
    if (!$connectionTest) {
        sendJSONResponse([
            'success' => false,
            'message' => 'Database connection failed'
        ], 500);
    }
    
    // Get database instance
    $db = getDatabase();
    
    // Test getting all species
    $allSpecies = $db->getAllSpecies();
    
    // Test getting a specific species
    $robinInfo = $db->getSpeciesByName('American Robin');
    
    // Test counting identifications
    $totalIdentifications = $db->countIdentifications();
    
    // Test getting recent identifications
    $recentIdentifications = $db->getRecentIdentifications(5);
    
    // Test getting species statistics
    $statistics = $db->getSpeciesStatistics();
    
    // Return test results
    sendJSONResponse([
        'success' => true,
        'message' => 'Database connection and operations successful',
        'data' => [
            'connection_test' => $connectionTest,
            'total_species' => count($allSpecies),
            'species_names' => array_column($allSpecies, 'name'),
            'robin_found' => !empty($robinInfo),
            'total_identifications' => $totalIdentifications,
            'recent_identifications_count' => count($recentIdentifications),
            'species_statistics_count' => count($statistics),
            'database_url_configured' => !empty(getenv('DATABASE_URL'))
        ]
    ]);
    
} catch (Exception $e) {
    logError('Database test failed', ['error' => $e->getMessage()]);
    
    sendJSONResponse([
        'success' => false,
        'message' => 'Database test failed: ' . $e->getMessage(),
        'data' => [
            'database_url_configured' => !empty(getenv('DATABASE_URL'))
        ]
    ], 500);
}
?>
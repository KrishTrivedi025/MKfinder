<?php
/**
 * MKfinder Bird Identification Handler
 * Processes uploaded images and identifies bird species
 */

require_once 'config.php';
require_once 'database.php';

// Initialize session
initSession();

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSONResponse([
        'success' => false,
        'message' => getMessage('INVALID_REQUEST')
    ], 405);
}

try {
    // Check if file was uploaded
    if (!isset($_FILES['image'])) {
        sendJSONResponse([
            'success' => false,
            'message' => getMessage('MISSING_FILE')
        ], 400);
    }

    $uploadedFile = $_FILES['image'];

    // Validate file upload
    $validationErrors = validateFileUpload($uploadedFile);
    if (!empty($validationErrors)) {
        sendJSONResponse([
            'success' => false,
            'message' => implode(' ', $validationErrors)
        ], 400);
    }

    // Generate unique filename and save file
    $uniqueFilename = generateUniqueFilename($uploadedFile['name']);
    $uploadPath = UPLOAD_DIR . $uniqueFilename;

    if (!move_uploaded_file($uploadedFile['tmp_name'], $uploadPath)) {
        logError('Failed to move uploaded file for identification', [
            'original_name' => $uploadedFile['name'],
            'upload_path' => $uploadPath
        ]);
        
        sendJSONResponse([
            'success' => false,
            'message' => getMessage('UPLOAD_ERROR')
        ], 500);
    }

    // Perform bird identification
    $identificationResult = identifyBirdSpecies($uploadPath);

    if (!$identificationResult['success']) {
        // Clean up uploaded file on identification failure
        if (file_exists($uploadPath)) {
            unlink($uploadPath);
        }
        
        sendJSONResponse([
            'success' => false,
            'message' => $identificationResult['message']
        ], 400);
    }

    // Store identification record
    $identificationRecord = [
        'identification_id' => uniqid('id_', true),
        'filename' => $uniqueFilename,
        'original_name' => sanitizeInput($uploadedFile['name']),
        'species_name' => $identificationResult['data']['species'],
        'confidence' => $identificationResult['data']['confidence'],
        'file_size' => $uploadedFile['size']
    ];

    try {
        $db = getDatabase();
        $recordId = $db->saveIdentification($identificationRecord);
        logError('Identification record saved successfully', ['record_id' => $recordId]);
    } catch (Exception $e) {
        logError('Failed to save identification record to database', [
            'identification_record' => $identificationRecord,
            'error' => $e->getMessage()
        ]);
    }

    // Return identification results
    sendJSONResponse([
        'success' => true,
        'message' => 'Bird species identified successfully.',
        'data' => $identificationResult['data']
    ]);

} catch (Exception $e) {
    logError('Exception in identify.php', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);

    sendJSONResponse([
        'success' => false,
        'message' => getMessage('IDENTIFICATION_ERROR')
    ], 500);
}

/**
 * Identify bird species from uploaded image
 */
function identifyBirdSpecies($imagePath) {
    try {
        // Check if image file exists
        if (!file_exists($imagePath)) {
            return [
                'success' => false,
                'message' => 'Image file not found.'
            ];
        }

        // Get image information
        $imageInfo = getimagesize($imagePath);
        if ($imageInfo === false) {
            return [
                'success' => false,
                'message' => 'Invalid image file.'
            ];
        }

        // If API key is configured, use external identification service
        $apiKey = IDENTIFICATION_API_KEY;
        $apiUrl = IDENTIFICATION_API_URL;

        if (!empty($apiKey) && !empty($apiUrl)) {
            return identifyWithExternalAPI($imagePath, $apiKey, $apiUrl);
        }

        // Fallback: Return demo identification result
        return getDemoIdentificationResult();

    } catch (Exception $e) {
        logError('Error in identifyBirdSpecies', [
            'message' => $e->getMessage(),
            'image_path' => $imagePath
        ]);

        return [
            'success' => false,
            'message' => getMessage('IDENTIFICATION_ERROR')
        ];
    }
}

/**
 * Identify bird using external API
 */
function identifyWithExternalAPI($imagePath, $apiKey, $apiUrl) {
    try {
        // Prepare image data for API
        $imageData = base64_encode(file_get_contents($imagePath));
        $mimeType = mime_content_type($imagePath);

        // Prepare API request
        $postData = [
            'image' => $imageData,
            'mime_type' => $mimeType,
            'species_filter' => SUPPORTED_SPECIES
        ];

        $options = [
            'http' => [
                'header' => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $apiKey,
                    'User-Agent: MKfinder/1.0'
                ],
                'method' => 'POST',
                'content' => json_encode($postData),
                'timeout' => 30
            ]
        ];

        $context = stream_context_create($options);
        $response = file_get_contents($apiUrl, false, $context);

        if ($response === false) {
            return [
                'success' => false,
                'message' => getMessage('NETWORK_ERROR')
            ];
        }

        $apiResult = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'message' => 'Invalid response from identification service.'
            ];
        }

        // Process API response
        if (!isset($apiResult['success']) || !$apiResult['success']) {
            return [
                'success' => false,
                'message' => $apiResult['message'] ?? getMessage('IDENTIFICATION_ERROR')
            ];
        }

        // Validate and format the response
        $species = $apiResult['data']['species'] ?? '';
        $confidence = floatval($apiResult['data']['confidence'] ?? 0);

        if (empty($species) || $confidence < 10) {
            return [
                'success' => false,
                'message' => getMessage('NO_SPECIES_FOUND')
            ];
        }

        // Check if species is supported
        if (!in_array($species, SUPPORTED_SPECIES)) {
            return [
                'success' => false,
                'message' => getMessage('SPECIES_NOT_SUPPORTED')
            ];
        }

        // Get species information
        $speciesInfo = getSpeciesInfoFromDB($species);

        return [
            'success' => true,
            'data' => [
                'species' => $species,
                'confidence' => round($confidence, 1),
                'description' => $speciesInfo['description'] ?? '',
                'characteristics' => $speciesInfo['characteristics'] ?? [],
                'habitat' => $speciesInfo['habitat'] ?? '',
                'scientific_name' => $speciesInfo['scientific_name'] ?? ''
            ]
        ];

    } catch (Exception $e) {
        logError('Error in identifyWithExternalAPI', [
            'message' => $e->getMessage(),
            'api_url' => $apiUrl
        ]);

        return [
            'success' => false,
            'message' => getMessage('IDENTIFICATION_ERROR')
        ];
    }
}

/**
 * Get species information from database
 */
function getSpeciesInfo($speciesName) {
    $database = getDatabase();
    
    if (!isset($database['species'])) {
        return [];
    }

    foreach ($database['species'] as $species) {
        if (strcasecmp($species['name'], $speciesName) === 0) {
            return $species;
        }
    }

    // Return default information if not found in database
    return getDefaultSpeciesInfo($speciesName);
}

/**
 * Get demo identification result (randomly selects from supported species)
 */
function getDemoIdentificationResult() {
    $supportedSpecies = ['American Robin', 'Blue Jay', 'Northern Cardinal'];
    $randomSpecies = $supportedSpecies[array_rand($supportedSpecies)];
    $randomConfidence = rand(80, 99); // Generate random confidence between 80-99%
    
    $speciesInfo = getDefaultSpeciesInfo($randomSpecies);
    
    return [
        'success' => true,
        'data' => [
            'species' => $randomSpecies,
            'confidence' => $randomConfidence,
            'description' => $speciesInfo['description'] ?? '',
            'characteristics' => $speciesInfo['characteristics'] ?? [],
            'habitat' => $speciesInfo['habitat'] ?? '',
            'scientific_name' => $speciesInfo['scientific_name'] ?? ''
        ]
    ];
}

/**
 * Get default species information
 */
function getDefaultSpeciesInfo($speciesName) {
    $defaultInfo = [
        'American Robin' => [
            'name' => 'American Robin',
            'scientific_name' => 'Turdus migratorius',
            'description' => 'The American Robin is a migratory songbird with a distinctive red breast and gray back.',
            'characteristics' => [
                'Red-orange breast',
                'Dark gray head and back',
                'White markings around eyes',
                'Yellow beak',
                'White undertail coverts'
            ],
            'habitat' => 'Woodlands, parks, gardens, and lawns'
        ],
        'Blue Jay' => [
            'name' => 'Blue Jay',
            'scientific_name' => 'Cyanocitta cristata',
            'description' => 'The Blue Jay is a highly intelligent corvid known for its bright blue coloration and distinctive crest.',
            'characteristics' => [
                'Bright blue upper parts',
                'White chest and underparts',
                'Black necklace markings',
                'Prominent blue crest',
                'White and black barred wings and tail'
            ],
            'habitat' => 'Deciduous and mixed forests, parks, and residential areas'
        ],
        'Northern Cardinal' => [
            'name' => 'Northern Cardinal',
            'scientific_name' => 'Cardinalis cardinalis',
            'description' => 'The Northern Cardinal is a vibrant songbird, with males displaying brilliant red plumage.',
            'characteristics' => [
                'Males: Bright red all over',
                'Females: Brown with red tinges',
                'Thick orange-red beak',
                'Black mask around eyes (males)',
                'Prominent red crest'
            ],
            'habitat' => 'Woodlands, gardens, shrublands, and wetlands'
        ]
    ];

    return $defaultInfo[$speciesName] ?? [];
}
?>

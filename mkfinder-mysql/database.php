<?php
/**
 * MKfinder Database Class - MySQL Version
 * Handles all database operations using MySQL/MariaDB
 */

require_once 'config.php';

class Database {
    private static $instance = null;
    private $connection = null;
    
    private function __construct() {
        $this->connect();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function connect() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            // Create tables if they don't exist
            $this->initializeTables();
            
            logError('Database connection established successfully');
            
        } catch (PDOException $e) {
            logError('Database connection failed', ['error' => $e->getMessage()]);
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
    }
    
    public function getConnection() {
        if ($this->connection === null) {
            $this->connect();
        }
        return $this->connection;
    }
    
    private function initializeTables() {
        try {
            $pdo = $this->getConnection();
            
            // Create species table
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS species (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL UNIQUE,
                    scientific_name VARCHAR(255),
                    description TEXT,
                    characteristics JSON,
                    habitat TEXT,
                    diet TEXT,
                    behavior TEXT,
                    conservation_status VARCHAR(100),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_species_name (name)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Create uploads table
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS uploads (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    filename VARCHAR(255) NOT NULL,
                    original_name VARCHAR(255) NOT NULL,
                    file_size INT,
                    mime_type VARCHAR(100),
                    upload_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_uploads_time (upload_time DESC)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Create identifications table
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS identifications (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    identification_id VARCHAR(255) UNIQUE,
                    upload_id INT,
                    species_name VARCHAR(255),
                    confidence DECIMAL(5,2),
                    identification_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (upload_id) REFERENCES uploads(id) ON DELETE SET NULL,
                    FOREIGN KEY (species_name) REFERENCES species(name) ON UPDATE CASCADE,
                    INDEX idx_identifications_species (species_name),
                    INDEX idx_identifications_time (identification_time DESC)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Insert initial species data
            $this->insertInitialSpeciesData();
            
            logError('Database tables initialized successfully');
            
        } catch (PDOException $e) {
            logError('Failed to initialize database tables', ['error' => $e->getMessage()]);
            throw new Exception('Database initialization failed: ' . $e->getMessage());
        }
    }
    
    private function insertInitialSpeciesData() {
        try {
            $pdo = $this->getConnection();
            
            // Check if data already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM species");
            $stmt->execute();
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                return; // Data already exists
            }
            
            $speciesData = [
                [
                    'name' => 'American Robin',
                    'scientific_name' => 'Turdus migratorius',
                    'description' => 'The American Robin is a migratory songbird of the true thrush genus and Turdidae, the wider thrush family. It is named after the European robin because of its reddish-orange breast, though the two species are not closely related.',
                    'characteristics' => json_encode([
                        'size' => '8-11 inches',
                        'wingspan' => '12-16 inches',
                        'weight' => '2.7-3.0 oz',
                        'colors' => ['orange-red breast', 'dark gray head', 'brown back'],
                        'distinctive_features' => ['bright orange-red breast', 'white eye ring', 'yellow bill']
                    ]),
                    'habitat' => 'Found in woodlands, suburban areas, parks, and gardens. Prefers areas with trees for nesting and open ground for foraging.',
                    'diet' => 'Primarily earthworms and insects, but also fruits and berries, especially in fall and winter.',
                    'behavior' => 'Often seen hopping on lawns searching for worms. Known for their melodic song, especially at dawn. Builds cup-shaped nests in trees.',
                    'conservation_status' => 'Least Concern'
                ],
                [
                    'name' => 'Blue Jay',
                    'scientific_name' => 'Cyanocitta cristata',
                    'description' => 'The Blue Jay is a passerine bird in the family Corvidae, native to eastern North America. It is resident through most of eastern and central United States, though western populations may be migratory.',
                    'characteristics' => json_encode([
                        'size' => '11-12 inches',
                        'wingspan' => '13-17 inches',
                        'weight' => '2.5-3.5 oz',
                        'colors' => ['bright blue upperparts', 'white underparts', 'black markings'],
                        'distinctive_features' => ['prominent blue crest', 'black necklace marking', 'white patches on wings and tail']
                    ]),
                    'habitat' => 'Deciduous and mixed forests, woodland edges, parks, and suburban areas with mature trees.',
                    'diet' => 'Omnivorous - nuts (especially acorns), seeds, insects, occasionally eggs and nestlings of other birds.',
                    'behavior' => 'Highly intelligent and social. Known for their loud calls and ability to mimic other birds. Often travels in flocks outside breeding season.',
                    'conservation_status' => 'Least Concern'
                ],
                [
                    'name' => 'Northern Cardinal',
                    'scientific_name' => 'Cardinalis cardinalis',
                    'description' => 'The Northern Cardinal is a bird in the genus Cardinalis. It can be found in southeastern Canada, through the eastern United States from Maine to northern Guatemala and Belize.',
                    'characteristics' => json_encode([
                        'size' => '8.5-9 inches',
                        'wingspan' => '9.8-12.2 inches',
                        'weight' => '1.5-1.7 oz',
                        'colors' => ['bright red (male)', 'warm brown with red tinges (female)'],
                        'distinctive_features' => ['prominent red crest', 'thick orange-red bill', 'black face mask (male)']
                    ]),
                    'habitat' => 'Woodland edges, gardens, shrublands, and swamps. Prefers areas with dense shrubs and thickets.',
                    'diet' => 'Seeds, grains, fruits, and insects. Common at bird feeders, especially enjoying sunflower seeds.',
                    'behavior' => 'Non-migratory year-round residents. Males are territorial and sing from prominent perches. Known for their clear whistled songs.',
                    'conservation_status' => 'Least Concern'
                ]
            ];
            
            $stmt = $pdo->prepare("
                INSERT INTO species (name, scientific_name, description, characteristics, habitat, diet, behavior, conservation_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($speciesData as $species) {
                $stmt->execute([
                    $species['name'],
                    $species['scientific_name'],
                    $species['description'],
                    $species['characteristics'],
                    $species['habitat'],
                    $species['diet'],
                    $species['behavior'],
                    $species['conservation_status']
                ]);
            }
            
            logError('Initial species data inserted successfully');
            
        } catch (PDOException $e) {
            logError('Failed to insert initial species data', ['error' => $e->getMessage()]);
        }
    }
    
    public function getAllSpecies() {
        try {
            $pdo = $this->getConnection();
            $stmt = $pdo->prepare("SELECT * FROM species ORDER BY name");
            $stmt->execute();
            
            $species = $stmt->fetchAll();
            
            // Decode JSON characteristics
            foreach ($species as &$sp) {
                if (isset($sp['characteristics'])) {
                    $sp['characteristics'] = json_decode($sp['characteristics'], true);
                }
            }
            
            return $species;
            
        } catch (PDOException $e) {
            logError('Failed to get all species', ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    public function getSpeciesByName($name) {
        try {
            $pdo = $this->getConnection();
            $stmt = $pdo->prepare("SELECT * FROM species WHERE name = ?");
            $stmt->execute([$name]);
            
            $species = $stmt->fetch();
            
            if ($species && isset($species['characteristics'])) {
                $species['characteristics'] = json_decode($species['characteristics'], true);
            }
            
            return $species;
            
        } catch (PDOException $e) {
            logError('Failed to get species by name', ['name' => $name, 'error' => $e->getMessage()]);
            return null;
        }
    }
    
    public function saveUpload($uploadData) {
        try {
            $pdo = $this->getConnection();
            $stmt = $pdo->prepare("
                INSERT INTO uploads (filename, original_name, file_size, mime_type) 
                VALUES (?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $uploadData['filename'],
                $uploadData['original_name'],
                $uploadData['file_size'],
                $uploadData['mime_type']
            ]);
            
            return $pdo->lastInsertId();
            
        } catch (PDOException $e) {
            logError('Failed to save upload', ['upload_data' => $uploadData, 'error' => $e->getMessage()]);
            throw new Exception('Failed to save upload: ' . $e->getMessage());
        }
    }
    
    public function saveIdentification($identificationData) {
        try {
            $pdo = $this->getConnection();
            
            // First save the upload if filename is provided
            $uploadId = null;
            if (isset($identificationData['filename'])) {
                $uploadId = $this->saveUpload([
                    'filename' => $identificationData['filename'],
                    'original_name' => $identificationData['original_name'],
                    'file_size' => $identificationData['file_size'],
                    'mime_type' => $identificationData['mime_type'] ?? 'image/jpeg'
                ]);
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO identifications (identification_id, upload_id, species_name, confidence) 
                VALUES (?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $identificationData['identification_id'],
                $uploadId,
                $identificationData['species_name'],
                $identificationData['confidence']
            ]);
            
            return $pdo->lastInsertId();
            
        } catch (PDOException $e) {
            logError('Failed to save identification', ['identification_data' => $identificationData, 'error' => $e->getMessage()]);
            throw new Exception('Failed to save identification: ' . $e->getMessage());
        }
    }
    
    public function getRecentIdentifications($limit = 10, $offset = 0, $speciesFilter = '') {
        try {
            $pdo = $this->getConnection();
            
            $sql = "
                SELECT i.*, u.filename, u.original_name, u.file_size, u.upload_time
                FROM identifications i
                LEFT JOIN uploads u ON i.upload_id = u.id
            ";
            
            $params = [];
            if (!empty($speciesFilter)) {
                $sql .= " WHERE i.species_name = ?";
                $params[] = $speciesFilter;
            }
            
            $sql .= " ORDER BY i.identification_time DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            logError('Failed to get recent identifications', ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    public function countIdentifications($speciesFilter = '') {
        try {
            $pdo = $this->getConnection();
            
            $sql = "SELECT COUNT(*) FROM identifications";
            $params = [];
            
            if (!empty($speciesFilter)) {
                $sql .= " WHERE species_name = ?";
                $params[] = $speciesFilter;
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchColumn();
            
        } catch (PDOException $e) {
            logError('Failed to count identifications', ['error' => $e->getMessage()]);
            return 0;
        }
    }
    
    public function getSpeciesStatistics() {
        try {
            $pdo = $this->getConnection();
            $stmt = $pdo->prepare("
                SELECT 
                    species_name,
                    COUNT(*) as identification_count,
                    AVG(confidence) as avg_confidence,
                    MAX(confidence) as max_confidence,
                    MIN(confidence) as min_confidence
                FROM identifications 
                GROUP BY species_name 
                ORDER BY identification_count DESC
            ");
            
            $stmt->execute();
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            logError('Failed to get species statistics', ['error' => $e->getMessage()]);
            return [];
        }
    }
}

/**
 * Global function to get database instance
 */
function getDatabase() {
    return Database::getInstance();
}

/**
 * Test database connection
 */
function testDatabaseConnection() {
    try {
        $db = getDatabase();
        $pdo = $db->getConnection();
        
        // Test query
        $stmt = $pdo->prepare("SELECT 1");
        $stmt->execute();
        
        return true;
    } catch (Exception $e) {
        logError('Database connection test failed', ['error' => $e->getMessage()]);
        return false;
    }
}

/**
 * Get species information from database
 */
function getSpeciesInfoFromDB($speciesName) {
    try {
        $db = getDatabase();
        return $db->getSpeciesByName($speciesName);
    } catch (Exception $e) {
        logError('Failed to get species info from database', ['species' => $speciesName, 'error' => $e->getMessage()]);
        return getDefaultSpeciesInfo($speciesName);
    }
}

/**
 * Get all species from database
 */
function getAllSpeciesFromDB() {
    try {
        $db = getDatabase();
        return $db->getAllSpecies();
    } catch (Exception $e) {
        logError('Failed to get all species from database', ['error' => $e->getMessage()]);
        
        // Return default species info as fallback
        $defaultSpecies = [];
        foreach (SUPPORTED_SPECIES as $speciesName) {
            $info = getDefaultSpeciesInfo($speciesName);
            if ($info) {
                $defaultSpecies[] = $info;
            }
        }
        return $defaultSpecies;
    }
}
?>
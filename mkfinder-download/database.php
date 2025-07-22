<?php
/**
 * MKfinder Database Connection Handler
 * Manages PostgreSQL database connections and operations
 */

require_once 'config.php';

class Database {
    private static $instance = null;
    private $connection = null;
    
    private function __construct() {
        $this->connect();
    }
    
    /**
     * Get database instance (Singleton pattern)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    /**
     * Connect to PostgreSQL database
     */
    private function connect() {
        try {
            $databaseUrl = getenv('DATABASE_URL');
            
            if (empty($databaseUrl)) {
                throw new Exception('DATABASE_URL environment variable not set');
            }
            
            // Parse DATABASE_URL
            $dbInfo = parse_url($databaseUrl);
            $host = $dbInfo['host'];
            $port = $dbInfo['port'] ?? 5432;
            $dbname = ltrim($dbInfo['path'], '/');
            $user = $dbInfo['user'];
            $password = $dbInfo['pass'];
            
            $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
            
            $this->connection = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            
        } catch (Exception $e) {
            logError('Database connection failed', ['error' => $e->getMessage()]);
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Get database connection
     */
    public function getConnection() {
        if ($this->connection === null) {
            $this->connect();
        }
        return $this->connection;
    }
    
    /**
     * Execute query and return results
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            logError('Database query failed', [
                'sql' => $sql,
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            throw new Exception('Database query failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Get all species
     */
    public function getAllSpecies() {
        $stmt = $this->query("SELECT * FROM species ORDER BY name");
        $species = $stmt->fetchAll();
        
        // Convert JSONB characteristics to array
        foreach ($species as &$spec) {
            if (isset($spec['characteristics'])) {
                $spec['characteristics'] = json_decode($spec['characteristics'], true);
            }
        }
        
        return $species;
    }
    
    /**
     * Get species by name
     */
    public function getSpeciesByName($name) {
        $stmt = $this->query("SELECT * FROM species WHERE name = ?", [$name]);
        $species = $stmt->fetch();
        
        if ($species && isset($species['characteristics'])) {
            $species['characteristics'] = json_decode($species['characteristics'], true);
        }
        
        return $species;
    }
    
    /**
     * Save upload record
     */
    public function saveUpload($uploadData) {
        $sql = "INSERT INTO uploads (upload_id, filename, original_name, file_size, mime_type, file_path, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?) RETURNING id";
        
        $stmt = $this->query($sql, [
            $uploadData['upload_id'],
            $uploadData['filename'],
            $uploadData['original_name'],
            $uploadData['file_size'],
            $uploadData['mime_type'],
            $uploadData['file_path'],
            $uploadData['status'] ?? 'uploaded'
        ]);
        
        return $stmt->fetch()['id'];
    }
    
    /**
     * Save identification record
     */
    public function saveIdentification($identificationData) {
        $sql = "INSERT INTO identifications 
                (identification_id, upload_id, filename, original_name, species_name, confidence, file_size, processing_time_ms) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?) RETURNING id";
        
        $stmt = $this->query($sql, [
            $identificationData['identification_id'],
            $identificationData['upload_id'] ?? null,
            $identificationData['filename'],
            $identificationData['original_name'],
            $identificationData['species_name'],
            $identificationData['confidence'],
            $identificationData['file_size'] ?? null,
            $identificationData['processing_time_ms'] ?? null
        ]);
        
        return $stmt->fetch()['id'];
    }
    
    /**
     * Get recent identifications
     */
    public function getRecentIdentifications($limit = 50, $offset = 0, $speciesFilter = null) {
        $sql = "SELECT i.*, u.file_path as upload_path 
                FROM identifications i 
                LEFT JOIN uploads u ON i.upload_id = u.upload_id";
        
        $params = [];
        
        if ($speciesFilter) {
            $sql .= " WHERE i.species_name = ?";
            $params[] = $speciesFilter;
        }
        
        $sql .= " ORDER BY i.identification_time DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Count total identifications
     */
    public function countIdentifications($speciesFilter = null) {
        $sql = "SELECT COUNT(*) as total FROM identifications";
        $params = [];
        
        if ($speciesFilter) {
            $sql .= " WHERE species_name = ?";
            $params[] = $speciesFilter;
        }
        
        $stmt = $this->query($sql, $params);
        return $stmt->fetch()['total'];
    }
    
    /**
     * Get species statistics
     */
    public function getSpeciesStatistics() {
        $sql = "SELECT 
                    species_name,
                    COUNT(*) as identification_count,
                    AVG(confidence) as avg_confidence,
                    MAX(confidence) as max_confidence,
                    MIN(confidence) as min_confidence
                FROM identifications 
                GROUP BY species_name 
                ORDER BY identification_count DESC";
        
        $stmt = $this->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Close connection
     */
    public function close() {
        $this->connection = null;
    }
}

/**
 * Get database instance
 */
function getDatabase() {
    return Database::getInstance();
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
        return [];
    }
}

/**
 * Get species info from database
 */
function getSpeciesInfoFromDB($speciesName) {
    try {
        $db = getDatabase();
        return $db->getSpeciesByName($speciesName);
    } catch (Exception $e) {
        logError('Failed to get species info from database', [
            'species' => $speciesName,
            'error' => $e->getMessage()
        ]);
        return null;
    }
}

/**
 * Test database connection
 */
function testDatabaseConnection() {
    try {
        $db = getDatabase();
        $stmt = $db->query("SELECT 1 as test");
        return $stmt->fetch()['test'] === 1;
    } catch (Exception $e) {
        logError('Database connection test failed', ['error' => $e->getMessage()]);
        return false;
    }
}
?>
<?php
/**
 * MKfinder — database.php (UPDATED)
 * Added user-specific methods for personal gallery
 */

require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $connection = null;

    private function __construct() { $this->connect(); }

    public static function getInstance() {
        if (self::$instance === null) self::$instance = new Database();
        return self::$instance;
    }

    private function connect() {
        try {
            $databaseUrl = getenv('DATABASE_URL');
            if (!empty($databaseUrl)) {
                $dbInfo   = parse_url($databaseUrl);
                $host     = $dbInfo['host'];
                $port     = $dbInfo['port'] ?? 3306;
                $dbname   = ltrim($dbInfo['path'], '/');
                $user     = $dbInfo['user'];
                $password = $dbInfo['pass'];
            } else {
                $host     = getenv('DB_HOST') ?: 'localhost';
                $port     = getenv('DB_PORT') ?: 3306;
                $dbname   = getenv('DB_NAME') ?: 'mkfinder';
                $user     = getenv('DB_USER') ?: 'root';
                $password = getenv('DB_PASS') ?: '';
            }

            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
            $this->connection = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);
        } catch (Exception $e) {
            logError('Database connection failed', ['error' => $e->getMessage()]);
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
    }

    public function getConnection() {
        if ($this->connection === null) $this->connect();
        return $this->connection;
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            logError('Database query failed', [
                'sql'    => $sql,
                'params' => $params,
                'error'  => $e->getMessage()
            ]);
            throw new Exception('Database query failed: ' . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────
    // SPECIES
    // ─────────────────────────────────────────────────────────

    public function getAllSpecies() {
        // Only show admin-approved species in public pages
        // Falls back gracefully if status column doesn't exist yet
        try {
            $stmt = $this->query(
                "SELECT * FROM species WHERE status = 'approved' ORDER BY name"
            );
        } catch (Exception $e) {
            // status column not added yet — show all (backwards compat)
            $stmt = $this->query("SELECT * FROM species ORDER BY name");
        }
        $species = $stmt->fetchAll();
        foreach ($species as &$spec) {
            if (isset($spec['characteristics']))
                $spec['characteristics'] = json_decode($spec['characteristics'], true);
        }
        return $species;
    }

    public function getSpeciesByName($name) {
        $stmt    = $this->query("SELECT * FROM species WHERE name = ?", [$name]);
        $species = $stmt->fetch();
        if ($species && isset($species['characteristics']))
            $species['characteristics'] = json_decode($species['characteristics'], true);
        return $species;
    }

    // ─────────────────────────────────────────────────────────
    // UPLOADS
    // ─────────────────────────────────────────────────────────

    public function saveUpload($uploadData) {
        $sql = "INSERT INTO uploads
                    (upload_id, user_id, filename, original_name,
                     file_size, mime_type, file_path, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $this->query($sql, [
            $uploadData['upload_id'],
            $uploadData['user_id']       ?? null,
            $uploadData['filename'],
            $uploadData['original_name'],
            $uploadData['file_size']     ?? null,
            $uploadData['mime_type']     ?? null,
            $uploadData['file_path']     ?? null,
            $uploadData['status']        ?? 'uploaded',
        ]);

        return $this->connection->lastInsertId();
    }

    // ─────────────────────────────────────────────────────────
    // IDENTIFICATIONS
    // ─────────────────────────────────────────────────────────

    public function saveIdentification($data) {
        $sql = "INSERT INTO identifications
        (identification_id, upload_id, user_id, filename,
         original_name, species_name, confidence,
         file_size, processing_time_ms, location)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$this->query($sql, [
    $data['identification_id'],
    $data['upload_id']           ?? null,
    $data['user_id']             ?? null,
    $data['filename'],
    $data['original_name'],
    $data['species_name'],
    $data['confidence'],
    $data['file_size']           ?? null,
    $data['processing_time_ms']  ?? null,
    $data['location']            ?? null,
]);

        return $this->connection->lastInsertId();
    }

    // ─────────────────────────────────────────────────────────
    // USER-SPECIFIC GALLERY (personal — only their own uploads)
    // ─────────────────────────────────────────────────────────

    public function getUserIdentifications($userId, $limit = 50, $offset = 0, $speciesFilter = null) {
        $params = [$userId];
        $sql    = "SELECT i.*, u.file_path AS upload_path
                   FROM identifications i
                   LEFT JOIN uploads u ON i.upload_id = u.upload_id
                   WHERE i.user_id = ?";

        if ($speciesFilter) {
            $sql     .= " AND i.species_name = ?";
            $params[] = $speciesFilter;
        }

        $sql     .= " ORDER BY i.identification_time DESC LIMIT ? OFFSET ?";
        $params[] = (int)$limit;
        $params[] = (int)$offset;

        return $this->query($sql, $params)->fetchAll();
    }

    public function countUserIdentifications($userId, $speciesFilter = null) {
        $params = [$userId];
        $sql    = "SELECT COUNT(*) AS total FROM identifications WHERE user_id = ?";

        if ($speciesFilter) {
            $sql     .= " AND species_name = ?";
            $params[] = $speciesFilter;
        }

        return (int)$this->query($sql, $params)->fetch()['total'];
    }

    public function getUserSpeciesStatistics($userId) {
        $sql = "SELECT
                    species_name,
                    COUNT(*)        AS identification_count,
                    AVG(confidence) AS avg_confidence,
                    MAX(confidence) AS max_confidence,
                    MIN(confidence) AS min_confidence
                FROM identifications
                WHERE user_id = ?
                GROUP BY species_name
                ORDER BY identification_count DESC";

        return $this->query($sql, [$userId])->fetchAll();
    }

    // ─────────────────────────────────────────────────────────
    // LEGACY — all users combined (kept for compatibility)
    // ─────────────────────────────────────────────────────────

    public function getRecentIdentifications($limit = 50, $offset = 0, $speciesFilter = null) {
        $params = [];
        $sql    = "SELECT i.*, u.file_path AS upload_path
                   FROM identifications i
                   LEFT JOIN uploads u ON i.upload_id = u.upload_id";

        if ($speciesFilter) {
            $sql     .= " WHERE i.species_name = ?";
            $params[] = $speciesFilter;
        }

        $sql     .= " ORDER BY i.identification_time DESC LIMIT ? OFFSET ?";
        $params[] = (int)$limit;
        $params[] = (int)$offset;

        return $this->query($sql, $params)->fetchAll();
    }

    public function countIdentifications($speciesFilter = null) {
        $params = [];
        $sql    = "SELECT COUNT(*) AS total FROM identifications";

        if ($speciesFilter) {
            $sql     .= " WHERE species_name = ?";
            $params[] = $speciesFilter;
        }

        return (int)$this->query($sql, $params)->fetch()['total'];
    }

    public function getSpeciesStatistics() {
        $sql = "SELECT
                    species_name,
                    COUNT(*)        AS identification_count,
                    AVG(confidence) AS avg_confidence,
                    MAX(confidence) AS max_confidence,
                    MIN(confidence) AS min_confidence
                FROM identifications
                GROUP BY species_name
                ORDER BY identification_count DESC";

        return $this->query($sql)->fetchAll();
    }

    public function close() { $this->connection = null; }
}

function getDatabase()       { return Database::getInstance(); }

function getAllSpeciesFromDB() {
    try   { return getDatabase()->getAllSpecies(); }
    catch (Exception $e) {
        logError('getAllSpecies failed', ['error' => $e->getMessage()]);
        return [];
    }
}

function getSpeciesInfoFromDB($name) {
    try   { return getDatabase()->getSpeciesByName($name); }
    catch (Exception $e) {
        logError('getSpeciesByName failed', ['error' => $e->getMessage()]);
        return null;
    }
}

function testDatabaseConnection() {
    try   { return getDatabase()->query("SELECT 1 AS test")->fetch()['test'] == 1; }
    catch (Exception $e) {
        logError('DB test failed', ['error' => $e->getMessage()]);
        return false;
    }
}
?>
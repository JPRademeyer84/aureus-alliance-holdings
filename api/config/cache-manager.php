<?php
/**
 * CACHE MANAGER
 * High-performance caching system with multiple backends and intelligent cache strategies
 */

require_once 'performance-monitor.php';

class CacheManager {
    private static $instance = null;
    private $cacheBackend;
    private $defaultTTL = 3600; // 1 hour
    private $performanceMonitor;
    
    // Cache backends
    const BACKEND_FILE = 'file';
    const BACKEND_REDIS = 'redis';
    const BACKEND_MEMCACHED = 'memcached';
    const BACKEND_DATABASE = 'database';
    
    // Cache types
    const TYPE_USER_DATA = 'user_data';
    const TYPE_API_RESPONSE = 'api_response';
    const TYPE_DATABASE_QUERY = 'db_query';
    const TYPE_TRANSLATION = 'translation';
    const TYPE_CONFIGURATION = 'config';
    const TYPE_SESSION = 'session';
    
    private function __construct() {
        $this->performanceMonitor = PerformanceMonitor::getInstance();
        $this->initializeCacheBackend();
        $this->initializeCacheTables();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize cache backend
     */
    private function initializeCacheBackend() {
        // Try Redis first, then fallback to file cache
        if (extension_loaded('redis') && $this->testRedisConnection()) {
            $this->cacheBackend = self::BACKEND_REDIS;
        } else {
            $this->cacheBackend = self::BACKEND_FILE;
            $this->ensureCacheDirectory();
        }
    }
    
    /**
     * Initialize cache tables for database backend
     */
    private function initializeCacheTables() {
        $database = new Database();
        $db = $database->getConnection();
        
        if ($db) {
            $sql = "CREATE TABLE IF NOT EXISTS cache_storage (
                cache_key VARCHAR(255) PRIMARY KEY,
                cache_type VARCHAR(50) NOT NULL,
                cache_data LONGTEXT NOT NULL,
                expires_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                access_count INT DEFAULT 0,
                last_accessed TIMESTAMP NULL,
                data_size INT DEFAULT 0,
                INDEX idx_cache_type (cache_type),
                INDEX idx_expires_at (expires_at),
                INDEX idx_last_accessed (last_accessed)
            )";
            
            try {
                $db->exec($sql);
            } catch (PDOException $e) {
                error_log("Failed to create cache table: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Get cached data
     */
    public function get($key, $type = self::TYPE_API_RESPONSE) {
        $fullKey = $this->buildCacheKey($key, $type);
        
        switch ($this->cacheBackend) {
            case self::BACKEND_REDIS:
                $data = $this->getFromRedis($fullKey);
                break;
            case self::BACKEND_FILE:
                $data = $this->getFromFile($fullKey);
                break;
            case self::BACKEND_DATABASE:
                $data = $this->getFromDatabase($fullKey);
                break;
            default:
                $data = null;
        }
        
        if ($data !== null) {
            $this->performanceMonitor->trackCacheHit($fullKey, $type);
            $this->updateAccessStats($fullKey);
        } else {
            $this->performanceMonitor->trackCacheMiss($fullKey, $type);
        }
        
        return $data;
    }
    
    /**
     * Set cached data
     */
    public function set($key, $data, $ttl = null, $type = self::TYPE_API_RESPONSE) {
        $fullKey = $this->buildCacheKey($key, $type);
        $ttl = $ttl ?? $this->defaultTTL;
        
        switch ($this->cacheBackend) {
            case self::BACKEND_REDIS:
                return $this->setToRedis($fullKey, $data, $ttl);
            case self::BACKEND_FILE:
                return $this->setToFile($fullKey, $data, $ttl);
            case self::BACKEND_DATABASE:
                return $this->setToDatabase($fullKey, $data, $ttl, $type);
            default:
                return false;
        }
    }
    
    /**
     * Delete cached data
     */
    public function delete($key, $type = self::TYPE_API_RESPONSE) {
        $fullKey = $this->buildCacheKey($key, $type);
        
        switch ($this->cacheBackend) {
            case self::BACKEND_REDIS:
                return $this->deleteFromRedis($fullKey);
            case self::BACKEND_FILE:
                return $this->deleteFromFile($fullKey);
            case self::BACKEND_DATABASE:
                return $this->deleteFromDatabase($fullKey);
            default:
                return false;
        }
    }
    
    /**
     * Clear cache by type
     */
    public function clearByType($type) {
        switch ($this->cacheBackend) {
            case self::BACKEND_REDIS:
                return $this->clearRedisType($type);
            case self::BACKEND_FILE:
                return $this->clearFileType($type);
            case self::BACKEND_DATABASE:
                return $this->clearDatabaseType($type);
            default:
                return false;
        }
    }
    
    /**
     * Get cache statistics
     */
    public function getStatistics() {
        $stats = [
            'backend' => $this->cacheBackend,
            'total_keys' => 0,
            'total_size' => 0,
            'hit_ratio' => 0,
            'types' => []
        ];
        
        switch ($this->cacheBackend) {
            case self::BACKEND_DATABASE:
                $stats = array_merge($stats, $this->getDatabaseStats());
                break;
            case self::BACKEND_FILE:
                $stats = array_merge($stats, $this->getFileStats());
                break;
            case self::BACKEND_REDIS:
                $stats = array_merge($stats, $this->getRedisStats());
                break;
        }
        
        return $stats;
    }
    
    /**
     * Cache with callback for automatic cache population
     */
    public function remember($key, $callback, $ttl = null, $type = self::TYPE_API_RESPONSE) {
        $data = $this->get($key, $type);
        
        if ($data === null) {
            $data = $callback();
            if ($data !== null) {
                $this->set($key, $data, $ttl, $type);
            }
        }
        
        return $data;
    }
    
    /**
     * Intelligent cache warming
     */
    public function warmCache($patterns = []) {
        $warmed = 0;
        
        foreach ($patterns as $pattern) {
            switch ($pattern['type']) {
                case 'user_data':
                    $warmed += $this->warmUserDataCache($pattern);
                    break;
                case 'translations':
                    $warmed += $this->warmTranslationCache($pattern);
                    break;
                case 'configurations':
                    $warmed += $this->warmConfigurationCache($pattern);
                    break;
            }
        }
        
        return $warmed;
    }
    
    /**
     * Private methods for different backends
     */
    
    private function getFromRedis($key) {
        try {
            $redis = new Redis();
            $redis->connect('127.0.0.1', 6379);
            
            $data = $redis->get($key);
            $redis->close();
            
            return $data ? json_decode($data, true) : null;
        } catch (Exception $e) {
            error_log("Redis get error: " . $e->getMessage());
            return null;
        }
    }
    
    private function setToRedis($key, $data, $ttl) {
        try {
            $redis = new Redis();
            $redis->connect('127.0.0.1', 6379);
            
            $result = $redis->setex($key, $ttl, json_encode($data));
            $redis->close();
            
            return $result;
        } catch (Exception $e) {
            error_log("Redis set error: " . $e->getMessage());
            return false;
        }
    }
    
    private function deleteFromRedis($key) {
        try {
            $redis = new Redis();
            $redis->connect('127.0.0.1', 6379);
            
            $result = $redis->del($key);
            $redis->close();
            
            return $result > 0;
        } catch (Exception $e) {
            error_log("Redis delete error: " . $e->getMessage());
            return false;
        }
    }
    
    private function getFromFile($key) {
        $filename = $this->getCacheFilePath($key);
        
        if (!file_exists($filename)) {
            return null;
        }
        
        $data = file_get_contents($filename);
        $cacheData = json_decode($data, true);
        
        if (!$cacheData || (isset($cacheData['expires']) && $cacheData['expires'] < time())) {
            unlink($filename);
            return null;
        }
        
        return $cacheData['data'];
    }
    
    private function setToFile($key, $data, $ttl) {
        $filename = $this->getCacheFilePath($key);
        $cacheData = [
            'data' => $data,
            'expires' => time() + $ttl,
            'created' => time()
        ];
        
        return file_put_contents($filename, json_encode($cacheData)) !== false;
    }
    
    private function deleteFromFile($key) {
        $filename = $this->getCacheFilePath($key);
        return file_exists($filename) ? unlink($filename) : true;
    }
    
    private function getFromDatabase($key) {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "SELECT cache_data FROM cache_storage 
                      WHERE cache_key = ? AND (expires_at IS NULL OR expires_at > NOW())";
            $stmt = $db->prepare($query);
            $stmt->execute([$key]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                // Update access stats
                $updateQuery = "UPDATE cache_storage 
                               SET access_count = access_count + 1, last_accessed = NOW() 
                               WHERE cache_key = ?";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->execute([$key]);
                
                return json_decode($result['cache_data'], true);
            }
            
            return null;
        } catch (PDOException $e) {
            error_log("Database cache get error: " . $e->getMessage());
            return null;
        }
    }
    
    private function setToDatabase($key, $data, $ttl, $type) {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $jsonData = json_encode($data);
            $expiresAt = date('Y-m-d H:i:s', time() + $ttl);
            
            $query = "INSERT INTO cache_storage (
                cache_key, cache_type, cache_data, expires_at, data_size
            ) VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                cache_data = VALUES(cache_data),
                expires_at = VALUES(expires_at),
                updated_at = NOW(),
                data_size = VALUES(data_size)";
            
            $stmt = $db->prepare($query);
            return $stmt->execute([$key, $type, $jsonData, $expiresAt, strlen($jsonData)]);
            
        } catch (PDOException $e) {
            error_log("Database cache set error: " . $e->getMessage());
            return false;
        }
    }
    
    private function deleteFromDatabase($key) {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "DELETE FROM cache_storage WHERE cache_key = ?";
            $stmt = $db->prepare($query);
            return $stmt->execute([$key]);
            
        } catch (PDOException $e) {
            error_log("Database cache delete error: " . $e->getMessage());
            return false;
        }
    }
    
    private function buildCacheKey($key, $type) {
        return $type . ':' . hash('sha256', $key);
    }
    
    private function getCacheFilePath($key) {
        $cacheDir = dirname(__DIR__) . '/cache';
        return $cacheDir . '/' . hash('sha256', $key) . '.cache';
    }
    
    private function ensureCacheDirectory() {
        $cacheDir = dirname(__DIR__) . '/cache';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
    }
    
    private function testRedisConnection() {
        try {
            $redis = new Redis();
            $connected = $redis->connect('127.0.0.1', 6379, 1); // 1 second timeout
            if ($connected) {
                $redis->close();
                return true;
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function updateAccessStats($key) {
        // Update access statistics for performance monitoring
        // Implementation depends on backend
    }
    
    private function getDatabaseStats() {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "SELECT 
                COUNT(*) as total_keys,
                SUM(data_size) as total_size,
                cache_type,
                COUNT(*) as type_count
            FROM cache_storage 
            WHERE expires_at IS NULL OR expires_at > NOW()
            GROUP BY cache_type";
            
            $stmt = $db->prepare($query);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $stats = ['total_keys' => 0, 'total_size' => 0, 'types' => []];
            foreach ($results as $result) {
                $stats['total_keys'] += $result['type_count'];
                $stats['total_size'] += $result['total_size'];
                $stats['types'][$result['cache_type']] = $result['type_count'];
            }
            
            return $stats;
        } catch (PDOException $e) {
            error_log("Database cache stats error: " . $e->getMessage());
            return ['total_keys' => 0, 'total_size' => 0, 'types' => []];
        }
    }
    
    private function getFileStats() {
        $cacheDir = dirname(__DIR__) . '/cache';
        $stats = ['total_keys' => 0, 'total_size' => 0, 'types' => []];
        
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '/*.cache');
            $stats['total_keys'] = count($files);
            
            foreach ($files as $file) {
                $stats['total_size'] += filesize($file);
            }
        }
        
        return $stats;
    }
    
    private function getRedisStats() {
        try {
            $redis = new Redis();
            $redis->connect('127.0.0.1', 6379);
            
            $info = $redis->info();
            $redis->close();
            
            return [
                'total_keys' => $info['db0']['keys'] ?? 0,
                'total_size' => $info['used_memory'] ?? 0,
                'types' => []
            ];
        } catch (Exception $e) {
            return ['total_keys' => 0, 'total_size' => 0, 'types' => []];
        }
    }
    
    private function clearRedisType($type) {
        // Implementation for clearing Redis cache by type
        return true;
    }
    
    private function clearFileType($type) {
        // Implementation for clearing file cache by type
        return true;
    }
    
    private function clearDatabaseType($type) {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "DELETE FROM cache_storage WHERE cache_type = ?";
            $stmt = $db->prepare($query);
            return $stmt->execute([$type]);
            
        } catch (PDOException $e) {
            error_log("Database cache clear error: " . $e->getMessage());
            return false;
        }
    }
    
    private function warmUserDataCache($pattern) {
        // Implementation for warming user data cache
        return 0;
    }
    
    private function warmTranslationCache($pattern) {
        // Implementation for warming translation cache
        return 0;
    }
    
    private function warmConfigurationCache($pattern) {
        // Implementation for warming configuration cache
        return 0;
    }
}

// Convenience functions
function cache_get($key, $type = CacheManager::TYPE_API_RESPONSE) {
    $cache = CacheManager::getInstance();
    return $cache->get($key, $type);
}

function cache_set($key, $data, $ttl = null, $type = CacheManager::TYPE_API_RESPONSE) {
    $cache = CacheManager::getInstance();
    return $cache->set($key, $data, $ttl, $type);
}

function cache_delete($key, $type = CacheManager::TYPE_API_RESPONSE) {
    $cache = CacheManager::getInstance();
    return $cache->delete($key, $type);
}

function cache_remember($key, $callback, $ttl = null, $type = CacheManager::TYPE_API_RESPONSE) {
    $cache = CacheManager::getInstance();
    return $cache->remember($key, $callback, $ttl, $type);
}

function cache_clear_type($type) {
    $cache = CacheManager::getInstance();
    return $cache->clearByType($type);
}

function cache_stats() {
    $cache = CacheManager::getInstance();
    return $cache->getStatistics();
}
?>

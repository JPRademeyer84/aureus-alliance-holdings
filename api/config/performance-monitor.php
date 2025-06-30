<?php
/**
 * PERFORMANCE MONITORING SYSTEM
 * Comprehensive performance tracking, optimization, and analytics
 */

require_once 'database.php';
require_once 'security-logger.php';

class PerformanceMonitor {
    private static $instance = null;
    private $db;
    private $startTime;
    private $memoryStart;
    private $queryCount = 0;
    private $queryTimes = [];
    private $cacheHits = 0;
    private $cacheMisses = 0;
    
    // Performance thresholds
    const SLOW_QUERY_THRESHOLD = 1000; // milliseconds
    const HIGH_MEMORY_THRESHOLD = 128 * 1024 * 1024; // 128MB
    const SLOW_REQUEST_THRESHOLD = 5000; // milliseconds
    
    private function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->startTime = microtime(true);
        $this->memoryStart = memory_get_usage(true);
        $this->initializePerformanceTables();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize performance monitoring tables
     */
    private function initializePerformanceTables() {
        $tables = [
            // Performance metrics
            "CREATE TABLE IF NOT EXISTS performance_metrics (
                id VARCHAR(36) PRIMARY KEY,
                endpoint VARCHAR(255) NOT NULL,
                method VARCHAR(10) NOT NULL,
                response_time_ms INT NOT NULL,
                memory_usage_mb DECIMAL(10,2) NOT NULL,
                query_count INT NOT NULL,
                cache_hit_ratio DECIMAL(5,2) DEFAULT 0,
                user_id VARCHAR(36),
                ip_address VARCHAR(45),
                user_agent TEXT,
                timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_endpoint (endpoint),
                INDEX idx_response_time (response_time_ms),
                INDEX idx_timestamp (timestamp)
            )",
            
            // Query performance
            "CREATE TABLE IF NOT EXISTS query_performance (
                id VARCHAR(36) PRIMARY KEY,
                query_hash VARCHAR(64) NOT NULL,
                query_type VARCHAR(50) NOT NULL,
                execution_time_ms DECIMAL(10,3) NOT NULL,
                rows_examined INT DEFAULT 0,
                rows_returned INT DEFAULT 0,
                query_text TEXT,
                endpoint VARCHAR(255),
                timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_query_hash (query_hash),
                INDEX idx_execution_time (execution_time_ms),
                INDEX idx_query_type (query_type)
            )",
            
            // Cache performance
            "CREATE TABLE IF NOT EXISTS cache_performance (
                id VARCHAR(36) PRIMARY KEY,
                cache_key VARCHAR(255) NOT NULL,
                cache_type VARCHAR(50) NOT NULL,
                hit_count INT DEFAULT 0,
                miss_count INT DEFAULT 0,
                last_hit TIMESTAMP NULL,
                last_miss TIMESTAMP NULL,
                data_size_bytes INT DEFAULT 0,
                ttl_seconds INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_cache_key (cache_key, cache_type),
                INDEX idx_cache_type (cache_type),
                INDEX idx_hit_ratio (hit_count, miss_count)
            )",
            
            // System performance
            "CREATE TABLE IF NOT EXISTS system_performance (
                id VARCHAR(36) PRIMARY KEY,
                cpu_usage DECIMAL(5,2) DEFAULT 0,
                memory_usage DECIMAL(5,2) DEFAULT 0,
                disk_usage DECIMAL(5,2) DEFAULT 0,
                active_connections INT DEFAULT 0,
                database_size_mb DECIMAL(10,2) DEFAULT 0,
                log_size_mb DECIMAL(10,2) DEFAULT 0,
                timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_timestamp (timestamp)
            )",
            
            // Performance alerts
            "CREATE TABLE IF NOT EXISTS performance_alerts (
                id VARCHAR(36) PRIMARY KEY,
                alert_type VARCHAR(50) NOT NULL,
                severity ENUM('low', 'medium', 'high', 'critical') NOT NULL,
                metric_name VARCHAR(100) NOT NULL,
                metric_value DECIMAL(15,3) NOT NULL,
                threshold_value DECIMAL(15,3) NOT NULL,
                endpoint VARCHAR(255),
                description TEXT,
                resolved BOOLEAN DEFAULT FALSE,
                resolved_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_alert_type (alert_type),
                INDEX idx_severity (severity),
                INDEX idx_resolved (resolved)
            )"
        ];
        
        foreach ($tables as $sql) {
            try {
                $this->db->exec($sql);
            } catch (PDOException $e) {
                error_log("Failed to create performance table: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Start monitoring a request
     */
    public function startRequest($endpoint = null, $method = null) {
        $this->startTime = microtime(true);
        $this->memoryStart = memory_get_usage(true);
        $this->queryCount = 0;
        $this->queryTimes = [];
        $this->cacheHits = 0;
        $this->cacheMisses = 0;
        
        if (!$endpoint) {
            $endpoint = $_SERVER['REQUEST_URI'] ?? 'unknown';
        }
        if (!$method) {
            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        }
        
        $this->currentEndpoint = $endpoint;
        $this->currentMethod = $method;
    }
    
    /**
     * Track database query performance
     */
    public function trackQuery($query, $executionTime, $rowsExamined = 0, $rowsReturned = 0) {
        $this->queryCount++;
        $this->queryTimes[] = $executionTime;
        
        // Log slow queries
        if ($executionTime > self::SLOW_QUERY_THRESHOLD) {
            $this->logSlowQuery($query, $executionTime, $rowsExamined, $rowsReturned);
        }
        
        // Store query performance data
        $this->storeQueryPerformance($query, $executionTime, $rowsExamined, $rowsReturned);
    }
    
    /**
     * Track cache performance
     */
    public function trackCacheHit($cacheKey, $cacheType = 'default') {
        $this->cacheHits++;
        $this->updateCacheStats($cacheKey, $cacheType, true);
    }
    
    public function trackCacheMiss($cacheKey, $cacheType = 'default') {
        $this->cacheMisses++;
        $this->updateCacheStats($cacheKey, $cacheType, false);
    }
    
    /**
     * End request monitoring and store metrics
     */
    public function endRequest() {
        $endTime = microtime(true);
        $responseTime = ($endTime - $this->startTime) * 1000; // milliseconds
        $memoryUsage = (memory_get_peak_usage(true) - $this->memoryStart) / 1024 / 1024; // MB
        
        $cacheHitRatio = ($this->cacheHits + $this->cacheMisses) > 0 
            ? ($this->cacheHits / ($this->cacheHits + $this->cacheMisses)) * 100 
            : 0;
        
        // Store performance metrics
        $this->storePerformanceMetrics($responseTime, $memoryUsage, $cacheHitRatio);
        
        // Check for performance alerts
        $this->checkPerformanceAlerts($responseTime, $memoryUsage);
        
        return [
            'response_time_ms' => round($responseTime, 2),
            'memory_usage_mb' => round($memoryUsage, 2),
            'query_count' => $this->queryCount,
            'cache_hit_ratio' => round($cacheHitRatio, 2)
        ];
    }
    
    /**
     * Get performance analytics
     */
    public function getPerformanceAnalytics($timeRange = '24h') {
        $whereClause = $this->getTimeRangeClause($timeRange);
        
        $analytics = [
            'summary' => $this->getPerformanceSummary($whereClause),
            'slow_endpoints' => $this->getSlowEndpoints($whereClause),
            'query_performance' => $this->getQueryPerformanceStats($whereClause),
            'cache_performance' => $this->getCachePerformanceStats($whereClause),
            'system_metrics' => $this->getSystemMetrics($whereClause),
            'alerts' => $this->getPerformanceAlerts($whereClause)
        ];
        
        return $analytics;
    }
    
    /**
     * Optimize database queries
     */
    public function optimizeQueries() {
        $optimizations = [];
        
        // Find slow queries
        $slowQueries = $this->findSlowQueries();
        foreach ($slowQueries as $query) {
            $optimization = $this->analyzeQueryOptimization($query);
            if ($optimization) {
                $optimizations[] = $optimization;
            }
        }
        
        // Find missing indexes
        $missingIndexes = $this->findMissingIndexes();
        $optimizations = array_merge($optimizations, $missingIndexes);
        
        return $optimizations;
    }
    
    /**
     * Private helper methods
     */
    
    private function storePerformanceMetrics($responseTime, $memoryUsage, $cacheHitRatio) {
        try {
            $metricId = bin2hex(random_bytes(16));
            
            $query = "INSERT INTO performance_metrics (
                id, endpoint, method, response_time_ms, memory_usage_mb, 
                query_count, cache_hit_ratio, user_id, ip_address, user_agent
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $metricId,
                $this->currentEndpoint ?? 'unknown',
                $this->currentMethod ?? 'GET',
                round($responseTime),
                round($memoryUsage, 2),
                $this->queryCount,
                round($cacheHitRatio, 2),
                $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
            
        } catch (PDOException $e) {
            error_log("Failed to store performance metrics: " . $e->getMessage());
        }
    }
    
    private function storeQueryPerformance($query, $executionTime, $rowsExamined, $rowsReturned) {
        try {
            $queryId = bin2hex(random_bytes(16));
            $queryHash = hash('sha256', $query);
            $queryType = $this->getQueryType($query);
            
            $insertQuery = "INSERT INTO query_performance (
                id, query_hash, query_type, execution_time_ms, rows_examined, 
                rows_returned, query_text, endpoint
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($insertQuery);
            $stmt->execute([
                $queryId,
                $queryHash,
                $queryType,
                round($executionTime, 3),
                $rowsExamined,
                $rowsReturned,
                $query,
                $this->currentEndpoint ?? 'unknown'
            ]);
            
        } catch (PDOException $e) {
            error_log("Failed to store query performance: " . $e->getMessage());
        }
    }
    
    private function updateCacheStats($cacheKey, $cacheType, $isHit) {
        try {
            $query = "INSERT INTO cache_performance (
                id, cache_key, cache_type, hit_count, miss_count, last_hit, last_miss
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                hit_count = hit_count + ?,
                miss_count = miss_count + ?,
                last_hit = IF(? = 1, NOW(), last_hit),
                last_miss = IF(? = 0, NOW(), last_miss)";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                bin2hex(random_bytes(16)),
                $cacheKey,
                $cacheType,
                $isHit ? 1 : 0,
                $isHit ? 0 : 1,
                $isHit ? date('Y-m-d H:i:s') : null,
                $isHit ? null : date('Y-m-d H:i:s'),
                $isHit ? 1 : 0,
                $isHit ? 0 : 1,
                $isHit ? 1 : 0,
                $isHit ? 1 : 0
            ]);
            
        } catch (PDOException $e) {
            error_log("Failed to update cache stats: " . $e->getMessage());
        }
    }
    
    private function checkPerformanceAlerts($responseTime, $memoryUsage) {
        $alerts = [];
        
        // Check slow response time
        if ($responseTime > self::SLOW_REQUEST_THRESHOLD) {
            $alerts[] = [
                'type' => 'slow_response',
                'severity' => $responseTime > 10000 ? 'critical' : 'high',
                'metric' => 'response_time_ms',
                'value' => $responseTime,
                'threshold' => self::SLOW_REQUEST_THRESHOLD
            ];
        }
        
        // Check high memory usage
        if ($memoryUsage > self::HIGH_MEMORY_THRESHOLD / 1024 / 1024) {
            $alerts[] = [
                'type' => 'high_memory',
                'severity' => $memoryUsage > 256 ? 'critical' : 'high',
                'metric' => 'memory_usage_mb',
                'value' => $memoryUsage,
                'threshold' => self::HIGH_MEMORY_THRESHOLD / 1024 / 1024
            ];
        }
        
        // Store alerts
        foreach ($alerts as $alert) {
            $this->storePerformanceAlert($alert);
        }
    }
    
    private function storePerformanceAlert($alert) {
        try {
            $alertId = bin2hex(random_bytes(16));
            
            $query = "INSERT INTO performance_alerts (
                id, alert_type, severity, metric_name, metric_value, 
                threshold_value, endpoint, description
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $description = "Performance alert: {$alert['metric']} = {$alert['value']} exceeds threshold {$alert['threshold']}";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $alertId,
                $alert['type'],
                $alert['severity'],
                $alert['metric'],
                $alert['value'],
                $alert['threshold'],
                $this->currentEndpoint ?? 'unknown',
                $description
            ]);
            
        } catch (PDOException $e) {
            error_log("Failed to store performance alert: " . $e->getMessage());
        }
    }
    
    private function getTimeRangeClause($timeRange) {
        switch ($timeRange) {
            case '1h':
                return "WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";
            case '24h':
                return "WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
            case '7d':
                return "WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            case '30d':
                return "WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            default:
                return "WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        }
    }
    
    private function getPerformanceSummary($whereClause) {
        try {
            $query = "SELECT 
                COUNT(*) as total_requests,
                AVG(response_time_ms) as avg_response_time,
                MAX(response_time_ms) as max_response_time,
                AVG(memory_usage_mb) as avg_memory_usage,
                AVG(query_count) as avg_query_count,
                AVG(cache_hit_ratio) as avg_cache_hit_ratio
            FROM performance_metrics $whereClause";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Failed to get performance summary: " . $e->getMessage());
            return [];
        }
    }
    
    private function getSlowEndpoints($whereClause) {
        try {
            $query = "SELECT 
                endpoint,
                COUNT(*) as request_count,
                AVG(response_time_ms) as avg_response_time,
                MAX(response_time_ms) as max_response_time
            FROM performance_metrics $whereClause
            GROUP BY endpoint
            HAVING avg_response_time > 1000
            ORDER BY avg_response_time DESC
            LIMIT 10";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Failed to get slow endpoints: " . $e->getMessage());
            return [];
        }
    }
    
    private function getQueryType($query) {
        $query = trim(strtoupper($query));
        if (strpos($query, 'SELECT') === 0) return 'SELECT';
        if (strpos($query, 'INSERT') === 0) return 'INSERT';
        if (strpos($query, 'UPDATE') === 0) return 'UPDATE';
        if (strpos($query, 'DELETE') === 0) return 'DELETE';
        return 'OTHER';
    }
    
    private function logSlowQuery($query, $executionTime, $rowsExamined, $rowsReturned) {
        logSecurityEvent(SecurityLogger::EVENT_SYSTEM, 'slow_query_detected', SecurityLogger::LEVEL_WARNING,
            'Slow database query detected', [
                'execution_time_ms' => $executionTime,
                'rows_examined' => $rowsExamined,
                'rows_returned' => $rowsReturned,
                'query_preview' => substr($query, 0, 200)
            ]);
    }
    
    private function getQueryPerformanceStats($whereClause) {
        // Implementation for query performance statistics
        return [];
    }
    
    private function getCachePerformanceStats($whereClause) {
        // Implementation for cache performance statistics
        return [];
    }
    
    private function getSystemMetrics($whereClause) {
        // Implementation for system metrics
        return [];
    }
    
    private function getPerformanceAlerts($whereClause) {
        // Implementation for performance alerts
        return [];
    }
    
    private function findSlowQueries() {
        try {
            $query = "SELECT
                query_hash,
                query_type,
                AVG(execution_time_ms) as avg_time,
                COUNT(*) as execution_count,
                MAX(execution_time_ms) as max_time,
                query_text
            FROM query_performance
            WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            GROUP BY query_hash
            HAVING avg_time > ?
            ORDER BY avg_time DESC
            LIMIT 20";

            $stmt = $this->db->prepare($query);
            $stmt->execute([self::SLOW_QUERY_THRESHOLD]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Failed to find slow queries: " . $e->getMessage());
            return [];
        }
    }

    private function analyzeQueryOptimization($query) {
        $optimizations = [];
        $queryText = $query['query_text'];

        // Check for missing WHERE clauses
        if (stripos($queryText, 'SELECT') === 0 && stripos($queryText, 'WHERE') === false) {
            $optimizations[] = [
                'type' => 'missing_where_clause',
                'severity' => 'high',
                'description' => 'Query lacks WHERE clause, may scan entire table',
                'recommendation' => 'Add appropriate WHERE conditions to limit result set',
                'auto_apply' => false
            ];
        }

        // Check for SELECT *
        if (stripos($queryText, 'SELECT *') !== false) {
            $optimizations[] = [
                'type' => 'select_star',
                'severity' => 'medium',
                'description' => 'Query uses SELECT *, retrieving unnecessary columns',
                'recommendation' => 'Specify only required columns in SELECT clause',
                'auto_apply' => false
            ];
        }

        // Check for ORDER BY without LIMIT
        if (stripos($queryText, 'ORDER BY') !== false && stripos($queryText, 'LIMIT') === false) {
            $optimizations[] = [
                'type' => 'order_without_limit',
                'severity' => 'medium',
                'description' => 'Query uses ORDER BY without LIMIT, sorting entire result set',
                'recommendation' => 'Add LIMIT clause if not all results are needed',
                'auto_apply' => false
            ];
        }

        // Check for subqueries that could be JOINs
        if (stripos($queryText, 'IN (SELECT') !== false) {
            $optimizations[] = [
                'type' => 'subquery_to_join',
                'severity' => 'medium',
                'description' => 'Query uses subquery that could be optimized as JOIN',
                'recommendation' => 'Consider rewriting subquery as JOIN for better performance',
                'auto_apply' => false
            ];
        }

        return $optimizations;
    }

    private function findMissingIndexes() {
        $recommendations = [];

        try {
            // Analyze slow queries for potential index opportunities
            $slowQueries = $this->findSlowQueries();

            foreach ($slowQueries as $query) {
                $indexRecommendations = $this->analyzeIndexOpportunities($query['query_text']);
                $recommendations = array_merge($recommendations, $indexRecommendations);
            }

            // Check for common index patterns
            $commonIndexes = $this->checkCommonIndexPatterns();
            $recommendations = array_merge($recommendations, $commonIndexes);

        } catch (Exception $e) {
            error_log("Failed to find missing indexes: " . $e->getMessage());
        }

        return $recommendations;
    }

    private function analyzeIndexOpportunities($queryText) {
        $recommendations = [];

        // Extract table names and WHERE conditions
        if (preg_match_all('/FROM\s+(\w+)/i', $queryText, $tableMatches)) {
            foreach ($tableMatches[1] as $table) {
                // Check for WHERE conditions on this table
                if (preg_match_all('/WHERE\s+.*?(\w+)\s*[=<>]/i', $queryText, $whereMatches)) {
                    foreach ($whereMatches[1] as $column) {
                        if ($this->shouldRecommendIndex($table, $column)) {
                            $recommendations[] = [
                                'type' => 'missing_index',
                                'severity' => 'medium',
                                'table' => $table,
                                'column' => $column,
                                'description' => "Consider adding index on {$table}.{$column}",
                                'recommendation' => "CREATE INDEX idx_{$table}_{$column} ON {$table} ({$column})",
                                'auto_apply' => false
                            ];
                        }
                    }
                }
            }
        }

        return $recommendations;
    }

    private function checkCommonIndexPatterns() {
        $recommendations = [];

        // Check for common tables that should have indexes
        $commonIndexes = [
            'users' => ['email', 'username', 'created_at'],
            'user_profiles' => ['user_id'],
            'aureus_investments' => ['user_id', 'created_at'],
            'commission_transactions' => ['user_id', 'created_at'],
            'security_events' => ['user_id', 'created_at', 'event_level'],
            'performance_metrics' => ['endpoint', 'timestamp'],
            'cache_storage' => ['cache_type', 'expires_at']
        ];

        foreach ($commonIndexes as $table => $columns) {
            foreach ($columns as $column) {
                if ($this->shouldRecommendIndex($table, $column)) {
                    $recommendations[] = [
                        'type' => 'recommended_index',
                        'severity' => 'low',
                        'table' => $table,
                        'column' => $column,
                        'description' => "Recommended index for common query patterns",
                        'recommendation' => "CREATE INDEX idx_{$table}_{$column} ON {$table} ({$column})",
                        'auto_apply' => true
                    ];
                }
            }
        }

        return $recommendations;
    }

    private function shouldRecommendIndex($table, $column) {
        try {
            // Check if index already exists
            $query = "SHOW INDEX FROM {$table} WHERE Column_name = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$column]);

            return $stmt->rowCount() === 0;
        } catch (PDOException $e) {
            // Table might not exist or other error
            return false;
        }
    }
}

// Convenience functions
function startPerformanceMonitoring($endpoint = null, $method = null) {
    $monitor = PerformanceMonitor::getInstance();
    $monitor->startRequest($endpoint, $method);
}

function trackQueryPerformance($query, $executionTime, $rowsExamined = 0, $rowsReturned = 0) {
    $monitor = PerformanceMonitor::getInstance();
    $monitor->trackQuery($query, $executionTime, $rowsExamined, $rowsReturned);
}

function trackCacheHit($cacheKey, $cacheType = 'default') {
    $monitor = PerformanceMonitor::getInstance();
    $monitor->trackCacheHit($cacheKey, $cacheType);
}

function trackCacheMiss($cacheKey, $cacheType = 'default') {
    $monitor = PerformanceMonitor::getInstance();
    $monitor->trackCacheMiss($cacheKey, $cacheType);
}

function endPerformanceMonitoring() {
    $monitor = PerformanceMonitor::getInstance();
    return $monitor->endRequest();
}

function getPerformanceAnalytics($timeRange = '24h') {
    $monitor = PerformanceMonitor::getInstance();
    return $monitor->getPerformanceAnalytics($timeRange);
}
?>

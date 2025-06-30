<?php
/**
 * PERFORMANCE OPTIMIZATION API
 * Administrative interface for performance monitoring, optimization, and analytics
 */

require_once '../config/cors.php';
require_once '../config/secure-session.php';
require_once '../config/performance-monitor.php';
require_once '../config/cache-manager.php';
require_once '../config/mfa-system.php';

// Handle CORS and preflight requests
handlePreflight();
setCorsHeaders();

// Start secure session
SecureSession::start();

// Check admin authentication and require fresh MFA
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Admin authentication required']);
    exit;
}

// Require fresh MFA for performance operations
requireFreshMFA('admin', 300); // 5 minutes

// Start performance monitoring for this request
startPerformanceMonitoring();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'dashboard';

try {
    switch ($action) {
        case 'dashboard':
            getPerformanceDashboard();
            break;
            
        case 'analytics':
            getPerformanceAnalytics();
            break;
            
        case 'optimize_queries':
            optimizeDatabaseQueries();
            break;
            
        case 'cache_management':
            manageCacheSystem();
            break;
            
        case 'system_metrics':
            getSystemMetrics();
            break;
            
        case 'performance_alerts':
            getPerformanceAlerts();
            break;
            
        case 'optimization_recommendations':
            getOptimizationRecommendations();
            break;
            
        case 'warm_cache':
            warmSystemCache();
            break;
            
        case 'clear_cache':
            clearSystemCache();
            break;
            
        case 'benchmark':
            runPerformanceBenchmark();
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            exit;
    }
    
} catch (Exception $e) {
    error_log("Performance optimization error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Performance operation failed: ' . $e->getMessage()]);
} finally {
    // End performance monitoring
    $metrics = endPerformanceMonitoring();
    if (isset($metrics)) {
        header('X-Performance-Time: ' . $metrics['response_time_ms'] . 'ms');
        header('X-Performance-Memory: ' . $metrics['memory_usage_mb'] . 'MB');
        header('X-Performance-Queries: ' . $metrics['query_count']);
    }
}

/**
 * Get performance dashboard
 */
function getPerformanceDashboard() {
    $timeRange = $_GET['time_range'] ?? '24h';
    
    $dashboard = [
        'overview' => getPerformanceOverview($timeRange),
        'recent_metrics' => getRecentPerformanceMetrics(),
        'slow_endpoints' => getSlowEndpoints($timeRange),
        'cache_statistics' => getCacheStatistics(),
        'system_health' => getSystemHealthMetrics(),
        'optimization_opportunities' => getOptimizationOpportunities()
    ];
    
    echo json_encode([
        'success' => true,
        'dashboard' => $dashboard,
        'time_range' => $timeRange,
        'last_updated' => date('c')
    ]);
}

/**
 * Get performance analytics
 */
function getPerformanceAnalytics() {
    $timeRange = $_GET['time_range'] ?? '24h';
    $metric = $_GET['metric'] ?? 'all';
    
    $analytics = getPerformanceAnalytics($timeRange);
    
    if ($metric !== 'all' && isset($analytics[$metric])) {
        $analytics = [$metric => $analytics[$metric]];
    }
    
    echo json_encode([
        'success' => true,
        'analytics' => $analytics,
        'time_range' => $timeRange,
        'metric' => $metric
    ]);
}

/**
 * Optimize database queries
 */
function optimizeDatabaseQueries() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $monitor = PerformanceMonitor::getInstance();
    $optimizations = $monitor->optimizeQueries();
    
    $applied = 0;
    $recommendations = [];
    
    foreach ($optimizations as $optimization) {
        if ($optimization['auto_apply'] ?? false) {
            // Apply automatic optimizations
            if (applyQueryOptimization($optimization)) {
                $applied++;
            }
        } else {
            // Add to recommendations
            $recommendations[] = $optimization;
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Query optimization completed',
        'applied_optimizations' => $applied,
        'recommendations' => $recommendations,
        'total_optimizations' => count($optimizations)
    ]);
}

/**
 * Manage cache system
 */
function manageCacheSystem() {
    $operation = $_GET['operation'] ?? 'status';
    $cache = CacheManager::getInstance();
    
    switch ($operation) {
        case 'status':
            $result = $cache->getStatistics();
            break;
            
        case 'clear':
            $type = $_GET['type'] ?? null;
            if ($type) {
                $result = $cache->clearByType($type);
                $message = "Cache cleared for type: $type";
            } else {
                // Clear all cache types
                $types = [
                    CacheManager::TYPE_USER_DATA,
                    CacheManager::TYPE_API_RESPONSE,
                    CacheManager::TYPE_DATABASE_QUERY,
                    CacheManager::TYPE_TRANSLATION,
                    CacheManager::TYPE_CONFIGURATION
                ];
                
                $cleared = 0;
                foreach ($types as $cacheType) {
                    if ($cache->clearByType($cacheType)) {
                        $cleared++;
                    }
                }
                
                $result = ['cleared_types' => $cleared];
                $message = "Cleared $cleared cache types";
            }
            break;
            
        case 'warm':
            $patterns = $_POST['patterns'] ?? [
                ['type' => 'user_data'],
                ['type' => 'translations'],
                ['type' => 'configurations']
            ];
            
            $warmed = $cache->warmCache($patterns);
            $result = ['warmed_items' => $warmed];
            $message = "Warmed $warmed cache items";
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid cache operation']);
            return;
    }
    
    echo json_encode([
        'success' => true,
        'operation' => $operation,
        'result' => $result,
        'message' => $message ?? 'Cache operation completed'
    ]);
}

/**
 * Get system metrics
 */
function getSystemMetrics() {
    $metrics = [
        'server_metrics' => getServerMetrics(),
        'database_metrics' => getDatabaseMetrics(),
        'application_metrics' => getApplicationMetrics(),
        'resource_usage' => getResourceUsage()
    ];
    
    echo json_encode([
        'success' => true,
        'metrics' => $metrics,
        'timestamp' => date('c')
    ]);
}

/**
 * Get performance alerts
 */
function getPerformanceAlerts() {
    $severity = $_GET['severity'] ?? 'all';
    $limit = (int)($_GET['limit'] ?? 50);
    $resolved = $_GET['resolved'] ?? 'false';
    
    $database = new Database();
    $db = $database->getConnection();
    
    $whereConditions = [];
    $params = [];
    
    if ($severity !== 'all') {
        $whereConditions[] = "severity = ?";
        $params[] = $severity;
    }
    
    if ($resolved === 'false') {
        $whereConditions[] = "resolved = FALSE";
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    $query = "SELECT * FROM performance_alerts 
              $whereClause
              ORDER BY created_at DESC 
              LIMIT ?";
    $params[] = $limit;
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $alerts = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'alerts' => $alerts,
        'filters' => [
            'severity' => $severity,
            'resolved' => $resolved,
            'limit' => $limit
        ]
    ]);
}

/**
 * Get optimization recommendations
 */
function getOptimizationRecommendations() {
    $recommendations = [
        'database' => getDatabaseOptimizationRecommendations(),
        'cache' => getCacheOptimizationRecommendations(),
        'api' => getAPIOptimizationRecommendations(),
        'frontend' => getFrontendOptimizationRecommendations()
    ];
    
    // Calculate priority scores
    foreach ($recommendations as $category => &$categoryRecs) {
        foreach ($categoryRecs as &$rec) {
            $rec['priority_score'] = calculatePriorityScore($rec);
        }
        
        // Sort by priority score
        usort($categoryRecs, function($a, $b) {
            return $b['priority_score'] - $a['priority_score'];
        });
    }
    
    echo json_encode([
        'success' => true,
        'recommendations' => $recommendations,
        'generated_at' => date('c')
    ]);
}

/**
 * Warm system cache
 */
function warmSystemCache() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $cacheTypes = $input['cache_types'] ?? ['all'];
    
    $cache = CacheManager::getInstance();
    $warmed = 0;
    
    if (in_array('all', $cacheTypes)) {
        $patterns = [
            ['type' => 'user_data'],
            ['type' => 'translations'],
            ['type' => 'configurations'],
            ['type' => 'api_responses']
        ];
    } else {
        $patterns = array_map(function($type) {
            return ['type' => $type];
        }, $cacheTypes);
    }
    
    $warmed = $cache->warmCache($patterns);
    
    echo json_encode([
        'success' => true,
        'message' => 'Cache warming completed',
        'warmed_items' => $warmed,
        'cache_types' => $cacheTypes
    ]);
}

/**
 * Clear system cache
 */
function clearSystemCache() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $cacheTypes = $input['cache_types'] ?? ['all'];
    
    $cache = CacheManager::getInstance();
    $cleared = 0;
    
    if (in_array('all', $cacheTypes)) {
        $types = [
            CacheManager::TYPE_USER_DATA,
            CacheManager::TYPE_API_RESPONSE,
            CacheManager::TYPE_DATABASE_QUERY,
            CacheManager::TYPE_TRANSLATION,
            CacheManager::TYPE_CONFIGURATION
        ];
    } else {
        $types = $cacheTypes;
    }
    
    foreach ($types as $type) {
        if ($cache->clearByType($type)) {
            $cleared++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Cache clearing completed',
        'cleared_types' => $cleared,
        'cache_types' => $cacheTypes
    ]);
}

/**
 * Run performance benchmark
 */
function runPerformanceBenchmark() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $benchmarkType = $input['benchmark_type'] ?? 'comprehensive';
    
    $benchmark = [
        'type' => $benchmarkType,
        'started_at' => date('c'),
        'results' => []
    ];
    
    switch ($benchmarkType) {
        case 'database':
            $benchmark['results'] = runDatabaseBenchmark();
            break;
        case 'cache':
            $benchmark['results'] = runCacheBenchmark();
            break;
        case 'api':
            $benchmark['results'] = runAPIBenchmark();
            break;
        case 'comprehensive':
            $benchmark['results'] = [
                'database' => runDatabaseBenchmark(),
                'cache' => runCacheBenchmark(),
                'api' => runAPIBenchmark()
            ];
            break;
    }
    
    $benchmark['completed_at'] = date('c');
    $benchmark['duration_ms'] = (strtotime($benchmark['completed_at']) - strtotime($benchmark['started_at'])) * 1000;
    
    echo json_encode([
        'success' => true,
        'benchmark' => $benchmark
    ]);
}

/**
 * Helper functions
 */

function getPerformanceOverview($timeRange) {
    $analytics = getPerformanceAnalytics($timeRange);
    
    return [
        'avg_response_time' => $analytics['summary']['avg_response_time'] ?? 0,
        'total_requests' => $analytics['summary']['total_requests'] ?? 0,
        'error_rate' => calculateErrorRate($timeRange),
        'cache_hit_ratio' => $analytics['summary']['avg_cache_hit_ratio'] ?? 0,
        'slow_requests' => count($analytics['slow_endpoints'] ?? [])
    ];
}

function getRecentPerformanceMetrics() {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM performance_metrics 
              ORDER BY timestamp DESC 
              LIMIT 10";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

function getSlowEndpoints($timeRange) {
    $analytics = getPerformanceAnalytics($timeRange);
    return $analytics['slow_endpoints'] ?? [];
}

function getCacheStatistics() {
    return cache_stats();
}

function getSystemHealthMetrics() {
    return [
        'cpu_usage' => getCPUUsage(),
        'memory_usage' => getMemoryUsage(),
        'disk_usage' => getDiskUsage(),
        'database_connections' => getDatabaseConnections(),
        'uptime' => getSystemUptime()
    ];
}

function getOptimizationOpportunities() {
    return [
        'slow_queries' => getSlowQueriesCount(),
        'cache_misses' => getCacheMissesCount(),
        'large_responses' => getLargeResponsesCount(),
        'optimization_score' => calculateOptimizationScore()
    ];
}

// Placeholder implementations for system metrics
function getCPUUsage() { return rand(10, 80); }
function getMemoryUsage() { return rand(30, 70); }
function getDiskUsage() { return rand(20, 60); }
function getDatabaseConnections() { return rand(5, 25); }
function getSystemUptime() { return '99.9%'; }
function getSlowQueriesCount() { return rand(0, 5); }
function getCacheMissesCount() { return rand(10, 100); }
function getLargeResponsesCount() { return rand(0, 10); }
function calculateOptimizationScore() { return rand(75, 95); }
function calculateErrorRate($timeRange) { return rand(0, 5); }

function getServerMetrics() { return ['status' => 'healthy']; }
function getDatabaseMetrics() { return ['status' => 'healthy']; }
function getApplicationMetrics() { return ['status' => 'healthy']; }
function getResourceUsage() { return ['status' => 'optimal']; }

function getDatabaseOptimizationRecommendations() { return []; }
function getCacheOptimizationRecommendations() { return []; }
function getAPIOptimizationRecommendations() { return []; }
function getFrontendOptimizationRecommendations() { return []; }

function calculatePriorityScore($recommendation) { return rand(1, 100); }

function applyQueryOptimization($optimization) { return true; }

function runDatabaseBenchmark() { return ['score' => rand(80, 95)]; }
function runCacheBenchmark() { return ['score' => rand(85, 98)]; }
function runAPIBenchmark() { return ['score' => rand(75, 90)]; }
?>

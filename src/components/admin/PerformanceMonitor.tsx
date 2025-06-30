import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Switch } from '@/components/ui/switch';
import { Progress } from '@/components/ui/progress';
import { 
  Activity, 
  Zap, 
  Database, 
  Globe, 
  Image,
  RefreshCw,
  Settings,
  TrendingUp,
  TrendingDown,
  AlertTriangle,
  CheckCircle,
  Clock,
  MemoryStick,
  Wifi,
  HardDrive
} from 'lucide-react';
import { usePerformanceOptimization } from '@/hooks/usePerformanceOptimization';

const PerformanceMonitor: React.FC = () => {
  const {
    metrics,
    settings,
    setSettings,
    runOptimizations,
    isOptimizing,
    performanceScore,
    isPerformant
  } = usePerformanceOptimization();

  const [lastUpdated, setLastUpdated] = useState<Date>(new Date());

  useEffect(() => {
    const interval = setInterval(() => {
      setLastUpdated(new Date());
    }, 5000);

    return () => clearInterval(interval);
  }, []);

  const getScoreColor = (score: number) => {
    if (score >= 90) return 'text-green-500';
    if (score >= 70) return 'text-yellow-500';
    return 'text-red-500';
  };

  const getScoreBadge = (score: number) => {
    if (score >= 90) return <Badge className="bg-green-500">Excellent</Badge>;
    if (score >= 70) return <Badge className="bg-yellow-500">Good</Badge>;
    if (score >= 50) return <Badge className="bg-orange-500">Fair</Badge>;
    return <Badge className="bg-red-500">Poor</Badge>;
  };

  const formatBytes = (bytes: number) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  };

  const formatTime = (ms: number) => {
    if (ms < 1000) return `${ms}ms`;
    return `${(ms / 1000).toFixed(2)}s`;
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-3xl font-bold tracking-tight">Performance Monitor</h2>
          <p className="text-muted-foreground">
            Monitor and optimize application performance metrics
          </p>
        </div>
        
        <div className="flex items-center gap-4">
          <Button 
            onClick={runOptimizations} 
            disabled={isOptimizing}
            variant="outline"
          >
            <RefreshCw className={`w-4 h-4 mr-2 ${isOptimizing ? 'animate-spin' : ''}`} />
            {isOptimizing ? 'Optimizing...' : 'Run Optimizations'}
          </Button>
        </div>
      </div>

      {/* Performance Score Overview */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Activity className="w-5 h-5" />
            Performance Score
          </CardTitle>
          <CardDescription>
            Overall application performance rating
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="flex items-center justify-between mb-4">
            <div className="flex items-center gap-4">
              <div className={`text-4xl font-bold ${getScoreColor(performanceScore)}`}>
                {performanceScore}
              </div>
              <div className="flex flex-col gap-2">
                {getScoreBadge(performanceScore)}
                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                  {isPerformant ? (
                    <>
                      <CheckCircle className="w-4 h-4 text-green-500" />
                      Performing well
                    </>
                  ) : (
                    <>
                      <AlertTriangle className="w-4 h-4 text-yellow-500" />
                      Needs optimization
                    </>
                  )}
                </div>
              </div>
            </div>
            <div className="text-right">
              <div className="text-sm text-muted-foreground">Last updated</div>
              <div className="text-sm font-medium">{lastUpdated.toLocaleTimeString()}</div>
            </div>
          </div>
          <Progress value={performanceScore} className="h-2" />
        </CardContent>
      </Card>

      {/* Performance Metrics */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center gap-2">
              <Clock className="w-4 h-4 text-blue-500" />
              <div>
                <p className="text-sm text-muted-foreground">Load Time</p>
                <p className="text-2xl font-bold">{formatTime(metrics.loadTime)}</p>
                <p className="text-xs text-muted-foreground">
                  {metrics.loadTime < 3000 ? 'Good' : 'Needs improvement'}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
        
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center gap-2">
              <Zap className="w-4 h-4 text-yellow-500" />
              <div>
                <p className="text-sm text-muted-foreground">Render Time</p>
                <p className="text-2xl font-bold">{formatTime(metrics.renderTime)}</p>
                <p className="text-xs text-muted-foreground">
                  {metrics.renderTime < 1500 ? 'Good' : 'Needs improvement'}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
        
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center gap-2">
              <MemoryStick className="w-4 h-4 text-purple-500" />
              <div>
                <p className="text-sm text-muted-foreground">Memory Usage</p>
                <p className="text-2xl font-bold">{metrics.memoryUsage.toFixed(1)} MB</p>
                <p className="text-xs text-muted-foreground">
                  {metrics.memoryUsage < 50 ? 'Good' : 'High usage'}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
        
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center gap-2">
              <Activity className="w-4 h-4 text-green-500" />
              <div>
                <p className="text-sm text-muted-foreground">FPS</p>
                <p className="text-2xl font-bold">{metrics.fps}</p>
                <p className="text-xs text-muted-foreground">
                  {metrics.fps >= 30 ? 'Smooth' : 'Choppy'}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Network & Cache Metrics */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Wifi className="w-5 h-5" />
              Network Activity
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              <div className="flex justify-between items-center">
                <span className="text-sm text-muted-foreground">Network Requests</span>
                <span className="font-bold">{metrics.networkRequests}</span>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-sm text-muted-foreground">Cache Hit Rate</span>
                <span className="font-bold">{metrics.cacheHitRate}</span>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-sm text-muted-foreground">Cache Efficiency</span>
                <span className="font-bold">
                  {metrics.networkRequests > 0 
                    ? Math.round((metrics.cacheHitRate / metrics.networkRequests) * 100)
                    : 0}%
                </span>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <HardDrive className="w-5 h-5" />
              Bundle Information
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              <div className="flex justify-between items-center">
                <span className="text-sm text-muted-foreground">Bundle Size</span>
                <span className="font-bold">{formatBytes(metrics.bundleSize)}</span>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-sm text-muted-foreground">Compression</span>
                <span className="font-bold">
                  {settings.enableCompression ? 'Enabled' : 'Disabled'}
                </span>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-sm text-muted-foreground">Code Splitting</span>
                <span className="font-bold">
                  {settings.enableCodeSplitting ? 'Enabled' : 'Disabled'}
                </span>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Optimization Settings */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Settings className="w-5 h-5" />
            Optimization Settings
          </CardTitle>
          <CardDescription>
            Configure performance optimization features
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div className="space-y-4">
              <div className="flex items-center justify-between">
                <div>
                  <div className="font-medium">Image Lazy Loading</div>
                  <div className="text-sm text-muted-foreground">
                    Load images only when they enter the viewport
                  </div>
                </div>
                <Switch
                  checked={settings.enableImageLazyLoading}
                  onCheckedChange={(checked) =>
                    setSettings(prev => ({ ...prev, enableImageLazyLoading: checked }))
                  }
                />
              </div>
              
              <div className="flex items-center justify-between">
                <div>
                  <div className="font-medium">Code Splitting</div>
                  <div className="text-sm text-muted-foreground">
                    Split code into smaller chunks for faster loading
                  </div>
                </div>
                <Switch
                  checked={settings.enableCodeSplitting}
                  onCheckedChange={(checked) =>
                    setSettings(prev => ({ ...prev, enableCodeSplitting: checked }))
                  }
                />
              </div>
              
              <div className="flex items-center justify-between">
                <div>
                  <div className="font-medium">API Caching</div>
                  <div className="text-sm text-muted-foreground">
                    Cache API responses for faster subsequent requests
                  </div>
                </div>
                <Switch
                  checked={settings.enableCaching}
                  onCheckedChange={(checked) =>
                    setSettings(prev => ({ ...prev, enableCaching: checked }))
                  }
                />
              </div>
            </div>
            
            <div className="space-y-4">
              <div className="flex items-center justify-between">
                <div>
                  <div className="font-medium">Resource Preloading</div>
                  <div className="text-sm text-muted-foreground">
                    Preload critical resources for faster navigation
                  </div>
                </div>
                <Switch
                  checked={settings.enablePreloading}
                  onCheckedChange={(checked) =>
                    setSettings(prev => ({ ...prev, enablePreloading: checked }))
                  }
                />
              </div>
              
              <div className="flex items-center justify-between">
                <div>
                  <div className="font-medium">Service Worker</div>
                  <div className="text-sm text-muted-foreground">
                    Enable offline support and advanced caching
                  </div>
                </div>
                <Switch
                  checked={settings.enableServiceWorker}
                  onCheckedChange={(checked) =>
                    setSettings(prev => ({ ...prev, enableServiceWorker: checked }))
                  }
                />
              </div>
              
              <div className="flex items-center justify-between">
                <div>
                  <div className="font-medium">Compression</div>
                  <div className="text-sm text-muted-foreground">
                    Compress assets for reduced bandwidth usage
                  </div>
                </div>
                <Switch
                  checked={settings.enableCompression}
                  onCheckedChange={(checked) =>
                    setSettings(prev => ({ ...prev, enableCompression: checked }))
                  }
                />
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Performance Recommendations */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <TrendingUp className="w-5 h-5" />
            Performance Recommendations
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-3">
            {metrics.loadTime > 3000 && (
              <div className="flex items-start gap-3 p-3 bg-yellow-50 rounded-lg">
                <AlertTriangle className="w-5 h-5 text-yellow-500 mt-0.5" />
                <div>
                  <div className="font-medium text-yellow-800">Slow Load Time</div>
                  <div className="text-sm text-yellow-700">
                    Consider enabling compression and optimizing images to improve load times.
                  </div>
                </div>
              </div>
            )}
            
            {metrics.memoryUsage > 100 && (
              <div className="flex items-start gap-3 p-3 bg-red-50 rounded-lg">
                <AlertTriangle className="w-5 h-5 text-red-500 mt-0.5" />
                <div>
                  <div className="font-medium text-red-800">High Memory Usage</div>
                  <div className="text-sm text-red-700">
                    Memory usage is high. Consider implementing code splitting and lazy loading.
                  </div>
                </div>
              </div>
            )}
            
            {metrics.fps < 30 && (
              <div className="flex items-start gap-3 p-3 bg-orange-50 rounded-lg">
                <TrendingDown className="w-5 h-5 text-orange-500 mt-0.5" />
                <div>
                  <div className="font-medium text-orange-800">Low Frame Rate</div>
                  <div className="text-sm text-orange-700">
                    Frame rate is below optimal. Check for heavy animations or DOM manipulations.
                  </div>
                </div>
              </div>
            )}
            
            {performanceScore >= 90 && (
              <div className="flex items-start gap-3 p-3 bg-green-50 rounded-lg">
                <CheckCircle className="w-5 h-5 text-green-500 mt-0.5" />
                <div>
                  <div className="font-medium text-green-800">Excellent Performance</div>
                  <div className="text-sm text-green-700">
                    Your application is performing excellently! Keep up the good work.
                  </div>
                </div>
              </div>
            )}
          </div>
        </CardContent>
      </Card>
    </div>
  );
};

export default PerformanceMonitor;

import React, { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { useDebug } from '@/contexts/DebugContext';
import { 
  Settings, 
  X, 
  Copy, 
  Trash2, 
  Monitor,
  Wifi,
  Database,
  User,
  Package,
  Wallet,
  Activity
} from 'lucide-react';

const DebugPanel: React.FC = () => {
  const { 
    isDebugMode, 
    toggleDebugMode, 
    debugHistory, 
    clearDebugHistory 
  } = useDebug();
  
  const [isVisible, setIsVisible] = useState(false);

  const getSystemInfo = () => {
    return {
      userAgent: navigator.userAgent,
      platform: navigator.platform,
      language: navigator.language,
      cookieEnabled: navigator.cookieEnabled,
      onLine: navigator.onLine,
      screen: `${screen.width}x${screen.height}`,
      viewport: `${window.innerWidth}x${window.innerHeight}`,
      localStorage: (() => {
        try {
          return Object.keys(localStorage).length;
        } catch {
          return 'Not available';
        }
      })(),
      sessionStorage: (() => {
        try {
          return Object.keys(sessionStorage).length;
        } catch {
          return 'Not available';
        }
      })(),
      url: window.location.href,
      timestamp: new Date().toISOString()
    };
  };

  const getPerformanceInfo = () => {
    if (!performance.getEntriesByType) return null;
    
    const navigation = performance.getEntriesByType('navigation')[0] as PerformanceNavigationTiming;
    const paint = performance.getEntriesByType('paint');
    
    return {
      domContentLoaded: navigation?.domContentLoadedEventEnd - navigation?.domContentLoadedEventStart,
      loadComplete: navigation?.loadEventEnd - navigation?.loadEventStart,
      firstPaint: paint.find(p => p.name === 'first-paint')?.startTime,
      firstContentfulPaint: paint.find(p => p.name === 'first-contentful-paint')?.startTime,
      memoryUsage: (performance as any).memory ? {
        used: Math.round((performance as any).memory.usedJSHeapSize / 1024 / 1024),
        total: Math.round((performance as any).memory.totalJSHeapSize / 1024 / 1024),
        limit: Math.round((performance as any).memory.jsHeapSizeLimit / 1024 / 1024)
      } : null
    };
  };

  const copySystemInfo = () => {
    const systemInfo = getSystemInfo();
    const performanceInfo = getPerformanceInfo();
    const debugInfo = {
      system: systemInfo,
      performance: performanceInfo,
      debugHistory: debugHistory.slice(-10), // Last 10 entries
      timestamp: new Date().toISOString()
    };
    
    navigator.clipboard.writeText(JSON.stringify(debugInfo, null, 2));
    console.log('System debug info copied to clipboard');
  };

  const testApiConnections = async () => {
    const endpoints = [
      'http://localhost/aureus-angel-alliance/api/packages/index.php',
      'http://localhost/aureus-angel-alliance/api/admin/wallets.php',
      'http://localhost/aureus-angel-alliance/api/users/auth.php'
    ];
    
    console.log('Testing API connections...');
    
    for (const endpoint of endpoints) {
      try {
        const response = await fetch(endpoint, {
          method: 'OPTIONS'
        });
        console.log(`✅ ${endpoint}: ${response.status} ${response.statusText}`);
      } catch (error) {
        console.error(`❌ ${endpoint}:`, error);
      }
    }
  };

  if (!isVisible) {
    return (
      <div className="fixed bottom-4 left-4 z-50">
        <Button
          onClick={() => setIsVisible(true)}
          variant="outline"
          className="bg-gray-800 hover:bg-gray-700 text-white border-gray-600"
        >
          <Settings className="h-4 w-4 mr-2" />
          Debug Panel
          {isDebugMode && (
            <Badge className="ml-2 bg-green-500 text-white">ON</Badge>
          )}
        </Button>
      </div>
    );
  }

  const systemInfo = getSystemInfo();
  const performanceInfo = getPerformanceInfo();

  return (
    <div className="fixed bottom-4 left-4 z-50 w-80 max-w-[90vw]">
      <Card className="bg-gray-900 border-gray-700 text-white shadow-2xl max-h-96 overflow-y-auto">
        <CardHeader className="pb-2">
          <div className="flex items-center justify-between">
            <CardTitle className="text-sm flex items-center">
              <Settings className="h-4 w-4 mr-2" />
              Debug Panel
            </CardTitle>
            <Button
              size="sm"
              variant="ghost"
              onClick={() => setIsVisible(false)}
              className="h-6 w-6 p-0 text-gray-400 hover:text-white"
            >
              <X className="h-3 w-3" />
            </Button>
          </div>
        </CardHeader>
        
        <CardContent className="space-y-4">
          {/* Debug Mode Toggle */}
          <div className="flex items-center justify-between">
            <span className="text-sm">Debug Mode</span>
            <Button
              size="sm"
              onClick={toggleDebugMode}
              className={isDebugMode ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-600 hover:bg-gray-700'}
            >
              {isDebugMode ? 'ON' : 'OFF'}
            </Button>
          </div>

          {/* Quick Actions */}
          <div className="space-y-2">
            <h4 className="text-sm font-semibold text-gray-300">Quick Actions</h4>
            <div className="grid grid-cols-2 gap-2">
              <Button size="sm" variant="outline" onClick={copySystemInfo} className="text-xs">
                <Copy className="h-3 w-3 mr-1" />
                Copy Info
              </Button>
              <Button size="sm" variant="outline" onClick={testApiConnections} className="text-xs">
                <Wifi className="h-3 w-3 mr-1" />
                Test APIs
              </Button>
              <Button size="sm" variant="outline" onClick={clearDebugHistory} className="text-xs">
                <Trash2 className="h-3 w-3 mr-1" />
                Clear History
              </Button>
              <Button 
                size="sm" 
                variant="outline" 
                onClick={() => window.location.reload()} 
                className="text-xs"
              >
                <Activity className="h-3 w-3 mr-1" />
                Reload
              </Button>
            </div>
          </div>

          {/* System Info */}
          <div className="space-y-2">
            <h4 className="text-sm font-semibold text-gray-300 flex items-center">
              <Monitor className="h-3 w-3 mr-1" />
              System Info
            </h4>
            <div className="text-xs space-y-1 text-gray-400">
              <div>Viewport: {systemInfo.viewport}</div>
              <div>Online: {systemInfo.onLine ? '✅' : '❌'}</div>
              <div>LocalStorage: {systemInfo.localStorage} items</div>
              <div>Language: {systemInfo.language}</div>
            </div>
          </div>

          {/* Performance Info */}
          {performanceInfo && (
            <div className="space-y-2">
              <h4 className="text-sm font-semibold text-gray-300 flex items-center">
                <Activity className="h-3 w-3 mr-1" />
                Performance
              </h4>
              <div className="text-xs space-y-1 text-gray-400">
                {performanceInfo.domContentLoaded && (
                  <div>DOM Ready: {Math.round(performanceInfo.domContentLoaded)}ms</div>
                )}
                {performanceInfo.firstContentfulPaint && (
                  <div>First Paint: {Math.round(performanceInfo.firstContentfulPaint)}ms</div>
                )}
                {performanceInfo.memoryUsage && (
                  <div>Memory: {performanceInfo.memoryUsage.used}MB / {performanceInfo.memoryUsage.total}MB</div>
                )}
              </div>
            </div>
          )}

          {/* Recent Debug History */}
          {isDebugMode && debugHistory.length > 0 && (
            <div className="space-y-2">
              <h4 className="text-sm font-semibold text-gray-300">Recent Activity</h4>
              <div className="space-y-1 max-h-32 overflow-y-auto">
                {debugHistory.slice(-5).map((entry, index) => (
                  <div key={index} className="text-xs p-2 bg-gray-800 rounded">
                    <div className="flex items-center gap-2">
                      <Badge variant="outline" className="text-xs">
                        {entry.component}
                      </Badge>
                      <span className="text-gray-300">{entry.action}</span>
                    </div>
                    <div className="text-gray-500 mt-1">
                      {entry.timestamp.toLocaleTimeString()}
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* Environment Info */}
          <div className="space-y-2">
            <h4 className="text-sm font-semibold text-gray-300">Environment</h4>
            <div className="text-xs space-y-1 text-gray-400">
              <div>Mode: {process.env.NODE_ENV}</div>
              <div>React: {React.version}</div>
              <div>URL: {window.location.pathname}</div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
};

export default DebugPanel;

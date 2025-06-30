import React, { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { 
  Settings, 
  X, 
  Copy, 
  Wifi,
  Monitor,
  Activity,
  Trash2
} from 'lucide-react';

const SimpleDebugPanel: React.FC = () => {
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
      url: window.location.href,
      timestamp: new Date().toISOString()
    };
  };

  const copySystemInfo = () => {
    const systemInfo = getSystemInfo();
    const debugInfo = {
      system: systemInfo,
      timestamp: new Date().toISOString(),
      environment: process.env.NODE_ENV,
      react: React.version
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
    
    console.log('🔍 Testing API connections...');
    
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

  const clearLocalStorage = () => {
    try {
      localStorage.clear();
      console.log('✅ Local storage cleared');
    } catch (error) {
      console.error('❌ Failed to clear local storage:', error);
    }
  };

  const clearSessionStorage = () => {
    try {
      sessionStorage.clear();
      console.log('✅ Session storage cleared');
    } catch (error) {
      console.error('❌ Failed to clear session storage:', error);
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
        </Button>
      </div>
    );
  }

  const systemInfo = getSystemInfo();

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
              <Button size="sm" variant="outline" onClick={clearLocalStorage} className="text-xs">
                <Trash2 className="h-3 w-3 mr-1" />
                Clear Local
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
              <div className="flex justify-between">
                <span>Viewport:</span>
                <span>{systemInfo.viewport}</span>
              </div>
              <div className="flex justify-between">
                <span>Online:</span>
                <span>{systemInfo.onLine ? '✅' : '❌'}</span>
              </div>
              <div className="flex justify-between">
                <span>LocalStorage:</span>
                <span>{systemInfo.localStorage} items</span>
              </div>
              <div className="flex justify-between">
                <span>Language:</span>
                <span>{systemInfo.language}</span>
              </div>
              <div className="flex justify-between">
                <span>Platform:</span>
                <span>{systemInfo.platform}</span>
              </div>
            </div>
          </div>

          {/* Environment Info */}
          <div className="space-y-2">
            <h4 className="text-sm font-semibold text-gray-300">Environment</h4>
            <div className="text-xs space-y-1 text-gray-400">
              <div className="flex justify-between">
                <span>Mode:</span>
                <span>{process.env.NODE_ENV}</span>
              </div>
              <div className="flex justify-between">
                <span>React:</span>
                <span>{React.version}</span>
              </div>
              <div className="flex justify-between">
                <span>Path:</span>
                <span>{window.location.pathname}</span>
              </div>
            </div>
          </div>

          {/* Current URL */}
          <div className="space-y-2">
            <h4 className="text-sm font-semibold text-gray-300">Current URL</h4>
            <div className="text-xs text-gray-400 break-all bg-gray-800 p-2 rounded">
              {systemInfo.url}
            </div>
          </div>

          {/* User Agent */}
          <div className="space-y-2">
            <h4 className="text-sm font-semibold text-gray-300">User Agent</h4>
            <div className="text-xs text-gray-400 break-all bg-gray-800 p-2 rounded max-h-20 overflow-y-auto">
              {systemInfo.userAgent}
            </div>
          </div>

          {/* Debug Commands */}
          <div className="space-y-2">
            <h4 className="text-sm font-semibold text-gray-300">Debug Commands</h4>
            <div className="text-xs space-y-1 text-gray-400">
              <div>• Open console and run: <code className="bg-gray-800 px-1 rounded">localStorage.setItem('debug', 'true')</code></div>
              <div>• Add to URL: <code className="bg-gray-800 px-1 rounded">?debug=true</code></div>
              <div>• Check network tab for API calls</div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
};

export default SimpleDebugPanel;

import React, { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { useToast } from '@/hooks/use-toast';
import {
  Bug,
  X,
  Monitor,
  Network,
  Database,
  Activity,
  Settings,
  RefreshCw,
  Download,
  Trash2,
  Eye,
  AlertTriangle,
  Clock,
  Wifi,
  HardDrive
} from 'lucide-react';

interface DebugFeature {
  feature_key: string;
  feature_name: string;
  feature_description: string;
  config_data: any;
  access_level: string;
}

interface DebugPanelProps {
  isOpen: boolean;
  onClose: () => void;
}

const EnhancedDebugPanel: React.FC<DebugPanelProps> = ({ isOpen, onClose }) => {
  const [features, setFeatures] = useState<DebugFeature[]>([]);
  const [activeFeature, setActiveFeature] = useState<string | null>(null);
  const [logs, setLogs] = useState<any[]>([]);
  const [networkRequests, setNetworkRequests] = useState<any[]>([]);
  const [systemInfo, setSystemInfo] = useState<any>(null);
  const [loading, setLoading] = useState(false);
  const { toast } = useToast();

  useEffect(() => {
    if (isOpen) {
      fetchActiveFeatures();
      initializeDebugFeatures();
    }
  }, [isOpen]);

  const fetchActiveFeatures = async () => {
    try {
      console.log('🔍 EnhancedDebugPanel: Fetching active features...');

      const response = await fetch('http://localhost/aureus-angel-alliance/api/admin/debug-config.php?action=active', {
        credentials: 'include'
      });

      console.log('🔍 EnhancedDebugPanel: Response status:', response.status);

      const data = await response.json();
      console.log('🔍 EnhancedDebugPanel: API response:', data);

      if (data.success) {
        console.log('🔍 EnhancedDebugPanel: Setting features:', data.data.features);
        setFeatures(data.data.features);

        if (data.data.features.length > 0) {
          setActiveFeature(data.data.features[0].feature_key);
          console.log('🔍 EnhancedDebugPanel: Active feature set to:', data.data.features[0].feature_key);
        } else {
          console.log('🔍 EnhancedDebugPanel: No active features found');
          setActiveFeature(null);
        }
      } else {
        console.error('🔍 EnhancedDebugPanel: API error:', data.error);
        setFeatures([]);
        setActiveFeature(null);
      }
    } catch (error) {
      console.error('🔍 EnhancedDebugPanel: Network error:', error);
      setFeatures([]);
      setActiveFeature(null);
    }
  };

  const initializeDebugFeatures = () => {
    // Initialize console log capture
    if (typeof window !== 'undefined') {
      const originalConsole = { ...console };
      
      ['log', 'warn', 'error', 'info'].forEach(method => {
        (console as any)[method] = (...args: any[]) => {
          originalConsole[method](...args);
          
          setLogs(prev => [...prev.slice(-99), {
            id: Date.now() + Math.random(),
            type: method,
            message: args.map(arg => 
              typeof arg === 'object' ? JSON.stringify(arg, null, 2) : String(arg)
            ).join(' '),
            timestamp: new Date().toISOString(),
            stack: method === 'error' ? new Error().stack : null
          }]);
        };
      });

      // Initialize network monitoring
      const originalFetch = window.fetch;
      window.fetch = async (...args) => {
        const startTime = Date.now();
        let url = 'Unknown URL';

        if (args[0]) {
          if (typeof args[0] === 'string') {
            url = args[0];
          } else if (args[0] instanceof URL) {
            url = args[0].href;
          } else if (args[0] instanceof Request) {
            url = args[0].url;
          } else if (args[0] && typeof args[0] === 'object' && 'url' in args[0]) {
            url = String(args[0].url);
          } else {
            url = String(args[0]);
          }
        }
        
        try {
          const response = await originalFetch(...args);
          const endTime = Date.now();
          
          setNetworkRequests(prev => [...prev.slice(-49), {
            id: Date.now() + Math.random(),
            url,
            method: args[1]?.method || 'GET',
            status: response.status,
            statusText: response.statusText,
            duration: endTime - startTime,
            timestamp: new Date().toISOString(),
            type: 'fetch'
          }]);
          
          return response;
        } catch (error) {
          const endTime = Date.now();
          
          setNetworkRequests(prev => [...prev.slice(-49), {
            id: Date.now() + Math.random(),
            url,
            method: args[1]?.method || 'GET',
            status: 0,
            statusText: 'Network Error',
            duration: endTime - startTime,
            timestamp: new Date().toISOString(),
            type: 'fetch',
            error: error instanceof Error ? error.message : 'Unknown error'
          }]);
          
          throw error;
        }
      };

      // Collect system information
      setSystemInfo({
        userAgent: navigator.userAgent,
        platform: navigator.platform,
        language: navigator.language,
        cookieEnabled: navigator.cookieEnabled,
        onLine: navigator.onLine,
        viewport: {
          width: window.innerWidth,
          height: window.innerHeight
        },
        screen: {
          width: screen.width,
          height: screen.height,
          colorDepth: screen.colorDepth
        },
        memory: (performance as any).memory ? {
          usedJSHeapSize: (performance as any).memory.usedJSHeapSize,
          totalJSHeapSize: (performance as any).memory.totalJSHeapSize,
          jsHeapSizeLimit: (performance as any).memory.jsHeapSizeLimit
        } : null,
        connection: (navigator as any).connection ? {
          effectiveType: (navigator as any).connection.effectiveType,
          downlink: (navigator as any).connection.downlink,
          rtt: (navigator as any).connection.rtt
        } : null,
        timestamp: new Date().toISOString()
      });
    }
  };

  const logDebugAction = async (featureKey: string, actionType: string, actionData?: any) => {
    try {
      await fetch('http://localhost/aureus-angel-alliance/api/admin/debug-config.php?action=log_session', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({
          feature_key: featureKey,
          action_type: actionType,
          action_data: actionData
        })
      });
    } catch (error) {
      console.error('Error logging debug action:', error);
    }
  };

  const clearLogs = () => {
    setLogs([]);
    logDebugAction('console_logs', 'clear');
    toast({
      title: "Logs Cleared",
      description: "Console logs have been cleared",
    });
  };

  const clearNetworkRequests = () => {
    setNetworkRequests([]);
    logDebugAction('network_monitor', 'clear');
    toast({
      title: "Network Requests Cleared",
      description: "Network request history has been cleared",
    });
  };

  const downloadLogs = () => {
    const data = {
      logs,
      networkRequests,
      systemInfo,
      timestamp: new Date().toISOString()
    };
    
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `debug-logs-${new Date().toISOString().split('T')[0]}.json`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    
    logDebugAction('console_logs', 'download', { logCount: logs.length, networkCount: networkRequests.length });
    
    toast({
      title: "Debug Data Downloaded",
      description: "Debug logs have been downloaded as JSON file",
    });
  };

  const getFeatureIcon = (featureKey: string) => {
    switch (featureKey) {
      case 'console_logs': return <Monitor className="w-4 h-4" />;
      case 'network_monitor': return <Network className="w-4 h-4" />;
      case 'system_info': return <Settings className="w-4 h-4" />;
      case 'database_queries': return <Database className="w-4 h-4" />;
      case 'api_testing': return <Activity className="w-4 h-4" />;
      case 'error_tracking': return <AlertTriangle className="w-4 h-4" />;
      default: return <Bug className="w-4 h-4" />;
    }
  };

  const getLogTypeColor = (type: string) => {
    switch (type) {
      case 'error': return 'text-red-400';
      case 'warn': return 'text-yellow-400';
      case 'info': return 'text-blue-400';
      default: return 'text-gray-300';
    }
  };

  const getStatusColor = (status: number) => {
    if (status >= 200 && status < 300) return 'text-green-400';
    if (status >= 300 && status < 400) return 'text-yellow-400';
    if (status >= 400) return 'text-red-400';
    return 'text-gray-400';
  };

  const renderFeatureContent = () => {
    const feature = features.find(f => f.feature_key === activeFeature);
    if (!feature) return null;

    switch (activeFeature) {
      case 'console_logs':
        return (
          <div className="space-y-4">
            <div className="flex items-center justify-between">
              <h3 className="text-lg font-semibold text-white">Console Logs</h3>
              <div className="flex gap-2">
                <Button
                  onClick={clearLogs}
                  size="sm"
                  variant="outline"
                  className="text-red-400 border-red-500/30 hover:bg-red-500/20"
                >
                  <Trash2 className="w-4 h-4 mr-1" />
                  Clear
                </Button>
                <Button
                  onClick={downloadLogs}
                  size="sm"
                  className="bg-gold-gradient text-black"
                >
                  <Download className="w-4 h-4 mr-1" />
                  Download
                </Button>
              </div>
            </div>
            
            <div className="bg-black rounded border border-gray-600 h-64 overflow-y-auto p-2 font-mono text-sm">
              {logs.length === 0 ? (
                <div className="text-gray-500 text-center py-8">No console logs captured yet</div>
              ) : (
                logs.map((log) => (
                  <div key={log.id} className="mb-1 flex gap-2">
                    <span className="text-gray-500 text-xs">
                      {new Date(log.timestamp).toLocaleTimeString()}
                    </span>
                    <span className={`text-xs font-semibold ${getLogTypeColor(log.type)}`}>
                      [{log.type.toUpperCase()}]
                    </span>
                    <span className="text-gray-300 flex-1">{log.message}</span>
                  </div>
                ))
              )}
            </div>
          </div>
        );

      case 'network_monitor':
        return (
          <div className="space-y-4">
            <div className="flex items-center justify-between">
              <h3 className="text-lg font-semibold text-white">Network Requests</h3>
              <Button
                onClick={clearNetworkRequests}
                size="sm"
                variant="outline"
                className="text-red-400 border-red-500/30 hover:bg-red-500/20"
              >
                <Trash2 className="w-4 h-4 mr-1" />
                Clear
              </Button>
            </div>
            
            <div className="bg-gray-800 rounded border border-gray-600 h-64 overflow-y-auto">
              {networkRequests.length === 0 ? (
                <div className="text-gray-500 text-center py-8">No network requests captured yet</div>
              ) : (
                <table className="w-full text-sm">
                  <thead className="bg-gray-700 sticky top-0">
                    <tr>
                      <th className="px-2 py-1 text-left text-xs">Method</th>
                      <th className="px-2 py-1 text-left text-xs">URL</th>
                      <th className="px-2 py-1 text-left text-xs">Status</th>
                      <th className="px-2 py-1 text-left text-xs">Time</th>
                    </tr>
                  </thead>
                  <tbody>
                    {networkRequests.map((req) => (
                      <tr key={req.id} className="border-b border-gray-700">
                        <td className="px-2 py-1 text-blue-400 font-mono">{req.method}</td>
                        <td className="px-2 py-1 text-gray-300 truncate max-w-xs" title={req.url}>
                          {req.url}
                        </td>
                        <td className={`px-2 py-1 font-mono ${getStatusColor(req.status)}`}>
                          {req.status}
                        </td>
                        <td className="px-2 py-1 text-gray-400">{req.duration}ms</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              )}
            </div>
          </div>
        );

      case 'error_tracking':
        return (
          <div className="space-y-4">
            <div className="flex items-center justify-between">
              <h3 className="text-lg font-semibold text-white">Error Tracking</h3>
              <Button
                onClick={() => {
                  setLogs(prev => prev.filter(log => log.type !== 'error'));
                  logDebugAction('error_tracking', 'clear_errors');
                }}
                size="sm"
                variant="outline"
                className="text-red-400 border-red-500/30 hover:bg-red-500/20"
              >
                <Trash2 className="w-4 h-4 mr-1" />
                Clear Errors
              </Button>
            </div>

            <div className="bg-black rounded border border-gray-600 h-64 overflow-y-auto p-2 font-mono text-sm">
              {logs.filter(log => log.type === 'error').length === 0 ? (
                <div className="text-gray-500 text-center py-8">
                  <AlertTriangle className="w-8 h-8 mx-auto mb-2 text-green-400" />
                  <p>No errors detected</p>
                  <p className="text-xs mt-1">Your application is running smoothly!</p>
                </div>
              ) : (
                logs.filter(log => log.type === 'error').map((log) => (
                  <div key={log.id} className="mb-3 p-2 bg-red-900/20 border border-red-500/30 rounded">
                    <div className="flex gap-2 items-start">
                      <AlertTriangle className="w-4 h-4 text-red-400 mt-0.5 flex-shrink-0" />
                      <div className="flex-1">
                        <div className="flex gap-2 text-xs text-gray-400 mb-1">
                          <span>{new Date(log.timestamp).toLocaleTimeString()}</span>
                          <span className="text-red-400 font-semibold">[ERROR]</span>
                        </div>
                        <div className="text-red-300 mb-2">{log.message}</div>
                        {log.stack && (
                          <details className="text-xs text-gray-400">
                            <summary className="cursor-pointer hover:text-white">Stack Trace</summary>
                            <pre className="mt-1 text-xs overflow-x-auto">{log.stack}</pre>
                          </details>
                        )}
                      </div>
                    </div>
                  </div>
                ))
              )}
            </div>
          </div>
        );

      case 'system_info':
        return (
          <div className="space-y-4">
            <h3 className="text-lg font-semibold text-white">System Information</h3>
            
            {systemInfo && (
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="bg-gray-800 rounded p-3 border border-gray-600">
                  <h4 className="text-gold font-semibold mb-2 flex items-center gap-2">
                    <Monitor className="w-4 h-4" />
                    Browser
                  </h4>
                  <div className="space-y-1 text-sm">
                    <div><span className="text-gray-400">Platform:</span> <span className="text-white">{systemInfo.platform}</span></div>
                    <div><span className="text-gray-400">Language:</span> <span className="text-white">{systemInfo.language}</span></div>
                    <div><span className="text-gray-400">Online:</span> <span className={systemInfo.onLine ? 'text-green-400' : 'text-red-400'}>{systemInfo.onLine ? 'Yes' : 'No'}</span></div>
                  </div>
                </div>

                <div className="bg-gray-800 rounded p-3 border border-gray-600">
                  <h4 className="text-gold font-semibold mb-2 flex items-center gap-2">
                    <Eye className="w-4 h-4" />
                    Viewport
                  </h4>
                  <div className="space-y-1 text-sm">
                    <div><span className="text-gray-400">Size:</span> <span className="text-white">{systemInfo.viewport.width} × {systemInfo.viewport.height}</span></div>
                    <div><span className="text-gray-400">Screen:</span> <span className="text-white">{systemInfo.screen.width} × {systemInfo.screen.height}</span></div>
                    <div><span className="text-gray-400">Color Depth:</span> <span className="text-white">{systemInfo.screen.colorDepth}-bit</span></div>
                  </div>
                </div>

                {systemInfo.memory && (
                  <div className="bg-gray-800 rounded p-3 border border-gray-600">
                    <h4 className="text-gold font-semibold mb-2 flex items-center gap-2">
                      <HardDrive className="w-4 h-4" />
                      Memory
                    </h4>
                    <div className="space-y-1 text-sm">
                      <div><span className="text-gray-400">Used:</span> <span className="text-white">{(systemInfo.memory.usedJSHeapSize / 1024 / 1024).toFixed(1)} MB</span></div>
                      <div><span className="text-gray-400">Total:</span> <span className="text-white">{(systemInfo.memory.totalJSHeapSize / 1024 / 1024).toFixed(1)} MB</span></div>
                      <div><span className="text-gray-400">Limit:</span> <span className="text-white">{(systemInfo.memory.jsHeapSizeLimit / 1024 / 1024).toFixed(1)} MB</span></div>
                    </div>
                  </div>
                )}

                {systemInfo.connection && (
                  <div className="bg-gray-800 rounded p-3 border border-gray-600">
                    <h4 className="text-gold font-semibold mb-2 flex items-center gap-2">
                      <Wifi className="w-4 h-4" />
                      Connection
                    </h4>
                    <div className="space-y-1 text-sm">
                      <div><span className="text-gray-400">Type:</span> <span className="text-white">{systemInfo.connection.effectiveType}</span></div>
                      <div><span className="text-gray-400">Downlink:</span> <span className="text-white">{systemInfo.connection.downlink} Mbps</span></div>
                      <div><span className="text-gray-400">RTT:</span> <span className="text-white">{systemInfo.connection.rtt} ms</span></div>
                    </div>
                  </div>
                )}
              </div>
            )}
          </div>
        );

      default:
        return (
          <div className="text-center py-8">
            <Bug className="w-12 h-12 text-gray-500 mx-auto mb-4" />
            <p className="text-gray-400">Debug feature not implemented yet</p>
          </div>
        );
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div className="bg-gray-900 rounded-lg border border-gray-700 w-full max-w-6xl h-[80vh] flex flex-col">
        {/* Header */}
        <div className="flex items-center justify-between p-4 border-b border-gray-700">
          <div className="flex items-center gap-2">
            <Bug className="w-5 h-5 text-gold" />
            <h2 className="text-xl font-bold text-white">Debug Panel</h2>
            <span className="px-2 py-1 bg-green-500/20 text-green-400 text-xs rounded">
              {features.length} features active
            </span>
          </div>
          
          <Button
            onClick={onClose}
            variant="ghost"
            size="sm"
            className="text-gray-400 hover:text-white"
          >
            <X className="w-4 h-4" />
          </Button>
        </div>

        <div className="flex flex-1 overflow-hidden">
          {/* Sidebar */}
          <div className="w-64 bg-gray-800 border-r border-gray-700 p-4">
            <h3 className="text-sm font-semibold text-gray-400 uppercase mb-3">Debug Features</h3>
            <div className="space-y-1">
              {features.length === 0 ? (
                <div className="text-center py-8">
                  <Bug className="w-8 h-8 text-gray-500 mx-auto mb-2" />
                  <p className="text-gray-400 text-sm">No debug features enabled</p>
                  <p className="text-gray-500 text-xs mt-1">Enable features in Debug Manager</p>
                </div>
              ) : (
                features.map((feature) => (
                  <button
                    key={feature.feature_key}
                    onClick={() => {
                      setActiveFeature(feature.feature_key);
                      logDebugAction(feature.feature_key, 'view');
                    }}
                    className={`w-full flex items-center gap-2 px-3 py-2 rounded text-sm transition-colors ${
                      activeFeature === feature.feature_key
                        ? 'bg-gold text-black font-semibold'
                        : 'text-gray-300 hover:bg-gray-700 hover:text-white'
                    }`}
                  >
                    {getFeatureIcon(feature.feature_key)}
                    {feature.feature_name}
                  </button>
                ))
              )}
            </div>
          </div>

          {/* Content */}
          <div className="flex-1 p-4 overflow-y-auto">
            {features.length === 0 ? (
              <div className="text-center py-16">
                <Bug className="w-16 h-16 text-gray-500 mx-auto mb-4" />
                <h3 className="text-xl font-semibold text-white mb-2">No Debug Features Available</h3>
                <p className="text-gray-400 mb-4">
                  No debug features are currently enabled. Enable features in the Debug Manager to start debugging.
                </p>
                <Button
                  onClick={onClose}
                  className="bg-gold-gradient text-black font-semibold"
                >
                  Close Debug Panel
                </Button>
              </div>
            ) : (
              renderFeatureContent()
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

export default EnhancedDebugPanel;

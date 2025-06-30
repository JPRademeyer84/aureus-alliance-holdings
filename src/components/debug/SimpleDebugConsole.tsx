import React, { useState, useEffect, useRef } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Bug,
  X,
  Copy,
  Trash2,
  ChevronDown,
  ChevronUp,
  AlertTriangle,
  AlertCircle,
  Info,
  XCircle,
  CheckCircle,
  Download,
  Globe,
  Eye,
  EyeOff
} from 'lucide-react';

interface DebugEntry {
  id: string;
  type: 'error' | 'warn' | 'log' | 'info' | 'network';
  message: string;
  stack?: string;
  timestamp: Date;
  url?: string;
  line?: number;
  column?: number;
  source?: string;
  details?: any;
  requestData?: any;
  responseData?: any;
  statusCode?: number;
  method?: string;
  duration?: number;
}

const SimpleDebugConsole: React.FC = () => {
  const [entries, setEntries] = useState<DebugEntry[]>([
    {
      id: 'init-1',
      type: 'info',
      message: '🔍 Debug Console Initialized',
      timestamp: new Date(),
      source: 'debug-console',
      details: { status: 'ready' }
    }
  ]);
  const [isVisible, setIsVisible] = useState(false);
  const [isMinimized, setIsMinimized] = useState(true);
  const [activeFilters, setActiveFilters] = useState<Set<'error' | 'warn' | 'log' | 'info' | 'network'>>(
    new Set(['error', 'warn', 'log', 'info', 'network'])
  );
  const [autoScroll, setAutoScroll] = useState(true);
  const [debugConfig, setDebugConfig] = useState<{
    console_logs: boolean;
    network_monitor: boolean;
    error_tracking: boolean;
    enabled: boolean;
  }>({
    console_logs: true,
    network_monitor: true,
    error_tracking: true,
    enabled: true
  });
  const consoleRef = useRef<HTMLDivElement>(null);

  // Fetch debug configuration from admin settings
  useEffect(() => {
    const fetchDebugConfig = async () => {
      try {
        const response = await fetch('http://localhost/aureus-angel-alliance/api/admin/debug-config.php?action=active', {
          credentials: 'include'
        });

        // Handle non-200 responses gracefully - this is expected when not logged in as admin
        if (!response.ok) {
          // Silently use default config without logging to avoid console spam
          setDebugConfig({
            console_logs: true,
            network_monitor: true,
            error_tracking: true,
            enabled: true
          });
          return;
        }

        const data = await response.json();

        if (data.success) {
          const features = data.data.features;
          const config = {
            console_logs: features.some((f: any) => f.feature_key === 'console_logs'),
            network_monitor: features.some((f: any) => f.feature_key === 'network_monitor'),
            error_tracking: features.some((f: any) => f.feature_key === 'error_tracking'),
            enabled: features.length > 0
          };

          console.log('🔍 SimpleDebugConsole: Debug config loaded:', config);
          setDebugConfig(config);
        } else {
          // Silently use default config
          setDebugConfig({
            console_logs: true,
            network_monitor: true,
            error_tracking: true,
            enabled: true
          });
        }
      } catch (error) {
        // Silently use default config without logging to avoid console spam
        setDebugConfig({
          console_logs: true,
          network_monitor: true,
          error_tracking: true,
          enabled: true
        });
      }
    };

    fetchDebugConfig();

    // Add some initial test entries to show the console is working
    setTimeout(() => {
      console.log('🎯 Debug Console Test: Console logging is working!');
      console.info('ℹ️ Debug Console Test: Info messages are captured');
      console.warn('⚠️ Debug Console Test: Warning messages are captured');
    }, 1000);

    // Refresh config every 5 minutes to pick up admin changes (reduced frequency)
    const interval = setInterval(fetchDebugConfig, 300000);
    return () => clearInterval(interval);
  }, []);

  useEffect(() => {
    // Don't initialize if debug is not enabled
    if (!debugConfig.enabled) {
      console.log('🔍 SimpleDebugConsole: Debug disabled by admin');
      return;
    }

    console.log('🔍 SimpleDebugConsole: Initializing with config:', debugConfig);

    // Store original console methods
    const originalConsole = {
      log: console.log,
      warn: console.warn,
      error: console.error,
      info: console.info
    };

    // Safe JSON stringify that handles circular references
    const safeStringify = (obj: any, maxDepth = 3): string => {
      const seen = new WeakSet();

      const replacer = (key: string, value: any, depth = 0): any => {
        if (depth > maxDepth) {
          return '[Max Depth Reached]';
        }

        if (value === null) return null;

        if (typeof value === 'object') {
          if (seen.has(value)) {
            return '[Circular Reference]';
          }
          seen.add(value);

          // Handle specific problematic objects
          if (value.constructor && value.constructor.name === 'sp') {
            return '[SafePal Provider Object]';
          }
          if (value._pushEventHandlers) {
            return '[Wallet Provider with Event Handlers]';
          }
          if (value.provider && typeof value.provider === 'object') {
            return { ...value, provider: '[Provider Object]' };
          }
        }

        return value;
      };

      try {
        return JSON.stringify(obj, replacer, 2);
      } catch (e) {
        return `[Stringify Error: ${e.message}]`;
      }
    };

    // Override console methods
    const addEntry = (type: DebugEntry['type'], args: any[], source?: string, extraData?: any) => {
      const message = args.map(arg => {
        if (typeof arg === 'object') {
          return safeStringify(arg);
        }
        return String(arg);
      }).join(' ');

      const entry: DebugEntry = {
        id: Date.now() + Math.random().toString(),
        type,
        message,
        timestamp: new Date(),
        source: source || 'console',
        details: args.length === 1 && typeof args[0] === 'object' ? args[0] : args,
        ...extraData
      };

      // Use setTimeout to avoid state updates during render
      setTimeout(() => {
        setEntries(prev => [...prev.slice(-49), entry]); // Keep last 50 entries
      }, 0);
    };

    // Only override console methods if console_logs is enabled
    if (debugConfig.console_logs) {
      console.log = (...args) => {
        originalConsole.log(...args);
        addEntry('log', args, 'console.log');
      };

      console.warn = (...args) => {
        originalConsole.warn(...args);
        addEntry('warn', args, 'console.warn');
      };

      console.info = (...args) => {
        originalConsole.info(...args);
        addEntry('info', args, 'console.info');
      };
    }

    // Always capture errors if error_tracking is enabled
    if (debugConfig.error_tracking) {
      console.error = (...args) => {
        originalConsole.error(...args);
        addEntry('error', args, 'console.error');
      };
    }

    // Capture unhandled errors (only if error_tracking is enabled)
    const handleError = (event: ErrorEvent) => {
      if (!debugConfig.error_tracking) return;

      const entry: DebugEntry = {
        id: Date.now() + Math.random().toString(),
        type: 'error',
        message: event.message,
        stack: event.error?.stack,
        timestamp: new Date(),
        url: event.filename,
        line: event.lineno,
        column: event.colno,
        source: 'window.error',
        details: {
          error: event.error,
          filename: event.filename,
          lineno: event.lineno,
          colno: event.colno
        }
      };
      setTimeout(() => {
        setEntries(prev => [...prev.slice(-49), entry]);
      }, 0);
    };

    // Capture unhandled promise rejections (only if error_tracking is enabled)
    const handleUnhandledRejection = (event: PromiseRejectionEvent) => {
      if (!debugConfig.error_tracking) return;

      const entry: DebugEntry = {
        id: Date.now() + Math.random().toString(),
        type: 'error',
        message: `Unhandled Promise Rejection: ${event.reason}`,
        stack: event.reason?.stack,
        timestamp: new Date(),
        source: 'unhandledrejection',
        details: {
          reason: event.reason,
          promise: event.promise
        }
      };
      setTimeout(() => {
        setEntries(prev => [...prev.slice(-49), entry]);
      }, 0);
    };

    // Capture CSP violations (always enabled for security)
    const handleCSPViolation = (event: SecurityPolicyViolationEvent) => {
      const entry: DebugEntry = {
        id: Date.now() + Math.random().toString(),
        type: 'error',
        message: `CSP Violation: ${event.violatedDirective} - ${event.blockedURI}`,
        timestamp: new Date(),
        source: 'csp-violation',
        details: {
          blockedURI: event.blockedURI,
          violatedDirective: event.violatedDirective,
          originalPolicy: event.originalPolicy,
          disposition: event.disposition,
          effectiveDirective: event.effectiveDirective,
          sourceFile: event.sourceFile,
          lineNumber: event.lineNumber,
          columnNumber: event.columnNumber
        }
      };
      setTimeout(() => {
        setEntries(prev => [...prev.slice(-49), entry]);
      }, 0);
    };

    // Enhanced fetch monitoring (only if network_monitor is enabled)
    let originalFetch: typeof window.fetch | null = null;

    if (debugConfig.network_monitor) {
      originalFetch = window.fetch;
      window.fetch = async (...args) => {
        const startTime = performance.now();
        const url = args[0];
        const options = args[1] || {};
        const method = options.method || 'GET';

        // Log request start
        addEntry('network', [`🌐 ${method} ${url} - Starting...`], 'fetch.request', {
          url,
          method,
          headers: options.headers,
          body: options.body,
          requestTime: new Date().toISOString()
        });

      try {
        const response = await originalFetch(...args);
        const endTime = performance.now();
        const duration = Math.round(endTime - startTime);

        // Try to get response data
        let responseData;
        let responseText;
        try {
          const clonedResponse = response.clone();
          responseText = await clonedResponse.text();
          try {
            responseData = JSON.parse(responseText);
          } catch {
            responseData = responseText;
          }
        } catch (e) {
          responseData = 'Could not read response';
        }

        const logType = response.ok ? 'log' : 'error';
        const statusEmoji = response.ok ? '✅' : '❌';

        addEntry(logType, [
          `${statusEmoji} ${method} ${url} - ${response.status} ${response.statusText} (${duration}ms)`
        ], 'fetch.response', {
          url,
          method,
          status: response.status,
          statusText: response.statusText,
          duration,
          headers: Object.fromEntries(response.headers.entries()),
          requestData: options.body,
          responseData,
          responseText,
          ok: response.ok,
          type: response.type,
          redirected: response.redirected
        });

        return response;
      } catch (error) {
        const endTime = performance.now();
        const duration = Math.round(endTime - startTime);

        addEntry('error', [
          `💥 ${method} ${url} - Network Error (${duration}ms): ${error}`
        ], 'fetch.error', {
          url,
          method,
          duration,
          error: error instanceof Error ? {
            name: error.name,
            message: error.message,
            stack: error.stack
          } : error,
          requestData: options.body
        });

        throw error;
      }
      };
    }

    // Add event listeners based on configuration
    if (debugConfig.error_tracking) {
      window.addEventListener('error', handleError);
      window.addEventListener('unhandledrejection', handleUnhandledRejection);
    }

    // Always listen for CSP violations (security-related)
    window.addEventListener('securitypolicyviolation', handleCSPViolation);

    return () => {
      // Restore original console methods
      console.log = originalConsole.log;
      console.warn = originalConsole.warn;
      console.error = originalConsole.error;
      console.info = originalConsole.info;

      // Restore original fetch if it was overridden
      if (originalFetch) {
        window.fetch = originalFetch;
      }

      // Remove event listeners
      window.removeEventListener('error', handleError);
      window.removeEventListener('unhandledrejection', handleUnhandledRejection);
      window.removeEventListener('securitypolicyviolation', handleCSPViolation);
    };
  }, [debugConfig]); // Re-run when debug config changes

  useEffect(() => {
    if (autoScroll && consoleRef.current) {
      consoleRef.current.scrollTop = consoleRef.current.scrollHeight;
    }
  }, [entries, autoScroll]);

  const getIcon = (type: DebugEntry['type']) => {
    switch (type) {
      case 'error': return <XCircle className="h-4 w-4 text-red-500" />;
      case 'warn': return <AlertTriangle className="h-4 w-4 text-yellow-500" />;
      case 'info': return <Info className="h-4 w-4 text-blue-500" />;
      case 'log': return <CheckCircle className="h-4 w-4 text-green-500" />;
      case 'network': return <Globe className="h-4 w-4 text-purple-500" />;
      default: return <Info className="h-4 w-4 text-gray-500" />;
    }
  };

  const getBadgeColor = (type: DebugEntry['type']) => {
    switch (type) {
      case 'error': return 'bg-red-500/10 border-red-500/30 text-red-400';
      case 'warn': return 'bg-yellow-500/10 border-yellow-500/30 text-yellow-400';
      case 'info': return 'bg-blue-500/10 border-blue-500/30 text-blue-400';
      case 'log': return 'bg-green-500/10 border-green-500/30 text-green-400';
      case 'network': return 'bg-purple-500/10 border-purple-500/30 text-purple-400';
      default: return 'bg-gray-500/10 border-gray-500/30 text-gray-400';
    }
  };

  const copyToClipboard = (entry: DebugEntry) => {
    const debugInfo = {
      timestamp: entry.timestamp.toISOString(),
      type: entry.type,
      source: entry.source,
      message: entry.message,
      ...(entry.stack && { stack: entry.stack }),
      ...(entry.url && {
        location: {
          url: entry.url,
          line: entry.line,
          column: entry.column
        }
      }),
      ...(entry.method && { method: entry.method }),
      ...(entry.statusCode && { statusCode: entry.statusCode }),
      ...(entry.duration && { duration: `${entry.duration}ms` }),
      ...(entry.requestData && { requestData: entry.requestData }),
      ...(entry.responseData && { responseData: entry.responseData }),
      ...(entry.details && { details: entry.details })
    };

    const text = safeStringify(debugInfo);

    navigator.clipboard.writeText(text).then(() => {
      console.log('✅ Debug entry copied to clipboard');
    }).catch(() => {
      console.error('❌ Failed to copy to clipboard');
    });
  };

  const dismissEntry = (id: string) => {
    setEntries(prev => prev.filter(entry => entry.id !== id));
  };

  const clearAll = () => {
    setEntries([]);
  };

  const toggleFilter = (type: 'error' | 'warn' | 'log' | 'info' | 'network') => {
    setActiveFilters(prev => {
      const newFilters = new Set(prev);
      if (newFilters.has(type)) {
        newFilters.delete(type);
      } else {
        newFilters.add(type);
      }
      return newFilters;
    });
  };

  const toggleAllFilters = () => {
    const allTypes: ('error' | 'warn' | 'log' | 'info' | 'network')[] = ['error', 'warn', 'log', 'info', 'network'];
    const allActive = allTypes.every(type => activeFilters.has(type));

    if (allActive) {
      setActiveFilters(new Set());
    } else {
      setActiveFilters(new Set(allTypes));
    }
  };

  const exportLogs = () => {
    const logs = entries.map(entry => ({
      timestamp: entry.timestamp.toISOString(),
      type: entry.type,
      message: entry.message,
      stack: entry.stack,
      url: entry.url,
      line: entry.line,
      column: entry.column
    }));

    const blob = new Blob([safeStringify(logs)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `debug-logs-${new Date().toISOString().split('T')[0]}.json`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  };

  const filteredEntries = entries.filter(entry =>
    activeFilters.has(entry.type)
  );

  const errorCount = entries.filter(e => e.type === 'error').length;
  const warnCount = entries.filter(e => e.type === 'warn').length;

  // Don't render if debug is disabled by admin
  if (!debugConfig.enabled) {
    return null;
  }

  if (!isVisible) {
    return (
      <div className="fixed bottom-4 right-4 z-[9999]">
        <Button
          onClick={() => setIsVisible(true)}
          className={`bg-gray-800/95 backdrop-blur-sm hover:bg-gray-700/95 text-white border shadow-lg ${
            errorCount > 0 ? 'border-red-500 animate-pulse' :
            warnCount > 0 ? 'border-yellow-500' : 'border-gray-600'
          }`}
        >
          <Bug className="h-4 w-4 mr-2" />
          Debug ({entries.length})
          {(errorCount > 0 || warnCount > 0) && (
            <Badge className="ml-2 bg-red-500 text-white">
              {errorCount + warnCount}
            </Badge>
          )}
        </Button>
      </div>
    );
  }

  return (
    <div className="fixed bottom-4 right-4 z-[9999] w-96 max-w-[90vw]">
      <Card className="bg-gray-900/95 backdrop-blur-sm border-gray-700 text-white shadow-2xl">
        <CardHeader className="pb-2">
          <div className="flex items-center justify-between">
            <CardTitle className="text-sm flex items-center">
              <Bug className="h-4 w-4 mr-2" />
              Debug Console ({filteredEntries.length})
              <div className="ml-2 flex gap-1">
                {debugConfig.error_tracking && (
                  <Badge className="text-xs bg-red-500/20 text-red-400 border-red-500/30">E</Badge>
                )}
                {debugConfig.console_logs && (
                  <Badge className="text-xs bg-green-500/20 text-green-400 border-green-500/30">C</Badge>
                )}
                {debugConfig.network_monitor && (
                  <Badge className="text-xs bg-purple-500/20 text-purple-400 border-purple-500/30">N</Badge>
                )}
              </div>
            </CardTitle>
            <div className="flex items-center gap-1">
              <Button
                size="sm"
                variant="ghost"
                onClick={() => setIsMinimized(!isMinimized)}
                className="h-6 w-6 p-0 text-gray-400 hover:text-white"
              >
                {isMinimized ? <ChevronUp className="h-3 w-3" /> : <ChevronDown className="h-3 w-3" />}
              </Button>
              <Button
                size="sm"
                variant="ghost"
                onClick={() => setIsVisible(false)}
                className="h-6 w-6 p-0 text-gray-400 hover:text-white"
              >
                <X className="h-3 w-3" />
              </Button>
            </div>
          </div>
          
          {!isMinimized && (
            <div className="space-y-2 mt-2">
              {/* Filter Toggle Buttons - Only show enabled types */}
              <div className="flex items-center gap-1 flex-wrap">
                <Button
                  size="sm"
                  variant={activeFilters.size === 5 ? "default" : "outline"}
                  onClick={toggleAllFilters}
                  className="h-6 px-2 text-xs"
                >
                  All ({entries.length})
                </Button>

                {debugConfig.error_tracking && (
                  <Button
                    size="sm"
                    variant={activeFilters.has('error') ? "destructive" : "outline"}
                    onClick={() => toggleFilter('error')}
                    className="h-6 px-2 text-xs"
                  >
                    <AlertCircle className="h-3 w-3 mr-1" />
                    Errors ({entries.filter(e => e.type === 'error').length})
                  </Button>
                )}

                {debugConfig.console_logs && (
                  <>
                    <Button
                      size="sm"
                      variant={activeFilters.has('warn') ? "default" : "outline"}
                      onClick={() => toggleFilter('warn')}
                      className={`h-6 px-2 text-xs ${
                        activeFilters.has('warn')
                          ? 'bg-yellow-600 hover:bg-yellow-700 text-white border-yellow-600'
                          : 'border-yellow-600 text-yellow-600 hover:bg-yellow-600 hover:text-white'
                      }`}
                    >
                      <AlertTriangle className="h-3 w-3 mr-1" />
                      Warnings ({entries.filter(e => e.type === 'warn').length})
                    </Button>

                    <Button
                      size="sm"
                      variant={activeFilters.has('log') ? "default" : "outline"}
                      onClick={() => toggleFilter('log')}
                      className={`h-6 px-2 text-xs ${
                        activeFilters.has('log')
                          ? 'bg-green-600 hover:bg-green-700 text-white border-green-600'
                          : 'border-green-600 text-green-600 hover:bg-green-600 hover:text-white'
                      }`}
                    >
                      <CheckCircle className="h-3 w-3 mr-1" />
                      Logs ({entries.filter(e => e.type === 'log').length})
                    </Button>

                    <Button
                      size="sm"
                      variant={activeFilters.has('info') ? "default" : "outline"}
                      onClick={() => toggleFilter('info')}
                      className={`h-6 px-2 text-xs ${
                        activeFilters.has('info')
                          ? 'bg-blue-600 hover:bg-blue-700 text-white border-blue-600'
                          : 'border-blue-600 text-blue-600 hover:bg-blue-600 hover:text-white'
                      }`}
                    >
                      <Info className="h-3 w-3 mr-1" />
                      Info ({entries.filter(e => e.type === 'info').length})
                    </Button>
                  </>
                )}

                {debugConfig.network_monitor && (
                  <Button
                    size="sm"
                    variant={activeFilters.has('network') ? "default" : "outline"}
                    onClick={() => toggleFilter('network')}
                    className={`h-6 px-2 text-xs ${
                      activeFilters.has('network')
                        ? 'bg-purple-600 hover:bg-purple-700 text-white border-purple-600'
                        : 'border-purple-600 text-purple-600 hover:bg-purple-600 hover:text-white'
                    }`}
                  >
                    <Globe className="h-3 w-3 mr-1" />
                    Network ({entries.filter(e => e.type === 'network').length})
                  </Button>
                )}
              </div>

              {/* Action Buttons */}
              <div className="flex items-center gap-1">
                <Button
                  size="sm"
                  variant="ghost"
                  onClick={() => setAutoScroll(!autoScroll)}
                  className="h-6 px-2 text-xs"
                  title={autoScroll ? "Disable auto-scroll" : "Enable auto-scroll"}
                >
                  {autoScroll ? <Eye className="h-3 w-3" /> : <EyeOff className="h-3 w-3" />}
                </Button>

                <Button
                  size="sm"
                  variant="ghost"
                  onClick={exportLogs}
                  className="h-6 px-2 text-xs"
                  title="Export logs"
                >
                  <Download className="h-3 w-3" />
                </Button>

                <Button
                  size="sm"
                  variant="ghost"
                  onClick={clearAll}
                  className="h-6 px-2 text-xs text-red-400 hover:text-red-300"
                  title="Clear all logs"
                >
                  <Trash2 className="h-3 w-3" />
                </Button>
              </div>
            </div>
          )}
        </CardHeader>
        
        {!isMinimized && (
          <CardContent className="p-0">
            <div 
              ref={consoleRef}
              className="max-h-80 overflow-y-auto bg-gray-950 border-t border-gray-700"
            >
              {filteredEntries.length === 0 ? (
                <div className="p-4 text-center text-gray-500 text-sm">
                  {activeFilters.size === 0
                    ? 'No log types selected'
                    : `No ${Array.from(activeFilters).join(', ')} entries found`
                  }
                </div>
              ) : (
                filteredEntries.map((entry) => (
                  <div key={entry.id} className="border-b border-gray-800 p-3 hover:bg-gray-800/50">
                    <div className="flex items-start justify-between gap-2">
                      <div className="flex items-start gap-2 flex-1 min-w-0">
                        {getIcon(entry.type)}
                        <div className="flex-1 min-w-0">
                          <div className="flex items-center gap-2 mb-1">
                            <Badge className={`text-xs ${getBadgeColor(entry.type)}`}>
                              {entry.type}
                            </Badge>
                            <span className="text-xs text-gray-400">
                              {entry.timestamp.toLocaleTimeString()}
                            </span>
                            {entry.source && (
                              <Badge variant="outline" className="text-xs border-gray-600 text-gray-400">
                                {entry.source}
                              </Badge>
                            )}
                            {entry.duration && (
                              <Badge variant="outline" className="text-xs border-blue-600 text-blue-400">
                                {entry.duration}ms
                              </Badge>
                            )}
                            {entry.statusCode && (
                              <Badge variant="outline" className={`text-xs ${
                                entry.statusCode >= 200 && entry.statusCode < 300
                                  ? 'border-green-600 text-green-400'
                                  : 'border-red-600 text-red-400'
                              }`}>
                                {entry.statusCode}
                              </Badge>
                            )}
                          </div>
                          <div className="text-sm text-white break-words">
                            {entry.message}
                          </div>

                          {entry.requestData && (
                            <details className="mt-2">
                              <summary className="text-xs text-blue-400 cursor-pointer hover:text-blue-300">
                                📤 Request Data
                              </summary>
                              <pre className="text-xs text-gray-300 mt-1 whitespace-pre-wrap bg-gray-900 p-2 rounded max-h-32 overflow-y-auto">
                                {typeof entry.requestData === 'string'
                                  ? entry.requestData
                                  : safeStringify(entry.requestData)}
                              </pre>
                            </details>
                          )}

                          {entry.responseData && (
                            <details className="mt-2">
                              <summary className="text-xs text-green-400 cursor-pointer hover:text-green-300">
                                📥 Response Data
                              </summary>
                              <pre className="text-xs text-gray-300 mt-1 whitespace-pre-wrap bg-gray-900 p-2 rounded max-h-32 overflow-y-auto">
                                {typeof entry.responseData === 'string'
                                  ? entry.responseData
                                  : safeStringify(entry.responseData)}
                              </pre>
                            </details>
                          )}

                          {entry.details && entry.source?.includes('fetch') && (
                            <details className="mt-2">
                              <summary className="text-xs text-purple-400 cursor-pointer hover:text-purple-300">
                                🔍 Network Details
                              </summary>
                              <pre className="text-xs text-gray-300 mt-1 whitespace-pre-wrap bg-gray-900 p-2 rounded max-h-32 overflow-y-auto">
                                {safeStringify(entry.details)}
                              </pre>
                            </details>
                          )}

                          {entry.stack && (
                            <details className="mt-2">
                              <summary className="text-xs text-red-400 cursor-pointer hover:text-red-300">
                                📍 Stack Trace
                              </summary>
                              <pre className="text-xs text-gray-300 mt-1 whitespace-pre-wrap bg-gray-900 p-2 rounded max-h-32 overflow-y-auto">
                                {entry.stack}
                              </pre>
                            </details>
                          )}

                          {entry.details && !entry.source?.includes('fetch') && (
                            <details className="mt-2">
                              <summary className="text-xs text-yellow-400 cursor-pointer hover:text-yellow-300">
                                📋 Additional Details
                              </summary>
                              <pre className="text-xs text-gray-300 mt-1 whitespace-pre-wrap bg-gray-900 p-2 rounded max-h-32 overflow-y-auto">
                                {safeStringify(entry.details)}
                              </pre>
                            </details>
                          )}

                          {entry.url && (
                            <div className="text-xs text-gray-400 mt-1">
                              📍 {entry.url}:{entry.line}:{entry.column}
                            </div>
                          )}
                        </div>
                      </div>
                      <div className="flex gap-1">
                        <Button
                          size="sm"
                          variant="ghost"
                          onClick={() => copyToClipboard(entry)}
                          className="h-6 w-6 p-0 text-gray-400 hover:text-white"
                        >
                          <Copy className="h-3 w-3" />
                        </Button>
                        <Button
                          size="sm"
                          variant="ghost"
                          onClick={() => dismissEntry(entry.id)}
                          className="h-6 w-6 p-0 text-gray-400 hover:text-red-400"
                        >
                          <X className="h-3 w-3" />
                        </Button>
                      </div>
                    </div>
                  </div>
                ))
              )}
            </div>
          </CardContent>
        )}
      </Card>
    </div>
  );
};

export default SimpleDebugConsole;

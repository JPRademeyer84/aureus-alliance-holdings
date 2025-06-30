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
  Eye,
  EyeOff,
  Download,
  Filter
} from 'lucide-react';

interface DebugEntry {
  id: string;
  type: 'error' | 'warn' | 'log' | 'info';
  message: string;
  stack?: string;
  timestamp: Date;
  url?: string;
  line?: number;
  column?: number;
  source: 'console' | 'unhandledrejection' | 'error' | 'network';
  details?: any;
}

const DebugConsole: React.FC = () => {
  const [entries, setEntries] = useState<DebugEntry[]>([]);
  const [isVisible, setIsVisible] = useState(false);
  const [isMinimized, setIsMinimized] = useState(true);
  const [activeFilters, setActiveFilters] = useState<Set<'error' | 'warn' | 'log' | 'info'>>(
    new Set(['error', 'warn', 'log', 'info'])
  );
  const [autoScroll, setAutoScroll] = useState(true);
  const consoleRef = useRef<HTMLDivElement>(null);
  const originalConsole = useRef<any>({});

  useEffect(() => {
    // Store original console methods
    originalConsole.current = {
      log: console.log,
      warn: console.warn,
      error: console.error,
      info: console.info
    };

    // Override console methods
    const addEntry = (type: DebugEntry['type'], args: any[], source: DebugEntry['source'] = 'console') => {
      const message = args.map(arg => 
        typeof arg === 'object' ? JSON.stringify(arg, null, 2) : String(arg)
      ).join(' ');

      const entry: DebugEntry = {
        id: Date.now() + Math.random().toString(),
        type,
        message,
        timestamp: new Date(),
        source,
        details: args.length === 1 && typeof args[0] === 'object' ? args[0] : args
      };

      setEntries(prev => [...prev, entry]);
    };

    console.log = (...args) => {
      originalConsole.current.log(...args);
      addEntry('log', args);
    };

    console.warn = (...args) => {
      originalConsole.current.warn(...args);
      addEntry('warn', args);
    };

    console.error = (...args) => {
      originalConsole.current.error(...args);
      addEntry('error', args);
    };

    console.info = (...args) => {
      originalConsole.current.info(...args);
      addEntry('info', args);
    };

    // Capture unhandled errors
    const handleError = (event: ErrorEvent) => {
      const entry: DebugEntry = {
        id: Date.now() + Math.random().toString(),
        type: 'error',
        message: event.message,
        stack: event.error?.stack,
        timestamp: new Date(),
        url: event.filename,
        line: event.lineno,
        column: event.colno,
        source: 'error',
        details: event.error
      };
      setEntries(prev => [...prev, entry]);
    };

    // Capture unhandled promise rejections
    const handleUnhandledRejection = (event: PromiseRejectionEvent) => {
      const entry: DebugEntry = {
        id: Date.now() + Math.random().toString(),
        type: 'error',
        message: `Unhandled Promise Rejection: ${event.reason}`,
        stack: event.reason?.stack,
        timestamp: new Date(),
        source: 'unhandledrejection',
        details: event.reason
      };
      setEntries(prev => [...prev, entry]);
    };

    // Capture network errors
    const originalFetch = window.fetch;
    window.fetch = async (...args) => {
      try {
        const response = await originalFetch(...args);
        if (!response.ok) {
          const entry: DebugEntry = {
            id: Date.now() + Math.random().toString(),
            type: 'error',
            message: `Network Error: ${response.status} ${response.statusText} - ${args[0]}`,
            timestamp: new Date(),
            source: 'network',
            details: { url: args[0], status: response.status, statusText: response.statusText }
          };
          setEntries(prev => [...prev, entry]);
        }
        return response;
      } catch (error) {
        const entry: DebugEntry = {
          id: Date.now() + Math.random().toString(),
          type: 'error',
          message: `Network Error: ${error} - ${args[0]}`,
          timestamp: new Date(),
          source: 'network',
          details: { url: args[0], error }
        };
        setEntries(prev => [...prev, entry]);
        throw error;
      }
    };

    window.addEventListener('error', handleError);
    window.addEventListener('unhandledrejection', handleUnhandledRejection);

    return () => {
      // Restore original console methods
      console.log = originalConsole.current.log;
      console.warn = originalConsole.current.warn;
      console.error = originalConsole.current.error;
      console.info = originalConsole.current.info;
      window.fetch = originalFetch;
      window.removeEventListener('error', handleError);
      window.removeEventListener('unhandledrejection', handleUnhandledRejection);
    };
  }, []);

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
      default: return <Info className="h-4 w-4 text-gray-500" />;
    }
  };

  const getBadgeColor = (type: DebugEntry['type']) => {
    switch (type) {
      case 'error': return 'bg-red-500/10 border-red-500/30 text-red-400';
      case 'warn': return 'bg-yellow-500/10 border-yellow-500/30 text-yellow-400';
      case 'info': return 'bg-blue-500/10 border-blue-500/30 text-blue-400';
      case 'log': return 'bg-green-500/10 border-green-500/30 text-green-400';
      default: return 'bg-gray-500/10 border-gray-500/30 text-gray-400';
    }
  };

  const copyToClipboard = (entry: DebugEntry) => {
    const text = `[${entry.timestamp.toISOString()}] ${entry.type.toUpperCase()}: ${entry.message}${
      entry.stack ? `\n\nStack Trace:\n${entry.stack}` : ''
    }${
      entry.url ? `\n\nLocation: ${entry.url}:${entry.line}:${entry.column}` : ''
    }`;
    
    navigator.clipboard.writeText(text).then(() => {
      console.log('Debug entry copied to clipboard');
    });
  };

  const dismissEntry = (id: string) => {
    setEntries(prev => prev.filter(entry => entry.id !== id));
  };

  const clearAll = () => {
    setEntries([]);
  };

  const exportLogs = () => {
    const logs = entries.map(entry => ({
      timestamp: entry.timestamp.toISOString(),
      type: entry.type,
      message: entry.message,
      stack: entry.stack,
      url: entry.url,
      line: entry.line,
      column: entry.column,
      source: entry.source,
      details: entry.details
    }));

    const blob = new Blob([JSON.stringify(logs, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `debug-logs-${new Date().toISOString().split('T')[0]}.json`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  };

  const toggleFilter = (type: 'error' | 'warn' | 'log' | 'info') => {
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
    const allTypes: ('error' | 'warn' | 'log' | 'info')[] = ['error', 'warn', 'log', 'info'];
    const allActive = allTypes.every(type => activeFilters.has(type));

    if (allActive) {
      setActiveFilters(new Set());
    } else {
      setActiveFilters(new Set(allTypes));
    }
  };

  const filteredEntries = entries.filter(entry =>
    activeFilters.has(entry.type)
  );

  const errorCount = entries.filter(e => e.type === 'error').length;
  const warnCount = entries.filter(e => e.type === 'warn').length;

  if (!isVisible) {
    return (
      <div className="fixed bottom-4 right-4 z-50">
        <Button
          onClick={() => setIsVisible(true)}
          className={`bg-gray-800 hover:bg-gray-700 text-white border ${
            errorCount > 0 ? 'border-red-500 animate-pulse' : 
            warnCount > 0 ? 'border-yellow-500' : 'border-gray-600'
          }`}
        >
          <Bug className="h-4 w-4 mr-2" />
          Debug
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
    <div className="fixed bottom-4 right-4 z-50 w-96 max-w-[90vw]">
      <Card className="bg-gray-900 border-gray-700 text-white shadow-2xl">
        <CardHeader className="pb-2">
          <div className="flex items-center justify-between">
            <CardTitle className="text-sm flex items-center">
              <Bug className="h-4 w-4 mr-2" />
              Debug Console ({filteredEntries.length})
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
              {/* Filter Toggle Buttons */}
              <div className="flex items-center gap-1 flex-wrap">
                <Button
                  size="sm"
                  variant={activeFilters.size === 4 ? "default" : "outline"}
                  onClick={toggleAllFilters}
                  className="h-6 px-2 text-xs"
                >
                  All ({entries.length})
                </Button>

                <Button
                  size="sm"
                  variant={activeFilters.has('error') ? "destructive" : "outline"}
                  onClick={() => toggleFilter('error')}
                  className="h-6 px-2 text-xs"
                >
                  <AlertCircle className="h-3 w-3 mr-1" />
                  Errors ({entries.filter(e => e.type === 'error').length})
                </Button>

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
                      ? 'bg-blue-600 hover:bg-blue-700 text-white border-blue-600'
                      : 'border-blue-600 text-blue-600 hover:bg-blue-600 hover:text-white'
                  }`}
                >
                  <Info className="h-3 w-3 mr-1" />
                  Logs ({entries.filter(e => e.type === 'log').length})
                </Button>

                <Button
                  size="sm"
                  variant={activeFilters.has('info') ? "default" : "outline"}
                  onClick={() => toggleFilter('info')}
                  className={`h-6 px-2 text-xs ${
                    activeFilters.has('info')
                      ? 'bg-green-600 hover:bg-green-700 text-white border-green-600'
                      : 'border-green-600 text-green-600 hover:bg-green-600 hover:text-white'
                  }`}
                >
                  <Info className="h-3 w-3 mr-1" />
                  Info ({entries.filter(e => e.type === 'info').length})
                </Button>
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
                            <Badge variant="outline" className="text-xs border-gray-600 text-gray-400">
                              {entry.source}
                            </Badge>
                          </div>
                          <div className="text-sm text-white break-words">
                            {entry.message}
                          </div>
                          {entry.stack && (
                            <details className="mt-2">
                              <summary className="text-xs text-gray-400 cursor-pointer hover:text-white">
                                Stack Trace
                              </summary>
                              <pre className="text-xs text-gray-300 mt-1 whitespace-pre-wrap bg-gray-900 p-2 rounded">
                                {entry.stack}
                              </pre>
                            </details>
                          )}
                          {entry.url && (
                            <div className="text-xs text-gray-400 mt-1">
                              {entry.url}:{entry.line}:{entry.column}
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

export default DebugConsole;

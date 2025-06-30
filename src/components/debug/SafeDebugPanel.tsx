import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { X, Bug, AlertTriangle, Info, CheckCircle } from 'lucide-react';

interface DebugLog {
  id: string;
  type: 'error' | 'warn' | 'info' | 'success';
  message: string;
  timestamp: Date;
  details?: string;
}

const SafeDebugPanel: React.FC = () => {
  const [isVisible, setIsVisible] = useState(false);
  const [logs, setLogs] = useState<DebugLog[]>([]);
  const [isMinimized, setIsMinimized] = useState(true);

  const addLog = (type: DebugLog['type'], message: string, details?: string) => {
    const newLog: DebugLog = {
      id: `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`, // Unique ID
      type,
      message,
      timestamp: new Date(),
      details
    };

    setLogs(prev => [...prev.slice(-49), newLog]); // Keep only last 50 logs
  };

  const clearLogs = () => {
    setLogs([]);
  };

  const getIcon = (type: DebugLog['type']) => {
    switch (type) {
      case 'error': return <AlertTriangle className="h-4 w-4 text-red-500" />;
      case 'warn': return <AlertTriangle className="h-4 w-4 text-yellow-500" />;
      case 'info': return <Info className="h-4 w-4 text-blue-500" />;
      case 'success': return <CheckCircle className="h-4 w-4 text-green-500" />;
    }
  };

  const getBadgeColor = (type: DebugLog['type']) => {
    switch (type) {
      case 'error': return 'bg-red-500/10 border-red-500/30 text-red-400';
      case 'warn': return 'bg-yellow-500/10 border-yellow-500/30 text-yellow-400';
      case 'info': return 'bg-blue-500/10 border-blue-500/30 text-blue-400';
      case 'success': return 'bg-green-500/10 border-green-500/30 text-green-400';
    }
  };

  // Test functions for debugging
  const testAPI = async () => {
    addLog('info', 'Testing API connection...');
    try {
      const response = await fetch('http://localhost/Aureus%201%20-%20Complex/api/users/session-status.php', {
        credentials: 'include'
      });
      const text = await response.text();
      addLog('info', `Session API status: ${response.status}`);
      addLog('info', `Session API response: ${text}`);

      if (response.ok) {
        addLog('success', 'API connection successful');
      } else {
        addLog('warn', `API returned status: ${response.status}`);
      }
    } catch (error) {
      addLog('error', 'API connection failed', error instanceof Error ? error.message : 'Unknown error');
    }
  };

  const testLocalStorage = () => {
    addLog('info', 'Testing localStorage...');
    try {
      localStorage.setItem('debug-test', 'test-value');
      const value = localStorage.getItem('debug-test');
      if (value === 'test-value') {
        addLog('success', 'localStorage working correctly');
        localStorage.removeItem('debug-test');
      } else {
        addLog('error', 'localStorage test failed');
      }
    } catch (error) {
      addLog('error', 'localStorage not available', error instanceof Error ? error.message : 'Unknown error');
    }
  };

  const testSessionStorage = () => {
    addLog('info', 'Testing sessionStorage...');
    try {
      sessionStorage.setItem('debug-test', 'test-value');
      const value = sessionStorage.getItem('debug-test');
      if (value === 'test-value') {
        addLog('success', 'sessionStorage working correctly');
        sessionStorage.removeItem('debug-test');
      } else {
        addLog('error', 'sessionStorage test failed');
      }
    } catch (error) {
      addLog('error', 'sessionStorage not available', error instanceof Error ? error.message : 'Unknown error');
    }
  };

  const checkEnvironment = () => {
    addLog('info', 'Checking environment...');
    addLog('info', `Node ENV: ${process.env.NODE_ENV || 'undefined'}`);
    addLog('info', `Current URL: ${window.location.href}`);
    addLog('info', `User Agent: ${navigator.userAgent.substring(0, 50)}...`);
    addLog('info', `Screen: ${window.screen.width}x${window.screen.height}`);
    addLog('info', `Viewport: ${window.innerWidth}x${window.innerHeight}`);
  };

  const debugInvestmentIssue = async () => {
    addLog('info', 'Running comprehensive investment debug...');
    try {
      const response = await fetch('http://localhost/aureus-angel-alliance/api/debug/debug-investment-history.php', {
        credentials: 'include'
      });

      const text = await response.text();
      addLog('info', `Debug response status: ${response.status}`);

      if (response.ok) {
        try {
          const data = JSON.parse(text);
          addLog('success', 'Debug data retrieved successfully');

          // Log key findings
          if (data.session_info) {
            addLog('info', `Session user_id: ${data.session_info.user_id_value || 'NOT SET'}`);
          }

          if (data.table_info) {
            addLog('info', `Investments table exists: ${data.table_info.aureus_investments_exists ? 'YES' : 'NO'}`);
            if (data.table_info.total_records !== undefined) {
              addLog('info', `Total investment records: ${data.table_info.total_records}`);
            }
          }

          if (data.investment_data) {
            addLog('info', `User investments found: ${data.investment_data.user_investments_count || 0}`);
          }

          // Log full debug data for detailed analysis
          addLog('info', 'Full debug data:', JSON.stringify(data, null, 2).substring(0, 500) + '...');

        } catch (parseError) {
          addLog('error', 'Failed to parse debug response', text.substring(0, 200));
        }
      } else {
        addLog('error', `Debug script failed: ${response.status}`, text.substring(0, 200));
      }
    } catch (error) {
      addLog('error', 'Debug script request failed', error instanceof Error ? error.message : 'Unknown error');
    }
  };

  const testSimplePHP = async () => {
    addLog('info', 'Testing basic PHP functionality...');
    try {
      const response = await fetch('http://localhost/aureus-angel-alliance/api/debug/simple-test.php');
      const text = await response.text();

      addLog('info', `Simple PHP test status: ${response.status}`);
      addLog('info', `Simple PHP response: ${text}`);

      if (response.ok) {
        try {
          const data = JSON.parse(text);
          addLog('success', 'Basic PHP is working!', `PHP version: ${data.php_version}`);
        } catch (parseError) {
          addLog('error', 'PHP response not JSON', text);
        }
      } else {
        addLog('error', `PHP test failed: ${response.status}`, text);
      }
    } catch (error) {
      addLog('error', 'PHP test request failed', error instanceof Error ? error.message : 'Unknown error');
    }
  };

  const checkDirectDatabase = async () => {
    addLog('info', '🔥 DIRECT DATABASE CHECK - NO AUTH BULLSHIT');
    try {
      const response = await fetch('http://localhost/Aureus%201%20-%20Complex/api/debug/direct-investment-check.php');
      const text = await response.text();

      addLog('info', `Direct DB status: ${response.status}`);

      if (response.ok) {
        try {
          const data = JSON.parse(text);
          addLog('success', '✅ DIRECT DATABASE ACCESS WORKING!');

          if (data.total_investments !== undefined) {
            addLog('info', `🎯 TOTAL INVESTMENTS FOUND: ${data.total_investments}`);

            if (data.total_investments > 0) {
              addLog('success', '🎉 YOUR INVESTMENTS ARE IN THE DATABASE!');
              data.investments.forEach((inv, index) => {
                addLog('info', `Investment ${index + 1}: ${inv.package_name || 'Unknown'} - $${inv.amount} - Status: ${inv.status}`);
              });
            } else {
              addLog('warn', '⚠️ NO INVESTMENTS FOUND IN DATABASE');
            }
          }

          if (data.alternative_tables) {
            Object.keys(data.alternative_tables).forEach(table => {
              const tableData = data.alternative_tables[table];
              if (tableData.exists && tableData.count > 0) {
                addLog('info', `📊 Found ${tableData.count} records in ${table} table`);
              }
            });
          }

        } catch (parseError) {
          addLog('error', 'Failed to parse direct DB response', text.substring(0, 200));
        }
      } else {
        addLog('error', `Direct DB check failed: ${response.status}`, text);
      }
    } catch (error) {
      addLog('error', 'Direct DB request failed', error instanceof Error ? error.message : 'Unknown error');
    }
  };

  const testParticipationHistory = async () => {
    addLog('info', 'Testing Investment History API...');
    try {
      const response = await fetch('http://localhost/aureus-angel-alliance/get-my-investments.php?user_id=1', {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        }
      });

      addLog('info', `Response status: ${response.status} ${response.statusText}`);

      const text = await response.text();
      addLog('info', `Response body: ${text.substring(0, 200)}...`);

      if (response.ok) {
        try {
          const data = JSON.parse(text);
          addLog('success', 'Investment History API working', `Data: ${JSON.stringify(data).substring(0, 100)}...`);
        } catch (parseError) {
          addLog('error', 'JSON Parse Error', `Response was: ${text}`);
        }
      } else {
        addLog('error', `API Error: ${response.status}`, text);
      }
    } catch (error) {
      addLog('error', 'Investment History API failed', error instanceof Error ? error.message : 'Unknown error');
    }
  };

  const testInvestmentHistory = async () => {
    addLog('info', 'Testing Investment History API...');
    try {
      const response = await fetch('http://localhost/aureus-angel-alliance/get-my-investments.php?user_id=1', {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        }
      });

      addLog('info', `Response status: ${response.status} ${response.statusText}`);
      addLog('info', `Response headers: ${JSON.stringify(Object.fromEntries(response.headers.entries()))}`);

      const text = await response.text();
      addLog('info', `Response body (full): ${text}`);

      if (response.ok) {
        try {
          const data = JSON.parse(text);
          addLog('success', 'Investment History API working', `Data: ${JSON.stringify(data).substring(0, 100)}...`);
        } catch (parseError) {
          addLog('error', 'JSON Parse Error', `Response was: ${text}`);
        }
      } else {
        addLog('error', `API Error: ${response.status}`, `Full response: ${text}`);
      }
    } catch (error) {
      addLog('error', 'Investment History API failed', error instanceof Error ? error.message : 'Unknown error');
    }
  };

  useEffect(() => {
    // Add initial log
    addLog('success', 'Safe Debug Panel initialized');

    // Capture JavaScript errors
    const handleError = (event: ErrorEvent) => {
      addLog('error', `JS Error: ${event.message}`, `File: ${event.filename}:${event.lineno}:${event.colno}`);
    };

    // Capture unhandled promise rejections
    const handleUnhandledRejection = (event: PromiseRejectionEvent) => {
      addLog('error', 'Unhandled Promise Rejection', String(event.reason));
    };

    // Capture console errors, warnings, and logs
    const originalConsoleError = console.error;
    const originalConsoleWarn = console.warn;
    const originalConsoleLog = console.log;

    console.error = (...args) => {
      const message = args.map(arg => {
        if (typeof arg === 'object') {
          try {
            return JSON.stringify(arg, null, 2);
          } catch {
            return String(arg);
          }
        }
        return String(arg);
      }).join(' ');

      // Block wallet protection and jQuery SVG messages from being logged
      if (message.includes('Wallet Protection Hook') ||
          message.includes('🛡️') ||
          message.includes('SafePal') ||
          message.includes('TrustWallet') ||
          message.includes('ethereum provider') ||
          (message.includes('Error: <path>') && message.includes('attribute d') && message.includes('Expected number')) ||
          (message.includes('tc0.2,0,0.4-0.2,0')) ||
          (message.includes('jquery') && message.includes('path') && message.includes('Expected number'))) {
        // Silent block - don't log spam messages
        originalConsoleError.apply(console, args);
        return;
      }

      addLog('error', `Console Error: ${message}`);
      originalConsoleError.apply(console, args);
    };

    console.warn = (...args) => {
      const message = args.map(arg => {
        if (typeof arg === 'object') {
          try {
            return JSON.stringify(arg, null, 2);
          } catch {
            return String(arg);
          }
        }
        return String(arg);
      }).join(' ');

      // Block wallet protection messages from being logged
      if (message.includes('Wallet Protection Hook') ||
          message.includes('🛡️') ||
          message.includes('SafePal') ||
          message.includes('TrustWallet') ||
          message.includes('ethereum provider')) {
        // Silent block - don't log wallet protection spam
        originalConsoleWarn.apply(console, args);
        return;
      }

      addLog('warn', `Console Warning: ${message}`);
      originalConsoleWarn.apply(console, args);
    };

    console.log = (...args) => {
      const message = args.map(arg => {
        if (typeof arg === 'object') {
          try {
            return JSON.stringify(arg, null, 2);
          } catch {
            return String(arg);
          }
        }
        return String(arg);
      }).join(' ');

      // Block wallet protection messages from being logged
      if (message.includes('Wallet Protection Hook') ||
          message.includes('🛡️') ||
          message.includes('SafePal') ||
          message.includes('TrustWallet') ||
          message.includes('ethereum provider')) {
        // Silent block - don't log wallet protection spam
        originalConsoleLog.apply(console, args);
        return;
      }

      // Only log console.log messages that contain 'error' or 'fail'
      if (message.toLowerCase().includes('error') || message.toLowerCase().includes('fail')) {
        addLog('warn', `Console Log: ${message}`);
      }
      originalConsoleLog.apply(console, args);
    };

    // Capture fetch errors
    const originalFetch = window.fetch;
    window.fetch = async (...args) => {
      try {
        const response = await originalFetch(...args);
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

        if (!response.ok) {
          addLog('error', `Fetch Error: ${response.status} ${response.statusText}`, `URL: ${url}`);
        } else {
          // Only log successful requests to our API
          if (url.includes('aureus-angel-alliance')) {
            addLog('success', `API Success: ${response.status}`, `URL: ${url}`);
          }
        }
        return response;
      } catch (error) {
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

        addLog('error', `Network Error: ${error instanceof Error ? error.message : 'Unknown error'}`, `URL: ${url}`);
        throw error;
      }
    };

    // Add event listeners
    window.addEventListener('error', handleError);
    window.addEventListener('unhandledrejection', handleUnhandledRejection);

    // Cleanup function
    return () => {
      window.removeEventListener('error', handleError);
      window.removeEventListener('unhandledrejection', handleUnhandledRejection);

      // Restore original console methods
      console.error = originalConsoleError;
      console.warn = originalConsoleWarn;
      console.log = originalConsoleLog;

      // Restore original fetch
      window.fetch = originalFetch;
    };
  }, []);

  if (!isVisible) {
    return (
      <Button
        onClick={() => setIsVisible(true)}
        className="fixed bottom-4 right-4 z-50 bg-purple-600 hover:bg-purple-700 text-white shadow-lg"
        size="sm"
      >
        <Bug className="h-4 w-4 mr-2" />
        Debug
      </Button>
    );
  }

  return (
    <div className="fixed bottom-4 right-4 z-50 w-[500px] max-h-[600px]">
      <Card className="bg-gray-900 border-gray-700 text-white shadow-2xl">
        <CardHeader className="pb-2">
          <div className="flex items-center justify-between">
            <CardTitle className="text-sm flex items-center">
              <Bug className="h-4 w-4 mr-2 text-purple-400" />
              Safe Debug Panel
            </CardTitle>
            <div className="flex items-center space-x-2">
              <Button
                onClick={() => setIsMinimized(!isMinimized)}
                variant="ghost"
                size="sm"
                className="h-6 w-6 p-0 text-gray-400 hover:text-white"
              >
                {isMinimized ? '▲' : '▼'}
              </Button>
              <Button
                onClick={() => setIsVisible(false)}
                variant="ghost"
                size="sm"
                className="h-6 w-6 p-0 text-gray-400 hover:text-white"
              >
                <X className="h-4 w-4" />
              </Button>
            </div>
          </div>
        </CardHeader>
        
        {!isMinimized && (
          <CardContent className="pt-0">
            <div className="space-y-2 mb-3">
              <div className="flex flex-wrap gap-1">
                <Button onClick={testAPI} size="sm" variant="outline" className="text-xs">
                  Test API
                </Button>
                <Button onClick={testParticipationHistory} size="sm" variant="outline" className="text-xs">
                  Test History
                </Button>
                <Button onClick={testInvestmentHistory} size="sm" variant="outline" className="text-xs">
                  Test Investment
                </Button>
                <Button onClick={testLocalStorage} size="sm" variant="outline" className="text-xs">
                  Storage
                </Button>
                <Button onClick={checkEnvironment} size="sm" variant="outline" className="text-xs">
                  Env
                </Button>
                <Button onClick={checkDirectDatabase} size="sm" variant="outline" className="text-xs bg-red-600 hover:bg-red-700 text-white">
                  🔥 DIRECT DB
                </Button>
                <Button onClick={testSimplePHP} size="sm" variant="outline" className="text-xs">
                  Test PHP
                </Button>
                <Button onClick={debugInvestmentIssue} size="sm" variant="outline" className="text-xs">
                  Debug DB
                </Button>
                <Button onClick={clearLogs} size="sm" variant="outline" className="text-xs">
                  Clear
                </Button>
              </div>
            </div>
            
            <div className="max-h-80 overflow-y-auto space-y-1">
              {logs.length === 0 ? (
                <p className="text-gray-400 text-xs">No logs yet... Errors will appear here automatically!</p>
              ) : (
                logs.slice(-20).map((log) => (
                  <div key={log.id} className="flex items-start space-x-2 text-xs">
                    {getIcon(log.type)}
                    <div className="flex-1 min-w-0">
                      <div className="flex items-center space-x-2">
                        <Badge className={`text-xs px-1 py-0 ${getBadgeColor(log.type)}`}>
                          {log.type}
                        </Badge>
                        <span className="text-gray-300 text-xs">
                          {log.timestamp.toLocaleTimeString()}
                        </span>
                      </div>
                      <p className="text-white mt-1 break-words">{log.message}</p>
                      {log.details && (
                        <p className="text-gray-400 mt-1 text-xs break-words">{log.details}</p>
                      )}
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

export default SafeDebugPanel;

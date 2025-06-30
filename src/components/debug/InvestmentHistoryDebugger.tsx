import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { AlertTriangle, CheckCircle, Info, RefreshCw } from 'lucide-react';

const InvestmentHistoryDebugger: React.FC = () => {
  const [debugInfo, setDebugInfo] = useState<any>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const testInvestmentHistoryAPI = async () => {
    setIsLoading(true);
    setError(null);
    setDebugInfo(null);

    const debugData: any = {
      timestamp: new Date().toISOString(),
      tests: []
    };

    try {
      // Test 1: Check session status
      debugData.tests.push({ name: 'Session Status', status: 'testing' });
      
      try {
        const sessionResponse = await fetch('http://localhost/Aureus%201%20-%20Complex/api/users/session-status.php', {
          credentials: 'include'
        });
        const sessionText = await sessionResponse.text();
        debugData.tests[0].status = sessionResponse.ok ? 'success' : 'failed';
        debugData.tests[0].response = sessionText.substring(0, 200);
        debugData.sessionStatus = sessionResponse.status;
      } catch (e) {
        debugData.tests[0].status = 'error';
        debugData.tests[0].error = e instanceof Error ? e.message : 'Unknown error';
      }

      // Test 2: Test investment history API
      debugData.tests.push({ name: 'Investment History', status: 'testing' });

      try {
        const participationResponse = await fetch('http://localhost/aureus-angel-alliance/get-my-investments.php?user_id=1', {
          method: 'GET',
          headers: {
            'Content-Type': 'application/json',
          }
        });
        
        const participationText = await participationResponse.text();
        debugData.tests[1].status = participationResponse.ok ? 'success' : 'failed';
        debugData.tests[1].response = participationText;
        debugData.tests[1].statusCode = participationResponse.status;
        debugData.tests[1].statusText = participationResponse.statusText;
        
        // Try to parse JSON
        try {
          const participationData = JSON.parse(participationText);
          debugData.tests[1].parsedData = participationData;
        } catch (parseError) {
          debugData.tests[1].parseError = 'Invalid JSON response';
        }
        
      } catch (e) {
        debugData.tests[1].status = 'error';
        debugData.tests[1].error = e instanceof Error ? e.message : 'Unknown error';
      }

      // Test 3: Test investment history API
      debugData.tests.push({ name: 'Investment History', status: 'testing' });
      
      try {
        const investmentResponse = await fetch('http://localhost/aureus-angel-alliance/get-my-investments.php?user_id=1', {
          method: 'GET',
          headers: {
            'Content-Type': 'application/json',
          }
        });
        
        const investmentText = await investmentResponse.text();
        debugData.tests[2].status = investmentResponse.ok ? 'success' : 'failed';
        debugData.tests[2].response = investmentText;
        debugData.tests[2].statusCode = investmentResponse.status;
        debugData.tests[2].statusText = investmentResponse.statusText;
        
        // Try to parse JSON
        try {
          const investmentData = JSON.parse(investmentText);
          debugData.tests[2].parsedData = investmentData;
        } catch (parseError) {
          debugData.tests[2].parseError = 'Invalid JSON response';
        }
        
      } catch (e) {
        debugData.tests[2].status = 'error';
        debugData.tests[2].error = e instanceof Error ? e.message : 'Unknown error';
      }

      // Test 4: Check database connection
      debugData.tests.push({ name: 'Database Connection', status: 'testing' });
      
      try {
        const dbResponse = await fetch('http://localhost/aureus-angel-alliance/api/debug/check-session.php', {
          credentials: 'include'
        });
        const dbText = await dbResponse.text();
        debugData.tests[3].status = dbResponse.ok ? 'success' : 'failed';
        debugData.tests[3].response = dbText.substring(0, 200);
      } catch (e) {
        debugData.tests[3].status = 'error';
        debugData.tests[3].error = e instanceof Error ? e.message : 'Unknown error';
      }

      setDebugInfo(debugData);
      
    } catch (error) {
      setError(error instanceof Error ? error.message : 'Unknown error occurred');
    } finally {
      setIsLoading(false);
    }
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'success': return <CheckCircle className="h-4 w-4 text-green-500" />;
      case 'failed': return <AlertTriangle className="h-4 w-4 text-red-500" />;
      case 'error': return <AlertTriangle className="h-4 w-4 text-red-500" />;
      case 'testing': return <RefreshCw className="h-4 w-4 text-blue-500 animate-spin" />;
      default: return <Info className="h-4 w-4 text-gray-500" />;
    }
  };

  useEffect(() => {
    // Auto-run the test when component mounts
    testInvestmentHistoryAPI();
  }, []);

  return (
    <Card className="w-full max-w-4xl mx-auto bg-gray-900 border-gray-700 text-white">
      <CardHeader>
        <CardTitle className="flex items-center justify-between">
          <span className="flex items-center">
            <AlertTriangle className="h-5 w-5 mr-2 text-yellow-500" />
            Investment History Debugger
          </span>
          <Button 
            onClick={testInvestmentHistoryAPI} 
            disabled={isLoading}
            size="sm"
            variant="outline"
          >
            {isLoading ? <RefreshCw className="h-4 w-4 animate-spin" /> : 'Retest'}
          </Button>
        </CardTitle>
      </CardHeader>
      <CardContent>
        {error && (
          <div className="bg-red-500/10 border border-red-500/30 rounded p-3 mb-4">
            <p className="text-red-400 text-sm">{error}</p>
          </div>
        )}
        
        {debugInfo && (
          <div className="space-y-4">
            <div className="text-xs text-gray-400 mb-4">
              Test run at: {new Date(debugInfo.timestamp).toLocaleString()}
            </div>
            
            {debugInfo.tests.map((test: any, index: number) => (
              <div key={index} className="border border-gray-700 rounded p-3">
                <div className="flex items-center mb-2">
                  {getStatusIcon(test.status)}
                  <span className="ml-2 font-medium">{test.name}</span>
                  {test.statusCode && (
                    <span className="ml-auto text-xs text-gray-400">
                      Status: {test.statusCode} {test.statusText}
                    </span>
                  )}
                </div>
                
                {test.error && (
                  <div className="bg-red-500/10 border border-red-500/30 rounded p-2 mt-2">
                    <p className="text-red-400 text-xs">Error: {test.error}</p>
                  </div>
                )}
                
                {test.parseError && (
                  <div className="bg-yellow-500/10 border border-yellow-500/30 rounded p-2 mt-2">
                    <p className="text-yellow-400 text-xs">Parse Error: {test.parseError}</p>
                  </div>
                )}
                
                {test.response && (
                  <details className="mt-2">
                    <summary className="text-xs text-blue-400 cursor-pointer">
                      View Response ({test.response.length} chars)
                    </summary>
                    <pre className="text-xs text-gray-300 mt-2 bg-gray-800 p-2 rounded overflow-x-auto">
                      {test.response}
                    </pre>
                  </details>
                )}
                
                {test.parsedData && (
                  <details className="mt-2">
                    <summary className="text-xs text-green-400 cursor-pointer">
                      View Parsed Data
                    </summary>
                    <pre className="text-xs text-gray-300 mt-2 bg-gray-800 p-2 rounded overflow-x-auto">
                      {JSON.stringify(test.parsedData, null, 2)}
                    </pre>
                  </details>
                )}
              </div>
            ))}
          </div>
        )}
      </CardContent>
    </Card>
  );
};

export default InvestmentHistoryDebugger;

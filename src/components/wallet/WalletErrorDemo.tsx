import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import WalletErrorHandler from './WalletErrorHandler';

const WalletErrorDemo: React.FC = () => {
  const [currentError, setCurrentError] = useState<any>(null);

  const errorExamples = [
    {
      name: 'User Rejected (4001)',
      error: {
        code: 4001,
        message: 'User rejected the request.',
        data: { method: 'PUBLIC_requestAccounts' }
      }
    },
    {
      name: 'Pending Request (-32002)',
      error: {
        code: -32002,
        message: 'Already processing eth_requestAccounts. Please wait.'
      }
    },
    {
      name: 'Wallet Locked (-32603)',
      error: {
        code: -32603,
        message: 'Internal JSON-RPC error.'
      }
    },
    {
      name: 'Wallet Not Found',
      error: {
        message: 'SafePal wallet not detected. Please install the extension and refresh.'
      }
    },
    {
      name: 'Network Error',
      error: {
        code: -32000,
        message: 'Network error occurred while connecting to blockchain.'
      }
    },
    {
      name: 'Connection Timeout',
      error: {
        message: 'Connection timed out - please check if your wallet is unlocked and try again'
      }
    }
  ];

  const handleRetry = () => {
    console.log('Retry button clicked');
    // Simulate clearing error after retry
    setTimeout(() => {
      setCurrentError(null);
    }, 1000);
  };

  return (
    <div className="max-w-4xl mx-auto p-6 space-y-6">
      <Card className="bg-gray-800 border-gray-700">
        <CardHeader>
          <CardTitle className="text-white">Wallet Error Handler Demo</CardTitle>
          <p className="text-gray-400">
            Test different wallet connection error scenarios and see how they're handled.
          </p>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            {errorExamples.map((example, index) => (
              <Button
                key={index}
                variant="outline"
                onClick={() => setCurrentError(example.error)}
                className="text-left justify-start h-auto p-3 border-gray-600 hover:bg-gray-700"
              >
                <div>
                  <div className="font-medium text-white">{example.name}</div>
                  <div className="text-xs text-gray-400 mt-1">
                    Code: {example.error.code || 'N/A'}
                  </div>
                </div>
              </Button>
            ))}
          </div>

          <Button
            variant="ghost"
            onClick={() => setCurrentError(null)}
            className="w-full text-gray-400 hover:text-white"
          >
            Clear Error
          </Button>
        </CardContent>
      </Card>

      {currentError && (
        <Card className="bg-gray-800 border-gray-700">
          <CardHeader>
            <CardTitle className="text-white">Error Handler Output</CardTitle>
          </CardHeader>
          <CardContent>
            <WalletErrorHandler
              error={currentError}
              onRetry={handleRetry}
              isRetrying={false}
            />
          </CardContent>
        </Card>
      )}

      <Card className="bg-gray-800 border-gray-700">
        <CardHeader>
          <CardTitle className="text-white">Error Code Reference</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-3 text-sm">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <h4 className="font-medium text-white mb-2">Common Error Codes:</h4>
                <ul className="space-y-1 text-gray-300">
                  <li><code className="text-gold">4001</code> - User rejected request</li>
                  <li><code className="text-gold">-32002</code> - Request already pending</li>
                  <li><code className="text-gold">-32603</code> - Internal error (wallet locked)</li>
                  <li><code className="text-gold">-32000</code> - Invalid request/network error</li>
                </ul>
              </div>
              <div>
                <h4 className="font-medium text-white mb-2">Error Handling Features:</h4>
                <ul className="space-y-1 text-gray-300">
                  <li>• User-friendly error messages</li>
                  <li>• Specific troubleshooting tips</li>
                  <li>• Retry functionality</li>
                  <li>• Install wallet links</li>
                  <li>• Debug info (dev mode)</li>
                </ul>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
};

export default WalletErrorDemo;

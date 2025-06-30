import React from 'react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { AlertCircle, RefreshCw, ExternalLink, Shield, Info } from 'lucide-react';

interface WalletErrorHandlerProps {
  error: any;
  onRetry: () => void;
  isRetrying?: boolean;
}

const WalletErrorHandler: React.FC<WalletErrorHandlerProps> = ({ 
  error, 
  onRetry, 
  isRetrying = false 
}) => {
  const getErrorInfo = (error: any) => {
    // User rejected the connection request
    if (error?.code === 4001 || error?.message?.includes("User rejected")) {
      return {
        type: 'user_rejected',
        title: 'Connection Cancelled',
        message: 'You cancelled the wallet connection request.',
        suggestion: 'Click "Connect Wallet" again and approve the connection in your SafePal wallet.',
        icon: <Info className="h-4 w-4" />,
        variant: 'default' as const,
        showRetry: true
      };
    }

    // Wallet is locked
    if (error?.code === -32002 || error?.message?.includes("pending")) {
      return {
        type: 'pending_request',
        title: 'Connection Request Pending',
        message: 'There\'s already a pending connection request in your wallet.',
        suggestion: 'Check your SafePal wallet for a pending request and approve it, or wait a moment and try again.',
        icon: <AlertCircle className="h-4 w-4" />,
        variant: 'default' as const,
        showRetry: true
      };
    }

    // Wallet not found
    if (error?.message?.includes("not detected") || error?.message?.includes("not found")) {
      return {
        type: 'wallet_not_found',
        title: 'SafePal Wallet Not Found',
        message: 'SafePal wallet extension is not installed or not detected.',
        suggestion: 'Please install the SafePal wallet extension and refresh the page.',
        icon: <Shield className="h-4 w-4" />,
        variant: 'destructive' as const,
        showRetry: false,
        showInstallLink: true
      };
    }

    // Wallet locked or unauthorized
    if (error?.code === -32603 || error?.message?.includes("locked") || error?.message?.includes("unauthorized")) {
      return {
        type: 'wallet_locked',
        title: 'Wallet Locked',
        message: 'Your SafePal wallet appears to be locked.',
        suggestion: 'Please unlock your SafePal wallet and try connecting again.',
        icon: <AlertCircle className="h-4 w-4" />,
        variant: 'default' as const,
        showRetry: true
      };
    }

    // Network or RPC error
    if (error?.code === -32000 || error?.message?.includes("network") || error?.message?.includes("RPC")) {
      return {
        type: 'network_error',
        title: 'Network Error',
        message: 'There was a problem connecting to the blockchain network.',
        suggestion: 'Check your internet connection and try again. If the problem persists, try switching networks in your wallet.',
        icon: <AlertCircle className="h-4 w-4" />,
        variant: 'destructive' as const,
        showRetry: true
      };
    }

    // Generic error
    return {
      type: 'generic',
      title: 'Connection Error',
      message: error?.message || 'An unexpected error occurred while connecting to your wallet.',
      suggestion: 'Please try connecting again. If the problem persists, try refreshing the page.',
      icon: <AlertCircle className="h-4 w-4" />,
      variant: 'destructive' as const,
      showRetry: true
    };
  };

  const errorInfo = getErrorInfo(error);

  return (
    <div className="space-y-4">
      <Alert variant={errorInfo.variant}>
        <div className="flex items-start gap-3">
          {errorInfo.icon}
          <div className="flex-1 space-y-2">
            <div className="font-medium">{errorInfo.title}</div>
            <AlertDescription className="text-sm">
              {errorInfo.message}
            </AlertDescription>
            <div className="text-sm text-muted-foreground">
              💡 {errorInfo.suggestion}
            </div>
          </div>
        </div>
      </Alert>

      <div className="flex flex-col sm:flex-row gap-3">
        {errorInfo.showRetry && (
          <Button 
            onClick={onRetry} 
            disabled={isRetrying}
            className="flex items-center gap-2"
          >
            <RefreshCw className={`h-4 w-4 ${isRetrying ? 'animate-spin' : ''}`} />
            {isRetrying ? 'Connecting...' : 'Try Again'}
          </Button>
        )}

        {errorInfo.showInstallLink && (
          <Button 
            variant="outline" 
            onClick={() => window.open('https://chrome.google.com/webstore/detail/safepal-extension-wallet/lgmpcpglpngdoalbgeoldeajfclnhafa', '_blank')}
            className="flex items-center gap-2"
          >
            <ExternalLink className="h-4 w-4" />
            Install SafePal Wallet
          </Button>
        )}

        <Button 
          variant="ghost" 
          onClick={() => window.location.reload()}
          className="flex items-center gap-2"
        >
          <RefreshCw className="h-4 w-4" />
          Refresh Page
        </Button>
      </div>

      {/* Troubleshooting Tips */}
      <div className="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-sm">
        <div className="font-medium mb-2">🔧 Troubleshooting Tips:</div>
        <ul className="space-y-1 text-muted-foreground">
          <li>• Make sure SafePal wallet extension is installed and enabled</li>
          <li>• Ensure your wallet is unlocked</li>
          <li>• Check that you're on the correct network (Polygon recommended)</li>
          <li>• Try refreshing the page if connection issues persist</li>
          <li>• Disable other wallet extensions to avoid conflicts</li>
        </ul>
      </div>

      {/* Debug Info (only in development) */}
      {process.env.NODE_ENV === 'development' && (
        <details className="text-xs text-gray-500">
          <summary className="cursor-pointer">Debug Info (Dev Only)</summary>
          <pre className="mt-2 p-2 bg-gray-100 dark:bg-gray-900 rounded overflow-auto">
            {JSON.stringify(error, null, 2)}
          </pre>
        </details>
      )}
    </div>
  );
};

export default WalletErrorHandler;

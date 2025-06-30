import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import { useToast } from '@/hooks/use-toast';
import { Wallet, AlertTriangle, CheckCircle, RefreshCw } from 'lucide-react';

const WalletConnectionTest: React.FC = () => {
  const [isConnecting, setIsConnecting] = useState(false);
  const [connectionError, setConnectionError] = useState<string | null>(null);
  const [walletAddress, setWalletAddress] = useState<string | null>(null);
  const { toast } = useToast();

  const testWalletConnection = async () => {
    setIsConnecting(true);
    setConnectionError(null);
    setWalletAddress(null);

    try {
      // Check if SafePal is available
      const safepal = (window as any).safepal?.ethereum || (window as any).ethereum;

      if (!safepal) {
        throw new Error("SafePal wallet not detected. Please install the SafePal extension.");
      }

      // Request account access
      const accounts = await safepal.request({ method: 'eth_requestAccounts' });

      if (accounts && accounts.length > 0) {
        setWalletAddress(accounts[0]);
        toast({
          title: "Connection Successful!",
          description: `Connected to ${accounts[0].substring(0, 6)}...${accounts[0].substring(accounts[0].length - 4)}`,
        });
      } else {
        throw new Error("No accounts found");
      }

    } catch (error: any) {
      // Only log non-user-rejection errors to avoid console spam
      if (error.code !== 4001) {
        console.error("Wallet connection error:", error);
      } else {
        console.log("User cancelled wallet connection");
      }

      let errorMessage = "Failed to connect wallet. Please try again.";
      let toastTitle = "Connection Error";

      // Handle specific error codes
      if (error.code === 4001) {
        errorMessage = "You cancelled the wallet connection. Please try again and approve the connection to continue.";
        toastTitle = "Connection Cancelled";
      } else if (error.code === -32002) {
        errorMessage = "Please check your SafePal wallet for a pending connection request and approve it.";
        toastTitle = "Connection Pending";
      } else if (error.code === -32603) {
        errorMessage = "Please unlock your SafePal wallet and try connecting again.";
        toastTitle = "Wallet Locked";
      } else if (error.message.includes("not detected")) {
        errorMessage = "SafePal wallet not detected. Please install the SafePal extension and refresh the page.";
        toastTitle = "Wallet Not Found";
      }

      setConnectionError(errorMessage);
      toast({
        title: toastTitle,
        description: errorMessage,
        variant: "destructive"
      });
    } finally {
      setIsConnecting(false);
    }
  };

  const disconnectWallet = () => {
    setWalletAddress(null);
    setConnectionError(null);
    toast({
      title: "Wallet Disconnected",
      description: "Your wallet has been disconnected.",
    });
  };

  const resetTest = () => {
    setWalletAddress(null);
    setConnectionError(null);
  };

  return (
    <div className="max-w-md mx-auto p-6 bg-gray-900 rounded-lg border border-gray-700">
      <h2 className="text-xl font-bold text-white mb-4 flex items-center gap-2">
        <Wallet className="w-5 h-5 text-gold" />
        Wallet Connection Test
      </h2>
      
      <div className="space-y-4">
        {/* Connection Status */}
        {walletAddress ? (
          <div className="bg-green-500/10 border border-green-500/30 rounded-lg p-4">
            <div className="flex items-center gap-2 mb-2">
              <CheckCircle className="w-4 h-4 text-green-400" />
              <span className="text-green-400 font-semibold">Connected</span>
            </div>
            <p className="text-sm text-gray-300 mb-3">
              {walletAddress.substring(0, 6)}...{walletAddress.substring(walletAddress.length - 4)}
            </p>
            <Button
              onClick={disconnectWallet}
              variant="outline"
              size="sm"
              className="text-red-400 border-red-500/30 hover:bg-red-500/20"
            >
              Disconnect
            </Button>
          </div>
        ) : connectionError ? (
          <div className="bg-red-500/10 border border-red-500/30 rounded-lg p-4">
            <div className="flex items-center gap-2 mb-2">
              <AlertTriangle className="w-4 h-4 text-red-400" />
              <span className="text-red-400 font-semibold">
                {connectionError.includes("cancelled") ? "Connection Cancelled" : "Connection Error"}
              </span>
            </div>
            <p className="text-sm text-red-200 mb-3">{connectionError}</p>
            
            {connectionError.includes("cancelled") && (
              <div className="bg-yellow-500/10 border border-yellow-500/30 rounded p-3 mb-3">
                <p className="text-yellow-200 text-sm">
                  💡 <strong>Tip:</strong> When the SafePal popup appears, click "Connect" or "Approve" to continue.
                </p>
              </div>
            )}
            
            <div className="flex gap-2">
              <Button
                onClick={testWalletConnection}
                disabled={isConnecting}
                className="bg-gold-gradient text-black font-semibold"
              >
                {isConnecting ? (
                  <>
                    <RefreshCw className="w-4 h-4 mr-2 animate-spin" />
                    Connecting...
                  </>
                ) : (
                  "Try Again"
                )}
              </Button>
              <Button
                onClick={resetTest}
                variant="outline"
                size="sm"
                className="text-gray-400 border-gray-600 hover:bg-gray-800"
              >
                Reset
              </Button>
            </div>
          </div>
        ) : (
          <div className="text-center">
            <p className="text-gray-400 text-sm mb-4">
              Test the improved wallet connection experience with better error handling.
            </p>
            <Button
              onClick={testWalletConnection}
              disabled={isConnecting}
              className="w-full bg-gold-gradient text-black font-semibold"
            >
              {isConnecting ? (
                <>
                  <RefreshCw className="w-4 h-4 mr-2 animate-spin" />
                  Connecting...
                </>
              ) : (
                <>
                  <Wallet className="w-4 h-4 mr-2" />
                  Connect SafePal Wallet
                </>
              )}
            </Button>
          </div>
        )}

        {/* Instructions */}
        <div className="bg-blue-500/10 border border-blue-500/30 rounded-lg p-4">
          <h3 className="text-blue-200 font-semibold mb-2">Test Instructions</h3>
          <ol className="text-blue-100 text-sm space-y-1 list-decimal list-inside">
            <li>Click "Connect SafePal Wallet"</li>
            <li>Try rejecting the connection to see improved error handling</li>
            <li>Try connecting again and approve to see success flow</li>
            <li>Notice the helpful tips and clearer error messages</li>
          </ol>
        </div>

        {/* Error Codes Reference */}
        <div className="bg-gray-800/50 border border-gray-600 rounded-lg p-4">
          <h3 className="text-gray-200 font-semibold mb-2">Error Codes Handled</h3>
          <div className="text-gray-300 text-xs space-y-1">
            <div><strong>4001:</strong> User rejected request</div>
            <div><strong>-32002:</strong> Request pending</div>
            <div><strong>-32603:</strong> Internal error (wallet locked)</div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default WalletConnectionTest;

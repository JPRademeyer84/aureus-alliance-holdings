import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

const WalletDebugger: React.FC = () => {
  const [debugInfo, setDebugInfo] = useState<any>(null);
  const [isLoading, setIsLoading] = useState(false);

  const testWalletConnection = async () => {
    setIsLoading(true);
    const info: any = {
      timestamp: new Date().toISOString(),
      ethereum: !!window.ethereum,
      safepal: !!window.safepal,
      safepalProvider: !!window.safepalProvider,
      providers: []
    };

    try {
      if (window.ethereum) {
        // Get basic wallet info
        info.isMetaMask = window.ethereum.isMetaMask;
        info.isSafePal = window.ethereum.isSafePal;
        info.isSafeWallet = window.ethereum.isSafeWallet;
        info.chainId = await window.ethereum.request({ method: 'eth_chainId' });
        info.accounts = await window.ethereum.request({ method: 'eth_accounts' });
        
        // Test basic network call
        try {
          info.blockNumber = await window.ethereum.request({ 
            method: 'eth_blockNumber' 
          });
        } catch (e) {
          info.blockNumberError = e.message;
        }

        // Test USDT balance call if we have an account
        if (info.accounts && info.accounts.length > 0) {
          const account = info.accounts[0];
          const usdtContract = '0xc2132D05D31c914a87C6611C10748AEb04B58e8F'; // Polygon USDT
          const balanceOfData = `0x70a08231000000000000000000000000${account.slice(2)}`;
          
          try {
            info.usdtBalanceRaw = await window.ethereum.request({
              method: 'eth_call',
              params: [{
                to: usdtContract,
                data: balanceOfData
              }, 'latest']
            });
            
            if (info.usdtBalanceRaw && info.usdtBalanceRaw !== '0x') {
              const balanceInt = parseInt(info.usdtBalanceRaw, 16);
              info.usdtBalanceFormatted = (balanceInt / Math.pow(10, 6)).toFixed(6);
            }
          } catch (e) {
            info.usdtBalanceError = e.message;
          }
        }

        // Check if we're on Polygon
        if (info.chainId === '0x89') {
          info.networkName = 'Polygon Mainnet';
          info.isCorrectNetwork = true;
        } else {
          info.networkName = `Unknown (${info.chainId})`;
          info.isCorrectNetwork = false;
          
          // Try to switch to Polygon
          try {
            await window.ethereum.request({
              method: 'wallet_switchEthereumChain',
              params: [{ chainId: '0x89' }],
            });
            info.switchedToPolygon = true;
          } catch (switchError: any) {
            info.switchError = switchError.message;
            
            // If chain doesn't exist, try to add it
            if (switchError.code === 4902) {
              try {
                await window.ethereum.request({
                  method: 'wallet_addEthereumChain',
                  params: [{
                    chainId: '0x89',
                    chainName: 'Polygon Mainnet',
                    nativeCurrency: { name: 'MATIC', symbol: 'MATIC', decimals: 18 },
                    rpcUrls: ['https://polygon-rpc.com/'],
                    blockExplorerUrls: ['https://polygonscan.com/'],
                  }],
                });
                info.addedPolygonNetwork = true;
              } catch (addError: any) {
                info.addNetworkError = addError.message;
              }
            }
          }
        }
      }
    } catch (error: any) {
      info.error = error.message;
    }

    setDebugInfo(info);
    setIsLoading(false);
  };

  return (
    <Card className="bg-gray-800 border-gray-700 max-w-2xl mx-auto">
      <CardHeader>
        <CardTitle className="text-white">Wallet Connection Debugger</CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        <Button 
          onClick={testWalletConnection}
          disabled={isLoading}
          className="bg-gold hover:bg-gold/90 text-black"
        >
          {isLoading ? 'Testing...' : 'Test Wallet Connection'}
        </Button>
        
        {debugInfo && (
          <div className="bg-gray-900 p-4 rounded-lg">
            <pre className="text-xs text-green-400 whitespace-pre-wrap overflow-auto max-h-96">
              {JSON.stringify(debugInfo, null, 2)}
            </pre>
          </div>
        )}
      </CardContent>
    </Card>
  );
};

export default WalletDebugger;

import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import { 
  HelpCircle, 
  Wallet, 
  CheckCircle, 
  AlertTriangle, 
  ExternalLink,
  ChevronDown,
  ChevronUp
} from 'lucide-react';

interface WalletConnectionGuideProps {
  isVisible?: boolean;
  onClose?: () => void;
}

const WalletConnectionGuide: React.FC<WalletConnectionGuideProps> = ({ 
  isVisible = false, 
  onClose 
}) => {
  const [isExpanded, setIsExpanded] = useState(isVisible);

  if (!isExpanded && !isVisible) {
    return (
      <div className="mb-4">
        <Button
          variant="outline"
          size="sm"
          onClick={() => setIsExpanded(true)}
          className="text-gold border-gold/30 hover:bg-gold/10"
        >
          <HelpCircle className="w-4 h-4 mr-2" />
          Need help connecting your wallet?
        </Button>
      </div>
    );
  }

  return (
    <div className="mb-6 bg-blue-500/10 border border-blue-500/30 rounded-lg p-4">
      <div className="flex items-center justify-between mb-3">
        <div className="flex items-center gap-2">
          <Wallet className="w-5 h-5 text-blue-400" />
          <h3 className="font-semibold text-blue-200">Wallet Connection Guide</h3>
        </div>
        <Button
          variant="ghost"
          size="sm"
          onClick={() => {
            setIsExpanded(false);
            onClose?.();
          }}
          className="text-blue-400 hover:text-blue-300"
        >
          {isExpanded ? <ChevronUp className="w-4 h-4" /> : <ChevronDown className="w-4 h-4" />}
        </Button>
      </div>

      <div className="space-y-4 text-sm">
        <div className="bg-green-500/10 border border-green-500/30 rounded p-3">
          <h4 className="font-semibold text-green-200 mb-2 flex items-center gap-2">
            <CheckCircle className="w-4 h-4" />
            How to Connect Successfully
          </h4>
          <ol className="list-decimal list-inside space-y-1 text-green-100">
            <li>Make sure SafePal wallet extension is installed and unlocked</li>
            <li>Click the "Connect Wallet" button below</li>
            <li>When the SafePal popup appears, click "Connect" or "Approve"</li>
            <li>Your wallet address will appear once connected</li>
          </ol>
        </div>

        <div className="bg-yellow-500/10 border border-yellow-500/30 rounded p-3">
          <h4 className="font-semibold text-yellow-200 mb-2 flex items-center gap-2">
            <AlertTriangle className="w-4 h-4" />
            Common Issues & Solutions
          </h4>
          <div className="space-y-2 text-yellow-100">
            <div>
              <strong>Connection Cancelled:</strong> You clicked "Reject" or closed the popup. Simply try connecting again and click "Approve" this time.
            </div>
            <div>
              <strong>No Popup Appeared:</strong> Check if your browser is blocking popups, or if SafePal is installed and unlocked.
            </div>
            <div>
              <strong>Connection Timeout:</strong> Your wallet might be locked. Unlock SafePal and try again.
            </div>
          </div>
        </div>

        <div className="bg-blue-500/10 border border-blue-500/30 rounded p-3">
          <h4 className="font-semibold text-blue-200 mb-2">Don't Have SafePal Wallet?</h4>
          <p className="text-blue-100 mb-3">
            SafePal is a secure, multi-chain wallet that supports all the networks we use. 
            It's free and easy to install.
          </p>
          <Button
            variant="outline"
            size="sm"
            onClick={() => window.open('https://chrome.google.com/webstore/detail/safepal-extension-wallet/lgmpcpglpngdoalbgeoldeajfclnhafa', '_blank')}
            className="text-blue-200 border-blue-400/30 hover:bg-blue-500/20"
          >
            <ExternalLink className="w-4 h-4 mr-2" />
            Install SafePal Wallet
          </Button>
        </div>

        <div className="bg-gray-500/10 border border-gray-500/30 rounded p-3">
          <h4 className="font-semibold text-gray-200 mb-2">Why Do I Need to Connect a Wallet?</h4>
          <p className="text-gray-300 text-sm">
            Your wallet is used to securely send USDT payments for your NFT investments. 
            We never store your private keys - the connection is only used to facilitate 
            the payment transaction when you're ready to invest.
          </p>
        </div>

        <div className="text-center pt-2">
          <p className="text-xs text-gray-400">
            Having trouble? Contact our support team for assistance.
          </p>
        </div>
      </div>
    </div>
  );
};

export default WalletConnectionGuide;

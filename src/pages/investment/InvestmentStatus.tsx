
import React from "react";
import { Loader2 } from "lucide-react";

interface InvestmentStatusProps {
  paymentStatus: 'idle' | 'pending' | 'success' | 'error';
  paymentTxHash: string | null;
  setPaymentStatus: (status: 'idle' | 'pending' | 'success' | 'error') => void;
}

const InvestmentStatus: React.FC<InvestmentStatusProps> = ({
  paymentStatus,
  paymentTxHash,
  setPaymentStatus,
}) => {
  switch (paymentStatus) {
    case 'pending':
      return (
        <div className="p-6 bg-blue-500/20 rounded-lg border border-blue-500/30 my-6 text-center">
          <Loader2 className="h-8 w-8 animate-spin mx-auto mb-3 text-blue-400" />
          <h3 className="text-lg font-semibold text-blue-200 mb-1">Processing Your Investment</h3>
          <p className="text-blue-100 text-sm opacity-80">Please wait while we process your transaction...</p>
        </div>
      );
    case 'success':
      return (
        <div className="p-6 bg-green-500/20 rounded-lg border border-green-500/30 my-6 text-center">
          <div className="w-12 h-12 bg-green-500/30 rounded-full flex items-center justify-center mx-auto mb-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-green-400">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
              <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
          </div>
          <h3 className="text-lg font-semibold text-green-200 mb-1">Investment Successful!</h3>
          <p className="text-green-100 text-sm mb-3">Your investment has been successfully processed.</p>
          {paymentTxHash && (
            <div className="p-2 bg-black/30 rounded font-mono text-xs text-green-300">
              Transaction: {paymentTxHash}
            </div>
          )}
        </div>
      );
    case 'error':
      return (
        <div className="p-6 bg-red-500/20 rounded-lg border border-red-500/30 my-6 text-center">
          <div className="w-12 h-12 bg-red-500/30 rounded-full flex items-center justify-center mx-auto mb-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-red-400">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="15" y1="9" x2="9" y2="15"></line>
              <line x1="9" y1="9" x2="15" y2="15"></line>
            </svg>
          </div>
          <h3 className="text-lg font-semibold text-red-200 mb-1">Investment Failed</h3>
          <p className="text-red-100 text-sm mb-3">There was an error processing your investment.</p>
          <button
            onClick={() => setPaymentStatus('idle')}
            className="px-4 py-2 bg-red-500/20 hover:bg-red-500/30 text-white rounded"
          >
            Try Again
          </button>
        </div>
      );
    default:
      return null;
  }
};

export default InvestmentStatus;

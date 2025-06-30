import React from 'react';
import InvestmentHistoryDebugger from '@/components/debug/InvestmentHistoryDebugger';

const DebugTest: React.FC = () => {
  return (
    <div className="min-h-screen bg-gray-950 p-4">
      <div className="max-w-6xl mx-auto">
        <h1 className="text-2xl font-bold text-white mb-6">Debug Test Page</h1>
        <InvestmentHistoryDebugger />
      </div>
    </div>
  );
};

export default DebugTest;

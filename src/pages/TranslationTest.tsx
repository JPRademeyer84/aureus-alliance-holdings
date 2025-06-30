import React from 'react';
import TranslationDebug from '@/components/TranslationDebug';
import HybridTranslator from '@/components/HybridTranslator';

const TranslationTest: React.FC = () => {
  return (
    <div className="min-h-screen bg-gray-900 py-8 px-4">
      <div className="max-w-4xl mx-auto space-y-8">
        <div className="text-center">
          <h1 className="text-3xl font-bold text-white mb-4">Translation System Test</h1>
          <p className="text-gray-400">Debug and test the translation system</p>
        </div>

        <TranslationDebug />

        <div className="bg-gray-800 border border-gray-700 rounded-lg p-6">
          <h2 className="text-xl font-bold text-white mb-4">Language Selector Test</h2>
          <div className="flex items-center gap-4">
            <span className="text-white">Current Language Selector:</span>
            <HybridTranslator />
          </div>
        </div>

        <div className="bg-gray-800 border border-gray-700 rounded-lg p-6">
          <h2 className="text-xl font-bold text-white mb-4">Sample Content to Translate</h2>
          <div className="space-y-4 text-white">
            <h3 className="text-lg font-semibold">Become an Angel Investor</h3>
            <p>in the Future of Digital Gold</p>
            <div className="flex gap-4">
              <button className="bg-gold text-black px-4 py-2 rounded">Invest Now</button>
              <button className="border border-gold text-gold px-4 py-2 rounded">Learn More</button>
            </div>
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
              <div className="text-center">
                <div className="text-2xl font-bold text-gold">10x</div>
                <div className="text-sm text-gray-400">Yield on Investment</div>
              </div>
              <div className="text-center">
                <div className="text-2xl font-bold text-gold">$89</div>
                <div className="text-sm text-gray-400">Annual per Share</div>
              </div>
              <div className="text-center">
                <div className="text-2xl font-bold text-gold">20%</div>
                <div className="text-sm text-gray-400">Affiliate Commission</div>
              </div>
              <div className="text-center">
                <div className="text-2xl font-bold text-gold">June</div>
                <div className="text-sm text-gray-400">NFT Presale Launch</div>
              </div>
            </div>
          </div>
        </div>

        <div className="bg-gray-800 border border-gray-700 rounded-lg p-6">
          <h2 className="text-xl font-bold text-white mb-4">Instructions</h2>
          <div className="text-gray-300 space-y-2">
            <p>1. Check the database status above</p>
            <p>2. If setup is needed, click "Setup Database"</p>
            <p>3. Test the language selector</p>
            <p>4. Watch the sample content translate</p>
            <p>5. Check browser console for debug messages</p>
          </div>
        </div>
      </div>
    </div>
  );
};

export default TranslationTest;

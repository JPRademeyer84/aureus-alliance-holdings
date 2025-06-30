import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Badge } from '@/components/ui/badge';
import { Calendar, Shield, AlertTriangle, Coins, Clock, FileText } from 'lucide-react';
import Navbar from '@/components/Navbar';
import Footer from '@/components/Footer';

const TermsAndConditions: React.FC = () => {
  return (
    <div className="min-h-screen bg-charcoal">
      <Navbar />
      
      <div className="container mx-auto px-4 py-12">
        <div className="max-w-4xl mx-auto">
          {/* Header */}
          <div className="text-center mb-12">
            <h1 className="text-4xl font-bold font-playfair mb-4">
              <span className="text-gradient">Terms & Conditions</span>
            </h1>
            <p className="text-xl text-gray-300 mb-6">
              Investment Terms for Aureus Angel Alliance Gold Mining Sector
            </p>
            <div className="flex items-center justify-center gap-4 text-sm text-gray-400">
              <div className="flex items-center gap-2">
                <FileText size={16} />
                <span>Version 1.0</span>
              </div>
              <div className="flex items-center gap-2">
                <Calendar size={16} />
                <span>Effective: {new Date().toLocaleDateString()}</span>
              </div>
            </div>
          </div>

          {/* Important Notice */}
          <Card className="bg-red-500/10 border-red-500/30 mb-8">
            <CardContent className="p-6">
              <div className="flex items-start gap-3">
                <AlertTriangle className="h-6 w-6 text-red-400 mt-1 flex-shrink-0" />
                <div>
                  <h3 className="text-red-400 font-semibold text-lg mb-2">Important Investment Notice</h3>
                  <p className="text-red-300 leading-relaxed">
                    By proceeding with your investment, you acknowledge that you understand the risks involved 
                    in gold mining investments and that all investments are subject to market conditions and 
                    regulatory compliance requirements.
                  </p>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Investment Purpose */}
          <Card className="bg-black/40 border-gold/30 mb-8">
            <CardHeader>
              <CardTitle className="flex items-center gap-3 text-gold">
                <Coins className="h-6 w-6" />
                Investment Purpose & Fund Allocation
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <p className="text-gray-300 leading-relaxed">
                Your investment funds will be used exclusively to secure your investment position in the 
                <strong className="text-gold"> Gold Mining Sector</strong> through the pre-purchase of NFT shares 
                before they have been created and minted.
              </p>
              <div className="bg-gold/10 border border-gold/30 rounded-lg p-4">
                <h4 className="font-semibold text-gold mb-2">Fund Utilization:</h4>
                <ul className="space-y-2 text-gray-300">
                  <li>• 70% - Direct gold mining operations and equipment</li>
                  <li>• 15% - NFT development and smart contract deployment</li>
                  <li>• 10% - Operational costs and regulatory compliance</li>
                  <li>• 5% - Platform development and maintenance</li>
                </ul>
              </div>
            </CardContent>
          </Card>

          {/* NFT Shares Understanding */}
          <Card className="bg-black/40 border-gold/30 mb-8">
            <CardHeader>
              <CardTitle className="flex items-center gap-3 text-gold">
                <Shield className="h-6 w-6" />
                NFT Shares & Digital Assets
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <p className="text-gray-300 leading-relaxed">
                You understand that you are purchasing rights to future NFT shares that represent ownership 
                stakes in gold mining operations. These NFTs are currently in development and will be minted 
                upon completion of the development phase.
              </p>
              <div className="grid md:grid-cols-2 gap-4">
                <div className="bg-blue-500/10 border border-blue-500/30 rounded-lg p-4">
                  <h4 className="font-semibold text-blue-400 mb-2">What You're Purchasing:</h4>
                  <ul className="space-y-1 text-gray-300 text-sm">
                    <li>• Future NFT mining shares</li>
                    <li>• Dividend rights from mining operations</li>
                    <li>• Tradeable digital assets on OpenSea</li>
                    <li>• Voting rights in mining decisions</li>
                  </ul>
                </div>
                <div className="bg-purple-500/10 border border-purple-500/30 rounded-lg p-4">
                  <h4 className="font-semibold text-purple-400 mb-2">Development Status:</h4>
                  <ul className="space-y-1 text-gray-300 text-sm">
                    <li>• Smart contracts in development</li>
                    <li>• NFT artwork being created</li>
                    <li>• Mining operations being established</li>
                    <li>• Regulatory approvals in progress</li>
                  </ul>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Delivery Timeline */}
          <Card className="bg-black/40 border-gold/30 mb-8">
            <CardHeader>
              <CardTitle className="flex items-center gap-3 text-gold">
                <Clock className="h-6 w-6" />
                Delivery Timeline & Schedule
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="bg-orange-500/10 border border-orange-500/30 rounded-lg p-4">
                <h4 className="font-semibold text-orange-400 mb-3">180-Day Development Period</h4>
                <p className="text-gray-300 mb-4">
                  You acknowledge and understand that it will take <strong>180 days (6 months)</strong> from 
                  your investment date before you will receive your mine shares and NFT assets.
                </p>
                <div className="space-y-2">
                  <div className="flex justify-between items-center">
                    <span className="text-gray-400">Investment Date:</span>
                    <Badge variant="outline">Today</Badge>
                  </div>
                  <div className="flex justify-between items-center">
                    <span className="text-gray-400">NFT Delivery:</span>
                    <Badge variant="outline">180 Days</Badge>
                  </div>
                  <div className="flex justify-between items-center">
                    <span className="text-gray-400">Mining Shares Active:</span>
                    <Badge variant="outline">180 Days</Badge>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Dividend Timeline */}
          <Card className="bg-black/40 border-gold/30 mb-8">
            <CardHeader>
              <CardTitle className="flex items-center gap-3 text-gold">
                <Calendar className="h-6 w-6" />
                Dividend Payment Schedule
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="bg-green-500/10 border border-green-500/30 rounded-lg p-4">
                <h4 className="font-semibold text-green-400 mb-3">First Dividend Payout</h4>
                <p className="text-gray-300 mb-4">
                  The first dividend payout from mining operations will occur at the 
                  <strong className="text-green-400"> end of Q1 2026</strong> (March 31, 2026).
                </p>
                <div className="grid md:grid-cols-2 gap-4">
                  <div>
                    <h5 className="font-medium text-green-400 mb-2">Payout Schedule:</h5>
                    <ul className="space-y-1 text-gray-300 text-sm">
                      <li>• Q1 2026: First dividend payment</li>
                      <li>• Quarterly thereafter</li>
                      <li>• Based on mining profitability</li>
                      <li>• Paid in USDT to your wallet</li>
                    </ul>
                  </div>
                  <div>
                    <h5 className="font-medium text-green-400 mb-2">Factors Affecting Payouts:</h5>
                    <ul className="space-y-1 text-gray-300 text-sm">
                      <li>• Gold market prices</li>
                      <li>• Mining operational costs</li>
                      <li>• Regulatory compliance costs</li>
                      <li>• Equipment maintenance</li>
                    </ul>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Risk Acknowledgment */}
          <Card className="bg-black/40 border-red-500/30 mb-8">
            <CardHeader>
              <CardTitle className="flex items-center gap-3 text-red-400">
                <AlertTriangle className="h-6 w-6" />
                Risk Acknowledgment & Disclaimers
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="bg-red-500/10 border border-red-500/30 rounded-lg p-4">
                <h4 className="font-semibold text-red-400 mb-3">Investment Risks</h4>
                <ul className="space-y-2 text-gray-300">
                  <li>• <strong>Market Risk:</strong> Gold prices and mining profitability can fluctuate</li>
                  <li>• <strong>Operational Risk:</strong> Mining operations may face technical challenges</li>
                  <li>• <strong>Regulatory Risk:</strong> Changes in mining regulations may affect operations</li>
                  <li>• <strong>Technology Risk:</strong> NFT and blockchain technology risks</li>
                  <li>• <strong>Liquidity Risk:</strong> NFT shares may not always be easily tradeable</li>
                  <li>• <strong>Development Risk:</strong> Project development may face delays</li>
                </ul>
              </div>
              <div className="bg-yellow-500/10 border border-yellow-500/30 rounded-lg p-4">
                <h4 className="font-semibold text-yellow-400 mb-2">No Guarantee of Returns</h4>
                <p className="text-gray-300 text-sm">
                  Past performance does not guarantee future results. All investments carry risk of loss. 
                  Dividend payments are subject to mining profitability and are not guaranteed.
                </p>
              </div>
            </CardContent>
          </Card>

          <Separator className="my-8" />

          {/* Footer */}
          <div className="text-center text-gray-400 text-sm">
            <p className="mb-2">
              By proceeding with your investment, you acknowledge that you have read, understood, 
              and agree to all terms and conditions outlined above.
            </p>
            <p>
              For questions regarding these terms, please contact our support team through the live chat system.
            </p>
          </div>
        </div>
      </div>
      
      <Footer />
    </div>
  );
};

export default TermsAndConditions;

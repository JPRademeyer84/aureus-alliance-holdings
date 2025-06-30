import React, { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { ST as T } from "@/components/SimpleTranslator";
import {
  ChevronRight,
  ChevronDown,
  ExternalLink,
  Copy,
  CheckCircle,
  AlertTriangle,
  Info,
  Smartphone,
  Monitor,
  CreditCard,
  Shield,
  Clock,
  DollarSign
} from "lucide-react";

interface GuideStep {
  id: string;
  title: string;
  description: string;
  image?: string;
  tips?: string[];
  warning?: string;
}

interface ExchangeGuide {
  name: string;
  icon: string;
  color: string;
  steps: GuideStep[];
}

const ManualPaymentGuide: React.FC = () => {
  const [selectedExchange, setSelectedExchange] = useState<string>('binance');
  const [expandedStep, setExpandedStep] = useState<string>('');

  const exchangeGuides: Record<string, ExchangeGuide> = {
    binance: {
      name: 'Binance',
      icon: '🟡',
      color: 'bg-yellow-500',
      steps: [
        {
          id: 'login',
          title: 'Login to Binance',
          description: 'Open the Binance app or website and log into your account',
          tips: [
            'Make sure you have USDT in your spot wallet',
            'Enable 2FA for additional security',
            'Use the official Binance app or website only'
          ]
        },
        {
          id: 'navigate',
          title: 'Navigate to Withdraw',
          description: 'Go to Wallet → Spot → Withdraw, then select USDT',
          tips: [
            'You can also use the search function to find "Withdraw"',
            'Make sure you select the correct USDT token'
          ]
        },
        {
          id: 'select-network',
          title: 'Select Network',
          description: 'Choose the blockchain network (BSC, Ethereum, Polygon, or Tron)',
          tips: [
            'BSC (BEP-20) has the lowest fees',
            'Ethereum (ERC-20) is most secure but has higher fees',
            'Make sure to match the network shown in your payment instructions'
          ],
          warning: 'Using the wrong network will result in lost funds!'
        },
        {
          id: 'enter-address',
          title: 'Enter Wallet Address',
          description: 'Copy and paste the company wallet address from your payment instructions',
          tips: [
            'Double-check the address character by character',
            'Use copy-paste to avoid typing errors',
            'Some exchanges allow you to save addresses for future use'
          ],
          warning: 'Never type the address manually - always copy and paste!'
        },
        {
          id: 'enter-amount',
          title: 'Enter Amount',
          description: 'Enter the exact USDT amount shown in your payment instructions',
          tips: [
            'Enter the exact amount - do not round up or down',
            'Check the network fee and ensure you have enough balance',
            'The amount should match exactly what\'s shown in your purchase'
          ]
        },
        {
          id: 'review-submit',
          title: 'Review and Submit',
          description: 'Review all details carefully, then submit the withdrawal',
          tips: [
            'Take a screenshot before submitting',
            'Save the transaction hash/ID for your records',
            'The transaction may take 5-30 minutes to confirm'
          ]
        },
        {
          id: 'upload-proof',
          title: 'Upload Payment Proof',
          description: 'Return to Aureus Alliance and upload your transaction screenshot',
          tips: [
            'Include the transaction hash in the screenshot',
            'Make sure the amount and address are visible',
            'You can also upload the transaction receipt from Binance'
          ]
        }
      ]
    },
    coinbase: {
      name: 'Coinbase',
      icon: '🔵',
      color: 'bg-blue-500',
      steps: [
        {
          id: 'login',
          title: 'Login to Coinbase',
          description: 'Open Coinbase Pro or regular Coinbase and log in',
          tips: [
            'Coinbase Pro has lower fees for withdrawals',
            'Make sure you have USDT or USD Coin available'
          ]
        },
        {
          id: 'portfolio',
          title: 'Go to Portfolio',
          description: 'Navigate to your portfolio and select USDT or USDC',
          tips: [
            'If you only have USD, you\'ll need to buy USDT first',
            'USDC can also be used as it\'s equivalent to USDT'
          ]
        },
        {
          id: 'send',
          title: 'Click Send',
          description: 'Click the Send button for your USDT/USDC',
          tips: [
            'Make sure you have enough balance including network fees',
            'Check the current network fees before proceeding'
          ]
        },
        {
          id: 'enter-details',
          title: 'Enter Transfer Details',
          description: 'Enter the company wallet address and amount',
          tips: [
            'Coinbase will automatically detect the network',
            'Double-check the address before confirming',
            'Enter the exact amount from your payment instructions'
          ]
        },
        {
          id: 'confirm',
          title: 'Confirm Transaction',
          description: 'Review and confirm the transaction',
          tips: [
            'Take a screenshot of the confirmation screen',
            'Note down the transaction hash',
            'Transactions usually confirm within 10-20 minutes'
          ]
        }
      ]
    },
    kucoin: {
      name: 'KuCoin',
      icon: '🟢',
      color: 'bg-green-500',
      steps: [
        {
          id: 'login',
          title: 'Login to KuCoin',
          description: 'Access your KuCoin account via app or website',
          tips: [
            'Ensure you have USDT in your Main Account',
            'If funds are in Trading Account, transfer to Main Account first'
          ]
        },
        {
          id: 'assets',
          title: 'Go to Assets',
          description: 'Navigate to Assets → Main Account → Withdraw',
          tips: [
            'You can also use the quick withdraw feature',
            'Make sure you\'re in the Main Account, not Trading Account'
          ]
        },
        {
          id: 'select-usdt',
          title: 'Select USDT',
          description: 'Find and select USDT from your asset list',
          tips: [
            'Use the search function if you have many assets',
            'Check your available balance before proceeding'
          ]
        },
        {
          id: 'withdrawal-details',
          title: 'Enter Withdrawal Details',
          description: 'Select network, enter address and amount',
          tips: [
            'Choose the network that matches your payment instructions',
            'TRC-20 (Tron) usually has the lowest fees',
            'ERC-20 (Ethereum) is more secure but expensive'
          ]
        },
        {
          id: 'security-verification',
          title: 'Complete Security Verification',
          description: 'Complete 2FA, email, or SMS verification as required',
          tips: [
            'Have your 2FA device ready',
            'Check your email for verification codes',
            'This step is required for security'
          ]
        }
      ]
    }
  };

  const generalTips = [
    {
      icon: Shield,
      title: 'Security First',
      description: 'Always verify wallet addresses and use official exchange apps/websites only'
    },
    {
      icon: Clock,
      title: 'Processing Time',
      description: 'Transactions typically take 5-30 minutes to confirm depending on network congestion'
    },
    {
      icon: DollarSign,
      title: 'Exact Amount',
      description: 'Send the exact amount shown in your payment instructions to avoid delays'
    },
    {
      icon: Smartphone,
      title: 'Keep Records',
      description: 'Always take screenshots and save transaction hashes for your records'
    }
  ];

  const networkInfo = [
    {
      name: 'Binance Smart Chain (BSC)',
      symbol: 'BEP-20',
      fees: 'Low (~$0.50)',
      time: '3-5 minutes',
      color: 'bg-yellow-500'
    },
    {
      name: 'Ethereum',
      symbol: 'ERC-20',
      fees: 'High ($5-50)',
      time: '5-15 minutes',
      color: 'bg-blue-500'
    },
    {
      name: 'Polygon',
      symbol: 'Polygon',
      fees: 'Very Low (~$0.01)',
      time: '2-5 minutes',
      color: 'bg-purple-500'
    },
    {
      name: 'Tron',
      symbol: 'TRC-20',
      fees: 'Very Low (~$1)',
      time: '3-5 minutes',
      color: 'bg-red-500'
    }
  ];

  const currentGuide = exchangeGuides[selectedExchange];

  return (
    <div className="space-y-6">
      {/* Header */}
      <Card className="bg-gradient-to-r from-gold/20 to-gold/10 border-gold/30">
        <CardHeader>
          <CardTitle className="text-white text-2xl flex items-center gap-3">
            <CreditCard className="h-8 w-8" />
            Manual Payment Guide
          </CardTitle>
          <p className="text-gray-300">
            Step-by-step instructions for sending USDT from popular exchanges to complete your investment
          </p>
        </CardHeader>
      </Card>

      {/* Important Notice */}
      <Card className="bg-blue-500/10 border-blue-500/30">
        <CardContent className="p-4">
          <div className="flex items-start gap-3">
            <Info className="h-5 w-5 text-blue-400 mt-0.5 flex-shrink-0" />
            <div className="text-sm text-blue-200">
              <p className="font-medium mb-2">Before You Start:</p>
              <ul className="space-y-1 text-blue-300">
                <li>• Make sure you have enough USDT plus network fees in your exchange account</li>
                <li>• Double-check the wallet address and network before sending</li>
                <li>• Keep screenshots and transaction hashes for verification</li>
                <li>• Contact support if you need help at any step</li>
              </ul>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Exchange Selection */}
      <Card className="bg-gray-800/50 border-gray-700">
        <CardHeader>
          <CardTitle className="text-white">Select Your Exchange</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            {Object.entries(exchangeGuides).map(([key, guide]) => (
              <Button
                key={key}
                variant={selectedExchange === key ? 'default' : 'outline'}
                onClick={() => setSelectedExchange(key)}
                className={`p-4 h-auto flex items-center gap-3 ${
                  selectedExchange === key 
                    ? 'bg-gold text-black' 
                    : 'border-gray-600 text-white hover:bg-gray-700'
                }`}
              >
                <span className="text-2xl">{guide.icon}</span>
                <span className="font-medium">{guide.name}</span>
              </Button>
            ))}
          </div>
        </CardContent>
      </Card>

      {/* Network Information */}
      <Card className="bg-gray-800/50 border-gray-700">
        <CardHeader>
          <CardTitle className="text-white">Network Options & Fees</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {networkInfo.map((network) => (
              <div key={network.name} className="p-4 bg-gray-700/50 rounded-lg border border-gray-600">
                <div className="flex items-center gap-2 mb-2">
                  <div className={`w-3 h-3 rounded-full ${network.color}`}></div>
                  <span className="text-white font-medium">{network.symbol}</span>
                </div>
                <div className="text-sm text-gray-300 space-y-1">
                  <div>Network: {network.name}</div>
                  <div>Fees: {network.fees}</div>
                  <div>Time: {network.time}</div>
                </div>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>

      {/* Step-by-Step Guide */}
      <Card className="bg-gray-800/50 border-gray-700">
        <CardHeader>
          <CardTitle className="text-white flex items-center gap-3">
            <span className="text-2xl">{currentGuide.icon}</span>
            {currentGuide.name} Withdrawal Guide
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {currentGuide.steps.map((step, index) => (
              <div key={step.id} className="border border-gray-600 rounded-lg">
                <button
                  onClick={() => setExpandedStep(expandedStep === step.id ? '' : step.id)}
                  className="w-full p-4 flex items-center justify-between text-left hover:bg-gray-700/50 transition-colors"
                >
                  <div className="flex items-center gap-3">
                    <div className="w-8 h-8 rounded-full bg-gold text-black flex items-center justify-center font-bold">
                      {index + 1}
                    </div>
                    <div>
                      <div className="text-white font-medium">{step.title}</div>
                      <div className="text-gray-400 text-sm">{step.description}</div>
                    </div>
                  </div>
                  {expandedStep === step.id ? (
                    <ChevronDown className="h-5 w-5 text-gray-400" />
                  ) : (
                    <ChevronRight className="h-5 w-5 text-gray-400" />
                  )}
                </button>
                
                {expandedStep === step.id && (
                  <div className="px-4 pb-4 border-t border-gray-600">
                    {step.warning && (
                      <div className="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-lg">
                        <div className="flex items-center gap-2 text-red-400">
                          <AlertTriangle className="h-4 w-4" />
                          <span className="font-medium">Warning:</span>
                        </div>
                        <p className="text-red-300 text-sm mt-1">{step.warning}</p>
                      </div>
                    )}
                    
                    {step.tips && (
                      <div className="space-y-2">
                        <h4 className="text-white font-medium">Tips:</h4>
                        <ul className="space-y-1">
                          {step.tips.map((tip, tipIndex) => (
                            <li key={tipIndex} className="text-gray-300 text-sm flex items-start gap-2">
                              <CheckCircle className="h-4 w-4 text-green-400 mt-0.5 flex-shrink-0" />
                              {tip}
                            </li>
                          ))}
                        </ul>
                      </div>
                    )}
                  </div>
                )}
              </div>
            ))}
          </div>
        </CardContent>
      </Card>

      {/* General Tips */}
      <Card className="bg-gray-800/50 border-gray-700">
        <CardHeader>
          <CardTitle className="text-white">General Tips & Best Practices</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {generalTips.map((tip, index) => (
              <div key={index} className="flex items-start gap-3 p-4 bg-gray-700/50 rounded-lg">
                <tip.icon className="h-5 w-5 text-gold mt-0.5 flex-shrink-0" />
                <div>
                  <div className="text-white font-medium">{tip.title}</div>
                  <div className="text-gray-300 text-sm mt-1">{tip.description}</div>
                </div>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>

      {/* Support */}
      <Card className="bg-green-500/10 border-green-500/30">
        <CardContent className="p-4">
          <div className="flex items-start gap-3">
            <CheckCircle className="h-5 w-5 text-green-400 mt-0.5 flex-shrink-0" />
            <div className="text-sm text-green-200">
              <p className="font-medium mb-2">Need Help?</p>
              <p className="text-green-300">
                If you encounter any issues during the payment process, our support team is here to help. 
                Contact us through the live chat or email support@aureusalliance.com with your payment details.
              </p>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
};

export default ManualPaymentGuide;

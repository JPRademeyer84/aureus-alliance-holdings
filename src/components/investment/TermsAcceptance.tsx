import React, { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { ExternalLink, FileText, AlertTriangle, Clock, Calendar, Coins, Shield } from 'lucide-react';
import { Link } from 'react-router-dom';

interface TermsAcceptanceProps {
  onAcceptanceChange: (allAccepted: boolean, acceptanceData: TermsAcceptanceData) => void;
  isRequired?: boolean;
}

export interface TermsAcceptanceData {
  goldMiningInvestmentAccepted: boolean;
  nftSharesUnderstandingAccepted: boolean;
  deliveryTimelineAccepted: boolean;
  dividendTimelineAccepted: boolean;
  riskAcknowledgmentAccepted: boolean;
  acceptanceTimestamp: string;
  termsVersion: string;
}

const TermsAcceptance: React.FC<TermsAcceptanceProps> = ({ 
  onAcceptanceChange, 
  isRequired = true 
}) => {
  const [acceptanceState, setAcceptanceState] = useState<TermsAcceptanceData>({
    goldMiningInvestmentAccepted: false,
    nftSharesUnderstandingAccepted: false,
    deliveryTimelineAccepted: false,
    dividendTimelineAccepted: false,
    riskAcknowledgmentAccepted: false,
    acceptanceTimestamp: '',
    termsVersion: '1.0'
  });

  const handleCheckboxChange = (field: keyof TermsAcceptanceData, checked: boolean) => {
    const newState = {
      ...acceptanceState,
      [field]: checked,
      acceptanceTimestamp: new Date().toISOString()
    };

    setAcceptanceState(newState);

    const allAccepted =
      newState.goldMiningInvestmentAccepted &&
      newState.nftSharesUnderstandingAccepted &&
      newState.deliveryTimelineAccepted &&
      newState.dividendTimelineAccepted &&
      newState.riskAcknowledgmentAccepted;

    // Add safety check to ensure onAcceptanceChange is a function
    if (typeof onAcceptanceChange === 'function') {
      onAcceptanceChange(allAccepted, newState);
    } else {
      console.error('TermsAcceptance: onAcceptanceChange prop is not a function');
    }
  };

  const allTermsAccepted = 
    acceptanceState.goldMiningInvestmentAccepted &&
    acceptanceState.nftSharesUnderstandingAccepted &&
    acceptanceState.deliveryTimelineAccepted &&
    acceptanceState.dividendTimelineAccepted &&
    acceptanceState.riskAcknowledgmentAccepted;

  return (
    <Card className="bg-black/40 border-gold/30">
      <CardHeader>
        <CardTitle className="flex items-center gap-3 text-gold">
          <FileText className="h-5 w-5" />
          Terms & Conditions Acceptance
          {isRequired && <Badge variant="destructive" className="ml-2">Required</Badge>}
        </CardTitle>
        <div className="flex items-center gap-4 text-sm text-gray-400">
          <span>Version 1.0</span>
          <Link 
            to="/terms-and-conditions" 
            target="_blank"
            className="flex items-center gap-1 text-gold hover:text-gold/80 transition-colors"
          >
            <ExternalLink size={14} />
            Read Full Terms
          </Link>
        </div>
      </CardHeader>
      <CardContent className="space-y-6">
        
        {/* Gold Mining Investment */}
        <div className="flex items-start space-x-3">
          <Checkbox
            id="gold-mining"
            checked={acceptanceState.goldMiningInvestmentAccepted}
            onCheckedChange={(checked) => 
              handleCheckboxChange('goldMiningInvestmentAccepted', checked as boolean)
            }
            className="mt-1"
          />
          <div className="flex-1">
            <label htmlFor="gold-mining" className="flex items-start gap-2 cursor-pointer">
              <Coins className="h-4 w-4 text-gold mt-0.5 flex-shrink-0" />
              <div>
                <p className="text-white font-medium">Gold Mining Investment Understanding</p>
                <p className="text-gray-400 text-sm mt-1">
                  I understand that my funds will be used to secure my investment in the Gold Mining Sector 
                  through pre-purchasing NFT shares before they have been created.
                </p>
              </div>
            </label>
          </div>
        </div>

        {/* NFT Shares Understanding */}
        <div className="flex items-start space-x-3">
          <Checkbox
            id="nft-shares"
            checked={acceptanceState.nftSharesUnderstandingAccepted}
            onCheckedChange={(checked) => 
              handleCheckboxChange('nftSharesUnderstandingAccepted', checked as boolean)
            }
            className="mt-1"
          />
          <div className="flex-1">
            <label htmlFor="nft-shares" className="flex items-start gap-2 cursor-pointer">
              <Shield className="h-4 w-4 text-blue-400 mt-0.5 flex-shrink-0" />
              <div>
                <p className="text-white font-medium">NFT Shares Development</p>
                <p className="text-gray-400 text-sm mt-1">
                  I understand that the NFT shares I am purchasing are currently in development and will be 
                  minted and delivered upon completion of the development phase.
                </p>
              </div>
            </label>
          </div>
        </div>

        {/* 180-Day Delivery Timeline */}
        <div className="flex items-start space-x-3">
          <Checkbox
            id="delivery-timeline"
            checked={acceptanceState.deliveryTimelineAccepted}
            onCheckedChange={(checked) => 
              handleCheckboxChange('deliveryTimelineAccepted', checked as boolean)
            }
            className="mt-1"
          />
          <div className="flex-1">
            <label htmlFor="delivery-timeline" className="flex items-start gap-2 cursor-pointer">
              <Clock className="h-4 w-4 text-orange-400 mt-0.5 flex-shrink-0" />
              <div>
                <p className="text-white font-medium">180-Day Delivery Timeline</p>
                <p className="text-gray-400 text-sm mt-1">
                  I understand and accept that it will take <strong className="text-orange-400">180 days (6 months)</strong> 
                  from my investment date before I will receive my mine shares and NFT assets.
                </p>
              </div>
            </label>
          </div>
        </div>

        {/* Dividend Timeline */}
        <div className="flex items-start space-x-3">
          <Checkbox
            id="dividend-timeline"
            checked={acceptanceState.dividendTimelineAccepted}
            onCheckedChange={(checked) => 
              handleCheckboxChange('dividendTimelineAccepted', checked as boolean)
            }
            className="mt-1"
          />
          <div className="flex-1">
            <label htmlFor="dividend-timeline" className="flex items-start gap-2 cursor-pointer">
              <Calendar className="h-4 w-4 text-green-400 mt-0.5 flex-shrink-0" />
              <div>
                <p className="text-white font-medium">First Dividend Payout - Q1 2026</p>
                <p className="text-gray-400 text-sm mt-1">
                  I understand that the first dividend payout from mining operations will be at the 
                  <strong className="text-green-400"> end of Q1 2026 (March 31, 2026)</strong>, with quarterly payments thereafter.
                </p>
              </div>
            </label>
          </div>
        </div>

        {/* Risk Acknowledgment */}
        <div className="flex items-start space-x-3">
          <Checkbox
            id="risk-acknowledgment"
            checked={acceptanceState.riskAcknowledgmentAccepted}
            onCheckedChange={(checked) => 
              handleCheckboxChange('riskAcknowledgmentAccepted', checked as boolean)
            }
            className="mt-1"
          />
          <div className="flex-1">
            <label htmlFor="risk-acknowledgment" className="flex items-start gap-2 cursor-pointer">
              <AlertTriangle className="h-4 w-4 text-red-400 mt-0.5 flex-shrink-0" />
              <div>
                <p className="text-white font-medium">Risk Acknowledgment</p>
                <p className="text-gray-400 text-sm mt-1">
                  I acknowledge and understand the risks involved in gold mining investments, including market volatility, 
                  operational risks, and that dividend payments are not guaranteed and depend on mining profitability.
                </p>
              </div>
            </label>
          </div>
        </div>

        {/* Acceptance Status */}
        <div className="pt-4 border-t border-gray-700">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2">
              <span className="text-sm text-gray-400">Acceptance Status:</span>
              <Badge 
                variant={allTermsAccepted ? "default" : "secondary"}
                className={allTermsAccepted ? "bg-green-500/20 text-green-400 border-green-500/30" : ""}
              >
                {allTermsAccepted ? "All Terms Accepted" : `${Object.values(acceptanceState).filter(v => v === true).length - 2}/5 Accepted`}
              </Badge>
            </div>
            {allTermsAccepted && (
              <div className="text-xs text-green-400">
                ✓ Ready to proceed with investment
              </div>
            )}
          </div>
          
          {!allTermsAccepted && isRequired && (
            <div className="mt-3 p-3 bg-red-500/10 border border-red-500/30 rounded-lg">
              <div className="flex items-center gap-2 text-red-400 text-sm">
                <AlertTriangle size={16} />
                <span>You must accept all terms and conditions to proceed with your investment.</span>
              </div>
            </div>
          )}
        </div>
      </CardContent>
    </Card>
  );
};

export default TermsAcceptance;

import React, { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { useSimpleTranslation as useTranslation, ST as T } from '@/components/SimpleTranslator';
import { 
  Play, 
  Pause, 
  RotateCcw, 
  Package, 
  Wallet, 
  Clock, 
  Gift, 
  TrendingUp,
  Users,
  CheckCircle,
  ArrowRight,
  Info,
  DollarSign,
  Calendar
} from 'lucide-react';

interface InvestmentGuideProps {
  onNavigate: (tab: string) => void;
}

const InvestmentGuide: React.FC<InvestmentGuideProps> = ({ onNavigate }) => {
  const { translate } = useTranslation();
  const [currentStep, setCurrentStep] = useState(0);
  const [isPlaying, setIsPlaying] = useState(false);

  const steps = [
    {
      title: translate('choose_participation_amount', 'Choose Your Participation Amount'),
      description: translate('select_multiple_packages', 'Select one or multiple packages to match your budget. Mix and match any combination!'),
      icon: Package,
      color: "text-blue-400",
      bgColor: "bg-blue-400/10",
      action: translate('view_packages', 'View Packages'),
      actionTab: "packages",
      details: [
        translate('eight_mining_packages', '8 mining packages: Shovel ($25) to Aureus ($1,000)'),
        translate('daily_reward_range', 'Daily reward from 1.7% to 5% for 180 days'),
        translate('includes_nft_shares', 'Each package includes NFT mining shares'),
        translate('higher_packages_better_rewards', 'Higher packages = better daily reward rates')
      ]
    },
    {
      title: translate('connect_safepal_wallet', 'Connect Your SafePal Wallet'),
      description: translate('secure_usdt_payments', 'Use your SafePal wallet to make secure USDT payments. All transactions are recorded on blockchain.'),
      icon: Wallet,
      color: "text-green-400",
      bgColor: "bg-green-400/10",
      action: translate('connect_wallet', 'Connect Wallet'),
      actionTab: "packages",
      details: [
        translate('only_safepal_supported', 'Only SafePal wallet supported for security'),
        translate('pay_usdt_multiple_chains', 'Pay with USDT on multiple chains'),
        translate('instant_transaction_confirmation', 'Instant transaction confirmation'),
        translate('blockchain_transparency', 'Blockchain transparency and security')
      ]
    },
    {
      title: translate('track_180_day_countdown', 'Track Your 180-Day Countdown'),
      description: translate('watch_nft_delivery_countdown', 'Watch your NFT delivery countdown in real-time. Your digital gold shares are being prepared!'),
      icon: Clock,
      color: "text-orange-400",
      bgColor: "bg-orange-400/10",
      action: translate('view_countdown', 'View Countdown'),
      actionTab: "countdown",
      details: [
        translate('180_day_reward_period', '180-day reward earning period'),
        translate('daily_reward_payments', 'Daily reward payments (1.7% to 5%)'),
        translate('real_time_earnings_tracking', 'Real-time earnings tracking'),
        translate('total_reward_range', 'Total Reward: 306% to 900% over 180 days')
      ]
    },
    {
      title: translate('earn_referral_commissions', 'Earn Referral Commissions'),
      description: translate('share_unique_link', 'Share your unique link and earn 12% USDT + 12% NFT bonuses on every referral investment.'),
      icon: Users,
      color: "text-purple-400",
      bgColor: "bg-purple-400/10",
      action: translate('start_referring', 'Start Referring'),
      actionTab: "affiliate",
      details: [
        translate('level_1_commission', 'Level 1: 12% USDT + 12% NFT bonuses'),
        translate('level_2_commission', 'Level 2: 5% USDT + 5% NFT bonuses'),
        translate('level_3_commission', 'Level 3: 3% USDT + 3% NFT bonuses'),
        translate('instant_commission_payouts', 'Instant commission payouts')
      ]
    },
    {
      title: translate('receive_nfts_rewards', 'Receive Your NFTs & Rewards'),
      description: translate('after_180_days_receive', 'After 180 days, receive your digital gold NFT shares plus your guaranteed reward amount.'),
      icon: Gift,
      color: "text-gold",
      bgColor: "bg-gold/10",
      action: translate('view_portfolio', 'View Portfolio'),
      actionTab: "portfolio",
      details: [
        translate('nft_mining_shares_range', 'NFT mining shares (5 to 200 shares)'),
        translate('total_reward_amount_range', 'Total Reward: $76.50 to $9,000 per package'),
        translate('tradeable_gold_certificates', 'Tradeable digital gold certificates'),
        translate('polygon_ownership_proof', 'Polygon blockchain ownership proof')
      ]
    },
    {
      title: translate('enjoy_quarterly_dividends', 'Enjoy Quarterly Dividends'),
      description: translate('starting_q1_2026', 'Starting Q1 2026, receive quarterly dividend payments from real gold mining operations.'),
      icon: TrendingUp,
      color: "text-green-500",
      bgColor: "bg-green-500/10",
      action: translate('view_earnings', 'View Earnings'),
      actionTab: "portfolio",
      details: [
        translate('quarterly_payments_q1_2026', 'Quarterly payments starting Q1 2026'),
        translate('based_gold_mining_profits', 'Based on actual gold mining profits'),
        translate('paid_directly_wallet', 'Paid directly to your wallet'),
        translate('lifetime_passive_income', 'Lifetime passive income stream')
      ]
    }
  ];

  const nextStep = () => {
    setCurrentStep((prev) => (prev + 1) % steps.length);
  };

  const prevStep = () => {
    setCurrentStep((prev) => (prev - 1 + steps.length) % steps.length);
  };

  const resetGuide = () => {
    setCurrentStep(0);
    setIsPlaying(false);
  };

  const toggleAutoPlay = () => {
    setIsPlaying(!isPlaying);
  };

  // Auto-advance when playing
  React.useEffect(() => {
    if (isPlaying) {
      const interval = setInterval(nextStep, 4000);
      return () => clearInterval(interval);
    }
  }, [isPlaying]);

  const currentStepData = steps[currentStep];

  return (
    <Card className="bg-gray-800 border-gray-700">
      <CardHeader>
        <div className="flex items-center justify-between">
          <CardTitle className="text-white flex items-center">
            <Info className="h-5 w-5 mr-2 text-gold" />
            <T k="how_investment_journey_works" fallback="How Your Investment Journey Works" />
          </CardTitle>
          <div className="flex items-center space-x-2">
            <Button
              variant="ghost"
              size="sm"
              onClick={toggleAutoPlay}
              className="text-gray-400 hover:text-white"
            >
              {isPlaying ? <Pause className="h-4 w-4" /> : <Play className="h-4 w-4" />}
            </Button>
            <Button
              variant="ghost"
              size="sm"
              onClick={resetGuide}
              className="text-gray-400 hover:text-white"
            >
              <RotateCcw className="h-4 w-4" />
            </Button>
          </div>
        </div>
      </CardHeader>
      <CardContent className="space-y-6">
        {/* Progress Indicator */}
        <div className="flex items-center justify-between mb-6">
          {steps.map((_, index) => (
            <div key={index} className="flex items-center">
              <div
                className={`w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-all duration-300 ${
                  index === currentStep
                    ? 'bg-gold text-black'
                    : index < currentStep
                    ? 'bg-green-500 text-white'
                    : 'bg-gray-600 text-gray-400'
                }`}
              >
                {index < currentStep ? (
                  <CheckCircle className="h-4 w-4" />
                ) : (
                  index + 1
                )}
              </div>
              {index < steps.length - 1 && (
                <div
                  className={`h-1 w-8 mx-2 transition-all duration-300 ${
                    index < currentStep ? 'bg-green-500' : 'bg-gray-600'
                  }`}
                />
              )}
            </div>
          ))}
        </div>

        {/* Current Step Content */}
        <div className="bg-gray-700/50 rounded-lg p-6">
          <div className="flex items-start space-x-4">
            <div className={`${currentStepData.bgColor} rounded-full p-3`}>
              <currentStepData.icon className={`w-6 h-6 ${currentStepData.color}`} />
            </div>
            <div className="flex-1">
              <div className="flex items-center space-x-2 mb-2">
                <Badge className="bg-gold/20 text-gold">
                  <T k="step" fallback="Step" /> {currentStep + 1}
                </Badge>
                <h3 className="text-xl font-semibold text-white">
                  {currentStepData.title}
                </h3>
              </div>
              <p className="text-gray-300 mb-4">
                {currentStepData.description}
              </p>
              
              {/* Step Details */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                {currentStepData.details.map((detail, index) => (
                  <div key={index} className="flex items-center space-x-2">
                    <CheckCircle className="h-4 w-4 text-green-400 flex-shrink-0" />
                    <span className="text-sm text-gray-300">{detail}</span>
                  </div>
                ))}
              </div>

              {/* Action Button */}
              <Button
                onClick={() => onNavigate(currentStepData.actionTab)}
                className="bg-gold-gradient text-black hover:opacity-90"
              >
                {currentStepData.action}
                <ArrowRight className="ml-2 h-4 w-4" />
              </Button>
            </div>
          </div>
        </div>

        {/* Navigation Controls */}
        <div className="flex items-center justify-between">
          <Button
            variant="outline"
            onClick={prevStep}
            disabled={currentStep === 0}
            className="border-gray-600 text-gray-300 hover:bg-gray-700"
          >
            <T k="previous" fallback="Previous" />
          </Button>

          <div className="text-center">
            <span className="text-sm text-gray-400">
              {currentStep + 1} <T k="of" fallback="of" /> {steps.length}
            </span>
          </div>

          <Button
            variant="outline"
            onClick={nextStep}
            disabled={currentStep === steps.length - 1}
            className="border-gray-600 text-gray-300 hover:bg-gray-700"
          >
            <T k="next" fallback="Next" />
          </Button>
        </div>

        {/* Quick Stats */}
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 pt-4 border-t border-gray-700">
          <div className="text-center">
            <DollarSign className="h-6 w-6 text-gold mx-auto mb-1" />
            <div className="text-sm text-gray-400">
              <T k="min_participation" fallback="Min Participation" />
            </div>
            <div className="font-semibold text-white">$25</div>
          </div>
          <div className="text-center">
            <Calendar className="h-6 w-6 text-blue-400 mx-auto mb-1" />
            <div className="text-sm text-gray-400">
              <T k="roi_period" fallback="ROI Period" />
            </div>
            <div className="font-semibold text-white">
              <T k="days_180" fallback="180 Days" />
            </div>
          </div>
          <div className="text-center">
            <TrendingUp className="h-6 w-6 text-green-400 mx-auto mb-1" />
            <div className="text-sm text-gray-400">
              <T k="commission_rate" fallback="Commission Rate" />
            </div>
            <div className="font-semibold text-white">20%</div>
          </div>
          <div className="text-center">
            <Gift className="h-6 w-6 text-purple-400 mx-auto mb-1" />
            <div className="text-sm text-gray-400">
              <T k="total_packages" fallback="Total Packages" />
            </div>
            <div className="font-semibold text-white">
              <T k="mining_8" fallback="8 Mining" />
            </div>
          </div>
        </div>
      </CardContent>
    </Card>
  );
};

export default InvestmentGuide;

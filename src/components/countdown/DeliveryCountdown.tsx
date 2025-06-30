import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';
import { 
  Clock, 
  Gift, 
  TrendingUp, 
  CheckCircle, 
  AlertCircle,
  Calendar,
  Timer,
  Sparkles,
  DollarSign
} from 'lucide-react';

interface CountdownData {
  nft_days_remaining: number;
  roi_days_remaining: number;
  nft_hours_remaining: number;
  roi_hours_remaining: number;
  nft_countdown_status: 'pending' | 'soon' | 'ready' | 'delivered';
  roi_countdown_status: 'pending' | 'soon' | 'ready' | 'delivered';
  nft_delivery_date: string;
  roi_delivery_date: string;
  nft_delivered: boolean;
  roi_delivered: boolean;
}

interface DeliveryCountdownProps {
  investmentId: string;
  packageName: string;
  amount: number;
  roi: number;
  shares: number;
  purchaseDate: string;
  countdownData: CountdownData;
  compact?: boolean;
}

interface TimeRemaining {
  days: number;
  hours: number;
  minutes: number;
  seconds: number;
}

const DeliveryCountdown: React.FC<DeliveryCountdownProps> = ({
  investmentId,
  packageName,
  amount,
  roi,
  shares,
  purchaseDate,
  countdownData,
  compact = false
}) => {
  const [nftTimeRemaining, setNftTimeRemaining] = useState<TimeRemaining>({ days: 0, hours: 0, minutes: 0, seconds: 0 });
  const [roiTimeRemaining, setRoiTimeRemaining] = useState<TimeRemaining>({ days: 0, hours: 0, minutes: 0, seconds: 0 });

  useEffect(() => {
    const updateCountdown = () => {
      const now = new Date().getTime();

      // NFT Countdown
      if (countdownData?.nft_delivery_date && !countdownData?.nft_delivered) {
        const nftDeliveryTime = new Date(countdownData.nft_delivery_date).getTime();
        const nftDifference = nftDeliveryTime - now;

        if (nftDifference > 0) {
          setNftTimeRemaining({
            days: Math.floor(nftDifference / (1000 * 60 * 60 * 24)),
            hours: Math.floor((nftDifference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)),
            minutes: Math.floor((nftDifference % (1000 * 60 * 60)) / (1000 * 60)),
            seconds: Math.floor((nftDifference % (1000 * 60)) / 1000)
          });
        } else {
          setNftTimeRemaining({ days: 0, hours: 0, minutes: 0, seconds: 0 });
        }
      }

      // Reward Countdown
      if (countdownData?.roi_delivery_date && !countdownData?.roi_delivered) {
        const rewardDeliveryTime = new Date(countdownData.roi_delivery_date).getTime();
        const rewardDifference = rewardDeliveryTime - now;

        if (rewardDifference > 0) {
          setRoiTimeRemaining({
            days: Math.floor(rewardDifference / (1000 * 60 * 60 * 24)),
            hours: Math.floor((rewardDifference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)),
            minutes: Math.floor((rewardDifference % (1000 * 60 * 60)) / (1000 * 60)),
            seconds: Math.floor((rewardDifference % (1000 * 60)) / 1000)
          });
        } else {
          setRoiTimeRemaining({ days: 0, hours: 0, minutes: 0, seconds: 0 });
        }
      }
    };

    updateCountdown();
    const interval = setInterval(updateCountdown, 1000);

    return () => clearInterval(interval);
  }, [countdownData]);

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'delivered': return 'bg-green-500/20 text-green-400 border-green-500/30';
      case 'ready': return 'bg-blue-500/20 text-blue-400 border-blue-500/30';
      case 'soon': return 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30';
      default: return 'bg-gray-500/20 text-gray-400 border-gray-500/30';
    }
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'delivered': return <CheckCircle className="h-4 w-4" />;
      case 'ready': return <Gift className="h-4 w-4" />;
      case 'soon': return <AlertCircle className="h-4 w-4" />;
      default: return <Clock className="h-4 w-4" />;
    }
  };

  const getProgressPercentage = (daysRemaining: number) => {
    const totalDays = 180;
    const daysPassed = totalDays - (daysRemaining || 0);
    return Math.max(0, Math.min(100, (daysPassed / totalDays) * 100));
  };

  const formatTimeUnit = (value: number, unit: string) => {
    return (
      <div className="text-center">
        <div className="text-2xl font-bold text-white">{value.toString().padStart(2, '0')}</div>
        <div className="text-xs text-gray-400 uppercase">{unit}</div>
      </div>
    );
  };

  if (compact) {
    return (
      <div className="space-y-2">
        {/* NFT Countdown - Compact */}
        <div className="flex items-center justify-between p-3 bg-gray-800 rounded-lg border border-gray-700">
          <div className="flex items-center gap-2">
            <Sparkles className="h-4 w-4 text-purple-400" />
            <span className="text-sm text-white">NFT Delivery</span>
          </div>
          <div className="flex items-center gap-2">
            {countdownData?.nft_delivered ? (
              <Badge className={getStatusColor('delivered')}>
                {getStatusIcon('delivered')}
                <span className="ml-1">Delivered</span>
              </Badge>
            ) : (
              <>
                <span className="text-sm text-gray-300">
                  {nftTimeRemaining.days}d {nftTimeRemaining.hours}h
                </span>
                <Badge className={getStatusColor(countdownData?.nft_countdown_status || 'pending')}>
                  {getStatusIcon(countdownData?.nft_countdown_status || 'pending')}
                </Badge>
              </>
            )}
          </div>
        </div>

        {/* Reward Countdown - Compact */}
        <div className="flex items-center justify-between p-3 bg-gray-800 rounded-lg border border-gray-700">
          <div className="flex items-center gap-2">
            <TrendingUp className="h-4 w-4 text-green-400" />
            <span className="text-sm text-white">Reward Delivery</span>
          </div>
          <div className="flex items-center gap-2">
            {countdownData?.roi_delivered ? (
              <Badge className={getStatusColor('delivered')}>
                {getStatusIcon('delivered')}
                <span className="ml-1">Delivered</span>
              </Badge>
            ) : (
              <>
                <span className="text-sm text-gray-300">
                  {roiTimeRemaining.days}d {roiTimeRemaining.hours}h
                </span>
                <Badge className={getStatusColor(countdownData?.roi_countdown_status || 'pending')}>
                  {getStatusIcon(countdownData?.roi_countdown_status || 'pending')}
                </Badge>
              </>
            )}
          </div>
        </div>
      </div>
    );
  }

  return (
    <Card className="bg-gray-800 border-gray-700">
      <CardHeader>
        <CardTitle className="text-white flex items-center gap-2">
          <Timer className="h-5 w-5 text-gold" />
          Delivery Countdown - {packageName}
        </CardTitle>
        <div className="text-sm text-gray-400">
          Investment: ${(amount || 0).toLocaleString()} • Expected ROI: ${(roi || 0).toLocaleString()} • Shares: {shares || 0}
        </div>
      </CardHeader>
      <CardContent className="space-y-6">
        {/* NFT Delivery Countdown */}
        <div className="space-y-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2">
              <Sparkles className="h-5 w-5 text-purple-400" />
              <h3 className="text-lg font-semibold text-white">NFT Delivery</h3>
            </div>
            <Badge className={getStatusColor(countdownData?.nft_countdown_status || 'pending')}>
              {getStatusIcon(countdownData?.nft_countdown_status || 'pending')}
              <span className="ml-1 capitalize">{countdownData?.nft_countdown_status || 'pending'}</span>
            </Badge>
          </div>

          {countdownData?.nft_delivered ? (
            <div className="text-center py-4">
              <CheckCircle className="h-12 w-12 text-green-400 mx-auto mb-2" />
              <p className="text-green-400 font-medium">NFT Delivered!</p>
              <p className="text-sm text-gray-400">Your NFT has been successfully delivered to your wallet</p>
            </div>
          ) : (
            <>
              <div className="grid grid-cols-4 gap-4 text-center">
                {formatTimeUnit(nftTimeRemaining.days, 'Days')}
                {formatTimeUnit(nftTimeRemaining.hours, 'Hours')}
                {formatTimeUnit(nftTimeRemaining.minutes, 'Minutes')}
                {formatTimeUnit(nftTimeRemaining.seconds, 'Seconds')}
              </div>
              
              <div className="space-y-2">
                <div className="flex justify-between text-sm">
                  <span className="text-gray-400">Progress</span>
                  <span className="text-white">{getProgressPercentage(countdownData?.nft_days_remaining || 0).toFixed(1)}%</span>
                </div>
                <Progress
                  value={getProgressPercentage(countdownData?.nft_days_remaining || 0)}
                  className="h-2 bg-gray-700"
                />
              </div>

              <div className="text-center text-sm text-gray-400">
                <Calendar className="h-4 w-4 inline mr-1" />
                Delivery Date: {countdownData?.nft_delivery_date ? new Date(countdownData.nft_delivery_date).toLocaleDateString() : 'TBD'}
              </div>
            </>
          )}
        </div>

        {/* ROI Delivery Countdown */}
        <div className="space-y-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2">
              <DollarSign className="h-5 w-5 text-green-400" />
              <h3 className="text-lg font-semibold text-white">ROI Delivery</h3>
            </div>
            <Badge className={getStatusColor(countdownData?.roi_countdown_status || 'pending')}>
              {getStatusIcon(countdownData?.roi_countdown_status || 'pending')}
              <span className="ml-1 capitalize">{countdownData?.roi_countdown_status || 'pending'}</span>
            </Badge>
          </div>

          {countdownData?.roi_delivered ? (
            <div className="text-center py-4">
              <CheckCircle className="h-12 w-12 text-green-400 mx-auto mb-2" />
              <p className="text-green-400 font-medium">Reward Delivered!</p>
              <p className="text-sm text-gray-400">Your reward of ${(roi || 0).toLocaleString()} has been delivered</p>
            </div>
          ) : (
            <>
              <div className="grid grid-cols-4 gap-4 text-center">
                {formatTimeUnit(roiTimeRemaining.days, 'Days')}
                {formatTimeUnit(roiTimeRemaining.hours, 'Hours')}
                {formatTimeUnit(roiTimeRemaining.minutes, 'Minutes')}
                {formatTimeUnit(roiTimeRemaining.seconds, 'Seconds')}
              </div>
              
              <div className="space-y-2">
                <div className="flex justify-between text-sm">
                  <span className="text-gray-400">Progress</span>
                  <span className="text-white">{getProgressPercentage(countdownData?.roi_days_remaining || 0).toFixed(1)}%</span>
                </div>
                <Progress
                  value={getProgressPercentage(countdownData?.roi_days_remaining || 0)}
                  className="h-2 bg-gray-700"
                />
              </div>

              <div className="text-center text-sm text-gray-400">
                <Calendar className="h-4 w-4 inline mr-1" />
                Delivery Date: {countdownData?.roi_delivery_date ? new Date(countdownData.roi_delivery_date).toLocaleDateString() : 'TBD'}
              </div>

              <div className="bg-green-500/10 border border-green-500/30 rounded-lg p-3">
                <div className="flex items-center gap-2 text-green-400">
                  <TrendingUp className="h-4 w-4" />
                  <span className="font-medium">Expected ROI: ${(roi || 0).toLocaleString()}</span>
                </div>
                <p className="text-green-300 text-sm mt-1">
                  Your return on investment will be delivered automatically when the countdown reaches zero.
                </p>
              </div>
            </>
          )}
        </div>

        {/* Purchase Info */}
        <div className="border-t border-gray-700 pt-4">
          <div className="text-sm text-gray-400">
            <div className="flex justify-between">
              <span>Purchase Date:</span>
              <span>{new Date(purchaseDate).toLocaleDateString()}</span>
            </div>
            <div className="flex justify-between mt-1">
              <span>Investment ID:</span>
              <span className="font-mono">{investmentId.slice(0, 8)}...{investmentId.slice(-4)}</span>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>
  );
};

export default DeliveryCountdown;

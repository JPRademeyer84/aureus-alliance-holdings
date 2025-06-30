import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { useToast } from '@/hooks/use-toast';
import { 
  Clock, 
  RefreshCw, 
  Package, 
  TrendingUp,
  Calendar,
  Timer,
  Sparkles,
  DollarSign,
  CheckCircle,
  AlertCircle
} from 'lucide-react';
import { useUser } from '@/contexts/UserContext';
import ApiConfig from '@/config/api';
import DeliveryCountdown from '@/components/countdown/DeliveryCountdown';

interface ParticipationCountdown {
  id: string;
  user_id: string;
  package_name: string;
  amount: number;
  shares: number;
  reward: number;
  status: string;
  created_at: string;
  nft_delivery_date: string;
  reward_delivery_date: string;
  delivery_status: string;
  nft_delivered: boolean;
  reward_delivered: boolean;
  nft_days_remaining: number;
  reward_days_remaining: number;
  nft_hours_remaining: number;
  reward_hours_remaining: number;
  nft_countdown_status: 'pending' | 'soon' | 'ready' | 'delivered';
  reward_countdown_status: 'pending' | 'soon' | 'ready' | 'delivered';
}

interface CountdownSummary {
  total_participations: number;
  pending_nft_deliveries: number;
  pending_reward_deliveries: number;
  ready_nft_deliveries: number;
  ready_reward_deliveries: number;
  completed_deliveries: number;
}

const ParticipationCountdownList: React.FC = () => {
  const { user } = useUser();
  const { toast } = useToast();
  const [countdowns, setCountdowns] = useState<ParticipationCountdown[]>([]);
  const [summary, setSummary] = useState<CountdownSummary | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [lastUpdated, setLastUpdated] = useState<Date>(new Date());

  useEffect(() => {
    if (user?.id) {
      fetchCountdowns();
      const interval = setInterval(fetchCountdowns, 60000); // Update every minute
      return () => clearInterval(interval);
    }
  }, [user?.id]);

  const fetchCountdowns = async () => {
    if (!user?.id) {
      console.log('No user ID available for countdown fetch');
      return;
    }

    setIsLoading(true);
    try {
      // Try countdown API first, fallback to participations API
      let response;
      let data;

      try {
        // Check if the countdown endpoint exists in ApiConfig
        if (!ApiConfig?.endpoints?.investments?.countdown) {
          throw new Error('Countdown API endpoint not configured');
        }

        response = await fetch(`${ApiConfig.endpoints.investments.countdown}?action=get_user_countdowns&user_id=${user.id}`, {
          credentials: 'include' // Include session cookies
        });

        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        data = await response.json();

        if (data?.success) {
          setCountdowns(data.data?.countdowns || []);
          setSummary(data.data?.summary || null);
          setLastUpdated(new Date());
          return; // Success, exit early
        } else {
          throw new Error(data?.message || 'Failed to fetch countdowns');
        }
      } catch (countdownError) {
        console.warn('Countdown API failed, trying fallback:', countdownError);

        // Fallback to participations API
        try {
          response = await fetch(`http://localhost/aureus-angel-alliance/api/participations/user-history.php?user_id=${user.id}`, {
            credentials: 'include'
          });

          if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
          }

          data = await response.json();

          if (data?.success) {
            // Transform participation data to countdown format
            const participations = data.data?.participations || [];
            const transformedCountdowns = participations.map((p: any) => ({
              id: p.id,
              package_name: p.package_name || 'Unknown Package',
              amount: p.amount || 0,
              shares: p.shares_purchased || 0,
              reward: (p.amount || 0) * 10, // Calculate reward as 10x amount
              created_at: p.created_at,
              // Calculate 180-day countdown
              nft_delivery_date: new Date(new Date(p.created_at).getTime() + 180 * 24 * 60 * 60 * 1000).toISOString(),
              roi_delivery_date: new Date(new Date(p.created_at).getTime() + 180 * 24 * 60 * 60 * 1000).toISOString(),
              nft_days_remaining: Math.max(0, Math.ceil((new Date(new Date(p.created_at).getTime() + 180 * 24 * 60 * 60 * 1000).getTime() - new Date().getTime()) / (24 * 60 * 60 * 1000))),
              roi_days_remaining: Math.max(0, Math.ceil((new Date(new Date(p.created_at).getTime() + 180 * 24 * 60 * 60 * 1000).getTime() - new Date().getTime()) / (24 * 60 * 60 * 1000))),
              nft_hours_remaining: Math.max(0, Math.ceil((new Date(new Date(p.created_at).getTime() + 180 * 24 * 60 * 60 * 1000).getTime() - new Date().getTime()) / (60 * 60 * 1000))),
              roi_hours_remaining: Math.max(0, Math.ceil((new Date(new Date(p.created_at).getTime() + 180 * 24 * 60 * 60 * 1000).getTime() - new Date().getTime()) / (60 * 60 * 1000))),
              nft_countdown_status: 'pending',
              roi_countdown_status: 'pending',
              nft_delivered: false,
              roi_delivered: false
            }));

            setCountdowns(transformedCountdowns);
            setSummary({
              total_investments: transformedCountdowns.length,
              pending_nft_deliveries: transformedCountdowns.length,
              pending_roi_deliveries: transformedCountdowns.length,
              ready_nft_deliveries: 0,
              ready_roi_deliveries: 0,
              completed_deliveries: 0
            });
            setLastUpdated(new Date());
            return; // Success with fallback
          } else {
            throw new Error(data?.message || 'Failed to fetch participation data');
          }
        } catch (fallbackError) {
          // Both APIs failed, throw the original error
          throw countdownError;
        }
      }
    } catch (error) {
      console.error('Failed to fetch countdowns:', {
        error: error,
        message: error?.message,
        stack: error?.stack,
        endpoint: ApiConfig?.endpoints?.investments?.countdown,
        userId: user?.id
      });

      // Set empty data on error to prevent crashes
      setCountdowns([]);
      setSummary(null);

      // Only show toast if it's not a configuration error
      if (error?.message !== 'Countdown API endpoint not configured') {
        toast({
          title: "Error",
          description: `Failed to load countdown data: ${error?.message || 'Unknown error'}`,
          variant: "destructive"
        });
      }
    } finally {
      setIsLoading(false);
    }
  };

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
      case 'ready': return <Package className="h-4 w-4" />;
      case 'soon': return <AlertCircle className="h-4 w-4" />;
      default: return <Clock className="h-4 w-4" />;
    }
  };

  if (isLoading) {
    return (
      <div className="space-y-6">
        <div className="text-center py-12">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-gold mx-auto mb-4"></div>
          <p className="text-gray-400">Loading countdown data...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-white">Investment Countdowns</h1>
          <p className="text-gray-400">Track your NFT and ROI delivery schedules</p>
        </div>
        <div className="flex items-center gap-2">
          <span className="text-xs text-gray-400">
            Last updated: {lastUpdated.toLocaleTimeString()}
          </span>
          <Button
            size="sm"
            variant="ghost"
            onClick={fetchCountdowns}
            className="h-8 w-8 p-0"
          >
            <RefreshCw className="h-4 w-4" />
          </Button>
        </div>
      </div>

      {/* Summary Cards */}
      {summary && (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <Card className="bg-gray-800 border-gray-700">
            <CardContent className="p-4">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-gray-400">Total Investments</p>
                  <p className="text-2xl font-bold text-white">{summary.total_investments}</p>
                </div>
                <Package className="w-8 h-8 text-blue-400" />
              </div>
            </CardContent>
          </Card>

          <Card className="bg-gray-800 border-gray-700">
            <CardContent className="p-4">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-gray-400">Ready for Delivery</p>
                  <p className="text-2xl font-bold text-green-400">
                    {summary.ready_nft_deliveries + summary.ready_roi_deliveries}
                  </p>
                </div>
                <CheckCircle className="w-8 h-8 text-green-400" />
              </div>
            </CardContent>
          </Card>

          <Card className="bg-gray-800 border-gray-700">
            <CardContent className="p-4">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-gray-400">Completed</p>
                  <p className="text-2xl font-bold text-gold">{summary.completed_deliveries}</p>
                </div>
                <Sparkles className="w-8 h-8 text-gold" />
              </div>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Countdown List */}
      {countdowns.length === 0 ? (
        <Card className="bg-gray-800 border-gray-700">
          <CardContent className="text-center py-12">
            <Timer className="h-12 w-12 text-gray-400 mx-auto mb-4" />
            <h3 className="text-lg font-medium text-white mb-2">No Active Countdowns</h3>
            <p className="text-gray-400 mb-4">
              Make a participation to start your 180-day countdown to NFT and reward delivery.
            </p>
            <p className="text-xs text-gray-500">
              Note: Countdown data requires completed investments with confirmed transactions.
            </p>
          </CardContent>
        </Card>
      ) : (
        <div className="space-y-6">
          {countdowns.map((countdown) => (
            <DeliveryCountdown
              key={countdown.id}
              investmentId={countdown.id}
              packageName={countdown.package_name}
              amount={countdown.amount}
              roi={countdown.reward}
              shares={countdown.shares}
              purchaseDate={countdown.created_at}
              countdownData={{
                nft_days_remaining: countdown.nft_days_remaining,
                roi_days_remaining: countdown.roi_days_remaining,
                nft_hours_remaining: countdown.nft_hours_remaining,
                roi_hours_remaining: countdown.roi_hours_remaining,
                nft_countdown_status: countdown.nft_countdown_status,
                roi_countdown_status: countdown.roi_countdown_status,
                nft_delivery_date: countdown.nft_delivery_date,
                roi_delivery_date: countdown.roi_delivery_date,
                nft_delivered: countdown.nft_delivered,
                roi_delivered: countdown.roi_delivered
              }}
            />
          ))}
        </div>
      )}

      {/* Info Card */}
      <Card className="bg-blue-500/10 border-blue-500/30">
        <CardContent className="p-4">
          <div className="flex items-start gap-3">
            <Calendar className="h-5 w-5 text-blue-400 mt-0.5" />
            <div>
              <h4 className="text-blue-400 font-medium mb-1">12-Month NFT Countdown</h4>
              <p className="text-blue-300 text-sm">
                Your NFT certificate will be automatically delivered after the 12-month countdown period.
                Track the countdown here and receive notifications as delivery dates approach.
              </p>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
};

export default ParticipationCountdownList;

// Legacy export for backward compatibility
export { ParticipationCountdownList as InvestmentCountdownList };

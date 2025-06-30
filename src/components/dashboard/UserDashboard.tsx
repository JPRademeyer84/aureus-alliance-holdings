import React, { useState, useEffect } from 'react';
import { useUser } from '@/contexts/UserContext';
import { useInvestmentPackages } from '@/hooks/useInvestmentPackages';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { useSimpleTranslation as useTranslation, ST as T } from '@/components/SimpleTranslator';
import InvestmentGuide from './InvestmentGuide';
import CertificatesView from './CertificatesView';
// Ultra-safe icons using only emojis to prevent ANY SVG errors
const SafeIcon = ({ emoji, className }: { emoji: string; className?: string }) => (
  <span className={className} style={{ fontSize: '1.2em' }}>{emoji}</span>
);

// All icons as safe emojis
const Users = ({ className }: { className?: string }) => <SafeIcon emoji="👥" className={className} />;
const Package = ({ className }: { className?: string }) => <SafeIcon emoji="📦" className={className} />;
const TrendingUp = ({ className }: { className?: string }) => <SafeIcon emoji="📈" className={className} />;
const DollarSign = ({ className }: { className?: string }) => <SafeIcon emoji="💰" className={className} />;
const Activity = ({ className }: { className?: string }) => <SafeIcon emoji="📊" className={className} />;
const AlertCircle = ({ className }: { className?: string }) => <SafeIcon emoji="⚠️" className={className} />;
const CheckCircle = ({ className }: { className?: string }) => <SafeIcon emoji="✅" className={className} />;
const Clock = ({ className }: { className?: string }) => <SafeIcon emoji="🕐" className={className} />;
const Mail = ({ className }: { className?: string }) => <SafeIcon emoji="📧" className={className} />;
const MessageCircle = ({ className }: { className?: string }) => <SafeIcon emoji="💬" className={className} />;
const Wallet = ({ className }: { className?: string }) => <SafeIcon emoji="👛" className={className} />;
const Eye = ({ className }: { className?: string }) => <SafeIcon emoji="👁️" className={className} />;
const Plus = ({ className }: { className?: string }) => <SafeIcon emoji="➕" className={className} />;
const Star = ({ className }: { className?: string }) => <SafeIcon emoji="⭐" className={className} />;
const Target = ({ className }: { className?: string }) => <SafeIcon emoji="🎯" className={className} />;
const User = ({ className }: { className?: string }) => <SafeIcon emoji="👤" className={className} />;
const Trophy = ({ className }: { className?: string }) => <SafeIcon emoji="🏆" className={className} />;
const Timer = ({ className }: { className?: string }) => <SafeIcon emoji="⏱️" className={className} />;
const BarChart3 = ({ className }: { className?: string }) => <SafeIcon emoji="📊" className={className} />;
const PieChart = ({ className }: { className?: string }) => <SafeIcon emoji="🥧" className={className} />;

interface DashboardStats {
  investments: {
    total: number;
    active: number;
    completed: number;
    total_value: number;
    total_reward: number;
  };
  portfolio: {
    total_shares: number;
    expected_dividends: number;
    next_dividend_date: string;
  };
  commissions: {
    total_usdt_earned: number;
    total_nft_earned: number;
    available_usdt_balance: number;
    available_nft_balance: number;
    total_withdrawals: number;
    pending_withdrawals: number;
  };
  activity: {
    recent_transactions: number;
    pending_transactions: number;
    last_activity: string;
  };
}

interface QuickAction {
  title: string;
  description: string;
  icon: React.ReactNode;
  action: () => void;
  color: string;
  badge?: string;
}

interface UserDashboardProps {
  onNavigate: (section: string) => void;
}

const UserDashboard: React.FC<UserDashboardProps> = ({ onNavigate }) => {
  const { user } = useUser();
  const { packages } = useInvestmentPackages();
  const { translate } = useTranslation();
  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  const fetchDashboardStats = async () => {
    if (!user?.id) return;

    setIsLoading(true);
    try {
      // Fetch real investment data from working API endpoint
      const investmentResponse = await fetch('http://localhost/aureus-angel-alliance/get-my-investments.php?user_id=1', {
        method: 'GET'
      });

      // Fetch commission data from working API endpoint
      const commissionResponse = await fetch('http://localhost/aureus-angel-alliance/get-commission-balance.php?user_id=1', {
        method: 'GET'
      });

      if (!investmentResponse.ok) {
        throw new Error('Failed to fetch investment data');
      }

      const investmentData = await investmentResponse.json();
      let commissionData = null;

      // Commission data is optional (user might not have commissions yet)
      if (commissionResponse.ok) {
        commissionData = await commissionResponse.json();
      }

      if (investmentData.success) {
        const { investments } = investmentData;

        // Calculate active vs completed investments
        const activeInvestments = investments.filter((inv: any) =>
          inv.status === 'pending' || inv.deliveryStatus === 'pending'
        ).length;

        const completedInvestments = investments.filter((inv: any) =>
          inv.status === 'completed' && inv.deliveryStatus === 'completed'
        ).length;

        // Calculate total shares from investments
        const totalShares = investments.reduce((sum: number, inv: any) => sum + (inv.shares || 0), 0);

        // Calculate expected annual dividends (estimated 10% of total shares value)
        const expectedDividends = Math.round(totalShares * 10 * 0.1); // $10 per share * 10% dividend

        // Get most recent investment date for activity
        const lastActivity = investments.length > 0
          ? investments[0].createdAt
          : new Date().toISOString();

        // Process commission data
        let commissionStats = {
          total_usdt_earned: 0,
          total_nft_earned: 0,
          available_usdt_balance: 0,
          available_nft_balance: 0,
          total_withdrawals: 0,
          pending_withdrawals: 0
        };

        if (commissionData && commissionData.success) {
          const { balance } = commissionData;
          commissionStats = {
            total_usdt_earned: balance.total_usdt_earned || 0,
            total_nft_earned: balance.total_nft_earned || 0,
            available_usdt_balance: balance.available_usdt_balance || 0,
            available_nft_balance: balance.available_nft_balance || 0,
            total_withdrawals: 0, // Will be fetched separately if needed
            pending_withdrawals: 0 // Will be fetched separately if needed
          };
        }

        // Calculate totals from investments array
        const totalInvestments = investments.length;
        const totalInvested = investments.reduce((sum: number, inv: any) => sum + (inv.amount || 0), 0);
        const totalROI = investments.reduce((sum: number, inv: any) => sum + (inv.reward || 0), 0);

        setStats({
          investments: {
            total: totalInvestments,
            active: activeInvestments,
            completed: completedInvestments,
            total_value: totalInvested,
            total_reward: totalROI
          },
          portfolio: {
            total_shares: totalShares,
            expected_dividends: expectedDividends,
            next_dividend_date: '2026-03-15' // Q1 2026 as per business plan
          },
          commissions: commissionStats,
          activity: {
            recent_transactions: totalInvestments,
            pending_transactions: activeInvestments,
            last_activity: lastActivity
          }
        });
      } else {
        throw new Error(investmentData.error || 'Failed to fetch investment data');
      }
    } catch (error) {
      console.error('Failed to fetch dashboard stats:', error);
      // Set empty stats on error (no mock data)
      setStats({
        investments: { total: 0, active: 0, completed: 0, total_value: 0, total_reward: 0 },
        portfolio: { total_shares: 0, expected_dividends: 0, next_dividend_date: '2026-03-15' },
        commissions: { total_usdt_earned: 0, total_nft_earned: 0, available_usdt_balance: 0, available_nft_balance: 0, total_withdrawals: 0, pending_withdrawals: 0 },
        activity: { recent_transactions: 0, pending_transactions: 0, last_activity: new Date().toISOString() }
      });
    } finally {
      setIsLoading(false);
    }
  };

  const quickActions: QuickAction[] = [
    {
      title: translate('commission_wallet', 'Commission Wallet'),
      description: translate('manage_referral_earnings', 'Manage your referral earnings and withdrawals'),
      icon: <Wallet className="h-5 w-5" />,
      action: () => onNavigate('commissions'),
      color: 'bg-green-600',
      badge: 'NEW'
    },
    {
      title: translate('affiliate_program', 'Affiliate Program'),
      description: translate('grow_network_earn_commissions', 'Grow your network and earn commissions'),
      icon: <Users className="h-5 w-5" />,
      action: () => onNavigate('affiliate'),
      color: 'bg-purple-600'
    },
    {
      title: translate('browse_packages', 'Browse Packages'),
      description: translate('explore_available_opportunities', 'Explore available funding opportunities'),
      icon: <Package className="h-5 w-5" />,
      action: () => onNavigate('packages'),
      color: 'bg-blue-500'
    },
    {
      title: translate('funding_history', 'Funding History'),
      description: translate('view_past_current_funding', 'View your past and current funding'),
      icon: <BarChart3 className="h-5 w-5" />,
      action: () => onNavigate('history'),
      color: 'bg-green-500'
    },
    {
      title: translate('delivery_countdown', 'Delivery Countdown'),
      description: translate('track_nft_roi_delivery_180', 'Track NFT & ROI delivery (180 days)'),
      icon: <Timer className="h-5 w-5" />,
      action: () => onNavigate('countdown'),
      color: 'bg-orange-500',
      badge: '180d'
    },
    {
      title: translate('portfolio_overview', 'Portfolio Overview'),
      description: translate('check_portfolio_performance', 'Check your portfolio performance'),
      icon: <PieChart className="h-5 w-5" />,
      action: () => onNavigate('portfolio'),
      color: 'bg-purple-500'
    },
    {
      title: translate('gold_diggers_club', 'Gold Diggers Club'),
      description: translate('compete_250k_bonus_pool', 'Compete for $250K bonus pool'),
      icon: <Trophy className="h-5 w-5" />,
      action: () => onNavigate('leaderboard'),
      color: 'bg-gold',
      badge: '$250K'
    },
    {
      title: translate('contact_support', 'Contact Support'),
      description: translate('get_help_support_team', 'Get help from our support team'),
      icon: <MessageCircle className="h-5 w-5" />,
      action: () => onNavigate('support'),
      color: 'bg-orange-500'
    },
    {
      title: translate('wallet_connection', 'Wallet Connection'),
      description: translate('connect_manage_wallets', 'Connect and manage your wallets'),
      icon: <Wallet className="h-5 w-5" />,
      action: () => onNavigate('wallet'),
      color: 'bg-yellow-500'
    }
  ];

  useEffect(() => {
    fetchDashboardStats();
  }, [user?.id]);

  if (isLoading) {
    return (
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <h2 className="text-2xl font-bold text-white">
            <T k="dashboard" fallback="Dashboard" />
          </h2>
        </div>
        <div className="text-center py-8">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-gold mx-auto"></div>
          <p className="text-gray-400 mt-2">
            <T k="loading_dashboard" fallback="Loading dashboard..." />
          </p>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Welcome Section */}
      <div className="bg-gradient-to-r from-gold to-yellow-600 rounded-lg p-6 text-black shadow-lg">
        <div className="flex items-center justify-between">
          <div>
            <h2 className="text-2xl font-bold mb-2">
              {translate('welcome_back_user', 'Welcome back, {username}!').replace('{username}', user?.username || '')}
            </h2>
            <p className="text-black/80">
              <T k="ready_to_grow_wealth" fallback="Ready to grow your wealth?" /> • <T k="last_login" fallback="Last login:" /> {new Date().toLocaleString()}
            </p>
          </div>
          <div className="text-right">
            <Badge variant="secondary" className="bg-black/20 text-black border-black/30">
              <T k="funder_badge" fallback="ANGEL FUNDER" />
            </Badge>
          </div>
        </div>
      </div>

      {/* Investment Guide */}
      <InvestmentGuide onNavigate={onNavigate} />

      {/* Statistics Overview */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {/* Commission Earnings */}
        <Card className="bg-gradient-to-br from-green-800 to-green-900 border-green-700">
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium text-green-100">
              <T k="commission_earnings" fallback="Commission Earnings" />
            </CardTitle>
            <DollarSign className="h-4 w-4 text-green-300" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-white">${stats?.commissions.total_usdt_earned?.toLocaleString() || '0'}</div>
            <div className="flex items-center text-xs text-green-200 mt-1">
              <Star className="h-3 w-3 mr-1 text-green-300" />
              {stats?.commissions.total_nft_earned || 0} <T k="nft_packs_earned" fallback="NFT packs earned" />
            </div>
          </CardContent>
        </Card>

        {/* Available Balance */}
        <Card className="bg-gradient-to-br from-blue-800 to-blue-900 border-blue-700">
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium text-blue-100">
              <T k="available_balance" fallback="Available Balance" />
            </CardTitle>
            <Wallet className="h-4 w-4 text-blue-300" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-white">${stats?.commissions.available_usdt_balance?.toLocaleString() || '0'}</div>
            <div className="flex items-center text-xs text-blue-200 mt-1">
              <Package className="h-3 w-3 mr-1 text-blue-300" />
              {stats?.commissions.available_nft_balance || 0} <T k="nft_available" fallback="NFT available" />
            </div>
          </CardContent>
        </Card>

        {/* Total Investments */}
        <Card className="bg-gray-800 border-gray-700">
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium text-gray-200">
              <T k="total_funding" fallback="Total Funding" />
            </CardTitle>
            <Package className="h-4 w-4 text-gray-400" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-white">{stats?.investments.total || 0}</div>
            <div className="flex items-center text-xs text-gray-400 mt-1">
              <CheckCircle className="h-3 w-3 mr-1 text-green-400" />
              {stats?.investments.active || 0} <T k="active" fallback="active" />
              <Clock className="h-3 w-3 ml-2 mr-1 text-yellow-400" />
              {stats?.investments.completed || 0} <T k="completed" fallback="completed" />
            </div>
          </CardContent>
        </Card>

        {/* Portfolio Value */}
        <Card className="bg-gray-800 border-gray-700">
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium text-gray-200">
              <T k="portfolio_value" fallback="Portfolio Value" />
            </CardTitle>
            <DollarSign className="h-4 w-4 text-gray-400" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-white">${stats?.investments.total_value?.toLocaleString() || '0'}</div>
            <div className="flex items-center text-xs text-gray-400 mt-1">
              <TrendingUp className="h-3 w-3 mr-1 text-green-400" />
              ${stats?.investments.total_reward?.toLocaleString() || '0'} <T k="expected_reward" fallback="expected reward" />
            </div>
          </CardContent>
        </Card>

        {/* Shares Owned */}
        <Card className="bg-gray-800 border-gray-700">
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium text-gray-200">
              <T k="aureus_shares" fallback="Aureus Shares" />
            </CardTitle>
            <Star className="h-4 w-4 text-gray-400" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-white">{stats?.portfolio.total_shares?.toLocaleString() || '0'}</div>
            <div className="flex items-center text-xs text-gray-400 mt-1">
              <Target className="h-3 w-3 mr-1 text-blue-400" />
              ${stats?.portfolio.expected_dividends?.toLocaleString() || '0'} <T k="annual_dividends" fallback="annual dividends" />
            </div>
          </CardContent>
        </Card>

        {/* Recent Activity */}
        <Card className="bg-gray-800 border-gray-700">
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium text-gray-200">
              <T k="activity" fallback="Activity" />
            </CardTitle>
            <Activity className="h-4 w-4 text-gray-400" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-white">{stats?.activity.recent_transactions || 0}</div>
            <div className="flex items-center text-xs text-gray-400 mt-1">
              <AlertCircle className="h-3 w-3 mr-1 text-orange-400" />
              {stats?.activity.pending_transactions || 0} <T k="pending" fallback="pending" />
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Available Packages Preview */}
      <Card className="bg-gray-800 border-gray-700">
        <CardHeader>
          <div className="flex items-center justify-between">
            <CardTitle className="text-white">
              <T k="available_funding_packages" fallback="Available Funding Packages" />
            </CardTitle>
            <Button onClick={() => onNavigate('packages')} variant="outline" size="sm">
              <Eye className="h-4 w-4 mr-2" />
              <T k="view_all" fallback="View All" />
            </Button>
          </div>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            {packages.slice(0, 3).map((pkg) => (
              <div key={pkg.id} className="bg-gray-700 rounded-lg p-4 border border-gray-600">
                <div className="flex items-center justify-between mb-2">
                  <h3 className="font-semibold text-white">{pkg.name}</h3>
                  <Badge className="bg-gold/10 border-gold/30 text-gold">
                    ${pkg.price?.toLocaleString() || '0'}
                  </Badge>
                </div>
                <div className="text-sm text-gray-300 space-y-1">
                  <div>• {pkg.shares} <T k="aureus_shares" fallback="Aureus Shares" /></div>
                  <div>• ${pkg.roi?.toLocaleString() || '0'} <T k="expected_roi" fallback="Expected ROI" /></div>
                  <div>• ${pkg.annual_dividends?.toLocaleString() || '0'} <T k="annual_dividends" fallback="Annual Dividends" /></div>
                </div>
                <Button
                  onClick={() => onNavigate('packages')}
                  className="w-full mt-3 bg-gold hover:bg-gold/90 text-black"
                  size="sm"
                >
                  <Plus className="h-4 w-4 mr-1" />
                  <T k="fund_now" fallback="Fund Now" />
                </Button>
              </div>
            ))}
          </div>
          {packages.length === 0 && (
            <div className="text-center py-8 text-gray-400">
              <Package className="h-12 w-12 mx-auto mb-4 text-gray-500" />
              <p><T k="no_packages_available" fallback="No funding packages available at the moment." /></p>
            </div>
          )}
        </CardContent>
      </Card>

      {/* Quick Actions */}
      <Card className="bg-gray-800 border-gray-700">
        <CardHeader>
          <CardTitle className="text-white">
            <T k="quick_actions" fallback="Quick Actions" />
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {quickActions.map((action, index) => (
              <Button
                key={index}
                onClick={action.action}
                variant="outline"
                className="h-auto p-4 border-gray-600 hover:bg-gray-700 text-left justify-start"
              >
                <div className={`${action.color} p-2 rounded-lg mr-3 text-white`}>
                  {action.icon}
                </div>
                <div className="flex-1">
                  <div className="flex items-center gap-2">
                    <div className="font-medium text-white">{action.title}</div>
                    {action.badge && (
                      <Badge className="bg-gold/20 text-gold border-gold/30 text-xs">
                        {action.badge}
                      </Badge>
                    )}
                  </div>
                  <div className="text-sm text-gray-400">{action.description}</div>
                </div>
              </Button>
            ))}
          </div>
        </CardContent>
      </Card>
    </div>
  );
};

export default UserDashboard;

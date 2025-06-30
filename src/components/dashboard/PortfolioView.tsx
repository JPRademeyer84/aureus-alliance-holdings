import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { useSimpleTranslation as useTranslation, ST as T } from '@/components/SimpleTranslator';
import {
  TrendingUp,
  TrendingDown,
  DollarSign,
  Star,
  Calendar,
  Target,
  Activity,
  RefreshCw,
  Download,
  Eye
} from 'lucide-react';

// Safe chart icons to avoid SVG path errors
const PieChart = ({ className }: { className?: string }) => <span className={className}>🥧</span>;
const BarChart3 = ({ className }: { className?: string }) => <span className={className}>📊</span>;
import { useNavigate } from 'react-router-dom';

interface PortfolioData {
  summary: {
    total_invested: number;
    current_value: number;
    total_commission_earned: number;
    total_shares: number;
    nft_delivery_countdown: number;
    next_nft_delivery: string;
  };
  investments: Array<{
    id: string;
    package_name: string;
    invested_amount: number;
    current_value: number;
    shares: number;
    purchase_date: string;
    status: 'active' | 'completed' | 'pending';
    commission_earned: number;
    phase_id: number;
    nft_delivery_date: string;
  }>;
  performance: {
    monthly_commission: number;
    yearly_commission: number;
    commission_history: Array<{
      date: string;
      amount: number;
      type: string;
    }>;
  };
}

const PortfolioView: React.FC = () => {
  const { translate } = useTranslation();
  const [portfolioData, setPortfolioData] = useState<PortfolioData | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [selectedTimeframe, setSelectedTimeframe] = useState('1Y');
  const navigate = useNavigate();

  const fetchPortfolioData = async () => {
    setIsLoading(true);
    try {
      // Fetch user's investment history from working API
      const response = await fetch('http://localhost/aureus-angel-alliance/get-my-investments.php?user_id=1', {
        method: 'GET'
      });

      if (!response.ok) {
        throw new Error('Failed to fetch investment history');
      }

      const data = await response.json();
      const investments = data.investments || [];

      // Calculate portfolio summary from real data with null safety (NEW BUSINESS MODEL)
      const totalInvested = investments.reduce((sum, inv) => {
        const amount = parseFloat(inv.amount) || 0;
        return sum + (isNaN(amount) ? 0 : amount);
      }, 0);

      const totalShares = investments.reduce((sum, inv) => {
        const shares = parseInt(inv.shares) || 0;
        return sum + (isNaN(shares) ? 0 : shares);
      }, 0);

      // Calculate commission earned (if user has referrals)
      const totalCommissionEarned = 0; // Will be fetched from commission API later

      // Current value is just the invested amount (no ROI model)
      const currentValue = totalInvested;

      // Calculate next NFT delivery (12 months from latest investment)
      const latestInvestment = investments.reduce((latest, inv) => {
        const invDate = new Date(inv.createdAt);
        const latestDate = new Date(latest.createdAt);
        return invDate > latestDate ? inv : latest;
      }, investments[0] || { createdAt: new Date().toISOString() });

      const nextNftDelivery = new Date(latestInvestment.createdAt);
      nextNftDelivery.setMonth(nextNftDelivery.getMonth() + 12);

      // Convert to portfolio format with null safety (NEW BUSINESS MODEL)
      const portfolioInvestments = investments.map(inv => {
        const amount = parseFloat(inv.amount) || 0;
        const shares = parseInt(inv.shares) || 0;
        const commissionEarned = 0; // Will be calculated from referrals

        // Calculate NFT delivery date (12 months from investment)
        const investmentDate = new Date(inv.createdAt);
        const nftDeliveryDate = new Date(investmentDate);
        nftDeliveryDate.setMonth(nftDeliveryDate.getMonth() + 12);

        return {
          id: inv.id || '',
          package_name: inv.packageName || 'Unknown Package',
          invested_amount: isNaN(amount) ? 0 : amount,
          current_value: isNaN(amount) ? 0 : amount, // No ROI, value = investment
          shares: isNaN(shares) ? 0 : shares,
          purchase_date: inv.createdAt || '',
          status: (inv.status as 'active' | 'completed' | 'pending') || 'pending',
          commission_earned: commissionEarned,
          phase_id: inv.phase_id || 1,
          nft_delivery_date: nftDeliveryDate.toISOString()
        };
      });

      setPortfolioData({
        summary: {
          total_invested: isNaN(totalInvested) ? 0 : totalInvested,
          current_value: isNaN(currentValue) ? 0 : currentValue,
          total_commission_earned: isNaN(totalCommissionEarned) ? 0 : totalCommissionEarned,
          total_shares: isNaN(totalShares) ? 0 : totalShares,
          nft_delivery_countdown: Math.ceil((nextNftDelivery.getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24)),
          next_nft_delivery: nextNftDelivery.toISOString().split('T')[0]
        },
        investments: portfolioInvestments,
        performance: {
          monthly_commission: totalCommissionEarned > 0 ? totalCommissionEarned * 0.1 : 0,
          yearly_commission: totalCommissionEarned,
          commission_history: []
        }
      });
    } catch (error) {
      console.error('Failed to fetch portfolio data:', error);
      setPortfolioData({
        summary: {
          total_invested: 0,
          current_value: 0,
          total_commission_earned: 0,
          total_shares: 0,
          nft_delivery_countdown: 0,
          next_nft_delivery: new Date(Date.now() + 365 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
        },
        investments: [],
        performance: {
          monthly_commission: 0,
          yearly_commission: 0,
          commission_history: []
        }
      });
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchPortfolioData();
  }, []);

  if (isLoading) {
    return (
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <h2 className="text-2xl font-bold text-white">
            <T k="portfolio_overview" fallback="Portfolio Overview" />
          </h2>
        </div>
        <div className="text-center py-8">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-gold mx-auto"></div>
          <p className="text-gray-400 mt-2">
            <T k="loading_portfolio_data" fallback="Loading portfolio data..." />
          </p>
        </div>
      </div>
    );
  }

  const hasInvestments = (portfolioData?.investments?.length || 0) > 0;
  const summary = portfolioData?.summary;
  const performance = portfolioData?.performance;

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold text-white">
            <T k="portfolio_overview" fallback="Portfolio Overview" />
          </h2>
          <p className="text-gray-400">
            <T k="track_investment_performance" fallback="Track your investment performance and growth" />
          </p>
        </div>
        <div className="flex items-center space-x-2">
          <Button onClick={fetchPortfolioData} variant="outline" size="sm">
            <RefreshCw className="h-4 w-4 mr-2" />
            <T k="refresh" fallback="Refresh" />
          </Button>
          <Button variant="outline" size="sm" className="border-gold/30 text-gold hover:bg-gold/10">
            <Download className="h-4 w-4 mr-2" />
            <T k="export_report" fallback="Export Report" />
          </Button>
        </div>
      </div>

      {!hasInvestments ? (
        /* Empty State */
        <Card className="bg-gray-800 border-gray-700">
          <CardContent className="p-12">
            <div className="text-center">
              <PieChart className="h-16 w-16 text-gray-400 mx-auto mb-6" />
              <h3 className="text-xl font-semibold text-white mb-2">
                <T k="no_investments_yet" fallback="No Investments Yet" />
              </h3>
              <p className="text-gray-400 mb-6 max-w-md mx-auto">
                <T k="start_building_portfolio" fallback="Start building your portfolio by investing in our available packages. Track your growth and dividends all in one place." />
              </p>
              <Button
                className="bg-gold hover:bg-gold/90 text-black"
                onClick={() => navigate('/investment')}
              >
                <Eye className="h-4 w-4 mr-2" />
                <T k="browse_investment_packages" fallback="Browse Investment Packages" />
              </Button>
            </div>
          </CardContent>
        </Card>
      ) : (
        <>
          {/* Portfolio Summary */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <Card className="bg-gray-800 border-gray-700">
              <CardContent className="p-4">
                <div className="flex items-center">
                  <DollarSign className="h-8 w-8 text-green-400" />
                  <div className="ml-4">
                    <p className="text-sm font-medium text-gray-400">
                      <T k="total_invested" fallback="Total Invested" />
                    </p>
                    <p className="text-2xl font-bold text-white">${(summary?.total_invested || 0).toLocaleString()}</p>
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card className="bg-gray-800 border-gray-700">
              <CardContent className="p-4">
                <div className="flex items-center">
                  <TrendingUp className="h-8 w-8 text-blue-400" />
                  <div className="ml-4">
                    <p className="text-sm font-medium text-gray-400">
                      <T k="current_value" fallback="Current Value" />
                    </p>
                    <p className="text-2xl font-bold text-white">${(summary?.current_value || 0).toLocaleString()}</p>
                    <div className="flex items-center text-xs mt-1">
                      {(summary?.current_value || 0) >= (summary?.total_invested || 0) ? (
                        <TrendingUp className="h-3 w-3 text-green-400 mr-1" />
                      ) : (
                        <TrendingDown className="h-3 w-3 text-red-400 mr-1" />
                      )}
                      <span className={`${
                        (summary?.current_value || 0) >= (summary?.total_invested || 0) 
                          ? 'text-green-400' 
                          : 'text-red-400'
                      }`}>
                        {summary?.total_invested ?
                          (((summary.current_value - summary.total_invested) / summary.total_invested) * 100).toFixed(1)
                          : '0'}%
                      </span>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card className="bg-gray-800 border-gray-700">
              <CardContent className="p-4">
                <div className="flex items-center">
                  <Star className="h-8 w-8 text-yellow-400" />
                  <div className="ml-4">
                    <p className="text-sm font-medium text-gray-400">
                      <T k="aureus_shares" fallback="Aureus Shares" />
                    </p>
                    <p className="text-2xl font-bold text-white">{summary?.total_shares?.toLocaleString() || '0'}</p>
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card className="bg-gray-800 border-gray-700">
              <CardContent className="p-4">
                <div className="flex items-center">
                  <DollarSign className="h-8 w-8 text-green-400" />
                  <div className="ml-4">
                    <p className="text-sm font-medium text-gray-400">
                      Commission Earnings
                    </p>
                    <p className="text-2xl font-bold text-white">${(summary?.total_commission_earned || 0).toLocaleString()}</p>
                    <p className="text-xs text-gray-400 mt-1">
                      20% Direct Sales Commission
                    </p>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>

          {/* Performance Chart Placeholder */}
          <Card className="bg-gray-800 border-gray-700">
            <CardHeader>
              <div className="flex items-center justify-between">
                <CardTitle className="text-white">
                  <T k="portfolio_performance" fallback="Portfolio Performance" />
                </CardTitle>
                <div className="flex items-center space-x-2">
                  {['1M', '3M', '6M', '1Y', 'ALL'].map((timeframe) => (
                    <Button
                      key={timeframe}
                      onClick={() => setSelectedTimeframe(timeframe)}
                      variant={selectedTimeframe === timeframe ? "secondary" : "ghost"}
                      size="sm"
                      className={selectedTimeframe === timeframe ? 
                        'bg-gold/10 text-gold' : 
                        'text-gray-400 hover:text-white'
                      }
                    >
                      {timeframe}
                    </Button>
                  ))}
                </div>
              </div>
            </CardHeader>
            <CardContent>
              <div className="h-64 flex items-center justify-center border-2 border-dashed border-gray-600 rounded-lg">
                <div className="text-center">
                  <BarChart3 className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                  <p className="text-gray-400">
                    <T k="performance_chart_displayed" fallback="Performance chart will be displayed here" />
                  </p>
                  <p className="text-sm text-gray-500 mt-1">
                    <T k="chart_integration_coming" fallback="Chart integration coming soon" />
                  </p>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Active Investments */}
          <Card className="bg-gray-800 border-gray-700">
            <CardHeader>
              <CardTitle className="text-white">
                Active Investments & Share Certificates
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                {(portfolioData?.investments || []).map((investment) => (
                  <div key={investment.id} className="bg-gray-700 rounded-lg p-4 border border-gray-600">
                    <div className="flex items-center justify-between">
                      <div className="flex-1">
                        <div className="flex items-center space-x-3">
                          <h3 className="font-semibold text-white">{investment.package_name}</h3>
                          <Badge className={`text-xs ${
                            investment.status === 'active' ? 'bg-green-100 text-green-800' :
                            investment.status === 'completed' ? 'bg-blue-100 text-blue-800' :
                            'bg-yellow-100 text-yellow-800'
                          }`}>
                            <T k={investment.status} fallback={investment.status.toUpperCase()} />
                          </Badge>
                        </div>
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mt-3 text-sm">
                          <div>
                            <p className="text-gray-400">
                              <T k="invested" fallback="Invested" />
                            </p>
                            <p className="text-white font-medium">${investment.invested_amount.toLocaleString()}</p>
                          </div>
                          <div>
                            <p className="text-gray-400">
                              <T k="current_value" fallback="Current Value" />
                            </p>
                            <p className="text-white font-medium">${investment.current_value.toLocaleString()}</p>
                          </div>
                          <div>
                            <p className="text-gray-400">
                              <T k="shares" fallback="Shares" />
                            </p>
                            <p className="text-white font-medium">{investment.shares.toLocaleString()}</p>
                          </div>
                          <div>
                            <p className="text-gray-400">
                              NFT Delivery
                            </p>
                            <p className="text-blue-400 font-medium">
                              {Math.ceil((new Date(investment.nft_delivery_date).getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24))} days
                            </p>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>

          {/* Dividend History */}
          <Card className="bg-gray-800 border-gray-700">
            <CardHeader>
              <CardTitle className="text-white">
                <T k="recent_dividend_payments" fallback="Recent Dividend Payments" />
              </CardTitle>
            </CardHeader>
            <CardContent>
              {(performance?.dividend_history?.length || 0) === 0 ? (
                <div className="text-center py-8">
                  <Calendar className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                  <p className="text-gray-400">
                    <T k="no_dividend_payments_yet" fallback="No dividend payments yet" />
                  </p>
                  <p className="text-sm text-gray-500 mt-1">
                    <T k="dividends_appear_investments_mature" fallback="Dividends will appear here once your investments mature" />
                  </p>
                </div>
              ) : (
                <div className="space-y-3">
                  {(performance?.dividend_history || []).map((dividend, index) => (
                    <div key={index} className="flex items-center justify-between py-2 border-b border-gray-700 last:border-b-0">
                      <div className="flex items-center space-x-3">
                        <div className="w-2 h-2 bg-green-400 rounded-full"></div>
                        <span className="text-white">{new Date(dividend.date).toLocaleDateString()}</span>
                      </div>
                      <span className="text-green-400 font-medium">+${dividend.amount.toLocaleString()}</span>
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>
        </>
      )}
    </div>
  );
};

export default PortfolioView;

import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { useToast } from '@/hooks/use-toast';
import { useSimpleTranslation as useTranslation, ST as T } from '@/components/SimpleTranslator';
import {
  Users,
  DollarSign,
  Gift,
  Copy,
  Share2,
  TrendingUp,
  Award,
  ExternalLink,
  RefreshCw,
  MessageSquare,
  Target,
  BarChart3
} from 'lucide-react';
import { useUser } from '@/contexts/UserContext';
import { useReferralStats } from '@/hooks/useReferralTracking';
import DownlineManager from '@/components/affiliate/DownlineManager';
import SocialMediaTools from '@/components/affiliate/SocialMediaTools';

interface ReferralStats {
  totalReferrals: number;
  totalCommissions: number;
  pendingCommissions: number;
  paidCommissions: number;
  availableBalance: number;
  thisMonthCommissions: number;
}

interface ReferralRecord {
  id: string;
  referredUser: string;
  purchaseAmount: number;
  commissionAmount: number;
  commissionPercentage: number;
  status: 'pending' | 'paid';
  date: string;
  packageName: string;
}

const AffiliateView: React.FC = () => {
  const [referralCode, setReferralCode] = useState('');
  const [activeTab, setActiveTab] = useState<'overview' | 'downline' | 'marketing'>('overview');
  const { user } = useUser();
  const { toast } = useToast();
  const { translate } = useTranslation();

  // Use the referral stats hook
  const { stats: referralStats, history: referralRecords, isLoading, error, refetch } = useReferralStats(user?.id?.toString());

  // Generate referral code based on username
  useEffect(() => {
    if (user?.username) {
      setReferralCode(user.username);
    }
  }, [user]);

  const referralLink = `${window.location.origin}/${referralCode}`;

  const copyReferralLink = () => {
    navigator.clipboard.writeText(referralLink).then(() => {
      toast({
        title: translate('copied', 'Copied!'),
        description: translate('referral_link_copied', 'Referral link copied to clipboard'),
      });
    });
  };

  const shareReferralLink = () => {
    if (navigator.share) {
      navigator.share({
        title: translate('join_aureus_alliance_nft', 'Join Aureus Alliance NFT Presale'),
        text: translate('get_exclusive_nft_packs', 'Get exclusive NFT packs with amazing rewards!'),
        url: referralLink,
      });
    } else {
      copyReferralLink();
    }
  };

  // Remove the old fetchReferralData function since we're using the hook

  // NEW 20% Direct Commission Model
  const getCommissionInfo = () => {
    return {
      percentage: 20, // 20% of commission allocation
      effectiveRate: 3, // 3% of total investment
      description: '20% Direct Sales Commission',
      calculation: 'Investment × 15% × 20% = Your Commission'
    };
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold text-white">
            <T k="affiliate_program" fallback="Affiliate Program" />
          </h2>
          <p className="text-gray-400">
            <T k="build_network_earn_commissions" fallback="Build your network, earn commissions, and grow your business" />
          </p>
        </div>
        <Button onClick={refetch} variant="outline" size="sm" disabled={isLoading}>
          <RefreshCw className={`h-4 w-4 mr-2 ${isLoading ? 'animate-spin' : ''}`} />
          <T k="refresh" fallback="Refresh" />
        </Button>
      </div>

      {/* Tab Navigation */}
      <div className="flex items-center gap-1 bg-gray-800 rounded-lg p-1">
        <Button
          variant={activeTab === 'overview' ? 'default' : 'ghost'}
          onClick={() => setActiveTab('overview')}
          className="flex-1"
        >
          <BarChart3 className="h-4 w-4 mr-2" />
          <T k="overview" fallback="Overview" />
        </Button>
        <Button
          variant={activeTab === 'downline' ? 'default' : 'ghost'}
          onClick={() => setActiveTab('downline')}
          className="flex-1"
        >
          <Users className="h-4 w-4 mr-2" />
          <T k="downline_manager" fallback="Downline Manager" />
        </Button>
        <Button
          variant={activeTab === 'marketing' ? 'default' : 'ghost'}
          onClick={() => setActiveTab('marketing')}
          className="flex-1"
        >
          <Target className="h-4 w-4 mr-2" />
          <T k="marketing_tools" fallback="Marketing Tools" />
        </Button>
      </div>

      {/* Tab Content */}
      {activeTab === 'overview' && (
        <div className="space-y-6">

      {/* NFT Presale Info */}
      <Card className="bg-gradient-to-r from-gold/10 to-gold/5 border-gold/30">
        <CardContent className="p-6">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6 text-center">
            <div>
              <h3 className="text-2xl font-bold text-gold mb-2">$5</h3>
              <p className="text-gray-300">
                <T k="per_nft_pack" fallback="Per NFT Pack" />
              </p>
            </div>
            <div>
              <h3 className="text-2xl font-bold text-gold mb-2">200,000</h3>
              <p className="text-gray-300">
                <T k="total_packs_available" fallback="Total Packs Available" />
              </p>
            </div>
            <div>
              <h3 className="text-2xl font-bold text-gold mb-2">
                20% Direct
              </h3>
              <p className="text-gray-300">
                Commission Structure
              </p>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Referral Link Section */}
      <Card className="bg-gray-800 border-gray-700">
        <CardHeader>
          <CardTitle className="text-white flex items-center gap-2">
            <Share2 className="h-5 w-5 text-gold" />
            <T k="your_referral_link" fallback="Your Referral Link" />
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="flex items-center gap-2">
            <Input
              value={referralLink}
              readOnly
              className="bg-gray-700 border-gray-600 text-white"
            />
            <Button onClick={copyReferralLink} variant="outline" size="sm">
              <Copy className="h-4 w-4" />
            </Button>
            <Button onClick={shareReferralLink} variant="outline" size="sm">
              <Share2 className="h-4 w-4" />
            </Button>
          </div>
          <div className="text-center">
            <p className="text-sm text-gray-400 mb-2">
              <T k="your_referral_code" fallback="Your Referral Code:" />
            </p>
            <Badge className="bg-gold/20 text-gold text-lg px-4 py-2">
              {referralCode}
            </Badge>
          </div>
        </CardContent>
      </Card>

      {/* Commission Structure */}
      <Card className="bg-gray-800 border-gray-700">
        <CardHeader>
          <CardTitle className="text-white flex items-center gap-2">
            <Award className="h-5 w-5 text-gold" />
            <T k="commission_structure" fallback="Commission Structure" />
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {/* New Commission Structure */}
            <div className="bg-gradient-to-r from-green-500/10 to-green-600/10 border border-green-500/30 rounded-lg p-6 text-center">
              <h3 className="text-xl font-semibold text-white mb-4">Direct Sales Commission</h3>
              <div className="space-y-3">
                <div className="text-3xl font-bold text-green-400">20%</div>
                <div className="text-sm text-gray-300">of commission allocation</div>
                <div className="text-xs text-gray-400">
                  Effective rate: 3% of total investment
                </div>
              </div>
              <div className="mt-4 p-3 bg-gray-800/50 rounded text-xs text-gray-300">
                <strong>Example:</strong> $1,000 sale = $30 commission
                <br />
                <span className="text-gray-400">($1,000 × 15% × 20% = $30)</span>
              </div>
            </div>

            {/* Revenue Distribution */}
            <div className="bg-gradient-to-r from-blue-500/10 to-purple-600/10 border border-blue-500/30 rounded-lg p-6">
              <h3 className="text-xl font-semibold text-white mb-4">Revenue Distribution</h3>
              <div className="space-y-2 text-sm">
                <div className="flex justify-between">
                  <span className="text-gray-300">Commission Pool:</span>
                  <span className="text-green-400">15%</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-300">Competition:</span>
                  <span className="text-yellow-400">15%</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-300">Platform & Tech:</span>
                  <span className="text-blue-400">25%</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-300">NPO Fund:</span>
                  <span className="text-purple-400">10%</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-300">Mine Setup:</span>
                  <span className="text-orange-400">35%</span>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Stats Overview */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <Card className="bg-gray-800 border-gray-700">
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <Users className="h-8 w-8 text-blue-400" />
              <div>
                <p className="text-sm text-gray-400">
                  <T k="total_referrals" fallback="Total Referrals" />
                </p>
                <p className="text-2xl font-bold text-white">{referralStats?.totalReferrals || 0}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card className="bg-gray-800 border-gray-700">
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <DollarSign className="h-8 w-8 text-green-400" />
              <div>
                <p className="text-sm text-gray-400">
                  <T k="total_usdt_earned" fallback="Total USDT Earned" />
                </p>
                <p className="text-2xl font-bold text-white">${(referralStats?.totalCommissions || 0).toFixed(2)}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card className="bg-gray-800 border-gray-700">
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <TrendingUp className="h-8 w-8 text-blue-400" />
              <div>
                <p className="text-sm text-gray-400">
                  Available Balance
                </p>
                <p className="text-2xl font-bold text-white">${(referralStats?.availableBalance || 0).toFixed(2)}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card className="bg-gray-800 border-gray-700">
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <TrendingUp className="h-8 w-8 text-gold" />
              <div>
                <p className="text-sm text-gray-400">
                  <T k="pending_usdt" fallback="Pending USDT" />
                </p>
                <p className="text-2xl font-bold text-white">${(referralStats?.pendingCommissions || 0).toFixed(2)}</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Commission Performance */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <Card className="bg-gray-800 border-gray-700">
          <CardContent className="p-4 text-center">
            <h3 className="text-lg font-semibold text-white mb-2">
              This Month Commissions
            </h3>
            <p className="text-3xl font-bold text-green-400">${(referralStats?.thisMonthCommissions || 0).toFixed(2)}</p>
            <p className="text-sm text-gray-400 mt-1">
              Current month earnings
            </p>
          </CardContent>
        </Card>

        <Card className="bg-gray-800 border-gray-700">
          <CardContent className="p-4 text-center">
            <h3 className="text-lg font-semibold text-white mb-2">
              Average Commission
            </h3>
            <p className="text-3xl font-bold text-blue-400">${(referralStats?.averageCommission || 0).toFixed(2)}</p>
            <p className="text-sm text-gray-400 mt-1">
              Per referral sale
            </p>
          </CardContent>
        </Card>
      </div>

      {/* Recent Referral Activity */}
      <Card className="bg-gray-800 border-gray-700">
        <CardHeader>
          <CardTitle className="text-white flex items-center gap-2">
            <TrendingUp className="h-5 w-5 text-gold" />
            <T k="recent_referral_activity" fallback="Recent Referral Activity" />
          </CardTitle>
        </CardHeader>
        <CardContent>
          {error ? (
            <div className="text-center py-8">
              <div className="text-red-400 mb-2">
                <T k="error_loading_referral_data" fallback="⚠️ Error loading referral data" />
              </div>
              <p className="text-red-300 text-sm mb-4">{error}</p>
              <Button onClick={refetch} variant="outline" size="sm">
                <T k="try_again" fallback="Try Again" />
              </Button>
            </div>
          ) : referralRecords.length === 0 ? (
            <div className="text-center py-8">
              <Users className="h-12 w-12 text-gray-400 mx-auto mb-4" />
              <p className="text-gray-400">
                <T k="no_referral_activity_yet" fallback="No referral activity yet" />
              </p>
              <p className="text-sm text-gray-500 mt-1">
                <T k="start_sharing_referral_link" fallback="Start sharing your referral link to earn commissions!" />
              </p>
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead>
                  <tr className="border-b border-gray-700">
                    <th className="text-left py-3 px-4 text-gray-400 font-medium">
                      <T k="user" fallback="User" />
                    </th>
                    <th className="text-left py-3 px-4 text-gray-400 font-medium">
                      <T k="level" fallback="Level" />
                    </th>
                    <th className="text-left py-3 px-4 text-gray-400 font-medium">
                      <T k="purchase" fallback="Purchase" />
                    </th>
                    <th className="text-left py-3 px-4 text-gray-400 font-medium">
                      <T k="usdt_commission" fallback="USDT Commission" />
                    </th>
                    <th className="text-left py-3 px-4 text-gray-400 font-medium">
                      <T k="nft_bonus" fallback="NFT Bonus" />
                    </th>
                    <th className="text-left py-3 px-4 text-gray-400 font-medium">
                      <T k="status" fallback="Status" />
                    </th>
                    <th className="text-left py-3 px-4 text-gray-400 font-medium">
                      <T k="date" fallback="Date" />
                    </th>
                  </tr>
                </thead>
                <tbody>
                  {referralRecords.map((record) => (
                    <tr key={record.id} className="border-b border-gray-700/50">
                      <td className="py-3 px-4 text-white">{record.referredUsername}</td>
                      <td className="py-3 px-4">
                        <Badge className={`
                          ${record.level === 1 ? 'bg-blue-500/20 text-blue-400' :
                            record.level === 2 ? 'bg-green-500/20 text-green-400' :
                            'bg-purple-500/20 text-purple-400'}
                        `}>
                          {translate('level_number', 'Level {number}').replace('{number}', record.level.toString())}
                        </Badge>
                      </td>
                      <td className="py-3 px-4 text-white">${record.purchaseAmount}</td>
                      <td className="py-3 px-4 text-green-400">${record.commissionUSDT}</td>
                      <td className="py-3 px-4 text-blue-400">
                        {translate('nfts_count', '{count} NFTs').replace('{count}', record.commissionNFT.toString())}
                      </td>
                      <td className="py-3 px-4">
                        <Badge className={`
                          ${record.status === 'paid' ? 'bg-green-500/20 text-green-400' :
                            record.status === 'pending' ? 'bg-yellow-500/20 text-yellow-400' :
                            'bg-red-500/20 text-red-400'}
                        `}>
                          {record.status === 'paid' ? translate('paid', 'Paid') :
                           record.status === 'pending' ? translate('pending', 'Pending') :
                           translate('cancelled', 'Cancelled')}
                        </Badge>
                      </td>
                      <td className="py-3 px-4 text-gray-400">
                        {new Date(record.createdAt).toLocaleDateString()}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </CardContent>
      </Card>
        </div>
      )}

      {/* Downline Manager Tab */}
      {activeTab === 'downline' && <DownlineManager />}

      {/* Marketing Tools Tab */}
      {activeTab === 'marketing' && <SocialMediaTools />}
    </div>
  );
};

export default AffiliateView;

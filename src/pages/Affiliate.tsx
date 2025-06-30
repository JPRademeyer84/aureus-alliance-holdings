import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { useToast } from '@/hooks/use-toast';
import {
  Users,
  DollarSign,
  Gift,
  Copy,
  Share2,
  TrendingUp,
  Award,
  ExternalLink,
  RefreshCw
} from 'lucide-react';
import { useUser } from '@/contexts/UserContext';
import Navbar from '@/components/Navbar';
import Footer from '@/components/Footer';

interface ReferralStats {
  totalReferrals: number;
  totalCommissions: number;
  totalNFTBonuses: number;
  level1Referrals: number;
  level2Referrals: number;
  level3Referrals: number;
  pendingCommissions: number;
  paidCommissions: number;
}

interface ReferralRecord {
  id: string;
  referredUser: string;
  level: 1 | 2 | 3;
  purchaseAmount: number;
  commissionUSDT: number;
  commissionNFT: number;
  status: 'pending' | 'paid';
  date: string;
}

const Affiliate: React.FC = () => {
  const [referralStats, setReferralStats] = useState<ReferralStats>({
    totalReferrals: 0,
    totalCommissions: 0,
    totalNFTBonuses: 0,
    level1Referrals: 0,
    level2Referrals: 0,
    level3Referrals: 0,
    pendingCommissions: 0,
    paidCommissions: 0
  });
  const [referralRecords, setReferralRecords] = useState<ReferralRecord[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [referralCode, setReferralCode] = useState('');
  const { user } = useUser();
  const { toast } = useToast();

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
        title: "Copied!",
        description: "Referral link copied to clipboard",
      });
    });
  };

  const shareReferralLink = () => {
    if (navigator.share) {
      navigator.share({
        title: 'Join Aureus Alliance NFT Presale',
        text: 'Get exclusive NFT packs with amazing rewards!',
        url: referralLink,
      });
    } else {
      copyReferralLink();
    }
  };

  const fetchReferralData = async () => {
    if (!user?.id) return;

    setIsLoading(true);
    try {
      // Fetch real referral statistics
      const statsResponse = await fetch('/api/referrals/user-stats.php', {
        method: 'GET',
        credentials: 'include'
      });

      if (!statsResponse.ok) {
        throw new Error('Failed to fetch referral stats');
      }

      const statsData = await statsResponse.json();

      if (statsData.success) {
        setReferralStats(statsData.stats);
      } else {
        throw new Error(statsData.error || 'Failed to fetch referral stats');
      }

      // Fetch real referral history
      const historyResponse = await fetch('/api/referrals/user-history.php', {
        method: 'GET',
        credentials: 'include'
      });

      if (!historyResponse.ok) {
        throw new Error('Failed to fetch referral history');
      }

      const historyData = await historyResponse.json();

      if (historyData.success) {
        setReferralRecords(historyData.records);
      } else {
        throw new Error(historyData.error || 'Failed to fetch referral history');
      }

    } catch (error) {
      console.error('Failed to fetch referral data:', error);
      // Set empty data on error (NO MOCK DATA)
      setReferralStats({
        totalReferrals: 0,
        totalCommissions: 0,
        totalNFTBonuses: 0,
        level1Referrals: 0,
        level2Referrals: 0,
        level3Referrals: 0,
        pendingCommissions: 0,
        paidCommissions: 0
      });
      setReferralRecords([]);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchReferralData();
  }, [user]);

  const getCommissionRate = (level: number) => {
    switch (level) {
      case 1: return { usdt: 12, nft: 12 };
      case 2: return { usdt: 5, nft: 5 };
      case 3: return { usdt: 3, nft: 3 };
      default: return { usdt: 0, nft: 0 };
    }
  };

  return (
    <div className="min-h-screen bg-charcoal">
      <Navbar />
      <div className="container mx-auto px-4 py-12">
        <div className="max-w-6xl mx-auto">
          {/* Header */}
          <div className="text-center mb-12">
            <h1 className="text-4xl font-bold text-white mb-4">
              <span className="text-gradient">Affiliate Program</span>
            </h1>
            <p className="text-gray-400 text-lg max-w-2xl mx-auto">
              Earn commissions by referring others to our exclusive NFT presale. 
              Get USDT + NFT bonuses for every successful referral!
            </p>
          </div>

          {/* NFT Presale Info */}
          <Card className="bg-gradient-to-r from-gold/10 to-gold/5 border-gold/30 mb-8">
            <CardContent className="p-6">
              <div className="grid grid-cols-1 md:grid-cols-3 gap-6 text-center">
                <div>
                  <h3 className="text-2xl font-bold text-gold mb-2">$5</h3>
                  <p className="text-gray-300">Per NFT Pack</p>
                </div>
                <div>
                  <h3 className="text-2xl font-bold text-gold mb-2">200,000</h3>
                  <p className="text-gray-300">Total Packs Available</p>
                </div>
                <div>
                  <h3 className="text-2xl font-bold text-gold mb-2">3 Levels</h3>
                  <p className="text-gray-300">Commission Structure</p>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Referral Link Section */}
          <Card className="bg-gray-800 border-gray-700 mb-8">
            <CardHeader>
              <CardTitle className="text-white flex items-center gap-2">
                <Share2 className="h-5 w-5 text-gold" />
                Your Referral Link
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
                <p className="text-sm text-gray-400 mb-2">Your Referral Code:</p>
                <Badge className="bg-gold/20 text-gold text-lg px-4 py-2">
                  {referralCode}
                </Badge>
              </div>
            </CardContent>
          </Card>

          {/* Commission Structure */}
          <Card className="bg-gray-800 border-gray-700 mb-8">
            <CardHeader>
              <CardTitle className="text-white flex items-center gap-2">
                <Award className="h-5 w-5 text-gold" />
                Commission Structure
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                {[1, 2, 3].map((level) => {
                  const rates = getCommissionRate(level);
                  return (
                    <div key={level} className="bg-gray-700 rounded-lg p-4 text-center">
                      <h3 className="text-lg font-semibold text-white mb-2">Level {level}</h3>
                      <div className="space-y-2">
                        <div className="text-green-400 font-bold">{rates.usdt}% USDT</div>
                        <div className="text-blue-400 font-bold">{rates.nft}% NFT Packs</div>
                      </div>
                      <div className="mt-3 text-xs text-gray-400">
                        Example: $1,000 sale = ${rates.usdt * 10} USDT + {rates.nft * 2} NFTs
                      </div>
                    </div>
                  );
                })}
              </div>
            </CardContent>
          </Card>

          {/* Stats Overview */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <Card className="bg-gray-800 border-gray-700">
              <CardContent className="p-4">
                <div className="flex items-center gap-3">
                  <Users className="h-8 w-8 text-blue-400" />
                  <div>
                    <p className="text-sm text-gray-400">Total Referrals</p>
                    <p className="text-2xl font-bold text-white">{referralStats.totalReferrals}</p>
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card className="bg-gray-800 border-gray-700">
              <CardContent className="p-4">
                <div className="flex items-center gap-3">
                  <DollarSign className="h-8 w-8 text-green-400" />
                  <div>
                    <p className="text-sm text-gray-400">Total USDT Earned</p>
                    <p className="text-2xl font-bold text-white">${referralStats.totalCommissions.toFixed(2)}</p>
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card className="bg-gray-800 border-gray-700">
              <CardContent className="p-4">
                <div className="flex items-center gap-3">
                  <Gift className="h-8 w-8 text-purple-400" />
                  <div>
                    <p className="text-sm text-gray-400">NFT Bonuses</p>
                    <p className="text-2xl font-bold text-white">{referralStats.totalNFTBonuses}</p>
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card className="bg-gray-800 border-gray-700">
              <CardContent className="p-4">
                <div className="flex items-center gap-3">
                  <TrendingUp className="h-8 w-8 text-gold" />
                  <div>
                    <p className="text-sm text-gray-400">Pending USDT</p>
                    <p className="text-2xl font-bold text-white">${referralStats.pendingCommissions.toFixed(2)}</p>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>

          {/* Level Breakdown */}
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <Card className="bg-gray-800 border-gray-700">
              <CardContent className="p-4 text-center">
                <h3 className="text-lg font-semibold text-white mb-2">Level 1 Referrals</h3>
                <p className="text-3xl font-bold text-blue-400">{referralStats.level1Referrals}</p>
                <p className="text-sm text-gray-400 mt-1">Direct referrals</p>
              </CardContent>
            </Card>

            <Card className="bg-gray-800 border-gray-700">
              <CardContent className="p-4 text-center">
                <h3 className="text-lg font-semibold text-white mb-2">Level 2 Referrals</h3>
                <p className="text-3xl font-bold text-green-400">{referralStats.level2Referrals}</p>
                <p className="text-sm text-gray-400 mt-1">2nd level referrals</p>
              </CardContent>
            </Card>

            <Card className="bg-gray-800 border-gray-700">
              <CardContent className="p-4 text-center">
                <h3 className="text-lg font-semibold text-white mb-2">Level 3 Referrals</h3>
                <p className="text-3xl font-bold text-purple-400">{referralStats.level3Referrals}</p>
                <p className="text-sm text-gray-400 mt-1">3rd level referrals</p>
              </CardContent>
            </Card>
          </div>

          {/* Recent Referral Activity */}
          <Card className="bg-gray-800 border-gray-700">
            <CardHeader>
              <div className="flex items-center justify-between">
                <CardTitle className="text-white flex items-center gap-2">
                  <TrendingUp className="h-5 w-5 text-gold" />
                  Recent Referral Activity
                </CardTitle>
                <Button onClick={fetchReferralData} variant="outline" size="sm" disabled={isLoading}>
                  <RefreshCw className={`h-4 w-4 ${isLoading ? 'animate-spin' : ''}`} />
                </Button>
              </div>
            </CardHeader>
            <CardContent>
              {referralRecords.length === 0 ? (
                <div className="text-center py-8">
                  <Users className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                  <p className="text-gray-400">No referral activity yet</p>
                  <p className="text-sm text-gray-500 mt-1">Start sharing your referral link to earn commissions!</p>
                </div>
              ) : (
                <div className="overflow-x-auto">
                  <table className="w-full">
                    <thead>
                      <tr className="border-b border-gray-700">
                        <th className="text-left py-3 px-4 text-gray-400 font-medium">User</th>
                        <th className="text-left py-3 px-4 text-gray-400 font-medium">Level</th>
                        <th className="text-left py-3 px-4 text-gray-400 font-medium">Purchase</th>
                        <th className="text-left py-3 px-4 text-gray-400 font-medium">USDT Commission</th>
                        <th className="text-left py-3 px-4 text-gray-400 font-medium">NFT Bonus</th>
                        <th className="text-left py-3 px-4 text-gray-400 font-medium">Status</th>
                        <th className="text-left py-3 px-4 text-gray-400 font-medium">Date</th>
                      </tr>
                    </thead>
                    <tbody>
                      {referralRecords.map((record) => (
                        <tr key={record.id} className="border-b border-gray-700/50">
                          <td className="py-3 px-4 text-white">{record.referredUser}</td>
                          <td className="py-3 px-4">
                            <Badge className={`
                              ${record.level === 1 ? 'bg-blue-500/20 text-blue-400' :
                                record.level === 2 ? 'bg-green-500/20 text-green-400' :
                                'bg-purple-500/20 text-purple-400'}
                            `}>
                              Level {record.level}
                            </Badge>
                          </td>
                          <td className="py-3 px-4 text-white">${record.purchaseAmount}</td>
                          <td className="py-3 px-4 text-green-400">${record.commissionUSDT}</td>
                          <td className="py-3 px-4 text-blue-400">{record.commissionNFT} NFTs</td>
                          <td className="py-3 px-4">
                            <Badge className={`
                              ${record.status === 'paid' ? 'bg-green-500/20 text-green-400' : 'bg-yellow-500/20 text-yellow-400'}
                            `}>
                              {record.status === 'paid' ? 'Paid' : 'Pending'}
                            </Badge>
                          </td>
                          <td className="py-3 px-4 text-gray-400">
                            {new Date(record.date).toLocaleDateString()}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </CardContent>
          </Card>

          {/* How It Works */}
          <Card className="bg-gray-800 border-gray-700 mt-8">
            <CardHeader>
              <CardTitle className="text-white">How It Works</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div className="text-center">
                  <div className="bg-gold/20 rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-3">
                    <span className="text-gold font-bold">1</span>
                  </div>
                  <h3 className="font-semibold text-white mb-2">Share Your Link</h3>
                  <p className="text-gray-400 text-sm">Copy and share your unique referral link with friends and family</p>
                </div>
                <div className="text-center">
                  <div className="bg-gold/20 rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-3">
                    <span className="text-gold font-bold">2</span>
                  </div>
                  <h3 className="font-semibold text-white mb-2">They Purchase NFTs</h3>
                  <p className="text-gray-400 text-sm">When someone uses your link to buy NFT packs, you earn commissions</p>
                </div>
                <div className="text-center">
                  <div className="bg-gold/20 rounded-full w-12 h-12 flex items-center justify-center mx-auto mb-3">
                    <span className="text-gold font-bold">3</span>
                  </div>
                  <h3 className="font-semibold text-white mb-2">Earn Rewards</h3>
                  <p className="text-gray-400 text-sm">Get USDT commissions + NFT bonuses for up to 3 levels deep</p>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
      <Footer />
    </div>
  );
};

export default Affiliate;

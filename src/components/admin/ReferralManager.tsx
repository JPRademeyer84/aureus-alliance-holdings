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
  TrendingUp,
  Award,
  RefreshCw,
  Search,
  Download,
  CheckCircle,
  Clock,
  XCircle
} from 'lucide-react';
import ApiConfig from '@/config/api';

interface CommissionRecord {
  id: string;
  referrerUserId: string;
  referrerUsername: string;
  referredUserId: string;
  referredUsername: string;
  level: 1 | 2 | 3;
  purchaseAmount: number;
  commissionUSDT: number;
  commissionNFT: number;
  status: 'pending' | 'paid' | 'cancelled';
  transactionHash?: string;
  createdAt: string;
  paidAt?: string;
}

interface ReferralStats {
  totalCommissions: number;
  pendingCommissions: number;
  paidCommissions: number;
  totalReferrals: number;
  totalNFTBonuses: number;
  thisMonthCommissions: number;
}

const ReferralManager: React.FC = () => {
  const [commissions, setCommissions] = useState<CommissionRecord[]>([]);
  const [stats, setStats] = useState<ReferralStats>({
    totalCommissions: 0,
    pendingCommissions: 0,
    paidCommissions: 0,
    totalReferrals: 0,
    totalNFTBonuses: 0,
    thisMonthCommissions: 0
  });
  const [isLoading, setIsLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState<'all' | 'pending' | 'paid' | 'cancelled'>('all');
  const [isProcessingPayout, setIsProcessingPayout] = useState<string | null>(null);
  const { toast } = useToast();

  const fetchCommissions = async () => {
    setIsLoading(true);
    try {
      const response = await fetch(`${ApiConfig.endpoints.referrals.history}?admin=true`);
      const data = await response.json();

      if (data.success) {
        setCommissions(data.data || []);
        
        // Calculate stats
        const totalCommissions = data.data.reduce((sum: number, c: CommissionRecord) => sum + c.commissionUSDT, 0);
        const pendingCommissions = data.data
          .filter((c: CommissionRecord) => c.status === 'pending')
          .reduce((sum: number, c: CommissionRecord) => sum + c.commissionUSDT, 0);
        const paidCommissions = data.data
          .filter((c: CommissionRecord) => c.status === 'paid')
          .reduce((sum: number, c: CommissionRecord) => sum + c.commissionUSDT, 0);
        const totalNFTBonuses = data.data.reduce((sum: number, c: CommissionRecord) => sum + c.commissionNFT, 0);
        
        setStats({
          totalCommissions,
          pendingCommissions,
          paidCommissions,
          totalReferrals: data.data.length,
          totalNFTBonuses,
          thisMonthCommissions: data.data
            .filter((c: CommissionRecord) => new Date(c.createdAt).getMonth() === new Date().getMonth())
            .reduce((sum: number, c: CommissionRecord) => sum + c.commissionUSDT, 0)
        });
      } else {
        throw new Error(data.error || 'Failed to fetch commissions');
      }
    } catch (error) {
      console.error('Failed to fetch commissions:', error);
      toast({
        title: "Error",
        description: "Failed to load referral commissions",
        variant: "destructive"
      });
    } finally {
      setIsLoading(false);
    }
  };

  const processCommissionPayout = async (commissionId: string) => {
    setIsProcessingPayout(commissionId);
    try {
      const response = await fetch(ApiConfig.endpoints.referrals.payout, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          commissionId,
          action: 'pay'
        })
      });

      const result = await response.json();

      if (result.success) {
        toast({
          title: "Payout Processed",
          description: "Commission has been marked as paid",
        });
        fetchCommissions(); // Refresh the list
      } else {
        throw new Error(result.error || 'Failed to process payout');
      }
    } catch (error) {
      console.error('Failed to process payout:', error);
      toast({
        title: "Payout Failed",
        description: "Failed to process commission payout",
        variant: "destructive"
      });
    } finally {
      setIsProcessingPayout(null);
    }
  };

  const cancelCommission = async (commissionId: string) => {
    try {
      const response = await fetch(ApiConfig.endpoints.referrals.payout, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          commissionId,
          action: 'cancel'
        })
      });

      const result = await response.json();

      if (result.success) {
        toast({
          title: "Commission Cancelled",
          description: "Commission has been cancelled",
        });
        fetchCommissions(); // Refresh the list
      } else {
        throw new Error(result.error || 'Failed to cancel commission');
      }
    } catch (error) {
      console.error('Failed to cancel commission:', error);
      toast({
        title: "Cancellation Failed",
        description: "Failed to cancel commission",
        variant: "destructive"
      });
    }
  };

  const exportCommissions = () => {
    const csvContent = [
      ['ID', 'Referrer', 'Referred User', 'Level', 'Purchase Amount', 'USDT Commission', 'NFT Bonus', 'Status', 'Date'],
      ...filteredCommissions.map(c => [
        c.id,
        c.referrerUsername,
        c.referredUsername,
        c.level.toString(),
        c.purchaseAmount.toString(),
        c.commissionUSDT.toString(),
        c.commissionNFT.toString(),
        c.status,
        new Date(c.createdAt).toLocaleDateString()
      ])
    ].map(row => row.join(',')).join('\n');

    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `referral-commissions-${new Date().toISOString().split('T')[0]}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  };

  useEffect(() => {
    fetchCommissions();
  }, []);

  const filteredCommissions = commissions.filter(commission => {
    const matchesSearch = commission.referrerUsername.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         commission.referredUsername.toLowerCase().includes(searchTerm.toLowerCase());
    const matchesStatus = statusFilter === 'all' || commission.status === statusFilter;
    return matchesSearch && matchesStatus;
  });

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'paid': return <CheckCircle className="h-4 w-4 text-green-400" />;
      case 'pending': return <Clock className="h-4 w-4 text-yellow-400" />;
      case 'cancelled': return <XCircle className="h-4 w-4 text-red-400" />;
      default: return <Clock className="h-4 w-4 text-gray-400" />;
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold text-white">Referral Management</h2>
          <p className="text-gray-400">Manage referral commissions and payouts</p>
        </div>
        <div className="flex items-center gap-2">
          <Button onClick={exportCommissions} variant="outline" size="sm">
            <Download className="h-4 w-4 mr-2" />
            Export CSV
          </Button>
          <Button onClick={fetchCommissions} variant="outline" size="sm" disabled={isLoading}>
            <RefreshCw className={`h-4 w-4 mr-2 ${isLoading ? 'animate-spin' : ''}`} />
            Refresh
          </Button>
        </div>
      </div>

      {/* Stats Overview */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <Card className="bg-gray-800 border-gray-700">
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <DollarSign className="h-8 w-8 text-green-400" />
              <div>
                <p className="text-sm text-gray-400">Total Commissions</p>
                <p className="text-2xl font-bold text-white">${stats.totalCommissions.toFixed(2)}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card className="bg-gray-800 border-gray-700">
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <Clock className="h-8 w-8 text-yellow-400" />
              <div>
                <p className="text-sm text-gray-400">Pending Payouts</p>
                <p className="text-2xl font-bold text-white">${stats.pendingCommissions.toFixed(2)}</p>
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
                <p className="text-2xl font-bold text-white">{stats.totalNFTBonuses}</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Filters */}
      <Card className="bg-gray-800 border-gray-700">
        <CardContent className="p-4">
          <div className="flex items-center gap-4">
            <div className="flex-1">
              <div className="relative">
                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                <Input
                  placeholder="Search by username..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="pl-10 bg-gray-700 border-gray-600 text-white"
                />
              </div>
            </div>
            <div className="flex items-center gap-2">
              {['all', 'pending', 'paid', 'cancelled'].map((status) => (
                <Button
                  key={status}
                  variant={statusFilter === status ? "default" : "outline"}
                  size="sm"
                  onClick={() => setStatusFilter(status as any)}
                  className="capitalize"
                >
                  {status}
                </Button>
              ))}
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Commissions Table */}
      <Card className="bg-gray-800 border-gray-700">
        <CardHeader>
          <CardTitle className="text-white flex items-center gap-2">
            <Award className="h-5 w-5 text-gold" />
            Commission Records ({filteredCommissions.length})
          </CardTitle>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <div className="text-center py-8">
              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-gold mx-auto mb-4"></div>
              <p className="text-gray-400">Loading commissions...</p>
            </div>
          ) : filteredCommissions.length === 0 ? (
            <div className="text-center py-8">
              <Users className="h-12 w-12 text-gray-400 mx-auto mb-4" />
              <p className="text-gray-400">No commission records found</p>
              <p className="text-sm text-gray-500 mt-1">Commission records will appear here as referrals make purchases</p>
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead>
                  <tr className="border-b border-gray-700">
                    <th className="text-left py-3 px-4 text-gray-400 font-medium">Referrer</th>
                    <th className="text-left py-3 px-4 text-gray-400 font-medium">Referred User</th>
                    <th className="text-left py-3 px-4 text-gray-400 font-medium">Level</th>
                    <th className="text-left py-3 px-4 text-gray-400 font-medium">Purchase</th>
                    <th className="text-left py-3 px-4 text-gray-400 font-medium">USDT Commission</th>
                    <th className="text-left py-3 px-4 text-gray-400 font-medium">NFT Bonus</th>
                    <th className="text-left py-3 px-4 text-gray-400 font-medium">Status</th>
                    <th className="text-left py-3 px-4 text-gray-400 font-medium">Date</th>
                    <th className="text-left py-3 px-4 text-gray-400 font-medium">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {filteredCommissions.map((commission) => (
                    <tr key={commission.id} className="border-b border-gray-700/50 hover:bg-gray-700/30">
                      <td className="py-3 px-4 text-white font-medium">{commission.referrerUsername}</td>
                      <td className="py-3 px-4 text-white">{commission.referredUsername}</td>
                      <td className="py-3 px-4">
                        <Badge className={`
                          ${commission.level === 1 ? 'bg-blue-500/20 text-blue-400' :
                            commission.level === 2 ? 'bg-green-500/20 text-green-400' :
                            'bg-purple-500/20 text-purple-400'}
                        `}>
                          Level {commission.level}
                        </Badge>
                      </td>
                      <td className="py-3 px-4 text-white">${commission.purchaseAmount.toLocaleString()}</td>
                      <td className="py-3 px-4 text-green-400 font-semibold">${commission.commissionUSDT.toFixed(2)}</td>
                      <td className="py-3 px-4 text-blue-400">{commission.commissionNFT} NFTs</td>
                      <td className="py-3 px-4">
                        <div className="flex items-center gap-2">
                          {getStatusIcon(commission.status)}
                          <Badge className={`
                            ${commission.status === 'paid' ? 'bg-green-500/20 text-green-400' :
                              commission.status === 'pending' ? 'bg-yellow-500/20 text-yellow-400' :
                              'bg-red-500/20 text-red-400'}
                          `}>
                            {commission.status.charAt(0).toUpperCase() + commission.status.slice(1)}
                          </Badge>
                        </div>
                      </td>
                      <td className="py-3 px-4 text-gray-400">
                        <div className="text-sm">
                          <div>{new Date(commission.createdAt).toLocaleDateString()}</div>
                          {commission.paidAt && (
                            <div className="text-xs text-green-400">
                              Paid: {new Date(commission.paidAt).toLocaleDateString()}
                            </div>
                          )}
                        </div>
                      </td>
                      <td className="py-3 px-4">
                        <div className="flex items-center gap-2">
                          {commission.status === 'pending' && (
                            <>
                              <Button
                                size="sm"
                                onClick={() => processCommissionPayout(commission.id)}
                                disabled={isProcessingPayout === commission.id}
                                className="bg-green-600 hover:bg-green-700 text-white"
                              >
                                {isProcessingPayout === commission.id ? (
                                  <div className="animate-spin rounded-full h-3 w-3 border-b-2 border-white"></div>
                                ) : (
                                  'Pay'
                                )}
                              </Button>
                              <Button
                                size="sm"
                                variant="outline"
                                onClick={() => cancelCommission(commission.id)}
                                className="border-red-500/30 text-red-400 hover:bg-red-500/20"
                              >
                                Cancel
                              </Button>
                            </>
                          )}
                          {commission.status === 'paid' && (
                            <Badge className="bg-green-500/20 text-green-400">
                              ✓ Completed
                            </Badge>
                          )}
                          {commission.status === 'cancelled' && (
                            <Badge className="bg-red-500/20 text-red-400">
                              ✗ Cancelled
                            </Badge>
                          )}
                        </div>
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
  );
};

export default ReferralManager;

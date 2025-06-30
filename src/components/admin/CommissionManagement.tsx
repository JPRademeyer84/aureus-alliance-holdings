import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useToast } from '@/hooks/use-toast';
import {
  DollarSign,
  Gift,
  CheckCircle,
  XCircle,
  Clock,
  RefreshCw,
  AlertTriangle,
  TrendingUp,
  Users,
  ArrowUpRight
} from 'lucide-react';

interface CommissionRecord {
  id: string;
  referrer_username: string;
  referred_username: string;
  level: number;
  purchase_amount: number;
  commission_usdt: number;
  commission_nft: number;
  status: 'pending' | 'paid' | 'cancelled';
  created_at: string;
  investment_id: string;
}

interface WithdrawalRequest {
  id: string;
  user_username: string;
  withdrawal_type: 'usdt' | 'nft' | 'reinvest';
  amount: number;
  nft_quantity: number;
  wallet_address: string;
  status: 'pending' | 'processing' | 'completed' | 'failed' | 'cancelled';
  requested_at: string;
}

const CommissionManagement: React.FC = () => {
  const { toast } = useToast();
  
  const [commissions, setCommissions] = useState<CommissionRecord[]>([]);
  const [withdrawalRequests, setWithdrawalRequests] = useState<WithdrawalRequest[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [selectedTab, setSelectedTab] = useState<'commissions' | 'withdrawals'>('commissions');
  const [processingId, setProcessingId] = useState<string | null>(null);
  
  const [processForm, setProcessForm] = useState({
    withdrawal_id: '',
    status: 'completed' as 'completed' | 'failed' | 'cancelled',
    transaction_hash: '',
    admin_notes: ''
  });

  useEffect(() => {
    fetchCommissionData();
  }, []);

  const fetchCommissionData = async () => {
    setIsLoading(true);
    try {
      // Fetch commission records
      const commissionsResponse = await fetch('http://localhost/aureus-angel-alliance/api/admin/commission-records.php', {
        method: 'GET',
        credentials: 'include'
      });

      if (commissionsResponse.ok) {
        const commissionsData = await commissionsResponse.json();
        if (commissionsData.success) {
          setCommissions(commissionsData.commissions);
        }
      }

      // Fetch withdrawal requests
      const withdrawalsResponse = await fetch('http://localhost/aureus-angel-alliance/api/admin/withdrawal-requests.php', {
        method: 'GET',
        credentials: 'include'
      });

      if (withdrawalsResponse.ok) {
        const withdrawalsData = await withdrawalsResponse.json();
        if (withdrawalsData.success) {
          setWithdrawalRequests(withdrawalsData.requests);
        }
      }

    } catch (error) {
      console.error('Failed to fetch commission data:', error);
      toast({
        title: "Error",
        description: "Failed to load commission data",
        variant: "destructive"
      });
    } finally {
      setIsLoading(false);
    }
  };

  const activateCommissions = async (type: 'all' | 'selected', commissionIds?: string[]) => {
    try {
      const response = await fetch('/api/referrals/activate-commissions.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({
          action: type === 'all' ? 'activate_pending' : 'activate_specific',
          commission_ids: commissionIds
        })
      });

      const data = await response.json();

      if (data.success) {
        toast({
          title: "Commissions Activated",
          description: `Successfully activated ${data.activated_count} commission records`,
        });
        fetchCommissionData();
      } else {
        throw new Error(data.error || 'Failed to activate commissions');
      }
    } catch (error) {
      console.error('Commission activation failed:', error);
      toast({
        title: "Activation Failed",
        description: error instanceof Error ? error.message : "Failed to activate commissions",
        variant: "destructive"
      });
    }
  };

  const processWithdrawal = async (withdrawalId: string) => {
    setProcessingId(withdrawalId);
    
    try {
      const response = await fetch('/api/referrals/payout.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({
          action: 'admin_process',
          withdrawal_id: withdrawalId,
          status: processForm.status,
          transaction_hash: processForm.transaction_hash,
          admin_notes: processForm.admin_notes
        })
      });

      const data = await response.json();

      if (data.success) {
        toast({
          title: "Withdrawal Processed",
          description: `Withdrawal has been ${processForm.status}`,
        });
        setProcessForm({
          withdrawal_id: '',
          status: 'completed',
          transaction_hash: '',
          admin_notes: ''
        });
        fetchCommissionData();
      } else {
        throw new Error(data.error || 'Failed to process withdrawal');
      }
    } catch (error) {
      console.error('Withdrawal processing failed:', error);
      toast({
        title: "Processing Failed",
        description: error instanceof Error ? error.message : "Failed to process withdrawal",
        variant: "destructive"
      });
    } finally {
      setProcessingId(null);
    }
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'completed':
      case 'paid':
        return <CheckCircle className="h-4 w-4 text-green-400" />;
      case 'pending':
        return <Clock className="h-4 w-4 text-yellow-400" />;
      case 'processing':
        return <RefreshCw className="h-4 w-4 text-blue-400 animate-spin" />;
      case 'failed':
      case 'cancelled':
        return <XCircle className="h-4 w-4 text-red-400" />;
      default:
        return <AlertTriangle className="h-4 w-4 text-gray-400" />;
    }
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'completed':
      case 'paid':
        return 'bg-green-500/20 text-green-400';
      case 'pending':
        return 'bg-yellow-500/20 text-yellow-400';
      case 'processing':
        return 'bg-blue-500/20 text-blue-400';
      case 'failed':
      case 'cancelled':
        return 'bg-red-500/20 text-red-400';
      default:
        return 'bg-gray-500/20 text-gray-400';
    }
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-12">
        <RefreshCw className="h-8 w-8 animate-spin text-gold" />
      </div>
    );
  }

  const pendingCommissions = commissions.filter(c => c.status === 'pending');
  const pendingWithdrawals = withdrawalRequests.filter(w => w.status === 'pending');

  return (
    <div className="space-y-6">
      {/* Summary Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card className="bg-gray-800 border-gray-700">
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <TrendingUp className="h-8 w-8 text-gold" />
              <div>
                <p className="text-sm text-gray-400">Total Commissions</p>
                <p className="text-2xl font-bold text-white">{commissions.length}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card className="bg-gray-800 border-gray-700">
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <Clock className="h-8 w-8 text-yellow-400" />
              <div>
                <p className="text-sm text-gray-400">Pending Commissions</p>
                <p className="text-2xl font-bold text-white">{pendingCommissions.length}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card className="bg-gray-800 border-gray-700">
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <ArrowUpRight className="h-8 w-8 text-blue-400" />
              <div>
                <p className="text-sm text-gray-400">Withdrawal Requests</p>
                <p className="text-2xl font-bold text-white">{withdrawalRequests.length}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card className="bg-gray-800 border-gray-700">
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <AlertTriangle className="h-8 w-8 text-orange-400" />
              <div>
                <p className="text-sm text-gray-400">Pending Withdrawals</p>
                <p className="text-2xl font-bold text-white">{pendingWithdrawals.length}</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Tab Navigation */}
      <div className="flex space-x-4">
        <Button
          onClick={() => setSelectedTab('commissions')}
          variant={selectedTab === 'commissions' ? 'default' : 'outline'}
          className={selectedTab === 'commissions' ? 'bg-gold-gradient text-black' : 'border-gray-600'}
        >
          Commission Records
        </Button>
        <Button
          onClick={() => setSelectedTab('withdrawals')}
          variant={selectedTab === 'withdrawals' ? 'default' : 'outline'}
          className={selectedTab === 'withdrawals' ? 'bg-gold-gradient text-black' : 'border-gray-600'}
        >
          Withdrawal Requests
        </Button>
      </div>

      {/* Commission Records Tab */}
      {selectedTab === 'commissions' && (
        <Card className="bg-gray-800 border-gray-700">
          <CardHeader>
            <div className="flex justify-between items-center">
              <CardTitle className="text-white">Commission Records</CardTitle>
              <div className="flex gap-2">
                <Button 
                  onClick={() => activateCommissions('all')}
                  className="bg-green-600 hover:bg-green-700"
                  disabled={pendingCommissions.length === 0}
                >
                  <CheckCircle className="h-4 w-4 mr-2" />
                  Activate All Pending
                </Button>
                <Button onClick={fetchCommissionData} variant="outline" className="border-gray-600">
                  <RefreshCw className="h-4 w-4 mr-2" />
                  Refresh
                </Button>
              </div>
            </div>
          </CardHeader>
          <CardContent>
            {commissions.length === 0 ? (
              <div className="text-center py-8">
                <Users className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                <p className="text-gray-400">No commission records found</p>
              </div>
            ) : (
              <div className="space-y-3">
                {commissions.map((commission) => (
                  <div key={commission.id} className="flex items-center justify-between p-4 bg-gray-700 rounded-lg">
                    <div className="flex items-center gap-4">
                      {getStatusIcon(commission.status)}
                      <div>
                        <p className="text-white font-medium">
                          {commission.referrer_username} → {commission.referred_username}
                        </p>
                        <p className="text-sm text-gray-400">
                          Level {commission.level} • ${commission.purchase_amount} purchase
                        </p>
                      </div>
                    </div>
                    <div className="flex items-center gap-4">
                      <div className="text-right">
                        <p className="text-white font-medium">${commission.commission_usdt.toFixed(2)} USDT</p>
                        <p className="text-sm text-purple-400">{commission.commission_nft} NFT packs</p>
                      </div>
                      <Badge className={getStatusColor(commission.status)}>
                        {commission.status.charAt(0).toUpperCase() + commission.status.slice(1)}
                      </Badge>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>
      )}

      {/* Withdrawal Requests Tab */}
      {selectedTab === 'withdrawals' && (
        <Card className="bg-gray-800 border-gray-700">
          <CardHeader>
            <CardTitle className="text-white">Withdrawal Requests</CardTitle>
          </CardHeader>
          <CardContent>
            {withdrawalRequests.length === 0 ? (
              <div className="text-center py-8">
                <ArrowUpRight className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                <p className="text-gray-400">No withdrawal requests found</p>
              </div>
            ) : (
              <div className="space-y-4">
                {withdrawalRequests.map((request) => (
                  <div key={request.id} className="p-4 bg-gray-700 rounded-lg">
                    <div className="flex items-center justify-between mb-3">
                      <div className="flex items-center gap-3">
                        {getStatusIcon(request.status)}
                        <div>
                          <p className="text-white font-medium">{request.user_username}</p>
                          <p className="text-sm text-gray-400">
                            {request.withdrawal_type.toUpperCase()} - 
                            {request.withdrawal_type === 'usdt' ? ` $${request.amount.toFixed(2)}` : ` ${request.nft_quantity} NFTs`}
                          </p>
                        </div>
                      </div>
                      <Badge className={getStatusColor(request.status)}>
                        {request.status.charAt(0).toUpperCase() + request.status.slice(1)}
                      </Badge>
                    </div>
                    
                    {request.status === 'pending' && (
                      <div className="mt-4 p-3 bg-gray-600 rounded space-y-3">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                          <div>
                            <Label className="text-white">Status</Label>
                            <select
                              value={processForm.status}
                              onChange={(e) => setProcessForm({...processForm, status: e.target.value as any})}
                              className="w-full p-2 bg-gray-500 border border-gray-400 rounded text-white"
                            >
                              <option value="completed">Completed</option>
                              <option value="failed">Failed</option>
                              <option value="cancelled">Cancelled</option>
                            </select>
                          </div>
                          <div>
                            <Label className="text-white">Transaction Hash</Label>
                            <Input
                              value={processForm.transaction_hash}
                              onChange={(e) => setProcessForm({...processForm, transaction_hash: e.target.value})}
                              className="bg-gray-500 border-gray-400 text-white"
                              placeholder="0x..."
                            />
                          </div>
                        </div>
                        <div>
                          <Label className="text-white">Admin Notes</Label>
                          <Textarea
                            value={processForm.admin_notes}
                            onChange={(e) => setProcessForm({...processForm, admin_notes: e.target.value})}
                            className="bg-gray-500 border-gray-400 text-white"
                            placeholder="Optional notes..."
                          />
                        </div>
                        <Button
                          onClick={() => processWithdrawal(request.id)}
                          disabled={processingId === request.id}
                          className="bg-gold-gradient text-black"
                        >
                          {processingId === request.id ? (
                            <RefreshCw className="h-4 w-4 animate-spin mr-2" />
                          ) : (
                            <CheckCircle className="h-4 w-4 mr-2" />
                          )}
                          Process Withdrawal
                        </Button>
                      </div>
                    )}
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>
      )}
    </div>
  );
};

export default CommissionManagement;

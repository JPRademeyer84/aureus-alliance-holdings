import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useToast } from '@/hooks/use-toast';
import {
  Wallet,
  DollarSign,
  Gift,
  ArrowUpRight,
  ArrowDownLeft,
  RefreshCw,
  AlertCircle,
  CheckCircle,
  Clock,
  TrendingUp
} from 'lucide-react';
import { useUser } from '@/contexts/UserContext';
import { useSimpleTranslation as useTranslation, ST as T } from '@/components/SimpleTranslator';
import ApiConfig from '@/config/api';

interface CommissionBalance {
  total_usdt_earned: number;
  total_nft_earned: number;
  available_usdt_balance: number;
  available_nft_balance: number;
  total_usdt_withdrawn: number;
  total_nft_redeemed: number;
}

interface WithdrawalRequest {
  id: string;
  withdrawal_type: 'usdt' | 'nft' | 'reinvest';
  amount: number;
  nft_quantity: number;
  wallet_address: string;
  status: 'pending' | 'processing' | 'completed' | 'failed' | 'cancelled';
  requested_at: string;
  transaction_hash?: string;
}

const CommissionWallet: React.FC = () => {
  const { user } = useUser();
  const { toast } = useToast();
  const { translate } = useTranslation();
  
  const [balance, setBalance] = useState<CommissionBalance>({
    total_usdt_earned: 0,
    total_nft_earned: 0,
    available_usdt_balance: 0,
    available_nft_balance: 0,
    total_usdt_withdrawn: 0,
    total_nft_redeemed: 0
  });
  
  const [withdrawalRequests, setWithdrawalRequests] = useState<WithdrawalRequest[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isWithdrawing, setIsWithdrawing] = useState(false);
  const [showWithdrawForm, setShowWithdrawForm] = useState(false);
  const [withdrawalForm, setWithdrawalForm] = useState({
    type: 'usdt' as 'usdt' | 'nft' | 'reinvest',
    amount: '',
    nft_quantity: '',
    wallet_address: ''
  });

  useEffect(() => {
    if (user?.id) {
      fetchCommissionData();
    }
  }, [user]);

  const fetchCommissionData = async () => {
    setIsLoading(true);
    try {
      // Fetch commission balance from correct API
      const balanceResponse = await fetch(`${ApiConfig.baseUrl}/referrals/commission-balance.php`, {
        method: 'GET',
        credentials: 'include'
      });

      if (balanceResponse.ok) {
        const balanceData = await balanceResponse.json();
        if (balanceData.success) {
          setBalance(balanceData.balance);
        }
      }

      // Fetch withdrawal history from correct API
      const withdrawalsResponse = await fetch(`${ApiConfig.baseUrl}/referrals/withdrawal-history.php`, {
        method: 'GET',
        credentials: 'include'
      });

      if (withdrawalsResponse.ok) {
        const withdrawalsData = await withdrawalsResponse.json();
        if (withdrawalsData.success) {
          setWithdrawalRequests(withdrawalsData.withdrawals);
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

  const handleWithdrawalRequest = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsWithdrawing(true);

    try {
      // Handle reinvestment differently
      if (withdrawalForm.type === 'reinvest') {
        const response = await fetch(`${ApiConfig.baseUrl}/referrals/reinvest.php`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          credentials: 'include',
          body: JSON.stringify({
            amount: parseFloat(withdrawalForm.amount) || 0,
            type: parseFloat(withdrawalForm.amount) > 0 ? 'usdt' : 'nft',
            nft_quantity: parseInt(withdrawalForm.nft_quantity) || 0
          })
        });

        const data = await response.json();

        if (data.success) {
          toast({
            title: "Reinvestment Successful",
            description: data.message,
          });
          setShowWithdrawForm(false);
          setWithdrawalForm({
            type: 'usdt',
            amount: '',
            nft_quantity: '',
            wallet_address: ''
          });
          fetchCommissionData(); // Refresh data
        } else {
          throw new Error(data.error || 'Failed to process reinvestment');
        }
      } else {
        // Handle regular withdrawal
        const response = await fetch(ApiConfig.endpoints.referrals.payout, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          credentials: 'include',
          body: JSON.stringify({
            action: 'request_withdrawal',
            type: withdrawalForm.type,
            amount: parseFloat(withdrawalForm.amount) || 0,
            nft_quantity: parseInt(withdrawalForm.nft_quantity) || 0,
            wallet_address: withdrawalForm.wallet_address
          })
        });

        const data = await response.json();

        if (data.success) {
          toast({
            title: "Withdrawal Requested",
            description: data.message || "Your withdrawal request has been submitted for admin approval",
          });
          setShowWithdrawForm(false);
          setWithdrawalForm({
            type: 'usdt',
            amount: '',
            nft_quantity: '',
            wallet_address: ''
          });
          fetchCommissionData(); // Refresh data
        } else {
          throw new Error(data.error || 'Failed to submit withdrawal request');
        }
      }
    } catch (error) {
      console.error('Request failed:', error);
      toast({
        title: withdrawalForm.type === 'reinvest' ? "Reinvestment Failed" : "Withdrawal Failed",
        description: error instanceof Error ? error.message : "Failed to process request",
        variant: "destructive"
      });
    } finally {
      setIsWithdrawing(false);
    }
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'completed':
        return <CheckCircle className="h-4 w-4 text-green-400" />;
      case 'pending':
        return <Clock className="h-4 w-4 text-yellow-400" />;
      case 'processing':
        return <RefreshCw className="h-4 w-4 text-blue-400 animate-spin" />;
      case 'failed':
      case 'cancelled':
        return <AlertCircle className="h-4 w-4 text-red-400" />;
      default:
        return <Clock className="h-4 w-4 text-gray-400" />;
    }
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'completed':
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

  return (
    <div className="space-y-6">
      {/* Commission Balance Overview */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <Card className="bg-gray-800 border-gray-700">
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <DollarSign className="h-8 w-8 text-green-400" />
              <div>
                <p className="text-sm text-gray-400">
                  <T k="available_usdt" fallback="Available USDT" />
                </p>
                <p className="text-2xl font-bold text-white">${(balance?.available_usdt_balance || 0).toFixed(2)}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card className="bg-gray-800 border-gray-700">
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <Gift className="h-8 w-8 text-purple-400" />
              <div>
                <p className="text-sm text-gray-400">
                  <T k="available_nfts" fallback="Available NFTs" />
                </p>
                <p className="text-2xl font-bold text-white">{balance?.available_nft_balance || 0}</p>
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
                  <T k="total_earned_usdt" fallback="Total Earned USDT" />
                </p>
                <p className="text-2xl font-bold text-white">${(balance?.total_usdt_earned || 0).toFixed(2)}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card className="bg-gray-800 border-gray-700">
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <ArrowUpRight className="h-8 w-8 text-blue-400" />
              <div>
                <p className="text-sm text-gray-400">
                  <T k="total_withdrawn" fallback="Total Withdrawn" />
                </p>
                <p className="text-2xl font-bold text-white">${(balance?.total_usdt_withdrawn || 0).toFixed(2)}</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Withdrawal Actions */}
      <Card className="bg-gray-800 border-gray-700">
        <CardHeader>
          <CardTitle className="text-white flex items-center gap-2">
            <Wallet className="h-5 w-5 text-gold" />
            Commission Wallet Actions
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="flex gap-4">
            <Button 
              onClick={() => setShowWithdrawForm(true)}
              className="bg-green-600 hover:bg-green-700"
              disabled={(balance?.available_usdt_balance || 0) <= 0 && (balance?.available_nft_balance || 0) <= 0}
            >
              <ArrowUpRight className="h-4 w-4 mr-2" />
              Request Withdrawal
            </Button>
            <Button 
              onClick={fetchCommissionData}
              variant="outline"
              className="border-gray-600"
            >
              <RefreshCw className="h-4 w-4 mr-2" />
              Refresh Balance
            </Button>
          </div>

          {showWithdrawForm && (
            <form onSubmit={handleWithdrawalRequest} className="space-y-4 p-4 bg-gray-700 rounded-lg">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <Label htmlFor="type" className="text-white">Withdrawal Type</Label>
                  <select
                    id="type"
                    value={withdrawalForm.type}
                    onChange={(e) => setWithdrawalForm({...withdrawalForm, type: e.target.value as 'usdt' | 'nft' | 'reinvest'})}
                    className="w-full p-2 bg-gray-600 border border-gray-500 rounded text-white"
                  >
                    <option value="usdt">USDT Withdrawal</option>
                    <option value="nft">NFT Redemption</option>
                    <option value="reinvest">Reinvest in More NFTs</option>
                  </select>
                </div>

                {withdrawalForm.type === 'usdt' && (
                  <div>
                    <Label htmlFor="amount" className="text-white">USDT Amount</Label>
                    <Input
                      id="amount"
                      type="number"
                      step="0.01"
                      max={balance?.available_usdt_balance || 0}
                      value={withdrawalForm.amount}
                      onChange={(e) => setWithdrawalForm({...withdrawalForm, amount: e.target.value})}
                      className="bg-gray-600 border-gray-500 text-white"
                      placeholder="0.00"
                    />
                  </div>
                )}

                {withdrawalForm.type === 'nft' && (
                  <div>
                    <Label htmlFor="nft_quantity" className="text-white">NFT Quantity</Label>
                    <Input
                      id="nft_quantity"
                      type="number"
                      min="1"
                      max={balance?.available_nft_balance || 0}
                      value={withdrawalForm.nft_quantity}
                      onChange={(e) => setWithdrawalForm({...withdrawalForm, nft_quantity: e.target.value})}
                      className="bg-gray-600 border-gray-500 text-white"
                      placeholder="1"
                    />
                  </div>
                )}
              </div>

              <div>
                <Label htmlFor="wallet_address" className="text-white">Wallet Address</Label>
                <Input
                  id="wallet_address"
                  value={withdrawalForm.wallet_address}
                  onChange={(e) => setWithdrawalForm({...withdrawalForm, wallet_address: e.target.value})}
                  className="bg-gray-600 border-gray-500 text-white"
                  placeholder="0x..."
                  required
                />
              </div>

              <div className="flex gap-2">
                <Button type="submit" disabled={isWithdrawing} className="bg-gold-gradient text-black">
                  {isWithdrawing ? <RefreshCw className="h-4 w-4 animate-spin mr-2" /> : null}
                  Submit Request
                </Button>
                <Button type="button" onClick={() => setShowWithdrawForm(false)} variant="outline">
                  Cancel
                </Button>
              </div>
            </form>
          )}
        </CardContent>
      </Card>

      {/* Withdrawal History */}
      <Card className="bg-gray-800 border-gray-700">
        <CardHeader>
          <CardTitle className="text-white">Withdrawal History</CardTitle>
        </CardHeader>
        <CardContent>
          {withdrawalRequests.length === 0 ? (
            <div className="text-center py-8">
              <ArrowDownLeft className="h-12 w-12 text-gray-400 mx-auto mb-4" />
              <p className="text-gray-400">No withdrawal requests yet</p>
            </div>
          ) : (
            <div className="space-y-3">
              {withdrawalRequests.map((request) => (
                <div key={request.id} className="flex items-center justify-between p-3 bg-gray-700 rounded-lg">
                  <div className="flex items-center gap-3">
                    {getStatusIcon(request.status)}
                    <div>
                      <p className="text-white font-medium">
                        {request.withdrawal_type.toUpperCase()} - 
                        {request.withdrawal_type === 'usdt' ? ` $${(request.amount || 0).toFixed(2)}` : ` ${request.nft_quantity || 0} NFTs`}
                      </p>
                      <p className="text-sm text-gray-400">
                        {new Date(request.requested_at).toLocaleDateString()}
                      </p>
                    </div>
                  </div>
                  <Badge className={getStatusColor(request.status)}>
                    {request.status.charAt(0).toUpperCase() + request.status.slice(1)}
                  </Badge>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
};

export default CommissionWallet;

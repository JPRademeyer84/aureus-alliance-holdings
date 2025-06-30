import React, { useState, useEffect } from 'react';
import {
  Gift,
  DollarSign,
  Clock,
  CheckCircle,
  AlertCircle,
  History,
  Wallet
} from 'lucide-react';
import ApiConfig from '@/config/api';

const CouponRedemption = () => {
  const [couponCode, setCouponCode] = useState('');
  const [loading, setLoading] = useState(false);
  const [credits, setCredits] = useState(null);
  const [creditHistory, setCreditHistory] = useState([]);
  const [message, setMessage] = useState({ type: '', text: '' });

  useEffect(() => {
    fetchUserCredits();
    fetchCreditHistory();
  }, []);

  const fetchUserCredits = async () => {
    try {
      const response = await fetch(`${ApiConfig.endpoints.coupons.index}?action=user_credits`, {
        credentials: 'include'
      });
      const data = await response.json();
      
      if (data.success) {
        setCredits(data.data);
      } else {
        console.error('Failed to fetch credits:', data.error);
      }
    } catch (error) {
      console.error('Error fetching credits:', error);
    }
  };

  const fetchCreditHistory = async () => {
    try {
      const response = await fetch(`${ApiConfig.endpoints.coupons.index}?action=credit_history`, {
        credentials: 'include'
      });
      const data = await response.json();

      if (data.success) {
        setCreditHistory(data.data);
      } else {
        console.error('Failed to fetch credit history:', data.error);
      }
    } catch (error) {
      console.error('Error fetching credit history:', error);
    }
  };

  const handleRedeemCoupon = async (e) => {
    e.preventDefault();
    
    if (!couponCode.trim()) {
      setMessage({ type: 'error', text: 'Please enter a coupon code' });
      return;
    }
    
    setLoading(true);
    setMessage({ type: '', text: '' });
    
    try {
      const response = await fetch(ApiConfig.endpoints.coupons.index, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({
          action: 'redeem_coupon',
          coupon_code: couponCode.trim().toUpperCase()
        })
      });
      
      const data = await response.json();
      
      if (data.success) {
        setMessage({ type: 'success', text: data.message });
        setCouponCode('');
        fetchUserCredits();
        fetchCreditHistory();
      } else {
        setMessage({ type: 'error', text: data.error });
      }
    } catch (error) {
      console.error('Error redeeming coupon:', error);
      setMessage({ type: 'error', text: 'Error redeeming coupon. Please try again.' });
    } finally {
      setLoading(false);
    }
  };

  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const getTransactionIcon = (type) => {
    switch (type) {
      case 'earned':
        return <Gift className="w-4 h-4 text-green-500" />;
      case 'spent':
        return <DollarSign className="w-4 h-4 text-red-500" />;
      case 'refunded':
        return <CheckCircle className="w-4 h-4 text-blue-500" />;
      default:
        return <Clock className="w-4 h-4 text-gray-500" />;
    }
  };

  const getTransactionColor = (type) => {
    switch (type) {
      case 'earned':
        return 'text-green-400';
      case 'spent':
        return 'text-red-400';
      case 'refunded':
        return 'text-blue-400';
      default:
        return 'text-gray-400';
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-white">NFT Coupons & Credits</h1>
        <p className="text-gray-400">Redeem coupons for NFT purchase credits</p>
      </div>

      {/* Credits Balance Card */}
      <div className="bg-gradient-to-r from-blue-600 to-purple-600 p-6 rounded-lg">
        <div className="flex items-center justify-between">
          <div>
            <h2 className="text-lg font-semibold text-white mb-2">Your Credit Balance</h2>
            <div className="flex items-center space-x-4">
              <div className="flex items-center">
                <Wallet className="w-5 h-5 text-white mr-2" />
                <span className="text-2xl font-bold text-white">
                  ${credits ? parseFloat(credits.available_credits).toFixed(2) : '0.00'}
                </span>
              </div>
              <div className="text-sm text-blue-100">
                Available Credits
              </div>
            </div>
          </div>
          <div className="text-right text-blue-100">
            <div className="text-sm">Total Earned: ${credits ? parseFloat(credits.total_credits).toFixed(2) : '0.00'}</div>
            <div className="text-sm">Total Used: ${credits ? parseFloat(credits.used_credits).toFixed(2) : '0.00'}</div>
          </div>
        </div>
      </div>

      {/* Coupon Redemption Form */}
      <div className="bg-gray-800 p-6 rounded-lg">
        <h2 className="text-xl font-semibold text-white mb-4 flex items-center">
          <Gift className="w-5 h-5 mr-2" />
          Redeem Coupon
        </h2>
        
        <form onSubmit={handleRedeemCoupon} className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-300 mb-2">
              Coupon Code
            </label>
            <div className="flex space-x-3">
              <input
                type="text"
                value={couponCode}
                onChange={(e) => setCouponCode(e.target.value.toUpperCase())}
                className="flex-1 px-4 py-2 bg-gray-700 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Enter your coupon code"
                disabled={loading}
              />
              <button
                type="submit"
                disabled={loading || !couponCode.trim()}
                className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                {loading ? 'Redeeming...' : 'Redeem'}
              </button>
            </div>
          </div>
          
          {message.text && (
            <div className={`p-3 rounded-lg flex items-center ${
              message.type === 'success' 
                ? 'bg-green-900 text-green-300' 
                : 'bg-red-900 text-red-300'
            }`}>
              {message.type === 'success' ? (
                <CheckCircle className="w-5 h-5 mr-2" />
              ) : (
                <AlertCircle className="w-5 h-5 mr-2" />
              )}
              {message.text}
            </div>
          )}
        </form>
      </div>

      {/* How It Works */}
      <div className="bg-gray-800 p-6 rounded-lg">
        <h2 className="text-xl font-semibold text-white mb-4">How NFT Credits Work</h2>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div className="text-center">
            <div className="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center mx-auto mb-3">
              <Gift className="w-6 h-6 text-white" />
            </div>
            <h3 className="font-semibold text-white mb-2">1. Redeem Coupons</h3>
            <p className="text-sm text-gray-400">
              Enter valid coupon codes to earn dollar credits
            </p>
          </div>
          
          <div className="text-center">
            <div className="w-12 h-12 bg-green-600 rounded-full flex items-center justify-center mx-auto mb-3">
              <DollarSign className="w-6 h-6 text-white" />
            </div>
            <h3 className="font-semibold text-white mb-2">2. Earn Credits</h3>
            <p className="text-sm text-gray-400">
              Credits are added to your account balance instantly
            </p>
          </div>
          
          <div className="text-center">
            <div className="w-12 h-12 bg-purple-600 rounded-full flex items-center justify-center mx-auto mb-3">
              <Wallet className="w-6 h-6 text-white" />
            </div>
            <h3 className="font-semibold text-white mb-2">3. Buy NFTs</h3>
            <p className="text-sm text-gray-400">
              Use credits to purchase NFTs just like real USDT
            </p>
          </div>
        </div>
      </div>

      {/* Credit History */}
      <div className="bg-gray-800 rounded-lg">
        <div className="p-6 border-b border-gray-700">
          <h2 className="text-xl font-semibold text-white flex items-center">
            <History className="w-5 h-5 mr-2" />
            Credit History
          </h2>
        </div>
        
        <div className="divide-y divide-gray-700">
          {creditHistory.length > 0 ? (
            creditHistory.map((transaction) => (
              <div key={transaction.id} className="p-6 flex items-center justify-between">
                <div className="flex items-center space-x-3">
                  {getTransactionIcon(transaction.transaction_type)}
                  <div>
                    <p className="text-white font-medium">{transaction.description}</p>
                    <p className="text-sm text-gray-400">
                      {formatDate(transaction.created_at)}
                      {transaction.coupon_code && (
                        <span className="ml-2 px-2 py-1 bg-blue-900 text-blue-300 text-xs rounded">
                          {transaction.coupon_code}
                        </span>
                      )}
                    </p>
                  </div>
                </div>
                <div className={`text-lg font-semibold ${getTransactionColor(transaction.transaction_type)}`}>
                  {transaction.transaction_type === 'earned' ? '+' : '-'}
                  ${parseFloat(transaction.amount).toFixed(2)}
                </div>
              </div>
            ))
          ) : (
            <div className="p-6 text-center text-gray-400">
              <History className="w-12 h-12 mx-auto mb-3 opacity-50" />
              <p>No credit transactions yet</p>
              <p className="text-sm">Redeem your first coupon to get started!</p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default CouponRedemption;

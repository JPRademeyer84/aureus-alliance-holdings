import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { useToast } from "@/hooks/use-toast";
import { ST as T } from "@/components/SimpleTranslator";
import ApiConfig from "@/config/api";
import {
  Clock,
  CheckCircle,
  XCircle,
  AlertTriangle,
  Eye,
  RefreshCw,
  DollarSign,
  Calendar,
  Wallet,
  FileText,
  Mail
} from "lucide-react";

interface ManualPayment {
  payment_id: string;
  amount_usd: number;
  chain: string;
  payment_status: string;
  verification_status: string;
  created_at: string;
  expires_at: string;
  effective_status: string;
  days_until_expiry: number;
}

const ManualPaymentStatus: React.FC = () => {
  const { toast } = useToast();
  const [payments, setPayments] = useState<ManualPayment[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  useEffect(() => {
    fetchPaymentStatus();
    
    // Set up polling for status updates every 30 seconds
    const interval = setInterval(fetchPaymentStatus, 30000);
    return () => clearInterval(interval);
  }, []);

  const fetchPaymentStatus = async (showRefreshToast = false) => {
    try {
      if (showRefreshToast) setRefreshing(true);
      
      const response = await fetch(ApiConfig.endpoints.payments.manualPayment);
      const data = await response.json();
      
      if (data.success) {
        setPayments(data.data || []);
        if (showRefreshToast) {
          toast({
            title: "Status Updated",
            description: "Payment status refreshed successfully",
          });
        }
      } else {
        throw new Error(data.error || 'Failed to fetch payment status');
      }
    } catch (error) {
      console.error('Failed to fetch payment status:', error);
      if (showRefreshToast) {
        toast({
          title: "Error",
          description: "Failed to refresh payment status",
          variant: "destructive"
        });
      }
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  const getStatusInfo = (payment: ManualPayment) => {
    const status = payment.effective_status;
    
    switch (status) {
      case 'pending':
        return {
          icon: Clock,
          color: 'text-yellow-400',
          bgColor: 'bg-yellow-500/10 border-yellow-500/30',
          label: 'Under Review',
          description: 'Your payment is being verified by our team'
        };
      case 'confirmed':
      case 'approved':
        return {
          icon: CheckCircle,
          color: 'text-green-400',
          bgColor: 'bg-green-500/10 border-green-500/30',
          label: 'Approved',
          description: 'Payment verified and investment activated'
        };
      case 'rejected':
        return {
          icon: XCircle,
          color: 'text-red-400',
          bgColor: 'bg-red-500/10 border-red-500/30',
          label: 'Rejected',
          description: 'Payment could not be verified'
        };
      case 'expired':
        return {
          icon: AlertTriangle,
          color: 'text-red-400',
          bgColor: 'bg-red-500/10 border-red-500/30',
          label: 'Expired',
          description: 'Payment verification period has expired'
        };
      default:
        return {
          icon: Clock,
          color: 'text-gray-400',
          bgColor: 'bg-gray-500/10 border-gray-500/30',
          label: 'Processing',
          description: 'Payment is being processed'
        };
    }
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString();
  };

  const getTimeRemaining = (expiresAt: string) => {
    const now = new Date();
    const expiry = new Date(expiresAt);
    const diff = expiry.getTime() - now.getTime();
    
    if (diff <= 0) return 'Expired';
    
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    
    if (days > 0) return `${days}d ${hours}h remaining`;
    return `${hours}h remaining`;
  };

  if (loading) {
    return (
      <Card className="bg-gray-800/50 border-gray-700">
        <CardContent className="p-6">
          <div className="text-center text-gray-400">Loading payment status...</div>
        </CardContent>
      </Card>
    );
  }

  if (payments.length === 0) {
    return (
      <Card className="bg-gray-800/50 border-gray-700">
        <CardHeader>
          <CardTitle className="text-white flex items-center gap-2">
            <DollarSign className="h-5 w-5" />
            Manual Payment Status
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="text-center py-8 text-gray-400">
            <FileText className="h-12 w-12 mx-auto mb-4 opacity-50" />
            <p>No manual payments found</p>
            <p className="text-sm mt-2">Your manual payment submissions will appear here</p>
          </div>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card className="bg-gray-800/50 border-gray-700">
      <CardHeader>
        <div className="flex items-center justify-between">
          <CardTitle className="text-white flex items-center gap-2">
            <DollarSign className="h-5 w-5" />
            Manual Payment Status ({payments.length})
          </CardTitle>
          <Button
            onClick={() => fetchPaymentStatus(true)}
            disabled={refreshing}
            variant="outline"
            size="sm"
            className="border-gray-600 text-white hover:bg-gray-700"
          >
            <RefreshCw className={`h-4 w-4 mr-2 ${refreshing ? 'animate-spin' : ''}`} />
            Refresh
          </Button>
        </div>
      </CardHeader>
      <CardContent>
        <div className="space-y-4">
          {payments.map((payment) => {
            const statusInfo = getStatusInfo(payment);
            const StatusIcon = statusInfo.icon;
            
            return (
              <div
                key={payment.payment_id}
                className={`p-4 rounded-lg border ${statusInfo.bgColor}`}
              >
                <div className="flex items-start justify-between mb-3">
                  <div className="flex items-center gap-3">
                    <StatusIcon className={`h-5 w-5 ${statusInfo.color}`} />
                    <div>
                      <div className="text-white font-medium">
                        Payment #{payment.payment_id}
                      </div>
                      <div className="text-sm text-gray-400">
                        {statusInfo.description}
                      </div>
                    </div>
                  </div>
                  <Badge 
                    variant={payment.effective_status === 'approved' ? 'default' : 
                            payment.effective_status === 'rejected' || payment.effective_status === 'expired' ? 'destructive' : 
                            'secondary'}
                  >
                    {statusInfo.label}
                  </Badge>
                </div>

                <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-3">
                  <div className="flex items-center gap-2">
                    <DollarSign className="h-4 w-4 text-gray-400" />
                    <div>
                      <div className="text-white font-semibold">
                        ${payment.amount_usd.toLocaleString()}
                      </div>
                      <div className="text-xs text-gray-400">Amount</div>
                    </div>
                  </div>

                  <div className="flex items-center gap-2">
                    <Wallet className="h-4 w-4 text-gray-400" />
                    <div>
                      <div className="text-white">
                        {payment.chain.toUpperCase()}
                      </div>
                      <div className="text-xs text-gray-400">Network</div>
                    </div>
                  </div>

                  <div className="flex items-center gap-2">
                    <Calendar className="h-4 w-4 text-gray-400" />
                    <div>
                      <div className="text-white text-sm">
                        {formatDate(payment.created_at).split(',')[0]}
                      </div>
                      <div className="text-xs text-gray-400">Submitted</div>
                    </div>
                  </div>

                  <div className="flex items-center gap-2">
                    <Clock className="h-4 w-4 text-gray-400" />
                    <div>
                      <div className={`text-sm ${
                        payment.days_until_expiry <= 1 && payment.verification_status === 'pending' 
                          ? 'text-red-400' 
                          : 'text-white'
                      }`}>
                        {payment.verification_status === 'pending' 
                          ? getTimeRemaining(payment.expires_at)
                          : 'Processed'
                        }
                      </div>
                      <div className="text-xs text-gray-400">Status</div>
                    </div>
                  </div>
                </div>

                {/* Progress Timeline */}
                <div className="flex items-center gap-2 mb-3">
                  <div className="flex items-center gap-1">
                    <div className="w-3 h-3 rounded-full bg-green-500"></div>
                    <span className="text-xs text-gray-400">Submitted</span>
                  </div>
                  <div className="flex-1 h-px bg-gray-600"></div>
                  <div className="flex items-center gap-1">
                    <div className={`w-3 h-3 rounded-full ${
                      payment.verification_status !== 'pending' ? 'bg-green-500' : 'bg-gray-600'
                    }`}></div>
                    <span className="text-xs text-gray-400">Reviewed</span>
                  </div>
                  <div className="flex-1 h-px bg-gray-600"></div>
                  <div className="flex items-center gap-1">
                    <div className={`w-3 h-3 rounded-full ${
                      payment.verification_status === 'approved' ? 'bg-green-500' : 'bg-gray-600'
                    }`}></div>
                    <span className="text-xs text-gray-400">Completed</span>
                  </div>
                </div>

                {/* Action Buttons */}
                <div className="flex gap-2">
                  <Button
                    variant="outline"
                    size="sm"
                    className="border-gray-600 text-white hover:bg-gray-700"
                    onClick={() => {
                      window.open(`/api/files/serve.php?type=payment_proof&payment_id=${payment.payment_id}`, '_blank');
                    }}
                  >
                    <Eye className="h-4 w-4 mr-2" />
                    View Proof
                  </Button>
                  
                  {payment.verification_status === 'pending' && payment.days_until_expiry <= 2 && (
                    <Button
                      variant="outline"
                      size="sm"
                      className="border-yellow-500 text-yellow-400 hover:bg-yellow-500/10"
                      onClick={() => {
                        toast({
                          title: "Support Contact",
                          description: "Please contact support if you need assistance with your payment verification.",
                        });
                      }}
                    >
                      <Mail className="h-4 w-4 mr-2" />
                      Contact Support
                    </Button>
                  )}
                </div>

                {/* Expiry Warning */}
                {payment.verification_status === 'pending' && payment.days_until_expiry <= 1 && (
                  <div className="mt-3 p-3 bg-red-500/10 border border-red-500/30 rounded-lg">
                    <div className="flex items-center gap-2 text-red-400">
                      <AlertTriangle className="h-4 w-4" />
                      <span className="text-sm font-medium">
                        Payment verification expires soon!
                      </span>
                    </div>
                    <p className="text-xs text-red-300 mt-1">
                      Please contact support if you need assistance with verification.
                    </p>
                  </div>
                )}
              </div>
            );
          })}
        </div>
      </CardContent>
    </Card>
  );
};

export default ManualPaymentStatus;

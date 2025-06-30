import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Badge } from "@/components/ui/badge";
import { useToast } from "@/hooks/use-toast";
import { ST as T } from "@/components/SimpleTranslator";
import {
  CheckCircle,
  XCircle,
  Clock,
  Eye,
  Download,
  User,
  Wallet,
  DollarSign,
  Calendar,
  AlertTriangle,
  RefreshCw
} from "lucide-react";

interface ManualPayment {
  id: string;
  payment_id: string;
  user_id: string;
  username: string;
  email: string;
  amount_usd: number;
  chain: string;
  sender_name: string;
  sender_wallet_address?: string;
  transaction_hash?: string;
  notes?: string;
  payment_status: string;
  verification_status: string;
  created_at: string;
  expires_at: string;
  effective_status: string;
  days_until_expiry: number;
}

const ManualPaymentReview: React.FC = () => {
  const { toast } = useToast();
  const [payments, setPayments] = useState<ManualPayment[]>([]);
  const [loading, setLoading] = useState(true);
  const [selectedPayment, setSelectedPayment] = useState<ManualPayment | null>(null);
  const [reviewNotes, setReviewNotes] = useState('');
  const [processing, setProcessing] = useState(false);
  const [filter, setFilter] = useState<'all' | 'pending' | 'approved' | 'rejected'>('pending');

  useEffect(() => {
    fetchManualPayments();
  }, [filter]);

  const fetchManualPayments = async () => {
    try {
      setLoading(true);
      const response = await fetch(`/api/admin/manual-payments.php?filter=${filter}`);
      const data = await response.json();
      
      if (data.success) {
        setPayments(data.data || []);
      } else {
        throw new Error(data.error || 'Failed to fetch payments');
      }
    } catch (error) {
      console.error('Failed to fetch manual payments:', error);
      toast({
        title: "Error",
        description: "Failed to load manual payments",
        variant: "destructive"
      });
    } finally {
      setLoading(false);
    }
  };

  const handlePaymentAction = async (paymentId: string, action: 'approve' | 'reject') => {
    if (!selectedPayment) return;

    setProcessing(true);
    try {
      const response = await fetch('/api/payments/manual-payment.php', {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          payment_id: paymentId,
          action: action,
          notes: reviewNotes
        })
      });

      const result = await response.json();

      if (result.success) {
        toast({
          title: "Success",
          description: `Payment ${action}d successfully`,
        });
        
        // Refresh the payments list
        fetchManualPayments();
        setSelectedPayment(null);
        setReviewNotes('');
      } else {
        throw new Error(result.error || `Failed to ${action} payment`);
      }
    } catch (error) {
      console.error(`Payment ${action} error:`, error);
      toast({
        title: "Error",
        description: `Failed to ${action} payment`,
        variant: "destructive"
      });
    } finally {
      setProcessing(false);
    }
  };

  const getStatusBadge = (payment: ManualPayment) => {
    const status = payment.effective_status;
    const variant = {
      'pending': 'secondary',
      'confirmed': 'default',
      'approved': 'default',
      'rejected': 'destructive',
      'expired': 'destructive',
      'failed': 'destructive'
    }[status] || 'secondary';

    return (
      <Badge variant={variant as any}>
        {status.charAt(0).toUpperCase() + status.slice(1)}
      </Badge>
    );
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString();
  };

  const filteredPayments = payments.filter(payment => {
    if (filter === 'all') return true;
    if (filter === 'pending') return payment.verification_status === 'pending';
    if (filter === 'approved') return payment.verification_status === 'approved';
    if (filter === 'rejected') return payment.verification_status === 'rejected';
    return true;
  });

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <h2 className="text-2xl font-bold text-white">Manual Payment Review</h2>
        <Button
          onClick={fetchManualPayments}
          variant="outline"
          className="border-gray-600 text-white hover:bg-gray-700"
        >
          <RefreshCw className="h-4 w-4 mr-2" />
          Refresh
        </Button>
      </div>

      {/* Filter Tabs */}
      <div className="flex space-x-2">
        {(['all', 'pending', 'approved', 'rejected'] as const).map((filterOption) => (
          <Button
            key={filterOption}
            variant={filter === filterOption ? 'default' : 'outline'}
            onClick={() => setFilter(filterOption)}
            className={filter === filterOption ? 'bg-gold text-black' : 'border-gray-600 text-white hover:bg-gray-700'}
          >
            {filterOption.charAt(0).toUpperCase() + filterOption.slice(1)}
          </Button>
        ))}
      </div>

      {/* Payments List */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Payments List */}
        <Card className="bg-gray-800/50 border-gray-700">
          <CardHeader>
            <CardTitle className="text-white">
              Manual Payments ({filteredPayments.length})
            </CardTitle>
          </CardHeader>
          <CardContent>
            {loading ? (
              <div className="text-center py-8 text-gray-400">Loading payments...</div>
            ) : filteredPayments.length === 0 ? (
              <div className="text-center py-8 text-gray-400">No payments found</div>
            ) : (
              <div className="space-y-4 max-h-96 overflow-y-auto">
                {filteredPayments.map((payment) => (
                  <div
                    key={payment.id}
                    className={`p-4 rounded-lg border cursor-pointer transition-colors ${
                      selectedPayment?.id === payment.id
                        ? 'border-gold bg-gold/10'
                        : 'border-gray-600 bg-gray-700/50 hover:bg-gray-700'
                    }`}
                    onClick={() => setSelectedPayment(payment)}
                  >
                    <div className="flex items-center justify-between mb-2">
                      <div className="flex items-center gap-2">
                        <User className="h-4 w-4 text-gray-400" />
                        <span className="text-white font-medium">{payment.username}</span>
                      </div>
                      {getStatusBadge(payment)}
                    </div>
                    
                    <div className="grid grid-cols-2 gap-2 text-sm">
                      <div className="flex items-center gap-1 text-gray-300">
                        <DollarSign className="h-3 w-3" />
                        ${payment.amount_usd.toLocaleString()}
                      </div>
                      <div className="flex items-center gap-1 text-gray-300">
                        <Wallet className="h-3 w-3" />
                        {payment.chain.toUpperCase()}
                      </div>
                    </div>
                    
                    <div className="text-xs text-gray-400 mt-2">
                      {formatDate(payment.created_at)}
                    </div>
                    
                    {payment.days_until_expiry <= 1 && payment.verification_status === 'pending' && (
                      <div className="flex items-center gap-1 text-red-400 text-xs mt-1">
                        <AlertTriangle className="h-3 w-3" />
                        Expires soon
                      </div>
                    )}
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>

        {/* Payment Details */}
        <Card className="bg-gray-800/50 border-gray-700">
          <CardHeader>
            <CardTitle className="text-white">Payment Details</CardTitle>
          </CardHeader>
          <CardContent>
            {selectedPayment ? (
              <div className="space-y-4">
                {/* Basic Info */}
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <Label className="text-gray-300">Payment ID</Label>
                    <div className="text-white font-mono text-sm">{selectedPayment.payment_id}</div>
                  </div>
                  <div>
                    <Label className="text-gray-300">Amount</Label>
                    <div className="text-white font-semibold">${selectedPayment.amount_usd.toLocaleString()} USDT</div>
                  </div>
                </div>

                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <Label className="text-gray-300">User</Label>
                    <div className="text-white">{selectedPayment.username}</div>
                    <div className="text-gray-400 text-sm">{selectedPayment.email}</div>
                  </div>
                  <div>
                    <Label className="text-gray-300">Network</Label>
                    <div className="text-white">{selectedPayment.chain.toUpperCase()}</div>
                  </div>
                </div>

                <div>
                  <Label className="text-gray-300">Sender Name</Label>
                  <div className="text-white">{selectedPayment.sender_name}</div>
                </div>

                {selectedPayment.sender_wallet_address && (
                  <div>
                    <Label className="text-gray-300">Sender Wallet</Label>
                    <div className="text-white font-mono text-sm break-all">{selectedPayment.sender_wallet_address}</div>
                  </div>
                )}

                {selectedPayment.transaction_hash && (
                  <div>
                    <Label className="text-gray-300">Transaction Hash</Label>
                    <div className="text-white font-mono text-sm break-all">{selectedPayment.transaction_hash}</div>
                  </div>
                )}

                {selectedPayment.notes && (
                  <div>
                    <Label className="text-gray-300">User Notes</Label>
                    <div className="text-white text-sm">{selectedPayment.notes}</div>
                  </div>
                )}

                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <Label className="text-gray-300">Created</Label>
                    <div className="text-white text-sm">{formatDate(selectedPayment.created_at)}</div>
                  </div>
                  <div>
                    <Label className="text-gray-300">Expires</Label>
                    <div className="text-white text-sm">{formatDate(selectedPayment.expires_at)}</div>
                  </div>
                </div>

                {/* Payment Proof */}
                <div>
                  <Label className="text-gray-300">Payment Proof</Label>
                  <Button
                    variant="outline"
                    className="w-full mt-2 border-gray-600 text-white hover:bg-gray-700"
                    onClick={() => {
                      // This would open the payment proof in a new window
                      window.open(`/api/files/serve.php?type=payment_proof&payment_id=${selectedPayment.payment_id}`, '_blank');
                    }}
                  >
                    <Eye className="h-4 w-4 mr-2" />
                    View Payment Proof
                  </Button>
                </div>

                {/* Admin Actions */}
                {selectedPayment.verification_status === 'pending' && (
                  <div className="space-y-4 pt-4 border-t border-gray-600">
                    <div>
                      <Label className="text-gray-300">Review Notes</Label>
                      <Textarea
                        value={reviewNotes}
                        onChange={(e) => setReviewNotes(e.target.value)}
                        placeholder="Add notes about your review decision..."
                        className="bg-gray-700 border-gray-600 text-white mt-2"
                        rows={3}
                      />
                    </div>

                    <div className="flex gap-3">
                      <Button
                        onClick={() => handlePaymentAction(selectedPayment.payment_id, 'approve')}
                        disabled={processing}
                        className="flex-1 bg-green-600 hover:bg-green-700 text-white"
                      >
                        <CheckCircle className="h-4 w-4 mr-2" />
                        Approve
                      </Button>
                      <Button
                        onClick={() => handlePaymentAction(selectedPayment.payment_id, 'reject')}
                        disabled={processing}
                        variant="destructive"
                        className="flex-1"
                      >
                        <XCircle className="h-4 w-4 mr-2" />
                        Reject
                      </Button>
                    </div>
                  </div>
                )}

                {selectedPayment.verification_status !== 'pending' && (
                  <div className="pt-4 border-t border-gray-600">
                    <div className="text-center text-gray-400">
                      Payment has been {selectedPayment.verification_status}
                    </div>
                  </div>
                )}
              </div>
            ) : (
              <div className="text-center py-8 text-gray-400">
                Select a payment to view details
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
};

export default ManualPaymentReview;

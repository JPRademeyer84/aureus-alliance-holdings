import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { useToast } from '@/hooks/use-toast';
import { 
  Building2, 
  CheckCircle, 
  XCircle, 
  Eye, 
  Clock, 
  DollarSign,
  FileText,
  User,
  Calendar,
  AlertTriangle,
  RefreshCw,
  Download,
  Search
} from 'lucide-react';
import ApiConfig from '@/config/api';

interface BankPayment {
  id: string;
  investment_id: string;
  user_id: string;
  username: string;
  email: string;
  package_name: string;
  reference_number: string;
  amount_usd: number;
  amount_local: number;
  local_currency: string;
  exchange_rate: number;
  payment_status: 'pending' | 'submitted' | 'verified' | 'confirmed' | 'failed' | 'refunded';
  verification_status: 'pending' | 'reviewing' | 'approved' | 'rejected';
  sender_name?: string;
  sender_account?: string;
  sender_bank?: string;
  transfer_date?: string;
  bank_reference?: string;
  payment_proof_path?: string;
  verified_by_username?: string;
  verified_at?: string;
  expires_at: string;
  created_at: string;
}

const BankPaymentManager: React.FC = () => {
  const [payments, setPayments] = useState<BankPayment[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [selectedPayment, setSelectedPayment] = useState<BankPayment | null>(null);
  const [showVerificationDialog, setShowVerificationDialog] = useState(false);
  const [verificationNotes, setVerificationNotes] = useState('');
  const [processing, setProcessing] = useState<string | null>(null);
  const { toast } = useToast();

  useEffect(() => {
    loadBankPayments();
  }, []);

  const loadBankPayments = async () => {
    try {
      const response = await fetch(`${ApiConfig.baseUrl}/payments/bank-transfer.php?admin=true`);
      const data = await response.json();
      
      if (data.success) {
        setPayments(data.payments || []);
      } else {
        throw new Error(data.error || 'Failed to load bank payments');
      }
    } catch (error) {
      console.error('Error loading bank payments:', error);
      toast({
        title: 'Error',
        description: 'Failed to load bank payments',
        variant: 'destructive',
      });
    } finally {
      setLoading(false);
    }
  };

  const handleVerifyPayment = async (paymentId: string, action: 'approve' | 'reject') => {
    try {
      setProcessing(paymentId);
      
      const response = await fetch(`${ApiConfig.baseUrl}/payments/bank-transfer.php`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          payment_id: paymentId,
          action: action === 'approve' ? 'verify_payment' : 'reject_payment',
          admin_id: 'admin', // Should be actual admin ID
          verification_notes: verificationNotes
        }),
      });

      const data = await response.json();
      
      if (data.success) {
        toast({
          title: 'Success',
          description: `Payment ${action === 'approve' ? 'approved' : 'rejected'} successfully`,
        });
        
        loadBankPayments();
        setShowVerificationDialog(false);
        setVerificationNotes('');
        setSelectedPayment(null);
      } else {
        throw new Error(data.error || `Failed to ${action} payment`);
      }
    } catch (error) {
      console.error(`Error ${action}ing payment:`, error);
      toast({
        title: 'Error',
        description: error instanceof Error ? error.message : `Failed to ${action} payment`,
        variant: 'destructive',
      });
    } finally {
      setProcessing(null);
    }
  };

  const getStatusBadge = (status: string, type: 'payment' | 'verification') => {
    const variants: Record<string, { variant: any; icon: React.ReactNode; color: string }> = {
      // Payment status
      pending: { variant: 'secondary', icon: <Clock className="w-3 h-3" />, color: 'text-yellow-600' },
      submitted: { variant: 'default', icon: <FileText className="w-3 h-3" />, color: 'text-blue-600' },
      verified: { variant: 'default', icon: <CheckCircle className="w-3 h-3" />, color: 'text-green-600' },
      confirmed: { variant: 'default', icon: <CheckCircle className="w-3 h-3" />, color: 'text-green-600' },
      failed: { variant: 'destructive', icon: <XCircle className="w-3 h-3" />, color: 'text-red-600' },
      refunded: { variant: 'secondary', icon: <RefreshCw className="w-3 h-3" />, color: 'text-gray-600' },
      
      // Verification status
      reviewing: { variant: 'default', icon: <Eye className="w-3 h-3" />, color: 'text-blue-600' },
      approved: { variant: 'default', icon: <CheckCircle className="w-3 h-3" />, color: 'text-green-600' },
      rejected: { variant: 'destructive', icon: <XCircle className="w-3 h-3" />, color: 'text-red-600' },
    };

    const config = variants[status] || { variant: 'secondary', icon: <AlertTriangle className="w-3 h-3" />, color: 'text-gray-600' };
    
    return (
      <Badge variant={config.variant} className={`flex items-center gap-1 ${config.color}`}>
        {config.icon}
        {status.replace('_', ' ').toUpperCase()}
      </Badge>
    );
  };

  const filteredPayments = payments.filter(payment => {
    const matchesSearch = payment.reference_number.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         payment.username.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         payment.email.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         payment.sender_name?.toLowerCase().includes(searchTerm.toLowerCase());
    
    const matchesStatus = statusFilter === 'all' || payment.verification_status === statusFilter;
    
    return matchesSearch && matchesStatus;
  });

  const openVerificationDialog = (payment: BankPayment) => {
    setSelectedPayment(payment);
    setVerificationNotes('');
    setShowVerificationDialog(true);
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center p-8">
        <RefreshCw className="w-8 h-8 animate-spin" />
        <span className="ml-2">Loading bank payments...</span>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-3xl font-bold tracking-tight">Bank Payment Management</h2>
          <p className="text-muted-foreground">
            Verify and manage bank transfer payments from users
          </p>
        </div>
        
        <Button onClick={loadBankPayments} variant="outline">
          <RefreshCw className="w-4 h-4 mr-2" />
          Refresh
        </Button>
      </div>

      {/* Filters */}
      <div className="flex items-center gap-4">
        <div className="relative flex-1 max-w-sm">
          <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground w-4 h-4" />
          <Input
            placeholder="Search payments..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="pl-10"
          />
        </div>
        
        <Select value={statusFilter} onValueChange={setStatusFilter}>
          <SelectTrigger className="w-48">
            <SelectValue placeholder="Filter by status" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Status</SelectItem>
            <SelectItem value="pending">Pending</SelectItem>
            <SelectItem value="reviewing">Reviewing</SelectItem>
            <SelectItem value="approved">Approved</SelectItem>
            <SelectItem value="rejected">Rejected</SelectItem>
          </SelectContent>
        </Select>
      </div>

      {/* Statistics Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center gap-2">
              <Clock className="w-4 h-4 text-yellow-500" />
              <div>
                <p className="text-sm text-muted-foreground">Pending Review</p>
                <p className="text-2xl font-bold">
                  {payments.filter(p => p.verification_status === 'reviewing').length}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
        
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center gap-2">
              <CheckCircle className="w-4 h-4 text-green-500" />
              <div>
                <p className="text-sm text-muted-foreground">Approved</p>
                <p className="text-2xl font-bold">
                  {payments.filter(p => p.verification_status === 'approved').length}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
        
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center gap-2">
              <DollarSign className="w-4 h-4 text-blue-500" />
              <div>
                <p className="text-sm text-muted-foreground">Total Value</p>
                <p className="text-2xl font-bold">
                  ${payments.reduce((sum, p) => sum + p.amount_usd, 0).toLocaleString()}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
        
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center gap-2">
              <Building2 className="w-4 h-4 text-purple-500" />
              <div>
                <p className="text-sm text-muted-foreground">Total Payments</p>
                <p className="text-2xl font-bold">{payments.length}</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Payments Table */}
      <Card>
        <CardHeader>
          <CardTitle>Bank Transfer Payments</CardTitle>
          <CardDescription>
            {filteredPayments.length} payment(s) found
          </CardDescription>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Reference</TableHead>
                <TableHead>User</TableHead>
                <TableHead>Package</TableHead>
                <TableHead>Amount</TableHead>
                <TableHead>Payment Status</TableHead>
                <TableHead>Verification</TableHead>
                <TableHead>Date</TableHead>
                <TableHead>Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {filteredPayments.map((payment) => (
                <TableRow key={payment.id}>
                  <TableCell className="font-mono text-sm">
                    {payment.reference_number}
                  </TableCell>
                  <TableCell>
                    <div>
                      <div className="font-medium">{payment.username}</div>
                      <div className="text-sm text-muted-foreground">{payment.email}</div>
                    </div>
                  </TableCell>
                  <TableCell>{payment.package_name}</TableCell>
                  <TableCell>
                    <div>
                      <div className="font-medium">${payment.amount_usd.toLocaleString()}</div>
                      {payment.local_currency !== 'USD' && (
                        <div className="text-sm text-muted-foreground">
                          {payment.amount_local.toLocaleString()} {payment.local_currency}
                        </div>
                      )}
                    </div>
                  </TableCell>
                  <TableCell>{getStatusBadge(payment.payment_status, 'payment')}</TableCell>
                  <TableCell>{getStatusBadge(payment.verification_status, 'verification')}</TableCell>
                  <TableCell>
                    <div className="text-sm">
                      <div>{new Date(payment.created_at).toLocaleDateString()}</div>
                      {payment.transfer_date && (
                        <div className="text-muted-foreground">
                          Transfer: {new Date(payment.transfer_date).toLocaleDateString()}
                        </div>
                      )}
                    </div>
                  </TableCell>
                  <TableCell>
                    <div className="flex items-center gap-2">
                      <Button
                        variant="outline"
                        size="sm"
                        onClick={() => openVerificationDialog(payment)}
                      >
                        <Eye className="w-4 h-4" />
                      </Button>
                      
                      {payment.verification_status === 'reviewing' && (
                        <>
                          <Button
                            variant="outline"
                            size="sm"
                            onClick={() => {
                              setSelectedPayment(payment);
                              handleVerifyPayment(payment.id, 'approve');
                            }}
                            disabled={processing === payment.id}
                          >
                            <CheckCircle className="w-4 h-4" />
                          </Button>
                          <Button
                            variant="outline"
                            size="sm"
                            onClick={() => {
                              setSelectedPayment(payment);
                              handleVerifyPayment(payment.id, 'reject');
                            }}
                            disabled={processing === payment.id}
                          >
                            <XCircle className="w-4 h-4" />
                          </Button>
                        </>
                      )}
                      
                      {payment.payment_proof_path && (
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={() => window.open(payment.payment_proof_path, '_blank')}
                        >
                          <Download className="w-4 h-4" />
                        </Button>
                      )}
                    </div>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
          
          {filteredPayments.length === 0 && (
            <div className="text-center py-8 text-muted-foreground">
              No bank payments found matching your criteria
            </div>
          )}
        </CardContent>
      </Card>

      {/* Verification Dialog */}
      <Dialog open={showVerificationDialog} onOpenChange={setShowVerificationDialog}>
        <DialogContent className="max-w-2xl">
          <DialogHeader>
            <DialogTitle>Payment Verification</DialogTitle>
            <DialogDescription>
              Review and verify the bank payment details
            </DialogDescription>
          </DialogHeader>
          
          {selectedPayment && (
            <div className="space-y-6">
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="text-sm font-medium text-muted-foreground">Reference Number</label>
                  <p className="font-mono">{selectedPayment.reference_number}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">Amount</label>
                  <p>${selectedPayment.amount_usd.toLocaleString()}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">Sender Name</label>
                  <p>{selectedPayment.sender_name || 'Not provided'}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">Transfer Date</label>
                  <p>{selectedPayment.transfer_date ? new Date(selectedPayment.transfer_date).toLocaleDateString() : 'Not provided'}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">Sender Bank</label>
                  <p>{selectedPayment.sender_bank || 'Not provided'}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">Bank Reference</label>
                  <p>{selectedPayment.bank_reference || 'Not provided'}</p>
                </div>
              </div>
              
              <div>
                <label className="text-sm font-medium text-muted-foreground">Verification Notes</label>
                <Textarea
                  value={verificationNotes}
                  onChange={(e) => setVerificationNotes(e.target.value)}
                  placeholder="Add verification notes..."
                  rows={3}
                />
              </div>
              
              <div className="flex gap-4">
                <Button
                  onClick={() => handleVerifyPayment(selectedPayment.id, 'approve')}
                  disabled={processing === selectedPayment.id}
                  className="flex-1"
                >
                  {processing === selectedPayment.id ? 'Processing...' : 'Approve Payment'}
                </Button>
                <Button
                  variant="destructive"
                  onClick={() => handleVerifyPayment(selectedPayment.id, 'reject')}
                  disabled={processing === selectedPayment.id}
                  className="flex-1"
                >
                  {processing === selectedPayment.id ? 'Processing...' : 'Reject Payment'}
                </Button>
              </div>
            </div>
          )}
        </DialogContent>
      </Dialog>
    </div>
  );
};

export default BankPaymentManager;

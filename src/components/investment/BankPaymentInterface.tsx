import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { useToast } from '@/hooks/use-toast';
import { 
  Building2, 
  Copy, 
  Upload, 
  CheckCircle, 
  Clock, 
  AlertTriangle,
  FileText,
  Calendar,
  DollarSign,
  Info
} from 'lucide-react';
import ApiConfig from '@/config/api';

interface BankPaymentInterfaceProps {
  investmentId: string;
  userId: string;
  investmentAmount: number;
  bankAccountDetails: any;
  countryCode: string;
  currencyCode: string;
  onPaymentCreated: (paymentDetails: any) => void;
  onBack: () => void;
}

interface PaymentDetails {
  payment_id: string;
  reference_number: string;
  amount_usd: number;
  amount_local: number;
  currency: string;
  expires_at: string;
}

const BankPaymentInterface: React.FC<BankPaymentInterfaceProps> = ({
  investmentId,
  userId,
  investmentAmount,
  bankAccountDetails,
  countryCode,
  currencyCode,
  onPaymentCreated,
  onBack
}) => {
  const [step, setStep] = useState<'create' | 'submit_proof' | 'waiting'>('create');
  const [loading, setLoading] = useState(false);
  const [paymentDetails, setPaymentDetails] = useState<PaymentDetails | null>(null);
  const [exchangeRate, setExchangeRate] = useState(1.0);
  const [formData, setFormData] = useState({
    sender_name: '',
    sender_account: '',
    sender_bank: '',
    transfer_date: '',
    bank_reference: '',
    payment_proof_file: null as File | null,
    notes: ''
  });
  const { toast } = useToast();

  useEffect(() => {
    if (currencyCode !== 'USD') {
      fetchExchangeRate();
    }
  }, [currencyCode]);

  const fetchExchangeRate = async () => {
    try {
      // In production, use a real exchange rate API
      // For now, using mock rates
      const mockRates: Record<string, number> = {
        'EUR': 0.85,
        'GBP': 0.73,
        'CAD': 1.25,
        'AUD': 1.35,
        'JPY': 110.0,
        'CNY': 6.45,
        'INR': 74.5
      };
      
      setExchangeRate(mockRates[currencyCode] || 1.0);
    } catch (error) {
      console.error('Failed to fetch exchange rate:', error);
      setExchangeRate(1.0);
    }
  };

  const createBankPayment = async () => {
    try {
      setLoading(true);
      
      const response = await fetch(`${ApiConfig.baseUrl}/payments/bank-transfer.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          investment_id: investmentId,
          user_id: userId,
          amount_usd: investmentAmount,
          country_code: countryCode,
          currency_code: currencyCode,
          exchange_rate: exchangeRate
        }),
      });

      const data = await response.json();
      
      if (data.success) {
        setPaymentDetails(data.payment_details);
        setStep('submit_proof');
        
        toast({
          title: 'Payment Created',
          description: 'Bank payment created successfully. Please make the transfer and submit proof.',
        });
        
        onPaymentCreated(data);
      } else {
        throw new Error(data.error || 'Failed to create bank payment');
      }
    } catch (error) {
      console.error('Bank payment creation error:', error);
      toast({
        title: 'Creation Failed',
        description: error instanceof Error ? error.message : 'Failed to create bank payment',
        variant: 'destructive',
      });
    } finally {
      setLoading(false);
    }
  };

  const submitPaymentProof = async () => {
    try {
      setLoading(true);
      
      // Upload file if provided
      let paymentProofPath = null;
      if (formData.payment_proof_file) {
        paymentProofPath = await uploadPaymentProof(formData.payment_proof_file);
      }
      
      const response = await fetch(`${ApiConfig.baseUrl}/payments/bank-transfer.php`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          payment_id: paymentDetails?.payment_id,
          user_id: userId,
          action: 'submit_proof',
          sender_name: formData.sender_name,
          sender_account: formData.sender_account,
          sender_bank: formData.sender_bank,
          transfer_date: formData.transfer_date,
          bank_reference: formData.bank_reference,
          payment_proof_path: paymentProofPath,
          notes: formData.notes
        }),
      });

      const data = await response.json();
      
      if (data.success) {
        setStep('waiting');
        
        toast({
          title: 'Proof Submitted',
          description: 'Payment proof submitted successfully. Awaiting admin verification.',
        });
      } else {
        throw new Error(data.error || 'Failed to submit payment proof');
      }
    } catch (error) {
      console.error('Payment proof submission error:', error);
      toast({
        title: 'Submission Failed',
        description: error instanceof Error ? error.message : 'Failed to submit payment proof',
        variant: 'destructive',
      });
    } finally {
      setLoading(false);
    }
  };

  const uploadPaymentProof = async (file: File): Promise<string> => {
    const formData = new FormData();
    formData.append('payment_proof', file);
    formData.append('payment_id', paymentDetails?.payment_id || '');
    
    const response = await fetch(`${ApiConfig.baseUrl}/payments/upload-proof.php`, {
      method: 'POST',
      body: formData,
    });
    
    const data = await response.json();
    
    if (data.success) {
      return data.file_path;
    } else {
      throw new Error(data.error || 'Failed to upload file');
    }
  };

  const copyToClipboard = (text: string, label: string) => {
    navigator.clipboard.writeText(text);
    toast({
      title: 'Copied',
      description: `${label} copied to clipboard`,
    });
  };

  const formatCurrency = (amount: number, currency: string) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: currency,
    }).format(amount);
  };

  if (step === 'create') {
    return (
      <div className="space-y-6">
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Building2 className="w-5 h-5" />
              Bank Transfer Payment
            </CardTitle>
            <CardDescription>
              Review the details and create your bank payment
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-6">
            {/* Payment Summary */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 bg-muted rounded-lg">
              <div className="text-center">
                <div className="text-2xl font-bold text-green-600">
                  {formatCurrency(investmentAmount, 'USD')}
                </div>
                <div className="text-sm text-muted-foreground">Investment Amount (USD)</div>
              </div>
              {currencyCode !== 'USD' && (
                <div className="text-center">
                  <div className="text-2xl font-bold text-blue-600">
                    {formatCurrency(investmentAmount * exchangeRate, currencyCode)}
                  </div>
                  <div className="text-sm text-muted-foreground">Local Amount ({currencyCode})</div>
                </div>
              )}
              <div className="text-center">
                <div className="text-lg font-semibold">
                  1 USD = {exchangeRate} {currencyCode}
                </div>
                <div className="text-sm text-muted-foreground">Exchange Rate</div>
              </div>
            </div>

            {/* Bank Account Details */}
            <div className="space-y-4">
              <h3 className="text-lg font-semibold">Transfer to this account:</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 border rounded-lg">
                <div>
                  <Label className="text-sm font-medium text-muted-foreground">Bank Name</Label>
                  <div className="flex items-center gap-2">
                    <p className="font-mono">{bankAccountDetails.bank_name}</p>
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => copyToClipboard(bankAccountDetails.bank_name, 'Bank name')}
                    >
                      <Copy className="w-4 h-4" />
                    </Button>
                  </div>
                </div>
                <div>
                  <Label className="text-sm font-medium text-muted-foreground">Account Number</Label>
                  <div className="flex items-center gap-2">
                    <p className="font-mono">{bankAccountDetails.account_number}</p>
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => copyToClipboard(bankAccountDetails.account_number, 'Account number')}
                    >
                      <Copy className="w-4 h-4" />
                    </Button>
                  </div>
                </div>
                <div>
                  <Label className="text-sm font-medium text-muted-foreground">Account Holder</Label>
                  <div className="flex items-center gap-2">
                    <p className="font-mono">{bankAccountDetails.account_holder_name}</p>
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => copyToClipboard(bankAccountDetails.account_holder_name, 'Account holder')}
                    >
                      <Copy className="w-4 h-4" />
                    </Button>
                  </div>
                </div>
                {bankAccountDetails.swift_code && (
                  <div>
                    <Label className="text-sm font-medium text-muted-foreground">SWIFT Code</Label>
                    <div className="flex items-center gap-2">
                      <p className="font-mono">{bankAccountDetails.swift_code}</p>
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => copyToClipboard(bankAccountDetails.swift_code, 'SWIFT code')}
                      >
                        <Copy className="w-4 h-4" />
                      </Button>
                    </div>
                  </div>
                )}
              </div>
            </div>

            <Alert>
              <Info className="w-4 h-4" />
              <AlertDescription>
                <strong>Important:</strong> After creating the payment, you will receive a unique reference number. 
                You must include this reference in your bank transfer for proper processing.
              </AlertDescription>
            </Alert>

            <div className="flex gap-4">
              <Button variant="outline" onClick={onBack}>
                Back
              </Button>
              <Button onClick={createBankPayment} disabled={loading} className="flex-1">
                {loading ? 'Creating...' : 'Create Bank Payment'}
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>
    );
  }

  if (step === 'submit_proof') {
    return (
      <div className="space-y-6">
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <FileText className="w-5 h-5" />
              Submit Payment Proof
            </CardTitle>
            <CardDescription>
              Make the bank transfer and submit proof of payment
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-6">
            {/* Reference Number */}
            <Alert>
              <CheckCircle className="w-4 h-4" />
              <AlertDescription>
                <div className="space-y-2">
                  <p><strong>Your Reference Number:</strong></p>
                  <div className="flex items-center gap-2 p-2 bg-muted rounded font-mono text-lg">
                    {paymentDetails?.reference_number}
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => copyToClipboard(paymentDetails?.reference_number || '', 'Reference number')}
                    >
                      <Copy className="w-4 h-4" />
                    </Button>
                  </div>
                  <p className="text-sm">Include this reference in your bank transfer description/memo.</p>
                </div>
              </AlertDescription>
            </Alert>

            {/* Payment Details Form */}
            <div className="space-y-4">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <Label htmlFor="sender_name">Sender Name *</Label>
                  <Input
                    id="sender_name"
                    value={formData.sender_name}
                    onChange={(e) => setFormData(prev => ({ ...prev, sender_name: e.target.value }))}
                    placeholder="Name on the sending account"
                    required
                  />
                </div>
                <div>
                  <Label htmlFor="sender_account">Sender Account Number</Label>
                  <Input
                    id="sender_account"
                    value={formData.sender_account}
                    onChange={(e) => setFormData(prev => ({ ...prev, sender_account: e.target.value }))}
                    placeholder="Your account number"
                  />
                </div>
                <div>
                  <Label htmlFor="sender_bank">Sender Bank</Label>
                  <Input
                    id="sender_bank"
                    value={formData.sender_bank}
                    onChange={(e) => setFormData(prev => ({ ...prev, sender_bank: e.target.value }))}
                    placeholder="Your bank name"
                  />
                </div>
                <div>
                  <Label htmlFor="transfer_date">Transfer Date *</Label>
                  <Input
                    id="transfer_date"
                    type="date"
                    value={formData.transfer_date}
                    onChange={(e) => setFormData(prev => ({ ...prev, transfer_date: e.target.value }))}
                    required
                  />
                </div>
                <div className="md:col-span-2">
                  <Label htmlFor="bank_reference">Bank Reference Number</Label>
                  <Input
                    id="bank_reference"
                    value={formData.bank_reference}
                    onChange={(e) => setFormData(prev => ({ ...prev, bank_reference: e.target.value }))}
                    placeholder="Transaction reference from your bank"
                  />
                </div>
              </div>

              <div>
                <Label htmlFor="payment_proof">Payment Proof (Receipt/Screenshot)</Label>
                <Input
                  id="payment_proof"
                  type="file"
                  accept="image/*,.pdf"
                  onChange={(e) => setFormData(prev => ({ 
                    ...prev, 
                    payment_proof_file: e.target.files?.[0] || null 
                  }))}
                />
                <p className="text-sm text-muted-foreground mt-1">
                  Upload a screenshot or photo of your bank transfer receipt
                </p>
              </div>

              <div>
                <Label htmlFor="notes">Additional Notes</Label>
                <Textarea
                  id="notes"
                  value={formData.notes}
                  onChange={(e) => setFormData(prev => ({ ...prev, notes: e.target.value }))}
                  placeholder="Any additional information about the transfer"
                  rows={3}
                />
              </div>
            </div>

            <div className="flex gap-4">
              <Button variant="outline" onClick={() => setStep('create')}>
                Back
              </Button>
              <Button 
                onClick={submitPaymentProof} 
                disabled={loading || !formData.sender_name || !formData.transfer_date}
                className="flex-1"
              >
                {loading ? 'Submitting...' : 'Submit Payment Proof'}
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>
    );
  }

  // Waiting for verification step
  return (
    <Card>
      <CardContent className="text-center py-12">
        <Clock className="w-16 h-16 mx-auto mb-4 text-blue-500" />
        <h3 className="text-xl font-semibold mb-2">Payment Proof Submitted</h3>
        <p className="text-muted-foreground mb-6">
          Your payment proof has been submitted and is awaiting admin verification.
          This typically takes 1-3 business days.
        </p>
        <div className="space-y-2 text-sm">
          <p><strong>Reference Number:</strong> {paymentDetails?.reference_number}</p>
          <p><strong>Amount:</strong> {formatCurrency(paymentDetails?.amount_usd || 0, 'USD')}</p>
          <p><strong>Status:</strong> <Badge variant="secondary">Under Review</Badge></p>
        </div>
        <Button variant="outline" onClick={onBack} className="mt-6">
          Return to Dashboard
        </Button>
      </CardContent>
    </Card>
  );
};

export default BankPaymentInterface;

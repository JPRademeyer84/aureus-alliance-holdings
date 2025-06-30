import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { useToast } from "@/hooks/use-toast";
import { ST as T, useSimpleTranslation as useTranslation } from "@/components/SimpleTranslator";
import ApiConfig from "@/config/api";
import {
  Copy,
  CheckCircle,
  Clock,
  AlertCircle,
  Upload,
  ExternalLink,
  Wallet,
  Shield,
  Info,
  QrCode
} from "lucide-react";

interface ManualPaymentInterfaceProps {
  totalAmount: number;
  onPaymentInitiated: (paymentId: string) => void;
  onBack: () => void;
}

interface CompanyWallet {
  chain: string;
  address: string;
  network: string;
  symbol: string;
  qrCode?: string;
}

const ManualPaymentInterface: React.FC<ManualPaymentInterfaceProps> = ({
  totalAmount,
  onPaymentInitiated,
  onBack
}) => {
  const { toast } = useToast();
  const { translate } = useTranslation();
  const [selectedChain, setSelectedChain] = useState<string>('bsc');
  const [companyWallets, setCompanyWallets] = useState<Record<string, CompanyWallet>>({});
  const [loading, setLoading] = useState(true);
  const [copiedAddress, setCopiedAddress] = useState<string>('');
  const [paymentProof, setPaymentProof] = useState<File | null>(null);
  const [senderDetails, setSenderDetails] = useState({
    senderName: '',
    senderWallet: '',
    transactionHash: '',
    notes: ''
  });
  const [isSubmitting, setIsSubmitting] = useState(false);

  // Available chains for manual payment
  const availableChains = [
    { key: 'bsc', name: 'Binance Smart Chain', symbol: 'USDT (BEP-20)', network: 'BSC' },
    { key: 'ethereum', name: 'Ethereum', symbol: 'USDT (ERC-20)', network: 'ETH' },
    { key: 'polygon', name: 'Polygon', symbol: 'USDT (Polygon)', network: 'MATIC' },
    { key: 'tron', name: 'Tron', symbol: 'USDT (TRC-20)', network: 'TRX' }
  ];

  useEffect(() => {
    fetchCompanyWallets();
  }, []);

  const fetchCompanyWallets = async () => {
    try {
      const response = await fetch(ApiConfig.endpoints.wallets.active);
      const data = await response.json();
      
      if (data.success && data.data) {
        // Transform the data to include network information
        const walletsWithInfo: Record<string, CompanyWallet> = {};
        Object.entries(data.data).forEach(([chain, address]) => {
          const chainInfo = availableChains.find(c => c.key === chain);
          if (chainInfo) {
            walletsWithInfo[chain] = {
              chain,
              address: address as string,
              network: chainInfo.network,
              symbol: chainInfo.symbol
            };
          }
        });
        setCompanyWallets(walletsWithInfo);
      }
    } catch (error) {
      console.error('Failed to fetch company wallets:', error);
      toast({
        title: "Error",
        description: "Failed to load payment addresses. Please try again.",
        variant: "destructive"
      });
    } finally {
      setLoading(false);
    }
  };

  const copyToClipboard = async (text: string, type: string) => {
    try {
      await navigator.clipboard.writeText(text);
      setCopiedAddress(type);
      toast({
        title: "Copied!",
        description: `${type} copied to clipboard`,
      });
      setTimeout(() => setCopiedAddress(''), 2000);
    } catch (error) {
      toast({
        title: "Copy Failed",
        description: "Please copy the address manually",
        variant: "destructive"
      });
    }
  };

  const handleFileUpload = (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (file) {
      // Validate file type and size
      const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
      const maxSize = 5 * 1024 * 1024; // 5MB

      if (!allowedTypes.includes(file.type)) {
        toast({
          title: "Invalid File Type",
          description: "Please upload a JPG, PNG, or PDF file",
          variant: "destructive"
        });
        return;
      }

      if (file.size > maxSize) {
        toast({
          title: "File Too Large",
          description: "Please upload a file smaller than 5MB",
          variant: "destructive"
        });
        return;
      }

      setPaymentProof(file);
    }
  };

  const handleSubmitPayment = async () => {
    if (!paymentProof) {
      toast({
        title: "Payment Proof Required",
        description: "Please upload a screenshot or receipt of your payment",
        variant: "destructive"
      });
      return;
    }

    if (!senderDetails.senderName.trim()) {
      toast({
        title: "Sender Name Required",
        description: "Please enter your name as it appears on your exchange account",
        variant: "destructive"
      });
      return;
    }

    setIsSubmitting(true);

    try {
      const formData = new FormData();
      formData.append('payment_proof', paymentProof);
      formData.append('amount_usd', totalAmount.toString());
      formData.append('chain', selectedChain);
      formData.append('company_wallet', companyWallets[selectedChain]?.address || '');
      formData.append('sender_name', senderDetails.senderName);
      formData.append('sender_wallet', senderDetails.senderWallet);
      formData.append('transaction_hash', senderDetails.transactionHash);
      formData.append('notes', senderDetails.notes);

      const response = await fetch(ApiConfig.endpoints.payments.manualPayment, {
        method: 'POST',
        body: formData
      });

      const result = await response.json();

      if (result.success) {
        toast({
          title: "Payment Submitted Successfully!",
          description: "Your payment is being reviewed. You'll receive an email confirmation shortly.",
        });
        onPaymentInitiated(result.payment_id);
      } else {
        throw new Error(result.error || 'Failed to submit payment');
      }
    } catch (error) {
      console.error('Payment submission error:', error);
      toast({
        title: "Submission Failed",
        description: "Failed to submit payment proof. Please try again.",
        variant: "destructive"
      });
    } finally {
      setIsSubmitting(false);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center p-8">
        <div className="text-white">Loading payment information...</div>
      </div>
    );
  }

  const selectedWallet = companyWallets[selectedChain];

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <h3 className="text-xl font-semibold text-white">
          <T k="manual_payment_title" fallback="Manual Payment Instructions" />
        </h3>
        <Button variant="outline" onClick={onBack} className="text-white border-gray-600">
          <T k="back" fallback="Back" />
        </Button>
      </div>

      {/* Important Notice */}
      <Card className="bg-blue-500/10 border-blue-500/30">
        <CardContent className="p-4">
          <div className="flex items-start gap-3">
            <Info className="h-5 w-5 text-blue-400 mt-0.5 flex-shrink-0" />
            <div className="text-sm text-blue-200">
              <p className="font-medium mb-2">
                <T k="manual_payment_notice_title" fallback="Important: Manual Payment Process" />
              </p>
              <ul className="space-y-1 text-blue-300">
                <li>• Send exactly <strong>${totalAmount} USDT</strong> to avoid processing delays</li>
                <li>• Payment will be verified within 24 hours</li>
                <li>• Keep your transaction receipt for verification</li>
                <li>• Do not send from exchange internal wallets (use withdrawal)</li>
              </ul>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Chain Selection */}
      <Card className="bg-gray-800/50 border-gray-700">
        <CardHeader>
          <CardTitle className="text-white flex items-center gap-2">
            <Wallet className="h-5 w-5" />
            <T k="select_network" fallback="Step 1: Select Network" />
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
            {availableChains.map((chain) => (
              <Button
                key={chain.key}
                variant={selectedChain === chain.key ? 'default' : 'outline'}
                onClick={() => setSelectedChain(chain.key)}
                className={`p-4 h-auto flex-col space-y-2 ${
                  selectedChain === chain.key 
                    ? 'bg-gold text-black' 
                    : 'border-gray-600 text-white hover:bg-gray-700'
                }`}
                disabled={!companyWallets[chain.key]}
              >
                <div className="font-medium">{chain.network}</div>
                <div className="text-xs opacity-80">{chain.symbol}</div>
              </Button>
            ))}
          </div>
        </CardContent>
      </Card>

      {/* Payment Address */}
      {selectedWallet && (
        <Card className="bg-gray-800/50 border-gray-700">
          <CardHeader>
            <CardTitle className="text-white flex items-center gap-2">
              <Shield className="h-5 w-5" />
              <T k="payment_address" fallback="Step 2: Send Payment to This Address" />
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="bg-gray-900/50 p-4 rounded-lg">
              <div className="flex items-center justify-between mb-2">
                <Label className="text-gray-300">
                  <T k="company_wallet_address" fallback="Company Wallet Address" />
                </Label>
                <div className="text-sm text-gray-400">
                  {selectedWallet.network} Network
                </div>
              </div>
              <div className="flex items-center gap-2">
                <Input
                  value={selectedWallet.address}
                  readOnly
                  className="bg-gray-800 border-gray-600 text-white font-mono text-sm"
                />
                <Button
                  size="sm"
                  variant="outline"
                  onClick={() => copyToClipboard(selectedWallet.address, 'Address')}
                  className="border-gray-600 text-white hover:bg-gray-700"
                >
                  {copiedAddress === 'Address' ? (
                    <CheckCircle className="h-4 w-4" />
                  ) : (
                    <Copy className="h-4 w-4" />
                  )}
                </Button>
              </div>
            </div>

            <div className="bg-yellow-500/10 border border-yellow-500/30 rounded-lg p-4">
              <div className="flex items-start gap-3">
                <AlertCircle className="h-5 w-5 text-yellow-400 mt-0.5 flex-shrink-0" />
                <div className="text-sm text-yellow-200">
                  <p className="font-medium mb-1">Payment Amount: ${totalAmount} USDT</p>
                  <p>Send exactly this amount to ensure automatic processing. Network: {selectedWallet.network}</p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Payment Proof Upload */}
      <Card className="bg-gray-800/50 border-gray-700">
        <CardHeader>
          <CardTitle className="text-white flex items-center gap-2">
            <Upload className="h-5 w-5" />
            <T k="upload_payment_proof" fallback="Step 3: Upload Payment Proof" />
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-4">
            <div>
              <Label className="text-gray-300 mb-2 block">
                <T k="sender_name" fallback="Your Name (as shown on exchange)" />
              </Label>
              <Input
                value={senderDetails.senderName}
                onChange={(e) => setSenderDetails(prev => ({ ...prev, senderName: e.target.value }))}
                placeholder="Enter your full name"
                className="bg-gray-800 border-gray-600 text-white"
              />
            </div>

            <div>
              <Label className="text-gray-300 mb-2 block">
                <T k="sender_wallet" fallback="Your Wallet/Exchange Address (Optional)" />
              </Label>
              <Input
                value={senderDetails.senderWallet}
                onChange={(e) => setSenderDetails(prev => ({ ...prev, senderWallet: e.target.value }))}
                placeholder="Your sending wallet address"
                className="bg-gray-800 border-gray-600 text-white font-mono text-sm"
              />
            </div>

            <div>
              <Label className="text-gray-300 mb-2 block">
                <T k="transaction_hash" fallback="Transaction Hash (Optional)" />
              </Label>
              <Input
                value={senderDetails.transactionHash}
                onChange={(e) => setSenderDetails(prev => ({ ...prev, transactionHash: e.target.value }))}
                placeholder="Transaction hash from blockchain"
                className="bg-gray-800 border-gray-600 text-white font-mono text-sm"
              />
            </div>

            <div>
              <Label className="text-gray-300 mb-2 block">
                <T k="payment_proof_file" fallback="Payment Screenshot/Receipt" />
              </Label>
              <div className="border-2 border-dashed border-gray-600 rounded-lg p-6 text-center">
                <input
                  type="file"
                  accept="image/*,.pdf"
                  onChange={handleFileUpload}
                  className="hidden"
                  id="payment-proof-upload"
                />
                <label
                  htmlFor="payment-proof-upload"
                  className="cursor-pointer flex flex-col items-center gap-2"
                >
                  <Upload className="h-8 w-8 text-gray-400" />
                  <div className="text-white">
                    {paymentProof ? (
                      <span className="text-green-400">✓ {paymentProof.name}</span>
                    ) : (
                      <span>Click to upload payment proof</span>
                    )}
                  </div>
                  <div className="text-sm text-gray-400">
                    JPG, PNG, or PDF (max 5MB)
                  </div>
                </label>
              </div>
            </div>

            <div>
              <Label className="text-gray-300 mb-2 block">
                <T k="additional_notes" fallback="Additional Notes (Optional)" />
              </Label>
              <Textarea
                value={senderDetails.notes}
                onChange={(e) => setSenderDetails(prev => ({ ...prev, notes: e.target.value }))}
                placeholder="Any additional information about your payment"
                className="bg-gray-800 border-gray-600 text-white"
                rows={3}
              />
            </div>
          </div>

          <div className="bg-green-500/10 border border-green-500/30 rounded-lg p-4">
            <div className="flex items-start gap-3">
              <CheckCircle className="h-5 w-5 text-green-400 mt-0.5 flex-shrink-0" />
              <div className="text-sm text-green-200">
                <p className="font-medium mb-1">What happens next?</p>
                <ul className="space-y-1">
                  <li>• Your payment will be verified within 24 hours</li>
                  <li>• You'll receive email confirmation once approved</li>
                  <li>• Your investment packages will be activated automatically</li>
                  <li>• Contact support if you need assistance</li>
                </ul>
              </div>
            </div>
          </div>

          <div className="flex gap-3 pt-4">
            <Button
              onClick={onBack}
              variant="outline"
              className="flex-1 border-gray-600 text-white hover:bg-gray-700"
            >
              <T k="back" fallback="Back" />
            </Button>
            <Button
              onClick={handleSubmitPayment}
              disabled={isSubmitting || !paymentProof || !senderDetails.senderName.trim()}
              className="flex-1 bg-gold text-black hover:bg-gold/90 disabled:opacity-50"
            >
              {isSubmitting ? (
                <>
                  <Clock className="h-4 w-4 mr-2 animate-spin" />
                  <T k="submitting" fallback="Submitting..." />
                </>
              ) : (
                <>
                  <CheckCircle className="h-4 w-4 mr-2" />
                  <T k="submit_payment_proof" fallback="Submit Payment Proof" />
                </>
              )}
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
};

export default ManualPaymentInterface;

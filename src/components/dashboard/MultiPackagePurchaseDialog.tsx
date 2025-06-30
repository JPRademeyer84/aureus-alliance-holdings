import React, { useState, useEffect } from "react";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent } from "@/components/ui/card";
import { useSimpleTranslation as useTranslation, ST as T } from '@/components/SimpleTranslator';
import { useWalletConnection, WalletProviderName } from "@/pages/investment/useWalletConnection";
import { WalletSelector } from "@/pages/investment/WalletSelector";
import WalletConnector from "@/pages/investment/WalletConnector";
import ChainSelector from "@/components/investment/ChainSelector";
import BalanceChecker from "@/components/investment/BalanceChecker";
import TermsAcceptance, { TermsAcceptanceData } from "@/components/investment/TermsAcceptance";
import ManualPaymentInterface from "@/components/payment/ManualPaymentInterface";
import { useToast } from "@/hooks/use-toast";
import { useUser } from "@/contexts/UserContext";
import { useReferralConversion } from "@/hooks/useReferralTracking";
import { createInvestmentRecord } from "@/pages/investment/utils/investmentHistory";
import { sendUSDTTransaction } from "@/pages/investment/utils/web3Transaction";
import { SupportedChain, BalanceInfo } from "@/types/investment";
import {
  X,
  ShoppingCart,
  Wallet,
  CreditCard,
  CheckCircle,
  Loader2,
  Package,
  DollarSign,
  Star,
  TrendingUp
} from "lucide-react";

interface PackageSelection {
  packageId: string;
  quantity: number;
  package: any;
}

interface MultiPackagePurchaseDialogProps {
  isOpen: boolean;
  onClose: () => void;
  selections: PackageSelection[];
  totalAmount: number;
}

const MultiPackagePurchaseDialog: React.FC<MultiPackagePurchaseDialogProps> = ({
  isOpen,
  onClose,
  selections,
  totalAmount
}) => {
  const { translate } = useTranslation();
  const { toast } = useToast();
  const { user } = useUser();
  const { trackConversion } = useReferralConversion();
  const [selectedWallet, setSelectedWallet] = useState<WalletProviderName>("safepal");
  const [selectedChain, setSelectedChain] = useState<SupportedChain | null>(null);
  const [isPurchasing, setIsPurchasing] = useState(false);
  const [isChainSwitching, setIsChainSwitching] = useState(false);
  const [hasEnoughBalance, setHasEnoughBalance] = useState(false);
  const [currentBalance, setCurrentBalance] = useState<BalanceInfo | null>(null);
  const [termsAccepted, setTermsAccepted] = useState(false);
  const [termsData, setTermsData] = useState<TermsAcceptanceData | null>(null);
  const [purchaseStep, setPurchaseStep] = useState<'payment' | 'wallet' | 'chain' | 'balance' | 'terms' | 'confirm' | 'processing' | 'success' | 'manual' | 'manual_submitted'>('payment');
  const [transactionHashes, setTransactionHashes] = useState<string[]>([]);
  const [investmentIds, setInvestmentIds] = useState<string[]>([]);
  const [paymentMethod, setPaymentMethod] = useState<'wallet' | 'credits' | 'manual'>('wallet');
  const [userCredits, setUserCredits] = useState<number>(0);
  const [loadingCredits, setLoadingCredits] = useState(false);

  const {
    walletAddress,
    isConnecting,
    connectWallet,
    connectionError,
    chainId,
    disconnectWallet,
  } = useWalletConnection();

  // Calculate total packages and shares
  const totalPackages = selections.reduce((sum, sel) => sum + sel.quantity, 0);
  const totalShares = selections.reduce((sum, sel) => sum + (sel.package.shares * sel.quantity), 0);
  const totalROI = selections.reduce((sum, sel) => sum + (sel.package.roi * sel.quantity), 0);

  // Fetch user credits on mount
  useEffect(() => {
    if (isOpen) {
      fetchUserCredits();
    }
  }, [isOpen]);

  const fetchUserCredits = async () => {
    if (!user?.id) return;
    
    setLoadingCredits(true);
    try {
      const response = await fetch(`http://localhost/aureus-angel-alliance/get-user-credits.php?user_id=${user.id}`);
      const data = await response.json();
      if (data.success) {
        setUserCredits(data.credits || 0);
      }
    } catch (error) {
      console.error('Failed to fetch user credits:', error);
    } finally {
      setLoadingCredits(false);
    }
  };

  const handleClose = () => {
    if (!isPurchasing) {
      onClose();
      // Reset state
      setPurchaseStep('payment');
      setTransactionHashes([]);
      setInvestmentIds([]);
      setTermsAccepted(false);
      setTermsData(null);
    }
  };

  const handleWalletConnect = async () => {
    try {
      await connectWallet(selectedWallet);
      if (walletAddress) {
        setPurchaseStep('chain');
      }
    } catch (error) {
      console.error('Wallet connection failed:', error);
    }
  };

  const handleChainSelect = (chain: SupportedChain) => {
    setSelectedChain(chain);
    setPurchaseStep('balance');
  };

  const handleBalanceCheck = (hasBalance: boolean, balance: BalanceInfo | null) => {
    setHasEnoughBalance(hasBalance);
    setCurrentBalance(balance);
    if (hasBalance) {
      setPurchaseStep('terms');
    }
  };

  const handleTermsAcceptance = (allAccepted: boolean, acceptanceData: TermsAcceptanceData) => {
    setTermsAccepted(allAccepted);
    setTermsData(acceptanceData);
    if (allAccepted) {
      setPurchaseStep('confirm');
    }
  };

  const handleManualPaymentInitiated = (paymentId: string) => {
    // Store the payment ID for tracking
    setTransactionHashes([paymentId]);
    setPurchaseStep('manual_submitted');
  };

  const handleBackToPayment = () => {
    setPurchaseStep('payment');
    setPaymentMethod('wallet');
  };

  const handleCreditPurchase = async () => {
    if (!termsAccepted) {
      toast({
        title: "Terms Not Accepted",
        description: "Please accept the terms and conditions to proceed",
        variant: "destructive"
      });
      return;
    }

    if (userCredits < totalAmount) {
      toast({
        title: "Insufficient Credits",
        description: `You need $${totalAmount.toFixed(2)} but only have $${userCredits.toFixed(2)} in credits`,
        variant: "destructive"
      });
      return;
    }

    setIsPurchasing(true);
    setPurchaseStep('processing');

    try {
      const newInvestmentIds: string[] = [];
      
      // Create investment records for each package selection
      for (const selection of selections) {
        for (let i = 0; i < selection.quantity; i++) {
          const investmentRecord = await createInvestmentRecord({
            packageName: selection.package.name,
            amount: selection.package.price,
            shares: selection.package.shares,
            roi: selection.package.roi,
            txHash: '', // No transaction hash for credit payments
            chainId: '', // No chain for credit payments
            walletAddress: '', // No wallet for credit payments
            userEmail: '',
            userName: '',
            termsData: termsData,
            paymentMethod: 'credits'
          });
          
          newInvestmentIds.push(investmentRecord.id);
        }
      }

      setInvestmentIds(newInvestmentIds);

      // Track referral conversion for total amount
      if (user?.id && user?.username) {
        try {
          const referralTracked = await trackConversion(
            totalAmount,
            `multi_credit_${newInvestmentIds.join('_')}`,
            user.id.toString(),
            user.username
          );

          if (referralTracked) {
            console.log('Multi-package referral conversion tracked successfully');
          }
        } catch (referralError) {
          console.error('Failed to track referral conversion:', referralError);
        }
      }

      // Refresh user credits
      await fetchUserCredits();

      setPurchaseStep('success');

      toast({
        title: "Multi-Package Purchase Successful!",
        description: `Successfully purchased ${totalPackages} packages with credits`,
      });

    } catch (error: any) {
      console.error("Multi-package credit purchase failed:", error);

      toast({
        title: "Purchase Failed",
        description: error.message || "There was an error processing your multi-package purchase",
        variant: "destructive"
      });

      setPurchaseStep('confirm');
    } finally {
      setIsPurchasing(false);
    }
  };

  const handleWalletPurchase = async () => {
    if (!walletAddress || !selectedChain || !hasEnoughBalance || !termsAccepted) {
      toast({
        title: "Purchase Requirements Not Met",
        description: "Please ensure wallet is connected, chain is selected, you have sufficient balance, and terms are accepted",
        variant: "destructive"
      });
      return;
    }

    setIsPurchasing(true);
    setPurchaseStep('processing');

    try {
      const newInvestmentIds: string[] = [];
      const newTransactionHashes: string[] = [];
      
      // Process each package selection
      for (const selection of selections) {
        for (let i = 0; i < selection.quantity; i++) {
          // Create investment record
          const investmentRecord = await createInvestmentRecord({
            packageName: selection.package.name,
            amount: selection.package.price,
            shares: selection.package.shares,
            roi: selection.package.roi,
            txHash: '', // Will be updated after transaction
            chainId: selectedChain,
            walletAddress: walletAddress,
            userEmail: '',
            userName: '',
            termsData: termsData,
            paymentMethod: 'wallet'
          });

          newInvestmentIds.push(investmentRecord.id);

          // Send blockchain transaction for this package
          const txResult = await sendUSDTTransaction(
            selectedWallet,
            walletAddress,
            selection.package.price,
            selectedChain
          );

          if (!txResult.success) {
            throw new Error(`Transaction failed for ${selection.package.name}: ${txResult.error}`);
          }

          newTransactionHashes.push(txResult.txHash!);

          // Update investment record with transaction hash
          await fetch('/api/investments/update-transaction.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              investment_id: investmentRecord.id,
              tx_hash: txResult.txHash
            })
          });
        }
      }

      setInvestmentIds(newInvestmentIds);
      setTransactionHashes(newTransactionHashes);

      // Track referral conversion for total amount
      if (user?.id && user?.username) {
        try {
          const referralTracked = await trackConversion(
            totalAmount,
            `multi_wallet_${newTransactionHashes.join('_')}`,
            user.id.toString(),
            user.username
          );

          if (referralTracked) {
            console.log('Multi-package referral conversion tracked successfully');
          }
        } catch (referralError) {
          console.error('Failed to track referral conversion:', referralError);
        }
      }

      setPurchaseStep('success');

      toast({
        title: "Multi-Package Purchase Successful!",
        description: `Successfully purchased ${totalPackages} packages via blockchain`,
      });

    } catch (error: any) {
      console.error("Multi-package wallet purchase failed:", error);

      toast({
        title: "Purchase Failed",
        description: error.message || "There was an error processing your multi-package purchase",
        variant: "destructive"
      });

      setPurchaseStep('confirm');
    } finally {
      setIsPurchasing(false);
    }
  };

  const handlePurchase = async () => {
    if (paymentMethod === 'credits') {
      return handleCreditPurchase();
    } else {
      return handleWalletPurchase();
    }
  };

  return (
    <Dialog open={isOpen} onOpenChange={handleClose}>
      <DialogContent className="max-w-2xl bg-charcoal border-gold/30 max-h-[90vh] overflow-y-auto">
        <DialogHeader className="relative">
          <Button
            onClick={handleClose}
            className="absolute -top-2 -right-2 h-8 w-8 p-0 bg-transparent hover:bg-white/10"
          >
            <X className="h-4 w-4" />
          </Button>
          <DialogTitle className="text-gold text-xl font-bold">
            <ShoppingCart className="h-5 w-5 inline mr-2" />
            <T k="multi_package_purchase" fallback="Multi-Package Purchase" />
          </DialogTitle>
        </DialogHeader>

        <div className="space-y-6">
          {/* Package Summary */}
          <Card className="bg-gray-800 border-gray-700">
            <CardContent className="p-4">
              <h3 className="text-white font-semibold mb-3 flex items-center">
                <Package className="h-4 w-4 mr-2 text-gold" />
                <T k="purchase_summary" fallback="Purchase Summary" />
              </h3>
              
              <div className="space-y-2 mb-4">
                {selections.map((sel) => (
                  <div key={sel.packageId} className="flex items-center justify-between bg-gray-700/50 rounded-lg p-3">
                    <div>
                      <span className="text-white font-medium">{sel.package.name}</span>
                      <span className="text-gray-400 ml-2">x{sel.quantity}</span>
                    </div>
                    <div className="text-right">
                      <div className="text-gold font-semibold">
                        ${(sel.package.price * sel.quantity).toLocaleString()}
                      </div>
                      <div className="text-xs text-gray-400">
                        {sel.package.shares * sel.quantity} shares
                      </div>
                    </div>
                  </div>
                ))}
              </div>

              <div className="border-t border-gray-600 pt-3">
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                  <div>
                    <div className="text-2xl font-bold text-white">{totalPackages}</div>
                    <div className="text-xs text-gray-400">
                      <T k="total_packages" fallback="Total Packages" />
                    </div>
                  </div>
                  <div>
                    <div className="text-2xl font-bold text-gold">${totalAmount.toLocaleString()}</div>
                    <div className="text-xs text-gray-400">
                      <T k="total_amount" fallback="Total Amount" />
                    </div>
                  </div>
                  <div>
                    <div className="text-2xl font-bold text-blue-400">{totalShares.toLocaleString()}</div>
                    <div className="text-xs text-gray-400">
                      <T k="total_shares" fallback="Total Shares" />
                    </div>
                  </div>
                  <div>
                    <div className="text-2xl font-bold text-green-400">${totalROI.toLocaleString()}</div>
                    <div className="text-xs text-gray-400">
                      <T k="total_roi" fallback="Total ROI" />
                    </div>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Payment Method Selection */}
          {purchaseStep === 'payment' && (
            <div className="space-y-4">
              <h3 className="text-white font-semibold">
                <T k="choose_payment_method" fallback="Choose Payment Method" />
              </h3>
              
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <Button
                  variant={paymentMethod === 'wallet' ? 'default' : 'outline'}
                  onClick={() => {
                    setPaymentMethod('wallet');
                    setPurchaseStep('wallet');
                  }}
                  className={`p-6 h-auto flex-col space-y-2 ${
                    paymentMethod === 'wallet'
                      ? 'bg-gold text-black'
                      : 'border-gray-600 text-white hover:bg-gray-800'
                  }`}
                >
                  <Wallet className="h-8 w-8" />
                  <div className="text-center">
                    <div className="font-semibold">
                      <T k="crypto_wallet" fallback="Crypto Wallet" />
                    </div>
                    <div className="text-sm opacity-80">
                      <T k="pay_with_usdt" fallback="Connect & Pay" />
                    </div>
                  </div>
                </Button>

                <Button
                  variant={paymentMethod === 'manual' ? 'default' : 'outline'}
                  onClick={() => {
                    setPaymentMethod('manual');
                    setPurchaseStep('manual');
                  }}
                  className={`p-6 h-auto flex-col space-y-2 ${
                    paymentMethod === 'manual'
                      ? 'bg-gold text-black'
                      : 'border-gray-600 text-white hover:bg-gray-800'
                  }`}
                >
                  <DollarSign className="h-8 w-8" />
                  <div className="text-center">
                    <div className="font-semibold">
                      <T k="manual_payment" fallback="Manual Payment" />
                    </div>
                    <div className="text-sm opacity-80">
                      <T k="pay_from_exchange" fallback="Pay from Binance/Exchange" />
                    </div>
                  </div>
                </Button>

                <Button
                  variant={paymentMethod === 'credits' ? 'default' : 'outline'}
                  onClick={() => {
                    setPaymentMethod('credits');
                    setPurchaseStep('terms');
                  }}
                  className={`p-6 h-auto flex-col space-y-2 ${
                    paymentMethod === 'credits'
                      ? 'bg-gold text-black'
                      : 'border-gray-600 text-white hover:bg-gray-800'
                  }`}
                >
                  <CreditCard className="h-8 w-8" />
                  <div className="text-center">
                    <div className="font-semibold">
                      <T k="account_credits" fallback="Account Credits" />
                    </div>
                    <div className="text-sm opacity-80">
                      {loadingCredits ? (
                        <T k="loading_credits" fallback="Loading..." />
                      ) : (
                        `$${userCredits.toFixed(2)} ${translate('available', 'available')}`
                      )}
                    </div>
                  </div>
                </Button>
              </div>
            </div>
          )}

          {/* Wallet Connection Steps */}
          {paymentMethod === 'wallet' && purchaseStep === 'wallet' && (
            <div className="space-y-4">
              <h3 className="text-white font-semibold">
                <T k="step_1_connect_wallet" fallback="Step 1: Connect Your Wallet" />
              </h3>
              {!walletAddress ? (
                <>
                  <WalletSelector
                    selected={selectedWallet}
                    setSelected={setSelectedWallet}
                    connecting={isConnecting}
                    onConnect={handleWalletConnect}
                  />
                  <WalletConnector
                    walletAddress={walletAddress}
                    isConnecting={isConnecting}
                    connectionError={connectionError}
                    connectWallet={handleWalletConnect}
                    selectedProvider={selectedWallet}
                  />
                </>
              ) : (
                <div className="bg-green-500/10 border border-green-500/30 rounded-lg p-4">
                  <div className="flex items-center gap-2 mb-2">
                    <CheckCircle className="h-4 w-4 text-green-400" />
                    <span className="text-green-400 font-medium">
                      <T k="wallet_connected" fallback="Wallet Connected" />
                    </span>
                  </div>
                  <p className="text-gray-300 text-sm">{walletAddress}</p>
                  <div className="flex gap-2 mt-3">
                    <Button
                      onClick={() => setPurchaseStep('chain')}
                      className="bg-gold text-black hover:opacity-90"
                    >
                      <T k="continue" fallback="Continue" />
                    </Button>
                    <Button
                      variant="outline"
                      onClick={disconnectWallet}
                      className="border-gray-600 text-white hover:bg-gray-800"
                    >
                      <T k="disconnect" fallback="Disconnect" />
                    </Button>
                  </div>
                </div>
              )}
            </div>
          )}

          {/* Chain Selection */}
          {paymentMethod === 'wallet' && purchaseStep === 'chain' && (
            <div className="space-y-4">
              <h3 className="text-white font-semibold">
                <T k="step_2_select_network" fallback="Step 2: Select Network" />
              </h3>
              <ChainSelector
                selectedChain={selectedChain}
                onChainSelect={handleChainSelect}
                currentChainId={chainId}
                isChainSwitching={isChainSwitching}
                setIsChainSwitching={setIsChainSwitching}
              />
            </div>
          )}

          {/* Balance Check */}
          {paymentMethod === 'wallet' && purchaseStep === 'balance' && selectedChain && (
            <div className="space-y-4">
              <h3 className="text-white font-semibold">
                <T k="step_3_check_balance" fallback="Step 3: Check Balance" />
              </h3>
              <BalanceChecker
                walletAddress={walletAddress!}
                requiredAmount={totalAmount}
                selectedChain={selectedChain}
                onBalanceCheck={handleBalanceCheck}
              />
            </div>
          )}

          {/* Terms Acceptance */}
          {purchaseStep === 'terms' && (
            <div className="space-y-4">
              <h3 className="text-white font-semibold">
                {paymentMethod === 'wallet' 
                  ? translate('step_4_accept_terms', 'Step 4: Accept Terms')
                  : translate('accept_terms', 'Accept Terms')
                }
              </h3>
              <TermsAcceptance
                onAcceptanceChange={handleTermsAcceptance}
                isRequired={true}
              />
            </div>
          )}

          {/* Confirmation */}
          {purchaseStep === 'confirm' && (
            <div className="space-y-4">
              <h3 className="text-white font-semibold">
                <T k="confirm_purchase" fallback="Confirm Purchase" />
              </h3>
              
              <div className="bg-gray-800 border border-gray-700 rounded-lg p-4">
                <div className="space-y-2 text-sm">
                  <div className="flex justify-between">
                    <span className="text-gray-400">
                      <T k="payment_method" fallback="Payment Method" />:
                    </span>
                    <span className="text-white">
                      {paymentMethod === 'wallet' 
                        ? translate('crypto_wallet', 'Crypto Wallet')
                        : translate('account_credits', 'Account Credits')
                      }
                    </span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-400">
                      <T k="total_packages" fallback="Total Packages" />:
                    </span>
                    <span className="text-white">{totalPackages}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-400">
                      <T k="total_amount" fallback="Total Amount" />:
                    </span>
                    <span className="text-gold font-semibold">${totalAmount.toLocaleString()}</span>
                  </div>
                  {paymentMethod === 'wallet' && (
                    <>
                      <div className="flex justify-between">
                        <span className="text-gray-400">
                          <T k="network" fallback="Network" />:
                        </span>
                        <span className="text-white">{selectedChain}</span>
                      </div>
                      <div className="flex justify-between">
                        <span className="text-gray-400">
                          <T k="wallet" fallback="Wallet" />:
                        </span>
                        <span className="text-white text-xs">{walletAddress}</span>
                      </div>
                    </>
                  )}
                </div>
              </div>

              <Button
                onClick={handlePurchase}
                disabled={isPurchasing}
                className="w-full bg-gold text-black hover:opacity-90 py-3 text-lg font-semibold"
              >
                {isPurchasing ? (
                  <>
                    <Loader2 className="h-5 w-5 mr-2 animate-spin" />
                    <T k="processing_purchase" fallback="Processing Purchase..." />
                  </>
                ) : (
                  <>
                    {paymentMethod === 'wallet' ? (
                      <Wallet className="h-5 w-5 mr-2" />
                    ) : (
                      <CreditCard className="h-5 w-5 mr-2" />
                    )}
                    <T k="confirm_purchase_amount" fallback="Confirm Purchase - ${amount}" />
                      .replace('${amount}', `$${totalAmount.toLocaleString()}`)
                  </>
                )}
              </Button>
            </div>
          )}

          {/* Processing */}
          {purchaseStep === 'processing' && (
            <div className="text-center py-8">
              <Loader2 className="h-12 w-12 animate-spin text-gold mx-auto mb-4" />
              <h3 className="text-white font-semibold mb-2">
                <T k="processing_multi_package_purchase" fallback="Processing Multi-Package Purchase..." />
              </h3>
              <p className="text-gray-400">
                <T k="please_wait_processing" fallback="Please wait while we process your purchase" />
              </p>
            </div>
          )}

          {/* Manual Payment Interface */}
          {paymentMethod === 'manual' && purchaseStep === 'manual' && (
            <ManualPaymentInterface
              totalAmount={totalAmount}
              onPaymentInitiated={handleManualPaymentInitiated}
              onBack={handleBackToPayment}
            />
          )}

          {/* Manual Payment Submitted */}
          {purchaseStep === 'manual_submitted' && (
            <div className="text-center py-8">
              <CheckCircle className="h-12 w-12 text-green-400 mx-auto mb-4" />
              <h3 className="text-white font-semibold mb-2">
                <T k="payment_proof_submitted" fallback="Payment Proof Submitted!" />
              </h3>
              <p className="text-gray-400 mb-6">
                <T k="manual_payment_review_message" fallback="Your payment proof has been submitted successfully. Our team will verify your payment within 24 hours and you'll receive an email confirmation once approved." />
              </p>

              <div className="bg-blue-500/10 border border-blue-500/30 rounded-lg p-4 mb-6">
                <div className="space-y-2 text-sm">
                  <div className="flex justify-between">
                    <span className="text-gray-400">
                      <T k="payment_id" fallback="Payment ID" />:
                    </span>
                    <span className="text-white font-mono">{transactionHashes[0]}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-400">
                      <T k="amount_submitted" fallback="Amount Submitted" />:
                    </span>
                    <span className="text-gold font-semibold">${totalAmount.toLocaleString()} USDT</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-400">
                      <T k="packages_pending" fallback="Packages Pending" />:
                    </span>
                    <span className="text-white">{totalPackages}</span>
                  </div>
                </div>
              </div>

              <div className="bg-yellow-500/10 border border-yellow-500/30 rounded-lg p-4 mb-6">
                <div className="flex items-start gap-3">
                  <Clock className="h-5 w-5 text-yellow-400 mt-0.5 flex-shrink-0" />
                  <div className="text-sm text-yellow-200">
                    <p className="font-medium mb-1">
                      <T k="next_steps_title" fallback="What happens next?" />
                    </p>
                    <ul className="space-y-1 text-left">
                      <li>• Your payment will be verified within 24 hours</li>
                      <li>• You'll receive email confirmation once approved</li>
                      <li>• Your investment packages will be activated automatically</li>
                      <li>• You can track the status in your dashboard</li>
                    </ul>
                  </div>
                </div>
              </div>

              <Button
                onClick={handleClose}
                className="bg-gold text-black hover:opacity-90"
              >
                <T k="close" fallback="Close" />
              </Button>
            </div>
          )}

          {/* Success */}
          {purchaseStep === 'success' && (
            <div className="text-center py-8">
              <CheckCircle className="h-12 w-12 text-green-400 mx-auto mb-4" />
              <h3 className="text-white font-semibold mb-2">
                <T k="purchase_successful" fallback="Purchase Successful!" />
              </h3>
              <p className="text-gray-400 mb-4">
                <T k="multi_package_purchase_complete" fallback="Your multi-package purchase has been completed successfully" />
              </p>
              
              <div className="bg-gray-800 border border-gray-700 rounded-lg p-4 mb-4">
                <div className="space-y-2 text-sm">
                  <div className="flex justify-between">
                    <span className="text-gray-400">
                      <T k="packages_purchased" fallback="Packages Purchased" />:
                    </span>
                    <span className="text-white">{totalPackages}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-400">
                      <T k="total_invested" fallback="Total Invested" />:
                    </span>
                    <span className="text-gold font-semibold">${totalAmount.toLocaleString()}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-400">
                      <T k="total_shares" fallback="Total Shares" />:
                    </span>
                    <span className="text-white">{totalShares.toLocaleString()}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-400">
                      <T k="expected_roi" fallback="Expected ROI" />:
                    </span>
                    <span className="text-green-400">${totalROI.toLocaleString()}</span>
                  </div>
                </div>
              </div>

              <Button
                onClick={handleClose}
                className="bg-gold text-black hover:opacity-90"
              >
                <T k="close" fallback="Close" />
              </Button>
            </div>
          )}
        </div>
      </DialogContent>
    </Dialog>
  );
};

export default MultiPackagePurchaseDialog;

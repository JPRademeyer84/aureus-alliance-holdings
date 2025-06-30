import React, { useState, useEffect } from "react";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { useSimpleTranslation as useTranslation, ST as T } from '@/components/SimpleTranslator';
import { useWalletConnection, WalletProviderName } from "@/pages/investment/useWalletConnection";
import { WalletSelector } from "@/pages/investment/WalletSelector";
import WalletConnector from "@/pages/investment/WalletConnector";
import ChainSelector from "@/components/investment/ChainSelector";
import BalanceChecker from "@/components/investment/BalanceChecker";
import TermsAcceptance, { TermsAcceptanceData } from "@/components/investment/TermsAcceptance";
import { useToast } from "@/hooks/use-toast";
import { X, Shield, CheckCircle, AlertTriangle } from "@/components/SafeIcons";

// Safe icons for purchase dialog
const Wallet = ({ className }: { className?: string }) => <span className={className}>👛</span>;
const CreditCard = ({ className }: { className?: string }) => <span className={className}>💳</span>;
const ExternalLink = ({ className }: { className?: string }) => <span className={className}>🔗</span>;
const Gift = ({ className }: { className?: string }) => <span className={className}>🎁</span>;
const DollarSign = ({ className }: { className?: string }) => <span className={className}>💲</span>;
import { cn } from "@/lib/utils";
import {
  SupportedChain,
  switchToChain,
  sendUSDTTransaction,
  waitForTransactionConfirmation,
  BalanceInfo
} from "@/pages/investment/utils/web3Transaction";
import {
  createInvestmentRecord,
  updateInvestmentStatus,
  getBlockExplorerUrl
} from "@/pages/investment/utils/investmentHistory";
import { useReferralConversion } from "@/hooks/useReferralTracking";
import { useUser } from "@/contexts/UserContext";

interface PurchaseDialogProps {
  isOpen: boolean;
  onClose: () => void;
  package: {
    id: string;
    name: string;
    price: number;
    shares: number;
    roi: number;
    annualDividends: number;
    quarterlyDividends: number;
    bonuses: string[];
  };
}

const PurchaseDialog: React.FC<PurchaseDialogProps> = ({
  isOpen,
  onClose,
  package: pkg
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
  const [purchaseStep, setPurchaseStep] = useState<'payment' | 'wallet' | 'chain' | 'balance' | 'terms' | 'confirm' | 'processing' | 'success'>('payment');
  const [transactionHash, setTransactionHash] = useState<string | null>(null);
  const [investmentId, setInvestmentId] = useState<string | null>(null);
  const [paymentMethod, setPaymentMethod] = useState<'wallet' | 'credits'>('wallet');
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

  // Fetch user credits when component mounts
  useEffect(() => {
    if (isOpen) {
      fetchUserCredits();
    }
  }, [isOpen]);

  const fetchUserCredits = async () => {
    setLoadingCredits(true);
    try {
      const response = await fetch(`${ApiConfig.endpoints.coupons.index}?action=user_credits`, {
        credentials: 'include'
      });
      const data = await response.json();

      if (data.success) {
        setUserCredits(parseFloat(data.data.available_credits) || 0);
      } else {
        console.error('Failed to fetch credits:', data.error);
        setUserCredits(0);
      }
    } catch (error) {
      console.error('Error fetching credits:', error);
      setUserCredits(0);
    } finally {
      setLoadingCredits(false);
    }
  };

  const handlePaymentMethodSelect = (method: 'wallet' | 'credits') => {
    setPaymentMethod(method);
    if (method === 'wallet') {
      setPurchaseStep('wallet');
    } else {
      // For credits, skip directly to terms
      setPurchaseStep('terms');
    }
  };

  const handleWalletConnect = async () => {
    await connectWallet(selectedWallet);
    if (walletAddress) {
      setPurchaseStep('chain');
    }
  };

  const handleChainSelect = async (chain: SupportedChain) => {
    if (!walletAddress) return;

    setIsChainSwitching(true);
    try {
      const success = await switchToChain(selectedWallet, chain);
      if (success) {
        setSelectedChain(chain);
        setPurchaseStep('balance');
      } else {
        toast({
          title: "Chain Switch Failed",
          description: "Failed to switch to the selected chain",
          variant: "destructive"
        });
      }
    } catch (error) {
      toast({
        title: "Chain Switch Error",
        description: "Error switching chains. Please try again.",
        variant: "destructive"
      });
    } finally {
      setIsChainSwitching(false);
    }
  };

  const handleBalanceCheck = (sufficient: boolean, balance: BalanceInfo) => {
    setHasEnoughBalance(sufficient);
    setCurrentBalance(balance);
    if (sufficient) {
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

  const handleCreditPurchase = async () => {
    if (!termsAccepted) {
      toast({
        title: "Terms Not Accepted",
        description: "Please accept the terms and conditions to proceed",
        variant: "destructive"
      });
      return;
    }

    if (userCredits < pkg.price) {
      toast({
        title: "Insufficient Credits",
        description: `You need $${pkg.price.toFixed(2)} but only have $${userCredits.toFixed(2)} in credits`,
        variant: "destructive"
      });
      return;
    }

    setIsPurchasing(true);
    setPurchaseStep('processing');

    try {
      // Create investment record with credit payment
      const investmentRecord = await createInvestmentRecord({
        packageName: pkg.name,
        amount: pkg.price,
        shares: pkg.shares,
        roi: pkg.roi,
        txHash: '', // No transaction hash for credit payments
        chainId: '', // No chain for credit payments
        walletAddress: '', // No wallet for credit payments
        userEmail: '',
        userName: '',
        termsData: termsData,
        paymentMethod: 'credits'
      });

      setInvestmentId(investmentRecord.id);

      // Track referral conversion if user was referred
      if (user?.id && user?.username) {
        try {
          const referralTracked = await trackConversion(
            pkg.price,
            `credit_${investmentRecord.id}`, // Use investment ID as transaction reference
            user.id.toString(),
            user.username
          );

          if (referralTracked) {
            console.log('Referral conversion tracked successfully');
          }
        } catch (referralError) {
          console.error('Failed to track referral conversion:', referralError);
          // Don't fail the purchase if referral tracking fails
        }
      }

      // Refresh user credits
      await fetchUserCredits();

      setPurchaseStep('success');

      toast({
        title: "Purchase Successful!",
        description: `Successfully purchased ${pkg.name} package with credits`,
      });

    } catch (error: any) {
      console.error("Credit purchase failed:", error);

      toast({
        title: "Purchase Failed",
        description: error.message || "There was an error processing your purchase",
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
    }

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
      // Step 1: Create investment record
      const investmentRecord = await createInvestmentRecord({
        packageName: pkg.name,
        amount: pkg.price,
        shares: pkg.shares,
        roi: pkg.roi,
        txHash: '', // Will be updated after transaction
        chainId: selectedChain,
        walletAddress: walletAddress,
        userEmail: '', // Could be collected in a form
        userName: '',   // Could be collected in a form
        termsData: termsData // Include terms acceptance data
      });

      setInvestmentId(investmentRecord.id);

      // Step 2: Send blockchain transaction
      const txResult = await sendUSDTTransaction(
        selectedWallet,
        walletAddress,
        pkg.price,
        selectedChain
      );

      if (!txResult.success) {
        throw new Error(txResult.error || "Transaction failed");
      }

      console.log(`Transaction result:`, txResult);
      console.log(`Setting transaction hash: ${txResult.txHash}`);
      setTransactionHash(txResult.txHash!);

      // Step 3: Update investment record with transaction hash
      await updateInvestmentStatus(investmentRecord.id, 'pending', txResult.txHash);

      // Step 4: Wait for transaction confirmation
      const confirmed = await waitForTransactionConfirmation(
        txResult.txHash!,
        selectedChain,
        300000 // 5 minutes
      );

      if (confirmed) {
        await updateInvestmentStatus(investmentRecord.id, 'completed');

        // Track referral conversion if user was referred
        if (user?.id && user?.username && txResult.txHash) {
          try {
            const referralTracked = await trackConversion(
              pkg.price,
              txResult.txHash,
              user.id.toString(),
              user.username
            );

            if (referralTracked) {
              console.log('Referral conversion tracked successfully');
            }
          } catch (referralError) {
            console.error('Failed to track referral conversion:', referralError);
            // Don't fail the purchase if referral tracking fails
          }
        }

        setPurchaseStep('success');

        toast({
          title: "Purchase Successful!",
          description: `Successfully purchased ${pkg.name} package`,
        });
      } else {
        throw new Error("Transaction confirmation timeout");
      }

    } catch (error: any) {
      console.error("Purchase failed:", error);

      // Update investment record as failed if we have an ID
      if (investmentId) {
        try {
          await updateInvestmentStatus(investmentId, 'failed');
        } catch (updateError) {
          console.error("Failed to update investment status:", updateError);
        }
      }

      toast({
        title: "Purchase Failed",
        description: error.message || "There was an error processing your purchase",
        variant: "destructive"
      });

      setPurchaseStep('confirm'); // Go back to confirm step
    } finally {
      setIsPurchasing(false);
    }
  };

  const resetDialog = () => {
    setPurchaseStep('payment');
    setSelectedChain(null);
    setHasEnoughBalance(false);
    setCurrentBalance(null);
    setTermsAccepted(false);
    setTermsData(null);
    setTransactionHash(null);
    setInvestmentId(null);
    setIsPurchasing(false);
    setIsChainSwitching(false);
    setPaymentMethod('wallet');
  };

  const handleClose = () => {
    resetDialog();
    onClose();
  };

  return (
    <Dialog open={isOpen} onOpenChange={handleClose}>
      <DialogContent className="max-w-lg bg-charcoal border-gold/30 max-h-[90vh] overflow-y-auto">
        <DialogHeader className="relative">
          <Button
            onClick={handleClose}
            className="absolute -top-2 -right-2 h-8 w-8 p-0 bg-transparent hover:bg-white/10"
          >
            <X className="h-4 w-4" />
          </Button>
          <DialogTitle className="text-gold text-xl font-bold">
            <T k="purchase" fallback="Purchase" /> {pkg.name}
          </DialogTitle>

          {/* Progress Steps */}
          <div className="flex items-center justify-center gap-2 mt-4">
            {(paymentMethod === 'wallet'
              ? ['payment', 'wallet', 'chain', 'balance', 'terms', 'confirm', 'processing']
              : ['payment', 'terms', 'confirm', 'processing']
            ).map((step, index, steps) => (
              <div
                key={step}
                className={`h-2 w-6 rounded-full transition-colors ${
                  purchaseStep === step
                    ? 'bg-gold'
                    : steps.indexOf(purchaseStep) > index
                    ? 'bg-gold/50'
                    : 'bg-gray-600'
                }`}
              />
            ))}
          </div>
        </DialogHeader>

        <div className="space-y-6">
          {/* Package Summary */}
          <div className="bg-black/30 rounded-lg p-4 border border-gold/20">
            <h3 className="text-gold font-semibold mb-2">
              <T k="package_details" fallback="Package Details" />
            </h3>
            <div className="space-y-1 text-sm">
              <div className="flex justify-between">
                <span className="text-gray-300">
                  <T k="price" fallback="Price:" />
                </span>
                <span className="text-gold font-semibold">${pkg.price.toLocaleString()}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-300">
                  <T k="shares" fallback="Shares:" />
                </span>
                <span className="text-white">{pkg.shares.toLocaleString()}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-300">
                  <T k="roi" fallback="ROI:" />
                </span>
                <span className="text-green-400">${pkg.roi.toLocaleString()}</span>
              </div>
            </div>
          </div>

          {/* Step 0: Payment Method Selection */}
          {purchaseStep === 'payment' && (
            <div className="space-y-4">
              <h3 className="text-white font-semibold">
                <T k="choose_payment_method" fallback="Choose Payment Method" />
              </h3>

              <div className="grid grid-cols-1 gap-4">
                {/* Credit Payment Option */}
                <div
                  className={`border-2 rounded-lg p-4 cursor-pointer transition-all ${
                    paymentMethod === 'credits'
                      ? 'border-gold bg-gold/10'
                      : 'border-gray-600 hover:border-gray-500'
                  }`}
                  onClick={() => handlePaymentMethodSelect('credits')}
                >
                  <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-3">
                      <Gift className="w-6 h-6 text-gold" />
                      <div>
                        <h4 className="text-white font-semibold">
                          <T k="pay_with_credits" fallback="Pay with Credits" />
                        </h4>
                        <p className="text-sm text-gray-400">
                          <T k="use_nft_credits_instant" fallback="Use your NFT credits for instant purchase" />
                        </p>
                      </div>
                    </div>
                    <div className="text-right">
                      {loadingCredits ? (
                        <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-gold"></div>
                      ) : (
                        <>
                          <div className="text-gold font-semibold">${userCredits.toFixed(2)}</div>
                          <div className="text-xs text-gray-400">
                            <T k="available" fallback="Available" />
                          </div>
                        </>
                      )}
                    </div>
                  </div>

                  {userCredits < pkg.price && (
                    <div className="mt-2 p-2 bg-red-900/30 border border-red-500/30 rounded text-sm text-red-400">
                      <T k="insufficient_credits_need_more" fallback="Insufficient credits. Need ${amount} more." />
                    </div>
                  )}
                </div>

                {/* Wallet Payment Option */}
                <div
                  className={`border-2 rounded-lg p-4 cursor-pointer transition-all ${
                    paymentMethod === 'wallet'
                      ? 'border-gold bg-gold/10'
                      : 'border-gray-600 hover:border-gray-500'
                  }`}
                  onClick={() => handlePaymentMethodSelect('wallet')}
                >
                  <div className="flex items-center space-x-3">
                    <Wallet className="w-6 h-6 text-blue-400" />
                    <div>
                      <h4 className="text-white font-semibold">
                        <T k="pay_with_wallet" fallback="Pay with Wallet" />
                      </h4>
                      <p className="text-sm text-gray-400">
                        <T k="connect_crypto_wallet_usdt" fallback="Connect your crypto wallet and pay with USDT" />
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              {paymentMethod === 'credits' && userCredits >= pkg.price && (
                <Button
                  onClick={() => setPurchaseStep('terms')}
                  className="w-full bg-gold-gradient text-black font-semibold py-3"
                >
                  <T k="continue_with_credits" fallback="Continue with Credits" />
                </Button>
              )}
            </div>
          )}

          {/* Step 1: Wallet Connection */}
          {purchaseStep === 'wallet' && (
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
                    <span className="text-green-400 font-semibold">
                      <T k="wallet_connected" fallback="Wallet Connected" />
                    </span>
                  </div>
                  <p className="text-sm text-gray-300 mb-3">
                    {walletAddress.substring(0, 6)}...{walletAddress.substring(walletAddress.length - 4)}
                  </p>
                  <div className="flex gap-2">
                    <Button
                      onClick={() => setPurchaseStep('chain')}
                      className="bg-gold-gradient text-black font-semibold"
                    >
                      <T k="continue" fallback="Continue" />
                    </Button>
                    <Button
                      onClick={disconnectWallet}
                      variant="outline"
                      className="text-red-400 border-red-500/30 hover:bg-red-500/20"
                      size="sm"
                    >
                      <T k="disconnect" fallback="Disconnect" />
                    </Button>
                  </div>
                </div>
              )}
            </div>
          )}

          {/* Step 2: Chain Selection */}
          {purchaseStep === 'chain' && walletAddress && (
            <div className="space-y-4">
              <h3 className="text-white font-semibold">
                <T k="step_2_select_chain" fallback="Step 2: Select Payment Chain" />
              </h3>
              <ChainSelector
                selectedChain={selectedChain}
                onChainSelect={handleChainSelect}
                walletProvider={selectedWallet}
                isLoading={isChainSwitching}
                disabled={isChainSwitching}
              />
            </div>
          )}

          {/* Step 3: Balance Check */}
          {purchaseStep === 'balance' && walletAddress && selectedChain && (
            <div className="space-y-4">
              <h3 className="text-white font-semibold">
                <T k="step_3_check_balance" fallback="Step 3: Check Balance" />
              </h3>
              <BalanceChecker
                walletAddress={walletAddress}
                walletProvider={selectedWallet}
                selectedChain={selectedChain}
                requiredAmount={Number(pkg.price)}
                onBalanceCheck={handleBalanceCheck}
              />
            </div>
          )}

          {/* Step 4: Terms and Conditions */}
          {purchaseStep === 'terms' && (
            (paymentMethod === 'credits' || (walletAddress && selectedChain && hasEnoughBalance))
          ) && (
            <div className="space-y-4">
              <h3 className="text-white font-semibold">
                {paymentMethod === 'credits' ?
                  <T k="step_2_terms_conditions" fallback="Step 2: Terms & Conditions" /> :
                  <T k="step_4_terms_conditions" fallback="Step 4: Terms & Conditions" />
                }
              </h3>
              <TermsAcceptance
                onAcceptanceChange={handleTermsAcceptance}
                isRequired={true}
              />
            </div>
          )}

          {/* Step 5: Confirm Purchase */}
          {purchaseStep === 'confirm' && termsAccepted && (
            (paymentMethod === 'credits' || (walletAddress && selectedChain && hasEnoughBalance))
          ) && (
            <div className="space-y-4">
              <h3 className="text-white font-semibold">
                {paymentMethod === 'credits' ?
                  <T k="step_3_confirm_purchase" fallback="Step 3: Confirm Purchase" /> :
                  <T k="step_5_confirm_purchase" fallback="Step 5: Confirm Purchase" />
                }
              </h3>

              <div className="bg-blue-500/10 border border-blue-500/30 rounded-lg p-4">
                <div className="space-y-3">
                  <div className="flex items-center gap-2">
                    <Shield className="h-4 w-4 text-blue-400" />
                    <span className="text-blue-400 font-semibold">
                      <T k="transaction_summary" fallback="Transaction Summary" />
                    </span>
                  </div>

                  <div className="space-y-2 text-sm">
                    <div className="flex justify-between">
                      <span className="text-gray-300">
                        <T k="package" fallback="Package:" />
                      </span>
                      <span className="text-white">{pkg.name}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-gray-300">
                        <T k="amount" fallback="Amount:" />
                      </span>
                      <span className="text-gold font-semibold">
                        ${pkg.price.toLocaleString()} {paymentMethod === 'credits' ?
                          translate('credits', 'Credits') :
                          translate('usdt', 'USDT')
                        }
                      </span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-gray-300">
                        <T k="payment_method" fallback="Payment Method:" />
                      </span>
                      <span className="text-white flex items-center gap-1">
                        {paymentMethod === 'credits' ? (
                          <>
                            <Gift className="w-4 h-4" />
                            <T k="credits" fallback="Credits" />
                          </>
                        ) : (
                          <>
                            <Wallet className="w-4 h-4" />
                            <T k="wallet" fallback="Wallet" />
                          </>
                        )}
                      </span>
                    </div>
                    {paymentMethod === 'wallet' && selectedChain && (
                      <>
                        <div className="flex justify-between">
                          <span className="text-gray-300">
                            <T k="chain" fallback="Chain:" />
                          </span>
                          <span className="text-white">{selectedChain.toUpperCase()}</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-gray-300">
                            <T k="your_balance" fallback="Your Balance:" />
                          </span>
                          <span className="text-green-400">{currentBalance?.formatted} <T k="usdt" fallback="USDT" /></span>
                        </div>
                      </>
                    )}
                    {paymentMethod === 'credits' && (
                      <div className="flex justify-between">
                        <span className="text-gray-300">
                          <T k="your_credits" fallback="Your Credits:" />
                        </span>
                        <span className="text-green-400">${userCredits.toFixed(2)}</span>
                      </div>
                    )}
                  </div>
                </div>
              </div>

              <Button
                onClick={handlePurchase}
                disabled={isPurchasing}
                className="w-full bg-gold-gradient text-black font-semibold py-3"
              >
                {isPurchasing ? (
                  <>
                    <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-black mr-2"></div>
                    <T k="processing_payment_type" fallback="Processing {type}..." />
                    {paymentMethod === 'credits' ?
                      translate('credit_payment', 'Credit Payment') :
                      translate('transaction', 'Transaction')
                    }
                  </>
                ) : (
                  translate('confirm_purchase_amount_currency', 'Confirm Purchase - ${amount} {currency}')
                    .replace('${amount}', `$${pkg.price.toLocaleString()}`)
                    .replace('{currency}', paymentMethod === 'credits' ?
                      translate('credits', 'Credits') :
                      translate('usdt', 'USDT')
                    )
                )}
              </Button>
            </div>
          )}

          {/* Step 6: Processing */}
          {purchaseStep === 'processing' && (
            <div className="space-y-4 text-center">
              <h3 className="text-white font-semibold">
                <T k="processing_transaction_title" fallback="Processing Transaction" />
              </h3>

              <div className="bg-yellow-500/10 border border-yellow-500/30 rounded-lg p-6">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-gold mx-auto mb-4"></div>
                <div className="space-y-2">
                  <p className="text-yellow-400 font-medium">
                    <T k="transaction_in_progress" fallback="Transaction in Progress" />
                  </p>
                  <p className="text-sm text-gray-300">
                    <T k="confirm_wallet_wait_blockchain" fallback="Please confirm the transaction in your wallet and wait for blockchain confirmation." />
                  </p>
                  {transactionHash && (
                    <div className="mt-3 pt-3 border-t border-yellow-500/20">
                      <p className="text-xs text-gray-400 mb-2">
                        <T k="transaction_hash" fallback="Transaction Hash:" />
                      </p>
                      <div className="flex items-center justify-center gap-2">
                        <code className="text-xs bg-black/30 px-2 py-1 rounded">
                          {transactionHash.slice(0, 10)}...{transactionHash.slice(-8)}
                        </code>
                        {selectedChain && (
                          <Button
                            variant="ghost"
                            size="sm"
                            className="h-6 w-6 p-0"
                            onClick={() => window.open(getBlockExplorerUrl(transactionHash, selectedChain), '_blank')}
                          >
                            <ExternalLink className="h-3 w-3" />
                          </Button>
                        )}
                      </div>
                    </div>
                  )}
                </div>
              </div>
            </div>
          )}

          {/* Step 7: Success */}
          {purchaseStep === 'success' && (
            <div className="space-y-4 text-center">
              <h3 className="text-white font-semibold">
                <T k="purchase_successful" fallback="Purchase Successful!" />
              </h3>

              <div className="bg-green-500/10 border border-green-500/30 rounded-lg p-6">
                <CheckCircle className="h-12 w-12 text-green-400 mx-auto mb-4" />
                <div className="space-y-2">
                  <p className="text-green-400 font-medium">
                    <T k="transaction_confirmed" fallback="Transaction Confirmed" />
                  </p>
                  <p className="text-sm text-gray-300">
                    <T k="package_successfully_purchased" fallback="Your {package} package has been successfully purchased and added to your investment portfolio." />
                  </p>
                  {transactionHash && selectedChain && (
                    <div className="mt-4 pt-4 border-t border-green-500/20">
                      <div className="flex items-center justify-center gap-2">
                        <span className="text-xs text-gray-400">
                          <T k="view_on_explorer" fallback="View on Explorer:" />
                        </span>
                        <Button
                          variant="ghost"
                          size="sm"
                          className="h-6 px-2 text-xs text-green-400 hover:text-green-300"
                          onClick={() => window.open(getBlockExplorerUrl(transactionHash, selectedChain), '_blank')}
                        >
                          <ExternalLink className="h-3 w-3 mr-1" />
                          <T k="transaction" fallback="Transaction" />
                        </Button>
                      </div>
                    </div>
                  )}
                </div>
              </div>

              <Button
                onClick={handleClose}
                className="w-full bg-gold-gradient text-black font-semibold py-3"
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

export default PurchaseDialog;

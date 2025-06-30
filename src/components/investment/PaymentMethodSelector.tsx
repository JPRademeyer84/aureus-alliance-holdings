import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useToast } from '@/hooks/use-toast';
import { 
  CreditCard, 
  Building2, 
  Globe, 
  Shield, 
  Clock, 
  CheckCircle,
  AlertTriangle,
  Info
} from 'lucide-react';
import ApiConfig from '@/config/api';

interface PaymentConfig {
  country_code: string;
  country_name: string;
  crypto_payments_allowed: boolean;
  bank_payments_allowed: boolean;
  default_payment_method: 'crypto' | 'bank';
  currency_code: string;
  kyc_required_level: number;
  investment_limit_usd?: number;
}

interface BankAccount {
  id: string;
  account_name: string;
  bank_name: string;
  account_number: string;
  swift_code?: string;
  iban?: string;
  account_holder_name: string;
  currency_code: string;
  bank_address?: string;
  processing_time_days: number;
}

interface PaymentMethodSelectorProps {
  investmentPackage: string;
  investmentAmount: number;
  onPaymentMethodSelected: (method: 'crypto' | 'bank', details?: any) => void;
  userId?: string;
}

const PaymentMethodSelector: React.FC<PaymentMethodSelectorProps> = ({
  investmentPackage,
  investmentAmount,
  onPaymentMethodSelected,
  userId
}) => {
  const [loading, setLoading] = useState(true);
  const [detectedCountry, setDetectedCountry] = useState<string>('');
  const [selectedCountry, setSelectedCountry] = useState<string>('');
  const [paymentConfig, setPaymentConfig] = useState<PaymentConfig | null>(null);
  const [availableMethods, setAvailableMethods] = useState<string[]>([]);
  const [selectedMethod, setSelectedMethod] = useState<'crypto' | 'bank' | null>(null);
  const [bankAccountDetails, setBankAccountDetails] = useState<BankAccount | null>(null);
  const [nextSteps, setNextSteps] = useState<Record<string, string>>({});
  const { toast } = useToast();

  useEffect(() => {
    detectCountryAndPaymentMethods();
  }, []);

  const detectCountryAndPaymentMethods = async () => {
    try {
      setLoading(true);
      
      const response = await fetch(`${ApiConfig.baseUrl}/payments/country-detection.php?user_id=${userId}`);
      const data = await response.json();
      
      if (data.success) {
        setDetectedCountry(data.detected_country);
        setSelectedCountry(data.detected_country);
        setPaymentConfig(data.payment_config);
        setAvailableMethods(data.available_methods);
        
        // Auto-select recommended method
        if (data.recommended_method && data.available_methods.includes(data.recommended_method)) {
          setSelectedMethod(data.recommended_method);
        }
      } else {
        throw new Error(data.error || 'Failed to detect country');
      }
    } catch (error) {
      console.error('Country detection error:', error);
      toast({
        title: 'Detection Failed',
        description: 'Using default payment options',
        variant: 'destructive',
      });
      
      // Fallback to default configuration
      setDetectedCountry('ZZZ');
      setSelectedCountry('ZZZ');
      setAvailableMethods(['crypto', 'bank']);
      setSelectedMethod('crypto');
    } finally {
      setLoading(false);
    }
  };

  const handleCountryChange = async (countryCode: string) => {
    try {
      setSelectedCountry(countryCode);
      
      // Re-fetch payment configuration for selected country
      const response = await fetch(`${ApiConfig.baseUrl}/payments/country-detection.php?user_id=${userId}`);
      const data = await response.json();
      
      if (data.success) {
        setPaymentConfig(data.payment_config);
        setAvailableMethods(data.available_methods);
        setSelectedMethod(data.recommended_method);
      }
    } catch (error) {
      console.error('Country change error:', error);
    }
  };

  const handleMethodSelection = async (method: 'crypto' | 'bank') => {
    try {
      setSelectedMethod(method);
      
      const response = await fetch(`${ApiConfig.baseUrl}/payments/country-detection.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          user_id: userId,
          country_code: selectedCountry,
          payment_method: method,
          investment_package: investmentPackage,
          investment_amount: investmentAmount
        }),
      });

      const data = await response.json();
      
      if (data.success) {
        setBankAccountDetails(data.bank_account_details);
        setNextSteps(data.next_steps);
        
        toast({
          title: 'Payment Method Selected',
          description: `${method === 'crypto' ? 'Cryptocurrency' : 'Bank Transfer'} payment selected`,
        });
      } else {
        throw new Error(data.error || 'Failed to select payment method');
      }
    } catch (error) {
      console.error('Method selection error:', error);
      toast({
        title: 'Selection Failed',
        description: error instanceof Error ? error.message : 'Failed to select payment method',
        variant: 'destructive',
      });
    }
  };

  const handleProceed = () => {
    if (!selectedMethod) {
      toast({
        title: 'Selection Required',
        description: 'Please select a payment method to proceed',
        variant: 'destructive',
      });
      return;
    }

    onPaymentMethodSelected(selectedMethod, {
      country: selectedCountry,
      paymentConfig,
      bankAccountDetails,
      nextSteps
    });
  };

  if (loading) {
    return (
      <Card>
        <CardContent className="flex items-center justify-center p-8">
          <div className="flex items-center gap-2">
            <Globe className="w-5 h-5 animate-spin" />
            <span>Detecting your location and payment options...</span>
          </div>
        </CardContent>
      </Card>
    );
  }

  return (
    <div className="space-y-6">
      {/* Country Selection */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Globe className="w-5 h-5" />
            Location & Payment Options
          </CardTitle>
          <CardDescription>
            Your location determines available payment methods
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="flex items-center gap-4">
            <div className="flex-1">
              <label className="text-sm font-medium">Detected Country</label>
              <p className="text-lg">{paymentConfig?.country_name || 'Unknown'}</p>
            </div>
            <div className="flex-1">
              <label className="text-sm font-medium">Currency</label>
              <p className="text-lg">{paymentConfig?.currency_code || 'USD'}</p>
            </div>
          </div>
          
          {detectedCountry !== selectedCountry && (
            <Alert>
              <Info className="w-4 h-4" />
              <AlertDescription>
                If the detected country is incorrect, please contact support to update your location.
              </AlertDescription>
            </Alert>
          )}
        </CardContent>
      </Card>

      {/* Payment Method Selection */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        {/* Cryptocurrency Payment */}
        {availableMethods.includes('crypto') && (
          <Card className={`cursor-pointer transition-all ${selectedMethod === 'crypto' ? 'ring-2 ring-blue-500 bg-blue-50 dark:bg-blue-950' : 'hover:shadow-md'}`}>
            <CardHeader>
              <div className="flex items-center justify-between">
                <CardTitle className="flex items-center gap-2">
                  <CreditCard className="w-5 h-5" />
                  Cryptocurrency Payment
                </CardTitle>
                {paymentConfig?.default_payment_method === 'crypto' && (
                  <Badge variant="default">Recommended</Badge>
                )}
              </div>
              <CardDescription>
                Pay with USDT using your SafePal wallet
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <div className="flex items-center gap-2 text-sm">
                  <CheckCircle className="w-4 h-4 text-green-500" />
                  <span>Instant confirmation</span>
                </div>
                <div className="flex items-center gap-2 text-sm">
                  <CheckCircle className="w-4 h-4 text-green-500" />
                  <span>Automated processing</span>
                </div>
                <div className="flex items-center gap-2 text-sm">
                  <CheckCircle className="w-4 h-4 text-green-500" />
                  <span>Lower fees</span>
                </div>
              </div>
              
              <Button 
                onClick={() => handleMethodSelection('crypto')}
                variant={selectedMethod === 'crypto' ? 'default' : 'outline'}
                className="w-full"
              >
                {selectedMethod === 'crypto' ? 'Selected' : 'Select Crypto Payment'}
              </Button>
            </CardContent>
          </Card>
        )}

        {/* Bank Transfer Payment */}
        {availableMethods.includes('bank') && (
          <Card className={`cursor-pointer transition-all ${selectedMethod === 'bank' ? 'ring-2 ring-blue-500 bg-blue-50 dark:bg-blue-950' : 'hover:shadow-md'}`}>
            <CardHeader>
              <div className="flex items-center justify-between">
                <CardTitle className="flex items-center gap-2">
                  <Building2 className="w-5 h-5" />
                  Bank Transfer
                </CardTitle>
                {paymentConfig?.default_payment_method === 'bank' && (
                  <Badge variant="default">Recommended</Badge>
                )}
              </div>
              <CardDescription>
                Traditional bank transfer to company account
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <div className="flex items-center gap-2 text-sm">
                  <Clock className="w-4 h-4 text-yellow-500" />
                  <span>3-5 business days processing</span>
                </div>
                <div className="flex items-center gap-2 text-sm">
                  <Shield className="w-4 h-4 text-green-500" />
                  <span>Traditional banking security</span>
                </div>
                <div className="flex items-center gap-2 text-sm">
                  <AlertTriangle className="w-4 h-4 text-orange-500" />
                  <span>Manual verification required</span>
                </div>
              </div>
              
              <Button 
                onClick={() => handleMethodSelection('bank')}
                variant={selectedMethod === 'bank' ? 'default' : 'outline'}
                className="w-full"
              >
                {selectedMethod === 'bank' ? 'Selected' : 'Select Bank Transfer'}
              </Button>
            </CardContent>
          </Card>
        )}
      </div>

      {/* Bank Account Details */}
      {selectedMethod === 'bank' && bankAccountDetails && (
        <Card>
          <CardHeader>
            <CardTitle>Bank Account Details</CardTitle>
            <CardDescription>
              Use these details for your bank transfer
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="text-sm font-medium text-muted-foreground">Account Name</label>
                <p className="font-mono">{bankAccountDetails.account_name}</p>
              </div>
              <div>
                <label className="text-sm font-medium text-muted-foreground">Bank Name</label>
                <p className="font-mono">{bankAccountDetails.bank_name}</p>
              </div>
              <div>
                <label className="text-sm font-medium text-muted-foreground">Account Number</label>
                <p className="font-mono">{bankAccountDetails.account_number}</p>
              </div>
              {bankAccountDetails.swift_code && (
                <div>
                  <label className="text-sm font-medium text-muted-foreground">SWIFT Code</label>
                  <p className="font-mono">{bankAccountDetails.swift_code}</p>
                </div>
              )}
              <div>
                <label className="text-sm font-medium text-muted-foreground">Account Holder</label>
                <p className="font-mono">{bankAccountDetails.account_holder_name}</p>
              </div>
              <div>
                <label className="text-sm font-medium text-muted-foreground">Currency</label>
                <p className="font-mono">{bankAccountDetails.currency_code}</p>
              </div>
            </div>
            
            {bankAccountDetails.bank_address && (
              <div>
                <label className="text-sm font-medium text-muted-foreground">Bank Address</label>
                <p className="text-sm">{bankAccountDetails.bank_address}</p>
              </div>
            )}
            
            <Alert>
              <Info className="w-4 h-4" />
              <AlertDescription>
                <strong>Important:</strong> You will receive a unique reference number after proceeding. 
                Include this reference in your bank transfer to ensure proper processing.
              </AlertDescription>
            </Alert>
          </CardContent>
        </Card>
      )}

      {/* Commission Information */}
      <Card>
        <CardHeader>
          <CardTitle>Commission Structure</CardTitle>
          <CardDescription>
            Regardless of payment method, all commissions are paid in USDT
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-3 gap-4 text-center">
            <div>
              <div className="text-2xl font-bold text-blue-600">12%</div>
              <div className="text-sm text-muted-foreground">Level 1</div>
            </div>
            <div>
              <div className="text-2xl font-bold text-green-600">5%</div>
              <div className="text-sm text-muted-foreground">Level 2</div>
            </div>
            <div>
              <div className="text-2xl font-bold text-purple-600">3%</div>
              <div className="text-sm text-muted-foreground">Level 3</div>
            </div>
          </div>
          <p className="text-sm text-muted-foreground mt-4 text-center">
            All affiliate commissions are paid in USDT cryptocurrency for security and compliance
          </p>
        </CardContent>
      </Card>

      {/* Proceed Button */}
      <div className="flex justify-center">
        <Button 
          onClick={handleProceed}
          disabled={!selectedMethod}
          size="lg"
          className="px-8"
        >
          Proceed with {selectedMethod === 'crypto' ? 'Cryptocurrency' : 'Bank Transfer'} Payment
        </Button>
      </div>
    </div>
  );
};

export default PaymentMethodSelector;

import React, { useState, useEffect } from 'react';
import { useParams, useSearchParams } from 'react-router-dom';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { 
  Shield, 
  CheckCircle, 
  XCircle, 
  Search, 
  FileText, 
  Calendar,
  User,
  DollarSign,
  Award,
  AlertTriangle,
  RefreshCw,
  ExternalLink
} from 'lucide-react';
import ApiConfig from '@/config/api';

interface VerificationResult {
  success: boolean;
  verified: boolean;
  message?: string;
  certificate?: {
    certificate_number: string;
    holder_name: string;
    package_name: string;
    share_quantity: number;
    certificate_value: number;
    issue_date: string;
    legal_status: string;
    verification_status: string;
    issued_by: string;
    verification_timestamp: string;
  };
  verification_details?: {
    verification_method: string;
    verification_count: number;
    last_verified: string;
    certificate_age_days: number;
  };
}

const CertificateVerification: React.FC = () => {
  const { verificationCode } = useParams<{ verificationCode: string }>();
  const [searchParams] = useSearchParams();
  const [verificationInput, setVerificationInput] = useState('');
  const [verificationResult, setVerificationResult] = useState<VerificationResult | null>(null);
  const [loading, setLoading] = useState(false);
  const [autoVerified, setAutoVerified] = useState(false);

  useEffect(() => {
    // Auto-verify if verification code is in URL
    if (verificationCode && !autoVerified) {
      setVerificationInput(verificationCode);
      handleVerification(verificationCode);
      setAutoVerified(true);
    }
    
    // Check for other URL parameters
    const certNumber = searchParams.get('certificate_number');
    const hash = searchParams.get('hash');
    
    if ((certNumber || hash) && !autoVerified) {
      if (certNumber) {
        setVerificationInput(certNumber);
        handleVerification(certNumber, 'certificate_number');
      } else if (hash) {
        setVerificationInput(hash);
        handleVerification(hash, 'hash');
      }
      setAutoVerified(true);
    }
  }, [verificationCode, searchParams, autoVerified]);

  const handleVerification = async (input?: string, method?: string) => {
    const verifyValue = input || verificationInput;
    if (!verifyValue.trim()) return;

    setLoading(true);
    setVerificationResult(null);

    try {
      let url = `${ApiConfig.baseUrl}/certificates/verify.php?`;
      
      if (method === 'certificate_number') {
        url += `certificate_number=${encodeURIComponent(verifyValue)}`;
      } else if (method === 'hash') {
        url += `hash=${encodeURIComponent(verifyValue)}`;
      } else {
        // Default to verification code
        url += `code=${encodeURIComponent(verifyValue)}`;
      }

      const response = await fetch(url);
      const data = await response.json();
      
      setVerificationResult(data);
    } catch (error) {
      console.error('Verification error:', error);
      setVerificationResult({
        success: false,
        verified: false,
        message: 'Verification service temporarily unavailable'
      });
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    handleVerification();
  };

  const getStatusIcon = (verified: boolean, legalStatus?: string) => {
    if (!verified || legalStatus === 'invalidated') {
      return <XCircle className="w-8 h-8 text-red-500" />;
    } else if (legalStatus === 'converted_to_nft') {
      return <AlertTriangle className="w-8 h-8 text-yellow-500" />;
    } else {
      return <CheckCircle className="w-8 h-8 text-green-500" />;
    }
  };

  const getStatusMessage = (verified: boolean, legalStatus?: string) => {
    if (!verified) {
      return "Certificate is invalid or not found";
    } else if (legalStatus === 'invalidated') {
      return "Certificate has been invalidated";
    } else if (legalStatus === 'converted_to_nft') {
      return "Certificate has been converted to NFT";
    } else {
      return "Certificate is valid and authentic";
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-blue-50 to-purple-50 dark:from-gray-900 dark:to-gray-800 py-12">
      <div className="container mx-auto px-4 max-w-4xl">
        <div className="text-center mb-8">
          <div className="flex items-center justify-center mb-4">
            <Shield className="w-12 h-12 text-blue-600 mr-3" />
            <h1 className="text-4xl font-bold text-gray-900 dark:text-white">
              Certificate Verification
            </h1>
          </div>
          <p className="text-xl text-gray-600 dark:text-gray-300">
            Verify the authenticity of Aureus Alliance Holdings share certificates
          </p>
        </div>

        {/* Verification Form */}
        <Card className="mb-8">
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Search className="w-5 h-5" />
              Verify Certificate
            </CardTitle>
            <CardDescription>
              Enter a verification code, certificate number, or verification hash to check authenticity
            </CardDescription>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="verification-input">
                  Verification Code / Certificate Number / Hash
                </Label>
                <Input
                  id="verification-input"
                  value={verificationInput}
                  onChange={(e) => setVerificationInput(e.target.value)}
                  placeholder="e.g., AAH-2024-000001 or ABC123DEF456"
                  className="font-mono"
                />
              </div>
              <Button type="submit" disabled={loading || !verificationInput.trim()}>
                {loading ? (
                  <RefreshCw className="w-4 h-4 mr-2 animate-spin" />
                ) : (
                  <Search className="w-4 h-4 mr-2" />
                )}
                Verify Certificate
              </Button>
            </form>
          </CardContent>
        </Card>

        {/* Verification Result */}
        {verificationResult && (
          <Card className="mb-8">
            <CardHeader>
              <CardTitle className="flex items-center gap-3">
                {getStatusIcon(verificationResult.verified, verificationResult.certificate?.legal_status)}
                Verification Result
              </CardTitle>
            </CardHeader>
            <CardContent>
              {verificationResult.success ? (
                <div className="space-y-6">
                  <Alert className={verificationResult.verified ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50'}>
                    <AlertDescription className="text-lg font-medium">
                      {getStatusMessage(verificationResult.verified, verificationResult.certificate?.legal_status)}
                    </AlertDescription>
                  </Alert>

                  {verificationResult.certificate && (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                      <div className="space-y-4">
                        <h3 className="text-lg font-semibold flex items-center gap-2">
                          <FileText className="w-5 h-5" />
                          Certificate Details
                        </h3>
                        
                        <div className="space-y-3">
                          <div>
                            <Label className="text-sm font-medium text-muted-foreground">Certificate Number</Label>
                            <p className="font-mono text-lg">{verificationResult.certificate.certificate_number}</p>
                          </div>
                          
                          <div>
                            <Label className="text-sm font-medium text-muted-foreground">Certificate Holder</Label>
                            <p className="text-lg">{verificationResult.certificate.holder_name}</p>
                          </div>
                          
                          <div>
                            <Label className="text-sm font-medium text-muted-foreground">Package Type</Label>
                            <p className="text-lg">{verificationResult.certificate.package_name}</p>
                          </div>
                          
                          <div>
                            <Label className="text-sm font-medium text-muted-foreground">Share Quantity</Label>
                            <p className="text-lg">{verificationResult.certificate.share_quantity.toLocaleString()} shares</p>
                          </div>
                        </div>
                      </div>

                      <div className="space-y-4">
                        <h3 className="text-lg font-semibold flex items-center gap-2">
                          <Award className="w-5 h-5" />
                          Certificate Value & Status
                        </h3>
                        
                        <div className="space-y-3">
                          <div>
                            <Label className="text-sm font-medium text-muted-foreground">Certificate Value</Label>
                            <p className="text-2xl font-bold text-green-600">
                              ${verificationResult.certificate.certificate_value.toLocaleString()}
                            </p>
                          </div>
                          
                          <div>
                            <Label className="text-sm font-medium text-muted-foreground">Issue Date</Label>
                            <p className="text-lg">{new Date(verificationResult.certificate.issue_date).toLocaleDateString()}</p>
                          </div>
                          
                          <div>
                            <Label className="text-sm font-medium text-muted-foreground">Legal Status</Label>
                            <Badge 
                              variant={verificationResult.certificate.legal_status === 'valid' ? 'default' : 'secondary'}
                              className="text-sm"
                            >
                              {verificationResult.certificate.legal_status.toUpperCase()}
                            </Badge>
                          </div>
                          
                          <div>
                            <Label className="text-sm font-medium text-muted-foreground">Issued By</Label>
                            <p className="text-lg">{verificationResult.certificate.issued_by}</p>
                          </div>
                        </div>
                      </div>
                    </div>
                  )}

                  {verificationResult.verification_details && (
                    <div className="border-t pt-6">
                      <h3 className="text-lg font-semibold mb-4">Verification Details</h3>
                      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                          <Label className="text-muted-foreground">Verification Method</Label>
                          <p className="font-medium">{verificationResult.verification_details.verification_method}</p>
                        </div>
                        <div>
                          <Label className="text-muted-foreground">Times Verified</Label>
                          <p className="font-medium">{verificationResult.verification_details.verification_count}</p>
                        </div>
                        <div>
                          <Label className="text-muted-foreground">Certificate Age</Label>
                          <p className="font-medium">{verificationResult.verification_details.certificate_age_days} days</p>
                        </div>
                        <div>
                          <Label className="text-muted-foreground">Verified At</Label>
                          <p className="font-medium">{new Date(verificationResult.certificate.verification_timestamp).toLocaleString()}</p>
                        </div>
                      </div>
                    </div>
                  )}
                </div>
              ) : (
                <Alert className="border-red-200 bg-red-50">
                  <XCircle className="w-4 h-4" />
                  <AlertDescription>
                    {verificationResult.message || 'Certificate verification failed'}
                  </AlertDescription>
                </Alert>
              )}
            </CardContent>
          </Card>
        )}

        {/* Information Section */}
        <Card>
          <CardHeader>
            <CardTitle>About Certificate Verification</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div className="text-center">
                <Shield className="w-12 h-12 mx-auto mb-3 text-blue-600" />
                <h3 className="font-semibold mb-2">Secure Verification</h3>
                <p className="text-sm text-muted-foreground">
                  All certificates are cryptographically secured and verified against our blockchain records
                </p>
              </div>
              
              <div className="text-center">
                <FileText className="w-12 h-12 mx-auto mb-3 text-green-600" />
                <h3 className="font-semibold mb-2">Legal Validity</h3>
                <p className="text-sm text-muted-foreground">
                  Valid certificates represent legal ownership of shares in Aureus Alliance Holdings
                </p>
              </div>
              
              <div className="text-center">
                <ExternalLink className="w-12 h-12 mx-auto mb-3 text-purple-600" />
                <h3 className="font-semibold mb-2">NFT Conversion</h3>
                <p className="text-sm text-muted-foreground">
                  Certificates can be converted to NFTs, after which the original certificate becomes invalid
                </p>
              </div>
            </div>
            
            <div className="border-t pt-4 text-sm text-muted-foreground">
              <p><strong>Note:</strong> This verification system is provided for public transparency. 
              If you suspect fraudulent activity or have questions about a certificate, 
              please contact Aureus Alliance Holdings directly.</p>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
};

export default CertificateVerification;

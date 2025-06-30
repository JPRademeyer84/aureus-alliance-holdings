import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  FileText,
  Upload,
  Camera,
  CheckCircle,
  AlertTriangle as AlertCircle,
  ArrowLeft,
  Trash2,
  Eye,
  RefreshCw
} from '@/components/SafeIcons';
import { useSimpleTranslation as useTranslation, ST as T } from '@/components/SimpleTranslator';
import { useUser } from '@/contexts/UserContext';
import { useNavigate } from 'react-router-dom';
import { useToast } from '@/hooks/use-toast';
import FacialRecognition from '@/components/kyc/FacialRecognition';

interface KYCDocument {
  id: string;
  type: 'drivers_license' | 'national_id' | 'passport' | 'proof_of_address';
  filename: string;
  status: 'pending' | 'verified' | 'rejected';
  uploaded_at: string;
  file_path: string;
}

const KYCVerification: React.FC = () => {
  const { translate } = useTranslation();
  const { user, isLoading: userLoading } = useUser();
  const navigate = useNavigate();
  const { toast } = useToast();

  const [kycStatus, setKycStatus] = useState<'not_verified' | 'pending' | 'verified'>('not_verified');
  const [kycDocuments, setKycDocuments] = useState<KYCDocument[]>([]);
  const [facialVerificationStatus, setFacialVerificationStatus] = useState<'not_started' | 'pending' | 'verified' | 'failed'>('not_started');
  const [facialVerificationData, setFacialVerificationData] = useState<any>(null);
  const [adminApproved, setAdminApproved] = useState(false);
  const [canRestartVerification, setCanRestartVerification] = useState(false);
  const [showFacialRecognition, setShowFacialRecognition] = useState(false);
  const [isUploading, setIsUploading] = useState(false);
  const [uploadError, setUploadError] = useState<string | null>(null);
  const [isInitialized, setIsInitialized] = useState(false);

  // Load KYC data on component mount
  useEffect(() => {
    console.log('KYC useEffect triggered:', {
      userLoading,
      user: !!user,
      userId: user?.id,
      isInitialized
    });

    // Wait for user context to finish loading
    if (userLoading) {
      console.log('User still loading, waiting...');
      return;
    }

    if (!user) {
      // If no user is logged in after loading is complete, redirect to login
      console.log('No user found, redirecting to login');
      navigate('/login');
      return;
    }

    if (!isInitialized) {
      console.log('Initializing KYC data load...');
      setIsInitialized(true);
      loadKycData();
    }
  }, [user, userLoading, navigate, isInitialized]);

  const loadKycData = async () => {
    try {
      console.log('Loading KYC data...');
      const response = await fetch('http://localhost/Aureus%201%20-%20Complex/api/kyc/status.php', {
        credentials: 'include'
      });

      console.log('KYC API Response status:', response.status);

      if (response.ok) {
        const text = await response.text();
        console.log('Raw response:', text);

        try {
          const data = JSON.parse(text);
          console.log('KYC API Response data:', data);

          if (data.success) {
            console.log('Setting KYC data:', {
              kyc_status: data.kyc_status,
              documents: data.documents,
              facial_verification_status: data.facial_verification_status,
              facial_verification: data.facial_verification,
              admin_approved: data.admin_approved,
              can_restart_verification: data.can_restart_verification
            });

            setKycStatus(data.kyc_status || 'not_verified');
            setKycDocuments(data.documents || []);
            setFacialVerificationStatus(data.facial_verification_status || 'not_started');
            setFacialVerificationData(data.facial_verification);
            setAdminApproved(data.admin_approved || false);
            setCanRestartVerification(data.can_restart_verification || false);
          } else {
            console.error('KYC API returned error:', data.error);
          }
        } catch (parseError) {
          console.error('Failed to parse JSON response:', parseError);
          console.error('Raw response text:', text);
        }
      } else {
        console.error('KYC API request failed:', response.status, response.statusText);
        const errorText = await response.text();
        console.error('Error response:', errorText);
      }
    } catch (error) {
      console.error('Error loading KYC data:', error);
    }
  };

  const handleKycUpload = async (file: File, documentType: string) => {
    console.log('handleKycUpload called:', { fileName: file.name, documentType });

    if (!file) return;

    setIsUploading(true);
    setUploadError(null);

    const formData = new FormData();
    formData.append('document', file);
    formData.append('type', documentType);

    try {
      console.log('Sending upload request...');
      const response = await fetch('http://localhost/aureus-angel-alliance/api/kyc/upload.php', {
        method: 'POST',
        credentials: 'include', // Use session-based auth instead of token
        body: formData
      });

      const result = await response.json();
      console.log('Upload response:', result);

      if (result.success) {
        console.log('Upload successful, reloading KYC data...');
        // Reload KYC data to show the new document
        await loadKycData();
        toast({
          title: "Success",
          description: "Document uploaded successfully",
        });
      } else {
        console.log('Upload failed:', result);
        setUploadError(result.error || result.message || 'Upload failed');
        toast({
          title: "Upload Error",
          description: result.error || result.message || 'Upload failed',
          variant: "destructive"
        });
      }
    } catch (error) {
      console.error('Upload error:', error);
      setUploadError('Upload failed. Please try again.');
      toast({
        title: "Upload Error",
        description: 'Upload failed. Please try again.',
        variant: "destructive"
      });
    } finally {
      setIsUploading(false);
    }
  };

  const handleDeleteDocument = async (documentId: string) => {
    if (!confirm('Are you sure you want to delete this document?')) return;

    try {
      const response = await fetch(`http://localhost/aureus-angel-alliance/api/kyc/delete.php?id=${documentId}`, {
        method: 'DELETE',
        credentials: 'include'
      });

      const result = await response.json();
      if (result.success) {
        await loadKycData();
        toast({
          title: "Success",
          description: "Document deleted successfully",
        });
      } else {
        toast({
          title: "Error",
          description: result.error || "Failed to delete document",
          variant: "destructive"
        });
      }
    } catch (error) {
      console.error('Delete error:', error);
      toast({
        title: "Error",
        description: "Failed to delete document",
        variant: "destructive"
      });
    }
  };

  const getDocumentTypeLabel = (type: string) => {
    switch (type) {
      case 'drivers_license': return "Driver's License";
      case 'national_id': return 'National ID';
      case 'passport': return 'Passport';
      case 'proof_of_address': return 'Proof of Address';
      default: return type;
    }
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'verified': return 'bg-green-500/20 text-green-400';
      case 'pending': return 'bg-yellow-500/20 text-yellow-400';
      case 'rejected': return 'bg-red-500/20 text-red-400';
      default: return 'bg-gray-500/20 text-gray-400';
    }
  };

  // Handle restarting facial verification
  const handleRestartFacialVerification = async () => {
    if (!confirm('Are you sure you want to restart facial verification? This will allow you to take a new photo.')) {
      return;
    }

    try {
      const response = await fetch('http://localhost/aureus-angel-alliance/api/kyc/restart-facial-verification.php', {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
        }
      });

      const data = await response.json();

      if (data.success) {
        toast({
          title: "Success",
          description: "Facial verification reset successfully. You can now start a new verification.",
        });
        // Reload KYC data to reflect the reset status
        await loadKycData();
      } else {
        toast({
          title: "Error",
          description: data.error || "Failed to restart verification",
          variant: "destructive"
        });
      }
    } catch (error) {
      console.error('Error restarting facial verification:', error);
      toast({
        title: "Error",
        description: "Failed to restart verification",
        variant: "destructive"
      });
    }
  };

  // Handle facial verification completion
  const handleFacialVerificationComplete = async (result: {
    success: boolean;
    confidence: number;
    livenessScore: number;
    capturedImage: string;
  }) => {
    console.log('Facial verification completed:', result);

    try {
      // Send the facial verification result to the backend
      const response = await fetch('http://localhost/aureus-angel-alliance/api/kyc/facial-verification.php', {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          success: result.success,
          confidence: result.confidence,
          livenessScore: result.livenessScore,
          capturedImage: result.capturedImage
        })
      });

      const data = await response.json();

      if (data.success) {
        // Update status based on API response, not just the result
        const newStatus = data.verification_status || (result.success ? 'verified' : 'failed');
        setFacialVerificationStatus(newStatus);

        console.log('API Response:', data);
        console.log('Setting facial verification status to:', newStatus);

        toast({
          title: result.success ? "Verification Successful" : "Verification Failed",
          description: result.success ?
            "Your facial verification was successful!" :
            "Facial verification failed. Please try again.",
          variant: result.success ? "default" : "destructive"
        });

        // Reload KYC data to get updated status
        await loadKycData();
      } else {
        setFacialVerificationStatus('failed');
        toast({
          title: "Error",
          description: data.error || "Failed to save verification result",
          variant: "destructive"
        });
      }
    } catch (error) {
      console.error('Error saving facial verification:', error);
      setFacialVerificationStatus('failed');
      toast({
        title: "Error",
        description: "Failed to save verification result",
        variant: "destructive"
      });
    } finally {
      // Always close the modal
      setShowFacialRecognition(false);
    }
  };

  // Show loading if user is not yet loaded or still initializing
  if (userLoading || !isInitialized) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-gray-900 via-blue-900 to-purple-900 p-4 flex items-center justify-center">
        <div className="text-white text-center">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-white mx-auto mb-4"></div>
          <p>Loading...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-gray-900 via-blue-900 to-purple-900 p-4">
      <div className="max-w-4xl mx-auto space-y-6">
        {/* Header */}
        <div className="flex items-center gap-4 mb-6">
          <Button
            variant="ghost"
            onClick={() => navigate('/dashboard')}
            className="text-white hover:bg-white/10"
          >
            <ArrowLeft className="h-4 w-4 mr-2" />
            <T k="back_to_dashboard" fallback="Back to Dashboard" />
          </Button>
          <div>
            <h1 className="text-2xl font-bold text-white">
              <T k="kyc_verification" fallback="KYC Verification" />
            </h1>
            <p className="text-gray-400">
              <T k="verify_identity_secure_account" fallback="Verify your identity to secure your account" />
            </p>
          </div>
        </div>

        {/* Status Overview */}
        <Card className="bg-gray-800 border-gray-700">
          <CardHeader>
            <CardTitle className="text-white flex items-center gap-2">
              <FileText className="h-5 w-5 text-gold" />
              <T k="verification_status" fallback="Verification Status" />
              <Badge className={`ml-2 ${getStatusColor(kycStatus)}`}>
                {kycStatus === 'verified' ? translate('verified', 'Verified') :
                 kycStatus === 'pending' ? translate('pending', 'Pending') : 
                 translate('not_verified', 'Not Verified')}
              </Badge>
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div className="text-center">
                <div className={`w-12 h-12 rounded-full mx-auto mb-2 flex items-center justify-center ${
                  kycDocuments.length > 0 ? 'bg-green-500/20' : 'bg-gray-500/20'
                }`}>
                  <FileText className={`h-6 w-6 ${
                    kycDocuments.length > 0 ? 'text-green-400' : 'text-gray-400'
                  }`} />
                </div>
                <p className="text-white font-medium">Document Upload</p>
                <p className="text-gray-400 text-sm">
                  {kycDocuments.length > 0 ? 'Complete' : 'Required'}
                </p>
              </div>
              
              <div className="text-center">
                <div className={`w-12 h-12 rounded-full mx-auto mb-2 flex items-center justify-center ${
                  facialVerificationStatus === 'verified' ? 'bg-green-500/20' : 'bg-gray-500/20'
                }`}>
                  <Camera className={`h-6 w-6 ${
                    facialVerificationStatus === 'verified' ? 'text-green-400' : 'text-gray-400'
                  }`} />
                </div>
                <p className="text-white font-medium">Facial Verification</p>
                <p className="text-gray-400 text-sm">
                  {facialVerificationStatus === 'verified' ? 'Complete' : 'Pending'}
                </p>
              </div>
              
              <div className="text-center">
                <div className={`w-12 h-12 rounded-full mx-auto mb-2 flex items-center justify-center ${
                  kycStatus === 'verified' ? 'bg-green-500/20' : 'bg-gray-500/20'
                }`}>
                  <CheckCircle className={`h-6 w-6 ${
                    kycStatus === 'verified' ? 'text-green-400' : 'text-gray-400'
                  }`} />
                </div>
                <p className="text-white font-medium">Admin Review</p>
                <p className="text-gray-400 text-sm">
                  {kycStatus === 'verified' ? 'Approved' : 'Pending'}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Document Upload */}
        <Card className="bg-gray-800 border-gray-700">
          <CardHeader>
            <CardTitle className="text-white">
              <T k="upload_identity_document" fallback="Upload Identity Document" />
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <p className="text-gray-400 text-sm">
              <T k="kyc_upload_description" fallback="Upload ONE of the following identity documents. The document must be clear and all information must be visible." />
            </p>

            {uploadError && (
              <div className="bg-red-500/10 border border-red-500/30 rounded-lg p-3">
                <div className="flex items-center gap-2">
                  <AlertCircle className="h-4 w-4 text-red-400" />
                  <p className="text-red-400 text-sm">{uploadError}</p>
                </div>
              </div>
            )}

            {/* DEBUG TEST BUTTON */}
            <div className="bg-red-500/20 border border-red-500/30 rounded-lg p-4 mb-4">
              <h4 className="text-red-400 font-medium mb-2">🔍 DEBUG TEST</h4>
              <Button
                onClick={() => {
                  console.log('DEBUG: Test button clicked - no file upload');
                  alert('Test button clicked - if this redirects, the issue is not with file upload');
                }}
                className="bg-red-600 hover:bg-red-700 text-white"
              >
                Test Button (No File Upload)
              </Button>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              {/* Driver's License */}
              <div className="bg-gray-700/50 rounded-lg p-4">
                <div className="flex items-center gap-2 mb-3">
                  <FileText className="h-4 w-4 text-gold" />
                  <span className="text-white font-medium">Driver's License</span>
                </div>
                <p className="text-gray-400 text-xs mb-3">
                  Valid government-issued driver's license
                </p>
                <input
                  type="file"
                  accept="image/*,.pdf"
                  onChange={(e) => {
                    console.log('File input onChange triggered for drivers_license');
                    e.preventDefault();
                    e.stopPropagation();
                    const file = e.target.files?.[0];
                    if (file) {
                      console.log('File selected:', file.name);
                      handleKycUpload(file, 'drivers_license');
                    }
                  }}
                  className="hidden"
                  id="drivers-license-upload"
                  disabled={isUploading}
                />
                <label
                  htmlFor="drivers-license-upload"
                  className={`cursor-pointer inline-flex items-center gap-2 px-3 py-2 bg-gold/20 text-gold rounded text-sm hover:bg-gold/30 transition-colors w-full justify-center ${
                    isUploading ? 'opacity-50 cursor-not-allowed' : ''
                  }`}
                  onClick={(e) => {
                    console.log('Upload label clicked for drivers_license');
                    // Don't prevent default here - we want the file input to open
                  }}
                >
                  <Upload className="h-3 w-3" />
                  {isUploading ? 'Uploading...' : 'Upload'}
                </label>
              </div>

              {/* National ID */}
              <div className="bg-gray-700/50 rounded-lg p-4">
                <div className="flex items-center gap-2 mb-3">
                  <FileText className="h-4 w-4 text-gold" />
                  <span className="text-white font-medium">National ID</span>
                </div>
                <p className="text-gray-400 text-xs mb-3">
                  Government-issued national identity card
                </p>
                <input
                  type="file"
                  accept="image/*,.pdf"
                  onChange={(e) => {
                    const file = e.target.files?.[0];
                    if (file) handleKycUpload(file, 'national_id');
                  }}
                  className="hidden"
                  id="national-id-upload"
                  disabled={isUploading}
                />
                <label
                  htmlFor="national-id-upload"
                  className={`cursor-pointer inline-flex items-center gap-2 px-3 py-2 bg-gold/20 text-gold rounded text-sm hover:bg-gold/30 transition-colors w-full justify-center ${
                    isUploading ? 'opacity-50 cursor-not-allowed' : ''
                  }`}
                >
                  <Upload className="h-3 w-3" />
                  {isUploading ? 'Uploading...' : 'Upload'}
                </label>
              </div>

              {/* Passport */}
              <div className="bg-gray-700/50 rounded-lg p-4">
                <div className="flex items-center gap-2 mb-3">
                  <FileText className="h-4 w-4 text-gold" />
                  <span className="text-white font-medium">Passport</span>
                </div>
                <p className="text-gray-400 text-xs mb-3">
                  Valid passport with photo page visible
                </p>
                <input
                  type="file"
                  accept="image/*,.pdf"
                  onChange={(e) => {
                    const file = e.target.files?.[0];
                    if (file) handleKycUpload(file, 'passport');
                  }}
                  className="hidden"
                  id="passport-upload"
                  disabled={isUploading}
                />
                <label
                  htmlFor="passport-upload"
                  className={`cursor-pointer inline-flex items-center gap-2 px-3 py-2 bg-gold/20 text-gold rounded text-sm hover:bg-gold/30 transition-colors w-full justify-center ${
                    isUploading ? 'opacity-50 cursor-not-allowed' : ''
                  }`}
                >
                  <Upload className="h-3 w-3" />
                  {isUploading ? 'Uploading...' : 'Upload'}
                </label>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Proof of Address Upload */}
        <Card className="bg-gray-800 border-gray-700">
          <CardHeader>
            <CardTitle className="text-white">
              <T k="upload_proof_of_address" fallback="Upload Proof of Address" />
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <p className="text-gray-400 text-sm">
              <T k="proof_of_address_description" fallback="Upload a document that shows your current residential address. This is required for Level 2 verification." />
            </p>

            <div className="bg-gray-700/50 rounded-lg p-4">
              <div className="flex items-center gap-2 mb-3">
                <FileText className="h-4 w-4 text-blue-400" />
                <span className="text-white font-medium">Proof of Address Document</span>
              </div>
              <p className="text-gray-400 text-xs mb-3">
                Utility bill, bank statement, or official document showing your current address (not older than 3 months)
              </p>
              <input
                type="file"
                accept="image/*,.pdf"
                onChange={(e) => {
                  const file = e.target.files?.[0];
                  if (file) handleKycUpload(file, 'proof_of_address');
                }}
                className="hidden"
                id="proof-of-address-upload"
                disabled={isUploading}
              />
              <label
                htmlFor="proof-of-address-upload"
                className={`cursor-pointer inline-flex items-center gap-2 px-4 py-2 bg-blue-600/20 text-blue-400 rounded text-sm hover:bg-blue-600/30 transition-colors w-full justify-center ${
                  isUploading ? 'opacity-50 cursor-not-allowed' : ''
                }`}
              >
                <Upload className="h-4 w-4" />
                {isUploading ? 'Uploading...' : 'Upload Proof of Address'}
              </label>
            </div>

            <div className="bg-blue-500/10 border border-blue-500/30 rounded-lg p-3">
              <h4 className="text-blue-400 font-medium mb-2">Acceptable Documents:</h4>
              <ul className="text-gray-400 text-sm space-y-1">
                <li>• Utility bill (electricity, water, gas, internet)</li>
                <li>• Bank statement or credit card statement</li>
                <li>• Government correspondence (tax notice, voter registration)</li>
                <li>• Rental agreement or mortgage statement</li>
                <li>• Insurance statement</li>
              </ul>
              <p className="text-yellow-400 text-xs mt-2">
                ⚠️ Document must be dated within the last 3 months and clearly show your name and address.
              </p>
            </div>
          </CardContent>
        </Card>

        {/* Uploaded Documents */}
        {kycDocuments.length > 0 && (
          <Card className="bg-gray-800 border-gray-700">
            <CardHeader>
              <CardTitle className="text-white">
                <T k="uploaded_documents" fallback="Uploaded Documents" />
              </CardTitle>
            </CardHeader>
            <CardContent>
              {/* Identity Documents */}
              {kycDocuments.filter(doc => ['drivers_license', 'national_id', 'passport'].includes(doc.type)).length > 0 && (
                <div className="mb-6">
                  <h4 className="text-white font-medium mb-3 flex items-center gap-2">
                    <FileText className="h-4 w-4 text-gold" />
                    Identity Documents
                  </h4>
                  <div className="space-y-3">
                    {kycDocuments.filter(doc => ['drivers_license', 'national_id', 'passport'].includes(doc.type)).map((doc) => (
                      <div key={doc.id} className="flex items-center justify-between bg-gray-700/50 rounded-lg p-4">
                        <div className="flex items-center gap-3">
                          <FileText className="h-5 w-5 text-gold" />
                          <div>
                            <p className="text-white font-medium">{getDocumentTypeLabel(doc.type)}</p>
                            <p className="text-gray-400 text-sm">{doc.filename}</p>
                            <p className="text-gray-500 text-xs">
                              Uploaded: {new Date(doc.uploaded_at).toLocaleDateString()}
                            </p>
                          </div>
                        </div>
                        <div className="flex items-center gap-2">
                          <Badge className={getStatusColor(doc.status)}>
                            {doc.status}
                          </Badge>
                          <Button
                            size="sm"
                            variant="ghost"
                            onClick={() => window.open(`http://localhost/aureus-angel-alliance/assets/kyc/${doc.file_path}`, '_blank')}
                            className="text-gray-400 hover:text-white"
                          >
                            <Eye className="h-4 w-4" />
                          </Button>
                          <Button
                            size="sm"
                            variant="ghost"
                            onClick={() => handleDeleteDocument(doc.id)}
                            disabled={doc.status === 'approved'}
                            className={doc.status === 'approved' ?
                              "text-gray-500 cursor-not-allowed" :
                              "text-red-400 hover:text-red-300"
                            }
                            title={doc.status === 'approved' ?
                              "Cannot delete approved documents" :
                              "Delete document"
                            }
                          >
                            <Trash2 className="h-4 w-4" />
                          </Button>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* Proof of Address Documents */}
              {kycDocuments.filter(doc => doc.type === 'proof_of_address').length > 0 && (
                <div>
                  <h4 className="text-white font-medium mb-3 flex items-center gap-2">
                    <FileText className="h-4 w-4 text-blue-400" />
                    Proof of Address Documents
                  </h4>
                  <div className="space-y-3">
                    {kycDocuments.filter(doc => doc.type === 'proof_of_address').map((doc) => (
                      <div key={doc.id} className="flex items-center justify-between bg-gray-700/50 rounded-lg p-4">
                        <div className="flex items-center gap-3">
                          <FileText className="h-5 w-5 text-blue-400" />
                          <div>
                            <p className="text-white font-medium">{getDocumentTypeLabel(doc.type)}</p>
                            <p className="text-gray-400 text-sm">{doc.filename}</p>
                            <p className="text-gray-500 text-xs">
                              Uploaded: {new Date(doc.uploaded_at).toLocaleDateString()}
                            </p>
                          </div>
                        </div>
                        <div className="flex items-center gap-2">
                          <Badge className={getStatusColor(doc.status)}>
                            {doc.status}
                          </Badge>
                          <Button
                            size="sm"
                            variant="ghost"
                            onClick={() => window.open(`http://localhost/aureus-angel-alliance/assets/kyc/${doc.file_path}`, '_blank')}
                            className="text-gray-400 hover:text-white"
                          >
                            <Eye className="h-4 w-4" />
                          </Button>
                          <Button
                            size="sm"
                            variant="ghost"
                            onClick={() => handleDeleteDocument(doc.id)}
                            disabled={doc.status === 'approved'}
                            className={doc.status === 'approved' ?
                              "text-gray-500 cursor-not-allowed" :
                              "text-red-400 hover:text-red-300"
                            }
                            title={doc.status === 'approved' ?
                              "Cannot delete approved documents" :
                              "Delete document"
                            }
                          >
                            <Trash2 className="h-4 w-4" />
                          </Button>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}
            </CardContent>
          </Card>
        )}

        {/* Facial Recognition - Only show if not verified or if verified but not admin approved */}
        {(facialVerificationStatus !== 'verified' || (facialVerificationStatus === 'verified' && !adminApproved)) && (
          <Card className="bg-gray-800 border-gray-700">
            <CardHeader>
            <CardTitle className="text-white flex items-center gap-2">
              <Camera className="h-5 w-5 text-blue-400" />
              <T k="facial_verification" fallback="Facial Verification" />
              <Badge className={`ml-2 ${getStatusColor(facialVerificationStatus)}`}>
                {facialVerificationStatus === 'verified' ? 'Verified' :
                 facialVerificationStatus === 'failed' ? 'Failed' :
                 facialVerificationStatus === 'pending' ? 'Pending' : 'Not Started'}
              </Badge>
            </CardTitle>
          </CardHeader>
            <CardContent className="space-y-4">
            <p className="text-gray-400 text-sm">
              <T k="facial_verification_description" fallback="Take a selfie to verify your identity matches your uploaded document. This step is required after uploading your identity document." />
            </p>

            {/* Show captured image if verification is complete */}
            {facialVerificationData && facialVerificationData.captured_image_path && (
              <div className="bg-gray-700/50 rounded-lg p-4">
                <div className="flex items-center gap-2 mb-3">
                  <Camera className="h-5 w-5 text-green-400" />
                  <span className="text-green-400 font-medium">Captured Verification Photo</span>
                </div>
                <div className="flex flex-col md:flex-row gap-4">
                  <div className="flex-shrink-0">
                    <img
                      src={`http://localhost/aureus-angel-alliance/${facialVerificationData.captured_image_path}`}
                      alt="Facial verification photo"
                      className="w-32 h-32 object-cover rounded-lg border border-gray-600"
                      onError={(e) => {
                        e.currentTarget.src = '/placeholder-avatar.png';
                      }}
                    />
                  </div>
                  <div className="flex-1 space-y-2">
                    <div className="grid grid-cols-2 gap-4 text-sm">
                      <div>
                        <span className="text-gray-400">Confidence Score:</span>
                        <span className="text-white ml-2">
                          {(facialVerificationData.confidence_score * 100).toFixed(1)}%
                        </span>
                      </div>
                      <div>
                        <span className="text-gray-400">Liveness Score:</span>
                        <span className="text-white ml-2">
                          {(facialVerificationData.liveness_score * 100).toFixed(1)}%
                        </span>
                      </div>
                      <div>
                        <span className="text-gray-400">Status:</span>
                        <Badge className={`ml-2 ${getStatusColor(facialVerificationData.verification_status)}`}>
                          {facialVerificationData.verification_status}
                        </Badge>
                      </div>
                      <div>
                        <span className="text-gray-400">Date:</span>
                        <span className="text-white ml-2">
                          {new Date(facialVerificationData.created_at).toLocaleDateString()}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            )}

            {/* Only show Live Selfie Verification section if facial verification is not yet verified */}
            {facialVerificationStatus !== 'verified' && (
              <div className="bg-blue-500/10 border border-blue-500/30 rounded-lg p-4">
                <div className="flex items-center gap-2 mb-3">
                  <Camera className="h-5 w-5 text-blue-400" />
                  <span className="text-blue-400 font-medium">
                    <T k="live_selfie_verification" fallback="Live Selfie Verification" />
                  </span>
                </div>
                <p className="text-gray-400 text-sm mb-4">
                  <T k="selfie_instructions" fallback="Position your face in the center of the camera and follow the on-screen instructions. Make sure you're in a well-lit area." />
                </p>

                <div className="flex gap-2">
                  <Button
                    onClick={() => setShowFacialRecognition(true)}
                    disabled={kycDocuments.length === 0}
                    className="bg-blue-600 hover:bg-blue-700 text-white"
                  >
                    <Camera className="h-4 w-4 mr-2" />
                    <T k="start_facial_verification" fallback="Start Facial Verification" />
                  </Button>
                </div>

                {kycDocuments.length === 0 && (
                  <p className="text-yellow-400 text-sm mt-2">
                    <T k="upload_document_first" fallback="Please upload an identity document first" />
                  </p>
                )}
              </div>
            )}

            {/* Show restart verification section only if facial verification is verified but not admin approved */}
            {facialVerificationStatus === 'verified' && !adminApproved && (
              <div className="bg-yellow-500/10 border border-yellow-500/30 rounded-lg p-4">
                <div className="flex items-center gap-2 mb-3">
                  <CheckCircle className="h-5 w-5 text-yellow-400" />
                  <span className="text-yellow-400 font-medium">
                    <T k="facial_verification_complete" fallback="Facial Verification Complete" />
                  </span>
                </div>
                <p className="text-gray-400 text-sm mb-4">
                  <T k="verification_complete_message" fallback="Your facial verification has been completed successfully. If you need to retake your photo, you can restart the verification process." />
                </p>

                <Button
                  onClick={handleRestartFacialVerification}
                  variant="outline"
                  className="border-yellow-500 text-yellow-400 hover:bg-yellow-500/10"
                >
                  <RefreshCw className="h-4 w-4 mr-2" />
                  <T k="restart_verification" fallback="Restart Verification" />
                </Button>
              </div>
            )}

            {/* Show completion message if admin approved */}
            {adminApproved && (
              <div className="bg-green-500/10 border border-green-500/30 rounded-lg p-4">
                <div className="flex items-center gap-2 mb-2">
                  <CheckCircle className="h-5 w-5 text-green-400" />
                  <span className="text-green-400 font-medium">
                    <T k="kyc_approved" fallback="KYC Verification Approved" />
                  </span>
                </div>
                <p className="text-gray-400 text-sm">
                  <T k="kyc_approved_message" fallback="Your KYC verification has been approved by our admin team. No further verification is needed." />
                </p>
              </div>
            )}
            </CardContent>
          </Card>
        )}

        {/* Requirements & Guidelines */}
        <Card className="bg-gray-800 border-gray-700">
          <CardHeader>
            <CardTitle className="text-white">
              <T k="verification_requirements" fallback="Verification Requirements" />
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <h4 className="text-white font-medium mb-3">
                  <T k="document_requirements" fallback="Document Requirements" />
                </h4>
                <ul className="space-y-2 text-gray-400 text-sm">
                  <li className="flex items-start gap-2">
                    <CheckCircle className="h-4 w-4 text-green-400 mt-0.5 flex-shrink-0" />
                    <T k="req_clear_readable" fallback="Document must be clear and readable" />
                  </li>
                  <li className="flex items-start gap-2">
                    <CheckCircle className="h-4 w-4 text-green-400 mt-0.5 flex-shrink-0" />
                    <T k="req_all_corners_visible" fallback="All four corners must be visible" />
                  </li>
                  <li className="flex items-start gap-2">
                    <CheckCircle className="h-4 w-4 text-green-400 mt-0.5 flex-shrink-0" />
                    <T k="req_no_glare_shadows" fallback="No glare or shadows on the document" />
                  </li>
                  <li className="flex items-start gap-2">
                    <CheckCircle className="h-4 w-4 text-green-400 mt-0.5 flex-shrink-0" />
                    <T k="req_valid_not_expired" fallback="Document must be valid and not expired" />
                  </li>
                </ul>
              </div>

              <div>
                <h4 className="text-white font-medium mb-3">
                  <T k="photo_requirements" fallback="Photo Requirements" />
                </h4>
                <ul className="space-y-2 text-gray-400 text-sm">
                  <li className="flex items-start gap-2">
                    <CheckCircle className="h-4 w-4 text-green-400 mt-0.5 flex-shrink-0" />
                    <T k="req_face_clearly_visible" fallback="Face must be clearly visible" />
                  </li>
                  <li className="flex items-start gap-2">
                    <CheckCircle className="h-4 w-4 text-green-400 mt-0.5 flex-shrink-0" />
                    <T k="req_good_lighting" fallback="Good lighting conditions" />
                  </li>
                  <li className="flex items-start gap-2">
                    <CheckCircle className="h-4 w-4 text-green-400 mt-0.5 flex-shrink-0" />
                    <T k="req_no_sunglasses_hat" fallback="No sunglasses or hat covering face" />
                  </li>
                  <li className="flex items-start gap-2">
                    <CheckCircle className="h-4 w-4 text-green-400 mt-0.5 flex-shrink-0" />
                    <T k="req_look_directly_camera" fallback="Look directly at the camera" />
                  </li>
                </ul>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Facial Recognition Modal */}
        {showFacialRecognition && (
          <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <FacialRecognition
              onVerificationComplete={handleFacialVerificationComplete}
              onClose={() => setShowFacialRecognition(false)}
            />
          </div>
        )}
      </div>
    </div>
  );
};

export default KYCVerification;

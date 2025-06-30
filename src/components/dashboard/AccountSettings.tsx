import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useToast } from '@/hooks/use-toast';
import { useSimpleTranslation as useTranslation, ST as T } from '@/components/SimpleTranslator';
import {
  User,
  Mail,
  Shield,
  Wallet,
  CheckCircle,
  AlertCircle,
  ExternalLink,
  Copy,
  RefreshCw,
  Settings,
  Eye,
  EyeOff,
  Upload,
  FileText,
  Camera
} from 'lucide-react';
import { useUser } from '@/contexts/UserContext';
import { useWalletConnection } from '@/pages/investment/useWalletConnection';
import { getUSDTBalance, BalanceInfo } from '@/pages/investment/utils/web3Transaction';
import WalletSelector from '@/components/investment/WalletSelector';
import { WalletProviderName } from '@/pages/investment/useWalletConnection';
import ApiConfig from '@/config/api';
// import FacialRecognition from '@/components/kyc/FacialRecognition';

const AccountSettings: React.FC = () => {
  const { translate } = useTranslation();
  const { user, updateUser, refreshUser } = useUser();
  const { toast } = useToast();
  const { 
    walletAddress, 
    connectWallet, 
    disconnectWallet, 
    currentProvider, 
    chainId,
    isConnecting,
    connectionError 
  } = useWalletConnection();
  
  const [selectedWallet, setSelectedWallet] = useState<WalletProviderName>("safepal");
  const [usdtBalance, setUsdtBalance] = useState<BalanceInfo | null>(null);
  const [isLoadingBalance, setIsLoadingBalance] = useState(false);
  const [showFullAddress, setShowFullAddress] = useState(false);
  const [isEditingProfile, setIsEditingProfile] = useState(false);
  const [profileData, setProfileData] = useState({
    username: user?.username || '',
    email: user?.email || '',
    fullName: user?.full_name || '',
    whatsappNumber: user?.whatsapp_number || '',
    telegramUsername: user?.telegram_username || '',
    twitterHandle: user?.twitter_handle || '',
    instagramHandle: user?.instagram_handle || '',
    linkedinProfile: user?.linkedin_profile || ''
  });
  const [isSavingProfile, setIsSavingProfile] = useState(false);

  // KYC states
  const [kycDocuments, setKycDocuments] = useState<any[]>([]);
  const [isUploadingKyc, setIsUploadingKyc] = useState(false);
  const [kycStatus, setKycStatus] = useState('pending');
  const [showFacialRecognition, setShowFacialRecognition] = useState(false);
  const [facialVerificationStatus, setFacialVerificationStatus] = useState('not_started');

  // Load USDT balance when wallet is connected
  useEffect(() => {
    if (walletAddress && currentProvider) {
      loadUSDTBalance();
    } else {
      setUsdtBalance(null);
    }
  }, [walletAddress, currentProvider, chainId]);

  const loadUSDTBalance = async () => {
    if (!walletAddress || !currentProvider) return;
    
    setIsLoadingBalance(true);
    try {
      const balance = await getUSDTBalance(walletAddress, currentProvider, 'polygon');
      setUsdtBalance(balance);
    } catch (error) {
      console.error('Failed to load USDT balance:', error);
    } finally {
      setIsLoadingBalance(false);
    }
  };

  const handleWalletConnect = async () => {
    await connectWallet(selectedWallet);
  };

  const copyWalletAddress = () => {
    if (walletAddress) {
      navigator.clipboard.writeText(walletAddress);
      toast({
        title: "Copied!",
        description: "Wallet address copied to clipboard",
      });
    }
  };

  const handleProfileUpdate = async () => {
    if (!profileData.username.trim() || !profileData.email.trim()) {
      toast({
        title: "Validation Error",
        description: "Username and email are required",
        variant: "destructive"
      });
      return;
    }

    setIsSavingProfile(true);
    try {
      const apiUrl = ApiConfig.endpoints.users.updateProfile;
      console.log('API URL:', apiUrl);
      console.log('Profile data:', profileData);

      const response = await fetch(apiUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify(profileData)
      });

      console.log('Response status:', response.status);
      console.log('Response headers:', response.headers);

      const data = await response.json();
      console.log('Response data:', data);

      if (data.success) {
        toast({
          title: "Profile Updated",
          description: "Your profile has been updated successfully",
        });
        setIsEditingProfile(false);

        // Update user context with new data
        if (data.data && data.data.user) {
          updateUser(data.data.user);
        }

        // Refresh user data from server to ensure sync
        await refreshUser();
      } else {
        throw new Error(data.error || 'Failed to update profile');
      }
    } catch (error) {
      console.error('Profile update error:', error);
      toast({
        title: "Error",
        description: error instanceof Error ? error.message : "Failed to save profile",
        variant: "destructive"
      });
    } finally {
      setIsSavingProfile(false);
    }
  };

  const formatAddress = (address: string) => {
    if (showFullAddress) return address;
    return `${address.substring(0, 6)}...${address.substring(address.length - 4)}`;
  };

  const handleKycUpload = async (file: File, documentType: string) => {
    setIsUploadingKyc(true);
    try {
      const formData = new FormData();
      formData.append('document', file);
      formData.append('type', documentType);
      formData.append('action', 'upload_kyc');

      const response = await fetch('http://localhost/aureus-angel-alliance/api/users/kyc-upload.php', {
        method: 'POST',
        credentials: 'include',
        body: formData
      });

      const data = await response.json();
      if (data.success) {
        toast({
          title: "Success",
          description: "KYC document uploaded successfully",
        });
        // Refresh KYC documents
        fetchKycDocuments();
      } else {
        throw new Error(data.message || 'Upload failed');
      }
    } catch (error) {
      console.error('KYC upload failed:', error);
      toast({
        title: "Error",
        description: "Failed to upload document",
        variant: "destructive"
      });
    } finally {
      setIsUploadingKyc(false);
    }
  };

  const fetchKycDocuments = async () => {
    try {
      console.log('Fetching KYC documents...');
      // Temporarily disable API call to test rendering
      // const response = await fetch('http://localhost/aureus-angel-alliance/api/users/kyc-documents.php', {
      //   credentials: 'include'
      // });
      // const data = await response.json();
      // if (data.success) {
      //   setKycDocuments(data.data.documents || []);
      //   setKycStatus(data.data.status || 'pending');
      // }

      // Set dummy data for testing
      setKycDocuments([]);
      setKycStatus('pending');
      console.log('KYC documents set to dummy data');
    } catch (error) {
      console.error('Failed to fetch KYC documents:', error);
    }
  };

  const fetchFacialVerificationStatus = async () => {
    try {
      console.log('Fetching facial verification status...');
      // Temporarily disable API call to test rendering
      // const response = await fetch('http://localhost/aureus-angel-alliance/api/users/facial-verification-status.php', {
      //   credentials: 'include'
      // });
      // const data = await response.json();
      // if (data.success) {
      //   setFacialVerificationStatus(data.data.status || 'not_started');
      // }

      // Set dummy data for testing
      setFacialVerificationStatus('not_started');
      console.log('Facial verification status set to dummy data');
    } catch (error) {
      console.error('Failed to fetch facial verification status:', error);
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
    // Temporarily disable for testing
    setShowFacialRecognition(false);
    toast({
      title: "Test Mode",
      description: "Facial verification is in test mode",
    });
  };

  // Load KYC documents and facial verification status on component mount
  useEffect(() => {
    console.log('AccountSettings: Loading KYC data...');
    fetchKycDocuments();
    fetchFacialVerificationStatus();
  }, []);

  // Debug logging
  console.log('AccountSettings render - KYC Status:', kycStatus);
  console.log('AccountSettings render - KYC Documents:', kycDocuments);

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h2 className="text-2xl font-bold text-white">
          <T k="account_settings" fallback="Account Settings" />
        </h2>
        <p className="text-gray-400">
          <T k="manage_account_wallet" fallback="Manage your account and wallet connections" />
        </p>
      </div>

      {/* Profile Information */}
      <Card className="bg-gray-800 border-gray-700">
        <CardHeader>
          <CardTitle className="text-white flex items-center gap-2">
            <User className="h-5 w-5 text-gold" />
            <T k="profile_information" fallback="Profile Information" />
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          {!isEditingProfile ? (
            <>
              <div className="space-y-6">
                {/* Basic Information */}
                <div>
                  <h4 className="text-white font-medium mb-3">
                    <T k="basic_information" fallback="Basic Information" />
                  </h4>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <Label className="text-gray-400">
                        <T k="username" fallback="Username" />
                      </Label>
                      <p className="text-white font-medium">{user?.username}</p>
                    </div>
                    <div>
                      <Label className="text-gray-400">
                        <T k="email" fallback="Email" />
                      </Label>
                      <p className="text-white font-medium">{user?.email}</p>
                    </div>
                    <div>
                      <Label className="text-gray-400">
                        <T k="full_name" fallback="Full Name" />
                      </Label>
                      <p className="text-white font-medium">{user?.full_name || 'Not provided'}</p>
                    </div>
                    <div>
                      <Label className="text-gray-400">
                        <T k="member_since" fallback="Member Since" />
                      </Label>
                      <p className="text-white font-medium">
                        {user?.created_at ? new Date(user.created_at).toLocaleDateString() : 'N/A'}
                      </p>
                    </div>
                    <div>
                      <Label className="text-gray-400">
                        <T k="account_status" fallback="Account Status" />
                      </Label>
                      <Badge className="bg-green-500/20 text-green-400 border-green-500/30">
                        <T k="active_investor" fallback="Active Investor" />
                      </Badge>
                    </div>
                  </div>
                </div>

                {/* Contact & Social Media */}
                {(user?.whatsapp_number || user?.telegram_username || user?.twitter_handle || user?.instagram_handle || user?.linkedin_profile) && (
                  <div>
                    <h4 className="text-white font-medium mb-3">
                      <T k="contact_social_media" fallback="Contact & Social Media" />
                    </h4>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      {user?.whatsapp_number && (
                        <div>
                          <Label className="text-gray-400">
                            <T k="whatsapp_number" fallback="WhatsApp Number" />
                          </Label>
                          <p className="text-white font-medium">{user.whatsapp_number}</p>
                        </div>
                      )}
                      {user?.telegram_username && (
                        <div>
                          <Label className="text-gray-400">
                            <T k="telegram_username" fallback="Telegram Username" />
                          </Label>
                          <p className="text-white font-medium">{user.telegram_username}</p>
                        </div>
                      )}
                      {user?.twitter_handle && (
                        <div>
                          <Label className="text-gray-400">
                            <T k="twitter_handle" fallback="Twitter Handle" />
                          </Label>
                          <p className="text-white font-medium">{user.twitter_handle}</p>
                        </div>
                      )}
                      {user?.instagram_handle && (
                        <div>
                          <Label className="text-gray-400">
                            <T k="instagram_handle" fallback="Instagram Handle" />
                          </Label>
                          <p className="text-white font-medium">{user.instagram_handle}</p>
                        </div>
                      )}
                      {user?.linkedin_profile && (
                        <div className="md:col-span-2">
                          <Label className="text-gray-400">
                            <T k="linkedin_profile" fallback="LinkedIn Profile" />
                          </Label>
                          <p className="text-white font-medium">
                            <a
                              href={user.linkedin_profile}
                              target="_blank"
                              rel="noopener noreferrer"
                              className="text-blue-400 hover:text-blue-300 flex items-center gap-1"
                            >
                              {user.linkedin_profile}
                              <ExternalLink className="h-3 w-3" />
                            </a>
                          </p>
                        </div>
                      )}
                    </div>
                  </div>
                )}
              </div>

              <div className="flex gap-3">
                <Button
                  onClick={() => setIsEditingProfile(true)}
                  variant="outline"
                  className="border-gold/30 text-gold hover:bg-gold/10"
                >
                  <Settings className="h-4 w-4 mr-2" />
                  <T k="edit_profile" fallback="Edit Profile" />
                </Button>
                <Button
                  onClick={() => window.location.href = '/dashboard/kyc-profile'}
                  className="bg-blue-600 hover:bg-blue-700 text-white"
                >
                  <Shield className="h-4 w-4 mr-2" />
                  <T k="complete_kyc_profile" fallback="Complete KYC Profile" />
                </Button>
              </div>
            </>
          ) : (
            <>
              <div className="space-y-4">
                {/* Personal Information */}
                <div>
                  <h4 className="text-white font-medium mb-3">
                    <T k="personal_information" fallback="Personal Information" />
                  </h4>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <Label className="text-gray-400">
                        <T k="username" fallback="Username" /> *
                      </Label>
                      <Input
                        value={profileData.username}
                        onChange={(e) => setProfileData({...profileData, username: e.target.value})}
                        className="bg-gray-700 border-gray-600 text-white"
                        disabled={isSavingProfile}
                      />
                    </div>
                    <div>
                      <Label className="text-gray-400">
                        <T k="email" fallback="Email" /> *
                      </Label>
                      <Input
                        type="email"
                        value={profileData.email}
                        onChange={(e) => setProfileData({...profileData, email: e.target.value})}
                        className="bg-gray-700 border-gray-600 text-white"
                        disabled={isSavingProfile}
                      />
                    </div>
                    <div className="md:col-span-2">
                      <Label className="text-gray-400">
                        <T k="full_name" fallback="Full Name" />
                      </Label>
                      <Input
                        value={profileData.fullName}
                        onChange={(e) => setProfileData({...profileData, fullName: e.target.value})}
                        className="bg-gray-700 border-gray-600 text-white"
                        disabled={isSavingProfile}
                      />
                    </div>
                  </div>
                </div>

                {/* Contact & Social Media */}
                <div>
                  <h4 className="text-white font-medium mb-3">
                    <T k="contact_social_media" fallback="Contact & Social Media" />
                  </h4>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <Label className="text-gray-400">
                        <T k="whatsapp_number" fallback="WhatsApp Number" />
                      </Label>
                      <Input
                        value={profileData.whatsappNumber}
                        onChange={(e) => setProfileData({...profileData, whatsappNumber: e.target.value})}
                        className="bg-gray-700 border-gray-600 text-white"
                        placeholder="+1234567890"
                        disabled={isSavingProfile}
                      />
                    </div>
                    <div>
                      <Label className="text-gray-400">
                        <T k="telegram_username" fallback="Telegram Username" />
                      </Label>
                      <Input
                        value={profileData.telegramUsername}
                        onChange={(e) => setProfileData({...profileData, telegramUsername: e.target.value})}
                        className="bg-gray-700 border-gray-600 text-white"
                        placeholder="@username"
                        disabled={isSavingProfile}
                      />
                    </div>
                    <div>
                      <Label className="text-gray-400">
                        <T k="twitter_handle" fallback="Twitter Handle" />
                      </Label>
                      <Input
                        value={profileData.twitterHandle}
                        onChange={(e) => setProfileData({...profileData, twitterHandle: e.target.value})}
                        className="bg-gray-700 border-gray-600 text-white"
                        placeholder="@username"
                        disabled={isSavingProfile}
                      />
                    </div>
                    <div>
                      <Label className="text-gray-400">
                        <T k="instagram_handle" fallback="Instagram Handle" />
                      </Label>
                      <Input
                        value={profileData.instagramHandle}
                        onChange={(e) => setProfileData({...profileData, instagramHandle: e.target.value})}
                        className="bg-gray-700 border-gray-600 text-white"
                        placeholder="@username"
                        disabled={isSavingProfile}
                      />
                    </div>
                    <div className="md:col-span-2">
                      <Label className="text-gray-400">
                        <T k="linkedin_profile" fallback="LinkedIn Profile" />
                      </Label>
                      <Input
                        value={profileData.linkedinProfile}
                        onChange={(e) => setProfileData({...profileData, linkedinProfile: e.target.value})}
                        className="bg-gray-700 border-gray-600 text-white"
                        placeholder="https://linkedin.com/in/username"
                        disabled={isSavingProfile}
                      />
                    </div>
                  </div>
                </div>
              </div>

              <div className="flex gap-2">
                <Button
                  onClick={handleProfileUpdate}
                  className="bg-gold-gradient text-black"
                  disabled={isSavingProfile}
                >
                  {isSavingProfile ? (
                    <>
                      <RefreshCw className="h-4 w-4 mr-2 animate-spin" />
                      <T k="saving" fallback="Saving..." />
                    </>
                  ) : (
                    <T k="save_changes" fallback="Save Changes" />
                  )}
                </Button>
                <Button
                  onClick={() => setIsEditingProfile(false)}
                  variant="outline"
                  className="border-gray-600 text-gray-400"
                  disabled={isSavingProfile}
                >
                  <T k="cancel" fallback="Cancel" />
                </Button>
              </div>
            </>
          )}
        </CardContent>
      </Card>

      {/* KYC Verification Link */}
      <Card className="bg-gray-800 border-gray-700">
        <CardHeader>
          <CardTitle className="text-white flex items-center gap-2">
            <FileText className="h-5 w-5 text-gold" />
            <T k="kyc_verification" fallback="KYC Verification" />
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <p className="text-gray-400 text-sm">
            <T k="kyc_verification_description" fallback="Complete your identity verification to unlock all platform features and increase your security." />
          </p>
          <Button
            onClick={() => window.location.href = '/kyc'}
            className="bg-gold-gradient text-black hover:opacity-90"
          >
            <FileText className="h-4 w-4 mr-2" />
            <T k="start_kyc_verification" fallback="Start KYC Verification" />
          </Button>
        </CardContent>
      </Card>

      {/* Wallet Connection */}
      <Card className="bg-gray-800 border-gray-700">
        <CardHeader>
          <CardTitle className="text-white flex items-center gap-2">
            <Wallet className="h-5 w-5 text-gold" />
            <T k="wallet_connection" fallback="Wallet Connection" />
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          {!walletAddress ? (
            <>
              <p className="text-gray-400 text-sm">
                <T k="connect_wallet_start_investing" fallback="Connect your wallet to start investing and track your portfolio" />
              </p>
              <WalletSelector
                selected={selectedWallet}
                setSelected={setSelectedWallet}
                connecting={isConnecting}
                onConnect={handleWalletConnect}
              />
              {connectionError && (
                <div className="bg-red-500/10 border border-red-500/30 rounded-lg p-3">
                  <div className="flex items-center gap-2">
                    <AlertCircle className="h-4 w-4 text-red-400" />
                    <p className="text-red-400 text-sm">{connectionError}</p>
                  </div>
                </div>
              )}
            </>
          ) : (
            <div className="space-y-4">
              {/* Connected Wallet Info */}
              <div className="bg-green-500/10 border border-green-500/30 rounded-lg p-4">
                <div className="flex items-center gap-2 mb-3">
                  <CheckCircle className="h-5 w-5 text-green-400" />
                  <span className="text-green-400 font-semibold">
                    <T k="wallet_connected" fallback="Wallet Connected" />
                  </span>
                  <Badge className="bg-green-500/20 text-green-400 text-xs">
                    {currentProvider?.toUpperCase()}
                  </Badge>
                </div>
                
                <div className="space-y-2">
                  <div className="flex items-center justify-between">
                    <span className="text-gray-400 text-sm">
                      <T k="address" fallback="Address:" />
                    </span>
                    <div className="flex items-center gap-2">
                      <code className="text-white bg-black/30 px-2 py-1 rounded text-sm">
                        {formatAddress(walletAddress)}
                      </code>
                      <Button
                        size="sm"
                        variant="ghost"
                        onClick={() => setShowFullAddress(!showFullAddress)}
                        className="h-6 w-6 p-0 text-gray-400 hover:text-white"
                      >
                        {showFullAddress ? <EyeOff className="h-3 w-3" /> : <Eye className="h-3 w-3" />}
                      </Button>
                      <Button
                        size="sm"
                        variant="ghost"
                        onClick={copyWalletAddress}
                        className="h-6 w-6 p-0 text-gray-400 hover:text-white"
                      >
                        <Copy className="h-3 w-3" />
                      </Button>
                    </div>
                  </div>
                  
                  <div className="flex items-center justify-between">
                    <span className="text-gray-400 text-sm">
                      <T k="usdt_balance" fallback="USDT Balance:" />
                    </span>
                    <div className="flex items-center gap-2">
                      {isLoadingBalance ? (
                        <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-gold"></div>
                      ) : (
                        <span className="text-green-400 font-semibold">
                          {usdtBalance?.formatted || translate('zero_decimal', '0.00')} <T k="usdt" fallback="USDT" />
                        </span>
                      )}
                      <Button
                        size="sm"
                        variant="ghost"
                        onClick={loadUSDTBalance}
                        className="h-6 w-6 p-0 text-gray-400 hover:text-white"
                        disabled={isLoadingBalance}
                      >
                        <RefreshCw className={`h-3 w-3 ${isLoadingBalance ? 'animate-spin' : ''}`} />
                      </Button>
                    </div>
                  </div>
                  
                  {chainId && (
                    <div className="flex items-center justify-between">
                      <span className="text-gray-400 text-sm">
                        <T k="network" fallback="Network:" />
                      </span>
                      <Badge className="bg-blue-500/20 text-blue-400 text-xs">
                        {chainId === '0x89' ? translate('polygon', 'Polygon') :
                         chainId === '0x38' ? translate('bsc', 'BSC') :
                         chainId === '0x1' ? translate('ethereum', 'Ethereum') : translate('unknown', 'Unknown')}
                      </Badge>
                    </div>
                  )}
                </div>
                
                <div className="flex gap-2 mt-4">
                  <Button
                    size="sm"
                    variant="outline"
                    onClick={disconnectWallet}
                    className="border-red-500/30 text-red-400 hover:bg-red-500/20"
                  >
                    <T k="disconnect" fallback="Disconnect" />
                  </Button>
                  <Button
                    size="sm"
                    variant="outline"
                    onClick={handleWalletConnect}
                    className="border-gold/30 text-gold hover:bg-gold/10"
                  >
                    <T k="switch_wallet" fallback="Switch Wallet" />
                  </Button>
                </div>
              </div>
            </div>
          )}
        </CardContent>
      </Card>

      {/* Security Settings */}
      <Card className="bg-gray-800 border-gray-700">
        <CardHeader>
          <CardTitle className="text-white flex items-center gap-2">
            <Shield className="h-5 w-5 text-gold" />
            <T k="security_settings" fallback="Security Settings" />
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <p className="text-gray-400 text-sm">
            <T k="security_features_coming_soon" fallback="Security features coming soon..." />
          </p>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div className="bg-gray-700/50 rounded-lg p-4">
              <h4 className="text-white font-medium mb-2">
                <T k="two_factor_authentication" fallback="Two-Factor Authentication" />
              </h4>
              <p className="text-gray-400 text-sm mb-3">
                <T k="add_extra_security_layer" fallback="Add an extra layer of security to your account" />
              </p>
              <Button size="sm" variant="outline" disabled>
                <T k="enable_2fa_coming_soon" fallback="Enable 2FA (Coming Soon)" />
              </Button>
            </div>
            <div className="bg-gray-700/50 rounded-lg p-4">
              <h4 className="text-white font-medium mb-2">
                <T k="password_change" fallback="Password Change" />
              </h4>
              <p className="text-gray-400 text-sm mb-3">
                <T k="update_account_password" fallback="Update your account password" />
              </p>
              <Button size="sm" variant="outline" disabled>
                <T k="change_password_coming_soon" fallback="Change Password (Coming Soon)" />
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>



      {/* KYC Verification */}
      <Card className="bg-gray-800 border-gray-700">
        <CardHeader>
          <CardTitle className="text-white flex items-center gap-2">
            <FileText className="h-5 w-5 text-gold" />
            <T k="kyc_verification" fallback="KYC Verification" />
            <Badge className={`ml-2 ${
              kycStatus === 'verified' ? 'bg-green-500/20 text-green-400' :
              kycStatus === 'pending' ? 'bg-yellow-500/20 text-yellow-400' :
              'bg-red-500/20 text-red-400'
            }`}>
              {kycStatus === 'verified' ? 'Verified' :
               kycStatus === 'pending' ? 'Pending' : 'Not Verified'}
            </Badge>
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <p className="text-gray-400 text-sm">
            <T k="kyc_verification_description" fallback="Upload your identity documents for account verification. Only ONE document is required." />
          </p>

          {/* Document Upload */}
          <div className="space-y-4">
            <h4 className="text-white font-medium">
              <T k="upload_identity_document" fallback="Upload Identity Document" />
            </h4>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              {/* Driver's License */}
              <div className="bg-gray-700/50 rounded-lg p-4">
                <div className="flex items-center gap-2 mb-2">
                  <FileText className="h-4 w-4 text-gold" />
                  <span className="text-white font-medium">Driver's License</span>
                </div>
                <input
                  type="file"
                  accept="image/*,.pdf"
                  onChange={(e) => {
                    const file = e.target.files?.[0];
                    if (file) handleKycUpload(file, 'drivers_license');
                  }}
                  className="hidden"
                  id="drivers-license-upload"
                />
                <label
                  htmlFor="drivers-license-upload"
                  className="cursor-pointer inline-flex items-center gap-2 px-3 py-2 bg-gold/20 text-gold rounded text-sm hover:bg-gold/30 transition-colors"
                >
                  <Upload className="h-3 w-3" />
                  Upload
                </label>
              </div>

              {/* National ID */}
              <div className="bg-gray-700/50 rounded-lg p-4">
                <div className="flex items-center gap-2 mb-2">
                  <FileText className="h-4 w-4 text-gold" />
                  <span className="text-white font-medium">National ID</span>
                </div>
                <input
                  type="file"
                  accept="image/*,.pdf"
                  onChange={(e) => {
                    const file = e.target.files?.[0];
                    if (file) handleKycUpload(file, 'national_id');
                  }}
                  className="hidden"
                  id="national-id-upload"
                />
                <label
                  htmlFor="national-id-upload"
                  className="cursor-pointer inline-flex items-center gap-2 px-3 py-2 bg-gold/20 text-gold rounded text-sm hover:bg-gold/30 transition-colors"
                >
                  <Upload className="h-3 w-3" />
                  Upload
                </label>
              </div>

              {/* Passport */}
              <div className="bg-gray-700/50 rounded-lg p-4">
                <div className="flex items-center gap-2 mb-2">
                  <FileText className="h-4 w-4 text-gold" />
                  <span className="text-white font-medium">Passport</span>
                </div>
                <input
                  type="file"
                  accept="image/*,.pdf"
                  onChange={(e) => {
                    const file = e.target.files?.[0];
                    if (file) handleKycUpload(file, 'passport');
                  }}
                  className="hidden"
                  id="passport-upload"
                />
                <label
                  htmlFor="passport-upload"
                  className="cursor-pointer inline-flex items-center gap-2 px-3 py-2 bg-gold/20 text-gold rounded text-sm hover:bg-gold/30 transition-colors"
                >
                  <Upload className="h-3 w-3" />
                  Upload
                </label>
              </div>
            </div>

            {/* Facial Recognition */}
            <div className="bg-blue-500/10 border border-blue-500/30 rounded-lg p-4">
              <div className="flex items-center gap-2 mb-2">
                <Camera className="h-4 w-4 text-blue-400" />
                <span className="text-blue-400 font-medium">Facial Recognition Verification</span>
                <Badge className={`text-xs ${
                  facialVerificationStatus === 'verified' ? 'bg-green-500/20 text-green-400' :
                  facialVerificationStatus === 'failed' ? 'bg-red-500/20 text-red-400' :
                  facialVerificationStatus === 'pending' ? 'bg-yellow-500/20 text-yellow-400' :
                  'bg-blue-500/20 text-blue-400'
                }`}>
                  {facialVerificationStatus === 'verified' ? 'Verified' :
                   facialVerificationStatus === 'failed' ? 'Failed' :
                   facialVerificationStatus === 'pending' ? 'Pending' : 'Not Started'}
                </Badge>
              </div>
              <p className="text-gray-400 text-sm mb-3">
                Take a selfie to verify your identity matches your uploaded document
              </p>
              <Button
                size="sm"
                variant="outline"
                onClick={() => setShowFacialRecognition(true)}
                disabled={kycDocuments.length === 0 || facialVerificationStatus === 'verified'}
                className="border-blue-500/30 text-blue-400 hover:bg-blue-500/20"
              >
                <Camera className="h-3 w-3 mr-2" />
                {facialVerificationStatus === 'verified' ? 'Verification Complete' : 'Start Facial Verification'}
              </Button>
              {kycDocuments.length === 0 && (
                <p className="text-yellow-400 text-xs mt-2">
                  Please upload an identity document first
                </p>
              )}
            </div>

            {/* Uploaded Documents */}
            {kycDocuments.length > 0 && (
              <div>
                <h4 className="text-white font-medium mb-2">Uploaded Documents</h4>
                <div className="space-y-2">
                  {kycDocuments.map((doc, index) => (
                    <div key={index} className="flex items-center justify-between bg-gray-700/50 rounded-lg p-3">
                      <div className="flex items-center gap-2">
                        <FileText className="h-4 w-4 text-gold" />
                        <span className="text-white">{doc.type.replace('_', ' ').toUpperCase()}</span>
                        <Badge className={`text-xs ${
                          doc.status === 'verified' ? 'bg-green-500/20 text-green-400' :
                          doc.status === 'pending' ? 'bg-yellow-500/20 text-yellow-400' :
                          'bg-red-500/20 text-red-400'
                        }`}>
                          {doc.status}
                        </Badge>
                      </div>
                      <span className="text-gray-400 text-sm">
                        {new Date(doc.uploaded_at).toLocaleDateString()}
                      </span>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>
        </CardContent>
      </Card>

      {/* Facial Recognition Modal */}
      {showFacialRecognition && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
          <div className="bg-gray-800 p-6 rounded-lg">
            <h3 className="text-white text-lg mb-4">Facial Recognition</h3>
            <p className="text-gray-400 mb-4">Facial recognition component will be loaded here.</p>
            <Button onClick={() => setShowFacialRecognition(false)}>Close</Button>
          </div>
        </div>
      )}
    </div>
  );
};

export default AccountSettings;

import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useToast } from '@/hooks/use-toast';
import { 
  User, 
  Mail, 
  Phone, 
  MapPin, 
  Calendar, 
  MessageSquare, 
  Edit, 
  Save, 
  X,
  Shield,
  CheckCircle,
  AlertCircle,
  Clock,
  Upload,
  FileText,
  Camera,
  Globe,
  MessageCircle,
  Send,
  ExternalLink,
  Star,
  Award,
  TrendingUp,
  Users,
  DollarSign,
  Eye,
  Trash2,
  Share2
} from 'lucide-react';
import { useUser } from '@/contexts/UserContext';
import ApiConfig from '@/config/api';
// import SocialMediaSharing from './SocialMediaSharing';

interface UserProfileData {
  id: string;
  username: string;
  email: string;
  full_name: string;
  phone?: string;
  country?: string;
  city?: string;
  date_of_birth?: string;
  profile_image?: string;
  bio?: string;
  
  // Social Media Links
  telegram_username?: string;
  whatsapp_number?: string;
  twitter_handle?: string;
  instagram_handle?: string;
  linkedin_profile?: string;
  facebook_profile?: string;
  
  // KYC Information
  kyc_status: 'pending' | 'verified' | 'rejected';
  kyc_documents?: string[];
  kyc_verified_at?: string;
  kyc_rejected_reason?: string;
  
  // Profile Completion
  profile_completion: number;
  
  // Investment Stats
  total_invested?: number;
  total_commissions?: number;
  referral_count?: number;
  
  // Timestamps
  created_at: string;
  updated_at: string;
}

interface KYCDocument {
  id: string;
  type: 'passport' | 'drivers_license' | 'national_id' | 'proof_of_address';
  filename: string;
  upload_date: string;
  status: 'pending' | 'approved' | 'rejected';
}

const EnhancedUserProfile: React.FC = () => {
  const { user, isLoading: userLoading } = useUser();
  const { toast } = useToast();
  const [profile, setProfile] = useState<UserProfileData | null>(null);
  const [isEditing, setIsEditing] = useState(false);
  const [isLoading, setIsLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const [activeTab, setActiveTab] = useState('overview');
  const [kycDocuments, setKycDocuments] = useState<KYCDocument[]>([]);
  const [uploadingKyc, setUploadingKyc] = useState(false);

  useEffect(() => {
    // Silent profile loading
    if (user?.id) {
      fetchProfile();
      fetchKycDocuments();
    }
  }, [user?.id]);

  const fetchProfile = async () => {
    if (!user?.id) {
      console.log('No user ID available');
      return;
    }

    console.log('Fetching profile for user:', user.id);
    setIsLoading(true);
    try {
      const url = `${ApiConfig.endpoints.users.enhancedProfile}?action=get&user_id=${user.id}`;
      console.log('API URL:', url);

      const response = await fetch(url, {
        credentials: 'include'
      });

      console.log('Response status:', response.status);
      const data = await response.json();
      console.log('Response data:', data);

      if (data.success) {
        setProfile(data.data.profile);
        console.log('Profile set successfully:', data.data.profile);
      } else {
        console.log('API returned error, creating basic profile:', data.message);
        // Create basic profile if doesn't exist
        const basicProfile = {
          id: user.id.toString(),
          username: user.username,
          email: user.email,
          full_name: user.full_name || '',
          kyc_status: 'pending' as const,
          profile_completion: 20,
          created_at: user.created_at || new Date().toISOString(),
          updated_at: new Date().toISOString()
        };
        setProfile(basicProfile);
        console.log('Basic profile created:', basicProfile);
      }
    } catch (error) {
      console.error('Failed to fetch profile:', error);
      toast({
        title: "Error",
        description: "Failed to load profile",
        variant: "destructive"
      });

      // Create fallback profile on error
      const fallbackProfile = {
        id: user.id.toString(),
        username: user.username,
        email: user.email,
        full_name: user.full_name || '',
        kyc_status: 'pending' as const,
        profile_completion: 20,
        created_at: user.created_at || new Date().toISOString(),
        updated_at: new Date().toISOString()
      };
      setProfile(fallbackProfile);
      console.log('Fallback profile created:', fallbackProfile);
    } finally {
      setIsLoading(false);
    }
  };

  const fetchKycDocuments = async () => {
    if (!user?.id) return;

    try {
      const response = await fetch(`${ApiConfig.endpoints.users.enhancedProfile}?action=kyc_documents`, {
        credentials: 'include'
      });

      // Handle 401 Unauthorized silently (user not authenticated for KYC)
      if (response.status === 401) {
        // Silent handling - user may not have KYC access yet
        return;
      }

      const data = await response.json();

      if (data.success) {
        setKycDocuments(data.data.documents || []);
      }
    } catch (error) {
      // Only log non-authentication errors
      if (!error.message?.includes('401') && !error.message?.includes('Unauthorized')) {
        console.error('Failed to fetch KYC documents:', error);
      }
    }
  };

  const calculateProfileCompletion = (profileData: UserProfileData) => {
    const requiredFields = [
      'full_name', 'phone', 'country', 'city', 'date_of_birth',
      'whatsapp_number', 'telegram_username'
    ];
    const optionalFields = [
      'bio', 'twitter_handle', 'instagram_handle', 'linkedin_profile'
    ];
    
    const completedRequired = requiredFields.filter(field => 
      profileData[field as keyof UserProfileData]?.toString().trim()
    ).length;
    const completedOptional = optionalFields.filter(field => 
      profileData[field as keyof UserProfileData]?.toString().trim()
    ).length;
    
    const requiredScore = (completedRequired / requiredFields.length) * 70; // 70% for required
    const optionalScore = (completedOptional / optionalFields.length) * 20; // 20% for optional
    const kycScore = profile?.kyc_status === 'verified' ? 10 : 0; // 10% for KYC
    
    return Math.round(requiredScore + optionalScore + kycScore);
  };

  const saveProfile = async () => {
    if (!profile) return;

    setIsSaving(true);
    try {
      const updatedProfile = {
        ...profile,
        profile_completion: calculateProfileCompletion(profile),
        updated_at: new Date().toISOString()
      };

      console.log('Saving profile data:', updatedProfile);

      const response = await fetch(`${ApiConfig.endpoints.users.enhancedProfile}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({
          action: 'update',
          ...updatedProfile
        })
      });

      console.log('Response status:', response.status);
      const data = await response.json();
      console.log('Response data:', data);
      if (data.success) {
        setProfile(data.data.profile);
        setIsEditing(false);
        toast({
          title: "Success",
          description: "Profile updated successfully",
        });
      } else {
        throw new Error(data.message || 'Failed to save profile');
      }
    } catch (error) {
      console.error('Failed to save profile:', error);
      toast({
        title: "Error",
        description: "Failed to save profile",
        variant: "destructive"
      });
    } finally {
      setIsSaving(false);
    }
  };

  const handleKycUpload = async (file: File, documentType: string) => {
    setUploadingKyc(true);
    try {
      const formData = new FormData();
      formData.append('document', file);
      formData.append('type', documentType);
      formData.append('action', 'upload_kyc');

      const response = await fetch(`${ApiConfig.endpoints.users.enhancedProfile}`, {
        method: 'POST',
        credentials: 'include',
        body: formData
      });

      const data = await response.json();
      if (data.success) {
        fetchKycDocuments();
        toast({
          title: "Success",
          description: "KYC document uploaded successfully",
        });
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
      setUploadingKyc(false);
    }
  };

  const handleDeleteKycDocument = async (documentId: string, documentType: string) => {
    try {
      const response = await fetch(`${ApiConfig.endpoints.users.enhancedProfile}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({
          action: 'delete_kyc',
          document_id: documentId
        })
      });

      const data = await response.json();
      if (data.success) {
        fetchKycDocuments();
        toast({
          title: "Success",
          description: "Document deleted successfully",
        });
      } else {
        throw new Error(data.message || 'Delete failed');
      }
    } catch (error) {
      console.error('KYC delete failed:', error);
      toast({
        title: "Error",
        description: "Failed to delete document",
        variant: "destructive"
      });
    }
  };

  const openSocialMedia = (platform: string, handle: string) => {
    const urls = {
      whatsapp: `https://wa.me/${handle.replace(/[^\d+]/g, '')}`,
      telegram: `https://t.me/${handle.replace('@', '')}`,
      twitter: `https://twitter.com/${handle.replace('@', '')}`,
      instagram: `https://instagram.com/${handle.replace('@', '')}`,
      linkedin: handle.startsWith('http') ? handle : `https://linkedin.com/in/${handle}`,
      facebook: handle.startsWith('http') ? handle : `https://facebook.com/${handle}`
    };
    window.open(urls[platform as keyof typeof urls], '_blank');
  };

  const getKYCStatusColor = (status: string) => {
    switch (status) {
      case 'verified': return 'bg-green-500/20 text-green-400 border-green-500/30';
      case 'rejected': return 'bg-red-500/20 text-red-400 border-red-500/30';
      default: return 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30';
    }
  };

  const getKYCStatusIcon = (status: string) => {
    switch (status) {
      case 'verified': return <CheckCircle className="h-4 w-4" />;
      case 'rejected': return <AlertCircle className="h-4 w-4" />;
      default: return <Clock className="h-4 w-4" />;
    }
  };

  if (userLoading || isLoading) {
    return (
      <div className="flex flex-col items-center justify-center py-12">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-gold-400 mb-4"></div>
        <p className="text-gray-400">
          {userLoading ? 'Loading user data...' : 'Loading your profile...'}
        </p>
        <p className="text-sm text-gray-500 mt-2">User ID: {user?.id || 'Not available'}</p>
      </div>
    );
  }

  if (!user) {
    return (
      <div className="text-center py-8">
        <User className="h-12 w-12 text-gray-400 mx-auto mb-4" />
        <p className="text-gray-400">User not authenticated</p>
        <p className="text-sm text-gray-500 mt-2">Please log in to view your profile</p>
      </div>
    );
  }

  if (!profile) {
    return (
      <div className="text-center py-8">
        <User className="h-12 w-12 text-gray-400 mx-auto mb-4" />
        <p className="text-gray-400">Profile not found</p>
        <p className="text-sm text-gray-500 mt-2">User: {user?.username} (ID: {user?.id})</p>
        <Button
          onClick={fetchProfile}
          className="mt-4 bg-gold-gradient text-black"
        >
          Retry Loading Profile
        </Button>
      </div>
    );
  }

  // Helper function to safely format numbers
  const formatCurrency = (value: any): string => {
    const num = Number(value || 0);
    return isNaN(num) ? '0.00' : num.toFixed(2);
  };

  return (
    <div className="space-y-6">
      {/* Debug Info - Remove in production */}
      {process.env.NODE_ENV === 'development' && (
        <Card className="bg-gray-800 border-gray-700">
          <CardHeader>
            <CardTitle className="text-white text-sm">Debug Info</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-xs text-gray-400 space-y-1">
              <p>User ID: {user?.id}</p>
              <p>Username: {user?.username}</p>
              <p>Profile loaded: {profile ? 'Yes' : 'No'}</p>
              <p>API Endpoint: {ApiConfig.endpoints.users.enhancedProfile}</p>
              {profile && (
                <>
                  <p>Profile completion: {profile.profile_completion}%</p>
                  <p>Total invested: {profile.total_invested} (type: {typeof profile.total_invested})</p>
                  <p>Total commissions: {profile.total_commissions} (type: {typeof profile.total_commissions})</p>
                </>
              )}
            </div>
          </CardContent>
        </Card>
      )}

      {/* Profile Header */}
      <Card className="bg-gray-800 border-gray-700">
        <CardContent className="p-6">
          <div className="flex items-start justify-between">
            <div className="flex items-center gap-4">
              <div className="relative">
                <div className="w-20 h-20 bg-gradient-to-br from-gold to-yellow-600 rounded-full flex items-center justify-center">
                  {profile.profile_image ? (
                    <img
                      src={profile.profile_image}
                      alt="Profile"
                      className="w-full h-full rounded-full object-cover"
                    />
                  ) : (
                    <User className="h-10 w-10 text-black" />
                  )}
                </div>
                <Badge
                  className={`absolute -bottom-1 -right-1 ${getKYCStatusColor(profile.kyc_status)} border`}
                >
                  {getKYCStatusIcon(profile.kyc_status)}
                </Badge>
              </div>
              <div>
                <h2 className="text-2xl font-bold text-white">{profile.full_name || profile.username}</h2>
                <p className="text-gray-400">@{profile.username}</p>
                <p className="text-sm text-gray-500">{profile.email}</p>
                <div className="flex items-center gap-2 mt-2">
                  <Badge variant="secondary" className="bg-gold/20 text-gold border-gold/30">
                    ANGEL FUNDER
                  </Badge>
                  <Badge className={getKYCStatusColor(profile.kyc_status)}>
                    {profile.kyc_status.toUpperCase()}
                  </Badge>
                </div>
              </div>
            </div>

            <div className="text-right">
              <div className="mb-4">
                <p className="text-sm text-gray-400">Profile Completion</p>
                <div className="flex items-center gap-2">
                  <Progress value={profile.profile_completion} className="w-24" />
                  <span className="text-sm font-medium text-white">{profile.profile_completion}%</span>
                </div>
              </div>

              {!isEditing ? (
                <Button
                  onClick={() => setIsEditing(true)}
                  className="bg-gold-gradient text-black hover:opacity-90"
                >
                  <Edit className="h-4 w-4 mr-2" />
                  Edit Profile
                </Button>
              ) : (
                <div className="flex gap-2">
                  <Button
                    onClick={saveProfile}
                    disabled={isSaving}
                    className="bg-green-600 hover:bg-green-700 text-white"
                  >
                    <Save className="h-4 w-4 mr-2" />
                    {isSaving ? 'Saving...' : 'Save'}
                  </Button>
                  <Button
                    onClick={() => setIsEditing(false)}
                    variant="outline"
                    className="border-gray-600 text-gray-300 hover:bg-gray-700"
                  >
                    <X className="h-4 w-4 mr-2" />
                    Cancel
                  </Button>
                </div>
              )}
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Profile Tabs */}
      <Tabs value={activeTab} onValueChange={setActiveTab} className="w-full">
        <TabsList className="grid w-full grid-cols-4 bg-gray-800 border-gray-700">
          <TabsTrigger value="overview" className="data-[state=active]:bg-gold data-[state=active]:text-black">
            <User className="h-4 w-4 mr-2" />
            Overview
          </TabsTrigger>
          <TabsTrigger value="personal" className="data-[state=active]:bg-gold data-[state=active]:text-black">
            <FileText className="h-4 w-4 mr-2" />
            Personal Info
          </TabsTrigger>
          <TabsTrigger value="social" className="data-[state=active]:bg-gold data-[state=active]:text-black">
            <Globe className="h-4 w-4 mr-2" />
            Social Media
          </TabsTrigger>
          <TabsTrigger value="stats" className="data-[state=active]:bg-gold data-[state=active]:text-black">
            <TrendingUp className="h-4 w-4 mr-2" />
            Statistics
          </TabsTrigger>
        </TabsList>

        {/* Overview Tab */}
        <TabsContent value="overview" className="space-y-6">
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {/* Quick Stats */}
            <Card className="bg-gray-800 border-gray-700">
              <CardHeader>
                <CardTitle className="text-white flex items-center gap-2">
                  <DollarSign className="h-5 w-5 text-gold" />
                  Investment Stats
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-3">
                <div className="flex justify-between">
                  <span className="text-gray-400">Total Invested:</span>
                  <span className="text-white font-medium">${formatCurrency(profile.total_invested)}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-400">Total Commissions:</span>
                  <span className="text-green-400 font-medium">${formatCurrency(profile.total_commissions)}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-400">Referrals:</span>
                  <span className="text-blue-400 font-medium">{profile.referral_count || 0}</span>
                </div>
              </CardContent>
            </Card>

            {/* KYC Status */}
            <Card className="bg-gray-800 border-gray-700">
              <CardHeader>
                <CardTitle className="text-white flex items-center gap-2">
                  <Shield className="h-5 w-5 text-gold" />
                  KYC Status
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="flex items-center gap-3">
                  {getKYCStatusIcon(profile.kyc_status)}
                  <div>
                    <p className="text-white font-medium capitalize">{profile.kyc_status}</p>
                    {profile.kyc_verified_at && (
                      <p className="text-sm text-gray-400">
                        Verified: {new Date(profile.kyc_verified_at).toLocaleDateString()}
                      </p>
                    )}
                    {profile.kyc_rejected_reason && (
                      <p className="text-sm text-red-400">{profile.kyc_rejected_reason}</p>
                    )}
                  </div>
                </div>
              </CardContent>
            </Card>

            {/* Account Info */}
            <Card className="bg-gray-800 border-gray-700">
              <CardHeader>
                <CardTitle className="text-white flex items-center gap-2">
                  <Calendar className="h-5 w-5 text-gold" />
                  Account Info
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-3">
                <div>
                  <p className="text-gray-400 text-sm">Member Since:</p>
                  <p className="text-white">{new Date(profile.created_at).toLocaleDateString()}</p>
                </div>
                <div>
                  <p className="text-gray-400 text-sm">Last Updated:</p>
                  <p className="text-white">{new Date(profile.updated_at).toLocaleDateString()}</p>
                </div>
              </CardContent>
            </Card>
          </div>

          {/* Bio Section */}
          {(profile.bio || isEditing) && (
            <Card className="bg-gray-800 border-gray-700">
              <CardHeader>
                <CardTitle className="text-white flex items-center gap-2">
                  <MessageSquare className="h-5 w-5 text-gold" />
                  About Me
                </CardTitle>
              </CardHeader>
              <CardContent>
                {isEditing ? (
                  <Textarea
                    value={profile.bio || ''}
                    onChange={(e) => setProfile({...profile, bio: e.target.value})}
                    placeholder="Tell us about yourself..."
                    className="bg-gray-700 border-gray-600 text-white min-h-[100px]"
                  />
                ) : (
                  <p className="text-gray-300">{profile.bio || 'No bio available'}</p>
                )}
              </CardContent>
            </Card>
          )}
        </TabsContent>

        {/* Personal Info Tab */}
        <TabsContent value="personal" className="space-y-6">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {/* Basic Information */}
            <Card className="bg-gray-800 border-gray-700">
              <CardHeader>
                <CardTitle className="text-white flex items-center gap-2">
                  <User className="h-5 w-5 text-gold" />
                  Basic Information
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div>
                  <Label className="text-gray-400">Full Name</Label>
                  {isEditing ? (
                    <Input
                      value={profile.full_name || ''}
                      onChange={(e) => setProfile({...profile, full_name: e.target.value})}
                      className="bg-gray-700 border-gray-600 text-white"
                      placeholder="Enter your full name"
                    />
                  ) : (
                    <p className="text-white mt-1">{profile.full_name || 'Not provided'}</p>
                  )}
                </div>

                <div>
                  <Label className="text-gray-400">Phone Number</Label>
                  {isEditing ? (
                    <Input
                      value={profile.phone || ''}
                      onChange={(e) => setProfile({...profile, phone: e.target.value})}
                      className="bg-gray-700 border-gray-600 text-white"
                      placeholder="Enter your phone number"
                    />
                  ) : (
                    <p className="text-white mt-1 flex items-center gap-2">
                      <Phone className="h-4 w-4 text-gray-400" />
                      {profile.phone || 'Not provided'}
                    </p>
                  )}
                </div>

                <div>
                  <Label className="text-gray-400">Date of Birth</Label>
                  {isEditing ? (
                    <Input
                      type="date"
                      value={profile.date_of_birth || ''}
                      onChange={(e) => setProfile({...profile, date_of_birth: e.target.value})}
                      className="bg-gray-700 border-gray-600 text-white"
                    />
                  ) : (
                    <p className="text-white mt-1 flex items-center gap-2">
                      <Calendar className="h-4 w-4 text-gray-400" />
                      {profile.date_of_birth ? new Date(profile.date_of_birth).toLocaleDateString() : 'Not provided'}
                    </p>
                  )}
                </div>
              </CardContent>
            </Card>

            {/* Location Information */}
            <Card className="bg-gray-800 border-gray-700">
              <CardHeader>
                <CardTitle className="text-white flex items-center gap-2">
                  <MapPin className="h-5 w-5 text-gold" />
                  Location
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div>
                  <Label className="text-gray-400">Country</Label>
                  {isEditing ? (
                    <Input
                      value={profile.country || ''}
                      onChange={(e) => setProfile({...profile, country: e.target.value})}
                      className="bg-gray-700 border-gray-600 text-white"
                      placeholder="Enter your country"
                    />
                  ) : (
                    <p className="text-white mt-1">{profile.country || 'Not provided'}</p>
                  )}
                </div>

                <div>
                  <Label className="text-gray-400">City</Label>
                  {isEditing ? (
                    <Input
                      value={profile.city || ''}
                      onChange={(e) => setProfile({...profile, city: e.target.value})}
                      className="bg-gray-700 border-gray-600 text-white"
                      placeholder="Enter your city"
                    />
                  ) : (
                    <p className="text-white mt-1">{profile.city || 'Not provided'}</p>
                  )}
                </div>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        {/* Social Media Tab */}
        <TabsContent value="social" className="space-y-6">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {/* Communication Platforms */}
            <Card className="bg-gray-800 border-gray-700">
              <CardHeader>
                <CardTitle className="text-white flex items-center gap-2">
                  <MessageCircle className="h-5 w-5 text-gold" />
                  Communication
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div>
                  <Label className="text-gray-400">WhatsApp Number</Label>
                  {isEditing ? (
                    <Input
                      value={profile.whatsapp_number || ''}
                      onChange={(e) => setProfile({...profile, whatsapp_number: e.target.value})}
                      className="bg-gray-700 border-gray-600 text-white"
                      placeholder="Enter your WhatsApp number"
                    />
                  ) : (
                    <div className="flex items-center gap-2 mt-1">
                      <p className="text-white">{profile.whatsapp_number || 'Not provided'}</p>
                      {profile.whatsapp_number && (
                        <Button
                          size="sm"
                          variant="outline"
                          onClick={() => openSocialMedia('whatsapp', profile.whatsapp_number!)}
                          className="border-green-500 text-green-400 hover:bg-green-500/10"
                        >
                          <ExternalLink className="h-3 w-3" />
                        </Button>
                      )}
                    </div>
                  )}
                </div>

                <div>
                  <Label className="text-gray-400">Telegram Username</Label>
                  {isEditing ? (
                    <Input
                      value={profile.telegram_username || ''}
                      onChange={(e) => setProfile({...profile, telegram_username: e.target.value})}
                      className="bg-gray-700 border-gray-600 text-white"
                      placeholder="@username"
                    />
                  ) : (
                    <div className="flex items-center gap-2 mt-1">
                      <p className="text-white">{profile.telegram_username || 'Not provided'}</p>
                      {profile.telegram_username && (
                        <Button
                          size="sm"
                          variant="outline"
                          onClick={() => openSocialMedia('telegram', profile.telegram_username!)}
                          className="border-blue-500 text-blue-400 hover:bg-blue-500/10"
                        >
                          <ExternalLink className="h-3 w-3" />
                        </Button>
                      )}
                    </div>
                  )}
                </div>
              </CardContent>
            </Card>

            {/* Social Networks */}
            <Card className="bg-gray-800 border-gray-700">
              <CardHeader>
                <CardTitle className="text-white flex items-center gap-2">
                  <Share2 className="h-5 w-5 text-gold" />
                  Social Networks
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div>
                  <Label className="text-gray-400">Twitter Handle</Label>
                  {isEditing ? (
                    <Input
                      value={profile.twitter_handle || ''}
                      onChange={(e) => setProfile({...profile, twitter_handle: e.target.value})}
                      className="bg-gray-700 border-gray-600 text-white"
                      placeholder="@username"
                    />
                  ) : (
                    <div className="flex items-center gap-2 mt-1">
                      <p className="text-white">{profile.twitter_handle || 'Not provided'}</p>
                      {profile.twitter_handle && (
                        <Button
                          size="sm"
                          variant="outline"
                          onClick={() => openSocialMedia('twitter', profile.twitter_handle!)}
                          className="border-blue-400 text-blue-300 hover:bg-blue-400/10"
                        >
                          <ExternalLink className="h-3 w-3" />
                        </Button>
                      )}
                    </div>
                  )}
                </div>

                <div>
                  <Label className="text-gray-400">Instagram Handle</Label>
                  {isEditing ? (
                    <Input
                      value={profile.instagram_handle || ''}
                      onChange={(e) => setProfile({...profile, instagram_handle: e.target.value})}
                      className="bg-gray-700 border-gray-600 text-white"
                      placeholder="@username"
                    />
                  ) : (
                    <div className="flex items-center gap-2 mt-1">
                      <p className="text-white">{profile.instagram_handle || 'Not provided'}</p>
                      {profile.instagram_handle && (
                        <Button
                          size="sm"
                          variant="outline"
                          onClick={() => openSocialMedia('instagram', profile.instagram_handle!)}
                          className="border-pink-500 text-pink-400 hover:bg-pink-500/10"
                        >
                          <ExternalLink className="h-3 w-3" />
                        </Button>
                      )}
                    </div>
                  )}
                </div>

                <div>
                  <Label className="text-gray-400">LinkedIn Profile</Label>
                  {isEditing ? (
                    <Input
                      value={profile.linkedin_profile || ''}
                      onChange={(e) => setProfile({...profile, linkedin_profile: e.target.value})}
                      className="bg-gray-700 border-gray-600 text-white"
                      placeholder="LinkedIn URL or username"
                    />
                  ) : (
                    <div className="flex items-center gap-2 mt-1">
                      <p className="text-white">{profile.linkedin_profile || 'Not provided'}</p>
                      {profile.linkedin_profile && (
                        <Button
                          size="sm"
                          variant="outline"
                          onClick={() => openSocialMedia('linkedin', profile.linkedin_profile!)}
                          className="border-blue-600 text-blue-500 hover:bg-blue-600/10"
                        >
                          <ExternalLink className="h-3 w-3" />
                        </Button>
                      )}
                    </div>
                  )}
                </div>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        {/* Statistics Tab */}
        <TabsContent value="stats" className="space-y-6">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {/* Investment Statistics */}
            <Card className="bg-gray-800 border-gray-700">
              <CardHeader>
                <CardTitle className="text-white flex items-center gap-2">
                  <TrendingUp className="h-5 w-5 text-gold" />
                  Investment Performance
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="grid grid-cols-2 gap-4">
                  <div className="bg-gray-700/50 p-3 rounded-lg">
                    <p className="text-gray-400 text-sm">Total Invested</p>
                    <p className="text-2xl font-bold text-white">${formatCurrency(profile.total_invested)}</p>
                  </div>
                  <div className="bg-gray-700/50 p-3 rounded-lg">
                    <p className="text-gray-400 text-sm">Total Returns</p>
                    <p className="text-2xl font-bold text-green-400">${formatCurrency(profile.total_commissions)}</p>
                  </div>
                </div>

                <div className="bg-gray-700/50 p-3 rounded-lg">
                  <p className="text-gray-400 text-sm">ROI Percentage</p>
                  <p className="text-xl font-bold text-gold">
                    {Number(profile.total_invested || 0) > 0
                      ? formatCurrency((Number(profile.total_commissions || 0) / Number(profile.total_invested || 0)) * 100)
                      : '0.00'
                    }%
                  </p>
                </div>
              </CardContent>
            </Card>

            {/* Network Statistics */}
            <Card className="bg-gray-800 border-gray-700">
              <CardHeader>
                <CardTitle className="text-white flex items-center gap-2">
                  <Users className="h-5 w-5 text-gold" />
                  Network Growth
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="grid grid-cols-2 gap-4">
                  <div className="bg-gray-700/50 p-3 rounded-lg">
                    <p className="text-gray-400 text-sm">Total Referrals</p>
                    <p className="text-2xl font-bold text-blue-400">{profile.referral_count || 0}</p>
                  </div>
                  <div className="bg-gray-700/50 p-3 rounded-lg">
                    <p className="text-gray-400 text-sm">Active Network</p>
                    <p className="text-2xl font-bold text-green-400">{profile.referral_count || 0}</p>
                  </div>
                </div>

                <div className="bg-gray-700/50 p-3 rounded-lg">
                  <p className="text-gray-400 text-sm">Commission Earnings</p>
                  <p className="text-xl font-bold text-gold">${formatCurrency(profile.total_commissions)}</p>
                </div>
              </CardContent>
            </Card>

            {/* Achievement Badges */}
            <Card className="bg-gray-800 border-gray-700 lg:col-span-2">
              <CardHeader>
                <CardTitle className="text-white flex items-center gap-2">
                  <Award className="h-5 w-5 text-gold" />
                  Achievements & Milestones
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                  <div className="text-center p-4 bg-gray-700/50 rounded-lg">
                    <Star className="h-8 w-8 text-gold mx-auto mb-2" />
                    <p className="text-sm text-gray-400">Profile Complete</p>
                    <p className="text-lg font-bold text-white">{profile.profile_completion}%</p>
                  </div>

                  <div className="text-center p-4 bg-gray-700/50 rounded-lg">
                    <Shield className="h-8 w-8 text-green-400 mx-auto mb-2" />
                    <p className="text-sm text-gray-400">KYC Status</p>
                    <p className="text-lg font-bold text-white capitalize">{profile.kyc_status}</p>
                  </div>

                  <div className="text-center p-4 bg-gray-700/50 rounded-lg">
                    <Users className="h-8 w-8 text-blue-400 mx-auto mb-2" />
                    <p className="text-sm text-gray-400">Network Size</p>
                    <p className="text-lg font-bold text-white">{profile.referral_count || 0}</p>
                  </div>

                  <div className="text-center p-4 bg-gray-700/50 rounded-lg">
                    <TrendingUp className="h-8 w-8 text-purple-400 mx-auto mb-2" />
                    <p className="text-sm text-gray-400">Member Since</p>
                    <p className="text-lg font-bold text-white">
                      {new Date(profile.created_at).getFullYear()}
                    </p>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </TabsContent>
      </Tabs>
    </div>
  );
};

export default EnhancedUserProfile;



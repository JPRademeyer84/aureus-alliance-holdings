import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { useToast } from '@/hooks/use-toast';
import {
  User,
  Phone,
  MapPin,
  Calendar,
  Globe,
  MessageSquare,
  Camera,
  Save,
  Edit,
  Shield,
  CheckCircle,
  AlertCircle,
  ExternalLink
} from 'lucide-react';
import { useUser } from '@/contexts/UserContext';

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
  
  // KYC Status
  kyc_status: 'pending' | 'verified' | 'rejected';
  kyc_documents?: string[];
  
  // Profile Completion
  profile_completion: number;
  
  // Timestamps
  created_at: string;
  updated_at: string;
}

interface UserProfileProps {
  userId?: string;
  isOwnProfile?: boolean;
  onProfileUpdate?: (profile: UserProfileData) => void;
}

const UserProfile: React.FC<UserProfileProps> = ({ 
  userId, 
  isOwnProfile = true, 
  onProfileUpdate 
}) => {
  const { user } = useUser();
  const { toast } = useToast();
  const [profile, setProfile] = useState<UserProfileData | null>(null);
  const [isEditing, setIsEditing] = useState(false);
  const [isLoading, setIsLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);

  const fetchProfile = async () => {
    setIsLoading(true);
    try {
      const targetUserId = userId || user?.id;
      if (!targetUserId) return;

      const response = await fetch(`http://localhost/aureus-angel-alliance/api/users/profile/${targetUserId}`);
      if (response.ok) {
        const data = await response.json();
        setProfile(data.profile);
      } else {
        // If profile doesn't exist, create basic profile from user data
        if (isOwnProfile && user) {
          setProfile({
            id: user.id.toString(),
            username: user.username,
            email: user.email,
            full_name: user.full_name || '',
            kyc_status: 'pending',
            profile_completion: calculateProfileCompletion(user),
            created_at: user.created_at || new Date().toISOString(),
            updated_at: new Date().toISOString()
          });
        }
      }
    } catch (error) {
      console.error('Failed to fetch profile:', error);
      toast({
        title: "Error",
        description: "Failed to load profile",
        variant: "destructive"
      });
    } finally {
      setIsLoading(false);
    }
  };

  const calculateProfileCompletion = (profileData: any) => {
    const fields = [
      'full_name', 'phone', 'country', 'city', 'date_of_birth',
      'telegram_username', 'whatsapp_number'
    ];
    const completedFields = fields.filter(field => profileData[field]?.trim());
    return Math.round((completedFields.length / fields.length) * 100);
  };

  const saveProfile = async () => {
    if (!profile) return;

    setIsSaving(true);
    try {
      const response = await fetch('http://localhost/aureus-angel-alliance/api/users/update-profile.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({
          username: profile.username,
          email: profile.email,
          fullName: profile.full_name,
          whatsappNumber: profile.whatsapp_number || '',
          telegramUsername: profile.telegram_username || '',
          twitterHandle: profile.twitter_handle || '',
          instagramHandle: profile.instagram_handle || '',
          linkedinProfile: profile.linkedin_profile || ''
        })
      });

      const data = await response.json();

      if (data.success) {
        // Update profile with returned data
        if (data.data && data.data.user) {
          setProfile({
            ...profile,
            ...data.data.user,
            profile_completion: calculateProfileCompletion(data.data.user),
            updated_at: new Date().toISOString()
          });
        }
        setIsEditing(false);
        onProfileUpdate?.(profile);
        toast({
          title: "Success",
          description: "Profile updated successfully",
        });
      } else {
        throw new Error(data.error || 'Failed to save profile');
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

  const openWhatsApp = (number: string) => {
    const cleanNumber = number.replace(/[^\d+]/g, '');
    window.open(`https://wa.me/${cleanNumber}`, '_blank');
  };

  const openTelegram = (username: string) => {
    const cleanUsername = username.replace('@', '');
    window.open(`https://t.me/${cleanUsername}`, '_blank');
  };

  const openSocialMedia = (platform: string, handle: string) => {
    const urls = {
      twitter: `https://twitter.com/${handle.replace('@', '')}`,
      instagram: `https://instagram.com/${handle.replace('@', '')}`,
      linkedin: handle.startsWith('http') ? handle : `https://linkedin.com/in/${handle}`,
      facebook: handle.startsWith('http') ? handle : `https://facebook.com/${handle}`
    };
    window.open(urls[platform as keyof typeof urls], '_blank');
  };

  useEffect(() => {
    fetchProfile();
  }, [userId, user?.id]);

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-8">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-gold"></div>
      </div>
    );
  }

  if (!profile) {
    return (
      <div className="text-center py-8">
        <User className="h-12 w-12 text-gray-400 mx-auto mb-4" />
        <p className="text-gray-400">Profile not found</p>
      </div>
    );
  }

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
      default: return <Shield className="h-4 w-4" />;
    }
  };

  return (
    <div className="space-y-6">
      {/* Profile Header */}
      <Card className="bg-gray-800 border-gray-700">
        <CardContent className="p-6">
          <div className="flex items-start justify-between">
            <div className="flex items-start gap-4">
              {/* Profile Image */}
              <div className="relative">
                <div className="w-20 h-20 bg-gradient-to-r from-gold to-yellow-600 rounded-full flex items-center justify-center">
                  {profile.profile_image ? (
                    <img 
                      src={profile.profile_image} 
                      alt={profile.full_name}
                      className="w-full h-full rounded-full object-cover"
                    />
                  ) : (
                    <span className="text-black font-bold text-2xl">
                      {profile.full_name?.charAt(0) || profile.username.charAt(0)}
                    </span>
                  )}
                </div>
                {isOwnProfile && isEditing && (
                  <Button
                    size="sm"
                    className="absolute -bottom-2 -right-2 w-8 h-8 p-0 rounded-full"
                  >
                    <Camera className="h-4 w-4" />
                  </Button>
                )}
              </div>

              {/* Profile Info */}
              <div className="flex-1">
                <div className="flex items-center gap-3 mb-2">
                  <h2 className="text-2xl font-bold text-white">
                    {profile.full_name || profile.username}
                  </h2>
                  <Badge className={getKYCStatusColor(profile.kyc_status)}>
                    {getKYCStatusIcon(profile.kyc_status)}
                    <span className="ml-1 capitalize">{profile.kyc_status}</span>
                  </Badge>
                </div>
                
                <p className="text-gray-400 mb-2">@{profile.username}</p>
                
                {profile.bio && (
                  <p className="text-gray-300 text-sm mb-3">{profile.bio}</p>
                )}

                {/* Profile Completion */}
                <div className="flex items-center gap-2 mb-3">
                  <span className="text-sm text-gray-400">Profile Completion:</span>
                  <div className="flex-1 bg-gray-700 rounded-full h-2 max-w-32">
                    <div 
                      className="bg-gold h-2 rounded-full transition-all duration-300"
                      style={{ width: `${profile.profile_completion}%` }}
                    />
                  </div>
                  <span className="text-sm text-gold font-medium">{profile.profile_completion}%</span>
                </div>

                {/* Quick Contact Buttons */}
                {!isOwnProfile && (
                  <div className="flex items-center gap-2">
                    {profile.whatsapp_number && (
                      <Button
                        size="sm"
                        onClick={() => openWhatsApp(profile.whatsapp_number!)}
                        className="bg-green-600 hover:bg-green-700"
                      >
                        <MessageSquare className="h-4 w-4 mr-1" />
                        WhatsApp
                      </Button>
                    )}
                    {profile.telegram_username && (
                      <Button
                        size="sm"
                        onClick={() => openTelegram(profile.telegram_username!)}
                        className="bg-blue-600 hover:bg-blue-700"
                      >
                        <MessageSquare className="h-4 w-4 mr-1" />
                        Telegram
                      </Button>
                    )}
                  </div>
                )}
              </div>
            </div>

            {/* Edit Button */}
            {isOwnProfile && (
              <div className="flex items-center gap-2">
                {isEditing ? (
                  <>
                    <Button
                      onClick={saveProfile}
                      disabled={isSaving}
                      className="bg-gold-gradient text-black"
                    >
                      <Save className="h-4 w-4 mr-2" />
                      {isSaving ? 'Saving...' : 'Save'}
                    </Button>
                    <Button
                      onClick={() => setIsEditing(false)}
                      variant="outline"
                    >
                      Cancel
                    </Button>
                  </>
                ) : (
                  <Button
                    onClick={() => setIsEditing(true)}
                    variant="outline"
                  >
                    <Edit className="h-4 w-4 mr-2" />
                    Edit Profile
                  </Button>
                )}
              </div>
            )}
          </div>
        </CardContent>
      </Card>

      {/* Profile Details */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Personal Information */}
        <Card className="bg-gray-800 border-gray-700">
          <CardHeader>
            <CardTitle className="text-white flex items-center gap-2">
              <User className="h-5 w-5 text-gold" />
              Personal Information
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            {isEditing ? (
              <>
                <div>
                  <Label className="text-gray-400">Full Name</Label>
                  <Input
                    value={profile.full_name || ''}
                    onChange={(e) => setProfile({...profile, full_name: e.target.value})}
                    className="bg-gray-700 border-gray-600 text-white"
                    placeholder="Enter your full name"
                  />
                </div>
                <div>
                  <Label className="text-gray-400">Phone Number</Label>
                  <Input
                    value={profile.phone || ''}
                    onChange={(e) => setProfile({...profile, phone: e.target.value})}
                    className="bg-gray-700 border-gray-600 text-white"
                    placeholder="+1234567890"
                  />
                </div>
                <div>
                  <Label className="text-gray-400">Country</Label>
                  <Input
                    value={profile.country || ''}
                    onChange={(e) => setProfile({...profile, country: e.target.value})}
                    className="bg-gray-700 border-gray-600 text-white"
                    placeholder="Your country"
                  />
                </div>
                <div>
                  <Label className="text-gray-400">City</Label>
                  <Input
                    value={profile.city || ''}
                    onChange={(e) => setProfile({...profile, city: e.target.value})}
                    className="bg-gray-700 border-gray-600 text-white"
                    placeholder="Your city"
                  />
                </div>
                <div>
                  <Label className="text-gray-400">Date of Birth</Label>
                  <Input
                    type="date"
                    value={profile.date_of_birth || ''}
                    onChange={(e) => setProfile({...profile, date_of_birth: e.target.value})}
                    className="bg-gray-700 border-gray-600 text-white"
                  />
                </div>
              </>
            ) : (
              <>
                <div className="flex items-center justify-between">
                  <span className="text-gray-400">Full Name:</span>
                  <span className="text-white">{profile.full_name || 'Not set'}</span>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-gray-400">Phone:</span>
                  <span className="text-white">{profile.phone || 'Not set'}</span>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-gray-400">Location:</span>
                  <span className="text-white">
                    {profile.city && profile.country
                      ? `${profile.city}, ${profile.country}`
                      : profile.country || 'Not set'
                    }
                  </span>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-gray-400">Date of Birth:</span>
                  <span className="text-white">
                    {profile.date_of_birth
                      ? new Date(profile.date_of_birth).toLocaleDateString()
                      : 'Not set'
                    }
                  </span>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-gray-400">Member Since:</span>
                  <span className="text-white">
                    {new Date(profile.created_at).toLocaleDateString()}
                  </span>
                </div>
              </>
            )}
          </CardContent>
        </Card>

        {/* Contact & Social Media */}
        <Card className="bg-gray-800 border-gray-700">
          <CardHeader>
            <CardTitle className="text-white flex items-center gap-2">
              <MessageSquare className="h-5 w-5 text-gold" />
              Contact & Social Media
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            {isEditing ? (
              <>
                <div>
                  <Label className="text-gray-400">WhatsApp Number</Label>
                  <Input
                    value={profile.whatsapp_number || ''}
                    onChange={(e) => setProfile({...profile, whatsapp_number: e.target.value})}
                    className="bg-gray-700 border-gray-600 text-white"
                    placeholder="+1234567890"
                  />
                </div>
                <div>
                  <Label className="text-gray-400">Telegram Username</Label>
                  <Input
                    value={profile.telegram_username || ''}
                    onChange={(e) => setProfile({...profile, telegram_username: e.target.value})}
                    className="bg-gray-700 border-gray-600 text-white"
                    placeholder="@username"
                  />
                </div>
                <div>
                  <Label className="text-gray-400">Twitter Handle</Label>
                  <Input
                    value={profile.twitter_handle || ''}
                    onChange={(e) => setProfile({...profile, twitter_handle: e.target.value})}
                    className="bg-gray-700 border-gray-600 text-white"
                    placeholder="@username"
                  />
                </div>
                <div>
                  <Label className="text-gray-400">Instagram Handle</Label>
                  <Input
                    value={profile.instagram_handle || ''}
                    onChange={(e) => setProfile({...profile, instagram_handle: e.target.value})}
                    className="bg-gray-700 border-gray-600 text-white"
                    placeholder="@username"
                  />
                </div>
                <div>
                  <Label className="text-gray-400">LinkedIn Profile</Label>
                  <Input
                    value={profile.linkedin_profile || ''}
                    onChange={(e) => setProfile({...profile, linkedin_profile: e.target.value})}
                    className="bg-gray-700 border-gray-600 text-white"
                    placeholder="linkedin.com/in/username"
                  />
                </div>
              </>
            ) : (
              <>
                {profile.whatsapp_number && (
                  <div className="flex items-center justify-between">
                    <span className="text-gray-400">WhatsApp:</span>
                    <Button
                      size="sm"
                      variant="outline"
                      onClick={() => openWhatsApp(profile.whatsapp_number!)}
                      className="border-green-500/30 text-green-400 hover:bg-green-500/20"
                    >
                      <MessageSquare className="h-4 w-4 mr-1" />
                      {profile.whatsapp_number}
                    </Button>
                  </div>
                )}
                {profile.telegram_username && (
                  <div className="flex items-center justify-between">
                    <span className="text-gray-400">Telegram:</span>
                    <Button
                      size="sm"
                      variant="outline"
                      onClick={() => openTelegram(profile.telegram_username!)}
                      className="border-blue-500/30 text-blue-400 hover:bg-blue-500/20"
                    >
                      <MessageSquare className="h-4 w-4 mr-1" />
                      {profile.telegram_username}
                    </Button>
                  </div>
                )}
                {profile.twitter_handle && (
                  <div className="flex items-center justify-between">
                    <span className="text-gray-400">Twitter:</span>
                    <Button
                      size="sm"
                      variant="outline"
                      onClick={() => openSocialMedia('twitter', profile.twitter_handle!)}
                      className="border-sky-500/30 text-sky-400 hover:bg-sky-500/20"
                    >
                      <ExternalLink className="h-4 w-4 mr-1" />
                      {profile.twitter_handle}
                    </Button>
                  </div>
                )}
                {profile.instagram_handle && (
                  <div className="flex items-center justify-between">
                    <span className="text-gray-400">Instagram:</span>
                    <Button
                      size="sm"
                      variant="outline"
                      onClick={() => openSocialMedia('instagram', profile.instagram_handle!)}
                      className="border-pink-500/30 text-pink-400 hover:bg-pink-500/20"
                    >
                      <ExternalLink className="h-4 w-4 mr-1" />
                      {profile.instagram_handle}
                    </Button>
                  </div>
                )}
                {profile.linkedin_profile && (
                  <div className="flex items-center justify-between">
                    <span className="text-gray-400">LinkedIn:</span>
                    <Button
                      size="sm"
                      variant="outline"
                      onClick={() => openSocialMedia('linkedin', profile.linkedin_profile!)}
                      className="border-blue-700/30 text-blue-300 hover:bg-blue-700/20"
                    >
                      <ExternalLink className="h-4 w-4 mr-1" />
                      View Profile
                    </Button>
                  </div>
                )}
                {!profile.whatsapp_number && !profile.telegram_username && !profile.twitter_handle &&
                 !profile.instagram_handle && !profile.linkedin_profile && (
                  <p className="text-gray-500 text-center py-4">
                    No contact information available
                  </p>
                )}
              </>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
};

export default UserProfile;

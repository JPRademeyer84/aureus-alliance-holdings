import React, { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useToast } from '@/hooks/use-toast';
import {
  X,
  User,
  FileText,
  Camera,
  Eye,
  CheckCircle,
  XCircle,
  Clock,
  Download,
  AlertTriangle as AlertCircle,
  Calendar,
  Phone,
  Mail,
  Shield,
  Star,
  RefreshCw
} from '@/components/SafeIcons';

// Safe additional icons
const MapPin = ({ className }: { className?: string }) => <span className={className}>📍</span>;
const Globe = ({ className }: { className?: string }) => <span className={className}>🌐</span>;
const Activity = ({ className }: { className?: string }) => <span className={className}>📊</span>;
const History = ({ className }: { className?: string }) => <span className={className}>📜</span>;
const Image = ({ className }: { className?: string }) => <span className={className}>🖼️</span>;
const Percent = ({ className }: { className?: string }) => <span className={className}>%</span>;
import ApiConfig from '@/config/api';

interface UserDetailsModalProps {
  userId: string;
  onClose: () => void;
  onApprove?: (documentId: string) => void;
  onReject?: (documentId: string, reason: string) => void;
}

interface UserProfile {
  id: string;
  username: string;
  email: string;
  full_name: string;
  phone: string;
  country: string;
  city: string;
  date_of_birth: string;
  bio: string;
  telegram_username: string;
  whatsapp_number: string;
  twitter_handle: string;
  instagram_handle: string;
  linkedin_profile: string;
  facebook_profile: string;
  kyc_status: string;
  kyc_verified_at: string;
  kyc_rejected_reason: string;
  profile_completion: number;
  created_at: string;
  updated_at: string;
  is_active: boolean;
  email_verified: boolean;
  last_login: string;
  facial_verification_status: string;
  facial_verification_at: string;
  // Enhanced KYC fields
  first_name?: string;
  last_name?: string;
  middle_name?: string;
  nationality?: string;
  gender?: string;
  place_of_birth?: string;
  address_line_1?: string;
  address_line_2?: string;
  state_province?: string;
  postal_code?: string;
  id_type?: string;
  id_number?: string;
  id_expiry_date?: string;
  occupation?: string;
  employer?: string;
  annual_income?: string;
  source_of_funds?: string;
  purpose_of_account?: string;
  emergency_contact_name?: string;
  emergency_contact_phone?: string;
  emergency_contact_relationship?: string;
  // Enhanced KYC status fields
  personal_info_status?: string;
  contact_info_status?: string;
  address_info_status?: string;
  identity_info_status?: string;
  financial_info_status?: string;
  emergency_contact_status?: string;
  personal_info_rejection_reason?: string;
  contact_info_rejection_reason?: string;
  address_info_rejection_reason?: string;
  identity_info_rejection_reason?: string;
  financial_info_rejection_reason?: string;
  emergency_contact_rejection_reason?: string;
}

interface KYCDocument {
  id: string;
  type: string;
  filename: string;
  original_name: string;
  file_path: string;
  status: string;
  upload_date: string;
  reviewed_by: string;
  reviewed_at: string;
  rejection_reason: string;
  file_size?: number;
  mime_type?: string;
}

interface FacialVerification {
  id: string;
  captured_image_path: string;
  confidence_score: number;
  liveness_score: number;
  verification_status: string;
  comparison_result: string;
  created_at: string;
  verified_at: string;
}

interface DocumentAccessLog {
  id: string;
  document_id: string;
  accessed_by: string;
  access_type: string;
  accessed_at: string;
  document_type?: string;
  filename?: string;
}

interface KYCSectionAuditLog {
  id: string;
  user_id: string;
  section: string;
  action: string;
  admin_id: string;
  rejection_reason?: string;
  created_at: string;
}

const UserDetailsModal: React.FC<UserDetailsModalProps> = ({
  userId,
  onClose,
  onApprove,
  onReject
}) => {
  const { toast } = useToast();
  const [userProfile, setUserProfile] = useState<UserProfile | null>(null);
  const [kycDocuments, setKycDocuments] = useState<KYCDocument[]>([]);
  const [facialVerifications, setFacialVerifications] = useState<FacialVerification[]>([]);
  const [accessLogs, setAccessLogs] = useState<DocumentAccessLog[]>([]);
  const [kycSectionAuditLogs, setKycSectionAuditLogs] = useState<KYCSectionAuditLog[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [rejectionReason, setRejectionReason] = useState('');
  const [selectedDocumentId, setSelectedDocumentId] = useState<string | null>(null);
  const [selectedSection, setSelectedSection] = useState<string | null>(null);
  const [activeTab, setActiveTab] = useState('profile');
  const [isSyncing, setIsSyncing] = useState(false);

  useEffect(() => {
    fetchUserDetails();
  }, [userId]);

  const fetchUserDetails = async () => {
    setIsLoading(true);
    try {
      // Fetch comprehensive user data
      const [profileResponse, kycResponse, facialResponse, accessLogsResponse, kycAuditResponse] = await Promise.all([
        fetch(`${ApiConfig.endpoints.admin.manageUsers}?action=get_user&user_id=${userId}`, {
          credentials: 'include'
        }),
        fetch(`${ApiConfig.endpoints.admin.kycManagement}?action=get_user_documents&user_id=${userId}`, {
          credentials: 'include'
        }),
        fetch(`${ApiConfig.endpoints.admin.kycManagement}?action=get_all_facial_verifications&user_id=${userId}`, {
          credentials: 'include'
        }),
        fetch(`${ApiConfig.endpoints.admin.kycManagement}?action=get_access_logs&user_id=${userId}`, {
          credentials: 'include'
        }),
        fetch(`${ApiConfig.endpoints.admin.kycManagement}?action=get_kyc_section_audit_logs&user_id=${userId}`, {
          credentials: 'include'
        })
      ]);

      if (profileResponse.ok) {
        const profileData = await profileResponse.json();
        if (profileData.success) {
          setUserProfile(profileData.data.user);
        }
      }

      if (kycResponse.ok) {
        const kycData = await kycResponse.json();
        if (kycData.success) {
          setKycDocuments(kycData.data.documents || []);
        }
      }

      if (facialResponse.ok) {
        const facialData = await facialResponse.json();
        if (facialData.success) {
          setFacialVerifications(facialData.data.verifications || []);
        }
      }

      if (accessLogsResponse.ok) {
        const accessData = await accessLogsResponse.json();
        if (accessData.success) {
          setAccessLogs(accessData.data.logs || []);
        }
      }

      if (kycAuditResponse.ok) {
        const kycAuditData = await kycAuditResponse.json();
        if (kycAuditData.success) {
          setKycSectionAuditLogs(kycAuditData.data.logs || []);
        }
      }

    } catch (error) {
      console.error('Failed to fetch user details:', error);
      toast({
        title: "Error",
        description: "Failed to load user details",
        variant: "destructive"
      });
    } finally {
      setIsLoading(false);
    }
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'verified':
      case 'approved':
        return 'bg-green-500/20 text-green-400 border-green-500/30';
      case 'pending':
        return 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30';
      case 'rejected':
      case 'failed':
        return 'bg-red-500/20 text-red-400 border-red-500/30';
      default:
        return 'bg-gray-500/20 text-gray-400 border-gray-500/30';
    }
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'verified':
      case 'approved':
        return <CheckCircle className="h-3 w-3" />;
      case 'pending':
        return <Clock className="h-3 w-3" />;
      case 'rejected':
      case 'failed':
        return <XCircle className="h-3 w-3" />;
      default:
        return <AlertCircle className="h-3 w-3" />;
    }
  };

  const handleDocumentApprove = async (documentId: string) => {
    if (onApprove) {
      await onApprove(documentId);
      fetchUserDetails(); // Refresh data
    }
  };

  const handleDocumentReject = async () => {
    if (selectedDocumentId && onReject && rejectionReason.trim()) {
      await onReject(selectedDocumentId, rejectionReason);
      setSelectedDocumentId(null);
      setRejectionReason('');
      fetchUserDetails(); // Refresh data
    }
  };

  const handleSectionApprove = async (section: string) => {
    try {
      const response = await fetch(`${ApiConfig.endpoints.admin.kycManagement}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({
          action: 'approve_kyc_section',
          user_id: userId,
          section: section
        })
      });

      const data = await response.json();
      if (data.success) {
        toast({
          title: "Success",
          description: `${section.replace('_', ' ')} section approved successfully`,
          variant: "default"
        });
        fetchUserDetails(); // Refresh data
      } else {
        throw new Error(data.message || 'Failed to approve section');
      }
    } catch (error) {
      console.error('Failed to approve section:', error);
      toast({
        title: "Error",
        description: "Failed to approve section",
        variant: "destructive"
      });
    }
  };

  const handleSectionReject = async () => {
    if (selectedSection && rejectionReason.trim()) {
      try {
        const response = await fetch(`${ApiConfig.endpoints.admin.kycManagement}`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          credentials: 'include',
          body: JSON.stringify({
            action: 'reject_kyc_section',
            user_id: userId,
            section: selectedSection,
            rejection_reason: rejectionReason
          })
        });

        const data = await response.json();
        if (data.success) {
          toast({
            title: "Success",
            description: `${selectedSection.replace('_', ' ')} section rejected`,
            variant: "default"
          });
          setSelectedSection(null);
          setRejectionReason('');
          fetchUserDetails(); // Refresh data
        } else {
          throw new Error(data.message || 'Failed to reject section');
        }
      } catch (error) {
        console.error('Failed to reject section:', error);
        toast({
          title: "Error",
          description: "Failed to reject section",
          variant: "destructive"
        });
      }
    }
  };

  const handleSyncUserStatus = async () => {
    setIsSyncing(true);
    try {
      const response = await fetch('/api/admin/sync-user-status.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({
          user_id: userId
        })
      });

      const data = await response.json();
      if (data.success) {
        toast({
          title: "Status Synchronized",
          description: "User verification status has been synchronized successfully",
          variant: "default"
        });
        fetchUserDetails(); // Refresh data
      } else {
        throw new Error(data.message || 'Sync failed');
      }
    } catch (error) {
      console.error('Failed to sync user status:', error);
      toast({
        title: "Error",
        description: "Failed to sync user status",
        variant: "destructive"
      });
    } finally {
      setIsSyncing(false);
    }
  };

  const handleCompleteKYC = async () => {
    try {
      const response = await fetch(`${ApiConfig.endpoints.admin.kycManagement}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({
          action: 'complete_kyc_verification',
          user_id: userId
        })
      });

      const data = await response.json();
      if (data.success) {
        toast({
          title: "KYC Completed",
          description: "User KYC verification has been completed successfully",
          variant: "default"
        });
        fetchUserDetails(); // Refresh data
      } else {
        throw new Error(data.message || 'Failed to complete KYC');
      }
    } catch (error) {
      console.error('Failed to complete KYC:', error);
      toast({
        title: "Error",
        description: "Failed to complete KYC verification",
        variant: "destructive"
      });
    }
  };

  if (isLoading) {
    return (
      <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <Card className="bg-gray-800 border-gray-700 w-full max-w-4xl mx-4 max-h-[90vh] overflow-y-auto">
          <CardContent className="flex items-center justify-center h-64">
            <div className="text-white">Loading user details...</div>
          </CardContent>
        </Card>
      </div>
    );
  }

  return (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <Card className="bg-gray-800 border-gray-700 w-full max-w-6xl max-h-[90vh] overflow-y-auto">
        <CardHeader className="flex flex-row items-center justify-between">
          <CardTitle className="text-white flex items-center gap-2">
            <User className="h-5 w-5 text-gold" />
            User Details - {userProfile?.full_name || userProfile?.username}
          </CardTitle>
          <Button
            variant="ghost"
            size="sm"
            onClick={onClose}
            className="text-gray-400 hover:text-white"
          >
            <X className="h-4 w-4" />
          </Button>
        </CardHeader>
        <CardContent className="space-y-6">
          {userProfile && (
            <Tabs value={activeTab} onValueChange={setActiveTab} className="w-full">
              <TabsList className="grid w-full grid-cols-6 bg-gray-700">
                <TabsTrigger value="profile" className="data-[state=active]:bg-gold-gradient data-[state=active]:text-black">
                  <User className="h-4 w-4 mr-2" />
                  Profile
                </TabsTrigger>
                <TabsTrigger value="kyc-profile" className="data-[state=active]:bg-gold-gradient data-[state=active]:text-black">
                  <Shield className="h-4 w-4 mr-2" />
                  KYC Profile
                </TabsTrigger>
                <TabsTrigger value="documents" className="data-[state=active]:bg-gold-gradient data-[state=active]:text-black">
                  <FileText className="h-4 w-4 mr-2" />
                  Documents
                </TabsTrigger>
                <TabsTrigger value="facial" className="data-[state=active]:bg-gold-gradient data-[state=active]:text-black">
                  <Camera className="h-4 w-4 mr-2" />
                  Facial
                </TabsTrigger>
                <TabsTrigger value="timeline" className="data-[state=active]:bg-gold-gradient data-[state=active]:text-black">
                  <History className="h-4 w-4 mr-2" />
                  Timeline
                </TabsTrigger>
                <TabsTrigger value="audit" className="data-[state=active]:bg-gold-gradient data-[state=active]:text-black">
                  <Shield className="h-4 w-4 mr-2" />
                  Audit
                </TabsTrigger>
              </TabsList>

              {/* Profile Tab */}
              <TabsContent value="profile" className="space-y-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  {/* Basic Information */}
                  <Card className="bg-gray-700 border-gray-600">
                    <CardHeader>
                      <CardTitle className="text-white text-lg flex items-center gap-2">
                        <User className="h-4 w-4 text-blue-400" />
                        Basic Information
                      </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                      <div className="grid grid-cols-1 gap-3 text-sm">
                        <div className="flex items-center gap-2">
                          <User className="h-4 w-4 text-gray-400" />
                          <span className="text-gray-400">Username:</span>
                          <span className="text-white font-medium">{userProfile.username}</span>
                        </div>
                        <div className="flex items-center gap-2">
                          <Mail className="h-4 w-4 text-gray-400" />
                          <span className="text-gray-400">Email:</span>
                          <span className="text-white">{userProfile.email}</span>
                          {userProfile.email_verified && (
                            <Badge className="bg-green-500/20 text-green-400 text-xs">Verified</Badge>
                          )}
                        </div>
                        <div className="flex items-center gap-2">
                          <User className="h-4 w-4 text-gray-400" />
                          <span className="text-gray-400">Full Name:</span>
                          <span className="text-white">{userProfile.full_name || 'Not provided'}</span>
                        </div>
                        <div className="flex items-center gap-2">
                          <Phone className="h-4 w-4 text-gray-400" />
                          <span className="text-gray-400">Phone:</span>
                          <span className="text-white">{userProfile.phone || 'Not provided'}</span>
                        </div>
                        <div className="flex items-center gap-2">
                          <Calendar className="h-4 w-4 text-gray-400" />
                          <span className="text-gray-400">Date of Birth:</span>
                          <span className="text-white">
                            {userProfile.date_of_birth ? new Date(userProfile.date_of_birth).toLocaleDateString() : 'Not provided'}
                          </span>
                        </div>
                      </div>
                    </CardContent>
                  </Card>

                  {/* Location & Contact */}
                  <Card className="bg-gray-700 border-gray-600">
                    <CardHeader>
                      <CardTitle className="text-white text-lg flex items-center gap-2">
                        <MapPin className="h-4 w-4 text-green-400" />
                        Location & Contact
                      </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                      <div className="grid grid-cols-1 gap-3 text-sm">
                        <div className="flex items-center gap-2">
                          <Globe className="h-4 w-4 text-gray-400" />
                          <span className="text-gray-400">Country:</span>
                          <span className="text-white">{userProfile.country || 'Not provided'}</span>
                        </div>
                        <div className="flex items-center gap-2">
                          <MapPin className="h-4 w-4 text-gray-400" />
                          <span className="text-gray-400">City:</span>
                          <span className="text-white">{userProfile.city || 'Not provided'}</span>
                        </div>
                        <div className="flex items-center gap-2">
                          <Phone className="h-4 w-4 text-gray-400" />
                          <span className="text-gray-400">WhatsApp:</span>
                          <span className="text-white">{userProfile.whatsapp_number || 'Not provided'}</span>
                        </div>
                        <div className="flex items-center gap-2">
                          <User className="h-4 w-4 text-gray-400" />
                          <span className="text-gray-400">Telegram:</span>
                          <span className="text-white">{userProfile.telegram_username || 'Not provided'}</span>
                        </div>
                      </div>
                    </CardContent>
                  </Card>

                  {/* Account Status */}
                  <Card className="bg-gray-700 border-gray-600">
                    <CardHeader>
                      <CardTitle className="text-white text-lg flex items-center gap-2">
                        <Shield className="h-4 w-4 text-yellow-400" />
                        Account Status
                      </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                      <div className="grid grid-cols-1 gap-3 text-sm">
                        <div className="flex items-center gap-2">
                          <Activity className="h-4 w-4 text-gray-400" />
                          <span className="text-gray-400">Account Status:</span>
                          <Badge className={userProfile.is_active ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400'}>
                            {userProfile.is_active ? 'Active' : 'Inactive'}
                          </Badge>
                        </div>
                        <div className="flex items-center gap-2">
                          <Shield className="h-4 w-4 text-gray-400" />
                          <span className="text-gray-400">KYC Status:</span>
                          <Badge className={`${getStatusColor(userProfile.kyc_status)}`}>
                            {getStatusIcon(userProfile.kyc_status)}
                            <span className="ml-1 capitalize">{userProfile.kyc_status}</span>
                          </Badge>
                        </div>
                        <div className="flex items-center gap-2">
                          <Camera className="h-4 w-4 text-gray-400" />
                          <span className="text-gray-400">Facial Verification:</span>
                          <Badge className={`${getStatusColor(userProfile.facial_verification_status)}`}>
                            {getStatusIcon(userProfile.facial_verification_status)}
                            <span className="ml-1 capitalize">{userProfile.facial_verification_status}</span>
                          </Badge>
                        </div>
                        <div className="flex items-center gap-2">
                          <Percent className="h-4 w-4 text-gray-400" />
                          <span className="text-gray-400">Profile Completion:</span>
                          <span className="text-white">{userProfile.profile_completion || 0}%</span>
                        </div>
                      </div>
                    </CardContent>
                  </Card>

                  {/* Social Media */}
                  <Card className="bg-gray-700 border-gray-600">
                    <CardHeader>
                      <CardTitle className="text-white text-lg flex items-center gap-2">
                        <Globe className="h-4 w-4 text-purple-400" />
                        Social Media
                      </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                      <div className="grid grid-cols-1 gap-3 text-sm">
                        <div className="flex items-center gap-2">
                          <span className="text-gray-400">Twitter:</span>
                          <span className="text-white">{userProfile.twitter_handle || 'Not provided'}</span>
                        </div>
                        <div className="flex items-center gap-2">
                          <span className="text-gray-400">Instagram:</span>
                          <span className="text-white">{userProfile.instagram_handle || 'Not provided'}</span>
                        </div>
                        <div className="flex items-center gap-2">
                          <span className="text-gray-400">LinkedIn:</span>
                          <span className="text-white">{userProfile.linkedin_profile || 'Not provided'}</span>
                        </div>
                        <div className="flex items-center gap-2">
                          <span className="text-gray-400">Facebook:</span>
                          <span className="text-white">{userProfile.facebook_profile || 'Not provided'}</span>
                        </div>
                      </div>
                      {userProfile.bio && (
                        <div className="mt-4">
                          <span className="text-gray-400 text-sm">Bio:</span>
                          <p className="text-white text-sm mt-1 p-2 bg-gray-800 rounded">{userProfile.bio}</p>
                        </div>
                      )}
                    </CardContent>
                  </Card>
                </div>
              </TabsContent>

              {/* Enhanced KYC Profile Tab */}
              <TabsContent value="kyc-profile" className="space-y-6">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  {/* Personal Information */}
                  <Card className="bg-gray-700 border-gray-600">
                    <CardHeader className="flex flex-row items-center justify-between">
                      <CardTitle className="text-white text-lg flex items-center gap-2">
                        <User className="h-4 w-4 text-blue-400" />
                        Personal Information
                      </CardTitle>
                      <div className="flex items-center gap-2">
                        <Badge className={`${getStatusColor(userProfile.personal_info_status || 'pending')}`}>
                          {getStatusIcon(userProfile.personal_info_status || 'pending')}
                          <span className="ml-1 capitalize">{userProfile.personal_info_status || 'pending'}</span>
                        </Badge>
                        {userProfile.personal_info_status === 'pending' && (
                          <div className="flex gap-1">
                            <Button
                              size="sm"
                              className="bg-green-600 hover:bg-green-700 text-white px-2 py-1 h-7"
                              onClick={() => handleSectionApprove('personal_info')}
                            >
                              <CheckCircle className="h-3 w-3" />
                            </Button>
                            <Button
                              size="sm"
                              variant="destructive"
                              className="px-2 py-1 h-7"
                              onClick={() => setSelectedSection('personal_info')}
                            >
                              <XCircle className="h-3 w-3" />
                            </Button>
                          </div>
                        )}
                      </div>
                    </CardHeader>
                    <CardContent className="space-y-3">
                      <div className="grid grid-cols-1 gap-2 text-sm">
                        <div className="flex justify-between">
                          <span className="text-gray-400">First Name:</span>
                          <span className="text-white">{userProfile.first_name || 'Not provided'}</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-gray-400">Middle Name:</span>
                          <span className="text-white">{userProfile.middle_name || 'Not provided'}</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-gray-400">Last Name:</span>
                          <span className="text-white">{userProfile.last_name || 'Not provided'}</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-gray-400">Gender:</span>
                          <span className="text-white">{userProfile.gender || 'Not provided'}</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-gray-400">Nationality:</span>
                          <span className="text-white">{userProfile.nationality || 'Not provided'}</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-gray-400">Place of Birth:</span>
                          <span className="text-white">{userProfile.place_of_birth || 'Not provided'}</span>
                        </div>
                      </div>
                      {userProfile.personal_info_rejection_reason && (
                        <div className="bg-red-500/10 border border-red-500/30 rounded p-2 mt-3">
                          <p className="text-red-400 text-xs">
                            <strong>Rejection Reason:</strong> {userProfile.personal_info_rejection_reason}
                          </p>
                        </div>
                      )}
                    </CardContent>
                  </Card>

                  {/* Contact Information */}
                  <Card className="bg-gray-700 border-gray-600">
                    <CardHeader className="flex flex-row items-center justify-between">
                      <CardTitle className="text-white text-lg flex items-center gap-2">
                        <Phone className="h-4 w-4 text-green-400" />
                        Contact Information
                      </CardTitle>
                      <div className="flex items-center gap-2">
                        <Badge className={`${getStatusColor(userProfile.contact_info_status || 'pending')}`}>
                          {getStatusIcon(userProfile.contact_info_status || 'pending')}
                          <span className="ml-1 capitalize">{userProfile.contact_info_status || 'pending'}</span>
                        </Badge>
                        {userProfile.contact_info_status === 'pending' && (
                          <div className="flex gap-1">
                            <Button
                              size="sm"
                              className="bg-green-600 hover:bg-green-700 text-white px-2 py-1 h-7"
                              onClick={() => handleSectionApprove('contact_info')}
                            >
                              <CheckCircle className="h-3 w-3" />
                            </Button>
                            <Button
                              size="sm"
                              variant="destructive"
                              className="px-2 py-1 h-7"
                              onClick={() => setSelectedSection('contact_info')}
                            >
                              <XCircle className="h-3 w-3" />
                            </Button>
                          </div>
                        )}
                      </div>
                    </CardHeader>
                    <CardContent className="space-y-3">
                      <div className="grid grid-cols-1 gap-2 text-sm">
                        <div className="flex justify-between">
                          <span className="text-gray-400">Email:</span>
                          <span className="text-white">{userProfile.email}</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-gray-400">Phone:</span>
                          <span className="text-white">{userProfile.phone || 'Not provided'}</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-gray-400">WhatsApp:</span>
                          <span className="text-white">{userProfile.whatsapp_number || 'Not provided'}</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-gray-400">Telegram:</span>
                          <span className="text-white">{userProfile.telegram_username || 'Not provided'}</span>
                        </div>
                      </div>
                      {userProfile.contact_info_rejection_reason && (
                        <div className="bg-red-500/10 border border-red-500/30 rounded p-2 mt-3">
                          <p className="text-red-400 text-xs">
                            <strong>Rejection Reason:</strong> {userProfile.contact_info_rejection_reason}
                          </p>
                        </div>
                      )}
                    </CardContent>
                  </Card>

                  {/* Address Information */}
                  <Card className="bg-gray-700 border-gray-600">
                    <CardHeader className="flex flex-row items-center justify-between">
                      <CardTitle className="text-white text-lg flex items-center gap-2">
                        <MapPin className="h-4 w-4 text-purple-400" />
                        Address Information
                      </CardTitle>
                      <div className="flex items-center gap-2">
                        <Badge className={`${getStatusColor(userProfile.address_info_status || 'pending')}`}>
                          {getStatusIcon(userProfile.address_info_status || 'pending')}
                          <span className="ml-1 capitalize">{userProfile.address_info_status || 'pending'}</span>
                        </Badge>
                        {userProfile.address_info_status === 'pending' && (
                          <div className="flex gap-1">
                            <Button
                              size="sm"
                              className="bg-green-600 hover:bg-green-700 text-white px-2 py-1 h-7"
                              onClick={() => handleSectionApprove('address_info')}
                            >
                              <CheckCircle className="h-3 w-3" />
                            </Button>
                            <Button
                              size="sm"
                              variant="destructive"
                              className="px-2 py-1 h-7"
                              onClick={() => setSelectedSection('address_info')}
                            >
                              <XCircle className="h-3 w-3" />
                            </Button>
                          </div>
                        )}
                      </div>
                    </CardHeader>
                    <CardContent className="space-y-3">
                      <div className="grid grid-cols-1 gap-2 text-sm">
                        <div className="flex justify-between">
                          <span className="text-gray-400">Address Line 1:</span>
                          <span className="text-white">{userProfile.address_line_1 || 'Not provided'}</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-gray-400">Address Line 2:</span>
                          <span className="text-white">{userProfile.address_line_2 || 'Not provided'}</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-gray-400">City:</span>
                          <span className="text-white">{userProfile.city || 'Not provided'}</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-gray-400">State/Province:</span>
                          <span className="text-white">{userProfile.state_province || 'Not provided'}</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-gray-400">Postal Code:</span>
                          <span className="text-white">{userProfile.postal_code || 'Not provided'}</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-gray-400">Country:</span>
                          <span className="text-white">{userProfile.country || 'Not provided'}</span>
                        </div>
                      </div>
                      {userProfile.address_info_rejection_reason && (
                        <div className="bg-red-500/10 border border-red-500/30 rounded p-2 mt-3">
                          <p className="text-red-400 text-xs">
                            <strong>Rejection Reason:</strong> {userProfile.address_info_rejection_reason}
                          </p>
                        </div>
                      )}
                    </CardContent>
                  </Card>
                  {/* Identity Information */}
                  <Card className="bg-gray-700 border-gray-600">
                    <CardHeader className="flex flex-row items-center justify-between">
                      <CardTitle className="text-white text-lg flex items-center gap-2">
                        <FileText className="h-4 w-4 text-yellow-400" />
                        Identity Information
                      </CardTitle>
                      <div className="flex items-center gap-2">
                        <Badge className={`${getStatusColor(userProfile.identity_info_status || 'pending')}`}>
                          {getStatusIcon(userProfile.identity_info_status || 'pending')}
                          <span className="ml-1 capitalize">{userProfile.identity_info_status || 'pending'}</span>
                        </Badge>
                        {userProfile.identity_info_status === 'pending' && (
                          <div className="flex gap-1">
                            <Button
                              size="sm"
                              className="bg-green-600 hover:bg-green-700 text-white px-2 py-1 h-7"
                              onClick={() => handleSectionApprove('identity_info')}
                            >
                              <CheckCircle className="h-3 w-3" />
                            </Button>
                            <Button
                              size="sm"
                              variant="destructive"
                              className="px-2 py-1 h-7"
                              onClick={() => setSelectedSection('identity_info')}
                            >
                              <XCircle className="h-3 w-3" />
                            </Button>
                          </div>
                        )}
                      </div>
                    </CardHeader>
                    <CardContent className="space-y-3">
                      <div className="grid grid-cols-1 gap-2 text-sm">
                        <div className="flex justify-between">
                          <span className="text-gray-400">ID Type:</span>
                          <span className="text-white">{userProfile.id_type || 'Not provided'}</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-gray-400">ID Number:</span>
                          <span className="text-white">{userProfile.id_number || 'Not provided'}</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-gray-400">ID Expiry Date:</span>
                          <span className="text-white">
                            {userProfile.id_expiry_date ? new Date(userProfile.id_expiry_date).toLocaleDateString() : 'Not provided'}
                          </span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-gray-400">Date of Birth:</span>
                          <span className="text-white">
                            {userProfile.date_of_birth ? new Date(userProfile.date_of_birth).toLocaleDateString() : 'Not provided'}
                          </span>
                        </div>
                      </div>
                      {userProfile.identity_info_rejection_reason && (
                        <div className="bg-red-500/10 border border-red-500/30 rounded p-2 mt-3">
                          <p className="text-red-400 text-xs">
                            <strong>Rejection Reason:</strong> {userProfile.identity_info_rejection_reason}
                          </p>
                        </div>
                      )}
                    </CardContent>
                  </Card>

                  {/* Financial Information */}
                  <Card className="bg-gray-700 border-gray-600">
                    <CardHeader className="flex flex-row items-center justify-between">
                      <CardTitle className="text-white text-lg flex items-center gap-2">
                        <Star className="h-4 w-4 text-orange-400" />
                        Financial Information
                      </CardTitle>
                      <div className="flex items-center gap-2">
                        <Badge className={`${getStatusColor(userProfile.financial_info_status || 'pending')}`}>
                          {getStatusIcon(userProfile.financial_info_status || 'pending')}
                          <span className="ml-1 capitalize">{userProfile.financial_info_status || 'pending'}</span>
                        </Badge>
                        {userProfile.financial_info_status === 'pending' && (
                          <div className="flex gap-1">
                            <Button
                              size="sm"
                              className="bg-green-600 hover:bg-green-700 text-white px-2 py-1 h-7"
                              onClick={() => handleSectionApprove('financial_info')}
                            >
                              <CheckCircle className="h-3 w-3" />
                            </Button>
                            <Button
                              size="sm"
                              variant="destructive"
                              className="px-2 py-1 h-7"
                              onClick={() => setSelectedSection('financial_info')}
                            >
                              <XCircle className="h-3 w-3" />
                            </Button>
                          </div>
                        )}
                      </div>
                    </CardHeader>
                    <CardContent className="space-y-3">
                      <div className="grid grid-cols-1 gap-2 text-sm">
                        <div className="flex justify-between">
                          <span className="text-gray-400">Occupation:</span>
                          <span className="text-white">{userProfile.occupation || 'Not provided'}</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-gray-400">Employer:</span>
                          <span className="text-white">{userProfile.employer || 'Not provided'}</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-gray-400">Annual Income:</span>
                          <span className="text-white">{userProfile.annual_income || 'Not provided'}</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-gray-400">Source of Funds:</span>
                          <span className="text-white">{userProfile.source_of_funds || 'Not provided'}</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-gray-400">Purpose of Account:</span>
                          <span className="text-white">{userProfile.purpose_of_account || 'Not provided'}</span>
                        </div>
                      </div>
                      {userProfile.financial_info_rejection_reason && (
                        <div className="bg-red-500/10 border border-red-500/30 rounded p-2 mt-3">
                          <p className="text-red-400 text-xs">
                            <strong>Rejection Reason:</strong> {userProfile.financial_info_rejection_reason}
                          </p>
                        </div>
                      )}
                    </CardContent>
                  </Card>

                  {/* Emergency Contact */}
                  <Card className="bg-gray-700 border-gray-600">
                    <CardHeader className="flex flex-row items-center justify-between">
                      <CardTitle className="text-white text-lg flex items-center gap-2">
                        <Phone className="h-4 w-4 text-red-400" />
                        Emergency Contact
                      </CardTitle>
                      <div className="flex items-center gap-2">
                        <Badge className={`${getStatusColor(userProfile.emergency_contact_status || 'pending')}`}>
                          {getStatusIcon(userProfile.emergency_contact_status || 'pending')}
                          <span className="ml-1 capitalize">{userProfile.emergency_contact_status || 'pending'}</span>
                        </Badge>
                        {userProfile.emergency_contact_status === 'pending' && (
                          <div className="flex gap-1">
                            <Button
                              size="sm"
                              className="bg-green-600 hover:bg-green-700 text-white px-2 py-1 h-7"
                              onClick={() => handleSectionApprove('emergency_contact')}
                            >
                              <CheckCircle className="h-3 w-3" />
                            </Button>
                            <Button
                              size="sm"
                              variant="destructive"
                              className="px-2 py-1 h-7"
                              onClick={() => setSelectedSection('emergency_contact')}
                            >
                              <XCircle className="h-3 w-3" />
                            </Button>
                          </div>
                        )}
                      </div>
                    </CardHeader>
                    <CardContent className="space-y-3">
                      <div className="grid grid-cols-1 gap-2 text-sm">
                        <div className="flex justify-between">
                          <span className="text-gray-400">Contact Name:</span>
                          <span className="text-white">{userProfile.emergency_contact_name || 'Not provided'}</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-gray-400">Contact Phone:</span>
                          <span className="text-white">{userProfile.emergency_contact_phone || 'Not provided'}</span>
                        </div>
                        <div className="flex justify-between">
                          <span className="text-gray-400">Relationship:</span>
                          <span className="text-white">{userProfile.emergency_contact_relationship || 'Not provided'}</span>
                        </div>
                      </div>
                      {userProfile.emergency_contact_rejection_reason && (
                        <div className="bg-red-500/10 border border-red-500/30 rounded p-2 mt-3">
                          <p className="text-red-400 text-xs">
                            <strong>Rejection Reason:</strong> {userProfile.emergency_contact_rejection_reason}
                          </p>
                        </div>
                      )}
                    </CardContent>
                  </Card>
                </div>
              </TabsContent>

              {/* Documents Tab */}
              <TabsContent value="documents" className="space-y-6">
                <Card className="bg-gray-700 border-gray-600">
                  <CardHeader>
                    <CardTitle className="text-white text-lg flex items-center gap-2">
                      <FileText className="h-4 w-4 text-yellow-400" />
                      KYC Documents ({kycDocuments.length})
                    </CardTitle>
                  </CardHeader>
                  <CardContent>
                    {kycDocuments.length === 0 ? (
                      <div className="text-center py-8">
                        <FileText className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                        <p className="text-gray-400">No documents uploaded</p>
                      </div>
                    ) : (
                      <div className="space-y-4">
                        {kycDocuments.map((doc) => (
                          <div key={doc.id} className="border border-gray-600 rounded-lg p-4">
                            <div className="flex items-start justify-between">
                              <div className="flex-1">
                                <div className="flex items-center gap-2 mb-3">
                                  <Badge className={getStatusColor(doc.status)}>
                                    {getStatusIcon(doc.status)}
                                    <span className="ml-1 capitalize">{doc.status}</span>
                                  </Badge>
                                  <span className="text-gray-400 text-sm font-medium">
                                    {doc.type.replace('_', ' ').toUpperCase()}
                                  </span>
                                </div>

                                <div className="grid grid-cols-2 gap-4 text-sm mb-3">
                                  <div>
                                    <span className="text-gray-400">Filename:</span>
                                    <p className="text-white">{doc.original_name || doc.filename}</p>
                                  </div>
                                  <div>
                                    <span className="text-gray-400">Upload Date:</span>
                                    <p className="text-white">{new Date(doc.upload_date).toLocaleDateString()}</p>
                                  </div>
                                  <div>
                                    <span className="text-gray-400">File Path:</span>
                                    <p className="text-white text-xs">{doc.file_path}</p>
                                  </div>
                                  {doc.reviewed_at && (
                                    <div>
                                      <span className="text-gray-400">Reviewed:</span>
                                      <p className="text-white">{new Date(doc.reviewed_at).toLocaleDateString()}</p>
                                    </div>
                                  )}
                                </div>

                                {doc.rejection_reason && (
                                  <div className="bg-red-500/10 border border-red-500/30 rounded p-3 mb-3">
                                    <p className="text-red-400 text-sm">
                                      <strong>Rejection Reason:</strong> {doc.rejection_reason}
                                    </p>
                                  </div>
                                )}
                              </div>

                              <div className="flex flex-col gap-2 ml-4">
                                <Button
                                  size="sm"
                                  variant="outline"
                                  onClick={() => window.open(`/api/kyc/serve-document.php?id=${doc.id}`, '_blank')}
                                  className="border-gray-600 text-gray-300 hover:bg-gray-700"
                                >
                                  <Eye className="h-4 w-4 mr-1" />
                                  View
                                </Button>
                                {doc.status === 'pending' && onApprove && onReject && (
                                  <>
                                    <Button
                                      size="sm"
                                      onClick={() => handleDocumentApprove(doc.id)}
                                      className="bg-green-600 hover:bg-green-700 text-white"
                                    >
                                      <CheckCircle className="h-4 w-4 mr-1" />
                                      Approve
                                    </Button>
                                    <Button
                                      size="sm"
                                      variant="outline"
                                      onClick={() => setSelectedDocumentId(doc.id)}
                                      className="border-red-600 text-red-400 hover:bg-red-900/20"
                                    >
                                      <XCircle className="h-4 w-4 mr-1" />
                                      Reject
                                    </Button>
                                  </>
                                )}
                              </div>
                            </div>
                          </div>
                        ))}
                      </div>
                    )}
                  </CardContent>
                </Card>
              </TabsContent>

              {/* Facial Verification Tab */}
              <TabsContent value="facial" className="space-y-6">
                <Card className="bg-gray-700 border-gray-600">
                  <CardHeader>
                    <CardTitle className="text-white text-lg flex items-center gap-2">
                      <Camera className="h-4 w-4 text-green-400" />
                      Facial Verification History ({facialVerifications.length})
                    </CardTitle>
                  </CardHeader>
                  <CardContent>
                    {facialVerifications.length === 0 ? (
                      <div className="text-center py-8">
                        <Camera className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                        <p className="text-gray-400">No facial verification attempts</p>
                      </div>
                    ) : (
                      <div className="space-y-6">
                        {facialVerifications.map((verification, index) => (
                          <div key={verification.id} className="border border-gray-600 rounded-lg p-4">
                            <div className="flex items-start gap-4">
                              <div className="flex-shrink-0">
                                <img
                                  src={`http://localhost/aureus-angel-alliance/${verification.captured_image_path}`}
                                  alt={`Facial verification ${index + 1}`}
                                  className="w-32 h-32 object-cover rounded-lg border border-gray-600"
                                  onError={(e) => {
                                    e.currentTarget.src = '/placeholder-avatar.png';
                                  }}
                                />
                              </div>
                              <div className="flex-1">
                                <div className="flex items-center gap-2 mb-3">
                                  <Badge className={`${getStatusColor(verification.verification_status)}`}>
                                    {getStatusIcon(verification.verification_status)}
                                    <span className="ml-1 capitalize">{verification.verification_status}</span>
                                  </Badge>
                                  <span className="text-gray-400 text-sm">
                                    Attempt #{facialVerifications.length - index}
                                  </span>
                                </div>

                                <div className="grid grid-cols-2 gap-4 text-sm">
                                  <div>
                                    <span className="text-gray-400">Confidence Score:</span>
                                    <div className="flex items-center gap-2">
                                      <span className="text-white font-medium">
                                        {(verification.confidence_score * 100).toFixed(1)}%
                                      </span>
                                      {verification.confidence_score >= 0.6 ? (
                                        <CheckCircle className="h-4 w-4 text-green-400" />
                                      ) : (
                                        <XCircle className="h-4 w-4 text-red-400" />
                                      )}
                                    </div>
                                  </div>
                                  <div>
                                    <span className="text-gray-400">Liveness Score:</span>
                                    <span className="text-white font-medium">
                                      {(verification.liveness_score * 100).toFixed(1)}%
                                    </span>
                                  </div>
                                  <div>
                                    <span className="text-gray-400">Created:</span>
                                    <span className="text-white">
                                      {new Date(verification.created_at).toLocaleString()}
                                    </span>
                                  </div>
                                  {verification.verified_at && (
                                    <div>
                                      <span className="text-gray-400">Verified:</span>
                                      <span className="text-white">
                                        {new Date(verification.verified_at).toLocaleString()}
                                      </span>
                                    </div>
                                  )}
                                </div>

                                {verification.comparison_result && (
                                  <div className="mt-3 p-3 bg-gray-800 rounded">
                                    <span className="text-gray-400 text-sm">Comparison Result:</span>
                                    <pre className="text-white text-xs mt-1 overflow-x-auto">
                                      {JSON.stringify(JSON.parse(verification.comparison_result), null, 2)}
                                    </pre>
                                  </div>
                                )}
                              </div>
                            </div>
                          </div>
                        ))}
                      </div>
                    )}
                  </CardContent>
                </Card>
              </TabsContent>

              {/* Timeline Tab */}
              <TabsContent value="timeline" className="space-y-6">
                <Card className="bg-gray-700 border-gray-600">
                  <CardHeader>
                    <CardTitle className="text-white text-lg flex items-center gap-2">
                      <History className="h-4 w-4 text-purple-400" />
                      KYC Verification Timeline
                    </CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div className="space-y-4">
                      {/* Account Creation */}
                      <div className="flex items-start gap-4">
                        <div className="flex-shrink-0 w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                          <User className="h-4 w-4 text-white" />
                        </div>
                        <div className="flex-1">
                          <h4 className="text-white font-medium">Account Created</h4>
                          <p className="text-gray-400 text-sm">User registered on the platform</p>
                          <p className="text-gray-500 text-xs">{new Date(userProfile.created_at).toLocaleString()}</p>
                        </div>
                      </div>

                      {/* KYC Documents */}
                      {kycDocuments.map((doc, index) => (
                        <div key={doc.id} className="flex items-start gap-4">
                          <div className={`flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center ${
                            doc.status === 'approved' ? 'bg-green-500' :
                            doc.status === 'rejected' ? 'bg-red-500' : 'bg-yellow-500'
                          }`}>
                            <FileText className="h-4 w-4 text-white" />
                          </div>
                          <div className="flex-1">
                            <h4 className="text-white font-medium">
                              {doc.type.replace('_', ' ').toUpperCase()} Document {doc.status === 'approved' ? 'Approved' : doc.status === 'rejected' ? 'Rejected' : 'Uploaded'}
                            </h4>
                            <p className="text-gray-400 text-sm">
                              {doc.status === 'approved' ? 'Document verified and approved' :
                               doc.status === 'rejected' ? `Document rejected: ${doc.rejection_reason}` :
                               'Document uploaded and pending review'}
                            </p>
                            <p className="text-gray-500 text-xs">
                              {doc.reviewed_at ? new Date(doc.reviewed_at).toLocaleString() : new Date(doc.upload_date).toLocaleString()}
                            </p>
                          </div>
                        </div>
                      ))}

                      {/* Facial Verifications */}
                      {facialVerifications.slice(0, 3).map((verification, index) => (
                        <div key={verification.id} className="flex items-start gap-4">
                          <div className={`flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center ${
                            verification.verification_status === 'verified' ? 'bg-green-500' :
                            verification.verification_status === 'failed' ? 'bg-red-500' : 'bg-yellow-500'
                          }`}>
                            <Camera className="h-4 w-4 text-white" />
                          </div>
                          <div className="flex-1">
                            <h4 className="text-white font-medium">
                              Facial Verification {verification.verification_status === 'verified' ? 'Passed' : verification.verification_status === 'failed' ? 'Failed' : 'Attempted'}
                            </h4>
                            <p className="text-gray-400 text-sm">
                              Confidence: {(verification.confidence_score * 100).toFixed(1)}%,
                              Liveness: {(verification.liveness_score * 100).toFixed(1)}%
                            </p>
                            <p className="text-gray-500 text-xs">{new Date(verification.created_at).toLocaleString()}</p>
                          </div>
                        </div>
                      ))}

                      {/* KYC Status Updates */}
                      {userProfile.kyc_verified_at && (
                        <div className="flex items-start gap-4">
                          <div className="flex-shrink-0 w-8 h-8 bg-gold rounded-full flex items-center justify-center">
                            <Shield className="h-4 w-4 text-black" />
                          </div>
                          <div className="flex-1">
                            <h4 className="text-white font-medium">KYC Verification Complete</h4>
                            <p className="text-gray-400 text-sm">User successfully completed KYC verification</p>
                            <p className="text-gray-500 text-xs">{new Date(userProfile.kyc_verified_at).toLocaleString()}</p>
                          </div>
                        </div>
                      )}
                    </div>
                  </CardContent>
                </Card>
              </TabsContent>

              {/* Audit Tab */}
              <TabsContent value="audit" className="space-y-6">
                {/* KYC Section Audit Log */}
                <Card className="bg-gray-700 border-gray-600">
                  <CardHeader>
                    <CardTitle className="text-white text-lg flex items-center gap-2">
                      <Shield className="h-4 w-4 text-blue-400" />
                      KYC Section Audit Log
                    </CardTitle>
                  </CardHeader>
                  <CardContent>
                    {kycSectionAuditLogs.length === 0 ? (
                      <div className="text-center py-8">
                        <Shield className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                        <p className="text-gray-400">No KYC section audit logs available</p>
                      </div>
                    ) : (
                      <div className="space-y-3">
                        {kycSectionAuditLogs.map((log) => (
                          <div key={log.id} className="p-4 bg-gray-800 rounded-lg border-l-4 border-l-blue-400">
                            <div className="flex items-center justify-between mb-2">
                              <div className="flex items-center gap-3">
                                <div className={`w-3 h-3 rounded-full ${
                                  log.action === 'approved' ? 'bg-green-400' : 'bg-red-400'
                                }`}></div>
                                <div>
                                  <p className="text-white font-medium">
                                    {log.section.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())} Section {log.action.charAt(0).toUpperCase() + log.action.slice(1)}
                                  </p>
                                  <p className="text-gray-400 text-sm">
                                    by Admin: {log.admin_username || log.admin_id}
                                  </p>
                                </div>
                              </div>
                              <div className="text-right">
                                <p className="text-gray-400 text-xs">
                                  {new Date(log.created_at).toLocaleString()}
                                </p>
                              </div>
                            </div>
                            {log.rejection_reason && (
                              <div className="mt-2 p-2 bg-red-500/10 border border-red-500/30 rounded">
                                <p className="text-red-400 text-sm">
                                  <strong>Reason:</strong> {log.rejection_reason}
                                </p>
                              </div>
                            )}
                          </div>
                        ))}
                      </div>
                    )}
                  </CardContent>
                </Card>

                {/* Document Access Audit Log */}
                <Card className="bg-gray-700 border-gray-600">
                  <CardHeader>
                    <CardTitle className="text-white text-lg flex items-center gap-2">
                      <FileText className="h-4 w-4 text-yellow-400" />
                      Document Access Audit Log
                    </CardTitle>
                  </CardHeader>
                  <CardContent>
                    {accessLogs.length === 0 ? (
                      <div className="text-center py-8">
                        <FileText className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                        <p className="text-gray-400">No document access logs available</p>
                      </div>
                    ) : (
                      <div className="space-y-3">
                        {accessLogs.map((log) => (
                          <div key={log.id} className="p-3 bg-gray-800 rounded-lg border-l-4 border-l-yellow-400">
                            <div className="flex items-center justify-between">
                              <div className="flex items-center gap-3">
                                <div className={`w-2 h-2 rounded-full ${
                                  log.access_type === 'admin' ? 'bg-red-400' : 'bg-blue-400'
                                }`}></div>
                                <div>
                                  <p className="text-white text-sm">
                                    {log.document_type?.replace('_', ' ').toUpperCase()} document accessed
                                  </p>
                                  <p className="text-gray-400 text-xs">
                                    by {log.access_type} (ID: {log.accessed_by})
                                  </p>
                                  {log.filename && (
                                    <p className="text-gray-500 text-xs">
                                      File: {log.filename}
                                    </p>
                                  )}
                                </div>
                              </div>
                              <div className="text-right">
                                <p className="text-gray-400 text-xs">
                                  {new Date(log.accessed_at).toLocaleString()}
                                </p>
                              </div>
                            </div>
                          </div>
                        ))}
                      </div>
                    )}
                  </CardContent>
                </Card>

                {/* Account Activity Summary */}
                <Card className="bg-gray-700 border-gray-600">
                  <CardHeader>
                    <CardTitle className="text-white text-lg flex items-center justify-between">
                      <div className="flex items-center gap-2">
                        <Activity className="h-4 w-4 text-green-400" />
                        Account Activity Summary
                      </div>
                      <Button
                        onClick={handleSyncUserStatus}
                        disabled={isSyncing}
                        size="sm"
                        className="bg-blue-600 hover:bg-blue-700 text-white"
                      >
                        <RefreshCw className={`h-4 w-4 mr-2 ${isSyncing ? 'animate-spin' : ''}`} />
                        {isSyncing ? 'Syncing...' : 'Sync Status'}
                      </Button>
                    </CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div className="space-y-3">
                        <div className="flex items-center justify-between p-3 bg-gray-800 rounded">
                          <span className="text-gray-400">Account Created</span>
                          <span className="text-white text-sm">
                            {userProfile?.created_at ? new Date(userProfile.created_at).toLocaleDateString() : 'N/A'}
                          </span>
                        </div>
                        <div className="flex items-center justify-between p-3 bg-gray-800 rounded">
                          <span className="text-gray-400">Last Login</span>
                          <span className="text-white text-sm">
                            {userProfile?.last_login ? new Date(userProfile.last_login).toLocaleDateString() : 'Never'}
                          </span>
                        </div>
                        <div className="flex items-center justify-between p-3 bg-gray-800 rounded">
                          <span className="text-gray-400">Email Verified</span>
                          <Badge className={userProfile?.email_verified ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400'}>
                            {userProfile?.email_verified ? 'Yes' : 'No'}
                          </Badge>
                        </div>
                      </div>
                      <div className="space-y-3">
                        <div className="flex items-center justify-between p-3 bg-gray-800 rounded">
                          <span className="text-gray-400">KYC Verified</span>
                          <span className="text-white text-sm">
                            {userProfile?.kyc_verified_at ? new Date(userProfile.kyc_verified_at).toLocaleDateString() : 'Not verified'}
                          </span>
                        </div>
                        <div className="flex items-center justify-between p-3 bg-gray-800 rounded">
                          <span className="text-gray-400">Facial Verification</span>
                          <span className="text-white text-sm">
                            {userProfile?.facial_verification_at ? new Date(userProfile.facial_verification_at).toLocaleDateString() : 'Not completed'}
                          </span>
                        </div>
                        <div className="flex items-center justify-between p-3 bg-gray-800 rounded">
                          <span className="text-gray-400">Profile Completion</span>
                          <div className="flex items-center gap-2">
                            <div className="w-16 h-2 bg-gray-600 rounded-full">
                              <div
                                className="h-2 bg-gold-gradient rounded-full"
                                style={{ width: `${userProfile?.profile_completion || 0}%` }}
                              ></div>
                            </div>
                            <span className="text-white text-sm">{userProfile?.profile_completion || 0}%</span>
                          </div>
                        </div>
                      </div>
                    </div>

                    {/* KYC Completion Actions */}
                    {userProfile?.kyc_status !== 'verified' && (
                      <div className="mt-6 p-4 bg-gray-800 rounded-lg border border-gray-600">
                        <div className="flex items-center justify-between">
                          <div>
                            <h4 className="text-white font-medium">Complete KYC Verification</h4>
                            <p className="text-gray-400 text-sm mt-1">
                              Manually complete the user's KYC verification process
                            </p>
                          </div>
                          <Button
                            onClick={handleCompleteKYC}
                            className="bg-green-600 hover:bg-green-700 text-white"
                          >
                            <CheckCircle className="h-4 w-4 mr-2" />
                            Complete KYC
                          </Button>
                        </div>
                      </div>
                    )}
                  </CardContent>
                </Card>
              </TabsContent>
            </Tabs>
          )}
        </CardContent>
      </Card>

      {/* Rejection Modal */}
      {selectedDocumentId && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-60">
          <Card className="bg-gray-800 border-gray-700 w-full max-w-md mx-4">
            <CardHeader>
              <CardTitle className="text-white">Reject Document</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <textarea
                placeholder="Please provide a reason for rejection..."
                value={rejectionReason}
                onChange={(e) => setRejectionReason(e.target.value)}
                className="w-full bg-gray-700 border border-gray-600 text-white p-3 rounded-lg"
                rows={4}
              />
              <div className="flex items-center gap-2 justify-end">
                <Button
                  variant="outline"
                  onClick={() => {
                    setSelectedDocumentId(null);
                    setRejectionReason('');
                  }}
                  className="border-gray-600 text-gray-300"
                >
                  Cancel
                </Button>
                <Button
                  onClick={handleDocumentReject}
                  disabled={!rejectionReason.trim()}
                  className="bg-red-600 hover:bg-red-700 text-white"
                >
                  Reject Document
                </Button>
              </div>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Section Rejection Modal */}
      {selectedSection && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-60">
          <Card className="bg-gray-800 border-gray-700 w-full max-w-md mx-4">
            <CardHeader>
              <CardTitle className="text-white">
                Reject {selectedSection.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())} Section
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <textarea
                placeholder="Please provide a reason for rejection..."
                value={rejectionReason}
                onChange={(e) => setRejectionReason(e.target.value)}
                className="w-full bg-gray-700 border border-gray-600 text-white p-3 rounded-lg"
                rows={4}
              />
              <div className="flex items-center gap-2 justify-end">
                <Button
                  variant="outline"
                  onClick={() => {
                    setSelectedSection(null);
                    setRejectionReason('');
                  }}
                  className="border-gray-600 text-gray-300"
                >
                  Cancel
                </Button>
                <Button
                  onClick={handleSectionReject}
                  disabled={!rejectionReason.trim()}
                  className="bg-red-600 hover:bg-red-700 text-white"
                >
                  Reject Section
                </Button>
              </div>
            </CardContent>
          </Card>
        </div>
      )}
    </div>
  );
};

export default UserDetailsModal;

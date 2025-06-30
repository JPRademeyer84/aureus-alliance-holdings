import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import { useToast } from '@/hooks/use-toast';
import {
  Loader2,
  Search,
  CheckCircle,
  XCircle,
  Clock,
  User,
  Phone,
  MapPin,
  CreditCard,
  Briefcase,
  Users,
  Eye,
  FileText
} from '@/components/SafeIcons';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';

interface UserKYCData {
  id: string;
  username: string;
  email: string;
  full_name: string;
  
  // Personal Information
  first_name: string;
  last_name: string;
  middle_name: string;
  date_of_birth: string;
  nationality: string;
  gender: string;
  place_of_birth: string;
  
  // Contact Information
  phone: string;
  whatsapp_number: string;
  telegram_username: string;
  twitter_handle: string;
  instagram_handle: string;
  linkedin_profile: string;
  facebook_profile: string;
  
  // Address Information
  address_line_1: string;
  address_line_2: string;
  city: string;
  state_province: string;
  postal_code: string;
  country: string;
  
  // Identity Information
  id_type: string;
  id_number: string;
  id_expiry_date: string;
  
  // Financial Information
  occupation: string;
  employer: string;
  annual_income: string;
  source_of_funds: string;
  purpose_of_account: string;
  
  // Emergency Contact
  emergency_contact_name: string;
  emergency_contact_phone: string;
  emergency_contact_relationship: string;
  
  // Approval Status
  personal_info_status: 'pending' | 'approved' | 'rejected';
  contact_info_status: 'pending' | 'approved' | 'rejected';
  address_info_status: 'pending' | 'approved' | 'rejected';
  identity_info_status: 'pending' | 'approved' | 'rejected';
  financial_info_status: 'pending' | 'approved' | 'rejected';
  emergency_contact_status: 'pending' | 'approved' | 'rejected';
  
  personal_info_rejection_reason?: string;
  contact_info_rejection_reason?: string;
  address_info_rejection_reason?: string;
  identity_info_rejection_reason?: string;
  financial_info_rejection_reason?: string;
  emergency_contact_rejection_reason?: string;
}

const StatusIcon = ({ status }: { status: 'pending' | 'approved' | 'rejected' }) => {
  switch (status) {
    case 'approved':
      return <CheckCircle className="h-4 w-4 text-green-500" />;
    case 'rejected':
      return <XCircle className="h-4 w-4 text-red-500" />;
    default:
      return <Clock className="h-4 w-4 text-yellow-500" />;
  }
};

const StatusBadge = ({ status }: { status: 'pending' | 'approved' | 'rejected' }) => {
  const colors = {
    pending: 'bg-yellow-100 text-yellow-800 border-yellow-200',
    approved: 'bg-green-100 text-green-800 border-green-200',
    rejected: 'bg-red-100 text-red-800 border-red-200'
  };
  
  return (
    <Badge variant="outline" className={colors[status]}>
      <StatusIcon status={status} />
      <span className="ml-1 capitalize">{status}</span>
    </Badge>
  );
};

interface KYCDetailViewProps {
  user: UserKYCData;
  onApprove: (userId: string, section: string, notes?: string) => void;
  onReject: (userId: string, section: string, reason: string) => void;
  actionLoading: boolean;
}

const KYCDetailView: React.FC<KYCDetailViewProps> = ({ user, onApprove, onReject, actionLoading }) => {
  const [rejectionReasons, setRejectionReasons] = useState<Record<string, string>>({});

  const handleReject = (section: string) => {
    const reason = rejectionReasons[section];
    if (!reason?.trim()) {
      alert('Please provide a rejection reason');
      return;
    }
    onReject(user.id, section, reason);
    setRejectionReasons(prev => ({ ...prev, [section]: '' }));
  };

  const sections = [
    {
      key: 'personal_info',
      title: 'Personal Information',
      icon: User,
      status: user.personal_info_status,
      rejectionReason: user.personal_info_rejection_reason,
      fields: [
        { label: 'First Name', value: user.first_name },
        { label: 'Last Name', value: user.last_name },
        { label: 'Middle Name', value: user.middle_name },
        { label: 'Date of Birth', value: user.date_of_birth },
        { label: 'Nationality', value: user.nationality },
        { label: 'Gender', value: user.gender },
        { label: 'Place of Birth', value: user.place_of_birth }
      ]
    },
    {
      key: 'contact_info',
      title: 'Contact Information',
      icon: Phone,
      status: user.contact_info_status,
      rejectionReason: user.contact_info_rejection_reason,
      fields: [
        { label: 'Phone', value: user.phone },
        { label: 'WhatsApp', value: user.whatsapp_number },
        { label: 'Telegram', value: user.telegram_username },
        { label: 'Twitter', value: user.twitter_handle },
        { label: 'Instagram', value: user.instagram_handle },
        { label: 'LinkedIn', value: user.linkedin_profile },
        { label: 'Facebook', value: user.facebook_profile }
      ]
    },
    {
      key: 'address_info',
      title: 'Address Information',
      icon: MapPin,
      status: user.address_info_status,
      rejectionReason: user.address_info_rejection_reason,
      fields: [
        { label: 'Address Line 1', value: user.address_line_1 },
        { label: 'Address Line 2', value: user.address_line_2 },
        { label: 'City', value: user.city },
        { label: 'State/Province', value: user.state_province },
        { label: 'Postal Code', value: user.postal_code },
        { label: 'Country', value: user.country }
      ]
    },
    {
      key: 'identity_info',
      title: 'Identity Information',
      icon: CreditCard,
      status: user.identity_info_status,
      rejectionReason: user.identity_info_rejection_reason,
      fields: [
        { label: 'ID Type', value: user.id_type },
        { label: 'ID Number', value: user.id_number },
        { label: 'ID Expiry Date', value: user.id_expiry_date }
      ]
    },
    {
      key: 'financial_info',
      title: 'Financial Information',
      icon: Briefcase,
      status: user.financial_info_status,
      rejectionReason: user.financial_info_rejection_reason,
      fields: [
        { label: 'Occupation', value: user.occupation },
        { label: 'Employer', value: user.employer },
        { label: 'Annual Income', value: user.annual_income },
        { label: 'Source of Funds', value: user.source_of_funds },
        { label: 'Purpose of Account', value: user.purpose_of_account }
      ]
    },
    {
      key: 'emergency_contact',
      title: 'Emergency Contact',
      icon: Users,
      status: user.emergency_contact_status,
      rejectionReason: user.emergency_contact_rejection_reason,
      fields: [
        { label: 'Contact Name', value: user.emergency_contact_name },
        { label: 'Contact Phone', value: user.emergency_contact_phone },
        { label: 'Relationship', value: user.emergency_contact_relationship }
      ]
    }
  ];

  return (
    <div className="space-y-6 max-h-[60vh] overflow-y-auto">
      {sections.map((section) => {
        const IconComponent = section.icon;
        return (
          <Card key={section.key}>
            <CardHeader>
              <div className="flex items-center justify-between">
                <div className="flex items-center space-x-2">
                  <IconComponent className="h-5 w-5" />
                  <CardTitle className="text-lg">{section.title}</CardTitle>
                </div>
                <StatusBadge status={section.status} />
              </div>
              {section.status === 'rejected' && section.rejectionReason && (
                <div className="bg-red-50 border border-red-200 rounded-md p-3 mt-2">
                  <p className="text-red-800 text-sm">
                    <strong>Rejection Reason:</strong> {section.rejectionReason}
                  </p>
                </div>
              )}
            </CardHeader>
            <CardContent className="space-y-3">
              <div className="grid grid-cols-2 gap-4">
                {section.fields.map((field) => (
                  <div key={field.label}>
                    <Label className="text-sm font-medium text-gray-600">{field.label}</Label>
                    <p className="text-sm text-gray-900">{field.value || 'Not provided'}</p>
                  </div>
                ))}
              </div>
              
              {section.status === 'pending' && (
                <div className="flex items-center space-x-2 pt-4 border-t">
                  <Button
                    size="sm"
                    onClick={() => onApprove(user.id, section.key)}
                    disabled={actionLoading}
                    className="bg-green-600 hover:bg-green-700"
                  >
                    {actionLoading ? <Loader2 className="h-4 w-4 animate-spin mr-2" /> : <CheckCircle className="h-4 w-4 mr-2" />}
                    Approve
                  </Button>
                  <div className="flex-1 flex items-center space-x-2">
                    <Textarea
                      placeholder="Rejection reason..."
                      value={rejectionReasons[section.key] || ''}
                      onChange={(e) => setRejectionReasons(prev => ({ ...prev, [section.key]: e.target.value }))}
                      className="flex-1"
                      rows={1}
                    />
                    <Button
                      size="sm"
                      variant="outline"
                      onClick={() => handleReject(section.key)}
                      disabled={actionLoading}
                      className="border-red-600 text-red-600 hover:bg-red-50"
                    >
                      {actionLoading ? <Loader2 className="h-4 w-4 animate-spin mr-2" /> : <XCircle className="h-4 w-4 mr-2" />}
                      Reject
                    </Button>
                  </div>
                </div>
              )}
            </CardContent>
          </Card>
        );
      })}
    </div>
  );
};

export default function EnhancedKYCManagement() {
  const [users, setUsers] = useState<UserKYCData[]>([]);
  const [loading, setLoading] = useState(false);
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedUser, setSelectedUser] = useState<UserKYCData | null>(null);
  const [actionLoading, setActionLoading] = useState(false);
  const { toast } = useToast();

  useEffect(() => {
    fetchUsers();
  }, []);

  const fetchUsers = async () => {
    setLoading(true);
    try {
      console.log('Fetching Enhanced KYC users...');
      const response = await fetch('http://localhost/aureus-angel-alliance/api/admin/enhanced-kyc-management.php?action=get_users', {
        credentials: 'include'
      });

      console.log('Response status:', response.status);
      const data = await response.json();
      console.log('Enhanced KYC API Response:', data);

      if (data.success) {
        console.log('Users received:', data.data.users);
        setUsers(data.data.users || []);
      } else {
        throw new Error(data.message || 'Failed to fetch users');
      }
    } catch (error) {
      console.error('Error fetching users:', error);
      toast({
        title: "Error",
        description: "Failed to load users",
        variant: "destructive"
      });
    } finally {
      setLoading(false);
    }
  };

  const handleApproveSection = async (userId: string, section: string, notes?: string) => {
    setActionLoading(true);
    try {
      const response = await fetch('http://localhost/aureus-angel-alliance/api/admin/enhanced-kyc-management.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({
          action: 'approve_section',
          user_id: userId,
          section: section,
          notes: notes
        })
      });

      const data = await response.json();
      if (data.success) {
        toast({
          title: "Success",
          description: `${section.replace('_', ' ')} approved successfully`,
        });
        fetchUsers(); // Refresh the list
        if (selectedUser && selectedUser.id === userId) {
          // Update selected user data
          setSelectedUser(prev => prev ? { ...prev, [`${section}_status`]: 'approved' } : null);
        }
      } else {
        throw new Error(data.message || 'Failed to approve section');
      }
    } catch (error) {
      console.error('Error approving section:', error);
      toast({
        title: "Error",
        description: "Failed to approve section",
        variant: "destructive"
      });
    } finally {
      setActionLoading(false);
    }
  };

  const handleRejectSection = async (userId: string, section: string, reason: string) => {
    setActionLoading(true);
    try {
      const response = await fetch('http://localhost/aureus-angel-alliance/api/admin/enhanced-kyc-management.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({
          action: 'reject_section',
          user_id: userId,
          section: section,
          reason: reason
        })
      });

      const data = await response.json();
      if (data.success) {
        toast({
          title: "Success",
          description: `${section.replace('_', ' ')} rejected`,
        });
        fetchUsers(); // Refresh the list
        if (selectedUser && selectedUser.id === userId) {
          // Update selected user data
          setSelectedUser(prev => prev ? {
            ...prev,
            [`${section}_status`]: 'rejected',
            [`${section}_rejection_reason`]: reason
          } : null);
        }
      } else {
        throw new Error(data.message || 'Failed to reject section');
      }
    } catch (error) {
      console.error('Error rejecting section:', error);
      toast({
        title: "Error",
        description: "Failed to reject section",
        variant: "destructive"
      });
    } finally {
      setActionLoading(false);
    }
  };

  const filteredUsers = users.filter(user =>
    user.username.toLowerCase().includes(searchTerm.toLowerCase()) ||
    user.email.toLowerCase().includes(searchTerm.toLowerCase()) ||
    user.full_name?.toLowerCase().includes(searchTerm.toLowerCase())
  );

  if (loading) {
    return (
      <div className="flex items-center justify-center p-8">
        <Loader2 className="h-8 w-8 animate-spin" />
        <span className="ml-2">Loading users...</span>
      </div>
    );
  }

  // Debug: Show user count
  console.log('Enhanced KYC Management - Users loaded:', users.length);

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-3xl font-bold">Enhanced KYC Management</h1>
        <div className="flex items-center space-x-2">
          <Search className="h-4 w-4 text-gray-400" />
          <Input
            placeholder="Search users..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="w-64"
          />
        </div>
      </div>

      <div className="grid gap-4">
        {filteredUsers.map((user) => (
          <Card key={user.id}>
            <CardHeader>
              <div className="flex items-center justify-between">
                <div>
                  <CardTitle className="flex items-center space-x-2">
                    <User className="h-5 w-5" />
                    <span>{user.full_name || user.username}</span>
                  </CardTitle>
                  <CardDescription>{user.email}</CardDescription>
                </div>
                <Dialog>
                  <DialogTrigger asChild>
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() => setSelectedUser(user)}
                    >
                      <Eye className="h-4 w-4 mr-2" />
                      View Details
                    </Button>
                  </DialogTrigger>
                  <DialogContent className="max-w-4xl max-h-[80vh] overflow-y-auto">
                    <DialogHeader>
                      <DialogTitle>KYC Details - {user.full_name || user.username}</DialogTitle>
                      <DialogDescription>
                        Review and approve/reject each section of the user's KYC information
                      </DialogDescription>
                    </DialogHeader>
                    {selectedUser && <KYCDetailView user={selectedUser} onApprove={handleApproveSection} onReject={handleRejectSection} actionLoading={actionLoading} />}
                  </DialogContent>
                </Dialog>
              </div>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-2 md:grid-cols-6 gap-4">
                <div className="flex items-center space-x-2">
                  <User className="h-4 w-4 text-gray-500" />
                  <StatusBadge status={user.personal_info_status} />
                </div>
                <div className="flex items-center space-x-2">
                  <Phone className="h-4 w-4 text-gray-500" />
                  <StatusBadge status={user.contact_info_status} />
                </div>
                <div className="flex items-center space-x-2">
                  <MapPin className="h-4 w-4 text-gray-500" />
                  <StatusBadge status={user.address_info_status} />
                </div>
                <div className="flex items-center space-x-2">
                  <CreditCard className="h-4 w-4 text-gray-500" />
                  <StatusBadge status={user.identity_info_status} />
                </div>
                <div className="flex items-center space-x-2">
                  <Briefcase className="h-4 w-4 text-gray-500" />
                  <StatusBadge status={user.financial_info_status} />
                </div>
                <div className="flex items-center space-x-2">
                  <Users className="h-4 w-4 text-gray-500" />
                  <StatusBadge status={user.emergency_contact_status} />
                </div>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      {filteredUsers.length === 0 && users.length > 0 && (
        <div className="text-center py-8">
          <p className="text-gray-500">No users found matching your search.</p>
        </div>
      )}

      {users.length === 0 && !loading && (
        <div className="text-center py-8">
          <p className="text-gray-500">No users with KYC data found.</p>
          <p className="text-sm text-gray-400 mt-2">Users will appear here once they submit their KYC profiles.</p>
        </div>
      )}
    </div>
  );
}

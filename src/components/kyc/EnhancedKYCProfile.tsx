import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useToast } from '@/hooks/use-toast';
import { Loader2, Save, CheckCircle, XCircle, Clock } from 'lucide-react';
import ApiConfig from '@/config/api';

interface KYCProfileData {
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
  email: string;
  
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
  
  // Social Media
  telegram_username: string;
  twitter_handle: string;
  instagram_handle: string;
  linkedin_profile: string;
  facebook_profile: string;
}

interface ApprovalStatus {
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
      return <CheckCircle className="h-5 w-5 text-green-500" />;
    case 'rejected':
      return <XCircle className="h-5 w-5 text-red-500" />;
    default:
      return <Clock className="h-5 w-5 text-yellow-500" />;
  }
};

const StatusBadge = ({ status }: { status: 'pending' | 'approved' | 'rejected' }) => {
  const colors = {
    pending: 'bg-yellow-100 text-yellow-800 border-yellow-200',
    approved: 'bg-green-100 text-green-800 border-green-200',
    rejected: 'bg-red-100 text-red-800 border-red-200'
  };
  
  return (
    <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium border ${colors[status]}`}>
      <StatusIcon status={status} />
      <span className="ml-1 capitalize">{status}</span>
    </span>
  );
};

export default function EnhancedKYCProfile() {
  const [formData, setFormData] = useState<KYCProfileData>({
    first_name: '', last_name: '', middle_name: '', date_of_birth: '', nationality: '', gender: '',
    place_of_birth: '', phone: '', whatsapp_number: '', email: '', address_line_1: '', address_line_2: '',
    city: '', state_province: '', postal_code: '', country: '', id_type: '', id_number: '', id_expiry_date: '',
    occupation: '', employer: '', annual_income: '', source_of_funds: '', purpose_of_account: '',
    emergency_contact_name: '', emergency_contact_phone: '', emergency_contact_relationship: '',
    telegram_username: '', twitter_handle: '', instagram_handle: '', linkedin_profile: '', facebook_profile: ''
  });
  
  const [approvalStatus, setApprovalStatus] = useState<ApprovalStatus>({
    personal_info_status: 'pending',
    contact_info_status: 'pending',
    address_info_status: 'pending',
    identity_info_status: 'pending',
    financial_info_status: 'pending',
    emergency_contact_status: 'pending'
  });
  
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const { toast } = useToast();

  useEffect(() => {
    fetchProfileData();
  }, []);

  const fetchProfileData = async () => {
    setLoading(true);
    try {
      const response = await fetch(ApiConfig.endpoints.users.enhancedKycProfile, {
        method: 'GET',
        credentials: 'include'
      });

      const data = await response.json();
      if (data.success && data.data) {
        setFormData(prev => ({ ...prev, ...data.data.profile }));
        setApprovalStatus(prev => ({ ...prev, ...data.data.approval_status }));
      }
    } catch (error) {
      console.error('Error fetching profile:', error);
      toast({
        title: "Error",
        description: "Failed to load profile data",
        variant: "destructive"
      });
    } finally {
      setLoading(false);
    }
  };

  const handleInputChange = (field: keyof KYCProfileData, value: string) => {
    setFormData(prev => ({ ...prev, [field]: value }));
  };

  const saveProfile = async () => {
    setSaving(true);
    try {
      const response = await fetch(ApiConfig.endpoints.users.enhancedKycProfile, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ action: 'update_profile', ...formData })
      });

      const data = await response.json();
      if (data.success) {
        toast({
          title: "Success",
          description: "Profile updated successfully",
        });
        fetchProfileData(); // Refresh to get updated approval status
      } else {
        throw new Error(data.message || 'Failed to save profile');
      }
    } catch (error) {
      console.error('Error saving profile:', error);
      toast({
        title: "Error",
        description: "Failed to save profile",
        variant: "destructive"
      });
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center p-8">
        <Loader2 className="h-8 w-8 animate-spin" />
        <span className="ml-2">Loading profile...</span>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-3xl font-bold">Complete KYC Profile</h1>
        <Button onClick={saveProfile} disabled={saving}>
          {saving ? <Loader2 className="h-4 w-4 animate-spin mr-2" /> : <Save className="h-4 w-4 mr-2" />}
          Save Profile
        </Button>
      </div>

      {/* Personal Information */}
      <Card>
        <CardHeader>
          <div className="flex items-center justify-between">
            <div>
              <CardTitle>Personal Information</CardTitle>
              <CardDescription>Your basic personal details</CardDescription>
            </div>
            <StatusBadge status={approvalStatus.personal_info_status} />
          </div>
          {approvalStatus.personal_info_status === 'rejected' && approvalStatus.personal_info_rejection_reason && (
            <div className="bg-red-50 border border-red-200 rounded-md p-3 mt-2">
              <p className="text-red-800 text-sm">
                <strong>Rejection Reason:</strong> {approvalStatus.personal_info_rejection_reason}
              </p>
            </div>
          )}
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <Label htmlFor="first_name">First Name *</Label>
              <Input
                id="first_name"
                value={formData.first_name}
                onChange={(e) => handleInputChange('first_name', e.target.value)}
                placeholder="Enter your first name"
              />
            </div>
            <div>
              <Label htmlFor="middle_name">Middle Name</Label>
              <Input
                id="middle_name"
                value={formData.middle_name}
                onChange={(e) => handleInputChange('middle_name', e.target.value)}
                placeholder="Enter your middle name"
              />
            </div>
            <div>
              <Label htmlFor="last_name">Last Name *</Label>
              <Input
                id="last_name"
                value={formData.last_name}
                onChange={(e) => handleInputChange('last_name', e.target.value)}
                placeholder="Enter your last name"
              />
            </div>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <Label htmlFor="date_of_birth">Date of Birth *</Label>
              <Input
                id="date_of_birth"
                type="date"
                value={formData.date_of_birth}
                onChange={(e) => handleInputChange('date_of_birth', e.target.value)}
              />
            </div>
            <div>
              <Label htmlFor="gender">Gender</Label>
              <Select value={formData.gender} onValueChange={(value) => handleInputChange('gender', value)}>
                <SelectTrigger>
                  <SelectValue placeholder="Select gender" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="male">Male</SelectItem>
                  <SelectItem value="female">Female</SelectItem>
                  <SelectItem value="other">Other</SelectItem>
                  <SelectItem value="prefer_not_to_say">Prefer not to say</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div>
              <Label htmlFor="nationality">Nationality *</Label>
              <Input
                id="nationality"
                value={formData.nationality}
                onChange={(e) => handleInputChange('nationality', e.target.value)}
                placeholder="Enter your nationality"
              />
            </div>
          </div>
          
          <div>
            <Label htmlFor="place_of_birth">Place of Birth</Label>
            <Input
              id="place_of_birth"
              value={formData.place_of_birth}
              onChange={(e) => handleInputChange('place_of_birth', e.target.value)}
              placeholder="Enter your place of birth"
            />
          </div>
        </CardContent>
      </Card>

      {/* Contact Information */}
      <Card>
        <CardHeader>
          <div className="flex items-center justify-between">
            <div>
              <CardTitle>Contact Information</CardTitle>
              <CardDescription>Your contact details and social media</CardDescription>
            </div>
            <StatusBadge status={approvalStatus.contact_info_status} />
          </div>
          {approvalStatus.contact_info_status === 'rejected' && approvalStatus.contact_info_rejection_reason && (
            <div className="bg-red-50 border border-red-200 rounded-md p-3 mt-2">
              <p className="text-red-800 text-sm">
                <strong>Rejection Reason:</strong> {approvalStatus.contact_info_rejection_reason}
              </p>
            </div>
          )}
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <Label htmlFor="phone">Phone Number *</Label>
              <Input
                id="phone"
                value={formData.phone}
                onChange={(e) => handleInputChange('phone', e.target.value)}
                placeholder="Enter your phone number"
              />
            </div>
            <div>
              <Label htmlFor="whatsapp_number">WhatsApp Number</Label>
              <Input
                id="whatsapp_number"
                value={formData.whatsapp_number}
                onChange={(e) => handleInputChange('whatsapp_number', e.target.value)}
                placeholder="Enter your WhatsApp number"
              />
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <Label htmlFor="telegram_username">Telegram Username</Label>
              <Input
                id="telegram_username"
                value={formData.telegram_username}
                onChange={(e) => handleInputChange('telegram_username', e.target.value)}
                placeholder="@username"
              />
            </div>
            <div>
              <Label htmlFor="twitter_handle">Twitter Handle</Label>
              <Input
                id="twitter_handle"
                value={formData.twitter_handle}
                onChange={(e) => handleInputChange('twitter_handle', e.target.value)}
                placeholder="@username"
              />
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <Label htmlFor="instagram_handle">Instagram Handle</Label>
              <Input
                id="instagram_handle"
                value={formData.instagram_handle}
                onChange={(e) => handleInputChange('instagram_handle', e.target.value)}
                placeholder="@username"
              />
            </div>
            <div>
              <Label htmlFor="linkedin_profile">LinkedIn Profile</Label>
              <Input
                id="linkedin_profile"
                value={formData.linkedin_profile}
                onChange={(e) => handleInputChange('linkedin_profile', e.target.value)}
                placeholder="https://linkedin.com/in/username"
              />
            </div>
          </div>

          <div>
            <Label htmlFor="facebook_profile">Facebook Profile</Label>
            <Input
              id="facebook_profile"
              value={formData.facebook_profile}
              onChange={(e) => handleInputChange('facebook_profile', e.target.value)}
              placeholder="https://facebook.com/username"
            />
          </div>
        </CardContent>
      </Card>

      {/* Address Information */}
      <Card>
        <CardHeader>
          <div className="flex items-center justify-between">
            <div>
              <CardTitle>Address Information</CardTitle>
              <CardDescription>Your residential address details</CardDescription>
            </div>
            <StatusBadge status={approvalStatus.address_info_status} />
          </div>
          {approvalStatus.address_info_status === 'rejected' && approvalStatus.address_info_rejection_reason && (
            <div className="bg-red-50 border border-red-200 rounded-md p-3 mt-2">
              <p className="text-red-800 text-sm">
                <strong>Rejection Reason:</strong> {approvalStatus.address_info_rejection_reason}
              </p>
            </div>
          )}
        </CardHeader>
        <CardContent className="space-y-4">
          <div>
            <Label htmlFor="address_line_1">Address Line 1 *</Label>
            <Input
              id="address_line_1"
              value={formData.address_line_1}
              onChange={(e) => handleInputChange('address_line_1', e.target.value)}
              placeholder="Street address, building number"
            />
          </div>

          <div>
            <Label htmlFor="address_line_2">Address Line 2</Label>
            <Input
              id="address_line_2"
              value={formData.address_line_2}
              onChange={(e) => handleInputChange('address_line_2', e.target.value)}
              placeholder="Apartment, suite, unit, etc."
            />
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <Label htmlFor="city">City *</Label>
              <Input
                id="city"
                value={formData.city}
                onChange={(e) => handleInputChange('city', e.target.value)}
                placeholder="Enter your city"
              />
            </div>
            <div>
              <Label htmlFor="state_province">State/Province</Label>
              <Input
                id="state_province"
                value={formData.state_province}
                onChange={(e) => handleInputChange('state_province', e.target.value)}
                placeholder="Enter state or province"
              />
            </div>
            <div>
              <Label htmlFor="postal_code">Postal Code</Label>
              <Input
                id="postal_code"
                value={formData.postal_code}
                onChange={(e) => handleInputChange('postal_code', e.target.value)}
                placeholder="Enter postal code"
              />
            </div>
          </div>

          <div>
            <Label htmlFor="country">Country *</Label>
            <Input
              id="country"
              value={formData.country}
              onChange={(e) => handleInputChange('country', e.target.value)}
              placeholder="Enter your country"
            />
          </div>
        </CardContent>
      </Card>

      {/* Identity Information */}
      <Card>
        <CardHeader>
          <div className="flex items-center justify-between">
            <div>
              <CardTitle>Identity Information</CardTitle>
              <CardDescription>Your government-issued identification details</CardDescription>
            </div>
            <StatusBadge status={approvalStatus.identity_info_status} />
          </div>
          {approvalStatus.identity_info_status === 'rejected' && approvalStatus.identity_info_rejection_reason && (
            <div className="bg-red-50 border border-red-200 rounded-md p-3 mt-2">
              <p className="text-red-800 text-sm">
                <strong>Rejection Reason:</strong> {approvalStatus.identity_info_rejection_reason}
              </p>
            </div>
          )}
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <Label htmlFor="id_type">ID Document Type *</Label>
              <Select value={formData.id_type} onValueChange={(value) => handleInputChange('id_type', value)}>
                <SelectTrigger>
                  <SelectValue placeholder="Select ID type" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="passport">Passport</SelectItem>
                  <SelectItem value="national_id">National ID</SelectItem>
                  <SelectItem value="drivers_license">Driver's License</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div>
              <Label htmlFor="id_number">ID Number *</Label>
              <Input
                id="id_number"
                value={formData.id_number}
                onChange={(e) => handleInputChange('id_number', e.target.value)}
                placeholder="Enter your ID number"
              />
            </div>
          </div>

          <div>
            <Label htmlFor="id_expiry_date">ID Expiry Date</Label>
            <Input
              id="id_expiry_date"
              type="date"
              value={formData.id_expiry_date}
              onChange={(e) => handleInputChange('id_expiry_date', e.target.value)}
            />
          </div>
        </CardContent>
      </Card>

      {/* Financial Information */}
      <Card>
        <CardHeader>
          <div className="flex items-center justify-between">
            <div>
              <CardTitle>Financial Information</CardTitle>
              <CardDescription>Your employment and financial details</CardDescription>
            </div>
            <StatusBadge status={approvalStatus.financial_info_status} />
          </div>
          {approvalStatus.financial_info_status === 'rejected' && approvalStatus.financial_info_rejection_reason && (
            <div className="bg-red-50 border border-red-200 rounded-md p-3 mt-2">
              <p className="text-red-800 text-sm">
                <strong>Rejection Reason:</strong> {approvalStatus.financial_info_rejection_reason}
              </p>
            </div>
          )}
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <Label htmlFor="occupation">Occupation *</Label>
              <Input
                id="occupation"
                value={formData.occupation}
                onChange={(e) => handleInputChange('occupation', e.target.value)}
                placeholder="Enter your occupation"
              />
            </div>
            <div>
              <Label htmlFor="employer">Employer</Label>
              <Input
                id="employer"
                value={formData.employer}
                onChange={(e) => handleInputChange('employer', e.target.value)}
                placeholder="Enter your employer"
              />
            </div>
          </div>

          <div>
            <Label htmlFor="annual_income">Annual Income (USD)</Label>
            <Input
              id="annual_income"
              type="number"
              value={formData.annual_income}
              onChange={(e) => handleInputChange('annual_income', e.target.value)}
              placeholder="Enter your annual income"
            />
          </div>

          <div>
            <Label htmlFor="source_of_funds">Source of Funds</Label>
            <Textarea
              id="source_of_funds"
              value={formData.source_of_funds}
              onChange={(e) => handleInputChange('source_of_funds', e.target.value)}
              placeholder="Describe the source of your investment funds"
              rows={3}
            />
          </div>

          <div>
            <Label htmlFor="purpose_of_account">Purpose of Account</Label>
            <Textarea
              id="purpose_of_account"
              value={formData.purpose_of_account}
              onChange={(e) => handleInputChange('purpose_of_account', e.target.value)}
              placeholder="Describe the purpose of your account"
              rows={3}
            />
          </div>
        </CardContent>
      </Card>

      {/* Emergency Contact */}
      <Card>
        <CardHeader>
          <div className="flex items-center justify-between">
            <div>
              <CardTitle>Emergency Contact</CardTitle>
              <CardDescription>Contact person in case of emergency</CardDescription>
            </div>
            <StatusBadge status={approvalStatus.emergency_contact_status} />
          </div>
          {approvalStatus.emergency_contact_status === 'rejected' && approvalStatus.emergency_contact_rejection_reason && (
            <div className="bg-red-50 border border-red-200 rounded-md p-3 mt-2">
              <p className="text-red-800 text-sm">
                <strong>Rejection Reason:</strong> {approvalStatus.emergency_contact_rejection_reason}
              </p>
            </div>
          )}
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <Label htmlFor="emergency_contact_name">Contact Name *</Label>
              <Input
                id="emergency_contact_name"
                value={formData.emergency_contact_name}
                onChange={(e) => handleInputChange('emergency_contact_name', e.target.value)}
                placeholder="Enter emergency contact name"
              />
            </div>
            <div>
              <Label htmlFor="emergency_contact_phone">Contact Phone *</Label>
              <Input
                id="emergency_contact_phone"
                value={formData.emergency_contact_phone}
                onChange={(e) => handleInputChange('emergency_contact_phone', e.target.value)}
                placeholder="Enter emergency contact phone"
              />
            </div>
          </div>

          <div>
            <Label htmlFor="emergency_contact_relationship">Relationship *</Label>
            <Input
              id="emergency_contact_relationship"
              value={formData.emergency_contact_relationship}
              onChange={(e) => handleInputChange('emergency_contact_relationship', e.target.value)}
              placeholder="e.g., Spouse, Parent, Sibling, Friend"
            />
          </div>
        </CardContent>
      </Card>

      <div className="flex justify-end">
        <Button onClick={saveProfile} disabled={saving} size="lg">
          {saving ? <Loader2 className="h-4 w-4 animate-spin mr-2" /> : <Save className="h-4 w-4 mr-2" />}
          Save Complete Profile
        </Button>
      </div>
    </div>
  );
}

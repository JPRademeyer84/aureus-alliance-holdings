import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import { useToast } from '@/hooks/use-toast';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import ApiConfig from '@/config/api';
import UserDetailsModal from './UserDetailsModal';
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
  FileText,
  Filter,
  Shield
} from '@/components/SafeIcons';

interface KYCDocument {
  id: string;
  user_id: string;
  username: string;
  email: string;
  full_name: string;
  type: 'passport' | 'drivers_license' | 'national_id' | 'proof_of_address';
  filename: string;
  original_name: string;
  upload_date: string;
  status: 'pending' | 'approved' | 'rejected';
  reviewed_by?: string;
  reviewed_at?: string;
  rejection_reason?: string;
}

const KYCManagement: React.FC = () => {
  const { toast } = useToast();
  const [documents, setDocuments] = useState<KYCDocument[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [activeTab, setActiveTab] = useState('pending');
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedDocument, setSelectedDocument] = useState<KYCDocument | null>(null);
  const [rejectionReason, setRejectionReason] = useState('');
  const [isProcessing, setIsProcessing] = useState(false);
  const [selectedUserId, setSelectedUserId] = useState<string | null>(null);
  const [isSyncing, setIsSyncing] = useState(false);
  const [isCleaning, setIsCleaning] = useState(false);
  const [selectedUserForRejection, setSelectedUserForRejection] = useState<KYCDocument | null>(null);
  const [overallRejectionReason, setOverallRejectionReason] = useState('');

  useEffect(() => {
    fetchKYCDocuments();
  }, []);

  const fetchKYCDocuments = async () => {
    setIsLoading(true);
    try {
      const response = await fetch(ApiConfig.endpoints.admin.kycManagement, {
        credentials: 'include'
      });
      const data = await response.json();
      
      if (data.success) {
        console.log('KYC Documents received:', data.data.documents);
        setDocuments(data.data.documents || []);
      } else {
        throw new Error(data.message || 'Failed to fetch documents');
      }
    } catch (error) {
      console.error('Failed to fetch KYC documents:', error);
      toast({
        title: "Error",
        description: "Failed to load KYC documents",
        variant: "destructive"
      });
    } finally {
      setIsLoading(false);
    }
  };

  const handleApprove = async (documentId: string) => {
    setIsProcessing(true);
    try {
      const response = await fetch(ApiConfig.endpoints.admin.kycManagement, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({
          action: 'approve',
          document_id: documentId
        })
      });

      const data = await response.json();
      if (data.success) {
        fetchKYCDocuments();
        toast({
          title: "Success",
          description: "Document approved successfully",
        });
      } else {
        throw new Error(data.message || 'Approval failed');
      }
    } catch (error) {
      console.error('Approval failed:', error);
      toast({
        title: "Error",
        description: "Failed to approve document",
        variant: "destructive"
      });
    } finally {
      setIsProcessing(false);
    }
  };

  const handleReject = async (documentId: string, reason: string) => {
    if (!reason.trim()) {
      toast({
        title: "Error",
        description: "Please provide a rejection reason",
        variant: "destructive"
      });
      return;
    }

    setIsProcessing(true);
    try {
      const response = await fetch(ApiConfig.endpoints.admin.kycManagement, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({
          action: 'reject',
          document_id: documentId,
          rejection_reason: reason
        })
      });

      const data = await response.json();
      if (data.success) {
        fetchKYCDocuments();
        setSelectedDocument(null);
        setRejectionReason('');
        toast({
          title: "Success",
          description: "Document rejected successfully",
        });
      } else {
        throw new Error(data.message || 'Rejection failed');
      }
    } catch (error) {
      console.error('Rejection failed:', error);
      toast({
        title: "Error",
        description: "Failed to reject document",
        variant: "destructive"
      });
    } finally {
      setIsProcessing(false);
    }
  };

  const handleApproveOverallKYC = async (userId: string) => {
    setIsProcessing(true);
    try {
      const response = await fetch(ApiConfig.endpoints.admin.kycManagement, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({
          action: 'approve_overall_kyc',
          user_id: userId
        })
      });

      const data = await response.json();
      if (data.success) {
        fetchKYCDocuments();
        toast({
          title: "Success",
          description: "User KYC approved successfully",
        });
      } else {
        throw new Error(data.message || 'KYC approval failed');
      }
    } catch (error) {
      console.error('KYC approval failed:', error);
      toast({
        title: "Error",
        description: "Failed to approve user KYC",
        variant: "destructive"
      });
    } finally {
      setIsProcessing(false);
    }
  };

  const handleRejectOverallKYC = async (userId: string, reason: string) => {
    if (!reason.trim()) {
      toast({
        title: "Error",
        description: "Please provide a rejection reason",
        variant: "destructive"
      });
      return;
    }

    setIsProcessing(true);
    try {
      const response = await fetch(ApiConfig.endpoints.admin.kycManagement, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({
          action: 'reject_overall_kyc',
          user_id: userId,
          rejection_reason: reason
        })
      });

      const data = await response.json();
      if (data.success) {
        fetchKYCDocuments();
        setSelectedUserForRejection(null);
        setOverallRejectionReason('');
        toast({
          title: "Success",
          description: "User KYC rejected successfully",
        });
      } else {
        throw new Error(data.message || 'KYC rejection failed');
      }
    } catch (error) {
      console.error('KYC rejection failed:', error);
      toast({
        title: "Error",
        description: "Failed to reject user KYC",
        variant: "destructive"
      });
    } finally {
      setIsProcessing(false);
    }
  };

  const handleSyncKYCStatus = async () => {
    setIsSyncing(true);
    try {
      const response = await fetch('/api/admin/sync-kyc-status.php', {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
        },
      });

      const data = await response.json();

      if (data.success) {
        toast({
          title: "KYC Status Synchronized",
          description: `Synced ${data.sync_results.email_verification_synced} email verifications, ${data.sync_results.profile_completion_synced} profile completions, and ${data.sync_results.kyc_status_synced} KYC statuses.`,
          variant: "default"
        });

        // Refresh the documents list
        fetchKYCDocuments();
      } else {
        throw new Error(data.error || 'Sync failed');
      }
    } catch (error) {
      console.error('Sync error:', error);
      toast({
        title: "Sync Failed",
        description: error instanceof Error ? error.message : "Failed to synchronize KYC status",
        variant: "destructive"
      });
    } finally {
      setIsSyncing(false);
    }
  };

  const handleCleanDuplicates = async () => {
    setIsCleaning(true);
    try {
      const response = await fetch('/api/admin/clean-kyc-duplicates.php', {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
        },
      });

      const data = await response.json();

      if (data.success) {
        toast({
          title: "KYC Requirements Cleaned",
          description: `Removed ${data.cleanup_results.duplicates_removed} duplicate requirements and reset all level requirements.`,
          variant: "default"
        });

        // Refresh the documents list
        fetchKYCDocuments();
      } else {
        throw new Error(data.error || 'Cleanup failed');
      }
    } catch (error) {
      console.error('Cleanup error:', error);
      toast({
        title: "Cleanup Failed",
        description: error instanceof Error ? error.message : "Failed to clean KYC requirements",
        variant: "destructive"
      });
    } finally {
      setIsCleaning(false);
    }
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'approved': return 'bg-green-500/20 text-green-400 border-green-500/30';
      case 'rejected': return 'bg-red-500/20 text-red-400 border-red-500/30';
      default: return 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30';
    }
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'approved': return <CheckCircle className="h-4 w-4" />;
      case 'rejected': return <XCircle className="h-4 w-4" />;
      default: return <Clock className="h-4 w-4" />;
    }
  };

  const filteredDocuments = documents.filter(doc => {
    const matchesTab = activeTab === 'all' || doc.status === activeTab;
    const matchesSearch = searchTerm === '' || 
      doc.username.toLowerCase().includes(searchTerm.toLowerCase()) ||
      doc.email.toLowerCase().includes(searchTerm.toLowerCase()) ||
      doc.full_name.toLowerCase().includes(searchTerm.toLowerCase());
    return matchesTab && matchesSearch;
  });

  const getTabCounts = () => {
    return {
      pending: documents.filter(d => d.status === 'pending').length,
      approved: documents.filter(d => d.status === 'approved').length,
      rejected: documents.filter(d => d.status === 'rejected').length,
      all: documents.length
    };
  };

  const counts = getTabCounts();

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-gold-400"></div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-white">KYC Management</h1>
          <p className="text-gray-400">Review and manage user KYC document submissions</p>
        </div>
        <div className="flex items-center gap-4">
          <Button
            onClick={handleSyncKYCStatus}
            disabled={isSyncing}
            className="bg-blue-600 hover:bg-blue-700 text-white"
          >
            {isSyncing ? (
              <>
                <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                Syncing...
              </>
            ) : (
              <>
                <Shield className="h-4 w-4 mr-2" />
                Sync KYC Status
              </>
            )}
          </Button>
          <Button
            onClick={handleCleanDuplicates}
            disabled={isCleaning}
            className="bg-red-600 hover:bg-red-700 text-white"
          >
            {isCleaning ? (
              <>
                <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                Cleaning...
              </>
            ) : (
              <>
                <Shield className="h-4 w-4 mr-2" />
                Clean Duplicates
              </>
            )}
          </Button>
          <div className="relative">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
            <Input
              placeholder="Search users..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="pl-10 bg-gray-700 border-gray-600 text-white w-64"
            />
          </div>
        </div>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <Card className="bg-gray-800 border-gray-700">
          <CardContent className="p-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-400">Pending Review</p>
                <p className="text-2xl font-bold text-yellow-400">{counts.pending}</p>
              </div>
              <Clock className="w-8 h-8 text-yellow-400" />
            </div>
          </CardContent>
        </Card>

        <Card className="bg-gray-800 border-gray-700">
          <CardContent className="p-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-400">Approved</p>
                <p className="text-2xl font-bold text-green-400">{counts.approved}</p>
              </div>
              <CheckCircle className="w-8 h-8 text-green-400" />
            </div>
          </CardContent>
        </Card>

        <Card className="bg-gray-800 border-gray-700">
          <CardContent className="p-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-400">Rejected</p>
                <p className="text-2xl font-bold text-red-400">{counts.rejected}</p>
              </div>
              <XCircle className="w-8 h-8 text-red-400" />
            </div>
          </CardContent>
        </Card>

        <Card className="bg-gray-800 border-gray-700">
          <CardContent className="p-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-400">Total Documents</p>
                <p className="text-2xl font-bold text-white">{counts.all}</p>
              </div>
              <FileText className="w-8 h-8 text-gray-400" />
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Document List */}
      <Card className="bg-gray-800 border-gray-700">
        <CardHeader>
          <CardTitle className="text-white flex items-center gap-2">
            <Shield className="h-5 w-5 text-gold" />
            KYC Documents
          </CardTitle>
        </CardHeader>
        <CardContent>
          <Tabs value={activeTab} onValueChange={setActiveTab} className="space-y-4">
            <TabsList className="grid w-full grid-cols-4 bg-gray-700">
              <TabsTrigger value="pending" className="data-[state=active]:bg-gold-gradient data-[state=active]:text-black">
                Pending ({counts.pending})
              </TabsTrigger>
              <TabsTrigger value="approved" className="data-[state=active]:bg-gold-gradient data-[state=active]:text-black">
                Approved ({counts.approved})
              </TabsTrigger>
              <TabsTrigger value="rejected" className="data-[state=active]:bg-gold-gradient data-[state=active]:text-black">
                Rejected ({counts.rejected})
              </TabsTrigger>
              <TabsTrigger value="all" className="data-[state=active]:bg-gold-gradient data-[state=active]:text-black">
                All ({counts.all})
              </TabsTrigger>
            </TabsList>

            <div className="space-y-4">
              {filteredDocuments.length === 0 ? (
                <div className="text-center py-8">
                  <FileText className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                  <p className="text-gray-400">No documents found</p>
                </div>
              ) : (
                filteredDocuments.map((doc) => (
                  <div key={doc.id} className="border border-gray-700 rounded-lg p-4">
                    <div className="flex items-center justify-between">
                      <div className="flex items-center gap-4">
                        <div className="w-12 h-12 bg-gold-gradient rounded-full flex items-center justify-center">
                          <span className="text-black font-bold">
                            {doc.full_name?.charAt(0) || doc.username.charAt(0)}
                          </span>
                        </div>
                        <div>
                          <h3 className="text-white font-medium">{doc.full_name || doc.username}</h3>
                          <p className="text-gray-400 text-sm">@{doc.username} • {doc.email}</p>
                          <div className="flex items-center gap-2 mt-1">
                            <Badge className={getStatusColor(doc.status)}>
                              {getStatusIcon(doc.status)}
                              <span className="ml-1 capitalize">{doc.status}</span>
                            </Badge>
                            <span className="text-gray-500 text-xs">
                              {doc.total_docs} document{doc.total_docs !== 1 ? 's' : ''}
                            </span>
                            {doc.pending_docs > 0 && (
                              <span className="text-yellow-400 text-xs">
                                {doc.pending_docs} pending
                              </span>
                            )}
                            {doc.approved_docs > 0 && (
                              <span className="text-green-400 text-xs">
                                {doc.approved_docs} approved
                              </span>
                            )}
                            {doc.rejected_docs > 0 && (
                              <span className="text-red-400 text-xs">
                                {doc.rejected_docs} rejected
                              </span>
                            )}
                            {doc.upload_date && (
                              <span className="text-gray-500 text-xs">
                                Last: {new Date(doc.upload_date).toLocaleDateString()}
                              </span>
                            )}
                          </div>
                        </div>
                      </div>
                      <div className="flex items-center gap-2">
                        <Button
                          size="sm"
                          variant="outline"
                          onClick={() => setSelectedUserId(doc.user_id)}
                          className="border-gray-600 text-gray-300 hover:bg-gray-700"
                        >
                          <User className="h-4 w-4 mr-1" />
                          View Details
                        </Button>
                        {/* Show overall status buttons */}
                        {doc.status === 'pending' && (
                          <>
                            <Button
                              size="sm"
                              onClick={() => handleApproveOverallKYC(doc.user_id)}
                              disabled={isProcessing}
                              className="bg-green-600 hover:bg-green-700 text-white"
                            >
                              <CheckCircle className="h-4 w-4 mr-1" />
                              {isProcessing ? 'Approving...' : 'Approve KYC'}
                            </Button>
                            <Button
                              size="sm"
                              onClick={() => setSelectedUserForRejection(doc)}
                              disabled={isProcessing}
                              className="bg-red-600 hover:bg-red-700 text-white"
                            >
                              <XCircle className="h-4 w-4 mr-1" />
                              Reject KYC
                            </Button>
                          </>
                        )}

                        {doc.status === 'approved' && (
                          <Badge className="bg-green-500/20 text-green-400 border-green-500/30">
                            <CheckCircle className="h-4 w-4 mr-1" />
                            KYC Verified
                          </Badge>
                        )}

                        {doc.status === 'rejected' && (
                          <Badge className="bg-red-500/20 text-red-400 border-red-500/30">
                            <XCircle className="h-4 w-4 mr-1" />
                            KYC Rejected
                          </Badge>
                        )}
                      </div>
                    </div>
                    {doc.rejection_reason && (
                      <div className="mt-3 bg-red-500/10 border border-red-500/30 rounded p-3">
                        <p className="text-red-400 text-sm">
                          <strong>Rejection Reason:</strong> {doc.rejection_reason}
                        </p>
                      </div>
                    )}
                  </div>
                ))
              )}
            </div>
          </Tabs>
        </CardContent>
      </Card>

      {/* Rejection Modal */}
      {selectedDocument && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
          <Card className="bg-gray-800 border-gray-700 w-full max-w-md mx-4">
            <CardHeader>
              <CardTitle className="text-white">Reject Document</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div>
                <p className="text-gray-400 mb-2">
                  Rejecting {selectedDocument.type.replace('_', ' ')} for {selectedDocument.full_name || selectedDocument.username}
                </p>
                <Textarea
                  placeholder="Please provide a reason for rejection..."
                  value={rejectionReason}
                  onChange={(e) => setRejectionReason(e.target.value)}
                  className="bg-gray-700 border-gray-600 text-white"
                  rows={4}
                />
              </div>
              <div className="flex items-center gap-2 justify-end">
                <Button
                  variant="outline"
                  onClick={() => {
                    setSelectedDocument(null);
                    setRejectionReason('');
                  }}
                  className="border-gray-600 text-gray-300"
                >
                  Cancel
                </Button>
                <Button
                  onClick={() => handleReject(selectedDocument.id, rejectionReason)}
                  disabled={isProcessing || !rejectionReason.trim()}
                  className="bg-red-600 hover:bg-red-700 text-white"
                >
                  {isProcessing ? 'Rejecting...' : 'Reject Document'}
                </Button>
              </div>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Overall KYC Rejection Modal */}
      {selectedUserForRejection && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
          <Card className="bg-gray-800 border-gray-700 w-full max-w-md mx-4">
            <CardHeader>
              <CardTitle className="text-white">Reject Overall KYC</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div>
                <p className="text-gray-400 mb-2">
                  Rejecting overall KYC verification for {selectedUserForRejection.full_name || selectedUserForRejection.username}
                </p>
                <p className="text-sm text-yellow-400 mb-3">
                  This will reject the user's entire KYC application and they will need to resubmit.
                </p>
                <Textarea
                  placeholder="Please provide a reason for KYC rejection..."
                  value={overallRejectionReason}
                  onChange={(e) => setOverallRejectionReason(e.target.value)}
                  className="bg-gray-700 border-gray-600 text-white"
                  rows={4}
                />
              </div>
              <div className="flex items-center gap-2 justify-end">
                <Button
                  variant="outline"
                  onClick={() => {
                    setSelectedUserForRejection(null);
                    setOverallRejectionReason('');
                  }}
                  className="border-gray-600 text-gray-300"
                >
                  Cancel
                </Button>
                <Button
                  onClick={() => handleRejectOverallKYC(selectedUserForRejection.user_id, overallRejectionReason)}
                  disabled={isProcessing || !overallRejectionReason.trim()}
                  className="bg-red-600 hover:bg-red-700 text-white"
                >
                  {isProcessing ? 'Rejecting...' : 'Reject KYC'}
                </Button>
              </div>
            </CardContent>
          </Card>
        </div>
      )}

      {/* User Details Modal */}
      {selectedUserId && (
        <UserDetailsModal
          userId={selectedUserId}
          onClose={() => setSelectedUserId(null)}
          onApprove={handleApprove}
          onReject={handleReject}
        />
      )}
    </div>
  );
};

export default KYCManagement;

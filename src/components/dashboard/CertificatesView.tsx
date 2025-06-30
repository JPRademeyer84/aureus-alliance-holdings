import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { useToast } from '@/hooks/use-toast';
import { useUser } from '@/contexts/UserContext';
import { 
  FileText, 
  Download, 
  Eye, 
  Share2, 
  Shield, 
  Calendar,
  DollarSign,
  Award,
  ExternalLink,
  QrCode,
  Copy,
  CheckCircle,
  AlertCircle,
  Clock
} from 'lucide-react';
import ApiConfig from '@/config/api';

interface Certificate {
  id: string;
  certificate_number: string;
  investment_id: string;
  package_name: string;
  share_quantity: number;
  certificate_value: number;
  issue_date: string;
  generation_status: 'pending' | 'generating' | 'completed' | 'failed';
  delivery_status: 'pending' | 'sent' | 'delivered' | 'viewed';
  legal_status: 'valid' | 'invalidated' | 'converted_to_nft';
  certificate_image_path?: string;
  certificate_pdf_path?: string;
  template_name: string;
  verification_code?: string;
  verification_url?: string;
  investment_amount: number;
  investment_date: string;
  view_count: number;
  created_at: string;
}

const CertificatesView: React.FC = () => {
  const [certificates, setCertificates] = useState<Certificate[]>([]);
  const [loading, setLoading] = useState(true);
  const [selectedCertificate, setSelectedCertificate] = useState<Certificate | null>(null);
  const [showDetailsDialog, setShowDetailsDialog] = useState(false);
  const { user } = useUser();
  const { toast } = useToast();

  useEffect(() => {
    if (user?.id) {
      loadCertificates();
    }
  }, [user]);

  const loadCertificates = async () => {
    try {
      const response = await fetch(`${ApiConfig.baseUrl}/users/certificates.php?user_id=${user?.id}`);
      const data = await response.json();
      
      if (data.success) {
        setCertificates(data.certificates || []);
      } else {
        throw new Error(data.error || 'Failed to load certificates');
      }
    } catch (error) {
      console.error('Error loading certificates:', error);
      toast({
        title: 'Error',
        description: 'Failed to load your certificates',
        variant: 'destructive',
      });
    } finally {
      setLoading(false);
    }
  };

  const handleCertificateAction = async (certificateId: string, action: string, additionalData?: any) => {
    try {
      const response = await fetch(`${ApiConfig.baseUrl}/users/certificates.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          certificate_id: certificateId,
          user_id: user?.id,
          action,
          ...additionalData
        }),
      });

      const data = await response.json();
      
      if (data.success) {
        return data;
      } else {
        throw new Error(data.error || `Failed to ${action} certificate`);
      }
    } catch (error) {
      console.error(`Error ${action} certificate:`, error);
      toast({
        title: 'Error',
        description: error instanceof Error ? error.message : `Failed to ${action} certificate`,
        variant: 'destructive',
      });
      return null;
    }
  };

  const handleDownload = async (certificate: Certificate) => {
    const result = await handleCertificateAction(certificate.id, 'download');
    if (result) {
      if (result.download_url) {
        window.open(result.download_url, '_blank');
        toast({
          title: 'Success',
          description: 'Certificate downloaded successfully',
        });
      } else {
        toast({
          title: 'Info',
          description: 'Certificate is still being generated',
        });
      }
      loadCertificates(); // Refresh to update view count
    }
  };

  const handleShare = async (certificate: Certificate) => {
    const result = await handleCertificateAction(certificate.id, 'share', { share_method: 'link' });
    if (result && result.share_url) {
      navigator.clipboard.writeText(result.share_url);
      toast({
        title: 'Success',
        description: 'Share link copied to clipboard',
      });
    }
  };

  const handleVerify = async (certificate: Certificate) => {
    const result = await handleCertificateAction(certificate.id, 'verify');
    if (result) {
      setSelectedCertificate({ ...certificate, ...result });
      setShowDetailsDialog(true);
    }
  };

  const getStatusBadge = (status: string, type: 'generation' | 'delivery' | 'legal') => {
    const variants: Record<string, { variant: any; icon: React.ReactNode; color: string }> = {
      // Generation status
      pending: { variant: 'secondary', icon: <Clock className="w-3 h-3" />, color: 'text-yellow-600' },
      generating: { variant: 'default', icon: <Clock className="w-3 h-3" />, color: 'text-blue-600' },
      completed: { variant: 'default', icon: <CheckCircle className="w-3 h-3" />, color: 'text-green-600' },
      failed: { variant: 'destructive', icon: <AlertCircle className="w-3 h-3" />, color: 'text-red-600' },
      
      // Delivery status
      sent: { variant: 'default', icon: <CheckCircle className="w-3 h-3" />, color: 'text-blue-600' },
      delivered: { variant: 'default', icon: <CheckCircle className="w-3 h-3" />, color: 'text-green-600' },
      viewed: { variant: 'default', icon: <Eye className="w-3 h-3" />, color: 'text-green-600' },
      
      // Legal status
      valid: { variant: 'default', icon: <Shield className="w-3 h-3" />, color: 'text-green-600' },
      invalidated: { variant: 'destructive', icon: <AlertCircle className="w-3 h-3" />, color: 'text-red-600' },
      converted_to_nft: { variant: 'secondary', icon: <Award className="w-3 h-3" />, color: 'text-purple-600' },
    };

    const config = variants[status] || { variant: 'secondary', icon: <AlertCircle className="w-3 h-3" />, color: 'text-gray-600' };
    
    return (
      <Badge variant={config.variant} className={`flex items-center gap-1 ${config.color}`}>
        {config.icon}
        {status.replace('_', ' ').toUpperCase()}
      </Badge>
    );
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center p-8">
        <Clock className="w-8 h-8 animate-spin" />
        <span className="ml-2">Loading your certificates...</span>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-3xl font-bold tracking-tight">My Share Certificates</h2>
          <p className="text-muted-foreground">
            View and manage your Aureus Alliance Holdings share certificates
          </p>
        </div>
      </div>

      {certificates.length === 0 ? (
        <Card>
          <CardContent className="text-center py-12">
            <FileText className="w-16 h-16 mx-auto mb-4 text-muted-foreground" />
            <h3 className="text-xl font-semibold mb-2">No Certificates Yet</h3>
            <p className="text-muted-foreground mb-4">
              Your share certificates will appear here once your investments are processed
            </p>
            <p className="text-sm text-muted-foreground">
              Certificates are typically generated within 24-48 hours of investment confirmation
            </p>
          </CardContent>
        </Card>
      ) : (
        <div className="grid gap-6">
          {certificates.map((certificate) => (
            <Card key={certificate.id} className="overflow-hidden">
              <CardHeader className="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-950 dark:to-purple-950">
                <div className="flex items-center justify-between">
                  <div>
                    <CardTitle className="text-xl font-bold">
                      Certificate #{certificate.certificate_number}
                    </CardTitle>
                    <CardDescription className="text-base">
                      {certificate.package_name} • {certificate.share_quantity.toLocaleString()} shares
                    </CardDescription>
                  </div>
                  <div className="text-right">
                    <div className="text-2xl font-bold text-green-600">
                      ${certificate.certificate_value.toLocaleString()}
                    </div>
                    <div className="text-sm text-muted-foreground">
                      Certificate Value
                    </div>
                  </div>
                </div>
              </CardHeader>
              
              <CardContent className="p-6">
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                  <div className="space-y-3">
                    <div className="flex items-center gap-2">
                      <Calendar className="w-4 h-4 text-muted-foreground" />
                      <span className="text-sm font-medium">Issue Date</span>
                    </div>
                    <p className="text-lg">{new Date(certificate.issue_date).toLocaleDateString()}</p>
                  </div>
                  
                  <div className="space-y-3">
                    <div className="flex items-center gap-2">
                      <DollarSign className="w-4 h-4 text-muted-foreground" />
                      <span className="text-sm font-medium">Investment Amount</span>
                    </div>
                    <p className="text-lg">${certificate.investment_amount.toLocaleString()}</p>
                  </div>
                  
                  <div className="space-y-3">
                    <div className="flex items-center gap-2">
                      <Eye className="w-4 h-4 text-muted-foreground" />
                      <span className="text-sm font-medium">Views</span>
                    </div>
                    <p className="text-lg">{certificate.view_count}</p>
                  </div>
                </div>

                <div className="flex flex-wrap gap-3 mb-6">
                  {getStatusBadge(certificate.generation_status, 'generation')}
                  {getStatusBadge(certificate.delivery_status, 'delivery')}
                  {getStatusBadge(certificate.legal_status, 'legal')}
                </div>

                <div className="flex flex-wrap gap-3">
                  {certificate.generation_status === 'completed' && certificate.certificate_image_path && (
                    <Button onClick={() => handleDownload(certificate)}>
                      <Download className="w-4 h-4 mr-2" />
                      Download
                    </Button>
                  )}
                  
                  <Button variant="outline" onClick={() => handleVerify(certificate)}>
                    <Shield className="w-4 h-4 mr-2" />
                    View Details
                  </Button>
                  
                  {certificate.verification_url && (
                    <Button variant="outline" onClick={() => handleShare(certificate)}>
                      <Share2 className="w-4 h-4 mr-2" />
                      Share
                    </Button>
                  )}
                  
                  {certificate.generation_status === 'pending' && (
                    <Badge variant="secondary" className="flex items-center gap-1">
                      <Clock className="w-3 h-3" />
                      Generating...
                    </Badge>
                  )}
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}

      {/* Certificate Details Dialog */}
      <Dialog open={showDetailsDialog} onOpenChange={setShowDetailsDialog}>
        <DialogContent className="max-w-2xl">
          <DialogHeader>
            <DialogTitle>Certificate Details</DialogTitle>
            <DialogDescription>
              Complete information about your share certificate
            </DialogDescription>
          </DialogHeader>
          
          {selectedCertificate && (
            <div className="space-y-6">
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <Label className="text-sm font-medium text-muted-foreground">Certificate Number</Label>
                  <p className="font-mono text-lg">{selectedCertificate.certificate_number}</p>
                </div>
                <div>
                  <Label className="text-sm font-medium text-muted-foreground">Template</Label>
                  <p>{selectedCertificate.template_name}</p>
                </div>
              </div>
              
              {selectedCertificate.verification_code && (
                <div className="space-y-3">
                  <Label className="text-sm font-medium text-muted-foreground">Verification</Label>
                  <div className="flex items-center gap-2 p-3 bg-muted rounded-lg">
                    <QrCode className="w-5 h-5" />
                    <code className="flex-1 font-mono text-sm">{selectedCertificate.verification_code}</code>
                    <Button 
                      variant="outline" 
                      size="sm"
                      onClick={() => {
                        navigator.clipboard.writeText(selectedCertificate.verification_code!);
                        toast({ title: 'Copied', description: 'Verification code copied to clipboard' });
                      }}
                    >
                      <Copy className="w-4 h-4" />
                    </Button>
                  </div>
                </div>
              )}
              
              {selectedCertificate.verification_url && (
                <div className="space-y-3">
                  <Label className="text-sm font-medium text-muted-foreground">Public Verification URL</Label>
                  <div className="flex items-center gap-2 p-3 bg-muted rounded-lg">
                    <ExternalLink className="w-5 h-5" />
                    <code className="flex-1 font-mono text-sm break-all">{selectedCertificate.verification_url}</code>
                    <Button 
                      variant="outline" 
                      size="sm"
                      onClick={() => window.open(selectedCertificate.verification_url, '_blank')}
                    >
                      Open
                    </Button>
                  </div>
                </div>
              )}
              
              <div className="text-sm text-muted-foreground space-y-1">
                <p><strong>Important:</strong> This certificate represents your legal ownership of shares in Aureus Alliance Holdings.</p>
                <p>Once converted to an NFT, this certificate will become invalid and the NFT will serve as proof of ownership.</p>
                <p>Keep this certificate secure and do not share your verification details with unauthorized parties.</p>
              </div>
            </div>
          )}
        </DialogContent>
      </Dialog>
    </div>
  );
};

const Label: React.FC<{ className?: string; children: React.ReactNode }> = ({ className, children }) => (
  <label className={className}>{children}</label>
);

export default CertificatesView;

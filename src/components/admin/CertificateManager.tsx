import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { useToast } from '@/hooks/use-toast';
import { 
  FileText, 
  Download, 
  Eye, 
  RefreshCw, 
  Plus, 
  Search,
  Calendar,
  User,
  DollarSign,
  Award,
  AlertCircle,
  CheckCircle,
  Clock,
  XCircle
} from 'lucide-react';
import ApiConfig from '@/config/api';

interface Certificate {
  id: string;
  certificate_number: string;
  investment_id: string;
  user_id: string;
  username: string;
  email: string;
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
  generated_by_username?: string;
  created_at: string;
  view_count: number;
}

interface Investment {
  id: string;
  user_id: string;
  username: string;
  email: string;
  package_name: string;
  shares: number;
  amount: number;
  status: string;
  created_at: string;
}

const CertificateManager: React.FC = () => {
  const [certificates, setCertificates] = useState<Certificate[]>([]);
  const [investments, setInvestments] = useState<Investment[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [selectedCertificate, setSelectedCertificate] = useState<Certificate | null>(null);
  const [showCreateDialog, setShowCreateDialog] = useState(false);
  const [generating, setGenerating] = useState<string | null>(null);
  const { toast } = useToast();

  useEffect(() => {
    loadCertificates();
    loadInvestments();
  }, []);

  const loadCertificates = async () => {
    try {
      const response = await fetch(`${ApiConfig.baseUrl}/admin/certificate-generator.php`);
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
        description: 'Failed to load certificates',
        variant: 'destructive',
      });
    } finally {
      setLoading(false);
    }
  };

  const loadInvestments = async () => {
    try {
      // Load investments that don't have certificates yet
      const response = await fetch(`${ApiConfig.endpoints.investments.list}?without_certificates=true`);
      const data = await response.json();
      
      if (data.success) {
        setInvestments(data.investments || []);
      }
    } catch (error) {
      console.error('Error loading investments:', error);
    }
  };

  const generateCertificate = async (investmentId: string) => {
    try {
      setGenerating(investmentId);
      
      const response = await fetch(`${ApiConfig.baseUrl}/admin/certificate-generator.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          investment_id: investmentId,
          generated_by: 'admin', // Should be actual admin ID
          generation_method: 'manual'
        }),
      });

      const data = await response.json();
      
      if (data.success) {
        toast({
          title: 'Success',
          description: 'Certificate created successfully',
        });
        
        // Now generate the actual certificate image
        await generateCertificateImage(data.certificate_id);
        
        loadCertificates();
        loadInvestments();
        setShowCreateDialog(false);
      } else {
        throw new Error(data.error || 'Failed to create certificate');
      }
    } catch (error) {
      console.error('Error generating certificate:', error);
      toast({
        title: 'Error',
        description: error instanceof Error ? error.message : 'Failed to generate certificate',
        variant: 'destructive',
      });
    } finally {
      setGenerating(null);
    }
  };

  const generateCertificateImage = async (certificateId: string) => {
    try {
      const response = await fetch(`${ApiConfig.baseUrl}/admin/generate-certificate.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          certificate_id: certificateId,
          admin_id: 'admin' // Should be actual admin ID
        }),
      });

      const data = await response.json();
      
      if (data.success) {
        toast({
          title: 'Success',
          description: 'Certificate image generated successfully',
        });
        loadCertificates();
      } else {
        throw new Error(data.error || 'Failed to generate certificate image');
      }
    } catch (error) {
      console.error('Error generating certificate image:', error);
      toast({
        title: 'Warning',
        description: 'Certificate created but image generation failed',
        variant: 'destructive',
      });
    }
  };

  const getStatusBadge = (status: string, type: 'generation' | 'delivery' | 'legal') => {
    const variants: Record<string, { variant: any; icon: React.ReactNode }> = {
      // Generation status
      pending: { variant: 'secondary', icon: <Clock className="w-3 h-3" /> },
      generating: { variant: 'default', icon: <RefreshCw className="w-3 h-3 animate-spin" /> },
      completed: { variant: 'default', icon: <CheckCircle className="w-3 h-3" /> },
      failed: { variant: 'destructive', icon: <XCircle className="w-3 h-3" /> },
      
      // Delivery status
      sent: { variant: 'default', icon: <CheckCircle className="w-3 h-3" /> },
      delivered: { variant: 'default', icon: <CheckCircle className="w-3 h-3" /> },
      viewed: { variant: 'default', icon: <Eye className="w-3 h-3" /> },
      
      // Legal status
      valid: { variant: 'default', icon: <CheckCircle className="w-3 h-3" /> },
      invalidated: { variant: 'destructive', icon: <XCircle className="w-3 h-3" /> },
      converted_to_nft: { variant: 'secondary', icon: <Award className="w-3 h-3" /> },
    };

    const config = variants[status] || { variant: 'secondary', icon: <AlertCircle className="w-3 h-3" /> };
    
    return (
      <Badge variant={config.variant} className="flex items-center gap-1">
        {config.icon}
        {status.replace('_', ' ').toUpperCase()}
      </Badge>
    );
  };

  const filteredCertificates = certificates.filter(cert => {
    const matchesSearch = cert.certificate_number.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         cert.username.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         cert.email.toLowerCase().includes(searchTerm.toLowerCase());
    
    const matchesStatus = statusFilter === 'all' || cert.generation_status === statusFilter;
    
    return matchesSearch && matchesStatus;
  });

  if (loading) {
    return (
      <div className="flex items-center justify-center p-8">
        <RefreshCw className="w-8 h-8 animate-spin" />
        <span className="ml-2">Loading certificates...</span>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-3xl font-bold tracking-tight">Certificate Management</h2>
          <p className="text-muted-foreground">
            Manage share certificates and NFT conversion tracking
          </p>
        </div>
        
        <Dialog open={showCreateDialog} onOpenChange={setShowCreateDialog}>
          <DialogTrigger asChild>
            <Button>
              <Plus className="w-4 h-4 mr-2" />
              Generate Certificate
            </Button>
          </DialogTrigger>
          <DialogContent className="max-w-4xl">
            <DialogHeader>
              <DialogTitle>Generate New Certificate</DialogTitle>
              <DialogDescription>
                Select an investment to generate a share certificate for
              </DialogDescription>
            </DialogHeader>
            
            <div className="space-y-4">
              <div className="grid gap-4">
                {investments.length === 0 ? (
                  <p className="text-center text-muted-foreground py-8">
                    No investments without certificates found
                  </p>
                ) : (
                  <div className="space-y-2">
                    {investments.map((investment) => (
                      <Card key={investment.id} className="p-4">
                        <div className="flex items-center justify-between">
                          <div className="space-y-1">
                            <div className="flex items-center gap-2">
                              <User className="w-4 h-4" />
                              <span className="font-medium">{investment.username}</span>
                              <span className="text-sm text-muted-foreground">({investment.email})</span>
                            </div>
                            <div className="flex items-center gap-4 text-sm text-muted-foreground">
                              <span className="flex items-center gap-1">
                                <Award className="w-3 h-3" />
                                {investment.package_name}
                              </span>
                              <span className="flex items-center gap-1">
                                <FileText className="w-3 h-3" />
                                {investment.shares} shares
                              </span>
                              <span className="flex items-center gap-1">
                                <DollarSign className="w-3 h-3" />
                                ${investment.amount.toLocaleString()}
                              </span>
                              <span className="flex items-center gap-1">
                                <Calendar className="w-3 h-3" />
                                {new Date(investment.created_at).toLocaleDateString()}
                              </span>
                            </div>
                          </div>
                          <Button
                            onClick={() => generateCertificate(investment.id)}
                            disabled={generating === investment.id}
                          >
                            {generating === investment.id ? (
                              <RefreshCw className="w-4 h-4 animate-spin" />
                            ) : (
                              <FileText className="w-4 h-4" />
                            )}
                            Generate
                          </Button>
                        </div>
                      </Card>
                    ))}
                  </div>
                )}
              </div>
            </div>
          </DialogContent>
        </Dialog>
      </div>

      {/* Filters */}
      <div className="flex items-center gap-4">
        <div className="relative flex-1 max-w-sm">
          <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground w-4 h-4" />
          <Input
            placeholder="Search certificates..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="pl-10"
          />
        </div>
        
        <Select value={statusFilter} onValueChange={setStatusFilter}>
          <SelectTrigger className="w-48">
            <SelectValue placeholder="Filter by status" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Status</SelectItem>
            <SelectItem value="pending">Pending</SelectItem>
            <SelectItem value="generating">Generating</SelectItem>
            <SelectItem value="completed">Completed</SelectItem>
            <SelectItem value="failed">Failed</SelectItem>
          </SelectContent>
        </Select>
        
        <Button variant="outline" onClick={loadCertificates}>
          <RefreshCw className="w-4 h-4 mr-2" />
          Refresh
        </Button>
      </div>

      {/* Certificates Table */}
      <Card>
        <CardHeader>
          <CardTitle>Share Certificates</CardTitle>
          <CardDescription>
            {filteredCertificates.length} certificate(s) found
          </CardDescription>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Certificate #</TableHead>
                <TableHead>Holder</TableHead>
                <TableHead>Package</TableHead>
                <TableHead>Shares</TableHead>
                <TableHead>Value</TableHead>
                <TableHead>Generation</TableHead>
                <TableHead>Delivery</TableHead>
                <TableHead>Legal Status</TableHead>
                <TableHead>Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {filteredCertificates.map((certificate) => (
                <TableRow key={certificate.id}>
                  <TableCell className="font-mono text-sm">
                    {certificate.certificate_number}
                  </TableCell>
                  <TableCell>
                    <div>
                      <div className="font-medium">{certificate.username}</div>
                      <div className="text-sm text-muted-foreground">{certificate.email}</div>
                    </div>
                  </TableCell>
                  <TableCell>{certificate.package_name}</TableCell>
                  <TableCell>{certificate.share_quantity.toLocaleString()}</TableCell>
                  <TableCell>${certificate.certificate_value.toLocaleString()}</TableCell>
                  <TableCell>{getStatusBadge(certificate.generation_status, 'generation')}</TableCell>
                  <TableCell>{getStatusBadge(certificate.delivery_status, 'delivery')}</TableCell>
                  <TableCell>{getStatusBadge(certificate.legal_status, 'legal')}</TableCell>
                  <TableCell>
                    <div className="flex items-center gap-2">
                      {certificate.certificate_image_path && (
                        <Button variant="outline" size="sm">
                          <Eye className="w-4 h-4" />
                        </Button>
                      )}
                      {certificate.certificate_pdf_path && (
                        <Button variant="outline" size="sm">
                          <Download className="w-4 h-4" />
                        </Button>
                      )}
                      {certificate.generation_status === 'pending' && (
                        <Button 
                          variant="outline" 
                          size="sm"
                          onClick={() => generateCertificateImage(certificate.id)}
                        >
                          <RefreshCw className="w-4 h-4" />
                        </Button>
                      )}
                    </div>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
          
          {filteredCertificates.length === 0 && (
            <div className="text-center py-8 text-muted-foreground">
              No certificates found matching your criteria
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
};

export default CertificateManager;

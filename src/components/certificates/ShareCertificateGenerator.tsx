import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { useToast } from '@/hooks/use-toast';

// Safe icons
const FileText = ({ className }: { className?: string }) => <span className={className}>📄</span>;
const Download = ({ className }: { className?: string }) => <span className={className}>⬇️</span>;
const Printer = ({ className }: { className?: string }) => <span className={className}>🖨️</span>;
const Calendar = ({ className }: { className?: string }) => <span className={className}>📅</span>;
const Shield = ({ className }: { className?: string }) => <span className={className}>🛡️</span>;
const AlertTriangle = ({ className }: { className?: string }) => <span className={className}>⚠️</span>;
const CheckCircle = ({ className }: { className?: string }) => <span className={className}>✅</span>;
const Clock = ({ className }: { className?: string }) => <span className={className}>🕐</span>;
const RefreshCw = ({ className }: { className?: string }) => <span className={className}>🔄</span>;
const Eye = ({ className }: { className?: string }) => <span className={className}>👁️</span>;
const Award = ({ className }: { className?: string }) => <span className={className}>🏆</span>;
const Stamp = ({ className }: { className?: string }) => <span className={className}>🔖</span>;

interface ShareCertificate {
  id: number;
  certificate_number: string;
  user_id: number;
  investment_id: number;
  shares_amount: number;
  share_value: number;
  total_value: number;
  issue_date: string;
  expiry_date: string;
  is_printed: boolean;
  print_count: number;
  is_void: boolean;
  void_reason: string | null;
  void_date: string | null;
  pdf_path: string | null;
  metadata: any;
  created_at: string;
  updated_at: string;
  investment_details: {
    package_name: string;
    amount: number;
    phase_id: number;
    user_name: string;
    user_email: string;
  };
}

interface CertificateStats {
  total_certificates: number;
  active_certificates: number;
  void_certificates: number;
  printed_certificates: number;
  expiring_soon: number;
  total_share_value: number;
}

const ShareCertificateGenerator: React.FC = () => {
  const [certificates, setCertificates] = useState<ShareCertificate[]>([]);
  const [stats, setStats] = useState<CertificateStats | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [generatingCertificate, setGeneratingCertificate] = useState<number | null>(null);
  const [selectedCertificate, setSelectedCertificate] = useState<ShareCertificate | null>(null);
  const [showPreview, setShowPreview] = useState(false);
  const { toast } = useToast();

  useEffect(() => {
    fetchCertificates();
  }, []);

  const fetchCertificates = async () => {
    setIsLoading(true);
    try {
      const response = await fetch('/api/certificates/list.php', {
        credentials: 'include'
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const data = await response.json();

      if (data.success) {
        setCertificates(data.certificates || []);
        setStats(data.stats || null);
      } else {
        throw new Error(data.error || 'Failed to fetch certificates');
      }
    } catch (error) {
      console.error('Failed to fetch certificates:', error);
      toast({
        title: "Error",
        description: "Failed to load certificate data",
        variant: "destructive"
      });
    } finally {
      setIsLoading(false);
    }
  };

  const generateCertificate = async (investmentId: number) => {
    setGeneratingCertificate(investmentId);
    try {
      const response = await fetch('/api/certificates/generate.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({
          investment_id: investmentId
        })
      });

      const data = await response.json();

      if (data.success) {
        toast({
          title: "Success",
          description: "Share certificate generated successfully",
        });
        fetchCertificates();
      } else {
        throw new Error(data.error || 'Failed to generate certificate');
      }
    } catch (error) {
      console.error('Failed to generate certificate:', error);
      toast({
        title: "Error",
        description: "Failed to generate certificate",
        variant: "destructive"
      });
    } finally {
      setGeneratingCertificate(null);
    }
  };

  const downloadCertificate = async (certificateId: number) => {
    try {
      const response = await fetch(`/api/certificates/download.php?certificate_id=${certificateId}`, {
        credentials: 'include'
      });

      if (response.ok) {
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `share-certificate-${certificateId}.pdf`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);

        // Update print count
        await fetch('/api/certificates/mark-printed.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          credentials: 'include',
          body: JSON.stringify({
            certificate_id: certificateId
          })
        });

        toast({
          title: "Success",
          description: "Certificate downloaded successfully",
        });
        fetchCertificates();
      } else {
        throw new Error('Failed to download certificate');
      }
    } catch (error) {
      console.error('Failed to download certificate:', error);
      toast({
        title: "Error",
        description: "Failed to download certificate",
        variant: "destructive"
      });
    }
  };

  const voidCertificate = async (certificateId: number, reason: string) => {
    try {
      const response = await fetch('/api/certificates/void.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({
          certificate_id: certificateId,
          void_reason: reason
        })
      });

      const data = await response.json();

      if (data.success) {
        toast({
          title: "Success",
          description: "Certificate voided successfully",
        });
        fetchCertificates();
      } else {
        throw new Error(data.error || 'Failed to void certificate');
      }
    } catch (error) {
      console.error('Failed to void certificate:', error);
      toast({
        title: "Error",
        description: "Failed to void certificate",
        variant: "destructive"
      });
    }
  };

  const getStatusBadge = (certificate: ShareCertificate) => {
    if (certificate.is_void) {
      return <Badge className="bg-red-500 hover:bg-red-600">Void</Badge>;
    }
    
    const expiryDate = new Date(certificate.expiry_date);
    const now = new Date();
    const daysUntilExpiry = Math.ceil((expiryDate.getTime() - now.getTime()) / (1000 * 60 * 60 * 24));
    
    if (daysUntilExpiry < 0) {
      return <Badge className="bg-red-500 hover:bg-red-600">Expired</Badge>;
    } else if (daysUntilExpiry <= 30) {
      return <Badge className="bg-yellow-500 hover:bg-yellow-600">Expiring Soon</Badge>;
    } else {
      return <Badge className="bg-green-500 hover:bg-green-600">Active</Badge>;
    }
  };

  const getDaysUntilExpiry = (expiryDate: string) => {
    const expiry = new Date(expiryDate);
    const now = new Date();
    return Math.ceil((expiry.getTime() - now.getTime()) / (1000 * 60 * 60 * 24));
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-8">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-gold"></div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-white">Share Certificate Management</h1>
          <p className="text-gray-400">Generate and manage printable share certificates with 12-month validity</p>
        </div>
        <Button onClick={fetchCertificates} variant="outline" className="border-gray-600">
          <RefreshCw className="h-4 w-4 mr-2" />
          Refresh
        </Button>
      </div>

      {/* Stats Overview */}
      {stats && (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
          <Card className="bg-gray-800 border-gray-700">
            <CardContent className="p-4">
              <div className="flex items-center gap-3">
                <FileText className="h-8 w-8 text-blue-400" />
                <div>
                  <p className="text-sm text-gray-400">Total Certificates</p>
                  <p className="text-2xl font-bold text-white">{stats.total_certificates}</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="bg-gray-800 border-gray-700">
            <CardContent className="p-4">
              <div className="flex items-center gap-3">
                <CheckCircle className="h-8 w-8 text-green-400" />
                <div>
                  <p className="text-sm text-gray-400">Active</p>
                  <p className="text-2xl font-bold text-white">{stats.active_certificates}</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="bg-gray-800 border-gray-700">
            <CardContent className="p-4">
              <div className="flex items-center gap-3">
                <AlertTriangle className="h-8 w-8 text-red-400" />
                <div>
                  <p className="text-sm text-gray-400">Void</p>
                  <p className="text-2xl font-bold text-white">{stats.void_certificates}</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="bg-gray-800 border-gray-700">
            <CardContent className="p-4">
              <div className="flex items-center gap-3">
                <Printer className="h-8 w-8 text-purple-400" />
                <div>
                  <p className="text-sm text-gray-400">Printed</p>
                  <p className="text-2xl font-bold text-white">{stats.printed_certificates}</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="bg-gray-800 border-gray-700">
            <CardContent className="p-4">
              <div className="flex items-center gap-3">
                <Clock className="h-8 w-8 text-yellow-400" />
                <div>
                  <p className="text-sm text-gray-400">Expiring Soon</p>
                  <p className="text-2xl font-bold text-white">{stats.expiring_soon}</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="bg-gray-800 border-gray-700">
            <CardContent className="p-4">
              <div className="flex items-center gap-3">
                <Award className="h-8 w-8 text-gold" />
                <div>
                  <p className="text-sm text-gray-400">Total Value</p>
                  <p className="text-2xl font-bold text-white">${stats.total_share_value.toLocaleString()}</p>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Important Notice */}
      <Card className="bg-gradient-to-r from-yellow-500/10 to-orange-600/10 border-yellow-500/30">
        <CardContent className="p-4">
          <div className="flex items-start gap-3">
            <AlertTriangle className="h-6 w-6 text-yellow-400 mt-1" />
            <div>
              <h3 className="text-lg font-semibold text-white mb-2">Important Certificate Notice</h3>
              <p className="text-gray-300 text-sm">
                Share certificates are valid for 12 months from issue date. If you sell your NFT shares in the future, 
                your physical certificate will become <strong>null and void</strong>. Please keep certificates in a safe place 
                and only print when necessary.
              </p>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Certificates List */}
      <Card className="bg-gray-800 border-gray-700">
        <CardHeader>
          <CardTitle className="text-white flex items-center gap-2">
            <Stamp className="h-5 w-5 text-gold" />
            Share Certificates
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {certificates.map((certificate) => (
              <div key={certificate.id} className="bg-gray-700/50 rounded-lg p-4">
                <div className="flex items-center justify-between mb-3">
                  <div>
                    <h3 className="text-lg font-semibold text-white">
                      Certificate #{certificate.certificate_number}
                    </h3>
                    <p className="text-sm text-gray-400">
                      {certificate.investment_details.user_name} - {certificate.investment_details.package_name}
                    </p>
                  </div>
                  {getStatusBadge(certificate)}
                </div>
                
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm mb-4">
                  <div>
                    <p className="text-gray-400">Shares</p>
                    <p className="text-white font-medium">{certificate.shares_amount.toLocaleString()}</p>
                  </div>
                  <div>
                    <p className="text-gray-400">Total Value</p>
                    <p className="text-white font-medium">${certificate.total_value.toLocaleString()}</p>
                  </div>
                  <div>
                    <p className="text-gray-400">Issue Date</p>
                    <p className="text-white font-medium">{new Date(certificate.issue_date).toLocaleDateString()}</p>
                  </div>
                  <div>
                    <p className="text-gray-400">Expires</p>
                    <p className="text-white font-medium">
                      {new Date(certificate.expiry_date).toLocaleDateString()}
                      <span className="text-gray-400 ml-1">
                        ({getDaysUntilExpiry(certificate.expiry_date)} days)
                      </span>
                    </p>
                  </div>
                </div>

                {certificate.is_printed && (
                  <div className="mb-3">
                    <Badge className="bg-blue-500 hover:bg-blue-600">
                      <Printer className="h-3 w-3 mr-1" />
                      Printed {certificate.print_count} time{certificate.print_count !== 1 ? 's' : ''}
                    </Badge>
                  </div>
                )}

                {certificate.is_void && (
                  <div className="mb-3 p-3 bg-red-500/10 border border-red-500/30 rounded">
                    <p className="text-red-400 text-sm">
                      <strong>VOID:</strong> {certificate.void_reason}
                      {certificate.void_date && (
                        <span className="ml-2">({new Date(certificate.void_date).toLocaleDateString()})</span>
                      )}
                    </p>
                  </div>
                )}

                <div className="flex gap-2">
                  {!certificate.is_void && (
                    <>
                      <Button
                        onClick={() => downloadCertificate(certificate.id)}
                        size="sm"
                        className="bg-blue-600 hover:bg-blue-700"
                      >
                        <Download className="h-4 w-4 mr-2" />
                        Download PDF
                      </Button>
                      <Button
                        onClick={() => {
                          setSelectedCertificate(certificate);
                          setShowPreview(true);
                        }}
                        size="sm"
                        variant="outline"
                        className="border-gray-600"
                      >
                        <Eye className="h-4 w-4 mr-2" />
                        Preview
                      </Button>
                      <Button
                        onClick={() => voidCertificate(certificate.id, 'Manual void by admin')}
                        size="sm"
                        className="bg-red-600 hover:bg-red-700"
                      >
                        <AlertTriangle className="h-4 w-4 mr-2" />
                        Void
                      </Button>
                    </>
                  )}
                </div>
              </div>
            ))}
            
            {certificates.length === 0 && (
              <div className="text-center py-8">
                <FileText className="h-12 w-12 text-gray-600 mx-auto mb-4" />
                <p className="text-gray-400">No certificates generated yet</p>
              </div>
            )}
          </div>
        </CardContent>
      </Card>
    </div>
  );
};

export default ShareCertificateGenerator;

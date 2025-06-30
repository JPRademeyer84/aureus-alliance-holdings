import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { useToast } from '@/hooks/use-toast';
import { 
  Package, 
  CheckCircle, 
  XCircle, 
  Eye, 
  Clock, 
  DollarSign,
  FileText,
  User,
  Calendar,
  AlertTriangle,
  RefreshCw,
  Search,
  TrendingUp,
  Award,
  Wallet
} from 'lucide-react';
import ApiConfig from '@/config/api';

interface Investment {
  id: string;
  user_id: string;
  username: string;
  email: string;
  package_name: string;
  amount: number;
  shares: number;
  status: 'pending' | 'confirmed' | 'active' | 'completed' | 'cancelled';
  payment_method: 'crypto' | 'bank' | 'manual';
  payment_status: string;
  wallet_address?: string;
  transaction_hash?: string;
  bank_reference?: string;
  roi_amount: number;
  nft_delivery_date: string;
  roi_delivery_date: string;
  created_at: string;
  confirmed_at?: string;
  admin_notes?: string;
}

const InvestmentManager: React.FC = () => {
  const [investments, setInvestments] = useState<Investment[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [selectedInvestment, setSelectedInvestment] = useState<Investment | null>(null);
  const [showActionDialog, setShowActionDialog] = useState(false);
  const [actionType, setActionType] = useState<'confirm' | 'cancel' | 'activate' | 'complete'>('confirm');
  const [adminNotes, setAdminNotes] = useState('');
  const [processing, setProcessing] = useState<string | null>(null);
  const { toast } = useToast();

  useEffect(() => {
    loadInvestments();
  }, []);

  const loadInvestments = async () => {
    try {
      const response = await fetch(`${ApiConfig.baseUrl}/admin/investments.php?action=list`);
      const data = await response.json();
      
      if (data.success) {
        setInvestments(data.investments || []);
      } else {
        throw new Error(data.error || 'Failed to load investments');
      }
    } catch (error) {
      console.error('Error loading investments:', error);
      toast({
        title: 'Error',
        description: 'Failed to load investments',
        variant: 'destructive',
      });
    } finally {
      setLoading(false);
    }
  };

  const handleInvestmentAction = async (investmentId: string, action: string) => {
    try {
      setProcessing(investmentId);
      
      const response = await fetch(`${ApiConfig.baseUrl}/admin/investments.php`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          action: 'update_status',
          investment_id: investmentId,
          status: action,
          admin_notes: adminNotes,
          admin_id: 'admin' // Should be actual admin ID
        }),
      });

      const data = await response.json();
      
      if (data.success) {
        toast({
          title: 'Success',
          description: `Investment ${action} successfully`,
        });
        
        loadInvestments();
        setShowActionDialog(false);
        setAdminNotes('');
        setSelectedInvestment(null);
      } else {
        throw new Error(data.error || `Failed to ${action} investment`);
      }
    } catch (error) {
      console.error(`Error ${action}ing investment:`, error);
      toast({
        title: 'Error',
        description: error instanceof Error ? error.message : `Failed to ${action} investment`,
        variant: 'destructive',
      });
    } finally {
      setProcessing(null);
    }
  };

  const generateCertificate = async (investmentId: string) => {
    try {
      setProcessing(investmentId);
      
      const response = await fetch(`${ApiConfig.baseUrl}/admin/generate-certificate.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          investment_id: investmentId,
          admin_id: 'admin'
        }),
      });

      const data = await response.json();
      
      if (data.success) {
        toast({
          title: 'Success',
          description: 'Certificate generated successfully',
        });
      } else {
        throw new Error(data.error || 'Failed to generate certificate');
      }
    } catch (error) {
      console.error('Error generating certificate:', error);
      toast({
        title: 'Error',
        description: 'Failed to generate certificate',
        variant: 'destructive',
      });
    } finally {
      setProcessing(null);
    }
  };

  const getStatusBadge = (status: string) => {
    const variants: Record<string, { variant: any; icon: React.ReactNode; color: string }> = {
      pending: { variant: 'secondary', icon: <Clock className="w-3 h-3" />, color: 'text-yellow-600' },
      confirmed: { variant: 'default', icon: <CheckCircle className="w-3 h-3" />, color: 'text-blue-600' },
      active: { variant: 'default', icon: <TrendingUp className="w-3 h-3" />, color: 'text-green-600' },
      completed: { variant: 'default', icon: <Award className="w-3 h-3" />, color: 'text-purple-600' },
      cancelled: { variant: 'destructive', icon: <XCircle className="w-3 h-3" />, color: 'text-red-600' },
    };

    const config = variants[status] || { variant: 'secondary', icon: <AlertTriangle className="w-3 h-3" />, color: 'text-gray-600' };
    
    return (
      <Badge variant={config.variant} className={`flex items-center gap-1 ${config.color}`}>
        {config.icon}
        {status.replace('_', ' ').toUpperCase()}
      </Badge>
    );
  };

  const filteredInvestments = investments.filter(investment => {
    const matchesSearch = investment.username.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         investment.email.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         investment.package_name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         investment.id.toLowerCase().includes(searchTerm.toLowerCase());
    
    const matchesStatus = statusFilter === 'all' || investment.status === statusFilter;
    
    return matchesSearch && matchesStatus;
  });

  const openActionDialog = (investment: Investment, action: 'confirm' | 'cancel' | 'activate' | 'complete') => {
    setSelectedInvestment(investment);
    setActionType(action);
    setAdminNotes('');
    setShowActionDialog(true);
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center p-8">
        <RefreshCw className="w-8 h-8 animate-spin" />
        <span className="ml-2">Loading investments...</span>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-3xl font-bold tracking-tight">Investment Management</h2>
          <p className="text-muted-foreground">
            Manually manage and process user investments for pre-launch operations
          </p>
        </div>
        
        <Button onClick={loadInvestments} variant="outline">
          <RefreshCw className="w-4 h-4 mr-2" />
          Refresh
        </Button>
      </div>

      {/* Filters */}
      <div className="flex items-center gap-4">
        <div className="relative flex-1 max-w-sm">
          <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground w-4 h-4" />
          <Input
            placeholder="Search investments..."
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
            <SelectItem value="confirmed">Confirmed</SelectItem>
            <SelectItem value="active">Active</SelectItem>
            <SelectItem value="completed">Completed</SelectItem>
            <SelectItem value="cancelled">Cancelled</SelectItem>
          </SelectContent>
        </Select>
      </div>

      {/* Statistics Cards */}
      <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center gap-2">
              <Clock className="w-4 h-4 text-yellow-500" />
              <div>
                <p className="text-sm text-muted-foreground">Pending</p>
                <p className="text-2xl font-bold">
                  {investments.filter(i => i.status === 'pending').length}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
        
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center gap-2">
              <CheckCircle className="w-4 h-4 text-blue-500" />
              <div>
                <p className="text-sm text-muted-foreground">Confirmed</p>
                <p className="text-2xl font-bold">
                  {investments.filter(i => i.status === 'confirmed').length}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
        
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center gap-2">
              <TrendingUp className="w-4 h-4 text-green-500" />
              <div>
                <p className="text-sm text-muted-foreground">Active</p>
                <p className="text-2xl font-bold">
                  {investments.filter(i => i.status === 'active').length}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
        
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center gap-2">
              <DollarSign className="w-4 h-4 text-purple-500" />
              <div>
                <p className="text-sm text-muted-foreground">Total Value</p>
                <p className="text-2xl font-bold">
                  ${investments.reduce((sum, i) => sum + i.amount, 0).toLocaleString()}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>
        
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center gap-2">
              <Package className="w-4 h-4 text-orange-500" />
              <div>
                <p className="text-sm text-muted-foreground">Total Investments</p>
                <p className="text-2xl font-bold">{investments.length}</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Investments Table */}
      <Card>
        <CardHeader>
          <CardTitle>Investment Records</CardTitle>
          <CardDescription>
            {filteredInvestments.length} investment(s) found
          </CardDescription>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>User</TableHead>
                <TableHead>Package</TableHead>
                <TableHead>Amount</TableHead>
                <TableHead>Payment Method</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Date</TableHead>
                <TableHead>Actions</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {filteredInvestments.map((investment) => (
                <TableRow key={investment.id}>
                  <TableCell>
                    <div>
                      <div className="font-medium">{investment.username}</div>
                      <div className="text-sm text-muted-foreground">{investment.email}</div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <div>
                      <div className="font-medium">{investment.package_name}</div>
                      <div className="text-sm text-muted-foreground">{investment.shares} shares</div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <div>
                      <div className="font-medium">${investment.amount.toLocaleString()}</div>
                      <div className="text-sm text-muted-foreground">ROI: ${investment.roi_amount.toLocaleString()}</div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <div className="flex items-center gap-2">
                      {investment.payment_method === 'crypto' && <Wallet className="w-4 h-4" />}
                      {investment.payment_method === 'bank' && <FileText className="w-4 h-4" />}
                      <span className="capitalize">{investment.payment_method}</span>
                    </div>
                  </TableCell>
                  <TableCell>{getStatusBadge(investment.status)}</TableCell>
                  <TableCell>
                    <div className="text-sm">
                      <div>{new Date(investment.created_at).toLocaleDateString()}</div>
                      {investment.confirmed_at && (
                        <div className="text-muted-foreground">
                          Confirmed: {new Date(investment.confirmed_at).toLocaleDateString()}
                        </div>
                      )}
                    </div>
                  </TableCell>
                  <TableCell>
                    <div className="flex items-center gap-2">
                      {investment.status === 'pending' && (
                        <>
                          <Button
                            variant="outline"
                            size="sm"
                            onClick={() => openActionDialog(investment, 'confirm')}
                            disabled={processing === investment.id}
                          >
                            <CheckCircle className="w-4 h-4" />
                          </Button>
                          <Button
                            variant="outline"
                            size="sm"
                            onClick={() => openActionDialog(investment, 'cancel')}
                            disabled={processing === investment.id}
                          >
                            <XCircle className="w-4 h-4" />
                          </Button>
                        </>
                      )}
                      
                      {investment.status === 'confirmed' && (
                        <>
                          <Button
                            variant="outline"
                            size="sm"
                            onClick={() => openActionDialog(investment, 'activate')}
                            disabled={processing === investment.id}
                          >
                            <TrendingUp className="w-4 h-4" />
                          </Button>
                          <Button
                            variant="outline"
                            size="sm"
                            onClick={() => generateCertificate(investment.id)}
                            disabled={processing === investment.id}
                          >
                            <FileText className="w-4 h-4" />
                          </Button>
                        </>
                      )}
                      
                      {investment.status === 'active' && (
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={() => openActionDialog(investment, 'complete')}
                          disabled={processing === investment.id}
                        >
                          <Award className="w-4 h-4" />
                        </Button>
                      )}
                    </div>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
          
          {filteredInvestments.length === 0 && (
            <div className="text-center py-8 text-muted-foreground">
              No investments found matching your criteria
            </div>
          )}
        </CardContent>
      </Card>

      {/* Action Dialog */}
      <Dialog open={showActionDialog} onOpenChange={setShowActionDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>
              {actionType === 'confirm' && 'Confirm Investment'}
              {actionType === 'cancel' && 'Cancel Investment'}
              {actionType === 'activate' && 'Activate Investment'}
              {actionType === 'complete' && 'Complete Investment'}
            </DialogTitle>
            <DialogDescription>
              {actionType === 'confirm' && 'Confirm this investment and activate the 180-day countdown'}
              {actionType === 'cancel' && 'Cancel this investment and refund the user'}
              {actionType === 'activate' && 'Activate this investment and start ROI tracking'}
              {actionType === 'complete' && 'Mark this investment as completed'}
            </DialogDescription>
          </DialogHeader>
          
          {selectedInvestment && (
            <div className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="text-sm font-medium text-muted-foreground">User</label>
                  <p>{selectedInvestment.username}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">Package</label>
                  <p>{selectedInvestment.package_name}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">Amount</label>
                  <p>${selectedInvestment.amount.toLocaleString()}</p>
                </div>
                <div>
                  <label className="text-sm font-medium text-muted-foreground">Shares</label>
                  <p>{selectedInvestment.shares}</p>
                </div>
              </div>
              
              <div>
                <label className="text-sm font-medium text-muted-foreground">Admin Notes</label>
                <Textarea
                  value={adminNotes}
                  onChange={(e) => setAdminNotes(e.target.value)}
                  placeholder="Add notes about this action..."
                  rows={3}
                />
              </div>
              
              <div className="flex gap-4">
                <Button
                  onClick={() => handleInvestmentAction(selectedInvestment.id, actionType)}
                  disabled={processing === selectedInvestment.id}
                  className="flex-1"
                  variant={actionType === 'cancel' ? 'destructive' : 'default'}
                >
                  {processing === selectedInvestment.id ? 'Processing...' : `${actionType.charAt(0).toUpperCase() + actionType.slice(1)} Investment`}
                </Button>
                <Button
                  variant="outline"
                  onClick={() => setShowActionDialog(false)}
                  className="flex-1"
                >
                  Cancel
                </Button>
              </div>
            </div>
          )}
        </DialogContent>
      </Dialog>
    </div>
  );
};

export default InvestmentManager;

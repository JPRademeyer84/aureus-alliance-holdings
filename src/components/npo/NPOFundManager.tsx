import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { useToast } from '@/hooks/use-toast';
import {
  Heart,
  DollarSign,
  Globe,
  TrendingUp,
  Users,
  Calendar,
  CheckCircle,
  Clock,
  Send,
  RefreshCw,
  Plus,
  Eye,
  Download
} from 'lucide-react';

interface NPOFund {
  id: number;
  transaction_id: string;
  source_investment_id: number;
  phase_id: number;
  amount: number;
  percentage: number;
  status: 'pending' | 'allocated' | 'distributed';
  npo_recipient: string | null;
  distribution_date: string | null;
  notes: string | null;
  created_at: string;
  updated_at: string;
}

interface NPOStats {
  total_fund_balance: number;
  pending_allocations: number;
  distributed_amount: number;
  total_donations: number;
  beneficiary_count: number;
  this_month_contributions: number;
}

interface NPORecipient {
  id: number;
  name: string;
  description: string;
  website: string;
  contact_email: string;
  total_received: number;
  last_donation_date: string;
  is_active: boolean;
}

const NPOFundManager: React.FC = () => {
  const [fundEntries, setFundEntries] = useState<NPOFund[]>([]);
  const [stats, setStats] = useState<NPOStats | null>(null);
  const [recipients, setRecipients] = useState<NPORecipient[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [selectedEntry, setSelectedEntry] = useState<NPOFund | null>(null);
  const [showDistributionForm, setShowDistributionForm] = useState(false);
  const { toast } = useToast();

  useEffect(() => {
    fetchNPOData();
  }, []);

  const fetchNPOData = async () => {
    setIsLoading(true);
    try {
      const response = await fetch('/api/npo/fund-balance.php', {
        credentials: 'include'
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const data = await response.json();

      if (data.success) {
        setFundEntries(data.fund_entries || []);
        setStats(data.stats || null);
        setRecipients(data.recipients || []);
      } else {
        throw new Error(data.error || 'Failed to fetch NPO data');
      }
    } catch (error) {
      console.error('Failed to fetch NPO data:', error);
      toast({
        title: "Error",
        description: "Failed to load NPO fund data",
        variant: "destructive"
      });
    } finally {
      setIsLoading(false);
    }
  };

  const distributeToNPO = async (fundId: number, npoRecipient: string, notes: string) => {
    try {
      const response = await fetch('/api/npo/distribute.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({
          fund_id: fundId,
          npo_recipient: npoRecipient,
          notes: notes
        })
      });

      const data = await response.json();

      if (data.success) {
        toast({
          title: "Success",
          description: "Funds distributed to NPO successfully",
        });
        setShowDistributionForm(false);
        setSelectedEntry(null);
        fetchNPOData();
      } else {
        throw new Error(data.error || 'Failed to distribute funds');
      }
    } catch (error) {
      console.error('Failed to distribute funds:', error);
      toast({
        title: "Error",
        description: "Failed to distribute funds",
        variant: "destructive"
      });
    }
  };

  const getStatusBadge = (status: string) => {
    const statusConfig = {
      pending: { color: 'bg-yellow-500 hover:bg-yellow-600', icon: Clock },
      allocated: { color: 'bg-blue-500 hover:bg-blue-600', icon: CheckCircle },
      distributed: { color: 'bg-green-500 hover:bg-green-600', icon: Send }
    };

    const config = statusConfig[status as keyof typeof statusConfig] || statusConfig.pending;
    const Icon = config.icon;

    return (
      <Badge className={config.color}>
        <Icon className="h-3 w-3 mr-1" />
        {status.charAt(0).toUpperCase() + status.slice(1)}
      </Badge>
    );
  };

  const exportReport = async () => {
    try {
      const response = await fetch('/api/npo/export-report.php', {
        credentials: 'include'
      });

      if (response.ok) {
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `npo-fund-report-${new Date().toISOString().split('T')[0]}.csv`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);

        toast({
          title: "Success",
          description: "NPO fund report exported successfully",
        });
      } else {
        throw new Error('Failed to export report');
      }
    } catch (error) {
      console.error('Failed to export report:', error);
      toast({
        title: "Error",
        description: "Failed to export report",
        variant: "destructive"
      });
    }
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
          <h1 className="text-3xl font-bold text-white">NPO Fund Management</h1>
          <p className="text-gray-400">Manage charity fund with 10% allocation from each sale</p>
        </div>
        <div className="flex gap-2">
          <Button onClick={exportReport} variant="outline" className="border-gray-600">
            <Download className="h-4 w-4 mr-2" />
            Export Report
          </Button>
          <Button onClick={fetchNPOData} variant="outline" className="border-gray-600">
            <RefreshCw className="h-4 w-4 mr-2" />
            Refresh
          </Button>
        </div>
      </div>

      {/* Stats Overview */}
      {stats && (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
          <Card className="bg-gray-800 border-gray-700">
            <CardContent className="p-4">
              <div className="flex items-center gap-3">
                <Heart className="h-8 w-8 text-purple-400" />
                <div>
                  <p className="text-sm text-gray-400">Total Fund</p>
                  <p className="text-2xl font-bold text-white">${stats.total_fund_balance.toLocaleString()}</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="bg-gray-800 border-gray-700">
            <CardContent className="p-4">
              <div className="flex items-center gap-3">
                <Clock className="h-8 w-8 text-yellow-400" />
                <div>
                  <p className="text-sm text-gray-400">Pending</p>
                  <p className="text-2xl font-bold text-white">${stats.pending_allocations.toLocaleString()}</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="bg-gray-800 border-gray-700">
            <CardContent className="p-4">
              <div className="flex items-center gap-3">
                <Send className="h-8 w-8 text-green-400" />
                <div>
                  <p className="text-sm text-gray-400">Distributed</p>
                  <p className="text-2xl font-bold text-white">${stats.distributed_amount.toLocaleString()}</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="bg-gray-800 border-gray-700">
            <CardContent className="p-4">
              <div className="flex items-center gap-3">
                <DollarSign className="h-8 w-8 text-gold" />
                <div>
                  <p className="text-sm text-gray-400">Total Donations</p>
                  <p className="text-2xl font-bold text-white">{stats.total_donations}</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="bg-gray-800 border-gray-700">
            <CardContent className="p-4">
              <div className="flex items-center gap-3">
                <Globe className="h-8 w-8 text-blue-400" />
                <div>
                  <p className="text-sm text-gray-400">NPO Partners</p>
                  <p className="text-2xl font-bold text-white">{stats.beneficiary_count}</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="bg-gray-800 border-gray-700">
            <CardContent className="p-4">
              <div className="flex items-center gap-3">
                <TrendingUp className="h-8 w-8 text-purple-400" />
                <div>
                  <p className="text-sm text-gray-400">This Month</p>
                  <p className="text-2xl font-bold text-white">${stats.this_month_contributions.toLocaleString()}</p>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Fund Entries and NPO Recipients */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Fund Entries */}
        <Card className="bg-gray-800 border-gray-700">
          <CardHeader>
            <CardTitle className="text-white flex items-center gap-2">
              <Heart className="h-5 w-5 text-purple-400" />
              Fund Allocations
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-4 max-h-96 overflow-y-auto">
              {fundEntries.slice(0, 20).map((entry) => (
                <div key={entry.id} className="bg-gray-700/50 rounded-lg p-4">
                  <div className="flex items-center justify-between mb-3">
                    <div>
                      <p className="text-white font-medium">${entry.amount.toLocaleString()}</p>
                      <p className="text-sm text-gray-400">Transaction: {entry.transaction_id}</p>
                    </div>
                    {getStatusBadge(entry.status)}
                  </div>
                  
                  <div className="grid grid-cols-2 gap-4 text-sm mb-3">
                    <div>
                      <p className="text-gray-400">Percentage</p>
                      <p className="text-white">{entry.percentage}%</p>
                    </div>
                    <div>
                      <p className="text-gray-400">Created</p>
                      <p className="text-white">{new Date(entry.created_at).toLocaleDateString()}</p>
                    </div>
                  </div>

                  {entry.npo_recipient && (
                    <div className="mb-3">
                      <p className="text-sm text-gray-400">Recipient</p>
                      <p className="text-white">{entry.npo_recipient}</p>
                    </div>
                  )}

                  {entry.status === 'pending' && (
                    <Button
                      onClick={() => {
                        setSelectedEntry(entry);
                        setShowDistributionForm(true);
                      }}
                      size="sm"
                      className="bg-purple-600 hover:bg-purple-700"
                    >
                      <Send className="h-4 w-4 mr-2" />
                      Distribute
                    </Button>
                  )}
                </div>
              ))}
              
              {fundEntries.length === 0 && (
                <div className="text-center py-8">
                  <Heart className="h-12 w-12 text-gray-600 mx-auto mb-4" />
                  <p className="text-gray-400">No fund entries yet</p>
                </div>
              )}
            </div>
          </CardContent>
        </Card>

        {/* NPO Recipients */}
        <Card className="bg-gray-800 border-gray-700">
          <CardHeader>
            <CardTitle className="text-white flex items-center gap-2">
              <Globe className="h-5 w-5 text-blue-400" />
              NPO Partners
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-4 max-h-96 overflow-y-auto">
              {recipients.map((recipient) => (
                <div key={recipient.id} className="bg-gray-700/50 rounded-lg p-4">
                  <div className="flex items-center justify-between mb-3">
                    <div>
                      <h3 className="text-white font-medium">{recipient.name}</h3>
                      <p className="text-sm text-gray-400">{recipient.description}</p>
                    </div>
                    <Badge className={recipient.is_active ? 'bg-green-500' : 'bg-gray-500'}>
                      {recipient.is_active ? 'Active' : 'Inactive'}
                    </Badge>
                  </div>
                  
                  <div className="grid grid-cols-2 gap-4 text-sm mb-3">
                    <div>
                      <p className="text-gray-400">Total Received</p>
                      <p className="text-white font-medium">${recipient.total_received.toLocaleString()}</p>
                    </div>
                    <div>
                      <p className="text-gray-400">Last Donation</p>
                      <p className="text-white">{recipient.last_donation_date ? new Date(recipient.last_donation_date).toLocaleDateString() : 'Never'}</p>
                    </div>
                  </div>

                  {recipient.website && (
                    <div className="mb-3">
                      <a 
                        href={recipient.website} 
                        target="_blank" 
                        rel="noopener noreferrer"
                        className="text-blue-400 hover:text-blue-300 text-sm"
                      >
                        Visit Website →
                      </a>
                    </div>
                  )}

                  <Button
                    onClick={() => window.open(`mailto:${recipient.contact_email}`, '_blank')}
                    size="sm"
                    variant="outline"
                    className="border-gray-600"
                  >
                    <Send className="h-4 w-4 mr-2" />
                    Contact
                  </Button>
                </div>
              ))}
              
              {recipients.length === 0 && (
                <div className="text-center py-8">
                  <Globe className="h-12 w-12 text-gray-600 mx-auto mb-4" />
                  <p className="text-gray-400">No NPO partners yet</p>
                </div>
              )}
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Distribution Form Modal */}
      {showDistributionForm && selectedEntry && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
          <Card className="bg-gray-800 border-gray-700 w-full max-w-md mx-4">
            <CardHeader>
              <CardTitle className="text-white">Distribute to NPO</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                <div>
                  <p className="text-sm text-gray-400">Amount to Distribute</p>
                  <p className="text-2xl font-bold text-white">${selectedEntry.amount.toLocaleString()}</p>
                </div>
                
                <div>
                  <label className="text-sm text-gray-400">NPO Recipient</label>
                  <select className="w-full mt-1 p-2 bg-gray-700 border border-gray-600 rounded text-white">
                    <option value="">Select NPO...</option>
                    {recipients.filter(r => r.is_active).map(recipient => (
                      <option key={recipient.id} value={recipient.name}>
                        {recipient.name}
                      </option>
                    ))}
                  </select>
                </div>

                <div>
                  <label className="text-sm text-gray-400">Notes</label>
                  <Textarea
                    placeholder="Distribution notes..."
                    className="bg-gray-700 border-gray-600 text-white"
                    rows={3}
                  />
                </div>

                <div className="flex gap-2">
                  <Button
                    onClick={() => {
                      // Implementation would go here
                      setShowDistributionForm(false);
                      setSelectedEntry(null);
                    }}
                    className="bg-purple-600 hover:bg-purple-700 flex-1"
                  >
                    <Send className="h-4 w-4 mr-2" />
                    Distribute
                  </Button>
                  <Button
                    onClick={() => {
                      setShowDistributionForm(false);
                      setSelectedEntry(null);
                    }}
                    variant="outline"
                    className="border-gray-600"
                  >
                    Cancel
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      )}
    </div>
  );
};

export default NPOFundManager;

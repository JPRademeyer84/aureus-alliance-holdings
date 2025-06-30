import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useToast } from '@/hooks/use-toast';
import { Search, FileText, Shield, CheckCircle, XCircle, Calendar, User, Wallet } from 'lucide-react';
import ApiConfig from '@/config/api';

interface TermsAcceptanceRecord {
  id: string;
  user_id: string | null;
  email: string;
  wallet_address: string;
  investment_id: string | null;
  gold_mining_investment_accepted: boolean;
  nft_shares_understanding_accepted: boolean;
  delivery_timeline_accepted: boolean;
  dividend_timeline_accepted: boolean;
  risk_acknowledgment_accepted: boolean;
  all_terms_accepted: boolean;
  ip_address: string;
  acceptance_timestamp: string;
  terms_version: string;
  created_at: string;
}

const TermsComplianceManager: React.FC = () => {
  const [records, setRecords] = useState<TermsAcceptanceRecord[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [searchType, setSearchType] = useState<'email' | 'wallet_address' | 'investment_id'>('email');
  const { toast } = useToast();

  useEffect(() => {
    if (searchTerm.trim()) {
      fetchRecords();
    } else {
      setRecords([]);
      setIsLoading(false);
    }
  }, [searchTerm, searchType]);

  const fetchRecords = async () => {
    if (!searchTerm.trim()) return;
    
    setIsLoading(true);
    try {
      const params = new URLSearchParams();
      params.append(searchType, searchTerm);

      const response = await fetch(`${ApiConfig.endpoints.investments.termsAcceptance}?${params}`);
      const data = await response.json();

      if (data.success) {
        setRecords(data.data.terms_acceptance_records || []);
      } else {
        throw new Error(data.error || 'Failed to fetch records');
      }
    } catch (error) {
      console.error('Error fetching terms records:', error);
      toast({
        title: 'Error',
        description: 'Failed to fetch terms acceptance records',
        variant: 'destructive',
      });
    } finally {
      setIsLoading(false);
    }
  };

  const handleSearch = () => {
    fetchRecords();
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString();
  };

  const getComplianceStatus = (record: TermsAcceptanceRecord) => {
    if (record.all_terms_accepted) {
      return <Badge className="bg-green-500/20 text-green-400 border-green-500/30">Fully Compliant</Badge>;
    } else {
      return <Badge variant="destructive">Non-Compliant</Badge>;
    }
  };

  const getTermStatus = (accepted: boolean) => {
    return accepted ? (
      <CheckCircle className="h-4 w-4 text-green-400" />
    ) : (
      <XCircle className="h-4 w-4 text-red-400" />
    );
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold text-white">Terms & Conditions Compliance</h2>
          <p className="text-gray-400 mt-1">Monitor and audit terms acceptance records for regulatory compliance</p>
        </div>
        <Badge variant="outline" className="text-blue-400 border-blue-400">
          <FileText className="h-4 w-4 mr-1" />
          Version 1.0
        </Badge>
      </div>

      {/* Search Section */}
      <Card className="bg-black/40 border-gray-700">
        <CardHeader>
          <CardTitle className="flex items-center gap-2 text-white">
            <Search className="h-5 w-5" />
            Search Terms Acceptance Records
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="flex gap-4">
            <div className="flex-1">
              <Input
                placeholder={`Enter ${searchType.replace('_', ' ')}...`}
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="bg-gray-800 border-gray-600 text-white"
                onKeyPress={(e) => e.key === 'Enter' && handleSearch()}
              />
            </div>
            <select
              value={searchType}
              onChange={(e) => setSearchType(e.target.value as any)}
              className="px-3 py-2 bg-gray-800 border border-gray-600 rounded-md text-white"
            >
              <option value="email">Email</option>
              <option value="wallet_address">Wallet Address</option>
              <option value="investment_id">Investment ID</option>
            </select>
            <Button onClick={handleSearch} disabled={!searchTerm.trim() || isLoading}>
              {isLoading ? 'Searching...' : 'Search'}
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Results */}
      {records.length > 0 && (
        <Card className="bg-black/40 border-gray-700">
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-white">
              <Shield className="h-5 w-5" />
              Terms Acceptance Records ({records.length})
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="overflow-x-auto">
              <Table>
                <TableHeader>
                  <TableRow className="border-gray-700">
                    <TableHead className="text-gray-300">User Info</TableHead>
                    <TableHead className="text-gray-300">Compliance Status</TableHead>
                    <TableHead className="text-gray-300">Terms Acceptance</TableHead>
                    <TableHead className="text-gray-300">Metadata</TableHead>
                    <TableHead className="text-gray-300">Date</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {records.map((record) => (
                    <TableRow key={record.id} className="border-gray-700">
                      <TableCell>
                        <div className="space-y-1">
                          <div className="flex items-center gap-2 text-sm">
                            <User className="h-4 w-4 text-gray-400" />
                            <span className="text-white">{record.email}</span>
                          </div>
                          <div className="flex items-center gap-2 text-xs text-gray-400">
                            <Wallet className="h-3 w-3" />
                            <span>{record.wallet_address.substring(0, 10)}...{record.wallet_address.substring(record.wallet_address.length - 8)}</span>
                          </div>
                          {record.investment_id && (
                            <div className="text-xs text-blue-400">
                              Investment: {record.investment_id}
                            </div>
                          )}
                        </div>
                      </TableCell>
                      <TableCell>
                        {getComplianceStatus(record)}
                      </TableCell>
                      <TableCell>
                        <div className="grid grid-cols-5 gap-2">
                          <div className="flex flex-col items-center gap-1">
                            {getTermStatus(record.gold_mining_investment_accepted)}
                            <span className="text-xs text-gray-400">Mining</span>
                          </div>
                          <div className="flex flex-col items-center gap-1">
                            {getTermStatus(record.nft_shares_understanding_accepted)}
                            <span className="text-xs text-gray-400">NFT</span>
                          </div>
                          <div className="flex flex-col items-center gap-1">
                            {getTermStatus(record.delivery_timeline_accepted)}
                            <span className="text-xs text-gray-400">180d</span>
                          </div>
                          <div className="flex flex-col items-center gap-1">
                            {getTermStatus(record.dividend_timeline_accepted)}
                            <span className="text-xs text-gray-400">Q1'26</span>
                          </div>
                          <div className="flex flex-col items-center gap-1">
                            {getTermStatus(record.risk_acknowledgment_accepted)}
                            <span className="text-xs text-gray-400">Risk</span>
                          </div>
                        </div>
                      </TableCell>
                      <TableCell>
                        <div className="space-y-1 text-xs text-gray-400">
                          <div>IP: {record.ip_address}</div>
                          <div>v{record.terms_version}</div>
                        </div>
                      </TableCell>
                      <TableCell>
                        <div className="flex items-center gap-2 text-sm text-gray-300">
                          <Calendar className="h-4 w-4" />
                          <span>{formatDate(record.acceptance_timestamp)}</span>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          </CardContent>
        </Card>
      )}

      {/* No Results */}
      {!isLoading && searchTerm && records.length === 0 && (
        <Card className="bg-black/40 border-gray-700">
          <CardContent className="text-center py-8">
            <FileText className="h-12 w-12 text-gray-400 mx-auto mb-4" />
            <h3 className="text-lg font-medium text-white mb-2">No Records Found</h3>
            <p className="text-gray-400">
              No terms acceptance records found for "{searchTerm}". 
              Try searching with a different {searchType.replace('_', ' ')}.
            </p>
          </CardContent>
        </Card>
      )}

      {/* Instructions */}
      {!searchTerm && (
        <Card className="bg-blue-500/10 border-blue-500/30">
          <CardContent className="p-6">
            <div className="flex items-start gap-3">
              <Shield className="h-6 w-6 text-blue-400 mt-1 flex-shrink-0" />
              <div>
                <h3 className="text-blue-400 font-semibold text-lg mb-2">Compliance Monitoring</h3>
                <p className="text-blue-300 mb-4">
                  Use this tool to search and audit terms and conditions acceptance records for regulatory compliance.
                  All investment transactions require complete terms acceptance.
                </p>
                <ul className="text-blue-300 text-sm space-y-1">
                  <li>• Search by email, wallet address, or investment ID</li>
                  <li>• View detailed acceptance status for each term</li>
                  <li>• Monitor compliance across all investments</li>
                  <li>• Export records for regulatory reporting</li>
                </ul>
              </div>
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
};

export default TermsComplianceManager;

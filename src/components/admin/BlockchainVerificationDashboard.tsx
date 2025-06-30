import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { useToast } from "@/hooks/use-toast";
import ApiConfig from "@/config/api";
import {
  CheckCircle,
  XCircle,
  AlertTriangle,
  Clock,
  Shield,
  Link,
  DollarSign,
  Hash,
  Wallet,
  RefreshCw,
  Eye,
  ExternalLink
} from "lucide-react";

interface VerificationResult {
  payment_id: string;
  transaction_hash: string;
  amount_usd: number;
  chain: string;
  verification_status: string;
  blockchain_verified: boolean;
  verification_confidence: number;
  verification_checks: {
    no_duplicates?: boolean;
    transaction_exists?: boolean;
    sender_verified?: boolean;
    recipient_verified?: boolean;
    amount_verified?: boolean;
    confirmed?: boolean;
    time_valid?: boolean;
  };
  verification_errors: string[];
  created_at: string;
  blockchain_data?: any;
}

const BlockchainVerificationDashboard: React.FC = () => {
  const { toast } = useToast();
  const [verifications, setVerifications] = useState<VerificationResult[]>([]);
  const [loading, setLoading] = useState(true);
  const [selectedVerification, setSelectedVerification] = useState<VerificationResult | null>(null);

  useEffect(() => {
    fetchVerifications();
  }, []);

  const fetchVerifications = async () => {
    try {
      setLoading(true);
      const response = await fetch(`${ApiConfig.baseUrl}/admin/blockchain-verifications.php`);
      const data = await response.json();
      
      if (data.success) {
        setVerifications(data.data || []);
      } else {
        throw new Error(data.error || 'Failed to fetch verifications');
      }
    } catch (error) {
      console.error('Failed to fetch blockchain verifications:', error);
      toast({
        title: "Error",
        description: "Failed to load blockchain verifications",
        variant: "destructive"
      });
    } finally {
      setLoading(false);
    }
  };

  const getStatusIcon = (verification: VerificationResult) => {
    if (verification.blockchain_verified) {
      return <CheckCircle className="h-5 w-5 text-green-400" />;
    } else if (verification.verification_status === 'manual_review_required') {
      return <AlertTriangle className="h-5 w-5 text-yellow-400" />;
    } else if (verification.verification_status === 'blockchain_failed') {
      return <XCircle className="h-5 w-5 text-red-400" />;
    } else {
      return <Clock className="h-5 w-5 text-blue-400" />;
    }
  };

  const getStatusBadge = (verification: VerificationResult) => {
    if (verification.blockchain_verified) {
      return <Badge className="bg-green-600">Blockchain Verified</Badge>;
    } else if (verification.verification_status === 'manual_review_required') {
      return <Badge className="bg-yellow-600">Manual Review</Badge>;
    } else if (verification.verification_status === 'blockchain_failed') {
      return <Badge className="bg-red-600">Failed</Badge>;
    } else {
      return <Badge className="bg-blue-600">Pending</Badge>;
    }
  };

  const openBlockchainExplorer = (hash: string, chain: string) => {
    const explorers = {
      ethereum: `https://etherscan.io/tx/${hash}`,
      bsc: `https://bscscan.com/tx/${hash}`,
      polygon: `https://polygonscan.com/tx/${hash}`,
      tron: `https://tronscan.org/#/transaction/${hash}`
    };
    
    const url = explorers[chain as keyof typeof explorers];
    if (url) {
      window.open(url, '_blank');
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center p-8">
        <div className="text-white">Loading blockchain verifications...</div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <h2 className="text-2xl font-bold text-white flex items-center gap-2">
          <Shield className="h-6 w-6" />
          Blockchain Verification Dashboard
        </h2>
        <Button 
          onClick={fetchVerifications} 
          variant="outline" 
          className="text-white border-gray-600"
        >
          <RefreshCw className="h-4 w-4 mr-2" />
          Refresh
        </Button>
      </div>

      {/* Verification List */}
      <div className="grid grid-cols-1 gap-4">
        {verifications.length > 0 ? (
          verifications.map((verification) => (
            <Card key={verification.payment_id} className="bg-gray-800/50 border-gray-700">
              <CardContent className="p-6">
                <div className="flex items-start justify-between">
                  <div className="flex items-start gap-4">
                    {getStatusIcon(verification)}
                    <div className="space-y-2">
                      <div className="flex items-center gap-2">
                        <span className="text-white font-medium">
                          Payment: {verification.payment_id}
                        </span>
                        {getStatusBadge(verification)}
                      </div>
                      
                      <div className="grid grid-cols-2 gap-4 text-sm">
                        <div className="flex items-center gap-2 text-gray-300">
                          <DollarSign className="h-4 w-4" />
                          ${verification.amount_usd.toLocaleString()}
                        </div>
                        <div className="flex items-center gap-2 text-gray-300">
                          <Link className="h-4 w-4" />
                          {verification.chain.toUpperCase()}
                        </div>
                      </div>
                      
                      <div className="flex items-center gap-2 text-gray-400 text-sm">
                        <Hash className="h-4 w-4" />
                        <span className="font-mono">
                          {verification.transaction_hash.substring(0, 20)}...
                        </span>
                        <Button
                          size="sm"
                          variant="ghost"
                          onClick={() => openBlockchainExplorer(verification.transaction_hash, verification.chain)}
                          className="h-6 px-2 text-blue-400 hover:text-blue-300"
                        >
                          <ExternalLink className="h-3 w-3" />
                        </Button>
                      </div>
                    </div>
                  </div>
                  
                  <div className="text-right">
                    <div className="text-white font-medium">
                      {verification.verification_confidence}% Confidence
                    </div>
                    <div className="text-gray-400 text-sm">
                      {new Date(verification.created_at).toLocaleDateString()}
                    </div>
                    <Button
                      size="sm"
                      variant="outline"
                      onClick={() => setSelectedVerification(verification)}
                      className="mt-2 text-white border-gray-600"
                    >
                      <Eye className="h-4 w-4 mr-1" />
                      Details
                    </Button>
                  </div>
                </div>
                
                {/* Verification Checks */}
                {verification.blockchain_verified && (
                  <div className="mt-4 pt-4 border-t border-gray-600">
                    <div className="text-sm text-gray-300 mb-2">Verification Checks:</div>
                    <div className="flex flex-wrap gap-2">
                      {Object.entries(verification.verification_checks).map(([check, passed]) => (
                        <Badge 
                          key={check}
                          variant={passed ? "default" : "destructive"}
                          className={`text-xs ${passed ? 'bg-green-600' : 'bg-red-600'}`}
                        >
                          {check.replace(/_/g, ' ')}
                        </Badge>
                      ))}
                    </div>
                  </div>
                )}
                
                {/* Verification Errors */}
                {verification.verification_errors.length > 0 && (
                  <div className="mt-4 pt-4 border-t border-gray-600">
                    <div className="text-sm text-red-400 mb-2">Verification Errors:</div>
                    <div className="space-y-1">
                      {verification.verification_errors.map((error, index) => (
                        <div key={index} className="text-xs text-red-300 bg-red-900/20 p-2 rounded">
                          {error}
                        </div>
                      ))}
                    </div>
                  </div>
                )}
              </CardContent>
            </Card>
          ))
        ) : (
          <Card className="bg-gray-800/50 border-gray-700">
            <CardContent className="p-8 text-center">
              <div className="text-gray-400">No blockchain verifications found</div>
            </CardContent>
          </Card>
        )}
      </div>

      {/* Detailed View Modal */}
      {selectedVerification && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
          <Card className="bg-gray-800 border-gray-700 max-w-2xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <CardHeader>
              <CardTitle className="text-white flex items-center justify-between">
                <span>Blockchain Verification Details</span>
                <Button
                  variant="ghost"
                  onClick={() => setSelectedVerification(null)}
                  className="text-gray-400 hover:text-white"
                >
                  ✕
                </Button>
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid grid-cols-2 gap-4 text-sm">
                <div>
                  <span className="text-gray-400">Payment ID:</span>
                  <div className="text-white font-mono">{selectedVerification.payment_id}</div>
                </div>
                <div>
                  <span className="text-gray-400">Amount:</span>
                  <div className="text-white">${selectedVerification.amount_usd.toLocaleString()}</div>
                </div>
                <div>
                  <span className="text-gray-400">Chain:</span>
                  <div className="text-white">{selectedVerification.chain.toUpperCase()}</div>
                </div>
                <div>
                  <span className="text-gray-400">Status:</span>
                  <div>{getStatusBadge(selectedVerification)}</div>
                </div>
              </div>
              
              <div>
                <span className="text-gray-400">Transaction Hash:</span>
                <div className="text-white font-mono text-sm break-all">
                  {selectedVerification.transaction_hash}
                </div>
              </div>
              
              {selectedVerification.blockchain_data && (
                <div>
                  <span className="text-gray-400">Blockchain Data:</span>
                  <pre className="text-xs text-gray-300 bg-gray-900 p-3 rounded mt-2 overflow-x-auto">
                    {JSON.stringify(selectedVerification.blockchain_data, null, 2)}
                  </pre>
                </div>
              )}
            </CardContent>
          </Card>
        </div>
      )}
    </div>
  );
};

export default BlockchainVerificationDashboard;

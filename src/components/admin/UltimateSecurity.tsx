import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { useToast } from '@/hooks/use-toast';
import {
  Shield,
  ShieldCheck,
  ShieldAlert,
  ShieldX,
  Lock,
  Key,
  Database,
  Clock,
  AlertTriangle,
  CheckCircle,
  XCircle,
  RefreshCw,
  Eye,
  FileText,
  Zap
} from '@/components/SafeIcons';

interface SecurityReport {
  dual_table_integrity: {
    status: string;
    total_users_checked: number;
    valid_users: number;
    compromised_users: number[];
    integrity_percentage: number;
  };
  cryptographic_verification: {
    status: string;
    hash_generation_working: boolean;
    hash_consistency_verified: boolean;
    recent_transactions_with_hashes: number;
    test_hash_sample: string;
  };
  audit_trail_verification: {
    status: string;
    total_audit_entries: number;
    unique_users_in_audit: number;
    oldest_entry: string;
    newest_entry: string;
    verification_events: number;
    commission_events: number;
    withdrawal_events: number;
    suspicious_timestamp_gaps: number;
  };
  business_hours_verification: {
    status: string;
    currently_within_business_hours: boolean;
    next_business_day: string;
    business_hours_violations: number;
  };
  withdrawal_security_verification: {
    status: string;
    completed_withdrawals_without_blockchain_hash: number;
    automated_withdrawals_detected: number;
  };
  overall_security_assessment: {
    overall_status: string;
    security_score_percentage: number;
    secure_checks: number;
    total_checks: number;
    individual_check_results: Record<string, string>;
    recommendation: string;
  };
}

const UltimateSecurity: React.FC = () => {
  const { toast } = useToast();
  
  const [securityReport, setSecurityReport] = useState<SecurityReport | null>(null);
  const [isRunning, setIsRunning] = useState(false);
  const [lastCheckTime, setLastCheckTime] = useState<string | null>(null);

  const runUltimateSecurityCheck = async () => {
    setIsRunning(true);
    try {
      const response = await fetch('/api/security/ultimate-security-check.php', {
        method: 'GET',
        credentials: 'include'
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const data = await response.json();

      if (data.success) {
        setSecurityReport(data.security_report);
        setLastCheckTime(data.security_report.timestamp);
        
        const overallStatus = data.security_report.overall_security_assessment.overall_status;
        const securityScore = data.security_report.overall_security_assessment.security_score_percentage;
        
        if (overallStatus === 'MAXIMUM_SECURITY') {
          toast({
            title: "🛡️ MAXIMUM SECURITY ACHIEVED",
            description: `Perfect security score: ${securityScore}% - System is bulletproof!`,
          });
        } else if (securityScore >= 80) {
          toast({
            title: "🔒 High Security Status",
            description: `Security score: ${securityScore}% - System is highly secure`,
          });
        } else {
          toast({
            title: "⚠️ Security Issues Detected",
            description: `Security score: ${securityScore}% - Immediate attention required`,
            variant: "destructive"
          });
        }
      } else {
        throw new Error(data.message || 'Security check failed');
      }
    } catch (error) {
      console.error('Ultimate security check failed:', error);
      toast({
        title: "❌ Security Check Failed",
        description: error instanceof Error ? error.message : "Failed to run ultimate security check",
        variant: "destructive"
      });
    } finally {
      setIsRunning(false);
    }
  };

  const getSecurityIcon = (status: string) => {
    switch (status) {
      case 'SECURE':
      case 'MAXIMUM_SECURITY':
        return <ShieldCheck className="h-5 w-5 text-green-400" />;
      case 'HIGH_SECURITY':
        return <Shield className="h-5 w-5 text-blue-400" />;
      case 'MEDIUM_SECURITY':
      case 'SUSPICIOUS':
        return <ShieldAlert className="h-5 w-5 text-yellow-400" />;
      case 'LOW_SECURITY':
      case 'VIOLATIONS_DETECTED':
      case 'COMPROMISED':
        return <ShieldX className="h-5 w-5 text-red-400" />;
      default:
        return <AlertTriangle className="h-5 w-5 text-gray-400" />;
    }
  };

  const getSecurityColor = (status: string) => {
    switch (status) {
      case 'SECURE':
      case 'MAXIMUM_SECURITY':
        return 'bg-green-500/20 text-green-400 border-green-500/30';
      case 'HIGH_SECURITY':
        return 'bg-blue-500/20 text-blue-400 border-blue-500/30';
      case 'MEDIUM_SECURITY':
      case 'SUSPICIOUS':
        return 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30';
      case 'LOW_SECURITY':
      case 'VIOLATIONS_DETECTED':
      case 'COMPROMISED':
        return 'bg-red-500/20 text-red-400 border-red-500/30';
      default:
        return 'bg-gray-500/20 text-gray-400 border-gray-500/30';
    }
  };

  if (isRunning) {
    return (
      <div className="flex items-center justify-center py-12">
        <RefreshCw className="h-8 w-8 animate-spin text-gold" />
        <span className="ml-3 text-white">Running ultimate security verification...</span>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Security Control */}
      <Card className="bg-gray-800 border-gray-700">
        <CardHeader>
          <div className="flex justify-between items-center">
            <CardTitle className="text-white flex items-center gap-2">
              <Shield className="h-5 w-5 text-gold" />
              Ultimate Security Verification
            </CardTitle>
            <Button 
              onClick={runUltimateSecurityCheck} 
              disabled={isRunning}
              className="bg-gold-gradient text-black hover:bg-gold-gradient/90"
            >
              {isRunning ? <RefreshCw className="h-4 w-4 animate-spin mr-2" /> : <Zap className="h-4 w-4 mr-2" />}
              Run Ultimate Security Check
            </Button>
          </div>
        </CardHeader>
        <CardContent>
          <p className="text-gray-300 mb-4">
            This performs the most comprehensive security verification possible: dual-table integrity, 
            cryptographic verification, audit trail analysis, business hours enforcement, and withdrawal security.
          </p>
          {lastCheckTime && (
            <p className="text-sm text-gray-400">
              Last security check: {new Date(lastCheckTime).toLocaleString()}
            </p>
          )}
        </CardContent>
      </Card>

      {/* Overall Security Status */}
      {securityReport && (
        <Card className={`bg-gray-800 border-2 ${getSecurityColor(securityReport.overall_security_assessment.overall_status)}`}>
          <CardHeader>
            <CardTitle className="text-white flex items-center gap-2">
              {getSecurityIcon(securityReport.overall_security_assessment.overall_status)}
              Overall Security Status
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
              <div className="text-center">
                <div className="text-4xl font-bold text-white mb-2">
                  {securityReport.overall_security_assessment.security_score_percentage}%
                </div>
                <div className="text-sm text-gray-400">Security Score</div>
              </div>
              <div className="text-center">
                <div className="text-2xl font-bold text-green-400 mb-2">
                  {securityReport.overall_security_assessment.secure_checks}
                </div>
                <div className="text-sm text-gray-400">Secure Checks</div>
              </div>
              <div className="text-center">
                <div className="text-2xl font-bold text-blue-400 mb-2">
                  {securityReport.overall_security_assessment.total_checks}
                </div>
                <div className="text-sm text-gray-400">Total Checks</div>
              </div>
              <div className="text-center">
                <Badge className={getSecurityColor(securityReport.overall_security_assessment.overall_status)} variant="outline">
                  {securityReport.overall_security_assessment.overall_status}
                </Badge>
                <div className="text-sm text-gray-400 mt-2">Status</div>
              </div>
            </div>
            
            <div className="bg-gray-700/50 rounded p-4">
              <p className="text-gray-300 text-sm">
                <strong>Recommendation:</strong> {securityReport.overall_security_assessment.recommendation}
              </p>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Detailed Security Checks */}
      {securityReport && (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          {/* Dual-Table Integrity */}
          <Card className={`bg-gray-800 border-2 ${getSecurityColor(securityReport.dual_table_integrity.status)}`}>
            <CardHeader>
              <CardTitle className="text-white flex items-center gap-2">
                <Database className="h-5 w-5" />
                Dual-Table Integrity
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-3">
                <div className="flex justify-between">
                  <span className="text-gray-400">Status:</span>
                  <Badge className={getSecurityColor(securityReport.dual_table_integrity.status)} variant="outline">
                    {securityReport.dual_table_integrity.status}
                  </Badge>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-400">Users Checked:</span>
                  <span className="text-white">{securityReport.dual_table_integrity.total_users_checked}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-400">Valid Users:</span>
                  <span className="text-green-400">{securityReport.dual_table_integrity.valid_users}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-400">Integrity:</span>
                  <span className="text-white">{securityReport.dual_table_integrity.integrity_percentage}%</span>
                </div>
                {securityReport.dual_table_integrity.compromised_users.length > 0 && (
                  <div className="bg-red-500/10 border border-red-500/20 rounded p-2">
                    <p className="text-red-400 text-sm">
                      Compromised Users: {securityReport.dual_table_integrity.compromised_users.join(', ')}
                    </p>
                  </div>
                )}
              </div>
            </CardContent>
          </Card>

          {/* Cryptographic Verification */}
          <Card className={`bg-gray-800 border-2 ${getSecurityColor(securityReport.cryptographic_verification.status)}`}>
            <CardHeader>
              <CardTitle className="text-white flex items-center gap-2">
                <Key className="h-5 w-5" />
                Cryptographic Security
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-3">
                <div className="flex justify-between">
                  <span className="text-gray-400">Status:</span>
                  <Badge className={getSecurityColor(securityReport.cryptographic_verification.status)} variant="outline">
                    {securityReport.cryptographic_verification.status}
                  </Badge>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-400">Hash Generation:</span>
                  <span className={securityReport.cryptographic_verification.hash_generation_working ? 'text-green-400' : 'text-red-400'}>
                    {securityReport.cryptographic_verification.hash_generation_working ? 'Working' : 'Failed'}
                  </span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-400">Hash Consistency:</span>
                  <span className={securityReport.cryptographic_verification.hash_consistency_verified ? 'text-green-400' : 'text-red-400'}>
                    {securityReport.cryptographic_verification.hash_consistency_verified ? 'Verified' : 'Failed'}
                  </span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-400">Recent Hashed Transactions:</span>
                  <span className="text-white">{securityReport.cryptographic_verification.recent_transactions_with_hashes}</span>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Audit Trail Verification */}
          <Card className={`bg-gray-800 border-2 ${getSecurityColor(securityReport.audit_trail_verification.status)}`}>
            <CardHeader>
              <CardTitle className="text-white flex items-center gap-2">
                <FileText className="h-5 w-5" />
                Audit Trail Integrity
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-3">
                <div className="flex justify-between">
                  <span className="text-gray-400">Status:</span>
                  <Badge className={getSecurityColor(securityReport.audit_trail_verification.status)} variant="outline">
                    {securityReport.audit_trail_verification.status}
                  </Badge>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-400">Total Entries:</span>
                  <span className="text-white">{securityReport.audit_trail_verification.total_audit_entries}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-400">Commission Events:</span>
                  <span className="text-blue-400">{securityReport.audit_trail_verification.commission_events}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-400">Withdrawal Events:</span>
                  <span className="text-green-400">{securityReport.audit_trail_verification.withdrawal_events}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-400">Suspicious Gaps:</span>
                  <span className={securityReport.audit_trail_verification.suspicious_timestamp_gaps === 0 ? 'text-green-400' : 'text-red-400'}>
                    {securityReport.audit_trail_verification.suspicious_timestamp_gaps}
                  </span>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Business Hours & Withdrawal Security */}
          <Card className={`bg-gray-800 border-2 ${getSecurityColor(securityReport.business_hours_verification.status)}`}>
            <CardHeader>
              <CardTitle className="text-white flex items-center gap-2">
                <Clock className="h-5 w-5" />
                Business Hours & Withdrawal Security
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-3">
                <div className="flex justify-between">
                  <span className="text-gray-400">Business Hours Status:</span>
                  <Badge className={getSecurityColor(securityReport.business_hours_verification.status)} variant="outline">
                    {securityReport.business_hours_verification.status}
                  </Badge>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-400">Currently Within Hours:</span>
                  <span className={securityReport.business_hours_verification.currently_within_business_hours ? 'text-green-400' : 'text-yellow-400'}>
                    {securityReport.business_hours_verification.currently_within_business_hours ? 'Yes' : 'No'}
                  </span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-400">Hours Violations:</span>
                  <span className={securityReport.business_hours_verification.business_hours_violations === 0 ? 'text-green-400' : 'text-red-400'}>
                    {securityReport.business_hours_verification.business_hours_violations}
                  </span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-400">Missing Blockchain Hashes:</span>
                  <span className={securityReport.withdrawal_security_verification.completed_withdrawals_without_blockchain_hash === 0 ? 'text-green-400' : 'text-red-400'}>
                    {securityReport.withdrawal_security_verification.completed_withdrawals_without_blockchain_hash}
                  </span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-400">Automated Withdrawals:</span>
                  <span className={securityReport.withdrawal_security_verification.automated_withdrawals_detected === 0 ? 'text-green-400' : 'text-red-400'}>
                    {securityReport.withdrawal_security_verification.automated_withdrawals_detected}
                  </span>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Security Instructions */}
      <Card className="bg-gray-800 border-gray-700">
        <CardHeader>
          <CardTitle className="text-white">Ultimate Security Features</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="text-gray-300 space-y-2">
            <p><strong>This system implements military-grade security:</strong></p>
            <ul className="list-disc list-inside space-y-1 ml-4">
              <li><strong>Dual-Table Verification:</strong> Primary + verification tables with cross-validation</li>
              <li><strong>Cryptographic Hashing:</strong> SHA-256/SHA-512 signatures on all transactions</li>
              <li><strong>Immutable Audit Trail:</strong> Append-only transaction log with tamper detection</li>
              <li><strong>Business Hours Enforcement:</strong> Monday-Friday 9AM-4PM processing only</li>
              <li><strong>Manual Withdrawal Processing:</strong> No private keys, admin verification required</li>
              <li><strong>Blockchain Verification:</strong> All completed withdrawals require blockchain proof</li>
            </ul>
            <p className="mt-4"><strong>Expected Result:</strong> MAXIMUM_SECURITY status with 100% security score.</p>
          </div>
        </CardContent>
      </Card>
    </div>
  );
};

export default UltimateSecurity;

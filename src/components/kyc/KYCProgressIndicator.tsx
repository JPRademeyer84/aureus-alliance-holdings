import React from 'react';
import { useNavigate } from 'react-router-dom';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { CheckCircle, Clock, XCircle, AlertCircle, Mail, Phone, User, FileText, Camera, Shield, Calendar, ExternalLink } from 'lucide-react';
import KYCLevelBadge from './KYCLevelBadge';

interface Requirement {
  requirement: {
    id: string;
    requirement_type: string;
    requirement_name: string;
    description: string;
    is_mandatory: boolean;
  };
  status: 'not_started' | 'in_progress' | 'completed' | 'failed';
  completed: boolean;
  details?: any;
}

interface LevelProgress {
  level: number;
  requirements: Requirement[];
  progress: number;
  completed_count: number;
  total_count: number;
  can_upgrade: boolean;
}

interface KYCProgressIndicatorProps {
  currentLevel: number;
  levelProgress: LevelProgress[];
  onUpgradeLevel?: (targetLevel: number) => void;
  className?: string;
}

const KYCProgressIndicator: React.FC<KYCProgressIndicatorProps> = ({
  currentLevel,
  levelProgress,
  onUpgradeLevel,
  className = ''
}) => {
  const navigate = useNavigate();
  const getRequirementIcon = (type: string) => {
    switch (type) {
      case 'email_verification':
        return <Mail className="h-5 w-5 text-blue-400" />;
      case 'phone_verification':
        return <Phone className="h-5 w-5 text-green-400" />;
      case 'profile_completion':
        return <User className="h-5 w-5 text-purple-400" />;
      case 'document_upload':
        return <FileText className="h-5 w-5 text-orange-400" />;
      case 'facial_verification':
        return <Camera className="h-5 w-5 text-pink-400" />;
      case 'address_verification':
        return <FileText className="h-5 w-5 text-yellow-400" />;
      case 'enhanced_due_diligence':
        return <Shield className="h-5 w-5 text-red-400" />;
      case 'account_activity':
        return <Calendar className="h-5 w-5 text-indigo-400" />;
      default:
        return <AlertCircle className="h-5 w-5 text-gray-400" />;
    }
  };

  const getRequirementDetails = (requirement: Requirement) => {
    const { requirement: req, status, details } = requirement;
    const type = req.requirement_type;

    switch (type) {
      case 'email_verification':
        return {
          title: 'Email Verification',
          description: 'Verify your email address to secure your account',
          actionText: status === 'completed' ? '✅ Email verified successfully' : '📧 Check your email for verification link',
          helpText: status === 'completed' ? 'Your email has been verified and is secure.' : 'Click the verification link sent to your email address.',
          actionButton: status !== 'completed' ? 'Go to Profile Settings' : null,
          actionLink: status !== 'completed' ? '/dashboard/profile' : null
        };

      case 'phone_verification':
        return {
          title: 'Phone Number Verification',
          description: 'Add and verify your phone number for account security',
          actionText: status === 'completed' ? '✅ Phone number verified' : '📱 Add your phone number in profile settings',
          helpText: status === 'completed' ? 'Your phone number is verified and secure.' : 'Go to your profile settings to add and verify your phone number.',
          actionButton: status !== 'completed' ? 'Add Phone Number' : null,
          actionLink: status !== 'completed' ? '/dashboard/profile' : null
        };

      case 'profile_completion':
        const completion = details?.completion_percentage || 0;
        return {
          title: 'Complete Your Profile',
          description: 'Fill out your basic profile information (75% required)',
          actionText: status === 'completed' ? '✅ Profile completed (100%)' : `📝 Profile ${completion}% complete (need 75%)`,
          helpText: status === 'completed' ? 'Your profile is complete with all required information.' : 'Complete your profile with personal details, address, and other required information.',
          actionButton: status !== 'completed' ? 'Complete Profile' : null,
          actionLink: status !== 'completed' ? '/dashboard/profile' : null
        };

      case 'document_upload':
        const approvedDocs = details?.approved_documents || 0;
        return {
          title: 'Government ID Document',
          description: 'Upload a clear photo of your government-issued ID',
          actionText: status === 'completed' ? '✅ ID document approved' : `📄 ${approvedDocs} approved documents (need 1)`,
          helpText: status === 'completed' ? 'Your government ID has been verified and approved.' : 'Upload a clear photo of your driver\'s license, passport, or national ID card.',
          actionButton: status !== 'completed' ? 'Upload ID Document' : null,
          actionLink: status !== 'completed' ? '/dashboard/kyc-verification' : null
        };

      case 'facial_verification':
        return {
          title: 'Facial Recognition Verification',
          description: 'Complete facial recognition to verify your identity',
          actionText: status === 'completed' ? '✅ Facial verification completed' : '📸 Take a selfie for verification',
          helpText: status === 'completed' ? 'Your facial verification has been completed successfully.' : 'Use your device camera to take a clear selfie that matches your ID document.',
          actionButton: status !== 'completed' ? 'Start Facial Verification' : null,
          actionLink: status !== 'completed' ? '/dashboard/kyc-verification' : null
        };

      case 'address_verification':
        return {
          title: 'Proof of Address',
          description: 'Upload a document showing your current address',
          actionText: status === 'completed' ? '✅ Address verified' : '🏠 Upload proof of address document',
          helpText: status === 'completed' ? 'Your address has been verified successfully.' : 'Upload a utility bill, bank statement, or official document showing your current address.',
          actionButton: status !== 'completed' ? 'Upload Address Proof' : null,
          actionLink: status !== 'completed' ? '/dashboard/kyc-verification' : null
        };

      case 'enhanced_due_diligence':
        return {
          title: 'Enhanced Due Diligence',
          description: 'Additional verification for high-level access',
          actionText: status === 'completed' ? '✅ Enhanced verification completed' : '🔍 Additional documentation required',
          helpText: status === 'completed' ? 'Enhanced due diligence verification is complete.' : 'Additional documentation and verification steps are required for Level 3 access.',
          actionButton: status !== 'completed' ? 'Contact Support' : null,
          actionLink: status !== 'completed' ? '/dashboard/contact-support' : null
        };

      case 'account_activity':
        const daysSince = details?.days_since_creation || 0;
        const daysRemaining = Math.max(0, 30 - daysSince);
        return {
          title: 'Account Activity Period',
          description: 'Maintain account activity for 30 days minimum',
          actionText: status === 'completed' ? '✅ 30+ days of account activity' : `📅 ${Math.floor(daysSince)} days of activity (need 30)`,
          helpText: status === 'completed' ? 'Your account has been active for the required period.' : `Your account needs to be active for ${daysRemaining} more days to meet this requirement.`,
          actionButton: null,
          actionLink: null
        };

      default:
        return {
          title: req.requirement_name,
          description: req.description,
          actionText: status === 'completed' ? '✅ Completed' : '⏳ Not started',
          helpText: 'Complete this requirement to progress to the next level.',
          actionButton: null,
          actionLink: null
        };
    }
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'completed':
        return <CheckCircle className="h-4 w-4 text-green-400" />;
      case 'in_progress':
        return <Clock className="h-4 w-4 text-yellow-400" />;
      case 'failed':
        return <XCircle className="h-4 w-4 text-red-400" />;
      default:
        return <AlertCircle className="h-4 w-4 text-gray-400" />;
    }
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'completed':
        return 'bg-green-500/20 text-green-400 border-green-500/30';
      case 'in_progress':
        return 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30';
      case 'failed':
        return 'bg-red-500/20 text-red-400 border-red-500/30';
      default:
        return 'bg-gray-500/20 text-gray-400 border-gray-500/30';
    }
  };

  const getLevelDescription = (level: number) => {
    switch (level) {
      case 1:
        return {
          name: 'Basic Verification',
          description: 'Essential account security with email and phone verification',
          benefits: 'Access to basic investment packages ($25-$100), 5% commission rate, $1,000 daily withdrawal limit'
        };
      case 2:
        return {
          name: 'Intermediate Verification',
          description: 'Document verification with government ID and address proof',
          benefits: 'Access to intermediate packages ($25-$500), 7% commission rate, $10,000 daily withdrawal limit'
        };
      case 3:
        return {
          name: 'Advanced Verification',
          description: 'Enhanced verification with additional due diligence',
          benefits: 'Access to all packages ($25-$1,000), 10% commission rate, unlimited withdrawals, VIP support'
        };
      default:
        return {
          name: 'Unknown Level',
          description: 'Level information not available',
          benefits: 'Benefits information not available'
        };
    }
  };

  return (
    <div className={`space-y-6 ${className}`}>
      {/* Level Information Header */}
      <Card className="bg-gradient-to-r from-gray-800 to-gray-700 border-gray-600">
        <CardContent className="p-6">
          <div className="flex items-center gap-4 mb-4">
            <KYCLevelBadge level={currentLevel} size="lg" />
            <div>
              <h2 className="text-2xl font-bold text-white">{getLevelDescription(currentLevel).name}</h2>
              <p className="text-gray-300">{getLevelDescription(currentLevel).description}</p>
            </div>
          </div>
          <div className="bg-gray-800/50 p-4 rounded-lg">
            <h3 className="text-gold font-semibold mb-2">🎁 Your Current Benefits:</h3>
            <p className="text-gray-300 text-sm">{getLevelDescription(currentLevel).benefits}</p>
          </div>
        </CardContent>
      </Card>

      {/* Current Level Display */}
      <Card className="bg-gray-800 border-gray-700">
        <CardHeader>
          <CardTitle className="text-white flex items-center gap-3">
            <KYCLevelBadge level={currentLevel} size="lg" />
            <span>Level Progress Overview</span>
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            {levelProgress.map((level) => (
              <div
                key={level.level}
                className={`p-4 rounded-lg border ${
                  level.level === currentLevel
                    ? 'border-gold bg-gold/10'
                    : level.level < currentLevel
                    ? 'border-green-500/30 bg-green-500/10'
                    : 'border-gray-600 bg-gray-700/50'
                }`}
              >
                <div className="flex items-center justify-between mb-3">
                  <KYCLevelBadge level={level.level} size="sm" />
                  {level.level < currentLevel && (
                    <Badge className="bg-green-500/20 text-green-400 text-xs">
                      Completed
                    </Badge>
                  )}
                  {level.level === currentLevel && (
                    <Badge className="bg-gold/20 text-gold text-xs">
                      Current
                    </Badge>
                  )}
                </div>
                
                <div className="space-y-2">
                  <div className="flex justify-between text-sm">
                    <span className="text-gray-400">Progress</span>
                    <span className="text-white">{level.progress.toFixed(0)}%</span>
                  </div>
                  <Progress 
                    value={level.progress} 
                    className="h-2"
                  />
                  <div className="text-xs text-gray-400">
                    {level.completed_count} of {level.total_count} requirements completed
                  </div>
                </div>

                {level.level > currentLevel && level.can_upgrade && onUpgradeLevel && (
                  <button
                    onClick={() => onUpgradeLevel(level.level)}
                    className="w-full mt-3 px-3 py-2 bg-gold hover:bg-gold/80 text-black text-sm font-medium rounded transition-colors"
                  >
                    Upgrade to Level {level.level}
                  </button>
                )}
              </div>
            ))}
          </div>
        </CardContent>
      </Card>

      {/* Current Level Requirements Detail */}
      {levelProgress.find(l => l.level === currentLevel) && (
        <Card className="bg-gray-800 border-gray-700">
          <CardHeader>
            <CardTitle className="text-white flex items-center gap-2">
              <CheckCircle className="h-5 w-5 text-green-400" />
              Current Level Requirements
            </CardTitle>
          </CardHeader>
          <CardContent>
            {(() => {
              const currentLevelData = levelProgress.find(l => l.level === currentLevel);
              if (!currentLevelData) return null;

              return (
                <div className="space-y-4">
                  <div className="flex items-center justify-between mb-4">
                    <KYCLevelBadge level={currentLevelData.level} />
                    <div className="text-right">
                      <div className="text-white font-medium">
                        {currentLevelData.progress.toFixed(0)}% Complete
                      </div>
                      <div className="text-sm text-gray-400">
                        {currentLevelData.completed_count} of {currentLevelData.total_count} requirements
                      </div>
                    </div>
                  </div>

                  <Progress value={currentLevelData.progress} className="h-3" />

                  <div className="space-y-3">
                    {currentLevelData.requirements.map((req) => {
                      const reqDetails = getRequirementDetails(req);
                      return (
                        <div
                          key={req.requirement.id}
                          className={`p-3 rounded-lg border ${
                            req.completed
                              ? 'border-green-500/30 bg-green-500/10'
                              : 'border-yellow-500/30 bg-yellow-500/10'
                          }`}
                        >
                          <div className="flex items-start gap-3">
                            {getRequirementIcon(req.requirement.requirement_type)}
                            <div className="flex-1">
                              <div className="flex items-center gap-2 mb-1">
                                <h4 className="text-white font-medium">{reqDetails.title}</h4>
                                <Badge className={`text-xs ${
                                  req.completed
                                    ? 'bg-green-500/20 text-green-400 border-green-500/30'
                                    : 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30'
                                }`}>
                                  {req.completed ? 'Completed' : 'In Progress'}
                                </Badge>
                              </div>

                              <div className="text-sm">
                                <span className={req.completed ? 'text-green-400' : 'text-yellow-400'}>
                                  {reqDetails.actionText}
                                </span>
                              </div>
                            </div>
                          </div>
                        </div>
                      );
                    })}
                  </div>
                </div>
              );
            })()}
          </CardContent>
        </Card>
      )}

      {/* Detailed Requirements for Next Level */}
      {levelProgress.find(l => l.level === currentLevel + 1) && (
        <Card className="bg-gray-800 border-gray-700">
          <CardHeader>
            <CardTitle className="text-white flex items-center gap-2">
              <AlertCircle className="h-5 w-5 text-yellow-400" />
              Next Level Requirements
            </CardTitle>
          </CardHeader>
          <CardContent>
            {(() => {
              const nextLevel = levelProgress.find(l => l.level === currentLevel + 1);
              if (!nextLevel) return null;

              return (
                <div className="space-y-4">
                  <div className="flex items-center justify-between mb-4">
                    <KYCLevelBadge level={nextLevel.level} />
                    <div className="text-right">
                      <div className="text-white font-medium">
                        {nextLevel.progress.toFixed(0)}% Complete
                      </div>
                      <div className="text-sm text-gray-400">
                        {nextLevel.completed_count} of {nextLevel.total_count} requirements
                      </div>
                    </div>
                  </div>

                  <Progress value={nextLevel.progress} className="h-3" />

                  <div className="space-y-4">
                    {nextLevel.requirements.map((req) => {
                      const reqDetails = getRequirementDetails(req);
                      return (
                        <div
                          key={req.requirement.id}
                          className={`p-4 rounded-lg border ${
                            req.completed
                              ? 'border-green-500/30 bg-green-500/10'
                              : 'border-gray-600 bg-gray-700/50'
                          }`}
                        >
                          <div className="flex items-start gap-4">
                            {getRequirementIcon(req.requirement.requirement_type)}
                            <div className="flex-1">
                              <div className="flex items-center gap-2 mb-2">
                                <h4 className="text-white font-semibold">{reqDetails.title}</h4>
                                {req.requirement.is_mandatory && (
                                  <Badge className="bg-red-500/20 text-red-400 text-xs">
                                    Required
                                  </Badge>
                                )}
                              </div>

                              <p className="text-sm text-gray-300 mb-3">{reqDetails.description}</p>

                              <div className={`p-3 rounded-md ${
                                req.completed
                                  ? 'bg-green-500/10 border border-green-500/30'
                                  : 'bg-blue-500/10 border border-blue-500/30'
                              }`}>
                                <div className="flex items-center gap-2 mb-1">
                                  {getStatusIcon(req.status)}
                                  <span className={`text-sm font-medium ${
                                    req.completed ? 'text-green-400' : 'text-blue-400'
                                  }`}>
                                    {reqDetails.actionText}
                                  </span>
                                </div>
                                <p className="text-xs text-gray-400">{reqDetails.helpText}</p>
                              </div>

                              {reqDetails.actionButton && !req.completed && (
                                <div className="mt-3">
                                  <Button
                                    size="sm"
                                    className="bg-gold hover:bg-gold/80 text-black"
                                    onClick={() => {
                                      if (reqDetails.actionLink) {
                                        console.log('KYC Progress: Navigating to', reqDetails.actionLink);
                                        navigate(reqDetails.actionLink);
                                      }
                                    }}
                                  >
                                    <ExternalLink className="h-3 w-3 mr-1" />
                                    {reqDetails.actionButton}
                                  </Button>
                                </div>
                              )}
                            </div>
                          </div>
                        </div>
                      );
                    })}
                  </div>

                  {nextLevel.can_upgrade && onUpgradeLevel && (
                    <div className="pt-4 border-t border-gray-600">
                      <button
                        onClick={() => onUpgradeLevel(nextLevel.level)}
                        className="w-full px-4 py-3 bg-gold hover:bg-gold/80 text-black font-medium rounded-lg transition-colors"
                      >
                        🎉 Upgrade to Level {nextLevel.level} Now!
                      </button>
                    </div>
                  )}
                </div>
              );
            })()}
          </CardContent>
        </Card>
      )}
    </div>
  );
};

export default KYCProgressIndicator;

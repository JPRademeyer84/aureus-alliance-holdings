import React, { useState, useEffect } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useSimpleTranslation as useTranslation, ST as T } from '@/components/SimpleTranslator';
import {
  X,
  User,
  DollarSign,
  TrendingUp,
  Users,
  Calendar,
  MapPin,
  Phone,
  Mail,
  MessageCircle,
  Award,
  Target,
  Activity,
  Wallet,
  Star,
  Eye
} from 'lucide-react';

// Safe chart icon to avoid SVG path errors
const BarChart3 = ({ className }: { className?: string }) => <span className={className}>📊</span>;

interface DownlineMember {
  id: string;
  fullName: string;
  username: string;
  email: string;
  phone?: string;
  country?: string;
  level: number;
  status: 'active' | 'inactive';
  totalInvested: number;
  commissionGenerated: number;
  totalDownline: number;
  joinDate: string;
  lastActivity: string;
}

interface MemberProfileModalProps {
  isOpen: boolean;
  onClose: () => void;
  member: DownlineMember | null;
}

interface MemberStats {
  totalInvestments: number;
  totalCommissions: number;
  directReferrals: number;
  totalDownline: number;
  monthlyVolume: number;
  averageInvestment: number;
  lastInvestmentDate: string;
  performanceRank: number;
}

interface Investment {
  id: string;
  packageName: string;
  amount: number;
  shares: number;
  roi: number;
  date: string;
  status: string;
}

const MemberProfileModal: React.FC<MemberProfileModalProps> = ({
  isOpen,
  onClose,
  member
}) => {
  const { translate } = useTranslation();
  const [memberStats, setMemberStats] = useState<MemberStats | null>(null);
  const [memberInvestments, setMemberInvestments] = useState<Investment[]>([]);
  const [isLoading, setIsLoading] = useState(false);

  useEffect(() => {
    if (isOpen && member) {
      fetchMemberDetails();
    }
  }, [isOpen, member]);

  const fetchMemberDetails = async () => {
    if (!member) return;
    
    setIsLoading(true);
    try {
      // Fetch detailed member statistics
      const statsResponse = await fetch(`http://localhost/aureus-angel-alliance/api/affiliate/member-stats.php?member_id=${member.id}`);
      if (statsResponse.ok) {
        const statsData = await statsResponse.json();
        if (statsData.success) {
          setMemberStats(statsData.data);
        }
      }

      // Fetch member investments
      const investmentsResponse = await fetch(`http://localhost/aureus-angel-alliance/api/affiliate/member-investments.php?member_id=${member.id}`);
      if (investmentsResponse.ok) {
        const investmentsData = await investmentsResponse.json();
        if (investmentsData.success) {
          setMemberInvestments(investmentsData.data || []);
        }
      }
    } catch (error) {
      console.error('Error fetching member details:', error);
    } finally {
      setIsLoading(false);
    }
  };

  const getLevelColor = (level: number) => {
    switch (level) {
      case 1: return 'bg-blue-500/20 text-blue-400 border-blue-500/30';
      case 2: return 'bg-green-500/20 text-green-400 border-green-500/30';
      case 3: return 'bg-purple-500/20 text-purple-400 border-purple-500/30';
      default: return 'bg-gray-500/20 text-gray-400 border-gray-500/30';
    }
  };

  const getStatusColor = (status: string) => {
    return status === 'active' 
      ? 'bg-green-500/20 text-green-400 border-green-500/30'
      : 'bg-red-500/20 text-red-400 border-red-500/30';
  };

  const openWhatsApp = () => {
    if (member?.phone) {
      const message = encodeURIComponent(`Hello ${member.fullName}, I hope you're doing well!`);
      window.open(`https://wa.me/${member.phone.replace(/\D/g, '')}?text=${message}`, '_blank');
    }
  };

  const openTelegram = () => {
    if (member?.username) {
      window.open(`https://t.me/${member.username}`, '_blank');
    }
  };

  if (!member) return null;

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-4xl bg-charcoal border-gold/30 max-h-[90vh] overflow-y-auto">
        <DialogHeader className="relative">
          <Button
            onClick={onClose}
            className="absolute -top-2 -right-2 h-8 w-8 p-0 bg-transparent hover:bg-white/10"
          >
            <X className="h-4 w-4" />
          </Button>
          <DialogTitle className="text-gold text-xl font-bold flex items-center gap-2">
            <User className="h-5 w-5" />
            <T k="member_profile" fallback="Member Profile" />
          </DialogTitle>
        </DialogHeader>

        <div className="space-y-6">
          {/* Member Header */}
          <Card className="bg-gray-800 border-gray-700">
            <CardContent className="p-6">
              <div className="flex items-start gap-6">
                {/* Avatar */}
                <div className="w-20 h-20 bg-gradient-to-r from-gold to-yellow-600 rounded-full flex items-center justify-center flex-shrink-0">
                  <span className="text-black font-bold text-2xl">
                    {member.fullName.charAt(0)}
                  </span>
                </div>

                {/* Basic Info */}
                <div className="flex-1">
                  <div className="flex items-center gap-3 mb-3">
                    <h2 className="text-2xl font-bold text-white">{member.fullName}</h2>
                    <Badge className={getLevelColor(member.level)}>
                      Level {member.level}
                    </Badge>
                    <Badge className={getStatusColor(member.status)}>
                      {member.status}
                    </Badge>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div className="flex items-center gap-2">
                      <User className="h-4 w-4 text-gray-400" />
                      <span className="text-gray-400">Username:</span>
                      <span className="text-white">@{member.username}</span>
                    </div>
                    <div className="flex items-center gap-2">
                      <Mail className="h-4 w-4 text-gray-400" />
                      <span className="text-gray-400">Email:</span>
                      <span className="text-white">{member.email}</span>
                    </div>
                    {member.phone && (
                      <div className="flex items-center gap-2">
                        <Phone className="h-4 w-4 text-gray-400" />
                        <span className="text-gray-400">Phone:</span>
                        <span className="text-white">{member.phone}</span>
                      </div>
                    )}
                    {member.country && (
                      <div className="flex items-center gap-2">
                        <MapPin className="h-4 w-4 text-gray-400" />
                        <span className="text-gray-400">Country:</span>
                        <span className="text-white">{member.country}</span>
                      </div>
                    )}
                    <div className="flex items-center gap-2">
                      <Calendar className="h-4 w-4 text-gray-400" />
                      <span className="text-gray-400">Joined:</span>
                      <span className="text-white">{new Date(member.joinDate).toLocaleDateString()}</span>
                    </div>
                    <div className="flex items-center gap-2">
                      <Activity className="h-4 w-4 text-gray-400" />
                      <span className="text-gray-400">Last Active:</span>
                      <span className="text-white">{new Date(member.lastActivity).toLocaleDateString()}</span>
                    </div>
                  </div>
                </div>

                {/* Action Buttons */}
                <div className="flex flex-col gap-2">
                  <Button
                    size="sm"
                    onClick={openWhatsApp}
                    className="bg-green-600 hover:bg-green-700 text-white"
                    disabled={!member.phone}
                  >
                    <MessageCircle className="h-4 w-4 mr-2" />
                    WhatsApp
                  </Button>
                  <Button
                    size="sm"
                    variant="outline"
                    onClick={openTelegram}
                    className="border-blue-500/30 text-blue-400 hover:bg-blue-500/20"
                  >
                    <MessageCircle className="h-4 w-4 mr-2" />
                    Telegram
                  </Button>
                  {member.phone && (
                    <Button
                      size="sm"
                      variant="outline"
                      onClick={() => window.open(`tel:${member.phone}`)}
                      className="border-purple-500/30 text-purple-400 hover:bg-purple-500/20"
                    >
                      <Phone className="h-4 w-4 mr-2" />
                      Call
                    </Button>
                  )}
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Tabs for detailed information */}
          <Tabs defaultValue="overview" className="w-full">
            <TabsList className="grid w-full grid-cols-3 bg-gray-800">
              <TabsTrigger value="overview" className="data-[state=active]:bg-gold data-[state=active]:text-black">
                <BarChart3 className="h-4 w-4 mr-2" />
                Overview
              </TabsTrigger>
              <TabsTrigger value="investments" className="data-[state=active]:bg-gold data-[state=active]:text-black">
                <Wallet className="h-4 w-4 mr-2" />
                Investments
              </TabsTrigger>
              <TabsTrigger value="network" className="data-[state=active]:bg-gold data-[state=active]:text-black">
                <Users className="h-4 w-4 mr-2" />
                Network
              </TabsTrigger>
            </TabsList>

            <TabsContent value="overview" className="space-y-4">
              {/* Performance Stats */}
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <Card className="bg-gray-800 border-gray-700">
                  <CardContent className="p-4">
                    <div className="flex items-center gap-3">
                      <DollarSign className="h-8 w-8 text-green-400" />
                      <div>
                        <p className="text-sm text-gray-400">Total Invested</p>
                        <p className="text-xl font-bold text-white">${member.totalInvested.toLocaleString()}</p>
                      </div>
                    </div>
                  </CardContent>
                </Card>

                <Card className="bg-gray-800 border-gray-700">
                  <CardContent className="p-4">
                    <div className="flex items-center gap-3">
                      <Award className="h-8 w-8 text-gold" />
                      <div>
                        <p className="text-sm text-gray-400">Commission Generated</p>
                        <p className="text-xl font-bold text-white">${member.commissionGenerated.toFixed(2)}</p>
                      </div>
                    </div>
                  </CardContent>
                </Card>

                <Card className="bg-gray-800 border-gray-700">
                  <CardContent className="p-4">
                    <div className="flex items-center gap-3">
                      <Users className="h-8 w-8 text-blue-400" />
                      <div>
                        <p className="text-sm text-gray-400">Total Downline</p>
                        <p className="text-xl font-bold text-white">{member.totalDownline}</p>
                      </div>
                    </div>
                  </CardContent>
                </Card>

                <Card className="bg-gray-800 border-gray-700">
                  <CardContent className="p-4">
                    <div className="flex items-center gap-3">
                      <TrendingUp className="h-8 w-8 text-purple-400" />
                      <div>
                        <p className="text-sm text-gray-400">Performance</p>
                        <p className="text-xl font-bold text-white">
                          {memberStats?.performanceRank ? `#${memberStats.performanceRank}` : 'N/A'}
                        </p>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              </div>

              {/* Additional Stats */}
              {memberStats && (
                <Card className="bg-gray-800 border-gray-700">
                  <CardHeader>
                    <CardTitle className="text-white flex items-center gap-2">
                      <Target className="h-5 w-5 text-gold" />
                      Detailed Statistics
                    </CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                      <div>
                        <p className="text-gray-400">Direct Referrals</p>
                        <p className="text-white font-semibold">{memberStats.directReferrals}</p>
                      </div>
                      <div>
                        <p className="text-gray-400">Monthly Volume</p>
                        <p className="text-green-400 font-semibold">${memberStats.monthlyVolume.toLocaleString()}</p>
                      </div>
                      <div>
                        <p className="text-gray-400">Average Investment</p>
                        <p className="text-blue-400 font-semibold">${memberStats.averageInvestment.toLocaleString()}</p>
                      </div>
                      <div>
                        <p className="text-gray-400">Total Investments</p>
                        <p className="text-white font-semibold">{memberStats.totalInvestments}</p>
                      </div>
                      <div>
                        <p className="text-gray-400">Last Investment</p>
                        <p className="text-white font-semibold">
                          {memberStats.lastInvestmentDate 
                            ? new Date(memberStats.lastInvestmentDate).toLocaleDateString()
                            : 'None'
                          }
                        </p>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              )}
            </TabsContent>

            <TabsContent value="investments" className="space-y-4">
              <Card className="bg-gray-800 border-gray-700">
                <CardHeader>
                  <CardTitle className="text-white flex items-center gap-2">
                    <Wallet className="h-5 w-5 text-gold" />
                    Investment History
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  {isLoading ? (
                    <div className="flex items-center justify-center py-8">
                      <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-gold"></div>
                    </div>
                  ) : memberInvestments.length === 0 ? (
                    <div className="text-center py-8">
                      <Wallet className="h-12 w-12 text-gray-600 mx-auto mb-4" />
                      <p className="text-gray-400">No investments found</p>
                    </div>
                  ) : (
                    <div className="space-y-3">
                      {memberInvestments.map((investment) => (
                        <div key={investment.id} className="bg-gray-700/50 rounded-lg p-4">
                          <div className="flex items-center justify-between">
                            <div>
                              <h4 className="text-white font-semibold">{investment.packageName}</h4>
                              <p className="text-sm text-gray-400">
                                {new Date(investment.date).toLocaleDateString()}
                              </p>
                            </div>
                            <div className="text-right">
                              <p className="text-green-400 font-semibold">${investment.amount.toLocaleString()}</p>
                              <p className="text-xs text-gray-400">
                                {investment.shares} shares • ${investment.roi.toLocaleString()} ROI
                              </p>
                            </div>
                          </div>
                        </div>
                      ))}
                    </div>
                  )}
                </CardContent>
              </Card>
            </TabsContent>

            <TabsContent value="network" className="space-y-4">
              <Card className="bg-gray-800 border-gray-700">
                <CardHeader>
                  <CardTitle className="text-white flex items-center gap-2">
                    <Users className="h-5 w-5 text-gold" />
                    Network Overview
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="text-center py-8">
                    <Users className="h-12 w-12 text-gray-600 mx-auto mb-4" />
                    <p className="text-gray-400">Network details coming soon</p>
                    <p className="text-sm text-gray-500 mt-2">
                      This section will show detailed network structure and referral tree
                    </p>
                  </div>
                </CardContent>
              </Card>
            </TabsContent>
          </Tabs>
        </div>
      </DialogContent>
    </Dialog>
  );
};

export default MemberProfileModal;

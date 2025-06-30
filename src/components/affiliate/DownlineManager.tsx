import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { useToast } from '@/hooks/use-toast';
import MemberProfileModal from './MemberProfileModal';
import {
  Users,
  DollarSign,
  Mail,
  MessageCircle,
  Search,
  Filter,
  TrendingUp,
  Calendar,
  Eye,
  Phone,
  MapPin,
  Award,
  ChevronDown,
  ChevronRight,
  UserPlus
} from 'lucide-react';
import { useUser } from '@/contexts/UserContext';
import ApiConfig from '@/config/api';

interface DownlineMember {
  id: string;
  username: string;
  email: string;
  fullName: string;
  level: 1 | 2 | 3;
  joinDate: string;
  totalInvested: number;
  commissionGenerated: number;
  nftBonusGenerated: number;
  directReferrals: number;
  totalDownline: number;
  lastActivity: string;
  status: 'active' | 'inactive';
  country?: string;
  phone?: string;
  children?: DownlineMember[];
}

interface DownlineStats {
  totalMembers: number;
  activeMembers: number;
  totalVolume: number;
  totalCommissions: number;
  thisMonthVolume: number;
  thisMonthCommissions: number;
  level1Count: number;
  level2Count: number;
  level3Count: number;
}

const DownlineManager: React.FC = () => {
  const { user } = useUser();
  const [downlineMembers, setDownlineMembers] = useState<DownlineMember[]>([]);
  const [stats, setStats] = useState<DownlineStats>({
    totalMembers: 0,
    activeMembers: 0,
    totalVolume: 0,
    totalCommissions: 0,
    thisMonthVolume: 0,
    thisMonthCommissions: 0,
    level1Count: 0,
    level2Count: 0,
    level3Count: 0
  });
  const [searchTerm, setSearchTerm] = useState('');
  const [levelFilter, setLevelFilter] = useState<'all' | 1 | 2 | 3>('all');
  const [statusFilter, setStatusFilter] = useState<'all' | 'active' | 'inactive'>('all');
  const [viewMode, setViewMode] = useState<'list' | 'tree'>('list');
  const [expandedNodes, setExpandedNodes] = useState<Set<string>>(new Set());
  const [isLoading, setIsLoading] = useState(true);
  const [profileModalOpen, setProfileModalOpen] = useState(false);
  const [selectedMember, setSelectedMember] = useState<DownlineMember | null>(null);
  const { toast } = useToast();

  const fetchDownlineData = async () => {
    setIsLoading(true);
    try {
      const response = await fetch(`${ApiConfig.baseUrl}/affiliate/downline.php?user_id=${user?.id || 1}`, {
        credentials: 'include'
      });

      if (response.ok) {
        const data = await response.json();
        if (data.success) {
          setDownlineMembers(data.members || []);
          setStats(data.stats || {
            totalMembers: 0,
            activeMembers: 0,
            totalVolume: 0,
            totalCommissions: 0,
            thisMonthVolume: 0,
            thisMonthCommissions: 0,
            level1Count: 0,
            level2Count: 0,
            level3Count: 0
          });
        } else {
          throw new Error(data.message || 'Failed to fetch downline data');
        }
      } else {
        // No downline data yet or API error
        setDownlineMembers([]);
        setStats({
          totalMembers: 0,
          activeMembers: 0,
          totalVolume: 0,
          totalCommissions: 0,
          thisMonthVolume: 0,
          thisMonthCommissions: 0,
          level1Count: 0,
          level2Count: 0,
          level3Count: 0
        });
      }
    } catch (error) {
      console.error('Failed to fetch downline data:', error);
      setDownlineMembers([]);
      setStats({
        totalMembers: 0,
        activeMembers: 0,
        totalVolume: 0,
        totalCommissions: 0,
        thisMonthVolume: 0,
        thisMonthCommissions: 0,
        level1Count: 0,
        level2Count: 0,
        level3Count: 0
      });
    } finally {
      setIsLoading(false);
    }
  };

  const openWhatsApp = (member: DownlineMember) => {
    if (!member.phone) {
      toast({
        title: "No WhatsApp",
        description: `${member.fullName} hasn't added their WhatsApp number`,
        variant: "destructive"
      });
      return;
    }
    const cleanNumber = member.phone.replace(/[^\d+]/g, '');
    window.open(`https://wa.me/${cleanNumber}`, '_blank');
  };

  const openTelegram = (member: DownlineMember) => {
    // Get telegram username from member profile
    // For now, we'll show a message that this needs to be implemented
    toast({
      title: "Telegram Contact",
      description: "Opening Telegram contact (requires profile integration)",
    });
  };

  const viewProfile = (member: DownlineMember) => {
    setSelectedMember(member);
    setProfileModalOpen(true);
  };

  const closeProfileModal = () => {
    setProfileModalOpen(false);
    setSelectedMember(null);
  };

  useEffect(() => {
    fetchDownlineData();
  }, []);

  const filteredMembers = downlineMembers.filter(member => {
    const matchesSearch = member.fullName.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         member.username.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         member.email.toLowerCase().includes(searchTerm.toLowerCase());
    const matchesLevel = levelFilter === 'all' || member.level === levelFilter;
    const matchesStatus = statusFilter === 'all' || member.status === statusFilter;
    return matchesSearch && matchesLevel && matchesStatus;
  });

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

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold text-white">Downline Management</h2>
          <p className="text-gray-400">Manage and communicate with your referral network</p>
        </div>
        <div className="flex items-center gap-2">
          <Button
            variant={viewMode === 'list' ? 'default' : 'outline'}
            size="sm"
            onClick={() => setViewMode('list')}
          >
            List View
          </Button>
          <Button
            variant={viewMode === 'tree' ? 'default' : 'outline'}
            size="sm"
            onClick={() => setViewMode('tree')}
          >
            Tree View
          </Button>
        </div>
      </div>

      {/* Stats Overview */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <Card className="bg-gray-800 border-gray-700">
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <Users className="h-8 w-8 text-blue-400" />
              <div>
                <p className="text-sm text-gray-400">Total Members</p>
                <p className="text-2xl font-bold text-white">{stats.totalMembers}</p>
                <p className="text-xs text-green-400">{stats.activeMembers} active</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card className="bg-gray-800 border-gray-700">
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <DollarSign className="h-8 w-8 text-green-400" />
              <div>
                <p className="text-sm text-gray-400">Total Volume</p>
                <p className="text-2xl font-bold text-white">${stats.totalVolume.toLocaleString()}</p>
                <p className="text-xs text-blue-400">This month: ${stats.thisMonthVolume.toLocaleString()}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card className="bg-gray-800 border-gray-700">
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <Award className="h-8 w-8 text-gold" />
              <div>
                <p className="text-sm text-gray-400">Commissions Earned</p>
                <p className="text-2xl font-bold text-white">${stats.totalCommissions.toFixed(2)}</p>
                <p className="text-xs text-green-400">This month: ${stats.thisMonthCommissions.toFixed(2)}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card className="bg-gray-800 border-gray-700">
          <CardContent className="p-4">
            <div className="flex items-center gap-3">
              <TrendingUp className="h-8 w-8 text-purple-400" />
              <div>
                <p className="text-sm text-gray-400">Level Distribution</p>
                <div className="flex items-center gap-1 text-sm">
                  <span className="text-blue-400">L1: {stats.level1Count}</span>
                  <span className="text-green-400">L2: {stats.level2Count}</span>
                  <span className="text-purple-400">L3: {stats.level3Count}</span>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Filters */}
      <Card className="bg-gray-800 border-gray-700">
        <CardContent className="p-4">
          <div className="flex items-center gap-4 flex-wrap">
            <div className="flex-1 min-w-64">
              <div className="relative">
                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                <Input
                  placeholder="Search members..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="pl-10 bg-gray-700 border-gray-600 text-white"
                />
              </div>
            </div>
            
            <div className="flex items-center gap-2">
              <Filter className="h-4 w-4 text-gray-400" />
              <span className="text-sm text-gray-400">Level:</span>
              {['all', 1, 2, 3].map((level) => (
                <Button
                  key={level}
                  variant={levelFilter === level ? "default" : "outline"}
                  size="sm"
                  onClick={() => setLevelFilter(level as any)}
                  className="text-xs"
                >
                  {level === 'all' ? 'All' : `L${level}`}
                </Button>
              ))}
            </div>
            
            <div className="flex items-center gap-2">
              <span className="text-sm text-gray-400">Status:</span>
              {['all', 'active', 'inactive'].map((status) => (
                <Button
                  key={status}
                  variant={statusFilter === status ? "default" : "outline"}
                  size="sm"
                  onClick={() => setStatusFilter(status as any)}
                  className="text-xs capitalize"
                >
                  {status}
                </Button>
              ))}
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Members List */}
      <Card className="bg-gray-800 border-gray-700">
        <CardHeader>
          <CardTitle className="text-white flex items-center gap-2">
            <Users className="h-5 w-5 text-gold" />
            Downline Members ({filteredMembers.length})
          </CardTitle>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <div className="flex items-center justify-center py-8">
              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-gold"></div>
            </div>
          ) : filteredMembers.length === 0 ? (
            <div className="text-center py-8">
              <Users className="h-12 w-12 text-gray-600 mx-auto mb-4" />
              <p className="text-gray-400">No members found</p>
            </div>
          ) : (
            <div className="space-y-4">
              {filteredMembers.map((member) => (
                <div key={member.id} className="bg-gray-700/50 rounded-lg p-4">
                  <div className="flex items-start justify-between">
                    <div className="flex items-start gap-4 flex-1">
                      {/* Avatar */}
                      <div className="w-12 h-12 bg-gradient-to-r from-gold to-yellow-600 rounded-full flex items-center justify-center flex-shrink-0">
                        <span className="text-black font-bold text-lg">
                          {member.fullName.charAt(0)}
                        </span>
                      </div>

                      {/* Member Info */}
                      <div className="flex-1 min-w-0">
                        <div className="flex items-center gap-2 mb-2">
                          <h3 className="text-lg font-semibold text-white">{member.fullName}</h3>
                          <Badge className={getLevelColor(member.level)}>
                            Level {member.level}
                          </Badge>
                          <Badge className={getStatusColor(member.status)}>
                            {member.status}
                          </Badge>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                          <div>
                            <p className="text-gray-400">Username</p>
                            <p className="text-white font-medium">@{member.username}</p>
                          </div>
                          <div>
                            <p className="text-gray-400">Total Invested</p>
                            <p className="text-green-400 font-semibold">${member.totalInvested.toLocaleString()}</p>
                          </div>
                          <div>
                            <p className="text-gray-400">Commission Generated</p>
                            <p className="text-blue-400 font-semibold">${member.commissionGenerated.toFixed(2)}</p>
                          </div>
                          <div>
                            <p className="text-gray-400">Downline Size</p>
                            <p className="text-purple-400 font-semibold">{member.totalDownline} members</p>
                          </div>
                        </div>

                        <div className="flex items-center gap-4 mt-3 text-xs text-gray-400">
                          <div className="flex items-center gap-1">
                            <Calendar className="h-3 w-3" />
                            Joined: {new Date(member.joinDate).toLocaleDateString()}
                          </div>
                          <div className="flex items-center gap-1">
                            <TrendingUp className="h-3 w-3" />
                            Last Active: {new Date(member.lastActivity).toLocaleDateString()}
                          </div>
                          {member.country && (
                            <div className="flex items-center gap-1">
                              <MapPin className="h-3 w-3" />
                              {member.country}
                            </div>
                          )}
                        </div>
                      </div>
                    </div>

                    {/* Action Buttons */}
                    <div className="flex items-center gap-2 flex-shrink-0">
                      <Button
                        size="sm"
                        variant="outline"
                        onClick={() => viewProfile(member)}
                        className="border-blue-500/30 text-blue-400 hover:bg-blue-500/20"
                      >
                        <Eye className="h-4 w-4" />
                      </Button>
                      <Button
                        size="sm"
                        variant="outline"
                        onClick={() => openWhatsApp(member)}
                        className="border-green-500/30 text-green-400 hover:bg-green-500/20"
                        title="Contact via WhatsApp"
                      >
                        <MessageCircle className="h-4 w-4" />
                      </Button>
                      <Button
                        size="sm"
                        variant="outline"
                        onClick={() => openTelegram(member)}
                        className="border-blue-500/30 text-blue-400 hover:bg-blue-500/20"
                        title="Contact via Telegram"
                      >
                        <MessageCircle className="h-4 w-4" />
                      </Button>
                      {member.phone && (
                        <Button
                          size="sm"
                          variant="outline"
                          onClick={() => window.open(`tel:${member.phone}`)}
                          className="border-purple-500/30 text-purple-400 hover:bg-purple-500/20"
                          title="Call directly"
                        >
                          <Phone className="h-4 w-4" />
                        </Button>
                      )}
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>

      {/* Member Profile Modal */}
      <MemberProfileModal
        isOpen={profileModalOpen}
        onClose={closeProfileModal}
        member={selectedMember}
      />
    </div>
  );
};

export default DownlineManager;

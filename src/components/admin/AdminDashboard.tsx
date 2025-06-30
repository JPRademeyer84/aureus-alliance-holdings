import React, { useState, useEffect } from 'react';
import { useAdmin } from '@/contexts/AdminContext';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Users,
  MessageSquare,
  Package,
  Wallet,
  Shield,
  Activity,
  TrendingUp,
  AlertCircle,
  CheckCircle,
  Clock,
  Mail,
  UserCheck,
  UserX,
  MessageCircle,
  DollarSign,
  BarChart3,
  Settings,
  Eye,
  Plus,
  Star,
  FileText
} from 'lucide-react';
import ApiConfig from '@/config/api';

interface DashboardStats {
  users: {
    total: number;
    active: number;
    inactive: number;
    new_today: number;
  };
  admins: {
    total: number;
    online: number;
    super_admins: number;
    regular_admins: number;
    chat_support: number;
  };
  messages: {
    contact_messages: number;
    unread_contact: number;
    chat_sessions: number;
    active_chats: number;
    offline_messages: number;
  };
  system: {
    wallets_configured: number;
    packages_available: number;
    recent_activity: number;
  };
  certificates: {
    total_certificates: number;
    pending_generation: number;
    completed_certificates: number;
    failed_generation: number;
    valid_certificates: number;
    converted_to_nft: number;
  };
}

interface QuickAction {
  title: string;
  description: string;
  icon: React.ReactNode;
  action: () => void;
  color: string;
  permission?: 'super_admin' | 'admin' | 'chat_support';
}

interface AdminDashboardProps {
  onNavigate: (tab: string) => void;
}

const AdminDashboard: React.FC<AdminDashboardProps> = ({ onNavigate }) => {
  const { admin, hasPermission } = useAdmin();
  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    fetchDashboardStats();
  }, []);

  const fetchDashboardStats = async () => {
    if (!admin?.id) return;

    setIsLoading(true);
    try {
      const response = await fetch(`http://localhost/Aureus%201%20-%20Complex/api/simple-dashboard-stats.php`, {
        method: 'GET',
        credentials: 'include'
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const data = await response.json();

      if (data.success) {
        setStats(data.data);
      } else {
        throw new Error(data.message || 'Failed to fetch dashboard stats');
      }
    } catch (error) {
      console.error('Failed to fetch dashboard stats:', error);
      // Set empty stats on error (NO MOCK DATA)
      setStats({
        users: { total: 0, active: 0, inactive: 0, new_today: 0 },
        admins: { total: 0, online: 0, super_admins: 0, regular_admins: 0, chat_support: 0 },
        messages: { contact_messages: 0, unread_contact: 0, chat_sessions: 0, active_chats: 0, offline_messages: 0 },
        system: { wallets_configured: 0, packages_available: 0, recent_activity: 0 }
      });
    } finally {
      setIsLoading(false);
    }
  };

  const quickActions: QuickAction[] = [
    {
      title: 'Manage Users',
      description: 'View and manage platform users',
      icon: <Users className="h-5 w-5" />,
      action: () => onNavigate('users'),
      color: 'bg-blue-500',
      permission: 'admin'
    },
    {
      title: 'Contact Messages',
      description: 'Review contact form submissions',
      icon: <Mail className="h-5 w-5" />,
      action: () => onNavigate('contact'),
      color: 'bg-purple-500'
    },
    {
      title: 'Investment Packages',
      description: 'Manage investment offerings',
      icon: <Package className="h-5 w-5" />,
      action: () => onNavigate('packages'),
      color: 'bg-orange-500',
      permission: 'admin'
    },
    {
      title: 'Payment Wallets',
      description: 'Configure payment addresses',
      icon: <Wallet className="h-5 w-5" />,
      action: () => onNavigate('wallets'),
      color: 'bg-yellow-500',
      permission: 'admin'
    },
    {
      title: 'Admin Users',
      description: 'Manage admin accounts',
      icon: <Shield className="h-5 w-5" />,
      action: () => onNavigate('admins'),
      color: 'bg-red-500',
      permission: 'admin'
    },
    {
      title: 'KYC Verification',
      description: 'Review and approve user documents',
      icon: <UserCheck className="h-5 w-5" />,
      action: () => onNavigate('kyc'),
      color: 'bg-cyan-500',
      permission: 'admin'
    },
    {
      title: 'Enhanced KYC Management',
      description: 'Comprehensive KYC profile management',
      icon: <Shield className="h-5 w-5" />,
      action: () => onNavigate('enhanced-kyc'),
      color: 'bg-teal-500',
      permission: 'admin'
    },
    {
      title: 'Commission Plans',
      description: 'Manage referral commission structures',
      icon: <TrendingUp className="h-5 w-5" />,
      action: () => onNavigate('commissions'),
      color: 'bg-emerald-500',
      permission: 'admin'
    },
    {
      title: 'Commission Management',
      description: 'Process withdrawals and manage commissions',
      icon: <DollarSign className="h-5 w-5" />,
      action: () => onNavigate('commission-management'),
      color: 'bg-green-600',
      permission: 'admin'
    },
    {
      title: 'System Status',
      description: 'Monitor system health and security',
      icon: <Shield className="h-5 w-5" />,
      action: () => onNavigate('system-status'),
      color: 'bg-purple-600',
      permission: 'admin'
    },
    {
      title: 'System Validation',
      description: 'Run complete end-to-end workflow tests',
      icon: <Activity className="h-5 w-5" />,
      action: () => onNavigate('system-validation'),
      color: 'bg-indigo-600',
      permission: 'admin'
    },
    {
      title: 'Ultimate Security',
      description: 'Military-grade security verification',
      icon: <Shield className="h-5 w-5" />,
      action: () => onNavigate('ultimate-security'),
      color: 'bg-red-600',
      permission: 'admin'
    },
    {
      title: 'Offline Messages',
      description: 'Review offline chat messages',
      icon: <Clock className="h-5 w-5" />,
      action: () => onNavigate('offline'),
      color: 'bg-indigo-500'
    },
    {
      title: 'Customer Reviews',
      description: 'View customer feedback and ratings',
      icon: <Star className="h-5 w-5" />,
      action: () => onNavigate('reviews'),
      color: 'bg-pink-500'
    },
    {
      title: 'Certificate Management',
      description: 'Generate and manage share certificates',
      icon: <FileText className="h-5 w-5" />,
      action: () => onNavigate('certificates'),
      color: 'bg-indigo-500',
      permission: 'admin'
    },
    {
      title: 'Certificate Templates',
      description: 'Manage certificate design templates',
      icon: <Settings className="h-5 w-5" />,
      action: () => onNavigate('certificate-templates'),
      color: 'bg-purple-500',
      permission: 'admin'
    }
  ];

  const filteredActions = quickActions.filter(action => 
    !action.permission || hasPermission(action.permission)
  );

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="text-center">
          <Activity className="h-8 w-8 animate-spin mx-auto mb-4 text-blue-500" />
          <p className="text-muted-foreground">Loading dashboard...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Welcome Section */}
      <div className="bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg p-6 text-white shadow-lg">
        <div className="flex items-center justify-between">
          <div>
            <h2 className="text-2xl font-bold mb-2">
              Welcome back, {admin?.full_name || admin?.username}!
            </h2>
            <p className="text-blue-100">
              You're logged in as {admin?.role ? admin.role.replace('_', ' ') : 'Admin'} • Last activity: {new Date().toLocaleString()}
            </p>
          </div>
          <div className="text-right">
            <Badge variant="secondary" className="bg-white/20 text-white border-white/30">
              {admin?.role ? admin.role.replace('_', ' ').toUpperCase() : 'ADMIN'}
            </Badge>
          </div>
        </div>
      </div>

      {/* Statistics Overview */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        {/* Users Stats */}
        <Card className="bg-gray-800 border-gray-700">
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium text-gray-200">Total Users</CardTitle>
            <Users className="h-4 w-4 text-gray-400" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-white">{stats?.users.total || 0}</div>
            <div className="flex items-center text-xs text-gray-400 mt-1">
              <UserCheck className="h-3 w-3 mr-1 text-green-400" />
              {stats?.users.active || 0} active
              <UserX className="h-3 w-3 ml-2 mr-1 text-red-400" />
              {stats?.users.inactive || 0} inactive
            </div>
          </CardContent>
        </Card>

        {/* Messages Stats */}
        <Card className="bg-gray-800 border-gray-700">
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium text-gray-200">Messages</CardTitle>
            <MessageSquare className="h-4 w-4 text-gray-400" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-white">{stats?.messages.contact_messages || 0}</div>
            <div className="flex items-center text-xs text-gray-400 mt-1">
              <AlertCircle className="h-3 w-3 mr-1 text-orange-400" />
              {stats?.messages.unread_contact || 0} unread
            </div>
          </CardContent>
        </Card>

        {/* Chat Sessions */}
        <Card className="bg-gray-800 border-gray-700">
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium text-gray-200">Live Chats</CardTitle>
            <MessageCircle className="h-4 w-4 text-gray-400" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-white">{stats?.messages.chat_sessions || 0}</div>
            <div className="flex items-center text-xs text-gray-400 mt-1">
              <Activity className="h-3 w-3 mr-1 text-green-400" />
              {stats?.messages.active_chats || 0} active now
            </div>
          </CardContent>
        </Card>

        {/* Admin Users */}
        <Card className="bg-gray-800 border-gray-700">
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium text-gray-200">Admin Users</CardTitle>
            <Shield className="h-4 w-4 text-gray-400" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-white">{stats?.admins.total || 1}</div>
            <div className="flex items-center text-xs text-gray-400 mt-1">
              <CheckCircle className="h-3 w-3 mr-1 text-green-400" />
              {stats?.admins.online || 1} online
            </div>
          </CardContent>
        </Card>

        {/* Certificates Stats */}
        {hasPermission('admin') && (
          <Card className="bg-gray-800 border-gray-700">
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium text-gray-200">Certificates</CardTitle>
              <FileText className="h-4 w-4 text-gray-400" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-white">{stats?.certificates?.total_certificates || 0}</div>
              <div className="flex items-center text-xs text-gray-400 mt-1">
                <span className="text-green-400">{stats?.certificates?.completed_certificates || 0} completed</span>
                <span className="mx-1">•</span>
                <span className="text-yellow-400">{stats?.certificates?.pending_generation || 0} pending</span>
              </div>
            </CardContent>
          </Card>
        )}
      </div>

      {/* System Status */}
      {hasPermission('admin') && (
        <Card className="bg-gray-800 border-gray-700">
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-white">
              <BarChart3 className="h-5 w-5" />
              System Overview
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div className="flex items-center justify-between p-3 bg-gray-700 rounded-lg">
                <div className="flex items-center gap-2">
                  <Wallet className="h-4 w-4 text-blue-400" />
                  <span className="text-sm font-medium text-gray-200">Payment Wallets</span>
                </div>
                <Badge variant="outline" className="border-gray-600 text-gray-300">{stats?.system.wallets_configured || 0} configured</Badge>
              </div>
              <div className="flex items-center justify-between p-3 bg-gray-700 rounded-lg">
                <div className="flex items-center gap-2">
                  <Package className="h-4 w-4 text-green-400" />
                  <span className="text-sm font-medium text-gray-200">Investment Packages</span>
                </div>
                <Badge variant="outline" className="border-gray-600 text-gray-300">{stats?.system.packages_available || 0} available</Badge>
              </div>
              <div className="flex items-center justify-between p-3 bg-gray-700 rounded-lg">
                <div className="flex items-center gap-2">
                  <TrendingUp className="h-4 w-4 text-purple-400" />
                  <span className="text-sm font-medium text-gray-200">Recent Activity</span>
                </div>
                <Badge variant="outline" className="border-gray-600 text-gray-300">{stats?.system.recent_activity || 0} events</Badge>
              </div>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Quick Actions */}
      <Card className="bg-gray-800 border-gray-700">
        <CardHeader>
          <CardTitle className="flex items-center gap-2 text-white">
            <Settings className="h-5 w-5" />
            Quick Actions
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {filteredActions.map((action, index) => (
              <Button
                key={index}
                variant="outline"
                className="h-auto p-4 flex flex-col items-start gap-2 bg-gray-700 border-gray-600 text-gray-200 hover:bg-gray-600"
                onClick={action.action}
              >
                <div className={`p-2 rounded-md ${action.color} text-white`}>
                  {action.icon}
                </div>
                <div className="text-left">
                  <div className="font-medium text-white">{action.title}</div>
                  <div className="text-xs text-gray-400">{action.description}</div>
                </div>
              </Button>
            ))}
          </div>
        </CardContent>
      </Card>

      {/* Recent Activity */}
      <Card className="bg-gray-800 border-gray-700">
        <CardHeader>
          <CardTitle className="flex items-center gap-2 text-white">
            <Activity className="h-5 w-5" />
            Recent Activity
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-3">
            <div className="flex items-center gap-3 p-3 bg-gray-700 rounded-lg">
              <div className="p-2 bg-blue-500 rounded-full">
                <Users className="h-3 w-3 text-white" />
              </div>
              <div className="flex-1">
                <p className="text-sm font-medium text-gray-200">New user registered</p>
                <p className="text-xs text-gray-400">2 minutes ago</p>
              </div>
            </div>
            <div className="flex items-center gap-3 p-3 bg-gray-700 rounded-lg">
              <div className="p-2 bg-green-500 rounded-full">
                <MessageCircle className="h-3 w-3 text-white" />
              </div>
              <div className="flex-1">
                <p className="text-sm font-medium text-gray-200">New chat session started</p>
                <p className="text-xs text-gray-400">5 minutes ago</p>
              </div>
            </div>
            <div className="flex items-center gap-3 p-3 bg-gray-700 rounded-lg">
              <div className="p-2 bg-purple-500 rounded-full">
                <Mail className="h-3 w-3 text-white" />
              </div>
              <div className="flex-1">
                <p className="text-sm font-medium text-gray-200">Contact form submitted</p>
                <p className="text-xs text-gray-400">10 minutes ago</p>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
};

export default AdminDashboard;

import React from 'react';
import { useAdmin } from '@/contexts/AdminContext';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Users,
  Package,
  Shield,
  Mail,
  Settings,
  FileText,
  Star,
  Gift,
  Trophy
} from '@/components/SafeIcons';

// Safe admin icons
const LayoutDashboard = ({ className }: { className?: string }) => <span className={className}>📊</span>;
const MessageSquare = ({ className }: { className?: string }) => <span className={className}>💬</span>;
const Wallet = ({ className }: { className?: string }) => <span className={className}>👛</span>;
const MessageCircle = ({ className }: { className?: string }) => <span className={className}>💬</span>;
const Clock = ({ className }: { className?: string }) => <span className={className}>🕐</span>;
const LogOut = ({ className }: { className?: string }) => <span className={className}>🚪</span>;
const ChevronLeft = ({ className }: { className?: string }) => <span className={className}>◀</span>;
const ChevronRight = ({ className }: { className?: string }) => <span className={className}>▶</span>;
const TrendingUp = ({ className }: { className?: string }) => <span className={className}>📈</span>;
const UserCheck = ({ className }: { className?: string }) => <span className={className}>👤✅</span>;
const Bug = ({ className }: { className?: string }) => <span className={className}>🐛</span>;
const Globe = ({ className }: { className?: string }) => <span className={className}>🌐</span>;
const Activity = ({ className }: { className?: string }) => <span className={className}>⚡</span>;
const Heart = ({ className }: { className?: string }) => <span className={className}>💜</span>;
const Building2 = ({ className }: { className?: string }) => <span className={className}>🏢</span>;
const Share2 = ({ className }: { className?: string }) => <span className={className}>📤</span>;

interface NavigationItem {
  id: string;
  label: string;
  icon: React.ReactNode;
  permission?: 'super_admin' | 'admin' | 'chat_support';
  badge?: number;
}

interface AdminSidebarProps {
  activeTab: string;
  onTabChange: (tab: string) => void;
  isCollapsed: boolean;
  onToggleCollapse: () => void;
}

const AdminSidebar: React.FC<AdminSidebarProps> = ({ 
  activeTab, 
  onTabChange, 
  isCollapsed, 
  onToggleCollapse 
}) => {
  const { admin, hasPermission, logout } = useAdmin();

  const navigationItems: NavigationItem[] = [
    {
      id: 'dashboard',
      label: 'Dashboard',
      icon: <LayoutDashboard className="h-4 w-4" />
    },
    {
      id: 'packages',
      label: 'Investment Packages',
      icon: <Package className="h-4 w-4" />,
      permission: 'admin'
    },
    {
      id: 'wallets',
      label: 'Payment Wallets',
      icon: <Wallet className="h-4 w-4" />,
      permission: 'admin'
    },
    {
      id: 'users',
      label: 'User Management',
      icon: <Users className="h-4 w-4" />,
      permission: 'admin'
    },
    {
      id: 'commissions',
      label: 'Commission Plans',
      icon: <TrendingUp className="h-4 w-4" />,
      permission: 'admin'
    },
    {
      id: 'phases',
      label: 'Phase Management',
      icon: <Activity className="h-4 w-4" />,
      permission: 'admin'
    },
    {
      id: 'competitions',
      label: 'Competitions',
      icon: <Trophy className="h-4 w-4" />,
      permission: 'admin'
    },
    {
      id: 'npo-fund',
      label: 'NPO Fund',
      icon: <Heart className="h-4 w-4" />,
      permission: 'admin'
    },
    {
      id: 'share-certificates',
      label: 'Share Certificates',
      icon: <FileText className="h-4 w-4" />,
      permission: 'admin'
    },
    {
      id: 'coupons',
      label: 'NFT Coupons',
      icon: <Gift className="h-4 w-4" />,
      permission: 'admin'
    },
    {
      id: 'contact',
      label: 'Contact Messages',
      icon: <Mail className="h-4 w-4" />
    },
    {
      id: 'chat',
      label: 'Live Chat',
      icon: <MessageCircle className="h-4 w-4" />
    },
    {
      id: 'offline',
      label: 'Offline Messages',
      icon: <Clock className="h-4 w-4" />
    },
    {
      id: 'reviews',
      label: 'Customer Reviews',
      icon: <Star className="h-4 w-4" />
    },
    {
      id: 'admins',
      label: 'Admin Users',
      icon: <Shield className="h-4 w-4" />,
      permission: 'admin'
    },
    {
      id: 'kyc',
      label: 'KYC Management',
      icon: <UserCheck className="h-4 w-4" />,
      permission: 'admin'
    },
    {
      id: 'certificates',
      label: 'Certificate Management',
      icon: <FileText className="h-4 w-4" />,
      permission: 'admin'
    },
    {
      id: 'certificate-templates',
      label: 'Certificate Templates',
      icon: <Settings className="h-4 w-4" />,
      permission: 'admin'
    },
    {
      id: 'bank-payments',
      label: 'Bank Payment Management',
      icon: <Building2 className="h-4 w-4" />,
      permission: 'admin'
    },
    {
      id: 'investments',
      label: 'Investment Management',
      icon: <TrendingUp className="h-4 w-4" />,
      permission: 'admin'
    },
    {
      id: 'prize-manager',
      label: 'Gold Diggers Prizes',
      icon: <Trophy className="h-4 w-4" />,
      permission: 'admin'
    },
    {
      id: 'social-leaderboard',
      label: 'Social Sharing Stats',
      icon: <Share2 className="h-4 w-4" />,
      permission: 'admin'
    },
    {
      id: 'performance-monitor',
      label: 'Performance Monitor',
      icon: <Activity className="h-4 w-4" />,
      permission: 'admin'
    },
    {
      id: 'terms-compliance',
      label: 'Terms Compliance',
      icon: <FileText className="h-4 w-4" />,
      permission: 'admin'
    },
    {
      id: 'translations',
      label: 'Translation Management',
      icon: <Globe className="h-4 w-4" />,
      permission: 'admin'
    },
    {
      id: 'english-editor',
      label: 'English Editor',
      icon: <Globe className="h-4 w-4" />,
      permission: 'admin'
    },
    {
      id: 'debug',
      label: 'Debug Manager',
      icon: <Bug className="h-4 w-4" />,
      permission: 'admin'
    }
  ];

  const filteredItems = navigationItems.filter(item =>
    !item.permission || hasPermission(item.permission)
  );

  const getRoleBadgeColor = (role: string) => {
    switch (role) {
      case 'super_admin':
        return 'bg-red-500 text-white';
      case 'admin':
        return 'bg-blue-500 text-white';
      case 'chat_support':
        return 'bg-green-500 text-white';
      default:
        return 'bg-gray-500 text-white';
    }
  };

  return (
    <div className={`bg-gray-900 border-r border-gray-700 transition-all duration-300 ${
      isCollapsed ? 'w-16' : 'w-64'
    } flex flex-col h-full`}>
      {/* Header */}
      <div className="p-4 border-b border-gray-700">
        <div className="flex items-center justify-between">
          {!isCollapsed && (
            <div className="flex items-center gap-2">
              <div className="w-8 h-8 bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg flex items-center justify-center">
                <Shield className="h-4 w-4 text-white" />
              </div>
              <span className="font-bold text-white">Admin Panel</span>
            </div>
          )}
          <Button
            variant="ghost"
            size="sm"
            onClick={onToggleCollapse}
            className="p-1 h-8 w-8 text-gray-400 hover:text-white hover:bg-gray-800"
          >
            {isCollapsed ? (
              <ChevronRight className="h-4 w-4" />
            ) : (
              <ChevronLeft className="h-4 w-4" />
            )}
          </Button>
        </div>
      </div>

      {/* Admin Info */}
      {!isCollapsed && (
        <div className="p-4 border-b border-gray-700">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full flex items-center justify-center">
              <span className="text-white font-semibold text-sm">
                {(admin?.full_name || admin?.username || 'A').charAt(0).toUpperCase()}
              </span>
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-sm font-medium text-white truncate">
                {admin?.full_name || admin?.username}
              </p>
              <Badge
                className={`text-xs ${getRoleBadgeColor(admin?.role || '')}`}
                variant="secondary"
              >
                {admin?.role ? admin.role.replace('_', ' ').toUpperCase() : 'ADMIN'}
              </Badge>
            </div>
          </div>
        </div>
      )}

      {/* Navigation */}
      <nav className="flex-1 p-2 space-y-1">
        {filteredItems.map((item) => (
          <Button
            key={item.id}
            variant={activeTab === item.id ? "default" : "ghost"}
            className={`w-full justify-start ${
              isCollapsed ? 'px-2' : 'px-3'
            } ${
              activeTab === item.id
                ? 'bg-blue-600 text-white hover:bg-blue-700'
                : 'text-gray-300 hover:bg-gray-800 hover:text-white'
            }`}
            onClick={() => onTabChange(item.id)}
          >
            <div className="flex items-center gap-3 w-full">
              {item.icon}
              {!isCollapsed && (
                <>
                  <span className="flex-1 text-left">{item.label}</span>
                  {item.badge && item.badge > 0 && (
                    <Badge 
                      variant="secondary" 
                      className="bg-red-500 text-white text-xs"
                    >
                      {item.badge}
                    </Badge>
                  )}
                </>
              )}
            </div>
          </Button>
        ))}
      </nav>

      {/* Footer */}
      <div className="p-2 border-t border-gray-700">
        <Button
          variant="ghost"
          className={`w-full justify-start text-red-400 hover:bg-red-900/20 hover:text-red-300 ${
            isCollapsed ? 'px-2' : 'px-3'
          }`}
          onClick={logout}
        >
          <LogOut className="h-4 w-4" />
          {!isCollapsed && <span className="ml-3">Logout</span>}
        </Button>
      </div>
    </div>
  );
};

export default AdminSidebar;

import React from 'react';
import { useAdmin } from '@/contexts/AdminContext';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Bell, Search, Settings, LogOut } from 'lucide-react';

interface AdminHeaderProps {
  activeTab: string;
  onSearch?: (query: string) => void;
}

const AdminHeader: React.FC<AdminHeaderProps> = ({ activeTab, onSearch }) => {
  const { admin, logout } = useAdmin();

  const getTabInfo = (tab: string) => {
    const tabInfo = {
      dashboard: {
        title: 'Dashboard',
        description: 'Overview of your admin panel'
      },
      packages: {
        title: 'Investment Packages',
        description: 'Manage investment packages and offerings'
      },
      wallets: {
        title: 'Payment Wallets',
        description: 'Configure payment wallet addresses'
      },
      users: {
        title: 'User Management',
        description: 'Manage platform users and accounts'
      },
      contact: {
        title: 'Contact Messages',
        description: 'Review and respond to contact messages'
      },
      chat: {
        title: 'Live Chat',
        description: 'Handle live customer support chats'
      },
      offline: {
        title: 'Offline Messages',
        description: 'Review offline chat messages'
      },
      admins: {
        title: 'Admin Users',
        description: 'Manage admin users and permissions'
      }
    };

    return tabInfo[tab as keyof typeof tabInfo] || {
      title: 'Admin Panel',
      description: 'Admin panel management'
    };
  };

  const { title, description } = getTabInfo(activeTab);

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
    <header className="bg-white border-b border-gray-200 px-6 py-4">
      <div className="flex items-center justify-between">
        {/* Left side - Page info */}
        <div>
          <h1 className="text-2xl font-bold text-gray-900">{title}</h1>
          <p className="text-gray-600 text-sm mt-1">{description}</p>
        </div>

        {/* Right side - User info and actions */}
        <div className="flex items-center gap-4">
          {/* Search */}
          {onSearch && (
            <div className="relative">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
              <input
                type="text"
                placeholder="Search..."
                className="pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                onChange={(e) => onSearch(e.target.value)}
              />
            </div>
          )}

          {/* Notifications */}
          <Button variant="ghost" size="sm" className="relative">
            <Bell className="h-4 w-4" />
            <span className="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
              3
            </span>
          </Button>

          {/* Settings */}
          <Button variant="ghost" size="sm">
            <Settings className="h-4 w-4" />
          </Button>

          {/* User info */}
          <div className="flex items-center gap-3 pl-4 border-l border-gray-200">
            <div className="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full flex items-center justify-center">
              <span className="text-white font-semibold text-sm">
                {(admin?.full_name || admin?.username || 'A').charAt(0).toUpperCase()}
              </span>
            </div>
            <div className="text-right">
              <p className="text-sm font-medium text-gray-900">
                {admin?.full_name || admin?.username}
              </p>
              <Badge 
                className={`text-xs ${getRoleBadgeColor(admin?.role || '')}`}
                variant="secondary"
              >
                {admin?.role.replace('_', ' ').toUpperCase()}
              </Badge>
            </div>
          </div>

          {/* Logout */}
          <Button 
            variant="ghost" 
            size="sm" 
            onClick={logout}
            className="text-red-600 hover:text-red-700 hover:bg-red-50"
          >
            <LogOut className="h-4 w-4" />
          </Button>
        </div>
      </div>
    </header>
  );
};

export default AdminHeader;

import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { useUser } from '@/contexts/UserContext';
import { useSimpleTranslation as useTranslation, ST as T } from '@/components/SimpleTranslator';
import { useWalletConnection } from '@/pages/investment/useWalletConnection';
import { WalletProviderName } from '@/pages/investment/useWalletConnection';
import {
  Package,
  User,
  Mail,
  Star,
  Users,
  Trophy,
  Gift,
  Shield,
  Award,
  FileText
} from '@/components/SafeIcons';

// Safe dashboard icons
const LayoutDashboard = ({ className }: { className?: string }) => <span className={className}>📊</span>;
const BarChart3 = ({ className }: { className?: string }) => <span className={className}>📊</span>;
const PieChart = ({ className }: { className?: string }) => <span className={className}>🥧</span>;
const MessageCircle = ({ className }: { className?: string }) => <span className={className}>💬</span>;
const Wallet = ({ className }: { className?: string }) => <span className={className}>👛</span>;
const LogOut = ({ className }: { className?: string }) => <span className={className}>🚪</span>;
const ChevronLeft = ({ className }: { className?: string }) => <span className={className}>◀</span>;
const ChevronRight = ({ className }: { className?: string }) => <span className={className}>▶</span>;
const History = ({ className }: { className?: string }) => <span className={className}>📜</span>;
const Timer = ({ className }: { className?: string }) => <span className={className}>⏱️</span>;

interface NavigationItem {
  id: string;
  label: string;
  icon: React.ReactNode;
  badge?: string;
}

interface UserSidebarProps {
  activeTab: string;
  onTabChange: (tab: string) => void;
  onLogout: () => void;
}

const UserSidebar: React.FC<UserSidebarProps> = ({ activeTab, onTabChange, onLogout }) => {
  const { user } = useUser();
  const { translate } = useTranslation();
  const [isCollapsed, setIsCollapsed] = useState(false);
  const {
    walletAddress,
    connectWallet,
    disconnectWallet,
    currentProvider,
    isConnecting
  } = useWalletConnection();

  const navigationItems: NavigationItem[] = [
    {
      id: 'dashboard',
      label: translate('dashboard', 'Dashboard'),
      icon: <LayoutDashboard className="h-4 w-4" />
    },
    {
      id: 'profile',
      label: translate('my_profile', 'My Profile'),
      icon: <User className="h-4 w-4" />
    },
    {
      id: 'kyc-profile',
      label: translate('kyc_profile', 'KYC Profile'),
      icon: <Shield className="h-4 w-4" />
    },
    {
      id: 'kyc',
      label: translate('kyc_verification', 'KYC Verification'),
      icon: <FileText className="h-4 w-4" />
    },
    {
      id: 'kyc-levels',
      label: translate('kyc_levels', 'KYC Levels'),
      icon: <Award className="h-4 w-4" />
    },
    {
      id: 'packages',
      label: translate('investment_packages', 'Investment Packages'),
      icon: <Package className="h-4 w-4" />
    },
    {
      id: 'history',
      label: translate('investment_history', 'Investment History'),
      icon: <History className="h-4 w-4" />
    },
    {
      id: 'certificates',
      label: translate('share_certificates', 'Share Certificates'),
      icon: <FileText className="h-4 w-4" />
    },
    {
      id: 'countdown',
      label: translate('delivery_countdown', 'Delivery Countdown'),
      icon: <Timer className="h-4 w-4" />,
      badge: '180d'
    },
    {
      id: 'portfolio',
      label: translate('portfolio_overview', 'Portfolio Overview'),
      icon: <PieChart className="h-4 w-4" />
    },
    {
      id: 'affiliate',
      label: translate('affiliate_program', 'Affiliate Program'),
      icon: <Users className="h-4 w-4" />
    },
    {
      id: 'commissions',
      label: translate('commission_wallet', 'Commission Wallet'),
      icon: <Wallet className="h-4 w-4" />
    },
    {
      id: 'coupons',
      label: translate('nft_coupons', 'NFT Coupons'),
      icon: <Gift className="h-4 w-4" />
    },
    {
      id: 'leaderboard',
      label: translate('gold_diggers_club', 'Gold Diggers Club'),
      icon: <Trophy className="h-4 w-4" />,
      badge: '$250K'
    },
    {
      id: 'support',
      label: translate('contact_support', 'Contact Support'),
      icon: <MessageCircle className="h-4 w-4" />
    }
  ];

  return (
    <div className={`bg-gray-900 border-r border-gray-700 transition-all duration-300 ${
      isCollapsed ? 'w-16' : 'w-64'
    } flex flex-col h-full`}>
      {/* Header */}
      <div className="p-4 border-b border-gray-700">
        <div className="flex items-center justify-between">
          {!isCollapsed && (
            <div className="flex items-center space-x-3">
              <div className="w-8 h-8 bg-gradient-to-r from-gold to-yellow-600 rounded-lg flex items-center justify-center">
                <Star className="h-4 w-4 text-black" />
              </div>
              <div>
                <h2 className="text-white font-semibold text-sm">
                  <T k="aureus_capital" fallback="Aureus Capital" />
                </h2>
                <p className="text-gray-400 text-xs">
                  <T k="investment_portal" fallback="Investment Portal" />
                </p>
              </div>
            </div>
          )}
          <Button
            onClick={() => setIsCollapsed(!isCollapsed)}
            variant="ghost"
            size="sm"
            className="text-gray-400 hover:text-white hover:bg-gray-800"
          >
            {isCollapsed ? (
              <ChevronRight className="h-4 w-4" />
            ) : (
              <ChevronLeft className="h-4 w-4" />
            )}
          </Button>
        </div>
      </div>

      {/* User Info */}
      <div className="p-4 border-b border-gray-700">
        <div className="flex items-center space-x-3">
          <div className="w-8 h-8 bg-gray-700 rounded-full flex items-center justify-center">
            <User className="h-4 w-4 text-gray-300" />
          </div>
          {!isCollapsed && (
            <div className="flex-1 min-w-0">
              <p className="text-white font-medium text-sm truncate">{user?.username}</p>
              <p className="text-gray-400 text-xs truncate">{user?.email}</p>
              <Badge variant="outline" className="mt-1 text-xs border-gold/30 text-gold">
                <T k="participant_badge" fallback="PARTICIPANT" />
              </Badge>
            </div>
          )}
        </div>
      </div>

      {/* Navigation */}
      <nav className="flex-1 p-4 space-y-2">
        {navigationItems.map((item) => (
          <Button
            key={item.id}
            onClick={() => onTabChange(item.id)}
            variant={activeTab === item.id ? "secondary" : "ghost"}
            className={`w-full justify-start text-left ${
              activeTab === item.id
                ? 'bg-gold/10 text-gold border-gold/30 hover:bg-gold/20'
                : 'text-gray-300 hover:text-white hover:bg-gray-800'
            } ${isCollapsed ? 'px-2' : 'px-3'}`}
            title={isCollapsed ? item.label : undefined}
          >
            <div className="flex items-center space-x-3 w-full">
              {item.icon}
              {!isCollapsed && (
                <>
                  <span className="flex-1">{item.label}</span>
                  {item.badge && (
                    <Badge variant="secondary" className="text-xs">
                      {item.badge}
                    </Badge>
                  )}
                </>
              )}
            </div>
          </Button>
        ))}
      </nav>

      {/* Wallet Connection */}
      <div className="p-4 border-t border-gray-700">
        {!isCollapsed && (
          <div className="space-y-3">
            <h3 className="text-xs font-semibold text-gray-400 uppercase tracking-wider">
              <T k="wallet_connection" fallback="Wallet Connection" />
            </h3>

            {!walletAddress ? (
              <div className="space-y-2">
                <p className="text-xs text-gray-500">
                  <T k="connect_wallet_to_start" fallback="Connect wallet to start investing" />
                </p>
                <Button
                  size="sm"
                  onClick={() => connectWallet("safepal")}
                  disabled={isConnecting}
                  className="w-full bg-blue-600 hover:bg-blue-700 text-white text-xs py-1 h-7"
                >
                  {isConnecting ? (
                    <div className="animate-spin rounded-full h-3 w-3 border-b-2 border-white"></div>
                  ) : (
                    <T k="connect_safepal" fallback="Connect SafePal" />
                  )}
                </Button>
              </div>
            ) : (
              <div className="space-y-2">
                <div className="flex items-center gap-2">
                  <div className="w-2 h-2 bg-green-400 rounded-full"></div>
                  <span className="text-xs text-green-400 font-medium">
                    <T k="connected" fallback="Connected" />
                  </span>
                </div>
                <div className="bg-gray-800 rounded p-2">
                  <p className="text-xs text-gray-400 mb-1">{currentProvider?.toUpperCase()}</p>
                  <code className="text-xs text-white">
                    {walletAddress.substring(0, 6)}...{walletAddress.substring(walletAddress.length - 4)}
                  </code>
                </div>
                <Button
                  size="sm"
                  variant="outline"
                  onClick={disconnectWallet}
                  className="w-full text-xs py-1 h-7 border-red-500/30 text-red-400 hover:bg-red-500/20"
                >
                  <T k="disconnect" fallback="Disconnect" />
                </Button>
              </div>
            )}
          </div>
        )}

        {isCollapsed && (
          <div className="flex flex-col items-center space-y-2">
            {!walletAddress ? (
              <Button
                size="sm"
                onClick={() => connectWallet("safepal")}
                disabled={isConnecting}
                className="w-8 h-8 p-0 bg-blue-600 hover:bg-blue-700"
                title="Connect Wallet"
              >
                {isConnecting ? (
                  <div className="animate-spin rounded-full h-3 w-3 border-b-2 border-white"></div>
                ) : (
                  <Wallet className="h-4 w-4" />
                )}
              </Button>
            ) : (
              <div className="flex flex-col items-center space-y-1">
                <div className="w-8 h-8 bg-green-600 rounded flex items-center justify-center" title="Wallet Connected">
                  <Wallet className="h-4 w-4 text-white" />
                </div>
                <div className="w-2 h-2 bg-green-400 rounded-full"></div>
              </div>
            )}
          </div>
        )}
      </div>

      {/* Footer */}
      <div className="p-4 border-t border-gray-700">
        <Button
          onClick={onLogout}
          variant="ghost"
          className={`w-full justify-start text-red-400 hover:text-red-300 hover:bg-red-500/10 ${
            isCollapsed ? 'px-2' : 'px-3'
          }`}
          title={isCollapsed ? translate('logout', 'Logout') : undefined}
        >
          <div className="flex items-center space-x-3">
            <LogOut className="h-4 w-4" />
            {!isCollapsed && <span><T k="logout" fallback="Logout" /></span>}
          </div>
        </Button>
      </div>
    </div>
  );
};

export default UserSidebar;

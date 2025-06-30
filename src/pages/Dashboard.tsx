import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useUser } from '@/contexts/UserContext';
import { Loader2 } from '@/components/SafeIcons';
import UserSidebar from '@/components/dashboard/UserSidebar';
import CouponRedemption from '@/components/user/CouponRedemption';
import UserDashboard from '@/components/dashboard/UserDashboard';
import PackagesView from '@/components/dashboard/PackagesView';
import InvestmentHistory from '@/components/dashboard/InvestmentHistory';
import PortfolioView from '@/components/dashboard/PortfolioView';
import SupportView from '@/components/dashboard/SupportView';
import CommissionWallet from '@/components/dashboard/CommissionWallet';
import KYCVerification from '@/pages/KYCVerification';
import EnhancedKYCProfile from '@/components/kyc/EnhancedKYCProfile';
import KYCLevelsDashboard from '@/components/kyc/KYCLevelsDashboard';
import WalletDebugger from '@/components/debug/WalletDebugger';
import { useWalletConnection } from '@/pages/investment/useWalletConnection';
import AffiliateView from '@/components/dashboard/AffiliateView';
import EnhancedUserProfile from '@/components/profile/EnhancedUserProfile';
import GoldDiggersClub from '@/components/leaderboard/GoldDiggersClub';
import InvestmentCountdownList from '@/components/investment/InvestmentCountdownList';
import CertificatesView from '@/components/dashboard/CertificatesView';
import RealWorkingTranslator from '@/components/RealWorkingTranslator';
// Removed wallet imports to prevent Trust Wallet popup

const Dashboard: React.FC = () => {
  const { user, isAuthenticated, logout } = useUser();
  const { walletAddress, currentProvider } = useWalletConnection();
  const navigate = useNavigate();
  const [activeTab, setActiveTab] = useState('dashboard');

  // Wallet functionality removed from dashboard to prevent Trust Wallet popup
  // Wallet connection is now only available in the investment pages



  useEffect(() => {
    console.log('Dashboard authentication check:', { isAuthenticated, user: !!user });
    // Redirect to auth if not authenticated
    if (!isAuthenticated) {
      console.log('Dashboard redirecting to /auth - user not authenticated');
      navigate('/auth');
    }
  }, [isAuthenticated, navigate, user]);

  const handleLogout = () => {
    logout();
    navigate('/');
  };

  const handleTabChange = (tab: string) => {
    setActiveTab(tab);
  };

  const renderContent = () => {
    switch (activeTab) {
      case 'packages':
        return <PackagesView />;
      case 'history':
        return <InvestmentHistory />;
      case 'certificates':
        return <CertificatesView />;
      case 'countdown':
        return <InvestmentCountdownList />;
      case 'portfolio':
        return <PortfolioView />;
      case 'affiliate':
        return <AffiliateView />;
      case 'commissions':
        return <CommissionWallet />;
      case 'leaderboard':
        return <GoldDiggersClub />;
      case 'support':
        return <SupportView />;
      case 'profile':
        return <EnhancedUserProfile />;
      case 'coupons':
        return <CouponRedemption />;
      case 'kyc-profile':
        return <EnhancedKYCProfile />;
      case 'kyc':
        return <KYCVerification />;
      case 'kyc-levels':
        return <KYCLevelsDashboard />;
      default:
        return <UserDashboard onNavigate={handleTabChange} />;
    }
  };

  if (!isAuthenticated || !user) {
    return (
      <div className="min-h-screen bg-gray-900 flex items-center justify-center">
        <Loader2 className="h-8 w-8 animate-spin text-gold" />
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-900 flex">
      {/* Sidebar */}
      <UserSidebar
        activeTab={activeTab}
        onTabChange={handleTabChange}
        onLogout={handleLogout}
      />

      {/* Main Content */}
      <div className="flex-1 flex flex-col">
        {/* Header */}
        <div className="bg-gray-800 border-b border-gray-700 px-6 py-4">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-xl font-semibold text-white">
                {activeTab === 'dashboard' ? 'Dashboard' :
                 activeTab === 'packages' ? 'Investment Packages' :
                 activeTab === 'history' ? 'Investment History' :
                 activeTab === 'certificates' ? 'Share Certificates' :
                 activeTab === 'countdown' ? 'Delivery Countdown' :
                 activeTab === 'portfolio' ? 'Portfolio Overview' :
                 activeTab === 'affiliate' ? 'Affiliate Program' :
                 activeTab === 'commissions' ? 'Commission Wallet' :
                 activeTab === 'leaderboard' ? 'Gold Diggers Club' :
                 activeTab === 'support' ? 'Contact Support' :
                 activeTab === 'profile' ? 'My Profile' :
                 activeTab === 'kyc-profile' ? 'KYC Profile' :
                 activeTab === 'kyc' ? 'KYC Verification' :
                 activeTab === 'kyc-levels' ? 'KYC Levels' :
                 activeTab === 'wallet' ? 'Wallet Connection' : 'Dashboard'}
              </h1>
              <p className="text-sm text-gray-400">
                {activeTab === 'dashboard' ? 'Welcome back to your investment portal' :
                 activeTab === 'packages' ? 'Explore available investment opportunities' :
                 activeTab === 'history' ? 'Track your investment performance' :
                 activeTab === 'certificates' ? 'View and manage your share certificates' :
                 activeTab === 'countdown' ? 'Track your NFT and ROI delivery schedules' :
                 activeTab === 'portfolio' ? 'Monitor your portfolio growth' :
                 activeTab === 'affiliate' ? 'Grow your network and earn commissions' :
                 activeTab === 'commissions' ? 'Manage your referral earnings and withdrawals' :
                 activeTab === 'leaderboard' ? 'Compete for the $250K bonus pool' :
                 activeTab === 'support' ? 'Get help from our support team' :
                 activeTab === 'profile' ? 'Complete your profile and KYC verification' :
                 activeTab === 'kyc-profile' ? 'Complete your comprehensive KYC profile information' :
                 activeTab === 'kyc' ? 'Verify your identity to secure your account' :
                 activeTab === 'kyc-levels' ? 'Track your KYC progress and unlock platform benefits' :
                 activeTab === 'wallet' ? 'Connect and manage your wallets' : 'Investment portal management'}
              </p>
            </div>

            {/* User & Wallet Info */}
            <div className="flex items-center gap-4">
              {/* Language Selector */}
              <RealWorkingTranslator className="mr-2" />

              <div className="flex items-center gap-2 bg-gray-700 rounded-lg px-4 py-2">
                <div className="w-2 h-2 bg-green-400 rounded-full"></div>
                <span className="text-xs text-green-400 font-medium">
                  {user?.username || 'User'}
                </span>
              </div>

              {walletAddress && (
                <div className="flex items-center gap-2 bg-gray-700 rounded-lg px-4 py-2">
                  <div className="w-2 h-2 bg-blue-400 rounded-full"></div>
                  <span className="text-xs text-blue-400 font-medium">
                    {currentProvider?.toUpperCase()}
                  </span>
                  <code className="text-xs text-white">
                    {walletAddress.substring(0, 6)}...{walletAddress.substring(walletAddress.length - 4)}
                  </code>
                </div>
              )}
            </div>
          </div>
        </div>

        {/* Content Area */}
        <div className="flex-1 p-6 overflow-auto">
          {renderContent()}
        </div>
      </div>
    </div>
  );
};

export default Dashboard;

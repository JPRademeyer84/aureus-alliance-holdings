
import React, { useState, useEffect } from 'react';
import { useAdmin } from '@/contexts/AdminContext';
import AdminLogin from '@/components/admin/AdminLogin';
import PackageManager from '@/components/admin/PackageManager';
import WalletManager from '@/components/admin/WalletManager';
import ContactMessagesManager from '@/components/admin/ContactMessagesManager';
import LiveChatManager from '@/components/admin/LiveChatManager';
import AdminUserManager from '@/components/admin/AdminUserManager';
import OfflineMessagesManager from '@/components/admin/OfflineMessagesManager';
import UserManager from '@/components/admin/UserManager';
import AdminDashboard from '@/components/admin/AdminDashboard';
import AdminSidebar from '@/components/admin/AdminSidebar';
import NFTCouponsManager from '@/components/admin/NFTCouponsManager';
import DebugManager from '@/components/admin/DebugManager';
import TermsComplianceManager from '@/components/admin/TermsComplianceManager';
import KYCManagement from '@/components/admin/KYCManagement';
import EnhancedKYCManagement from '@/components/admin/EnhancedKYCManagement';
import CommissionPlansManager from '@/components/admin/CommissionPlansManager';
import CommissionManagement from '@/components/admin/CommissionManagement';
import SystemStatus from '@/components/admin/SystemStatus';
import SystemValidation from '@/components/admin/SystemValidation';
import UltimateSecurity from '@/components/admin/UltimateSecurity';
import ReviewsManager from '@/components/admin/ReviewsManager';
import AdminDebug from '@/components/admin/AdminDebug';
import TranslationManagement from '@/pages/admin/TranslationManagement';
import EnglishTranslationEditor from '@/pages/admin/EnglishTranslationEditor';
import ForcePasswordChange from '@/components/admin/ForcePasswordChange';
import CertificateManager from '@/components/admin/CertificateManager';
import CertificateTemplateManager from '@/components/admin/CertificateTemplateManager';
import BankPaymentManager from '@/components/admin/BankPaymentManager';
import InvestmentManager from '@/components/admin/InvestmentManager';
import PrizeManager from '@/components/leaderboard/PrizeManager';
import SocialSharingLeaderboard from '@/components/admin/SocialSharingLeaderboard';
import PerformanceMonitor from '@/components/admin/PerformanceMonitor';
import PhaseManager from '@/components/admin/PhaseManager';
import CompetitionManager from '@/components/competitions/CompetitionManager';
import NPOFundManager from '@/components/npo/NPOFundManager';
import ShareCertificateGenerator from '@/components/certificates/ShareCertificateGenerator';
import { AdminProvider } from '@/contexts/AdminContext';
import { Loader2 } from "@/components/SafeIcons";

const AdminRoutes = () => {
  const { admin, isLoading, hasPermission } = useAdmin();
  const [activeTab, setActiveTab] = React.useState('dashboard');
  const [sidebarCollapsed, setSidebarCollapsed] = React.useState(false);

  // Determine default tab based on permissions
  const getDefaultTab = () => {
    return 'dashboard';
  };
  
  React.useEffect(() => {
    if (admin && activeTab === 'dashboard') {
      setActiveTab(getDefaultTab());
    }
  }, [admin]);

  if (isLoading) {
    return (
      <div className="flex flex-col items-center justify-center min-h-screen gap-4 bg-gray-50">
        <Loader2 className="h-8 w-8 animate-spin text-blue-600" />
        <p className="text-gray-600">Loading admin panel...</p>
      </div>
    );
  }

  if (!admin) {
    return <AdminLogin />;
  }

  // Check if password change is required
  if (admin.password_change_required) {
    return <ForcePasswordChange />;
  }

  const renderContent = () => {
    switch (activeTab) {
      case 'dashboard':
        return <AdminDashboard onNavigate={setActiveTab} />;
      case 'packages':
        return hasPermission('admin') ? <PackageManager /> : <div>Access Denied</div>;
      case 'wallets':
        return hasPermission('admin') ? <WalletManager /> : <div>Access Denied</div>;
      case 'users':
        return hasPermission('admin') ? <UserManager /> : <div>Access Denied</div>;
      case 'commissions':
        return hasPermission('admin') ? <CommissionPlansManager /> : <div>Access Denied</div>;
      case 'coupons':
        return hasPermission('admin') ? <NFTCouponsManager /> : <div>Access Denied</div>;
      case 'debug':
        return hasPermission('admin') ? <DebugManager /> : <div>Access Denied</div>;
      case 'commission-management':
        return hasPermission('admin') ? <CommissionManagement /> : <div>Access Denied</div>;
      case 'system-status':
        return hasPermission('admin') ? <SystemStatus /> : <div>Access Denied</div>;
      case 'system-validation':
        return hasPermission('admin') ? <SystemValidation /> : <div>Access Denied</div>;
      case 'ultimate-security':
        return hasPermission('admin') ? <UltimateSecurity /> : <div>Access Denied</div>;
      case 'contact':
        return <ContactMessagesManager isActive={activeTab === 'contact'} />;
      case 'chat':
        return <LiveChatManager isActive={activeTab === 'chat'} />;
      case 'offline':
        return <OfflineMessagesManager isActive={activeTab === 'offline'} />;
      case 'reviews':
        return <ReviewsManager isActive={activeTab === 'reviews'} />;
      case 'admins':
        return hasPermission('admin') ? <AdminUserManager /> : <div>Access Denied</div>;
      case 'kyc':
        return hasPermission('admin') ? <KYCManagement /> : <div>Access Denied</div>;
      case 'enhanced-kyc':
        return hasPermission('admin') ? <EnhancedKYCManagement /> : <div>Access Denied</div>;
      case 'terms-compliance':
        return hasPermission('admin') ? <TermsComplianceManager /> : <div>Access Denied</div>;
      case 'translations':
        return hasPermission('admin') ? <TranslationManagement /> : <div>Access Denied</div>;
      case 'english-editor':
        return hasPermission('admin') ? <EnglishTranslationEditor /> : <div>Access Denied</div>;
      case 'certificates':
        return hasPermission('admin') ? <CertificateManager /> : <div>Access Denied</div>;
      case 'certificate-templates':
        return hasPermission('admin') ? <CertificateTemplateManager /> : <div>Access Denied</div>;
      case 'bank-payments':
        return hasPermission('admin') ? <BankPaymentManager /> : <div>Access Denied</div>;
      case 'investments':
        return hasPermission('admin') ? <InvestmentManager /> : <div>Access Denied</div>;
      case 'prize-manager':
        return hasPermission('admin') ? <PrizeManager /> : <div>Access Denied</div>;
      case 'social-leaderboard':
        return hasPermission('admin') ? <SocialSharingLeaderboard /> : <div>Access Denied</div>;
      case 'performance-monitor':
        return hasPermission('admin') ? <div className="p-6 text-center text-gray-400">Performance Monitor temporarily disabled to prevent fetch override conflicts</div> : <div>Access Denied</div>;
      case 'phases':
        return hasPermission('admin') ? <PhaseManager /> : <div>Access Denied</div>;
      case 'competitions':
        return hasPermission('admin') ? <CompetitionManager /> : <div>Access Denied</div>;
      case 'npo-fund':
        return hasPermission('admin') ? <NPOFundManager /> : <div>Access Denied</div>;
      case 'share-certificates':
        return hasPermission('admin') ? <ShareCertificateGenerator /> : <div>Access Denied</div>;
      case 'debug':
        return <AdminDebug />;
      default:
        return <AdminDashboard onNavigate={setActiveTab} />;
    }
  };

  return (
    <div className="flex h-screen bg-gray-900">
      {/* Sidebar */}
      <AdminSidebar
        activeTab={activeTab}
        onTabChange={setActiveTab}
        isCollapsed={sidebarCollapsed}
        onToggleCollapse={() => setSidebarCollapsed(!sidebarCollapsed)}
      />

      {/* Main Content */}
      <div className="flex-1 flex flex-col overflow-hidden">
        {/* Header */}
        <header className="bg-gray-800 border-b border-gray-700 px-6 py-4">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-2xl font-bold text-white">
                {activeTab === 'dashboard' ? 'Dashboard' :
                 activeTab === 'packages' ? 'Investment Packages' :
                 activeTab === 'wallets' ? 'Payment Wallets' :
                 activeTab === 'users' ? 'User Management' :
                 activeTab === 'contact' ? 'Contact Messages' :
                 activeTab === 'chat' ? 'Live Chat' :
                 activeTab === 'offline' ? 'Offline Messages' :
                 activeTab === 'reviews' ? 'Customer Reviews' :
                 activeTab === 'admins' ? 'Admin Users' :
                 activeTab === 'kyc' ? 'KYC Management' :
                 activeTab === 'enhanced-kyc' ? 'Enhanced KYC Management' :
                 activeTab === 'terms-compliance' ? 'Terms Compliance' :
                 activeTab === 'translations' ? 'Translation Management' :
                 activeTab === 'english-editor' ? 'English Translation Editor' : 'Admin Panel'}
              </h1>
              <p className="text-gray-400 text-sm mt-1">
                {activeTab === 'dashboard' ? 'Overview of your admin panel' :
                 activeTab === 'packages' ? 'Manage investment packages and offerings' :
                 activeTab === 'wallets' ? 'Configure payment wallet addresses' :
                 activeTab === 'users' ? 'Manage platform users and accounts' :
                 activeTab === 'commissions' ? 'Manage referral commission plans and structures' :
                 activeTab === 'contact' ? 'Review and respond to contact messages' :
                 activeTab === 'chat' ? 'Handle live customer support chats' :
                 activeTab === 'offline' ? 'Review offline chat messages' :
                 activeTab === 'reviews' ? 'View and manage customer feedback and ratings' :
                 activeTab === 'admins' ? 'Manage admin users and permissions' :
                 activeTab === 'kyc' ? 'Review and approve user KYC documents' :
                 activeTab === 'enhanced-kyc' ? 'Comprehensive KYC profile management with section-by-section approval' :
                 activeTab === 'terms-compliance' ? 'Monitor terms acceptance for regulatory compliance' :
                 activeTab === 'translations' ? 'Manage website translations and languages' :
                 activeTab === 'english-editor' ? 'Edit English translations and regenerate all language translations' : 'Admin panel management'}
              </p>
            </div>
            <div className="flex items-center gap-3">
              <div className="text-right">
                <p className="text-sm font-medium text-white">
                  {admin.full_name || admin.username}
                </p>
                <p className="text-xs text-gray-400">
                  {admin.role ? admin.role.replace('_', ' ').toUpperCase() : 'ADMIN'}
                </p>
              </div>
            </div>
          </div>
        </header>

        {/* Content Area */}
        <main className="flex-1 overflow-auto p-6 bg-gray-900">
          {renderContent()}
        </main>
      </div>
    </div>
  );
};

const Admin = () => (
  <AdminProvider>
    <AdminRoutes />
  </AdminProvider>
);

export default Admin;

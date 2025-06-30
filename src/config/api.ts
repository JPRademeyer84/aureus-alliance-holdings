// API configuration with environment detection
class ApiConfig {
  private static getBaseUrl(): string {
    // In development, point directly to XAMPP
    // In production, use the actual domain
    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
      // Check if we're running on Vite dev server (port 5173/5174) or direct XAMPP access
      if (window.location.port === '5173' || window.location.port === '5174' || window.location.port === '3000') {
        // Development mode: point to XAMPP server on custom port 3506
        return 'http://localhost:3506/Aureus%201%20-%20Complex/api';
      } else {
        // Direct XAMPP access
        return `${window.location.origin}/api`;
      }
    }
    // In production: use the actual domain
    return `${window.location.origin}/api`;
  }

  public static get baseUrl(): string {
    return this.getBaseUrl();
  }

  public static get endpoints() {
    const base = this.baseUrl;
    return {
      admin: {
        auth: `${base}/admin/auth.php`,
        wallets: `${base}/admin/wallets.php`,
        manageAdmins: `${base}/admin/manage-admins.php`,
        manageUsers: `${base}/admin/manage-users.php`,
        dashboardStats: `${base}/admin/dashboard-stats.php`,
        clearSessions: `${base}/admin/clear-sessions.php`,
        reviews: `${base}/admin/reviews.php`,
        commissionPlans: `${base}/admin/commission-plans.php`,
        kycManagement: `${base}/admin/kyc-management.php`,
        kycLevels: `${base}/admin/kyc-levels.php`,
        securityMonitoring: `${base}/admin/security-monitoring.php`,
        certificateTemplates: `${base}/admin/certificate-templates.php`,
        certificateGenerator: `${base}/admin/certificate-generator.php`,
        generateCertificate: `${base}/admin/generate-certificate.php`,
        certificateTemplateUpload: `${base}/admin/certificate-template-upload.php`
      },
      users: {
        auth: `${base}/users/auth.php`,
        profile: `${base}/users/profile.php`,
        updateProfile: `${base}/users/update-profile.php`,
        enhancedProfile: `${base}/users/enhanced-profile.php`,
        enhancedKycProfile: `${base}/users/test-kyc-profile.php`,
        investments: `${base}/users/investments.php`,
        validateUsername: `${base}/users/validate-username.php`
      },
      packages: {
        index: `${base}/packages/index.php`
      },
      investments: {
        process: `${base}/investments/process.php`,
        create: `${base}/investments/create.php`,
        update: `${base}/investments/update.php`,
        history: `${base}/investments/history.php`,
        get: `${base}/investments/get.php`,
        list: `${base}/investments/list.php`,
        countdown: `${base}/investments/countdown.php`,
        termsAcceptance: `${base}/investments/terms-acceptance.php`
      },
      wallets: {
        index: `${base}/wallets/index.php`,
        active: `${base}/wallets/active.php`
      },
      contact: {
        messages: `${base}/contact/messages.php`
      },
      chat: {
        sessions: `${base}/chat/sessions.php`,
        messages: `${base}/chat/messages.php`,
        offlineMessages: `${base}/chat/offline-messages.php`,
        agentStatus: `${base}/chat/agent-status.php`
      },
      referrals: {
        track: `${base}/referrals/track-visit.php`,
        stats: `${base}/referrals/user-stats.php`,
        history: `${base}/referrals/user-history.php`,
        leaderboard: `${base}/referrals/leaderboard.php`,
        goldDiggers: `${base}/referrals/gold-diggers-leaderboard.php`,
        payout: `${base}/referrals/payout.php`,
        commissionBalance: `${base}/referrals/commission-balance.php`
      },
      commissions: {
        process: `${base}/commissions/process.php`,
        transactions: `${base}/commissions/transactions.php`,
        payouts: `${base}/commissions/payouts.php`
      },
      notifications: {
        referral: `${base}/notifications/referral.php`,
        commission: `${base}/notifications/commission.php`
      },
      coupons: {
        index: `${base}/coupons/index.php`
      },
      kyc: {
        upload: `${base}/kyc/upload.php`,
        documents: `${base}/kyc/documents.php`,
        facialVerification: `${base}/kyc/facial-verification.php`,
        levels: `${base}/kyc/test-levels.php`
      },
      certificates: {
        verify: `${base}/certificates/verify.php`,
        userCertificates: `${base}/users/certificates.php`
      },
      payments: {
        countryDetection: `${base}/payments/country-detection.php`,
        bankTransfer: `${base}/payments/bank-transfer.php`,
        manualVerification: `${base}/payments/manual-verification.php`,
        manualPayment: `${base}/payments/manual-payment.php`
      },
      admin: {
        investments: `${base}/admin/investments.php`
      },
      leaderboard: {
        goldDiggers: `${base}/leaderboard/gold-diggers-club.php`,
        prizeDistribution: `${base}/leaderboard/prize-distribution.php`
      },
      social: {
        shareTracking: `${base}/social/share-tracking.php`,
        platformIntegration: `${base}/social/platform-integration.php`
      },
      notifications: {
        email: `${base}/notifications/email-service.php`,
        referral: `${base}/notifications/referral-notification.php`
      }
    };
  }

  public static isProduction(): boolean {
    return window.location.hostname !== 'localhost' &&
           window.location.hostname !== '127.0.0.1';
  }

  public static isExternal(): boolean {
    // Check if we're accessing via tunnel (ngrok, cloudflare, etc.)
    return window.location.hostname.includes('.ngrok.io') ||
           window.location.hostname.includes('.trycloudflare.com') ||
           window.location.hostname.includes('.loca.lt') ||
           (!window.location.hostname.includes('localhost') &&
            !window.location.hostname.includes('127.0.0.1'));
  }
}

export default ApiConfig;

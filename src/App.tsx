
import React from 'react';
import { Toaster } from "@/components/ui/toaster";
import { Toaster as Sonner } from "@/components/ui/sonner";
import { TooltipProvider } from "@/components/ui/tooltip";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { BrowserRouter, Routes, Route, useLocation } from "react-router-dom";
import { UserProvider } from "@/contexts/UserContext";
import { AdminProvider } from "@/contexts/AdminContext";
import { SimpleTranslationProvider } from "@/components/SimpleTranslator";
import { DebugProvider } from "@/contexts/DebugContext";
import { useWalletProtection } from "@/hooks/useWalletProtection";
import { useMobileOptimization } from "@/hooks/useMobileOptimization";
// DISABLED to prevent fetch override errors
// import { usePerformanceOptimization } from "@/hooks/usePerformanceOptimization";
import { ResponsiveLayout } from "@/components/responsive/ResponsiveLayout";
import ErrorBoundary from "@/components/ErrorBoundary";
import SafeDebugPanel from "@/components/debug/SafeDebugPanel";
// Temporarily disabled problematic debug components
// import SimpleDebugConsole from "@/components/debug/SimpleDebugConsole";
// import DebugButton from "@/components/debug/DebugButton";
// import DebugTestButton from "@/components/debug/DebugTestButton";
// import ProfileTestButton from "@/components/debug/ProfileTestButton";
// import AdminAuthTestButton from "@/components/debug/AdminAuthTestButton";
// import KycTestButton from "@/components/debug/KycTestButton";
import LiveChat from "@/components/chat/LiveChat";
import Index from "./pages/Index";
import Participation from "./pages/Investment";
import Admin from "./pages/Admin";
import Auth from "./pages/Auth";
import Dashboard from "./pages/Dashboard";
import Affiliate from "./pages/Affiliate";
import ChatTest from "./pages/ChatTest";
import TermsAndConditions from "./pages/TermsAndConditions";
import TranslationTest from "./pages/TranslationTest";
import WalletErrorDemo from "./components/wallet/WalletErrorDemo";
import KYCVerification from "./pages/KYCVerification";
import EnhancedKYCProfile from "./components/kyc/EnhancedKYCProfile";
import CertificateVerification from "./pages/CertificateVerification";
import NotFound from "./pages/NotFound";
import TestComponent from "./components/TestComponent";
import DebugTest from "./pages/DebugTest";

// Component to conditionally render LiveChat based on route
const ConditionalLiveChat = () => {
  const location = useLocation();

  // Don't show LiveChat on admin pages
  if (location.pathname.startsWith('/admin')) {
    return null;
  }

  return <LiveChat />;
};

const App = () => {
  // Create a new QueryClient instance inside the component
  const queryClient = new QueryClient();

  // Initialize wallet protection (debug disabled to prevent console spam)
  useWalletProtection({
    debug: false
  });

  // Initialize mobile optimization
  const { isMobile, isTablet, deviceInfo } = useMobileOptimization();

  // DISABLED to prevent fetch override errors
  // const { runOptimizations, isOptimizing } = usePerformanceOptimization();

  return (
    <ErrorBoundary>
      <QueryClientProvider client={queryClient}>
        <UserProvider>
          <AdminProvider>
            <SimpleTranslationProvider>
              <DebugProvider>
              <TooltipProvider>
              <Toaster />
              <Sonner />
              <BrowserRouter>
                <ResponsiveLayout
                  enableSafeArea={isMobile || isTablet}
                  enableKeyboardAdjustment={isMobile}
                  className="min-h-screen"
                >
                  <Routes>
              <Route path="/" element={<Index />} />
              <Route path="/participation" element={<Participation />} />
              <Route path="/participate" element={<Participation />} />
              {/* Legacy routes for backward compatibility */}
              <Route path="/investment" element={<Participation />} />
              <Route path="/invest" element={<Participation />} />
              <Route path="/auth" element={<Auth />} />
              <Route path="/login" element={<Auth />} />
              <Route path="/register" element={<Auth />} />
              {/* KYC routes - must come BEFORE dashboard route to avoid conflicts */}
              <Route path="/kyc" element={<KYCVerification />} />
              <Route path="/dashboard/kyc-verification" element={<KYCVerification />} />
              <Route path="/dashboard/kyc-profile" element={<EnhancedKYCProfile />} />
              {/* Dashboard route - must come AFTER specific dashboard sub-routes */}
              <Route path="/dashboard" element={<Dashboard />} />
              <Route path="/affiliate" element={<Affiliate />} />
              <Route path="/admin" element={<Admin />} />
              <Route path="/chat-test" element={<ChatTest />} />
              <Route path="/translation-test" element={<TranslationTest />} />
              <Route path="/wallet-error-demo" element={<WalletErrorDemo />} />
              <Route path="/terms-and-conditions" element={<TermsAndConditions />} />
              {/* Test route to verify site is working */}
              <Route path="/test" element={<TestComponent />} />
              {/* Certificate verification routes */}
              <Route path="/verify" element={<CertificateVerification />} />
              <Route path="/verify/:verificationCode" element={<CertificateVerification />} />
              {/* Debug test route */}
              <Route path="/debug-test" element={<DebugTest />} />
              {/* Referral routes - username-based referrals */}
              <Route path="/:username" element={<Participation />} />
              {/* ADD ALL CUSTOM ROUTES ABOVE THE CATCH-ALL "*" ROUTE */}
              <Route path="*" element={<NotFound />} />
            </Routes>
            {/* Safe Debug Panel - DISABLED to prevent fetch override errors */}
            {/* <SafeDebugPanel /> */}

            {/* Original debug components - temporarily disabled to prevent circular reference errors */}
            {/* <SimpleDebugConsole /> */}
            {/* <DebugButton position="bottom-right" /> */}
            {/* <DebugTestButton /> */}
            {/* <ProfileTestButton /> */}
            {/* <AdminAuthTestButton /> */}
            {/* <KycTestButton /> */}
            <ConditionalLiveChat />
                </ResponsiveLayout>
              </BrowserRouter>
              </TooltipProvider>
              </DebugProvider>
            </SimpleTranslationProvider>
          </AdminProvider>
        </UserProvider>
      </QueryClientProvider>
    </ErrorBoundary>
  );
};

export default App;

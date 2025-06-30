import React, { useState, useEffect } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { useUser } from '@/contexts/UserContext';
import UserLogin from '@/components/auth/UserLogin';
import UserRegister from '@/components/auth/UserRegister';
import Navbar from '@/components/Navbar';
import Footer from '@/components/Footer';
import { useSimpleTranslation as useTranslation } from '@/components/SimpleTranslator';

const Auth: React.FC = () => {
  const [isLogin, setIsLogin] = useState(true);
  const { isAuthenticated } = useUser();
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const { translate: t } = useTranslation();

  // Get the package ID from URL params if user was trying to invest
  const packageId = searchParams.get('package');
  const returnTo = searchParams.get('returnTo') || '/dashboard';

  useEffect(() => {
    // If user is already authenticated, redirect to dashboard or intended page
    if (isAuthenticated) {
      navigate(returnTo);
    }
  }, [isAuthenticated, navigate, returnTo]);

  const handleAuthSuccess = () => {
    // Redirect to dashboard or the page they were trying to access
    if (packageId) {
      navigate(`/dashboard?package=${packageId}`);
    } else {
      navigate(returnTo);
    }
  };

  return (
    <div className="min-h-screen bg-charcoal">
      <Navbar />
      <div className="container mx-auto px-4 py-12">
        <div className="max-w-md mx-auto">
          <div className="text-center mb-8">
            <h1 className="text-3xl md:text-4xl font-bold font-playfair mb-4">
              <span className="text-gradient">
                {isLogin ? t('auth.welcome_back') : t('auth.join_us')}
              </span>
            </h1>
            <p className="text-white/70">
              {isLogin
                ? t('auth.sign_in_dashboard')
                : t('auth.create_account_investing')
              }
            </p>
          </div>

          {isLogin ? (
            <UserLogin
              onSwitchToRegister={() => setIsLogin(false)}
              onSuccess={handleAuthSuccess}
            />
          ) : (
            <UserRegister
              onSwitchToLogin={() => setIsLogin(true)}
              onSuccess={handleAuthSuccess}
            />
          )}

          {packageId && (
            <div className="mt-6 p-4 bg-gold/10 border border-gold/30 rounded-lg text-center">
              <p className="text-white/80 text-sm">
                🎯 You'll be redirected to complete your investment after signing in
              </p>
            </div>
          )}
        </div>
      </div>
      <Footer />
    </div>
  );
};

export default Auth;

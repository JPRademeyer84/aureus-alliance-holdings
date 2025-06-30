import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input, PasswordInput } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Loader2 } from '@/components/SafeIcons';

// Safe alert icon
const AlertCircle = ({ className }: { className?: string }) => <span className={className}>⚠️</span>;
import { useUser } from '@/contexts/UserContext';
import { useSimpleTranslation as useTranslation } from '@/components/SimpleTranslator';

interface UserLoginProps {
  onSwitchToRegister: () => void;
  onSuccess?: () => void;
}

const UserLogin: React.FC<UserLoginProps> = ({ onSwitchToRegister, onSuccess }) => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const { login, isLoading, loginError } = useUser();
  const { translate: t } = useTranslation();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    const success = await login(email, password);
    if (success && onSuccess) {
      onSuccess();
    }
  };

  return (
    <Card className="w-full max-w-md mx-auto bg-[#23243a] border-gold/30">
      <CardHeader className="text-center">
        <CardTitle className="text-2xl font-playfair text-gold">
          {t('auth.welcome_back')}
        </CardTitle>
        <p className="text-white/70">{t('auth.sign_in_account')}</p>
      </CardHeader>
      
      <form onSubmit={handleSubmit}>
        <CardContent className="space-y-4">
          {loginError && (
            <Alert variant="destructive" className="mb-4">
              <AlertCircle className="h-4 w-4" />
              <AlertDescription>
                {loginError}
              </AlertDescription>
            </Alert>
          )}
          
          <div className="space-y-2">
            <label htmlFor="email" className="text-sm font-medium text-white">
              {t('auth.email')}
            </label>
            <Input
              id="email"
              type="email"
              placeholder={t('auth.email_placeholder')}
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
              className="bg-black/50 border-gold/30 text-white"
            />
          </div>

          <div className="space-y-2">
            <label htmlFor="password" className="text-sm font-medium text-white">
              {t('auth.password')}
            </label>
            <PasswordInput
              id="password"
              placeholder={t('auth.password_placeholder')}
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
              className="bg-black/50 border-gold/30 text-white"
            />
          </div>
          
          <Button
            type="submit"
            className="w-full bg-gold-gradient text-black font-semibold"
            disabled={isLoading}
          >
            {isLoading ? (
              <>
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                {t('auth.signing_in')}
              </>
            ) : (
              t('auth.sign_in')
            )}
          </Button>
          
          <div className="text-center pt-4">
            <p className="text-white/70 text-sm">
              {t('auth.dont_have_account')}{' '}
              <button
                type="button"
                onClick={onSwitchToRegister}
                className="text-gold hover:text-gold/80 font-medium"
              >
                {t('auth.sign_up')}
              </button>
            </p>
          </div>
        </CardContent>
      </form>
    </Card>
  );
};

export default UserLogin;

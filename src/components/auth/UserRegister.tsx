import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input, PasswordInput } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { AlertCircle, Loader2 } from 'lucide-react';
import { useUser } from '@/contexts/UserContext';
import { useSimpleTranslation as useTranslation } from '@/components/SimpleTranslator';

interface UserRegisterProps {
  onSwitchToLogin: () => void;
  onSuccess?: () => void;
}

const UserRegister: React.FC<UserRegisterProps> = ({ onSwitchToLogin, onSuccess }) => {
  const [username, setUsername] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [passwordError, setPasswordError] = useState('');
  const { register, isLoading, registerError } = useUser();
  const { translate: t } = useTranslation();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    // Validate passwords match
    if (password !== confirmPassword) {
      setPasswordError(t('auth.passwords_no_match'));
      return;
    }

    if (password.length < 6) {
      setPasswordError(t('auth.password_min_length'));
      return;
    }
    
    setPasswordError('');
    
    const success = await register(username, email, password);
    if (success && onSuccess) {
      onSuccess();
    }
  };

  return (
    <Card className="w-full max-w-md mx-auto bg-[#23243a] border-gold/30">
      <CardHeader className="text-center">
        <CardTitle className="text-2xl font-playfair text-gold">
          {t('auth.join_alliance')}
        </CardTitle>
        <p className="text-white/70">{t('auth.create_investment_account')}</p>
      </CardHeader>
      
      <form onSubmit={handleSubmit}>
        <CardContent className="space-y-4">
          {(registerError || passwordError) && (
            <Alert variant="destructive" className="mb-4">
              <AlertCircle className="h-4 w-4" />
              <AlertDescription>
                {registerError || passwordError}
              </AlertDescription>
            </Alert>
          )}
          
          <div className="space-y-2">
            <label htmlFor="username" className="text-sm font-medium text-white">
              {t('auth.username')}
            </label>
            <Input
              id="username"
              placeholder={t('auth.username_placeholder')}
              value={username}
              onChange={(e) => setUsername(e.target.value)}
              required
              minLength={3}
              className="bg-black/50 border-gold/30 text-white"
            />
          </div>

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
              placeholder={t('auth.create_password_placeholder')}
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
              minLength={6}
              className="bg-black/50 border-gold/30 text-white"
            />
          </div>

          <div className="space-y-2">
            <label htmlFor="confirmPassword" className="text-sm font-medium text-white">
              {t('auth.confirm_password')}
            </label>
            <PasswordInput
              id="confirmPassword"
              placeholder={t('auth.confirm_password_placeholder')}
              value={confirmPassword}
              onChange={(e) => setConfirmPassword(e.target.value)}
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
                {t('auth.creating_account')}
              </>
            ) : (
              t('auth.create_account')
            )}
          </Button>
          
          <div className="text-center pt-4">
            <p className="text-white/70 text-sm">
              {t('auth.already_have_account')}{' '}
              <button
                type="button"
                onClick={onSwitchToLogin}
                className="text-gold hover:text-gold/80 font-medium"
              >
                {t('auth.sign_in_link')}
              </button>
            </p>
          </div>
        </CardContent>
      </form>
    </Card>
  );
};

export default UserRegister;

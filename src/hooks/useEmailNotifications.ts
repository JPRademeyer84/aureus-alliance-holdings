import { useState, useCallback } from 'react';
import { useToast } from '@/hooks/use-toast';
import ApiConfig from '@/config/api';

interface EmailData {
  [key: string]: any;
}

interface EmailOptions {
  priority?: 'low' | 'normal' | 'high';
  scheduled_at?: string;
}

export const useEmailNotifications = () => {
  const { toast } = useToast();
  const [isSending, setIsSending] = useState(false);
  const [emailStatus, setEmailStatus] = useState<any>(null);

  const sendEmail = useCallback(async (
    type: string,
    recipient: string,
    data: EmailData,
    options: EmailOptions = {}
  ) => {
    setIsSending(true);
    
    try {
      const response = await fetch(`${ApiConfig.baseUrl}/notifications/email-service.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          type,
          recipient,
          data,
          ...options
        }),
      });

      const result = await response.json();
      
      if (result.success) {
        toast({
          title: "Email Sent",
          description: "Notification email has been sent successfully",
        });
        return { success: true, messageId: result.message_id };
      } else {
        throw new Error(result.error || 'Failed to send email');
      }
    } catch (error) {
      console.error('Email sending failed:', error);
      toast({
        title: "Email Failed",
        description: error instanceof Error ? error.message : "Failed to send email",
        variant: "destructive"
      });
      return { success: false, error: error instanceof Error ? error.message : 'Unknown error' };
    } finally {
      setIsSending(false);
    }
  }, [toast]);

  const sendInvestmentConfirmation = useCallback(async (
    recipient: string,
    investmentData: {
      username: string;
      package_name: string;
      amount: number;
      shares: number;
      investment_date: string;
      nft_delivery_date: string;
      roi_delivery_date: string;
    }
  ) => {
    return sendEmail('investment_confirmation', recipient, investmentData, { priority: 'high' });
  }, [sendEmail]);

  const sendKYCStatusUpdate = useCallback(async (
    recipient: string,
    kycData: {
      username: string;
      status: 'approved' | 'rejected' | 'pending' | 'incomplete';
      verification_level: string;
      rejection_reason?: string;
    }
  ) => {
    return sendEmail('kyc_status_update', recipient, kycData, { priority: 'high' });
  }, [sendEmail]);

  const sendPasswordReset = useCallback(async (
    recipient: string,
    resetData: {
      username: string;
      reset_link: string;
      expiry_time: string;
      ip_address: string;
      request_time: string;
    }
  ) => {
    return sendEmail('password_reset', recipient, resetData, { priority: 'high' });
  }, [sendEmail]);

  const sendCommissionNotification = useCallback(async (
    recipient: string,
    commissionData: {
      username: string;
      commission_amount: number;
      nft_bonus: number;
      referred_username: string;
      commission_level: number;
      total_earnings: number;
    }
  ) => {
    return sendEmail('commission_notification', recipient, commissionData, { priority: 'normal' });
  }, [sendEmail]);

  const sendWelcomeEmail = useCallback(async (
    recipient: string,
    welcomeData: {
      username: string;
      referral_link: string;
    }
  ) => {
    return sendEmail('welcome', recipient, welcomeData, { priority: 'normal' });
  }, [sendEmail]);

  const sendCertificateReady = useCallback(async (
    recipient: string,
    certificateData: {
      username: string;
      certificate_number: string;
      share_quantity: number;
      download_link: string;
    }
  ) => {
    return sendEmail('certificate_ready', recipient, certificateData, { priority: 'normal' });
  }, [sendEmail]);

  const getEmailStatus = useCallback(async () => {
    try {
      const response = await fetch(`${ApiConfig.baseUrl}/notifications/email-service.php?action=status`);
      const result = await response.json();
      
      if (result.success) {
        setEmailStatus(result.status);
        return result.status;
      } else {
        throw new Error(result.error || 'Failed to get email status');
      }
    } catch (error) {
      console.error('Failed to get email status:', error);
      return null;
    }
  }, []);

  // Batch email sending for multiple recipients
  const sendBatchEmails = useCallback(async (
    type: string,
    recipients: Array<{ email: string; data: EmailData }>,
    options: EmailOptions = {}
  ) => {
    const results = [];
    
    for (const recipient of recipients) {
      const result = await sendEmail(type, recipient.email, recipient.data, options);
      results.push({
        email: recipient.email,
        ...result
      });
      
      // Add small delay between emails to avoid overwhelming the server
      await new Promise(resolve => setTimeout(resolve, 100));
    }
    
    const successful = results.filter(r => r.success).length;
    const failed = results.filter(r => !r.success).length;
    
    toast({
      title: "Batch Email Complete",
      description: `${successful} emails sent successfully, ${failed} failed`,
      variant: failed > 0 ? "destructive" : "default"
    });
    
    return results;
  }, [sendEmail, toast]);

  // Email template validation
  const validateEmailData = useCallback((type: string, data: EmailData): boolean => {
    const requiredFields: Record<string, string[]> = {
      investment_confirmation: ['username', 'package_name', 'amount', 'shares'],
      kyc_status_update: ['username', 'status', 'verification_level'],
      password_reset: ['username', 'reset_link', 'expiry_time'],
      commission_notification: ['username', 'commission_amount', 'referred_username'],
      welcome: ['username', 'referral_link'],
      certificate_ready: ['username', 'certificate_number', 'share_quantity']
    };

    const required = requiredFields[type];
    if (!required) {
      console.warn(`Unknown email type: ${type}`);
      return false;
    }

    for (const field of required) {
      if (!data[field]) {
        console.error(`Missing required field for ${type}: ${field}`);
        return false;
      }
    }

    return true;
  }, []);

  // Safe email sending with validation
  const sendValidatedEmail = useCallback(async (
    type: string,
    recipient: string,
    data: EmailData,
    options: EmailOptions = {}
  ) => {
    if (!validateEmailData(type, data)) {
      toast({
        title: "Email Validation Failed",
        description: "Required email data is missing",
        variant: "destructive"
      });
      return { success: false, error: 'Validation failed' };
    }

    return sendEmail(type, recipient, data, options);
  }, [sendEmail, validateEmailData, toast]);

  return {
    // Core functions
    sendEmail: sendValidatedEmail,
    sendBatchEmails,
    getEmailStatus,
    
    // Specific email types
    sendInvestmentConfirmation,
    sendKYCStatusUpdate,
    sendPasswordReset,
    sendCommissionNotification,
    sendWelcomeEmail,
    sendCertificateReady,
    
    // Utilities
    validateEmailData,
    
    // State
    isSending,
    emailStatus,
    
    // Computed values
    isEmailConfigured: emailStatus?.smtp_configured || false,
    queueCount: emailStatus?.queue_count || 0,
    lastEmailSent: emailStatus?.last_sent || null
  };
};

export default useEmailNotifications;

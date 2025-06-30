import React, { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { useToast } from '@/hooks/use-toast';
import { useUser } from '@/contexts/UserContext';
import { Loader2, Send, MessageSquare } from 'lucide-react';
import ApiConfig from '@/config/api';

interface ContactFormProps {
  onMessageSent?: () => void;
}

const ContactForm: React.FC<ContactFormProps> = ({ onMessageSent }) => {
  const [subject, setSubject] = useState('');
  const [message, setMessage] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const { user } = useUser();
  const { toast } = useToast();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!user) {
      toast({
        title: 'Authentication Required',
        description: 'Please log in to send a message.',
        variant: 'destructive',
      });
      return;
    }

    if (!subject.trim() || !message.trim()) {
      toast({
        title: 'Missing Information',
        description: 'Please fill in both subject and message.',
        variant: 'destructive',
      });
      return;
    }

    setIsSubmitting(true);

    try {
      const response = await fetch(ApiConfig.endpoints.contact.messages, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          action: 'submit',
          user_id: user.id,
          subject: subject.trim(),
          message: message.trim(),
        }),
      });

      const data = await response.json();

      if (data.success) {
        toast({
          title: 'Message Sent',
          description: 'Your message has been sent successfully. We will get back to you soon.',
        });
        
        // Clear form
        setSubject('');
        setMessage('');
        
        // Notify parent component
        if (onMessageSent) {
          onMessageSent();
        }
      } else {
        throw new Error(data.error || 'Failed to send message');
      }
    } catch (error) {
      console.error('Contact form error:', error);
      toast({
        title: 'Error',
        description: error instanceof Error ? error.message : 'Failed to send message. Please try again.',
        variant: 'destructive',
      });
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <Card className="bg-[#23243a] border-gold/30">
      <CardHeader>
        <CardTitle className="text-white flex items-center">
          <MessageSquare className="h-5 w-5 mr-2 text-gold" />
          Contact Support
        </CardTitle>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="subject" className="text-white">
              Subject
            </Label>
            <Input
              id="subject"
              type="text"
              value={subject}
              onChange={(e) => setSubject(e.target.value)}
              placeholder="Enter the subject of your message"
              className="bg-charcoal border-gold/30 text-white placeholder:text-white/50"
              maxLength={255}
              disabled={isSubmitting}
            />
          </div>
          
          <div className="space-y-2">
            <Label htmlFor="message" className="text-white">
              Message
            </Label>
            <Textarea
              id="message"
              value={message}
              onChange={(e) => setMessage(e.target.value)}
              placeholder="Describe your question or issue in detail..."
              className="bg-charcoal border-gold/30 text-white placeholder:text-white/50 min-h-[120px]"
              maxLength={5000}
              disabled={isSubmitting}
            />
            <div className="text-xs text-white/50 text-right">
              {message.length}/5000 characters
            </div>
          </div>
          
          <Button
            type="submit"
            disabled={isSubmitting || !subject.trim() || !message.trim()}
            className="w-full bg-gold-gradient text-black font-semibold hover:opacity-90 transition-opacity"
          >
            {isSubmitting ? (
              <>
                <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                Sending...
              </>
            ) : (
              <>
                <Send className="h-4 w-4 mr-2" />
                Send Message
              </>
            )}
          </Button>
        </form>
      </CardContent>
    </Card>
  );
};

export default ContactForm;

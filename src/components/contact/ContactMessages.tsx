import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { useUser } from '@/contexts/UserContext';
import { useToast } from '@/hooks/use-toast';
import { Loader2, MessageSquare, Clock, CheckCircle, Reply, RefreshCw } from 'lucide-react';
import ApiConfig from '@/config/api';

interface ContactMessage {
  id: string;
  subject: string;
  message: string;
  status: 'unread' | 'read' | 'replied';
  admin_reply?: string;
  created_at: string;
  updated_at: string;
}

interface ContactMessagesProps {
  refreshTrigger?: number;
}

const ContactMessages: React.FC<ContactMessagesProps> = ({ refreshTrigger }) => {
  const [messages, setMessages] = useState<ContactMessage[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const { user } = useUser();
  const { toast } = useToast();

  const fetchMessages = async (showRefreshIndicator = false) => {
    if (!user) return;

    if (showRefreshIndicator) {
      setIsRefreshing(true);
    } else {
      setIsLoading(true);
    }

    try {
      const response = await fetch(
        `${ApiConfig.endpoints.contact.messages}?user_id=${user.id}&limit=20`,
        {
          method: 'GET',
          headers: {
            'Content-Type': 'application/json',
          },
        }
      );

      const data = await response.json();

      if (data.success) {
        setMessages(data.data.messages);
      } else {
        throw new Error(data.error || 'Failed to fetch messages');
      }
    } catch (error) {
      console.error('Fetch messages error:', {
        error: error,
        message: error instanceof Error ? error.message : 'Unknown error',
        stack: error instanceof Error ? error.stack : undefined,
        userId: user?.id,
        endpoint: `${ApiConfig.endpoints.contact.messages}?user_id=${user?.id}&limit=20`
      });
      toast({
        title: 'Error',
        description: 'Failed to load messages. Please try again.',
        variant: 'destructive',
      });
    } finally {
      setIsLoading(false);
      setIsRefreshing(false);
    }
  };

  useEffect(() => {
    // Only fetch messages if user is properly loaded
    if (user?.id) {
      fetchMessages();
    }
  }, [user?.id, refreshTrigger]);

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'unread':
        return <Clock className="h-4 w-4 text-yellow-500" />;
      case 'read':
        return <CheckCircle className="h-4 w-4 text-blue-500" />;
      case 'replied':
        return <Reply className="h-4 w-4 text-green-500" />;
      default:
        return <MessageSquare className="h-4 w-4 text-gray-500" />;
    }
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'unread':
        return <Badge variant="secondary" className="bg-yellow-500/10 text-yellow-500 border-yellow-500/30">Unread</Badge>;
      case 'read':
        return <Badge variant="secondary" className="bg-blue-500/10 text-blue-500 border-blue-500/30">Read</Badge>;
      case 'replied':
        return <Badge variant="secondary" className="bg-green-500/10 text-green-500 border-green-500/30">Replied</Badge>;
      default:
        return <Badge variant="secondary">Unknown</Badge>;
    }
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString();
  };

  if (isLoading) {
    return (
      <Card className="bg-[#23243a] border-gold/30">
        <CardContent className="flex items-center justify-center py-8">
          <Loader2 className="h-6 w-6 animate-spin text-gold" />
          <span className="ml-2 text-white">Loading messages...</span>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card className="bg-[#23243a] border-gold/30">
      <CardHeader>
        <div className="flex items-center justify-between">
          <CardTitle className="text-white flex items-center">
            <MessageSquare className="h-5 w-5 mr-2 text-gold" />
            Your Messages ({messages.length})
          </CardTitle>
          <Button
            onClick={() => fetchMessages(true)}
            disabled={isRefreshing}
            variant="outline"
            size="sm"
            className="border-gold/30 text-white hover:bg-gold/10"
          >
            {isRefreshing ? (
              <Loader2 className="h-4 w-4 animate-spin" />
            ) : (
              <RefreshCw className="h-4 w-4" />
            )}
          </Button>
        </div>
      </CardHeader>
      <CardContent>
        {messages.length === 0 ? (
          <div className="text-center py-8 text-white/70">
            <MessageSquare className="h-12 w-12 mx-auto mb-4 text-white/30" />
            <p>No messages yet.</p>
            <p className="text-sm">Send a message using the contact form above.</p>
          </div>
        ) : (
          <div className="space-y-4">
            {messages.map((message) => (
              <div
                key={message.id}
                className="border border-gold/20 rounded-lg p-4 bg-charcoal/50"
              >
                <div className="flex items-start justify-between mb-3">
                  <div className="flex items-center space-x-2">
                    {getStatusIcon(message.status)}
                    <h3 className="font-semibold text-white">{message.subject}</h3>
                  </div>
                  <div className="flex items-center space-x-2">
                    {getStatusBadge(message.status)}
                  </div>
                </div>
                
                <div className="mb-3">
                  <p className="text-white/80 text-sm leading-relaxed">
                    {message.message}
                  </p>
                </div>
                
                {message.admin_reply && (
                  <div className="mt-4 p-3 bg-gold/10 border border-gold/30 rounded">
                    <div className="flex items-center mb-2">
                      <Reply className="h-4 w-4 text-gold mr-2" />
                      <span className="text-gold font-medium text-sm">Admin Reply:</span>
                    </div>
                    <p className="text-white text-sm leading-relaxed">
                      {message.admin_reply}
                    </p>
                  </div>
                )}
                
                <div className="flex justify-between items-center mt-3 pt-3 border-t border-gold/20">
                  <span className="text-xs text-white/50">
                    Sent: {formatDate(message.created_at)}
                  </span>
                  {message.status === 'replied' && (
                    <span className="text-xs text-white/50">
                      Replied: {formatDate(message.updated_at)}
                    </span>
                  )}
                </div>
              </div>
            ))}
          </div>
        )}
      </CardContent>
    </Card>
  );
};

export default ContactMessages;

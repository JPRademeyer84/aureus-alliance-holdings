import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Textarea } from '@/components/ui/textarea';
import { useToast } from '@/hooks/use-toast';
import {
  Loader2,
  Clock,
  CheckCircle,
  RefreshCw,
  User,
  Mail
} from '@/components/SafeIcons';

// Safe message icons
const MessageSquare = ({ className }: { className?: string }) => <span className={className}>💬</span>;
const Reply = ({ className }: { className?: string }) => <span className={className}>↩️</span>;
const Send = ({ className }: { className?: string }) => <span className={className}>📤</span>;
import ApiConfig from '@/config/api';

interface ContactMessage {
  id: string;
  user_id: string;
  username: string;
  email: string;
  subject: string;
  message: string;
  status: 'unread' | 'read' | 'replied';
  admin_reply?: string;
  created_at: string;
  updated_at: string;
}

interface ContactMessagesManagerProps {
  isActive?: boolean;
}

const ContactMessagesManager: React.FC<ContactMessagesManagerProps> = ({ isActive = false }) => {
  const [messages, setMessages] = useState<ContactMessage[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [selectedMessage, setSelectedMessage] = useState<ContactMessage | null>(null);
  const [replyText, setReplyText] = useState('');
  const [isSendingReply, setIsSendingReply] = useState(false);
  const [statusFilter, setStatusFilter] = useState<'all' | 'unread' | 'read' | 'replied'>('all');
  const { toast } = useToast();

  const fetchMessages = async (showRefreshIndicator = false) => {
    if (showRefreshIndicator) {
      setIsRefreshing(true);
    } else {
      setIsLoading(true);
    }

    try {
      const params = new URLSearchParams({
        admin_view: 'true',
        limit: '50'
      });
      
      if (statusFilter !== 'all') {
        params.append('status', statusFilter);
      }

      const response = await fetch(
        `${ApiConfig.endpoints.contact.messages}?${params}`,
        {
          method: 'GET',
          headers: {
            'Content-Type': 'application/json',
          },
          credentials: 'include', // Include session cookies for admin authentication
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
        statusFilter: statusFilter,
        endpoint: `${ApiConfig.endpoints.contact.messages}?admin_view=true&limit=50${statusFilter !== 'all' ? `&status=${statusFilter}` : ''}`
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
    // Only fetch messages if component is active and we're in admin context
    if (!isActive) {
      return;
    }

    const isAdminPage = window.location.pathname.includes('/admin');

    if (isAdminPage) {
      fetchMessages();
    }
  }, [statusFilter, isActive]);

  const markAsRead = async (messageId: string) => {
    try {
      const response = await fetch(ApiConfig.endpoints.contact.messages, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          action: 'mark_read',
          message_id: messageId,
        }),
      });

      const data = await response.json();
      if (data.success) {
        fetchMessages(true);
      }
    } catch (error) {
      console.error('Mark as read error:', error);
    }
  };

  const sendReply = async () => {
    if (!selectedMessage || !replyText.trim()) return;

    setIsSendingReply(true);
    try {
      const response = await fetch(ApiConfig.endpoints.contact.messages, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          action: 'reply',
          message_id: selectedMessage.id,
          admin_reply: replyText.trim(),
        }),
      });

      const data = await response.json();

      if (data.success) {
        toast({
          title: 'Reply Sent',
          description: 'Your reply has been sent successfully.',
        });
        
        setReplyText('');
        setSelectedMessage(null);
        fetchMessages(true);
      } else {
        throw new Error(data.error || 'Failed to send reply');
      }
    } catch (error) {
      console.error('Send reply error:', error);
      toast({
        title: 'Error',
        description: error instanceof Error ? error.message : 'Failed to send reply. Please try again.',
        variant: 'destructive',
      });
    } finally {
      setIsSendingReply(false);
    }
  };

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

  const unreadCount = messages.filter(m => m.status === 'unread').length;

  return (
    <div className="space-y-6">
      <Card>
        <CardHeader>
          <div className="flex items-center justify-between">
            <CardTitle className="flex items-center">
              <MessageSquare className="h-5 w-5 mr-2" />
              Contact Messages ({messages.length})
              {unreadCount > 0 && (
                <Badge className="ml-2 bg-red-500 text-white">
                  {unreadCount} unread
                </Badge>
              )}
            </CardTitle>
            <div className="flex items-center gap-2">
              <select
                value={statusFilter}
                onChange={(e) => setStatusFilter(e.target.value as any)}
                className="text-sm border border-gray-300 rounded px-2 py-1"
              >
                <option value="all">All Messages</option>
                <option value="unread">Unread</option>
                <option value="read">Read</option>
                <option value="replied">Replied</option>
              </select>
              <Button
                onClick={() => fetchMessages(true)}
                disabled={isRefreshing}
                variant="outline"
                size="sm"
              >
                {isRefreshing ? (
                  <Loader2 className="h-4 w-4 animate-spin" />
                ) : (
                  <RefreshCw className="h-4 w-4" />
                )}
              </Button>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <div className="flex items-center justify-center py-8">
              <Loader2 className="h-6 w-6 animate-spin" />
              <span className="ml-2">Loading messages...</span>
            </div>
          ) : messages.length === 0 ? (
            <div className="text-center py-8 text-gray-500">
              <MessageSquare className="h-12 w-12 mx-auto mb-4 text-gray-300" />
              <p>No messages found.</p>
            </div>
          ) : (
            <div className="space-y-4">
              {messages.map((message) => (
                <div
                  key={message.id}
                  className="border rounded-lg p-4 hover:bg-gray-50 transition-colors"
                >
                  <div className="flex items-start justify-between mb-3">
                    <div className="flex items-center space-x-3">
                      {getStatusIcon(message.status)}
                      <div>
                        <h3 className="font-semibold">{message.subject}</h3>
                        <div className="flex items-center space-x-2 text-sm text-gray-600">
                          <User className="h-3 w-3" />
                          <span>{message.username}</span>
                          <Mail className="h-3 w-3" />
                          <span>{message.email}</span>
                        </div>
                      </div>
                    </div>
                    <div className="flex items-center space-x-2">
                      {getStatusBadge(message.status)}
                      {message.status === 'unread' && (
                        <Button
                          onClick={() => markAsRead(message.id)}
                          size="sm"
                          variant="outline"
                        >
                          Mark Read
                        </Button>
                      )}
                      <Button
                        onClick={() => {
                          setSelectedMessage(message);
                          setReplyText(message.admin_reply || '');
                        }}
                        size="sm"
                        variant="outline"
                      >
                        <Reply className="h-3 w-3 mr-1" />
                        Reply
                      </Button>
                    </div>
                  </div>
                  
                  <div className="mb-3">
                    <p className="text-gray-700 text-sm leading-relaxed">
                      {message.message}
                    </p>
                  </div>
                  
                  {message.admin_reply && (
                    <div className="mt-4 p-3 bg-blue-50 border border-blue-200 rounded">
                      <div className="flex items-center mb-2">
                        <Reply className="h-4 w-4 text-blue-600 mr-2" />
                        <span className="text-blue-600 font-medium text-sm">Your Reply:</span>
                      </div>
                      <p className="text-gray-700 text-sm leading-relaxed">
                        {message.admin_reply}
                      </p>
                    </div>
                  )}
                  
                  <div className="flex justify-between items-center mt-3 pt-3 border-t text-xs text-gray-500">
                    <span>Sent: {formatDate(message.created_at)}</span>
                    {message.status === 'replied' && (
                      <span>Replied: {formatDate(message.updated_at)}</span>
                    )}
                  </div>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>

      {/* Reply Modal */}
      {selectedMessage && (
        <Card>
          <CardHeader>
            <CardTitle>Reply to: {selectedMessage.subject}</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              <div className="p-3 bg-gray-50 rounded">
                <p className="text-sm text-gray-600 mb-2">
                  <strong>From:</strong> {selectedMessage.username} ({selectedMessage.email})
                </p>
                <p className="text-sm">{selectedMessage.message}</p>
              </div>
              
              <div>
                <Textarea
                  value={replyText}
                  onChange={(e) => setReplyText(e.target.value)}
                  placeholder="Type your reply..."
                  className="min-h-[120px]"
                  maxLength={5000}
                />
                <div className="text-xs text-gray-500 text-right mt-1">
                  {replyText.length}/5000 characters
                </div>
              </div>
              
              <div className="flex justify-end space-x-2">
                <Button
                  onClick={() => {
                    setSelectedMessage(null);
                    setReplyText('');
                  }}
                  variant="outline"
                >
                  Cancel
                </Button>
                <Button
                  onClick={sendReply}
                  disabled={!replyText.trim() || isSendingReply}
                >
                  {isSendingReply ? (
                    <>
                      <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                      Sending...
                    </>
                  ) : (
                    <>
                      <Send className="h-4 w-4 mr-2" />
                      Send Reply
                    </>
                  )}
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
};

export default ContactMessagesManager;

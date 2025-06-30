import React, { useState, useEffect } from 'react';
import { useToast } from '@/hooks/use-toast';
import { useAdmin } from '@/contexts/AdminContext';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Textarea } from '@/components/ui/textarea';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Mail, MessageSquare, Reply, Eye, Loader2, Clock, CheckCircle } from 'lucide-react';
import ApiConfig from '@/config/api';

interface OfflineMessage {
  id: string;
  guest_name: string;
  guest_email: string;
  subject: string;
  message: string;
  status: 'unread' | 'read' | 'replied';
  admin_reply: string | null;
  replied_by_username: string | null;
  replied_at: string | null;
  created_at: string;
}

interface OfflineMessagesManagerProps {
  isActive?: boolean;
}

const OfflineMessagesManager: React.FC<OfflineMessagesManagerProps> = ({ isActive = false }) => {
  const [messages, setMessages] = useState<OfflineMessage[]>([]);
  const [selectedMessage, setSelectedMessage] = useState<OfflineMessage | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isReplyDialogOpen, setIsReplyDialogOpen] = useState(false);
  const [replyText, setReplyText] = useState('');
  const [statusFilter, setStatusFilter] = useState<'all' | 'unread' | 'read' | 'replied'>('all');
  const [unreadCount, setUnreadCount] = useState(0);
  const [isSending, setIsSending] = useState(false);

  const { admin } = useAdmin();
  const { toast } = useToast();

  useEffect(() => {
    // Only fetch messages if component is active and admin is properly loaded
    if (!isActive) {
      return;
    }

    if (!admin?.id) {
      return;
    }

    fetchMessages();
    // Poll for new messages every 30 seconds
    const interval = setInterval(fetchMessages, 30000);
    return () => clearInterval(interval);
  }, [statusFilter, admin?.id, isActive]);

  const fetchMessages = async () => {
    if (!admin?.id) return;
    
    setIsLoading(true);
    try {
      const params = new URLSearchParams({
        admin_id: admin.id,
        ...(statusFilter !== 'all' && { status: statusFilter })
      });
      
      const response = await fetch(`${ApiConfig.endpoints.chat.offlineMessages}?${params}`, {
        credentials: 'include' // Include session cookies for admin authentication
      });
      const data = await response.json();
      
      if (data.success) {
        setMessages(data.data.messages);
        setUnreadCount(data.data.unread_count);
      } else {
        throw new Error(data.message);
      }
    } catch (error) {
      console.error('Fetch messages error:', {
        error: error,
        message: error instanceof Error ? error.message : 'Unknown error',
        stack: error instanceof Error ? error.stack : undefined,
        adminId: admin?.id,
        statusFilter: statusFilter,
        endpoint: `${ApiConfig.endpoints.chat.offlineMessages}?admin_id=${admin?.id}${statusFilter !== 'all' ? `&status=${statusFilter}` : ''}`
      });
      toast({
        title: 'Error',
        description: 'Failed to load offline messages',
        variant: 'destructive',
      });
    } finally {
      setIsLoading(false);
    }
  };

  const markAsRead = async (messageId: string) => {
    if (!admin?.id) return;
    
    try {
      const response = await fetch(ApiConfig.endpoints.chat.offlineMessages, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include', // Include session cookies for admin authentication
        body: JSON.stringify({
          action: 'mark_read',
          message_id: messageId,
          admin_id: admin.id
        }),
      });

      const data = await response.json();
      if (data.success) {
        fetchMessages();
      } else {
        throw new Error(data.message);
      }
    } catch (error) {
      console.error('Mark as read error:', error);
      toast({
        title: 'Error',
        description: 'Failed to mark message as read',
        variant: 'destructive',
      });
    }
  };

  const sendReply = async () => {
    if (!admin?.id || !selectedMessage || !replyText.trim()) return;
    
    setIsSending(true);
    try {
      const response = await fetch(ApiConfig.endpoints.chat.offlineMessages, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include', // Include session cookies for admin authentication
        body: JSON.stringify({
          action: 'reply',
          message_id: selectedMessage.id,
          admin_id: admin.id,
          reply: replyText.trim()
        }),
      });

      const data = await response.json();
      if (data.success) {
        toast({
          title: 'Success',
          description: 'Reply sent successfully',
        });
        setIsReplyDialogOpen(false);
        setReplyText('');
        setSelectedMessage(null);
        fetchMessages();
      } else {
        throw new Error(data.message);
      }
    } catch (error) {
      console.error('Send reply error:', error);
      toast({
        title: 'Error',
        description: error instanceof Error ? error.message : 'Failed to send reply',
        variant: 'destructive',
      });
    } finally {
      setIsSending(false);
    }
  };

  const getStatusBadge = (status: string) => {
    const statusConfig = {
      unread: { label: 'Unread', variant: 'destructive' as const, icon: Mail },
      read: { label: 'Read', variant: 'secondary' as const, icon: Eye },
      replied: { label: 'Replied', variant: 'default' as const, icon: CheckCircle }
    };
    
    const config = statusConfig[status as keyof typeof statusConfig] || statusConfig.unread;
    const Icon = config.icon;
    
    return (
      <Badge variant={config.variant} className="flex items-center gap-1">
        <Icon className="h-3 w-3" />
        {config.label}
      </Badge>
    );
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString();
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold flex items-center gap-2">
            <MessageSquare className="h-6 w-6" />
            Offline Messages
            {unreadCount > 0 && (
              <Badge variant="destructive" className="ml-2">
                {unreadCount} unread
              </Badge>
            )}
          </h2>
          <p className="text-muted-foreground mt-1">
            Messages from users when no admin was available
          </p>
        </div>
        
        <div className="flex items-center gap-4">
          <Select value={statusFilter} onValueChange={(value: any) => setStatusFilter(value)}>
            <SelectTrigger className="w-40 bg-gray-700 border-gray-600 text-white">
              <SelectValue />
            </SelectTrigger>
            <SelectContent className="bg-gray-700 border-gray-600">
              <SelectItem value="all" className="text-white hover:bg-gray-600">All Messages</SelectItem>
              <SelectItem value="unread" className="text-white hover:bg-gray-600">Unread</SelectItem>
              <SelectItem value="read" className="text-white hover:bg-gray-600">Read</SelectItem>
              <SelectItem value="replied" className="text-white hover:bg-gray-600">Replied</SelectItem>
            </SelectContent>
          </Select>
          
          <Button onClick={fetchMessages} variant="outline">
            Refresh
          </Button>
        </div>
      </div>

      {/* Messages Table */}
      <Card>
        <CardHeader>
          <CardTitle>Messages</CardTitle>
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
              <p>No offline messages found.</p>
            </div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Status</TableHead>
                  <TableHead>From</TableHead>
                  <TableHead>Subject</TableHead>
                  <TableHead>Message</TableHead>
                  <TableHead>Date</TableHead>
                  <TableHead>Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {messages.map((message) => (
                  <TableRow key={message.id} className={message.status === 'unread' ? 'bg-blue-50' : ''}>
                    <TableCell>{getStatusBadge(message.status)}</TableCell>
                    <TableCell>
                      <div>
                        <div className="font-medium">{message.guest_name}</div>
                        <div className="text-sm text-muted-foreground">{message.guest_email}</div>
                      </div>
                    </TableCell>
                    <TableCell className="font-medium">{message.subject}</TableCell>
                    <TableCell>
                      <div className="max-w-xs truncate" title={message.message}>
                        {message.message}
                      </div>
                    </TableCell>
                    <TableCell>
                      <div className="text-sm">
                        <div className="flex items-center gap-1">
                          <Clock className="h-3 w-3" />
                          {formatDate(message.created_at)}
                        </div>
                        {message.replied_at && (
                          <div className="text-muted-foreground mt-1">
                            Replied: {formatDate(message.replied_at)}
                          </div>
                        )}
                      </div>
                    </TableCell>
                    <TableCell>
                      <div className="flex items-center gap-2">
                        {message.status === 'unread' && (
                          <Button
                            variant="outline"
                            size="sm"
                            onClick={() => markAsRead(message.id)}
                          >
                            <Eye className="h-4 w-4 mr-1" />
                            Mark Read
                          </Button>
                        )}
                        
                        <Dialog 
                          open={isReplyDialogOpen && selectedMessage?.id === message.id} 
                          onOpenChange={(open) => {
                            setIsReplyDialogOpen(open);
                            if (open) {
                              setSelectedMessage(message);
                              if (message.status === 'unread') {
                                markAsRead(message.id);
                              }
                            } else {
                              setSelectedMessage(null);
                              setReplyText('');
                            }
                          }}
                        >
                          <DialogTrigger asChild>
                            <Button variant="outline" size="sm">
                              <Reply className="h-4 w-4 mr-1" />
                              {message.status === 'replied' ? 'View Reply' : 'Reply'}
                            </Button>
                          </DialogTrigger>
                          <DialogContent className="max-w-2xl">
                            <DialogHeader>
                              <DialogTitle>
                                {message.status === 'replied' ? 'Message & Reply' : 'Reply to Message'}
                              </DialogTitle>
                            </DialogHeader>
                            <div className="space-y-4">
                              {/* Original Message */}
                              <div className="border rounded-lg p-4 bg-gray-50">
                                <div className="flex justify-between items-start mb-2">
                                  <div>
                                    <h4 className="font-medium">{message.guest_name}</h4>
                                    <p className="text-sm text-muted-foreground">{message.guest_email}</p>
                                  </div>
                                  <div className="text-sm text-muted-foreground">
                                    {formatDate(message.created_at)}
                                  </div>
                                </div>
                                <div className="mb-2">
                                  <strong>Subject:</strong> {message.subject}
                                </div>
                                <div className="whitespace-pre-wrap">{message.message}</div>
                              </div>

                              {/* Existing Reply */}
                              {message.admin_reply && (
                                <div className="border rounded-lg p-4 bg-blue-50">
                                  <div className="flex justify-between items-start mb-2">
                                    <h4 className="font-medium">Reply from {message.replied_by_username}</h4>
                                    <div className="text-sm text-muted-foreground">
                                      {message.replied_at && formatDate(message.replied_at)}
                                    </div>
                                  </div>
                                  <div className="whitespace-pre-wrap">{message.admin_reply}</div>
                                </div>
                              )}

                              {/* Reply Form */}
                              {message.status !== 'replied' && (
                                <div className="space-y-4">
                                  <div>
                                    <label className="block text-sm font-medium mb-2 text-gray-200">Your Reply</label>
                                    <Textarea
                                      value={replyText}
                                      onChange={(e) => setReplyText(e.target.value)}
                                      placeholder="Type your reply here..."
                                      rows={6}
                                    />
                                  </div>
                                  <div className="flex justify-end gap-2">
                                    <Button 
                                      variant="outline" 
                                      onClick={() => {
                                        setIsReplyDialogOpen(false);
                                        setSelectedMessage(null);
                                        setReplyText('');
                                      }}
                                    >
                                      Cancel
                                    </Button>
                                    <Button 
                                      onClick={sendReply}
                                      disabled={!replyText.trim() || isSending}
                                    >
                                      {isSending ? (
                                        <>
                                          <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                          Sending...
                                        </>
                                      ) : (
                                        <>
                                          <Reply className="h-4 w-4 mr-2" />
                                          Send Reply
                                        </>
                                      )}
                                    </Button>
                                  </div>
                                </div>
                              )}
                            </div>
                          </DialogContent>
                        </Dialog>
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>
    </div>
  );
};

export default OfflineMessagesManager;

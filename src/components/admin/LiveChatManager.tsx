import React, { useState, useEffect, useRef } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { useAdmin } from '@/contexts/AdminContext';
import { useToast } from '@/hooks/use-toast';
import {
  Loader2,
  MessageCircle,
  Send,
  RefreshCw,
  User,
  UserCheck,
  Clock,
  CheckCircle,
  X,
  Trash2,
  AlertTriangle
} from 'lucide-react';
import ApiConfig from '@/config/api';

interface ChatMessage {
  id: string;
  message: string;
  sender_type: 'user' | 'admin';
  sender_name: string;
  created_at: string;
  is_read: boolean;
}

interface ChatSession {
  id: string;
  user_id: string;
  username: string;
  email: string;
  admin_id?: string;
  admin_username?: string;
  status: 'waiting' | 'active' | 'closed';
  created_at: string;
  updated_at: string;
}

interface LiveChatManagerProps {
  isActive?: boolean;
}

const LiveChatManager: React.FC<LiveChatManagerProps> = ({ isActive = false }) => {
  const [sessions, setSessions] = useState<ChatSession[]>([]);
  const [selectedSession, setSelectedSession] = useState<ChatSession | null>(null);
  const [messages, setMessages] = useState<ChatMessage[]>([]);
  const [newMessage, setNewMessage] = useState('');
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [isSending, setIsSending] = useState(false);
  const [statusFilter, setStatusFilter] = useState<'all' | 'waiting' | 'active' | 'closed'>('all');
  const [agentStatus, setAgentStatus] = useState<'online' | 'offline' | 'busy'>('offline');
  const [isUpdatingStatus, setIsUpdatingStatus] = useState(false);
  const messagesEndRef = useRef<HTMLDivElement>(null);
  const pollingRef = useRef<NodeJS.Timeout | null>(null);
  const statusPollingRef = useRef<NodeJS.Timeout | null>(null);
  const { admin } = useAdmin();
  const { toast } = useToast();

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  };

  useEffect(() => {
    if (messages.length > 0) {
      scrollToBottom();
    }
  }, [messages]);

  const startPolling = () => {
    if (pollingRef.current) {
      clearInterval(pollingRef.current);
    }

    pollingRef.current = setInterval(() => {
      // Only poll if component is active
      if (isActive) {
        fetchSessions(true);
        if (selectedSession) {
          fetchMessages(selectedSession.id, true);
        }
      }
    }, 2000); // Poll every 2 seconds for more real-time experience
  };

  const stopPolling = () => {
    if (pollingRef.current) {
      clearInterval(pollingRef.current);
      pollingRef.current = null;
    }
  };

  const startStatusPolling = () => {
    if (statusPollingRef.current) {
      clearInterval(statusPollingRef.current);
    }

    // Poll for status updates every 30 seconds
    statusPollingRef.current = setInterval(() => {
      // Only poll if component is active
      if (isActive) {
        fetchAgentStatus();
      }
    }, 30000);
  };

  const stopStatusPolling = () => {
    if (statusPollingRef.current) {
      clearInterval(statusPollingRef.current);
      statusPollingRef.current = null;
    }
  };

  const fetchAgentStatus = async () => {
    // Double-check that component is active before making API call
    if (!isActive) {
      return;
    }

    if (!admin?.id) return;

    try {
      const response = await fetch(`${ApiConfig.endpoints.admin.manageAdmins}?current_admin_id=${admin.id}`, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include', // Include session cookies for admin authentication
      });

      const data = await response.json();
      if (data.success) {
        const currentAdmin = data.data.admins.find((a: any) => a.id === admin.id);
        if (currentAdmin) {
          setAgentStatus(currentAdmin.chat_status || 'offline');
        }
      }
    } catch (error) {
      console.error('Fetch agent status error:', {
        error: error,
        message: error instanceof Error ? error.message : 'Unknown error',
        stack: error instanceof Error ? error.stack : undefined,
        adminId: admin?.id,
        endpoint: `${ApiConfig.endpoints.admin.manageAdmins}?current_admin_id=${admin?.id}`
      });
    }
  };

  const updateAgentStatus = async (newStatus: 'online' | 'offline' | 'busy') => {
    if (!admin?.id || isUpdatingStatus) return;

    console.log('Updating agent status to:', newStatus, 'for admin:', admin.id);
    setIsUpdatingStatus(true);
    try {
      const requestBody = {
        action: 'update_chat_status',
        current_admin_id: admin.id,
        chat_status: newStatus,
      };

      console.log('Sending request:', requestBody);

      const response = await fetch(ApiConfig.endpoints.admin.manageAdmins, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include', // Include session cookies for admin authentication
        body: JSON.stringify(requestBody),
      });

      console.log('Response status:', response.status);
      const data = await response.json();
      console.log('Response data:', data);

      if (data.success) {
        setAgentStatus(newStatus);
        toast({
          title: 'Status Updated',
          description: `Your status has been set to ${newStatus}`,
        });

        // Force refresh agent status to verify
        setTimeout(() => {
          fetchAgentStatus();
        }, 1000);
      } else {
        throw new Error(data.error || data.message || 'Failed to update status');
      }
    } catch (error) {
      console.error('Update agent status error:', error);
      toast({
        title: 'Error',
        description: `Failed to update status: ${error.message}`,
        variant: 'destructive',
      });
    } finally {
      setIsUpdatingStatus(false);
    }
  };

  useEffect(() => {
    startPolling();
    return () => stopPolling();
  }, [selectedSession]);

  const fetchSessions = async (isPolling = false) => {
    // Double-check that component is active before making API call
    if (!isActive && !isPolling) {
      return;
    }

    if (!isPolling) {
      setIsLoading(true);
    } else {
      setIsRefreshing(true);
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
        `${ApiConfig.endpoints.chat.sessions}?${params}`,
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
        setSessions(data.data.sessions);
      } else {
        throw new Error(data.error || 'Failed to fetch sessions');
      }
    } catch (error) {
      if (!isPolling) {
        console.error('Fetch sessions error:', {
          error: error,
          message: error instanceof Error ? error.message : 'Unknown error',
          stack: error instanceof Error ? error.stack : undefined,
          statusFilter: statusFilter,
          endpoint: `${ApiConfig.endpoints.chat.sessions}?admin_view=true&limit=50${statusFilter !== 'all' ? `&status=${statusFilter}` : ''}`
        });
        toast({
          title: 'Error',
          description: 'Failed to load chat sessions. Please try again.',
          variant: 'destructive',
        });
      }
    } finally {
      setIsLoading(false);
      setIsRefreshing(false);
    }
  };

  const fetchMessages = async (sessionId: string, isPolling = false) => {
    try {
      const response = await fetch(
        `${ApiConfig.endpoints.chat.messages}?session_id=${sessionId}&limit=100`,
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
        
        // Mark messages as read
        if (data.data.unread_counts.from_user > 0) {
          markMessagesAsRead(sessionId);
        }
      }
    } catch (error) {
      if (!isPolling) {
        console.error('Fetch messages error:', {
          error: error,
          message: error instanceof Error ? error.message : 'Unknown error',
          stack: error instanceof Error ? error.stack : undefined,
          sessionId: sessionId,
          endpoint: `${ApiConfig.endpoints.chat.messages}?session_id=${sessionId}&limit=100`
        });
      }
    }
  };

  const markMessagesAsRead = async (sessionId: string) => {
    try {
      await fetch(ApiConfig.endpoints.chat.messages, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include', // Include session cookies for admin authentication
        body: JSON.stringify({
          action: 'mark_read',
          session_id: sessionId,
          reader_type: 'admin',
        }),
      });
    } catch (error) {
      console.error('Mark messages as read error:', error);
    }
  };

  const assignToSession = async (sessionId: string) => {
    if (!admin) return;

    try {
      const response = await fetch(ApiConfig.endpoints.chat.sessions, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include', // Include session cookies for admin authentication
        body: JSON.stringify({
          action: 'assign',
          session_id: sessionId,
          admin_id: admin.id,
        }),
      });

      const data = await response.json();

      if (data.success) {
        toast({
          title: 'Session Assigned',
          description: 'You have been assigned to this chat session.',
        });
        fetchSessions(true);
      } else {
        throw new Error(data.error);
      }
    } catch (error) {
      console.error('Assign session error:', error);
      toast({
        title: 'Error',
        description: 'Failed to assign session. Please try again.',
        variant: 'destructive',
      });
    }
  };

  const sendMessage = async () => {
    if (!selectedSession || !newMessage.trim() || isSending || !admin) return;

    setIsSending(true);
    try {
      const response = await fetch(ApiConfig.endpoints.chat.messages, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include', // Include session cookies for admin authentication
        body: JSON.stringify({
          action: 'send',
          session_id: selectedSession.id,
          sender_type: 'admin',
          sender_id: admin.id,
          message: newMessage.trim(),
        }),
      });

      const data = await response.json();
      if (data.success) {
        setNewMessage('');
        fetchMessages(selectedSession.id);
      } else {
        throw new Error(data.error);
      }
    } catch (error) {
      console.error('Send message error:', error);
      toast({
        title: 'Error',
        description: 'Failed to send message. Please try again.',
        variant: 'destructive',
      });
    } finally {
      setIsSending(false);
    }
  };

  const closeSession = async (sessionId: string) => {
    try {
      const response = await fetch(ApiConfig.endpoints.chat.sessions, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include', // Include session cookies for admin authentication
        body: JSON.stringify({
          action: 'close',
          session_id: sessionId,
        }),
      });

      const data = await response.json();
      if (data.success) {
        toast({
          title: 'Session Closed',
          description: 'The chat session has been closed successfully.',
        });

        // Update the selected session status
        if (selectedSession?.id === sessionId) {
          setSelectedSession({
            ...selectedSession,
            status: 'closed'
          });
        }

        // Refresh sessions list
        fetchSessions(true);
      } else {
        throw new Error(data.error);
      }
    } catch (error) {
      console.error('Close session error:', error);
      toast({
        title: 'Error',
        description: 'Failed to close session. Please try again.',
        variant: 'destructive',
      });
    }
  };

  const clearAllSessions = async () => {
    if (!confirm('Are you sure you want to clear ALL chat sessions? This action cannot be undone.')) {
      return;
    }

    try {
      const response = await fetch(ApiConfig.endpoints.admin.clearSessions, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include', // Include session cookies for admin authentication
        body: JSON.stringify({
          action: 'clear_all',
        }),
      });

      const data = await response.json();
      if (data.success) {
        toast({
          title: 'Sessions Cleared',
          description: 'All chat sessions have been cleared successfully.',
        });
        setSessions([]);
        setSelectedSession(null);
        setMessages([]);
      } else {
        throw new Error(data.error);
      }
    } catch (error) {
      console.error('Clear all sessions error:', error);
      toast({
        title: 'Error',
        description: 'Failed to clear sessions. Please try again.',
        variant: 'destructive',
      });
    }
  };

  const clearClosedSessions = async () => {
    if (!confirm('Are you sure you want to clear all closed chat sessions? This action cannot be undone.')) {
      return;
    }

    try {
      const response = await fetch(ApiConfig.endpoints.admin.clearSessions, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include', // Include session cookies for admin authentication
        body: JSON.stringify({
          action: 'clear_closed',
        }),
      });

      const data = await response.json();
      if (data.success) {
        toast({
          title: 'Closed Sessions Cleared',
          description: `${data.data.count} closed sessions have been cleared successfully.`,
        });
        fetchSessions(true);

        // Clear selected session if it was closed
        if (selectedSession?.status === 'closed') {
          setSelectedSession(null);
          setMessages([]);
        }
      } else {
        throw new Error(data.error);
      }
    } catch (error) {
      console.error('Clear closed sessions error:', error);
      toast({
        title: 'Error',
        description: 'Failed to clear closed sessions. Please try again.',
        variant: 'destructive',
      });
    }
  };

  const deleteSpecificSession = async (sessionId: string) => {
    if (!confirm('Are you sure you want to delete this chat session? This action cannot be undone.')) {
      return;
    }

    try {
      const response = await fetch(ApiConfig.endpoints.admin.clearSessions, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include', // Include session cookies for admin authentication
        body: JSON.stringify({
          action: 'clear_specific',
          session_id: sessionId,
        }),
      });

      const data = await response.json();
      if (data.success) {
        toast({
          title: 'Session Deleted',
          description: 'The chat session has been deleted successfully.',
        });

        // Clear selected session if it was the deleted one
        if (selectedSession?.id === sessionId) {
          setSelectedSession(null);
          setMessages([]);
        }

        fetchSessions(true);
      } else {
        throw new Error(data.error);
      }
    } catch (error) {
      console.error('Delete session error:', error);
      toast({
        title: 'Error',
        description: 'Failed to delete session. Please try again.',
        variant: 'destructive',
      });
    }
  };

  useEffect(() => {
    // Only initialize if component is active and admin is properly loaded
    if (!isActive) {
      return;
    }

    if (!admin?.id) {
      return;
    }

    fetchSessions();
    fetchAgentStatus();
    startStatusPolling();

    return () => {
      stopStatusPolling();
    };
  }, [admin?.id, isActive]);

  useEffect(() => {
    // Only fetch sessions if component is active and admin is properly loaded
    if (!isActive) {
      return;
    }

    if (!admin?.id) {
      return;
    }

    fetchSessions();
  }, [statusFilter, admin?.id, isActive]);

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'waiting':
        return <Badge variant="secondary" className="bg-yellow-500/10 text-yellow-500">Waiting</Badge>;
      case 'active':
        return <Badge variant="secondary" className="bg-green-500/10 text-green-500">Active</Badge>;
      case 'closed':
        return <Badge variant="secondary" className="bg-gray-500/10 text-gray-500">Closed</Badge>;
      default:
        return <Badge variant="secondary">Unknown</Badge>;
    }
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString();
  };

  const waitingSessions = sessions.filter(s => s.status === 'waiting').length;

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'online':
        return 'bg-green-500';
      case 'busy':
        return 'bg-yellow-500';
      case 'offline':
      default:
        return 'bg-gray-500';
    }
  };

  const getStatusText = (status: string) => {
    switch (status) {
      case 'online':
        return 'Available';
      case 'busy':
        return 'Busy';
      case 'offline':
      default:
        return 'Offline';
    }
  };

  return (
    <div className="space-y-6">
      {/* Agent Status Header */}
      <Card>
        <CardHeader>
          <div className="flex items-center justify-between">
            <div className="flex items-center space-x-4">
              <div className="flex items-center space-x-2">
                <div className={`w-3 h-3 rounded-full ${getStatusColor(agentStatus)}`}></div>
                <span className="text-lg font-semibold text-white">
                  Agent Status: {getStatusText(agentStatus)}
                </span>
              </div>
            </div>
            <div className="flex items-center space-x-2">
              <Button
                onClick={() => updateAgentStatus('online')}
                disabled={isUpdatingStatus || agentStatus === 'online'}
                size="sm"
                variant={agentStatus === 'online' ? 'default' : 'outline'}
                className={agentStatus === 'online' ? 'bg-green-600 hover:bg-green-700' : ''}
              >
                {isUpdatingStatus && agentStatus !== 'online' ? (
                  <Loader2 className="h-4 w-4 animate-spin mr-1" />
                ) : (
                  <div className="w-2 h-2 bg-green-500 rounded-full mr-1"></div>
                )}
                Available
              </Button>
              <Button
                onClick={() => updateAgentStatus('busy')}
                disabled={isUpdatingStatus || agentStatus === 'busy'}
                size="sm"
                variant={agentStatus === 'busy' ? 'default' : 'outline'}
                className={agentStatus === 'busy' ? 'bg-yellow-600 hover:bg-yellow-700' : ''}
              >
                {isUpdatingStatus && agentStatus !== 'busy' ? (
                  <Loader2 className="h-4 w-4 animate-spin mr-1" />
                ) : (
                  <div className="w-2 h-2 bg-yellow-500 rounded-full mr-1"></div>
                )}
                Busy
              </Button>
              <Button
                onClick={() => updateAgentStatus('offline')}
                disabled={isUpdatingStatus || agentStatus === 'offline'}
                size="sm"
                variant={agentStatus === 'offline' ? 'default' : 'outline'}
                className={agentStatus === 'offline' ? 'bg-gray-600 hover:bg-gray-700' : ''}
              >
                {isUpdatingStatus && agentStatus !== 'offline' ? (
                  <Loader2 className="h-4 w-4 animate-spin mr-1" />
                ) : (
                  <div className="w-2 h-2 bg-gray-500 rounded-full mr-1"></div>
                )}
                Offline
              </Button>
            </div>
          </div>
        </CardHeader>
      </Card>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Sessions List */}
        <Card>
          <CardHeader>
            <div className="flex items-center justify-between">
              <CardTitle className="flex items-center">
                <MessageCircle className="h-5 w-5 mr-2" />
                Chat Sessions ({sessions.length})
                {waitingSessions > 0 && (
                  <Badge className="ml-2 bg-red-500 text-white">
                    {waitingSessions} waiting
                  </Badge>
                )}
              </CardTitle>
            <div className="flex items-center gap-2">
              <select
                value={statusFilter}
                onChange={(e) => setStatusFilter(e.target.value as any)}
                className="text-sm border bg-gray-800 border-gray-600 text-white rounded px-2 py-1"
              >
                <option value="all">All Sessions</option>
                <option value="waiting">Waiting</option>
                <option value="active">Active</option>
                <option value="closed">Closed</option>
              </select>
              <Button
                onClick={() => fetchSessions()}
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
              <Button
                onClick={clearClosedSessions}
                variant="outline"
                size="sm"
                className="border-orange-200 text-orange-600 hover:bg-orange-50 hover:border-orange-300"
                title="Clear all closed sessions"
              >
                <Trash2 className="h-4 w-4" />
                Clear Closed
              </Button>
              <Button
                onClick={clearAllSessions}
                variant="outline"
                size="sm"
                className="border-red-200 text-red-600 hover:bg-red-50 hover:border-red-300"
                title="Clear all sessions (WARNING: This will delete everything!)"
              >
                <AlertTriangle className="h-4 w-4 mr-1" />
                Clear All
              </Button>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <div className="flex items-center justify-center py-8">
              <Loader2 className="h-6 w-6 animate-spin" />
              <span className="ml-2">Loading sessions...</span>
            </div>
          ) : sessions.length === 0 ? (
            <div className="text-center py-8 text-gray-300">
              <MessageCircle className="h-12 w-12 mx-auto mb-4 text-gray-400" />
              <p>No chat sessions found.</p>
            </div>
          ) : (
            <div className="space-y-3 max-h-96 overflow-y-auto">
              {sessions.map((session) => (
                <div
                  key={session.id}
                  className={`border rounded-lg p-3 cursor-pointer transition-colors ${
                    selectedSession?.id === session.id
                      ? 'bg-blue-900/30 border-blue-500'
                      : 'bg-gray-700 border-gray-600 hover:bg-gray-600'
                  }`}
                  onClick={() => {
                    setSelectedSession(session);
                    fetchMessages(session.id);
                  }}
                >
                  <div className="flex items-center justify-between mb-2">
                    <div className="flex items-center space-x-2">
                      <User className="h-4 w-4 text-gray-400" />
                      <span className="font-medium">{session.username}</span>
                    </div>
                    {getStatusBadge(session.status)}
                  </div>
                  
                  <div className="text-sm text-gray-400 mb-2">
                    {session.email}
                  </div>
                  
                  <div className="flex justify-between items-center">
                    <span className="text-xs text-gray-400">
                      {formatDate(session.created_at)}
                    </span>
                    <div className="flex items-center gap-2">
                      {session.status === 'waiting' && (
                        <Button
                          onClick={(e) => {
                            e.stopPropagation();
                            assignToSession(session.id);
                          }}
                          size="sm"
                          variant="outline"
                        >
                          Take Chat
                        </Button>
                      )}
                      <Button
                        onClick={(e) => {
                          e.stopPropagation();
                          deleteSpecificSession(session.id);
                        }}
                        size="sm"
                        variant="outline"
                        className="border-red-200 text-red-600 hover:bg-red-50 hover:border-red-300"
                        title="Delete this session"
                      >
                        <Trash2 className="h-3 w-3" />
                      </Button>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>

      {/* Chat Interface */}
      <Card>
        <CardHeader>
          <div className="flex items-center justify-between">
            <CardTitle className="flex items-center">
              {selectedSession ? (
                <>
                  <MessageCircle className="h-5 w-5 mr-2" />
                  Chat with {selectedSession.username}
                </>
              ) : (
                <>
                  <MessageCircle className="h-5 w-5 mr-2" />
                  Select a Chat Session
                </>
              )}
            </CardTitle>
            {selectedSession && (
              <div className="flex items-center gap-2">
                {getStatusBadge(selectedSession.status)}
                {selectedSession.status !== 'closed' && (
                  <Button
                    onClick={() => closeSession(selectedSession.id)}
                    size="sm"
                    variant="outline"
                    className="border-red-200 text-red-600 hover:bg-red-50 hover:border-red-300"
                  >
                    <X className="h-4 w-4 mr-1" />
                    Close Session
                  </Button>
                )}
              </div>
            )}
          </div>
        </CardHeader>
        <CardContent>
          {!selectedSession ? (
            <div className="flex items-center justify-center h-80 text-gray-300">
              <div className="text-center">
                <MessageCircle className="h-12 w-12 mx-auto mb-4 text-gray-400" />
                <p>Select a chat session to start messaging</p>
              </div>
            </div>
          ) : (
            <>
              {/* Messages */}
              <div className="h-80 overflow-y-auto border border-gray-600 rounded p-3 mb-4 bg-gray-900">
                {messages.length === 0 ? (
                  <div className="flex items-center justify-center h-full text-gray-300">
                    <p>No messages yet</p>
                  </div>
                ) : (
                  <div className="space-y-3">
                    {messages.map((message) => (
                      <div
                        key={message.id}
                        className={`flex ${message.sender_type === 'admin' ? 'justify-end' : 'justify-start'}`}
                      >
                        <div
                          className={`max-w-[80%] rounded-lg p-2 ${
                            message.sender_type === 'admin'
                              ? 'bg-blue-500 text-white'
                              : 'bg-gray-700 text-white border border-gray-600'
                          }`}
                        >
                          <div className="flex items-center mb-1">
                            {message.sender_type === 'admin' ? (
                              <UserCheck className="h-3 w-3 mr-1" />
                            ) : (
                              <User className="h-3 w-3 mr-1" />
                            )}
                            <span className="text-xs font-medium">
                              {message.sender_name}
                            </span>
                          </div>
                          <p className="text-sm">{message.message}</p>
                          <div className="text-xs opacity-70 mt-1">
                            {new Date(message.created_at).toLocaleTimeString()}
                          </div>
                        </div>
                      </div>
                    ))}
                    <div ref={messagesEndRef} />
                  </div>
                )}
              </div>
              
              {/* Message Input */}
              {selectedSession.status !== 'closed' && (
                <div className="flex gap-2">
                  <Input
                    value={newMessage}
                    onChange={(e) => setNewMessage(e.target.value)}
                    placeholder="Type your message..."
                    onKeyPress={(e) => e.key === 'Enter' && sendMessage()}
                    disabled={isSending}
                    maxLength={2000}
                  />
                  <Button
                    onClick={sendMessage}
                    disabled={!newMessage.trim() || isSending}
                    size="sm"
                  >
                    {isSending ? (
                      <Loader2 className="h-4 w-4 animate-spin" />
                    ) : (
                      <Send className="h-4 w-4" />
                    )}
                  </Button>
                </div>
              )}
            </>
          )}
        </CardContent>
      </Card>
      </div>
    </div>
  );
};

export default LiveChatManager;

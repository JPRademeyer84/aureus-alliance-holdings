import React, { useState, useEffect, useRef } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useUser } from '@/contexts/UserContext';
import { useToast } from '@/hooks/use-toast';
import {
  Loader2,
  MessageCircle,
  Send,
  X,
  Minimize2,
  Maximize2,
  User,
  UserCheck,
  Mail,
  Star,
  StarOff,
  ThumbsUp,
  ThumbsDown,
  Heart,
  CheckCircle2,
  Clock,
  Sparkles
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
  status: 'waiting' | 'active' | 'closed';
  admin_username?: string;
  created_at: string;
}

const LiveChat: React.FC = () => {
  const [isOpen, setIsOpen] = useState(false);
  const [isMinimized, setIsMinimized] = useState(false);
  const [session, setSession] = useState<ChatSession | null>(null);
  const [messages, setMessages] = useState<ChatMessage[]>([]);
  const [newMessage, setNewMessage] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [isSending, setIsSending] = useState(false);
  const [unreadCount, setUnreadCount] = useState(0);
  const [showEmailForm, setShowEmailForm] = useState(false);
  const [guestEmail, setGuestEmail] = useState('');
  const [guestName, setGuestName] = useState('');
  const [showRatingForm, setShowRatingForm] = useState(false);
  const [rating, setRating] = useState(0);
  const [feedback, setFeedback] = useState('');
  const [isSubmittingRating, setIsSubmittingRating] = useState(false);
  const [sessionEnded, setSessionEnded] = useState(false);
  const [showOfflineForm, setShowOfflineForm] = useState(false);
  const [offlineMessage, setOfflineMessage] = useState('');
  const [offlineSubject, setOfflineSubject] = useState('');
  const [isSubmittingOffline, setIsSubmittingOffline] = useState(false);
  const [agentsOnline, setAgentsOnline] = useState(0);
  const [isCheckingAgents, setIsCheckingAgents] = useState(false);
  const [lastMessageCount, setLastMessageCount] = useState(0);
  const messagesEndRef = useRef<HTMLDivElement>(null);
  const pollingRef = useRef<NodeJS.Timeout | null>(null);
  const agentPollingRef = useRef<NodeJS.Timeout | null>(null);
  const { user } = useUser();
  const { toast } = useToast();

  const playNotificationSound = () => {
    try {
      // Create a simple notification sound using Web Audio API
      const audioContext = new (window.AudioContext || (window as any).webkitAudioContext)();
      const oscillator = audioContext.createOscillator();
      const gainNode = audioContext.createGain();

      oscillator.connect(gainNode);
      gainNode.connect(audioContext.destination);

      oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
      oscillator.frequency.setValueAtTime(600, audioContext.currentTime + 0.1);

      gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
      gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);

      oscillator.start(audioContext.currentTime);
      oscillator.stop(audioContext.currentTime + 0.3);
    } catch (error) {
      // Fallback: no sound if Web Audio API is not supported
      console.log('Notification sound not supported');
    }
  };

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
      if (session) {
        fetchMessages(true);
      }
    }, 2000); // Poll every 2 seconds for more real-time experience
  };

  const stopPolling = () => {
    if (pollingRef.current) {
      clearInterval(pollingRef.current);
      pollingRef.current = null;
    }
  };

  const startAgentPolling = () => {
    if (agentPollingRef.current) {
      clearInterval(agentPollingRef.current);
    }

    // Check agent availability immediately
    checkAgentAvailability();

    // Poll for agent availability every 2 minutes to reduce server load and CORS errors
    agentPollingRef.current = setInterval(() => {
      checkAgentAvailability();
    }, 120000);
  };

  const stopAgentPolling = () => {
    if (agentPollingRef.current) {
      clearInterval(agentPollingRef.current);
      agentPollingRef.current = null;
    }
  };

  const checkAgentAvailability = async () => {
    // Temporarily disable agent status checking to prevent CORS errors
    // until backend CORS configuration is updated for port 5174
    setAgentsOnline(1);
    return;

    /* CORS FIX NEEDED: Uncomment below when backend allows localhost:5174
    try {
      const url = ApiConfig.endpoints.chat.agentStatus;

      const response = await fetch(url, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        },
        // Add timeout to prevent hanging requests
        signal: AbortSignal.timeout(5000)
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const data = await response.json();

      if (data.success) {
        const onlineCount = data.data?.online_count || 0;
        const busyCount = data.data?.busy_count || 0;
        const availableCount = data.data?.available_count || 0;

        // Use the available_count from API, fallback to manual calculation
        const totalAvailable = availableCount || (onlineCount + busyCount);
        setAgentsOnline(totalAvailable);
      } else {
        // Fallback: assume at least one agent is available
        setAgentsOnline(1);
      }
    } catch (error) {
      // Silently handle errors (including CORS issues) and assume agents are available
      // This prevents console spam while maintaining functionality
      setAgentsOnline(1);

      // Only log non-CORS errors in development
      if (process.env.NODE_ENV === 'development' && error instanceof Error) {
        if (!error.message.includes('CORS') && !error.message.includes('fetch')) {
          console.warn('Chat agent status check failed:', error.message);
        }
      }
    }
    */
  };

  useEffect(() => {
    if (isOpen && session) {
      startPolling();
      // Check agent status more frequently when chat is open
      checkAgentAvailability();
    } else {
      stopPolling();
    }

    return () => stopPolling();
  }, [isOpen, session]);

  useEffect(() => {
    // Start checking agent availability when component mounts
    startAgentPolling();

    // Add visibility change listener for immediate updates when user returns to page
    const handleVisibilityChange = () => {
      if (!document.hidden) {
        console.log('🔄 Page became visible, checking agent status...');
        checkAgentAvailability();
      }
    };

    const handleFocus = () => {
      console.log('🔄 Window focused, checking agent status...');
      checkAgentAvailability();
    };

    document.addEventListener('visibilitychange', handleVisibilityChange);
    window.addEventListener('focus', handleFocus);

    return () => {
      stopAgentPolling();
      document.removeEventListener('visibilitychange', handleVisibilityChange);
      window.removeEventListener('focus', handleFocus);
    };
  }, []);

  const createSession = async () => {
    console.log('createSession called', { user, guestEmail, guestName });
    setIsLoading(true);
    try {
      let sessionData;

      if (user) {
        // Logged-in user session
        sessionData = {
          action: 'create',
          user_id: user.id,
        };
      } else {
        // Guest user session
        if (!guestEmail || !guestName) {
          console.log('Missing guest details, showing form');
          setShowEmailForm(true);
          setIsLoading(false);
          return;
        }
        sessionData = {
          action: 'create_guest',
          guest_email: guestEmail,
          guest_name: guestName,
        };
      }

      console.log('Sending session data:', sessionData);
      const response = await fetch(ApiConfig.endpoints.chat.sessions, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(sessionData),
      });

      console.log('Response status:', response.status);
      const data = await response.json();
      console.log('Response data:', data);

      if (data.success) {
        if (data.data.no_admin_available) {
          // No admin available, show offline message form
          setShowOfflineForm(true);
          setShowEmailForm(false);
          toast({
            title: 'No agents available',
            description: data.data.message,
            variant: 'default',
          });
        } else {
          setSession(data.data);
          setShowEmailForm(false);
          fetchMessages();
        }
      } else {
        throw new Error(data.error);
      }
    } catch (error) {
      console.error('Create session error:', error);
      toast({
        title: 'Error',
        description: 'Failed to start chat session. Please try again.',
        variant: 'destructive',
      });
    } finally {
      setIsLoading(false);
    }
  };

  const fetchMessages = async (isPolling = false) => {
    if (!session) return;

    try {
      const response = await fetch(
        `${ApiConfig.endpoints.chat.messages}?session_id=${session.id}&limit=50`,
        {
          method: 'GET',
          headers: {
            'Content-Type': 'application/json',
          },
        }
      );

      const data = await response.json();
      if (data.success) {
        const newMessages = data.data.messages;
        const newMessageCount = newMessages.length;

        // Check for new messages and play notification sound
        if (isPolling && newMessageCount > lastMessageCount && lastMessageCount > 0) {
          // Check if the new message is from admin
          const latestMessage = newMessages[newMessages.length - 1];
          if (latestMessage && latestMessage.sender_type === 'admin') {
            playNotificationSound();

            // Show toast notification if chat is minimized or closed
            if (isMinimized || !isOpen) {
              toast({
                title: 'New Message',
                description: `${latestMessage.sender_name}: ${latestMessage.message.substring(0, 50)}${latestMessage.message.length > 50 ? '...' : ''}`,
                duration: 5000,
              });
            }
          }
        }

        setMessages(newMessages);
        setLastMessageCount(newMessageCount);
        setUnreadCount(data.data.unread_counts.from_admin);

        // Check if session was closed by admin
        if (data.data.session_status === 'closed' && !sessionEnded) {
          setSessionEnded(true);
          setShowRatingForm(true);
          stopPolling();
        }

        // Mark messages as read if chat is open and not minimized
        if (isOpen && !isMinimized && data.data.unread_counts.from_admin > 0) {
          markMessagesAsRead();
        }
      }
    } catch (error) {
      if (!isPolling) {
        console.error('Fetch messages error:', {
          error: error,
          message: error instanceof Error ? error.message : 'Unknown error',
          stack: error instanceof Error ? error.stack : undefined,
          sessionId: session?.id,
          endpoint: `${ApiConfig.endpoints.chat.messages}?session_id=${session?.id}&limit=50`
        });
      }
    }
  };

  const markMessagesAsRead = async () => {
    if (!session) return;

    try {
      await fetch(ApiConfig.endpoints.chat.messages, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          action: 'mark_read',
          session_id: session.id,
          reader_type: 'user',
        }),
      });
      setUnreadCount(0);
    } catch (error) {
      console.error('Mark messages as read error:', error);
    }
  };

  const sendMessage = async () => {
    if (!session || !newMessage.trim() || isSending) return;

    setIsSending(true);
    try {
      const messageData = {
        action: 'send',
        session_id: session.id,
        sender_type: 'user',
        message: newMessage.trim(),
      };

      if (user) {
        messageData.sender_id = user.id;
      } else {
        messageData.sender_id = 'guest';
        messageData.guest_email = guestEmail;
        messageData.guest_name = guestName;
      }

      const response = await fetch(ApiConfig.endpoints.chat.messages, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(messageData),
      });

      const data = await response.json();
      if (data.success) {
        setNewMessage('');
        fetchMessages();
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

  const handleOpen = () => {
    console.log('Chat button clicked', { user, session, guestEmail, guestName });
    setIsOpen(true);
    setIsMinimized(false);
    if (!session) {
      if (!user && (!guestEmail || !guestName)) {
        console.log('Showing email form for guest user');
        setShowEmailForm(true);
      } else {
        console.log('Creating session');
        createSession();
      }
    } else {
      console.log('Session exists, fetching messages');
      fetchMessages();
      markMessagesAsRead();
    }
  };

  const handleMinimize = () => {
    setIsMinimized(true);
  };

  const handleMaximize = () => {
    setIsMinimized(false);
    markMessagesAsRead();
  };

  const handleClose = () => {
    setIsOpen(false);
    setIsMinimized(false);
    setShowRatingForm(false);
    setSessionEnded(false);
    stopPolling();
  };

  const submitRating = async () => {
    if (!session || rating === 0) return;

    setIsSubmittingRating(true);
    try {
      const response = await fetch(ApiConfig.endpoints.chat.sessions, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          action: 'rate',
          session_id: session.id,
          rating: rating,
          feedback: feedback.trim(),
          user_email: user?.email || guestEmail,
        }),
      });

      const data = await response.json();
      if (data.success) {
        toast({
          title: 'Thank You!',
          description: 'Your feedback has been submitted successfully.',
        });
        setShowRatingForm(false);

        // Send email with chat transcript
        await sendChatTranscript();
      } else {
        throw new Error(data.error);
      }
    } catch (error) {
      console.error('Submit rating error:', error);
      toast({
        title: 'Error',
        description: 'Failed to submit rating. Please try again.',
        variant: 'destructive',
      });
    } finally {
      setIsSubmittingRating(false);
    }
  };

  const sendChatTranscript = async () => {
    if (!session) return;

    try {
      const response = await fetch(ApiConfig.endpoints.chat.sessions, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          action: 'send_transcript',
          session_id: session.id,
          user_email: user?.email || guestEmail,
          user_name: user?.username || guestName,
        }),
      });

      const data = await response.json();
      if (data.success) {
        toast({
          title: 'Chat Transcript Sent',
          description: 'A copy of your chat has been sent to your email.',
        });
      }
    } catch (error) {
      console.error('Send transcript error:', error);
    }
  };

  const submitOfflineMessage = async () => {
    if (!offlineMessage.trim() || !guestEmail || !guestName) return;

    setIsSubmittingOffline(true);
    try {
      const response = await fetch(ApiConfig.endpoints.chat.offlineMessages, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          action: 'submit',
          guest_name: guestName,
          guest_email: guestEmail,
          subject: offlineSubject || 'Chat Support Request',
          message: offlineMessage.trim(),
        }),
      });

      const data = await response.json();
      if (data.success) {
        toast({
          title: 'Message Sent',
          description: 'Your message has been submitted. We will contact you soon.',
        });
        setShowOfflineForm(false);
        setOfflineMessage('');
        setOfflineSubject('');
        handleClose();
      } else {
        throw new Error(data.message);
      }
    } catch (error) {
      console.error('Submit offline message error:', error);
      toast({
        title: 'Error',
        description: error instanceof Error ? error.message : 'Failed to submit message. Please try again.',
        variant: 'destructive',
      });
    } finally {
      setIsSubmittingOffline(false);
    }
  };

  const getStatusBadge = () => {
    if (!session) return null;
    
    switch (session.status) {
      case 'waiting':
        return <Badge variant="secondary" className="bg-yellow-500/10 text-yellow-500">Waiting for admin</Badge>;
      case 'active':
        return <Badge variant="secondary" className="bg-green-500/10 text-green-500">Active</Badge>;
      case 'closed':
        return <Badge variant="secondary" className="bg-gray-500/10 text-gray-500">Closed</Badge>;
      default:
        return null;
    }
  };

  // Chat is available for both logged-in and guest users

  // Chat button when closed
  if (!isOpen) {
    return (
      <div className="fixed bottom-6 left-6 z-[60]">
        <Button
          onClick={handleOpen}
          className="bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold hover:from-blue-700 hover:to-blue-800 transition-all duration-200 shadow-lg hover:shadow-xl border-0 rounded-xl px-5 py-3 h-auto"
          size="lg"
        >
          <div className="flex items-center space-x-2">
            <div className="relative">
              <MessageCircle className="h-5 w-5" />
              {/* Agent status indicator */}
              <div className={`absolute -top-1 -right-1 w-3 h-3 rounded-full border-2 border-white ${
                agentsOnline > 0 ? 'bg-green-500' : 'bg-gray-500'
              }`}></div>
            </div>
            <div className="flex flex-col items-start">
              <span className="text-sm font-semibold">Live Support</span>
              <span className="text-xs opacity-90">
                {agentsOnline > 0 ? `${agentsOnline} agent${agentsOnline > 1 ? 's' : ''} online` : 'Currently offline'}
              </span>
            </div>
          </div>
          {unreadCount > 0 && (
            <Badge className="absolute -top-1 -right-1 bg-red-500 text-white text-xs px-1.5 py-0.5">
              {unreadCount}
            </Badge>
          )}
        </Button>
      </div>
    );
  }

  return (
    <div className="fixed bottom-6 left-6 z-[60] w-80 max-w-[90vw]">
      <Card className="bg-gray-800 border border-gray-700 shadow-xl rounded-lg overflow-hidden">
        <CardHeader className="pb-3 bg-blue-600 text-white">
          <div className="flex items-center justify-between">
            <CardTitle className="text-sm flex items-center font-semibold">
              <div className="relative mr-2">
                <MessageCircle className="h-4 w-4" />
                {/* Agent status indicator */}
                <div className={`absolute -top-1 -right-1 w-2.5 h-2.5 rounded-full border border-white ${
                  agentsOnline > 0 ? 'bg-green-500' : 'bg-gray-500'
                }`}></div>
              </div>
              <div className="flex flex-col">
                <div className="flex items-center space-x-2">
                  <span>Live Support</span>
                  <span className="text-xs opacity-75">
                    ({agentsOnline > 0 ? `${agentsOnline} online` : 'offline'})
                  </span>
                </div>
                {session?.admin_username && (
                  <span className="text-xs font-normal opacity-90">
                    with {session.admin_username}
                  </span>
                )}
              </div>
            </CardTitle>
            <div className="flex items-center gap-1">
              {getStatusBadge()}
              <Button
                size="sm"
                variant="ghost"
                onClick={isMinimized ? handleMaximize : handleMinimize}
                className="h-6 w-6 p-0 text-white/70 hover:text-white hover:bg-white/10"
              >
                {isMinimized ? <Maximize2 className="h-3 w-3" /> : <Minimize2 className="h-3 w-3" />}
              </Button>
              <Button
                size="sm"
                variant="ghost"
                onClick={handleClose}
                className="h-6 w-6 p-0 text-white/70 hover:text-white hover:bg-white/10"
              >
                <X className="h-3 w-3" />
              </Button>
            </div>
          </div>
        </CardHeader>
        
        {!isMinimized && (
          <CardContent className="p-0">
            {/* Rating Form */}
            {showRatingForm && sessionEnded && (
              <div className="p-6 bg-gradient-to-br from-gray-700 to-gray-800 border-b border-gray-600">
                <div className="text-center mb-4">
                  <CheckCircle2 className="h-12 w-12 mx-auto text-green-500 mb-3" />
                  <h3 className="text-lg font-bold text-white mb-2">Chat Session Ended</h3>
                  <p className="text-sm text-gray-300">How was your experience with our support?</p>
                </div>

                <div className="space-y-4">
                  {/* Star Rating */}
                  <div className="flex justify-center space-x-1 mb-4">
                    {[1, 2, 3, 4, 5].map((star) => (
                      <button
                        key={star}
                        onClick={() => setRating(star)}
                        className="transition-all duration-200 hover:scale-110"
                      >
                        {star <= rating ? (
                          <Star className="h-8 w-8 text-yellow-400 fill-current" />
                        ) : (
                          <StarOff className="h-8 w-8 text-gray-300 hover:text-yellow-400" />
                        )}
                      </button>
                    ))}
                  </div>

                  {/* Feedback Text */}
                  <div>
                    <Label htmlFor="feedback" className="text-gray-200 text-sm font-medium">
                      Additional Feedback (Optional)
                    </Label>
                    <Textarea
                      id="feedback"
                      value={feedback}
                      onChange={(e) => setFeedback(e.target.value)}
                      placeholder="Tell us about your experience..."
                      className="mt-1 bg-gray-700 border-gray-600 text-white placeholder:text-gray-400 resize-none"
                      rows={3}
                      maxLength={500}
                    />
                  </div>

                  {/* Submit Buttons */}
                  <div className="flex gap-2 pt-2">
                    <Button
                      onClick={() => setShowRatingForm(false)}
                      variant="outline"
                      className="flex-1 border-gray-600 text-gray-300 hover:bg-gray-700"
                    >
                      Skip
                    </Button>
                    <Button
                      onClick={submitRating}
                      disabled={rating === 0 || isSubmittingRating}
                      className="flex-1 bg-gradient-to-r from-blue-600 to-purple-600 text-white hover:from-blue-700 hover:to-purple-700"
                    >
                      {isSubmittingRating ? (
                        <>
                          <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                          Submitting...
                        </>
                      ) : (
                        <>
                          <Heart className="h-4 w-4 mr-2" />
                          Submit Rating
                        </>
                      )}
                    </Button>
                  </div>
                </div>
              </div>
            )}

            {/* Guest Email Form */}
            {showEmailForm && !user && !showRatingForm && (
              <div className="p-4 bg-gray-700 border-b border-gray-600">
                <div className="space-y-3">
                  <div className="text-center mb-3">
                    <Mail className="h-6 w-6 mx-auto text-blue-400 mb-2" />
                    <p className="text-sm text-white font-medium">Please provide your details to start chatting</p>
                  </div>
                  <div>
                    <Label htmlFor="guest-name" className="text-gray-200 text-xs">Name</Label>
                    <Input
                      id="guest-name"
                      value={guestName}
                      onChange={(e) => setGuestName(e.target.value)}
                      placeholder="Your name"
                      className="mt-1"
                      maxLength={100}
                    />
                  </div>
                  <div>
                    <Label htmlFor="guest-email" className="text-gray-200 text-xs">Email</Label>
                    <Input
                      id="guest-email"
                      type="email"
                      value={guestEmail}
                      onChange={(e) => setGuestEmail(e.target.value)}
                      placeholder="your.email@example.com"
                      className="mt-1"
                      maxLength={255}
                    />
                  </div>
                  <div className="flex gap-2">
                    <Button
                      onClick={() => setShowEmailForm(false)}
                      variant="outline"
                      size="sm"
                      className="flex-1 border-gray-600 text-gray-300 hover:bg-gray-700"
                    >
                      Cancel
                    </Button>
                    <Button
                      onClick={createSession}
                      disabled={!guestName.trim() || !guestEmail.trim() || isLoading}
                      size="sm"
                      className="flex-1 bg-blue-600 hover:bg-blue-700"
                    >
                      {isLoading ? (
                        <Loader2 className="h-4 w-4 animate-spin" />
                      ) : (
                        'Start Chat'
                      )}
                    </Button>
                  </div>
                </div>
              </div>
            )}

            {/* Offline Message Form */}
            {showOfflineForm && !user && !showRatingForm && (
              <div className="p-4 bg-gray-700 border-b border-gray-600">
                <div className="space-y-3">
                  <div className="text-center mb-3">
                    <MessageCircle className="h-6 w-6 mx-auto text-orange-400 mb-2" />
                    <p className="text-sm text-white font-medium">No agents are currently online</p>
                    <p className="text-xs text-gray-300">Leave a message and we'll get back to you soon</p>
                  </div>
                  <div>
                    <Label htmlFor="offline-subject" className="text-gray-200 text-xs">Subject (Optional)</Label>
                    <Input
                      id="offline-subject"
                      type="text"
                      placeholder="Subject"
                      value={offlineSubject}
                      onChange={(e) => setOfflineSubject(e.target.value)}
                      className="bg-gray-800 border-gray-600 text-white placeholder-gray-400 text-sm"
                      maxLength={255}
                    />
                  </div>
                  <div>
                    <Label htmlFor="offline-message" className="text-gray-200 text-xs">Message</Label>
                    <Textarea
                      id="offline-message"
                      placeholder="Type your message here..."
                      value={offlineMessage}
                      onChange={(e) => setOfflineMessage(e.target.value)}
                      className="bg-gray-800 border-gray-600 text-white placeholder-gray-400 text-sm min-h-[80px]"
                      rows={3}
                    />
                  </div>
                  <div className="flex gap-2">
                    <Button
                      onClick={() => {
                        setShowOfflineForm(false);
                        setOfflineMessage('');
                        setOfflineSubject('');
                      }}
                      variant="outline"
                      size="sm"
                      className="flex-1 border-gray-600 text-gray-300 hover:bg-gray-700"
                    >
                      Cancel
                    </Button>
                    <Button
                      onClick={submitOfflineMessage}
                      disabled={!offlineMessage.trim() || !guestEmail || !guestName || isSubmittingOffline}
                      size="sm"
                      className="flex-1 bg-orange-600 hover:bg-orange-700 text-white"
                    >
                      {isSubmittingOffline ? (
                        <>
                          <Loader2 className="h-3 w-3 mr-1 animate-spin" />
                          Sending...
                        </>
                      ) : (
                        <>
                          <Send className="h-3 w-3 mr-1" />
                          Send Message
                        </>
                      )}
                    </Button>
                  </div>
                </div>
              </div>
            )}

            {/* Messages */}
            <div className={`${showEmailForm && !user ? 'h-40' : showOfflineForm && !user ? 'h-40' : showRatingForm ? 'h-40' : 'h-80'} overflow-y-auto bg-gray-900 p-3`}>
              {isLoading ? (
                <div className="flex items-center justify-center h-full">
                  <div className="text-center">
                    <Loader2 className="h-6 w-6 animate-spin text-blue-600 mx-auto mb-2" />
                    <span className="text-sm text-gray-300">Connecting...</span>
                  </div>
                </div>
              ) : messages.length === 0 ? (
                <div className="flex flex-col items-center justify-center h-full text-center">
                  <MessageCircle className="h-8 w-8 text-gray-400 mx-auto mb-3" />
                  <h3 className="text-sm font-medium text-gray-300 mb-1">
                    {session?.status === 'waiting' ? 'Waiting for support...' : 'Start chatting!'}
                  </h3>
                  <p className="text-xs text-gray-400">
                    {session?.status === 'waiting'
                      ? 'An agent will join shortly'
                      : 'Type a message below'}
                  </p>
                </div>
              ) : (
                <div className="space-y-3">
                  {messages.map((message) => (
                    <div
                      key={message.id}
                      className={`flex ${message.sender_type === 'user' ? 'justify-end' : 'justify-start'}`}
                    >
                      <div
                        className={`max-w-[80%] rounded-lg p-3 ${
                          message.sender_type === 'user'
                            ? 'bg-blue-600 text-white'
                            : 'bg-gray-700 text-white border border-gray-600'
                        }`}
                      >
                        <div className="flex items-center mb-1">
                          {message.sender_type === 'user' ? (
                            <User className="h-3 w-3 mr-1" />
                          ) : (
                            <UserCheck className="h-3 w-3 mr-1" />
                          )}
                          <span className="text-xs font-medium">
                            {message.sender_name}
                          </span>
                        </div>
                        <p className="text-sm">{message.message}</p>
                        <div className="text-xs opacity-70 mt-1">
                          {new Date(message.created_at).toLocaleTimeString([], {
                            hour: '2-digit',
                            minute: '2-digit'
                          })}
                        </div>
                      </div>
                    </div>
                  ))}
                  <div ref={messagesEndRef} />
                </div>
              )}
            </div>
            
            {/* Message input */}
            {!showRatingForm && (
              <div className="p-3 bg-gray-800 border-t border-gray-600">
                <div className="flex gap-2">
                  <Input
                    value={newMessage}
                    onChange={(e) => setNewMessage(e.target.value)}
                    placeholder={session?.status === 'closed' ? 'Chat session has ended' : 'Type your message...'}
                    className="flex-1"
                    onKeyPress={(e) => e.key === 'Enter' && !e.shiftKey && sendMessage()}
                    disabled={isSending || session?.status === 'closed' || sessionEnded}
                    maxLength={2000}
                  />
                  <Button
                    onClick={sendMessage}
                    disabled={!newMessage.trim() || isSending || session?.status === 'closed' || sessionEnded}
                    size="sm"
                    className="bg-blue-600 hover:bg-blue-700"
                  >
                    {isSending ? (
                      <Loader2 className="h-4 w-4 animate-spin" />
                    ) : (
                      <Send className="h-4 w-4" />
                    )}
                  </Button>
                </div>

                {session?.status === 'closed' || sessionEnded ? (
                  <div className="mt-2 p-2 bg-red-900/20 border border-red-600 rounded text-red-300 text-xs">
                    Chat session has ended. Thank you for contacting us!
                  </div>
                ) : session?.status === 'waiting' ? (
                  <div className="mt-2 p-2 bg-yellow-900/20 border border-yellow-600 rounded text-yellow-300 text-xs">
                    Waiting for a support agent...
                  </div>
                ) : null}
              </div>
            )}
          </CardContent>
        )}
      </Card>
    </div>
  );
};

export default LiveChat;

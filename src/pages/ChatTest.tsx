import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { MessageCircle, Users, Clock, CheckCircle } from 'lucide-react';

const ChatTest: React.FC = () => {
  const openAdminChat = () => {
    window.open('/admin/live-chat', '_blank', 'width=1200,height=800');
  };

  const openUserSite = () => {
    window.open('/', '_blank', 'width=800,height=600');
  };

  return (
    <div className="min-h-screen bg-gray-900 text-white p-8">
      <div className="max-w-6xl mx-auto">
        <div className="text-center mb-8">
          <h1 className="text-4xl font-bold mb-4 bg-gradient-to-r from-blue-400 to-purple-500 bg-clip-text text-transparent">
            Live Chat System Test
          </h1>
          <p className="text-gray-300 text-lg">
            Test the advanced real-time chat functionality with agent status management
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
          {/* Admin Side */}
          <Card className="bg-gray-800 border-gray-700">
            <CardHeader>
              <CardTitle className="flex items-center text-blue-400">
                <Users className="h-6 w-6 mr-2" />
                Admin Dashboard
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-3">
                <div className="flex items-center space-x-2">
                  <CheckCircle className="h-5 w-5 text-green-500" />
                  <span>Agent status toggle (Available/Busy/Offline)</span>
                </div>
                <div className="flex items-center space-x-2">
                  <CheckCircle className="h-5 w-5 text-green-500" />
                  <span>Real-time chat sessions management</span>
                </div>
                <div className="flex items-center space-x-2">
                  <CheckCircle className="h-5 w-5 text-green-500" />
                  <span>Instant message notifications</span>
                </div>
                <div className="flex items-center space-x-2">
                  <CheckCircle className="h-5 w-5 text-green-500" />
                  <span>Session assignment and management</span>
                </div>
              </div>
              <Button 
                onClick={openAdminChat}
                className="w-full bg-blue-600 hover:bg-blue-700"
              >
                Open Admin Chat Dashboard
              </Button>
            </CardContent>
          </Card>

          {/* User Side */}
          <Card className="bg-gray-800 border-gray-700">
            <CardHeader>
              <CardTitle className="flex items-center text-green-400">
                <MessageCircle className="h-6 w-6 mr-2" />
                User Experience
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-3">
                <div className="flex items-center space-x-2">
                  <CheckCircle className="h-5 w-5 text-green-500" />
                  <span>Live agent availability status</span>
                </div>
                <div className="flex items-center space-x-2">
                  <CheckCircle className="h-5 w-5 text-green-500" />
                  <span>Real-time messaging with notifications</span>
                </div>
                <div className="flex items-center space-x-2">
                  <CheckCircle className="h-5 w-5 text-green-500" />
                  <span>Guest and authenticated user support</span>
                </div>
                <div className="flex items-center space-x-2">
                  <CheckCircle className="h-5 w-5 text-green-500" />
                  <span>Offline message functionality</span>
                </div>
              </div>
              <Button 
                onClick={openUserSite}
                className="w-full bg-green-600 hover:bg-green-700"
              >
                Open User Site
              </Button>
            </CardContent>
          </Card>
        </div>

        {/* Features Overview */}
        <Card className="bg-gray-800 border-gray-700">
          <CardHeader>
            <CardTitle className="flex items-center text-purple-400">
              <Clock className="h-6 w-6 mr-2" />
              Advanced Features Implemented
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <div className="space-y-2">
                <h3 className="font-semibold text-blue-400">Real-time Status</h3>
                <ul className="text-sm text-gray-300 space-y-1">
                  <li>• Agent availability toggle</li>
                  <li>• Live status indicators</li>
                  <li>• Automatic status updates</li>
                  <li>• Online agent counter</li>
                </ul>
              </div>
              
              <div className="space-y-2">
                <h3 className="font-semibold text-green-400">Messaging</h3>
                <ul className="text-sm text-gray-300 space-y-1">
                  <li>• 2-second polling for real-time</li>
                  <li>• Notification sounds</li>
                  <li>• Toast notifications</li>
                  <li>• Message read receipts</li>
                </ul>
              </div>
              
              <div className="space-y-2">
                <h3 className="font-semibold text-purple-400">User Experience</h3>
                <ul className="text-sm text-gray-300 space-y-1">
                  <li>• Seamless chat interface</li>
                  <li>• Guest user support</li>
                  <li>• Session management</li>
                  <li>• Rating and feedback</li>
                </ul>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Testing Instructions */}
        <Card className="bg-gray-800 border-gray-700 mt-8">
          <CardHeader>
            <CardTitle className="text-yellow-400">Testing Instructions</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-4 text-gray-300">
              <div>
                <h4 className="font-semibold text-white mb-2">1. Set Agent Status</h4>
                <p>Open the Admin Dashboard and set your status to "Available" using the toggle buttons.</p>
              </div>
              
              <div>
                <h4 className="font-semibold text-white mb-2">2. Check User Side</h4>
                <p>Open the User Site and notice the "Live Support" button shows "1 agent online" with a green indicator.</p>
              </div>
              
              <div>
                <h4 className="font-semibold text-white mb-2">3. Start a Chat</h4>
                <p>Click the Live Support button on the user side and start a chat session (as guest or logged-in user).</p>
              </div>
              
              <div>
                <h4 className="font-semibold text-white mb-2">4. Test Real-time Messaging</h4>
                <p>Send messages from both sides and observe the real-time delivery with notification sounds.</p>
              </div>
              
              <div>
                <h4 className="font-semibold text-white mb-2">5. Test Status Changes</h4>
                <p>Change agent status to "Offline" and see how the user side updates to show "Currently offline".</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
};

export default ChatTest;

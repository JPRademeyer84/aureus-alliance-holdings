import React, { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { useSimpleTranslation as useTranslation, ST as T } from '@/components/SimpleTranslator';
import { 
  MessageCircle, 
  Mail, 
  Phone, 
  Clock,
  CheckCircle,
  AlertCircle,
  HelpCircle,
  Send,
  Plus,
  Search,
  Filter
} from 'lucide-react';
import ContactForm from '@/components/contact/ContactForm';
import ContactMessages from '@/components/contact/ContactMessages';

const SupportView: React.FC = () => {
  const { translate } = useTranslation();
  const [contactRefreshTrigger, setContactRefreshTrigger] = useState(0);
  const [activeTab, setActiveTab] = useState<'contact' | 'messages' | 'faq'>('contact');

  const handleContactMessageSent = () => {
    setContactRefreshTrigger(prev => prev + 1);
    setActiveTab('messages'); // Switch to messages tab after sending
  };

  const supportStats = [
    {
      title: translate('response_time', 'Response Time'),
      value: translate('less_than_2_hours', '< 2 hours'),
      icon: <Clock className="h-5 w-5" />,
      color: 'text-blue-400'
    },
    {
      title: translate('support_hours', 'Support Hours'),
      value: translate('24_7', '24/7'),
      icon: <MessageCircle className="h-5 w-5" />,
      color: 'text-green-400'
    },
    {
      title: translate('satisfaction_rate', 'Satisfaction Rate'),
      value: translate('98_percent', '98%'),
      icon: <CheckCircle className="h-5 w-5" />,
      color: 'text-purple-400'
    }
  ];

  const faqItems = [
    {
      question: 'How do I start investing?',
      answer: 'Browse our investment packages, select one that fits your budget and goals, then follow the purchase process. You\'ll need to connect a wallet for transactions.'
    },
    {
      question: 'When will I receive dividends?',
      answer: 'Dividends are paid quarterly starting from the date specified in your investment package. You\'ll receive notifications before each payment.'
    },
    {
      question: 'Can I withdraw my investment early?',
      answer: 'Early withdrawal terms depend on your specific investment package. Please contact support for details about your particular investment.'
    },
    {
      question: 'How are my investments secured?',
      answer: 'All investments are backed by our diversified portfolio and smart contract technology. We maintain strict security protocols and regular audits.'
    },
    {
      question: 'What payment methods do you accept?',
      answer: 'We accept various cryptocurrencies including USDT, USDC, and other major tokens. Connect your wallet to see available payment options.'
    }
  ];

  const handleLiveChat = () => {
    // Open live chat - you can integrate with your live chat service here
    // For now, we'll open a new window or redirect to chat service
    window.open('https://tawk.to/chat', '_blank');
  };

  const handleEmailSupport = () => {
    // Open email client with company email
    const companyEmail = 'support@aureusangel.com';
    const subject = 'Support Request';
    const body = 'Hello, I need assistance with...';
    window.location.href = `mailto:${companyEmail}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
  };

  const handlePhoneSupport = () => {
    // Open WhatsApp with the company number
    const whatsappNumber = '+27783699799';
    const message = 'Hello, I need support with my account.';
    window.open(`https://wa.me/${whatsappNumber.replace('+', '')}?text=${encodeURIComponent(message)}`, '_blank');
  };

  const contactMethods = [
    {
      title: 'Live Chat',
      description: 'Get instant help from our support team',
      icon: <MessageCircle className="h-6 w-6" />,
      action: 'Start Chat',
      available: true,
      onClick: handleLiveChat
    },
    {
      title: 'Email Support',
      description: 'Send us a detailed message',
      icon: <Mail className="h-6 w-6" />,
      action: 'Send Email',
      available: true,
      onClick: handleEmailSupport
    },
    {
      title: 'Phone Support',
      description: 'Speak directly with our team',
      icon: <Phone className="h-6 w-6" />,
      action: 'Call Now',
      available: true, // Changed to true since it opens WhatsApp
      onClick: handlePhoneSupport
    }
  ];

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold text-white">
            <T k="contact_support" fallback="Contact Support" />
          </h2>
          <p className="text-gray-400">
            <T k="get_help_investments_account" fallback="Get help with your investments and account" />
          </p>
        </div>
      </div>

      {/* Support Stats */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        {supportStats.map((stat, index) => (
          <Card key={index} className="bg-gray-800 border-gray-700">
            <CardContent className="p-4">
              <div className="flex items-center">
                <div className={`${stat.color}`}>
                  {stat.icon}
                </div>
                <div className="ml-4">
                  <p className="text-sm font-medium text-gray-400">{stat.title}</p>
                  <p className="text-xl font-bold text-white">{stat.value}</p>
                </div>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      {/* Contact Methods */}
      <Card className="bg-gray-800 border-gray-700">
        <CardHeader>
          <CardTitle className="text-white">How would you like to contact us?</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            {contactMethods.map((method, index) => (
              <div key={index} className="bg-gray-700 rounded-lg p-4 border border-gray-600">
                <div className="flex items-center justify-between mb-3">
                  <div className="text-gold">
                    {method.icon}
                  </div>
                  {method.title === 'Phone Support' && (
                    <Badge variant="outline" className="text-xs border-green-400 text-green-400">
                      WhatsApp
                    </Badge>
                  )}
                </div>
                <h3 className="font-semibold text-white mb-2">{method.title}</h3>
                <p className="text-sm text-gray-400 mb-4">{method.description}</p>
                <Button
                  className={`w-full ${method.available ? 'bg-gold hover:bg-gold/90 text-black' : 'bg-gray-600 text-gray-400 cursor-not-allowed'}`}
                  disabled={!method.available}
                  size="sm"
                  onClick={method.onClick}
                >
                  {method.action}
                </Button>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>

      {/* Tabs */}
      <div className="flex space-x-1 bg-gray-800 p-1 rounded-lg border border-gray-700">
        {[
          { id: 'contact', label: translate('send_message', 'Send Message'), icon: <Send className="h-4 w-4" /> },
          { id: 'messages', label: translate('my_messages', 'My Messages'), icon: <Mail className="h-4 w-4" /> },
          { id: 'faq', label: translate('faq', 'FAQ'), icon: <HelpCircle className="h-4 w-4" /> }
        ].map((tab) => (
          <Button
            key={tab.id}
            onClick={() => setActiveTab(tab.id as any)}
            variant={activeTab === tab.id ? "secondary" : "ghost"}
            className={`flex-1 ${
              activeTab === tab.id
                ? 'bg-gold/10 text-gold border-gold/30'
                : 'text-gray-400 hover:text-white hover:bg-gray-700'
            }`}
          >
            {tab.icon}
            <span className="ml-2">{tab.label}</span>
          </Button>
        ))}
      </div>

      {/* Tab Content */}
      {activeTab === 'contact' && (
        <div className="grid grid-cols-1 gap-8">
          <ContactForm onMessageSent={handleContactMessageSent} />
        </div>
      )}

      {activeTab === 'messages' && (
        <div className="grid grid-cols-1 gap-8">
          <ContactMessages refreshTrigger={contactRefreshTrigger} />
        </div>
      )}

      {activeTab === 'faq' && (
        <Card className="bg-gray-800 border-gray-700">
          <CardHeader>
            <CardTitle className="text-white">
              <T k="frequently_asked_questions" fallback="Frequently Asked Questions" />
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              {faqItems.map((item, index) => (
                <div key={index} className="border-b border-gray-700 pb-4 last:border-b-0">
                  <h3 className="font-semibold text-white mb-2 flex items-center">
                    <HelpCircle className="h-4 w-4 text-gold mr-2" />
                    {item.question}
                  </h3>
                  <p className="text-gray-400 text-sm leading-relaxed pl-6">
                    {item.answer}
                  </p>
                </div>
              ))}
            </div>
            
            <div className="mt-6 p-4 bg-gray-700 rounded-lg border border-gray-600">
              <div className="flex items-center">
                <AlertCircle className="h-5 w-5 text-yellow-400 mr-3" />
                <div>
                  <p className="text-white font-medium">
                    <T k="still_need_help" fallback="Still need help?" />
                  </p>
                  <p className="text-sm text-gray-400">
                    <T k="cant_find_looking_for" fallback="Can't find what you're looking for? Send us a message and we'll get back to you quickly." />
                  </p>
                </div>
              </div>
              <Button
                onClick={() => setActiveTab('contact')}
                className="mt-3 bg-gold hover:bg-gold/90 text-black"
                size="sm"
              >
                <Plus className="h-4 w-4 mr-2" />
                <T k="contact_support" fallback="Contact Support" />
              </Button>
            </div>
          </CardContent>
        </Card>
      )}
    </div>
  );
};

export default SupportView;

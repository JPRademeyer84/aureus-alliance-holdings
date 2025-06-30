import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { useToast } from '@/hooks/use-toast';
import { useUser } from '@/contexts/UserContext';
import {
  Share2,
  Copy,
  Download,
  Image,
  Video,
  FileText,
  Facebook,
  Twitter,
  Instagram,
  Linkedin,
  MessageSquare,
  Mail,
  Globe,
  Smartphone,
  TrendingUp,
  Users,
  DollarSign,
  Star,
  Zap,
  Target,
  Gift
} from 'lucide-react';
import ApiConfig from '@/config/api';

interface MarketingTemplate {
  id: string;
  type: 'post' | 'story' | 'email' | 'message';
  platform: string;
  title: string;
  content: string;
  hashtags?: string[];
  image?: string;
  category: 'investment' | 'nft' | 'affiliate' | 'general';
}

interface MarketingAsset {
  id: string;
  type: 'image' | 'video' | 'banner' | 'logo';
  title: string;
  description: string;
  url: string;
  size: string;
  format: string;
}

const SocialMediaTools: React.FC = () => {
  const { user } = useUser();
  const { toast } = useToast();
  const [selectedTemplate, setSelectedTemplate] = useState<MarketingTemplate | null>(null);
  const [customMessage, setCustomMessage] = useState('');
  const [referralLink, setReferralLink] = useState(`https://aureuscapital.com/${user?.username || 'referral'}`);
  const [generatedContent, setGeneratedContent] = useState('');
  const [isGenerating, setIsGenerating] = useState(false);
  const [marketingAssets, setMarketingAssets] = useState<MarketingAsset[]>([]);
  const [showInstructions, setShowInstructions] = useState<string | null>(null);

  const marketingTemplates: MarketingTemplate[] = [
    {
      id: '1',
      type: 'post',
      platform: 'Facebook',
      title: 'Investment Opportunity Post',
      content: `🚀 Exciting Investment Opportunity Alert! 🚀

Join me in the Aureus Alliance - where smart investors are building wealth through strategic NFT investments!

✅ $5 NFT Packs with 200,000 total supply
✅ 3-Level Commission Structure (12% + 5% + 3%)
✅ USDT + NFT Bonuses on every level
✅ Proven track record and transparent operations

Ready to secure your financial future? 

👇 Get started with my referral link:
${referralLink}

#Investment #NFT #CryptoInvestment #WealthBuilding #PassiveIncome #AureusAlliance`,
      hashtags: ['Investment', 'NFT', 'CryptoInvestment', 'WealthBuilding', 'PassiveIncome'],
      category: 'investment'
    },
    {
      id: '2',
      type: 'story',
      platform: 'Instagram',
      title: 'Success Story',
      content: `💰 Just earned another commission from my Aureus Alliance network!

This is what happens when you combine:
• Smart investment strategy
• Strong referral network  
• Consistent action

Swipe up to join my team! 👆

${referralLink}`,
      category: 'affiliate'
    },
    {
      id: '3',
      type: 'email',
      platform: 'Email',
      title: 'Personal Invitation Email',
      content: `Subject: Exclusive Investment Opportunity - Limited Time

Hi [Name],

I hope this email finds you well! I wanted to share an exciting participation opportunity that I've been involved with - the Aureus Alliance.

Here's what makes this special:

🎯 Low Entry Point: Start with just $5 NFT packs
💎 High Potential: 200,000 limited supply creates scarcity value
💰 Multiple Income Streams: Direct participation rewards + referral commissions
🌍 Global Community: Join participants from around the world

As someone I trust and respect, I wanted to give you first access to this opportunity before it becomes more widely known.

The commission structure is particularly attractive:
- Level 1: 12% USDT + 12% NFT bonus
- Level 2: 5% USDT + 5% NFT bonus  
- Level 3: 3% USDT + 3% NFT bonus

If you're interested in learning more or have any questions, I'm here to help guide you through the process.

You can get started here: ${referralLink}

Best regards,
${user?.full_name || user?.username}

P.S. The early bird gets the worm - limited supply means this opportunity won't last forever!`,
      category: 'investment'
    },
    {
      id: '4',
      type: 'message',
      platform: 'WhatsApp',
      title: 'Quick WhatsApp Message',
      content: `Hey! 👋

I've been making some great returns with this new investment platform called Aureus Alliance. 

It's perfect for people like us who want to:
✅ Start small ($5 minimum)
✅ Earn passive income
✅ Build a referral network

Want to check it out? Here's my link:
${referralLink}

Let me know if you have any questions! 😊`,
      category: 'general'
    },
    {
      id: '5',
      type: 'post',
      platform: 'LinkedIn',
      title: 'Professional Investment Post',
      content: `Professional Investment Opportunity: Aureus Alliance

As a forward-thinking professional, I'm always looking for innovative investment opportunities that align with the future of finance.

The Aureus Alliance represents a unique convergence of:
• NFT technology and scarcity economics
• Multi-level commission structures
• Transparent blockchain operations
• Global accessibility

Key Investment Highlights:
→ $5 entry point makes it accessible to all investors
→ Limited supply of 200,000 NFT packs creates inherent value
→ Three-tier commission system rewards network building
→ USDT payments ensure stable, liquid returns

For serious investors looking to diversify into the NFT space while building passive income streams, this presents a compelling opportunity.

Connect with me to learn more: ${referralLink}

#InvestmentOpportunity #NFT #Blockchain #PassiveIncome #ProfessionalInvesting`,
      category: 'investment'
    }
  ];

  // Fetch marketing assets from admin
  const fetchMarketingAssets = async () => {
    try {
      const response = await fetch(`${ApiConfig.baseUrl}/admin/marketing-assets.php`, {
        credentials: 'include'
      });

      if (response.ok) {
        const data = await response.json();
        if (data.success) {
          setMarketingAssets(data.assets || []);
        } else {
          setMarketingAssets([]);
        }
      } else {
        setMarketingAssets([]);
      }
    } catch (error) {
      console.error('Failed to fetch marketing assets:', error);
      setMarketingAssets([]);
    }
  };

  const socialPlatforms = [
    {
      name: 'Facebook',
      icon: Facebook,
      color: 'bg-blue-600',
      shareUrl: 'https://www.facebook.com/intent/post?text=',
      type: 'text_with_url'
    },
    {
      name: 'X (Twitter)',
      icon: Twitter,
      color: 'bg-black',
      shareUrl: 'https://twitter.com/intent/tweet?text=',
      type: 'text_with_url'
    },
    {
      name: 'LinkedIn',
      icon: Linkedin,
      color: 'bg-blue-700',
      shareUrl: 'https://www.linkedin.com/feed/?shareActive=true&text=',
      type: 'text_with_url'
    },
    {
      name: 'WhatsApp',
      icon: MessageSquare,
      color: 'bg-green-600',
      shareUrl: 'https://wa.me/?text=',
      type: 'text_with_url'
    },
    {
      name: 'Telegram',
      icon: MessageSquare,
      color: 'bg-blue-500',
      shareUrl: 'https://t.me/share/url?url=',
      type: 'url_with_text'
    }
  ];

  const copyToClipboard = (text: string) => {
    navigator.clipboard.writeText(text).then(() => {
      toast({
        title: "✅ Copied!",
        description: "Content copied to clipboard - ready to paste!",
        duration: 3000,
      });
    }).catch(() => {
      // Fallback for older browsers
      const textArea = document.createElement('textarea');
      textArea.value = text;
      document.body.appendChild(textArea);
      textArea.select();
      document.execCommand('copy');
      document.body.removeChild(textArea);

      toast({
        title: "✅ Copied!",
        description: "Content copied to clipboard - ready to paste!",
        duration: 3000,
      });
    });
  };

  const shareToSocial = (platform: any, content: string) => {
    // Prepare content with referral link
    const fullContent = `${content}\n\n${referralLink}`;

    // For platforms that don't support pre-filled content, we'll copy to clipboard and show instructions
    const handleManualShare = (platformName: string, instructions: string) => {
      copyToClipboard(fullContent);
      toast({
        title: `${platformName} Sharing`,
        description: `Content copied to clipboard! ${instructions}`,
        duration: 5000,
      });
    };

    switch (platform.name) {
      case 'Facebook':
        // Facebook no longer supports pre-filled content due to policy changes
        copyToClipboard(fullContent);
        setShowInstructions('Facebook');
        setTimeout(() => {
          window.open('https://www.facebook.com/', '_blank', 'width=626,height=436,scrollbars=yes,resizable=yes');
        }, 2000);
        break;

      case 'X (Twitter)':
        // Twitter still supports pre-filled content
        let twitterContent = fullContent;
        if (twitterContent.length > 280) {
          const maxContentLength = 280 - referralLink.length - 10;
          twitterContent = `${content.substring(0, maxContentLength)}...\n\n${referralLink}`;
        }
        const encodedTwitterContent = encodeURIComponent(twitterContent);
        const twitterUrl = `https://twitter.com/intent/tweet?text=${encodedTwitterContent}`;
        window.open(twitterUrl, '_blank', 'width=550,height=420,scrollbars=yes,resizable=yes');
        toast({
          title: "Sharing to X (Twitter)",
          description: "Opening Twitter with your content...",
        });
        break;

      case 'LinkedIn':
        // LinkedIn has restricted pre-filled content, use copy method
        copyToClipboard(fullContent);
        setShowInstructions('LinkedIn');
        setTimeout(() => {
          window.open('https://www.linkedin.com/feed/', '_blank', 'width=520,height=570,scrollbars=yes,resizable=yes');
        }, 2000);
        break;

      case 'WhatsApp':
        // WhatsApp still works with pre-filled content
        const whatsappContent = encodeURIComponent(fullContent);
        const whatsappUrl = `https://wa.me/?text=${whatsappContent}`;
        window.open(whatsappUrl, '_blank', 'width=600,height=500,scrollbars=yes,resizable=yes');
        toast({
          title: "Sharing to WhatsApp",
          description: "Opening WhatsApp with your content...",
        });
        break;

      case 'Telegram':
        // Telegram still works with pre-filled content
        const encodedUrl = encodeURIComponent(referralLink);
        const encodedText = encodeURIComponent(content);
        const telegramUrl = `https://t.me/share/url?url=${encodedUrl}&text=${encodedText}`;
        window.open(telegramUrl, '_blank', 'width=600,height=500,scrollbars=yes,resizable=yes');
        toast({
          title: "Sharing to Telegram",
          description: "Opening Telegram with your content...",
        });
        break;

      default:
        handleManualShare(platform.name, "Please paste the content manually.");
    }

    // Debug log
    console.log(`Sharing to ${platform.name} with content:`, fullContent);
  };

  const downloadAsset = async (asset: MarketingAsset) => {
    try {
      // Show loading toast
      toast({
        title: "Download Starting",
        description: `Preparing ${asset.title} for download...`,
      });

      // Create download URL
      const downloadUrl = `/api/admin/marketing-assets-download.php?id=${asset.id}`;

      // Create a temporary link element and trigger download
      const link = document.createElement('a');
      link.href = downloadUrl;
      link.download = `${asset.title}.${asset.format}`;
      link.style.display = 'none';

      // Add to DOM, click, and remove
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);

      // Show success toast
      toast({
        title: "Download Started",
        description: `${asset.title} is being downloaded to your device`,
      });

    } catch (error) {
      console.error('Download failed:', error);
      toast({
        title: "Download Failed",
        description: `Failed to download ${asset.title}. Please try again.`,
        variant: "destructive"
      });
    }
  };

  const generateCustomLink = () => {
    const customLink = `https://aureuscapital.com/${user?.username}?utm_source=social&utm_medium=referral&utm_campaign=${Date.now()}`;
    setReferralLink(customLink);
    copyToClipboard(customLink);
  };

  const generateUniqueContent = async (template: MarketingTemplate) => {
    setIsGenerating(true);
    try {
      // Create variations of the template content
      const variations = {
        emojis: [
          ['🚀', '💎', '⭐', '🔥', '💰'],
          ['🌟', '💫', '✨', '🎯', '💸'],
          ['🏆', '🎉', '🔥', '💪', '🚀'],
          ['💎', '⚡', '🌟', '🎊', '💯']
        ],
        openings: [
          `🚀 Exciting Investment Opportunity Alert! 🚀`,
          `💎 Discover the Future of Investment! 💎`,
          `⭐ Limited Time Investment Opportunity! ⭐`,
          `🔥 Smart Investors Are Joining This! 🔥`,
          `💰 Build Wealth with Strategic Investing! 💰`
        ],
        callToActions: [
          `Ready to secure your financial future?`,
          `Want to join successful investors?`,
          `Ready to build generational wealth?`,
          `Looking for your next investment opportunity?`,
          `Ready to take control of your finances?`
        ],
        closings: [
          `Don't miss out on this opportunity!`,
          `Limited spots available - act now!`,
          `Join the smart money movement!`,
          `Your future self will thank you!`,
          `Start building wealth today!`
        ]
      };

      // Randomly select variations
      const randomEmojis = variations.emojis[Math.floor(Math.random() * variations.emojis.length)];
      const randomOpening = variations.openings[Math.floor(Math.random() * variations.openings.length)];
      const randomCTA = variations.callToActions[Math.floor(Math.random() * variations.callToActions.length)];
      const randomClosing = variations.closings[Math.floor(Math.random() * variations.closings.length)];

      // Generate personalized content
      let newContent = '';

      if (template.platform === 'Facebook' || template.platform === 'LinkedIn') {
        newContent = `${randomOpening}

Join me in the Aureus Alliance - where smart investors are building wealth through strategic NFT investments!

✅ $5 NFT Packs with 200,000 total supply
✅ 3-Level Commission Structure (12% + 5% + 3%)
✅ USDT + NFT Bonuses on every level
✅ Proven track record and transparent operations

${randomCTA}

👇 Get started with my referral link:
${referralLink}

${randomClosing}

#Investment #NFT #CryptoInvestment #WealthBuilding #PassiveIncome #AureusAlliance`;
      } else if (template.platform === 'Instagram') {
        newContent = `${randomEmojis.join(' ')} Just earned another commission from my Aureus Alliance network!

This is what happens when you combine:
• Smart investment strategy
• Strong referral network
• Consistent action

${randomCTA}

Link in bio: ${referralLink}

${randomClosing}`;
      } else if (template.platform === 'WhatsApp') {
        newContent = `Hey! 👋

I've been making some great returns with this new investment platform called Aureus Alliance.

It's perfect for people like us who want to:
✅ Start small ($5 minimum)
✅ Earn passive income
✅ Build a referral network

${randomCTA} Here's my link:
${referralLink}

${randomClosing} 😊`;
      } else {
        // Default variation
        newContent = template.content.replace(/🚀/g, randomEmojis[0])
                                   .replace(/Ready to secure your financial future\?/g, randomCTA)
                                   .replace(referralLink, referralLink);
      }

      setGeneratedContent(newContent);
      toast({
        title: "Content Generated!",
        description: "Unique content created for your campaign",
      });
    } catch (error) {
      console.error('Failed to generate content:', error);
      toast({
        title: "Generation Failed",
        description: "Could not generate unique content",
        variant: "destructive"
      });
    } finally {
      setIsGenerating(false);
    }
  };

  // Load marketing assets on component mount
  useEffect(() => {
    fetchMarketingAssets();
  }, []);

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h2 className="text-2xl font-bold text-white">Social Media Marketing Tools</h2>
        <p className="text-gray-400">Promote Aureus Alliance and grow your network with professional marketing materials</p>
      </div>

      {/* Marketing Performance - Will be populated with real data */}
      <Card className="bg-gray-800 border-gray-700">
        <CardHeader>
          <CardTitle className="text-white flex items-center gap-2">
            <TrendingUp className="h-5 w-5 text-gold" />
            Marketing Performance
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="text-center py-8">
            <Target className="h-12 w-12 text-gray-400 mx-auto mb-4" />
            <p className="text-gray-400 mb-2">Start sharing to see your marketing analytics</p>
            <p className="text-sm text-gray-500">Track clicks, conversions, and campaign performance here</p>
          </div>
        </CardContent>
      </Card>

      {/* Referral Link Generator */}
      <Card className="bg-gray-800 border-gray-700">
        <CardHeader>
          <CardTitle className="text-white flex items-center gap-2">
            <Globe className="h-5 w-5 text-gold" />
            Your Referral Link
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="flex items-center gap-2">
            <Input
              value={referralLink}
              readOnly
              className="bg-gray-700 border-gray-600 text-white font-mono"
            />
            <Button onClick={() => copyToClipboard(referralLink)} variant="outline">
              <Copy className="h-4 w-4" />
            </Button>
            <Button onClick={generateCustomLink} className="bg-gold-gradient text-black">
              <Zap className="h-4 w-4 mr-2" />
              Generate Tracking Link
            </Button>
          </div>
          <p className="text-sm text-gray-400">
            Share this link to earn commissions on all referrals. Custom tracking links help you measure campaign performance.
          </p>
        </CardContent>
      </Card>

      {/* Marketing Templates */}
      <Card className="bg-gray-800 border-gray-700">
        <CardHeader>
          <CardTitle className="text-white flex items-center gap-2">
            <FileText className="h-5 w-5 text-gold" />
            Marketing Templates
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {marketingTemplates.map((template) => (
              <div key={template.id} className="bg-gray-700/50 rounded-lg p-4">
                <div className="flex items-start justify-between mb-3">
                  <div>
                    <h3 className="text-white font-semibold">{template.title}</h3>
                    <div className="flex items-center gap-2 mt-1">
                      <Badge className="bg-blue-500/20 text-blue-400 text-xs">
                        {template.platform}
                      </Badge>
                      <Badge className="bg-purple-500/20 text-purple-400 text-xs">
                        {template.type}
                      </Badge>
                    </div>
                  </div>
                </div>

                <div className="bg-gray-800 rounded p-3 mb-3 max-h-32 overflow-y-auto">
                  <p className="text-gray-300 text-sm whitespace-pre-line">
                    {template.content.substring(0, 200)}...
                  </p>
                </div>

                <div className="flex items-center gap-2">
                  <Button
                    size="sm"
                    onClick={() => generateUniqueContent(template)}
                    disabled={isGenerating}
                    className="bg-blue-600 hover:bg-blue-700 text-white"
                  >
                    <Zap className="h-4 w-4 mr-1" />
                    {isGenerating ? 'Generating...' : 'Generate'}
                  </Button>
                  <Button
                    size="sm"
                    onClick={() => copyToClipboard(template.content)}
                    className="bg-gold-gradient text-black"
                  >
                    <Copy className="h-4 w-4 mr-1" />
                    Copy
                  </Button>
                  <Button
                    size="sm"
                    variant="outline"
                    onClick={() => setSelectedTemplate(template)}
                  >
                    <FileText className="h-4 w-4 mr-1" />
                    Edit
                  </Button>
                </div>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>

      {/* Generated Content */}
      {generatedContent && (
        <Card className="bg-gray-800 border-gray-700">
          <CardHeader>
            <CardTitle className="text-white flex items-center gap-2">
              <Zap className="h-5 w-5 text-gold" />
              Generated Content
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="bg-gray-700 rounded-lg p-4 mb-4">
              <pre className="text-gray-300 text-sm whitespace-pre-wrap font-sans">
                {generatedContent}
              </pre>
            </div>
            <div className="flex items-center gap-2">
              <Button
                onClick={() => copyToClipboard(generatedContent)}
                className="bg-gold-gradient text-black"
              >
                <Copy className="h-4 w-4 mr-2" />
                Copy Generated Content
              </Button>
              <Button
                variant="outline"
                onClick={() => setCustomMessage(generatedContent)}
              >
                Use in Quick Share
              </Button>
              <Button
                variant="outline"
                onClick={() => setGeneratedContent('')}
              >
                Clear
              </Button>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Quick Share Buttons */}
      <Card className="bg-gray-800 border-gray-700">
        <CardHeader>
          <CardTitle className="text-white flex items-center gap-2">
            <Share2 className="h-5 w-5 text-gold" />
            Quick Share
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            <div>
              <label className="text-sm text-gray-400 mb-2 block">Custom Message (Optional)</label>
              <Textarea
                placeholder="Write your custom message here or use generated content..."
                value={customMessage}
                onChange={(e) => setCustomMessage(e.target.value)}
                className="bg-gray-700 border-gray-600 text-white min-h-24"
              />
              {customMessage && (
                <div className="mt-2 flex items-center gap-2">
                  <Button
                    size="sm"
                    variant="outline"
                    onClick={() => setCustomMessage('')}
                  >
                    Clear Message
                  </Button>
                  <span className="text-xs text-gray-500">
                    {customMessage.length} characters
                  </span>
                </div>
              )}
            </div>

            <div className="grid grid-cols-1 md:grid-cols-5 gap-3">
              {socialPlatforms.map((platform) => {
                const IconComponent = platform.icon;
                const shareContent = customMessage || `🚀 Exciting investment opportunity with Aureus Alliance!\n\n✅ $5 NFT Packs\n✅ 3-Level Commissions\n✅ USDT + NFT Bonuses\n\nJoin me today!`;

                return (
                  <Button
                    key={platform.name}
                    onClick={() => shareToSocial(platform, shareContent)}
                    className={`${platform.color} hover:opacity-90 text-white flex items-center gap-2 justify-center`}
                  >
                    <IconComponent className="h-4 w-4" />
                    <span className="hidden sm:inline">{platform.name}</span>
                  </Button>
                );
              })}
            </div>

            {/* Copy Content Button */}
            <div className="flex justify-center">
              <Button
                onClick={() => {
                  const shareContent = customMessage || `🚀 Exciting investment opportunity with Aureus Alliance!\n\n✅ $5 NFT Packs\n✅ 3-Level Commissions\n✅ USDT + NFT Bonuses\n\nJoin me today!`;
                  copyToClipboard(`${shareContent}\n\n${referralLink}`);
                }}
                className="bg-gray-600 hover:bg-gray-700 text-white flex items-center gap-2"
                size="sm"
              >
                <Copy className="w-4 h-4" />
                Copy Content & Link
              </Button>
            </div>

            <div className="bg-gray-700/50 rounded-lg p-3">
              <h4 className="text-white text-sm font-semibold mb-2">Platform Status:</h4>
              <div className="grid grid-cols-1 gap-2 text-xs">
                <div className="flex items-center gap-2">
                  <span className="w-2 h-2 bg-green-400 rounded-full"></span>
                  <span className="text-green-400">WhatsApp & Telegram:</span>
                  <span className="text-gray-400">Auto-fill content</span>
                </div>
                <div className="flex items-center gap-2">
                  <span className="w-2 h-2 bg-blue-400 rounded-full"></span>
                  <span className="text-blue-400">X (Twitter):</span>
                  <span className="text-gray-400">Auto-fill content</span>
                </div>
                <div className="flex items-center gap-2">
                  <span className="w-2 h-2 bg-yellow-400 rounded-full"></span>
                  <span className="text-yellow-400">Facebook & LinkedIn:</span>
                  <span className="text-gray-400">Copy & paste method</span>
                </div>
              </div>
              <p className="text-xs text-gray-500 mt-2">
                💡 Facebook & LinkedIn restrict auto-fill for security. Content is copied to clipboard automatically.
              </p>
            </div>

            <p className="text-xs text-gray-500">
              💡 <strong>Pro Tip:</strong> Use "Generate" on templates above for unique content, then "Use in Quick Share" to add it here.
            </p>
          </div>
        </CardContent>
      </Card>

      {/* Marketing Assets - Only show if assets are available */}
      {marketingAssets.length > 0 ? (
        <Card className="bg-gray-800 border-gray-700">
          <CardHeader>
            <CardTitle className="text-white flex items-center gap-2">
              <Image className="h-5 w-5 text-gold" />
              Marketing Assets
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
              {marketingAssets.map((asset) => {
              const getAssetIcon = () => {
                switch (asset.type) {
                  case 'image': return <Image className="h-8 w-8 text-blue-400" />;
                  case 'video': return <Video className="h-8 w-8 text-red-400" />;
                  case 'banner': return <FileText className="h-8 w-8 text-green-400" />;
                  case 'logo': return <Star className="h-8 w-8 text-gold" />;
                  default: return <FileText className="h-8 w-8 text-gray-400" />;
                }
              };

              return (
                <div key={asset.id} className="bg-gray-700/50 rounded-lg p-4">
                  <div className="flex items-center gap-3 mb-3">
                    {getAssetIcon()}
                    <div>
                      <h3 className="text-white font-semibold text-sm">{asset.title}</h3>
                      <p className="text-gray-400 text-xs">{asset.size} • {asset.format}</p>
                    </div>
                  </div>

                  <p className="text-gray-300 text-xs mb-3">{asset.description}</p>

                  <Button
                    size="sm"
                    onClick={() => downloadAsset(asset)}
                    className="w-full bg-gold-gradient text-black"
                  >
                    <Download className="h-4 w-4 mr-1" />
                    Download
                  </Button>
                </div>
              );
            })}
          </div>
        </CardContent>
      </Card>
      ) : (
        <Card className="bg-gray-800 border-gray-700">
          <CardHeader>
            <CardTitle className="text-white flex items-center gap-2">
              <Image className="h-5 w-5 text-gold" />
              Marketing Assets
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-center py-8">
              <Image className="h-12 w-12 text-gray-400 mx-auto mb-4" />
              <p className="text-gray-400 mb-2">No marketing assets available</p>
              <p className="text-sm text-gray-500">Marketing assets will appear here once uploaded by admin</p>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Template Editor Modal */}
      {selectedTemplate && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
          <div className="bg-gray-800 rounded-lg p-6 max-w-2xl w-full max-h-[80vh] overflow-y-auto">
            <div className="flex items-center justify-between mb-4">
              <h3 className="text-xl font-bold text-white">Edit Template</h3>
              <Button
                variant="ghost"
                onClick={() => setSelectedTemplate(null)}
                className="text-gray-400 hover:text-white"
              >
                ✕
              </Button>
            </div>

            <div className="space-y-4">
              <div>
                <label className="text-sm text-gray-400 block mb-2">Template Content</label>
                <Textarea
                  value={selectedTemplate.content}
                  onChange={(e) => setSelectedTemplate({...selectedTemplate, content: e.target.value})}
                  className="bg-gray-700 border-gray-600 text-white min-h-48"
                />
              </div>

              <div className="flex items-center gap-2">
                <Button
                  onClick={() => {
                    copyToClipboard(selectedTemplate.content);
                    setSelectedTemplate(null);
                  }}
                  className="bg-gold-gradient text-black"
                >
                  <Copy className="h-4 w-4 mr-2" />
                  Copy & Close
                </Button>
                <Button
                  variant="outline"
                  onClick={() => setSelectedTemplate(null)}
                >
                  Cancel
                </Button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Instructions Modal */}
      {showInstructions && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
          <div className="bg-gray-800 rounded-lg p-6 max-w-md w-full">
            <div className="flex items-center justify-between mb-4">
              <h3 className="text-xl font-bold text-white">
                {showInstructions} Sharing Instructions
              </h3>
              <Button
                variant="ghost"
                onClick={() => setShowInstructions(null)}
                className="text-gray-400 hover:text-white"
              >
                ✕
              </Button>
            </div>

            <div className="space-y-4">
              <div className="bg-green-900/20 border border-green-500/30 rounded-lg p-3">
                <div className="flex items-center gap-2 mb-2">
                  <span className="text-green-400">✅</span>
                  <span className="text-green-400 font-semibold">Content Copied!</span>
                </div>
                <p className="text-base text-white">
                  Your marketing content and referral link have been copied to your clipboard.
                </p>
              </div>

              <div className="space-y-3">
                <h4 className="text-white font-semibold text-lg">Next Steps:</h4>
                <div className="space-y-3 text-base text-white">
                  <div className="flex items-start gap-3">
                    <span className="text-blue-400 font-bold text-lg">1.</span>
                    <span>{showInstructions} will open in a new window</span>
                  </div>
                  <div className="flex items-start gap-3">
                    <span className="text-blue-400 font-bold text-lg">2.</span>
                    <span>
                      {showInstructions === 'Facebook'
                        ? 'Click "What\'s on your mind?" to create a new post'
                        : 'Click "Start a post" to create a new post'
                      }
                    </span>
                  </div>
                  <div className="flex items-start gap-3">
                    <span className="text-blue-400 font-bold text-lg">3.</span>
                    <span>Press <kbd className="bg-gray-700 px-2 py-1 rounded text-white">Ctrl+V</kbd> (or <kbd className="bg-gray-700 px-2 py-1 rounded text-white">Cmd+V</kbd> on Mac) to paste your content</span>
                  </div>
                  <div className="flex items-start gap-3">
                    <span className="text-blue-400 font-bold text-lg">4.</span>
                    <span>Review and publish your post!</span>
                  </div>
                </div>
              </div>

              <Button
                onClick={() => setShowInstructions(null)}
                className="w-full bg-gold-gradient text-black"
              >
                Got it! Open {showInstructions}
              </Button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default SocialMediaTools;

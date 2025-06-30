
import React from 'react';
import { Mail } from "@/components/SafeIcons";
import { ST as T } from '@/components/SimpleTranslator';

// Safe social media and other icons
const Diamond = ({ className }: { className?: string }) => <span className={className}>💎</span>;
const Twitter = ({ className }: { className?: string }) => <span className={className}>🐦</span>;
const Facebook = ({ className }: { className?: string }) => <span className={className}>📘</span>;
const Instagram = ({ className }: { className?: string }) => <span className={className}>📷</span>;
const Globe = ({ className }: { className?: string }) => <span className={className}>🌐</span>;

const Footer: React.FC = () => {
  return (
    <footer id="contact" className="bg-charcoal py-12 px-6 md:px-12">
      <div className="max-w-6xl mx-auto">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-8 mb-12">
          <div className="md:col-span-2">
            <div className="flex items-center space-x-2 mb-4">
              <Diamond className="w-6 h-6 text-gold" />
              <span className="text-xl font-bold font-playfair text-gradient">Aureus Alliance Holdings</span>
            </div>
            
            <p className="text-white/70 mb-6">
              <T k="footer.company_description" fallback="The future of gold mining meets blockchain innovation, NFT collectibles, and immersive gaming." />
            </p>
            
            <div className="flex space-x-4">
              <a href="#" className="text-white/70 hover:text-gold transition-colors">
                <Twitter className="w-5 h-5" />
              </a>
              <a href="#" className="text-white/70 hover:text-gold transition-colors">
                <Facebook className="w-5 h-5" />
              </a>
              <a href="#" className="text-white/70 hover:text-gold transition-colors">
                <Instagram className="w-5 h-5" />
              </a>
              <a href="#" className="text-white/70 hover:text-gold transition-colors">
                <Globe className="w-5 h-5" />
              </a>
            </div>
          </div>
          
          <div>
            <h3 className="font-playfair font-semibold text-gold mb-4">
              <T k="footer.quick_links" fallback="Quick Links" />
            </h3>
            <ul className="space-y-2">
              <li><a href="#" className="text-white/70 hover:text-gold transition-colors">
                <T k="footer.investment" fallback="Investment" />
              </a></li>
              <li><a href="#" className="text-white/70 hover:text-gold transition-colors">
                <T k="footer.benefits" fallback="Benefits" />
              </a></li>
              <li><a href="#" className="text-white/70 hover:text-gold transition-colors">
                <T k="footer.about" fallback="About" />
              </a></li>
              <li><a href="#" className="text-white/70 hover:text-gold transition-colors">
                <T k="footer.contact" fallback="Contact" />
              </a></li>
            </ul>
          </div>
          
          <div>
            <h3 className="font-playfair font-semibold text-gold mb-4">
              <T k="footer.contact_us" fallback="Contact Us" />
            </h3>
            <div className="space-y-2">
              <p className="text-white/70">
                <T k="footer.investment_inquiries" fallback="For investment inquiries:" />
              </p>
              <a href="mailto:invest@aureusalliance.com" className="text-gold hover:underline flex items-center">
                <Mail className="w-4 h-4 mr-2" />
                invest@aureusalliance.com
              </a>
            </div>
          </div>
        </div>
        
        <div className="border-t border-white/10 pt-6 text-center">
          <p className="text-white/60 text-sm">
            © {new Date().getFullYear()} <T k="footer.company_name" fallback="Aureus Alliance Holdings" />. <T k="footer.rights_reserved" fallback="All rights reserved." />
          </p>
          <p className="text-white/40 text-xs mt-2">
            <T k="footer.investment_risk_disclaimer" fallback="Investment opportunities involve risk. Please consult with a professional financial advisor before investing." />
          </p>
        </div>
      </div>
    </footer>
  );
};

export default Footer;

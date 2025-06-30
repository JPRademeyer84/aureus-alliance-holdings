
import React from 'react';
import { Button } from "@/components/ui/button";
import { User } from "@/components/SafeIcons";
import { Link, useLocation } from 'react-router-dom';
import { useUser } from '@/contexts/UserContext';
import { ST as T } from '@/components/SimpleTranslator';
import HybridTranslator from './HybridTranslator';

// Safe social media and other icons
const Diamond = ({ className }: { className?: string }) => <span className={className}>💎</span>;
const Twitter = ({ className }: { className?: string }) => <span className={className}>🐦</span>;
const Facebook = ({ className }: { className?: string }) => <span className={className}>📘</span>;
const Globe = ({ className }: { className?: string }) => <span className={className}>🌐</span>;

const Navbar: React.FC = () => {
  const location = useLocation();
  const isHomePage = location.pathname === '/';
  const { user, isAuthenticated } = useUser();

  const scrollToSection = (sectionId: string) => {
    if (!isHomePage) {
      window.location.href = `/#${sectionId}`;
      return;
    }
    
    const element = document.getElementById(sectionId);
    element?.scrollIntoView({ behavior: 'smooth' });
  };

  return (
    <nav className="py-4 px-6 md:px-12 flex items-center justify-between border-b border-gold/20">
      <Link to="/" className="flex items-center space-x-2">
        <Diamond className="w-8 h-8 text-gold" />
        <span className="text-2xl font-bold font-playfair text-gradient">Aureus Alliance Holdings</span>
      </Link>
      
      <div className="hidden md:flex items-center space-x-6">
        <button onClick={() => scrollToSection('rewards')} className="text-white/80 hover:text-gold transition-colors">
          <T k="nav.rewards" fallback="Rewards" />
        </button>
        <Link to={isAuthenticated ? "/dashboard?tab=affiliate" : "/auth"} className="text-white/80 hover:text-gold transition-colors">
          <T k="nav.affiliate" fallback="Affiliate" />
        </Link>
        <button onClick={() => scrollToSection('benefits')} className="text-white/80 hover:text-gold transition-colors">
          <T k="nav.benefits" fallback="Benefits" />
        </button>
        <button onClick={() => scrollToSection('about')} className="text-white/80 hover:text-gold transition-colors">
          <T k="nav.about" fallback="About" />
        </button>
        <button onClick={() => scrollToSection('contact')} className="text-white/80 hover:text-gold transition-colors">
          <T k="nav.contact" fallback="Contact" />
        </button>
      </div>
      
      <div className="flex items-center space-x-4">
        <a href="#" className="text-white/70 hover:text-gold transition-colors">
          <Twitter className="w-5 h-5" />
        </a>
        <a href="#" className="text-white/70 hover:text-gold transition-colors">
          <Facebook className="w-5 h-5" />
        </a>
        <a href="#" className="text-white/70 hover:text-gold transition-colors">
          <Globe className="w-5 h-5" />
        </a>

        {/* Language Selector */}
        <HybridTranslator className="ml-2" />

        {isAuthenticated ? (
          <Link to="/dashboard">
            <Button variant="outline" className="border-gold/30 text-white hover:bg-gold/10">
              <User className="w-4 h-4 mr-2" />
              {user?.username}
            </Button>
          </Link>
        ) : (
          <>
            <Link to="/auth">
              <Button variant="outline" className="border-gold/30 text-white hover:bg-gold/10 mr-2">
                <T k="nav.sign_in" fallback="Sign In" />
              </Button>
            </Link>
            <Link to="/auth">
              <Button className="bg-gold-gradient text-black font-semibold hover:opacity-90 transition-opacity">
                <T k="homepage.hero.cta_participate_en" fallback="Participate Now" />
              </Button>
            </Link>
          </>
        )}
      </div>
    </nav>
  );
};

export default Navbar;

import React from 'react';
import { Button } from "@/components/ui/button";
import { Link } from "react-router-dom";
import { useSimpleTranslation as useTranslation, ST as T } from '@/components/SimpleTranslator';

// Safe arrow icon
const ArrowRight = ({ className }: { className?: string }) => <span className={className}>→</span>;
const Hero: React.FC = () => {
  const { translate } = useTranslation();

  return <section className="py-16 md:py-24 px-6 md:px-12 flex flex-col items-center text-center">
      <h1 className="text-4xl md:text-6xl lg:text-7xl font-bold font-playfair leading-tight mb-6 max-w-4xl animate-fade-in">
        <T k="homepage.hero.title_part1" fallback="Become an" /> <span className="text-gradient"><T k="homepage.hero.title_part2" fallback="Angel Funder" /></span> <T k="homepage.hero.title_part3" fallback="in the Future of Digital" /> <T k="homepage.hero.title_part4" fallback="Gold" />
      </h1>

      <p className="text-lg md:text-xl text-white/80 max-w-2xl mb-8 animate-fade-in-slow">
        <T k="homepage.hero.subtitle" fallback="Exclusive pre-seed opportunity to fund Aureus Alliance Holdings – combining physical gold mining with digital NFT collectibles." />
      </p>
      
      <div className="flex flex-col md:flex-row items-center gap-4 animate-fade-in-slow">
        <Button className="bg-gold-gradient text-black font-semibold hover:opacity-90 transition-opacity px-8 py-6 text-lg" asChild>
          <Link to="/auth">
            <T k="homepage.hero.cta_participate_en" fallback="Fund Now" />
          </Link>
        </Button>
        <Button variant="outline" className="border-gold text-gold hover:bg-gold/10 px-8 py-6 text-lg" asChild>
          <Link to="/auth">
            <T k="homepage.hero.cta_learn_en" fallback="Learn More" /> <ArrowRight className="ml-2 h-5 w-5" />
          </Link>
        </Button>
      </div>
      
      <div className="mt-12 pt-8 border-t border-white/10 flex flex-col md:flex-row justify-center gap-8 md:gap-16 w-full">
        <div className="text-center">
          <p className="text-3xl md:text-4xl font-bold font-playfair text-gradient">10x</p>
          <p className="text-sm text-white/70">
            <T k="stats.reward_funding" fallback="Reward on Funding" />
          </p>
        </div>
        <div className="text-center">
          <p className="text-3xl md:text-4xl font-bold font-playfair text-gradient">$89</p>
          <p className="text-sm text-white/70">
            <T k="stats.annual_share" fallback="Annual per Share" />
          </p>
        </div>
        <div className="text-center">
          <p className="text-3xl md:text-4xl font-bold font-playfair text-gradient">20%</p>
          <p className="text-sm text-white/70">
            <T k="stats.affiliate_commission" fallback="Affiliate Commission" />
          </p>
        </div>
        <div className="text-center">
          <p className="text-3xl md:text-4xl font-bold font-playfair text-gold">June</p>
          <p className="text-sm text-white/70">
            <T k="stats.nft_presale" fallback="NFT Presale Launch" />
          </p>
        </div>
      </div>
    </section>;
};
export default Hero;
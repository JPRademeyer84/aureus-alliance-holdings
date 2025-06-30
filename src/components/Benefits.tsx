
import React from 'react';
import { Shield } from '@/components/SafeIcons';

// Safe benefit icons
const Diamond = ({ className }: { className?: string }) => <span className={className}>💎</span>;
const Coins = ({ className }: { className?: string }) => <span className={className}>🪙</span>;
const UserPlus = ({ className }: { className?: string }) => <span className={className}>👤➕</span>;
const Gamepad = ({ className }: { className?: string }) => <span className={className}>🎮</span>;
import { ST as T } from '@/components/SimpleTranslator';

const benefitsData = [
  {
    titleKey: "guarantees.product_based_income",
    titleFallback: "100% Product-Based Income",
    descriptionKey: "guarantees.product_based_income_desc",
    descriptionFallback: "No rewards for recruiting - all income comes from actual NFT sales and gold mining operations.",
    icon: Shield,
  },
  {
    titleKey: "guarantees.blockchain_asset_security",
    titleFallback: "Blockchain Asset Security",
    descriptionKey: "guarantees.blockchain_security_desc",
    descriptionFallback: "NFTs are real blockchain assets with verifiable ownership and mining revenue backing.",
    icon: Diamond,
  },
  {
    titleKey: "guarantees.sales_funded_rewards",
    titleFallback: "Sales-Funded Rewards",
    descriptionKey: "guarantees.sales_funded_desc",
    descriptionFallback: "All rewards and bonuses come from actual sales proceeds - not promises or new participant funds.",
    icon: Coins,
  },
  {
    titleKey: "guarantees.transparent_structure",
    titleFallback: "Transparent Structure",
    descriptionKey: "guarantees.transparent_structure_desc",
    descriptionFallback: "Fully transparent presale and referral plan with clear fund allocation and usage.",
    icon: UserPlus,
  },
  {
    titleKey: "guarantees.compliance_focused",
    titleFallback: "Compliance Focused",
    descriptionKey: "guarantees.compliance_desc",
    descriptionFallback: "Sales-based structure with no pyramid model - fully compliant with regulations.",
    icon: Gamepad,
  },
];

const Benefits: React.FC = () => {
  return (
    <section id="benefits" className="py-16 px-6 md:px-12 bg-gradient-to-b from-black/50 to-charcoal/30">
      <div className="max-w-6xl mx-auto">
        <h2 className="text-3xl md:text-4xl font-bold font-playfair mb-4 text-center">
          <T k="guarantees.key_guarantees_safety" fallback="Key Guarantees & Safety" />
        </h2>

        <p className="text-center text-white/70 mb-12 max-w-2xl mx-auto">
          <T k="guarantees.participation_structure_desc" fallback="Our participation structure is built on transparency, compliance, and real-world value creation - not speculation or recruitment schemes." />
        </p>
        
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          {benefitsData.map((benefit, index) => (
            <div 
              key={index} 
              className="bg-black/30 rounded-lg p-6 border border-gold/30 hover:border-gold/50 transition-all hover:shadow-[0_0_15px_rgba(212,175,55,0.3)] group"
            >
              <div className="mb-4 p-3 bg-gold/10 rounded-full inline-block group-hover:animate-gold-pulse">
                <benefit.icon className="w-6 h-6 text-gold" />
              </div>
              <h3 className="text-xl font-playfair font-semibold mb-2">
                <T k={benefit.titleKey} fallback={benefit.titleFallback} />
              </h3>
              <p className="text-white/70">
                <T k={benefit.descriptionKey} fallback={benefit.descriptionFallback} />
              </p>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
};

export default Benefits;

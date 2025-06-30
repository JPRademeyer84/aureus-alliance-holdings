
import React from 'react';
import Navbar from '@/components/Navbar';
import Hero from '@/components/Hero';
import HowItWorks from '@/components/HowItWorks';
import RewardsCalculator from '@/components/RewardsCalculator';
import Benefits from '@/components/Benefits';
import SimpleNetworkerCommission from '@/components/SimpleNetworkerCommission';
import SimpleRewards from '@/components/SimpleRewards';
import SimpleGoldDiggersClub from '@/components/SimpleGoldDiggersClub';
import AboutProject from '@/components/AboutProject';
import InvestmentOpportunity from '@/components/InvestmentOpportunity';
import CallToAction from '@/components/CallToAction';
import Footer from '@/components/Footer';

const Index: React.FC = () => {
  return (
    <div className="min-h-screen bg-charcoal">
      <Navbar />
      <Hero />
      <AboutProject />
      <InvestmentOpportunity />
      <HowItWorks />
      <SimpleRewards />
      <SimpleNetworkerCommission />
      <SimpleGoldDiggersClub />
      <Benefits />
      <RewardsCalculator />
      <CallToAction />
      <Footer />
    </div>
  );
};

export default Index;

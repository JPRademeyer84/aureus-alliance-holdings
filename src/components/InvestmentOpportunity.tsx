import React from 'react';
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import FeaturedPlans from "@/components/investment/FeaturedPlans";
import AllPlans from "@/components/investment/AllPlans";
import { quarterlyDividendsStart, yieldDeadline } from '@/pages/investment/constants';
import { ST as T } from '@/components/SimpleTranslator';

const InvestmentOpportunity: React.FC = () => (
  <section className="py-16 px-6 md:px-12 bg-gradient-to-b from-charcoal/80 to-royal/30">
    <div className="max-w-6xl mx-auto">
      <div className="text-center mb-12">
        <h2 className="text-3xl md:text-4xl font-bold font-playfair mb-4 text-white drop-shadow-lg">
          <T k="participation.title_part1" fallback="Limited Time" /> <span className="text-gradient"><T k="participation.title_part2" fallback="Participation Opportunity" /></span>
        </h2>
        <p className="text-white/80 max-w-2xl mx-auto drop-shadow-lg">
          <T k="participation.description" fallback="Our pre-seed round is capped at $250,000 to ensure exclusivity and maximum rewards for our earliest supporters." />
        </p>
      </div>

      <Tabs defaultValue="featured" className="w-full">
        <TabsList className="w-full max-w-md mx-auto bg-[#21232c]/80 mb-8 shadow-lg border border-gold/40">
          <TabsTrigger value="featured" className="flex-1 text-gold">
            <T k="participation.tab_featured" fallback="Featured Plans" />
          </TabsTrigger>
          <TabsTrigger value="all" className="flex-1 text-gold">
            <T k="participation.tab_all" fallback="View All Plans" />
          </TabsTrigger>
        </TabsList>
        <TabsContent value="featured" className="mt-0">
          <FeaturedPlans />
        </TabsContent>
        <TabsContent value="all" className="mt-0">
          <AllPlans />
        </TabsContent>
      </Tabs>

      <div className="mt-8 text-white/80 text-center text-base bg-black/40 p-4 rounded-lg border border-gold/10">
        <strong className="text-gold">
          <T k="participation.instructions_part1" fallback="Participate between $50 and $50,000 per transaction." />
        </strong>{" "}
        <T k="participation.instructions_part2" fallback="Maximum total round is $250,000. Receive rewards by" /> {yieldDeadline} <T k="participation.instructions_part3" fallback="and quarterly benefits starting" /> {quarterlyDividendsStart}.
      </div>
    </div>
  </section>
);

export default InvestmentOpportunity;


import React from 'react';
import { Button } from "@/components/ui/button";
import { Link } from "react-router-dom";
import { yieldDeadline, maxRoundParticipation } from "@/pages/investment/constants";
import { ST as T } from "@/components/SimpleTranslator";
import { safeToLocaleString } from "@/utils/formatters";

const CallToAction: React.FC = () => {
  return (
    <section className="py-24 px-6 md:px-12 bg-gold-gradient">
      <div className="max-w-4xl mx-auto text-center">
        <h2 className="text-3xl md:text-4xl font-bold font-playfair mb-6 text-black">
          <T k="cta.become_angel_funder_today" fallback="Become an Angel Funder Today" />
        </h2>

        <p className="text-black/80 mb-8 text-lg">
          <T k="cta.only" fallback="Only" /> ${safeToLocaleString(maxRoundParticipation, 250000)} <T k="cta.preseed_funding" fallback="of pre-seed funding" /> <T k="cta.available" fallback="available" />. <T k="cta.secure_position" fallback="Secure your position" /> <T k="cta.before_opportunity_closes" fallback="before the opportunity closes" />.
        </p>

        <Button className="bg-black text-gold hover:bg-black/80 transition-colors px-8 py-6 text-lg" asChild>
          <Link to="/auth">
            <T k="cta.fund_now_button" fallback="Fund Now" />
          </Link>
        </Button>

        <p className="mt-6 text-black/70 text-sm">
          <T k="cta.10x_rewards" fallback="10x Rewards" /> <T k="cta.by_january_1_2026" fallback={`by ${yieldDeadline}`} />. <T k="cta.funding_closes" fallback="Funding closes" /> <T k="cta.when_reach_cap" fallback={`when we reach our $${maxRoundParticipation.toLocaleString()} cap`} /> <T k="cta.or_when_nft_presale" fallback="or when NFT presale begins in June" />.
        </p>
      </div>
    </section>
  );
};

export default CallToAction;

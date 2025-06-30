
import React, { useState } from "react";
import Navbar from "@/components/Navbar";
import Footer from "@/components/Footer";
import ParticipationSection from "./investment/InvestmentSection";
import { ParticipationPlan } from "./investment/constants";
import { useReferralTracking } from '@/hooks/useReferralTracking';
import { Card, CardContent } from '@/components/ui/card';
import { Users, Gift } from 'lucide-react';

const ParticipationPage: React.FC = () => {
  const [selectedPlan, setSelectedPlan] = useState<ParticipationPlan | null>(null);
  const [isPaying, setIsPaying] = useState(false);
  const [paymentStatus, setPaymentStatus] = useState<'idle' | 'pending' | 'success' | 'error'>('idle');
  const [paymentTxHash, setPaymentTxHash] = useState<string | null>(null);

  // Referral tracking
  const { isValidReferral, referralData, hasActiveReferral } = useReferralTracking();

  return (
    <div className="min-h-screen bg-charcoal">
      <Navbar />
      <div className="container mx-auto px-4 py-12">
        {/* Referral Banner */}
        {hasActiveReferral && referralData && (
          <Card className="mb-8 bg-gradient-to-r from-gold/10 to-gold/5 border-gold/30">
            <CardContent className="p-6">
              <div className="flex items-center justify-center gap-4 text-center">
                <Users className="h-8 w-8 text-gold" />
                <div>
                  <h3 className="text-xl font-bold text-white mb-2">
                    🎉 You were referred by <span className="text-gold">{referralData.referrerUsername}</span>!
                  </h3>
                  <p className="text-gray-300">
                    Complete your participation and they'll earn commission rewards.
                    You'll also get the same great participation rewards!
                  </p>
                </div>
                <Gift className="h-8 w-8 text-gold" />
              </div>
            </CardContent>
          </Card>
        )}

        <h1 className="text-3xl md:text-4xl font-bold font-playfair mb-8 text-center">
          <span className="text-gradient">Participate</span> in Aureus Alliance
        </h1>
        <ParticipationSection
          selectedPlan={selectedPlan}
          setSelectedPlan={setSelectedPlan}
          paymentStatus={paymentStatus}
          setPaymentStatus={setPaymentStatus}
          paymentTxHash={paymentTxHash}
          setPaymentTxHash={setPaymentTxHash}
          isPaying={isPaying}
          setIsPaying={setIsPaying}
        />
      </div>
      <Footer />
    </div>
  );
};

export default ParticipationPage;

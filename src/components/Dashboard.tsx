
import React from 'react';
import { Button } from "@/components/ui/button";

const Dashboard: React.FC = () => {
  return (
    <div className="py-16 px-6 md:px-12">
      <div className="max-w-6xl mx-auto">
        <h2 className="text-3xl md:text-4xl font-bold font-playfair mb-8">
          Investor <span className="text-gradient">Dashboard</span>
        </h2>
        
        <div className="bg-black/40 border golden-border rounded-lg p-8">
          <div className="text-center py-12">
            <p className="text-lg mb-4">Coming Soon</p>
            <h3 className="text-2xl font-bold font-playfair mb-6">
              Participant Dashboard
            </h3>
            <p className="text-white/70 max-w-2xl mx-auto mb-8">
              The participant dashboard is currently under development. Here you'll be able to track your participation, NFT holdings, and reward payments.
            </p>

            <Button className="bg-gold-gradient text-black font-semibold hover:opacity-90 transition-opacity">
              Go to Participation
            </Button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Dashboard;

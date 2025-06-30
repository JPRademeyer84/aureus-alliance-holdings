
import React from 'react';
import { Loader2 } from "lucide-react";

const CalculatorLoading: React.FC = () => (
  <section id="investment" className="py-16 px-6 md:px-12">
    <div className="max-w-4xl mx-auto flex flex-col items-center justify-center min-h-[400px]">
      <Loader2 className="h-8 w-8 animate-spin text-gold" />
      <p className="mt-4 text-white/80">Loading investment packages...</p>
    </div>
  </section>
);

export default CalculatorLoading;

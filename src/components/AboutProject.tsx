
import React from 'react';
import { ST as T } from '@/components/SimpleTranslator';

const AboutProject: React.FC = () => {
  return (
    <section id="about" className="py-16 px-6 md:px-12 bg-gradient-to-b from-charcoal to-charcoal/80">
      <div className="max-w-6xl mx-auto">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
          <div>
            <h2 className="text-3xl md:text-4xl font-bold font-playfair mb-6">
              <T k="about.title_part1" fallback="About" /> <span className="text-gradient"><T k="about.title_part2" fallback="Aureus Alliance Holdings" /></span>
            </h2>

            <p className="text-white/80 mb-4">
              <T k="about.description1" fallback="Aureus Alliance Holdings is pioneering the convergence of traditional gold mining with the digital frontier of NFTs, gaming, and blockchain technology." />
            </p>

            <p className="text-white/80 mb-4">
              <T k="about.description2" fallback="Our foundation is built on physical gold mining operations that generate consistent revenue, providing a stable backing for our digital endeavors." />
            </p>

            <p className="text-white/80 mb-4">
              <T k="about.description3" fallback="The Aureus ecosystem includes premium collectible NFTs, an immersive MMO gaming experience, and exclusive opportunities for early investors." />
            </p>
            
            <div className="mt-8 border-t border-gold/20 pt-6">
              <h3 className="text-xl font-playfair font-semibold mb-3">
                <T k="about.timeline_title" fallback="Investment Timeline" />
              </h3>

              <div className="space-y-4">
                <div className="flex items-start">
                  <div className="w-20 text-gold font-semibold">
                    <T k="about.timeline_now" fallback="Now" />
                  </div>
                  <div>
                    <p className="font-semibold">
                      <T k="about.timeline_now_title" fallback="Pre-Seed Angel Investment Round" />
                    </p>
                    <p className="text-white/70 text-sm">
                      <T k="about.timeline_now_desc" fallback="Limited to $100,000 total investment" />
                    </p>
                  </div>
                </div>

                <div className="flex items-start">
                  <div className="w-20 text-gold font-semibold">
                    <T k="about.timeline_june" fallback="June" />
                  </div>
                  <div>
                    <p className="font-semibold">
                      <T k="about.timeline_june_title" fallback="NFT Presale Launch" />
                    </p>
                    <p className="text-white/70 text-sm">
                      <T k="about.timeline_june_desc" fallback="Public offering at higher valuation" />
                    </p>
                  </div>
                </div>

                <div className="flex items-start">
                  <div className="w-20 text-gold font-semibold">
                    <T k="about.timeline_q3" fallback="Q3 2025" />
                  </div>
                  <div>
                    <p className="font-semibold">
                      <T k="about.timeline_q3_title" fallback="Gaming Platform Alpha" />
                    </p>
                    <p className="text-white/70 text-sm">
                      <T k="about.timeline_q3_desc" fallback="Early access for investors and NFT holders" />
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div className="bg-gradient-to-br from-royal/20 to-gold/10 p-8 rounded-lg border golden-border">
            <h3 className="text-2xl font-playfair font-semibold mb-6 text-center">
              <T k="about.advantage_title" fallback="The Aureus Advantage" />
            </h3>

            <div className="space-y-6">
              <div>
                <h4 className="text-xl text-gold mb-2">
                  <T k="about.advantage_security_title" fallback="Gold-Backed Security" />
                </h4>
                <p className="text-white/80">
                  <T k="about.advantage_security_desc" fallback="Unlike purely speculative digital assets, Aureus is backed by physical gold mining operations with real-world value and cash flow." />
                </p>
              </div>

              <div>
                <h4 className="text-xl text-gold mb-2">
                  <T k="about.advantage_revenue_title" fallback="Multi-Stream Revenue" />
                </h4>
                <p className="text-white/80">
                  <T k="about.advantage_revenue_desc" fallback="Our business model combines income from gold production, NFT sales, gaming microtransactions, and marketplace fees." />
                </p>
              </div>

              <div>
                <h4 className="text-xl text-gold mb-2">
                  <T k="about.advantage_growth_title" fallback="Exponential Growth Potential" />
                </h4>
                <p className="text-white/80">
                  <T k="about.advantage_growth_desc" fallback="The integration of traditional mining with cutting-edge digital assets creates unique synergies and market positioning." />
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
};

export default AboutProject;

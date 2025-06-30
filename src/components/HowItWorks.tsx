import React from 'react';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import {
  Package,
  Calendar,
  CheckCircle
} from '@/components/SafeIcons';

// Safe additional icons
const UserPlus = ({ className }: { className?: string }) => <span className={className}>👤➕</span>;
const Coins = ({ className }: { className?: string }) => <span className={className}>🪙</span>;
const TrendingUp = ({ className }: { className?: string }) => <span className={className}>📈</span>;
const Gift = ({ className }: { className?: string }) => <span className={className}>🎁</span>;
const ArrowRight = ({ className }: { className?: string }) => <span className={className}>→</span>;
import { Link } from 'react-router-dom';
import { ST as T, useSimpleTranslation as useTranslation } from '@/components/SimpleTranslator';

const HowItWorks: React.FC = () => {
  const { translate } = useTranslation();

  const steps = [
    {
      number: 1,
      title: "homepage.steps.step1.title",
      description: "homepage.steps.step1.description",
      icon: UserPlus,
      color: "text-blue-400",
      bgColor: "bg-blue-400/10"
    },
    {
      number: 2,
      title: "homepage.steps.step2.title",
      description: "homepage.steps.step2.description",
      icon: Package,
      color: "text-green-400",
      bgColor: "bg-green-400/10"
    },
    {
      number: 3,
      title: "homepage.steps.step3.title",
      description: "homepage.steps.step3.description",
      icon: Coins,
      color: "text-yellow-400",
      bgColor: "bg-yellow-400/10"
    },
    {
      number: 4,
      title: "homepage.steps.step4.title",
      description: "homepage.steps.step4.description",
      icon: TrendingUp,
      color: "text-purple-400",
      bgColor: "bg-purple-400/10"
    },
    {
      number: 5,
      title: "homepage.steps.step5.title",
      description: "homepage.steps.step5.description",
      icon: Calendar,
      color: "text-orange-400",
      bgColor: "bg-orange-400/10"
    },
    {
      number: 6,
      title: "homepage.steps.step6.title",
      description: "homepage.steps.step6.description",
      icon: Gift,
      color: "text-gold",
      bgColor: "bg-gold/10"
    }
  ];

  const benefits = [
    "homepage.benefits.benefit1",
    "homepage.benefits.benefit2",
    "homepage.benefits.benefit3",
    "homepage.benefits.benefit4",
    "homepage.benefits.benefit5",
    "homepage.benefits.benefit6"
  ];

  return (
    <section id="how-it-works" className="py-16 px-6 md:px-12 bg-gradient-to-b from-charcoal/50 to-royal/30">
      <div className="max-w-6xl mx-auto">
        {/* Header */}
        <div className="text-center mb-12">
          <h2 className="text-3xl md:text-4xl font-bold font-playfair mb-4">
            <T k="homepage.how_it_works.title_part1" fallback="How" /> <span className="text-gradient"><T k="homepage.how_it_works.title_part2" fallback="Angel Funding" /></span> <T k="homepage.how_it_works.title_part3" fallback="Works" />
          </h2>
          <p className="text-white/80 max-w-2xl mx-auto text-lg">
            <T k="homepage.how_it_works.description" fallback="Join Aureus Alliance Holdings in 6 simple steps. No complicated processes, no hidden fees - just a straightforward path to digital gold ownership." />
          </p>
        </div>

        {/* Steps Grid */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
          {steps.map((step, index) => (
            <Card key={step.number} className="bg-black/40 border-gold/20 hover:border-gold/40 transition-all duration-300 group">
              <CardContent className="p-6">
                <div className="flex items-start space-x-4">
                  <div className={`${step.bgColor} rounded-full p-3 group-hover:scale-110 transition-transform duration-300`}>
                    <step.icon className={`w-6 h-6 ${step.color}`} />
                  </div>
                  <div className="flex-1">
                    <div className="flex items-center space-x-2 mb-2">
                      <span className="bg-gold/20 text-gold text-sm font-bold px-2 py-1 rounded-full">
                        {step.number}
                      </span>
                      <h3 className="font-semibold text-white">
                        {step.title.startsWith('homepage.') ? (
                          <T k={step.title} fallback={`Step ${step.number}`} />
                        ) : (
                          step.title
                        )}
                      </h3>
                    </div>
                    <p className="text-gray-300 text-sm leading-relaxed">
                      {step.description.startsWith('homepage.') ? (
                        <T k={step.description} fallback="Step description" />
                      ) : (
                        step.description
                      )}
                    </p>
                  </div>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>

        {/* Benefits Section */}
        <div className="bg-black/30 rounded-xl p-8 border border-gold/20">
          <h3 className="text-2xl font-bold text-white mb-6 text-center">
            <T k="homepage.benefits.title" fallback="Why Choose Aureus Alliance?" />
          </h3>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {benefits.map((benefit, index) => (
              <div key={index} className="flex items-center space-x-3">
                <CheckCircle className="w-5 h-5 text-green-400 flex-shrink-0" />
                <span className="text-white/90">
                  <T k={benefit} fallback="Benefit description" />
                </span>
              </div>
            ))}
          </div>
        </div>

        {/* Call to Action */}
        <div className="text-center mt-12">
          <div className="bg-gold-gradient rounded-xl p-8">
            <h3 className="text-2xl font-bold text-black mb-4">
              <T k="homepage.cta.title" fallback="Ready to Become an Angel Funder?" />
            </h3>
            <p className="text-black/80 mb-6 max-w-2xl mx-auto">
              <T k="homepage.cta.description" fallback="Join the presale of 200,000 NFT packs at $5 each. Limited time opportunity to fund digital gold mining shares before the main sale phases begin." />
            </p>
            <div className="flex flex-col sm:flex-row gap-4 justify-center items-center">
              <Button
                asChild
                className="bg-black text-gold hover:bg-black/80 px-8 py-3 text-lg font-semibold"
              >
                <Link to="/auth">
                  <T k="homepage.cta.start_funding" fallback="Start Funding Now" />
                  <ArrowRight className="ml-2 h-5 w-5" />
                </Link>
              </Button>
              <Button
                variant="outline"
                asChild
                className="border-black/30 text-black hover:bg-black/10 px-8 py-3"
              >
                <Link to="/auth">
                  <T k="homepage.cta.view_packages" fallback="View Participation Packages" />
                </Link>
              </Button>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
};

export default HowItWorks;

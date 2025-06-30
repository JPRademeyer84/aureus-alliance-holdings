
import React, { useState } from "react";
import { participationAmounts, ParticipationPlan, planConfig } from "./constants";
import { Form, FormField, FormItem, FormLabel, FormControl, FormMessage } from "@/components/ui/form";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Select, SelectTrigger, SelectValue, SelectContent, SelectItem } from "@/components/ui/select";
import { z } from "zod";
import { useForm } from "react-hook-form";
import TermsAcceptance, { TermsAcceptanceData } from "@/components/investment/TermsAcceptance";
import { zodResolver } from "@hookform/resolvers/zod";

interface InvestmentFormProps {
  selectedPlan: InvestmentPlan | null,
  setSelectedPlan: (plan: InvestmentPlan) => void,
  walletAddress: string,
  isPaying: boolean,
  onSubmit: () => void,
  form: ReturnType<typeof useForm<z.infer<typeof formSchema>>>
}

const formSchema = z.object({
  name: z.string().min(2, "Name is required"),
  email: z.string().email("Invalid email address"),
  chain: z.enum(["polygon", "ethereum", "tron", "bnb"]),
  participationPlan: z.enum([
    "starter", "bronze", "silver", "gold", "sapphire", "emerald", "ruby", "diamond", "obsidian"
  ]),
  // Terms acceptance fields
  termsAccepted: z.boolean().refine(val => val === true, {
    message: "You must accept all terms and conditions to proceed"
  }),
  termsData: z.object({
    goldMiningParticipationAccepted: z.boolean(),
    nftSharesUnderstandingAccepted: z.boolean(),
    deliveryTimelineAccepted: z.boolean(),
    benefitTimelineAccepted: z.boolean(),
    riskAcknowledgmentAccepted: z.boolean(),
    acceptanceTimestamp: z.string(),
    termsVersion: z.string()
  }).optional()
});

const InvestmentForm: React.FC<InvestmentFormProps> = ({
  selectedPlan,
  setSelectedPlan,
  walletAddress,
  isPaying,
  onSubmit,
  form,
}) => {
  const [termsAccepted, setTermsAccepted] = useState(false);
  const [termsData, setTermsData] = useState<TermsAcceptanceData | null>(null);

  const handleTermsAcceptance = (allAccepted: boolean, acceptanceData: TermsAcceptanceData) => {
    setTermsAccepted(allAccepted);
    setTermsData(acceptanceData);

    // Update form values
    form.setValue('termsAccepted', allAccepted);
    form.setValue('termsData', acceptanceData);
  };

  return (
  <div>
    <h3 className="text-lg font-semibold mb-4">2. Enter Investment Details</h3>
    <Form {...form}>
      <form onSubmit={onSubmit} className="space-y-6">
        <FormField
          control={form.control}
          name="name"
          render={({ field }) => (
            <FormItem>
              <FormLabel>Full Name</FormLabel>
              <FormControl>
                <Input placeholder="John Smith" {...field} className="bg-black/50 border-gold/30" />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />
        <FormField
          control={form.control}
          name="email"
          render={({ field }) => (
            <FormItem>
              <FormLabel>Email</FormLabel>
              <FormControl>
                <Input placeholder="you@example.com" {...field} className="bg-black/50 border-gold/30" />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />
        <FormField
          control={form.control}
          name="chain"
          render={({ field }) => (
            <FormItem>
              <FormLabel>Blockchain Network</FormLabel>
              <Select onValueChange={field.onChange} defaultValue={field.value}>
                <FormControl>
                  <SelectTrigger className="bg-black/50 border-gold/30">
                    <SelectValue placeholder="Select blockchain" />
                  </SelectTrigger>
                </FormControl>
                <SelectContent className="bg-charcoal border-gold/30">
                  <SelectItem value="polygon">Polygon (MATIC)</SelectItem>
                  <SelectItem value="ethereum">Ethereum (ETH)</SelectItem>
                  <SelectItem value="tron">TRON (TRX)</SelectItem>
                  <SelectItem value="bnb">BNB Chain (BSC)</SelectItem>
                </SelectContent>
              </Select>
              <FormMessage />
            </FormItem>
          )}
        />
        {selectedPlan && (
          <div className="py-4 px-6 bg-black/30 rounded-md border border-gold/30">
            <p className="mb-2">Selected Plan:</p>
            <p className="text-xl font-bold text-gradient capitalize">
              {planConfig[selectedPlan].name} - ${investmentAmounts[selectedPlan].toLocaleString()} USDT
            </p>
            <p className="text-sm text-white/60 mt-1">{planConfig[selectedPlan].shares} Shares</p>
          </div>
        )}

        {/* Terms and Conditions Acceptance */}
        <TermsAcceptance
          onAcceptanceChange={handleTermsAcceptance}
          isRequired={true}
        />
        <Button
          type="submit"
          className="w-full bg-gold-gradient text-black font-semibold py-6"
          disabled={isPaying || !selectedPlan || !walletAddress || !termsAccepted}
        >
          {isPaying ? "Processing..." : "Complete Investment"}
        </Button>
      </form>
    </Form>
  </div>
  );
};

export { InvestmentForm, formSchema };

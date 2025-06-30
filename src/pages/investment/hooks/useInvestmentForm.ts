
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { formSchema } from "../InvestmentForm";
import { ParticipationPlan } from "../constants";
import { useToast } from "@/hooks/use-toast";
import { toast as sonnerToast } from "sonner";
import { participationAmounts } from "../constants";
import ApiConfig from "@/config/api";

export const useParticipationForm = (
  setPaymentStatus: (status: 'idle' | 'pending' | 'success' | 'error') => void,
  setPaymentTxHash: (hash: string | null) => void,
  setIsPaying: (isPaying: boolean) => void,
  selectedPlan: ParticipationPlan | null,
  setSelectedPlan: (plan: ParticipationPlan | null) => void,
  walletAddress: string
) => {
  const { toast } = useToast();
  
  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      name: "",
      email: "",
      chain: "polygon",
      participationPlan: selectedPlan || "gold",
      termsAccepted: false,
      termsData: undefined
    }
  });

  const onSubmit = async (values: z.infer<typeof formSchema>) => {
    if (!walletAddress) {
      toast({
        title: "Wallet Required",
        description: "Please connect your wallet first",
        variant: "destructive"
      });
      return;
    }

    if (!values.termsAccepted) {
      toast({
        title: "Terms Required",
        description: "Please accept all terms and conditions to proceed",
        variant: "destructive"
      });
      return;
    }

    setIsPaying(true);
    setPaymentStatus('pending');

    try {
      const amount = participationAmounts[values.participationPlan];

      // Call MySQL API for processing investment
      const response = await fetch(ApiConfig.endpoints.investments.process, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          name: values.name,
          email: values.email,
          walletAddress: walletAddress,
          chain: values.chain,
          amount: amount,
          investmentPlan: values.investmentPlan,
          termsData: values.termsData,
          userAgent: navigator.userAgent,
          ipAddress: 'client-side' // Will be captured server-side
        })
      });

      const data = await response.json();

      if (data.success) {
        setPaymentTxHash(`0x${Math.random().toString(16).slice(2, 10)}...${Math.random().toString(16).slice(2, 10)}`);
        setPaymentStatus('success');
      } else {
        throw new Error(data.error || 'Failed to process participation');
      }

      sonnerToast.success("Participation Successfully Processed", {
        description: `Your ${amount} USDT participation on ${values.chain} has been recorded.`
      });

      toast({
        title: "Participation Successful",
        description: `Your ${amount} USDT participation is now being processed. You'll receive a confirmation email shortly.`,
      });

      form.reset();
      setSelectedPlan(null);

    } catch (error) {
      console.error("Participation error:", error);
      setPaymentStatus('error');
      toast({
        title: "Participation Failed",
        description: error instanceof Error ? error.message : "There was an error processing your participation. Please try again.",
        variant: "destructive"
      });
    } finally {
      setIsPaying(false);
    }
  };

  return {
    form,
    onSubmit
  };
};

// Legacy export for backward compatibility
export const useInvestmentForm = useParticipationForm;

export type ParticipationPlan =
  | "starter"
  | "bronze"
  | "silver"
  | "gold"
  | "sapphire"
  | "emerald"
  | "ruby"
  | "diamond"
  | "obsidian";

// Legacy type alias for backward compatibility
export type InvestmentPlan = ParticipationPlan;

// Amounts per package (for forms/limits)
export const participationAmounts: Record<ParticipationPlan, number> = {
  starter: 50,
  bronze: 100,
  silver: 250,
  gold: 500,
  sapphire: 750,
  emerald: 1000,
  ruby: 5000,
  diamond: 10000,
  obsidian: 50000,
};

// Legacy export for backward compatibility
export const investmentAmounts = participationAmounts;

export const planConfig: Record<
  ParticipationPlan,
  {
    name: string;
    icon: string;
    iconColor: string;
    shares: number;
    reward: number;
    annualDividends: number;
    quarterDividends: number;
    bonuses: string[];
  }
> = {
  starter: {
    name: "Starter",
    icon: "star",
    iconColor: "bg-green-500",
    shares: 2,
    reward: 400,
    annualDividends: 200,
    quarterDividends: 50,
    bonuses: [
      "Community Discord Access",
      "Guaranteed Common NFT Card"
    ]
  },
  bronze: {
    name: "Bronze",
    icon: "square",
    iconColor: "bg-amber-700",
    shares: 10,
    reward: 800,
    annualDividends: 800,
    quarterDividends: 200,
    bonuses: [
      "All Starter Bonuses",
      "Guaranteed Uncommon NFT Card",
      "Early Game Access",
      "Priority Support",
    ]
  },
  silver: {
    name: "Silver",
    icon: "circle",
    iconColor: "bg-gray-300",
    shares: 30,
    reward: 2000,
    annualDividends: 2500,
    quarterDividends: 625,
    bonuses: [
      "All Bronze Bonuses",
      "Guaranteed Epic NFT Card",
      "Exclusive Game Events Access",
      "VIP Game Benefits",
    ]
  },
  gold: {
    name: "Gold",
    icon: "medal",
    iconColor: "bg-yellow-400",
    shares: 80,
    reward: 4000,
    annualDividends: 7000,
    quarterDividends: 1750,
    bonuses: [
      "All Silver Bonuses",
      "Guaranteed Legendary NFT Card",
      "Studio Visit & Team Dinner",
      "Quarterly Strategy Calls",
    ]
  },
  sapphire: {
    name: "Sapphire",
    icon: "diamond",
    iconColor: "bg-blue-400",
    shares: 100,
    reward: 6000,
    annualDividends: 10000,
    quarterDividends: 2500,
    bonuses: [
      "All Gold Bonuses",
      "Advisory Board Consideration",
      "Named Character In-Game"
    ]
  },
  emerald: {
    name: "Emerald",
    icon: "badge",
    iconColor: "bg-green-400",
    shares: 150,
    reward: 8000,
    annualDividends: 13000,
    quarterDividends: 3250,
    bonuses: [
      "All Sapphire Bonuses",
      "Advisory Board Membership",
      "Mine Site Visit & Team Dinner",
    ]
  },
  ruby: {
    name: "Ruby",
    icon: "heart",
    iconColor: "bg-red-500",
    shares: 800,
    reward: 20000,
    annualDividends: 70000,
    quarterDividends: 17500,
    bonuses: [
      "All Emerald Bonuses",
      "Guaranteed Mythic NFT Card",
      "Company Retreat Invitation",
      "Game Development Input",
    ]
  },
  diamond: {
    name: "Diamond",
    icon: "diamond",
    iconColor: "bg-cyan-300",
    shares: 2000,
    reward: 45000,
    annualDividends: 170000,
    quarterDividends: 42500,
    bonuses: [
      "All Ruby Bonuses",
      "Executive Board Consideration",
      "Profit Sharing Bonus in S.U.N.",
    ]
  },
  obsidian: {
    name: "Obsidian",
    icon: "square",
    iconColor: "bg-black",
    shares: 12500,
    reward: 250000,
    annualDividends: 1000000,
    quarterDividends: 500000,
    bonuses: [
      "All Diamond Bonuses",
      "Lifetime Executive Board Membership",
      "Custom Executive NFT Card",
      "Personal Jet Invitation to Strategy Summit",
      "Handwritten Letter of Appreciation from the Founders",
    ]
  }
};

// Shared constants
export const minParticipation = 50;
export const maxParticipation = 50000;
export const maxRoundParticipation = 250000;
export const rewardDeadline = "1 January 2026";
export const quarterlyBenefitsStart = "Q3 2026";

// Legacy exports for backward compatibility
export const minInvestment = minParticipation;
export const maxInvestment = maxParticipation;
export const maxRoundInvestment = maxRoundParticipation;
export const yieldDeadline = rewardDeadline;
export const quarterlyDividendsStart = quarterlyBenefitsStart;
export const investmentPlans = planConfig;

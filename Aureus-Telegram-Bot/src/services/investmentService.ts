import { Database } from '../database/connection';

export interface InvestmentPackage {
  id: number;
  name: string;
  price: number;
  shares: number;
  roi: number;
  annual_dividends?: number;
  quarter_dividends?: number;
  bonuses: string[];
  description?: string;
  is_active: boolean;
  created_at: Date;
  updated_at: Date;
}

export interface UserInvestment {
  id: number;
  user_id: number;
  package_id: number;
  amount: number;
  shares: number;
  status: string;
  payment_method?: string;
  payment_status?: string;
  created_at: Date;
  updated_at: Date;
  package_name?: string;
  package_price?: number;
}

export interface InvestmentStats {
  total_investments: number;
  total_amount: number;
  active_investments: number;
  completed_investments: number;
  total_shares: number;
  estimated_roi: number;
}

export class InvestmentService {
  private database: Database;

  constructor(database: Database) {
    this.database = database;
  }

  async getInvestmentPackages(): Promise<InvestmentPackage[]> {
    try {
      const packages = await this.database.query(
        `SELECT * FROM investment_packages 
         WHERE is_active = TRUE 
         ORDER BY price ASC`
      );

      return packages.map((pkg: any) => ({
        ...pkg,
        bonuses: typeof pkg.bonuses === 'string' ? JSON.parse(pkg.bonuses) : pkg.bonuses || []
      }));
    } catch (error) {
      console.error('Error getting investment packages:', error);
      return [];
    }
  }

  async getPackageById(packageId: number): Promise<InvestmentPackage | null> {
    try {
      const rows = await this.database.query(
        "SELECT * FROM investment_packages WHERE id = ? AND is_active = TRUE",
        [packageId]
      );

      if (rows.length === 0) return null;

      const pkg = rows[0];
      return {
        ...pkg,
        bonuses: typeof pkg.bonuses === 'string' ? JSON.parse(pkg.bonuses) : pkg.bonuses || []
      };
    } catch (error) {
      console.error('Error getting package by ID:', error);
      return null;
    }
  }

  async getUserInvestments(userId: number): Promise<UserInvestment[]> {
    try {
      const investments = await this.database.query(
        `SELECT i.*, p.name as package_name, p.price as package_price
         FROM aureus_investments i
         LEFT JOIN investment_packages p ON i.package_id = p.id
         WHERE i.user_id = ?
         ORDER BY i.created_at DESC`,
        [userId]
      );

      return investments;
    } catch (error) {
      console.error('Error getting user investments:', error);
      return [];
    }
  }

  async getUserInvestmentStats(userId: number): Promise<InvestmentStats> {
    try {
      const stats = await this.database.query(
        `SELECT 
           COUNT(*) as total_investments,
           COALESCE(SUM(amount), 0) as total_amount,
           COALESCE(SUM(shares), 0) as total_shares,
           COUNT(CASE WHEN status = 'active' THEN 1 END) as active_investments,
           COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_investments
         FROM aureus_investments 
         WHERE user_id = ?`,
        [userId]
      );

      const result = stats[0] || {};
      
      // Calculate estimated ROI based on packages
      const roiQuery = await this.database.query(
        `SELECT COALESCE(SUM(p.roi * (i.shares / p.shares)), 0) as estimated_roi
         FROM aureus_investments i
         JOIN investment_packages p ON i.package_id = p.id
         WHERE i.user_id = ? AND i.status IN ('active', 'completed')`,
        [userId]
      );

      return {
        total_investments: parseInt(result.total_investments) || 0,
        total_amount: parseFloat(result.total_amount) || 0,
        total_shares: parseInt(result.total_shares) || 0,
        active_investments: parseInt(result.active_investments) || 0,
        completed_investments: parseInt(result.completed_investments) || 0,
        estimated_roi: parseFloat(roiQuery[0]?.estimated_roi) || 0
      };
    } catch (error) {
      console.error('Error getting investment stats:', error);
      return {
        total_investments: 0,
        total_amount: 0,
        total_shares: 0,
        active_investments: 0,
        completed_investments: 0,
        estimated_roi: 0
      };
    }
  }

  async createInvestment(userId: number, packageId: number, amount: number, shares: number): Promise<number | null> {
    try {
      const result = await this.database.query(
        `INSERT INTO aureus_investments (user_id, package_id, amount, shares, status, created_at)
         VALUES (?, ?, ?, ?, 'pending', NOW())`,
        [userId, packageId, amount, shares]
      );

      return result.insertId;
    } catch (error) {
      console.error('Error creating investment:', error);
      return null;
    }
  }

  async updateInvestmentStatus(investmentId: number, status: string, paymentMethod?: string): Promise<boolean> {
    try {
      const updates: any = { status };
      if (paymentMethod) {
        updates.payment_method = paymentMethod;
      }

      const fields = Object.keys(updates).map(key => `${key} = ?`).join(", ");
      const values = Object.values(updates);
      values.push(investmentId);

      await this.database.query(
        `UPDATE aureus_investments SET ${fields}, updated_at = NOW() WHERE id = ?`,
        values
      );

      return true;
    } catch (error) {
      console.error('Error updating investment status:', error);
      return false;
    }
  }

  formatCurrency(amount: number): string {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD'
    }).format(amount);
  }

  formatPackageInfo(pkg: InvestmentPackage): string {
    const bonusList = pkg.bonuses.length > 0 
      ? pkg.bonuses.map(bonus => `• ${bonus}`).join('\n')
      : '• None';

    return `💎 **${pkg.name}**
💰 Price: ${this.formatCurrency(pkg.price)}
📊 Shares: ${pkg.shares}
📈 ROI: ${this.formatCurrency(pkg.roi)}
${pkg.annual_dividends ? `💵 Annual Dividends: ${this.formatCurrency(pkg.annual_dividends)}\n` : ''}${pkg.quarter_dividends ? `💰 Quarterly Dividends: ${this.formatCurrency(pkg.quarter_dividends)}\n` : ''}
🎁 **Bonuses:**
${bonusList}`;
  }

  formatInvestmentSummary(investment: UserInvestment): string {
    return `📦 **${investment.package_name}**
💰 Amount: ${this.formatCurrency(investment.amount)}
📊 Shares: ${investment.shares}
📅 Date: ${new Date(investment.created_at).toLocaleDateString()}
🔄 Status: ${investment.status.charAt(0).toUpperCase() + investment.status.slice(1)}`;
  }

  formatPortfolioStats(stats: InvestmentStats): string {
    return `📊 **Portfolio Overview**

💼 Total Investments: ${stats.total_investments}
💰 Total Amount: ${this.formatCurrency(stats.total_amount)}
📊 Total Shares: ${stats.total_shares}
🔄 Active: ${stats.active_investments}
✅ Completed: ${stats.completed_investments}
📈 Estimated ROI: ${this.formatCurrency(stats.estimated_roi)}`;
  }
}

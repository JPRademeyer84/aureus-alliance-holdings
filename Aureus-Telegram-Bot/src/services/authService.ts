import bcrypt from 'bcryptjs';
import jwt from 'jsonwebtoken';
import { Database } from '../database/connection';

export interface TelegramUser {
  id: string;
  user_id?: number;
  telegram_id: number;
  username?: string;
  first_name?: string;
  last_name?: string;
  is_active: boolean;
  is_registered: boolean;
  registration_step: string;
  registration_mode: string;
  temp_email?: string;
  temp_password?: string;
  created_at: Date;
  updated_at: Date;
}

export interface WebUser {
  id: number;
  username: string;
  email: string;
  password_hash: string;
  full_name?: string;
  created_at: Date;
  updated_at: Date;
  is_active: boolean;
  role: string;
  email_verified: boolean;
  kyc_verified: boolean;
  kyc_status: string;
}

export interface AuthResult {
  success: boolean;
  message: string;
  user?: WebUser;
  shouldLogin?: boolean;
  userId?: number;
}

export class AuthService {
  private database: Database;
  private jwtSecret: string;

  constructor(database: Database) {
    this.database = database;
    this.jwtSecret = process.env.JWT_SECRET || 'aureus_jwt_secret_2024_telegram_bot_secure';
  }

  async getTelegramUser(telegramId: number): Promise<TelegramUser | null> {
    try {
      const rows = await this.database.query(
        "SELECT * FROM telegram_users WHERE telegram_id = ?",
        [telegramId]
      );
      return rows[0] || null;
    } catch (error) {
      console.error('Error getting telegram user:', error);
      return null;
    }
  }

  async createTelegramUser(telegramId: number, userData: any): Promise<string | null> {
    try {
      const result = await this.database.query(
        `INSERT INTO telegram_users (telegram_id, username, first_name, last_name) 
         VALUES (?, ?, ?, ?)`,
        [telegramId, userData.username, userData.first_name, userData.last_name]
      );
      return result.insertId;
    } catch (error) {
      console.error('Error creating telegram user:', error);
      return null;
    }
  }

  async updateTelegramUser(telegramId: number, updates: Partial<TelegramUser>): Promise<boolean> {
    try {
      const fields = Object.keys(updates).map(key => `${key} = ?`).join(", ");
      const values = Object.values(updates);
      values.push(telegramId);

      await this.database.query(
        `UPDATE telegram_users SET ${fields} WHERE telegram_id = ?`,
        values
      );
      return true;
    } catch (error) {
      console.error('Error updating telegram user:', error);
      return false;
    }
  }

  async checkEmailExists(email: string): Promise<boolean> {
    try {
      const rows = await this.database.query(
        "SELECT id FROM users WHERE email = ?",
        [email]
      );
      return rows.length > 0;
    } catch (error) {
      console.error('Error checking email:', error);
      return false;
    }
  }

  async linkTelegramToWebUser(telegramId: number, email: string, password: string): Promise<AuthResult> {
    try {
      const webUsers = await this.database.query(
        "SELECT * FROM users WHERE email = ?",
        [email]
      );

      if (webUsers.length === 0) {
        return { success: false, message: "No account found with this email" };
      }
      
      const webUser = webUsers[0];

      const isValidPassword = await bcrypt.compare(password, webUser.password_hash);
      if (!isValidPassword) {
        return { success: false, message: "Invalid password" };
      }

      await this.updateTelegramUser(telegramId, {
        user_id: webUser.id,
        is_registered: true,
        registration_step: 'complete'
      });

      return { success: true, message: "Account linked successfully!", user: webUser };
    } catch (error) {
      console.error('Error linking accounts:', error);
      return { success: false, message: "Error linking accounts" };
    }
  }

  async createNewWebUser(telegramId: number, email: string, password: string, fullName: string): Promise<AuthResult> {
    try {
      const emailExists = await this.checkEmailExists(email);
      if (emailExists) {
        return { success: false, message: "Email already registered", shouldLogin: true };
      }

      const passwordHash = await bcrypt.hash(password, 12);

      const result = await this.database.query(
        `INSERT INTO users (username, email, password_hash, full_name)
         VALUES (?, ?, ?, ?)`,
        [email.split("@")[0], email, passwordHash, fullName]
      );
      const userId = result.insertId;

      await this.updateTelegramUser(telegramId, {
        user_id: userId,
        is_registered: true,
        registration_step: 'complete'
      });

      return { success: true, message: "Account created and linked successfully!", userId };
    } catch (error) {
      console.error('Error creating web user:', error);
      return { success: false, message: "Error creating account" };
    }
  }

  async logoutTelegramUser(telegramId: number): Promise<boolean> {
    try {
      await this.database.query(
        `UPDATE telegram_users SET 
         user_id = NULL,
         is_registered = FALSE, 
         registration_step = "start",
         registration_mode = "login",
         temp_email = NULL,
         temp_password = NULL
         WHERE telegram_id = ?`,
        [telegramId]
      );

      await this.database.query(
        "DELETE FROM telegram_sessions WHERE telegram_id = ?",
        [telegramId]
      );

      return true;
    } catch (error) {
      console.error('Error logging out telegram user:', error);
      return false;
    }
  }

  async getWebUserById(userId: number): Promise<WebUser | null> {
    try {
      const rows = await this.database.query(
        "SELECT * FROM users WHERE id = ?",
        [userId]
      );
      return rows[0] || null;
    } catch (error) {
      console.error('Error getting web user:', error);
      return null;
    }
  }

  generateJWT(userId: number, telegramId: number): string {
    return jwt.sign(
      { userId, telegramId },
      this.jwtSecret,
      { expiresIn: '7d' }
    );
  }

  verifyJWT(token: string): any {
    try {
      return jwt.verify(token, this.jwtSecret);
    } catch (error) {
      return null;
    }
  }
}

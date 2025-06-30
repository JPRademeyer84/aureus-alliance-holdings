import mysql from 'mysql2/promise';
import dotenv from 'dotenv';

dotenv.config();

export interface DatabaseConfig {
  host: string;
  port: number;
  user: string;
  password: string;
  database: string;
  connectionLimit: number;
  acquireTimeout: number;
  timeout: number;
}

export const dbConfig: DatabaseConfig = {
  host: process.env.DB_HOST || 'localhost',
  port: parseInt(process.env.DB_PORT || '3506'), // Custom port 3506, NOT 3306
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASSWORD || '',
  database: process.env.DB_NAME || 'aureus_angels',
  connectionLimit: 10,
  acquireTimeout: 60000,
  timeout: 60000,
};

class Database {
  private pool: mysql.Pool;

  constructor() {
    this.pool = mysql.createPool(dbConfig);
    console.log(`📊 Database configured for ${dbConfig.host}:${dbConfig.port}/${dbConfig.database}`);
  }

  async getConnection(): Promise<mysql.PoolConnection> {
    try {
      const connection = await this.pool.getConnection();
      console.log('✅ Database connection established');
      return connection;
    } catch (error) {
      console.error('❌ Database connection failed:', error);
      throw error;
    }
  }

  async query(sql: string, params?: any[]): Promise<any> {
    const connection = await this.getConnection();
    try {
      const [results] = await connection.execute(sql, params);
      return results;
    } catch (error) {
      console.error('❌ Database query failed:', error);
      throw error;
    } finally {
      connection.release();
    }
  }

  async testConnection(): Promise<boolean> {
    try {
      const connection = await this.getConnection();
      await connection.ping();
      connection.release();
      console.log('✅ Database connection test successful');
      return true;
    } catch (error) {
      console.error('❌ Database connection test failed:', error);
      return false;
    }
  }

  async createBotTables(): Promise<void> {
    try {
      // Create telegram_users table
      await this.query(`
        CREATE TABLE IF NOT EXISTS telegram_users (
          id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
          user_id VARCHAR(36) NOT NULL,
          telegram_id BIGINT UNIQUE NOT NULL,
          username VARCHAR(255),
          first_name VARCHAR(255),
          last_name VARCHAR(255),
          is_active BOOLEAN DEFAULT TRUE,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
          INDEX idx_telegram_id (telegram_id),
          INDEX idx_user_id (user_id)
        )
      `);

      // Create telegram_sessions table
      await this.query(`
        CREATE TABLE IF NOT EXISTS telegram_sessions (
          id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
          telegram_id BIGINT NOT NULL,
          session_data TEXT,
          expires_at TIMESTAMP,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          INDEX idx_telegram_id (telegram_id),
          INDEX idx_expires_at (expires_at)
        )
      `);

      // Create bot_notifications table
      await this.query(`
        CREATE TABLE IF NOT EXISTS bot_notifications (
          id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
          telegram_id BIGINT NOT NULL,
          message TEXT NOT NULL,
          type ENUM('info', 'warning', 'success', 'error') DEFAULT 'info',
          is_sent BOOLEAN DEFAULT FALSE,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          INDEX idx_telegram_id (telegram_id),
          INDEX idx_is_sent (is_sent)
        )
      `);

      console.log('✅ Bot-specific database tables created successfully');
    } catch (error) {
      console.error('❌ Failed to create bot tables:', error);
      throw error;
    }
  }

  async close(): Promise<void> {
    await this.pool.end();
    console.log('📊 Database connection pool closed');
  }
}

export default Database;

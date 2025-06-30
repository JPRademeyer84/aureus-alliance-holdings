import mysql from 'mysql2/promise';

export interface DatabaseConfig {
  host: string;
  port: number;
  user: string;
  password: string;
  database: string;
}

export class Database {
  private connection: mysql.Connection | null = null;
  private config: DatabaseConfig;

  constructor() {
    this.config = {
      host: process.env.DB_HOST || 'localhost',
      port: parseInt(process.env.DB_PORT || '3506'),
      user: process.env.DB_USER || 'root',
      password: process.env.DB_PASSWORD || '',
      database: process.env.DB_NAME || 'aureus_angels'
    };
  }

  async connect(): Promise<boolean> {
    try {
      this.connection = await mysql.createConnection(this.config);
      console.log('✅ Database connected successfully!');
      return true;
    } catch (error) {
      console.error('❌ Database connection failed:', error);
      return false;
    }
  }

  async testConnection(): Promise<boolean> {
    try {
      if (!this.connection) {
        return await this.connect();
      }
      await this.connection.ping();
      return true;
    } catch (error) {
      console.error('❌ Database test failed:', error);
      return false;
    }
  }

  async query(sql: string, params: any[] = []): Promise<any> {
    try {
      if (!this.connection) {
        await this.connect();
      }
      const [rows] = await this.connection!.execute(sql, params);
      return rows;
    } catch (error) {
      console.error('❌ Database query failed:', error);
      throw error;
    }
  }

  async createBotTables(): Promise<void> {
    try {
      // Check users table structure first
      const userTableStructure = await this.query("DESCRIBE users");
      console.log('📋 Users table structure:');
      userTableStructure.forEach((row: any) => {
        console.log(`  ${row.Field}: ${row.Type} ${row.Key ? `(${row.Key})` : ""}`);
      });

      // Determine user_id type from users table
      const idField = userTableStructure.find((row: any) => row.Field === 'id');
      const userIdType = idField ? idField.Type : 'int(11)';
      console.log(`📋 Using user_id type: ${userIdType}`);

      // Create telegram_users table
      await this.query(`
        CREATE TABLE IF NOT EXISTS telegram_users (
          id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
          user_id ${userIdType} NULL,
          telegram_id BIGINT UNIQUE NOT NULL,
          username VARCHAR(255),
          first_name VARCHAR(255),
          last_name VARCHAR(255),
          is_active BOOLEAN DEFAULT TRUE,
          is_registered BOOLEAN DEFAULT FALSE,
          registration_step ENUM('start', 'email', 'password', 'complete') DEFAULT 'start',
          registration_mode ENUM('login', 'register') DEFAULT 'login',
          temp_email VARCHAR(255) NULL,
          temp_password VARCHAR(255) NULL,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
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
      console.error('❌ Error creating bot tables:', error);
      throw error;
    }
  }

  async close(): Promise<void> {
    if (this.connection) {
      await this.connection.end();
      this.connection = null;
      console.log('✅ Database connection closed');
    }
  }
}

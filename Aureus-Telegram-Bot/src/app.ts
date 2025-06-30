import { Telegraf, Context } from 'telegraf';
import dotenv from 'dotenv';
import { Database } from './database/connection';
import { AuthService } from './services/authService';
import { InvestmentService } from './services/investmentService';
import { setupCommands } from './bot/commands';
import { setupMiddleware } from './bot/middleware';
import { setupCallbacks } from './bot/callbacks';

// Load environment variables
dotenv.config();

// Bot configuration
const BOT_TOKEN = process.env.BOT_TOKEN || "8015476800:AAGMH8HMXRurphYHRQDJdeHLO10ghZVzBt8";
const isDevelopment = process.env.NODE_ENV !== 'production';

// Custom context interface
export interface BotContext extends Context {
  telegramUser?: any;
  webUser?: any;
  session?: any;
}

class AureusBot {
  private bot: Telegraf<BotContext>;
  private database: Database;
  private authService: AuthService;
  private investmentService: InvestmentService;

  constructor() {
    this.bot = new Telegraf<BotContext>(BOT_TOKEN);
    this.database = new Database();
    this.authService = new AuthService(this.database);
    this.investmentService = new InvestmentService(this.database);

    console.log('🚀 Initializing Aureus Africa Telegram Bot...');
    this.setupBot();
  }

  private setupBot(): void {
    // Error handling
    this.bot.catch((err, ctx) => {
      console.error('❌ Bot error:', err);
      ctx.reply('Sorry, something went wrong. Please try again later.');
    });

    // Setup middleware first
    setupMiddleware(this.bot, this.authService);

    // Setup commands
    setupCommands(this.bot, this.authService, this.investmentService);

    // Setup callback handlers
    setupCallbacks(this.bot, this.authService, this.investmentService);
  }

  public async start(): Promise<void> {
    try {
      // Test database connection
      console.log('🔍 Testing database connection...');
      const isDbConnected = await this.database.testConnection();

      if (!isDbConnected) {
        throw new Error('Database connection failed');
      }

      // Create bot-specific tables
      console.log('🏗️ Creating bot-specific database tables...');
      await this.database.createBotTables();

      // Start bot
      if (isDevelopment) {
        console.log('🔄 Starting bot in polling mode (development)...');
        await this.bot.launch();
      } else {
        console.log('🌐 Starting bot in webhook mode (production)...');
        await this.bot.launch();
      }

      console.log('✅ Aureus Africa Bot is running!');
      console.log(`🤖 Bot username: @aureus_africa_bot`);
      console.log(`🌐 Environment: ${isDevelopment ? 'development' : 'production'}`);

    } catch (error) {
      console.error('❌ Failed to start bot:', error);
      process.exit(1);
    }
  }

  public async stop(): Promise<void> {
    console.log('🛑 Stopping bot...');
    this.bot.stop();
    await this.database.close();
    console.log('✅ Bot stopped successfully');
  }
}

// Create and start bot
const aureusBot = new AureusBot();

// Graceful shutdown
process.once('SIGINT', () => aureusBot.stop());
process.once('SIGTERM', () => aureusBot.stop());

// Start the bot
aureusBot.start().catch((error) => {
  console.error('❌ Fatal error:', error);
  process.exit(1);
});

export default AureusBot;

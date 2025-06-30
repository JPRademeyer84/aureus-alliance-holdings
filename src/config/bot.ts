import dotenv from 'dotenv';

dotenv.config();

export interface BotConfig {
  token: string;
  username: string;
  webhookUrl?: string;
  port: number;
  environment: 'development' | 'production';
  logLevel: string;
  rateLimit: {
    window: number;
    maxRequests: number;
  };
  fileUpload: {
    maxSize: number;
    uploadPath: string;
    certificatePath: string;
  };
}

export const botConfig: BotConfig = {
  token: process.env.BOT_TOKEN || '',
  username: process.env.BOT_USERNAME || 'aureus_africa_bot',
  webhookUrl: process.env.WEBHOOK_URL,
  port: parseInt(process.env.PORT || '3000'),
  environment: (process.env.NODE_ENV as 'development' | 'production') || 'development',
  logLevel: process.env.LOG_LEVEL || 'debug',
  rateLimit: {
    window: parseInt(process.env.RATE_LIMIT_WINDOW || '60000'),
    maxRequests: parseInt(process.env.RATE_LIMIT_MAX_REQUESTS || '30'),
  },
  fileUpload: {
    maxSize: parseInt(process.env.MAX_FILE_SIZE || '10485760'), // 10MB
    uploadPath: process.env.UPLOAD_PATH || './uploads',
    certificatePath: process.env.CERTIFICATE_PATH || './certificates',
  },
};

// Validation
if (!botConfig.token) {
  throw new Error('BOT_TOKEN is required in environment variables');
}

export const isDevelopment = botConfig.environment === 'development';
export const isProduction = botConfig.environment === 'production';

console.log(`🤖 Bot configured: @${botConfig.username} (${botConfig.environment})`);

export default botConfig;

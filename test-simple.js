const { Telegraf } = require('telegraf');
require('dotenv').config();

console.log('🚀 Starting Aureus Africa Telegram Bot...');

// Bot configuration
const BOT_TOKEN = '8015476800:AAGMH8HMXRurphYHRQDJdeHLO10ghZVzBt8';

// Create bot instance
const bot = new Telegraf(BOT_TOKEN);

// Bot commands
bot.start(async (ctx) => {
  const user = ctx.from;
  console.log(`👋 New user started bot: ${user.first_name} (@${user.username})`);
  
  const welcomeMessage = `
🌟 *Welcome to Aureus Angel Alliance!* 🌟

Hello ${user.first_name}! I'm your personal investment assistant.

🔹 *What I can help you with:*
• View investment packages
• Make investments  
• Track your portfolio
• Manage payments
• Access referral system
• Generate certificates

🔹 *Getting Started:*
Use /menu to see all available options
Use /help for detailed information

Ready to start your investment journey? 💎
  `;
  
  await ctx.replyWithMarkdown(welcomeMessage);
});

bot.help(async (ctx) => {
  const helpMessage = `
📚 *Aureus Africa Bot Help* 📚

🔹 *Basic Commands:*
/start - Welcome message
/menu - Main navigation menu
/help - This help message

🔹 *Investment Commands:*
/packages - View investment packages
/invest - Start investment process
/portfolio - View your investments

🔹 *Support:*
/support - Contact support

Need more help? Contact our support team! 💬
  `;
  
  await ctx.replyWithMarkdown(helpMessage);
});

bot.command('menu', async (ctx) => {
  await ctx.reply('🏠 Main Menu - Coming soon! Use /help for available commands.');
});

// Error handling
bot.catch((err, ctx) => {
  console.error('❌ Bot error:', err);
  ctx.reply('Sorry, something went wrong. Please try again later.');
});

// Start bot
async function startBot() {
  try {
    console.log('🔄 Starting bot in polling mode...');
    await bot.launch();
    console.log('✅ Aureus Africa Bot is running!');
    console.log(`🤖 Bot username: @aureus_africa_bot`);
  } catch (error) {
    console.error('❌ Failed to start bot:', error);
    process.exit(1);
  }
}

// Graceful shutdown
process.once('SIGINT', () => {
  console.log('🛑 Stopping bot...');
  bot.stop('SIGINT');
});

process.once('SIGTERM', () => {
  console.log('🛑 Stopping bot...');
  bot.stop('SIGTERM');
});

// Start the bot
startBot();

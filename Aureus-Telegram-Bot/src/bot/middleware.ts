import { Telegraf } from 'telegraf';
import { BotContext } from '../app';
import { AuthService } from '../services/authService';

export function setupMiddleware(bot: Telegraf<BotContext>, authService: AuthService): void {
  // Logging middleware
  bot.use(async (ctx, next) => {
    const start = Date.now();
    const user = ctx.from;
    const messageText = ctx.message?.text || ctx.callbackQuery?.data || "non-text";
    console.log(`📨 Message from ${user?.first_name} (@${user?.username}): ${messageText}`);

    await next();

    const responseTime = Date.now() - start;
    console.log(`⏱️ Response time: ${responseTime}ms`);
  });

  // Authentication middleware
  bot.use(async (ctx, next) => {
    const telegramId = ctx.from?.id;
    if (!telegramId) {
      await next();
      return;
    }

    let telegramUser = await authService.getTelegramUser(telegramId);

    if (!telegramUser) {
      await authService.createTelegramUser(telegramId, ctx.from);
      telegramUser = await authService.getTelegramUser(telegramId);
    }

    ctx.telegramUser = telegramUser;

    // If user is registered, get their web user data
    if (telegramUser?.is_registered && telegramUser.user_id) {
      const webUser = await authService.getWebUserById(telegramUser.user_id);
      ctx.webUser = webUser;
    }

    await next();
  });

  // Session middleware (placeholder for future implementation)
  bot.use(async (ctx, next) => {
    ctx.session = {};
    await next();
  });
}

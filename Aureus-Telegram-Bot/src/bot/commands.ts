import { Telegraf } from 'telegraf';
import { BotContext } from '../app';
import { AuthService } from '../services/authService';
import { InvestmentService } from '../services/investmentService';

export function setupCommands(
  bot: Telegraf<BotContext>, 
  authService: AuthService, 
  investmentService: InvestmentService
): void {

  // Start command
  bot.start(async (ctx) => {
    const user = ctx.from;
    const telegramUser = ctx.telegramUser;

    console.log(`👋 User started bot: ${user?.first_name} (@${user?.username}) - Registered: ${telegramUser?.is_registered}`);

    if (telegramUser?.is_registered) {
      const welcomeMessage = `🌟 *Welcome back, ${user?.first_name}!* 🌟

Your account is linked and ready to use.

🔹 *Quick Actions:*
• /menu - Full menu
• /profile - Your profile
• /logout - Logout from account

Ready to continue your investment journey? 💎`;

      await ctx.replyWithMarkdown(welcomeMessage);
    } else {
      const welcomeMessage = `🌟 *Welcome to Aureus Angel Alliance!* 🌟

Hello ${user?.first_name}! I am your personal investment assistant.

To get started, I need to link your Telegram account to our platform.

🔹 *Choose an option:*`;

      const keyboard = {
        inline_keyboard: [
          [
            { text: "🔑 Login to Existing Account", callback_data: "auth_login" },
            { text: "📝 Create New Account", callback_data: "auth_register" }
          ],
          [
            { text: "❓ Help", callback_data: "auth_help" }
          ]
        ]
      };

      await ctx.replyWithMarkdown(welcomeMessage, { reply_markup: keyboard });
    }
  });

  // Login command
  bot.command("login", async (ctx) => {
    const telegramUser = ctx.telegramUser;

    if (telegramUser?.is_registered) {
      await ctx.reply("✅ You are already logged in! Use /logout to logout first.");
      return;
    }

    await authService.updateTelegramUser(ctx.from!.id, {
      registration_step: "email",
      registration_mode: "login"
    });

    await ctx.reply(`🔑 *Account Login*

Please enter your email address:`, { parse_mode: "Markdown" });
  });

  // Register command
  bot.command("register", async (ctx) => {
    const telegramUser = ctx.telegramUser;

    if (telegramUser?.is_registered) {
      await ctx.reply("✅ You already have an account! Use /logout to logout first.");
      return;
    }

    await authService.updateTelegramUser(ctx.from!.id, { 
      registration_step: "email",
      registration_mode: "register"
    });

    await ctx.reply(`📝 *Create New Account*

Please enter your email address:`, { parse_mode: "Markdown" });
  });

  // Logout command
  bot.command("logout", async (ctx) => {
    const telegramUser = ctx.telegramUser;

    if (!telegramUser?.is_registered) {
      await ctx.reply("❌ You are not logged in!");
      return;
    }

    const logoutMessage = `🚪 *Logout Confirmation*

Are you sure you want to logout from your account?`;

    const keyboard = {
      inline_keyboard: [
        [
          { text: "✅ Yes, Logout", callback_data: "confirm_logout" },
          { text: "❌ Cancel", callback_data: "cancel_logout" }
        ]
      ]
    };

    await ctx.replyWithMarkdown(logoutMessage, { reply_markup: keyboard });
  });

  // Menu command
  bot.command("menu", async (ctx) => {
    console.log(`📋 MENU COMMAND RECEIVED from ${ctx.from?.first_name} (ID: ${ctx.from?.id})`);

    try {
      const telegramUser = ctx.telegramUser;
      console.log(`📋 User registration status: ${telegramUser?.is_registered}`);

      if (!telegramUser?.is_registered) {
        console.log(`📋 User not authenticated, sending auth message`);
        await ctx.reply("🔐 Please login or register first using /start");
        return;
      }

      console.log(`📋 User authenticated, sending menu...`);

      const menuMessage = `🏠 *Main Menu* 🏠

Choose an option below:`;

      const keyboard = {
        inline_keyboard: [
          [
            { text: "💰 Investment", callback_data: "menu_investment" },
            { text: "📊 Portfolio", callback_data: "menu_portfolio" }
          ],
          [
            { text: "💳 Payments", callback_data: "menu_payments" },
            { text: "👥 Referrals", callback_data: "menu_referrals" }
          ],
          [
            { text: "🎫 NFT & Certificates", callback_data: "menu_nft" },
            { text: "👤 Profile", callback_data: "menu_profile" }
          ],
          [
            { text: "❓ Help & Support", callback_data: "menu_support" },
            { text: "🚪 Logout", callback_data: "menu_logout" }
          ]
        ]
      };

      await ctx.replyWithMarkdown(menuMessage, { reply_markup: keyboard });
      console.log(`✅ Menu sent successfully to ${ctx.from?.first_name}`);

    } catch (error) {
      console.error("❌ MENU ERROR:", error);
      await ctx.reply("❌ Sorry, there was an error loading the menu. Please try again or contact support.");
    }
  });

  // Profile command
  bot.command("profile", async (ctx) => {
    const telegramUser = ctx.telegramUser;

    if (!telegramUser?.is_registered) {
      await ctx.reply("🔐 Please login or register first using /start");
      return;
    }

    try {
      const webUser = ctx.webUser;

      if (!webUser) {
        await ctx.reply("❌ Account not found. Please contact support.");
        return;
      }

      const profileMessage = `👤 *Your Profile*

📧 Email: ${webUser.email}
👤 Name: ${webUser.full_name || "Not set"}
🆔 Username: ${webUser.username}
📅 Member since: ${new Date(webUser.created_at).toLocaleDateString()}
🔄 Status: ${webUser.is_active ? "Active" : "Inactive"}
✅ Email Verified: ${webUser.email_verified ? "Yes" : "No"}
🔐 KYC Status: ${webUser.kyc_status}

📱 Telegram: Linked
🆔 Telegram ID: ${ctx.from?.id}

Use /logout to unlink your account.`;

      await ctx.replyWithMarkdown(profileMessage);
    } catch (error) {
      console.error("Error getting profile:", error);
      await ctx.reply("❌ Error loading profile. Please try again.");
    }
  });

  // Packages command
  bot.command("packages", async (ctx) => {
    const telegramUser = ctx.telegramUser;

    if (!telegramUser?.is_registered) {
      await ctx.reply("🔐 Please login or register first using /start");
      return;
    }

    try {
      const packages = await investmentService.getInvestmentPackages();

      if (packages.length === 0) {
        await ctx.reply("❌ No investment packages available at the moment.");
        return;
      }

      const packageMessage = `💎 *Available Investment Packages* 💎

Choose a package to view details:`;

      const keyboard = {
        inline_keyboard: packages.map(pkg => [
          { text: `${pkg.name} - ${investmentService.formatCurrency(pkg.price)}`, callback_data: `package_${pkg.id}` }
        ])
      };

      await ctx.replyWithMarkdown(packageMessage, { reply_markup: keyboard });
    } catch (error) {
      console.error("Error getting packages:", error);
      await ctx.reply("❌ Error loading packages. Please try again.");
    }
  });

  // Portfolio command
  bot.command("portfolio", async (ctx) => {
    const telegramUser = ctx.telegramUser;

    if (!telegramUser?.is_registered || !telegramUser.user_id) {
      await ctx.reply("🔐 Please login or register first using /start");
      return;
    }

    try {
      const stats = await investmentService.getUserInvestmentStats(telegramUser.user_id);
      const investments = await investmentService.getUserInvestments(telegramUser.user_id);

      let portfolioMessage = investmentService.formatPortfolioStats(stats);

      if (investments.length > 0) {
        portfolioMessage += "\n\n📋 *Recent Investments:*\n\n";
        const recentInvestments = investments.slice(0, 5);
        
        recentInvestments.forEach((investment, index) => {
          portfolioMessage += `${index + 1}. ${investmentService.formatInvestmentSummary(investment)}\n\n`;
        });

        if (investments.length > 5) {
          portfolioMessage += `... and ${investments.length - 5} more investments`;
        }
      } else {
        portfolioMessage += "\n\n📋 *No investments yet*\nUse /packages to view available investment opportunities!";
      }

      await ctx.replyWithMarkdown(portfolioMessage);
    } catch (error) {
      console.error("Error getting portfolio:", error);
      await ctx.reply("❌ Error loading portfolio. Please try again.");
    }
  });

  // Test database command (development only)
  if (process.env.NODE_ENV !== 'production') {
    bot.command("testdb", async (ctx) => {
      try {
        const database = authService['database']; // Access private property for testing
        await database.query("SELECT 1");
        await ctx.reply("✅ Database connection successful!");
      } catch (error) {
        await ctx.reply(`❌ Database error: ${error}`);
      }
    });
  }

  // Help command
  bot.help(async (ctx) => {
    const telegramUser = ctx.telegramUser;

    if (!telegramUser?.is_registered) {
      const helpMessage = `📚 *Aureus Africa Bot Help* 📚

🔹 *Getting Started:*
/start - Begin registration or login
/login - Login to existing account
/register - Create new account

🔹 *Support:*
/help - This help message

Please complete registration to access all features! 💎`;

      await ctx.replyWithMarkdown(helpMessage);
    } else {
      const helpMessage = `📚 *Aureus Africa Bot Help* 📚

🔹 *Basic Commands:*
/start - Welcome message
/menu - Main navigation menu
/profile - Your profile information
/logout - Logout from account
/help - This help message

🔹 *Investment Commands:*
/packages - View investment packages
/portfolio - View your investments

Need more help? Contact our support team! 💬`;

      await ctx.replyWithMarkdown(helpMessage);
    }
  });
}

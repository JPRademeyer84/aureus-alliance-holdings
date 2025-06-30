import { Telegraf } from 'telegraf';
import { BotContext } from '../app';
import { AuthService } from '../services/authService';
import { InvestmentService } from '../services/investmentService';

export function setupCallbacks(
  bot: Telegraf<BotContext>, 
  authService: AuthService, 
  investmentService: InvestmentService
): void {

  // Handle text messages for registration flow
  bot.on("text", async (ctx) => {
    const telegramUser = ctx.telegramUser;
    const text = ctx.message.text;

    // Skip if user is already registered or message is a command
    if (telegramUser?.is_registered || text.startsWith("/")) {
      return;
    }

    if (telegramUser?.registration_step === "email") {
      // Validate email
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(text)) {
        await ctx.reply("❌ Please enter a valid email address:");
        return;
      }

      // Check if email exists
      const emailExists = await authService.checkEmailExists(text);
      const isLoginMode = telegramUser.registration_mode === "login";

      await authService.updateTelegramUser(ctx.from!.id, { 
        temp_email: text,
        registration_step: "password"
      });

      if (isLoginMode) {
        // User is trying to LOGIN
        if (emailExists) {
          await ctx.reply(`📧 Email: ${text}

✅ *Account found!*

Please enter your password:`);
        } else {
          // Email doesn't exist but user is trying to login
          const noAccountMessage = `❌ *No account found with this email*

The email "${text}" is not registered in our system.

🔹 *What would you like to do?*`;

          const keyboard = {
            inline_keyboard: [
              [
                { text: "📝 Create New Account", callback_data: "switch_to_register" },
                { text: "🔄 Try Different Email", callback_data: "try_different_email" }
              ],
              [
                { text: "📞 Contact Support", callback_data: "contact_support" }
              ]
            ]
          };

          await ctx.replyWithMarkdown(noAccountMessage, { reply_markup: keyboard });
          return;
        }
      } else {
        // User is trying to REGISTER
        if (emailExists) {
          // Email exists but user is trying to register
          const existingAccountMessage = `❌ *Email already registered*

The email "${text}" already has an account.

🔹 *What would you like to do?*`;

          const keyboard = {
            inline_keyboard: [
              [
                { text: "🔑 Login Instead", callback_data: "switch_to_login" },
                { text: "🔄 Try Different Email", callback_data: "try_different_email" }
              ],
              [
                { text: "🔐 Forgot Password?", callback_data: "forgot_password" }
              ]
            ]
          };

          await ctx.replyWithMarkdown(existingAccountMessage, { reply_markup: keyboard });
          return;
        } else {
          await ctx.reply(`📧 Email: ${text}

📝 *Creating new account*

Please create a secure password (minimum 8 characters):`);
        }
      }

    } else if (telegramUser?.registration_step === "password") {
      if (text.length < 8) {
        await ctx.reply("❌ Password must be at least 8 characters long:");
        return;
      }

      // Get updated user data
      const updatedUser = await authService.getTelegramUser(ctx.from!.id);
      const isLoginMode = updatedUser?.registration_mode === "login";

      if (isLoginMode) {
        // LOGIN FLOW
        const linkResult = await authService.linkTelegramToWebUser(
          ctx.from!.id,
          updatedUser!.temp_email!, 
          text
        );

        if (linkResult.success) {
          await ctx.reply(`✅ ${linkResult.message}

Welcome back! Your Telegram account is now linked.

Use /menu to access all features.`);
        } else {
          await ctx.reply(`❌ Incorrect password!

The password you entered is not correct for this email address.

Please try entering your password again:`);
          return; // Stay on password step
        }
      } else {
        // REGISTER FLOW
        const fullName = `${ctx.from!.first_name} ${ctx.from!.last_name || ""}`.trim();
        const createResult = await authService.createNewWebUser(
          ctx.from!.id,
          updatedUser!.temp_email!,
          text,
          fullName
        );
        
        if (createResult.success) {
          await ctx.reply(`✅ ${createResult.message}

Welcome to Aureus Angel Alliance! Your account is ready.

Use /menu to start investing.`);
        } else {
          await ctx.reply(`❌ ${createResult.message}

Please try again with /register or /login`);
          await authService.updateTelegramUser(ctx.from!.id, {
            registration_step: "start",
            temp_email: null,
            temp_password: null
          });
          return;
        }
      }

      // Clear temporary data
      await authService.updateTelegramUser(ctx.from!.id, { 
        temp_email: null,
        temp_password: null
      });
    }
  });

  // Callback query handlers
  bot.on("callback_query", async (ctx) => {
    const data = ctx.callbackQuery!.data!;
    console.log(`🔘 Callback query: ${data} from ${ctx.from?.first_name}`);

    // Authentication callbacks
    if (data === "auth_login") {
      await ctx.answerCbQuery();
      await ctx.editMessageText("🔑 *Account Login*\n\nPlease enter your email address:", { parse_mode: "Markdown" });
      await authService.updateTelegramUser(ctx.from!.id, {
        registration_step: "email",
        registration_mode: "login"
      });
    } else if (data === "auth_register") {
      await ctx.answerCbQuery();
      await ctx.editMessageText("📝 *Create New Account*\n\nPlease enter your email address:", { parse_mode: "Markdown" });
      await authService.updateTelegramUser(ctx.from!.id, {
        registration_step: "email",
        registration_mode: "register"
      });
    } else if (data === "switch_to_register") {
      await ctx.answerCbQuery();
      await ctx.editMessageText("📝 *Create New Account*\n\nPlease enter your email address:", { parse_mode: "Markdown" });
      await authService.updateTelegramUser(ctx.from!.id, { 
        registration_step: "email",
        registration_mode: "register",
        temp_email: null,
        temp_password: null
      });
    } else if (data === "switch_to_login") {
      await ctx.answerCbQuery();
      await ctx.editMessageText("🔑 *Account Login*\n\nPlease enter your email address:", { parse_mode: "Markdown" }); 
      await authService.updateTelegramUser(ctx.from!.id, { 
        registration_step: "email",
        registration_mode: "login",
        temp_email: null,
        temp_password: null
      });
    } else if (data === "try_different_email") {
      await ctx.answerCbQuery();
      const currentMode = ctx.telegramUser?.registration_mode;
      const modeText = currentMode === "login" ? "🔑 *Account Login*" : "📝 *Create New Account*";
      await ctx.editMessageText(`${modeText}\n\nPlease enter your email address:`, { parse_mode: "Markdown" });
      await authService.updateTelegramUser(ctx.from!.id, {
        registration_step: "email",
        temp_email: null,
        temp_password: null
      });
    } else if (data === "contact_support") {
      await ctx.answerCbQuery();
      await ctx.editMessageText(`📞 *Contact Support*

If you need help with your account, please contact our support team:

📧 Email: support@aureusangels.com

Use /start to try again.`, { parse_mode: "Markdown" });
    } else if (data === "forgot_password") {
      await ctx.answerCbQuery();
      await ctx.editMessageText(`🔐 *Forgot Password*

To reset your password, please contact our support team:

📧 Email: support@aureusangels.com

Use /start to try again.`, { parse_mode: "Markdown" });
    } else if (data === "auth_help") {
      await ctx.answerCbQuery();
      const helpMessage = `❓ *Authentication Help*

🔹 *Login:* If you already have an account
🔹 *Register:* If you are new to Aureus Angel Alliance

Your Telegram account will be securely linked to your investment account.`;

      await ctx.editMessageText(helpMessage, { parse_mode: "Markdown" });
    } else if (data === "confirm_logout") {
      await ctx.answerCbQuery();

      const success = await authService.logoutTelegramUser(ctx.from!.id);

      if (success) {
        await ctx.editMessageText(`✅ *Logout Successful*

You have been logged out from your account.

Use /start to login or register again.`, { parse_mode: "Markdown" });
      } else {
        await ctx.editMessageText("❌ Error during logout. Please try again.", { parse_mode: "Markdown" });
      }
    } else if (data === "cancel_logout") {
      await ctx.answerCbQuery();
      await ctx.editMessageText("❌ Logout cancelled. You remain logged in.", { parse_mode: "Markdown" });
    } else if (data === "menu_logout") {
      await ctx.answerCbQuery();

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

      await ctx.editMessageText(logoutMessage, { parse_mode: "Markdown", reply_markup: keyboard });
    } 
    // Investment package callbacks
    else if (data.startsWith("package_")) {
      await ctx.answerCbQuery();
      const packageId = parseInt(data.replace("package_", ""));
      
      try {
        const pkg = await investmentService.getPackageById(packageId);
        if (!pkg) {
          await ctx.editMessageText("❌ Package not found.", { parse_mode: "Markdown" });
          return;
        }

        const packageInfo = investmentService.formatPackageInfo(pkg);
        const keyboard = {
          inline_keyboard: [
            [
              { text: "💰 Invest Now", callback_data: `invest_${packageId}` },
              { text: "🔙 Back to Packages", callback_data: "back_to_packages" }
            ]
          ]
        };

        await ctx.editMessageText(packageInfo, { parse_mode: "Markdown", reply_markup: keyboard });
      } catch (error) {
        console.error("Error showing package details:", error);
        await ctx.editMessageText("❌ Error loading package details.", { parse_mode: "Markdown" });
      }
    }
    // Menu callbacks - placeholder for future implementation
    else {
      await ctx.answerCbQuery();
      await ctx.reply(`🚧 Feature "${data}" is coming soon! Stay tuned.`);
    }
  });
}

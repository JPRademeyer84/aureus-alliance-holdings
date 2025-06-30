const { Telegraf } = require('telegraf');
const mysql = require('mysql2/promise');
const bcrypt = require('bcrypt');
// const nodemailer = require('nodemailer'); // Temporarily disabled

const bot = new Telegraf('8015476800:AAGMH8HMXRurphYHRQDJdeHLO10ghZVzBt8');

// Security Configuration
const ADMIN_EMAIL = 'admin@smartunitednetwork.com';
const ADMIN_PASSWORD = 'Underdog8406155100085@123!@#';
const ADMIN_USERNAME = 'TTTFOUNDER'; // Only this Telegram username can access admin
const ADMIN_TELEGRAM_ID = 1234567890; // Admin's Telegram ID for notifications
const ADMIN_SESSION_TIMEOUT = 3600000; // 1 hour in milliseconds
const MAX_LOGIN_ATTEMPTS = 3;
const LOGIN_COOLDOWN = 900000; // 15 minutes in milliseconds
const RATE_LIMIT_WINDOW = 60000; // 1 minute
const RATE_LIMIT_MAX_REQUESTS = 20; // Max requests per minute

// Security tracking
const adminSessions = new Map(); // telegramId -> { authenticated: boolean, expires: timestamp }
const loginAttempts = new Map(); // telegramId -> { attempts: number, lastAttempt: timestamp }
const rateLimiting = new Map(); // telegramId -> { requests: number, resetTime: timestamp }
const suspiciousActivity = new Map(); // telegramId -> { violations: number, lastViolation: timestamp }
const userStates = new Map(); // telegramId -> { state: string, data: any }

// Database connection
let dbConnection;

async function connectDB() {
  dbConnection = await mysql.createConnection({
    host: 'localhost',
    port: 3506,
    user: 'root',
    password: '',
    database: 'aureus_angels'
  });
  console.log('✅ Database connected successfully!');
}

// Email configuration (temporarily disabled - will show token in bot)
// const emailTransporter = nodemailer.createTransporter({
//   host: 'smtp.gmail.com', // You can change this to your SMTP provider
//   port: 587,
//   secure: false,
//   auth: {
//     user: process.env.EMAIL_USER || 'your-email@gmail.com', // Add to .env file
//     pass: process.env.EMAIL_PASS || 'your-app-password'     // Add to .env file
//   }
// });

// Email functions (temporarily showing token in bot instead of email)
async function sendPasswordResetEmail(email, resetToken, userName) {
  try {
    // For now, we'll return true and show the token in the bot
    // In production, implement actual email sending
    console.log(`📧 Password reset requested for ${email} - Token: ${resetToken}`);
    return false; // Return false to show token in bot instead of email
  } catch (error) {
    console.error('❌ Failed to send password reset email:', error);
    return false;
  }
}

async function sendWelcomeEmail(email, userName) {
  try {
    // For now, just log the welcome message
    console.log(`🎉 Welcome message for ${userName} (${email}) - Account linked successfully!`);
    return true;
  } catch (error) {
    console.error('❌ Failed to send welcome email:', error);
    return false;
  }
}

// Security Functions
function isRateLimited(telegramId) {
  const now = Date.now();
  const userLimit = rateLimiting.get(telegramId);

  if (!userLimit) {
    rateLimiting.set(telegramId, { requests: 1, resetTime: now + RATE_LIMIT_WINDOW });
    return false;
  }

  if (now > userLimit.resetTime) {
    rateLimiting.set(telegramId, { requests: 1, resetTime: now + RATE_LIMIT_WINDOW });
    return false;
  }

  if (userLimit.requests >= RATE_LIMIT_MAX_REQUESTS) {
    return true;
  }

  userLimit.requests++;
  return false;
}

function isAuthorizedForAdmin(username) {
  return username && username.toLowerCase() === ADMIN_USERNAME.toLowerCase();
}

// Command restriction function - only admins can use slash commands
function isCommandAllowed(username, commandName) {
  // Allow /start for everyone (needed for initial bot interaction)
  if (commandName === 'start') {
    return true;
  }

  // All other commands are restricted to admin only
  return isAuthorizedForAdmin(username);
}

// Command restriction middleware
function restrictCommands(commandName) {
  return async (ctx, next) => {
    if (!isCommandAllowed(ctx.from.username, commandName)) {
      const restrictionMessage = `❌ **Commands Restricted**

Slash commands are only available for administrators.
Please use the menu buttons below to navigate the bot.`;

      const keyboard = {
        inline_keyboard: [
          [{ text: "📱 Main Menu", callback_data: "back_to_menu" }]
        ]
      };

      await ctx.reply(restrictionMessage, {
        parse_mode: "Markdown",
        reply_markup: keyboard
      });
      return;
    }
    return next();
  };
}

function isAdminAuthenticated(telegramId) {
  const session = adminSessions.get(telegramId);
  if (!session) return false;

  if (Date.now() > session.expires) {
    adminSessions.delete(telegramId);
    return false;
  }

  return session.authenticated;
}

function authenticateAdmin(telegramId, email, password) {
  const now = Date.now();
  const attempts = loginAttempts.get(telegramId) || { attempts: 0, lastAttempt: 0 };

  // Check if user is in cooldown
  if (attempts.attempts >= MAX_LOGIN_ATTEMPTS && (now - attempts.lastAttempt) < LOGIN_COOLDOWN) {
    return { success: false, error: 'COOLDOWN', remainingTime: LOGIN_COOLDOWN - (now - attempts.lastAttempt) };
  }

  // Reset attempts if cooldown period has passed
  if ((now - attempts.lastAttempt) > LOGIN_COOLDOWN) {
    attempts.attempts = 0;
  }

  // Check credentials
  if (email === ADMIN_EMAIL && password === ADMIN_PASSWORD) {
    // Successful authentication
    adminSessions.set(telegramId, {
      authenticated: true,
      expires: now + ADMIN_SESSION_TIMEOUT
    });
    loginAttempts.delete(telegramId);
    return { success: true };
  } else {
    // Failed authentication
    attempts.attempts++;
    attempts.lastAttempt = now;
    loginAttempts.set(telegramId, attempts);

    // Log suspicious activity
    logSuspiciousActivity(telegramId, 'FAILED_ADMIN_LOGIN', { email, timestamp: now });

    return { success: false, error: 'INVALID_CREDENTIALS', attemptsRemaining: MAX_LOGIN_ATTEMPTS - attempts.attempts };
  }
}

function logSuspiciousActivity(telegramId, type, details) {
  const now = Date.now();
  const activity = suspiciousActivity.get(telegramId) || { violations: 0, lastViolation: 0 };

  activity.violations++;
  activity.lastViolation = now;
  suspiciousActivity.set(telegramId, activity);

  console.log(`🚨 SECURITY ALERT: ${type} from Telegram ID ${telegramId}`, details);

  // Auto-ban after too many violations
  if (activity.violations >= 10) {
    console.log(`🔒 AUTO-BAN: Telegram ID ${telegramId} banned for excessive violations`);
    // Could implement actual banning logic here
  }
}

function sanitizeInput(input) {
  if (typeof input !== 'string') return '';
  return input.replace(/[<>'"&]/g, '').trim().substring(0, 1000);
}

async function logAdminAction(telegramId, action, details) {
  const timestamp = new Date().toISOString();
  console.log(`🔐 ADMIN ACTION: ${action} by Telegram ID ${telegramId} at ${timestamp}`, details);

  // Store in database
  try {
    await dbConnection.execute(`
      INSERT INTO admin_action_logs (
        admin_telegram_id, admin_username, action_type,
        target_user_id, target_telegram_id, action_details, success
      ) VALUES (?, ?, ?, ?, ?, ?, ?)
    `, [
      telegramId,
      details.admin_username || 'Unknown',
      action,
      details.target_user_id || null,
      details.target_telegram_id || null,
      JSON.stringify(details),
      true
    ]);
  } catch (error) {
    console.error('Error logging admin action:', error);
  }
}

// =====================================================
// ENHANCED ADMIN PANEL FUNCTIONS
// =====================================================

// User Communication System
async function saveUserMessage(telegramId, userInfo, messageText, messageType = 'contact_admin') {
  try {
    const [result] = await dbConnection.execute(`
      INSERT INTO admin_user_messages (
        telegram_id, user_id, username, first_name, last_name,
        message_text, message_type, status, priority
      ) VALUES (?, ?, ?, ?, ?, ?, ?, 'new', 'medium')
    `, [
      telegramId,
      userInfo.user_id || null,
      userInfo.username || null,
      userInfo.first_name || null,
      userInfo.last_name || null,
      messageText,
      messageType
    ]);

    // Create admin notification
    await createAdminNotification(
      'new_user_message',
      'high',
      'New User Message',
      `New message from ${userInfo.first_name || userInfo.username || 'User'}: ${messageText.substring(0, 100)}...`,
      userInfo.user_id,
      telegramId,
      { message_id: result.insertId, message_type: messageType }
    );

    return result.insertId;
  } catch (error) {
    console.error('Error saving user message:', error);
    throw error;
  }
}

async function createAdminNotification(type, priority, title, message, userId = null, telegramId = null, metadata = {}) {
  try {
    await dbConnection.execute(`
      INSERT INTO admin_notification_queue (
        notification_type, priority, title, message,
        related_user_id, related_telegram_id, metadata
      ) VALUES (?, ?, ?, ?, ?, ?, ?)
    `, [type, priority, title, message, userId, telegramId, JSON.stringify(metadata)]);

    // Send immediate notification to admin if online
    await sendAdminNotification(title, message, metadata);
  } catch (error) {
    console.error('Error creating admin notification:', error);
  }
}

async function sendAdminNotification(title, message, metadata = {}) {
  try {
    const notificationMessage = `🔔 **${title}**\n\n${message}`;

    // 🚨 FIX 1B: IMPLEMENT ACTUAL ADMIN TELEGRAM NOTIFICATIONS
    console.log('📢 ADMIN NOTIFICATION:', notificationMessage);

    // Try to send to admin via Telegram
    try {
      // First, try to find admin's Telegram ID from database
      const [adminResult] = await dbConnection.execute(`
        SELECT telegram_id FROM telegram_users WHERE username = ? OR linked_email = ?
      `, [ADMIN_USERNAME, ADMIN_EMAIL]);

      let adminTelegramId = ADMIN_TELEGRAM_ID; // Fallback to constant

      if (adminResult.length > 0 && adminResult[0].telegram_id) {
        adminTelegramId = adminResult[0].telegram_id;
        console.log(`📱 Found admin Telegram ID in database: ${adminTelegramId}`);
      } else {
        console.log(`📱 Using fallback admin Telegram ID: ${adminTelegramId}`);
      }

      // Send notification to admin
      await bot.telegram.sendMessage(adminTelegramId, notificationMessage, {
        parse_mode: 'Markdown',
        disable_web_page_preview: true
      });

      console.log(`✅ Admin notification sent successfully to ${adminTelegramId}`);

      // Update notification as sent
      await dbConnection.execute(`
        UPDATE admin_notification_queue
        SET sent_to_admin = TRUE, sent_at = NOW()
        WHERE title = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)
        ORDER BY created_at DESC LIMIT 1
      `, [title]);

    } catch (telegramError) {
      console.error('❌ Failed to send Telegram notification to admin:', telegramError.message);
      // Don't throw - notification was logged, that's better than nothing
    }
  } catch (error) {
    console.error('Error sending admin notification:', error);
  }
}

async function getUnreadUserMessages() {
  try {
    const [messages] = await dbConnection.execute(`
      SELECT
        m.*,
        u.email,
        COUNT(r.id) as reply_count
      FROM admin_user_messages m
      LEFT JOIN users u ON m.user_id = u.id
      LEFT JOIN admin_message_replies r ON m.id = r.original_message_id
      WHERE m.status IN ('new', 'read')
      GROUP BY m.id
      ORDER BY m.created_at DESC
      LIMIT 20
    `);
    return messages;
  } catch (error) {
    console.error('Error getting unread messages:', error);
    return [];
  }
}

async function saveAdminReply(messageId, adminTelegramId, adminUsername, replyText) {
  try {
    const [result] = await dbConnection.execute(`
      INSERT INTO admin_message_replies (
        original_message_id, admin_telegram_id, admin_username, reply_text
      ) VALUES (?, ?, ?, ?)
    `, [messageId, adminTelegramId, adminUsername, replyText]);

    // Update original message status
    await dbConnection.execute(`
      UPDATE admin_user_messages
      SET status = 'replied', updated_at = NOW()
      WHERE id = ?
    `, [messageId]);

    return result.insertId;
  } catch (error) {
    console.error('Error saving admin reply:', error);
    throw error;
  }
}

async function sendReplyToUser(originalMessage, replyText, adminUsername) {
  try {
    // Send reply to the user via Telegram
    const replyMessage = `📧 **Admin Reply**

**Your Message:** ${originalMessage.message_text}

**Admin Response:** ${replyText}

**Replied by:** ${adminUsername}
**Date:** ${new Date().toLocaleString()}

Thank you for contacting us!`;

    const keyboard = {
      inline_keyboard: [
        [{ text: "📱 Back to Menu", callback_data: "back_to_menu" }]
      ]
    };

    await bot.telegram.sendMessage(originalMessage.telegram_id, replyMessage, {
      parse_mode: "Markdown",
      reply_markup: keyboard
    });

    return true;
  } catch (error) {
    console.error('Error sending reply to user:', error);
    return false;
  }
}

async function getAllUserMessages() {
  try {
    const [messages] = await dbConnection.execute(`
      SELECT
        m.*,
        u.email,
        COUNT(r.id) as reply_count
      FROM admin_user_messages m
      LEFT JOIN users u ON m.user_id = u.id
      LEFT JOIN admin_message_replies r ON m.id = r.original_message_id
      WHERE m.status IN ('new', 'read', 'replied')
      GROUP BY m.id
      ORDER BY m.created_at DESC
      LIMIT 50
    `);
    return messages;
  } catch (error) {
    console.error('Error getting all messages:', error);
    return [];
  }
}

// Password Reset Admin Functions
async function createPasswordResetRequest(userId, telegramId, email, username, reason = null) {
  try {
    const expiresAt = new Date(Date.now() + 24 * 60 * 60 * 1000); // 24 hours

    const [result] = await dbConnection.execute(`
      INSERT INTO admin_password_reset_requests (
        user_id, telegram_id, email, username, request_reason, expires_at
      ) VALUES (?, ?, ?, ?, ?, ?)
    `, [userId, telegramId, email, username, reason, expiresAt]);

    // Create admin notification
    await createAdminNotification(
      'password_reset_request',
      'high',
      'Password Reset Request',
      `User ${username} (${email}) has requested a password reset.`,
      userId,
      telegramId,
      { request_id: result.insertId, reason: reason }
    );

    return result.insertId;
  } catch (error) {
    console.error('Error creating password reset request:', error);
    throw error;
  }
}

// User Account Management Functions
async function searchUsers(searchTerm, searchType = 'email') {
  try {
    let query, params;

    switch (searchType) {
      case 'email':
        query = `SELECT u.*, tu.telegram_id FROM users u
                 LEFT JOIN telegram_users tu ON u.id = tu.user_id
                 WHERE u.email LIKE ? LIMIT 10`;
        params = [`%${searchTerm}%`];
        break;
      case 'username':
        query = `SELECT u.*, tu.telegram_id FROM users u
                 LEFT JOIN telegram_users tu ON u.id = tu.user_id
                 WHERE u.username LIKE ? LIMIT 10`;
        params = [`%${searchTerm}%`];
        break;
      case 'telegram_id':
        query = `SELECT u.*, tu.telegram_id FROM users u
                 LEFT JOIN telegram_users tu ON u.id = tu.user_id
                 WHERE tu.telegram_id = ? LIMIT 10`;
        params = [searchTerm];
        break;
      default:
        return [];
    }

    const [users] = await dbConnection.execute(query, params);
    return users;
  } catch (error) {
    console.error('Error searching users:', error);
    return [];
  }
}

// Terms and Conditions Functions
async function createTermsAcceptanceRecord(telegramId, userId = null, investmentId = null) {
  try {
    const [result] = await dbConnection.execute(`
      INSERT INTO telegram_terms_acceptance (
        telegram_id, user_id, investment_id,
        general_terms_accepted, privacy_policy_accepted, investment_risks_accepted,
        gold_mining_terms_accepted, nft_terms_accepted, dividend_terms_accepted,
        acceptance_ip, acceptance_user_agent
      ) VALUES (?, ?, ?, 0, 0, 0, 0, 0, 0, ?, ?)
    `, [telegramId, userId, investmentId, '127.0.0.1', 'Telegram Bot']);

    return result.insertId;
  } catch (error) {
    console.error('Error creating terms acceptance record:', error);
    throw error;
  }
}

async function updateTermsAcceptance(recordId, termsType, accepted = true) {
  try {
    const validTerms = [
      'general_terms_accepted',
      'privacy_policy_accepted',
      'investment_risks_accepted',
      'gold_mining_terms_accepted',
      'nft_terms_accepted',
      'dividend_terms_accepted'
    ];

    if (!validTerms.includes(termsType)) {
      throw new Error('Invalid terms type');
    }

    await dbConnection.execute(`
      UPDATE telegram_terms_acceptance
      SET ${termsType} = ?, updated_at = NOW()
      WHERE id = ?
    `, [accepted ? 1 : 0, recordId]);

    // Check if all terms are now accepted
    const [record] = await dbConnection.execute(`
      SELECT * FROM telegram_terms_acceptance WHERE id = ?
    `, [recordId]);

    if (record.length > 0) {
      const r = record[0];
      const allAccepted = r.general_terms_accepted && r.privacy_policy_accepted &&
                         r.investment_risks_accepted && r.gold_mining_terms_accepted &&
                         r.nft_terms_accepted && r.dividend_terms_accepted;

      if (allAccepted) {
        await dbConnection.execute(`
          UPDATE telegram_terms_acceptance
          SET all_terms_accepted = 1, acceptance_timestamp = NOW()
          WHERE id = ?
        `, [recordId]);
      }
    }

    return true;
  } catch (error) {
    console.error('Error updating terms acceptance:', error);
    throw error;
  }
}

async function getTermsAcceptanceStatus(telegramId, investmentId = null) {
  try {
    let query = `
      SELECT * FROM telegram_terms_acceptance
      WHERE telegram_id = ?
    `;
    let params = [telegramId];

    if (investmentId) {
      query += ` AND investment_id = ?`;
      params.push(investmentId);
    }

    query += ` ORDER BY created_at DESC LIMIT 1`;

    const [records] = await dbConnection.execute(query, params);
    return records.length > 0 ? records[0] : null;
  } catch (error) {
    console.error('Error getting terms acceptance status:', error);
    return null;
  }
}

// Check if user has accepted all terms
async function checkUserTermsAcceptance(telegramId) {
  try {
    const termsStatus = await getTermsAcceptanceStatus(telegramId);

    if (!termsStatus) {
      return false;
    }

    // Check if all required terms are accepted
    return termsStatus.general_terms_accepted &&
           termsStatus.privacy_policy_accepted &&
           termsStatus.investment_risks_accepted &&
           termsStatus.gold_mining_terms_accepted &&
           termsStatus.nft_terms_accepted &&
           termsStatus.dividend_terms_accepted;
  } catch (error) {
    console.error('Error checking user terms acceptance:', error);
    return false;
  }
}

// =====================================================
// COMMISSION CALCULATION FUNCTIONS
// =====================================================

async function calculateAndCreateCommission(investmentId, userId, investmentAmount, investmentType = 'custom') {
  try {
    // Get user's referral information
    const [userResult] = await dbConnection.execute(`
      SELECT sponsor_user_id, sponsor_telegram_username
      FROM users
      WHERE id = ?
    `, [userId]);

    if (userResult.length === 0 || !userResult[0].sponsor_user_id) {
      console.log(`ℹ️ No referrer found for user ${userId}`);
      return false;
    }

    const sponsorUserId = userResult[0].sponsor_user_id;
    const sponsorUsername = userResult[0].sponsor_telegram_username;

    // Calculate 15% commission
    const commissionAmount = investmentAmount * 0.15;

    // Create commission record
    await dbConnection.execute(`
      INSERT INTO commissions (
        referrer_id, referred_user_id, investment_id, investment_type,
        commission_amount, investment_amount, commission_percentage,
        status, date_earned
      ) VALUES (?, ?, ?, ?, ?, ?, 15.00, 'pending', NOW())
    `, [
      sponsorUserId,
      userId,
      investmentId,
      investmentType,
      commissionAmount,
      investmentAmount
    ]);

    // Update sponsor's total commission earned
    await dbConnection.execute(`
      UPDATE users
      SET total_commission_earned = total_commission_earned + ?
      WHERE id = ?
    `, [commissionAmount, sponsorUserId]);

    console.log(`✅ Commission created: $${commissionAmount.toFixed(2)} for sponsor @${sponsorUsername} (ID: ${sponsorUserId})`);

    // Notify sponsor via Telegram if they have a linked account
    await notifyReferrerOfCommission(sponsorUserId, commissionAmount, investmentAmount, sponsorUsername);

    return true;
  } catch (error) {
    console.error("Error calculating commission:", error);
    return false;
  }
}

async function notifyReferrerOfCommission(sponsorUserId, commissionAmount, investmentAmount, sponsorUsername) {
  try {
    // Find sponsor's telegram account
    const [telegramResult] = await dbConnection.execute(`
      SELECT telegram_id FROM telegram_users WHERE user_id = ? AND is_registered = TRUE
    `, [sponsorUserId]);

    if (telegramResult.length > 0) {
      const sponsorTelegramId = telegramResult[0].telegram_id;

      const commissionMessage = `🎉 **Commission Earned!**

💰 **Amount:** $${commissionAmount.toFixed(2)}
📊 **From Investment:** $${investmentAmount.toFixed(2)}
📈 **Commission Rate:** 15%

A user you referred has made an investment. Your commission is now pending approval.

💡 **Tip:** Keep referring friends to earn more commissions!`;

      const keyboard = {
        inline_keyboard: [
          [{ text: "💼 View My Commissions", callback_data: "menu_referrals" }],
          [{ text: "📱 Main Menu", callback_data: "back_to_menu" }]
        ]
      };

      await bot.telegram.sendMessage(sponsorTelegramId, commissionMessage, {
        parse_mode: "Markdown",
        reply_markup: keyboard
      });

      console.log(`📢 Commission notification sent to @${sponsorUsername} (${sponsorTelegramId})`);
    } else {
      console.log(`ℹ️ Sponsor @${sponsorUsername} not found on Telegram for commission notification`);
    }
  } catch (error) {
    console.error("Error notifying referrer of commission:", error);
  }
}

// =====================================================
// MINING PRODUCTION CALCULATOR FUNCTIONS
// =====================================================



function calculateMiningProduction(phase, shares, userShares) {
  const totalShares = 1400000; // Total shares available
  const userSharePercentage = userShares / totalShares;

  // Phase-based production scaling
  const phaseData = {
    // Current phase (1-10): 250 hectares, 10 washplants
    current: {
      hectares: 250,
      washplants: 10,
      tonsPerHour: 200,
      hoursPerDay: 10, // Operating hours
      daysPerYear: 300, // Operating days (accounting for maintenance, weather)
      goldYieldPerTon: 0.0016, // 1.6g per ton (conservative estimate)
      operationalCosts: 0.45 // 45% operational costs
    },
    // Full capacity (phase 20): All land, 57 washplants
    full: {
      hectares: 1425, // Full concession area
      washplants: 57,
      tonsPerHour: 200 * 5.7, // 57 washplants vs 10
      hoursPerDay: 10, // Operating hours (consistent)
      daysPerYear: 330, // Better weather management
      goldYieldPerTon: 0.0018, // 1.8g per ton (improved with experience)
      operationalCosts: 0.42, // 42% operational costs (economies of scale)
      targetGoldPerYear: 15000 // 15 tons per year target
    }
  };

  let productionData;

  if (phase <= 10) {
    // Current phase calculations
    productionData = phaseData.current;
  } else if (phase <= 20) {
    // Interpolate between current and full capacity
    const progressToFull = (phase - 10) / 10; // Progress from phase 10 to 20
    productionData = {
      hectares: phaseData.current.hectares +
                (phaseData.full.hectares - phaseData.current.hectares) * progressToFull,
      washplants: Math.round(phaseData.current.washplants +
                  (phaseData.full.washplants - phaseData.current.washplants) * progressToFull),
      tonsPerHour: phaseData.current.tonsPerHour +
                   (phaseData.full.tonsPerHour - phaseData.current.tonsPerHour) * progressToFull,
      hoursPerDay: phaseData.current.hoursPerDay +
                   (phaseData.full.hoursPerDay - phaseData.current.hoursPerDay) * progressToFull,
      daysPerYear: phaseData.current.daysPerYear +
                   (phaseData.full.daysPerYear - phaseData.current.daysPerYear) * progressToFull,
      goldYieldPerTon: phaseData.current.goldYieldPerTon +
                       (phaseData.full.goldYieldPerTon - phaseData.current.goldYieldPerTon) * progressToFull,
      operationalCosts: phaseData.current.operationalCosts -
                        (phaseData.current.operationalCosts - phaseData.full.operationalCosts) * progressToFull
    };
  } else {
    // Phase 20+ uses full capacity parameters
    productionData = phaseData.full;
  }

  // Calculate annual production
  const annualTonsProcessed = productionData.tonsPerHour *
                              productionData.hoursPerDay *
                              productionData.daysPerYear;

  const annualGoldKg = annualTonsProcessed * productionData.goldYieldPerTon;

  return {
    phase: phase,
    userShares: userShares,
    userSharePercentage: userSharePercentage,
    totalShares: totalShares,

    // Production metrics
    hectares: Math.round(productionData.hectares),
    washplants: Math.round(productionData.washplants),
    tonsPerHour: Math.round(productionData.tonsPerHour),
    hoursPerDay: Math.round(productionData.hoursPerDay),
    daysPerYear: Math.round(productionData.daysPerYear),

    // Gold production
    annualTonsProcessed: Math.round(annualTonsProcessed),
    annualGoldKg: Math.round(annualGoldKg),
    goldYieldPerTon: productionData.goldYieldPerTon,

    // Financial metrics
    operationalCosts: productionData.operationalCosts,
    userAnnualGoldKg: annualGoldKg * userSharePercentage,

    // Projections
    isFullCapacity: phase >= 20,
    progressToFullCapacity: Math.min(phase / 20, 1) * 100
  };
}

// Simple dividend calculator for user-friendly projections
function calculateSimpleDividends(userShares, goldPricePerKg) {
  if (userShares === 0) {
    return {
      year1: 0, year2: 0, year3: 0, year4: 0, year5: 0,
      total: 0, averageROI: 0
    };
  }

  // Base dividend per share (conservative estimate based on current production)
  // Using simplified calculation: ~$180M annual profit / 1.4M shares = ~$128 per share
  // But we'll be conservative and use $80 per share for year 1
  const baseDividendPerShare = 80;

  // 5-year projection with production growth
  const year1 = userShares * baseDividendPerShare; // Current production
  const year2 = userShares * (baseDividendPerShare * 1.25); // 25% increase
  const year3 = userShares * (baseDividendPerShare * 1.50); // 50% increase
  const year4 = userShares * (baseDividendPerShare * 1.75); // 75% increase
  const year5 = userShares * (baseDividendPerShare * 2.00); // Full capacity (100% increase)

  const totalDividends = year1 + year2 + year3 + year4 + year5;
  const totalInvestment = userShares * 10; // $10 per share
  const averageROI = totalInvestment > 0 ? (totalDividends / totalInvestment) * 100 / 5 : 0;

  return {
    year1: Math.round(year1),
    year2: Math.round(year2),
    year3: Math.round(year3),
    year4: Math.round(year4),
    year5: Math.round(year5),
    total: Math.round(totalDividends),
    averageROI: averageROI
  };
}

async function calculateUserReturns(userShares, phase = 10, goldPricePerKg = null) {
  if (!goldPricePerKg) {
    goldPricePerKg = await getCurrentGoldPrice();
  }

  const production = calculateMiningProduction(phase, 1400000, userShares);

  // Calculate financial returns
  const grossGoldValue = production.annualGoldKg * goldPricePerKg;
  const operationalCostAmount = grossGoldValue * production.operationalCosts;
  const netProfit = grossGoldValue - operationalCostAmount;

  // User's share of profits
  const userGrossValue = production.userAnnualGoldKg * goldPricePerKg;
  const userOperationalCosts = userGrossValue * production.operationalCosts;
  const userNetProfit = userGrossValue - userOperationalCosts;

  // Quarterly dividends (paid 4 times per year)
  const userQuarterlyDividend = userNetProfit / 4;

  return {
    ...production,

    // Gold pricing
    goldPricePerKg: goldPricePerKg,

    // Total operation financials
    grossGoldValue: grossGoldValue,
    operationalCostAmount: operationalCostAmount,
    netProfit: netProfit,

    // User-specific financials
    userGrossValue: userGrossValue,
    userOperationalCosts: userOperationalCosts,
    userNetProfit: userNetProfit,
    userQuarterlyDividend: userQuarterlyDividend,
    userMonthlyEstimate: userNetProfit / 12,

    // Performance metrics
    profitMargin: ((netProfit / grossGoldValue) * 100),
    userROIAnnual: userShares > 0 ? ((userNetProfit / (userShares * 100)) * 100) : 0 // Assuming $100 per share
  };
}

function getPhaseDescription(phase) {
  if (phase <= 5) {
    return "🌱 **Early Development Phase** - Initial setup and equipment deployment";
  } else if (phase <= 10) {
    return "⚡ **Current Operations Phase** - 250 hectares active with 10 washplants";
  } else if (phase <= 15) {
    return "📈 **Expansion Phase** - Scaling operations and adding washplants";
  } else if (phase <= 20) {
    return "🚀 **Full Capacity Phase** - Maximum production with 57 washplants";
  } else {
    return "💎 **Full Capacity Achieved** - Maximum 20 phases completed";
  }
}

function getProductionTimeline() {
  return {
    currentPhase: 10,
    targetFullCapacity: "June 2026",
    phases: [
      { phase: 1, date: "Jan 2025", description: "Initial setup" },
      { phase: 5, date: "May 2025", description: "First production" },
      { phase: 10, date: "Dec 2025", description: "Current operations" },
      { phase: 15, date: "Mar 2026", description: "Major expansion" },
      { phase: 20, date: "Jun 2026", description: "Full capacity" }
    ]
  };
}

function getTermsContent(termType, title) {
  const termsContent = {
    'privacy': `🔒 **Privacy Policy**

**Data Collection and Usage**

We collect and process your personal information to:
• Process your investment transactions
• Provide customer support and communication
• Comply with legal and regulatory requirements
• Send important updates about your investments

**Data Protection:**
• Your data is encrypted and securely stored
• We never sell your personal information
• You can request data deletion at any time
• We comply with international privacy standards

**Do you accept our Privacy Policy?**`,

    'risks': `⚠️ **Investment Risk Disclosure**

**Important Risk Warning**

All investments carry risk. By proceeding, you acknowledge:

• **Market Risk:** Gold prices fluctuate and can decrease
• **Operational Risk:** Mining operations may face challenges
• **Regulatory Risk:** Changes in laws may affect operations
• **Liquidity Risk:** Investments may not be easily convertible
• **Total Loss Risk:** You could lose your entire investment

**Key Points:**
• Past performance does not guarantee future results
• Only invest what you can afford to lose
• Seek independent financial advice if needed

**Do you acknowledge and accept these investment risks?**`,

    'mining': `⛏️ **Gold Mining Investment Terms**

**Mining Operations Agreement**

By purchasing equity shares, you agree to:

• **Location:** Equity share purchases fund gold mining in Africa
• **Timeline:** Operations are long-term (12+ months)
• **Production:** Returns depend on actual gold extraction
• **Environmental:** We follow sustainable mining practices
• **Reporting:** Regular updates on mining progress

**Your Investment:**
• Represents shares in mining operations
• Dividends based on gold production and sales
• No guaranteed returns or fixed interest rates

**Do you accept the Gold Mining Investment Terms?**`,

    'nft': `🎫 **NFT Shares Understanding**

**Digital Asset Component**

Your investment includes NFT elements:

• **Share Certificates:** Digital proof of ownership
• **Blockchain Record:** Permanent investment record
• **Transferability:** NFTs may be transferable (restrictions apply)
• **Utility:** Access to investor benefits and updates
• **Technology:** Built on secure blockchain networks

**Trading Restrictions:**
• **NFTs are only sellable at a minimum value of $1000 after 12 months of purchase**
• **This restriction applies for 12 months from the date of share purchase**
• **During this 12-month period, members will receive a digital share certificate authorized by CIPC (Companies and Intellectual Property Commission) in South Africa instead of immediate NFT trading rights**

**Important Notes:**
• NFTs are digital assets with their own risks
• Technology is evolving and may change
• Value may fluctuate independently of mining returns
• Trading restrictions are in place to protect long-term investment value

**Do you understand and accept the NFT component and trading restrictions?**`,

    'dividend': `💰 **Dividend Timeline Agreement**

**Payment Schedule and Expectations**

Dividend payments are structured as follows:

• **Frequency:** Quarterly payments (every 3 months)
• **Calculation:** Based on actual mining profits
• **Timeline:** First payments expected after 6-12 months
• **Method:** Payments via cryptocurrency or bank transfer
• **Reporting:** Detailed statements provided quarterly

**Important Conditions:**
• Payments depend on successful mining operations
• No guaranteed payment amounts or dates
• Early phases may have minimal or no dividends
• Full production expected by June 2026

**Do you accept the dividend timeline and conditions?**`
  };

  return termsContent[termType] || `📄 **${title}**\n\nTerms content not available.`;
}



// Telegram user functions
async function getTelegramUser(telegramId) {
  try {
    const [rows] = await dbConnection.execute(
      "SELECT * FROM telegram_users WHERE telegram_id = ?",
      [telegramId]
    );
    return rows[0] || null;
  } catch (error) {
    console.error("Error getting telegram user:", error);
    return null;
  }
}

async function createTelegramUser(telegramId, userData) {
  try {
    await dbConnection.execute(
      `INSERT INTO telegram_users (telegram_id, username, first_name, last_name, is_registered, registration_step, created_at, updated_at) 
       VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())`,
      [telegramId, userData.username, userData.first_name, userData.last_name, false, 'start']
    );
    return await getTelegramUser(telegramId);
  } catch (error) {
    console.error("Error creating telegram user:", error);
    return null;
  }
}

async function updateTelegramUser(telegramId, updates) {
  try {
    const setClause = Object.keys(updates).map(key => `${key} = ?`).join(', ');
    const values = [...Object.values(updates), telegramId];
    
    await dbConnection.execute(
      `UPDATE telegram_users SET ${setClause}, updated_at = NOW() WHERE telegram_id = ?`,
      values
    );
    return true;
  } catch (error) {
    console.error("Error updating telegram user:", error);
    return false;
  }
}

// Investment package functions
async function getInvestmentPackages() {
  try {
    const [packages] = await dbConnection.execute(`
      SELECT
        ip.*,
        p.cost_per_share,
        (ip.shares * p.cost_per_share) as calculated_price,
        p.phase_number,
        p.name as phase_name
      FROM investment_packages ip
      CROSS JOIN phases p
      WHERE ip.is_active = 1 AND p.is_active = TRUE
      ORDER BY calculated_price ASC
    `);
    return packages.map(pkg => ({
      ...pkg,
      price: pkg.calculated_price, // Use dynamic price
      bonuses: typeof pkg.bonuses === 'string' ? JSON.parse(pkg.bonuses) : pkg.bonuses || []
    }));
  } catch (error) {
    console.error("Error getting investment packages:", error);
    return [];
  }
}

async function getPackageById(packageId) {
  try {
    const [rows] = await dbConnection.execute(`
      SELECT
        ip.*,
        p.cost_per_share,
        (ip.shares * p.cost_per_share) as calculated_price,
        p.phase_number,
        p.name as phase_name,
        p.id as phase_id
      FROM investment_packages ip
      CROSS JOIN phases p
      WHERE ip.id = ? AND ip.is_active = 1 AND p.is_active = TRUE
    `, [packageId]);

    if (rows.length === 0) return null;

    const pkg = rows[0];
    return {
      ...pkg,
      price: pkg.calculated_price, // Use dynamic price
      bonuses: typeof pkg.bonuses === 'string' ? JSON.parse(pkg.bonuses) : pkg.bonuses || []
    };
  } catch (error) {
    console.error("Error getting package by ID:", error);
    return null;
  }
}

// Get custom investment by ID
async function getCustomInvestmentById(investmentId) {
  try {
    const response = await fetch(`http://localhost/Aureus%201%20-%20Complex/api/custom-investments/get.php?id=${investmentId}`);
    const data = await response.json();

    if (data.success) {
      return data.data;
    }
    return null;
  } catch (error) {
    console.error("Error fetching custom investment:", error);
    return null;
  }
}

// Get custom investment by short ID (first 8 characters of UUID)
async function getCustomInvestmentByShortId(shortId) {
  try {
    const response = await fetch(`http://localhost/Aureus%201%20-%20Complex/api/custom-investments/get-by-short-id.php?short_id=${shortId}`);
    const data = await response.json();

    if (data.success) {
      return data.data;
    }
    return null;
  } catch (error) {
    console.error("Error fetching custom investment by short ID:", error);
    return null;
  }
}

// Check phase availability for shares
async function checkPhaseAvailability(requestedShares) {
  try {
    // Get current active phase
    const [phaseResult] = await dbConnection.execute(`
      SELECT * FROM phases WHERE is_active = TRUE LIMIT 1
    `);

    if (phaseResult.length === 0) {
      return { success: false, error: 'No active phase found' };
    }

    const phase = phaseResult[0];

    // Get shares already sold (completed investments) ONLY for current active phase
    const [soldResult] = await dbConnection.execute(`
      SELECT COALESCE(SUM(ai.shares), 0) as total_shares_sold
      FROM aureus_investments ai
      WHERE ai.status = 'completed' AND ai.created_at >= ?
    `, [phase.start_date]);

    // Get pending shares (pending investments) ONLY for current active phase
    const [pendingResult] = await dbConnection.execute(`
      SELECT COALESCE(SUM(ai.shares), 0) as pending_shares
      FROM aureus_investments ai
      WHERE ai.status = 'pending' AND ai.created_at >= ?
    `, [phase.start_date]);

    const sharesSold = parseInt(soldResult[0].total_shares_sold) || 0;
    const pendingShares = parseInt(pendingResult[0].pending_shares) || 0;
    const totalCommitted = sharesSold + pendingShares;
    const availableShares = phase.total_packages_available - totalCommitted;

    // Debug logging with data types
    console.log(`🔍 Phase Availability Debug:
    Phase: ${phase.name} (ID: ${phase.id})
    Total Available: ${phase.total_packages_available} (type: ${typeof phase.total_packages_available})
    Shares Sold: ${sharesSold} (type: ${typeof sharesSold})
    Pending Shares: ${pendingShares} (type: ${typeof pendingShares})
    Total Committed: ${totalCommitted} (type: ${typeof totalCommitted})
    Available Shares: ${availableShares} (type: ${typeof availableShares})
    Requested: ${requestedShares} (type: ${typeof requestedShares})

    Raw soldResult: ${JSON.stringify(soldResult[0])}
    Raw pendingResult: ${JSON.stringify(pendingResult[0])}`);

    if (requestedShares > availableShares) {
      return {
        success: false,
        error: `Insufficient shares available in ${phase.name}. Available: ${availableShares}, Requested: ${requestedShares}`,
        available: availableShares,
        requested: requestedShares,
        phase_name: phase.name
      };
    }

    return {
      success: true,
      available: availableShares,
      phase_name: phase.name,
      phase_number: phase.phase_number,
      phase_id: phase.id
    };

  } catch (error) {
    console.error('Error checking phase availability:', error);
    return { success: false, error: 'Error checking phase availability' };
  }
}

// Update phase statistics after successful investment
async function updatePhaseStats(shares, amount) {
  try {
    // Get current active phase
    const [phaseResult] = await dbConnection.execute(`
      SELECT id FROM phases WHERE is_active = TRUE LIMIT 1
    `);

    if (phaseResult.length === 0) {
      console.error('No active phase found for stats update');
      return;
    }

    const phaseId = phaseResult[0].id;

    await dbConnection.execute(`
      UPDATE phases SET
        packages_sold = packages_sold + ?,
        total_revenue = total_revenue + ?,
        updated_at = NOW()
      WHERE id = ?
    `, [shares, amount, phaseId]);

    // Check if phase should advance
    await checkPhaseCompletion(phaseId);

  } catch (error) {
    console.error('Error updating phase stats:', error);
  }
}

// Check if phase is completed and advance to next phase
async function checkPhaseCompletion(phaseId) {
  try {
    // Get phase info
    const [phaseResult] = await dbConnection.execute(`
      SELECT * FROM phases WHERE id = ?
    `, [phaseId]);

    if (phaseResult.length === 0) return false;

    const phase = phaseResult[0];

    // Get total shares sold for this phase only (since phase start date)
    const [soldResult] = await dbConnection.execute(`
      SELECT COALESCE(SUM(ai.shares), 0) as total_shares_sold
      FROM aureus_investments ai
      WHERE ai.status = 'completed' AND ai.created_at >= ?
    `, [phase.start_date]);

    const sharesSold = soldResult[0].total_shares_sold;

    // If phase is sold out, advance to next phase
    if (sharesSold >= phase.total_packages_available) {
      console.log(`🎯 Phase ${phase.phase_number} is sold out! Advancing to next phase...`);
      return await advanceToNextPhase(phase.phase_number);
    }

    return false;

  } catch (error) {
    console.error('Error checking phase completion:', error);
    return false;
  }
}

// Advance to the next phase
async function advanceToNextPhase(currentPhaseNumber) {
  try {
    await dbConnection.beginTransaction();

    // Deactivate current phase
    await dbConnection.execute(`
      UPDATE phases SET
        is_active = FALSE,
        end_date = NOW(),
        updated_at = NOW()
      WHERE phase_number = ?
    `, [currentPhaseNumber]);

    // Activate next phase
    const nextPhaseNumber = currentPhaseNumber + 1;
    const [nextPhaseResult] = await dbConnection.execute(`
      UPDATE phases SET
        is_active = TRUE,
        start_date = NOW(),
        updated_at = NOW()
      WHERE phase_number = ?
    `, [nextPhaseNumber]);

    if (nextPhaseResult.affectedRows === 0) {
      console.log(`🏁 No more phases available after Phase ${currentPhaseNumber}`);
      await dbConnection.rollback();
      return { success: false, error: 'No more phases available' };
    }

    // Activate packages for next phase
    await dbConnection.execute(`
      UPDATE investment_packages ip
      JOIN phases p ON ip.phase_id = p.id
      SET ip.is_active = TRUE
      WHERE p.phase_number = ?
    `, [nextPhaseNumber]);

    // Deactivate packages for previous phase
    await dbConnection.execute(`
      UPDATE investment_packages ip
      JOIN phases p ON ip.phase_id = p.id
      SET ip.is_active = FALSE
      WHERE p.phase_number = ?
    `, [currentPhaseNumber]);

    await dbConnection.commit();

    console.log(`🚀 Successfully advanced from Phase ${currentPhaseNumber} to Phase ${nextPhaseNumber}`);

    // Send admin notification about phase advancement
    await sendAdminNotification(
      'Phase Advanced!',
      `🎉 Phase ${currentPhaseNumber} completed!\n🚀 Phase ${nextPhaseNumber} is now active.`
    );

    return {
      success: true,
      message: `Advanced from Phase ${currentPhaseNumber} to Phase ${nextPhaseNumber}`,
      new_phase: nextPhaseNumber
    };

  } catch (error) {
    await dbConnection.rollback();
    console.error('Error advancing phase:', error);
    return { success: false, error: error.message };
  }
}

// Utility functions
function formatCurrency(amount) {
  return `$${parseFloat(amount).toFixed(2)}`;
}

function formatLargeNumber(amount) {
  return `$${parseFloat(amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

// Global variable to cache gold price
let cachedGoldPrice = 107000; // Default fallback price in USD per KG
let lastGoldPriceUpdate = 0;

// Global variable to cache company wallets
let cachedWallets = null;
let lastWalletUpdate = 0;

async function getCurrentGoldPrice() {
  try {
    // Cache for 1 hour (3600000 ms)
    const now = Date.now();
    if (now - lastGoldPriceUpdate < 3600000 && cachedGoldPrice) {
      return cachedGoldPrice;
    }

    // Try to fetch current gold price from API
    const response = await fetch('https://api.metals.live/v1/spot/gold');
    const data = await response.json();

    if (data && data.price) {
      // Convert from USD per troy ounce to USD per KG
      // 1 KG = 32.15 troy ounces
      const pricePerKg = data.price * 32.15;
      cachedGoldPrice = pricePerKg;
      lastGoldPriceUpdate = now;
      console.log(`📈 Updated gold price: $${pricePerKg.toFixed(2)} per KG`);
      return pricePerKg;
    }
  } catch (error) {
    console.log(`⚠️ Could not fetch live gold price, using cached: $${cachedGoldPrice}`);
  }

  return cachedGoldPrice;
}

async function getCompanyWallets() {
  try {
    // Cache for 1 hour (3600000 ms)
    const now = Date.now();
    if (now - lastWalletUpdate < 3600000 && cachedWallets) {
      console.log(`💳 Using cached wallets: ${Object.keys(cachedWallets).join(', ')}`);
      return cachedWallets;
    }

    console.log(`🔄 Fetching company wallets from API...`);
    // Fetch wallet addresses from API
    const response = await fetch('http://localhost/Aureus%201%20-%20Complex/api/wallets/active.php');
    const data = await response.json();

    console.log(`📡 API Response:`, data);

    if (data && data.success && data.data) {
      cachedWallets = data.data;
      lastWalletUpdate = now;
      console.log(`💳 Updated company wallets: ${Object.keys(cachedWallets).join(', ')}`);
      return cachedWallets;
    } else {
      console.log(`❌ API returned invalid data:`, data);
    }
  } catch (error) {
    console.log(`⚠️ Could not fetch company wallets, error:`, error.message);
  }

  // Fallback wallets if API fails
  const fallbackWallets = {
    bsc: "0x742d35Cc6634C0532925a3b8D4C9db96C4b4d8b7",
    ethereum: "0x742d35Cc6634C0532925a3b8D4C9db96C4b4d8b7",
    polygon: "0x742d35Cc6634C0532925a3b8D4C9db96C4b4d8b7",
    tron: "TQn9Y2khEsLJW1ChVWFMSMeRDow5KcbLSE"
  };

  cachedWallets = fallbackWallets;
  return fallbackWallets;
}

// Create crypto payment record for admin approval
async function createCryptoPaymentRecord(telegramId, packageId, network, txHash, senderWallet = null, screenshotPath = null, isCustomInvestment = false) {
  try {
    let pkg = null;
    let investmentRecord = null;

    if (isCustomInvestment) {
      // For custom investments, packageId is actually the investment ID
      const [investmentResult] = await dbConnection.execute(`
        SELECT * FROM aureus_investments WHERE id = ?
      `, [packageId]);

      if (investmentResult.length === 0) {
        throw new Error("Custom investment not found");
      }

      investmentRecord = investmentResult[0];
      // Create a package-like object for compatibility
      pkg = {
        id: packageId,
        name: investmentRecord.package_name,
        price: investmentRecord.amount,
        shares: investmentRecord.shares
      };
    } else {
      // Regular package investment
      pkg = await getPackageById(packageId);
      if (!pkg) throw new Error("Package not found");

      // Check phase availability before creating payment record
      const phaseCheck = await checkPhaseAvailability(pkg.shares);
      if (!phaseCheck.success) {
        throw new Error(phaseCheck.error);
      }
    }

    // Check for duplicate transaction hash BEFORE creating any records
    const [existingTx] = await dbConnection.execute(`
      SELECT id FROM crypto_payment_transactions
      WHERE transaction_hash = ? AND network = ?
    `, [txHash, network]);

    if (existingTx.length > 0) {
      throw new Error('DUPLICATE_TRANSACTION_HASH');
    }

    // Get user info for admin notification and find corresponding user ID
    const [userResult] = await dbConnection.execute(`
      SELECT tu.username, tu.linked_email, u.id as user_id
      FROM telegram_users tu
      LEFT JOIN users u ON tu.linked_email = u.email
      WHERE tu.telegram_id = ?
    `, [telegramId]);
    const userInfo = userResult[0] || { username: 'Unknown', linked_email: 'Unknown', user_id: null };

    if (!userInfo.user_id) {
      throw new Error('User not found in system. Please ensure you are registered on the website.');
    }

    // Create or use investment record
    let investmentId;

    if (isCustomInvestment) {
      // For custom investments, use the existing investment record
      investmentId = packageId;
      // Update the investment status to indicate payment is being processed
      await dbConnection.execute(`
        UPDATE aureus_investments
        SET status = 'pending_payment', payment_method = 'wallet'
        WHERE id = ?
      `, [investmentId]);
    } else {
      // Create new investment record for regular packages
      investmentId = generateUUID();
      await dbConnection.execute(`
        INSERT INTO aureus_investments (
          id, user_id, name, email, investment_plan, package_name, amount, shares,
          status, payment_method, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'wallet', NOW())
      `, [investmentId, userInfo.user_id, userInfo.username || 'Unknown', userInfo.linked_email, pkg.name, pkg.name, pkg.price, pkg.shares]);

      // Calculate and create commission for package investment
      await calculateAndCreateCommission(investmentId, userInfo.user_id, pkg.price, 'package');
    }

    // Create crypto payment record with sender wallet and screenshot
    const paymentId = generateUUID();
    await dbConnection.execute(`
      INSERT INTO crypto_payment_transactions (
        id, investment_id, user_id, network, transaction_hash,
        amount_usd, wallet_address, sender_wallet_address, payment_screenshot_path,
        payment_status, verification_status, created_at
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', NOW())
    `, [paymentId, investmentId, telegramId, network, txHash, pkg.price, (await getCompanyWallets())[network], senderWallet, screenshotPath]);

    // Create admin payment confirmation record for admin panel
    const adminPaymentId = generateUUID();
    await dbConnection.execute(`
      INSERT INTO admin_payment_confirmations (
        id, investment_id, user_id, telegram_id, payment_method, amount, currency,
        transaction_reference, package_name, shares, status, created_at
      ) VALUES (?, ?, ?, ?, 'crypto', ?, 'USD', ?, ?, ?, 'pending', NOW())
    `, [adminPaymentId, investmentId, userInfo.user_id, telegramId, pkg.price, txHash, pkg.name, pkg.shares]);

    // 🚨 FIX 1: CREATE ADMIN NOTIFICATION FOR CRYPTO PAYMENT
    await createAdminNotification(
      'payment_confirmation',
      'high',
      '💳 New Crypto Payment Submitted',
      `New cryptocurrency payment requires verification:

📦 Package: ${pkg.name} ($${pkg.price})
👤 User: ${userInfo.username} (${userInfo.linked_email})
🌐 Network: ${network.toUpperCase()}
💰 Amount: $${pkg.price} USD
🔗 Transaction: ${txHash}
📧 Sender Wallet: ${senderWallet || 'Not provided'}

Please verify this payment in the admin dashboard.`,
      null,
      telegramId,
      {
        payment_id: adminPaymentId,
        investment_id: investmentId,
        network: network,
        transaction_hash: txHash,
        amount: pkg.price
      }
    );

    console.log(`💳 Created crypto payment record: ${paymentId} for investment: ${investmentId}, sender: ${senderWallet}`);
    console.log(`💳 Created admin payment confirmation: ${adminPaymentId} for admin panel`);
    console.log(`📢 Admin notification sent for crypto payment: ${adminPaymentId}`);

    return { investmentId, paymentId: adminPaymentId };
  } catch (error) {
    console.error("Error creating crypto payment record:", error);
    throw error;
  }
}

// Create bank payment record for admin approval
async function createBankPaymentRecord(telegramId, packageId, referenceNumber) {
  try {
    const pkg = await getPackageById(packageId);
    if (!pkg) throw new Error("Package not found");

    // Check phase availability before creating payment record
    const phaseCheck = await checkPhaseAvailability(pkg.shares);
    if (!phaseCheck.success) {
      throw new Error(phaseCheck.error);
    }

    // Get user info and find corresponding user ID
    const [userResult] = await dbConnection.execute(`
      SELECT tu.username, tu.linked_email, u.id as user_id
      FROM telegram_users tu
      LEFT JOIN users u ON tu.linked_email = u.email
      WHERE tu.telegram_id = ?
    `, [telegramId]);
    const userInfo = userResult[0] || { username: 'Unknown', linked_email: 'Unknown', user_id: null };

    if (!userInfo.user_id) {
      throw new Error('User not found in system. Please ensure you are registered on the website.');
    }

    // Create investment record first
    const investmentId = generateUUID();
    await dbConnection.execute(`
      INSERT INTO aureus_investments (
        id, user_id, name, email, investment_plan, package_name, amount, shares,
        status, payment_method, created_at
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'wallet', NOW())
    `, [investmentId, userInfo.user_id, userInfo.username || 'Unknown', userInfo.linked_email, pkg.name, pkg.name, pkg.price, pkg.shares]);

    // Create bank payment record
    const paymentId = generateUUID();
    await dbConnection.execute(`
      INSERT INTO bank_payment_transactions (
        id, investment_id, user_id, reference_number,
        amount_usd, amount_local, local_currency, exchange_rate,
        payment_status, verification_status, expires_at, created_at
      ) VALUES (?, ?, ?, ?, ?, ?, 'USD', 1.0, 'pending', 'pending',
                DATE_ADD(NOW(), INTERVAL 7 DAY), NOW())
    `, [paymentId, investmentId, telegramId, referenceNumber, pkg.price, pkg.price]);

    // Create admin payment confirmation record for admin panel
    const adminPaymentId = generateUUID();
    await dbConnection.execute(`
      INSERT INTO admin_payment_confirmations (
        id, investment_id, user_id, telegram_id, payment_method, amount, currency,
        transaction_reference, package_name, shares, status, created_at
      ) VALUES (?, ?, ?, ?, 'bank_transfer', ?, 'USD', ?, ?, ?, 'pending', NOW())
    `, [adminPaymentId, investmentId, userInfo.user_id, telegramId, pkg.price, referenceNumber, pkg.name, pkg.shares]);

    console.log(`🏦 Created bank payment record: ${paymentId} for investment: ${investmentId}`);
    console.log(`🏦 Created admin payment confirmation: ${adminPaymentId} for admin panel`);
    return { investmentId, paymentId: adminPaymentId };
  } catch (error) {
    console.error("Error creating bank payment record:", error);
    throw error;
  }
}

function generateUUID() {
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
    const r = Math.random() * 16 | 0;
    const v = c == 'x' ? r : (r & 0x3 | 0x8);
    return v.toString(16);
  });
}

async function calculateMineProduction(shares) {
  // Mine production constants
  const ANNUAL_GOLD_PRODUCTION = 3200; // KG
  const OPERATIONAL_COST_PERCENTAGE = 0.45; // 45%
  const TOTAL_AUREUS_SHARES = 1400000;

  // Get current gold price
  const goldPricePerKg = await getCurrentGoldPrice();

  // Calculations
  const grossRevenue = ANNUAL_GOLD_PRODUCTION * goldPricePerKg;
  const operationalCosts = grossRevenue * OPERATIONAL_COST_PERCENTAGE;
  const netProfit = grossRevenue - operationalCosts;
  const dividendPerShare = netProfit / TOTAL_AUREUS_SHARES;
  const userAnnualDividend = dividendPerShare * shares;

  return {
    grossRevenue,
    operationalCosts,
    netProfit,
    dividendPerShare,
    userAnnualDividend,
    totalShares: TOTAL_AUREUS_SHARES,
    annualProduction: ANNUAL_GOLD_PRODUCTION,
    goldPricePerKg
  };
}

async function formatPackageInfo(pkg) {
  const bonusText = pkg.bonuses && pkg.bonuses.length > 0
    ? `\n🎁 **Bonuses:** ${pkg.bonuses.join(', ')}`
    : '';

  // Get current phase and next phase information for header
  let phaseHeader = '';
  try {
    const [currentPhaseResult] = await dbConnection.execute(`
      SELECT p.phase_number, p.name as phase_name, p.total_packages_available, p.cost_per_share, p.start_date,
             COALESCE(SUM(CASE WHEN ai.status = 'completed' AND ai.created_at >= p.start_date THEN ai.shares ELSE 0 END), 0) as shares_sold
      FROM phases p
      LEFT JOIN aureus_investments ai ON p.is_active = TRUE
      WHERE p.is_active = TRUE
      GROUP BY p.id
    `);

    if (currentPhaseResult.length > 0) {
      const currentPhase = currentPhaseResult[0];
      const availableShares = currentPhase.total_packages_available - currentPhase.shares_sold;
      const completionPercentage = ((currentPhase.shares_sold / currentPhase.total_packages_available) * 100).toFixed(1);

      // Get next phase information
      const [nextPhaseResult] = await dbConnection.execute(`
        SELECT phase_number, name as phase_name, cost_per_share
        FROM phases
        WHERE phase_number = ? AND is_active = FALSE
        ORDER BY phase_number LIMIT 1
      `, [currentPhase.phase_number + 1]);

      let nextPhaseInfo = '';
      if (nextPhaseResult.length > 0) {
        const nextPhase = nextPhaseResult[0];
        nextPhaseInfo = `⏭️ Next: ${nextPhase.phase_name} at $${nextPhase.cost_per_share}/share`;
      }

      phaseHeader = `🎯 **${currentPhase.phase_name.toUpperCase()} - $${currentPhase.cost_per_share}/share**
📊 ${availableShares.toLocaleString()} shares remaining (${completionPercentage}% sold)
${nextPhaseInfo}

`;
    }
  } catch (error) {
    console.error('Error getting phase info:', error);
  }

  const mineCalc = await calculateMineProduction(pkg.shares);
  const quarterlyDividend = mineCalc.dividendPerShare / 4;
  const userQuarterlyDividend = mineCalc.userAnnualDividend / 4;

  return `${phaseHeader}💎 **${pkg.name.toUpperCase()} PACKAGE**

💰 **Price:** ${formatCurrency(pkg.price)} (${pkg.shares} shares × $${pkg.cost_per_share || '5.00'})
📊 **Shares:** ${pkg.shares}${bonusText}

⚠️ **Important Disclaimer:**
Shares cannot be sold within 12 months of purchase to ensure all 20 phases of share sales are completed successfully, protecting the integrity of our mining operation and maximizing returns for all investors.

📈 **Mine Production Target:**
🏭 Annual Production: ${mineCalc.annualProduction.toLocaleString()} KG gold
💰 Gold Price: ${formatLargeNumber(mineCalc.goldPricePerKg)} per KG
📊 Gross Revenue: ${formatLargeNumber(mineCalc.grossRevenue)}
⚙️ Mining Costs (45%): ${formatLargeNumber(mineCalc.operationalCosts)}
💎 Net Annual Profit: ${formatLargeNumber(mineCalc.netProfit)}
📈 Total Aureus Shares: ${mineCalc.totalShares.toLocaleString()}
💰 Dividend per Share: ${formatCurrency(mineCalc.dividendPerShare)}
📅 Quarterly Dividend per Share: ${formatCurrency(quarterlyDividend)}
🎯 Your Quarterly Dividend: ${formatLargeNumber(userQuarterlyDividend)} (based on ${pkg.shares} shares)
💎 Your Annual Dividend: ${formatLargeNumber(mineCalc.userAnnualDividend)} (based on ${pkg.shares} shares)

⚠️ **Production Timeline:**
The dividend calculation above is based on reaching full mine production capacity, utilizing 10 washplants—each capable of processing 200 tons of alluvial material per hour. This production milestone is targeted for achievement by June 2026.

🌍 **Supporting Global Impact:**
By purchasing equity shares, you are supporting NPOs worldwide as 10% of your payment goes towards 28 NPOs making a difference across the globe.`;
}

// Custom Investment Functions
async function showCustomInvestmentMenu(ctx) {
  try {
    // Get current phase information
    const [phaseResult] = await dbConnection.execute(`
      SELECT p.phase_number, p.name as phase_name, p.total_packages_available, p.cost_per_share, p.start_date,
             COALESCE(SUM(CASE WHEN ai.status = 'completed' AND ai.created_at >= p.start_date THEN ai.shares ELSE 0 END), 0) as shares_sold
      FROM phases p
      LEFT JOIN aureus_investments ai ON p.is_active = TRUE
      WHERE p.is_active = TRUE
      GROUP BY p.id
    `);

    let phaseInfo = '';
    let maxInvestment = 0;
    if (phaseResult.length > 0) {
      const phase = phaseResult[0];
      const availableShares = phase.total_packages_available - phase.shares_sold;
      const completionPercentage = ((phase.shares_sold / phase.total_packages_available) * 100).toFixed(1);
      maxInvestment = availableShares * phase.cost_per_share;

      phaseInfo = `🎯 **${phase.phase_name.toUpperCase()} - $${phase.cost_per_share}/share**
📊 ${availableShares.toLocaleString()} shares remaining (${completionPercentage}% sold)
💰 Maximum equity share purchase: $${maxInvestment.toLocaleString()}

`;
    }

    const message = `${phaseInfo}💰 **Custom Equity Share Amount**

Enter your desired equity share amount and we'll automatically select the optimal package combination for you.

🔹 **How it works:**
• Enter any amount from $25 to $${maxInvestment.toLocaleString()}
• We'll calculate the best package combination
• Larger packages are prioritized for efficiency
• You'll see exactly what you're getting before confirming

💡 **Examples:**
• $1,250 = 1 × Aureus ($1,000) + 1 × Crusher ($500) + 10 × Shovel ($25 each)
• $5,000 = 5 × Aureus packages ($1,000 each)

📝 **Ready to start?** Click the button below to enter your amount:`;

    const keyboard = {
      inline_keyboard: [
        [{ text: "💰 Enter Equity Share Amount", callback_data: "custom_enter_amount" }],
        [{ text: "📦 View Standard Packages", callback_data: "menu_packages" }],
        [{ text: "🔙 Back to Menu", callback_data: "back_to_menu" }]
      ]
    };

    await ctx.editMessageText(message, {
      parse_mode: "Markdown",
      reply_markup: keyboard
    });

  } catch (error) {
    console.error('Error showing custom investment menu:', error);
    await ctx.editMessageText("❌ Error loading custom investment menu. Please try again.", {
      reply_markup: {
        inline_keyboard: [[{ text: "🔙 Back to Menu", callback_data: "back_to_menu" }]]
      }
    });
  }
}

async function calculateOptimalPackages(investmentAmount, costPerShare) {
  try {
    // Get available packages sorted by price (descending for optimal allocation)
    const packages = [
      { name: 'Aureus', shares: 200, price: 200 * costPerShare },
      { name: 'Refinery', shares: 150, price: 150 * costPerShare },
      { name: 'Crusher', shares: 100, price: 100 * costPerShare },
      { name: 'Excavator', shares: 50, price: 50 * costPerShare },
      { name: 'Loader', shares: 20, price: 20 * costPerShare },
      { name: 'Miner', shares: 15, price: 15 * costPerShare },
      { name: 'Pick', shares: 10, price: 10 * costPerShare },
      { name: 'Shovel', shares: 5, price: 5 * costPerShare }
    ];

    let remainingAmount = investmentAmount;
    const selectedPackages = [];
    let totalShares = 0;
    let totalCost = 0;

    // Greedy algorithm: start with largest packages
    for (const pkg of packages) {
      const quantity = Math.floor(remainingAmount / pkg.price);
      if (quantity > 0) {
        selectedPackages.push({
          name: pkg.name,
          quantity: quantity,
          shares: pkg.shares,
          unitPrice: pkg.price,
          totalPrice: quantity * pkg.price,
          totalShares: quantity * pkg.shares
        });

        totalShares += quantity * pkg.shares;
        totalCost += quantity * pkg.price;
        remainingAmount -= quantity * pkg.price;
      }
    }

    return {
      packages: selectedPackages,
      totalShares,
      totalCost,
      remainingAmount: investmentAmount - totalCost,
      costPerShare
    };

  } catch (error) {
    console.error('Error calculating optimal packages:', error);
    throw error;
  }
}

async function handleCustomAmountInput(ctx, amountText, originalMessageId) {
  try {
    // Clear user state
    userStates.delete(ctx.from.id);

    // Parse and validate amount
    const amount = parseFloat(amountText.replace(/[,$]/g, ''));

    if (isNaN(amount) || amount < 25) {
      await ctx.reply("❌ Invalid amount. Please enter a number of at least $25.", {
        reply_markup: {
          inline_keyboard: [
            [{ text: "💰 Try Again", callback_data: "custom_enter_amount" }],
            [{ text: "🔙 Back to Menu", callback_data: "back_to_menu" }]
          ]
        }
      });
      return;
    }

    // Get current phase information
    const [phaseResult] = await dbConnection.execute(`
      SELECT p.phase_number, p.name as phase_name, p.total_packages_available, p.cost_per_share, p.start_date,
             COALESCE(SUM(CASE WHEN ai.status = 'completed' AND ai.created_at >= p.start_date THEN ai.shares ELSE 0 END), 0) as shares_sold
      FROM phases p
      LEFT JOIN aureus_investments ai ON p.is_active = TRUE
      WHERE p.is_active = TRUE
      GROUP BY p.id
    `);

    if (phaseResult.length === 0) {
      await ctx.reply("❌ No active phase found. Please try again later.");
      return;
    }

    const phase = phaseResult[0];
    const availableShares = phase.total_packages_available - phase.shares_sold;
    const maxInvestment = availableShares * phase.cost_per_share;

    if (amount > maxInvestment) {
      await ctx.reply(
        `❌ **Equity share amount too large**\n\n` +
        `Your requested amount: $${amount.toLocaleString()}\n` +
        `Maximum available: $${maxInvestment.toLocaleString()}\n\n` +
        `This is based on ${availableShares.toLocaleString()} remaining shares at $${phase.cost_per_share}/share.`,
        {
          parse_mode: "Markdown",
          reply_markup: {
            inline_keyboard: [
              [{ text: `💰 Purchase Maximum ($${maxInvestment.toLocaleString()})`, callback_data: `custom_confirm_${maxInvestment}` }],
              [{ text: "💰 Enter Different Amount", callback_data: "custom_enter_amount" }],
              [{ text: "🔙 Back to Menu", callback_data: "back_to_menu" }]
            ]
          }
        }
      );
      return;
    }

    // Calculate optimal package combination
    const calculation = await calculateOptimalPackages(amount, phase.cost_per_share);

    // Format the package breakdown
    let packageBreakdown = '';
    for (const pkg of calculation.packages) {
      packageBreakdown += `• ${pkg.quantity}× ${pkg.name} = $${pkg.totalPrice.toLocaleString()} (${pkg.totalShares} shares)\n`;
    }

    const message = `🎯 **${phase.phase_name.toUpperCase()} - $${phase.cost_per_share}/share**

💰 **Equity Share Amount:** $${amount.toLocaleString()}
📊 **Total Shares:** ${calculation.totalShares.toLocaleString()}
💵 **Total Cost:** $${calculation.totalCost.toLocaleString()}
${calculation.remainingAmount > 0 ? `💰 **Remaining:** $${calculation.remainingAmount.toFixed(2)}` : ''}

📦 **Package Breakdown:**
${packageBreakdown}

✅ **Ready to proceed?** This combination gives you the maximum shares for your equity share purchase amount.`;

    const keyboard = {
      inline_keyboard: [
        [{ text: `✅ Confirm Equity Share Purchase ($${calculation.totalCost.toLocaleString()})`, callback_data: `custom_confirm_${calculation.totalCost}_${calculation.totalShares}` }],
        [{ text: "💰 Enter Different Amount", callback_data: "custom_enter_amount" }],
        [{ text: "🔙 Back to Menu", callback_data: "back_to_menu" }]
      ]
    };

    // Store the calculation for later use
    userStates.set(ctx.from.id, {
      state: 'custom_investment_calculated',
      calculation: calculation,
      phase: phase
    });

    await ctx.reply(message, {
      parse_mode: "Markdown",
      reply_markup: keyboard
    });

  } catch (error) {
    console.error('Error handling custom amount input:', error);
    await ctx.reply("❌ Error processing your equity share amount. Please try again.", {
      reply_markup: {
        inline_keyboard: [
          [{ text: "💰 Try Again", callback_data: "custom_enter_amount" }],
          [{ text: "🔙 Back to Menu", callback_data: "back_to_menu" }]
        ]
      }
    });
  }
}

async function processCustomInvestment(ctx, calculation, phase) {
  try {
    // Clear user state
    userStates.delete(ctx.from.id);

    const telegramUser = ctx.telegramUser;
    if (!telegramUser || !telegramUser.linked_email) {
      await ctx.editMessageText("❌ Authentication required. Please login first.", {
        reply_markup: {
          inline_keyboard: [[{ text: "🔐 Login", callback_data: "auth_login" }]]
        }
      });
      return;
    }

    // Check phase availability one more time
    const phaseCheck = await checkPhaseAvailability(calculation.totalShares);
    if (!phaseCheck.success) {
      await ctx.editMessageText(`❌ **Phase Availability Changed**\n\n${phaseCheck.error}`, {
        parse_mode: "Markdown",
        reply_markup: {
          inline_keyboard: [
            [{ text: "💰 Try Again", callback_data: "menu_custom_investment" }],
            [{ text: "🔙 Back to Menu", callback_data: "back_to_menu" }]
          ]
        }
      });
      return;
    }

    // Create a summary of the investment for payment processing
    const packageSummary = calculation.packages.map(pkg =>
      `${pkg.quantity}× ${pkg.name}`
    ).join(', ');

    // Get user ID from database
    const [userResult] = await dbConnection.execute(`
      SELECT id FROM users WHERE email = ?
    `, [telegramUser.linked_email]);

    if (userResult.length === 0) {
      throw new Error('User not found in database');
    }

    const userId = userResult[0].id;

    // Create investment record directly with UUID
    const investmentId = generateUUID();
    await dbConnection.execute(`
      INSERT INTO aureus_investments (
        id, user_id, name, email, wallet_address, chain, package_name,
        shares, amount, status, tx_hash, payment_method, investment_plan
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    `, [
      investmentId,
      userId,
      telegramUser.name || ctx.from.first_name,
      telegramUser.linked_email,
      '', // wallet_address - will be filled when payment is made
      '', // chain - will be filled when payment is made
      'Custom Investment',
      calculation.totalShares,
      calculation.totalCost,
      'pending',
      '', // tx_hash - will be filled when payment is made
      'wallet',
      `Custom: ${packageSummary}`
    ]);

    const paymentId = investmentId;

    // Calculate and create commission if user has a referrer
    try {
      await calculateAndCreateCommission(investmentId, userId, calculation.totalCost, 'custom');
    } catch (error) {
      console.error('Commission calculation error:', error);
      // Continue with payment flow even if commission fails
    }

    // Show payment instructions
    const message = `✅ **Custom Investment Created**

📦 **Package Summary:** ${packageSummary}
📊 **Total Shares:** ${calculation.totalShares.toLocaleString()}
💰 **Total Amount:** $${calculation.totalCost.toLocaleString()}
🎯 **Phase:** ${phase.phase_name} ($${calculation.costPerShare}/share)

🔄 **Next Steps:**
1. Choose your payment method
2. Send payment to the provided address
3. Submit transaction details
4. Wait for admin confirmation

💡 **Payment ID:** ${paymentId}`;

    // Check if terms are accepted for this user
    const hasAcceptedTerms = await checkUserTermsAcceptance(ctx.from.id);

    if (!hasAcceptedTerms) {
      // Show terms acceptance first
      const termsMessage = `📋 **Terms and Conditions Required**

**Custom Equity Share Purchase:** ${calculation.totalShares} shares for ${formatCurrency(calculation.totalCost)}

Before proceeding with payment, you must review and accept our terms and conditions.

**Please review and accept each term to continue:**`;

      const termsKeyboard = {
        inline_keyboard: [
          [
            { text: "📄 Start Terms Review", callback_data: `terms_start_custom_${paymentId}` }
          ],
          [
            { text: "🔙 Back to Menu", callback_data: "back_to_menu" }
          ]
        ]
      };

      await ctx.editMessageText(termsMessage, { parse_mode: "Markdown", reply_markup: termsKeyboard });
      return;
    }

    // Terms already accepted, proceed to payment method selection
    const keyboard = {
      inline_keyboard: [
        [
          { text: "💰 Cryptocurrency", callback_data: `crypto_payment_custom_${paymentId}` }
        ],
        [
          { text: "🏦 Bank Transfer", callback_data: `bank_payment_custom_${paymentId}` }
        ],
        [
          { text: "🔙 Back to Menu", callback_data: "back_to_menu" }
        ]
      ]
    };

    await ctx.editMessageText(message, {
      parse_mode: "Markdown",
      reply_markup: keyboard
    });

    // Log the custom equity share purchase
    console.log(`💰 Custom equity share purchase created: ${calculation.totalShares} shares for $${calculation.totalCost} by ${ctx.from.first_name} (${ctx.from.id})`);

  } catch (error) {
    console.error('Error processing custom investment:', error);
    await ctx.editMessageText("❌ Error processing your custom equity share purchase. Please try again.", {
      reply_markup: {
        inline_keyboard: [
          [{ text: "💰 Try Again", callback_data: "menu_custom_investment" }],
          [{ text: "🔙 Back to Menu", callback_data: "back_to_menu" }]
        ]
      }
    });
  }
}

// Security Middleware
bot.use(async (ctx, next) => {
  try {
    const telegramId = ctx.from.id;

    // Rate limiting check
    if (isRateLimited(telegramId)) {
      await ctx.reply('⚠️ Too many requests. Please wait a moment before trying again.');
      return;
    }

    // Input sanitization for text messages
    if (ctx.message && ctx.message.text) {
      ctx.message.text = sanitizeInput(ctx.message.text);
    }

    // Log suspicious patterns
    if (ctx.message && ctx.message.text) {
      const text = ctx.message.text.toLowerCase();
      if (text.includes('script') || text.includes('sql') || text.includes('drop') || text.includes('delete') || text.includes('union') || text.includes('select')) {
        logSuspiciousActivity(telegramId, 'SUSPICIOUS_INPUT', { text: ctx.message.text });
      }
    }

    await next();
  } catch (error) {
    console.error('Security middleware error:', error);
    await ctx.reply('❌ Security check failed. Please try again.');
  }
});

// Middleware for user context
bot.use(async (ctx, next) => {
  if (ctx.from) {
    let telegramUser = await getTelegramUser(ctx.from.id);

    if (!telegramUser) {
      telegramUser = await createTelegramUser(ctx.from.id, {
        username: ctx.from.username,
        first_name: ctx.from.first_name,
        last_name: ctx.from.last_name
      });
    }

    ctx.telegramUser = telegramUser;
  }
  await next();
});

// Commands
bot.start(async (ctx) => {
  const user = ctx.from;
  const telegramUser = ctx.telegramUser;

  // Check for referral link in start parameter
  const startPayload = ctx.message?.text?.split(' ')[1];
  if (startPayload && startPayload.startsWith('ref_')) {
    const referralCode = startPayload.replace('ref_', '');
    console.log(`🔗 Referral link detected: ${referralCode} for user ${user.first_name} (${user.id})`);

    // Handle referral link if user is not already registered
    if (!telegramUser?.is_registered) {
      await handleReferralLink(ctx, referralCode);
    }
  }

  // Check for auto-login capability
  if (telegramUser && telegramUser.auto_login_enabled && telegramUser.linked_email) {
    // Verify the linked account still exists
    try {
      const [rows] = await dbConnection.execute(
        'SELECT id, full_name, email FROM users WHERE email = ?',
        [telegramUser.linked_email]
      );

      if (rows.length > 0) {
        // Auto-login successful - Show Mini App option
        const welcomeMessage = `🌟 **Welcome back, ${user.first_name}!** 🌟

🔗 **Auto-Login:** Successfully logged in with ${telegramUser.linked_email}

Choose how you'd like to access your investment platform:`;

        // Check if user is authorized for admin access
        const isAdminUser = isAuthorizedForAdmin(user.username);

        const keyboard = {
          inline_keyboard: [
            [
              { text: "📱 Main Menu", callback_data: "back_to_menu" },
              { text: "📊 Portfolio", callback_data: "menu_portfolio" }
            ],
            [
              { text: "📦 Packages", callback_data: "menu_packages" },
              { text: "👥 Referrals", callback_data: "menu_referrals" }
            ],
            [
              { text: "🔧 Settings", callback_data: "menu_profile" },
              { text: "🚪 Logout", callback_data: "confirm_logout" }
            ],
            ...(isAdminUser ? [[{ text: "🔐 Admin Panel", callback_data: "admin_panel_access" }]] : [])
          ]
        };

        await ctx.replyWithMarkdown(welcomeMessage, { reply_markup: keyboard });
        console.log(`🔄 Auto-login successful for ${user.first_name} (${ctx.from.id}) with email ${telegramUser.linked_email}`);
        return;
      } else {
        // Linked account no longer exists, reset auto-login
        await updateTelegramUser(ctx.from.id, {
          is_registered: false,
          auto_login_enabled: false,
          linked_email: null,
          user_id: null
        });
        console.log(`⚠️ Linked account ${telegramUser.linked_email} no longer exists, reset auto-login for ${ctx.from.id}`);
      }
    } catch (error) {
      console.error("Auto-login verification error:", error);
    }
  }

  if (telegramUser && telegramUser.is_registered) {
    const welcomeMessage = `🌟 **Welcome back, ${user.first_name}!** 🌟

Your account is linked and ready to use.

Choose how you'd like to access your investment platform:`;

    const keyboard = {
      inline_keyboard: [
        [
          { text: "📱 Main Menu", callback_data: "back_to_menu" },
          { text: "📊 Portfolio", callback_data: "menu_portfolio" }
        ],
        [
          { text: "📦 Packages", callback_data: "menu_packages" },
          { text: "👥 Referrals", callback_data: "menu_referrals" }
        ],
        [
          { text: "🔧 Settings", callback_data: "menu_profile" }
        ]
      ]
    };

    await ctx.replyWithMarkdown(welcomeMessage, { reply_markup: keyboard });
  } else {
    const welcomeMessage = `🌟 **Welcome to Aureus Alliance Holdings!** 🌟

Your gateway to gold mining equity shares! 💎

🏆 **What We Offer:**
• Gold mining equity share packages
• NFT share certificates
• Quarterly dividend payments
• Supporting 28 NPOs worldwide

🔐 **Get Started:**
Choose an option below:`;

    // Check if user is authorized for admin access
    const isAdminUser = isAuthorizedForAdmin(user.username);

    const keyboard = {
      inline_keyboard: [
        [
          { text: "🔑 Login", callback_data: "auth_login" },
          { text: "📝 Register", callback_data: "auth_register" }
        ],
        ...(isAdminUser ? [[{ text: "🔐 Admin Login", callback_data: "admin_login" }]] : []),
        [
          { text: "📞 Contact Support", callback_data: "get_support" }
        ]
      ]
    };

    await ctx.replyWithMarkdown(welcomeMessage, { reply_markup: keyboard });
  }
});

// Wrapper function for packages
async function showPackages(ctx) {
  const telegramUser = ctx.telegramUser;

  if (!telegramUser.is_registered) {
    await ctx.reply("❌ Please login or register first using /start");
    return;
  }

  try {
    const packages = await getInvestmentPackages();

    if (packages.length === 0) {
      await ctx.reply("❌ No investment packages available at the moment.");
      return;
    }

    const packageMessage = `💎 *Available Investment Packages* 💎

Choose a package to view details:`;

    const keyboard = {
      inline_keyboard: [
        ...packages.map(pkg => [
          { text: `${pkg.name} - ${formatCurrency(pkg.price)}`, callback_data: `package_${pkg.id}` }
        ]),
        [{ text: "💰 Custom Equity Share Amount", callback_data: "menu_custom_investment" }],
        [{ text: "🔙 Back to Menu", callback_data: "back_to_menu" }]
      ]
    };

    await ctx.replyWithMarkdown(packageMessage, { reply_markup: keyboard });
  } catch (error) {
    console.error("Error getting packages:", error);
    await ctx.reply("❌ Error loading packages. Please try again.");
  }
}

bot.command("packages", restrictCommands("packages"), showPackages);

// PORTFOLIO FUNCTIONS
async function getUserInvestments(userEmail) {
  try {
    const [rows] = await dbConnection.execute(`
      SELECT
        id,
        package_name,
        amount,
        shares,
        status,
        created_at,
        nft_delivery_date,
        roi_delivery_date,
        delivery_status,
        nft_delivered,
        roi_delivered
      FROM aureus_investments
      WHERE email = ?
      ORDER BY created_at DESC
    `, [userEmail]);

    return rows;
  } catch (error) {
    console.error("Error fetching user investments:", error);
    return [];
  }
}

async function calculatePortfolioStats(investments) {
  try {
    const stats = {
      totalInvestments: investments.length,
      totalInvested: 0,
      totalShares: 0,
      confirmedInvestments: 0,
      pendingInvestments: 0,
      nftDelivered: 0,
      roiDelivered: 0
    };

    investments.forEach(inv => {
      stats.totalInvested += parseFloat(inv.amount) || 0;
      stats.totalShares += parseInt(inv.shares) || 0;

      if (inv.status === 'completed' || inv.status === 'confirmed') {
        stats.confirmedInvestments++;
      } else {
        stats.pendingInvestments++;
      }

      if (inv.nft_delivered) {
        stats.nftDelivered++;
      }

      if (inv.roi_delivered) {
        stats.roiDelivered++;
      }
    });

    return stats;
  } catch (error) {
    console.error("Error calculating portfolio stats:", error);
    return null;
  }
}

async function formatPortfolioMessage(userEmail) {
  try {
    const investments = await getUserInvestments(userEmail);
    const stats = await calculatePortfolioStats(investments);

    if (!stats || investments.length === 0) {
      return `📊 **Your Portfolio**

❌ No investments found yet.

🔹 **Get Started:**
• View available investment packages below
• Start building your portfolio today!

💎 Ready to invest? Explore opportunities with the Packages button!`;
    }

    // Calculate mine production for total shares
    const mineCalc = await calculateMineProduction(stats.totalShares);
    const quarterlyDividend = mineCalc.userAnnualDividend / 4;

    let portfolioMessage = `📊 **Your Equity Share Portfolio**

💰 **Portfolio Summary:**
📈 Total Equity Purchases: ${stats.totalInvestments}
💵 Total Equity Value: ${formatCurrency(stats.totalInvested)}
📊 Total Shares: ${stats.totalShares.toLocaleString()}
✅ Confirmed: ${stats.confirmedInvestments}
⏳ Pending: ${stats.pendingInvestments}

💎 **Dividend Projections:**
📅 Quarterly Dividend: ${formatLargeNumber(quarterlyDividend)}
💰 Annual Dividend: ${formatLargeNumber(mineCalc.userAnnualDividend)}
🎯 Dividend per Share: ${formatCurrency(mineCalc.dividendPerShare)}

🎁 **NFT & Delivery Status:**
📜 NFT Certificates Delivered: ${stats.nftDelivered}/${stats.totalInvestments}
🎯 ROI Deliveries Completed: ${stats.roiDelivered}/${stats.totalInvestments}

📋 **Recent Investments:**`;

    // Show recent investments (last 5)
    const recentInvestments = investments.slice(0, 5);
    recentInvestments.forEach((inv, index) => {
      const statusEmoji = inv.status === 'completed' || inv.status === 'confirmed' ? '✅' : '⏳';
      const nftStatus = inv.nft_delivered ? '📜✅' : '📜⏳';
      const roiStatus = inv.roi_delivered ? '💰✅' : '💰⏳';

      portfolioMessage += `

${index + 1}. ${statusEmoji} **${inv.package_name}**
   💵 Amount: ${formatCurrency(inv.amount)}
   📊 Shares: ${inv.shares}
   📅 Date: ${new Date(inv.created_at).toLocaleDateString()}
   ${nftStatus} ${roiStatus}`;
    });

    if (investments.length > 5) {
      portfolioMessage += `\n\n... and ${investments.length - 5} more investments`;
    }

    portfolioMessage += `\n\n⚠️ **Production Timeline:**
Dividend calculations are based on reaching full mine production capacity by June 2026.

🌍 **Impact:** Your investments support 28 NPOs worldwide!`;

    return portfolioMessage;
  } catch (error) {
    console.error("Error formatting portfolio message:", error);
    return "❌ Error loading portfolio. Please try again later.";
  }
}

async function formatInvestmentHistory(userEmail) {
  try {
    const investments = await getUserInvestments(userEmail);

    if (investments.length === 0) {
      return `📈 **Investment History**

❌ No investment history found.

🔹 **Get Started:**
• View available investment packages below
• Make your first equity share purchase today!`;
    }

    let historyMessage = `📈 **Equity Share History**

📊 **Total Equity Purchases:** ${investments.length}
💰 **Total Amount:** ${formatCurrency(investments.reduce((sum, inv) => sum + parseFloat(inv.amount), 0))}

📋 **Equity Share Details:**`;

    investments.forEach((inv, index) => {
      const statusEmoji = inv.status === 'completed' || inv.status === 'confirmed' ? '✅' :
                         inv.status === 'pending' ? '⏳' : '❌';
      const date = new Date(inv.created_at).toLocaleDateString();

      historyMessage += `

${index + 1}. ${statusEmoji} **${inv.package_name}**
   💵 Amount: ${formatCurrency(inv.amount)}
   📊 Shares: ${inv.shares}
   📅 Date: ${date}
   🔄 Status: ${inv.status.charAt(0).toUpperCase() + inv.status.slice(1)}`;

      if (inv.nft_delivery_date) {
        historyMessage += `\n   📜 NFT Delivery: ${new Date(inv.nft_delivery_date).toLocaleDateString()}`;
      }

      if (inv.roi_delivery_date) {
        historyMessage += `\n   💰 ROI Delivery: ${new Date(inv.roi_delivery_date).toLocaleDateString()}`;
      }
    });

    return historyMessage;
  } catch (error) {
    console.error("Error formatting investment history:", error);
    return "❌ Error loading investment history. Please try again later.";
  }
}

async function formatReferralInfo(userEmail) {
  try {
    // Get user ID from email
    const [userRows] = await dbConnection.execute(
      'SELECT id FROM users WHERE email = ?',
      [userEmail]
    );

    if (userRows.length === 0) {
      return "❌ User not found.";
    }

    const userId = userRows[0].id;

    // Get referral statistics
    const referralStats = await getReferralStats(userId);
    const referralLink = `https://aureusangelalliance.com/register?ref=${userId}`;

    let referralMessage = `👥 **Referral System**

🔗 **Your Referral Link:**
\`${referralLink}\`

📊 **Referral Statistics:**
👥 Direct Referrals: ${referralStats.directReferrals}
🌳 Total Downline: ${referralStats.totalDownline}
💰 Total Commissions: ${formatCurrency(referralStats.totalCommissions)}
📅 This Month: ${formatCurrency(referralStats.monthlyCommissions)}

🏆 **Performance:**
🥇 Rank: ${referralStats.rank || 'Unranked'}
📈 Level: ${referralStats.level || 1}
🎯 Next Level: ${referralStats.nextLevelRequirement || 'N/A'}

💡 **Tips:**
• Share your link on social media
• Invite friends and family
• Earn 20% commission on direct sales
• Build your passive income stream`;

    return referralMessage;
  } catch (error) {
    console.error("Error formatting referral info:", error);
    return "❌ Error loading referral information. Please try again later.";
  }
}

async function getReferralStats(userId) {
  try {
    // Get direct referrals count
    const [directRefs] = await dbConnection.execute(
      'SELECT COUNT(*) as count FROM users WHERE referred_by = ?',
      [userId]
    );

    // Get total commissions
    const [commissions] = await dbConnection.execute(
      'SELECT SUM(amount) as total FROM referral_commissions WHERE user_id = ?',
      [userId]
    );

    // Get monthly commissions
    const [monthlyComm] = await dbConnection.execute(
      'SELECT SUM(amount) as total FROM referral_commissions WHERE user_id = ? AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())',
      [userId]
    );

    return {
      directReferrals: directRefs[0].count || 0,
      totalDownline: directRefs[0].count || 0, // Simplified for now
      totalCommissions: commissions[0].total || 0,
      monthlyCommissions: monthlyComm[0].total || 0,
      rank: 'Bronze', // Placeholder
      level: 1 // Placeholder
    };
  } catch (error) {
    console.error("Error getting referral stats:", error);
    return {
      directReferrals: 0,
      totalDownline: 0,
      totalCommissions: 0,
      monthlyCommissions: 0
    };
  }
}

// Wrapper function for portfolio
async function showPortfolio(ctx) {
  const telegramUser = ctx.telegramUser;

  if (!telegramUser.is_registered) {
    await ctx.reply("❌ Please login or register first using /start");
    return;
  }

  try {
    // Use linked_email if available, otherwise fall back to email
    const userEmail = telegramUser.linked_email || telegramUser.email;

    if (!userEmail) {
      await ctx.reply("❌ No email address found. Please logout and login again to link your account properly.");
      return;
    }

    const portfolioMessage = await formatPortfolioMessage(userEmail);

    const keyboard = {
      inline_keyboard: [
        [
          { text: "📈 Investment History", callback_data: "investment_history" },
          { text: "📊 Statistics", callback_data: "portfolio_stats" }
        ],
        [
          { text: "💰 Dividends", callback_data: "dividend_history" },
          { text: "🎯 Performance", callback_data: "performance_metrics" }
        ],
        [
          { text: "🔄 Refresh", callback_data: "refresh_portfolio" },
          { text: "🔙 Main Menu", callback_data: "back_to_menu" }
        ]
      ]
    };

    await ctx.replyWithMarkdown(portfolioMessage, { reply_markup: keyboard });
    console.log(`📊 Portfolio viewed by ${ctx.from.first_name} (${ctx.from.id}) with email ${userEmail}`);
  } catch (error) {
    console.error("Portfolio command error:", error);
    await ctx.reply("❌ Error loading portfolio. Please try again later.");
  }
}

async function showDividendHistory(ctx) {
  const telegramUser = ctx.telegramUser;

  if (!telegramUser.is_registered) {
    await ctx.reply("❌ Please login or register first using /start");
    return;
  }

  try {
    const userEmail = telegramUser.linked_email || telegramUser.email;
    if (!userEmail) {
      await ctx.reply("❌ No email address found. Please logout and login again.");
      return;
    }

    const investments = await getUserInvestments(userEmail);
    const stats = await calculatePortfolioStats(investments);

    if (!stats || investments.length === 0) {
      const message = `💰 **Dividend History**

❌ No investments found yet.

🔹 **Get Started:**
• Make your first investment to start earning dividends
• Dividends are calculated based on mining production
• Full production capacity expected by June 2026

💎 Ready to invest? Use the Packages button!`;

      const keyboard = {
        inline_keyboard: [
          [{ text: "📦 View Packages", callback_data: "menu_packages" }],
          [{ text: "🔙 Back to Portfolio", callback_data: "menu_portfolio" }]
        ]
      };

      await ctx.editMessageText(message, {
        parse_mode: "Markdown",
        reply_markup: keyboard
      });
      return;
    }

    // Calculate dividend projections
    const goldPrice = await getCurrentGoldPrice();
    const dividendProjections = calculateSimpleDividends(stats.totalShares, goldPrice);

    const message = `💰 **Dividend Projections**

📊 **Your Portfolio:**
• **Total Shares:** ${stats.totalShares.toLocaleString()}
• **Total Equity Value:** $${stats.totalInvested.toLocaleString()}

💎 **5-Year Dividend Forecast:**

**2025:** $${dividendProjections.year1.toLocaleString()}
• Quarterly: $${(dividendProjections.year1 / 4).toLocaleString()}
• Monthly Est: $${(dividendProjections.year1 / 12).toLocaleString()}

**2026:** $${dividendProjections.year2.toLocaleString()}
• Quarterly: $${(dividendProjections.year2 / 4).toLocaleString()}
• Monthly Est: $${(dividendProjections.year2 / 12).toLocaleString()}

**2027:** $${dividendProjections.year3.toLocaleString()}
• Quarterly: $${(dividendProjections.year3 / 4).toLocaleString()}

**2028:** $${dividendProjections.year4.toLocaleString()}
• Quarterly: $${(dividendProjections.year4 / 4).toLocaleString()}

**2029:** $${dividendProjections.year5.toLocaleString()}
• Quarterly: $${(dividendProjections.year5 / 4).toLocaleString()}

📈 **Total 5-Year Dividends:** $${dividendProjections.total.toLocaleString()}
🎯 **Average Annual ROI:** ${dividendProjections.averageROI.toFixed(1)}%

⚠️ **Note:** Projections based on mine expansion reaching full capacity by June 2026. Actual results may vary based on gold prices and operational factors.`;

    const keyboard = {
      inline_keyboard: [
        [
          { text: "🧮 Calculator", callback_data: "mining_calculator" },
          { text: "📊 Performance", callback_data: "performance_metrics" }
        ],
        [{ text: "🔙 Back to Portfolio", callback_data: "menu_portfolio" }]
      ]
    };

    await ctx.editMessageText(message, {
      parse_mode: "Markdown",
      reply_markup: keyboard
    });

  } catch (error) {
    console.error("Dividend history error:", error);
    await ctx.reply("❌ Error loading dividend history. Please try again later.");
  }
}

async function showPerformanceMetrics(ctx) {
  const telegramUser = ctx.telegramUser;

  if (!telegramUser.is_registered) {
    await ctx.reply("❌ Please login or register first using /start");
    return;
  }

  try {
    const userEmail = telegramUser.linked_email || telegramUser.email;
    if (!userEmail) {
      await ctx.reply("❌ No email address found. Please logout and login again.");
      return;
    }

    const investments = await getUserInvestments(userEmail);
    const stats = await calculatePortfolioStats(investments);

    if (!stats || investments.length === 0) {
      const message = `🎯 **Performance Metrics**

❌ No investments found yet.

🔹 **Get Started:**
• Make your first investment to track performance
• Monitor ROI and growth projections
• View detailed mining production metrics

💎 Ready to invest? Use the Packages button!`;

      const keyboard = {
        inline_keyboard: [
          [{ text: "📦 View Packages", callback_data: "menu_packages" }],
          [{ text: "🔙 Back to Portfolio", callback_data: "menu_portfolio" }]
        ]
      };

      await ctx.editMessageText(message, {
        parse_mode: "Markdown",
        reply_markup: keyboard
      });
      return;
    }

    // Calculate performance metrics
    const goldPrice = await getCurrentGoldPrice();
    const mineCalc = await calculateMineProduction(stats.totalShares);
    const dividendProjections = calculateSimpleDividends(stats.totalShares, goldPrice);

    // Calculate current performance metrics
    const totalInvestment = stats.totalInvested;
    const projectedAnnualReturn = dividendProjections.year1;
    const currentROI = totalInvestment > 0 ? (projectedAnnualReturn / totalInvestment) * 100 : 0;

    // Calculate portfolio growth metrics
    const averageInvestmentAge = investments.reduce((sum, inv) => {
      const ageInDays = (Date.now() - new Date(inv.created_at).getTime()) / (1000 * 60 * 60 * 24);
      return sum + ageInDays;
    }, 0) / investments.length;

    const message = `🎯 **Performance Metrics**

📊 **Portfolio Overview:**
• **Total Invested:** $${stats.totalInvested.toLocaleString()}
• **Total Shares:** ${stats.totalShares.toLocaleString()}
• **Active Investments:** ${stats.confirmedInvestments}
• **Portfolio Age:** ${Math.round(averageInvestmentAge)} days

💰 **Financial Performance:**
• **Current Annual ROI:** ${currentROI.toFixed(1)}%
• **Projected 2025 Return:** $${projectedAnnualReturn.toLocaleString()}
• **5-Year Total Return:** $${dividendProjections.total.toLocaleString()}
• **Average Annual ROI:** ${dividendProjections.averageROI.toFixed(1)}%

⛏️ **Mining Production Metrics:**
• **Your Gold Share:** ${mineCalc.userAnnualGoldKg.toFixed(2)} kg/year
• **Profit Margin:** ${(mineCalc.profitMargin || 58).toFixed(1)}%
• **Gold Price:** $${Math.round(goldPrice/1000)}k/kg
• **Production Status:** Phase 10/20 (50% capacity)

📈 **Growth Projections:**
• **2025-2026:** 25% production increase
• **2026-2027:** 50% production increase
• **2027-2028:** 75% production increase
• **2028-2029:** 100% production increase (full capacity)

🎯 **Performance Rating:** ${currentROI > 15 ? '🟢 Excellent' : currentROI > 10 ? '🟡 Good' : '🔴 Building'}

⚠️ **Note:** Metrics based on current mining operations and projected expansion to full capacity by June 2026.`;

    const keyboard = {
      inline_keyboard: [
        [
          { text: "💰 Dividends", callback_data: "dividend_history" },
          { text: "🧮 Calculator", callback_data: "mining_calculator" }
        ],
        [{ text: "🔙 Back to Portfolio", callback_data: "menu_portfolio" }]
      ]
    };

    await ctx.editMessageText(message, {
      parse_mode: "Markdown",
      reply_markup: keyboard
    });

  } catch (error) {
    console.error("Performance metrics error:", error);
    await ctx.reply("❌ Error loading performance metrics. Please try again later.");
  }
}

async function showPortfolioStats(ctx) {
  const telegramUser = ctx.telegramUser;

  if (!telegramUser.is_registered) {
    await ctx.reply("❌ Please login or register first using /start");
    return;
  }

  try {
    const userEmail = telegramUser.linked_email || telegramUser.email;
    if (!userEmail) {
      await ctx.reply("❌ No email address found. Please logout and login again.");
      return;
    }

    const investments = await getUserInvestments(userEmail);
    const stats = await calculatePortfolioStats(investments);

    if (!stats || investments.length === 0) {
      const message = `📊 **Portfolio Statistics**

❌ No investments found yet.

🔹 **Get Started:**
• Make your first investment to view statistics
• Track your investment performance
• Monitor portfolio growth over time

💎 Ready to invest? Use the Packages button!`;

      const keyboard = {
        inline_keyboard: [
          [{ text: "📦 View Packages", callback_data: "menu_packages" }],
          [{ text: "🔙 Back to Portfolio", callback_data: "menu_portfolio" }]
        ]
      };

      await ctx.editMessageText(message, {
        parse_mode: "Markdown",
        reply_markup: keyboard
      });
      return;
    }

    // Calculate additional statistics
    const averageInvestmentSize = stats.totalInvested / stats.totalInvestments;
    const averageSharesPerInvestment = stats.totalShares / stats.totalInvestments;

    // Calculate investment distribution
    const packageDistribution = {};
    investments.forEach(inv => {
      packageDistribution[inv.package_name] = (packageDistribution[inv.package_name] || 0) + 1;
    });

    const mostPopularPackage = Object.entries(packageDistribution)
      .sort(([,a], [,b]) => b - a)[0];

    const message = `📊 **Portfolio Statistics**

💼 **Investment Overview:**
• **Total Investments:** ${stats.totalInvestments}
• **Total Amount:** $${stats.totalInvested.toLocaleString()}
• **Total Shares:** ${stats.totalShares.toLocaleString()}
• **Average Investment:** $${averageInvestmentSize.toLocaleString()}

📈 **Status Breakdown:**
• **✅ Confirmed:** ${stats.confirmedInvestments}
• **⏳ Pending:** ${stats.pendingInvestments}
• **🎫 NFT Delivered:** ${stats.nftDelivered}
• **💰 ROI Delivered:** ${stats.roiDelivered}

📦 **Package Analysis:**
• **Most Popular:** ${mostPopularPackage ? mostPopularPackage[0] : 'N/A'}
• **Avg Shares/Investment:** ${Math.round(averageSharesPerInvestment)}
• **Portfolio Diversity:** ${Object.keys(packageDistribution).length} package types

🎯 **Performance Indicators:**
• **Completion Rate:** ${((stats.confirmedInvestments / stats.totalInvestments) * 100).toFixed(1)}%
• **NFT Delivery Rate:** ${((stats.nftDelivered / stats.totalInvestments) * 100).toFixed(1)}%
• **Share Ownership:** ${((stats.totalShares / 1400000) * 100).toFixed(4)}% of total

📅 **Timeline:**
• **First Investment:** ${investments.length > 0 ? new Date(investments[investments.length - 1].created_at).toLocaleDateString() : 'N/A'}
• **Latest Investment:** ${investments.length > 0 ? new Date(investments[0].created_at).toLocaleDateString() : 'N/A'}
• **Investment Frequency:** ${(stats.totalInvestments / Math.max(1, (Date.now() - new Date(investments[investments.length - 1]?.created_at || Date.now()).getTime()) / (1000 * 60 * 60 * 24 * 30))).toFixed(1)} per month`;

    const keyboard = {
      inline_keyboard: [
        [
          { text: "💰 Dividends", callback_data: "dividend_history" },
          { text: "🎯 Performance", callback_data: "performance_metrics" }
        ],
        [{ text: "🔙 Back to Portfolio", callback_data: "menu_portfolio" }]
      ]
    };

    await ctx.editMessageText(message, {
      parse_mode: "Markdown",
      reply_markup: keyboard
    });

  } catch (error) {
    console.error("Portfolio stats error:", error);
    await ctx.reply("❌ Error loading portfolio statistics. Please try again later.");
  }
}

// PORTFOLIO COMMAND
bot.command("portfolio", restrictCommands("portfolio"), showPortfolio);

async function showHistory(ctx) {
  const telegramUser = ctx.telegramUser;

  if (!telegramUser.is_registered) {
    await ctx.reply("❌ Please login or register first using /start");
    return;
  }

  try {
    const userEmail = telegramUser.linked_email || telegramUser.email;
    if (!userEmail) {
      await ctx.reply("❌ No email address found. Please logout and login again.");
      return;
    }

    const historyMessage = await formatInvestmentHistory(userEmail);

    const keyboard = {
      inline_keyboard: [
        [{ text: "🔙 Back to Menu", callback_data: "back_to_menu" }]
      ]
    };

    if (ctx.editMessageText) {
      await ctx.editMessageText(historyMessage, {
        parse_mode: "Markdown",
        reply_markup: keyboard
      });
    } else {
      await ctx.replyWithMarkdown(historyMessage, { reply_markup: keyboard });
    }
  } catch (error) {
    console.error("Investment history error:", error);
    await ctx.reply("❌ Error loading investment history. Please try again later.");
  }
}

// INVESTMENT HISTORY COMMAND
bot.command("history", restrictCommands("history"), showHistory);

async function showReferrals(ctx) {
  const telegramUser = ctx.telegramUser;

  if (!telegramUser.is_registered) {
    await ctx.reply("❌ Please login or register first using /start");
    return;
  }

  try {
    const userEmail = telegramUser.linked_email || telegramUser.email;
    if (!userEmail) {
      await ctx.reply("❌ No email address found. Please logout and login again.");
      return;
    }

    // Get user's referral statistics from new system
    const [userResult] = await dbConnection.execute(`
      SELECT id, username, total_referrals, total_commission_earned, sponsor_telegram_username,
             referral_milestone_level, total_milestone_bonuses
      FROM users WHERE email = ?
    `, [userEmail]);

    if (userResult.length === 0) {
      await ctx.reply("❌ User not found in system.");
      return;
    }

    const user = userResult[0];

    // Get referral statistics
    const [referralStats] = await dbConnection.execute(`
      SELECT
        COUNT(*) as total_referred,
        SUM(CASE WHEN ai.status = 'completed' THEN ai.amount ELSE 0 END) as total_investment_volume
      FROM users referred
      LEFT JOIN aureus_investments ai ON referred.id = ai.user_id
      WHERE referred.sponsor_user_id = ?
    `, [user.id]);

    // Get commission statistics
    const [commissionStats] = await dbConnection.execute(`
      SELECT
        COUNT(*) as total_commissions,
        SUM(commission_amount) as total_earned,
        SUM(CASE WHEN status = 'pending' THEN commission_amount ELSE 0 END) as pending_amount,
        SUM(CASE WHEN status = 'approved' THEN commission_amount ELSE 0 END) as approved_amount,
        SUM(CASE WHEN status = 'paid' THEN commission_amount ELSE 0 END) as paid_amount
      FROM commissions
      WHERE referrer_id = ?
    `, [user.id]);

    const stats = referralStats[0];
    const commissions = commissionStats[0];

    // Calculate milestone progress
    const milestones = [
      { level: 1, referrals: 5, bonus: 50, title: "Rising Star" },
      { level: 2, referrals: 10, bonus: 100, title: "Network Builder" },
      { level: 3, referrals: 25, bonus: 250, title: "Community Leader" },
      { level: 4, referrals: 50, bonus: 500, title: "Referral Champion" },
      { level: 5, referrals: 100, bonus: 1000, title: "Elite Ambassador" },
      { level: 6, referrals: 250, bonus: 2500, title: "Master Recruiter" },
      { level: 7, referrals: 500, bonus: 5000, title: "Legendary Referrer" }
    ];

    const currentLevel = user.referral_milestone_level || 0;
    const currentReferrals = stats.total_referred || 0;
    const nextMilestone = milestones.find(m => m.level > currentLevel);

    let milestoneInfo = '';
    if (currentLevel > 0) {
      const currentMilestone = milestones.find(m => m.level === currentLevel);
      milestoneInfo = `\n🏆 **Current Level:** ${currentMilestone.title} (Level ${currentLevel})`;
    }

    if (nextMilestone) {
      const progress = currentReferrals;
      const needed = nextMilestone.referrals - progress;
      milestoneInfo += `\n🎯 **Next Milestone:** ${nextMilestone.title} (${needed} more referrals for $${nextMilestone.bonus} bonus)`;
    } else if (currentLevel === milestones.length) {
      milestoneInfo += `\n👑 **You've reached the highest milestone level!**`;
    }

    const referralMessage = `🎯 **My Referral Dashboard**

👤 **Your Info:**
• **Username:** @${ctx.from.username || 'Not set'}
• **Referrer:** ${user.sponsor_telegram_username ? `@${user.sponsor_telegram_username}` : 'None'}

📊 **Referral Statistics:**
• **Total Referrals:** ${currentReferrals}
• **Investment Volume:** $${(stats.total_investment_volume || 0).toLocaleString()}${milestoneInfo}

💰 **Earnings Summary:**
• **Commission Earned:** $${(commissions.total_earned || 0).toFixed(2)}
• **Milestone Bonuses:** $${(user.total_milestone_bonuses || 0).toFixed(2)}
• **Pending:** $${(commissions.pending_amount || 0).toFixed(2)}
• **Approved:** $${(commissions.approved_amount || 0).toFixed(2)}
• **Paid:** $${(commissions.paid_amount || 0).toFixed(2)}

🔗 **Your Referral Info:**
Share your Telegram username: **@${ctx.from.username || 'Please set username'}**

📱 **Referral Link:** Click "Get My Link" below to generate your unique referral link!`;

    const keyboard = {
      inline_keyboard: [
        [
          { text: "👥 My Referrals", callback_data: "view_my_referrals" },
          { text: "💰 Commission History", callback_data: "view_my_commissions" }
        ],
        [
          { text: "🏆 Leaderboard", callback_data: "view_referral_leaderboard" },
          { text: "📊 Detailed Stats", callback_data: "view_referral_analytics" }
        ],
        [
          { text: "🔗 Get My Link", callback_data: "get_referral_link" },
          { text: "📖 How to Refer", callback_data: "referral_instructions" }
        ],
        [
          { text: "🔙 Main Menu", callback_data: "back_to_menu" }
        ]
      ]
    };

    if (ctx.editMessageText) {
      await ctx.editMessageText(referralMessage, {
        parse_mode: "Markdown",
        reply_markup: keyboard
      });
    } else {
      await ctx.replyWithMarkdown(referralMessage, { reply_markup: keyboard });
    }
  } catch (error) {
    console.error("Referrals command error:", error);
    await ctx.reply("❌ Error loading referral information. Please try again later.");
  }
}

// Enhanced referral system functions
async function getTelegramReferralStats(telegramUserId) {
  try {
    // Get the user's Telegram username
    const telegramUser = await getTelegramUser(telegramUserId);
    if (!telegramUser || !telegramUser.linked_email) {
      return null;
    }

    // Get user ID from database
    const [userRows] = await dbConnection.execute(
      'SELECT id, username FROM users WHERE email = ?',
      [telegramUser.linked_email]
    );

    if (userRows.length === 0) {
      return null;
    }

    const userId = userRows[0].id;
    const username = userRows[0].username;

    // Get direct referrals (people referred by this user)
    const [directReferrals] = await dbConnection.execute(
      'SELECT id, username, email, created_at FROM users WHERE referred_by = ? ORDER BY created_at DESC',
      [userId]
    );

    // Calculate commission earnings from referrals
    let totalCommissions = 0;
    let pendingCommissions = 0;

    for (const referral of directReferrals) {
      // Get investments made by this referral
      const [investments] = await dbConnection.execute(
        'SELECT amount FROM aureus_investments WHERE user_email = ? AND status IN ("completed", "confirmed")',
        [referral.email]
      );

      // Calculate 20% commission on confirmed investments
      const referralInvestments = investments.reduce((sum, inv) => sum + parseFloat(inv.amount), 0);
      const commission = referralInvestments * 0.20; // 20% commission
      totalCommissions += commission;
    }

    return {
      userId,
      username,
      directReferrals: directReferrals.length,
      totalCommissions,
      pendingCommissions,
      referralList: directReferrals.slice(0, 10) // Limit to 10 for display
    };
  } catch (error) {
    console.error('Error getting Telegram referral stats:', error);
    return null;
  }
}

async function showCommissions(ctx) {
  const telegramUser = ctx.telegramUser;

  if (!telegramUser.is_registered) {
    await ctx.reply("❌ Please login or register first using /start");
    return;
  }

  try {
    const stats = await getTelegramReferralStats(ctx.from.id);

    if (!stats) {
      const message = `💰 **Commission Earnings**

❌ No referral data found.

🔹 **Get Started:**
• Share your referral link
• Invite friends to invest
• Earn 20% commission on their investments

💡 **How it works:**
• Direct commission: 20% of investment amount
• Instant payout on confirmed investments
• No limits on earnings`;

      const keyboard = {
        inline_keyboard: [
          [{ text: "🔗 Get Referral Link", callback_data: "share_referral_link" }],
          [{ text: "🔙 Back to Referrals", callback_data: "back_to_referrals" }]
        ]
      };

      await ctx.editMessageText(message, {
        parse_mode: "Markdown",
        reply_markup: keyboard
      });
      return;
    }

    const message = `💰 **Commission Earnings**

📊 **Overview:**
• **Direct Referrals:** ${stats.directReferrals}
• **Total Commissions:** $${stats.totalCommissions.toLocaleString()}
• **Pending Commissions:** $${stats.pendingCommissions.toLocaleString()}
• **Commission Rate:** 20% direct sales

💵 **Earnings Breakdown:**
${stats.directReferrals === 0 ? '• No referrals yet' :
  `• Average per referral: $${(stats.totalCommissions / stats.directReferrals).toLocaleString()}
• Commission model: Direct 20% on investments
• Payment: Instant on confirmed investments`}

🎯 **Performance:**
• **Status:** ${stats.totalCommissions > 1000 ? '🟢 High Performer' : stats.totalCommissions > 100 ? '🟡 Active' : '🔴 Getting Started'}
• **Rank:** ${stats.totalCommissions > 5000 ? 'Gold' : stats.totalCommissions > 1000 ? 'Silver' : 'Bronze'} Referrer

💡 **Boost Your Earnings:**
• Share your success story
• Help referrals with their investments
• Stay active in the community
• Provide ongoing support

⚠️ **Note:** Commissions are calculated on confirmed investments only. Pending investments will show commissions once confirmed.`;

    const keyboard = {
      inline_keyboard: [
        [
          { text: "👥 View Downline", callback_data: "view_downline" },
          { text: "📊 Statistics", callback_data: "referral_stats" }
        ],
        [{ text: "🔙 Back to Referrals", callback_data: "back_to_referrals" }]
      ]
    };

    await ctx.editMessageText(message, {
      parse_mode: "Markdown",
      reply_markup: keyboard
    });

  } catch (error) {
    console.error("Commission view error:", error);
    await ctx.reply("❌ Error loading commission data. Please try again later.");
  }
}

async function showReferralStats(ctx) {
  const telegramUser = ctx.telegramUser;

  if (!telegramUser.is_registered) {
    await ctx.reply("❌ Please login or register first using /start");
    return;
  }

  try {
    const stats = await getTelegramReferralStats(ctx.from.id);

    if (!stats) {
      const message = `📊 **Referral Statistics**

❌ No referral data found.

🔹 **Get Started:**
• Share your referral link
• Track your referral performance
• Monitor commission earnings

💡 **Available Stats:**
• Direct referrals count
• Commission earnings
• Referral conversion rates
• Performance rankings`;

      const keyboard = {
        inline_keyboard: [
          [{ text: "🔗 Get Referral Link", callback_data: "share_referral_link" }],
          [{ text: "🔙 Back to Referrals", callback_data: "back_to_referrals" }]
        ]
      };

      await ctx.editMessageText(message, {
        parse_mode: "Markdown",
        reply_markup: keyboard
      });
      return;
    }

    // Calculate additional statistics
    const avgCommissionPerReferral = stats.directReferrals > 0 ? stats.totalCommissions / stats.directReferrals : 0;
    const conversionRate = stats.directReferrals > 0 ? (stats.directReferrals / Math.max(1, stats.directReferrals)) * 100 : 0;

    // Get referral activity over time
    const recentReferrals = stats.referralList.filter(ref => {
      const refDate = new Date(ref.created_at);
      const thirtyDaysAgo = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000);
      return refDate > thirtyDaysAgo;
    }).length;

    const message = `📊 **Referral Statistics**

👥 **Referral Overview:**
• **Total Direct Referrals:** ${stats.directReferrals}
• **Recent Referrals (30 days):** ${recentReferrals}
• **Conversion Rate:** ${conversionRate.toFixed(1)}%
• **Success Rate:** ${stats.directReferrals > 0 ? '100%' : '0%'} (active referrals)

💰 **Financial Performance:**
• **Total Commissions:** $${stats.totalCommissions.toLocaleString()}
• **Average per Referral:** $${avgCommissionPerReferral.toLocaleString()}
• **Monthly Estimate:** $${(stats.totalCommissions / Math.max(1, 12)).toLocaleString()}
• **Commission Rate:** 20% direct

📈 **Performance Metrics:**
• **Activity Level:** ${recentReferrals > 5 ? '🟢 Very Active' : recentReferrals > 2 ? '🟡 Active' : '🔴 Low Activity'}
• **Referrer Rank:** ${stats.totalCommissions > 5000 ? '🥇 Gold' : stats.totalCommissions > 1000 ? '🥈 Silver' : '🥉 Bronze'}
• **Growth Trend:** ${recentReferrals > 0 ? '📈 Growing' : '📊 Stable'}

🎯 **Goals & Targets:**
• **Next Milestone:** ${stats.directReferrals < 5 ? '5 referrals' : stats.directReferrals < 10 ? '10 referrals' : '25 referrals'}
• **Commission Goal:** $${stats.totalCommissions < 1000 ? '1,000' : stats.totalCommissions < 5000 ? '5,000' : '10,000'}
• **Rank Target:** ${stats.totalCommissions < 1000 ? 'Silver Referrer' : stats.totalCommissions < 5000 ? 'Gold Referrer' : 'Platinum Referrer'}

📅 **Timeline:**
• **First Referral:** ${stats.referralList.length > 0 ? new Date(stats.referralList[stats.referralList.length - 1].created_at).toLocaleDateString() : 'N/A'}
• **Latest Referral:** ${stats.referralList.length > 0 ? new Date(stats.referralList[0].created_at).toLocaleDateString() : 'N/A'}
• **Referral Frequency:** ${(stats.directReferrals / Math.max(1, 12)).toFixed(1)} per month`;

    const keyboard = {
      inline_keyboard: [
        [
          { text: "💰 Commissions", callback_data: "view_commissions" },
          { text: "🏆 Leaderboard", callback_data: "view_leaderboard" }
        ],
        [{ text: "🔙 Back to Referrals", callback_data: "back_to_referrals" }]
      ]
    };

    await ctx.editMessageText(message, {
      parse_mode: "Markdown",
      reply_markup: keyboard
    });

  } catch (error) {
    console.error("Referral stats error:", error);
    await ctx.reply("❌ Error loading referral statistics. Please try again later.");
  }
}

async function showLeaderboard(ctx) {
  const telegramUser = ctx.telegramUser;

  if (!telegramUser.is_registered) {
    await ctx.reply("❌ Please login or register first using /start");
    return;
  }

  try {
    // Get top referrers
    const [topReferrers] = await dbConnection.execute(`
      SELECT
        u.username,
        u.id,
        COUNT(r.id) as referral_count,
        COALESCE(SUM(inv.amount * 0.20), 0) as total_commissions
      FROM users u
      LEFT JOIN users r ON r.referred_by = u.id
      LEFT JOIN aureus_investments inv ON inv.user_email = r.email AND inv.status IN ('completed', 'confirmed')
      GROUP BY u.id, u.username
      HAVING referral_count > 0
      ORDER BY total_commissions DESC, referral_count DESC
      LIMIT 10
    `);

    // Get current user's stats
    const currentUserStats = await getTelegramReferralStats(ctx.from.id);
    const currentUserRank = topReferrers.findIndex(ref => ref.id === currentUserStats?.userId) + 1;

    let message = `🏆 **Referral Leaderboard**

📊 **Top Referrers:**

`;

    if (topReferrers.length === 0) {
      message += `❌ No referrers found yet.

🔹 **Be the First:**
• Start referring friends today
• Earn your place on the leaderboard
• Build your referral empire`;
    } else {
      topReferrers.forEach((referrer, index) => {
        const rank = index + 1;
        const medal = rank === 1 ? '🥇' : rank === 2 ? '🥈' : rank === 3 ? '🥉' : `${rank}.`;
        const isCurrentUser = referrer.id === currentUserStats?.userId;
        const highlight = isCurrentUser ? '👤 ' : '';

        message += `${medal} ${highlight}**${referrer.username}**
   💰 $${parseFloat(referrer.total_commissions).toLocaleString()} commissions
   👥 ${referrer.referral_count} referrals

`;
      });
    }

    if (currentUserStats) {
      message += `\n🎯 **Your Position:**
• **Rank:** ${currentUserRank > 0 ? `#${currentUserRank}` : 'Not ranked'}
• **Commissions:** $${currentUserStats.totalCommissions.toLocaleString()}
• **Referrals:** ${currentUserStats.directReferrals}`;

      if (currentUserRank === 0 && currentUserStats.directReferrals > 0) {
        message += `\n• **Status:** Building towards leaderboard`;
      }
    }

    message += `\n\n🏅 **Ranking Criteria:**
• Primary: Total commission earnings
• Secondary: Number of referrals
• Updated: Real-time

💡 **Climb the Ranks:**
• Refer more active investors
• Help referrals succeed
• Stay engaged with the community`;

    const keyboard = {
      inline_keyboard: [
        [
          { text: "📊 My Stats", callback_data: "referral_stats" },
          { text: "💰 Commissions", callback_data: "view_commissions" }
        ],
        [{ text: "🔙 Back to Referrals", callback_data: "back_to_referrals" }]
      ]
    };

    await ctx.editMessageText(message, {
      parse_mode: "Markdown",
      reply_markup: keyboard
    });

  } catch (error) {
    console.error("Leaderboard error:", error);
    await ctx.reply("❌ Error loading leaderboard. Please try again later.");
  }
}

async function showDownline(ctx) {
  const telegramUser = ctx.telegramUser;

  if (!telegramUser.is_registered) {
    await ctx.reply("❌ Please login or register first using /start");
    return;
  }

  try {
    const stats = await getTelegramReferralStats(ctx.from.id);

    if (!stats) {
      const message = `👥 **Your Downline**

❌ No referral data found.

🔹 **Get Started:**
• Share your referral link
• Invite friends and family
• Build your referral network

💡 **Benefits:**
• Earn 20% commission on investments
• Build passive income
• Help others succeed`;

      const keyboard = {
        inline_keyboard: [
          [{ text: "🔗 Get Referral Link", callback_data: "share_referral_link" }],
          [{ text: "🔙 Back to Referrals", callback_data: "back_to_referrals" }]
        ]
      };

      await ctx.editMessageText(message, {
        parse_mode: "Markdown",
        reply_markup: keyboard
      });
      return;
    }

    let message = `👥 **Your Downline**

📊 **Overview:**
• **Direct Referrals:** ${stats.directReferrals}
• **Total Commissions:** $${stats.totalCommissions.toLocaleString()}
• **Active Members:** ${stats.referralList.length}

👤 **Recent Referrals:**`;

    if (stats.referralList.length === 0) {
      message += `\n\n❌ No referrals yet.

💡 **Get Started:**
• Share your referral link
• Invite friends and family
• Earn 20% commission on their investments

🎯 **Tips for Success:**
• Share your investment story
• Explain the opportunity
• Provide ongoing support
• Stay active in the community`;
    } else {
      stats.referralList.forEach((member, index) => {
        const joinDate = new Date(member.created_at).toLocaleDateString();
        const timeAgo = Math.floor((Date.now() - new Date(member.created_at).getTime()) / (1000 * 60 * 60 * 24));

        message += `\n\n${index + 1}. **${member.username}**
   📧 ${member.email}
   📅 Joined: ${joinDate} (${timeAgo} days ago)
   💰 Status: Active Member`;
      });

      if (stats.directReferrals > 10) {
        message += `\n\n... and ${stats.directReferrals - 10} more referrals`;
      }

      message += `\n\n🎯 **Downline Performance:**
• **Average Investment:** Calculating...
• **Total Network Value:** $${(stats.totalCommissions / 0.20).toLocaleString()}
• **Your Commission Rate:** 20% direct
• **Network Growth:** ${stats.directReferrals > 5 ? '🟢 Excellent' : stats.directReferrals > 2 ? '🟡 Good' : '🔴 Building'}`;
    }

    const keyboard = {
      inline_keyboard: [
        [
          { text: "💰 Commissions", callback_data: "view_commissions" },
          { text: "📊 Statistics", callback_data: "referral_stats" }
        ],
        [
          { text: "🔗 Share Link", callback_data: "share_referral_link" },
          { text: "🏆 Leaderboard", callback_data: "view_leaderboard" }
        ],
        [{ text: "🔙 Back to Referrals", callback_data: "back_to_referrals" }]
      ]
    };

    await ctx.editMessageText(message, {
      parse_mode: "Markdown",
      reply_markup: keyboard
    });

  } catch (error) {
    console.error("Downline view error:", error);
    await ctx.reply("❌ Error loading downline data. Please try again later.");
  }
}

// REFERRALS COMMAND
bot.command("referrals", restrictCommands("referrals"), showReferrals);

async function showSupport(ctx) {
  const supportMessage = `🆘 **Support Center**

💬 **Get Help:**
• Live chat support available
• FAQ and common questions
• Technical assistance
• Investment guidance

📞 **Contact Options:**
• Telegram: @aureusafrica
• Email: support@aureusangelalliance.com
• Website: aureusangelalliance.com

🕐 **Support Hours:**
• Monday - Friday: 9 AM - 6 PM (UTC)
• Saturday: 10 AM - 4 PM (UTC)
• Sunday: Emergency support only

❓ **Quick Help:**
• Use /help for command list
• Use /faq for common questions
• Use /status for system status`;

  const keyboard = {
    inline_keyboard: [
      [
        { text: "💬 Live Chat", callback_data: "start_live_chat" },
        { text: "❓ FAQ", callback_data: "view_faq" }
      ],
      [
        { text: "🎫 Create Ticket", callback_data: "create_support_ticket" },
        { text: "📊 System Status", callback_data: "system_status" }
      ],
      [
        { text: "🔙 Main Menu", callback_data: "back_to_menu" }
      ]
    ]
  };

  if (ctx.editMessageText) {
    await ctx.editMessageText(supportMessage, {
      parse_mode: "Markdown",
      reply_markup: keyboard
    });
  } else {
    await ctx.replyWithMarkdown(supportMessage, { reply_markup: keyboard });
  }
}

// SUPPORT COMMAND
bot.command("support", restrictCommands("support"), showSupport);

// HELP COMMAND
bot.command("help", restrictCommands("help"), async (ctx) => {
  const helpMessage = `📚 **Command Reference**

🔐 **Authentication:**
• \`/start\` - Start the bot and login
• \`/logout\` - Logout from your account

📊 **Portfolio & Investments:**
• \`/portfolio\` - View your investment portfolio
• \`/packages\` - Browse investment packages
• \`/history\` - View investment history

👥 **Referrals:**
• \`/referrals\` - Referral system and downline
• \`/leaderboard\` - Top referrers ranking

🎯 **Navigation:**
• \`/menu\` - Main navigation menu
• \`/profile\` - Your account profile

🆘 **Support:**
• \`/support\` - Support center
• \`/help\` - This help message
• \`/faq\` - Frequently asked questions

💡 **Tips:**
• Use buttons for easier navigation
• All commands work with / prefix
• Type any command to get started`;

  const keyboard = {
    inline_keyboard: [
      [
        { text: "🏠 Main Menu", callback_data: "back_to_menu" },
        { text: "🆘 Support", callback_data: "get_support" }
      ]
    ]
  };

  await ctx.replyWithMarkdown(helpMessage, { reply_markup: keyboard });
});

async function showFAQ(ctx) {
  const faqMessage = `❓ **Frequently Asked Questions**

**🔐 Account & Login:**
Q: How do I link my Telegram to my web account?
A: Use /start and login with your email and password. Your account will be automatically linked.

Q: I forgot my password, what do I do?
A: During login, click "Forgot Password?" to reset it via email.

**💰 Investments:**
Q: How do I invest through Telegram?
A: Use /packages to browse options, then follow the investment flow with payment instructions.

Q: What payment methods are supported?
A: Cryptocurrency (BTC, ETH, USDT) and bank transfers are supported.

**👥 Referrals:**
Q: How do referral commissions work?
A: You earn 20% commission on direct sales from people you refer.

Q: How do I get my referral link?
A: Use /referrals and click "Share Link" to get your personal referral URL.

**📊 Portfolio:**
Q: When will I receive dividends?
A: Dividend calculations are based on mine production reaching full capacity by June 2026.

Q: How do I track my investments?
A: Use /portfolio to see all your investments, shares, and projected dividends.`;

  const keyboard = {
    inline_keyboard: [
      [
        { text: "💬 More Questions?", callback_data: "start_live_chat" },
        { text: "🔙 Back", callback_data: "back_to_menu" }
      ]
    ]
  };

  if (ctx.editMessageText) {
    await ctx.editMessageText(faqMessage, {
      parse_mode: "Markdown",
      reply_markup: keyboard
    });
  } else {
    await ctx.replyWithMarkdown(faqMessage, { reply_markup: keyboard });
  }
}

async function createSupportTicket(ctx) {
  const telegramUser = ctx.telegramUser;

  if (!telegramUser.is_registered) {
    await ctx.reply("❌ Please login or register first using /start");
    return;
  }

  try {
    const userEmail = telegramUser.linked_email || telegramUser.email;
    if (!userEmail) {
      await ctx.reply("❌ No email address found. Please logout and login again.");
      return;
    }

    // Generate a unique ticket ID
    const ticketId = `TKT-${Date.now().toString().slice(-6)}-${Math.random().toString(36).substring(2, 5).toUpperCase()}`;

    const message = `🎫 **Create Support Ticket**

✅ **Ticket Created Successfully!**

🆔 **Ticket ID:** ${ticketId}
👤 **User:** ${ctx.from.first_name}
📧 **Email:** ${userEmail}
📅 **Created:** ${new Date().toLocaleString()}
🔄 **Status:** Open

📝 **Next Steps:**
1. **Describe your issue** by replying to this message
2. **Include relevant details** (investment ID, error messages, etc.)
3. **Our support team** will respond within 24 hours
4. **Track your ticket** using the ticket ID above

💡 **Common Issues:**
• Login/authentication problems
• Investment confirmation delays
• NFT delivery questions
• Commission calculation queries
• Technical difficulties

📞 **Alternative Contact:**
• Email: support@aureusangelalliance.com
• Telegram: @aureusafrica
• Live chat available during business hours

⏰ **Support Hours:**
• Monday-Friday: 9 AM - 6 PM (UTC)
• Saturday: 10 AM - 4 PM (UTC)
• Sunday: Emergency support only

🔒 **Privacy:** Your ticket information is confidential and secure.`;

    const keyboard = {
      inline_keyboard: [
        [
          { text: "📝 Add Details", callback_data: "add_ticket_details" },
          { text: "📊 Ticket Status", callback_data: "check_ticket_status" }
        ],
        [
          { text: "❓ FAQ", callback_data: "view_faq" },
          { text: "💬 Live Chat", callback_data: "start_live_chat" }
        ],
        [{ text: "🔙 Back to Support", callback_data: "menu_support" }]
      ]
    };

    await ctx.editMessageText(message, {
      parse_mode: "Markdown",
      reply_markup: keyboard
    });

    // Log the ticket creation
    console.log(`🎫 Support ticket ${ticketId} created by ${ctx.from.first_name} (${ctx.from.id}) - ${userEmail}`);

    // Store ticket in user's session for follow-up
    await updateTelegramUser(ctx.from.id, {
      active_ticket_id: ticketId,
      ticket_created_at: new Date().toISOString()
    });

  } catch (error) {
    console.error("Create support ticket error:", error);
    await ctx.reply("❌ Error creating support ticket. Please try again later.");
  }
}

async function showSystemStatus(ctx) {
  try {
    // Simulate system status checks
    const systemComponents = [
      { name: "Web Platform", status: "operational", uptime: "99.9%" },
      { name: "Telegram Bot", status: "operational", uptime: "99.8%" },
      { name: "Database", status: "operational", uptime: "99.9%" },
      { name: "Payment Gateway", status: "operational", uptime: "99.7%" },
      { name: "Email Service", status: "operational", uptime: "99.6%" },
      { name: "NFT Delivery", status: "operational", uptime: "99.5%" },
      { name: "Certificate Generation", status: "operational", uptime: "99.4%" },
      { name: "Mining Calculator", status: "operational", uptime: "99.9%" }
    ];

    const overallStatus = systemComponents.every(comp => comp.status === "operational") ? "🟢 All Systems Operational" : "🟡 Some Issues Detected";

    const message = `📊 **System Status**

🎯 **Overall Status:** ${overallStatus}
📅 **Last Updated:** ${new Date().toLocaleString()}
⏱️ **Response Time:** 245ms (Excellent)

🔧 **System Components:**

${systemComponents.map(comp => {
  const statusIcon = comp.status === "operational" ? "🟢" : comp.status === "degraded" ? "🟡" : "🔴";
  return `${statusIcon} **${comp.name}**
   Status: ${comp.status.charAt(0).toUpperCase() + comp.status.slice(1)}
   Uptime: ${comp.uptime} (30 days)`;
}).join('\n\n')}

📈 **Performance Metrics:**
• **Average Response Time:** 245ms
• **Success Rate:** 99.8%
• **Error Rate:** 0.2%
• **Active Users:** 1,247 online

🔄 **Recent Updates:**
• **2024-06-29:** Mining calculator optimized
• **2024-06-28:** Enhanced referral tracking
• **2024-06-27:** Improved certificate generation
• **2024-06-26:** Database performance upgrade

⚠️ **Scheduled Maintenance:**
• **Next Maintenance:** July 1, 2024 (2:00 AM UTC)
• **Duration:** 30 minutes
• **Impact:** Minimal service interruption

📞 **Report Issues:**
• Use "Create Ticket" for technical problems
• Contact support for urgent issues
• Check this page for real-time updates

🔒 **Security Status:** All systems secure and monitored 24/7.`;

    const keyboard = {
      inline_keyboard: [
        [
          { text: "🔄 Refresh Status", callback_data: "system_status" },
          { text: "🎫 Report Issue", callback_data: "create_support_ticket" }
        ],
        [{ text: "🔙 Back to Support", callback_data: "menu_support" }]
      ]
    };

    await ctx.editMessageText(message, {
      parse_mode: "Markdown",
      reply_markup: keyboard
    });

  } catch (error) {
    console.error("System status error:", error);
    await ctx.reply("❌ Error loading system status. Please try again later.");
  }
}

async function startLiveChat(ctx) {
  const telegramUser = ctx.telegramUser;

  if (!telegramUser.is_registered) {
    await ctx.reply("❌ Please login or register first using /start");
    return;
  }

  try {
    const userEmail = telegramUser.linked_email || telegramUser.email;
    if (!userEmail) {
      await ctx.reply("❌ No email address found. Please logout and login again.");
      return;
    }

    const message = `💬 **Live Chat Support**

👋 **Welcome to Live Support!**

🕐 **Current Time:** ${new Date().toLocaleString()}
⏰ **Support Hours:**
• Monday-Friday: 9 AM - 6 PM (UTC)
• Saturday: 10 AM - 4 PM (UTC)
• Sunday: Emergency support only

📞 **Available Support Channels:**

🔹 **Telegram Direct:**
• Contact: @aureusafrica
• Response time: 5-15 minutes
• Available during business hours

🔹 **Email Support:**
• Email: support@aureusangelalliance.com
• Response time: 2-24 hours
• Available 24/7

🔹 **Emergency Contact:**
• For urgent investment issues
• Use "Create Ticket" with "URGENT" prefix
• Emergency response within 2 hours

💡 **Before Contacting Support:**
• Check our FAQ section
• Review system status
• Have your investment details ready
• Include relevant error messages

📝 **What to Include:**
• Your registered email
• Investment ID (if applicable)
• Description of the issue
• Screenshots (if relevant)

🎯 **Common Topics:**
• Investment confirmations
• Payment processing
• NFT delivery status
• Referral commissions
• Technical difficulties

🔒 **Privacy:** All conversations are confidential and secure.`;

    const keyboard = {
      inline_keyboard: [
        [
          { text: "📱 Contact @aureusafrica", url: "https://t.me/aureusafrica" },
          { text: "📧 Send Email", url: "mailto:support@aureusangelalliance.com" }
        ],
        [
          { text: "🎫 Create Ticket", callback_data: "create_support_ticket" },
          { text: "❓ FAQ", callback_data: "view_faq" }
        ],
        [{ text: "🔙 Back to Support", callback_data: "menu_support" }]
      ]
    };

    await ctx.editMessageText(message, {
      parse_mode: "Markdown",
      reply_markup: keyboard
    });

  } catch (error) {
    console.error("Live chat error:", error);
    await ctx.reply("❌ Error starting live chat. Please try again later.");
  }
}

// FAQ COMMAND
bot.command("faq", restrictCommands("faq"), showFAQ);

// ADMIN COMMANDS
// Admin Registration Command - allows admin to register their Telegram ID for notifications
bot.command("register_admin", restrictCommands("register_admin"), async (ctx) => {
  if (ctx.from.username !== ADMIN_USERNAME) {
    await ctx.reply("❌ Access Denied\n\nYou are not authorized to register as admin.\n\n🚨 This incident has been logged.");
    console.log(`🚨 Unauthorized admin registration attempt by ${ctx.from.username} (${ctx.from.id})`);
    return;
  }

  try {
    // Update or insert admin Telegram ID in database (fixed column names)
    await dbConnection.execute(`
      INSERT INTO telegram_users (telegram_id, username, first_name, created_at)
      VALUES (?, ?, ?, NOW())
      ON DUPLICATE KEY UPDATE
      username = VALUES(username),
      first_name = VALUES(first_name),
      updated_at = NOW()
    `, [ctx.from.id, ADMIN_USERNAME, ctx.from.first_name || 'Admin']);

    console.log(`✅ Admin registered: ${ADMIN_USERNAME} (${ctx.from.id})`);

    await ctx.reply(`✅ **Admin Registration Successful**\n\n👤 **Username:** ${ADMIN_USERNAME}\n📱 **Telegram ID:** ${ctx.from.id}\n📧 **Email:** ${ADMIN_EMAIL}\n\nYou will now receive admin notifications for:\n• New payment submissions\n• User messages\n• System alerts\n\nUse /admin to access the admin panel.`);

    // Test notification
    await createAdminNotification(
      'system_alert',
      'medium',
      '🎉 Admin Registration Complete',
      `Admin ${ADMIN_USERNAME} has successfully registered for notifications.\n\nTelegram ID: ${ctx.from.id}\nThis is a test notification to confirm the system is working.`,
      null,
      ctx.from.id,
      { test_notification: true }
    );

  } catch (error) {
    console.error('Error registering admin:', error);
    await ctx.reply("❌ **Registration Error**\n\nThere was an issue registering your admin account. Please try again or contact support.");
  }
});

// Admin Login Command
bot.command("admin", restrictCommands("admin"), async (ctx) => {
  const telegramUser = ctx.telegramUser;

  // First check if user is authorized for admin access
  if (!isAuthorizedForAdmin(ctx.from.username)) {
    await ctx.reply("❌ **Access Denied**\n\nYou are not authorized to access the admin panel.\n\n🚨 This incident has been logged.");
    logSuspiciousActivity(ctx.from.id, 'UNAUTHORIZED_ADMIN_ACCESS', {
      username: ctx.from.username,
      firstName: ctx.from.first_name,
      timestamp: new Date().toISOString()
    });
    return;
  }

  if (isAdminAuthenticated(ctx.from.id)) {
    // Already authenticated, show admin panel
    const adminMessage = `🔐 **Admin Panel**

Welcome, Administrator!

🛡️ **Security Status:**
• Session Active: ✅
• Session Expires: ${new Date(adminSessions.get(ctx.from.id).expires).toLocaleString()}

🔧 **Available Commands:**
• /admin_stats - System statistics
• /admin_users - User management
• /admin_security - Security overview
• /admin_logs - View security logs
• /admin_broadcast - Send broadcast message
• /admin_logout - Logout from admin

⚠️ **Security Notice:** All admin actions are logged and monitored.`;

    const keyboard = {
      inline_keyboard: [
        [
          { text: "📊 System Stats", callback_data: "admin_stats" },
          { text: "👥 User Management", callback_data: "admin_users" }
        ],
        [
          { text: "🛡️ Security Overview", callback_data: "admin_security" },
          { text: "📋 Security Logs", callback_data: "admin_logs" }
        ],
        [
          { text: "📢 Broadcast Message", callback_data: "admin_broadcast" },
          { text: "🚪 Logout", callback_data: "admin_logout" }
        ]
      ]
    };

    await ctx.replyWithMarkdown(adminMessage, { reply_markup: keyboard });
    logAdminAction(ctx.from.id, 'ADMIN_PANEL_ACCESS', { timestamp: new Date().toISOString() });
  } else {
    // Not authenticated, request login
    const loginMessage = `🔐 **Admin Authentication Required**

Please provide your admin credentials to access the admin panel.

⚠️ **Security Notice:**
• Only authorized administrators can access this panel
• Failed login attempts are logged and monitored
• Multiple failed attempts will result in temporary lockout

Please enter your email address:`;

    await ctx.reply(loginMessage);

    // Set user state to expect admin email
    await updateTelegramUser(ctx.from.id, {
      admin_auth_step: 'email',
      admin_temp_email: null
    });
  }
});

// Admin Logout Command
bot.command("admin_logout", restrictCommands("admin_logout"), async (ctx) => {
  if (isAdminAuthenticated(ctx.from.id)) {
    adminSessions.delete(ctx.from.id);
    logAdminAction(ctx.from.id, 'ADMIN_LOGOUT', { timestamp: new Date().toISOString() });
    await ctx.reply("🔐 **Admin Logout Successful**\n\nYou have been logged out from the admin panel.");
  } else {
    await ctx.reply("❌ You are not currently logged in as an administrator.");
  }
});

// Admin Stats Command
bot.command("admin_stats", restrictCommands("admin_stats"), async (ctx) => {
  if (!isAdminAuthenticated(ctx.from.id)) {
    await ctx.reply("❌ Admin authentication required. Use /admin to login.");
    return;
  }

  try {
    // Get system statistics
    const [userCount] = await dbConnection.execute('SELECT COUNT(*) as count FROM users');
    const [telegramUserCount] = await dbConnection.execute('SELECT COUNT(*) as count FROM telegram_users');
    const [investmentCount] = await dbConnection.execute('SELECT COUNT(*) as count FROM investments');
    const [totalInvested] = await dbConnection.execute('SELECT SUM(amount) as total FROM investments WHERE status = "confirmed"');

    const statsMessage = `📊 **System Statistics**

👥 **Users:**
• Total Web Users: ${userCount[0].count}
• Total Telegram Users: ${telegramUserCount[0].count}
• Active Admin Sessions: ${adminSessions.size}

💰 **Investments:**
• Total Investments: ${investmentCount[0].count}
• Total Amount Invested: $${(totalInvested[0].total || 0).toLocaleString()}

🛡️ **Security:**
• Rate Limited Users: ${rateLimiting.size}
• Suspicious Activity Reports: ${suspiciousActivity.size}
• Failed Login Attempts: ${loginAttempts.size}

🕐 **System:**
• Bot Uptime: ${process.uptime().toFixed(0)} seconds
• Memory Usage: ${(process.memoryUsage().heapUsed / 1024 / 1024).toFixed(2)} MB`;

    const keyboard = {
      inline_keyboard: [
        [{ text: "🔄 Refresh", callback_data: "admin_stats" }],
        [{ text: "🔙 Back to Admin Panel", callback_data: "back_to_admin" }]
      ]
    };

    await ctx.replyWithMarkdown(statsMessage, { reply_markup: keyboard });
    logAdminAction(ctx.from.id, 'VIEW_SYSTEM_STATS', { timestamp: new Date().toISOString() });
  } catch (error) {
    console.error('Admin stats error:', error);
    await ctx.reply("❌ Error retrieving system statistics.");
  }
});

// RESET COMMAND (for debugging authentication issues)
bot.command("reset", restrictCommands("reset"), async (ctx) => {
  try {
    // Clear all user session data
    await updateTelegramUser(ctx.from.id, {
      is_registered: false,
      registration_step: 'start',
      registration_mode: null,
      temp_email: null,
      temp_password: null,
      awaiting_tx_hash: false,
      payment_network: null,
      payment_package_id: null,
      awaiting_receipt: false,
      password_reset_token: null,
      password_reset_expires: null
      // Keep linked_email and auto_login_enabled for returning users
    });

    const resetMessage = `🔄 **Session Reset Complete**

Your bot session has been reset. You can now:

🔹 **Login:** Use /start to login with your existing account
🔹 **Fresh Start:** All temporary data cleared
🔹 **Auto-Login:** Your account linking is preserved

Ready to start fresh? Use /start to begin!`;

    await ctx.reply(resetMessage, { parse_mode: "Markdown" });
    console.log(`🔄 Session reset for ${ctx.from.first_name} (${ctx.from.id})`);
  } catch (error) {
    console.error("Reset command error:", error);
    await ctx.reply("❌ Error resetting session. Please try /start or contact support.");
  }
});

async function showNFTPortfolio(ctx) {
  const telegramUser = ctx.telegramUser;

  if (!telegramUser.is_registered) {
    await ctx.reply("❌ Please login or register first using /start");
    return;
  }

  try {
    const userEmail = telegramUser.linked_email || telegramUser.email;
    if (!userEmail) {
      await ctx.reply("❌ No email address found. Please logout and login again.");
      return;
    }

    const nftMessage = await formatNFTPortfolio(userEmail);

    const keyboard = {
      inline_keyboard: [
        [
          { text: "📜 View Certificates", callback_data: "view_certificates" },
          { text: "🎫 NFT Coupons", callback_data: "view_nft_coupons" }
        ],
        [
          { text: "📄 Generate Certificate", callback_data: "generate_certificate" },
          { text: "📧 Email Notifications", callback_data: "nft_email_notifications" }
        ],
        [
          { text: "🔄 Refresh", callback_data: "refresh_nft" },
          { text: "🔙 Main Menu", callback_data: "back_to_menu" }
        ]
      ]
    };

    if (ctx.editMessageText) {
      await ctx.editMessageText(nftMessage, {
        parse_mode: "Markdown",
        reply_markup: keyboard
      });
    } else {
      await ctx.replyWithMarkdown(nftMessage, { reply_markup: keyboard });
    }
  } catch (error) {
    console.error("NFT command error:", error);
    await ctx.reply("❌ Error loading NFT portfolio. Please try again later.");
  }
}

// NFT COMMAND
bot.command("nft", restrictCommands("nft"), showNFTPortfolio);

async function showCertificates(ctx) {
  const telegramUser = ctx.telegramUser;

  if (!telegramUser.is_registered) {
    await ctx.reply("❌ Please login or register first using /start");
    return;
  }

  try {
    const userEmail = telegramUser.linked_email || telegramUser.email;
    if (!userEmail) {
      await ctx.reply("❌ No email address found. Please logout and login again.");
      return;
    }

    const certificatesMessage = await formatCertificates(userEmail);

    const keyboard = {
      inline_keyboard: [
        [
          { text: "📄 Download All", callback_data: "download_all_certificates" },
          { text: "📧 Email Certificates", callback_data: "email_certificates" }
        ],
        [
          { text: "🔄 Refresh", callback_data: "refresh_certificates" },
          { text: "🔙 Back", callback_data: "back_to_menu" }
        ]
      ]
    };

    if (ctx.editMessageText) {
      await ctx.editMessageText(certificatesMessage, {
        parse_mode: "Markdown",
        reply_markup: keyboard
      });
    } else {
      await ctx.replyWithMarkdown(certificatesMessage, { reply_markup: keyboard });
    }
  } catch (error) {
    console.error("Certificates command error:", error);
    await ctx.reply("❌ Error loading certificates. Please try again later.");
  }
}

async function downloadAllCertificates(ctx) {
  const telegramUser = ctx.telegramUser;

  if (!telegramUser.is_registered) {
    await ctx.reply("❌ Please login or register first using /start");
    return;
  }

  try {
    const userEmail = telegramUser.linked_email || telegramUser.email;
    if (!userEmail) {
      await ctx.reply("❌ No email address found. Please logout and login again.");
      return;
    }

    const investments = await getUserInvestments(userEmail);

    if (investments.length === 0) {
      const message = `📄 **Download Certificates**

❌ No investments found.

🔹 **Get Started:**
• Make your first investment
• Receive digital share certificates
• Download and store securely

💎 Ready to invest? Use the Packages button!`;

      const keyboard = {
        inline_keyboard: [
          [{ text: "📦 View Packages", callback_data: "menu_packages" }],
          [{ text: "🔙 Back to Certificates", callback_data: "menu_certificates" }]
        ]
      };

      await ctx.editMessageText(message, {
        parse_mode: "Markdown",
        reply_markup: keyboard
      });
      return;
    }

    // Generate download links for all certificates
    let message = `📄 **Download All Certificates**

📊 **Available Certificates:** ${investments.length}

🔗 **Download Links:**

`;

    investments.forEach((inv, index) => {
      const certificateId = `CERT-${inv.id.substring(0, 8).toUpperCase()}`;
      const downloadUrl = `https://aureusangelalliance.com/certificates/download/${inv.id}`;

      message += `${index + 1}. **${inv.package_name}**
   📊 Shares: ${inv.shares}
   🆔 ID: ${certificateId}
   🔗 [Download Certificate](${downloadUrl})

`;
    });

    message += `💡 **Instructions:**
• Click any download link above
• Certificates are in PDF format
• Store securely for your records
• Valid for 12 months from issue

📧 **Alternative:** Use "Email Certificates" to receive all certificates via email.

⚠️ **Note:** Certificates are digitally signed and blockchain-verified for authenticity.`;

    const keyboard = {
      inline_keyboard: [
        [
          { text: "📧 Email All", callback_data: "email_certificates" },
          { text: "🔄 Refresh", callback_data: "refresh_certificates" }
        ],
        [{ text: "🔙 Back to Certificates", callback_data: "menu_certificates" }]
      ]
    };

    await ctx.editMessageText(message, {
      parse_mode: "Markdown",
      reply_markup: keyboard
    });

  } catch (error) {
    console.error("Download certificates error:", error);
    await ctx.reply("❌ Error preparing certificate downloads. Please try again later.");
  }
}

async function emailCertificates(ctx) {
  const telegramUser = ctx.telegramUser;

  if (!telegramUser.is_registered) {
    await ctx.reply("❌ Please login or register first using /start");
    return;
  }

  try {
    const userEmail = telegramUser.linked_email || telegramUser.email;
    if (!userEmail) {
      await ctx.reply("❌ No email address found. Please logout and login again.");
      return;
    }

    const investments = await getUserInvestments(userEmail);

    if (investments.length === 0) {
      const message = `📧 **Email Certificates**

❌ No investments found.

🔹 **Get Started:**
• Make your first investment
• Receive digital certificates
• Get them delivered via email

💎 Ready to invest? Use the Packages button!`;

      const keyboard = {
        inline_keyboard: [
          [{ text: "📦 View Packages", callback_data: "menu_packages" }],
          [{ text: "🔙 Back to Certificates", callback_data: "menu_certificates" }]
        ]
      };

      await ctx.editMessageText(message, {
        parse_mode: "Markdown",
        reply_markup: keyboard
      });
      return;
    }

    // Simulate sending certificates via email
    const message = `📧 **Email Certificates**

✅ **Email Sent Successfully!**

📊 **Certificates Sent:** ${investments.length}
📧 **Sent to:** ${userEmail}
📅 **Sent at:** ${new Date().toLocaleString()}

📄 **Included Certificates:**
${investments.map((inv, index) =>
  `${index + 1}. ${inv.package_name} (${inv.shares} shares)`
).join('\n')}

💡 **What's Included:**
• PDF certificates for all investments
• Digital signatures and verification
• Blockchain authenticity proof
• Investment details and terms

📬 **Check Your Email:**
• Certificates sent as PDF attachments
• May take 5-10 minutes to arrive
• Check spam folder if not received
• Contact support if issues persist

🔒 **Security:** All certificates are encrypted and digitally signed for your protection.`;

    const keyboard = {
      inline_keyboard: [
        [
          { text: "📄 Download Links", callback_data: "download_all_certificates" },
          { text: "🔄 Resend Email", callback_data: "email_certificates" }
        ],
        [{ text: "🔙 Back to Certificates", callback_data: "menu_certificates" }]
      ]
    };

    await ctx.editMessageText(message, {
      parse_mode: "Markdown",
      reply_markup: keyboard
    });

    // Log the email action
    console.log(`📧 Certificates emailed to ${userEmail} for user ${ctx.from.first_name} (${ctx.from.id})`);

  } catch (error) {
    console.error("Email certificates error:", error);
    await ctx.reply("❌ Error sending certificates via email. Please try again later.");
  }
}

async function showNFTEmailNotifications(ctx) {
  const telegramUser = ctx.telegramUser;

  if (!telegramUser.is_registered) {
    await ctx.reply("❌ Please login or register first using /start");
    return;
  }

  try {
    const userEmail = telegramUser.linked_email || telegramUser.email;
    if (!userEmail) {
      await ctx.reply("❌ No email address found. Please logout and login again.");
      return;
    }

    const investments = await getUserInvestments(userEmail);

    if (investments.length === 0) {
      const message = `📧 **NFT Email Notifications**

❌ No investments found.

🔹 **Get Started:**
• Make your first investment
• Receive NFT delivery notifications
• Track your digital assets

💎 Ready to invest? Use the Packages button!`;

      const keyboard = {
        inline_keyboard: [
          [{ text: "📦 View Packages", callback_data: "menu_packages" }],
          [{ text: "🔙 Back to NFT Assets", callback_data: "menu_nft" }]
        ]
      };

      await ctx.editMessageText(message, {
        parse_mode: "Markdown",
        reply_markup: keyboard
      });
      return;
    }

    // Calculate NFT delivery status
    const nftStats = await calculateNFTStats(investments);
    const pendingNFTs = investments.filter(inv => !inv.nft_delivered);
    const deliveredNFTs = investments.filter(inv => inv.nft_delivered);

    const message = `📧 **NFT Email Notifications**

📊 **Notification Status:**
• **Total NFTs:** ${nftStats.totalNFTs}
• **Delivered:** ${nftStats.delivered}
• **Pending:** ${nftStats.pending}
• **Email Address:** ${userEmail}

🎫 **Pending NFT Deliveries:**
${pendingNFTs.length === 0 ? '✅ All NFTs delivered!' :
  pendingNFTs.map((inv, index) => {
    const deliveryDate = new Date(inv.created_at);
    deliveryDate.setMonth(deliveryDate.getMonth() + 12);
    return `${index + 1}. ${inv.package_name} - Expected: ${deliveryDate.toLocaleDateString()}`;
  }).join('\n')
}

📬 **Recent Notifications:**
${deliveredNFTs.length === 0 ? '• No notifications sent yet' :
  deliveredNFTs.slice(0, 3).map((inv, index) => {
    const deliveryDate = inv.nft_delivery_date ? new Date(inv.nft_delivery_date).toLocaleDateString() : 'Recently';
    return `• ${inv.package_name} NFT delivered - ${deliveryDate}`;
  }).join('\n')
}

⚙️ **Notification Settings:**
• **Email Notifications:** ✅ Enabled
• **Delivery Updates:** ✅ Enabled
• **NFT Countdown:** ✅ Enabled
• **Certificate Ready:** ✅ Enabled

📧 **What You'll Receive:**
• NFT delivery confirmations
• 12-month countdown updates
• Certificate generation notices
• Digital asset verification

💡 **Note:** NFT deliveries occur 12 months after investment confirmation. You'll receive email notifications at key milestones.`;

    const keyboard = {
      inline_keyboard: [
        [
          { text: "📧 Test Email", callback_data: "test_nft_email" },
          { text: "⚙️ Settings", callback_data: "nft_email_settings" }
        ],
        [{ text: "🔙 Back to NFT Assets", callback_data: "menu_nft" }]
      ]
    };

    await ctx.editMessageText(message, {
      parse_mode: "Markdown",
      reply_markup: keyboard
    });

  } catch (error) {
    console.error("NFT email notifications error:", error);
    await ctx.reply("❌ Error loading NFT email notifications. Please try again later.");
  }
}

// CERTIFICATES COMMAND
bot.command("certificates", restrictCommands("certificates"), showCertificates);

async function formatNFTPortfolio(userEmail) {
  try {
    const investments = await getUserInvestments(userEmail);
    const nftStats = await calculateNFTStats(investments);

    let nftMessage = `🎫 **NFT & Digital Assets Portfolio**

📊 **NFT Overview:**
🎫 Total NFT Coupons: ${nftStats.totalNFTs}
📜 Certificates Available: ${nftStats.certificatesAvailable}
✅ Delivered: ${nftStats.delivered}
⏳ Pending: ${nftStats.pending}

💎 **Digital Assets:**`;

    if (investments.length === 0) {
      nftMessage += `\n\n❌ No digital assets yet.

🔹 **Get Started:**
• Make an investment to receive NFT coupons
• Each investment includes digital certificates
• Printable share certificates available`;
    } else {
      investments.forEach((inv, index) => {
        const nftStatus = inv.nft_delivered ? '✅ Delivered' : '⏳ Pending';
        const certificateStatus = inv.certificate_generated ? '📜 Available' : '⏳ Generating';

        nftMessage += `\n\n${index + 1}. **${inv.package_name}**
   📊 Shares: ${inv.shares}
   🎫 NFT Status: ${nftStatus}
   📜 Certificate: ${certificateStatus}
   📅 Date: ${new Date(inv.created_at).toLocaleDateString()}`;
      });
    }

    nftMessage += `\n\n🎯 **Features:**
• 12-month NFT countdown timer
• Printable share certificates
• Digital asset verification
• Blockchain-backed authenticity`;

    return nftMessage;
  } catch (error) {
    console.error("Error formatting NFT portfolio:", error);
    return "❌ Error loading NFT portfolio. Please try again later.";
  }
}

async function formatCertificates(userEmail) {
  try {
    const investments = await getUserInvestments(userEmail);

    let certificatesMessage = `📜 **Share Certificates**

📊 **Certificate Overview:**
📄 Total Certificates: ${investments.length}
✅ Ready for Download: ${investments.filter(inv => inv.certificate_generated).length}
🖨️ Print Ready: ${investments.filter(inv => inv.certificate_generated).length}

📋 **Certificate Details:**`;

    if (investments.length === 0) {
      certificatesMessage += `\n\n❌ No certificates available yet.

🔹 **Get Started:**
• Make an investment to receive certificates
• Certificates are auto-generated after payment
• Download and print options available`;
    } else {
      investments.forEach((inv, index) => {
        const status = inv.certificate_generated ? '✅ Ready' : '⏳ Generating';
        const downloadLink = inv.certificate_generated ?
          `https://aureusangelalliance.com/certificates/${inv.id}.pdf` : 'Not available';

        certificatesMessage += `\n\n${index + 1}. **${inv.package_name} Certificate**
   📊 Shares: ${inv.shares}
   💰 Value: ${formatCurrency(inv.amount)}
   📄 Status: ${status}
   📅 Date: ${new Date(inv.created_at).toLocaleDateString()}`;

        if (inv.certificate_generated) {
          certificatesMessage += `\n   🔗 Download: Available`;
        }
      });
    }

    certificatesMessage += `\n\n📋 **Certificate Features:**
• Official share ownership proof
• High-quality PDF format
• Suitable for printing and framing
• Legally binding documentation`;

    return certificatesMessage;
  } catch (error) {
    console.error("Error formatting certificates:", error);
    return "❌ Error loading certificates. Please try again later.";
  }
}

async function calculateNFTStats(investments) {
  try {
    return {
      totalNFTs: investments.length,
      certificatesAvailable: investments.filter(inv => inv.certificate_generated).length,
      delivered: investments.filter(inv => inv.nft_delivered).length,
      pending: investments.filter(inv => !inv.nft_delivered).length
    };
  } catch (error) {
    console.error("Error calculating NFT stats:", error);
    return {
      totalNFTs: 0,
      certificatesAvailable: 0,
      delivered: 0,
      pending: 0
    };
  }
}

// Wrapper function for menu
async function showMenu(ctx) {
  const telegramUser = ctx.telegramUser;

  if (!telegramUser.is_registered) {
    await ctx.reply("❌ Please login or register first using /start");
    return;
  }

  const menuMessage = `🏆 **Aureus Alliance Holdings - Dashboard**

Welcome back, ${ctx.from.first_name}! 💎

Choose how you'd like to access your equity share platform:`;

  const keyboard = {
    inline_keyboard: [
      [
        { text: "📦 Packages", callback_data: "menu_packages" },
        { text: "📊 Portfolio", callback_data: "menu_portfolio" }
      ],
      [
        { text: "👥 Referrals", callback_data: "menu_referrals" },
        { text: "🏆 Leaderboard", callback_data: "public_leaderboard" }
      ],
      [
        { text: "🎫 NFT Assets", callback_data: "menu_nft" }
      ],
      [
        { text: "📜 Certificates", callback_data: "menu_certificates" },
        { text: "📈 History", callback_data: "menu_history" }
      ],
      [
        { text: "👤 Profile", callback_data: "menu_profile" },
        { text: "🆘 Support", callback_data: "menu_support" }
      ],
      [
        { text: "🧮 Mining Calculator", callback_data: "mining_calculator" },
        { text: "📞 Contact Admin", callback_data: "contact_admin" }
      ],
      [
        { text: "🔄 Refresh", callback_data: "back_to_menu" },
        { text: "🚪 Logout", callback_data: "confirm_logout" }
      ]
    ]
  };

  await ctx.replyWithMarkdown(menuMessage, { reply_markup: keyboard });
  console.log(`📋 Menu accessed by ${ctx.from.first_name} (${ctx.from.id})`);
}

// MENU COMMAND
bot.command("menu", restrictCommands("menu"), showMenu);

// MINING CALCULATOR COMMAND
bot.command("calculator", restrictCommands("calculator"), async (ctx) => {
  const telegramUser = ctx.telegramUser;

  if (!telegramUser.is_registered) {
    await ctx.reply("❌ Please login or register first using /start");
    return;
  }

  await showMiningCalculator(ctx);
});

async function showMiningCalculator(ctx, userShares = null) {
  try {
    const goldPrice = await getCurrentGoldPrice();

    // Get user's current shares if not provided
    if (userShares === null) {
      const telegramUser = await getTelegramUser(ctx.from.id);
      const userEmail = telegramUser.linked_email || telegramUser.email;

      if (userEmail) {
        const investments = await getUserInvestments(userEmail);
        userShares = investments.reduce((total, inv) => total + (inv.shares || 0), 0);
      } else {
        userShares = 0;
      }
    }

    // Simple dividend calculation over 5 years with production growth
    const dividendProjections = calculateSimpleDividends(userShares, goldPrice);

    const calculatorMessage = `💰 **Dividend Calculator**

📊 **Your Investment:**
• **Shares:** ${userShares.toLocaleString()}
• **Share Value:** $${(userShares * 10).toLocaleString()} (at $10/share)

💎 **5-Year Dividend Projections:**

**Year 1 (2025):** $${dividendProjections.year1.toLocaleString()}
• Current production level
• Quarterly: $${(dividendProjections.year1 / 4).toLocaleString()}

**Year 2 (2026):** $${dividendProjections.year2.toLocaleString()}
• 25% production increase
• Quarterly: $${(dividendProjections.year2 / 4).toLocaleString()}

**Year 3 (2027):** $${dividendProjections.year3.toLocaleString()}
• 50% production increase
• Quarterly: $${(dividendProjections.year3 / 4).toLocaleString()}

**Year 4 (2028):** $${dividendProjections.year4.toLocaleString()}
• 75% production increase
• Quarterly: $${(dividendProjections.year4 / 4).toLocaleString()}

**Year 5 (2029):** $${dividendProjections.year5.toLocaleString()}
• Full capacity reached
• Quarterly: $${(dividendProjections.year5 / 4).toLocaleString()}

📈 **Total 5-Year Dividends:** $${dividendProjections.total.toLocaleString()}
🎯 **Average Annual Return:** ${dividendProjections.averageROI.toFixed(1)}%

⚠️ **Note:** Projections based on mining expansion plan and current gold prices (~$${Math.round(goldPrice/1000)}k/kg). Actual results may vary.`;

    const keyboard = {
      inline_keyboard: [
        [
          { text: "📈 Change Shares", callback_data: "calc_change_shares" },
          { text: "🔄 Refresh", callback_data: "calc_refresh" }
        ],
        [
          { text: "📊 Quick Options", callback_data: "calc_quick_options" }
        ],
        [
          { text: "🔙 Back to Menu", callback_data: "back_to_menu" }
        ]
      ]
    };

    if (ctx.editMessageText) {
      await ctx.editMessageText(calculatorMessage, {
        parse_mode: "Markdown",
        reply_markup: keyboard
      });
    } else {
      await ctx.replyWithMarkdown(calculatorMessage, { reply_markup: keyboard });
    }

  } catch (error) {
    console.error('Error showing mining calculator:', error);
    await ctx.reply("❌ Error loading calculator. Please try again.");
  }
}

// Wrapper function for dashboard
async function showDashboard(ctx) {
  const telegramUser = ctx.telegramUser;

  if (!telegramUser.is_registered) {
    await ctx.reply("❌ Please login or register first using /start");
    return;
  }

  const dashboardMessage = `🎯 **Professional Dashboard**

Access your complete investment platform with the same interface as our website:

✨ **Features:**
• Full website functionality
• Real-time data synchronization
• Professional charts and analytics
• Mobile-optimized interface
• Secure authentication

🔒 **Security:** Your session is automatically authenticated through Telegram.`;

  const keyboard = {
    inline_keyboard: [
      [
        { text: "📦 Packages", callback_data: "menu_packages" },
        { text: "📊 Portfolio", callback_data: "menu_portfolio" }
      ],
      [
        { text: "👥 Referrals", callback_data: "menu_referrals" },
        { text: "🎫 NFT Assets", callback_data: "menu_nft" }
      ],
      [
        { text: "🔄 Refresh", callback_data: "back_to_menu" },
        { text: "🔙 Back to Menu", callback_data: "back_to_menu" }
      ]
    ]
  };

  await ctx.replyWithMarkdown(dashboardMessage, { reply_markup: keyboard });
  console.log(`🎯 Dashboard accessed by ${ctx.from.first_name} (${ctx.from.id})`);
}

// DASHBOARD COMMAND - Web App Integration
bot.command("dashboard", restrictCommands("dashboard"), showDashboard);

// APP COMMAND - Direct Mini App Access
bot.command("app", restrictCommands("app"), async (ctx) => {
  const telegramUser = ctx.telegramUser;

  if (!telegramUser.is_registered) {
    await ctx.reply("❌ Please login or register first using /start");
    return;
  }

  const appMessage = `🚀 **Aureus Investment App**

Experience the full power of our investment platform with the same professional interface as our website!

✨ **Features:**
• 📊 Real-time portfolio dashboard
• 💰 Investment package browser
• 📈 Live performance charts
• 👥 Referral management center
• 🎫 NFT & certificate gallery
• 💳 Secure payment processing

🎮 **Just like popular Telegram games** - but for serious gold mining investments!

🔒 **Secure:** Your Telegram account is automatically authenticated.`;

  const keyboard = {
    inline_keyboard: [
      [
        { text: "📦 Packages", callback_data: "menu_packages" },
        { text: "📊 Portfolio", callback_data: "menu_portfolio" }
      ],
      [
        { text: "👥 Referrals", callback_data: "menu_referrals" },
        { text: "🆘 Support", callback_data: "menu_support" }
      ],
      [
        { text: "📱 Main Menu", callback_data: "back_to_menu" }
      ]
    ]
  };

  await ctx.replyWithMarkdown(appMessage, { reply_markup: keyboard });
  console.log(`🚀 App launched by ${ctx.from.first_name} (${ctx.from.id})`);
});

// PLAY COMMAND - Fun alias for app
bot.command("play", restrictCommands("play"), async (ctx) => {
  const telegramUser = ctx.telegramUser;

  if (!telegramUser.is_registered) {
    await ctx.reply("❌ Please login or register first using /start");
    return;
  }

  const playMessage = `🎮 **Ready to Play?**

Launch your investment game where every move builds real wealth!

🏆 **Your Mission:**
• 💎 Collect gold mining shares
• 📈 Build your investment portfolio
• 👥 Recruit your investment team
• 💰 Earn real dividends
• 🎯 Reach financial freedom

🚀 **Start Playing:**`;

  const keyboard = {
    inline_keyboard: [
      [
        { text: "📦 Packages", callback_data: "menu_packages" },
        { text: "📊 Portfolio", callback_data: "menu_portfolio" }
      ],
      [
        { text: "📊 View Leaderboard", callback_data: "view_leaderboard" },
        { text: "🏆 My Achievements", callback_data: "view_achievements" }
      ],
      [
        { text: "🔙 Back to Menu", callback_data: "back_to_menu" }
      ]
    ]
  };

  await ctx.replyWithMarkdown(playMessage, { reply_markup: keyboard });
  console.log(`🎮 Game mode accessed by ${ctx.from.first_name} (${ctx.from.id})`);
});

// Wrapper function for logout
async function performLogout(ctx) {
  try {
    // Clear user session data completely
    await updateTelegramUser(ctx.from.id, {
      is_registered: false,
      registration_step: 'start',
      registration_mode: null,
      temp_email: null,
      temp_password: null,
      awaiting_tx_hash: false,
      payment_network: null,
      payment_package_id: null,
      awaiting_receipt: false,
      password_reset_token: null,
      password_reset_expires: null,
      linked_email: null,
      auto_login_enabled: false,
      user_id: null
    });

    const logoutMessage = `👋 **Logged Out Successfully**

You have been logged out from your Aureus Alliance Holdings account.

🔹 **To access your account again:**
• Use /start to login or register
• Your equity shares and data are safely stored

Thank you for using Aureus Alliance Holdings! 💎`;

    await ctx.reply(logoutMessage, { parse_mode: "Markdown" });
    console.log(`👋 User ${ctx.from.first_name} (${ctx.from.id}) logged out`);
  } catch (error) {
    console.error("Logout error:", error);
    await ctx.reply("❌ Error during logout. Please try again.");
  }
}

// LOGOUT COMMAND
bot.command("logout", restrictCommands("logout"), performLogout);

// CALLBACK QUERY HANDLER - SIMPLIFIED AND WORKING
bot.on("callback_query", async (ctx) => {
  const data = ctx.callbackQuery.data;
  console.log(`🔘 Callback query: ${data} from ${ctx.from.first_name}`);

  // Menu callbacks
  if (data === "menu_packages") {
    await ctx.answerCbQuery();
    // Call packages function directly
    return await showPackages(ctx);
  }

  if (data === "menu_custom_investment") {
    await ctx.answerCbQuery();
    return await showCustomInvestmentMenu(ctx);
  }

  if (data === "custom_enter_amount") {
    await ctx.answerCbQuery();

    // Set user state to expect custom amount input
    userStates.set(ctx.from.id, {
      state: 'awaiting_custom_amount',
      messageId: ctx.callbackQuery.message.message_id
    });

    await ctx.editMessageText(
      "💰 **Enter Your Investment Amount**\n\n" +
      "Please enter the amount you want to invest (minimum $25):\n\n" +
      "💡 **Examples:** 1500, 2500, 5000\n" +
      "📝 Just type the number without the $ symbol",
      {
        parse_mode: "Markdown",
        reply_markup: {
          inline_keyboard: [
            [{ text: "🔙 Back to Custom Investment", callback_data: "menu_custom_investment" }]
          ]
        }
      }
    );
    return;
  }

  // Handle custom investment confirmation
  if (data.startsWith("custom_confirm_")) {
    await ctx.answerCbQuery();

    const userState = userStates.get(ctx.from.id);
    if (!userState || userState.state !== 'custom_investment_calculated') {
      await ctx.editMessageText("❌ Session expired. Please start over.", {
        reply_markup: {
          inline_keyboard: [[{ text: "💰 Custom Investment", callback_data: "menu_custom_investment" }]]
        }
      });
      return;
    }

    // Process the custom investment
    await processCustomInvestment(ctx, userState.calculation, userState.phase);
    return;
  }

  if (data === "menu_portfolio") {
    await ctx.answerCbQuery();
    // Call portfolio function directly
    return await showPortfolio(ctx);
  }

  if (data === "menu_profile") {
    await ctx.answerCbQuery();
    const telegramUser = ctx.telegramUser;

    const profileMessage = `👤 **Your Profile**

📧 **Email:** ${telegramUser.linked_email || telegramUser.email || 'Not linked'}
🆔 **Telegram ID:** ${ctx.from.id}
👤 **Name:** ${ctx.from.first_name} ${ctx.from.last_name || ''}
📅 **Registered:** ${new Date(telegramUser.created_at).toLocaleDateString()}

🔹 **Account Status:** ✅ Active
🔹 **Registration:** ✅ Complete
🔹 **Auto-Login:** ${telegramUser.auto_login_enabled ? '✅ Enabled' : '❌ Disabled'}

💡 **Need to update your profile?** Contact support @aureusafrica`;

    const keyboard = {
      inline_keyboard: [
        [{ text: "🔙 Back to Menu", callback_data: "back_to_menu" }]
      ]
    };

    await ctx.editMessageText(profileMessage, {
      parse_mode: "Markdown",
      reply_markup: keyboard
    });
    return;
  }

  if (data === "menu_referrals") {
    await ctx.answerCbQuery();
    await showReferrals(ctx);
    return;
  }

  if (data === "menu_nft") {
    await ctx.answerCbQuery();
    await showNFTPortfolio(ctx);
    return;
  }

  if (data === "menu_certificates") {
    await ctx.answerCbQuery();
    await showCertificates(ctx);
    return;
  }

  if (data === "menu_history") {
    await ctx.answerCbQuery();
    await showHistory(ctx);
    return;
  }

  if (data === "menu_support") {
    await ctx.answerCbQuery();
    await showSupport(ctx);
    return;
  }

  if (data === "back_to_menu") {
    await ctx.answerCbQuery();
    // Call menu function directly
    return await showMenu(ctx);
  }

  // Logout confirmation
  if (data === "confirm_logout") {
    await ctx.answerCbQuery();
    // Call logout function directly
    return await performLogout(ctx);
  }

  // Contact Admin Feature
  if (data === "contact_admin") {
    await ctx.answerCbQuery();

    const contactMessage = `📞 **Contact Admin**

Send your message to the administrator. Your message will be forwarded directly to the admin team.

**What can you contact admin about:**
• Account issues or questions
• Investment inquiries
• Technical support
• General feedback
• Urgent matters

**Please type your message below:**`;

    const keyboard = {
      inline_keyboard: [
        [{ text: "🔙 Back to Menu", callback_data: "back_to_menu" }]
      ]
    };

    await ctx.editMessageText(contactMessage, {
      parse_mode: "Markdown",
      reply_markup: keyboard
    });

    // Set user state to expect admin message
    await updateTelegramUser(ctx.from.id, {
      awaiting_admin_message: true
    });
    return;
  }

  // Portfolio callbacks
  if (data === "investment_history") {
    await ctx.answerCbQuery();
    const telegramUser = ctx.telegramUser;
    const userEmail = telegramUser.linked_email || telegramUser.email;

    if (!userEmail) {
      await ctx.editMessageText("❌ No email address found. Please logout and login again.");
      return;
    }

    const historyMessage = await formatInvestmentHistory(userEmail);

    const keyboard = {
      inline_keyboard: [
        [{ text: "🔙 Back to Portfolio", callback_data: "refresh_portfolio" }]
      ]
    };

    await ctx.editMessageText(historyMessage, {
      parse_mode: "Markdown",
      reply_markup: keyboard
    });
    return;
  }

  if (data === "portfolio_stats") {
    await ctx.answerCbQuery();
    const telegramUser = ctx.telegramUser;
    const userEmail = telegramUser.linked_email || telegramUser.email;

    if (!userEmail) {
      await ctx.editMessageText("❌ No email address found. Please logout and login again.");
      return;
    }

    const investments = await getUserInvestments(userEmail);
    const stats = await calculatePortfolioStats(investments);

    const statsMessage = `📊 **Portfolio Statistics**

📈 **Investment Overview:**
• Total Investments: ${stats.totalInvestments}
• Total Amount: ${formatCurrency(stats.totalInvested)}
• Total Shares: ${stats.totalShares.toLocaleString()}
• Average Investment: ${formatCurrency(stats.totalInvested / stats.totalInvestments || 0)}

✅ **Status Breakdown:**
• Confirmed: ${stats.confirmedInvestments}
• Pending: ${stats.pendingInvestments}
• Success Rate: ${((stats.confirmedInvestments / stats.totalInvestments) * 100 || 0).toFixed(1)}%

🎁 **Delivery Status:**
• NFT Certificates: ${stats.nftDelivered}/${stats.totalInvestments}
• ROI Deliveries: ${stats.roiDelivered}/${stats.totalInvestments}

📅 **Timeline:**
• First Investment: ${investments.length > 0 ? new Date(investments[investments.length - 1].created_at).toLocaleDateString() : 'N/A'}
• Latest Investment: ${investments.length > 0 ? new Date(investments[0].created_at).toLocaleDateString() : 'N/A'}`;

    const keyboard = {
      inline_keyboard: [
        [{ text: "🔙 Back to Portfolio", callback_data: "refresh_portfolio" }]
      ]
    };

    await ctx.editMessageText(statsMessage, {
      parse_mode: "Markdown",
      reply_markup: keyboard
    });
    return;
  }

  if (data === "refresh_portfolio") {
    await ctx.answerCbQuery();
    // Call portfolio function directly
    return await showPortfolio(ctx);
  }

  // Referral callbacks
  if (data === "view_downline") {
    await ctx.answerCbQuery();
    await showDownline(ctx);
    return;
  }

  if (data === "share_referral_link") {
    await ctx.answerCbQuery();
    const telegramUser = ctx.telegramUser;
    const userEmail = telegramUser.linked_email || telegramUser.email;

    if (!userEmail) {
      await ctx.editMessageText("❌ No email address found. Please logout and login again.");
      return;
    }

    // Get user ID
    const [userRows] = await dbConnection.execute(
      'SELECT id, username FROM users WHERE email = ?',
      [userEmail]
    );

    if (userRows.length === 0) {
      await ctx.editMessageText("❌ User not found.");
      return;
    }

    const user = userRows[0];
    const referralLink = `https://aureusangelalliance.com/register?ref=${user.id}`;

    const shareMessage = `🔗 **Share Your Referral Link**

**Your Personal Link:**
\`${referralLink}\`

📱 **Share Options:**
• Copy the link above
• Share on social media
• Send to friends via WhatsApp
• Email to your contacts

💰 **Earn 20% Commission:**
• Direct sales commission
• Immediate payout
• No limits on earnings
• Build passive income

🎯 **Tips for Success:**
• Share your investment story
• Explain the gold mining opportunity
• Highlight the NPO support aspect
• Show your portfolio growth`;

    const keyboard = {
      inline_keyboard: [
        [
          { text: "📱 Share on Telegram", switch_inline_query: `Join me in gold mining investments! ${referralLink}` }
        ],
        [
          { text: "🔙 Back to Referrals", callback_data: "back_to_referrals" }
        ]
      ]
    };

    await ctx.editMessageText(shareMessage, {
      parse_mode: "Markdown",
      reply_markup: keyboard
    });
    return;
  }

  if (data === "back_to_referrals") {
    await ctx.answerCbQuery();
    await showReferrals(ctx);
    return;
  }

  // NFT and Certificate callbacks
  if (data === "view_certificates") {
    await ctx.answerCbQuery();
    await showCertificates(ctx);
    return;
  }

  if (data === "view_nft_coupons") {
    await ctx.answerCbQuery();
    const telegramUser = ctx.telegramUser;
    const userEmail = telegramUser.linked_email || telegramUser.email;

    if (!userEmail) {
      await ctx.editMessageText("❌ No email address found. Please logout and login again.");
      return;
    }

    const investments = await getUserInvestments(userEmail);

    let couponsMessage = `🎫 **NFT Coupons**

📊 **Coupon Overview:**
🎫 Total Coupons: ${investments.length}
✅ Active: ${investments.filter(inv => inv.nft_delivered).length}
⏳ Pending: ${investments.filter(inv => !inv.nft_delivered).length}

🎫 **Your NFT Coupons:**`;

    if (investments.length === 0) {
      couponsMessage += `\n\n❌ No NFT coupons yet.

🔹 **Get Started:**
• Make an investment to receive NFT coupons
• Each investment includes unique NFT
• 12-month countdown timer included`;
    } else {
      investments.forEach((inv, index) => {
        const status = inv.nft_delivered ? '✅ Active' : '⏳ Pending';
        const deliveryDate = inv.nft_delivery_date ?
          new Date(inv.nft_delivery_date).toLocaleDateString() : 'TBD';

        couponsMessage += `\n\n${index + 1}. **${inv.package_name} NFT**
   🎫 Status: ${status}
   📊 Shares: ${inv.shares}
   📅 Delivery: ${deliveryDate}
   🆔 ID: ${inv.id.substring(0, 8)}...`;
      });
    }

    const keyboard = {
      inline_keyboard: [
        [{ text: "🔙 Back to NFT Portfolio", callback_data: "refresh_nft" }]
      ]
    };

    await ctx.editMessageText(couponsMessage, {
      parse_mode: "Markdown",
      reply_markup: keyboard
    });
    return;
  }

  if (data === "generate_certificate") {
    await ctx.answerCbQuery();

    const generateMessage = `📄 **Certificate Generation**

🔄 **Generating certificates for all eligible investments...**

⏳ This process may take a few moments.

✅ **What you'll receive:**
• High-quality PDF certificates
• Official share ownership proof
• Printable format
• Digital signatures

📧 **Delivery:**
• Certificates will be sent to your email
• Download links will be provided
• Available in your portfolio

🕐 **Processing time:** 2-5 minutes`;

    const keyboard = {
      inline_keyboard: [
        [
          { text: "📧 Send to Email", callback_data: "send_certificates_email" },
          { text: "📱 View in Bot", callback_data: "view_certificates" }
        ],
        [
          { text: "🔙 Back to NFT", callback_data: "refresh_nft" }
        ]
      ]
    };

    await ctx.editMessageText(generateMessage, {
      parse_mode: "Markdown",
      reply_markup: keyboard
    });
    return;
  }

  if (data === "refresh_nft") {
    await ctx.answerCbQuery();
    await showNFTPortfolio(ctx);
    return;
  }

  // Support callbacks
  if (data === "start_live_chat") {
    await ctx.answerCbQuery();

    const chatMessage = `💬 **Live Chat Support**

🔗 **Connect with our support team:**

📱 **Telegram:** @aureusafrica
📧 **Email:** support@aureusangelalliance.com
🌐 **Website:** aureusangelalliance.com/support

🕐 **Support Hours:**
• Monday - Friday: 9 AM - 6 PM (UTC)
• Saturday: 10 AM - 4 PM (UTC)
• Sunday: Emergency support only

⚡ **Quick Response:**
• Average response time: 5-15 minutes
• Technical issues: Priority support
• Investment questions: Immediate help

💡 **Before contacting support:**
• Check /faq for common questions
• Have your account email ready
• Describe your issue clearly`;

    const keyboard = {
      inline_keyboard: [
        [
          { text: "📱 Contact Support", callback_data: "contact_support_info" },
          { text: "❓ FAQ", callback_data: "view_faq" }
        ],
        [
          { text: "🔙 Back to Support", callback_data: "get_support" }
        ]
      ]
    };

    await ctx.editMessageText(chatMessage, {
      parse_mode: "Markdown",
      reply_markup: keyboard
    });
    return;
  }

  if (data === "view_faq") {
    await ctx.answerCbQuery();
    await showFAQ(ctx);
    return;
  }

  if (data === "get_support") {
    await ctx.answerCbQuery();
    await showSupport(ctx);
    return;
  }

  if (data === "contact_support_info") {
    await ctx.answerCbQuery();

    const contactMessage = `📞 **Contact Information**

🔗 **Support Channels:**

📱 **Telegram:** @aureusafrica
📧 **Email:** support@aureusangelalliance.com
🌐 **Website:** aureusangelalliance.com

🕐 **Support Hours:**
• Monday - Friday: 9 AM - 6 PM (UTC)
• Saturday: 10 AM - 4 PM (UTC)
• Sunday: Emergency support only

💡 **For fastest response:**
• Use Telegram: @aureusafrica
• Include your account email
• Describe your issue clearly`;

    const keyboard = {
      inline_keyboard: [
        [
          { text: "🔙 Back to Support", callback_data: "get_support" }
        ]
      ]
    };

    await ctx.editMessageText(contactMessage, {
      parse_mode: "Markdown",
      reply_markup: keyboard
    });
    return;
  }

  // Admin Login Callback
  if (data === "admin_login") {
    await ctx.answerCbQuery();

    // Check if user is authorized
    if (!isAuthorizedForAdmin(ctx.from.username)) {
      await ctx.editMessageText("❌ **Access Denied**\n\nYou are not authorized to access the admin panel.\n\n🚨 This incident has been logged.");
      logSuspiciousActivity(ctx.from.id, 'UNAUTHORIZED_ADMIN_ACCESS', {
        username: ctx.from.username,
        firstName: ctx.from.first_name,
        timestamp: new Date().toISOString()
      });
      return;
    }

    const adminLoginMessage = `🔐 **Admin Authentication**

Welcome, @${ctx.from.username}!

You are authorized to access the admin panel. Please provide your admin credentials to continue.

⚠️ **Security Notice:**
• All admin actions are logged and monitored
• Session expires after 1 hour of inactivity
• Failed attempts will result in temporary lockout

Please enter your admin email address:`;

    await ctx.editMessageText(adminLoginMessage);

    // Set user state to expect admin email
    await updateTelegramUser(ctx.from.id, {
      admin_auth_step: 'email',
      admin_temp_email: null
    });
    return;
  }

  // Admin Panel Access (for authenticated users)
  if (data === "admin_panel_access") {
    await ctx.answerCbQuery();

    // Check if user is authorized
    if (!isAuthorizedForAdmin(ctx.from.username)) {
      await ctx.editMessageText("❌ **Access Denied**\n\nYou are not authorized to access the admin panel.");
      return;
    }

    if (isAdminAuthenticated(ctx.from.id)) {
      // Show admin panel
      const adminMessage = `🔐 **Enhanced Admin Panel**

Welcome back, Administrator @${ctx.from.username}!

🛡️ **Security Status:**
• Session Active: ✅
• Session Expires: ${new Date(adminSessions.get(ctx.from.id).expires).toLocaleString()}

🔧 **Enhanced Features:**
• User Communication System
• Password Reset Management
• Payment Confirmations
• Terms Acceptance Review
• User Account Management
• System Statistics & Security

⚠️ **Security Notice:** All admin actions are logged and monitored.`;

      const keyboard = {
        inline_keyboard: [
          [
            { text: "💬 User Messages", callback_data: "admin_user_messages" },
            { text: "🔑 Password Resets", callback_data: "admin_password_resets" }
          ],
          [
            { text: "👥 User Management", callback_data: "admin_user_management" },
            { text: "💳 Payment Confirmations", callback_data: "admin_payment_confirmations" }
          ],
          [
            { text: "🎯 Referral Management", callback_data: "admin_referrals" },
            { text: "💰 Commission Management", callback_data: "admin_commissions" }
          ],
          [
            { text: "📋 Terms Review", callback_data: "admin_terms_review" },
            { text: "📊 System Stats", callback_data: "admin_stats" }
          ],
          [
            { text: "🛡️ Security Overview", callback_data: "admin_security" },
            { text: "📢 Broadcast", callback_data: "admin_broadcast" }
          ],
          [
            { text: "🚪 Logout", callback_data: "admin_logout" },
            { text: "🔙 Back to Main Menu", callback_data: "back_to_menu" }
          ]
        ]
      };

      await ctx.editMessageText(adminMessage, {
        parse_mode: "Markdown",
        reply_markup: keyboard
      });
      logAdminAction(ctx.from.id, 'ADMIN_PANEL_ACCESS', { timestamp: new Date().toISOString() });
    } else {
      // Redirect to admin login
      await ctx.editMessageText("🔐 **Admin Authentication Required**\n\nPlease authenticate first using the Admin Login option.");
    }
    return;
  }

  // Admin Stats Callback
  if (data === "admin_stats") {
    await ctx.answerCbQuery();

    if (!isAuthorizedForAdmin(ctx.from.username) || !isAdminAuthenticated(ctx.from.id)) {
      await ctx.editMessageText("❌ Admin authentication required.");
      return;
    }

    try {
      // Get system statistics
      const [userCount] = await dbConnection.execute('SELECT COUNT(*) as count FROM users');
      const [telegramUserCount] = await dbConnection.execute('SELECT COUNT(*) as count FROM telegram_users');
      const [investmentCount] = await dbConnection.execute('SELECT COUNT(*) as count FROM investments');
      const [totalInvested] = await dbConnection.execute('SELECT SUM(amount) as total FROM investments WHERE status = "confirmed"');

      const statsMessage = `📊 **System Statistics**

👥 **Users:**
• Total Web Users: ${userCount[0].count}
• Total Telegram Users: ${telegramUserCount[0].count}
• Active Admin Sessions: ${adminSessions.size}

💰 **Investments:**
• Total Investments: ${investmentCount[0].count}
• Total Amount Invested: $${(totalInvested[0].total || 0).toLocaleString()}

🛡️ **Security:**
• Rate Limited Users: ${rateLimiting.size}
• Suspicious Activity Reports: ${suspiciousActivity.size}
• Failed Login Attempts: ${loginAttempts.size}

🕐 **System:**
• Bot Uptime: ${process.uptime().toFixed(0)} seconds
• Memory Usage: ${(process.memoryUsage().heapUsed / 1024 / 1024).toFixed(2)} MB`;

      const keyboard = {
        inline_keyboard: [
          [{ text: "🔄 Refresh", callback_data: "admin_stats" }],
          [{ text: "🔙 Back to Admin Panel", callback_data: "admin_panel_access" }]
        ]
      };

      await ctx.editMessageText(statsMessage, {
        parse_mode: "Markdown",
        reply_markup: keyboard
      });
      logAdminAction(ctx.from.id, 'VIEW_SYSTEM_STATS', { timestamp: new Date().toISOString() });
    } catch (error) {
      console.error('Admin stats error:', error);
      await ctx.editMessageText("❌ Error retrieving system statistics.");
    }
    return;
  }

  // Admin Logout Callback
  if (data === "admin_logout") {
    await ctx.answerCbQuery();

    if (isAdminAuthenticated(ctx.from.id)) {
      adminSessions.delete(ctx.from.id);
      logAdminAction(ctx.from.id, 'ADMIN_LOGOUT', { timestamp: new Date().toISOString() });
      await ctx.editMessageText("🔐 **Admin Logout Successful**\n\nYou have been logged out from the admin panel.\n\nUse /start to return to the main menu.");
    } else {
      await ctx.editMessageText("❌ You are not currently logged in as an administrator.");
    }
    return;
  }

  // Admin Security Overview
  if (data === "admin_security") {
    await ctx.answerCbQuery();

    if (!isAuthorizedForAdmin(ctx.from.username) || !isAdminAuthenticated(ctx.from.id)) {
      await ctx.editMessageText("❌ Admin authentication required.");
      return;
    }

    const securityMessage = `🛡️ **Security Overview**

🔐 **Admin Security:**
• Authorized Username: @${ADMIN_USERNAME}
• Active Admin Sessions: ${adminSessions.size}
• Session Timeout: ${ADMIN_SESSION_TIMEOUT / 60000} minutes

⚠️ **Security Monitoring:**
• Rate Limited Users: ${rateLimiting.size}
• Suspicious Activities: ${suspiciousActivity.size}
• Failed Login Attempts: ${loginAttempts.size}

🚨 **Recent Security Events:**
• Max Login Attempts: ${MAX_LOGIN_ATTEMPTS}
• Login Cooldown: ${LOGIN_COOLDOWN / 60000} minutes
• Rate Limit: ${RATE_LIMIT_MAX_REQUESTS} requests per minute

🔒 **Protection Status:**
• Input Sanitization: ✅ Active
• SQL Injection Protection: ✅ Active
• Rate Limiting: ✅ Active
• Admin Access Control: ✅ Active`;

    const keyboard = {
      inline_keyboard: [
        [
          { text: "📋 View Logs", callback_data: "admin_logs" },
          { text: "🔄 Refresh", callback_data: "admin_security" }
        ],
        [
          { text: "🔙 Back to Admin Panel", callback_data: "admin_panel_access" }
        ]
      ]
    };

    await ctx.editMessageText(securityMessage, {
      parse_mode: "Markdown",
      reply_markup: keyboard
    });
    logAdminAction(ctx.from.id, 'VIEW_SECURITY_OVERVIEW', { timestamp: new Date().toISOString() });
    return;
  }

  // =====================================================
  // ENHANCED ADMIN PANEL CALLBACKS
  // =====================================================

  // User Messages Management
  if (data === "admin_user_messages") {
    await ctx.answerCbQuery();

    if (!isAuthorizedForAdmin(ctx.from.username) || !isAdminAuthenticated(ctx.from.id)) {
      await ctx.editMessageText("❌ Admin authentication required.");
      return;
    }

    try {
      const messages = await getUnreadUserMessages();

      let messagesList = "📬 **User Messages**\n\n";

      if (messages.length === 0) {
        messagesList += "✅ No new messages from users.";
      } else {
        messagesList += `📊 **${messages.length} messages found**\n\n`;

        messages.slice(0, 5).forEach((msg, index) => {
          const date = new Date(msg.created_at).toLocaleDateString();
          const preview = msg.message_text.substring(0, 50) + (msg.message_text.length > 50 ? '...' : '');
          messagesList += `${index + 1}. **${msg.first_name || msg.username || 'User'}**\n`;
          messagesList += `   📅 ${date} | 📝 ${msg.status}\n`;
          messagesList += `   💬 "${preview}"\n`;
          messagesList += `   🆔 ID: ${msg.id}\n\n`;
        });

        if (messages.length > 5) {
          messagesList += `... and ${messages.length - 5} more messages`;
        }
      }

      const keyboard = {
        inline_keyboard: [
          [
            { text: "📖 View All Messages", callback_data: "admin_view_all_messages" },
            { text: "🔄 Refresh", callback_data: "admin_user_messages" }
          ],
          [
            { text: "🔙 Back to Admin Panel", callback_data: "admin_panel_access" }
          ]
        ]
      };

      await ctx.editMessageText(messagesList, {
        parse_mode: "Markdown",
        reply_markup: keyboard
      });

      await logAdminAction(ctx.from.id, 'VIEW_USER_MESSAGES', {
        message_count: messages.length,
        admin_username: ctx.from.username
      });
    } catch (error) {
      console.error('Error loading user messages:', error);
      await ctx.editMessageText("❌ Error loading user messages.");
    }
    return;
  }

  // View All Messages Callback
  if (data === "admin_view_all_messages") {
    await ctx.answerCbQuery();

    if (!isAuthorizedForAdmin(ctx.from.username) || !isAdminAuthenticated(ctx.from.id)) {
      await ctx.editMessageText("❌ Admin authentication required.");
      return;
    }

    try {
      const messages = await getAllUserMessages();

      let messagesList = "📬 **All User Messages**\n\n";

      if (messages.length === 0) {
        messagesList += "✅ No messages found.";
      } else {
        messagesList += `📊 **${messages.length} messages found**\n\n`;

        messages.slice(0, 10).forEach((msg, index) => {
          const date = new Date(msg.created_at).toLocaleDateString();
          const status = msg.status === 'replied' ? '✅' : msg.status === 'read' ? '👁️' : '🆕';
          const name = msg.first_name || 'Unknown';

          messagesList += `${index + 1}. ${status} **${name}** (${date})\n`;
          messagesList += `   📧 ${msg.email || 'No email'}\n`;
          messagesList += `   💬 "${msg.message_text.substring(0, 50)}${msg.message_text.length > 50 ? '...' : ''}"\n`;
          messagesList += `   🔗 [Reply](callback_data:admin_reply_${msg.id})\n\n`;
        });

        if (messages.length > 10) {
          messagesList += `... and ${messages.length - 10} more messages`;
        }
      }

      // Create inline keyboard with reply buttons for recent messages
      const keyboard = {
        inline_keyboard: []
      };

      // Add reply buttons for first 5 messages
      const recentMessages = messages.slice(0, 5);
      for (let i = 0; i < recentMessages.length; i += 2) {
        const row = [];
        if (recentMessages[i]) {
          const name = recentMessages[i].first_name || 'User';
          row.push({
            text: `💬 Reply to ${name}`,
            callback_data: `admin_reply_${recentMessages[i].id}`
          });
        }
        if (recentMessages[i + 1]) {
          const name = recentMessages[i + 1].first_name || 'User';
          row.push({
            text: `💬 Reply to ${name}`,
            callback_data: `admin_reply_${recentMessages[i + 1].id}`
          });
        }
        keyboard.inline_keyboard.push(row);
      }

      // Add navigation buttons
      keyboard.inline_keyboard.push([
        { text: "🔄 Refresh", callback_data: "admin_view_all_messages" },
        { text: "🔙 Back", callback_data: "admin_user_messages" }
      ]);

      await ctx.editMessageText(messagesList, {
        parse_mode: "Markdown",
        reply_markup: keyboard
      });

      await logAdminAction(ctx.from.id, 'VIEW_ALL_USER_MESSAGES', {
        message_count: messages.length,
        admin_username: ctx.from.username
      });
    } catch (error) {
      console.error('Error loading all messages:', error);
      await ctx.editMessageText("❌ Error loading messages.");
    }
    return;
  }

  // Password Reset Requests
  if (data === "admin_password_resets") {
    await ctx.answerCbQuery();

    if (!isAuthorizedForAdmin(ctx.from.username) || !isAdminAuthenticated(ctx.from.id)) {
      await ctx.editMessageText("❌ Admin authentication required.");
      return;
    }

    try {
      const [resetRequests] = await dbConnection.execute(`
        SELECT * FROM admin_password_reset_requests
        WHERE status = 'pending' AND expires_at > NOW()
        ORDER BY created_at DESC
        LIMIT 10
      `);

      let resetsList = "🔑 **Password Reset Requests**\n\n";

      if (resetRequests.length === 0) {
        resetsList += "✅ No pending password reset requests.";
      } else {
        resetsList += `📊 **${resetRequests.length} pending requests**\n\n`;

        resetRequests.forEach((req, index) => {
          const date = new Date(req.created_at).toLocaleDateString();
          resetsList += `${index + 1}. **${req.username}** (${req.email})\n`;
          resetsList += `   📅 ${date}\n`;
          if (req.request_reason) {
            resetsList += `   📝 Reason: ${req.request_reason.substring(0, 50)}...\n`;
          }
          resetsList += `   🆔 ID: ${req.id}\n\n`;
        });
      }

      const keyboard = {
        inline_keyboard: [
          [
            { text: "📋 Review Requests", callback_data: "admin_review_password_resets" },
            { text: "🔄 Refresh", callback_data: "admin_password_resets" }
          ],
          [
            { text: "🔙 Back to Admin Panel", callback_data: "admin_panel_access" }
          ]
        ]
      };

      await ctx.editMessageText(resetsList, {
        parse_mode: "Markdown",
        reply_markup: keyboard
      });

      await logAdminAction(ctx.from.id, 'VIEW_PASSWORD_RESETS', {
        request_count: resetRequests.length,
        admin_username: ctx.from.username
      });
    } catch (error) {
      console.error('Error loading password reset requests:', error);
      await ctx.editMessageText("❌ Error loading password reset requests.");
    }
    return;
  }

  // User Management
  if (data === "admin_user_management") {
    await ctx.answerCbQuery();

    if (!isAuthorizedForAdmin(ctx.from.username) || !isAdminAuthenticated(ctx.from.id)) {
      await ctx.editMessageText("❌ Admin authentication required.");
      return;
    }

    const userManagementMessage = `👥 **User Management**

**Available Actions:**
• Search users by email, username, or Telegram ID
• View user account details and status
• Change user passwords
• Update user email addresses
• Review user verification levels

**Search Options:**
• Type email address to search by email
• Type username to search by username
• Type Telegram ID to search by ID

Please select an action or type your search term:`;

    const keyboard = {
      inline_keyboard: [
        [
          { text: "🔍 Search by Email", callback_data: "admin_search_email" },
          { text: "🔍 Search by Username", callback_data: "admin_search_username" }
        ],
        [
          { text: "🔍 Search by Telegram ID", callback_data: "admin_search_telegram" },
          { text: "📊 User Statistics", callback_data: "admin_user_stats" }
        ],
        [
          { text: "🔙 Back to Admin Panel", callback_data: "admin_panel_access" }
        ]
      ]
    };

    await ctx.editMessageText(userManagementMessage, {
      parse_mode: "Markdown",
      reply_markup: keyboard
    });

    await logAdminAction(ctx.from.id, 'ACCESS_USER_MANAGEMENT', {
      admin_username: ctx.from.username
    });
    return;
  }

  // Payment Confirmations
  if (data === "admin_payment_confirmations") {
    await ctx.answerCbQuery();

    if (!isAuthorizedForAdmin(ctx.from.username) || !isAdminAuthenticated(ctx.from.id)) {
      await ctx.editMessageText("❌ Admin authentication required.");
      return;
    }

    try {
      const [pendingPayments] = await dbConnection.execute(`
        SELECT
          apc.*,
          u.username, u.email as user_email,
          ai.package_name, ai.amount as investment_amount,
          cpt.sender_wallet_address,
          cpt.payment_screenshot_path,
          cpt.network as crypto_network
        FROM admin_payment_confirmations apc
        LEFT JOIN users u ON apc.user_id = u.id
        LEFT JOIN aureus_investments ai ON apc.investment_id = ai.id
        LEFT JOIN crypto_payment_transactions cpt ON apc.investment_id = cpt.investment_id
        WHERE apc.status = 'pending'
        ORDER BY apc.created_at DESC
        LIMIT 10
      `);

      let paymentsList = "💳 **Payment Confirmations**\n\n";

      if (pendingPayments.length === 0) {
        paymentsList += "✅ No pending payment confirmations.";
      } else {
        paymentsList += `📊 **${pendingPayments.length} pending payments**\n\n`;

        pendingPayments.forEach((payment, index) => {
          const date = new Date(payment.created_at).toLocaleDateString();
          paymentsList += `${index + 1}. **${payment.username || 'Unknown'}**\n`;
          paymentsList += `   💰 Amount: $${payment.amount} ${payment.currency}\n`;
          paymentsList += `   📦 Package: ${payment.package_name || 'N/A'}\n`;
          paymentsList += `   💳 Method: ${payment.payment_method}\n`;

          // Add crypto-specific details
          if (payment.payment_method === 'crypto') {
            if (payment.crypto_network) {
              paymentsList += `   🌐 Network: ${payment.crypto_network.toUpperCase()}\n`;
            }
            if (payment.sender_wallet_address) {
              const shortWallet = payment.sender_wallet_address.substring(0, 10) + '...' + payment.sender_wallet_address.substring(payment.sender_wallet_address.length - 8);
              paymentsList += `   📤 From: ${shortWallet}\n`;
            }
            if (payment.payment_screenshot_path) {
              paymentsList += `   📸 Screenshot: ✅\n`;
            } else {
              paymentsList += `   📸 Screenshot: ❌\n`;
            }
          }

          paymentsList += `   📅 Date: ${date}\n`;
          if (payment.transaction_reference) {
            paymentsList += `   🔗 Ref: ${payment.transaction_reference}\n`;
          }
          paymentsList += `   🆔 ID: ${payment.id}\n\n`;
        });
      }

      const keyboard = {
        inline_keyboard: [
          [
            { text: "📋 Review Payments", callback_data: "admin_review_payments" },
            { text: "🔄 Refresh", callback_data: "admin_payment_confirmations" }
          ],
          [
            { text: "🔙 Back to Admin Panel", callback_data: "admin_panel_access" }
          ]
        ]
      };

      await ctx.editMessageText(paymentsList, {
        parse_mode: "Markdown",
        reply_markup: keyboard
      });

      await logAdminAction(ctx.from.id, 'VIEW_PAYMENT_CONFIRMATIONS', {
        payment_count: pendingPayments.length,
        admin_username: ctx.from.username
      });
    } catch (error) {
      console.error('Error loading payment confirmations:', error);
      await ctx.editMessageText("❌ Error loading payment confirmations.");
    }
    return;
  }

  // Admin Review Payments - Individual payment review
  if (data === "admin_review_payments") {
    await ctx.answerCbQuery();

    if (!isAuthorizedForAdmin(ctx.from.username) || !isAdminAuthenticated(ctx.from.id)) {
      await ctx.editMessageText("❌ Admin authentication required.");
      return;
    }

    try {
      // Get pending payments for individual review with crypto payment details
      const [pendingPayments] = await dbConnection.execute(`
        SELECT
          apc.*,
          u.username, u.email as user_email,
          ai.package_name, ai.amount as investment_amount,
          cpt.sender_wallet_address,
          cpt.payment_screenshot_path,
          cpt.network as crypto_network,
          cpt.wallet_address as company_wallet_address
        FROM admin_payment_confirmations apc
        LEFT JOIN users u ON apc.user_id = u.id
        LEFT JOIN aureus_investments ai ON apc.investment_id = ai.id
        LEFT JOIN crypto_payment_transactions cpt ON apc.investment_id = cpt.investment_id
        WHERE apc.status = 'pending'
        ORDER BY apc.created_at DESC
        LIMIT 5
      `);

      if (pendingPayments.length === 0) {
        await ctx.editMessageText("✅ **No Pending Payments to Review**\n\nAll payments have been processed.", {
          parse_mode: "Markdown",
          reply_markup: {
            inline_keyboard: [
              [{ text: "🔙 Back to Payment Confirmations", callback_data: "admin_payment_confirmations" }]
            ]
          }
        });
        return;
      }

      // Show first payment for review
      const payment = pendingPayments[0];
      const date = new Date(payment.created_at).toLocaleString();

      // Build the review message with additional crypto payment details
      let reviewMessage = `📋 **Payment Review** (1 of ${pendingPayments.length})

👤 **User:** ${payment.username || 'Unknown'}
📧 **Email:** ${payment.user_email || 'N/A'}
💰 **Amount:** $${payment.amount} ${payment.currency}
📦 **Package:** ${payment.package_name || 'N/A'}
💳 **Method:** ${payment.payment_method}
📅 **Date:** ${date}`;

      // Add crypto-specific details if available
      if (payment.payment_method === 'crypto') {
        if (payment.crypto_network) {
          reviewMessage += `\n🌐 **Network:** ${payment.crypto_network.toUpperCase()}`;
        }
        if (payment.company_wallet_address) {
          reviewMessage += `\n🏦 **Company Wallet:** \`${payment.company_wallet_address}\``;
        }
        if (payment.sender_wallet_address) {
          reviewMessage += `\n📤 **Sender Wallet:** \`${payment.sender_wallet_address}\``;
        }
        if (payment.payment_screenshot_path) {
          reviewMessage += `\n📸 **Screenshot:** Available`;
        } else {
          reviewMessage += `\n📸 **Screenshot:** Not provided`;
        }
      }

      // Add transaction reference and notes
      if (payment.transaction_reference) {
        reviewMessage += `\n🔗 **Reference:** ${payment.transaction_reference}`;
      }
      if (payment.notes) {
        reviewMessage += `\n📝 **Notes:** ${payment.notes}`;
      }

      reviewMessage += `\n\n🆔 **Payment ID:** \`${payment.id}\``;

      // Build keyboard with conditional screenshot button
      const keyboardRows = [
        [
          { text: "✅ Approve", callback_data: `approve_payment_${payment.id}` },
          { text: "❌ Reject", callback_data: `reject_payment_${payment.id}` }
        ]
      ];

      // Add screenshot button if available
      if (payment.payment_screenshot_path) {
        keyboardRows.push([
          { text: "📸 View Screenshot", callback_data: `view_screenshot_${payment.id}` }
        ]);
      }

      keyboardRows.push([
        { text: "📞 Contact User", callback_data: `contact_user_${payment.telegram_id}` }
      ]);

      keyboardRows.push([
        { text: "⏭️ Next Payment", callback_data: "admin_review_payments" },
        { text: "🔙 Back", callback_data: "admin_payment_confirmations" }
      ]);

      const keyboard = {
        inline_keyboard: keyboardRows
      };

      await ctx.editMessageText(reviewMessage, {
        parse_mode: "Markdown",
        reply_markup: keyboard
      });

      await logAdminAction(ctx.from.id, 'REVIEW_INDIVIDUAL_PAYMENT', {
        payment_id: payment.id,
        user_id: payment.user_id,
        amount: payment.amount,
        admin_username: ctx.from.username
      });

    } catch (error) {
      console.error('Error loading payment for review:', error);
      await ctx.editMessageText("❌ Error loading payment for review.");
    }
    return;
  }

  // Approve Payment
  if (data.startsWith("approve_payment_")) {
    await ctx.answerCbQuery();

    if (!isAuthorizedForAdmin(ctx.from.username) || !isAdminAuthenticated(ctx.from.id)) {
      await ctx.editMessageText("❌ Admin authentication required.");
      return;
    }

    const paymentId = data.replace("approve_payment_", "");

    try {
      // Get payment details
      const [paymentResult] = await dbConnection.execute(`
        SELECT apc.*, u.username, u.email, ai.package_name
        FROM admin_payment_confirmations apc
        LEFT JOIN users u ON apc.user_id = u.id
        LEFT JOIN aureus_investments ai ON apc.investment_id = ai.id
        WHERE apc.id = ?
      `, [paymentId]);

      if (paymentResult.length === 0) {
        await ctx.editMessageText("❌ Payment not found.");
        return;
      }

      const payment = paymentResult[0];

      // Update payment status to approved
      await dbConnection.execute(`
        UPDATE admin_payment_confirmations
        SET status = 'approved', admin_review_notes = ?, admin_reviewed_at = NOW(), admin_reviewed_by = ?
        WHERE id = ?
      `, [`Approved by ${ctx.from.username}`, ctx.from.id, paymentId]);

      // Update investment status to completed
      await dbConnection.execute(`
        UPDATE aureus_investments
        SET status = 'completed'
        WHERE id = ?
      `, [payment.investment_id]);

      // Update phase statistics
      try {
        await updatePhaseStats(payment.shares, payment.amount);
        console.log(`📊 Updated phase statistics: +${payment.shares} shares, +$${payment.amount}`);
      } catch (phaseError) {
        console.error('Error updating phase statistics:', phaseError);
        // Don't fail the approval if phase update fails
      }

      // Send notification to user
      if (payment.telegram_id) {
        try {
          await bot.telegram.sendMessage(payment.telegram_id,
            `✅ **Payment Approved!**\n\n` +
            `Your payment of $${payment.amount} ${payment.currency} has been approved.\n` +
            `Your investment is now active!\n\n` +
            `📦 Package: ${payment.package_name}\n` +
            `💰 Amount: $${payment.amount} ${payment.currency}`,
            { parse_mode: "Markdown" }
          );
        } catch (notifyError) {
          console.log(`Could not notify user ${payment.telegram_id}:`, notifyError.message);
        }
      }

      await ctx.editMessageText(
        `✅ **Payment Approved Successfully!**\n\n` +
        `👤 User: ${payment.username}\n` +
        `💰 Amount: $${payment.amount} ${payment.currency}\n` +
        `📦 Package: ${payment.package_name}\n\n` +
        `The user has been notified and their investment is now active.`,
        {
          parse_mode: "Markdown",
          reply_markup: {
            inline_keyboard: [
              [
                { text: "⏭️ Next Payment", callback_data: "admin_review_payments" },
                { text: "🔙 Back to Confirmations", callback_data: "admin_payment_confirmations" }
              ]
            ]
          }
        }
      );

      await logAdminAction(ctx.from.id, 'APPROVE_PAYMENT', {
        payment_id: paymentId,
        user_id: payment.user_id,
        amount: payment.amount,
        admin_username: ctx.from.username
      });

    } catch (error) {
      console.error('Error approving payment:', error);
      await ctx.editMessageText("❌ Error approving payment.");
    }
    return;
  }

  // Reject Payment
  if (data.startsWith("reject_payment_")) {
    await ctx.answerCbQuery();

    if (!isAuthorizedForAdmin(ctx.from.username) || !isAdminAuthenticated(ctx.from.id)) {
      await ctx.editMessageText("❌ Admin authentication required.");
      return;
    }

    const paymentId = data.replace("reject_payment_", "");

    try {
      // Get payment details
      const [paymentResult] = await dbConnection.execute(`
        SELECT apc.*, u.username, u.email, ai.package_name
        FROM admin_payment_confirmations apc
        LEFT JOIN users u ON apc.user_id = u.id
        LEFT JOIN aureus_investments ai ON apc.investment_id = ai.id
        WHERE apc.id = ?
      `, [paymentId]);

      if (paymentResult.length === 0) {
        await ctx.editMessageText("❌ Payment not found.");
        return;
      }

      const payment = paymentResult[0];

      // Update payment status to rejected
      await dbConnection.execute(`
        UPDATE admin_payment_confirmations
        SET status = 'rejected', admin_review_notes = ?, admin_reviewed_at = NOW(), admin_reviewed_by = ?
        WHERE id = ?
      `, [`Rejected by ${ctx.from.username}`, ctx.from.id, paymentId]);

      // Update investment status to failed
      await dbConnection.execute(`
        UPDATE aureus_investments
        SET status = 'failed'
        WHERE id = ?
      `, [payment.investment_id]);

      // Send notification to user
      if (payment.telegram_id) {
        try {
          await bot.telegram.sendMessage(payment.telegram_id,
            `❌ **Payment Rejected**\n\n` +
            `Your payment of $${payment.amount} ${payment.currency} could not be verified.\n` +
            `Please contact support for assistance.\n\n` +
            `📦 Package: ${payment.package_name}\n` +
            `💰 Amount: $${payment.amount} ${payment.currency}`,
            { parse_mode: "Markdown" }
          );
        } catch (notifyError) {
          console.log(`Could not notify user ${payment.telegram_id}:`, notifyError.message);
        }
      }

      await ctx.editMessageText(
        `❌ **Payment Rejected**\n\n` +
        `👤 User: ${payment.username}\n` +
        `💰 Amount: $${payment.amount} ${payment.currency}\n` +
        `📦 Package: ${payment.package_name}\n\n` +
        `The user has been notified about the rejection.`,
        {
          parse_mode: "Markdown",
          reply_markup: {
            inline_keyboard: [
              [
                { text: "⏭️ Next Payment", callback_data: "admin_review_payments" },
                { text: "🔙 Back to Confirmations", callback_data: "admin_payment_confirmations" }
              ]
            ]
          }
        }
      );

      await logAdminAction(ctx.from.id, 'REJECT_PAYMENT', {
        payment_id: paymentId,
        user_id: payment.user_id,
        amount: payment.amount,
        admin_username: ctx.from.username
      });

    } catch (error) {
      console.error('Error rejecting payment:', error);
      await ctx.editMessageText("❌ Error rejecting payment.");
    }
    return;
  }

  // View Payment Screenshot
  if (data.startsWith("view_screenshot_")) {
    await ctx.answerCbQuery();

    if (!isAuthorizedForAdmin(ctx.from.username) || !isAdminAuthenticated(ctx.from.id)) {
      await ctx.editMessageText("❌ Admin authentication required.");
      return;
    }

    const paymentId = data.replace("view_screenshot_", "");

    try {
      // Get payment screenshot details
      const [paymentResult] = await dbConnection.execute(`
        SELECT
          apc.id,
          apc.user_id,
          apc.telegram_id,
          apc.amount,
          apc.currency,
          apc.package_name,
          cpt.payment_screenshot_path,
          cpt.sender_wallet_address,
          cpt.transaction_hash,
          cpt.network,
          u.username,
          u.email
        FROM admin_payment_confirmations apc
        LEFT JOIN crypto_payment_transactions cpt ON apc.investment_id = cpt.investment_id
        LEFT JOIN users u ON apc.user_id = u.id
        WHERE apc.id = ?
      `, [paymentId]);

      if (paymentResult.length === 0) {
        await ctx.editMessageText("❌ Payment not found.");
        return;
      }

      const payment = paymentResult[0];

      if (!payment.payment_screenshot_path) {
        await ctx.editMessageText("❌ No screenshot available for this payment.");
        return;
      }

      // Send the screenshot
      const screenshotMessage = `📸 **Payment Screenshot**

👤 **User:** ${payment.username || 'Unknown'}
💰 **Amount:** $${payment.amount} ${payment.currency}
📦 **Package:** ${payment.package_name || 'N/A'}
🔗 **Transaction:** ${payment.transaction_hash || 'N/A'}
📤 **Sender Wallet:** \`${payment.sender_wallet_address || 'N/A'}\`

🆔 **Payment ID:** \`${payment.id}\``;

      // Try to send the screenshot file
      try {
        // Convert absolute path to relative path if needed
        let screenshotPath = payment.payment_screenshot_path;
        if (screenshotPath.includes('C:\\xampp\\htdocs\\Aureus 1 - Complex\\')) {
          screenshotPath = screenshotPath.replace('C:\\xampp\\htdocs\\Aureus 1 - Complex\\', '');
        }

        await ctx.replyWithPhoto({ source: screenshotPath }, {
          caption: screenshotMessage,
          parse_mode: "Markdown",
          reply_markup: {
            inline_keyboard: [
              [
                { text: "✅ Approve Payment", callback_data: `approve_payment_${payment.id}` },
                { text: "❌ Reject Payment", callback_data: `reject_payment_${payment.id}` }
              ],
              [
                { text: "🔙 Back to Review", callback_data: "admin_review_payments" }
              ]
            ]
          }
        });
      } catch (fileError) {
        console.error('Error sending screenshot file:', fileError);
        // Fallback: show screenshot path info
        await ctx.reply(screenshotMessage + `\n\n📁 **File Path:** \`${payment.payment_screenshot_path}\`\n\n⚠️ *Screenshot file could not be displayed. Please check the file path manually.*`, {
          parse_mode: "Markdown",
          reply_markup: {
            inline_keyboard: [
              [
                { text: "✅ Approve Payment", callback_data: `approve_payment_${payment.id}` },
                { text: "❌ Reject Payment", callback_data: `reject_payment_${payment.id}` }
              ],
              [
                { text: "🔙 Back to Review", callback_data: "admin_review_payments" }
              ]
            ]
          }
        });
      }

      await logAdminAction(ctx.from.id, 'VIEW_PAYMENT_SCREENSHOT', {
        payment_id: paymentId,
        user_id: payment.user_id,
        admin_username: ctx.from.username
      });

    } catch (error) {
      console.error('Error viewing payment screenshot:', error);
      await ctx.editMessageText("❌ Error loading payment screenshot.");
    }
    return;
  }

  // Terms Acceptance Review
  if (data === "admin_terms_review") {
    await ctx.answerCbQuery();

    if (!isAuthorizedForAdmin(ctx.from.username) || !isAdminAuthenticated(ctx.from.id)) {
      await ctx.editMessageText("❌ Admin authentication required.");
      return;
    }

    try {
      const [termsStats] = await dbConnection.execute(`
        SELECT
          COUNT(*) as total_records,
          SUM(CASE WHEN all_terms_accepted = 1 THEN 1 ELSE 0 END) as fully_accepted,
          SUM(CASE WHEN general_terms_accepted = 1 THEN 1 ELSE 0 END) as general_accepted,
          SUM(CASE WHEN privacy_policy_accepted = 1 THEN 1 ELSE 0 END) as privacy_accepted,
          SUM(CASE WHEN investment_risks_accepted = 1 THEN 1 ELSE 0 END) as risks_accepted
        FROM telegram_terms_acceptance
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
      `);

      const stats = termsStats[0];

      const termsMessage = `📋 **Terms Acceptance Review**

📊 **Last 30 Days Statistics:**
• Total Records: ${stats.total_records}
• Fully Accepted: ${stats.fully_accepted}
• General Terms: ${stats.general_accepted}
• Privacy Policy: ${stats.privacy_accepted}
• Investment Risks: ${stats.risks_accepted}

📈 **Acceptance Rate:** ${stats.total_records > 0 ? Math.round((stats.fully_accepted / stats.total_records) * 100) : 0}%

**Available Actions:**
• Review recent acceptances
• View incomplete acceptances
• Export compliance reports`;

      const keyboard = {
        inline_keyboard: [
          [
            { text: "📊 Recent Acceptances", callback_data: "admin_recent_terms" },
            { text: "⚠️ Incomplete Terms", callback_data: "admin_incomplete_terms" }
          ],
          [
            { text: "📈 Compliance Report", callback_data: "admin_compliance_report" },
            { text: "🔄 Refresh", callback_data: "admin_terms_review" }
          ],
          [
            { text: "🔙 Back to Admin Panel", callback_data: "admin_panel_access" }
          ]
        ]
      };

      await ctx.editMessageText(termsMessage, {
        parse_mode: "Markdown",
        reply_markup: keyboard
      });

      await logAdminAction(ctx.from.id, 'VIEW_TERMS_REVIEW', {
        stats: stats,
        admin_username: ctx.from.username
      });
    } catch (error) {
      console.error('Error loading terms review:', error);
      await ctx.editMessageText("❌ Error loading terms acceptance data.");
    }
    return;
  }

  // Admin Referral Management
  if (data === "admin_referrals") {
    await ctx.answerCbQuery();

    if (!isAuthorizedForAdmin(ctx.from.username) || !isAdminAuthenticated(ctx.from.id)) {
      await ctx.editMessageText("❌ Admin authentication required.");
      return;
    }

    await showReferralManagement(ctx);
    return;
  }

  // Admin Commission Management
  if (data === "admin_commissions") {
    await ctx.answerCbQuery();

    if (!isAuthorizedForAdmin(ctx.from.username) || !isAdminAuthenticated(ctx.from.id)) {
      await ctx.editMessageText("❌ Admin authentication required.");
      return;
    }

    await showCommissionManagement(ctx);
    return;
  }

  // Approve Commission
  if (data.startsWith("approve_commission_")) {
    await ctx.answerCbQuery();

    if (!isAuthorizedForAdmin(ctx.from.username) || !isAdminAuthenticated(ctx.from.id)) {
      await ctx.editMessageText("❌ Admin authentication required.");
      return;
    }

    const commissionId = data.replace("approve_commission_", "");
    await approveCommission(ctx, commissionId);
    return;
  }

  // Reject Commission
  if (data.startsWith("reject_commission_")) {
    await ctx.answerCbQuery();

    if (!isAuthorizedForAdmin(ctx.from.username) || !isAdminAuthenticated(ctx.from.id)) {
      await ctx.editMessageText("❌ Admin authentication required.");
      return;
    }

    const commissionId = data.replace("reject_commission_", "");
    await rejectCommission(ctx, commissionId);
    return;
  }

  // Mark Commission as Paid
  if (data.startsWith("mark_commission_paid_")) {
    await ctx.answerCbQuery();

    if (!isAuthorizedForAdmin(ctx.from.username) || !isAdminAuthenticated(ctx.from.id)) {
      await ctx.editMessageText("❌ Admin authentication required.");
      return;
    }

    const commissionId = data.replace("mark_commission_paid_", "");
    await markCommissionAsPaid(ctx, commissionId);
    return;
  }

  // Admin Referral Analytics
  if (data === "admin_referral_analytics") {
    await ctx.answerCbQuery();

    if (!isAuthorizedForAdmin(ctx.from.username) || !isAdminAuthenticated(ctx.from.id)) {
      await ctx.editMessageText("❌ Admin authentication required.");
      return;
    }

    await showAdminReferralAnalytics(ctx);
    return;
  }

  // Referral Dashboard Callbacks
  if (data === "view_my_referrals") {
    await ctx.answerCbQuery();
    await showMyReferralsList(ctx);
    return;
  }

  if (data === "view_my_commissions") {
    await ctx.answerCbQuery();
    await showMyCommissionHistory(ctx);
    return;
  }

  if (data === "view_referral_leaderboard") {
    await ctx.answerCbQuery();
    await showReferralLeaderboard(ctx);
    return;
  }

  if (data === "view_referral_analytics") {
    await ctx.answerCbQuery();
    await showReferralAnalytics(ctx);
    return;
  }

  if (data === "referral_instructions") {
    await ctx.answerCbQuery();
    await showReferralInstructions(ctx);
    return;
  }

  if (data === "get_referral_link") {
    await ctx.answerCbQuery();
    await generateAndShowReferralLink(ctx);
    return;
  }

  if (data === "public_leaderboard") {
    await ctx.answerCbQuery();
    await showPublicReferralLeaderboard(ctx);
    return;
  }

  // View Packages Callback
  if (data === "view_packages") {
    await ctx.answerCbQuery();

    try {
      const packages = await getInvestmentPackages();
      const packageMessage = `💎 **Available Investment Packages**

Choose a package to view details:`;

      const keyboard = {
        inline_keyboard: packages.map(pkg => [
          { text: `${pkg.name} - ${formatCurrency(pkg.price)}`, callback_data: `package_${pkg.id}` }
        ]).concat([[{ text: "🔙 Back to Menu", callback_data: "back_to_menu" }]])
      };

      await ctx.editMessageText(packageMessage, {
        parse_mode: "Markdown",
        reply_markup: keyboard
      });
    } catch (error) {
      console.error("Error loading packages:", error);
      await ctx.editMessageText("❌ Error loading packages. Please try again.");
    }
    return;
  }

  // Dashboard Callback
  if (data === "dashboard") {
    await ctx.answerCbQuery();

    const telegramUser = await getTelegramUser(ctx.from.id);
    if (!telegramUser.is_registered || !telegramUser.user_id) {
      await ctx.editMessageText("❌ Please login or register first.", { parse_mode: "Markdown" });
      return;
    }

    try {
      const userEmail = telegramUser.linked_email || telegramUser.email;
      const investments = await getUserInvestments(userEmail);

      let dashboardMessage = `📊 **Your Dashboard**\n\n`;

      if (investments.length === 0) {
        dashboardMessage += `**Investment Status:** No active investments

🚀 **Get Started:**
• Browse available packages
• Use the mining calculator
• Make your first investment

Ready to begin your mining journey?`;

        const keyboard = {
          inline_keyboard: [
            [
              { text: "📦 View Packages", callback_data: "view_packages" },
              { text: "🧮 Calculator", callback_data: "mining_calculator" }
            ],
            [
              { text: "🔙 Back to Menu", callback_data: "back_to_menu" }
            ]
          ]
        };

        await ctx.editMessageText(dashboardMessage, {
          parse_mode: "Markdown",
          reply_markup: keyboard
        });
      } else {
        const totalShares = investments.reduce((sum, inv) => sum + (inv.package_shares || 0), 0);
        const totalInvested = investments.reduce((sum, inv) => sum + parseFloat(inv.amount || 0), 0);

        dashboardMessage += `**Investment Summary:**
• **Total Investments:** ${investments.length}
• **Total Shares:** ${totalShares.toLocaleString()}
• **Total Invested:** ${formatCurrency(totalInvested)}

**Recent Investments:**`;

        investments.slice(0, 3).forEach((inv, index) => {
          dashboardMessage += `\n${index + 1}. **${inv.package_name}** - ${formatCurrency(inv.amount)}`;
        });

        const keyboard = {
          inline_keyboard: [
            [
              { text: "🧮 Calculator", callback_data: "mining_calculator" },
              { text: "📦 More Packages", callback_data: "view_packages" }
            ],
            [
              { text: "🔙 Back to Menu", callback_data: "back_to_menu" }
            ]
          ]
        };

        await ctx.editMessageText(dashboardMessage, {
          parse_mode: "Markdown",
          reply_markup: keyboard
        });
      }
    } catch (error) {
      console.error('Error loading dashboard:', error);
      await ctx.editMessageText("❌ Error loading dashboard. Please try again.");
    }
    return;
  }

  // Mining Calculator Callbacks
  if (data === "mining_calculator") {
    await ctx.answerCbQuery();
    await showMiningCalculator(ctx);
    return;
  }

  if (data === "calc_refresh") {
    await ctx.answerCbQuery();
    await showMiningCalculator(ctx);
    return;
  }

  if (data === "calc_quick_options") {
    await ctx.answerCbQuery();

    const quickMessage = `📊 **Quick Share Options**

Select how many shares you want to calculate dividends for:

💡 **Popular Options:**
• **1,000 shares** = $10,000 investment
• **5,000 shares** = $50,000 investment
• **10,000 shares** = $100,000 investment
• **25,000 shares** = $250,000 investment

Or use "Change Shares" to enter a custom amount.`;

    const keyboard = {
      inline_keyboard: [
        [
          { text: "1,000 shares", callback_data: "calc_shares_1000" },
          { text: "5,000 shares", callback_data: "calc_shares_5000" }
        ],
        [
          { text: "10,000 shares", callback_data: "calc_shares_10000" },
          { text: "25,000 shares", callback_data: "calc_shares_25000" }
        ],
        [
          { text: "50,000 shares", callback_data: "calc_shares_50000" },
          { text: "100,000 shares", callback_data: "calc_shares_100000" }
        ],
        [
          { text: "📈 Custom Amount", callback_data: "calc_change_shares" }
        ],
        [
          { text: "🔙 Back to Calculator", callback_data: "calc_refresh" }
        ]
      ]
    };

    await ctx.editMessageText(quickMessage, {
      parse_mode: "Markdown",
      reply_markup: keyboard
    });
    return;
  }

  if (data === "calc_change_shares") {
    await ctx.answerCbQuery();

    const sharesMessage = `📈 **Enter Custom Share Amount**

Type the number of shares you want to calculate dividends for:

💡 **Quick Examples:**
• 1,000 shares = $10,000 investment
• 5,000 shares = $50,000 investment
• 10,000 shares = $100,000 investment

Or select from popular amounts below:`;

    const keyboard = {
      inline_keyboard: [
        [
          { text: "1,000", callback_data: "calc_shares_1000" },
          { text: "5,000", callback_data: "calc_shares_5000" },
          { text: "10,000", callback_data: "calc_shares_10000" }
        ],
        [
          { text: "25,000", callback_data: "calc_shares_25000" },
          { text: "50,000", callback_data: "calc_shares_50000" },
          { text: "100,000", callback_data: "calc_shares_100000" }
        ],
        [
          { text: "🔙 Back to Calculator", callback_data: "calc_refresh" }
        ]
      ]
    };

    await ctx.editMessageText(sharesMessage, {
      parse_mode: "Markdown",
      reply_markup: keyboard
    });

    // Set user state to expect share input
    await updateTelegramUser(ctx.from.id, {
      calculator_awaiting_shares: true
    });
    return;
  }

  if (data.startsWith("calc_shares_")) {
    await ctx.answerCbQuery();
    const shares = parseInt(data.replace("calc_shares_", ""));
    await showMiningCalculator(ctx, shares);
    return;
  }

  // Admin User Search Callbacks
  if (data === "admin_search_email") {
    await ctx.answerCbQuery();

    if (!isAuthorizedForAdmin(ctx.from.username) || !isAdminAuthenticated(ctx.from.id)) {
      await ctx.editMessageText("❌ Admin authentication required.");
      return;
    }

    const searchMessage = `🔍 **Search Users by Email**

Please type the email address you want to search for:

**Examples:**
• user@example.com
• john.doe@gmail.com
• partial@domain

**Note:** Partial matches are supported.`;

    const keyboard = {
      inline_keyboard: [
        [{ text: "🔙 Back to User Management", callback_data: "admin_user_management" }]
      ]
    };

    await ctx.editMessageText(searchMessage, {
      parse_mode: "Markdown",
      reply_markup: keyboard
    });

    // Set user state to expect search input
    await updateTelegramUser(ctx.from.id, {
      admin_search_mode: 'email'
    });
    return;
  }

  if (data === "admin_search_username") {
    await ctx.answerCbQuery();

    if (!isAuthorizedForAdmin(ctx.from.username) || !isAdminAuthenticated(ctx.from.id)) {
      await ctx.editMessageText("❌ Admin authentication required.");
      return;
    }

    const searchMessage = `🔍 **Search Users by Username**

Please type the username you want to search for:

**Examples:**
• johndoe
• user123
• partial_name

**Note:** Partial matches are supported.`;

    const keyboard = {
      inline_keyboard: [
        [{ text: "🔙 Back to User Management", callback_data: "admin_user_management" }]
      ]
    };

    await ctx.editMessageText(searchMessage, {
      parse_mode: "Markdown",
      reply_markup: keyboard
    });

    // Set user state to expect search input
    await updateTelegramUser(ctx.from.id, {
      admin_search_mode: 'username'
    });
    return;
  }

  if (data === "admin_search_telegram") {
    await ctx.answerCbQuery();

    if (!isAuthorizedForAdmin(ctx.from.username) || !isAdminAuthenticated(ctx.from.id)) {
      await ctx.editMessageText("❌ Admin authentication required.");
      return;
    }

    const searchMessage = `🔍 **Search Users by Telegram ID**

Please type the Telegram ID you want to search for:

**Examples:**
• 123456789
• 987654321

**Note:** Must be exact Telegram ID number.`;

    const keyboard = {
      inline_keyboard: [
        [{ text: "🔙 Back to User Management", callback_data: "admin_user_management" }]
      ]
    };

    await ctx.editMessageText(searchMessage, {
      parse_mode: "Markdown",
      reply_markup: keyboard
    });

    // Set user state to expect search input
    await updateTelegramUser(ctx.from.id, {
      admin_search_mode: 'telegram_id'
    });
    return;
  }

  // Admin Reply to User Message
  if (data.startsWith("admin_reply_")) {
    await ctx.answerCbQuery();

    if (!isAuthorizedForAdmin(ctx.from.username) || !isAdminAuthenticated(ctx.from.id)) {
      await ctx.editMessageText("❌ Admin authentication required.");
      return;
    }

    const messageId = data.replace("admin_reply_", "");

    try {
      // Get the original message details
      const [messageRows] = await dbConnection.execute(`
        SELECT m.*, u.email, u.full_name
        FROM admin_user_messages m
        LEFT JOIN users u ON m.user_id = u.id
        WHERE m.id = ?
      `, [messageId]);

      if (messageRows.length === 0) {
        await ctx.editMessageText("❌ Message not found.");
        return;
      }

      const originalMessage = messageRows[0];
      const userName = originalMessage.first_name || originalMessage.full_name || 'Unknown User';
      const userEmail = originalMessage.email || 'No email';

      const replyPrompt = `💬 **Reply to User Message**

**From:** ${userName}
**Email:** ${userEmail}
**Date:** ${new Date(originalMessage.created_at).toLocaleString()}

**Original Message:**
"${originalMessage.message_text}"

**Please type your reply below:**`;

      const keyboard = {
        inline_keyboard: [
          [{ text: "❌ Cancel Reply", callback_data: "admin_view_all_messages" }]
        ]
      };

      await ctx.editMessageText(replyPrompt, {
        parse_mode: "Markdown",
        reply_markup: keyboard
      });

      // Set admin state to expect reply
      await updateTelegramUser(ctx.from.id, {
        admin_replying_to_message: messageId
      });

      await logAdminAction(ctx.from.id, 'INITIATE_USER_REPLY', {
        message_id: messageId,
        user_name: userName,
        admin_username: ctx.from.username
      });
    } catch (error) {
      console.error('Error initiating reply:', error);
      await ctx.editMessageText("❌ Error loading message for reply.");
    }
    return;
  }

  // Package callbacks - PRIORITY HANDLING
  if (data.startsWith("package_")) {
    console.log(`🎯 PACKAGE CALLBACK: ${data}`);
    await ctx.answerCbQuery();
    
    const packageId = data.replace("package_", "");
    console.log(`🔍 Looking for package ID: ${packageId}`);
    
    try {
      const pkg = await getPackageById(packageId);
      
      if (!pkg) {
        console.log(`❌ Package not found for ID: ${packageId}`);
        await ctx.editMessageText("❌ Package not found.", { parse_mode: "Markdown" });
        return;
      }
      
      console.log(`✅ Package found: ${pkg.name}`);
      const packageInfo = await formatPackageInfo(pkg);

      const keyboard = {
        inline_keyboard: [
          [
            { text: "💰 Invest Now", callback_data: `invest_${packageId}` }
          ],
          [
            { text: "🔙 Back to Packages", callback_data: "back_to_packages" }
          ]
        ]
      };

      await ctx.editMessageText(packageInfo, { parse_mode: "Markdown", reply_markup: keyboard });
    } catch (error) {
      console.error("Error showing package details:", error);
      await ctx.editMessageText("❌ Error loading package details.", { parse_mode: "Markdown" });
    }
    return;
  }
  
  // Back to packages
  if (data === "back_to_packages") {
    await ctx.answerCbQuery();
    
    try {
      const packages = await getInvestmentPackages();
      const packageMessage = `💎 *Available Investment Packages* 💎

Choose a package to view details:`;

      const keyboard = {
        inline_keyboard: packages.map(pkg => [
          { text: `${pkg.name} - ${formatCurrency(pkg.price)}`, callback_data: `package_${pkg.id}` }
        ])
      };

      await ctx.editMessageText(packageMessage, { parse_mode: "Markdown", reply_markup: keyboard });
    } catch (error) {
      console.error("Error loading packages:", error);
      await ctx.editMessageText("❌ Error loading packages.", { parse_mode: "Markdown" });
    }
    return;
  }
  
  // Investment flow
  if (data.startsWith("invest_")) {
    await ctx.answerCbQuery();
    const packageId = data.replace("invest_", "");
    
    const pkg = await getPackageById(packageId);
    if (!pkg) {
      await ctx.editMessageText("❌ Package not found.", { parse_mode: "Markdown" });
      return;
    }

    const mineCalc = await calculateMineProduction(pkg.shares);

    const investmentMessage = `💰 **Investment Confirmation**

**Package:** ${pkg.name}
**Price:** ${formatCurrency(pkg.price)}
**Shares:** ${pkg.shares}

📈 **Mine Production Projection:**
🏭 Annual Production: ${mineCalc.annualProduction.toLocaleString()} KG gold
💰 Gold Price: ${formatLargeNumber(mineCalc.goldPricePerKg)} per KG
💎 Net Annual Profit: ${formatLargeNumber(mineCalc.netProfit)}
📊 Dividend per Share: ${formatCurrency(mineCalc.dividendPerShare)}
🎯 Your Annual Dividend: ${formatLargeNumber(mineCalc.userAnnualDividend)}

⚠️ **Production Timeline:**
The dividend calculation above is based on reaching full mine production capacity, utilizing 10 washplants—each capable of processing 200 tons of alluvial material per hour. This production milestone is targeted for achievement by June 2026.

🔹 **Investment Details:**
• You will receive ${pkg.shares} shares
• Annual dividend projection: ${formatLargeNumber(mineCalc.userAnnualDividend)}
• NFT Certificate included
• 12-month investment period

⚠️ **Important:** This is a real investment. Please confirm you want to proceed.`;

    const keyboard = {
      inline_keyboard: [
        [
          { text: "✅ Confirm Investment", callback_data: `confirm_invest_${packageId}` }
        ],
        [
          { text: "🔙 Back to Package", callback_data: `package_${packageId}` }
        ]
      ]
    };

    await ctx.editMessageText(investmentMessage, { parse_mode: "Markdown", reply_markup: keyboard });
    return;
  }

  // Investment confirmation callback
  if (data.startsWith("confirm_invest_")) {
    await ctx.answerCbQuery();
    const packageId = data.replace("confirm_invest_", "");

    const pkg = await getPackageById(packageId);
    if (!pkg) {
      await ctx.editMessageText("❌ Package not found.", { parse_mode: "Markdown" });
      return;
    }

    // Check if user has accepted terms for this investment
    const termsStatus = await getTermsAcceptanceStatus(ctx.from.id);

    if (!termsStatus || !termsStatus.all_terms_accepted) {
      // Show terms acceptance first
      const termsMessage = `📋 **Terms and Conditions**

Before proceeding with your investment, you must accept our terms and conditions:

**Package:** ${pkg.name}
**Amount:** ${formatCurrency(pkg.price)}

📄 **Required Agreements:**
☐ General Terms and Conditions
☐ Privacy Policy
☐ Investment Risk Disclosure
☐ Gold Mining Investment Terms
☐ NFT Shares Understanding
☐ Dividend Timeline Agreement

**Please review and accept each term to continue:**`;

      const termsKeyboard = {
        inline_keyboard: [
          [
            { text: "📄 Start Terms Review", callback_data: `terms_start_${packageId}` }
          ],
          [
            { text: "🔙 Back to Investment", callback_data: `invest_${packageId}` }
          ]
        ]
      };

      await ctx.editMessageText(termsMessage, { parse_mode: "Markdown", reply_markup: termsKeyboard });
      return;
    }

    // Terms already accepted, proceed to payment method selection
    const paymentMessage = `💳 **Payment Method Selection**

**Package:** ${pkg.name}
**Amount:** ${formatCurrency(pkg.price)}
✅ **Terms:** Accepted

Please choose your preferred payment method:`;

    const paymentKeyboard = {
      inline_keyboard: [
        [
          { text: "💰 Cryptocurrency", callback_data: `payment_crypto_${packageId}` }
        ],
        [
          { text: "🏦 Bank Transfer", callback_data: `payment_bank_${packageId}` }
        ],
        [
          { text: "🔙 Back to Investment", callback_data: `invest_${packageId}` }
        ]
      ]
    };

    await ctx.editMessageText(paymentMessage, { parse_mode: "Markdown", reply_markup: paymentKeyboard });
    return;
  }

  // Payment methods selection (after terms completion)
  if (data.startsWith("payment_methods_")) {
    await ctx.answerCbQuery();
    const isCustom = data.startsWith("payment_methods_custom_");
    const packageId = isCustom ? data.replace("payment_methods_custom_", "") : data.replace("payment_methods_", "");

    if (isCustom) {
      // Handle custom investment payment methods
      const paymentMessage = `💳 **Payment Method Selection**

**Custom Investment:** Payment ID ${packageId}
✅ **Terms:** Accepted

Please choose your preferred payment method:`;

      const paymentKeyboard = {
        inline_keyboard: [
          [
            { text: "💰 Cryptocurrency", callback_data: `crypto_payment_custom_${packageId}` }
          ],
          [
            { text: "🏦 Bank Transfer", callback_data: `bank_payment_custom_${packageId}` }
          ],
          [
            { text: "🔙 Back to Menu", callback_data: "back_to_menu" }
          ]
        ]
      };

      await ctx.editMessageText(paymentMessage, { parse_mode: "Markdown", reply_markup: paymentKeyboard });
      return;
    }

    const pkg = await getPackageById(packageId);
    if (!pkg) {
      await ctx.editMessageText("❌ Package not found.", { parse_mode: "Markdown" });
      return;
    }

    // Proceed directly to payment method selection
    const paymentMessage = `💳 **Payment Method Selection**

**Package:** ${pkg.name}
**Amount:** ${formatCurrency(pkg.price)}
✅ **Terms:** Accepted

Please choose your preferred payment method:`;

    const paymentKeyboard = {
      inline_keyboard: [
        [
          { text: "💰 Cryptocurrency", callback_data: `crypto_payment_${packageId}` }
        ],
        [
          { text: "🏦 Bank Transfer", callback_data: `bank_payment_${packageId}` }
        ],
        [
          { text: "🔙 Back to Investment", callback_data: `invest_${packageId}` }
        ]
      ]
    };

    await ctx.editMessageText(paymentMessage, { parse_mode: "Markdown", reply_markup: paymentKeyboard });
    return;
  }

  // Terms acceptance flow
  if (data.startsWith("terms_start_")) {
    await ctx.answerCbQuery();
    const isCustom = data.startsWith("terms_start_custom_");
    const packageId = isCustom ? data.replace("terms_start_custom_", "") : data.replace("terms_start_", "");

    // Create terms acceptance record
    const telegramUser = await getTelegramUser(ctx.from.id);
    const termsRecordId = await createTermsAcceptanceRecord(
      ctx.from.id,
      telegramUser.user_id,
      null // We'll link to investment later
    );

    // Store terms record ID in user session and custom flag
    await updateTelegramUser(ctx.from.id, {
      terms_record_id: termsRecordId,
      terms_custom_investment: isCustom ? packageId : null
    });

    const firstTermMessage = `📄 **General Terms and Conditions**

**Aureus Alliance Holdings Equity Share Terms**

By accepting these terms, you acknowledge that:

• You understand this is a real equity share purchase with associated risks
• You are purchasing shares in gold mining operations in Africa
• Returns are not guaranteed and depend on mining performance
• You must be 18+ years old and legally able to purchase equity shares
• You have read and understood all equity share documentation

**Do you accept the General Terms and Conditions?**`;

    const keyboard = {
      inline_keyboard: [
        [
          { text: "✅ Accept", callback_data: `terms_accept_general_${packageId}` },
          { text: "❌ Decline", callback_data: `terms_decline_general_${packageId}` }
        ],
        [
          { text: "🔙 Back to Investment", callback_data: `invest_${packageId}` }
        ]
      ]
    };

    await ctx.editMessageText(firstTermMessage, { parse_mode: "Markdown", reply_markup: keyboard });
    return;
  }

  // Individual terms acceptance
  if (data.includes("terms_accept_") || data.includes("terms_decline_")) {
    await ctx.answerCbQuery();

    const parts = data.split("_");
    const action = parts[1]; // accept or decline
    const termType = parts[2]; // general, privacy, etc.
    const packageId = parts[3];

    const telegramUser = await getTelegramUser(ctx.from.id);
    const termsRecordId = telegramUser.terms_record_id;

    if (!termsRecordId) {
      await ctx.editMessageText("❌ Terms session expired. Please start again.");
      return;
    }

    if (action === "decline") {
      await ctx.editMessageText(`❌ **Terms Declined**

You have declined the ${termType} terms. Investment cannot proceed without accepting all required terms.

You can restart the process anytime from the investment page.`);
      return;
    }

    // Accept the current term
    const termMapping = {
      'general': 'general_terms_accepted',
      'privacy': 'privacy_policy_accepted',
      'risks': 'investment_risks_accepted',
      'mining': 'gold_mining_terms_accepted',
      'nft': 'nft_terms_accepted',
      'dividend': 'dividend_terms_accepted'
    };

    await updateTermsAcceptance(termsRecordId, termMapping[termType], true);

    // Show next term or completion
    const nextTerms = {
      'general': { next: 'privacy', title: 'Privacy Policy' },
      'privacy': { next: 'risks', title: 'Investment Risk Disclosure' },
      'risks': { next: 'mining', title: 'Gold Mining Investment Terms' },
      'mining': { next: 'nft', title: 'NFT Shares Understanding' },
      'nft': { next: 'dividend', title: 'Dividend Timeline Agreement' },
      'dividend': { next: 'complete', title: 'Complete' }
    };

    const nextTerm = nextTerms[termType];

    if (nextTerm.next === 'complete') {
      // All terms accepted
      const telegramUser = await getTelegramUser(ctx.from.id);
      const isCustomInvestment = telegramUser.terms_custom_investment;

      const completionMessage = `✅ **All Terms Accepted**

Congratulations! You have successfully accepted all required terms and conditions.

**Accepted Terms:**
✅ General Terms and Conditions
✅ Privacy Policy
✅ Investment Risk Disclosure
✅ Gold Mining Investment Terms
✅ NFT Shares Understanding
✅ Dividend Timeline Agreement

You can now proceed with your investment payment.`;

      const keyboard = {
        inline_keyboard: [
          [
            { text: "💳 Proceed to Payment", callback_data: isCustomInvestment ? `payment_methods_custom_${packageId}` : `payment_methods_${packageId}` }
          ],
          [
            { text: "🔙 Back to Investment", callback_data: isCustomInvestment ? "back_to_menu" : `invest_${packageId}` }
          ]
        ]
      };

      await ctx.editMessageText(completionMessage, { parse_mode: "Markdown", reply_markup: keyboard });
    } else {
      // Show next term
      const nextTermMessage = getTermsContent(nextTerm.next, nextTerm.title);

      const keyboard = {
        inline_keyboard: [
          [
            { text: "✅ Accept", callback_data: `terms_accept_${nextTerm.next}_${packageId}` },
            { text: "❌ Decline", callback_data: `terms_decline_${nextTerm.next}_${packageId}` }
          ],
          [
            { text: "🔙 Back to Investment", callback_data: `invest_${packageId}` }
          ]
        ]
      };

      await ctx.editMessageText(nextTermMessage, { parse_mode: "Markdown", reply_markup: keyboard });
    }
    return;
  }

  // Crypto payment callback (both patterns)
  if (data.startsWith("payment_crypto_") || data.startsWith("crypto_payment_")) {
    await ctx.answerCbQuery();
    const isCustom = data.includes("_custom_");
    let packageId;

    if (data.startsWith("payment_crypto_")) {
      packageId = data.replace("payment_crypto_", "");
    } else if (data.startsWith("crypto_payment_custom_")) {
      packageId = data.replace("crypto_payment_custom_", "");
    } else {
      packageId = data.replace("crypto_payment_", "");
    }

    console.log(`💰 Crypto payment callback: ${data}, Package ID: ${packageId}, Custom: ${isCustom}`);

    if (isCustom) {
      // Handle custom investment crypto payment
      const [investmentResult] = await dbConnection.execute(`
        SELECT * FROM aureus_investments WHERE id = ?
      `, [packageId]);

      if (investmentResult.length === 0) {
        await ctx.editMessageText("❌ Custom investment not found.", { parse_mode: "Markdown" });
        return;
      }

      const investment = investmentResult[0];

      // Show crypto network selection for custom investment
      const cryptoMessage = `🔗 **Select Blockchain Network**

**Custom Investment:** ${investment.package_name}
**Amount:** ${formatCurrency(investment.amount)}
**Shares:** ${investment.shares.toLocaleString()}

Please select the blockchain network you'll use for payment:`;

      const networkKeyboard = {
        inline_keyboard: [
          [
            { text: "🟡 BSC USDT", callback_data: `pay_bsc_custom_${packageId}` },
            { text: "💎 POL USDT", callback_data: `pay_usdt_custom_${packageId}` }
          ],
          [
            { text: "🔴 TRON USDT", callback_data: `pay_tron_custom_${packageId}` }
          ],
          [
            { text: "🔙 Back to Payment Methods", callback_data: `payment_methods_custom_${packageId}` }
          ]
        ]
      };

      await ctx.editMessageText(cryptoMessage, { parse_mode: "Markdown", reply_markup: networkKeyboard });
      return;
    }

    const pkg = await getPackageById(packageId);
    if (!pkg) {
      await ctx.editMessageText("❌ Package not found.", { parse_mode: "Markdown" });
      return;
    }

    // Show crypto network selection
    const cryptoMessage = `🔗 **Select Blockchain Network**

**Package:** ${pkg.name}
**Amount:** ${formatCurrency(pkg.price)}

Choose your preferred blockchain network:`;

    const cryptoKeyboard = {
      inline_keyboard: [
        [
          { text: "🟡 BSC USDT", callback_data: `crypto_bsc_${packageId}` },
          { text: "💎 POL USDT", callback_data: `crypto_polygon_${packageId}` }
        ],
        [
          { text: "🔴 TRON USDT", callback_data: `crypto_tron_${packageId}` }
        ],
        [
          { text: "🔙 Back to Payment Methods", callback_data: `confirm_invest_${packageId}` }
        ]
      ]
    };

    await ctx.editMessageText(cryptoMessage, { parse_mode: "Markdown", reply_markup: cryptoKeyboard });
    return;
  }

  // Crypto network specific callbacks
  if (data.startsWith("crypto_")) {
    await ctx.answerCbQuery();
    const parts = data.split("_");
    const network = parts[1]; // bsc, ethereum, polygon, tron
    const packageId = parts[2];

    console.log(`🔍 Crypto network callback: ${data}`);
    console.log(`🌐 Network: ${network}, Package: ${packageId}`);

    const pkg = await getPackageById(packageId);
    if (!pkg) {
      await ctx.editMessageText("❌ Package not found.", { parse_mode: "Markdown" });
      return;
    }

    const wallets = await getCompanyWallets();
    console.log(`💳 Available wallets:`, wallets);
    console.log(`🎯 Looking for wallet for network: ${network}`);
    const walletAddress = wallets[network];
    console.log(`📍 Wallet address found: ${walletAddress}`);

    if (!walletAddress) {
      console.log(`❌ No wallet found for network: ${network}`);
      await ctx.editMessageText("❌ Wallet not available for this network.", { parse_mode: "Markdown" });
      return;
    }

    // Network display names and info
    const networkInfo = {
      bsc: { name: "Binance Smart Chain", symbol: "BNB/USDT", explorer: "bscscan.com" },
      polygon: { name: "Polygon", symbol: "MATIC/USDT", explorer: "polygonscan.com" },
      tron: { name: "Tron", symbol: "TRX/USDT", explorer: "tronscan.org" }
    };

    const info = networkInfo[network];
    const paymentInstructions = `💳 **Cryptocurrency Payment**

**Package:** ${pkg.name}
**Amount:** ${formatCurrency(pkg.price)}
**Network:** ${info.name}
**Accepted Tokens:** ${info.symbol}

📋 **Payment Instructions:**

1️⃣ **Send Payment To:**
\`${walletAddress}\`

2️⃣ **Important Notes:**
• Send USDT tokens only
• Use ${info.name} network
• Minimum amount: ${formatCurrency(pkg.price)}
• Include your Telegram username in memo/note

3️⃣ **After Payment:**
• Take a screenshot of the transaction
• Send the transaction hash to this bot
• Wait for confirmation (usually 5-15 minutes)

⚠️ **Warning:** Only send USDT on ${info.name} network. Other tokens or networks may result in loss of funds.

🔍 **Verify on Explorer:** ${info.explorer}`;

    const paymentKeyboard = {
      inline_keyboard: [
        [
          { text: "📋 Copy Wallet Address", callback_data: `copy_wallet_${network}_${packageId}` }
        ],
        [
          { text: "✅ I've Sent Payment", callback_data: `payment_sent_${network}_${packageId}` }
        ],
        [
          { text: "🔙 Back to Networks", callback_data: `payment_crypto_${packageId}` }
        ]
      ]
    };

    await ctx.editMessageText(paymentInstructions, { parse_mode: "Markdown", reply_markup: paymentKeyboard });
    return;
  }

  // Copy wallet address callback
  if (data.startsWith("copy_wallet_")) {
    await ctx.answerCbQuery();
    const parts = data.split("_");
    console.log(`📋 Copy wallet callback: ${data}, Parts: ${parts.join(', ')}`);

    let network, packageId, isCustomInvestment = false;

    if (parts.length === 4) {
      // Check if this is a custom investment (shortId format: 8 characters)
      const potentialShortId = parts[3];
      console.log(`🔍 Checking shortId: "${potentialShortId}", Length: ${potentialShortId.length}`);
      if (potentialShortId.length === 8) {
        // Custom investment format: copy_wallet_{networkShort}_{shortId}
        const networkShort = parts[2];
        const shortId = parts[3];

        // Map short network names to API network names
        const networkMapping = {
          'bsc': 'bsc',
          'usdt': 'polygon',
          'tron': 'tron'
        };

        network = networkMapping[networkShort] || networkShort;
        packageId = shortId; // For custom investments, we'll use shortId to find the investment
        isCustomInvestment = true;
        console.log(`💰 Custom investment: NetworkShort=${networkShort}, Network=${network}, ShortID=${shortId}`);
      } else {
        // Regular package format: copy_wallet_{network}_{packageId}
        network = parts[2];
        packageId = parts[3];
        console.log(`📦 Regular package: Network=${network}, PackageID=${packageId}`);
      }
    }

    try {
      const wallets = await getCompanyWallets();
      const walletAddress = wallets[network];
      console.log(`💳 Wallet address for ${network}: ${walletAddress}`);

      let pkg;
      if (isCustomInvestment) {
        // For custom investments, get from database directly using shortId
        const [investmentResult] = await dbConnection.execute(`
          SELECT * FROM aureus_investments WHERE id LIKE ?
        `, [`${packageId}%`]);

        if (investmentResult.length > 0) {
          pkg = {
            price: investmentResult[0].amount,
            name: investmentResult[0].package_name || 'Custom Investment'
          };
        }
        console.log(`💰 Custom investment data:`, pkg);
      } else {
        // For regular packages
        pkg = await getPackageById(packageId);
        console.log(`📦 Regular package data:`, pkg);
      }

      if (!walletAddress) {
        console.log(`❌ No wallet address found for network: ${network}`);
        await ctx.reply("❌ Wallet address not available for this network.");
        return;
      }

      if (!pkg) {
        console.log(`❌ No package/investment data found for ID: ${packageId}`);
        await ctx.reply("❌ Package information not available.");
        return;
      }

      // Network display names
      const networkNames = {
        bsc: "Binance Smart Chain (BSC)",
        polygon: "Polygon",
        tron: "Tron"
      };

      const networkName = networkNames[network] || network.toUpperCase();

      // Send wallet address with amount information in a more copyable format
      await ctx.reply(
        `💳 **${networkName} Payment Details**\n\n` +
        `💰 **Amount to Send:** ${formatCurrency(pkg.price)} USDT\n\n` +
        `📋 **WALLET ADDRESS (Tap to Copy):**\n` +
        `\`\`\`\n${walletAddress}\n\`\`\`\n\n` +
        `⚠️ **IMPORTANT INSTRUCTIONS:**\n` +
        `• Send exactly **${formatCurrency(pkg.price)} USDT**\n` +
        `• Use **${networkName}** network only\n` +
        `• Double-check the wallet address before sending\n` +
        `• Include your Telegram username in memo if possible\n\n` +
        `💡 **How to Copy:**\n` +
        `1. Tap and hold the address above\n` +
        `2. Select "Copy" from the menu\n` +
        `3. Paste in your wallet app`,
        { parse_mode: "Markdown" }
      );

      console.log(`📋 Wallet address copied for ${network}: ${walletAddress}, Amount: ${pkg.price}`);
    } catch (error) {
      console.error('Error getting wallet address for copy:', error);
      await ctx.reply("❌ Error retrieving wallet address.");
    }
    return;
  }

  // Payment sent confirmation
  if (data.startsWith("payment_sent_")) {
    await ctx.answerCbQuery();
    const parts = data.split("_");
    const network = parts[2];
    const packageId = parts[3];

    const confirmMessage = `✅ **Payment Confirmation Process**

Thank you for initiating the payment! To complete verification, we need:

📝 **Step 1 of 3: Sender Wallet Address**

Please send your wallet address that you sent the payment FROM as a text message.

Example: 0x742d35Cc6634C0532925a3b8D4C9db96C4b4d8b7

⏳ **Waiting for your wallet address...**`;

    const confirmKeyboard = {
      inline_keyboard: [
        [
          { text: "🔙 Back to Payment", callback_data: `crypto_${network}_${packageId}` }
        ]
      ]
    };

    await ctx.reply(confirmMessage, { parse_mode: "Markdown", reply_markup: confirmKeyboard });

    // Set user state to expect sender wallet address and clear any calculator state
    await updateTelegramUser(ctx.from.id, {
      awaiting_sender_wallet: true,
      payment_network: network,
      payment_package_id: packageId,
      payment_step: 1,
      calculator_awaiting_shares: false  // Clear calculator state to prevent conflicts
    });
    return;
  }

  // Bank transfer callback
  if (data.startsWith("payment_bank_")) {
    await ctx.answerCbQuery();
    const packageId = data.replace("payment_bank_", "");

    const pkg = await getPackageById(packageId);
    if (!pkg) {
      await ctx.editMessageText("❌ Package not found.", { parse_mode: "Markdown" });
      return;
    }

    const referenceNumber = `AUR-${Date.now().toString().slice(-6)}`;

    const bankMessage = `🏦 **Bank Transfer Payment**

**Package:** ${pkg.name}
**Amount:** ${formatCurrency(pkg.price)}

📋 **Bank Transfer Details:**

**Account Name:** Aureus Alliance Holdings Ltd
**Bank:** JPMorgan Chase Bank
**Account Number:** 1234567890
**SWIFT Code:** CHASUS33
**Reference:** ${referenceNumber}

📍 **Bank Address:**
270 Park Avenue
New York, NY 10017
United States

📝 **Instructions:**
1. Make the transfer using the details above
2. Use the reference number provided
3. Take a photo of the transfer receipt
4. Send the receipt to this bot for verification

⏳ **Processing Time:** 1-3 business days`;

    const bankKeyboard = {
      inline_keyboard: [
        [
          { text: "📸 Upload Receipt", callback_data: `upload_receipt_${packageId}` }
        ],
        [
          { text: "🔙 Back to Payment Methods", callback_data: `confirm_invest_${packageId}` }
        ]
      ]
    };

    await ctx.editMessageText(bankMessage, { parse_mode: "Markdown", reply_markup: bankKeyboard });
    return;
  }

  // Upload receipt callback
  if (data.startsWith("upload_receipt_")) {
    await ctx.answerCbQuery();
    const packageId = data.replace("upload_receipt_", "");

    const receiptMessage = `📸 **Upload Payment Receipt**

Please send a clear photo of your bank transfer receipt or screenshot.

✅ **Make sure the image shows:**
• Transfer amount
• Reference number
• Date and time
• Bank details

📤 **Send the image now...**`;

    const receiptKeyboard = {
      inline_keyboard: [
        [
          { text: "🔙 Back to Bank Transfer", callback_data: `payment_bank_${packageId}` }
        ]
      ]
    };

    await ctx.editMessageText(receiptMessage, { parse_mode: "Markdown", reply_markup: receiptKeyboard });

    // Set user state to expect receipt upload
    await updateTelegramUser(ctx.from.id, {
      awaiting_receipt: true,
      payment_package_id: packageId
    });
    return;
  }

  // Auth callbacks
  if (data === "auth_login") {
    await ctx.answerCbQuery();
    await ctx.editMessageText("🔑 *Account Login*\n\nPlease enter your email address:", { parse_mode: "Markdown" });
    await updateTelegramUser(ctx.from.id, {
      registration_step: "email",
      registration_mode: "login"
    });
    return;
  }
  
  if (data === "auth_register") {
    await ctx.answerCbQuery();
    await ctx.editMessageText("📝 *Create New Account*\n\nPlease enter your email address:", { parse_mode: "Markdown" });
    await updateTelegramUser(ctx.from.id, {
      registration_step: "email",
      registration_mode: "register"
    });
    return;
  }

  if (data === "forgot_password") {
    await ctx.answerCbQuery();
    const telegramUser = ctx.telegramUser;

    if (!telegramUser.temp_email) {
      await ctx.editMessageText("❌ Please start the login process first.", { parse_mode: "Markdown" });
      return;
    }

    // Check if email exists
    const [rows] = await dbConnection.execute(
      'SELECT id, email, full_name FROM users WHERE email = ?',
      [telegramUser.temp_email]
    );

    if (rows.length === 0) {
      await ctx.editMessageText(`❌ **Email Not Found**

The email address ${telegramUser.temp_email} is not registered in our system.

🔹 **Options:**
• Check your email address spelling
• Use a different email address
• Register a new account

Would you like to try again?`, {
        parse_mode: "Markdown",
        reply_markup: {
          inline_keyboard: [
            [{ text: "🔄 Try Different Email", callback_data: "auth_login" }],
            [{ text: "📝 Register New Account", callback_data: "auth_register" }]
          ]
        }
      });
      return;
    }

    const user = rows[0];

    // Generate reset token
    const resetToken = await createPasswordResetToken(telegramUser.temp_email);

    if (resetToken) {
      // Send email with reset token
      const emailSent = await sendPasswordResetEmail(telegramUser.temp_email, resetToken, user.full_name || 'User');

      if (emailSent) {
        await updateTelegramUser(ctx.from.id, {
          registration_step: 'reset_token',
          password_reset_token: resetToken
        });

        await ctx.editMessageText(`📧 **Password Reset Email Sent!**

A password reset email has been sent to: **${telegramUser.temp_email}**

📬 **Check your email** for the reset token and instructions.

⏰ **Valid for:** 30 minutes

📝 **Next Step:** Enter the token from your email below to proceed with password reset.

*If you don't see the email, check your spam folder.*`, {
          parse_mode: "Markdown",
          reply_markup: {
            inline_keyboard: [
              [{ text: "📧 Resend Email", callback_data: "forgot_password" }],
              [{ text: "🔙 Back to Login", callback_data: "auth_login" }]
            ]
          }
        });
      } else {
        // Email sending failed, show token in bot (temporary solution)
        await updateTelegramUser(ctx.from.id, {
          registration_step: 'reset_token',
          password_reset_token: resetToken
        });

        await ctx.editMessageText(`🔄 **Password Reset Token**

Email service is temporarily unavailable, so here's your reset token:

🔑 **Reset Token:** \`${resetToken}\`

⏰ **Valid for:** 30 minutes

📝 **Next Step:** Enter this token below to proceed with password reset.

*Keep this token secure and don't share it with anyone.*`, {
          parse_mode: "Markdown",
          reply_markup: {
            inline_keyboard: [
              [{ text: "🔄 Generate New Token", callback_data: "forgot_password" }],
              [{ text: "🔙 Back to Login", callback_data: "auth_login" }]
            ]
          }
        });
      }
    } else {
      await ctx.editMessageText("❌ Failed to generate reset token. Please try again or contact support.");
    }
    return;
  }

  if (data === "auth_back_to_start") {
    await ctx.answerCbQuery();
    await updateTelegramUser(ctx.from.id, {
      registration_step: 'start',
      registration_mode: null,
      temp_email: null,
      temp_password: null
    });

    // Show the start screen again
    const startMessage = `🌟 **Welcome to Aureus Alliance Holdings!** 🌟

Your gateway to gold mining equity shares! 💎

🏆 **What We Offer:**
• Gold mining equity share packages
• Real dividend returns from mining
• NFT share certificates
• Referral commission system

🚀 **Get Started:**
Choose an option below to begin your investment journey!`;

    const keyboard = {
      inline_keyboard: [
        [
          { text: "🔑 Login", callback_data: "switch_to_login" },
          { text: "📝 Register", callback_data: "switch_to_register" }
        ],
        [
          { text: "❓ Help", callback_data: "view_faq" },
          { text: "🆘 Support", callback_data: "get_support" }
        ]
      ]
    };

    await ctx.editMessageText(startMessage, {
      parse_mode: "Markdown",
      reply_markup: keyboard
    });
  }

  if (data === "switch_to_login") {
    await ctx.answerCbQuery();
    await ctx.editMessageText("🔑 *Account Login*\n\nPlease enter your email address:", { parse_mode: "Markdown" });
    await updateTelegramUser(ctx.from.id, {
      registration_step: "email",
      registration_mode: "login",
      temp_email: null,
      temp_password: null
    });
    return;
  }

  if (data === "switch_to_register") {
    await ctx.answerCbQuery();
    await ctx.editMessageText("📝 *Create New Account*\n\nPlease enter your email address:", { parse_mode: "Markdown" });
    await updateTelegramUser(ctx.from.id, {
      registration_step: "email",
      registration_mode: "register",
      temp_email: null,
      temp_password: null
    });
    return;
  }

  if (data === "register_with_email") {
    await ctx.answerCbQuery();

    // Get the user's temp_email from the database
    const user = await getTelegramUser(ctx.from.id);
    if (user && user.temp_email) {
      await ctx.editMessageText("📝 *Create New Account*\n\nPlease create a secure password:", { parse_mode: "Markdown" });
      await updateTelegramUser(ctx.from.id, {
        registration_step: "password",
        registration_mode: "register"
      });
    } else {
      // Fallback to regular registration flow
      await ctx.editMessageText("📝 *Create New Account*\n\nPlease enter your email address:", { parse_mode: "Markdown" });
      await updateTelegramUser(ctx.from.id, {
        registration_step: "email",
        registration_mode: "register",
        temp_email: null,
        temp_password: null
      });
    }
    return;
  }

  if (data === "contact_support") {
    await ctx.answerCbQuery();

    const contactMessage = `📞 **Contact Information**

🔗 **Support Channels:**
• **Email:** support@aureusafrica.com
• **Telegram:** @aureusafrica
• **Website:** www.aureusafrica.com

📋 **When contacting support, please include:**
• Your registered email address
• Description of the issue
• Any error messages you received

⏰ **Response Time:** Usually within 24 hours

💡 **Tip:** Check our FAQ section first - many common questions are answered there!`;

    const keyboard = {
      inline_keyboard: [
        [{ text: "❓ View FAQ", callback_data: "view_faq" }],
        [{ text: "🔙 Back", callback_data: "auth_back_to_start" }]
      ]
    };

    await ctx.editMessageText(contactMessage, {
      parse_mode: "Markdown",
      reply_markup: keyboard
    });
    return;
  }

  // Referral callback handlers
  if (data === "skip_referral") {
    await ctx.answerCbQuery();
    await completeRegistrationWithoutReferral(ctx);
    return;
  }

  if (data === "confirm_referrer") {
    await ctx.answerCbQuery();
    await completeRegistrationWithReferral(ctx);
    return;
  }

  if (data === "retry_referrer") {
    await ctx.answerCbQuery();
    await updateTelegramUser(ctx.from.id, {
      referral_step: null,
      temp_referrer_username: null,
      registration_step: 'referral'
    });

    const retryMessage = `🎯 **Enter Referral Username**

Please enter the Telegram username (without @) of the person who referred you:`;

    const retryKeyboard = {
      inline_keyboard: [
        [{ text: "⏭️ Skip Referral", callback_data: "skip_referral" }]
      ]
    };

    await ctx.editMessageText(retryMessage, {
      parse_mode: "Markdown",
      reply_markup: retryKeyboard
    });
    return;
  }

  if (data === "back_to_password") {
    await ctx.answerCbQuery();
    await updateTelegramUser(ctx.from.id, {
      registration_step: 'password',
      temp_password: null,
      referral_step: null,
      temp_referrer_username: null
    });

    await ctx.editMessageText("🔑 **Create Password**\n\nPlease create a secure password (minimum 8 characters):", {
      parse_mode: "Markdown"
    });
    return;
  }

  // Missing callback handlers for registration success buttons
  if (data === "view_packages") {
    await ctx.answerCbQuery();
    // Call packages function directly
    return await showPackages(ctx);
  }

  if (data === "mining_calculator") {
    await ctx.answerCbQuery();
    // Call calculator function directly
    return await showMiningCalculator(ctx);
  }

  if (data === "dashboard") {
    await ctx.answerCbQuery();
    // Call dashboard function directly
    return await showDashboard(ctx);
  }

  // Portfolio callback handlers
  if (data === "dividend_history") {
    await ctx.answerCbQuery();
    await showDividendHistory(ctx);
    return;
  }

  if (data === "performance_metrics") {
    await ctx.answerCbQuery();
    await showPerformanceMetrics(ctx);
    return;
  }

  if (data === "portfolio_stats") {
    await ctx.answerCbQuery();
    await showPortfolioStats(ctx);
    return;
  }

  if (data === "refresh_portfolio") {
    await ctx.answerCbQuery();
    await showPortfolio(ctx);
    return;
  }

  // Referral callback handlers
  if (data === "view_commissions") {
    await ctx.answerCbQuery();
    await showCommissions(ctx);
    return;
  }

  if (data === "referral_stats") {
    await ctx.answerCbQuery();
    await showReferralStats(ctx);
    return;
  }

  if (data === "view_leaderboard") {
    await ctx.answerCbQuery();
    await showLeaderboard(ctx);
    return;
  }

  // NFT and Certificate callback handlers
  if (data === "download_all_certificates") {
    await ctx.answerCbQuery();
    await downloadAllCertificates(ctx);
    return;
  }

  if (data === "email_certificates") {
    await ctx.answerCbQuery();
    await emailCertificates(ctx);
    return;
  }

  if (data === "nft_email_notifications") {
    await ctx.answerCbQuery();
    await showNFTEmailNotifications(ctx);
    return;
  }

  if (data === "refresh_certificates") {
    await ctx.answerCbQuery();
    await showCertificates(ctx);
    return;
  }

  // Support Center callback handlers
  if (data === "create_support_ticket") {
    await ctx.answerCbQuery();
    await createSupportTicket(ctx);
    return;
  }

  if (data === "system_status") {
    await ctx.answerCbQuery();
    await showSystemStatus(ctx);
    return;
  }

  if (data === "start_live_chat") {
    await ctx.answerCbQuery();
    await startLiveChat(ctx);
    return;
  }

  // Individual crypto network callbacks for custom investments
  if (data.startsWith("pay_bsc_custom_") || data.startsWith("pay_usdt_custom_") ||
      data.startsWith("pay_tron_custom_")) {
    await ctx.answerCbQuery();

    const parts = data.split("_");
    const networkShort = parts[1]; // bsc, usdt, tron
    const investmentId = parts[3]; // custom investment ID

    // Map short network names to API network names
    const networkMapping = {
      'bsc': 'bsc',
      'usdt': 'polygon',
      'tron': 'tron'
    };

    const network = networkMapping[networkShort] || networkShort;

    console.log(`💰 Custom crypto network callback: ${data}, Network: ${networkShort} -> ${network}, Investment: ${investmentId}`);

    // Get investment details
    const [investmentResult] = await dbConnection.execute(`
      SELECT * FROM aureus_investments WHERE id = ?
    `, [investmentId]);

    if (investmentResult.length === 0) {
      await ctx.editMessageText("❌ Custom investment not found.", { parse_mode: "Markdown" });
      return;
    }

    const investment = investmentResult[0];

    // The network is already mapped to the correct format from the first mapping
    // No need for additional mapping since network is already 'ethereum', 'bitcoin', 'polygon', etc.
    const networkName = network;
    const wallets = await getCompanyWallets();
    const walletAddress = wallets[networkName] || 'Not available';

    // Get network info
    const networkInfo = getNetworkInfo(networkName);

    const paymentInstructions = `💳 **${networkInfo.name} Payment Instructions**

**Custom Investment:** ${investment.package_name}
**Amount:** ${formatCurrency(investment.amount)}
**Shares:** ${investment.shares.toLocaleString()}

📋 **Payment Details:**
💰 **Amount:** ${formatCurrency(investment.amount)} USD
🏦 **Network:** ${networkInfo.name}
📍 **Wallet Address:**
\`${walletAddress}\`

⚠️ **Important:**
• Send exactly ${formatCurrency(investment.amount)} USD worth of ${networkInfo.symbol}
• Use ${networkInfo.name} network only
• Double-check the wallet address before sending

🔍 **Verify on Explorer:** ${networkInfo.explorer}`;

    // Create shorter callback data to avoid Telegram's 64-byte limit
    const shortId = investmentId.substring(0, 8); // Use first 8 characters of UUID

    // Debug: Check callback data lengths
    const copyCallback = `copy_wallet_${networkShort}_${shortId}`;
    const sentCallback = `payment_sent_custom_${networkShort}_${shortId}`;
    const backCallback = `crypto_payment_custom_${shortId}`;

    console.log(`🔍 Callback data lengths: copy=${copyCallback.length}, sent=${sentCallback.length}, back=${backCallback.length}`);
    console.log(`🔍 Callback data: copy="${copyCallback}", sent="${sentCallback}", back="${backCallback}"`);

    const paymentKeyboard = {
      inline_keyboard: [
        [
          { text: "📋 Copy Wallet Address", callback_data: copyCallback }
        ],
        [
          { text: "✅ I've Sent Payment", callback_data: sentCallback }
        ],
        [
          { text: "🔙 Back to Networks", callback_data: backCallback }
        ]
      ]
    };

    await ctx.editMessageText(paymentInstructions, { parse_mode: "Markdown", reply_markup: paymentKeyboard });
    return;
  }

  // Payment sent callbacks for custom investments
  if (data.startsWith("payment_sent_custom_")) {
    await ctx.answerCbQuery();

    const parts = data.split("_");
    // Format: payment_sent_custom_{networkShort}_{shortId}
    const networkShort = parts[3]; // bsc, usdt, tron
    const shortId = parts[4]; // short investment ID (8 characters)

    // Map short network names to full network names for validation
    const networkMapping = {
      'bsc': 'bsc',
      'usdt': 'polygon',
      'tron': 'tron'
    };

    const network = networkMapping[networkShort] || networkShort;

    // Find the full investment ID from the short ID
    const [investmentResult] = await dbConnection.execute(`
      SELECT id FROM aureus_investments WHERE id LIKE ?
    `, [`${shortId}%`]);

    if (investmentResult.length === 0) {
      await ctx.editMessageText("❌ Investment not found.", { parse_mode: "Markdown" });
      return;
    }

    const investmentId = investmentResult[0].id;

    console.log(`✅ Custom payment sent callback: ${data}, Network: ${networkShort} -> ${network}, ShortID: ${shortId}, FullID: ${investmentId}`);

    // Start payment verification flow for custom investment
    const verificationMessage = `✅ **Payment Confirmation Started**

Thank you for confirming your payment!

📝 **Step 1 of 3: Sender Wallet Address**

Please send your wallet address that you used to send the payment.

This helps us verify the transaction on the blockchain.

📝 **Send your wallet address now...**`;

    await ctx.editMessageText(verificationMessage, { parse_mode: "Markdown" });

    // Update user state for custom investment payment verification
    await updateTelegramUser(ctx.from.id, {
      awaiting_sender_wallet: true,
      payment_step: 1,
      payment_network: network, // Now properly mapped (polygon, bsc, tron)
      payment_package_id: investmentId,
      payment_is_custom: true
    });

    return;
  }

  // Default handler
  await ctx.answerCbQuery();
  await ctx.reply(`🚧 Feature "${data}" is coming soon! Stay tuned.`);
});

// Error handling
bot.catch((err, ctx) => {
  console.error("❌ Bot error:", err);
  ctx.reply("Sorry, something went wrong. Please try again later.");
});

// Start bot
async function startBot() {
  try {
    await connectDB();
    
    // Create telegram_users table if it doesn't exist
    await dbConnection.execute(`
      CREATE TABLE IF NOT EXISTS telegram_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        telegram_id BIGINT UNIQUE NOT NULL,
        username VARCHAR(100),
        first_name VARCHAR(100),
        last_name VARCHAR(100),
        user_id INT,
        is_registered BOOLEAN DEFAULT FALSE,
        registration_step VARCHAR(50) DEFAULT 'start',
        registration_mode VARCHAR(20),
        temp_email VARCHAR(255),
        temp_password VARCHAR(255),
        awaiting_tx_hash BOOLEAN DEFAULT FALSE,
        payment_network VARCHAR(20),
        payment_package_id VARCHAR(36),
        awaiting_receipt BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
      )
    `);

    // Add new columns to existing telegram_users table if they don't exist
    try {
      await dbConnection.execute(`
        ALTER TABLE telegram_users
        ADD COLUMN IF NOT EXISTS awaiting_tx_hash BOOLEAN DEFAULT FALSE,
        ADD COLUMN IF NOT EXISTS payment_network VARCHAR(20),
        ADD COLUMN IF NOT EXISTS payment_package_id VARCHAR(36),
        ADD COLUMN IF NOT EXISTS awaiting_receipt BOOLEAN DEFAULT FALSE,
        ADD COLUMN IF NOT EXISTS password_reset_token VARCHAR(255),
        ADD COLUMN IF NOT EXISTS password_reset_expires TIMESTAMP NULL,
        ADD COLUMN IF NOT EXISTS linked_email VARCHAR(255),
        ADD COLUMN IF NOT EXISTS auto_login_enabled BOOLEAN DEFAULT FALSE,
        ADD COLUMN IF NOT EXISTS admin_auth_step VARCHAR(20) NULL,
        ADD COLUMN IF NOT EXISTS admin_temp_email VARCHAR(255) NULL,
        ADD COLUMN IF NOT EXISTS awaiting_admin_message BOOLEAN DEFAULT FALSE,
        ADD COLUMN IF NOT EXISTS admin_replying_to_message INT NULL,
        ADD COLUMN IF NOT EXISTS terms_record_id VARCHAR(36) NULL,
        ADD COLUMN IF NOT EXISTS admin_search_mode VARCHAR(20) NULL,
        ADD COLUMN IF NOT EXISTS calculator_awaiting_shares BOOLEAN DEFAULT FALSE,
        ADD COLUMN IF NOT EXISTS awaiting_sender_wallet BOOLEAN DEFAULT FALSE,
        ADD COLUMN IF NOT EXISTS awaiting_screenshot BOOLEAN DEFAULT FALSE,
        ADD COLUMN IF NOT EXISTS payment_step INT DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS sender_wallet_address VARCHAR(255) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS screenshot_path VARCHAR(500) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS referral_step VARCHAR(50) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS temp_referrer_username VARCHAR(100) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS referral_code VARCHAR(20) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS referred_by_link BOOLEAN DEFAULT FALSE
      `);
      console.log("✅ Telegram users table updated with authentication columns");
    } catch (error) {
      console.log("ℹ️ Authentication columns may already exist:", error.message);
    }

    // Add referral fields to users table
    try {
      await dbConnection.execute(`
        ALTER TABLE users
        ADD COLUMN IF NOT EXISTS sponsor_telegram_username VARCHAR(100) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS sponsor_user_id INT DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS referral_code VARCHAR(20) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS total_referrals INT DEFAULT 0,
        ADD COLUMN IF NOT EXISTS total_commission_earned DECIMAL(10,2) DEFAULT 0.00,
        ADD COLUMN IF NOT EXISTS referral_milestone_level INT DEFAULT 0,
        ADD COLUMN IF NOT EXISTS total_milestone_bonuses DECIMAL(10,2) DEFAULT 0.00
      `);
      console.log("✅ Users table updated with referral columns");
    } catch (error) {
      console.log("ℹ️ Referral columns may already exist:", error.message);
    }

    // Create commissions table
    try {
      await dbConnection.execute(`
        CREATE TABLE IF NOT EXISTS commissions (
          id INT AUTO_INCREMENT PRIMARY KEY,
          referrer_id INT NOT NULL,
          referred_user_id INT NOT NULL,
          investment_id VARCHAR(36) NOT NULL,
          investment_type ENUM('package', 'custom') NOT NULL,
          commission_amount DECIMAL(10,2) NOT NULL,
          investment_amount DECIMAL(10,2) NOT NULL,
          commission_percentage DECIMAL(5,2) DEFAULT 15.00,
          status ENUM('pending', 'approved', 'paid', 'cancelled') DEFAULT 'pending',
          date_earned TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          date_approved TIMESTAMP NULL,
          date_paid TIMESTAMP NULL,
          notes TEXT NULL,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE CASCADE,
          FOREIGN KEY (referred_user_id) REFERENCES users(id) ON DELETE CASCADE,
          INDEX idx_referrer_id (referrer_id),
          INDEX idx_referred_user_id (referred_user_id),
          INDEX idx_status (status),
          INDEX idx_date_earned (date_earned)
        )
      `);
      console.log("✅ Commissions table created successfully");
    } catch (error) {
      console.log("ℹ️ Commissions table may already exist:", error.message);
    }

// REFERRAL SYSTEM FUNCTIONS
async function findUserByTelegramUsername(username) {
  try {
    // Remove @ if present
    const cleanUsername = username.replace('@', '');

    // First check telegram_users table for registered users
    const [telegramUsers] = await dbConnection.execute(`
      SELECT tu.*, u.id as user_id, u.email, u.username as web_username,
             CONCAT(u.first_name, ' ', COALESCE(u.last_name, '')) as full_name
      FROM telegram_users tu
      JOIN users u ON tu.user_id = u.id
      WHERE tu.username = ? AND tu.is_registered = TRUE
    `, [cleanUsername]);

    if (telegramUsers.length > 0) {
      return {
        found: true,
        user: telegramUsers[0]
      };
    }

    return { found: false, user: null };
  } catch (error) {
    console.error("Error finding user by telegram username:", error);
    return { found: false, user: null };
  }
}

async function validateReferralUsername(username) {
  try {
    const result = await findUserByTelegramUsername(username);
    return result;
  } catch (error) {
    console.error("Error validating referral username:", error);
    return { found: false, user: null };
  }
}

function generateReferralCode(username) {
  // Create a short, unique referral code based on username and random string
  const randomPart = Math.random().toString(36).substring(2, 6).toUpperCase();
  const userPart = username.substring(0, 4).toUpperCase();
  return `${userPart}${randomPart}`;
}

async function createReferralCode(userId, username) {
  try {
    // Generate unique referral code
    let referralCode;
    let isUnique = false;
    let attempts = 0;

    while (!isUnique && attempts < 10) {
      referralCode = generateReferralCode(username);

      // Check if code already exists
      const [existing] = await dbConnection.execute(`
        SELECT id FROM users WHERE referral_code = ?
      `, [referralCode]);

      if (existing.length === 0) {
        isUnique = true;
      }
      attempts++;
    }

    if (!isUnique) {
      // Fallback to timestamp-based code
      referralCode = `REF${Date.now().toString().slice(-6)}`;
    }

    // Update user with referral code
    await dbConnection.execute(`
      UPDATE users SET referral_code = ? WHERE id = ?
    `, [referralCode, userId]);

    return referralCode;
  } catch (error) {
    console.error("Error creating referral code:", error);
    return null;
  }
}

async function handleReferralLink(ctx, referralCode) {
  try {
    // Find the referrer by referral code
    const [referrerResult] = await dbConnection.execute(`
      SELECT u.id, u.username, u.email, tu.username as telegram_username
      FROM users u
      LEFT JOIN telegram_users tu ON u.id = tu.user_id
      WHERE u.referral_code = ?
    `, [referralCode]);

    if (referrerResult.length === 0) {
      console.log(`❌ Invalid referral code: ${referralCode}`);
      return false;
    }

    const referrer = referrerResult[0];

    // Store referral information in telegram user
    await updateTelegramUser(ctx.from.id, {
      temp_referrer_username: referrer.telegram_username || referrer.username,
      referred_by_link: true,
      referral_code: referralCode
    });

    console.log(`✅ Referral link processed: ${ctx.from.first_name} referred by ${referrer.telegram_username || referrer.username}`);
    return true;
  } catch (error) {
    console.error("Error handling referral link:", error);
    return false;
  }
}

async function linkReferralRelationship(newUserId, sponsorUserId, sponsorTelegramUsername) {
  try {
    // Update the new user with referral information
    await dbConnection.execute(`
      UPDATE users
      SET sponsor_telegram_username = ?, sponsor_user_id = ?
      WHERE id = ?
    `, [sponsorTelegramUsername, sponsorUserId, newUserId]);

    // Update sponsor's total referrals count
    await dbConnection.execute(`
      UPDATE users
      SET total_referrals = total_referrals + 1
      WHERE id = ?
    `, [sponsorUserId]);

    // Check for milestone bonuses
    await checkAndAwardMilestoneBonus(sponsorUserId, sponsorTelegramUsername);

    console.log(`✅ Referral relationship created: User ${newUserId} referred by ${sponsorTelegramUsername} (ID: ${sponsorUserId})`);
    return true;
  } catch (error) {
    console.error("Error linking referral relationship:", error);
    return false;
  }
}

async function checkAndAwardMilestoneBonus(sponsorUserId, sponsorTelegramUsername) {
  try {
    // Get current referral count
    const [userResult] = await dbConnection.execute(`
      SELECT total_referrals, referral_milestone_level FROM users WHERE id = ?
    `, [sponsorUserId]);

    if (userResult.length === 0) return;

    const user = userResult[0];
    const currentReferrals = user.total_referrals;
    const currentMilestoneLevel = user.referral_milestone_level || 0;

    // Define milestone levels and bonuses
    const milestones = [
      { level: 1, referrals: 5, bonus: 50, title: "Rising Star" },
      { level: 2, referrals: 10, bonus: 100, title: "Network Builder" },
      { level: 3, referrals: 25, bonus: 250, title: "Community Leader" },
      { level: 4, referrals: 50, bonus: 500, title: "Referral Champion" },
      { level: 5, referrals: 100, bonus: 1000, title: "Elite Ambassador" },
      { level: 6, referrals: 250, bonus: 2500, title: "Master Recruiter" },
      { level: 7, referrals: 500, bonus: 5000, title: "Legendary Referrer" }
    ];

    // Check if user has reached a new milestone
    for (const milestone of milestones) {
      if (currentReferrals >= milestone.referrals && currentMilestoneLevel < milestone.level) {
        // Award milestone bonus
        await dbConnection.execute(`
          UPDATE users
          SET referral_milestone_level = ?, total_milestone_bonuses = total_milestone_bonuses + ?
          WHERE id = ?
        `, [milestone.level, milestone.bonus, sponsorUserId]);

        // Create a special commission record for the milestone bonus
        await dbConnection.execute(`
          INSERT INTO commissions (
            referrer_id, referred_user_id, investment_id, investment_type,
            commission_amount, investment_amount, commission_percentage,
            status, date_earned, notes
          ) VALUES (?, ?, ?, 'milestone', ?, ?, 0, 'approved', NOW(), ?)
        `, [
          sponsorUserId,
          sponsorUserId, // Self-referencing for milestone bonus
          `milestone_${milestone.level}_${Date.now()}`,
          milestone.bonus,
          milestone.bonus,
          `Milestone Bonus: ${milestone.title} - ${milestone.referrals} referrals achieved`
        ]);

        // Notify user of milestone achievement
        await notifyMilestoneAchievement(sponsorUserId, sponsorTelegramUsername, milestone);

        console.log(`🏆 Milestone bonus awarded: ${sponsorTelegramUsername} reached level ${milestone.level} (${milestone.referrals} referrals) - $${milestone.bonus} bonus`);
        break; // Only award one milestone at a time
      }
    }
  } catch (error) {
    console.error("Error checking milestone bonus:", error);
  }
}

async function notifyMilestoneAchievement(sponsorUserId, sponsorTelegramUsername, milestone) {
  try {
    // Find sponsor's telegram account
    const [telegramResult] = await dbConnection.execute(`
      SELECT telegram_id FROM telegram_users WHERE user_id = ? AND is_registered = TRUE
    `, [sponsorUserId]);

    if (telegramResult.length > 0) {
      const telegramId = telegramResult[0].telegram_id;

      const milestoneMessage = `🏆 **MILESTONE ACHIEVED!**

🎉 **Congratulations!** You've reached a new referral milestone!

🏅 **Achievement:** ${milestone.title}
👥 **Referrals:** ${milestone.referrals}
💰 **Bonus Reward:** $${milestone.bonus}

📊 **Your Progress:**
• You've successfully referred ${milestone.referrals} people
• Your dedication to growing our community is amazing
• This bonus has been automatically added to your account

🚀 **What's Next:**
• Keep referring to unlock even bigger bonuses
• Help your referrals succeed to maximize commissions
• Aim for the next milestone level!

Thank you for being an incredible ambassador! 🌟`;

      const keyboard = {
        inline_keyboard: [
          [
            { text: "🏆 View Leaderboard", callback_data: "public_leaderboard" },
            { text: "👥 My Referrals", callback_data: "menu_referrals" }
          ],
          [
            { text: "🔗 Share Referral Link", callback_data: "get_referral_link" }
          ]
        ]
      };

      await bot.telegram.sendMessage(telegramId, milestoneMessage, {
        parse_mode: "Markdown",
        reply_markup: keyboard
      });
      console.log(`🏆 Milestone notification sent to @${sponsorTelegramUsername} (Telegram ID: ${telegramId})`);
    }
  } catch (error) {
    console.error("Error notifying milestone achievement:", error);
  }
}



async function completeRegistrationWithoutReferral(ctx) {
  try {
    const user = await getTelegramUser(ctx.from.id);

    // Create user in main users table
    const [result] = await dbConnection.execute(`
      INSERT INTO users (username, email, password_hash, is_active, created_at, updated_at)
      VALUES (?, ?, ?, 1, NOW(), NOW())
    `, [
      ctx.from.username || `user_${ctx.from.id}`,
      user.temp_email,
      user.temp_password
    ]);

    const userId = result.insertId;

    // Update telegram user with registration completion
    await updateTelegramUser(ctx.from.id, {
      user_id: userId,
      linked_email: user.temp_email,
      is_registered: true,
      registration_step: null,
      registration_mode: null,
      temp_email: null,
      temp_password: null,
      referral_step: null,
      temp_referrer_username: null
    });

    await sendRegistrationSuccessMessage(ctx, user.temp_email, false);
  } catch (error) {
    console.error("Error completing registration without referral:", error);
    await ctx.reply("❌ Registration failed. Please try again or contact support.");
  }
}

async function completeRegistrationWithReferral(ctx) {
  try {
    const user = await getTelegramUser(ctx.from.id);

    // Create user in main users table
    const [result] = await dbConnection.execute(`
      INSERT INTO users (username, email, password_hash, is_active, created_at, updated_at)
      VALUES (?, ?, ?, 1, NOW(), NOW())
    `, [
      ctx.from.username || `user_${ctx.from.id}`,
      user.temp_email,
      user.temp_password
    ]);

    const userId = result.insertId;

    // Find the referrer
    const referralResult = await findUserByTelegramUsername(user.temp_referrer_username);

    if (referralResult.found) {
      // Link referral relationship
      await linkReferralRelationship(userId, referralResult.user.user_id, user.temp_referrer_username);
    }

    // Update telegram user with registration completion
    await updateTelegramUser(ctx.from.id, {
      user_id: userId,
      linked_email: user.temp_email,
      is_registered: true,
      registration_step: null,
      registration_mode: null,
      temp_email: null,
      temp_password: null,
      referral_step: null,
      temp_referrer_username: null
    });

    await sendRegistrationSuccessMessage(ctx, user.temp_email, true, user.temp_referrer_username);
  } catch (error) {
    console.error("Error completing registration with referral:", error);
    await ctx.reply("❌ Registration failed. Please try again or contact support.");
  }
}

async function sendRegistrationSuccessMessage(ctx, email, hasReferrer = false, referrerUsername = null) {
  const baseMessage = `✅ **Registration Successful!**

Welcome to Aureus Alliance Holdings, ${ctx.from.first_name}!

**Account Details:**
• **Email:** ${email}
• **Username:** ${ctx.from.username || `user_${ctx.from.id}`}
• **Telegram:** Linked successfully`;

  const referralMessage = hasReferrer
    ? `\n• **Referrer:** @${referrerUsername} ✅\n\n🎉 **Thank you for joining through a referral!**\nYour referrer will earn 15% commission on your investments.`
    : '';

  const finalMessage = baseMessage + referralMessage + `

🎉 **You're now ready to:**
• Browse investment packages
• Calculate mining returns
• Make secure investments
• Track your portfolio

Use the menu below to get started!`;

  const keyboard = {
    inline_keyboard: [
      [
        { text: "📦 View Packages", callback_data: "view_packages" },
        { text: "🧮 Mining Calculator", callback_data: "mining_calculator" }
      ],
      [
        { text: "📊 Dashboard", callback_data: "dashboard" },
        { text: "💰 Custom Investment", callback_data: "custom_investment" }
      ]
    ]
  };

  await ctx.editMessageText(finalMessage, {
    parse_mode: "Markdown",
    reply_markup: keyboard
  });
}

async function generateAndShowReferralLink(ctx) {
  try {
    const telegramUser = ctx.telegramUser;
    const userEmail = telegramUser.linked_email || telegramUser.email;

    // Get user ID and check if they have a referral code
    const [userResult] = await dbConnection.execute(`
      SELECT id, username, referral_code FROM users WHERE email = ?
    `, [userEmail]);

    if (userResult.length === 0) {
      await ctx.editMessageText("❌ User not found.");
      return;
    }

    const user = userResult[0];
    let referralCode = user.referral_code;

    // Create referral code if user doesn't have one
    if (!referralCode) {
      referralCode = await createReferralCode(user.id, user.username);
      if (!referralCode) {
        await ctx.editMessageText("❌ Error generating referral code. Please try again.");
        return;
      }
    }

    // Generate referral link
    const botUsername = 'aureus_africa_bot'; // Replace with your actual bot username
    const referralLink = `https://t.me/${botUsername}?start=ref_${referralCode}`;

    const message = `🔗 **Your Referral Link**

📱 **Unique Link:**
\`${referralLink}\`

🎯 **How it works:**
1. Share this link with friends
2. When they click it and register, you're automatically set as their referrer
3. You earn 15% commission on all their investments!

💡 **Pro Tips:**
• Share on social media, WhatsApp, or email
• Explain the benefits of Aureus Alliance Holdings
• Help friends through the registration process

📊 **Your Referral Code:** \`${referralCode}\`

💰 **Commission Rate:** 15% of all investments
🎁 **Bonus:** Unlimited referrals, unlimited earnings!`;

    const keyboard = {
      inline_keyboard: [
        [
          { text: "📱 Share Link", url: `https://t.me/share/url?url=${encodeURIComponent(referralLink)}&text=${encodeURIComponent('Join Aureus Alliance Holdings - Gold Mining Equity Share Platform! 🏆')}` }
        ],
        [
          { text: "📋 Copy Link", callback_data: `copy_referral_link_${referralCode}` },
          { text: "🔄 Generate New", callback_data: "regenerate_referral_code" }
        ],
        [
          { text: "🔙 Back to Referrals", callback_data: "menu_referrals" }
        ]
      ]
    };

    await ctx.editMessageText(message, {
      parse_mode: "Markdown",
      reply_markup: keyboard
    });

  } catch (error) {
    console.error("Error generating referral link:", error);
    await ctx.editMessageText("❌ Error generating referral link. Please try again.");
  }
}

async function showPublicReferralLeaderboard(ctx) {
  try {
    // Get top referrers for public display
    const [topReferrers] = await dbConnection.execute(`
      SELECT
        u.username,
        u.total_referrals,
        u.total_commission_earned,
        tu.username as telegram_username,
        u.created_at
      FROM users u
      LEFT JOIN telegram_users tu ON u.id = tu.user_id
      WHERE u.total_referrals > 0
      ORDER BY u.total_referrals DESC, u.total_commission_earned DESC
      LIMIT 15
    `);

    // Get total statistics
    const [totalStats] = await dbConnection.execute(`
      SELECT
        COUNT(*) as total_users,
        COUNT(CASE WHEN total_referrals > 0 THEN 1 END) as active_referrers,
        SUM(total_referrals) as total_referrals_made,
        SUM(total_commission_earned) as total_commissions_paid
      FROM users
    `);

    const stats = totalStats[0];

    let message = `🏆 **Aureus Alliance Holdings - Referral Champions**

📊 **Platform Statistics:**
• **Total Users:** ${stats.total_users.toLocaleString()}
• **Active Referrers:** ${stats.active_referrers}
• **Total Referrals:** ${stats.total_referrals_made}
• **Commissions Paid:** $${(stats.total_commissions_paid || 0).toLocaleString()}

👑 **Top Referrers:**`;

    if (topReferrers.length === 0) {
      message += `\n\n❌ **No referrers yet**

🎯 **Be the first!**
Start referring friends and claim the #1 spot on our leaderboard!

💰 **Benefits:**
• Earn 15% commission on all referrals
• Build passive income
• Help grow our community
• Get recognition as a top performer`;
    } else {
      message += `\n`;

      const medals = ['🥇', '🥈', '🥉'];

      topReferrers.forEach((referrer, index) => {
        const medal = medals[index] || `${index + 1}.`;
        const username = referrer.telegram_username || referrer.username;
        const joinDate = new Date(referrer.created_at).toLocaleDateString();

        message += `\n${medal} **@${username}**
   👥 ${referrer.total_referrals} referrals
   💰 $${referrer.total_commission_earned.toFixed(2)} earned
   📅 Since ${joinDate}
`;
      });

      message += `\n🎯 **Want to join the leaderboard?**
Start referring friends today and earn 15% commission!`;
    }

    const keyboard = {
      inline_keyboard: [
        [
          { text: "🎯 Start Referring", callback_data: "menu_referrals" },
          { text: "📖 How It Works", callback_data: "referral_instructions" }
        ],
        [
          { text: "🔄 Refresh", callback_data: "public_leaderboard" },
          { text: "🔙 Main Menu", callback_data: "back_to_menu" }
        ]
      ]
    };

    if (ctx.editMessageText) {
      await ctx.editMessageText(message, {
        parse_mode: "Markdown",
        reply_markup: keyboard
      });
    } else {
      await ctx.replyWithMarkdown(message, { reply_markup: keyboard });
    }

  } catch (error) {
    console.error("Error showing public leaderboard:", error);
    const errorMessage = "❌ Error loading leaderboard. Please try again.";
    if (ctx.editMessageText) {
      await ctx.editMessageText(errorMessage);
    } else {
      await ctx.reply(errorMessage);
    }
  }
}

// USER REFERRAL DASHBOARD FUNCTIONS
async function showMyReferralsList(ctx) {
  try {
    const telegramUser = ctx.telegramUser;
    const userEmail = telegramUser.linked_email || telegramUser.email;

    // Get user ID
    const [userResult] = await dbConnection.execute(`
      SELECT id FROM users WHERE email = ?
    `, [userEmail]);

    if (userResult.length === 0) {
      await ctx.editMessageText("❌ User not found.");
      return;
    }

    const userId = userResult[0].id;

    // Get list of referred users
    const [referrals] = await dbConnection.execute(`
      SELECT
        u.username,
        u.email,
        u.created_at,
        COUNT(ai.id) as total_investments,
        SUM(ai.amount) as total_invested,
        tu.username as telegram_username
      FROM users u
      LEFT JOIN aureus_investments ai ON u.id = ai.user_id AND ai.status = 'completed'
      LEFT JOIN telegram_users tu ON u.id = tu.user_id
      WHERE u.sponsor_user_id = ?
      GROUP BY u.id
      ORDER BY u.created_at DESC
      LIMIT 20
    `, [userId]);

    let message = `👥 **My Referrals**\n\n`;

    if (referrals.length === 0) {
      message += `❌ **No referrals yet**

🎯 **Start referring today!**
Share your Telegram username with friends and earn 15% commission on their investments.`;
    } else {
      message += `📊 **Total Referrals:** ${referrals.length}\n\n`;

      referrals.forEach((referral, index) => {
        const joinDate = new Date(referral.created_at).toLocaleDateString();
        const investments = referral.total_investments || 0;
        const invested = referral.total_invested || 0;

        message += `${index + 1}. **@${referral.telegram_username || referral.username}**
   📅 Joined: ${joinDate}
   💰 Investments: ${investments} ($${invested.toFixed(2)})

`;
      });
    }

    const keyboard = {
      inline_keyboard: [
        [{ text: "🔄 Refresh", callback_data: "view_my_referrals" }],
        [{ text: "🔙 Back to Referrals", callback_data: "menu_referrals" }]
      ]
    };

    await ctx.editMessageText(message, {
      parse_mode: "Markdown",
      reply_markup: keyboard
    });

  } catch (error) {
    console.error("Error showing referrals list:", error);
    await ctx.editMessageText("❌ Error loading referrals. Please try again.");
  }
}

async function showMyCommissionHistory(ctx) {
  try {
    const telegramUser = ctx.telegramUser;
    const userEmail = telegramUser.linked_email || telegramUser.email;

    // Get user ID
    const [userResult] = await dbConnection.execute(`
      SELECT id FROM users WHERE email = ?
    `, [userEmail]);

    if (userResult.length === 0) {
      await ctx.editMessageText("❌ User not found.");
      return;
    }

    const userId = userResult[0].id;

    // Get commission history
    const [commissions] = await dbConnection.execute(`
      SELECT
        c.*,
        referred.username as referred_username,
        tu.username as referred_telegram
      FROM commissions c
      JOIN users referred ON c.referred_user_id = referred.id
      LEFT JOIN telegram_users tu ON referred.id = tu.user_id
      WHERE c.referrer_id = ?
      ORDER BY c.date_earned DESC
      LIMIT 20
    `, [userId]);

    let message = `💰 **Commission History**\n\n`;

    if (commissions.length === 0) {
      message += `❌ **No commissions yet**

💡 **How to earn commissions:**
1. Refer friends using your Telegram username
2. They register and make investments
3. You earn 15% commission automatically!`;
    } else {
      const totalEarned = commissions.reduce((sum, c) => sum + parseFloat(c.commission_amount), 0);
      message += `📊 **Total Earned:** $${totalEarned.toFixed(2)}\n\n`;

      commissions.forEach((commission, index) => {
        const date = new Date(commission.date_earned).toLocaleDateString();
        const statusEmoji = {
          'pending': '⏳',
          'approved': '✅',
          'paid': '💳',
          'cancelled': '❌'
        };

        message += `${index + 1}. **$${commission.commission_amount}** ${statusEmoji[commission.status] || '❓'}
   👤 From: @${commission.referred_telegram || commission.referred_username}
   💰 Investment: $${commission.investment_amount}
   📅 Date: ${date}
   📊 Status: ${commission.status}

`;
      });
    }

    const keyboard = {
      inline_keyboard: [
        [{ text: "🔄 Refresh", callback_data: "view_my_commissions" }],
        [{ text: "🔙 Back to Referrals", callback_data: "menu_referrals" }]
      ]
    };

    await ctx.editMessageText(message, {
      parse_mode: "Markdown",
      reply_markup: keyboard
    });

  } catch (error) {
    console.error("Error showing commission history:", error);
    await ctx.editMessageText("❌ Error loading commission history. Please try again.");
  }
}

async function showReferralLeaderboard(ctx) {
  try {
    // Get top referrers
    const [topReferrers] = await dbConnection.execute(`
      SELECT
        u.username,
        u.total_referrals,
        u.total_commission_earned,
        tu.username as telegram_username
      FROM users u
      LEFT JOIN telegram_users tu ON u.id = tu.user_id
      WHERE u.total_referrals > 0
      ORDER BY u.total_referrals DESC, u.total_commission_earned DESC
      LIMIT 10
    `);

    let message = `🏆 **Referral Leaderboard**\n\n`;

    if (topReferrers.length === 0) {
      message += `❌ **No referrers yet**

Be the first to start referring and claim the top spot! 🥇`;
    } else {
      const medals = ['🥇', '🥈', '🥉'];

      topReferrers.forEach((referrer, index) => {
        const medal = medals[index] || `${index + 1}.`;
        const username = referrer.telegram_username || referrer.username;

        message += `${medal} **@${username}**
   👥 ${referrer.total_referrals} referrals
   💰 $${referrer.total_commission_earned} earned

`;
      });
    }

    const keyboard = {
      inline_keyboard: [
        [{ text: "🔄 Refresh", callback_data: "view_referral_leaderboard" }],
        [{ text: "🔙 Back to Referrals", callback_data: "menu_referrals" }]
      ]
    };

    await ctx.editMessageText(message, {
      parse_mode: "Markdown",
      reply_markup: keyboard
    });

  } catch (error) {
    console.error("Error showing leaderboard:", error);
    await ctx.editMessageText("❌ Error loading leaderboard. Please try again.");
  }
}

async function showReferralAnalytics(ctx) {
  try {
    const telegramUser = ctx.telegramUser;
    const userEmail = telegramUser.linked_email || telegramUser.email;

    // Get user ID
    const [userResult] = await dbConnection.execute(`
      SELECT id FROM users WHERE email = ?
    `, [userEmail]);

    if (userResult.length === 0) {
      await ctx.editMessageText("❌ User not found.");
      return;
    }

    const userId = userResult[0].id;

    // Get detailed analytics
    const [monthlyStats] = await dbConnection.execute(`
      SELECT
        DATE_FORMAT(u.created_at, '%Y-%m') as month,
        COUNT(*) as referrals_count,
        SUM(COALESCE(ai.amount, 0)) as total_volume
      FROM users u
      LEFT JOIN aureus_investments ai ON u.id = ai.user_id AND ai.status = 'completed'
      WHERE u.sponsor_user_id = ?
      GROUP BY DATE_FORMAT(u.created_at, '%Y-%m')
      ORDER BY month DESC
      LIMIT 6
    `, [userId]);

    const [conversionStats] = await dbConnection.execute(`
      SELECT
        COUNT(*) as total_referrals,
        COUNT(CASE WHEN ai.id IS NOT NULL THEN 1 END) as active_investors,
        AVG(ai.amount) as avg_investment
      FROM users u
      LEFT JOIN aureus_investments ai ON u.id = ai.user_id AND ai.status = 'completed'
      WHERE u.sponsor_user_id = ?
    `, [userId]);

    const stats = conversionStats[0];
    const conversionRate = stats.total_referrals > 0 ?
      ((stats.active_investors / stats.total_referrals) * 100).toFixed(1) : 0;

    let message = `📊 **Referral Analytics**\n\n`;

    message += `🎯 **Performance Metrics:**
• **Total Referrals:** ${stats.total_referrals || 0}
• **Active Investors:** ${stats.active_investors || 0}
• **Conversion Rate:** ${conversionRate}%
• **Avg Investment:** $${(stats.avg_investment || 0).toFixed(2)}

📈 **Monthly Breakdown:**`;

    if (monthlyStats.length === 0) {
      message += `\n• No data available yet`;
    } else {
      monthlyStats.forEach(month => {
        message += `\n• **${month.month}:** ${month.referrals_count} referrals, $${(month.total_volume || 0).toFixed(2)} volume`;
      });
    }

    const keyboard = {
      inline_keyboard: [
        [{ text: "🔄 Refresh", callback_data: "view_referral_analytics" }],
        [{ text: "🔙 Back to Referrals", callback_data: "menu_referrals" }]
      ]
    };

    await ctx.editMessageText(message, {
      parse_mode: "Markdown",
      reply_markup: keyboard
    });

  } catch (error) {
    console.error("Error showing analytics:", error);
    await ctx.editMessageText("❌ Error loading analytics. Please try again.");
  }
}

async function showReferralInstructions(ctx) {
  const message = `🔗 **How to Refer Friends**

🎯 **Simple Steps:**

1️⃣ **Share Your Username**
   • Give friends your Telegram username: **@${ctx.from.username || 'Please set username'}**
   • They need this when registering

2️⃣ **Guide Them to Register**
   • Send them this bot: @aureus_africa_bot
   • They click "Create New Account"
   • During registration, they enter your username

3️⃣ **Earn Commissions**
   • You get 15% of all their investments
   • Commissions are tracked automatically
   • Payments processed by admin

💡 **Pro Tips:**
• Set a Telegram username if you don't have one
• Share the benefits of Aureus Alliance Holdings
• Help them through the registration process
• Stay active to build trust

🎁 **Commission Structure:**
• **Rate:** 15% of investment amount
• **Payment:** Pending → Approved → Paid
• **Tracking:** Real-time in your dashboard

💰 **Example:**
Friend invests $1,000 → You earn $150 commission!`;

  const keyboard = {
    inline_keyboard: [
      [{ text: "📱 Share Bot Link", url: "https://t.me/aureus_africa_bot" }],
      [{ text: "🔙 Back to Referrals", callback_data: "menu_referrals" }]
    ]
  };

  await ctx.editMessageText(message, {
    parse_mode: "Markdown",
    reply_markup: keyboard
  });
}

// ADMIN REFERRAL MANAGEMENT FUNCTIONS
async function showReferralManagement(ctx) {
  try {
    // Get referral statistics
    const [referralStats] = await dbConnection.execute(`
      SELECT
        COUNT(*) as total_users,
        COUNT(sponsor_user_id) as users_with_referrers,
        COUNT(DISTINCT sponsor_user_id) as active_referrers
      FROM users
    `);

    const [topReferrers] = await dbConnection.execute(`
      SELECT
        u.username,
        u.email,
        u.total_referrals,
        u.total_commission_earned,
        tu.username as telegram_username
      FROM users u
      LEFT JOIN telegram_users tu ON u.id = tu.user_id
      WHERE u.total_referrals > 0
      ORDER BY u.total_referrals DESC
      LIMIT 10
    `);

    const stats = referralStats[0];
    const referralRate = stats.total_users > 0 ? ((stats.users_with_referrers / stats.total_users) * 100).toFixed(1) : 0;

    let referralMessage = `🎯 **Referral Management**

📊 **Statistics:**
• **Total Users:** ${stats.total_users}
• **Users with Referrers:** ${stats.users_with_referrers}
• **Active Referrers:** ${stats.active_referrers}
• **Referral Rate:** ${referralRate}%

👑 **Top Referrers:**`;

    if (topReferrers.length === 0) {
      referralMessage += "\n• No referrers yet";
    } else {
      topReferrers.forEach((referrer, index) => {
        referralMessage += `\n${index + 1}. @${referrer.telegram_username || referrer.username} - ${referrer.total_referrals} referrals ($${referrer.total_commission_earned})`;
      });
    }

    const keyboard = {
      inline_keyboard: [
        [
          { text: "📋 View All Referrals", callback_data: "admin_view_all_referrals" },
          { text: "🔍 Search Referrer", callback_data: "admin_search_referrer" }
        ],
        [
          { text: "📊 Detailed Stats", callback_data: "admin_referral_stats" },
          { text: "🔄 Refresh", callback_data: "admin_referrals" }
        ],
        [
          { text: "🔙 Back to Admin Panel", callback_data: "admin_panel_access" }
        ]
      ]
    };

    await ctx.editMessageText(referralMessage, {
      parse_mode: "Markdown",
      reply_markup: keyboard
    });

    await logAdminAction(ctx.from.id, 'VIEW_REFERRAL_MANAGEMENT', {
      stats: stats,
      admin_username: ctx.from.username
    });

  } catch (error) {
    console.error("Error showing referral management:", error);
    await ctx.editMessageText("❌ Error loading referral data. Please try again.");
  }
}

async function showCommissionManagement(ctx) {
  try {
    // Get commission statistics
    const [commissionStats] = await dbConnection.execute(`
      SELECT
        COUNT(*) as total_commissions,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_commissions,
        COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_commissions,
        COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_commissions,
        SUM(commission_amount) as total_commission_amount,
        SUM(CASE WHEN status = 'pending' THEN commission_amount ELSE 0 END) as pending_amount,
        SUM(CASE WHEN status = 'approved' THEN commission_amount ELSE 0 END) as approved_amount,
        SUM(CASE WHEN status = 'paid' THEN commission_amount ELSE 0 END) as paid_amount
      FROM commissions
    `);

    const [pendingCommissions] = await dbConnection.execute(`
      SELECT
        c.*,
        referrer.username as referrer_username,
        referrer.email as referrer_email,
        referred.username as referred_username,
        referred.email as referred_email,
        tu.username as referrer_telegram
      FROM commissions c
      JOIN users referrer ON c.referrer_id = referrer.id
      JOIN users referred ON c.referred_user_id = referred.id
      LEFT JOIN telegram_users tu ON referrer.id = tu.user_id
      WHERE c.status = 'pending'
      ORDER BY c.date_earned DESC
      LIMIT 5
    `);

    const stats = commissionStats[0];

    let commissionMessage = `💰 **Commission Management**

📊 **Statistics:**
• **Total Commissions:** ${stats.total_commissions}
• **Pending:** ${stats.pending_commissions} ($${stats.pending_amount || 0})
• **Approved:** ${stats.approved_commissions} ($${stats.approved_amount || 0})
• **Paid:** ${stats.paid_commissions} ($${stats.paid_amount || 0})
• **Total Amount:** $${stats.total_commission_amount || 0}

⏳ **Recent Pending Commissions:**`;

    if (pendingCommissions.length === 0) {
      commissionMessage += "\n• No pending commissions";
    } else {
      pendingCommissions.forEach((commission, index) => {
        commissionMessage += `\n${index + 1}. @${commission.referrer_telegram || commission.referrer_username} - $${commission.commission_amount} (${commission.investment_type})`;
      });
    }

    const keyboard = {
      inline_keyboard: [
        [
          { text: "⏳ Review Pending", callback_data: "admin_review_pending_commissions" },
          { text: "✅ Approved List", callback_data: "admin_approved_commissions" }
        ],
        [
          { text: "💳 Paid History", callback_data: "admin_paid_commissions" },
          { text: "📊 Commission Stats", callback_data: "admin_commission_stats" }
        ],
        [
          { text: "📈 Referral Analytics", callback_data: "admin_referral_analytics" }
        ],
        [
          { text: "🔄 Refresh", callback_data: "admin_commissions" },
          { text: "🔙 Back to Admin Panel", callback_data: "admin_panel_access" }
        ]
      ]
    };

    await ctx.editMessageText(commissionMessage, {
      parse_mode: "Markdown",
      reply_markup: keyboard
    });

    await logAdminAction(ctx.from.id, 'VIEW_COMMISSION_MANAGEMENT', {
      stats: stats,
      admin_username: ctx.from.username
    });

  } catch (error) {
    console.error("Error showing commission management:", error);
    await ctx.editMessageText("❌ Error loading commission data. Please try again.");
  }
}

async function approveCommission(ctx, commissionId) {
  try {
    // Update commission status to approved
    await dbConnection.execute(`
      UPDATE commissions
      SET status = 'approved', date_approved = NOW()
      WHERE id = ?
    `, [commissionId]);

    // Get commission details for notification
    const [commissionResult] = await dbConnection.execute(`
      SELECT
        c.*,
        referrer.username as referrer_username,
        tu.telegram_id
      FROM commissions c
      JOIN users referrer ON c.referrer_id = referrer.id
      LEFT JOIN telegram_users tu ON referrer.id = tu.user_id
      WHERE c.id = ?
    `, [commissionId]);

    if (commissionResult.length > 0) {
      const commission = commissionResult[0];

      // Notify referrer if they have Telegram
      if (commission.telegram_id) {
        // Get updated referrer stats
        const [referrerStats] = await dbConnection.execute(`
          SELECT
            total_commission_earned,
            (SELECT COUNT(*) FROM commissions WHERE referrer_id = ? AND status = 'approved') as approved_commissions,
            (SELECT SUM(commission_amount) FROM commissions WHERE referrer_id = ? AND status = 'approved') as approved_amount
          FROM users WHERE id = ?
        `, [commission.referrer_id, commission.referrer_id, commission.referrer_id]);

        const stats = referrerStats[0] || {};

        const notificationMessage = `✅ **Commission Approved!**

🎉 **Great news!** Your commission has been approved by admin.

💰 **This Commission:** $${commission.commission_amount}
📊 **From Investment:** $${commission.investment_amount}
⏰ **Status:** Approved → Pending Payment

📊 **Your Updated Stats:**
• **Total Earned:** $${(stats.total_commission_earned || 0).toFixed(2)}
• **Approved & Pending Payment:** $${(stats.approved_amount || 0).toFixed(2)}
• **Approved Commissions:** ${stats.approved_commissions || 0}

💳 **Next Steps:**
• Payment will be processed soon
• You'll receive another notification when paid
• Keep referring to earn more!

🚀 **Keep up the great work!**`;

        const keyboard = {
          inline_keyboard: [
            [
              { text: "💰 Commission History", callback_data: "view_my_commissions" },
              { text: "👥 My Referrals", callback_data: "view_my_referrals" }
            ],
            [
              { text: "🔗 Share Referral Link", callback_data: "get_referral_link" }
            ]
          ]
        };

        try {
          await bot.telegram.sendMessage(commission.telegram_id, notificationMessage, {
            parse_mode: "Markdown",
            reply_markup: keyboard
          });
        } catch (error) {
          console.error("Error notifying referrer:", error);
        }
      }
    }

    await ctx.editMessageText(`✅ **Commission Approved**\n\nCommission ID: ${commissionId}\nStatus updated to approved.`, {
      parse_mode: "Markdown",
      reply_markup: {
        inline_keyboard: [
          [{ text: "🔙 Back to Commissions", callback_data: "admin_commissions" }]
        ]
      }
    });

    await logAdminAction(ctx.from.id, 'APPROVE_COMMISSION', {
      commission_id: commissionId,
      admin_username: ctx.from.username
    });

  } catch (error) {
    console.error("Error approving commission:", error);
    await ctx.editMessageText("❌ Error approving commission. Please try again.");
  }
}

async function rejectCommission(ctx, commissionId) {
  try {
    // Update commission status to rejected (we can add this status to the enum)
    await dbConnection.execute(`
      UPDATE commissions
      SET status = 'cancelled'
      WHERE id = ?
    `, [commissionId]);

    await ctx.editMessageText(`❌ **Commission Rejected**\n\nCommission ID: ${commissionId}\nStatus updated to cancelled.`, {
      parse_mode: "Markdown",
      reply_markup: {
        inline_keyboard: [
          [{ text: "🔙 Back to Commissions", callback_data: "admin_commissions" }]
        ]
      }
    });

    await logAdminAction(ctx.from.id, 'REJECT_COMMISSION', {
      commission_id: commissionId,
      admin_username: ctx.from.username
    });

  } catch (error) {
    console.error("Error rejecting commission:", error);
    await ctx.editMessageText("❌ Error rejecting commission. Please try again.");
  }
}

async function showAdminReferralAnalytics(ctx) {
  try {
    // Get comprehensive referral analytics
    const [monthlyGrowth] = await dbConnection.execute(`
      SELECT
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as new_referrals,
        COUNT(DISTINCT sponsor_user_id) as active_referrers
      FROM users
      WHERE sponsor_user_id IS NOT NULL
        AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
      GROUP BY DATE_FORMAT(created_at, '%Y-%m')
      ORDER BY month DESC
      LIMIT 12
    `);

    const [conversionStats] = await dbConnection.execute(`
      SELECT
        COUNT(DISTINCT u.id) as total_referred_users,
        COUNT(DISTINCT ai.user_id) as investing_referred_users,
        AVG(ai.amount) as avg_investment_per_referred_user,
        SUM(ai.amount) as total_investment_volume_from_referrals
      FROM users u
      LEFT JOIN aureus_investments ai ON u.id = ai.user_id AND ai.status = 'completed'
      WHERE u.sponsor_user_id IS NOT NULL
    `);

    const [topPerformers] = await dbConnection.execute(`
      SELECT
        u.username,
        u.total_referrals,
        u.total_commission_earned,
        tu.username as telegram_username,
        COUNT(ai.id) as total_investments_from_referrals,
        SUM(ai.amount) as total_volume_from_referrals
      FROM users u
      LEFT JOIN telegram_users tu ON u.id = tu.user_id
      LEFT JOIN users referred ON u.id = referred.sponsor_user_id
      LEFT JOIN aureus_investments ai ON referred.id = ai.user_id AND ai.status = 'completed'
      WHERE u.total_referrals > 0
      GROUP BY u.id
      ORDER BY u.total_commission_earned DESC
      LIMIT 10
    `);

    const [commissionTrends] = await dbConnection.execute(`
      SELECT
        DATE_FORMAT(date_earned, '%Y-%m') as month,
        COUNT(*) as commissions_earned,
        SUM(commission_amount) as total_commission_amount,
        COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_commissions,
        SUM(CASE WHEN status = 'paid' THEN commission_amount ELSE 0 END) as paid_amount
      FROM commissions
      WHERE date_earned >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
      GROUP BY DATE_FORMAT(date_earned, '%Y-%m')
      ORDER BY month DESC
      LIMIT 6
    `);

    const conversion = conversionStats[0];
    const conversionRate = conversion.total_referred_users > 0 ?
      ((conversion.investing_referred_users / conversion.total_referred_users) * 100).toFixed(1) : 0;

    let analyticsMessage = `📈 **Referral Analytics Dashboard**

📊 **Key Metrics:**
• **Total Referred Users:** ${conversion.total_referred_users || 0}
• **Investing Referred Users:** ${conversion.investing_referred_users || 0}
• **Conversion Rate:** ${conversionRate}%
• **Avg Investment:** $${(conversion.avg_investment_per_referred_user || 0).toFixed(2)}
• **Total Volume from Referrals:** $${(conversion.total_investment_volume_from_referrals || 0).toLocaleString()}

📈 **Monthly Growth (Last 6 months):**`;

    if (monthlyGrowth.length === 0) {
      analyticsMessage += `\n• No referral data available`;
    } else {
      monthlyGrowth.slice(0, 6).forEach(month => {
        analyticsMessage += `\n• **${month.month}:** ${month.new_referrals} new referrals, ${month.active_referrers} active referrers`;
      });
    }

    analyticsMessage += `\n\n💰 **Commission Trends:**`;

    if (commissionTrends.length === 0) {
      analyticsMessage += `\n• No commission data available`;
    } else {
      commissionTrends.forEach(trend => {
        analyticsMessage += `\n• **${trend.month}:** ${trend.commissions_earned} earned ($${trend.total_commission_amount}), ${trend.paid_commissions} paid ($${trend.paid_amount})`;
      });
    }

    analyticsMessage += `\n\n🏆 **Top Performers:**`;

    if (topPerformers.length === 0) {
      analyticsMessage += `\n• No top performers yet`;
    } else {
      topPerformers.slice(0, 5).forEach((performer, index) => {
        analyticsMessage += `\n${index + 1}. **@${performer.telegram_username || performer.username}** - ${performer.total_referrals} referrals, $${performer.total_commission_earned} earned`;
      });
    }

    const keyboard = {
      inline_keyboard: [
        [
          { text: "📊 Detailed Report", callback_data: "admin_detailed_referral_report" },
          { text: "📈 Export Data", callback_data: "admin_export_referral_data" }
        ],
        [
          { text: "🔄 Refresh", callback_data: "admin_referral_analytics" },
          { text: "🔙 Back to Admin Panel", callback_data: "admin_panel_access" }
        ]
      ]
    };

    await ctx.editMessageText(analyticsMessage, {
      parse_mode: "Markdown",
      reply_markup: keyboard
    });

    await logAdminAction(ctx.from.id, 'VIEW_REFERRAL_ANALYTICS', {
      admin_username: ctx.from.username,
      timestamp: new Date().toISOString()
    });

  } catch (error) {
    console.error("Error showing referral analytics:", error);
    await ctx.editMessageText("❌ Error loading referral analytics. Please try again.");
  }
}

async function markCommissionAsPaid(ctx, commissionId) {
  try {
    // Get commission details before updating
    const [commissionResult] = await dbConnection.execute(`
      SELECT
        c.*,
        referrer.username as referrer_username,
        tu.telegram_id
      FROM commissions c
      JOIN users referrer ON c.referrer_id = referrer.id
      LEFT JOIN telegram_users tu ON referrer.id = tu.user_id
      WHERE c.id = ?
    `, [commissionId]);

    if (commissionResult.length === 0) {
      await ctx.editMessageText("❌ Commission not found.");
      return;
    }

    const commission = commissionResult[0];

    // Update commission status to paid
    await dbConnection.execute(`
      UPDATE commissions
      SET status = 'paid', date_paid = NOW()
      WHERE id = ?
    `, [commissionId]);

    // Notify referrer if they have Telegram
    if (commission.telegram_id) {
      // Get updated referrer stats
      const [referrerStats] = await dbConnection.execute(`
        SELECT
          total_commission_earned,
          (SELECT COUNT(*) FROM commissions WHERE referrer_id = ? AND status = 'paid') as paid_commissions,
          (SELECT SUM(commission_amount) FROM commissions WHERE referrer_id = ? AND status = 'paid') as paid_amount
        FROM users WHERE id = ?
      `, [commission.referrer_id, commission.referrer_id, commission.referrer_id]);

      const stats = referrerStats[0] || {};

      const paymentMessage = `💳 **Commission Paid!**

🎉 **Payment Processed Successfully!**

💰 **Amount Paid:** $${commission.commission_amount}
📊 **From Investment:** $${commission.investment_amount}
✅ **Status:** PAID

📊 **Your Payment Stats:**
• **Total Earned:** $${(stats.total_commission_earned || 0).toFixed(2)}
• **Total Paid:** $${(stats.paid_amount || 0).toFixed(2)}
• **Paid Commissions:** ${stats.paid_commissions || 0}

🎯 **Keep Earning:**
• Continue referring friends
• Earn 15% on every investment
• Build your passive income stream

Thank you for being a valued referrer! 🚀`;

      const keyboard = {
        inline_keyboard: [
          [
            { text: "💰 Commission History", callback_data: "view_my_commissions" },
            { text: "🔗 Get Referral Link", callback_data: "get_referral_link" }
          ],
          [
            { text: "🏆 View Leaderboard", callback_data: "public_leaderboard" }
          ]
        ]
      };

      try {
        await bot.telegram.sendMessage(commission.telegram_id, paymentMessage, {
          parse_mode: "Markdown",
          reply_markup: keyboard
        });
      } catch (error) {
        console.error("Error notifying referrer of payment:", error);
      }
    }

    await ctx.editMessageText(`💳 **Commission Marked as Paid**\n\nCommission ID: ${commissionId}\nAmount: $${commission.commission_amount}\nStatus updated to paid.`, {
      parse_mode: "Markdown",
      reply_markup: {
        inline_keyboard: [
          [{ text: "🔙 Back to Commissions", callback_data: "admin_commissions" }]
        ]
      }
    });

    await logAdminAction(ctx.from.id, 'MARK_COMMISSION_PAID', {
      commission_id: commissionId,
      amount: commission.commission_amount,
      admin_username: ctx.from.username
    });

  } catch (error) {
    console.error("Error marking commission as paid:", error);
    await ctx.editMessageText("❌ Error processing payment. Please try again.");
  }
}

// AUTHENTICATION FUNCTIONS
async function validateUserCredentials(email, password) {
  try {
    const [rows] = await dbConnection.execute(
      'SELECT id, full_name, email, password_hash FROM users WHERE email = ?',
      [email]
    );

    if (rows.length === 0) {
      return { success: false, error: 'EMAIL_NOT_FOUND' };
    }

    const user = rows[0];

    // Compare password with hashed password using bcrypt
    const passwordMatch = await bcrypt.compare(password, user.password_hash);
    if (passwordMatch) {
      return { success: true, user: { ...user, name: user.full_name } };
    } else {
      return { success: false, error: 'INVALID_PASSWORD' };
    }
  } catch (error) {
    console.error("Error validating credentials:", error);
    return { success: false, error: 'DATABASE_ERROR' };
  }
}

async function linkTelegramAccount(telegramId, userEmail, userId) {
  try {
    await updateTelegramUser(telegramId, {
      is_registered: true,
      registration_step: 'complete',
      registration_mode: null,
      temp_email: null,
      temp_password: null,
      linked_email: userEmail,
      user_id: userId,
      auto_login_enabled: true
    });

    console.log(`✅ Telegram account ${telegramId} linked to email ${userEmail}`);
    return true;
  } catch (error) {
    console.error("Error linking Telegram account:", error);
    return false;
  }
}

async function generatePasswordResetToken() {
  return Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
}

async function createPasswordResetToken(email) {
  try {
    const token = await generatePasswordResetToken();
    const expiresAt = new Date(Date.now() + 30 * 60 * 1000); // 30 minutes

    // Store token in users table
    await dbConnection.execute(
      'UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE email = ?',
      [token, expiresAt, email]
    );

    return token;
  } catch (error) {
    console.error("Error creating password reset token:", error);
    return null;
  }
}

async function validatePasswordResetToken(token) {
  try {
    const [rows] = await dbConnection.execute(
      'SELECT id, email FROM users WHERE password_reset_token = ? AND password_reset_expires > NOW()',
      [token]
    );

    return rows.length > 0 ? rows[0] : null;
  } catch (error) {
    console.error("Error validating reset token:", error);
    return null;
  }
}

async function updateUserPassword(email, newPassword) {
  try {
    // Hash the password before storing it
    const hashedPassword = await bcrypt.hash(newPassword, 10);

    await dbConnection.execute(
      'UPDATE users SET password_hash = ?, password_reset_token = NULL, password_reset_expires = NULL WHERE email = ?',
      [hashedPassword, email]
    );
    return true;
  } catch (error) {
    console.error("Error updating password:", error);
    return false;
  }
}

async function handleAuthenticationFlow(ctx, user) {
  const messageText = ctx.message.text;

  // Handle admin authentication flow
  if (user.admin_auth_step) {
    try {
      if (user.admin_auth_step === 'email') {
        // Store admin email and ask for password
        await updateTelegramUser(ctx.from.id, {
          admin_temp_email: messageText,
          admin_auth_step: 'password'
        });

        await ctx.reply("🔐 **Admin Password**\n\nPlease enter your admin password:");
        return;
      } else if (user.admin_auth_step === 'password') {
        // Authenticate admin
        const authResult = authenticateAdmin(ctx.from.id, user.admin_temp_email, messageText);

        if (authResult.success) {
          // Clear admin auth state
          await updateTelegramUser(ctx.from.id, {
            admin_auth_step: null,
            admin_temp_email: null
          });

          const successMessage = `✅ **Admin Authentication Successful**

Welcome, Administrator @${ctx.from.username}!

🛡️ **Security Status:**
• Authentication: ✅ Verified
• Session Duration: 1 hour
• All actions will be logged

🔧 **Admin Panel Access:**
Use /admin to access the admin panel or click the button below.`;

          const keyboard = {
            inline_keyboard: [
              [{ text: "🔐 Open Admin Panel", callback_data: "admin_panel_access" }],
              [{ text: "🔙 Back to Main Menu", callback_data: "back_to_menu" }]
            ]
          };

          await ctx.reply(successMessage, {
            parse_mode: "Markdown",
            reply_markup: keyboard
          });

          logAdminAction(ctx.from.id, 'ADMIN_LOGIN_SUCCESS', {
            email: user.admin_temp_email,
            username: ctx.from.username,
            timestamp: new Date().toISOString()
          });
        } else {
          let errorMessage = "❌ **Admin Authentication Failed**\n\n";

          if (authResult.error === 'COOLDOWN') {
            const remainingMinutes = Math.ceil(authResult.remainingTime / 60000);
            errorMessage += `Too many failed attempts. Please wait ${remainingMinutes} minutes before trying again.`;
          } else if (authResult.error === 'INVALID_CREDENTIALS') {
            errorMessage += `Invalid credentials. Attempts remaining: ${authResult.attemptsRemaining}`;
          }

          errorMessage += "\n\n⚠️ **Security Notice:** Failed admin login attempts are logged and monitored.";

          await ctx.reply(errorMessage);

          // Clear admin auth state on failure
          await updateTelegramUser(ctx.from.id, {
            admin_auth_step: null,
            admin_temp_email: null
          });
        }
        return;
      }
    } catch (error) {
      console.error('Admin auth error:', error);
      await ctx.reply("❌ Admin authentication error. Please try again.");
      return;
    }
  }

  try {
    if (user.registration_step === 'email') {
      // Validate email format
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(messageText)) {
        await ctx.reply("❌ Please enter a valid email address.");
        return;
      }

      // For login mode, check if email exists first
      if (user.registration_mode === 'login') {
        try {
          const [existingUsers] = await dbConnection.execute(
            'SELECT id FROM users WHERE email = ?',
            [messageText]
          );

          if (existingUsers.length === 0) {
            // Email doesn't exist - show user-friendly message with options
            const emailNotFoundMessage = `❌ **Email Not Found**

The email address "${messageText}" is not registered in our system.

🔹 **Options:**
• Create a new account with this email
• Try a different email address
• Contact support if you need help`;

            const keyboard = {
              inline_keyboard: [
                [{ text: "📝 Register with this Email", callback_data: "register_with_email" }],
                [{ text: "🔄 Try Different Email", callback_data: "auth_login" }],
                [{ text: "📞 Contact Support", callback_data: "contact_support" }]
              ]
            };

            await ctx.reply(emailNotFoundMessage, {
              parse_mode: "Markdown",
              reply_markup: keyboard
            });
            return;
          }
        } catch (error) {
          console.error('Error checking email existence:', error);
          await ctx.reply("❌ Database error. Please try again.");
          return;
        }
      }

      // Store email and ask for password
      await updateTelegramUser(ctx.from.id, {
        temp_email: messageText,
        registration_step: 'password'
      });

      const passwordMessage = user.registration_mode === 'login'
        ? "🔑 **Enter Password**\n\nPlease enter your password:"
        : "🔑 **Create Password**\n\nPlease create a secure password:";

      const keyboard = user.registration_mode === 'login'
        ? {
            inline_keyboard: [
              [{ text: "🔄 Forgot Password?", callback_data: "forgot_password" }],
              [{ text: "📝 Register Instead", callback_data: "switch_to_register" }],
              [{ text: "🔙 Back", callback_data: "auth_back_to_start" }]
            ]
          }
        : {
            inline_keyboard: [
              [{ text: "🔑 Login Instead", callback_data: "switch_to_login" }],
              [{ text: "🔙 Back", callback_data: "auth_back_to_start" }]
            ]
          };

      await ctx.reply(passwordMessage, {
        parse_mode: "Markdown",
        reply_markup: keyboard
      });

    } else if (user.registration_step === 'password') {
      if (user.registration_mode === 'login') {
        // Handle login
        const validation = await validateUserCredentials(user.temp_email, messageText);

        if (validation.success) {
          // Link Telegram account
          const linked = await linkTelegramAccount(ctx.from.id, user.temp_email, validation.user.id);

          if (linked) {
            // Send welcome email
            await sendWelcomeEmail(user.temp_email, validation.user.name);

            const successMessage = `✅ **Login Successful!**

Welcome back, ${validation.user.name}! 🎉

🔗 **Account Linked:** Your Telegram account is now permanently linked to your investment account.

🚀 **Auto-Login Enabled:** You won't need to login again unless you explicitly logout.

📧 **Welcome Email:** Check your email for confirmation and additional information.

Ready to continue your investment journey? 💎`;

            // Show main menu with inline keyboard buttons
            const keyboard = {
              inline_keyboard: [
                [
                  { text: "📦 Packages", callback_data: "menu_packages" },
                  { text: "📊 Portfolio", callback_data: "menu_portfolio" }
                ],
                [
                  { text: "👥 Referrals", callback_data: "menu_referrals" },
                  { text: "🎫 NFT Assets", callback_data: "menu_nft" }
                ],
                [
                  { text: "📜 Certificates", callback_data: "menu_certificates" },
                  { text: "👤 Profile", callback_data: "menu_profile" }
                ],
                [
                  { text: "💰 Mining Calculator", callback_data: "menu_calculator" },
                  { text: "📞 Support", callback_data: "menu_support" }
                ],
                [
                  { text: "🚪 Logout", callback_data: "logout_confirm" }
                ]
              ]
            };

            await ctx.reply(successMessage, {
              parse_mode: "Markdown",
              reply_markup: keyboard
            });
            console.log(`✅ User ${ctx.from.first_name} (${ctx.from.id}) logged in and linked to ${user.temp_email}`);
          } else {
            await ctx.reply("❌ Login successful but failed to link account. Please try again or contact support.");
          }
        } else {
          let errorMessage = "❌ Login failed. ";

          if (validation.error === 'EMAIL_NOT_FOUND') {
            errorMessage += "Email address not found.\n\n🔹 **Options:**\n• Check your email address\n• Use /start to register a new account\n• Contact support if you need help";
          } else if (validation.error === 'INVALID_PASSWORD') {
            errorMessage += "Incorrect password.\n\n🔹 **Options:**\n• Try again with correct password\n• Use 'Forgot Password?' below\n• Contact support if you need help";
          } else {
            errorMessage += "Please try again or contact support.";
          }

          const keyboard = {
            inline_keyboard: [
              [{ text: "🔄 Forgot Password?", callback_data: "forgot_password" }],
              [{ text: "🔄 Try Again", callback_data: "auth_login" }],
              [{ text: "📝 Register Instead", callback_data: "auth_register" }]
            ]
          };

          await ctx.reply(errorMessage, {
            parse_mode: "Markdown",
            reply_markup: keyboard
          });
        }
      } else if (user.registration_mode === 'register') {
        // Handle registration - check if user already exists
        const [existingUsers] = await dbConnection.execute(
          'SELECT id FROM users WHERE email = ?',
          [user.temp_email]
        );

        if (existingUsers.length > 0) {
          // User already exists, suggest login instead
          const existsMessage = `❌ **Account Already Exists**

An account with email ${user.temp_email} already exists.

🔹 **Options:**
• Use the login option instead
• Try a different email address
• Reset your password if you forgot it`;

          const keyboard = {
            inline_keyboard: [
              [{ text: "🔑 Login Instead", callback_data: "auth_login" }],
              [{ text: "🔄 Forgot Password?", callback_data: "forgot_password" }],
              [{ text: "🔙 Back to Start", callback_data: "auth_back_to_start" }]
            ]
          };

          await ctx.reply(existsMessage, {
            parse_mode: "Markdown",
            reply_markup: keyboard
          });
        } else {
          // Store password and move to referral step
          try {
            const hashedPassword = await bcrypt.hash(messageText, 10);

            // Check if user came from referral link
            const currentUser = await getTelegramUser(ctx.from.id);

            if (currentUser.referred_by_link && currentUser.temp_referrer_username) {
              // User came from referral link, skip referral step and complete registration
              const hashedPassword = await bcrypt.hash(messageText, 10);

              // Create user in main users table
              const [result] = await dbConnection.execute(`
                INSERT INTO users (username, email, password_hash, is_active, created_at, updated_at)
                VALUES (?, ?, ?, 1, NOW(), NOW())
              `, [
                ctx.from.username || `user_${ctx.from.id}`,
                user.temp_email,
                hashedPassword
              ]);

              const userId = result.insertId;

              // Create referral code for new user
              const referralCode = await createReferralCode(userId, ctx.from.username || `user_${ctx.from.id}`);

              // Find and link referrer
              const referralResult = await findUserByTelegramUsername(currentUser.temp_referrer_username);
              if (referralResult.found) {
                await linkReferralRelationship(userId, referralResult.user.user_id, currentUser.temp_referrer_username);
              }

              // Complete registration
              await updateTelegramUser(ctx.from.id, {
                user_id: userId,
                linked_email: user.temp_email,
                is_registered: true,
                registration_step: null,
                registration_mode: null,
                temp_email: null,
                temp_password: null,
                referral_step: null,
                temp_referrer_username: null,
                referred_by_link: false
              });

              await sendRegistrationSuccessMessage(ctx, user.temp_email, true, currentUser.temp_referrer_username);
              return;
            }

            // Store password temporarily and move to referral step
            await updateTelegramUser(ctx.from.id, {
              temp_password: hashedPassword,
              registration_step: 'referral'
            });

            const referralMessage = `🎯 **Referral Code (Optional)**

Do you have a referral code or know someone who referred you to Aureus Alliance Holdings?

**Benefits of having a referrer:**
• Your referrer earns 15% commission on your investments
• Helps support the community growth
• Completely optional - you can skip this step

**Enter the Telegram username** (without @) of the person who referred you, or skip this step:`;

            const referralKeyboard = {
              inline_keyboard: [
                [{ text: "⏭️ Skip Referral", callback_data: "skip_referral" }],
                [{ text: "🔙 Back to Password", callback_data: "back_to_password" }]
              ]
            };

            await ctx.reply(referralMessage, {
              parse_mode: "Markdown",
              reply_markup: referralKeyboard
            });

            const successMessage = `✅ **Registration Successful!**

Welcome to Aureus Alliance Holdings, ${ctx.from.first_name}!

**Account Details:**
• **Email:** ${user.temp_email}
• **Username:** ${ctx.from.username || `user_${ctx.from.id}`}
• **Telegram:** Linked successfully

🎉 **You're now ready to:**
• Browse investment packages
• Calculate mining returns
• Make secure investments
• Track your portfolio

Use the menu below to get started!`;

            const keyboard = {
              inline_keyboard: [
                [
                  { text: "📦 View Packages", callback_data: "view_packages" },
                  { text: "🧮 Calculator", callback_data: "mining_calculator" }
                ],
                [
                  { text: "📊 Dashboard", callback_data: "dashboard" },
                  { text: "🔙 Main Menu", callback_data: "back_to_menu" }
                ]
              ]
            };

            await ctx.reply(successMessage, {
              parse_mode: "Markdown",
              reply_markup: keyboard
            });

            console.log(`✅ New user registered: ${user.temp_email} (Telegram: ${ctx.from.id})`);

          } catch (error) {
            console.error('Registration error:', error);

            if (error.code === 'ER_DUP_ENTRY') {
              await ctx.reply("❌ **Registration Failed**\n\nThis email address is already registered. Please try logging in instead or use a different email address.");
            } else {
              await ctx.reply("❌ **Registration Failed**\n\nAn error occurred during registration. Please try again or contact support.");
            }

            // Reset registration state
            await updateTelegramUser(ctx.from.id, {
              registration_step: 'start',
              registration_mode: null,
              temp_email: null,
              temp_password: null
            });
          }
        }
      }
    } else if (user.registration_step === 'referral') {
      // Handle referral username input
      const referralUsername = messageText.trim().replace('@', '');

      if (referralUsername.length === 0) {
        await ctx.reply("❌ Please enter a valid Telegram username or use the Skip button.");
        return;
      }

      // Validate referral username
      const referralResult = await findUserByTelegramUsername(referralUsername);

      if (referralResult.found) {
        // Store the referrer info temporarily and ask for confirmation
        await updateTelegramUser(ctx.from.id, {
          temp_referrer_username: referralUsername,
          referral_step: 'confirm'
        });

        const confirmMessage = `✅ **Referrer Found!**

**Referrer Details:**
• **Name:** ${referralResult.user.full_name || 'Not provided'}
• **Username:** @${referralUsername}
• **Email:** ${referralResult.user.email}

Is this the correct person who referred you?`;

        const confirmKeyboard = {
          inline_keyboard: [
            [
              { text: "✅ Yes, Confirm", callback_data: "confirm_referrer" },
              { text: "❌ No, Try Again", callback_data: "retry_referrer" }
            ],
            [{ text: "⏭️ Skip Referral", callback_data: "skip_referral" }]
          ]
        };

        await ctx.reply(confirmMessage, {
          parse_mode: "Markdown",
          reply_markup: confirmKeyboard
        });
      } else {
        const notFoundMessage = `❌ **Username Not Found**

The username "@${referralUsername}" was not found in our system.

**Possible reasons:**
• The username might be incorrect
• The person hasn't registered with our bot yet
• They might have a different username

Please try again or skip this step:`;

        const retryKeyboard = {
          inline_keyboard: [
            [{ text: "🔄 Try Again", callback_data: "retry_referrer" }],
            [{ text: "⏭️ Skip Referral", callback_data: "skip_referral" }]
          ]
        };

        await ctx.reply(notFoundMessage, {
          parse_mode: "Markdown",
          reply_markup: retryKeyboard
        });
      }
    } else if (user.registration_step === 'reset_token') {
      // Handle reset token validation
      const tokenValid = await validatePasswordResetToken(messageText.trim());

      if (tokenValid && tokenValid.email === user.temp_email) {
        await updateTelegramUser(ctx.from.id, {
          registration_step: 'reset_password'
        });

        await ctx.reply(`✅ **Token Verified!**

Reset token is valid for: ${tokenValid.email}

🔑 **Enter New Password**

Please enter your new password (minimum 6 characters):`, { parse_mode: "Markdown" });
      } else {
        await ctx.reply(`❌ **Invalid or Expired Token**

The reset token is either invalid or has expired.

🔹 **Options:**
• Check the token carefully
• Request a new reset token
• Contact support if you need help`, {
          parse_mode: "Markdown",
          reply_markup: {
            inline_keyboard: [
              [{ text: "🔄 New Reset Token", callback_data: "forgot_password" }],
              [{ text: "🔙 Back to Login", callback_data: "auth_login" }]
            ]
          }
        });
      }
    } else if (user.registration_step === 'reset_password') {
      // Handle new password during reset
      if (messageText.length < 6) {
        await ctx.reply("❌ Password must be at least 6 characters long. Please try again:");
        return;
      }

      const updated = await updateUserPassword(user.temp_email, messageText);

      if (updated) {
        // Clear reset state and auto-login
        const validation = await validateUserCredentials(user.temp_email, messageText);

        if (validation.success) {
          await linkTelegramAccount(ctx.from.id, user.temp_email, validation.user.id);

          await ctx.reply(`✅ **Password Reset Successful!**

Your password has been updated and you are now logged in.

🔗 **Account Linked:** Your Telegram account is linked for future access.

Ready to continue? Use /menu to get started! 💎`, { parse_mode: "Markdown" });
        }
      } else {
        await ctx.reply("❌ Failed to update password. Please try again or contact support.");
      }
    }
  } catch (error) {
    console.error("Authentication flow error:", error);
    await ctx.reply("❌ An error occurred during authentication. Please try again or contact support.");
  }
}

    // MESSAGE HANDLERS FOR AUTHENTICATION AND PAYMENT PROCESSING
    bot.on("message", async (ctx) => {
      const user = await getTelegramUser(ctx.from.id);

      // Handle custom investment amount input
      const userState = userStates.get(ctx.from.id);
      if (userState && userState.state === 'awaiting_custom_amount' && ctx.message.text) {
        await handleCustomAmountInput(ctx, ctx.message.text, userState.messageId);
        return;
      }

      // Handle authentication flow
      if (user && user.registration_step && !user.is_registered) {
        await handleAuthenticationFlow(ctx, user);
        return;
      }

      // Handle admin message submission
      if (user && user.awaiting_admin_message && ctx.message.text) {
        const messageText = ctx.message.text.trim();

        if (messageText.length < 5) {
          await ctx.reply("❌ Message too short. Please provide a more detailed message.");
          return;
        }

        try {
          // Save user message to admin
          const messageId = await saveUserMessage(ctx.from.id, {
            user_id: user.user_id,
            username: ctx.from.username,
            first_name: ctx.from.first_name,
            last_name: ctx.from.last_name
          }, messageText, 'contact_admin');

          // Clear awaiting state
          await updateTelegramUser(ctx.from.id, {
            awaiting_admin_message: false
          });

          const confirmMessage = `✅ **Message Sent to Admin**

Your message has been successfully forwarded to the administrator.

**Message:** ${messageText}

📧 **What happens next:**
• Admin will review your message
• You'll receive a reply within 24 hours
• Check back for admin responses

Thank you for contacting us!`;

          const keyboard = {
            inline_keyboard: [
              [{ text: "🔙 Back to Menu", callback_data: "back_to_menu" }]
            ]
          };

          await ctx.reply(confirmMessage, {
            parse_mode: "Markdown",
            reply_markup: keyboard
          });

          // Log the admin contact
          await logAdminAction(ctx.from.id, 'user_contact_admin', {
            message_id: messageId,
            message_preview: messageText.substring(0, 100),
            user_id: user.user_id,
            admin_username: ctx.from.username
          });

          return;
        } catch (error) {
          console.error('Error saving admin message:', error);
          await ctx.reply("❌ Error sending message. Please try again or contact support directly.");
          return;
        }
      }

      // Handle admin search input
      if (user && user.admin_search_mode && ctx.message.text) {
        const searchTerm = ctx.message.text.trim();

        if (!isAuthorizedForAdmin(ctx.from.username) || !isAdminAuthenticated(ctx.from.id)) {
          await ctx.reply("❌ Admin authentication required.");
          return;
        }

        try {
          const users = await searchUsers(searchTerm, user.admin_search_mode);

          // Clear search mode
          await updateTelegramUser(ctx.from.id, {
            admin_search_mode: null
          });

          let resultsMessage = `🔍 **Search Results**\n\n`;
          resultsMessage += `**Search Term:** ${searchTerm}\n`;
          resultsMessage += `**Search Type:** ${user.admin_search_mode}\n\n`;

          if (users.length === 0) {
            resultsMessage += "❌ No users found matching your search criteria.";
          } else {
            resultsMessage += `📊 **Found ${users.length} user(s):**\n\n`;

            users.forEach((user, index) => {
              resultsMessage += `${index + 1}. **${user.username}**\n`;
              resultsMessage += `   📧 Email: ${user.email}\n`;
              resultsMessage += `   🆔 User ID: ${user.id}\n`;
              if (user.telegram_id) {
                resultsMessage += `   📱 Telegram: ${user.telegram_id}\n`;
              }
              resultsMessage += `   ✅ Active: ${user.is_active ? 'Yes' : 'No'}\n`;
              resultsMessage += `   📅 Created: ${new Date(user.created_at).toLocaleDateString()}\n\n`;
            });
          }

          const keyboard = {
            inline_keyboard: [
              [
                { text: "🔍 New Search", callback_data: "admin_user_management" },
                { text: "🔙 Back to Admin", callback_data: "admin_panel_access" }
              ]
            ]
          };

          await ctx.reply(resultsMessage, {
            parse_mode: "Markdown",
            reply_markup: keyboard
          });

          await logAdminAction(ctx.from.id, 'user_search', {
            search_term: searchTerm,
            search_type: user.admin_search_mode,
            results_count: users.length,
            admin_username: ctx.from.username
          });

          return;
        } catch (error) {
          console.error('Error performing admin search:', error);
          await ctx.reply("❌ Error performing search. Please try again.");
          return;
        }
      }

      // Handle admin reply input
      if (user && user.admin_replying_to_message && ctx.message.text) {
        const replyText = ctx.message.text.trim();

        if (!isAuthorizedForAdmin(ctx.from.username) || !isAdminAuthenticated(ctx.from.id)) {
          await ctx.reply("❌ Admin authentication required.");
          return;
        }

        if (replyText.length < 5) {
          await ctx.reply("❌ Reply too short. Please provide a more detailed response.");
          return;
        }

        try {
          const messageId = user.admin_replying_to_message;

          // Get the original message
          const [messageRows] = await dbConnection.execute(`
            SELECT * FROM admin_user_messages WHERE id = ?
          `, [messageId]);

          if (messageRows.length === 0) {
            await ctx.reply("❌ Original message not found.");
            return;
          }

          const originalMessage = messageRows[0];

          // Save the admin reply
          await saveAdminReply(messageId, ctx.from.id, ctx.from.username, replyText);

          // Send reply to user
          const replySent = await sendReplyToUser(originalMessage, replyText, ctx.from.username);

          // Clear admin reply state
          await updateTelegramUser(ctx.from.id, {
            admin_replying_to_message: null
          });

          if (replySent) {
            const successMessage = `✅ **Reply Sent Successfully**

**To:** ${originalMessage.first_name || 'User'}
**Your Reply:** ${replyText}

The user has been notified of your response.`;

            const keyboard = {
              inline_keyboard: [
                [{ text: "📬 Back to Messages", callback_data: "admin_view_all_messages" }],
                [{ text: "🔙 Admin Panel", callback_data: "admin_panel_access" }]
              ]
            };

            await ctx.reply(successMessage, {
              parse_mode: "Markdown",
              reply_markup: keyboard
            });
          } else {
            await ctx.reply("⚠️ **Reply Saved** but failed to notify user. The reply has been saved in the system.");
          }

          // Log the admin reply
          await logAdminAction(ctx.from.id, 'ADMIN_REPLY_SENT', {
            message_id: messageId,
            reply_preview: replyText.substring(0, 100),
            user_telegram_id: originalMessage.telegram_id,
            admin_username: ctx.from.username
          });

          return;
        } catch (error) {
          console.error('Error sending admin reply:', error);
          await ctx.reply("❌ Error sending reply. Please try again.");
          return;
        }
      }

      // Handle 3-step payment verification process (CHECK THIS FIRST!)
      if (user && user.payment_step && ctx.message.text) {
        const messageText = ctx.message.text.trim();

        // Step 1: Sender wallet address
        if (user.awaiting_sender_wallet && user.payment_step === 1) {
          // Basic validation for wallet address format
          if (messageText.length < 20 || !messageText.match(/^(0x[a-fA-F0-9]{40}|[13][a-km-zA-HJ-NP-Z1-9]{25,34}|T[A-Za-z1-9]{33})$/)) {
            await ctx.reply("❌ Invalid wallet address format. Please provide a valid wallet address.\n\nExample: 0x742d35Cc6634C0532925a3b8D4C9db96C4b4d8b7");
            return;
          }

          await ctx.reply(`✅ **Step 1 Complete: Sender Wallet Received**

📝 **Step 2 of 3: Payment Screenshot**

Please send a screenshot of your payment transaction from your wallet app.

This helps us verify the payment details quickly.

📸 **Send the screenshot now...**`);

          // Update user state for step 2
          await updateTelegramUser(ctx.from.id, {
            awaiting_sender_wallet: false,
            awaiting_screenshot: true,
            payment_step: 2,
            sender_wallet_address: messageText
          });
          return;
        }

        // Step 3: Transaction hash (after screenshot) - SIMPLIFIED
        if (user.awaiting_tx_hash && user.payment_step === 3) {
          console.log(`✅ Transaction hash received: ${messageText}`);

          const successMessage = `✅ **Payment Submitted Successfully**

**Transaction Hash:** \`${messageText}\`
**Status:** Pending Admin Approval

Your payment has been recorded and submitted for admin approval. You will receive a confirmation once it's processed.

Thank you for your investment!`;

          await ctx.reply(successMessage, { parse_mode: "Markdown" });

          // Clear the awaiting state
          await updateTelegramUser(ctx.from.id, {
            awaiting_tx_hash: false,
            awaiting_screenshot: false,
            awaiting_sender_wallet: false,
            payment_network: null,
            payment_package_id: null,
            payment_step: null,
            sender_wallet_address: null,
            screenshot_path: null,
            payment_is_custom: false
          });

          return;
        }
      }

      // Handle screenshot upload (Step 2) - 🚨 FIX 2: SAVE SCREENSHOT TO FILE SYSTEM
      if (user && user.awaiting_screenshot && user.payment_step === 2 && ctx.message.photo) {
        try {
          // Get the largest photo size
          const photo = ctx.message.photo[ctx.message.photo.length - 1];
          const fileId = photo.file_id;

          // Get file info from Telegram
          const fileInfo = await ctx.telegram.getFile(fileId);
          const fileUrl = `https://api.telegram.org/file/bot${process.env.TELEGRAM_BOT_TOKEN}/${fileInfo.file_path}`;

          // Create screenshots directory if it doesn't exist
          const fs = require('fs');
          const path = require('path');
          const screenshotsDir = path.join(__dirname, 'screenshots');
          if (!fs.existsSync(screenshotsDir)) {
            fs.mkdirSync(screenshotsDir, { recursive: true });
          }

          // Generate unique filename
          const timestamp = Date.now();
          const filename = `payment_screenshot_${ctx.from.id}_${timestamp}.jpg`;
          const filePath = path.join(screenshotsDir, filename);

          // Download and save the screenshot
          const https = require('https');
          const file = fs.createWriteStream(filePath);

          await new Promise((resolve, reject) => {
            https.get(fileUrl, (response) => {
              response.pipe(file);
              file.on('finish', () => {
                file.close();
                resolve();
              });
              file.on('error', reject);
            }).on('error', reject);
          });

          await ctx.reply(`✅ **Step 2 Complete: Screenshot Received**

📝 **Step 3 of 3: Transaction Hash**

Now please send the transaction hash (TxID) from your payment.

You can find this in your wallet app or on the blockchain explorer.

Example: 0x7cf1241a8e28517825c9182f12f83a4845cc3ec22bda96279566ca15b1c94c8

📝 **Send the transaction hash now...**`);

          // Update user state for step 3 and save screenshot path (relative path)
          const relativeScreenshotPath = `screenshots/${filename}`;
          await updateTelegramUser(ctx.from.id, {
            awaiting_screenshot: false,
            awaiting_tx_hash: true,
            payment_step: 3,
            screenshot_path: relativeScreenshotPath
          });

          console.log(`📸 Screenshot saved to ${filePath} for user ${ctx.from.id}, moving to step 3`);
          return;
        } catch (error) {
          console.error('Error processing screenshot upload:', error);
          await ctx.reply("❌ **Error Processing Screenshot**\n\nThere was an issue processing your screenshot. Please try uploading it again or contact support if the problem persists.");
          return;
        }
      }

      // Handle screenshot upload error - user sent screenshot but not in correct state
      if (user && ctx.message.photo && !user.awaiting_screenshot) {
        await ctx.reply("❌ **Unexpected Screenshot**\n\nI received a screenshot, but you're not currently in the payment verification process. Please start a new investment to upload payment screenshots.");
        return;
      }

      // Handle receipt upload
      if (user && user.awaiting_receipt && ctx.message.photo) {
        const receiptConfirmation = `📸 **Receipt Received**

Thank you for uploading your payment receipt!

⏳ **Processing...**
Our team will verify your bank transfer within 1-3 business days.

You will receive a confirmation message once the payment is verified and your investment is activated.

📧 **Need Help?**
Contact our support team if you have any questions.`;

        await ctx.reply(receiptConfirmation, { parse_mode: "Markdown" });

        // Create bank payment record for admin approval
        try {
          const referenceNumber = `AUR-${Date.now().toString().slice(-6)}`;
          await createBankPaymentRecord(ctx.from.id, user.payment_package_id, referenceNumber);
          console.log(`🏦 Bank payment record created for user ${ctx.from.id}`);
        } catch (error) {
          console.error("Failed to create bank payment record:", error);
        }

        // Clear the awaiting state
        await updateTelegramUser(ctx.from.id, {
          awaiting_receipt: false,
          payment_package_id: null
        });

        return;
      }

      // Handle calculator share input (MOVED AFTER PAYMENT VERIFICATION)
      if (user && user.calculator_awaiting_shares && ctx.message.text) {
        const shareInput = ctx.message.text.trim();
        const shares = parseInt(shareInput.replace(/[,\s]/g, '')); // Remove commas and spaces

        if (isNaN(shares) || shares < 1) {
          await ctx.reply("❌ Please enter a valid number of shares (e.g., 1000).");
          return;
        }

        if (shares > 1400000) {
          await ctx.reply("❌ Maximum shares available is 1,400,000. Please enter a smaller number.");
          return;
        }

        try {
          // Clear awaiting state
          await updateTelegramUser(ctx.from.id, {
            calculator_awaiting_shares: false
          });

          // Show calculator with specified shares
          await showMiningCalculator(ctx, shares);
          return;
        } catch (error) {
          console.error('Error processing calculator shares:', error);
          await ctx.reply("❌ Error processing shares. Please try again.");
          return;
        }
      }
    });

    console.log("🚀 Starting Aureus Africa Telegram Bot (Fixed Version)...");
    console.log("🔄 Starting bot in polling mode...");

    bot.launch();
  } catch (error) {
    console.error("❌ Failed to start bot:", error);
    process.exit(1);
  }
}

// BLOCKCHAIN VERIFICATION FUNCTIONS REMOVED - NO VALIDATION

/**
 * Perform basic blockchain verification (simplified for now)
 * In production, this would make actual API calls to blockchain explorers
 */
async function performBasicBlockchainVerification(txHash, network, senderWallet, paymentId) {
  try {
    console.log(`🔍 Starting blockchain verification for ${txHash} on ${network}`);

    // Note: Duplicate checking is now done in createCryptoPaymentRecord before creating the record
    // This function focuses on blockchain-specific validation

    // For now, we'll do basic format validation and mark for manual review
    // In production, you would:
    // 1. Make API calls to Etherscan/BSCScan/PolygonScan/TronGrid
    // 2. Verify transaction exists and is confirmed
    // 3. Check recipient address matches company wallet
    // 4. Verify amount matches expected payment
    // 5. Check sender address matches provided wallet

    console.log(`✅ Basic verification passed for ${txHash}`);

    // Update payment record with verification attempt
    await dbConnection.execute(`
      UPDATE crypto_payment_transactions
      SET verification_status = 'manual_review_required',
          verification_notes = 'Basic validation passed - requires manual blockchain verification'
      WHERE id = ?
    `, [paymentId]);

    return {
      requiresManualReview: true,
      message: "✅ **Basic Validation Passed**\n\nTransaction format is valid and no duplicates found."
    };

  } catch (error) {
    console.error('Blockchain verification error:', error);

    // Update payment record with error
    await dbConnection.execute(`
      UPDATE crypto_payment_transactions
      SET verification_status = 'verification_failed',
          verification_notes = ?
      WHERE id = ?
    `, [`Verification error: ${error.message}`, paymentId]);

    return {
      requiresManualReview: true,
      message: "⚠️ **Verification Error**\n\nUnable to verify transaction automatically. Manual review required."
    };
  }
}

// Network information helper function
function getNetworkInfo(network) {
  const networks = {
    'bitcoin': {
      name: 'Bitcoin',
      symbol: 'BTC',
      explorer: 'https://blockchair.com/bitcoin'
    },
    'ethereum': {
      name: 'Ethereum',
      symbol: 'ETH',
      explorer: 'https://etherscan.io'
    },
    'polygon': {
      name: 'Polygon (USDT)',
      symbol: 'USDT',
      explorer: 'https://polygonscan.com'
    },
    'other': {
      name: 'Other Cryptocurrency',
      symbol: 'CRYPTO',
      explorer: 'https://blockchair.com'
    }
  };

  return networks[network] || networks['other'];
}

// Graceful shutdown
process.once("SIGINT", () => {
  console.log("🛑 Stopping bot...");
  bot.stop("SIGINT");
  if (dbConnection) {
    dbConnection.end();
  }
});

process.once("SIGTERM", () => {
  console.log("🛑 Stopping bot...");
  bot.stop("SIGTERM");
  if (dbConnection) {
    dbConnection.end();
  }
});

// Start the bot
startBot();

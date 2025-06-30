# 🤖 Aureus Telegram Bot

## 📋 Overview
This is the official Telegram bot for the Aureus Angel Alliance investment platform. The bot handles user registration, authentication, investment management, and admin functions.

## 🚀 Quick Start

### Starting the Bot
```bash
# Option 1: Use the batch file (Windows)
start-telegram-bot.bat

# Option 2: Run directly
node telegram-bot.cjs
```

## 📁 Bot Files Structure

### Main Bot File
- **`telegram-bot.cjs`** - The main bot application (cleaned up and organized)

### Startup Scripts
- **`start-telegram-bot.bat`** - Windows batch file to start the bot

### Configuration
- Bot Token: `8015476800:AAGMH8HMXRurphYHRQDJdeHLO10ghZVzBt8`
- Bot Username: `@aureus_africa_bot`
- Database: MySQL on port 3506

## ✨ Bot Features

### 🔐 Authentication System
- **Registration**: Create new accounts directly in Telegram
- **Login**: Link existing web accounts to Telegram
- **Admin Access**: Secure admin authentication with session management
- **Password Reset**: Email-based password recovery

### 💰 Investment Features
- **Package Browsing**: View available investment packages
- **Mining Calculator**: Calculate potential returns
- **Portfolio Management**: Track investments and shares
- **Payment Processing**: Handle investment payments

### 👥 Admin Panel
- **User Management**: Search, view, and manage user accounts
- **Payment Confirmation**: Approve manual payments
- **Communication**: Send messages to users
- **Audit Logging**: Track all admin actions

### 📊 Additional Features
- **Referral System**: Share referral links and track commissions
- **Certificates**: Generate and download investment certificates
- **Support System**: Contact support and help features
- **Multi-language**: Support for multiple languages

## 🛠️ Technical Details

### Dependencies
- **telegraf**: Telegram Bot API framework
- **mysql2**: MySQL database connection
- **bcrypt**: Password hashing
- **nodemailer**: Email functionality

### Database Tables Used
- `users` - Main user accounts
- `telegram_users` - Telegram-specific user data
- `investments` - Investment records
- `packages` - Investment packages
- `company_wallets` - Payment wallet addresses
- `admin_sessions` - Admin authentication sessions

## 🔧 Maintenance

### Restarting the Bot
If the bot stops responding:
1. Stop the current process (Ctrl+C)
2. Run `start-telegram-bot.bat` or `node telegram-bot.js`

### Checking Logs
The bot outputs logs to the console showing:
- Database connection status
- User interactions
- Error messages
- Admin actions

## 🚨 Troubleshooting

### Common Issues
1. **Database Connection Failed**: Check MySQL is running on port 3506
2. **Bot Not Responding**: Restart the bot process
3. **Registration Issues**: Check database permissions and table structure

### Support
For technical issues, check the main project documentation or contact the development team.

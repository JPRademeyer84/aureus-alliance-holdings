# Telegram Bot for Aureus Alliance Holdings

## 🚨 CRITICAL PROJECT INFORMATION 🚨

### **Project Structure**
- **Main Web App**: `c:\xampp\htdocs\Aureus 1 - Complex\` (PHP + React)
- **Telegram Bot**: `c:\xampp\htdocs\Aureus-Telegram-Bot\` (Node.js + TypeScript)
- **Database**: Shared MySQL on **port 3506** (NOT default 3306)

### **Key Configuration Details**
- **XAMPP Port**: 3506 (custom, not standard)
- **Database Name**: aureus_angels
- **Database Host**: localhost:3506
- **Shared Tables**: users, investments, payments, referrals, etc.
- **Bot Framework**: Node.js + TypeScript + Telegraf.js

### **Directory Locations**
```
c:\xampp\htdocs\
├── Aureus 1 - Complex/          # Main web application (existing)
└── Aureus-Telegram-Bot/         # Telegram bot (to be created)
```

### **Database Connection**
Both applications connect to the SAME database:
- Host: localhost
- Port: 3506 (IMPORTANT: Custom port, not 3306)
- Database: aureus_angels
- User: root
- Password: (empty for XAMPP default)

---

## Overview
A Telegram bot that serves as a secondary payment method and equity share purchase interface for the Aureus Alliance Holdings platform. The bot connects to the same database as the main web application, providing users with mobile-friendly access to equity share opportunities.

## Features Required

### 🔐 Authentication & User Management
- User registration via Telegram
- Link existing web accounts to Telegram
- Secure session management
- KYC verification through bot
- Profile management

### 💰 Equity Share Features
- View available equity share packages
- Purchase equity shares via Telegram
- Equity share history and tracking
- Portfolio overview
- Dividend calculations and projections

### 💳 Payment Integration
- Multiple payment methods (crypto, bank transfer)
- Payment verification and confirmation
- Transaction history
- Manual payment submission with proof upload
- Auto-verification for supported payment methods

### 👥 Referral System
- Referral link generation
- Downline tracking
- Commission calculations
- Leaderboard access
- Gold Diggers Club integration

### 📊 Dashboard & Analytics
- Personal dashboard
- Equity share statistics
- Commission earnings
- Withdrawal history
- Performance metrics

### 🎫 NFT & Certificates
- NFT coupon management
- Certificate generation and download
- Share certificate printing
- Digital asset portfolio

### 💬 Support & Communication
- Live chat integration
- Support ticket system
- FAQ and help commands
- Notification system

## Technical Architecture

### Bot Framework
- **Language**: Node.js with TypeScript
- **Framework**: Telegraf.js (Telegram Bot API wrapper)
- **Database**: MySQL (shared with main application)
- **Authentication**: JWT tokens
- **File Storage**: Local storage with secure serving

### Database Integration
- Connect to existing MySQL database
- Use same tables as web application
- Maintain data consistency
- Real-time synchronization

### Security Features
- Rate limiting
- Input validation
- SQL injection prevention
- Secure file uploads
- Encrypted sensitive data

## Implementation Plan

### Phase 1: Basic Setup (Week 1) ✅ COMPLETED
1. **Bot Creation & Setup** ✅ COMPLETED
   - ✅ Create bot with BotFather (@aureus_africa_bot)
   - ✅ Set up development environment (Node.js + Telegraf)
   - ⏳ Configure webhooks (using polling for development)
   - ✅ Basic command structure (/start, /help, /menu, /testdb)

2. **Database Connection** ✅ COMPLETED
   - ✅ Connect to existing MySQL database (localhost:3506/aureus_angels)
   - ⏳ Create bot-specific tables if needed
   - ✅ Test database operations (working connection)
   - ⏳ Implement connection pooling

3. **User Authentication** 🔄 IN PROGRESS
   - ⏳ Registration flow
   - ⏳ Login system
   - ⏳ Account linking
   - ⏳ Session management

### Phase 2: Core Features (Week 2-3)
1. **Equity Share System**
   - Package listing
   - Equity share purchase flow
   - Payment integration
   - Transaction tracking

2. **Payment Methods**
   - Crypto payment integration
   - Bank transfer handling
   - Manual payment submission
   - Payment verification

3. **User Dashboard**
   - Portfolio overview
   - Equity share history
   - Basic statistics

### Phase 3: Advanced Features (Week 4-5)
1. **Referral System**
   - Referral tracking
   - Commission calculations
   - Leaderboard integration

2. **NFT & Certificates**
   - NFT management
   - Certificate generation
   - File downloads

3. **Support System**
   - Live chat integration
   - Support tickets
   - FAQ system

### Phase 4: Polish & Deployment (Week 6)
1. **Testing & Optimization**
   - Comprehensive testing
   - Performance optimization
   - Security audit

2. **Deployment**
   - Production setup
   - Monitoring
   - Documentation

## Project Structure

### Separate Bot Directory
The Telegram bot will be completely separate from the main web application:

```
Aureus 1 - Complex/                 # Main web application
├── api/
├── src/
├── public/
└── ...

telegram-bot/                       # Separate Telegram bot project
├── src/
├── uploads/
├── certificates/
├── package.json
├── .env
└── README.md
```

## File Structure
```
telegram-bot/
├── src/
│   ├── bot/
│   │   ├── commands/
│   │   │   ├── start.ts
│   │   │   ├── register.ts
│   │   │   ├── login.ts
│   │   │   ├── invest.ts
│   │   │   ├── portfolio.ts
│   │   │   ├── payments.ts
│   │   │   ├── referrals.ts
│   │   │   └── support.ts
│   │   ├── scenes/
│   │   │   ├── registration.ts
│   │   │   ├── investment.ts
│   │   │   ├── payment.ts
│   │   │   └── kyc.ts
│   │   ├── middleware/
│   │   │   ├── auth.ts
│   │   │   ├── rateLimit.ts
│   │   │   └── validation.ts
│   │   └── handlers/
│   │       ├── callback.ts
│   │       ├── message.ts
│   │       └── error.ts
│   ├── database/
│   │   ├── connection.ts
│   │   ├── models/
│   │   │   ├── User.ts
│   │   │   ├── Investment.ts
│   │   │   ├── Payment.ts
│   │   │   └── Referral.ts
│   │   └── queries/
│   ├── services/
│   │   ├── authService.ts
│   │   ├── investmentService.ts
│   │   ├── paymentService.ts
│   │   ├── referralService.ts
│   │   └── notificationService.ts
│   ├── utils/
│   │   ├── crypto.ts
│   │   ├── validation.ts
│   │   ├── formatting.ts
│   │   └── fileHandler.ts
│   ├── config/
│   │   ├── database.ts
│   │   ├── bot.ts
│   │   └── payments.ts
│   └── app.ts
├── uploads/
├── certificates/
├── .env
├── package.json
├── tsconfig.json
└── README.md
```

## Environment Variables
```env
# Bot Configuration
BOT_TOKEN=your_telegram_bot_token
WEBHOOK_URL=https://yourdomain.com/webhook
PORT=3000

# Database Configuration (CRITICAL: Custom port 3506, NOT 3306)
DB_HOST=localhost
DB_PORT=3506
DB_NAME=aureus_angels
DB_USER=root
DB_PASSWORD=

# Security
JWT_SECRET=your_jwt_secret
ENCRYPTION_KEY=your_encryption_key

# Payment Configuration
CRYPTO_API_KEY=your_crypto_api_key
BANK_API_KEY=your_bank_api_key

# File Storage
UPLOAD_PATH=./uploads
CERTIFICATE_PATH=./certificates
MAX_FILE_SIZE=10485760
```

## Key Commands Structure

### Basic Commands
- `/start` - Welcome message and registration
- `/help` - Show available commands
- `/menu` - Main navigation menu
- `/profile` - User profile and settings

### Equity Share Commands
- `/packages` - View equity share packages
- `/invest` - Start equity share purchase process
- `/portfolio` - View current equity shares
- `/history` - Equity share history

### Payment Commands
- `/payments` - Payment methods
- `/pay` - Make a payment
- `/verify` - Verify payment
- `/transactions` - Transaction history

### Referral Commands
- `/referral` - Referral information
- `/link` - Get referral link
- `/downline` - View downline
- `/commissions` - Commission earnings

### Support Commands
- `/support` - Contact support
- `/faq` - Frequently asked questions
- `/status` - System status

## Database Integration Points

### Shared Tables
- `users` - User accounts
- `investments` - Equity share records
- `payments` - Payment transactions
- `referrals` - Referral tracking
- `commissions` - Commission records
- `nft_coupons` - NFT management
- `certificates` - Certificate records

### Bot-Specific Tables
```sql
CREATE TABLE telegram_users (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    telegram_id BIGINT UNIQUE NOT NULL,
    username VARCHAR(255),
    first_name VARCHAR(255),
    last_name VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE telegram_sessions (
    id VARCHAR(36) PRIMARY KEY,
    telegram_id BIGINT NOT NULL,
    session_data TEXT,
    expires_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE bot_notifications (
    id VARCHAR(36) PRIMARY KEY,
    telegram_id BIGINT NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'success', 'error') DEFAULT 'info',
    is_sent BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## Security Considerations

### Authentication
- Secure token-based authentication
- Session timeout management
- Rate limiting per user
- Input validation and sanitization

### Data Protection
- Encrypt sensitive user data
- Secure file upload handling
- SQL injection prevention
- XSS protection

### Payment Security
- PCI DSS compliance considerations
- Secure payment processing
- Transaction verification
- Fraud detection

## Deployment Instructions

### Prerequisites
- Node.js 18+ installed
- MySQL database access
- SSL certificate for webhooks
- Domain name for webhook URL

### Installation Steps
1. Clone repository
2. Install dependencies: `npm install`
3. Configure environment variables
4. Set up database tables
5. Configure webhook with Telegram
6. Start bot: `npm start`

### Production Deployment
- Use PM2 for process management
- Set up reverse proxy (Nginx)
- Configure SSL certificates
- Set up monitoring and logging
- Implement backup strategies

## Testing Strategy

### Unit Tests
- Database operations
- Business logic
- Utility functions
- Payment processing

### Integration Tests
- Bot command flows
- Database integration
- Payment gateway integration
- Webhook handling

### User Acceptance Tests
- Complete user journeys
- Payment flows
- Error handling
- Performance testing

## Monitoring & Maintenance

### Logging
- Structured logging with Winston
- Error tracking
- Performance metrics
- User activity logs

### Monitoring
- Bot uptime monitoring
- Database performance
- Payment processing status
- User engagement metrics

### Maintenance
- Regular security updates
- Database optimization
- Performance tuning
- Feature updates

## Success Metrics

### User Engagement
- Daily active users
- Command usage statistics
- Session duration
- User retention rate

### Business Metrics
- Equity share volume through bot
- Payment success rate
- Referral conversion rate
- Support ticket resolution time

## Future Enhancements

### Advanced Features
- AI-powered equity share recommendations
- Voice message support
- Multi-language support
- Advanced analytics dashboard

### Integrations
- Social media sharing
- Calendar integration
- Email notifications
- Mobile app deep linking

## Support & Documentation

### User Documentation
- Getting started guide
- Command reference
- FAQ section
- Video tutorials

### Developer Documentation
- API documentation
- Code comments
- Architecture diagrams
- Deployment guides

---

## Directory Setup Instructions

### 1. Create Separate Bot Directory
```bash
# Navigate to parent directory of your main project
cd "c:\xampp\htdocs\"

# Create separate telegram bot directory
mkdir "Aureus-Telegram-Bot"
cd "Aureus-Telegram-Bot"

# Initialize Node.js project
npm init -y

# Install dependencies
npm install telegraf mysql2 jsonwebtoken bcryptjs dotenv
npm install -D typescript @types/node ts-node nodemon
```

### 2. Database Connection
The bot will connect to the same MySQL database as the main application:
- **Host**: localhost:3506 (your custom XAMPP port)
- **Database**: aureus_angels
- **Tables**: Shared with main application

### 3. Independent Deployment
- Separate package.json and dependencies
- Own environment configuration
- Independent startup and shutdown
- Separate logging and monitoring
- Can be deployed on different server if needed

## Benefits of Separation
- **Modularity**: Easy to maintain and update independently
- **Scalability**: Can scale bot separately from web app
- **Security**: Isolated security configurations
- **Deployment**: Independent deployment cycles
- **Development**: Different teams can work on each part
- **Technology**: Can use different tech stacks if needed

## Next Steps
1. ✅ Create separate `Aureus-Telegram-Bot` directory
2. ✅ Set up development environment in new directory
3. ✅ Create Telegram bot with BotFather (@aureus_africa_bot)
4. ✅ Initialize Node.js project with TypeScript
5. ✅ Configure database connection to existing MySQL (port 3506)
6. ✅ Implement basic bot structure (/start, /help, /menu commands)
7. 🔄 Develop core authentication flow (CURRENT TASK)
8. ⏳ Implement investment features
9. ⏳ Add payment integration
10. ⏳ Test and deploy independently

This Telegram bot will provide users with a convenient mobile interface for managing their equity shares while maintaining full integration with the existing web platform.

---

## 📋 QUICK REFERENCE FOR NEW AGENTS

### **Essential Information**
- **Main Project**: Aureus Alliance Holdings equity share platform
- **Web App Location**: `c:\xampp\htdocs\Aureus 1 - Complex\`
- **Bot Location**: `c:\xampp\htdocs\Aureus-Telegram-Bot\` (separate directory)
- **Database Port**: 3506 (CUSTOM, not 3306)
- **Technology Stack**:
  - Web App: PHP + React + Vite
  - Bot: Node.js + TypeScript + Telegraf.js
- **Shared Database**: Both apps use same MySQL database
- **Purpose**: Bot serves as secondary payment method with mobile access

### **Development Environment**
- XAMPP running on custom port 3506
- MySQL database: aureus_angels
- Vite dev server: localhost:5173
- Admin panel: localhost:5173/admin

### **Key Features to Implement**
1. User authentication and registration
2. Equity share package viewing and purchasing
3. Payment processing (crypto + bank transfer)
4. Referral system integration
5. Portfolio management
6. NFT and certificate handling
7. Live chat support integration

### **Database Tables (Shared)**
- users, investments, payments, referrals
- admin_users, company_wallets, nft_coupons
- certificates, commissions, phases

This file contains the complete specification for building the Telegram bot as a separate application that integrates with the existing investment platform.

# Aureus Angel Alliance - Comprehensive Codebase Index

## 🏗️ Project Overview

**Aureus Angel Alliance** is a sophisticated React-based investment platform with a PHP/MySQL backend, featuring advanced security, KYC verification, multi-language support, and comprehensive admin management.

### Core Technologies
- **Frontend**: React 18 + TypeScript + Vite
- **UI Framework**: Tailwind CSS + shadcn/ui components
- **State Management**: TanStack Query for API calls
- **Routing**: React Router DOM
- **Backend**: PHP with MySQL
- **Development Environment**: XAMPP (Apache + MySQL)
- **Security**: Enterprise-grade API security with MFA

## 📁 Directory Structure

### Frontend (`/src`)
```
src/
├── components/           # React components
│   ├── ui/              # shadcn/ui base components
│   ├── dashboard/       # Dashboard-specific components
│   ├── investment/      # Investment-related components
│   ├── chat/           # Live chat components
│   ├── admin/          # Admin panel components
│   ├── auth/           # Authentication components
│   └── kyc/            # KYC verification components
├── pages/              # Main page components
├── hooks/              # Custom React hooks
├── contexts/           # React context providers
├── config/             # Configuration files
├── lib/                # Utility libraries
├── utils/              # Helper functions
└── i18n/               # Internationalization
```

### Backend (`/api`)
```
api/
├── config/             # Database & security configuration
├── admin/              # Admin management endpoints
├── users/              # User management & authentication
├── investments/        # Investment processing
├── kyc/                # KYC document handling
├── chat/               # Live chat system
├── payments/           # Payment processing
├── certificates/       # Certificate generation
├── translations/       # Multi-language support
├── security/           # Security monitoring
└── utils/              # Backend utilities
```

## 🔐 Authentication & Security System

### User Authentication
- **Session-based authentication** with secure session management
- **Multi-factor authentication (MFA)** support
- **Rate limiting** and abuse detection
- **CAPTCHA integration** for bot protection
- **Password hashing** with PHP's password_hash()

### Admin Authentication
- **Separate admin authentication system**
- **Role-based access control** (super admin, regular admin, chat support)
- **Fresh MFA requirements** for sensitive operations
- **Session monitoring** and automatic logout

### API Security
- **Enterprise-grade API security middleware**
- **CORS protection** with configurable origins
- **Request validation** and input sanitization
- **SQL injection prevention** with prepared statements
- **XSS protection** and output encoding

## 💰 Investment/Participation System

### Investment Packages
- **8 Presale Packages**: From $25 Shovel to $1000 Aureus
- **NFT Packs**: $5 each with 180-day ROI countdown
- **Total Supply**: 200,000 packages available
- **Dynamic pricing** and availability tracking

### Features
- **Package management** via admin interface
- **Purchase tracking** and investment history
- **ROI calculations** with automated payouts
- **Wallet integration** for crypto payments
- **Manual payment verification** for bank transfers

## 🔍 KYC (Know Your Customer) System

### Document Verification
- **Single document upload**: License, ID, or Passport
- **File storage**: Secure storage in `/public/assets/kyc/`
- **Admin verification interface** for approval/rejection
- **Re-upload capability** for rejected documents

### Facial Recognition
- **Advanced facial verification** using face-api.js
- **Liveness detection** with movement challenges
- **Confidence scoring** and verification thresholds
- **Real-time face detection** and landmark analysis

### KYC Levels
- **Multi-tier KYC system** with different verification levels
- **Progressive access** to features based on KYC completion
- **Automated status tracking** and notifications

## 👥 Admin Dashboard System

### User Management
- **User account management** (activate/deactivate)
- **KYC document review** and approval workflow
- **Investment history** and transaction monitoring
- **Commission tracking** and payout management

### System Administration
- **Package management** (create/edit/delete investment packages)
- **Wallet configuration** for different cryptocurrencies
- **Certificate generation** and verification
- **Security monitoring** and audit logs

### Analytics & Reporting
- **Dashboard statistics** with real-time metrics
- **User activity monitoring**
- **Investment performance tracking**
- **Commission distribution reports**

## 💬 Live Chat System

### Features
- **Real-time messaging** between users and support agents
- **Guest chat support** for non-registered users
- **Agent status management** (online/offline/busy)
- **Message history** and session management
- **Offline message handling**

### Implementation
- **WebSocket-like polling** for real-time updates
- **Session-based chat rooms**
- **File attachment support**
- **Admin chat management interface**

## 💳 Payment & Wallet System

### Wallet Integration
- **Multi-wallet support** (MetaMask, WalletConnect, etc.)
- **Chain switching** for different networks
- **Transaction verification** and confirmation
- **Wallet address validation**

### Payment Methods
- **Cryptocurrency payments** via wallet integration
- **Bank transfer support** with manual verification
- **Country-based payment detection**
- **Payment method recommendations**

## 🌍 Translation & Internationalization

### Multi-language Support
- **Database-driven translations** with fallback system
- **Dynamic language switching**
- **Translation key management** via admin interface
- **AI-powered translation suggestions**

### Supported Languages
- **English** (default)
- **Spanish** (comprehensive translation set)
- **Extensible system** for additional languages

## 🏆 Commission & Referral System

### Multi-level Marketing
- **3-level referral system** with configurable rates
- **Commission tracking** and automatic calculations
- **NFT pack rewards** based on commission amounts
- **Leaderboard system** (Gold Diggers Club)

### Features
- **Referral link generation**
- **Commission wallet** with balance tracking
- **Payout management** and history
- **Performance analytics**

## 📜 Certificate System

### Digital Certificates
- **Investment certificate generation**
- **Blockchain verification** with unique hashes
- **Template-based certificate design**
- **NFT conversion capability**

### Verification
- **Public certificate verification** via verification codes
- **QR code integration** for easy verification
- **Certificate authenticity** with cryptographic proofs

## 🎫 NFT & Coupon System

### NFT Integration
- **NFT pack purchases** with investment packages
- **Supply tracking** and availability management
- **NFT-based rewards** and bonuses

### Coupon System
- **Promotional coupon codes**
- **Discount application** on investments
- **Usage tracking** and expiration management

## 🔧 Development & Deployment

### Local Development
```bash
# Frontend
npm install
npm run dev  # Runs on localhost:5174

# Backend
# Start XAMPP (Apache + MySQL)
# Access APIs at http://localhost/api/
```

### Database Setup
- **Database**: `aureus_angels`
- **Auto-table creation** on first run
- **Migration system** for schema updates
- **Backup and optimization** scripts

### Configuration Files
- **Frontend**: `vite.config.ts`, `tailwind.config.ts`
- **Backend**: `api/config/database.php`
- **Environment**: `.env` files for production

## 🚀 Key Features Summary

1. **Enterprise Security**: MFA, rate limiting, CORS protection
2. **Advanced KYC**: Document upload + facial recognition
3. **Investment Platform**: 8 packages + NFT system
4. **Admin Dashboard**: Comprehensive management interface
5. **Live Chat**: Real-time customer support
6. **Multi-language**: Database-driven translations
7. **Commission System**: 3-level MLM with tracking
8. **Certificate Generation**: Blockchain-verified certificates
9. **Wallet Integration**: Multi-wallet crypto support
10. **Responsive Design**: Mobile-first approach

## 📊 Database Schema

### Core Tables
- `users` - User accounts and authentication
- `admin_users` - Admin accounts with roles
- `investment_packages` - Available investment plans
- `investments` - User investment records
- `kyc_documents` - KYC verification files
- `chat_sessions` - Live chat conversations
- `certificates` - Generated investment certificates
- `commission_transactions` - Referral commissions
- `translations` - Multi-language content

This codebase represents a production-ready investment platform with enterprise-grade security and comprehensive feature set.

## 📚 Additional Documentation Files

- `API_ENDPOINTS_REFERENCE.md` - Complete API endpoints documentation
- `FRONTEND_COMPONENTS_REFERENCE.md` - Detailed frontend component guide
- `DATABASE_SCHEMA_REFERENCE.md` - Database structure and relationships
- `SECURITY_GUIDE.md` - Security implementation details
- `DEPLOYMENT_GUIDE.md` - Production deployment instructions

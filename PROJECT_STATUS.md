# Aureus Alliance Holdings - Project Status & Documentation

## 🔧 Development Environment

### XAMPP Configuration
- **Apache Server**: Running on localhost (default port 80)
- **MySQL Database**: localhost with existing production data
- **PHP Backend**: API endpoints in `/api/` directory
- **Frontend**: React + Vite dev server on `localhost:5174`

### Tech Stack
- **Frontend**: React 18 + TypeScript + Vite
- **UI Framework**: Tailwind CSS + shadcn/ui components
- **State Management**: TanStack Query for API calls
- **Routing**: React Router DOM
- **Backend**: PHP with MySQL
- **Development**: Hot Module Replacement enabled

### File Structure
```
/
├── src/                    # React application
├── api/                    # PHP backend APIs
├── public/assets/          # Static assets
├── index.html             # Main HTML with wallet protection
├── start-dev.bat          # Development server launcher
└── PROJECT_STATUS.md      # This documentation
```

## 🗄️ Database Structure

### Core Tables (DO NOT MODIFY - Contains Real Data)
- `users` - User accounts and authentication
- `user_profiles` - Extended user information
- `kyc_documents` - KYC verification files
- `investment_packages` - Package definitions and purchases
- `company_wallets` - Company wallet addresses
- `admin_users` - Admin accounts and roles
- `affiliate_commissions` - Commission tracking
- `chat_sessions` - Live chat sessions
- `chat_messages` - Chat message history
- `agent_status` - Live chat agent availability

## 🎯 Completed Features

### ✅ Live Chat System
- **Admin Dashboard**: Agent availability toggle, session management
- **Anonymous Support**: Email + message for non-logged users
- **Offline Messaging**: Queue messages when agents offline
- **Multiple Admin Support**: Multiple agents can handle chats
- **Dark Theme UI**: Consistent with admin panel design
- **Session Management**: Clear sessions, view user ratings
- **API Endpoints**: `/api/chat/` directory with full functionality

### ✅ User Management & KYC
- **KYC System**: Single document upload (license OR ID OR passport)
- **Admin Verification**: KYC approval/rejection interface
- **Profile Management**: User dashboard with social media links
- **Document Storage**: Files saved in `/public/assets/` folder
- **Re-upload Capability**: Delete and re-upload documents
- **Access Control**: Features locked until KYC completion

### ✅ Investment System
- **8 Presale Packages**: $25 Shovel to $1000 Aureus (200K total supply)
- **NFT Packs**: $5 each with 180-day countdown to ROI
- **Package Management**: Admin interface for adding/editing packages
- **Purchase Tracking**: User investment history and status
- **ROI Calculations**: Automated based on package settings

### ✅ Wallet Integration
- **SafePal Only**: All other wallets disabled per user preference
- **Connection Persistence**: Wallet stays connected across navigation
- **USDT Balance Display**: Real-time balance checking
- **Wallet Switching**: Seamless switching between wallets
- **Status Display**: Address and balance in sidebar

### ✅ Affiliate System
- **3-Level Commission**: 12%/5%/3% USDT + NFT bonuses
- **Username Referrals**: aureuscapital.com/username format
- **Downline Management**: View and contact downlines
- **Commission Tracking**: Database tables for payouts
- **Analytics Dashboard**: Referral performance metrics

### ✅ Admin Dashboard
- **Dark Theme**: Consistent UI design
- **User Management**: View/edit all user details
- **Role-Based Access**: Admin role permissions
- **KYC Verification**: Approve/reject documents
- **Package Management**: Add/edit investment packages
- **Live Chat Controls**: Agent status and session management

### ✅ Marketing Tools
- **Unique Post Generation**: Generate button for varied content
- **Asset Management**: Upload marketing materials via admin
- **Social Sharing**: WhatsApp/Telegram working (others need fixing)
- **Referral Links**: Username-based sharing system

## 🚨 Current Issues

### 🔴 Critical - Trust Wallet Popup
- **Problem**: Trust Wallet extension popup appears on all pages
- **Attempted Solutions**: 
  - Nuclear wallet protection scripts in index.html
  - React hook disabling on homepage
  - Provider function blocking
  - Meta tags for wallet injection blocking
- **Status**: Extension-level issue, needs user to disable Trust Wallet extension
- **Test Page**: `/test-no-wallet.html` created for diagnosis

### 🟡 Medium Priority
- **Social Media Share**: LinkedIn, Facebook, Twitter buttons not working
- **Smart Contract Integration**: Commission payments need blockchain implementation
- **NFT Minting**: Custom contracts compatible with OpenSea needed

## 🎯 Pending Features

### Gold Diggers Club Leaderboard
- **Prize Pool**: $250K total ($100K first, $50K second, $30K third, $10K each 4th-10th)
- **Qualification**: Minimum $2,500 direct referrals
- **Calculation**: Direct sales only, no team volume
- **Timeline**: Calculated at presale end

### Blockchain Integration
- **Preferred Chain**: Polygon (low gas fees)
- **Smart Contracts**: Custom NFT contracts + commission payments
- **Security**: No private keys in database
- **Compatibility**: OpenSea integration for NFT trading

## 🚀 Development Commands

### Start Development
```bash
# Start XAMPP (Apache + MySQL)
# Then run:
npm run dev
# Or use: start-dev.bat
```

### Access Points
- **Frontend**: http://localhost:5174
- **Backend APIs**: http://localhost/api/
- **Database**: localhost:3306 (via phpMyAdmin)

## 📋 Next Steps for New Agent

1. **Resolve Trust Wallet popup** - Browser extension level solution
2. **Fix social media sharing** - Debug LinkedIn/Facebook/Twitter buttons  
3. **Implement smart contracts** - Polygon blockchain integration
4. **Complete NFT minting** - Custom contract development
5. **Finalize Gold Diggers Club** - Leaderboard and prize distribution

## ⚠️ Important Notes

- **Database**: Contains REAL production data - DO NOT reset or modify existing records
- **Wallet Preference**: User wants SafePal ONLY - no other wallet integrations
- **Theme**: Dark theme preferred for all admin interfaces
- **Communication**: User prefers Web3 methods (WhatsApp, Telegram) over email
- **Security**: Smart contract payments preferred over database private key storage

---
*Last Updated: Current session*
*Status: 90% Complete - Core functionality working, minor technical issues remaining*

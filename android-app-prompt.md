# Aureus Angel Alliance - Android App Development Prompt for Famous.ai

## 🎯 Project Overview

Create a professional Android investment app for **Aureus Angel Alliance**, a gold mining investment platform. The app should replicate the web platform's functionality while providing a native mobile experience optimized for investment management and portfolio tracking.

## 📱 App Identity & Branding

**App Name**: Aureus Angel Alliance
**Package Name**: com.aureus.angelalliance
**Target Audience**: Investors interested in gold mining opportunities
**App Category**: Finance & Investment
**Primary Colors**: Gold (#FFD700), Dark Blue (#1E3A8A), White (#FFFFFF)
**Logo**: Gold mining theme with angel wings (professional, trustworthy design)

## 🏗️ Core Architecture & Database Integration

### Database Connection
- **Backend API**: Connect to existing MySQL database via REST API
- **Database Host**: Custom XAMPP setup (port 3506)
- **Database Name**: aureus_angels
- **Authentication**: JWT token-based authentication
- **Real-time Sync**: Live data synchronization with web platform

### Key Database Tables to Integrate:
- `users` - User authentication and profiles
- `investment_packages` - Available investment plans
- `investments` - User investment records
- `payments` - Payment transactions and verification
- `referrals` - Referral system and commissions
- `kyc_documents` - KYC verification files
- `certificates` - Investment certificates
- `company_wallets` - Payment wallet addresses

## 🔐 Authentication System

### User Registration & Login
- Email/password authentication
- Secure password hashing (bcrypt)
- JWT token management
- Session persistence
- Biometric login support (fingerprint/face)
- Password reset via email
- Account verification system

### Security Features
- Rate limiting for login attempts
- Input validation and sanitization
- Secure API communication (HTTPS)
- Local data encryption
- Session timeout management

## 💰 Investment Features

### Investment Packages Display
- **Package Cards**: Show name, price, shares, annual dividends
- **Package Details**: Comprehensive information with bonuses
- **Real-time Data**: Live package availability and pricing
- **Package Categories**: Starter ($50), Bronze ($100), Silver ($250), Gold ($500), Platinum ($1000), Diamond ($2500), Elite ($5000), Ultimate ($10000)

### Investment Calculator
- **Share Calculator**: Calculate dividends based on shares owned
- **Mining Production**: Show gold production projections
- **Growth Projections**: 5-year dividend growth estimates
- **ROI Visualization**: Charts and graphs for returns

### Portfolio Management
- **Portfolio Overview**: Total investments, shares, projected dividends
- **Investment History**: Detailed transaction records
- **Performance Tracking**: Investment growth over time
- **Certificate Access**: Download and view investment certificates

## 💳 Payment Integration

### Payment Methods
- **Cryptocurrency**: Bitcoin, Ethereum, USDT, BNB support
- **Bank Transfer**: Manual bank transfer with proof upload
- **Payment Verification**: Automatic and manual verification system
- **Wallet Integration**: Connect to crypto wallets

### Payment Features
- **QR Code Generation**: For crypto payments
- **Payment Status Tracking**: Real-time payment verification
- **Transaction History**: Complete payment records
- **Manual Payment Upload**: Photo upload for bank transfers
- **Payment Confirmation**: Push notifications for successful payments

## 👥 Referral System

### Referral Management
- **Referral Link Generation**: Unique referral codes
- **Downline Tracking**: View referred users and their investments
- **Commission Tracking**: Real-time commission calculations
- **Leaderboard**: Top referrers and earnings
- **Social Sharing**: Share referral links via social media

### Commission Features
- **20% Direct Commission**: Immediate commission on referrals
- **Commission Wallet**: Separate wallet for commission earnings
- **Withdrawal System**: Request commission withdrawals
- **Reinvestment Option**: Reinvest commissions into new packages

## 📊 Dashboard & Analytics

### Main Dashboard
- **Portfolio Summary**: Total investments, shares, dividends
- **Recent Activity**: Latest investments and transactions
- **Quick Actions**: Invest, refer, withdraw buttons
- **Performance Charts**: Visual representation of portfolio growth
- **Mining Statistics**: Gold production and operational data

### Analytics Features
- **Investment Performance**: ROI calculations and projections
- **Dividend Tracking**: Quarterly and annual dividend estimates
- **Growth Metrics**: Portfolio growth over time
- **Comparison Tools**: Compare different investment packages

## 🎫 NFT & Certificate System

### Digital Assets
- **NFT Coupons**: Manage and redeem NFT coupons
- **Investment Certificates**: Generate and download certificates
- **Share Certificates**: Printable share ownership certificates
- **Digital Wallet**: Store and manage digital assets

## 📞 Support & Communication

### Customer Support
- **Live Chat**: In-app chat with support agents
- **Support Tickets**: Create and track support requests
- **FAQ Section**: Comprehensive help documentation
- **Contact Forms**: Direct communication with support team

### Notifications
- **Push Notifications**: Investment updates, payment confirmations
- **Email Notifications**: Important account activities
- **In-app Alerts**: System announcements and updates

## 🔍 KYC & Verification

### KYC System
- **Document Upload**: ID, passport, utility bills
- **Facial Recognition**: Biometric verification
- **KYC Status Tracking**: Verification progress
- **Document Management**: Secure document storage

## 🎮 Gamification Features

### Gold Diggers Club
- **Leaderboard**: Top investors and referrers
- **Achievement Badges**: Investment milestones
- **Competition System**: Monthly investment competitions
- **Rewards Program**: Bonus rewards for active users

## 📱 UI/UX Design Requirements

### Design Principles
- **Professional**: Clean, trustworthy financial app design
- **Intuitive**: Easy navigation for all user levels
- **Responsive**: Optimized for all screen sizes
- **Accessible**: Support for accessibility features
- **Fast**: Quick loading times and smooth animations

### Key Screens
1. **Splash Screen**: App logo with loading animation
2. **Onboarding**: Welcome screens explaining key features
3. **Login/Register**: Secure authentication screens
4. **Dashboard**: Main portfolio and statistics overview
5. **Investment Packages**: Browse and purchase packages
6. **Portfolio**: Detailed investment tracking
7. **Payments**: Payment methods and transaction history
8. **Referrals**: Referral management and tracking
9. **Profile**: User settings and KYC management
10. **Support**: Help and customer service

### Navigation
- **Bottom Navigation**: Dashboard, Invest, Portfolio, Referrals, Profile
- **Side Drawer**: Additional features and settings
- **Floating Action Button**: Quick invest button

## 🔧 Technical Specifications

### Development Requirements
- **Platform**: Android (API level 24+)
- **Language**: Kotlin/Java
- **Architecture**: MVVM with Repository pattern
- **Database**: Room for local caching
- **Networking**: Retrofit for API calls
- **Image Loading**: Glide for image handling
- **Charts**: MPAndroidChart for data visualization

### Performance Requirements
- **Loading Time**: Under 3 seconds for main screens
- **Offline Support**: Basic functionality without internet
- **Data Usage**: Optimized for minimal data consumption
- **Battery Optimization**: Efficient background processing

## 🚀 Key Features Priority

### Phase 1 (MVP)
1. User authentication and registration
2. Investment package browsing
3. Basic portfolio view
4. Payment integration
5. Referral system basics

### Phase 2 (Enhanced)
1. Advanced portfolio analytics
2. KYC verification system
3. Live chat support
4. Push notifications
5. Certificate generation

### Phase 3 (Advanced)
1. NFT management
2. Advanced gamification
3. Social sharing features
4. Offline capabilities
5. Advanced security features

## 📈 Success Metrics

### User Engagement
- Daily active users
- Session duration
- Feature usage statistics
- User retention rate

### Business Metrics
- Investment volume through app
- Payment success rate
- Referral conversion rate
- Customer satisfaction score

## 🔒 Security & Compliance

### Security Measures
- End-to-end encryption
- Secure API communication
- Local data protection
- Biometric authentication
- Fraud detection

### Compliance
- Financial regulations compliance
- Data protection (GDPR)
- App store guidelines
- Security best practices

## 📋 Additional Requirements

### Localization
- Multi-language support (English, Spanish)
- Currency formatting
- Regional payment methods
- Cultural adaptations

### Integration Points
- **Web Platform Sync**: Real-time data synchronization
- **Email System**: Automated email notifications
- **Payment Gateways**: Crypto and traditional payment integration
- **Social Media**: Sharing and referral integration

This Android app should provide a complete mobile investment experience that matches the functionality of the existing web platform while offering the convenience and features expected from a modern mobile application.

## 🎯 Famous.ai Specific Instructions

### App Generation Settings
- **App Type**: Finance & Investment
- **Complexity Level**: Advanced
- **Target Users**: Adult investors (18+)
- **Monetization**: Free app with investment features
- **Data Storage**: Cloud-based with local caching

### API Integration Requirements
```
Base URL: https://yourdomain.com/api/
Authentication: Bearer Token (JWT)
Content-Type: application/json

Key Endpoints:
- POST /users/auth.php (login/register)
- GET /packages/index.php (investment packages)
- GET /investments/history.php (user investments)
- POST /investments/process.php (create investment)
- GET /referrals/user-stats.php (referral data)
- POST /payments/manual-payment.php (payment processing)
```

### Database Schema Reference
```sql
-- Core tables structure for API integration
users (id, username, email, password_hash, created_at)
investment_packages (id, name, price, shares, annual_dividends, bonuses)
investments (id, user_id, package_id, amount, shares, status, created_at)
payments (id, user_id, investment_id, amount, method, status, proof_image)
referrals (id, referrer_id, referred_id, commission_amount, status)
```

### Business Logic Implementation
1. **Investment Calculation**: Annual dividends = (shares × gold_production × gold_price × profit_margin) / total_shares
2. **Commission Structure**: 20% direct commission on referral investments
3. **Mining Production**: 3,200 KG gold annually, 45% operational costs
4. **Share Distribution**: 1,400,000 total shares across all packages
5. **Phase System**: 20 phases total for mine development completion

### UI Component Specifications
- **Color Scheme**: Primary Gold (#FFD700), Secondary Navy (#1E3A8A), Success Green (#10B981), Error Red (#EF4444)
- **Typography**: Modern sans-serif, readable font sizes (14sp-24sp)
- **Icons**: Material Design icons with custom gold mining themed icons
- **Animations**: Smooth transitions, loading spinners, success animations
- **Charts**: Line charts for portfolio growth, pie charts for allocation

### Data Flow Architecture
1. **User Authentication**: Login → JWT Token → Store Locally → API Headers
2. **Investment Flow**: Browse Packages → Select → Payment → Confirmation → Portfolio Update
3. **Referral Flow**: Generate Link → Share → Track Clicks → Commission Calculation
4. **Payment Flow**: Select Method → Enter Details → Upload Proof → Verification → Confirmation

### Error Handling & Validation
- **Network Errors**: Offline mode with cached data
- **Input Validation**: Email format, password strength, amount limits
- **API Errors**: User-friendly error messages with retry options
- **Payment Errors**: Clear error states with support contact options

### Testing Requirements
- **Unit Tests**: Business logic and calculations
- **Integration Tests**: API connectivity and data flow
- **UI Tests**: User interaction flows
- **Performance Tests**: Loading times and memory usage

### Deployment Specifications
- **Minimum SDK**: Android 7.0 (API 24)
- **Target SDK**: Latest Android version
- **App Size**: Under 50MB initial download
- **Permissions**: Internet, Camera (KYC), Storage (documents), Biometric
- **Google Play**: Finance category, appropriate content rating

### Success Criteria
- **Functionality**: All web platform features working on mobile
- **Performance**: Under 3 second load times for main screens
- **User Experience**: Intuitive navigation, professional design
- **Security**: Secure data handling and API communication
- **Reliability**: 99.9% uptime, crash-free experience

Generate this app with professional-grade code quality, comprehensive error handling, and a user experience that matches modern fintech applications like Robinhood or Coinbase.

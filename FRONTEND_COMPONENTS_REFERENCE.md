# Frontend Components Reference - Aureus Angel Alliance

## 🏗️ Component Architecture

The frontend is built with React 18 + TypeScript using a modular component architecture with shadcn/ui as the base UI library.

## 📁 Component Directory Structure

```
src/components/
├── ui/                    # shadcn/ui base components
├── dashboard/             # Dashboard-specific components
├── investment/            # Investment-related components
├── chat/                 # Live chat components
├── admin/                # Admin panel components
├── auth/                 # Authentication components
├── kyc/                  # KYC verification components
├── user/                 # User profile components
├── responsive/           # Responsive layout components
└── debug/                # Development debug components
```

## 🎨 UI Components (`/src/components/ui/`)

### Base Components (shadcn/ui)
- `Button` - Customizable button with variants
- `Card` - Container component with header/content/footer
- `Input` - Form input with validation states
- `Label` - Form labels with accessibility
- `Badge` - Status indicators and tags
- `Dialog` - Modal dialogs and overlays
- `Tabs` - Tabbed navigation interface
- `Toast` - Notification system
- `Tooltip` - Hover information displays
- `Select` - Dropdown selection component
- `Checkbox` - Checkbox input with states
- `RadioGroup` - Radio button groups
- `Progress` - Progress bars and indicators
- `Skeleton` - Loading state placeholders

### Custom UI Extensions
- `Sonner` - Advanced toast notifications
- `ResponsiveLayout` - Mobile-first responsive wrapper
- `ErrorBoundary` - Error handling wrapper

## 🏠 Main Pages (`/src/pages/`)

### Core Pages
- `Index.tsx` - Landing page with hero, features, and CTA
- `Auth.tsx` - Login/register authentication page
- `Dashboard.tsx` - User dashboard with tabbed navigation
- `Investment.tsx` - Investment/participation page
- `Admin.tsx` - Admin panel with management tools
- `KYCVerification.tsx` - KYC document upload and verification
- `CertificateVerification.tsx` - Public certificate verification
- `NotFound.tsx` - 404 error page

### Dashboard Tabs
- User dashboard with multiple views:
  - Overview, Packages, History, Certificates
  - Portfolio, Affiliate, Commissions, Leaderboard
  - Support, Profile, Coupons, KYC Management

## 💰 Investment Components (`/src/components/investment/`)

### Package Management
- `FeaturedPlans.tsx` - Highlighted investment packages
- `AllPlans.tsx` - Complete list of available packages
- `DatabasePlanCard.tsx` - Individual package display card
- `InvestmentPackageCard.tsx` - Package selection interface

### Investment Process
- `InvestmentForm.tsx` - Investment amount and package selection
- `WalletConnection.tsx` - Crypto wallet integration
- `PaymentMethod.tsx` - Payment method selection
- `InvestmentConfirmation.tsx` - Transaction confirmation

### Investment Tracking
- `InvestmentHistory.tsx` - User investment history
- `InvestmentCountdown.tsx` - ROI countdown timers
- `PortfolioView.tsx` - Investment portfolio overview

## 🔍 KYC Components (`/src/components/kyc/`)

### Document Verification
- `KYCDocumentUpload.tsx` - Document upload interface
- `DocumentPreview.tsx` - Uploaded document preview
- `KYCStatus.tsx` - Verification status display

### Facial Recognition
- `FacialRecognition.tsx` - Advanced facial verification system
  - Real-time face detection using face-api.js
  - Liveness detection with movement challenges
  - Confidence scoring and verification thresholds
  - Step-by-step verification process

### KYC Management
- `KYCLevelsDashboard.tsx` - Multi-tier KYC level management
- `EnhancedKYCProfile.tsx` - Comprehensive KYC profile view

## 👥 Dashboard Components (`/src/components/dashboard/`)

### User Dashboard
- `UserDashboard.tsx` - Main dashboard overview
- `UserSidebar.tsx` - Navigation sidebar
- `AccountSettings.tsx` - User account management
- `EnhancedUserProfile.tsx` - Detailed user profile

### Investment Management
- `PackagesView.tsx` - Available packages display
- `InvestmentHistory.tsx` - Investment transaction history
- `PortfolioView.tsx` - Portfolio performance view
- `InvestmentCountdownList.tsx` - Active investment countdowns

### Financial Management
- `CommissionWallet.tsx` - Commission tracking and payouts
- `AffiliateView.tsx` - Referral system management
- `CouponRedemption.tsx` - Coupon code redemption

### Support & Communication
- `SupportView.tsx` - Customer support interface
- `CertificatesView.tsx` - User certificates display

## 🔐 Admin Components (`/src/components/admin/`)

### Admin Dashboard
- `AdminDashboard.tsx` - Main admin overview with statistics
- `AdminSidebar.tsx` - Admin navigation menu
- `AdminLayout.tsx` - Admin panel layout wrapper

### User Management
- `UserManagement.tsx` - User account administration
- `KYCManagement.tsx` - KYC document review and approval
- `AdminUserManagement.tsx` - Admin user management

### System Management
- `PackageManagement.tsx` - Investment package administration
- `WalletManagement.tsx` - Wallet configuration
- `SecurityMonitoring.tsx` - Security logs and monitoring
- `SystemSettings.tsx` - System configuration

### Certificate Management
- `CertificateGenerator.tsx` - Certificate creation interface
- `CertificateTemplates.tsx` - Certificate template management

## 💬 Chat Components (`/src/components/chat/`)

### Live Chat System
- `LiveChat.tsx` - Main chat interface with real-time messaging
- `ChatWindow.tsx` - Chat conversation display
- `MessageInput.tsx` - Message composition interface
- `ChatSession.tsx` - Chat session management
- `AgentStatus.tsx` - Support agent status display

### Chat Features
- Real-time message polling
- Guest chat support for non-registered users
- File attachment support
- Message history and session persistence
- Agent online/offline status

## 🔐 Authentication Components (`/src/components/auth/`)

### User Authentication
- `LoginForm.tsx` - User login interface
- `RegisterForm.tsx` - User registration form
- `ForgotPassword.tsx` - Password reset interface
- `AuthLayout.tsx` - Authentication page layout

### Security Features
- Form validation and error handling
- Rate limiting integration
- CAPTCHA support
- Multi-factor authentication UI

## 👤 User Components (`/src/components/user/`)

### Profile Management
- `UserProfile.tsx` - User profile display and editing
- `ProfileSettings.tsx` - Account settings management
- `SocialMediaLinks.tsx` - Social media profile links
- `CouponRedemption.tsx` - Coupon code redemption interface

## 📱 Responsive Components (`/src/components/responsive/`)

### Layout Management
- `ResponsiveLayout.tsx` - Mobile-first responsive wrapper
- `MobileNavigation.tsx` - Mobile navigation menu
- `TabletLayout.tsx` - Tablet-specific layout adjustments

## 🌍 Translation Components

### Internationalization
- `DatabaseTranslator.tsx` - Database-driven translation system
- `RealWorkingTranslator.tsx` - Advanced translation with fallbacks
- `LanguageSwitcher.tsx` - Language selection interface
- `T` component - Translation wrapper for text content

### Translation Features
- Dynamic language switching
- Fallback to English for missing translations
- Context-aware translations
- Admin translation management

## 🔧 Debug Components (`/src/components/debug/`)

### Development Tools
- `SimpleDebugConsole.tsx` - Debug information display
- `DebugButton.tsx` - Debug panel toggle
- `DebugTestButton.tsx` - API testing interface
- `ProfileTestButton.tsx` - Profile API testing
- `AdminAuthTestButton.tsx` - Admin authentication testing
- `KycTestButton.tsx` - KYC system testing

## 🎯 Specialized Components

### Landing Page Components
- `Hero.tsx` - Main hero section with CTA
- `HowItWorks.tsx` - Process explanation
- `RewardsCalculator.tsx` - ROI calculation tool
- `Benefits.tsx` - Platform benefits display
- `AboutProject.tsx` - Project information
- `CallToAction.tsx` - Conversion-focused CTA sections
- `Footer.tsx` - Site footer with links

### Commission System
- `SimpleNetworkerCommission.tsx` - Commission structure display
- `SimpleRewards.tsx` - Rewards system explanation
- `SimpleGoldDiggersClub.tsx` - Leaderboard and competition

### Navigation
- `Navbar.tsx` - Main site navigation
- `ConditionalLiveChat.tsx` - Conditional chat widget display

## 🔗 Component Integration

### Context Providers
- `UserProvider` - User authentication and profile state
- `TranslationProvider` - Multi-language support
- `DebugProvider` - Development debugging context

### Custom Hooks
- `useUser()` - User authentication and profile management
- `useTranslation()` - Translation system integration
- `useInvestmentPackages()` - Investment package data
- `useToast()` - Notification system

### API Integration
- TanStack Query for data fetching
- Automatic error handling and retry logic
- Optimistic updates for better UX
- Real-time data synchronization

## 🎨 Styling & Theming

### Design System
- **Dark theme** as primary design
- **Gold accent colors** for premium feel
- **Mobile-first** responsive design
- **Consistent spacing** using Tailwind CSS
- **Accessible** color contrasts and focus states

### Component Variants
- Multiple button styles (primary, secondary, destructive)
- Card variants for different content types
- Badge colors for status indicators
- Input states for validation feedback

This component architecture provides a scalable, maintainable, and feature-rich frontend for the investment platform.

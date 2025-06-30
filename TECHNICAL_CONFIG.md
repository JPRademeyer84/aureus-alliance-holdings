# Technical Configuration & Setup Guide

## 🔧 XAMPP Configuration

### Required XAMPP Components
- **Apache**: Web server for PHP backend
- **MySQL**: Database server
- **PHP**: Backend API processing
- **phpMyAdmin**: Database management interface

### XAMPP Settings
- **Apache Port**: 80 (default)
- **MySQL Port**: 3306 (default)
- **Document Root**: XAMPP htdocs directory
- **PHP Version**: 7.4+ recommended
- **MySQL**: 5.7+ or 8.0+

### Database Connection
```php
// Standard XAMPP database connection
$host = 'localhost';
$username = 'root';
$password = ''; // Default XAMPP has no password
$database = 'aureus_alliance'; // Your database name
```

## 🚀 Development Server Setup

### Frontend (Vite + React)
```bash
# Install dependencies
npm install

# Start development server
npm run dev
# Server will auto-assign port (usually 5174)
```

### Backend (PHP + Apache)
- Ensure XAMPP Apache is running
- PHP files in `/api/` directory are accessible via `http://localhost/api/`
- Database accessible via `http://localhost/phpmyadmin`

### Development Workflow
1. Start XAMPP (Apache + MySQL)
2. Run `npm run dev` for frontend
3. Frontend: `http://localhost:5174`
4. Backend APIs: `http://localhost/api/`

## 📁 Critical File Locations

### Frontend Configuration
- **Main App**: `src/App.tsx`
- **Routing**: React Router in `src/App.tsx`
- **API Calls**: TanStack Query hooks in `src/hooks/`
- **Components**: `src/components/` and `src/pages/`
- **Styles**: `src/index.css` (Tailwind CSS)

### Backend API Structure
```
api/
├── auth/                 # Authentication endpoints
├── users/               # User management
├── investment/          # Investment packages
├── kyc/                # KYC document handling
├── chat/               # Live chat system
├── admin/              # Admin panel APIs
├── affiliate/          # Affiliate system
└── config/             # Database connections
```

### Asset Management
- **User Uploads**: `public/assets/uploads/`
- **KYC Documents**: `public/assets/kyc/`
- **Marketing Assets**: `public/assets/marketing/`
- **Static Files**: `public/` directory

## 🔐 Security Configurations

### Wallet Protection (Current Implementation)
```html
<!-- In index.html -->
<script>
  // Nuclear wallet protection
  window.NUCLEAR_WALLET_BLOCK = true;
  // Blocks all wallet providers globally
</script>
```

### API Security
- **CORS**: Configured for localhost development
- **Input Validation**: PHP backend validates all inputs
- **SQL Injection Protection**: Prepared statements used
- **File Upload Security**: Type and size validation

### Database Security
- **Real Data Protection**: Existing records must not be modified
- **Backup Strategy**: Regular backups recommended
- **Access Control**: Admin roles implemented

## 🎨 UI/UX Configuration

### Theme Settings
- **Primary Theme**: Dark mode preferred
- **Color Scheme**: Tailwind CSS custom colors
- **Components**: shadcn/ui component library
- **Responsive**: Mobile-first design approach

### Component Structure
```
src/components/
├── ui/                  # shadcn/ui base components
├── dashboard/           # Dashboard-specific components
├── investment/          # Investment-related components
├── chat/               # Live chat components
├── admin/              # Admin panel components
└── auth/               # Authentication components
```

## 🔌 API Endpoints Reference

### Authentication
- `POST /api/auth/login.php`
- `POST /api/auth/register.php`
- `POST /api/auth/logout.php`

### User Management
- `GET /api/users/profile.php`
- `PUT /api/users/update.php`
- `POST /api/users/kyc-upload.php`

### Investment System
- `GET /api/investment/packages.php`
- `POST /api/investment/purchase.php`
- `GET /api/investment/history.php`

### Live Chat
- `GET /api/chat/agent-status.php`
- `POST /api/chat/send-message.php`
- `GET /api/chat/messages.php`

### Admin Panel
- `GET /api/admin/users.php`
- `PUT /api/admin/kyc-verify.php`
- `POST /api/admin/packages.php`

## 🐛 Known Issues & Solutions

### Trust Wallet Popup Issue
- **Problem**: Extension injects before our blocking scripts
- **Current Status**: Nuclear protection implemented but ineffective
- **Solution**: User must disable Trust Wallet browser extension
- **Test Page**: `/test-no-wallet.html` for diagnosis

### Social Media Sharing
- **Working**: WhatsApp, Telegram
- **Not Working**: LinkedIn, Facebook, Twitter
- **Location**: Marketing tools component
- **Next Step**: Debug share button implementations

### Development Server
- **Port Conflicts**: Vite auto-assigns available ports
- **Hot Reload**: Working correctly
- **API Proxy**: Configured for localhost XAMPP

## 📊 Database Schema Notes

### Critical Tables
```sql
-- Users and authentication
users (id, username, email, password_hash, created_at)
user_profiles (user_id, first_name, last_name, phone, social_media)

-- KYC system
kyc_documents (id, user_id, document_type, file_path, status, verified_at)

-- Investment system
investment_packages (id, name, price, description, roi_percentage, duration_days)
user_investments (id, user_id, package_id, amount, purchase_date, status)

-- Affiliate system
affiliate_commissions (id, user_id, referrer_id, level, amount, commission_type)

-- Live chat
chat_sessions (id, user_id, agent_id, status, created_at)
chat_messages (id, session_id, sender_type, message, timestamp)
agent_status (id, admin_id, status, last_active)
```

## 🚀 Deployment Considerations

### Production Environment
- **Web Server**: Apache or Nginx
- **PHP**: 7.4+ with required extensions
- **Database**: MySQL 5.7+ or 8.0+
- **SSL**: HTTPS required for wallet connections
- **File Permissions**: Proper upload directory permissions

### Environment Variables
- Database credentials
- API keys for external services
- Wallet provider configurations
- CORS settings for production domain

---
*This configuration guide ensures seamless project continuation for any new developer or agent.*

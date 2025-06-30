# API Endpoints Reference - Aureus Angel Alliance

## Base URL
- **Development**: `http://localhost/aureus-angel-alliance/api/`
- **Production**: `https://your-domain.com/api/`

## 🔐 Authentication Endpoints

### User Authentication
- `POST /users/auth.php` - User login/register/logout
- `POST /auth/login.php` - User login
- `POST /auth/register.php` - User registration
- `POST /auth/logout.php` - User logout
- `POST /auth/forgot-password.php` - Password reset

### Admin Authentication
- `POST /admin/auth.php` - Admin login/logout
- `GET /admin/auth.php` - Check admin session status

## 👥 User Management

### User Profile
- `GET /users/profile.php` - Get user profile
- `POST /users/profile.php` - Update user profile
- `POST /users/save-profile.php` - Save profile changes

### User Data
- `GET /users/index.php` - Get user data
- `POST /users/index.php` - Update user data

## 📋 KYC (Know Your Customer)

### Document Management
- `POST /kyc/upload.php` - Upload KYC documents
- `GET /kyc/documents.php` - Get user's KYC documents
- `DELETE /kyc/documents.php` - Delete KYC document

### Facial Verification
- `POST /kyc/facial-verification.php` - Submit facial verification
- `GET /kyc/levels.php` - Get KYC level requirements

### Admin KYC Management
- `GET /admin/kyc-management.php` - Get pending KYC reviews
- `POST /admin/kyc-management.php` - Approve/reject KYC documents
- `GET /admin/kyc-levels.php` - Manage KYC level settings

## 💰 Investment/Participation System

### Packages
- `GET /packages/index.php` - Get all investment packages
- `POST /packages/index.php` - Create new package (admin)
- `PUT /packages/index.php` - Update package (admin)
- `DELETE /packages/index.php` - Delete package (admin)

### Investments
- `POST /investments/process.php` - Process new investment
- `POST /investments/create.php` - Create investment record
- `GET /investments/history.php` - Get user investment history
- `GET /investments/list.php` - Get all investments (admin)
- `GET /investments/countdown.php` - Get investment countdown data

### Participations
- `POST /participations/create.php` - Create participation record
- `GET /participations/history.php` - Get participation history

## 💳 Wallet & Payment System

### Wallets
- `GET /wallets/index.php` - Get configured wallets
- `POST /wallets/index.php` - Add/update wallet (admin)
- `GET /wallets/active.php` - Get active wallets

### Payments
- `GET /payments/country-detection.php` - Detect user country for payment methods
- `POST /payments/bank-transfer.php` - Process bank transfer
- `POST /payments/manual-verification.php` - Manual payment verification (admin)

## 💬 Chat System

### Chat Sessions
- `GET /chat/sessions.php` - Get user chat sessions
- `POST /chat/sessions.php` - Create new chat session

### Messages
- `GET /chat/messages.php` - Get chat messages
- `POST /chat/messages.php` - Send chat message
- `GET /chat/offline-messages.php` - Get offline messages

### Agent Management
- `GET /chat/agent-status.php` - Get agent online status
- `POST /chat/agent-status.php` - Update agent status

## 🏆 Commission & Referral System

### Commissions
- `GET /commissions/index.php` - Get user commissions
- `POST /commissions/calculate.php` - Calculate commissions
- `GET /commissions/history.php` - Get commission history

### Referrals
- `GET /referrals/index.php` - Get referral data
- `POST /referrals/track.php` - Track referral activity

### Leaderboard
- `GET /leaderboard/gold-diggers-club.php` - Get leaderboard data
- `POST /leaderboard/prize-distribution.php` - Distribute prizes (admin)

## 📜 Certificate System

### Certificate Generation
- `POST /admin/certificate-generator.php` - Generate certificates (admin)
- `GET /admin/certificate-templates.php` - Manage certificate templates

### Certificate Verification
- `GET /certificates/verify.php` - Verify certificate by code
- `GET /users/certificates.php` - Get user certificates

## 🌍 Translation System

### Translations
- `GET /translations/index.php` - Get translations for language
- `POST /translations/index.php` - Add/update translations (admin)
- `GET /translations/keys.php` - Get all translation keys

## 🔧 Admin Management

### Dashboard
- `GET /admin/dashboard-stats.php` - Get dashboard statistics
- `GET /admin/manage-users.php` - Get user management data
- `POST /admin/manage-users.php` - Manage user accounts

### Admin Users
- `GET /admin/manage-admins.php` - Get admin users
- `POST /admin/manage-admins.php` - Create/update admin users

### System Management
- `POST /admin/clear-sessions.php` - Clear user sessions
- `GET /admin/security-monitoring.php` - Get security logs
- `GET /admin/reviews.php` - Get system reviews

## 🔒 Security Endpoints

### Security Monitoring
- `GET /security/comprehensive-security-test.php` - Run security tests (admin)
- `POST /security/cors-security-fixer.php` - Fix CORS issues (admin)
- `GET /admin/cors-security.php` - CORS security management
- `GET /admin/api-security-management.php` - API security management

## 📞 Contact & Support

### Contact Messages
- `GET /contact/messages.php` - Get contact messages (admin)
- `POST /contact/messages.php` - Send contact message

## 🎫 Coupons & NFT System

### Coupons
- `GET /coupons/index.php` - Get available coupons
- `POST /coupons/redeem.php` - Redeem coupon code
- `POST /coupons/create.php` - Create coupon (admin)

### NFT System
- `GET /nft/packs.php` - Get NFT pack information
- `POST /nft/purchase.php` - Purchase NFT packs

## 🔧 Utility Endpoints

### Database Operations
- `POST /setup/database-setup.php` - Initialize database
- `POST /migrations/run.php` - Run database migrations

### Testing & Debug
- `GET /test/test-session.php` - Test session functionality
- `GET /debug/debug-session.php` - Debug session data
- `POST /test/test-user-api.php` - Test user API endpoints

## 📊 Request/Response Format

### Standard Request Headers
```
Content-Type: application/json
Accept: application/json
```

### Standard Response Format
```json
{
  "success": true|false,
  "message": "Response message",
  "data": {...},
  "error": "Error message (if applicable)",
  "request_id": "unique-request-id"
}
```

### Authentication
- **Session-based**: Automatic via cookies
- **API Key**: `X-API-Key` header (for API access)
- **Admin**: Requires admin session + MFA for sensitive operations

### Rate Limiting
- **User endpoints**: 100-500 requests per hour
- **Admin endpoints**: Higher limits based on role
- **Public endpoints**: 10-20 requests per hour

### Error Codes
- `200` - Success
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `429` - Rate Limited
- `500` - Internal Server Error

## 🔐 Security Features

### Request Validation
- Input sanitization and validation
- SQL injection prevention
- XSS protection
- CSRF token validation

### Access Control
- Role-based permissions
- Session timeout management
- IP-based restrictions
- Abuse detection and blocking

### Monitoring
- Request logging
- Security event tracking
- Performance monitoring
- Error reporting

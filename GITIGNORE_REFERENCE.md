# .gitignore Reference Guide

This document explains the comprehensive .gitignore file created for the Aureus Angel Alliance project.

## Categories Covered

### 🟢 Node.js / Frontend
- `node_modules/` - Dependencies
- `dist/`, `build/` - Build outputs
- `.env*` - Environment variables
- `.vite/` - Vite cache
- TypeScript cache files

### 🔵 PHP / Backend
- `vendor/` - Composer dependencies
- `*.php.bak`, `*.php.tmp` - Temporary PHP files
- PHP session and log files
- XAMPP specific directories

### 🟡 Database
- `*.sql`, `*.dump` - Database backups
- Database configuration files
- Migration logs
- Connection files

### 🔴 Security & Sensitive Files
- API keys and secrets
- SSL certificates (`.pem`, `.key`, `.crt`)
- JWT secrets and encryption keys
- OAuth configurations
- Payment gateway configs
- Wallet data and private keys

### 🟣 Uploads & User Content
- `uploads/`, `files/`, `media/`
- KYC documents and facial verification
- User-generated content
- Temporary upload directories

### 🟠 Logs & Monitoring
- Application logs (`*.log`)
- Security logs
- Performance monitoring
- Analytics data

### 📁 Project Specific
- Investment platform data
- Admin panel logs
- Commission calculations
- Competition results
- Financial reports

## Important Notes

### ✅ Files NOT Ignored (Kept in Git)
- `.env.example` - Template for environment variables
- `.vscode/extensions.json` - VS Code extensions configuration
- Source code files (`.php`, `.ts`, `.tsx`, `.js`)
- Configuration templates

### ⚠️ Security Considerations
The .gitignore prevents committing:
- Database credentials
- API keys and secrets
- Private encryption keys
- User uploaded files
- Financial data
- Personal information (KYC documents)

### 🔧 Development Tools
Covers common tools used in the project:
- XAMPP development environment
- Vite build system
- Composer (PHP)
- npm/yarn/pnpm
- Various IDEs and editors

## Recommendations

1. **Review before committing**: Always run `git status` to verify no sensitive files are being committed
2. **Use .env.example**: Create template files for environment variables
3. **Regular cleanup**: Periodically clean ignored cache and temporary files
4. **Team coordination**: Ensure all team members understand what's being ignored

## Quick Commands

```bash
# Check what files would be committed
git status

# See what's being ignored
git ls-files --others --ignored --exclude-standard

# Add all non-ignored files
git add .

# Force add an ignored file (use carefully)
git add -f filename
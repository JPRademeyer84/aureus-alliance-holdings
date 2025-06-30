# Production Deployment Guide

## Overview

This guide will help you deploy the Aureus Angel Alliance application to a live server with a production MySQL database.

## Deployment Options

### Option 1: Shared Hosting (Recommended for beginners)
- **Providers:** Hostinger, Bluehost, SiteGround, GoDaddy
- **Requirements:** PHP 7.4+, MySQL 5.7+, Apache/Nginx
- **Cost:** $3-10/month

### Option 2: VPS/Cloud Server
- **Providers:** DigitalOcean, Linode, Vultr, AWS EC2
- **Requirements:** Full server control
- **Cost:** $5-20/month

### Option 3: Free Hosting (Limited)
- **Providers:** 000webhost, InfinityFree, Heroku
- **Limitations:** Limited resources, may have ads
- **Cost:** Free

## Pre-Deployment Checklist

### 1. Build the React Application
```bash
# In your project directory
npm run build
```

### 2. Prepare Files for Upload
- `dist/` folder (React build output)
- `api/` folder (PHP backend)
- `database/` folder (SQL files)

### 3. Database Preparation
- Export your local database
- Prepare production database credentials

## Deployment Steps

### Step 1: Choose a Hosting Provider

**Recommended: Hostinger (Affordable & Reliable)**
1. Go to [hostinger.com](https://hostinger.com)
2. Choose "Web Hosting" plan
3. Select domain name
4. Complete purchase and setup

### Step 2: Setup Domain & Hosting

1. **Purchase domain** (e.g., aureusangels.com)
2. **Setup hosting account**
3. **Access cPanel/hosting panel**

### Step 3: Upload Files

#### Upload React Build (Frontend)
1. Upload contents of `dist/` folder to `public_html/` (root directory)
2. Ensure `index.html` is in the root

#### Upload PHP API (Backend)
1. Create `api/` folder in `public_html/`
2. Upload all files from your local `api/` folder
3. Maintain folder structure:
   ```
   public_html/
   ├── index.html (from dist/)
   ├── assets/ (from dist/)
   ├── api/
   │   ├── config/
   │   ├── admin/
   │   ├── packages/
   │   ├── investments/
   │   └── wallets/
   ```

### Step 4: Setup Production Database

#### Create Database
1. Access **phpMyAdmin** or **MySQL Databases** in cPanel
2. Create new database (e.g., `username_aureus`)
3. Create database user with full privileges
4. Note down:
   - Database name
   - Username
   - Password
   - Host (usually `localhost`)

#### Import Database Schema
1. Open phpMyAdmin
2. Select your database
3. Go to "Import" tab
4. Upload `database/init.sql`
5. Execute import

### Step 5: Update Configuration

Update `api/config/database.php` with production credentials:
```php
private $host = "localhost";           // Or provided host
private $db_name = "username_aureus";  // Your database name
private $username = "db_username";     // Database username
private $password = "db_password";     // Database password
```

### Step 6: Deploy Using Automated Script

Run the deployment script to prepare all files:

```bash
npm run deploy
```

This will:
- Build the React application
- Create a `deploy/` folder with all necessary files
- Generate deployment instructions

### Step 7: Upload Files

1. **Upload Frontend & API:**
   - Upload all contents of `deploy/public_html/` to your hosting root directory
   - Ensure the file structure is maintained

2. **Set File Permissions:**
   - Directories: 755
   - PHP files: 644
   - .htaccess: 644

### Step 8: Test Your Live Application

1. **Test API Endpoints:**
   - `https://yourdomain.com/api/packages/` - Should return JSON
   - `https://yourdomain.com/api/admin/auth` - Should handle POST requests

2. **Test Frontend:**
   - `https://yourdomain.com/` - Main application
   - `https://yourdomain.com/admin` - Admin login
   - `https://yourdomain.com/invest` - Investment page

## Quick Deployment Checklist

- [ ] Purchase hosting and domain
- [ ] Run `npm run deploy` locally
- [ ] Upload `deploy/public_html/` contents to hosting
- [ ] Create production database
- [ ] Import `deploy/database/init.sql`
- [ ] Update database credentials in `api/config/database.php`
- [ ] Test all functionality
- [ ] Update DNS if needed

## Recommended Hosting Providers

### Budget-Friendly ($3-5/month)
- **Hostinger** - Great performance, easy setup
- **Namecheap** - Reliable, good support
- **SiteGround** - WordPress optimized but works for any PHP

### Premium ($10-20/month)
- **DigitalOcean** - Full VPS control
- **Linode** - Developer-friendly
- **Vultr** - High performance

### Free Options (Limited)
- **000webhost** - Basic PHP/MySQL hosting
- **InfinityFree** - No ads, decent resources

## Environment Configuration

The application automatically detects production vs development:

- **Development:** `localhost` URLs
- **Production:** Uses current domain

No manual configuration needed for API URLs!

## Security Considerations

1. **Database Security:**
   - Use strong database passwords
   - Limit database user privileges
   - Enable SSL if available

2. **File Security:**
   - Set proper file permissions
   - Keep database credentials secure
   - Regular backups

3. **Admin Security:**
   - Change default admin password
   - Use HTTPS for admin access
   - Monitor admin login attempts

## Troubleshooting Production Issues

### Common Problems

1. **"500 Internal Server Error"**
   - Check PHP error logs
   - Verify file permissions
   - Ensure PHP 7.4+ is enabled

2. **Database Connection Failed**
   - Verify database credentials
   - Check database server status
   - Ensure database exists

3. **API Returns 404**
   - Check .htaccess file uploaded
   - Verify mod_rewrite is enabled
   - Check file paths

4. **CORS Errors**
   - Ensure .htaccess has CORS headers
   - Check if hosting supports .htaccess
   - Contact hosting support if needed

## Performance Optimization

1. **Enable Gzip Compression**
2. **Use CDN for static assets**
3. **Enable browser caching**
4. **Optimize database queries**
5. **Use production PHP settings**

## Monitoring & Maintenance

1. **Regular Backups:**
   - Database backups weekly
   - File backups monthly
   - Test restore procedures

2. **Updates:**
   - Monitor for security updates
   - Update dependencies regularly
   - Test updates in staging first

3. **Performance Monitoring:**
   - Monitor page load times
   - Check database performance
   - Monitor server resources

## Support Resources

- **Hosting Documentation:** Check your provider's PHP/MySQL guides
- **Community Forums:** Stack Overflow, Reddit r/webdev
- **Professional Help:** Consider hiring a developer for complex setups

## Cost Estimation

### Monthly Costs
- **Domain:** $1-2/month (annual payment)
- **Hosting:** $3-20/month depending on provider
- **SSL Certificate:** Usually free with hosting
- **Total:** $4-22/month

### One-time Costs
- **Domain Registration:** $10-15/year
- **Setup Time:** 2-4 hours for first deployment

## Success Metrics

Your deployment is successful when:
- [ ] Main website loads at your domain
- [ ] Admin login works with correct credentials
- [ ] Investment packages display correctly
- [ ] Wallet connection functions properly
- [ ] Investment processing completes
- [ ] Admin dashboard manages data correctly

## Next Steps After Deployment

1. **Custom Domain Email:** Setup professional email addresses
2. **Analytics:** Add Google Analytics for visitor tracking
3. **SEO:** Optimize for search engines
4. **Marketing:** Promote your investment platform
5. **Legal:** Ensure compliance with financial regulations

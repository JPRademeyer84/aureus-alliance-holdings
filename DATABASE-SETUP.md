# Database Setup Guide

## 🎯 What This Does

After setting up the database, your admin panel will:
- ✅ **Save real data to MySQL database**
- ✅ **Update the main website immediately**
- ✅ **Persist data between sessions**
- ✅ **No more mock data!**

## 📋 Prerequisites

1. **XAMPP is installed and running**
   - Apache server: ✅ Running
   - MySQL database: ✅ Running

2. **phpMyAdmin is accessible**
   - Go to: http://localhost/phpmyadmin
   - Should load without errors

## 🚀 Quick Setup

### Option 1: Automated Setup
1. **Double-click** `setup-database.bat`
2. **Follow the instructions** that appear
3. **Import the database file** when prompted

### Option 2: Manual Setup

1. **Open phpMyAdmin**
   - Go to: http://localhost/phpmyadmin

2. **Import Database**
   - Click "Import" tab
   - Click "Choose File"
   - Select: `database/init.sql`
   - Click "Go"

3. **Verify Setup**
   - You should see "aureus_angels" database created
   - Check that tables are created with data

## 🗄️ Database Structure

The database includes:

### Tables Created:
- `admin_users` - Admin login credentials
- `investment_packages` - Investment packages (editable via admin)
- `investment_wallets` - Payment wallet addresses
- `aureus_investments` - User investment submissions
- `wallet_connections` - Wallet connection logs

### Default Data:
- **Admin User**: username `admin`, password `Underdog8406155100085@123!@#`
- **7 Investment Packages**: Starter, Bronze, Silver, Gold, Platinum, Diamond, Obsidian
- **3 Wallet Addresses**: Ethereum, Polygon, BSC

## ✅ Testing the Setup

1. **Test API Connection**
   - Go to: http://localhost/aureus-angel-alliance/api/test.php
   - Should show green checkmarks for database and tables
   - If you see errors, check XAMPP and database setup

2. **Test API Endpoints**
   - Packages: http://localhost/aureus-angel-alliance/api/packages/index.php
   - Should return JSON with investment packages

3. **Start the development server**
   ```bash
   npm run dev
   ```

4. **Test Admin Panel**
   - Go to: http://localhost:5174/admin
   - Login with: admin / Underdog8406155100085@123!@#
   - Try adding/editing a package
   - Check if changes appear on main site

5. **Test Main Site**
   - Go to: http://localhost:5174
   - Check if investment packages load
   - Try the investment calculator

## 🔧 Troubleshooting

### "Connection refused" errors
- Make sure XAMPP MySQL is running
- Check if port 3306 is available

### "Database not found" errors
- Re-import the `database/init.sql` file
- Make sure the import completed successfully

### "Access denied" errors
- Check MySQL credentials in `api/config/database.php`
- Default: host=localhost, user=root, password=(empty)

### Admin login fails
- Make sure database was imported correctly
- Check if `admin_users` table exists with data

## 🎉 Success!

Once setup is complete:
- Your admin panel will save real data
- Changes will immediately update the website
- All data will persist between sessions
- You have a fully functional database-driven site!

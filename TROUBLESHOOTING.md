# Troubleshooting Guide

## 🚨 "Failed to save package" Error

This error means the admin panel cannot connect to the database API.

### ✅ Quick Fix:

1. **Run the setup script**:
   ```
   Double-click: setup-database.bat
   ```

2. **Make sure XAMPP is running**:
   - Apache: ✅ Running (green)
   - MySQL: ✅ Running (green)

3. **Test the API connection**:
   - Go to: http://localhost/aureus-angel-alliance/api/test.php
   - Should show green checkmarks

4. **Import the database**:
   - Go to: http://localhost/phpmyadmin
   - Import: `database/init.sql`

### 🔍 Detailed Steps:

#### Step 1: Check XAMPP
- Open XAMPP Control Panel
- Make sure Apache and MySQL are both running (green)
- If not running, click "Start" for both

#### Step 2: Test API
- Go to: http://localhost/aureus-angel-alliance/api/test.php
- You should see:
  - ✅ Database connection successful!
  - ✅ All tables exist with data

#### Step 3: Test Database
- Go to: http://localhost/phpmyadmin
- Check if "aureus_angels" database exists
- If not, import `database/init.sql`

#### Step 4: Test Admin Panel
- Go to: http://localhost:5174/admin
- Login: admin / [Check server logs for temporary password]
- **SECURITY**: Change password immediately on first login
- Try adding a package

## 🚨 Other Common Issues

### "Cannot connect to API"
- XAMPP Apache is not running
- API files not copied to htdocs
- Run `setup-database.bat` again

### "Database connection failed"
- XAMPP MySQL is not running
- Database not imported
- Check phpMyAdmin access

### "Admin login failed"
- Database not set up correctly
- Re-import `database/init.sql`
- Check if admin_users table exists

### "Packages not loading on main site"
- Same as "Failed to save package"
- API connection issue
- Follow the quick fix steps above

## 🎯 Success Indicators

When everything is working correctly:

1. **API Test Page**: http://localhost/aureus-angel-alliance/api/test.php
   - Shows all green checkmarks

2. **Admin Panel**: http://localhost:5174/admin
   - Can login successfully
   - Can add/edit packages without errors

3. **Main Site**: http://localhost:5174
   - Investment packages load from database
   - Changes in admin panel appear immediately

## 📞 Still Having Issues?

1. Check the browser console (F12) for error messages
2. Check XAMPP error logs
3. Make sure no antivirus is blocking XAMPP
4. Try restarting XAMPP completely

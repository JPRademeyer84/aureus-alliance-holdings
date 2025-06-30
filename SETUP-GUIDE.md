# Aureus Alliance Holdings - Setup Guide

## ✅ QUICK START (Recommended)

### Step 1: Database Setup (One-time only)
1. **Make sure XAMPP is running** (Apache + MySQL)
2. **Double-click** `setup-database.bat`
3. **Follow the instructions** to import the database

### Step 2: Start the Server
1. **Double-click** `start-dev.bat` in the project folder
2. Your browser will automatically open to the site
3. That's it! 🎉

### Option 2: Command Line
```bash
npm run dev
```

## 🌐 Your Site URLs
- **Local Development**: http://localhost:5174 (or next available port)
- **Admin Panel**: http://localhost:5174/admin
  - Username: `admin`
  - Password: `Underdog8406155100085@123!@#`
  - ✅ **Admin login now works with mock authentication!**

## 🔧 What's Been Fixed

### ✅ No More Port Conflicts
- Changed from port 8080 to 5174 (Vite default)
- No more conflicts with XAMPP Apache
- Auto-finds next available port if 5174 is busy

### ✅ Real Database Integration
- ✅ **Admin panel now updates the real MySQL database!**
- ✅ **Changes in admin panel immediately update the website!**
- ✅ **Data persists between sessions!**
- Uses your local XAMPP MySQL database
- No more mock data - everything is real!

### ✅ Full Backend Integration
- All features work with real database
- Investment packages load from MySQL
- Admin panel saves to MySQL
- Investment submissions saved to database
- Wallet connections logged to database

## 🎯 Features That Work

### Main Site
- ✅ Investment calculator (loads from database)
- ✅ Package selection (real packages from MySQL)
- ✅ Wallet connection (logged to database)
- ✅ Investment form processing (saved to database)
- ✅ All animations and UI

### Admin Panel
- ✅ Package management (real CRUD operations)
- ✅ Add/Edit/Delete packages (updates website immediately)
- ✅ Wallet management (real database data)
- ✅ All changes persist and update the main site

## 📁 Project Structure
```
aureus-angel-alliance-main/
├── start-dev.bat          # Quick start script
├── src/                   # React source code
├── database/              # SQL files (for reference)
├── api/                   # PHP files (not needed for frontend)
└── scripts/               # Deployment scripts
```

## 🚀 Going Live (Optional)

If you want to share your site online:

```bash
npm run live
```

This will create a public URL using ngrok.

## 🛠️ Troubleshooting

### Port Already in Use
- The app will automatically find the next available port
- Check the terminal output for the actual URL

### Browser Doesn't Open
- Manually go to http://localhost:5174
- Or check terminal for the actual port number

### XAMPP Conflicts
- No more conflicts! The app now uses different ports
- You can keep XAMPP running

## 📝 Notes

- ✅ **Real database operations** - All data is saved to MySQL
- ✅ **Admin changes update the website immediately**
- ✅ **Data persists between sessions**
- ✅ **Full backend integration with XAMPP**
- Perfect for production use!

## 🚨 Having Issues?

If you see "Failed to save package" or other errors:
- **Check**: `TROUBLESHOOTING.md` for solutions
- **Quick fix**: Run `setup-database.bat` again

## 🎉 You're All Set!

Your Aureus Angel Alliance site now has full database integration!
When you add/edit packages in the admin panel, they immediately appear on the main website!

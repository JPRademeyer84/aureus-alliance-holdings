# Aureus Angel Alliance - Local Setup Guide

## Overview

This project has been completely migrated from Supabase and lovable.dev to use a local MySQL database with XAMPP. All external dependencies have been removed.

## Prerequisites

1. **XAMPP** - Download and install from [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. **Node.js** - Download and install from [https://nodejs.org/](https://nodejs.org/)
3. **Git** (optional) - For version control

## Installation Steps

### 1. Setup XAMPP

1. Install XAMPP on your system
2. Start XAMPP Control Panel
3. Start **Apache** and **MySQL** services
4. Verify services are running (green status)

### 2. Setup Database

1. Open your browser and go to `http://localhost/phpmyadmin`
2. Create a new database named `aureus_angels`
3. Import the database schema:
   - Click on the `aureus_angels` database
   - Go to "Import" tab
   - Select the file `database/init.sql` from this project
   - Click "Go" to execute

### 3. Setup Project Files

1. Extract/clone this project to your XAMPP `htdocs` directory:
   ```
   C:\xampp\htdocs\aureus-angel-alliance-main\
   ```

2. Install Node.js dependencies:
   ```bash
   cd C:\xampp\htdocs\aureus-angel-alliance-main
   npm install
   ```

### 4. Configure API Access

Ensure your project structure in XAMPP htdocs looks like this:
```
htdocs/
└── aureus-angel-alliance-main/
    ├── api/
    │   ├── config/
    │   ├── admin/
    │   ├── packages/
    │   ├── investments/
    │   └── wallets/
    ├── src/
    ├── database/
    └── package.json
```

### 5. Start the Application

1. Start the React development server:
   ```bash
   npm run dev
   ```

2. Open your browser and go to `http://localhost:8080`

## Default Credentials

### Admin Login
- **Username:** admin
- **Password:** Underdog8406155100085@123!@#

### Database Access
- **Host:** localhost
- **Database:** aureus_angels
- **Username:** root
- **Password:** (empty)

## API Endpoints

All API endpoints are accessible at `http://localhost/aureus-angel-alliance-main/api/`:

- `GET /packages/` - Get investment packages
- `POST /packages/` - Create investment package
- `PUT /packages/` - Update investment package
- `DELETE /packages/` - Delete investment package
- `POST /admin/auth` - Admin authentication
- `GET /wallets/` - Get investment wallets
- `POST /wallets/` - Create investment wallet
- `POST /investments/process` - Process investment

## Features

### ✅ Completed Migrations

- ❌ Removed all Supabase connections
- ❌ Removed all lovable.dev references
- ✅ Created MySQL database schema
- ✅ Created PHP API endpoints
- ✅ Updated React components to use MySQL API
- ✅ Admin authentication with MySQL
- ✅ Investment package management
- ✅ Wallet management
- ✅ Investment processing
- ✅ Wallet connection logging

### Available Functionality

1. **Homepage** - Investment platform landing page
2. **Investment Page** - Wallet connection and investment processing
3. **Admin Dashboard** - Package and wallet management

## Troubleshooting

### Common Issues

1. **"Connection error" in API**
   - Ensure MySQL is running in XAMPP
   - Check if `aureus_angels` database exists
   - Verify database credentials in `api/config/database.php`

2. **"404 Not Found" for API calls**
   - Ensure Apache is running in XAMPP
   - Check project is in correct XAMPP htdocs directory
   - Verify API files exist in `api/` directory

3. **CORS errors**
   - Ensure Apache is running
   - Check `.htaccess` file exists in `api/` directory

4. **Admin login fails**
   - Verify database has been initialized with `database/init.sql`
   - Check admin user exists in `admin_users` table
   - Use correct password: `Underdog8406155100085@123!@#`

### Testing the Setup

1. **Test API directly:**
   - Go to `http://localhost/aureus-angel-alliance-main/api/packages/`
   - Should return JSON with investment packages

2. **Test Admin Login:**
   - Go to `http://localhost:8080/admin`
   - Login with admin credentials
   - Should access admin dashboard

3. **Test Investment Flow:**
   - Go to `http://localhost:8080/invest`
   - Connect a wallet (MetaMask, etc.)
   - Try to make an investment

## File Structure

```
aureus-alliance-holdings-main/
├── api/                    # PHP Backend API
│   ├── config/
│   │   ├── database.php    # Database connection
│   │   └── cors.php        # CORS headers
│   ├── admin/
│   │   └── auth.php        # Admin authentication
│   ├── packages/
│   │   └── index.php       # Package CRUD operations
│   ├── investments/
│   │   └── process.php     # Investment processing
│   ├── wallets/
│   │   └── index.php       # Wallet management
│   └── .htaccess          # Apache configuration
├── database/
│   ├── init.sql           # Database schema and data
│   └── README.md          # Database setup guide
├── src/                   # React Frontend
│   ├── components/        # React components
│   ├── pages/            # Page components
│   ├── hooks/            # Custom hooks
│   └── contexts/         # React contexts
├── package.json          # Node.js dependencies
├── vite.config.ts        # Vite configuration
└── README.md             # Project documentation
```

## Support

If you encounter any issues:

1. Check XAMPP services are running
2. Verify database setup is complete
3. Ensure project is in correct directory
4. Check browser console for errors
5. Check XAMPP error logs for PHP errors

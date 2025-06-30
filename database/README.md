# Database Setup Guide

## Prerequisites

1. **XAMPP** installed and running
2. **MySQL** service started in XAMPP
3. **Apache** service started in XAMPP (for phpMyAdmin)

## Setup Instructions

### 1. Start XAMPP Services

1. Open XAMPP Control Panel
2. Start **Apache** service
3. Start **MySQL** service

### 2. Create Database

#### Option A: Using phpMyAdmin (Recommended)

1. Open your browser and go to `http://localhost/phpmyadmin`
2. Click on "New" in the left sidebar
3. Enter database name: `aureus_angels`
4. Click "Create"
5. Select the newly created database
6. Click on "Import" tab
7. Choose the file `database/init.sql` from this project
8. Click "Go" to execute the SQL script

#### Option B: Using MySQL Command Line

1. Open Command Prompt/Terminal
2. Navigate to your project directory
3. Run the following commands:

```bash
# Connect to MySQL
mysql -u root -p

# Create and setup database
source database/init.sql
```

### 3. Verify Setup

After running the setup, your database should contain the following tables:

- `admin_users` - Admin authentication
- `investment_packages` - Investment package data
- `investment_wallets` - Wallet addresses for different chains
- `aureus_investments` - Investment records
- `wallet_connections` - Wallet connection logs

### 4. Default Data

The setup script includes:

**Admin User:**
- Username: `admin`
- Password: `Underdog8406155100085@123!@#`

**Investment Packages:**
- Starter ($50)
- Bronze ($100)
- Silver ($250)
- Gold ($500)
- Platinum ($1,000)
- Diamond ($2,500)
- Obsidian ($50,000)

**Default Wallet Addresses:**
- Ethereum: `0x742d35cc6e09c4e1d9b56e5b3b5b3b5b3b5b3b5b`
- Polygon: `0x742d35cc6e09c4e1d9b56e5b3b5b3b5b3b5b3b5b`
- BSC: `0x742d35cc6e09c4e1d9b56e5b3b5b3b5b3b5b3b5b`

## API Configuration

The API is configured to connect to:
- **Host:** localhost
- **Database:** aureus_angels
- **Username:** root
- **Password:** (empty - default XAMPP setup)

If your MySQL setup is different, update the connection details in `api/config/database.php`.

## Troubleshooting

### Common Issues

1. **"Connection error"**
   - Ensure MySQL service is running in XAMPP
   - Check if the database `aureus_angels` exists
   - Verify MySQL credentials in `api/config/database.php`

2. **"Access denied"**
   - Make sure you're using the correct MySQL username/password
   - Default XAMPP setup uses `root` with no password

3. **"Table doesn't exist"**
   - Run the `database/init.sql` script again
   - Check if the database was created successfully

4. **CORS errors**
   - Ensure Apache is running for the API endpoints
   - Check that the API files are accessible at `http://localhost/aureus-angel-alliance-main/api/`

### Testing the Setup

1. Open your browser and go to `http://localhost/aureus-angel-alliance-main/api/packages/index.php`
2. You should see a JSON response with the investment packages
3. If you see an error, check the MySQL connection and database setup

## File Structure

```
database/
├── init.sql          # Database initialization script
└── README.md         # This file

api/
├── config/
│   ├── database.php  # Database connection configuration
│   └── cors.php      # CORS headers utility
├── admin/
│   └── auth.php      # Admin authentication endpoint
├── packages/
│   └── index.php     # Investment packages CRUD
├── investments/
│   └── process.php   # Investment processing
├── wallets/
│   └── index.php     # Wallet management
└── .htaccess         # Apache rewrite rules
```

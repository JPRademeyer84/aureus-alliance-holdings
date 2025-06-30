#!/usr/bin/env node

const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

console.log('🚀 Starting deployment process...\n');

// Step 1: Build the React application
console.log('📦 Building React application...');
try {
  execSync('npm run build', { stdio: 'inherit' });
  console.log('✅ React build completed successfully\n');
} catch (error) {
  console.error('❌ Build failed:', error.message);
  process.exit(1);
}

// Step 2: Create deployment directory
const deployDir = path.join(__dirname, '..', 'deploy');
if (fs.existsSync(deployDir)) {
  fs.rmSync(deployDir, { recursive: true });
}
fs.mkdirSync(deployDir);

console.log('📁 Creating deployment package...');

// Step 3: Copy built React files
const distDir = path.join(__dirname, '..', 'dist');
const deployPublicDir = path.join(deployDir, 'public_html');
fs.mkdirSync(deployPublicDir);

// Copy all files from dist to public_html
copyDirectory(distDir, deployPublicDir);

// Step 4: Copy API files
const apiDir = path.join(__dirname, '..', 'api');
const deployApiDir = path.join(deployPublicDir, 'api');
copyDirectory(apiDir, deployApiDir);

// Step 5: Copy database files
const databaseDir = path.join(__dirname, '..', 'database');
const deployDatabaseDir = path.join(deployDir, 'database');
copyDirectory(databaseDir, deployDatabaseDir);

// Step 6: Create deployment instructions
const instructions = `
# Deployment Instructions

## Files to Upload

1. Upload all contents of 'public_html/' to your web hosting root directory
2. Import 'database/init.sql' into your production MySQL database

## Configuration Steps

1. Update database credentials in 'api/config/database.php'
2. Ensure your domain points to the uploaded files
3. Test the API endpoints at: yourdomain.com/api/packages/

## Default Admin Credentials

Username: admin
Password: Underdog8406155100085@123!@#

## Support

If you encounter issues, check:
- Database connection settings
- File permissions (755 for directories, 644 for files)
- PHP version (7.4+ required)
- MySQL version (5.7+ required)
`;

fs.writeFileSync(path.join(deployDir, 'DEPLOYMENT_INSTRUCTIONS.txt'), instructions);

console.log('✅ Deployment package created successfully!');
console.log(`📦 Files ready in: ${deployDir}`);
console.log('\n🌐 Next steps:');
console.log('1. Upload public_html/ contents to your web hosting');
console.log('2. Import database/init.sql to your production database');
console.log('3. Update database credentials in api/config/database.php');
console.log('4. Test your live application!');

function copyDirectory(src, dest) {
  if (!fs.existsSync(dest)) {
    fs.mkdirSync(dest, { recursive: true });
  }
  
  const items = fs.readdirSync(src);
  
  for (const item of items) {
    const srcPath = path.join(src, item);
    const destPath = path.join(dest, item);
    
    if (fs.statSync(srcPath).isDirectory()) {
      copyDirectory(srcPath, destPath);
    } else {
      fs.copyFileSync(srcPath, destPath);
    }
  }
}

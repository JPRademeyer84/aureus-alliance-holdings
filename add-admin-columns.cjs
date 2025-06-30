const mysql = require('mysql2/promise');

async function addAdminColumns() {
  let connection;
  
  try {
    // Connect to database
    connection = await mysql.createConnection({
      host: 'localhost',
      port: 3506,
      user: 'root',
      password: '',
      database: 'aureus_angels'
    });
    
    console.log('✅ Connected to database');
    
    // Check current table structure
    console.log('\n📋 Current telegram_users table structure:');
    const [columns] = await connection.execute('DESCRIBE telegram_users');
    columns.forEach(col => {
      console.log(`  ${col.Field} - ${col.Type} - ${col.Null} - ${col.Default}`);
    });
    
    // Check if admin columns exist
    const hasAdminAuthStep = columns.some(col => col.Field === 'admin_auth_step');
    const hasAdminTempEmail = columns.some(col => col.Field === 'admin_temp_email');
    
    console.log(`\n🔍 Column check:`);
    console.log(`  admin_auth_step exists: ${hasAdminAuthStep}`);
    console.log(`  admin_temp_email exists: ${hasAdminTempEmail}`);
    
    // Add missing columns
    if (!hasAdminAuthStep) {
      console.log('\n➕ Adding admin_auth_step column...');
      await connection.execute('ALTER TABLE telegram_users ADD COLUMN admin_auth_step VARCHAR(20) NULL');
      console.log('✅ admin_auth_step column added');
    }
    
    if (!hasAdminTempEmail) {
      console.log('\n➕ Adding admin_temp_email column...');
      await connection.execute('ALTER TABLE telegram_users ADD COLUMN admin_temp_email VARCHAR(255) NULL');
      console.log('✅ admin_temp_email column added');
    }
    
    if (hasAdminAuthStep && hasAdminTempEmail) {
      console.log('\n✅ All admin columns already exist!');
    }
    
    // Show final table structure
    console.log('\n📋 Final telegram_users table structure:');
    const [finalColumns] = await connection.execute('DESCRIBE telegram_users');
    finalColumns.forEach(col => {
      console.log(`  ${col.Field} - ${col.Type} - ${col.Null} - ${col.Default}`);
    });
    
  } catch (error) {
    console.error('❌ Error:', error.message);
  } finally {
    if (connection) {
      await connection.end();
      console.log('\n🔌 Database connection closed');
    }
  }
}

addAdminColumns();

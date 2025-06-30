const mysql = require('mysql2/promise');

async function checkDatabaseStructure() {
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
    
    // Check users table structure
    console.log('\n📋 Users table structure:');
    try {
      const [userColumns] = await connection.execute('DESCRIBE users');
      userColumns.forEach(col => {
        console.log(`  ${col.Field} - ${col.Type} - ${col.Key} - ${col.Default}`);
      });
    } catch (error) {
      console.log('❌ Users table does not exist');
    }
    
    // Check aureus_investments table structure
    console.log('\n📋 Aureus_investments table structure:');
    try {
      const [investmentColumns] = await connection.execute('DESCRIBE aureus_investments');
      investmentColumns.forEach(col => {
        console.log(`  ${col.Field} - ${col.Type} - ${col.Key} - ${col.Default}`);
      });
    } catch (error) {
      console.log('❌ Aureus_investments table does not exist');
    }
    
    // Check telegram_users table structure
    console.log('\n📋 Telegram_users table structure:');
    try {
      const [telegramColumns] = await connection.execute('DESCRIBE telegram_users');
      telegramColumns.forEach(col => {
        console.log(`  ${col.Field} - ${col.Type} - ${col.Key} - ${col.Default}`);
      });
    } catch (error) {
      console.log('❌ Telegram_users table does not exist');
    }
    
    // List all tables
    console.log('\n📊 All tables in aureus_angels database:');
    const [tables] = await connection.execute(`
      SELECT TABLE_NAME 
      FROM INFORMATION_SCHEMA.TABLES 
      WHERE TABLE_SCHEMA = 'aureus_angels' 
      ORDER BY TABLE_NAME
    `);
    
    tables.forEach(table => {
      console.log(`  ✓ ${table.TABLE_NAME}`);
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

checkDatabaseStructure();

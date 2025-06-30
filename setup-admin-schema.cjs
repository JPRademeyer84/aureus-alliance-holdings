const mysql = require('mysql2/promise');
const fs = require('fs');

async function setupAdminSchema() {
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
    
    // Read and execute the schema file
    console.log('\n📋 Reading admin features schema...');
    const schemaSQL = fs.readFileSync('admin-features-schema.sql', 'utf8');
    
    // Split by semicolons and execute each statement
    const statements = schemaSQL.split(';').filter(stmt => stmt.trim().length > 0);
    
    console.log(`\n🔧 Executing ${statements.length} SQL statements...`);
    
    for (let i = 0; i < statements.length; i++) {
      const statement = statements[i].trim();
      if (statement.length > 0) {
        try {
          await connection.execute(statement);
          console.log(`✅ Statement ${i + 1}/${statements.length} executed successfully`);
        } catch (error) {
          if (error.code === 'ER_TABLE_EXISTS_ERROR') {
            console.log(`⚠️  Statement ${i + 1}/${statements.length} - Table already exists`);
          } else {
            console.error(`❌ Statement ${i + 1}/${statements.length} failed:`, error.message);
          }
        }
      }
    }
    
    // Verify tables were created
    console.log('\n🔍 Verifying created tables...');
    const [tables] = await connection.execute(`
      SELECT TABLE_NAME 
      FROM INFORMATION_SCHEMA.TABLES 
      WHERE TABLE_SCHEMA = 'aureus_angels' 
      AND TABLE_NAME LIKE 'admin_%' 
      OR TABLE_NAME LIKE 'telegram_terms_%'
      ORDER BY TABLE_NAME
    `);
    
    console.log('\n📊 Admin-related tables in database:');
    tables.forEach(table => {
      console.log(`  ✓ ${table.TABLE_NAME}`);
    });
    
    console.log('\n🎉 Admin features schema setup completed successfully!');
    
  } catch (error) {
    console.error('❌ Error setting up admin schema:', error.message);
  } finally {
    if (connection) {
      await connection.end();
      console.log('\n🔌 Database connection closed');
    }
  }
}

setupAdminSchema();

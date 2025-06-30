const mysql = require('mysql2/promise');
const fs = require('fs');

// Database connection configuration
const dbConfig = {
  host: 'localhost',
  port: 3506,
  user: 'root',
  password: '',
  database: 'aureus_angels'
};

// Known schema from files - this will be populated by reading schema files
const knownTables = {};

async function loadSchemaFromFiles() {
  console.log('📋 Loading database schema from files...');
  
  // Load admin-features-schema.sql
  try {
    const adminSchema = fs.readFileSync('admin-features-schema.sql', 'utf8');
    parseSchemaFile(adminSchema, 'admin-features-schema.sql');
  } catch (error) {
    console.log('⚠️ Could not load admin-features-schema.sql:', error.message);
  }

  // Load database/init.sql
  try {
    const initSchema = fs.readFileSync('database/init.sql', 'utf8');
    parseSchemaFile(initSchema, 'database/init.sql');
  } catch (error) {
    console.log('⚠️ Could not load database/init.sql:', error.message);
  }

  // Load migration files
  const migrationFiles = [
    'database/migrations/create_crypto_payment_transactions.sql',
    'database/migrations/create_bank_payment_system.sql',
    'database/migrations/update_telegram_users_payment_columns.sql',
    'database/migrations/create_commission_tables.sql',
    'database/migrations/create_manual_payment_system.sql'
  ];

  for (const file of migrationFiles) {
    try {
      const content = fs.readFileSync(file, 'utf8');
      parseSchemaFile(content, file);
    } catch (error) {
      console.log(`⚠️ Could not load ${file}:`, error.message);
    }
  }
}

function parseSchemaFile(content, filename) {
  console.log(`  📄 Parsing ${filename}...`);
  
  // Extract CREATE TABLE statements
  const tableRegex = /CREATE TABLE(?:\s+IF NOT EXISTS)?\s+(\w+)\s*\(([\s\S]*?)\);/gi;
  let match;
  
  while ((match = tableRegex.exec(content)) !== null) {
    const tableName = match[1];
    const tableDefinition = match[2];
    
    if (!knownTables[tableName]) {
      knownTables[tableName] = {
        columns: {},
        enums: {},
        source: filename
      };
    }
    
    // Parse columns
    const columnLines = tableDefinition.split('\n');
    for (const line of columnLines) {
      const trimmed = line.trim();
      if (trimmed && !trimmed.startsWith('--') && !trimmed.startsWith('INDEX') && 
          !trimmed.startsWith('FOREIGN KEY') && !trimmed.startsWith('UNIQUE') &&
          !trimmed.startsWith('PRIMARY KEY') && !trimmed.startsWith('KEY')) {
        
        const columnMatch = trimmed.match(/^(\w+)\s+(.+?)(?:,\s*$|$)/);
        if (columnMatch) {
          const columnName = columnMatch[1];
          const columnDef = columnMatch[2];
          
          knownTables[tableName].columns[columnName] = columnDef;
          
          // Extract ENUM values
          const enumMatch = columnDef.match(/ENUM\s*\((.*?)\)/i);
          if (enumMatch) {
            const enumValues = enumMatch[1].split(',').map(v => v.trim().replace(/['"]/g, ''));
            knownTables[tableName].enums[columnName] = enumValues;
          }
        }
      }
    }
  }
}

async function auditDatabaseOperations() {
  let connection;
  const auditResults = {
    errors: [],
    warnings: [],
    summary: {
      totalQueries: 0,
      tablesChecked: 0,
      columnsChecked: 0,
      enumsChecked: 0
    }
  };

  try {
    // Connect to database to get actual schema
    connection = await mysql.createConnection(dbConfig);
    console.log('✅ Connected to database for schema verification');

    // Get actual database schema
    const actualSchema = await getActualDatabaseSchema(connection);
    
    // Load and parse telegram-bot.cjs
    console.log('\n📋 Analyzing telegram-bot.cjs database operations...');
    const botCode = fs.readFileSync('telegram-bot.cjs', 'utf8');
    
    // Extract all database operations
    const dbOperations = extractDatabaseOperations(botCode);
    auditResults.summary.totalQueries = dbOperations.length;
    
    console.log(`\n🔍 Found ${dbOperations.length} database operations to audit`);
    
    // Audit each operation
    for (let i = 0; i < dbOperations.length; i++) {
      const operation = dbOperations[i];
      console.log(`\n[${i + 1}/${dbOperations.length}] Auditing: ${operation.type} on line ${operation.line}`);
      
      await auditOperation(operation, actualSchema, auditResults);
    }
    
    // Generate report
    generateAuditReport(auditResults);
    
  } catch (error) {
    console.error('❌ Audit failed:', error.message);
  } finally {
    if (connection) {
      await connection.end();
    }
  }
}

async function getActualDatabaseSchema(connection) {
  const schema = {};
  
  // Get all tables
  const [tables] = await connection.execute(`
    SELECT TABLE_NAME 
    FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = 'aureus_angels'
  `);
  
  for (const table of tables) {
    const tableName = table.TABLE_NAME;
    schema[tableName] = { columns: {}, enums: {} };
    
    // Get columns for this table
    const [columns] = await connection.execute(`
      SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
      FROM INFORMATION_SCHEMA.COLUMNS 
      WHERE TABLE_SCHEMA = 'aureus_angels' AND TABLE_NAME = ?
      ORDER BY ORDINAL_POSITION
    `, [tableName]);
    
    for (const col of columns) {
      schema[tableName].columns[col.COLUMN_NAME] = {
        type: col.COLUMN_TYPE,
        nullable: col.IS_NULLABLE === 'YES',
        default: col.COLUMN_DEFAULT
      };
      
      // Extract ENUM values if it's an ENUM column
      if (col.COLUMN_TYPE.startsWith('enum(')) {
        const enumMatch = col.COLUMN_TYPE.match(/enum\((.*?)\)/i);
        if (enumMatch) {
          const enumValues = enumMatch[1].split(',').map(v => v.trim().replace(/['"]/g, ''));
          schema[tableName].enums[col.COLUMN_NAME] = enumValues;
        }
      }
    }
  }
  
  return schema;
}

function extractDatabaseOperations(code) {
  const operations = [];
  const lines = code.split('\n');
  
  for (let i = 0; i < lines.length; i++) {
    const line = lines[i];
    const lineNumber = i + 1;
    
    // Look for dbConnection.execute calls
    if (line.includes('dbConnection.execute(')) {
      // Extract the SQL query (might span multiple lines)
      let sqlQuery = '';
      let j = i;
      let inQuery = false;
      let braceCount = 0;
      
      while (j < lines.length) {
        const currentLine = lines[j];
        
        if (currentLine.includes('dbConnection.execute(')) {
          inQuery = true;
        }
        
        if (inQuery) {
          sqlQuery += currentLine + '\n';
          braceCount += (currentLine.match(/\(/g) || []).length;
          braceCount -= (currentLine.match(/\)/g) || []).length;
          
          if (braceCount <= 0 && currentLine.includes(')')) {
            break;
          }
        }
        j++;
      }
      
      // Parse the operation
      const operation = parseOperation(sqlQuery, lineNumber);
      if (operation) {
        operations.push(operation);
      }
    }
  }
  
  return operations;
}

function parseOperation(sqlQuery, lineNumber) {
  // Extract SQL type and table name
  const sqlMatch = sqlQuery.match(/`([^`]+)`/);
  if (!sqlMatch) return null;
  
  const sql = sqlMatch[1];
  const type = sql.trim().split(' ')[0].toUpperCase();
  
  let tableName = null;
  let columns = [];
  let values = [];
  
  // Extract table name and columns based on operation type
  if (type === 'SELECT') {
    const fromMatch = sql.match(/FROM\s+(\w+)/i);
    if (fromMatch) tableName = fromMatch[1];
  } else if (type === 'INSERT') {
    const intoMatch = sql.match(/INSERT\s+INTO\s+(\w+)/i);
    if (intoMatch) tableName = intoMatch[1];
    
    const columnsMatch = sql.match(/\(([^)]+)\)\s+VALUES/i);
    if (columnsMatch) {
      columns = columnsMatch[1].split(',').map(c => c.trim());
    }
  } else if (type === 'UPDATE') {
    const tableMatch = sql.match(/UPDATE\s+(\w+)/i);
    if (tableMatch) tableName = tableMatch[1];
    
    const setMatch = sql.match(/SET\s+(.+?)(?:\s+WHERE|$)/i);
    if (setMatch) {
      const setPairs = setMatch[1].split(',');
      columns = setPairs.map(pair => pair.trim().split('=')[0].trim());
    }
  }
  
  return {
    type,
    tableName,
    columns,
    sql,
    line: lineNumber,
    fullQuery: sqlQuery
  };
}

async function auditOperation(operation, actualSchema, auditResults) {
  const { type, tableName, columns, sql, line } = operation;
  
  if (!tableName) {
    auditResults.warnings.push({
      line,
      type: 'NO_TABLE_DETECTED',
      message: `Could not detect table name in ${type} operation`,
      sql: sql.substring(0, 100) + '...'
    });
    return;
  }
  
  // Check if table exists
  if (!actualSchema[tableName]) {
    auditResults.errors.push({
      line,
      type: 'TABLE_NOT_FOUND',
      message: `Table '${tableName}' does not exist`,
      table: tableName,
      sql: sql.substring(0, 100) + '...'
    });
    return;
  }
  
  auditResults.summary.tablesChecked++;
  
  // Check columns
  for (const column of columns) {
    const cleanColumn = column.replace(/[`'"]/g, '').trim();
    
    if (!actualSchema[tableName].columns[cleanColumn]) {
      auditResults.errors.push({
        line,
        type: 'COLUMN_NOT_FOUND',
        message: `Column '${cleanColumn}' does not exist in table '${tableName}'`,
        table: tableName,
        column: cleanColumn,
        sql: sql.substring(0, 100) + '...'
      });
    } else {
      auditResults.summary.columnsChecked++;
    }
  }
  
  // Check ENUM values in SET clauses for UPDATE operations
  if (type === 'UPDATE') {
    const enumChecks = checkEnumValues(sql, tableName, actualSchema);
    auditResults.errors.push(...enumChecks.errors);
    auditResults.warnings.push(...enumChecks.warnings);
    auditResults.summary.enumsChecked += enumChecks.enumsChecked;
  }
}

function checkEnumValues(sql, tableName, actualSchema) {
  const results = { errors: [], warnings: [], enumsChecked: 0 };
  
  // Look for SET clauses with quoted values that might be ENUMs
  const setMatches = sql.match(/(\w+)\s*=\s*'([^']+)'/g);
  if (!setMatches) return results;
  
  for (const match of setMatches) {
    const [, column, value] = match.match(/(\w+)\s*=\s*'([^']+)'/);
    
    if (actualSchema[tableName].enums[column]) {
      results.enumsChecked++;
      const validValues = actualSchema[tableName].enums[column];
      
      if (!validValues.includes(value)) {
        results.errors.push({
          type: 'INVALID_ENUM_VALUE',
          message: `Invalid ENUM value '${value}' for column '${column}' in table '${tableName}'. Valid values: ${validValues.join(', ')}`,
          table: tableName,
          column,
          value,
          validValues
        });
      }
    }
  }
  
  return results;
}

function generateAuditReport(auditResults) {
  console.log('\n' + '='.repeat(80));
  console.log('📊 DATABASE AUDIT REPORT');
  console.log('='.repeat(80));
  
  console.log(`\n📈 SUMMARY:`);
  console.log(`  • Total queries analyzed: ${auditResults.summary.totalQueries}`);
  console.log(`  • Tables checked: ${auditResults.summary.tablesChecked}`);
  console.log(`  • Columns verified: ${auditResults.summary.columnsChecked}`);
  console.log(`  • ENUM values checked: ${auditResults.summary.enumsChecked}`);
  console.log(`  • Errors found: ${auditResults.errors.length}`);
  console.log(`  • Warnings: ${auditResults.warnings.length}`);
  
  if (auditResults.errors.length > 0) {
    console.log(`\n❌ ERRORS (${auditResults.errors.length}):`);
    auditResults.errors.forEach((error, index) => {
      console.log(`\n${index + 1}. ${error.type} (Line ${error.line || 'Unknown'})`);
      console.log(`   ${error.message}`);
      if (error.sql) console.log(`   SQL: ${error.sql}`);
    });
  }
  
  if (auditResults.warnings.length > 0) {
    console.log(`\n⚠️ WARNINGS (${auditResults.warnings.length}):`);
    auditResults.warnings.forEach((warning, index) => {
      console.log(`\n${index + 1}. ${warning.type} (Line ${warning.line || 'Unknown'})`);
      console.log(`   ${warning.message}`);
      if (warning.sql) console.log(`   SQL: ${warning.sql}`);
    });
  }
  
  if (auditResults.errors.length === 0 && auditResults.warnings.length === 0) {
    console.log('\n✅ NO ISSUES FOUND - All database operations appear to be valid!');
  }
  
  console.log('\n' + '='.repeat(80));
}

// Run the audit
async function main() {
  console.log('🔍 TELEGRAM BOT DATABASE AUDIT');
  console.log('='.repeat(50));
  
  await loadSchemaFromFiles();
  console.log(`\n📋 Loaded schema for ${Object.keys(knownTables).length} tables from files`);
  
  await auditDatabaseOperations();
}

main().catch(console.error);

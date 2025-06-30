<?php
/**
 * COMPREHENSIVE DATABASE INVESTIGATION
 * Checks ALL tables for plan-related data and duplicates
 */

header('Content-Type: text/plain');

require_once '../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "🔍 COMPREHENSIVE DATABASE INVESTIGATION\n";
    echo "=====================================\n\n";
    
    // STEP 1: Get all tables in the database
    echo "STEP 1: Discovering all tables...\n";
    
    $tablesQuery = "SHOW TABLES";
    $tablesStmt = $db->prepare($tablesQuery);
    $tablesStmt->execute();
    $allTables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Total tables found: " . count($allTables) . "\n";
    foreach ($allTables as $table) {
        echo "- $table\n";
    }
    echo "\n";
    
    // STEP 2: Check each table for plan-related data
    echo "STEP 2: Checking each table for plan/package data...\n";
    
    $planRelatedTables = [];
    
    foreach ($allTables as $table) {
        try {
            // Get table structure
            $structureQuery = "DESCRIBE $table";
            $structureStmt = $db->prepare($structureQuery);
            $structureStmt->execute();
            $columns = $structureStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Check if table has plan-related columns
            $planRelated = false;
            $planColumns = [];
            
            foreach ($columns as $column) {
                $columnName = strtolower($column['Field']);
                if (strpos($columnName, 'package') !== false || 
                    strpos($columnName, 'plan') !== false ||
                    strpos($columnName, 'investment') !== false ||
                    $columnName === 'name' || 
                    $columnName === 'price' ||
                    $columnName === 'shares' ||
                    $columnName === 'roi') {
                    $planRelated = true;
                    $planColumns[] = $column['Field'];
                }
            }
            
            if ($planRelated) {
                // Get row count
                $countQuery = "SELECT COUNT(*) as count FROM $table";
                $countStmt = $db->prepare($countQuery);
                $countStmt->execute();
                $rowCount = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
                
                $planRelatedTables[$table] = [
                    'row_count' => $rowCount,
                    'plan_columns' => $planColumns,
                    'all_columns' => array_column($columns, 'Field')
                ];
                
                echo "📦 $table: $rowCount rows, plan-related columns: " . implode(', ', $planColumns) . "\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Error checking table $table: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n";
    
    // STEP 3: Detailed analysis of plan-related tables
    echo "STEP 3: Detailed analysis of plan-related tables...\n";
    
    foreach ($planRelatedTables as $tableName => $tableInfo) {
        echo "\n📋 TABLE: $tableName\n";
        echo "Row count: {$tableInfo['row_count']}\n";
        echo "Columns: " . implode(', ', $tableInfo['all_columns']) . "\n";
        
        try {
            // Get sample data
            $sampleQuery = "SELECT * FROM $tableName LIMIT 5";
            $sampleStmt = $db->prepare($sampleQuery);
            $sampleStmt->execute();
            $sampleData = $sampleStmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($sampleData)) {
                echo "Sample data:\n";
                foreach ($sampleData as $index => $row) {
                    echo "  Row " . ($index + 1) . ": ";
                    $displayData = [];
                    foreach ($row as $key => $value) {
                        if (strlen($value) > 50) {
                            $value = substr($value, 0, 47) . '...';
                        }
                        $displayData[] = "$key=$value";
                    }
                    echo implode(', ', array_slice($displayData, 0, 3)) . "\n";
                }
            }
            
            // Check for duplicates if it looks like a plans table
            if (in_array('name', $tableInfo['all_columns']) && in_array('price', $tableInfo['all_columns'])) {
                $duplicateQuery = "
                    SELECT name, price, COUNT(*) as duplicate_count
                    FROM $tableName 
                    GROUP BY name, price
                    HAVING COUNT(*) > 1
                    ORDER BY duplicate_count DESC
                    LIMIT 10
                ";
                
                $duplicateStmt = $db->prepare($duplicateQuery);
                $duplicateStmt->execute();
                $duplicates = $duplicateStmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($duplicates)) {
                    echo "🚨 DUPLICATES FOUND:\n";
                    foreach ($duplicates as $duplicate) {
                        echo "  - {$duplicate['name']} (\${$duplicate['price']}): {$duplicate['duplicate_count']} copies\n";
                    }
                } else {
                    echo "✅ No duplicates found in this table\n";
                }
            }
            
        } catch (Exception $e) {
            echo "❌ Error analyzing table $tableName: " . $e->getMessage() . "\n";
        }
    }
    
    // STEP 4: Check for hidden or renamed plan tables
    echo "\n\nSTEP 4: Searching for hidden or renamed plan tables...\n";
    
    foreach ($allTables as $table) {
        try {
            // Check if table contains data that looks like investment plans
            $checkQuery = "SELECT * FROM $table LIMIT 1";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute();
            $sampleRow = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($sampleRow) {
                $hasPrice = false;
                $hasShares = false;
                $hasROI = false;
                $hasName = false;
                
                foreach ($sampleRow as $key => $value) {
                    $keyLower = strtolower($key);
                    if (strpos($keyLower, 'price') !== false || (is_numeric($value) && $value > 10 && $value < 100000)) {
                        $hasPrice = true;
                    }
                    if (strpos($keyLower, 'share') !== false || strpos($keyLower, 'roi') !== false) {
                        $hasShares = true;
                    }
                    if (strpos($keyLower, 'name') !== false || strpos($keyLower, 'title') !== false) {
                        $hasName = true;
                    }
                }
                
                if (($hasPrice && $hasShares) || ($hasPrice && $hasName)) {
                    $countQuery = "SELECT COUNT(*) as count FROM $table";
                    $countStmt = $db->prepare($countQuery);
                    $countStmt->execute();
                    $count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
                    
                    if ($count > 10) { // Suspicious if many rows
                        echo "🔍 SUSPICIOUS TABLE: $table ($count rows) - might contain plan data\n";
                        
                        // Show structure
                        $structureQuery = "DESCRIBE $table";
                        $structureStmt = $db->prepare($structureQuery);
                        $structureStmt->execute();
                        $structure = $structureStmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        echo "  Columns: " . implode(', ', array_column($structure, 'Field')) . "\n";
                        
                        // Show sample data
                        $sampleQuery = "SELECT * FROM $table LIMIT 3";
                        $sampleStmt = $db->prepare($sampleQuery);
                        $sampleStmt->execute();
                        $samples = $sampleStmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach ($samples as $i => $sample) {
                            echo "  Sample " . ($i + 1) . ": ";
                            $display = [];
                            foreach ($sample as $k => $v) {
                                if (strlen($v) > 30) $v = substr($v, 0, 27) . '...';
                                $display[] = "$k=$v";
                            }
                            echo implode(', ', array_slice($display, 0, 4)) . "\n";
                        }
                        echo "\n";
                    }
                }
            }
            
        } catch (Exception $e) {
            // Skip tables we can't read
            continue;
        }
    }
    
    // STEP 5: Check specific known tables
    echo "\nSTEP 5: Checking specific known tables...\n";
    
    $knownTables = [
        'investment_packages',
        'packages',
        'plans',
        'investment_plans',
        'aureus_packages',
        'presale_packages'
    ];
    
    foreach ($knownTables as $tableName) {
        try {
            $countQuery = "SELECT COUNT(*) as count FROM $tableName";
            $countStmt = $db->prepare($countQuery);
            $countStmt->execute();
            $count = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            echo "📊 $tableName: $count rows\n";
            
            if ($count > 0) {
                // Check for duplicates
                $duplicateQuery = "
                    SELECT name, COUNT(*) as duplicate_count
                    FROM $tableName 
                    GROUP BY name
                    HAVING COUNT(*) > 1
                    ORDER BY duplicate_count DESC
                ";
                
                $duplicateStmt = $db->prepare($duplicateQuery);
                $duplicateStmt->execute();
                $duplicates = $duplicateStmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($duplicates)) {
                    echo "  🚨 DUPLICATES: " . count($duplicates) . " groups\n";
                    foreach (array_slice($duplicates, 0, 5) as $dup) {
                        echo "    - {$dup['name']}: {$dup['duplicate_count']} copies\n";
                    }
                }
            }
            
        } catch (Exception $e) {
            echo "❌ $tableName: Table doesn't exist or error: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=====================================\n";
    echo "🎯 INVESTIGATION COMPLETE\n";
    echo "=====================================\n";

} catch (Exception $e) {
    echo "❌ INVESTIGATION FAILED: " . $e->getMessage() . "\n";
}
?>

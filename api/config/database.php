<?php
/**
 * SIMPLE DATABASE CONFIGURATION - DIRECT CONNECTION
 * Uses your exact XAMPP settings without environment variables
 */

class Database {
    private $host = 'localhost';
    private $port = '3506';  // Your custom XAMPP MySQL port
    private $db_name = 'aureus_angels';
    private $username = 'root';
    private $password = '';
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=utf8mb4";

            $this->conn = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);

        } catch(PDOException $exception) {
            error_log("Database connection error: " . $exception->getMessage());
            throw new Exception("Database connection failed: " . $exception->getMessage());
        }

        return $this->conn;
    }

    public function createTables() {
        try {
            // Create users table
            $query = "CREATE TABLE IF NOT EXISTS users (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                username VARCHAR(50) UNIQUE NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                full_name VARCHAR(255),
                role ENUM('user', 'admin') DEFAULT 'user',
                email_verified BOOLEAN DEFAULT FALSE,
                email_verification_token VARCHAR(255),
                password_reset_token VARCHAR(255),
                password_reset_expires TIMESTAMP NULL,
                last_login TIMESTAMP NULL,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_username (username),
                INDEX idx_email (email),
                INDEX idx_role (role),
                INDEX idx_is_active (is_active)
            )";
            $this->conn->exec($query);

            // Add missing columns to existing users table
            $userColumns = [
                "ALTER TABLE users ADD COLUMN IF NOT EXISTS full_name VARCHAR(255)",
                "ALTER TABLE users ADD COLUMN IF NOT EXISTS role ENUM('user', 'admin') DEFAULT 'user'",
                "ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verified BOOLEAN DEFAULT FALSE",
                "ALTER TABLE users ADD COLUMN IF NOT EXISTS email_verification_token VARCHAR(255)",
                "ALTER TABLE users ADD COLUMN IF NOT EXISTS password_reset_token VARCHAR(255)",
                "ALTER TABLE users ADD COLUMN IF NOT EXISTS password_reset_expires TIMESTAMP NULL",
                "ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL",
                "ALTER TABLE users ADD COLUMN IF NOT EXISTS facial_verification_status ENUM('not_started', 'pending', 'verified', 'failed') DEFAULT 'not_started'"
            ];

            foreach ($userColumns as $alterQuery) {
                try {
                    $this->conn->exec($alterQuery);
                } catch (PDOException $e) {
                    // Column might already exist, continue
                }
            }

            // Create admin_users table
            $query = "CREATE TABLE IF NOT EXISTS admin_users (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                username VARCHAR(50) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                email VARCHAR(255) NULL,
                full_name VARCHAR(100) NULL,
                role ENUM('super_admin', 'admin', 'chat_support') DEFAULT 'chat_support',
                is_active BOOLEAN DEFAULT TRUE,
                chat_status ENUM('online', 'offline', 'busy') DEFAULT 'offline',
                last_activity TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_role (role),
                INDEX idx_chat_status (chat_status),
                INDEX idx_is_active (is_active)
            )";
            $this->conn->exec($query);

            // Add new columns to existing admin_users table if they don't exist
            try {
                $this->conn->exec("ALTER TABLE admin_users ADD COLUMN email VARCHAR(255) NULL");
            } catch(PDOException $e) {
                // Column already exists
            }
            try {
                $this->conn->exec("ALTER TABLE admin_users ADD COLUMN full_name VARCHAR(100) NULL");
            } catch(PDOException $e) {
                // Column already exists
            }
            try {
                $this->conn->exec("ALTER TABLE admin_users ADD COLUMN role ENUM('super_admin', 'admin', 'chat_support') DEFAULT 'chat_support'");
            } catch(PDOException $e) {
                // Column already exists
            }
            try {
                $this->conn->exec("ALTER TABLE admin_users ADD COLUMN is_active BOOLEAN DEFAULT TRUE");
            } catch(PDOException $e) {
                // Column already exists
            }
            try {
                $this->conn->exec("ALTER TABLE admin_users ADD COLUMN chat_status ENUM('online', 'offline', 'busy') DEFAULT 'offline'");
            } catch(PDOException $e) {
                // Column already exists
            }
            try {
                $this->conn->exec("ALTER TABLE admin_users ADD COLUMN last_activity TIMESTAMP NULL");
            } catch(PDOException $e) {
                // Column already exists
            }
            try {
                $this->conn->exec("ALTER TABLE admin_users ADD COLUMN password_change_required BOOLEAN DEFAULT FALSE");
            } catch(PDOException $e) {
                // Column already exists
            }
            try {
                $this->conn->exec("ALTER TABLE admin_users ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
            } catch(PDOException $e) {
                // Column already exists
            }

            // Create investment_packages table
            $query = "CREATE TABLE IF NOT EXISTS investment_packages (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                name VARCHAR(100) NOT NULL,
                price DECIMAL(10,2) NOT NULL,
                shares INT NOT NULL,
                roi DECIMAL(10,2) NOT NULL,
                annual_dividends DECIMAL(10,2) NOT NULL,
                quarter_dividends DECIMAL(10,2) NOT NULL,
                icon VARCHAR(50) DEFAULT 'star',
                icon_color VARCHAR(50) DEFAULT 'bg-green-500',
                bonuses JSON,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            $this->conn->exec($query);

            // Create investment_wallets table
            $query = "CREATE TABLE IF NOT EXISTS investment_wallets (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                chain VARCHAR(50) NOT NULL,
                address VARCHAR(255) NOT NULL,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            $this->conn->exec($query);

            // Create aureus_investments table with countdown system
            $query = "CREATE TABLE IF NOT EXISTS aureus_investments (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                user_id VARCHAR(255) NOT NULL,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                wallet_address VARCHAR(255) NOT NULL,
                chain VARCHAR(50) NOT NULL,
                amount DECIMAL(15,6) NOT NULL,
                investment_plan VARCHAR(50) NOT NULL,
                package_name VARCHAR(100) NOT NULL,
                shares INT NOT NULL DEFAULT 0,
                roi DECIMAL(15,6) NOT NULL DEFAULT 0.00,
                tx_hash VARCHAR(255) NULL,
                status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',

                -- 180-Day Countdown System
                nft_delivery_date TIMESTAMP NULL COMMENT 'Date when NFT will be delivered (180 days from purchase)',
                roi_delivery_date TIMESTAMP NULL COMMENT 'Date when ROI will be delivered (180 days from purchase)',
                delivery_status ENUM('pending', 'nft_ready', 'roi_ready', 'completed') DEFAULT 'pending',
                nft_delivered BOOLEAN DEFAULT FALSE,
                roi_delivered BOOLEAN DEFAULT FALSE,
                nft_delivery_tx_hash VARCHAR(255) NULL,
                roi_delivery_tx_hash VARCHAR(255) NULL,

                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                INDEX idx_user_id (user_id),
                INDEX idx_wallet_address (wallet_address),
                INDEX idx_status (status),
                INDEX idx_nft_delivery_date (nft_delivery_date),
                INDEX idx_roi_delivery_date (roi_delivery_date),
                INDEX idx_delivery_status (delivery_status)
            )";
            $this->conn->exec($query);

            // Add countdown columns to existing table if they don't exist
            $alterQueries = [
                "ALTER TABLE aureus_investments ADD COLUMN IF NOT EXISTS nft_delivery_date TIMESTAMP NULL COMMENT 'Date when NFT will be delivered (180 days from purchase)'",
                "ALTER TABLE aureus_investments ADD COLUMN IF NOT EXISTS roi_delivery_date TIMESTAMP NULL COMMENT 'Date when ROI will be delivered (180 days from purchase)'",
                "ALTER TABLE aureus_investments ADD COLUMN IF NOT EXISTS delivery_status ENUM('pending', 'nft_ready', 'roi_ready', 'completed') DEFAULT 'pending'",
                "ALTER TABLE aureus_investments ADD COLUMN IF NOT EXISTS nft_delivered BOOLEAN DEFAULT FALSE",
                "ALTER TABLE aureus_investments ADD COLUMN IF NOT EXISTS roi_delivered BOOLEAN DEFAULT FALSE",
                "ALTER TABLE aureus_investments ADD COLUMN IF NOT EXISTS nft_delivery_tx_hash VARCHAR(255) NULL",
                "ALTER TABLE aureus_investments ADD COLUMN IF NOT EXISTS roi_delivery_tx_hash VARCHAR(255) NULL"
            ];

            foreach ($alterQueries as $alterQuery) {
                try {
                    $this->conn->exec($alterQuery);
                } catch (PDOException $e) {
                    // Column might already exist, continue
                }
            }

            // Add missing columns to existing aureus_investments table if they don't exist
            try {
                $this->conn->exec("ALTER TABLE aureus_investments ADD COLUMN package_name VARCHAR(100) NOT NULL DEFAULT ''");
            } catch(PDOException $e) {
                // Column already exists
            }
            try {
                $this->conn->exec("ALTER TABLE aureus_investments ADD COLUMN shares INT NOT NULL DEFAULT 0");
            } catch(PDOException $e) {
                // Column already exists
            }
            try {
                $this->conn->exec("ALTER TABLE aureus_investments ADD COLUMN roi DECIMAL(10,2) NOT NULL DEFAULT 0.00");
            } catch(PDOException $e) {
                // Column already exists
            }
            try {
                $this->conn->exec("ALTER TABLE aureus_investments ADD COLUMN tx_hash VARCHAR(255) NULL");
            } catch(PDOException $e) {
                // Column already exists
            }

            // Create wallet_connections table for logging
            $query = "CREATE TABLE IF NOT EXISTS wallet_connections (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                provider VARCHAR(50) NOT NULL,
                address VARCHAR(255) NOT NULL,
                chain_id VARCHAR(50) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            $this->conn->exec($query);

            // Create contact_messages table
            $query = "CREATE TABLE IF NOT EXISTS contact_messages (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                user_id VARCHAR(36) NOT NULL,
                subject VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                status ENUM('unread', 'read', 'replied') DEFAULT 'unread',
                admin_reply TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_status (status),
                INDEX idx_created_at (created_at)
            )";
            $this->conn->exec($query);

            // Create chat_sessions table
            $query = "CREATE TABLE IF NOT EXISTS chat_sessions (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                user_id VARCHAR(36) NULL,
                guest_email VARCHAR(255) NULL,
                guest_name VARCHAR(100) NULL,
                admin_id VARCHAR(36) NULL,
                status ENUM('waiting', 'active', 'closed') DEFAULT 'waiting',
                rating INT NULL CHECK (rating >= 1 AND rating <= 5),
                feedback TEXT NULL,
                rated_at TIMESTAMP NULL,
                transcript_sent BOOLEAN DEFAULT FALSE,
                transcript_sent_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_guest_email (guest_email),
                INDEX idx_admin_id (admin_id),
                INDEX idx_status (status),
                INDEX idx_rating (rating)
            )";
            $this->conn->exec($query);

            // Add new columns to existing table if they don't exist
            try {
                $this->conn->exec("ALTER TABLE chat_sessions ADD COLUMN rating INT NULL CHECK (rating >= 1 AND rating <= 5)");
            } catch(PDOException $e) {
                // Column already exists
            }
            try {
                $this->conn->exec("ALTER TABLE chat_sessions ADD COLUMN feedback TEXT NULL");
            } catch(PDOException $e) {
                // Column already exists
            }
            try {
                $this->conn->exec("ALTER TABLE chat_sessions ADD COLUMN rated_at TIMESTAMP NULL");
            } catch(PDOException $e) {
                // Column already exists
            }
            try {
                $this->conn->exec("ALTER TABLE chat_sessions ADD COLUMN transcript_sent BOOLEAN DEFAULT FALSE");
            } catch(PDOException $e) {
                // Column already exists
            }
            try {
                $this->conn->exec("ALTER TABLE chat_sessions ADD COLUMN transcript_sent_at TIMESTAMP NULL");
            } catch(PDOException $e) {
                // Column already exists
            }

            // Create chat_messages table
            $query = "CREATE TABLE IF NOT EXISTS chat_messages (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                session_id VARCHAR(36) NOT NULL,
                sender_type ENUM('user', 'admin') NOT NULL,
                sender_id VARCHAR(36) NOT NULL,
                message TEXT NOT NULL,
                is_read BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_session_id (session_id),
                INDEX idx_sender (sender_type, sender_id),
                INDEX idx_created_at (created_at),
                FOREIGN KEY (session_id) REFERENCES chat_sessions(id) ON DELETE CASCADE
            )";
            $this->conn->exec($query);

            // Create offline_messages table for when no admin is available
            $query = "CREATE TABLE IF NOT EXISTS offline_messages (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                guest_name VARCHAR(100) NOT NULL,
                guest_email VARCHAR(255) NOT NULL,
                subject VARCHAR(255) NULL,
                message TEXT NOT NULL,
                status ENUM('unread', 'read', 'replied') DEFAULT 'unread',
                admin_reply TEXT NULL,
                replied_by VARCHAR(36) NULL,
                replied_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_guest_email (guest_email),
                INDEX idx_status (status),
                INDEX idx_created_at (created_at),
                FOREIGN KEY (replied_by) REFERENCES admin_users(id) ON DELETE SET NULL
            )";
            $this->conn->exec($query);

            // Create commission tables
            $this->createCommissionTables();

            // Create delivery_schedule table
            $query = "CREATE TABLE IF NOT EXISTS delivery_schedule (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                investment_id VARCHAR(36) NOT NULL,
                user_id VARCHAR(255) NOT NULL,
                package_name VARCHAR(100) NOT NULL,
                investment_amount DECIMAL(15,6) NOT NULL,
                nft_delivery_date TIMESTAMP NOT NULL,
                roi_delivery_date TIMESTAMP NOT NULL,
                nft_status ENUM('pending', 'ready', 'delivered') DEFAULT 'pending',
                roi_status ENUM('pending', 'ready', 'delivered') DEFAULT 'pending',
                priority ENUM('low', 'normal', 'high') DEFAULT 'normal',
                notes TEXT NULL,
                assigned_to VARCHAR(36) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                INDEX idx_investment_id (investment_id),
                INDEX idx_user_id (user_id),
                INDEX idx_nft_delivery_date (nft_delivery_date),
                INDEX idx_roi_delivery_date (roi_delivery_date)
            )";
            $this->conn->exec($query);

            // Create referral_relationships table for Gold Diggers Club
            $query = "CREATE TABLE IF NOT EXISTS referral_relationships (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                referrer_user_id VARCHAR(255) NOT NULL,
                referred_user_id VARCHAR(255) NOT NULL,
                level INT NOT NULL DEFAULT 1,
                investment_amount DECIMAL(15,6) DEFAULT 0,
                commission_earned DECIMAL(15,6) DEFAULT 0,
                status ENUM('active', 'inactive') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_referrer (referrer_user_id),
                INDEX idx_referred (referred_user_id),
                INDEX idx_level (level),
                INDEX idx_status (status),
                UNIQUE KEY unique_referral (referrer_user_id, referred_user_id, level)
            )";
            $this->conn->exec($query);

            // Run enhanced KYC migration
            $this->runEnhancedKYCMigration();

            // Create user_profiles table for enhanced profiles
            $query = "CREATE TABLE IF NOT EXISTS user_profiles (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                user_id VARCHAR(255) NOT NULL,
                phone VARCHAR(20),
                country VARCHAR(100),
                city VARCHAR(100),
                date_of_birth DATE,
                profile_image VARCHAR(255),
                bio TEXT,

                -- Social Media Links
                telegram_username VARCHAR(100),
                whatsapp_number VARCHAR(20),
                twitter_handle VARCHAR(100),
                instagram_handle VARCHAR(100),
                linkedin_profile VARCHAR(255),
                facebook_profile VARCHAR(255),

                -- KYC Information
                kyc_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
                kyc_verified_at TIMESTAMP NULL,
                kyc_rejected_reason TEXT,

                -- Profile Completion
                profile_completion INT DEFAULT 0,

                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                UNIQUE KEY unique_user_profile (user_id),
                INDEX idx_kyc_status (kyc_status),
                INDEX idx_completion (profile_completion)
            )";
            $this->conn->exec($query);

            // Create KYC documents table
            $query = "CREATE TABLE IF NOT EXISTS kyc_documents (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                user_id VARCHAR(255) NOT NULL,
                type ENUM('passport', 'drivers_license', 'national_id', 'proof_of_address') NOT NULL,
                filename VARCHAR(255) NOT NULL,
                original_name VARCHAR(255) NOT NULL,
                file_path VARCHAR(500) NOT NULL,
                upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                reviewed_by VARCHAR(36) NULL,
                reviewed_at TIMESTAMP NULL,
                rejection_reason TEXT NULL,

                INDEX idx_user_id (user_id),
                INDEX idx_status (status),
                INDEX idx_type (type)
            )";
            $this->conn->exec($query);

            // Create facial_verifications table
            $query = "CREATE TABLE IF NOT EXISTS facial_verifications (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                user_id VARCHAR(255) NOT NULL,
                confidence_score DECIMAL(5,4) NOT NULL,
                liveness_score DECIMAL(5,4) NOT NULL,
                verification_status ENUM('verified', 'failed', 'pending') NOT NULL,
                captured_image_path VARCHAR(500) NULL,
                verification_data JSON NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                INDEX idx_user_id (user_id),
                INDEX idx_status (verification_status),
                INDEX idx_created_at (created_at)
            )";
            $this->conn->exec($query);

            // Fix user_id column type in existing tables
            try {
                $this->conn->exec("ALTER TABLE user_profiles MODIFY COLUMN user_id VARCHAR(255) NOT NULL");
            } catch(PDOException $e) {
                // Column might already be correct type
            }
            try {
                $this->conn->exec("ALTER TABLE kyc_documents MODIFY COLUMN user_id VARCHAR(255) NOT NULL");
            } catch(PDOException $e) {
                // Column might already be correct type
            }

            // Create KYC document access log table for audit trail
            $query = "CREATE TABLE IF NOT EXISTS kyc_document_access_log (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                document_id VARCHAR(36) NOT NULL,
                accessed_by VARCHAR(36) NOT NULL,
                access_type ENUM('owner', 'admin') NOT NULL,
                accessed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_document_id (document_id),
                INDEX idx_accessed_by (accessed_by),
                INDEX idx_access_type (access_type),
                INDEX idx_accessed_at (accessed_at)
            )";
            $this->conn->exec($query);

            // Create countdown system tables
            $this->createCountdownTables();

            // Create terms and conditions tables
            $this->createTermsTables();

            // Create certificate system tables
            $this->createCertificateTables();

            // Create bank payment system tables
            $this->createBankPaymentTables();

            return true;
        } catch(PDOException $exception) {
            error_log("Table creation error: " . $exception->getMessage());
            return false;
        }
    }

    private function createCommissionTables() {
        try {
            // COMMISSION PLAN AUTO-CREATION DISABLED
            // Create commission_plans table manually to avoid duplicate inserts
            $query = "CREATE TABLE IF NOT EXISTS commission_plans (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                plan_name VARCHAR(100) NOT NULL,
                description TEXT,
                is_active BOOLEAN DEFAULT TRUE,
                is_default BOOLEAN DEFAULT FALSE,

                -- Commission structure
                level_1_usdt_percent DECIMAL(5,2) NOT NULL DEFAULT 12.00,
                level_1_nft_percent DECIMAL(5,2) NOT NULL DEFAULT 12.00,
                level_2_usdt_percent DECIMAL(5,2) NOT NULL DEFAULT 5.00,
                level_2_nft_percent DECIMAL(5,2) NOT NULL DEFAULT 5.00,
                level_3_usdt_percent DECIMAL(5,2) NOT NULL DEFAULT 3.00,
                level_3_nft_percent DECIMAL(5,2) NOT NULL DEFAULT 3.00,

                -- NFT configuration
                nft_pack_price DECIMAL(10,2) NOT NULL DEFAULT 5.00,
                nft_total_supply INT NOT NULL DEFAULT 200000,
                nft_remaining_supply INT NOT NULL DEFAULT 200000,

                -- Plan configuration
                max_levels INT NOT NULL DEFAULT 3,
                minimum_investment DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                commission_cap DECIMAL(15,6) NULL,

                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                created_by VARCHAR(36) NULL,

                INDEX idx_plan_name (plan_name),
                INDEX idx_is_active (is_active),
                INDEX idx_is_default (is_default)
            )";
            $this->conn->exec($query);

            // Only create default commission plan if none exists (first-time setup only)
            $checkQuery = "SELECT COUNT(*) as count FROM commission_plans";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute();
            $planCount = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'];

            // AUTOMATIC COMMISSION PLAN CREATION DISABLED
            // Commission plans should only be created manually by admin through the admin panel
            // This prevents duplicate plan creation and maintains data integrity
            error_log("Commission plans table has " . $planCount . " plans - automatic creation disabled");

            // Create other commission tables without automatic inserts
            $this->createCommissionTransactionTables();

            // Create NFT coupons tables
            $this->createNFTCouponsTables();

        } catch(PDOException $exception) {
            error_log("Commission tables creation error: " . $exception->getMessage());
        }
    }

    private function createCommissionTransactionTables() {
        try {
            // Create commission_transactions table
            $query = "CREATE TABLE IF NOT EXISTS commission_transactions (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                commission_plan_id VARCHAR(36) NOT NULL,
                referrer_user_id VARCHAR(36) NOT NULL,
                referred_user_id VARCHAR(36) NOT NULL,
                referrer_username VARCHAR(50) NOT NULL,
                referred_username VARCHAR(50) NOT NULL,
                investment_id VARCHAR(36) NOT NULL,
                investment_amount DECIMAL(15,6) NOT NULL,
                investment_package VARCHAR(100) NOT NULL,
                commission_level INT NOT NULL,
                usdt_commission_percent DECIMAL(5,2) NOT NULL,
                nft_commission_percent DECIMAL(5,2) NOT NULL,
                usdt_commission_amount DECIMAL(15,6) NOT NULL,
                nft_commission_amount INT NOT NULL,
                status ENUM('pending', 'approved', 'paid', 'cancelled') DEFAULT 'pending',
                payment_method ENUM('manual', 'smart_contract') DEFAULT 'manual',
                usdt_tx_hash VARCHAR(255) NULL,
                nft_tx_hash VARCHAR(255) NULL,
                payment_wallet VARCHAR(255) NULL,
                payment_chain VARCHAR(50) NULL,
                approved_by VARCHAR(36) NULL,
                approved_at TIMESTAMP NULL,
                paid_by VARCHAR(36) NULL,
                paid_at TIMESTAMP NULL,
                cancelled_by VARCHAR(36) NULL,
                cancelled_at TIMESTAMP NULL,
                cancellation_reason TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                INDEX idx_commission_plan (commission_plan_id),
                INDEX idx_referrer (referrer_user_id),
                INDEX idx_referred (referred_user_id),
                INDEX idx_investment (investment_id),
                INDEX idx_status (status),
                INDEX idx_level (commission_level)
            )";
            $this->conn->exec($query);

        } catch(PDOException $exception) {
            error_log("Commission transaction tables creation error: " . $exception->getMessage());
        }
    }

    private function createNFTCouponsTables() {
        try {
            // Read and execute NFT coupons migration
            $migrationPath = __DIR__ . '/../../database/migrations/create_nft_coupons_table.sql';
            if (file_exists($migrationPath)) {
                $migrationSql = file_get_contents($migrationPath);
                if ($migrationSql) {
                    // Execute the migration
                    $this->conn->exec($migrationSql);
                    error_log("NFT coupons tables created successfully");
                }
            } else {
                error_log("NFT coupons migration file not found: " . $migrationPath);
            }
        } catch(PDOException $exception) {
            error_log("NFT coupons tables creation error: " . $exception->getMessage());
        }
    }

    private function createCountdownTables() {
        try {
            // Create delivery_schedule table for countdown management
            $query = "CREATE TABLE IF NOT EXISTS delivery_schedule (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                investment_id VARCHAR(36) NOT NULL,
                user_id VARCHAR(255) NOT NULL,
                package_name VARCHAR(100) NOT NULL,
                investment_amount DECIMAL(15,6) NOT NULL,
                nft_delivery_date TIMESTAMP NOT NULL,
                roi_delivery_date TIMESTAMP NOT NULL,
                nft_status ENUM('pending', 'ready', 'delivered') DEFAULT 'pending',
                roi_status ENUM('pending', 'ready', 'delivered') DEFAULT 'pending',
                priority ENUM('low', 'normal', 'high') DEFAULT 'normal',
                notes TEXT NULL,
                assigned_to VARCHAR(36) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                INDEX idx_investment_id (investment_id),
                INDEX idx_user_id (user_id),
                INDEX idx_nft_delivery_date (nft_delivery_date),
                INDEX idx_roi_delivery_date (roi_delivery_date)
            )";
            $this->conn->exec($query);

            // Create investment countdown view
            $viewQuery = "CREATE OR REPLACE VIEW investment_countdown_view AS
            SELECT
                ai.id,
                ai.user_id,
                ai.package_name,
                ai.amount,
                ai.shares,
                ai.roi,
                ai.status,
                ai.created_at,
                ai.nft_delivery_date,
                ai.roi_delivery_date,
                ai.delivery_status,
                ai.nft_delivered,
                ai.roi_delivered,

                CASE
                    WHEN ai.nft_delivery_date IS NULL THEN NULL
                    WHEN ai.nft_delivered = TRUE THEN 0
                    ELSE GREATEST(0, DATEDIFF(ai.nft_delivery_date, NOW()))
                END as nft_days_remaining,

                CASE
                    WHEN ai.roi_delivery_date IS NULL THEN NULL
                    WHEN ai.roi_delivered = TRUE THEN 0
                    ELSE GREATEST(0, DATEDIFF(ai.roi_delivery_date, NOW()))
                END as roi_days_remaining,

                CASE
                    WHEN ai.nft_delivery_date IS NULL THEN NULL
                    WHEN ai.nft_delivered = TRUE THEN 0
                    ELSE GREATEST(0, TIMESTAMPDIFF(HOUR, NOW(), ai.nft_delivery_date))
                END as nft_hours_remaining,

                CASE
                    WHEN ai.roi_delivery_date IS NULL THEN NULL
                    WHEN ai.roi_delivered = TRUE THEN 0
                    ELSE GREATEST(0, TIMESTAMPDIFF(HOUR, NOW(), ai.roi_delivery_date))
                END as roi_hours_remaining,

                CASE
                    WHEN ai.nft_delivered = TRUE THEN 'delivered'
                    WHEN ai.nft_delivery_date <= NOW() THEN 'ready'
                    WHEN DATEDIFF(ai.nft_delivery_date, NOW()) <= 7 THEN 'soon'
                    ELSE 'pending'
                END as nft_countdown_status,

                CASE
                    WHEN ai.roi_delivered = TRUE THEN 'delivered'
                    WHEN ai.roi_delivery_date <= NOW() THEN 'ready'
                    WHEN DATEDIFF(ai.roi_delivery_date, NOW()) <= 7 THEN 'soon'
                    ELSE 'pending'
                END as roi_countdown_status

            FROM aureus_investments ai
            WHERE ai.status = 'completed'";
            $this->conn->exec($viewQuery);

        } catch(PDOException $exception) {
            error_log("Countdown tables creation error: " . $exception->getMessage());
        }
    }

    private function createTermsTables() {
        try {
            // Create terms_acceptance table
            $query = "CREATE TABLE IF NOT EXISTS terms_acceptance (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                user_id VARCHAR(255) NULL,
                email VARCHAR(255) NOT NULL,
                wallet_address VARCHAR(255) NOT NULL,
                investment_id VARCHAR(36) NULL,

                gold_mining_investment_accepted BOOLEAN DEFAULT FALSE,
                nft_shares_understanding_accepted BOOLEAN DEFAULT FALSE,
                delivery_timeline_accepted BOOLEAN DEFAULT FALSE,
                dividend_timeline_accepted BOOLEAN DEFAULT FALSE,
                risk_acknowledgment_accepted BOOLEAN DEFAULT FALSE,

                ip_address VARCHAR(45),
                user_agent TEXT,
                acceptance_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                terms_version VARCHAR(10) DEFAULT '1.0',

                all_terms_accepted BOOLEAN GENERATED ALWAYS AS (
                    gold_mining_investment_accepted = TRUE AND
                    nft_shares_understanding_accepted = TRUE AND
                    delivery_timeline_accepted = TRUE AND
                    dividend_timeline_accepted = TRUE AND
                    risk_acknowledgment_accepted = TRUE
                ) STORED,

                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

                INDEX idx_user_id (user_id),
                INDEX idx_email (email),
                INDEX idx_wallet_address (wallet_address),
                INDEX idx_investment_id (investment_id),
                INDEX idx_all_accepted (all_terms_accepted),
                INDEX idx_acceptance_timestamp (acceptance_timestamp)
            )";
            $this->conn->exec($query);

            // Create terms_versions table
            $query = "CREATE TABLE IF NOT EXISTS terms_versions (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                version VARCHAR(10) NOT NULL UNIQUE,
                title VARCHAR(255) NOT NULL,
                content LONGTEXT NOT NULL,
                effective_date TIMESTAMP NOT NULL,
                created_by VARCHAR(36) NULL,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

                INDEX idx_version (version),
                INDEX idx_effective_date (effective_date),
                INDEX idx_is_active (is_active)
            )";
            $this->conn->exec($query);

            // Insert default terms version
            $defaultTerms = "INSERT IGNORE INTO terms_versions (
                version, title, content, effective_date, is_active
            ) VALUES (?, ?, ?, ?, ?)";

            $stmt = $this->conn->prepare($defaultTerms);
            $stmt->execute([
                '1.0',
                'Aureus Angel Alliance Investment Terms & Conditions',
                'Investment terms for Gold Mining Sector NFT shares with 180-day delivery timeline and Q1 2026 dividend schedule.',
                date('Y-m-d H:i:s'),
                1
            ]);

        } catch(PDOException $exception) {
            error_log("Terms tables creation error: " . $exception->getMessage());
        }
    }

    public function insertDefaultData() {
        try {
            // Check if admin user already exists
            $checkQuery = "SELECT COUNT(*) FROM admin_users WHERE username = 'admin'";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute();

            if ($checkStmt->fetchColumn() == 0) {
                // SECURITY: Admin must set password on first login
                // Generate a temporary random password that must be changed
                $tempPassword = bin2hex(random_bytes(16));
                $password_hash = password_hash($tempPassword, PASSWORD_DEFAULT);

                $query = "INSERT INTO admin_users (username, password_hash, role, full_name, email, password_change_required) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $this->conn->prepare($query);
                $stmt->execute(['admin', $password_hash, 'super_admin', 'System Administrator', 'admin@aureusangels.com', 1]);

                // Log the temporary password for initial setup (remove this in production)
                error_log("SECURITY NOTICE: Temporary admin password generated: " . $tempPassword);
                error_log("SECURITY NOTICE: Admin must change password on first login");
            }

            // Update existing admin user to super_admin if it exists
            $updateQuery = "UPDATE admin_users SET role = 'super_admin', full_name = 'System Administrator', email = 'admin@aureusangels.com' WHERE username = 'admin' AND role IS NULL";
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->execute();

            // AUTOMATIC PLAN CREATION DISABLED
            // Plans should only be created manually by admin through the admin panel
            // This prevents duplicate plan creation and maintains data integrity

            // Check if investment_packages table is completely empty (first-time setup)
            $checkQuery = "SELECT COUNT(*) as count FROM investment_packages";
            $checkStmt = $this->conn->prepare($checkQuery);
            $checkStmt->execute();
            $packageCount = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'];

            // Only insert default packages if table is completely empty (first-time setup only)
            if ($packageCount == 0) {
                $packages = [
                    ['Starter', 50, 2, 400, 200, 50, 'star', 'bg-green-500', '["Community Discord Access", "Guaranteed Common NFT Card"]'],
                    ['Bronze', 100, 10, 800, 800, 200, 'square', 'bg-amber-700', '["All Starter Bonuses", "Guaranteed Uncommon NFT Card", "Early Game Access", "Priority Support"]'],
                    ['Silver', 250, 30, 2000, 2500, 625, 'circle', 'bg-gray-300', '["All Bronze Bonuses", "Guaranteed Epic NFT Card", "Exclusive Game Events Access", "VIP Game Benefits"]'],
                    ['Gold', 500, 75, 4000, 6000, 1500, 'diamond', 'bg-yellow-500', '["All Silver Bonuses", "Guaranteed Rare NFT Card", "Monthly Strategy Calls", "Beta Testing Access"]'],
                    ['Platinum', 1000, 175, 8000, 15000, 3750, 'crown', 'bg-purple-500', '["All Gold Bonuses", "Guaranteed Legendary NFT Card", "Quarterly Executive Briefings", "Priority Feature Requests"]'],
                    ['Diamond', 2500, 500, 20000, 50000, 12500, 'gem', 'bg-blue-500', '["All Platinum Bonuses", "Guaranteed Mythic NFT Card", "Direct Line to Development Team", "Annual VIP Event Invitation"]'],
                    ['Obsidian', 50000, 12500, 250000, 1000000, 500000, 'square', 'bg-black', '["All Diamond Bonuses", "Lifetime Executive Board Membership", "Custom Executive NFT Card", "Personal Jet Invitation to Strategy Summit", "Handwritten Letter of Appreciation from the Founders"]']
                ];

                $query = "INSERT INTO investment_packages (name, price, shares, roi, annual_dividends, quarter_dividends, icon, icon_color, bonuses) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $this->conn->prepare($query);

                foreach ($packages as $package) {
                    $stmt->execute($package);
                }

                error_log("First-time setup: Default investment packages created");
            } else {
                error_log("Skipping package creation: " . $packageCount . " packages already exist");
            }

            return true;
        } catch(PDOException $exception) {
            error_log("Default data insertion error: " . $exception->getMessage());
            return false;
        }
    }

    private function runEnhancedKYCMigration() {
        try {
            // Read and execute enhanced KYC migration
            $migrationPath = __DIR__ . '/../../database/migrations/enhance_user_profiles_kyc.sql';
            if (file_exists($migrationPath)) {
                $migrationSql = file_get_contents($migrationPath);
                if ($migrationSql) {
                    // Split by semicolon and execute each statement
                    $statements = array_filter(array_map('trim', explode(';', $migrationSql)));
                    foreach ($statements as $statement) {
                        if (!empty($statement) && !str_starts_with($statement, '--')) {
                            try {
                                $this->conn->exec($statement);
                            } catch (PDOException $e) {
                                // Some statements might fail if columns already exist, continue
                                error_log("KYC Migration statement failed (continuing): " . $e->getMessage());
                            }
                        }
                    }
                    error_log("Enhanced KYC migration executed successfully");
                }
            } else {
                error_log("Enhanced KYC migration file not found: " . $migrationPath);
            }

            // Create KYC section audit log table
            $auditTableQuery = "CREATE TABLE IF NOT EXISTS kyc_section_audit_log (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                user_id VARCHAR(36) NOT NULL,
                section ENUM('personal_info', 'contact_info', 'address_info', 'identity_info', 'financial_info', 'emergency_contact') NOT NULL,
                action ENUM('approved', 'rejected') NOT NULL,
                admin_id VARCHAR(36) NOT NULL,
                rejection_reason TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_section (section),
                INDEX idx_action (action),
                INDEX idx_admin_id (admin_id),
                INDEX idx_created_at (created_at)
            )";
            $this->conn->exec($auditTableQuery);

        } catch(PDOException $exception) {
            error_log("Enhanced KYC migration error: " . $exception->getMessage());
        }
    }

    private function createCertificateTables() {
        try {
            // Read and execute certificate system migration
            $migrationPath = __DIR__ . '/../../database/migrations/create_certificate_system.sql';
            if (file_exists($migrationPath)) {
                $migrationSql = file_get_contents($migrationPath);
                if ($migrationSql) {
                    // Split by semicolon and execute each statement
                    $statements = array_filter(array_map('trim', explode(';', $migrationSql)));
                    foreach ($statements as $statement) {
                        if (!empty($statement) && !str_starts_with($statement, '--') && !str_starts_with($statement, 'USE')) {
                            try {
                                $this->conn->exec($statement);
                            } catch (PDOException $e) {
                                // Some statements might fail if tables already exist, continue
                                error_log("Certificate Migration statement failed (continuing): " . $e->getMessage());
                            }
                        }
                    }
                    error_log("Certificate system migration executed successfully");
                }
            } else {
                error_log("Certificate system migration file not found: " . $migrationPath);
            }
        } catch(PDOException $exception) {
            error_log("Certificate system migration error: " . $exception->getMessage());
        }
    }

    private function createBankPaymentTables() {
        try {
            // Read and execute bank payment system migration
            $migrationPath = __DIR__ . '/../../database/migrations/create_bank_payment_system.sql';
            if (file_exists($migrationPath)) {
                $migrationSql = file_get_contents($migrationPath);
                if ($migrationSql) {
                    // Split by semicolon and execute each statement
                    $statements = array_filter(array_map('trim', explode(';', $migrationSql)));
                    foreach ($statements as $statement) {
                        if (!empty($statement) && !str_starts_with($statement, '--') && !str_starts_with($statement, 'USE')) {
                            try {
                                $this->conn->exec($statement);
                            } catch (PDOException $e) {
                                // Some statements might fail if tables already exist, continue
                                error_log("Bank Payment Migration statement failed (continuing): " . $e->getMessage());
                            }
                        }
                    }
                    error_log("Bank payment system migration executed successfully");
                }
            } else {
                error_log("Bank payment system migration file not found: " . $migrationPath);
            }
        } catch(PDOException $exception) {
            error_log("Bank payment system migration error: " . $exception->getMessage());
        }
    }
}
?>

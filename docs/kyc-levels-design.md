# 3-Level KYC Verification System Design

## Overview
A progressive KYC verification system with 3 levels, each offering increased benefits and platform access. Similar to Binance's verification levels.

## KYC Levels

### Level 1: Basic Verification
**Requirements:**
- Email verification
- Phone number verification
- Basic profile information (name, country)

**Benefits:**
- Platform access
- Basic investment packages ($25-$100)
- Standard commission rates (5%)
- Withdrawal limit: $1,000/day
- NFT pack purchases: Up to 10 packs/month

**Features Unlocked:**
- Dashboard access
- Basic referral system
- Live chat support
- Marketing materials download

---

### Level 2: Intermediate Verification
**Requirements:**
- Level 1 completion
- Government-issued ID document (passport, driver's license, or national ID)
- Proof of address document
- Facial recognition verification

**Benefits:**
- Medium investment packages ($100-$500)
- Enhanced commission rates (7%)
- Withdrawal limit: $10,000/day
- NFT pack purchases: Up to 50 packs/month
- Priority customer support

**Features Unlocked:**
- Advanced referral analytics
- Commission tracking dashboard
- Investment portfolio management
- Social media integration tools

---

### Level 3: Advanced Verification
**Requirements:**
- Level 2 completion
- Enhanced due diligence (additional documentation)
- Source of funds verification
- Video call verification (optional)
- Minimum account activity (30 days)

**Benefits:**
- All investment packages ($25-$1,000)
- Premium commission rates (10%)
- Unlimited withdrawal limits
- NFT pack purchases: Unlimited
- VIP customer support
- Early access to new features

**Features Unlocked:**
- Advanced analytics and reporting
- API access for integrations
- White-label marketing materials
- Direct contact with management
- Exclusive investment opportunities

## Database Schema

### kyc_levels Table
```sql
CREATE TABLE kyc_levels (
    id INT PRIMARY KEY,
    level_number INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    badge_color VARCHAR(20),
    badge_icon VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### kyc_level_requirements Table
```sql
CREATE TABLE kyc_level_requirements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level_id INT NOT NULL,
    requirement_type ENUM('email_verification', 'phone_verification', 'profile_completion', 'document_upload', 'facial_verification', 'address_verification', 'enhanced_due_diligence', 'account_activity') NOT NULL,
    requirement_name VARCHAR(100) NOT NULL,
    description TEXT,
    is_mandatory BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (level_id) REFERENCES kyc_levels(id)
);
```

### kyc_level_benefits Table
```sql
CREATE TABLE kyc_level_benefits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level_id INT NOT NULL,
    benefit_type ENUM('investment_limit', 'commission_rate', 'withdrawal_limit', 'nft_limit', 'support_tier', 'feature_access') NOT NULL,
    benefit_name VARCHAR(100) NOT NULL,
    benefit_value VARCHAR(100),
    description TEXT,
    FOREIGN KEY (level_id) REFERENCES kyc_levels(id)
);
```

### user_kyc_levels Table
```sql
CREATE TABLE user_kyc_levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    current_level INT DEFAULT 1,
    level_1_completed_at TIMESTAMP NULL,
    level_2_completed_at TIMESTAMP NULL,
    level_3_completed_at TIMESTAMP NULL,
    level_1_progress JSON,
    level_2_progress JSON,
    level_3_progress JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (current_level) REFERENCES kyc_levels(id)
);
```

### kyc_level_progress Table
```sql
CREATE TABLE kyc_level_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    level_id INT NOT NULL,
    requirement_id INT NOT NULL,
    status ENUM('not_started', 'in_progress', 'completed', 'failed') DEFAULT 'not_started',
    completed_at TIMESTAMP NULL,
    verification_data JSON,
    notes TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (level_id) REFERENCES kyc_levels(id),
    FOREIGN KEY (requirement_id) REFERENCES kyc_level_requirements(id),
    UNIQUE KEY unique_user_requirement (user_id, requirement_id)
);
```

## Progress Calculation
- Level 1: 4 requirements (25% each)
- Level 2: 4 requirements (25% each) 
- Level 3: 5 requirements (20% each)

## Commission Rate Integration
- Level 1: 5% commission on referrals
- Level 2: 7% commission on referrals  
- Level 3: 10% commission on referrals

## Investment Limits Integration
- Level 1: $25-$100 packages only
- Level 2: $25-$500 packages
- Level 3: All packages ($25-$1,000)

## UI Components Needed
1. KYC Level Badge Component
2. Progress Indicator Component
3. Requirements Checklist Component
4. Benefits Display Component
5. Level Upgrade Flow Component
6. Admin Level Management Interface

## API Endpoints Needed
1. GET /api/kyc/levels - Get all KYC levels
2. GET /api/kyc/user-level/{userId} - Get user's current level and progress
3. POST /api/kyc/check-requirements - Check if user meets level requirements
4. POST /api/kyc/upgrade-level - Upgrade user to next level
5. GET /api/kyc/benefits/{levelId} - Get benefits for specific level
6. POST /api/admin/kyc/manage-levels - Admin level management

## Integration Points
1. Investment system - check level before allowing package purchase
2. Commission system - apply level-based commission rates
3. Withdrawal system - enforce level-based limits
4. NFT system - enforce level-based purchase limits
5. Support system - assign priority based on level
6. Feature access - show/hide features based on level

#!/bin/bash

# ============================================================================
# PRODUCTION DEPLOYMENT SCRIPT FOR AUREUS ANGEL ALLIANCE
# ============================================================================
# This script deploys the application to production with all optimizations
# ============================================================================

set -e  # Exit on any error

# Configuration
PROJECT_NAME="aureus-angel-alliance"
DOMAIN="aureusangels.com"
DEPLOY_USER="deploy"
WEB_ROOT="/var/www/html"
BACKUP_DIR="/var/backups/aureus"
LOG_DIR="/var/log/aureus"
NODE_VERSION="18"
PHP_VERSION="8.1"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Logging
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"
}

error() {
    echo -e "${RED}[ERROR] $1${NC}" >&2
}

warning() {
    echo -e "${YELLOW}[WARNING] $1${NC}"
}

info() {
    echo -e "${BLUE}[INFO] $1${NC}"
}

# Check if running as root
check_root() {
    if [[ $EUID -ne 0 ]]; then
        error "This script must be run as root"
        exit 1
    fi
}

# Create necessary directories
create_directories() {
    log "Creating necessary directories..."
    
    mkdir -p "$WEB_ROOT"
    mkdir -p "$BACKUP_DIR"
    mkdir -p "$LOG_DIR"
    mkdir -p "/var/www/uploads"
    mkdir -p "/var/www/certificates"
    
    # Set permissions
    chown -R www-data:www-data "$WEB_ROOT"
    chown -R www-data:www-data "/var/www/uploads"
    chown -R www-data:www-data "/var/www/certificates"
    chmod -R 755 "$WEB_ROOT"
    chmod -R 755 "/var/www/uploads"
    chmod -R 755 "/var/www/certificates"
}

# Install system dependencies
install_dependencies() {
    log "Installing system dependencies..."
    
    # Update package list
    apt-get update
    
    # Install basic dependencies
    apt-get install -y \
        curl \
        wget \
        git \
        unzip \
        software-properties-common \
        apt-transport-https \
        ca-certificates \
        gnupg \
        lsb-release
    
    # Install PHP and extensions
    add-apt-repository ppa:ondrej/php -y
    apt-get update
    apt-get install -y \
        php${PHP_VERSION} \
        php${PHP_VERSION}-fpm \
        php${PHP_VERSION}-mysql \
        php${PHP_VERSION}-curl \
        php${PHP_VERSION}-gd \
        php${PHP_VERSION}-mbstring \
        php${PHP_VERSION}-xml \
        php${PHP_VERSION}-zip \
        php${PHP_VERSION}-bcmath \
        php${PHP_VERSION}-json \
        php${PHP_VERSION}-redis \
        php${PHP_VERSION}-imagick
    
    # Install Node.js
    curl -fsSL https://deb.nodesource.com/setup_${NODE_VERSION}.x | bash -
    apt-get install -y nodejs
    
    # Install MySQL
    apt-get install -y mysql-server mysql-client
    
    # Install Redis
    apt-get install -y redis-server
    
    # Install Apache
    apt-get install -y apache2
    
    # Enable Apache modules
    a2enmod rewrite
    a2enmod ssl
    a2enmod headers
    a2enmod deflate
    a2enmod expires
}

# Configure PHP for production
configure_php() {
    log "Configuring PHP for production..."
    
    PHP_INI="/etc/php/${PHP_VERSION}/fpm/php.ini"
    
    # Backup original php.ini
    cp "$PHP_INI" "${PHP_INI}.backup"
    
    # Configure PHP settings
    sed -i 's/expose_php = On/expose_php = Off/' "$PHP_INI"
    sed -i 's/display_errors = On/display_errors = Off/' "$PHP_INI"
    sed -i 's/display_startup_errors = On/display_startup_errors = Off/' "$PHP_INI"
    sed -i 's/log_errors = Off/log_errors = On/' "$PHP_INI"
    sed -i "s|;error_log = php_errors.log|error_log = ${LOG_DIR}/php_errors.log|" "$PHP_INI"
    sed -i 's/allow_url_fopen = On/allow_url_fopen = Off/' "$PHP_INI"
    sed -i 's/allow_url_include = On/allow_url_include = Off/' "$PHP_INI"
    sed -i 's/enable_dl = On/enable_dl = Off/' "$PHP_INI"
    sed -i 's/memory_limit = 128M/memory_limit = 256M/' "$PHP_INI"
    sed -i 's/max_execution_time = 30/max_execution_time = 30/' "$PHP_INI"
    sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 10M/' "$PHP_INI"
    sed -i 's/post_max_size = 8M/post_max_size = 20M/' "$PHP_INI"
    
    # Configure session settings
    sed -i 's/session.cookie_httponly =/session.cookie_httponly = 1/' "$PHP_INI"
    sed -i 's/session.cookie_secure =/session.cookie_secure = 1/' "$PHP_INI"
    sed -i 's/session.use_strict_mode = 0/session.use_strict_mode = 1/' "$PHP_INI"
    
    # Restart PHP-FPM
    systemctl restart php${PHP_VERSION}-fpm
}

# Configure MySQL
configure_mysql() {
    log "Configuring MySQL..."
    
    # Secure MySQL installation
    mysql -e "DELETE FROM mysql.user WHERE User='';"
    mysql -e "DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');"
    mysql -e "DROP DATABASE IF EXISTS test;"
    mysql -e "DELETE FROM mysql.db WHERE Db='test' OR Db='test\\_%';"
    mysql -e "FLUSH PRIVILEGES;"
    
    # Create database and user
    mysql -e "CREATE DATABASE IF NOT EXISTS aureus_angels_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    mysql -e "CREATE USER IF NOT EXISTS 'aureus_user'@'localhost' IDENTIFIED BY '$(openssl rand -base64 32)';"
    mysql -e "GRANT ALL PRIVILEGES ON aureus_angels_prod.* TO 'aureus_user'@'localhost';"
    mysql -e "FLUSH PRIVILEGES;"
    
    info "Database created. Please note the generated password for aureus_user."
}

# Configure Redis
configure_redis() {
    log "Configuring Redis..."
    
    # Configure Redis for production
    sed -i 's/# maxmemory <bytes>/maxmemory 256mb/' /etc/redis/redis.conf
    sed -i 's/# maxmemory-policy noeviction/maxmemory-policy allkeys-lru/' /etc/redis/redis.conf
    sed -i 's/save 900 1/# save 900 1/' /etc/redis/redis.conf
    sed -i 's/save 300 10/# save 300 10/' /etc/redis/redis.conf
    sed -i 's/save 60 10000/# save 60 10000/' /etc/redis/redis.conf
    
    # Restart Redis
    systemctl restart redis-server
    systemctl enable redis-server
}

# Build frontend assets
build_frontend() {
    log "Building frontend assets..."
    
    cd "$WEB_ROOT"
    
    # Install dependencies
    npm ci --production
    
    # Build for production
    npm run build
    
    # Optimize images
    if command -v optipng &> /dev/null; then
        find dist/assets -name "*.png" -exec optipng -o7 {} \;
    fi
    
    if command -v jpegoptim &> /dev/null; then
        find dist/assets -name "*.jpg" -exec jpegoptim --max=85 {} \;
    fi
}

# Set up file permissions
set_permissions() {
    log "Setting up file permissions..."
    
    # Set ownership
    chown -R www-data:www-data "$WEB_ROOT"
    
    # Set directory permissions
    find "$WEB_ROOT" -type d -exec chmod 755 {} \;
    
    # Set file permissions
    find "$WEB_ROOT" -type f -exec chmod 644 {} \;
    
    # Make scripts executable
    find "$WEB_ROOT" -name "*.sh" -exec chmod +x {} \;
    
    # Secure sensitive files
    chmod 600 "$WEB_ROOT/api/config/database.php"
    chmod 600 "$WEB_ROOT/.env" 2>/dev/null || true
    
    # Create upload directories with proper permissions
    mkdir -p "/var/www/uploads/kyc"
    mkdir -p "/var/www/uploads/certificates"
    mkdir -p "/var/www/uploads/payment-proofs"
    chown -R www-data:www-data "/var/www/uploads"
    chmod -R 755 "/var/www/uploads"
}

# Configure Apache virtual host
configure_apache() {
    log "Configuring Apache virtual host..."
    
    cat > /etc/apache2/sites-available/aureusangels.conf << EOF
<VirtualHost *:80>
    ServerName $DOMAIN
    ServerAlias www.$DOMAIN
    DocumentRoot $WEB_ROOT/dist
    
    # Redirect to HTTPS
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]
</VirtualHost>

<VirtualHost *:443>
    ServerName $DOMAIN
    ServerAlias www.$DOMAIN
    DocumentRoot $WEB_ROOT/dist
    
    # SSL Configuration (will be added by certbot)
    
    # Security Headers
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "DENY"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    
    # API routing
    Alias /api $WEB_ROOT/api
    <Directory "$WEB_ROOT/api">
        AllowOverride All
        Require all granted
    </Directory>
    
    # Frontend routing (SPA)
    <Directory "$WEB_ROOT/dist">
        AllowOverride All
        Require all granted
        
        # Handle SPA routing
        RewriteEngine On
        RewriteBase /
        RewriteRule ^index\.html$ - [L]
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule . /index.html [L]
    </Directory>
    
    # Static file caching
    <LocationMatch "\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$">
        ExpiresActive On
        ExpiresDefault "access plus 1 year"
        Header append Cache-Control "public"
    </LocationMatch>
    
    # Gzip compression
    <IfModule mod_deflate.c>
        AddOutputFilterByType DEFLATE text/plain
        AddOutputFilterByType DEFLATE text/html
        AddOutputFilterByType DEFLATE text/xml
        AddOutputFilterByType DEFLATE text/css
        AddOutputFilterByType DEFLATE application/xml
        AddOutputFilterByType DEFLATE application/xhtml+xml
        AddOutputFilterByType DEFLATE application/rss+xml
        AddOutputFilterByType DEFLATE application/javascript
        AddOutputFilterByType DEFLATE application/x-javascript
        AddOutputFilterByType DEFLATE application/json
    </IfModule>
    
    # Logs
    ErrorLog ${LOG_DIR}/apache_error.log
    CustomLog ${LOG_DIR}/apache_access.log combined
</VirtualHost>
EOF
    
    # Enable site
    a2ensite aureusangels.conf
    a2dissite 000-default.conf
    
    # Test configuration
    apache2ctl configtest
    
    # Restart Apache
    systemctl restart apache2
}

# Set up monitoring
setup_monitoring() {
    log "Setting up monitoring..."
    
    # Create health check endpoint
    cat > "$WEB_ROOT/api/health.php" << 'EOF'
<?php
require_once 'config/production-config.php';

header('Content-Type: application/json');

$health = healthCheck();
http_response_code($health['status'] === 'healthy' ? 200 : 503);
echo json_encode($health, JSON_PRETTY_PRINT);
EOF
    
    # Create log rotation
    cat > /etc/logrotate.d/aureusangels << EOF
${LOG_DIR}/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        systemctl reload apache2
        systemctl reload php${PHP_VERSION}-fpm
    endscript
}
EOF
}

# Create backup script
create_backup_script() {
    log "Creating backup script..."
    
    cat > /usr/local/bin/aureus-backup.sh << 'EOF'
#!/bin/bash

BACKUP_DIR="/var/backups/aureus"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="aureus_angels_prod"
DB_USER="aureus_user"

# Create backup directory
mkdir -p "$BACKUP_DIR/$DATE"

# Backup database
mysqldump -u "$DB_USER" -p "$DB_NAME" > "$BACKUP_DIR/$DATE/database.sql"

# Backup uploads
tar -czf "$BACKUP_DIR/$DATE/uploads.tar.gz" /var/www/uploads/

# Backup configuration
tar -czf "$BACKUP_DIR/$DATE/config.tar.gz" /var/www/html/api/config/

# Remove backups older than 30 days
find "$BACKUP_DIR" -type d -mtime +30 -exec rm -rf {} +

echo "Backup completed: $BACKUP_DIR/$DATE"
EOF
    
    chmod +x /usr/local/bin/aureus-backup.sh
    
    # Add to crontab
    (crontab -l 2>/dev/null; echo "0 2 * * * /usr/local/bin/aureus-backup.sh") | crontab -
}

# Final security hardening
security_hardening() {
    log "Applying security hardening..."
    
    # Disable unnecessary services
    systemctl disable bluetooth 2>/dev/null || true
    systemctl disable cups 2>/dev/null || true
    
    # Configure fail2ban
    if command -v fail2ban-server &> /dev/null; then
        systemctl enable fail2ban
        systemctl start fail2ban
    fi
    
    # Set up automatic security updates
    apt-get install -y unattended-upgrades
    echo 'Unattended-Upgrade::Automatic-Reboot "false";' >> /etc/apt/apt.conf.d/50unattended-upgrades
}

# Main deployment function
main() {
    log "Starting production deployment for Aureus Angel Alliance..."
    
    check_root
    create_directories
    install_dependencies
    configure_php
    configure_mysql
    configure_redis
    build_frontend
    set_permissions
    configure_apache
    setup_monitoring
    create_backup_script
    security_hardening
    
    log "Production deployment completed successfully!"
    info "Next steps:"
    info "1. Run SSL setup: ./ssl-setup.sh"
    info "2. Configure environment variables"
    info "3. Import database schema"
    info "4. Test the application"
}

# Run main function
main "$@"

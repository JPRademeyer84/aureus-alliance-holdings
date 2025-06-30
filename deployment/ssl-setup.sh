#!/bin/bash

# ============================================================================
# SSL CERTIFICATE SETUP FOR AUREUS ANGEL ALLIANCE
# ============================================================================
# This script sets up SSL certificates using Let's Encrypt (Certbot)
# and configures Apache/Nginx for HTTPS
# ============================================================================

set -e  # Exit on any error

# Configuration
DOMAIN="aureusangels.com"
WWW_DOMAIN="www.aureusangels.com"
EMAIL="admin@aureusangels.com"
WEBROOT="/var/www/html"
WEB_SERVER="apache"  # or "nginx"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging function
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

# Update system packages
update_system() {
    log "Updating system packages..."
    
    if command -v apt-get &> /dev/null; then
        apt-get update
        apt-get upgrade -y
    elif command -v yum &> /dev/null; then
        yum update -y
    else
        error "Unsupported package manager"
        exit 1
    fi
}

# Install Certbot
install_certbot() {
    log "Installing Certbot..."
    
    if command -v apt-get &> /dev/null; then
        apt-get install -y certbot
        
        if [[ "$WEB_SERVER" == "apache" ]]; then
            apt-get install -y python3-certbot-apache
        elif [[ "$WEB_SERVER" == "nginx" ]]; then
            apt-get install -y python3-certbot-nginx
        fi
    elif command -v yum &> /dev/null; then
        yum install -y certbot
        
        if [[ "$WEB_SERVER" == "apache" ]]; then
            yum install -y python3-certbot-apache
        elif [[ "$WEB_SERVER" == "nginx" ]]; then
            yum install -y python3-certbot-nginx
        fi
    else
        error "Unsupported package manager"
        exit 1
    fi
}

# Configure firewall
configure_firewall() {
    log "Configuring firewall..."
    
    if command -v ufw &> /dev/null; then
        ufw allow 80/tcp
        ufw allow 443/tcp
        ufw --force enable
    elif command -v firewall-cmd &> /dev/null; then
        firewall-cmd --permanent --add-service=http
        firewall-cmd --permanent --add-service=https
        firewall-cmd --reload
    else
        warning "No supported firewall found. Please manually open ports 80 and 443"
    fi
}

# Create webroot directory
create_webroot() {
    log "Creating webroot directory..."
    
    mkdir -p "$WEBROOT"
    chown -R www-data:www-data "$WEBROOT" 2>/dev/null || chown -R apache:apache "$WEBROOT" 2>/dev/null || true
    chmod -R 755 "$WEBROOT"
}

# Obtain SSL certificate
obtain_certificate() {
    log "Obtaining SSL certificate for $DOMAIN and $WWW_DOMAIN..."
    
    if [[ "$WEB_SERVER" == "apache" ]]; then
        certbot --apache \
            --non-interactive \
            --agree-tos \
            --email "$EMAIL" \
            --domains "$DOMAIN,$WWW_DOMAIN" \
            --redirect
    elif [[ "$WEB_SERVER" == "nginx" ]]; then
        certbot --nginx \
            --non-interactive \
            --agree-tos \
            --email "$EMAIL" \
            --domains "$DOMAIN,$WWW_DOMAIN" \
            --redirect
    else
        # Webroot method
        certbot certonly \
            --webroot \
            --webroot-path="$WEBROOT" \
            --non-interactive \
            --agree-tos \
            --email "$EMAIL" \
            --domains "$DOMAIN,$WWW_DOMAIN"
    fi
}

# Configure Apache SSL
configure_apache_ssl() {
    log "Configuring Apache SSL..."
    
    # Enable SSL module
    a2enmod ssl
    a2enmod rewrite
    a2enmod headers
    
    # Create SSL virtual host configuration
    cat > /etc/apache2/sites-available/aureusangels-ssl.conf << EOF
<VirtualHost *:443>
    ServerName $DOMAIN
    ServerAlias $WWW_DOMAIN
    DocumentRoot $WEBROOT
    
    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/$DOMAIN/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/$DOMAIN/privkey.pem
    
    # Security Headers
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "DENY"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' https:; connect-src 'self' https:; frame-ancestors 'none';"
    
    # PHP Configuration
    <Directory "$WEBROOT">
        AllowOverride All
        Require all granted
        
        # Disable server signature
        ServerSignature Off
        
        # Hide PHP version
        Header unset X-Powered-By
        
        # Prevent access to sensitive files
        <FilesMatch "\.(htaccess|htpasswd|ini|log|sh|sql|conf)$">
            Require all denied
        </FilesMatch>
    </Directory>
    
    # Error and Access Logs
    ErrorLog \${APACHE_LOG_DIR}/aureusangels_error.log
    CustomLog \${APACHE_LOG_DIR}/aureusangels_access.log combined
    
    # Gzip Compression
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
    </IfModule>
</VirtualHost>

# HTTP to HTTPS Redirect
<VirtualHost *:80>
    ServerName $DOMAIN
    ServerAlias $WWW_DOMAIN
    
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]
</VirtualHost>
EOF
    
    # Enable the site
    a2ensite aureusangels-ssl.conf
    a2dissite 000-default.conf
    
    # Test configuration
    apache2ctl configtest
    
    # Restart Apache
    systemctl restart apache2
}

# Configure Nginx SSL
configure_nginx_ssl() {
    log "Configuring Nginx SSL..."
    
    cat > /etc/nginx/sites-available/aureusangels << EOF
server {
    listen 80;
    server_name $DOMAIN $WWW_DOMAIN;
    return 301 https://\$server_name\$request_uri;
}

server {
    listen 443 ssl http2;
    server_name $DOMAIN $WWW_DOMAIN;
    root $WEBROOT;
    index index.php index.html index.htm;
    
    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/$DOMAIN/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/$DOMAIN/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    
    # Security Headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "DENY" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' https:; connect-src 'self' https:; frame-ancestors 'none';" always;
    
    # PHP Configuration
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # Static file caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|pdf|txt)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # Security
    location ~ /\. {
        deny all;
    }
    
    location ~* \.(htaccess|htpasswd|ini|log|sh|sql|conf)$ {
        deny all;
    }
}
EOF
    
    # Enable the site
    ln -sf /etc/nginx/sites-available/aureusangels /etc/nginx/sites-enabled/
    rm -f /etc/nginx/sites-enabled/default
    
    # Test configuration
    nginx -t
    
    # Restart Nginx
    systemctl restart nginx
}

# Setup automatic renewal
setup_auto_renewal() {
    log "Setting up automatic certificate renewal..."
    
    # Create renewal script
    cat > /usr/local/bin/certbot-renew.sh << 'EOF'
#!/bin/bash
/usr/bin/certbot renew --quiet --no-self-upgrade

# Restart web server after renewal
if systemctl is-active --quiet apache2; then
    systemctl reload apache2
elif systemctl is-active --quiet nginx; then
    systemctl reload nginx
fi
EOF
    
    chmod +x /usr/local/bin/certbot-renew.sh
    
    # Add to crontab
    (crontab -l 2>/dev/null; echo "0 3 * * * /usr/local/bin/certbot-renew.sh") | crontab -
}

# Test SSL configuration
test_ssl() {
    log "Testing SSL configuration..."
    
    # Test certificate
    echo | openssl s_client -servername "$DOMAIN" -connect "$DOMAIN:443" 2>/dev/null | openssl x509 -noout -dates
    
    # Test HTTP to HTTPS redirect
    curl -I "http://$DOMAIN" 2>/dev/null | grep -i "location: https://"
    
    info "SSL configuration completed successfully!"
    info "Your site should now be accessible at https://$DOMAIN"
}

# Main execution
main() {
    log "Starting SSL setup for Aureus Angel Alliance..."
    
    check_root
    update_system
    install_certbot
    configure_firewall
    create_webroot
    obtain_certificate
    
    if [[ "$WEB_SERVER" == "apache" ]]; then
        configure_apache_ssl
    elif [[ "$WEB_SERVER" == "nginx" ]]; then
        configure_nginx_ssl
    fi
    
    setup_auto_renewal
    test_ssl
    
    log "SSL setup completed successfully!"
}

# Run main function
main "$@"

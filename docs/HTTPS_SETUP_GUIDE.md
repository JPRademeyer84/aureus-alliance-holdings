# HTTPS/TLS Setup Guide for Aureus Angel Alliance

## 🔒 Overview

This guide provides comprehensive instructions for setting up HTTPS/TLS encryption for the Aureus Angel Alliance platform, ensuring bank-level security for data in transit.

## 🎯 Security Requirements

- **TLS 1.2 minimum** (TLS 1.3 recommended)
- **Strong cipher suites** (AES-256-GCM preferred)
- **HSTS enabled** with preload
- **Perfect Forward Secrecy** (PFS)
- **Certificate transparency** compliance

## 🔧 XAMPP Development Setup

### Step 1: Enable SSL Module in XAMPP

1. Open XAMPP Control Panel
2. Click "Config" next to Apache
3. Select "httpd.conf"
4. Uncomment the following lines:
   ```apache
   LoadModule ssl_module modules/mod_ssl.so
   Include conf/extra/httpd-ssl.conf
   ```

### Step 2: Generate Self-Signed Certificate (Development)

```bash
# Navigate to XAMPP Apache directory
cd C:\xampp\apache

# Generate private key
openssl genrsa -out server.key 2048

# Generate certificate signing request
openssl req -new -key server.key -out server.csr

# Generate self-signed certificate
openssl x509 -req -days 365 -in server.csr -signkey server.key -out server.crt

# Move certificates to conf/ssl.crt/ and conf/ssl.key/
```

### Step 3: Configure Apache SSL

Edit `C:\xampp\apache\conf\extra\httpd-ssl.conf`:

```apache
<VirtualHost _default_:443>
    DocumentRoot "C:/xampp/htdocs"
    ServerName localhost:443
    
    # SSL Configuration
    SSLEngine on
    SSLProtocol all -SSLv2 -SSLv3 -TLSv1 -TLSv1.1
    SSLCipherSuite ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305
    SSLHonorCipherOrder on
    
    # Certificate files
    SSLCertificateFile "conf/ssl.crt/server.crt"
    SSLCertificateKeyFile "conf/ssl.key/server.key"
    
    # Security headers
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    
    # OCSP Stapling
    SSLUseStapling on
    SSLStaplingCache "shmcb:logs/stapling-cache(150000)"
</VirtualHost>
```

## 🌐 Production Setup

### Step 1: Obtain SSL Certificate

#### Option A: Let's Encrypt (Free)
```bash
# Install Certbot
sudo apt-get install certbot python3-certbot-apache

# Obtain certificate
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com
```

#### Option B: Commercial Certificate
1. Purchase from trusted CA (DigiCert, GlobalSign, etc.)
2. Generate CSR with your domain details
3. Complete domain validation
4. Install certificate files

### Step 2: Apache Production Configuration

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    
    # Redirect all HTTP to HTTPS
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</VirtualHost>

<VirtualHost *:443>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /var/www/html/aureus-angel-alliance
    
    # SSL Configuration
    SSLEngine on
    SSLProtocol all -SSLv2 -SSLv3 -TLSv1 -TLSv1.1
    SSLCipherSuite ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256
    SSLHonorCipherOrder on
    SSLCompression off
    SSLSessionTickets off
    
    # Certificate files
    SSLCertificateFile /etc/ssl/certs/yourdomain.com.crt
    SSLCertificateKeyFile /etc/ssl/private/yourdomain.com.key
    SSLCertificateChainFile /etc/ssl/certs/yourdomain.com-chain.crt
    
    # OCSP Stapling
    SSLUseStapling on
    SSLStaplingResponderTimeout 5
    SSLStaplingReturnResponderErrors off
    SSLStaplingCache "shmcb:/var/run/ocsp(128000)"
    
    # Security Headers
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'"
    
    # PHP Security
    php_admin_value session.cookie_secure 1
    php_admin_value session.cookie_httponly 1
    php_admin_value session.cookie_samesite Strict
</VirtualHost>
```

## 🔐 Environment Configuration

Update your `.env` file:

```env
# TLS/HTTPS Configuration
FORCE_HTTPS=true
HSTS_MAX_AGE=31536000
HSTS_INCLUDE_SUBDOMAINS=true
HSTS_PRELOAD=true
CSP_ALLOWED_ORIGINS=yourdomain.com www.yourdomain.com
```

## 🧪 Testing HTTPS Configuration

### 1. SSL Labs Test
Visit: https://www.ssllabs.com/ssltest/
- Should achieve A+ rating
- Check for proper cipher suites
- Verify HSTS implementation

### 2. Internal Testing
Use the built-in TLS management API:

```bash
# Test TLS status
curl -X GET "https://yourdomain.com/api/admin/tls-management.php?action=status"

# Validate configuration
curl -X GET "https://yourdomain.com/api/admin/tls-management.php?action=validate"

# Generate security report
curl -X GET "https://yourdomain.com/api/admin/tls-management.php?action=report"
```

### 3. Command Line Testing

```bash
# Test SSL connection
openssl s_client -connect yourdomain.com:443 -servername yourdomain.com

# Check certificate details
openssl x509 -in certificate.crt -text -noout

# Test cipher suites
nmap --script ssl-enum-ciphers -p 443 yourdomain.com
```

## 🛡️ Security Best Practices

### 1. Certificate Management
- Use 2048-bit RSA or 256-bit ECDSA keys minimum
- Implement certificate pinning for mobile apps
- Set up certificate transparency monitoring
- Automate certificate renewal

### 2. Protocol Configuration
- Disable SSLv2, SSLv3, TLSv1.0, TLSv1.1
- Enable TLS 1.2 and 1.3 only
- Use strong cipher suites with PFS
- Enable OCSP stapling

### 3. Security Headers
- Implement HSTS with preload
- Set proper CSP policies
- Use secure cookie flags
- Enable CSRF protection

### 4. Monitoring
- Monitor certificate expiration
- Track SSL/TLS errors
- Log security events
- Set up alerting for issues

## 🚨 Troubleshooting

### Common Issues

1. **Mixed Content Warnings**
   - Ensure all resources load over HTTPS
   - Update hardcoded HTTP URLs
   - Use protocol-relative URLs

2. **Certificate Errors**
   - Verify certificate chain
   - Check domain name matching
   - Ensure certificate is not expired

3. **Performance Issues**
   - Enable HTTP/2
   - Use session resumption
   - Optimize cipher suite selection

### Debug Commands

```bash
# Check certificate chain
openssl verify -CAfile ca-bundle.crt certificate.crt

# Test specific TLS version
openssl s_client -connect domain.com:443 -tls1_2

# Check HSTS headers
curl -I https://yourdomain.com
```

## 📋 Compliance Checklist

- [ ] TLS 1.2+ enabled
- [ ] Strong cipher suites configured
- [ ] HSTS implemented with preload
- [ ] Certificate from trusted CA
- [ ] OCSP stapling enabled
- [ ] Security headers configured
- [ ] HTTP to HTTPS redirection
- [ ] Certificate monitoring setup
- [ ] Regular security testing
- [ ] Documentation updated

## 🔄 Maintenance

### Monthly Tasks
- Check certificate expiration dates
- Review SSL Labs test results
- Update cipher suite configuration
- Monitor security logs

### Quarterly Tasks
- Update TLS configuration
- Review security headers
- Test disaster recovery procedures
- Update documentation

---

**Note**: This configuration provides bank-level security for data in transit. Regular monitoring and updates are essential to maintain security posture.

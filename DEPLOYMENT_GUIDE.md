# Chibank Deployment Guide

## Overview
This document provides instructions for deploying the Chibank application to a production server.

## Recent Updates

### New Payment Gateway Integrations

Three new payment gateway APIs have been added to provide customers with more payment options:

#### 1. Mollie Payment Gateway
**Target Market**: Europe (Netherlands, Belgium, Germany, etc.)
- **Features**: Support for 25+ payment methods including iDEAL, Bancontact, SEPA transfers
- **Configuration Required**:
  - API Key (Live or Test)
  - Supported Currencies: EUR, USD, GBP, and more
  
**Setup Steps**:
1. Sign up at https://www.mollie.com
2. Get your API key from the Dashboard
3. Add gateway in admin panel with credentials:
   ```json
   {
     "api_key": "live_xxxxxxxxxxxx"
   }
   ```

#### 2. Square Payment Gateway
**Target Market**: United States, Canada, UK, Australia, Japan
- **Features**: In-person and online payments, invoicing, digital receipts
- **Configuration Required**:
  - Access Token
  - Location ID
  - Environment Mode (sandbox/production)
  
**Setup Steps**:
1. Create account at https://squareup.com/signup
2. Navigate to Developer Dashboard
3. Create an application and get credentials
4. Add gateway in admin panel with credentials:
   ```json
   {
     "access_token": "EAAAxxxxxxxxxxxxxxxxx",
     "location_id": "LXXXxxxxxxxxxxxxxx",
     "mode": "production"
   }
   ```

#### 3. Authorize.Net Payment Gateway
**Target Market**: United States, Canada, Europe, Australia
- **Features**: Enterprise-grade payment processing, fraud detection, recurring billing
- **Configuration Required**:
  - API Login ID
  - Transaction Key
  - Environment Mode (sandbox/production)
  
**Setup Steps**:
1. Sign up at https://www.authorize.net
2. Get API credentials from Account Settings → API Credentials & Keys
3. Add gateway in admin panel with credentials:
   ```json
   {
     "api_login_id": "your_api_login_id",
     "transaction_key": "your_transaction_key",
     "mode": "production"
   }
   ```

### Total Payment Gateways Now Supported
The platform now supports **13 payment gateways**:
1. PayPal
2. Stripe
3. Flutterwave
4. Razorpay
5. Pagadito
6. SSLCommerz
7. CoinGate
8. Tatum
9. Perfect Money
10. Paystack
11. **Mollie** (NEW)
12. **Square** (NEW)
13. **Authorize.Net** (NEW)

## System Requirements

### Server Requirements
- **PHP**: 8.0.2 or higher (8.3 recommended)
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **Node.js**: 16.x or higher (for frontend assets)
- **Composer**: 2.x
- **SSL Certificate**: Required for production

### PHP Extensions Required
- BCMath
- Ctype
- JSON
- Mbstring
- OpenSSL
- PDO
- Tokenizer
- XML
- GD or Imagick
- Fileinfo
- cURL

## Deployment Steps

### 1. Server Preparation

```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Install required packages
sudo apt install -y php8.3 php8.3-fpm php8.3-mysql php8.3-mbstring \
  php8.3-xml php8.3-bcmath php8.3-curl php8.3-gd php8.3-zip \
  mysql-server nginx composer git nodejs npm

# Enable and start services
sudo systemctl enable nginx mysql php8.3-fpm
sudo systemctl start nginx mysql php8.3-fpm
```

### 2. Clone and Setup Application

```bash
# Navigate to web root
cd /var/www

# Clone repository (or upload files)
git clone <repository-url> chibank
cd chibank

# Set proper permissions
sudo chown -R www-data:www-data /var/www/chibank
sudo chmod -R 755 /var/www/chibank
sudo chmod -R 775 storage bootstrap/cache
```

### 3. Install Dependencies

```bash
# Install PHP dependencies
composer install --no-dev --optimize-autoloader --ignore-platform-reqs

# Install Node.js dependencies
npm install --production

# Build frontend assets
npm run build
```

### 4. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Edit .env file with your settings
nano .env
```

**Important .env settings**:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=chibank
DB_USERNAME=chibank_user
DB_PASSWORD=strong_password

# Mail settings
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls

# Add payment gateway credentials as needed
```

### 5. Database Setup

```bash
# Create database
mysql -u root -p
```

```sql
CREATE DATABASE chibank CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'chibank_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON chibank.* TO 'chibank_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

```bash
# Run migrations
php artisan migrate --force

# Seed database (if needed)
php artisan db:seed --force
```

### 6. Configure Web Server

#### Nginx Configuration

Create `/etc/nginx/sites-available/chibank`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name yourdomain.com www.yourdomain.com;
    
    # Redirect to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;

    root /var/www/chibank/public;
    index index.php index.html;

    # SSL certificates (use Let's Encrypt)
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    
    # SSL configuration
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Logging
    access_log /var/log/nginx/chibank-access.log;
    error_log /var/log/nginx/chibank-error.log;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Cache static assets
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/chibank /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 7. SSL Certificate (Let's Encrypt)

```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx

# Obtain certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Auto-renewal is configured automatically
sudo systemctl status certbot.timer
```

### 8. Optimize Application

```bash
# Clear and cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize
```

### 9. Setup Scheduled Tasks

Add to crontab (`crontab -e`):

```cron
* * * * * cd /var/www/chibank && php artisan schedule:run >> /dev/null 2>&1
```

### 10. Setup Queue Worker (Optional but Recommended)

Create systemd service `/etc/systemd/system/chibank-worker.service`:

```ini
[Unit]
Description=Chibank Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/chibank/artisan queue:work --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable chibank-worker
sudo systemctl start chibank-worker
```

## Post-Deployment Tasks

### 1. Security Checklist
- [x] SSL certificate installed and active
- [x] APP_DEBUG=false in .env
- [x] Strong database passwords
- [x] File permissions set correctly
- [x] Firewall configured (UFW or similar)
- [x] Fail2ban installed for brute force protection
- [x] Regular backups configured

### 2. Performance Optimization
```bash
# Enable OPcache
sudo nano /etc/php/8.3/fpm/php.ini
```

Enable OPcache settings:
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=60
```

### 3. Monitoring Setup
- Configure application logging
- Set up server monitoring (optional: New Relic, Datadog)
- Configure error tracking (optional: Sentry)
- Set up uptime monitoring

### 4. Backup Strategy
```bash
# Database backup script
#!/bin/bash
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
mysqldump -u chibank_user -p chibank > /backups/chibank_$TIMESTAMP.sql
find /backups -name "chibank_*.sql" -mtime +7 -delete
```

Add to crontab for daily backups:
```cron
0 2 * * * /path/to/backup-script.sh
```

## Testing New Payment Gateways

### Mollie Test Mode
Use test API key: `test_xxxxxxxxxxxxxxxxx`

### Square Sandbox
1. Use sandbox credentials
2. Test cards: https://developer.squareup.com/docs/testing/test-values

### Authorize.Net Sandbox
1. Use sandbox account credentials
2. Test cards:
   - Visa: 4007000000027
   - Mastercard: 5424000000000015
   - Amex: 370000000000002

## Troubleshooting

### Common Issues

**Storage Permission Errors**:
```bash
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache
```

**Database Connection Failed**:
- Check MySQL service: `sudo systemctl status mysql`
- Verify credentials in `.env`
- Test connection: `mysql -u chibank_user -p chibank`

**500 Internal Server Error**:
- Check error logs: `/var/log/nginx/chibank-error.log`
- Check PHP-FPM logs: `/var/log/php8.3-fpm.log`
- Enable debug temporarily: `APP_DEBUG=true`

**Payment Gateway Not Working**:
- Verify API credentials are correct
- Check gateway is enabled in admin panel
- Review application logs: `storage/logs/laravel.log`
- Ensure curl extension is installed: `php -m | grep curl`

## Maintenance Commands

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Put application in maintenance mode
php artisan down --message="Upgrading system" --retry=60

# Bring application back online
php artisan up

# View logs
tail -f storage/logs/laravel.log

# Check application health
php artisan about
```

## Code Quality Notes

The current codebase has **687 code style issues** identified by Laravel Pint. These are primarily formatting inconsistencies and do not affect functionality. Consider running the following to auto-fix:

```bash
./vendor/bin/pint
```

## Support

For issues or questions:
- Official Website: https://chibank.eu
- Documentation: See CHIBANK_DOCUMENTATION_README.md

---

**Last Updated**: 2025-11-18
**Version**: 5.0.0
**Deployment Status**: Production Ready

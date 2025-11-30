# codono-V7.3.3
Codono Cryptocurrency exchange + all addons 7.7.3
## Prerequisites  
 NB:do not change  codebase and webserver folders name (http://yourwebsite.com/install_check.php)
- VPS (do not use usa/uk ip) with at least 4GB RAM, 2 CPU cores, 50GB SSD (recommended for production)
- Domain name (e.g., yourdomain.com) pointed to the VPS IP
- cPanel/WHM installed (version 11+ recommended)(virtualmin)(hestia)
- Binance api + secret with trading withdraw deposit permissions whitelisted vps ip
- use PHP 7.4
## Step 1: Initial Server Setup and Dependencies

### 1.1 Update System and Install Basic Tools
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y wget curl git unzip nano vim software-properties-common
```

### 1.2 Install Apache, PHP, and Required Extensions via cPanel (EasyApache)
# In WHM: Home > Software > EasyApache 4 > Customize
# Or via command-line (if available):
```bash
# Install PHP 7.4+ with required extensions make sure exec()  is enabled
sudo yum install -y php php-cli php-common php-curl php-gd php-json php-mbstring php-mysql php-xml php-zip php-bcmath php-gmp php-pdo php-tokenizer php-fileinfo php-ctype php-openssl php-stream-socket-server

# Enable mod_rewrite for Apache

sudo a2enmod rewrite
```

### 1.3 Install MySQL/MariaDB
# In cPanel: Home > SQL Services > MySQL/MariaDB Upgrade

# Or via command-line:
```bash
sudo apt install -y mariadb-server mariadb-client
sudo systemctl start mariadb
sudo systemctl enable mariadb
sudo mysql_secure_installation
```

### 1.4 Install Redis
```bash
sudo apt install -y redis-server
sudo systemctl start redis-server
sudo systemctl enable redis-server
```

### 1.5 Install Node.js and npm (for frontend assets if needed)
```bash
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs
```

## Step 2: Domain and SSL Setup in cPanel

### 2.1 Create cPanel Account
# In WHM: Home > Account Functions > Create a New Account
# - Domain: yourdomain.com
# - Username: codono
# - Password: [secure password]
# - Package: Default or custom with unlimited resources

### 2.2 Install SSL Certificate
# In cPanel: Security > Let's Encrypt
# - Issue certificate for yourdomain.com and www.yourdomain.com

### 2.3 Create Database
# In cPanel: Databases > MySQL Databases
# - Database Name: codono_db
# - Database User: codono_user
# - Password: [secure password]
# - Add user to database with ALL privileges

## Step 3: upload and Extract Codono Files
### 3.1 Set Proper Permissions
```bash
sudo chown -R codono:codono /home/codono/public_html
sudo chmod -R 755 /home/codono/public_html
sudo chmod -R 777 /home/codono/public_html/webserver/Upload
sudo chmod -R 777 /home/codono/public_html/codebase/Database

if you are confused use www-data   as owner  and edit 
```

## Step 4: Database Setup

### 4.1 Import Database Schema
```bash
mysql -u codono_user -p codono_db < codono_production_7.7.1.sql
# Enter the database password when prompted(or use installation manual to see images)
```

### 4.2 Configure Database Connection
# Edit codebase/pure_config.php and codebase/other_config.php
```bash
nano codebase/pure_config.php
```
# Update database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'codono_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'codono_db');
```

## Step 5: Web Server Configuration

### 5.1 Apache Virtual Host Configuration
# For detailed Apache configuration, create or edit the virtual host file.
# Example virtual host configuration (similar to app.localhost.conf):(use your domain name instaed of app.localhost)
# In WHM: Home > Service Configuration > Apache Configuration > Include Editor
# Or edit /etc/apache2/sites-available/yourdomain.com.conf (Ubuntu) or /usr/local/apache/conf/includes/pre_virtualhost_global.conf (cPanel)

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /home/codono/public_html/webserver

    <Directory /home/codono/public_html/webserver>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog /home/codono/logs/error_log
    CustomLog /home/codono/logs/access_log combined

    # Redirect HTTP to HTTPS
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</VirtualHost>

<VirtualHost *:443>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /home/codono/public_html/webserver

    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/yourdomain.com.crt
    SSLCertificateKeyFile /etc/ssl/private/yourdomain.com.key
    SSLCertificateChainFile /etc/ssl/certs/yourdomain.com.ca-bundle

    <Directory /home/codono/public_html/webserver>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog /home/codono/logs/error_log
    CustomLog /home/codono/logs/access_log combined
</VirtualHost>
```

# Replace "yourdomain.com" with your actual domain name throughout the configuration.
# Enable the site: sudo a2ensite yourdomain.com.conf && sudo systemctl reload apache2
# For cPanel, use the Include Editor to add this configuration.

### 5.2 Configure .htaccess
 webserver/.htaccess
```


### 5.3 Configure PHP Settings
# In cPanel: Software > MultiPHP Manager
# Set PHP version to 7.4+ for the domain
# Or via .htaccess:
```apache
# Add to webserver/.htaccess
php_value upload_max_filesize 50M
php_value post_max_size 50M
php_value memory_limit 256M
php_value max_execution_time 600
```

## Step 6: Redis Configuration

### 6.1 Configure Redis Password
```bash
sudo nano /etc/redis.conf
```
# Add or modify:
```
requirepass your_redis_password
```

### 6.2 Restart Redis
```bash
sudo systemctl restart redis
```

### 6.3 Update Codono Config for Redis
# Edit codebase/pure_config.php
```php
define('REDIS_HOST', '127.0.0.1');
define('REDIS_PORT', 6379);
define('REDIS_PASS', 'your_redis_password');

```
check requirement before install :
http://yourwebsite.com/install_check.php

## Step 7: Admin Access Setup

### 7.1 Set Admin Key
# Edit codebase/Framework/codono.php
```php
define('ADMIN_KEY', '12345678'); // Change to your secure key
```

### 7.2 Access Admin Panel
# Visit: https://yourdomain.com/Admin
# Login with admin credentials from database or setup wizard


### 8 Configure Binance API Keys
# Edit codebase/other_config.php
```php
const BINANCE_API_KEY_1 = 'paste-your-key-here';
const BINANCE_API_SECRET_1 = 'paste-your-secret-here';
const BINANCE_API_KEY_2 = 'paste-your-key-here';
const BINANCE_API_SECRET_2 = 'paste-your-secret-here';
```

## Step 9: Cron Jobs Setup

### 9.1 Add Cron Jobs via cPanel
# In cPanel: Advanced > Cron Jobs
# Add the following cron jobs:

# Every minute for matching engine
* * * * * php /home/codono/public_html/codebase/matching.php

# Every 5 minutes for general tasks
*/5 * * * * php /home/codono/public_html/codebase/cron.php

# Hourly for cleanup
0 * * * * php /home/codono/public_html/codebase/single_task.php

# Daily backup (adjust path)
0 2 * * * mysqldump -u codono_user -p'your_db_password' codono_db > /home/codono/backup_$(date +\%Y\%m\%d).sql

## Step 10: Security Hardening

### 10.1 Firewall Setup
# In WHM: Plugins > ConfigServer Security & Firewall (CSF)
# Or via ufw (Ubuntu default):
```bash
sudo apt install -y ufw
sudo ufw enable

# Allow SSH, HTTP, HTTPS
sudo ufw allow ssh
sudo ufw allow http
sudo ufw allow https
sudo ufw --force reload
```

### 10.2 SSL Enforcement
# Add to webserver/.htaccess:
```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 10.3 File Permissions
```bash
find /home/codono/public_html -type f -exec chmod 644 {} \;
find /home/codono/public_html -type d -exec chmod 755 {} \;
chmod 777 /home/codono/public_html/webserver/Upload
chmod 777 /home/codono/public_html/codebase/Database
```

## Step 11: Testing and Verification

### 11.1 Test Homepage
```bash
curl -I https://yourdomain.com
# Should return 200 OK
```

### 11.2 Test Admin Login
# Visit: https://yourdomain.com/Admin
# Use admin credentials



### 11.4 Test Database Connection
# Check if tables are populated and connection works

## Step 12: Post-Installation Configuration

### 12.1 Configure Site Settings
# In Admin Panel:
# - Set site name, logo, etc.
# - Configure payment gateways
# - Set up email settings
# - Configure trading pairs

### 12.2 Enable Trading
# In Admin Panel > Market Management
# Enable desired trading pairs

### 12.3 Set Up Wallets
# Configure hot/cold wallets for supported cryptocurrencies

## Troubleshooting

### Common Issues:
1. **403 Forbidden**: Check .htaccess Files directive
2. **Database Connection Error**: Verify credentials in pure_config.php
3. **Redis Connection Failed**: Check Redis password and service status
4. **PHP Extension Missing**: Install missing extensions via EasyApache
5. **Permission Denied**: Fix file permissions as described
6. **Service Unavailable (503 Error)**: Check PHP version compatibility, Redis connection, or database issues
7. **Blank Page or No Output**: Enable error reporting in PHP or check logs for fatal errors
8. **PHP Version Incompatibility**: Ensure PHP 7.4+ is used; check with `php -v`
9. **Redis Version Issues**: Ensure Redis 5+ is installed; check with `redis-server --version`
10. **MySQL Connection Timeout**: Verify MySQL/MariaDB is running and accessible

### System Requirements Check:
# Run this script to verify system compatibility:
```bash
#!/bin/bash
echo "PHP Version: $(php -v | head -1)"
echo "Redis Version: $(redis-server --version)"
echo "MySQL Version: $(mysql --version)"
echo "Apache Version: $(apache2ctl -v | head -1)"
echo "Node.js Version: $(node -v)"
echo "NPM Version: $(npm -v)"
echo "Disk Space: $(df -h / | tail -1 | awk '{print $4}')"
echo "Memory: $(free -h | grep Mem | awk '{print $2}')"
```

## Final Notes
- Change all default passwords and keys
- Regularly update the system and Codono
- Monitor server resources and logs
- Set up monitoring and alerts
- Consider load balancing for high traffic

This installation should result in a fully functional Codono exchange. For detailed configuration, refer to the Docs/ folder in the installation.

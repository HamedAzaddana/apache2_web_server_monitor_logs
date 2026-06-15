# DrSerita Monitor - VPS Setup Guide

Complete setup guide for VPS (Virtual Private Server) with root/SSH access.

## Prerequisites

- Ubuntu/Debian server with Apache 2.4+
- PHP 8.1 or higher
- SQLite3 PHP extension
- Access to SSH terminal
- Apache mod_rewrite enabled

## Step 1: Clone or Upload Files

```bash
# Option A: If you have git access
cd /var/www
git clone <your-repo-url> drserita-monitoring

# Option B: Upload files via SCP
scp -r drserita-monitoring user@server:/var/www/
```

## Step 2: Set Permissions

```bash
cd /var/www/drserita-monitoring

# Set ownership
sudo chown -R www-data:www-data .

# Set permissions
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;

# Make cache and logs writable
chmod -R 777 cache logs

# Make parser executable
chmod +x cron/parse_logs.php
```

## Step 3: Configure Apache Virtual Host

Add to your Apache config (`/etc/apache2/sites-available/drserita-le-ssl.conf`):

```apache
Alias "/monitor/logs" "/var/www/drserita-monitoring/public"

<Directory "/var/www/drserita-monitoring/public">
    Options -Indexes +FollowSymLinks
    AllowOverride All
    Require all granted
    DirectoryIndex index.php
</Directory>
```

Enable and restart:

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

## Step 4: Create Configuration

```bash
cd /var/www/drserita-monitoring
cp config.sample.php config.php
nano config.php
```

Edit these values:
```php
// Set your application title
define('APP_TITLE', 'DrSerita');

// Generate password hash using:
// php -r "echo password_hash('your-password', PASSWORD_DEFAULT);"
define('MONITOR_PASSWORD_HASH', 'YOUR_GENERATED_HASH_HERE');
```

## Step 5: Create Database & Indexes

```bash
php parsers/setup_db.php

# Create indexes for performance
sqlite3 cache/logs.db "CREATE INDEX IF NOT EXISTS idx_url ON access_logs(url);"
sqlite3 cache/logs.db "CREATE INDEX IF NOT EXISTS idx_user_agent ON access_logs(user_agent);"
sqlite3 cache/logs.db "CREATE INDEX IF NOT EXISTS idx_status_code ON access_logs(status_code);"
sqlite3 cache/logs.db "CREATE INDEX IF NOT EXISTS idx_date_time ON access_logs(date_time);"
```

## Step 6: Configure Log Rotation

Create `/etc/logrotate.d/drserita-logs`:

```
/var/log/apache2/drserita_access.log {
    daily
    rotate 7
    compress
    delaycompress
    missingok
    notifempty
    create 644 root adm
    sharedscripts
    postrotate
        # Trigger log parsing
        /usr/bin/php /var/www/drserita-monitoring/cron/parse_logs.php
    endscript
}
```

## Step 7: Setup Cron Job

Edit crontab:

```bash
crontab -e
```

Add this line:

```cron
0 */1 * * * /usr/bin/php /var/www/drserita-monitoring/cron/parse_logs.php >> /var/www/drserita-monitoring/logs/parser.log 2>&1
```

## Step 8: Verify Installation

1. Visit: `https://yourdomain.com/monitor/logs/`
2. Login with your password
3. You should see the dashboard with log data

## Troubleshooting

**No data showing:**
```bash
# Check if logs are being parsed
tail -f /var/www/drserita-monitoring/logs/parser.log

# Check if database exists
ls -la /var/www/drserita-monitoring/cache/logs.db

# Verify Apache logs exist
ls -la /var/log/apache2/drserita_access.log*
```

**Permission errors:**
```bash
sudo chown -R www-data:www-data /var/www/drserita-monitoring
chmod -R 755 /var/www/drserita-monitoring/public
chmod -R 777 /var/www/drserita-monitoring/cache
chmod -R 777 /var/www/drserita-monitoring/logs
```

**Apache 500 error:**
```bash
# Check Apache error log
tail -50 /var/log/apache2/error.log

# Verify PHP syntax
php -l /var/www/drserita-monitoring/public/index.php
```

# Apache2 Monitor - VPS Setup Guide

Complete, beginner-friendly setup guide for VPS (Virtual Private Server) with root/SSH access.

---

## Table of Contents

1. [What You Need](#what-you-need)
2. [Understanding the Architecture](#understanding-the-architecture)
3. [Step-by-Step Installation](#step-by-step-installation)
4. [Configuration](#configuration)
5. [Apache Setup](#apache-setup)
6. [Database & Indexes](#database--indexes)
7. [Cron Job Setup](#cron-job-setup)
8. [Verification](#verification)
9. [Troubleshooting](#troubleshooting)
10. [Security Best Practices](#security-best-practices)

---

## What You Need

### Prerequisites Checklist

Before starting, make sure you have:

- [ ] **Ubuntu/Debian** server (18.04+ recommended)
- [ ] **Apache 2.4+** web server
- [ ] **PHP 8.1 or higher**
- [ ] **SQLite3** PHP extension
- [ ] **SSH access** with root or sudo privileges
- [ ] **Basic terminal knowledge** (copy/paste commands)

### Check Your Server

Run these commands to verify your server meets requirements:

```bash
# Check Apache version
apache2 -v

# Check PHP version
php -v

# Check if SQLite3 is installed
php -m | grep sqlite

# Check if mod_rewrite is enabled
apache2ctl -M | grep rewrite
```

**If any are missing:**

```bash
# Install Apache
sudo apt update
sudo apt install apache2

# Install PHP and extensions
sudo apt install php php-sqlite3 php-curl php-mbstring

# Enable mod_rewrite
sudo a2enmod rewrite
sudo systemctl restart apache2
```

---

## Understanding the Architecture

Before installing, it's helpful to understand how this system works:

```
┌─────────────────────────────────────────────────────────────┐
│                    HOW IT WORKS                              │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  1. Apache writes logs to: /var/log/apache2/               │
│                                                              │
│  2. Cron job (hourly) runs: parse_logs.php                  │
│     ↓                                                        │
│     Reads Apache logs → Parses → Saves to SQLite DB         │
│                                                              │
│  3. Web interface (public/) displays data from DB           │
│                                                              │
│  4. Password protection via config.php                      │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

**Directory Structure:**

```
/var/www/drserita-monitoring/
├── cache/              # SQLite database (logs.db)
├── config.php          # Configuration file (NOT in git)
├── config.sample.php   # Template for config.php
├── cron/               # Log parser scripts
├── logs/               # Parser logs
├── models/             # Data models
├── parsers/            # Database setup scripts
└── public/             # Web interface (public facing)
    ├── api.php         # AJAX endpoints
    ├── index.php       # Main entry point
    ├── views/          # HTML templates
    ├── css/            # Stylesheets
    └── js/             # JavaScript files
```

---

## Step-by-Step Installation

### Step 1: Upload Files to Server

Choose one of these methods:

#### Option A: Using Git (Recommended if you have a repository)

```bash
# Navigate to web directory
cd /var/www

# Clone your repository
sudo git clone <your-repo-url> drserita-monitoring

# If you don't have git, install it first:
# sudo apt install git
```

#### Option B: Manual Upload via SCP

```bash
# From your local computer
scp -r drserita-monitoring user@yourserver.com:/var/www/

# Then SSH into server and continue
```

#### Option C: Create Files Directly on Server

```bash
# Create directory
sudo mkdir -p /var/www/drserita-monitoring
cd /var/www/drserita-monitoring

# Create the directory structure
sudo mkdir -p cache cron logs models parsers public/views public/css public/js
```

---

### Step 2: Set Proper Permissions

**Why this matters:** Apache runs as `www-data` user. Files must be readable by this user, and certain directories must be writable.

```bash
cd /var/www/drserita-monitoring

# Set ownership to Apache user (www-data)
sudo chown -R www-data:www-data .

# Set permissions:
# 755 for directories (readable, executable by all, writable by owner)
find . -type d -exec chmod 755 {} \;

# 644 for files (readable by all, writable only by owner)
find . -type f -exec chmod 644 {} \;

# Make cache and logs writable (for database and parser logs)
chmod -R 777 cache logs

# Make parser executable
chmod +x cron/parse_logs.php
```

**Verify permissions:**

```bash
# Should show www-data:www-data ownership
ls -la

# cache and logs should be drwxrwxrwx (777)
ls -la cache logs
```

---

## Configuration

### Step 3: Create Configuration File

```bash
cd /var/www/drserita-monitoring

# Copy the sample config
cp config.sample.php config.php
```

### Step 4: Generate Password Hash

**Security Note:** Never store plain-text passwords! We use PHP's built-in password hashing.

```bash
# Generate a secure hash for your password
php -r "echo password_hash('your-secure-password', PASSWORD_DEFAULT);"
```

**Example output:**
```
$2y$12$kbNoAISCGF3Gpkpz1rvZ.emW3jT2AC3Vac19EZg9FUxS1uMW3U9ry
```

**Copy this entire hash** - you'll need it in the next step.

### Step 5: Edit Configuration File

```bash
sudo nano config.php
```

**Edit these values:**

```php
<?php
// This file is OUTSIDE the public web root, so it's safe.

// Base directory (current directory - where config.php is located)
define('BASE_DIR', __DIR__);

// Database path
define('DB_PATH', BASE_DIR . '/cache/logs.db');

// Log directory - Where Apache stores access logs
// Default for Ubuntu/Debian Apache:
define('LOG_DIR', '/var/log/apache2/');

// Application title (shows in the UI)
define('APP_TITLE', 'Your Monitor Name');

// ⚠️ PASTE YOUR GENERATED HASH HERE ⚠️
define('MONITOR_PASSWORD_HASH', 'YOUR_GENERATED_HASH_HERE');
```

**Save and exit:** Press `Ctrl+X`, then `Y`, then `Enter`.

### Step 6: Secure Configuration File

**Critical:** Config.php contains your password hash and must be protected.

```bash
# Set ownership to web server user
sudo chown www-data:www-data config.php

# Set permissions: owner can read/write, group can read, others cannot access
sudo chmod 640 config.php

# Verify
ls -la config.php
# Should show: -rw-r----- 1 www-data www-data
```

---

## Apache Setup

### Step 7: Configure Apache Virtual Host

You have two options depending on your needs:

---

#### Option A: Add to Existing Site (Alias Method)

**Use this if:** You want to access the monitor at `https://yourdomain.com/monitor/logs`

1. Find your existing Apache config:

```bash
ls /etc/apache2/sites-available/
# Common files: 000-default.conf, yourdomain-le-ssl.conf
```

2. Edit your SSL config (recommended for HTTPS):

```bash
sudo nano /etc/apache2/sites-available/yourdomain-le-ssl.conf
```

3. Add this inside the `<VirtualHost>` block:

```apache
# Add these lines inside your existing <VirtualHost *:443> block

Alias "/monitor/logs" "/var/www/drserita-monitoring/public"

<Directory "/var/www/drserita-monitoring/public">
    Options -Indexes +FollowSymLinks
    AllowOverride All
    Require all granted
    DirectoryIndex index.php
</Directory>
```

4. Save and restart Apache:

```bash
sudo systemctl restart apache2
```

---

#### Option B: Separate VirtualHost (Custom Port)

**Use this if:** You want a separate port (like `http://yourdomain.com:4534`)

1. Create new config:

```bash
sudo nano /etc/apache2/sites-available/drserita-monitoring.conf
```

2. Add this content:

```apache
<VirtualHost *:4534>
    ServerName yourdomain.com
    DocumentRoot /var/www/drserita-monitoring/public

    <Directory /var/www/drserita-monitoring/public>
        AllowOverride All
        Require all granted
        DirectoryIndex index.php
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/monitoring_error.log
    CustomLog ${APACHE_LOG_DIR}/monitoring_access.log combined
</VirtualHost>
```

3. Enable the site and restart:

```bash
# Enable the site
sudo a2ensite drserita-monitoring.conf

# Enable required modules
sudo a2enmod rewrite
sudo a2enmod ssl

# Restart Apache
sudo systemctl restart apache2
```

4. **Firewall Note:** If using a custom port, open it in your firewall:

```bash
# UFW firewall
sudo ufw allow 4534/tcp

# Or iptables
sudo iptables -A INPUT -p tcp --dport 4534 -j ACCEPT
```

---

### Step 8: Configure Apache Log Format (Optional but Recommended)

The parser expects the `%D` directive for response time. Verify your Apache config includes it:

```bash
# Check current log format
sudo apache2ctl -M | grep -i log_config

# View log format definition
sudo grep -r "LogFormat" /etc/apache2/
```

The format should include `%D` (microseconds) at the end:

```apache
LogFormat "%h %l %u %t \"%r\" %>s %O \"%{Referer}i\" \"%{User-Agent}i\" %D" combined_with_time
```

---

## Database & Indexes

### Step 9: Initialize Database

```bash
cd /var/www/drserita-monitoring

# Run the database setup script
php parsers/setup_db.php
```

**Expected output:**

```
✓ Database created successfully at: cache/logs.db
✓ Table 'access_logs' created
```

### Step 10: Set Database Permissions

```bash
# Set proper ownership and permissions
sudo chown www-data:www-data cache/logs.db
sudo chmod 664 cache/logs.db
```

### Step 11: Create Database Indexes

**Why indexes matter:** They make searches much faster, especially with large log files.

```bash
cd /var/www/drserita-monitoring

# Create indexes for common search fields
sqlite3 cache/logs.db "CREATE INDEX IF NOT EXISTS idx_url ON access_logs(url);"
sqlite3 cache/logs.db "CREATE INDEX IF NOT EXISTS idx_user_agent ON access_logs(user_agent);"
sqlite3 cache/logs.db "CREATE INDEX IF NOT EXISTS idx_status_code ON access_logs(status_code);"
sqlite3 cache/logs.db "CREATE INDEX IF NOT EXISTS idx_date_time ON access_logs(date_time);"

# Verify indexes were created
sqlite3 cache/logs.db ".indexes access_logs"
```

**Expected output:**
```
idx_url
idx_user_agent
idx_status_code
idx_date_time
sqlite_autoindex_access_logs_1
```

---

## Cron Job Setup

### Step 12: Configure Automatic Log Parsing

The cron job runs the log parser automatically (usually every hour).

```bash
# Open crontab editor
crontab -e
```

**Choose your editor** (nano is recommended for beginners).

**Add this line at the bottom:**

```cron
# Run log parser every hour at minute 0
0 */1 * * * /usr/bin/php /var/www/drserita-monitoring/cron/parse_logs.php >> /var/www/drserita-monitoring/logs/parser.log 2>&1
```

**Save and exit:** Press `Ctrl+X`, then `Y`, then `Enter`.

### Understanding the Cron Syntax

```
┌───────────── minute (0 - 59)
│ ┌───────────── hour (0 - 23)
│ │ ┌───────────── day of month (1 - 31)
│ │ │ ┌───────────── month (1 - 12)
│ │ │ │ ┌───────────── day of week (0 - 7, 0 or 7 = Sunday)
│ │ │ │ │
* * * * * command
```

**Common schedules:**

```cron
# Every 5 minutes (for testing)
*/5 * * * * /usr/bin/php /var/www/drserita-monitoring/cron/parse_logs.php

# Every hour
0 * * * * /usr/bin/php /var/www/drserita-monitoring/cron/parse_logs.php

# Every 6 hours
0 */6 * * * /usr/bin/php /var/www/drserita-monitoring/cron/parse_logs.php

# Once daily at midnight
0 0 * * * /usr/bin/php /var/www/drserita-monitoring/cron/parse_logs.php
```

### Step 13: Test the Parser Manually

```bash
cd /var/www/drserita-monitoring

# Run the parser manually to test
php cron/parse_logs.php

# Check the output
cat logs/parser.log
```

**Expected output:**
```
[2025-06-15 12:00:00] Starting log parser...
[2025-06-15 12:00:01] Processing: /var/log/apache2/drserita_access.log
[2025-06-15 12:00:02] Parsed 1,234 entries
[2025-06-15 12:00:02] Completed successfully
```

---

## Verification

### Step 14: Test Your Installation

1. **Check if files are accessible:**

```bash
# Test the web interface
curl -I http://localhost/monitor/logs/
# Or for custom port:
curl -I http://localhost:4534/
```

**Should return:** `HTTP/1.1 200 OK` or `302 Found`

2. **Visit in browser:**

- **Option A (Alias):** `https://yourdomain.com/monitor/logs/`
- **Option B (Custom Port):** `http://yourdomain.com:4534/`

3. **You should see:** A login page with your APP_TITLE

4. **Login with:** The password you chose in Step 4

5. **Dashboard should show:**
- Charts (response time, traffic distribution)
- Recent logs table
- Filter options

### Step 15: Verify Data Flow

```bash
# Check database has data
sqlite3 cache/logs.db "SELECT COUNT(*) FROM access_logs;"

# Should return a number > 0 if logs have been parsed

# Check recent entries
sqlite3 cache/logs.db "SELECT * FROM access_logs ORDER BY id DESC LIMIT 5;"
```

---

## Troubleshooting

### Common Issues and Solutions

---

#### Issue: "Permission denied" accessing config.php

**Error:** `Warning: require_once(.../config.php): Failed to open stream: Permission denied`

**Solution:**

```bash
sudo chown www-data:www-data config.php
sudo chmod 640 config.php
```

---

#### Issue: "Unable to open database file"

**Error:** `SQLSTATE[HY000] [14] unable to open database file`

**Solution:**

```bash
cd /var/www/drserita-monitoring

# Fix cache directory permissions
sudo chmod 777 cache

# Fix database file permissions
sudo chown www-data:www-data cache/logs.db
sudo chmod 664 cache/logs.db
```

---

#### Issue: No data showing in dashboard

**Diagnosis:**

```bash
# 1. Check if database exists
ls -la cache/logs.db

# 2. Check if database has data
sqlite3 cache/logs.db "SELECT COUNT(*) FROM access_logs;"

# 3. Check parser log
tail -50 logs/parser.log

# 4. Check if Apache logs exist
ls -la /var/log/apache2/drserita_access.log*
```

**Solutions:**

1. **If database is empty:**

```bash
# Run parser manually
php cron/parse_logs.php

# Check for errors
cat logs/parser.log
```

2. **If log file path is wrong:**

```bash
# Find your actual log files
ls -la /var/log/apache2/

# Update config.php with correct LOG_DIR
sudo nano config.php
```

---

#### Issue: Login not working

**Diagnosis:**

```bash
# Check if config.php is being loaded
php -r "require_once '/var/www/drserita-monitoring/config.php'; echo APP_TITLE;"
```

**Solutions:**

1. **Regenerate password hash:**

```bash
php -r "echo password_hash('your-password', PASSWORD_DEFAULT);"
```

2. **Update config.php with new hash**

3. **Clear browser cache and try again**

---

#### Issue: Charts not displaying

**Check:**

1. **Browser console for JavaScript errors** (F12 → Console)

2. **Verify asset files are accessible:**

```bash
ls -la public/css/
ls -la public/js/
```

3. **Check API endpoint:**

```bash
# After logging in, visit:
https://yourdomain.com/monitor/logs/api.php?action=init
```

---

#### Issue: Cron job not running

**Diagnosis:**

```bash
# Check cron service is running
sudo systemctl status cron

# View cron logs
grep CRON /var/log/syslog | tail -20
```

**Solutions:**

1. **Verify PHP path:**

```bash
which php
# Common paths: /usr/bin/php, /usr/local/bin/php
```

2. **Test cron command manually:**

```bash
/usr/bin/php /var/www/drserita-monitoring/cron/parse_logs.php
```

3. **Check file permissions:**

```bash
ls -la cron/parse_logs.php
# Should be executable (-rwxr-xr-x)
```

---

#### Issue: Apache 403 Forbidden

**Diagnosis:**

```bash
# Check Apache error log
sudo tail -50 /var/log/apache2/error.log

# Check directory permissions
ls -la /var/www/drserita-monitoring/public
```

**Solution:**

```bash
# Fix permissions
sudo chmod -R 755 /var/www/drserita-monitoring/public
sudo chown -R www-data:www-data /var/www/drserita-monitoring/public

# Check .htaccess exists
ls -la public/.htaccess
```

---

#### Issue: URLs not decoded in search

**This should be fixed in the latest version.** Verify:

```bash
# Check api.php has URL decoding
grep "url_decoded" public/api.php
```

If missing, update your files from the repository.

---

## Security Best Practices

### 1. Keep Software Updated

```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Update PHP extensions
sudo apt install --only-upgrade php php-sqlite3
```

### 2. Use Strong Passwords

```bash
# Generate a strong, random password hash
php -r "echo password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);"
```

### 3. Restrict Access by IP (Optional)

Add to your Apache config:

```apache
<Directory "/var/www/drserita-monitoring/public">
    # Only allow specific IPs (comment out Require all granted)
    Require ip 192.168.1.0/24
    Require ip 203.0.113.5
</Directory>
```

### 4. Enable HTTPS (SSL/TLS)

```bash
# Install Certbot
sudo apt install certbot python3-certbot-apache

# Get free SSL certificate
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com

# Auto-renewal is configured automatically
```

### 5. Regular Backups

```bash
# Backup database
cp cache/logs.db backups/logs-$(date +%Y%m%d).db

# Backup config
cp config.php backups/config-$(date +%Y%m%d).php
```

### 6. Monitor Disk Space

```bash
# Check disk usage
df -h

# Check database size
ls -lh cache/logs.db

# If database grows too large, you can truncate old logs
# via the "Truncate All Logs" button in the dashboard
```

---

## Quick Reference

### Essential Commands

```bash
# Restart Apache
sudo systemctl restart apache2

# Check Apache status
sudo systemctl status apache2

# View Apache error log
sudo tail -f /var/log/apache2/error.log

# Run parser manually
php /var/www/drserita-monitoring/cron/parse_logs.php

# Query database
sqlite3 /var/www/drserita-monitoring/cache/logs.db

# Edit config
sudo nano /var/www/drserita-monitoring/config.php

# Edit cron
crontab -e

# Check permissions
ls -la /var/www/drserita-monitoring/
```

### File Locations

| File | Location |
|------|----------|
| Config | `/var/www/drserita-monitoring/config.php` |
| Database | `/var/www/drserita-monitoring/cache/logs.db` |
| Parser | `/var/www/drserita-monitoring/cron/parse_logs.php` |
| Parser Log | `/var/www/drserita-monitoring/logs/parser.log` |
| Apache Config | `/etc/apache2/sites-available/` |
| Apache Log | `/var/log/apache2/drserita_access.log` |

### URLs

| Access | URL |
|--------|-----|
| Web Interface | `https://yourdomain.com/monitor/logs/` |
| API Endpoint | `https://yourdomain.com/monitor/logs/api.php` |
| Custom Port | `http://yourdomain.com:4534/` |

---

## Need Help?

1. Check the troubleshooting section above
2. Review Apache error logs: `sudo tail -50 /var/log/apache2/error.log`
3. Check parser logs: `cat /var/www/drserita-monitoring/logs/parser.log`
4. Verify file permissions: `ls -la /var/www/drserita-monitoring/`

---

## Changelog

- **2025-06-15:** Added URL decoding for search, fixed UTF-8/Persian support
- **2025-06-14:** Initial release with timezone support, response time tracking

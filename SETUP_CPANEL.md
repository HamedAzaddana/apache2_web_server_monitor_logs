# Apache2 Monitor - cPanel Setup Guide

Complete, beginner-friendly setup guide for shared hosting with cPanel.

---

## Table of Contents

1. [What You Need](#what-you-need)
2. [Understanding cPanel Limitations](#understanding-cpanel-limitations)
3. [Step-by-Step Installation](#step-by-step-installation)
4. [Configuration](#configuration)
5. [Database Setup](#database-setup)
6. [Web Access Configuration](#web-access-configuration)
7. [Finding Your Log Files](#finding-your-log-files)
8. [Cron Job Setup](#cron-job-setup)
9. [Verification](#verification)
10. [Troubleshooting](#troubleshooting)
11. [cPanel-Specific Tips](#cpanel-specific-tips)

---

## What You Need

### Prerequisites Checklist

Before starting, verify your hosting provides:

- [ ] **cPanel access** (File Manager, Cron Jobs, etc.)
- [ ] **PHP 8.1 or higher**
- [ ] **SQLite3 PHP extension** (or ability to enable it)
- [ ] **Access to Terminal** (preferred) or File Manager
- [ ] **Apache web server** (most cPanel hosts use Apache)
- [ ] **Ability to set file permissions** (755/644/777)

### Check Your Hosting Environment

Log in to cPanel and check:

1. **PHP Version:**
   - Look for "MultiPHP Manager" or "Select PHP Version"
   - Verify PHP 8.1+ is available

2. **SQLite3 Extension:**
   - In "MultiPHP Manager" or "Select PHP Version"
   - Check if `sqlite3` is enabled

3. **Terminal Access:**
   - Look for "Terminal" or "SSH Access" icon

4. **Cron Jobs:**
   - Look for "Cron Jobs" icon

---

## Understanding cPanel Limitations

### What You CAN Do in cPanel:

✅ Upload files via File Manager or FTP
✅ Create and edit files
✅ Set file permissions
✅ Create databases (SQLite files)
✅ Setup cron jobs
✅ Use .htaccess files
✅ Access Terminal (if enabled by host)

### What You CANNOT Do in cPanel:

❌ Modify Apache configuration files directly
❌ Install system-level packages
❌ Change file ownership (chown)
❌ Modify system PHP configuration
❌ Access server-level logs outside your account

### Workarounds:

- **Apache Alias:** Request from hosting support OR use subdirectory method
- **Log File Access:** Logs are usually in `/home/username/access-logs/`
- **Permissions:** Use File Manager or Terminal to chmod files

---

## Step-by-Step Installation

### Step 1: Find Your Home Directory Path

Before uploading, you need to know your home directory path.

**Method A: Via Terminal**

```bash
# Log in to cPanel → Terminal
echo $HOME
# Output: /home/username
```

**Method B: Via File Manager**

1. Log in to cPanel
2. Open **File Manager**
3. Look at the path in the address bar
4. Usually shows: `/home/username`

**Example:** For this guide, we'll use `/home/username/` - replace `username` with your actual cPanel username.

---

### Step 2: Upload Files

Choose your preferred method:

#### Option A: Using File Manager (Easiest)

1. **Log in to cPanel**

2. **Open File Manager**

3. **Navigate to Home Directory** (the folder with your username)

4. **Create a new folder:**
   - Click "+ Folder" button
   - Name it: `drserita-monitoring`
   - Click "Create New Folder"

5. **Upload files:**
   - Click "Upload" button
   - Select all files from your local copy
   - Or upload a ZIP and extract it

6. **If uploading ZIP:**
   - Right-click the ZIP file
   - Select "Extract"
   - Delete the ZIP file after extraction

#### Option B: Using FTP/SFTP

```bash
# Connect to your server via FTP/SFTP
# Upload to: /home/username/drserita-monitoring/

# Or use command-line:
sftp username@yourdomain.com
put -r drserita-monitoring /home/username/
```

---

### Step 3: Set Folder Permissions

**Why this matters:** PHP needs to write to certain directories (cache, logs) to store the database and parser logs.

#### Via File Manager:

1. **Navigate to** `drserita-monitoring`

2. **Select all files and folders:**
   - Click "Select All" or Ctrl+A

3. **Change Permissions:**
   - Right-click → "Change Permissions"
   - Set: **755** for folders, **644** for files
   - Click "Change Permissions"

4. **Set writable permissions for cache and logs:**
   - Select `cache` folder
   - Right-click → "Change Permissions"
   - Set to **777** (all checkboxes checked)
   - Repeat for `logs` folder

#### Via Terminal:

```bash
cd ~/public_html/drserita-monitoring

# Set standard permissions
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;

# Make cache and logs writable
chmod -R 777 cache logs

# Verify
ls -la cache logs
# Should show: drwxrwxrwx
```

---

## Configuration

### Step 4: Create Configuration File

#### Via File Manager:

1. **Navigate to** `drserita-monitoring`

2. **Find** `config.sample.php`

3. **Copy it:**
   - Right-click `config.sample.php`
   - Select "Copy"
   - Enter new name: `config.php`
   - Click "Copy File(s)"

4. **Edit the new file:**
   - Right-click `config.php`
   - Select "Edit"

#### Via Terminal:

```bash
cd ~/public_html/drserita-monitoring
cp config.sample.php config.php
nano config.php
```

---

### Step 5: Generate Your Password Hash

**Security Note:** Never use plain-text passwords! We need to generate a secure hash.

#### If you have Terminal access:

```bash
# Generate password hash
php -r "echo password_hash('your-secure-password', PASSWORD_DEFAULT);"
```

**Example output:**
```
$2y$12$kbNoAISCGF3Gpkpz1rvZ.emW3jT2AC3Vac19EZg9FUxS1uMW3U9ry
```

#### If you don't have Terminal access:

1. **On your local computer** with PHP installed:

```bash
php -r "echo password_hash('your-secure-password', PASSWORD_DEFAULT);"
```

2. **Or use an online tool** (not recommended for production):
   - Search: "php password_hash generator online"
   - Use your password to generate the hash

---

### Step 6: Edit Configuration File

Edit `config.php` and update these values:

```php
<?php
// This file is OUTSIDE the public web root, so it's safe.

// Base directory (current directory - where config.php is located)
define('BASE_DIR', __DIR__);

// ============================================
// IMPORTANT: Update these paths for cPanel!
// ============================================

// Database path - SQLite database file location
define('DB_PATH', BASE_DIR . '/cache/logs.db');

// Log directory - cPanel stores access logs differently
// Common locations (uncomment the one that matches your host):
define('LOG_DIR', '/home/username/access-logs/');
// Or: define('LOG_DIR', '/home/username/public_html/logs/');

// ============================================
// APPLICATION SETTINGS
// ============================================

// Application title (shows in the UI)
define('APP_TITLE', 'Your Monitor Name');

// ============================================
// SECURITY - PASSWORD HASH
// ============================================

// ⚠️ PASTE YOUR GENERATED HASH HERE ⚠️
define('MONITOR_PASSWORD_HASH', 'YOUR_GENERATED_HASH_HERE');

// ============================================
// PATHS (Usually no need to change these)
// ============================================

define('CRON_PATH', BASE_DIR . '/cron/parse_logs.php');
define('PARSER_LOG_PATH', BASE_DIR . '/logs/parser.log');
```

**Important:** Replace `/home/username/` with your actual home directory path!

**Save the file** in File Manager or press `Ctrl+X` then `Y` in Terminal.

---

### Step 7: Secure Configuration File

Set proper permissions to protect your password:

#### Via File Manager:

1. Right-click `config.php`
2. Select "Change Permissions"
3. Set to **640** (rw-r-----)
   - Owner: Read + Write
   - Group: Read
   - World: None

#### Via Terminal:

```bash
chmod 640 config.php

# Verify
ls -la config.php
# Should show: -rw-r----- 1 username username
```

---

## Database Setup

### Step 8: Create Database

#### Via Terminal:

```bash
cd ~/public_html/drserita-monitoring

# Run database setup
php parsers/setup_db.php

# Set proper permissions
chmod 664 cache/logs.db

# Verify database was created
ls -la cache/logs.db
```

**Expected output:**
```
✓ Database created successfully at: cache/logs.db
✓ Table 'access_logs' created
```

#### If you don't have Terminal:

You may need to request this from your hosting support, or:

1. **Create a PHP script** temporarily:

```php
<?php
// Create in: ~/public_html/drserita-monitoring/setup.php
require_once 'config.php';

try {
    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $db->exec("CREATE TABLE IF NOT EXISTS access_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ip TEXT,
        date_time TEXT,
        method TEXT,
        url TEXT,
        status_code INTEGER,
        response_time_ms REAL,
        user_agent TEXT
    )");

    echo "✓ Database created successfully!";
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage();
}
?>
```

2. **Access via browser:** `https://yourdomain.com/drserita-monitoring/setup.php`

3. **Delete the file after success!**

---

### Step 9: Create Database Indexes

**Why indexes matter:** They make searches much faster.

#### Via Terminal:

```bash
cd ~/public_html/drserita-monitoring

# Create indexes for common search fields
sqlite3 cache/logs.db "CREATE INDEX IF NOT EXISTS idx_url ON access_logs(url);"
sqlite3 cache/logs.db "CREATE INDEX IF NOT EXISTS idx_user_agent ON access_logs(user_agent);"
sqlite3 cache/logs.db "CREATE INDEX IF NOT EXISTS idx_status_code ON access_logs(status_code);"
sqlite3 cache/logs.db "CREATE INDEX IF NOT EXISTS idx_date_time ON access_logs(date_time);"

# Verify indexes
sqlite3 cache/logs.db ".indexes access_logs"
```

#### If you don't have sqlite3 command:

Create a temporary PHP script:

```php
<?php
// Create in: ~/public_html/drserita-monitoring/create_indexes.php
require_once 'config.php';

try {
    $db = new PDO('sqlite:' . DB_PATH);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_url ON access_logs(url)",
        "CREATE INDEX IF NOT EXISTS idx_user_agent ON access_logs(user_agent)",
        "CREATE INDEX IF NOT EXISTS idx_status_code ON access_logs(status_code)",
        "CREATE INDEX IF NOT EXISTS idx_date_time ON access_logs(date_time)"
    ];

    foreach ($indexes as $index) {
        $db->exec($index);
    }

    echo "✓ Indexes created successfully!";
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage();
}
?>
```

Access via browser and delete after success.

---

## Web Access Configuration

### Step 10: Configure Web Access

On cPanel, you have limited Apache configuration. Choose one of these methods:

---

#### Option A: Subdirectory Method (Easiest - Recommended)

**Use this if:** You want the simplest setup without requesting Apache changes.

**How it works:** Place the public folder contents in a subdirectory.

**Steps:**

1. **Create the directory structure:**

```bash
# Via Terminal
mkdir -p ~/public_html/monitor/logs

# Copy public folder contents
cp -r ~/public_html/drserita-monitoring/public/* ~/public_html/monitor/logs/

# Copy config files
cp ~/public_html/drserita-monitoring/config.php ~/public_html/monitor/
cp -r ~/public_html/drserita-monitoring/cache ~/public_html/monitor/
cp -r ~/public_html/drserita-monitoring/cron ~/public_html/monitor/
cp -r ~/public_html/drserita-monitoring/logs ~/public_html/monitor/
```

2. **Update paths in config.php:**

```php
// In ~/public_html/monitor/config.php
define('BASE_DIR', '/home/username/public_html/monitor');
define('DB_PATH', BASE_DIR . '/cache/logs.db');
define('LOG_DIR', '/home/username/access-logs/');
```

3. **Access via:** `https://yourdomain.com/monitor/logs/`

---

#### Option B: Request Apache Alias from Hosting

**Use this if:** You want to keep files outside public_html for security.

**How it works:** Ask your hosting provider to add an Apache Alias.

**What to request:**

```
Subject: Request for Apache Alias for Log Monitoring Tool

Hello Support,

I would like to set up a log monitoring tool on my account.
Could you please add the following Apache Alias configuration?

Alias "/monitor/logs" "/home/username/public_html/drserita-monitoring/public"

<Directory "/home/username/public_html/drserita-monitoring/public">
    Options -Indexes +FollowSymLinks
    AllowOverride All
    Require all granted
    DirectoryIndex index.php
</Directory>

Please replace /home/username/ with my actual home directory.

Thank you!
```

**Access via:** `https://yourdomain.com/monitor/logs/`

---

#### Option C: .htaccess Rewrite Method

**Use this if:** Neither of the above options work.

**Steps:**

1. **Create .htaccess** in `~/public_html/drserita-monitoring/public/`:

```apache
RewriteEngine On
RewriteBase /drserita-monitoring/public/

# Redirect all requests to index.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Security - prevent viewing config files
<FilesMatch "^(config\.php)$">
    Require all denied
</FilesMatch>
```

2. **Access via:** `https://yourdomain.com/drserita-monitoring/public/`

---

### Step 11: Set .htaccess Security

Ensure your `.htaccess` file (in `public/`) contains security rules:

```apache
# Prevent directory browsing
Options -Indexes

# Block direct access to config files
<FilesMatch "^(config\.php|\.htaccess)$">
    Require all denied
</FilesMatch>

# Allow only specific PHP files
<FilesMatch "^(index|api|truncate_logs)\.php$">
    Require all granted
</FilesMatch>

# Deny access to views directory
RedirectMatch 404 ^/views/.*$
```

---

## Finding Your Log Files

### Step 12: Locate Apache Access Logs

cPanel stores logs differently depending on the host. Common locations:

#### Common Paths (try each):

```bash
# Check if these exist
ls ~/access-logs/
ls ~/public_html/logs/
ls /var/log/apache2/  # (unlikely in cPanel)
```

#### How to find your actual log location:

1. **Ask your hosting support:**
   - "Where are my Apache access logs located?"

2. **Check cPanel documentation:**
   - Some hosts have this in their knowledge base

3. **Look in common locations:**

```bash
# Via Terminal
find ~ -name "*access*log" 2>/dev/null
find ~/public_html -name "*access*log" 2>/dev/null
```

#### Update config.php with correct path:

```php
// After finding your logs, update config.php
define('LOG_DIR', '/home/username/access-logs/');
// Or wherever your logs are located
```

---

### Step 13: Identify Your Log File Name

cPanel often uses domain-based naming:

```bash
# List access logs
ls ~/access-logs/

# Common patterns:
# - yourdomain.com
# - yourdomain.com-ssl_log
# - example.com
```

**Update the parser** if needed (`cron/parse_logs.php`):

```php
// If your logs have specific naming, update the glob pattern:
$files = glob($logDir . 'yourdomain.com*');
```

---

## Cron Job Setup

### Step 14: Configure Automatic Log Parsing

#### Via cPanel Cron Jobs:

1. **Log in to cPanel**

2. **Navigate to** "Cron Jobs"

3. **Select frequency:**
   - "Once per hour" (recommended)
   - Or "Once per minute" for testing

4. **Add cron command:**

```bash
/usr/bin/php /home/username/public_html/drserita-monitoring/cron/parse_logs.php >> /home/username/public_html/drserita-monitoring/logs/parser.log 2>&1
```

**Important:** Replace `/home/username/` with your actual path!

5. **Click "Add New Cron Job"**

#### Via Terminal:

```bash
# Edit crontab
crontab -e

# Add this line:
0 */1 * * * /usr/bin/php ~/public_html/drserita-monitoring/cron/parse_logs.php >> ~/public_html/drserita-monitoring/logs/parser.log 2>&1

# Save and exit
```

---

### Step 15: Find Your PHP Path

Different hosts use different PHP paths. To find yours:

#### Via Terminal:

```bash
which php
# Output examples:
# /usr/bin/php
# /usr/local/bin/php
# /opt/cpanel/ea-php81/root/usr/bin/php
```

#### Via cPanel:

1. Look for "MultiPHP Manager"
2. Or check "Select PHP Version"
3. The PHP path is usually displayed

**Update your cron command with the correct PHP path!**

---

## Verification

### Step 16: Test Your Installation

1. **Check file permissions:**

```bash
# Via Terminal
ls -la ~/public_html/drserita-monitoring/

# Should show:
# drwxrwxrwx cache
# drwxrwxrwx logs
# -rw-r----- config.php
```

2. **Test database exists:**

```bash
ls -la ~/public_html/drserita-monitoring/cache/logs.db

# Should show file size > 0
```

3. **Test parser manually:**

```bash
cd ~/public_html/drserita-monitoring
php cron/parse_logs.php

# Check output
cat logs/parser.log
```

4. **Access in browser:**

   - **Option A (Subdirectory):** `https://yourdomain.com/monitor/logs/`
   - **Option B (Alias):** `https://yourdomain.com/monitor/logs/`
   - **Option C (Direct):** `https://yourdomain.com/drserita-monitoring/public/`

5. **Login with** your chosen password

6. **You should see:**
   - Dashboard with charts
   - Recent logs table
   - Filter options

---

### Step 17: Verify Data Flow

```bash
# Check database has data
sqlite3 ~/public_html/drserita-monitoring/cache/logs.db "SELECT COUNT(*) FROM access_logs;"

# Should return a number > 0

# Check recent entries
sqlite3 ~/public_html/drserita-monitoring/cache/logs.db "SELECT * FROM access_logs ORDER BY id DESC LIMIT 5;"
```

---

## Troubleshooting

### Common cPanel Issues and Solutions

---

#### Issue: "Permission denied" errors

**Error:** `Warning: require_once(...config.php): Failed to open stream: Permission denied`

**Solutions:**

1. **Check file permissions:**

```bash
ls -la config.php
# Should be: -rw-r----- (644 may also work in cPanel)
```

2. **Fix via File Manager:**
   - Right-click `config.php`
   - Change Permissions
   - Set to **640** or **644**

3. **Fix via Terminal:**

```bash
chmod 644 config.php
```

---

#### Issue: "Unable to open database file"

**Error:** `SQLSTATE[HY000] [14] unable to open database file`

**Solutions:**

```bash
# Fix cache directory permissions
chmod 777 ~/public_html/drserita-monitoring/cache

# Fix database file permissions
chmod 664 ~/public_html/drserita-monitoring/cache/logs.db

# Verify
ls -la ~/public_html/drserita-monitoring/cache/
```

---

#### Issue: SQLite3 extension not enabled

**Error:** `Class 'SQLite3' not found` or similar

**Solutions:**

1. **Enable via cPanel:**
   - Go to "Select PHP Version" or "MultiPHP Manager"
   - Find your domain
   - Check/enable `sqlite3` extension
   - Save changes

2. **Request from hosting:**
   - Ask support to enable SQLite3 for your account

3. **Check if enabled:**

```bash
php -m | grep sqlite
```

---

#### Issue: No data showing in dashboard

**Diagnosis:**

```bash
# 1. Check database exists
ls -la ~/public_html/drserita-monitoring/cache/logs.db

# 2. Check database has data
sqlite3 ~/public_html/drserita-monitoring/cache/logs.db "SELECT COUNT(*) FROM access_logs;"

# 3. Check parser log
cat ~/public_html/drserita-monitoring/logs/parser.log

# 4. Check if Apache logs exist
ls ~/access-logs/
```

**Solutions:**

1. **If log file path is wrong:**
   - Find actual log location
   - Update `LOG_DIR` in config.php

2. **If parser isn't running:**
   - Test manually: `php cron/parse_logs.php`
   - Check cron job is configured correctly
   - Verify PHP path is correct

3. **If log files have different naming:**
   - Check actual log file names
   - Update `cron/parse_logs.php` glob pattern

---

#### Issue: Cron job not working

**Diagnosis:**

```bash
# Check cron is set up
crontab -l

# Test cron command manually
/usr/bin/php ~/public_html/drserita-monitoring/cron/parse_logs.php
```

**Solutions:**

1. **Verify PHP path:**

```bash
which php
# Use the output in your cron command
```

2. **Verify file paths are absolute:**
   - Use full paths: `/home/username/...`
   - Don't use `~/` in cron

3. **Check file permissions:**

```bash
ls -la ~/public_html/drserita-monitoring/cron/parse_logs.php
# Should be readable (644 or 755)
```

4. **Check cron email:**
   - In cPanel Cron Jobs, an email is sent with output
   - Look for error messages

---

#### Issue: Can't find Apache logs

**Solution:**

1. **Ask hosting support** for log location

2. **Try common locations:**

```bash
ls ~/access-logs/
ls ~/public_html/logs/
find ~ -name "*access*log" 2>/dev/null
```

3. **Update config.php** with correct path

---

#### Issue: 403 Forbidden errors

**Error:** `403 Forbidden` or `You don't have permission to access`

**Solutions:**

1. **Check .htaccess exists:**

```bash
ls -la ~/public_html/drserita-monitoring/public/.htaccess
```

2. **Check file permissions:**

```bash
chmod 755 ~/public_html/drserita-monitoring/public
chmod 644 ~/public_html/drserita-monitoring/public/*.php
```

3. **Check .htaccess content:**
   - Ensure no `Require all denied` directives

---

#### Issue: Login not working

**Solutions:**

1. **Regenerate password hash:**

```bash
php -r "echo password_hash('your-password', PASSWORD_DEFAULT);"
```

2. **Update config.php** with new hash

3. **Clear browser cache** and cookies

4. **Check config.php is being loaded:**

```bash
php -r "require_once 'config.php'; echo APP_TITLE;"
```

---

## cPanel-Specific Tips

### Finding Your Paths

**Home Directory:**
```bash
echo $HOME
# Output: /home/username
```

**Public Directory:**
```bash
echo $HOME/public_html
# Output: /home/username/public_html
```

**PHP Binary:**
```bash
which php
# Output: /usr/bin/php (or similar)
```

**Access Logs:**
```bash
ls ~/access-logs/
# Output: list of log files
```

---

### File Permissions Reference

| Purpose | Permission | Command |
|---------|-----------|---------|
| Directories | 755 | `chmod 755 folder` |
| PHP Files | 644 | `chmod 644 file.php` |
| Config File | 640 | `chmod 640 config.php` |
| Cache/Logs | 777 | `chmod 777 cache` |
| Database | 664 | `chmod 664 logs.db` |

---

### Useful cPanel Tools

1. **MultiPHP Manager** - Change PHP version per domain
2. **Select PHP Version** - Enable/disable PHP extensions
3. **File Manager** - Upload, edit, manage files
4. **Terminal** - Command-line access (if enabled)
5. **Cron Jobs** - Schedule tasks
6. **phpMyAdmin** - Not needed for SQLite (uses files)
7. **Backup** - Backup your files and database

---

### Quick Reference for cPanel

**Essential Paths:**

| Item | Path |
|------|------|
| Home | `/home/username/` |
| Public HTML | `/home/username/public_html/` |
| Access Logs | `/home/username/access-logs/` |
| Your App | `/home/username/public_html/drserita-monitoring/` |

**Essential URLs:**

| Access | URL |
|--------|-----|
| cPanel | `https://yourdomain.com:2083` |
| Webmail | `https://yourdomain.com:2096` |
| Your App | `https://yourdomain.com/monitor/logs/` |

**Essential Commands:**

```bash
# Navigate to app
cd ~/public_html/drserita-monitoring

# Run parser
php cron/parse_logs.php

# Check parser log
cat logs/parser.log

# Query database
sqlite3 cache/logs.db "SELECT COUNT(*) FROM access_logs;"

# Edit config
nano config.php

# Edit cron
crontab -e
```

---

## When to Contact Hosting Support

Contact your hosting provider if you need help with:

1. ✋ **Finding your Apache log location**
2. ✋ **Enabling SQLite3 extension**
3. ✋ **Adding Apache Alias configuration**
4. ✋ **Resolving persistent permission errors**
5. ✋ **Finding correct PHP path for cron**
6. ✋ **Troubleshooting server-level issues**

**What to include in support request:**

- Your cPanel username
- What you're trying to accomplish
- What you've already tried
- Any error messages you're seeing

---

## Need More Help?

1. Check the [VPS Setup Guide](SETUP_VPS.md) for additional technical details
2. Review the troubleshooting section above
3. Check your hosting provider's documentation
4. Contact your hosting support for cPanel-specific issues

---

## Changelog

- **2025-06-15:** Added URL decoding for search, fixed UTF-8/Persian support, enhanced cPanel documentation
- **2025-06-14:** Initial release with timezone support, response time tracking

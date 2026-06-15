# DrSerita Monitor - cPanel Setup Guide

Setup guide for shared hosting with cPanel (limited access).

## Prerequisites

- cPanel hosting account
- PHP 8.1 or higher
- SQLite3 PHP extension enabled
- Access to cPanel File Manager
- Access to cPanel Cron Jobs
- Apache mod_rewrite enabled (usually available)

## Step 1: Upload Files

### Via File Manager:

1. Log in to cPanel
2. Go to **File Manager** → **Home Directory**
3. Create a folder: `drserita-monitoring`
4. Upload all files to this folder
5. Extract if uploading a ZIP archive

### Via FTP:

```bash
# Upload files to public subfolder
/public_html/drserita-monitoring/
```

## Step 2: Set Folder Permissions

In File Manager:

1. Navigate to `drserita-monitoring`
2. Select all files and folders
3. Click **Change Permissions**
4. Set: **755** for folders, **644** for files
5. For `cache` and `logs` folders: **777** (full write access)

## Step 3: Create Configuration

1. In File Manager, go to `drserita-monitoring`
2. Copy `config.sample.php` → `config.php`
3. Right-click `config.php` → **Edit**
4. Edit these values:

```php
// Set your application title
define('APP_TITLE', 'DrSerita');

// Generate password hash using:
// In cPanel Terminal or your local PHP:
// php -r "echo password_hash('your-password', PASSWORD_DEFAULT);"
define('MONITOR_PASSWORD_HASH', 'YOUR_GENERATED_HASH_HERE');
```

5. Save changes

## Step 4: Create Database

1. In cPanel, go to **Terminal** (if available) or use **PHP Console**
2. Run:

```bash
cd ~/public_html/drserita-monitoring
php parsers/setup_db.php
```

3. Verify database created:
```bash
ls -la cache/logs.db
```

## Step 5: Create Indexes

In cPanel Terminal:

```bash
sqlite3 ~/public_html/drserita-monitoring/cache/logs.db "CREATE INDEX IF NOT EXISTS idx_url ON access_logs(url);"
sqlite3 ~/public_html/drserita-monitoring/cache/logs.db "CREATE INDEX IF NOT EXISTS idx_user_agent ON access_logs(user_agent);"
sqlite3 ~/public_html/drserita-monitoring/cache/logs.db "CREATE INDEX IF NOT EXISTS idx_status_code ON access_logs(status_code);"
sqlite3 ~/public_html/drserita-monitoring/cache/logs.db "CREATE INDEX IF NOT EXISTS idx_date_time ON access_logs(date_time);"
```

## Step 6: Configure Apache Alias

Note: On cPanel, you might need to ask your hosting provider to add an Apache Alias, OR use the subdirectory method:

### Option A: Subdirectory Method (No Apache Config Needed)

Place files in: `/public_html/monitor/logs/`

Access via: `https://yourdomain.com/monitor/logs/`

### Option B: Request Apache Alias

Contact hosting support to add:

```apache
Alias "/monitor/logs" "/home/username/public_html/drserita-monitoring/public"
```

## Step 7: Setup Cron Job

1. In cPanel, go to **Cron Jobs**
2. Select **Once per hour** (or custom)
3. Add this command:

```bash
/usr/bin/php /home/username/public_html/drserita-monitoring/cron/parse_logs.php >> /home/username/public_html/drserita-monitoring/logs/parser.log 2>&1
```

**Note:** Replace `/home/username/` with your actual home path (check in cPanel sidebar).

## Step 8: Access Log Location for cPanel

cPanel stores Apache logs at:

```bash
# Access logs
/home/username/access-logs/domain.com

# Error logs  
/home/username/access-logs/domain.com
```

**Update config.php:**

```php
define('LOG_DIR', '/home/username/access-logs/');
```

Then update `cron/parse_logs.php` to use your domain log file:

```php
$files = glob($logDir . 'domain.com*');
```

## Step 9: Verify Installation

1. Visit: `https://yourdomain.com/monitor/logs/` (or your configured path)
2. Login with your password
3. You should see the dashboard

## cPanel-Specific Tips

**Finding PHP Path:**

```bash
# In cPanel Terminal
which php
# Usually: /usr/bin/php or /usr/local/bin/php
```

**Finding Home Path:**

```bash
# In cPanel Terminal
echo $HOME
# Usually: /home/username
```

**Enable SQLite3:**

1. Go to **Select PHP Version** (MultiPHP Manager)
2. Select your domain
3. Check **sqlite3** extension
4. Save

**File Permissions via SSH:**

If you have SSH access:

```bash
cd ~/public_html/drserita-monitoring
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod -R 777 cache logs
```

## Troubleshooting

**403/404 errors:**
- Check .htaccess exists in `public/`
- Verify Apache mod_rewrite is enabled
- Check file permissions (755 for folders, 644 for files)

**No data displaying:**
- Verify database exists: `ls -la cache/logs.db`
- Check parser log: `cat logs/parser.log`
- Verify log file path is correct for your hosting

**Login not working:**
- Regenerate password hash
- Verify config.php is being loaded
- Check PHP sessions are enabled

**Cron not running:**
- Verify PHP path: `which php`
- Check file paths are absolute
- Check cron email for errors

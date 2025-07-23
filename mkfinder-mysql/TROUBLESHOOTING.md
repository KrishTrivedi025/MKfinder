# MKfinder XAMPP Troubleshooting Guide

## "Not Found" Error Solutions

### Issue: Apache/2.4.58 (Win64) "Not Found" Error

This error means Apache is running but can't find your files. Here are the solutions:

### Solution 1: Check File Location
**Most Common Issue**

Ensure files are in the correct XAMPP directory:
```
Correct Path: C:\xampp\htdocs\mkfinder\
Wrong Path: C:\xampp\htdocs\mkfinder-mysql\
```

**Action:**
1. Extract/move ALL files to `C:\xampp\htdocs\mkfinder\`
2. Visit: `http://localhost/mkfinder/`
3. NOT: `http://localhost/mkfinder-mysql/`

### Solution 2: XAMPP htdocs Permission
**Windows Permission Issue**

1. Right-click `C:\xampp\htdocs` folder
2. Properties → Security → Edit
3. Add "Full Control" for your user account
4. Apply to subfolders

### Solution 3: Check XAMPP Services
**Service Status Check**

Open XAMPP Control Panel:
- Apache: Must show GREEN "Running"
- MySQL: Must show GREEN "Running"

If not running:
1. Click "Start" for each service
2. If port conflicts occur, see Port Solutions below

### Solution 4: Alternative URLs to Try

Try these URLs in order:
1. `http://localhost/mkfinder/`
2. `http://localhost:80/mkfinder/`
3. `http://127.0.0.1/mkfinder/`
4. `http://localhost/mkfinder/index.html`

### Solution 5: Port Conflicts

**If Apache won't start (Port 80 busy):**

1. Open XAMPP Control Panel
2. Apache → Config → httpd.conf
3. Find: `Listen 80`
4. Change to: `Listen 8080`
5. Save and restart Apache
6. Access via: `http://localhost:8080/mkfinder/`

**If MySQL won't start (Port 3306 busy):**

1. MySQL → Config → my.ini
2. Find: `port = 3306`
3. Change to: `port = 3307`
4. Update `mkfinder/config.php`:
   ```php
   define('DB_HOST', 'localhost:3307');
   ```

## Step-by-Step Verification

### 1. File Structure Check
Your `C:\xampp\htdocs\mkfinder\` should contain:
```
mkfinder/
├── index.html          ← Main page
├── config.php          ← Database config
├── database.php        ← Database operations
├── mkfinder.sql        ← Database schema
├── README.md           ← Instructions
├── images/             ← Bird illustrations
└── uploads/            ← Upload directory
```

### 2. Test Basic Apache
Visit: `http://localhost/`
- Should show XAMPP welcome page
- If not, Apache isn't working properly

### 3. Test Directory Access
Visit: `http://localhost/mkfinder/`
- Should show MKfinder application
- If "403 Forbidden": Permission issue
- If "404 Not Found": Path issue

### 4. Test Database
Visit: `http://localhost/mkfinder/test-db.php`
- Should return JSON with success
- If error: Database setup issue

## Windows-Specific Fixes

### Antivirus Blocking
1. Add XAMPP folder to antivirus exclusions:
   - `C:\xampp\`
2. Temporarily disable Windows Defender
3. Restart XAMPP services

### Windows Firewall
1. Windows Security → Firewall
2. Allow Apache through firewall
3. Allow MySQL through firewall

### User Account Control (UAC)
1. Right-click XAMPP Control Panel
2. "Run as Administrator"
3. Start services with admin rights

## Quick Fix Commands

**Reset XAMPP (Run as Administrator):**
```cmd
cd C:\xampp
apache\bin\httpd.exe -k stop
mysql\bin\mysqld --shutdown
apache\bin\httpd.exe -k start
mysql\bin\mysqld --console
```

**Check if ports are free:**
```cmd
netstat -ano | findstr :80
netstat -ano | findstr :3306
```

## Alternative: Portable Test

If XAMPP issues persist, you can run a quick PHP test:

1. Open Command Prompt as Administrator
2. Navigate to your mkfinder folder:
   ```cmd
   cd C:\xampp\htdocs\mkfinder
   ```
3. Run PHP built-in server:
   ```cmd
   C:\xampp\php\php.exe -S localhost:8000
   ```
4. Visit: `http://localhost:8000`

## Database Setup After File Fix

Once files are accessible:

1. **Create Database:**
   - Visit: `http://localhost/phpmyadmin`
   - Create database: `mkfinder`

2. **Import Schema:**
   - Select `mkfinder` database
   - Import → Choose `mkfinder.sql`
   - Click "Go"

3. **Test Connection:**
   - Visit: `http://localhost/mkfinder/test-db.php`
   - Should return success JSON

## Success Indicators

✅ **Working correctly when:**
- `http://localhost/mkfinder/` shows MKfinder homepage
- Can upload images without errors
- Gallery page displays properly
- Species pages load with bird information
- Database test returns success

## Still Having Issues?

1. Check `C:\xampp\apache\logs\error.log`
2. Check `mkfinder/error.log` (if exists)
3. Verify PHP version: `http://localhost/mkfinder/test-db.php`
4. Try different browser or incognito mode
5. Restart computer and try again

The most common fix is ensuring files are in `C:\xampp\htdocs\mkfinder\` and accessing via `http://localhost/mkfinder/`
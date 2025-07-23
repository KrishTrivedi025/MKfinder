# XAMPP Setup Guide for MKfinder

## Step-by-Step Installation

### 1. Prepare XAMPP Environment

**Start XAMPP Services:**
- Open XAMPP Control Panel
- Start **Apache** service
- Start **MySQL** service
- ✅ Both should show green "Running" status

### 2. Extract Project Files

**Windows:**
```
Extract mkfinder-mysql.zip to:
C:\xampp\htdocs\mkfinder\
```

**Mac/Linux:**
```
Extract mkfinder-mysql.zip to:
/Applications/XAMPP/htdocs/mkfinder/
```

### 3. Create MySQL Database

**Option A: Using phpMyAdmin (Recommended)**
1. Open browser: `http://localhost/phpmyadmin`
2. Click **"New"** in left sidebar
3. Database name: `mkfinder`
4. Collation: `utf8mb4_unicode_ci`
5. Click **"Create"**

**Option B: Using MySQL Command Line**
```sql
mysql -u root -p
CREATE DATABASE mkfinder CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit
```

### 4. Import Database Schema

**Using phpMyAdmin:**
1. Select `mkfinder` database from left sidebar
2. Click **"Import"** tab at top
3. Click **"Choose File"** button
4. Select `mkfinder.sql` from project folder
5. Click **"Go"** at bottom
6. ✅ Should see "Import has been successfully finished"

**Using Command Line:**
```bash
cd C:\xampp\htdocs\mkfinder
mysql -u root -p mkfinder < mkfinder.sql
```

### 5. Set Folder Permissions

**Windows:**
1. Right-click `uploads` folder
2. Properties → Security → Edit
3. Add "Full Control" for Users
4. Apply changes

**Mac/Linux:**
```bash
chmod 755 uploads
# If upload issues persist:
chmod 777 uploads
```

### 6. Test Installation

**Database Test:**
- Visit: `http://localhost/mkfinder/test-db.php`
- Should return: `{"success": true, ...}`

**Main Application:**
- Visit: `http://localhost/mkfinder`
- Should see MKfinder homepage with upload interface

## Verification Checklist

- [ ] XAMPP Apache service running
- [ ] XAMPP MySQL service running  
- [ ] Database `mkfinder` exists in phpMyAdmin
- [ ] Tables visible: `species`, `uploads`, `identifications`
- [ ] Species data imported (3 bird species)
- [ ] `uploads` folder writable
- [ ] `test-db.php` returns success
- [ ] Main page loads without errors
- [ ] Can upload and identify test image

## Common XAMPP Issues

### Apache Won't Start
**Port 80 conflict:**
1. XAMPP Config → Apache → Config → httpd.conf
2. Change `Listen 80` to `Listen 8080`
3. Access via: `http://localhost:8080/mkfinder`

### MySQL Won't Start
**Port 3306 conflict:**
1. XAMPP Config → MySQL → Config → my.ini
2. Change `port = 3306` to `port = 3307`
3. Update config.php: `define('DB_HOST', 'localhost:3307');`

### Permission Denied Errors
**Windows:**
- Run XAMPP as Administrator
- Check Windows Defender/Antivirus

**Mac/Linux:**
```bash
sudo chown -R daemon:daemon /Applications/XAMPP/htdocs/mkfinder
```

## Database Backup & Restore

**Create Backup:**
1. phpMyAdmin → Select `mkfinder` → Export
2. Choose "Quick" method → Go
3. Save .sql file

**Restore Backup:**
1. Drop database: `DROP DATABASE mkfinder;`
2. Create new: `CREATE DATABASE mkfinder;`
3. Import your backup .sql file

## Performance Tips

**Optimize MySQL:**
```ini
# Add to my.ini [mysqld] section
innodb_buffer_pool_size = 128M
query_cache_size = 32M
query_cache_limit = 2M
```

**Optimize PHP:**
```ini
# Add to php.ini
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 120
memory_limit = 256M
```

## Troubleshooting URLs

- **XAMPP Dashboard**: `http://localhost/dashboard`
- **phpMyAdmin**: `http://localhost/phpmyadmin`
- **MKfinder App**: `http://localhost/mkfinder`
- **Database Test**: `http://localhost/mkfinder/test-db.php`
- **Error Logs**: Check `mkfinder/error.log`

---

**Ready to identify birds! 🐦** Upload your first image and start exploring the species database.
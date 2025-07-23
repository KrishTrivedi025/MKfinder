# MKfinder - Bird Species Identification System
## MySQL/XAMPP Version

A complete web-based bird identification application optimized for XAMPP deployment with MySQL database.

## 🚀 Quick XAMPP Setup

### Prerequisites
- XAMPP installed with Apache and MySQL running
- Web browser
- Basic knowledge of phpMyAdmin

### Installation Steps

1. **Extract Files**
   - Extract all files to your XAMPP `htdocs` directory
   - Path should be: `C:\xampp\htdocs\mkfinder\` (Windows) or `/opt/lampp/htdocs/mkfinder/` (Linux)

2. **Create Database**
   - Open phpMyAdmin in your browser: `http://localhost/phpmyadmin`
   - Click "New" to create a database
   - Name it `mkfinder`
   - Set collation to `utf8mb4_unicode_ci`
   - Click "Create"

3. **Import Database Schema**
   - Select the `mkfinder` database
   - Click "Import" tab
   - Choose file: `mkfinder.sql`
   - Click "Go" to import
   - ✅ Tables and initial data will be created automatically

4. **Set Permissions**
   - Ensure the `uploads` folder is writable by the web server
   - Windows: Right-click uploads folder → Properties → Security → Edit → Add write permissions
   - Linux: `chmod 755 uploads` or `chmod 777 uploads` if needed

5. **Access Application**
   - Open browser and go to: `http://localhost/mkfinder`
   - ✅ Your bird identification system is ready!

## 📁 File Structure

```
mkfinder/
├── index.html              # Main application page
├── styles.css              # Responsive design & styling
├── script.js               # Frontend JavaScript
├── config.php              # MySQL configuration
├── database.php            # Database operations class
├── upload.php              # File upload handler
├── identify.php            # Bird identification logic
├── species.php             # Species information display
├── gallery.php             # Image gallery & statistics
├── test-db.php             # Database connection test
├── mkfinder.sql            # Database schema & data
├── README.md               # This file
├── images/                 # Custom bird SVG illustrations
│   ├── american-robin.svg
│   ├── blue-jay.svg
│   └── northern-cardinal.svg
└── uploads/                # User uploaded images
    └── .htaccess           # Upload security
```

## 🔧 Configuration

### Database Settings
Edit `config.php` if your MySQL settings differ:

```php
define('DB_HOST', 'localhost');     // Usually localhost for XAMPP
define('DB_NAME', 'mkfinder');      // Database name
define('DB_USER', 'root');          // XAMPP default user
define('DB_PASS', '');              // XAMPP default password (empty)
```

### Supported Species
- **American Robin** (*Turdus migratorius*)
- **Blue Jay** (*Cyanocitta cristata*)
- **Northern Cardinal** (*Cardinalis cardinalis*)

## 🧪 Testing

1. **Database Connection Test**
   - Visit: `http://localhost/mkfinder/test-db.php`
   - Should return JSON with `"success": true`

2. **Upload Test**
   - Go to main page: `http://localhost/mkfinder`
   - Upload any bird image
   - Click "Identify Bird"
   - Check gallery for uploaded image

## 📊 Features

- **✅ Image Upload**: Drag-and-drop interface
- **✅ Bird Identification**: AI-powered species recognition
- **✅ Species Database**: Comprehensive bird information
- **✅ Photo Gallery**: View identification history
- **✅ Statistics**: Track accuracy and frequency
- **✅ Responsive Design**: Works on all devices
- **✅ MySQL Integration**: Robust database backend

## 🛠️ Technical Details

- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5.3.0
- **Backend**: PHP 7.4+ with PDO MySQL
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **Server**: Apache (included with XAMPP)
- **File Uploads**: Secure validation and storage

## 📈 Database Schema

### Tables
- **species**: Bird species information with JSON characteristics
- **uploads**: File metadata and upload tracking
- **identifications**: Identification results with confidence scores

### Relationships
- Foreign key constraints ensure data integrity
- Cascade updates for species name changes
- Automatic timestamps for audit trails

## 🔒 Security Features

- Input sanitization and validation
- File type and size restrictions
- SQL injection protection with PDO prepared statements
- Upload directory protection with .htaccess
- Error logging without exposing sensitive data

## 🐛 Troubleshooting

### Common Issues

1. **"Database connection failed"**
   - Check XAMPP MySQL is running
   - Verify database name in phpMyAdmin
   - Check config.php settings

2. **"Upload failed"**
   - Ensure uploads folder exists and is writable
   - Check file size (max 10MB)
   - Verify file type (JPG, PNG, GIF only)

3. **"Species not found"**
   - Run `mkfinder.sql` import again
   - Check if species table has data in phpMyAdmin

4. **Images not displaying**
   - Check file permissions
   - Verify uploads folder path
   - Check browser console for errors

### Debug Mode
Add to config.php for debugging:
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

## 📝 License

Custom project for educational and demonstration purposes.

## 🤝 Support

For issues:
1. Check error.log file in project directory
2. Verify XAMPP services are running
3. Test database connection with test-db.php
4. Check phpMyAdmin for data integrity

---

**Enjoy identifying birds with MKfinder! 🐦**
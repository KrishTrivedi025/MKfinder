# MKfinder - Quick Start Guide

## Fix for Username/Password Issue

The authentication prompt you encountered is caused by Apache configuration. This simplified version removes all authentication requirements.

## Installation Steps

1. **Extract Files**
   - Extract to: `C:\xampp\htdocs\mkfinder\`
   - Ensure `.htaccess` file is included

2. **Access Application**
   - Visit: `http://localhost/mkfinder/`
   - OR: `http://localhost:8080/mkfinder/` (if using port 8080)
   - Should load WITHOUT asking for username/password

3. **Create Database (Optional)**
   - Open: `http://localhost/phpmyadmin`
   - Create database: `mkfinder`
   - Import: `database.sql`

## Files Included

- `index.php` - Complete single-file application
- `.htaccess` - Removes authentication requirements
- `database.sql` - Simple database schema
- `QUICK-START.md` - This guide

## Features

- ✅ No username/password required
- ✅ Works with or without database
- ✅ Responsive design
- ✅ File upload interface
- ✅ Demo bird identification
- ✅ System status display

## Troubleshooting

If still getting authentication prompt:
1. Ensure `.htaccess` file is in the same folder
2. Try accessing: `http://localhost/mkfinder/index.php` directly
3. Check XAMPP Apache config for authentication settings

The application will work even without database setup - it shows a demo version with simulated identification results.
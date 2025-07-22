# MKfinder - Bird Species Identification System

A complete web-based bird identification application built with HTML, CSS, JavaScript, and PHP with PostgreSQL database support.

## Features

- **Bird Species Identification**: Supports American Robin, Blue Jay, and Northern Cardinal
- **Image Upload**: Drag-and-drop interface for uploading bird photos
- **Species Database**: Comprehensive information about each supported bird species
- **Gallery**: View identification history and uploaded images
- **Statistics**: Track identification accuracy and species frequency
- **Responsive Design**: Works on desktop and mobile devices

## Requirements

- PHP 8.0 or higher with PDO PostgreSQL extension
- PostgreSQL database
- Web server (Apache, Nginx, or PHP built-in server)

## Database Setup

1. Create a PostgreSQL database
2. Set the DATABASE_URL environment variable:
   ```
   DATABASE_URL=postgresql://username:password@host:port/database_name
   ```
3. Run the database initialization by accessing `test-db.php` in your browser
4. The database tables and initial data will be created automatically

## Installation

1. Extract all files to your web server directory
2. Ensure the `uploads` directory is writable by the web server
3. Set up your PostgreSQL database connection
4. Access `index.html` in your web browser

## File Structure

- `index.html` - Main application page
- `styles.css` - Custom styling and responsive design
- `script.js` - Frontend JavaScript functionality
- `config.php` - Configuration and utility functions
- `database.php` - PostgreSQL database class and operations
- `upload.php` - File upload handling
- `identify.php` - Bird identification logic
- `species.php` - Species information display
- `gallery.php` - Image gallery and history
- `test-db.php` - Database connection testing
- `images/` - Custom SVG bird illustrations
- `uploads/` - Directory for uploaded images

## Usage

1. Open the application in your web browser
2. Upload a bird image using the drag-and-drop interface
3. Click "Identify Bird" to get species identification
4. View detailed species information and identification history
5. Browse the gallery to see all uploaded images and statistics

## Technical Details

- **Frontend**: Pure JavaScript with Bootstrap 5.3.0 for responsive UI
- **Backend**: PHP with PostgreSQL database
- **Database**: Optimized schema with proper indexing and relationships
- **Security**: Input sanitization and file upload validation
- **Performance**: Pagination support and efficient database queries

## License

Custom project for educational and demonstration purposes.
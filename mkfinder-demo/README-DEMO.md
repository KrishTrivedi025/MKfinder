# MKfinder - Demo Version

This is a demonstration version of the MKfinder bird species identification system.

## Demo Functionality

**Important**: This version is configured to show demo results for any uploaded image. When you upload any bird image, the system will randomly identify it as one of three supported species:

- American Robin
- Blue Jay  
- Northern Cardinal

The identification results include:
- Species name
- Random confidence score (80-99%)
- Detailed description
- Key characteristics
- Habitat information
- Scientific name

## Installation

1. Extract all files to your web server directory
2. Ensure PHP is installed and running
3. Start your web server (Apache/Nginx)
4. Navigate to the project directory in your browser

## Quick Start

1. Open `index.html` in your web browser
2. Upload any bird image using the drag-and-drop interface
3. Click "Identify Species" to see demo results
4. The system will randomly display information for one of the three supported species

## Project Structure

- `index.html` - Main application interface
- `script.js` - Frontend JavaScript functionality
- `styles.css` - Application styling
- `identify.php` - Backend identification handler (demo mode)
- `config.php` - Configuration settings
- `database.php` - Database operations
- `images/` - Bird species SVG illustrations
- `uploads/` - Directory for uploaded images

## Features

- Responsive web design
- Drag-and-drop file upload
- Real-time image preview
- Demo species identification
- Bootstrap 5 styling
- Font Awesome icons

## Note

This is a demonstration version. For production use with real AI identification, you would need to integrate with an actual bird identification API service.
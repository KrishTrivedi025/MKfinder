<?php
/**
 * MKfinder - Simple PHP Version
 * No authentication required
 */

// Basic configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'mkfinder');
define('DB_USER', 'root');
define('DB_PASS', '');

// Test database connection
$dbConnected = false;
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $dbConnected = true;
} catch (PDOException $e) {
    $dbError = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MKfinder - Bird Species Identification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c5530;
            --secondary-color: #4a7c59;
            --accent-color: #8fbc8f;
            --warm-white: #fefefe;
        }
        body { background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); min-height: 100vh; }
        .main-container { background: var(--warm-white); border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .upload-area { border: 3px dashed #dee2e6; border-radius: 10px; padding: 40px; text-align: center; transition: all 0.3s ease; cursor: pointer; }
        .upload-area:hover { border-color: var(--accent-color); background-color: #f8f9fa; }
        .upload-area.dragover { border-color: var(--primary-color); background-color: #e8f5e8; }
        .btn-primary { background: var(--primary-color); border-color: var(--primary-color); }
        .btn-primary:hover { background: var(--secondary-color); }
        .species-card { transition: transform 0.2s; }
        .species-card:hover { transform: translateY(-5px); }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="main-container p-5">
            <!-- Header -->
            <div class="text-center mb-5">
                <h1 class="display-4 fw-bold text-primary">
                    <i class="fas fa-dove me-3"></i>MKfinder
                </h1>
                <p class="lead">Bird Species Identification System</p>
            </div>

            <!-- Status Check -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-server me-2"></i>System Status
                            </h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <span class="badge bg-success">✓ PHP Working</span>
                                    <small class="text-muted d-block">Version: <?php echo PHP_VERSION; ?></small>
                                </div>
                                <div class="col-md-6">
                                    <?php if ($dbConnected): ?>
                                        <span class="badge bg-success">✓ Database Connected</span>
                                        <small class="text-muted d-block">MySQL Ready</small>
                                    <?php else: ?>
                                        <span class="badge bg-warning">⚠ Database Issue</span>
                                        <small class="text-muted d-block">Need to create 'mkfinder' database</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!$dbConnected): ?>
            <!-- Database Setup Instructions -->
            <div class="alert alert-info">
                <h5 class="alert-heading">
                    <i class="fas fa-database me-2"></i>Database Setup Required
                </h5>
                <p>To use the bird identification system:</p>
                <ol>
                    <li>Open phpMyAdmin: <a href="http://localhost/phpmyadmin" target="_blank">http://localhost/phpmyadmin</a></li>
                    <li>Create a new database named: <code>mkfinder</code></li>
                    <li>Import the provided SQL file</li>
                    <li>Refresh this page</li>
                </ol>
            </div>
            <?php else: ?>
            
            <!-- Upload Section -->
            <div class="row mb-5">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title mb-4">
                                <i class="fas fa-upload me-2"></i>Upload Bird Image
                            </h5>
                            
                            <div class="upload-area" id="uploadArea">
                                <div class="upload-content">
                                    <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                    <h5>Drag & drop your bird image here</h5>
                                    <p class="text-muted">or click to browse files</p>
                                    <input type="file" id="fileInput" class="d-none" accept="image/*">
                                </div>
                            </div>
                            
                            <div class="mt-3" id="imagePreview" style="display: none;">
                                <img id="previewImg" class="img-fluid rounded" style="max-height: 300px;">
                                <div class="mt-3">
                                    <button class="btn btn-primary" id="identifyBtn">
                                        <i class="fas fa-search me-2"></i>Identify Bird
                                    </button>
                                    <button class="btn btn-secondary ms-2" id="clearBtn">
                                        <i class="fas fa-times me-2"></i>Clear
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results Section -->
            <div class="row mb-5" id="resultsSection" style="display: none;">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-clipboard-check me-2"></i>Identification Results
                            </h5>
                            <div id="results"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php endif; ?>

            <!-- Supported Species -->
            <div class="row">
                <div class="col-12">
                    <h4 class="text-center mb-4">
                        <i class="fas fa-feather-alt me-2"></i>Supported Bird Species
                    </h4>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card species-card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-dove fa-3x text-danger"></i>
                            </div>
                            <h5 class="card-title">American Robin</h5>
                            <p class="card-text text-muted">
                                <em>Turdus migratorius</em><br>
                                Distinctive red breast, common in gardens
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card species-card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-dove fa-3x text-primary"></i>
                            </div>
                            <h5 class="card-title">Blue Jay</h5>
                            <p class="card-text text-muted">
                                <em>Cyanocitta cristata</em><br>
                                Bright blue with prominent crest
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card species-card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-dove fa-3x" style="color: #dc3545;"></i>
                            </div>
                            <h5 class="card-title">Northern Cardinal</h5>
                            <p class="card-text text-muted">
                                <em>Cardinalis cardinalis</em><br>
                                Vibrant red male, crested songbird
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // File upload handling
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('fileInput');
        const imagePreview = document.getElementById('imagePreview');
        const previewImg = document.getElementById('previewImg');
        const identifyBtn = document.getElementById('identifyBtn');
        const clearBtn = document.getElementById('clearBtn');
        const resultsSection = document.getElementById('resultsSection');
        const results = document.getElementById('results');

        // Click to upload
        uploadArea.addEventListener('click', () => fileInput.click());

        // Drag and drop
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleFile(files[0]);
            }
        });

        // File input change
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                handleFile(e.target.files[0]);
            }
        });

        // Handle file upload
        function handleFile(file) {
            if (!file.type.startsWith('image/')) {
                alert('Please select an image file.');
                return;
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                previewImg.src = e.target.result;
                imagePreview.style.display = 'block';
                uploadArea.style.display = 'none';
            };
            reader.readAsDataURL(file);
        }

        // Identify button
        identifyBtn.addEventListener('click', () => {
            // Simulate bird identification
            const species = ['American Robin', 'Blue Jay', 'Northern Cardinal'];
            const randomSpecies = species[Math.floor(Math.random() * species.length)];
            const confidence = Math.floor(Math.random() * 30) + 70; // 70-99%

            results.innerHTML = `
                <div class="alert alert-success">
                    <h5><i class="fas fa-check-circle me-2"></i>Identification Complete</h5>
                    <p><strong>Species:</strong> ${randomSpecies}</p>
                    <p><strong>Confidence:</strong> ${confidence}%</p>
                    <p class="mb-0"><em>This is a demonstration version. In the full system, this would use AI-powered image recognition.</em></p>
                </div>
            `;
            resultsSection.style.display = 'block';
        });

        // Clear button
        clearBtn.addEventListener('click', () => {
            imagePreview.style.display = 'none';
            uploadArea.style.display = 'block';
            resultsSection.style.display = 'none';
            fileInput.value = '';
        });
    </script>
</body>
</html>
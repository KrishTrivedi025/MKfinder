<?php
/**
 * MKfinder Gallery Handler
 * Displays gallery of bird images and identification history
 */

require_once 'config.php';
require_once 'database.php';

// Get species filter
$speciesFilter = isset($_GET['species']) ? sanitizeInput($_GET['species']) : '';

// Pagination
$itemsPerPage = 12;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $itemsPerPage;

// Get identification data from database
try {
    $db = getDatabase();
    $totalItems = $db->countIdentifications($speciesFilter);
    $totalPages = ceil($totalItems / $itemsPerPage);
    $paginatedIdentifications = $db->getRecentIdentifications($itemsPerPage, $offset, $speciesFilter);
} catch (Exception $e) {
    logError('Failed to get identifications from database', ['error' => $e->getMessage()]);
    $totalItems = 0;
    $totalPages = 0;
    $paginatedIdentifications = [];
}

// Get supported species for filter dropdown
$supportedSpecies = SUPPORTED_SPECIES;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery - MKfinder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.html">
                <div class="logo-mk me-2">MK</div>
                <span class="brand-name">finder</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.html">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="gallery.php">Gallery</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="species.php">Species Info</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5 pt-5">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h1 class="display-5 fw-bold">
                    <i class="fas fa-images text-primary me-3"></i>
                    Identification Gallery
                </h1>
                <p class="lead text-muted">Browse previous bird identifications and results</p>
            </div>
            <div class="col-md-4">
                <div class="card border-0 bg-light">
                    <div class="card-body text-center">
                        <h5 class="card-title mb-1">Total Identifications</h5>
                        <h2 class="text-primary mb-0"><?php echo $totalItems; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-md-6">
                <form method="GET" class="d-flex">
                    <select name="species" class="form-select me-2">
                        <option value="">All Species</option>
                        <?php foreach ($supportedSpecies as $species): ?>
                            <option value="<?php echo htmlspecialchars($species); ?>" 
                                    <?php echo ($speciesFilter === $species) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($species); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="fas fa-filter me-1"></i>
                        Filter
                    </button>
                    <?php if (!empty($speciesFilter)): ?>
                        <a href="gallery.php" class="btn btn-outline-secondary ms-2">
                            <i class="fas fa-times me-1"></i>
                            Clear
                        </a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="col-md-6 text-md-end">
                <a href="index.html" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>
                    Add New Identification
                </a>
            </div>
        </div>

        <!-- Gallery Content -->
        <?php if (!empty($paginatedIdentifications)): ?>
            <div class="gallery-grid mb-5">
                <?php foreach ($paginatedIdentifications as $identification): ?>
                    <div class="gallery-item">
                        <div class="position-relative">
                            <?php 
                            $imagePath = 'uploads/' . $identification['filename'];
                            if (file_exists($imagePath)): 
                            ?>
                                <img src="<?php echo htmlspecialchars($imagePath); ?>" 
                                     alt="<?php echo htmlspecialchars($identification['species_name']); ?>" 
                                     class="w-100">
                            <?php else: ?>
                                <div class="d-flex align-items-center justify-content-center bg-light" style="height: 200px;">
                                    <i class="fas fa-image fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="gallery-overlay">
                                <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($identification['species_name']); ?></h6>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge bg-success"><?php echo $identification['confidence']; ?>% confidence</span>
                                    <small><?php echo date('M j, Y', strtotime($identification['identification_time'])); ?></small>
                                </div>
                                <div class="mt-2">
                                    <small class="text-light">
                                        <i class="fas fa-file-image me-1"></i>
                                        <?php echo htmlspecialchars($identification['original_name']); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Gallery pagination">
                    <ul class="pagination justify-content-center">
                        <!-- Previous Page -->
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($speciesFilter) ? '&species=' . urlencode($speciesFilter) : ''; ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>

                        <!-- Page Numbers -->
                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        for ($i = $startPage; $i <= $endPage; $i++):
                        ?>
                            <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($speciesFilter) ? '&species=' . urlencode($speciesFilter) : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <!-- Next Page -->
                        <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($speciesFilter) ? '&species=' . urlencode($speciesFilter) : ''; ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>

                <div class="text-center text-muted">
                    <small>
                        Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $itemsPerPage, $totalItems); ?> 
                        of <?php echo $totalItems; ?> identifications
                    </small>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- Empty State -->
            <div class="empty-state">
                <i class="fas fa-images"></i>
                <h3>No Identifications Found</h3>
                <?php if (!empty($speciesFilter)): ?>
                    <p>No identifications found for <strong><?php echo htmlspecialchars($speciesFilter); ?></strong>.</p>
                    <a href="gallery.php" class="btn btn-outline-primary me-2">
                        <i class="fas fa-times me-2"></i>
                        Clear Filter
                    </a>
                <?php else: ?>
                    <p>No bird identifications have been made yet. Start by uploading your first bird image!</p>
                <?php endif; ?>
                <a href="index.html" class="btn btn-primary">
                    <i class="fas fa-camera me-2"></i>
                    Identify Your First Bird
                </a>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <?php if ($totalItems > 0): ?>
            <div class="row mt-5">
                <div class="col-12">
                    <h3 class="fw-bold mb-4">
                        <i class="fas fa-chart-bar text-primary me-2"></i>
                        Identification Statistics
                    </h3>
                </div>
            </div>

            <div class="row g-4">
                <?php
                // Get species statistics from database
                try {
                    $db = getDatabase();
                    $speciesStats = $db->getSpeciesStatistics();
                } catch (Exception $e) {
                    logError('Failed to get species statistics from database', ['error' => $e->getMessage()]);
                    $speciesStats = [];
                }
                ?>

                <?php foreach ($speciesStats as $stats): ?>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <h5 class="card-title"><?php echo htmlspecialchars($stats['species_name']); ?></h5>
                                <div class="row">
                                    <div class="col-6">
                                        <div class="text-primary fw-bold fs-4"><?php echo $stats['identification_count']; ?></div>
                                        <small class="text-muted">Identifications</small>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-success fw-bold fs-4"><?php echo round($stats['avg_confidence'], 1); ?>%</div>
                                        <small class="text-muted">Avg. Confidence</small>
                                    </div>
                                </div>
                                <a href="species.php?species=<?php echo urlencode($stats['species_name']); ?>" class="btn btn-outline-primary btn-sm mt-2">
                                    Learn More
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <div class="d-flex align-items-center mb-2">
                        <div class="logo-mk me-2">MK</div>
                        <span class="brand-name">finder</span>
                    </div>
                    <p class="text-muted small">Advanced bird species identification system</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted small mb-0">&copy; 2025 MKfinder. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
/**
 * MKfinder Species Information Handler
 * Displays detailed information about bird species
 */

require_once 'config.php';

// Get species parameter
$speciesName = isset($_GET['species']) ? sanitizeInput($_GET['species']) : '';

// Get database
$database = getDatabase();

// Find species information
$speciesInfo = null;
if (!empty($speciesName)) {
    $speciesInfo = getSpeciesInfo($speciesName);
}

// Get all species for listing
$allSpecies = getAllSpecies();

/**
 * Get species information
 */
function getSpeciesInfo($speciesName) {
    $database = getDatabase();
    
    if (isset($database['species'])) {
        foreach ($database['species'] as $species) {
            if (strcasecmp($species['name'], $speciesName) === 0) {
                return $species;
            }
        }
    }

    // Return default information if not found
    return getDefaultSpeciesInfo($speciesName);
}

/**
 * Get all supported species
 */
function getAllSpecies() {
    $species = [];
    foreach (SUPPORTED_SPECIES as $speciesName) {
        $info = getSpeciesInfo($speciesName);
        if (!empty($info)) {
            $species[] = $info;
        }
    }
    return $species;
}

/**
 * Get default species information
 */
function getDefaultSpeciesInfo($speciesName) {
    $defaultInfo = [
        'American Robin' => [
            'name' => 'American Robin',
            'scientific_name' => 'Turdus migratorius',
            'description' => 'The American Robin is a migratory songbird of the true thrush genus and Turdidae, the wider thrush family. It is named after the European robin because of its reddish-orange breast, though the two species are not closely related.',
            'characteristics' => [
                'Red-orange breast and belly',
                'Dark gray to black head and back',
                'White markings around the eyes',
                'Yellow beak with dark tip',
                'White undertail coverts',
                'Length: 8-11 inches',
                'Wingspan: 12-16 inches'
            ],
            'habitat' => 'Woodlands, parks, gardens, and lawns across North America',
            'behavior' => 'Known for pulling earthworms from lawns, territorial during breeding season, forms flocks in winter',
            'diet' => 'Insects, earthworms, fruits, and berries',
            'conservation_status' => 'Least Concern'
        ],
        'Blue Jay' => [
            'name' => 'Blue Jay',
            'scientific_name' => 'Cyanocitta cristata',
            'description' => 'The Blue Jay is a passerine bird in the family Corvidae, native to eastern North America. It is a highly intelligent and social bird known for its distinctive blue coloration and complex vocalizations.',
            'characteristics' => [
                'Bright blue upper parts with white underparts',
                'Black necklace markings across throat',
                'Prominent blue crest that can be raised or lowered',
                'White and black barred wings and tail',
                'Black bill and legs',
                'Length: 11-12 inches',
                'Wingspan: 13-17 inches'
            ],
            'habitat' => 'Deciduous and mixed forests, parks, and residential areas with large trees',
            'behavior' => 'Highly social, forms complex family groups, known for mobbing predators, excellent mimics',
            'diet' => 'Nuts, seeds, insects, eggs, and small animals',
            'conservation_status' => 'Least Concern'
        ],
        'Northern Cardinal' => [
            'name' => 'Northern Cardinal',
            'scientific_name' => 'Cardinalis cardinalis',
            'description' => 'The Northern Cardinal is a bird in the genus Cardinalis. It is also known colloquially as the redbird, common cardinal, red cardinal, or just cardinal. Males are vibrant red while females are a warm brown with red accents.',
            'characteristics' => [
                'Males: Brilliant red all over with black mask',
                'Females: Warm brown with red tinges on wings, tail, and crest',
                'Thick, orange-red, cone-shaped beak',
                'Prominent red crest',
                'Black face mask around beak and eyes (males)',
                'Length: 8.5-9 inches',
                'Wingspan: 9.8-12.2 inches'
            ],
            'habitat' => 'Woodlands, gardens, shrublands, and wetlands with dense cover',
            'behavior' => 'Non-migratory, territorial, males sing to defend territory, females also sing',
            'diet' => 'Seeds, grains, fruits, and insects',
            'conservation_status' => 'Least Concern'
        ]
    ];

    return $defaultInfo[$speciesName] ?? null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $speciesInfo ? $speciesInfo['name'] . ' - ' : ''; ?>Species Information - MKfinder</title>
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
                        <a class="nav-link" href="gallery.php">Gallery</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="species.php">Species Info</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-5 pt-5">
        <?php if ($speciesInfo): ?>
            <!-- Species Detail View -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow-lg border-0">
                        <div class="card-body p-5">
                            <div class="d-flex align-items-center mb-4">
                                <h1 class="display-5 fw-bold mb-0 me-3"><?php echo htmlspecialchars($speciesInfo['name']); ?></h1>
                                <span class="badge bg-primary fs-6"><?php echo htmlspecialchars($speciesInfo['scientific_name']); ?></span>
                            </div>

                            <p class="lead text-muted mb-4"><?php echo htmlspecialchars($speciesInfo['description']); ?></p>

                            <?php if (!empty($speciesInfo['characteristics'])): ?>
                                <div class="mb-4">
                                    <h3 class="h4 fw-bold mb-3">
                                        <i class="fas fa-list-ul text-primary me-2"></i>
                                        Key Characteristics
                                    </h3>
                                    <div class="row">
                                        <?php foreach ($speciesInfo['characteristics'] as $characteristic): ?>
                                            <div class="col-md-6 mb-2">
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-check text-success me-2"></i>
                                                    <span><?php echo htmlspecialchars($characteristic); ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="row">
                                <?php if (!empty($speciesInfo['habitat'])): ?>
                                    <div class="col-md-6 mb-4">
                                        <h4 class="h5 fw-bold">
                                            <i class="fas fa-tree text-success me-2"></i>
                                            Habitat
                                        </h4>
                                        <p class="text-muted"><?php echo htmlspecialchars($speciesInfo['habitat']); ?></p>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($speciesInfo['diet'])): ?>
                                    <div class="col-md-6 mb-4">
                                        <h4 class="h5 fw-bold">
                                            <i class="fas fa-utensils text-warning me-2"></i>
                                            Diet
                                        </h4>
                                        <p class="text-muted"><?php echo htmlspecialchars($speciesInfo['diet']); ?></p>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($speciesInfo['behavior'])): ?>
                                    <div class="col-md-6 mb-4">
                                        <h4 class="h5 fw-bold">
                                            <i class="fas fa-brain text-info me-2"></i>
                                            Behavior
                                        </h4>
                                        <p class="text-muted"><?php echo htmlspecialchars($speciesInfo['behavior']); ?></p>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($speciesInfo['conservation_status'])): ?>
                                    <div class="col-md-6 mb-4">
                                        <h4 class="h5 fw-bold">
                                            <i class="fas fa-shield-alt text-primary me-2"></i>
                                            Conservation Status
                                        </h4>
                                        <p class="text-muted"><?php echo htmlspecialchars($speciesInfo['conservation_status']); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="mt-4">
                                <a href="index.html" class="btn btn-primary">
                                    <i class="fas fa-camera me-2"></i>
                                    Identify This Species
                                </a>
                                <a href="species.php" class="btn btn-outline-secondary ms-2">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    Back to Species List
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card shadow border-0">
                        <div class="card-body">
                            <h5 class="card-title fw-bold">
                                <i class="fas fa-info-circle text-primary me-2"></i>
                                Quick Facts
                            </h5>
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <strong>Scientific Name:</strong><br>
                                    <em><?php echo htmlspecialchars($speciesInfo['scientific_name']); ?></em>
                                </li>
                                <?php if (!empty($speciesInfo['conservation_status'])): ?>
                                    <li class="mb-2">
                                        <strong>Conservation Status:</strong><br>
                                        <?php echo htmlspecialchars($speciesInfo['conservation_status']); ?>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>

                    <div class="card shadow border-0 mt-4">
                        <div class="card-body">
                            <h5 class="card-title fw-bold">
                                <i class="fas fa-dove text-primary me-2"></i>
                                Other Species
                            </h5>
                            <div class="list-group list-group-flush">
                                <?php foreach ($allSpecies as $species): ?>
                                    <?php if ($species['name'] !== $speciesInfo['name']): ?>
                                        <a href="species.php?species=<?php echo urlencode($species['name']); ?>" 
                                           class="list-group-item list-group-item-action border-0">
                                            <div class="fw-bold"><?php echo htmlspecialchars($species['name']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($species['scientific_name']); ?></small>
                                        </a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Species List View -->
            <div class="row">
                <div class="col-12">
                    <div class="text-center mb-5">
                        <h1 class="display-4 fw-bold">Supported Bird Species</h1>
                        <p class="lead text-muted">Learn about the bird species our system can identify</p>
                    </div>
                </div>
            </div>

            <?php if (!empty($allSpecies)): ?>
                <div class="row g-4">
                    <?php foreach ($allSpecies as $species): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="card species-card shadow border-0 h-100">
                                <div class="card-body p-4">
                                    <h5 class="card-title fw-bold"><?php echo htmlspecialchars($species['name']); ?></h5>
                                    <p class="text-muted small mb-2"><?php echo htmlspecialchars($species['scientific_name']); ?></p>
                                    <p class="card-text text-muted">
                                        <?php echo htmlspecialchars(substr($species['description'], 0, 150) . '...'); ?>
                                    </p>
                                    <a href="species.php?species=<?php echo urlencode($species['name']); ?>" 
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Learn More
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>No Species Information Available</h3>
                    <p>Species information is not currently available. Please check back later or configure the system with species data.</p>
                    <a href="index.html" class="btn btn-primary">
                        <i class="fas fa-home me-2"></i>
                        Return to Home
                    </a>
                </div>
            <?php endif; ?>
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

<?php
/**
 * MKfinder Gallery — Redesigned
 * Shows ONLY the logged-in user's own identifications
 */

require_once 'config.php';
require_once 'database.php';

initSession();

// ── Must be logged in — show inline modal like Bird Explorer ─────────────
if (!isLoggedIn()) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery — MKfinder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{background:#f0f7f4;font-family:'Inter',sans-serif;}
        .navbar{background:rgba(13,27,42,.96)!important;backdrop-filter:blur(20px);
                border-bottom:1px solid rgba(116,198,157,.15);padding:14px 0;}
        .page-hero{background:linear-gradient(135deg,#0d1b2a 0%,#1b4332 45%,#2d6a4f 100%);
                   padding:110px 0 60px;position:relative;overflow:hidden;}
        .page-hero::before{content:'';position:absolute;inset:0;pointer-events:none;
            background:radial-gradient(circle at 20% 55%,rgba(116,198,157,.18) 0%,transparent 50%),
                       radial-gradient(circle at 80% 20%,rgba(72,202,228,.1) 0%,transparent 40%);}
        .hero-wave{display:block;width:100%;line-height:0;}
        /* Login modal overlay */
        #galleryLoginOverlay{position:fixed;inset:0;z-index:9999;display:flex;
            align-items:center;justify-content:center;padding:20px;
            background:rgba(13,27,42,.82);backdrop-filter:blur(10px);}
        .gallery-login-modal{background:linear-gradient(135deg,#0d1b2a,#0f2236);
            border:1px solid rgba(116,198,157,.25);border-radius:24px;
            padding:40px 36px;max-width:400px;width:100%;text-align:center;
            box-shadow:0 32px 80px rgba(0,0,0,.5);
            animation:glModalIn .3s cubic-bezier(.34,1.3,.64,1);}
        @keyframes glModalIn{from{opacity:0;transform:scale(.88) translateY(20px)}
                              to{opacity:1;transform:scale(1) translateY(0)}}
        .gl-icon{width:72px;height:72px;border-radius:50%;margin:0 auto 20px;
                 background:linear-gradient(135deg,rgba(64,145,108,.2),rgba(116,198,157,.1));
                 border:2px solid rgba(116,198,157,.3);
                 display:flex;align-items:center;justify-content:center;
                 animation:glPulse 2.5s ease-in-out infinite;}
        @keyframes glPulse{0%,100%{box-shadow:0 0 0 0 rgba(116,198,157,.3)}
                           50%{box-shadow:0 0 0 12px rgba(116,198,157,.0)}}
        .gl-icon i{font-size:2rem;color:#74c69d;}
        .gl-title{font-family:'Playfair Display',serif;font-size:1.5rem;font-weight:700;
                  color:#fff;margin-bottom:10px;}
        .gl-title em{color:#74c69d;font-style:normal;}
        .gl-desc{color:rgba(255,255,255,.5);font-size:.88rem;line-height:1.7;margin-bottom:28px;}
        .gl-btn-login{display:flex;align-items:center;justify-content:center;gap:8px;
                      width:100%;padding:13px 24px;margin-bottom:10px;
                      background:linear-gradient(135deg,#40916c,#74c69d);color:#fff;
                      border:none;border-radius:50px;font-size:.9rem;font-weight:700;
                      text-decoration:none;font-family:'Inter',sans-serif;transition:transform .2s;}
        .gl-btn-login:hover{transform:translateY(-2px);color:#fff;}
        .gl-btn-signup{display:flex;align-items:center;justify-content:center;gap:8px;
                       width:100%;padding:13px 24px;margin-bottom:20px;
                       background:rgba(255,255,255,.06);color:rgba(255,255,255,.8);
                       border:1px solid rgba(255,255,255,.15);border-radius:50px;
                       font-size:.9rem;font-weight:600;text-decoration:none;
                       font-family:'Inter',sans-serif;transition:transform .2s;}
        .gl-btn-signup:hover{transform:translateY(-2px);color:#fff;}
        .gl-back{font-size:.8rem;color:rgba(255,255,255,.3);text-decoration:none;
                 display:inline-flex;align-items:center;gap:5px;transition:color .2s;}
        .gl-back:hover{color:#74c69d;}
        .site-footer{background:#0d1b2a;padding:40px 0 0;}
        .footer-bottom{border-top:1px solid rgba(255,255,255,.06);padding:20px 0;
                       font-size:.82rem;color:rgba(255,255,255,.25);}
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.html">
            <div class="logo-mk me-2">MK</div>
            <span class="brand-name">finder</span>
        </a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto align-items-center gap-1">
                <li class="nav-item"><a class="nav-link" href="index.html">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="species.php"><i class="fas fa-feather me-1"></i>Species</a></li>
                <li class="nav-item"><a class="nav-link" href="community.php"><i class="fas fa-globe me-1"></i>Bird Explorer</a></li>
                <li class="nav-item"><a class="nav-link" href="login.html">Login</a></li>
                <li class="nav-item ms-2">
                    <a class="btn-hero-primary px-4 py-2" href="signup.html" style="font-size:.88rem;text-decoration:none;">
                        <i class="fas fa-user-plus me-1"></i>Sign Up
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<section class="page-hero">
    <div class="container position-relative" style="z-index:2;">
        <div class="text-center pb-4">
            <div style="display:inline-flex;align-items:center;gap:8px;background:rgba(116,198,157,.15);
                        border:1px solid rgba(116,198,157,.3);color:#74c69d;padding:6px 16px;
                        border-radius:50px;font-size:.78rem;font-weight:600;letter-spacing:.5px;
                        text-transform:uppercase;margin-bottom:16px;">
                <i class="fas fa-images"></i> Personal Gallery
            </div>
            <h1 style="font-family:'Playfair Display',serif;font-size:clamp(2rem,4.5vw,2.8rem);
                        color:#fff;font-weight:800;margin-bottom:10px;">
                Your Bird <span style="color:#74c69d;">Gallery</span>
            </h1>
            <p style="color:rgba(255,255,255,.5);font-size:.95rem;">
                Your personal collection of identified bird sightings.
            </p>
        </div>
    </div>
    <svg class="hero-wave" viewBox="0 0 1440 60" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M0,40 C360,80 1080,0 1440,40 L1440,60 L0,60 Z" fill="#f0f7f4"/>
    </svg>
</section>

<!-- Inline Login Required Modal (like Bird Explorer) -->
<div id="galleryLoginOverlay">
    <div class="gallery-login-modal">
        <div class="gl-icon"><i class="fas fa-images"></i></div>
        <div class="gl-title">Your <em>Gallery</em></div>
        <p class="gl-desc">Your personal bird identification gallery is private.<br>
            Login to view your saved sightings and photos.</p>
        <a href="login.html" class="gl-btn-login">
            <i class="fas fa-sign-in-alt"></i> Login to View Gallery
        </a>
        <a href="signup.html" class="gl-btn-signup">
            <i class="fas fa-user-plus"></i> Create Free Account
        </a>
        <a href="index.html" class="gl-back">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>
    </div>
</div>

<footer class="site-footer">
    <div class="container">
        <div class="footer-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span>&copy; 2025 MKfinder. All rights reserved.</span>
            <span>Made with <i class="fas fa-heart" style="color:#f87171;"></i> for bird lovers</span>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
    exit;
}

// ── Current user from session ─────────────────────────────────
$currentUserId   = $_SESSION['user_id'];
$currentUserName = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
if (empty(trim($currentUserName))) $currentUserName = $_SESSION['email'] ?? 'User';

$speciesFilter = isset($_GET['species']) ? sanitizeInput($_GET['species']) : '';

$itemsPerPage = 12;
$page         = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset       = ($page - 1) * $itemsPerPage;

// ── Fetch ONLY this user's data ───────────────────────────────
try {
    $db                       = getDatabase();
    $totalItems               = $db->countUserIdentifications($currentUserId, $speciesFilter);
    $totalPages               = ceil($totalItems / $itemsPerPage);
    $paginatedIdentifications = $db->getUserIdentifications($currentUserId, $itemsPerPage, $offset, $speciesFilter);
} catch (Exception $e) {
    logError('Gallery DB error', ['error' => $e->getMessage()]);
    $totalItems = $totalPages = 0;
    $paginatedIdentifications = [];
}

try {
    $db          = getDatabase();
    $speciesStats = $db->getUserSpeciesStatistics($currentUserId);
} catch (Exception $e) {
    $speciesStats = [];
}

// Fetch species list from DB — auto-updates when admin adds new species
$supportedSpecies = [];
try {
    $conn = getDatabase()->getConnection();
    $rows = $conn->query("SELECT name FROM species WHERE status='approved' ORDER BY name ASC")->fetchAll();
    foreach ($rows as $row) {
        $supportedSpecies[] = $row['name'];
    }
} catch (Exception $e) {
    // Fallback to config if DB fails
    $supportedSpecies = array_values(SUPPORTED_SPECIES);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Gallery – MKfinder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">

    <style>
        /* ── PAGE-LEVEL OVERRIDES ── */
        body { background: #f0f7f4; }

        /* ── NAVBAR (exact match to index) ── */
        .navbar {
            background: rgba(13,27,42,.96) !important;
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(116,198,157,.15);
            padding: 14px 0;
        }
        .navbar.scrolled {
            background: rgba(13,27,42,.99) !important;
            box-shadow: 0 4px 30px rgba(0,0,0,.3);
        }

        /* ── PAGE HERO ── */
        .gallery-hero {
            background: linear-gradient(135deg, #0d1b2a 0%, #1b4332 45%, #2d6a4f 100%);
            padding: 130px 0 70px;
            position: relative;
            overflow: hidden;
        }
        .gallery-hero::before {
            content:'';
            position:absolute; inset:0;
            background:
                radial-gradient(circle at 15% 50%, rgba(116,198,157,.18) 0%, transparent 50%),
                radial-gradient(circle at 85% 20%, rgba(72,202,228,.1) 0%,  transparent 40%);
            pointer-events:none;
        }
        .gallery-hero::after {
            content:'';
            position:absolute; bottom:-2px; left:0; right:0; height:80px;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 80'%3E%3Cpath fill='%23f0f7f4' d='M0,40L60,36C120,32,240,24,360,28C480,32,600,48,720,52C840,56,960,48,1080,40C1200,32,1320,24,1380,20L1440,16L1440,80L0,80Z'/%3E%3C/svg%3E") no-repeat bottom / cover;
            pointer-events:none;
        }
        .gallery-hero .hero-badge {
            display:inline-flex; align-items:center; gap:8px;
            background:rgba(116,198,157,.15);
            border:1px solid rgba(116,198,157,.3);
            color:#74c69d; padding:6px 16px; border-radius:50px;
            font-size:.78rem; font-weight:600; letter-spacing:.5px;
            text-transform:uppercase; margin-bottom:18px;
        }
        .gallery-hero h1 {
            font-family:'Playfair Display',serif;
            font-size: clamp(2rem,5vw,3rem);
            color:#fff; font-weight:800; margin-bottom:12px; line-height:1.15;
        }
        .gallery-hero h1 span {
            background:linear-gradient(135deg,#74c69d,#48cae4);
            -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;
        }
        .gallery-hero p { color:rgba(255,255,255,.55); font-size:1rem; }

        /* Stat pills in hero */
        .hero-pill {
            display:inline-flex; align-items:center; gap:10px;
            background:rgba(255,255,255,.06);
            border:1px solid rgba(116,198,157,.2);
            border-radius:50px; padding:10px 22px;
            backdrop-filter:blur(12px);
        }
        .hero-pill .pill-ico {
            width:32px; height:32px; border-radius:50%;
            background:linear-gradient(135deg,#40916c,#74c69d);
            display:flex; align-items:center; justify-content:center;
            font-size:.8rem; color:#fff; flex-shrink:0;
        }
        .hero-pill .pill-val { font-size:1.1rem; font-weight:700; color:#fff; line-height:1.1; }
        .hero-pill .pill-lbl { font-size:.72rem; color:rgba(255,255,255,.45); }

        /* Floating dots (cosmetic) */
        .hero-dots { position:absolute; inset:0; pointer-events:none; }
        .hdot {
            position:absolute; border-radius:50%;
            background:rgba(116,198,157,.25);
            animation:hdotFloat 8s ease-in-out infinite;
        }
        .hdot:nth-child(1){ width:6px;height:6px; top:25%; left:8%;  animation-delay:0s; }
        .hdot:nth-child(2){ width:4px;height:4px; top:65%; left:18%; animation-delay:1.5s; }
        .hdot:nth-child(3){ width:8px;height:8px; top:35%; right:12%;animation-delay:3s; }
        .hdot:nth-child(4){ width:5px;height:5px; top:70%; right:22%;animation-delay:2s; }
        @keyframes hdotFloat {
            0%,100%{ transform:translateY(0) scale(1); opacity:.3; }
            50%    { transform:translateY(-18px) scale(1.3); opacity:.7; }
        }

        /* ── TOOLBAR BAR ── */
        .toolbar {
            background:#fff;
            border-radius:16px;
            padding:20px 28px;
            margin-bottom:32px;
            box-shadow:0 2px 20px rgba(45,106,79,.08);
            border:1px solid rgba(116,198,157,.12);
            display:flex; align-items:center; justify-content:space-between;
            flex-wrap:wrap; gap:16px;
        }
        .toolbar-left { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }

        .filter-select {
            background:#f8fffe;
            border:1.5px solid rgba(116,198,157,.25);
            border-radius:10px;
            padding:10px 16px;
            font-size:.88rem;
            color:#1a1a2e;
            font-family:'Inter',sans-serif;
            outline:none;
            transition:all .25s;
            min-width:180px;
            cursor:pointer;
        }
        .filter-select:focus {
            border-color:#40916c;
            box-shadow:0 0 0 3px rgba(64,145,108,.1);
        }

        .btn-filter {
            background:linear-gradient(135deg,#40916c,#74c69d);
            color:#fff; border:none; border-radius:10px;
            padding:10px 20px; font-size:.88rem; font-weight:600;
            font-family:'Inter',sans-serif; cursor:pointer;
            display:inline-flex; align-items:center; gap:7px;
            transition:all .25s;
        }
        .btn-filter:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(64,145,108,.35); }

        .btn-clear {
            background:transparent;
            color:#6b7280; border:1.5px solid #e5e7eb;
            border-radius:10px; padding:10px 16px;
            font-size:.85rem; font-family:'Inter',sans-serif;
            cursor:pointer; transition:all .25s;
            display:inline-flex; align-items:center; gap:6px;
        }
        .btn-clear:hover { border-color:#f87171; color:#f87171; }

        .btn-add-new {
            background:linear-gradient(135deg,#2d6a4f,#40916c);
            color:#fff; border:none; border-radius:10px;
            padding:10px 22px; font-size:.88rem; font-weight:600;
            font-family:'Inter',sans-serif; cursor:pointer;
            text-decoration:none; display:inline-flex;
            align-items:center; gap:8px; transition:all .25s;
        }
        .btn-add-new:hover {
            transform:translateY(-2px);
            box-shadow:0 8px 22px rgba(45,106,79,.35);
            color:#fff; text-decoration:none;
        }

        /* Filter tag */
        .filter-tag {
            display:inline-flex; align-items:center; gap:6px;
            background:rgba(116,198,157,.12);
            border:1px solid rgba(116,198,157,.25);
            border-radius:50px; padding:4px 12px;
            font-size:.78rem; font-weight:600; color:#2d6a4f;
        }
        .filter-tag i { cursor:pointer; color:#40916c; }
        .filter-tag i:hover { color:#f87171; }

        /* ── GALLERY GRID ── */
        .gallery-grid {
            display:grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap:24px;
            margin-bottom:48px;
        }

        .gallery-card {
            background:#fff;
            border-radius:18px;
            overflow:hidden;
            box-shadow:0 4px 20px rgba(45,106,79,.07);
            border:1px solid rgba(116,198,157,.1);
            transition:all .35s cubic-bezier(.4,0,.2,1);
            cursor:pointer;
        }
        .gallery-card:hover {
            transform:translateY(-6px);
            box-shadow:0 16px 48px rgba(45,106,79,.15);
            border-color:rgba(116,198,157,.3);
        }

        /* Image wrapper */
        .gallery-card .img-wrap {
            position:relative; overflow:hidden; height:220px;
        }
        .gallery-card .img-wrap img {
            width:100%; height:100%; object-fit:cover;
            transition:transform .5s ease;
        }
        .gallery-card:hover .img-wrap img { transform:scale(1.06); }

        /* Placeholder when no image */
        .img-placeholder {
            width:100%; height:220px;
            background:linear-gradient(135deg,#e8f5e9,#c8e6c9);
            display:flex; flex-direction:column;
            align-items:center; justify-content:center; gap:8px;
        }
        .img-placeholder i { font-size:2.5rem; color:#40916c; opacity:.4; }
        .img-placeholder span { font-size:.78rem; color:#6b7280; }

        /* Hover overlay */
        .gallery-card .img-overlay {
            position:absolute; inset:0;
            background:linear-gradient(to top, rgba(13,27,42,.88) 0%, rgba(13,27,42,.1) 55%, transparent 100%);
            opacity:0; transition:opacity .35s;
            display:flex; flex-direction:column;
            justify-content:flex-end; padding:20px;
        }
        .gallery-card:hover .img-overlay { opacity:1; }
        .img-overlay .ov-title {
            font-family:'Playfair Display',serif;
            font-size:1.05rem; font-weight:700; color:#fff; margin-bottom:8px;
        }
        .img-overlay .ov-row {
            display:flex; align-items:center; justify-content:space-between;
        }

        /* Confidence badge */
        .conf-badge {
            display:inline-flex; align-items:center; gap:5px;
            background:rgba(116,198,157,.2);
            border:1px solid rgba(116,198,157,.35);
            border-radius:50px; padding:3px 10px;
            font-size:.75rem; font-weight:600; color:#74c69d;
            backdrop-filter:blur(8px);
        }
        .conf-badge.high { background:rgba(116,198,157,.2); color:#74c69d; border-color:rgba(116,198,157,.35); }
        .conf-badge.mid  { background:rgba(244,162,97,.2);  color:#f4a261; border-color:rgba(244,162,97,.35); }
        .conf-badge.low  { background:rgba(248,113,113,.2); color:#f87171; border-color:rgba(248,113,113,.35); }

        .ov-date { font-size:.72rem; color:rgba(255,255,255,.5); }

        /* Card footer */
        .gallery-card .card-foot {
            padding:14px 18px;
            display:flex; align-items:center; justify-content:space-between;
            border-top:1px solid rgba(116,198,157,.08);
        }
        .card-foot .species-name {
            font-size:.88rem; font-weight:600; color:#1a1a2e;
            white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
            max-width:160px;
        }
        .card-foot .foot-date { font-size:.75rem; color:#9ca3af; }

        /* ── EMPTY STATE ── */
        .empty-state {
            text-align:center; padding:80px 20px;
            background:#fff; border-radius:24px;
            border:1px dashed rgba(116,198,157,.3);
            box-shadow:0 2px 20px rgba(45,106,79,.06);
        }
        .empty-state .empty-icon {
            width:90px; height:90px; border-radius:50%;
            background:linear-gradient(135deg,rgba(116,198,157,.15),rgba(64,145,108,.1));
            border:2px solid rgba(116,198,157,.2);
            display:flex; align-items:center; justify-content:center;
            margin:0 auto 24px; font-size:2rem; color:#40916c;
        }
        .empty-state h3 {
            font-family:'Playfair Display',serif;
            font-size:1.5rem; font-weight:700; color:#1a1a2e; margin-bottom:10px;
        }
        .empty-state p { color:#6b7280; font-size:.9rem; margin-bottom:28px; max-width:400px; margin-inline:auto; }

        /* ── PAGINATION ── */
        .pagination-wrap { display:flex; justify-content:center; margin-bottom:20px; }
        .pag-info { text-align:center; font-size:.82rem; color:#9ca3af; margin-bottom:40px; }

        .custom-pagination { display:flex; align-items:center; gap:6px; }
        .page-btn {
            width:40px; height:40px; border-radius:10px;
            display:flex; align-items:center; justify-content:center;
            border:1.5px solid rgba(116,198,157,.2);
            background:#fff; color:#1a1a2e; font-size:.88rem; font-weight:500;
            text-decoration:none; transition:all .25s; cursor:pointer;
        }
        .page-btn:hover { border-color:#40916c; color:#40916c; text-decoration:none; }
        .page-btn.active {
            background:linear-gradient(135deg,#40916c,#74c69d);
            border-color:transparent; color:#fff;
            box-shadow:0 4px 14px rgba(64,145,108,.35);
        }
        .page-btn.disabled { opacity:.35; pointer-events:none; }

        /* ── STATS SECTION ── */
        .stats-section { margin-top:16px; margin-bottom:60px; }
        .stats-header {
            display:flex; align-items:center; gap:12px; margin-bottom:28px;
        }
        .stats-header h2 {
            font-family:'Playfair Display',serif;
            font-size:1.5rem; font-weight:700; color:#1a1a2e; margin:0;
        }
        .stats-header .section-icon {
            width:44px; height:44px; border-radius:12px;
            background:linear-gradient(135deg,#40916c,#74c69d);
            display:flex; align-items:center; justify-content:center;
            font-size:.95rem; color:#fff; flex-shrink:0;
        }

        .stat-card {
            background:#fff; border-radius:18px; padding:24px;
            border:1px solid rgba(116,198,157,.12);
            box-shadow:0 4px 20px rgba(45,106,79,.07);
            transition:all .3s; height:100%;
        }
        .stat-card:hover {
            transform:translateY(-4px);
            box-shadow:0 12px 36px rgba(45,106,79,.12);
            border-color:rgba(116,198,157,.25);
        }
        .stat-card .sp-name {
            font-weight:700; font-size:1rem; color:#1a1a2e; margin-bottom:4px;
        }
        .stat-card .sp-sci {
            font-size:.78rem; color:#9ca3af; font-style:italic; margin-bottom:18px;
        }
        .stat-card .metrics {
            display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:18px;
        }
        .metric-box {
            background:linear-gradient(135deg,#f0faf4,#e8f5ec);
            border-radius:10px; padding:12px;
            text-align:center; border:1px solid rgba(116,198,157,.1);
        }
        .metric-box.accent {
            background:linear-gradient(135deg,#e8f4fc,#d1eaf7);
            border-color:rgba(72,202,228,.15);
        }
        .metric-val { font-size:1.4rem; font-weight:800; color:#2d6a4f; line-height:1; }
        .metric-val.blue { color:#0077b6; }
        .metric-lbl { font-size:.7rem; color:#6b7280; margin-top:4px; text-transform:uppercase; letter-spacing:.4px; }
        .btn-learn {
            width:100%; background:transparent;
            border:1.5px solid rgba(116,198,157,.3);
            border-radius:10px; padding:9px;
            color:#40916c; font-size:.83rem; font-weight:600;
            font-family:'Inter',sans-serif; cursor:pointer;
            text-decoration:none; display:flex; align-items:center;
            justify-content:center; gap:6px; transition:all .25s;
        }
        .btn-learn:hover {
            background:linear-gradient(135deg,#40916c,#74c69d);
            border-color:transparent; color:#fff; text-decoration:none;
        }

        /* ── FOOTER (matches index) ── */
        .site-footer {
            background:#0d1b2a;
            color:rgba(255,255,255,.7);
            padding:60px 0 32px;
            border-top:1px solid rgba(255,255,255,.05);
        }
        .footer-brand .brand-name { font-size:1.4rem; }
        .footer-desc { color:rgba(255,255,255,.4); font-size:.85rem; margin-top:8px; max-width:280px; }
        .footer-links h6 {
            color:rgba(255,255,255,.6); font-size:.75rem; text-transform:uppercase;
            letter-spacing:1px; margin-bottom:16px; font-weight:700;
        }
        .footer-links a {
            display:block; color:rgba(255,255,255,.4); text-decoration:none;
            font-size:.88rem; margin-bottom:8px; transition:all .3s;
        }
        .footer-links a:hover { color:#74c69d; transform:translateX(4px); }
        .footer-bottom {
            border-top:1px solid rgba(255,255,255,.06);
            padding-top:24px; margin-top:40px;
            font-size:.82rem; color:rgba(255,255,255,.25);
        }

        /* Delete button show on hover */
        .gallery-card:hover .gallery-del-btn { opacity:1 !important; }
        .gallery-del-btn:hover {
            background:rgba(248,113,113,.25) !important;
            border-color:#f87171 !important;
            transform:scale(1.1);
        }

        /* scrollbar */
        ::-webkit-scrollbar { width:6px; }
        ::-webkit-scrollbar-track { background:#f0f7f4; }
        ::-webkit-scrollbar-thumb { background:#40916c; border-radius:3px; }

        @media(max-width:768px){
            .toolbar { flex-direction:column; align-items:stretch; }
            .toolbar-left { justify-content:stretch; }
            .filter-select { width:100%; }
            .gallery-grid { grid-template-columns: repeat(auto-fill, minmax(240px,1fr)); gap:16px; }
        }
        @media(max-width:480px){
            .gallery-grid { grid-template-columns:1fr; }
        }
    </style>
</head>
<body>

<!-- ══════════════ NAVBAR ══════════════ -->
<nav class="navbar navbar-expand-lg fixed-top" id="mainNav">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.html">
            <div class="logo-mk me-2">MK</div>
            <span class="brand-name">finder</span>
        </a>
        <button class="navbar-toggler border-0" type="button"
                data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center gap-1">
                <li class="nav-item">
                    <a class="nav-link" href="index.html">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="gallery.php">
                        <i class="fas fa-images me-1"></i>Gallery
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="species.php">
                        <i class="fas fa-feather me-1"></i>Species
                    </a>
                </li>
                <li class="nav-item">
                        <a class="nav-link" href="community.php">
                            <i class="fas fa-globe me-1"></i>Bird Explorer
                        </a>
                    </li>
                <!-- Logged-in user name shown here -->
                <li class="nav-item ms-2">
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="admin.php" class="nav-link" style="color:#74c69d; font-size:.85rem;">
                        <i class="fas fa-user-circle me-1"></i>
                        <?php echo htmlspecialchars($currentUserName); ?>
                    </a>
                    <?php else: ?>
                    <span class="nav-link" style="color:#74c69d; font-size:.85rem; cursor:default;">
                        <i class="fas fa-user-circle me-1"></i>
                        <?php echo htmlspecialchars($currentUserName); ?>
                    </span>
                    <?php endif; ?>
                </li>
                <li class="nav-item ms-1">
                    <a class="nav-link" href="#"
                       onclick="handleLogout();return false;"
                       style="color:#f87171;">
                        <i class="fas fa-sign-out-alt me-1"></i>Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- ══════════════ HERO ══════════════ -->
<section class="gallery-hero">
    <div class="hero-dots">
        <div class="hdot"></div><div class="hdot"></div>
        <div class="hdot"></div><div class="hdot"></div>
    </div>
    <div class="container" style="position:relative;z-index:2;">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <div class="hero-badge">
                    <i class="fas fa-user-circle"></i>My Personal Gallery
                </div>
                <h1>My Bird <span>Identification</span> History</h1>
                <p>All bird photos you've uploaded and identified — filter by species, explore your results. Welcome back, <strong style="color:#74c69d;"><?php echo htmlspecialchars($currentUserName); ?></strong>!</p>
            </div>
            <div class="col-lg-5">
                <div class="d-flex flex-wrap gap-3 justify-content-lg-end">
                    <div class="hero-pill">
                        <div class="pill-ico"><i class="fas fa-camera"></i></div>
                        <div>
                            <div class="pill-val"><?php echo $totalItems; ?></div>
                            <div class="pill-lbl">Your Identifications</div>
                        </div>
                    </div>
                    <div class="hero-pill">
                        <div class="pill-ico"><i class="fas fa-feather-alt"></i></div>
                        <div>
                            <div class="pill-val"><?php echo count($supportedSpecies); ?></div>
                            <div class="pill-lbl">Species in DB</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══════════════ MAIN CONTENT ══════════════ -->
<div class="container" style="margin-top:48px;">

    <!-- ── TOOLBAR ── -->
    <div class="toolbar">
        <div class="toolbar-left">
            <form method="GET" style="display:contents;">
                <select name="species" class="filter-select" onchange="this.form.submit()">
                    <option value="">All Species</option>
                    <?php foreach ($supportedSpecies as $sp): ?>
                        <option value="<?php echo htmlspecialchars($sp); ?>"
                            <?php echo ($speciesFilter === $sp) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($sp); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-filter">
                    <i class="fas fa-filter"></i>Filter
                </button>
            </form>
            <?php if (!empty($speciesFilter)): ?>
                <span class="filter-tag">
                    <i class="fas fa-feather-alt"></i>
                    <?php echo htmlspecialchars($speciesFilter); ?>
                    <a href="gallery.php"><i class="fas fa-times-circle"></i></a>
                </span>
            <?php endif; ?>
        </div>
        <a href="index.html" class="btn-add-new">
            <i class="fas fa-plus-circle"></i>New Identification
        </a>
    </div>

    <!-- ── GALLERY GRID ── -->
    <?php if (!empty($paginatedIdentifications)): ?>

        <div class="gallery-grid">
            <?php foreach ($paginatedIdentifications as $item):
                $imagePath  = 'uploads/' . $item['filename'];
                $hasImage   = file_exists($imagePath);
                $confidence = intval($item['confidence'] ?? 0);
                $confClass  = $confidence >= 85 ? 'high' : ($confidence >= 65 ? 'mid' : 'low');
                $dateStr    = date('M j, Y', strtotime($item['identification_time'] ?? 'now'));
            ?>
            <div class="gallery-card" id="gcard-<?php echo htmlspecialchars($item['identification_id']); ?>" style="position:relative;">
                <!-- Delete button -->
                <button onclick="deleteGalleryCard('<?php echo htmlspecialchars($item['identification_id']); ?>',this)"
                    title="Delete this record"
                    style="position:absolute;top:10px;right:10px;z-index:10;
                           width:30px;height:30px;border-radius:50%;
                           background:rgba(13,27,42,.75);backdrop-filter:blur(4px);
                           border:1px solid rgba(248,113,113,.35);color:#f87171;
                           font-size:.75rem;cursor:pointer;display:flex;align-items:center;
                           justify-content:center;transition:all .2s;opacity:0;"
                    class="gallery-del-btn">
                    <i class="fas fa-trash-alt"></i>
                </button>
                <div class="img-wrap">
                    <?php if ($hasImage): ?>
                        <img src="<?php echo htmlspecialchars($imagePath); ?>"
                             alt="<?php echo htmlspecialchars($item['species_name']); ?>"
                             loading="lazy">
                    <?php else: ?>
                        <div class="img-placeholder">
                            <i class="fas fa-feather-alt"></i>
                            <span>No image</span>
                        </div>
                    <?php endif; ?>
                    <div class="img-overlay">
                        <div class="ov-title"><?php echo htmlspecialchars($item['species_name']); ?></div>
                        <div class="ov-row">
                            <span class="conf-badge <?php echo $confClass; ?>">
                                <i class="fas fa-brain"></i>
                                <?php echo $confidence; ?>% confidence
                            </span>
                            <span class="ov-date"><?php echo $dateStr; ?></span>
                        </div>
                    </div>
                </div>
                <div class="card-foot">
                    <span class="species-name"><?php echo htmlspecialchars($item['species_name']); ?></span>
                    <span class="foot-date"><?php echo $dateStr; ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1):
            $startPage = max(1, $page - 2);
            $endPage   = min($totalPages, $page + 2);
            $spParam   = !empty($speciesFilter) ? '&species=' . urlencode($speciesFilter) : '';
        ?>
        <div class="pagination-wrap">
            <div class="custom-pagination">
                <a href="?page=<?php echo $page-1; ?><?php echo $spParam; ?>"
                   class="page-btn <?php echo $page<=1?'disabled':''; ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <a href="?page=<?php echo $i; ?><?php echo $spParam; ?>"
                       class="page-btn <?php echo $i===$page?'active':''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                <a href="?page=<?php echo $page+1; ?><?php echo $spParam; ?>"
                   class="page-btn <?php echo $page>=$totalPages?'disabled':''; ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        </div>
        <p class="pag-info">
            Showing <?php echo $offset+1; ?>–<?php echo min($offset+$itemsPerPage,$totalItems); ?>
            of <?php echo $totalItems; ?> identifications
        </p>
        <?php endif; ?>

    <?php else: ?>

        <!-- Empty state -->
        <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-images"></i></div>
            <h3>
                <?php if (!empty($speciesFilter)): ?>
                    No <?php echo htmlspecialchars($speciesFilter); ?> Found
                <?php else: ?>
                    Your Gallery is Empty
                <?php endif; ?>
            </h3>
            <?php if (!empty($speciesFilter)): ?>
                <p>No results found for <strong><?php echo htmlspecialchars($speciesFilter); ?></strong>. Try a different filter or clear it.</p>
                <div class="d-flex gap-3 justify-content-center flex-wrap">
                    <a href="gallery.php" class="btn-add-new" style="text-decoration:none;">
                        <i class="fas fa-times"></i>Clear Filter
                    </a>
                    <a href="index.html" class="btn-add-new" style="text-decoration:none;">
                        <i class="fas fa-camera"></i>Add Identification
                    </a>
                </div>
            <?php else: ?>
                <p>You haven't uploaded any bird photos yet. Start identifying birds and they'll all appear here!</p>
                <a href="index.html" class="btn-add-new" style="text-decoration:none;">
                    <i class="fas fa-camera"></i>Identify Your First Bird
                </a>
            <?php endif; ?>
        </div>

    <?php endif; ?>

    <!-- ── STATS SECTION ── -->
    <?php if (!empty($speciesStats)): ?>
    <div class="stats-section">
        <div class="stats-header">
            <div class="section-icon"><i class="fas fa-chart-bar"></i></div>
            <h2>Your Species Statistics</h2>
        </div>
        <div class="row g-4">
            <?php foreach ($speciesStats as $st): ?>
            <div class="col-lg-4 col-md-6">
                <div class="stat-card">
                    <div class="sp-name"><?php echo htmlspecialchars($st['species_name']); ?></div>
                    <div class="sp-sci">
                        <?php
                        $sciNames = [
                            'American Robin'    => 'Turdus migratorius',
                            'Blue Jay'          => 'Cyanocitta cristata',
                            'Northern Cardinal' => 'Cardinalis cardinalis',
                        ];
                        echo htmlspecialchars($sciNames[$st['species_name']] ?? '');
                        ?>
                    </div>
                    <div class="metrics">
                        <div class="metric-box">
                            <div class="metric-val"><?php echo $st['identification_count']; ?></div>
                            <div class="metric-lbl">Identifications</div>
                        </div>
                        <div class="metric-box accent">
                            <div class="metric-val blue"><?php echo round($st['avg_confidence'],1); ?>%</div>
                            <div class="metric-lbl">Avg. Confidence</div>
                        </div>
                    </div>
                    <a href="species.php?species=<?php echo urlencode($st['species_name']); ?>"
                       class="btn-learn">
                        <i class="fas fa-feather-alt"></i>View Species Info
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div><!-- /container -->

<!-- ══════════════ FOOTER ══════════════ -->
<footer class="site-footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="footer-brand d-flex align-items-center gap-2 mb-3">
                    <div class="logo-mk">MK</div>
                    <span class="brand-name">finder</span>
                </div>
                <p class="footer-desc">Advanced AI-powered bird species identification. Helping birdwatchers and researchers explore avian biodiversity.</p>
            </div>
            <div class="col-lg-2 col-6 footer-links">
                <h6>Navigation</h6>
                <a href="index.html">Home</a>
                <a href="gallery.php">Gallery</a>
                <a href="species.php">Species</a>
                <a href="community.php">Bird Explorer</a>
            </div>
            <div class="col-lg-2 col-6 footer-links">
                <h6>Account</h6>
                <a href="#" onclick="handleLogout();return false;">Logout</a>
            </div>
            <div class="col-lg-4 footer-links">
                <h6>About</h6>
                <p style="color:rgba(255,255,255,.35);font-size:.85rem;line-height:1.7;">
                    MKfinder uses cutting-edge machine learning to help you identify bird species from photos with high accuracy.
                </p>
            </div>
        </div>
        <div class="footer-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span>&copy; 2025 MKfinder. All rights reserved.</span>
            <span>Made with <i class="fas fa-heart" style="color:#f87171;"></i> for bird lovers</span>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Navbar scroll effect
    window.addEventListener('scroll', () => {
        document.getElementById('mainNav').classList.toggle('scrolled', window.scrollY > 50);
    });

    // Verify session is still valid
    fetch('auth.php?action=check')
        .then(r => r.json())
        .then(d => {
            if (!d.authenticated) window.location.href = 'index.html?login=required';
        })
        .catch(() => {});

    async function handleLogout() {
        try { await fetch('auth.php?action=logout', { method:'POST' }); } catch(e){}
        window.location.href = 'index.html';
    }

    async function deleteGalleryCard(identificationId, btn) {
        // Use MK confirm modal
        const card = document.getElementById('gcard-' + identificationId);

        // Build inline confirm
        const overlay = document.createElement('div');
        overlay.style.cssText = 'position:fixed;inset:0;z-index:99999;display:flex;align-items:center;justify-content:center;padding:20px;background:rgba(13,27,42,.82);backdrop-filter:blur(8px);';
        overlay.innerHTML = `
            <div style="background:#fff;border-radius:20px;padding:32px 28px;max-width:340px;
                        width:100%;text-align:center;box-shadow:0 24px 60px rgba(0,0,0,.2);
                        animation:mkAdminIn .22s ease;">
                <div style="width:52px;height:52px;border-radius:50%;margin:0 auto 14px;
                             background:rgba(248,113,113,.1);border:2px solid rgba(248,113,113,.25);
                             display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-trash-alt" style="color:#e63946;font-size:1.1rem;"></i>
                </div>
                <div style="font-family:'Playfair Display',serif;font-size:1.05rem;font-weight:700;
                            color:#1a1a2e;margin-bottom:8px;">Delete Record?</div>
                <div style="font-size:.83rem;color:#6b7280;line-height:1.6;margin-bottom:22px;">
                    This identification will be permanently removed from your gallery.
                </div>
                <div style="display:flex;gap:10px;justify-content:center;">
                    <button id="delYes" style="flex:1;max-width:130px;background:linear-gradient(135deg,#e63946,#f87171);
                        color:#fff;border:none;border-radius:50px;padding:10px 18px;font-size:.85rem;
                        font-weight:700;cursor:pointer;font-family:'Inter',sans-serif;">Delete</button>
                    <button id="delNo" style="flex:1;max-width:130px;background:#f0f7f4;
                        color:#2d6a4f;border:1px solid rgba(116,198,157,.3);border-radius:50px;
                        padding:10px 18px;font-size:.85rem;font-weight:600;cursor:pointer;
                        font-family:'Inter',sans-serif;">Cancel</button>
                </div>
            </div>
            <style>@keyframes mkAdminIn{from{opacity:0;transform:scale(.92)}to{opacity:1;transform:scale(1)}}</style>`;
        document.body.appendChild(overlay);

        overlay.querySelector('#delNo').onclick  = () => document.body.removeChild(overlay);
        overlay.onclick = (e) => { if(e.target===overlay) document.body.removeChild(overlay); };

        overlay.querySelector('#delYes').onclick = async () => {
            document.body.removeChild(overlay);
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            try {
                const fd = new FormData();
                fd.append('identification_id', identificationId);
                const res  = await fetch('delete_identification.php', { method:'POST', body:fd });
                const data = await res.json();
                if (data.success) {
                    card.style.transition = 'all .3s';
                    card.style.opacity    = '0';
                    card.style.transform  = 'scale(.9)';
                    setTimeout(() => {
                        card.remove();
                        // Update count in hero pill
                        const pill = document.querySelector('.pill-val');
                        if (pill) pill.textContent = Math.max(0, parseInt(pill.textContent) - 1);
                    }, 300);
                } else {
                    btn.innerHTML = '<i class="fas fa-trash-alt"></i>';
                    alert(data.message || 'Delete failed.');
                }
            } catch(e) {
                btn.innerHTML = '<i class="fas fa-trash-alt"></i>';
            }
        };
    }
</script>
</body>
</html>
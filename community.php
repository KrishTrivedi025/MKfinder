<?php
require_once 'config.php';
require_once 'database.php';
initSession();
$isLoggedIn = isLoggedIn();
$userId     = $_SESSION['user_id'] ?? null;
$isAdmin    = isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bird Explorer — MKfinder</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
<link href="styles.css" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;}
body{background:#f0f7f4;font-family:'Inter',sans-serif;}

.navbar{background:rgba(13,27,42,.96)!important;backdrop-filter:blur(20px);border-bottom:1px solid rgba(116,198,157,.15);padding:14px 0;}
.navbar.scrolled{box-shadow:0 4px 30px rgba(0,0,0,.3);}

.page-hero{background:linear-gradient(135deg,#0d1b2a 0%,#1b4332 45%,#2d6a4f 100%);position:relative;overflow:hidden;padding:110px 0 0;}
.page-hero::before{content:'';position:absolute;inset:0;pointer-events:none;background:radial-gradient(circle at 20% 55%,rgba(116,198,157,.18) 0%,transparent 50%),radial-gradient(circle at 80% 20%,rgba(72,202,228,.1) 0%,transparent 40%);}
.hero-dots{position:absolute;inset:0;pointer-events:none;}
.hdot{position:absolute;border-radius:50%;background:rgba(116,198,157,.25);animation:hdotFloat 8s ease-in-out infinite;}
.hdot:nth-child(1){width:6px;height:6px;top:25%;left:7%;animation-delay:0s;}
.hdot:nth-child(2){width:4px;height:4px;top:62%;left:16%;animation-delay:2s;}
.hdot:nth-child(3){width:8px;height:8px;top:38%;right:9%;animation-delay:1s;}
.hdot:nth-child(4){width:5px;height:5px;top:70%;right:20%;animation-delay:3s;}
@keyframes hdotFloat{0%,100%{transform:translateY(0) scale(1);opacity:.3;}50%{transform:translateY(-16px) scale(1.3);opacity:.7;}}
.hero-wave{display:block;width:100%;margin-top:-2px;line-height:0;}
.hero-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(116,198,157,.15);border:1px solid rgba(116,198,157,.3);color:#74c69d;padding:6px 16px;border-radius:50px;font-size:.78rem;font-weight:600;letter-spacing:.5px;text-transform:uppercase;margin-bottom:16px;}
.hero-title{font-family:'Playfair Display',serif;font-size:clamp(2rem,4.5vw,3rem);color:#fff;font-weight:800;line-height:1.15;margin-bottom:12px;}
.hero-title em{color:#74c69d;font-style:normal;}
.hero-sub{font-size:1rem;color:rgba(255,255,255,.55);max-width:500px;line-height:1.7;}

/* FEED GRID */
.feed-section{padding:48px 0 72px;}
.feed-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:22px;}
@media(max-width:1199px){.feed-grid{grid-template-columns:repeat(3,1fr);}}
@media(max-width:767px){.feed-grid{grid-template-columns:repeat(2,1fr);gap:14px;}}
@media(max-width:479px){.feed-grid{grid-template-columns:1fr;}}

/* FEED CARD */
.feed-card{background:#fff;border-radius:18px;overflow:hidden;border:1px solid rgba(116,198,157,.12);box-shadow:0 4px 20px rgba(45,106,79,.07);transition:transform .28s cubic-bezier(.4,0,.2,1),box-shadow .28s;cursor:pointer;display:flex;flex-direction:column;position:relative;}
.feed-card:hover{transform:translateY(-6px);box-shadow:0 18px 48px rgba(45,106,79,.16);border-color:rgba(116,198,157,.3);}

.card-img-wrap{position:relative;overflow:hidden;height:200px;background:linear-gradient(135deg,#1b4332,#2d6a4f);}
.card-img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .45s;}
.feed-card:hover .card-img{transform:scale(1.06);}
.card-img-placeholder{width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:3rem;color:rgba(116,198,157,.35);}
.card-species-badge{position:absolute;bottom:10px;left:10px;background:rgba(13,27,42,.82);backdrop-filter:blur(8px);color:#fff;padding:5px 12px;border-radius:50px;font-size:.75rem;font-weight:700;display:flex;align-items:center;gap:5px;border:1px solid rgba(116,198,157,.2);}
.card-conf-badge{position:absolute;top:10px;right:10px;background:linear-gradient(135deg,#40916c,#74c69d);color:#fff;padding:4px 10px;border-radius:50px;font-size:.7rem;font-weight:700;}
.card-conf-badge.unknown{background:linear-gradient(135deg,#f4a261,#fb923c);}

.card-body-inner{padding:13px 15px 14px;flex:1;display:flex;flex-direction:column;gap:8px;}
.card-user-row{display:flex;align-items:center;gap:9px;}
.card-avatar{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#40916c,#74c69d);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.72rem;font-weight:700;flex-shrink:0;border:2px solid rgba(116,198,157,.25);}
.card-user-name{font-size:.83rem;font-weight:700;color:#1a1a2e;line-height:1.2;}
.card-time{font-size:.7rem;color:#9ca3af;}
.card-footer-row{display:flex;align-items:center;gap:12px;margin-top:auto;padding-top:10px;border-top:1px solid rgba(116,198,157,.08);}
.card-stat{display:flex;align-items:center;gap:5px;font-size:.75rem;color:#9ca3af;}
.card-stat i{font-size:.7rem;color:#40916c;}
.card-view-hint{margin-left:auto;font-size:.7rem;color:rgba(116,198,157,.5);display:flex;align-items:center;gap:4px;transition:color .2s;}
.feed-card:hover .card-view-hint{color:#74c69d;}

/* Admin remove button on card */
.card-admin-del{position:absolute;top:10px;left:10px;width:28px;height:28px;border-radius:50%;background:rgba(13,27,42,.75);backdrop-filter:blur(4px);border:1px solid rgba(248,113,113,.4);color:#f87171;font-size:.7rem;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .2s;opacity:0;z-index:5;}
.feed-card:hover .card-admin-del{opacity:1;}
.card-admin-del:hover{background:rgba(248,113,113,.25);}

/* FEED EMPTY / LOADER */
.feed-empty{text-align:center;padding:80px 20px;background:#fff;border-radius:20px;border:1px solid rgba(116,198,157,.1);grid-column:1/-1;}
.feed-empty-icon{width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,rgba(64,145,108,.1),rgba(116,198,157,.1));border:2px solid rgba(116,198,157,.2);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:1.8rem;color:#40916c;}
.feed-empty h4{font-family:'Playfair Display',serif;font-size:1.4rem;font-weight:700;color:#1a1a2e;margin-bottom:8px;}
.feed-empty p{color:#9ca3af;font-size:.9rem;}
.loader{text-align:center;padding:32px;color:#9ca3af;font-size:.875rem;grid-column:1/-1;}
.loader i{display:block;font-size:1.5rem;margin-bottom:8px;color:#40916c;}
.feed-end-msg{text-align:center;padding:24px;color:#9ca3af;font-size:.85rem;grid-column:1/-1;}

/* DETAIL OVERLAY */
#detailOverlay{position:fixed;inset:0;z-index:9999;display:none;align-items:center;justify-content:center;padding:20px;background:rgba(13,27,42,.85);backdrop-filter:blur(10px);}
#detailOverlay.active{display:flex;}

.detail-modal{background:#fff;border-radius:24px;width:100%;max-width:1020px;max-height:92vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 40px 100px rgba(0,0,0,.45);animation:modalSlide .3s cubic-bezier(.4,0,.2,1);}
@keyframes modalSlide{from{opacity:0;transform:translateY(28px) scale(.97);}to{opacity:1;transform:translateY(0) scale(1);}}

.detail-close{position:absolute;top:16px;right:16px;width:38px;height:38px;border-radius:50%;background:rgba(255,255,255,.12);border:none;color:rgba(255,255,255,.7);font-size:1rem;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .2s;z-index:10;}
.detail-close:hover{background:rgba(248,113,113,.25);color:#f87171;}

.detail-body{display:flex;flex:1;overflow:hidden;}
@media(max-width:700px){.detail-body{flex-direction:column;}}

/* LEFT photo */
.detail-left{width:52%;flex-shrink:0;background:linear-gradient(135deg,#0d1b2a,#1b4332);position:relative;display:flex;align-items:center;justify-content:center;overflow:hidden;}
@media(max-width:700px){.detail-left{width:100%;height:260px;}}
.detail-photo{width:100%;height:100%;object-fit:cover;display:block;}
.detail-photo-placeholder{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;width:100%;height:100%;min-height:320px;}
.detail-photo-placeholder i{font-size:4rem;color:rgba(116,198,157,.3);}
.detail-photo-placeholder span{font-size:.85rem;color:rgba(116,198,157,.4);}
.detail-species-overlay{position:absolute;bottom:0;left:0;right:0;padding:20px 22px 18px;background:linear-gradient(to top,rgba(13,27,42,.92) 0%,transparent 100%);}
.detail-species-name{font-family:'Playfair Display',serif;font-size:1.35rem;font-weight:800;color:#fff;margin-bottom:4px;}
.detail-conf-pill{display:inline-flex;align-items:center;gap:6px;background:linear-gradient(135deg,#40916c,#74c69d);color:#fff;padding:4px 12px;border-radius:50px;font-size:.75rem;font-weight:700;}
.detail-conf-pill.unknown{background:linear-gradient(135deg,#f4a261,#fb923c);}

/* RIGHT info+comments */
.detail-right{flex:1;display:flex;flex-direction:column;overflow:hidden;min-width:0;}
.detail-user-header{padding:22px 24px 16px;border-bottom:1px solid rgba(116,198,157,.1);flex-shrink:0;}
.detail-avatar{width:46px;height:46px;border-radius:50%;background:linear-gradient(135deg,#40916c,#74c69d);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.92rem;font-weight:700;flex-shrink:0;border:2px solid rgba(116,198,157,.3);}
.detail-uname{font-size:1rem;font-weight:700;color:#1a1a2e;}
.detail-meta-row{display:flex;flex-wrap:wrap;gap:10px 16px;margin-top:10px;}
.detail-meta-item{display:flex;align-items:center;gap:6px;font-size:.78rem;color:#6b7280;}
.detail-meta-item i{color:#40916c;font-size:.72rem;width:13px;text-align:center;}

.detail-like-bar{padding:14px 24px;border-bottom:1px solid rgba(116,198,157,.1);flex-shrink:0;display:flex;align-items:center;gap:14px;}
.btn-like-detail{display:flex;align-items:center;gap:8px;background:none;border:1.5px solid #e5e7eb;border-radius:50px;padding:8px 18px;font-size:.85rem;font-weight:600;color:#6b7280;cursor:pointer;transition:all .22s;}
.btn-like-detail:hover,.btn-like-detail.liked{color:#e63946;border-color:#f87171;background:rgba(248,113,113,.06);}
.btn-like-detail.liked i{color:#e63946;}
.like-detail-count{font-size:.82rem;color:#9ca3af;}
.cmt-label{font-size:.78rem;color:#9ca3af;margin-left:auto;display:flex;align-items:center;gap:5px;}
.cmt-label i{color:#40916c;}
.btn-admin-del-post{background:rgba(248,113,113,.1);color:#f87171;border:1px solid rgba(248,113,113,.25);border-radius:50px;padding:6px 14px;font-size:.75rem;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:5px;transition:all .2s;}
.btn-admin-del-post:hover{background:rgba(248,113,113,.2);}

.detail-comments-area{flex:1;overflow-y:auto;padding:16px 24px 8px;}
.detail-comments-area::-webkit-scrollbar{width:4px;}
.detail-comments-area::-webkit-scrollbar-thumb{background:#74c69d;border-radius:2px;}

.cmt-item{display:flex;gap:10px;margin-bottom:14px;}
.cmt-av{width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,#40916c,#74c69d);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.62rem;font-weight:700;flex-shrink:0;margin-top:2px;}
.cmt-bubble{background:#f8fffe;border-radius:12px;padding:9px 13px;flex:1;font-size:.82rem;color:#1a1a2e;border:1px solid rgba(116,198,157,.12);}
.cmt-uname{font-weight:700;font-size:.76rem;color:#2d6a4f;margin-bottom:3px;}
.cmt-footer{font-size:.68rem;color:#9ca3af;margin-top:4px;display:flex;align-items:center;gap:10px;}
.btn-del-cmt{background:none;border:none;color:#f87171;font-size:.68rem;cursor:pointer;padding:0;display:flex;align-items:center;gap:3px;}
.cmt-empty{text-align:center;padding:28px 0;color:#9ca3af;font-size:.82rem;}
.cmt-empty i{display:block;font-size:1.6rem;margin-bottom:8px;color:rgba(116,198,157,.35);}

.detail-comment-input{padding:12px 24px 18px;border-top:1px solid rgba(116,198,157,.1);flex-shrink:0;}
.cmt-input-row{display:flex;gap:8px;}
.cmt-input{flex:1;border:1.5px solid rgba(116,198,157,.25);border-radius:50px;padding:9px 16px;font-size:.83rem;font-family:Inter,sans-serif;outline:none;background:#f8fffe;color:#1a1a2e;}
.cmt-input:focus{border-color:#40916c;box-shadow:0 0 0 3px rgba(64,145,108,.1);}
.btn-send-cmt{background:linear-gradient(135deg,#40916c,#74c69d);color:#fff;border:none;border-radius:50px;padding:9px 18px;font-size:.82rem;font-weight:600;cursor:pointer;transition:transform .2s;}
.btn-send-cmt:hover{transform:scale(1.05);}
.cmt-login-prompt{text-align:center;font-size:.8rem;color:#9ca3af;padding:4px 0;}
.cmt-login-prompt a{color:#40916c;font-weight:600;text-decoration:none;}

/* FILTER TOOLBAR */
.feed-toolbar{background:#fff;border-radius:16px;padding:16px 24px;margin-bottom:28px;
    box-shadow:0 2px 16px rgba(45,106,79,.07);border:1px solid rgba(116,198,157,.12);
    display:flex;align-items:center;gap:12px;flex-wrap:wrap;}
.filter-btn{display:inline-flex;align-items:center;gap:7px;
    background:transparent;border:1.5px solid #e5e7eb;border-radius:50px;
    padding:9px 18px;font-size:.83rem;font-weight:600;color:#6b7280;
    cursor:pointer;font-family:'Inter',sans-serif;transition:all .22s;}
.filter-btn:hover{border-color:#74c69d;color:#2d6a4f;background:rgba(116,198,157,.06);}
.filter-btn.active{background:linear-gradient(135deg,#40916c,#74c69d);
    color:#fff;border-color:transparent;}
.filter-btn i{font-size:.75rem;}

/* FOOTER */
.site-footer{background:#0d1b2a;padding:60px 0 0;}
.footer-brand .brand-name{font-size:1.4rem;}
.footer-desc{color:rgba(255,255,255,.4);font-size:.85rem;margin-top:8px;max-width:280px;}
.footer-links h6{color:rgba(255,255,255,.6);font-size:.75rem;text-transform:uppercase;letter-spacing:1px;margin-bottom:16px;font-weight:700;}
.footer-links a{display:block;color:rgba(255,255,255,.4);text-decoration:none;font-size:.88rem;margin-bottom:8px;transition:all .3s;}
.footer-links a:hover{color:#74c69d;transform:translateX(4px);}
.footer-bottom{border-top:1px solid rgba(255,255,255,.06);padding:24px 0;margin-top:40px;font-size:.82rem;color:rgba(255,255,255,.25);}
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg fixed-top" id="mainNav">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.html">
            <div class="logo-mk me-2">MK</div>
            <span class="brand-name">finder</span>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center gap-1">
                <li class="nav-item"><a class="nav-link" href="index.html">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="gallery.php"><i class="fas fa-images me-1"></i>Gallery</a></li>
                <li class="nav-item"><a class="nav-link" href="species.php"><i class="fas fa-feather me-1"></i>Species</a></li>
                <li class="nav-item"><a class="nav-link active" href="community.php"><i class="fas fa-globe me-1"></i>Bird Explorer</a></li>
                <li class="nav-item guest-nav"><a class="nav-link" href="login.html">Login</a></li>
                <li class="nav-item guest-nav ms-2">
                    <a class="btn-hero-primary px-4 py-2" href="signup.html" style="font-size:.88rem;text-decoration:none;">
                        <i class="fas fa-user-plus me-1"></i>Sign Up
                    </a>
                </li>
                <li class="nav-item user-nav" style="display:none;">
                    <a class="nav-link" id="userNavLink" href="index.html" style="color:#74c69d;font-size:.85rem;">
                        <i class="fas fa-user-circle me-1"></i><span id="userDisplayName"></span>
                    </a>
                </li>
                <li class="nav-item user-nav ms-2" style="display:none;">
                    <a class="nav-link" href="#" onclick="handleLogout();return false;" style="color:#f87171;">
                        <i class="fas fa-sign-out-alt me-1"></i>Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- HERO -->
<section class="page-hero">
    <div class="hero-dots">
        <div class="hdot"></div><div class="hdot"></div>
        <div class="hdot"></div><div class="hdot"></div>
    </div>
    <div class="container position-relative" style="z-index:2;">
        <div class="text-center pb-5">
            <div class="hero-badge"><i class="fas fa-globe"></i> Live Community Feed</div>
            <h1 class="hero-title">Bird <em>Explorer</em></h1>
            <p class="hero-sub mx-auto">Discover what bird enthusiasts around the world are finding right now. Share your sightings, like amazing photos and connect with fellow birders.</p>
        </div>
    </div>
    <svg class="hero-wave" viewBox="0 0 1440 60" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M0,40 C360,80 1080,0 1440,40 L1440,60 L0,60 Z" fill="#f0f7f4"/>
    </svg>
</section>

<!-- FEED GRID -->
<section class="feed-section">
    <div class="container">
        <!-- Filter Toolbar -->
        <div class="feed-toolbar">
            <button class="filter-btn active" id="fBtn-latest" onclick="setFilter('latest')">
                <i class="fas fa-clock"></i> Latest
            </button>
            <button class="filter-btn" id="fBtn-liked" onclick="setFilter('liked')">
                <i class="fas fa-heart"></i> Most Liked
            </button>
            <button class="filter-btn" id="fBtn-commented" onclick="setFilter('commented')">
                <i class="fas fa-comment"></i> Most Commented
            </button>
        </div>
        <div class="feed-grid" id="feedContainer"></div>
        <div class="feed-grid" style="margin-top:0;">
            <div id="feedLoader" class="loader" style="display:none;">
                <i class="fas fa-spinner fa-spin"></i>Loading more sightings...
            </div>
            <div id="feedEnd" class="feed-end-msg" style="display:none;">
                <i class="fas fa-check-circle" style="color:#40916c;margin-right:6px;"></i>You've seen all sightings!
            </div>
        </div>
    </div>
</section>

<!-- DETAIL OVERLAY -->
<div id="detailOverlay" onclick="handleOverlayClick(event)">
    <div class="detail-modal" id="detailModal">
        <button class="detail-close" onclick="closeDetail()"><i class="fas fa-times"></i></button>
        <div class="detail-body">
            <!-- LEFT: photo -->
            <div class="detail-left" id="detailLeft">
                <div class="detail-photo-placeholder" id="detailPhotoWrap">
                    <i class="fas fa-dove"></i>
                    <span>No photo</span>
                </div>
                <div class="detail-species-overlay">
                    <div class="detail-species-name" id="detailSpeciesName"></div>
                    <span class="detail-conf-pill" id="detailConfPill"></span>
                </div>
            </div>
            <!-- RIGHT: info + comments -->
            <div class="detail-right">
                <div class="detail-user-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="detail-avatar" id="detailAvatar"></div>
                        <div><div class="detail-uname" id="detailUname"></div></div>
                    </div>
                    <div class="detail-meta-row" id="detailMetaRow"></div>
                </div>
                <div class="detail-like-bar">
                    <button class="btn-like-detail" id="detailLikeBtn" onclick="detailToggleLike()">
                        <i class="far fa-heart"></i>
                        <span id="detailLikeCount">0</span>
                    </button>
                    <span class="like-detail-count">likes</span>
                    <span class="cmt-label" id="detailCmtLabel"><i class="far fa-comment"></i> 0 comments</span>
                    <?php if ($isAdmin): ?>
                    <button class="btn-admin-del-post" id="detailAdminDelBtn" onclick="adminDeleteCurrentPost()">
                        <i class="fas fa-trash-alt"></i> Remove Post
                    </button>
                    <?php endif; ?>
                </div>
                <div class="detail-comments-area" id="detailCommentsArea">
                    <div class="cmt-empty"><i class="far fa-comment-dots"></i>No comments yet</div>
                </div>
                <div class="detail-comment-input">
                    <?php if ($isLoggedIn): ?>
                    <div class="cmt-input-row">
                        <input class="cmt-input" id="detailCmtInput" placeholder="Write a comment…" maxlength="500" onkeydown="if(event.key==='Enter')detailSubmitComment()">
                        <button class="btn-send-cmt" onclick="detailSubmitComment()"><i class="fas fa-paper-plane"></i></button>
                    </div>
                    <?php else: ?>
                    <div class="cmt-login-prompt"><a href="login.html">Login</a> to join the conversation</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- FOOTER -->
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
                <a href="login.html">Login</a>
                <a href="signup.html">Sign Up</a>
            </div>
            <div class="col-lg-4 footer-links">
                <h6>About</h6>
                <p style="color:rgba(255,255,255,.35);font-size:.85rem;line-height:1.7;">MKfinder uses cutting-edge machine learning to help you identify bird species from photos with high accuracy.</p>
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
const IS_LOGGED_IN = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
const CURRENT_UID  = <?php echo json_encode($userId); ?>;
const IS_ADMIN     = <?php echo $isAdmin ? 'true' : 'false'; ?>;
let offset = 0, loading = false, ended = false;
let detailCurrentId = null;
let currentSort = 'latest';

function setFilter(sort) {
    if (currentSort === sort) return;
    currentSort = sort;
    // Update button styles
    ['latest','liked','commented'].forEach(s => {
        const btn = document.getElementById('fBtn-' + s);
        if (btn) btn.classList.toggle('active', s === sort);
    });
    // Reset and reload feed
    offset = 0;
    ended  = false;
    document.getElementById('feedContainer').innerHTML = '';
    document.getElementById('feedEnd').style.display = 'none';
    loadFeed();
}

window.addEventListener('scroll', () => {
    document.getElementById('mainNav').classList.toggle('scrolled', window.scrollY > 50);
});

async function checkAndUpdateNav() {
    try {
        const d = await (await fetch('auth.php?action=check')).json();
        if (d.success && d.authenticated) {
            document.querySelectorAll('.guest-nav').forEach(e => e.style.display='none');
            document.querySelectorAll('.user-nav').forEach(e  => e.style.display='block');
            const s = document.getElementById('userDisplayName');
            if (s && d.user) s.textContent = [d.user.first_name, d.user.last_name].filter(Boolean).join(' ') || d.user.email || '';
            const nl = document.getElementById('userNavLink');
            if (nl && d.is_admin) nl.href = 'admin.php';
        }
    } catch(e) {}
}
document.addEventListener('DOMContentLoaded', checkAndUpdateNav);

async function handleLogout() {
    try { await fetch('auth.php?action=logout', {method:'POST'}); } catch(e) {}
    window.location.href = 'index.html';
}

function timeAgo(dt) {
    const s = Math.floor((Date.now() - new Date(dt).getTime()) / 1000);
    if (s < 60) return 'just now';
    if (s < 3600) return Math.floor(s/60) + 'm ago';
    if (s < 86400) return Math.floor(s/3600) + 'h ago';
    return Math.floor(s/86400) + 'd ago';
}

function initials(f, l) { return ((f||'U')[0] + (l||'')[0]).toUpperCase(); }

function buildCard(d) {
    const imgPath   = d.filename ? 'uploads/' + d.filename : '';
    const isUnknown = !d.species_name || d.species_name === 'Unknown';
    const species   = isUnknown ? 'Unknown Bird' : d.species_name;
    const conf      = d.confidence > 0 ? parseFloat(d.confidence).toFixed(1) + '%' : '';
    const name      = ((d.first_name||'') + ' ' + (d.last_name||'')).trim() || 'Anonymous';

    const adminBtn  = IS_ADMIN
        ? `<button class="card-admin-del" onclick="event.stopPropagation();adminDeleteCard('${d.identification_id}',this)" title="Remove post"><i class="fas fa-trash-alt"></i></button>`
        : '';

    return `<div class="feed-card" id="card-${d.identification_id}" onclick="openDetail(${JSON.stringify(d).replace(/"/g,'&quot;')})">
        ${adminBtn}
        <div class="card-img-wrap">
            ${imgPath
                ? `<img class="card-img" src="${imgPath}" alt="${species}" loading="lazy" onerror="this.parentElement.innerHTML='<div class=card-img-placeholder><i class=fas fa-dove></i></div>'">`
                : `<div class="card-img-placeholder"><i class="fas fa-dove"></i></div>`}
            <div class="card-species-badge"><i class="fas fa-${isUnknown?'question-circle':'feather-alt'}"></i>${species}</div>
            ${conf ? `<div class="card-conf-badge ${isUnknown?'unknown':''}">${conf}</div>` : ''}
        </div>
        <div class="card-body-inner">
            <div class="card-user-row">
                <div class="card-avatar">${initials(d.first_name, d.last_name)}</div>
                <div>
                    <div class="card-user-name">${name}</div>
                    <div class="card-time">${timeAgo(d.identification_time)}</div>
                </div>
            </div>
            <div class="card-footer-row">
                <span class="card-stat"><i class="fas fa-heart"></i>${d.likes}</span>
                <span class="card-stat"><i class="fas fa-comment"></i>${d.comments}</span>
                <span class="card-view-hint"><i class="fas fa-expand-alt"></i> View</span>
            </div>
        </div>
    </div>`;
}

async function loadFeed() {
    if (loading || ended) return;
    loading = true;
    document.getElementById('feedLoader').style.display = 'block';
    try {
        const res  = await fetch(`community_api.php?action=feed&offset=${offset}&sort=${currentSort}`);
        const data = await res.json();
        document.getElementById('feedLoader').style.display = 'none';
        const container = document.getElementById('feedContainer');
        if (!data.data || data.data.length === 0) {
            if (offset === 0) {
                container.innerHTML = `<div class="feed-empty">
                    <div class="feed-empty-icon"><i class="fas fa-binoculars"></i></div>
                    <h4>No Sightings Yet</h4>
                    <p>Be the first! Go identify a bird and it will appear here for the whole world to see.</p>
                </div>`;
            } else {
                document.getElementById('feedEnd').style.display = 'block';
                ended = true;
            }
        } else {
            data.data.forEach(d => container.insertAdjacentHTML('beforeend', buildCard(d)));
            offset += data.data.length;
            if (data.data.length < 10) {
                document.getElementById('feedEnd').style.display = 'block';
                ended = true;
            }
        }
    } catch(e) {
        document.getElementById('feedLoader').style.display = 'none';
    }
    loading = false;
}

function openDetail(d) {
    detailCurrentId = d.identification_id;
    const imgPath   = d.filename ? 'uploads/' + d.filename : '';
    const isUnknown = !d.species_name || d.species_name === 'Unknown';
    const species   = isUnknown ? 'Unknown Bird' : d.species_name;
    const conf      = d.confidence > 0 ? parseFloat(d.confidence).toFixed(1) + '%' : '';
    const name      = ((d.first_name||'') + ' ' + (d.last_name||'')).trim() || 'Anonymous';
    const liked     = d.user_liked > 0;

    const left = document.getElementById('detailLeft');
    const existing = left.querySelector('img.detail-photo');
    if (existing) existing.remove();
    if (imgPath) {
        const ph = document.getElementById('detailPhotoWrap');
        if (ph) ph.style.display = 'none';
        const img = document.createElement('img');
        img.className = 'detail-photo';
        img.src = imgPath;
        img.alt = species;
        img.onerror = function() {
            this.remove();
            const p = document.getElementById('detailPhotoWrap');
            if (p) p.style.display = 'flex';
        };
        left.insertBefore(img, left.firstChild);
    } else {
        const ph = document.getElementById('detailPhotoWrap');
        if (ph) ph.style.display = 'flex';
    }

    document.getElementById('detailSpeciesName').textContent = species;
    const confPill = document.getElementById('detailConfPill');
    if (conf) {
        confPill.textContent = conf + ' confident';
        confPill.className   = 'detail-conf-pill' + (isUnknown ? ' unknown' : '');
        confPill.style.display = 'inline-flex';
    } else {
        confPill.style.display = 'none';
    }

    document.getElementById('detailAvatar').textContent = initials(d.first_name, d.last_name);
    document.getElementById('detailUname').textContent  = name;

    let metaHtml = `<span class="detail-meta-item"><i class="far fa-clock"></i>${timeAgo(d.identification_time)}</span>`;
    if (d.location) metaHtml += `<span class="detail-meta-item"><i class="fas fa-map-marker-alt"></i>${d.location}</span>`;
    document.getElementById('detailMetaRow').innerHTML = metaHtml;

    const likeBtn = document.getElementById('detailLikeBtn');
    likeBtn.className = 'btn-like-detail' + (liked ? ' liked' : '');
    likeBtn.querySelector('i').className = liked ? 'fas fa-heart' : 'far fa-heart';
    document.getElementById('detailLikeCount').textContent = d.likes;
    document.getElementById('detailCmtLabel').innerHTML = `<i class="far fa-comment"></i> ${d.comments} comment${d.comments!=1?'s':''}`;

    loadDetailComments();

    document.getElementById('detailOverlay').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeDetail() {
    document.getElementById('detailOverlay').classList.remove('active');
    document.body.style.overflow = '';
    detailCurrentId = null;
}

function handleOverlayClick(e) {
    if (e.target === document.getElementById('detailOverlay')) closeDetail();
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeDetail(); });

async function detailToggleLike() {
    if (!IS_LOGGED_IN) { alert('Please login to like posts.'); return; }
    const fd = new FormData();
    fd.append('action','like');
    fd.append('identification_id', detailCurrentId);
    const data = await (await fetch('community_api.php', {method:'POST',body:fd})).json();
    if (!data.success) return;
    const btn = document.getElementById('detailLikeBtn');
    btn.classList.toggle('liked', data.liked);
    btn.querySelector('i').className = data.liked ? 'fas fa-heart' : 'far fa-heart';
    document.getElementById('detailLikeCount').textContent = data.count;
    const cardEl = document.getElementById('card-' + detailCurrentId);
    if (cardEl) {
        const heartStat = cardEl.querySelector('.card-stat');
        if (heartStat) heartStat.innerHTML = `<i class="fas fa-heart"></i>${data.count}`;
    }
}

async function loadDetailComments() {
    const area = document.getElementById('detailCommentsArea');
    area.innerHTML = '<div style="padding:16px;text-align:center;color:#9ca3af;font-size:.8rem;"><i class="fas fa-spinner fa-spin me-1"></i>Loading…</div>';
    const data = await (await fetch(`community_api.php?action=comments&identification_id=${detailCurrentId}`)).json();
    let html = '';
    if (!data.data || data.data.length === 0) {
        html = `<div class="cmt-empty"><i class="far fa-comment-dots"></i>No comments yet — be the first!</div>`;
    } else {
        data.data.forEach(c => { html += buildDetailComment(c); });
    }
    area.innerHTML = html;
    area.scrollTop = area.scrollHeight;
}

function buildDetailComment(c) {
    const name   = ((c.first_name||'') + ' ' + (c.last_name||'')).trim() || 'User';
    const canDel = IS_LOGGED_IN && (c.user_id === CURRENT_UID || IS_ADMIN);
    return `<div class="cmt-item" id="dcmt-${c.id}">
        <div class="cmt-av">${initials(c.first_name, c.last_name)}</div>
        <div style="flex:1;">
            <div class="cmt-bubble">
                <div class="cmt-uname">${name}</div>
                ${c.comment.replace(/</g,'&lt;')}
            </div>
            <div class="cmt-footer">
                <span>${timeAgo(c.created_at)}</span>
                ${canDel ? `<button class="btn-del-cmt" onclick="deleteDetailComment(${c.id})"><i class="fas fa-trash-alt"></i> Delete</button>` : ''}
            </div>
        </div>
    </div>`;
}

async function detailSubmitComment() {
    const inp = document.getElementById('detailCmtInput');
    if (!inp) return;
    const txt = inp.value.trim();
    if (!txt) return;
    const fd = new FormData();
    fd.append('action','comment_add');
    fd.append('identification_id', detailCurrentId);
    fd.append('comment', txt);
    const data = await (await fetch('community_api.php', {method:'POST',body:fd})).json();
    if (!data.success) return;
    inp.value = '';
    const area = document.getElementById('detailCommentsArea');
    const empty = area.querySelector('.cmt-empty');
    if (empty) empty.remove();
    area.insertAdjacentHTML('beforeend', buildDetailComment(data.comment));
    area.scrollTop = area.scrollHeight;
    const lbl = document.getElementById('detailCmtLabel');
    const cur = parseInt(lbl.textContent) || 0;
    lbl.innerHTML = `<i class="far fa-comment"></i> ${cur+1} comment${(cur+1)!==1?'s':''}`;
}

async function deleteDetailComment(cid) {
    mkCommunityConfirm('Delete Comment?', 'This comment will be permanently removed.', async () => {
        const fd = new FormData();
        fd.append('action','comment_delete');
        fd.append('comment_id', cid);
        const data = await (await fetch('community_api.php', {method:'POST',body:fd})).json();
        if (data.success) document.getElementById('dcmt-' + cid)?.remove();
    });
}

async function adminDeleteCard(identificationId, btn) {
    mkCommunityConfirm('Remove Post?', 'This bird sighting will be permanently removed from Bird Explorer.', async () => {
        if (btn) btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        const fd = new FormData();
        fd.append('action','delete_card');
        fd.append('identification_id', identificationId);
        const data = await (await fetch('community_api.php', {method:'POST',body:fd})).json();
        if (data.success) {
            const card = document.getElementById('card-' + identificationId);
            if (card) {
                card.style.transition = 'all .3s';
                card.style.opacity = '0';
                card.style.transform = 'scale(.95)';
                setTimeout(() => card.remove(), 300);
            }
        }
    });
}

async function adminDeleteCurrentPost() {
    if (!detailCurrentId) return;
    mkCommunityConfirm('Remove Post?', 'This bird sighting will be permanently removed from Bird Explorer.', async () => {
        const fd = new FormData();
        fd.append('action','delete_card');
        fd.append('identification_id', detailCurrentId);
        const data = await (await fetch('community_api.php', {method:'POST',body:fd})).json();
        if (data.success) {
            closeDetail();
            const card = document.getElementById('card-' + detailCurrentId);
            if (card) {
                card.style.transition = 'all .3s';
                card.style.opacity = '0';
                setTimeout(() => card.remove(), 300);
            }
        }
    });
}

function mkCommunityConfirm(title, message, onConfirm) {
    let modal = document.getElementById('mkCommModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'mkCommModal';
        modal.style.cssText = 'position:fixed;inset:0;z-index:99999;display:flex;align-items:center;justify-content:center;padding:20px;background:rgba(13,27,42,.85);backdrop-filter:blur(8px);';
        document.body.appendChild(modal);
    }
    modal.innerHTML = `
        <div style="background:#fff;border-radius:20px;padding:32px 28px;max-width:340px;
                    width:100%;text-align:center;box-shadow:0 24px 60px rgba(0,0,0,.2);
                    animation:mkCmIn .22s ease;">
            <div style="width:50px;height:50px;border-radius:50%;margin:0 auto 14px;
                         background:rgba(248,113,113,.1);border:2px solid rgba(248,113,113,.25);
                         display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-trash-alt" style="color:#e63946;font-size:1.1rem;"></i>
            </div>
            <div style="font-family:'Playfair Display',serif;font-size:1rem;font-weight:700;
                        color:#1a1a2e;margin-bottom:8px;">${title}</div>
            <div style="font-size:.82rem;color:#6b7280;line-height:1.6;margin-bottom:20px;">${message}</div>
            <div style="display:flex;gap:10px;justify-content:center;">
                <button id="mkCmYes" style="flex:1;max-width:120px;background:linear-gradient(135deg,#e63946,#f87171);
                    color:#fff;border:none;border-radius:50px;padding:10px 16px;font-size:.83rem;
                    font-weight:700;cursor:pointer;font-family:'Inter',sans-serif;">Delete</button>
                <button id="mkCmNo" style="flex:1;max-width:120px;background:#f0f7f4;
                    color:#2d6a4f;border:1px solid rgba(116,198,157,.3);border-radius:50px;
                    padding:10px 16px;font-size:.83rem;font-weight:600;cursor:pointer;
                    font-family:'Inter',sans-serif;">Cancel</button>
            </div>
        </div>
        <style>@keyframes mkCmIn{from{opacity:0;transform:scale(.92)}to{opacity:1;transform:scale(1)}}</style>`;
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    const close = () => { modal.style.display='none'; document.body.style.overflow=''; };
    document.getElementById('mkCmYes').onclick = () => { close(); onConfirm(); };
    document.getElementById('mkCmNo').onclick  = close;
    modal.onclick = (e) => { if(e.target===modal) close(); };
}

window.addEventListener('scroll', () => {
    if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 400) loadFeed();
});

loadFeed();
</script>
</body>
</html>
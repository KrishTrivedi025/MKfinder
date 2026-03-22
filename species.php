<?php
/**
 * MKfinder Species Information — Alignment Fixed
 */
require_once 'config.php';
require_once 'database.php';

$speciesName = isset($_GET['species']) ? sanitizeInput($_GET['species']) : '';
$speciesInfo = null;
if (!empty($speciesName)) {
    $speciesInfo = getSpeciesInfoFromDB($speciesName);
}
$allSpecies = getAllSpeciesFromDB();

function getDefaultSpeciesInfo($name) {
    $data = [
        'American Robin' => [
            'name' => 'American Robin', 'scientific_name' => 'Turdus migratorius',
            'description' => 'The American Robin is a migratory songbird of the true thrush genus and Turdidae, the wider thrush family. Named after the European robin because of its reddish-orange breast, it is one of the most familiar birds across North America.',
            'characteristics' => ['Red-orange breast and belly','Dark gray to black head and back','White markings around the eyes','Yellow beak with dark tip','White undertail coverts','Length: 8–11 inches','Wingspan: 12–16 inches'],
            'habitat' => 'Woodlands, parks, gardens, and lawns across North America',
            'behavior' => 'Known for pulling earthworms from lawns, territorial during breeding season, forms large flocks in winter',
            'diet' => 'Insects, earthworms, fruits, and berries',
            'conservation_status' => 'Least Concern',
        ],
        'Blue Jay' => [
            'name' => 'Blue Jay', 'scientific_name' => 'Cyanocitta cristata',
            'description' => 'The Blue Jay is a passerine bird in the family Corvidae, native to eastern North America. A highly intelligent and social bird known for its distinctive blue coloration, complex vocalizations, and remarkable memory for storing food.',
            'characteristics' => ['Bright blue upper parts with white underparts','Black necklace markings across throat','Prominent blue crest (raised or lowered)','White and black barred wings and tail','Black bill and legs','Length: 11–12 inches','Wingspan: 13–17 inches'],
            'habitat' => 'Deciduous and mixed forests, parks, and residential areas with large trees',
            'behavior' => 'Highly social, forms complex family groups, known for mobbing predators, excellent mimic of other birds',
            'diet' => 'Nuts, seeds, insects, eggs, and small animals',
            'conservation_status' => 'Least Concern',
        ],
        'Northern Cardinal' => [
            'name' => 'Northern Cardinal', 'scientific_name' => 'Cardinalis cardinalis',
            'description' => 'The Northern Cardinal is a bird in the genus Cardinalis, also known as the redbird or common cardinal. Males are vibrant red while females are a warm brown with red accents — one of the most recognisable songbirds in North America.',
            'characteristics' => ['Males: Brilliant red all over with black mask','Females: Warm brown with red tinges on wings, tail, and crest','Thick, orange-red, cone-shaped beak','Prominent crest on head','Black face mask around beak and eyes (males)','Length: 8.5–9 inches','Wingspan: 9.8–12.2 inches'],
            'habitat' => 'Woodlands, gardens, shrublands, and wetlands with dense cover',
            'behavior' => 'Non-migratory, territorial, males sing to defend territory, females also sing — unusual among songbirds',
            'diet' => 'Seeds, grains, fruits, and insects',
            'conservation_status' => 'Least Concern',
        ],
    ];
    return $data[$name] ?? null;
}
if (!$speciesInfo && !empty($speciesName)) {
    $speciesInfo = getDefaultSpeciesInfo($speciesName);
}

function conservationColor($status) {
    $map = [
        'Least Concern'         => ['#74c69d', 'rgba(116,198,157,.15)'],
        'Near Threatened'       => ['#facc15', 'rgba(250,204,21,.15)'],
        'Vulnerable'            => ['#fb923c', 'rgba(251,146,60,.15)'],
        'Endangered'            => ['#f87171', 'rgba(248,113,113,.15)'],
        'Critically Endangered' => ['#dc2626', 'rgba(220,38,38,.15)'],
    ];
    return $map[$status] ?? ['#9ca3af', 'rgba(156,163,175,.15)'];
}

// Build imageMap from DB — uses admin-uploaded photos
// Falls back gracefully (no image shown) if column missing
$imageMap = [];
foreach ($allSpecies as $sp) {
    if (!empty($sp['image_path'])) {
        $imageMap[$sp['name']] = $sp['image_path'];
    }
}

$families = [
    'American Robin'    => 'Turdidae (Thrushes)',
    'Blue Jay'          => 'Corvidae (Crows & Jays)',
    'Northern Cardinal' => 'Cardinalidae (Cardinals)',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $speciesInfo ? htmlspecialchars($speciesInfo['name']).' – ' : ''; ?>Species – MKfinder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
    <style>
        /* ─────────────────────────────────────
           BASE
        ───────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; }
        body { background: #f0f7f4; font-family: 'Inter', sans-serif; }

        /* ─────────────────────────────────────
           NAVBAR
        ───────────────────────────────────── */
        .navbar {
            background: rgba(13,27,42,.96) !important;
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(116,198,157,.15);
            padding: 14px 0;
        }
        .navbar.scrolled { box-shadow: 0 4px 30px rgba(0,0,0,.3); }

        /* ─────────────────────────────────────
           SHARED HERO STYLES
        ───────────────────────────────────── */
        .page-hero {
            background: linear-gradient(135deg, #0d1b2a 0%, #1b4332 45%, #2d6a4f 100%);
            position: relative;
            overflow: hidden;
        }
        .page-hero::before {
            content: ''; position: absolute; inset: 0; pointer-events: none;
            background:
                radial-gradient(circle at 20% 55%, rgba(116,198,157,.18) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(72,202,228,.1)   0%, transparent 40%);
        }
        .hero-wave {
            display: block; width: 100%; margin-top: -2px; line-height: 0;
        }
        .hero-badge {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(116,198,157,.15); border: 1px solid rgba(116,198,157,.3);
            color: #74c69d; padding: 6px 16px; border-radius: 50px;
            font-size: .78rem; font-weight: 600; letter-spacing: .5px;
            text-transform: uppercase; margin-bottom: 16px;
        }
        /* animated dots */
        .hero-dots { position: absolute; inset: 0; pointer-events: none; }
        .hdot {
            position: absolute; border-radius: 50%; background: rgba(116,198,157,.25);
            animation: hdotFloat 8s ease-in-out infinite;
        }
        .hdot:nth-child(1){ width:6px;height:6px;top:25%;left:7%;  animation-delay:0s;   }
        .hdot:nth-child(2){ width:4px;height:4px;top:62%;left:16%; animation-delay:2s;   }
        .hdot:nth-child(3){ width:8px;height:8px;top:38%;right:9%; animation-delay:1s;   }
        .hdot:nth-child(4){ width:5px;height:5px;top:70%;right:20%;animation-delay:3s;   }
        @keyframes hdotFloat {
            0%,100%{ transform:translateY(0) scale(1); opacity:.3; }
            50%    { transform:translateY(-16px) scale(1.3); opacity:.7; }
        }

        /* ─────────────────────────────────────
           ① DETAIL HERO  — fixed alignment
             Left col: vertically centred text
             Right col: bird image flush to bottom
        ───────────────────────────────────── */
        .detail-hero { padding: 90px 0 0; }

        .detail-hero .hero-inner {
            position: relative; z-index: 2;
            display: flex;
            align-items: center;
            gap: 48px;
            padding: 48px 0 56px;
        }

        /* LEFT — text */
        .hero-text { flex: 1 1 50%; }
        .sp-tags { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 18px; }
        .sp-tag {
            display: inline-flex; align-items: center; gap: 6px;
            border-radius: 50px; padding: 5px 14px;
            font-size: .76rem; font-weight: 600;
            backdrop-filter: blur(8px);
            background: rgba(116,198,157,.12);
            border: 1px solid rgba(116,198,157,.28);
            color: #74c69d;
        }
        .sp-hero-name {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2rem, 4.5vw, 3.2rem);
            color: #fff; font-weight: 800; line-height: 1.15; margin-bottom: 10px;
        }
        .sp-hero-name em { color: #74c69d; font-style: normal; }
        .sp-hero-sci {
            font-size: .95rem; color: rgba(255,255,255,.45); font-style: italic;
        }

        /* RIGHT — floating photo card */
        .hero-img {
            flex: 0 0 420px;
            max-width: 420px;
            position: relative;
        }
        /* Ambient glow behind card */
        .hero-img::after {
            content: '';
            position: absolute;
            inset: -20px;
            background: radial-gradient(ellipse at center,
                rgba(116,198,157,.22) 0%,
                transparent 70%);
            border-radius: 28px;
            z-index: 0;
            pointer-events: none;
        }
        .hero-img-card {
            position: relative;
            z-index: 1;
            border-radius: 24px;
            overflow: hidden;
            background: #0d1b2a;
            box-shadow:
                0 32px 64px rgba(0,0,0,.45),
                0 0 0 1px rgba(116,198,157,.18),
                inset 0 1px 0 rgba(255,255,255,.08);
            transition: transform .4s cubic-bezier(.4,0,.2,1),
                        box-shadow .4s;
        }
        .hero-img-card:hover {
            transform: translateY(-8px) scale(1.01);
            box-shadow:
                0 48px 80px rgba(0,0,0,.5),
                0 0 0 1px rgba(116,198,157,.3),
                inset 0 1px 0 rgba(255,255,255,.1);
        }
        .hero-img-card img {
            width: 100%;
            height: 340px;
            object-fit: cover;
            object-position: center top;
            display: block;
            transition: transform .6s cubic-bezier(.4,0,.2,1);
        }
        .hero-img-card:hover img {
            transform: scale(1.05);
        }
        /* Species name overlay at bottom of card */
        .hero-img-card .card-overlay {
            position: absolute;
            bottom: 0; left: 0; right: 0;
            padding: 32px 20px 16px;
            background: linear-gradient(to top,
                rgba(13,27,42,.9) 0%,
                transparent 100%);
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
        }
        .card-overlay-species {
            font-size: .78rem;
            font-weight: 700;
            color: #74c69d;
            letter-spacing: .5px;
            text-transform: uppercase;
        }
        .card-overlay-conf {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(116,198,157,.15);
            border: 1px solid rgba(116,198,157,.3);
            color: #74c69d;
            padding: 3px 10px;
            border-radius: 50px;
            font-size: .7rem;
            font-weight: 700;
        }
        .hero-img .img-placeholder {
            width: 100%;
            height: 300px;
            border-radius: 24px;
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(116,198,157,.12);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        .hero-img .img-placeholder i   { font-size: 4rem; color: rgba(116,198,157,.25); }
        .hero-img .img-placeholder span { font-size: .8rem; color: rgba(116,198,157,.35); }

        /* back link */
        .back-link {
            position: absolute; top: 0; left: 0;
            display: inline-flex; align-items: center; gap: 7px;
            color: rgba(255,255,255,.5); font-size: .84rem;
            text-decoration: none; transition: color .2s; z-index: 3;
        }
        .back-link:hover { color: #74c69d; text-decoration: none; }

        @media(max-width: 900px) {
            .detail-hero .hero-inner {
                flex-direction: column-reverse;
                align-items: center;
                gap: 28px;
                padding: 32px 0 40px;
            }
            .hero-text { text-align: center; }
            .sp-tags { justify-content: center; }
            .hero-img { flex: 0 0 auto; width: 100%; max-width: 340px; }
            .hero-img-card img { height: 260px; }
        }

        /* ─────────────────────────────────────
           ② DETAIL BODY — NO h-100, no stretch
        ───────────────────────────────────── */
        .detail-body { padding: 48px 0 72px; }

        /* Info cards — height always fits content */
        .info-card {
            background: #fff;
            border-radius: 18px;
            padding: 28px;
            box-shadow: 0 4px 22px rgba(45,106,79,.08);
            border: 1px solid rgba(116,198,157,.1);
            /* height: auto — never forces extra whitespace */
        }
        .card-head { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
        .head-icon {
            width: 38px; height: 38px; border-radius: 10px;
            background: linear-gradient(135deg,#40916c,#74c69d);
            display: flex; align-items: center; justify-content: center;
            font-size: .88rem; color: #fff; flex-shrink: 0;
        }
        .card-head h3 { font-size: 1rem; font-weight: 700; color: #1a1a2e; margin: 0; }
        .info-card p  { font-size: .9rem; color: #4b5563; line-height: 1.75; margin: 0; }

        /* characteristics grid */
        .char-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        @media(max-width:576px){ .char-grid { grid-template-columns: 1fr; } }
        .char-item {
            display: flex; align-items: flex-start; gap: 10px;
            background: #f8fffe;
            border: 1px solid rgba(116,198,157,.12);
            border-radius: 10px; padding: 11px 14px;
        }
        .ci-dot {
            width: 22px; height: 22px; border-radius: 50%;
            background: linear-gradient(135deg,#40916c,#74c69d);
            display: flex; align-items: center; justify-content: center;
            font-size: .6rem; color: #fff; flex-shrink: 0; margin-top: 1px;
        }
        .char-item span { font-size: .85rem; color: #374151; line-height: 1.5; }

        /* ─────────────────────────────────────
           ③ SIDEBAR — sticky, no overflow bleed
        ───────────────────────────────────── */
        .sidebar { position: sticky; top: 88px; display: flex; flex-direction: column; gap: 18px; }

        .facts-card {
            background: #fff; border-radius: 18px; overflow: hidden;
            box-shadow: 0 4px 22px rgba(45,106,79,.08);
            border: 1px solid rgba(116,198,157,.1);
        }
        .facts-header {
            background: linear-gradient(135deg,#1b4332,#2d6a4f);
            padding: 20px 24px;
        }
        .facts-header h3 { font-size: 1rem; font-weight: 700; color: #fff; margin: 0; }
        .facts-header p  { font-size: .78rem; color: rgba(255,255,255,.5); margin: 4px 0 0; }
        .facts-body { padding: 8px 24px 16px; }
        .fact-row { padding: 13px 0; border-bottom: 1px solid rgba(116,198,157,.08); }
        .fact-row:last-child { border-bottom: none; padding-bottom: 6px; }
        .fact-lbl {
            font-size: .72rem; font-weight: 600; color: #9ca3af;
            text-transform: uppercase; letter-spacing: .5px; margin-bottom: 5px;
        }
        .fact-val { font-size: .9rem; color: #1a1a2e; font-weight: 500; }
        .fact-val em { font-style: italic; color: #6b7280; font-weight: 400; }
        .iucn-pill {
            display: inline-flex; align-items: center; gap: 6px;
            border-radius: 8px; padding: 5px 12px;
            font-size: .8rem; font-weight: 700; border: 1px solid transparent;
        }

        /* CTA */
        .identify-cta {
            background: linear-gradient(135deg,#1b4332,#2d6a4f);
            border-radius: 18px; padding: 28px; text-align: center;
            border: 1px solid rgba(116,198,157,.2);
        }
        .identify-cta .cta-icon { font-size: 2rem; color: #74c69d; margin-bottom: 12px; display: block; }
        .identify-cta h4 {
            font-family: 'Playfair Display',serif;
            font-size: 1.1rem; color: #fff; font-weight: 700; margin-bottom: 8px;
        }
        .identify-cta p { font-size: .83rem; color: rgba(255,255,255,.5); margin-bottom: 18px; }
        .btn-cta {
            display: inline-flex; align-items: center; gap: 7px;
            background: linear-gradient(135deg,#40916c,#74c69d);
            color: #fff; border: none; border-radius: 10px;
            padding: 11px 22px; font-size: .85rem; font-weight: 600;
            font-family: 'Inter',sans-serif; text-decoration: none;
            transition: all .25s; cursor: pointer;
        }
        .btn-cta:hover {
            transform: translateY(-2px); box-shadow: 0 8px 22px rgba(64,145,108,.4);
            color: #fff; text-decoration: none;
        }

        /* Other species */
        .other-card {
            background: #fff; border-radius: 18px; overflow: hidden;
            box-shadow: 0 4px 22px rgba(45,106,79,.08);
            border: 1px solid rgba(116,198,157,.1);
        }
        .other-header {
            background: linear-gradient(135deg,#0d1b2a,#1b4332);
            padding: 16px 22px;
        }
        .other-header h3 { font-size: .95rem; font-weight: 700; color: #fff; margin: 0; }
        .other-item {
            display: flex; align-items: center; gap: 14px;
            padding: 14px 20px; text-decoration: none;
            border-bottom: 1px solid rgba(116,198,157,.07);
            transition: all .25s;
        }
        .other-item:last-child { border-bottom: none; }
        .other-item:hover { background: rgba(116,198,157,.06); text-decoration: none; transform: translateX(4px); }
        .other-thumb {
            width: 50px; height: 44px; border-radius: 10px; flex-shrink: 0;
            background: linear-gradient(135deg,#e8f5e9,#c8e6c9);
            display: flex; align-items: center; justify-content: center; overflow: hidden;
        }
        .other-thumb img { width: 100%; height: 100%; object-fit: cover; border-radius: 10px; }
        .other-thumb i   { font-size: 1.2rem; color: #40916c; opacity: .5; }
        .other-name { font-size: .88rem; font-weight: 600; color: #1a1a2e; }
        .other-sci  { font-size: .74rem; color: #9ca3af; font-style: italic; }

        /* ─────────────────────────────────────
           LIST PAGE STYLES
        ───────────────────────────────────── */
        .list-hero { padding: 130px 0 70px; }
        .list-hero h1 {
            font-family: 'Playfair Display',serif;
            font-size: clamp(2rem,5vw,3rem);
            color: #fff; font-weight: 800; margin-bottom: 12px; line-height: 1.15;
        }
        .list-hero h1 span {
            background: linear-gradient(135deg,#74c69d,#48cae4);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        }
        .list-hero p { color: rgba(255,255,255,.55); font-size: 1rem; }
        .list-hero::after {
            content: ''; position: absolute; bottom: -2px; left: 0; right: 0; height: 80px;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 80'%3E%3Cpath fill='%23f0f7f4' d='M0,40L60,36C120,32,240,24,360,28C480,32,600,48,720,52C840,56,960,48,1080,40C1200,32,1320,24,1380,20L1440,16L1440,80L0,80Z'/%3E%3C/svg%3E") no-repeat bottom/cover;
            pointer-events: none;
        }

        /* search bar */
        .search-bar {
            background: #fff; border-radius: 16px; padding: 18px 26px;
            margin-bottom: 32px; box-shadow: 0 2px 20px rgba(45,106,79,.08);
            border: 1px solid rgba(116,198,157,.12);
            display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
        }
        .search-wrap { flex: 1; min-width: 220px; position: relative; }
        .search-wrap i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: rgba(64,145,108,.5); font-size: .88rem; pointer-events: none; }
        .search-wrap input {
            width: 100%; background: #f8fffe;
            border: 1.5px solid rgba(116,198,157,.22); border-radius: 10px;
            padding: 11px 16px 11px 40px; font-size: .88rem; color: #1a1a2e;
            font-family: 'Inter',sans-serif; outline: none; transition: all .25s;
        }
        .search-wrap input:focus { border-color: #40916c; box-shadow: 0 0 0 3px rgba(64,145,108,.1); }
        .search-wrap input::placeholder { color: #9ca3af; }
        .result-count { font-size: .83rem; color: #9ca3af; white-space: nowrap; }

        /* species list grid */
        .sp-grid {
            display: grid; grid-template-columns: repeat(auto-fill,minmax(300px,1fr));
            gap: 28px; margin-bottom: 60px;
        }
        .sp-card {
            background: #fff; border-radius: 20px; overflow: hidden;
            box-shadow: 0 4px 22px rgba(45,106,79,.08);
            border: 1px solid rgba(116,198,157,.1);
            transition: all .35s cubic-bezier(.4,0,.2,1);
            display: flex; flex-direction: column;
        }
        .sp-card:hover { transform: translateY(-8px); box-shadow: 0 20px 56px rgba(45,106,79,.16); border-color: rgba(116,198,157,.28); }
        .sp-img-wrap { height: 220px; overflow: hidden; position: relative; background: linear-gradient(135deg,#e8f5e9,#c8e6c9); }
        .sp-img-wrap img { width: 100%; height: 100%; object-fit: cover; transition: transform .5s; }
        .sp-card:hover .sp-img-wrap img { transform: scale(1.07); }
        .sp-placeholder { width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; background: linear-gradient(135deg,#e8f5e9,#b7dfca); }
        .sp-placeholder i { font-size: 3.5rem; color: #40916c; opacity: .4; }
        .sp-placeholder span { font-size: .8rem; color: #40916c; opacity: .6; }
        .status-ribbon { position: absolute; top: 14px; right: 14px; display: inline-flex; align-items: center; gap: 5px; border-radius: 50px; padding: 4px 12px; font-size: .72rem; font-weight: 700; backdrop-filter: blur(8px); border: 1px solid transparent; }
        .sp-body { padding: 22px; flex: 1; display: flex; flex-direction: column; }
        .sp-name { font-family: 'Playfair Display',serif; font-size: 1.1rem; font-weight: 700; color: #1a1a2e; margin-bottom: 2px; }
        .sp-sci  { font-size: .8rem; color: #9ca3af; font-style: italic; margin-bottom: 12px; }
        .sp-desc { font-size: .85rem; color: #6b7280; line-height: 1.65; margin-bottom: 16px; flex: 1; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
        .sp-chips { display: flex; gap: 7px; flex-wrap: wrap; margin-bottom: 16px; }
        .chip { display: inline-flex; align-items: center; gap: 5px; background: #f0f7f4; border: 1px solid rgba(116,198,157,.2); border-radius: 50px; padding: 4px 11px; font-size: .73rem; font-weight: 500; color: #2d6a4f; }
        .chip i { font-size: .65rem; color: #40916c; }
        .btn-profile { width: 100%; background: linear-gradient(135deg,#40916c,#74c69d); border: none; border-radius: 11px; padding: 11px; color: #fff; font-size: .85rem; font-weight: 600; font-family: 'Inter',sans-serif; cursor: pointer; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 7px; transition: all .3s; }
        .btn-profile:hover { transform: translateY(-2px); box-shadow: 0 8px 22px rgba(64,145,108,.4); color: #fff; text-decoration: none; }

        /* ─────────────────────────────────────
           FOOTER
        ───────────────────────────────────── */
        .site-footer {
            background: #0d1b2a; color: rgba(255,255,255,.7);
            padding: 60px 0 32px; border-top: 1px solid rgba(255,255,255,.05);
        }
        .footer-brand .brand-name { font-size: 1.4rem; }
        .footer-desc { color: rgba(255,255,255,.4); font-size: .85rem; margin-top: 8px; max-width: 280px; }
        .footer-links h6 { color: rgba(255,255,255,.6); font-size: .75rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px; font-weight: 700; }
        .footer-links a { display: block; color: rgba(255,255,255,.4); text-decoration: none; font-size: .88rem; margin-bottom: 8px; transition: all .3s; }
        .footer-links a:hover { color: #74c69d; transform: translateX(4px); }
        .footer-bottom { border-top: 1px solid rgba(255,255,255,.06); padding-top: 24px; margin-top: 40px; font-size: .82rem; color: rgba(255,255,255,.25); }

        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #f0f7f4; }
        ::-webkit-scrollbar-thumb { background: #40916c; border-radius: 3px; }
    </style>
</head>
<body>

<!-- ══ NAVBAR ══ -->
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
                <li class="nav-item"><a class="nav-link active" href="species.php"><i class="fas fa-feather me-1"></i>Species</a></li>
                <li class="nav-item">
                        <a class="nav-link" href="community.php">
                            <i class="fas fa-globe me-1"></i>Bird Explorer
                        </a>
                    </li>
                <li class="nav-item guest-nav"><a class="nav-link" href="login.html">Login</a></li>
                <li class="nav-item guest-nav ms-2">
                    <a class="btn-hero-primary px-4 py-2" href="signup.html" style="font-size:.88rem;text-decoration:none;">
                        <i class="fas fa-user-plus me-1"></i>Sign Up
                    </a>
                </li>
                <li class="nav-item user-nav" style="display:none;">
                    <a class="nav-link" id="userNavLink" href="index.html"
                       style="color:#74c69d;font-size:.85rem;text-decoration:none;">
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

<?php if ($speciesInfo):
    $imgFile = $imageMap[$speciesInfo['name']] ?? '';
    $hasImg  = !empty($imgFile) && file_exists($imgFile);
    [$iucnColor, $iucnBg] = conservationColor($speciesInfo['conservation_status'] ?? 'Least Concern');
?>

<!-- ══════════════════════════════
     DETAIL HERO  (alignment fixed)
══════════════════════════════ -->
<section class="page-hero detail-hero">
    <div class="hero-dots">
        <div class="hdot"></div><div class="hdot"></div>
        <div class="hdot"></div><div class="hdot"></div>
    </div>

    <div class="container" style="position:relative; z-index:2;">

        <!-- back link sits above the two columns -->
        <a href="species.php" class="back-link">
            <i class="fas fa-arrow-left"></i> All Species
        </a>

        <!-- Two columns: text left, image right — both aligned to bottom -->
        <div class="hero-inner">

            <!-- LEFT: tags + name + sci name -->
            <div class="hero-text">
                <div class="sp-tags">
                    <span class="sp-tag"><i class="fas fa-feather-alt"></i>Bird Species</span>
                    <span class="sp-tag" style="background:<?php echo $iucnBg;?>;border-color:<?php echo $iucnColor;?>55;color:<?php echo $iucnColor;?>;">
                        <i class="fas fa-shield-alt"></i>
                        <?php echo htmlspecialchars($speciesInfo['conservation_status'] ?? 'Least Concern'); ?>
                    </span>
                </div>
                <h1 class="sp-hero-name">
                    <?php
                    $parts = explode(' ', $speciesInfo['name'], 2);
                    echo htmlspecialchars($parts[0]);
                    if (!empty($parts[1])) echo ' <em>'.htmlspecialchars($parts[1]).'</em>';
                    ?>
                </h1>
                <p class="sp-hero-sci">
                    <i class="fas fa-flask me-1" style="color:rgba(116,198,157,.45);font-size:.8rem;"></i>
                    <?php echo htmlspecialchars($speciesInfo['scientific_name']); ?>
                </p>
            </div>

            <!-- RIGHT: floating photo card -->
            <div class="hero-img">
                <?php if ($hasImg): ?>
                    <div class="hero-img-card">
                        <img src="<?php echo htmlspecialchars($imgFile); ?>"
                             alt="<?php echo htmlspecialchars($speciesInfo['name']); ?>">
                        <div class="card-overlay">
                            <span class="card-overlay-species">
                                <i class="fas fa-feather-alt me-1"></i>
                                <?php echo htmlspecialchars($speciesInfo['name']); ?>
                            </span>
                            <span class="card-overlay-conf">
                                <i class="fas fa-check-circle"></i> Verified
                            </span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="img-placeholder">
                        <i class="fas fa-dove"></i>
                        <span>No photo available</span>
                    </div>
                <?php endif; ?>
            </div>

        </div><!-- /hero-inner -->
    </div>

    <svg class="hero-wave" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 60">
        <path fill="#f0f7f4" d="M0,30L60,27C120,24,240,18,360,20C480,22,600,32,720,35C840,38,960,32,1080,27C1200,22,1320,16,1380,13L1440,10L1440,60L0,60Z"/>
    </svg>
</section>

<!-- ══════════════════════════════
     DETAIL BODY
     Two-col: left=content, right=sidebar
     Cards are AUTO height — no empty space
══════════════════════════════ -->
<div class="container detail-body">
    <div class="row g-4 align-items-start">

        <!-- ── LEFT: stacked content cards ── -->
        <div class="col-lg-8 d-flex flex-column gap-4">

            <!-- About -->
            <div class="info-card">
                <div class="card-head">
                    <div class="head-icon"><i class="fas fa-book-open"></i></div>
                    <h3>About this Species</h3>
                </div>
                <p><?php echo htmlspecialchars($speciesInfo['description']); ?></p>
            </div>

            <!-- Characteristics -->
            <?php if (!empty($speciesInfo['characteristics'])): ?>
            <div class="info-card">
                <div class="card-head">
                    <div class="head-icon"><i class="fas fa-list-ul"></i></div>
                    <h3>Key Characteristics</h3>
                </div>
                <div class="char-grid">
                    <?php foreach ($speciesInfo['characteristics'] as $c): ?>
                    <div class="char-item">
                        <div class="ci-dot"><i class="fas fa-check"></i></div>
                        <span><?php echo htmlspecialchars($c); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Habitat + Diet side by side -->
            <?php if (!empty($speciesInfo['habitat']) || !empty($speciesInfo['diet'])): ?>
            <div class="row g-4">
                <?php if (!empty($speciesInfo['habitat'])): ?>
                <div class="col-md-6">
                    <div class="info-card">
                        <div class="card-head">
                            <div class="head-icon" style="background:linear-gradient(135deg,#0077b6,#48cae4);">
                                <i class="fas fa-tree"></i>
                            </div>
                            <h3>Habitat</h3>
                        </div>
                        <p><?php echo htmlspecialchars($speciesInfo['habitat']); ?></p>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (!empty($speciesInfo['diet'])): ?>
                <div class="col-md-6">
                    <div class="info-card">
                        <div class="card-head">
                            <div class="head-icon" style="background:linear-gradient(135deg,#e85d04,#f4a261);">
                                <i class="fas fa-drumstick-bite"></i>
                            </div>
                            <h3>Diet</h3>
                        </div>
                        <p><?php echo htmlspecialchars($speciesInfo['diet']); ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Behaviour -->
            <?php if (!empty($speciesInfo['behavior'])): ?>
            <div class="info-card">
                <div class="card-head">
                    <div class="head-icon" style="background:linear-gradient(135deg,#7209b7,#a855f7);">
                        <i class="fas fa-brain"></i>
                    </div>
                    <h3>Behaviour</h3>
                </div>
                <p><?php echo htmlspecialchars($speciesInfo['behavior']); ?></p>
            </div>
            <?php endif; ?>

        </div><!-- /col-lg-8 -->

        <!-- ── RIGHT: sticky sidebar ── -->
        <div class="col-lg-4">
            <div class="sidebar">

                <!-- Quick Facts -->
                <div class="facts-card">
                    <div class="facts-header">
                        <h3><i class="fas fa-info-circle me-2"></i>Quick Facts</h3>
                        <p>Scientific classification &amp; status</p>
                    </div>
                    <div class="facts-body">
                        <div class="fact-row">
                            <div class="fact-lbl">Common Name</div>
                            <div class="fact-val"><?php echo htmlspecialchars($speciesInfo['name']); ?></div>
                        </div>
                        <div class="fact-row">
                            <div class="fact-lbl">Scientific Name</div>
                            <div class="fact-val"><em><?php echo htmlspecialchars($speciesInfo['scientific_name']); ?></em></div>
                        </div>
                        <?php if (!empty($speciesInfo['conservation_status'])): ?>
                        <div class="fact-row">
                            <div class="fact-lbl">Conservation Status</div>
                            <div class="fact-val">
                                <span class="iucn-pill"
                                      style="background:<?php echo $iucnBg;?>;color:<?php echo $iucnColor;?>;border-color:<?php echo $iucnColor;?>55;">
                                    <i class="fas fa-shield-alt" style="font-size:.7rem;"></i>
                                    <?php echo htmlspecialchars($speciesInfo['conservation_status']); ?>
                                </span>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="fact-row">
                            <div class="fact-lbl">Family</div>
                            <div class="fact-val"><?php echo htmlspecialchars($families[$speciesInfo['name']] ?? '—'); ?></div>
                        </div>
                        <div class="fact-row">
                            <div class="fact-lbl">Order</div>
                            <div class="fact-val">Passeriformes</div>
                        </div>
                    </div>
                </div>

                <!-- Identify CTA -->
                <div class="identify-cta">
                    <i class="fas fa-camera cta-icon"></i>
                    <h4>Identify This Bird</h4>
                    <p>Upload a photo to see if it matches this species</p>
                    <a href="index.html#upload-section" class="btn-cta">
                        <i class="fas fa-search"></i>Try Identification
                    </a>
                </div>

                <!-- Other Species -->
                <?php
                $others = array_filter($allSpecies ?? [], fn($s) => $s['name'] !== $speciesInfo['name']);
                if (!empty($others)):
                ?>
                <div class="other-card">
                    <div class="other-header">
                        <h3><i class="fas fa-dove me-2"></i>Other Species</h3>
                    </div>
                    <?php foreach ($others as $oth):
                        $oImg = $imageMap[$oth['name']] ?? '';
                        $oHas = !empty($oImg) && file_exists($oImg);
                    ?>
                    <a href="species.php?species=<?php echo urlencode($oth['name']); ?>" class="other-item">
                        <div class="other-thumb">
                            <?php if ($oHas): ?>
                                <img src="<?php echo htmlspecialchars($oImg); ?>" alt="<?php echo htmlspecialchars($oth['name']); ?>">
                            <?php else: ?>
                                <i class="fas fa-dove"></i>
                            <?php endif; ?>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div class="other-name"><?php echo htmlspecialchars($oth['name']); ?></div>
                            <div class="other-sci"><?php echo htmlspecialchars($oth['scientific_name']); ?></div>
                        </div>
                        <i class="fas fa-chevron-right" style="font-size:.75rem;color:#9ca3af;flex-shrink:0;"></i>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

            </div><!-- /sidebar -->
        </div><!-- /col-lg-4 -->

    </div><!-- /row -->
</div><!-- /container detail-body -->

<?php else: ?>
<!-- ══════════════════════════════
     LIST VIEW
══════════════════════════════ -->
<section class="page-hero list-hero">
    <div class="hero-dots">
        <div class="hdot"></div><div class="hdot"></div>
        <div class="hdot"></div><div class="hdot"></div>
    </div>
    <div class="container" style="position:relative;z-index:2;">
        <div class="hero-badge"><i class="fas fa-feather-alt"></i>Species Database</div>
        <h1>Explore <span>Bird Species</span></h1>
        <p>Learn about every bird species our AI can identify — habitat, diet, behaviour and conservation status.</p>
    </div>
</section>

<div class="container" style="margin-top:48px;">

    <div class="search-bar">
        <div class="search-wrap">
            <i class="fas fa-search"></i>
            <input type="text" id="spSearch" placeholder="Search species by name…" oninput="filterSpecies(this.value)">
        </div>
        <span class="result-count" id="rCount"><?php echo count($allSpecies); ?> species available</span>
    </div>

    <?php if (!empty($allSpecies)): ?>
    <div class="sp-grid" id="spGrid">
        <?php foreach ($allSpecies as $sp):
            $imgFile = $imageMap[$sp['name']] ?? '';
            $hasImg  = !empty($imgFile) && file_exists($imgFile);
            [$iucnColor, $iucnBg] = conservationColor($sp['conservation_status'] ?? 'Least Concern');
            $chips = [];
            if (!empty($sp['habitat'])) $chips[] = ['fas fa-tree',          substr(trim(explode(',',$sp['habitat'])[0]),0,22)];
            if (!empty($sp['diet']))    $chips[] = ['fas fa-drumstick-bite', substr(trim(explode(',',$sp['diet'])[0]),   0,18)];
        ?>
        <div class="sp-card" data-name="<?php echo strtolower(htmlspecialchars($sp['name'])); ?>">
            <div class="sp-img-wrap">
                <?php if ($hasImg): ?>
                    <img src="<?php echo htmlspecialchars($imgFile); ?>" alt="<?php echo htmlspecialchars($sp['name']); ?>" loading="lazy">
                <?php else: ?>
                    <div class="sp-placeholder">
                        <i class="fas fa-dove"></i>
                        <span><?php echo htmlspecialchars($sp['name']); ?></span>
                    </div>
                <?php endif; ?>
                <span class="status-ribbon" style="background:<?php echo $iucnBg;?>;border-color:<?php echo $iucnColor;?>66;color:<?php echo $iucnColor;?>;">
                    <i class="fas fa-shield-alt" style="font-size:.65rem;"></i>
                    <?php echo htmlspecialchars($sp['conservation_status'] ?? 'Least Concern'); ?>
                </span>
            </div>
            <div class="sp-body">
                <div class="sp-name"><?php echo htmlspecialchars($sp['name']); ?></div>
                <div class="sp-sci"><?php echo htmlspecialchars($sp['scientific_name']); ?></div>
                <p class="sp-desc"><?php echo htmlspecialchars($sp['description']); ?></p>
                <div class="sp-chips">
                    <?php foreach ($chips as [$ico,$txt]): ?>
                    <span class="chip"><i class="<?php echo $ico;?>"></i><?php echo htmlspecialchars($txt);?></span>
                    <?php endforeach; ?>
                </div>
                <a href="species.php?species=<?php echo urlencode($sp['name']); ?>" class="btn-profile">
                    <i class="fas fa-binoculars"></i>View Full Profile
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="text-center py-5">
        <i class="fas fa-dove fa-3x mb-3" style="color:#40916c;opacity:.4;"></i>
        <h3 style="font-family:'Playfair Display',serif;color:#1a1a2e;">No Species Available</h3>
        <p style="color:#6b7280;">Species data is not currently loaded. Please check your database configuration.</p>
    </div>
    <?php endif; ?>

</div>
<?php endif; ?>

<!-- ══ FOOTER ══ -->
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
                if (s && d.user) {
                    const full = [d.user.first_name, d.user.last_name].filter(Boolean).join(' ');
                    s.textContent = full || d.user.email || '';
                }
                const navLink = document.getElementById('userNavLink');
                if (navLink && d.is_admin) navLink.href = 'admin.php';
            }
        } catch(e) {}
    }
    document.addEventListener('DOMContentLoaded', checkAndUpdateNav);
    async function handleLogout() {
        try { await fetch('auth.php?action=logout',{method:'POST'}); } catch(e){}
        document.querySelectorAll('.user-nav').forEach(e  => e.style.display='none');
        document.querySelectorAll('.guest-nav').forEach(e => e.style.display='block');
        window.location.href = 'index.html';
    }
    function filterSpecies(q) {
        const cards = document.querySelectorAll('#spGrid .sp-card');
        if (!cards.length) return;
        q = q.toLowerCase().trim();
        let v = 0;
        cards.forEach(c => {
            const show = !q || (c.dataset.name||'').includes(q);
            c.style.display = show ? '' : 'none';
            if (show) v++;
        });
        const el = document.getElementById('rCount');
        if (el) el.textContent = v + ' species' + (q ? ' found' : ' available');
    }
</script>
</body>
</html>
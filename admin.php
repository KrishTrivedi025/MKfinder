<?php
/**
 * MKfinder — admin.php
 * Admin Dashboard — protected, admin only
 */

require_once 'config.php';
require_once 'database.php';

initSession();

// ── Guard: only admin can access ──────────────────────────────
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: index.html');
    exit;
}

$adminName = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
if (empty(trim($adminName))) $adminName = 'Admin';

// ── Active tab ────────────────────────────────────────────────
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';
$allowedTabs = ['dashboard', 'species', 'gallery', 'submissions', 'users', 'system'];
if (!in_array($tab, $allowedTabs)) $tab = 'dashboard';

// ── Quick stats for dashboard ─────────────────────────────────
$stats = ['users' => 0, 'identifications' => 0, 'species' => 0, 'submissions' => 0];
try {
    $db   = getDatabase();
    $conn = $db->getConnection();
    $stats['users']           = $conn->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
    $stats['identifications'] = $conn->query("SELECT COUNT(*) FROM identifications")->fetchColumn();
    $stats['species']         = $conn->query("SELECT COUNT(*) FROM species")->fetchColumn();
    $stats['submissions']     = $conn->query("SELECT COUNT(*) FROM unknown_submissions WHERE status='pending'")->fetchColumn();
} catch (Exception $e) { /* silently fail */ }

// ── Species tab: handle Add / Edit / Delete ───────────────────
$speciesMsg     = '';
$speciesMsgType = '';

if ($tab === 'species' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action_sp = $_POST['action_sp'] ?? '';
    try {
        $conn = getDatabase()->getConnection();

        if ($action_sp === 'add') {
            $modelLabel = strtoupper(trim($_POST['model_label'] ?? ''));
            $name   = trim($_POST['name']                ?? '');
            $sci    = trim($_POST['scientific_name']     ?? '');
            $desc   = trim($_POST['description']         ?? '');
            $hab    = trim($_POST['habitat']             ?? '');
            $diet   = trim($_POST['diet']                ?? '');
            $beh    = trim($_POST['behavior']            ?? '');
            $status = trim($_POST['conservation_status'] ?? 'Least Concern');
            $chars  = trim($_POST['characteristics']     ?? '');

            if (empty($modelLabel)||empty($name)||empty($sci)||empty($desc)||empty($hab)||empty($diet)||empty($beh)) {
                $speciesMsg = 'All fields are required.'; $speciesMsgType = 'error';
            } else {
                $dup = $conn->prepare("SELECT id FROM species WHERE name = ?");
                $dup->execute([$name]);
                if ($dup->fetch()) {
                    $speciesMsg = "\"$name\" already exists."; $speciesMsgType = 'error';
                } else {
                    $imagePath = null;
                    if (!empty($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg','jpeg','png','webp'])) {
                            $fname = 'species_'.time().'_'.bin2hex(random_bytes(4)).'.'.$ext;
                            if (move_uploaded_file($_FILES['photo']['tmp_name'], UPLOAD_DIR.$fname))
                                $imagePath = 'uploads/'.$fname;
                        }
                    }
                    $charsArr = array_values(array_filter(array_map('trim', explode("\n", $chars))));
                    $conn->prepare(
                        "INSERT INTO species (name,scientific_name,description,characteristics,habitat,diet,behavior,conservation_status,image_path,model_label)
                         VALUES (?,?,?,?,?,?,?,?,?,?)"
                    )->execute([$name,$sci,$desc,json_encode($charsArr),$hab,$diet,$beh,$status,$imagePath,$modelLabel]);

                    $pyPath = __DIR__.'/predict.py';
                    $aiNote = '';
                    if (file_exists($pyPath)) {
                        $py = file_get_contents($pyPath);
                        if (preg_match('/SUPPORTED_SPECIES\s*=\s*\{([^}]*)\}/s', $py, $m)) {
                            if (strpos($m[1], '"'.$modelLabel.'"') !== false) {
                                $aiNote = ' · Already in AI model';
                            } else {
                                $newEntry = '    "' . $modelLabel . '": "' . $name . '",' . "\n";
                                $newBlock = 'SUPPORTED_SPECIES = {' . $m[1] . $newEntry . '}';
                                $newPy = preg_replace('/SUPPORTED_SPECIES\s*=\s*\{[^}]*\}/s', $newBlock, $py);
                                if ($newPy && file_put_contents($pyPath, $newPy)) $aiNote = ' · AI model updated ✓';
                                else $aiNote = ' · (predict.py update failed)';
                            }
                        }
                    }
                    $speciesMsg = "\"$name\" added successfully!$aiNote"; $speciesMsgType = 'success';
                }
            }
        }

        elseif ($action_sp === 'edit') {
            $id     = intval($_POST['species_id'] ?? 0);
            $name   = trim($_POST['name']                ?? '');
            $sci    = trim($_POST['scientific_name']     ?? '');
            $desc   = trim($_POST['description']         ?? '');
            $hab    = trim($_POST['habitat']             ?? '');
            $diet   = trim($_POST['diet']                ?? '');
            $beh    = trim($_POST['behavior']            ?? '');
            $status = trim($_POST['conservation_status'] ?? 'Least Concern');
            $chars  = trim($_POST['characteristics']     ?? '');

            if (empty($name)||empty($sci)||empty($desc)||empty($hab)||empty($diet)||empty($beh)) {
                $speciesMsg = 'All fields are required.'; $speciesMsgType = 'error';
            } else {
                $imgUpdate = ''; $imgParams = [];
                if (!empty($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg','jpeg','png','webp'])) {
                        $fname = 'species_'.time().'_'.bin2hex(random_bytes(4)).'.'.$ext;
                        if (move_uploaded_file($_FILES['photo']['tmp_name'], UPLOAD_DIR.$fname)) {
                            $imgUpdate = ', image_path = ?'; $imgParams = ['uploads/'.$fname];
                        }
                    }
                }
                $charsArr = array_values(array_filter(array_map('trim', explode("\n", $chars))));
                $params   = array_merge([$name,$sci,$desc,json_encode($charsArr),$hab,$diet,$beh,$status], $imgParams, [$id]);
                $conn->prepare("UPDATE species SET name=?,scientific_name=?,description=?,characteristics=?,habitat=?,diet=?,behavior=?,conservation_status=?$imgUpdate WHERE id=?")->execute($params);
                $speciesMsg = "\"$name\" updated successfully!"; $speciesMsgType = 'success';
            }
        }

        elseif ($action_sp === 'delete') {
            $id  = intval($_POST['species_id'] ?? 0);
            $row = $conn->prepare("SELECT name FROM species WHERE id = ?");
            $row->execute([$id]);
            $spRow = $row->fetch();
            if ($spRow) {
                $conn->prepare("DELETE FROM species WHERE id = ?")->execute([$id]);
                $speciesMsg = "\"{$spRow['name']}\" removed from the database."; $speciesMsgType = 'success';
            }
        }

    } catch (Exception $e) {
        $speciesMsg = 'Database error: '.$e->getMessage(); $speciesMsgType = 'error';
    }
}

$allSpeciesList = [];
if ($tab === 'species') {
    try { $allSpeciesList = getDatabase()->getAllSpecies(); } catch (Exception $e) {}
}

// ── Users tab: handle activate / deactivate / delete ─────────
$usersMsg     = '';
$usersMsgType = '';

if ($tab === 'users' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action_u  = $_POST['action_u']  ?? '';
    $target_id = $_POST['target_id'] ?? '';

    // Prevent admin from modifying own account
    if ($target_id === $_SESSION['user_id']) {
        $usersMsg = 'You cannot modify your own admin account.'; $usersMsgType = 'error';
    } else {
        try {
            $conn = getDatabase()->getConnection();
            if ($action_u === 'activate') {
                $conn->prepare("UPDATE users SET is_active=1 WHERE user_id=?")->execute([$target_id]);
                $usersMsg = 'User activated.'; $usersMsgType = 'success';
            } elseif ($action_u === 'deactivate') {
                $conn->prepare("UPDATE users SET is_active=0 WHERE user_id=?")->execute([$target_id]);
                $usersMsg = 'User deactivated.'; $usersMsgType = 'success';
            } elseif ($action_u === 'delete') {
                $conn->prepare("DELETE FROM users WHERE user_id=? AND role!='admin'")->execute([$target_id]);
                $usersMsg = 'User deleted.'; $usersMsgType = 'success';
            }
        } catch (Exception $e) {
            $usersMsg = 'Error: '.$e->getMessage(); $usersMsgType = 'error';
        }
    }
}

$allUsers = [];
if ($tab === 'users') {
    try {
        $conn     = getDatabase()->getConnection();
        $allUsers = $conn->query(
            "SELECT u.user_id, u.email, u.first_name, u.last_name,
                    u.phone_number, u.is_active, u.role,
                    u.created_at, u.last_login,
                    COUNT(i.id) AS upload_count
             FROM users u
             LEFT JOIN identifications i ON i.user_id = u.user_id
             WHERE u.role = 'user'
             GROUP BY u.user_id
             ORDER BY u.created_at DESC"
        )->fetchAll();
    } catch (Exception $e) { $allUsers = []; }
}

// ── Gallery tab: All Identifications with filters ─────────────
$galleryMsg     = '';
$galleryMsgType = '';

if ($tab === 'gallery' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action_g  = $_POST['action_g'] ?? '';
    $ident_id  = $_POST['ident_id'] ?? '';
    try {
        $conn = getDatabase()->getConnection();
        // Shared helper: delete one identification fully
        $deleteOne = function($id) use ($conn) {
            // 1. Fetch file info FIRST before deleting anything
            $row = $conn->prepare("SELECT filename, upload_id FROM identifications WHERE identification_id=?");
            $row->execute([$id]);
            $r = $row->fetch();
            // 2. Delete likes & comments (FK safe)
            $conn->prepare("DELETE FROM feed_likes    WHERE identification_id=?")->execute([$id]);
            $conn->prepare("DELETE FROM feed_comments WHERE identification_id=?")->execute([$id]);
            // 3. Delete identification record
            $conn->prepare("DELETE FROM identifications WHERE identification_id=?")->execute([$id]);
            // 4. Delete upload record
            if ($r && !empty($r['upload_id'])) {
                $conn->prepare("DELETE FROM uploads WHERE upload_id=?")->execute([$r['upload_id']]);
            }
            // 5. Delete image file from disk
            if ($r && !empty($r['filename'])) {
                $fp = UPLOAD_DIR . $r['filename'];
                if (file_exists($fp)) @unlink($fp);
            }
        };

        if ($action_g === 'delete_ident') {
            if (!empty($ident_id)) {
                $deleteOne($ident_id);
                $galleryMsg = 'Identification deleted.'; $galleryMsgType = 'success';
            }

        } elseif ($action_g === 'bulk_delete_selected') {
            $selectedIds = $_POST['selected_ids'] ?? [];
            if (!is_array($selectedIds)) $selectedIds = [];
            $selectedIds = array_filter(array_map('trim', $selectedIds));
            $deleted = 0;
            foreach ($selectedIds as $selId) {
                $deleteOne($selId);
                $deleted++;
            }
            $galleryMsg = "Deleted $deleted identification(s) successfully."; $galleryMsgType = 'success';
        }
    } catch (Exception $e) {
        $galleryMsg = 'Error: '.$e->getMessage(); $galleryMsgType = 'error';
    }
}

$galleryData    = [];
$galleryStats   = [];
$galleryFilter  = $_GET['gf_species'] ?? 'all';
$galleryUser    = $_GET['gf_user']    ?? 'all';
$gallerySort    = $_GET['gf_sort']    ?? 'newest';

if ($tab === 'gallery') {
    try {
        $conn = getDatabase()->getConnection();

        // Stats
        $galleryStats['total']    = $conn->query("SELECT COUNT(*) FROM identifications")->fetchColumn();
        $galleryStats['identified']= $conn->query("SELECT COUNT(*) FROM identifications WHERE species_name != 'Unknown' AND species_name IS NOT NULL AND species_name != ''")->fetchColumn();
        $galleryStats['unknown']  = $conn->query("SELECT COUNT(*) FROM identifications WHERE species_name='Unknown' OR species_name IS NULL OR species_name=''")->fetchColumn();
        $galleryStats['users']    = $conn->query("SELECT COUNT(DISTINCT user_id) FROM identifications WHERE user_id IS NOT NULL")->fetchColumn();

        // Species list for filter
        $gallerySpecies = $conn->query("SELECT DISTINCT species_name FROM identifications WHERE species_name IS NOT NULL AND species_name != '' ORDER BY species_name")->fetchAll(PDO::FETCH_COLUMN);

        // User list for filter
        $galleryUsers = $conn->query("SELECT DISTINCT u.user_id, u.first_name, u.last_name, u.email FROM identifications i LEFT JOIN users u ON i.user_id = u.user_id WHERE i.user_id IS NOT NULL AND u.user_id IS NOT NULL ORDER BY u.first_name")->fetchAll();

        // Build query with filters
        $where  = [];
        $params = [];
        if ($galleryFilter !== 'all') { $where[] = "i.species_name = ?"; $params[] = $galleryFilter; }
        if ($galleryUser   !== 'all') { $where[] = "i.user_id = ?";      $params[] = $galleryUser; }
        $whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

        $orderSQL = match($gallerySort) {
            'oldest'     => "ORDER BY i.identification_time ASC",
            'species'    => "ORDER BY i.species_name ASC, i.identification_time DESC",
            'confidence' => "ORDER BY i.confidence DESC",
            'user'       => "ORDER BY u.first_name ASC",
            default      => "ORDER BY i.identification_time DESC",
        };

        $galleryData = $conn->prepare("
            SELECT i.identification_id, i.filename, i.original_name, i.species_name,
                   i.confidence, i.identification_time, i.location,
                   u.first_name, u.last_name, u.email, u.user_id,
                   (SELECT COUNT(*) FROM feed_likes l WHERE l.identification_id = i.identification_id) AS likes,
                   (SELECT COUNT(*) FROM feed_comments c WHERE c.identification_id = i.identification_id) AS comments
            FROM identifications i
            LEFT JOIN users u ON i.user_id = u.user_id
            $whereSQL
            $orderSQL
            LIMIT 200
        ");
        $galleryData->execute($params);
        $galleryData = $galleryData->fetchAll();

    } catch (Exception $e) { $galleryData = []; $galleryStats = []; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel — MKfinder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary:       #2d6a4f;
            --primary-light: #40916c;
            --accent:        #74c69d;
            --bg-dark:       #0d1b2a;
            --bg-sidebar:    #0f2236;
            --bg-body:       #f0f7f4;
            --text-main:     #1a1a2e;
            --text-muted:    #6b7280;
            --border:        #e5e7eb;
            --radius:        16px;
            --radius-sm:     10px;
            --shadow-sm:     0 2px 8px rgba(0,0,0,.06);
            --shadow-md:     0 8px 30px rgba(0,0,0,.12);
            --transition:    all .3s cubic-bezier(.4,0,.2,1);
            --sidebar-w:     260px;
        }

        * { margin:0; padding:0; box-sizing:border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-body);
            color: var(--text-main);
            display: flex;
            min-height: 100vh;
        }

        /* ── SIDEBAR ── */
        .sidebar {
            width: var(--sidebar-w);
            background: var(--bg-sidebar);
            min-height: 100vh;
            position: fixed;
            top: 0; left: 0;
            display: flex;
            flex-direction: column;
            z-index: 100;
            border-right: 1px solid rgba(116,198,157,.1);
        }

        .sidebar-brand {
            padding: 28px 24px 20px;
            border-bottom: 1px solid rgba(255,255,255,.06);
        }

        .sidebar-brand .logo-mk {
            background: linear-gradient(135deg, #40916c, #74c69d);
            color: #fff;
            font-weight: 800;
            font-size: .95rem;
            padding: 6px 10px;
            border-radius: 8px;
            letter-spacing: 1px;
            display: inline-block;
            margin-bottom: 4px;
        }

        .sidebar-brand .brand-name {
            font-family: 'Playfair Display', serif;
            font-size: 1.3rem;
            color: #fff;
            font-weight: 700;
            margin-left: 8px;
        }

        .admin-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(248,113,113,.15);
            border: 1px solid rgba(248,113,113,.3);
            color: #f87171;
            font-size: .68rem;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 50px;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-top: 8px;
        }

        /* Admin info */
        .sidebar-admin {
            padding: 16px 24px;
            border-bottom: 1px solid rgba(255,255,255,.06);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .admin-avatar {
            width: 38px; height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, #40916c, #74c69d);
            display: flex; align-items: center; justify-content: center;
            font-size: .9rem; color: #fff; font-weight: 700;
            flex-shrink: 0;
        }

        .admin-info .admin-name {
            font-size: .85rem; font-weight: 600; color: #fff;
        }

        .admin-info .admin-role {
            font-size: .72rem; color: rgba(255,255,255,.4);
        }

        /* Nav links */
        .sidebar-nav {
            flex: 1;
            padding: 16px 12px;
            overflow-y: auto;
        }

        .nav-section-label {
            font-size: .65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: rgba(255,255,255,.25);
            padding: 12px 12px 6px;
        }

        .nav-link-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 14px;
            border-radius: var(--radius-sm);
            color: rgba(255,255,255,.55);
            text-decoration: none;
            font-size: .875rem;
            font-weight: 500;
            transition: var(--transition);
            margin-bottom: 2px;
            cursor: pointer;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
        }

        .nav-link-item:hover {
            background: rgba(116,198,157,.1);
            color: #74c69d;
            text-decoration: none;
        }

        .nav-link-item.active {
            background: linear-gradient(135deg, rgba(64,145,108,.25), rgba(116,198,157,.15));
            color: #74c69d;
            border: 1px solid rgba(116,198,157,.2);
        }

        .nav-link-item .nav-icon {
            width: 32px; height: 32px;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: .8rem;
            background: rgba(255,255,255,.05);
            flex-shrink: 0;
            transition: var(--transition);
        }

        .nav-link-item.active .nav-icon,
        .nav-link-item:hover .nav-icon {
            background: rgba(116,198,157,.2);
        }

        .nav-badge {
            margin-left: auto;
            background: #f87171;
            color: #fff;
            font-size: .65rem;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 50px;
        }

        /* Sidebar logout */
        .sidebar-footer {
            padding: 16px 12px;
            border-top: 1px solid rgba(255,255,255,.06);
        }

        .btn-logout {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: var(--radius-sm);
            color: rgba(248,113,113,.7);
            font-size: .875rem;
            font-weight: 500;
            cursor: pointer;
            border: 1px solid rgba(248,113,113,.2);
            background: rgba(248,113,113,.05);
            width: 100%;
            transition: var(--transition);
        }

        .btn-logout:hover {
            background: rgba(248,113,113,.15);
            color: #f87171;
        }

        /* ── MAIN CONTENT ── */
        .main-content {
            margin-left: var(--sidebar-w);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Top bar */
        .topbar {
            background: #fff;
            padding: 16px 32px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
            box-shadow: var(--shadow-sm);
        }

        .topbar-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .topbar-subtitle {
            font-size: .78rem;
            color: var(--text-muted);
            margin-top: 1px;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .topbar-time {
            font-size: .78rem;
            color: var(--text-muted);
            background: var(--bg-body);
            padding: 6px 14px;
            border-radius: 50px;
            border: 1px solid var(--border);
        }

        /* ── PAGE BODY ── */
        .page-body {
            padding: 32px;
            flex: 1;
        }

        /* ── STAT CARDS ── */
        .stat-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: #fff;
            border-radius: var(--radius);
            padding: 24px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
        }

        .stat-card.green::before  { background: linear-gradient(90deg, #40916c, #74c69d); }
        .stat-card.blue::before   { background: linear-gradient(90deg, #0077b6, #48cae4); }
        .stat-card.orange::before { background: linear-gradient(90deg, #e76f51, #f4a261); }
        .stat-card.red::before    { background: linear-gradient(90deg, #e63946, #f87171); }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .stat-icon {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem;
            margin-bottom: 16px;
        }

        .stat-icon.green  { background: rgba(64,145,108,.1);  color: #40916c; }
        .stat-icon.blue   { background: rgba(0,119,182,.1);   color: #0077b6; }
        .stat-icon.orange { background: rgba(231,111,81,.1);  color: #e76f51; }
        .stat-icon.red    { background: rgba(230,57,70,.1);   color: #e63946; }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-main);
            line-height: 1;
            margin-bottom: 6px;
        }

        .stat-label {
            font-size: .8rem;
            color: var(--text-muted);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        /* ── QUICK ACTION CARDS ── */
        .section-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title i {
            color: var(--primary-light);
        }

        .action-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 32px;
        }

        .action-card {
            background: #fff;
            border-radius: var(--radius);
            padding: 20px 22px;
            border: 1px solid var(--border);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
        }

        .action-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
            border-color: rgba(116,198,157,.3);
            text-decoration: none;
        }

        .action-card-icon {
            width: 44px; height: 44px;
            border-radius: 12px;
            background: linear-gradient(135deg, #40916c, #74c69d);
            display: flex; align-items: center; justify-content: center;
            color: #fff;
            font-size: .95rem;
            flex-shrink: 0;
        }

        .action-card-text .act-title {
            font-size: .9rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .action-card-text .act-desc {
            font-size: .75rem;
            color: var(--text-muted);
            margin-top: 2px;
        }

        /* ── TAB CONTENT ── */
        .tab-content-area {
            background: #fff;
            border-radius: var(--radius);
            padding: 28px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .coming-soon {
            text-align: center;
            padding: 60px 20px;
        }

        .coming-soon .cs-icon {
            width: 80px; height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(64,145,108,.1), rgba(116,198,157,.1));
            border: 2px solid rgba(116,198,157,.2);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 20px;
            font-size: 1.8rem;
            color: var(--primary-light);
        }

        .coming-soon h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 8px;
        }

        .coming-soon p {
            color: var(--text-muted);
            font-size: .9rem;
            max-width: 320px;
            margin: 0 auto;
        }

        /* ── RESPONSIVE ── */
        @media(max-width: 992px) {
            .stat-cards  { grid-template-columns: repeat(2, 1fr); }
            .action-cards { grid-template-columns: repeat(2, 1fr); }
        }

        /* ── SPECIES TAB ── */
        .sp-alert {
            display:flex; align-items:center; gap:10px;
            padding:14px 18px; border-radius:12px;
            font-size:.875rem; font-weight:500;
            margin-bottom:24px; border:1px solid;
        }
        .sp-alert.success { background:#f0faf4; color:#2d6a4f; border-color:#74c69d55; }
        .sp-alert.error   { background:#fff5f5; color:#c0392b; border-color:#f8717155; }

        .sp-layout {
            display:grid;
            grid-template-columns: 380px 1fr;
            gap:24px;
            align-items:start;
        }

        .sp-form-panel, .sp-list-panel {
            background:#fff;
            border-radius:var(--radius);
            border:1px solid var(--border);
            box-shadow:var(--shadow-sm);
            overflow:hidden;
        }

        .panel-head {
            display:flex; align-items:center; gap:10px;
            padding:16px 20px;
            background:linear-gradient(135deg,#f8fffe,#f0faf4);
            border-bottom:1px solid var(--border);
            font-size:.9rem; font-weight:700; color:var(--text-main);
        }

        .panel-head i { color:var(--primary-light); }

        .count-pill {
            margin-left:auto;
            background:var(--primary-light);
            color:#fff; font-size:.72rem; font-weight:700;
            padding:2px 9px; border-radius:50px;
        }

        /* Form inside panel */
        .sp-form-panel form { padding:20px; }

        .photo-upload-area {
            width:100%; height:160px;
            border:2px dashed rgba(116,198,157,.4);
            border-radius:12px;
            display:flex; flex-direction:column;
            align-items:center; justify-content:center;
            cursor:pointer; margin-bottom:18px;
            transition:var(--transition);
            overflow:hidden; position:relative;
            background:#fafffe;
        }
        .photo-upload-area:hover { border-color:#40916c; background:#f0faf4; }

        .field-group {
            margin-bottom:14px;
        }
        .field-group label {
            display:block; font-size:.75rem; font-weight:600;
            text-transform:uppercase; letter-spacing:.5px;
            color:var(--text-muted); margin-bottom:6px;
        }
        .req { color:#e63946; }
        .field-group input,
        .field-group textarea,
        .field-group select {
            width:100%;
            background:#fafffe;
            border:1.5px solid rgba(116,198,157,.25);
            border-radius:10px;
            padding:10px 14px;
            font-size:.875rem;
            color:var(--text-main);
            font-family:'Inter',sans-serif;
            outline:none;
            transition:var(--transition);
            resize:vertical;
        }
        .field-group input:focus,
        .field-group textarea:focus,
        .field-group select:focus {
            border-color:#40916c;
            box-shadow:0 0 0 3px rgba(64,145,108,.1);
        }

        .field-row {
            display:grid; grid-template-columns:1fr 1fr; gap:12px;
        }

        .btn-submit {
            flex:1;
            background:linear-gradient(135deg,#40916c,#74c69d);
            color:#fff; border:none; border-radius:10px;
            padding:11px 20px; font-size:.875rem; font-weight:600;
            font-family:'Inter',sans-serif; cursor:pointer;
            display:inline-flex; align-items:center; gap:8px;
            transition:var(--transition);
        }
        .btn-submit:hover { transform:translateY(-2px); box-shadow:0 6px 18px rgba(64,145,108,.35); }

        .btn-cancel-form {
            background:transparent;
            color:var(--text-muted);
            border:1.5px solid var(--border);
            border-radius:10px; padding:11px 16px;
            font-size:.875rem; font-family:'Inter',sans-serif;
            cursor:pointer; display:inline-flex; align-items:center; gap:6px;
            transition:var(--transition);
        }
        .btn-cancel-form:hover { border-color:#f87171; color:#f87171; }

        /* Species list */
        .sp-items { padding:12px; max-height:620px; overflow-y:auto; }

        .sp-item {
            display:flex; align-items:center; gap:14px;
            padding:14px; border-radius:12px;
            border:1px solid var(--border);
            margin-bottom:10px;
            transition:var(--transition);
            background:#fafffe;
        }
        .sp-item:hover {
            border-color:rgba(116,198,157,.3);
            box-shadow:0 4px 16px rgba(45,106,79,.08);
        }

        .sp-item-img {
            width:60px; height:60px; border-radius:10px;
            overflow:hidden; flex-shrink:0;
            background:linear-gradient(135deg,#e8f5e9,#c8e6c9);
            display:flex; align-items:center; justify-content:center;
            color:#40916c; font-size:1.4rem;
        }
        .sp-item-img img { width:100%; height:100%; object-fit:cover; }

        .sp-item-info { flex:1; min-width:0; }
        .sp-item-name { font-size:.9rem; font-weight:700; color:var(--text-main); }
        .sp-item-sci  { font-size:.75rem; color:var(--text-muted); font-style:italic; margin-bottom:6px; }

        .sp-status-pill {
            font-size:.68rem; font-weight:600;
            padding:2px 9px; border-radius:50px;
            border:1px solid; display:inline-block;
        }

        .sp-item-actions { display:flex; gap:8px; flex-shrink:0; }

        .btn-edit-sp, .btn-del-sp {
            width:34px; height:34px; border-radius:8px;
            border:none; cursor:pointer;
            display:flex; align-items:center; justify-content:center;
            font-size:.8rem; transition:var(--transition);
        }
        .btn-edit-sp { background:rgba(64,145,108,.1); color:#40916c; }
        .btn-edit-sp:hover { background:#40916c; color:#fff; }
        .btn-del-sp  { background:rgba(230,57,70,.08); color:#e63946; }
        .btn-del-sp:hover  { background:#e63946; color:#fff; }

        @media(max-width:900px){
            .sp-layout { grid-template-columns:1fr; }
        }

        /* scrollbar */
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: var(--bg-body); }
        ::-webkit-scrollbar-thumb { background: #40916c; border-radius: 3px; }

        /* ── USERS TAB ── */
        .users-toolbar {
            display:flex; align-items:center; gap:12px;
            margin-bottom:20px; flex-wrap:wrap;
        }
        .users-search {
            display:flex; align-items:center; gap:10px;
            background:#fff; border:1.5px solid rgba(116,198,157,.25);
            border-radius:10px; padding:10px 16px; flex:1; max-width:360px;
        }
        .users-search i { color:#9ca3af; }
        .users-search input {
            border:none; outline:none; font-size:.875rem;
            font-family:'Inter',sans-serif; width:100%; background:none; color:var(--text-main);
        }

        .users-table {
            width:100%; border-collapse:collapse;
        }
        .users-table thead tr {
            background:linear-gradient(135deg,#f8fffe,#f0faf4);
            border-bottom:2px solid var(--border);
        }
        .users-table th {
            padding:13px 16px; font-size:.72rem; font-weight:700;
            text-transform:uppercase; letter-spacing:.5px; color:var(--text-muted);
            text-align:left; white-space:nowrap;
        }
        .users-table tbody tr {
            border-bottom:1px solid var(--border);
            transition:var(--transition);
        }
        .users-table tbody tr:hover { background:#f8fffe; }
        .users-table td { padding:13px 16px; vertical-align:middle; }

        .user-avatar {
            width:36px; height:36px; border-radius:50%;
            background:linear-gradient(135deg,#40916c,#74c69d);
            display:flex; align-items:center; justify-content:center;
            color:#fff; font-size:.8rem; font-weight:700; flex-shrink:0;
        }

        .upload-badge {
            background:rgba(64,145,108,.1); color:#40916c;
            font-size:.75rem; font-weight:700;
            padding:3px 10px; border-radius:50px;
            border:1px solid rgba(64,145,108,.2);
        }

        .status-dot {
            display:inline-flex; align-items:center; gap:5px;
            font-size:.75rem; font-weight:600; padding:3px 10px;
            border-radius:50px; border:1px solid;
        }
        .status-dot::before {
            content:''; width:6px; height:6px; border-radius:50%;
        }
        .status-dot.active   { background:rgba(64,145,108,.1); color:#40916c; border-color:rgba(64,145,108,.25); }
        .status-dot.active::before   { background:#40916c; }
        .status-dot.inactive { background:rgba(248,113,113,.1); color:#f87171; border-color:rgba(248,113,113,.25); }
        .status-dot.inactive::before { background:#f87171; }

        .btn-ua {
            width:30px; height:30px; border-radius:8px; border:none;
            cursor:pointer; display:flex; align-items:center;
            justify-content:center; font-size:.75rem; transition:var(--transition);
        }
        .btn-ua.green  { background:rgba(64,145,108,.1);  color:#40916c; }
        .btn-ua.green:hover  { background:#40916c; color:#fff; }
        .btn-ua.orange { background:rgba(244,162,97,.1);  color:#f4a261; }
        .btn-ua.orange:hover { background:#f4a261; color:#fff; }
        .btn-ua.red    { background:rgba(248,113,113,.1); color:#f87171; }
        .btn-ua.red:hover    { background:#f87171; color:#fff; }

        /* ── GALLERY TAB ── */
        .gallery-grid {
            display:grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap:20px;
        }
        .gal-card {
            background:#fff; border-radius:var(--radius);
            border:1px solid var(--border); overflow:hidden;
            box-shadow:var(--shadow-sm); transition:var(--transition);
        }
        .gal-card:hover { transform:translateY(-3px); box-shadow:var(--shadow-md); }
        .gal-img {
            height:180px; position:relative; background:#f0f7f4; overflow:hidden;
        }
        .gal-img img { width:100%; height:100%; object-fit:cover; }
        .gal-download {
            position:absolute; top:10px; right:10px;
            background:rgba(13,27,42,.7); color:#fff;
            width:32px; height:32px; border-radius:8px;
            display:flex; align-items:center; justify-content:center;
            font-size:.8rem; text-decoration:none; transition:var(--transition);
        }
        .gal-download:hover { background:#40916c; color:#fff; }
        .gal-status {
            position:absolute; bottom:10px; left:10px;
            font-size:.68rem; font-weight:700; padding:3px 9px;
            border-radius:50px; border:1px solid;
        }
        .gal-body { padding:14px; }
        .gal-birdname { font-weight:700; font-size:.9rem; color:var(--text-main); margin-bottom:6px; }
        .gal-note { font-size:.78rem; color:var(--text-muted); margin-bottom:10px; line-height:1.5; }
        .gal-user { display:flex; align-items:center; gap:8px; margin-bottom:12px; }
        .gal-actions { border-top:1px solid var(--border); padding-top:10px; }

        /* ── SYSTEM HEALTH TAB ── */
        .sys-grid {
            display:grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap:16px;
        }
        .sys-card {
            background:#fff; border-radius:var(--radius);
            border:1.5px solid var(--border);
            padding:20px; display:flex; align-items:flex-start;
            gap:16px; box-shadow:var(--shadow-sm);
            position:relative; overflow:hidden;
        }
        .sys-card::before {
            content:''; position:absolute; top:0; left:0;
            width:4px; height:100%;
        }
        .sys-card.ok::before   { background:#40916c; }
        .sys-card.warn::before { background:#f4a261; }
        .sys-card.fail::before { background:#f87171; }
        .sys-icon {
            width:44px; height:44px; border-radius:12px;
            display:flex; align-items:center; justify-content:center;
            font-size:1.1rem; flex-shrink:0;
            background:linear-gradient(135deg,#f0faf4,#e8f5e9);
            color:#40916c;
        }
        .sys-info { flex:1; min-width:0; }
        .sys-title { font-size:.72rem; font-weight:700; text-transform:uppercase;
                     letter-spacing:.5px; color:var(--text-muted); margin-bottom:4px; }
        .sys-val   { font-size:1rem; font-weight:700; color:var(--text-main); margin-bottom:3px; }
        .sys-detail{ font-size:.75rem; color:var(--text-muted); }
        .sys-dot {
            width:10px; height:10px; border-radius:50%; flex-shrink:0; margin-top:4px;
        }
        .sys-dot.green  { background:#40916c; box-shadow:0 0 0 3px rgba(64,145,108,.2); }
        .sys-dot.orange { background:#f4a261; box-shadow:0 0 0 3px rgba(244,162,97,.2); }
        .sys-dot.red    { background:#f87171; box-shadow:0 0 0 3px rgba(248,113,113,.2); }
    </style>
</head>
<body>

<!-- ══════════ SIDEBAR ══════════ -->
<aside class="sidebar">

    <!-- Brand -->
    <div class="sidebar-brand">
        <div>
            <span class="logo-mk">MK</span>
            <span class="brand-name">finder</span>
        </div>
        <div class="admin-badge">
            <i class="fas fa-shield-alt"></i> Admin Panel
        </div>
    </div>

    <!-- Admin info -->
    <div class="sidebar-admin">
        <div class="admin-avatar">
            <?php echo strtoupper(substr($adminName, 0, 1)); ?>
        </div>
        <div class="admin-info">
            <div class="admin-name"><?php echo htmlspecialchars($adminName); ?></div>
            <div class="admin-role">Super Administrator</div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
        <div class="nav-section-label">Main</div>

        <a href="?tab=dashboard"
           class="nav-link-item <?php echo $tab==='dashboard'?'active':''; ?>">
            <span class="nav-icon"><i class="fas fa-th-large"></i></span>
            Dashboard
        </a>

        <div class="nav-section-label">Management</div>

        <a href="?tab=species"
           class="nav-link-item <?php echo $tab==='species'?'active':''; ?>">
            <span class="nav-icon"><i class="fas fa-feather-alt"></i></span>
            Species Database
        </a>

        <a href="?tab=gallery"
           class="nav-link-item <?php echo $tab==='gallery'?'active':''; ?>">
            <span class="nav-icon"><i class="fas fa-images"></i></span>
            Gallery Management
        </a>

        <a href="?tab=submissions"
           class="nav-link-item <?php echo $tab==='submissions'?'active':''; ?>">
            <span class="nav-icon"><i class="fas fa-question-circle"></i></span>
            Unknown Submissions
            <?php if ($stats['submissions'] > 0): ?>
                <span class="nav-badge"><?php echo $stats['submissions']; ?></span>
            <?php endif; ?>
        </a>

        <a href="?tab=users"
           class="nav-link-item <?php echo $tab==='users'?'active':''; ?>">
            <span class="nav-icon"><i class="fas fa-users"></i></span>
            User Management
        </a>

        <div class="nav-section-label">System</div>

        <a href="?tab=system"
           class="nav-link-item <?php echo $tab==='system'?'active':''; ?>">
            <span class="nav-icon"><i class="fas fa-server"></i></span>
            System Health
        </a>

        <a href="index.html" class="nav-link-item">
            <span class="nav-icon"><i class="fas fa-globe"></i></span>
            View Website
        </a>
    </nav>

    <!-- Logout -->
    <div class="sidebar-footer">
        <button class="btn-logout" onclick="adminLogout()">
            <i class="fas fa-sign-out-alt"></i>
            Logout
        </button>
    </div>
</aside>

<!-- ══════════ MAIN ══════════ -->
<div class="main-content">

    <!-- Topbar -->
    <div class="topbar">
        <div>
            <div class="topbar-title">
                <?php
                $titles = [
                    'dashboard'   => '🏠 Dashboard',
                    'species'     => '🐦 Species Database',
                    'gallery'     => '🖼️ Gallery Management',
                    'submissions' => '❓ Unknown Submissions',
                    'users'       => '👥 User Management',
                    'system'      => '⚙️ System Health',
                ];
                echo $titles[$tab] ?? 'Dashboard';
                ?>
            </div>
            <div class="topbar-subtitle">MKfinder Admin Panel</div>
        </div>
        <div class="topbar-right">
            <span class="topbar-time" id="clockDisplay"></span>
        </div>
    </div>

    <!-- Page body -->
    <div class="page-body">

        <?php if ($tab === 'dashboard'): ?>
        <!-- ══ DASHBOARD ══ -->

        <!-- Stat cards -->
        <div class="stat-cards">
            <div class="stat-card green">
                <div class="stat-icon green"><i class="fas fa-users"></i></div>
                <div class="stat-value"><?php echo $stats['users']; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card blue">
                <div class="stat-icon blue"><i class="fas fa-camera"></i></div>
                <div class="stat-value"><?php echo $stats['identifications']; ?></div>
                <div class="stat-label">Identifications</div>
            </div>
            <div class="stat-card orange">
                <div class="stat-icon orange"><i class="fas fa-feather-alt"></i></div>
                <div class="stat-value"><?php echo $stats['species']; ?></div>
                <div class="stat-label">Bird Species</div>
            </div>
            <div class="stat-card red">
                <div class="stat-icon red"><i class="fas fa-bell"></i></div>
                <div class="stat-value"><?php echo $stats['submissions']; ?></div>
                <div class="stat-label">Pending Submissions</div>
            </div>
        </div>

        <!-- Quick actions -->
        <p class="section-title">
            <i class="fas fa-bolt"></i> Quick Actions
        </p>
        <div class="action-cards">
            <a href="?tab=species" class="action-card">
                <div class="action-card-icon"><i class="fas fa-plus"></i></div>
                <div class="action-card-text">
                    <div class="act-title">Add Bird Species</div>
                    <div class="act-desc">Add a new species to the database</div>
                </div>
            </a>
            <a href="?tab=users" class="action-card">
                <div class="action-card-icon"><i class="fas fa-user-cog"></i></div>
                <div class="action-card-text">
                    <div class="act-title">Manage Users</div>
                    <div class="act-desc">View, activate or remove users</div>
                </div>
            </a>
            <a href="?tab=submissions" class="action-card">
                <div class="action-card-icon"><i class="fas fa-check-circle"></i></div>
                <div class="action-card-text">
                    <div class="act-title">Review Submissions</div>
                    <div class="act-desc"><?php echo $stats['submissions']; ?> pending unknown birds</div>
                </div>
            </a>
            <a href="?tab=gallery" class="action-card">
                <div class="action-card-icon"><i class="fas fa-images"></i></div>
                <div class="action-card-text">
                    <div class="act-title">Gallery Management</div>
                    <div class="act-desc">View user uploads & requests</div>
                </div>
            </a>
            <a href="?tab=system" class="action-card">
                <div class="action-card-icon"><i class="fas fa-server"></i></div>
                <div class="action-card-text">
                    <div class="act-title">System Health</div>
                    <div class="act-desc">Check DB & model status</div>
                </div>
            </a>
            <a href="index.html" class="action-card">
                <div class="action-card-icon"><i class="fas fa-globe"></i></div>
                <div class="action-card-text">
                    <div class="act-title">View Website</div>
                    <div class="act-desc">Open the public site</div>
                </div>
            </a>
        </div>

        <?php elseif ($tab === 'species'): ?>
        <!-- ══ SPECIES TAB ══ -->

        <?php if ($speciesMsg): ?>
        <div class="sp-alert <?php echo $speciesMsgType; ?>" id="spAlert">
            <i class="fas <?php echo $speciesMsgType==='success'?'fa-check-circle':'fa-exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($speciesMsg); ?>
            <button onclick="document.getElementById('spAlert').style.display='none'" style="margin-left:auto;background:none;border:none;cursor:pointer;font-size:1rem;color:inherit;">×</button>
        </div>
        <?php endif; ?>

        <div class="sp-layout">

            <!-- ── ADD FORM ── -->
            <div class="sp-form-panel">
                <div class="panel-head">
                    <i class="fas fa-plus-circle"></i>
                    <span id="formPanelTitle">Add New Species</span>
                </div>

                <form method="POST" enctype="multipart/form-data" id="speciesForm">
                    <input type="hidden" name="action_sp"   id="actionInput"    value="add">
                    <input type="hidden" name="species_id"  id="speciesIdInput" value="">
                    <input type="hidden" name="model_label" id="f_model_label"  value="">
                    <input type="hidden" name="name"        id="f_name"         value="">

                    <div class="photo-upload-area" id="photoArea" onclick="document.getElementById('photoInput').click()">
                        <img id="photoPreview" src="" alt="" style="display:none;width:100%;height:100%;object-fit:cover;border-radius:12px;">
                        <div id="photoPlaceholder">
                            <i class="fas fa-camera" style="font-size:1.8rem;color:#40916c;margin-bottom:8px;"></i>
                            <div style="font-size:.85rem;font-weight:600;color:#1a1a2e;">Upload Bird Photo</div>
                            <div style="font-size:.75rem;color:#9ca3af;margin-top:4px;">JPG, PNG or WEBP</div>
                        </div>
                        <input type="file" name="photo" id="photoInput" accept=".jpg,.jpeg,.png,.webp" style="display:none;" onchange="previewPhoto(this)">
                    </div>

                    <div class="field-group">
                        <label>Bird Species <span class="req">*</span> <small style="color:#74c69d;font-size:.65rem;text-transform:none;font-weight:400;">(AI model species)</small></label>
                        <div style="position:relative;">
                            <input type="text" id="spSearch" placeholder="Type to search e.g. Eagle, Robin…" autocomplete="off"
                                style="padding-right:34px;" oninput="filterSp(this.value)" onfocus="showSpDrop()">
                            <i class="fas fa-chevron-down" onclick="showSpDrop()" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:.75rem;cursor:pointer;"></i>
                            <div id="spDrop" style="display:none;position:fixed;z-index:99999;max-height:220px;overflow-y:auto;background:#fff;border:1.5px solid #40916c;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.15);">
                                <div id="spList"></div>
                            </div>
                        </div>
                        <div id="spBadge" style="display:none;margin-top:6px;padding:7px 12px;background:linear-gradient(135deg,rgba(64,145,108,.08),rgba(116,198,157,.08));border:1px solid rgba(116,198,157,.3);border-radius:8px;font-size:.8rem;color:#2d6a4f;font-weight:600;">
                            <i class="fas fa-check-circle" style="color:#40916c;margin-right:6px;"></i>
                            <span id="spBadgeLabel"></span>
                        </div>
                        <button type="button" id="aiFillBtn" onclick="autoFill()" style="display:none;margin-top:10px;width:100%;padding:10px;background:linear-gradient(135deg,#4285f4,#34a853);color:#fff;border:none;border-radius:10px;font-size:.85rem;font-weight:600;cursor:pointer;font-family:Inter,sans-serif;gap:8px;align-items:center;justify-content:center;">
                            <i class="fas fa-magic"></i> Auto-Fill with AI
                        </button>
                        <div id="aiStatus" style="display:none;margin-top:8px;padding:8px 12px;border-radius:8px;font-size:.8rem;font-weight:500;"></div>
                    </div>

                    <div class="field-group">
                        <label>Scientific Name <span class="req">*</span></label>
                        <input type="text" name="scientific_name" id="f_sci" placeholder="e.g. Haliaeetus leucocephalus" required>
                    </div>
                    <div class="field-group">
                        <label>Description <span class="req">*</span></label>
                        <textarea name="description" id="f_desc" rows="3" placeholder="Brief description of the species..." required></textarea>
                    </div>
                    <div class="field-row">
                        <div class="field-group">
                            <label>Habitat <span class="req">*</span></label>
                            <input type="text" name="habitat" id="f_hab" placeholder="e.g. Forests, coasts" required>
                        </div>
                        <div class="field-group">
                            <label>Diet <span class="req">*</span></label>
                            <input type="text" name="diet" id="f_diet" placeholder="e.g. Fish, small mammals" required>
                        </div>
                    </div>
                    <div class="field-group">
                        <label>Behavior <span class="req">*</span></label>
                        <input type="text" name="behavior" id="f_beh" placeholder="e.g. Solitary, migratory" required>
                    </div>
                    <div class="field-group">
                        <label>Conservation Status</label>
                        <select name="conservation_status" id="f_status">
                            <option value="Least Concern">Least Concern</option>
                            <option value="Near Threatened">Near Threatened</option>
                            <option value="Vulnerable">Vulnerable</option>
                            <option value="Endangered">Endangered</option>
                            <option value="Critically Endangered">Critically Endangered</option>
                            <option value="Extinct in the Wild">Extinct in the Wild</option>
                            <option value="Extinct">Extinct</option>
                        </select>
                    </div>
                    <div class="field-group">
                        <label>Key Characteristics <small style="color:#9ca3af;">(one per line)</small></label>
                        <textarea name="characteristics" id="f_chars" rows="3" placeholder="White head and tail&#10;Yellow curved beak&#10;Large wingspan 6-8 feet"></textarea>
                    </div>

                    <div style="display:flex;gap:10px;margin-top:4px;">
                        <button type="submit" class="btn-submit" id="submitBtn" onclick="return validateSpForm()">
                            <i class="fas fa-plus-circle"></i><span id="submitBtnText">Add Species</span>
                        </button>
                        <button type="button" class="btn-cancel-form" id="cancelEditBtn" onclick="resetForm()" style="display:none;">
                            <i class="fas fa-times"></i>Cancel
                        </button>
                    </div>
                </form>
            </div>

            <!-- ── SPECIES LIST ── -->
            <div class="sp-list-panel">
                <div class="panel-head">
                    <i class="fas fa-list"></i>
                    Species in Database
                    <span class="count-pill"><?php echo count($allSpeciesList); ?></span>
                </div>

                <?php if (empty($allSpeciesList)): ?>
                <div style="text-align:center;padding:40px 20px;color:#9ca3af;">
                    <i class="fas fa-feather-alt" style="font-size:2rem;margin-bottom:12px;display:block;opacity:.4;"></i>
                    No species yet. Add your first one!
                </div>
                <?php else: ?>
                <div class="sp-items">
                    <?php foreach ($allSpeciesList as $sp):
                        $hasImg = !empty($sp['image_path']) && file_exists($sp['image_path']);
                        $charsArr = is_array($sp['characteristics']) ? $sp['characteristics'] : [];
                        $charsStr = implode("\n", $charsArr);
                        $statusColors = [
                            'Least Concern'       => '#40916c',
                            'Near Threatened'     => '#74c69d',
                            'Vulnerable'          => '#f4a261',
                            'Endangered'          => '#e76f51',
                            'Critically Endangered'=> '#e63946',
                            'Extinct in the Wild' => '#9b2226',
                            'Extinct'             => '#370617',
                        ];
                        $sColor = $statusColors[$sp['conservation_status']] ?? '#6b7280';
                    ?>
                    <div class="sp-item">
                        <div class="sp-item-img">
                            <?php if ($hasImg): ?>
                                <img src="<?php echo htmlspecialchars($sp['image_path']); ?>" alt="<?php echo htmlspecialchars($sp['name']); ?>">
                            <?php else: ?>
                                <i class="fas fa-dove"></i>
                            <?php endif; ?>
                        </div>
                        <div class="sp-item-info">
                            <div class="sp-item-name"><?php echo htmlspecialchars($sp['name']); ?></div>
                            <div class="sp-item-sci"><?php echo htmlspecialchars($sp['scientific_name']); ?></div>
                            <span class="sp-status-pill" style="background:<?php echo $sColor; ?>22;color:<?php echo $sColor; ?>;border-color:<?php echo $sColor; ?>44;">
                                <?php echo htmlspecialchars($sp['conservation_status']); ?>
                            </span>
                        </div>
                        <div class="sp-item-actions">
                            <button class="btn-edit-sp" onclick="loadEdit(<?php echo $sp['id']; ?>,
                                <?php echo htmlspecialchars(json_encode($sp['name']),ENT_QUOTES); ?>,
                                <?php echo htmlspecialchars(json_encode($sp['scientific_name']),ENT_QUOTES); ?>,
                                <?php echo htmlspecialchars(json_encode($sp['description']),ENT_QUOTES); ?>,
                                <?php echo htmlspecialchars(json_encode($sp['habitat']),ENT_QUOTES); ?>,
                                <?php echo htmlspecialchars(json_encode($sp['diet']),ENT_QUOTES); ?>,
                                <?php echo htmlspecialchars(json_encode($sp['behavior']),ENT_QUOTES); ?>,
                                <?php echo htmlspecialchars(json_encode($sp['conservation_status']),ENT_QUOTES); ?>,
                                <?php echo htmlspecialchars(json_encode($charsStr),ENT_QUOTES); ?>
                            )">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form method="POST" style="display:inline;" onsubmit="return mkAdminConfirm(event,'Delete <?php echo htmlspecialchars($sp['name'],ENT_QUOTES); ?>?','This action cannot be undone. The species will be permanently removed.')">
                                <input type="hidden" name="action_sp"  value="delete">
                                <input type="hidden" name="species_id" value="<?php echo $sp['id']; ?>">
                                <button type="submit" class="btn-del-sp"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div><!-- /sp-layout -->

        <?php elseif ($tab === 'users'): ?>
        <!-- ══ USERS TAB ══ -->

        <?php if ($usersMsg): ?>
        <div class="sp-alert <?php echo $usersMsgType; ?>" id="usersAlert">
            <i class="fas <?php echo $usersMsgType==='success'?'fa-check-circle':'fa-exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($usersMsg); ?>
            <button onclick="this.parentElement.style.display='none'" style="margin-left:auto;background:none;border:none;cursor:pointer;font-size:1rem;color:inherit;">×</button>
        </div>
        <?php endif; ?>

        <!-- Search bar -->
        <div class="users-toolbar">
            <div class="users-search">
                <i class="fas fa-search"></i>
                <input type="text" id="userSearch" placeholder="Search by name or email…" oninput="filterUsers(this.value)">
            </div>
            <span class="count-pill" style="margin-left:auto;"><?php echo count($allUsers); ?> users</span>
        </div>

        <!-- Users Table -->
        <div class="tab-content-area" style="padding:0;overflow:hidden;">
            <?php if (empty($allUsers)): ?>
            <div style="text-align:center;padding:60px 20px;color:#9ca3af;">
                <i class="fas fa-users" style="font-size:2.5rem;margin-bottom:16px;display:block;opacity:.3;"></i>
                <h3 style="font-family:'Playfair Display',serif;color:#1a1a2e;margin-bottom:8px;">No Users Yet</h3>
                <p>Registered users will appear here.</p>
            </div>
            <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="users-table" id="usersTable">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Uploads</th>
                            <th>Joined</th>
                            <th>Last Login</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($allUsers as $u):
                        $fullName  = trim(($u['first_name'].' '.$u['last_name']));
                        $initials  = strtoupper(substr($u['first_name']??'U',0,1).substr($u['last_name']??'',0,1));
                        $joinDate  = date('M j, Y', strtotime($u['created_at']));
                        $lastLogin = $u['last_login'] ? date('M j, Y', strtotime($u['last_login'])) : 'Never';
                    ?>
                    <tr class="user-row" data-search="<?php echo strtolower(htmlspecialchars($fullName.' '.$u['email'])); ?>">
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div class="user-avatar"><?php echo $initials ?: 'U'; ?></div>
                                <div>
                                    <div style="font-weight:600;font-size:.875rem;color:var(--text-main);">
                                        <?php echo htmlspecialchars($fullName ?: 'No Name'); ?>
                                    </div>
                                    <div style="font-size:.72rem;color:var(--text-muted);"><?php echo htmlspecialchars($u['user_id']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td style="font-size:.85rem;"><?php echo htmlspecialchars($u['email']); ?></td>
                        <td style="font-size:.85rem;"><?php echo htmlspecialchars($u['phone_number'] ?: '—'); ?></td>
                        <td>
                            <span class="upload-badge"><?php echo $u['upload_count']; ?></span>
                        </td>
                        <td style="font-size:.82rem;color:var(--text-muted);"><?php echo $joinDate; ?></td>
                        <td style="font-size:.82rem;color:var(--text-muted);"><?php echo $lastLogin; ?></td>
                        <td>
                            <span class="status-dot <?php echo $u['is_active']?'active':'inactive'; ?>">
                                <?php echo $u['is_active']?'Active':'Inactive'; ?>
                            </span>
                        </td>
                        <td>
                            <div style="display:flex;gap:6px;">
                                <?php if ($u['is_active']): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action_u"  value="deactivate">
                                    <input type="hidden" name="target_id" value="<?php echo htmlspecialchars($u['user_id']); ?>">
                                    <button type="submit" class="btn-ua orange" title="Deactivate">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                </form>
                                <?php else: ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action_u"  value="activate">
                                    <input type="hidden" name="target_id" value="<?php echo htmlspecialchars($u['user_id']); ?>">
                                    <button type="submit" class="btn-ua green" title="Activate">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                                <form method="POST" style="display:inline;"
                                      onsubmit="return mkAdminConfirm(event,'Delete User?','This will permanently delete <?php echo htmlspecialchars($fullName ?: $u['email'],ENT_QUOTES); ?>. Cannot be undone.')">
                                    <input type="hidden" name="action_u"  value="delete">
                                    <input type="hidden" name="target_id" value="<?php echo htmlspecialchars($u['user_id']); ?>">
                                    <button type="submit" class="btn-ua red" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <?php elseif ($tab === 'gallery'): ?>
        <!-- ══ ALL IDENTIFICATIONS TAB ══ -->
        <style>
        .gal-stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;}
        .gal-stat-box{background:#fff;border-radius:14px;padding:18px 20px;border:1px solid rgba(116,198,157,.12);box-shadow:0 2px 12px rgba(45,106,79,.06);text-align:center;}
        .gal-stat-num{font-size:1.8rem;font-weight:800;color:#1b4332;line-height:1;}
        .gal-stat-lbl{font-size:.72rem;color:#9ca3af;margin-top:4px;text-transform:uppercase;letter-spacing:.5px;}
        .gal-filters{background:#fff;border-radius:14px;padding:16px 20px;margin-bottom:20px;border:1px solid rgba(116,198,157,.12);display:flex;flex-wrap:wrap;gap:12px;align-items:center;}
        .gal-filters select{background:#f8fffe;border:1.5px solid rgba(116,198,157,.25);border-radius:8px;padding:7px 12px;font-size:.82rem;font-family:'Inter',sans-serif;color:#1a1a2e;outline:none;cursor:pointer;}
        .gal-filters select:focus{border-color:#40916c;}
        .gal-filter-btn{background:linear-gradient(135deg,#40916c,#74c69d);color:#fff;border:none;border-radius:8px;padding:8px 18px;font-size:.82rem;font-weight:600;cursor:pointer;font-family:'Inter',sans-serif;}
        .gal-filter-btn:hover{opacity:.9;}
        .gal-bulk-btn{background:rgba(248,113,113,.1);color:#e63946;border:1px solid rgba(248,113,113,.3);border-radius:8px;padding:8px 16px;font-size:.8rem;font-weight:600;cursor:pointer;font-family:'Inter',sans-serif;display:flex;align-items:center;gap:6px;margin-left:auto;}
        .gal-bulk-btn:hover{background:rgba(248,113,113,.2);}
        .gal-table-wrap{background:#fff;border-radius:14px;border:1px solid rgba(116,198,157,.12);overflow:hidden;box-shadow:0 2px 12px rgba(45,106,79,.06);}
        .gal-table{width:100%;border-collapse:collapse;}
        .gal-table th{background:linear-gradient(135deg,#0d1b2a,#1b4332);color:rgba(255,255,255,.7);font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.5px;padding:12px 16px;text-align:left;}
        .gal-table td{padding:12px 16px;border-bottom:1px solid rgba(116,198,157,.08);font-size:.82rem;color:#1a1a2e;vertical-align:middle;}
        .gal-table tr:last-child td{border-bottom:none;}
        .gal-table tr:hover td{background:rgba(116,198,157,.04);}
        .gal-thumb{width:48px;height:48px;border-radius:8px;object-fit:cover;border:1px solid rgba(116,198,157,.15);}
        .gal-thumb-ph{width:48px;height:48px;border-radius:8px;background:#f0f7f4;display:flex;align-items:center;justify-content:center;color:#9ca3af;font-size:1.1rem;border:1px solid rgba(116,198,157,.15);}
        .gal-species-badge{display:inline-flex;align-items:center;gap:5px;background:#f0f7f4;border:1px solid rgba(116,198,157,.2);color:#2d6a4f;border-radius:50px;padding:3px 10px;font-size:.72rem;font-weight:600;}
        .gal-species-badge.unknown{background:rgba(244,162,97,.1);border-color:rgba(244,162,97,.3);color:#c05621;}
        .gal-conf{font-size:.78rem;font-weight:700;}
        .gal-conf.high{color:#40916c;}
        .gal-conf.medium{color:#f4a261;}
        .gal-conf.low{color:#f87171;}
        .gal-del-btn{background:none;border:1px solid rgba(248,113,113,.3);color:#f87171;border-radius:6px;padding:5px 10px;font-size:.72rem;cursor:pointer;transition:all .2s;}
        .gal-del-btn:hover{background:rgba(248,113,113,.1);}
        .gal-empty{text-align:center;padding:60px 20px;color:#9ca3af;}
        .gal-empty i{font-size:2.5rem;margin-bottom:12px;display:block;opacity:.25;color:#40916c;}
        .gal-result-count{font-size:.8rem;color:#9ca3af;padding:10px 16px;border-bottom:1px solid rgba(116,198,157,.08);background:#f8fffe;}
        </style>

        <?php if ($galleryMsg): ?>
        <div class="sp-alert <?php echo $galleryMsgType; ?>" style="margin-bottom:16px;">
            <i class="fas <?php echo $galleryMsgType==='success'?'fa-check-circle':'fa-exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($galleryMsg); ?>
            <button onclick="this.parentElement.style.display='none'" style="margin-left:auto;background:none;border:none;cursor:pointer;font-size:1rem;color:inherit;">×</button>
        </div>
        </form>
        <?php endif; ?>

        <!-- Stats Row -->
        <div class="gal-stats-row">
            <div class="gal-stat-box">
                <div class="gal-stat-num"><?php echo $galleryStats['total'] ?? 0; ?></div>
                <div class="gal-stat-lbl">Total Identifications</div>
            </div>
            <div class="gal-stat-box">
                <div class="gal-stat-num" style="color:#40916c;"><?php echo $galleryStats['identified'] ?? 0; ?></div>
                <div class="gal-stat-lbl">Successfully Identified</div>
            </div>
            <div class="gal-stat-box">
                <div class="gal-stat-num" style="color:#f4a261;"><?php echo $galleryStats['unknown'] ?? 0; ?></div>
                <div class="gal-stat-lbl">Unknown / Unidentified</div>
            </div>
            <div class="gal-stat-box">
                <div class="gal-stat-num" style="color:#48cae4;"><?php echo $galleryStats['users'] ?? 0; ?></div>
                <div class="gal-stat-lbl">Active Users</div>
            </div>
        </div>

        <!-- Filter Bar -->
        <form method="GET" action="?tab=gallery">
            <input type="hidden" name="tab" value="gallery">
            <div class="gal-filters">
                <i class="fas fa-filter" style="color:#40916c;font-size:.85rem;"></i>
                <select name="gf_species">
                    <option value="all" <?php echo $galleryFilter==='all'?'selected':''; ?>>All Species</option>
                    <?php foreach (($gallerySpecies??[]) as $sp): ?>
                    <option value="<?php echo htmlspecialchars($sp); ?>" <?php echo $galleryFilter===$sp?'selected':''; ?>>
                        <?php echo htmlspecialchars($sp ?: 'Unknown'); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <select name="gf_user">
                    <option value="all" <?php echo $galleryUser==='all'?'selected':''; ?>>All Users</option>
                    <?php foreach (($galleryUsers??[]) as $u): ?>
                    <?php $uname = trim(($u['first_name']??'').' '.($u['last_name']??'')) ?: ($u['email']??'Unknown'); ?>
                    <option value="<?php echo htmlspecialchars($u['user_id']); ?>" <?php echo $galleryUser===$u['user_id']?'selected':''; ?>>
                        <?php echo htmlspecialchars($uname); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <select name="gf_sort">
                    <option value="newest"     <?php echo $gallerySort==='newest'?'selected':''; ?>>Newest First</option>
                    <option value="oldest"     <?php echo $gallerySort==='oldest'?'selected':''; ?>>Oldest First</option>
                    <option value="species"    <?php echo $gallerySort==='species'?'selected':''; ?>>By Species</option>
                    <option value="confidence" <?php echo $gallerySort==='confidence'?'selected':''; ?>>By Confidence</option>
                    <option value="user"       <?php echo $gallerySort==='user'?'selected':''; ?>>By User</option>
                </select>
                <button type="submit" class="gal-filter-btn"><i class="fas fa-search me-1"></i>Apply</button>


            </div>
        </form>

        <!-- Results Table -->
        <?php if (empty($galleryData)): ?>
        <div class="gal-empty">
            <i class="fas fa-images"></i>
            <p>No identifications found matching your filters.</p>
        </div>
        <?php else: ?>
        <!-- HIDDEN FORMS — one for single delete, one for bulk delete -->
        <!-- Kept completely outside the table to avoid nested form issues -->
        <form method="POST" id="singleDeleteForm" action="?tab=gallery" style="display:none;">
            <input type="hidden" name="action_g"  value="delete_ident">
            <input type="hidden" name="ident_id"  id="singleDeleteId" value="">
        </form>
        <form method="POST" id="bulkForm" action="?tab=gallery" style="display:none;">
            <input type="hidden" name="action_g" value="bulk_delete_selected">
            <div id="bulkHiddenIds"></div>
        </form>

        <!-- Bulk action toolbar (shown when items are checked) -->
        <div id="bulkToolbar" style="display:none;background:linear-gradient(135deg,#0d1b2a,#1b4332);border-radius:12px;padding:14px 20px;margin-bottom:12px;align-items:center;gap:16px;">
            <i class="fas fa-check-square" style="color:#74c69d;font-size:1rem;"></i>
            <span id="bulkCount" style="color:#74c69d;font-weight:700;font-size:.9rem;"></span>
            <span style="color:rgba(255,255,255,.4);font-size:.8rem;">selected</span>
            <button type="button" onclick="bulkDeleteSelected()" style="margin-left:auto;background:rgba(248,113,113,.2);color:#f87171;border:1px solid rgba(248,113,113,.4);border-radius:8px;padding:8px 18px;font-size:.82rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:7px;font-family:'Inter',sans-serif;">
                <i class="fas fa-trash-alt"></i> Delete Selected
            </button>
            <button type="button" onclick="clearSelection()" style="background:rgba(255,255,255,.08);color:rgba(255,255,255,.6);border:1px solid rgba(255,255,255,.15);border-radius:8px;padding:8px 14px;font-size:.82rem;cursor:pointer;font-family:'Inter',sans-serif;">
                Cancel
            </button>
        </div>

        <div class="gal-table-wrap">
            <div class="gal-result-count">
                Showing <strong><?php echo count($galleryData); ?></strong> identifications
                <?php if ($galleryFilter !== 'all' || $galleryUser !== 'all'): ?>
                — <a href="?tab=gallery" style="color:#40916c;text-decoration:none;">Clear filters</a>
                <?php endif; ?>
            </div>
            <table class="gal-table">
                <thead>
                    <tr>
                        <th style="width:40px;text-align:center;">
                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)"
                                style="width:16px;height:16px;cursor:pointer;accent-color:#40916c;">
                        </th>
                        <th>Photo</th>
                        <th>Species</th>
                        <th>Confidence</th>
                        <th>User</th>
                        <th>Location</th>
                        <th>Date</th>
                        <th>Engagement</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($galleryData as $row):
                    $imgPath  = !empty($row['filename']) ? 'uploads/'.$row['filename'] : '';
                    $hasImg   = !empty($row['filename']) && file_exists(UPLOAD_DIR.$row['filename']);
                    $uname    = trim(($row['first_name']??'').' '.($row['last_name']??'')) ?: ($row['email'] ?? 'Guest');
                    $initials = strtoupper(substr($row['first_name']??'G',0,1).substr($row['last_name']??'',0,1));
                    $isUnk    = empty($row['species_name']) || $row['species_name'] === 'Unknown';
                    $conf     = round($row['confidence'] ?? 0, 1);
                    $confClass= $conf >= 70 ? 'high' : ($conf >= 30 ? 'medium' : 'low');
                    $date     = date('M j, Y', strtotime($row['identification_time']));
                    $time     = date('g:i A', strtotime($row['identification_time']));
                    $rid      = htmlspecialchars($row['identification_id']);
                ?>
                <tr id="row-<?php echo $rid; ?>">
                    <td style="text-align:center;">
                        <input type="checkbox" data-id="<?php echo $rid; ?>"
                               class="row-checkbox"
                               onchange="updateBulkToolbar()"
                               style="width:16px;height:16px;cursor:pointer;accent-color:#40916c;">
                    </td>
                    <td>
                        <?php if ($hasImg): ?>
                        <img src="<?php echo htmlspecialchars($imgPath); ?>" class="gal-thumb"
                             alt="bird" onclick="window.open('<?php echo htmlspecialchars($imgPath); ?>','_blank')"
                             style="cursor:pointer;" title="Click to view full">
                        <?php else: ?>
                        <div class="gal-thumb-ph"><i class="fas fa-dove"></i></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="gal-species-badge <?php echo $isUnk?'unknown':''; ?>">
                            <i class="fas fa-<?php echo $isUnk?'question-circle':'feather-alt'; ?>"></i>
                            <?php echo htmlspecialchars($isUnk ? 'Unknown' : $row['species_name']); ?>
                        </span>
                    </td>
                    <td>
                        <span class="gal-conf <?php echo $confClass; ?>">
                            <?php echo $conf > 0 ? $conf.'%' : '—'; ?>
                        </span>
                    </td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div class="user-avatar" style="width:28px;height:28px;font-size:.65rem;flex-shrink:0;"><?php echo $initials; ?></div>
                            <span style="font-size:.78rem;"><?php echo htmlspecialchars($uname); ?></span>
                        </div>
                    </td>
                    <td style="color:#9ca3af;font-size:.75rem;">
                        <?php echo !empty($row['location']) ? '<i class="fas fa-map-marker-alt" style="color:#40916c;margin-right:4px;"></i>'.htmlspecialchars($row['location']) : '—'; ?>
                    </td>
                    <td style="font-size:.75rem;color:#6b7280;">
                        <?php echo $date; ?><br>
                        <span style="color:#9ca3af;"><?php echo $time; ?></span>
                    </td>
                    <td style="font-size:.75rem;">
                        <span style="color:#e63946;margin-right:10px;"><i class="fas fa-heart"></i> <?php echo $row['likes']; ?></span>
                        <span style="color:#40916c;"><i class="fas fa-comment"></i> <?php echo $row['comments']; ?></span>
                    </td>
                    <td>
                        <button type="button" class="gal-del-btn" title="Delete"
                                onclick="singleDelete('<?php echo $rid; ?>')">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <?php elseif ($tab === 'submissions'): ?>
        <!-- ══ UNKNOWN SUBMISSIONS TAB — 3 Actions ══ -->
        <?php
        $subMsg = ''; $subMsgType = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action_s   = $_POST['action_s']    ?? '';
            $sub_id_s   = intval($_POST['sub_id_s'] ?? 0);
            $adminLabel = trim($_POST['admin_label'] ?? '');

            try {
                $conn = getDatabase()->getConnection();

                // ── Fetch submission row ──────────────────────────────
                $sub = $conn->prepare("SELECT * FROM unknown_submissions WHERE id=?");
                $sub->execute([$sub_id_s]);
                $subRow = $sub->fetch();

                if (!$subRow) {
                    $subMsg = 'Submission not found.'; $subMsgType = 'error';
                }

                // ── ACTION: APPROVE + OVERRIDE ───────────────────────
                elseif ($action_s === 'approve') {
                    if (empty($adminLabel)) {
                        $subMsg = 'Please enter the correct species name before approving.';
                        $subMsgType = 'error';
                    } else {
                        // 1. Save image hash → prediction_overrides (instant fix for same image)
                        $imgFile = UPLOAD_DIR . ($subRow['image_filename'] ?? '');
                        if (!empty($subRow['image_filename']) && file_exists($imgFile)) {
                            $imgHash = md5_file($imgFile);
                            $ovDup = $conn->prepare("SELECT id FROM prediction_overrides WHERE image_hash=?");
                            $ovDup->execute([$imgHash]);
                            if (!$ovDup->fetch()) {
                                $conn->prepare(
                                    "INSERT INTO prediction_overrides (image_hash, species_name, approved_by, source_sub_id)
                                     VALUES (?, ?, ?, ?)"
                                )->execute([$imgHash, $adminLabel, $_SESSION['user_id'], $sub_id_s]);
                            }
                        }

                        // 2. Save to training_images for future fine-tuning
                        $modelLabelUpper = strtoupper($adminLabel);
                        if (!empty($subRow['image_filename']) && file_exists($imgFile)) {
                            $conn->prepare(
                                "INSERT INTO training_images
                                    (image_filename, image_path, species_name, model_label, source_sub_id, added_by)
                                 VALUES (?, ?, ?, ?, ?, ?)"
                            )->execute([
                                $subRow['image_filename'],
                                $imgFile,
                                $adminLabel,
                                $modelLabelUpper,
                                $sub_id_s,
                                $_SESSION['user_id']
                            ]);
                        }

                        // 3. Update submission status + save admin label
                        $conn->prepare(
                            "UPDATE unknown_submissions SET status='approved', admin_label=?, reviewed_at=NOW() WHERE id=?"
                        )->execute([$adminLabel, $sub_id_s]);

                        $subMsg = "✓ Approved! Image of \"{$adminLabel}\" saved to override table and training queue.";
                        $subMsgType = 'success';
                    }
                }

                // ── ACTION: REJECT ────────────────────────────────────
                elseif ($action_s === 'reject') {
                    $conn->prepare(
                        "UPDATE unknown_submissions SET status='rejected', reviewed_at=NOW() WHERE id=?"
                    )->execute([$sub_id_s]);
                    $subMsg = 'Submission rejected.'; $subMsgType = 'success';
                }

                // ── ACTION: USE FOR TRAINING ONLY ─────────────────────
                elseif ($action_s === 'train') {
                    if (empty($adminLabel)) {
                        $subMsg = 'Please enter the correct species name before adding to training.';
                        $subMsgType = 'error';
                    } else {
                        $imgFile = UPLOAD_DIR . ($subRow['image_filename'] ?? '');
                        if (!empty($subRow['image_filename']) && file_exists($imgFile)) {
                            $modelLabelUpper = strtoupper($adminLabel);
                            $conn->prepare(
                                "INSERT INTO training_images
                                    (image_filename, image_path, species_name, model_label, source_sub_id, added_by)
                                 VALUES (?, ?, ?, ?, ?, ?)"
                            )->execute([
                                $subRow['image_filename'],
                                $imgFile,
                                $adminLabel,
                                $modelLabelUpper,
                                $sub_id_s,
                                $_SESSION['user_id']
                            ]);
                            $conn->prepare(
                                "UPDATE unknown_submissions SET status='approved', admin_label=?, reviewed_at=NOW() WHERE id=?"
                            )->execute([$adminLabel, $sub_id_s]);
                            $subMsg = "✓ Image of \"{$adminLabel}\" added to training queue. Run Fine-Tune from System tab when ready.";
                            $subMsgType = 'success';
                        } else {
                            $subMsg = 'Image file not found on server.'; $subMsgType = 'error';
                        }
                    }
                }

            } catch (Exception $e) {
                $subMsg = 'Error: '.$e->getMessage(); $subMsgType = 'error';
            }
        }

        // Load all submissions — pending first
        $allSubs = [];
        try {
            $conn    = getDatabase()->getConnection();
            $allSubs = $conn->query(
                "SELECT s.*, u.first_name, u.last_name, u.email
                 FROM unknown_submissions s
                 LEFT JOIN users u ON s.user_id = u.user_id
                 ORDER BY FIELD(s.status,'pending','approved','rejected'), s.submitted_at DESC"
            )->fetchAll();
        } catch(Exception $e) { $allSubs = []; }
        $pendingCount = count(array_filter($allSubs, fn($s) => $s['status']==='pending'));

        // Load all species from DB for dropdown
        $dbSpeciesList = [];
        try {
            $dbSpeciesList = getDatabase()->getConnection()->query(
                "SELECT name, scientific_name FROM species ORDER BY name ASC"
            )->fetchAll();
        } catch(Exception $e) { $dbSpeciesList = []; }
        $dbSpeciesNames = array_column($dbSpeciesList, 'name');
        ?>

        <?php if ($subMsg): ?>
        <div class="sp-alert <?php echo $subMsgType; ?>">
            <i class="fas <?php echo $subMsgType==='success'?'fa-check-circle':'fa-exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($subMsg); ?>
        </div>
        <?php endif; ?>

        <div class="users-toolbar" style="margin-bottom:20px;">
            <div style="font-size:.875rem;color:var(--text-muted);">
                <i class="fas fa-clock" style="color:#f4a261;margin-right:6px;"></i>
                <strong><?php echo $pendingCount; ?></strong> pending &nbsp;·&nbsp;
                <strong><?php echo count($allSubs); ?></strong> total submissions
            </div>
            <div style="font-size:.78rem;color:var(--text-muted);background:#f0f7f4;padding:8px 14px;border-radius:8px;border:1px solid rgba(116,198,157,.2);">
                <i class="fas fa-info-circle" style="color:#40916c;margin-right:5px;"></i>
                For each pending submission: type the correct bird name, then choose an action.
            </div>
        </div>

        <?php if (empty($allSubs)): ?>
        <div class="tab-content-area" style="text-align:center;padding:60px 20px;">
            <i class="fas fa-check-circle" style="font-size:2.5rem;margin-bottom:16px;display:block;opacity:.25;color:#40916c;"></i>
            <h3 style="font-family:'Playfair Display',serif;color:#1a1a2e;margin-bottom:8px;">All Clear!</h3>
            <p style="color:#9ca3af;">No unknown bird submissions to review.</p>
        </div>
        <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:18px;">
        <?php foreach ($allSubs as $s):
            $sName   = trim(($s['first_name']??'').(' '.($s['last_name']??''))) ?: ($s['email']??'Unknown');
            $imgPath = !empty($s['image_filename']) ? 'uploads/'.$s['image_filename'] : '';
            $hasImg  = !empty($imgPath) && file_exists(UPLOAD_DIR.($s['image_filename']??''));
            $sDate   = date('M j, Y · g:i A', strtotime($s['submitted_at']));
            $sc      = $s['status']==='pending' ? '#f4a261' : ($s['status']==='approved' ? '#40916c' : '#f87171');
            $suggested = $s['suggested_species'] ?? $s['bird_name'] ?? '';
            $userNote  = $s['user_note'] ?? $s['description'] ?? '';
            $adminLbl  = $s['admin_label'] ?? '';
        ?>
        <div style="background:#fff;border-radius:16px;border:1px solid <?php echo $s['status']==='pending'?'rgba(244,162,97,.3)':'var(--border)'; ?>;
                    box-shadow:0 2px 12px rgba(0,0,0,.06);overflow:hidden;">

            <!-- Card Header -->
            <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;
                        background:<?php echo $s['status']==='pending'?'rgba(244,162,97,.06)':'#fafafa'; ?>;
                        border-bottom:1px solid var(--border);">
                <div style="display:flex;align-items:center;gap:10px;">
                    <span style="font-size:.75rem;font-weight:700;background:<?php echo $sc; ?>22;
                                 color:<?php echo $sc; ?>;border:1px solid <?php echo $sc; ?>44;
                                 padding:3px 10px;border-radius:50px;">
                        <?php echo ucfirst($s['status']); ?>
                    </span>
                    <span style="font-size:.8rem;color:var(--text-muted);">
                        <i class="fas fa-user" style="margin-right:4px;"></i><?php echo htmlspecialchars($sName); ?>
                    </span>
                    <span style="font-size:.78rem;color:var(--text-muted);">
                        <i class="far fa-clock" style="margin-right:4px;"></i><?php echo $sDate; ?>
                    </span>
                </div>
                <span style="font-size:.75rem;color:var(--text-muted);">ID #<?php echo $s['id']; ?></span>
            </div>

            <!-- Card Body -->
            <div style="display:flex;gap:20px;padding:18px 20px;align-items:flex-start;flex-wrap:wrap;">

                <!-- Bird Image -->
                <div style="flex-shrink:0;">
                    <?php if ($hasImg): ?>
                    <a href="<?php echo htmlspecialchars($imgPath); ?>" target="_blank">
                        <img src="<?php echo htmlspecialchars($imgPath); ?>"
                             style="width:110px;height:110px;object-fit:cover;border-radius:12px;
                                    border:2px solid var(--border);display:block;">
                    </a>
                    <div style="font-size:.68rem;color:var(--text-muted);text-align:center;margin-top:4px;">Click to enlarge</div>
                    <?php else: ?>
                    <div style="width:110px;height:110px;border-radius:12px;background:#f0f7f4;
                                display:flex;flex-direction:column;align-items:center;justify-content:center;
                                color:#9ca3af;border:2px dashed var(--border);">
                        <i class="fas fa-image" style="font-size:1.6rem;margin-bottom:6px;"></i>
                        <span style="font-size:.68rem;">No image</span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Info -->
                <div style="flex:1;min-width:200px;">
                    <?php if (!empty($suggested)): ?>
                    <div style="margin-bottom:10px;">
                        <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;
                                    color:var(--text-muted);margin-bottom:3px;">User Suggestion</div>
                        <div style="font-size:.92rem;font-weight:700;color:var(--primary);">
                            <i class="fas fa-lightbulb" style="color:#f4a261;margin-right:5px;"></i>
                            <?php echo htmlspecialchars($suggested); ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($userNote)): ?>
                    <div style="margin-bottom:10px;">
                        <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;
                                    color:var(--text-muted);margin-bottom:3px;">User Note</div>
                        <div style="font-size:.82rem;color:var(--text-muted);line-height:1.5;">
                            <?php echo htmlspecialchars(substr($userNote, 0, 150)); ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($adminLbl)): ?>
                    <div style="margin-bottom:10px;">
                        <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;
                                    color:var(--text-muted);margin-bottom:3px;">Admin Label</div>
                        <div style="font-size:.88rem;font-weight:700;color:#40916c;">
                            <i class="fas fa-check-circle" style="margin-right:5px;"></i>
                            <?php echo htmlspecialchars($adminLbl); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Actions (only for pending) -->
                <?php if ($s['status'] === 'pending'): ?>
                <div style="flex-shrink:0;min-width:280px;">
                    <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;
                                color:var(--text-muted);margin-bottom:8px;">
                        <i class="fas fa-tag" style="color:#40916c;margin-right:4px;"></i>
                        Select Correct Species <span style="color:#e63946;">*</span>
                    </div>

                    <!-- Smart dropdown: DB species + not-in-db option -->
                    <select id="lbl_<?php echo $s['id']; ?>" onchange="handleSpeciesSelect(this,<?php echo $s['id']; ?>)"
                        style="width:100%;border:1.5px solid rgba(116,198,157,.4);border-radius:8px;
                               padding:9px 12px;font-size:.85rem;font-family:'Inter',sans-serif;
                               outline:none;margin-bottom:8px;color:var(--text-main);background:#f8fffe;cursor:pointer;">
                        <option value="">-- Select a species --</option>
                        <optgroup label="In Your Database (Approve + Override)">
                            <?php foreach ($dbSpeciesList as $sp): ?>
                            <option value="<?php echo htmlspecialchars($sp['name']); ?>"
                                <?php echo ($suggested === $sp['name']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sp['name']); ?>
                                <?php if (!empty($sp['scientific_name'])): ?>
                                (<?php echo htmlspecialchars($sp['scientific_name']); ?>)
                                <?php endif; ?>
                            </option>
                            <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="Not in Database (Use for AI Training only)">
                            <option value="__custom__">Other / Type custom name...</option>
                        </optgroup>
                    </select>

                    <!-- Custom name input (shown only when Other is selected) -->
                    <input type="text" id="lbl_custom_<?php echo $s['id']; ?>"
                           placeholder="Type species name for training..."
                           style="width:100%;border:1.5px solid rgba(72,202,228,.5);border-radius:8px;
                                  padding:9px 12px;font-size:.85rem;font-family:'Inter',sans-serif;
                                  outline:none;margin-bottom:8px;color:var(--text-main);background:#f0fbff;
                                  display:none;">

                    <!-- Warning shown when DB species selected -->
                    <div id="lbl_hint_<?php echo $s['id']; ?>"
                         style="display:none;font-size:.75rem;padding:7px 10px;border-radius:7px;
                                background:rgba(116,198,157,.1);border:1px solid rgba(116,198,157,.25);
                                color:#2d6a4f;margin-bottom:8px;">
                        <i class="fas fa-database" style="margin-right:4px;"></i>
                        This species is in your database — full info will be shown on next identification.
                    </div>
                    <div id="lbl_train_hint_<?php echo $s['id']; ?>"
                         style="display:none;font-size:.75rem;padding:7px 10px;border-radius:7px;
                                background:rgba(72,202,228,.1);border:1px solid rgba(72,202,228,.25);
                                color:#0077b6;margin-bottom:8px;">
                        <i class="fas fa-brain" style="margin-right:4px;"></i>
                        Not in database — use "Use for AI Training" to help the model learn this bird.
                    </div>

                    <div style="display:flex;flex-direction:column;gap:8px;">

                        <!-- APPROVE + OVERRIDE -->
                        <form method="POST" onsubmit="return fillLabel(this,<?php echo $s['id']; ?>)">
                            <input type="hidden" name="action_s"  value="approve">
                            <input type="hidden" name="sub_id_s" value="<?php echo $s['id']; ?>">
                            <input type="hidden" name="admin_label" id="al_approve_<?php echo $s['id']; ?>">
                            <button type="submit"
                                style="width:100%;background:linear-gradient(135deg,#40916c,#74c69d);
                                       color:#fff;border:none;border-radius:8px;padding:9px 14px;
                                       font-size:.82rem;font-weight:700;cursor:pointer;
                                       font-family:'Inter',sans-serif;display:flex;align-items:center;
                                       justify-content:center;gap:7px;transition:opacity .2s;">
                                <i class="fas fa-check-circle"></i>
                                Approve + Fix Override
                            </button>
                        </form>

                        <!-- USE FOR TRAINING -->
                        <form method="POST" onsubmit="return fillLabel(this,<?php echo $s['id']; ?>,'train')">
                            <input type="hidden" name="action_s"  value="train">
                            <input type="hidden" name="sub_id_s" value="<?php echo $s['id']; ?>">
                            <input type="hidden" name="admin_label" id="al_train_<?php echo $s['id']; ?>">
                            <button type="submit"
                                style="width:100%;background:linear-gradient(135deg,#0077b6,#48cae4);
                                       color:#fff;border:none;border-radius:8px;padding:9px 14px;
                                       font-size:.82rem;font-weight:700;cursor:pointer;
                                       font-family:'Inter',sans-serif;display:flex;align-items:center;
                                       justify-content:center;gap:7px;transition:opacity .2s;">
                                <i class="fas fa-brain"></i>
                                Use for AI Training
                            </button>
                        </form>

                        <!-- REJECT -->
                        <form method="POST" onsubmit="return mkAdminConfirm(event,'Reject Submission?','This submission will be marked as rejected.')">
                            <input type="hidden" name="action_s"  value="reject">
                            <input type="hidden" name="sub_id_s" value="<?php echo $s['id']; ?>">
                            <button type="submit"
                                style="width:100%;background:rgba(248,113,113,.1);color:#e63946;
                                       border:1.5px solid rgba(248,113,113,.3);border-radius:8px;
                                       padding:9px 14px;font-size:.82rem;font-weight:700;cursor:pointer;
                                       font-family:'Inter',sans-serif;display:flex;align-items:center;
                                       justify-content:center;gap:7px;transition:all .2s;">
                                <i class="fas fa-times-circle"></i>
                                Reject
                            </button>
                        </form>
                    </div>
                </div>
                <?php else: ?>
                <div style="flex-shrink:0;display:flex;align-items:center;padding:10px 16px;
                            background:#f8fffe;border-radius:10px;border:1px solid var(--border);
                            font-size:.82rem;color:var(--text-muted);gap:8px;">
                    <i class="fas fa-check" style="color:#40916c;"></i>
                    Reviewed
                </div>
                <?php endif; ?>

            </div>
        </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <script>
        // Called when admin changes the dropdown
        function handleSpeciesSelect(sel, id) {
            const val = sel.value;
            const customInput = document.getElementById('lbl_custom_' + id);
            const dbHint      = document.getElementById('lbl_hint_' + id);
            const trainHint   = document.getElementById('lbl_train_hint_' + id);

            if (val === '__custom__') {
                // Not in DB — show custom input + training hint
                customInput.style.display = 'block';
                dbHint.style.display      = 'none';
                trainHint.style.display   = 'block';
            } else if (val === '') {
                customInput.style.display = 'none';
                dbHint.style.display      = 'none';
                trainHint.style.display   = 'none';
            } else {
                // In DB — show DB hint, hide custom input
                customInput.style.display = 'none';
                dbHint.style.display      = 'block';
                trainHint.style.display   = 'none';
            }
        }

        // Called on form submit — reads the correct value from dropdown or custom input
        function fillLabel(form, id, action) {
            const sel         = document.getElementById('lbl_' + id);
            const customInput = document.getElementById('lbl_custom_' + id);
            const selVal      = sel ? sel.value.trim() : '';
            const customVal   = customInput ? customInput.value.trim() : '';

            // Determine final species name
            let finalVal = '';
            if (selVal === '__custom__') {
                finalVal = customVal;
            } else {
                finalVal = selVal;
            }

            if (!finalVal) {
                mkAdminAlert('Select Species','Please select or type the correct species name first.'); return false;
                return false;
            }

            // If DB species selected but admin clicks "Use for Training" — allow it
            // If NOT in DB but admin clicks "Approve + Fix Override" — warn
            const inDb = (selVal !== '__custom__' && selVal !== '');
            if (action !== 'train' && selVal === '__custom__') {
                mkAdminAlert('Not in Database','This species is not in your database. Use <strong>Use for AI Training</strong> instead of Approve.'); return false;
                return false;
            }

            const suffix = action === 'train' ? 'train' : 'approve';
            const hidden = document.getElementById('al_' + suffix + '_' + id);
            if (hidden) hidden.value = finalVal;
            return true;
        }
        </script>

        <?php elseif ($tab === 'system'): ?>
        <!-- ══ SYSTEM HEALTH TAB ══ -->
        <?php
        $dbOk    = false; $dbMsg = '';
        $modelOk = false; $modelFile = '';
        $uploadOk= false; $uploadWritable = false;
        $phpVer  = PHP_VERSION;
        $diskFree= '';
        try {
            getDatabase()->getConnection()->query("SELECT 1");
            $dbOk  = true; $dbMsg = 'Connected to mkfinder database';
        } catch(Exception $e) { $dbMsg = $e->getMessage(); }
        $modelFile    = __DIR__.'/model/bird_classifier_final.h5';
        $modelOk      = file_exists($modelFile);
        $uploadOk     = file_exists(UPLOAD_DIR);
        $uploadWritable = is_writable(UPLOAD_DIR);
        $diskFreeBytes  = disk_free_space(__DIR__);
        $diskFree       = $diskFreeBytes !== false ? round($diskFreeBytes/1024/1024/1024, 2).' GB' : 'Unknown';
        $uploadCount    = 0; $totalSize = 0;
        try {
            $conn        = getDatabase()->getConnection();
            $uploadCount = $conn->query("SELECT COUNT(*) FROM uploads")->fetchColumn();
            $totalSize   = $conn->query("SELECT SUM(file_size) FROM uploads")->fetchColumn();
            $totalSize   = $totalSize ? round($totalSize/1024/1024, 2).' MB' : '0 MB';
        } catch(Exception $e){}
        ?>
        <div class="sys-grid">

            <!-- DB Status -->
            <div class="sys-card <?php echo $dbOk?'ok':'fail'; ?>">
                <div class="sys-icon"><i class="fas fa-database"></i></div>
                <div class="sys-info">
                    <div class="sys-title">Database</div>
                    <div class="sys-val"><?php echo $dbOk ? 'Connected' : 'Disconnected'; ?></div>
                    <div class="sys-detail"><?php echo htmlspecialchars($dbMsg); ?></div>
                </div>
                <div class="sys-dot <?php echo $dbOk?'green':'red'; ?>"></div>
            </div>

            <!-- Model Status -->
            <div class="sys-card <?php echo $modelOk?'ok':'warn'; ?>">
                <div class="sys-icon"><i class="fas fa-brain"></i></div>
                <div class="sys-info">
                    <div class="sys-title">AI Model</div>
                    <div class="sys-val"><?php echo $modelOk ? 'bird_classifier_final.h5' : 'Model Not Found'; ?></div>
                    <div class="sys-detail"><?php echo $modelOk ? 'Model loaded from /model/' : 'Place .h5 file in /model/ folder'; ?></div>
                </div>
                <div class="sys-dot <?php echo $modelOk?'green':'orange'; ?>"></div>
            </div>

            <!-- Uploads Folder -->
            <div class="sys-card <?php echo ($uploadOk&&$uploadWritable)?'ok':'fail'; ?>">
                <div class="sys-icon"><i class="fas fa-folder-open"></i></div>
                <div class="sys-info">
                    <div class="sys-title">Uploads Folder</div>
                    <div class="sys-val"><?php echo $uploadOk ? ($uploadWritable?'Writable':'Read-only') : 'Missing'; ?></div>
                    <div class="sys-detail"><?php echo $uploadCount; ?> files · <?php echo $totalSize; ?> used</div>
                </div>
                <div class="sys-dot <?php echo ($uploadOk&&$uploadWritable)?'green':'red'; ?>"></div>
            </div>

            <!-- PHP Version -->
            <div class="sys-card ok">
                <div class="sys-icon"><i class="fab fa-php"></i></div>
                <div class="sys-info">
                    <div class="sys-title">PHP Version</div>
                    <div class="sys-val"><?php echo $phpVer; ?></div>
                    <div class="sys-detail">PDO MySQL extension active</div>
                </div>
                <div class="sys-dot green"></div>
            </div>

            <!-- Disk Space -->
            <div class="sys-card ok">
                <div class="sys-icon"><i class="fas fa-hdd"></i></div>
                <div class="sys-info">
                    <div class="sys-title">Free Disk Space</div>
                    <div class="sys-val"><?php echo $diskFree; ?></div>
                    <div class="sys-detail">Available on server drive</div>
                </div>
                <div class="sys-dot green"></div>
            </div>

            <!-- Species Count -->
            <div class="sys-card ok">
                <div class="sys-icon"><i class="fas fa-feather-alt"></i></div>
                <div class="sys-info">
                    <div class="sys-title">Species in DB</div>
                    <div class="sys-val"><?php echo $stats['species']; ?> species</div>
                    <div class="sys-detail"><?php echo $stats['identifications']; ?> total identifications</div>
                </div>
                <div class="sys-dot green"></div>
            </div>

        </div>

        <!-- ══ AI FINE-TUNE PANEL ══ -->
        <?php
        $trainingCount = 0;
        $usedCount     = 0;
        $ftWeightsExist = file_exists(__DIR__ . '/fine_tuned_model/classifier_head.pt');
        $ftMeta         = [];
        $ftMetaFile     = __DIR__ . '/fine_tuned_model/meta.json';
        if (file_exists($ftMetaFile)) {
            $ftMeta = json_decode(file_get_contents($ftMetaFile), true) ?? [];
        }
        try {
            $conn          = getDatabase()->getConnection();
            $trainingCount = $conn->query("SELECT COUNT(*) FROM training_images WHERE used_in_training=0")->fetchColumn();
            $usedCount     = $conn->query("SELECT COUNT(*) FROM training_images WHERE used_in_training=1")->fetchColumn();
        } catch(Exception $e){}
        ?>
        <div style="margin-top:32px;background:#fff;border-radius:16px;border:1px solid var(--border);
                    box-shadow:0 2px 12px rgba(0,0,0,.06);overflow:hidden;">

            <!-- Header -->
            <div style="padding:20px 28px;border-bottom:1px solid var(--border);
                        background:linear-gradient(135deg,#0d1b2a,#1b4332);
                        display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
                <div>
                    <div style="font-family:'Playfair Display',serif;font-size:1.1rem;font-weight:700;color:#fff;margin-bottom:4px;">
                        <i class="fas fa-brain" style="color:#74c69d;margin-right:8px;"></i>AI Fine-Tune Panel
                    </div>
                    <div style="font-size:.78rem;color:rgba(255,255,255,.45);">
                        Improve your model using admin-labelled images from user submissions
                    </div>
                </div>
                <div style="display:flex;gap:16px;flex-wrap:wrap;">
                    <div style="text-align:center;background:rgba(116,198,157,.12);border:1px solid rgba(116,198,157,.2);
                                border-radius:10px;padding:10px 18px;">
                        <div style="font-size:1.4rem;font-weight:800;color:#74c69d;"><?php echo $trainingCount; ?></div>
                        <div style="font-size:.7rem;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:.5px;">Ready to Train</div>
                    </div>
                    <div style="text-align:center;background:rgba(72,202,228,.1);border:1px solid rgba(72,202,228,.2);
                                border-radius:10px;padding:10px 18px;">
                        <div style="font-size:1.4rem;font-weight:800;color:#48cae4;"><?php echo $usedCount; ?></div>
                        <div style="font-size:.7rem;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:.5px;">Already Trained</div>
                    </div>
                </div>
            </div>

            <!-- Body -->
            <div style="padding:24px 28px;">

                <!-- Fine-tuned model status -->
                <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;
                            padding:14px 18px;border-radius:10px;
                            background:<?php echo $ftWeightsExist?'rgba(116,198,157,.08)':'rgba(244,162,97,.08)'; ?>;
                            border:1px solid <?php echo $ftWeightsExist?'rgba(116,198,157,.25)':'rgba(244,162,97,.25)'; ?>;">
                    <div style="width:40px;height:40px;border-radius:10px;flex-shrink:0;
                                background:<?php echo $ftWeightsExist?'rgba(116,198,157,.15)':'rgba(244,162,97,.15)'; ?>;
                                display:flex;align-items:center;justify-content:center;">
                        <i class="fas <?php echo $ftWeightsExist?'fa-check-circle':'fa-exclamation-triangle'; ?>"
                           style="color:<?php echo $ftWeightsExist?'#40916c':'#f4a261'; ?>;font-size:1rem;"></i>
                    </div>
                    <div>
                        <div style="font-size:.88rem;font-weight:700;color:var(--text-main);">
                            <?php echo $ftWeightsExist ? 'Fine-tuned model active' : 'No fine-tuned model yet'; ?>
                        </div>
                        <div style="font-size:.75rem;color:var(--text-muted);margin-top:2px;">
                            <?php if ($ftWeightsExist && !empty($ftMeta)): ?>
                                Last trained: <?php echo htmlspecialchars($ftMeta['trained_at'] ?? '—'); ?> &nbsp;·&nbsp;
                                <?php echo $ftMeta['num_images'] ?? 0; ?> image(s) &nbsp;·&nbsp;
                                Species: <?php echo htmlspecialchars(implode(', ', array_unique($ftMeta['trained_on'] ?? []))); ?>
                            <?php elseif ($ftWeightsExist): ?>
                                Weights file exists — meta.json not found
                            <?php else: ?>
                                Run fine-tuning below to improve AI accuracy for labelled images
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- How it works -->
                <div style="margin-bottom:24px;padding:14px 18px;border-radius:10px;
                            background:#f8fffe;border:1px solid rgba(116,198,157,.15);">
                    <div style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;
                                color:var(--primary);margin-bottom:10px;">
                        <i class="fas fa-info-circle" style="margin-right:5px;"></i>How Fine-Tuning Works
                    </div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <?php
                        $steps = [
                            ['fas fa-images','#40916c','Reads '.$trainingCount.' new labelled image(s) from DB'],
                            ['fas fa-lock','#0077b6','Freezes base model layers (keeps 526 species knowledge)'],
                            ['fas fa-bolt','#f4a261','Trains only the classifier head (fast on your RTX 5060)'],
                            ['fas fa-save','#40916c','Saves weights to fine_tuned_model/'],
                            ['fas fa-magic','#74c69d','predict.py auto-loads new weights on next run'],
                        ];
                        foreach ($steps as $i => $st): ?>
                        <div style="display:flex;align-items:flex-start;gap:8px;flex:1;min-width:180px;
                                    padding:10px 12px;background:#fff;border-radius:8px;
                                    border:1px solid rgba(116,198,157,.12);">
                            <div style="width:28px;height:28px;border-radius:7px;flex-shrink:0;
                                        background:<?php echo $st[1]; ?>18;
                                        display:flex;align-items:center;justify-content:center;">
                                <i class="fas <?php echo $st[0]; ?>" style="color:<?php echo $st[1]; ?>;font-size:.75rem;"></i>
                            </div>
                            <div style="font-size:.75rem;color:var(--text-muted);line-height:1.4;padding-top:2px;">
                                <?php echo htmlspecialchars($st[2]); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Run button + output -->
                <?php if ($trainingCount == 0): ?>
                <div style="text-align:center;padding:20px;background:#f8fffe;border-radius:10px;
                            border:1px dashed rgba(116,198,157,.3);">
                    <i class="fas fa-check-circle" style="color:#40916c;font-size:1.5rem;margin-bottom:8px;display:block;"></i>
                    <div style="font-size:.88rem;color:var(--text-muted);">
                        No new training images queued.<br>
                        Go to <strong>Submissions</strong> tab → click <strong>Use for AI Training</strong> on pending submissions first.
                    </div>
                </div>
                <?php else: ?>
                <div style="text-align:center;">
                    <button onclick="runFineTune()"
                        id="btnFineTune"
                        style="background:linear-gradient(135deg,#0d1b2a,#1b4332);color:#fff;
                               border:2px solid rgba(116,198,157,.3);border-radius:12px;
                               padding:14px 36px;font-size:.95rem;font-weight:700;cursor:pointer;
                               font-family:'Inter',sans-serif;display:inline-flex;align-items:center;
                               gap:10px;transition:all .3s;margin-bottom:16px;">
                        <i class="fas fa-brain" style="color:#74c69d;"></i>
                        Run Fine-Tune Now
                        <span style="background:rgba(116,198,157,.2);border-radius:50px;
                                     padding:2px 10px;font-size:.78rem;color:#74c69d;">
                            <?php echo $trainingCount; ?> image<?php echo $trainingCount!=1?'s':''; ?> queued
                        </span>
                    </button>

                    <!-- Progress output box -->
                    <div id="ftOutput" style="display:none;text-align:left;background:#0d1b2a;color:#74c69d;
                                              border-radius:10px;padding:16px 20px;font-family:'Courier New',monospace;
                                              font-size:.8rem;line-height:1.8;max-height:320px;overflow-y:auto;
                                              border:1px solid rgba(116,198,157,.2);margin-top:4px;"></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php else: ?>
        <div class="tab-content-area">
            <div class="coming-soon">
                <div class="cs-icon"><i class="fas fa-cog"></i></div>
                <h3>Coming Soon</h3>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /page-body -->
</div><!-- /main-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function toggleSelectAll(master) {
        document.querySelectorAll('.row-checkbox').forEach(cb => {
            cb.checked = master.checked;
            const row = cb.closest('tr');
            if (row) row.style.background = cb.checked ? 'rgba(116,198,157,.08)' : '';
        });
        updateBulkToolbar();
    }

    function updateBulkToolbar() {
        const checked = document.querySelectorAll('.row-checkbox:checked');
        const all     = document.querySelectorAll('.row-checkbox');
        const toolbar = document.getElementById('bulkToolbar');
        const countEl = document.getElementById('bulkCount');
        const master  = document.getElementById('selectAll');
        toolbar.style.display = checked.length > 0 ? 'flex' : 'none';
        if (countEl) countEl.textContent = checked.length;
        if (master) {
            master.indeterminate = checked.length > 0 && checked.length < all.length;
            master.checked = all.length > 0 && checked.length === all.length;
        }
        document.querySelectorAll('.row-checkbox').forEach(cb => {
            const row = cb.closest('tr');
            if (row) row.style.background = cb.checked ? 'rgba(116,198,157,.08)' : '';
        });
    }

    function clearSelection() {
        document.querySelectorAll('.row-checkbox').forEach(cb => {
            cb.checked = false;
            const row = cb.closest('tr');
            if (row) row.style.background = '';
        });
        const master = document.getElementById('selectAll');
        if (master) { master.checked = false; master.indeterminate = false; }
        document.getElementById('bulkToolbar').style.display = 'none';
    }

    // Single delete — uses hidden singleDeleteForm, no nested forms
    function singleDelete(identId) {
        document.getElementById('singleDeleteId').value = identId;
        const fakeEvent = { preventDefault: () => {}, target: document.getElementById('singleDeleteForm') };
        mkAdminConfirm(fakeEvent,
            'Delete Identification?',
            'This will permanently remove this bird photo and all its likes & comments from Bird Explorer.'
        );
    }

    // Bulk delete — injects hidden inputs into bulkForm dynamically
    function bulkDeleteSelected() {
        const checked = document.querySelectorAll('.row-checkbox:checked');
        if (checked.length === 0) return;
        const fakeEvent = {
            preventDefault: () => {},
            target: { submit: () => {
                // Inject checked IDs into bulkForm right before submit
                const container = document.getElementById('bulkHiddenIds');
                container.innerHTML = '';
                checked.forEach(cb => {
                    const inp = document.createElement('input');
                    inp.type  = 'hidden';
                    inp.name  = 'selected_ids[]';
                    inp.value = cb.dataset.id;
                    container.appendChild(inp);
                });
                document.getElementById('bulkForm').submit();
            }}
        };
        mkAdminConfirm(fakeEvent,
            `Delete ${checked.length} Identification${checked.length > 1 ? 's' : ''}?`,
            `This will permanently delete ${checked.length} selected record${checked.length > 1 ? 's' : ''} and their image files. This cannot be undone.`
        );
    }

    function updateClock() {
        const now = new Date();
        document.getElementById('clockDisplay').textContent =
            now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }
    updateClock(); setInterval(updateClock, 1000);

    async function runFineTune() {
        const btn    = document.getElementById('btnFineTune');
        const output = document.getElementById('ftOutput');
        if (!btn || !output) return;

        btn.disabled         = true;
        btn.innerHTML        = '<i class="fas fa-spinner fa-spin" style="color:#74c69d;"></i> Training in progress... (may take 1-2 min)';
        output.style.display = 'block';
        output.innerHTML     = '<span style="color:#74c69d;">▶ Starting fine-tune...</span>\n';
        output.scrollTop     = output.scrollHeight;

        try {
            const res  = await fetch('trigger_training.php', { method: 'POST' });
            const data = await res.json();

            // Show full python output line by line
            if (data.output && data.output.trim()) {
                const lines = data.output.split('\n');
                lines.forEach(line => {
                    if (!line.trim()) return;
                    const isErr  = line.includes('ERROR') || line.includes('❌') || line.includes('Traceback') || line.includes('Error:');
                    const isWarn = line.includes('⚠️') || line.includes('WARNING');
                    const color  = isErr ? '#f87171' : isWarn ? '#f4a261' : '#74c69d';
                    output.innerHTML += '<span style="color:' + color + ';">' +
                        line.replace(/</g,'&lt;').replace(/>/g,'&gt;') + '</span>\n';
                });
            }

            if (data.success) {
                output.innerHTML += '\n<span style="color:#74c69d;font-weight:bold;font-size:.85rem;">✅ Fine-tuning complete! Reload page to see updated status.</span>';
                btn.innerHTML = '<i class="fas fa-check-circle" style="color:#74c69d;"></i> Done! Click to Reload';
                btn.onclick   = () => location.reload();
                btn.disabled  = false;
            } else {
                output.innerHTML += '\n<span style="color:#f87171;font-weight:bold;">❌ ' + (data.message || 'Unknown error') + '</span>';
                output.innerHTML += '\n<span style="color:rgba(255,255,255,.4);font-size:.75rem;">Tip: You can also run manually — open CMD in your project folder and type: python finetune.py</span>';
                btn.innerHTML = '<i class="fas fa-brain" style="color:#74c69d;"></i> Retry Fine-Tune';
                btn.disabled  = false;
            }
        } catch(e) {
            output.innerHTML += '\n<span style="color:#f87171;">❌ Network error: ' + e.message + '</span>';
            btn.innerHTML = '<i class="fas fa-brain" style="color:#74c69d;"></i> Retry Fine-Tune';
            btn.disabled  = false;
        }

        output.scrollTop = output.scrollHeight;
    }

    function previewPhoto(input) {
        if (input.files && input.files[0]) {
            const r = new FileReader();
            r.onload = e => {
                document.getElementById('photoPreview').src = e.target.result;
                document.getElementById('photoPreview').style.display = 'block';
                document.getElementById('photoPlaceholder').style.display = 'none';
            };
            r.readAsDataURL(input.files[0]);
        }
    }

    const SP_LIST = [
        "ALBATROSS","AMERICAN AVOCET","AMERICAN BITTERN","AMERICAN COOT","AMERICAN FLAMINGO",
        "AMERICAN GOLDFINCH","AMERICAN KESTREL","AMERICAN ROBIN","ANHINGA","ARCTIC TERN",
        "BALD EAGLE","BALTIMORE ORIOLE","BARN OWL","BARN SWALLOW","BELTED KINGFISHER",
        "BLACK SKIMMER","BLACK SWAN","BLACK VULTURE","BLUE GROSBEAK","BLUE HERON",
        "CALIFORNIA CONDOR","CALIFORNIA QUAIL","CANARY","CEDAR WAXWING","CHIPPING SPARROW",
        "COCK OF THE ROCK","COMMON LOON","COMMON STARLING","CRESTED CARACARA","CROW",
        "DARK EYED JUNCO","DEMOISELLE CRANE","DOUBLE BAR FINCH","DOWNY WOODPECKER","DUSKY ROBIN",
        "EARED QUETZAL","EASTERN BLUEBIRD","EASTERN MEADOWLARK","EASTERN ROSELLA","EMU",
        "ELEGANT TROGON","EURASIAN MAGPIE","EUROPEAN GOLDFINCH","EUROPEAN ROLLER","EVENING GROSBEAK",
        "FAIRY BLUEBIRD","FLAMINGO","FOREST WAGTAIL","FRIGATE","FAN TAILED WIDOW",
        "GAMBELS QUAIL","GILDED FLICKER","GLOSSY IBIS","GOLDEN EAGLE","GOULDIAN FINCH",
        "GRAY CATBIRD","GREAT GRAY OWL","GREEN JAY","GYRFALCON","GOLDEN PHEASANT",
        "HAMERKOP","HARLEQUIN DUCK","HARPY EAGLE","HAWAIIAN GOOSE","HOATZIN",
        "HOOPOE","HORNBILL","HORNED LARK","HOUSE FINCH","HOUSE SPARROW",
        "IBISBILL","IMPERIAL SHAG","INDIGO BUNTING","INLAND DOTTEREL","IVORY GULL",
        "JABIRU","JANDAYA PARAKEET","JAPANESE ROBIN","JAVA SPARROW","JOCOTOCO ANTPITTA",
        "KAGU","KAKAPO","KILLDEER","KING EIDER","KIWI",
        "LARK BUNTING","LARK SPARROW","LAZULI BUNTING","LESSER ADJUTANT","LIMPKIN",
        "LOGGERHEAD SHRIKE","LONG TAILED TIT","LUCIFER HUMMINGBIRD","LILAC ROLLER","LOONEY BIRDS",
        "MALACHITE KINGFISHER","MALLARD DUCK","MANDARIN DUCK","MARABOU STORK","MOURNING DOVE",
        "NORTHERN CARDINAL","NORTHERN FLICKER","NORTHERN GANNET","NORTHERN MOCKINGBIRD","NORTHERN PINTAIL",
        "NICOBAR PIGEON","NIGHTHAWK","NUTMEG MANNIKIN","NORTHERN PARULA","NORTHERN BALD IBIS",
        "OSPREY","OSTRICH","OVENBIRD","OYSTER CATCHER","ORANGE BREASTED SUNBIRD",
        "PAINTED BUNTING","PEACOCK","PELICAN","PEREGRINE FALCON","PHILIPPINE EAGLE",
        "PUFFIN","PURPLE FINCH","PURPLE GALLINULE","PURPLE MARTIN","PYGMY KINGFISHER",
        "QUETZAL","RAINBOW LORIKEET","RAZORBILL","RED TAILED HAWK","ROADRUNNER",
        "ROBIN","RUBY CROWNED KINGLET","RUBY THROATED HUMMINGBIRD","RUFOUS MOTMOT","RED CROSSBILL",
        "SACRED IBIS","SANDHILL CRANE","SCARLET IBIS","SCARLET MACAW","SCARLET TANAGER",
        "SHOEBILL","SNOWY EGRET","SNOWY OWL","SNOW BUNTING","SNOW GOOSE",
        "TOUCAN","TREE SWALLOW","TROPICAL KINGBIRD","TUFTED PUFFIN","TURKEY VULTURE",
        "TUFTED TITMOUSE","TRUMPTER SWAN","TEAL DUCK","TURACO","TWIN SPOT HUMMINGBIRD",
        "UMBRELLA BIRD","UGUISU","VARIED THRUSH","VEERY","VERMILION FLYCATCHER",
        "VENEZUELAN TROUPIAL","VICTORIA CROWNED PIGEON","VIOLET CUCKOO","VIOLET TURACO","VULTURINE GUINEAFOWL",
        "WALL CREEPER","WATTLED CRANE","WHIMBREL","WILD TURKEY","WOOD DUCK",
        "WREN","WHITE THROATED ROBIN","WHITE NECKED RAVEN","WILLETS","WOODLAND KINGFISHER",
        "YELLOW BILLED STORK","YELLOW CARDINAL","YELLOW HEADED BLACKBIRD","YELLOW WAGTAIL","YUCATAN CARDINAL"
    ].sort();

    function tc(s){ return s.split(' ').map(w=>w[0].toUpperCase()+w.slice(1).toLowerCase()).join(' '); }

    function showSpDrop(){
        const inp  = document.getElementById('spSearch');
        const drop = document.getElementById('spDrop');
        const rect = inp.getBoundingClientRect();
        drop.style.top   = (rect.bottom + window.scrollY) + 'px';
        drop.style.left  = (rect.left   + window.scrollX) + 'px';
        drop.style.width = rect.width + 'px';
        drop.style.display = 'block';
        filterSp(inp.value);
    }

    function filterSp(q) {
        const uq = q.toUpperCase().trim();
        const hits = uq ? SP_LIST.filter(s=>s.includes(uq)) : SP_LIST;
        document.getElementById('spList').innerHTML = hits.length ? hits.map(s=>
            `<div onclick="pickSp('${s.replace(/'/g,"\\'")}');event.stopPropagation();"
             style="padding:9px 14px;font-size:.85rem;cursor:pointer;color:#1a1a2e;border-bottom:1px solid #f5f5f5;"
             onmouseover="this.style.background='#f0faf4'" onmouseout="this.style.background=''">${tc(s)}</div>`
        ).join('') : '<div style="padding:10px 14px;color:#9ca3af;font-size:.8rem;">No match</div>';
    }

    document.addEventListener('click', function(e){
        const drop = document.getElementById('spDrop');
        const inp  = document.getElementById('spSearch');
        if(drop && inp && !drop.contains(e.target) && e.target !== inp && !inp.parentElement.contains(e.target))
            drop.style.display='none';
    });

    function pickSp(modelLabel) {
        const name = tc(modelLabel);
        document.getElementById('f_model_label').value = modelLabel;
        document.getElementById('f_name').value        = name;
        document.getElementById('spSearch').value      = name;
        document.getElementById('spBadgeLabel').textContent = name + ' · ' + modelLabel;
        document.getElementById('spBadge').style.display = 'block';
        document.getElementById('spDrop').style.display  = 'none';
        document.getElementById('aiFillBtn').style.display = 'flex';
        document.getElementById('aiStatus').style.display  = 'none';
    }



    async function autoFill() {
        const birdName = document.getElementById('f_name').value;
        if (!birdName) return;
        const btn = document.getElementById('aiFillBtn');
        const status = document.getElementById('aiStatus');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> AI is researching...';
        status.style.display = 'none';
        try {
            const res = await fetch('gemini_autofill.php', {
                method: 'POST',
                headers: {'Content-Type':'application/x-www-form-urlencoded'},
                body: 'bird=' + encodeURIComponent(birdName)
            });
            const info = await res.json();
            if (info.error) throw new Error(info.error);
            document.getElementById('f_sci').value   = info.scientific_name || '';
            document.getElementById('f_desc').value  = info.description     || '';
            document.getElementById('f_hab').value   = info.habitat         || '';
            document.getElementById('f_diet').value  = info.diet            || '';
            document.getElementById('f_beh').value   = info.behavior        || '';
            document.getElementById('f_chars').value = info.characteristics || '';
            const sel = document.getElementById('f_status');
            for (let o of sel.options) {
                if (o.value === info.conservation_status) { sel.value = o.value; break; }
            }
            status.style.cssText = 'display:block;margin-top:8px;padding:8px 12px;border-radius:8px;font-size:.8rem;font-weight:500;background:#f0faf4;color:#2d6a4f;border:1px solid #74c69d55;';
            status.innerHTML = '<i class="fas fa-check-circle" style="margin-right:6px;"></i>Fields filled by AI! Review and submit.';
        } catch(e) {
            status.style.cssText = 'display:block;margin-top:8px;padding:8px 12px;border-radius:8px;font-size:.8rem;font-weight:500;background:#fff5f5;color:#c0392b;border:1px solid #f8717155;';
            status.innerHTML = '<i class="fas fa-exclamation-circle" style="margin-right:6px;"></i>AI failed: ' + e.message;
        }
        btn.innerHTML = '<i class="fas fa-magic"></i> Auto-Fill with AI';
        btn.disabled = false;
    }


    function validateSpForm() {
        if (document.getElementById('actionInput').value==='add' && !document.getElementById('f_model_label').value) {
            mkAdminAlert('Select a Bird','Please select a bird species from the dropdown first.');
            return false;
        }
        return true;
    }

    function loadEdit(id, name, sci, desc, hab, diet, beh, status, chars) {
        document.getElementById('actionInput').value    = 'edit';
        document.getElementById('speciesIdInput').value = id;
        document.getElementById('f_name').value         = name;
        document.getElementById('spSearch').value       = name;
        document.getElementById('f_sci').value          = sci;
        document.getElementById('f_desc').value         = desc;
        document.getElementById('f_hab').value          = hab;
        document.getElementById('f_diet').value         = diet;
        document.getElementById('f_beh').value          = beh;
        document.getElementById('f_status').value       = status;
        document.getElementById('f_chars').value        = chars;
        document.getElementById('spBadgeLabel').textContent = name;
        document.getElementById('spBadge').style.display    = 'block';
        document.getElementById('formPanelTitle').textContent = 'Edit Species';
        document.getElementById('submitBtnText').textContent  = 'Save Changes';
        document.getElementById('cancelEditBtn').style.display = 'inline-flex';
        document.querySelector('.sp-form-panel').scrollIntoView({behavior:'smooth'});
    }

    function resetForm() {
        document.getElementById('speciesForm').reset();
        document.getElementById('actionInput').value          = 'add';
        document.getElementById('speciesIdInput').value       = '';
        document.getElementById('f_model_label').value        = '';
        document.getElementById('f_name').value               = '';
        document.getElementById('spSearch').value             = '';
        document.getElementById('formPanelTitle').textContent = 'Add New Species';
        document.getElementById('submitBtnText').textContent  = 'Add Species';
        document.getElementById('cancelEditBtn').style.display = 'none';
        document.getElementById('photoPreview').style.display  = 'none';
        document.getElementById('photoPlaceholder').style.display = 'flex';
        document.getElementById('spBadge').style.display = 'none';
    }

    function filterUsers(val) {
        const q = val.toLowerCase();
        document.querySelectorAll('.user-row').forEach(row => {
            row.style.display = row.dataset.search.includes(q) ? '' : 'none';
        });
    }

    async function adminLogout() {
        try { await fetch('auth.php?action=logout', { method: 'POST' }); } catch(e) {}
        window.location.href = 'index.html';
    }

    // ── MK Admin Modal System — replaces all confirm/alert ──────────────────
    let __mkPendingForm = null;

    function mkAdminConfirm(event, title, message) {
        event.preventDefault();
        __mkPendingForm = event.target;
        let modal = document.getElementById('mkAdminModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'mkAdminModal';
            modal.style.cssText = 'position:fixed;inset:0;z-index:99999;display:flex;align-items:center;justify-content:center;padding:20px;background:rgba(13,27,42,.88);backdrop-filter:blur(8px);';
            document.body.appendChild(modal);
        }
        modal.innerHTML = `
            <div style="background:#fff;border-radius:20px;padding:32px 28px;max-width:360px;
                        width:100%;text-align:center;box-shadow:0 24px 60px rgba(0,0,0,.2);
                        animation:mkAdminIn .22s ease;">
                <div style="width:52px;height:52px;border-radius:50%;margin:0 auto 14px;
                             background:rgba(248,113,113,.1);border:2px solid rgba(248,113,113,.25);
                             display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-exclamation-triangle" style="color:#e63946;font-size:1.2rem;"></i>
                </div>
                <div style="font-family:'Playfair Display',serif;font-size:1.05rem;font-weight:700;
                            color:#1a1a2e;margin-bottom:8px;">${title}</div>
                <div style="font-size:.83rem;color:#6b7280;line-height:1.6;margin-bottom:22px;">${message}</div>
                <div style="display:flex;gap:10px;justify-content:center;">
                    <button id="mkAdminYes" style="flex:1;max-width:140px;background:linear-gradient(135deg,#e63946,#f87171);
                        color:#fff;border:none;border-radius:50px;padding:10px 18px;font-size:.85rem;
                        font-weight:700;cursor:pointer;font-family:'Inter',sans-serif;">Confirm</button>
                    <button id="mkAdminNo" style="flex:1;max-width:140px;background:#f0f7f4;
                        color:#2d6a4f;border:1px solid rgba(116,198,157,.3);border-radius:50px;
                        padding:10px 18px;font-size:.85rem;font-weight:600;cursor:pointer;
                        font-family:'Inter',sans-serif;">Cancel</button>
                </div>
            </div>
            <style>@keyframes mkAdminIn{from{opacity:0;transform:scale(.92)}to{opacity:1;transform:scale(1)}}</style>`;
        modal.style.display = 'flex';
        const close = () => { modal.style.display='none'; __mkPendingForm=null; };
        document.getElementById('mkAdminYes').onclick = () => {
            modal.style.display='none';
            if (__mkPendingForm) __mkPendingForm.submit();
            __mkPendingForm = null;
        };
        document.getElementById('mkAdminNo').onclick = close;
        modal.onclick = (e) => { if(e.target===modal) close(); };
        return false;
    }

    function mkAdminAlert(title, message) {
        let modal = document.getElementById('mkAdminAlertModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'mkAdminAlertModal';
            modal.style.cssText = 'position:fixed;inset:0;z-index:99999;display:flex;align-items:center;justify-content:center;padding:20px;background:rgba(13,27,42,.88);backdrop-filter:blur(8px);';
            document.body.appendChild(modal);
        }
        modal.innerHTML = `
            <div style="background:#fff;border-radius:20px;padding:32px 28px;max-width:340px;
                        width:100%;text-align:center;box-shadow:0 24px 60px rgba(0,0,0,.2);
                        animation:mkAdminIn .22s ease;">
                <div style="width:48px;height:48px;border-radius:50%;margin:0 auto 14px;
                             background:rgba(244,162,97,.1);border:2px solid rgba(244,162,97,.3);
                             display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-info-circle" style="color:#f4a261;font-size:1.2rem;"></i>
                </div>
                <div style="font-family:'Playfair Display',serif;font-size:1rem;font-weight:700;
                            color:#1a1a2e;margin-bottom:8px;">${title}</div>
                <div style="font-size:.83rem;color:#6b7280;line-height:1.6;margin-bottom:20px;">${message}</div>
                <button id="mkAdminAlertOk" style="background:linear-gradient(135deg,#40916c,#74c69d);
                    color:#fff;border:none;border-radius:50px;padding:10px 28px;font-size:.85rem;
                    font-weight:700;cursor:pointer;font-family:'Inter',sans-serif;">OK</button>
            </div>
            <style>@keyframes mkAdminIn{from{opacity:0;transform:scale(.92)}to{opacity:1;transform:scale(1)}}</style>`;
        modal.style.display = 'flex';
        const close = () => { modal.style.display='none'; };
        document.getElementById('mkAdminAlertOk').onclick = close;
        modal.onclick = (e) => { if(e.target===modal) close(); };
    }
</script>
</body>
</html>
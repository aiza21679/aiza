<?php
// org-dashboard.php
// NOTE: no output before this file (no BOM). session_start at top.
session_start();

$ORG_ID = isset($_SESSION['org_id']) ? (int)$_SESSION['org_id'] : null;

// default fallback
$profile = [
  'name' => 'Organization User',
  'position' => 'Organization User',
  'email' => '',
  'avatar' => '/eBotoni/eBotoni/assests/default-logo.jpg'
];

// Initialize metrics with safe defaults
$totalVoters = 0;
$activeElections = 0;

/**
 * Resolve logo path stored in DB to a root-relative or absolute path.
 * Adjust $projectPrefix if your project sits elsewhere.
 */
function resolve_logo_url(string $dbPath = ''): string {
    $projectPrefix = '/eBotoni/eBotoni/'; // <-- set to your project folder
    $default = $projectPrefix . 'assests/default-logo.jpg';

    $p = trim((string)$dbPath);
    if ($p === '') return $default;

    if (preg_match('#^https?://#i', $p)) return $p; // absolute
    if ($p[0] === '/') return $p;                  // root-relative

    // strip leading ../ or ./
    $clean = preg_replace('#^(\.\/|(\.\.\/)+)#', '', $p);

    if (strpos($clean, trim($projectPrefix, '/')) !== false) {
        return '/' . ltrim($clean, '/');
    }

    return rtrim($projectPrefix, '/') . '/' . ltrim($clean, '/');
}

/* ---------------------------
   Load profile and metrics (PDO)
   --------------------------- */
if ($ORG_ID) {
    try {
        $pdo = new PDO('mysql:host=127.0.0.1;dbname=eboto_db;charset=utf8mb4','root','',[
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        // Load organization profile
        $stmt = $pdo->prepare("SELECT Org_Name, Email, Logo_Url FROM student_organization WHERE Org_ID = ? LIMIT 1");
        $stmt->execute([$ORG_ID]);
        $row = $stmt->fetch();
        if ($row) {
            $profile['name'] = !empty($row['Org_Name']) ? $row['Org_Name'] : $profile['name'];
            $profile['email'] = !empty($row['Email']) ? $row['Email'] : $profile['email'];
            $profile['position'] = $row['Org_Name'] ? 'Organization User' : $profile['position'];
            $profile['avatar'] = resolve_logo_url($row['Logo_Url'] ?? '');
        }

        // Attempt to count voters using a few likely table names/structures.
        $foundVoters = false;
        try {
            $cols = $pdo->query("SHOW COLUMNS FROM `student`")->fetchAll(PDO::FETCH_COLUMN, 0);
            $possibleOrgCols = ['Org_ID','org_id','organization_id','OrganizationID','orgId','OrgId'];
            $orgCol = null;
            foreach ($possibleOrgCols as $c) {
                if (in_array($c, $cols, true)) { $orgCol = $c; break; }
            }
            if ($orgCol) {
                $q = "SELECT COUNT(*) AS cnt FROM `student` WHERE `$orgCol` = ?";
                $stm = $pdo->prepare($q);
                $stm->execute([$ORG_ID]);
                $r = $stm->fetch();
                if ($r && isset($r['cnt'])) { $totalVoters = (int)$r['cnt']; $foundVoters = true; }
            }
        } catch (Exception $e) {
            // ignore and continue to other heuristics
        }

        if (!$foundVoters) {
            $tryTables = ['student_organization_members','student_organization_member','organization_members','org_members','student_members','members'];
            foreach ($tryTables as $t) {
                try {
                    $q = "SELECT COUNT(*) AS cnt FROM `{$t}` WHERE org_id = ?";
                    $stm = $pdo->prepare($q);
                    $stm->execute([$ORG_ID]);
                    $r = $stm->fetch();
                    if ($r && isset($r['cnt'])) { $totalVoters = (int)$r['cnt']; $foundVoters = true; break; }
                } catch (Exception $e) { }
            }
        }

        if (!$foundVoters) {
            $voterTables = ['organization_voters','voters','student_voters','student_voter','org_voters'];
            foreach ($voterTables as $tbl) {
                try {
                    $q = "SELECT COUNT(*) AS cnt FROM `{$tbl}` WHERE Org_ID = ?";
                    $stm = $pdo->prepare($q);
                    $stm->execute([$ORG_ID]);
                    $r = $stm->fetch();
                    if ($r && isset($r['cnt'])) { $totalVoters = (int)$r['cnt']; $foundVoters = true; break; }
                } catch (Exception $e) { }
            }
        }

        if (!$foundVoters) {
            try {
                $stm = $pdo->query("SELECT COUNT(*) AS cnt FROM `student`");
                $r = $stm->fetch();
                if ($r && isset($r['cnt'])) $totalVoters = (int)$r['cnt'];
            } catch (Exception $e) { }
        }

        // -------------------
        // Active elections heuristics (server-side attempt)
        // -------------------
        $activeElections = 0;
        $electionTables = ['elections', 'student_elections', 'organization_elections', 'election', 'org_elections'];

        foreach ($electionTables as $et) {
            try {
                $stm = $pdo->prepare("SELECT COUNT(*) AS cnt FROM `{$et}` WHERE Org_ID = ? AND (status = 'active' OR status = 'in_progress' OR status = 'ongoing')");
                $stm->execute([$ORG_ID]);
                $r = $stm->fetch();
                if ($r && isset($r['cnt']) && (int)$r['cnt'] > 0) { $activeElections = (int)$r['cnt']; break; }
            } catch (Exception $e) { }

            try {
                $stm = $pdo->prepare(
                    "SELECT COUNT(*) AS cnt FROM `{$et}` WHERE Org_ID = ? AND (
                        ( (COALESCE(Start_Date, start_date) IS NOT NULL) AND (COALESCE(End_Date, end_date) IS NOT NULL) AND (NOW() BETWEEN COALESCE(Start_Date, start_date) AND COALESCE(End_Date, end_date)) )
                    )"
                );
                $stm->execute([$ORG_ID]);
                $r = $stm->fetch();
                if ($r && isset($r['cnt']) && (int)$r['cnt'] > 0) { $activeElections = (int)$r['cnt']; break; }
            } catch (Exception $e) { }
        }

        if ($activeElections === 0) {
            try {
                $stm = $pdo->prepare("SELECT COUNT(*) AS cnt FROM `elections` WHERE Org_ID = ? AND (is_active = 1 OR active = 1)");
                $stm->execute([$ORG_ID]);
                $r = $stm->fetch();
                if ($r && isset($r['cnt'])) $activeElections = (int)$r['cnt'];
            } catch (Exception $e) { }
        }

    } catch (Exception $e) {
        error_log("Profile/metrics error: " . $e->getMessage());
    }
}

/* ---------------------------
   mysqli fallback for voters if PDO heuristics didn't find anything.
   --------------------------- */
if (($totalVoters === 0) && $ORG_ID) {
    $mysqli = new mysqli('localhost','root','','eboto_db');
    if (!$mysqli->connect_errno) {
        $mysqli->set_charset('utf8mb4');

        $tryTables = ['student_organization_members','student_organization_member','organization_members','org_members'];
        $found = false;
        foreach ($tryTables as $t) {
            $q = "SHOW TABLES LIKE '{$t}'";
            $res = $mysqli->query($q);
            if ($res && $res->num_rows > 0) {
                $stmt = $mysqli->prepare("SELECT COUNT(*) AS cnt FROM `{$t}` WHERE org_id = ?");
                if ($stmt) {
                    $stmt->bind_param('i', $ORG_ID);
                    $stmt->execute();
                    $res2 = $stmt->get_result();
                    if ($res2) {
                        $r = $res2->fetch_assoc();
                        if ($r && isset($r['cnt'])) { $totalVoters = (int)$r['cnt']; $found = true; }
                    }
                    $stmt->close();
                }
            }
            if ($found) break;
        }

        if (!$found) {
            $res = $mysqli->query("SHOW TABLES LIKE 'student'");
            if ($res && $res->num_rows > 0) {
                $colsRes = $mysqli->query("SHOW COLUMNS FROM `student`");
                $cols = [];
                while ($c = $colsRes->fetch_assoc()) $cols[] = $c['Field'];
                $possibleOrgCols = ['Org_ID','org_id','organization_id','OrganizationID','orgId','OrgId'];
                $orgCol = null;
                foreach ($possibleOrgCols as $c) { if (in_array($c, $cols, true)) { $orgCol = $c; break; } }

                if ($orgCol) {
                    $stmt = $mysqli->prepare("SELECT COUNT(*) AS cnt FROM `student` WHERE `$orgCol` = ?");
                    if ($stmt) {
                        $stmt->bind_param('i', $ORG_ID);
                        $stmt->execute();
                        $res2 = $stmt->get_result();
                        if ($res2) {
                            $r = $res2->fetch_assoc();
                            if ($r && isset($r['cnt'])) $totalVoters = (int)$r['cnt'];
                        }
                        $stmt->close();
                    }
                } else {
                    $res2 = $mysqli->query("SELECT COUNT(*) AS cnt FROM `student`");
                    if ($res2) {
                        $r = $res2->fetch_assoc();
                        if ($r && isset($r['cnt'])) $totalVoters = (int)$r['cnt'];
                    }
                }
            }
        }

        $mysqli->close();
    }
}

// Expose to javascript
?>
<script>
  window.SERVER_PROFILE = <?php echo json_encode($profile, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE); ?>;
  window.SESSION_ORG_ID = <?php echo json_encode($ORG_ID); ?>;
  window.SERVER_COUNTS = {
    totalVoters: <?php echo (int)($totalVoters ?? 0); ?>,
    activeElections: <?php echo (int)($activeElections ?? 0); ?>
  };
</script>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SOCE Student Organization - Dashboard</title>

    <!-- Icons & chart lib (no CSS) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
    // small helper to resolve avatar URLs (returns absolute where appropriate)
    function resolveAvatarUrl(url) {
        const fallback = '/eBotoni/eBotoni/assests/default-avatar.jpg';
        if (!url) return fallback;
        url = String(url).trim();
        if (!url) return fallback;
        if (/^https?:\/\//i.test(url)) return url;
        const cleaned = url.replace(/^(\.\.\/)+/, '').replace(/^\/+/, '');
        return window.location.origin + '/' + cleaned;
    }

    // Parse date helper reused for client-side active-election calculation
    function parseDateInputClient(raw){
      if(raw === null|| raw===undefined || raw === '') return null;
      const str = String(raw).trim();
      const dOnly = str.match(/^(\d{4})-(\d{2})-(\d{2})$/);
      if(dOnly) return new Date(str + 'T00:00:00');
      const n = Number(str);
      if(!isNaN(n)) {
        if(String(n).length <= 10) return new Date(n*1000);
        return new Date(n);
      }
      const d = new Date(str);
      return isNaN(d) ? null : d;
    }

    // Decide if an election object is active based on fields similar to manage-elections logic.
    function electionIsActive(e){
      const s = (e.status || e.status_text || e.Status || '').toString().toLowerCase();
      if(s === 'active' || s === 'in_progress' || s === 'ongoing') return true;
      const startRaw = e.startDate ?? e.start_date ?? e.Start_Date ?? e.StartDate ?? e.start;
      const endRaw   = e.endDate ?? e.end_date ?? e.End_Date ?? e.EndDate ?? e.end;
      const start = parseDateInputClient(startRaw);
      const end = parseDateInputClient(endRaw);
      if(start && end){
        const now = new Date();
        return now >= start && now <= end;
      }
      if(e.is_active == 1 || e.active == 1 || e.IsActive === true) return true;
      return false;
    }
    </script>
</head>
<body>

     <!-- SIDEBAR -->
  <nav class="sidebar" id="sidebar">
    <div class="logo-container">
      <div class="logo"><img id="sidebarLogoImage" src="/eBotoni/eBotoni/assests/suffage.png" alt="Logo"></div>
    </div>

 <ul class="nav-menu">
  <li class="nav-item active"><i class="fas fa-home"></i><a href="org-dashboard.php">Dashboard</a></li>
  <li class="nav-item"><i class="fas fa-user-graduate"></i><a href="org-voters.php">Organization Voters</a></li>
  <li class="nav-item"><i class="fas fa-user-tie"></i><a href="manage-candidates.php">Candidates</a></li>
  <li class="nav-item"><i class="fas fa-user-tag"></i><a href="manage-elections.php">Election Monitoring</a></li>
  <li class="nav-item"><i class="fas fa-check-circle"></i><a href="election-results.php">Election Results</a></li>
  <li class="nav-item"><i class="fas fa-cog"></i><a href="settings.php">Settings</a></li>
</ul>
        
        <div class="user-profile" id="userProfile">
            <!-- dynamic avatar -->
         <img id="sidebarAvatar"
     src="<?php echo htmlspecialchars($profile['avatar'], ENT_QUOTES); ?>"
     class="user-avatar" alt="Org Avatar">

            <div class="user-info">
                <h4 id="sidebarName">Organization User</h4>
                <p id="sidebarRole">Organization User</p>
            </div>
            <div class="user-dropdown" id="dropdown" style="display:none;">
                <a href="../login.html" id="logoutBtn">Logout</a>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <div class="header">
            <div class="page-title">
                <h1 id="pageTitle">Organization Dashboard</h1>
                <p id="pageSubtitle">Welcome back, Organization User. Here's an overview of the election.</p>
            </div>
            
            <div class="header-actions">
                <div class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </div>
            </div>
        </div>
        
        <!-- Dashboard Content -->
        <div class="access-notice">
            <i class="fas fa-info-circle"></i>
            <p><strong>Stakeholder Access:</strong> You have limited access to organization data only. Some features are restricted.</p>
        </div>
        
        <div class="metrics-grid">
            <div class="metric-card metric-1">
                <div class="metric-icon"><i class="fas fa-user-graduate"></i></div>
                <div class="metric-info">
                    <h3>SOCE Registered Voters</h3>
                    <div class="metric-value" id="totalVoters"><?php echo htmlspecialchars($totalVoters ?? 0, ENT_QUOTES); ?></div>
                    <div class="metric-trend trend-up"><i class="fas fa-check-circle"></i> All members eligible</div>
                </div>
            </div>
            
            <div class="metric-card metric-2">
                <div class="metric-icon"><i class="fas fa-vote-yea"></i></div>
                <div class="metric-info">
                    <h3>Votes Cast Today</h3>
                    <div class="metric-value" id="votesToday">0</div>
                    <div class="metric-trend trend-up"><i class="fas fa-arrow-up"></i> High Activity</div>
                </div>
            </div>
            
            <div class="metric-card metric-3">
                <div class="metric-icon"><i class="fas fa-chart-line"></i></div>
                <div class="metric-info">
                    <h3>SOCE Participation Rate</h3>
                    <div class="metric-value" id="participationRate">—</div>
                    <div class="metric-trend trend-up"><i class="fas fa-chart-line"></i> On Track (Goal: 80%)</div>
                </div>
            </div>
            
            <div class="metric-card metric-4">
                <div class="metric-icon"><i class="fas fa-clock"></i></div>
                <div class="metric-info">
                    <h3>Active SOCE Election</h3>
                    <div class="metric-value" id="activeElections"><?php echo htmlspecialchars($activeElections ?? 0, ENT_QUOTES); ?></div>
                    <div class="metric-trend"><i class="fas fa-circle"></i> In Progress</div>
                </div>
            </div>
        </div>

        <h2 class="section-title">System Status & Alerts</h2>
        <div class="system-status">
            <div class="status-item"><div class="status-indicator status-operational"></div>
                <p>All systems operational.</p></div>
            <div class="access-note"><p><strong>Stakeholder Access:</strong> You have limited access to organization data.</p></div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast" id="toast" style="display:none;"></div>

<!-- SETTINGS UI (copied and integrated without CSS) -->
<!-- MAIN SETTINGS (this is the settings area you asked to integrate) -->
<div id="settingsArea" style="display:none;">
  <div class="settings-container">
    <div class="settings-section">
      <h3>Profile Settings</h3>

      <div class="profile-section">
        <img src="" class="profile-avatar" id="profileAvatarDisplay" alt="Profile Avatar">

        <div class="profile-info">
          <h3 id="profileNameDisplay">Organization User</h3>
          <p id="profilePositionDisplay">Organization User • email@domain.com</p>

          <label class="upload-btn">
            <i class="fas fa-camera"></i> Change Profile Picture
            <input type="file" class="file-upload" id="avatarUpload" accept="image/*">
          </label>
        </div>
      </div>

      <div class="settings-option">
        <div class="option-info">
          <h4>Personal Information</h4>
          <p>Update your name, email, and contact information</p>
        </div>
        <div class="option-actions">
          <button class="btn btn-primary" id="editProfileBtn">
            <i class="fas fa-edit"></i> Edit Profile
          </button>
        </div>
      </div>

      <div class="settings-option">
        <div class="option-info">
          <h4>Change Password</h4>
          <p>Update your password for security</p>
        </div>
        <div class="option-actions">
          <button class="btn btn-secondary" id="changePasswordBtn">
            <i class="fas fa-key"></i> Change Password
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- EDIT PROFILE MODAL -->
<div class="modal" id="editProfileModal" style="display:none;">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Edit Profile</h3>
      <button class="close-modal" id="closeEditProfileModal">&times;</button>
    </div>

    <div class="modal-body">
      <form id="editProfileForm" enctype="multipart/form-data" onsubmit="return false;">
        <label>Full Name</label>
        <input type="text" id="profileName" required>

        <label>Email Address</label>
        <input type="email" id="profileEmail" required>

        <label>Phone Number</label>
        <input type="text" id="profilePhone">

        <label>Position</label>
        <input type="text" id="profilePosition" required>

        <label>Bio</label>
        <textarea id="profileBio"></textarea>

        <input type="hidden" id="currentAvatarUrl" value="">
      </form>
    </div>

    <div class="modal-footer">
      <button class="btn btn-secondary" id="cancelEditProfile">Cancel</button>
      <button class="btn btn-primary" id="saveProfile">Save Changes</button>
    </div>
  </div>
</div>

<!-- CHANGE PASSWORD MODAL -->
<div class="modal" id="changePasswordModal" style="display:none;">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Change Password</h3>
      <button class="close-modal" id="closeChangePasswordModal">&times;</button>
    </div>

    <div class="modal-body">
      <form id="changePasswordForm" onsubmit="return false;">
        <label>Current Password</label>
        <input type="password" id="currentPassword" required>

        <label>New Password</label>
        <input type="password" id="newPassword" required>

        <label>Confirm Password</label>
        <input type="password" id="confirmPassword" required>
      </form>
    </div>

    <div class="modal-footer">
      <button class="btn btn-secondary" id="cancelChangePassword">Cancel</button>
      <button class="btn btn-primary" id="savePassword">Change Password</button>
    </div>
  </div>
</div>

<script>
/* ---------- Apply server profile to DOM ---------- */
function applyServerProfileToDOM() {
    const p = window.SERVER_PROFILE || {};
    const avatarEl = document.getElementById('sidebarAvatar');
    const logoEl = document.getElementById('sidebarLogoImage');
    const nameEl   = document.getElementById('sidebarName');
    const roleEl   = document.getElementById('sidebarRole');
    const subtitle = document.getElementById('pageSubtitle');

    try {
        if (avatarEl) {
            avatarEl.src = resolveAvatarUrl(p.avatar || p.Logo_Url || p.avatar_url || '');
            avatarEl.onerror = () => { avatarEl.src = '/eBotoni/eBotoni/assests/default-avatar.jpg'; };
        }

        if (logoEl) {
          logoEl.src = '/eBotoni/eBotoni/assests/suffage.png';
          logoEl.onerror = () => { logoEl.src = '/eBotoni/eBotoni/assests/suffage.png'; };
        }

        if (nameEl && p.name) nameEl.textContent = p.name;
        if (roleEl && p.position) roleEl.textContent = p.position;
        if (subtitle) subtitle.textContent = `Welcome back, ${p.name || 'User'}. Here's an overview of the ${p.name || 'organization'} election.`;

        const profileNameDisplay = document.getElementById('profileNameDisplay');
        if (profileNameDisplay) profileNameDisplay.textContent = p.name || 'Organization User';
        const posDisplay = document.getElementById('profilePositionDisplay');
        if (posDisplay) posDisplay.textContent = `${p.position || 'Organization User'} • ${p.email || ''}`;

        const profileAvatarDisplay = document.getElementById('profileAvatarDisplay');
        if (profileAvatarDisplay) profileAvatarDisplay.src = resolveAvatarUrl(p.avatar || '');

        const currentAvatarUrl = document.getElementById('currentAvatarUrl');
        if (currentAvatarUrl) currentAvatarUrl.value = resolveAvatarUrl(p.avatar || '');
    } catch (err) {
        console.warn('applyServerProfile error', err);
    }
}

/* ---------- Toast utility ---------- */
function showToast(message, type = 'info') {
    const toast = document.getElementById('toast');
    if (!toast) {
        console.log(type.toUpperCase(), message);
        return;
    }
    toast.textContent = message;
    toast.style.display = 'block';
    clearTimeout(window._toastTimeout);
    window._toastTimeout = setTimeout(() => { toast.style.display = 'none'; }, 3000);
}

/* ---------- Event listeners & UI wiring ---------- */
function setupEventListeners() {
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const userProfile = document.getElementById('userProfile');
    const dropdown = document.getElementById('dropdown');
    const logoutBtn = document.getElementById('logoutBtn');

    if (menuToggle) menuToggle.addEventListener('click', () => sidebar.classList.toggle('active'));
    if (userProfile) userProfile.addEventListener('click', () => {
        if (dropdown) dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    });
    if (logoutBtn) logoutBtn.addEventListener('click', (e) => {
        e.preventDefault();
        showToast('Logging out...', 'info');
        setTimeout(() => { window.location.href = '../login.html'; }, 600);
    });
}

/* ---------- Election / Votes integration (re-uses election-results logic) ---------- */

const API_CANDIDATE_BASES = [
  "http://localhost/eBotoni/eBotoni/student/",
  "/eBotoni/eBotoni/student/",
  "/eBotoni/eBotoni/student%20org/",
  "../student/",
  "../student%20org/",
  "./student/",
  "./student%20org/"
];

let resolvedApiBase = null;
let elections = [];
let partyCandidates = {};

function safeTextPreview(s, n = 600){ if (!s) return ''; return (s.length > n) ? s.slice(0,n) + ' … (truncated)' : s; }
function isLikelyJson(text){ if (!text || typeof text !== 'string') return false; const t = text.trim(); return (t.startsWith('{') || t.startsWith('[')); }
function tryParseJson(text){ try { return JSON.parse(text); } catch(e) { return null; } }
async function fetchText(url){ try { const res = await fetch(url, { cache: "no-store", credentials: 'include' }); const text = await res.text(); return { ok: res.ok, status: res.status, text }; } catch (err) { return { ok:false, error:err, text:null }; } }

async function detectApiBase(){
  if (resolvedApiBase !== null) return resolvedApiBase;
  for (const cand of API_CANDIDATE_BASES){
    let v = cand || '';
    if (v && !v.endsWith('/')) v = v + '/';
    const url = (v ? v : '') + 'get_elections.php';
    try {
      const r = await fetchText(url);
      if (r && r.text && isLikelyJson(r.text)) {
        resolvedApiBase = v;
        return resolvedApiBase;
      }
      if (r && r.ok) {
        resolvedApiBase = v;
        return resolvedApiBase;
      }
    } catch(e){ /* ignore */ }
  }
  resolvedApiBase = API_CANDIDATE_BASES[0].endsWith('/') ? API_CANDIDATE_BASES[0] : API_CANDIDATE_BASES[0] + '/';
  return resolvedApiBase;
}

async function getJSON(path){
  if (!resolvedApiBase) await detectApiBase();
  const base = resolvedApiBase || '';
  const cleanPath = path.replace(/^\//,'');
  const url = (base ? base : '') + cleanPath;
  try {
    const r = await fetchText(url);
    if (!r) return null;
    if (!r.ok) {
      if (r.text && isLikelyJson(r.text)) return tryParseJson(r.text);
      return null;
    }
    if (!r.text || !isLikelyJson(r.text)) return null;
    return tryParseJson(r.text);
  } catch (err) { return null; }
}

/* fetch candidates (optional, kept for parity with election-results behavior) */
async function fetchCandidates(){
  try {
    if (!resolvedApiBase) await detectApiBase();
    const base = resolvedApiBase || '';
    const url = (base ? base : '') + 'get_candidates.php';
    const r = await fetchText(url);
    if (!r || !r.text) return;
    const j = tryParseJson(r.text);
    const rows = Array.isArray(j) ? j : (j && Array.isArray(j.data) ? j.data : []);
    buildPartyCandidatesFromRows(rows);
  } catch (e) { console.warn('fetchCandidates failed', e); }
}
function buildPartyCandidatesFromRows(rows){
  partyCandidates = {};
  if (!Array.isArray(rows)) return;
  rows.forEach(r=>{
    const candidate = {
      id: String(r.id ?? r.Candidate_ID ?? r.candidate_id ?? '') || '',
      name: r.name ?? r.FullName ?? r.Name ?? 'Candidate',
      position: r.position ?? r.Position ?? 'President',
      avatar_url: r.avatar ?? r.avatar_url ?? '',
      party: r.party ?? r.partyList ?? r.Party ?? 'Independent'
    };
    if (!partyCandidates[candidate.party]) partyCandidates[candidate.party] = [];
    partyCandidates[candidate.party].push(candidate);
  });
}

/* loadElections (same robust logic) */
async function loadElections(){
  const data = await getJSON('get_elections.php' + (window.SESSION_ORG_ID ? ('?org=' + encodeURIComponent(window.SESSION_ORG_ID)) : ''));
  if (!data) { elections = []; updateDashboardVoteAndActiveStats(); return; }
  const rows = Array.isArray(data) ? data : (data.data || []);
  elections = rows.map(e=>{
    const dbId = Number(e.Election_ID ?? e.election_id ?? e.id ?? e.ID ?? 0) || 0;
    const id = String(dbId || e.id || e.Election_ID || e.election_id || '').trim();
    const start = e.Start_Date ?? e.start_date ?? e.startDate ?? e.start ?? '';
    const end = e.End_Date ?? e.end_date ?? e.endDate ?? e.end ?? '';
    const totalVoters = Number(e.total_voters ?? e.Total_Voters ?? e.totalVoters ?? 0) || 0;
    const votesCast = Number(e.votesCast ?? e.votes_cast ?? e.votes ?? 0) || 0;
    const votesArr = Array.isArray(e.votes) ? e.votes : [];
    const status = String(e.Status ?? e.status ?? '').toLowerCase() || ((start && end) ? (new Date() < new Date(start) ? 'upcoming' : (new Date() <= new Date(end) ? 'active' : 'completed')) : 'upcoming');
    return {
      dbId, id, title: e.Title ?? e.title ?? e.name ?? 'Untitled Election',
      startDate: start, endDate: end, totalVoters,
      votesCast: votesCast || (Array.isArray(votesArr) ? votesArr.length : 0),
      votes: Array.isArray(votesArr) ? votesArr : [],
      status, raw: e
    };
  }).filter(x => x.dbId || x.id);
  updateDashboardVoteAndActiveStats();
}

/* compute aggregated vote count and active elections and update DOM counters */
function updateDashboardVoteAndActiveStats(){
  try {
    const totalVotes = elections.reduce((acc,e) => acc + (Number(e.votesCast) || 0), 0);
    const serverTotalVoters = Number(window.SERVER_COUNTS && window.SERVER_COUNTS.totalVoters ? window.SERVER_COUNTS.totalVoters : 0);
    const participationPct = serverTotalVoters > 0 ? Math.round((totalVotes / serverTotalVoters) * 100) : '—';

    // Compute active election count using same client heuristic
    let activeCount = 0;
    try {
      for (const e of elections) {
        if (electionIsActive(e)) activeCount++;
      }
    } catch (err) { console.warn('active count compute failed', err); }

    // Update DOM
    const votesTodayEl = document.getElementById('votesToday');
    if (votesTodayEl) votesTodayEl.textContent = totalVotes ? Number(totalVotes).toLocaleString() : '0';

    const participationEl = document.getElementById('participationRate');
    if (participationEl) participationEl.textContent = (serverTotalVoters > 0 ? `${participationPct}%` : '—');

    const tv = document.getElementById('totalVoters');
    if (tv && serverTotalVoters) tv.textContent = serverTotalVoters;

    const ae = document.getElementById('activeElections');
    if (ae) ae.textContent = String(activeCount);
    if (window.SERVER_COUNTS) window.SERVER_COUNTS.activeElections = activeCount;

  } catch (err) {
    console.warn('updateDashboardVoteAndActiveStats error', err);
  }
}

/* orchestrator */
async function refreshElectionCounts(){
  try {
    await detectApiBase();
    await fetchCandidates().catch(()=>{});
    await loadElections().catch(()=>{});
  } catch (err) {
    console.warn('refreshElectionCounts error', err);
  }
}

/* ---------- Initialize everything on DOM ready ---------- */
document.addEventListener('DOMContentLoaded', () => {
    applyServerProfileToDOM();
    setupEventListeners();

    // populate server counts initially
    if (window.SERVER_COUNTS) {
      const tv = document.getElementById('totalVoters');
      if (tv) tv.textContent = window.SERVER_COUNTS.totalVoters;
      const ae = document.getElementById('activeElections');
      if (ae) ae.textContent = window.SERVER_COUNTS.activeElections;
    }

    // Run the election-results-style refresh so Votes Cast and Active Elections come from DB-backed API
    (async function start() {
      await refreshElectionCounts();
      // set interval to refresh every 15s (same as election-results)
      setInterval(refreshElectionCounts, 15000);
    })();

    // existing active-elections sync (keeps parity with rest of dashboard)
    if (typeof fetchAndUpdateActiveElections === 'function') {
      fetchAndUpdateActiveElections();
      setInterval(fetchAndUpdateActiveElections, 30000);
    }

    showToast('Welcome to SOCE Election Dashboard!', 'info');
});
</script>

</body>
</html>


        <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

         :root {
      --ustp-blue: #003366;
      --ustp-light-blue: #0066cc;
      --ustp-gold: #ffcc00;
      --ustp-white: #ffffff;
      --ustp-light-gray: #f5f5f5;
      --ustp-dark-gray: #333333;
      --sidebar-width: 250px;
      --header-height: 60px;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
      display: flex;
      min-height: 100vh;
      background-color: var(--ustp-light-gray);
      color: var(--ustp-dark-gray);
    }

    /* SIDEBAR */
    .sidebar {
      width: var(--sidebar-width);
      background: linear-gradient(180deg, var(--ustp-blue) 0%, var(--ustp-light-blue) 100%);
      color: var(--ustp-white);
      padding: 20px 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      position: fixed;
      top: 0;
      left: 0;
      height: 100vh;
      box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
      z-index: 100;
      transition: 0.3s ease;
    }

    .logo-container {
      text-align: center;
      width: 100%;
      padding: 0 20px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      margin-bottom: 20px;
    }

    .logo {
      width: 130px;
      height: 130px;
      border-radius: 50%;
      overflow: hidden;
      margin: 0 auto 10px auto;
      border: 4px solid var(--ustp-gold);
      background-color: var(--ustp-white);
    }

    .logo img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .logo-container h2 {
      font-size: 1.3rem;
      font-weight: 700;
      color: var(--ustp-white);
    }

    /* NAVIGATION */
    .nav-menu {
      flex: 1;
      list-style: none;
      padding: 0;
      width: 100%;
    }

    .nav-item {
      padding: 14px 25px;
      display: flex;
      align-items: center;
      gap: 12px;
      cursor: pointer;
      transition: all 0.3s ease;
      font-size: 15px;
    }

    .nav-item:hover {
      background-color: #004b99;
    }

    .nav-item.active {
      background-color: var(--ustp-gold);
      color: var(--ustp-blue);
      font-weight: bold;
      border-left: 4px solid var(--ustp-gold);
    }

    .nav-item i {
      width: 20px;
      text-align: center;
    }

    .nav-item a {
      text-decoration: none;
      color: inherit;
      display: block;
      width: 100%;
    }

        .user-profile {
            position: absolute;
            bottom: 20px;
            width: 100%;
            padding: 15px 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--accent);
        }

        .user-info h4 {
            font-size: 1rem;
            margin-bottom: 2px;
        }

        .user-info p {
            font-size: 0.8rem;
            opacity: 0.8;
        }

        .user-dropdown {
            display: none;
            position: absolute;
            bottom: 70px;
            left: 20px;
            background: var(--primary);
            border: 1px solid var(--accent);
            border-radius: 8px;
            overflow: hidden;
            width: 150px;
            z-index: 10;
        }

        .user-dropdown a {
            display: block;
            padding: 10px 20px;
            color: var(--light);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .user-dropdown a:hover {
            background: var(--accent);
            color: var(--primary);
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: all 0.3s ease;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            padding: 15px 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            animation: slideDown 0.5s ease-out;
        }

        .page-title h1 {
            font-size: 1.8rem;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            background-clip: text;
            -webkit-text-fill-color: Black;
            margin-bottom: 5px;
        }

        .page-title p {
            color: var(--gray);
            font-size: 0.9rem;
        }

        .header-actions {
            display: flex;
            align-items: center;
        }

        .menu-toggle {
            display: none;
            font-size: 1.5rem;
            color: var(--primary);
            cursor: pointer;
        }

        /* Dashboard Content */
        .access-notice {
            background: linear-gradient(135deg, #fff3cd, #ffeaa7);
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            animation: fadeIn 0.8s ease-out;
        }

        .access-notice i {
            color: #856404;
            font-size: 1.2rem;
        }

        .access-notice p {
            color: #856404;
            margin: 0;
            font-size: 0.9rem;
        }

        .election-status {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border-left: 4px solid var(--primary);
            position: relative;
            overflow: hidden;
            animation: slideUp 0.6s ease-out;
        }

        .election-status::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }

        .election-status h3 {
            font-size: 1.1rem;
            margin-bottom: 5px;
            color: var(--primary);
        }

        .election-status p {
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .election-progress {
            margin-top: 15px;
        }

        .progress-bar {
            height: 10px;
            background-color: var(--light-gray);
            border-radius: 5px;
            overflow: hidden;
            margin-bottom: 5px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            border-radius: 5px;
            width: 65%;
            transition: width 1s ease;
            position: relative;
            overflow: hidden;
        }

        .progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
            background-image: linear-gradient(
                -45deg,
                rgba(255, 255, 255, 0.2) 25%,
                transparent 25%,
                transparent 50%,
                rgba(255, 255, 255, 0.2) 50%,
                rgba(255, 255, 255, 0.2) 75%,
                transparent 75%,
                transparent
            );
            z-index: 1;
            background-size: 50px 50px;
            animation: move 2s linear infinite;
        }

        .progress-info {
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
            color: var(--gray);
        }

        /* Metrics Grid */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .metric-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
            animation: fadeIn 0.8s ease-out;
        }

        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }

        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .metric-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 1.5rem;
            transition: transform 0.3s ease;
        }

        .metric-card:hover .metric-icon {
            transform: scale(1.1);
        }

        .metric-1 .metric-icon { background: linear-gradient(135deg, rgba(0, 51, 102, 0.1), rgba(0, 51, 102, 0.2)); color: var(--primary); }
        .metric-2 .metric-icon { background: linear-gradient(135deg, rgba(255, 204, 0, 0.1), rgba(255, 204, 0, 0.2)); color: var(--accent); }
        .metric-3 .metric-icon { background: linear-gradient(135deg, rgba(0, 102, 204, 0.1), rgba(0, 102, 204, 0.2)); color: var(--secondary); }
        .metric-4 .metric-icon { background: linear-gradient(135deg, rgba(76, 181, 67, 0.1), rgba(76, 181, 67, 0.2)); color: var(--success); }

        .metric-info h3 {
            font-size: 0.9rem;
            color: var(--gray);
            margin-bottom: 5px;
        }

        .metric-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--primary);
        }

        .metric-trend {
            display: flex;
            align-items: center;
            font-size: 0.8rem;
        }

        .trend-up {
            color: var(--success);
        }

        .trend-down {
            color: var(--danger);
        }

        .metric-trend i {
            margin-right: 5px;
        }

        /* Quick Actions */
        .section-title {
            font-size: 1.3rem;
            margin: 30px 0 15px;
            color: var(--primary);
            position: relative;
            padding-bottom: 10px;
            animation: fadeIn 0.8s ease-out;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            border-radius: 2px;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .action-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            border: 1px solid transparent;
            position: relative;
            overflow: hidden;
            animation: fadeIn 0.8s ease-out;
        }

        .action-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            border-color: var(--primary);
        }

        .action-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            font-size: 1.3rem;
            transition: transform 0.3s ease;
        }

        .action-card:hover .action-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .action-1 .action-icon { background: linear-gradient(135deg, rgba(0, 51, 102, 0.1), rgba(0, 51, 102, 0.2)); color: var(--primary); }
        .action-2 .action-icon { background: linear-gradient(135deg, rgba(0, 102, 204, 0.1), rgba(0, 102, 204, 0.2)); color: var(--secondary); }
        .action-3 .action-icon { background: linear-gradient(135deg, rgba(255, 204, 0, 0.1), rgba(255, 204, 0, 0.2)); color: var(--accent); }
        .action-4 .action-icon { background: linear-gradient(135deg, rgba(108, 117, 125, 0.1), rgba(108, 117, 125, 0.2)); color: var(--gray); }

        .action-card h3 {
            font-size: 1rem;
            margin-bottom: 5px;
            color: var(--primary);
        }

        .action-card p {
            font-size: 0.8rem;
            color: var(--gray);
        }

        /* Recent Activity */
        .activity-feed {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            animation: fadeIn 0.8s ease-out;
        }

        .activity-item {
            display: flex;
            padding: 15px 0;
            border-bottom: 1px solid var(--light-gray);
            transition: background-color 0.2s ease;
            animation: slideIn 0.5s ease-out;
        }

        .activity-item:hover {
            background-color: rgba(0, 0, 0, 0.02);
            border-radius: 8px;
            padding-left: 10px;
            padding-right: 10px;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            background: linear-gradient(135deg, rgba(0, 51, 102, 0.1), rgba(0, 51, 102, 0.2));
            color: var(--primary);
            transition: transform 0.3s ease;
        }

        .activity-item:hover .activity-icon {
            transform: scale(1.1);
        }

        .activity-content h4 {
            font-size: 0.95rem;
            margin-bottom: 5px;
            color: var(--primary);
        }

        .activity-content p {
            font-size: 0.8rem;
            color: var(--gray);
        }

        /* System Status */
        .system-status {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            animation: fadeIn 0.8s ease-out;
        }

        .status-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .status-item:last-child {
            margin-bottom: 0;
        }

        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 10px;
            animation: pulse 2s infinite;
        }

        .status-operational {
            background-color: var(--success);
        }

        .status-warning {
            background-color: var(--warning);
        }

        .status-item p {
            font-size: 0.9rem;
        }

        .access-note {
            background: linear-gradient(135deg, rgba(0, 51, 102, 0.05), rgba(0, 51, 102, 0.1));
            border-left: 3px solid var(--primary);
            padding: 10px 15px;
            margin-top: 15px;
            border-radius: 0 5px 5px 0;
        }

        .access-note p {
            font-size: 0.8rem;
            color: var(--gray);
        }

        /* Toast notification */
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 15px 25px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
            z-index: 1001;
            display: flex;
            align-items: center;
            gap: 10px;
            transform: translateY(100px);
            opacity: 0;
            transition: transform 0.3s, opacity 0.3s;
        }

        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }

        .toast.success {
            background: linear-gradient(135deg, var(--success), #3a9c32);
        }

        .toast.error {
            background: linear-gradient(135deg, var(--danger), #c82333);
        }

        .toast.warning {
            background: linear-gradient(135deg, var(--warning), #e6b800);
            color: var(--dark);
        }

        .toast.info {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideDown {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @keyframes slideIn {
            from { transform: translateX(-20px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        @keyframes move {
            0% { background-position: 0 0; }
            100% { background-position: 50px 50px; }
        }

        /* Chart Container */
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            animation: fadeIn 0.8s ease-out;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .menu-toggle {
                display: block;
            }
        }

        @media (max-width: 768px) {
            .metrics-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .quick-actions {
                grid-template-columns: 1fr 1fr;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .header-actions {
                width: 100%;
                justify-content: flex-end;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 15px;
            }
            
            .metrics-grid {
                grid-template-columns: 1fr;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>

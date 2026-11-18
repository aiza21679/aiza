<?php
// admin/register-voters.php
// NOTE: no BOM / no output before <?php
session_start();
header('Content-Type: text/html; charset=utf-8');

// --- DB config ---
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'eboto_db';

$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($mysqli->connect_errno) {
    $dbError = "DB connection failed: " . $mysqli->connect_error;
    $mysqli = null;
} else {
    $mysqli->set_charset('utf8mb4');
    $dbError = null;
}

// --- helpers ---
function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function first_nonempty(...$vals) {
    foreach ($vals as $v) if (isset($v) && trim((string)$v) !== '') return trim((string)$v);
    return null;
}

// --- Determine selected org id (via GET or POST hidden context) ---
$orgId = null;
if (isset($_GET['org']) && is_numeric($_GET['org'])) $orgId = (int)$_GET['org'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['Org_ID']) && $_POST['Org_ID'] !== '') {
    $postedOrg = intval($_POST['Org_ID']);
    if ($postedOrg) $orgId = $postedOrg;
}

// --- Fetch organizations list (for boxes) ---
$organizations = [];
if ($mysqli) {
    $res = $mysqli->query("SELECT Org_ID, Org_Name, Description, Logo_Url, Email FROM student_organization ORDER BY Org_Name ASC");
    if ($res) {
        while ($r = $res->fetch_assoc()) $organizations[] = $r;
        $res->close();
    }
}

// --- Determine student Org column if exists ---
$studentCols = [];
$hasOrgCol = false;
$orgCol = null;
if ($mysqli) {
    $cRes = $mysqli->query("SHOW COLUMNS FROM `student`");
    if ($cRes) {
        while ($c = $cRes->fetch_assoc()) $studentCols[] = $c['Field'];
        $cRes->close();
    }
    $hasOrgCol = in_array('Org_ID', $studentCols, true) || in_array('org_id', $studentCols, true);
    $orgCol = in_array('Org_ID', $studentCols, true) ? 'Org_ID' : (in_array('org_id', $studentCols, true) ? 'org_id' : null);
}

// helper to count for an org using student table
function count_for_org($mysqli, $orgCol, $orgId) {
    $total = 0;
    $pending = 0;
    if (!$mysqli || !$orgCol) return [$total, $pending];

    $sOrg = (string)$orgId;

    $stmt = $mysqli->prepare("SELECT COUNT(*) AS c FROM `student` WHERE `$orgCol` = ?");
    if ($stmt) {
        $stmt->bind_param('s', $sOrg);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $r = $res->fetch_assoc()) $total = (int)$r['c'];
        if ($res) $res->free();
        $stmt->close();
    }

    $stmt2 = $mysqli->prepare("SELECT COUNT(*) AS c FROM `student` WHERE `$orgCol` = ? AND LOWER(COALESCE(`status`, '')) = 'pending'");
    if ($stmt2) {
        $stmt2->bind_param('s', $sOrg);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        if ($res2 && $r2 = $res2->fetch_assoc()) $pending = (int)$r2['c'];
        if ($res2) $res2->free();
        $stmt2->close();
    }

    return [$total, $pending];
}
// ---- logo resolver (attempts absolute + relative assests folder, then assets) ----
function resolve_logo($path) {
    // fallback default
    $default = '/eBotoni/eBotoni/assets/default-logo.jpg'; // absolute fallback (works from anywhere)
    if (!$path || trim($path) === '') return $default;

    $p = trim((string)$path);

    // if already an absolute URL
    if (preg_match('#^https?://#i', $p)) return $p;

    // If the DB already stores 'assests/filename' or 'assets/filename', normalize basename
    $filename = basename($p);

    // Try absolute project path first (your provided paths)
    $abs1 = "/eBotoni/eBotoni/assests/{$filename}";
    $abs2 = "/eBotoni/eBotoni/assets/{$filename}";

    // Try relative from admin folder (../assests/filename)
    $rel1 = "../assests/{$filename}";
    $rel2 = "../assets/{$filename}";

    // Prefer the absolute (server URL) paths if file exists on disk (server-side check)
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . $abs1)) return $abs1;
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . $abs2)) return $abs2;

    // otherwise prefer relative URLs that usually work when serving from admin folder
    if (file_exists(__DIR__ . "/../assests/" . $filename)) return $rel1;
    if (file_exists(__DIR__ . "/../assets/" . $filename)) return $rel2;

    // as last fallback return absolute default
    return $default;
}


// Build organizations with counts (server-side) to feed JS initial state
$orgs_with_counts = [];
foreach ($organizations as $org) {
    $oid = $org['Org_ID'];
    if ($hasOrgCol && $orgCol) {
        list($total, $pending) = count_for_org($mysqli, $orgCol, $oid);
    } else {
        $total = 0;
        $pending = 0;
    }
    $orgs_with_counts[] = [
        'Org_ID' => is_numeric($oid) ? (int)$oid : $oid,
        'Org_Name' => $org['Org_Name'],
        'Description' => $org['Description'],
        'Logo_Url' => $org['Logo_Url'],
        'Email' => $org['Email'],
        'Total_Voters' => $total,
        'Pending' => $pending
    ];
}

// Render HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>eBOTO | Voter Management</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

  <!-- SIDEBAR -->
  <nav class="sidebar" id="sidebar">
 <div class="logo">
  <img src="<?= resolve_logo('../assests/suffage.png') ?>" alt="Logo"
       onerror="this.onerror=null; this.src='/eBotoni/eBotoni/assets/default-logo.jpg'">
</div>

    <ul class="nav-menu">
      <li class="nav-item"><i class="fas fa-home"></i><a href="admin_dashboard.html">Dashboard</a></li>
      <li class="nav-item active"><i class="fas fa-user-graduate"></i><a href="register-voters.php">Voters</a></li>
      <li class="nav-item "><i class="fas fa-user-tie"></i><a href="officers.php">Officers</a></li>
      
      <li class="nav-item"><i class="fas fa-user-tag"></i><a href="candidates.php">Candidates</a></li>
      <li class="nav-item"><i class="fas fa-check-circle"></i><a href="validate-elections.php">Elections</a></li>
     <li class="nav-item "><i class="fas fa-chart-line"></i><a href="monitoring.php">Monitoring</a></li>
      <li class="nav-item"><i class="fas fa-file-alt"></i><a href="audit-reports.php">Reports</a></li>
      <li class="nav-item"><i class="fas fa-cog"></i><a href="settings.php">Settings</a></li>
    </ul>

  <div class="user-profile" id="userProfile" style="cursor:pointer;">
    <img id="sidebarAvatar" src="../assets/default-avatar.jpg" class="user-avatar" width="56" height="56" alt="avatar"/>
    <div class="user-info">
      <h4 id="sidebarName">Administrator</h4>
      <p id="sidebarRole">Administration</p>
    </div>
    <div class="user-dropdown" id="dropdown" style="display:none;">
      <a href="../login.html" id="logoutBtn">Logout</a>
    </div>
  </div>

  </nav>

  <!-- MAIN CONTENT -->
  <div class="main-content" id="mainContent">
    <div class="header">
      <h1>Voter Management</h1>
      <i class="fas fa-bars menu-toggle" id="menuToggle"></i>
    </div>

    <div class="content">
      <div class="voter-header">
        <h2 class="voter-title">Manage Voters by Organization</h2>
        <div class="search-box">
          <input type="text" class="search-input" placeholder="Search organizations..." id="searchInput">
          <button class="search-btn"><i class="fas fa-search"></i></button>
          <button class="add-org-btn" id="addOrgBtn">
            <i class="fas fa-plus"></i> Add Organization
          </button>
        </div>
      </div>

      <!-- Department Boxes -->
      <div class="department-boxes" id="departmentBoxes">
        <!-- Server-side fallback boxes -->
        <?php if (!empty($orgs_with_counts)): ?>
          <?php foreach ($orgs_with_counts as $org): ?>
            <div class="department-box" data-org-id="<?= esc($org['Org_ID']) ?>">
              <div class="department-header">
                <img src="<?= esc($org['Logo_Url'] ?: '../assets/default-logo.jpg') ?>" class="department-avatar" alt="<?= esc($org['Org_Name']) ?>">
                <div class="department-info">
                  <h3><?= esc($org['Org_Name']) ?></h3>
                  <p><?= esc($org['Description'] ?: 'No description available') ?></p>
                </div>
              </div>
              <div class="department-stats">
                <div class="stat">
                  <div class="stat-value"><?= (int)$org['Total_Voters'] ?></div>
                  <div class="stat-label">Total Voters</div>
                </div>
                <div class="stat">
                  <div class="stat-value"><?= (int)$org['Pending'] ?></div>
                  <div class="stat-label">Pending</div>
                </div>
              </div>
              <div class="department-actions">
                <a href="voters.php?org=<?= urlencode($org['Org_ID']) ?>" class="btn-view-department">
                  <i class="fas fa-arrow-right"></i> View Voters
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div style="padding:20px;">No organizations found.</div>
        <?php endif; ?>
      </div>

      <!-- Voter Modal -->
      <div id="voterModal" class="modal" style="display:none;">
        <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="voterModalTitle">
          <div class="modal-header">
            <h2 id="voterModalTitle">Add Voter</h2>
            <button class="close-modal" id="closeVoterModal" aria-label="Close">&times;</button>
          </div>
          <form method="POST" action="register-voters.php<?= $orgId ? '?org=' . $orgId : '' ?>" autocomplete="off">
            <input type="hidden" name="Org_ID" id="Org_ID" value="<?= $orgId ? (int)$orgId : '' ?>">
            <div class="form-group">
              <label class="form-label">Student ID</label>
              <input type="text" name="student_id" class="form-input" required>
            </div>
            <div class="form-group">
              <label class="form-label">Full Name</label>
              <input type="text" name="full_name" class="form-input" required>
            </div>
            <div class="form-group">
              <label class="form-label">Course</label>
              <select name="course" class="form-input" required>
                <option value="">Select Course</option>
                <option>Information Technology</option>
                <option>Energy System and Management</option>
                <option>Manufacturing Engineering Technology</option>
                <option>Naval Architecture and Marine Engineering</option>
                <option>Technology Communication Management</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Year Level</label>
              <select name="year_level" class="form-input" required>
                <option value="">Select Year Level</option>
                <option>1st Year</option>
                <option>2nd Year</option>
                <option>3rd Year</option>
                <option>4th Year</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-input" required>
            </div>

            <?php if (!empty($organizations)): ?>
            <fieldset style="margin-bottom:16px;">
              <legend>Organization Membership</legend>
              <?php foreach ($organizations as $orgItem): ?>
                <label style="display:block; margin-bottom:6px;">
                  <input type="checkbox" name="organizations[]" value="<?= (int)$orgItem['Org_ID'] ?>">
                  <?= esc($orgItem['Org_Name'] ?: $orgItem['Org_Code'] ?? "Org #{$orgItem['Org_ID']}") ?>
                </label>
              <?php endforeach; ?>
            </fieldset>
            <?php endif; ?>

            <div class="form-group">
              <label class="form-label">Status</label>
              <select name="status" class="form-input" required>
                <option value="">Select Status</option>
                <option>Active</option>
                <option>Pending</option>
                <option>Inactive</option>
              </select>
            </div>

            <div class="modal-actions" style="margin-top:10px;">
              <button type="button" class="btn btn-secondary" id="cancelVoterBtn">Cancel</button>
              <button type="submit" class="btn btn-primary">Save Voter</button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </div>

  <!-- Add Organization Modal -->
  <div class="modal" id="addOrgModal" aria-hidden="true" style="display:none;">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Add New Organization</h2>
        <button class="close-modal" id="closeOrgModal" aria-label="Close">&times;</button>
      </div>

      <!-- NOTE: enctype multipart/form-data for file upload -->
      <form id="orgForm" method="POST" action="../php/addOrganization.php" enctype="multipart/form-data" autocomplete="off">
        <div class="form-group">
          <label class="form-label" for="orgName">Organization Name</label>
          <input type="text" class="form-input" id="orgName" name="orgName" placeholder="e.g., Information Technology" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="orgDescription">Description</label>
          <input type="text" class="form-input" id="orgDescription" name="orgDescription" placeholder="e.g., Department of Information Technology">
        </div>

        <div class="form-group">
          <label class="form-label" for="orgEmail">Email</label>
          <input type="email" class="form-input" id="orgEmail" name="orgEmail" placeholder="e.g., info@organization.com" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="orgLogo">Logo (image file)</label>
          <input type="file" class="form-input" id="orgLogo" name="orgLogo" accept="image/*">
          <small>Max 2MB. JPG/PNG/GIF.</small>
        </div>

        <div class="modal-actions">
          <button type="button" class="btn btn-secondary" id="cancelOrgBtn">Cancel</button>
          <button type="submit" class="btn btn-primary">Add Organization</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Toast notification -->
  <div class="toast" id="toast" role="status" aria-live="polite"></div>

  <script>
  // -------------------------
  // JS: reuse the layout file's logic but seed initial data from PHP
  // -------------------------
  const departmentBoxes = document.getElementById('departmentBoxes');
  const searchInput = document.getElementById('searchInput');
  const addOrgBtn = document.getElementById('addOrgBtn');
  const addOrgModal = document.getElementById('addOrgModal');
  const closeOrgModal = document.getElementById('closeOrgModal');
  const cancelOrgBtn = document.getElementById('cancelOrgBtn');
  const toast = document.getElementById('toast');

  // departmentData seeded from server-side PHP
  let departmentData = <?php echo json_encode($orgs_with_counts, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?> || [];
  let currentSearch = '';

  function escapeHtml(str) {
    if (!str && str !== 0) return '';
    return String(str).replace(/[&<>"'\/]/g, s => {
      const map = { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;', '/':'&#x2F;' };
      return map[s];
    });
  }

  // Render department boxes (cards) with View Voters link (no view modal)
  function renderDepartmentBoxes() {
    departmentBoxes.innerHTML = '';

    const filtered = departmentData.filter(org =>
      (org.Org_Name || '').toLowerCase().includes(currentSearch.toLowerCase()) ||
      (org.Description || '').toLowerCase().includes(currentSearch.toLowerCase())
    );

    if (filtered.length === 0) {
      departmentBoxes.innerHTML = `<div class="empty-state" style="text-align:center; padding:40px;">
        <i class="fas fa-users" style="font-size:40px; color:#003366;"></i><h3>No organizations found</h3></div>`;
      return;
    }

    filtered.forEach(org => {
     const logo = org.Logo_Url && org.Logo_Url.trim() !== '' ? org.Logo_Url :
             '/eBotoni/eBotoni/assests/independent.jpg'; // or default you prefer

      const box = document.createElement('div');
      box.className = 'department-box';
      box.innerHTML = `
        <div class="department-header">
          <img src="${escapeHtml(logo)}" class="department-avatar" alt="${escapeHtml(org.Org_Name)}">
          <div class="department-info">
            <h3>${escapeHtml(org.Org_Name)}</h3>
            <p>${escapeHtml(org.Description || 'No description available')}</p>
          </div>
        </div>
        <div class="department-stats">
          <div class="stat"><div class="stat-value">${org.Total_Voters ?? 0}</div><div class="stat-label">Total Voters</div></div>
          <div class="stat"><div class="stat-value">${org.Pending ?? 0}</div><div class="stat-label">Pending</div></div>
        </div>
        <div class="department-actions">
          <a href="voters.php?org=${encodeURIComponent(org.Org_ID)}" class="btn-view-department">
            <i class="fas fa-arrow-right"></i> View Voters
          </a>
        </div>
      `;
      departmentBoxes.appendChild(box);
    });
  }

  // Toast helper
  function showToast(message, type = 'info') {
    toast.textContent = message;
    toast.className = `toast ${type} show`;
    if (type === 'success') { toast.style.background = '#e6ffed'; toast.style.color = '#0a8a3a'; }
    if (type === 'error') { toast.style.background = '#ffecec'; toast.style.color = '#b30000'; }
    setTimeout(() => {
      toast.classList.remove('show');
      toast.style.background = '';
      toast.style.color = '';
    }, 3000);
  }

  // Modal controls (Add Org)
  addOrgBtn.addEventListener('click', () => {
    addOrgModal.style.display = 'flex';
    addOrgModal.setAttribute('aria-hidden','false');
  });
  closeOrgModal.addEventListener('click', () => {
    addOrgModal.style.display = 'none';
    addOrgModal.setAttribute('aria-hidden','true');
  });
  cancelOrgBtn.addEventListener('click', () => {
    addOrgModal.style.display = 'none';
    addOrgModal.setAttribute('aria-hidden','true');
  });
  window.addEventListener('click', (e) => {
    if (e.target === addOrgModal) {
      addOrgModal.style.display = 'none';
      addOrgModal.setAttribute('aria-hidden','true');
    }
  });

  // Search handler
  searchInput.addEventListener('input', (e) => {
    currentSearch = e.target.value.trim();
    renderDepartmentBoxes();
  });

  // Form submit: Add Organization via AJAX (handles file upload)
  (function attachOrgFormHandler(){
    const orgForm = document.getElementById('orgForm');
    if (!orgForm) return;

    orgForm.addEventListener('submit', async function(e) {
      e.preventDefault();
      const submitBtn = orgForm.querySelector('button[type="submit"]');
      if (submitBtn) { submitBtn.disabled = true; submitBtn.dataset.origText = submitBtn.textContent; submitBtn.textContent = 'Adding...'; }

      try {
        const formData = new FormData(orgForm); // includes file input
        const resp = await fetch(orgForm.action || '../php/addOrganization.php', {
          method: 'POST',
          body: formData,
          cache: 'no-store'
        });

        if (!resp.ok) {
          let txt = await resp.text();
          try { const j = JSON.parse(txt); showToast(j.error || 'Server error', 'error'); }
          catch(err) { showToast(`Server error: ${resp.status}`, 'error'); }
          return;
        }

        const result = await resp.json();
        if (result.error) {
          showToast(result.error, 'error');
          return;
        }

        if (result.success && result.org) {
          const newOrg = {
            Org_ID: result.org.Org_ID,
            Org_Name: result.org.Org_Name,
            Description: result.org.Description || '',
            Logo_Url: result.org.Logo_Url || '../assets/default-logo.jpg',
            Email: result.org.Email || '',
            Total_Voters: result.org.Total_Voters ?? 0,
            Pending: result.org.Pending ?? 0
          };
          // insert to front and re-render
          departmentData.unshift(newOrg);
          renderDepartmentBoxes();
          showToast('Organization added!', 'success');
          addOrgModal.style.display = 'none';
          addOrgModal.setAttribute('aria-hidden','true');
          orgForm.reset();
          return;
        }

        // fallback: request full list endpoint if available
        try {
          const r2 = await fetch('../php/fetchOrganizations.php', { cache: 'no-store' });
          if (r2.ok) {
            const arr = await r2.json();
            if (Array.isArray(arr)) { departmentData = arr; renderDepartmentBoxes(); }
          }
        } catch (e) { /* ignore */ }

      } catch (err) {
        console.error('Add org failed', err);
        showToast('Failed to add organization', 'error');
      } finally {
        if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = submitBtn.dataset.origText || 'Add Organization'; }
      }
    });
  })();

  // Admin profile loader (kept small)
  const GET_ADMIN = '../php/getAdmin.php';
  function setText(node, text) { if (node) node.textContent = text; }

  async function loadAdminProfile() {
    try {
      const res = await fetch(GET_ADMIN, { cache: 'no-store' });
      if (!res.ok) return;
      const json = await res.json();
      if (!json) return;
      if (json.success) {
        const name = json.name || 'Administrator';
        const avatar = json.avatar || '../assets/default-avatar.jpg';
        setText(document.getElementById('sidebarName'), name);
        setText(document.getElementById('sidebarRole'), json.role || 'User');
        const av = document.getElementById('sidebarAvatar'); if (av) av.src = avatar;
      }
    } catch (err) {
      console.error('loadAdminProfile error', err);
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    renderDepartmentBoxes();
    loadAdminProfile();
  });
  </script>

</body>
</html>

<style>
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

    /* SIDEBAR - EXACTLY THE SAME AS DASHBOARD */
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

    /* NAVIGATION - EXACTLY THE SAME AS DASHBOARD */
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

    /* USER PROFILE - EXACTLY THE SAME AS DASHBOARD */
    .user-profile {
      padding: 20px;
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      display: flex;
      align-items: center;
      gap: 12px;
      position: relative;
      cursor: pointer;
      width: 100%;
    }

    .user-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      border: 2px solid var(--ustp-gold);
      object-fit: cover;
    }

    .user-info h4 {
      font-size: 0.95rem;
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
      background: var(--ustp-blue);
      border: 1px solid var(--ustp-gold);
      border-radius: 8px;
      overflow: hidden;
      width: 150px;
      z-index: 10;
    }

    .user-dropdown a {
      display: block;
      padding: 10px 20px;
      color: var(--ustp-white);
      text-decoration: none;
    }

    .user-dropdown a:hover {
      background: var(--ustp-gold);
      color: var(--ustp-blue);
    }

    /* MAIN CONTENT - EXACTLY THE SAME AS DASHBOARD */
    .main-content {
      flex: 1;
      margin-left: var(--sidebar-width);
      display: flex;
      flex-direction: column;
      transition: margin-left 0.3s ease;
    }

    .header {
      height: var(--header-height);
      background: var(--ustp-white);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 30px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      position: sticky;
      top: 0;
      z-index: 10;
    }

    .header h1 {
      color: var(--ustp-blue);
      font-size: 22px;
      font-weight: 700;
    }

    .menu-toggle {
      display: none;
      font-size: 22px;
      color: var(--ustp-blue);
      cursor: pointer;
    }

    .content {
      padding: 30px;
      flex: 1;
    }

    /* Voter Management Specific Styles */
    .voter-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
    }

    .voter-title {
      color: var(--ustp-blue);
      font-size: 24px;
    }

    .search-box {
      display: flex;
      gap: 10px;
    }

    .search-input {
      padding: 10px 15px;
      border: 1px solid #ddd;
      border-radius: 6px;
      width: 300px;
      font-size: 14px;
    }

    .search-btn {
      background: var(--ustp-blue);
      color: white;
      border: none;
      border-radius: 6px;
      padding: 0 20px;
      cursor: pointer;
      transition: 0.3s;
    }

    .search-btn:hover {
      background: var(--ustp-light-blue);
    }

    .add-org-btn {
      background: var(--ustp-gold);
      color: var(--ustp-blue);
      border: none;
      border-radius: 6px;
      padding: 10px 20px;
      cursor: pointer;
      font-weight: 600;
      transition: 0.3s;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .add-org-btn:hover {
      background: #e6b800;
    }

    /* Department Boxes */
    .department-boxes {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 25px;
      margin-bottom: 30px;
    }

    .department-box {
      background: var(--ustp-white);
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      overflow: hidden;
      transition: all 0.3s ease;
      cursor: pointer;
      border: 2px solid transparent;
    }

    .department-box:hover {
      transform: translateY(-8px);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
      border-color: var(--ustp-gold);
    }

    .department-header {
      padding: 20px;
      background: var(--ustp-blue);
      color: white;
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .department-avatar {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      border: 2px solid var(--ustp-gold);
      object-fit: cover;
      background-color: var(--ustp-white);
    }

    .department-info h3 {
      font-size: 18px;
      margin-bottom: 5px;
    }

    .department-info p {
      font-size: 14px;
      opacity: 0.9;
    }

    .department-stats {
      padding: 15px 20px;
      display: flex;
      justify-content: space-between;
      background: rgba(0, 102, 204, 0.05);
    }

    .stat {
      text-align: center;
    }

    .stat-value {
      font-size: 20px;
      font-weight: 700;
      color: var(--ustp-blue);
    }

    .stat-label {
      font-size: 12px;
      color: #666;
    }

    .department-actions {
      padding: 15px 20px;
      border-top: 1px solid #eee;
      text-align: center;
    }

    .btn-view-department {
      background: var(--ustp-blue);
      color: white;
      border: none;
      border-radius: 6px;
      padding: 10px 20px;
      cursor: pointer;
      font-weight: 600;
      transition: 0.3s;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      text-decoration: none;
    }

    .btn-view-department:hover {
      background: var(--ustp-light-blue);
    }

    /* Modal */
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 1000;
      justify-content: center;
      align-items: center;
    }

    .modal-content {
      background-color: white;
      padding: 30px;
      border-radius: 10px;
      width: 90%;
      max-width: 500px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 1px solid #eee;
    }

    .modal-header h2 {
      color: var(--ustp-blue);
      margin: 0;
    }

    .close-modal {
      background: none;
      border: none;
      font-size: 24px;
      cursor: pointer;
      color: var(--ustp-dark-gray);
    }

    .modal-actions {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-top: 20px;
    }

    .btn {
      padding: 10px 20px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
      transition: 0.3s;
    }

    .btn-primary {
      background: var(--ustp-blue);
      color: white;
    }

    .btn-primary:hover {
      background: var(--ustp-light-blue);
    }

    .btn-secondary {
      background: #6c757d;
      color: white;
    }

    .btn-secondary:hover {
      background: #5a6268;
    }

    /* Form Styles */
    .form-group {
      margin-bottom: 20px;
    }

    .form-label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: var(--ustp-blue);
    }

    .form-input {
      width: 100%;
      padding: 10px 15px;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 14px;
    }

    .form-input:focus {
      outline: none;
      border-color: var(--ustp-blue);
    }

    /* Toast notification */
    .toast {
      position: fixed;
      bottom: 20px;
      right: 20px;
      padding: 15px 25px;
      background-color: var(--ustp-blue);
      color: white;
      border-radius: 5px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
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
      background-color: #4caf50;
    }

    .toast.error {
      background-color: #f44336;
    }

    /* Responsive - EXACTLY THE SAME AS DASHBOARD */
    @media (max-width: 768px) {
      .sidebar { width: 70px; }
      .logo-container h2, .nav-item a { display: none; }
      .main-content { margin-left: 70px; }
      .menu-toggle { display: block; }
      .voter-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
      }
      .search-box {
        width: 100%;
      }
      .search-input {
        width: 100%;
      }
      .department-boxes {
        grid-template-columns: 1fr;
      }
    }
  </style>
</body>
</html>
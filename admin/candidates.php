<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>eBOTO | Candidates (Admin)</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; }
    body { display: flex; min-height: 100vh; background-color: var(--ustp-light-gray); color: var(--ustp-dark-gray); }

    /* SIDEBAR */
    .sidebar { width: var(--sidebar-width); background: linear-gradient(180deg, var(--ustp-blue) 0%, var(--ustp-light-blue) 100%); color: var(--ustp-white); padding: 20px 0; display: flex; flex-direction: column; align-items: center; position: fixed; top: 0; left: 0; height: 100vh; box-shadow: 2px 0 10px rgba(0,0,0,0.1); z-index: 100; transition: 0.3s ease; }
    .logo-container { text-align: center; width: 100%; padding: 0 20px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 20px; }
    .logo { width: 130px; height: 130px; border-radius: 50%; overflow: hidden; margin: 0 auto 10px auto; border: 4px solid var(--ustp-gold); background-color: var(--ustp-white); }
    .logo img { width: 100%; height: 100%; object-fit: cover; }
    .nav-menu { flex: 1; list-style: none; padding: 0; width: 100%; }
    .nav-item { padding: 14px 25px; display: flex; align-items: center; gap: 12px; cursor: pointer; transition: all 0.3s ease; font-size: 15px; }
    .nav-item:hover { background-color: #004b99; }
    .nav-item.active { background-color: var(--ustp-gold); color: var(--ustp-blue); font-weight: bold; border-left: 4px solid var(--ustp-gold); }
    .nav-item i { width: 20px; text-align: center; }
    .nav-item a { text-decoration: none; color: inherit; display: block; width: 100%; }

    .user-profile { padding: 20px; border-top: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; gap: 12px; position: relative; cursor: pointer; width: 100%; }
    .user-avatar { width: 40px; height: 40px; border-radius: 50%; border: 2px solid var(--ustp-gold); object-fit: cover; }
    .user-info h4 { font-size: 0.95rem; margin-bottom: 2px; }
    .user-info p { font-size: 0.8rem; opacity: 0.8; }
    .user-dropdown { display: none; position: absolute; bottom: 70px; left: 20px; background: var(--ustp-blue); border: 1px solid var(--ustp-gold); border-radius: 8px; overflow: hidden; width: 150px; z-index: 10; }
    .user-dropdown a { display: block; padding: 10px 20px; color: var(--ustp-white); text-decoration: none; }
    .user-dropdown a:hover { background: var(--ustp-gold); color: var(--ustp-blue); }

    /* MAIN */
    .main-content { flex: 1; margin-left: var(--sidebar-width); display: flex; flex-direction: column; transition: margin-left 0.3s ease; min-width: 0; }
    .header { height: var(--header-height); background: var(--ustp-white); display: flex; align-items: center; justify-content: space-between; padding: 0 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 10; }
    .header h1 { color: var(--ustp-blue); font-size: 22px; font-weight: 700; }
    .header-right { display: flex; align-items: center; gap: 18px; }
    .menu-toggle { display: none; font-size: 22px; color: var(--ustp-blue); cursor: pointer; }
    .content { padding: 30px; flex: 1; }

    /* LIVE STATUS */
    .live-status { display: inline-flex; align-items: center; gap: 8px; font-size: 13px; color: #555; background: var(--ustp-light-gray); padding: 6px 12px; border-radius: 20px; }
    .live-dot { width: 9px; height: 9px; border-radius: 50%; background: #9e9e9e; }
    .live-status.live .live-dot { background: #2e7d32; animation: pulse 2s infinite; }
    .live-status.error .live-dot { background: #c62828; }
    .live-status.paused .live-dot { background: #f9a825; }
    @keyframes pulse {
      0%   { box-shadow: 0 0 0 0 rgba(46,125,50,0.5); }
      70%  { box-shadow: 0 0 0 7px rgba(46,125,50,0); }
      100% { box-shadow: 0 0 0 0 rgba(46,125,50,0); }
    }

    .candidate-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
    .candidate-title { color: var(--ustp-blue); font-size: 24px; }
    .search-box { display: flex; gap: 10px; }
    .search-input { padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; width: 300px; font-size: 14px; }
    .search-btn { background: var(--ustp-blue); color: white; border: none; border-radius: 6px; padding: 0 20px; cursor: pointer; transition: 0.3s; }
    .search-btn:hover { background: var(--ustp-light-blue); }
    .add-org-btn { background: var(--ustp-gold); color: var(--ustp-blue); border: none; border-radius: 6px; padding: 10px 20px; cursor: pointer; font-weight: 600; transition: 0.3s; display: flex; align-items: center; gap: 8px; }
    .add-org-btn:hover { background: #e6b800; }

    /* Department boxes */
    .department-boxes { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 25px; margin-bottom: 30px; }
    .department-box { background: var(--ustp-white); border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); overflow: hidden; transition: all 0.3s ease; cursor: pointer; border: 2px solid transparent; }
    .department-box:hover { transform: translateY(-8px); box-shadow: 0 8px 20px rgba(0,0,0,0.15); border-color: var(--ustp-gold); }
    .department-header { padding: 20px; background: var(--ustp-blue); color: white; display: flex; align-items: center; gap: 15px; }
    .department-avatar { width: 50px; height: 50px; border-radius: 50%; border: 2px solid var(--ustp-gold); object-fit: cover; background-color: var(--ustp-white); }
    .department-info h3 { font-size: 18px; margin-bottom: 5px; }
    .department-info p { font-size: 14px; opacity: 0.9; }
    .department-stats { padding: 15px 20px; display: flex; justify-content: space-between; background: rgba(0,102,204,0.05); }
    .stat { text-align: center; }
    .stat-value { font-size: 20px; font-weight: 700; color: var(--ustp-blue); }
    .stat-label { font-size: 12px; color: #666; }
    .department-actions { padding: 15px 20px; border-top: 1px solid #eee; text-align: center; }
    .btn-view-department { background: var(--ustp-blue); color: white; border: none; border-radius: 6px; padding: 10px 20px; cursor: pointer; font-weight: 600; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
    .btn-view-department:hover { background: var(--ustp-light-blue); }

    /* Modal */
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
    .modal-content { background-color: white; padding: 30px; border-radius: 10px; width: 90%; max-width: 500px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); }
    .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #eee; }
    .modal-header h2 { color: var(--ustp-blue); margin: 0; }
    .close-modal { background: none; border: none; font-size: 24px; cursor: pointer; color: var(--ustp-dark-gray); }
    .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
    .modal-actions button { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; }
    .modal-actions button[type="submit"] { background: var(--ustp-blue); color: white; }
    .modal-actions button[type="button"] { background: #6c757d; color: white; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--ustp-blue); }
    .form-group input { width: 100%; padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }

    .toast { position: fixed; bottom: 20px; right: 20px; padding: 15px 25px; background-color: var(--ustp-blue); color: white; border-radius: 5px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 1001; }

    @media (max-width: 768px) {
      .sidebar { width: 70px; }
      .nav-item a, .user-info { display: none; }
      .main-content { margin-left: 70px; }
      .menu-toggle { display: block; }
      .candidate-header { flex-direction: column; align-items: flex-start; gap: 15px; }
      .search-box { width: 100%; }
      .search-input { width: 100%; }
      .department-boxes { grid-template-columns: 1fr; }
      .live-status .live-text { display: none; }
    }
  </style>
</head>
<body>

  <!-- SIDEBAR -->
  <nav class="sidebar" id="sidebar">
    <div class="logo-container">
      <div class="logo"><img src="../assests/suffage.png" alt="Logo"></div>
    </div>

    <ul class="nav-menu">
      <li class="nav-item"><i class="fas fa-home"></i><a href="admin_dashboard.html">Dashboard</a></li>
      <li class="nav-item"><i class="fas fa-user-graduate"></i><a href="register-voters.php">Voters</a></li>
      <li class="nav-item"><i class="fas fa-user-tie"></i><a href="register-officers.php">Officers</a></li>
      <li class="nav-item active"><i class="fas fa-user-tag"></i><a href="candidates.php">Candidates</a></li>
      <li class="nav-item"><i class="fas fa-check-circle"></i><a href="validate-elections.php">Elections</a></li>
      <li class="nav-item "><i class="fas fa-chart-line"></i><a href="monitoring.php">Monitoring</a></li>
      <li class="nav-item"><i class="fas fa-file-alt"></i><a href="audit-reports.php">Reports</a></li>
      <li class="nav-item"><i class="fas fa-cog"></i><a href="settings.php">Settings</a></li>
    </ul>

    <div class="user-profile" id="userProfile">
      <img id="sidebarAvatar" src="../assests/default-avatar.jpg" class="user-avatar" alt="avatar" />
      <div class="user-info">
        <h4 id="sidebarName">Administrator</h4>
        <p id="sidebarRole">Administration</p>
      </div>
      <div class="user-dropdown" id="dropdown">
        <a href="../login.html" id="logoutBtn">Logout</a>
      </div>
    </div>
  </nav>

  <!-- MAIN CONTENT -->
  <div class="main-content" id="mainContent">
    <div class="header">
      <h1>Candidate Management</h1>
      <div class="header-right">
        <span class="live-status" id="liveStatus" title="Counts refresh automatically">
          <span class="live-dot"></span>
          <span class="live-text" id="liveText">Connecting…</span>
        </span>
        <i class="fas fa-bars menu-toggle" id="menuToggle"></i>
      </div>
    </div>

    <div class="content">
      <div class="candidate-header">
        <h2 class="candidate-title">Manage Candidates by Organization</h2>
        <div class="search-box">
          <input type="text" class="search-input" placeholder="Search organizations..." id="searchInput">
          <button class="search-btn"><i class="fas fa-search"></i></button>
          <button class="add-org-btn" id="addOrgBtn"><i class="fas fa-plus"></i> Add Organization</button>
        </div>
      </div>

      <div class="department-boxes" id="departmentBoxes"></div>
    </div>
  </div>

  <!-- Add Organization Modal -->
  <div class="modal" id="addOrgModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Add New Organization</h2>
        <button class="close-modal" id="closeOrgModal">&times;</button>
      </div>
      <form id="orgForm">
        <div class="form-group"><label for="orgCode">Organization Code</label><input type="text" id="orgCode" required></div>
        <div class="form-group"><label for="orgName">Organization Name</label><input type="text" id="orgName" required></div>
        <div class="form-group"><label for="orgDescription">Description</label><input type="text" id="orgDescription" required></div>
        <div class="form-group"><label for="orgImage">Logo URL</label><input type="text" id="orgImage" required></div>
        <div class="modal-actions">
          <button type="button" id="cancelOrgBtn">Cancel</button>
          <button type="submit">Add Organization</button>
        </div>
      </form>
    </div>
  </div>

  <div class="toast" id="toast" style="display:none;"></div>

<script>
/* ========== CONFIG ========== */
const GET_ADMIN = '../php/getAdmin.php';
const GET_CANDIDATES = '../student org/get_candidates.php';
const POLL_INTERVAL_MS = 10000;   // how often counts refresh (10 seconds)

/* filter: 'department' = by program (IT, ESM, MET, TCM, NAME)
           'org'        = by organization (CACHE, USG, new orgs)
   candidates / pending start as undefined and are filled with REAL counts from the database */
let departmentData = [
  { id:"it",    code:"IT",    name:"Information Technology",                 description:"Department of Information Technology",                 image:"soce.jpg", filter:"department", link:"candidatesIT.php" },
  { id:"esm",   code:"ESM",   name:"Energy Solution Management",             description:"Department of Energy Solution Management",             image:"esm.jpg",  filter:"department", link:"candidatesESM.php" },
  { id:"met",   code:"MET",   name:"Manufacturing Engineering Technology",   description:"Department of Manufacturing Engineering Technology",   image:"met.jpg",  filter:"department", link:"candidatesMET.php" },
  { id:"tcm",   code:"TCM",   name:"Technology Communication Management",    description:"Department of Technology Communication Management",    image:"tcm.jpg",  filter:"department", link:"candidatesTCM.php" },
  { id:"name",  code:"NAME",  name:"Naval Architecture & Marine Engineering",description:"Department of Naval Architecture & Marine Engineering",image:"name.jpg", filter:"department", link:"candidatesNAME.php" },
  { id:"cache", code:"CACHE", name:"CACHE Organization",                     description:"Student Publication Organization",                     image:"tc.jpg",   filter:"org",        link:"candidatesCACHE.php" },
  { id:"usg",   code:"USG",   name:"University Student Government",          description:"University-wide Student Government",                   image:"usg.jpg",  filter:"org",        link:"candidatesUSG.php" }
];

/* ========== DOM refs ========== */
const sidebarAvatar = document.getElementById('sidebarAvatar');
const sidebarName = document.getElementById('sidebarName');
const sidebarRole = document.getElementById('sidebarRole');
const userProfile = document.getElementById('userProfile');
const dropdown = document.getElementById('dropdown');
const menuToggle = document.getElementById('menuToggle');
const sidebar = document.getElementById('sidebar');
const mainContent = document.getElementById('mainContent');
const logoutBtn = document.getElementById('logoutBtn');

const searchInput = document.getElementById('searchInput');
const departmentBoxes = document.getElementById('departmentBoxes');
const addOrgBtn = document.getElementById('addOrgBtn');
const addOrgModal = document.getElementById('addOrgModal');
const closeOrgModal = document.getElementById('closeOrgModal');
const cancelOrgBtn = document.getElementById('cancelOrgBtn');
const orgForm = document.getElementById('orgForm');
const toast = document.getElementById('toast');
const liveStatus = document.getElementById('liveStatus');
const liveText = document.getElementById('liveText');

/* ========== UTIL ========== */
function showToast(msg, t = 3000) {
  toast.textContent = msg; toast.style.display = 'block';
  clearTimeout(toast._t); toast._t = setTimeout(() => toast.style.display = 'none', t);
}
function escapeHtml(s) {
  if (s === undefined || s === null) return '';
  return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'})[m]);
}
function normalizeAvatarPath(raw) {
  if (!raw) return '../assests/default-avatar.jpg';
  let s = String(raw).trim().replace(/\\+/g, '/');
  if (!s) return '../assests/default-avatar.jpg';
  if (/^https?:\/\//i.test(s) || s.startsWith('/') || s.startsWith('../')) return s;
  return '../' + s.replace(/^\/+/, '');
}
/* undefined = still loading, null = failed to load */
function fmtCount(n) { return n === undefined ? '…' : (n === null ? '—' : n); }

/* ========== ADMIN PROFILE ========== */
async function loadAdminProfile() {
  try {
    const res = await fetch(GET_ADMIN, { cache: 'no-store' });
    if (!res.ok) { console.warn('GET_ADMIN failed', res.status); return; }
    const json = await res.json();
    if (!json || !json.success) { console.warn('getAdmin returned success:false', json); return; }
    const isAdmin = json.role && /admin/i.test(String(json.role));
    sidebarName.textContent = json.name || 'Administrator';
    sidebarRole.textContent = isAdmin ? 'Administration' : (json.role || 'User');
    sidebarAvatar.src = normalizeAvatarPath(json.avatar || json.avatar_path);
  } catch (err) {
    console.error('loadAdminProfile error', err);
  }
}

/* ========== LIVE STATUS INDICATOR ========== */
function setLiveStatus(state, text) {
  liveStatus.className = 'live-status ' + state;   // live | error | paused
  liveText.textContent = text;
}
function timeNow() {
  return new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}

/* ========== REAL-TIME COUNTS (polling) ========== */
let pollTimer = null;
let isLoading = false;        // prevents overlapping requests if the server is slow
let lastSignature = '';       // lets us re-render ONLY when numbers actually change

function countsSignature() {
  return JSON.stringify(departmentData.map(d => [d.id, d.candidates, d.pending]));
}

/* returns true if the request succeeded */
async function loadCountsFor(dept) {
  try {
    const url = `${GET_CANDIDATES}?${dept.filter}=${encodeURIComponent(dept.code)}`;
    const res = await fetch(url, { cache: 'no-store' });
    if (!res.ok) throw new Error('HTTP ' + res.status);
    const json = await res.json();
    const rows = Array.isArray(json) ? json : (json && Array.isArray(json.data) ? json.data : null);
    if (!rows) throw new Error('Unexpected response');
    dept.candidates = rows.length;
    dept.pending = rows.filter(r => String(r.Status || r.status || '').trim().toLowerCase() === 'pending').length;
    return true;
  } catch (err) {
    console.error('Count failed for', dept.code, err);
    // Keep the last good numbers on a temporary failure; only show "—" if we never had any
    if (dept.candidates === undefined) { dept.candidates = null; dept.pending = null; }
    return false;
  }
}

async function loadAllCounts() {
  if (isLoading) return;
  isLoading = true;
  try {
    const results = await Promise.all(departmentData.map(loadCountsFor));
    const allOk = results.every(Boolean);

    if (countsSignature() !== lastSignature) renderDepartmentBoxes();   // no flicker if nothing changed

    if (allOk) setLiveStatus('live', 'Live · updated ' + timeNow());
    else setLiveStatus('error', 'Connection issue · retrying…');
  } finally {
    isLoading = false;
  }
}

function startPolling() {
  stopPolling();
  pollTimer = setInterval(loadAllCounts, POLL_INTERVAL_MS);
}
function stopPolling() {
  if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
}

/* ========== RENDER DEPARTMENTS ========== */
function renderDepartmentBoxes() {
  lastSignature = countsSignature();
  departmentBoxes.innerHTML = '';
  const term = (searchInput.value || '').trim().toLowerCase();
  const filtered = term
    ? departmentData.filter(d => (d.code + ' ' + d.name + ' ' + d.description).toLowerCase().includes(term))
    : departmentData;

  if (!filtered.length) {
    departmentBoxes.innerHTML = `<div style="padding:40px;text-align:center"><i class="fas fa-user-tag"></i><h3>No organizations found</h3></div>`;
    return;
  }

  filtered.forEach(dept => {
    const box = document.createElement('div');
    box.className = 'department-box';
    box.innerHTML = `
      <div class="department-header">
        <img src="${escapeHtml(dept.image)}" class="department-avatar" alt="${escapeHtml(dept.name)} Logo" width="56" height="56">
        <div class="department-info">
          <h3>${escapeHtml(dept.code)}</h3>
          <p>${escapeHtml(dept.name)}</p>
        </div>
      </div>
      <div class="department-stats">
        <div class="stat"><div class="stat-value">${fmtCount(dept.candidates)}</div><div class="stat-label">Total Candidates</div></div>
        <div class="stat"><div class="stat-value">${fmtCount(dept.pending)}</div><div class="stat-label">Pending</div></div>
      </div>
      <div class="department-actions"><a href="${escapeHtml(dept.link)}" class="btn-view-department"><i class="fas fa-arrow-right"></i> View Candidates</a></div>
    `;
    departmentBoxes.appendChild(box);
  });
}

/* ========== ADD ORGANIZATION ========== */
async function addOrganization(orgData) {
  const id = orgData.code.toLowerCase();
  const link = `candidates${orgData.code.toUpperCase()}.php`;
  const dept = { id, code: orgData.code, name: orgData.name, description: orgData.description, image: orgData.image, filter: 'org', link };
  departmentData.push(dept);
  renderDepartmentBoxes();
  showToast(`Organization "${orgData.name}" added`);
  await loadCountsFor(dept);
  renderDepartmentBoxes();
}

/* ========== EVENTS ========== */
function setupEvents() {
  userProfile.addEventListener('click', () => dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block');
  document.addEventListener('click', e => { if (!userProfile.contains(e.target)) dropdown.style.display = 'none'; });

  menuToggle.addEventListener('click', () => {
    if (sidebar.style.left === '0px' || sidebar.style.left === '') { sidebar.style.left = '-250px'; mainContent.style.marginLeft = '0'; }
    else { sidebar.style.left = '0px'; mainContent.style.marginLeft = '250px'; }
  });

  logoutBtn.addEventListener('click', ev => { ev.preventDefault(); showToast('Logging out...'); setTimeout(() => location.href = '../login.html', 700); });

  searchInput.addEventListener('input', renderDepartmentBoxes);

  addOrgBtn.addEventListener('click', () => addOrgModal.style.display = 'flex');
  closeOrgModal.addEventListener('click', () => { addOrgModal.style.display = 'none'; orgForm.reset(); });
  cancelOrgBtn.addEventListener('click', () => { addOrgModal.style.display = 'none'; orgForm.reset(); });
  window.addEventListener('click', e => { if (e.target === addOrgModal) { addOrgModal.style.display = 'none'; orgForm.reset(); } });

  orgForm.addEventListener('submit', e => {
    e.preventDefault();
    const orgData = {
      code: document.getElementById('orgCode').value.trim(),
      name: document.getElementById('orgName').value.trim(),
      description: document.getElementById('orgDescription').value.trim(),
      image: document.getElementById('orgImage').value.trim()
    };
    if (!orgData.code || !orgData.name) { showToast('Code and Name are required', 2000); return; }
    addOrganization(orgData);
    addOrgModal.style.display = 'none'; orgForm.reset();
  });

  /* Pause polling while the tab is hidden; refresh immediately when it comes back */
  document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
      stopPolling();
      setLiveStatus('paused', 'Paused (tab inactive)');
    } else {
      loadAllCounts();
      startPolling();
    }
  });

  /* refresh counts when coming back with the browser's Back button */
  window.addEventListener('pageshow', e => { if (e.persisted) { loadAllCounts(); startPolling(); } });

  /* re-sync as soon as the connection returns */
  window.addEventListener('online', loadAllCounts);
  window.addEventListener('offline', () => setLiveStatus('error', 'Offline'));
}

/* ========== INIT ========== */
document.addEventListener('DOMContentLoaded', () => {
  renderDepartmentBoxes();   // shows "…" while counts load
  setupEvents();
  loadAdminProfile();
  loadAllCounts();           // first fetch right away
  startPolling();            // then keep refreshing automatically
});
</script>

</body>
</html>

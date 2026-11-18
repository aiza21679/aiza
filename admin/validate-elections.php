<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>eBOTO | Election Management</title>
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
    .menu-toggle { display: none; font-size: 22px; color: var(--ustp-blue); cursor: pointer; }
    .content { padding: 30px; flex: 1; }

    .election-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
    .election-title { color: var(--ustp-blue); font-size: 24px; }
    .search-box { display: flex; gap: 10px; }
    .search-input { padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; width: 300px; font-size: 14px; }
    .search-btn { background: var(--ustp-blue); color: white; border: none; border-radius: 6px; padding: 0 20px; cursor: pointer; transition: 0.3s; }
    .search-btn:hover { background: var(--ustp-light-blue); }
    .add-election-btn { background: var(--ustp-gold); color: var(--ustp-blue); border: none; border-radius: 6px; padding: 10px 20px; cursor: pointer; font-weight: 600; transition: 0.3s; display: flex; align-items: center; gap: 8px; }
    .add-election-btn:hover { background: #e6b800; }

    /* Election boxes */
    .election-boxes { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 25px; margin-bottom: 30px; }
    .election-box { background: var(--ustp-white); border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); overflow: hidden; transition: all 0.3s ease; border: 2px solid transparent; }
    .election-box:hover { transform: translateY(-8px); box-shadow: 0 8px 20px rgba(0,0,0,0.15); }
    .election-header-box { padding: 20px; color: white; display: flex; align-items: center; gap: 15px; }
    .election-avatar { width: 50px; height: 50px; border-radius: 50%; border: 2px solid var(--ustp-gold); background-color: var(--ustp-white); display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
    .election-info h3 { font-size: 18px; margin-bottom: 5px; }
    .election-info p { font-size: 14px; opacity: 0.9; }
    .election-stats { padding: 15px 20px; display: flex; justify-content: space-between; background: rgba(0,102,204,0.05); }
    .stat { text-align: center; }
    .stat-value { font-size: 20px; font-weight: 700; color: var(--ustp-blue); }
    .stat-label { font-size: 12px; color: #666; }
    .election-details { padding: 15px 20px; border-top: 1px solid #eee; }
    .election-detail { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; gap: 10px; }
    .election-detail-label { font-weight: 600; color: var(--ustp-blue); }
    /* Manage button removed -> Edit / Delete are aligned to the right */
    .election-actions { padding: 15px 20px; border-top: 1px solid #eee; display: flex; justify-content: flex-end; align-items: center; gap: 8px; flex-wrap: wrap; }
    .btn-action { background: transparent; color: var(--ustp-blue); border: 1px solid var(--ustp-blue); border-radius: 6px; padding: 8px 12px; cursor: pointer; font-weight: 600; transition: 0.3s; display: inline-flex; align-items: center; gap: 5px; font-size: 14px; }
    .btn-action:hover { background: var(--ustp-blue); color: white; }
    .btn-action.danger { color: #dc3545; border-color: #dc3545; }
    .btn-action.danger:hover { background: #dc3545; color: white; }

    .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .status-active { background: #d4edda; color: #155724; }
    .status-upcoming { background: #fff3cd; color: #856404; }
    .status-completed { background: #d1ecf1; color: #0c5460; }
    .status-draft { background: #e2e3e5; color: #383d41; }

    .empty-state { grid-column: 1 / -1; text-align: center; padding: 50px; background: white; border-radius: 12px; color: #777; }
    .empty-state i { font-size: 48px; color: #ccc; margin-bottom: 15px; }

    /* Modal */
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
    .modal-content { background-color: white; padding: 30px; border-radius: 10px; width: 90%; max-width: 600px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); max-height: 90vh; overflow-y: auto; }
    .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #eee; }
    .modal-header h2 { color: var(--ustp-blue); margin: 0; }
    .close-modal { background: none; border: none; font-size: 24px; cursor: pointer; color: var(--ustp-dark-gray); }
    .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
    .btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; transition: 0.3s; }
    .btn-primary { background: var(--ustp-blue); color: white; }
    .btn-primary:hover { background: var(--ustp-light-blue); }
    .btn-secondary { background: #6c757d; color: white; }
    .btn-secondary:hover { background: #5a6268; }
    .btn-danger { background: #dc3545; color: white; }
    .btn-danger:hover { background: #c82333; }

    .form-group { margin-bottom: 20px; }
    .form-label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--ustp-blue); }
    .form-input, .form-select { width: 100%; padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
    .form-select { background-color: white; }
    .form-input:focus { outline: none; border-color: var(--ustp-blue); }
    .form-row { display: flex; gap: 15px; }
    .form-row .form-group { flex: 1; }
    .form-hint { font-size: 12px; color: #777; margin-top: 5px; }

    .toast { position: fixed; bottom: 20px; right: 20px; padding: 15px 25px; background-color: var(--ustp-blue); color: white; border-radius: 5px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 1001; transform: translateY(100px); opacity: 0; transition: transform 0.3s, opacity 0.3s; pointer-events: none; }
    .toast.show { transform: translateY(0); opacity: 1; }
    .toast.success { background-color: #4caf50; }
    .toast.error { background-color: #f44336; }

    @media (max-width: 768px) {
      .sidebar { width: 70px; }
      .nav-item a, .user-info { display: none; }
      .main-content { margin-left: 70px; }
      .menu-toggle { display: block; }
      .election-header { flex-direction: column; align-items: flex-start; gap: 15px; }
      .search-box { width: 100%; }
      .search-input { width: 100%; }
      .election-boxes { grid-template-columns: 1fr; }
      .form-row { flex-direction: column; gap: 0; }
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
      <li class="nav-item"><i class="fas fa-user-tag"></i><a href="candidates.php">Candidates</a></li>
      <li class="nav-item active"><i class="fas fa-check-circle"></i><a href="validate-elections.php">Elections</a></li>
      <li class="nav-item"><i class="fas fa-chart-line"></i><a href="monitoring.php">Monitoring</a></li>
      <li class="nav-item"><i class="fas fa-file-alt"></i><a href="audit-reports.php">Reports</a></li>
      <li class="nav-item"><i class="fas fa-cog"></i><a href="settings.php">Settings</a></li>
    </ul>

    <div class="user-profile" id="userProfile">
      <img id="adminAvatar" src="../assests/default-avatar.jpg" class="user-avatar" alt="Admin Avatar">
      <div class="user-info">
        <h4 id="adminName">Administrator</h4>
        <p id="adminRole">Administration</p>
      </div>
      <div class="user-dropdown" id="dropdown">
        <a href="../login.html" id="logoutBtn">Logout</a>
      </div>
    </div>
  </nav>

  <!-- MAIN CONTENT -->
  <div class="main-content" id="mainContent">
    <div class="header">
      <h1>Election Management</h1>
      <i class="fas fa-bars menu-toggle" id="menuToggle"></i>
    </div>

    <div class="content">
      <div class="election-header">
        <h2 class="election-title">Manage Elections</h2>
        <div class="search-box">
          <input type="text" class="search-input" placeholder="Search elections..." id="searchInput">
          <button class="search-btn" type="button"><i class="fas fa-search"></i></button>
          <button class="add-election-btn" id="addElectionBtn" type="button"><i class="fas fa-plus"></i> Create Election</button>
        </div>
      </div>

      <div class="election-boxes" id="electionBoxes"></div>
    </div>
  </div>

  <!-- Create / Edit Election Modal -->
  <div class="modal" id="createElectionModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 id="electionModalTitle">Create New Election</h2>
        <button class="close-modal" id="closeElectionModal" type="button">&times;</button>
      </div>
      <form id="electionForm">
        <div class="form-group">
          <label class="form-label" for="electionTitle">Election Title</label>
          <input type="text" class="form-input" id="electionTitle" placeholder="e.g., IT Officers Election 2026" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="electionOrganization">Organization</label>
          <select class="form-select" id="electionOrganization" required>
            <option value="">Select Organization</option>
          </select>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="startDate">Start Date</label>
            <input type="datetime-local" class="form-input" id="startDate" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="endDate">End Date</label>
            <input type="datetime-local" class="form-input" id="endDate" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label" for="electionDescription">Description</label>
          <textarea class="form-input" id="electionDescription" rows="3" placeholder="Describe the purpose and scope of this election"></textarea>
        </div>
        <div class="form-group">
          <label class="form-label" for="electionStatus">Status</label>
          <select class="form-select" id="electionStatus" required>
            <option value="draft">Draft (not yet published)</option>
            <option value="published">Published (Upcoming / Active / Completed is set automatically by the dates)</option>
          </select>
        </div>
        <div class="modal-actions">
          <button type="button" class="btn btn-secondary" id="cancelElectionBtn">Cancel</button>
          <button type="submit" class="btn btn-primary" id="electionSubmitBtn">Create Election</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Confirmation Modal -->
  <div class="modal" id="actionModal">
    <div class="modal-content" style="max-width:420px;">
      <div class="modal-header">
        <h2 id="actionModalTitle">Confirm Action</h2>
        <button class="close-modal" id="closeActionModal" type="button">&times;</button>
      </div>
      <div id="actionModalContent"></div>
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" id="cancelActionBtn">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmActionBtn">Confirm</button>
      </div>
    </div>
  </div>

  <div class="toast" id="toast"></div>

<script>
/* =====================
   CONFIG
   ===================== */
const GET_ADMIN = '../php/getAdmin.php';
const GET_CANDIDATES = '../student org/get_candidates.php';
const STORAGE_KEY = 'eboto_elections_v1';

/* filter: 'department' = by program, 'org' = by organization (same as candidates.php) */
const ORGS = {
  IT:    { name: 'Information Technology',                  filter: 'department', color: '#003366' },
  ESM:   { name: 'Energy Solution Management',              filter: 'department', color: '#f44336' },
  MET:   { name: 'Manufacturing Engineering Technology',    filter: 'department', color: '#9c27b0' },
  TCM:   { name: 'Technology Communication Management',     filter: 'department', color: '#ff9800' },
  NAME:  { name: 'Naval Architecture & Marine Engineering', filter: 'department', color: '#4caf50' },
  CACHE: { name: 'The CACHE Organization',                  filter: 'org',        color: '#795548' },
  USG:   { name: 'University Student Government',           filter: 'org',        color: '#0066cc' }
};

/* =====================
   DOM
   ===================== */
const $ = id => document.getElementById(id);
const electionBoxes = $('electionBoxes');
const searchInput = $('searchInput');
const createElectionModal = $('createElectionModal');
const electionForm = $('electionForm');
const actionModal = $('actionModal');
const toast = $('toast');

/* =====================
   STATE  (NOTE: the list is called "elections" so it never clashes with function parameters)
   ===================== */
let elections = loadElections();
let candidateCounts = {};     // { IT: 12, ESM: 1, ... }  real numbers from the database
let currentSearch = '';
let editingId = null;
let pendingAction = null;

/* =====================
   STORAGE
   ===================== */
function loadElections() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    const arr = raw ? JSON.parse(raw) : [];
    return Array.isArray(arr) ? arr : [];
  } catch (e) { return []; }
}
function saveElections() {
  try { localStorage.setItem(STORAGE_KEY, JSON.stringify(elections)); }
  catch (e) { console.warn('Could not save elections', e); }
}

/* =====================
   UTIL
   ===================== */
function escapeHtml(s) {
  if (s === undefined || s === null) return '';
  return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'})[m]);
}
let toastTimer;
function showToast(message, type = 'info') {
  toast.textContent = message;
  toast.className = 'toast show' + (type === 'success' ? ' success' : type === 'error' ? ' error' : '');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => toast.classList.remove('show'), 3000);
}
function normalizeAvatarPath(raw) {
  if (!raw) return '../assests/default-avatar.jpg';
  let s = String(raw).trim().replace(/\\+/g, '/');
  if (!s) return '../assests/default-avatar.jpg';
  if (/^https?:\/\//i.test(s) || s.startsWith('/') || s.startsWith('../')) return s;
  return '../' + s.replace(/^\/+/, '');
}
function formatDate(iso) {
  const d = new Date(iso);
  if (isNaN(d)) return '—';
  return d.toLocaleDateString() + ' ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}
/* Draft stays draft. Published = upcoming / active / completed based on the dates. */
function effectiveStatus(e) {
  if (e.status === 'draft') return 'draft';
  const now = Date.now();
  const start = new Date(e.startDate).getTime();
  const end = new Date(e.endDate).getTime();
  if (now < start) return 'upcoming';
  if (now > end) return 'completed';
  return 'active';
}
const STATUS_LABEL = { draft: 'Draft', upcoming: 'Upcoming', active: 'Active', completed: 'Completed' };

/* =====================
   REAL CANDIDATE COUNTS
   ===================== */
async function loadCandidateCount(code) {
  const org = ORGS[code];
  if (!org) return;
  try {
    const url = `${GET_CANDIDATES}?${org.filter}=${encodeURIComponent(code)}`;
    const res = await fetch(url, { cache: 'no-store' });
    const json = await res.json();
    const rows = Array.isArray(json) ? json : (json && Array.isArray(json.data) ? json.data : null);
    if (!rows) throw new Error('Unexpected response');
    candidateCounts[code] = rows.length;
  } catch (err) {
    console.error('Candidate count failed for', code, err);
    candidateCounts[code] = null;
  }
}
async function loadAllCandidateCounts() {
  const codes = [...new Set(elections.map(e => e.organization))];
  await Promise.all(codes.map(loadCandidateCount));
  renderElectionBoxes();
}

/* =====================
   RENDER
   ===================== */
function renderElectionBoxes() {
  electionBoxes.innerHTML = '';
  const term = currentSearch.trim().toLowerCase();
  const list = !term ? elections : elections.filter(e =>
    (e.title || '').toLowerCase().includes(term) ||
    (e.organization || '').toLowerCase().includes(term) ||
    (e.description || '').toLowerCase().includes(term)
  );

  if (list.length === 0) {
    electionBoxes.innerHTML = `
      <div class="empty-state">
        <i class="fas fa-vote-yea"></i>
        <h3>No elections found</h3>
        <p>${elections.length ? 'Try changing your search term.' : 'Click “Create Election” to add your first election.'}</p>
      </div>`;
    return;
  }

  list.forEach(e => {
    const status = effectiveStatus(e);
    const color = (ORGS[e.organization] && ORGS[e.organization].color) || '#003366';
    const cc = candidateCounts[e.organization];
    const candidatesText = cc === undefined ? '…' : (cc === null ? '—' : cc);
    const voters = Number(e.voters) || 0;
    const voted = Number(e.voted) || 0;
    const pct = voters > 0 ? Math.round((voted / voters) * 100) : 0;

    const box = document.createElement('div');
    box.className = 'election-box';
    box.innerHTML = `
      <div class="election-header-box" style="background-color:${color}">
        <div class="election-avatar" style="color:${color}"><i class="fas fa-vote-yea"></i></div>
        <div class="election-info">
          <h3>${escapeHtml(e.organization)}</h3>
          <p>${escapeHtml(e.title)}</p>
        </div>
      </div>
      <div class="election-stats">
        <div class="stat"><div class="stat-value">${voters}</div><div class="stat-label">Voters</div></div>
        <div class="stat"><div class="stat-value">${candidatesText}</div><div class="stat-label">Candidates</div></div>
        <div class="stat"><div class="stat-value">${pct}%</div><div class="stat-label">Voted</div></div>
      </div>
      <div class="election-details">
        <div class="election-detail"><span class="election-detail-label">Status:</span><span class="status-badge status-${status}">${STATUS_LABEL[status]}</span></div>
        <div class="election-detail"><span class="election-detail-label">Starts:</span><span>${escapeHtml(formatDate(e.startDate))}</span></div>
        <div class="election-detail"><span class="election-detail-label">Ends:</span><span>${escapeHtml(formatDate(e.endDate))}</span></div>
        ${e.description ? `<div class="election-detail"><span class="election-detail-label">About:</span><span style="text-align:right">${escapeHtml(e.description)}</span></div>` : ''}
      </div>
      <div class="election-actions">
        <button type="button" class="btn-action" data-action="edit" data-election="${escapeHtml(e.id)}"><i class="fas fa-edit"></i> Edit</button>
        <button type="button" class="btn-action danger" data-action="delete" data-election="${escapeHtml(e.id)}"><i class="fas fa-trash"></i> Delete</button>
      </div>
    `;
    electionBoxes.appendChild(box);
  });

  electionBoxes.querySelectorAll('.btn-action').forEach(btn => btn.addEventListener('click', handleElectionAction));
}

/* =====================
   ACTIONS: create / edit / delete
   ===================== */
function handleElectionAction(ev) {
  const action = ev.currentTarget.getAttribute('data-action');
  const id = ev.currentTarget.getAttribute('data-election');
  const election = elections.find(x => x.id === id);
  if (!election) return;

  if (action === 'edit') openEditModal(election);
  if (action === 'delete') {
    showActionModal('Delete Election',
      `Are you sure you want to delete "${election.title}"? This action cannot be undone.`,
      () => deleteElection(id));
  }
}

function openCreateModal() {
  editingId = null;
  electionForm.reset();
  $('electionModalTitle').textContent = 'Create New Election';
  $('electionSubmitBtn').textContent = 'Create Election';
  createElectionModal.style.display = 'flex';
}
function openEditModal(election) {
  editingId = election.id;
  $('electionTitle').value = election.title;
  $('electionOrganization').value = election.organization;
  $('electionDescription').value = election.description || '';
  $('startDate').value = (election.startDate || '').slice(0, 16);
  $('endDate').value = (election.endDate || '').slice(0, 16);
  $('electionStatus').value = election.status;
  $('electionModalTitle').textContent = 'Edit Election';
  $('electionSubmitBtn').textContent = 'Update Election';
  createElectionModal.style.display = 'flex';
}
function closeElectionModalFn() {
  createElectionModal.style.display = 'none';
  electionForm.reset();
  editingId = null;
}

function handleElectionSubmit(ev) {
  ev.preventDefault();
  const data = {
    title: $('electionTitle').value.trim(),
    organization: $('electionOrganization').value,
    description: $('electionDescription').value.trim(),
    startDate: $('startDate').value,
    endDate: $('endDate').value,
    status: $('electionStatus').value
  };

  if (!data.title || !data.organization) { showToast('Title and Organization are required', 'error'); return; }
  if (!data.startDate || !data.endDate) { showToast('Start and End date are required', 'error'); return; }
  if (new Date(data.endDate) <= new Date(data.startDate)) { showToast('End date must be after the start date', 'error'); return; }

  if (editingId) {
    const idx = elections.findIndex(x => x.id === editingId);
    if (idx === -1) { showToast('Election not found', 'error'); return; }
    elections[idx] = { ...elections[idx], ...data };
    showToast(`Election "${data.title}" updated successfully!`, 'success');
  } else {
    elections.push({ id: 'el_' + Date.now(), ...data, voters: 0, voted: 0 });
    showToast(`Election "${data.title}" created successfully!`, 'success');
  }

  saveElections();
  closeElectionModalFn();
  renderElectionBoxes();
  if (candidateCounts[data.organization] === undefined) {
    loadCandidateCount(data.organization).then(renderElectionBoxes);
  }
}

function deleteElection(id) {
  const idx = elections.findIndex(x => x.id === id);
  if (idx === -1) return;
  const name = elections[idx].title;
  elections.splice(idx, 1);
  saveElections();
  renderElectionBoxes();
  showToast(`Election "${name}" deleted successfully!`, 'success');
}

/* =====================
   CONFIRM MODAL
   ===================== */
function showActionModal(title, content, callback) {
  $('actionModalTitle').textContent = title;
  $('actionModalContent').innerHTML = `<p>${escapeHtml(content)}</p>`;
  pendingAction = callback;
  actionModal.style.display = 'flex';
}
function closeActionModalFn() {
  actionModal.style.display = 'none';
  pendingAction = null;
}

/* =====================
   ADMIN PROFILE (one loader only)
   ===================== */
async function loadAdminProfile() {
  try {
    const res = await fetch(GET_ADMIN, { cache: 'no-store' });
    if (!res.ok) return;
    const json = await res.json().catch(() => null);
    if (!json || json.success === false) return;
    const name = json.name || json.admin_name || json.fullname || 'Administrator';
    const role = json.role || json.position || 'Administration';
    $('adminName').textContent = name;
    $('adminRole').textContent = /admin/i.test(String(role)) ? 'Administration' : role;
    $('adminAvatar').src = normalizeAvatarPath(json.avatar || json.avatar_path || json.avatarUrl || json.profile);
  } catch (err) {
    console.warn('loadAdminProfile error', err);
  }
}

/* =====================
   EVENTS
   ===================== */
function setupEventListeners() {
  /* profile dropdown: ONE listener (before, two listeners toggled it twice so it never opened) */
  $('userProfile').addEventListener('click', () => {
    const dd = $('dropdown');
    dd.style.display = dd.style.display === 'block' ? 'none' : 'block';
  });
  document.addEventListener('click', ev => {
    if (!$('userProfile').contains(ev.target)) $('dropdown').style.display = 'none';
  });
  $('logoutBtn').addEventListener('click', ev => {
    ev.preventDefault();
    showToast('Logging out...');
    setTimeout(() => location.href = '../login.html', 600);
  });

  $('menuToggle').addEventListener('click', () => {
    const sb = $('sidebar'), mc = $('mainContent');
    if (sb.style.left === '0px' || sb.style.left === '') { sb.style.left = '-250px'; mc.style.marginLeft = '0'; }
    else { sb.style.left = '0px'; mc.style.marginLeft = '250px'; }
  });

  searchInput.addEventListener('input', ev => { currentSearch = ev.target.value; renderElectionBoxes(); });

  $('addElectionBtn').addEventListener('click', openCreateModal);
  $('closeElectionModal').addEventListener('click', closeElectionModalFn);
  $('cancelElectionBtn').addEventListener('click', closeElectionModalFn);
  electionForm.addEventListener('submit', handleElectionSubmit);

  $('closeActionModal').addEventListener('click', closeActionModalFn);
  $('cancelActionBtn').addEventListener('click', closeActionModalFn);
  $('confirmActionBtn').addEventListener('click', () => {
    const fn = pendingAction;
    closeActionModalFn();
    if (fn) fn();
  });

  window.addEventListener('click', ev => {
    if (ev.target === createElectionModal) closeElectionModalFn();
    if (ev.target === actionModal) closeActionModalFn();
  });

  /* keep statuses fresh while the page stays open */
  setInterval(renderElectionBoxes, 60000);
}

/* =====================
   INIT
   ===================== */
document.addEventListener('DOMContentLoaded', () => {
  const sel = $('electionOrganization');
  Object.keys(ORGS).forEach(code => {
    const opt = document.createElement('option');
    opt.value = code;
    opt.textContent = `${code} - ${ORGS[code].name}`;
    sel.appendChild(opt);
  });

  setupEventListeners();
  renderElectionBoxes();
  loadAdminProfile();
  loadAllCandidateCounts();
});
</script>
</body>
</html>

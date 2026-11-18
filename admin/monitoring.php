<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>eBOTO | Election Monitoring</title>
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

    .monitoring-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; gap: 15px; flex-wrap: wrap; }
    .monitoring-title { color: var(--ustp-blue); font-size: 24px; }
    .filter-box { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
    .filter-select { padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; background-color: white; }
    .refresh-btn { background: var(--ustp-blue); color: white; border: none; border-radius: 6px; padding: 10px 15px; cursor: pointer; transition: 0.3s; display: flex; align-items: center; gap: 8px; }
    .refresh-btn:hover { background: var(--ustp-light-blue); }
    .refresh-btn.spinning i { animation: spin 0.8s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* Stats overview */
    .stats-overview { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .stat-card { background: var(--ustp-white); border-radius: 10px; padding: 20px; display: flex; align-items: center; box-shadow: 0 2px 6px rgba(0,0,0,0.05); transition: transform 0.3s; }
    .stat-card:hover { transform: translateY(-4px); }
    .stat-icon { width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; border-radius: 10px; margin-right: 15px; font-size: 24px; }
    .stat-1 .stat-icon { background: rgba(1,47,107,0.1); color: var(--ustp-blue); }
    .stat-2 .stat-icon { background: rgba(76,175,80,0.1); color: #4caf50; }
    .stat-3 .stat-icon { background: rgba(255,193,7,0.1); color: #ffc107; }
    .stat-4 .stat-icon { background: rgba(156,39,176,0.1); color: #9c27b0; }
    .stat-info h3 { margin: 0; font-size: 24px; font-weight: 700; }
    .stat-info p { font-size: 14px; color: #777; }

    /* Monitoring boxes */
    .monitoring-boxes { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 25px; margin-bottom: 30px; }
    .monitoring-box { background: var(--ustp-white); border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); overflow: hidden; transition: all 0.3s ease; border: 2px solid transparent; cursor: pointer; }
    .monitoring-box:hover { transform: translateY(-8px); box-shadow: 0 8px 20px rgba(0,0,0,0.15); border-color: var(--ustp-gold); }
    .monitoring-header-box { padding: 20px; color: white; display: flex; align-items: center; gap: 15px; }
    .monitoring-avatar { width: 50px; height: 50px; border-radius: 50%; border: 2px solid var(--ustp-gold); background-color: var(--ustp-white); display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
    .monitoring-info h3 { font-size: 18px; margin-bottom: 5px; }
    .monitoring-info p { font-size: 14px; opacity: 0.9; }
    .monitoring-stats { padding: 15px 20px; display: flex; justify-content: space-between; background: rgba(0,102,204,0.05); }
    .stat { text-align: center; }
    .stat-value { font-size: 20px; font-weight: 700; color: var(--ustp-blue); }
    .stat-label { font-size: 12px; color: #666; }
    .monitoring-details { padding: 15px 20px; border-top: 1px solid #eee; }
    .monitoring-detail { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; gap: 10px; }
    .monitoring-detail-label { font-weight: 600; color: var(--ustp-blue); }
    .progress-bar { height: 8px; background: #e9ecef; border-radius: 4px; margin: 10px 0; overflow: hidden; }
    .progress-fill { height: 100%; border-radius: 4px; transition: width 0.5s ease; }
    .progress-active { background: var(--ustp-blue); }
    .progress-upcoming { background: #ffc107; }
    .progress-completed { background: #28a745; }
    .monitoring-actions { padding: 15px 20px; border-top: 1px solid #eee; text-align: center; }
    .btn-view-monitoring { background: var(--ustp-blue); color: white; border: none; border-radius: 6px; padding: 10px 20px; cursor: pointer; font-weight: 600; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; }
    .btn-view-monitoring:hover { background: var(--ustp-light-blue); }

    .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .status-active { background: #d4edda; color: #155724; }
    .status-upcoming { background: #fff3cd; color: #856404; }
    .status-completed { background: #d1ecf1; color: #0c5460; }
    .status-issues { background: #f8d7da; color: #721c24; }

    .empty-state { grid-column: 1 / -1; text-align: center; padding: 40px; background: white; border-radius: 12px; color: #777; }
    .empty-state i { font-size: 48px; color: #ccc; margin-bottom: 15px; }

    /* Activity */
    .activity-section { background: var(--ustp-white); border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); padding: 25px; margin-bottom: 30px; }
    .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #eee; gap: 10px; flex-wrap: wrap; }
    .section-title { color: var(--ustp-blue); font-size: 20px; font-weight: 600; }
    .activity-list { max-height: 300px; overflow-y: auto; }
    .activity-item { display: flex; align-items: center; padding: 12px 0; border-bottom: 1px solid #f5f5f5; }
    .activity-item:last-child { border-bottom: none; }
    .activity-icon { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px; font-size: 16px; flex-shrink: 0; }
    .activity-vote { background: rgba(76,175,80,0.1); color: #4caf50; }
    .activity-system { background: rgba(158,158,158,0.1); color: #9e9e9e; }
    .activity-warning { background: rgba(255,193,7,0.1); color: #ffc107; }
    .activity-content { flex: 1; }
    .activity-text { font-size: 14px; margin-bottom: 4px; }
    .activity-time { font-size: 12px; color: #777; }

    /* Modal */
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; }
    .modal-content { background-color: white; padding: 30px; border-radius: 10px; width: 90%; max-width: 800px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); max-height: 90vh; overflow-y: auto; }
    .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #eee; }
    .modal-header h2 { color: var(--ustp-blue); margin: 0; font-size: 20px; }
    .close-modal { background: none; border: none; font-size: 24px; cursor: pointer; color: var(--ustp-dark-gray); }
    .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
    .btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; transition: 0.3s; }
    .btn-primary { background: var(--ustp-blue); color: white; }
    .btn-primary:hover { background: var(--ustp-light-blue); }
    .btn-secondary { background: #6c757d; color: white; }
    .btn-secondary:hover { background: #5a6268; }

    .election-details { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
    .detail-section { background: #f8f9fa; border-radius: 8px; padding: 15px; margin-bottom: 20px; }
    .election-details .detail-section { margin-bottom: 0; }
    .detail-section h4 { color: var(--ustp-blue); margin-bottom: 10px; border-bottom: 1px solid #dee2e6; padding-bottom: 5px; }
    .detail-item { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; gap: 10px; }
    .detail-label { font-weight: 600; color: var(--ustp-blue); }
    .detail-value { color: #333; text-align: right; }
    .candidate-item { display: flex; justify-content: space-between; padding: 8px 0 4px; font-size: 14px; }
    .vote-chart { height: 10px; background: #e9ecef; border-radius: 5px; overflow: hidden; margin-bottom: 8px; }
    .vote-fill { height: 100%; border-radius: 5px; transition: width 0.5s ease; background: var(--ustp-light-blue); }
    .issue-item { background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; padding: 8px 12px; margin-bottom: 8px; font-size: 14px; }
    .issue-item:last-child { margin-bottom: 0; }
    .timeline-item { display: flex; margin-bottom: 10px; font-size: 14px; }
    .timeline-time { font-weight: 600; color: var(--ustp-blue); min-width: 150px; }
    .timeline-event { flex: 1; padding-left: 10px; border-left: 2px solid var(--ustp-blue); }

    .toast { position: fixed; bottom: 20px; right: 20px; padding: 15px 25px; background-color: var(--ustp-blue); color: white; border-radius: 5px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 1001; transform: translateY(100px); opacity: 0; transition: transform 0.3s, opacity 0.3s; pointer-events: none; max-width: 420px; }
    .toast.show { transform: translateY(0); opacity: 1; }
    .toast.success { background-color: #4caf50; }
    .toast.error { background-color: #f44336; }

    @media (max-width: 768px) {
      .sidebar { width: 70px; }
      .nav-item a, .user-info { display: none; }
      .main-content { margin-left: 70px; }
      .menu-toggle { display: block; }
      .monitoring-header { flex-direction: column; align-items: flex-start; }
      .filter-box { width: 100%; }
      .monitoring-boxes { grid-template-columns: 1fr; }
      .stats-overview { grid-template-columns: repeat(2, 1fr); }
      .election-details { grid-template-columns: 1fr; }
      .timeline-time { min-width: 110px; }
    }
    @media (max-width: 480px) { .stats-overview { grid-template-columns: 1fr; } }
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
      <li class="nav-item"><i class="fas fa-user-tie"></i><a href="officers.php">Officers</a></li>
      <li class="nav-item"><i class="fas fa-user-tag"></i><a href="candidates.php">Candidates</a></li>
      <li class="nav-item"><i class="fas fa-check-circle"></i><a href="validate-elections.php">Elections</a></li>
      <li class="nav-item active"><i class="fas fa-chart-line"></i><a href="monitoring.php">Monitoring</a></li>
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
      <h1>Election Monitoring</h1>
      <i class="fas fa-bars menu-toggle" id="menuToggle"></i>
    </div>

    <div class="content">
      <div class="monitoring-header">
        <h2 class="monitoring-title">Real-time Election Monitoring</h2>
        <div class="filter-box">
          <select class="filter-select" id="statusFilter">
            <option value="all">All Status</option>
            <option value="active">Active Only</option>
            <option value="upcoming">Upcoming</option>
            <option value="completed">Completed</option>
          </select>
          <select class="filter-select" id="organizationFilter">
            <option value="all">All Organizations</option>
          </select>
          <button class="refresh-btn" id="refreshBtn" type="button"><i class="fas fa-sync-alt"></i> Refresh</button>
        </div>
      </div>

      <!-- Stats overview -->
      <div class="stats-overview">
        <div class="stat-card stat-1">
          <div class="stat-icon"><i class="fas fa-vote-yea"></i></div>
          <div class="stat-info"><h3 id="totalVotes">—</h3><p>Total Votes Cast</p></div>
        </div>
        <div class="stat-card stat-2">
          <div class="stat-icon"><i class="fas fa-play-circle"></i></div>
          <div class="stat-info"><h3 id="activeElections">—</h3><p>Active Elections</p></div>
        </div>
        <div class="stat-card stat-3">
          <div class="stat-icon"><i class="fas fa-clock"></i></div>
          <div class="stat-info"><h3 id="turnoutRate">—</h3><p>Voter Turnout</p></div>
        </div>
        <div class="stat-card stat-4">
          <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
          <div class="stat-info"><h3 id="issuesCount">—</h3><p>Active Issues</p></div>
        </div>
      </div>

      <div class="monitoring-boxes" id="monitoringBoxes"></div>

      <!-- Real-time activity -->
      <div class="activity-section">
        <div class="section-header">
          <h3 class="section-title">Real-time Activity</h3>
          <span class="status-badge status-completed" id="activityStatus">Connecting…</span>
        </div>
        <div class="activity-list" id="activityList"></div>
      </div>
    </div>
  </div>

  <!-- View details modal -->
  <div class="modal" id="viewDetailsModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 id="electionDetailsTitle">Election Details</h2>
        <button class="close-modal" id="closeDetailsModal" type="button">&times;</button>
      </div>
      <div id="electionDetailsContent"></div>
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" id="closeDetailsBtn">Close</button>
        <button type="button" class="btn btn-primary" id="exportDetailsBtn"><i class="fas fa-download"></i> Export Report (CSV)</button>
      </div>
    </div>
  </div>

  <div class="toast" id="toast"></div>

<script>
/* =====================
   CONFIG
   ===================== */
const GET_ADMIN = '../php/getAdmin.php';
const MONITOR_API = '../php/monitoring_api.php';   // lives in the same php folder as getAdmin.php
const REFRESH_MS = 10000;                          // realtime: reload from the database every 10 seconds

/* Known organizations (name + color). Any other organization found in the database
   (SOCE, SBA, SAS ...) is picked up automatically and gets a color from the palette. */
const ORGS = {
  IT:    { name: 'Information Technology',                  color: '#003366' },
  ESM:   { name: 'Energy Solution Management',              color: '#f44336' },
  MET:   { name: 'Manufacturing Engineering Technology',    color: '#9c27b0' },
  TCM:   { name: 'Technology Communication Management',     color: '#ff9800' },
  NAME:  { name: 'Naval Architecture & Marine Engineering', color: '#4caf50' },
  CACHE: { name: 'The CACHE Organization',                  color: '#795548' },
  USG:   { name: 'University Student Government',           color: '#0066cc' },
  SOCE:  { name: 'SOCE',                                    color: '#1565c0' }
};
const PALETTE = ['#00897b', '#6d4c41', '#5e35b1', '#c2185b', '#2e7d32', '#ef6c00', '#00838f', '#ad1457'];
function orgColor(code) {
  if (ORGS[code]) return ORGS[code].color;
  let h = 0;
  for (const ch of String(code || '')) h = (h * 31 + ch.charCodeAt(0)) >>> 0;
  return PALETTE[h % PALETTE.length];
}
const STATUS_LABEL = { active: 'Active', upcoming: 'Upcoming', completed: 'Completed' };

/* =====================
   DOM / STATE
   ===================== */
const $ = id => document.getElementById(id);
const monitoringBoxes = $('monitoringBoxes');
const activityList = $('activityList');
const activityStatus = $('activityStatus');
const viewDetailsModal = $('viewDetailsModal');
const toast = $('toast');

let elections = [];
let activity = [];
let stats = null;
let loaded = false;
let loadError = null;
let statusFilter = 'all';
let orgFilter = 'all';
let openElectionId = null;     // election currently shown in the details modal
let refreshing = false;
let warnedOffline = false;

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
  toastTimer = setTimeout(() => toast.classList.remove('show'), 3500);
}
function normalizeAvatarPath(raw) {
  if (!raw) return '../assests/default-avatar.jpg';
  let s = String(raw).trim().replace(/\\+/g, '/');
  if (!s) return '../assests/default-avatar.jpg';
  if (/^https?:\/\//i.test(s) || s.startsWith('/') || s.startsWith('../')) return s;
  return '../' + s.replace(/^\/+/, '');
}
/* database times are plain "YYYY-MM-DD HH:MM:SS" strings: show them as they are stored */
function parseDbDate(s) {
  if (!s) return null;
  const d = new Date(String(s).replace(' ', 'T'));
  return isNaN(d) ? null : d;
}
function formatDateTime(s) {
  const d = parseDbDate(s);
  if (!d) return '—';
  const hasTime = /\d{2}:\d{2}/.test(String(s));
  return d.toLocaleDateString() + (hasTime ? ' ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '');
}
function agoText(sec) {
  if (sec === null || sec === undefined) return 'No activity yet';
  sec = Number(sec);
  if (sec < 10) return 'Just now';
  if (sec < 60) return sec + ' seconds ago';
  const m = Math.floor(sec / 60);
  if (m < 60) return m + (m === 1 ? ' minute ago' : ' minutes ago');
  const h = Math.floor(m / 60);
  if (h < 24) return h + (h === 1 ? ' hour ago' : ' hours ago');
  const d = Math.floor(h / 24);
  return d + (d === 1 ? ' day ago' : ' days ago');
}

/* =====================
   LOAD FROM DATABASE
   ===================== */
async function fetchMonitoring() {
  const res = await fetch(MONITOR_API, { cache: 'no-store' });
  const txt = await res.text();
  let json;
  try { json = JSON.parse(txt); }
  catch (e) { throw new Error(`Server did not return JSON (HTTP ${res.status}). Check this path: ${MONITOR_API}`); }
  if (!res.ok || json.success === false) throw new Error(json.message || `HTTP ${res.status}`);
  return json;
}

async function refreshAll(manual = false) {
  if (refreshing) return;
  refreshing = true;
  if (manual) $('refreshBtn').classList.add('spinning');
  try {
    const json = await fetchMonitoring();
    elections = Array.isArray(json.elections) ? json.elections : [];
    activity = Array.isArray(json.activity) ? json.activity : [];
    stats = json.stats || null;
    loadError = null;
    warnedOffline = false;
    if (Array.isArray(json.warnings) && json.warnings.length) console.warn('monitoring warnings:', json.warnings);
    const t = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    activityStatus.textContent = 'Live • updated ' + t;
    activityStatus.className = 'status-badge status-active';
    if (manual) showToast('Data refreshed successfully', 'success');
  } catch (err) {
    console.error('Monitoring load failed', err);
    loadError = err.message;
    activityStatus.textContent = 'Offline';
    activityStatus.className = 'status-badge status-issues';
    if (manual || !warnedOffline) { showToast(err.message, 'error'); warnedOffline = true; }
  } finally {
    loaded = true;
    refreshing = false;
    $('refreshBtn').classList.remove('spinning');
  }
  renderAll();
}

/* =====================
   ORGANIZATION FILTER (built from the elections found in the database)
   ===================== */
function rebuildOrgFilter() {
  const sel = $('organizationFilter');
  const codes = [...new Set(elections.map(e => e.organization).filter(Boolean))].sort();
  const sig = codes.join('|');
  if (sel.dataset.sig === sig) return;                 // nothing changed -> keep the dropdown as it is
  sel.dataset.sig = sig;
  sel.innerHTML = '<option value="all">All Organizations</option>';
  codes.forEach(code => {
    const opt = document.createElement('option');
    opt.value = code;
    opt.textContent = (ORGS[code] && ORGS[code].name !== code) ? `${code} - ${ORGS[code].name}` : code;
    sel.appendChild(opt);
  });
  if (orgFilter !== 'all' && !codes.includes(orgFilter)) orgFilter = 'all';
  sel.value = orgFilter;
}

/* =====================
   RENDER
   ===================== */
function renderAll() {
  rebuildOrgFilter();
  renderStats();
  renderMonitoringBoxes();
  renderActivityList();
  if (openElectionId !== null && viewDetailsModal.style.display === 'flex') {
    const e = elections.find(x => String(x.id) === String(openElectionId));
    if (e) renderDetails(e);
  }
}

function renderStats() {
  if (!stats) return;
  $('totalVotes').textContent = Number(stats.total_votes || 0).toLocaleString();
  $('activeElections').textContent = stats.active_elections ?? 0;
  $('turnoutRate').textContent = (stats.avg_turnout ?? 0) + '%';
  $('issuesCount').textContent = stats.issues ?? 0;
}

function renderMonitoringBoxes() {
  monitoringBoxes.innerHTML = '';

  if (!loaded) {
    monitoringBoxes.innerHTML = `<div class="empty-state"><i class="fas fa-spinner fa-spin"></i><h3>Loading elections…</h3></div>`;
    return;
  }
  if (loadError && elections.length === 0) {
    monitoringBoxes.innerHTML = `<div class="empty-state"><i class="fas fa-triangle-exclamation"></i><h3>Could not load monitoring data</h3><p>${escapeHtml(loadError)}</p></div>`;
    return;
  }

  let list = elections;
  if (statusFilter !== 'all') list = list.filter(e => e.status === statusFilter);
  if (orgFilter !== 'all') list = list.filter(e => e.organization === orgFilter);

  if (list.length === 0) {
    monitoringBoxes.innerHTML = `<div class="empty-state"><i class="fas fa-chart-line"></i><h3>No elections found</h3><p>${elections.length ? 'Try changing your filters.' : 'No published elections yet. Create one in Elections.'}</p></div>`;
    return;
  }

  list.forEach(e => {
    const status = STATUS_LABEL[e.status] ? e.status : 'upcoming';
    const color = orgColor(e.organization);
    const box = document.createElement('div');
    box.className = 'monitoring-box';
    box.setAttribute('data-election', e.id);
    box.innerHTML = `
      <div class="monitoring-header-box" style="background-color:${color}">
        <div class="monitoring-avatar" style="color:${color}"><i class="fas fa-chart-bar"></i></div>
        <div class="monitoring-info">
          <h3>${escapeHtml(e.organization || '—')}</h3>
          <p>${escapeHtml(e.title)}</p>
        </div>
      </div>
      <div class="monitoring-stats">
        <div class="stat"><div class="stat-value">${e.voters}</div><div class="stat-label">Voters</div></div>
        <div class="stat"><div class="stat-value">${e.voted}</div><div class="stat-label">Voted</div></div>
        <div class="stat"><div class="stat-value">${e.turnout}%</div><div class="stat-label">Turnout</div></div>
      </div>
      <div class="monitoring-details">
        <div class="monitoring-detail"><span class="monitoring-detail-label">Status:</span><span class="status-badge status-${status}">${STATUS_LABEL[status]}</span></div>
        <div class="monitoring-detail"><span class="monitoring-detail-label">Issues:</span><span>${e.issues} ${e.issues === 1 ? 'issue' : 'issues'}</span></div>
        <div class="monitoring-detail"><span class="monitoring-detail-label">Last Activity:</span><span>${escapeHtml(agoText(e.last_activity_seconds))}</span></div>
        <div class="progress-bar"><div class="progress-fill progress-${status}" style="width:${Math.min(100, e.turnout)}%"></div></div>
      </div>
      <div class="monitoring-actions">
        <button class="btn-view-monitoring" type="button"><i class="fas fa-chart-line"></i> View Details</button>
      </div>
    `;
    monitoringBoxes.appendChild(box);
  });
}

function renderActivityList() {
  activityList.innerHTML = '';
  if (!activity.length) {
    activityList.innerHTML = `<div class="empty-state" style="padding:20px;"><i class="fas fa-info-circle" style="font-size:24px;margin-bottom:10px;"></i><p>No recent activity</p></div>`;
    return;
  }
  const icons = { vote: ['activity-vote', 'fa-vote-yea'], system: ['activity-system', 'fa-cog'], warning: ['activity-warning', 'fa-exclamation-triangle'] };
  activity.forEach(a => {
    const [cls, icon] = icons[a.type] || icons.system;
    const item = document.createElement('div');
    item.className = 'activity-item';
    item.innerHTML = `
      <div class="activity-icon ${cls}"><i class="fas ${icon}"></i></div>
      <div class="activity-content">
        <div class="activity-text">${escapeHtml(a.text)}</div>
        <div class="activity-time">${escapeHtml(agoText(a.seconds_ago))} • ${escapeHtml(a.election)}</div>
      </div>`;
    activityList.appendChild(item);
  });
}

/* =====================
   DETAILS MODAL
   ===================== */
function groupByPosition(cands) {
  const groups = {};
  cands.forEach(c => { (groups[c.position] = groups[c.position] || []).push(c); });
  return Object.keys(groups).map(position => ({ position, candidates: groups[position] }));
}

function renderDetails(e) {
  $('electionDetailsTitle').textContent = `${e.organization || ''} - ${e.title}`.replace(/^ - /, '');
  const status = STATUS_LABEL[e.status] ? e.status : 'upcoming';

  let html = `
    <div class="election-details">
      <div class="detail-section">
        <h4>Election Information</h4>
        <div class="detail-item"><span class="detail-label">Status:</span><span class="detail-value"><span class="status-badge status-${status}">${STATUS_LABEL[status]}</span></span></div>
        <div class="detail-item"><span class="detail-label">Start Date:</span><span class="detail-value">${escapeHtml(formatDateTime(e.start))}</span></div>
        <div class="detail-item"><span class="detail-label">End Date:</span><span class="detail-value">${escapeHtml(formatDateTime(e.end))}</span></div>
        <div class="detail-item"><span class="detail-label">Total Voters:</span><span class="detail-value">${e.voters}</span></div>
        <div class="detail-item"><span class="detail-label">Votes Cast:</span><span class="detail-value">${e.voted}</span></div>
        <div class="detail-item"><span class="detail-label">Turnout Rate:</span><span class="detail-value">${e.turnout}%</span></div>
      </div>
      <div class="detail-section">
        <h4>Election Positions</h4>
        ${(e.positions && e.positions.length)
          ? e.positions.map(p => `<div class="detail-item"><span class="detail-label">•</span><span class="detail-value">${escapeHtml(p)}</span></div>`).join('')
          : '<div class="detail-item"><span class="detail-value">No positions yet</span></div>'}
      </div>
    </div>`;

  if (e.candidates && e.candidates.length) {
    html += `<div class="detail-section"><h4>${status === 'upcoming' ? 'Candidates' : 'Candidate Results'}</h4>`;
    groupByPosition(e.candidates).forEach(g => {
      const total = g.candidates.reduce((s, c) => s + c.votes, 0);
      html += `<div style="margin-bottom:15px;"><h5 style="color:var(--ustp-blue);margin-bottom:8px;">${escapeHtml(g.position)}</h5>`;
      g.candidates.forEach(c => {
        if (status === 'upcoming') {
          html += `<div class="candidate-item"><span class="detail-label">${escapeHtml(c.name)}</span></div>`;
        } else {
          const pct = total > 0 ? Math.round((c.votes / total) * 100) : 0;
          html += `
            <div class="candidate-item"><span class="detail-label">${escapeHtml(c.name)}</span><span class="detail-value">${c.votes} vote${c.votes === 1 ? '' : 's'} (${pct}%)</span></div>
            <div class="vote-chart"><div class="vote-fill" style="width:${pct}%"></div></div>`;
        }
      });
      html += `</div>`;
    });
    html += `</div>`;
  }

  if (e.issue_list && e.issue_list.length) {
    html += `<div class="detail-section"><h4>Active Issues</h4>${e.issue_list.map(i => `<div class="issue-item"><i class="fas fa-exclamation-circle"></i> ${escapeHtml(i)}</div>`).join('')}</div>`;
  }

  if (e.timeline && e.timeline.length) {
    html += `<div class="detail-section"><h4>Election Timeline</h4>${e.timeline.map(t => `
      <div class="timeline-item"><div class="timeline-time">${escapeHtml(formatDateTime(t.time))}</div><div class="timeline-event">${escapeHtml(t.event)}</div></div>`).join('')}</div>`;
  }

  $('electionDetailsContent').innerHTML = html;
}

function openDetails(id) {
  const e = elections.find(x => String(x.id) === String(id));
  if (!e) return;
  openElectionId = e.id;
  renderDetails(e);
  viewDetailsModal.style.display = 'flex';
}
function closeDetails() {
  viewDetailsModal.style.display = 'none';
  openElectionId = null;
}

/* =====================
   EXPORT (real CSV download)
   ===================== */
function csvCell(v) {
  const s = v === null || v === undefined ? '' : String(v);
  return /[",\n\r]/.test(s) ? '"' + s.replace(/"/g, '""') + '"' : s;
}
function exportElection() {
  const e = elections.find(x => String(x.id) === String(openElectionId));
  if (!e) return;
  const rows = [
    ['Election', e.title],
    ['Organization', e.organization],
    ['Status', STATUS_LABEL[e.status] || e.status],
    ['Start', e.start],
    ['End', e.end],
    ['Registered Voters', e.voters],
    ['Votes Cast', e.voted],
    ['Turnout', e.turnout + '%'],
    ['Generated', new Date().toLocaleString()],
    [],
    ['Position', 'Candidate', 'Votes', 'Share']
  ];
  groupByPosition(e.candidates || []).forEach(g => {
    const total = g.candidates.reduce((s, c) => s + c.votes, 0);
    g.candidates.forEach(c => rows.push([g.position, c.name, c.votes, (total > 0 ? Math.round((c.votes / total) * 100) : 0) + '%']));
  });
  const csv = '\ufeff' + rows.map(r => r.map(csvCell).join(',')).join('\r\n');
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `election-${e.id}-report.csv`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  setTimeout(() => URL.revokeObjectURL(url), 1000);
  showToast('Report exported successfully!', 'success');
}

/* =====================
   ADMIN PROFILE
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

  $('statusFilter').addEventListener('change', ev => { statusFilter = ev.target.value; renderMonitoringBoxes(); });
  $('organizationFilter').addEventListener('change', ev => { orgFilter = ev.target.value; renderMonitoringBoxes(); });
  $('refreshBtn').addEventListener('click', () => refreshAll(true));

  monitoringBoxes.addEventListener('click', ev => {
    const box = ev.target.closest('.monitoring-box');
    if (box) openDetails(box.getAttribute('data-election'));
  });

  $('closeDetailsModal').addEventListener('click', closeDetails);
  $('closeDetailsBtn').addEventListener('click', closeDetails);
  $('exportDetailsBtn').addEventListener('click', exportElection);
  window.addEventListener('click', ev => { if (ev.target === viewDetailsModal) closeDetails(); });

  /* realtime: every 10s while the tab is visible, and immediately when you come back to it */
  setInterval(() => { if (!document.hidden) refreshAll(); }, REFRESH_MS);
  document.addEventListener('visibilitychange', () => { if (!document.hidden) refreshAll(); });
}

/* =====================
   INIT
   ===================== */
document.addEventListener('DOMContentLoaded', () => {
  setupEventListeners();
  renderMonitoringBoxes();   // shows "Loading…"
  renderActivityList();
  loadAdminProfile();
  refreshAll();
});
</script>
</body>
</html>

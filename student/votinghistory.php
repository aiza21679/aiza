<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voting History - USTP Jasaan</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<script>
    /* Utility helpers (single definitions) */
    function escapeHTML(s){ if (s === null || s === undefined) return ''; return String(s).replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'",'&#039;'); }
    function capitalize(s){ if(!s) return ''; return s.charAt(0).toUpperCase()+s.slice(1); }

    /* Single dropdown toggle + logout used by whole page */
    function toggleDropdown(){
      const d = document.getElementById('dropdown');
      if (!d) return;
      d.style.display = (d.style.display === 'block') ? 'none' : 'block';
    }
    function logoutUser(){
      fetch('../php/logout.php', { method: 'POST' }).finally(()=> window.location.href = '/');
    }
  </script>
</head>
<body>
    <!-- Sidebar - Consistent with Active Elections Page -->
    <nav class="sidebar">
        <div class="logo-container">
            <div class="logo">
                <img src="../assests/suffage.png" alt="eBoto Logo">
            </div>
            <div class="logoW"></div>
        </div>
        
        <div class="nav-menu">
            <a href="dashboard.php"><i class="fas fa-home"></i><span>Dashboard</span></a>
            <a href="vote.php"><i class="fas fa-vote-yea"></i><span>Active Elections</span></a>
            <a href="votinghistory.php" class="active"><i class="fas fa-history"></i><span>Voting History</span></a>
            <a href="candidates.php"><i class="fas fa-user-friends"></i><span>Candidates</span></a>
            <a href="results.php"><i class="fas fa-chart-bar"></i><span>Results</span></a>
            <a href="settings.php"><i class="fas fa-cog"></i><span>Settings</span></a>
        </div>
        
        <!-- User Profile - Consistent with Active Elections Page -->
<div class="user-profile" onclick="toggleDropdown()">
  <div class="user-avatar">
    <!-- id so JS can update src -->
    <img id="sidebarAvatar" src="../assests/default-avatar.jpg" alt="User Avatar" width="48" height="48">
  </div>
  <div class="user-info">
    <!-- ids so JS can update name & role -->
    <h4 id="sidebarName">Guest</h4>
    <p id="sidebarRole">Student</p>
  </div>
  <div class="user-dropdown" id="dropdown">
    <a href="../login.html" onclick="logoutUser()">Logout</a>
  </div>
</div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="header">
            <div class="page-title">
                <h1>Voting History</h1>
                <p>Each result tells a story — of hope, choice, and change!</p>
            </div>
        </div>

        <!-- Stats Cards - Improved Design -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-vote-yea"></i>
                </div>
                <div class="stat-info">
                    <h3 id="total-votes">0</h3>
                    <p>Total Votes Cast</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3 id="elections-participated">0</h3>
                    <p>Elections Participated</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-info">
                    <h3 id="participation-rate">0%</h3>
                    <p>Participation Rate</p>
                </div>
            </div>
        </div>

        <!-- Filter Section - Improved Design -->
        <div class="filter-section">
            <div class="filter-group">
                <label for="year-filter">Academic Year</label>
                <select id="year-filter" class="filter-select">
                    <option value="all">All Years</option>
                    <option value="2025-2026">2025-2026</option>
                    <option value="2024-2025">2024-2025</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="election-filter">Election Type</label>
                <select id="election-filter" class="filter-select">
                    <option value="all">All Elections</option>
                    <option value="it-officers">IT Officers</option>
                    <option value="usg">USG Officers</option>
                    <option value="soce">SOCE Officers</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="status-filter">Status</label>
                <select id="status-filter" class="filter-select">
                    <option value="all">All Status</option>
                    <option value="completed">Completed</option>
                    <option value="ongoing">Ongoing</option>
                </select>
            </div>
            <button class="btn-primary" id="apply-filters">
                <i class="fas fa-filter"></i> Apply Filters
            </button>
        </div>

        <!-- History Table - Improved Design -->
        <div class="history-table-container">
            <div class="table-header">
                <h3><i class="fas fa-history"></i> Voting History</h3>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="search-input" placeholder="Search elections...">
                </div>
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th>Election Name</th>
                        <th>Date</th>
                        <th>Organization</th>
                        <th>Status</th>
                        <th>Your Vote</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="election-table-body">
                    <!-- Table rows will be populated by JavaScript -->
                </tbody>
            </table>

            <div class="pagination">
                <div class="pagination-info">
                    Showing <span id="pagination-start">0</span> to <span id="pagination-end">0</span> of <span id="pagination-total">0</span> entries
                </div>
            </div>
        </div>

        <footer>
            <p>© 2025 eBOTO | USTP Jasaan | All Rights Reserved</p>
        </footer>
    </main>

    <!-- Modal for Election Details -->
    <div class="modal" id="election-details-modal" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modal-election-title">Election Details</h2>
                <button class="close-btn" id="close-details-modal">&times;</button>
            </div>
            <div class="modal-body" id="modal-election-body"></div>
        </div>
    </div>

    <!-- Modal for Election Results -->
    <div class="modal" id="election-results-modal" style="display:none;">
        <div class="modal-content" style="max-height:80vh;overflow:auto;">
            <div class="modal-header">
                <h2 id="modal-results-title">Election Results</h2>
                <button class="close-btn" id="close-results-modal">&times;</button>
            </div>
            <div class="modal-body" id="modal-results-body"></div>
        </div>
    </div>

<script>
/* Voting History frontend (single-file) */
/* Try to detect API base for get_results.php if needed */
const API_CANDIDATE_BASES = [
  "http://localhost/eBotoni/eBotoni/student%20org/",
  "/eBotoni/eBotoni/student%20org/",
  "http://localhost/eBotoni/eBotoni/student/",
  "/eBotoni/eBotoni/student/",
  "../student%20org/",
  "../student/",
  "./student%20org/",
  "./student/",
  "./",
  "../"
];

let elections = [];
let filteredElections = [];
let currentPage = 1;
const pageSize = 5;
let resolvedApiBase = null;

// DOM refs
const electionTableBody = document.getElementById('election-table-body');
const electionDetailsModal = document.getElementById('election-details-modal');
const electionResultsModal = document.getElementById('election-results-modal');
const closeDetailsModal = document.getElementById('close-details-modal');
const closeResultsModal = document.getElementById('close-results-modal');
const modalElectionTitle = document.getElementById('modal-election-title');
const modalElectionBody = document.getElementById('modal-election-body');
const modalResultsTitle = document.getElementById('modal-results-title');
const modalResultsBody = document.getElementById('modal-results-body');

const totalVotesEl = document.getElementById('total-votes');
const electionsParticipatedEl = document.getElementById('elections-participated');
const participationRateEl = document.getElementById('participation-rate');

const searchInput = document.getElementById('search-input');
const yearFilter = document.getElementById('year-filter');
const electionFilter = document.getElementById('election-filter');
const statusFilter = document.getElementById('status-filter');
const applyFiltersBtn = document.getElementById('apply-filters');

const paginationStart = document.getElementById('pagination-start');
const paginationEnd = document.getElementById('pagination-end');
const paginationTotal = document.getElementById('pagination-total');

/* Small helpers */
async function fetchText(url){
  try{
    const r = await fetch(url, { cache:'no-store', credentials: 'same-origin' });
    const text = await r.text();
    return { ok: r.ok, status: r.status, text };
  }catch(e){ return { ok:false, status:0, text:null }; }
}

async function detectApiBase(){
  if(resolvedApiBase) return resolvedApiBase;
  for(const base of API_CANDIDATE_BASES){
    let b = String(base); if(!b.endsWith('/')) b += '/';
    try {
      const r1 = await fetchText(b + 'get_elections.php');
      if(r1 && r1.text && (r1.text.trim().startsWith('[') || r1.text.trim().startsWith('{'))) { resolvedApiBase = b; return b; }
    } catch(e){}
    try {
      const r2 = await fetchText(b + 'get_results.php?election_id=1');
      if(r2 && r2.text && (r2.text.trim().startsWith('[') || r2.text.trim().startsWith('{'))) { resolvedApiBase = b; return b; }
    } catch(e){}
  }
  const fallbacks = ['./student/','./student%20org/','../student/','../student%20org/','./','../'];
  for(const fb of fallbacks){
    let b = fb.endsWith('/')? fb : fb + '/';
    try {
      const r = await fetchText(b + 'get_results.php?election_id=1');
      if(r && r.text && (r.text.trim().startsWith('[') || r.text.trim().startsWith('{'))){ resolvedApiBase = b; return b; }
    } catch(e){}
  }
  return null;
}

async function fetchResultsForElection(electionId){
  if(!resolvedApiBase) await detectApiBase();
  const basesToTry = (Array.isArray(API_CANDIDATE_BASES) ? API_CANDIDATE_BASES : []).slice();
  if(resolvedApiBase) basesToTry.unshift(resolvedApiBase);
  for(let cand of basesToTry){
    let base = String(cand); if(!base.endsWith('/')) base = base + '/';
    const url = base + 'get_results.php?election_id=' + encodeURIComponent(electionId);
    try {
      const r = await fetchText(url);
      if(r && r.text && (r.text.trim().startsWith('{') || r.text.trim().startsWith('['))){
        try {
          const j = JSON.parse(r.text);
          if(j && (Array.isArray(j.positions) || j.status || j.election)) {
            resolvedApiBase = base;
            return { ok:true, data: j };
          }
        } catch(e){}
      }
    } catch(e){}
  }
  const rels = ['./student/','./student%20org/','../student/','../student%20org/','./','../'];
  for(const r of rels){
    let base = r.endsWith('/')? r : r + '/';
    const url = base + 'get_results.php?election_id=' + encodeURIComponent(electionId);
    try {
      const rr = await fetchText(url);
      if(rr && rr.text && (rr.text.trim().startsWith('{') || rr.text.trim().startsWith('['))){
        try { const j = JSON.parse(rr.text); resolvedApiBase = base; return { ok:true, data: j }; } catch(e){}
      }
    } catch(e){}
  }
  return { ok:false };
}

/* Fetch voting history from server and normalize */
async function fetchElections(apiPath) {
  try {
    console.debug('[history] fetching voting history from', apiPath);
    const resp = await fetch(apiPath, { credentials: 'include', cache: 'no-store' });

    // Always read text first to help diagnose stray HTML/PHP warnings
    const rawText = await resp.text();
    let json;
    try {
      json = rawText ? JSON.parse(rawText) : null;
    } catch (err) {
      console.error('[history] invalid JSON response from server (first 2000 chars):', rawText.slice(0,2000));
      // fall back to empty
      elections = []; filteredElections = [];
      computeStats();
      applyFiltersAndSearch();
      return;
    }

    console.debug('[history] server responded:', json);

    // tolerant extraction of array payload
    let rows = [];
    if (!json) {
      rows = [];
    } else if (Array.isArray(json)) {
      rows = json;
    } else if (Array.isArray(json.data)) {
      rows = json.data;
    } else if (Array.isArray(json.result)) {
      rows = json.result;
    } else if (Array.isArray(json.elections)) {
      rows = json.elections;
    } else if (json.success && Array.isArray(json.rows)) {
      rows = json.rows;
    } else {
      // try to find the first array value inside the object
      const maybeArray = Object.values(json).find(v => Array.isArray(v));
      if (Array.isArray(maybeArray)) rows = maybeArray;
      else rows = [];
    }

    // Normalize each row defensively
    elections = (rows || []).map(row => {
      const rawRow = row || {};
      return {
        id: Number(rawRow.id ?? rawRow.Election_ID ?? rawRow.election_id ?? rawRow.dbId ?? rawRow.deleted_election_id ?? 0) || (rawRow.deleted_election_id ? null : 0),
        deleted_election_id: rawRow.deleted_election_id ?? null,
        name: rawRow.name ?? rawRow.Title ?? rawRow.title ?? rawRow.election_name ?? rawRow.Name ?? '',
        date: rawRow.date ?? rawRow.Date ?? rawRow.startDate ?? rawRow.Start_Date ?? '',
        organization: rawRow.organization ?? rawRow.Organization ?? rawRow.Org ?? rawRow.Org_Name ?? '',
        status: (rawRow.status ?? rawRow.Status ?? '').toString().toLowerCase() || (rawRow.deleted_election_id ? 'deleted' : ''),
        voted: !!(rawRow.voted || rawRow.has_voted || rawRow.user_voted || rawRow.Voted),
        // userSelections may be object or empty; normalize to object
        userSelections: (rawRow.userSelections && typeof rawRow.userSelections === 'object') ? rawRow.userSelections : (rawRow.selections && typeof rawRow.selections === 'object' ? rawRow.selections : (rawRow.vote && typeof rawRow.vote === 'object' ? rawRow.vote : {})),
        // results may be positions array
        results: Array.isArray(rawRow.results) ? rawRow.results : (Array.isArray(rawRow.positions) ? rawRow.positions : []),
        raw: rawRow
      };
    });

    // Keep a working copy for filtering/pagination
    filteredElections = [...elections];

    computeStats();
    applyFiltersAndSearch();
  } catch (err) {
    console.error('[history] fetchElections error:', err);
    elections = [];
    filteredElections = [];
    computeStats();
    applyFiltersAndSearch();
  }
}

/* compute stats using both 'voted' flag and any recorded selections */
function computeStats() {
  try {
    const totalVotesCast = elections.reduce((sum, el) => {
      // if results is array of positions with candidates that include votes - sum candidate votes
      if (Array.isArray(el.results) && el.results.length) {
        const posSum = el.results.reduce((ps, pos) => {
          if (pos && Array.isArray(pos.candidates)) {
            return ps + pos.candidates.reduce((cs, c) => cs + (Number(c.votes || 0)), 0);
          }
          return ps;
        }, 0);
        return sum + posSum;
      }
      // else treat a 'voted' flag as 1 vote cast from that user (for stats tile)
      return sum + (el.voted ? 1 : 0);
    }, 0);

    const electionsParticipated = elections.filter(e => e.voted || (e.userSelections && Object.keys(e.userSelections).length > 0)).length;
    const participationRate = elections.length ? Math.round((electionsParticipated / elections.length) * 100) : 0;

    if (totalVotesEl) totalVotesEl.textContent = totalVotesCast;
    if (electionsParticipatedEl) electionsParticipatedEl.textContent = electionsParticipated;
    if (participationRateEl) participationRateEl.textContent = participationRate + '%';
  } catch (err) {
    console.error('[history] computeStats error', err);
    if (totalVotesEl) totalVotesEl.textContent = '0';
    if (electionsParticipatedEl) electionsParticipatedEl.textContent = '0';
    if (participationRateEl) participationRateEl.textContent = '0%';
  }
}

/* render table (keeps your existing markup shape) */
function renderElectionTable() {
  try {
    electionTableBody.innerHTML = '';
    const start = (currentPage - 1) * pageSize;
    const pageItems = filteredElections.slice(start, start + pageSize);

    pageItems.forEach(election => {
      const tr = document.createElement('tr');
      const statusBadge = getStatusBadge(election.status);
      const voteStatus = election.voted
        ? `<span class="vote-status vote-submitted"><i class="fas fa-check"></i> Submitted</span>`
        : (election.status === 'upcoming' ? `<span class="vote-status vote-not-submitted"><i class="fas fa-clock"></i> Not Started</span>` : `<span class="vote-status vote-not-submitted"><i class="fas fa-times"></i> Not Voted</span>`);
      const actionButtons = getActionButtons(election);

      tr.innerHTML = `
        <td>
          <div class="election-name">${escapeHTML(election.name)}</div>
          <div class="election-details">${escapeHTML(election.raw?.details || '')}</div>
        </td>
        <td>${escapeHTML(election.date || '')}</td>
        <td>${escapeHTML(election.organization || '')}</td>
        <td>${statusBadge}</td>
        <td>${voteStatus}</td>
        <td>${actionButtons}</td>
      `;
      electionTableBody.appendChild(tr);
    });

    paginationStart.textContent = filteredElections.length === 0 ? 0 : start + 1;
    paginationEnd.textContent = Math.min(start + pageItems.length, filteredElections.length);
    paginationTotal.textContent = filteredElections.length;
  } catch (err) {
    console.error('[history] renderElectionTable error', err);
    electionTableBody.innerHTML = `<tr><td colspan="6" style="color:#e74c3c">An error occurred rendering the table.</td></tr>`;
  }
}

/* initialize on DOM ready */
document.addEventListener('DOMContentLoaded', () => {
  setupEventListeners();
  // Use absolute path + credentials include to ensure session cookie is sent
  fetchElections(window.location.origin + '/eBotoni/eBotoni/student/get_voting_history.php');
});

function getStatusBadge(status) {
  if (String(status).toLowerCase() === 'completed') return `<span class="status-badge status-completed"><i class="fas fa-check-circle"></i> Completed</span>`;
  if (String(status).toLowerCase() === 'ongoing' || String(status).toLowerCase() === 'active') return `<span class="status-badge status-ongoing"><i class="fas fa-clock"></i> Ongoing</span>`;
  return `<span class="status-badge status-upcoming"><i class="fas fa-calendar"></i> Upcoming</span>`;
}

function getActionButtons(election) {
  if (String(election.status).toLowerCase() === 'completed') {
    return `<div class="action-buttons">
      <button class="btn-outline view-details" data-id="${escapeHTML(String(election.id))}"><i class="fas fa-eye"></i> View</button>
      <button class="btn-outline view-results" data-id="${escapeHTML(String(election.id))}"><i class="fas fa-chart-bar"></i> Results</button>
    </div>`;
  } else if (String(election.status).toLowerCase() === 'ongoing' || String(election.status).toLowerCase() === 'active') {
    return election.voted
      ? `<div class="action-buttons">
           <button class="btn-outline view-details" data-id="${escapeHTML(String(election.id))}"><i class="fas fa-eye"></i> View Ballot</button>
           <span class="btn-disabled"><i class="fas fa-chart-bar"></i> Results</span>
         </div>`
      : `<div class="action-buttons">
           <button class="btn-outline vote-now" data-id="${escapeHTML(String(election.id))}"><i class="fas fa-vote-yea"></i> Vote Now</button>
           <span class="btn-disabled"><i class="fas fa-chart-bar"></i> Results</span>
         </div>`;
  } else {
    return `<div class="action-buttons"><span class="btn-disabled"><i class="fas fa-eye"></i> View</span><span class="btn-disabled"><i class="fas fa-chart-bar"></i> Results</span></div>`;
  }
}

function setupEventListeners() {
  electionTableBody.addEventListener('click', async (ev) => {
    const btn = ev.target.closest('button');
    if (!btn) return;
    const id = Number(btn.getAttribute('data-id'));
    const election = elections.find(e => Number(e.id) === Number(id));
    if (!election) return;
    if (btn.classList.contains('view-details')) showElectionDetails(election);
    else if (btn.classList.contains('view-results')) await onClickShowResults(election);
    else if (btn.classList.contains('vote-now')) handleVoteNow(election);
  });

  closeDetailsModal.addEventListener('click', () => hideModal(electionDetailsModal));
  closeResultsModal.addEventListener('click', () => hideModal(electionResultsModal));

  window.addEventListener('click', (e) => {
    if (e.target === electionDetailsModal) hideModal(electionDetailsModal);
    if (e.target === electionResultsModal) hideModal(electionResultsModal);
  });

  window.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') { hideModal(electionDetailsModal); hideModal(electionResultsModal); }
    if (e.key === 'ArrowRight') nextPage();
    if (e.key === 'ArrowLeft') prevPage();
  });

  applyFiltersBtn.addEventListener('click', () => { currentPage = 1; applyFiltersAndSearch(); });
  searchInput.addEventListener('input', () => { currentPage = 1; applyFiltersAndSearch(); });
}

function applyFiltersAndSearch() {
  const q = (searchInput.value || '').trim().toLowerCase();
  const yr = yearFilter.value;
  const et = electionFilter.value;
  const st = statusFilter.value;

  filteredElections = elections.filter(e => {
    if (yr !== 'all' && !(e.date && e.date.includes(yr))) return false;

    if (et !== 'all') {
      const map = {
        'it-officers': ['it officers', 'it department', 'it'],
        'usg': ['usg', 'university student government', 'student government'],
        'soce': ['soce', 'student organization of computer engineering', 'computer engineering']
      };
      const keys = map[et] || [];
      const searchTarget = (String(e.organization || '') + ' ' + String(e.name || '')).toLowerCase();
      if (!keys.some(k => searchTarget.includes(k))) return false;
    }

    if (st !== 'all' && e.status !== st) return false;

    if (q) {
      const hay = (String(e.name || '') + ' ' + String(e.raw?.details || '') + ' ' + String(e.organization || '')).toLowerCase();
      if (!hay.includes(q)) return false;
    }

    return true;
  });

  const maxPage = Math.max(1, Math.ceil(filteredElections.length / pageSize));
  if (currentPage > maxPage) currentPage = maxPage;
  renderElectionTable();
}

function nextPage(){ const maxPage = Math.ceil(filteredElections.length/pageSize)||1; if(currentPage<maxPage){currentPage++; renderElectionTable();}}
function prevPage(){ if(currentPage>1){currentPage--; renderElectionTable();}}

function showElectionDetails(e) {
  modalElectionTitle.textContent = e.name || 'Election Details';
  let html = `
    <div class="election-detail">
      <h3>Election Information</h3>
      <p><strong>Organization:</strong> ${escapeHTML(e.organization || '')}</p>
      <p><strong>Date:</strong> ${escapeHTML(e.date || '')}</p>
      <p><strong>Status:</strong> ${capitalize(e.status)}</p>
      <p><strong>Your Vote:</strong> ${e.voted ? 'Submitted' : 'Not Submitted'}</p>
    </div>
    <div class="election-detail">
      <h3>Your Ballot Selections</h3>
  `;
  const sels = e.userSelections || {};
  const keys = Object.keys(sels);
  if (keys.length === 0) {
    html += '<p><em>No selections recorded.</em></p>';
  } else {
    keys.forEach(pos => {
      html += `<div style="padding:12px;background:#f8f9fa;border-radius:8px;margin:8px 0;border-left:6px solid #2ecc71;">
        <strong>${escapeHTML(pos)}:</strong>&nbsp; ${escapeHTML(sels[pos] || '')}
      </div>`;
    });
  }
  html += '</div>';
  if (e.voted) {
    html += `<div style="margin-top:18px;padding:18px;border-radius:8px;background:#eafaf0;border-left:6px solid #2ecc71;">
      <strong style="display:flex;align-items:center;"><i class="fas fa-check-circle" style="margin-right:8px;color:#2ecc71;"></i> You have already voted in this election</strong>
      <div style="color:#666;margin-top:6px;">Your vote was submitted during the election period.</div>
    </div>`;
  }
  modalElectionBody.innerHTML = html;
  electionDetailsModal.style.display = 'flex';
}

async function onClickShowResults(election){
  modalResultsTitle.textContent = `${election.name} - Results`;
  modalResultsBody.innerHTML = '<p>Loading results...</p>';
  electionResultsModal.style.display = 'flex';

  if (Array.isArray(election.results) && election.results.length) {
    renderResultsModalFromPositions(election.name, election.organization, election.results);
    return;
  }

  const idToUse = election.id || election.raw?.Election_ID || election.raw?.election_id || election.raw?.id;
  if (!idToUse) {
    modalResultsBody.innerHTML = '<p>Invalid election identifier — cannot fetch results.</p>';
    return;
  }

  const fetched = await fetchResultsForElection(idToUse);
  if (fetched.ok && fetched.data) {
    const payload = fetched.data;
    const positions = Array.isArray(payload.positions) ? payload.positions : (Array.isArray(payload.data?.positions) ? payload.data.positions : []);
    election.results = positions;
    renderResultsModalFromPositions(election.name, election.organization, positions);
    return;
  }

  modalResultsBody.innerHTML = '<p>No official results available (server did not return results). If you are the admin, check the server endpoint and Election_ID used.</p>';
}

function renderResultsModalFromPositions(electionName, organization, positions){
  if (!Array.isArray(positions) || positions.length === 0) {
    modalResultsBody.innerHTML = '<p>No results available.</p>';
    return;
  }

  let html = `
    <div style="margin-bottom:12px;">
      <strong>Election Results</strong>
      <div style="margin-top:8px;"><strong>Organization:</strong> ${escapeHTML(organization || '')}</div>
      <div style="margin-top:4px;"><strong>Election:</strong> ${escapeHTML(electionName || '')}</div>
    </div>
  `;

  positions.forEach(pos => {
    const posName = pos.position || pos.position_name || pos.Position_Name || 'Position';
    const candidates = Array.isArray(pos.candidates) ? pos.candidates : [];
    let winner = null;
    if (candidates.length) {
      winner = candidates.reduce((a,b) => (Number(a.votes||0) > Number(b.votes||0) ? a : b));
    }
    html += `<div style="border-top:1px solid #e0e0e0;padding-top:12px;margin-top:12px;">
      <div style="font-weight:700;margin-bottom:8px;">${escapeHTML(posName)}</div>`;
    if (candidates.length === 0) {
      html += `<div style="padding:8px;background:#f8f9fa;border-radius:6px;">No candidates found for this position.</div>`;
    } else {
      candidates.forEach(c => {
        const isWinner = winner && String(c.id) === String(winner.id);
        const votes = Number(c.votes || 0);
        const perc = (typeof c.percentage !== 'undefined') ? c.percentage : (typeof c.perc !== 'undefined' ? c.perc : 0);
        const party = c.party || c.PartyList || c.partyList || '';
        const name = c.name || c.FullName || c.Full_Name || 'Candidate';
        html += `
          <div style="display:flex;justify-content:space-between;align-items:center;padding:10px;border-radius:6px;background:${isWinner ? '#e9f9ee' : '#f7f9fb'};margin-bottom:8px;border-left:6px solid ${isWinner ? '#2ecc71' : 'transparent'};">
            <div>
              <div style="font-weight:600;">${escapeHTML(name)} ${isWinner ? '<span style="background:#28a745;color:#fff;padding:4px 8px;border-radius:999px;font-size:0.75rem;margin-left:8px;">WINNER</span>' : ''}</div>
              <div style="font-size:0.85rem;color:#7f8c8d;">${escapeHTML(party)}</div>
            </div>
            <div style="text-align:right;">
              <div style="font-weight:700;">${votes} votes</div>
              <div style="font-size:0.85rem;color:#7f8c8d;">${perc}%</div>
            </div>
          </div>
        `;
      });
    }
    html += `</div>`;
  });

  modalResultsBody.innerHTML = html;
}

function hideModal(m) { if (!m) return; m.style.display = 'none'; }
function handleVoteNow(e) { if (!confirm(`Proceed to vote in "${e.name}"?`)) return; location.href = `/eBotoni/eBotoni/student/vote.php?eid=${e.id}`; }

/* Initialize UI */
document.addEventListener('DOMContentLoaded', () => {
  setupEventListeners();
  // NOTE: Change the path below to the actual endpoint that returns the user's voting history.
  // Example: '/eBotoni/eBotoni/student/get_voting_history.php' or '../student/get_voting_history.php'
  // <-- ADJUST IF NEEDED:
  fetchElections('/eBotoni/eBotoni/student/get_voting_history.php');
});
</script>

<!-- Single robust student profile loader (replaces duplicates) -->
<script>
/*
  Robust student profile loader.
  - Change PROJECT_BASE and GET_STUDENT_API if your deployment differs.
  - Uses element IDs: sidebarAvatar, sidebarName, sidebarRole
*/
const PROJECT_BASE = '/eBotoni/eBotoni'; // <-- ADJUST IF NEEDED
const GET_STUDENT_API = window.location.origin + PROJECT_BASE + '/student/getStudent.php'; // <-- ADJUST IF NEEDED
const FALLBACK_AVATAR = window.location.origin + PROJECT_BASE + '/assests/default-avatar.jpg';

async function loadStudentProfileRobust() {
  const avatarEl = document.getElementById('sidebarAvatar');
  const nameEl = document.getElementById('sidebarName');
  const roleEl = document.getElementById('sidebarRole');

  function applyFallback() {
    try {
      if (avatarEl && avatarEl.tagName && avatarEl.tagName.toLowerCase() === 'img' && (!avatarEl.src || avatarEl.src.length === 0)) {
        avatarEl.src = FALLBACK_AVATAR;
      }
      if (nameEl && (!nameEl.textContent || nameEl.textContent.trim() === '')) nameEl.textContent = 'Student';
      if (roleEl && (!roleEl.textContent || roleEl.textContent.trim() === '')) roleEl.textContent = 'Student';
    } catch(e) { /* ignore */ }
  }

  try {
    const res = await fetch(GET_STUDENT_API, { cache: 'no-store', credentials: 'same-origin' });
    if (!res.ok) { console.warn('getStudent returned non-OK', res.status); applyFallback(); return; }

    const text = (await res.text()).trim();
    if (!text) { applyFallback(); return; }

    let payload;
    try { payload = JSON.parse(text); } catch (err) {
      console.warn('getStudent returned non-JSON; first 800 chars:', text.slice(0,800));
      applyFallback();
      return;
    }

    let s = payload.student ?? payload.data?.student ?? null;
    if (!s) {
      if (payload.Name || payload.FullName || payload.Student_ID || payload.email) s = payload;
      else {
        for (const v of Object.values(payload)) {
          if (v && typeof v === 'object' && (v.Name || v.FullName || v.Student_ID || v.email)) { s = v; break; }
        }
      }
    }

    if (!s) { applyFallback(); return; }

    const name = s.Name ?? s.name ?? s.FullName ?? s.fullname ?? s.name_display ?? '';
    const photo = s.profile_photo_url ?? s.profile_photo ?? s.Avatar ?? s.avatar ?? s.photo ?? '';
    const role = s.role ?? 'Student';

    let avatarUrl = FALLBACK_AVATAR;
    if (photo && String(photo).trim()) {
      const p = String(photo).trim();
      if (/^https?:\/\//i.test(p)) avatarUrl = p;
      else {
        // clean up relative path (strip leading ../ etc)
        const cleaned = p.replace(/^(\.\.\/)+/, '').replace(/^\/+/, '');
        avatarUrl = window.location.origin + PROJECT_BASE + '/' + cleaned;
      }
    }

    if (avatarEl && avatarEl.tagName && avatarEl.tagName.toLowerCase() === 'img') {
      avatarEl.src = avatarUrl;
      avatarEl.alt = (name || 'User') + ' avatar';
    }
    if (nameEl) nameEl.textContent = name || 'Student';
    if (roleEl) roleEl.textContent = role || 'Student';

    try {
      window.CURRENT_USER = { id: s.id ?? s.Student_ID ?? s.student_id ?? null, name, avatar: avatarUrl, email: s.email ?? s.Email ?? null };
    } catch(e){}
  } catch (err) {
    console.error('loadStudentProfileRobust error', err);
    applyFallback();
  }
}

document.addEventListener('DOMContentLoaded', loadStudentProfileRobust);
</script> 
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
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --card-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        body {
            background-color: var(--ustp-light-gray);
            color: var(--ustp-dark-gray);
            line-height: 1.6;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar - Consistent with Active Elections Page */
        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, var(--ustp-blue) 0%, var(--ustp-light-blue) 100%);
            color: var(--ustp-white);
            padding: 20px 0;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            overflow: hidden;
            z-index: 100;
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
            margin: 0 auto 15px auto;
            border: 4px solid var(--ustp-gold);
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: var(--ustp-white);
        }

        .logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .logoW h1 {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--ustp-white);
        }

        .nav-menu {
            flex: 1;
            width: 100%;
        }

        .nav-menu a {
            text-decoration: none;
            color: var(--ustp-white);
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 20px;
            transition: var(--transition);
            border-left: 4px solid transparent;
        }

        .nav-menu a:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-left: 4px solid var(--ustp-gold);
        }

        .nav-menu a.active {
            background-color: var(--ustp-gold);
            color: black;
            border-left: 4px solid var(--ustp-gold);
        }

        .nav-menu a i {
            width: 20px;
            text-align: center;
        }

        /* User Profile - Consistent with Active Elections Page */
        .user-profile {
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
            position: relative;
            cursor: pointer;
            transition: var(--transition);
        }

        .user-profile:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid var(--ustp-gold);
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
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
            background: rgba(0, 51, 102, 0.95);
            border: 1px solid var(--ustp-gold);
            border-radius: 8px;
            overflow: hidden;
            width: 150px;
            z-index: 10;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .user-dropdown a {
            display: block;
            padding: 10px 20px;
            color: var(--ustp-white);
            text-decoration: none;
            transition: var(--transition);
        }

        .user-dropdown a:hover {
            background: var(--ustp-gold);
            color: var(--ustp-blue);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 20px;
            margin-left: 250px;
            overflow-y: auto;
        }

        /* Header - Consistent with Active Elections Page */
        .header {
            background: linear-gradient(135deg, var(--ustp-blue) 0%, var(--ustp-light-blue) 100%);
            color: white;
            padding: 30px 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: var(--card-shadow);
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(30%, -30%);
        }

        .page-title h1 {
            font-size: 2.2rem;
            margin-bottom: 10px;
        }

        .page-title p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        /* Stats Cards - Improved Design */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--ustp-white);
            border-radius: 12px;
            padding: 25px;
            box-shadow: var(--card-shadow);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
        }

        .stat-card:nth-child(1)::before {
            background: var(--ustp-blue);
        }

        .stat-card:nth-child(2)::before {
            background: var(--success);
        }

        .stat-card:nth-child(3)::before {
            background: var(--warning);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--ustp-white);
        }

        .stat-icon.blue {
            background: linear-gradient(135deg, var(--ustp-blue) 0%, var(--ustp-light-blue) 100%);
        }

        .stat-icon.green {
            background: linear-gradient(135deg, var(--success) 0%, #20c997 100%);
        }

        .stat-icon.orange {
            background: linear-gradient(135deg, var(--warning) 0%, #ff922b 100%);
        }

        .stat-info h3 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--ustp-dark-gray);
        }

        .stat-info p {
            font-size: 0.9rem;
            color: #7f8c8d;
            font-weight: 500;
        }

        /* Filter Section - Improved Design */
        .filter-section {
            background: var(--ustp-white);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: var(--card-shadow);
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            font-weight: 600;
            color: var(--ustp-blue);
            font-size: 0.9rem;
        }

        .filter-select {
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: var(--ustp-white);
            font-size: 0.9rem;
            color: var(--ustp-dark-gray);
            transition: var(--transition);
            cursor: pointer;
        }

        .filter-select:focus {
            border-color: var(--ustp-light-blue);
            box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--ustp-blue) 0%, var(--ustp-light-blue) 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            height: fit-content;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #002244 0%, #0055aa 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        /* History Table - Improved Design */
        .history-table-container {
            background: var(--ustp-white);
            border-radius: 12px;
            padding: 25px;
            box-shadow: var(--card-shadow);
            margin-bottom: 30px;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .table-header h3 {
            color: var(--ustp-blue);
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .search-box {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--ustp-light-gray);
            padding: 10px 15px;
            border-radius: 8px;
            width: 300px;
            transition: var(--transition);
        }

        .search-box:focus-within {
            box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
        }

        .search-box input {
            border: none;
            background: transparent;
            width: 100%;
            outline: none;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: var(--ustp-blue);
            border-bottom: 2px solid #e9ecef;
        }

        .table td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            transition: var(--transition);
        }

        .table tr:hover td {
            background-color: rgba(0, 102, 204, 0.03);
        }

        .election-name {
            font-weight: 600;
            color: var(--ustp-blue);
        }

        .election-details {
            font-size: 0.85rem;
            color: #7f8c8d;
            margin-top: 4px;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .status-completed {
            background: rgba(40, 167, 69, 0.1);
            color: var(--success);
        }

        .status-ongoing {
            background: rgba(255, 193, 7, 0.1);
            color: var(--warning);
        }

        /* Clean Vote Status - All in one line */
        .vote-status {
            padding: 6px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
        }

        .vote-submitted {
            background: rgba(40, 167, 69, 0.1);
            color: var(--success);
        }

        .vote-not-submitted {
            background: rgba(255, 193, 7, 0.1);
            color: var(--warning);
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--ustp-blue);
            color: var(--ustp-blue);
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-outline:hover {
            background: var(--ustp-blue);
            color: white;
            transform: translateY(-1px);
        }

        .btn-disabled {
            background: transparent;
            border: 1px solid #bdc3c7;
            color: #bdc3c7;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            cursor: not-allowed;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }

        .pagination-info {
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        footer {
            text-align: center;
            margin-top: 40px;
            padding: 20px;
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        /* Modal Styles - Improved Design */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: white;
            border-radius: 12px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            padding: 20px;
            background: linear-gradient(135deg, var(--ustp-blue) 0%, var(--ustp-light-blue) 100%);
            color: white;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
        }

        .close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .close-btn:hover {
            transform: scale(1.2);
        }

        .modal-body {
            padding: 25px;
        }

        .election-detail {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .election-detail h3 {
            color: var(--ustp-blue);
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 2px solid var(--ustp-light-blue);
        }

        .candidate-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .candidate-card {
            background: var(--ustp-light-gray);
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            transition: var(--transition);
            border-left: 4px solid var(--ustp-blue);
        }

        .candidate-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .candidate-name {
            font-weight: 600;
            color: var(--ustp-blue);
            margin-bottom: 5px;
        }

        .candidate-position {
            font-size: 0.85rem;
            color: #7f8c8d;
        }

        .vote-confirmation {
            text-align: center;
            padding: 20px;
            background: rgba(40, 167, 69, 0.1);
            border-radius: 8px;
            margin-top: 20px;
            border-left: 4px solid var(--success);
        }

        /* Results Styles - Improved Design */
        .results-container {
            margin-top: 20px;
        }
        
        .position-results {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .position-title {
            color: var(--ustp-blue);
            font-weight: 600;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 2px solid var(--ustp-light-blue);
        }
        
        .candidate-result {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 6px;
            margin-bottom: 8px;
            border-left: 4px solid transparent;
            transition: var(--transition);
        }
        
        .candidate-result:hover {
            background: #e9ecef;
        }
        
        .candidate-result.winner {
            background: rgba(40, 167, 69, 0.1);
            border-left-color: var(--success);
        }
        
        .candidate-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .vote-count {
            font-weight: 600;
            color: var(--ustp-blue);
        }
        
        .winner-badge {
            background: var(--success);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            margin-left: 10px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }
            
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .nav-menu {
                display: flex;
                overflow-x: auto;
            }
            
            .nav-menu a {
                flex-shrink: 0;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .filter-section {
                flex-direction: column;
            }
            
            .filter-group {
                min-width: 100%;
            }
            
            .table-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .search-box {
                width: 100%;
            }
            
            .table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
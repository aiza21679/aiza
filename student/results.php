<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Results - USTP Jasaan</title>
 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

</head>
 <script>
    /* small helpers used by templates */
    function escapeHTML(s){ if (s === null || s === undefined) return ''; return String(s).replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'",'&#039;'); }
    function escapeAttr(s){ return escapeHTML(s); }
    function truncate(s, n=140){ if (!s) return ''; s=String(s); return s.length>n ? s.slice(0,n)+'…' : s; }

    /* single dropdown toggle + logout used by whole page (keep only one) */
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
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="logo-container">
            <div class="logo"><img src="../assests/suffage.png" alt="Logo"></div>
            <div class="logoW"></div>
        </div>

        <div class="nav-menu">
            <a href="dashboard.php"><i class="fas fa-home"></i><span>Dashboard</span></a>
            <a href="vote.php"><i class="fas fa-vote-yea"></i><span>Active Elections</span></a>
            <a href="votinghistory.php"><i class="fas fa-history"></i><span>Voting History</span></a>
            <a href="candidates.php"><i class="fas fa-user-friends"></i><span>Candidates</span></a>
            <a href="results.php" class="active"><i class="fas fa-chart-bar"></i><span>Results</span></a>
            <a href="settings.php"><i class="fas fa-cog"></i><span>Settings</span></a>
        </div>

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
                <h1>Election Results</h1>
                <p>Victory is not just in winning, but in the courage to stand and be chosen.</p>
            </div>
        </div>

        <div class="stats-container">
            <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-vote-yea"></i></div>
                <div class="stat-info"><h3 id="total-votes">0</h3><p>Total Votes Cast</p></div>
            </div>
            <div class="stat-card"><div class="stat-icon green"><i class="fas fa-users"></i></div>
                <div class="stat-info"><h3 id="voter-turnout">0%</h3><p>Voter Turnout</p></div>
            </div>
            <div class="stat-card"><div class="stat-icon orange"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info"><h3 id="completed-elections">0</h3><p>Elections Completed</p></div>
            </div>
            <div class="stat-card"><div class="stat-icon purple"><i class="fas fa-clock"></i></div>
                <div class="stat-info"><h3 id="live-elections">0</h3><p>Live Elections</p></div>
            </div>
        </div>

        <div class="filter-section">
            <div class="filter-group">
                <label for="election-filter">Election Type</label>
                <select id="election-filter" class="filter-select">
                    <option value="all">All Elections</option>
                    <option value="soce">SOCE Officers</option>
                    <option value="it">IT Department</option>
                    <option value="usg">USG Officers</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="status-filter">Status</label>
                <select id="status-filter" class="filter-select">
                    <option value="all">All Status</option>
                    <option value="completed">Completed</option>
                    <option value="ongoing">Live Results</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="year-filter">Academic Year</label>
                <select id="year-filter" class="filter-select">
                    <option value="all">All Years</option>
                    <option value="2025-2026">2025-2026</option>
                    <option value="2024-2025">2024-2025</option>
                </select>
            </div>
            <button class="btn-primary" id="apply-filters"><i class="fas fa-filter"></i> Apply Filters</button>
        </div>

        <div class="elections-container" id="elections-container"></div>

        <div class="empty-state" id="empty-state" style="display:none;">
            <div class="empty-icon"><i class="fas fa-chart-bar"></i></div>
            <h3>No Results Found</h3>
            <p>Try adjusting your filters to find election results.</p>
            <button class="btn-primary" onclick="resetFilters()"><i class="fas fa-refresh"></i> Reset Filters</button>
        </div>

        <footer><p>© 2025 eBOTO | USTP Jasaan | All Rights Reserved</p></footer>
    </main>

    <!-- Results Modal -->
    <div id="results-modal" class="modal" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modal-election-title">Election Results</h2>
                <button class="close-modal">&times;</button>
            </div>
            <div id="modal-results-body"></div>
        </div>
    </div>

<script>
/* Results page script — corrected stats (total votes, turnout, live elections) */

/* CONFIG */
const API_CANDIDATES = [
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
const DEFAULT_AVATAR = '../assests/default-avatar.jpg';

/* DOM refs */
const electionsContainer = document.getElementById('elections-container');
const resultsModal = document.getElementById('results-modal');
const modalElectionTitle = document.getElementById('modal-election-title');
const modalResultsBody = document.getElementById('modal-results-body');
const emptyStateEl = document.getElementById('empty-state');
const electionFilter = document.getElementById('election-filter');
const statusFilter = document.getElementById('status-filter');
const yearFilter = document.getElementById('year-filter');
const applyFiltersBtn = document.getElementById('apply-filters');

let elections = [];
let filteredElections = [];
let resolvedApiBase = null;

/* small helpers */
function escapeHtml(s){ if (s === null || s === undefined) return ''; return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
function capitalize(s){ if(!s) return ''; return s.charAt(0).toUpperCase()+s.slice(1); }

async function safeFetchJson(url){
  try{
    const r = await fetch(url, { cache: 'no-cache', credentials: 'same-origin' });
    const txt = await r.text().catch(()=>null);
    if(!txt) return null;
    if(txt.trim().startsWith('{') || txt.trim().startsWith('[')) return JSON.parse(txt);
    return null;
  }catch(e){ return null; }
}

async function fetchText(url){
  try{
    const r = await fetch(url, { cache:'no-store', credentials: 'same-origin' });
    const text = await r.text();
    return { ok: r.ok, status: r.status, text };
  }catch(e){ return { ok:false, status:0, text:null }; }
}

/* detect API base by trying endpoints */
async function detectApiBase(){
  for(const baseCandidate of API_CANDIDATES){
    let b = String(baseCandidate); if(!b.endsWith('/')) b += '/';
    try {
      const te = await fetchText(b + 'get_elections.php');
      if(te && te.text && (te.text.trim().startsWith('[') || te.text.trim().startsWith('{'))) {
        try { JSON.parse(te.text); console.log('detected base (get_elections):', b); return b; } catch(e){}
      }
      if(te && te.ok) { console.log('detected base (ok):', b); return b; }
    } catch(e){}
    try {
      const tr = await fetchText(b + 'get_results.php?election_id=1');
      if(tr && tr.text && (tr.text.trim().startsWith('{') || tr.text.trim().startsWith('['))) {
        try { JSON.parse(tr.text); console.log('detected base (get_results):', b); return b; } catch(e){}
      }
    } catch(e){}
  }
  const fb = ['./student/','./student%20org/','../student/','../student%20org/','./','../'];
  for(const f of fb){
    let b = f.endsWith('/') ? f : f + '/';
    try {
      const tr = await fetchText(b + 'get_results.php?election_id=1');
      if(tr && tr.text && (tr.text.trim().startsWith('{') || tr.text.trim().startsWith('['))) {
        try { JSON.parse(tr.text); console.log('detected base (fallback):', b); return b; } catch(e){}
      }
    } catch(e){}
  }
  console.warn('detectApiBase: none found');
  return null;
}

/* rendering election cards */
function createElectionCard(election){
  const card = document.createElement('div');
  card.className = 'election-card';

  const status = String(election.status || 'completed').toLowerCase();
  const statusClass = status === 'completed' ? 'status-completed' : (status === 'ongoing' ? 'status-ongoing' : '');
  const statusLabel = escapeHtml(capitalize(status || 'Completed'));

  const startText = escapeHtml(election.startDate || election.start_date || '');
  const endText   = escapeHtml(election.endDate   || election.end_date   || '');
  const totalVotesLabel = (typeof election.totalVotes === 'number') ? election.totalVotes.toLocaleString() : (typeof election.votesCast === 'number' ? election.votesCast : '0');
  const useId = (election.dbId && Number(election.dbId) > 0) ? String(election.dbId) : String(election.id || '');

  let cardCompleted = false;
  if(status === 'completed') cardCompleted = true;
  else if(Number(election.progress) === 100) cardCompleted = true;
  else if(election.endDate){
    const ts = Date.parse(election.endDate);
    if(!isNaN(ts) && ts < Date.now()) cardCompleted = true;
  }

  card.innerHTML = `
    <div class="election-header">
      <h3 class="election-title">${escapeHtml(election.title || election.name || 'Election')}</h3>
      <span class="election-status ${statusClass}">${statusLabel}</span>
    </div>

    <div class="election-details">
      <div class="detail-item">
        <div class="detail-icon"><i class="fas fa-calendar-alt"></i></div>
        <div class="detail-content">
          <h4>Start Date & Time</h4>
          <p>${startText || '—'}</p>
        </div>
      </div>

      <div class="detail-item">
        <div class="detail-icon"><i class="fas fa-calendar-check"></i></div>
        <div class="detail-content">
          <h4>End Date & Time</h4>
          <p>${endText || '—'}</p>
        </div>
      </div>

      <div class="progress-container" aria-hidden="true">
        <div class="progress-bar"><div class="progress" style="width:${Number(election.progress || 100)}%"></div></div>
        <p class="time-remaining">${escapeHtml(election.timeRemaining || '')}</p>
      </div>

      <div style="margin-top:12px;">
        ${cardCompleted
          ? `<div class="card-completed-label">Election completed</div>`
          : `<div style="margin-top:10px;color:#666;font-weight:600;">Total votes: ${totalVotesLabel}</div>`
        }
      </div>

      <div style="margin-top:12px;">
        <button class="view-results-btn" data-election-id="${escapeHtml(useId)}"><i class="fas fa-chart-bar"></i> View Results</button>
      </div>
    </div>
  `;
  return card;
}

function renderElections(){
  electionsContainer.innerHTML = '';
  if(!filteredElections || filteredElections.length === 0){
    if(emptyStateEl) emptyStateEl.style.display = 'block';
    return;
  }
  if(emptyStateEl) emptyStateEl.style.display = 'none';
  filteredElections.forEach(e => electionsContainer.appendChild(createElectionCard(e)));
}

/* build candidate HTML (modal) */
/* safe attribute escaper (useful for JSON-in-attr and src) */
function escapeAttr(s){ if (s === null || s === undefined) return ''; return String(s).replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

/* improved buildCandidateHtml: tries multiple avatar URLs and cycles on error like the candidate cards */
function buildCandidateHtml(candidate, posName, winnerId){
  const isWinner = String(candidate.id) === String(winnerId);
  // gather possible avatar URLs in preferred order
  const candidatesList = [];
  // prefer explicit avatar_url returned by get_results.php
  if(candidate.avatar_url && String(candidate.avatar_url).trim()){
    candidatesList.push(String(candidate.avatar_url).trim());
  }
  // fallback to avatar or avatar path fields
  if(candidate.avatar && String(candidate.avatar).trim()){
    candidatesList.push(String(candidate.avatar).trim());
  }
  if(candidate.Avatar && String(candidate.Avatar).trim()){
    candidatesList.push(String(candidate.Avatar).trim());
  }
  // if candidate gives only a filename, try combining with common path patterns
  const raw = (candidate.avatar || candidate.Avatar || candidate.avatar_url || '') + '';
  if(raw && !raw.includes('://') && raw.split('/').pop()){
    const fname = encodeURIComponent(raw.split('/').pop());
    // use the PROVIDED_DIR_HTTP pattern from your page if available, else try sensible fallbacks
    try {
      if(typeof PROVIDED_DIR_HTTP !== 'undefined' && PROVIDED_DIR_HTTP) candidatesList.push(PROVIDED_DIR_HTTP + fname);
    } catch(e){}
    // other reasonable guesses
    candidatesList.push('../student%20org/uploads/candidates/' + fname);
    candidatesList.push('/eBotoni/eBotoni/student%20org/uploads/candidates/' + fname);
    candidatesList.push('./uploads/candidates/' + fname);
  }

  // always end with the DEFAULT_AVATAR
  candidatesList.push(DEFAULT_AVATAR);

  // de-duplicate while preserving order
  const avatarCandidates = Array.from(new Set(candidatesList.filter(Boolean)));

  const name = escapeHtml(candidate.name || candidate.FullName || candidate.Fullname || 'Candidate');
  const party = escapeHtml(candidate.party || '');
  const votes = Number(candidate.votes || 0);
  const pct = Number(candidate.percentage || candidate.perc || 0);

  // JSON.stringify for data attr, escaped
  const candsJSON = escapeAttr(JSON.stringify(avatarCandidates));

  return `
    <div class="candidate-item ${isWinner ? 'winner':''}">
      <div class="candidate-avatar" style="width:72px;height:72px;border-radius:40px;overflow:hidden;border:2px solid #eee;flex:0 0 72px;">
        <img
          src="${escapeAttr(avatarCandidates[0])}"
          alt="${escapeAttr(name)}"
          data-cands='${candsJSON}'
          data-idx="0"
          style="width:100%;height:100%;object-fit:cover;display:block;"
          onerror="(function(img){ try{ if(img.__trying) { /* prevent infinite loop */ } const list = JSON.parse(img.dataset.cands||'[]'); let idx = Number(img.dataset.idx||0)+1; const next = list[idx];
          if(next){ img.dataset.idx = idx; img.src = next; } else { img.onerror=null; img.src='${escapeAttr(DEFAULT_AVATAR)}'; } }catch(e){ img.onerror=null; 
          img.src='${escapeAttr(DEFAULT_AVATAR)}'; } })(this)"
        />
      </div>

      <div class="candidate-info" style="flex:1;margin-left:12px;">
        <div class="candidate-name" style="font-weight:700;">${name}</div>
        <div class="candidate-position" style="color:#666;margin-top:4px;">${escapeHtml(posName || '')}</div>
        ${party ? `<div class="candidate-party" style="color:#0a66c2;margin-top:6px;">${party}</div>` : ''}
      </div>

      <div class="vote-stats" style="min-width:120px;text-align:right;">
        <div class="vote-number" style="font-weight:700;font-size:16px;">${votes}</div>
        <div class="vote-number" style="color:#666;">${pct}%</div>
        ${isWinner ? '<div class="winner-badge" style="margin-top:6px;background:#ffd54f;padding:4px 8px;border-radius:8px;display:inline-block;font-weight:700;">Winner</div>' : ''}
      </div>
    </div>
  `;
}

/* fetch results trying many paths */
async function fetchResultsWithFallback(electionId){
  const bases = Array.isArray(API_CANDIDATES) ? API_CANDIDATES : [];
  for(const cand of bases){
    let base = String(cand); if(!base.endsWith('/')) base += '/';
    const url = base + 'get_results.php?election_id=' + encodeURIComponent(electionId);
    try {
      const r = await fetchText(url);
      if(r && r.text && (r.text.trim().startsWith('{') || r.text.trim().startsWith('['))){
        try { const j = JSON.parse(r.text); resolvedApiBase = base; return j; } catch(e){}
      }
    } catch(e){}
  }
  const rels = ['./student/','./student%20org/','../student/','../student%20org/','./','../'];
  for(const r of rels){
    let base = r.endsWith('/') ? r : r + '/';
    const url = base + 'get_results.php?election_id=' + encodeURIComponent(electionId);
    try {
      const res = await fetchText(url);
      if(res && res.text && (res.text.trim().startsWith('{') || res.text.trim().startsWith('['))){
        try { const j = JSON.parse(res.text); resolvedApiBase = base; return j; } catch(e){}
      }
    } catch(e){}
  }
  return null;
}

/* open modal with results */
async function openResultsModal(electionId){
  modalElectionTitle.textContent = 'Loading results...';
  modalResultsBody.innerHTML = '<p>Loading results...</p>';
  if(resultsModal) resultsModal.style.display = 'flex';

  const election = elections.find(x => String(x.dbId) === String(electionId) || String(x.id) === String(electionId));
  let payload = election && election._resultsPayload ? election._resultsPayload : null;

  if(!payload && resolvedApiBase){
    payload = await safeFetchJson(resolvedApiBase + `get_results.php?election_id=${encodeURIComponent(electionId)}`);
  }
  if(!payload) payload = await fetchResultsWithFallback(electionId);

  if(!payload){
    modalElectionTitle.textContent = 'Election Results';
    modalResultsBody.innerHTML = '<p>No results available (failed to load).</p>';
    return;
  }

  modalElectionTitle.textContent = `${escapeHtml(election ? election.title : ('Election ' + electionId))} - Results`;

  if(!Array.isArray(payload.positions) || payload.positions.length === 0){
    modalResultsBody.innerHTML = '<p>No results available.</p>';
    return;
  }

  let html = '';
  payload.positions.forEach(pos => {
    let winnerId = null; let maxVotes = -1;
    if(Array.isArray(pos.candidates)){
      pos.candidates.forEach(c => { const v = Number(c.votes||0); if(v>maxVotes){ maxVotes = v; winnerId = c.id; }});
    }
    html += `<div class="position-section"><div class="position-header">${escapeHtml(pos.position || pos.position_name || pos.Position_Name || '')}</div><div class="candidate-list">`;
    if(Array.isArray(pos.candidates) && pos.candidates.length){
      pos.candidates.forEach(cand => { html += buildCandidateHtml(cand, pos.position || pos.position_name || '', winnerId); });
    } else {
      html += '<div class="candidate-item"><p>No candidates found.</p></div>';
    }
    html += '</div></div>';
  });

  modalResultsBody.innerHTML = html;
}


/* close modal */
function closeResultsModal(){ if(resultsModal) resultsModal.style.display = 'none'; }

/* FILTERS */
function applyFilters(filterToCompletedOnly = false){
  const electionValue = electionFilter.value;
  const statusValue = statusFilter.value;
  const yearValue = yearFilter.value;

  filteredElections = elections.filter(election => {
    let include = true;
    if(filterToCompletedOnly) include = (String(election.status||'').toLowerCase() === 'completed');
    if(electionValue !== 'all' && election.type !== electionValue) include = false;
    if(statusValue !== 'all'){
      const st = (election.status || '').toString().toLowerCase();
      if(statusValue === 'completed' && st !== 'completed') include = false;
      if(statusValue === 'ongoing' && !(st === 'ongoing' || st === 'active')) include = false;
    }
    if(yearValue !== 'all' && election.year !== yearValue) include = false;
    return include;
  });

  renderElections();
  updateStats();
}

/* fetch student voting history from resolved base or common paths */
async function fetchStudentVotingHistory(){
  const candidates = [];
  if(resolvedApiBase) candidates.push(resolvedApiBase + 'get_voting_history.php');
  candidates.push('./student/get_voting_history.php','../student/get_voting_history.php','student/get_voting_history.php','./get_voting_history.php');

  for(const url of candidates){
    if(!url) continue;
    try {
      const data = await safeFetchJson(url);
      if(!data) continue;
      if(Array.isArray(data)) return data;
      if(Array.isArray(data.data)) return data.data;
      if(Array.isArray(data.results)) return data.results;
      if(Array.isArray(data.votes)) return data.votes;
      if(typeof data === 'object' && Object.keys(data).length > 0 && ('Student_ID' in data || 'Vote_ID' in data || 'Timestamp' in data || 'Candidate_ID' in data)) return [data];
    } catch(e){}
  }
  return null;
}

/* UPDATED: updateStats uses ALL elections for totals and live counts */
async function updateStats(){
  // (A) Total Votes Cast by current student
  let studentHistory = await fetchStudentVotingHistory();
  const myCount = Array.isArray(studentHistory) ? studentHistory.length : 0;
  const totalVotesEl = document.getElementById('total-votes');
  if(totalVotesEl) totalVotesEl.textContent = myCount;

  // (B) Voter Turnout = total votes cast across elections / total eligible voters * 100
  let totalVotesAll = 0;
  let totalEligible = 0;
  elections.forEach(el => {
    if(el._resultsPayload && Array.isArray(el._resultsPayload.positions)){
      el._resultsPayload.positions.forEach(pos => {
        if(typeof pos.total_votes === 'number') totalVotesAll += Number(pos.total_votes);
        else if(Array.isArray(pos.candidates)) pos.candidates.forEach(c => totalVotesAll += Number(c.votes || 0));
      });
    } else if(typeof el.totalVotes === 'number' && el.totalVotes > 0){
      totalVotesAll += Number(el.totalVotes);
    } else if(typeof el.votesCast === 'number' && el.votesCast > 0){
      totalVotesAll += Number(el.votesCast);
    }
    const raw = el.raw || {};
    const elEligible = Number(raw.total_voters || raw.Total_Voters || raw.totalVoters || el.totalVoters || raw.eligible_voters || 0) || 0;
    totalEligible += elEligible;
  });

  if(totalEligible === 0){
    elections.forEach(el => {
      if(el._resultsPayload && Array.isArray(el._resultsPayload.positions)){
        const maxPosTotal = el._resultsPayload.positions.reduce((m,p) => Math.max(m, Number(p.total_votes||0)), 0);
        if(maxPosTotal > 0) totalEligible += maxPosTotal;
      }
    });
  }

  const voterTurnoutEl = document.getElementById('voter-turnout');
  if(totalEligible > 0){
    const pct = Math.round((totalVotesAll / totalEligible) * 100);
    if(voterTurnoutEl) voterTurnoutEl.textContent = `${Math.min(100,pct)}%`;
  } else {
    if(voterTurnoutEl) voterTurnoutEl.textContent = `—`;
  }

  const completedCount = elections.filter(e => String(e.status||'').toLowerCase() === 'completed').length;
  const liveCount = elections.filter(e => {
    const st = String(e.status||'').toLowerCase();
    return (st === 'active' || st === 'ongoing' || st === 'live');
  }).length;

  const completedEl = document.getElementById('completed-elections');
  const liveEl = document.getElementById('live-elections');
  if(completedEl) completedEl.textContent = completedCount;
  if(liveEl) liveEl.textContent = liveCount;
}

/* reset filters */
function resetFilters(){ electionFilter.value='all'; statusFilter.value='all'; yearFilter.value='all'; applyFilters(false); }

/* setup event listeners */
function setupEventListeners(){
  document.addEventListener('click', (e) => {
    const btn = e.target.closest && e.target.closest('.view-results-btn');
    if(btn){
      const electionId = btn.getAttribute('data-election-id');
      if(electionId) openResultsModal(electionId);
    }
  });
  const closeBtn = document.querySelector('.close-modal');
  if(closeBtn) closeBtn.addEventListener('click', closeResultsModal);
  if(resultsModal) resultsModal.addEventListener('click', (e)=>{ if(e.target === resultsModal) closeResultsModal(); });
  if(applyFiltersBtn) applyFiltersBtn.addEventListener('click', ()=> applyFilters(false));
  document.addEventListener('click', function(e){
    const profile = document.querySelector('.user-profile');
    const dropdown = document.getElementById('dropdown');
    if(profile && !profile.contains(e.target) && dropdown) dropdown.style.display = 'none';
  });
}

/* load elections & warm cache */
async function loadElectionsFromAPI(){
  resolvedApiBase = await detectApiBase();
  console.log('resolvedApiBase ->', resolvedApiBase);

  let list = null;
  if(resolvedApiBase){
    list = await safeFetchJson(resolvedApiBase + 'get_elections.php');
    if(list && !Array.isArray(list) && Array.isArray(list.results)) list = list.results;
    if(list && !Array.isArray(list) && Array.isArray(list.data)) list = list.data;
  }

  if(!Array.isArray(list)){
    console.warn('Could not load get_elections.php; using demo data.');
    // If you have a DEMO_ELECTIONS array uncomment/use it, otherwise set elections empty
    elections = [];
  } else {
    elections = list.map(e => ({
      dbId: Number(e.Election_ID ?? e.id ?? e.election_id ?? 0) || 0,
      id: String(e.Election_ID ?? e.id ?? e.election_id ?? '') || '',
      title: e.title || e.Title || e.name || e.Name || (e.name || 'Election'),
      status: (String(e.status || e.Status || e.state || '')).toLowerCase() || 'upcoming',
      startDate: e.startDate || e.start_date || e.Start_Date || '',
      endDate: e.endDate || e.end_date || e.End_Date || '',
      progress: e.progress || 0,
      timeRemaining: e.timeRemaining || '',
      type: e.type || e.Type || '',
      year: e.year || e.Year || '',
      totalVotes: typeof e.total_voters !== 'undefined' ? Number(e.total_voters) : (typeof e.totalVotes !== 'undefined' ? Number(e.totalVotes) : 0),
      raw: e
    }));

    if(resolvedApiBase){
      await Promise.all(elections.map(async (el) => {
        const idToUse = (el.dbId && Number(el.dbId) > 0) ? el.dbId : el.id;
        if(!idToUse) return;
        const payload = await safeFetchJson(resolvedApiBase + `get_results.php?election_id=${encodeURIComponent(idToUse)}`);
        if(!payload) return;
        let tot = 0;
        if(Array.isArray(payload.positions)){
          payload.positions.forEach(p => {
            if(typeof p.total_votes === 'number') tot += Number(p.total_votes);
            else if(Array.isArray(p.candidates)) p.candidates.forEach(c => tot += Number(c.votes || 0));
          });
        }
        el.totalVotes = tot;
        el._resultsPayload = payload;
        if(!el.progress && el.status === 'completed') el.progress = 100;
        if(el.status === 'active') el.status = 'ongoing';
      }));
    }
  }

  const statusOrder = { 'completed':0, 'ongoing':1, 'active':1, 'upcoming':2, '':2 };
  elections.sort((a,b) => {
    const sa = statusOrder[a.status] ?? 3;
    const sb = statusOrder[b.status] ?? 3;
    if(sa !== sb) return sa - sb;
    const ta = a.startDate ? Date.parse(a.startDate) || 0 : 0;
    const tb = b.startDate ? Date.parse(b.startDate) || 0 : 0;
    return tb - ta;
  });

  filteredElections = elections.filter(e => String(e.status||'').toLowerCase() === 'completed');
  renderElections();
  await updateStats();
}

/* init */
(function init(){
  setupEventListeners();
  loadElectionsFromAPI();
})();

</script>

<!-- Single robust profile loader: REPLACES duplicate loaders -->
<script>
/*
  Robust student profile loader (single copy).
  - Change PROJECT_BASE and GET_STUDENT_API if your deployment differs.
  - Uses existing element IDs: sidebarAvatar, sidebarName, sidebarRole
*/
const PROJECT_BASE = '/eBotoni/eBotoni'; // <-- adjust if your project root differs
const GET_STUDENT_API = window.location.origin + PROJECT_BASE + '/student/getStudent.php'; // <-- adjust if needed
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

// Run once on DOM ready
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
            --ustp-green: #28a745;
            --ustp-red: #dc3545;
            --ustp-orange: #fd7e14;
        }

        body {
            background-color: var(--ustp-light-gray);
            color: var(--ustp-dark-gray);
            line-height: 1.6;
            display: flex;
            min-height: 100vh;
        }
        /* stronger, high-contrast party text for cards/modal */
.party-name,
.candidate-party,
.candidate-modal-party {
  color: #1f2937;           /* dark slate for good contrast */
  font-weight: 600;         /* slightly bolder so it stands out */
  text-shadow: none;        /* ensure clear text */
}

/* If the party text sits on a pale/yellow background, you can add a subtle
   darker background on the badge to help readability: */
.candidate-party {
  display: inline-block;
  padding: 4px 8px;
  border-radius: 6px;
  background: rgba(0,0,0,0.04); /* subtle contrast; tweak as needed */
}

/* If another rule is overriding your color, force it (use sparingly) */
.party-name,
.candidate-party,
.candidate-modal-party {
  color: #1f2937 !important;
}
.candidate-party {
  color: #fff;
  background: #0b63d6;       /* solid noticeable blue */
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 0.9rem;
  font-weight: 700;
}


        /* Sidebar - Consistent with Dashboard */
        .sidebar {
            width: 260px;
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
            width: 250px;
            overflow: hidden;
            z-index: 100;
            transition: all 0.3s ease;
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
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
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
            transition: all 0.3s ease;
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

        .user-profile {
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s ease;
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
            transition: all 0.3s ease;
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
            transition: all 0.3s ease;
        }

        /* Header - Consistent with Dashboard */
        .header {
            background: linear-gradient(135deg, var(--ustp-blue) 0%, var(--ustp-light-blue) 100%);
            color: white;
            padding: 30px 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
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

        /* Stats Cards - Consistent with Dashboard */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--ustp-white);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
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
            background: linear-gradient(135deg, var(--ustp-green) 0%, #20c997 100%);
        }

        .stat-icon.orange {
            background: linear-gradient(135deg, var(--ustp-orange) 0%, #ff922b 100%);
        }

        .stat-icon.purple {
            background: linear-gradient(135deg, #6f42c1 0%, #9b59b6 100%);
        }

        .stat-info h3 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--ustp-dark-gray);
        }

        .stat-info p {
            font-size: 0.9rem;
            color: #7f8c8d;
        }

        /* Filter Section */
        .filter-section {
            background: var(--ustp-white);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex: 1;
            min-width: 180px;
        }

        .filter-group label {
            font-weight: 600;
            color: var(--ustp-blue);
            font-size: 0.85rem;
        }

        .filter-select {
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background: var(--ustp-white);
            font-size: 0.85rem;
            color: var(--ustp-dark-gray);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--ustp-blue) 0%, var(--ustp-light-blue) 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            height: fit-content;
            display: flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 10px rgba(0, 51, 102, 0.2);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #002244 0%, #0055aa 100%);
            box-shadow: 0 6px 15px rgba(0, 51, 102, 0.4);
            transform: translateY(-2px);
        }

        /* Elections Container */
        .elections-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .election-card {
            background: var(--ustp-white);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .election-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .election-header {
            padding: 20px;
            background: linear-gradient(135deg, var(--ustp-blue) 0%, var(--ustp-light-blue) 100%);
            color: var(--ustp-white);
            position: relative;
        }

        .election-title {
            font-size: 1.3rem;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .election-status {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 5px;
        }

        .status-completed {
            background-color: rgba(255, 255, 255, 0.2);
            color: var(--ustp-white);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .status-ongoing {
            background-color: rgba(255, 204, 0, 0.2);
            color: var(--ustp-gold);
        }

        .election-details {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .detail-item {
            display: flex;
            margin-bottom: 15px;
            align-items: flex-start;
        }

        .detail-icon {
            width: 24px;
            margin-right: 12px;
            color: var(--ustp-blue);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .detail-content h4 {
            font-size: 0.85rem;
            color: #7f8c8d;
            margin-bottom: 3px;
            font-weight: 500;
        }

        .detail-content p {
            font-weight: 600;
            color: var(--ustp-dark-gray);
            font-size: 0.9rem;
        }

        .progress-container {
            margin: 15px 0;
        }

        .progress-bar {
            height: 8px;
            background-color: #ecf0f1;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 8px;
        }

        .progress {
            height: 100%;
            background: linear-gradient(90deg, var(--ustp-blue) 0%, var(--ustp-light-blue) 100%);
            border-radius: 4px;
            width: 65%;
            transition: width 0.5s ease;
        }

        .time-remaining {
            font-size: 0.85rem;
            color: #7f8c8d;
            text-align: center;
        }

        .view-results-btn {
            display: block;
            width: 100%;
            padding: 12px;
            text-align: center;
            background: linear-gradient(135deg, var(--ustp-blue) 0%, var(--ustp-light-blue) 100%);
            color: var(--ustp-white);
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: auto;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .view-results-btn:hover {
            background: linear-gradient(135deg, #002244 0%, #0055aa 100%);
            box-shadow: 0 5px 15px rgba(0, 51, 102, 0.4);
            transform: translateY(-2px);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: white;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.2);
            position: relative;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }

        .modal-title {
            font-size: 1.5rem;
            color: var(--ustp-blue);
            margin: 0;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #7f8c8d;
            transition: color 0.3s ease;
        }

        .close-modal:hover {
            color: var(--ustp-red);
        }

        .position-section {
            margin-bottom: 25px;
            border: 1px solid #eee;
            border-radius: 8px;
            overflow: hidden;
        }

        .position-header {
            background: var(--ustp-light-gray);
            padding: 15px 20px;
            font-weight: 600;
            color: var(--ustp-blue);
            border-bottom: 1px solid #eee;
        }
.card-completed-label {
  width: 100%;
  text-align: center;
  
  color: #7c7f81ff;

}
        .candidate-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding: 15px;
        }

        .candidate-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .candidate-item.winner {
            border-color: var(--ustp-gold);
            background-color: rgba(255, 204, 0, 0.05);
        }

        .candidate-item:hover {
            border-color: var(--ustp-light-blue);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }

        .candidate-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 15px;
            border: 2px solid var(--ustp-gold);
        }

        .candidate-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .candidate-info {
            flex: 1;
        }

        .candidate-name {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 5px;
            color: var(--ustp-dark-gray);
        }

        .candidate-position {
            color: var(--ustp-blue);
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .candidate-party {
            display: inline-block;
            padding: 3px 8px;
            background: var(--ustp-light-blue);
            color: white;
            border-radius: 4px;
            font-size: 0.8rem;
            margin-bottom: 5px;
        }

        .vote-stats {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .vote-count {
            text-align: center;
        }

        .vote-number {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--ustp-blue);
        }

        .vote-label {
            font-size: 0.8rem;
            color: #7f8c8d;
        }

        .winner-badge {
            background: var(--ustp-gold);
            color: var(--ustp-blue);
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: 15px;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            background: var(--ustp-white);
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            display: none;
        }

        .empty-icon {
            font-size: 3rem;
            color: #bdc3c7;
            margin-bottom: 15px;
        }

        .empty-state h3 {
            color: var(--ustp-blue);
            margin-bottom: 8px;
            font-size: 1.2rem;
        }

        .empty-state p {
            color: #7f8c8d;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }

        footer {
            text-align: center;
            margin-top: 40px;
            padding: 20px;
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .elections-container {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            }
        }

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
            
            .user-profile {
                display: none;
            }
            
            .elections-container {
                grid-template-columns: 1fr;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>
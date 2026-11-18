<?php
// election-results.php  (complete patched file - NO CSS)
// NOTE: make sure there is NO OUTPUT before this <?php (no BOM)
session_start();

/* ---------------- Server-side logo resolver (mirrors dashboard logic) ---------------- */
function resolve_logo_url(string $dbPath = ''): string {
    $projectPrefix = '/eBotoni/eBotoni/'; // adjust if your project folder differs
    $default = $projectPrefix . 'assests/default-logo.jpg';

    $p = trim((string)$dbPath);
    if ($p === '') return $default;

    // absolute http(s)
    if (preg_match('#^https?://#i', $p)) return $p;

    // already root-relative (starts with /) -> return as-is
    if ($p[0] === '/' || $p[0] === '\\') return $p;

    // sanitize: remove leading ./ or ../
    $clean = preg_replace('#^(\.\/|(\.\.\/)+)#', '', $p);
    $clean = ltrim($clean, '/\\');

    // if DB value already contains the project folder, keep only until after it
    if (strpos($clean, 'eBotoni/eBotoni/') !== false) {
        return '/' . ltrim($clean, '/\\');
    }

    // otherwise treat value as relative to project root: prefix project folder
    return rtrim($projectPrefix, '/') . '/' . ltrim($clean, '/\\');
}

/* ---------------- Load profile from DB (try multiple session keys) ---------------- */
$ORG_ID = isset($_SESSION['Org_ID']) ? (int)$_SESSION['Org_ID'] : (isset($_SESSION['org_id']) ? (int)$_SESSION['org_id'] : null);
$ORG_NAME = isset($_SESSION['Org_Name']) ? $_SESSION['Org_Name'] : (isset($_SESSION['org_name']) ? $_SESSION['org_name'] : 'SOCE');

$profile = [
    'name' => 'Organization User',
    'position' => 'Organization User',
    'email' => '',
    'avatar' => '/eBotoni/eBotoni/assests/default-logo.jpg'
];

if ($ORG_ID) {
    try {
        $pdo = new PDO(
            'mysql:host=127.0.0.1;dbname=eboto_db;charset=utf8mb4',
            'root',
            '',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );

        $stmt = $pdo->prepare("SELECT Org_Name, Email, Logo_Url FROM student_organization WHERE Org_ID = ?");
        $stmt->execute([$ORG_ID]);
        $row = $stmt->fetch();

        if ($row) {
            $profile['name'] = !empty($row['Org_Name']) ? $row['Org_Name'] : $profile['name'];
            $profile['email'] = !empty($row['Email']) ? $row['Email'] : $profile['email'];
            $profile['position'] = !empty($row['Org_Name']) ? 'Organization User' : $profile['position'];

            $profile['avatar'] = resolve_logo_url($row['Logo_Url'] ?? '');
        }
    } catch (Exception $e) {
        error_log("Profile load error (election-results): " . $e->getMessage());
        // keep defaults
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Results - USTP Jasaan</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
       <!-- Expose server profile & org id to the frontend -->
    <script>
      window.SERVER_PROFILE = <?php echo json_encode($profile, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE); ?>;
      window.CURRENT_ORG_ID = <?php echo json_encode($ORG_ID, JSON_UNESCAPED_SLASHES); ?>;
      window.CURRENT_ORG_NAME = <?php echo json_encode($ORG_NAME, JSON_UNESCAPED_SLASHES); ?>;
      console.debug('SERVER_PROFILE (election-results):', window.SERVER_PROFILE);
    </script>
</head>
<body>
   <!-- SIDEBAR - consistent markup with other pages -->
  <nav class="sidebar" id="sidebar">
    <div class="logo-container">
      <div class="logo"><img src="../assests/suffage.png" alt="Logo" id="appLogo"></div>
    </div>


    <ul class="nav-menu">
      <li class="nav-item"><i class="fas fa-home"></i><a href="org-dashboard.php">Dashboard</a></li>
      <li class="nav-item"><i class="fas fa-user-graduate"></i><a href="org-voters.php">Organization Voters</a></li>
      <li class="nav-item"><i class="fas fa-user-tie"></i><a href="manage-candidates.php">Candidates</a></li>
      <li class="nav-item"><i class="fas fa-user-tag"></i><a href="manage-elections.php">Election Monitoring</a></li>
      <li class="nav-item active"><i class="fas fa-check-circle"></i><a href="election-results.php">Election Results</a></li>
      <li class="nav-item"><i class="fas fa-cog"></i><a href="settings.php">Settings</a></li>
    </ul>

    <div class="user-profile" id="userProfile" onclick="toggleDropdown && toggleDropdown()">
        <div class="user-avatar">
            <!-- use PHP-resolved avatar as initial src; client JS will also normalize if needed -->
            <img id="sidebarAvatar"
                 src="<?php echo htmlspecialchars($profile['avatar'], ENT_QUOTES); ?>"
                 alt="Org Avatar"
                 onerror="this.onerror=null; this.src='/eBotoni/eBotoni/assests/default-logo.jpg';">
        </div>
        <div class="user-info">
            <h4 id="sidebarName"><?php echo htmlspecialchars($profile['name'], ENT_QUOTES); ?></h4>
            <p id="sidebarRole"><?php echo htmlspecialchars($profile['position'], ENT_QUOTES); ?></p>
        </div>
        <div class="user-dropdown" id="dropdown" style="display:none;">
            <a href="../login.html" id="logoutBtn">Logout</a>
        </div>
    </div>

  </nav>

  <!-- Main content (kept your structure) -->
  <main class="main-content">
    <div class="header">
      <div class="page-title">
        <h1>Party List Election Results</h1>
        <p id="pageSubtitle">View and analyze party list election results with detailed insights</p>
      </div>
    </div>

    <div class="access-notice" style="background: linear-gradient(90deg, #fff3cd 0%, #ffeaa7 100%); border: 1px solid #ffeaa7; border-radius: 12px; padding: 18px 25px; margin-bottom: 30px; display: flex; align-items: center; gap: 15px; box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);">
        <i class="fas fa-info-circle" style="color: #856404; font-size: 1.5rem;"></i>
        <p style="color: #856404; margin: 0; font-size: 1rem; font-weight: 500;"><strong>Stakeholder Access:</strong> You have limited access to SOCE organization data only. Some features are restricted.</p>
    </div>

    <!-- Stats -->
    <div class="stats-container">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-vote-yea"></i></div>
            <div class="stat-info">
                <h3 id="total-votes">187</h3>
                <p>Total Votes Cast</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-users"></i></div>
            <div class="stat-info">
                <h3 id="voter-turnout">76%</h3>
                <p>Voter Turnout</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange"><i class="fas fa-user-tie"></i></div>
            <div class="stat-info">
                <h3 id="total-candidates">20</h3>
                <p>Candidates</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple"><i class="fas fa-flag"></i></div>
            <div class="stat-info">
                <h3 id="party-lists">4</h3>
                <p>Party Lists</p>
            </div>
        </div>
    </div>

    <!-- Filters -->
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

<!-- (Your long script follows — unchanged in behavior; I only added a small client profile applier near top) -->
<script>
/* ---------- Apply server profile to DOM (client-side normalization) ---------- */
(function applyServerProfileToDOM(){
    try {
        const p = window.SERVER_PROFILE || {};
        const avatarEl = document.getElementById('sidebarAvatar');
        const appLogoEl = document.getElementById('appLogo');
        const nameEl = document.getElementById('sidebarName');
        const roleEl = document.getElementById('sidebarRole');
        const subtitle = document.getElementById('pageSubtitle');

        // helper: convert root-relative or project-relative to absolute URL
        function normalizeUrl(raw){
            if(!raw) return '/eBotoni/eBotoni/assests/default-avatar.jpg';
            raw = String(raw).trim();
            if(/^https?:\/\//i.test(raw)) return raw;
            if(raw.startsWith('/')) return window.location.origin + raw;
            // if it already contains eBotoni/eBotoni path return origin + /path
            if(raw.indexOf('eBotoni/eBotoni') !== -1) return window.location.origin + '/' + raw.replace(/^\/+/,'');
            return window.location.origin + '/' + raw.replace(/^\/+/,'');
        }
if (logoEl) {
  logoEl.src = '/eBotoni/eBotoni/assests/suffage.png';
  logoEl.onerror = () => { logoEl.src = '/eBotoni/eBotoni/assests/suffage.png'; };
}
        if (appLogoEl) {
            const raw = p.avatar || '';
            if(raw) appLogoEl.src = normalizeUrl(raw);
            appLogoEl.onerror = function(){ this.onerror=null; this.src='../assests/suffage.png'; };
        }
        if (nameEl && p.name) nameEl.textContent = p.name;
        if (roleEl && p.position) roleEl.textContent = p.position;
        if (subtitle) subtitle.textContent = `View and analyze party list election results for ${p.name || window.CURRENT_ORG_NAME}.`;

        // profile area
        const profileNameDisplay = document.getElementById('profileNameDisplay');
        if (profileNameDisplay) profileNameDisplay.textContent = p.name || 'Organization User';
        const posDisplay = document.getElementById('profilePositionDisplay');
        if (posDisplay) posDisplay.textContent = `${p.position || 'Organization User'} • ${p.email || ''}`;

        const profileAvatarDisplay = document.getElementById('profileAvatarDisplay');
        if (profileAvatarDisplay) profileAvatarDisplay.src = normalizeUrl(p.avatar || '');
    } catch (err) {
        console.warn('applyServerProfileToDOM error', err);
    }
})();

/* ---------- start of your election/results script (unchanged logic) ---------- */

/* Full patched combined elections + turnout + vote-casting script
   - Tries multiple candidate bases (handles "student" and "student org" paths)
   - Robust get_results fallback to avoid 404 problems and choose the working base
   - Removes party list totals and 'Cast Vote' button from the card list
   - Locks background scroll while modal open and preserves UI on failures
   - NEW: when election is detected as completed, attempt to fetch final results,
     and if unavailable compute final results locally from election.votes and partyCandidates
*/

const API_CANDIDATE_BASES = [
  "http://localhost/eBotoni/eBotoni/student/",
  "/eBotoni/eBotoni/student/",
  "/eBotoni/eBotoni/student%20org/",
  "../student/",
  "../student%20org/",
  "./student/",
  "./student%20org/"
];

const electionsContainer = document.getElementById('elections-container');
let resultsModal = document.getElementById('results-modal') || document.getElementById('resultsModal') || null;
const modalElectionTitle = document.getElementById('modal-election-title');
const modalResultsBody = document.getElementById('modal-results-body');
const emptyState = document.getElementById('empty-state');

const electionFilter = document.getElementById('election-filter');
const statusFilter = document.getElementById('status-filter');
const yearFilter = document.getElementById('year-filter');
const applyFiltersBtn = document.getElementById('apply-filters');

const totalVotesEl = document.getElementById("total-votes");
const voterTurnoutEl = document.getElementById("voter-turnout");
const totalCandidatesEl = document.getElementById("total-candidates");
const partyListsEl = document.getElementById("party-lists");

/* Voting modal builder (kept but Cast Vote removed from cards) */
let votingModal = document.getElementById('voting-modal');
if (!votingModal) {
  votingModal = document.createElement('div');
  votingModal.id = 'voting-modal';
  votingModal.className = 'modal';
  votingModal.style.display = 'none';
  votingModal.innerHTML = `
    <div class="modal-inner">
      <button class="close-modal" data-close="voting-modal">×</button>
      <h3 id="voting-modal-title">Vote</h3>
      <form id="voting-form"></form>
      <div style="margin-top:10px"><button id="submit-vote-btn" class="btn">Submit Vote</button></div>
    </div>`;
  document.body.appendChild(votingModal);
}
const votingForm = document.getElementById('voting-form') || votingModal.querySelector('#voting-form');
const submitVoteBtn = document.getElementById('submit-vote-btn') || votingModal.querySelector('#submit-vote-btn');

let resolvedApiBase = null; // chosen working base (keeps trailing slash)
let elections = [];
let filteredElections = [];
let partyCandidates = {};
let currentVotingElectionId = null;

/* ---------- helpers ---------- */
function safeTextPreview(s, n = 600){ if (!s) return ''; return (s.length > n) ? s.slice(0,n) + ' … (truncated)' : s; }
function isLikelyJson(text){ if (!text || typeof text !== 'string') return false; const t = text.trim(); return (t.startsWith('{') || t.startsWith('[')); }
function tryParseJson(text){ try { return JSON.parse(text); } catch(e) { return null; } }
function escapeHtml(s){ if (s === null || s === undefined) return ''; return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
async function fetchText(url){ try { const res = await fetch(url, { cache: "no-cache" }); const text = await res.text(); return { ok: res.ok, status: res.status, statusText: res.statusText, headers: res.headers, text }; } catch (err) { return { ok:false, error:err, text:null }; } }

/* ---------- detect base for get_elections/get_candidates (best-effort) ---------- */
async function detectApiBase(){
  for (const cand of API_CANDIDATE_BASES){
    let v = cand;
    if (!v.endsWith('/')) v = v + '/';
    const url = v + 'get_elections.php';
    try {
      const r = await fetchText(url);
      console.log('[detectApiBase] tried', url, '=>', (r && r.status) ? r.status : 'ERR');
      if (r && r.text && isLikelyJson(r.text)) {
        console.log('[detectApiBase] chosen (json) base', v);
        return v;
      }
      if (r && r.ok) {
        console.log('[detectApiBase] chosen (ok) base', v);
        return v;
      }
    } catch(e){ /* ignore */ }
  }
  // fallback to first candidate normalized
  const fallback = API_CANDIDATE_BASES[0].endsWith('/') ? API_CANDIDATE_BASES[0] : API_CANDIDATE_BASES[0] + '/';
  console.warn('[detectApiBase] fallback to', fallback);
  return fallback;
}

/* ---------- tolerant getJSON used for elections/candidates ---------- */
async function getJSON(path){
  if (!resolvedApiBase) resolvedApiBase = await detectApiBase();
  if (!resolvedApiBase.endsWith('/')) resolvedApiBase += '/';
  const cleanPath = path.replace(/^\//,'');
  const url = resolvedApiBase + cleanPath;
  console.log('[getJSON] fetching', url);
  try {
    const r = await fetchText(url);
    if (!r) {
      console.warn('[getJSON] no response from', url);
      return null;
    }
    if (!r.ok) {
      if (r.text && isLikelyJson(r.text)) return tryParseJson(r.text);
      console.warn('[getJSON] fetch not ok for', url, r.status, r.statusText);
      return null;
    }
    if (!r.text || !isLikelyJson(r.text)) {
      console.warn('[getJSON] response not JSON at', url, 'preview:', safeTextPreview(r.text));
      return null;
    }
    return tryParseJson(r.text);
  } catch (err) {
    console.error('[getJSON] unexpected error fetching', url, err);
    return null;
  }
}

/* ---------- fetch candidates ---------- */
async function fetchCandidates(){
  if (!resolvedApiBase) resolvedApiBase = await detectApiBase();
  if (!resolvedApiBase.endsWith('/')) resolvedApiBase += '/';
  const url = resolvedApiBase + 'get_candidates.php';
  console.log('[fetchCandidates] url:', url);
  try {
    const res = await fetch(url, { cache: 'no-store' });
    if (!res.ok) {
      const text = await res.text().catch(()=>null);
      if (isLikelyJson(text)) { const j = tryParseJson(text)||[]; buildPartyCandidatesFromRows(Array.isArray(j)?j:(j.data||[])); return; }
      console.warn('[fetchCandidates] get_candidates returned non-ok', res.status);
      return;
    }
    const data = await res.json().catch(()=>null);
    const rows = Array.isArray(data) ? data : (data && data.data ? data.data : []);
    buildPartyCandidatesFromRows(rows);
  } catch(e){ console.error('[fetchCandidates] failed', e); }
}
function buildPartyCandidatesFromRows(rows){
  partyCandidates = {};
  if (!Array.isArray(rows)) return;
  rows.forEach(r=>{
    const candidate = {
      id: r.id ?? r.Candidate_ID ?? r.candidate_id ?? String(r.id || ''),
      name: r.name ?? r.FullName ?? r.Name ?? 'Candidate',
      position: r.position ?? r.Position ?? r.position_name ?? 'President',
      avatar_url: r.avatar ?? r.avatar_url ?? '../assests/default-avatar.jpg',
      party: r.party ?? r.partyList ?? r.Party ?? 'Independent'
    };
    if (!partyCandidates[candidate.party]) partyCandidates[candidate.party] = [];
    partyCandidates[candidate.party].push(candidate);
  });
}

/* ---------- compute local results if server unavailable ---------- */
function computeLocalResults(election){
  const allCandidates = Object.values(partyCandidates).flat?.() || [];
  const candidateById = {};
  allCandidates.forEach(c => { candidateById[String(c.id)] = c; });

  const positions = Array.isArray(election.positions) ? election.positions : ['President'];

  const positionsResult = positions.map(pos => {
    const candidatesForPos = allCandidates.filter(c => String(c.position) === String(pos) || String(c.position).toLowerCase().includes(String(pos).toLowerCase()));
    const counts = {};
    candidatesForPos.forEach(c => counts[String(c.id)] = 0);

    const votesArr = Array.isArray(election.votes) ? election.votes : [];
    votesArr.forEach(vote => {
      const sel = vote.selections && vote.selections[pos];
      if (!sel) return;
      const sid = String(sel);
      if (counts[sid] === undefined) {
        counts[sid] = 0;
      }
      counts[sid] = (counts[sid] || 0) + 1;
    });

    const totalVotes = Object.values(counts).reduce((a,b)=>a+(Number(b)||0),0);

    const cArray = Object.keys(counts).map(cid => {
      const cand = candidateById[cid] || { id: cid, name: 'Candidate', party: 'Independent', avatar: '' };
      const votes = counts[cid] || 0;
      const percentage = totalVotes > 0 ? Math.round((votes/totalVotes) * 100) : 0;
      return { id: cid, name: cand.name || cand.FullName || 'Candidate', party: cand.party || '', avatar: cand.avatar_url || cand.avatar || '', votes, percentage };
    });

    return { position: pos, total_votes: totalVotes, candidates: cArray };
  });

  const partyTotals = {};
  positionsResult.forEach(pr => {
    pr.candidates.forEach(c => {
      const p = c.party || 'Independent';
      partyTotals[p] = (partyTotals[p] || 0) + (Number(c.votes) || 0);
    });
  });
  const party_totals_arr = Object.keys(partyTotals).map(p => ({ party: p, votes: partyTotals[p] }));

  const results = {
    status: 'ok',
    election: { id: election.id, title: election.title },
    positions: positionsResult,
    party_totals: party_totals_arr
  };
  election.results = results;
  return results;
}

/* ---------- load elections (preserve UI on transient failures) ---------- */
async function loadElections(){
  const prevElections = Array.isArray(elections) ? elections.slice() : [];
  const data = await getJSON('get_elections.php');

  if (!Array.isArray(data) && (!data || !data.data)) {
    if (prevElections.length > 0) {
      console.warn('[loadElections] get_elections failed — keeping previous elections state');
      updateStats();
      return;
    } else {
      elections = [];
      filteredElections = [];
      renderElections();
      updateStats();
      return;
    }
  }

  const rows = Array.isArray(data) ? data : (data.data || []);
  elections = rows.map(e=>{
    const dbId = Number(e.Election_ID ?? e.election_id ?? e.id ?? e.ID ?? 0) || 0;
    const id = String(dbId || e.id || e.Election_ID || e.election_id || '').trim();
    const start = e.Start_Date ?? e.start_date ?? e.startDate ?? e.start ?? '';
    const end = e.End_Date ?? e.end_date ?? e.endDate ?? e.end ?? '';
    const totalVoters = Number(e.total_voters ?? e.Total_Voters ?? e.totalVoters ?? 0) || 0;
    const votesCast = Number(e.votesCast ?? e.votes_cast ?? e.votes ?? 0) || 0;
    const partyLists = e.partyLists ?? e.party_lists ?? e.partyList ?? e.partyListJson ?? [];
    const positions = Array.isArray(e.positions) ? e.positions : (e.positions ? String(e.positions).split(',').map(s=>s.trim()).filter(Boolean) : ['President']);
    const status = String(e.Status ?? e.status ?? '').toLowerCase() || ((start && end) ? (new Date() < new Date(start) ? 'upcoming' : (new Date() <= new Date(end) ? 'active' : 'completed')) : 'upcoming');
    const votes = Array.isArray(e.votes) ? e.votes : [];
    return {
      dbId, id,
      title: e.Title ?? e.title ?? e.name ?? e.Name ?? 'Untitled Election',
      startDate: start, endDate: end, totalVoters,
      votesCast: votesCast || (Array.isArray(votes) ? votes.length : 0),
      votes: Array.isArray(votes) ? votes : [],
      partyLists: Array.isArray(partyLists) ? partyLists : (typeof partyLists === 'string' ? (partyLists.split ? partyLists.split(',').map(s=>s.trim()) : []) : []),
      positions, status, raw: e,
      results: null
    };
  }).filter(x => x.dbId || x.id);

  filteredElections = [...elections];
  renderElections();
  updateStats();

  elections.forEach(async election => {
    if (String(election.status) === 'completed') {
      if (election.results) return;
      let fetched = null;
      try {
        fetched = await fetchResultsWithFallback(election.dbId || election.id);
      } catch(e) { fetched = null; }
      if (fetched && Array.isArray(fetched.positions)) {
        election.results = fetched;
      } else {
        const local = computeLocalResults(election);
      }
    }
  });
}

/* ---------- render helpers ---------- */
function renderElections(){
  if(!electionsContainer) return;
  electionsContainer.innerHTML = '';
  if (!filteredElections || filteredElections.length === 0) { if (emptyState) emptyState.style.display = 'block'; return; }
  emptyState && (emptyState.style.display = 'none');
  filteredElections.forEach(e => electionsContainer.appendChild(createElectionCard(e)));
}
function createElectionCard(e){
  const div = document.createElement('div');
  div.className = 'election-card';
  const turnoutPct = e.totalVoters ? Math.round((e.votesCast / e.totalVoters) * 100) : 0;
  const useIdForServer = (e.dbId && Number(e.dbId) > 0) ? String(e.dbId) : String(e.id || '');
  div.innerHTML = `
    <div class="election-header">
      <h3 class="election-title">${escapeHtml(e.title)}</h3>
      <span class="election-status">${escapeHtml(e.status)}</span>
    </div>
    <div class="election-details">
      <div class="detail-item"><strong>Start:</strong> ${escapeHtml(e.startDate)}</div>
      <div class="detail-item"><strong>End:</strong> ${escapeHtml(e.endDate)}</div>
      <div class="detail-item"><strong>Votes:</strong> ${escapeHtml(String(e.votesCast||0))}/${escapeHtml(String(e.totalVoters||0))} (${turnoutPct}%)</div>
      <div style="margin-top:8px">
        <button class="view-results-btn" data-election-id="${escapeHtml(useIdForServer)}"><i class="fas fa-chart-bar"></i> View Results</button>
      </div>
    </div>`;
  const viewBtn = div.querySelector('.view-results-btn');
  if (viewBtn) viewBtn.addEventListener('click', (ev) => { ev.stopPropagation(); const id = viewBtn.dataset.electionId; if (!id) { alert('Invalid election id'); return; } openResultsModal(id); });
  return div;
}

/* ---------- fetchResultsWithFallback ---------- */
async function fetchResultsWithFallback(electionId){
  const tried = [];
  for (let cand of API_CANDIDATE_BASES){
    let base = cand.endsWith('/') ? cand : cand + '/';
    tried.push(base + 'get_results.php?election_id=' + encodeURIComponent(electionId));
    try {
      const r = await fetchText(base + 'get_results.php?election_id=' + encodeURIComponent(electionId));
      console.log('[fetchResultsWithFallback] tried', base + 'get_results.php?election_id=' + encodeURIComponent(electionId), '=>', (r && r.status) ? r.status : 'ERR');
      if (r && r.text && isLikelyJson(r.text)) {
        const j = tryParseJson(r.text);
        if (j) { resolvedApiBase = base; if (!resolvedApiBase.endsWith('/')) resolvedApiBase += '/'; return j; }
      }
    } catch(e){ /* continue */ }
  }
  console.warn('[fetchResultsWithFallback] all tried urls:', tried);
  return null;
}

/* ---------- modal helpers ---------- */
function showModal(el){
  if(!el) return;
  el.style.display = 'flex';
  document.body.style.overflow = 'hidden';
}
function closeModal(el){
  if(!el) return;
  el.style.display = 'none';
  document.body.style.overflow = '';
}

/* ---------- openResultsModal ---------- */
async function openResultsModal(electionId){
  if (!electionId || !String(electionId).match(/^\d+$/)) {
    if (modalElectionTitle) modalElectionTitle.textContent = "Error";
    if (modalResultsBody) modalResultsBody.innerHTML = "<p>Invalid election identifier.</p>";
    resultsModal && showModal(resultsModal);
    return;
  }

  const election = elections.find(x => String(x.dbId) === String(electionId) || String(x.id) === String(electionId));

  if (modalElectionTitle) modalElectionTitle.textContent = "Loading...";
  if (modalResultsBody) modalResultsBody.innerHTML = "<p>Loading results...</p>";
  resultsModal && showModal(resultsModal);

  if (election && election.results && Array.isArray(election.results.positions)) {
    renderResultsToModal(election.results);
    return;
  }

  let data = await fetchResultsWithFallback(electionId);

  if (!data) {
    if (!resolvedApiBase) resolvedApiBase = await detectApiBase();
    if (!resolvedApiBase.endsWith('/')) resolvedApiBase += '/';
    try {
      const r = await fetchText(resolvedApiBase + 'get_results.php?election_id=' + encodeURIComponent(electionId));
      if (r && r.text && isLikelyJson(r.text)) data = tryParseJson(r.text);
    } catch(e){ /* ignore */ }
  }

  if (data && Array.isArray(data.positions)) {
    if (election) election.results = data;
    renderResultsToModal(data);
    return;
  }

  if (election) {
    const local = computeLocalResults(election);
    renderResultsToModal(local);
    return;
  }

  if (modalResultsBody) modalResultsBody.innerHTML = "<p>No results available.</p>";
  console.error('[openResultsModal] get_results returned invalid payload or not found and no local election to compute from:', data);
}

/* render results */
function renderResultsToModal(data){
  if (!data || !Array.isArray(data.positions)) {
    if (modalResultsBody) modalResultsBody.innerHTML = "<p>No results available.</p>";
    return;
  }
  if (modalElectionTitle) modalElectionTitle.textContent = data.election?.title ?? ('Election Results');

  let html = '';
  data.positions.forEach(pos => {
    const candidates = Array.isArray(pos.candidates) ? pos.candidates : [];
    let winner = candidates.length ? candidates.reduce((a,b) => (Number(a.votes||0) > Number(b.votes||0) ? a : b)) : { id: null };
    html += `<div class="position-section"><div class="position-header">${escapeHtml(pos.position ?? pos.position_name ?? 'Position')}</div><div class="candidate-list">`;
    if (candidates.length === 0) {
      html += `<div class="candidate-item"><div class="candidate-info"><div class="candidate-name">No candidates</div></div></div>`;
    } else {
      candidates.forEach(c => {
        const avatarUrl = c.avatar_url || c.avatar || (resolvedApiBase ? resolvedApiBase + '../assests/default-avatar.jpg' : '../assests/default-avatar.jpg');
        const isWinner = (c.id !== undefined && winner && (String(c.id) === String(winner.id)));
        const pct = (c.percentage !== undefined) ? c.percentage : (c.perc ?? 0);
        html += `
          <div class="candidate-item ${isWinner ? 'winner' : ''}">
            <div class="candidate-avatar"><img src="${escapeHtml(avatarUrl)}" onerror="this.onerror=null;this.src='../assests/default-avatar.jpg'"></div>
            <div class="candidate-info">
              <div class="candidate-name">${escapeHtml(c.name ?? c.FullName ?? 'Candidate')}</div>
              <div class="candidate-party">${escapeHtml(c.party ?? c.PartyList ?? '')}</div>
            </div>
            <div class="vote-stats">
              <div class="vote-count"><div class="vote-number">${Number(c.votes||0)}</div><div class="vote-label">Votes</div></div>
              <div class="vote-count"><div class="vote-number">${escapeHtml(String(pct))}%</div><div class="vote-label">Share</div></div>
              ${isWinner ? '<div class="winner-badge">Winner</div>' : ''}
            </div>
          </div>`;
      });
    }
    html += `</div></div>`;
  });

  if (modalResultsBody) modalResultsBody.innerHTML = html;
}

/* updateStats */
function updateStats(){
  const totalVotes = elections.reduce((acc,e) => acc + (Number(e.votesCast) || 0), 0);
  const totalVoters = elections.reduce((acc,e) => acc + (Number(e.totalVoters) || 0), 0);
  const turnoutPct = totalVoters > 0 ? Math.round((totalVotes / totalVoters) * 100) : 0;
  const totalCandidates = (Object.values(partyCandidates).flat?.() || []).length || "—";
  const partyListsCount = Array.from(new Set(elections.flatMap(e => e.partyLists || []))).length || "—";
  if (totalVotesEl) totalVotesEl.textContent = totalVotes ? totalVotes.toLocaleString() : "—";
  if (voterTurnoutEl) voterTurnoutEl.textContent = totalVoters > 0 ? `${turnoutPct}%` : "—";
  if (totalCandidatesEl) totalCandidatesEl.textContent = totalCandidates;
  if (partyListsEl) partyListsEl.textContent = partyListsCount;
}

/* voting modal & submit flow */
function openVotingModal(electionId){
  const election = elections.find(x => String(x.dbId) === String(electionId) || String(x.id) === String(electionId));
  if (!election) { alert('Election not found'); return; }
  currentVotingElectionId = electionId;
  const titleEl = votingModal.querySelector('#voting-modal-title');
  titleEl && (titleEl.textContent = `Vote in ${election.title}`);
  votingForm.innerHTML = '';

  (election.positions || []).forEach(position => {
    const posDiv = document.createElement('div');
    posDiv.className = 'vote-position';
    posDiv.innerHTML = `<h4>${escapeHtml(position)}</h4>`;
    const radiosContainer = document.createElement('div');
    radiosContainer.className = 'radios-container';

    const allCandidates = Object.values(partyCandidates).flat();
    const candidatesForPos = allCandidates.filter(c => String(c.position) === String(position) || String(c.position).toLowerCase().includes(String(position).toLowerCase()));

    if (candidatesForPos.length === 0) {
      radiosContainer.innerHTML = `<div class="no-candidates">No candidates available for this position.</div>`;
    } else {
      candidatesForPos.forEach(c => {
        const id = `candidate-${escapeHtml(String(c.id))}-${Math.random().toString(36).slice(2,7)}`;
        const wrapper = document.createElement('div');
        wrapper.className = 'candidate-option';
        wrapper.innerHTML = `
          <input type="radio" name="${escapeHtml(position)}" id="${id}" value="${escapeHtml(c.id)}">
          <label for="${id}"><strong>${escapeHtml(c.name)}</strong> — ${escapeHtml(c.party)}</label>
        `;
        radiosContainer.appendChild(wrapper);
      });
    }

    posDiv.appendChild(radiosContainer);
    votingForm.appendChild(posDiv);
  });

  showModal(votingModal);
}

async function submitVote(){
  if (!currentVotingElectionId) return;
  const election = elections.find(e => String(e.dbId) === String(currentVotingElectionId) || String(e.id) === String(currentVotingElectionId));
  if (!election) return;

  const selections = {};
  let allVoted = true;
  (election.positions || []).forEach(position => {
    const sel = document.querySelector(`input[name="${CSS.escape(position)}"]:checked`);
    if (sel) selections[position] = sel.value; else allVoted = false;
  });
  if (!allVoted) { alert('Please vote for all positions'); return; }

  if (!resolvedApiBase) resolvedApiBase = await detectApiBase();
  if (!resolvedApiBase.endsWith('/')) resolvedApiBase += '/';
  const url = resolvedApiBase + 'submit_vote.php';
  const payload = { electionId: currentVotingElectionId, selections };

  try {
    const res = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload), cache:'no-store' });
    if (res.ok) {
      const data = await res.json().catch(()=>({success:true}));
      if (data && data.success !== false) {
        await loadElections();
        await fetchCandidates();
        updateStats();
        alert(data.message || 'Vote submitted!');
        closeModal(votingModal);
        return;
      }
    }
  } catch (err) { console.warn('[submitVote] server call failed, fallback local', err); }

  const localVote = { voterId: 'V' + String((election.votes||[]).length + 1).padStart(3,'0'), timestamp: new Date().toISOString(), selections };
  election.votes = election.votes || [];
  election.votes.push(localVote);
  election.votesCast = election.votes.length;

  if (!election.partyResults) election.partyResults = {};
  Object.values(selections).forEach(cid => {
    const candidate = Object.values(partyCandidates).flat().find(c => String(c.id) === String(cid));
    const party = (candidate && candidate.party) ? candidate.party : 'Independent';
    if (!election.partyResults[party]) election.partyResults[party] = { votes: 0 };
    election.partyResults[party].votes++;
  });

  updateStats();
  renderElections();
  closeModal(votingModal);
  alert('Your vote has been submitted successfully (local).');
}

/* global event listeners */
document.addEventListener('click', (e) => {
  const viewBtn = e.target.closest && e.target.closest('.view-results-btn');
  if (viewBtn) {
    const electionId = viewBtn.dataset.electionId;
    if (electionId) openResultsModal(electionId);
    return;
  }

  const close = e.target.closest && e.target.closest('.close-modal');
  if (close && close.dataset && close.dataset.close) {
    const id = close.dataset.close;
    const el = document.getElementById(id) || (id === 'voting-modal' ? votingModal : null);
    if (el) closeModal(el);
  }

  if (resultsModal && resultsModal.style.display === 'flex' && !resultsModal.querySelector('.modal-inner')?.contains(e.target)) {
    closeModal(resultsModal);
  }
  if (votingModal && votingModal.style.display === 'flex' && !votingModal.querySelector('.modal-inner')?.contains(e.target)) {
    closeModal(votingModal);
  }
});

if (submitVoteBtn) submitVoteBtn.addEventListener('click', (ev) => { ev.preventDefault(); submitVote(); });

if (applyFiltersBtn) applyFiltersBtn.addEventListener('click', () => {
  filteredElections = elections.filter(e => {
    let keep = true;
    if (statusFilter && statusFilter.value !== 'all' && e.status !== statusFilter.value) keep = false;
    if (yearFilter && yearFilter.value !== 'all' && (e.raw && (e.raw.year || e.raw.academic_year) !== yearFilter.value)) keep = false;
    return keep;
  });
  renderElections();
});

/* init */
(async function init(){
  resolvedApiBase = await detectApiBase();
  console.log('[init] resolvedApiBase =', resolvedApiBase);
  await fetchCandidates();
  await loadElections();
  updateStats();

  setInterval(async ()=> {
    try {
      await loadElections();
      await fetchCandidates();
      updateStats();
    } catch (e) { console.warn('[init] periodic refresh failed', e); }
  }, 15000);
})();

/* utility used by your PHP markup */
function toggleDropdown(){
  const dd = document.getElementById('dropdown');
  if (!dd) return;
  dd.style.display = dd.style.display === 'block' ? 'none' : 'block';
}
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

    /* SIDEBAR - EXACTLY THE SAME AS OTHER PAGES */
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

    /* NAVIGATION - EXACTLY THE SAME AS OTHER PAGES */
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
            margin-left: var(--sidebar-width);
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
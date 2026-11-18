<?php
// manage-elections.php  (complete file — no CSS)
// NOTE: place at top of file and make sure there is NO output before <?php (no BOM)
session_start();

// Accept multiple possible session keys
$ORG_ID = isset($_SESSION['Org_ID']) ? $_SESSION['Org_ID'] : (isset($_SESSION['org_id']) ? $_SESSION['org_id'] : null);
$ORG_NAME = isset($_SESSION['Org_Name']) ? $_SESSION['Org_Name'] : (isset($_SESSION['org_name']) ? $_SESSION['org_name'] : 'SOCE');

// Default fallback profile (uses your dashboard's default asset paths)
$profile = [
    'name' => 'Organization User',
    'position' => 'Organization User',
    'email' => '',
    'avatar' => '/eBotoni/eBotoni/assests/default-logo.jpg'
];

// Resolve logo path helper (mirror dashboard behavior)
function resolve_logo_url(string $dbPath = ''): string {
    // This should match the working dashboard's prefix
    $projectPrefix = '/eBotoni/eBotoni/'; // keep leading/trailing slash as used in dashboard
    $default = $projectPrefix . 'assests/default-logo.jpg';

    $p = trim((string)$dbPath);
    if ($p === '') return $default;

    // Absolute http(s) return as-is
    if (preg_match('#^https?://#i', $p)) return $p;

    // Already root relative (starts with /)
    if (strlen($p) > 0 && ($p[0] === '/' || $p[0] === '\\')) return $p;

    // Remove leading ../ or ./ segments
    $clean = preg_replace('#^(\.\/|(\.\.\/)+)#', '', $p);
    $clean = ltrim($clean, '/\\');

    // If DB value contains project path (e.g. "eBotoni/eBotoni/assests/logo.png"), ensure leading slash
    if (strpos($clean, 'eBotoni/eBotoni/') !== false) {
        return '/' . ltrim($clean, '/\\');
    }

    // Otherwise treat as relative to project prefix
    return rtrim($projectPrefix, '/') . '/' . ltrim($clean, '/\\');
}

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
        error_log("Profile load error (manage-elections): " . $e->getMessage());
        // Keep defaults if DB fails
    }
}

// Expose to JS
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOCE Student Organization - Party List Election Monitoring</title>

    <!-- Font Awesome for icons (no CSS included per request) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Expose profile & org id to JS -->
  <script>
    // Expose server profile for frontend JS (same object structure used in dashboard)
    window.SERVER_PROFILE = <?php echo json_encode($profile, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE); ?>;
    window.CURRENT_ORG_ID = <?php echo json_encode($ORG_ID, JSON_UNESCAPED_SLASHES); ?>;
    window.CURRENT_ORG_NAME = <?php echo json_encode($ORG_NAME, JSON_UNESCAPED_SLASHES); ?>;
  </script>
</head>

<body>
    <!-- SIDEBAR - Same as your main page but with IDs so JS can update avatar/name/role -->
  <nav class="sidebar" id="sidebar">
    <div class="logo-container">
      <div class="logo"><img src="../assests/suffage.png" alt="Logo" id="appLogo"></div>
    </div>

 <ul class="nav-menu">
  <li class="nav-item"><i class="fas fa-home"></i><a href="org-dashboard.php">Dashboard</a></li>
  <li class="nav-item"><i class="fas fa-user-graduate"></i><a href="org-voters.php">Organization Voters</a></li>
  <li class="nav-item"><i class="fas fa-user-tie"></i><a href="manage-candidates.php">Candidates</a></li>
  <li class="nav-item active"><i class="fas fa-user-tag"></i><a href="manage-elections.php">Election Monitoring</a></li>
  <li class="nav-item"><i class="fas fa-check-circle"></i><a href="election-results.php">Election Results</a></li>
  <li class="nav-item"><i class="fas fa-cog"></i><a href="settings.php">Settings</a></li>
</ul>
        
        <div class="user-profile" id="userProfile">
            <!-- updated: give the img an id so JS can set src -->
            <img src="../assests/default-avatar.jpg" class="user-avatar" id="sidebarAvatar" alt="Org Avatar" />
            <div class="user-info">
                <h4 id="sidebarName">Maria Santos</h4>
                <p id="sidebarRole">SOCE President</p>
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
                <h1 id="pageTitle">Party List Election Monitoring</h1>
                <p id="pageSubtitle">Monitor party list elections and track voting progress</p>
            </div>
            
            <div class="header-actions">
                <div class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </div>
            </div>
        </div>
        
        <!-- Access Notice -->
        <div class="access-notice">
            <i class="fas fa-info-circle"></i>
            <p><strong>Stakeholder Access:</strong> You have limited access to SOCE organization data only. Some features are restricted.</p>
        </div>

        <!-- Election Statistics -->
        <div class="election-stats">
            <div class="stat-card stat-1">
                <div class="stat-icon">
                    <i class="fas fa-vote-yea"></i>
                </div>
                <div class="stat-info">
                    <h3>Active Elections</h3>
                    <div class="stat-value" id="activeElections">2</div>
                </div>
            </div>
            
            <div class="stat-card stat-2">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Voters</h3>
                    <div class="stat-value" id="totalVoters">245</div>
                </div>
            </div>
            
            <div class="stat-card stat-3">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3>Votes Cast</h3>
                    <div class="stat-value" id="votesCast">187</div>
                </div>
            </div>
            
            <div class="stat-card stat-4">
                <div class="stat-icon">
                    <i class="fas fa-percentage"></i>
                </div>
                <div class="stat-info">
                    <h3>Turnout Rate</h3>
                    <div class="stat-value" id="turnoutRate">76%</div>
                </div>
            </div>
        </div>
        
        <!-- Election Management -->
        <div class="election-monitoring">
            <div class="management-header">
                <h2>Party List Election Management</h2>
                <div class="management-actions">
                    <button class="btn btn-primary" id="createElectionBtn" type="button">
                        <i class="fas fa-plus-circle"></i> Create Election
                    </button>
                    <button class="btn btn-secondary" id="exportResultsBtn" type="button">
                        <i class="fas fa-file-export"></i> Export Results
                    </button>
                </div>
            </div>

            <!-- Election Tabs -->
            <div class="election-tabs" id="electionTabs">
                <div class="election-tab active" data-status="all">All Elections</div>
                <div class="election-tab" data-status="active">Active</div>
                <div class="election-tab" data-status="upcoming">Upcoming</div>
                <div class="election-tab" data-status="completed">Completed</div>
            </div>
            
            <div class="election-table-container">
                <table class="election-table" id="electionTable">
                    <thead>
                        <tr>
                            <th>Election Name</th>
                            <th>Organization</th>
                            <th>Party Lists</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Voter Turnout</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="electionTableBody">
                        <!-- Election data will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>

            <!-- Empty State -->
            <div class="empty-state" id="emptyState" style="display: none;">
                <i class="fas fa-vote-yea"></i>
                <h3>No Elections Found</h3>
                <p>There are no elections matching your criteria. Try selecting a different status or create a new election.</p>
            </div>
        </div>

        <!-- Removed Real-time Party List Monitoring as requested -->

    </div>

    <!-- Toast Notification -->
    <div class="toast" id="toast"></div>

    <!-- Create/Edit Election Modal -->
    <div class="modal" id="electionModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="electionModalTitle">Create New Election</h2>
                <button class="modal-close" id="closeElectionModal" type="button">&times;</button>
            </div>
            <div class="modal-body">
                <form id="electionForm">
                  <!-- hidden organization id (server-provided by PHP session) -->
                <input type="hidden" id="electionOrganizationId" name="organization_id" value="<?php echo isset($ORG_ID) ? htmlspecialchars($ORG_ID, ENT_QUOTES) : ''; ?>">
                    <input type="hidden" id="electionId">
                    <div class="form-group">
                        <label for="electionName">Election Name</label>
                        <input type="text" id="electionName" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="electionOrganization">Organization</label>
                        <input type="text" id="electionOrganization" class="form-control" value="<?php echo htmlspecialchars($ORG_NAME); ?>" readonly>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="startDate">Start Date</label>
                            <input type="date" id="startDate" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="endDate">End Date</label>
                            <input type="date" id="endDate" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="totalVoters">Total Voters</label>
                     <input type="number" id="electionTotalVoters" class="form-control" min="1" required>

                    </div>
                    <div class="form-group">
                        <label>Party Lists</label>
                        <div class="checkbox-group">
                            <div class="checkbox-item">
                                <input type="checkbox" id="partyProgress" value="Progress">
                                <label for="partyProgress">Progress Party</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="partyUnity" value="Unity">
                                <label for="partyUnity">Unity Alliance</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="partyVision" value="Vision">
                                <label for="partyVision">Vision Coalition</label>
                            </div>
                            <div class="checkbox-item">
                                <input type="checkbox" id="partyIndependent" value="Independent">
                                <label for="partyIndependent">Independent</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancelElectionBtn" type="button">Cancel</button>
                <button class="btn btn-primary" id="saveElectionBtn" type="button">Save Election</button>
            </div>
        </div>
    </div>

    <!-- Voting Modal (kept but no table action will call it) -->
    <div class="modal" id="votingModal">
        <div class="modal-content voting-modal">
            <div class="modal-header">
                <h2 id="votingModalTitle">Cast Your Vote</h2>
                <button class="modal-close" id="closeVotingModal" type="button">&times;</button>
            </div>
            <div class="modal-body">
                <div id="votingInstructions">
                    <p>Please select one candidate for each position from the party lists below:</p>
                </div>
                <div id="votingForm">
                    <!-- Voting form will be populated by JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancelVotingBtn" type="button">Cancel</button>
                <button class="btn btn-primary" id="submitVoteBtn" type="button">Submit Vote</button>
            </div>
        </div>
    </div>

    <!-- Results Modal -->
    <div class="modal" id="resultsModal">
        <div class="modal-content results-modal">
            <div class="modal-header">
                <h2 id="resultsModalTitle">Election Results</h2>
                <button class="modal-close" id="closeResultsModal" type="button">&times;</button>
            </div>
            <div class="modal-body">
                <div id="resultsContent">
                    <!-- Results content will be populated by JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="closeResultsBtn" type="button">Close</button>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal" id="confirmationModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Confirm Action</h2>
                <button class="modal-close" id="closeConfirmationModal" type="button">&times;</button>
            </div>
            <div class="modal-body">
                <div class="confirmation-text" id="confirmationText">
                    Are you sure you want to perform this action?
                </div>
            </div>
            <div class="modal-footer">
                <div class="confirmation-buttons">
                    <button class="btn btn-secondary" id="cancelActionBtn" type="button">Cancel</button>
                    <button class="btn btn-danger" id="confirmActionBtn" type="button">Confirm</button>
                </div>
            </div>
        </div>
    </div>

<script>
/* Full manage-elections script — updated to apply server profile (avatar/name/role) */

/* expose server session org id/name to JS (already set server-side) */
const CURRENT_ORG_ID = window.CURRENT_ORG_ID;
const CURRENT_ORG_NAME = window.CURRENT_ORG_NAME;

let partyCandidates = {};
let elections = [];

/* DOM references (your original ones) */
const menuToggle = document.getElementById('menuToggle');
const sidebar = document.getElementById('sidebar');
const userProfile = document.getElementById('userProfile');
const dropdown = document.getElementById('dropdown');
const logoutBtn = document.getElementById('logoutBtn');
const toast = document.getElementById('toast');
const createElectionBtn = document.getElementById('createElectionBtn');
const exportResultsBtn = document.getElementById('exportResultsBtn');
const refreshDataBtn = document.getElementById('refreshDataBtn');

const activeElectionsEl = document.getElementById('activeElections');
const totalVotersEl = document.getElementById('totalVoters');
const votesCastEl = document.getElementById('votesCast');
const turnoutRateEl = document.getElementById('turnoutRate');

const emptyState = document.getElementById('emptyState');
const electionTabs = document.querySelectorAll('.election-tab');

const electionModal = document.getElementById('electionModal');
const electionModalTitle = document.getElementById('electionModalTitle');
const closeElectionModal = document.getElementById('closeElectionModal');
const electionForm = document.getElementById('electionForm');
const electionIdInput = document.getElementById('electionId');
const electionName = document.getElementById('electionName');
const electionOrganization = document.getElementById('electionOrganization');
const startDate = document.getElementById('startDate');
const endDate = document.getElementById('endDate');
const totalVotersInput = document.getElementById('electionTotalVoters');
const cancelElectionBtn = document.getElementById('cancelElectionBtn');
const saveElectionBtn = document.getElementById('saveElectionBtn');

const votingModal = document.getElementById('votingModal');
const votingModalTitle = document.getElementById('votingModalTitle');
const closeVotingModal = document.getElementById('closeVotingModal');
const votingForm = document.getElementById('votingForm');
const cancelVotingBtn = document.getElementById('cancelVotingBtn');
const submitVoteBtn = document.getElementById('submitVoteBtn');

const resultsModal = document.getElementById('resultsModal');
const resultsModalTitle = document.getElementById('resultsModalTitle');
const closeResultsModal = document.getElementById('closeResultsModal');
const resultsContent = document.getElementById('resultsContent');
const closeResultsBtn = document.getElementById('closeResultsBtn');

const confirmationModal = document.getElementById('confirmationModal');
const closeConfirmationModal = document.getElementById('closeConfirmationModal');
const confirmationText = document.getElementById('confirmationText');
const cancelActionBtn = document.getElementById('cancelActionBtn');
const confirmActionBtn = document.getElementById('confirmActionBtn');

let filteredElections = [];
let selectedStatus = 'all';
let currentElectionId = null;
let pendingAction = null;

/* ---------- Apply server profile to sidebar (avatar/name/role) ---------- */
function applyServerProfileToDOM() {
    const p = window.SERVER_PROFILE || {};
    const avatarEl = document.getElementById('sidebarAvatar');
    const nameEl   = document.getElementById('sidebarName');
    const roleEl   = document.getElementById('sidebarRole');
    const subtitle = document.getElementById('pageSubtitle');

    try {
        if (avatarEl) {
            const url = p.avatar || '';
            if (url) {
                avatarEl.src = url;
                avatarEl.onerror = function() {
                    console.warn('Avatar failed to load, falling back to default.');
                    this.src = '../assests/default-avatar.jpg';
                };
            }
        }
        if (nameEl && p.name) nameEl.textContent = p.name;
        if (roleEl && p.position) roleEl.textContent = p.position;
        if (subtitle) subtitle.textContent = `Monitor party list elections and track voting progress for ${p.name || CURRENT_ORG_NAME}.`;
    } catch (err) {
        console.warn('applyServerProfileToDOM error', err);
    }
}

/* ---------- small helpers ---------- */
function escapeHtml(s){ if(s===null||s===undefined) return ''; return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }
function showToast(msg, type='info'){ if(!toast) return; toast.textContent = msg; toast.className = 'toast'; toast.classList.add(type); toast.classList.add('show'); setTimeout(()=>toast.classList.remove('show'),3000); }
function openModal(m){ if(!m) return; m.classList.add('active'); m.style.display='flex'; }
function closeModal(m){ if(!m) return; m.classList.remove('active'); m.style.display='none'; }
function getPartyDisplayName(p){ const m = {'Progress':'Progress Party','Unity':'Unity Alliance','Vision':'Vision Coalition','Independent':'Independent'}; return m[p]||p||'Independent'; }
function parseDateInput(raw){
  if(raw === null|| raw===undefined || raw === '') return null;
  if(typeof raw === 'number' || /^\d+$/.test(String(raw))){ const n=Number(raw); if(String(n).length <= 10) return new Date(n*1000); return new Date(n); }
  try { const maybe = JSON.parse(raw); if(typeof maybe === 'number') { if(String(maybe).length <= 10) return new Date(maybe*1000); return new Date(maybe); } } catch(e){}
  const dateOnlyMatch = String(raw).trim().match(/^(\d{4})-(\d{2})-(\d{2})$/);
  if(dateOnlyMatch) return new Date(`${raw}T23:59:59`);
  const d = new Date(raw); return isNaN(d) ? null : d;
}
function formatDate(dateString){ if(!dateString) return ''; const d=parseDateInput(dateString); if(!d) return dateString; return d.toLocaleDateString(undefined,{year:'numeric',month:'short',day:'numeric'}); }
function normalizePartyLists(value){ if(!value) return []; if(Array.isArray(value)) return value.map(s=>String(s).trim()).filter(Boolean); try { const parsed = JSON.parse(value); if(Array.isArray(parsed)) return parsed.map(s=>String(s).trim()).filter(Boolean); } catch(e){} return String(value).split(',').map(s=>s.trim()).filter(Boolean); }

/* ---------- server data fetching ---------- */
async function fetchCandidatesFromServer(){
  try{
    const res = await fetch('get_candidates.php',{cache:'no-store', credentials:'include'});
    if(!res.ok) throw new Error('Candidates fetch failed');
    const json = await res.json();
    const rows = Array.isArray(json) ? json : (json.data || []);
    partyCandidates = {};
    rows.forEach(r=>{
      const candidate = {
        id: r.id || r.Candidate_ID || r.candidate_id || '',
        name: r.name || r.FullName || r.Name || '',
        position: r.position || r.Position || '',
        avatar: r.avatar || '../assests/default-avatar.jpg',
        party: r.partyList || r.party || r.Party || 'Independent'
      };
      if(!partyCandidates[candidate.party]) partyCandidates[candidate.party]=[];
      partyCandidates[candidate.party].push(candidate);
    });
  }catch(err){
    console.error(err);
    showToast('Failed to load candidates','error');
  }
}

async function loadElectionsFromServer(){
  try{
    const res = await fetch('get_elections.php',{cache:'no-store', credentials:'include'});
    if(!res.ok) throw new Error('Elections fetch failed');
    const data = await res.json();
    const raw = Array.isArray(data) ? data : (data.data || []);
    const tmp = {};
    raw.forEach(item=>{
      const e = Object.assign({}, item);
      const id = e.id ?? e.electionId ?? e.Election_ID ?? '';
      if(!id) return;
      const start = parseDateInput(e.startDate ?? e.start_date ?? e.start);
      const end = parseDateInput(e.endDate ?? e.end_date ?? e.end);
      const partyLists = normalizePartyLists(e.partyLists ?? e.party_lists ?? e.partylists ?? e.partyList ?? e.partyListsJson);
      const positions = Array.isArray(e.positions) ? e.positions : (e.positions ? String(e.positions).split(',').map(s=>s.trim()).filter(Boolean) : ['President']);
      const votes = Array.isArray(e.votes) ? e.votes : (e.votes ? e.votes : []);
      const totalVoters = parseInt(e.totalVoters ?? e.total_voters ?? 0,10) || 0;
      let votesCast = parseInt(e.votesCast ?? e.votes_cast ?? votes.length,10) || votes.length || 0;
      let partyResults = {};
      if(e.partyResults && typeof e.partyResults === 'object'){
        Object.entries(e.partyResults).forEach(([p,v]) => partyResults[p] = { votes: parseInt((v && v.votes) || v || 0,10) || 0 });
      } else {
        (partyLists || []).forEach(p => partyResults[p] = partyResults[p] || { votes: 0 });
        votes.forEach(vote=>{
          Object.values(vote.selections || {}).forEach(cid=>{
            const candidate = Object.values(partyCandidates).flat().find(c => String(c.id) === String(cid));
            const partyName = (candidate && candidate.party) ? candidate.party : 'Independent';
            if(!partyResults[partyName]) partyResults[partyName] = { votes: 0 };
            partyResults[partyName].votes++;
          });
        });
        votesCast = votes.length;
      }
      let status = (e.status || '').toString().toLowerCase();
      const now = new Date();
      if(!status || status==='null' || status==='undefined'){
        if(start && end){
          if(now < start) status = 'upcoming';
          else if(now >= start && now <= end) status = 'active';
          else status = 'completed';
        } else status = e.status || 'upcoming';
      }
      tmp[String(id)] = {
        id: String(id),
        name: e.name ?? e.electionName ?? e.Title ?? 'Untitled Election',
        organization: e.organization ?? e.organizationName ?? e.Organization ?? (window.CURRENT_ORG_NAME || 'Unknown'),
        partyLists: partyLists,
        positions: positions,
        votes: votes,
        totalVoters: totalVoters,
        votesCast: votesCast,
        partyResults: partyResults,
        status: status,
        startDate: start ? start.toISOString() : (e.startDate || ''),
        endDate: end ? end.toISOString() : (e.endDate || '')
      };
    });
    elections = Object.values(tmp);
    filteredElections = [...elections];
    updateElectionStats();
    renderElectionTable();
  }catch(err){
    console.error(err);
    showToast('Failed to load elections','error');
  }
}

/* ---------- table/render helpers ---------- */
function updateElectionStats(){
  const activeCount = elections.filter(e => e.status === 'active').length;
  const totalVotersCount = elections.reduce((s,e)=> s + (e.totalVoters||0), 0);
  const votesCastCount = elections.reduce((s,e)=> s + (e.votesCast||0), 0);
  const turnout = totalVotersCount > 0 ? Math.round((votesCastCount/totalVotersCount)*100) : 0;
  if(activeElectionsEl) activeElectionsEl.textContent = activeCount;
  if(totalVotersEl) totalVotersEl.textContent = totalVotersCount;
  if(votesCastEl) votesCastEl.textContent = votesCastCount;
  if(turnoutRateEl) turnoutRateEl.textContent = `${turnout}%`;
}

function selectStatus(status){ selectedStatus = status; if(electionTabs) electionTabs.forEach(tab => tab.classList.toggle('active', tab.getAttribute('data-status') === status)); filterElections(); }
function filterElections(){ filteredElections = selectedStatus === 'all' ? [...elections] : elections.filter(e => e.status === selectedStatus); renderElectionTable(); }

function renderElectionTable(){
  const electionTableBody = document.getElementById('electionTableBody');
  const electionTable = document.getElementById('electionTable');
  if(!electionTableBody || !electionTable) return;
  electionTableBody.innerHTML = '';
  if(!filteredElections || filteredElections.length === 0){
    if(emptyState) emptyState.style.display = 'block';
    electionTable.style.display = 'none';
    return;
  }
  if(emptyState) emptyState.style.display = 'none';
  electionTable.style.display = 'table';

  filteredElections.forEach(election=>{
    const row = document.createElement('tr');
    const turnout = election.totalVoters ? Math.round((election.votesCast/election.totalVoters)*100) : 0;
    const partyListBadges = (election.partyLists||[]).map(p=>`<span class="party-badge party-${String(p).toLowerCase()}">${escapeHtml(getPartyDisplayName(p))}</span>`).join(' ');
    row.innerHTML = `
      <td>${escapeHtml(election.name)}</td>
      <td>${escapeHtml(election.organization)}</td>
      <td>${partyListBadges}</td>
      <td>${formatDate(election.startDate)}</td>
      <td>${formatDate(election.endDate)}</td>
      <td>${election.votesCast || 0}/${election.totalVoters || 0} (${turnout}%)</td>
      <td><span class="status-badge status-${escapeHtml(election.status)}">${(election.status||'').charAt(0).toUpperCase() + (election.status||'').slice(1)}</span></td>
      <td>
        <div class="action-buttons">
          <button type="button" class="btn-action btn-view" data-action="view" data-id="${escapeHtml(election.id)}" title="View Results"><i class="fas fa-eye"></i></button>
          <button type="button" class="btn-action btn-edit" data-action="edit" data-id="${escapeHtml(election.id)}" title="Edit Election"><i class="fas fa-edit"></i></button>
          <button type="button" class="btn-action btn-delete" data-action="delete" data-id="${escapeHtml(election.id)}" title="Delete Election"><i class="fas fa-trash"></i></button>
        </div>
      </td>
    `;
    electionTableBody.appendChild(row);
  });
}

/* ---------- voting & results (kept same as your logic) ---------- */
function openVotingModal(electionId){
  const election = elections.find(e => String(e.id) === String(electionId));
  if(!election || !votingForm) return;
  currentElectionId = electionId;
  votingModalTitle.textContent = `Vote in ${election.name}`;
  const now = new Date();
  const start = parseDateInput(election.startDate);
  const end = parseDateInput(election.endDate);
  if(start && now < start){ showToast('This election has not started yet','warning'); return; }
  if(end && now > end){ showToast('This election has already ended','warning'); return; }

  votingForm.innerHTML = '';
  (election.positions || []).forEach(position=>{
    const positionSection = document.createElement('div'); positionSection.className='party-section';
    const header = document.createElement('div'); header.className='party-header'; header.textContent = position;
    const candidatesContainer = document.createElement('div');
    (election.partyLists || []).forEach(party=>{
      const candidates = partyCandidates[party]?.filter(c => String(c.position) === String(position)) || [];
      candidates.forEach(candidate=>{
        const candidateOption = document.createElement('div'); candidateOption.className='candidate-option';
        candidateOption.innerHTML = `
          <input type="radio" name="${escapeHtml(position)}" value="${escapeHtml(candidate.id)}" id="candidate-${escapeHtml(candidate.id)}">
          <label for="candidate-${escapeHtml(candidate.id)}"><strong>${escapeHtml(candidate.name)}</strong> - ${escapeHtml(getPartyDisplayName(candidate.party))}</label>
        `;
        candidatesContainer.appendChild(candidateOption);
      });
    });
    if(candidatesContainer.children.length === 0){
      const noC = document.createElement('div'); noC.className='candidate-option'; noC.textContent='No candidates available for this position.'; candidatesContainer.appendChild(noC);
    }
    positionSection.appendChild(header); positionSection.appendChild(candidatesContainer); votingForm.appendChild(positionSection);
  });
  openModal(votingModal);
}

async function submitVote(){
  const election = elections.find(e => String(e.id) === String(currentElectionId));
  if(!election) return;
  const votes = {}; let allPositionsVoted = true;
  (election.positions || []).forEach(position=>{
    const selectedCandidate = document.querySelector(`input[name="${CSS.escape(position)}"]:checked`);
    if(selectedCandidate) votes[position] = selectedCandidate.value;
    else allPositionsVoted = false;
  });
  if(!allPositionsVoted){ showToast('Please vote for all positions','error'); return; }

  try{
    const payload = { electionId: currentElectionId, selections: votes, orgId: CURRENT_ORG_ID };
    const res = await fetch('submit_vote.php',{ method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload), cache:'no-store', credentials: 'include' });
    if(res.ok){
      const data = await res.json().catch(()=>({success:true}));
      if(data && data.success !== false){
        showToast(data.message || 'Your vote was submitted!','success');
        closeModal(votingModal);
        await loadElectionsFromServer();
        return;
      }
    }
  }catch(err){
    console.warn('submitVote server call failed, performing local update',err);
  }

  const localVote = { voterId: 'V' + String((election.votes||[]).length + 1).padStart(3,'0'), timestamp: new Date().toISOString(), selections: votes };
  election.votes = election.votes || [];
  election.votes.push(localVote);
  election.votesCast = election.votes.length;
  Object.values(votes).forEach(candidateId=>{
    const candidate = Object.values(partyCandidates).flat().find(c => String(c.id) === String(candidateId));
    const party = (candidate && candidate.party) ? candidate.party : 'Independent';
    if(!election.partyResults) election.partyResults = {};
    if(!election.partyResults[party]) election.partyResults[party] = { votes: 0 };
    election.partyResults[party].votes++;
  });
  showToast('Your vote has been submitted successfully (local)!','success');
  closeModal(votingModal);
  updateElectionStats(); filterElections(); renderElectionTable();
}

function viewElection(electionId){
  const election = elections.find(e=> String(e.id) === String(electionId));
  if(!election || !resultsContent){
    showToast('Election not found','error');
    return;
  }
  currentElectionId = electionId;
  resultsModalTitle.textContent = `${election.name} - Results`;
  resultsContent.innerHTML = '';

  const computedPartyResults = {};
  (election.partyLists || []).forEach(p => computedPartyResults[p] = 0);

  if (Array.isArray(election.votes)) {
    election.votes.forEach(vote => {
      Object.values(vote.selections || {}).forEach(candidateId => {
        const candidate = Object.values(partyCandidates).flat().find(c => String(c.id) === String(candidateId));
        const partyName = (candidate && candidate.party) ? candidate.party : 'Independent';
        if (!computedPartyResults[partyName]) computedPartyResults[partyName] = 0;
        computedPartyResults[partyName]++;
      });
    });
  }

  const partyResultsToShow = {};
  (election.partyLists || []).forEach(p => {
    const serverVal = (election.partyResults && election.partyResults[p] && Number.isFinite(Number(election.partyResults[p].votes))) ? Number(election.partyResults[p].votes) : null;
    partyResultsToShow[p] = serverVal !== null ? Number(serverVal) : (computedPartyResults[p] || 0);
  });

  const summary = document.createElement('div'); summary.className='results-summary';
  summary.innerHTML = `
    <div class="result-card"><h3>Total Votes</h3><div class="result-value">${election.votesCast || (Array.isArray(election.votes) ? election.votes.length : 0)}</div></div>
    <div class="result-card"><h3>Voter Turnout</h3><div class="result-value">${election.totalVoters ? Math.round(((election.votesCast || (Array.isArray(election.votes) ? election.votes.length : 0)) / election.totalVoters) * 100) : 0}%</div></div>
    <div class="result-card"><h3>Leading Party</h3><div class="result-value">${escapeHtml(getLeadingParty({ partyResults: partyResultsToShow, votesCast: (election.votesCast || (Array.isArray(election.votes) ? election.votes.length : 0)) }) || 'N/A')}</div></div>
    <div class="result-card"><h3>Positions</h3><div class="result-value">${(election.positions||[]).length}</div></div>
  `;
  resultsContent.appendChild(summary);

  const partyResultsSection = document.createElement('div');
  partyResultsSection.innerHTML = '<h3>Party Results</h3>';
  (election.partyLists || []).forEach(p=>{
    const votesForParty = partyResultsToShow[p] || 0;
    const votesCastTotal = election.votesCast || (Array.isArray(election.votes) ? election.votes.length : 0);
    const percentage = votesCastTotal > 0 ? Math.round((votesForParty / votesCastTotal)*100) : 0;
    const partyResult = document.createElement('div');
    partyResult.style.marginBottom = '15px';
    partyResult.innerHTML = `
      <div style="display:flex; justify-content:space-between; margin-bottom:5px;">
        <span>${escapeHtml(getPartyDisplayName(p))}</span>
        <span>${votesForParty} votes (${percentage}%)</span>
      </div>
      <div class="progress-container"><div class="progress-bar" style="width: ${percentage}%;"></div></div>
    `;
    partyResultsSection.appendChild(partyResult);
  });
  resultsContent.appendChild(partyResultsSection);

  openModal(resultsModal);
}

async function deleteElection(electionId) {
    try {
        const res = await fetch("delete_election.php", {
          method: "POST",
          credentials: "include",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            electionId: electionId,
            orgId: CURRENT_ORG_ID
          })
        });

        const data = await res.json().catch(() => null);

        if (!res.ok || !data || data.success === false) {
            showToast(data?.message || "Delete failed", "error");
            console.warn("DELETE ERROR:", data);
            return;
        }

        showToast("Election deleted", "success");
        await loadElectionsFromServer();

    } catch (err) {
        console.error("Delete exception:", err);
        showToast("Server error", "error");
    }
}

function executePendingAction(){
  if(!pendingAction){
    closeModal(confirmationModal);
    return;
  }
  try {
    const maybePromise = pendingAction();
    if (maybePromise && typeof maybePromise.then === 'function') {
      maybePromise.then(()=> { pendingAction = null; }).catch(()=> { pendingAction = null; });
    } else {
      pendingAction = null;
    }
  } catch (err) {
    console.error('executePendingAction error', err);
    pendingAction = null;
  }
  closeModal(confirmationModal);
}

function calculateWinners(election, position){
  const positionVotes = {};
  (election.votes || []).forEach(vote=>{
    const cid = vote.selections ? vote.selections[position] : null;
    if(cid) positionVotes[cid] = (positionVotes[cid] || 0) + 1;
  });
  let maxVotes = 0; let winners = [];
  Object.entries(positionVotes).forEach(([cid,v]) => {
    if(v > maxVotes){ maxVotes = v; winners = [cid]; } else if(v === maxVotes) winners.push(cid);
  });
  return Object.values(partyCandidates).flat()
    .filter(c => c.position === position)
    .map(c => ({ ...c, votes: positionVotes[c.id] || 0, isWinner: winners.includes(String(c.id)) }))
    .sort((a,b) => b.votes - a.votes);
}

function getLeadingParty(election){
  let leading = ''; let maxv = -1;
  Object.entries(election.partyResults || {}).forEach(([party,data])=>{
    if((data.votes || 0) > maxv){ maxv = data.votes || 0; leading = party; }
  });
  return maxv > 0 ? getPartyDisplayName(leading) : null;
}

function confirmDeleteElection(electionId){
  const election = elections.find(e => String(e.id) === String(electionId));
  if(!election || !confirmationModal || !confirmationText) return;
  currentElectionId = electionId;
  confirmationText.textContent = `Are you sure you want to delete the election "${election.name}"? This action cannot be undone.`;
  pendingAction = () => { deleteElection(electionId); closeModal(confirmationModal); };
  openModal(confirmationModal);
}

function exportResults(){ showToast('Exporting results...','info'); setTimeout(()=>{
  const csv = "data:text/csv;charset=utf-8,ID,Name,Organization,Party Lists,Start Date,End Date,Total Voters,Votes Cast,Status\n" + elections.map(e=>`${e.id},"${e.name}","${e.organization}","${(e.partyLists||[]).join('; ')}",${e.startDate||''},${e.endDate||''},${e.totalVoters},${e.votesCast},${e.status}`).join("\n");
  const link = document.createElement('a'); link.href = encodeURI(csv); link.setAttribute('download','party_list_election_results.csv'); document.body.appendChild(link); link.click(); document.body.removeChild(link); showToast('Export completed','success');
},600); }

async function refreshData(){
  showToast('Refreshing party list election data...','info');
  try{
    await fetchCandidatesFromServer();
    await loadElectionsFromServer();
    showToast('Party list election data refreshed!','success');
  }catch(err){
    console.error(err);
    showToast('Refresh failed','error');
  }
}

/* ---------- Event listeners ---------- */
function setupEventListeners(){
  if(menuToggle) menuToggle.addEventListener('click', ()=> sidebar && sidebar.classList.toggle('active'));
  if(userProfile) userProfile.addEventListener('click', ()=> { if(!dropdown) return; dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block'; });
  if(logoutBtn) logoutBtn.addEventListener('click', (e)=>{ e.preventDefault(); showToast('Logging out...','info'); setTimeout(()=>window.location.href='../login.html',800); });
  if(createElectionBtn) createElectionBtn.addEventListener('click', ()=> openModal(electionModal));
  if(exportResultsBtn) exportResultsBtn.addEventListener('click', exportResults);
  if(refreshDataBtn) refreshDataBtn.addEventListener('click', refreshData);
  if(electionTabs) electionTabs.forEach(tab=>tab.addEventListener('click', e=> selectStatus(e.currentTarget.getAttribute('data-status'))));
  if(closeElectionModal) closeElectionModal.addEventListener('click', ()=> closeModal(electionModal));
  if(cancelElectionBtn) cancelElectionBtn.addEventListener('click', ()=> closeModal(electionModal));
  if(saveElectionBtn) saveElectionBtn.addEventListener('click', saveElection);
  if(closeVotingModal) closeVotingModal.addEventListener('click', ()=> closeModal(votingModal));
  if(cancelVotingBtn) cancelVotingBtn.addEventListener('click', ()=> closeModal(votingModal));
  if(submitVoteBtn) submitVoteBtn.addEventListener('click', submitVote);
  if(closeResultsModal) closeResultsModal.addEventListener('click', ()=> closeModal(resultsModal));
  if(closeResultsBtn) closeResultsBtn.addEventListener('click', ()=> closeModal(resultsModal));
  if(closeConfirmationModal) closeConfirmationModal.addEventListener('click', ()=> closeModal(confirmationModal));
  if(cancelActionBtn) cancelActionBtn.addEventListener('click', ()=> closeModal(confirmationModal));
  if(confirmActionBtn) confirmActionBtn.addEventListener('click', executePendingAction);

  if (!document._electionClickHandlerInstalled) {
    document.addEventListener('click', (e) => {
      try {
        const btn = e.target.closest('button[data-action]');
        if (!btn) return;
        const table = document.getElementById('electionTable');
        const inTable = table && table.contains(btn);
        if (!inTable) return;
        const action = btn.dataset.action;
        const electionIdRaw = btn.dataset.id;
        if (!action) return;
        switch(action) {
          case 'view':
            if (!electionIdRaw) { console.warn('view clicked but no data-id'); return; }
            viewElection(electionIdRaw);
            break;
          case 'edit':
            openElectionModal(electionIdRaw);
            break;
          case 'delete':
            confirmDeleteElection(electionIdRaw);
            break;
          default:
            console.warn('Unhandled election action:', action);
        }
      } catch (err) {
        console.error('election click handler error', err);
      }
    });
    document._electionClickHandlerInstalled = true;
  }

  document.addEventListener('click', (e)=>{
    if(dropdown && userProfile && !userProfile.contains(e.target)) dropdown.style.display = 'none';
    if(window.innerWidth <= 992 && sidebar && !sidebar.contains(e.target) && menuToggle && !menuToggle.contains(e.target) && sidebar.classList.contains('active')) sidebar.classList.remove('active');
  });

  document.addEventListener('keydown', (e)=> { if(e.key === 'Escape' && confirmationModal && confirmationModal.classList.contains('active')) closeModal(confirmationModal); });
}

/* ---------- Election modal save (kept original with org ID exposure) ---------- */
const electionOrganizationId = document.getElementById('electionOrganizationId');

function openElectionModal(id = null){
  if(!electionForm) return;
  electionForm.reset();

  if(typeof CURRENT_ORG_ID !== 'undefined' && CURRENT_ORG_ID){
    if(electionOrganizationId) electionOrganizationId.value = String(CURRENT_ORG_ID);
    if(electionOrganization) electionOrganization.value = String(CURRENT_ORG_NAME || electionOrganization.value || '');
  }

  if(id){
    const election = elections.find(e => String(e.id) === String(id));
    if(election){
      electionModalTitle.textContent = 'Edit Election';
      electionIdInput.value = election.id;
      electionName.value = election.name;
      const electionOrgId = election.organization_id ?? election.Org_ID ?? election.Organization_ID ?? election.OrgId ?? null;
      if (electionOrgId !== null && electionOrganizationId) {
        electionOrganizationId.value = String(electionOrgId);
      }
      electionOrganization.value = election.organization || (typeof CURRENT_ORG_NAME !== 'undefined' ? CURRENT_ORG_NAME : '');
      startDate.value = election.startDate ? election.startDate.split('T')[0] : '';
      endDate.value = election.endDate ? election.endDate.split('T')[0] : '';
      totalVotersInput.value = election.totalVoters || '';
      const normalized = normalizePartyLists(election.partyLists);
      document.querySelectorAll('#electionModal input[type="checkbox"]').forEach(checkbox => {
        checkbox.checked = normalized.includes(checkbox.value);
      });
    }
  } else {
    electionModalTitle.textContent = 'Create New Election';
    electionIdInput.value = '';
    const today = new Date(); const nextWeek = new Date(); nextWeek.setDate(today.getDate() + 5);
    startDate.value = today.toISOString().split('T')[0];
    endDate.value = nextWeek.toISOString().split('T')[0];
    document.querySelectorAll('#electionModal input[type="checkbox"]').forEach(checkbox => checkbox.checked = true);
  }
  openModal(electionModal);
}

async function saveElection(){
  try {
    if(!electionForm) { console.error('No electionForm element'); showToast('Form not found','error'); return; }
    if(!electionForm.checkValidity()){ showToast('Please fill in all required fields','error'); return; }

    const selectedParties = Array.from(document.querySelectorAll('#electionModal input[type="checkbox"]:checked')).map(cb => cb.value).filter(Boolean);
    if(selectedParties.length === 0){ showToast('Please select at least one party list','error'); return; }

    const isNew = !electionIdInput.value;
    const startRaw = startDate.value || '';
    const endRaw = endDate.value || '';
    const startISO = startRaw ? new Date(startRaw + 'T00:00:00').toISOString() : null;
    const endISO = endRaw ? new Date(endRaw + 'T23:59:59').toISOString() : null;

    const orgIdVal = electionOrganizationId && electionOrganizationId.value ? String(electionOrganizationId.value).trim() : (typeof CURRENT_ORG_ID !== 'undefined' ? String(CURRENT_ORG_ID) : null);

    const payload = {
      electionId: electionIdInput.value || null,
      name: (electionName.value || '').trim(),
      organization: (electionOrganization.value || '').trim(),
      organization_id: orgIdVal || null,
      startDate: isNew ? new Date().toISOString() : (startISO || startRaw || null),
      endDate: endISO || endRaw || null,
      totalVoters: parseInt(totalVotersInput.value || '0', 10) || 0,
      partyLists: selectedParties,
      party_lists: selectedParties.join(','),
      partyListsJson: JSON.stringify(selectedParties),
      status: isNew ? 'active' : undefined
    };

    console.debug('saveElection payload:', payload);

    showToast(isNew ? 'Creating election...' : 'Saving election...', 'info');

    const res = await fetch('save_election.php', {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify(payload),
      cache: 'no-store'
    });

    let data;
    try { data = await res.json(); }
    catch(parseErr) {
      const text = await res.text().catch(()=>null);
      data = { success: res.ok, rawText: text };
    }

    if(!res.ok || (data && data.success === false)){
      const message = (data && (data.message || data.error)) || `Server returned ${res.status}`;
      showToast(message, 'error');
      return;
    }

    showToast((data && (data.message || 'Election saved successfully!')) || 'Saved', 'success');
    closeModal(electionModal);

    await fetchCandidatesFromServer();
    await loadElectionsFromServer();

  } catch (err) {
    console.error('saveElection unexpected error:', err);
    try {
      const localId = electionIdInput.value || ('local-' + Date.now());
      const localE = {
        id: localId,
        name: electionName.value || 'Untitled (local)',
        organization: electionOrganization.value || '',
        startDate: new Date().toISOString(),
        endDate: endDate.value ? new Date(endDate.value + 'T23:59:59').toISOString() : null,
        totalVoters: parseInt(totalVotersInput.value || '0',10) || 0,
        partyLists: Array.from(document.querySelectorAll('#electionModal input[type="checkbox"]:checked')).map(cb => cb.value),
        positions: ['President'],
        votes: [], votesCast: 0, partyResults: {}, status: 'active'
      };
      elections.push(localE);
      updateElectionStats(); filterElections(); renderElectionTable();
      closeModal(electionModal);
      showToast('Saved locally (server unavailable).', 'warning');
    } catch (localErr){
      console.error('Local fallback failed', localErr);
      showToast('Failed to save election', 'error');
    }
  }
}

/* ---------- init ---------- */
async function initPage(){
  [electionModal, votingModal, resultsModal, confirmationModal].forEach(m => { if(m){ m.classList.remove('active'); m.style.display='none'; } });
  setupEventListeners();
  applyServerProfileToDOM();
  await fetchCandidatesFromServer();
  await loadElectionsFromServer();
}

document.addEventListener('DOMContentLoaded', initPage);
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

    /* SIDEBAR - EXACTLY THE SAME AS VOTERS PAGE */
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

    /* NAVIGATION - EXACTLY THE SAME AS VOTERS PAGE */
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

        /* Main Content */
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
            background: white;
            padding: 15px 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .page-title h1 {
            font-size: 1.8rem;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .page-title p {
            color: var(--gray);
            font-size: 0.9rem;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .menu-toggle {
            display: none;
            font-size: 1.5rem;
            color: var(--primary);
            cursor: pointer;
        }

        /* Page Content */
        .access-notice {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
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

        /* Election Monitoring */
        .election-monitoring {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .management-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .management-header h2 {
            color: var(--primary);
            font-size: 1.5rem;
        }

        .management-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--primary);
            color: var(--dark);
        }

        .btn-primary:hover {
            background: var(--secondary);
        }

        .btn-secondary {
            background: var(--light-gray);
            color: var(--dark);
        }

        .btn-secondary:hover {
            background: #d1d5db;
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #3d9a35;
        }

        .btn-warning {
            background: var(--warning);
            color: var(--dark);
        }

        .btn-warning:hover {
            background: #e6b800;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        /* Election Stats */
        .election-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 1.3rem;
        }

        .stat-1 .stat-icon { background: rgba(0, 51, 102, 0.1); color: var(--primary); }
        .stat-2 .stat-icon { background: rgba(75, 181, 67, 0.1); color: var(--success); }
        .stat-3 .stat-icon { background: rgba(255, 204, 0, 0.1); color: var(--accent); }
        .stat-4 .stat-icon { background: rgba(220, 53, 69, 0.1); color: var(--danger); }

        .stat-info h3 {
            font-size: 0.9rem;
            color: var(--gray);
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }

        /* Election Tabs */
        .election-tabs {
            display: flex;
            background: var(--light-gray);
            border-radius: 8px;
            padding: 5px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 5px;
        }

        .election-tab {
            flex: 1;
            padding: 12px 20px;
            text-align: center;
            cursor: pointer;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-weight: 600;
            min-width: 140px;
        }

        .election-tab:hover {
            background: rgba(0, 51, 102, 0.1);
        }

        .election-tab.active {
            background: var(--primary);
            
        }

        /* Election Table */
        .election-table-container {
            overflow-x: auto;
        }

        .election-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .election-table th {
            background: var(--light-gray);
            padding: 12px 15px;
            text-align: center;
            font-weight: 600;
            color: var(--primary);
            border-bottom: 2px solid var(--primary);
        }

        .election-table td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--light-gray);
              text-align: center;
        }

        .election-table tr:hover {
            background: rgba(0, 51, 102, 0.03);
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-active {
            background: rgba(75, 181, 67, 0.1);
            color: var(--success);
        }

        .status-upcoming {
            background: rgba(255, 204, 0, 0.1);
            color: var(--warning);
        }

        .status-completed {
            background: rgba(0, 102, 204, 0.1);
            color: var(--secondary);
        }

        .status-cancelled {
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger);
        }

        .party-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
               color: var(--gray);
            display: inline-block;
            margin: 2px;
        }

        .party-progress { background-color: var(--party-progress); }
        .party-unity { background-color: var(--party-unity); }
        .party-vision { background-color: var(--party-vision); }
        .party-independent { background-color: var(--party-independent); }

        .action-buttons {
            display: flex;
            gap: 5px;
            justify-content: center;
            align-items: center;
        }

        .btn-action {
            padding: 6px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
        }

        .btn-edit {
            background: rgba(0, 102, 204, 0.1);
            color: var(--secondary);
        }

        .btn-edit:hover {
            background: rgba(0, 102, 204, 0.2);
        }

        .btn-delete {
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger);
        }

        .btn-delete:hover {
            background: rgba(220, 53, 69, 0.2);
        }

        .btn-view {
            background: rgba(76, 175, 80, 0.1);
            color: var(--success);
        }

        .btn-view:hover {
            background: rgba(76, 175, 80, 0.2);
        }

        .btn-vote {
            background: rgba(255, 204, 0, 0.1);
            color: var(--warning);
        }

        .btn-vote:hover {
            background: rgba(255, 204, 0, 0.2);
        }

        /* Progress Bar */
        .progress-container {
            width: 100%;
            background-color: var(--light-gray);
            border-radius: 5px;
            margin: 10px 0;
        }

        .progress-bar {
            height: 10px;
            border-radius: 5px;
            transition: width 0.5s ease;
        }

        /* Toast notification */
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 15px 25px;
            background-color: var(--primary);
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
            background-color: var(--success);
        }

        .toast.error {
            background-color: var(--danger);
        }

        .toast.warning {
            background-color: var(--warning);
            color: var(--dark);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--gray);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: var(--light-gray);
        }

        .empty-state h3 {
            margin-bottom: 10px;
            color: var(--gray);
        }

        /* Modal Styles */
.modal {
  display: none; /* stays the same */
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.5);
  z-index: 1000;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 20px;
}

        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
  width: 600px;
  max-width: 100%;
  max-height: calc(100vh - 40px); /* ensure it never exceeds viewport */
  overflow-y: auto;                 /* allow internal scroll */
  border-radius: 10px;
  background: white;
}
        
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid var(--light-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h2 {
            color: var(--primary);
            margin: 0;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray);
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid var(--light-gray);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--primary);
        }
        
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid var(--light-gray);
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--secondary);
        }
        
        .form-row {
            display: flex;
            gap: 15px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        
        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .checkbox-item input {
            margin: 0;
        }
        
        /* Candidate Management Styles */
        .candidate-management {
            margin-top: 20px;
        }
        
        .candidate-list {
            border: 1px solid var(--light-gray);
            border-radius: 6px;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .candidate-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
            border-bottom: 1px solid var(--light-gray);
        }
        
        .candidate-item:last-child {
            border-bottom: none;
        }
        
        .candidate-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .candidate-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--accent);
        }
        
        .candidate-actions {
            display: flex;
            gap: 5px;
        }
        
        /* Voting Modal Styles */
        .voting-modal {
            max-width: 800px;
        }
        
        .party-section {
            margin-bottom: 25px;
            border: 1px solid var(--light-gray);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .party-header {
            background: var(--primary);
            color: white;
            padding: 12px 15px;
            font-weight: 600;
        }
        
        .candidate-option {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            border-bottom: 1px solid var(--light-gray);
        }
        
        .candidate-option:last-child {
            border-bottom: none;
        }
        
        .candidate-option input {
            margin-right: 10px;
        }
        
        /* Results Modal Styles */
        .results-modal {
            max-width: 900px;
        }
        
        .results-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .result-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .result-card h3 {
            margin: 0 0 10px;
            font-size: 1rem;
            color: var(--gray);
        }
        
        .result-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .winner-badge {
            background: var(--success);
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            margin-left: 5px;
        }
        
        /* Confirmation Modal */
        .confirmation-modal .modal-content {
            max-width: 400px;
        }
        
        .confirmation-text {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .confirmation-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        /* Loading Spinner */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
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
            .management-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .management-actions {
                width: 100%;
                justify-content: space-between;
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

            .election-stats {
                grid-template-columns: 1fr 1fr;
            }

            .election-tabs {
                flex-direction: column;
            }

            .election-tab {
                min-width: auto;
            }

            .party-results {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
                gap: 3px;
            }

            .btn-action {
                width: 100%;
                height: 28px;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 15px;
            }

            .election-stats {
                grid-template-columns: 1fr;
            }

            .management-actions {
                flex-direction: column;
                width: 100%;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .action-buttons {
                flex-direction: row;
                gap: 5px;
            }

            .btn-action {
                width: 32px;
                height: 32px;
            }
        }
    </style>
</body>
</html>

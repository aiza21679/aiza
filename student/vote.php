<?php
// vote.php
session_start();
// accept either Student_ID (legacy) or user_id
$studentId = null;
if (!empty($_SESSION['Student_ID'])) {
    $studentId = $_SESSION['Student_ID'];
} elseif (!empty($_SESSION['user_id'])) {
    $studentId = $_SESSION['user_id'];
}
$studentName = isset($_SESSION['Student_Name']) ? $_SESSION['Student_Name'] : 'Student';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USTP Jasaan - Active Elections</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="logo-container">
            <div class="logo">
                <img src="../assests/suffage.png" alt="Logo">
            </div>
            <div class="logoW"></div>
        </div>
        <div class="nav-menu">
            <a href="dashboard.php"><i class="fas fa-home"></i><span>Dashboard</span></a>
            <a href="vote.php" class="active"><i class="fas fa-vote-yea"></i><span>Active Elections</span></a>
            <a href="votinghistory.php"><i class="fas fa-history"></i><span>Voting History</span></a>
            <a href="candidates.php"><i class="fas fa-user-friends"></i><span>Candidates</span></a>
            <a href="results.php"><i class="fas fa-chart-bar"></i><span>Results</span></a>
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
    <a href="../login.html">Logout</a>
  </div>
</div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="header">
            <div class="page-title">
                <h1>Vote Now</h1>
                <p>Don't just hope for change—vote for it!</p>
            </div>
        </div>

        <!-- Active Elections Content -->
        <div class="dashboard-title">
            <i class="fas fa-vote-yea"></i>
            <h2>Active Elections</h2>
        </div>

        <div class="elections-container" id="elections-container">
            <!-- Election cards will be dynamically generated here -->
        </div>

        <footer>
            <p>© 2025 eBOTO | USTP Jasaan | All Rights Reserved</p>
        </footer>
    </main>

    <!-- Voting Modal -->
    <div id="voting-modal" class="modal" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modal-election-title">Vote</h2>
                <button class="close-modal">&times;</button>
            </div>

            <div class="success-message" id="vote-success" style="display:none;">
                <i class="fas fa-check-circle"></i> Your vote has been successfully cast!
            </div>

            <!-- Position Navigation -->
            <div class="position-nav" id="position-nav"></div>

            <div id="candidate-selection">
                <div id="position-container"></div>
                <div style="padding:16px; text-align:center;">
                    <button class="vote-confirm" id="confirm-vote" disabled>Confirm Vote</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast" id="toast" style="display:none;">
        <i class="fas fa-check-circle"></i>
        <span id="toast-message">Vote cast successfully!</span>
    </div>

    

<script>
  
/* ---------- CONFIG (adjust PROJECT_BASE if your project root is different) ---------- */
const PROJECT_BASE = '/eBotoni/eBotoni'; // change only if needed
const GET_ACTIVE_ELECTIONS_URL = window.location.origin + PROJECT_BASE + '/student/get_active_elections.php';
const CANDIDATES_LIST_URL = window.location.origin + PROJECT_BASE + '/student%20org/get_candidates.php';
const POSITIONS_LIST_URL = window.location.origin + PROJECT_BASE + '/student/position_list.php';
const GET_ELECTION_CANDIDATES_FALLBACK = window.location.origin + PROJECT_BASE + '/student/getElectionCandidates.php';
const CAST_VOTE_URL = window.location.origin + PROJECT_BASE + '/student/castVote.php';
const GET_STUDENT_API = window.location.origin + PROJECT_BASE + '/student/getStudent.php';
const SIDEBAR_AVATAR_ID = 'sidebarAvatar';
const SIDEBAR_NAME_ID = 'sidebarName';
const SIDEBAR_ROLE_ID = 'sidebarRole';
const TOP_AVATAR_ID = 'topAvatar';
const TOP_NAME_ID = 'topName';
const TOP_ROLE_ID = 'topRole';

/* ---------- session user (PHP-injected) ---------- */
const CURRENT_USER = {
  id: <?php echo $studentId ? json_encode((string)$studentId) : 'null'; ?>,
  name: <?php echo json_encode($studentName); ?>
};

/* ---------- state ---------- */
let elections = [];
let currentElection = null;
let selectedCandidates = {}; // { positionNameOrId: candidateId }
let positionsMap = {}; // Position_ID -> Position_Name

// Track canonical positions shown in modal (used to determine when all positions are selected)
let currentPositions = [];

// localStorage key for voting history per user
function votingHistoryKey() {
  return CURRENT_USER && CURRENT_USER.id ? `votingHistory_user_${CURRENT_USER.id}` : 'votingHistory_guest';
}
function toggleDropdown(){
    const d = document.getElementById('dropdown');
    if (!d) return;
    d.style.display = (d.style.display === 'block') ? 'none' : 'block';
}
function logoutUser(){
    fetch('../php/logout.php', { method: 'POST' }).finally(()=> window.location.href = '/');
}
// small helper to decode HTML entities (since we use escapeHtml when injecting)
function decodeHtml(html) {
  const txt = document.createElement('textarea');
  txt.innerHTML = html || '';
  return txt.value;
}

/* ---------- DOM refs ---------- */
const electionsContainer = document.getElementById('elections-container');
const votingModal = document.getElementById('voting-modal');
const modalElectionTitle = document.getElementById('modal-election-title');
const positionContainer = document.getElementById('position-container');
const positionNav = document.getElementById('position-nav');
const confirmVoteBtn = document.getElementById('confirm-vote');
const voteSuccess = document.getElementById('vote-success');
const toast = document.getElementById('toast');
const toastMessage = document.getElementById('toast-message');

/* ---------- init ---------- */
async function init() {
  try {
    await loadPositions();

    // load active elections
    const resp = await fetch(GET_ACTIVE_ELECTIONS_URL, { cache: 'no-store', credentials: 'include' });
    if (!resp.ok) {
      const text = await resp.text();
      console.error('Server returned non-OK. Raw body:', text);
      throw new Error('Network error: ' + resp.status);
    }
    let payload;
    try {
      payload = await resp.json();
    } catch (parseErr) {
      const text = await resp.text();
      console.error('Could not parse JSON. Raw server response (first 2000 chars):', text.slice(0,2000));
      throw new Error('Invalid JSON from server. Check PHP endpoint & look for warnings/notices.');
    }
    elections = payload.data || payload.elections || payload.result || payload || [];
    if (!Array.isArray(elections)) elections = [];

    // normalize election shape
    elections = elections.map(raw => ({
      id: raw.Election_ID ?? raw.id ?? raw.election_id ?? raw.ID ?? null,
      title: raw.Title ?? raw.title ?? raw.name ?? '',
      status: (raw.Status ?? raw.status ?? '').toString().toLowerCase(),
      startDate: raw.Start_Date ?? raw.startDate ?? raw.start_date ?? '',
      endDate: raw.End_Date ?? raw.endDate ?? raw.end_date ?? '',
      organization: raw.Organization ?? raw.organization ?? raw.org ?? raw.Org_Name ?? '',
      Org_ID: raw.Org_ID ?? raw.OrgId ?? raw.org_id ?? raw.organization_id ?? null,
      parties: raw.parties || raw.party_list || raw.partyLists || [],
      candidates: Array.isArray(raw.candidates) ? raw.candidates : [],
      userVoted: !!(raw.userVoted || raw.user_voted || raw.voted),
      progress: Number(raw.progress ?? 0),
      timeRemaining: raw.timeRemaining ?? raw.time_remaining ?? ''
    }));

    await loadAllCandidates();

    // If there's a saved voting history in localStorage mark elections as voted accordingly (optional)
    // (This helps preserve "Voted" state across refresh for local-only demo)
    try {
      const histKey = votingHistoryKey();
      const existing = JSON.parse(localStorage.getItem(histKey) || '[]');
      if (Array.isArray(existing)) {
        existing.forEach(h => {
          const idx = elections.findIndex(e => String(e.id) === String(h.electionId));
          if (idx !== -1) elections[idx].userVoted = true;
        });
      }
    } catch (e){
      // ignore localStorage parse errors
    }

    renderElections();
    setupEventListeners();
  } catch (err) {
    console.error('init error:', err);
    electionsContainer.innerHTML = `<div style="padding:20px;color:#e74c3c;">Could not load elections. Check console & server endpoint.</div>`;
  }
}

/* ---------- load positions ---------- */
async function loadPositions() {
  try {
    const res = await fetch(POSITIONS_LIST_URL, { cache: 'no-store', credentials: 'include' });
    if (!res.ok) { positionsMap = {}; return; }
    const json = await res.json();
    const rows = Array.isArray(json) ? json : (Array.isArray(json.data) ? json.data : (json.positions || json.result || []));
    positionsMap = {};
    (rows || []).forEach(r => {
      const id = String(r.Position_ID ?? r.position_id ?? r.id ?? '').trim();
      const name = r.Position_Name ?? r.position_name ?? r.PositionName ?? r.name ?? '';
      if (id) positionsMap[id] = String(name || '').trim();
    });
    console.debug('[vote] positionsMap loaded', positionsMap);
  } catch (err) {
    console.warn('loadPositions error', err);
    positionsMap = {};
  }
}

/* ====== Load ALL candidates and attach to elections using heuristics ====== */
let allCandidatesCount = 0;
async function loadAllCandidates() {
  try {
    console.debug('[vote] loading candidates from', CANDIDATES_LIST_URL);
    const res = await fetch(CANDIDATES_LIST_URL, { cache: 'no-store', credentials: 'include' });
    if (!res.ok) {
      console.warn('[vote] candidates_list fetch not ok', res.status, res.statusText);
      // keep elections as-is (they may have candidates embedded)
      return;
    }
    const json = await res.json();
    console.debug('[vote] raw candidates response:', json);

    // Find the array inside possible shapes
    let allCandidates = [];
    if (Array.isArray(json.data)) allCandidates = json.data;
    else if (Array.isArray(json.candidates)) allCandidates = json.candidates;
    else if (Array.isArray(json)) allCandidates = json;
    else {
      const maybe = Object.values(json).find(v => Array.isArray(v));
      if (Array.isArray(maybe)) allCandidates = maybe;
    }

    // helper to read many possible column name variants
    const read = (obj, ...keys) => {
      for (const k of keys) {
        if (obj == null) continue;
        if (Object.prototype.hasOwnProperty.call(obj, k) && obj[k] !== null && obj[k] !== undefined && String(obj[k]).trim() !== '') {
          return obj[k];
        }
      }
      return '';
    };

    // Normalize each candidate row to stable fields used by UI
    const normalizedAll = (allCandidates || []).map(c => {
      const posId = String(read(c, 'Position_ID', 'position_id', 'PositionId', 'positionId') || '').trim();
      const posNameFromRow = String(read(c, 'Position_Name', 'position_name', 'PositionName') || '').trim();

      // read both year/level and program/course so education contains both
      const yearLevelVal = read(c,
        'YearLevel', 'yearlevel', 'Year_Level', 'year_level', 'Year', 'year');

      const programVal = read(c,
        'Program', 'program', 'Course', 'course', 'ProgramName', 'program_name');

      const experienceVal = read(c,
        'Experience', 'experience', 'Experience_Desc', 'ExperienceDesc', 'exp', 'Exp',
        'achievements', 'Achievements', 'Bio', 'bio');

      const platformVal = read(c, 'Platform', 'platform', 'PlatformText', 'manifesto', 'Manifesto');
      const avatarVal = read(c, 'Avatar', 'avatar', 'photo', 'profile_photo');

      // build education array combining YearLevel and Program (avoid empty / duplicate entries)
      const educationArr = [];
      if (Array.isArray(yearLevelVal)) {
        yearLevelVal.forEach(v => { if (v != null && String(v).trim() !== '') educationArr.push(String(v).trim()); });
      } else if (yearLevelVal) {
        const v = String(yearLevelVal).trim();
        if (v) educationArr.push(v);
      }
      if (Array.isArray(programVal)) {
        programVal.forEach(v => { if (v != null && String(v).trim() !== '' && !educationArr.includes(String(v).trim())) educationArr.push(String(v).trim()); });
      } else if (programVal) {
        const p = String(programVal).trim();
        if (p && !educationArr.includes(p)) educationArr.push(p);
      }

      return {
        raw: c,
        id: Number(read(c, 'Candidate_ID', 'CandidateId', 'id', 'ID') || 0),
        name: String(read(c, 'FullName', 'fullname', 'name') || '').trim(),
        party: String(read(c, 'PartyList', 'Party', 'party', 'party_list') || '').trim(),
        avatar: String(avatarVal || '').trim(),
        platform: String(platformVal || '').trim(),
        education: educationArr,
        achievements: Array.isArray(experienceVal) ? experienceVal : (experienceVal ? [String(experienceVal)] : []),
        position_id: posId || null,
        position_name: posNameFromRow || (posId && positionsMap[posId] ? positionsMap[posId] : ''),
        org_id: read(c, 'Org_ID', 'OrgId', 'org_id') || null,
        org_name: String(read(c, 'Org_Name', 'OrgName', 'org') || '').trim()
      };
    });

    // Build normalized elections for matching
    const normalizedElections = elections.map(e => {
      const orgId = e.Org_ID ?? e.organization_id ?? null;
      const orgName = e.organization ?? e.Org_Name ?? '';
      return {
        __orig: e,
        id: e.id,
        title: e.title,
        organization_id_str: orgId != null ? String(orgId).trim() : '',
        organization_name: (orgName || '').toString().trim().toLowerCase()
      };
    });

    // Assign candidates to elections using heuristics
    normalizedElections.forEach(ne => {
      const matches = normalizedAll.filter(c => {
        if (ne.organization_id_str && c.org_id != null && String(c.org_id).trim() === ne.organization_id_str) return true;
        if (ne.organization_name && c.org_name) {
          if (ne.organization_name === String(c.org_name).trim().toLowerCase()) return true;
        }
        if (ne.title && c.org_name) {
          const t = String(ne.title).toLowerCase();
          const on = String(c.org_name).toLowerCase();
          if (t.includes(on) || on.includes(t)) return true;
        }
        const rawText = Object.values(c.raw || {}).join(' ').toLowerCase();
        if (ne.title && rawText.includes(String(ne.title).toLowerCase())) return true;
        return false;
      });

      ne.__orig.candidates = matches.map(c => ({
        id: c.id,
        name: c.name,
        party: c.party,
        avatar: c.avatar,
        platform: c.platform,
        position_id: c.position_id,
        position_name: c.position_name,
        education: c.education,
        achievements: c.achievements,
        raw: c.raw
      }));
    });

    // write assigned candidates back to elections array
    normalizedElections.forEach(ne => {
      const idx = elections.findIndex(x => String(x.id) === String(ne.id));
      if (idx !== -1) elections[idx].candidates = ne.__orig.candidates || [];
    });

    allCandidatesCount = normalizedAll.length;
  } catch (err) {
    console.error('loadAllCandidates error:', err);
    elections.forEach(e => { if (!Array.isArray(e.candidates)) e.candidates = []; });
  }
}

/* ---------- render ---------- */
function renderElections() {
  electionsContainer.innerHTML = '';
  elections.forEach(e => {
    e.status = (e.status || '').toString().toLowerCase();
    if (typeof e.progress === 'undefined') e.progress = 0;
    if (!e.timeRemaining) e.timeRemaining = '';
  });

  const activeElections = elections.filter(e => e.status === 'ongoing' || e.status === 'active');

  if (activeElections.length === 0) {
    electionsContainer.innerHTML = `
      <div style="grid-column:1 / -1; text-align:center; padding:40px; background:white; border-radius:12px; box-shadow:0 5px 15px rgba(0,0,0,0.08);">
        <i class="fas fa-calendar-times" style="font-size:3rem; color:#bdc3c7; margin-bottom:20px;"></i>
        <h3 style="color:#7f8c8d; margin-bottom:10px;">No Active Elections</h3>
        <p style="color:#95a5a6;">There are currently no active elections. Please check back later.</p>
      </div>
    `;
    return;
  }

  activeElections.forEach(e => {
    const card = createElectionCard(e);
    electionsContainer.appendChild(card);
  });
}

function createElectionCard(election) {
  const card = document.createElement('div');
  card.className = 'election-card';

  const statusText = election.status ? (String(election.status).charAt(0).toUpperCase() + String(election.status).slice(1)) : '';
  const votedClass = election.userVoted ? 'voted' : (election.status !== 'ongoing' ? 'disabled' : '');
  const buttonLabel = election.userVoted ? '<i class="fas fa-check"></i> Voted' : (election.status === 'ongoing' ? 'Vote Now' : (election.status === 'upcoming' ? 'Upcoming' : 'Completed'));

  const candidateCount = (Array.isArray(election.candidates) && election.candidates.length) || 0;

  card.innerHTML = `
    <div class="election-header">
      <h3 class="election-title">${escapeHtml(election.title || '')}</h3>
      <span class="election-status status-${escapeHtml(election.status || '')}">${escapeHtml(statusText)}</span>
    </div>
    <div class="election-details">
      <div class="detail-item">
        <div class="detail-icon"><i class="fas fa-calendar-alt"></i></div>
        <div class="detail-content">
          <h4>Start Date & Time</h4>
          <p>${escapeHtml(election.startDate || '')}</p>
        </div>
      </div>
      <div class="detail-item">
        <div class="detail-icon"><i class="fas fa-calendar-check"></i></div>
        <div class="detail-content">
          <h4>End Date & Time</h4>
          <p>${escapeHtml(election.endDate || '')}</p>
        </div>
      </div>
      <div class="detail-item">
        <div class="detail-icon"><i class="fas fa-users"></i></div>
        <div class="detail-content">
          <h4>Candidates</h4>
          <p>${candidateCount}</p>
        </div>
      </div>
      <div class="progress-container">
        <div class="progress-bar">
          <div class="progress" style="width:${Number(election.progress || 0)}%"></div>
        </div>
        <p class="time-remaining">${escapeHtml(election.timeRemaining || '')}</p>
      </div>
      <button class="vote-button ${votedClass}" data-election-id="${Number(election.id || 0)}" ${election.userVoted ? 'disabled' : ''}>
        ${buttonLabel}
      </button>
    </div>
  `;
  return card;
}

/* ---------- events ---------- */
function setupEventListeners() {
  document.addEventListener('click', (ev) => {
    const btn = ev.target.closest && ev.target.closest('.vote-button');
    if (btn && !btn.classList.contains('disabled') && !btn.classList.contains('voted')) {
      const electionId = Number(btn.getAttribute('data-election-id'));
      if (!Number.isNaN(electionId)) openVotingModal(electionId);
    }
  });

  const closeBtn = document.querySelector('.close-modal');
  if (closeBtn) closeBtn.addEventListener('click', closeVotingModal);

  votingModal.addEventListener('click', (e) => {
    if (e.target === votingModal) closeVotingModal();
  });

document.addEventListener('click', function(e){
    if (e.target.id === 'confirm-vote') {
        onConfirmVote();
    }
});

}

/* ---------- modal / selection ---------- */
async function openVotingModal(electionId) {
  console.log('[vote] openVotingModal electionId=', electionId);
  currentElection = elections.find(e => Number(e.id) === Number(electionId));
  console.log('[vote] currentElection=', currentElection);

  if (!currentElection) {
    showToast('Election not found', 'error');
    return;
  }

  // Title
  modalElectionTitle.textContent = `Vote for ${currentElection.title || ''}`;

  // reset state
  selectedCandidates = {};
  confirmVoteBtn.disabled = true;
  voteSuccess.style.display = 'none';

  // if candidates missing or empty -> try fallback to fetch from server (optional)
  let candidatesList = currentElection.candidates;
  if (!Array.isArray(candidatesList) || candidatesList.length === 0) {
    console.warn('[vote] currentElection.candidates is empty. Trying fallback fetch for candidates...');
    try {
      // fallback endpoint expects ?election_id=...
      const fallbackResp = await fetch(GET_ELECTION_CANDIDATES_FALLBACK + '?election_id=' + encodeURIComponent(electionId), { credentials: 'include' });
      if (fallbackResp.ok) {
        const fallbackJson = await fallbackResp.json();
        candidatesList = fallbackJson.candidates || fallbackJson.data || fallbackJson || [];
        if (Array.isArray(candidatesList)) {
          // normalize shape and resolve position name via positionsMap if needed
          candidatesList = candidatesList.map(c => {
            const posId = String(c.Position_ID ?? c.position_id ?? '').trim();
            const posNameRow = c.position_name ?? c.Position_Name ?? c.PositionName ?? '';

            const yearLevelVal = c.YearLevel ?? c.yearlevel ?? c.Year_Level ?? c.year_level ?? c.Year ?? '';
            const programVal = c.Program ?? c.program ?? c.Course ?? c.course ?? '';

            // build education array combining YearLevel and Program
            const edu = [];
            if (Array.isArray(yearLevelVal)) {
              yearLevelVal.forEach(v => { if (v != null && String(v).trim() !== '') edu.push(String(v).trim()); });
            } else if (yearLevelVal) {
              const v = String(yearLevelVal).trim();
              if (v) edu.push(v);
            }
            if (Array.isArray(programVal)) {
              programVal.forEach(v => { if (v != null && String(v).trim() !== '' && !edu.includes(String(v).trim())) edu.push(String(v).trim()); });
            } else if (programVal) {
              const p = String(programVal).trim();
              if (p && !edu.includes(p)) edu.push(p);
            }

            const experienceVal = c.Experience ?? c.experience ?? c.Experience_Desc ?? c.ExperienceDesc ?? '';

            return {
              id: c.Candidate_ID ?? c.id ?? c.ID ?? 0,
              name: c.FullName ?? c.fullname ?? c.name ?? '',
              party: c.PartyList ?? c.Party ?? c.party ?? '',
              avatar: c.Avatar ?? c.avatar ?? '',
              platform: c.Platform ?? c.platform ?? '',
              position: posNameRow || positionsMap[posId] || (posId ? `Position ${posId}` : 'Unknown'),
              position_id: posId || null,
              education: Array.isArray(edu) ? edu : (edu ? [String(edu)] : []),
              achievements: Array.isArray(experienceVal) ? experienceVal : (experienceVal ? [String(experienceVal)] : []),
              raw: c
            };
          });
          currentElection.candidates = candidatesList;
        }
        console.log('[vote] fallback candidates loaded:', candidatesList.length);
      } else {
        console.warn('[vote] fallback fetch failed: ', fallbackResp.status);
      }
    } catch (err) {
      console.error('[vote] fallback fetch error', err);
    }
  }

  // Group candidates by position (safely). Use either position_name or position_id -> positionsMap
  const positions = {};
  (currentElection.candidates || []).forEach(candidate => {
    let pos = candidate.position || candidate.Position || candidate.position_name || candidate.Position_Name || '';
    if (!pos) {
      const posId = String(candidate.position_id ?? candidate.Position_ID ?? '').trim();
      if (posId && positionsMap[posId]) pos = positionsMap[posId];
    }
    pos = (pos || 'Unknown').toString();

    if (!positions[pos]) positions[pos] = [];
    candidate.avatar = normalizeAvatarPath(candidate.avatar);
    candidate.education = candidate.education ?? (candidate.YearLevel ? (Array.isArray(candidate.YearLevel) ? candidate.YearLevel : [candidate.YearLevel]) : []);
    candidate.achievements = candidate.achievements ?? (candidate.Experience ? (Array.isArray(candidate.Experience) ? candidate.Experience : [candidate.Experience]) : []);
    positions[pos].push(candidate);
  });

  // Clear containers
  positionContainer.innerHTML = '';
  positionNav.innerHTML = '';

  // If still no positions, show helpful message
  const positionKeys = Object.keys(positions);
  if (positionKeys.length === 0) {
    positionContainer.innerHTML = `
      <div style="padding:24px; text-align:center; color:#666;">
        <p style="margin:6px 0 0;">No candidates available for this election yet.</p>
        <p style="font-size:13px; margin:6px 0 0; color:#999;">If this is wrong, check your server API or candidate records.</p>
      </div>
    `;
    votingModal.style.display = 'flex';
    return;
  }

  // --- Sort positions with desired hierarchy: President first, Vice second, then secretary/treasurer/auditor, then alphabetically ---
  const priority = pos => {
    const p = (pos || '').toLowerCase();
    if (p.includes('president') && !p.includes('vice')) return 0;
    if (p.includes('vice')) return 1;
    if (p.includes('secretary')) return 2;
    if (p.includes('treasurer')) return 3;
    if (p.includes('auditor')) return 4;
    return 5;
  };
  const orderedPositions = positionKeys.slice().sort((a,b) => {
    const pa = priority(a), pb = priority(b);
    if (pa !== pb) return pa - pb;
    return a.localeCompare(b, undefined, { sensitivity: 'base' });
  });

  // expose current positions for select/deselect logic
// expose current positions for select/deselect logic (trim to avoid stray whitespace)
currentPositions = orderedPositions.map(p => (p || '').toString().trim());
console.debug('[vote] currentPositions:', currentPositions);


  // Add party information if available
  if (currentElection.parties && currentElection.parties.length > 0) {
    const partiesSection = document.createElement('div');
    partiesSection.className = 'parties-section';
    let partiesHTML = '';
    currentElection.parties.forEach(party => {
      const logoPath = normalizeAvatarPath(party.logo);
      partiesHTML += `
        <div class="party-info">
          <div class="party-logo">
            <img src="${logoPath}" alt="${party.name || ''}">
          </div>
          <div class="party-details">
            <h3>${escapeHtml(party.name || '')}</h3>
            <p>"${escapeHtml(party.slogan || '')}"</p>
            <div class="party-stats">
              <div class="party-stat"><div class="party-stat-value">${escapeHtml(String(party.candidatesCount || ''))}</div><div class="party-stat-label">Candidates</div></div>
              <div class="party-stat"><div class="party-stat-value">${escapeHtml(String(party.established || ''))}</div><div class="party-stat-label">Est.</div></div>
              <div class="party-stat"><div class="party-stat-value">${escapeHtml(String(party.scope || ''))}</div><div class="party-stat-label">Scope</div></div>
            </div>
          </div>
        </div>
      `;
    });
    partiesSection.innerHTML = partiesHTML;
    positionContainer.appendChild(partiesSection);
  }

  // Build nav and position sections using the orderedPositions array
  let positionIndex = 0;
  for (const position of orderedPositions) {
    const candidates = positions[position] || [];
    const positionClass = getPositionClass(position);
    const positionIcon = getPositionIcon(position);

    // nav
    const navItem = document.createElement('div');
    navItem.className = `position-nav-item ${positionClass} ${positionIndex === 0 ? 'active' : ''}`;
    // set actual data-position attribute (raw string)
    navItem.setAttribute('data-position', position);
    navItem.innerHTML = `<i class="fas ${positionIcon}"></i><span>${escapeHtml(position)}</span>`;
    navItem.addEventListener('click', () => {
      document.querySelectorAll('.position-nav-item').forEach(item => item.classList.remove('active'));
      navItem.classList.add('active');
      const positionElement = document.getElementById(`position-${position.replace(/\s+/g, '-')}`);
      if (positionElement) positionElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
    positionNav.appendChild(navItem);

    // position group
    const positionGroup = document.createElement('div');
    positionGroup.className = 'position-group';
    positionGroup.id = `position-${position.replace(/\s+/g, '-')}`;

    const positionTitle = document.createElement('h3');
    positionTitle.className = `position-title ${positionClass}`;
    positionTitle.innerHTML = `<i class="fas ${positionIcon} position-icon"></i><span>${escapeHtml(position)}</span>`;
    positionGroup.appendChild(positionTitle);

    // candidates
    candidates.forEach(candidate => {
    // build a small prioritized list of avatar candidates (candidate.avatar should already be normalized)
const primaryAvatar = candidate.avatar ? String(candidate.avatar).trim() : '';
const filename = primaryAvatar.split('/').pop() || '';
const guesses = [];

// 1) prefer normalized avatar (likely absolute)
if (primaryAvatar) guesses.push(primaryAvatar);

// 2) try common upload folder guess using filename (if filename exists)
if (filename) {
  // encoded filename to be safe with spaces/special chars
  const enc = encodeURIComponent(filename);
  guesses.push(window.location.origin + PROJECT_BASE + '/student%20org/uploads/candidates/' + enc);
  guesses.push(window.location.origin + PROJECT_BASE + '/uploads/candidates/' + enc);
}

// 3) final fallback to default avatar
guesses.push(window.location.origin + PROJECT_BASE + '/assests/default-avatar.jpg');

// de-duplicate preserving order
const avatarCandidates = Array.from(new Set(guesses.filter(Boolean)));
const avatarDataAttr = escapeAttr(JSON.stringify(avatarCandidates));
const firstSrc = escapeAttr(avatarCandidates[0] || (window.location.origin + PROJECT_BASE + '/assests/default-avatar.jpg'));

const candidateCard = document.createElement('div');
candidateCard.className = 'candidate-detail-card';

candidateCard.innerHTML = `
  <div class="candidate-header">
    <div class="candidate-avatar-large" style="width:96px;height:96px;border-radius:50%;overflow:hidden;border:3px solid #ffd54f;flex:0 0 96px;">
      <img
        src="${firstSrc}"
        alt="${escapeAttr(candidate.name || '')}"
        data-cands='${avatarDataAttr}'
        data-idx="0"
        style="width:100%;height:100%;object-fit:cover;display:block;"
        onerror="(function(img){ try{ const list = JSON.parse(img.dataset.cands||'[]'); let idx = Number(img.dataset.idx||0) + 1; const next = list[idx]; if(next){ img.dataset.idx = idx; img.src = next; } else { img.onerror=null; img.src='${escapeAttr(window.location.origin + PROJECT_BASE + '/assests/default-avatar.jpg')}'; } } catch(e){ img.onerror=null; img.src='${escapeAttr(window.location.origin + PROJECT_BASE + '/assests/default-avatar.jpg')}'; } })(this)"
      />
    </div>

    <div class="candidate-main-info" style="margin-left:12px;">
      <h2 style="margin:0 0 6px 0;">${escapeHtml(candidate.name || '')}</h2>
      <div class="position" style="color:#666">${escapeHtml(position)}</div>
      <div class="party" style="margin-top:6px;">${escapeHtml(candidate.party || '')}</div>
    </div>
  </div>

  <div class="candidate-details">
    <div class="detail-section">
      <h3>Education</h3>
      <ul>${(Array.isArray(candidate.education) ? candidate.education.map(i => `<li>${escapeHtml(i)}</li>`).join('') : '')}</ul>
    </div>

    <div class="detail-section">
      <h3>Platform</h3>
      <ul>${(Array.isArray(candidate.platform) ? candidate.platform.map(i => `<li>${escapeHtml(i)}</li>`).join('') : escapeHtml(candidate.platform || ''))}</ul>
    </div>

    <div class="detail-section">
      <h3>Key Achievements</h3>
      <ul>${(Array.isArray(candidate.achievements) ? candidate.achievements.map(i => `<li>${escapeHtml(i)}</li>`).join('') : '')}</ul>
    </div>
  </div>

  <div class="vote-section">
    <button class="select-candidate-btn" data-position="${escapeAttr(position)}" data-candidate-id="${Number(candidate.id || 0)}">Select Candidate</button>
  </div>
`;

positionGroup.appendChild(candidateCard);

    });

    positionContainer.appendChild(positionGroup);
    positionIndex++;
  }

  // wire up select buttons (use currentTarget to avoid icon/text clicks causing issues)
  positionContainer.querySelectorAll('.select-candidate-btn').forEach(button => {
    button.addEventListener('click', (e) => {
      const btn = e.currentTarget;
      // decodeHtml in case data-position was escaped into entities
      const rawPos = btn.getAttribute('data-position') || '';
      const position = decodeHtml(rawPos).trim();
      const candidateId = parseInt(btn.getAttribute('data-candidate-id'), 10);
      selectCandidate(position, candidateId, btn);
    });
  });

  // finally show modal
  votingModal.style.display = 'flex';
}
/* ====== Student profile loader (unchanged) ====== */
async function loadStudentProfileAndUpdateUI() {
  const defaultAvatar = window.location.origin + PROJECT_BASE + '/assests/default-avatar.jpg';
  try {
    const res = await fetch(GET_STUDENT_API, { cache: 'no-store', credentials: 'include' });
    if (!res.ok) return applyFallback();
    const text = (await res.text()).trim();
    if (!text) return applyFallback();
    let json;
    try { json = JSON.parse(text); } catch (err) { console.warn('getStudent non-json:', text.slice(0,200)); return applyFallback(); }
    let s = json.student ?? json.data?.student ?? null;
    if (!s) {
      // if json itself looks like student object, use it
      if (json.Name || json.FullName || json.Student_ID || json.Email) s = json;
      else {
        const maybe = Object.values(json).find(v => v && typeof v === 'object' && (v.Name || v.FullName || v.Student_ID || v.Email));
        if (maybe) s = maybe;
      }
    }
    if (!s) return applyFallback();

    const name = s.Name ?? s.name ?? s.FullName ?? s.fullname ?? '';
    const photo = s.profile_photo_url ?? s.profile_photo ?? s.Avatar ?? s.avatar ?? s.photo ?? '';
    const role = s.role ?? 'Student';
    const id = s.id ?? s.Student_ID ?? s.student_id ?? null;

    let avatarUrl = defaultAvatar;
    if (photo && String(photo).trim()) {
      const p = String(photo).trim();
      if (/^https?:\/\//i.test(p)) avatarUrl = p;
      else {
        let cleaned = p.replace(/^(\.\.\/)+/, '').replace(/^\/+/, '');
        avatarUrl = window.location.origin + PROJECT_BASE + '/' + cleaned;
      }
    }

    const sidebarAvatar = document.getElementById(SIDEBAR_AVATAR_ID);
    const sidebarName = document.getElementById(SIDEBAR_NAME_ID);
    const sidebarRole = document.getElementById(SIDEBAR_ROLE_ID);
    const topAvatar = document.getElementById(TOP_AVATAR_ID);
    const topName = document.getElementById(TOP_NAME_ID);
    const topRole = document.getElementById(TOP_ROLE_ID);

    if (sidebarAvatar && sidebarAvatar.tagName === 'IMG') { sidebarAvatar.src = avatarUrl; sidebarAvatar.alt = (name||'User') + ' avatar'; }
    if (sidebarName) sidebarName.textContent = name || 'Student';
    if (sidebarRole) sidebarRole.textContent = role || 'Student';
    if (topAvatar && topAvatar.tagName === 'IMG') { topAvatar.src = avatarUrl; topAvatar.alt = (name||'User') + ' avatar'; }
    if (topName) topName.textContent = name || 'Student';
    if (topRole) topRole.textContent = role || 'Student';

    window.CURRENT_USER = { id, name, avatar: avatarUrl, email: s.Email ?? s.email ?? null };
  } catch (err) {
    console.error('loadStudentProfile error', err);
    applyFallback();
  }

  function applyFallback() {
    const sidebarAvatar = document.getElementById(SIDEBAR_AVATAR_ID);
    const topAvatar = document.getElementById(TOP_AVATAR_ID);
    if (sidebarAvatar && sidebarAvatar.tagName === 'IMG' && (!sidebarAvatar.src || sidebarAvatar.src.length === 0)) sidebarAvatar.src = window.location.origin + PROJECT_BASE + '/assests/default-avatar.jpg';
    if (topAvatar && topAvatar.tagName === 'IMG' && (!topAvatar.src || topAvatar.src.length === 0)) topAvatar.src = window.location.origin + PROJECT_BASE + '/assests/default-avatar.jpg';
  }
}

// call it once on page load
document.addEventListener('DOMContentLoaded', loadStudentProfileAndUpdateUI);

function selectCandidate(position, candidateId, buttonEl) {
  if (!position) position = 'Unknown';
  // canonical position key (keep original string trimmed for readability)
  const pos = position.toString().trim();

  console.debug('[vote] selectCandidate called for pos=', pos, 'id=', candidateId);

  // If button is already selected -> toggle off (unselect)
  const alreadySelected = buttonEl.classList.contains('selected');
  if (alreadySelected) {
    // unselect all for this position
    positionContainer.querySelectorAll(`.select-candidate-btn[data-position]`).forEach(b => {
      const bp = decodeHtml(b.getAttribute('data-position') || '').trim();
      if (bp === pos) {
        b.classList.remove('selected');
        b.textContent = 'Select Candidate';
      }
    });
    // remove the selected entry using any matching key (trimmed)
    Object.keys(selectedCandidates).forEach(k => {
      if (k.toString().trim() === pos) delete selectedCandidates[k];
    });
  } else {
    // deselect all for this position then select clicked
    positionContainer.querySelectorAll(`.select-candidate-btn[data-position]`).forEach(b => {
      const bp = decodeHtml(b.getAttribute('data-position') || '').trim();
      if (bp === pos) {
        b.classList.remove('selected');
        b.textContent = 'Select Candidate';
      }
    });

    buttonEl.classList.add('selected');
    buttonEl.textContent = 'Selected ✓';

    // store using trimmed position key
    selectedCandidates[pos] = candidateId;
  }

  // NEW robust check: require that number of unique positions selected >= number of positions shown
  // This avoids subtle string mismatches and still blocks Confirm until all positions are chosen.
  const uniqueSelectedCount = Object.keys(selectedCandidates).filter(k => (k || '').toString().trim() !== '').length;
  const requiredCount = currentPositions.length;
  const allSelected = requiredCount > 0 && (uniqueSelectedCount >= requiredCount);

  console.debug('[vote] selectedCandidates keys:', Object.keys(selectedCandidates), 'uniqueSelectedCount=', uniqueSelectedCount, 'requiredCount=', requiredCount, 'allSelected=', allSelected);

  confirmVoteBtn.disabled = !allSelected;
}

async function onConfirmVote() {
  console.debug('[vote] onConfirmVote invoked, selectedCandidates=', selectedCandidates);

  if (!CURRENT_USER.id) {
    showToast('You must be logged in to vote', 'error');
    return;
  }
  if (!currentElection) {
    showToast('No election selected', 'error');
    return;
  }

  // build payload for server: use normalized structure (you can adapt server keys if needed)
// convert object {position: candidateId} into array [candidateId, ...]
// convert object {position: candidateId} → array [candidateId, ...]
// build payload for server: send array of candidate IDs (server expects numeric IDs)

// build payload for server: send array of candidate IDs (server expects numeric IDs)
const selectionArray = Object.values(selectedCandidates).map(v => Number(v));

const payload = {
    election_id: Number(currentElection.id),
    student_id: Number(CURRENT_USER.id),
    selections: selectionArray
};

console.log("[vote] sending payload (array):", payload);




  confirmVoteBtn.disabled = true;

  // Attempt server save, but proceed regardless so UX always records locally
  let serverOk = false;
  try {
    const resp = await fetch(CAST_VOTE_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
      cache: 'no-store',
      credentials: 'include'
    });
    if (resp.ok) {
      try {
        const result = await resp.json();
        if (result.status === 'success' || result.success === true) serverOk = true;
      } catch (e) {
        // non-json or parse error -> still proceed
      }
    } else {
      console.warn('cast vote non-ok status', resp.status);
    }
  } catch (err) {
    console.warn('cast vote network error', err);
  }

  // Build readable selections mapping position -> { id, name }
  const readableSelections = {};
  (currentElection.candidates || []).forEach(c => {
    // determine the position string that matches selectedCandidates keys
    const posRaw = c.position || c.Position || c.position_name || c.Position_Name || 'Unknown';
    if (selectedCandidates[posRaw] && Number(selectedCandidates[posRaw]) === Number(c.id || c.Candidate_ID || c.ID || 0)) {
      readableSelections[posRaw] = { id: c.id || c.Candidate_ID || c.ID || 0, name: c.name || c.FullName || c.fullname || '', party: c.party || c.PartyList || '' };
    }
  });

  // If readableSelections is empty (because candidate objects didn't match), try alternate matching by id across candidates
  if (Object.keys(readableSelections).length === 0) {
    // build id -> candidate map
    const idMap = {};
    (currentElection.candidates || []).forEach(c => { idMap[String(c.id || c.Candidate_ID || c.ID || '')] = c; });
    Object.entries(selectedCandidates).forEach(([pos, cid]) => {
      const candidate = idMap[String(cid)];
      readableSelections[pos] = { id: cid, name: candidate ? (candidate.name || candidate.FullName || '') : '', party: candidate ? (candidate.party || '') : '' };
    });
  }

  // Timestamp ISO for precise time
  const timestampISO = new Date().toISOString();

  // Save locally to per-user voting history (localStorage)
  try {
    const key = votingHistoryKey();
    const prev = JSON.parse(localStorage.getItem(key) || '[]');
    const entry = {
      electionId: currentElection.id,
      electionTitle: currentElection.title,
      selections: readableSelections,
      selectionsRaw: selectedCandidates,
      timestamp: timestampISO,
      serverSaved: serverOk
    };
    prev.push(entry);
    localStorage.setItem(key, JSON.stringify(prev));
  } catch (e) {
    console.warn('Could not save voting history locally', e);
  }

  // Mark as voted in UI state
  currentElection.userVoted = true;

  // Show success inside modal
  voteSuccess.style.display = 'block';

  // Show toast
  showToast('Your vote has been successfully cast!', 'success');

  // After 1.5s, close modal and update election list
  setTimeout(() => {
    closeVotingModal();
    renderElections();
  }, 1500);
}

/* ---------- helpers ---------- */
function normalizeAvatarPath(avatar) {
  const defaultAvatar = window.location.origin + PROJECT_BASE + '/assests/default-avatar.jpg';
  if (!avatar) return defaultAvatar;
  const s = String(avatar).trim();
  if (!s) return defaultAvatar;
  if (/^https?:\/\//i.test(s)) return s;
  // remove leading slashes
  const cleaned = s.replace(/^\/+/, '');
  // base path from PROJECT_BASE
  const base = (window.location.origin + PROJECT_BASE).replace(/\/+$/, '');
  return `${base}/${cleaned}`;
}

function closeVotingModal() {
  votingModal.style.display = 'none';
  positionContainer.innerHTML = '';
  positionNav.innerHTML = '';
  selectedCandidates = {};
  currentElection = null;
  currentPositions = [];
  confirmVoteBtn.disabled = true;
  voteSuccess.style.display = 'none';
}

function showToast(message, type = 'success') {
  toastMessage.textContent = message;
  toast.style.display = 'block';
  toast.className = 'toast ' + (type === 'error' ? 'error' : 'success');
  setTimeout(() => { toast.style.display = 'none'; }, 3000);
}

function getPositionClass(position) {
  const p = (position || '').toLowerCase();
  if (p.includes('president') && !p.includes('vice')) return 'president';
  if (p.includes('vice')) return 'vice-president';
  if (p.includes('secretary')) return 'secretary';
  if (p.includes('treasurer')) return 'treasurer';
  if (p.includes('auditor')) return 'auditor';
  return '';
}
function getPositionIcon(position) {
  const p = (position || '').toLowerCase();
  if (p.includes('president') && !p.includes('vice')) return 'fa-crown';
  if (p.includes('vice')) return 'fa-user-tie';
  if (p.includes('secretary')) return 'fa-clipboard-list';
  if (p.includes('treasurer')) return 'fa-money-bill-wave';
  if (p.includes('auditor')) return 'fa-chart-bar';
  return 'fa-user';
}

function escapeHtml(s) {
  if (s == null) return '';
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}
function escapeAttr(s) { return escapeHtml(s); }





/* ---------- start ---------- */
init();
document.addEventListener('click', function(e){
    if (e.target.id === 'confirm-vote') {
        console.log('[vote] Confirm Vote clicked');
        onConfirmVote();
    }
});



</script>
<script>
const GET_STUDENT_API = '/eBotoni/eBotoni/student/getStudent.php'; // adjust if your path differs

async function loadStudentProfile() {
  const avatarEl = document.getElementById('sidebarAvatar');
  const nameEl   = document.getElementById('sidebarName');
  const roleEl   = document.getElementById('sidebarRole');

  try {
    const res = await fetch(GET_STUDENT_API, { cache: 'no-store', credentials: 'same-origin' });
    if (!res.ok) {
      console.warn('getStudent.php returned non-OK:', res.status);
      return;
    }

    // read as text first so we can log/diagnose stray HTML/warnings before JSON.parse
    const txt = (await res.text()).trim();
    let data;
    try {
      data = JSON.parse(txt);
    } catch (err) {
      console.warn('getStudent.php returned non-JSON or had extra output (first 1000 chars):', txt.slice(0,1000));
      return;
    }

    if (!data || data.status !== 'success' || !data.student) {
      console.warn('getStudent response missing student object:', data);
      return;
    }

    const s = data.student;

    // tolerant getters, handle different property names
    const name  = s.Name ?? s.name ?? s.FullName ?? s.fullname ?? '';
    const photo = s.profile_photo_url ?? s.profile_photo ?? s.Avatar ?? s.avatar ?? null;
    const role  = 'Student'; // or use s.role if your API returns it

    // build avatar URL: if server returns absolute URL use it, else create relative path
    let avatarUrl = '../assests/default-avatar.jpg';
    if (photo && String(photo).trim() !== '') {
      const p = String(photo).trim();
      if (/^https?:\/\//i.test(p)) avatarUrl = p;
      else {
        // if `photo` is a relative path stored in DB (like uploads/avatars/1.jpg)
        // join with project base (adjust if needed)
        const base = window.location.origin + '/eBotoni/eBotoni';
        const cleaned = p.replace(/^\/+/, '');
        avatarUrl = base + '/' + cleaned;
      }
    }

    if (avatarEl && avatarEl.tagName.toLowerCase() === 'img') {
      avatarEl.src = avatarUrl;
      avatarEl.alt = (name || 'User') + ' avatar';
    }
    if (nameEl) nameEl.textContent = name || 'Student';
    if (roleEl) roleEl.textContent = role;

    // Optional: expose current user globally
    window.CURRENT_USER = { id: s.id ?? s.Student_ID ?? s.student_id ?? null, name: name, email: s.email ?? s.Email ?? null };

  } catch (err) {
    console.error('Error loading student profile:', err);
  }
}

// run on load
document.addEventListener('DOMContentLoaded', loadStudentProfile);
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
            --president-color: #8B0000;
            --vice-president-color: #006400;
            --secretary-color: #4B0082;
            --treasurer-color: #8B4513;
            --auditor-color: #2F4F4F;
        }

        body {
            background-color: var(--ustp-light-gray);
            color: var(--ustp-dark-gray);
            line-height: 1.6;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
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

        .dashboard-title {
            font-size: 1.5rem;
            margin-bottom: 25px;
            color: var(--ustp-blue);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .dashboard-title i {
            color: var(--ustp-blue);
        }

        .elections-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 25px;
        }

        /* Enhanced Election Cards */
        .election-card {
            background: var(--ustp-white);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
            position: relative;
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

        .status-ongoing {
            background-color: rgba(255, 255, 255, 0.2);
            color: var(--ustp-white);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .status-completed {
            background-color: rgba(149, 165, 166, 0.2);
            color: #7f8c8d;
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

        .vote-button {
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

        .vote-button:hover {
            background: linear-gradient(135deg, #002244 0%, #0055aa 100%);
            box-shadow: 0 5px 15px rgba(0, 51, 102, 0.4);
            transform: translateY(-2px);
        }

        .vote-button:active {
            transform: translateY(0);
        }

        .vote-button.disabled {
            background: #bdc3c7;
            color: #7f8c8d;
            cursor: not-allowed;
            pointer-events: none;
            box-shadow: none;
        }

        .vote-button.voted {
            background: var(--ustp-green);
        }

        footer {
            text-align: center;
            margin-top: 40px;
            padding: 20px;
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        /* Toast Notification */
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--ustp-green);
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 1000;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }

        .toast.error {
            background: var(--ustp-red);
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
            max-width: 1000px;
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

        .position-header.president {
            background: linear-gradient(135deg, var(--president-color) 0%, #B22222 100%);
            color: white;
        }

        .position-header.vice-president {
            background: linear-gradient(135deg, var(--vice-president-color) 0%, #228B22 100%);
            color: white;
        }

        .position-header.secretary {
            background: linear-gradient(135deg, var(--secretary-color) 0%, #6A5ACD 100%);
            color: white;
        }

        .position-header.treasurer {
            background: linear-gradient(135deg, var(--treasurer-color) 0%, #A0522D 100%);
            color: white;
        }

        .position-header.auditor {
            background: linear-gradient(135deg, var(--auditor-color) 0%, #708090 100%);
            color: white;
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
            cursor: pointer;
        }

        .candidate-item:hover {
            border-color: var(--ustp-light-blue);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }

        .candidate-item.selected {
            border-color: var(--ustp-blue);
            background-color: rgba(0, 102, 204, 0.05);
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

        .candidate-platform {
            font-size: 0.85rem;
            color: #7f8c8d;
        }

        .vote-confirm {
            background: linear-gradient(135deg, var(--ustp-blue) 0%, var(--ustp-light-blue) 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
        }

        .vote-confirm:hover {
            background: linear-gradient(135deg, #002244 0%, #0055aa 100%);
            box-shadow: 0 5px 15px rgba(0, 51, 102, 0.4);
        }

        .vote-confirm:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
            box-shadow: none;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
            align-items: center;
            gap: 10px;
        }

        /* New Styles for Candidate Detail Cards */
        .candidate-detail-card {
            background: var(--ustp-white);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .candidate-detail-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }

        .candidate-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .candidate-avatar-large {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 20px;
            border: 3px solid var(--ustp-gold);
        }

        .candidate-avatar-large img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .candidate-main-info h2 {
            font-size: 1.5rem;
            color: var(--ustp-blue);
            margin-bottom: 5px;
        }

        .candidate-main-info .position {
            font-size: 1.1rem;
            color: var(--ustp-light-blue);
            margin-bottom: 5px;
            font-weight: 600;
        }

        .candidate-main-info .party {
            display: inline-block;
            padding: 5px 12px;
            background: var(--ustp-light-blue);
            color: white;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .candidate-details {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .detail-section {
            background: var(--ustp-light-gray);
            padding: 15px;
            border-radius: 8px;
        }

        .detail-section h3 {
            font-size: 1.1rem;
            color: var(--ustp-blue);
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #ddd;
        }

        .detail-section ul {
            list-style-type: none;
        }

        .detail-section li {
            padding: 5px 0;
            position: relative;
            padding-left: 15px;
        }

        .detail-section li:before {
            content: "•";
            color: var(--ustp-blue);
            position: absolute;
            left: 0;
        }

        .vote-section {
            margin-top: 20px;
            text-align: center;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .select-candidate-btn {
            background: linear-gradient(135deg, var(--ustp-blue) 0%, var(--ustp-light-blue) 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .select-candidate-btn:hover {
            background: linear-gradient(135deg, #002244 0%, #0055aa 100%);
            box-shadow: 0 5px 15px rgba(0, 51, 102, 0.4);
            transform: translateY(-2px);
        }

        .select-candidate-btn.selected {
            background: var(--ustp-green);
        }

        .position-group {
            margin-bottom: 40px;
        }

        .position-title {
            font-size: 1.4rem;
            color: var(--ustp-blue);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--ustp-gold);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .position-title.president {
            color: var(--president-color);
            border-bottom: 2px solid var(--president-color);
        }

        .position-title.vice-president {
            color: var(--vice-president-color);
            border-bottom: 2px solid var(--vice-president-color);
        }

        .position-title.secretary {
            color: var(--secretary-color);
            border-bottom: 2px solid var(--secretary-color);
        }

        .position-title.treasurer {
            color: var(--treasurer-color);
            border-bottom: 2px solid var(--treasurer-color);
        }

        .position-title.auditor {
            color: var(--auditor-color);
            border-bottom: 2px solid var(--auditor-color);
        }

        .position-icon {
            font-size: 1.2rem;
        }

        /* Party Info */
        .party-info {
            background: var(--ustp-white);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .party-logo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid var(--ustp-gold);
            display: flex;
            justify-content: center;
            align-items: center;
            background: var(--ustp-light-gray);
        }

        .party-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .party-details h3 {
            font-size: 1.3rem;
            color: var(--ustp-blue);
            margin-bottom: 5px;
        }

        .party-details p {
            font-style: italic;
            color: #7f8c8d;
            margin-bottom: 10px;
        }

        .party-stats {
            display: flex;
            gap: 15px;
        }

        .party-stat {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 10px;
            background: var(--ustp-light-gray);
            border-radius: 8px;
            min-width: 80px;
        }

        .party-stat-value {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--ustp-blue);
        }

        .party-stat-label {
            font-size: 0.8rem;
            color: #7f8c8d;
        }

        /* Position Navigation */
        .position-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 30px;
            background: var(--ustp-white);
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .position-nav-item {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .position-nav-item.president {
            background: var(--president-color);
            color: white;
        }

        .position-nav-item.vice-president {
            background: var(--vice-president-color);
            color: white;
        }

        .position-nav-item.secretary {
            background: var(--secretary-color);
            color: white;
        }

        .position-nav-item.treasurer {
            background: var(--treasurer-color);
            color: white;
        }

        .position-nav-item.auditor {
            background: var(--auditor-color);
            color: white;
        }

        .position-nav-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .position-nav-item.active {
            box-shadow: 0 0 0 2px var(--ustp-gold);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .elections-container {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            }
            
            .candidate-details {
                grid-template-columns: 1fr;
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
            
            .candidate-header {
                flex-direction: column;
                text-align: center;
            }
            
            .candidate-avatar-large {
                margin-right: 0;
                margin-bottom: 15px;
            }
            
            .party-info {
                flex-direction: column;
                text-align: center;
            }
            
            .party-stats {
                justify-content: center;
            }
            
            .position-nav {
                justify-content: center;
            }
        }
    </style>
</body>
</html>
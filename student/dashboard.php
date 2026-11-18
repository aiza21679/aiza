<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>USTP Jasaan - Election Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../logout.js" defer></script>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="logo-container">
            <div class="logo">
                <img src="../assests/suffage.png" alt="Logo" />
            </div>
            <div class="logoW"></div>
        </div>

        <div class="nav-menu">
            <a href="dashboard.php" class="active"><i class="fas fa-home"></i><span>Dashboard</span></a>
            <a href="vote.php"><i class="fas fa-vote-yea"></i><span>Active Elections</span></a>
            <a href="votinghistory.php"><i class="fas fa-history"></i><span>Voting History</span></a>
            <a href="candidates.php"><i class="fas fa-user-friends"></i><span>Candidates</span></a>
            <a href="results.php"><i class="fas fa-chart-bar"></i><span>Results</span></a>
            <a href="settings.php"><i class="fas fa-cog"></i><span>Settings</span></a>
        </div>

        <div class="user-profile" onclick="toggleDropdown()">
            <div class="user-avatar">
                <img src="../assests/default-avatar.jpg" alt="User avatar" />
            </div>
            <div class="user-info">
                <h4>stud</h4>
                <p>Student</p>
            </div>
            <div class="user-dropdown" id="dropdown">
                <a href="javascript:void(0)" onclick="logoutUser()">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Dashboard Page -->
        <div id="dashboard-page" class="page-content">
            <div class="header">
                <div class="page-title">
                    <h1>Welcome, Student!</h1>
                    <p>Change doesn't happen by chance, it happens by choice—vote!</p>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-vote-yea"></i>
                    </div>
                    <div class="stat-info">
                        <h3 id="active-elections-count">0</h3>
                        <p>Available Elections</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3 id="votes-cast">0</h3>
                        <p>Votes Cast</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3 id="total-candidates">0</h3>
                        <p>Total Candidates</p>
                    </div>
                </div>
            </div>

            <div class="dashboard-title">
                <i class="fas fa-vote-yea"></i>
                <h2>Available Elections</h2>
            </div>

            <div class="elections-container" id="elections-container">
                <!-- Election cards will be dynamically generated here -->
            </div>
        </div>

        <footer>
            <p>© 2025 eBOTO | USTP Jasaan | All Rights Reserved</p>
        </footer>
    </main>

    <!-- Election Details Modal -->
    <div id="election-details-modal" class="modal" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modal-election-title">Election Details</h2>
                <button class="close-modal">&times;</button>
            </div>
            <div id="election-details-content">
                <!-- Election details will be dynamically generated here -->
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast" id="toast" style="opacity:0;">
        <i class="fas fa-check-circle"></i>
        <span id="toast-message">Vote cast successfully!</span>
    </div>

<script>
/* ====== CONFIG (update paths if your structure differs) ====== */
/* canonical directories (user-provided) */
const PROVIDED_DIR_ENCODED = '/eBotoni/eBotoni/student%20org/uploads/candidates/';
const PROVIDED_DIR_HTTP = location.origin + '/eBotoni/eBotoni/student%20org/uploads/candidates/';

const GET_ELECTIONS_URL = '../student%20org/get_elections.php?active=1';
const CANDIDATES_LIST_URL = '/eBotoni/eBotoni/student%20org/candidates_list.php';

/* ====== ELEMENT SELECTORS ====== */
const electionsContainer = document.getElementById('elections-container');
const electionDetailsModal = document.getElementById('election-details-modal');
const modalElectionTitle = document.getElementById('modal-election-title');
const electionDetailsContent = document.getElementById('election-details-content');
const toast = document.getElementById('toast');
const toastMessage = document.getElementById('toast-message');
const activeElectionsCount = document.getElementById('active-elections-count');
const votesCast = document.getElementById('votes-cast');
const totalCandidates = document.getElementById('total-candidates');

const userAvatarEl = document.querySelector('.user-avatar img');
const userNameEl = document.querySelector('.user-info h4');
const userRoleEl = document.querySelector('.user-info p');

let elections = []; // loaded from DB
let allCandidatesCount = 0; // total candidates count

/* ====== Avatar helper (uses provided canonical upload folder) ====== */
function buildAvatarUrl(raw) {
    if (!raw) return '../assests/default-avatar.jpg';
    const s = String(raw).trim();
    // if it's already a full url, return it
    if (/^https?:\/\//i.test(s)) return s;
    // take filename if path included and construct canonical http url
    const name = s.split('/').pop();
    if (name) return PROVIDED_DIR_HTTP + encodeURIComponent(name);
    return '../assests/default-avatar.jpg';
}

/* ====== INIT ====== */
function init() {
    loadStudentProfile();
    fetchElections();
    setupEventListeners();
}

/* ====== Event delegation ====== */
function setupEventListeners() {
    document.addEventListener('click', (e) => {
        const el = e.target;

        // open election modal
        if (el.classList.contains('view-button')) {
            const electionId = el.dataset.electionId;
            if (electionId) showElectionDetails(electionId);
            return;
        }

        // Note: intentionally removed vote button handling because there are no vote buttons rendered anywhere.
    });

    // close modal
    const closeBtn = document.querySelector('.close-modal');
    if (closeBtn) closeBtn.addEventListener('click', closeElectionDetailsModal);

    electionDetailsModal.addEventListener('click', (e) => {
        if (e.target === electionDetailsModal) closeElectionDetailsModal();
    });

    // click outside user profile to hide dropdown
    document.addEventListener('click', function(e) {
        const profile = document.querySelector('.user-profile');
        const dropdown = document.getElementById('dropdown');
        if (profile && !profile.contains(e.target) && dropdown) {
            dropdown.style.display = 'none';
        }
    });
}

/* ====== Student profile loader (unchanged) ====== */
function loadStudentProfile() {
    fetch('../php/getStudent.php', { cache: 'no-store' })
        .then(r => r.text())
        .then(text => {
            // Try to parse JSON robustly (some endpoints might return stray whitespace/html)
            let data;
            try {
                data = JSON.parse(text.trim());
            } catch (err) {
                console.warn('getStudent returned non-json:', text.slice(0,200));
                return;
            }

            if (data.status === 'success' && data.student) {
                const s = data.student;

                // tolerant getters (handles Name or name, profile_photo_url or profile_photo or avatar)
                const name = s.Name ?? s.name ?? s.FullName ?? s.fullname ?? s.name_display ?? '';
                const email = s.Email ?? s.email ?? '';
                const course = s.Course ?? s.course ?? '';
                const year = s.Year_Level ?? s.year_level ?? s.year ?? '';
                const photo = s.profile_photo_url ?? s.profile_photo ?? s.profile_photo_url ?? s.profile_photo_url ?? s.avatar ?? s.Avatar ?? null;

                // if server returned a relative filepath, best to build full URL on client
                // if returned null or empty, fallback to default
                const avatarUrl = (photo && photo.toString().trim())
                    ? (photo.startsWith('http') ? photo : ('../' + photo).replace(/\/\.\.\//g,'/')) // a small tweak so relative paths work
                    : '../assests/default-avatar.jpg';

                if (userAvatarEl) userAvatarEl.src = avatarUrl;
                if (userNameEl) userNameEl.textContent = name || 'Student';
                if (userRoleEl) userRoleEl.textContent = 'Student';

                const pageTitleName = document.querySelector('.page-title h1');
                if (pageTitleName) pageTitleName.textContent = `Welcome, ${name || 'Student'}!`;
            } else {
                console.warn('Student not loaded:', data);
            }
        })
        .catch(err => {
            console.error('getStudent fetch error:', err);
        });
}

/* ====== Fetch elections & candidates ====== */
async function fetchElections() {
    try {
        const res = await fetch(GET_ELECTIONS_URL, { cache: 'no-store' });
        const text = await res.text();
        const trimmed = text.trim();
        if (!trimmed) throw new Error('Empty response from server');
        if (trimmed[0] === '<') throw new Error('Server returned HTML instead of JSON:\n' + trimmed.slice(0,400));
        let data;
        try { data = JSON.parse(trimmed); } catch (e) { throw new Error('Invalid JSON from get_elections: ' + e.message); }

        // Normalize to array
        let rawElections = [];
        if (Array.isArray(data)) rawElections = data;
        else if (Array.isArray(data.elections)) rawElections = data.elections;
        else {
            const maybe = Object.values(data).find(v => Array.isArray(v));
            if (maybe) rawElections = maybe;
        }

        elections = rawElections.map(raw => ( {
            id: raw.id ?? raw.Election_ID ?? raw.election_id ?? raw.ID ?? null,
            title: raw.title ?? raw.name ?? raw.Name ?? raw.Title ?? '',
            status: (raw.status ?? raw.statusJs ?? '').toString().toLowerCase(),
            startDate: raw.startDate ?? raw.Start_Date ?? raw.start_date ?? raw.start ?? '',
            endDate: raw.endDate ?? raw.End_Date ?? raw.end_date ?? raw.end ?? '',
            totalVoters: Number(raw.totalVoters ?? raw.total_voters ?? raw.Total_Voters ?? 0),
            votedCount: Number(raw.votedCount ?? raw.votesCast ?? 0),
            // description intentionally not shown per request
            organization_id: raw.organization_id ?? raw.Organization_ID ?? raw.Org_ID ?? raw.organization ?? null,
            organization: raw.organization ?? raw.Org_Name ?? raw.organization_name ?? raw.OrgName ?? '',
            organization_logo: raw.organization_logo ?? raw.organization_logo_url ?? raw.org_logo ?? '',
            candidates: Array.isArray(raw.candidates) ? raw.candidates : [],
            userVoted: !!raw.userVoted,
            progress: Number(raw.progress ?? 0),
            timeRemaining: raw.timeRemaining ?? raw.time_remaining ?? ''
        } ));

        // load candidates and assign to elections
        await loadAllCandidates();

        renderElections();
        updateStats();
    } catch (err) {
        console.error('fetchElections error:', err);
        showToast(err.message || 'Unable to load elections', 'error');
    }
}

/* ====== Load all candidates once ====== */
async function loadAllCandidates() {
    try {
        const res = await fetch(CANDIDATES_LIST_URL, { cache: 'no-store' });
        if (!res.ok) throw new Error('Failed to load candidates_list.php (HTTP ' + res.status + ')');
        const json = await res.json();

        // find candidate array
        let allCandidates = [];
        if (Array.isArray(json.data)) allCandidates = json.data;
        else if (Array.isArray(json.candidates)) allCandidates = json.candidates;
        else if (Array.isArray(json)) allCandidates = json;
        else {
            const maybe = Object.values(json).find(v => Array.isArray(v));
            if (Array.isArray(maybe)) allCandidates = maybe;
        }

        allCandidatesCount = allCandidates.length || 0;
        totalCandidates.textContent = allCandidatesCount;

        // normalize candidates for matching and canonical avatar url
        const normalizedAll = allCandidates.map(c => {
            // pick common avatar fields
            const rawAvatar = c.Avatar ?? c.avatar ?? c.Avatar_Path ?? c.image ?? c.photo ?? '';
            return {
                raw: c,
                id: Number(c.Candidate_ID ?? c.id ?? c.ID ?? 0),
                name: c.FullName ?? c.fullname ?? c.name ?? '',
                party: c.PartyList ?? c.Party ?? c.party ?? '',
                avatar: buildAvatarUrl(rawAvatar),
                platform: c.Platform ?? c.platform ?? '',
                position_id: Number(c.Position_ID ?? c.position_id ?? 0) || null,
                position_name: c.position_name ?? c.Position_Name ?? c.PositionName ?? '',
                org_id: c.Org_ID ?? c.OrgId ?? c.org_id ?? null,
                org_name: c.org_name ?? c.Org_Name ?? c.OrgName ?? c.org ?? ''
            };
        });

        // prepare normalized election metadata for matching
        const normalizedElections = elections.map(e => {
            const orgId = e.organization_id ?? e.Org_ID ?? null;
            const orgName = e.organization ?? '';
            return {
                __orig: e,
                id: e.id,
                title: e.title,
                organization_id: orgId,
                organization_id_str: orgId != null ? String(orgId).trim() : '',
                organization_name: (orgName || '').toString().trim().toLowerCase()
            };
        });

        // assign candidates to elections using heuristics
        normalizedElections.forEach(ne => {
            const matches = normalizedAll.filter(c => {
                if (ne.organization_id_str && c.org_id && String(c.org_id).trim() && ne.organization_id_str === String(c.org_id).trim()) return true;
                if (ne.organization_name && c.org_name && c.org_name.toLowerCase && ne.organization_name === c.org_name.toLowerCase()) return true;
                if (ne.title && c.org_name) {
                    const t = String(ne.title).toLowerCase();
                    const cn = String(c.org_name || '').toLowerCase();
                    if (t.includes(cn) || cn.includes(t)) return true;
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
                position_name: c.position_name
            }));
        });

        // update elections array with assigned candidates
        normalizedElections.forEach(ne => {
            const idx = elections.findIndex(x => String(x.id) === String(ne.id));
            if (idx !== -1) elections[idx].candidates = ne.__orig.candidates || [];
        });

    } catch (err) {
        console.error('loadAllCandidates error:', err);
        allCandidatesCount = allCandidatesCount || 0;
        totalCandidates.textContent = allCandidatesCount;
        elections.forEach(e => { if (!Array.isArray(e.candidates)) e.candidates = []; });
    }
}

/* ====== Render election cards ====== */
function renderElections() {
    electionsContainer.innerHTML = '';
    if (!elections.length) {
        electionsContainer.innerHTML = '<p style="padding:20px;color:#666">No elections found.</p>';
        return;
    }

    elections.forEach(e => {
        const title = escapeHtml(e.title || 'Untitled Election');
        const status = (e.status || 'scheduled').toLowerCase();
        const start = formatLocal(e.startDate);
        const end = formatLocal(e.endDate);

        // compute progress if not provided
        let progress = Number(e.progress || 0);
        if ((!progress || progress === 0) && e.totalVoters > 0) {
            progress = Math.round(((e.votedCount || 0) / e.totalVoters) * 100);
        }
        progress = Math.min(Math.max(progress, 0), 100);

        const candidateCount = (Array.isArray(e.candidates) && e.candidates.length) || 0;

        const card = document.createElement('div');
        card.className = 'election-card';
        card.innerHTML = `
            <div class="election-header">
                <h3 class="election-title">${title}</h3>
                <span class="election-status status-${escapeHtml(status)}">${capitalize(status)}</span>
            </div>
            <div class="election-details">
                <div class="detail-item">
                    <div class="detail-icon"><i class="fas fa-calendar-alt"></i></div>
                    <div class="detail-content">
                        <h4>Start Date & Time</h4>
                        <p>${escapeHtml(start)}</p>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-icon"><i class="fas fa-calendar-check"></i></div>
                    <div class="detail-content">
                        <h4>End Date & Time</h4>
                        <p>${escapeHtml(end)}</p>
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
                        <div class="progress" style="width: ${progress}%"></div>
                    </div>
                    <p class="time-remaining">${escapeHtml(e.timeRemaining || '')}</p>
                </div>
                <button class="view-button" data-election-id="${escapeHtml(String(e.id))}">
                    View Details
                </button>
            </div>
        `;
        electionsContainer.appendChild(card);
    });
}

/* ====== Show election modal ======
   - Removed description display.
   - Integrated organization avatar area.
   - No Vote buttons are rendered anywhere (per request).
*/
function showElectionDetails(electionId) {
    const e = elections.find(x => String(x.id) === String(electionId));
    if (!e) return;
    modalElectionTitle.textContent = e.title || 'Election Details';

    const positions = {};
    (e.candidates || []).forEach(c => {
        const pos = c.position_name || (c.position_id ? `Position ${c.position_id}` : 'Candidate');
        if (!positions[pos]) positions[pos] = [];
        positions[pos].push(c);
    });

    const voterTurnout = e.totalVoters ? Math.round(((e.votedCount || 0) / e.totalVoters) * 100) : 0;

    // organization avatar fallback (use organization_logo when available)
    const orgAvatarRaw = e.organization_logo ?? '';
    const orgAvatar = orgAvatarRaw && String(orgAvatarRaw).trim() ? buildAvatarUrl(orgAvatarRaw) : '../assests/eboto logo.png';

    let html = `
        <div class="election-info">
            <div style="display:flex; gap:12px; align-items:center; margin-bottom:14px;">
                <div style="flex:0 0 64px;">
                    <img src="${escapeHtml(orgAvatar)}" alt="Org avatar" style="width:64px;height:64px;object-fit:cover;border-radius:8px;">
                </div>
                <div style="flex:1;">
                    <div style="font-weight:700; font-size:1.05rem;">${escapeHtml(e.title || '')}</div>
                    <div style="color:#666;">${escapeHtml(e.organization || '')}</div>
                </div>
            </div>

            <div class="election-info-grid">
                <div class="info-item"><span class="info-label">Department</span><span class="info-value">${escapeHtml(e.organization || '')}</span></div>
                <div class="info-item"><span class="info-label">Status</span><span class="info-value">${capitalize(e.status || '')}</span></div>
                <div class="info-item"><span class="info-label">Start Date</span><span class="info-value">${escapeHtml(formatLocal(e.startDate) || '')}</span></div>
                <div class="info-item"><span class="info-label">End Date</span><span class="info-value">${escapeHtml(formatLocal(e.endDate) || '')}</span></div>
                <div class="info-item"><span class="info-label">Voter Turnout</span><span class="info-value">${voterTurnout}% (${e.votedCount || 0}/${e.totalVoters || 0})</span></div>
                <div class="info-item"><span class="info-label">Time Remaining</span><span class="info-value">${escapeHtml(e.timeRemaining || '')}</span></div>
            </div>
        </div>
        <h3 style="margin-bottom:15px;">Candidates</h3>
    `;

    if (!e.candidates || e.candidates.length === 0) {
        html += `<p style="color:#666">No candidates available for this election.</p>`;
    } else {
        Object.entries(positions).forEach(([position, candidates]) => {
            html += `<div class="position-section"><div class="position-header">${escapeHtml(position)}</div><div class="candidates-grid">`;
            candidates.forEach(c => {
                // ensure avatar uses canonical provided directory
                const avatarRaw = c.avatar ?? c.Avatar ?? c.raw?.Avatar ?? c.raw?.avatar ?? '';
                const avatar = buildAvatarUrl(avatarRaw);

                // vote button intentionally removed (empty string)
                const voteBtnHtml = '';

                html += `
                    <div class="candidate-card">
                        <div class="candidate-avatar">
                            <img src="${escapeHtml(avatar)}" alt="${escapeHtml(c.name || '')}" />
                        </div>
                        <div class="candidate-info">
                            <div class="candidate-name">${escapeHtml(c.name || '')}</div>
                            <div class="candidate-party">${escapeHtml(c.party || '')}</div>
                            <div class="candidate-platform">${escapeHtml(c.platform || '')}</div>
                        </div>
                        <div style="margin-top:8px;">${voteBtnHtml}</div>
                    </div>
                `;
            });
            html += `</div></div>`;
        });
    }

    electionDetailsContent.innerHTML = html;
    electionDetailsModal.style.display = 'flex';
}

/* ====== Close modal ====== */
function closeElectionDetailsModal() {
    electionDetailsModal.style.display = 'none';
}

/* ====== Cast vote (kept for server-side endpoint; UI currently shows no vote buttons) ====== */
function castVote(electionId, candidateId) {
    showToast('Casting vote...', 'info');

    fetch('../php/castVote.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ election_id: electionId, candidate_id: candidateId })
    })
    .then(r => r.json())
    .then(res => {
        if (res.status === 'success') {
            showToast('Vote cast successfully!', 'success');

            // update local election model
            const e = elections.find(x => String(x.id) === String(electionId));
            if (e) {
                e.userVoted = true;
                e.votedCount = (Number(e.votedCount || 0) + 1);

                // recalc progress if totalVoters available
                if (e.totalVoters && Number(e.totalVoters) > 0) {
                    e.progress = Math.round((e.votedCount / e.totalVoters) * 100);
                }
            }

            renderElections();
            updateStats();

            // show updated modal (it will still show no vote buttons because UI removed them)
            showElectionDetails(electionId);
        } else {
            const err = res.error || res.message || 'Failed to cast vote';
            showToast(String(err), 'error');
        }
    })
    .catch(err => {
        console.error('castVote error:', err);
        showToast('Network or server error', 'error');
    });
}

/* ====== Stats ====== */
function updateStats() {
    const activeElections = elections.filter(e => {
        const st = (e.status || '').toString().toLowerCase();
        return st === 'active' || st === 'ongoing' || st === 'running';
    }).length;

    const totalVotesFromElections = elections.reduce((sum, e) => {
        let v = Number(e.votedCount ?? e.votesCast ?? e.votes?.length ?? 0);
        if (Number.isNaN(v)) v = 0;
        return sum + v;
    }, 0);

    const candidatesCountFromElections = elections.reduce((t, e) => t + ((e.candidates && e.candidates.length) || 0), 0);
    const candidatesCount = (typeof allCandidatesCount === 'number' && allCandidatesCount > 0)
        ? allCandidatesCount
        : candidatesCountFromElections;

    activeElectionsCount.textContent = activeElections;
    votesCast.textContent = totalVotesFromElections;
    totalCandidates.textContent = candidatesCount;
}

/* ====== Toast ====== */
function showToast(message, type = 'success') {
    toastMessage.textContent = message;
    toast.className = 'toast';
    if (type === 'error') {
        toast.querySelector('i').className = 'fas fa-exclamation-circle';
    } else if (type === 'info') {
        toast.querySelector('i').className = 'fas fa-info-circle';
    } else {
        toast.querySelector('i').className = 'fas fa-check-circle';
    }
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3500);
}

/* ====== Helpers ====== */
function capitalize(s){ return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; }
function escapeHtml(str){
    if (str === null || str === undefined) return '';
    return String(str)
        .replaceAll('&','&amp;')
        .replaceAll('<','&lt;')
        .replaceAll('>','&gt;')
        .replaceAll('"','&quot;')
        .replaceAll("'",'&#039;');
}
function formatLocal(dateStr) {
    if (!dateStr) return '';
    const d = new Date(String(dateStr).replace(' ', 'T'));
    if (isNaN(d)) return dateStr;
    return d.toLocaleString();
}

/* ====== Start app ====== */
document.addEventListener('DOMContentLoaded', init);

/* ====== Small helpers used in markup ====== */
function toggleDropdown(){
    const d = document.getElementById('dropdown');
    if (!d) return;
    d.style.display = (d.style.display === 'block') ? 'none' : 'block';
}
function logoutUser(){
    fetch('../php/logout.php', { method: 'POST' }).finally(()=> window.location.href = '/');
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

        .status-upcoming {
            background-color: rgba(255, 204, 0, 0.2);
            color: var(--ustp-gold);
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

        .view-button {
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

        .view-button:hover {
            background: linear-gradient(135deg, #002244 0%, #0055aa 100%);
            box-shadow: 0 5px 15px rgba(0, 51, 102, 0.4);
            transform: translateY(-2px);
        }

        footer {
            text-align: center;
            margin-top: 40px;
            padding: 20px;
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        /* Stats Cards */
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

        .election-info {
            margin-bottom: 25px;
        }

        .election-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            padding: 15px;
            background: var(--ustp-light-gray);
            border-radius: 8px;
        }

        .info-label {
            font-size: 0.85rem;
            color: #7f8c8d;
            margin-bottom: 5px;
        }

        .info-value {
            font-weight: 600;
            color: var(--ustp-dark-gray);
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

        .candidates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            padding: 15px;
        }

        .candidate-card {
            display: flex;
            flex-direction: column;
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .candidate-card:hover {
            border-color: var(--ustp-light-blue);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }

        .candidate-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            overflow: hidden;
            margin: 0 auto 15px auto;
            border: 2px solid var(--ustp-gold);
        }

        .candidate-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .candidate-info {
            text-align: center;
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
            margin-bottom: 10px;
        }

        .candidate-platform {
            font-size: 0.85rem;
            color: #7f8c8d;
            margin-top: 10px;
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
            
            .candidates-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>

</html>
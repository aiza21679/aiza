<?php
session_start();

/* ------------------ LOGO HELPERS ------------------ */
function resolve_logo_url(string $dbPath = ''): string {
    $projectPrefix = '/eBotoni/eBotoni/'; 
    $default = $projectPrefix . 'assests/default-logo.jpg';

    $p = trim((string)$dbPath);
    if ($p === '') return $default;
    if (preg_match('#^https?://#i', $p)) return $p;
    if ($p[0] === '/') return $p;

    $clean = preg_replace('#^(\.\/|(\.\.\/)+)#', '', $p);

    if (strpos($clean, trim($projectPrefix, '/')) !== false) {
        return '/' . ltrim($clean, '/');
    }

    return rtrim($projectPrefix, '/') . '/' . ltrim($clean, '/');
}
$AVATAR_BASE = '/eBotoni/uploads/avatars/';
$PARTY_DEFAULT = '/eBotoni/eBotoni/assests/party-default.png';

/* ------------------ LOAD ORG PROFILE ------------------ */
$profile = [
    'name' => 'Organization User',
    'position' => $_SESSION['org_role'] ?? 'Organization User',
    'email' => '',
    'avatar' => '/eBotoni/eBotoni/assests/default-logo.jpg'
];

$ORG_ID = $_SESSION['org_id'] ?? null;

if ($ORG_ID) {
    try {
        $pdo = new PDO(
            'mysql:host=127.0.0.1;dbname=eboto_db;charset=utf8mb4',
            'root','',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );

        $stmt = $pdo->prepare("SELECT Org_Name, Email, Logo_Url FROM student_organization WHERE Org_ID = ? LIMIT 1");
        $stmt->execute([$ORG_ID]);
        $row = $stmt->fetch();

        if ($row) {
            $profile['name']     = $row['Org_Name'] ?: $profile['name'];
            $profile['email']    = $row['Email']    ?: $profile['email'];
            $profile['avatar']   = resolve_logo_url($row['Logo_Url'] ?? '');
            $profile['position'] = 'Organization User';
        }
    } catch (Exception $e) {
        error_log("Org Profile Error: " . $e->getMessage());
    }
}

// Make accessible to JS
?>
<script>
  window.SERVER_PROFILE = <?= json_encode($profile, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
  window.PARTY_DEFAULT_AVATAR = <?= json_encode($PARTY_DEFAULT, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE); ?>;
  
  window.AVATAR_BASE = location.origin + '/eBotoni/eBotoni/student%20org/uploads/candidates/';
</script>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Party List Candidate Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

</head>
<body>
   <!-- SIDEBAR - EXACTLY THE SAME AS VOTERS PAGE -->
  <nav class="sidebar" id="sidebar">
    <div class="logo-container">
        <div class="logo">
            <img id="sidebarLogoImage"
                src='/eBotoni/eBotoni/assests/suffage.png'
                alt="Logo">
        </div>
    </div>

    <ul class="nav-menu">
        <li class="nav-item"><i class="fas fa-home"></i><a href="org-dashboard.php">Dashboard</a></li>
        <li class="nav-item"><i class="fas fa-user-graduate"></i><a href="org-voters.php">Organization Voters</a></li>
        <li class="nav-item active"><i class="fas fa-user-tie"></i><a href="manage-candidates.php">Candidates</a></li>
        <li class="nav-item"><i class="fas fa-user-tag"></i><a href="manage-elections.php">Election Monitoring</a></li>
        <li class="nav-item"><i class="fas fa-check-circle"></i><a href="election-results.php">Election Results</a></li>
        <li class="nav-item"><i class="fas fa-cog"></i><a href="settings.php">Settings</a></li>
    </ul>

    <div class="user-profile" id="userProfile">
        <!-- Dynamic avatar -->
        <img src="<?php echo htmlspecialchars($profile['avatar'], ENT_QUOTES); ?>" class="user-avatar" />

        <!-- Dynamic name + role -->
        <div class="user-info">
            <h4><?php echo htmlspecialchars($profile['name'], ENT_QUOTES); ?></h4>
            <p><?php echo htmlspecialchars($profile['position'], ENT_QUOTES); ?></p>
        </div>

        <div class="user-dropdown" id="dropdown">
            <a href="../login.html" id="logoutBtn">Logout</a>
        </div>
    </div>
</nav>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <div class="header">
            <div class="page-title">
                <h1 id="pageTitle">Party List Candidate Management</h1>
                <p id="pageSubtitle">Manage and view all candidates organized by party lists</p>
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

        <!-- Candidate Statistics -->
        <div class="candidate-stats">
            <div class="stat-card stat-1">
                <div class="stat-icon">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Candidates</h3>
                    <div class="stat-value" id="totalCandidates">18</div>
                </div>
            </div>
            
            <div class="stat-card stat-2">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3>Approved</h3>
                    <div class="stat-value" id="approvedCandidates">12</div>
                </div>
            </div>
            
            <div class="stat-card stat-3">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <h3>Pending Review</h3>
                    <div class="stat-value" id="pendingCandidates">4</div>
                </div>
            </div>
            
            <div class="stat-card stat-4">
                <div class="stat-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-info">
                    <h3>Rejected</h3>
                    <div class="stat-value" id="rejectedCandidates">2</div>
                </div>
            </div>
        </div>

        <!-- View Toggle -->
        <div class="view-toggle">
            <button class="view-toggle-btn" id="tableViewBtn">
                <i class="fas fa-table"></i> Table View
            </button>
            <button class="view-toggle-btn active" id="cardViewBtn">
                <i class="fas fa-th-large"></i> Card View
            </button>
        </div>

        <!-- Party List Summary -->
        <div class="party-list-summary" id="partyListSummary">
            <!-- Party list cards will be populated by JavaScript -->
        </div>

        <!-- Party Detail View -->
        <div class="party-detail-view" id="partyDetailView">
            <!-- Party details and candidates will be populated by JavaScript -->
        </div>
        
        <!-- Party List Management -->
        <div class="party-list-management" id="partyListManagement">
            <div class="management-header">
                <h2>Party List Candidate Management</h2>
                <div class="management-actions">
                    <button class="btn btn-primary" id="addCandidateBtn">
                        <i class="fas fa-user-plus"></i> Add Candidate
                    </button>
                    <button class="btn btn-secondary" id="exportCandidatesBtn">
                        <i class="fas fa-file-export"></i> Export List
                    </button>
                    <button class="btn btn-success" id="bulkApproveBtn">
                        <i class="fas fa-check-double"></i> Bulk Approve
                    </button>
                </div>
            </div>

            <!-- Party List Tabs -->
            <div class="party-list-tabs" id="partyListTabs">
                <div class="party-list-tab active" data-party="all">All Candidates</div>
                <div class="party-list-tab party-progress" data-party="Progress">Progress Party</div>
                <div class="party-list-tab party-unity" data-party="Unity">Unity Alliance</div>
                <div class="party-list-tab party-vision" data-party="Vision">Vision Coalition</div>
                <div class="party-list-tab party-independent" data-party="Independent">Independent</div>
            </div>
            
            <div class="candidate-table-container">
                <table class="candidate-table" id="candidateTable">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Name</th>
                            <th>Position</th>
                            <th>Party List</th>
                            <th>Organization</th>
                            <th>Year Level</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="candidateTableBody">
                        <!-- Candidate data will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>

            <!-- Empty State -->
            <div class="empty-state" id="emptyState" style="display: none;">
                <i class="fas fa-user-tie"></i>
                <h3>No Candidates Found</h3>
                <p>There are no candidates for this party list. Try selecting a different party list or add new candidates.</p>
            </div>
            
            <!-- Pagination -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
                <div style="color: var(--gray); font-size: 0.9rem;" id="paginationInfo">
                    Showing 1 to 6 of 18 entries
                </div>
                <div style="display: flex; gap: 5px;" id="paginationControls">
                    <button class="btn btn-secondary" id="prevPage">Previous</button>
                    <button class="btn btn-primary" id="page1">1</button>
                    <button class="btn btn-secondary" id="page2">2</button>
                    <button class="btn btn-secondary" id="page3">3</button>
                    <button class="btn btn-secondary" id="nextPage">Next</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Candidate Modal -->
    <div class="modal" id="addCandidateModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Candidate</h3>
                <button class="close-modal" id="closeAddModal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addCandidateForm">
                    <div class="form-group">
                        <label for="candidateName">Full Name</label>
                        <input type="text" id="candidateName" required>
                    </div>
                    <div class="form-group">
                        <label for="candidateEmail">Email</label>
                        <input type="email" id="candidateEmail" required>
                    </div>
                    <div class="form-group">
                        <label for="candidateStudentId">Student ID</label>
                        <input type="text" id="candidateStudentId" required>
                    </div>
                    <div class="form-group">
                        <label for="candidateYearLevel">Year Level</label>
                        <select id="candidateYearLevel" required>
                            <option value="">Select Year Level</option>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="candidatePosition">Position</label>
<select id="candidatePosition" required>
  <option value="">Select Position</option>
  <option value="1">President</option>         <!-- Position_ID = 1 -->
  <option value="2">Vice President</option>    <!-- Position_ID = 2 -->
  <option value="3">Secretary</option>
  <option value="4">Treasurer</option>
  <option value="5">Auditor</option>
</select>
                    </div>
                    <div class="form-group">
                        <label for="candidateOrganization">Organization</label>
                      <select id="candidateOrganization" required>
  <option value="">Select Organization</option>
  <option value="10">SOCE</option>      <!-- Org_ID = 1 -->
  <option value="11">SITE</option>      <!-- Org_ID = 2 -->
  <option value="12">SABM</option>
  <option value="13">SAS</option>
  <option value="14">Independent</option>
</select>
                    </div>
                    <div class="form-group">
                        <label for="candidatePartyList">Party List</label>
<select id="candidatePartyList" required>
  <option value="">Select Party List</option>
  <option value="Progress">Progress</option>
  <option value="Unity">Unity</option>
  <option value="Vision">Vision</option>
  <option value="Independent">Independent</option>
</select>
                    </div>
                    <div class="form-group">
                        <label for="candidateProgram">Program</label>
                        <input type="text" id="candidateProgram" required>
                    </div>
                    <div class="form-group">
                        <label for="candidatePlatform">Platform/Manifesto</label>
                        <textarea id="candidatePlatform" placeholder="Brief description of the candidate's platform and goals..." required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="candidateExperience">Experience</label>
                        <textarea id="candidateExperience" placeholder="Candidate's relevant experience..." required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Profile Photo</label>
                        <div class="avatar-upload">
                            <img src="../assests/default-avatar.jpg" class="avatar-preview" id="avatarPreview">
                            <div>
                                <div class="upload-btn" onclick="document.getElementById('avatarUpload').click()">
                                    <i class="fas fa-upload"></i> Upload Photo
                                </div>
                                <input type="file" id="avatarUpload" accept="image/*" style="display: none;">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="candidateStatus">Status</label>
                        <select id="candidateStatus" required>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancelAddCandidate">Cancel</button>
                <button class="btn btn-primary" id="saveCandidate">Save Candidate</button>
            </div>
        </div>
    </div>

    <!-- Edit Candidate Modal -->
    <div class="modal" id="editCandidateModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Candidate</h3>
                <button class="close-modal" id="closeEditModal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editCandidateForm">
                    <input type="hidden" id="editCandidateId">
                    <div class="form-group">
                        <label for="editCandidateName">Full Name</label>
                        <input type="text" id="editCandidateName" required>
                    </div>
                    <div class="form-group">
                        <label for="editCandidateEmail">Email</label>
                        <input type="email" id="editCandidateEmail" required>
                    </div>
                    <div class="form-group">
                        <label for="editCandidateStudentId">Student ID</label>
                        <input type="text" id="editCandidateStudentId" required>
                    </div>
                    <div class="form-group">
                        <label for="editCandidateYearLevel">Year Level</label>
                        <select id="editCandidateYearLevel" required>
                            <option value="">Select Year Level</option>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editCandidatePosition">Position</label>
                        <select id="editCandidatePosition" required>
                            <option value="">Select Position</option>
                            <option value="President">President</option>
                            <option value="Vice President">Vice President</option>
                            <option value="Secretary">Secretary</option>
                            <option value="Treasurer">Treasurer</option>
                            <option value="Auditor">Auditor</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editCandidateOrganization">Organization</label>
                        <select id="editCandidateOrganization" required>
                            <option value="">Select Organization</option>
                            <option value="SOCE">SOCE</option>
                            <option value="SITE">SITE</option>
                            <option value="SABM">SABM</option>
                            <option value="SAS">SAS</option>
                            <option value="Independent">Independent</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editCandidatePartyList">Party List</label>
                        <select id="editCandidatePartyList" required>
                            <option value="">Select Party List</option>
                            <option value="Progress">Progress Party</option>
                            <option value="Unity">Unity Alliance</option>
                            <option value="Vision">Vision Coalition</option>
                            <option value="Independent">Independent</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="editCandidateProgram">Program</label>
                        <input type="text" id="editCandidateProgram" required>
                    </div>
                    <div class="form-group">
                        <label for="editCandidatePlatform">Platform/Manifesto</label>
                        <textarea id="editCandidatePlatform" placeholder="Brief description of the candidate's platform and goals..." required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="editCandidateExperience">Experience</label>
                        <textarea id="editCandidateExperience" placeholder="Candidate's relevant experience..." required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Profile Photo</label>
                        <div class="avatar-upload">
                            <img src="../assests/default-avatar.jpg" class="avatar-preview" id="editAvatarPreview">
                            <div>
                                <div class="upload-btn" onclick="document.getElementById('editAvatarUpload').click()">
                                    <i class="fas fa-upload"></i> Upload Photo
                                </div>
                                <input type="file" id="editAvatarUpload" accept="image/*" style="display: none;">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="editCandidateStatus">Status</label>
                        <select id="editCandidateStatus" required>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancelEditCandidate">Cancel</button>
                <button class="btn btn-primary" id="updateCandidate">Update Candidate</button>
            </div>
        </div>
    </div>

    <!-- View Candidate Modal -->
    <div class="modal" id="viewCandidateModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Candidate Profile</h3>
                <button class="close-modal" id="closeViewModal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="candidate-profile">
                    <img src="../assests/default-avatar.jpg" class="profile-avatar" id="viewAvatar">
                    <div class="profile-info">
                        <h3 id="viewCandidateName">Juan Dela Cruz</h3>
                        <div class="profile-meta">
                            <div><strong>Position:</strong> <span id="viewPosition">President</span></div>
                            <div><strong>Party List:</strong> <span id="viewPartyList">Progress Party</span></div>
                            <div><strong>Organization:</strong> <span id="viewOrganization">SOCE</span></div>
                            <div><strong>Year Level:</strong> <span id="viewYearLevel">4th Year</span></div>
                            <div><strong>Student ID:</strong> <span id="viewStudentId">2021-00123</span></div>
                            <div><strong>Email:</strong> <span id="viewEmail">juan.delacruz@example.com</span></div>
                            <div><strong>Program:</strong> <span id="viewProgram">BS Computer Engineering</span></div>
                        </div>
                        <div class="status-badge status-approved" id="viewStatusBadge">Approved</div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Platform & Manifesto</label>
                    <div style="padding: 15px; background: var(--light-gray); border-radius: 5px; min-height: 100px;">
                        <p id="viewPlatform">This candidate's platform focuses on improving student engagement and creating more opportunities for hands-on learning experiences.</p>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Experience</label>
                    <div style="padding: 15px; background: var(--light-gray); border-radius: 5px; min-height: 100px;">
                        <p id="viewExperience">Class President (2022-2023), SOCE Committee Member (2021-2022)</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="closeViewProfile">Close</button>
                <button class="btn btn-primary" id="editFromView">Edit Candidate</button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal confirmation-modal" id="deleteConfirmationModal">
        <div class="modal-content">
            <div class="modal-body">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Delete Candidate</h3>
                <p id="deleteConfirmationText">Are you sure you want to delete this candidate? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancelDelete">Cancel</button>
                <button class="btn btn-danger" id="confirmDelete">Delete</button>
            </div>
        </div>
    </div>

    <!-- Bulk Approve Confirmation Modal -->
    <div class="modal confirmation-modal" id="bulkApproveModal">
        <div class="modal-content">
            <div class="modal-body">
                <i class="fas fa-check-circle"></i>
                <h3>Bulk Approve Candidates</h3>
                <p id="bulkApproveText">Are you sure you want to approve all pending candidates?</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancelBulkApprove">Cancel</button>
                <button class="btn btn-success" id="confirmBulkApprove">Approve All</button>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast" id="toast"></div>

  <script>
// ================== GLOBAL DATA & ENDPOINTS ==================
let candidates = []; // will be populated from the server

// Static information for each party list (design only, NOT candidate data)
const partyInfo = {
  Progress: {
    name: 'SOCE Progressive Party',
    logo: '../assests/progress_partylist.png', // change to your real logo path
    slogan: 'Advancing Technology, Empowering Students',
    established: '2023',
    department: 'Computer Engineering'
  },
  Unity: {
    name: 'Unity Alliance',
    logo: '../assests/unity_partylist.png', // change path
    slogan: 'One Campus, One Voice',
    established: '2023',
    department: 'All Departments'
  },
  Vision: {
    name: 'Vision Coalition',
    logo: '../assests/indepent_partylist.jpg', // change path
    slogan: 'Building a Brighter Tomorrow',
    established: '2023',
    department: 'Mixed Programs'
  },
  Independent: {
    name: 'Independent Candidates',
    logo: '../assests/indepent_partylist.jpg', // change path
    slogan: 'Service Beyond Party Lines',
    established: '2023',
    department: 'Various Departments'
  }
};

const ENDPOINTS = {
  GET: 'get_candidates.php',
  ADD: 'add_candidate.php',
  UPDATE: 'update_candidate.php',
  DELETE: 'delete_candidate.php'
};
// Force avatar to correct folder (same as student-side)
function fixAvatar(rawAvatar) {
    if (!rawAvatar) return '../assests/default-avatar.jpg';

    const filename = String(rawAvatar).split('/').pop();
    if (!filename) return '../assests/default-avatar.jpg';

    // same folder used by student candidates
    return location.origin + '/eBotoni/eBotoni/student%20org/uploads/candidates/' + filename;
}

// ================== FETCH CANDIDATES FROM SERVER ==================
async function fetchCandidates() {
  try {
    const res = await fetch(ENDPOINTS.GET + '?_=' + Date.now(), {
      credentials: 'same-origin',
      cache: 'no-store'
    });
    if (!res.ok) throw new Error('Failed to fetch candidates: ' + res.status);
    const json = await res.json();

    if (!Array.isArray(json)) {
      console.error('Unexpected get_candidates response:', json);
      showToast('Server returned unexpected data for candidates', 'error');
      return;
    }

candidates = json.map(c => ({
  id: c.id || c.Candidate_ID || c.insert_id || c.candidate_id || c.CandidateID,
  name: c.FullName || c.name || c.Name,
  email: c.Email || c.email || '',
  studentId: c.StudentID || c.student_id || '',
  yearLevel: c.YearLevel || c.year_level || '',
  position: c.Position_Name || c.Position || c.position || '',
  position_id: c.Position_ID || c.position_id || null,
  organization: c.Org_Name || c.OrgName || c.organization || c.Org || '',
  org_id: c.Org_ID || c.org_id || null,
  partyList: c.PartyList || c.partyList || c.Party || '',
  program: c.Program || c.program || '',
  platform: c.Platform || c.platform || '',
  experience: c.Experience || c.experience || '',
  avatar: fixAvatar(c.Avatar || c.avatar),
  status: (c.Status || c.status || 'pending').toLowerCase()
}));



    updateCandidateStats();
    renderPartyListSummary();
    filterCandidates();

    if (currentView === 'card') {
      showCardView();
    } else {
      showTableView();
    }
  } catch (err) {
    console.error(err);
    showToast('Could not load candidates from server', 'error');
  }
}

// ================== DOM ELEMENTS ==================
const menuToggle = document.getElementById('menuToggle');
const sidebar = document.getElementById('sidebar');
const mainContent = document.getElementById('mainContent');
const userProfile = document.getElementById('userProfile');
const dropdown = document.getElementById('dropdown');
const logoutBtn = document.getElementById('logoutBtn');
const toast = document.getElementById('toast');
const navLinks = document.querySelectorAll('.nav-link');
const addCandidateBtn = document.getElementById('addCandidateBtn');
const exportCandidatesBtn = document.getElementById('exportCandidatesBtn');
const bulkApproveBtn = document.getElementById('bulkApproveBtn');
const candidateTableBody = document.getElementById('candidateTableBody');
const totalCandidates = document.getElementById('totalCandidates');
const approvedCandidates = document.getElementById('approvedCandidates');
const pendingCandidates = document.getElementById('pendingCandidates');
const rejectedCandidates = document.getElementById('rejectedCandidates');
const paginationInfo = document.getElementById('paginationInfo');
const prevPageBtn = document.getElementById('prevPage');
const nextPageBtn = document.getElementById('nextPage');
const pageBtns = document.querySelectorAll('#paginationControls button:not(#prevPage):not(#nextPage)');
const partyListTabs = document.querySelectorAll('.party-list-tab');
const partyListSummary = document.getElementById('partyListSummary');
const partyDetailView = document.getElementById('partyDetailView');
const partyListManagement = document.getElementById('partyListManagement');
const emptyState = document.getElementById('emptyState');
const tableViewBtn = document.getElementById('tableViewBtn');
const cardViewBtn = document.getElementById('cardViewBtn');

// Modal Elements
const addCandidateModal = document.getElementById('addCandidateModal');
const editCandidateModal = document.getElementById('editCandidateModal');
const viewCandidateModal = document.getElementById('viewCandidateModal');
const deleteConfirmationModal = document.getElementById('deleteConfirmationModal');
const bulkApproveModal = document.getElementById('bulkApproveModal');
const closeAddModal = document.getElementById('closeAddModal');
const closeEditModal = document.getElementById('closeEditModal');
const closeViewModal = document.getElementById('closeViewModal');
const cancelAddCandidate = document.getElementById('cancelAddCandidate');
const cancelEditCandidate = document.getElementById('cancelEditCandidate');
const closeViewProfile = document.getElementById('closeViewProfile');
const saveCandidate = document.getElementById('saveCandidate');
const updateCandidate = document.getElementById('updateCandidate');
const editFromView = document.getElementById('editFromView');
const addCandidateForm = document.getElementById('addCandidateForm');
const editCandidateForm = document.getElementById('editCandidateForm');
const cancelDelete = document.getElementById('cancelDelete');
const confirmDelete = document.getElementById('confirmDelete');
const deleteConfirmationText = document.getElementById('deleteConfirmationText');
const cancelBulkApprove = document.getElementById('cancelBulkApprove');
const confirmBulkApprove = document.getElementById('confirmBulkApprove');
const bulkApproveText = document.getElementById('bulkApproveText');

// ================== STATE ==================
let currentPage = 1;
const itemsPerPage = 5;
let filteredCandidates = [];
let selectedPartyList = 'all';
let candidateToDelete = null;
let currentView = 'card'; // 'card' or 'table'

// In real app this comes from backend
const userRole = 'admin'; // or 'stakeholder'

// ================== INIT FUNCTIONS ==================
function initLayout() {
  // initial zero-state layout (like your original design)
  updateCandidateStats();
  renderPartyListSummary();

  if (currentView === 'card') {
    showCardView();
  } else {
    showTableView();
  }
}

function initPage() {
  setupEventListeners();
  setupRoleBasedAccess();
  initLayout();   // keep your original look
  fetchCandidates(); // then load real data from DB
}

// ================== ROLE-BASED ACCESS ==================
function setupRoleBasedAccess() {
  if (userRole !== 'admin') {
    addCandidateBtn.style.display = 'none';
    bulkApproveBtn.style.display = 'none';
    document.querySelectorAll('.btn-edit, .btn-delete').forEach(btn => {
      btn.style.display = 'none';
    });
    editFromView.style.display = 'none';
  }
}

// ================== EVENT LISTENERS ==================
function setupEventListeners() {
  // Mobile menu toggle
  menuToggle.addEventListener('click', toggleSidebar);

  // Nav links (if any have .nav-link)
  navLinks.forEach(link => {
    link.addEventListener('click', e => {
      navLinks.forEach(l => l.classList.remove('active'));
      e.currentTarget.classList.add('active');
      const span = e.currentTarget.querySelector('span');
      const pageName = span ? span.textContent : 'page';
      showToast(`Navigating to ${pageName}...`, 'info');
    });
  });

  // User profile dropdown
  userProfile.addEventListener('click', toggleDropdown);

  // Logout
  logoutBtn.addEventListener('click', e => {
    e.preventDefault();
    showToast('Logging out...', 'info');
    setTimeout(() => {
      window.location.href = '../login.html';
    }, 1500);
  });

  // Management buttons
  addCandidateBtn.addEventListener('click', openAddCandidateModal);
  exportCandidatesBtn.addEventListener('click', exportCandidates);
  bulkApproveBtn.addEventListener('click', openBulkApproveModal);

  // Party list tabs
  partyListTabs.forEach(tab => {
    tab.addEventListener('click', e => {
      const partyList = e.currentTarget.getAttribute('data-party');
      selectPartyList(partyList);
    });
  });

  // View toggle
  tableViewBtn.addEventListener('click', () => {
    if (currentView !== 'table') {
      currentView = 'table';
      tableViewBtn.classList.add('active');
      cardViewBtn.classList.remove('active');
      showTableView();
    }
  });

  cardViewBtn.addEventListener('click', () => {
    if (currentView !== 'card') {
      currentView = 'card';
      cardViewBtn.classList.add('active');
      tableViewBtn.classList.remove('active');
      showCardView();
    }
  });

  // Modals
  closeAddModal.addEventListener('click', () => addCandidateModal.style.display = 'none');
  closeEditModal.addEventListener('click', () => editCandidateModal.style.display = 'none');
  closeViewModal.addEventListener('click', () => viewCandidateModal.style.display = 'none');
  cancelAddCandidate.addEventListener('click', () => addCandidateModal.style.display = 'none');
  cancelEditCandidate.addEventListener('click', () => editCandidateModal.style.display = 'none');
  closeViewProfile.addEventListener('click', () => viewCandidateModal.style.display = 'none');

  saveCandidate.addEventListener('click', saveNewCandidate);
  updateCandidate.addEventListener('click', updateExistingCandidate);
  editFromView.addEventListener('click', editFromViewProfile);

  cancelDelete.addEventListener('click', () => deleteConfirmationModal.style.display = 'none');
  confirmDelete.addEventListener('click', deleteCandidateConfirmed);

  cancelBulkApprove.addEventListener('click', () => bulkApproveModal.style.display = 'none');
  confirmBulkApprove.addEventListener('click', bulkApproveCandidates);

  // Avatar preview (add)
  document.getElementById('avatarUpload').addEventListener('change', e => {
    const f = e.target.files[0];
    if (!f) return;
    const allowed = ['image/jpeg','image/png','image/gif','image/webp'];
    if (!allowed.includes(f.type)) {
      showToast('Invalid avatar file type', 'error');
      e.target.value = '';
      return;
    }
    const reader = new FileReader();
    reader.onload = ev => {
      document.getElementById('avatarPreview').src = ev.target.result;
    };
    reader.readAsDataURL(f);
  });

  // Avatar preview (edit)
  document.getElementById('editAvatarUpload').addEventListener('change', handleEditAvatarUpload);

  // Pagination
  prevPageBtn.addEventListener('click', goToPrevPage);
  nextPageBtn.addEventListener('click', goToNextPage);
  pageBtns.forEach((btn, index) => {
    btn.addEventListener('click', () => goToPage(index + 1));
  });

  // Close modals by clicking outside
  window.addEventListener('click', e => {
    if (e.target === addCandidateModal) addCandidateModal.style.display = 'none';
    if (e.target === editCandidateModal) editCandidateModal.style.display = 'none';
    if (e.target === viewCandidateModal) viewCandidateModal.style.display = 'none';
    if (e.target === deleteConfirmationModal) deleteConfirmationModal.style.display = 'none';
    if (e.target === bulkApproveModal) bulkApproveModal.style.display = 'none';
  });

  // Close sidebar/dropdown on outside click
  document.addEventListener('click', e => {
    if (window.innerWidth <= 992 &&
        !sidebar.contains(e.target) &&
        !menuToggle.contains(e.target) &&
        sidebar.classList.contains('active')) {
      toggleSidebar();
    }
    if (!userProfile.contains(e.target)) {
      dropdown.style.display = 'none';
    }
  });
}

// ================== SMALL HELPERS ==================
function toggleSidebar() {
  sidebar.classList.toggle('active');
}

function toggleDropdown() {
  dropdown.style.display = (dropdown.style.display === 'block') ? 'none' : 'block';
}

function showToast(message, type = 'info') {
  toast.textContent = message;
  toast.className = 'toast';

  if (type === 'success') toast.classList.add('success');
  else if (type === 'error') toast.classList.add('error');
  else if (type === 'warning') toast.classList.add('warning');

  toast.classList.add('show');
  setTimeout(() => toast.classList.remove('show'), 3000);
}

// ================== STATS & PARTY SUMMARY ==================
function updateCandidateStats() {
  const total = candidates.length;
  const approved = candidates.filter(c => c.status === 'approved').length;
  const pending = candidates.filter(c => c.status === 'pending').length;
  const rejected = candidates.filter(c => c.status === 'rejected').length;

  totalCandidates.textContent = total;
  approvedCandidates.textContent = approved;
  pendingCandidates.textContent = pending;
  rejectedCandidates.textContent = rejected;
}

function renderPartyListSummary() {
  const partyLists = [
    { name: 'Progress Party', value: 'Progress', color: 'party-progress' },
    { name: 'Unity Alliance', value: 'Unity', color: 'party-unity' },
    { name: 'Vision Coalition', value: 'Vision', color: 'party-vision' },
    { name: 'Independent', value: 'Independent', color: 'party-independent' }
  ];

  partyListSummary.innerHTML = partyLists.map(party => {
    const partyCandidates = candidates.filter(c => c.partyList === party.value);
    const approvedCount = partyCandidates.filter(c => c.status === 'approved').length;
    const pendingCount = partyCandidates.filter(c => c.status === 'pending').length;

    return `
      <div class="party-list-card ${selectedPartyList === party.value ? 'active' : ''}" data-party="${party.value}">
          <h3>${party.name}</h3>
          <div class="party-list-count">${partyCandidates.length}</div>
          <div class="party-list-stats">
              ${approvedCount} Approved • ${pendingCount} Pending
          </div>
      </div>
    `;
  }).join('');

  // ✅ Make the top summary cards clickable
  document.querySelectorAll('.party-list-card').forEach(card => {
    card.addEventListener('click', () => {
      const partyList = card.getAttribute('data-party');

      // use the same flow as tabs
      selectedPartyList = partyList;
      currentView = 'card';
      cardViewBtn.classList.add('active');
      tableViewBtn.classList.remove('active');

      // update active style for tabs & cards
      partyListTabs.forEach(tab => {
        tab.classList.toggle('active', tab.getAttribute('data-party') === partyList);
      });
      document.querySelectorAll('.party-list-card').forEach(c => {
        c.classList.toggle('active', c === card);
      });

      filterCandidates();
      showCardView();

      // optional: scroll down to the party section smoothly
      window.scrollTo({
        top: partyDetailView.offsetTop - 80,
        behavior: 'smooth'
      });
    });
  });
}

function selectPartyList(partyList) {
  selectedPartyList = partyList;

  partyListTabs.forEach(tab => {
    tab.classList.toggle('active', tab.getAttribute('data-party') === partyList);
  });

  document.querySelectorAll('.party-list-card').forEach(card => {
    card.classList.toggle('active', card.getAttribute('data-party') === partyList);
  });

  currentPage = 1;
  filterCandidates();

  if (currentView === 'card') {
    showCardView();
  } else {
    showTableView();
  }
}

function filterCandidates() {
  if (selectedPartyList === 'all') {
    filteredCandidates = [...candidates];
  } else {
    filteredCandidates = candidates.filter(c => c.partyList === selectedPartyList);
  }
}

// ================== CARD VIEW ==================
function showCardView() {
  partyListManagement.style.display = 'none';
  partyDetailView.style.display = 'block';

  if (selectedPartyList === 'all') {
    partyDetailView.innerHTML = `
      <div class="empty-state">
          <i class="fas fa-info-circle"></i>
          <h3>Select a Party List</h3>
          <p>Please select a specific party list from the tabs above to view candidates.</p>
      </div>
    `;
    return;
  }

  const party = (typeof partyInfo !== 'undefined') ? partyInfo[selectedPartyList] : null;
  const partyCandidates = candidates.filter(c => c.partyList === selectedPartyList);

  if (!party || partyCandidates.length === 0) {
    partyDetailView.innerHTML = `
      <div class="empty-state">
          <i class="fas fa-user-tie"></i>
          <h3>No Candidates Found</h3>
          <p>There are no candidates for this party list.</p>
      </div>
    `;
    return;
  }

  partyDetailView.innerHTML = `
    <div class="party-header">
        <img src="${party.logo}" class="party-logo" alt="${party.name}">
        <div class="party-info">
            <h2>${party.name}</h2>
            <p class="party-slogan">${party.slogan}</p>
            <div class="party-meta">
                <span><i class="fas fa-users"></i> ${partyCandidates.length} Candidates</span>
                <span><i class="fas fa-calendar-alt"></i> Est. ${party.established}</span>
                <span><i class="fas fa-graduation-cap"></i> ${party.department}</span>
            </div>
        </div>
    </div>
    <div class="candidates-grid" id="candidatesGrid">
        ${partyCandidates.map(candidate => `
            <div class="candidate-card">
                <div class="candidate-header">
                    <img src="${candidate.avatar}" class="candidate-avatar-lg" alt="${candidate.name}">
                    <div class="candidate-basic-info">
                        <h3>${candidate.name}</h3>
                        <div class="candidate-position">${candidate.position}</div>
                        <div class="candidate-program">${candidate.program}</div>
                        <div class="candidate-year">${candidate.yearLevel}</div>
                    </div>
                </div>
                <div class="candidate-details">
                    <div class="detail-section">
                        <h4>Platform</h4>
                        <ul class="platform-list">
                            ${candidate.platform.split('\n').map(point => `<li>${point}</li>`).join('')}
                        </ul>
                    </div>
                    <div class="detail-section">
                        <h4>Key Achievements</h4>
                        <ul class="achievements-list">
                            ${candidate.experience.split('\n').map(a => `<li>${a}</li>`).join('')}
                        </ul>
                    </div>
                </div>
            </div>
        `).join('')}
    </div>
  `;
}

// ================== TABLE VIEW ==================
function showTableView() {
  partyDetailView.style.display = 'none';
  partyListManagement.style.display = 'block';
  filterCandidates();
  renderCandidateTable();
}

function renderCandidateTable() {
  candidateTableBody.innerHTML = '';

  const startIndex = (currentPage - 1) * itemsPerPage;
  const endIndex = startIndex + itemsPerPage;
  const candidatesToShow = filteredCandidates.slice(startIndex, endIndex);

  if (candidatesToShow.length === 0) {
    emptyState.style.display = 'block';
    document.getElementById('candidateTable').style.display = 'none';
    updatePagination();
    return;
  }

  emptyState.style.display = 'none';
  document.getElementById('candidateTable').style.display = 'table';

  candidatesToShow.forEach(candidate => {
    const row = document.createElement('tr');

    let partyDisplayName = '';
    switch(candidate.partyList) {
      case 'Progress': partyDisplayName = 'Progress Party'; break;
      case 'Unity': partyDisplayName = 'Unity Alliance'; break;
      case 'Vision': partyDisplayName = 'Vision Coalition'; break;
      case 'Independent': partyDisplayName = 'Independent'; break;
      default: partyDisplayName = candidate.partyList;
    }

    row.innerHTML = `
      <td><img src="${candidate.avatar}" class="candidate-avatar" alt="${candidate.name}"></td>
      <td>${candidate.name}</td>
      <td>${candidate.position}</td>
      <td><span class="party-badge party-${candidate.partyList ? candidate.partyList.toLowerCase() : ''}">${partyDisplayName}</span></td>
      <td>${candidate.organization}</td>
      <td>${candidate.yearLevel}</td>
      <td><span class="status-badge status-${candidate.status}">${candidate.status.charAt(0).toUpperCase() + candidate.status.slice(1)}</span></td>
      <td class="action-buttons">
          <button class="btn-action btn-view" data-id="${candidate.id}"><i class="fas fa-eye"></i></button>
          ${userRole === 'admin' ? `
              <button class="btn-action btn-edit" data-id="${candidate.id}"><i class="fas fa-edit"></i></button>
              <button class="btn-action btn-delete" data-id="${candidate.id}"><i class="fas fa-trash"></i></button>
          ` : ''}
      </td>
    `;
    candidateTableBody.appendChild(row);
  });

  document.querySelectorAll('.btn-view').forEach(btn => {
    btn.addEventListener('click', e => {
      const candidateId = e.currentTarget.getAttribute('data-id');
      viewCandidateProfile(candidateId);
    });
  });

  if (userRole === 'admin') {
    document.querySelectorAll('.btn-edit').forEach(btn => {
      btn.addEventListener('click', e => {
        const candidateId = e.currentTarget.getAttribute('data-id');
        openEditCandidateModal(candidateId);
      });
    });
    document.querySelectorAll('.btn-delete').forEach(btn => {
      btn.addEventListener('click', e => {
        const candidateId = e.currentTarget.getAttribute('data-id');
        confirmDeleteCandidate(candidateId);
      });
    });
  }

  updatePagination();
}

function updatePagination() {
  const totalPages = Math.ceil(filteredCandidates.length / itemsPerPage);
  const startItem = filteredCandidates.length === 0 ? 0 : (currentPage - 1) * itemsPerPage + 1;
  const endItem = Math.min(currentPage * itemsPerPage, filteredCandidates.length);

  paginationInfo.textContent = `Showing ${startItem} to ${endItem} of ${filteredCandidates.length} entries`;

  pageBtns.forEach((btn, index) => {
    if (index < totalPages) {
      btn.textContent = index + 1;
      btn.style.display = 'block';
      btn.classList.toggle('btn-primary', index + 1 === currentPage);
      btn.classList.toggle('btn-secondary', index + 1 !== currentPage);
    } else {
      btn.style.display = 'none';
    }
  });

  prevPageBtn.disabled = currentPage === 1;
  nextPageBtn.disabled = currentPage === totalPages || totalPages === 0;
}

function goToPage(page) {
  currentPage = page;
  renderCandidateTable();
}

function goToPrevPage() {
  if (currentPage > 1) {
    currentPage--;
    renderCandidateTable();
  }
}

function goToNextPage() {
  const totalPages = Math.ceil(filteredCandidates.length / itemsPerPage);
  if (currentPage < totalPages) {
    currentPage++;
    renderCandidateTable();
  }
}

// ================== AVATAR (EDIT) ==================
function handleEditAvatarUpload(e) {
  const file = e.target.files[0];
  if (!file) return;

  const reader = new FileReader();
  reader.onload = ev => {
    document.getElementById('editAvatarPreview').src = ev.target.result;
    document.getElementById('viewAvatar').src = ev.target.result;
    const candidateId = document.getElementById('editCandidateId').value;
    const idx = candidates.findIndex(c => c.id === candidateId);
    if (idx !== -1) {
      candidates[idx].avatar = ev.target.result;
    }
  };
  reader.readAsDataURL(file);
}

// ================== MODALS ==================
function openAddCandidateModal() {
  if (userRole !== 'admin') {
    showToast('Access denied. Only administrators can add candidates.', 'error');
    return;
  }
  addCandidateForm.reset();
  document.getElementById('avatarPreview').src = '../assests/default-avatar.jpg';
  addCandidateModal.style.display = 'flex';
}

function openEditCandidateModal(candidateId) {
  if (userRole !== 'admin') {
    showToast('Access denied. Only administrators can edit candidates.', 'error');
    return;
  }
  const candidate = candidates.find(c => c.id === candidateId);
  if (!candidate) return;

  document.getElementById('editCandidateId').value = candidate.id;
  document.getElementById('editCandidateName').value = candidate.name;
  document.getElementById('editCandidateEmail').value = candidate.email;
  document.getElementById('editCandidateStudentId').value = candidate.studentId;
  document.getElementById('editCandidateYearLevel').value = candidate.yearLevel;
  document.getElementById('editCandidatePosition').value = candidate.position;
  document.getElementById('editCandidateOrganization').value = candidate.organization;
  document.getElementById('editCandidatePartyList').value = candidate.partyList;
  document.getElementById('editCandidateProgram').value = candidate.program;
  document.getElementById('editCandidatePlatform').value = candidate.platform;
  document.getElementById('editCandidateExperience').value = candidate.experience;
  document.getElementById('editCandidateStatus').value = candidate.status;
  document.getElementById('editAvatarPreview').src = candidate.avatar;

  editCandidateModal.style.display = 'flex';
}

function viewCandidateProfile(candidateId) {
  const candidate = candidates.find(c => c.id === candidateId);
  if (!candidate) return;

  document.getElementById('viewCandidateName').textContent = candidate.name;
  document.getElementById('viewPosition').textContent = candidate.position;

  let partyDisplayName = '';
  switch(candidate.partyList) {
    case 'Progress': partyDisplayName = 'Progress Party'; break;
    case 'Unity': partyDisplayName = 'Unity Alliance'; break;
    case 'Vision': partyDisplayName = 'Vision Coalition'; break;
    case 'Independent': partyDisplayName = 'Independent'; break;
    default: partyDisplayName = candidate.partyList;
  }

  document.getElementById('viewPartyList').textContent = partyDisplayName;
  document.getElementById('viewOrganization').textContent = candidate.organization;
  document.getElementById('viewYearLevel').textContent = candidate.yearLevel;
  document.getElementById('viewStudentId').textContent = candidate.studentId;
  document.getElementById('viewEmail').textContent = candidate.email;
  document.getElementById('viewProgram').textContent = candidate.program;
  document.getElementById('viewPlatform').textContent = candidate.platform;
  document.getElementById('viewExperience').textContent = candidate.experience;
  document.getElementById('viewAvatar').src = candidate.avatar;

  const statusBadge = document.getElementById('viewStatusBadge');
  statusBadge.textContent = candidate.status.charAt(0).toUpperCase() + candidate.status.slice(1);
  statusBadge.className = `status-badge status-${candidate.status}`;

  editFromView.setAttribute('data-id', candidate.id);
  editFromView.style.display = (userRole === 'admin') ? 'block' : 'none';

  viewCandidateModal.style.display = 'flex';
}

function editFromViewProfile() {
  const candidateId = editFromView.getAttribute('data-id');
  viewCandidateModal.style.display = 'none';
  openEditCandidateModal(candidateId);
}

function confirmDeleteCandidate(candidateId) {
  if (userRole !== 'admin') {
    showToast('Access denied. Only administrators can delete candidates.', 'error');
    return;
  }
  const candidate = candidates.find(c => c.id === candidateId);
  if (!candidate) return;

  candidateToDelete = candidateId;
  deleteConfirmationText.textContent =
    `Are you sure you want to delete candidate ${candidate.name} (${candidateId})? This action cannot be undone.`;
  deleteConfirmationModal.style.display = 'flex';
}

function openBulkApproveModal() {
  if (userRole !== 'admin') {
    showToast('Access denied. Only administrators can approve candidates.', 'error');
    return;
  }
  const pending = candidates.filter(c => c.status === 'pending');
  if (pending.length === 0) {
    showToast('No pending candidates to approve', 'warning');
    return;
  }
  bulkApproveText.textContent =
    `Are you sure you want to approve all ${pending.length} pending candidates?`;
  bulkApproveModal.style.display = 'flex';
}

function bulkApproveCandidates() {
  const pending = candidates.filter(c => c.status === 'pending');
  if (pending.length === 0) return;

  pending.forEach(c => c.status = 'approved');
  updateCandidateStats();
  renderPartyListSummary();
  renderCandidateTable();
  bulkApproveModal.style.display = 'none';
  showToast(`${pending.length} candidates approved successfully!`, 'success');
}

// ================== SAVE / UPDATE / DELETE ==================
async function saveNewCandidate(e) {
  if (e && e.preventDefault) e.preventDefault();

  const full = document.getElementById('candidateName').value.trim();
  const email = document.getElementById('candidateEmail').value.trim();
  const studentID = document.getElementById('candidateStudentId').value.trim();
  const year = document.getElementById('candidateYearLevel').value;
  const positionId = document.getElementById('candidatePosition').value;
  const orgId = document.getElementById('candidateOrganization').value;
  const party = document.getElementById('candidatePartyList').value;
  const program = document.getElementById('candidateProgram').value.trim();
  const platform = document.getElementById('candidatePlatform').value.trim();
  const experience = document.getElementById('candidateExperience').value.trim();
  const status = document.getElementById('candidateStatus').value;

  if (!full || !email || !studentID || !year || !positionId || !orgId || !party ||
      !program || !platform || !experience) {
    showToast('Please fill in all required fields', 'error');
    return;
  }

  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    showToast('Please enter a valid email address', 'error');
    return;
  }

  const fd = new FormData();
  fd.append('FullName', full);
  fd.append('Email', email);
  fd.append('StudentID', studentID);
  fd.append('YearLevel', year);
  fd.append('Position_ID', positionId);
  fd.append('Org_ID', orgId);
  fd.append('PartyList', party);
  fd.append('Program', program);
  fd.append('Platform', platform);
  fd.append('Experience', experience);
  fd.append('Status', status);

  const avatarInput = document.getElementById('avatarUpload');
  if (avatarInput && avatarInput.files && avatarInput.files.length > 0) {
    fd.append('avatar', avatarInput.files[0]);
  }

  saveCandidate.disabled = true;
  saveCandidate.textContent = 'Saving...';

  try {
    const res = await fetch(ENDPOINTS.ADD, {
      method: 'POST',
      body: fd,
      credentials: 'same-origin'
    });
    const json = await res.json();

    if (!res.ok || !json.success) {
      const msg = (json && json.message) ? json.message : 'Server error while saving candidate';
      showToast(msg, 'error');
      console.error('add_candidate response:', json);
      return;
    }

    const newId = json.insert_id || null;
    const avatarPreviewSrc = document.getElementById('avatarPreview').src || '../assests/default-avatar.jpg';

    const candidateObj = {
      id: newId ? 'C' + String(newId).padStart(3, '0') : 'C' + String(candidates.length + 1).padStart(3, '0'),
      name: full,
      email: email,
      studentId: studentID,
      yearLevel: year,
      position: document.getElementById('candidatePosition').selectedOptions[0].text,
      organization: document.getElementById('candidateOrganization').selectedOptions[0].text,
      partyList: party,
      program: program,
      platform: platform,
      experience: experience,
      avatar: avatarPreviewSrc,
      status: status
    };

    candidates.push(candidateObj);
    updateCandidateStats();
    renderPartyListSummary();
    filterCandidates();
    renderCandidateTable();

    addCandidateModal.style.display = 'none';
    addCandidateForm.reset();
    document.getElementById('avatarPreview').src = '../assests/default-avatar.jpg';

    showToast('Candidate added successfully!', 'success');
  } catch (err) {
    console.error('Error adding candidate', err);
    showToast('Server exception occurred', 'error');
  } finally {
    saveCandidate.disabled = false;
    saveCandidate.textContent = 'Save Candidate';
  }
}

async function updateExistingCandidate(e) {
  if (e && e.preventDefault) e.preventDefault();

  const candidateId = document.getElementById('editCandidateId').value;
  const payload = new FormData();

  payload.append('Candidate_ID', candidateId);
  payload.append('FullName', document.getElementById('editCandidateName').value.trim());
  payload.append('Email', document.getElementById('editCandidateEmail').value.trim());
  payload.append('StudentID', document.getElementById('editCandidateStudentId').value.trim());
  payload.append('YearLevel', document.getElementById('editCandidateYearLevel').value);
  payload.append('Position', document.getElementById('editCandidatePosition').value);
  payload.append('Organization', document.getElementById('editCandidateOrganization').value);
  payload.append('PartyList', document.getElementById('editCandidatePartyList').value);
  payload.append('Program', document.getElementById('editCandidateProgram').value.trim());
  payload.append('Platform', document.getElementById('editCandidatePlatform').value.trim());
  payload.append('Experience', document.getElementById('editCandidateExperience').value.trim());
  payload.append('Status', document.getElementById('editCandidateStatus').value);

  const editAvatarInput = document.getElementById('editAvatarUpload');
  if (editAvatarInput && editAvatarInput.files && editAvatarInput.files.length > 0) {
    payload.append('avatar', editAvatarInput.files[0]);
  }

  try {
    updateCandidate.disabled = true;
    updateCandidate.textContent = 'Updating...';

    const res = await fetch(ENDPOINTS.UPDATE, {
      method: 'POST',
      body: payload,
      credentials: 'same-origin'
    });
    const json = await res.json();

    if (!res.ok || !json.success) {
      showToast(json.message || 'Failed to update candidate', 'error');
      return;
    }

    const updated = json.candidate || null;
    if (updated) {
      const idx = candidates.findIndex(c => c.id == candidateId);
      if (idx !== -1) {
        candidates[idx] = {
          id: updated.id || candidateId,
          name: updated.FullName || updated.name,
          email: updated.Email || updated.email,
          studentId: updated.StudentID || updated.student_id,
          yearLevel: updated.YearLevel || updated.year_level,
          position: updated.Position || updated.position,
          organization: updated.Organization || updated.organization,
          partyList: updated.PartyList || updated.partyList,
          program: updated.Program || updated.program,
          platform: updated.Platform || updated.platform,
          experience: updated.Experience || updated.experience,
          avatar: updated.Avatar || document.getElementById('editAvatarPreview').src,
          status: (updated.Status || 'pending').toLowerCase()
        };
      }
    } else {
      const idx = candidates.findIndex(c => c.id == candidateId);
      if (idx !== -1) {
        candidates[idx].name = document.getElementById('editCandidateName').value.trim();
        candidates[idx].email = document.getElementById('editCandidateEmail').value.trim();
        candidates[idx].studentId = document.getElementById('editCandidateStudentId').value.trim();
        candidates[idx].yearLevel = document.getElementById('editCandidateYearLevel').value;
        candidates[idx].position = document.getElementById('editCandidatePosition').value;
        candidates[idx].organization = document.getElementById('editCandidateOrganization').value;
        candidates[idx].partyList = document.getElementById('editCandidatePartyList').value;
        candidates[idx].program = document.getElementById('editCandidateProgram').value.trim();
        candidates[idx].platform = document.getElementById('editCandidatePlatform').value.trim();
        candidates[idx].experience = document.getElementById('editCandidateExperience').value.trim();
        candidates[idx].status = document.getElementById('editCandidateStatus').value;
        candidates[idx].avatar = document.getElementById('editAvatarPreview').src;
      }
    }

    updateCandidateStats();
    renderPartyListSummary();
    filterCandidates();
    renderCandidateTable();
    editCandidateModal.style.display = 'none';
    showToast('Candidate updated successfully!', 'success');
  } catch (err) {
    console.error(err);
    showToast('Server error updating candidate', 'error');
  } finally {
    updateCandidate.disabled = false;
    updateCandidate.textContent = 'Update Candidate';
  }
}

async function deleteCandidateConfirmed() {
  if (!candidateToDelete) return;

  try {
    const fd = new FormData();
    fd.append('Candidate_ID', candidateToDelete);

    const res = await fetch(ENDPOINTS.DELETE, {
      method: 'POST',
      body: fd,
      credentials: 'same-origin'
    });
    const json = await res.json();

    if (!res.ok || !json.success) {
      showToast(json.message || 'Failed to delete candidate', 'error');
      return;
    }

    candidates = candidates.filter(c => c.id !== candidateToDelete);
    updateCandidateStats();
    renderPartyListSummary();
    filterCandidates();
    renderCandidateTable();

    deleteConfirmationModal.style.display = 'none';
    candidateToDelete = null;
    showToast('Candidate deleted successfully!', 'success');
  } catch (err) {
    console.error(err);
    showToast('Server error deleting candidate', 'error');
  }
}

// ================== EXPORT ==================
function exportCandidates() {
  showToast('Exporting candidate list...', 'info');
  setTimeout(() => {
    const csvContent = "data:text/csv;charset=utf-8," +
      "ID,Name,Email,Student ID,Year Level,Position,Organization,Party List,Program,Platform,Experience,Status\n" +
      candidates.map(c =>
        `${c.id},${c.name},${c.email},${c.studentId},${c.yearLevel},${c.position},${c.organization},${c.partyList},${c.program},"${c.platform}","${c.experience}",${c.status}`
      ).join("\n");

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "party_list_candidates.csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    showToast('Candidate list exported successfully!', 'success');
  }, 1500);
}

// ================== START ==================
document.addEventListener('DOMContentLoaded', initPage);
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

        .notification {
            position: relative;
            cursor: pointer;
        }

        .notification i {
            font-size: 1.2rem;
            color: var(--gray);
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--danger);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
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

        /* Party List Management */
        .party-list-management {
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

        /* Party List Tabs */
        .party-list-tabs {
            display: flex;
            background: var(--light-gray);
            border-radius: 8px;
            padding: 5px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 5px;
        }

        .party-list-tab {
            flex: 1;
            padding: 12px 20px;
            text-align: center;
            cursor: pointer;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-weight: 600;
            min-width: 140px;
        }

        .party-list-tab:hover {
            background: rgba(0, 51, 102, 0.1);
        }

        .party-list-tab.active {
            background: var(--primary);
            color: white;
        }

        .party-progress { background-color: var(--party-progress); }
        .party-unity { background-color: var(--party-unity); }
        .party-vision { background-color: var(--party-vision); }
        .party-independent { background-color: var(--party-independent); }

        /* Candidate Table */
        .candidate-table-container {
            overflow-x: auto;
        }

        .candidate-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .candidate-table th {
            background: var(--light-gray);
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: var(--primary);
            border-bottom: 2px solid var(--primary);
        }

        .candidate-table td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--light-gray);
        }

        .candidate-table tr:hover {
            background: rgba(0, 51, 102, 0.03);
        }

        .candidate-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--accent);
        }

        .party-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
          color: var(--gray);
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-approved {
            background: rgba(75, 181, 67, 0.1);
            color: var(--success);
        }

        .status-pending {
            background: rgba(255, 204, 0, 0.1);
            color: var(--warning);
        }

        .status-rejected {
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger);
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .btn-action {
            padding: 6px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.3s ease;
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

        /* Candidate Stats */
        .candidate-stats {
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

        /* Party List Summary */
        .party-list-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .party-list-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .party-list-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .party-list-card.active {
            border-color: var(--primary);
            background: rgba(0, 51, 102, 0.05);
        }

        .party-list-card h3 {
            color: var(--primary);
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .party-list-count {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .party-list-stats {
            font-size: 0.8rem;
            color: var(--gray);
        }

        /* Party Detail View */
        .party-detail-view {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .party-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--light-gray);
        }

        .party-logo {
            width: 80px;
            height: 80px;
            border-radius: 10px;
            object-fit: cover;
            border: 2px solid var(--accent);
        }

        .party-info h2 {
            color: var(--primary);
            margin-bottom: 5px;
        }

        .party-slogan {
            color: var(--gray);
            font-style: italic;
            margin-bottom: 10px;
        }

        .party-meta {
            display: flex;
            gap: 15px;
            font-size: 0.9rem;
            color: var(--gray);
        }

        .party-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .candidates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .candidate-card {
            background: var(--light);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .candidate-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .candidate-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .candidate-avatar-lg {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--accent);
        }

        .candidate-basic-info h3 {
            color: var(--primary);
            margin-bottom: 5px;
        }

        .candidate-position {
            color: var(--secondary);
            font-weight: 600;
            margin-bottom: 3px;
        }

        .candidate-program {
            color: var(--gray);
            font-size: 0.9rem;
        }

        .candidate-details {
            margin-top: 15px;
        }

        .detail-section {
            margin-bottom: 15px;
        }

        .detail-section h4 {
            color: var(--primary);
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .platform-list, .achievements-list {
            list-style-type: none;
            padding-left: 0;
        }

        .platform-list li, .achievements-list li {
            padding: 5px 0;
            padding-left: 15px;
            position: relative;
            font-size: 0.9rem;
        }

        .platform-list li:before, .achievements-list li:before {
            content: "•";
            color: var(--secondary);
            position: absolute;
            left: 0;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            border-radius: 10px;
            width: 600px;
            max-width: 90%;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            background: var(--primary);
            color: white;
            padding: 15px 20px;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .modal-header h3 {
            margin: 0;
        }

        .close-modal {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
        }

        .modal-body {
            padding: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: var(--primary);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--light-gray);
            border-radius: 5px;
            font-size: 0.9rem;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .avatar-upload {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .avatar-preview {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 2px solid var(--accent);
            object-fit: cover;
        }

        .upload-btn {
            background: var(--light-gray);
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid var(--light-gray);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            position: sticky;
            bottom: 0;
            background: white;
        }

        /* Candidate Profile */
        .candidate-profile {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--accent);
        }

        .profile-info {
            flex: 1;
        }

        .profile-info h3 {
            color: var(--primary);
            margin-bottom: 5px;
        }

        .profile-meta {
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 10px;
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

        /* Confirmation Modal */
        .confirmation-modal .modal-content {
            width: 400px;
        }

        .confirmation-modal .modal-body {
            text-align: center;
            padding: 30px 20px;
        }

        .confirmation-modal .modal-body i {
            font-size: 3rem;
            color: var(--warning);
            margin-bottom: 15px;
        }

        .confirmation-modal .modal-body h3 {
            color: var(--primary);
            margin-bottom: 10px;
        }

        .confirmation-modal .modal-body p {
            color: var(--gray);
            margin-bottom: 20px;
        }

        .confirmation-modal .modal-footer {
            justify-content: center;
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

        /* View Toggle */
        .view-toggle {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 15px;
        }

        .view-toggle-btn {
            background: var(--light-gray);
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .view-toggle-btn.active {
            background: var(--primary);
            color: white;
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
                justify-content: space-between;
            }

            .candidate-stats {
                grid-template-columns: 1fr 1fr;
            }

            .party-list-summary {
                grid-template-columns: 1fr 1fr;
            }

            .party-list-tabs {
                flex-direction: column;
            }

            .party-list-tab {
                min-width: auto;
            }

            .candidate-profile {
                flex-direction: column;
                text-align: center;
            }

            .party-header {
                flex-direction: column;
                text-align: center;
            }

            .candidates-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 15px;
            }

            .candidate-stats {
                grid-template-columns: 1fr;
            }

            .party-list-summary {
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
        }
    </style>
</body>
</html>
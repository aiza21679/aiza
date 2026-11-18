<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>eBOTO | Officers Management</title>
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
    .sidebar { width: var(--sidebar-width); background: linear-gradient(180deg, var(--ustp-blue) 0%, var(--ustp-light-blue) 100%); color: var(--ustp-white); padding: 20px 0; display: flex; flex-direction: column; align-items: center; position: fixed; top: 0; left: 0; height: 100vh; box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1); z-index: 100; transition: 0.3s ease; }
    .logo-container { text-align: center; width: 100%; padding: 0 20px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); margin-bottom: 20px; }
    .logo { width: 130px; height: 130px; border-radius: 50%; overflow: hidden; margin: 0 auto 10px auto; border: 4px solid var(--ustp-gold); background-color: var(--ustp-white); }
    .logo img { width: 100%; height: 100%; object-fit: cover; }
    .logo-container h2 { font-size: 1.3rem; font-weight: 700; color: var(--ustp-white); }

    /* NAVIGATION */
    .nav-menu { flex: 1; list-style: none; padding: 0; width: 100%; }
    .nav-item { padding: 14px 25px; display: flex; align-items: center; gap: 12px; cursor: pointer; transition: all 0.3s ease; font-size: 15px; }
    .nav-item:hover { background-color: #004b99; }
    .nav-item.active { background-color: var(--ustp-gold); color: var(--ustp-blue); font-weight: bold; border-left: 4px solid var(--ustp-gold); }
    .nav-item i { width: 20px; text-align: center; }
    .nav-item a { text-decoration: none; color: inherit; display: block; width: 100%; }

    /* USER PROFILE */
    .user-profile { padding: 20px; border-top: 1px solid rgba(255, 255, 255, 0.1); display: flex; align-items: center; gap: 12px; position: relative; cursor: pointer; width: 100%; }
    .user-avatar { width: 40px; height: 40px; border-radius: 50%; border: 2px solid var(--ustp-gold); object-fit: cover; }
    .user-info h4 { font-size: 0.95rem; margin-bottom: 2px; }
    .user-info p { font-size: 0.8rem; opacity: 0.8; }
    .user-dropdown { display: none; position: absolute; bottom: 70px; left: 20px; background: var(--ustp-blue); border: 1px solid var(--ustp-gold); border-radius: 8px; overflow: hidden; width: 150px; z-index: 10; }
    .user-dropdown a { display: block; padding: 10px 20px; color: var(--ustp-white); text-decoration: none; }
    .user-dropdown a:hover { background: var(--ustp-gold); color: var(--ustp-blue); }

    /* MAIN CONTENT */
    .main-content { flex: 1; margin-left: var(--sidebar-width); display: flex; flex-direction: column; transition: margin-left 0.3s ease; }
    .header { height: var(--header-height); background: var(--ustp-white); display: flex; align-items: center; justify-content: space-between; padding: 0 30px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); position: sticky; top: 0; z-index: 10; }
    .header h1 { color: var(--ustp-blue); font-size: 22px; font-weight: 700; }
    .menu-toggle { display: none; font-size: 22px; color: var(--ustp-blue); cursor: pointer; }
    .content { padding: 30px; flex: 1; }

    /* Department Header */
    .department-header { background: var(--ustp-white); border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); padding: 25px; margin-bottom: 25px; display: flex; align-items: center; gap: 20px; }
    .department-avatar { width: 80px; height: 80px; border-radius: 50%; border: 3px solid var(--ustp-gold); object-fit: cover; background-color: var(--ustp-white); }
    .department-info h2 { color: var(--ustp-blue); font-size: 24px; margin-bottom: 5px; }
    .department-info p { color: var(--ustp-dark-gray); font-size: 16px; opacity: 0.8; }
    .department-stats { display: flex; gap: 30px; margin-left: auto; }
    .stat { text-align: center; padding: 10px 20px; background: rgba(0, 102, 204, 0.05); border-radius: 8px; }
    .stat-value { font-size: 24px; font-weight: 700; color: var(--ustp-blue); }
    .stat-label { font-size: 14px; color: #666; }

    /* Officer Header */
    .officer-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
    .officer-title { color: var(--ustp-blue); font-size: 24px; }
    .search-box { display: flex; gap: 10px; }
    .search-input { padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; width: 300px; font-size: 14px; }
    .search-input:focus { outline: none; border-color: var(--ustp-blue); }
    .search-btn { background: var(--ustp-blue); color: white; border: none; border-radius: 6px; padding: 0 20px; cursor: pointer; transition: 0.3s; }
    .search-btn:hover { background: var(--ustp-light-blue); }
    .add-org-btn, .add-officer-btn { background: var(--ustp-gold); color: var(--ustp-blue); border: none; border-radius: 6px; padding: 10px 20px; cursor: pointer; font-weight: 600; transition: 0.3s; display: flex; align-items: center; gap: 8px; }
    .add-org-btn:hover, .add-officer-btn:hover { background: #e6b800; }

    /* Department Cards */
    .department-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 25px; margin-bottom: 30px; }
    .department-card { background: var(--ustp-white); border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); overflow: hidden; transition: all 0.3s ease; cursor: pointer; border: 2px solid transparent; }
    .department-card:hover { transform: translateY(-8px); box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15); border-color: var(--ustp-gold); }
    .department-card-header { padding: 20px; background: var(--ustp-blue); color: white; display: flex; align-items: center; gap: 15px; }
    .department-card-avatar { width: 50px; height: 50px; border-radius: 50%; border: 2px solid var(--ustp-gold); object-fit: cover; background-color: var(--ustp-white); }
    .department-card-info h3 { font-size: 18px; margin-bottom: 5px; }
    .department-card-info p { font-size: 14px; opacity: 0.9; }
    .department-card-stats { padding: 15px 20px; display: flex; justify-content: space-between; background: rgba(0, 102, 204, 0.05); }
    .department-stat { text-align: center; }
    .department-stat-value { font-size: 20px; font-weight: 700; color: var(--ustp-blue); }
    .department-stat-label { font-size: 12px; color: #666; }
    .department-card-actions { padding: 15px 20px; border-top: 1px solid #eee; text-align: center; }
    .btn-view-department { background: var(--ustp-blue); color: white; border: none; border-radius: 6px; padding: 10px 20px; cursor: pointer; font-weight: 600; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
    .btn-view-department:hover { background: var(--ustp-light-blue); }

    /* Filters */
    .position-filter { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; }
    .position-filter-label { font-weight: 600; color: var(--ustp-blue); }
    .position-filter-select { padding: 8px 15px; border: 1px solid #ddd; border-radius: 6px; background: white; font-size: 14px; }

    /* Officer Table */
    .officer-table-container { background: var(--ustp-white); border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); overflow: hidden; }
    .officer-table { width: 100%; border-collapse: collapse; }
    .officer-table th { background-color: var(--ustp-blue); color: var(--ustp-white); padding: 15px; text-align: left; font-weight: 600; }
    .officer-table td { padding: 15px; border-bottom: 1px solid #eee; }
    .officer-table tr:last-child td { border-bottom: none; }
    .officer-table tr:hover { background-color: rgba(0, 102, 204, 0.03); }
    .officer-actions { display: flex; gap: 8px; }
    .action-btn { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; transition: 0.3s; }
    .edit-btn { background: var(--ustp-gold); color: var(--ustp-blue); }
    .edit-btn:hover { background: #e6b800; }
    .delete-btn { background: #dc3545; color: white; }
    .delete-btn:hover { background: #c82333; }
    .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .status-active { background: #d4edda; color: #155724; }
    .status-pending { background: #fff3cd; color: #856404; }
    .status-inactive { background: #f8d7da; color: #721c24; }
    .position-badge { display: inline-block; padding: 3px 8px; border-radius: 12px; font-size: 11px; margin: 2px; background: rgba(0, 102, 204, 0.1); color: var(--ustp-blue); border: 1px solid rgba(0, 102, 204, 0.2); }

    /* Modal */
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 1000; justify-content: center; align-items: center; }
    .modal-content { background-color: white; padding: 30px; border-radius: 10px; width: 90%; max-width: 500px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15); max-height: 90vh; overflow-y: auto; }
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

    /* Form Styles */
    .form-group { margin-bottom: 20px; }
    .form-label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--ustp-blue); }
    .form-input { width: 100%; padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
    .form-input:focus { outline: none; border-color: var(--ustp-blue); }
    .form-row { display: flex; gap: 15px; }
    .form-row .form-group { flex: 1; }
    .position-section { background: rgba(0, 102, 204, 0.05); padding: 15px; border-radius: 8px; margin-bottom: 20px; }
    .position-section h3 { color: var(--ustp-blue); margin-bottom: 10px; font-size: 16px; }

    /* Toast */
    .toast { position: fixed; bottom: 20px; right: 20px; padding: 15px 25px; background-color: var(--ustp-blue); color: white; border-radius: 5px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); z-index: 1001; display: flex; align-items: center; gap: 10px; transform: translateY(100px); opacity: 0; transition: transform 0.3s, opacity 0.3s; }
    .toast.show { transform: translateY(0); opacity: 1; }
    .toast.success { background-color: #4caf50; }
    .toast.error { background-color: #f44336; }

    /* Empty state */
    .empty-state { text-align: center; padding: 40px 20px; color: #666; }
    .empty-state i { font-size: 48px; margin-bottom: 15px; color: #ccc; }

    /* Responsive */
    @media (max-width: 768px) {
      .sidebar { width: 70px; }
      .logo-container h2, .nav-item a { display: none; }
      .main-content { margin-left: 70px; }
      .menu-toggle { display: block; }
      .officer-header { flex-direction: column; align-items: flex-start; gap: 15px; }
      .search-box { width: 100%; }
      .search-input { width: 100%; }
      .officer-table-container { overflow-x: auto; }
      .form-row { flex-direction: column; gap: 0; }
      .department-cards { grid-template-columns: 1fr; }
      .department-header { flex-direction: column; text-align: center; }
      .department-stats { margin-left: 0; justify-content: center; }
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
      <li class="nav-item active"><i class="fas fa-user-tie"></i><a href="officers.php">Officers</a></li>
      <li class="nav-item"><i class="fas fa-user-tag"></i><a href="candidates.php">Candidates</a></li>
      <li class="nav-item"><i class="fas fa-check-circle"></i><a href="validate-elections.php">Elections</a></li>
      <li class="nav-item "><i class="fas fa-chart-line"></i><a href="monitoring.php">Monitoring</a></li>
      <li class="nav-item"><i class="fas fa-file-alt"></i><a href="audit-reports.php">Reports</a></li>
      <li class="nav-item"><i class="fas fa-cog"></i><a href="settings.php">Settings</a></li>
    </ul>

    <!-- USER PROFILE (filled in dynamically from getAdmin.php) -->
    <div class="user-profile" id="userProfile">
      <img src="../assests/default-avatar.jpg" class="user-avatar" id="sidebarAvatar" alt="avatar" />
      <div class="user-info">
        <h4 id="sidebarName">Loading...</h4>
        <p id="sidebarRole">&nbsp;</p>
      </div>
      <div class="user-dropdown" id="dropdown">
        <a href="../login.html" id="logoutBtn">Logout</a>
      </div>
    </div>
  </nav>

  <!-- MAIN CONTENT -->
  <div class="main-content" id="mainContent">
    <div class="header">
      <h1>Officers Management</h1>
      <i class="fas fa-bars menu-toggle" id="menuToggle"></i>
    </div>

    <div class="content">
      <!-- Department Header -->
      <div class="department-header" id="departmentHeader" style="display: none;">
        <img src="../assets/default-logo.jpg" class="department-avatar" alt="Department Logo" id="deptAvatar">
        <div class="department-info">
          <h2 id="deptName">Organization Name</h2>
          <p id="deptDescription">Organization Description</p>
        </div>
        <div class="department-stats">
          <div class="stat">
            <div class="stat-value" id="totalOfficers">0</div>
            <div class="stat-label">Total Officers</div>
          </div>
          <div class="stat">
            <div class="stat-value" id="positionsCount">0</div>
            <div class="stat-label">Positions</div>
          </div>
        </div>
      </div>

      <!-- Department Cards View (Default view) -->
      <div id="departmentsView">
        <div class="officer-header">
          <h2 class="officer-title">Manage Officers by Organizations</h2>
          <div class="search-box">
            <input type="text" class="search-input" placeholder="Search departments..." id="deptSearchInput">
            <button class="search-btn"><i class="fas fa-search"></i></button>
            <button class="add-org-btn" id="addOrgBtn">
              <i class="fas fa-plus"></i> Add Organization
            </button>
          </div>
        </div>

        <div class="department-cards" id="departmentCards">
          <!-- Department cards will be dynamically generated here -->
        </div>
      </div>

      <!-- Officers Management View (Hidden by default) -->
      <div id="officersManagementView" style="display: none;">
        <div class="officer-header">
          <h2>Manage Department Officers</h2>
          <div class="search-box">
            <input type="text" class="search-input" placeholder="Search officers..." id="searchInput">
            <button class="search-btn"><i class="fas fa-search"></i></button>
            <button class="add-officer-btn" id="addOfficerBtn">
              <i class="fas fa-plus"></i> Add Officer
            </button>
          </div>
        </div>

        <!-- Position Filter -->
        <div class="position-filter">
          <span class="position-filter-label">Filter by Position:</span>
          <select class="position-filter-select" id="positionFilter">
            <option value="all">All Positions</option>
            <option value="president">President</option>
            <option value="vice-president">Vice President</option>
            <option value="secretary">Secretary</option>
            <option value="treasurer">Treasurer</option>
            <option value="auditor">Auditor</option>
            <option value="pro">Public Relations Officer</option>
            <option value="tech-lead">Tech Lead</option>
            <option value="board-member">Board Member</option>
          </select>
        </div>

        <!-- Status Filter -->
        <div class="position-filter">
          <span class="position-filter-label">Filter by Status:</span>
          <select class="position-filter-select" id="statusFilter">
            <option value="all">All Status</option>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
          </select>
        </div>

        <!-- Officer Table -->
        <div class="officer-table-container">
          <table class="officer-table" id="officerTable">
            <thead>
              <tr>
                <th>Officer ID</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Position</th>
                <th>Year Level</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="officerTableBody">
              <!-- Officer data will be dynamically generated here -->
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Add Organization Modal -->
  <div class="modal" id="addOrgModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Add New Organization</h2>
        <button class="close-modal">&times;</button>
      </div>
      <form id="orgForm">
        <div class="form-group">
          <label class="form-label" for="orgID">Organization Code</label>
          <input type="text" class="form-input" id="orgID" name="orgID" placeholder="e.g., IT" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="course">Course</label>
          <select class="form-input" id="course" name="course" required>
            <option value="" disabled selected>Select Course</option>
            <option value="Information Technology">Information Technology</option>
            <option value="Energy System and Management">Energy System and Management</option>
            <option value="Manufacturing Engineering Technology">Manufacturing Engineering Technology</option>
            <option value="Naval Architecture and Marine Engineering">Naval Architecture and Marine Engineering</option>
            <option value="Technology Communication Management">Technology Communication Management</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="orgDescription">Description</label>
          <input type="text" class="form-input" id="orgDescription" name="orgDescription" placeholder="e.g., Department of Information Technology">
        </div>

        <div class="form-group">
          <label class="form-label" for="orgEmail">Email</label>
          <input type="email" class="form-input" id="orgEmail" name="orgEmail" placeholder="e.g., info@organization.com" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="orgImage">Logo URL</label>
          <input type="text" class="form-input" id="orgImage" name="orgImage" placeholder="e.g., ../assets/it-logo.jpg">
        </div>

        <div class="modal-actions">
          <button type="button" class="btn btn-secondary close-modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Add Organization</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Add/Edit Officer Modal -->
  <div class="modal" id="officerModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 id="modalTitle">Add New Officer</h2>
        <button class="close-modal" id="closeOfficerModal">&times;</button>
      </div>
      <form id="officerForm">
        <input type="hidden" id="officerId">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="officerIdInput">Student ID</label>
            <input type="text" class="form-input" id="officerIdInput" placeholder="e.g., IT-2023-001" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="yearLevel">Year Level</label>
            <select class="form-input" id="yearLevel" required>
              <option value="">Select Year Level</option>
              <option value="1st Year">1st Year</option>
              <option value="2nd Year">2nd Year</option>
              <option value="3rd Year">3rd Year</option>
              <option value="4th Year">4th Year</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label" for="fullName">Full Name</label>
          <input type="text" class="form-input" id="fullName" placeholder="e.g., Juan Tamad" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="email">Email Address</label>
          <input type="email" class="form-input" id="email" placeholder="e.g., juan.delacruz@ustp.edu.ph" required>
        </div>

        <!-- Position Selection -->
        <div class="position-section">
          <h3>Officer Position</h3>
          <p class="form-label">Select the position of this officer:</p>

          <select class="form-input" id="position" required>
            <option value="">Select Position</option>
            <option value="president">President</option>
            <option value="vice-president">Vice President</option>
            <option value="secretary">Secretary</option>
            <option value="treasurer">Treasurer</option>
            <option value="auditor">Auditor</option>
            <option value="pro">Public Relations Officer</option>
            <option value="tech-lead">Tech Lead</option>
            <option value="board-member">Board Member</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="status">Status</label>
          <select class="form-input" id="status" required>
            <option value="">Select Status</option>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
          </select>
        </div>
        <div class="modal-actions">
          <button type="button" class="btn btn-secondary" id="cancelOfficerBtn">Cancel</button>
          <button type="submit" class="btn btn-primary" id="saveOfficerBtn">Save Officer</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div class="modal" id="deleteModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Confirm Deletion</h2>
        <button class="close-modal" id="closeDeleteModal">&times;</button>
      </div>
      <p>Are you sure you want to delete this officer? This action cannot be undone.</p>
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" id="cancelDeleteBtn">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
      </div>
    </div>
  </div>

  <!-- Toast notification -->
  <div class="toast" id="toast"></div>

  <script>
// ==========================
// Utility Functions
// ==========================
function getQueryParam(param) {
  const urlParams = new URLSearchParams(window.location.search);
  const value = urlParams.get(param);
  return value ? parseInt(value, 10) : null;
}

function showToast(message, type = 'info') {
  const toast = document.getElementById('toast');
  if (!toast) return;

  toast.textContent = message;
  toast.className = 'toast show';
  if (type === 'success') toast.classList.add('success');
  else if (type === 'error') toast.classList.add('error');
  setTimeout(() => toast.classList.remove('show'), 3000);
}

// ==========================
// Admin Profile (logged-in user)  <-- FIX: no more hardcoded name
// ==========================
const GET_ADMIN = '../php/getAdmin.php';

function normalizeAvatarPath(raw) {
  if (!raw) return '../assests/default-avatar.jpg';
  let s = String(raw).trim().replace(/\\+/g, '/');
  if (!s) return '../assests/default-avatar.jpg';
  if (/^https?:\/\//i.test(s) || s.startsWith('/') || s.startsWith('../')) return s;
  return '../' + s.replace(/^\/+/, '');
}

async function loadAdminProfile() {
  const nameEl = document.getElementById('sidebarName');
  const roleEl = document.getElementById('sidebarRole');
  const avatarEl = document.getElementById('sidebarAvatar');
  try {
    const res = await fetch(GET_ADMIN, { cache: 'no-store' });
    if (!res.ok) throw new Error('HTTP ' + res.status);
    const json = await res.json();
    if (!json || !json.success) throw new Error('getAdmin returned success:false');

    const isAdmin = json.role && /admin/i.test(String(json.role));
    nameEl.textContent = json.name || 'Administrator';
    roleEl.textContent = isAdmin ? 'Administrator' : (json.role || 'User');
    avatarEl.src = normalizeAvatarPath(json.avatar || json.avatar_path);
  } catch (err) {
    console.error('loadAdminProfile error', err);
    nameEl.textContent = 'Administrator';
    roleEl.textContent = '';
  }
}

// ==========================
// Department Header Functions
// ==========================
function showDepartmentHeader(orgId) {
  const departmentHeader = document.getElementById('departmentHeader');
  if (departmentHeader) {
    departmentHeader.style.display = 'flex';
  }
  updateDepartmentHeader(orgId);
}

function updateDepartmentHeader(orgId) {
  if (!orgId) return;

  const officers = getOfficersFromStorage(orgId);
  const totalOfficers = officers.length;
  const uniquePositions = [...new Set(officers.map(o => o.rank))].length;

  const totalOfficersEl = document.getElementById('totalOfficers');
  const positionsCountEl = document.getElementById('positionsCount');

  if (totalOfficersEl) totalOfficersEl.textContent = totalOfficers;
  if (positionsCountEl) positionsCountEl.textContent = uniquePositions;

  fetchOrganizationDetails(orgId);
}

async function fetchOrganizationDetails(orgId) {
  try {
    const response = await fetch('../php/fetchOrganizations.php');
    if (!response.ok) throw new Error('Failed to fetch organization details');

    const data = await response.json();
    const org = data.find(o => Number(o.Org_ID) === Number(orgId));

    if (org) {
      const deptName = document.getElementById('deptName');
      const deptDescription = document.getElementById('deptDescription');
      const deptAvatar = document.getElementById('deptAvatar');

      if (deptName) deptName.textContent = org.Org_Name;
      if (deptDescription) deptDescription.textContent = org.Description || 'No description available';
      if (deptAvatar) {
        deptAvatar.src = org.Logo_Url && org.Logo_Url.trim() !== '' ? org.Logo_Url : '../assets/default-logo.jpg';
      }
    }
  } catch (error) {
    console.error('Error fetching organization details:', error);
  }
}

// ==========================
// Officer Management Data
// ==========================
const positions = {
  "president": { name: "President", level: 1 },
  "vice-president": { name: "Vice President", level: 2 },
  "secretary": { name: "Secretary", level: 3 },
  "treasurer": { name: "Treasurer", level: 4 },
  "auditor": { name: "Auditor", level: 5 },
  "pro": { name: "Public Relations Officer", level: 6 },
  "tech-lead": { name: "Tech Lead", level: 7 },
  "board-member": { name: "Board Member", level: 8 }
};

let officerData = [];

// ==========================
// DOM Elements
// ==========================
const userProfile = document.getElementById('userProfile');
const dropdown = document.getElementById('dropdown');
const menuToggle = document.getElementById('menuToggle');
const sidebar = document.getElementById('sidebar');
const mainContent = document.getElementById('mainContent');
const logoutBtn = document.getElementById('logoutBtn');
const searchInput = document.getElementById('searchInput');
const addOfficerBtn = document.getElementById('addOfficerBtn');
const addOrgBtn = document.getElementById('addOrgBtn');
const officerTableBody = document.getElementById('officerTableBody');
const officerModal = document.getElementById('officerModal');
const addOrgModal = document.getElementById('addOrgModal');
const closeOfficerModal = document.getElementById('closeOfficerModal');
const cancelOfficerBtn = document.getElementById('cancelOfficerBtn');
const officerForm = document.getElementById('officerForm');
const orgForm = document.getElementById('orgForm');
const modalTitle = document.getElementById('modalTitle');
const deleteModal = document.getElementById('deleteModal');
const closeDeleteModal = document.getElementById('closeDeleteModal');
const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
const toast = document.getElementById('toast');
const positionFilter = document.getElementById('positionFilter');
const statusFilter = document.getElementById('statusFilter');
const officerTitle = document.getElementById('officerTitle');
const departmentCards = document.getElementById('departmentCards');
const deptSearchInput = document.getElementById('deptSearchInput');
const departmentsView = document.getElementById('departmentsView');
const officersManagementView = document.getElementById('officersManagementView');

// ==========================
// State Variables
// ==========================
let currentSearch = '';
let currentPositionFilter = 'all';
let currentStatusFilter = 'all';
let currentOfficerToDelete = null;
let isEditing = false;
let currentOrgId = null;
let isViewingDepartment = false;

// ==========================
// Department Cards Management
// ==========================
async function renderDepartmentCards() {
  try {
    const response = await fetch('../php/fetchOrganizations.php', { cache: 'no-store' });

    if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);

    const data = await response.json();

    if (data.error) {
      showToast(data.error, 'error');
      departmentCards.innerHTML = '<p style="color:red; text-align:center; padding:20px;">Failed to load organizations.</p>';
      return;
    }

    let html = '';

    if (data.length === 0) {
      html = `
        <div class="empty-state">
          <i class="fas fa-institution"></i>
          <h3>No Organizations Found</h3>
          <p>Add your first organization to get started with officer management.</p>
        </div>`;
    } else {
      data.forEach(org => {
        const officers = getOfficersFromStorage(org.Org_ID);
        const totalOfficers = officers.length;
        const uniquePositions = [...new Set(officers.map(o => o.rank))].length;

        const logoUrl = org.Logo_Url && org.Logo_Url.trim() !== '' ? org.Logo_Url : '../assets/default-logo.jpg';

        html += `
          <div class="department-card" data-dept-id="${org.Org_ID}">
            <div class="department-card-header">
              <img src="${logoUrl}" class="department-card-avatar" alt="${org.Org_Name} Logo">
              <div class="department-card-info">
                <h3>${org.Org_Name}</h3>
                <p>${org.Description || 'No description available'}</p>
              </div>
            </div>
            <div class="department-card-stats">
              <div class="department-stat">
                <div class="department-stat-value total-officers">${totalOfficers}</div>
                <div class="department-stat-label">Total Officers</div>
              </div>
              <div class="department-stat">
                <div class="department-stat-value positions-count">${uniquePositions}</div>
                <div class="department-stat-label">Positions</div>
              </div>
            </div>
            <div class="department-card-actions">
              <button class="btn-view-department" data-dept-id="${org.Org_ID}">
                <i class="fas fa-arrow-right"></i> View Officers
              </button>
            </div>
          </div>
        `;
      });
    }

    departmentCards.innerHTML = html;

    document.querySelectorAll('.btn-view-department').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const deptId = btn.getAttribute('data-dept-id');
        viewDepartment(deptId);
      });
    });

  } catch (error) {
    console.error('Error loading organizations:', error);
    showToast('Failed to load organizations', 'error');
    departmentCards.innerHTML = '<p style="color:red; text-align:center; padding:20px;">Error loading organizations.</p>';
  }
}

function viewDepartment(orgId) {
  currentOrgId = orgId;
  isViewingDepartment = true;

  departmentsView.style.display = 'none';
  officersManagementView.style.display = 'block';

  showDepartmentHeader(orgId);
  fetchOfficers(orgId);
  updateOfficerTitle(orgId);
}

function updateOfficerTitle(orgId) {
  if (officerTitle) {
    officerTitle.textContent = `Manage Organization ${orgId} Officers`;
  }
}

// ==========================
// LocalStorage Management
// ==========================
function getOfficersFromStorage(orgId) {
  try {
    const stored = localStorage.getItem(`officers_org_${orgId}`);
    return stored ? JSON.parse(stored) : [];
  } catch (error) {
    console.error('Error reading from localStorage:', error);
    return [];
  }
}

function saveOfficersToStorage(orgId, officers) {
  try {
    localStorage.setItem(`officers_org_${orgId}`, JSON.stringify(officers));
    return true;
  } catch (error) {
    console.error('Error saving to localStorage:', error);
    showToast('Error saving data', 'error');
    return false;
  }
}

// ==========================
// Fetch Officers
// ==========================
function fetchOfficers(orgId) {
  showToast('Loading officers...', 'info');

  officerData = getOfficersFromStorage(orgId);

  renderOfficerTable();

  if (officerData.length === 0) {
    showToast(`No officers found for this organization. Add your first officer!`, 'info');
  } else {
    showToast(`Loaded ${officerData.length} officers`, 'success');
  }
}

// ==========================
// Officer Management Functions
// ==========================
function renderOfficerTable() {
  if (!officerTableBody) return;

  officerTableBody.innerHTML = '';

  let filteredOfficers = officerData;

  if (currentSearch) {
    const searchTerm = currentSearch.toLowerCase();
    filteredOfficers = filteredOfficers.filter(officer =>
      officer.officerId.toLowerCase().includes(searchTerm) ||
      officer.firstName.toLowerCase().includes(searchTerm) ||
      officer.lastName.toLowerCase().includes(searchTerm) ||
      officer.email.toLowerCase().includes(searchTerm) ||
      `${officer.firstName} ${officer.lastName}`.toLowerCase().includes(searchTerm)
    );
  }

  if (currentPositionFilter !== 'all') {
    filteredOfficers = filteredOfficers.filter(o => o.rank === currentPositionFilter);
  }

  if (currentStatusFilter !== 'all') {
    filteredOfficers = filteredOfficers.filter(o => o.status === currentStatusFilter);
  }

  updateDepartmentHeader(currentOrgId);

  if (filteredOfficers.length === 0) {
    officerTableBody.innerHTML = `
      <tr>
        <td colspan="7">
          <div class="empty-state">
            <i class="fas fa-user-tie"></i>
            <h3>No officers found</h3>
            <p>Try changing your search term or add a new officer</p>
          </div>
        </td>
      </tr>`;
    return;
  }

  filteredOfficers.forEach(officer => {
    const statusClass = officer.status === 'Active' ? 'status-active' : 'status-inactive';
    const positionName = positions[officer.rank]?.name || officer.rank;

    const officerRow = document.createElement('tr');
    officerRow.innerHTML = `
      <td>${officer.officerId}</td>
      <td>${officer.firstName} ${officer.lastName}</td>
      <td>${officer.email}</td>
      <td><span class="position-badge">${positionName}</span></td>
      <td>${officer.yearLevel}</td>
      <td><span class="status-badge ${statusClass}">${officer.status}</span></td>
      <td>
        <div class="officer-actions">
          <button class="action-btn edit-btn" data-id="${officer.id}">
            <i class="fas fa-edit"></i> Edit
          </button>
          <button class="action-btn delete-btn" data-id="${officer.id}">
            <i class="fas fa-trash"></i> Delete
          </button>
        </div>
      </td>`;
    officerTableBody.appendChild(officerRow);
  });

  document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', e => editOfficer(e.currentTarget.getAttribute('data-id')));
  });

  document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', e => confirmDeleteOfficer(e.currentTarget.getAttribute('data-id')));
  });
}

function addOfficer(formData) {
  const newOfficer = {
    id: Date.now().toString(),
    officerId: formData.officerId,
    firstName: formData.firstName,
    lastName: formData.lastName,
    email: formData.email,
    rank: formData.rank,
    yearLevel: formData.yearLevel,
    status: formData.status
  };

  const isDuplicate = officerData.some(officer =>
    officer.officerId === newOfficer.officerId
  );

  if (isDuplicate) {
    showToast(`Officer with ID ${newOfficer.officerId} already exists!`, 'error');
    return false;
  }

  officerData.push(newOfficer);

  if (saveOfficersToStorage(currentOrgId, officerData)) {
    renderOfficerTable();
    showToast(`Officer "${formData.firstName} ${formData.lastName}" added successfully!`, 'success');
    return true;
  }
  return false;
}

function editOfficer(officerId) {
  const officer = officerData.find(o => o.id === officerId);
  if (!officer) return;

  document.getElementById('officerId').value = officer.id;
  document.getElementById('officerIdInput').value = officer.officerId;
  document.getElementById('fullName').value = `${officer.firstName} ${officer.lastName}`.trim();
  document.getElementById('email').value = officer.email;
  document.getElementById('position').value = officer.rank;
  document.getElementById('yearLevel').value = officer.yearLevel;
  document.getElementById('status').value = officer.status;

  modalTitle.textContent = 'Edit Officer';
  isEditing = true;
  officerModal.style.display = 'flex';
}

function updateOfficer(formData) {
  const index = officerData.findIndex(o => o.id === formData.id);
  if (index === -1) return false;

  const isDuplicate = officerData.some((officer, i) =>
    i !== index && officer.officerId === formData.officerId
  );

  if (isDuplicate) {
    showToast(`Officer with ID ${formData.officerId} already exists!`, 'error');
    return false;
  }

  officerData[index] = { ...officerData[index], ...formData };

  if (saveOfficersToStorage(currentOrgId, officerData)) {
    renderOfficerTable();
    showToast(`Officer "${formData.firstName} ${formData.lastName}" updated successfully!`, 'success');
    return true;
  }
  return false;
}

function confirmDeleteOfficer(officerId) {
  currentOfficerToDelete = officerId;
  deleteModal.style.display = 'flex';
}

function deleteOfficer(officerId) {
  const index = officerData.findIndex(o => o.id === officerId);
  if (index === -1) return false;

  const name = `${officerData[index].firstName} ${officerData[index].lastName}`;
  officerData.splice(index, 1);

  if (saveOfficersToStorage(currentOrgId, officerData)) {
    renderOfficerTable();
    showToast(`Officer "${name}" deleted successfully!`, 'success');
    return true;
  }
  return false;
}

// ==========================
// Event Listeners
// ==========================
function setupEventListeners() {
  // User profile dropdown
  if (userProfile) {
    userProfile.addEventListener('click', () => {
      if (dropdown) dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    });
  }

  // Close dropdown when clicking outside
  document.addEventListener('click', e => {
    if (userProfile && !userProfile.contains(e.target) && dropdown) {
      dropdown.style.display = 'none';
    }
  });

  // Mobile menu toggle
  if (menuToggle) {
    menuToggle.addEventListener('click', () => {
      if (sidebar.style.left === '0px' || sidebar.style.left === '') {
        sidebar.style.left = '-250px';
        mainContent.style.marginLeft = '0';
      } else {
        sidebar.style.left = '0px';
        mainContent.style.marginLeft = '250px';
      }
    });
  }

  // Logout
  if (logoutBtn) {
    logoutBtn.addEventListener('click', (e) => {
      e.preventDefault();
      showToast('Logging out...', 'info');
      setTimeout(() => window.location.href = '../login.html', 1000);
    });
  }

  // Search input
  if (searchInput) {
    searchInput.addEventListener('input', e => {
      currentSearch = e.target.value;
      renderOfficerTable();
    });
  }

  // Department search input
  if (deptSearchInput) {
    deptSearchInput.addEventListener('input', e => {
      const searchTerm = e.target.value.toLowerCase();
      const cards = document.querySelectorAll('.department-card');

      cards.forEach(card => {
        const deptName = card.querySelector('h3').textContent.toLowerCase();
        const deptDesc = card.querySelector('p').textContent.toLowerCase();

        if (deptName.includes(searchTerm) || deptDesc.includes(searchTerm)) {
          card.style.display = 'block';
        } else {
          card.style.display = 'none';
        }
      });
    });
  }

  // Filters
  if (positionFilter) {
    positionFilter.addEventListener('change', e => {
      currentPositionFilter = e.target.value;
      renderOfficerTable();
    });
  }

  if (statusFilter) {
    statusFilter.addEventListener('change', e => {
      currentStatusFilter = e.target.value;
      renderOfficerTable();
    });
  }

  // Add Organization Button
  if (addOrgBtn) {
    addOrgBtn.addEventListener('click', () => {
      addOrgModal.style.display = 'flex';
    });
  }

  // Add Officer Button
  if (addOfficerBtn) {
    addOfficerBtn.addEventListener('click', () => {
      officerForm.reset();
      document.getElementById('officerId').value = '';
      modalTitle.textContent = 'Add New Officer';
      isEditing = false;
      officerModal.style.display = 'flex';
    });
  }

  // Close modal buttons
  const closeModalButtons = document.querySelectorAll('.close-modal');
  closeModalButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      officerModal.style.display = 'none';
      addOrgModal.style.display = 'none';
      officerForm.reset();
      orgForm.reset();
    });
  });

  if (cancelOfficerBtn) {
    cancelOfficerBtn.addEventListener('click', () => {
      officerModal.style.display = 'none';
      officerForm.reset();
    });
  }

  // Close modal when clicking outside
  window.addEventListener('click', e => {
    if (e.target === officerModal) {
      officerModal.style.display = 'none';
      officerForm.reset();
    }
    if (e.target === addOrgModal) {
      addOrgModal.style.display = 'none';
      orgForm.reset();
    }
    if (e.target === deleteModal) {
      deleteModal.style.display = 'none';
      currentOfficerToDelete = null;
    }
  });

  // Officer form submission
  if (officerForm) {
    officerForm.addEventListener('submit', e => {
      e.preventDefault();

      const fullName = document.getElementById('fullName').value.trim();
      const nameParts = fullName.split(' ');
      const firstName = nameParts[0] || '';
      const lastName = nameParts.slice(1).join(' ') || '';

      const formData = {
        id: document.getElementById('officerId').value,
        officerId: document.getElementById('officerIdInput').value,
        firstName: firstName,
        lastName: lastName,
        email: document.getElementById('email').value,
        rank: document.getElementById('position').value,
        yearLevel: document.getElementById('yearLevel').value,
        status: document.getElementById('status').value
      };

      let success = false;
      if (isEditing) {
        success = updateOfficer(formData);
      } else {
        success = addOfficer(formData);
      }

      if (success) {
        officerModal.style.display = 'none';
        officerForm.reset();
      }
    });
  }

  // Organization form submission
  if (orgForm) {
    orgForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      const formData = new FormData(orgForm);
      formData.append('ajax', '1');
      formData.append('action', 'add_organization');
      formData.append('orgCourse', document.getElementById('course').value);
      formData.append('orgEmail', document.getElementById('orgEmail').value);

      const submitBtn = orgForm.querySelector('button[type="submit"]');
      const originalText = submitBtn.textContent;
      submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
      submitBtn.disabled = true;

      try {
        const response = await fetch('', {
          method: 'POST',
          body: formData
        });

        const data = await response.json();

        if (data.status === 'success') {
          addOrgModal.style.display = 'none';
          orgForm.reset();
          showToast(data.message, 'success');
          renderDepartmentCards();
        } else {
          showToast(data.message, 'error');
        }
      } catch (error) {
        console.error('Error adding organization:', error);
        showToast('An error occurred while adding the organization', 'error');
      } finally {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
      }
    });
  }

  // Delete modal buttons
  [closeDeleteModal, cancelDeleteBtn].forEach(btn => {
    if (btn) {
      btn.addEventListener('click', () => {
        deleteModal.style.display = 'none';
        currentOfficerToDelete = null;
      });
    }
  });

  // Confirm delete
  if (confirmDeleteBtn) {
    confirmDeleteBtn.addEventListener('click', () => {
      if (currentOfficerToDelete) {
        const success = deleteOfficer(currentOfficerToDelete);
        if (success) {
          deleteModal.style.display = 'none';
          currentOfficerToDelete = null;
        }
      }
    });
  }
}

// ==========================
// Initialize
// ==========================
async function initOfficerManagement() {
  loadAdminProfile();   // <-- FIX: loads the real logged-in user into the sidebar

  const params = new URLSearchParams(window.location.search);
  currentOrgId = params.get('org');

  if (currentOrgId) {
    viewDepartment(currentOrgId);
  } else {
    await renderDepartmentCards();
  }

  setupEventListeners();
}

// Quick cache busting on page load
window.addEventListener('load', function() {
  if (document.referrer && document.referrer.includes('register-voters')) {
    setTimeout(() => {
      if (departmentCards && !currentOrgId) {
        renderDepartmentCards();
      }
    }, 100);
  }
});

document.addEventListener('DOMContentLoaded', initOfficerManagement);
  </script>
</body>
</html>

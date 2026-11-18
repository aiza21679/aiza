<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>eBOTO | Officer Management</title>
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

    /* USER PROFILE - EXACTLY THE SAME AS OTHER PAGES */
    .user-profile {
      padding: 20px;
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      display: flex;
      align-items: center;
      gap: 12px;
      position: relative;
      cursor: pointer;
      width: 100%;
    }

    .user-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      border: 2px solid var(--ustp-gold);
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
      background: var(--ustp-blue);
      border: 1px solid var(--ustp-gold);
      border-radius: 8px;
      overflow: hidden;
      width: 150px;
      z-index: 10;
    }

    .user-dropdown a {
      display: block;
      padding: 10px 20px;
      color: var(--ustp-white);
      text-decoration: none;
    }

    .user-dropdown a:hover {
      background: var(--ustp-gold);
      color: var(--ustp-blue);
    }

    /* MAIN CONTENT - EXACTLY THE SAME AS OTHER PAGES */
    .main-content {
      flex: 1;
      margin-left: var(--sidebar-width);
      display: flex;
      flex-direction: column;
      transition: margin-left 0.3s ease;
    }

    .header {
      height: var(--header-height);
      background: var(--ustp-white);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 30px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      position: sticky;
      top: 0;
      z-index: 10;
    }

    .header h1 {
      color: var(--ustp-blue);
      font-size: 22px;
      font-weight: 700;
    }

    .menu-toggle {
      display: none;
      font-size: 22px;
      color: var(--ustp-blue);
      cursor: pointer;
    }

    .content {
      padding: 30px;
      flex: 1;
    }

    /* Officer Management Specific Styles */
    .officer-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
    }

    .officer-title {
      color: var(--ustp-blue);
      font-size: 24px;
    }

    .search-box {
      display: flex;
      gap: 10px;
    }

    .search-input {
      padding: 10px 15px;
      border: 1px solid #ddd;
      border-radius: 6px;
      width: 300px;
      font-size: 14px;
    }

    .search-btn {
      background: var(--ustp-blue);
      color: white;
      border: none;
      border-radius: 6px;
      padding: 0 20px;
      cursor: pointer;
      transition: 0.3s;
    }

    .search-btn:hover {
      background: var(--ustp-light-blue);
    }

    .add-org-btn {
      background: var(--ustp-gold);
      color: var(--ustp-blue);
      border: none;
      border-radius: 6px;
      padding: 10px 20px;
      cursor: pointer;
      font-weight: 600;
      transition: 0.3s;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .add-org-btn:hover {
      background: #e6b800;
    }

    /* Department Boxes */
    .department-boxes {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 25px;
      margin-bottom: 30px;
    }

    .department-box {
      background: var(--ustp-white);
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      overflow: hidden;
      transition: all 0.3s ease;
      cursor: pointer;
      border: 2px solid transparent;
    }

    .department-box:hover {
      transform: translateY(-8px);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
      border-color: var(--ustp-gold);
    }

    .department-header {
      padding: 20px;
      background: var(--ustp-blue);
      color: white;
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .department-avatar {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      border: 2px solid var(--ustp-gold);
      object-fit: cover;
      background-color: var(--ustp-white);
    }

    .department-info h3 {
      font-size: 18px;
      margin-bottom: 5px;
    }

    .department-info p {
      font-size: 14px;
      opacity: 0.9;
    }

    .department-stats {
      padding: 15px 20px;
      display: flex;
      justify-content: space-between;
      background: rgba(0, 102, 204, 0.05);
    }

    .stat {
      text-align: center;
    }

    .stat-value {
      font-size: 20px;
      font-weight: 700;
      color: var(--ustp-blue);
    }

    .stat-label {
      font-size: 12px;
      color: #666;
    }

    .department-actions {
      padding: 15px 20px;
      border-top: 1px solid #eee;
      text-align: center;
    }

    .btn-view-department {
      background: var(--ustp-blue);
      color: white;
      border: none;
      border-radius: 6px;
      padding: 10px 20px;
      cursor: pointer;
      font-weight: 600;
      transition: 0.3s;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      text-decoration: none;
    }

    .btn-view-department:hover {
      background: var(--ustp-light-blue);
    }

    /* Modal */
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 1000;
      justify-content: center;
      align-items: center;
    }

    .modal-content {
      background-color: white;
      padding: 30px;
      border-radius: 10px;
      width: 90%;
      max-width: 500px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 1px solid #eee;
    }

    .modal-header h2 {
      color: var(--ustp-blue);
      margin: 0;
    }

    .close-modal {
      background: none;
      border: none;
      font-size: 24px;
      cursor: pointer;
      color: var(--ustp-dark-gray);
    }

    .modal-actions {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-top: 20px;
    }

    .btn {
      padding: 10px 20px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
      transition: 0.3s;
    }

    .btn-primary {
      background: var(--ustp-blue);
      color: white;
    }

    .btn-primary:hover {
      background: var(--ustp-light-blue);
    }

    .btn-secondary {
      background: #6c757d;
      color: white;
    }

    .btn-secondary:hover {
      background: #5a6268;
    }

    /* Form Styles */
    .form-group {
      margin-bottom: 20px;
    }

    .form-label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: var(--ustp-blue);
    }

    .form-input {
      width: 100%;
      padding: 10px 15px;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 14px;
    }

    .form-input:focus {
      outline: none;
      border-color: var(--ustp-blue);
    }

    /* Toast notification */
    .toast {
      position: fixed;
      bottom: 20px;
      right: 20px;
      padding: 15px 25px;
      background-color: var(--ustp-blue);
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
      background-color: #4caf50;
    }

    .toast.error {
      background-color: #f44336;
    }

    /* Responsive - EXACTLY THE SAME AS OTHER PAGES */
    @media (max-width: 768px) {
      .sidebar { width: 70px; }
      .logo-container h2, .nav-item a { display: none; }
      .main-content { margin-left: 70px; }
      .menu-toggle { display: block; }
      .officer-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
      }
      .search-box {
        width: 100%;
      }
      .search-input {
        width: 100%;
      }
      .department-boxes {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>

  <!-- SIDEBAR - EXACTLY THE SAME AS OTHER PAGES -->
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

    <div class="user-profile" id="userProfile">
      <img src="../assests/default-avatar.jpg" class="user-avatar" />
      <div class="user-info">
        <h4>Gamaliel Valcorza</h4>
        <p>Administrator</p>
      </div>
      <div class="user-dropdown" id="dropdown">
        <a href="../login.html" id="logoutBtn">Logout</a>
      </div>
    </div>
  </nav>

  <!-- MAIN CONTENT -->
  <div class="main-content" id="mainContent">
    <div class="header">
      <h1>Officer Management</h1>
      <i class="fas fa-bars menu-toggle" id="menuToggle"></i>
    </div>

    <div class="content">
      <div class="officer-header">
        <h2 class="officer-title">Manage Officers by Organization</h2>
        <div class="search-box">
          <input type="text" class="search-input" placeholder="Search organizations..." id="searchInput">
          <button class="search-btn"><i class="fas fa-search"></i></button>
          <button class="add-org-btn" id="addOrgBtn">
            <i class="fas fa-plus"></i> Add Organization
          </button>
        </div>
      </div>

      <!-- Department Boxes -->
      <div class="department-boxes" id="departmentBoxes">
        <!-- Department boxes will be dynamically generated here -->
      </div>
    </div>
  </div>

 <!-- Add Organization Modal -->
  <div class="modal" id="addOrgModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Add New Organization</h2>
        <button class="close-modal" id="closeOrgModal">&times;</button>
      </div>
      <form id="orgForm" method="POST" action="../php/addOrganization.php">
        <div class="form-group">
          <label class="form-label" for="orgCode">Organization Code</label>
          <input type="text" class="form-input" id="orgCode" name="orgCode" placeholder="e.g., IT" required>
        </div>

       <!-- Course Dropdown -->
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
          <input type="text" class="form-input" id="orgDescription" name="orgDescription" placeholder="e.g., Department of Information Technology" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="orgEmail">Email</label>
          <input type="email" class="form-input" id="orgEmail" name="orgEmail" placeholder="e.g., info@organization.com" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="orgImage">Logo URL</label>
          <input type="text" class="form-input" id="orgImage" name="orgImage" placeholder="e.g., ../assets/it-logo.jpg" required>
        </div>

        <div class="modal-actions">
          <button type="button" class="btn btn-secondary" id="cancelOrgBtn">Cancel</button>
          <button type="submit" class="btn btn-primary">Add Organization</button>
        </div>
      </form>

  </div>

  <!-- Toast notification -->
  <div class="toast" id="toast"></div>

  <script>
// ✅ DOM Elements
const departmentBoxes = document.getElementById('departmentBoxes');
const searchInput = document.getElementById('searchInput');
const addOrgBtn = document.getElementById('addOrgBtn');
const addOrgModal = document.getElementById('addOrgModal');
const closeOrgModal = document.getElementById('closeOrgModal');
const cancelOrgBtn = document.getElementById('cancelOrgBtn');
const toast = document.getElementById('toast');

let departmentData = [];
let currentSearch = '';

// ✅ Fetch organizations from the database
async function loadDepartments() {
  try {
    const response = await fetch('../php/fetchOrganizations.php', { cache: 'no-store' });
    if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
    
    const data = await response.json();

    // ✅ Check if backend sent an error
    if (data.error) {
      showToast(data.error, 'error');
      departmentBoxes.innerHTML = '<p style="color:red;">Failed to load organizations.</p>';
      return;
    }

    // ✅ Format data for rendering
    departmentData = data.map(org => ({
      Org_ID: org.Org_ID,
      Org_Name: org.Org_Name,
      Description: org.Description || 'No description available',
      Logo_Url: org.Logo_Url && org.Logo_Url.trim() !== '' ? org.Logo_Url : '../assets/default-logo.jpg',
      Officers: 0,   // static for now
      Positions: 0   // static for now
    }));

    renderDepartmentBoxes();
  } catch (error) {
    console.error('Error loading organizations:', error);
    showToast('Failed to load organizations', 'error');
  }
}

  // ✅ Render organization boxes
  function renderDepartmentBoxes() {
    departmentBoxes.innerHTML = '';

    const filtered = departmentData.filter(org =>
      org.Org_Name?.toLowerCase().includes(currentSearch.toLowerCase()) ||
      org.Description?.toLowerCase().includes(currentSearch.toLowerCase())
    );

    if (filtered.length === 0) {
      departmentBoxes.innerHTML = `
        <div class="empty-state" style="text-align:center; padding:40px;">
          <i class="fas fa-users" style="font-size:40px; color:#003366;"></i>
          <h3>No organizations found</h3>
        </div>
      `;
      return;
    }

    filtered.forEach(org => {
      const logo = org.Logo_Url && org.Logo_Url.trim() !== '' 
        ? org.Logo_Url 
        : '../assets/default-logo.jpg'; // ✅ fixed typo (assets not assests)

      const box = document.createElement('div');
      box.className = 'department-box';
      box.innerHTML = `
        <div class="department-header">
          <img src="${logo}" class="department-avatar" alt="${org.Org_Name}">
          <div class="department-info">
            <h3>${org.Org_Name}</h3>
            <p>${org.Description || 'No description available'}</p>
          </div>
        </div>
        <div class="department-stats">
          <div class="stat">
            <div class="stat-value">0</div>
            <div class="stat-label">Total Officers</div>
          </div>
          <div class="stat">
            <div class="stat-value">0</div>
            <div class="stat-label">Positions</div>
          </div>
        </div>

        <div class="department-actions">
          <a href="officers.php?org=${encodeURIComponent(org.Org_ID)}" class="btn-view-department">
            <i class="fas fa-arrow-right"></i> View Officers
          </a>
        </div>
      `;
      departmentBoxes.appendChild(box);
    });
  }

// ✅ Toast message
function showToast(message, type = 'info') {
  toast.textContent = message;
  toast.className = `toast ${type} show`;
  setTimeout(() => toast.classList.remove('show'), 3000);
}

// ✅ Modal controls
addOrgBtn.onclick = () => (addOrgModal.style.display = 'flex');
closeOrgModal.onclick = cancelOrgBtn.onclick = () => (addOrgModal.style.display = 'none');
window.onclick = e => { if (e.target === addOrgModal) addOrgModal.style.display = 'none'; };

// ✅ Search input handler
searchInput.addEventListener('input', e => {
  currentSearch = e.target.value.trim();
  renderDepartmentBoxes();
});

// ✅ Load data when page is ready
document.addEventListener('DOMContentLoaded', loadDepartments);

  </script>
</body>
</html>
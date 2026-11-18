<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>eBOTO | Election Reports</title>
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

    /* Reports Specific Styles */
    .reports-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
    }

    .reports-title {
      color: var(--ustp-blue);
      font-size: 24px;
    }

    .filter-box {
      display: flex;
      gap: 10px;
      align-items: center;
    }

    .filter-select {
      padding: 10px 15px;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 14px;
      background-color: white;
    }

    .generate-report-btn {
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

    .generate-report-btn:hover {
      background: #e6b800;
    }

    /* Report Boxes */
    .report-boxes {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 25px;
    }

    .report-box {
      background: var(--ustp-white);
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      overflow: hidden;
      transition: all 0.3s ease;
      border: 2px solid transparent;
      cursor: pointer;
    }

    .report-box:hover {
      transform: translateY(-8px);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
      border-color: var(--ustp-gold);
    }

    .report-header-box {
      padding: 20px;
      color: white;
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .report-avatar {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      border: 2px solid var(--ustp-gold);
      background-color: var(--ustp-white);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
    }

    .report-info h3 {
      font-size: 18px;
      margin-bottom: 5px;
    }

    .report-info p {
      font-size: 14px;
      opacity: 0.9;
    }

    .report-stats {
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

    .report-details {
      padding: 15px 20px;
      border-top: 1px solid #eee;
    }

    .report-detail {
      display: flex;
      justify-content: space-between;
      margin-bottom: 8px;
      font-size: 14px;
    }

    .report-detail-label {
      font-weight: 600;
      color: var(--ustp-blue);
    }

    .report-actions {
      padding: 15px 20px;
      border-top: 1px solid #eee;
      display: flex;
      justify-content: space-between;
    }

    .btn-view-report {
      background: var(--ustp-blue);
      color: white;
      border: none;
      border-radius: 6px;
      padding: 8px 15px;
      cursor: pointer;
      font-weight: 600;
      transition: 0.3s;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      text-decoration: none;
      font-size: 14px;
    }

    .btn-view-report:hover {
      background: var(--ustp-light-blue);
    }

    .btn-download {
      background: transparent;
      color: var(--ustp-blue);
      border: 1px solid var(--ustp-blue);
      border-radius: 6px;
      padding: 8px 12px;
      cursor: pointer;
      font-weight: 600;
      transition: 0.3s;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      font-size: 14px;
    }

    .btn-download:hover {
      background: var(--ustp-blue);
      color: white;
    }

    /* Status badges */
    .status-badge {
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
    }

    .status-completed {
      background: #d4edda;
      color: #155724;
    }

    .status-pending {
      background: #fff3cd;
      color: #856404;
    }

    .status-processing {
      background: #d1ecf1;
      color: #0c5460;
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

    .form-row {
      display: flex;
      gap: 15px;
    }

    .form-row .form-group {
      flex: 1;
    }

    .form-select {
      width: 100%;
      padding: 10px 15px;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 14px;
      background-color: white;
    }

    /* View Report Modal */
    .view-report-modal {
      max-width: 800px;
      width: 90%;
    }

    .report-preview {
      background: #f8f9fa;
      border-radius: 8px;
      padding: 20px;
      margin: 20px 0;
      max-height: 400px;
      overflow-y: auto;
    }

    .report-content {
      background: white;
      padding: 20px;
      border-radius: 6px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .report-section {
      margin-bottom: 20px;
    }

    .report-section h4 {
      color: var(--ustp-blue);
      margin-bottom: 10px;
      border-bottom: 1px solid #eee;
      padding-bottom: 5px;
    }

    .report-data {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
    }

    .data-item {
      display: flex;
      justify-content: space-between;
      padding: 5px 0;
      border-bottom: 1px solid #f0f0f0;
    }

    .data-label {
      font-weight: 600;
      color: var(--ustp-blue);
    }

    .data-value {
      color: #333;
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

    /* Responsive */
    @media (max-width: 768px) {
      .sidebar { width: 70px; }
      .logo-container h2, .nav-item a { display: none; }
      .main-content { margin-left: 70px; }
      .menu-toggle { display: block; }
      .reports-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
      }
      .filter-box {
        width: 100%;
        justify-content: space-between;
      }
      .report-boxes {
        grid-template-columns: 1fr;
      }
      .report-actions {
        flex-direction: column;
        gap: 10px;
      }
      .form-row {
        flex-direction: column;
        gap: 0;
      }
      .report-data {
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
      <li class="nav-item"><i class="fas fa-user-tie"></i><a href="register-officers.php">Officers</a></li>
      <li class="nav-item"><i class="fas fa-user-tag"></i><a href="candidates.php">Candidates</a></li>
      <li class="nav-item"><i class="fas fa-check-circle"></i><a href="validate-elections.php">Elections</a></li>
      <li class="nav-item"><i class="fas fa-chart-line"></i><a href="monitoring.php">Monitoring</a></li>
      <li class="nav-item active"><i class="fas fa-file-alt"></i><a href="audit-reports.php">Reports</a></li>
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
      <h1>Election Reports</h1>
      <i class="fas fa-bars menu-toggle" id="menuToggle"></i>
    </div>

    <div class="content">
      <div class="reports-header">
        <h2 class="reports-title">Election Reports</h2>
        <div class="filter-box">
          <select class="filter-select" id="timeFilter">
            <option value="all">All Time</option>
            <option value="today">Today</option>
            <option value="week">This Week</option>
            <option value="month">This Month</option>
            <option value="quarter">This Quarter</option>
            <option value="year">This Year</option>
          </select>
          <select class="filter-select" id="organizationFilter">
            <option value="all">All Organizations</option>
            <option value="SOCE">SOCE</option>
            <option value="SBA">SBA</option>
            <option value="SAS">SAS</option>
            <option value="SNAHS">SNAHS</option>
            <option value="USG">USG</option>
          </select>
          <button class="generate-report-btn" id="generateReportBtn">
            <i class="fas fa-plus"></i> Generate Report
          </button>
        </div>
      </div>

      <!-- Recent Reports -->
      <div class="report-boxes" id="reportBoxes">
        <!-- Report boxes will be dynamically generated here -->
      </div>
    </div>
  </div>

  <!-- Generate Report Modal -->
  <div class="modal" id="generateReportModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Generate New Report</h2>
        <button class="close-modal" id="closeReportModal">&times;</button>
      </div>
      <form id="reportForm">
        <div class="form-group">
          <label class="form-label" for="reportType">Report Type</label>
          <select class="form-select" id="reportType" required>
            <option value="">Select Report Type</option>
            <option value="election_results">Election Results</option>
            <option value="voter_turnout">Voter Turnout</option>
            <option value="candidate_performance">Candidate Performance</option>
            <option value="voter_demographics">Voter Demographics</option>
            <option value="election_audit">Election Audit</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="reportElection">Election</label>
          <select class="form-select" id="reportElection" required>
            <option value="">Select Election</option>
            <option value="SOCE_2024">SOCE Officers Election 2024</option>
            <option value="USG_2024">USG General Election 2024</option>
            <option value="CACHE_2024">CACHE Publication Election</option>
            <option value="SBA_2024">SBA Department Election</option>
            <option value="all">All Elections</option>
          </select>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="startDate">Start Date</label>
            <input type="date" class="form-input" id="startDate" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="endDate">End Date</label>
            <input type="date" class="form-input" id="endDate" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label" for="reportFormat">Format</label>
          <select class="form-select" id="reportFormat" required>
            <option value="pdf">PDF Document</option>
            <option value="excel">Excel Spreadsheet</option>
            <option value="csv">CSV File</option>
            <option value="html">Web Page</option>
          </select>
        </div>
        <div class="modal-actions">
          <button type="button" class="btn btn-secondary" id="cancelReportBtn">Cancel</button>
          <button type="submit" class="btn btn-primary">Generate Report</button>
        </div>
      </form>
    </div>
  </div>

  <!-- View Report Modal -->
  <div class="modal" id="viewReportModal">
    <div class="modal-content view-report-modal">
      <div class="modal-header">
        <h2 id="viewReportTitle">Report Details</h2>
        <button class="close-modal" id="closeViewModal">&times;</button>
      </div>
      <div class="report-preview">
        <div class="report-content" id="reportContent">
          <!-- Report content will be dynamically generated here -->
        </div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-secondary" id="closeViewBtn">Close</button>
        <button type="button" class="btn btn-primary" id="downloadViewBtn">
          <i class="fas fa-download"></i> Download
        </button>
      </div>
    </div>
  </div>

  <!-- Toast notification -->
  <div class="toast" id="toast"></div>

  <script>
    // Reports data - Only election-related reports
    let reportsData = [
      {
        id: "1",
        title: "SOCE Election Results 2024",
        type: "election_results",
        organization: "SOCE",
        status: "completed",
        generatedDate: "2024-03-18",
        fileSize: "2.4 MB",
        downloads: 45,
        color: "#003366",
        content: {
          totalVoters: 1250,
          votesCast: 980,
          turnout: "78.4%",
          winningCandidate: "John Smith",
          votesReceived: 520,
          margin: "12.3%",
          candidates: [
            { name: "John Smith", votes: 520, percentage: "53.1%" },
            { name: "Maria Garcia", votes: 320, percentage: "32.7%" },
            { name: "David Johnson", votes: 140, percentage: "14.3%" }
          ]
        }
      },
      {
        id: "2",
        title: "Voter Turnout Report",
        type: "voter_turnout",
        organization: "USG",
        status: "completed",
        generatedDate: "2024-04-01",
        fileSize: "1.8 MB",
        downloads: 32,
        color: "#0066cc",
        content: {
          totalVoters: 2150,
          votesCast: 1720,
          turnout: "80.0%",
          byDepartment: [
            { department: "SOCE", voters: 450, voted: 380, turnout: "84.4%" },
            { department: "SBA", voters: 620, voted: 480, turnout: "77.4%" },
            { department: "SAS", voters: 580, voted: 460, turnout: "79.3%" },
            { department: "SNAHS", voters: 500, voted: 400, turnout: "80.0%" }
          ],
          peakHours: ["10:00-11:00 AM", "2:00-3:00 PM", "4:00-5:00 PM"]
        }
      },
      {
        id: "3",
        title: "Candidate Performance Report",
        type: "candidate_performance",
        organization: "USG",
        status: "processing",
        generatedDate: "2024-04-03",
        fileSize: "Processing...",
        downloads: 0,
        color: "#4caf50",
        content: {}
      },
      {
        id: "4",
        title: "Voter Demographics Report",
        type: "voter_demographics",
        organization: "SOCE",
        status: "pending",
        generatedDate: "2024-04-04",
        fileSize: "Pending",
        downloads: 0,
        color: "#ff9800",
        content: {}
      },
      {
        id: "5",
        title: "USG Election Audit Report",
        type: "election_audit",
        organization: "USG",
        status: "completed",
        generatedDate: "2024-04-05",
        fileSize: "1.2 MB",
        downloads: 28,
        color: "#f44336",
        content: {
          auditDate: "2024-04-05",
          auditor: "Election Commission",
          findings: "All processes compliant",
          issues: "None identified",
          recommendations: "Continue current procedures",
          verification: "100% of votes verified"
        }
      },
      {
        id: "6",
        title: "SBA Election Results 2024",
        type: "election_results",
        organization: "SBA",
        status: "completed",
        generatedDate: "2024-04-06",
        fileSize: "2.1 MB",
        downloads: 23,
        color: "#795548",
        content: {
          totalVoters: 890,
          votesCast: 720,
          turnout: "80.9%",
          winningCandidate: "Sarah Chen",
          votesReceived: 410,
          margin: "18.2%",
          candidates: [
            { name: "Sarah Chen", votes: 410, percentage: "56.9%" },
            { name: "Michael Brown", votes: 210, percentage: "29.2%" },
            { name: "Lisa Wilson", votes: 100, percentage: "13.9%" }
          ]
        }
      }
    ];

    // Report types data - Only election-related types
    let reportTypesData = [
      {
        id: "election_results",
        name: "Election Results"
      },
      {
        id: "voter_turnout",
        name: "Voter Turnout"
      },
      {
        id: "candidate_performance",
        name: "Candidate Performance"
      },
      {
        id: "voter_demographics",
        name: "Voter Demographics"
      },
      {
        id: "election_audit",
        name: "Election Audit"
      }
    ];

    // DOM Elements
    const userProfile = document.getElementById('userProfile');
    const dropdown = document.getElementById('dropdown');
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    const logoutBtn = document.getElementById('logoutBtn');
    const timeFilter = document.getElementById('timeFilter');
    const organizationFilter = document.getElementById('organizationFilter');
    const generateReportBtn = document.getElementById('generateReportBtn');
    const reportBoxes = document.getElementById('reportBoxes');
    const generateReportModal = document.getElementById('generateReportModal');
    const closeReportModal = document.getElementById('closeReportModal');
    const cancelReportBtn = document.getElementById('cancelReportBtn');
    const reportForm = document.getElementById('reportForm');
    const viewReportModal = document.getElementById('viewReportModal');
    const closeViewModal = document.getElementById('closeViewModal');
    const closeViewBtn = document.getElementById('closeViewBtn');
    const downloadViewBtn = document.getElementById('downloadViewBtn');
    const viewReportTitle = document.getElementById('viewReportTitle');
    const reportContent = document.getElementById('reportContent');
    const toast = document.getElementById('toast');

    // Current filters and selected report
    let currentTimeFilter = 'all';
    let currentOrganizationFilter = 'all';
    let currentViewReport = null;

    // Initialize reports page
    function initReportsPage() {
      renderReportBoxes();
      setupEventListeners();
    }

    // Render report boxes
    function renderReportBoxes() {
      reportBoxes.innerHTML = '';
      
      let filteredReports = reportsData;
      
      // Apply time filter (simplified for demo)
      if (currentTimeFilter !== 'all') {
        filteredReports = filteredReports.filter(report => {
          return report.id <= 3; // Show only first 3 reports for "recent" filter
        });
      }
      
      // Apply organization filter
      if (currentOrganizationFilter !== 'all') {
        filteredReports = filteredReports.filter(report => 
          report.organization === currentOrganizationFilter || report.organization === 'All'
        );
      }
      
      if (filteredReports.length === 0) {
        reportBoxes.innerHTML = `
          <div class="empty-state" style="grid-column: 1 / -1; text-align: center; padding: 40px;">
            <i class="fas fa-file-alt" style="font-size: 48px; color: #ccc; margin-bottom: 15px;"></i>
            <h3 style="color: #666; margin-bottom: 10px;">No reports found</h3>
            <p style="color: #888;">Try changing your filters or generate a new report</p>
          </div>
        `;
        return;
      }
      
      filteredReports.forEach(report => {
        const reportBox = document.createElement('div');
        reportBox.className = 'report-box';
        reportBox.setAttribute('data-report', report.id);
        
        // Determine status badge class
        let statusClass = '';
        let statusText = '';
        switch(report.status) {
          case 'completed':
            statusClass = 'status-completed';
            statusText = 'Completed';
            break;
          case 'processing':
            statusClass = 'status-processing';
            statusText = 'Processing';
            break;
          case 'pending':
            statusClass = 'status-pending';
            statusText = 'Pending';
            break;
        }
        
        // Format date
        const generatedDate = new Date(report.generatedDate);
        const formattedDate = generatedDate.toLocaleDateString();
        
        reportBox.innerHTML = `
          <div class="report-header-box" style="background-color: ${report.color}">
            <div class="report-avatar" style="background-color: ${report.color}">
              <i class="fas fa-file-alt"></i>
            </div>
            <div class="report-info">
              <h3>${report.organization}</h3>
              <p>${report.title}</p>
            </div>
          </div>
          <div class="report-stats">
            <div class="stat">
              <div class="stat-value">${report.fileSize}</div>
              <div class="stat-label">File Size</div>
            </div>
            <div class="stat">
              <div class="stat-value">${report.downloads}</div>
              <div class="stat-label">Downloads</div>
            </div>
          </div>
          <div class="report-details">
            <div class="report-detail">
              <span class="report-detail-label">Status:</span>
              <span class="status-badge ${statusClass}">${statusText}</span>
            </div>
            <div class="report-detail">
              <span class="report-detail-label">Generated:</span>
              <span>${formattedDate}</span>
            </div>
            <div class="report-detail">
              <span class="report-detail-label">Type:</span>
              <span>${getReportTypeName(report.type)}</span>
            </div>
          </div>
          <div class="report-actions">
            <button class="btn-view-report view-report-btn" data-report="${report.id}">
              <i class="fas fa-eye"></i> View Report
            </button>
            <button class="btn-download" data-report="${report.id}" ${report.status !== 'completed' ? 'disabled' : ''}>
              <i class="fas fa-download"></i> Download
            </button>
          </div>
        `;
        
        reportBoxes.appendChild(reportBox);
      });
      
      // Add event listeners to view buttons
      document.querySelectorAll('.view-report-btn').forEach(btn => {
        btn.addEventListener('click', handleViewReport);
      });
      
      // Add event listeners to download buttons
      document.querySelectorAll('.btn-download').forEach(btn => {
        btn.addEventListener('click', handleDownloadReport);
      });

      // Add click event to entire report box for viewing
      document.querySelectorAll('.report-box').forEach(box => {
        box.addEventListener('click', (e) => {
          // Don't trigger if clicking on buttons inside the box
          if (!e.target.closest('.btn-view-report') && !e.target.closest('.btn-download')) {
            const reportId = box.getAttribute('data-report');
            handleViewReport({ currentTarget: { getAttribute: () => reportId } });
          }
        });
      });
    }

    // Handle view report
    function handleViewReport(e) {
      const reportId = e.currentTarget.getAttribute('data-report');
      const report = reportsData.find(r => r.id === reportId);
      
      if (report) {
        currentViewReport = report;
        showReportDetails(report);
      }
    }

    // Show report details in modal
    function showReportDetails(report) {
      viewReportTitle.textContent = report.title;
      
      // Generate report content based on type
      let contentHTML = '';
      
      if (report.status !== 'completed') {
        contentHTML = `
          <div style="text-align: center; padding: 40px;">
            <i class="fas fa-clock" style="font-size: 48px; color: #ffc107; margin-bottom: 20px;"></i>
            <h3 style="color: #666; margin-bottom: 10px;">Report is ${report.status}</h3>
            <p style="color: #888;">This report is currently being processed. Please check back later.</p>
          </div>
        `;
      } else {
        switch(report.type) {
          case 'election_results':
            contentHTML = generateElectionResultsContent(report);
            break;
          case 'voter_turnout':
            contentHTML = generateVoterTurnoutContent(report);
            break;
          case 'election_audit':
            contentHTML = generateElectionAuditContent(report);
            break;
          default:
            contentHTML = generateDefaultContent(report);
        }
      }
      
      reportContent.innerHTML = contentHTML;
      viewReportModal.style.display = 'flex';
    }

    // Generate election results content
    function generateElectionResultsContent(report) {
      const content = report.content;
      return `
        <div class="report-section">
          <h4>Election Summary</h4>
          <div class="report-data">
            <div class="data-item">
              <span class="data-label">Total Voters:</span>
              <span class="data-value">${content.totalVoters}</span>
            </div>
            <div class="data-item">
              <span class="data-label">Votes Cast:</span>
              <span class="data-value">${content.votesCast}</span>
            </div>
            <div class="data-item">
              <span class="data-label">Turnout Rate:</span>
              <span class="data-value">${content.turnout}</span>
            </div>
            <div class="data-item">
              <span class="data-label">Winning Candidate:</span>
              <span class="data-value">${content.winningCandidate}</span>
            </div>
            <div class="data-item">
              <span class="data-label">Votes Received:</span>
              <span class="data-value">${content.votesReceived}</span>
            </div>
            <div class="data-item">
              <span class="data-label">Winning Margin:</span>
              <span class="data-value">${content.margin}</span>
            </div>
          </div>
        </div>
        <div class="report-section">
          <h4>Candidate Results</h4>
          ${content.candidates.map(candidate => `
            <div class="data-item">
              <span class="data-label">${candidate.name}:</span>
              <span class="data-value">${candidate.votes} votes (${candidate.percentage})</span>
            </div>
          `).join('')}
        </div>
      `;
    }

    // Generate voter turnout content
    function generateVoterTurnoutContent(report) {
      const content = report.content;
      return `
        <div class="report-section">
          <h4>Overall Turnout</h4>
          <div class="report-data">
            <div class="data-item">
              <span class="data-label">Total Voters:</span>
              <span class="data-value">${content.totalVoters}</span>
            </div>
            <div class="data-item">
              <span class="data-label">Votes Cast:</span>
              <span class="data-value">${content.votesCast}</span>
            </div>
            <div class="data-item">
              <span class="data-label">Turnout Rate:</span>
              <span class="data-value">${content.turnout}</span>
            </div>
          </div>
        </div>
        <div class="report-section">
          <h4>Turnout by Department</h4>
          ${content.byDepartment.map(dept => `
            <div class="data-item">
              <span class="data-label">${dept.department}:</span>
              <span class="data-value">${dept.voted}/${dept.voters} (${dept.turnout})</span>
            </div>
          `).join('')}
        </div>
        <div class="report-section">
          <h4>Peak Voting Hours</h4>
          <div style="padding: 10px;">
            ${content.peakHours.map(hour => `<div style="margin: 5px 0;">• ${hour}</div>`).join('')}
          </div>
        </div>
      `;
    }

    // Generate election audit content
    function generateElectionAuditContent(report) {
      const content = report.content;
      return `
        <div class="report-section">
          <h4>Audit Information</h4>
          <div class="report-data">
            <div class="data-item">
              <span class="data-label">Audit Date:</span>
              <span class="data-value">${content.auditDate}</span>
            </div>
            <div class="data-item">
              <span class="data-label">Auditor:</span>
              <span class="data-value">${content.auditor}</span>
            </div>
            <div class="data-item">
              <span class="data-label">Verification Rate:</span>
              <span class="data-value">${content.verification}</span>
            </div>
          </div>
        </div>
        <div class="report-section">
          <h4>Findings</h4>
          <div style="padding: 10px; background: #e8f5e8; border-radius: 4px; margin: 10px 0;">
            ${content.findings}
          </div>
        </div>
        <div class="report-section">
          <h4>Issues Identified</h4>
          <div style="padding: 10px; background: ${content.issues === 'None identified' ? '#e8f5e8' : '#fff3cd'}; border-radius: 4px; margin: 10px 0;">
            ${content.issues}
          </div>
        </div>
        <div class="report-section">
          <h4>Recommendations</h4>
          <div style="padding: 10px; background: #e3f2fd; border-radius: 4px; margin: 10px 0;">
            ${content.recommendations}
          </div>
        </div>
      `;
    }

    // Generate default content for other report types
    function generateDefaultContent(report) {
      return `
        <div style="text-align: center; padding: 40px;">
          <i class="fas fa-file-alt" style="font-size: 48px; color: #003366; margin-bottom: 20px;"></i>
          <h3 style="color: #666; margin-bottom: 10px;">${report.title}</h3>
          <p style="color: #888;">This report contains detailed information about the ${getReportTypeName(report.type).toLowerCase()}.</p>
          <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 6px;">
            <div class="data-item">
              <span class="data-label">Organization:</span>
              <span class="data-value">${report.organization}</span>
            </div>
            <div class="data-item">
              <span class="data-label">Generated:</span>
              <span class="data-value">${new Date(report.generatedDate).toLocaleDateString()}</span>
            </div>
            <div class="data-item">
              <span class="data-label">File Size:</span>
              <span class="data-value">${report.fileSize}</span>
            </div>
          </div>
        </div>
      `;
    }

    // Handle download report
    function handleDownloadReport(e) {
      e.stopPropagation(); // Prevent triggering the view function
      const reportId = e.currentTarget.getAttribute('data-report');
      const report = reportsData.find(r => r.id === reportId);
      
      if (report && report.status === 'completed') {
        showToast(`Downloading ${report.title}...`, 'success');
        
        // Simulate download
        setTimeout(() => {
          showToast(`${report.title} downloaded successfully!`, 'success');
          
          // Update download count
          report.downloads += 1;
          renderReportBoxes();
        }, 1500);
      }
    }

    // Get report type name from ID
    function getReportTypeName(typeId) {
      const reportType = reportTypesData.find(rt => rt.id === typeId);
      return reportType ? reportType.name : typeId;
    }

    // Generate new report
    function generateReport(reportData) {
      // Generate a unique ID
      const id = (reportsData.length + 1).toString();
      
      // Determine organization based on election selection
      let organization = "All";
      if (reportData.election !== "all") {
        organization = reportData.election.split('_')[0]; // Extract org from election ID
      }
      
      // Add the new report to the data
      reportsData.unshift({
        id: id,
        title: `${getReportTypeName(reportData.type)} - ${new Date().toLocaleDateString()}`,
        type: reportData.type,
        organization: organization,
        status: 'processing',
        generatedDate: new Date().toISOString().split('T')[0],
        fileSize: 'Processing...',
        downloads: 0,
        color: getRandomColor(),
        content: {}
      });
      
      // Re-render the report boxes
      renderReportBoxes();
      
      // Show success message
      showToast(`Report generation started for ${getReportTypeName(reportData.type)}`, 'success');
      
      // Simulate report processing
      setTimeout(() => {
        const newReport = reportsData.find(r => r.id === id);
        if (newReport) {
          newReport.status = 'completed';
          newReport.fileSize = `${(Math.random() * 3 + 0.5).toFixed(1)} MB`;
          // Add sample content based on report type
          newReport.content = generateSampleContent(newReport.type);
          renderReportBoxes();
          showToast(`${getReportTypeName(reportData.type)} report completed!`, 'success');
        }
      }, 3000);
    }

    // Generate sample content for new reports
    function generateSampleContent(type) {
      switch(type) {
        case 'election_results':
          return {
            totalVoters: Math.floor(Math.random() * 2000) + 500,
            votesCast: Math.floor(Math.random() * 1500) + 400,
            turnout: `${(Math.random() * 20 + 70).toFixed(1)}%`,
            winningCandidate: "Sample Candidate",
            votesReceived: Math.floor(Math.random() * 800) + 200,
            margin: `${(Math.random() * 15 + 5).toFixed(1)}%`,
            candidates: [
              { name: "Sample Candidate", votes: 520, percentage: "53.1%" },
              { name: "Opponent One", votes: 320, percentage: "32.7%" },
              { name: "Opponent Two", votes: 140, percentage: "14.3%" }
            ]
          };
        case 'voter_turnout':
          return {
            totalVoters: Math.floor(Math.random() * 2000) + 500,
            votesCast: Math.floor(Math.random() * 1500) + 400,
            turnout: `${(Math.random() * 20 + 70).toFixed(1)}%`,
            byDepartment: [
              { department: "SOCE", voters: 450, voted: 380, turnout: "84.4%" },
              { department: "SBA", voters: 620, voted: 480, turnout: "77.4%" },
              { department: "SAS", voters: 580, voted: 460, turnout: "79.3%" }
            ],
            peakHours: ["10:00-11:00 AM", "2:00-3:00 PM", "4:00-5:00 PM"]
          };
        case 'election_audit':
          return {
            auditDate: new Date().toISOString().split('T')[0],
            auditor: "Election Commission",
            findings: "All processes compliant with election guidelines",
            issues: "None identified",
            recommendations: "Continue current election procedures",
            verification: "100% of votes verified and authenticated"
          };
        default:
          return {};
      }
    }

    // Generate random color for report
    function getRandomColor() {
      const colors = [
        '#003366', '#0066cc', '#4caf50', '#9c27b0', 
        '#ff9800', '#f44336', '#795548', '#607d8b'
      ];
      return colors[Math.floor(Math.random() * colors.length)];
    }

    // Show toast notification
    function showToast(message, type = 'info') {
      toast.textContent = message;
      toast.className = 'toast';
      
      if (type === 'success') {
        toast.classList.add('success');
      } else if (type === 'error') {
        toast.classList.add('error');
      }
      
      toast.classList.add('show');
      
      setTimeout(() => {
        toast.classList.remove('show');
      }, 3000);
    }

    // Set up all event listeners
    function setupEventListeners() {
      // User dropdown
      userProfile.addEventListener('click', () => {
        dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
      });

      // Close dropdown when clicking outside
      document.addEventListener('click', (e) => {
        if (!userProfile.contains(e.target)) dropdown.style.display = 'none';
      });

      // Highlight active nav item
      document.querySelectorAll('.nav-item').forEach(item => {
        item.addEventListener('click', () => {
          document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
          item.classList.add('active');
        });
      });

      // Mobile sidebar toggle
      menuToggle.addEventListener('click', () => {
        if (sidebar.style.left === '0px' || sidebar.style.left === '') {
          sidebar.style.left = '-250px';
          mainContent.style.marginLeft = '0';
        } else {
          sidebar.style.left = '0px';
          mainContent.style.marginLeft = '250px';
        }
      });

      // Logout
      logoutBtn.addEventListener('click', () => {
        showToast('Logging out...', 'info');
        setTimeout(() => {
          alert('You have been logged out. In a real application, you would be redirected to the login page.');
        }, 1000);
      });

      // Filter functionality
      timeFilter.addEventListener('change', (e) => {
        currentTimeFilter = e.target.value;
        renderReportBoxes();
      });

      organizationFilter.addEventListener('change', (e) => {
        currentOrganizationFilter = e.target.value;
        renderReportBoxes();
      });

      // Generate report modal
      generateReportBtn.addEventListener('click', () => {
        // Reset form
        reportForm.reset();
        
        // Show modal
        generateReportModal.style.display = 'flex';
      });

      // Close report modal
      closeReportModal.addEventListener('click', () => {
        generateReportModal.style.display = 'none';
        reportForm.reset();
      });

      cancelReportBtn.addEventListener('click', () => {
        generateReportModal.style.display = 'none';
        reportForm.reset();
      });

      // Close modal when clicking outside
      window.addEventListener('click', (e) => {
        if (e.target === generateReportModal) {
          generateReportModal.style.display = 'none';
          reportForm.reset();
        }
        if (e.target === viewReportModal) {
          viewReportModal.style.display = 'none';
        }
      });

      // Close view modal
      closeViewModal.addEventListener('click', () => {
        viewReportModal.style.display = 'none';
      });

      closeViewBtn.addEventListener('click', () => {
        viewReportModal.style.display = 'none';
      });

      // Download from view modal
      downloadViewBtn.addEventListener('click', () => {
        if (currentViewReport) {
          showToast(`Downloading ${currentViewReport.title}...`, 'success');
          
          // Simulate download
          setTimeout(() => {
            showToast(`${currentViewReport.title} downloaded successfully!`, 'success');
            
            // Update download count
            currentViewReport.downloads += 1;
            renderReportBoxes();
            viewReportModal.style.display = 'none';
          }, 1500);
        }
      });

      // Report form submission
      reportForm.addEventListener('submit', (e) => {
        e.preventDefault();
        
        const reportData = {
          type: document.getElementById('reportType').value,
          election: document.getElementById('reportElection').value,
          startDate: document.getElementById('startDate').value,
          endDate: document.getElementById('endDate').value,
          format: document.getElementById('reportFormat').value
        };
        
        generateReport(reportData);
        generateReportModal.style.display = 'none';
        reportForm.reset();
      });
    }

    // Initialize the reports page when DOM is loaded
    document.addEventListener('DOMContentLoaded', initReportsPage);
  </script>
</body>
</html>
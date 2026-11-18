<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>eBOTO | NAME Election Management</title>
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

    /* SIDEBAR */
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

    /* NAVIGATION */
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

    /* USER PROFILE */
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

    /* MAIN CONTENT */
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

    /* NAME Specific Styles */
    .election-detail-header {
      background: linear-gradient(135deg, #4caf50 0%, #81c784 100%);
      color: white;
      padding: 30px;
      border-radius: 12px;
      margin-bottom: 25px;
      display: flex;
      align-items: center;
      gap: 20px;
    }

    .election-detail-avatar {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      border: 4px solid var(--ustp-gold);
      background-color: var(--ustp-white);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 30px;
      color: #4caf50;
    }

    .election-detail-info h1 {
      font-size: 28px;
      margin-bottom: 5px;
    }

    .election-detail-info p {
      font-size: 16px;
      opacity: 0.9;
    }

    .back-button {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: var(--ustp-blue);
      text-decoration: none;
      font-weight: 600;
      margin-bottom: 20px;
      padding: 8px 16px;
      border-radius: 6px;
      background: var(--ustp-white);
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .back-button:hover {
      background: #f0f0f0;
    }

    .election-tabs {
      display: flex;
      border-bottom: 1px solid #ddd;
      margin-bottom: 25px;
    }

    .election-tab {
      padding: 12px 24px;
      cursor: pointer;
      font-weight: 600;
      border-bottom: 3px solid transparent;
    }

    .election-tab.active {
      color: var(--ustp-blue);
      border-bottom: 3px solid var(--ustp-blue);
    }

    .tab-content {
      display: none;
    }

    .tab-content.active {
      display: block;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .stat-card {
      background: var(--ustp-white);
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      text-align: center;
    }

    .stat-card i {
      font-size: 30px;
      color: #4caf50;
      margin-bottom: 10px;
    }

    .stat-card .value {
      font-size: 28px;
      font-weight: 700;
      color: #4caf50;
      margin-bottom: 5px;
    }

    .stat-card .label {
      font-size: 14px;
      color: #666;
    }

    .candidates-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 20px;
    }

    .candidate-card {
      background: var(--ustp-white);
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .candidate-header {
      padding: 15px;
      background: #4caf50;
      color: white;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .candidate-avatar {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      background: var(--ustp-gold);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      color: #4caf50;
    }

    .candidate-info {
      flex: 1;
    }

    .candidate-name {
      font-weight: 700;
      margin-bottom: 3px;
    }

    .candidate-position {
      font-size: 14px;
      opacity: 0.9;
    }

    .candidate-body {
      padding: 15px;
    }

    .candidate-votes {
      font-size: 24px;
      font-weight: 700;
      color: #4caf50;
      text-align: center;
      margin-bottom: 10px;
    }

    .vote-progress {
      height: 8px;
      background: #e0e0e0;
      border-radius: 4px;
      overflow: hidden;
    }

    .vote-progress-bar {
      height: 100%;
      background: #4caf50;
    }

    .status-badge {
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
    }

    .status-upcoming {
      background: #fff3cd;
      color: #856404;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .sidebar { width: 70px; }
      .logo-container h2, .nav-item a { display: none; }
      .main-content { margin-left: 70px; }
      .menu-toggle { display: block; }
      .election-tabs {
        flex-wrap: wrap;
      }
      .election-tab {
        flex: 1;
        min-width: 120px;
        text-align: center;
      }
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
      <li class="nav-item"><i class="fas fa-user-tie"></i><a href="register-officers.php">Officers</a></li>
      <li class="nav-item"><i class="fas fa-user-tag"></i><a href="candidates.php">Candidates</a></li>
      <li class="nav-item active"><i class="fas fa-check-circle"></i><a href="validate-elections.php">Elections</a></li>
      <li class="nav-item"><i class="fas fa-chart-line"></i><a href="monitoring.php">Monitoring</a></li>
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
      <h1>NAME Election Management</h1>
      <i class="fas fa-bars menu-toggle" id="menuToggle"></i>
    </div>

    <div class="content">
      <a href="validate-elections.php" class="back-button">
        <i class="fas fa-arrow-left"></i> Back to Elections
      </a>

      <div class="election-detail-header">
        <div class="election-detail-avatar">
          <i class="fas fa-ship"></i>
        </div>
        <div class="election-detail-info">
          <h1>Naval Architect Marine Engineering Election</h1>
          <p>Election for NAME Department Representatives</p>
          <div class="status-badge status-upcoming" style="margin-top: 10px;">Upcoming</div>
        </div>
      </div>

      <div class="election-tabs">
        <div class="election-tab active" data-tab="overview">Overview</div>
        <div class="election-tab" data-tab="candidates">Candidates</div>
        <div class="election-tab" data-tab="voters">Voters</div>
        <div class="election-tab" data-tab="settings">Settings</div>
      </div>

      <div class="tab-content active" id="overview">
        <div class="stats-grid">
          <div class="stat-card">
            <i class="fas fa-users"></i>
            <div class="value">180</div>
            <div class="label">Registered Voters</div>
          </div>
          <div class="stat-card">
            <i class="fas fa-user-tie"></i>
            <div class="value">15</div>
            <div class="label">Candidates</div>
          </div>
          <div class="stat-card">
            <i class="fas fa-vote-yea"></i>
            <div class="value">0</div>
            <div class="label">Votes Cast</div>
          </div>
          <div class="stat-card">
            <i class="fas fa-percentage"></i>
            <div class="value">0%</div>
            <div class="label">Voter Turnout</div>
          </div>
        </div>

        <h2>Election Details</h2>
        <div class="election-details" style="background: white; padding: 20px; border-radius: 8px; margin-top: 15px;">
          <div class="election-detail">
            <span class="election-detail-label">Start Date:</span>
            <span>March 20, 2024 8:00 AM</span>
          </div>
          <div class="election-detail">
            <span class="election-detail-label">End Date:</span>
            <span>March 22, 2024 5:00 PM</span>
          </div>
          <div class="election-detail">
            <span class="election-detail-label">Organization:</span>
            <span>Naval Architect Marine Engineering</span>
          </div>
          <div class="election-detail">
            <span class="election-detail-label">Election Type:</span>
            <span>Department Representatives</span>
          </div>
        </div>
      </div>

      <div class="tab-content" id="candidates">
        <h2>Candidates</h2>
        <div class="candidates-grid" style="margin-top: 20px;">
          <div class="candidate-card">
            <div class="candidate-header">
              <div class="candidate-avatar">
                <i class="fas fa-user"></i>
              </div>
              <div class="candidate-info">
                <div class="candidate-name">David Wilson</div>
                <div class="candidate-position">President</div>
              </div>
            </div>
            <div class="candidate-body">
              <div class="candidate-votes">0</div>
              <div class="vote-progress">
                <div class="vote-progress-bar" style="width: 0%"></div>
              </div>
            </div>
          </div>
          <div class="candidate-card">
            <div class="candidate-header">
              <div class="candidate-avatar">
                <i class="fas fa-user"></i>
              </div>
              <div class="candidate-info">
                <div class="candidate-name">Lisa Anderson</div>
                <div class="candidate-position">Vice President</div>
              </div>
            </div>
            <div class="candidate-body">
              <div class="candidate-votes">0</div>
              <div class="vote-progress">
                <div class="vote-progress-bar" style="width: 0%"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="tab-content" id="voters">
        <h2>Voter Management</h2>
        <p style="margin-top: 15px;">Voter management interface would go here.</p>
      </div>

      <div class="tab-content" id="settings">
        <h2>Election Settings</h2>
        <p style="margin-top: 15px;">Election configuration options would go here.</p>
      </div>
    </div>
  </div>

  <script>
    // Tab functionality
    document.querySelectorAll('.election-tab').forEach(tab => {
      tab.addEventListener('click', () => {
        // Remove active class from all tabs and contents
        document.querySelectorAll('.election-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        
        // Add active class to clicked tab and corresponding content
        tab.classList.add('active');
        document.getElementById(tab.getAttribute('data-tab')).classList.add('active');
      });
    });

    // Add the same sidebar functionality from your main page
    const userProfile = document.getElementById('userProfile');
    const dropdown = document.getElementById('dropdown');
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');

    userProfile.addEventListener('click', () => {
      dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    });

    document.addEventListener('click', (e) => {
      if (!userProfile.contains(e.target)) dropdown.style.display = 'none';
    });

    menuToggle.addEventListener('click', () => {
      if (sidebar.style.left === '0px' || sidebar.style.left === '') {
        sidebar.style.left = '-250px';
        mainContent.style.marginLeft = '0';
      } else {
        sidebar.style.left = '0px';
        mainContent.style.marginLeft = '250px';
      }
    });
  </script>
</body>
</html>